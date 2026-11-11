#!/usr/bin/env node
/**
 * CRM F3-43: REAL concurrent HTTP requests against 4 PHP CLI workers + PG16.
 * Runs ONLY in a disposable GitHub Actions job; no live lead/tenant/receipt.
 * The server uses actual CRM domain classes from F3-40/F3-41, not a mock JS API.
 */
import assert from 'node:assert/strict';
import {createHmac,randomUUID,timingSafeEqual} from 'node:crypto';
import {execFileSync} from 'node:child_process';

const env=process.env;
if(env.GITHUB_ACTIONS!=='true'||
   env.RUNNER_ENVIRONMENT!=='github-hosted'||
   env.CRM_F340_LAB!=='1'||
   env.CRM_F341_HTTP_LAB!=='1'||
   env.CRM_F343_CONCURRENT_LAB!=='1'||
   env.DB_HOST!=='127.0.0.1'||
   env.DB_DATABASE!=='imagine_crm_handoff_ci'||
   !/^[0-9a-f]{96}$/.test(env.CRM_F341_REQUEST_KEY_HEX??'')||
   !/^[0-9a-f]{96}$/.test(env.CRM_F341_RECEIPT_KEY_HEX??'')||
   !env.DB_PASSWORD||
   env.CRM_F341_REQUEST_KEY_HEX===env.CRM_F341_RECEIPT_KEY_HEX||
   ['PGHOST','PGHOSTADDR','PGSERVICE','PGUSER','PGDATABASE','PGPORT']
      .some(k=>env[k]!==undefined))
  throw new Error('F3-43 strictly requires standalone GitHub-hosted synthetic PG16');

const receiptKey=Buffer.from(env.CRM_F341_RECEIPT_KEY_HEX,'hex');
const requestKey=Buffer.from(env.CRM_F341_REQUEST_KEY_HEX,'hex');
const target='http://127.0.0.1:19141';
const requestDomain='imagine.portal.synthetic-service-request.v1';
const lookupDomain='imagine.portal.synthetic-crm-lookup.v1';
const receiptDomain='imagine.lead-intake.synthetic-receipt.v1';
const identities=[
  'organization_reference','binding_reference','submission_reference',
  'site_reference','form_reference','destination_reference'
];
const pinned={
  organization_reference:'11111111-1111-4111-8111-111111111111',
  binding_reference:'22222222-2222-4222-8222-222222222222',
  site_reference:'44444444-4444-4444-8444-444444444444',
  form_reference:'55555555-5555-4555-8555-555555555555',
  destination_reference:'66666666-6666-4666-8666-666666666666'
};

function sql(source){
  assert.match(source,/^(?:SHOW server_version_num;|SELECT .{1,500};)$/);
  return execFileSync('psql',[
    '-h','127.0.0.1','-p','5432','-U','postgres',
    '-d','imagine_crm_handoff_ci','-X','-A','-t',
    '-v','ON_ERROR_STOP=1','-c',source
  ],{
    env:{...env,PGPASSWORD:env.DB_PASSWORD},encoding:'utf8',timeout:15000,
    maxBuffer:65536
  }).trim();
}
assert.equal(sql('SELECT current_database();'),'imagine_crm_handoff_ci');
assert.ok(Number(sql('SHOW server_version_num;'))>=160000);

