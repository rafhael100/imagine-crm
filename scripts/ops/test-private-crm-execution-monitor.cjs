// Real Chromium + password/MFA on the disposable private CRM application.
// Synthetic presentation fixtures only; this does not execute n8n.
const fs = require('node:fs');
const path = require('node:path');

function required(value, message) { if (!value) throw Error(message); }
required(process.env.GITHUB_ACTIONS === 'true'
  && process.env.RUNNER_ENVIRONMENT === 'github-hosted'
  && process.env.GITHUB_REPOSITORY === 'rafhael100/imagine-crm'
  && process.env.GITHUB_EVENT_NAME === 'pull_request', 'Real hosted CRM pull request required');
required(process.env.CRM_PRIVATE_CI_MONITOR_PROFILE === 'execution-monitor-v1'
  && process.env.CRM_PRIVATE_PROJECT === 'imagine-crm-journey-ci', 'Disposable monitor profile required');
const { chromium } = require(path.join(process.env.CRM_PRIVATE_PLAYWRIGHT_DIR, 'node_modules/playwright'));
const vault = process.argv[2];
required(vault && !(fs.statSync(vault).mode & 0o077), 'Protected monitor fixture vault required');
const fixtures = JSON.parse(fs.readFileSync(vault, 'utf8'));
required(fixtures.monitor.synthetic_presentation_only === true
  && fixtures.monitor.n8n_executions_in_this_fixture === 0, 'Presentation fixture provenance required');
const output = process.argv[3];
fs.mkdirSync(output, { recursive: true, mode: 0o700 });
const base = 'http://127.0.0.1:18086';
const events = fixtures.monitor.events.clientA;
const main = events[55];
const labels = {
  pt: {title: 'Cada retorno, no seu contexto.', link: 'PT-BR', UNOBSERVED: 'Sem retorno registrado',
    NOT_STAGED: 'Aguardando preparação', NOT_STARTED: 'Aguardando início', STARTED: 'Início registrado',
    RESULT: 'Resultado recebido', ACKED: 'Confirmação registrada', UNKNOWN: 'Resultado incerto'},
  en: {title: 'Every response, in context.', link: 'EN', UNOBSERVED: 'No response recorded',
    NOT_STAGED: 'Awaiting preparation', NOT_STARTED: 'Awaiting start', STARTED: 'Start recorded',
    RESULT: 'Result received', ACKED: 'Confirmation recorded', UNKNOWN: 'Uncertain result'},
  es: {title: 'Cada respuesta, en su contexto.', link: 'ES', UNOBSERVED: 'Sin respuesta registrada',
    NOT_STAGED: 'Esperando preparación', NOT_STARTED: 'Esperando inicio', STARTED: 'Inicio registrado',
    RESULT: 'Resultado recibido', ACKED: 'Confirmación registrada', UNKNOWN: 'Resultado incierto'},
};
let checks = 0;
let phase = 'startup';
const failures = [];
class MonitorAssertion extends Error {}
function check(value, label) { checks++; if (!value) throw new MonitorAssertion(label); }

async function context(browser) {
  const context = await browser.newContext({ viewport: {width: 1440, height: 1000} });
  // The CI browser has no reason to leave this runner's loopback application.
  await context.route('**/*', route => {
    const url = new URL(route.request().url());
    if (url.origin !== base) { failures.push('non-loopback-request'); return route.abort(); }
    return route.continue();
  });
  const page = await context.newPage();
  page.on('pageerror', () => failures.push('pageerror'));
  return {context, page};
}

async function login(page, actor) {
  phase = 'login-' + actor;
  const account = fixtures[actor];
  await page.goto(base + '/login');
  await page.locator('[name=login]').fill(account.login);
  await page.locator('[name=password]').fill(account.password);
  await Promise.all([page.waitForURL('**/mfa'), page.locator('form[action="/login"] button').click()]);
  check(await page.locator('[name=code]').isVisible(), 'Real MFA challenge absent');
  // HTTP uses real TOTP. Recovery code zero is reserved for this browser journey.
  await page.locator('[name=code]').fill(account.recovery[0]);
  await page.locator('[name=recovery]').check();
  await Promise.all([page.waitForURL(actor === 'admin' ? '**/admin' : '**/painel'),
    page.locator('form[action="/mfa"] button').click()]);
  check(await page.locator('h1').isVisible(), 'Authenticated workspace absent');
}

