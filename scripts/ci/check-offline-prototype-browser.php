<?php

declare(strict_types=1);

// UX-F3-12: real headless-browser smoke test for a STATIC, synthetic,
// offline HTML prototype. No web server, client data, requests, or persistence.
// Fail CI if Google Chrome/Chromium is unavailable; do not silently fake QA.

require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

$source = dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html';
$html = file_get_contents($source);
if (!is_string($html)
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "UX-F3-12: unexpected prototype or offline CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $candidate = $finder->find($name);
    if ($candidate !== null) {
        $browser = $candidate;
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "UX-F3-12: Chromium/Chrome not available; browser verification NOT performed.\n");
    exit(1);
}

// Inject a harness into a TEMPORARY COPY. Never alter or publish the demo.
// Original app navigation runs first, then the browser checks the DOM/events.
$testScript = <<<'JS'
(function(){
  const output=document.createElement('output');
  output.id='imagine-browser-smoke';
  output.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(output);
  function check(value,message){if(!value) throw new Error(message);}
  try{
    const nav=Array.from(document.querySelectorAll('#demo-navigation button[data-page]'));
    check(nav.length===9,'nine navigation buttons');
    check(document.querySelectorAll('[data-screen]').length===9,'nine demo screens');
    check(document.getElementById('page-title').textContent==='Boas-vindas','initial screen');

    const monitor=nav.find(x=>x.dataset.page==='monitor');
    monitor.click();
    check(document.getElementById('page-title').textContent==='Monitoramento de leads','click switches screen');
    check(document.activeElement===document.getElementById('page-title'),'click focuses heading');
    check(!document.getElementById('screen-monitor').hidden,'active screen visible');
    check(document.querySelectorAll('[data-screen]:not([hidden])').length===1,'one screen visible');
    check(document.querySelectorAll('#demo-navigation [aria-current="page"]').length===1,'one selected item');
    // F4-01: switch fixed activity deadlines without side effects.
    const followUpButtons=Array.from(document.querySelectorAll('#screen-monitor [data-followup-case]'));
    const followUpStatus=document.getElementById('followup-demo-preview');
    check(followUpButtons.length===4&&followUpStatus.textContent.includes('1 de 5'),'four offline follow-up modes');
    check(followUpButtons.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'one initially selected follow-up mode');
    followUpButtons.find(x=>x.dataset.followupCase==='upcoming').click();
    check(followUpStatus.textContent.includes('2 de 5'),'two future synthetic follow-ups');
    const horizonButtons=Array.from(document.querySelectorAll('#screen-monitor [data-followup-horizon]'));
    const horizonSummary=document.getElementById('followup-horizon-summary');
    const horizonRows=()=>Array.from(document.querySelectorAll('#followup-horizon-daily li'));
    check(horizonButtons.length===2&&horizonRows().length===1,'UTC demo starts with a one-day view');
    check(horizonSummary.textContent.includes('1 de 2 exemplos futuros dentro da janela'),'one future example today in UTC');
    check(horizonRows()[0].textContent.includes('2026-11-09')&&horizonRows()[0].textContent.includes('— 1 '),'UTC day has one anonymous future item');
    horizonButtons.find(x=>x.dataset.followupHorizon==='7').click();
    check(horizonRows().length===7&&horizonSummary.textContent.includes('2 de 2 exemplos futuros dentro da janela'),'seven-day UTC horizon contains both future examples');
    check(horizonRows().some(x=>x.textContent.includes('2026-11-12 — 1')),'day four has second invented due date');
    check(horizonButtons.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'one UTC horizon control selected');
    horizonButtons.find(x=>x.dataset.followupHorizon==='1').click();
    check(horizonRows().length===1&&horizonSummary.textContent.includes('1 fora da janela'),'one-day UTC horizon can be restored');
    const followUpPageSizes=Array.from(document.querySelectorAll('#screen-monitor [data-followup-page-size]'));
    const followUpPrev=document.getElementById('followup-page-prev');
    const followUpNext=document.getElementById('followup-page-next');
    const followUpPageStatus=document.getElementById('followup-page-summary');
    const followUpRows=()=>Array.from(document.querySelectorAll('#followup-fixture-list li'));
    check(followUpPageSizes.length===2&&followUpRows().length===2,'two UTC fixture positions initially visible');
    check(followUpRows()[0].textContent.includes('Exemplo fictício 2')&&followUpRows()[1].textContent.includes('Exemplo fictício 5'),'upcoming fixtures remain in stable due-date order');
    followUpPageSizes.find(x=>x.dataset.followupPageSize==='1').click();
    check(followUpRows().length===1&&followUpPrev.disabled&&!followUpNext.disabled,'one-per-page future worklist and next enabled');
    check(followUpPageStatus.textContent.includes('Página 1 de 2'),'first future page announced');
    followUpNext.click();
    check(followUpRows()[0].textContent.includes('Exemplo fictício 5')&&!followUpPrev.disabled&&followUpNext.disabled,'next future page shows second anonymous fixture');
    check(followUpPageStatus.textContent.includes('Página 2 de 2'),'second future page announced');
    followUpPageSizes.find(x=>x.dataset.followupPageSize==='2').click();
    check(followUpRows().length===2&&followUpPrev.disabled&&followUpNext.disabled,'restore full upcoming worklist');
    check(followUpButtons.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'one selected follow-up mode after click');
    followUpButtons.find(x=>x.dataset.followupCase==='completed').click();
    check(followUpStatus.textContent.includes('concluída')&&followUpStatus.textContent.includes('1 de 5'),'completed synthetic follow-up presented');
    followUpButtons.find(x=>x.dataset.followupCase==='overdue').click();
    check(followUpStatus.textContent.includes('Prazo ultrapassado'),'default follow-up category restored');
    check(!document.querySelector('#screen-monitor form,#screen-monitor input'),'follow-ups never request personal data');

    const leads=nav.find(x=>x.dataset.page==='leads');
    const pipeline=nav.find(x=>x.dataset.page==='pipeline');
    leads.focus();
    leads.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true,cancelable:true}));
    check(document.activeElement===pipeline,'arrow moves focus');
    check(document.getElementById('page-title').textContent==='Monitoramento de leads','arrow does not activate');

    pipeline.dispatchEvent(new KeyboardEvent('keydown',{key:'End',bubbles:true,cancelable:true}));
    check(document.activeElement===nav[8],'End focuses last');
    nav[8].dispatchEvent(new KeyboardEvent('keydown',{key:'Home',bubbles:true,cancelable:true}));
    check(document.activeElement===nav[0],'Home focuses first');

    // Exercise the fixture-only stage filter in the real narrow browser.
    leads.click();
    check(document.getElementById('page-title').textContent==='Leads fictícios','open lead screen');
    const filterButtons=Array.from(document.querySelectorAll('[data-lead-stage]'));
    const rows=Array.from(document.querySelectorAll('#screen-leads [data-lead-fixture-stage]'));
    const visible=()=>rows.filter(x=>!x.hidden).length;
    check(filterButtons.length===4&&rows.length===4,'stage filter fixture cardinality');
    check(visible()===4,'default stage shows all');
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    check(visible()===2,'entry stage count');
    check(document.getElementById('lead-filter-summary').textContent.includes('2 de 4'),'live count updates');
    filterButtons.find(x=>x.dataset.leadStage==='em-contato').click();
    check(visible()===1,'contact stage count');
    filterButtons.find(x=>x.dataset.leadStage==='qualificacao').click();
    check(visible()===1,'qualification stage count');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    check(visible()===4,'all stage restores every fixture');
    // F3-38: exercise purely local pagination, including disabled boundary controls.
    const pageSizeButtons=Array.from(document.querySelectorAll('#screen-leads [data-lead-page-size]'));
    const previousPage=document.getElementById('lead-page-prev');
    const nextPage=document.getElementById('lead-page-next');
    const pageSummary=document.getElementById('lead-page-summary');
    check(pageSizeButtons.length===2&&previousPage.disabled&&nextPage.disabled,'four demo rows start on a single page');
    pageSizeButtons.find(x=>x.dataset.leadPageSize==='2').click();
    check(visible()===2&&previousPage.disabled&&!nextPage.disabled,'two-per-page preview with next enabled');
    check(pageSummary.textContent.includes('Página 1 de 2'),'first demo page announced');
    nextPage.click();
    check(visible()===2&&!previousPage.disabled&&nextPage.disabled,'second demo page boundaries');
    check(pageSummary.textContent.includes('Página 2 de 2'),'second page announced');
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    check(visible()===2&&previousPage.disabled&&nextPage.disabled,'filter resets pagination');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    pageSizeButtons.find(x=>x.dataset.leadPageSize==='4').click();
    check(visible()===4&&previousPage.disabled&&nextPage.disabled,'default four-row view restored');
    // Validate real Chromium DOM sorting without any lead mutation or API.
    const sortButtons=Array.from(document.querySelectorAll('#screen-leads [data-lead-sort]'));
    const order=()=>Array.from(document.querySelectorAll('#screen-leads tbody tr')).map(x=>x.querySelector('strong').textContent);
    const fixtures=['Lead Exemplo A','Lead Exemplo B','Lead Exemplo C','Lead Exemplo D'];
    check(sortButtons.length===3&&JSON.stringify(order())===JSON.stringify(fixtures),'original fixture order');
    sortButtons.find(x=>x.dataset.leadSort==='high').click();
    check(JSON.stringify(order())===JSON.stringify(['Lead Exemplo C','Lead Exemplo D','Lead Exemplo B','Lead Exemplo A']),'descending score order');
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    check(visible()===2,'filter remains functional after sorting');
    check(document.getElementById('lead-sort-summary').textContent.includes('maior pontuação'),'sort live feedback');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    sortButtons.find(x=>x.dataset.leadSort==='low').click();
    check(JSON.stringify(order())===JSON.stringify(['Lead Exemplo A','Lead Exemplo B','Lead Exemplo D','Lead Exemplo C']),'ascending score order');
    sortButtons.find(x=>x.dataset.leadSort==='original').click();
    check(JSON.stringify(order())===JSON.stringify(fixtures),'restore original order');
    check(document.querySelectorAll('[data-lead-sort][aria-pressed="true"]').length===1,'one selected sort');
    check(document.querySelectorAll('[data-lead-stage][aria-pressed="true"]').length===1,'one pressed filter');

    // UX-F3-29: stage + temperature are independent, intersected fake filters.
    const temperatureButtons=Array.from(document.querySelectorAll('#screen-leads [data-lead-temperature]'));
    check(temperatureButtons.length===4,'four static temperature filter buttons');
    check(document.querySelectorAll('[data-lead-temperature][aria-pressed="true"]').length===1,'one temperature selected');
    temperatureButtons.find(x=>x.dataset.leadTemperature==='hot').click();
    check(visible()===2,'two fictional hot leads');
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    check(visible()===1&&!rows.find(x=>x.dataset.leadFixtureTemperature==='hot'&&x.dataset.leadFixtureStage==='entrada').hidden,'temperature and stage intersection');
    filterButtons.find(x=>x.dataset.leadStage==='qualificacao').click();
    temperatureButtons.find(x=>x.dataset.leadTemperature==='warm').click();
    check(visible()===0,'conflicting filters yield no invented rows');
    check(document.getElementById('lead-filter-summary').textContent.includes('0 de 4'),'empty intersection announced');
    const emptyState=document.getElementById('lead-empty-filter-state');
    const clearFilters=document.getElementById('lead-clear-filters');
    check(!emptyState.hidden&&emptyState.textContent.includes('Nenhum lead fictício corresponde'),'empty result has clear explanation');
    check(clearFilters.type==='button','reset never submits a form');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    check(visible()===1,'temperature persists when stage resets');
    temperatureButtons.find(x=>x.dataset.leadTemperature==='all').click();
    check(visible()===4,'all synthetic records restored');
    check(document.querySelectorAll('[data-lead-temperature][aria-pressed="true"]').length===1,'one selected temperature after reset');
    check(emptyState.hidden,'empty result is removed when examples return');
    filterButtons.find(x=>x.dataset.leadStage==='qualificacao').click();
    temperatureButtons.find(x=>x.dataset.leadTemperature==='warm').click();
    check(!emptyState.hidden&&visible()===0,'reset action is available on empty combination');
    clearFilters.click();
    check(emptyState.hidden&&visible()===4,'reset restores all four fixtures');
    check(document.activeElement===filterButtons.find(x=>x.dataset.leadStage==='all'),'reset restores focus to visible filter');
    check(document.querySelectorAll('[data-lead-stage][aria-pressed="true"]').length===1&&document.querySelectorAll('[data-lead-temperature][aria-pressed="true"]').length===1,'reset preserves one active button in both groups');

    // UX-F3-26: fixture review follows sorting and fails closed on hidden rows.
    const detailButtons=Array.from(document.querySelectorAll('#screen-leads [data-lead-detail]'));
    const detailPanel=document.getElementById('lead-detail-preview');
    check(detailButtons.length===4&&detailPanel&&detailPanel.hidden,'four accessible detail toggles and initially hidden panel');
    const first=detailButtons.find(x=>x.dataset.leadDetail==='a');
    const third=detailButtons.find(x=>x.dataset.leadDetail==='c');
    first.click();
    check(!detailPanel.hidden&&detailPanel.textContent.includes('Lead Exemplo A'),'first fixture opens details');
    check(first.getAttribute('aria-expanded')==='true','selected fixture aria-expanded');
    filterButtons.find(x=>x.dataset.leadStage==='qualificacao').click();
    check(detailPanel.hidden&&first.getAttribute('aria-expanded')==='false','hiding selected fixture closes panel');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    third.focus();
    third.click();
    check(document.activeElement===third,'detail selection retains button focus');
    check(detailPanel.textContent.includes('Pontuação fixa: 85'),'third fixture details');
    sortButtons.find(x=>x.dataset.leadSort==='low').click();
    check(!detailPanel.hidden&&detailPanel.textContent.includes('Lead Exemplo C'),'sorting keeps selected lead details');
    third.click();
    check(detailPanel.hidden&&document.querySelectorAll('[data-lead-detail][aria-expanded="true"]').length===0,'same control collapses details');
    first.click();
    check(!detailPanel.hidden,'reopen detail');
    check(!document.querySelector('form,input,textarea'),'detail is not editable');
    check(detailPanel.textContent.includes('não autoriza exportação'),'detail cannot authorize export');

    // UX-F3-27: event lists remain fixture-only and follow selected identity.
    const timeline=()=>Array.from(detailPanel.querySelectorAll('ol.fixture-history li')).map(x=>x.textContent);
    check(timeline().length===2,'two invented events for first fixture');
    check(timeline()[1]==='Amostra aguardando revisão visual.','first fixture event text');
    third.click();
    check(timeline().length===3,'three invented events for third fixture');
    check(timeline().some(x=>x.includes('Qualificação')),'qualification fixture timeline');
    check(!timeline().some(x=>x.includes('aguardando revisão visual')),'no stale A events in C');
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    check(detailPanel.hidden&&detailPanel.querySelectorAll('li').length===0,'hidden fixture clears timeline');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    first.click();
    check(timeline().length===2&&!timeline().some(x=>x.includes('Qualificação')),'A timeline restored without C');
    first.click();
    check(detailPanel.hidden&&timeline().length===0,'closing detail clears timeline');
    check(!document.querySelector('form,input,textarea'),'timeline is read only');

    // A changed temperature must clear details and old fictitious timeline.
    first.click();
    check(!detailPanel.hidden&&timeline().length===2,'reopen A before temperature change');
    temperatureButtons.find(x=>x.dataset.leadTemperature==='hot').click();
    check(detailPanel.hidden&&timeline().length===0&&first.getAttribute('aria-expanded')==='false','temperature filter clears hidden details');
    check(visible()===2,'temperature filter stays applied until explicit reset');

    // UX-F3-28: open a static lead detail directly from a pipeline card.
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    nav.find(x=>x.dataset.page==='pipeline').click();
    const pipelineReview=Array.from(document.querySelectorAll('#screen-pipeline [data-pipeline-review]'));
    check(pipelineReview.length===4,'four pipeline review buttons');
    pageSizeButtons.find(x=>x.dataset.leadPageSize==='2').click();
    pipelineReview.find(x=>x.dataset.pipelineReview==='c').click();
    check(document.getElementById('page-title').textContent==='Leads fictícios','pipeline review opens leads page');
    check(document.querySelectorAll('[data-screen]:not([hidden])').length===1,'only leads page active after pipeline navigation');
    check(document.activeElement===detailButtons.find(x=>x.dataset.leadDetail==='c'),'focus moved to matching lead button');
    check(document.getElementById('lead-filter-summary').textContent.includes('4 de 4'),'pipeline review resets stage filter');
    check(detailPanel.textContent.includes('Lead Exemplo C')&&detailPanel.textContent.includes('Qualificação'),'pipeline review opens matching fixture');
    check(pageSummary.textContent.includes('Página 2 de 2')&&!detailButtons.find(x=>x.dataset.leadDetail==='c').closest('tr').hidden,'pipeline review opens fixture on correct second page');
    nav.find(x=>x.dataset.page==='pipeline').click();
    check(detailPanel.hidden,'leaving lead review hides previous detail');
    pipelineReview.find(x=>x.dataset.pipelineReview==='a').click();
    check(detailPanel.textContent.includes('Lead Exemplo A')&&!detailPanel.textContent.includes('Lead Exemplo C'),'pipeline review never carries stale lead');
    check(document.querySelectorAll('[data-lead-detail][aria-expanded="true"]').length===1,'only one lead expanded after pipeline navigation');
    check(pageSummary.textContent.includes('Página 1 de 2')&&!detailButtons.find(x=>x.dataset.leadDetail==='a').closest('tr').hidden,'pipeline review returns to first page');
    pageSizeButtons.find(x=>x.dataset.leadPageSize==='4').click();
    check(!document.querySelector('form,input,textarea'),'pipeline review has no form or mutation');

    // Static quota meters are an isolated visual example, never entitlement.
    const dashboard=nav.find(x=>x.dataset.page==='dashboard');
    dashboard.click();
    const meters=Array.from(document.querySelectorAll('#screen-dashboard [data-quota-fixture] meter'));
    check(meters.length===3,'three fictional quota meters');
    check(JSON.stringify(meters.map(x=>[x.value,x.max]))===JSON.stringify([[4,20],[1,2],[0,2]]),'synthetic quota meter values');
    check(document.getElementById('quota-demo-title').textContent==='Franquias demonstrativas','quota demo heading');
    check(document.querySelectorAll('[data-screen]:not([hidden])').length===1,'dashboard isolated from other screens');
    
    // The three offline mapping scenarios are only hard-coded DOM text.
    const importPage=nav.find(x=>x.dataset.page==='import');
    importPage.click();
    const mapButtons=Array.from(document.querySelectorAll('#screen-import [data-import-mapping]'));
    const mapPanel=document.getElementById('import-mapping-preview');
    check(mapButtons.length===3&&mapPanel!==null,'mapping scenarios exist');
    check(mapPanel.textContent.includes('Três correspondências ilustrativas'),'mapping initial state');
    mapButtons.find(x=>x.dataset.importMapping==='partial').click();
    check(mapPanel.textContent.includes('1 de 3 colunas fictícias'),'mapping partial fixture');
    check(document.querySelectorAll('[data-import-mapping][aria-pressed="true"]').length===1,'one mapping button selected');
    mapButtons.find(x=>x.dataset.importMapping==='invalid').click();
    check(mapPanel.textContent.includes('Estrutura de mapeamento inválida'),'mapping invalid fixture');
    mapButtons.find(x=>x.dataset.importMapping==='complete').click();
    check(mapPanel.textContent.includes('Nenhuma importação foi autorizada ou realizada'),'mapping remains denied');
    check(!document.querySelector('form,input,textarea'),'still no upload or entry forms');
    const reviewButtons=Array.from(document.querySelectorAll('#screen-import [data-import-review]'));
    const reviewPanel=document.getElementById('import-review-preview');
    check(reviewButtons.length===3&&reviewPanel!==null,'three offline review cases');
    check(reviewPanel.textContent.includes('Nenhum aviso identificado neste exemplo'),'review initial state');
    reviewButtons.find(x=>x.dataset.importReview==='formula').click();
    check(reviewPanel.textContent.includes('1 célula com possível fórmula'),'formula review fixture');
    reviewButtons.find(x=>x.dataset.importReview==='empty').click();
    check(reviewPanel.textContent.includes('0 linhas de dados'),'empty dataset fixture');
    reviewButtons.find(x=>x.dataset.importReview==='clear').click();
    check(reviewPanel.textContent.includes('A ausência de alertas não comprova'),'zero findings never grants import');
    check(reviewPanel.textContent.includes('Nenhuma importação foi autorizada ou realizada'),'denial persists');
    check(document.querySelectorAll('[data-import-review][aria-pressed="true"]').length===1,'one selected review case');

    // Access boundary scenarios are hard-coded and cannot export or authorize.
    const accessButtons=Array.from(document.querySelectorAll('#screen-import [data-access-scenario]'));
    const accessPanel=document.getElementById('access-boundary-preview');
    check(accessButtons.length===3&&accessPanel!==null,'three offline access boundary cases');
    check(accessPanel.textContent.includes('Visualizar não autoriza exportar'),'read does not imply export');
    accessButtons.find(x=>x.dataset.accessScenario==='cross-tenant').click();
    check(accessPanel.textContent.includes('Outra organização — acesso não comprovado'),'cross-tenant boundary fixture');
    check(document.querySelectorAll('[data-access-scenario][aria-pressed="true"]').length===1,'one active access scenario');
    accessButtons.find(x=>x.dataset.accessScenario==='revoked').click();
    check(accessPanel.textContent.includes('Vínculo alterado — revalidação necessária'),'revocation boundary fixture');
    accessButtons.find(x=>x.dataset.accessScenario==='read-only').click();
    check(accessPanel.textContent.includes('Nenhuma exportação foi autorizada ou realizada'),'all cases deny export');
    check(!document.querySelector('form,input,textarea'),'no export or input form');
    // REC-LAB-04: compare three hard-coded, NON-AUTHORITATIVE backup labels.
    const backupButtons=Array.from(document.querySelectorAll('#screen-import [data-backup-check]'));
    const backupPanel=document.getElementById('backup-integrity-preview');
    check(backupButtons.length===3&&backupPanel!==null,'three offline integrity scenarios');
    check(backupPanel.textContent.includes('SELF_CLAIM_MATCH_UNTRUSTED'),'default self-declared match is not a proof');
    check(backupPanel.textContent.includes('Nenhum backup foi criado ou restaurado'),'no restore action in default fixture');
    backupButtons.find(x=>x.dataset.backupCheck==='mismatch').click();
    check(backupPanel.textContent.includes('SELF_CLAIM_MISMATCH'),'tampered fixture is rejected');
    backupButtons.find(x=>x.dataset.backupCheck==='forged').click();
    check(backupPanel.textContent.includes('quem controla os bytes pode recalcular'),'forged self-claim remains untrusted');
    check(backupButtons.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'only one backup scenario pressed');
    backupButtons.find(x=>x.dataset.backupCheck==='consistent').click();
    check(backupPanel.textContent.includes('Nenhuma operação foi autorizada'),'backup preview never changes authority');
    check(!document.querySelector('form,input,textarea'),'backup demo has no upload, file or restore form');

    const skip=document.querySelector('.skip-link');
    check(skip&&skip.getAttribute('href')==='#conteudo','skip link exists');
    skip.focus();
    check(document.activeElement===skip,'skip link is focusable');
    skip.click();
    check(location.hash==='#conteudo','skip navigates to main anchor');

    const menu=document.getElementById('demo-navigation');
    check(window.innerWidth<=900,'narrow viewport used');
    check(getComputedStyle(menu).overflowX==='auto','mobile navigation is scrollable');
    check(getComputedStyle(document.querySelector('.mobile-nav-hint')).display!=='none','mobile hint visible');
    const visibleWidth=window.innerWidth, docWidth=document.documentElement.scrollWidth;
    if(docWidth>visibleWidth){
      const culprits=Array.from(document.querySelectorAll('body *')).map(el=>{
        const r=el.getBoundingClientRect();
        return {name:el.tagName.toLowerCase()+(el.className && typeof el.className==='string'?'.'+el.className.split(' ').join('.'):''),
          right:Math.round(r.right),width:Math.round(r.width)};
      }).filter(x=>x.right>visibleWidth+1).slice(0,6);
      throw new Error('horizontal overflow '+docWidth+'/'+visibleWidth+' culprits '+JSON.stringify(culprits));
    }

    check(!document.querySelector('form,input,textarea'),'no data-entry form');
    output.setAttribute('data-imagine-browser-smoke','PASS');
    output.textContent='UX-F3-12 PASS: offline keyboard/mobile interaction';
  }catch(error){
    output.setAttribute('data-imagine-browser-smoke','FAIL');
    output.textContent='UX-F3-12 FAIL: '+error.message;
  }
})();
JS;

$temp = sys_get_temp_dir().'/imagine-crm-ux-'.bin2hex(random_bytes(12)).'.html';
if (file_put_contents($temp, str_replace('</body>', '<script>'.$testScript.'</script></body>', $html)) === false) {
    fwrite(STDERR, "UX-F3-12: temporary fixture creation failed.\n");
    exit(1);
}

try {
    $result = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=520,780', '--virtual-time-budget=1500'],
        'file://'.$temp,
        'data-imagine-browser-smoke',
    );
    if (!$result['passed']) {
        fwrite(STDERR, 'UX-F3-12 browser failure: '.$result['reason']
            .' (attempts '.$result['attempts'].')'.PHP_EOL);
        exit(1);
    }

    echo "UX-F3-12 PASS: real headless browser, narrow viewport, nav/keyboard/skip link; offline prototype only.\n";
} finally {
    @unlink($temp);
}
