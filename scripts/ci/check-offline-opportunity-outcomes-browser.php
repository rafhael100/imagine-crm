<?php

declare(strict_types=1);

// UX F3-117 real Chromium QA for outcome/loss-reason sample dashboard.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-117-opportunity-outcomes/index.html';
$html = file_get_contents($page);
if (!is_string($html) || strlen($html) > 13000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-117: offline privacy boundaries missing.\n");
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
    fwrite(STDERR, "F3-117: actual Chromium required.\n");
    exit(1);
}
$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Opportunity outcome QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const marker=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;width:'+width+'px;height:1020px;border:0';
  frame.width=width;frame.height=1020;
  let finished=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function done(status,why){
    if(finished)return;finished=true;
    marker.setAttribute('data-imagine-browser-smoke',status);
    marker.textContent='F3-117 '+status+': '+why;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(!!doc&&!!win&&win.innerWidth===width,'local viewport loaded');
      const get=id=>doc.getElementById(id),text=id=>get(id).textContent.trim();
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction notice');
      check(!doc.querySelector('form,input,textarea,iframe'),'no customer data capture');
      check(doc.querySelectorAll('[data-case]').length===4,'four fictional outcome cases');
      const cases=[
        ['mixed','5','1','1','3','2','1'],
        ['losses','3','0','0','3','0','0'],
        ['wins','2','0','2','0','0','0'],
        ['empty','0','0','0','0','0','0']
      ];
      for(const [id,total,open,won,lost,price,competition] of cases){
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'fixture button exists');button.focus();button.click();
        check(doc.activeElement===button,'keyboard focus after switching');
        check(button.getAttribute('aria-pressed')==='true','aria selection');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'single selection');
        check(text('total')===total&&text('open')===open&&text('won')===won&&text('lost')===lost,'counter parity '+id);
        const reasons=Array.from(doc.querySelectorAll('#reason-rows tr'));
        check(reasons.length===5,'five fixed loss reason rows');
        check(reasons[0].querySelectorAll('td')[1].textContent.trim()===price,'price counts');
        check(reasons[2].querySelectorAll('td')[1].textContent.trim()===competition,'competitor counts');
        check(text('status').includes('Nenhum resultado, venda ou histórico foi verificado.'),'outcome not authenticated');
        check(text('verified')==='Vendas reconhecidas: 0 · Resultados verificados: NÃO','no verified revenue');
        check(text('rights')==='Marcar ganha: NÃO · Marcar perdida: NÃO · Reabrir: NÃO · Contar receita: NÃO · Gravar: NÃO','no operational capability');
        check(doc.documentElement.scrollWidth<=width,'no page horizontal overflow');
      }
      check(doc.querySelector('a[href="../index.html"]')!==null,'local hub link');
      done('PASS','four outcome/reason scenarios in Chromium '+width+'px');
    }catch(e){done('FAIL',e.message);}
  });
  frame.src=__TARGET__;document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-outcome-review-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($file, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-117: could not write temporary browser driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1060', '--virtual-time-budget=3500', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-117 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-117 real Chromium opportunity outcome review: PASS desktop and 320px.\n";
