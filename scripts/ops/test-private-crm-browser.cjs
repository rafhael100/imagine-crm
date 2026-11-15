// Independent Chromium tests against the ephemeral loopback application only.
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(path.join(process.env.CRM_PRIVATE_PLAYWRIGHT_DIR, 'node_modules/playwright'));
const vault = process.argv[2];
if (fs.statSync(vault).mode & 0o077) throw Error('Protected fixture vault required');
const fixtures = JSON.parse(fs.readFileSync(vault, 'utf8'));
const output = process.argv[3];
fs.mkdirSync(output, { recursive: true, mode: 0o700 });
const base = 'http://127.0.0.1:18086';
let checks = 0;
let phase = 'startup';
function check(value, label) { checks++; if (!value) throw Error(label); }
async function login(page, account, admin = false) {
  phase = admin ? 'administrator-login' : 'client-login';
  await page.goto(base + '/login');
  await page.locator('[name=login]').fill(account.login);
  await page.locator('[name=password]').fill(account.password);
  await Promise.all([page.waitForURL('**/mfa'), page.locator('form[action="/login"] button').click()]);
  await page.locator('[name=code]').fill(account.recovery[0]);
  await page.locator('[name=recovery]').check();
  await Promise.all([page.waitForURL(admin ? '**/admin' : '**/painel'), page.locator('form[action="/mfa"] button').click()]);
  check(await page.locator('h1').isVisible(), 'Authenticated workspace missing');
}
async function width(page) {
  const values = await page.evaluate(() => [document.documentElement.scrollWidth, innerWidth]);
  check(values[0] <= values[1] + 1, 'Page overflows viewport');
}
(async () => {
  const browser = await chromium.launch({ executablePath: process.env.CRM_PRIVATE_CHROME || '/usr/bin/google-chrome', headless: true });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const failures = [];
    page.on('pageerror', () => failures.push('pageerror'));
    await page.goto(base + '/');
    await width(page);
    await page.screenshot({ path: path.join(output, 'crm-home-desktop.png'), fullPage: true });
    await login(page, fixtures.clientA);
    await page.goto(base + '/painel?section=leads');
    phase = 'lead-creation';
    await page.locator('form[action="/leads"] [name=title]').fill('LEAD_TESTE_CHROMIUM');
    await page.locator('form[action="/leads"] [name=value_cents]').fill('250000');
    await Promise.all([page.waitForURL('**/painel?section=leads'), page.locator('form[action="/leads"] button').click()]);
    check(await page.getByText('LEAD_TESTE_CHROMIUM', { exact: true }).isVisible(), 'Browser lead creation missing');
    for (const section of ['overview', 'leads', 'funnels', 'kanban', 'tasks', 'reports', 'team', 'automation', 'settings', 'billing']) {
      phase = 'client-section-' + section;
      const response = await page.goto(base + '/painel?section=' + section);
      check(response.status() === 200, 'Workspace navigation failed: ' + section);
      await width(page);
    }
    await page.goto(base + '/painel?section=kanban');
    await page.screenshot({ path: path.join(output, 'crm-client-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    phase = 'client-mobile';
    await width(page);
    await page.screenshot({ path: path.join(output, 'crm-client-mobile.png'), fullPage: true });
    const administrator = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const adminPage = await administrator.newPage();
    await login(adminPage, fixtures.admin, true);
    phase = 'administrator-desktop';
    check(await adminPage.locator('[data-account-id]').count() >= 3, 'Administrator accounts missing');
    check(!(await adminPage.content()).includes('LEAD_TESTE_CHROMIUM'), 'Administrator exposed tenant lead content');
    await width(adminPage);
    await adminPage.screenshot({ path: path.join(output, 'crm-admin-desktop.png'), fullPage: true });
    await adminPage.setViewportSize({ width: 390, height: 844 });
    phase = 'administrator-mobile';
    await width(adminPage);
    await adminPage.screenshot({ path: path.join(output, 'crm-admin-mobile.png'), fullPage: true });
    check(failures.length === 0, 'Browser JavaScript failure');
    console.log('PRIVATE CHROMIUM PASS: ' + checks + ' assertions; desktop/mobile client and administrator.');
  } finally { await browser.close(); }
})().catch(() => { console.error('Private Chromium verification failed at phase ' + phase + '; no credential or page payload logged.'); process.exit(1); });
