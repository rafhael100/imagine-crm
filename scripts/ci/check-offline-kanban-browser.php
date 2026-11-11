<?php

declare(strict_types=1);

// F3-59: real Chrome/Chromium, with in-memory fictional CRM opportunities.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html');
if (!is_string($html)
    || strlen($html) > 25000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || !str_contains($html, "frame-src 'none'")) {
    fwrite(STDERR, "F3-59: invalid offline fixture/CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $binary) {
    $candidate = $finder->find($binary);
    if ($candidate !== null) {
        $browser = $candidate;
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "F3-59: real Chromium required; no mock PASS.\n");
    exit(1);
}

$checks = <<<'JS'
(function(){
  const output=document.createElement('output');
  output.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(output);
  function check(value,why){if(!value)throw new Error(why);}
  function count(n){return document.getElementById('lane-count-'+n).textContent.trim();}
  function visible(){return document.querySelectorAll('[data-card-id]').length;}
  function move(id,direction){
    const button=document.querySelector('[data-card-id="'+id+'"] button[data-move="'+direction+'"]');
    check(button&&!button.disabled,'available movement '+id+' '+direction);
    button.focus();
    button.click();
  }
  try{
    check(document.querySelector('main')!==null,'main landmark');
    check(document.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction banner');
    check(document.querySelectorAll('.lane').length===4,'exact four stages');
    check(visible()===8,'eight invented opportunities');
    check(['0','1','2','3'].every(n=>count(n)==='2'),'two invented opportunities per stage initially');
    check(['2.760','5.600','7.750','8.700'].every((sum,i)=>document.getElementById('lane-value-'+i).textContent.includes(sum)),'initial fictional values per stage');
    check(document.getElementById('count-visible').textContent==='8','initial metric');
    check(document.getElementById('count-high').textContent==='4','priority metric');
    check(document.getElementById('count-moves').textContent==='0','no edits initially');
    check(document.querySelector('[data-card-id="F01"] button[data-move="back"]').disabled,'first stage cannot move backward');
    check(document.querySelector('[data-card-id="F07"] button[data-move="next"]').disabled,'final stage cannot move forward');
    check(document.getElementById('undo-change').disabled,'undo starts disabled');
    const high=document.querySelector('[data-priority="high"]');
    high.focus();
    high.click();
    check(visible()===4&&document.getElementById('count-visible').textContent==='4','high filter has four cards');
    check(high.getAttribute('aria-pressed')==='true','high filter aria pressed');
    check(document.activeElement===high,'filter retains keyboard focus');
    check(document.querySelectorAll('[data-priority][aria-pressed="true"]').length===1,'single selected filter');
    check(['0','1','2','3'].every(n=>count(n)==='1'),'one high priority item per stage');
    check(['1.800','3.900','5.300','7.600'].every((sum,i)=>document.getElementById('lane-value-'+i).textContent.includes(sum)),'high priority stage amounts recalculate');
    document.querySelector('[data-priority="normal"]').click();
    check(visible()===4&&document.getElementById('count-high').textContent==='0','normal filter');
    document.querySelector('[data-priority="all"]').click();
    check(visible()===8&&document.getElementById('count-high').textContent==='4','all restores cards');
    move('F01','next');
    check(count(0)==='1'&&count(1)==='3','stage one updated');
    check(document.getElementById('lane-value-0').textContent.includes('960')&&document.getElementById('lane-value-1').textContent.includes('7.400'),'stage totals respond to movement');
    check(document.getElementById('count-moves').textContent==='1','movement counted');
    check(!document.getElementById('undo-change').disabled,'undo enabled');
    check(document.querySelector('[data-card-id="F01"]').closest('.lane').id==='', 'ticket remains semantic'); 
    check(document.querySelector('#lane-1 [data-card-id="F01"]')!==null,'F01 moved to contact');
    check(document.activeElement.closest('[data-card-id="F01"]')!==null,'focus follows moved card');
    move('F01','next');
    check(document.querySelector('#lane-2 [data-card-id="F01"]')!==null,'F01 moved to qualification');
    document.getElementById('undo-change').click();
    check(document.querySelector('#lane-1 [data-card-id="F01"]')!==null,'undo restored contact');
    document.getElementById('undo-change').click();
    check(document.querySelector('#lane-0 [data-card-id="F01"]')!==null,'second undo restored entry');
    check(document.getElementById('undo-change').disabled,'undo cleared after second reversal');
    check(document.getElementById('simulation-status').textContent.includes('Somente simulação local'),'status does not suggest real CRM change');
    move('F02','next');
    const stageOrder=()=>Array.from(document.querySelectorAll('#lane-1 [data-card-id]'))
      .map(card=>card.getAttribute('data-card-id'));
    check(JSON.stringify(stageOrder())===JSON.stringify(['F02','F03','F04']),'original stable fixture order after moving F02');
    const scoreSort=document.querySelector('[data-order="score"]');
    scoreSort.focus();
    scoreSort.click();
    check(scoreSort.getAttribute('aria-pressed')==='true','score ordering is pressed');
    check(document.activeElement===scoreSort,'score sort preserves keyboard focus');
    check(JSON.stringify(stageOrder())===JSON.stringify(['F03','F04','F02']),'higher scores sort before lower');
    document.querySelector('[data-order="default"]').click();
    check(JSON.stringify(stageOrder())===JSON.stringify(['F02','F03','F04']),'original insertion order restored');
    check(document.querySelectorAll('[data-order][aria-pressed="true"]').length===1,'sort group has one selected option');
    check(document.querySelectorAll('#activity-list li').length>=1,'actions recorded visibly');
    check(document.getElementById('activity-list').textContent.includes('Oportunidade fictícia 02'),'activity log includes only fictional action');
    document.getElementById('reset-demo').click();
    check(visible()===8&&['0','1','2','3'].every(n=>count(n)==='2'),'reset all four lanes');
    check(document.getElementById('count-moves').textContent==='0','reset movement count');
    check(document.getElementById('undo-change').disabled,'reset clears history');
    check(document.querySelectorAll('#activity-list li').length===1&&document.getElementById('activity-list').textContent.includes('Nenhuma ação'),'reset clears visible activity');
    check(document.querySelector('[data-order="default"]').getAttribute('aria-pressed')==='true','reset defaults to insertion order');
    check(document.querySelector('[data-priority="all"]').getAttribute('aria-pressed')==='true','reset priority');
    const add=document.getElementById('add-fiction');
    check(add&&!add.disabled,'fictional creation enabled');
    add.focus();
    add.click();
    check(visible()===9,'one invented opportunity created');
    check(document.querySelector('#lane-0 [data-card-id="F09"]')!==null,'new fictional card at entry');
    check(document.getElementById('lane-value-0').textContent.includes('4.610'),'new fictional card contributes only to entry total');
    check(document.activeElement.closest('[data-card-id="F09"]')!==null,'focus follows generated card');
    check(!document.getElementById('undo-change').disabled,'undo fictional add');
    add.click();
    add.click();
    add.click();
    check(visible()===12,'maximum twelve fictional cards');
    check(add.disabled,'limit rejects a thirteenth fictional card');
    document.getElementById('undo-change').click();
    check(visible()===11,'undo removes latest fictional creation');
    check(document.querySelectorAll('#activity-list li').length<=6,'history display remains bounded to six fictional actions');
    check(!add.disabled,'undo releases the bounded capacity');
    add.click();
    check(visible()===12,'capacity can be reused safely');
    document.getElementById('reset-demo').click();
    check(visible()===8,'reset removes all generated fictional cards');
    check(document.getElementById('lane-value-0').textContent.includes('2.760'),'reset restores stage value total');
    check(!add.disabled&&document.getElementById('undo-change').disabled,'reset clears creation history');

    check(document.documentElement.scrollWidth<=window.innerWidth+1,'desktop no page horizontal overflow');
    check(document.getElementById('count-pending').textContent==='6','six initially pending fictitious follow-ups');
    const followupFilter=selector=>document.querySelector('[data-followup-filter="'+selector+'"]');
    followupFilter('pending').focus();
    followupFilter('pending').click();
    check(visible()===6&&document.getElementById('count-pending').textContent==='6','only pending follow-ups');
    check(document.activeElement===followupFilter('pending'),'follow-up filter retains focus');
    document.querySelector('[data-priority="high"]').click();
    check(visible()===4,'priority and follow-up filters combine');
    document.querySelector('[data-priority="normal"]').click();
    check(visible()===2&&document.getElementById('count-pending').textContent==='2','normal pending intersection');
    followupFilter('done').click();
    check(visible()===2&&document.getElementById('count-pending').textContent==='0','completed normal subset');
    check(document.querySelectorAll('[data-followup-filter][aria-pressed="true"]').length===1,'follow-up filter single pressed state');
    document.querySelector('[data-priority="all"]').click();
    check(visible()===2&&document.querySelector('[data-card-id="F02"]')!==null,'two finished records initially');
    followupFilter('all').click();
    check(visible()===8,'all follow-up states restored');
    const toggle=document.querySelector('[data-card-id="F01"] button[data-followup]');
    check(toggle.textContent.includes('Concluir'),'initial pending action button');
    toggle.focus();
    toggle.click();
    check(document.getElementById('count-pending').textContent==='5','completion reduces pending count');
    check(document.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('concluído'),'completed status shown');
    check(document.activeElement===document.querySelector('[data-card-id="F01"] button[data-followup]'),'follow-up focus restored after card redraw');
    check(document.querySelector('[data-card-id="F01"] button[data-followup]').textContent.includes('Reabrir'),'reopen action after completion');
    document.getElementById('undo-change').click();
    check(document.getElementById('count-pending').textContent==='6','undo restores pending count');
    check(document.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('pendente'),'undo restores pending label');
    const addAgain=document.getElementById('add-fiction');
    addAgain.click();
    check(document.getElementById('count-pending').textContent==='7','generated card is pending');
    document.querySelector('[data-card-id="F09"] button[data-followup]').click();
    check(document.getElementById('count-pending').textContent==='6','generated card follow-up toggles');
    document.getElementById('undo-change').click();
    check(document.getElementById('count-pending').textContent==='7','undo generated card status');
    document.getElementById('undo-change').click();
    check(visible()===8&&document.getElementById('count-pending').textContent==='6','undo removes generated card and restores original pending');
    document.getElementById('reset-demo').click();
    check(document.getElementById('count-pending').textContent==='6','reset returns original follow-up states');
    check(followupFilter('all').getAttribute('aria-pressed')==='true','reset clears follow-up filter');
    check(!document.querySelector('form,iframe,textarea'),'no capture route');
    output.setAttribute('data-imagine-browser-smoke','PASS');
    output.textContent='F3-59 desktop real Chromium PASS';
  }catch(error){
    output.setAttribute('data-imagine-browser-smoke','FAIL');
    output.textContent='F3-59 FAIL: '+error.message;
  }
})();
JS;

$pageFile = sys_get_temp_dir().'/imagine-kanban-'.bin2hex(random_bytes(12)).'.html';
$mobileFile = sys_get_temp_dir().'/imagine-kanban-mobile-'.bin2hex(random_bytes(12)).'.html';
if (file_put_contents($pageFile, str_replace('</body>', '<script>'.$checks.'</script></body>', $html)) === false) {
    fwrite(STDERR, "F3-59: unable to create ephemeral browser fixture.\n");
    exit(1);
}

try {
    $desktop = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=1200,900', '--virtual-time-budget=2500'],
        'file://'.$pageFile,
        'data-imagine-browser-smoke',
    );
    if (!$desktop['passed']) {
        fwrite(STDERR, "F3-59 desktop FAIL: {$desktop['reason']}\n");
        exit(1);
    }

    $url = json_encode('file://'.$pageFile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_THROW_ON_ERROR);
    $mobileHtml = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Kanban 320 CSS px</title></head>
<body><output data-imagine-320="RUNNING"></output>
<script>
(function(){
  const marker=document.querySelector('[data-imagine-320]');
  const frame=document.createElement('iframe');
  frame.width=320;
  frame.height=900;
  frame.style.cssText='display:block;border:0;width:320px;height:900px';
  let done=false;
  function check(value,why){if(!value)throw new Error(why);}
  function finish(result,why){
    if(done)return;
    done=true;
    marker.setAttribute('data-imagine-320',result);
    marker.textContent='F3-59 '+result+': '+why;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'same-origin local fixture');
      check(win.innerWidth===320,'real 320 CSS pixel frame viewport');
      check(doc.documentElement.scrollWidth<=320,'no horizontal page overflow');
      check(doc.querySelectorAll('[data-card-id]').length===8,'eight cards on mobile');
      check(doc.getElementById('lane-value-0').textContent.includes('2.760'),'mobile initial entry value');
      check(win.getComputedStyle(doc.querySelector('.board')).gridTemplateColumns.split(' ').length===1,'one column layout');
      const next=doc.querySelector('[data-card-id="F01"] button[data-move="next"]');
      next.focus();
      next.click();
      check(doc.querySelector('#lane-1 [data-card-id="F01"]')!==null,'mobile moves a ticket');
      check(doc.getElementById('lane-value-1').textContent.includes('7.400'),'mobile stage value changes');
      check(doc.activeElement.closest('[data-card-id="F01"]')!==null,'mobile movement focus');
      check(doc.querySelectorAll('#activity-list li').length===1,'mobile history records movement');
      const sortButton=doc.querySelector('[data-order="score"]');
      sortButton.focus();
      sortButton.click();
      check(doc.activeElement===sortButton,'mobile sort preserves focus');
      check(sortButton.getAttribute('aria-pressed')==='true','mobile score sort selected');
      check(doc.documentElement.scrollWidth<=320,'mobile sort does not overflow');
      check(doc.documentElement.scrollWidth<=320,'no mobile overflow after movement');
      doc.querySelector('[data-priority="high"]').click();
      check(doc.querySelectorAll('[data-card-id]').length===4,'mobile priority filter');
      check(doc.documentElement.scrollWidth<=320,'no overflow with filter');
      doc.getElementById('reset-demo').click();
      const add=doc.getElementById('add-fiction');
      add.click();
      add.click();
      check(doc.querySelectorAll('[data-card-id]').length===10,'mobile creates two fictional cards');
      check(doc.getElementById('lane-value-0').textContent.includes('6.610'),'mobile two additions update entry amount');
      check(doc.querySelector('#lane-0 [data-card-id="F09"]')!==null,'mobile new fixture at entry');
      check(doc.documentElement.scrollWidth<=320,'mobile creation does not overflow');
      doc.getElementById('undo-change').click();
      check(doc.querySelectorAll('[data-card-id]').length===9,'mobile undo creation');
      doc.getElementById('reset-demo').click();
      check(doc.querySelectorAll('[data-card-id]').length===8,'mobile reset restores eight');
      check(doc.getElementById('count-pending').textContent==='6','mobile reset follow-up baseline');
      doc.querySelector('[data-followup-filter="done"]').click();
      check(doc.querySelectorAll('[data-card-id]').length===2,'mobile completed subset');
      doc.querySelector('[data-followup-filter="all"]').click();
      const mobileToggle=doc.querySelector('[data-card-id="F05"] button[data-followup]');
      mobileToggle.focus();
      mobileToggle.click();
      check(doc.getElementById('count-pending').textContent==='5','mobile follow-up completion');
      check(doc.activeElement===doc.querySelector('[data-card-id="F05"] button[data-followup]'),'mobile focus retained on follow-up control');
      doc.getElementById('undo-change').click();
      check(doc.getElementById('count-pending').textContent==='6','mobile undo restores follow-up');
      check(doc.documentElement.scrollWidth<=320,'mobile follow-up UI has no horizontal overflow');
      check(doc.documentElement.scrollWidth<=320,'no overflow after reset');
      finish('PASS','real interactive 320 px viewport');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__PAGE_URL__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;
    $mobileHtml = str_replace('__PAGE_URL__', $url, $mobileHtml);
    if (file_put_contents($mobileFile, $mobileHtml) === false) {
        fwrite(STDERR, "F3-59: could not create mobile driver.\n");
        exit(1);
    }
    $mobile = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=900,1060', '--virtual-time-budget=3400', '--allow-file-access-from-files'],
        'file://'.$mobileFile,
        'data-imagine-320',
    );
    if (!$mobile['passed']) {
        fwrite(STDERR, "F3-59 mobile FAIL: {$mobile['reason']}\n");
        exit(1);
    }
    echo "F3-59 real Chromium desktop + 320px: PASS.\n";
} finally {
    @unlink($pageFile);
    @unlink($mobileFile);
}
