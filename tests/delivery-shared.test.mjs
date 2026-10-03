import test from 'node:test';
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {readFile} from 'node:fs/promises';
import {existsSync} from 'node:fs';
import {resolve} from 'node:path';
import {root} from '../scripts/framework.mjs';

const python=process.env.TCL_FRAMEWORK_PYTHON || (existsSync(resolve(root,'.local/framework-venv/Scripts/python.exe')) ? resolve(root,'.local/framework-venv/Scripts/python.exe') : process.platform==='win32'?'python':'python3');
const env={...process.env,PYTHONUTF8:'1',PYTHONDONTWRITEBYTECODE:'1'};
test('Les garde-fous partagés passent les archives hostiles et les pannes SSH sans réseau réel',()=>{
  const result=spawnSync(python,['-m','unittest','discover','-s','tests','-p','test_delivery_shared.py'],{cwd:root,env,encoding:'utf8',windowsHide:true});
  assert.equal(result.status,0,result.stdout+result.stderr);
});
test('La préparation reste manuelle, borne les crédits runner et sépare construction et accès',async()=>{
  const contents=await Promise.all(['.github/workflows/preparer-deploiement.yml','.github/actions/qualified-ssh/action.yml'].map(path=>readFile(resolve(root,path),'utf8')));
  const parsed=spawnSync(python,['-c','import json,sys,yaml; print(json.dumps([yaml.safe_load(s) for s in json.load(sys.stdin)]))'],{input:JSON.stringify(contents),env,encoding:'utf8',windowsHide:true});
  assert.equal(parsed.status,0,parsed.stderr);
  const [flow,action]=JSON.parse(parsed.stdout);
  assert.deepEqual(Object.keys(flow.on),['workflow_dispatch']);
  assert.equal(flow.on.workflow_dispatch.inputs.operation.default,'build');
  assert.deepEqual(flow.permissions,{contents:'read'});
  assert.equal(flow.concurrency['cancel-in-progress'],false);
  assert.equal(flow.jobs.build.environment,undefined);
  assert.equal(flow.jobs['qualify-ssh'].environment,'tcl-preproduction');
  assert.ok(flow.jobs.build['timeout-minutes']<=20);
  assert.ok(flow.jobs['qualify-ssh']['timeout-minutes']<=8);
  for(const job of Object.values(flow.jobs)) {
    assert.match(job.if,/refs\/heads\/main/);
    for(const step of job.steps) {
      if(step.uses&&!step.uses.startsWith('./'))assert.match(step.uses,/^actions\/[\w-]+@[a-f0-9]{40}$/);
      if(step.uses?.startsWith('actions/checkout@'))assert.equal(step.with['persist-credentials'],false);
      assert.doesNotMatch(step.run||'',/\$\{\{|rsync|scp|maintenance_mode|secrets:/);
    }
  }
  const uploaded=flow.jobs.build.steps.find(step=>step.uses?.startsWith('actions/upload-artifact@'));
  assert.equal(uploaded.with.path,'.local/delivery/tc-longages-drupal.zip\n.local/delivery-receipt.json\n');
  assert.equal(uploaded.with['retention-days'],7);
  assert.equal(action.runs.steps.at(-1).if,'${{ always() }}');
  assert.match(action.runs.steps.at(-1).run,/cleanup$/);
});
