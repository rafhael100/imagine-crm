<?php

declare(strict_types=1);

// F3-177 — actual Chromium proof of explicit batch selection in offline Kanban.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html';
$html = file_get_contents($source);
if (!is_string($html) || strlen($html)>50000
    || substr_count($html, 'id="select-visible"')!==1
    || substr_count($html, 'id="clear-selection"')!==1
    || substr_count($html, 'id="count-selected"')!==1
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || str_contains($html, '<form')) {
    fwrite(STDERR,"F3-177: invalid offline multi-selection fixture.\n");
    exit(1);
}

$browser=null;
$finder=new ExecutableFinder();
foreach(['google-chrome','google-chrome-stable','chromium','chromium-browser'] as $name){
    $browser=$finder->find($name);
    if($browser!==null)break;
}
if($browser===null){fwrite(STDERR,"F3-177: Chromium required.\n");exit(1);}
$url=json_encode('file://'.$source, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

$driver=<<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>F3-177 batch selection browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
 const marker=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
 const frame=document.createElement('iframe');
 frame.style.cssText='width:'+width+'px;height:900px;border:0;display:block';
 frame.width=width;frame.height=900;
 let finished=false;
 function check(ok,msg){if(!ok)throw new Error(msg);}
 function finish(status,msg){if(finished)return;finished=true;marker.setAttribute('data-imagine-browser-smoke',status);
  marker.textContent='F3-177 '+status+': '+msg;}
 frame.addEventListener('load',function(){
  try{
   const d=frame.contentDocument,w=frame.contentWindow;
   check(d&&w&&w.innerWidth===width,'actual local viewport');
   const by=id=>d.getElementById(id),card=id=>d.querySelector('[data-card-id="'+id+'"]');
   const select=id=>card(id).querySelector('[data-select]').click();
   const search=value=>{
    const field=by('fiction-search');field.value=value;
    field.dispatchEvent(new w.Event('input',{bubbles:true}));
   };
   check(d.querySelectorAll('[data-select]').length===8,'eight selectable fake cards');
   check(by('count-selected').textContent.includes('0 selecionados'),'no selection initially');
   check(by('clear-selection').disabled,'clear initially disabled');
   check(!by('advance-visible-stage').disabled,'legacy all-visible batch mode retained');
   select('F01');
   check(card('F01').querySelector('[data-select]').getAttribute('aria-pressed')==='true',
    'selected card exposes pressed state');
   check(d.activeElement?.dataset.select==='F01','selection keeps keyboard focus');
   check(by('count-selected').textContent.includes('1 selecionados visíveis'),'one selected item');
   check(!by('clear-selection').disabled,'clear enabled');
   d.querySelector('[data-priority="normal"]').click();
   check(by('count-visible').textContent==='4','normal filter shown');
   check(by('count-selected').textContent.includes('0 selecionados visíveis de 1'),
    'selected but hidden card does not count as visible');
   check(by('advance-visible-stage').disabled,'cannot move hidden selected item');
   check(by('complete-visible-followups').disabled,'cannot complete hidden selected item');
   d.querySelector('[data-priority="all"]').click();
   by('advance-visible-stage').click();
   check(d.querySelector('#lane-1 [data-card-id="F01"]')!==null,'only selected high card advanced');
   check(d.querySelector('#lane-0 [data-card-id="F02"]')!==null,'unselected normal card untouched');
   by('undo-change').click();
   check(d.querySelector('#lane-0 [data-card-id="F01"]')!==null,'selected batch undone');
   by('redo-change').click();
   check(d.querySelector('#lane-1 [data-card-id="F01"]')!==null,'selected batch redone');
   by('reset-demo').click();
   check(by('count-selected').textContent.includes('0 selecionados'),'reset clears selection');

   // Select every visible high-priority card, preserving normal cards.
   d.querySelector('[data-priority="high"]').click();
   by('select-visible').click();
   check(by('count-selected').textContent.includes('4 selecionados visíveis de 4'),
    'select visible chooses four high cards');
   by('complete-visible-followups').click();
   check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
    'selected followup completed');
   d.querySelector('[data-priority="all"]').click();
   check(card('F06').querySelector('.followup-label').textContent.includes('pendente'),
    'normal pending F06 remains untouched');
   by('undo-change').click();
   check(card('F01').querySelector('.followup-label').textContent.includes('pendente'),
    'selected completion undone');
   by('redo-change').click();
   check(card('F01').querySelector('.followup-label').textContent.includes('concluído'),
    'selected completion redone');
   by('clear-selection').click();
   check(by('clear-selection').disabled,'explicit clear works');
   check(by('count-selected').textContent.includes('0 selecionados'),'selection count clears');
   by('reset-demo').click();

   // Search and selection must compose without touching hidden cards.
   search('f01');
   by('select-visible').click();
   check(by('count-selected').textContent.includes('1 selecionados visíveis de 1'),
     'search-scoped select visible picks only F01');
   by('advance-visible-stage').click();
   check(d.querySelector('#lane-1 [data-card-id="F01"]')!==null,'filtered selected F01 advanced');
   search('');
   check(d.querySelector('#lane-0 [data-card-id="F02"]')!==null,'hidden F02 remained in stage 0');
   by('undo-change').click();
   check(d.querySelector('#lane-0 [data-card-id="F01"]')!==null,'search-scoped batch undo');
   by('add-fiction').click();
   check(by('count-selected').textContent.includes('0 selecionados'),'creation clears selection');
   by('undo-change').click();
   by('reset-demo').click();

   // Upper bound stays 12 cards even with selective operations.
   for(let i=0;i<4;i++)by('add-fiction').click();
   check(by('count-visible').textContent==='12','twelve fake cards maximum');
   by('select-visible').click();
   check(by('count-selected').textContent.includes('12 selecionados visíveis de 12'),
     'maximum selection bounded to twelve');
   check(by('add-fiction').disabled,'no thirteenth card');
   by('reset-demo').click();
   check(by('count-visible').textContent==='8'&&by('clear-selection').disabled,
      'reset restores eight with no selected IDs');
   check(d.documentElement.scrollWidth<=width,'no horizontal overflow');
   check(d.querySelectorAll('input').length===1&&d.querySelectorAll('form').length===0,
      'selection adds no inputs or capture forms');
   finish('PASS','selection, scoped bulk+undo+redo, filtering, reset and 12-card bound at '+width+'px');
  }catch(error){finish('FAIL',error.message);}
 });
 frame.src=__SOURCE__;
 document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach([1100,320] as $width){
    $file=sys_get_temp_dir().'/imagine-selection-'.bin2hex(random_bytes(12)).'.html';
    $markup=str_replace(['__WIDTH__','__SOURCE__'],[(string)$width,$url],$driver);
    if(file_put_contents($file,$markup)===false){
        fwrite(STDERR,"F3-177: could not write temporary browser driver.\n");exit(1);
    }
    try{
        $result=BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1350,990','--virtual-time-budget=3000','--allow-file-access-from-files'],
            'file://'.$file,'data-imagine-browser-smoke'
        );
        if(!$result['passed']){fwrite(STDERR,"F3-177 {$width}px FAIL: {$result['reason']}\n");exit(1);}
    }finally{@unlink($file);}
}
echo "F3-177 PASS: actual Chromium selection and safe batch actions at 1100/320 CSS px.\n";
