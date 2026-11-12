<?php

declare(strict_types=1);

// UX F3-120: real Chromium checks for fictional proposal validity boundaries.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-120-proposal-validity/index.html';
$html = file_get_contents($page);
if (!is_string($html) || strlen($html) > 12000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-120: invalid local page or missing no-network boundary.\n");
    exit(1);
}
$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $candidate) {
    $browser = $finder->find($candidate);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "F3-120: real Chromium required.\n");
    exit(1);
}
$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Validity browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const mark=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;border:0;width:'+width+'px;height:980px';
  frame.width=width;frame.height=980;
  let finished=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function done(state,reason){
    if(finished)return;finished=true;
    mark.setAttribute('data-imagine-browser-smoke',state);
    mark.textContent='F3-120 '+state+': '+reason;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win&&win.innerWidth===width,'isolated local page loaded');
      const text=id=>doc.getElementById(id).textContent.trim();
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction notice visible');
      check(!doc.querySelector('form,input,textarea,iframe'),'no customer data entry');
      check(doc.querySelectorAll('[data-case]').length===5,'five fixed UTC cases');
      const cases=[
        ['before','Antes da vigência','1','604801'],
        ['start','Dentro do prazo ilustrativo','0','604800'],
        ['last','Dentro do prazo ilustrativo','0','1'],
        ['expired','Expirada','0','0'],
        ['after','Expirada','0','0']
      ];
      for(const [id,phase,toIssue,toExpiry] of cases){
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'known UTC case');
        button.focus();button.click();
        check(doc.activeElement===button,'keyboard focus preserved');
        check(button.getAttribute('aria-pressed')==='true','pressed state correct');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'only one selected');
        check(text('phase')===phase&&text('to-issue')===toIssue&&text('to-expiry')===toExpiry,'exact PHP second parity');
        check(text('issued')==='2026-11-01T12:00:00Z'&&text('expires')==='2026-11-08T12:00:00Z','fixed UTC bounds');
        check(text('status').includes('Prazo ilustrativo não autoriza aceite nem assinatura.'),'no authority from validity');
        check(text('rights')==='Enviar: NÃO · Aceitar: NÃO · Renovar: NÃO · Assinar: NÃO · Cobrar: NÃO · Gravar: NÃO','all rights permanently denied');
        check(doc.documentElement.scrollWidth<=width,'no horizontal page overflow');
      }
      check(doc.querySelector('a[href="../f3-111-proposal-versions/index.html"]')!==null,'local version review navigation');
      done('PASS','five validity boundaries in Chromium '+width+'px');
    }catch(error){done('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-proposal-validity-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($file, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-120: could not write temporary browser driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1040', '--virtual-time-budget=3400', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-120 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-120 real Chromium proposal validity preview: PASS desktop and 320px.\n";
