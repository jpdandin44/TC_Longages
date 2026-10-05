import test from 'node:test';
import assert from 'node:assert/strict';
import {createPullRequestFeed,createCandidatePullRequestCheck} from '../scripts/framework-prs.mjs';

test('La lecture GitHub classe les PR et garde un cache sans inventer de validation',async()=>{
  let calls=0,time=100000;
  const entries=[
    {number:1,title:'Initiale',html_url:'https://github.com/jpdandin44/TC_Longages/pull/1',state:'closed',merged_at:'2026-09-29T10:25:00Z'},
    {number:2,title:'Revue',html_url:'https://github.com/jpdandin44/TC_Longages/pull/2',state:'open',draft:true,merged_at:null},
    {number:3,title:'Fermée',html_url:'https://github.com/jpdandin44/TC_Longages/pull/3',state:'closed',merged_at:null},
    {number:4,title:'Hors dépôt',html_url:'https://github.com/autre/depot/pull/4',state:'open',merged_at:null}
  ];
  const read=createPullRequestFeed({now:()=>time,fetchImpl:async()=>{calls++;return {ok:true,json:async()=>entries};}});
  const first=await read();assert.equal(first.source,'github');assert.deepEqual(first.items.map(p=>p.status),['merged','draft','closed']);assert.equal(first.items.length,3);
  assert.equal((await read()).source,'cache');assert.equal(calls,1);
  time+=121000;assert.equal((await read()).source,'github');assert.equal(calls,2);
});

test('La preuve exige la fusion du candidat exact, puis une lecture fraîche lors de validation',async()=>{
  const url='https://github.com/jpdandin44/TC_Longages/pull/12',sourceCommit='a'.repeat(40);
  let status='draft',time=100000,calls=0;
  const check=createCandidatePullRequestCheck({now:()=>time,fetchImpl:async()=>{calls++;if(status==='offline')throw Error('offline');return {ok:true,json:async()=>({number:12,html_url:url,head:{sha:sourceCommit},state:status==='merged'||status==='closed'?'closed':'open',draft:status==='draft',merged:status==='merged',merged_at:status==='merged'?'2026-10-05T12:00:00Z':null})};}});
  for(status of ['draft','open','closed'])assert.equal((await check({url,sourceCommit,force:true})).passed,false);
  status='merged';const merged=await check({url,sourceCommit,force:true});assert.equal(merged.passed,true);assert.equal(merged.headCommit,sourceCommit);
  const count=calls;assert.equal((await check({url,sourceCommit})).passed,true);assert.equal(calls,count);
  status='offline';assert.equal((await check({url,sourceCommit,force:true})).passed,false);
  assert.equal((await check({url:'https://github.com/autre/depot/pull/12',sourceCommit})).passed,false);assert.equal(calls,count+1);
});

test('Des reçus exclus peuvent suivre le candidat ; tout autre changement ou comparaison incomplète bloque',async()=>{
  const url='https://github.com/jpdandin44/TC_Longages/pull/12',sourceCommit='a'.repeat(40),headCommit='c'.repeat(40);
  let files=[{filename:'data/receipt.json'}],status='ahead';
  const check=createCandidatePullRequestCheck({fetchImpl:async target=>({ok:true,json:async()=>target.includes('/compare/')?{status,base_commit:{sha:sourceCommit},merge_base_commit:{sha:sourceCommit},files}:{number:12,html_url:url,head:{sha:headCommit},state:'closed',merged:true,merged_at:'2026-10-05T12:00:00Z'}})});
  const input={url,sourceCommit,excludedPaths:['data/receipt.json'],force:true};
  assert.equal((await check(input)).passed,true);
  for(files of [[{filename:'scripts/app.mjs'}],[{filename:'data/receipt.json',previous_filename:'scripts/app.mjs'}],Array.from({length:300},()=>({filename:'data/receipt.json'}))])assert.equal((await check(input)).passed,false);
  files=[];status='diverged';assert.equal((await check(input)).passed,false);
});

test('Une panne GitHub est indiquée sans transformer un état inconnu en fusion',async()=>{
  let time=100000,fail=true;
  const read=createPullRequestFeed({now:()=>time,fetchImpl:async()=>{if(fail)throw Error('offline');return {ok:true,json:async()=>[{number:3,title:'PR 3',html_url:'https://github.com/jpdandin44/TC_Longages/pull/3',state:'closed',merged_at:'2026-09-29T12:00:00Z'}]};}});
  assert.equal((await read()).source,'unavailable');assert.equal((await read()).source,'unavailable');
  time+=11000;fail=false;assert.equal((await read()).items[0].status,'merged');
  time+=121000;fail=true;const fallback=await read();assert.equal(fallback.source,'cache');assert.match(fallback.warning,/indisponible/);
});
