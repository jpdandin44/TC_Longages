import {readFile,realpath} from 'node:fs/promises';
import {resolve} from 'node:path';
import {startFrameworkServer} from './framework-server.mjs';

// The configuration is an explicit local file; no path is accepted through HTTP.
const argument=process.argv[2];
if(!argument)throw new Error('Indiquez le fichier local de configuration du cockpit.');
const config=JSON.parse(await readFile(await realpath(resolve(argument)),'utf8'));
if(config.project!=='tclongages'||!Array.isArray(config.iterationRoots)||!config.iterationRoots.length)throw new Error('Configuration TC du cockpit attendue.');
const root=await realpath(config.historyRoot),iterationRoots=await Promise.all(config.iterationRoots.map(p=>realpath(p)));
const app=await startFrameworkServer({root,iterationRoots,candidateRoots:config.candidateRoots??{},port:config.port??4181});
console.log('Cockpit local des versions : '+app.origin+'/');
for(const signal of ['SIGINT','SIGTERM'])process.on(signal,()=>app.close().then(()=>process.exit(0)));
