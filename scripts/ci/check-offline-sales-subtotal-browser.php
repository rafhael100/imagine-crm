<?php

declare(strict_types=1);

// UX F3-109: require a real offline Chromium session, not a text-only pass.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-109-sales-quote-subtotal/index.html';
$html = file_get_contents($page);
if (!is_string($html)
    || strlen($html) > 15000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-109: offline quote fixture or CSP invalid.\n");
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
    fwrite(STDERR, "F3-109: real Chromium required.\n");
    exit(1);
}

$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Sales quote preview QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const mark=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;width:'+width+'px;height:1200px;border:0';
  frame.width=width;frame.height=1200;
  let done=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(status,why){
    if(done)return;done=true;
    mark.setAttribute('data-imagine-browser-smoke',status);
    mark.textContent='F3-109 '+status+': '+why;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win&&win.innerWidth===width,'isolated local viewport');
      const get=id=>doc.getElementById(id);
      const text=id=>get(id).textContent.trim();
      const rows=()=>Array.from(doc.querySelectorAll('#item-rows tr'));
      const select=id=>{
        const b=doc.querySelector('[data-case="'+id+'"]');
        check(!!b,'known fixture scenario');b.focus();b.click();
        check(doc.activeElement===b,'scenario keyboard focus retained');
        check(b.getAttribute('aria-pressed')==='true','selected aria state');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'exactly one active scenario');
      };
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'synthetic warning');
      check(!doc.querySelector('form,input,textarea,iframe'),'no user input or file capture');
      check(doc.querySelectorAll('[data-case]').length===3,'three invented bundles');
      check(text('count')==='3'&&text('subtotal')==='R$ 339,79','exact initial subtotal');
      check(rows().length===3,'three initial rows');
      check(text('rights')==='Emitir proposta: NÃO · Aceitar proposta: NÃO · Cobrar: NÃO · Gravar: NÃO','permanent deny flags');

      let plus=doc.querySelector('button[data-index="0"][data-delta="1"]');
      check(!!plus,'quantity increase exists');
      plus.focus();plus.click();
      check(text('subtotal')==='R$ 469,69','integer-cent increase updates subtotal');
      check(doc.activeElement.dataset.delta==='1','increase keeps keyboard focus');
      let minus=doc.querySelector('button[data-index="0"][data-delta="-1"]');
      minus.click();
      check(text('subtotal')==='R$ 339,79','decrease restores subtotal');
      check(doc.activeElement.dataset.delta==='-1','decrease keeps keyboard focus');

      select('compact');
      check(rows().length===2&&text('subtotal')==='R$ 335,00','second fixture matches PHP reference');
      select('empty');
      check(rows().length===0&&text('count')==='0'&&text('subtotal')==='R$ 0,00','empty fixture is zero');
      check(!get('empty-note').hidden&&get('item-table-wrap').hidden,'accessible empty state');
      select('basic');
      check(rows().length===3&&text('subtotal')==='R$ 339,79','switching restores original case');
      check(get('empty-note').hidden&&!get('item-table-wrap').hidden,'table restored');
      check(doc.documentElement.scrollWidth<=width,'no horizontal page overflow');
      check(doc.querySelector('a[href="../index.html"]')!==null,'hub link is local');
      check(text('rights')==='Emitir proposta: NÃO · Aceitar proposta: NÃO · Cobrar: NÃO · Gravar: NÃO','no issue/charge authority after edits');
      finish('PASS','real Chromium '+width+'px, 3 fixtures and quantity edits');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-sales-subtotal-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($file, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-109: could not build temporary browser driver.\n");
        exit(1);
    }

    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1250', '--virtual-time-budget=3500', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-109 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-109 real Chromium fictional sales quote subtotal: PASS desktop and 320px.\n";
