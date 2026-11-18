// Real human CRM navigation; only the server speaks to the isolated Agents service.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { execFileSync } = require('node:child_process');

const ROOT = path.resolve(__dirname, '../..');
const BASE = 'http://127.0.0.1:18086';
const REPO = 'rafhael100/imagine-crm';
const UUID = /^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/;
const LEAD_TITLE = 'LEAD_TESTE_OWNER_BROWSER';
const READ = 'crm.rule.draft.read';
let phase = 'preflight';
let checks = 0;
let trusted = false;
let browser;
class ProbeFailure extends Error {}
function check(value, label) { checks++; if (!value) throw new ProbeFailure(label); }
function protectedFile(file) {
  const stat = fs.lstatSync(file);
  check(stat.isFile() && !stat.isSymbolicLink() && !(stat.mode & 0o077), 'PROTECTED_FILE');
}
function canonical(value) {
  if (Array.isArray(value)) return JSON.stringify(value.map(item => JSON.parse(canonical(item))));
  if (value && typeof value === 'object') {
    return '{' + Object.keys(value).sort().map(key => JSON.stringify(key) + ':' + canonical(value[key])).join(',') + '}';
  }
  return JSON.stringify(value);
}
function digest(value) { return crypto.createHash('sha256').update(canonical(value)).digest('hex'); }
function preflight() {
  check(process.argv.length === 4, 'VAULT_AND_OUTPUT_ARGUMENTS');
  check(process.env.GITHUB_ACTIONS === 'true' && process.env.GITHUB_REPOSITORY === REPO
    && process.env.GITHUB_EVENT_NAME === 'pull_request'
    && process.env.CRM_OWNER_DRAFT_RUNNER_ENVIRONMENT === 'github-hosted'
    && process.env.CRM_PRIVATE_PROJECT === 'imagine-crm-journey-ci', 'ACTUAL_DISPOSABLE_CI');
  check(!process.env.DOCKER_HOST && !process.env.DOCKER_CONTEXT
    && (!process.env.CRM_PRIVATE_TEST_PORT || process.env.CRM_PRIVATE_TEST_PORT === '18086'), 'LOCAL_FIXED_ENDPOINT');
  // The new helper verifies the actual PR base/head, checkout and image tag.
  // It reads GITHUB_EVENT_PATH and preserves the real GITHUB_SHA merge value.
  const result = execFileSync('python3', [path.join(ROOT, 'scripts/ops/private_crm_owner_drafts_ci.py'), 'preflight'],
    { cwd: ROOT, encoding: 'utf8', timeout: 60000, stdio: ['ignore', 'pipe', 'pipe'] }).trim();
  check(result === 'CRM_OWNER_DRAFT_PREFLIGHT_PASS', 'SOURCE_PREFLIGHT');
  protectedFile(process.env.CRM_PRIVATE_ENV_FILE);
  trusted = true;
}
function oracle(sql, agents = false) {
  check(trusted, 'PREFLIGHT_BEFORE_DATABASE');
  // ci.sql validates the protected context and exact provider pins, then uses
  // the job's compose override. SQL is stdin, never shell text or a log line.
  const script = "import sys; sys.path.insert(0,'scripts/ops'); import private_crm_owner_drafts_ci as ci; "
    + 'print(ci.sql(sys.stdin.read(), agents=' + (agents ? 'True' : 'False') + '))';
  return execFileSync('python3', ['-c', script], { cwd: ROOT, input: sql, encoding: 'utf8',
    timeout: 30000, maxBuffer: 1048576, stdio: ['pipe', 'pipe', 'pipe'] }).trim();
}
function nativeSnapshot() {
  const tables = { leads: 'id', tasks: 'id', automation_runs: 'id', preferences: 'organization_id' };
  const fields = Object.entries(tables).map(([name, key]) => "'" + name
    + "',(SELECT md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY t." + key
    + "),'[]'::jsonb)::text) FROM public.crm_private_" + name + ' t)');
  return canonical(JSON.parse(oracle('SELECT jsonb_build_object(' + fields.join(',') + ')::text')));
}
function remoteSnapshot(draft) {
  check(UUID.test(draft), 'REMOTE_REFERENCE');
  return JSON.parse(oracle("SELECT jsonb_build_object('drafts',(SELECT count(*) FROM aia_p67_owner.drafts),"
    + "'fingerprint',(SELECT md5(coalesce(jsonb_agg(to_jsonb(d) ORDER BY company_id,draft_ref),'[]'::jsonb)::text)"
    + " FROM aia_p67_owner.drafts d),'reads',(SELECT count(*) FROM aia_p67_owner.audit WHERE draft_ref='"
    + draft + "' AND operation='" + READ + "'))::text", true));
}
function storedIntent(draft) {
  check(UUID.test(draft), 'INTENT_REFERENCE');
  return JSON.parse(oracle("SELECT to_jsonb(i)::text FROM public.crm_private_owner_draft_intents i WHERE draft_ref='" + draft + "'"));
}
function storedRemote(draft) {
  check(UUID.test(draft), 'STORED_REMOTE_REFERENCE');
  return JSON.parse(oracle("SELECT to_jsonb(d)::text FROM aia_p67_owner.drafts d WHERE draft_ref='" + draft + "'", true));
}
async function headers(response, status) {
  check(response && response.status() === status, 'DOCUMENT_STATUS');
  const values = await response.allHeaders();
  const sniff = (values['x-content-type-options'] || '').split(',').map(value => value.trim());
  check(values['cache-control']?.includes('no-store') && values['content-type']?.startsWith('text/html')
    && sniff.length > 0 && sniff.every(value => value === 'nosniff')
    && !Object.keys(values).some(name => name.startsWith('access-control-')), 'PRIVATE_HTML_HEADERS');
}
async function login(page, account) {
  phase = 'password-and-recovery-mfa';
  await page.goto(BASE + '/login');
  await page.locator('form[action="/login"] [name=login]').fill(account.login);
  await page.locator('form[action="/login"] [name=password]').fill(account.password);
  await Promise.all([page.waitForURL(BASE + '/mfa'), page.locator('form[action="/login"] button').click()]);
  await page.locator('form[action="/mfa"] [name=code]').fill(account.recovery[4]);
  await page.locator('form[action="/mfa"] [name=recovery]').check();
  await Promise.all([page.waitForURL(BASE + '/painel'), page.locator('form[action="/mfa"] button').click()]);
  check(await page.locator('nav.sidebar').isVisible(), 'AUTHENTICATED_WORKSPACE');
}
async function dimensions(page) {
  const value = await page.evaluate(() => ({
    width: innerWidth, scroll: document.documentElement.scrollWidth,
    mainWidth: document.querySelector('main').getBoundingClientRect().width,
  }));
  check(value.scroll <= value.width + 1 && value.mainWidth <= value.width + 1, 'DRAFT_VIEWPORT_OVERFLOW');
}
async function pressLink(page, locator) {
  await locator.focus();
  check(await locator.evaluate(element => document.activeElement === element
    && getComputedStyle(element).outlineStyle !== 'none'), 'VISIBLE_KEYBOARD_FOCUS');
  return (await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]))[0];
}

