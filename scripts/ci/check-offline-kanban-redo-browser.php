<?php

declare(strict_types=1);

// F3-175 — actual Chromium proof of undo/redo of fictional Kanban mutations.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html';
$html = file_get_contents($source);
if (!is_string($html)
    || strlen($html) > 48000
    || substr_count($html, 'id="redo-change"') !== 1
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || !str_contains($html, "frame-src 'none'")) {
    fwrite(STDERR, "F3-175: invalid bounded offline Kanban fixture.\n");
    exit(1);
}

$browser = null;
$finder = new ExecutableFinder();
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $browser = $finder->find($name);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "F3-175: actual Chromium is required.\n");
    exit(1);
}

$url = json_encode('file://'.$source, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>F3-175 undo redo browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  const marker=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;border:0;width:'+width+'px;height:900px';
  frame.width=width;frame.height=900;
  let finished=false;
  function check(ok,reason){if(!ok)throw new Error(reason);}
  function finish(state,reason){
    if(finished)return; finished=true;
    marker.setAttribute('data-imagine-browser-smoke',state);
    marker.textContent='F3-175 '+state+': '+reason;
  }
  frame.addEventListener('load',function(){
    try {
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win&&win.innerWidth===width,'real CSS viewport');
      const by=id=>doc.getElementById(id),undo=()=>by('undo-change').click(),
        redo=()=>by('redo-change').click();
      const card=id=>doc.querySelector('[data-card-id="'+id+'"]');
      const clickMove=(id,where)=>card(id).querySelector('[data-move="'+where+'"]').click();
      const follow=id=>card(id).querySelector('[data-followup]').click();
      check(by('redo-change').disabled,'redo disabled at start');
      check(by('undo-change').disabled,'undo disabled at start');

      // Single movement and the inverse are both reversible.
      clickMove('F01','next');
      check(by('lane-count-0').textContent==='1','individual movement committed');
      undo(); check(by('lane-count-0').textContent==='2','move undo');
      check(!by('redo-change').disabled,'redo enabled after undo');
      redo(); check(by('lane-count-0').textContent==='1','move redo');
      undo(); check(by('lane-count-0').textContent==='2','move second undo');

      // A fresh mutation after undo must invalidate the abandoned redo path.
      follow('F01');
      check(by('redo-change').disabled,'fresh edit invalidates redo');
      check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'followup completed');
      undo(); check(card('F01').querySelector('.followup-label').textContent.includes('pendente'),
        'followup undo');
      redo(); check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'followup redo');
      by('reset-demo').click();
      check(by('redo-change').disabled&&by('undo-change').disabled,'reset clears both stacks');

      // Whole batch is one reversible action, invisible normal-priority cards
      // are never touched and the batch can be replayed.
      doc.querySelector('[data-priority="high"]').click();
      by('advance-visible-stage').click();
      check(card('F01').closest('.lane').id!=='lane-0','high F01 advanced');
      check(by('lane-count-0').textContent==='0','high stage emptied');
      undo(); check(card('F01').closest('.lane').querySelector('[id="lane-0"]')!==null
        ||doc.querySelector('#lane-0 [data-card-id="F01"]')!==null,'batch move undo');
      redo(); check(doc.querySelector('#lane-1 [data-card-id="F01"]')!==null,'batch move redo');
      by('reset-demo').click();

      doc.querySelector('[data-priority="high"]').click();
      by('complete-visible-followups').click();
      check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'batch followup completed F01');
      undo(); check(card('F01').querySelector('.followup-label').textContent.includes('pendente'),
        'batch followup undo');
      redo(); check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'batch followup redo');
      by('reset-demo').click();
      check(card('F02').querySelector('.followup-label').textContent.includes('concluído'),
        'normal fixture remains untouched');

      // Creation replay restores only a synthetic card, and reset restores
      // eight original fictional cards.
      by('add-fiction').click();
      check(card('F09')!==null,'invented card created');
      undo(); check(card('F09')===null,'creation undo removes card');
      redo(); check(card('F09')!==null,'creation redo reconstructs same card');
      undo(); check(card('F09')===null,'creation redo itself can be undone');
      by('reset-demo').click();

      // Accessible keyboard shortcuts must never hijack an editable input.
      const key=(symbol,modifiers,target)=>{
        const event=new win.KeyboardEvent('keydown',
          Object.assign({key:symbol,bubbles:true,cancelable:true},modifiers));
        (target||doc).dispatchEvent(event);
        return event.defaultPrevented;
      };
      check(!key('z',{ctrlKey:true}),'disabled undo leaves browser shortcut untouched');
      follow('F01');
      check(key('z',{ctrlKey:true}),'Ctrl+Z handled after mutation');
      check(card('F01').querySelector('.followup-label').textContent.includes('pendente'),
        'Ctrl+Z reverses followup');
      check(key('z',{ctrlKey:true,shiftKey:true}),'Ctrl+Shift+Z handled');
      check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'Ctrl+Shift+Z reapplies followup');
      check(key('z',{metaKey:true}),'Cmd+Z handled');
      check(card('F01').querySelector('.followup-label').textContent.includes('pendente'),
        'Cmd+Z reverses followup');
      check(key('y',{ctrlKey:true}),'Ctrl+Y handled');
      check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'Ctrl+Y reapplies followup');
      check(key('z',{metaKey:true}),'Cmd+Z handled second time');
      check(key('z',{metaKey:true,shiftKey:true}),'Cmd+Shift+Z handled');
      const field=by('fiction-search');
      field.focus();
      check(!key('z',{ctrlKey:true},field),'search input keeps native Ctrl+Z');
      check(!key('z',{metaKey:true},field),'search input keeps native Cmd+Z');
      check(!key('z',{ctrlKey:true,shiftKey:true},field),'search input keeps native redo');
      check(!key('z',{ctrlKey:true,altKey:true},field),'Alt shortcut not hijacked');
      check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'typing focus cannot change Kanban state');
      by('reset-demo').click();
      check(!key('y',{ctrlKey:true}),'empty redo stack is not captured');
      follow('F01');
      check(!key('z',{ctrlKey:true,repeat:true}),'repeated shortcut ignored');
      check(!key('z',{ctrlKey:true,metaKey:true}),'ambiguous Ctrl+Cmd ignored');
      check(!key('z',{ctrlKey:true,altKey:true}),'Alt-modified shortcut ignored');
      check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
        'ignored modifiers have no side effect');
      by('reset-demo').click();

      // Long sessions: undo only the latest 64 of 70 mutations, and redo
      // those exact 64. Counters may grow; stacks must remain bounded.
      for(let i=0;i<70;i++)follow('F01');
      for(let i=0;i<64;i++) {
        check(!by('undo-change').disabled,'bounded undo action '+i);
        undo();
      }
      check(by('undo-change').disabled,'only last 64 are undoable');
      check(!by('redo-change').disabled,'bounded redo queue available');
      for(let i=0;i<64;i++){
        check(!by('redo-change').disabled,'bounded redo action '+i);
        redo();
      }
      check(by('redo-change').disabled,'redo fully consumed');
      check(!by('undo-change').disabled,'undo returns after redo');
      by('reset-demo').click();
      check(by('count-visible').textContent==='8'&&by('undo-change').disabled
        &&by('redo-change').disabled,'reset fully clears histories');
      check(doc.documentElement.scrollWidth<=width,'no horizontal overflow');
      check(doc.querySelectorAll('form').length===0,'offline without forms');
      check(doc.querySelectorAll('input').length===1,'only local search input');
      finish('PASS','real '+width+'px redo of move/batches/followup/create, history 64, fork, reset');
    } catch(error){ finish('FAIL',error.message); }
  });
  frame.src=__SOURCE__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file=sys_get_temp_dir().'/imagine-redo-'.bin2hex(random_bytes(12)).'.html';
    $markup=str_replace(['__WIDTH__','__SOURCE__'],[(string)$width,$url],$driver);
    if(file_put_contents($file,$markup)===false) {
        fwrite(STDERR,"F3-175: temporary browser driver could not be created.\n");
        exit(1);
    }
    try {
        $outcome=BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1350,980','--virtual-time-budget=3200','--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$outcome['passed']) {
            fwrite(STDERR,"F3-175 {$width}px FAIL: {$outcome['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}
echo "F3-175 PASS: real Chromium undo/redo and bounded history at 1100/320 CSS px.\n";
