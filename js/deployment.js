'use strict';
const form = document.querySelector('#graphit');
const area = document.querySelector('#grapharea');
const statusText = document.querySelector('#status');
const fields = Object.fromEntries(['cluster','servers','metrics_group','metrics'].map(id=>[id,document.getElementById(id)]));
let busy = false;
let sequence = 0;
let ready = false;
async function api(list, filters = {}) {
  const response = await fetch('api/webservice.php?' + new URLSearchParams({list,...filters}), {credentials:'same-origin'});
  if (!response.ok) throw new Error('Could not load monitoring data. Please retry.');
  return response.json();
}
function options(select, values, allLabel, preferred) {
  select.replaceChildren();
  if (allLabel) select.add(new Option(allLabel, 'All'));
  for (const value of [...new Set(values)].sort()) select.add(new Option(value, value));
  if (preferred && [...select.options].some(option=>option.value===preferred)) select.value=preferred;
  else if (select.options.length) select.options[0].selected=true;
}
async function updateMetrics() {
  const id=++sequence;
  fields.metrics.disabled=true;
  document.querySelector('#submit').disabled=true;
  try {
  const data = await api('metrics', fields.metrics_group.value==='All' ? (fields.cluster.value==='All' ? {} : {clusters:fields.cluster.value}) : {metrics_grp:fields.metrics_group.value});
  if(id===sequence) options(fields.metrics,Object.values(data).flat(),'All metrics');
  } finally { if(id===sequence) { fields.metrics.disabled=false; document.querySelector('#submit').disabled=busy; } }
}
async function updateCluster() {
  const id=++sequence;
  fields.servers.disabled=fields.metrics.disabled=fields.metrics_group.disabled=true;
  document.querySelector('#submit').disabled=true;
  try {
  const filter = fields.cluster.value === 'All' ? {} : {clusters:fields.cluster.value};
  const [servers,groups,metrics] = await Promise.all([api('servers',filter),api('metrics_grp',filter),api('metrics',filter)]);
  if(id!==sequence) return;
  options(fields.servers,Object.values(servers).flat(),null);
  options(fields.metrics_group,Object.values(groups).flat(),'All groups');
  options(fields.metrics,Object.values(metrics).flat(),'All metrics');
  } finally { if(id===sequence) { fields.servers.disabled=fields.metrics.disabled=fields.metrics_group.disabled=false; document.querySelector('#submit').disabled=busy; } }
}
function query() {
  const params=new URLSearchParams(new FormData(form));
  params.set('servers',[...fields.servers.selectedOptions].map(option=>option.value).join(','));
  return params;
}
function showError(error) {
  statusText.textContent=error.message;
  statusText.classList.add('error');
}
async function draw() {
  if(busy || !ready || fields.metrics.disabled || fields.servers.disabled || !form.reportValidity()) return;
  busy=true;
  const button=document.querySelector('#submit');
  button.disabled=true;
  area.setAttribute('aria-busy','true');
  statusText.classList.remove('error');
  statusText.textContent='Loading graphs…';
  try {
    const params=query();
    const response=await fetch('create_graph_panel.php?'+params,{credentials:'same-origin'});
    if(!response.ok) throw new Error((await response.json()).error || 'Unable to load graphs.');
    area.innerHTML=await response.text();
    document.querySelector('#detach').href='inner_panel.php?'+params;
    const images=[...area.querySelectorAll('img')];
    await Promise.all(images.map(img=>img.decode()));
    statusText.textContent=`${images.length} ${images.length===1?'graph':'graphs'} · Updated ${new Intl.DateTimeFormat(undefined,{timeZone:document.body.dataset.timezone,dateStyle:'medium',timeStyle:'medium'}).format(new Date())} ${document.body.dataset.timezone}`;
  } catch(error) {showError(error.message?.includes('decode') ? new Error('A graph could not load. Please retry Update graphs.') : error);}
  finally {busy=false;button.disabled=false;area.setAttribute('aria-busy','false');}
}
form.addEventListener('submit',event=>{event.preventDefault();draw();});
fields.cluster.addEventListener('change',async()=>{try{await updateCluster();}catch(error){showError(error);}});
fields.metrics_group.addEventListener('change',async()=>{try{await updateMetrics();}catch(error){showError(error);}});
document.querySelector('#graph_type').addEventListener('change',event=>{document.querySelector('#percentile-control').hidden=event.target.value!=='percentile';});
async function init() {
  try {
    options(fields.cluster,await api('clusters'),'All clusters',document.body.dataset.defaultCluster);
    await updateCluster();
    ready=true;
    document.querySelector('#submit').disabled=false;
    await draw();
  } catch(error) {showError(error);}
}
setInterval(()=>{if(ready && !document.hidden && document.querySelector('#auto_refresh').checked)draw();},60000);
init();
