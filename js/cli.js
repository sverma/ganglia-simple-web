'use strict';
const cliForm=document.querySelector('#cli-form');
const cliInput=document.querySelector('#command');
const cliResult=document.querySelector('#cli-result');
const cliStatus=document.querySelector('#cli-status');
const runButton=document.querySelector('#run-command');
const history=[];
let position=0;
let running=false;
async function runCommand() {
  if(running || !cliForm.reportValidity())return;
  running=true;
  runButton.disabled=true;
  cliResult.setAttribute('aria-busy','true');
  cliStatus.classList.remove('error');
  cliStatus.textContent='Running monitoring command…';
  const query=cliInput.value;
  try {
    const response=await fetch('cli.php',{method:'POST',body:new URLSearchParams(new FormData(cliForm)),credentials:'same-origin'});
    const result=await response.json();
    if(!response.ok)throw new Error(result.error || 'The command could not complete.');
    cliResult.innerHTML=result.html;
    await Promise.all([...cliResult.querySelectorAll('img')].map(image=>image.decode()));
    cliStatus.textContent='Completed: '+query;
    if(history.at(-1)!==query)history.push(query);
    position=history.length;
  }catch(error){
    cliStatus.classList.add('error');
    cliStatus.textContent=error.message || 'Unable to load the result. Please retry.';
  }finally{
    running=false;runButton.disabled=false;cliResult.setAttribute('aria-busy','false');
  }
}
cliForm.addEventListener('submit',event=>{event.preventDefault();runCommand();});
for(const button of document.querySelectorAll('[data-command]'))button.addEventListener('click',()=>{
  if(running)return;
  cliInput.value=button.dataset.command;
  runCommand();
});
cliInput.addEventListener('keydown',event=>{
  if(event.key==='ArrowUp' && history.length){event.preventDefault();position=Math.max(0,position-1);cliInput.value=history[position];}
  if(event.key==='ArrowDown' && history.length){event.preventDefault();position=Math.min(history.length,position+1);cliInput.value=history[position] || '';}
});
