<?php
// Development fixture only: allowlist mirrors the production Nginx entry points.
// Authentication is deliberately absent; the fixture binds only to loopback.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($path === '/cli.html') { header('Location: /cli.php', true, 302); return; }
if ($path === '/') { require dirname(__DIR__) . '/index.php'; return; }
if (preg_match('~^/(?:index|graphs|create_graph_panel|inner_panel|metric_table|alerts|cli|application|api/webservice)\.php$~D', $path)
    || preg_match('~^/(?:js/(?:deployment|cli)\.js|css/deployment\.css|favicon\.ico)$~D', $path)) {
    return false;
}
http_response_code(404);
echo 'Not found';
