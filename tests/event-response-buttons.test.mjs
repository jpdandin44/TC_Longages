import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';
const script=readFileSync(new URL('../drupal/web/modules/custom/tcl_bureau/js/event-response.js',import.meta.url),'utf8');
for(const encoded of [false,true]) for(const choice of ['yes','no','maybe']) test(`Email ${choice} encoded=${encoded}: prefill without saving, explicit confirmation`,async()=>{
 const radios=['yes','no','maybe'].map(value=>({value,checked:false}));Object.defineProperty(radios,'value',{get(){return radios.find(r=>r.checked)?.value;}});
 const listeners={},button={},elements={answer:radios,count:{value:1}};
 const form={elements,addEventListener:(name,fn)=>listeners[name]=fn,querySelector:()=>button};
 const nodes={'reply-form':form};for(const id of ['reply-status','reply-count','reply-title','reply-details','reply-name','reply-context','refresh'])nodes[id]={addEventListener(){}};
 const calls=[];let stripped=false;const context={title:'Fête',date:'2027-11-18',time:'19:00',location:'Club',deadline:'2027-11-17',name:'Fictif',answer:'unknown',count:0,revision:1,eventRevision:1,open:true};
 const sandbox={location:{hash:'#'+(encoded ? encodeURIComponent('a'.repeat(64)+':'+choice) : 'a'.repeat(64)+':'+choice),pathname:'/evenements/reponse'},history:{replaceState(){stripped=true;}},document:{getElementById:id=>nodes[id]},fetch:async(url,opts)=>{calls.push({url,data:JSON.parse(opts.body)});return {ok:true,json:async()=>({csrf:'fixture',context})};}};
 vm.runInNewContext(script,sandbox);await new Promise(resolve=>setImmediate(resolve));
 assert.equal(stripped,true);assert.equal(calls.length,1);assert.match(calls[0].url,/context$/);assert.equal(radios.value,choice);assert.equal(nodes['reply-count'].hidden,choice!=='yes');
 elements.count.value='4';await listeners.submit({preventDefault(){}});assert.equal(calls.length,2);assert.match(calls[1].url,/save$/);assert.equal(calls[1].data.answer,choice);assert.equal(calls[1].data.count,choice==='yes'?4:0);
});
