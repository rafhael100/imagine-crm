<?php

declare(strict_types=1);

// UX F3-111: actual Chromium proof that fictional proposal versions never
// generate acceptance, sending, signing, payment or persistence.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-111-proposal-versions/index.html';
$html = file_get_contents($page);
if (!is_string($html)
    || strlen($html) > 14000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-111: invalid offline proposal version or privacy boundaries.\n");
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
    fwrite(STDERR, "F3-111: real Chromium required.\n");
    exit(1);
}

$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Proposal version QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const mark=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;width:'+width+'px;height:1000px;border:0';
  frame.width=width;frame.height=1000;
  let finished=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function done(status,why){
    if(finished)return;finished=true;
    mark.setAttribute('data-imagine-browser-smoke',status);
    mark.textContent='F3-111 '+status+': '+why;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win&&win.innerWidth===width,'local viewport loaded');
      const get=id=>doc.getElementById(id),text=id=>get(id).textContent.trim();
      const rows=()=>Array.from(doc.querySelectorAll('#version-rows tr'));
      const fixtures=[
        ['draft','1','v1','Rascunho','0','0',1],
        ['viewed','1','v1','Enviada','0','1',1],
        ['revision','2','v2','Rascunho','1','0',2],
        ['accepted','1','v1','Aceite declarado','0','0',1],
        ['empty','0','Nenhuma','Não existe','0','0',0]
      ];
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction warning');
      check(!doc.querySelector('form,input,textarea,iframe'),'no real input or embedding');
      check(doc.querySelectorAll('[data-case]').length===5,'five fictional scenarios');
      for(const [id,count,latest,status,prior,viewed,rowCount] of fixtures){
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'scenario exists');
        button.focus();button.click();
        check(doc.activeElement===button,'scenario keyboard focus');
        check(button.getAttribute('aria-pressed')==='true','selected button state');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'single selection');
        check(text('version-count')===count&&text('latest-version')===latest&&text('latest-status')===status,'PHP version metadata '+id);
        check(text('prior-accepted')===prior&&text('viewed-count').endsWith(viewed),'prior/version view isolation');
        check(rows().length===rowCount,'version table rows');
        check(get('empty-note').hidden===(rowCount>0),'correct empty state');
        check(text('status').includes('Nenhum aceite ou direito confirmado.'),'no asserted acceptance');
        check(text('rights')==='Enviar: NÃO · Aceitar: NÃO · Assinar: NÃO · Cobrar: NÃO · Gravar: NÃO','all operations denied');
        check(doc.documentElement.scrollWidth<=width,'no horizontal page overflow');
      }
      check(doc.querySelector('a[href="../f3-109-sales-quote-subtotal/index.html"]')!==null,'local return to subtotal');
      done('PASS','five proposal history cases in Chromium '+width+'px');
    }catch(error){done('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-version-review-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($file, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-111: could not build temporary Chromium driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1050', '--virtual-time-budget=3400', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-111 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-111 real Chromium proposal version review: PASS desktop and 320px.\n";
