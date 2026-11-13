<?php

declare(strict_types=1);

// UX F3-186: real Chromium DOM drag events on the offline-only fake Kanban.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html';
$html = file_get_contents($source);
if (!is_string($html) || strlen($html) > 50000
    || substr_count($html, 'data-drop-stage="') !== 4
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || !str_contains($html, "frame-src 'none'")
    || str_contains($html, '<form')) {
    fwrite(STDERR, "F3-186: invalid offline Kanban drag fixture.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $browser = $finder->find($name);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "F3-186: Chromium is mandatory.\n");
    exit(1);
}

$sourceUrl = json_encode(
    'file://'.$source,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>F3-186 Kanban drag QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
 const width=__WIDTH__, marker=document.querySelector('[data-imagine-browser-smoke]');
 const frame=document.createElement('iframe');
 frame.style.cssText='display:block;width:'+width+'px;height:900px;border:0';
 frame.width=width;frame.height=900;
 let complete=false;
 function check(ok,why){if(!ok)throw new Error(why);}
 function done(status,reason){
   if(complete)return;
   complete=true;
   marker.setAttribute('data-imagine-browser-smoke',status);
   marker.textContent='F3-186 '+status+': '+reason;
 }
 frame.addEventListener('load',function(){
  try{
   const d=frame.contentDocument,w=frame.contentWindow;
   check(d&&w&&w.innerWidth===width,'actual '+width+'px viewport');
   const by=id=>d.getElementById(id);
   const card=id=>d.querySelector('[data-card-id="'+id+'"]');
   const lane=stage=>d.querySelector('[data-drop-stage="'+stage+'"]');
   check(d.querySelectorAll('[data-drop-stage]').length===4,'four fake lanes');
   check(d.querySelectorAll('.ticket[draggable="true"]').length===8,'eight draggable fake cards');
   const dt=()=>new w.DataTransfer();
   const fire=(target,type,dataTransfer)=>target.dispatchEvent(
     new w.DragEvent(type,{bubbles:true,cancelable:true,dataTransfer})
   );
   const drag=(id,to)=>{
     const source=card(id),transfer=dt();
     check(source&&source.draggable,'fake card is draggable '+id);
     fire(source,'dragstart',transfer);
     check(transfer.getData('text/plain')===id,'drag payload strictly invented code');
     const target=lane(to);
     fire(target,'dragover',transfer);
     if(!target.hidden)check(target.dataset.dragOver==='true','drop target shows focus');
     fire(target,'drop',transfer);
     check(!target.hasAttribute('data-drag-over'),'drop highlight removed');
   };

   // An external payload does not authorize moves; only an internal drag
   // start supplies a valid in-memory identifier.
   const fake=dt();
   fake.setData('text/plain','F01');
   fire(lane(2),'drop',fake);
   check(card('F01').closest('.lane')===lane(0),'external drag data ignored');
   check(by('undo-change').disabled,'external drop creates no history');

   // Browsers can skip dragleave when moving between nested lane children.
   // Only the current valid destination may retain a visual drop highlight.
   const hoverTransfer=dt();
   fire(card('F01'),'dragstart',hoverTransfer);
   fire(lane(1),'dragover',hoverTransfer);
   check(lane(1).dataset.dragOver==='true','first lane highlighted');
   fire(lane(2),'dragover',hoverTransfer);
   check(lane(2).dataset.dragOver==='true','second lane highlighted');
   check(!lane(1).hasAttribute('data-drag-over'),'previous lane highlight cleared');
   check(d.querySelectorAll('.lane[data-drag-over]').length===1,'one highlighted lane maximum');
   fire(card('F01'),'dragend',hoverTransfer);
   check(d.querySelectorAll('.lane[data-drag-over]').length===0,'drag cancellation clears highlight');
   check(by('undo-change').disabled,'hover and cancel create no history');

   const invalidHover=dt(), invalidHoverLane=lane(3), originalHoverStage=invalidHoverLane.dataset.dropStage;
   fire(card('F01'),'dragstart',invalidHover);
   invalidHoverLane.dataset.dropStage='NaN';
   fire(invalidHoverLane,'dragover',invalidHover);
   check(!invalidHoverLane.hasAttribute('data-drag-over'),'invalid target has no highlight');
   invalidHoverLane.dataset.dropStage=originalHoverStage;
   fire(card('F01'),'dragend',invalidHover);
   check(by('undo-change').disabled,'invalid hover is inert');

   // Mutated lane data must be rejected before either individual or group edits.
   const tamperedLane=lane(3);
   for(const invalid of ['NaN','4','-1','1.5','']) {
     const original=tamperedLane.dataset.dropStage;
     tamperedLane.dataset.dropStage=invalid;
     const transfer=dt();
     fire(card('F01'),'dragstart',transfer);
     fire(tamperedLane,'drop',transfer);
     tamperedLane.dataset.dropStage=original;
     check(card('F01').closest('.lane')===lane(0),'invalid stage '+invalid+' did not move card');
     check(by('undo-change').disabled,'invalid stage '+invalid+' did not create history');
   }
   drag('F01',3);
   check(card('F01').closest('.lane')===lane(3),'drag from Entrada to Proposta');
   check(by('count-moves').textContent==='1','drag increments simulated moves once');
   check(!by('undo-change').disabled,'drag is undoable');
   by('undo-change').click();
   check(card('F01').closest('.lane')===lane(0),'drag undo restores stage 0');
   by('redo-change').click();
   check(card('F01').closest('.lane')===lane(3),'drag redo restores target');
   by('reset-demo').click();
   check(by('undo-change').disabled&&by('redo-change').disabled,'reset clears history');

   // Sort within each stage using synthetic values only. Sorting never edits records.
   drag('F03',0);
   drag('F05',0);
   const stageZeroOrder=()=>Array.from(lane(0).querySelectorAll('[data-card-id]'))
     .map(item=>item.dataset.cardId).join(',');
   check(stageZeroOrder()==='F01,F02,F03,F05','original insertion order before value sort');
   d.querySelector('[data-order="value"]').click();
   check(stageZeroOrder()==='F05,F03,F01,F02','highest fictitious value first');
   check(d.querySelector('[data-order="value"]').getAttribute('aria-pressed')==='true',
     'value order announced to assistive technology');
   by('toggle-csv-demo').click();
   const csvIds=()=>by('csv-preview').textContent.trim().split('\n').slice(1)
     .map(row=>row.split(';')[0]);
   check(csvIds().slice(0,4).join(',')===stageZeroOrder(),
     'CSV preview mirrors displayed value order');
   d.querySelector('[data-order="score"]').click();
   check(stageZeroOrder()==='F01,F05,F03,F02','score order distinct from value order');
   check(csvIds().slice(0,4).join(',')===stageZeroOrder(),
     'CSV preview mirrors displayed score order');
   d.querySelector('[data-order="default"]').click();
   check(stageZeroOrder()==='F01,F02,F03,F05','original order restored');
   check(csvIds().slice(0,4).join(',')===stageZeroOrder(),
     'CSV preview mirrors displayed original order');
   by('toggle-csv-demo').click();
   check(by('count-moves').textContent==='2','sorting does not create extra actions');
   by('reset-demo').click();
   check(by('undo-change').disabled,'reset after sorting clears actions');

   // Same-lane drop is a no-op; adjacent keyboard button still works.
   drag('F01',0);
   check(by('undo-change').disabled,'dropping on current lane is inert');
   card('F01').querySelector('[data-move="next"]').click();
   check(card('F01').closest('.lane')===lane(1),'keyboard-compatible move button works');
   by('undo-change').click();
   check(card('F01').closest('.lane')===lane(0),'button move remains reversible');
   by('reset-demo').click();

   // Hidden lanes cannot receive synthetic drops while stage filter active.
   d.querySelector('[data-stage-filter="0"]').click();
   check(lane(3).hidden,'filter hides stage 3');
   drag('F01',3);
   check(card('F01').closest('.lane')===lane(0),'hidden target cannot change stage');
   check(by('undo-change').disabled,'hidden drop has no history');
   d.querySelector('[data-stage-filter="all"]').click();
   drag('F02',2);
   check(card('F02').closest('.lane')===lane(2),'independent card moves');
   check(card('F01').closest('.lane')===lane(0),'other card untouched');
   check(d.querySelectorAll('.lane[data-drag-over]').length===0,'no stale drop markers');
   by('reset-demo').click();

   // Beginning a drag on a card action button is ignored.
   const button=card('F01').querySelector('[data-followup]');
   fire(button,'dragstart',dt());
   fire(lane(2),'drop',dt());
   check(card('F01').closest('.lane')===lane(0),'button dragstart ignored');

   // Group drag: selected+visible cards move together as a single undo.
   by('reset-demo').click();
   card('F01').querySelector('[data-select]').click();
   card('F03').querySelector('[data-select]').click();
   check(by('count-selected').textContent.includes('2 selecionados visíveis'),
     'two selected fake opportunities');
   drag('F01',3);
   check(card('F01').closest('.lane')===lane(3),'selected F01 moved to destination');
   check(card('F03').closest('.lane')===lane(3),'selected F03 moved with F01');
   check(card('F02').closest('.lane')===lane(0),'unselected F02 unaffected');
   check(card('F04').closest('.lane')===lane(1),'unselected F04 unaffected');
   check(by('count-moves').textContent==='1','group drag recorded as one action');
   by('undo-change').click();
   check(card('F01').closest('.lane')===lane(0)&&card('F03').closest('.lane')===lane(1),
     'single group undo restores both stages');
   by('redo-change').click();
   check(card('F01').closest('.lane')===lane(3)&&card('F03').closest('.lane')===lane(3),
     'single group redo restores both stages');

   // Selected but HIDDEN card is not changed by another selected drag.
   by('reset-demo').click();
   card('F01').querySelector('[data-select]').click();
   card('F02').querySelector('[data-select]').click();
   d.querySelector('[data-priority="high"]').click();
   check(card('F02')===null,'normal selected F02 is hidden');
   drag('F01',2);
   check(card('F01').closest('.lane')===lane(2),'visible selected F01 moved');
   d.querySelector('[data-priority="all"]').click();
   check(card('F02').closest('.lane')===lane(0),'hidden selected F02 never moved');
   by('undo-change').click();
   check(card('F01').closest('.lane')===lane(0),'filtered group move undo');
   by('reset-demo').click();

   // Dragging an UNSELECTED card stays individual even if another is selected.
   card('F01').querySelector('[data-select]').click();
   drag('F02',3);
   check(card('F02').closest('.lane')===lane(3),'unselected dragged F02 moves alone');
   check(card('F01').closest('.lane')===lane(0),'selected but not dragged F01 untouched');
   by('undo-change').click();
   check(card('F02').closest('.lane')===lane(0),'unselected drag undo');
   by('reset-demo').click();

   check(d.documentElement.scrollWidth<=width,'no horizontal overflow');
   check(d.querySelectorAll('form').length===0,'no forms');
   check(d.querySelectorAll('input').length===1,'only bounded local search input');
   check(by('count-visible').textContent==='8','eight fake cards after reset');
   done('PASS','single/group visible-only drag, undo/redo, hidden lane, keyboard at '+width+'px');
  }catch(error){done('FAIL',error.message);}
 });
 frame.src=__SOURCE__;
 document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $driverFile = sys_get_temp_dir().'/crm-f3-186-'.bin2hex(random_bytes(12)).'.html';
    $markup = str_replace(['__WIDTH__', '__SOURCE__'], [(string) $width, $sourceUrl], $driver);
    if (file_put_contents($driverFile, $markup) === false) {
        fwrite(STDERR, "F3-186: browser driver could not be written.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1350,1000', '--virtual-time-budget=3200', '--allow-file-access-from-files'],
            'file://'.$driverFile,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-186 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($driverFile);
    }
}
echo "F3-186 PASS: real Chromium drag events and undo at 1100/320 CSS pixels.\n";
