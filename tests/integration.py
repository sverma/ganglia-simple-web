#!/usr/bin/env python3
"""Isolated tests with synthetic gmetad metadata and real RRDTool images."""
import argparse
import html
import http.cookiejar
import json
import os
from pathlib import Path
import re
import shutil
import socket
import socketserver
import subprocess
import tempfile
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_BINARY', 'php')
RRDTOOL = os.environ.get('RRDTOOL_BINARY', 'rrdtool')


def free_port():
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        return sock.getsockname()[1]


class MetadataServer(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    daemon_threads = True


class MetadataHandler(socketserver.BaseRequestHandler):
    def handle(self):
        self.request.sendall(self.server.xml)


def fixtures(directory):
    catalog = json.loads((ROOT / 'application-metrics.json').read_text())
    catalog += [
        {'name': 'cpu_user', 'title': 'CPU user', 'group': 'cpu', 'units': '%'},
        {'name': 'cpu_system', 'title': 'CPU system', 'group': 'cpu', 'units': '%'},
        {'name': 'mem_free', 'title': 'Free memory', 'group': 'memory', 'units': 'KB'},
        {'name': 'escaped', 'title': '<script>never execute</script>', 'group': 'test', 'units': ''},
        {'name': 'outside', 'title': 'Escaping symlink', 'group': 'test', 'units': ''},
        {'name': 'os_name', 'title': 'Operating system', 'group': 'system', 'units': ''},
    ]
    now = int(time.time())
    template = directory / 'template.rrd'
    subprocess.run([RRDTOOL, 'create', str(template), '--start', str(now - 3600),
                    '--step', '15', 'DS:sum:GAUGE:60:0:U', 'RRA:AVERAGE:0.5:1:300'], check=True)
    subprocess.run([RRDTOOL, 'update', str(template),
                    *[f'{stamp}:{(stamp // 15) % 50 + 1}' for stamp in range(now - 3585, now, 15)]], check=True)
    document = ET.Element('GANGLIA_XML')
    grid = ET.SubElement(document, 'GRID', NAME='Test Grid')
    for cluster_name, hosts in [('Production', ['web-01', 'web-02']), ('Staging', ['stage-01'])]:
        cluster = ET.SubElement(grid, 'CLUSTER', NAME=cluster_name)
        for hostname in hosts:
            host = ET.SubElement(cluster, 'HOST', NAME=hostname)
            folder = directory / 'rrds' / cluster_name / hostname
            folder.mkdir(parents=True)
            for item in catalog:
                name = item['name']
                metric = ET.SubElement(host, 'METRIC', NAME=name, VAL='42', TYPE='string' if name == 'os_name' else 'double', UNITS=item['units'])
                extra = ET.SubElement(metric, 'EXTRA_DATA')
                for key, value in [('GROUP', item['group']), ('TITLE', item['title']), ('DESC', item['title'])]:
                    ET.SubElement(extra, 'EXTRA_ELEMENT', NAME=key, VAL=value)
                if name == 'outside':
                    (folder / (name + '.rrd')).symlink_to(template)
                elif name != 'os_name':
                    shutil.copyfile(template, folder / (name + '.rrd'))
    return ET.tostring(document)


def check(base, directory):
    jar = http.cookiejar.CookieJar()
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    count = 0

    def request(path='', data=None, origin=None, method=None):
        nonlocal count
        headers = {'Origin': origin} if origin else {}
        body = urllib.parse.urlencode(data).encode() if data is not None else None
        req = urllib.request.Request(base + path, headers=headers, data=body, method=method)
        try:
            with client.open(req, timeout=15) as response:
                result = response.status, response.headers, response.read()
        except urllib.error.HTTPError as error:
            result = error.code, error.headers, error.read()
        count += 1
        return result

    for path in ('', 'application.php', 'alerts.php', 'metric_table.php', 'cli.php'):
        status, _, body = request(path)
        assert status == 200, (path, status, body[:500])
        assert all(label.encode() in body for label in ('>Graphs<', '>Application<', '>Alerts<', '>Metric Details<', '>CLI<'))
        assert body.count(b'aria-current="page"') == 1
    for path in ('lib/bootstrap.php', 'conf/defaults.php', 'application-metrics.json', '.git/config', 'graphs.php.bak', 'login.php'):
        assert request(path)[0] == 404
    status, _, body = request('api/webservice.php?list=clusters')
    assert status == 200 and json.loads(body) == ['Production', 'Staging']
    assert json.loads(request('api/webservice.php?list=servers&clusters=Staging')[2]) == {'Staging': ['stage-01']}
    assert 'cpu_user' in json.loads(request('api/webservice.php?list=metrics&metrics_grp=cpu')[2])['cpu']
    xml = ET.fromstring(request('api/webservice.php?list=clusters&method=xml')[2])
    assert xml.tag == 'response'
    assert request('api/webservice.php', method='POST')[0] == 405
    assert request('api/webservice.php?clusters[]=x&list=servers')[0] == 400
    assert request('api/webservice.php?list=servers&clusters=missing')[0] == 400
    assert request('api/webservice.php?method=invalid')[0] == 400
    assert b'&lt;script&gt;never execute&lt;/script&gt;' in request('metric_table.php')[2]

    graph = {'cluster': 'Production', 'servers': 'web-01', 'metrics': 'cpu_user'}
    variants = [
        *[{'graph_interval': period} for period in ('hour', 'day', 'week', 'month', 'year')],
        *[{'graph_style': style, 'metrics': 'cpu_user,cpu_system'} for style in ('LINE1', 'AREA', 'STACK')],
        *[{'graph_type': kind, 'percentile_val': '95'} for kind in ('minimum', 'maximum', 'percentile')],
        *[{'graph_size': size} for size in ('small', 'medium', 'large')],
        {'servers': 'web-01,web-02'}, {'cluster': 'All', 'servers': 'stage-01'},
    ]
    for options in variants:
        status, headers, png = request('graphs.php?' + urllib.parse.urlencode(graph | options))
        assert status == 200 and headers['Content-Type'] == 'image/png' and png.startswith(b'\x89PNG\r\n\x1a\n'), (options, status, png[:500])
    for options in ({'metrics': '../../etc/passwd'}, {'graph_style': 'LINE1;id'}, {'servers': 'stage-01'},
                    {'graph_size': 'huge'}, {'percentile_val': '101'}, {'metrics[]': 'cpu_user'},
                    {'metrics': 'outside'}, {'metrics': 'os_name'}, {'graph_type': '$(id)'}):
        assert request('graphs.php?' + urllib.parse.urlencode(graph | options))[0] == 400, options
    assert b'<img' in request('inner_panel.php?' + urllib.parse.urlencode(graph))[2]
    assert len(re.findall(rb'<img ', request('application.php')[2])) == 23

    status, headers, page = request('cli.php')
    token = re.search(rb'name="csrf" value="([a-f0-9]+)"', page).group(1).decode()
    assert request('cli.php', {'query': 'list servers'})[0] == 403
    assert request('cli.php', {'query': 'list servers', 'csrf': token}, 'https://untrusted.example')[0] == 403

    def command(text, expected=200):
        status, _, body = request('cli.php', {'query': text, 'csrf': token}, base.rstrip('/'))
        assert status == expected, (text, status, body[:500])
        return json.loads(body)

    assert command('list servers')['items'] == ['stage-01', 'web-01', 'web-02']
    assert command('find servers clusters="Production"')['items'] == ['web-01', 'web-02']
    assert command('find metrics groups="cpu" name="user"')['items'] == ['cpu_user']
    assert len(command('list metrics ^app_')['items']) == 66
    for text, expected in [
        ('graph (list servers ^web-01$) (list metrics ^cpu_(user|system)$) hour LINE1 small yes', 2),
        ('graph (find servers clusters="Production") (list metrics ^cpu_(user|system)$) day STACK medium no', 2),
    ]:
        result = command(text)
        assert result['graphs'] == expected
        for url in re.findall(r'<img src="([^"]+)"', result['html']):
            assert request(html.unescape(url))[2].startswith(b'\x89PNG')
    result = command('views save name="<img src=x> Test" (graph (list servers ^web-01$) (list metrics ^cpu_user$))')
    identifier = result['saved_id']
    assert '&lt;img src=x&gt;' in command('views list')['html']
    # A fresh session still sees the same on-disk view.
    jar.clear()
    token = re.search(rb'name="csrf" value="([a-f0-9]+)"', request('cli.php')[2]).group(1).decode()
    assert command(f'views load {identifier}')['graphs'] == 1
    saved = json.loads((directory / 'state/views.json').read_text())
    views = saved['views']
    item = views[str(identifier)] if isinstance(views, dict) else views[identifier]
    assert item['name'] == '<img src=x> Test'
    assert (directory / 'state/views.json').stat().st_mode & 0o777 == 0o600
    command(f'views del {identifier}')
    assert 'No saved views yet' in command('views list')['html']
    next_id = command('views save name="Next" (list servers)')['saved_id']
    assert next_id > identifier
    command(f'views del {next_id}')
    for text in ('whoami', 'list metrics [', 'find metrics invalid="x"', 'views load 999999',
                 'views save name="bad" (views list)', 'graph (list servers) (list metrics) day LINE1 small no'):
        command(text, 400)
    return count


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--browser', action='store_true')
    parser.add_argument('--serve', action='store_true', help='Keep the fixture alive for external browser checks')
    parser.add_argument('--port', type=int, default=0)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='ganglia-web-test-') as name:
        directory = Path(name)
        metadata = MetadataServer(('127.0.0.1', 0), MetadataHandler)
        metadata.xml = fixtures(directory)
        threading.Thread(target=metadata.serve_forever, daemon=True).start()
        port = args.port or free_port()
        base = f'http://127.0.0.1:{port}/'
        (directory / 'state').mkdir(mode=0o700)
        (directory / 'sessions').mkdir(mode=0o700)
        settings = {
            'site_name': 'Fixture monitoring', 'timezone': 'UTC', 'public_origin': base.rstrip('/'),
            'gmetad_port': metadata.server_address[1], 'rrd_root': str(directory / 'rrds'),
            'rrdtool': shutil.which(RRDTOOL), 'state_dir': str(directory / 'state'),
            'default_cluster': 'Production', 'application_cluster': 'Production', 'application_server': 'web-01',
        }
        config = directory / 'config.php'
        # JSON is decoded as data, not interpolated as PHP code.
        encoded = json.dumps(settings).replace('\\', '\\\\').replace("'", "\\'")
        config.write_text("<?php return json_decode('" + encoded + "', true, 512, JSON_THROW_ON_ERROR);\n")
        logfile = directory / 'php.log'
        with logfile.open('w') as log:
            process = subprocess.Popen([PHP, '-d', 'display_errors=0', '-d', 'log_errors=1',
                                        '-d', 'error_reporting=32767', '-d', f'session.save_path={directory / "sessions"}',
                                        '-d', 'session.cookie_httponly=1', '-d', 'session.cookie_samesite=Strict',
                                        '-S', f'127.0.0.1:{port}', '-t', str(ROOT), str(ROOT / 'tests/router.php')],
                                       env=os.environ | {'GANGLIA_WEB_CONFIG': str(config)}, stdout=log, stderr=log)
            try:
                for _ in range(100):
                    if process.poll() is not None: raise RuntimeError(logfile.read_text())
                    try:
                        with urllib.request.urlopen(base, timeout=1): break
                    except OSError: time.sleep(0.05)
                else: raise RuntimeError('Fixture PHP server did not start.')
                requests = check(base, directory)
                print(json.dumps({'passed': True, 'http_checks': requests, 'base_url': base}), flush=True)
                if args.browser:
                    subprocess.run([os.environ.get('NODE_BINARY', 'node'), str(ROOT / 'tests/browser.mjs')],
                                   env=os.environ | {'GANGLIA_BASE_URL': base}, cwd=ROOT, check=True)
                if args.serve:
                    print('Fixture ready; interrupt to stop.', flush=True)
                    try:
                        while True: time.sleep(1)
                    except KeyboardInterrupt: pass
                errors = [line for line in logfile.read_text().splitlines() if re.search(r'PHP (?:Warning|Fatal error|Deprecated|Notice)', line)]
                assert not errors, errors
            finally:
                process.terminate()
                process.wait(timeout=10)
                metadata.shutdown()
                metadata.server_close()


if __name__ == '__main__':
    main()
