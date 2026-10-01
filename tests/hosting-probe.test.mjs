import test from 'node:test';
import assert from 'node:assert/strict';
import {mkdtemp,rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {resolve,join} from 'node:path';
import {spawn,spawnSync} from 'node:child_process';
import {createServer} from 'node:net';
import {buildHostingProbe} from '../scripts/build-hosting-probe.mjs';
import {fileURLToPath} from 'node:url';

const root=fileURLToPath(new URL('../',import.meta.url));
const phpAvailable=spawnSync('php',['-v'],{stdio:'ignore'}).status===0;

test('La sonde PHP refuse une plateforme incomplète et ne révèle pas les variables privées',{skip:!phpAvailable},async()=>{
  const directory=await mkdtemp(join(tmpdir(),'tcl-probe-test-'));
  try{
    const {web}=await buildHostingProbe(root,directory);
    const run=spawnSync('php',['-n',resolve(web,'tcl-probe.php')],{encoding:'utf8',env:{...process.env,TCL_DB_PASSWORD:'SENTINEL_DO_NOT_DISCLOSE',TCL_PRIVATE_FILES:'SENTINEL_PRIVATE_PATH'}});
    assert.equal(run.status,0,run.stderr);
    const result=JSON.parse(run.stdout);
    assert.equal(result.kind,'tcl-runtime-probe');
    assert.equal(result.compatible,false);
    assert.ok(result.missingExtensions.includes('pdo_mysql'));
    assert.doesNotMatch(run.stdout,/SENTINEL_|<\?php/);
  }finally{
    assert.ok(directory.startsWith(resolve(tmpdir())+requireSeparator())&&directory.includes('tcl-probe-test-'));
    await rm(directory,{recursive:true,force:true});
  }
});

function requireSeparator(){return process.platform==='win32'?'\\':'/';}

test('La sonde exécutée sert du JSON sans cache, gère HEAD et refuse POST',{skip:!phpAvailable},async()=>{
  const directory=await mkdtemp(join(tmpdir(),'tcl-probe-http-'));
  let server;
  try{
    const {web,manifest}=await buildHostingProbe(root,directory);
    const reservation=createServer();
    await new Promise(resolve=>reservation.listen(0,'127.0.0.1',resolve));
    const port=reservation.address().port;
    await new Promise(resolve=>reservation.close(resolve));
    server=spawn('php',['-n','-S',`127.0.0.1:${port}`,'-t',web],{stdio:['ignore','ignore','pipe']});
    let serverError='';server.stderr.on('data',data=>{serverError+=data;});
    const url=`http://127.0.0.1:${port}/tcl-probe.php`;
    let response;
    for(let attempt=0;attempt<40;attempt++){
      try{response=await fetch(url);break;}catch{await new Promise(resolve=>setTimeout(resolve,50));}
    }
    assert.ok(response,serverError);
    assert.equal(response.status,503);
    assert.match(response.headers.get('content-type'),/application\/json/);
    assert.equal(response.headers.get('cache-control'),'no-store');
    assert.match(response.headers.get('x-robots-tag'),/noindex/);
    const result=await response.json();
    assert.equal(result.minimumPhp,manifest.requirements.minimumPhp);
    assert.deepEqual(Object.keys(result.extensions),manifest.requirements.requiredExtensions);
    const head=await fetch(url,{method:'HEAD'});
    assert.equal(head.status,503);assert.equal(await head.text(),'');
    const post=await fetch(url,{method:'POST',body:'ignored'});
    assert.equal(post.status,405);assert.equal(post.headers.get('allow'),'GET, HEAD');
  }finally{
    if(server&&server.exitCode===null){const stopped=new Promise(resolve=>server.once('exit',resolve));server.kill();await stopped;}
    assert.ok(directory.startsWith(resolve(tmpdir())+requireSeparator())&&directory.includes('tcl-probe-http-'));
    await rm(directory,{recursive:true,force:true});
  }
});
