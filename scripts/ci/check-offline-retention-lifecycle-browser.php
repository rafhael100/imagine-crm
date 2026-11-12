<?php

declare(strict_types=1);

// UX F3-104: actual Chromium tests of local, static lifecycle preview.
// Fail closed when real browser is unavailable. Never open external URLs.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-104-retention-lifecycle/index.html';
$html = file_get_contents($page);
if (!is_string($html)
    || strlen($html) > 17000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-104: invalid local preview or missing privacy boundaries.\n");
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
    fwrite(STDERR, "F3-104: real Chromium required.\n");
    exit(1);
}
$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Lifecycle browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const marker=document.querySelector('[data-imagine-browser-smoke]');
  const width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;border:0;width:'+width+'px;height:1000px';
  frame.width=width;frame.height=1000;
  let finished=false;
  function check(condition,message){if(!condition)throw new Error(message);}
  function done(status,message){
    if(finished)return;
    finished=true;
    marker.setAttribute('data-imagine-browser-smoke',status);
    marker.textContent='F3-104 '+status+': '+message;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(!!doc&&!!win,'local same-origin preview');
      check(win.innerWidth===width,'exact CSS viewport');
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction notice');
      check(!doc.querySelector('form,input,textarea,iframe'),'no data capture elements');
      const text=id=>doc.getElementById(id).textContent.trim();
      const cases=[
        ['trial-before','Antes da revisão','60','5184000','Trial'],
        ['trial-active','Antes da revisão','60','5184000','Trial'],
        ['trial-review','Em revisão ilustrativa','60','5184000','Trial'],
        ['trial-final','Em revisão ilustrativa','1','1','Trial'],
        ['trial-elapsed','Janela ilustrativa encerrada','0','0','Trial'],
        ['cancel-before','Antes do cancelamento','120','10368000','Cancelamento'],
        ['cancel-review','Em revisão ilustrativa','120','10368000','Cancelamento'],
        ['cancel-final','Em revisão ilustrativa','1','1','Cancelamento'],
        ['cancel-elapsed','Janela ilustrativa encerrada','0','0','Cancelamento'],
        ['billing-before','Antes do vencimento','8','604801','Vencimento fictício'],
        ['billing-due','Antes de 7 dias','7','604800','Vencimento fictício'],
        ['billing-first-final','Antes de 7 dias','1','1','Vencimento fictício'],
        ['billing-first','Entre os marcos de 7 e 30 dias','23','1987200','Vencimento fictício'],
        ['billing-second-final','Entre os marcos de 7 e 30 dias','1','1','Vencimento fictício'],
        ['billing-second','Marco de 30 dias alcançado','0','0','Vencimento fictício'],
        ['billing-elapsed','Marco de 30 dias alcançado','0','0','Vencimento fictício']
      ];
      check(doc.querySelectorAll('[data-case]').length===16,'sixteen fixed scenarios');
      check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'one default selection');
      for(const [id,phase,days,seconds,kind] of cases){
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'known scenario control');
        button.focus();button.click();
        check(doc.activeElement===button,'focus stays with selected button');
        check(button.getAttribute('aria-pressed')==='true','selected scenario aria state');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'exclusive selection');
        check(text('phase')===phase&&text('days')===days&&text('seconds')===seconds&&text('kind')===kind,'scenario '+id+' matches PHP pinned fixture');
        check(text('status').includes('Nenhuma ação operacional autorizada.'),'no operational authority');
        check(text('denials')==='Acesso: NÃO · Cobrança: NÃO · Exclusão: NÃO · Restauração: NÃO · Reativação: NÃO','no authorization appears in any state');
        check(text('start-date').endsWith('Z')&&text('end-date').endsWith('Z'),'absolute dates in UTC');
        check(kind!=='Vencimento fictício'?text('first-marker-date')==='Não aplicável':text('first-marker-date')==='2026-11-14T12:00:00.000Z','seven-day review marker shown only for billing cases');
        check(kind!=='Vencimento fictício'||text('duration-rule').includes('sem cobrança ou bloqueio'),'unverified bill cannot trigger collection');
        check(doc.documentElement.scrollWidth<=width,'no horizontal overflow '+id);
      }
      check(doc.querySelector('a[href="../index.html"]')!==null,'relative hub navigation');
      done('PASS','sixteen scenarios in real '+width+'px Chromium');
    }catch(error){done('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $driverFile = sys_get_temp_dir().'/imagine-retention-lifecycle-'.bin2hex(random_bytes(12)).'.html';
    $source = str_replace(['__TARGET__', '__WIDTH__'], [$target, (string) $width], $driver);
    if (file_put_contents($driverFile, $source) === false) {
        fwrite(STDERR, "F3-104: browser test driver could not be created.\n");
        exit(1);
    }

    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1040', '--virtual-time-budget=3500', '--allow-file-access-from-files'],
            'file://'.$driverFile,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-104 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($driverFile);
    }
}

echo "F3-104 real Chromium lifecycle review: PASS desktop and 320px.\n";
