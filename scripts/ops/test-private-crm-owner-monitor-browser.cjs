// A real human session navigates both installed opt-ins; no n8n execution.
'use strict';
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const ROOT = path.resolve(__dirname, '../..');
const BASE = 'http://127.0.0.1:18086';
const UUID = /^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/;
const SCRIPT = path.join(__dirname, 'test-private-crm-owner-monitor-coexistence.py');
let phase = 'preflight';
let checks = 0;
let browser;
let vault;
let fixtures;
let proof;
let output;
let violations = 0;
let pageErrors = 0;
let ownerReads = 0;
let monitorReads = 0;
const sessions = [];
class ProbeFailure extends Error {}
function check(value, label) { checks++; if (!value) throw new ProbeFailure(label); }

function protectedJson(filename, maximum) {
  const stat = fs.lstatSync(filename);
  check(stat.isFile() && !stat.isSymbolicLink() && !(stat.mode & 0o077) && stat.size <= maximum, 'PROTECTED_BOUNDED_JSON');
  return JSON.parse(fs.readFileSync(filename, 'utf8'));
}

function command(script, args) {
  try {
    return execFileSync('python3', [script, ...args], { cwd: ROOT, encoding: 'utf8', timeout: 90000,
      maxBuffer: 1048576, stdio: ['ignore', 'pipe', 'pipe'] }).trim();
  } catch (_) { throw new ProbeFailure('CLOSED_CI_HELPER_FAILED'); }
}

function preflight() {
  check(command(path.join(__dirname, 'private_crm_owner_drafts_ci.py'), ['preflight'])
    === 'CRM_OWNER_DRAFT_PREFLIGHT_PASS', 'ACTUAL_OWNER_CI_ADMISSION');
  check(process.env.CRM_N8N_EXECUTION_MONITOR === 'synthetic-private-v1'
    && process.env.CRM_PRIVATE_CI_MONITOR_PROFILE === 'execution-monitor-v1', 'MONITOR_CI_OPT_IN');
  check(process.argv.length === 4, 'FIXED_ARGUMENTS');
  vault = path.resolve(process.argv[2]);
  check(path.basename(vault) === 'fixtures.json', 'ORIGINAL_VAULT');
  const original = protectedJson(vault, 65536);
  fixtures = protectedJson(path.join(path.dirname(vault), 'execution-monitor-fixtures.json'), 262144);
  proof = protectedJson(path.join(path.dirname(vault), 'owner-monitor-coexistence.json'), 16384);
  check(proof.schema === 'CRM_OWNER_MONITOR_CI_V1' && proof.head === process.env.CRM_PRIVATE_IMAGE_TAG
    && proof.http_recovery_index === 2 && proof.browser_recovery_index === 3
    && ['company', 'actor', 'confirmed_ref', 'reconciled_ref', 'source_run', 'source_lead', 'main_event', 'foreign_event']
      .every(key => UUID.test(proof[key])), 'COMBINED_PROOF_BINDING');
  check(fixtures.monitor.synthetic_presentation_only === true && fixtures.monitor.n8n_executions_in_this_fixture === 0
    && JSON.stringify(original.clientA) === JSON.stringify(fixtures.clientA)
    && original.clientA.identity === proof.actor && original.clientA.organization === proof.company
    && fixtures.monitor.events.clientA[55].event_id === proof.main_event
    && fixtures.monitor.events.clientB[0].event_id === proof.foreign_event, 'ORIGINAL_IDENTITIES_AND_MONITOR_FIXTURES');
  for (const actor of ['clientA', 'clientB', 'member', 'admin']) {
    check(Array.isArray(fixtures[actor].recovery) && fixtures[actor].recovery.length === 5
      && /^[a-f0-9]{24}$/.test(fixtures[actor].recovery[3]), 'RESERVED_BROWSER_MFA');
  }
  const temporary = fs.realpathSync(process.env.RUNNER_TEMP);
  output = path.resolve(process.argv[3]);
  check(path.dirname(output) === temporary && path.basename(output) === 'crm-owner-monitor-evidence'
    && !fs.existsSync(output) && output !== path.dirname(vault), 'NEW_SANITIZED_SCREENSHOT_DIRECTORY');
  const playwrightDirectory = path.resolve(process.env.CRM_PRIVATE_PLAYWRIGHT_DIR);
  check(path.dirname(playwrightDirectory) === temporary && path.basename(playwrightDirectory) === 'crm-owner-drafts-browser'
    && !fs.lstatSync(playwrightDirectory).isSymbolicLink(), 'EXISTING_PINNED_PLAYWRIGHT_DIRECTORY');
  check(JSON.parse(fs.readFileSync(path.join(playwrightDirectory, 'node_modules/playwright/package.json'), 'utf8')).version
    === '1.56.1', 'PINNED_PLAYWRIGHT');
  return playwrightDirectory;
}

