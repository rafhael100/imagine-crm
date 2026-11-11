<?php

declare(strict_types=1);

// F3-72 real Chrome UI smoke: browser gets ONLY fictitious fixed metadata.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$pageFile = $root.'/prototypes/f3-72-csv-checklist/index.html';
$html = file_get_contents($pageFile);
if (!is_string($html)
    || strlen($html) > 18000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-72: missing offline HTML, local-only warning or CSP.\n");
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
    fwrite(STDERR, "F3-72: real Chromium required; no simulated PASS.\n");
    exit(1);
}

$target = json_encode(
    'file://'.$pageFile,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>CRM CSV checklist Chrome QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const mark=document.querySelector('[data-imagine-browser-smoke]');
  const width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.width=width; frame.height=1150;
  frame.style.cssText='display:block;width:'+width+'px;height:1150px;border:0';
  let done=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(state,why){
    if(done)return;
    done=true;
    mark.setAttribute('data-imagine-browser-smoke',state);
    mark.textContent='F3-72 '+state+': '+why;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'local same-origin fixture');
      check(win.innerWidth===width,'real CSS viewport');
      const get=id=>doc.getElementById(id).textContent.trim();
      const items=()=>Array.from(doc.querySelectorAll('#check-rows tr'));
      const buttons=Array.from(doc.querySelectorAll('[data-case]'));
      check(buttons.length===4,'exact four invented examples');
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'explicit fixture warning');
      check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'single selected case');
      check(get('metric-rows')==='2'&&get('metric-columns')==='2'&&get('metric-mapped')==='2','clean aggregate');
      check(get('metric-findings')==='0'&&items().length===1,'clean case with explanation only');
      check(get('review-status').includes('Importação proibida'),'never implies CRM write');
      check(!doc.querySelector('form,input,textarea'),'no user inputs or upload');

      buttons[1].focus();
      buttons[1].click();
      check(doc.activeElement===buttons[1],'keyboard focus retained on case B');
      check(buttons[1].getAttribute('aria-pressed')==='true','B selected');
      check(get('metric-rows')==='3'&&get('metric-columns')==='3','B dimensions');
      check(get('metric-findings')==='5'&&items().length===5,'B five position warnings');
      check(items().some(r=>r.textContent.includes('Primeira linha 2; repetida na linha 3')),'repeat position');
      check(items().some(r=>r.textContent.includes('Coluna requerida nº 3')),'missing required header position');
      check(items().some(r=>r.textContent.includes('Linha 1, coluna 1')),'empty required cell position');

      buttons[2].click();
      check(get('metric-rows')==='2'&&get('metric-findings')==='2','formula case counts');
      check(items().length===2&&items().every(r=>r.textContent.includes('Possível fórmula')),'formula warning categories');
      check(items().some(r=>r.textContent.includes('Linha 2, coluna 3')),'second formula position');

      buttons[3].click();
      check(get('metric-rows')==='1'&&get('metric-mapped')==='1','missing header case');
      check(get('metric-findings')==='2'&&items().length===2,'two absent technical headers');
      check(items().every(r=>r.textContent.includes('Cabeçalho técnico')),'only missing headers');

      buttons[0].click();
      check(get('metric-findings')==='0'&&items().length===1,'switching back clears stale warnings');
      check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'single selected button after return');
      check(get('review-status').includes('nenhuma pessoa ou empresa foi verificada'),'no false authorization');
      check(doc.documentElement.scrollWidth<=width,'no horizontal page overflow');
      check(doc.querySelector('a[href="../f3-22-csv-risk-preview/index.html"]')!==null,'local back link');
      finish('PASS','real Chromium '+width+'px');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-csv-checklist-'.bin2hex(random_bytes(12)).'.html';
    $page = str_replace(['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver);
    if (file_put_contents($file, $page) === false) {
        fwrite(STDERR, "F3-72: cannot create temporary browser driver.\n");
        exit(1);
    }

    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1370,1180', '--virtual-time-budget=3100', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-72 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-72 real Chromium CSV checklist: PASS desktop and 320px.\n";
