<?php
define('GANGLIA2_CLI', true);
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/cli_commands.php';
ini_set('session.use_strict_mode','1');
session_name('ganglia2_cli');
// The production PHP-FPM pool enforces secure cookie flags and a private save path.
session_start();
if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf'];
session_write_close();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals($csrf,$token)) fail_request('Reload the CLI panel and retry.',403);
    if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']!==config('public_origin')) fail_request('Invalid request origin.',403);
    $query=$_POST['query'] ?? null;
    if (!is_string($query)) fail_request('Enter a command.');
    try { $result=cli_run($query); }
    catch (InvalidArgumentException $error) { fail_request($error->getMessage()); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result,JSON_THROW_ON_ERROR);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CLI | Ganglia Simple Web</title><link rel="stylesheet" href="css/deployment.css"><script src="js/cli.js" defer></script></head><body><main>
<header><div><p class="eyebrow"><?= h(config('site_name')) ?> · SERVER MONITORING</p><h1>Ganglia Simple Web</h1></div><?= classic_link() ?></header>
<?= panel_navigation('cli') ?>
<section class="panel-card"><h2>Ganglia CLI</h2><p>Explore metrics, create graphs, and save views using the original command syntax.</p>
<form id="cli-form" action="cli.php" method="post">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<label for="command">Command<input id="command" name="query" type="text" maxlength="2048" placeholder="list servers" autocomplete="off" spellcheck="false" required></label>
<div class="form-footer"><button id="run-command" type="submit">Run command</button><button class="secondary" type="button" data-command="help">Help</button><button class="secondary" type="button" data-command="views list">Saved views</button></div>
</form><div class="cli-examples"><span>Try:</span><button type="button" data-command="list servers">List servers</button><button type="button" data-command="find metrics groups=&quot;cpu&quot;">CPU metrics</button><button type="button" data-command="graph (list servers) (list metrics ^cpu_(user|system)$) hour LINE1 small yes">CPU graphs</button></div>
</section><p id="cli-status" class="status-row" role="status" aria-live="polite">Ready. Enter a command or select an example.</p>
<section id="cli-result" class="panel-card" aria-label="Command result" aria-busy="false"><?= cli_help()['html'] ?></section>
<footer>Graph times are in <?= h(config('timezone')) ?>. Saved views are shared by monitoring users and persist across deployments.</footer>
</main></body></html>
