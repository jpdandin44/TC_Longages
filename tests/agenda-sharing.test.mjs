import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
const source = name => readFileSync(new URL('../drupal/web/modules/custom/tcl_bureau/js/' + name, import.meta.url), 'utf8');
const element = (values = {}) => ({ ...values, handlers: {}, addEventListener(type, fn) { this.handlers[type] = fn; } });
function agenda(saved, failStorage = false) {
  const frame = element({src: 'https://calendar.google.com/calendar/embed?src=fixture%40example.invalid&ctz=Europe%2FParis'});
  const view = element(), theme = element(), status = element(), reset = element(), tools = element({hidden:true});
  const nodes = {'iframe':frame, '[data-agenda-tools]':tools, '[data-agenda-view]':view, '[data-agenda-theme]':theme, '[data-agenda-status]':status, '[data-agenda-reset]':reset};
  const panel = {dataset:{}, querySelector: key => nodes[key]};
  let stored;
  runInNewContext(source('agenda-tools.js'), {URL, location:{href:'https://preprod.tclongages.fr/calendrier.html'}, document:{querySelector:()=>panel}, localStorage:{getItem:()=>saved, setItem:(key,value)=>{if(failStorage)throw Error(); stored=value;}}});
  return {frame,view,theme,status,reset,tools,panel,stored:()=>stored};
}
test('agenda opens in Month despite stored List, retains calendar identity and resets', () => {
  const a = agenda('{"view":"AGENDA","theme":"nature"}');
  assert.equal(a.tools.hidden,false); assert.equal(a.panel.dataset.theme,undefined);
  const url = new URL(a.frame.src);
  assert.equal(url.searchParams.get('src'),'fixture@example.invalid');
  assert.equal(url.searchParams.get('ctz'),'Europe/Paris');
  assert.equal(url.searchParams.get('mode'),'MONTH');
  a.view.value='AGENDA'; a.view.handlers.change();
  assert.equal(new URL(a.frame.src).searchParams.get('mode'),'AGENDA');
  a.reset.handlers.click();
  assert.equal(a.view.value,'MONTH');
  assert.equal(a.stored(),undefined);
});
test('agenda tolerates invalid preferences and unavailable storage', () => {
  for(const saved of ['broken','null','{"view":"OTHER","theme":"unknown"}','{"view":"WEEK"}']) {
    const a=agenda(saved,true); assert.equal(a.view.value,'MONTH'); assert.equal(a.theme.value,undefined);
    a.view.value='WEEK'; a.view.handlers.change();
    assert.match(a.status.textContent,/cette visite/);
    assert.equal(new URL(a.frame.src).searchParams.get('mode'),'WEEK');
  }
});
test('sharing copies exact text or selects it when clipboard fails', async () => {
  for (const fail of [false,true]) {
    const button=element({hidden:true}), status=element(); let copied, selected=false;
    const field={value:'Texte fictif\nAvec affiche',focus(){},select(){selected=true;}};
    const panel={querySelector:key=>({'[data-copy-message]':button,textarea:field,'[data-copy-status]':status})[key]};
    runInNewContext(source('sharing-tools.js'),{document:{querySelectorAll:()=>[panel]},navigator:fail?{}:{clipboard:{writeText:async text=>{copied=text;}}}});
    await button.handlers.click();
    assert.equal(button.disabled,false);
    if(fail) { assert.ok(selected); assert.match(status.textContent,/sélectionné/); }
    else { assert.equal(copied,field.value); assert.match(status.textContent,/Aucune publication/); }
  }
});
