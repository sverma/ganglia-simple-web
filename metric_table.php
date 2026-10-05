<?php
require_once __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
$data = metadata();
$groups = $data->get_metrics_from_groups($data->get_all_metrics_group());
echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Metric details | Ganglia Simple Web</title><link rel="stylesheet" href="css/deployment.css"><main><header><h1>Metric details</h1><a href="./">Back to graphs</a></header>' . panel_navigation('metrics') . '<div class="table-wrap"><table><thead><tr><th>Group</th><th>Metric</th><th>Units</th><th>Description</th></tr></thead><tbody>';
foreach ($groups as $group => $metrics) foreach ($data->get_metric_details($metrics) as $metric => $detail) {
    echo '<tr><td>' . h($group) . '</td><td>' . h($metric) . '</td><td>' . h($detail['units'] ?? '') . '</td><td>' . h($detail['DESC'] ?? $detail['TITLE'] ?? '') . '</td></tr>';
}
echo '</tbody></table></div></main></html>';
