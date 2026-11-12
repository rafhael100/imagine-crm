<?php

declare(strict_types=1);

// UX F3-115: real Chromium QA, including exact cents and keyboard edits.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-115-weighted-forecast/index.html';
$html = file_get_contents($page);
if (!is_string($html) || strlen($html) > 15000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-115: privacy/CSP or offline HTML boundary missing.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $binary) {
    $browser = $finder->find($binary);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "F3-115: actual Chromium not installed; no simulated pass.\n");
    exit(1);
}

$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Forecast QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const marker=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;width:'+width+'px;height:1120px;border:0';
  frame.width=width;frame.height=1120;
  let finished=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function done(status,why){
    if(finished)return;finished=true;
    marker.setAttribute('data-imagine-browser-smoke',status);
    marker.textContent='F3-115 '+status+': '+why;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(!!doc&&!!win&&win.innerWidth===width,'local CSS viewport loaded');
      const get=id=>doc.getElementById(id);
      const txt=id=>get(id).textContent.trim();
      const rows=()=>doc.querySelectorAll('#opportunity-rows tr');
      const select=id=>{
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'known sample');button.focus();button.click();
        check(doc.activeElement===button,'sample focus preserved');
        check(button.getAttribute('aria-pressed')==='true','selected aria flag');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'exclusive sample selection');
        check(txt('rights')==='Marcar ganha: NÃO · Faturar: NÃO · Cobrar: NÃO · Gravar: NÃO','no sales permissions');
      };
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'synthetic warning');
      check(!doc.querySelector('form,input,textarea,iframe'),'no customer input');
      check(doc.querySelectorAll('[data-case]').length===4,'four mock forecasts');
      check(txt('count')==='3'&&txt('raw-total')==='R$ 809,89'&&txt('weighted-total')==='R$ 534,95','initial weighted math');

      const increase=doc.querySelector('button[data-index="0"][data-delta="500"]');
      check(!!increase,'probability increase');
      increase.focus();increase.click();
      check(txt('weighted-total')==='R$ 541,45','plus 5 percentage points rounds exact cents');
      check(doc.activeElement.dataset.delta==='500','re-render keeps focus');
      const decrease=doc.querySelector('button[data-index="0"][data-delta="-500"]');
      decrease.click();
      check(txt('weighted-total')==='R$ 534,95','minus 5 percentage points resets result');
      check(doc.activeElement.dataset.delta==='-500','minus re-render keeps focus');

      select('conservative');
      check(rows().length===2&&txt('raw-total')==='R$ 292,50'&&txt('weighted-total')==='R$ 25,00','conservative PHP fixture');
      select('optimistic');
      check(rows().length===2&&txt('raw-total')==='R$ 250,01'&&txt('weighted-total')==='R$ 200,01','optimistic PHP fixture');
      select('empty');
      check(rows().length===0&&txt('count')==='0'&&txt('weighted-total')==='R$ 0,00','empty fixture');
      check(!get('empty-note').hidden&&get('table-wrap').hidden,'empty state');
      select('standard');
      check(rows().length===3&&txt('weighted-total')==='R$ 534,95','reset original predictions');
      check(get('empty-note').hidden&&!get('table-wrap').hidden,'table restored');
      check(doc.documentElement.scrollWidth<=width,'no horizontal overflow');
      check(doc.querySelector('a[href="../index.html"]')!==null,'local hub link');
      done('PASS','four forecasts with editable probabilities at '+width+'px');
    }catch(e){done('FAIL',e.message);}
  });
  frame.src=__TARGET__;document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $driverFile = sys_get_temp_dir().'/imagine-weighted-forecast-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($driverFile, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-115: failed to create temporary browser test.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1200', '--virtual-time-budget=3500', '--allow-file-access-from-files'],
            'file://'.$driverFile,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-115 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($driverFile);
    }
}

echo "F3-115 real Chromium weighted forecast review: PASS desktop and 320px.\n";
