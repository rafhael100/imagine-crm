import test from 'node:test';
import assert from 'node:assert/strict';
import {GATES,assessReadiness} from './homologation-gate.mjs';
const sha='a'.repeat(40),uri='https://github.com/rafhael100/imagine-crm/actions/runs/1234';
function complete(){return {schema:'imagine.crm.operational-homologation-evidence.v1',environment:'private-imagine-homologation',revisions:{comercial:sha,portal:sha,crm:sha},gates:Object.fromEntries(GATES.map(k=>[k,{status:'verified',execution_id:'private-run-123',reviewer:'appsec-reviewer',evidence_url:uri}]))};}
test('missing evidence is always NO_GO',()=>{assert.equal(assessReadiness({}).status,'NO_GO');for(const k of GATES){const r=complete();r.gates[k].status='pending';assert.equal(assessReadiness(r).status,'NO_GO');}});
test('refuse lab, mutable revisions, untrusted links and extra gates',()=>{for(const edit of [r=>r.environment='github-actions-lab',r=>r.revisions.crm='latest',r=>r.gates.backup_restore.evidence_url='http://evil.test',r=>r.gates.extra={status:'verified'},r=>r.gates.tenant_isolation.reviewer='']){const r=complete();edit(r);assert.equal(assessReadiness(r).status,'NO_GO');}});
test('even complete self-asserted evidence requires independent review',()=>{assert.deepEqual(assessReadiness(complete()),{status:'EVIDENCE_COMPLETE_REVIEW_REQUIRED',failures:[],accepted:12,required:12});});