function snapshot() {
  const value = JSON.parse(command(SCRIPT, [vault, 'browser-snapshot']));
  check(value && typeof value.business === 'object' && Number.isSafeInteger(value.owner_read_count)
    && value.event_fingerprints && !Array.isArray(value.event_fingerprints)
    && Object.keys(value.event_fingerprints).length <= 2048
    && Object.entries(value.event_fingerprints).every(([id, digest]) => UUID.test(id) && /^[a-f0-9]{64}$/.test(digest))
    && Object.keys(value).sort().join(',') === 'business,event_fingerprints,owner_read_count', 'BOUNDED_ORACLE_SHAPE');
  return value;
}

function same(a, b) { return JSON.stringify(a) === JSON.stringify(b); }

function ownerAuditOnly(before, after) {
  const events = 'public.crm_private_events';
  const withoutEvents = value => Object.fromEntries(Object.entries(value.business).filter(([key]) => key !== events));
  check(same(withoutEvents(before), withoutEvents(after))
    && after.business[events].rows === before.business[events].rows + 1
    && after.owner_read_count === before.owner_read_count + 1
    && Object.keys(after.event_fingerprints).length === Object.keys(before.event_fingerprints).length + 1
    && Object.entries(before.event_fingerprints).every(([id, digest]) => after.event_fingerprints[id] === digest),
  'ONLY_EXPECTED_OWNER_READ_AUDIT');
}

async function headers(response, status) {
  check(response && response.status() === status, 'DOCUMENT_STATUS');
  const values = await response.allHeaders();
  check(values['cache-control']?.includes('no-store') && values['x-content-type-options'] === 'nosniff'
    && !Object.keys(values).some(key => key.startsWith('access-control-')), 'PRIVATE_RESPONSE_HEADERS');
}

async function createSession(chromiumBrowser) {
  const context = await chromiumBrowser.newContext({ viewport: { width: 1440, height: 1000 }, locale: 'pt-BR',
    reducedMotion: 'reduce', serviceWorkers: 'block' });
  await context.route('**/*', async route => {
    const request = route.request();
    const url = new URL(request.url());
    if (url.origin !== BASE || ['fetch', 'xhr'].includes(request.resourceType())
      || /^\/(api|private)(\/|$)/.test(url.pathname) || /\/minimo$/.test(url.pathname)
      || (request.method() !== 'GET' && !['/login', '/mfa'].includes(url.pathname))) {
      violations++; await route.abort();
    } else await route.continue();
  });
  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  page.on('pageerror', () => pageErrors++);
  page.on('request', request => {
    const url = new URL(request.url());
    if (request.isNavigationRequest() && /^\/agentes\/rascunhos\/[^/]+$/.test(url.pathname)) ownerReads++;
    if (request.isNavigationRequest() && url.pathname === '/painel' && url.searchParams.get('section') === 'n8n') monitorReads++;
  });
  sessions.push(context);
  return page;
}

async function login(page, actor) {
  phase = 'real-password-and-reserved-mfa-' + actor;
  const account = fixtures[actor];
  await headers(await page.goto(BASE + '/login'), 200);
  await page.locator('form[action="/login"] [name=login]').fill(account.login);
  await page.locator('form[action="/login"] [name=password]').fill(account.password);
  await Promise.all([page.waitForURL('**/mfa'), page.locator('form[action="/login"] button').click()]);
  check(await page.locator('form[action="/mfa"] [name=code]').isVisible(), 'REAL_MFA_CHALLENGE');
  // Owner HTTP/recovery/Chromium already consumed 0/1/4; Python35 consumes 2.
  // This is the last untouched original code, not a rotated or reseeded factor.
  await page.locator('form[action="/mfa"] [name=code]').fill(account.recovery[3]);
  await page.locator('form[action="/mfa"] [name=recovery]').check();
  await Promise.all([page.waitForURL(actor === 'admin' ? '**/admin' : '**/painel'),
    page.locator('form[action="/mfa"] button').click()]);
  check(await page.locator('h1').isVisible(), 'AUTHENTICATED_DOCUMENT');
}

async function pressLink(page, locator) {
  check(await locator.count() === 1, 'UNIQUE_REAL_LINK');
  const href = await locator.getAttribute('href');
  check(new URL(href, BASE).origin === BASE, 'LOCAL_DOCUMENT_LINK');
  const [response] = await Promise.all([page.waitForNavigation(), locator.click()]);
  await headers(response, 200);
}

