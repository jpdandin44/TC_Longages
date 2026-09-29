import {createServer} from 'node:http';
import {randomBytes, timingSafeEqual} from 'node:crypto';
import {resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {createReviewStore,ReviewError} from './framework-store.mjs';
import {renderShell,styles,clientScript,documentPage} from './framework-ui.mjs';

export const projectRoot=fileURLToPath(new URL('../',import.meta.url));
const safeToken=(given,expected)=>typeof given==='string'&&Buffer.byteLength(given)===Buffer.byteLength(expected)&&timingSafeEqual(Buffer.from(given),Buffer.from(expected));
export async function startFrameworkServer({root=projectRoot,port=4181,regenerate}={}) {
  if(!Number.isInteger(port)||port<0||port>65535) throw new Error('Port incorrect.');
  const store=await createReviewStore(root,{regenerate});
  try {await store.read();}catch(error){await store.close();throw error;}
  const token=randomBytes(32).toString('hex');let origin,closing=false;
  const server=createServer(async(req,res)=>{
    const send=(status,body,type='application/json; charset=utf-8')=>{res.writeHead(status,{'Content-Type':type,'Cache-Control':'no-store','X-Content-Type-Options':'nosniff','X-Frame-Options':'DENY','Referrer-Policy':'no-referrer','Cross-Origin-Resource-Policy':'same-origin','Content-Security-Policy':"default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'none'; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; form-action 'none'"});res.end(typeof body==='string'?body:JSON.stringify(body));};
    try {
      if(req.headers.host!==new URL(origin).host||!['127.0.0.1','::ffff:127.0.0.1'].includes(req.socket.remoteAddress)) throw new ReviewError('Adresse locale attendue.',403);
      if(req.headers.origin&&req.headers.origin!==origin) throw new ReviewError('Origine refusée.',403);
      if(req.headers['sec-fetch-site']&&!['none','same-origin'].includes(req.headers['sec-fetch-site'])) throw new ReviewError('Requête extérieure refusée.',403);
      if(!['GET','POST'].includes(req.method)) {res.setHeader('Allow','GET, POST');throw new ReviewError('Méthode refusée.',405);}
      const path=req.url;
      if(req.method==='GET'&&path==='/') return send(200,renderShell(token),'text/html; charset=utf-8');
      if(req.method==='GET'&&path==='/app.js') return send(200,clientScript,'text/javascript; charset=utf-8');
      if(req.method==='GET'&&path==='/app.css') return send(200,styles,'text/css; charset=utf-8');
      if(req.method==='GET'&&/^\/documents\/[0-7]\/\d+$/.test(path)) {
        const [, ,phase,index]=path.split('/'),state=await store.read(),doc=state.documents.find(d=>d.phaseId===Number(phase)&&d.index===Number(index));
        if(!doc)throw new ReviewError('Document inconnu.',404);return send(200,documentPage(doc),'text/html; charset=utf-8');
      }
      if(!['/api/state','/api/action'].includes(path)) throw new ReviewError('Page inconnue.',404);
      if((path==='/api/state'&&req.method!=='GET')||(path==='/api/action'&&req.method!=='POST'))throw new ReviewError('Méthode refusée pour cette route.',405);
      if(!safeToken(req.headers['x-review-token'],token))throw new ReviewError('Jeton de session locale invalide. Rouvrez le tableau de bord.',403);
      if(req.method==='GET') return send(200,await store.read());
      if(req.headers.origin!==origin)throw new ReviewError('Origine requise pour enregistrer.',403);
      if(req.headers['content-type']!=='application/json')throw new ReviewError('Le contenu JSON est requis.',415);
      const chunks=[];let size=0;
      for await(const chunk of req){size+=chunk.length;if(size>20000)throw new ReviewError('Saisie trop volumineuse.',413);chunks.push(chunk);}
      const body=Buffer.concat(chunks).toString('utf8');
      let data;try{data=JSON.parse(body);}catch{throw new ReviewError('JSON incorrect.',400);}
      if(!data||Array.isArray(data)||typeof data!=='object')throw new ReviewError('Objet JSON requis.',400);
      send(200,await store.mutate(data));
    }catch(error){send(error.status||500,{error:error.status?error.message:'Erreur locale du suivi. Consultez le journal du processus et réessayez après correction.'});if(!error.status)console.error('Framework:',error.message);}
  });
  server.requestTimeout=15000;server.headersTimeout=10000;server.keepAliveTimeout=1000;
  try{await new Promise((done,fail)=>{server.once('error',fail);server.listen(port,'127.0.0.1',done);});}catch(error){await store.close();throw error;}
  origin='http://127.0.0.1:'+server.address().port;
  return {origin,server,close:async()=>{if(closing)return;closing=true;await new Promise(resolve=>server.close(resolve));await store.close();}};
}
if(process.argv[1]&&resolve(process.argv[1])===fileURLToPath(import.meta.url)) {
  const regenerate=async()=>{const {readState,renderViews}=await import('./framework.mjs');const {writeFile,rename}=await import('node:fs/promises');for(const [name,content] of Object.entries(renderViews(await readState()))){const path=resolve(projectRoot,'docs/suivi-chantier',name),tmp=path+'.ui.tmp';await writeFile(tmp,content,'utf8');await rename(tmp,path);}};
  startFrameworkServer({regenerate}).then(app=>{console.log('Suivi interactif local : '+app.origin+'/ — Ctrl+C pour arrêter.');for(const signal of ['SIGINT','SIGTERM'])process.on(signal,()=>app.close().then(()=>process.exit(0)));}).catch(error=>{console.error(error.message);process.exitCode=1;});
}
