<?php
require_once __DIR__ . '/lib/bootstrap.php';
$options = graph_options();
[$clusters, $servers, $metrics] = selection();
if (!$metrics || count($servers) * count($metrics) > 16) fail_request('Choose between 1 and 16 graph series.');
$sizes = ['small'=>[400,170], 'medium'=>[520,230], 'large'=>[760,310]];
[$width, $height] = $sizes[$options['graph_size']];
$ranges = ['hour'=>3600,'day'=>86400,'week'=>604800,'month'=>2592000,'year'=>31536000];
$data = metadata();
$details = $data->get_metric_details($metrics);
$units = array_unique(array_map(fn($metric) => (string)($details[$metric]['units'] ?? ''), $metrics));
$unit = count($units) === 1 ? reset($units) : 'mixed units';
$args = [config('rrdtool'),'graph','-','--imgformat','PNG','--start','end-' . $ranges[$options['graph_interval']], '--end','now',
    '--width',(string)$width,'--height',(string)$height,'--lower-limit','0','--slope-mode',
    '--title',implode(', ', $metrics),'--vertical-label', $unit, '--font','DEFAULT:9',
    '--watermark',config('site_name') . ' | ' . config('timezone')];
$palette = ['#2563eb','#059669','#d97706','#dc2626','#7c3aed','#0891b2','#be185d','#475569'];
$draw = $statistics = [];
$index = 0;
foreach ($metrics as $metric) foreach ($servers as $server) {
    $cluster = $data->get_cluster_from_servername($server);
    $path = series_path($cluster, $server, $metric);
    if (!$path) continue;
    $id = 's' . $index;
    $color = $palette[$index % count($palette)];
    $label = str_replace(['\\',':'], ['\\\\','\\:'], $server . ' ' . $metric);
    $rrd = str_replace(['\\',':'], ['\\\\','\\:'], $path);
    $args[] = "DEF:$id=$rrd:sum:AVERAGE";
    $style = $options['graph_style'] === 'STACK' ? 'AREA' : $options['graph_style'];
    $draw[] = "$style:$id$color:$label\\l" . ($options['graph_style'] === 'STACK' && $index ? ':STACK' : '');
    foreach (['last'=>'LAST','min'=>'MINIMUM','avg'=>'AVERAGE','max'=>'MAXIMUM'] as $name => $function) {
        $args[] = "VDEF:{$id}_$name=$id,$function";
        $statistics[] = "GPRINT:{$id}_$name:" . ucfirst($name) . '\\:%7.2lf%s' . ($name === 'max' ? '\\l' : '');
    }
    if ($options['graph_type'] !== 'average') {
        $type = $options['graph_type'];
        $function = ['minimum'=>'MINIMUM','maximum'=>'MAXIMUM','percentile'=>$options['percentile_val'] . ',PERCENT'][$type];
        $args[] = "VDEF:{$id}_summary=$id,$function";
        $draw[] = "LINE1:{$id}_summary$color:" . ucfirst($type) . ($type === 'percentile' ? ' ' . $options['percentile_val'] : '') . '\l:dashes';
    }
    $index++;
}
if (!$index) fail_request('No numeric history for this selection.', 404);
$args = array_merge($args, $draw, $statistics);
// Array argv bypasses the shell entirely; every series is selected from local metadata.
$process = proc_open($args, [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes);
if (!is_resource($process)) throw new RuntimeException('Could not start rrdtool.');
fclose($pipes[0]);
$png = stream_get_contents($pipes[1]); fclose($pipes[1]);
$error = stream_get_contents($pipes[2]); fclose($pipes[2]);
$status = proc_close($process);
if ($status !== 0 || !str_starts_with($png, "\x89PNG\r\n\x1a\n")) throw new RuntimeException('rrdtool: ' . $error);
header('Content-Type: image/png');
header('Cache-Control: private, no-store');
echo $png;
