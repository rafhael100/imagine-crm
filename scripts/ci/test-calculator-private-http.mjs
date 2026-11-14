import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {createServer} from 'node:net';
import {setTimeout as delay} from 'node:timers/promises';
import {createHmac} from 'node:crypto';

const router='scripts/ci/calculator-private-http-router.php';
const publicFixtureKey='IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
const bytes=payload=>[
  'imagine.comercial.calculator.synthetic-interest.v1',
  payload.schema,payload.source,payload.submission_id,
  payload.channel_ids.join(','),payload.currency,
  String(payload.media_total_cents),
  payload.report_requested?'1':'0',
  payload.marketing_requested?'1':'0',
  payload.notice_version,
  String(payload.issued_at),String(payload.expires_at),payload.nonce
].join('\n');
const pgMode=process.env.CRM_CALCULATOR_HTTP_PG_CI==='synthetic-pg';
const payload={
  schema:'imagine.calculator.synthetic-interest.v1',
  source:'imagine_comercial_calculadora_performance',
  submission_id:pgMode?'99999999-9999-4999-8999-999999999999':'123e4567-e89b-42d3-a456-426614174000',
  channel_ids:['chatgpt_ads','google_ads','meta_ads'],
  currency:'BRL',media_total_cents:430000,
  report_requested:true,marketing_requested:false,notice_version:'lab-notice-v1',
  issued_at:1794672000,expires_at:1794672300,
  nonce:pgMode?'88888888-8888-4888-8888-888888888888':'f2e67d4e-39c1-43ba-a304-7db5477a2e66'
};
const sign=body=>({
  payload:body,
  signature:createHmac('sha256',publicFixtureKey).update(bytes(body)).digest('hex')
});

async function freeLoopbackPort(){
  return new Promise((resolve,reject)=>{
    const socket=createServer();
    socket.on('error',reject);
    socket.listen(0,'127.0.0.1',()=>{
      const port=socket.address().port;
      socket.close(err=>err?reject(err):resolve(port));
    });
  });
}
async function main(){
  if(process.env.CI!=='true'||process.env.GITHUB_ACTIONS!=='true'||
     process.env.GITHUB_REPOSITORY!=='rafhael100/imagine-crm')
    throw Error('This HTTP smoke is reserved for disposable GitHub Actions.');
  const port=await freeLoopbackPort();
  const origin='http://127.0.0.1:'+port;
  const php=spawn('php',['-S','127.0.0.1:'+port,router],{
    env:{...process.env,CRM_CALCULATOR_HTTP_CI:'synthetic-loopback'},
    stdio:['ignore','ignore','pipe']
  });
  let runtimeLogs='';
  php.stderr.on('data',b=>{runtimeLogs+=b.toString().slice(0,1000)});
  try {
    let ready=false;
    for(let i=0;i<55;i++){
      if(php.exitCode!==null)throw Error('php -S exited early');
      try {
        const health=await fetch(origin+'/_lab/health',{signal:AbortSignal.timeout(600)});
        if(health.status===200){
          const answer=await health.json();
          assert.equal(answer.state,'SYNTHETIC_LOOPBACK_READY_NO_RECEIVER');
          ready=true;break;
        }
      }catch{}
      await delay(100);
    }
    assert.equal(ready,true,'private test web server must be ready');

    async function send(path,body,opts={}){
      return fetch(origin+path,{
        method:opts.method??'POST',
        headers:opts.headers??{'Content-Type':'application/json'},
        body:opts.method==='GET'?undefined:JSON.stringify(body),
        redirect:'manual',
        signal:AbortSignal.timeout(5000)
      });
    }
    const ok=await send('/_lab/calculator-interest',sign(payload));
    assert.equal(ok.status,pgMode?201:202,'only synthetic HTTP receipt is accepted');
    assert.equal(ok.headers.get('cache-control'),'no-store');
    assert.equal(ok.headers.get('access-control-allow-origin'),null,'no CORS grant');
    const receipt=await ok.json();
    assert.equal(receipt.state,pgMode?'SYNTHETIC_PG_INTENT_RECORDED':'SYNTHETIC_HTTP_REVIEW_ONLY_NO_RECEIPT');
    for(const flag of ['consent_proven','tenant_authorized','crm_durable_receipt_proven',
      'can_store_pii','can_send_report','can_send_marketing','can_charge']){
      assert.equal(receipt[flag],false,flag+' must be false');
    }
    assert.equal(Object.hasOwn(receipt,'email'),false);
    assert.equal(Object.hasOwn(receipt,'name'),false);
    assert.equal((await send('/_lab/calculator-interest',sign(payload))).status,pgMode?200:202,
      'replay may be recognized only for synthetic metadata, never a real CRM receipt');
    if(pgMode){
      const changed={...payload,media_total_cents:500000};
      assert.equal((await send('/_lab/calculator-interest',sign(changed))).status,409,
        'validly signed change of media budget must conflict with immutable submission');
      const reused={...payload,submission_id:'77777777-7777-4777-8777-777777777777'};
      assert.equal((await send('/_lab/calculator-interest',sign(reused))).status,409,
        'nonce cannot be used with a different submission in the same tenant');
    }

    const tampered=sign(payload);
    tampered.payload={...payload,marketing_requested:true};
    assert.equal((await send('/_lab/calculator-interest',tampered)).status,403,
      'marketing flag mutation must fail');
    assert.equal((await send('/_lab/calculator-interest',sign({...payload,channel_ids:['bad']}))).status,403);
    assert.equal((await send('/_lab/calculator-interest',{
      ...sign(payload),email:'unapproved@example.test'
    })).status,403,'unexpected PII must not be accepted');

    const expired=sign({...payload,expires_at:1794672029});
    assert.equal((await send('/_lab/calculator-interest',expired)).status,403);
    const unknown=await send('/_lab/outside',sign(payload));
    assert.equal(unknown.status,404);
    const get=await send('/_lab/calculator-interest',undefined,{method:'GET'});
    assert.equal(get.status,405);
    assert.equal(get.headers.get('allow'),'POST');
    assert.equal((await send('/_lab/calculator-interest',sign(payload),{
      headers:{'Content-Type':'text/plain'}
    })).status,415);

    const huge={...sign(payload),padding:'X'.repeat(4300)};
    assert.equal((await send('/_lab/calculator-interest',huge)).status,415);
    assert.equal((await send('/_lab/calculator-interest',{foo:'<script>alert(1)</script>'})).status,403);
    console.log(pgMode ?
      'PASS: localhost-only HTTP to ephemeral PG16, first insert 201, replay 200, budget/nonce conflict 409, tamper 403, NO real CRM/PII/email/marketing' :
      'PASS: localhost-only synthetic HTTP signed intent 202; tamper 403, no CORS/PII/CRM receipt/email, size/method protection');
  } finally {
    php.kill('SIGTERM');
    await delay(150);
    if(php.exitCode===null)php.kill('SIGKILL');
  }
}
await main();
