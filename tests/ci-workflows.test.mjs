import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile, mkdtemp, mkdir, writeFile, rm} from 'node:fs/promises';
import {existsSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
import {tmpdir} from 'node:os';
import {resolve,sep} from 'node:path';
import {root, checkActiveWorkflows, validateActiveWorkflow} from '../scripts/framework.mjs';

const python = process.env.TCL_FRAMEWORK_PYTHON || (existsSync(resolve(root,'.local/framework-venv/Scripts/python.exe')) ? resolve(root,'.local/framework-venv/Scripts/python.exe') : process.platform === 'win32' ? 'python' : 'python3');
const pythonEnv = {...process.env, PYTHONUTF8:'1', PYTHONDONTWRITEBYTECODE:'1'};
const workflow = name => readFile(resolve(root,'.github/workflows',name),'utf8');

test('Les seuls contrôles actifs sont les contenus CI et politique revus, sans déploiement', async()=>{
  assert.deepEqual(await checkActiveWorkflows(), ['ci.yml','pr-policy.yml']);
  for (const name of ['ci.yml','pr-policy.yml']) {
    const content = await workflow(name);
    assert.doesNotThrow(()=>validateActiveWorkflow(name,content.replace(/\r?\n/g,'\r\n')));
    assert.throws(()=>validateActiveWorkflow(name,content+'\n# modification non revue\n'), /requalifier/);
    assert.throws(()=>validateActiveWorkflow('deployer.yml',content), /non autorisé/);
  }
  const dir = await mkdtemp(resolve(tmpdir(),'tcl-ci-guard-'));
  try {
    for (const name of ['ci.yml','pr-policy.yml']) await writeFile(resolve(dir,name),await workflow(name));
    await writeFile(resolve(dir,'deployer.yml'),'name: Publication\non: push\n');
    await assert.rejects(checkActiveWorkflows(dir), /non autorisé/);
    await rm(resolve(dir,'deployer.yml'));
    await rm(resolve(dir,'pr-policy.yml'));
    await assert.rejects(checkActiveWorkflows(dir), /requis absent/);
  } finally { assert.ok(dir.startsWith(resolve(tmpdir())+sep)); await rm(dir,{recursive:true,force:true}); }
});

test('Le YAML exécutable limite les droits et ne confond pas test, approbation et livraison', async()=>{
  const parsed = spawnSync(python,['-c','import json,sys,yaml; print(json.dumps([yaml.safe_load(s) for s in json.load(sys.stdin)]))'],{input:JSON.stringify(await Promise.all(['ci.yml','pr-policy.yml'].map(workflow))),encoding:'utf8',env:pythonEnv});
  assert.equal(parsed.status,0,parsed.stderr);
  const [ci,policy] = JSON.parse(parsed.stdout);
  assert.deepEqual(ci.on, {pull_request:null,push:{branches:['main']}});
  assert.deepEqual(policy.on.pull_request.types,['opened','edited','synchronize','reopened','ready_for_review']);
  assert.equal(ci.jobs['technical-ci']['runs-on'],'windows-2025');
  assert.equal(ci.jobs['technical-ci'].steps.find(step=>step.uses?.startsWith('actions/checkout@')).with['fetch-depth'],0);
  for(const flow of [ci,policy]) {
    assert.deepEqual(flow.permissions,{contents:'read'});
    assert.deepEqual(Object.keys(flow.jobs), flow === ci ? ['technical-ci'] : ['policy']);
    for(const job of Object.values(flow.jobs)) {
      assert.equal(job.environment,undefined);
      assert.equal(job.permissions,undefined);
      for(const step of job.steps) {
        if(step.uses) assert.match(step.uses,/^actions\/(checkout|setup-node|setup-python)@[a-f0-9]{40}$/);
        if(step.uses?.startsWith('actions/checkout@')) assert.equal(step.with['persist-credentials'],false);
        assert.doesNotMatch(step.run || '',/secrets\.|gh\s|git\s+(push|commit)|ssh\s|scp\s|sftp\s|curl\s|\$\{\{/);
      }
    }
  }
  const ciRuns=ci.jobs['technical-ci'].steps.filter(s=>s.run).map(s=>s.run);
  assert.ok(ciRuns[0].includes('npm.cmd ci --ignore-scripts'));
  assert.ok(ciRuns[0].includes('requirements-verification.txt'));
  assert.deepEqual(ciRuns.slice(1,4),['npm.cmd run check','npm.cmd run framework:build','npm.cmd run framework:check']);
  assert.match(ciRuns[4],/node scripts\/framework-candidate\.mjs verify/);
  assert.match(ciRuns[4],/Aucune vérification du candidat attestée/);
  const policyCheck=policy.jobs.policy.steps.at(-1);
  assert.equal(policyCheck.run,'python .github/scripts/check-pr-policy.py');
  assert.equal(policyCheck.env.PR_BODY,'${{ github.event.pull_request.body }}');
  assert.equal(policyCheck.env.PR_TITLE,'${{ github.event.pull_request.title }}');
});

test('La CI annonce un manifeste absent et propage une vérification du candidat échouée', {skip:process.platform!=='win32'}, async()=>{
  const parsed=spawnSync(python,['-c','import json,sys,yaml; print(json.dumps(yaml.safe_load(sys.stdin.read())))'],{input:await workflow('ci.yml'),encoding:'utf8',env:pythonEnv});
  assert.equal(parsed.status,0,parsed.stderr);
  const command=JSON.parse(parsed.stdout).jobs['technical-ci'].steps.at(-1).run;
  const dir=await mkdtemp(resolve(tmpdir(),'tcl-ci-candidate-'));
  const run=()=>spawnSync('pwsh',['-NoLogo','-NoProfile','-NonInteractive','-Command','[Console]::OutputEncoding = [Text.UTF8Encoding]::new($false);\n'+command],{cwd:dir,encoding:'utf8',windowsHide:true});
  try {
    const missing=run();
    assert.equal(missing.status,0,missing.stderr);
    assert.match(missing.stdout,/Manifeste absent/);
    assert.match(missing.stdout,/Aucune vérification du candidat attestée/);
    await mkdir(resolve(dir,'data'));
    await mkdir(resolve(dir,'scripts'));
    await writeFile(resolve(dir,'data/framework-candidate.json'),'{}');
    await writeFile(resolve(dir,'scripts/framework-candidate.mjs'),'process.exit(7);\n');
    const failed=run();
    assert.equal(failed.status,7,failed.stderr);
    assert.doesNotMatch(failed.stdout,/Manifeste absent/);
  } finally { assert.ok(dir.startsWith(resolve(tmpdir())+sep)); await rm(dir,{recursive:true,force:true}); }
});

test('La politique accepte les cases humaines décochées sans les accorder et refuse une PR incomplète',async()=>{
  const templatePath=resolve(root,'.github/PULL_REQUEST_TEMPLATE.md');
  const template=await readFile(templatePath,'utf8');
  const validBody=template.replace(/URL_[A-Z0-9_]+/g,'https://github.com/jpdandin44/TC_Longages');
  const validate=(body,title='ci: activer les contrôles de revue',allow=true)=>spawnSync(python,[resolve(root,'.github/scripts/check-pr-policy.py'),'--github-json','-',...(allow?['--allow-unchecked']:[])],{input:JSON.stringify({title,body}),encoding:'utf8',env:pythonEnv});
  const result=validate(validBody);
  assert.equal(result.status,0,result.stderr+result.stdout);
  assert.match(result.stdout,/accords humains non evalues et non accordes/);
  assert.equal(await readFile(templatePath,'utf8'),template);
  assert.equal((validBody.match(/^- \[ \]/gm)||[]).length,4);
  assert.equal(validate(template).status,1,'Un lien temporaire ne passe pas');
  assert.equal(validate(validBody,'Titre sans convention').status,1);
  assert.equal(validate(validBody.replace('## Validation','## Sans revue')).status,1);
  assert.equal(validate(validBody.replace("J'autorise le merge de cette PR.",'Accord remplacé')).status,1);
  assert.equal(validate(validBody,undefined,false).status,1,'Le mode strict ne transforme pas une case vide en accord');
  const declared=validate(validBody.replace(/^- \[ \]/gm,'- [x]'),undefined,false);
  assert.equal(declared.status,0,declared.stderr+declared.stdout);
  assert.match(declared.stdout,/authenticite reste a verifier humainement/);
});
