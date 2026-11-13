/**
 * CRM F3-50: execute the REAL Imagine Comercial P0 preflight against fictional
 * inputs, then export ONLY the technical frame for existing Portal F119 ->
 * CRM F3-49 independent PostgreSQL/HTTP CI tests. No contact payload escapes.
 * This is never an intake, approval, durable receipt or production authority.
 */
import assert from 'node:assert/strict';
import {writeFileSync} from 'node:fs';
import {join} from 'node:path';
import {assessPrivateBudgetPreview} from '../../../comercial-source/src/lib/private-budget-preflight.mjs';

for (const [name,value] of Object.entries({
  GITHUB_ACTIONS:'true',
  RUNNER_ENVIRONMENT:'github-hosted',
  CRM_F342_CROSS_REPO_LAB:'1',
  CRM_F350_COMERCIAL_CI_LAB:'1'
})) {
  assert.equal(process.env[name],value,'F3-50 disposable CI gate: '+name);
}
const tmp=process.env.RUNNER_TEMP;
assert.ok(typeof tmp==='string' && tmp.startsWith('/home/runner/work/_temp'),
  'F3-50 only GitHub-hosted ephemeral runner storage');

const draft=Object.freeze({
  name:'Pessoa Inteiramente Fictícia',
  role:'Contato apenas demonstrativo',
  email:'review@example.invalid',
  phone:'',
  mobile:'+5511999999999',
  serviceSlug:'onepage',
  details:'Orçamento de laboratório, sem interessado ou dados de cliente.',
  challengePreview:'5',
  previewUnderstanding:true
});
const valid=assessPrivateBudgetPreview(draft);
assert.equal(valid.schema,'imagine.comercial.private-budget-preflight.v1');
assert.equal(valid.mode,'SYNTHETIC_REVIEW_ONLY');
assert.equal(valid.code,'STRUCTURAL_PREVIEW_ONLY_NO_AUTHORITY');
assert.equal(valid.structureMatches,true);
const invalid=assessPrivateBudgetPreview({...draft,email:'not-an-email'});
assert.equal(invalid.code,'INVALID_CONTACT_ROUTE');
const missingAcknowledgment=assessPrivateBudgetPreview({...draft,previewUnderstanding:false});
assert.equal(missingAcknowledgment.code,'PREVIEW_ACKNOWLEDGEMENT_REQUIRED');
const denyFlags=[
  'publicIntakeAllowed','consentProvenanceVerified','serverAntiBotVerified',
  'authenticatedTenantVerified','durableCrmReceiptVerified','canPersist',
  'canSendToPortal','canSendToCrm','canAcknowledgeVisitor',
  'canReplaceLegacyBudgetForm','canDeploy'
];
for(const output of [valid,invalid,missingAcknowledgment]){
  for(const flag of denyFlags) assert.equal(output[flag],false,'F3-50 NO-GO '+flag);
  const serialized=JSON.stringify(output);
  for(const privateValue of [draft.name,draft.email,draft.details,draft.mobile])
    assert.ok(!serialized.includes(privateValue),'No PII returned by real Comercial preflight');
}

// IDs are fabricated, and deliberately not derived from form fields or browser
// claims. A future operational binding must come from authenticated Portal/CRM.
const frame=Object.freeze({
  protocol:'imagine.lead-intake',
  schema_version:1,
  organization_reference:'11111111-1111-4111-8111-111111111111',
  binding_reference:'22222222-2222-4222-8222-222222222222',
  submission_reference:'f9f9f9f9-f9f9-4f9f-8f9f-f9f9f9f9f9f9',
  site_reference:'44444444-4444-4444-8444-444444444444',
  form_reference:'55555555-5555-4555-8555-555555555555',
  destination_reference:'66666666-6666-4666-8666-666666666666'
});
const claims=Object.fromEntries(denyFlags.map(key=>[key,false]));
const bridge={
  schema:'imagine.comercial.portal-crm-ci-frame.v1',
  provenance:'REAL_COMERCIAL_PREFLIGHT_SYNTHETIC_ONLY',
  preflight_code:valid.code,
  envelope:frame,
  claims
};
const json=JSON.stringify(bridge)+'\n';
assert.ok(Buffer.byteLength(json,'utf8')<2048,'F3-50 bounded technical JSON');
for(const privateValue of [draft.name,draft.email,draft.details,draft.mobile])
  assert.ok(!json.includes(privateValue),'F3-50 no form content in bridge');
// Create only; never follow or overwrite a pre-existing file in the runner.
const path=join(tmp,'crm-f350-comercial-envelope.json');
writeFileSync(path,json,{flag:'wx',mode:0o600});
console.log('CRM F3-50 PASS: real Comercial P0 preflight, 3 negative cases, metadata-only bridge; all operational gates remain denied.');