async function safe(page) {
  const html = await page.content();
  for (const actor of ['clientA', 'clientB', 'member', 'clientEmpty', 'admin']) {
    for (const secret of [fixtures[actor].password, fixtures[actor].secret, ...fixtures[actor].recovery]) {
      check(!html.includes(secret), 'NO_CREDENTIAL_DISCLOSURE');
    }
  }
  check(!['SQLSTATE', 'crm_auto_service', 'payload_hash', 'receipt_hash', 'authority_session_id',
    'AUTO_IA_EXECUTION_STATUS_V1', 'SYNTHETIC_EXECUTION_STATUS_KEY'].some(value => html.includes(value)),
  'NO_SERVICE_OR_DATABASE_DISCLOSURE');
  check(await page.locator('[data-execution-monitor] form,[data-execution-monitor] script').count() === 0,
    'MONITOR_HAS_NO_MUTATION_OR_SCRIPT');
}

async function dimensions(page, width) {
  await page.setViewportSize({ width, height: width === 1440 ? 1000 : 844 });
  const geometry = await page.evaluate(() => ({ document: document.documentElement.scrollWidth, viewport: innerWidth }));
  check(geometry.document <= geometry.viewport + 1, 'NO_PAGE_OVERFLOW_' + width);
}

async function screenshot(page, name, width) {
  await dimensions(page, width);
  await safe(page);
  await page.screenshot({ path: path.join(output, name), fullPage: true });
}

function queryIs(value, locale, event = null) {
  const url = new URL(value, BASE);
  return url.origin === BASE && url.pathname === '/painel' && url.searchParams.get('section') === 'n8n'
    && url.searchParams.get('lang') === locale && url.searchParams.get('event') === event
    && [...url.searchParams.keys()].sort().join(',') === (event ? 'event,lang,section' : 'lang,section');
}

const copy = {
  pt: { link: 'PT-BR', title: 'Cada retorno, no seu contexto.', confirmed: 'Confirmação registrada' },
  en: { link: 'EN', title: 'Every response, in context.', confirmed: 'Confirmation recorded' },
  es: { link: 'ES', title: 'Cada respuesta, en su contexto.', confirmed: 'Confirmación registrada' },
};

