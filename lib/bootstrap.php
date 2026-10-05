<?php
declare(strict_types=1);
// Shared PHP 8 runtime; metadata is read through the original Ganglia parser.
require_once __DIR__ . '/config.php';
date_default_timezone_set(config('timezone'));
putenv('TZ=' . config('timezone'));
require_once __DIR__ . '/parse_xml.php';
define('RRD_ROOT', rtrim(realpath(config('rrd_root')) ?: config('rrd_root'), '/'));

function fail_request(string $message, int $status = 400): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_THROW_ON_ERROR);
    exit;
}
$allowed_methods = defined('GANGLIA2_CLI') ? ['GET', 'HEAD', 'POST'] : ['GET', 'HEAD'];
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $allowed_methods, true)) {
    header('Allow: ' . implode(', ', $allowed_methods));
    fail_request('Only read-only requests are supported.', 405);
}
foreach ($_GET as $key => $value) {
    if (!is_string($value) || strlen($value) > 2048 || strlen($key) > 64) {
        fail_request('Invalid query parameter.');
    }
}
set_exception_handler(function (Throwable $error): void {
    error_log('Ganglia2: ' . $error->getMessage());
    fail_request('Monitoring data is temporarily unavailable. Please retry.', 503);
});
function h(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function classic_link(bool $application = false): string {
    $url = config($application ? 'classic_application_url' : 'classic_url');
    return $url === '' ? '' : '<a href="' . h($url) . '">Classic Ganglia ↗</a>';
}
function panel_navigation(string $current): string {
    $html = '<nav class="panel-tabs" aria-label="Monitoring panels">';
    foreach (['graphs'=>['./','Graphs'], 'application'=>['application.php','Application'], 'alerts'=>['alerts.php','Alerts'], 'metrics'=>['metric_table.php','Metric Details'], 'cli'=>['cli.php','CLI']] as $key => [$url,$label]) {
        $html .= '<a href="' . $url . '"' . ($key === $current ? ' aria-current="page"' : '') . '>' . $label . '</a>';
    }
    return $html . '</nav>';
}
function metadata(): parse_xml {
    static $data;
    if (!$data) {
        $data = new parse_xml(config('gmetad_host'), config('gmetad_port'));
        $data->parse();
    }
    return $data;
}
function choice(string $key, array $choices, string $default): string {
    $value = $_GET[$key] ?? $default;
    if (!in_array($value, $choices, true)) fail_request('Invalid ' . $key . '.');
    return $value;
}
function selected_values(string $key, array $allowed, string $default = 'All'): array {
    $raw = $_GET[$key] ?? $default;
    if (strcasecmp($raw, 'All') === 0) return $allowed;
    $values = array_values(array_unique(explode(',', $raw)));
    if (!$values || count($values) > 32 || array_diff($values, $allowed)) {
        fail_request('Unknown ' . $key . '.');
    }
    return $values;
}
function graph_options(): array {
    $percentile = $_GET['percentile_val'] ?? '90';
    if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D', $percentile) || (float)$percentile > 100) {
        fail_request('Percentile must be between 0 and 100.');
    }
    return [
        'graph_interval' => choice('graph_interval', ['hour','day','week','month','year'], 'hour'),
        'graph_size' => choice('graph_size', ['small','medium','large'], 'small'),
        'graph_style' => choice('graph_style', ['LINE1','AREA','STACK'], 'LINE1'),
        'graph_type' => choice('graph_type', ['average','minimum','maximum','percentile'], 'average'),
        'percentile_val' => $percentile,
    ];
}
function series_path(string $cluster, string $server, string $metric): ?string {
    foreach ([$cluster, $server, $metric] as $part) {
        if ($part === '' || str_contains($part, '/') || str_contains($part, '\\') || str_contains($part, "\0") || $part === '..') return null;
    }
    $path = realpath(RRD_ROOT . '/' . $cluster . '/' . $server . '/' . $metric . '.rrd');
    return $path && str_starts_with($path, RRD_ROOT . '/') && is_readable($path) ? $path : null;
}
function selection(): array {
    $data = metadata();
    $clusters = selected_values('cluster', $data->get_all_clusters());
    $hosts = [];
    foreach ($data->get_servers_from_clusters($clusters) as $list) $hosts = array_merge($hosts, $list);
    $servers = selected_values('servers', array_values(array_unique($hosts)));
    $groups = selected_values('metrics_group', $data->get_all_metrics_group());
    $metrics = [];
    foreach ($data->get_metrics_from_groups($groups) as $list) $metrics = array_merge($metrics, $list);
    $metrics = array_values(array_unique($metrics));
    $metrics = selected_values('metrics', $metrics);
    // Ganglia includes string metadata without RRDs. Only numeric history is graphable.
    $metrics = array_values(array_filter($metrics, function ($metric) use ($servers, $data) {
        foreach ($servers as $server) {
            if (series_path($data->get_cluster_from_servername($server), $server, $metric)) return true;
        }
        return false;
    }));
    return [$clusters, $servers, $metrics, $groups];
}
function render_panels(): void {
    $options = graph_options();
    [$clusters, $servers, $metrics, $groups] = selection();
    if (!$metrics) { echo '<p>No numeric history is available for this selection.</p>'; return; }
    $data = metadata();
    $details = $data->get_metric_details($metrics);
    $grouped = $data->get_metrics_from_groups($groups);
    $seen = [];
    foreach ($grouped as $group => $members) {
        $shown = array_values(array_diff(array_intersect($members, $metrics), $seen));
        if (!$shown) continue;
        $seen = array_merge($seen, $shown);
        echo '<details class="metric-group" open><summary>' . h(ucwords($group)) . '</summary><div class="graph-grid">';
        foreach ($shown as $metric) {
            $params = $options + ['cluster' => implode(',', $clusters), 'servers' => implode(',', $servers), 'metrics' => $metric];
            $url = 'graphs.php?' . http_build_query($params);
            $title = $details[$metric]['TITLE'] ?? $metric;
            echo '<figure><figcaption>' . h($title) . '</figcaption><a href="' . h($url) . '" target="_blank" rel="noopener"><img src="' . h($url) . '" alt="' . h($title) . ' history" width="' . (['small'=>497,'medium'=>617,'large'=>857][$options['graph_size']]) . '" loading="eager"></a></figure>';
        }
        echo '</div></details>';
    }
}
