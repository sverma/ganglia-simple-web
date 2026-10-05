<?php
require_once __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
echo strtr(file_get_contents(__DIR__ . '/templates/default/index.tpl'), [
    '{user}' => h($_SERVER['REMOTE_USER'] ?? 'Monitoring'),
    '{site_name}' => h(config('site_name')),
    '{timezone}' => h(config('timezone')),
    '{default_cluster}' => h(config('default_cluster')),
    '{classic_link}' => classic_link(),
    '{panels}' => panel_navigation('graphs'),
]);
