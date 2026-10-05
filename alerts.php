<?php
require_once __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alerts | Ganglia Simple Web</title><link rel="stylesheet" href="css/deployment.css"></head><body><main>
<header><div><p class="eyebrow"><?= h(config('site_name')) ?> · SERVER MONITORING</p><h1>Ganglia Simple Web</h1></div><?= classic_link() ?></header>
<?= panel_navigation('alerts') ?>
<section class="panel-card"><p class="eyebrow">LEGACY ALERTS PANEL</p><h2>Alerting is not configured</h2><p>The original Ganglia Simple Web repository includes this tab as a placeholder. It does not implement alert rules, alert history, or notifications.</p><p>The Graphs and Metric Details panels show the server's collected metrics. This panel does not indicate whether the server is healthy.</p><p><a href="./">View server graphs</a></p></section>
</main></body></html>
