/** CRM F3-51: conservative evidence-completeness gate. This never authorizes deployment. */
import {readFileSync} from 'node:fs';
import {pathToFileURL} from 'node:url';
export const GATES=Object.freeze(['private_environment','revision_provenance','service_authentication','tenant_isolation','durable_delivery','idempotency_replay','crm_lead_persistence','negative_path_recovery','audit_redaction','postgres_permissions','backup_restore','independent_security_review']);
const EXPECTED=new Set(GATES);
const HASH=/^[a-f0-9]{40}$/;
const ID=/^[a-z0-9][a-z0-9._-]{5,119}$/;
const SAFE_LINK=/^https:\/\/github\.com\/rafhael100\/(imagine-comercial|imagine-portal|imagine-crm)\/(actions\/runs\/\d+|issues\/\d+|pull\/\d+|commit\/[a-f0-9]{40})$/;
const plain=o=>o!==null && typeof o==='object' && !Array.isArray(o) && (Object.getPrototypeOf(o)===Object.prototype||Object.getPrototypeOf(o)===null);
export function assessReadiness(record){
 const failures=[];
 if(!plain(record)||Object.keys(record).sort().join('|')!==['environment','gates','revisions','schema'].sort().join('|'))return {status:'NO_GO',failures:['invalid document shape'],accepted:0,required:GATES.length};
 if(record.schema!=='imagine.crm.operational-homologation-evidence.v1')failures.push('schema mismatch');
 if(record.environment!=='private-imagine-homologation')failures.push('non-operational environment');
 if(!plain(record.revisions)||Object.keys(record.revisions).sort().join('|')!==['comercial','crm','portal'].sort().join('|')||!['comercial','portal','crm'].every(k=>typeof record.revisions[k]==='string'&&HASH.test(record.revisions[k])))failures.push('missing pinned revisions');
 if(!plain(record.gates)||Object.keys(record.gates).length!==GATES.length||Object.keys(record.gates).some(k=>!EXPECTED.has(k)))failures.push('gate inventory mismatch');
 let accepted=0;
 for(const gate of GATES){
  const evidence=plain(record.gates)?record.gates[gate]:undefined;
  if(!plain(evidence)||Object.keys(evidence).sort().join('|')!==['evidence_url','execution_id','reviewer','status'].sort().join('|')||evidence.status!=='verified'||typeof evidence.execution_id!=='string'||!ID.test(evidence.execution_id)||typeof evidence.reviewer!=='string'||!ID.test(evidence.reviewer)||typeof evidence.evidence_url!=='string'||!SAFE_LINK.test(evidence.evidence_url))failures.push('missing or unverified evidence: '+gate);
  else accepted++;
 }
 return {status:failures.length===0?'EVIDENCE_COMPLETE_REVIEW_REQUIRED':'NO_GO',failures,accepted,required:GATES.length};
}
if(process.argv[1]&&import.meta.url===pathToFileURL(process.argv[1]).href){
 const file=process.argv[2];if(!file){console.error('usage: node homologation-gate.mjs evidence.json');process.exit(2);}
 try{const source=readFileSync(file);if(source.length>65536)throw Error('evidence exceeds 64 KiB');const result=assessReadiness(JSON.parse(source.toString('utf8')));console.log(JSON.stringify(result,null,2));process.exit(result.status==='EVIDENCE_COMPLETE_REVIEW_REQUIRED'?0:1);}
 catch(e){console.error('NO_GO: invalid evidence file',e.message);process.exit(1);}
}