async function width(page) {
  const [documentWidth, viewport] = await page.evaluate(() => [document.documentElement.scrollWidth, innerWidth]);
  check(documentWidth <= viewport + 1, 'Horizontal page overflow at ' + viewport + 'px');
}

async function safe(page) {
  const html = await page.content();
  for (const actor of ['clientA', 'clientB', 'member', 'clientEmpty', 'admin']) {
    for (const secret of [fixtures[actor].password, fixtures[actor].secret, ...fixtures[actor].recovery]) {
      check(!html.includes(secret), 'Credential in screenshot surface');
    }
  }
  check(!['SQLSTATE', 'crm_auto_service', 'payload_hash', 'task_payload_hash', 'receipt_hash',
    'AUTO_IA_EXECUTION_STATUS_V1', 'SYNTHETIC_EXECUTION_STATUS_KEY'].some(value => html.includes(value)),
    'Private transport or database detail exposed');
  check(await page.locator('[data-execution-monitor] form,[data-execution-monitor] script').count() === 0,
    'Execution monitor contains a form or script');
}

async function screenshot(page, locale, view) {
  await safe(page);
  for (const [name, viewport] of [['desktop', {width: 1440, height: 1000}], ['mobile', {width: 390, height: 844}]]) {
    await page.setViewportSize(viewport);
    await width(page);
    // A 50-card list is intentionally captured at its viewport, not as an enormous strip.
    await page.screenshot({path: path.join(output, `crm-execution-monitor-${locale}-${name}-${view}.png`),
      fullPage: view === 'detail'});
  }
  await page.setViewportSize({width: 320, height: 844});
  await width(page);
  await page.setViewportSize({width: 1440, height: 1000});
}

function queryIs(url, locale, event = null) {
  const target = new URL(url, base);
  const keys = [...target.searchParams.keys()].sort().join(',');
  return target.origin === base && target.pathname === '/painel'
    && target.searchParams.get('section') === 'n8n' && target.searchParams.get('lang') === locale
    && target.searchParams.get('event') === event
    && keys === (event ? 'event,lang,section' : 'lang,section');
}

