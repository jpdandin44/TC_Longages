import test from 'node:test';
import assert from 'node:assert/strict';
import {createPullRequestFeed} from '../scripts/framework-prs.mjs';

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

test('Une panne GitHub est indiquée sans transformer un état inconnu en fusion',async()=>{
  let time=100000,fail=true;
  const read=createPullRequestFeed({now:()=>time,fetchImpl:async()=>{if(fail)throw Error('offline');return {ok:true,json:async()=>[{number:3,title:'PR 3',html_url:'https://github.com/jpdandin44/TC_Longages/pull/3',state:'closed',merged_at:'2026-09-29T12:00:00Z'}]};}});
  assert.equal((await read()).source,'unavailable');assert.equal((await read()).source,'unavailable');
  time+=11000;fail=false;assert.equal((await read()).items[0].status,'merged');
  time+=121000;fail=true;const fallback=await read();assert.equal(fallback.source,'cache');assert.match(fallback.warning,/indisponible/);
});
