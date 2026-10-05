#!/usr/bin/env python3
"""Build a clean release from this checkout; no upstream downloads or application repo needed."""
import argparse
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[1]
FILES = [
    'index.php', 'graphs.php', 'create_graph_panel.php', 'inner_panel.php',
    'metric_table.php', 'alerts.php', 'cli.php', 'application.php',
    'application-metrics.json', 'favicon.ico', 'api/webservice.php',
    'lib/bootstrap.php', 'lib/config.php', 'lib/parse_xml.php', 'lib/cli_commands.php',
    'conf/defaults.php', 'conf/application-sections.php',
    'templates/default/index.tpl', 'js/deployment.js', 'js/cli.js', 'css/deployment.css',
]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('destination', type=Path, help='A new, nonexistent directory')
    destination = parser.parse_args().destination.resolve()
    if destination.exists():
        raise SystemExit('Build directory must not already exist.')
    destination.mkdir(parents=True)
    for name in FILES:
        target = destination / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(ROOT / name, target)
        target.chmod(0o644)
    for path in [destination, *(p for p in destination.rglob('*') if p.is_dir())]:
        path.chmod(0o755)
    print(f'Built {len(FILES)} files in {destination}')


if __name__ == '__main__':
    main()
