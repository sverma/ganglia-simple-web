<?php
require_once __DIR__ . '/lib/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
if (isset($_GET['metric'])) $_GET['metrics'] = $_GET['metric'];
echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ganglia metric panel</title><link rel="stylesheet" href="css/deployment.css"><main><a href="./">Back to Ganglia Simple Web</a>';
render_panels();
echo '</main></html>';
