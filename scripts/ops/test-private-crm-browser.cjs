// Independent Chromium tests against the ephemeral loopback application only.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
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
async function accessibleLabels(page, screen) {
  const invalid = await page.locator('label').evaluateAll(labels => labels
    .filter(label => label.control === null)
    .map(label => label.textContent.trim().slice(0, 36)));
  check(invalid.length === 0, 'Unassociated labels in ' + screen + ': ' + invalid.join('|'));
  const repeated = await page.locator('[id]').evaluateAll(nodes => {
    const ids = nodes.map(node => node.id);
    return ids.length - new Set(ids).size;
  });
  check(repeated === 0, 'Duplicate DOM ids in ' + screen);
}
async function width(page) {
  const values = await page.evaluate(() => [document.documentElement.scrollWidth, innerWidth]);
  if (values[0] > values[1]+1) console.log('PRIVATE LAYOUT WIDTH: document='+values[0]+', viewport='+values[1]+', phase='+phase);
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
    for (const language of ['pt','en','es']) {
      phase='marketing-'+language;
      await page.goto(base+'/?lang='+language);
      check(await page.locator('[data-home-section]').count()>=12,'Incomplete commercial home');
      check(await page.locator('#duvidas details').count()>=10,'Missing purchase FAQs');
      const missingAnchors=await page.locator('.crm-home a[href^="#"]').evaluateAll(links=>links.filter(link=>!document.getElementById(link.hash.slice(1))).length);
      check(missingAnchors===0,'Broken home navigation anchor');
      const invalidCtas=await page.locator('.crm-home a.btn').evaluateAll(links=>links.filter(link=>!link.getAttribute('href').startsWith('/cadastro')&&!['/login','#recursos'].includes(link.getAttribute('href'))).length);
      check(invalidCtas===0,'Commercial CTA leaves approved purchase path');
      phase='marketing-calculator-'+language;
      const calculator=page.locator('form[data-scenario-form="imagine"]');
      check(await calculator.count()===1,'Imagine scenario form missing');
      check(await calculator.locator('input[name=name],input[name=email],input[name=cnpj]').count()===0,
        'Anonymous scenario is collecting personal contact details');
      await calculator.locator('input[name=visits]').fill('2500');
      await calculator.locator('input[name=lead_rate]').fill('8');
      await calculator.locator('input[name=client_rate]').fill('13');
      await Promise.all([
        page.waitForNavigation(),
        calculator.locator('button[type=submit]').click()
      ]);
      check(new URL(page.url()).searchParams.get('lang')===language,
        'Scenario lost selected locale');
      check(await page.locator('[data-scenario-visits="2500"]').count()===1
        && await page.locator('[data-scenario-leads="200"]').count()===1
        && await page.locator('[data-scenario-clients="26"]').count()===1,
        'Scenario estimates not updated after form submission');
      const question=page.locator('#duvidas details').first();
      await question.locator('summary').click();
      check(await question.getAttribute('open')!==null,'FAQ does not open');
      await question.locator('summary').click();
      for (const size of [320,390,768,1440]) {
        await page.setViewportSize({width:size,height:900});
        await width(page);
      }
    }
    await page.goto(base+'/?lang=pt');
    await page.setViewportSize({width:390,height:844});
    await page.screenshot({path:path.join(output,'crm-home-mobile.png'),fullPage:true});
    await page.setViewportSize({width:1440,height:1000});
    for (const entry of [
      ['/cadastro', 'signup'], ['/convite', 'invitation'],
      ['/recuperar', 'recovery'], ['/login', 'login']
    ]) {
      phase = 'a11y-' + entry[1];
      await page.goto(base + entry[0]);
      await accessibleLabels(page, entry[1]);
    }
    await login(page, fixtures.clientA);
    await page.goto(base + '/painel?section=leads');
    phase = 'lead-creation';
    const title='LEAD_TESTE_CHROMIUM_'+crypto.randomBytes(4).toString('hex').toUpperCase();
    await page.locator('form[action="/leads"] [name=title]').fill(title);
    await page.locator('form[action="/leads"] [name=value_cents]').fill('250000');
    await Promise.all([page.waitForURL('**/painel?section=leads'), page.locator('form[action="/leads"] button').click()]);
    check(await page.getByText(title, { exact: true }).isVisible(), 'Browser lead creation missing');
    for (const section of ['overview', 'leads', 'funnels', 'kanban', 'tasks', 'reports', 'team', 'automation', 'settings', 'billing']) {
      phase = 'client-section-' + section;
      const response = await page.goto(base + '/painel?section=' + section);
      check(response.status() === 200, 'Workspace navigation failed: ' + section);
      await width(page);
      await accessibleLabels(page, 'client-' + section);
    }
    await page.goto(base + '/painel?section=kanban');
    await page.screenshot({ path: path.join(output, 'crm-client-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    phase = 'client-mobile';
    check(await page.locator('[data-scroll-hint="kanban"]').isVisible(), 'Mobile Kanban scroll help is hidden');
    const kanban = page.locator('.kanban[role="region"][tabindex="0"]');
    check(await kanban.count() === 1, 'Keyboard accessible Kanban scroller absent');
    const kb = await kanban.evaluate(el => {
      const max = el.scrollWidth - el.clientWidth;
      el.scrollLeft = max;
      return {max, at: el.scrollLeft, lanes: el.querySelectorAll('section.lane').length};
    });
    check(kb.max > 0 && kb.at > 0 && kb.lanes === 5, 'Last Kanban columns unreachable on mobile');
    await kanban.focus();
    check(await kanban.evaluate(el => document.activeElement === el), 'Kanban not keyboard focusable');
    await page.screenshot({ path: path.join(output, 'crm-client-mobile.png'), fullPage: true });
    await width(page);
    const administrator = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const adminPage = await administrator.newPage();
    await login(adminPage, fixtures.admin, true);
    phase = 'administrator-desktop';
    await accessibleLabels(adminPage, 'admin-pt');
    check(await adminPage.locator('[data-account-id]').count() >= 3, 'Administrator accounts missing');
    check(!(await adminPage.content()).includes(title), 'Administrator exposed tenant lead content');
    await width(adminPage);
    await adminPage.screenshot({ path: path.join(output, 'crm-admin-desktop.png'), fullPage: true });
    await adminPage.setViewportSize({ width: 390, height: 844 });
    phase = 'administrator-mobile';
    check(await adminPage.locator('[data-scroll-hint="admin"]').isVisible(), 'Mobile admin table scroll help is hidden');
    const adminTable = adminPage.locator('.tablewrap[role="region"][tabindex="0"]');
    check(await adminTable.count() === 1, 'Keyboard accessible admin accounts scroller missing');
    await adminTable.locator('form[action^="/admin/contas/"] button').first().scrollIntoViewIfNeeded();
    check(await adminTable.locator('form[action^="/admin/contas/"] button').first().isVisible(),
      'Account suspend/resume control inaccessible on mobile');
    await adminTable.focus();
    check(await adminTable.evaluate(el => document.activeElement === el), 'Admin table not keyboard focusable');
    await adminPage.screenshot({ path: path.join(output, 'crm-admin-mobile.png'), fullPage: true });
    await width(adminPage);
    for (const [lang, heading, helper] of [
      ['en', 'Accounts and access', 'Swipe to see all table columns and actions.'],
      ['es', 'Cuentas y acceso', 'Deslice para ver todas las columnas y acciones de la tabla.']
    ]) {
      phase = 'admin-' + lang;
      await adminPage.goto(base + '/admin?lang=' + lang);
      check(await adminPage.getByRole('heading', {name: heading}).isVisible(), 'Admin translation not applied: ' + lang);
      await adminPage.setViewportSize({width:390,height:844});
      check(await adminPage.locator('[data-scroll-hint="admin"]').innerText() === helper,
        'Mobile admin scroll hint untranslated: ' + lang);
      await accessibleLabels(adminPage, 'admin-' + lang);
    }
    check(failures.length === 0, 'Browser JavaScript failure');
    console.log('PRIVATE CHROMIUM PASS: ' + checks + ' assertions; desktop/mobile client and administrator.');
  } finally { await browser.close(); }
})().catch(() => { console.error('Private Chromium verification failed at phase ' + phase + '; no credential or page payload logged.'); process.exit(1); });
