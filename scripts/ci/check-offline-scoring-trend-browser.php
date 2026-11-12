<?php

declare(strict_types=1);

// UX F3-113: require real Chromium for synthetic scoring trend chart QA.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-113-scoring-trend/index.html';
$html = file_get_contents($page);
if (!is_string($html)
    || strlen($html) > 14000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-113: invalid offline scoring UI or missing privacy boundaries.\n");
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
    fwrite(STDERR, "F3-113: cannot claim Chromium pass without Chromium.\n");
    exit(1);
}
$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Scoring chart QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const marker=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.width=width;frame.height=1050;
  frame.style.cssText='display:block;width:'+width+'px;height:1050px;border:0';
  let done=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(status,why){
    if(done)return;done=true;
    marker.setAttribute('data-imagine-browser-smoke',status);
    marker.textContent='F3-113 '+status+': '+why;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win&&win.innerWidth===width,'isolated local CSS viewport');
      const get=id=>doc.getElementById(id),text=id=>get(id).textContent.trim();
      const chart=()=>Array.from(doc.querySelectorAll('#score-chart .bar-item'));
      const cases=[
        ['growth','4','15 → 75','+60','2','Altas: 3 · Quedas: 0 · Estáveis: 0',4,'15%'],
        ['mixed','6','20 → 42','+22','3','Altas: 2 · Quedas: 2 · Estáveis: 1',6,'20%'],
        ['decline','3','90 → 30','-60','1','Altas: 0 · Quedas: 2 · Estáveis: 0',3,'90%'],
        ['empty','0','Sem amostras','—','0','Altas: 0 · Quedas: 0 · Estáveis: 0',0,null]
      ];
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction notice');
      check(!doc.querySelector('form,input,textarea,iframe'),'no capture or embedding');
      check(doc.querySelectorAll('[data-case]').length===4,'four charts');
      for(const [id,count,range,delta,changes,steps,bars,firstHeight] of cases){
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'known scoring scenario');
        button.focus();button.click();
        check(doc.activeElement===button,'selection preserves keyboard focus');
        check(button.getAttribute('aria-pressed')==='true','selected aria state');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'one selected scenario');
        check(text('sample-count')===count&&text('score-range')===range&&text('score-delta')===delta,'score report '+id);
        check(text('band-changes')===changes&&text('step-summary')===steps,'event trend counts '+id);
        check(chart().length===bars,'visual bar count matches core');
        check(get('empty-note').hidden===(bars>0),'empty state matches bar count');
        if(firstHeight!==null)check(chart()[0].querySelector('.bar').style.height===firstHeight,'normalized first bar');
        check(text('status').includes('sem evento real verificado.'),'no event provenance claimed');
        check(text('rights')==='Reclassificar: NÃO · Notificar: NÃO · Automatizar: NÃO · Gravar: NÃO','all operations denied');
        check(doc.documentElement.scrollWidth<=width,'no page-level horizontal overflow '+id);
      }
      check(doc.querySelector('a[href="../index.html"]')!==null,'local hub navigation');
      finish('PASS','four real scoring charts at '+width+'px');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-score-chart-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($file, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-113: temporary Chromium driver unavailable.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1140', '--virtual-time-budget=3400', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-113 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-113 real Chromium scoring trend chart: PASS desktop and 320px.\n";
