const endpoint='https://api.github.com/repos/jpdandin44/TC_Longages/pulls';
const projectUrl=/^https:\/\/github\.com\/jpdandin44\/TC_Longages\/pull\/(\d+)$/;
const validSha=value=>typeof value==='string'&&/^[a-f0-9]{40}$/.test(value);

// A display cache never authorizes an approval. The store forces a fresh
// detail read at approval time and records the exact merged head it checked.
export function createCandidatePullRequestCheck({fetchImpl=fetch,now=Date.now,ttlMs=30000}={}) {
  const cache=new Map();
  const read=async url=>{
    const response=await fetchImpl(url,{headers:{Accept:'application/vnd.github+json','User-Agent':'TC-Longages-local-review'},signal:AbortSignal.timeout(4000),redirect:'error'});
    if(!response.ok)throw Error('GitHub indisponible');
    return response.json();
  };
  return async function check({url,sourceCommit,excludedPaths=[],force=false}) {
    const match=typeof url==='string'&&url.match(projectUrl);
    if(!match||!validSha(sourceCommit))return {passed:false,status:'unknown',url,issues:['La PR candidate et son commit doivent être identifiés.']};
    const key=JSON.stringify([url,sourceCommit,excludedPaths]),previous=cache.get(key);
    if(!force&&previous&&now()-previous.checkedAtMs<ttlMs)return previous;
    let result;
    try {
      const pr=await read(endpoint+'/'+match[1]);
      if(pr.html_url!==url||pr.number!==Number(match[1])||!['open','closed'].includes(pr.state)||!validSha(pr.head?.sha))throw Error('Réponse incorrecte');
      const merged=pr.state==='closed'&&pr.merged===true&&Number.isFinite(Date.parse(pr.merged_at));
      const status=merged?'merged':pr.state==='open'?(pr.draft?'draft':'open'):'closed';
      result={passed:false,status,url,headCommit:pr.head.sha,mergedAt:merged?pr.merged_at:null,issues:[]};
      if(!merged)result.issues.push('PR #'+pr.number+' '+({draft:'en brouillon',open:'ouverte',closed:'fermée sans fusion'}[status])+ ' : vérifiez puis fusionnez la PR candidate avant de valider la phase.');
      else {
        if(pr.head.sha!==sourceCommit) {
          const comparison=await read(endpoint.replace(/\/pulls$/,'/compare/')+sourceCommit+'...'+pr.head.sha);
          const files=comparison.files;
          // GitHub limits this list to 300 files. An incomplete comparison is
          // not evidence that only excluded operational receipts changed.
          if(!['ahead','identical'].includes(comparison.status)||comparison.base_commit?.sha!==sourceCommit||comparison.merge_base_commit?.sha!==sourceCommit||!Array.isArray(files)||files.length>=300||files.some(file=>typeof file.filename!=='string'||!excludedPaths.includes(file.filename)||(file.previous_filename&&!excludedPaths.includes(file.previous_filename)))) {
            result.issues.push('La PR fusionnée ne correspond pas au candidat présenté ; qualifiez sa version exacte avant validation.');
          }
        }
        result.passed=result.issues.length===0;
      }
    } catch {result={passed:false,status:'unavailable',url,issues:['Impossible de vérifier la PR candidate sur GitHub. La validation reste bloquée ; vos critères et votre commentaire sont conservés.']};}
    result={...result,checkedAt:new Date(now()).toISOString(),checkedAtMs:now()};cache.set(key,result);return result;
  };
}

export function createPullRequestFeed({fetchImpl=fetch,now=Date.now,ttlMs=120000}={}) {
  let last=null,lastAttempt=0,pending=null;
  async function collect() {
    const items=[];
    for(let page=1;page<=5;page++) {
      const response=await fetchImpl(`${endpoint}?state=all&per_page=100&page=${page}`,{
        headers:{Accept:'application/vnd.github+json','User-Agent':'TC-Longages-local-review'},
        signal:AbortSignal.timeout(4000),redirect:'error'
      });
      if(!response.ok)throw new Error('Lecture GitHub indisponible.');
      const batch=await response.json();
      if(!Array.isArray(batch)||batch.length>100)throw new Error('Réponse GitHub incorrecte.');
      for(const item of batch) {
        const match=typeof item.html_url==='string'&&item.html_url.match(projectUrl);
        if(!match||Number(match[1])!==item.number||!Number.isSafeInteger(item.number)||typeof item.title!=='string'||!['open','closed'].includes(item.state))continue;
        const merged=typeof item.merged_at==='string'&&Number.isFinite(Date.parse(item.merged_at));
        items.push({number:item.number,title:item.title.slice(0,180),url:item.html_url,status:merged?'merged':item.state==='open'?(item.draft?'draft':'open'):'closed',mergedAt:merged?item.merged_at:null});
      }
      if(batch.length<100)return {items,partial:false};
    }
    return {items,partial:true};
  }
  return async function read({force=false}={}) {
    const time=now();
    if(last&&!force&&time-last.checkedAtMs<ttlMs)return {...last,source:'cache'};
    if(pending)return pending;
    if(time-lastAttempt<10000)return last?{...last,source:'cache'}:{items:[],partial:false,checkedAt:null,source:'unavailable',warning:'GitHub est momentanément indisponible ; les états des PR ne peuvent pas être vérifiés.'};
    lastAttempt=time;
    pending=(async()=>{
      try {
        const result=await collect();
        last={...result,checkedAt:new Date(now()).toISOString(),checkedAtMs:now()};
        return {...last,source:'github'};
      } catch {
        return last?{...last,source:'cache',warning:'GitHub est momentanément indisponible ; derniers états vérifiés affichés.'}:{items:[],partial:false,checkedAt:null,source:'unavailable',warning:'GitHub est momentanément indisponible ; les états des PR ne peuvent pas être vérifiés.'};
      } finally {pending=null;}
    })();
    return pending;
  };
}
