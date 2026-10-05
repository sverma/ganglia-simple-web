import {createRequire} from 'node:module';
import {mkdirSync} from 'node:fs';
import assert from 'node:assert/strict';
const require = createRequire(import.meta.url);
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.GANGLIA_BASE_URL;
assert.ok(base, 'Run through tests/integration.py --browser, or set GANGLIA_BASE_URL to an isolated fixture.');
const browser = await chromium.launch({headless:true});
const context = await browser.newContext({viewport:{width:1440,height:1000}});
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('console', message => { if (message.type()==='error') errors.push(message.text()); });
page.on('response', response => { if (response.status()>=400) errors.push(`${response.status()} ${response.url()}`); });
mkdirSync('test-results', {recursive:true});
async function rendered() {
  await page.waitForFunction(() => document.querySelector('#grapharea')?.getAttribute('aria-busy')==='false' && document.querySelector('#status').textContent.includes('Updated'), {}, {timeout:60000});
  assert.ok(await page.locator('#grapharea img').evaluateAll(images => images.length>0 && images.every(image=>image.complete && image.naturalWidth>100)));
}
async function command(query) {
  await page.fill('#command', query);
  const [response] = await Promise.all([page.waitForResponse(res=>res.url().endsWith('/cli.php') && res.request().method()==='POST'), page.click('#run-command')]);
  assert.equal(response.status(), 200, await response.text());
  await page.waitForFunction(()=>document.querySelector('#cli-result').getAttribute('aria-busy')==='false');
  return response.json();
}
try {
  await page.goto(base, {waitUntil:'networkidle'});
  await rendered();
  assert.equal(await page.locator('#cluster').inputValue(), 'Production');
  assert.deepEqual(await page.locator('.panel-tabs a').allTextContents(), ['Graphs','Application','Alerts','Metric Details','CLI']);
  await page.selectOption('#metrics_group','cpu');
  await page.waitForFunction(()=>!document.querySelector('#metrics').disabled && [...document.querySelector('#metrics').options].some(o=>o.value==='cpu_user'));
  await page.selectOption('#metrics','cpu_user');
  await page.selectOption('#graph_interval','day');
  await page.selectOption('#graph_style','AREA');
  await page.selectOption('#graph_type','percentile');
  await page.fill('#percentile_val','95');
  await Promise.all([page.waitForResponse(res=>res.url().includes('graphs.php') && res.url().includes('percentile_val=95')),page.click('#submit')]);
  await rendered();
  assert.equal(await page.locator('#grapharea img').count(),1);
  await page.screenshot({path:'test-results/graphs.png',fullPage:true});
  const detached = await page.context().newPage();
  await detached.goto(new URL(await page.locator('#detach').getAttribute('href'),base).href,{waitUntil:'networkidle'});
  assert.ok(await detached.locator('img').evaluate(img=>img.complete && img.naturalWidth>100));
  await detached.close();
  await page.goto(base+'application.php',{waitUntil:'networkidle'});
  assert.equal(await page.locator('figure img').count(),23);
  assert.ok(await page.locator('figure img').evaluateAll(images=>images.every(img=>img.complete && img.naturalWidth>100)));
  await page.screenshot({path:'test-results/application.png',fullPage:true});
  await page.goto(base+'cli.html',{waitUntil:'networkidle'});
  assert.ok(page.url().endsWith('/cli.php'));
  assert.ok((await command('list metrics ^app_')).items.length===66);
  const graph=await command('graph (list servers ^web-01$) (list metrics ^cpu_(user|system)$) hour LINE1 small yes');
  assert.equal(graph.graphs,2);
  assert.ok(await page.locator('#cli-result img').evaluateAll(images=>images.every(img=>img.complete && img.naturalWidth>100)));
  const saved = await command('views save name="Browser fixture" (graph (list servers ^web-01$) (list metrics ^cpu_user$))');
  await page.reload({waitUntil:'networkidle'});
  assert.ok((await command('views list')).html.includes('Browser fixture'));
  await command('views load '+saved.saved_id);
  await command('views del '+saved.saved_id);
  await page.screenshot({path:'test-results/cli.png',fullPage:true});
  await page.setViewportSize({width:390,height:844});
  for (const path of ['', 'application.php', 'cli.php', 'alerts.php', 'metric_table.php']) {
    await page.goto(base+path,{waitUntil:'networkidle'});
    if (!path) await rendered();
    assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth), 'No mobile overflow: '+path);
    if (path==='alerts.php') assert.ok((await page.locator('body').innerText()).includes('Alerting is not configured'));
  }
  await page.screenshot({path:'test-results/mobile.png',fullPage:true});
  assert.deepEqual(errors,[]);
  console.log(JSON.stringify({passed:true,checks:['graphs and overlays','detached panel','23 application graphs','CLI and persistent views','five mobile panels'],errors}));
} finally { await browser.close(); }
