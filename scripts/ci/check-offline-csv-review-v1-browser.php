<?php

declare(strict_types=1);

// UX F3-106: genuine Chromium verification of the static CSV-review V1 UI.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$page = $root.'/prototypes/f3-106-csv-review-v1/index.html';
$html = file_get_contents($page);
if (!is_string($html)
    || strlen($html) > 16000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-106: missing offline HTML or immutable import denial.\n");
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
    fwrite(STDERR, "F3-106: Chromium unavailable; no simulated pass allowed.\n");
    exit(1);
}
$target = json_encode('file://'.$page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>CSV review v1 QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  'use strict';
  const marker=document.querySelector('[data-imagine-browser-smoke]'),width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;width:'+width+'px;height:980px;border:0';
  frame.width=width;frame.height=980;
  let done=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(status,reason){
    if(done)return;done=true;
    marker.setAttribute('data-imagine-browser-smoke',status);
    marker.textContent='F3-106 '+status+': '+reason;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(!!doc&&!!win&&win.innerWidth===width,'real isolated local viewport');
      const text=id=>doc.getElementById(id).textContent.trim();
      const alerts=()=>Array.from(doc.querySelectorAll('#alerts tr'));
      const cases=[
        ['clean','1','3','0','0','SCOPE_NOT_VERIFIED',1,'Não'],
        ['warnings','2','3','2','6','SCOPE_NOT_VERIFIED',6,'Sim'],
        ['scope','2','3','2','6','CONTRACT_INVALID',6,'Sim']
      ];
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction notice');
      check(!doc.querySelector('form,input,textarea,iframe'),'no capture or frames');
      check(doc.querySelectorAll('button[data-case]').length===3,'exactly three examples');
      for(const [id,rows,columns,empty,findings,reason,count,required] of cases){
        const button=doc.querySelector('[data-case="'+id+'"]');
        check(!!button,'scenario '+id+' present');
        button.focus();button.click();
        check(doc.activeElement===button,'keyboard focus preserved');
        check(button.getAttribute('aria-pressed')==='true','selected state visible');
        check(doc.querySelectorAll('[data-case][aria-pressed="true"]').length===1,'one selected sample');
        check(text('rows')===rows&&text('columns')===columns&&text('empty')===empty&&text('findings')===findings,'PHP metadata parity '+id);
        check(text('version')==='v1'&&text('denial')===reason,'fixed version and denial '+id);
        check(text('required-analysis')===required,'optional required analysis state');
        check(alerts().length===count,'only position-only rows');
        check(text('status').includes('Importação proibida'),'all reports remain denied');
        check(text('permissions')==='Importar: NÃO · Persistir: NÃO · Habilitar rota: NÃO · Autorização: NÃO','no import/write powers');
        check(doc.documentElement.scrollWidth<=width,'no horizontal overflow '+id);
      }
      check(alerts().some(row=>row.textContent.includes('Possível fórmula')),'formula coordinates visible');
      check(doc.querySelector('a[href="../f3-72-csv-checklist/index.html"]')!==null,'local checklist link');
      finish('PASS','three real reports in Chromium '+width+'px');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $driverFile = sys_get_temp_dir().'/imagine-csv-review-v1-'.bin2hex(random_bytes(12)).'.html';
    if (file_put_contents($driverFile, str_replace(
        ['__TARGET__', '__WIDTH__'], [$target, (string)$width], $driver,
    )) === false) {
        fwrite(STDERR, "F3-106: failed to create ephemeral Chromium driver.\n");
        exit(1);
    }

    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1360,1040', '--virtual-time-budget=3200', '--allow-file-access-from-files'],
            'file://'.$driverFile,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-106 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($driverFile);
    }
}

echo "F3-106 real Chromium CSV review V1: PASS desktop and 320px.\n";