(async () => {
  preflight(); // Before vault access, provider oracle, Playwright or Chromium.
  const vault = path.resolve(process.argv[2]);
  protectedFile(vault);
  check(fs.statSync(vault).size <= 65536, 'BOUNDED_VAULT');
  const fixtures = JSON.parse(fs.readFileSync(vault, 'utf8'));
  const account = fixtures.clientA;
  check(account && UUID.test(account.identity) && UUID.test(account.organization)
    && Array.isArray(account.recovery) && /^[a-f0-9]{24}$/.test(account.recovery[4])
    && ['login', 'password', 'secret'].every(name => typeof account[name] === 'string' && account[name].length > 0),
  'ORIGINAL_HUMAN_FIXTURES');
  const runnerTemp = fs.realpathSync(process.env.RUNNER_TEMP);
  const output = path.resolve(process.argv[3]);
  check(path.dirname(output) === runnerTemp && path.basename(output) === 'crm-owner-drafts-evidence'
    && output !== path.dirname(vault) && !output.startsWith(path.dirname(vault) + path.sep)
    && !fs.existsSync(output), 'NEW_SEPARATE_SCREENSHOT_DIRECTORY');
  fs.mkdirSync(output, { mode: 0o700 });
  const packageDir = path.resolve(process.env.CRM_PRIVATE_PLAYWRIGHT_DIR);
  check(path.dirname(packageDir) === runnerTemp && path.basename(packageDir) === 'crm-owner-drafts-browser'
    && !fs.lstatSync(packageDir).isSymbolicLink(), 'ISOLATED_PLAYWRIGHT_DIRECTORY');
  const packageData = JSON.parse(fs.readFileSync(path.join(packageDir, 'node_modules/playwright/package.json'), 'utf8'));
  check(packageData.version === '1.56.1', 'PINNED_PLAYWRIGHT_PACKAGE');
  const { chromium } = require(path.join(packageDir, 'node_modules/playwright'));

  const previousSessions = JSON.parse(oracle("SELECT coalesce(jsonb_agg(id),'[]'::jsonb)::text FROM public.crm_auth_sessions"
    + " WHERE identity_id='" + account.identity + "'"));
  check(Array.isArray(previousSessions) && previousSessions.every(id => UUID.test(id)), 'ORIGINAL_SESSION_ORACLE');
  const preference = JSON.parse(oracle("SELECT automation_rules::text FROM public.crm_private_preferences WHERE organization_id='"
    + account.organization + "'"));
  check(Number.isInteger(preference.version) && preference.version >= 1 && preference.version <= 20
    && preference.delay_seconds === 3600 && preference.rules.includes('followup_qualified'), 'ORIGINAL_RULE_SETTINGS');
  check(oracle("SELECT count(*) FROM public.crm_private_leads WHERE organization_id='" + account.organization
    + "' AND title='" + LEAD_TITLE + "'") === '0', 'FRESH_BROWSER_LEAD');

  browser = await chromium.launch({ executablePath: process.env.CRM_PRIVATE_CHROME || '/usr/bin/google-chrome', headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, locale: 'pt-BR',
    reducedMotion: 'reduce', serviceWorkers: 'block' });
  let blockedExternal = 0;
  let blockedData = 0;
  let unexpectedWrites = 0;
  const allowedWrites = new Set(['/login', '/mfa', '/leads']);
  await context.route('**/*', async route => {
    const request = route.request();
    const url = new URL(request.url());
    if (url.origin !== BASE) { blockedExternal++; await route.abort(); }
    else if (['fetch', 'xhr'].includes(request.resourceType())) { blockedData++; await route.abort(); }
    else if (request.method() !== 'GET' && !allowedWrites.has(url.pathname)) {
      unexpectedWrites++; await route.abort();
    } else await route.continue();
  });
  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  let pageErrors = 0;
  let directApiRequests = 0;
  let operationalRequests = 0;
  const ownerPosts = [];
  const ownerGets = [];
  const nativeWrites = [];
  page.on('pageerror', () => pageErrors++);
  page.on('request', request => {
    const url = new URL(request.url());
    if (/^\/agentes\/rascunhos\/[^/]+$/.test(url.pathname)) ownerGets.push(request);
    if (/^\/agentes\/planejamentos\/[^/]+\/rascunho$/.test(url.pathname)) ownerPosts.push(request);
    if (request.method() === 'POST' && !['/login', '/mfa'].includes(url.pathname)) nativeWrites.push(request);
    if (/^\/(api|private)(\/|$)/.test(url.pathname) || /\/minimo$/.test(url.pathname)) directApiRequests++;
    if (/^\/(automacoes|tarefas)(\/|$)/.test(url.pathname)) operationalRequests++;
  });
  await login(page, account);
  phase = 'native-lead-form';
  await pressLink(page, page.locator('nav.sidebar a[href="/painel?section=leads"]'));
  const leadForm = page.locator('form[action="/leads"]');
  check(await leadForm.count() === 1, 'NATIVE_LEAD_FORM');
  await leadForm.locator('[name=title]').fill(LEAD_TITLE);
  await leadForm.locator('[name=value_cents]').fill('0');
  const leadResponse = (await Promise.all([page.waitForNavigation(), leadForm.locator('button').click()]))[0];
  await headers(leadResponse, 200);
  const leadRow = page.locator('tr[data-lead-id]').filter({ has: page.getByText(LEAD_TITLE, { exact: true }) });
  check(await leadRow.count() === 1, 'NATIVE_LEAD_ROW');
  const lead = await leadRow.getAttribute('data-lead-id');
  check(UUID.test(lead), 'NATIVE_LEAD_REFERENCE');
  const initial = JSON.parse(oracle("SELECT to_jsonb(l)::text FROM public.crm_private_leads l WHERE id='" + lead + "'"));
  check(initial.organization_id === account.organization && initial.title === LEAD_TITLE
    && initial.stage === 'new' && initial.version === 1, 'NATIVE_LEAD_PERSISTED');

  phase = 'native-kanban-qualification';
  await pressLink(page, page.locator('nav.sidebar a[href="/painel?section=kanban"]'));
  const card = page.locator('article.opportunity[data-lead-id="' + lead + '"]');
  const advance = card.locator('form').filter({ has: page.locator('input[name=stage][value=qualified]') });
  check(await advance.count() === 1 && await advance.getAttribute('action') === '/leads/' + lead,
    'NATIVE_QUALIFICATION_FORM');
  allowedWrites.add('/leads/' + lead);
  await headers((await Promise.all([page.waitForNavigation(), advance.locator('button').click()]))[0], 200);
  const runs = JSON.parse(oracle("SELECT coalesce(jsonb_agg(to_jsonb(r)),'[]'::jsonb)::text"
    + " FROM public.crm_private_automation_runs r WHERE lead_id='" + lead + "' AND organization_id='"
    + account.organization + "'"));
  check(runs.length === 1, 'ONE_ORIGINAL_NATIVE_PLAN');
  const source = runs[0];
  check(UUID.test(source.id) && UUID.test(source.authority_session_id)
    && !previousSessions.includes(source.authority_session_id)
    && source.actor_id === account.identity && source.template === 'followup_qualified'
    && source.state === 'pending' && source.lead_version === 2
    && source.settings_version === preference.version && source.command.due_at === source.created_at + 3600,
  'CURRENT_BROWSER_SESSION_NATIVE_SOURCE');
  const nativeBefore = nativeSnapshot();

  phase = 'native-owner-draft-create';
  await pressLink(page, page.locator('nav.sidebar a[href="/painel?section=automation"]'));
  const createPath = '/agentes/planejamentos/' + source.id + '/rascunho';
  const createForm = page.locator('form[data-owner-draft-create="' + source.id + '"]');
  check(await createForm.count() === 1 && await createForm.getAttribute('action') === createPath
    && (await createForm.getAttribute('method')).toLowerCase() === 'post', 'OWNER_CREATE_FORM_BINDING');
  check(await createForm.evaluate(form => {
    const entries = [...new FormData(form).entries()];
    return entries.length === 1 && entries[0][0] === '_token' && /^[A-Za-z0-9]{40}$/.test(entries[0][1]);
  }), 'BROWSER_FORM_SUPPLIES_ONLY_CSRF');
  allowedWrites.add(createPath);
  const created = await Promise.all([
    page.waitForResponse(response => new URL(response.url()).pathname === createPath && response.request().method() === 'POST'),
    page.waitForNavigation(),
    createForm.locator('button').click(),
  ]);
  check(created[0].status() === 303, 'NATIVE_CREATE_303');
  await headers(created[1], 200);
  const draft = new URL(page.url()).pathname.split('/').at(-1);
  check(UUID.test(draft) && new URL(page.url()).pathname === '/agentes/rascunhos/' + draft, 'LOCAL_DRAFT_REDIRECT');
  const redirect = await created[0].headerValue('location');
  check(new URL(redirect, BASE).origin === BASE
    && new URL(redirect, BASE).pathname === '/agentes/rascunhos/' + draft, 'CREATE_LOCAL_REDIRECT_BINDING');
  check(ownerPosts.length === 1 && ownerPosts[0].isNavigationRequest()
    && ownerPosts[0].resourceType() === 'document' && ownerPosts[0].method() === 'POST'
    && /^_token=[A-Za-z0-9]{40}$/.test(ownerPosts[0].postData() || ''), 'NATIVE_OWNER_POST_CLOSED_BODY');
  const observedOrigin = (await ownerPosts[0].allHeaders()).origin;
  // CRM's existing privacy policy may serialize Origin as null. Observe it;
  // neither forge headers nor invent an Origin requirement for the product.
  const originCategory = observedOrigin === BASE ? 'same-origin'
    : observedOrigin === 'null' ? 'null' : observedOrigin === undefined ? 'absent' : 'other';
  check(nativeSnapshot() === nativeBefore, 'CREATE_CHANGED_NATIVE_LEADS_TASKS_RUNS_OR_PREFERENCES');
  const local = storedIntent(draft);
  const remote = storedRemote(draft);
  const business = local.business;
  check(local.state === 'confirmed' && remote.state === 'DRAFT'
    && remote.authorship_source === 'CRM_AUTHENTICATED_OWNER'
    && remote.creator_id === account.identity && local.creator_id === account.identity
    && business.creator_id === account.identity && business.company_id === account.organization
    && business.draft_ref === draft && business.source_run_id === source.id && business.lead_id === lead
    && business.rule_id === 'followup_qualified' && business.settings_version === preference.version
    && business.lead_version === source.lead_version && business.source_created_at === source.created_at
    && business.command_hash === digest(source.command)
    && business.projection.action === 'task.followup.create' && business.projection.stage === 'qualified'
    && business.projection.delay_seconds === 3600 && business.projection.due_at === source.command.due_at
    && canonical(remote.business) === canonical(business) && remote.business_hash === digest(business)
    && local.business_hash === remote.business_hash, 'PERSISTED_SOURCE_AUTHOR_AND_PROJECTION');
  const remoteImmutable = canonical(remote);
  const draftPath = '/agentes/rascunhos/' + draft;
  const copy = {
    pt: { link: 'PT-BR', html: 'pt-BR', heading: 'Rascunho de acompanhamento', confirmed: 'Rascunho confirmado',
      pending: 'Confirmação pendente', rule: 'Acompanhar lead qualificado' },
    en: { link: 'EN', html: 'en', heading: 'Follow-up draft', confirmed: 'Draft confirmed',
      pending: 'Confirmation pending', rule: 'Follow up a qualified lead' },
    es: { link: 'ES', html: 'es', heading: 'Borrador de seguimiento', confirmed: 'Borrador confirmado',
      pending: 'Confirmación pendiente', rule: 'Dar seguimiento a un lead calificado' },
  };
  const screenshotNames = [];
  let reads = 1; // The create redirect performs one real GET through the CRM.
  let confirmedReads = 1;
  let pendingReads = 0;
  async function navigateRead(action, status = 200) {
    const before = nativeSnapshot();
    const previous = remoteSnapshot(draft);
    const count = ownerGets.length;
    const response = await action();
    await headers(response, status);
    check(ownerGets.length === count + 1 && ownerGets.at(-1).method() === 'GET'
      && ownerGets.at(-1).isNavigationRequest() && ownerGets.at(-1).resourceType() === 'document',
    'ONE_NATIVE_DOCUMENT_READ');
    check(nativeSnapshot() === before, 'READ_CHANGED_NATIVE_LEADS_TASKS_RUNS_OR_PREFERENCES');
    const after = remoteSnapshot(draft);
    check(after.drafts === previous.drafts && after.fingerprint === previous.fingerprint
      && after.reads === previous.reads + (status === 200 ? 1 : 0), 'READ_CHANGED_DRAFT_OR_DUPLICATED_REMOTE_AUDIT');
    reads++;
    if (status === 200) confirmedReads++; else pendingReads++;
  }
  async function inspect(locale, state) {
    const panel = page.locator('[data-owner-draft-view="imagine"]');
    check(await panel.count() === 1 && await panel.isVisible()
      && await panel.getAttribute('data-owner-draft-state') === state
      && await panel.getAttribute('data-owner-draft-operational') === 'false'
      && await panel.getAttribute('data-owner-draft-executed') === 'false', 'HONEST_DRAFT_STATE');
    check(await page.locator('html').getAttribute('lang') === copy[locale].html
      && await panel.locator('h1').innerText() === copy[locale].heading
      && await panel.locator('.workspacehead > .tag').innerText() === copy[locale][state], 'LOCALIZED_DRAFT_STATE');
    check(await panel.locator('[data-owner-draft-ref]').count() === 1
      && await panel.locator('[data-owner-draft-ref]').getAttribute('data-owner-draft-ref') === draft
      && await panel.locator('[data-owner-draft-ref]').innerText() === draft, 'DISPLAYED_DRAFT_REFERENCE');
    const html = await page.content();
    for (const forbidden of [account.organization, account.login, account.password, account.secret,
      ...account.recovery, source.authority_session_id, business.command_hash, LEAD_TITLE,
      'CRM_AIA_OWNER_DRAFT_AUTH_V1', 'business_hash', 'accepted_authorization_hash']) {
      check(typeof forbidden === 'string' && forbidden.length > 0 && !html.includes(forbidden), 'UNRELATED_OR_SECRET_DATA_DISCLOSURE');
    }
    check(await panel.locator('input:not([type=hidden]),select,textarea').count() === 0, 'DRAFT_EDITABLE_PAYLOAD');
    if (state === 'confirmed') {
      for (const [name, value] of Object.entries({ lead, creator: account.identity, source: source.id,
        due: source.command.due_at, settings: preference.version })) {
        check(await panel.locator('[data-owner-draft-' + name + ']').getAttribute('data-owner-draft-' + name) === String(value),
          'DISPLAYED_BUSINESS_BINDING');
      }
      check(await panel.locator('[data-owner-draft-rule="followup_qualified"]').innerText() === copy[locale].rule
        && await panel.locator('dd').count() === 8 && await panel.locator('form,button').count() === 0
        && await panel.locator('[data-owner-draft-pending]').count() === 0, 'CONFIRMED_PROJECTION_ONLY');
    } else {
      check(await panel.locator('dd').count() === 1
        && await panel.locator('[data-owner-draft-pending][role=status]').count() === 1
        && await panel.locator('[data-owner-draft-creator],[data-owner-draft-source],[data-owner-draft-lead],'
          + '[data-owner-draft-rule],[data-owner-draft-due],[data-owner-draft-settings]').count() === 0
        && !html.includes(account.identity) && !html.includes(source.id) && !html.includes(lead), 'PENDING_HIDES_UNCONFIRMED_BUSINESS');
      const retry = panel.locator('form[data-owner-draft-retry="' + draft + '"]');
      check(await retry.count() === 1 && await retry.getAttribute('action') === draftPath + '/reenviar'
        && await retry.evaluate(form => {
          const entries = [...new FormData(form).entries()];
          return entries.length === 1 && entries[0][0] === '_token';
        }), 'PENDING_RETRY_IS_NATIVE_AND_CLOSED');
    }
    const back = new URL(await panel.locator('[data-owner-draft-back]').getAttribute('href'), BASE);
    const refresh = new URL(await panel.locator('[data-owner-draft-refresh]').getAttribute('href'), BASE);
    check(back.origin === BASE && back.pathname === '/painel'
      && back.searchParams.get('section') === 'automation' && back.searchParams.get('lang') === locale
      && [...back.searchParams.keys()].sort().join(',') === 'lang,section'
      && refresh.origin === BASE && refresh.pathname === draftPath && refresh.search === '?lang=' + locale,
    'LOCALIZED_LOCAL_NAVIGATION');
    check(await page.locator('.brand').evaluate(image => image.complete && image.naturalWidth > 0), 'BRAND_IMAGE');
    check(await page.locator('form[action="/logout"]').count() === 1, 'CURRENT_SESSION_LOGOUT');
  }
  async function layouts(locale, state) {
    for (const width of [320, 390, 1440]) {
      await page.setViewportSize({ width, height: width < 500 ? 844 : 1000 });
      await dimensions(page);
      if (locale === 'pt' && [390, 1440].includes(width)) {
        await inspect(locale, state);
        const name = 'crm-owner-draft-' + state + (width === 390 ? '-mobile.png' : '-desktop.png');
        await page.screenshot({ path: path.join(output, name), fullPage: true });
        screenshotNames.push(name);
      }
    }
  }
  await inspect('pt', 'confirmed');
  phase = 'keyboard-confirmed-actions';
  await page.locator('[data-owner-draft-refresh]').focus();
  await page.keyboard.press('Tab');
  check(await page.locator('[data-owner-draft-back]').evaluate(element => document.activeElement === element), 'CONFIRMED_KEYBOARD_ORDER');
  await page.keyboard.press('Shift+Tab');
  check(await page.locator('[data-owner-draft-refresh]').evaluate(element => document.activeElement === element), 'CONFIRMED_REVERSE_KEYBOARD_ORDER');
  await navigateRead(() => pressLink(page, page.locator('[data-owner-draft-refresh]')));
  for (const locale of ['pt', 'en', 'es']) {
    phase = 'confirmed-locale-' + locale;
    await navigateRead(() => pressLink(page, page.locator('.languages').getByRole('link', { name: copy[locale].link, exact: true })));
    check(new URL(page.url()).pathname === draftPath && new URL(page.url()).search === '?lang=' + locale, 'LANGUAGE_RESOURCE_BINDING');
    await inspect(locale, 'confirmed');
    await layouts(locale, 'confirmed');
  }
  phase = 'automation-to-persisted-draft';
  await headers(await pressLink(page, page.locator('[data-owner-draft-back]')), 200);
  const returnUrl = new URL(page.url());
  check(returnUrl.pathname === '/painel' && returnUrl.searchParams.get('section') === 'automation'
    && returnUrl.searchParams.get('lang') === 'es', 'RETURN_TO_AUTOMATIONS');
  const storedLink = page.locator('tr[data-automation-id="' + source.id + '"] [data-owner-draft-link="' + draft + '"]');
  check(await storedLink.count() === 1 && await storedLink.getAttribute('href') === draftPath
    && await page.locator('form[data-owner-draft-create="' + source.id + '"]').count() === 0, 'CENTRAL_PERSISTED_REFERENCE_LINK');
  await navigateRead(() => pressLink(page, storedLink));
  await inspect('es', 'confirmed');

  phase = 'revoked-remote-read-grant';
  const grants = JSON.parse(oracle("SELECT coalesce(jsonb_agg(to_jsonb(g)),'[]'::jsonb)::text"
    + " FROM aia_p67_owner.service_grants g WHERE company_id='" + account.organization + "' AND operation='" + READ + "'", true));
  check(grants.length === 1 && grants[0].active === true && Number.isInteger(grants[0].revision)
    && /^[a-f0-9]{64}$/.test(grants[0].fingerprint), 'ONE_CURRENT_REMOTE_READ_GRANT');
  const grant = grants[0];
  const grantWhere = "company_id='" + account.organization + "' AND operation='" + READ + "' AND fingerprint='" + grant.fingerprint + "'";
  check(oracle('UPDATE aia_p67_owner.service_grants SET active=false,revision=revision+1 WHERE '
    + grantWhere + ' AND active AND revision=' + grant.revision + ' RETURNING revision', true) === String(grant.revision + 1),
  'REVOKE_ONLY_FIXTURE_READ_GRANT');
  try {
    for (const locale of ['pt', 'en', 'es']) {
      phase = 'pending-locale-' + locale;
      await navigateRead(() => pressLink(page, page.locator('.languages').getByRole('link', { name: copy[locale].link, exact: true })), 503);
      await inspect(locale, 'pending');
      await layouts(locale, 'pending');
    }
    phase = 'pending-keyboard-actions';
    await page.locator('[data-owner-draft-refresh]').focus();
    await page.keyboard.press('Tab');
    check(await page.locator('[data-owner-draft-retry] button').evaluate(element => document.activeElement === element), 'PENDING_RETRY_KEYBOARD_ACCESS');
    await page.keyboard.press('Tab');
    check(await page.locator('[data-owner-draft-back]').evaluate(element => document.activeElement === element), 'PENDING_BACK_KEYBOARD_ACCESS');
    check(storedIntent(draft).state === 'confirmed' && canonical(storedRemote(draft)) === remoteImmutable,
      'UNAVAILABLE_READ_ERASED_HISTORY');
  } finally {
    check(oracle('UPDATE aia_p67_owner.service_grants SET active=true,revision=revision+1 WHERE '
      + grantWhere + ' AND NOT active AND revision=' + (grant.revision + 1) + ' RETURNING revision', true) === String(grant.revision + 2),
    'RESTORE_ONLY_FIXTURE_READ_GRANT');
  }
  phase = 'authorized-read-restored';
  await navigateRead(() => pressLink(page, page.locator('[data-owner-draft-refresh]')));
  await inspect('es', 'confirmed');
  check(canonical(storedRemote(draft)) === remoteImmutable, 'READ_RECREATED_OR_REWROTE_HISTORY');

  phase = 'same-company-member';
  check(oracle("UPDATE public.crm_private_accounts SET team_role='member' WHERE identity_id='" + account.identity
    + "' AND organization_id='" + account.organization + "' AND team_role='owner' RETURNING identity_id") === account.identity,
  'MEMBER_FIXTURE_ONLY');
  try {
    await headers(await pressLink(page, page.locator('[data-owner-draft-back]')), 200);
    check(await page.locator('[data-owner-draft-create],[data-owner-draft-link],[data-owner-draft-retry]').count() === 0,
      'MEMBER_DASHBOARD_EXPOSED_OWNER_ACTIONS');
    const previous = remoteSnapshot(draft);
    const denied = await page.goto(BASE + draftPath);
    check(denied && denied.status() === 403
      && await page.locator('[data-owner-draft-view],[data-owner-draft-ref],[data-owner-draft-creator]').count() === 0,
    'MEMBER_CURRENT_READ_NOT_DENIED');
    check(canonical(remoteSnapshot(draft)) === canonical(previous), 'MEMBER_REACHED_REMOTE_SERVICE');
  } finally {
    check(oracle("UPDATE public.crm_private_accounts SET team_role='owner' WHERE identity_id='" + account.identity
      + "' AND organization_id='" + account.organization + "' AND team_role='member' RETURNING identity_id") === account.identity,
    'RESTORE_OWNER_FIXTURE_ONLY');
  }
  phase = 'final-confirmed-control';
  await navigateRead(() => page.goto(BASE + draftPath + '?lang=pt'));
  await inspect('pt', 'confirmed');
  check(nativeSnapshot() === nativeBefore && canonical(storedRemote(draft)) === remoteImmutable
    && storedIntent(draft).state === 'confirmed', 'FINAL_BUSINESS_AND_AUTHOR_IMMUTABILITY');
  check(nativeWrites.length === 3 && ownerPosts.length === 1
    && nativeWrites.every(request => request.isNavigationRequest() && request.resourceType() === 'document')
    && ownerGets.every(request => request.method() === 'GET')
    && pageErrors === 0 && blockedExternal === 0 && blockedData === 0 && directApiRequests === 0
    && operationalRequests === 0 && unexpectedWrites === 0, 'UNEXPECTED_BROWSER_EFFECT_OR_DIRECT_SERVICE_ACCESS');
  check(screenshotNames.length === 4 && new Set(screenshotNames).size === 4
    && fs.readdirSync(output).sort().join(',') === screenshotNames.sort().join(','), 'SCREENSHOT_ONLY_EVIDENCE');
  console.log(JSON.stringify({
    schema: 'CRM_OWNER_DRAFT_CHROMIUM_VERIFIED_V1', checks, human_session_mfa: true,
    source_created_in_browser_session: true, native_business_posts: 3, native_owner_draft_posts: 1,
    owner_post_origin_observed: originCategory, document_reads: reads, confirmed_reads: confirmedReads,
    pending_reads: pendingReads, locales: 3, viewport_widths: [320, 390, 1440], screenshots: 4,
    keyboard_navigation: true, native_business_unchanged_by_draft_flow: true,
    historical_author_preserved: true, current_member_denied: true, remote_read_revocation_pending: true,
    browser_direct_agents_requests: 0, browser_json_api_requests: 0,
    browser_service_credentials: false, operational: false, executed: false,
  }));
  console.log('CRM_OWNER_DRAFT_CHROMIUM_PASS');
})().catch(error => {
  const reason = error instanceof ProbeFailure ? '; reason=' + error.message : '';
  console.error('CRM_OWNER_DRAFT_BROWSER_FAILED phase=' + phase + reason);
  process.exitCode = 1;
}).finally(async () => {
  if (browser) {
    try { await browser.close(); }
    catch { console.error('CRM_OWNER_DRAFT_BROWSER_CLOSE_FAILED'); process.exitCode = 1; }
  }
});