(async () => {
  const playwrightDirectory = preflight();
  fs.mkdirSync(output, { mode: 0o700 });
  const { chromium } = require(path.join(playwrightDirectory, 'node_modules/playwright'));
  browser = await chromium.launch({ executablePath: process.env.CRM_PRIVATE_CHROME || '/usr/bin/google-chrome', headless: true });
  const page = await createSession(browser);
  await page.goto(BASE + '/painel?section=n8n');
  check(new URL(page.url()).pathname === '/login' && await page.locator('[data-execution-monitor]').count() === 0,
    'NO_ANONYMOUS_MONITOR');
  await login(page, 'clientA');
  phase = 'both-features-in-one-authorized-owner-session';
  await pressLink(page, page.locator('nav.sidebar a[href="/painel?section=automation"]'));
  check(await page.locator('nav.sidebar a[href="/painel?section=n8n"]').count() === 1, 'MONITOR_NAV_WITH_OWNER');
  const beforeOwner = snapshot();
  await pressLink(page, page.locator('[data-owner-draft-link="' + proof.confirmed_ref + '"]'));
  const panel = page.locator('[data-owner-draft-view="imagine"]');
  check(await panel.count() === 1 && await panel.getAttribute('data-owner-draft-state') === 'confirmed'
    && await panel.getAttribute('data-owner-draft-operational') === 'false'
    && await panel.getAttribute('data-owner-draft-executed') === 'false', 'REAL_CONFIRMED_INERT_OWNER_DOCUMENT');
  check(await panel.locator('[data-owner-draft-creator]').getAttribute('data-owner-draft-creator') === proof.actor
    && await panel.locator('[data-owner-draft-source]').getAttribute('data-owner-draft-source') === proof.source_run
    && await panel.locator('[data-owner-draft-lead]').getAttribute('data-owner-draft-lead') === proof.source_lead,
  'EXACT_OWNER_AUTHORSHIP_AND_NATIVE_SOURCE');
  ownerAuditOnly(beforeOwner, snapshot());
  await screenshot(page, 'crm-owner-monitor-owner-desktop.png', 1440);
  await pressLink(page, page.locator('[data-owner-draft-back]'));

  phase = 'separate-immutable-monitor-window';
  const beforeMonitor = snapshot();
  await pressLink(page, page.locator('nav.sidebar a[href="/painel?section=n8n"]'));
  for (const [index, locale] of ['pt', 'en', 'es'].entries()) {
    await pressLink(page, page.locator('.languages a').filter({ hasText: copy[locale].link }));
    check(queryIs(page.url(), locale) && await page.locator('[data-execution-monitor]').getAttribute('data-execution-monitor') === locale,
      'LOCALIZED_CLOSED_MONITOR_LIST');
    check((await page.locator('.execution-toolbar h2').textContent()).trim() === copy[locale].title
      && await page.locator('[data-execution-event]').count() === 50
      && await page.locator('[data-execution-event="' + proof.foreign_event + '"]').count() === 0,
    'LIST_TITLE_WINDOW_AND_TENANT');
    await pressLink(page, page.locator('[data-execution-open="' + proof.main_event + '"]'));
    check(queryIs(page.url(), locale, proof.main_event), 'CLOSED_DETAIL_QUERY');
    check(await page.locator('[data-execution-selected="' + proof.main_event + '"]').count() === 1
      && (await page.locator('.execution-detail [data-execution-state="ACKED"]').textContent()).trim() === copy[locale].confirmed
      && same(await page.locator('[data-execution-sequence]').evaluateAll(rows => rows.map(row => Number(row.dataset.executionSequence))),
        [6, 5, 4, 3, 2, 1]), 'PERSISTED_HISTORY_AND_LOCALIZED_STATE');
    const next = ['en', 'es', 'pt'][index];
    await pressLink(page, page.locator('.languages a').filter({ hasText: copy[next].link }));
    check(queryIs(page.url(), next, proof.main_event)
      && await page.locator('[data-execution-selected="' + proof.main_event + '"]').count() === 1,
    'LANGUAGE_CLICK_PRESERVES_AUTHORIZED_EVENT');
    await dimensions(page, 320); await dimensions(page, 390); await safe(page);
    if (next === 'en') await screenshot(page, 'crm-owner-monitor-history-en-desktop.png', 1440);
    if (next === 'es') await screenshot(page, 'crm-owner-monitor-history-es-mobile.png', 390);
    await pressLink(page, page.locator('.execution-back'));
    check(queryIs(page.url(), next) && await page.locator('[data-execution-selected]').count() === 0,
      'BACK_LINK_DROPS_EVENT');
  }
  check(same(snapshot(), beforeMonitor), 'MONITOR_WINDOW_ALL_PRIVATE_BUSINESS_AND_S2S_UNCHANGED');

  for (const actor of ['member', 'clientB', 'admin']) {
    const other = await createSession(browser);
    await login(other, actor);
    phase = 'combined-permission-boundary-' + actor;
    const before = snapshot();
    await headers(await other.goto(BASE + '/agentes/rascunhos/' + proof.confirmed_ref), actor === 'clientB' ? 404 : 403);
    check(await other.locator('[data-owner-draft-view]').count() === 0, 'OWNER_DOCUMENT_DENIED');
    await headers(await other.goto(BASE + '/painel?section=n8n&lang=pt'), actor === 'admin' ? 403 : 200);
    if (actor === 'admin') {
      check(await other.locator('[data-execution-monitor]').count() === 0, 'ADMIN_MONITOR_DENIED');
    } else {
      check(await other.locator('[data-execution-event]').count() === (actor === 'member' ? 50 : 1), 'MEMBER_OR_PEER_MONITOR_SCOPE');
      const foreign = actor === 'member' ? proof.foreign_event : proof.main_event;
      await headers(await other.goto(BASE + '/painel?section=n8n&event=' + foreign + '&lang=pt'), 404);
      check(await other.locator('[data-execution-monitor]').count() === 0, 'FOREIGN_HISTORY_DENIED');
    }
    await safe(other);
    check(same(snapshot(), before), 'DENIAL_AND_MONITOR_WINDOW_UNCHANGED');
  }
  check(violations === 0 && pageErrors === 0 && ownerReads >= 4 && monitorReads > 10,
    'LOCAL_DOCUMENT_NAVIGATION_ONLY');
  console.log(JSON.stringify({ check: 'CRM_OWNER_MONITOR_BROWSER_PASS_NO_GO', checks,
    head: proof.head, real_password_mfa: true, reserved_recovery_index: 3,
    same_owner_session_read_both: true, owner_audit_separate_from_monitor_window: true,
    monitor_window_business_and_s2s_immutable: true, locales: ['pt', 'en', 'es'], widths: [320, 390, 1440],
    owner_member_peer_admin_boundaries: true, credential_free_screenshots: 3,
    direct_service_or_api_requests: 0, new_n8n_executions: 0, synthetic_monitor_presentation_only: true,
    browser_domain_mutations: 0, operational: false }));
})().catch(error => {
  const reason = error instanceof ProbeFailure && /^[A-Z0-9_]{1,160}$/.test(error.message)
    ? error.message : 'SANITIZED_BROWSER_FAILURE';
  console.error(JSON.stringify({ check: 'CRM_OWNER_MONITOR_BROWSER_DENIED', phase, reason }));
  process.exitCode = 2;
}).finally(async () => {
  for (const context of sessions) await context.close().catch(() => {});
  if (browser) await browser.close().catch(() => {});
});
