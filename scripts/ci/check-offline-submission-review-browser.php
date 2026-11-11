<?php

declare(strict_types=1);

// F3-81: real Chrome/Chromium smoke. Only four hard-coded synthetic reports.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$pageFile = $root.'/prototypes/f3-81-submission-review/index.html';
$html = file_get_contents($pageFile);

if (!is_string($html)
    || strlen($html) > 15000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-81: invalid offline HTML or missing CSP/fiction warning.\n");
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
    fwrite(STDERR, "F3-81: Chromium required; cannot claim a simulated pass.\n");
    exit(1);
}

$target = json_encode(
    'file://'.$pageFile,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>CRM submission claims QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const mark=document.querySelector('[data-imagine-browser-smoke]');
  const width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.width=width; frame.height=1140;
  frame.style.cssText='display:block;border:0;width:'+width+'px;height:1140px';
  let done=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(status,why){
    if(done)return;
    done=true;
    mark.setAttribute('data-imagine-browser-smoke',status);
    mark.textContent='F3-81 '+status+': '+why;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'same-origin local fixture');
      check(win.innerWidth===width,'actual viewport width');
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'explicit fiction warning');
      check(!doc.querySelector('form,input,textarea'),'no contact input or upload');
      const get=id=>doc.getElementById(id).textContent.trim();
      const rows=()=>Array.from(doc.querySelectorAll('#finding-rows tr'));
      const buttons=Array.from(doc.querySelectorAll('[data-case]'));
      check(buttons.length===4,'four synthetic scenarios');
      check(get('count-claims')==='2','two technical claims per fixture');
      check(get('count-same')==='0'&&get('count-binding')==='0'&&get('count-org')==='0','clean fixture');
      check(rows().length===1&&rows()[0].textContent.includes('Nenhum aviso'),'clean still denied');
      check(get('review-status').includes('Entrega não autorizada'),'no positive delivery authorization');

      buttons[1].focus();buttons[1].click();
      check(doc.activeElement===buttons[1],'keyboard focus stays on scenario B');
      check(buttons[1].getAttribute('aria-pressed')==='true','B selected');
      check(rows().length===1&&get('count-same')==='1','repeated claim counted');
      check(rows()[0].textContent.includes('Posições 1 e 2'),'repeated position visible');

      buttons[2].click();
      check(get('count-same')==='0'&&get('count-binding')==='1','binding conflict only');
      check(rows().length===1&&rows()[0].textContent.includes('Vínculo técnico divergente'),'binding category');
      check(rows()[0].textContent.includes('Posições 1 e 2'),'binding positions');

      buttons[3].click();
      check(get('count-org')==='1'&&get('count-binding')==='0','organization conflict only');
      check(rows().length===1&&rows()[0].textContent.includes('Organização declarada divergente'),'organization category');
      check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'single selected button');

      buttons[0].click();
      check(get('count-org')==='0'&&rows().length===1,'reset scenario clears stale rows');
      check(get('review-status').includes('Nenhuma organização foi autenticada'),'auth explicitly unverified');
      check(doc.documentElement.scrollWidth<=width,'no page horizontal overflow');
      check(doc.querySelector('a[href="../index.html"]')!==null,'local hub link');
      finish('PASS','real Chromium '+width+'px');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-submission-review-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($file, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-81: cannot create temporary browser driver.\n");
        exit(1);
    }

    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1370,1180', '--virtual-time-budget=3200', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-81 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-81 real Chromium synthetic submission review: PASS desktop and 320px.\n";
