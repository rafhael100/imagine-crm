<?php

declare(strict_types=1);

// UX-F3-13: run the synthetic CRM demo in an actual 320-CSS-pixel iframe
// viewport inside headless Chromium. Parent driver loads only local srcdoc.
// No web server, network requests, credentials, real data or deployment.

require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

$html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
if (!is_string($html)
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || strlen($html) > 100000) {
    fwrite(STDERR, "UX-F3-13: unexpected local demo or missing offline CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $candidate) {
    $browser = $finder->find($candidate);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "UX-F3-13: no Chrome/Chromium; browser QA NOT performed.\n");
    exit(1);
}

// JSON_HEX_TAG ensures the embedded prototype's </script> cannot terminate
// the parent driver's script. The child's CSP still applies inside srcdoc.
$serialized = json_encode(
    $html,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Imagine UX 320 browser test</title></head>
<body>
<output id="smoke320" data-imagine-320="RUNNING"></output>
<script>
(function(){
  const marker=document.getElementById('smoke320');
  const source=__PROTO_STRING__;
  const frame=document.createElement('iframe');
  frame.setAttribute('title','Protótipo Imagine em 320 pixels');
  frame.setAttribute('width','320');
  frame.setAttribute('height','740');
  frame.style.cssText='border:0;display:block;width:320px;height:740px';
  function check(ok,message){if(!ok)throw new Error(message);}
  let done=false;
  function fail(message){
    if(done)return;done=true;
    marker.setAttribute('data-imagine-320','FAIL');
    marker.textContent='UX-F3-13 FAIL: '+message;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument;
      const win=frame.contentWindow;
      check(doc&&win,'same-origin srcdoc frame');
      check(win.innerWidth===320,'actual CSS viewport '+win.innerWidth+' not 320');
      check(doc.querySelectorAll('[data-screen]').length===9,'all nine screens exist');
      const pageWidth=doc.documentElement.scrollWidth;
      if(pageWidth>win.innerWidth){
        const suspects=Array.from(doc.querySelectorAll('body *')).map(el=>{
          const rect=el.getBoundingClientRect();
          return {tag:el.tagName.toLowerCase(),className:typeof el.className==='string'?el.className:'',
            right:Math.round(rect.right)};
        }).filter(item=>item.right>win.innerWidth+1).slice(0,4);
        throw new Error('page width '+pageWidth+'/320, suspects '+JSON.stringify(suspects));
      }
      const nav=doc.getElementById('demo-navigation');
      check(nav!==null,'navigation exists');
      check(win.getComputedStyle(nav).overflowX==='auto','navigation scrolls inside sidebar');
      check(win.getComputedStyle(doc.querySelector('.mobile-nav-hint')).display!=='none','mobile instructions visible');

      const buttons=Array.from(doc.querySelectorAll('#demo-navigation button[data-page]'));
      check(buttons.length===9,'all navigation buttons exist');
      const leads=buttons.find(node=>node.getAttribute('data-page')==='leads');
      const pipeline=buttons.find(node=>node.getAttribute('data-page')==='pipeline');
      const monitor=buttons.find(node=>node.getAttribute('data-page')==='monitor');
      monitor.click();
      check(doc.getElementById('page-title').textContent==='Monitoramento de leads','page switch at 320');
      check(doc.activeElement===doc.getElementById('page-title'),'heading focus');
      check(doc.querySelectorAll('[data-screen]:not([hidden])').length===1,'one active screen');
      // F4-01: fixed deadline preview has working accessible buttons in 320px.
      const followUps=Array.from(doc.querySelectorAll('#screen-monitor [data-followup-case]'));
      const followUpPanel=doc.getElementById('followup-demo-preview');
      check(followUps.length===4&&followUpPanel.textContent.includes('1 de 5'),'four synthetic activity states on mobile');
      followUps.find(node=>node.dataset.followupCase==='due-now').click();
      check(followUpPanel.textContent.includes('Igualdade exata de segundos UTC'),'mobile exact-instant activity sample');
      followUps.find(node=>node.dataset.followupCase==='upcoming').click();
      check(followUpPanel.textContent.includes('2 de 5'),'mobile future activity sample');
      const horizonButtons=Array.from(doc.querySelectorAll('#screen-monitor [data-followup-horizon]'));
      const horizonStatus=doc.getElementById('followup-horizon-summary');
      const horizonDaily=doc.getElementById('followup-horizon-daily');
      check(horizonButtons.length===2&&horizonDaily.querySelectorAll('li').length===1,'one-day synthetic UTC horizon at 320px');
      check(horizonStatus.textContent.includes('1 de 2 exemplos futuros dentro da janela'),'one UTC future item at 320px');
      horizonButtons.find(x=>x.dataset.followupHorizon==='7').click();
      check(horizonDaily.querySelectorAll('li').length===7&&horizonStatus.textContent.includes('2 de 2 exemplos futuros dentro da janela'),'seven-day UTC horizon at 320px');
      check(horizonDaily.textContent.includes('2026-11-12 — 1'),'mobile date breakdown matches fixture');
      check(horizonButtons.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'one horizon selected on mobile');
      horizonButtons.find(x=>x.dataset.followupHorizon==='1').click();
      check(doc.documentElement.scrollWidth<=320,'UTC horizon does not cause horizontal overflow');
      const followUpPageSizes=Array.from(doc.querySelectorAll('#screen-monitor [data-followup-page-size]'));
      const followUpPrev=doc.getElementById('followup-page-prev');
      const followUpNext=doc.getElementById('followup-page-next');
      const followUpList=doc.getElementById('followup-fixture-list');
      check(followUpPageSizes.length===2&&followUpList.querySelectorAll('li').length===2,'mobile future worklist starts with two fixtures');
      followUpPageSizes.find(node=>node.dataset.followupPageSize==='1').click();
      check(followUpList.querySelectorAll('li').length===1&&followUpPrev.disabled&&!followUpNext.disabled,'mobile one per page state');
      followUpNext.click();
      check(followUpList.textContent.includes('Exemplo fictício 5')&&!followUpPrev.disabled&&followUpNext.disabled,'mobile next page opens correct synthetic activity');
      followUps.find(node=>node.dataset.followupCase==='overdue').click();
      check(followUpList.textContent.includes('Exemplo fictício 1')&&followUpPrev.disabled&&followUpNext.disabled,'mobile category switch resets page');
      followUpPageSizes.find(node=>node.dataset.followupPageSize==='2').click();
      check(followUps.filter(node=>node.getAttribute('aria-pressed')==='true').length===1,'single selected activity state on mobile');
      check(doc.documentElement.scrollWidth<=320,'activity demonstration does not overflow 320px');
      followUps.find(node=>node.dataset.followupCase==='overdue').click();
      leads.focus();
      leads.dispatchEvent(new win.KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true,cancelable:true}));
      check(doc.activeElement===pipeline,'arrow focus at 320');
      check(doc.getElementById('page-title').textContent==='Monitoramento de leads','no implicit activation');
      // Stage filtering is demo-only, but its DOM behavior must work in 320px.
      leads.click();
      const stageButtons=Array.from(doc.querySelectorAll('#screen-leads [data-lead-stage]'));
      const leadRows=Array.from(doc.querySelectorAll('#screen-leads [data-lead-fixture-stage]'));
      const count=()=>leadRows.filter(node=>!node.hidden).length;
      check(stageButtons.length===4&&leadRows.length===4,'four stage controls and demo records');
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      check(count()===2,'two synthetic entries in 320px');
      check(doc.getElementById('lead-filter-summary').textContent.includes('2 de 4'),'filter status announces count');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      check(count()===4,'all synthetic entries restored');
      // F3-38: paged synthetic fixtures remain usable at 320 CSS pixels.
      const pageSizes=Array.from(doc.querySelectorAll('#screen-leads [data-lead-page-size]'));
      const prevPage=doc.getElementById('lead-page-prev');
      const nextPage=doc.getElementById('lead-page-next');
      check(pageSizes.length===2&&prevPage.disabled&&nextPage.disabled,'one default page at 320');
      pageSizes.find(node=>node.dataset.leadPageSize==='2').click();
      check(count()===2&&prevPage.disabled&&!nextPage.disabled,'two demo rows visible at 320');
      nextPage.click();
      check(count()===2&&!prevPage.disabled&&nextPage.disabled,'mobile next page advances');
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      check(count()===2&&prevPage.disabled&&nextPage.disabled,'mobile filter resets page');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      pageSizes.find(node=>node.dataset.leadPageSize==='4').click();
      check(count()===4,'mobile pagination restores baseline rows');
      // Fixture-only score ordering and stage filtering must coexist at 320px.
      const sortButtons=Array.from(doc.querySelectorAll('#screen-leads [data-lead-sort]'));
      const order=()=>Array.from(doc.querySelectorAll('#screen-leads tbody tr')).map(node=>node.querySelector('strong').textContent);
      check(sortButtons.length===3,'three accessible local sort buttons');
      sortButtons.find(node=>node.dataset.leadSort==='high').click();
      check(JSON.stringify(order())===JSON.stringify(['Lead Exemplo C','Lead Exemplo D','Lead Exemplo B','Lead Exemplo A']),'descending scores in 320px');
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      check(count()===2,'filtered count preserved after ordering');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      sortButtons.find(node=>node.dataset.leadSort==='original').click();
      check(JSON.stringify(order())===JSON.stringify(['Lead Exemplo A','Lead Exemplo B','Lead Exemplo C','Lead Exemplo D']),'original order restored in 320px');
      // UX-F3-29: intersect fake temperature and stage without horizontal overflow.
      const temperatureButtons=Array.from(doc.querySelectorAll('#screen-leads [data-lead-temperature]'));
      check(temperatureButtons.length===4,'four band controls on mobile');
      temperatureButtons.find(node=>node.dataset.leadTemperature==='hot').click();
      check(count()===2,'two hot synthetic leads at 320px');
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      check(count()===1,'intersection of entry and hot on mobile');
      stageButtons.find(node=>node.dataset.leadStage==='qualificacao').click();
      temperatureButtons.find(node=>node.dataset.leadTemperature==='warm').click();
      check(count()===0,'empty mixed filter at 320px');
      check(doc.getElementById('lead-filter-summary').textContent.includes('0 de 4'),'screen reader count for empty intersection');
      const emptyState=doc.getElementById('lead-empty-filter-state');
      const clearFilters=doc.getElementById('lead-clear-filters');
      check(!emptyState.hidden&&emptyState.textContent.includes('Nenhum lead fictício corresponde'),'empty guidance displayed at 320px');
      check(clearFilters.type==='button','mobile reset has no form submission');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      check(count()===1,'stage reset preserves temperature on mobile');
      temperatureButtons.find(node=>node.dataset.leadTemperature==='all').click();
      check(count()===4,'mobile filter reset shows four demo rows');
      check(doc.documentElement.scrollWidth<=320,'combined filters do not overflow page');
      check(emptyState.hidden,'mobile empty guidance disappears after valid results');
      stageButtons.find(node=>node.dataset.leadStage==='qualificacao').click();
      temperatureButtons.find(node=>node.dataset.leadTemperature==='warm').click();
      check(!emptyState.hidden&&count()===0,'mobile reset offered when no matches');
      clearFilters.click();
      check(count()===4&&emptyState.hidden,'mobile reset restores all rows');
      check(doc.activeElement===stageButtons.find(node=>node.dataset.leadStage==='all'),'mobile reset moves focus to visible all-stages button');
      check(doc.documentElement.scrollWidth<=320,'mobile reset state does not overflow');

      // UX-F3-26: real 320px keyboard-friendly fixture review.
      const detailButtons=Array.from(doc.querySelectorAll('#screen-leads [data-lead-detail]'));
      const detailPanel=doc.getElementById('lead-detail-preview');
      check(detailButtons.length===4&&detailPanel.hidden,'four collapsed detail buttons on mobile');
      const first=detailButtons.find(node=>node.dataset.leadDetail==='a');
      const third=detailButtons.find(node=>node.dataset.leadDetail==='c');
      first.click();
      check(!detailPanel.hidden&&detailPanel.textContent.includes('Lead Exemplo A'),'open sample detail at 320');
      check(first.getAttribute('aria-expanded')==='true','mobile expanded flag');
      stageButtons.find(node=>node.dataset.leadStage==='qualificacao').click();
      check(detailPanel.hidden&&first.getAttribute('aria-expanded')==='false','mobile filter closes hidden selection');
      third.click();
      check(!detailPanel.hidden&&detailPanel.textContent.includes('Pontuação fixa: 85'),'qualification example opens');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      sortButtons.find(node=>node.dataset.leadSort==='low').click();
      check(detailPanel.textContent.includes('Lead Exemplo C'),'mobile sort preserves selected fixture');
      third.click();
      check(detailPanel.hidden,'mobile repeated click collapses');
      check(!doc.querySelector('form,input,textarea'),'no editor or uploader on mobile');
      // UX-F3-27: no event carryover or horizontal overflow in 320px.
      const timeline=()=>Array.from(detailPanel.querySelectorAll('ol.fixture-history li')).map(n=>n.textContent);
      first.click();
      check(timeline().length===2&&timeline()[1].includes('revisão visual'),'first timeline on 320px');
      third.click();
      check(timeline().length===3&&timeline().some(x=>x.includes('Qualificação')),'third timeline on 320px');
      check(!timeline().some(x=>x.includes('aguardando revisão visual')),'no stale first event on 320px');
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      check(detailPanel.hidden&&timeline().length===0,'filter clears timeline in 320px');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      first.click();
      check(timeline().length===2&&!timeline().some(x=>x.includes('Qualificação')),'first event slice restored in 320px');
      check(doc.documentElement.scrollWidth<=320,'no timeline overflow on mobile');
      check(doc.documentElement.scrollWidth<=320,'no horizontal page overflow on lead screen');
      // Temperature change also invalidates a previously expanded lead.
      temperatureButtons.find(node=>node.dataset.leadTemperature==='hot').click();
      check(detailPanel.hidden&&timeline().length===0,'mobile temperature change clears selected history');
      check(first.getAttribute('aria-expanded')==='false','mobile hidden lead is collapsed');
      check(doc.documentElement.scrollWidth<=320,'band filtering remains inside mobile viewport');

      // UX-F3-28: pipeline-to-detail review works with a 320px viewport.
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      pipeline.click();
      const pipelineReview=Array.from(doc.querySelectorAll('#screen-pipeline [data-pipeline-review]'));
      check(pipelineReview.length===4,'four pipeline review buttons on mobile');
      pageSizes.find(node=>node.dataset.leadPageSize==='2').click();
      check(doc.documentElement.scrollWidth<=320,'pipeline review buttons fit 320px');
      pipelineReview.find(node=>node.dataset.pipelineReview==='c').click();
      check(doc.getElementById('page-title').textContent==='Leads fictícios','mobile pipeline navigation to leads');
      check(doc.activeElement===detailButtons.find(node=>node.dataset.leadDetail==='c'),'mobile review moves focus to matching lead');
      check(doc.getElementById('lead-filter-summary').textContent.includes('4 de 4'),'mobile pipeline review restores all stage fixtures');
      check(!detailPanel.hidden&&detailPanel.textContent.includes('Lead Exemplo C'),'mobile pipeline opens correct lead');
      check(doc.getElementById('lead-page-summary').textContent.includes('Página 2 de 2')&&!detailButtons.find(node=>node.dataset.leadDetail==='c').closest('tr').hidden,'mobile pipeline selects correct second page');
      pipeline.click();
      check(detailPanel.hidden,'mobile pipeline navigation clears previous selection');
      pipelineReview.find(node=>node.dataset.pipelineReview==='d').click();
      check(detailPanel.textContent.includes('Lead Exemplo D')&&!detailPanel.textContent.includes('Lead Exemplo C'),'mobile pipeline review is isolated per fixture');
      check(doc.getElementById('lead-page-summary').textContent.includes('Página 2 de 2')&&!detailButtons.find(node=>node.dataset.leadDetail==='d').closest('tr').hidden,'mobile pipeline D remains focusable on second page');
      pageSizes.find(node=>node.dataset.leadPageSize==='4').click();
      check(doc.documentElement.scrollWidth<=320,'reviewed lead fits 320px');
      buttons.find(node=>node.dataset.page==='dashboard').click();
      const quotas=Array.from(doc.querySelectorAll('#screen-dashboard [data-quota-fixture] meter'));
      check(quotas.length===3,'three static quota meters on 320px');
      check(JSON.stringify(quotas.map(node=>[node.value,node.max]))===JSON.stringify([[4,20],[1,2],[0,2]]),'fictional quota readings on mobile');
      check(doc.documentElement.scrollWidth<=320,'no overflow in quota dashboard on 320px');
      buttons.find(node=>node.dataset.page==='import').click();
      const mapButtons=Array.from(doc.querySelectorAll('#screen-import [data-import-mapping]'));
      const mapPanel=doc.getElementById('import-mapping-preview');
      check(mapButtons.length===3&&mapPanel!==null,'mapping preview exists in 320px');
      mapButtons.find(node=>node.dataset.importMapping==='partial').click();
      check(mapPanel.textContent.includes('1 de 3 colunas fictícias'),'partial mapping on 320px');
      mapButtons.find(node=>node.dataset.importMapping==='invalid').click();
      check(mapPanel.textContent.includes('Estrutura de mapeamento inválida'),'invalid mapping on 320px');
      mapButtons.find(node=>node.dataset.importMapping==='complete').click();
      check(mapPanel.textContent.includes('Nenhuma importação foi autorizada ou realizada'),'mapping never imports on 320px');
      check(doc.documentElement.scrollWidth<=320,'no page overflow on mapping preview in 320px');
      const reviewButtons=Array.from(doc.querySelectorAll('#screen-import [data-import-review]'));
      const reviewPanel=doc.getElementById('import-review-preview');
      check(reviewButtons.length===3&&reviewPanel!==null,'offline review modes on 320px');
      reviewButtons.find(node=>node.dataset.importReview==='formula').click();
      check(reviewPanel.textContent.includes('1 célula com possível fórmula'),'formula finding at 320px');
      reviewButtons.find(node=>node.dataset.importReview==='empty').click();
      check(reviewPanel.textContent.includes('EMPTY_DATASET'),'empty data finding at 320px');
      reviewButtons.find(node=>node.dataset.importReview==='clear').click();
      check(reviewPanel.textContent.includes('Nenhuma importação foi autorizada ou realizada'),'no import rights at 320px');
      check(doc.documentElement.scrollWidth<=320,'no overflow on review cases at 320px');

      // UX-F3-25: one status region, three fixed denial cases, no export.
      const accessButtons=Array.from(doc.querySelectorAll('#screen-import [data-access-scenario]'));
      const accessPanel=doc.getElementById('access-boundary-preview');
      check(accessButtons.length===3&&accessPanel!==null,'access boundary in 320px');
      check(accessPanel.textContent.includes('Visualizar não autoriza exportar'),'read/export separation at 320px');
      accessButtons.find(node=>node.dataset.accessScenario==='cross-tenant').click();
      check(accessPanel.textContent.includes('Outra organização — acesso não comprovado'),'tenant isolation example at 320px');
      accessButtons.find(node=>node.dataset.accessScenario==='revoked').click();
      check(accessPanel.textContent.includes('Vínculo alterado — revalidação necessária'),'revocation example at 320px');
      accessButtons.find(node=>node.dataset.accessScenario==='read-only').click();
      check(accessPanel.textContent.includes('Nenhuma exportação foi autorizada ou realizada'),'no export rights at 320px');
      check(doc.querySelectorAll('[data-access-scenario][aria-pressed="true"]').length===1,'one pressed access scenario');
      check(doc.documentElement.scrollWidth<=320,'no horizontal overflow on access boundary at 320px');
      check(!doc.querySelector('form,input,textarea'),'no input or export controls');

      done=true;
      marker.setAttribute('data-imagine-320','PASS');
      marker.textContent='UX-F3-13 PASS: actual 320 CSS pixels, no page overflow, offline keyboard and nav';
    }catch(error){fail(error.message);}
  });
  frame.srcdoc=source;
  document.body.appendChild(frame);
  setTimeout(function(){if(!done)fail('iframe load timed out');},2200);
})();
</script>
</body></html>
HTML;
$driver = str_replace('__PROTO_STRING__', $serialized, $driver);
$temp = sys_get_temp_dir().'/imagine-crm-320-'.bin2hex(random_bytes(10)).'.html';
if (file_put_contents($temp, $driver) === false) {
    fwrite(STDERR, "UX-F3-13: could not create temporary 320px test fixture.\n");
    exit(1);
}

try {
    $result = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=600,800', '--virtual-time-budget=3000'],
        'file://'.$temp,
        'data-imagine-320',
    );
    if (!$result['passed']) {
        fwrite(STDERR, 'UX-F3-13 browser failure: '.$result['reason']
            .' (attempts '.$result['attempts'].')'.PHP_EOL);
        exit(1);
    }

    echo "UX-F3-13 PASS: 320px CSS viewport, keyboard interactions and no page overflow in real Chromium.\n";
} finally {
    @unlink($temp);
}
