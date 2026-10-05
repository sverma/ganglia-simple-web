<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{site_name} | Ganglia Simple Web</title>
  <link rel="icon" href="favicon.ico">
  <link rel="stylesheet" href="css/deployment.css">
  <script src="js/deployment.js" defer></script>
</head>
<body data-timezone="{timezone}" data-default-cluster="{default_cluster}"><main>
  <header><div><p class="eyebrow">{site_name} · SERVER MONITORING</p><h1>Ganglia Simple Web</h1><p>Server history, grouped by metric. All graph times are in {timezone}.</p></div><div class="account"><strong>{user}</strong><nav><a href="metric_table.php">Metric details</a>{classic_link}</nav></div></header>
  {panels}
  <form id="graphit" action="create_graph_panel.php" method="get">
    <div class="controls">
      <label for="cluster">Cluster<select id="cluster" name="cluster" required><option>Loading…</option></select></label>
      <label for="servers">Servers<select id="servers" name="servers" multiple size="2" required aria-describedby="server-help"></select><small id="server-help">Select one or more servers.</small></label>
      <label for="metrics_group">Metrics group<select id="metrics_group" name="metrics_group"><option value="All">All groups</option></select></label>
      <label for="metrics">Metric<select id="metrics" name="metrics"><option value="All">All metrics</option></select></label>
      <label for="graph_interval">Time range<select id="graph_interval" name="graph_interval"><option value="hour">Last hour</option><option value="day">Last day</option><option value="week">Last week</option><option value="month">Last 30 days</option><option value="year">Last year</option></select></label>
      <label for="graph_size">Graph size<select id="graph_size" name="graph_size"><option value="small">Small</option><option value="medium">Medium</option><option value="large">Large</option></select></label>
      <label for="graph_style">Graph style<select id="graph_style" name="graph_style"><option value="LINE1">Line</option><option value="AREA">Area</option><option value="STACK">Stacked area</option></select></label>
      <label for="graph_type">Summary overlay<select id="graph_type" name="graph_type"><option value="average">None — interval averages</option><option value="minimum">Minimum</option><option value="maximum">Maximum</option><option value="percentile">Percentile</option></select></label>
      <label id="percentile-control" for="percentile_val" hidden>Percentile<input type="number" id="percentile_val" name="percentile_val" min="0" max="100" step="0.01" value="90"></label>
    </div>
    <div class="form-footer"><button type="submit" id="submit" disabled>Update graphs</button><label class="auto"><input type="checkbox" id="auto_refresh" checked> Refresh every 60 seconds</label><a id="detach" href="inner_panel.php" target="_blank" rel="noopener">Open selected panel ↗</a></div>
  </form>
  <div class="status-row"><p id="status" role="status" aria-live="polite">Connecting to local Ganglia data…</p><span>{timezone}</span></div>
  <div id="grapharea" aria-busy="true"></div>
  <footer>Based on <a href="https://github.com/sverma/ganglia-simple-web" target="_blank" rel="noopener noreferrer">sverma/ganglia-simple-web</a> · Read-only monitoring · History is available from collection start. Summary overlays describe the stored interval averages.</footer>
</main></body></html>