function safeUuid(value){
  assert.match(value,/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/);
  return "'"+value+"'::uuid";
}
function count(table,submission){
  assert.ok(['metadata_receipts','request_nonces'].includes(table));
  const num=Number(sql(`SELECT count(*) FROM crm_portal_handoff_ci.${table}
    WHERE organization_reference='11111111-1111-4111-8111-111111111111'::uuid
      AND submission_reference=${safeUuid(submission)};`.replace(/\s+/g,' ')));
  assert.ok(Number.isSafeInteger(num));
  return num;
}
function nonceCount(nonce){
  return Number(sql(`SELECT count(*) FROM crm_portal_handoff_ci.request_nonces
    WHERE nonce=${safeUuid(nonce)};`.replace(/\s+/g,' ')));
}
function envelope(submission=randomUUID()){
  return {
    protocol:'imagine.lead-intake',schema_version:1,
    ...pinned,submission_reference:submission
  };
}
function canonical(message,domain,nonce,issued,expires){
  return [
    domain,...identities.map(k=>message[k].toLowerCase()),
    String(issued),String(expires),nonce
  ].join('\n');
}
function sign(message,{domain=requestDomain,nonce=randomUUID(),key=requestKey}={}){
  const issued=Math.floor(Date.now()/1000),expires=issued+120;
  return {
    issued,expires,nonce,
    mac:createHmac('sha256',key).update(
      canonical(message,domain,nonce,issued,expires)
    ).digest('hex')
  };
}
async function call(message,auth,{
  route='/__lab/crm/ingest',dropReceipt=false
}={}){
  const headers={
    'content-type':'application/json',
    'X-Lab-Issued-At':String(auth.issued),
    'X-Lab-Expires-At':String(auth.expires),
    'X-Lab-Nonce':auth.nonce,
    'X-Lab-Signature':auth.mac
  };
  if(dropReceipt)headers['X-Lab-Drop-Receipt']='1';
  const response=await fetch(target+route,{
    method:'POST',headers,body:JSON.stringify(message),
    redirect:'error',signal:AbortSignal.timeout(15000)
  });
  const value=await response.json();
  assert.equal(value.schema,'imagine.crm.synthetic-portal-http.v1');
  assert.equal(value.mode,'CI_ONLY_REAL_PG16_NOT_REAL_CRM_RECEIPT');
  for(const flag of [
    'realCrmPersisted','tenantAuthenticatedForProduction','canAcknowledgeVisitor',
    'canAutoRetryUnknownOutcome','productionAuthorized'
  ])assert.equal(value[flag],false,flag);
  assert.ok(!JSON.stringify(value).includes(message.submission_reference));
  return {status:response.status,reason:value.reasonCode,receipt:value.receipt};
}
function verifyReceipt(receipt,expected){
  assert.ok(receipt&&typeof receipt==='object');
  assert.deepEqual(Object.keys(receipt).sort(),[
    'expires_at','issued_at','mac','nonce'
  ]);
  assert.ok(Number.isSafeInteger(receipt.issued_at));
  assert.ok(Number.isSafeInteger(receipt.expires_at));
  assert.match(receipt.nonce,/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/);
  assert.match(receipt.mac,/^[0-9a-f]{64}$/);
  const now=Math.floor(Date.now()/1000);
  assert.ok(receipt.issued_at<=now+30&&receipt.expires_at>now);
  assert.ok(receipt.expires_at-receipt.issued_at<=300);
  const expectedMac=createHmac('sha256',receiptKey).update(
    canonical(expected,receiptDomain,receipt.nonce,
      receipt.issued_at,receipt.expires_at)
  ).digest();
  assert.ok(timingSafeEqual(Buffer.from(receipt.mac,'hex'),expectedMac));
  const wrongMac=createHmac('sha256',requestKey).update(
    canonical(expected,receiptDomain,receipt.nonce,
      receipt.issued_at,receipt.expires_at)
  ).digest();
  assert.ok(!timingSafeEqual(Buffer.from(receipt.mac,'hex'),wrongMac));
}
function frequencies(results){
  const out=new Map();
  for(const r of results){
    const key=r.status+'|'+r.reason;
    out.set(key,(out.get(key)??0)+1);
  }
  return out;
}
function expectCount(results,key,num){
  const counts=frequencies(results);
  assert.equal(counts.get(key)??0,num,
    'Unexpected concurrency result for '+key+': '+JSON.stringify(Object.fromEntries(counts)));
}

// 1. Many request-scoped nonces for the SAME logical submission.
// Four real PHP worker processes must race against the same PG unique index.
// Exactly one FIRST; no duplicate durable lead; all valid attempts tracked.
const same=envelope();
assert.equal(count('metadata_receipts',same.submission_reference),0);
const sameResults=await Promise.all(Array.from({length:16},()=>
  call(same,sign(same))
));
expectCount(sameResults,'202|LAB_MOCK_COMMITTED_FIRST',1);
expectCount(sameResults,'202|LAB_MOCK_COMMITTED_LOGICAL_REPLAY',15);
assert.equal(count('metadata_receipts',same.submission_reference),1);
assert.equal(count('request_nonces',same.submission_reference),16);
for(const r of sameResults)verifyReceipt(r.receipt,same);

