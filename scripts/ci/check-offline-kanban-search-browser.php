<?php

declare(strict_types=1);

// F3-172: actual Chromium smoke for the in-memory-only Kanban search.
// Runs with a file:// fixture at desktop and 320 CSS pixels, no server.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f3-59-offline-kanban/index.html';
$raw = file_get_contents($source);
if (!is_string($raw) || substr_count($raw, 'id="fiction-search"') !== 1
    || !str_contains($raw, "connect-src 'none'")
    || !str_contains($raw, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || str_contains($raw, '<form')) {
    fwrite(STDERR, "F3-172: the offline search guard is missing.\n");
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
    fwrite(STDERR, "F3-172: an actual Chromium binary is required.\n");
    exit(1);
}

$sourceUrl = json_encode('file://'.$source, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>F3-172 offline search QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const marker=document.querySelector('[data-imagine-browser-smoke]');
  const frame=document.createElement('iframe');
  const width=__WIDTH__;
  frame.style.cssText='display:block;width:'+width+'px;height:900px;border:0';
  frame.width=width;frame.height=900;
  let finished=false;
  function check(condition,message){if(!condition)throw new Error(message);}
  function done(kind,message){
    if(finished)return;
    finished=true;
    marker.setAttribute('data-imagine-browser-smoke',kind);
    marker.textContent='F3-172 '+kind+': '+message;
  }
  frame.addEventListener('load',function(){
    try{
      const d=frame.contentDocument,w=frame.contentWindow;
      check(d&&w&&w.innerWidth===width,'real '+width+'px local viewport');
      const field=d.getElementById('fiction-search');
      check(field&&field.type==='search'&&field.maxLength===32,'search input length bound');
      check(field.getAttribute('autocomplete')==='off','no saved search suggestions');
      check(d.getElementById('count-visible').textContent==='8','eight fictitious cards initially');
      const search=function(value){
        field.value=value;
        field.dispatchEvent(new w.Event('input',{bubbles:true}));
      };
      field.focus();
      search('f01');
      check(d.activeElement===field,'keyboard input retained focus');
      check(d.getElementById('count-visible').textContent==='1','case insensitive ID search');
      check(d.querySelectorAll('[data-card-id]').length===1,'only one visible card');
      check(d.querySelector('[data-card-id="F01"]')!==null,'matching card F01');
      check(d.querySelector('[data-card-id="F02"]')===null,'unmatched card F02 hidden');
      check(d.querySelectorAll('#followup-queue tr').length===1,'queue follows same search');
      check(d.getElementById('complete-visible-followups').disabled===false,'one visible followup available');
      d.querySelector('[data-priority="normal"]').click();
      check(d.getElementById('count-visible').textContent==='0','combined priority/search zero');
      check(d.getElementById('complete-visible-followups').disabled,'bulk followup disabled on empty intersection');
      check(d.getElementById('advance-visible-stage').disabled,'bulk stage disabled on empty intersection');
      d.querySelector('[data-priority="all"]').click();
      check(d.getElementById('count-visible').textContent==='1','search retained while changing priority');
      d.getElementById('complete-visible-followups').click();
      check(d.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('concluído'),
        'only selected fake followup completed');
      d.getElementById('undo-change').click();
      check(d.querySelector('[data-card-id="F01"] .followup-label').textContent.includes('pendente'),
        'search-scoped batch undo restores target');
      d.getElementById('advance-visible-stage').click();
      check(d.querySelector('#lane-1 [data-card-id="F01"]')!==null,'single visible card advanced');
      check(d.getElementById('lane-count-0').textContent==='0','old lane now empty');
      d.getElementById('undo-change').click();
      check(d.querySelector('#lane-0 [data-card-id="F01"]')!==null,'stage undo restores selected card');
      d.getElementById('toggle-csv-demo').click();
      const preview=d.getElementById('csv-preview').textContent;
      check(preview.includes('F01;'),'CSV preview respects same search');
      check(!preview.includes('F02;'),'CSV omits hidden F02');
      check(!preview.includes('@')&&!preview.includes('http'),'CSV has no contact or link');
      search('absent');
      check(d.getElementById('count-visible').textContent==='0','no-match is a valid state');
      check(d.querySelectorAll('[data-card-id]').length===0,'no-match hides all cards');
      check(d.getElementById('complete-visible-followups').disabled,'no-match cannot run bulk action');
      d.getElementById('reset-demo').click();
      check(field.value===''&&d.getElementById('count-visible').textContent==='8','reset clears filter and restores board');
      search('F01');
      d.getElementById('add-fiction').click();
      check(field.value===''&&d.getElementById('count-visible').textContent==='9','fictional creation clears local search');
      check(d.querySelector('[data-card-id="F09"]')!==null,'new fake card visible after search cleared');
      d.getElementById('undo-change').click();
      check(d.getElementById('count-visible').textContent==='8','creation undo restores eight');
      check(d.documentElement.scrollWidth<=width,'no horizontal overflow at '+width+'px');
      check(d.querySelectorAll('input').length===1&&d.querySelectorAll('form').length===0,
        'no hidden input or form added');
      done('PASS','filter parity, bulk action bounds, undo, CSV and reset at '+width+'px');
    }catch(error){done('FAIL',error.message);}
  });
  frame.src=__SOURCE__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $driverFile = sys_get_temp_dir().'/imagine-kanban-search-'.bin2hex(random_bytes(12)).'.html';
    $html = str_replace(['__WIDTH__','__SOURCE__'],[(string)$width,$sourceUrl],$driver);
    if (file_put_contents($driverFile,$html)===false) {
        fwrite(STDERR,"F3-172: could not create browser driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1350,1000','--virtual-time-budget=3200','--allow-file-access-from-files'],
            'file://'.$driverFile,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR,"F3-172 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($driverFile);
    }
}

echo "F3-172 PASS: real Chromium in-memory search and batch safety at 1100/320 CSS px.\n";
