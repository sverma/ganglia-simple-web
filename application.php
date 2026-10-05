<?php
require_once __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
$options=graph_options();
$catalog=json_decode(file_get_contents(config('application_catalog')),true,512,JSON_THROW_ON_ERROR);
$labels=array_column($catalog,'title','name');
$available=metadata()->get_all_metrics();
$sections=config('application_sections');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="60"><title>Application metrics | <?= h(config('site_name')) ?></title><link rel="stylesheet" href="css/deployment.css"></head><body><main>
<header><div><p class="eyebrow"><?= h(config('site_name')) ?> · APPLICATION MONITORING</p><h1>Application metrics</h1><p><?= h(config('application_description')) ?> · Graph times in <?= h(config('timezone')) ?></p></div><?= classic_link(true) ?></header>
<?= panel_navigation('application') ?>
<form action="application.php" method="get"><div class="controls"><label for="app-range">Time range<select id="app-range" name="graph_interval"><?php foreach(['hour'=>'Last hour','day'=>'Last day','week'=>'Last week','month'=>'Last 30 days','year'=>'Last year'] as $key=>$label) echo '<option value="' . $key . '"' . ($options['graph_interval']===$key?' selected':'') . '>' . $label . '</option>'; ?></select></label><label for="app-size">Graph size<select id="app-size" name="graph_size"><?php foreach(['small','medium','large'] as $size) echo '<option value="' . $size . '"' . ($options['graph_size']===$size?' selected':'') . '>' . ucfirst($size) . '</option>'; ?></select></label></div><div class="form-footer"><button type="submit">Update graphs</button><span>Page refreshes every 60 seconds.</span></div></form>
<p class="status-row"><?= h(config('application_notes')) ?></p>
<?php foreach($sections as $section=>$names): ?>
<details class="metric-group" open><summary><?= h($section) ?></summary><div class="graph-grid">
<?php foreach($names as $name): ?>
<figure><figcaption><?= h($labels[$name] ?? $name) ?></figcaption>
<?php if(in_array($name,$available,true)):
    $url='graphs.php?' . http_build_query($options+['cluster'=>config('application_cluster'),'servers'=>config('application_server'),'metrics'=>$name]); ?>
<a href="<?= h($url) ?>" target="_blank" rel="noopener"><img src="<?= h($url) ?>" alt="<?= h($labels[$name] ?? $name) ?> history"></a>
<?php else: ?><p>Waiting for a fresh metric sample.</p><?php endif; ?>
</figure><?php endforeach; ?></div></details><?php endforeach; ?>
<footer>Long-range RRD graphs consolidate stored samples, including previously computed window percentiles. Explore all metrics in Graphs or CLI. This dashboard reads metrics published to Ganglia; it does not collect them.</footer>
</main></body></html>