(async () => {
  const browser = await chromium.launch({executablePath: process.env.CRM_PRIVATE_CHROME || '/usr/bin/google-chrome',
    headless: true});
  try {
    const primary = await context(browser);
    const page = primary.page;
    await page.goto(base + '/painel?section=n8n');
    check(new URL(page.url()).pathname === '/login'
      && await page.locator('[data-execution-monitor]').count() === 0, 'Anonymous monitor read admitted');
    await login(page, 'clientA');
    let monitorRequests = [];
    page.on('request', request => monitorRequests.push({method: request.method(), origin: new URL(request.url()).origin}));
    for (const locale of ['pt', 'en', 'es']) {
      phase = 'list-' + locale;
      await page.goto(base + '/painel?section=n8n&lang=' + locale);
      const monitor = page.locator('[data-execution-monitor="' + locale + '"]');
      check(await monitor.count() === 1 && await monitor.getByRole('heading', {name: labels[locale].title, exact: true}).isVisible(),
        'Monitor heading is not translated');
      const ids = await monitor.locator('[data-execution-event]').evaluateAll(nodes => nodes.map(node => node.dataset.executionEvent));
      check(JSON.stringify(ids) === JSON.stringify([...events].reverse().slice(0, 50).map(event => event.event_id)),
        'Event ordering or bounded list differs');
      for (const event of events.slice(49)) {
        check(await monitor.locator('[data-execution-event="' + event.event_id + '"] [data-execution-state]').innerText()
          === labels[locale][event.state], 'Execution state not translated');
      }
      const links = await monitor.locator('[data-execution-open]').evaluateAll(nodes => nodes.map(node => ({id: node.dataset.executionOpen, href: node.getAttribute('href')})));
      check(links.every(link => queryIs(link.href, locale, link.id)), 'Detail link forwards unsupported query fields');
      check(queryIs(await monitor.locator('[data-execution-refresh]').getAttribute('href'), locale),
        'List refresh lost the locale');
      await screenshot(page, locale, 'list');

      phase = 'detail-' + locale;
      await Promise.all([page.waitForNavigation(), page.locator('[data-execution-open="' + main.event_id + '"]').click()]);
      check(queryIs(page.url(), locale, main.event_id), 'Opening history lost event or locale');
      check(await page.locator('[data-execution-selected="' + main.event_id + '"]').count() === 1, 'Detail absent');
      const sequences = await page.locator('[data-execution-history] [data-execution-sequence]').evaluateAll(nodes => nodes.map(node => Number(node.dataset.executionSequence)));
      check(JSON.stringify(sequences) === '[6,5,4,3,2,1]' && sequences.length <= 20, 'History order/limit differs');
      check(queryIs(await page.locator('[data-execution-refresh]').getAttribute('href'), locale, main.event_id),
        'Detail refresh lost selected event');
      await screenshot(page, locale, 'detail');

      const next = {pt: 'en', en: 'es', es: 'pt'}[locale];
      await Promise.all([page.waitForNavigation(), page.locator('.languages').getByRole('link', {name: labels[next].link, exact: true}).click()]);
      check(queryIs(page.url(), next, main.event_id)
        && await page.locator('[data-execution-monitor="' + next + '"]').count() === 1,
        'Language link lost selected event or selected locale');
      await Promise.all([page.waitForNavigation(), page.locator('.execution-back').click()]);
      check(queryIs(page.url(), next), 'Back to events lost the locale or retained detail');
    }
    phase = 'absent-versus-not-staged';
    for (const index of [49, 50, 54, 0]) {
      const event = events[index];
      await page.goto(base + '/painel?section=n8n&event=' + event.event_id + '&lang=en');
      check(await page.locator('[data-execution-selected] [data-execution-state]').innerText() === labels.en[event.state],
        'Unobserved, NOT_STAGED or UNKNOWN conflated');
      check((await page.locator('[data-execution-unobserved]').count() > 0) === (event.history_count === 0),
        'Projection absence was presented as a remote observation');
    }
    await page.waitForLoadState('networkidle');
    const priorRequests = monitorRequests.length;
    await page.waitForTimeout(1000);
    check(monitorRequests.length === priorRequests, 'Monitor started automatic polling');
    check(monitorRequests.every(request => request.method === 'GET' && request.origin === base),
      'Monitor navigation sent a mutation or external request');
    await primary.context.close();

    for (const actor of ['clientB', 'clientEmpty', 'admin']) {
      const session = await context(browser);
      await login(session.page, actor);
      phase = 'scope-' + actor;
      const response = await session.page.goto(base + '/painel?section=n8n&lang=pt');
      if (actor === 'admin') {
        check(response.status() === 403 && await session.page.locator('[data-execution-monitor]').count() === 0,
          'Global admin accessed tenant execution history');
      } else if (actor === 'clientEmpty') {
        check(response.status() === 200 && await session.page.locator('[data-execution-empty]').count() === 1
          && await session.page.locator('[data-execution-event]').count() === 0, 'Empty company inherited another company history');
      } else {
        check(response.status() === 200 && await session.page.locator('[data-execution-event]').count() === 1
          && await session.page.locator('[data-execution-event="' + fixtures.monitor.events.clientB[0].event_id + '"]').count() === 1,
          'Peer company list was not isolated');
        const foreign = await session.page.goto(base + '/painel?section=n8n&event=' + main.event_id);
        check(foreign.status() === 404 && await session.page.locator('[data-execution-monitor]').count() === 0,
          'Peer company accessed another event by URL');
      }
      await session.context.close();
    }
    check(failures.length === 0, 'Browser emitted a JavaScript or non-loopback failure');
    console.log(JSON.stringify({schema: 'CRM_N8N_MONITOR_CHROMIUM_EVIDENCE_V1', checks,
      languages: ['pt', 'en', 'es'], screenshots: 12, viewports: [320, 390, 1440],
      synthetic_presentation_fixtures: true, new_n8n_executions: 0,
      real_password_mfa: true, only_get_after_login: true}));
    console.log('PRIVATE_CRM_EXECUTION_MONITOR_CHROMIUM_PASS NO_GO');
  } finally { await browser.close(); }
})().catch(error => {
  const reason = error instanceof MonitorAssertion ? error.message
    : (/^[A-Za-z0-9_]{1,60}$/.test(error?.name || '') ? error.name : 'ExternalError');
  console.error('Private execution monitor Chromium failed at ' + phase + ': ' + reason
    + '; no credential, cookie, receipt or page payload logged.');
  process.exit(1);
});
