<?php

declare(strict_types=1);

// F3-59: real Chrome/Chromium, with in-memory fictional CRM opportunities.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html');
if (!is_string($html)
    || strlen($html) > 32000
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
  const byId=id=>document.getElementById(id);
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
    const batch=byId('complete-visible-followups');
    check(!batch.disabled,'bulk action available with pending fictional cards');
    document.querySelector('[data-priority="high"]').click();
    check(visible()===4&&byId('count-pending').textContent==='4','only four high-priority pending fixtures');
    batch.focus();
    batch.click();
    check(byId('count-pending').textContent==='0'&&visible()===4,'bulk completion affects high priority visible subset');
    check(byId('count-moves').textContent==='1','bulk counted as one reversible action');
    check(batch.disabled,'bulk disabled when none visibly pending');
    check(document.activeElement===followupFilter('all'),'focus relocates to filter when action disables itself');
    check(document.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('concluído'),'visible high fixture completed');
    document.querySelector('[data-priority="all"]').click();
    check(visible()===8&&byId('count-pending').textContent==='2','hidden normal pending fixtures unchanged');
    check(document.querySelector('[data-card-id="F06"] .followup-label').textContent.includes('pendente'),'unselected normal fixture not mutated');
    byId('undo-change').click();
    check(byId('count-pending').textContent==='6','one undo restores all four batched fixtures');
    check(document.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('pendente'),'bulk undo restores previous card state');
    byId('reset-demo').click();
    followupFilter('pending').click();
    check(visible()===6,'six pending fixtures before filtered batch');
    batch.click();
    check(visible()===0&&byId('count-pending').textContent==='0','pending-only batch hides completed fixtures');
    check(document.activeElement===followupFilter('all'),'focus remains visible after filtered cards disappear');
    byId('undo-change').click();
    check(visible()===6&&byId('count-pending').textContent==='6','pending-only batch undone atomically');
    byId('reset-demo').click();
    check(visible()===8&&byId('count-pending').textContent==='6'&&!batch.disabled,'reset all batched states');
    const queueItems=()=>Array.from(document.querySelectorAll('#followup-queue button[data-queue-card]')).map(button=>button.dataset.queueCard);
    check(JSON.stringify(queueItems())===JSON.stringify(['F07','F01','F05','F03','F06','F08']),'initial pending queue sorted by fictional score');
    const locate=document.querySelector('[data-queue-card="F07"]');
    locate.focus();
    locate.click();
    check(document.activeElement===document.querySelector('[data-card-id="F07"] button[data-followup]'),'queue navigation moves focus to original follow-up button');
    check(byId('simulation-status').textContent.includes('localizado'),'queue announces card location');
    document.querySelector('[data-priority="normal"]').click();
    check(JSON.stringify(queueItems())===JSON.stringify(['F06','F08']),'queue respects visible normal priority');
    followupFilter('done').click();
    check(queueItems().length===0&&byId('followup-queue').textContent.includes('Nenhum acompanhamento'),'queue reports empty completed subset');
    followupFilter('all').click();
    document.querySelector('[data-priority="all"]').click();
    check(queueItems().length===6,'queue restores all visible pending cards');
    byId('add-fiction').click();
    check(queueItems().length===7&&queueItems().includes('F09'),'generated pending fixture enters queue');
    byId('undo-change').click();
    check(queueItems().length===6&&!queueItems().includes('F09'),'undo removes generated queue row');
    document.querySelector('[data-card-id="F07"] button[data-followup]').click();
    check(queueItems().length===5&&!queueItems().includes('F07'),'completion removes queue row');
    byId('undo-change').click();
    check(queueItems()[0]==='F07','undo restores highest-scoring pending queue item');
    byId('complete-visible-followups').click();
    check(queueItems().length===0,'atomic batch clears pending queue');
    byId('undo-change').click();
    check(queueItems().length===6,'atomic undo restores pending queue');
    byId('reset-demo').click();
    check(queueItems().length===6,'reset restores queue');
    const stageButton=stage=>document.querySelector('[data-stage-filter="'+stage+'"]');
    check(stageButton('all').getAttribute('aria-pressed')==='true','all four lanes initially visible');
    stageButton('2').focus();
    stageButton('2').click();
    check(document.activeElement===stageButton('2'),'stage focus button retains keyboard focus');
    check(visible()===2&&byId('count-visible').textContent==='2','qualification stage has two fictional cards');
    check(document.querySelectorAll('.lane:not([hidden])').length===1,'one lane visible when stage focused');
    check(!document.querySelector('#lane-2').closest('.lane').hidden,'qualification lane visible');
    check(byId('sum-visible').textContent.includes('7.750'),'focused totals recompute');
    check(queueItems().length===2,'queue follows focused stage');
    document.querySelector('[data-priority="high"]').click();
    check(visible()===1&&queueItems().length===1,'stage and priority filters intersect');
    byId('complete-visible-followups').click();
    check(byId('count-pending').textContent==='0','batch only completes visible focused stage');
    document.querySelector('[data-priority="all"]').click();
    stageButton('all').click();
    check(visible()===8&&byId('count-pending').textContent==='5','other stages not changed by focused batch');
    check(document.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('pendente'),'hidden entry-stage card untouched');
    byId('undo-change').click();
    check(byId('count-pending').textContent==='6','single undo restores focused batch');
    stageButton('2').click();
    document.querySelector('[data-priority="high"]').click();
    move('F05','next');
    check(visible()===0,'card leaving focused stage is no longer visible');
    check(document.activeElement===stageButton('2'),'focus falls back to selected stage after move');
    byId('undo-change').click();
    check(visible()===1&&document.querySelector('#lane-2 [data-card-id="F05"]')!==null,'undo restores card to focused stage');
    byId('add-fiction').click();
    check(stageButton('all').getAttribute('aria-pressed')==='true','adding fixture resets stage focus to show new entry');
    check(document.querySelector('[data-card-id="F09"]')!==null,'new fixture visible after stage focus reset');
    byId('reset-demo').click();
    check(visible()===8&&document.querySelectorAll('.lane:not([hidden])').length===4,'reset restores all four stages');
    stageButton('1').click();
    check(visible()===2&&queueItems().length===1,'contact stage has one pending one completed');
    followupFilter('done').click();
    check(visible()===1&&queueItems().length===0,'stage and completed filter intersection');
    byId('reset-demo').click();
    check(visible()===8&&stageButton('all').getAttribute('aria-pressed')==='true','reset restores all-stage focus');
    const stageAdvance=byId('advance-visible-stage');
    check(!stageAdvance.disabled,'forward bulk action available with six eligible fictional cards');
    byId('reset-demo').click();
    stageAdvance.focus();
    stageAdvance.click();
    check(byId('count-moves').textContent==='1','six-card advance is one action');
    check(visible()===8,'bulk stage advance preserves all eight cards');
    check(['0','2','2','4'].every((countExpected,i)=>count(i)===countExpected),'six nonterminal cards advance exactly once');
    check(document.querySelector('#lane-1 [data-card-id="F01"]')!==null,'entry high advanced to contact');
    check(document.querySelector('#lane-3 [data-card-id="F05"]')!==null,'qualification advanced to proposal');
    check(document.querySelector('#lane-3 [data-card-id="F07"]')!==null,'terminal proposal remains unchanged');
    check(document.activeElement===stageAdvance,'bulk action retains focus when still enabled');
    byId('undo-change').click();
    check(['2','2','2','2'].every((countExpected,i)=>count(i)===countExpected),'one undo restores all prior stages');
    check(byId('count-moves').textContent==='2','undo is a single action');
    byId('reset-demo').click();
    stageButton('0').click();
    document.querySelector('[data-priority="high"]').click();
    check(visible()===1&&document.querySelector('[data-card-id="F01"]')!==null,'stage+priority intersection selects one');
    stageAdvance.focus();
    stageAdvance.click();
    check(visible()===0&&stageAdvance.disabled,'moving only selected card clears focused stage');
    check(document.activeElement===stageButton('0'),'bulk stage move falls back to active stage filter');
    stageButton('all').click();
    document.querySelector('[data-priority="all"]').click();
    check(visible()===8,'restoring filters does not remove data');
    check(document.querySelector('#lane-1 [data-card-id="F01"]')!==null,'selected card alone advanced');
    check(document.querySelector('#lane-0 [data-card-id="F02"]')!==null,'hidden normal card not moved');
    byId('undo-change').click();
    check(document.querySelector('#lane-0 [data-card-id="F01"]')!==null,'undo restores focused bulk stage');
    byId('reset-demo').click();
    stageButton('3').click();
    check(stageAdvance.disabled,'terminal proposal stage cannot advance in bulk');
    stageButton('all').click();
    document.querySelector('[data-priority="high"]').click();
    check(visible()===4,'four high-priority fixtures');
    stageAdvance.click();
    check(document.querySelector('#lane-3 [data-card-id="F07"]')!==null,'terminal high card unaffected');
    document.querySelector('[data-priority="all"]').click();
    check(document.querySelector('#lane-2 [data-card-id="F06"]')!==null,'hidden normal card unaffected');
    check(document.querySelector('#lane-3 [data-card-id="F05"]')!==null,'qualified high card advanced');
    byId('undo-change').click();
    document.querySelector('[data-priority="all"]').click();
    check(['2','2','2','2'].every((expected,i)=>count(i)===expected),'bulk high undo restores all prior stages');
    byId('reset-demo').click();
    const bulkReturn=byId('return-visible-stage');
    byId('reset-demo').click();
    check(!bulkReturn.disabled,'backward bulk action enabled for six non-entry fictitious cards');
    bulkReturn.focus();
    bulkReturn.click();
    check(byId('count-moves').textContent==='1','six-card bulk return counts as one action');
    check(['4','2','2','0'].every((expected,i)=>count(i)===expected),'six non-entry cards return exactly one stage');
    check(document.querySelector('#lane-0 [data-card-id="F03"]')!==null,'contact high moved to entry');
    check(document.querySelector('#lane-0 [data-card-id="F01"]')!==null,'entry high remains untouched');
    check(document.querySelector('#lane-2 [data-card-id="F07"]')!==null,'proposal high moved to qualification');
    check(document.activeElement===bulkReturn,'keyboard focus stays on eligible bulk return');
    byId('undo-change').click();
    check(['2','2','2','2'].every((expected,i)=>count(i)===expected),'single undo restores all six original stages');
    byId('reset-demo').click();

    stageButton('0').click();
    check(bulkReturn.disabled,'entry-only view cannot return before first stage');
    stageButton('3').click();
    document.querySelector('[data-priority="normal"]').click();
    check(visible()===1&&document.querySelector('[data-card-id="F08"]')!==null,'proposal+normal selects only F08');
    bulkReturn.focus();
    bulkReturn.click();
    check(visible()===0&&bulkReturn.disabled,'backward move hides selected card in stage-focused view');
    check(document.activeElement===stageButton('3'),'focus returns to active stage selector');
    stageButton('all').click();
    document.querySelector('[data-priority="all"]').click();
    check(document.querySelector('#lane-2 [data-card-id="F08"]')!==null,'only visible normal proposal returned');
    check(document.querySelector('#lane-3 [data-card-id="F07"]')!==null,'hidden high proposal remains untouched');
    byId('undo-change').click();
    check(document.querySelector('#lane-3 [data-card-id="F08"]')!==null,'undo restores filtered return');
    byId('reset-demo').click();

    followupFilter('done').click();
    check(visible()===2,'completed only has two fictional cards');
    bulkReturn.click();
    check(document.querySelector('#lane-0 [data-card-id="F04"]')!==null,'eligible completed F04 returned to entry');
    check(document.querySelector('#lane-0 [data-card-id="F02"]')!==null,'completed F02 already at entry untouched');
    byId('undo-change').click();
    check(document.querySelector('#lane-1 [data-card-id="F04"]')!==null,'undo completed-only return');
    byId('reset-demo').click();
    const csvButton=byId('toggle-csv-demo'),csvView=byId('csv-preview');
    check(csvView.hidden&&csvButton.getAttribute('aria-expanded')==='false','CSV preview initially closed');
    csvButton.focus();csvButton.click();
    check(!csvView.hidden&&csvButton.getAttribute('aria-expanded')==='true','CSV preview opens read only');
    check(document.activeElement===csvButton,'CSV reveal preserves button focus');
    const csvLines=()=>csvView.textContent.trim().split('\n');
    check(csvLines()[0]==='id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao','strict technical CSV header');
    check(csvLines().length===9,'CSV preview has eight invented rows');
    check(csvLines()[1]==='F01;0;high;pending;1800;92','first CSV fixture is technical only');
    check(csvLines()[8]==='F08;3;normal;pending;1100;57','last CSV fixture is technical only');
    document.querySelector('[data-priority="high"]').click();
    check(csvLines().length===5&&csvLines().slice(1).every(x=>x.includes(';high;')),'CSV respects priority filtering');
    stageButton('2').click();
    check(csvLines().length===2&&csvLines()[1]==='F05;2;high;pending;5300;90','CSV respects stage focus');
    move('F05','next');
    check(csvLines().length===1,'CSV has only header when selected card leaves stage');
    byId('undo-change').click();
    check(csvLines().length===2&&csvLines()[1].startsWith('F05;2;'),'undo restores CSV without cached rows');
    byId('reset-demo').click();
    check(csvLines().length===9,'CSV follows reset');
    byId('add-fiction').click();
    check(csvLines().length===10&&csvLines()[9]==='F09;0;high;pending;1850;59','new generated fictitious card enters CSV');
    byId('undo-change').click();
    check(csvLines().length===9,'undo creation removes generated CSV entry');
    stageButton('3').click();
    followupFilter('done').click();
    check(csvLines().length===1,'empty intersection emits header only');
    byId('reset-demo').click();
    csvButton.click();
    check(csvView.hidden&&csvButton.getAttribute('aria-expanded')==='false','CSV preview closes without exporting a file');
    byId('reset-demo').click();
    const addForIds=byId('add-fiction');
    for(let i=0;i<25;i++){
      addForIds.click();
      check(document.querySelector('[data-card-id="F09"]')!==null,'cycled creation always reuses F09');
      check(document.querySelector('[data-card-id="F13"]')===null,'cyclic creation never emits F13');
      byId('undo-change').click();
      check(visible()===8&&document.querySelector('[data-card-id="F09"]')===null,'undo releases reusable F09');
    }
    check(byId('undo-change').disabled,'undo stack empty after repeated create/undo cycles');
    for(let i=0;i<4;i++)addForIds.click();
    check(visible()===12&&addForIds.disabled,'four generated slots fill at F12');
    check(['F09','F10','F11','F12'].every(id=>document.querySelector('[data-card-id="'+id+'"]')!==null),'four generated IDs unique');
    check(document.querySelector('[data-card-id="F13"]')===null,'no ID exceeds fixed synthetic contract');
    byId('toggle-csv-demo').click();
    check(csvLines().length===13&&csvLines()[12].startsWith('F12;'),'CSV remains valid for max twelve fixtures');
    byId('undo-change').click();
    check(visible()===11&&!addForIds.disabled,'undo frees latest F12 slot');
    addForIds.click();
    check(visible()===12&&document.querySelector('[data-card-id="F12"]')!==null,'readding reuses F12 after undo');
    check(csvLines().length===13&&csvLines()[12].startsWith('F12;'),'CSV stays valid after reclaimed F12');
    check(!csvView.textContent.includes('indisponível'),'CSV never fails due to burnt ID counter');
    byId('reset-demo').click();
    check(visible()===8&&csvLines().length===9,'reset restores original IDs and CSV');
    byId('toggle-csv-demo').click();
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
      const mobileBatch=doc.getElementById('complete-visible-followups');
      check(!mobileBatch.disabled,'mobile batch initially enabled');
      mobileBatch.focus();
      mobileBatch.click();
      check(doc.getElementById('count-pending').textContent==='0','mobile completed all visible pending');
      check(doc.getElementById('count-moves').textContent!=='0','mobile batch counted');
      doc.getElementById('undo-change').click();
      check(doc.getElementById('count-pending').textContent==='6','mobile undoes entire batch');
      doc.querySelector('[data-followup-filter="pending"]').click();
      mobileBatch.click();
      check(doc.querySelectorAll('[data-card-id]').length===0,'mobile filtered pending batch empties view');
      doc.getElementById('undo-change').click();
      check(doc.querySelectorAll('[data-card-id]').length===6,'mobile batch undo brings pending cards back');
      doc.getElementById('reset-demo').click();
      check(doc.querySelectorAll('[data-card-id]').length===8,'mobile reset restores eight after batches');
      const queueList=()=>doc.querySelectorAll('#followup-queue button[data-queue-card]');
      check(queueList().length===6,'mobile queue shows pending items');
      const queueLink=doc.querySelector('[data-queue-card="F01"]');
      queueLink.click();
      check(doc.activeElement===doc.querySelector('[data-card-id="F01"] button[data-followup]'),'mobile queue locates original card');
      doc.querySelector('[data-followup-filter="done"]').click();
      check(queueList().length===0,'mobile completed filter empties queue');
      doc.getElementById('reset-demo').click();
      check(queueList().length===6,'mobile reset restores queue');
      check(doc.documentElement.scrollWidth<=320,'mobile queue does not overflow page');
      doc.querySelector('[data-stage-filter="3"]').click();
      check(doc.querySelectorAll('.lane:not([hidden])').length===1,'mobile stage focus shows one lane');
      check(doc.querySelectorAll('[data-card-id]').length===2,'mobile proposal stage has two fixtures');
      check(doc.querySelectorAll('#followup-queue button[data-queue-card]').length===2,'mobile queue follows stage');
      check(doc.documentElement.scrollWidth<=320,'mobile stage-filter no horizontal overflow');
      doc.getElementById('reset-demo').click();
      check(doc.querySelectorAll('.lane:not([hidden])').length===4,'mobile reset shows all stages');
      check(doc.querySelectorAll('[data-card-id]').length===8,'mobile reset restores full pipeline');
      const mobileAdvance=doc.getElementById('advance-visible-stage');
      check(!mobileAdvance.disabled,'mobile forward bulk stage action available');
      doc.querySelector('[data-stage-filter="0"]').click();
      doc.querySelector('[data-priority="high"]').click();
      mobileAdvance.focus();
      mobileAdvance.click();
      check(doc.getElementById('count-visible').textContent==='0','mobile selected stage empty after moving visible card');
      check(doc.activeElement===doc.querySelector('[data-stage-filter="0"]'),'mobile focus fallback when stage emptied');
      doc.querySelector('[data-stage-filter="all"]').click();
      doc.querySelector('[data-priority="all"]').click();
      check(doc.querySelector('#lane-1 [data-card-id="F01"]')!==null,'mobile selected card moved');
      check(doc.querySelector('#lane-0 [data-card-id="F02"]')!==null,'mobile hidden card preserved');
      doc.getElementById('undo-change').click();
      check(doc.querySelector('#lane-0 [data-card-id="F01"]')!==null,'mobile undo restores moved card');
      doc.getElementById('reset-demo').click();
      check(doc.documentElement.scrollWidth<=320,'bulk stage control responsive at 320px');
      const mobileReturn=doc.getElementById('return-visible-stage');
      check(!mobileReturn.disabled,'mobile bulk return enabled after reset');
      doc.querySelector('[data-stage-filter="3"]').click();
      doc.querySelector('[data-priority="normal"]').click();
      mobileReturn.focus();
      mobileReturn.click();
      check(doc.getElementById('count-visible').textContent==='0','mobile stage-focused return empties view');
      check(doc.activeElement===doc.querySelector('[data-stage-filter="3"]'),'mobile return focus fallback');
      doc.querySelector('[data-stage-filter="all"]').click();
      doc.querySelector('[data-priority="all"]').click();
      check(doc.querySelector('#lane-2 [data-card-id="F08"]')!==null,'mobile F08 returned to qualification');
      check(doc.querySelector('#lane-3 [data-card-id="F07"]')!==null,'mobile hidden F07 remains in proposal');
      doc.getElementById('undo-change').click();
      check(doc.querySelector('#lane-3 [data-card-id="F08"]')!==null,'mobile undo restores backward stage');
      doc.getElementById('reset-demo').click();
      check(doc.documentElement.scrollWidth<=320,'mobile bulk return controls no overflow');
      const mobileCsvButton=doc.getElementById('toggle-csv-demo');
      const mobileCsv=doc.getElementById('csv-preview');
      mobileCsvButton.focus();mobileCsvButton.click();
      check(!mobileCsv.hidden&&mobileCsvButton.getAttribute('aria-expanded')==='true','mobile CSV preview opens');
      check(doc.activeElement===mobileCsvButton,'mobile CSV focus preserved');
      check(mobileCsv.textContent.trim().split('\n').length===9,'mobile CSV eight fictitious rows');
      doc.querySelector('[data-stage-filter="3"]').click();
      doc.querySelector('[data-priority="high"]').click();
      check(mobileCsv.textContent.trim().split('\n').length===2,'mobile CSV filtered to one high proposal');
      check(mobileCsv.textContent.includes('F07;3;high;pending;7600;95'),'mobile filtered CSV technical value');
      check(doc.documentElement.scrollWidth<=320,'mobile CSV scroll is internal');
      doc.getElementById('reset-demo').click();
      check(mobileCsv.textContent.trim().split('\n').length===9,'mobile CSV restored after reset');
      mobileCsvButton.click();
      check(mobileCsv.hidden,'mobile CSV preview closes');
      const mobileAddIds=doc.getElementById('add-fiction');
      for(let i=0;i<8;i++){
        mobileAddIds.click();
        check(doc.querySelector('[data-card-id="F09"]')!==null,'mobile reuses F09 after undo');
        doc.getElementById('undo-change').click();
        check(doc.querySelectorAll('[data-card-id]').length===8,'mobile undo restores eight records');
      }
      for(let i=0;i<4;i++)mobileAddIds.click();
      check(['F09','F10','F11','F12'].every(id=>doc.querySelector('[data-card-id="'+id+'"]')!==null),'mobile generated IDs F09-F12');
      doc.getElementById('undo-change').click();
      mobileAddIds.click();
      check(doc.querySelector('[data-card-id="F12"]')!==null,'mobile reuses F12 slot');
      check(doc.querySelector('[data-card-id="F13"]')===null,'mobile never generates F13');
      doc.getElementById('reset-demo').click();
      check(doc.documentElement.scrollWidth<=320,'mobile reusable slot controls do not overflow');
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