// 2. Identical HMAC/nonce racing 12 times against the same submission.
const identical=envelope(),sameAuth=sign(identical);
const identicalResults=await Promise.all(Array.from({length:12},()=>
  call(identical,sameAuth)
));
expectCount(identicalResults,'202|LAB_MOCK_COMMITTED_FIRST',1);
expectCount(identicalResults,'409|REQUEST_NONCE_REPLAY',11);
assert.equal(count('metadata_receipts',identical.submission_reference),1);
assert.equal(count('request_nonces',identical.submission_reference),1);
assert.equal(nonceCount(sameAuth.nonce),1);

// 3. Same nonce reused with DIFFERENT valid submission IDs. Exactly one
// destination is stored, and the other signed requests do not create rows.
const reusedNonce=randomUUID();
const choices=Array.from({length:12},()=>envelope());
const collision=await Promise.all(choices.map(m=>
  call(m,sign(m,{nonce:reusedNonce}))
));
expectCount(collision,'202|LAB_MOCK_COMMITTED_FIRST',1);
expectCount(collision,'409|REQUEST_NONCE_REPLAY',11);
assert.equal(nonceCount(reusedNonce),1);
assert.equal(choices.reduce((a,m)=>a+count('metadata_receipts',m.submission_reference),0),1);
assert.equal(choices.reduce((a,m)=>a+count('request_nonces',m.submission_reference),0),1);

// 4. Signed forged tenant and signed forged destination MUST fail under
// server-fixed binding; syntactically valid MAC is never authorization.
const forbidden=Array.from({length:12},(_,i)=>{
  const e=envelope();
  if(i%2===0)e.organization_reference=randomUUID();
  else e.destination_reference=randomUUID();
  return e;
});
const denied=await Promise.all(forbidden.map(e=>call(e,sign(e))));
expectCount(denied,'403|PINNED_CONTEXT_CONFLICT',12);
for(const e of forbidden){
  assert.equal(count('metadata_receipts',e.submission_reference),0);
  assert.equal(count('request_nonces',e.submission_reference),0);
}

// 5. All forged MACs fail before creating any nonce or lead, even in parallel.
const wrong=Array.from({length:12},()=>envelope());
const rejected=await Promise.all(wrong.map(e=>call(e,sign(e,{
  key:Buffer.from('not-the-actual-fixture-secret'.padEnd(48,'_'))
}))));
expectCount(rejected,'401|SYNTHETIC_AUTH_REJECTED',12);
for(const e of wrong)assert.equal(count('metadata_receipts',e.submission_reference),0);

// 6. Commit succeeds but reply drops. There must NOT be a blind retry;
// simultaneous readbacks use a distinct auth domain and are read-only.
const lost=envelope();
const lostResponse=await call(lost,sign(lost),{dropReceipt:true});
assert.equal(lostResponse.status,503);
assert.equal(lostResponse.reason,'UNKNOWN_OUTCOME_NO_RECEIPT');
assert.equal(lostResponse.receipt,undefined);
assert.equal(count('metadata_receipts',lost.submission_reference),1);
assert.equal(count('request_nonces',lost.submission_reference),1);
const looks=await Promise.all(Array.from({length:12},()=>
  call(lost,sign(lost,{domain:lookupDomain}),{route:'/__lab/crm/readback'})
));
expectCount(looks,'200|LAB_MOCK_READBACK_SIGNED',12);
for(const r of looks)verifyReceipt(r.receipt,lost);
assert.equal(count('request_nonces',lost.submission_reference),1);
assert.equal(count('metadata_receipts',lost.submission_reference),1);

console.log('F3-43 PASS: 77 signed concurrent HTTP requests across 4 PHP workers and disposable PG16; one first per key, nonce replays and wrong tenant/MAC denied; no live CRM ACK.');
