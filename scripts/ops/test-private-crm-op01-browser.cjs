// Real Chromium coverage of the CRM-owned minimal projection, disposable CI only.
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const ROOT = path.resolve(__dirname, '../..');
const REPO = 'rafhael100/imagine-crm';
const BASE = 'http://127.0.0.1:18086';
const UUID = /^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/;
let phase = 'preflight';
let checks = 0;
let trusted = false;
let browser;
class ProbeFailure extends Error {}
function check(value, label) { checks++; if (!value) throw new ProbeFailure(label); }
function protectedFile(file) {
  const stat = fs.lstatSync(file);
  check(stat.isFile() && !stat.isSymbolicLink() && !(stat.mode & 0o077), 'protected file required');
}
function preflight() {
  check(process.argv.length === 4, 'vault and screenshot directory required');
  check(process.env.GITHUB_ACTIONS === 'true' && process.env.GITHUB_REPOSITORY === REPO
    && process.env.GITHUB_EVENT_NAME === 'pull_request'
    && process.env.CRM_OP01_RUNNER_ENVIRONMENT === 'github-hosted'
    && process.env.CRM_PRIVATE_PROJECT === 'imagine-crm-journey-ci', 'disposable CI required');
  check(!process.env.CRM_PRIVATE_TEST_PORT || process.env.CRM_PRIVATE_TEST_PORT === '18086', 'fixed loopback port required');
  const eventPath = process.env.GITHUB_EVENT_PATH;
  const eventStat = fs.lstatSync(eventPath);
  check(eventStat.isFile() && !eventStat.isSymbolicLink() && eventStat.size <= 1048576, 'actual GitHub event required');
  const event = JSON.parse(fs.readFileSync(eventPath, 'utf8'));
  const head = event.pull_request?.head;
  const tag = process.env.CRM_PRIVATE_IMAGE_TAG || '';
  const checkout = execFileSync('git', ['-C', ROOT, 'rev-parse', 'HEAD'], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
  check(/^[a-f0-9]{40}$/.test(tag) && tag === checkout && head?.sha === tag
    && head?.repo?.full_name === REPO && event.repository?.full_name === REPO, 'candidate head and image mismatch');
  // The real GITHUB_SHA is deliberately preserved, including PR merge SHAs.
  protectedFile(process.env.CRM_PRIVATE_ENV_FILE);
  trusted = true;
}
function ownerScalar(sql) {
  check(trusted, 'preflight required before Docker');
  return execFileSync('docker', ['compose', '--env-file', process.env.CRM_PRIVATE_ENV_FILE,
    '-p', 'imagine-crm-journey-ci', '-f', path.join(ROOT, 'infra/homologation/compose.yaml'),
    'exec', '-T', 'postgres', 'psql', '-X', '-qAt', '-v', 'ON_ERROR_STOP=1',
    '-U', 'crm_homologation_owner', '-d', 'imagine_crm_private_homologation', '-c', sql],
    { encoding: 'utf8', timeout: 15000, stdio: ['ignore', 'pipe', 'pipe'] }).trim();
}
async function login(page, account) {
  phase = 'password-and-recovery-mfa';
  await page.goto(BASE + '/login');
  await page.locator('[name=login]').fill(account.login);
  await page.locator('[name=password]').fill(account.password);
  await Promise.all([page.waitForURL(BASE + '/mfa'), page.locator('form[action="/login"] button').click()]);
  await page.locator('[name=code]').fill(account.recovery[0]);
  await page.locator('[name=recovery]').check();
  await Promise.all([page.waitForURL(BASE + '/painel'), page.locator('form[action="/mfa"] button').click()]);
  check(await page.locator('h1').isVisible(), 'authenticated workspace missing');
}
async function width(page) {
  const dimensions = await page.evaluate(() => [document.documentElement.scrollWidth, innerWidth]);
  check(dimensions[0] <= dimensions[1] + 1, 'summary overflows viewport');
}

(async () => {
  preflight(); // Before vault access, Playwright loading or launching Chromium.
  const vault = path.resolve(process.argv[2]);
  protectedFile(vault);
  check(fs.statSync(vault).size <= 65536, 'bounded fixture vault required');
  const fixtures = JSON.parse(fs.readFileSync(vault, 'utf8'));
  const account = fixtures.clientA;
  check(account && fixtures.clientB && UUID.test(account.identity) && UUID.test(account.organization)
    && UUID.test(fixtures.clientB.organization) && Array.isArray(account.recovery)
    && /^[a-f0-9]{24}$/.test(account.recovery[0]), 'original HTTP fixtures required');
  const output = path.resolve(process.argv[3]);
  const vaultDirectory = path.dirname(vault);
  check(output !== vaultDirectory && !output.startsWith(vaultDirectory + path.sep)
    && !vaultDirectory.startsWith(output + path.sep), 'screenshots must be separate from fixture storage');
  fs.mkdirSync(output, { recursive: true, mode: 0o700 });
  check(!fs.lstatSync(output).isSymbolicLink(), 'screenshot directory must not be symbolic');
  const { chromium } = require(path.join(process.env.CRM_PRIVATE_PLAYWRIGHT_DIR, 'node_modules/playwright'));

  // Independent fixture oracle: one UUID only, not obtained from a denied response.
  // B's credential version changed during the HTTP matrix, so old recovery codes
  // are intentionally not reused to authenticate another browser session.
  const foreign = ownerScalar("SELECT id FROM public.crm_private_leads WHERE organization_id='"
    + fixtures.clientB.organization + "' AND title='LEAD_TESTE_OP01_B'");
  check(UUID.test(foreign), 'foreign fixture lead missing');
  browser = await chromium.launch({ executablePath: process.env.CRM_PRIVATE_CHROME || '/usr/bin/google-chrome', headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, locale: 'pt-BR', reducedMotion: 'reduce' });
  let blockedExternal = 0;
  await context.route('**/*', async route => {
    if (new URL(route.request().url()).origin !== BASE) { blockedExternal++; await route.abort(); }
    else await route.continue();
  });
  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  let pageErrors = 0;
  let jsonRequests = 0;
  let unexpectedWrites = 0;
  let automationRequests = 0;
  const summaryRequests = [];
  const summaryResponses = [];
  page.on('pageerror', () => pageErrors++);
  page.on('request', request => {
    const url = new URL(request.url());
    if (/^\/agentes\/leads\/[^/]+\/resumo$/.test(url.pathname)) summaryRequests.push(request.method());
    if (/^\/agentes\/leads\/[^/]+\/minimo$/.test(url.pathname)) jsonRequests++;
    if (request.method() !== 'GET' && !['/login', '/mfa', '/logout'].includes(url.pathname)) unexpectedWrites++;
    if (/^\/(automacoes|tarefas)(\/|$)/.test(url.pathname)) automationRequests++;
  });
  page.on('response', response => {
    if (/^\/agentes\/leads\/[^/]+\/resumo$/.test(new URL(response.url()).pathname)) summaryResponses.push(response.status());
  });
  await login(page, account);
  phase = 'lead-row-link';
  await page.goto(BASE + '/painel?section=leads&lang=pt');
  const row = page.locator('tr[data-lead-id]').filter({ has: page.getByText('LEAD_TESTE_OP01_A', { exact: true }) });
  check(await row.count() === 1, 'original won lead row missing');
  const lead = await row.getAttribute('data-lead-id');
  check(UUID.test(lead) && lead !== foreign, 'independent lead selection invalid');
  const summaryPath = '/agentes/leads/' + lead + '/resumo';
  const link = row.locator('[data-minimal-lead-link]');
  check(await link.count() === 1 && await link.getAttribute('data-minimal-lead-link') === lead
    && await link.getAttribute('href') === summaryPath, 'summary link is not bound to its row');
  const auditCount = () => Number(ownerScalar("SELECT count(*) FROM public.crm_private_events WHERE action='lead.minimal_read'"
    + " AND object_id='" + lead + "' AND actor_id='" + account.identity + "' AND organization_id='" + account.organization + "'"));
  let navigations = 0;
  async function singleRead(action) {
    const requests = summaryRequests.length;
    const audits = auditCount();
    const response = await action();
    check(response && response.status() === 200, 'summary navigation failed');
    const headers = response.headers();
    check(headers['cache-control']?.includes('no-store') && headers['x-content-type-options'] === 'nosniff'
      && headers['content-type']?.startsWith('text/html')
      && !Object.keys(headers).some(key => key.startsWith('access-control-')), 'summary privacy headers missing');
    check(summaryRequests.length === requests + 1 && summaryRequests.at(-1) === 'GET'
      && jsonRequests === 0, 'summary performs duplicate or JSON fetches');
    check(auditCount() === audits + 1, 'one summary navigation must audit exactly once');
    navigations++;
  }
  const copy = {
    pt: { link: 'PT-BR', heading: 'Resumo mínimo do lead', stage: 'Ganho', html: 'pt-BR' },
    en: { link: 'EN', heading: 'Minimal lead summary', stage: 'Won', html: 'en' },
    es: { link: 'ES', heading: 'Resumen mínimo del lead', stage: 'Ganado', html: 'es' },
  };
  async function inspect(locale) {
    const panel = page.locator('[data-minimal-lead-summary="imagine"]');
    check(await panel.count() === 1 && await panel.isVisible(), 'minimal summary missing');
    check(await page.locator('html').getAttribute('lang') === copy[locale].html
      && await panel.locator('[data-minimal-lead-heading]').innerText() === copy[locale].heading, 'summary locale missing');
    check(await panel.locator('[data-minimal-lead-ref]').count() === 1
      && await panel.locator('[data-minimal-lead-ref]').getAttribute('data-minimal-lead-ref') === lead
      && await panel.locator('[data-minimal-lead-ref]').innerText() === lead, 'summary reference differs from selected row');
    check(await panel.locator('[data-minimal-lead-stage="won"]').innerText() === copy[locale].stage
      && await panel.locator('dd').count() === 2, 'minimum stage projection missing');
    check(await panel.locator('form,input,select,textarea,button').count() === 0
      && await page.locator('form').count() === 1
      && await page.locator('form[action="/logout"]').count() === 1, 'summary exposes an operational form');
    const html = await page.content();
    for (const forbidden of ['LEAD_TESTE_', 'FUNIL_TESTE_', 'EMPRESA_TESTE_', 'value_cents', 'funnel_id', 'organization_id', 'identity_id',
      account.identity, account.organization, account.login, account.password, account.secret, ...account.recovery]) {
      check(typeof forbidden === 'string' && !html.includes(forbidden), 'summary includes data outside its minimum projection');
    }
    const back = new URL(await panel.locator('[data-minimal-lead-back]').getAttribute('href'), BASE);
    check(back.origin === BASE && back.pathname === '/painel' && back.searchParams.get('section') === 'leads'
      && back.searchParams.get('lang') === locale && [...back.searchParams.keys()].sort().join(',') === 'lang,section',
      'back link loses section or locale');
    check(await page.locator('.brand').evaluate(image => image.complete && image.naturalWidth > 0), 'brand image missing');
  }
  await singleRead(async () => (await Promise.all([page.waitForNavigation(), link.click()]))[0]);
  await inspect('pt');
  for (const locale of ['pt', 'en', 'es']) {
    phase = 'summary-locale-' + locale;
    await singleRead(async () => (await Promise.all([page.waitForNavigation(),
      page.locator('.languages').getByRole('link', { name: copy[locale].link, exact: true }).click()]))[0]);
    check(new URL(page.url()).pathname === summaryPath && new URL(page.url()).search === '?lang=' + locale,
      'language navigation changes the selected resource');
    await inspect(locale);
    for (const viewport of [320, 390, 1440]) {
      await page.setViewportSize({ width: viewport, height: viewport < 500 ? 844 : 1000 });
      await width(page);
      if (locale === 'pt' && [390, 1440].includes(viewport)) {
        // Only the checked minimum summary is captured. No login/MFA, trace or video.
        await inspect(locale);
        await page.screenshot({ path: path.join(output, viewport === 390
          ? 'crm-op01-summary-mobile.png' : 'crm-op01-summary-desktop.png'), fullPage: true });
      }
    }
  }
  phase = 'back-to-localized-list';
  await Promise.all([page.waitForNavigation(), page.locator('[data-minimal-lead-back]').click()]);
  const returned = new URL(page.url());
  check(returned.pathname === '/painel' && returned.searchParams.get('section') === 'leads'
    && returned.searchParams.get('lang') === 'es'
    && await page.locator('tr[data-lead-id="' + lead + '"]').count() === 1, 'back navigation lost selected list or locale');
  phase = 'foreign-resource-denied';
  const foreignResponse = await page.goto(BASE + '/agentes/leads/' + foreign + '/resumo');
  check(foreignResponse.status() === 404 && await page.locator('[data-minimal-lead-summary],[data-minimal-lead-ref]').count() === 0,
    'foreign summary exposed a projection');
  phase = 'unsupported-query-denied';
  const queryResponse = await page.goto(BASE + summaryPath + '?lang=en&extra=OP01_BROWSER_FORGED');
  check(queryResponse.status() === 422 && await page.locator('[data-minimal-lead-summary],[data-minimal-lead-ref]').count() === 0
    && !(await page.content()).includes('OP01_BROWSER_FORGED'), 'unsupported query exposed or reflected a projection');
  phase = 'restored-summary-control';
  await singleRead(() => page.goto(BASE + summaryPath + '?lang=pt'));
  await inspect('pt');
  phase = 'logout-and-denied-return';
  await Promise.all([page.waitForURL(BASE + '/login'), page.locator('form[action="/logout"] button').click()]);
  const deniedCount = summaryResponses.length;
  await page.goto(BASE + summaryPath);
  check(summaryResponses.length === deniedCount + 1 && summaryResponses.at(-1) === 303
    && new URL(page.url()).pathname === '/login'
    && await page.locator('[data-minimal-lead-summary],[data-minimal-lead-ref]').count() === 0
    && !(await page.content()).includes(lead), 'logout left a readable projection');
  check(pageErrors === 0 && blockedExternal === 0 && jsonRequests === 0 && unexpectedWrites === 0
    && automationRequests === 0 && summaryRequests.every(method => method === 'GET'), 'browser made an unexpected effect or request');
  console.log(JSON.stringify({ schema: 'CRM_OP01_MINIMAL_SUMMARY_CHROMIUM_VERIFIED_V1', checks,
    successful_summary_navigations: navigations, summary_requests: summaryRequests.length,
    json_api_requests: jsonRequests, locales: 3, viewport_widths: [320, 390, 1440], screenshots: 2,
    exact_audit_per_navigation: true, service_delegation_verified: false, operational: false }));
  console.log('CRM_OP01_MINIMAL_SUMMARY_CHROMIUM_PASS');
})().catch(error => {
  const reason = error instanceof ProbeFailure ? '; reason=' + error.message : '';
  console.error('CRM_OP01_BROWSER_FAILED phase=' + phase + reason);
  process.exitCode = 1;
}).finally(async () => {
  if (browser) {
    try { await browser.close(); }
    catch { console.error('CRM_OP01_BROWSER_CLOSE_FAILED'); process.exitCode = 1; }
  }
});
