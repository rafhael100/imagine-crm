<?php

declare(strict_types=1);

// UX F3-138: actual Chromium proof of two fictional cross-module journeys.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-138-commercial-journey/index.html';
$html = file_get_contents($page);
if (!is_string($html)
    || strlen($html) > 18000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-138: invalid offline page or missing data boundary.\n");
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
    fwrite(STDERR, "F3-138: real Chromium required.\n");
    exit(1);
}

$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP
    | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Journey QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
'use strict';
const width=__WIDTH__,marker=document.querySelector('[data-imagine-browser-smoke]');
const frame=document.createElement('iframe');
frame.width=width;frame.height=1000;
frame.style.cssText='display:block;border:0;width:'+width+'px;height:1000px';
let finished=false;
function check(ok,why){if(!ok)throw new Error(why);}
function done(state,msg){
 if(finished)return;finished=true;
 marker.setAttribute('data-imagine-browser-smoke',state);
 marker.textContent='F3-138 '+state+': '+msg;
}
frame.addEventListener('load',function(){
 if(finished)return;
 try{
  const doc=frame.contentDocument,win=frame.contentWindow;
  check(doc&&win&&win.innerWidth===width,'actual CSS viewport');
  const text=id=>doc.getElementById(id).textContent.trim();
  const controls=doc.querySelectorAll('[data-case]');
  check(controls.length===2,'two scenarios');
  check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction warning');
  check(!doc.querySelector('form,input,textarea,iframe'),'no customer input fields');
  const cases=[
   {id:'consistent', score:'90 / 100 · quente', assignment:'Posição 1 (fictícia)',
    opportunity:'Ganha (alegada)', proposal:'Aceita (alegada)',
    task:'Encerrada (alegada)', count:'0', total:'R$ 190,00', list:1},
   {id:'conflict', score:'90 / 100 · quente', assignment:'Sem posição disponível',
    opportunity:'Ganha (alegada)', proposal:'Rascunho fictício',
    task:'Vencida (alegada)', count:'4', total:'R$ 120,00', list:4}
  ];
  for(const item of cases){
   const button=doc.querySelector('[data-case="'+item.id+'"]');
   check(!!button,'known scenario');
   button.focus();button.click();
   check(doc.activeElement===button,'keyboard focus persists');
   check(button.getAttribute('aria-pressed')==='true','selected case');
   check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'single pressed button');
   check(text('lead-score')===item.score,'score band');
   check(text('assignment')===item.assignment,'assignment');
   check(text('opportunity')===item.opportunity,'opportunity');
   check(text('proposal')===item.proposal,'proposal');
   check(text('task')===item.task,'task');
   check(text('warning-count')===item.count,'warning count');
   check(text('quote-total')===item.total,'cent-exact quote');
   check(text('recognized')==='R$ 0,00','no recognized revenue');
   check(doc.querySelectorAll('#warnings li').length===item.list,'warning rows');
   check(text('rights')==='Criar lead: NÃO · Atribuir: NÃO · Mover: NÃO · Enviar: NÃO · Cobrar: NÃO · Gravar: NÃO','rights denied');
   check(doc.documentElement.scrollWidth<=width,'no horizontal overflow');
  }
  check(doc.querySelector('a[href="../index.html"]')!==null,'local hub link');
  if(width===320){
   const columns=win.getComputedStyle(doc.querySelector('.flow')).gridTemplateColumns;
   check(columns.split(' ').length===1,'mobile journey is one column');
  }
  done('PASS','two end-to-end fictional journeys at '+width+'px');
 }catch(e){done('FAIL',e.message);}
});
frame.src=__TARGET__;
document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-journey-'.bin2hex(random_bytes(12)).'.html';
    $source = str_replace(['__TARGET__', '__WIDTH__'], [$target, (string) $width], $driver);
    if (file_put_contents($file, $source) === false) {
        fwrite(STDERR, "F3-138: cannot write temporary Chromium driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1380,1080', '--virtual-time-budget=3200', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-138 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}
echo "F3-138 actual Chromium journey UI: PASS 1100px and 320px.\n";
