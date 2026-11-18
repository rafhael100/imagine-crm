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
const restoredLabels = new Set();
const fieldIds = numbers => numbers.map(number => 'crm-field-a11y-' + number);
async function accessibleLabels(page, screen, expected = []) {
  const previousPhase = phase;
  phase = previousPhase + '-label-associations';
  const associations = await page.locator('label').evaluateAll(labels => labels.every(label =>
    label.control !== null && [...label.control.labels].includes(label)));
  check(associations, 'Unassociated field label in ' + screen);
  const uniqueIds = await page.locator('[id]').evaluateAll(nodes =>
    nodes.length === new Set(nodes.map(node => node.id)).size);
  check(uniqueIds, 'Duplicate DOM id in ' + screen);
  for (const id of expected) {
    phase = previousPhase + '-label-' + id;
    const label = page.locator('label[for="' + id + '"]');
    check(await label.count() === 1 && await label.evaluate((node, target) =>
      node.control === document.getElementById(target), id), 'Restored label association missing: ' + id);
    const name = (await label.innerText()).trim();
    // The language navigation has the same accessible name as the preference field.
    check(name.length > 0 && await page.locator('form').getByLabel(name, {exact:true}).getAttribute('id') === id,
      'Control cannot be located by its accessible label: ' + id);
    restoredLabels.add(id);
  }
  phase = previousPhase;
}
async function formContracts(forms) {
  return forms.evaluateAll(nodes => nodes.map(form => ({
    action: form.action, method: form.method,
    fields: [...new FormData(form)].filter(([name]) => name !== '_token')
      .sort((left, right) => JSON.stringify(left).localeCompare(JSON.stringify(right))),
  })).sort((left, right) => JSON.stringify(left).localeCompare(JSON.stringify(right))));
}
async function languageClick(page, locale, pathname, section = null) {
  const name = {pt:'PT-BR',en:'EN',es:'ES'}[locale];
  await Promise.all([page.waitForNavigation(), page.locator('.languages').getByRole('link', {name,exact:true}).click()]);
  const location = new URL(page.url());
  check(location.pathname === pathname && location.searchParams.get('lang') === locale
    && location.searchParams.get('section') === section
    && await page.locator('html').getAttribute('lang') === (locale === 'pt' ? 'pt-BR' : locale),
    'Language link lost its destination or selected locale');
}
async function keyboardScroll(page, region, hint, name, hintText, lastContent) {
  check(await region.getAttribute('role') === 'region' && await region.getAttribute('tabindex') === '0'
    && await region.getAttribute('aria-label') === name
    && await page.getByRole('region', {name,exact:true}).count() === 1,
    'Horizontal region lacks its accessible name or keyboard tab stop');
  check(await hint.isVisible() && (await hint.innerText()).trim() === hintText,
    'Mobile horizontal scroll instruction missing or untranslated');
  await region.focus();
  await page.keyboard.press('Shift+Tab');
  check(!await region.evaluate(node => document.activeElement === node), 'Shift+Tab cannot leave scroll region');
  await page.keyboard.press('Tab');
  check(await region.evaluate(node => document.activeElement === node && node.matches(':focus-visible')
    && getComputedStyle(node).outlineStyle !== 'none' && parseFloat(getComputedStyle(node).outlineWidth) >= 3),
    'Tab cannot reach scroll region with visible focus');
  const before = await region.evaluate(node => ({left:node.scrollLeft,max:node.scrollWidth-node.clientWidth}));
  check(before.max > 1 && before.left < before.max - 1, 'Mobile fixture does not exercise horizontal overflow');
  for (let index = 0; index < 64; index++) await page.keyboard.press('ArrowRight', {delay:20});
  await page.waitForFunction(selector => {
    const node = document.querySelector(selector);
    return node.scrollLeft >= node.scrollWidth - node.clientWidth - 1;
  }, await region.evaluate(node => node.classList.contains('kanban') ? '.kanban[role="region"]' : '.tablewrap[role="region"]'));
  const reached = await lastContent.evaluate(node => {
    const region = node.closest('[role="region"]');
    const bounds = region.getBoundingClientRect();
    const content = node.getBoundingClientRect();
    return content.left >= bounds.left - 1 && content.right <= bounds.right + 1 && region.scrollLeft > 0;
  });
  check(reached, 'Arrow keys did not reveal the final column or account action');
  for (let index = 0; index < 4; index++) await page.keyboard.press('ArrowLeft', {delay:20});
  await page.waitForFunction(selector => {
    const node = document.querySelector(selector);
    return node.scrollLeft < node.scrollWidth - node.clientWidth - 1;
  }, await region.evaluate(node => node.classList.contains('kanban') ? '.kanban[role="region"]' : '.tablewrap[role="region"]'));
  check(await region.evaluate(node => document.activeElement === node), 'Horizontal scrolling lost keyboard focus');
}
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
    for (const [route, ids] of [['/cadastro',[]],['/convite',fieldIds([1,2,3,4])],
      ['/recuperar',fieldIds([5,6,7])],['/login',[]]]) {
      phase = 'field-labels-' + route.slice(1);
      await page.goto(base + route);
      await accessibleLabels(page, route, ids);
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
      const ids = {leads:fieldIds([8,9,10]), tasks:fieldIds([11,12,13]),
        billing:fieldIds([15,16]), automation:['automation-language'], settings:['automation-language']}[section] || [];
      await accessibleLabels(page, section, ids);
    }
    check(restoredLabels.size === 16, 'Not all sixteen restored field labels were exercised');
    phase = 'versioned-automation';
    await page.goto(base + '/painel?section=automation');
    const automationVersion = Number(await page.locator('[name=automation_version]').inputValue());
    await page.locator('[name="rules[]"][value=followup_qualified]').check();
    await page.locator('[name=delay_seconds]').selectOption('0');
    await Promise.all([page.waitForURL('**/painel?section=settings'), page.getByRole('button', {name:'Salvar nova versão →',exact:true}).click()]);
    check(Number(await page.locator('[name=automation_version]').inputValue())===automationVersion+1,'Automation revision not persisted');
    phase='browser-native-effect';
    await page.goto(base+'/painel?section=kanban');
    const opportunity=page.locator('article[data-lead-id]').filter({hasText:title});
    const leadId = await opportunity.getAttribute('data-lead-id');
    const kanbanForms = await formContracts(opportunity.locator('form'));
    check(kanbanForms.length === 2 && kanbanForms.every(form => form.method === 'post'
      && form.action === base + '/leads/' + leadId
      && form.fields.map(([name]) => name).sort().join(',') === 'stage,version'
      && form.fields.find(([name]) => name === 'version')[1] === '1')
      && kanbanForms.map(form => form.fields.find(([name]) => name === 'stage')[1]).sort().join(',') === 'lost,qualified',
      'Kanban forms changed canonical lead scope, revision or transitions');
    const kanbanCopy = {
      pt:{name:'Kanban',advance:'Avançar →',lost:'Marcar perdido',hint:'Deslize horizontalmente para ver todas as etapas.'},
      en:{name:'Pipeline',advance:'Advance →',lost:'Mark as lost',hint:'Swipe horizontally to see every stage.'},
      es:{name:'Embudo',advance:'Avanzar →',lost:'Marcar como perdido',hint:'Deslice horizontalmente para ver todas las etapas.'},
    };
    for (const locale of ['pt','en','es']) {
      phase = 'kanban-locale-' + locale;
      await languageClick(page, locale, '/painel', 'kanban');
      const copy = kanbanCopy[locale];
      check(await opportunity.getByRole('button', {name:copy.advance,exact:true}).count() === 1
        && await opportunity.getByRole('button', {name:copy.lost,exact:true}).count() === 1,
        'Kanban actions lack their localized accessible names');
      check(JSON.stringify(await formContracts(opportunity.locator('form'))) === JSON.stringify(kanbanForms),
        'Kanban translation changed submitted URLs or canonical values');
      await page.setViewportSize({width:320,height:844});
      await width(page);
      await keyboardScroll(page, page.locator('.kanban[role=region]'), page.locator('[data-scroll-hint=kanban]'),
        copy.name, copy.hint, page.locator('.kanban .lane').last());
      await page.setViewportSize({width:390,height:844});
      await width(page);
      if (locale === 'es') await page.screenshot({path:path.join(output,'crm-kanban-es-mobile.png'),fullPage:true});
      await page.setViewportSize({width:1440,height:1000});
    }
    await languageClick(page, 'pt', '/painel', 'kanban');
    phase = 'browser-native-effect';
    await opportunity.getByRole('button',{name:'Avançar →',exact:true}).click();
    await page.goto(base+'/painel?section=automation');
    const completed=page.locator('[data-automation-id]:has([data-automation-state=completed])');
    check(await completed.count()===1,'Browser action did not complete one native run');
    const executionId=await completed.getAttribute('data-automation-id');
    await page.goto(base+'/painel?section=tasks');
    check(await page.locator('[data-task-id="'+executionId+'"]').count()===1,'Native browser run did not create exactly its task');
    await page.goto(base+'/painel?section=automation');
    await page.setViewportSize({width:1440,height:1000});
    await page.screenshot({path:path.join(output,'crm-automation-desktop.png'),fullPage:true});
    for (const viewport of [320,390,768]) { await page.setViewportSize({width:viewport,height:844}); await width(page); }
    await page.setViewportSize({width:390,height:844});
    await page.screenshot({path:path.join(output,'crm-automation-mobile.png'),fullPage:true});
    await page.setViewportSize({width:1440,height:1000});
    await page.goto(base + '/painel?section=kanban');
    await page.screenshot({ path: path.join(output, 'crm-client-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    phase = 'client-mobile';
    await page.screenshot({ path: path.join(output, 'crm-client-mobile.png'), fullPage: true });
    await width(page);

    // HTTP reached revision 20 for client B. Submit its real browser form:
    // disabled automation controls must preserve their values via hidden inputs.
    const preferenceContext = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const preferencePage = await preferenceContext.newPage();
    preferencePage.on('pageerror', () => failures.push('preference-pageerror'));
    // Recovery code 0 was consumed by the final HTTP session-expiry scenario.
    await login(preferencePage, {...fixtures.clientB, recovery: [fixtures.clientB.recovery[1]]});
    phase = 'capped-preferences-form';
    await preferencePage.goto(base + '/painel?section=settings');
    const preferenceForm = preferencePage.locator('form[action="/preferencias"]');
    const preferenceValues = () => preferenceForm.evaluate(form => {
      const values = new FormData(form);
      return {version: values.getAll('automation_version'), delay: values.getAll('delay_seconds'),
        rules: values.getAll('rules[]').sort(), language: values.get('language')};
    });
    const priorPreferences = await preferenceValues();
    check(JSON.stringify(priorPreferences.version) === '["20"]'
      && JSON.stringify(priorPreferences.delay) === '["5"]'
      && JSON.stringify(priorPreferences.rules) === '["followup_proposal","followup_qualified"]',
      'Capped form lost or duplicated its persisted automation values');
    check(await preferenceForm.locator('button[type=submit]').isEnabled()
      && await preferenceForm.locator('[name=language]').isEnabled(), 'Capped language form is disabled');
    check(await preferenceForm.locator('select[name=delay_seconds]').isDisabled()
      && await preferenceForm.locator('select[name=delay_seconds]').inputValue() === '5',
      'Capped timing control is editable or shows a different persisted delay');
    const cappedRules = preferenceForm.locator('input[type=checkbox][name="rules[]"]');
    check(await cappedRules.count() === 2
      && await cappedRules.evaluateAll(inputs => inputs.every(input => input.matches(':disabled'))),
      'Capped automation rules remain editable');
    check(await preferencePage.locator('[data-automation-revision-limit="20"]').isVisible(),
      'Capped form does not explain the preserved rules and available language change');
    await Promise.all([preferencePage.waitForNavigation(), preferenceForm.locator('button[type=submit]').click()]);
    check(new URL(preferencePage.url()).search === '?section=settings'
      && JSON.stringify(await preferenceValues()) === JSON.stringify(priorPreferences),
      'Real capped no-op form submission failed or changed automation values');
    const nextLanguage = priorPreferences.language === 'en' ? 'pt' : 'en';
    await preferenceForm.locator('[name=language]').selectOption(nextLanguage);
    await Promise.all([preferencePage.waitForNavigation(), preferenceForm.locator('button[type=submit]').click()]);
    check(new URL(preferencePage.url()).search === '?section=settings'
      && await preferencePage.locator('html').getAttribute('lang') === (nextLanguage === 'pt' ? 'pt-BR' : nextLanguage)
      && JSON.stringify(await preferenceValues()) === JSON.stringify({...priorPreferences, language: nextLanguage}),
      'Real capped language form submission lost its values, language or revision');
    await preferencePage.screenshot({path: path.join(output, 'crm-preferences-revision-20.png'), fullPage: true});
    const savedPreferenceValues = await preferenceValues();
    const localizedAutomation = {
      pt: {link:'PT-BR', title:'Seu próximo atendimento começa aqui.', template:'Modelos de acompanhamento', delay:'Após 5 segundos', completed:'Concluída', submit:'Salvar idioma'},
      en: {link:'EN', title:'Your next follow-up starts here.', template:'Follow-up templates', delay:'After 5 seconds', completed:'Completed', submit:'Save language'},
      es: {link:'ES', title:'Tu próximo seguimiento empieza aquí.', template:'Plantillas de seguimiento', delay:'Después de 5 segundos', completed:'Completada', submit:'Guardar idioma'},
    };
    for (const section of ['settings','automation']) {
      await preferencePage.goto(base + '/painel?section=' + section);
      for (const locale of ['pt','en','es']) {
        phase = 'automation-locale-' + section + '-' + locale;
        const copy = localizedAutomation[locale];
        await Promise.all([preferencePage.waitForNavigation(),
          preferencePage.locator('.languages').getByRole('link',{name:copy.link,exact:true}).click()]);
        const location = new URL(preferencePage.url());
        check(location.searchParams.get('section') === section && location.searchParams.get('lang') === locale,
          'Language switch lost the selected CRM section');
        const localized = preferencePage.locator('[data-automation-locale="' + locale + '"]');
        check(await localized.getByRole('heading',{name:copy.title,exact:true}).isVisible()
          && await localized.locator('legend').innerText() === copy.template,
          'Automation content does not use the selected language');
        check(await preferenceForm.locator('select[name=delay_seconds] option:checked').innerText() === copy.delay
          && await localized.locator('[data-automation-state=completed]').first().innerText() === copy.completed
          && await preferenceForm.getByRole('button',{name:copy.submit,exact:true}).isEnabled(),
          'Automation timing, status or available action is not translated');
        check(JSON.stringify(await preferenceValues()) === JSON.stringify(savedPreferenceValues),
          'Viewing another language mutated the saved preference form values');
        for (const viewport of [390,1440]) {
          await preferencePage.setViewportSize({width:viewport,height:900});
          await width(preferencePage);
          if (section === 'automation' && locale === 'es') {
            await preferencePage.screenshot({path:path.join(output,'crm-automation-es-' + viewport + '.png'),fullPage:true});
          }
        }
      }
    }
    const invalidSection = await preferencePage.goto(base + '/painel?section%5B%5D=settings&lang=en&extra=do-not-forward');
    check(invalidSection.status() === 200
      && await preferencePage.getByRole('heading',{name:'Overview',exact:true}).isVisible(),
      'Non-string section did not safely fall back to overview');
    const safeLanguageLinks = await preferencePage.locator('.languages a').evaluateAll(links => links.map(link => link.href));
    check(safeLanguageLinks.every(link => {
      const query = new URL(link).searchParams;
      return query.get('section') === 'overview' && [...query.keys()].sort().join(',') === 'lang,section';
    }), 'Language links copied unvalidated section or unrelated query parameters');
    console.log('PRIVATE AUTOMATION LOCALES CHROMIUM PASS: PT/EN/ES, current section retained, exact values preserved, desktop/mobile.');
    await preferenceContext.close();
    console.log('PRIVATE CAPPED PREFERENCES CHROMIUM PASS: enabled language submit, preserved disabled controls, revision 20 unchanged.');

    const administrator = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const adminPage = await administrator.newPage();
    adminPage.on('pageerror', () => failures.push('administrator-pageerror'));
    await login(adminPage, fixtures.admin, true);
    phase = 'administrator-desktop';
    check(await adminPage.locator('[data-account-id]').count() >= 3, 'Administrator accounts missing');
    check(!(await adminPage.content()).includes(title), 'Administrator exposed tenant lead content');
    await width(adminPage);
    await adminPage.screenshot({ path: path.join(output, 'crm-admin-desktop.png'), fullPage: true });
    await adminPage.setViewportSize({ width: 390, height: 844 });
    phase = 'administrator-mobile';
    await adminPage.screenshot({ path: path.join(output, 'crm-admin-mobile.png'), fullPage: true });
    await width(adminPage);
    const adminForms = adminPage.locator('[data-account-id] form');
    const accountForms = await formContracts(adminForms);
    check(accountForms.length >= 3 && accountForms.every(form => form.method === 'post'
      && /^\/admin\/contas\/[0-9a-f-]{36}$/.test(new URL(form.action).pathname)
      && new URL(form.action).origin === base && new URL(form.action).search === ''
      && form.fields.length === 1 && form.fields[0][0] === 'status'
      && ['active','suspended'].includes(form.fields[0][1])), 'Administrator form contracts are not canonical');
    const adminCopy = {
      pt:{title:'Uma visão clara da operação.',accounts:'Contas e acesso',orders:'Pedidos simulados',audit:'Auditoria administrativa',
        company:'Empresa fictícia',status:'Estado',action:'Ação',order:'Pedido',plan:'Plano',method:'Método fictício',when:'Quando (UTC)',account:'Conta',
        suspend:'Suspender',resume:'Reativar',owner:'Responsável',member:'Membro',
        hint:'Deslize para ver as colunas e ações da tabela.',
        notice:'Suspender revoga as sessões existentes. Reativar exige novo login e MFA. Esta administração vê metadados operacionais fictícios; não abre leads e tarefas de outra empresa.'},
      en:{title:'A clear view of operations.',accounts:'Accounts and access',orders:'Simulated orders',audit:'Administrative audit',
        company:'Test company',status:'Status',action:'Action',order:'Order',plan:'Plan',method:'Test payment method',when:'When (UTC)',account:'Account',
        suspend:'Suspend',resume:'Resume',owner:'Owner',member:'Member',
        hint:'Swipe to see all table columns and actions.',
        notice:'Suspending revokes active sessions. Resuming requires a fresh sign-in and MFA. Administrators see test operational metadata, never leads or tasks from another company.'},
      es:{title:'Una visión clara de la operación.',accounts:'Cuentas y acceso',orders:'Pedidos simulados',audit:'Auditoría administrativa',
        company:'Empresa ficticia',status:'Estado',action:'Acción',order:'Pedido',plan:'Plan',method:'Método de pago de prueba',when:'Cuándo (UTC)',account:'Cuenta',
        suspend:'Suspender',resume:'Reactivar',owner:'Responsable',member:'Miembro',
        hint:'Deslice para ver todas las columnas y acciones de la tabla.',
        notice:'Suspender revoca las sesiones existentes. Reactivar requiere un nuevo inicio de sesión y MFA. La administración solo ve metadatos de prueba, no contactos ni tareas de otras empresas.'},
    };
    for (const locale of ['pt','en','es']) {
      phase = 'administrator-locale-' + locale;
      await languageClick(adminPage, locale, '/admin');
      const copy = adminCopy[locale];
      const localized = adminPage.locator('[data-admin-locale="' + locale + '"]');
      check(await localized.getByRole('heading', {name:copy.title,exact:true}).isVisible(), 'Administrator heading is not translated');
      const accounts = localized.locator('.tablewrap[role=region]');
      const orders = localized.locator('.tablewrap').filter({has:adminPage.getByRole('heading', {name:copy.orders,exact:true})});
      const audit = localized.locator('.tablewrap').filter({has:adminPage.getByRole('heading', {name:copy.audit,exact:true})});
      check(await accounts.getByRole('heading', {name:copy.accounts,exact:true}).isVisible()
        && await orders.count() === 1 && await audit.count() === 1,
        'Administrator table sections are not translated');
      check(JSON.stringify(await accounts.locator('thead th').allTextContents()) === JSON.stringify([copy.company,'Trial',copy.status,copy.action])
        && JSON.stringify(await orders.locator('thead th').allTextContents()) === JSON.stringify([copy.order,copy.plan,copy.method,copy.status])
        && JSON.stringify(await audit.locator('thead th').allTextContents()) === JSON.stringify([copy.when,copy.action,copy.account]),
        'Administrator column headers are not translated');
      check((await localized.locator('.notice').innerText()).trim() === copy.notice,
        'Administrator session-revocation and MFA guidance is not translated');
      check(await adminForms.evaluateAll((forms, labels) => forms.every(form =>
        form.querySelector('button').textContent.trim() ===
          (new FormData(form).get('status') === 'suspended' ? labels.suspend : labels.resume)), copy),
        'Administrator account actions are not translated for their canonical status');
      check(await localized.locator('[data-team-role]').evaluateAll((nodes, labels) => nodes.length > 0
        && nodes.every(node => node.textContent.trim() === labels[node.dataset.teamRole]), copy),
        'Administrator account roles are not translated');
      check(JSON.stringify(await formContracts(adminForms)) === JSON.stringify(accountForms),
        'Administrator translation changed account action URLs or submitted status');
      await accessibleLabels(adminPage, 'administrator-' + locale);
      await adminPage.setViewportSize({width:1440,height:1000});
      await width(adminPage);
      if (locale === 'en') await adminPage.screenshot({path:path.join(output,'crm-admin-en-desktop.png'),fullPage:true});
      await adminPage.setViewportSize({width:320,height:844});
      await width(adminPage);
      await keyboardScroll(adminPage, accounts, adminPage.locator('[data-scroll-hint=admin]'),
        copy.accounts, copy.hint, accounts.locator('[data-account-id] button').first());
      await adminPage.setViewportSize({width:390,height:844});
      await width(adminPage);
      if (locale === 'es') await adminPage.screenshot({path:path.join(output,'crm-admin-es-mobile.png'),fullPage:true});
    }
    console.log('PRIVATE QA-02 CHROMIUM PASS: sixteen field labels, unique IDs, PT/EN/ES admin and Kanban, stable form contracts, keyboard horizontal scroll.');
    check(failures.length === 0, 'Browser JavaScript failure');
    console.log('PRIVATE CHROMIUM PASS: ' + checks + ' assertions; desktop/mobile client and administrator.');
  } finally { await browser.close(); }
})().catch(() => { console.error('Private Chromium verification failed at phase ' + phase + '; no credential or page payload logged.'); process.exit(1); });
