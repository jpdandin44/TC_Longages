// Loopback-only identity provider for the Drupal recipe. No real identity/network.
import {createServer} from 'node:http';
import {readFileSync} from 'node:fs';
import {createHash,randomBytes,sign} from 'node:crypto';
import {fileURLToPath} from 'node:url';
const root=fileURLToPath(new URL('../.local/',import.meta.url));
const settings=JSON.parse(readFileSync(root+'oauth-fixture-provider.json'));
const key=readFileSync(root+'oauth-fixture-private.pem');
const codes=new Map(),tokens=new Map();
const json=(res,status,data)=>{res.writeHead(status,{'Content-Type':'application/json','Cache-Control':'no-store'});res.end(JSON.stringify(data));};
const jwt=(claims,alg='RS256')=>{
  const parts=[{alg,typ:'JWT'},claims].map(x=>Buffer.from(JSON.stringify(x)).toString('base64url'));
  return parts.join('.')+'.'+sign('RSA-SHA256',Buffer.from(parts.join('.')),key).toString('base64url');
};
createServer(async(req,res)=>{
  try{
    const url=new URL(req.url,'http://127.0.0.1:4190');
    if(url.pathname==='/authorize'){
      const query=url.searchParams;
      if(query.get('client_id')!==settings.client_id||query.get('redirect_uri')!==settings.redirect_uri||query.get('code_challenge_method')!=='S256')return json(res,400,{error:'invalid_request'});
      const mode=JSON.parse(readFileSync(root+'oauth-fixture-mode.json'));
      const back=new URL(settings.redirect_uri);
      back.searchParams.set('state',query.get('state'));
      if(mode.mode==='denied')back.searchParams.set('error','access_denied');
      else{
        const code=randomBytes(32).toString('hex');
        codes.set(code,{nonce:query.get('nonce'),challenge:query.get('code_challenge'),...mode,created:Date.now()});
        back.searchParams.set('code',code);
      }
      res.writeHead(303,{Location:back.href,'Cache-Control':'no-store'});return res.end();
    }
    if(url.pathname==='/token'&&req.method==='POST'){
      let body='';for await(const part of req){body+=part;if(body.length>16384)throw Error('large');}
      const form=new URLSearchParams(body),code=form.get('code'),transaction=codes.get(code);codes.delete(code);
      if(!transaction||Date.now()-transaction.created>300000||form.get('client_id')!==settings.client_id||form.get('client_secret')!==settings.client_secret||form.get('redirect_uri')!==settings.redirect_uri||form.get('grant_type')!=='authorization_code'||createHash('sha256').update(form.get('code_verifier')??'').digest('base64url')!==transaction.challenge)return json(res,400,{error:'invalid_grant'});
      if(transaction.mode==='unavailable')return json(res,503,{error:'unavailable'});
      const now=Math.floor(Date.now()/1000),claims={iss:settings.issuer,aud:settings.client_id,sub:transaction.subject,iat:now,exp:now+600,nonce:transaction.nonce};
      if(transaction.mode==='wrong_issuer')claims.iss='https://wrong.invalid';
      if(transaction.mode==='wrong_audience')claims.aud='another-client';
      if(transaction.mode==='wrong_nonce')claims.nonce='wrong';
      if(transaction.mode==='expired')claims.exp=now-1;
      if(transaction.mode==='missing_nonce')delete claims.nonce;
      const token=randomBytes(32).toString('hex');tokens.set(token,transaction);
      let id=jwt(claims,transaction.mode==='wrong_algorithm'?'HS256':'RS256');
      if(transaction.mode==='wrong_signature')id=id.slice(0,-8)+'AAAAAAAA';
      return json(res,200,{access_token:token,token_type:'Bearer',id_token:id});
    }
    if(url.pathname==='/userinfo'){
      const token=req.headers.authorization?.replace(/^Bearer /,''),transaction=tokens.get(token);
      tokens.delete(token);
      if(!transaction)return json(res,401,{error:'invalid_token'});
      return json(res,200,{sub:transaction.mode==='userinfo_mismatch'?'different':transaction.subject});
    }
    json(res,404,{error:'not_found'});
  }catch{json(res,500,{error:'fixture_error'});}
}).listen(4190,'127.0.0.1',()=>console.log('Fictitious OAuth provider on loopback :4190 only.'));
