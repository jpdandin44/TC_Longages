const endpoint='https://api.github.com/repos/jpdandin44/TC_Longages/pulls';
const projectUrl=/^https:\/\/github\.com\/jpdandin44\/TC_Longages\/pull\/(\d+)$/;

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
