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

    // UX-F3-28: open a static lead detail directly from a pipeline card.
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    nav.find(x=>x.dataset.page==='pipeline').click();
    const pipelineReview=Array.from(document.querySelectorAll('#screen-pipeline [data-pipeline-review]'));
    check(pipelineReview.length===4,'four pipeline review buttons');
    pipelineReview.find(x=>x.dataset.pipelineReview==='c').click();
    check(heading.textContent==='Leads fictícios','pipeline review opens leads page');
    check(document.querySelectorAll('[data-screen]:not([hidden])').length===1,'only leads page active after pipeline navigation');
    check(document.activeElement===detailButtons.find(x=>x.dataset.leadDetail==='c'),'focus moved to matching lead button');
    check(document.getElementById('lead-filter-summary').textContent.includes('4 de 4'),'pipeline review resets stage filter');
    check(detailPanel.textContent.includes('Lead Exemplo C')&&detailPanel.textContent.includes('Qualificação'),'pipeline review opens matching fixture');
    nav.find(x=>x.dataset.page==='pipeline').click();
    check(detailPanel.hidden,'leaving lead review hides previous detail');
    pipelineReview.find(x=>x.dataset.pipelineReview==='a').click();
    check(detailPanel.textContent.includes('Lead Exemplo A')&&!detailPanel.textContent.includes('Lead Exemplo C'),'pipeline review never carries stale lead');
    check(document.querySelectorAll('[data-lead-detail][aria-expanded="true"]').length===1,'only one lead expanded after pipeline navigation');
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
