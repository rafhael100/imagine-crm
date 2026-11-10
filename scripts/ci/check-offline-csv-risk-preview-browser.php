<?php

declare(strict_types=1);

// F3-22: real Chromium interaction smoke, entirely invented CSV report data.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-22-csv-risk-preview/index.html');
if (!is_string($html)
    || strlen($html) > 30000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-22: invalid offline HTML or CSP.\n");
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
    fwrite(STDERR, "F3-22: real Chrome/Chromium required; cannot claim a browser pass.\n");
    exit(1);
}

$assertions = <<<'JS'
(function(){
  const result=document.createElement('output');
  result.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(result);
  function check(value,why){if(!value)throw new Error(why);}
  function value(id){return document.getElementById(id).textContent.trim();}
  function pressed(){return Array.from(document.querySelectorAll('[data-sample][aria-pressed="true"]'));}
  function issues(){return Array.from(document.querySelectorAll('#findings-rows tr')).map(tr=>tr.textContent.trim());}
  try{
    check(document.querySelector('main')!==null,'main landmark exists');
    check(document.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS'),'fiction disclaimer');
    check(document.querySelector('a[href="../s01-prot-01/index.html"]')!==null,'local navigation');
    check(!document.querySelector('form,input,textarea,iframe'),'no form or capture surface');
    check(pressed().length===1&&pressed()[0].dataset.sample==='0','initial clean sample selected');
    check(value('count-rows')==='3','clean rows');
    check(value('count-data')==='2','clean data rows');
    check(value('count-columns')==='3','clean columns');
    check(value('count-alerts')==='0','clean alerts');
    check(issues().length===0,'no fabricated clean findings');
    check(!document.getElementById('clean-note').hidden,'clean caution visible');
    check(value('row-flagged')==='0','clean sample has zero flagged data rows');
    check(value('row-unflagged')==='2','clean sample has two rows without detected findings');
    check(value('row-header-risk')==='0','clean sample no header cell warnings');
    check(document.querySelectorAll('#row-review-rows tr').length===2,'two clean row records');
    check(Array.from(document.querySelectorAll('#row-review-rows tr')).every(row=>row.textContent.includes('revisão obrigatória')),'clean rows still require review');
    check(value('map-count')==='3','mapping assignments counted');
    check(value('map-unmapped')==='0','clean mapping has no omitted source columns');
    check(value('map-conflicts')==='0','clean mapping has no detected conflicts');
    check(document.querySelectorAll('#map-rows tr').length===0,'no fabricated mapping conflicts');
    check(value('map-status').includes('Revisão humana continua obrigatória'),'clean mapping still needs review');
    const conflictingMap=document.querySelector('[data-map="1"]');
    conflictingMap.focus();
    conflictingMap.click();
    check(document.activeElement===conflictingMap,'mapping choice keeps keyboard focus');
    check(document.querySelectorAll('[data-map][aria-pressed="true"]').length===1,'one mapping example selected');
    check(value('map-unmapped')==='1','one source column not assigned');
    check(value('map-conflicts')==='2','two mapping issue categories');
    const mappedIssues=Array.from(document.querySelectorAll('#map-rows tr')).map(row=>row.textContent);
    check(mappedIssues.length===3,'three mapping diagnostic rows');
    check(mappedIssues.some(row=>row.includes('Coluna 3')&&row.includes('Sem atribuição')),'unmapped position explained');
    check(mappedIssues.some(row=>row.includes('Coluna 1')&&row.includes('mais de uma atribuição')),'duplicate source explained');
    check(mappedIssues.some(row=>row.includes('Atribuições 1, 3')&&row.includes('Destino fictício')),'duplicate target explained');
    check(value('map-status').includes('Revisão humana obrigatória'),'mapping conflict requires human review');
    document.querySelector('[data-map="0"]').click();
    check(value('map-conflicts')==='0','mapping issues cleared on reset');
    check(document.querySelectorAll('#map-rows tr').length===0,'no stale mapping diagnostics');

    const suspicious=document.querySelector('[data-sample="1"]');
    suspicious.focus();
    suspicious.click();
    check(document.activeElement===suspicious,'button retains focus');
    check(pressed().length===1&&pressed()[0]===suspicious,'suspicious sample selected alone');
    check(value('count-rows')==='4','suspicious rows');
    check(value('count-data')==='3','suspicious data rows');
    check(value('count-columns')==='4','suspicious column count');
    check(value('count-alerts')==='5','suspicious structural + cell alerts');
    check(issues().length===5,'five rendered issue rows');
    check(issues().some(row=>row.includes('Linha 3')&&row.includes('Quantidade diferente')),'ragged row');
    check(issues().some(row=>row.includes('Coluna 4')&&row.includes('Nome vazio')),'empty header');
    check(issues().some(row=>row.includes('Colunas 1, 3')&&row.includes('Nome repetido')),'duplicate header');
    check(issues().some(row=>row.includes('Linha 2, coluna 3')&&row.includes('Prefixo semelhante')),'formula flag');
    check(issues().some(row=>row.includes('Linha 3, coluna 2')&&row.includes('Apóstrofo')),'ambiguous quote');
    check(document.getElementById('clean-note').hidden,'clean note hidden for warnings');
    check(value('preview-status').includes('Nenhuma operação de importação'),'never claims a real import');
    check(value('row-flagged')==='2','two problematic data rows');
    check(value('row-unflagged')==='1','one row without detected findings');
    const rowReview=Array.from(document.querySelectorAll('#row-review-rows tr')).map(row=>row.textContent);
    check(rowReview.length===3,'three original data rows in diagnostics');
    check(rowReview.some(row=>row.includes('Linha 2')&&row.includes('Coluna 3')&&row.includes('Prefixo semelhante')),'formula row diagnoses only a position');
    check(rowReview.some(row=>row.includes('Linha 3')&&row.includes('Quantidade de colunas divergente')&&row.includes('Coluna 2')),'ragged row and formula share one review');
    check(rowReview.some(row=>row.includes('Linha 4')&&row.includes('revisão obrigatória')),'unflagged row still requires review');
    check(value('row-review-status').includes('nenhuma importação ou escrita permitida'),'row preview never approves import');
    check(value('map-count')==='3'&&value('map-conflicts')==='0','structure choice does not modify independent mapping example');

    document.querySelector('[data-sample="0"]').click();
    check(value('count-alerts')==='0','clean reset count');
    check(issues().length===0,'old findings cleared');
    check(value('row-flagged')==='0','row warnings clear on clean sample reset');
    check(value('row-unflagged')==='2','clean row total returns');
    check(document.querySelectorAll('#row-review-rows tr').length===2,'no old row findings retained');
    check(!document.getElementById('clean-note').hidden,'clean caveat restored');

    result.setAttribute('data-imagine-browser-smoke','PASS');
    result.textContent='F3-22 real Chromium CSV preview PASS';
  }catch(error){
    result.setAttribute('data-imagine-browser-smoke','FAIL');
    result.textContent='F3-22 FAIL: '+error.message;
  }
})();
JS;

$desktopFile = sys_get_temp_dir().'/imagine-f3-22-'.bin2hex(random_bytes(12)).'.html';
$mobileFile = sys_get_temp_dir().'/imagine-f3-22-320-'.bin2hex(random_bytes(12)).'.html';
if (file_put_contents($desktopFile, str_replace('</body>', '<script>'.$assertions.'</script></body>', $html)) === false) {
    fwrite(STDERR, "F3-22: failed creating synthetic browser fixture.\n");
    exit(1);
}

try {
    $desktop = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=900,820', '--virtual-time-budget=2300'],
        'file://'.$desktopFile,
        'data-imagine-browser-smoke',
    );
    if (!$desktop['passed']) {
        fwrite(STDERR, "F3-22 desktop FAIL: {$desktop['reason']}\n");
        exit(1);
    }

    $serialized = json_encode(
        $html,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    );
    $driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>F3-22 320 px QA</title></head>
<body><output data-imagine-320="RUNNING"></output>
<script>
(function(){
  const marker=document.querySelector('[data-imagine-320]');
  const frame=document.createElement('iframe');
  frame.title='Prévia CSV fictícia em 320 pixels';
  frame.style.cssText='display:block;width:320px;height:740px;border:0';
  frame.width=320;
  frame.height=740;
  let finished=false;
  function check(value,why){if(!value)throw new Error(why);}
  function finish(state,why){
    if(finished)return;
    finished=true;
    marker.setAttribute('data-imagine-320',state);
    marker.textContent='F3-22 '+state+': '+why;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'local same-origin fixture');
      check(win.innerWidth===320,'actual 320 CSS pixels');
      check(doc.documentElement.scrollWidth<=320,'no page horizontal overflow');
      check(doc.getElementById('count-alerts').textContent==='0','initial mobile zero');
      check(doc.getElementById('row-flagged').textContent==='0','initial mobile row warning count');
      check(doc.querySelectorAll('#row-review-rows tr').length===2,'initial mobile clean rows');
      check(doc.getElementById('map-conflicts').textContent==='0','initial mobile mapping clean');
      const mapChoice=doc.querySelector('[data-map="1"]');
      mapChoice.focus();
      mapChoice.click();
      check(doc.activeElement===mapChoice,'mobile mapping focus preserved');
      check(doc.getElementById('map-conflicts').textContent==='2','mobile two mapping conflict categories');
      check(doc.querySelectorAll('#map-rows tr').length===3,'mobile mapping rows');
      check(doc.documentElement.scrollWidth<=320,'mapping does not overflow mobile viewport');
      doc.querySelector('[data-map="0"]').click();
      check(doc.querySelectorAll('#map-rows tr').length===0,'mobile mapping issues reset');
      const sample=doc.querySelector('[data-sample="1"]');
      sample.focus();
      sample.click();
      check(doc.activeElement===sample,'mobile keyboard focus');
      check(doc.getElementById('count-alerts').textContent==='5','mobile issue total');
      check(doc.getElementById('row-flagged').textContent==='2','mobile two rows flagged');
      check(doc.getElementById('row-unflagged').textContent==='1','mobile unflagged row still counted');
      check(doc.querySelectorAll('#row-review-rows tr').length===3,'mobile all data rows shown');
      check(doc.querySelectorAll('#findings-rows tr').length===5,'mobile issue rows');
      check(doc.documentElement.scrollWidth<=320,'no overflow after issues');
      doc.querySelector('[data-sample="0"]').click();
      check(doc.querySelectorAll('#findings-rows tr').length===0,'mobile stale rows cleared');
      check(doc.getElementById('row-flagged').textContent==='0','mobile stale per-row warnings cleared');
      check(doc.querySelectorAll('#row-review-rows tr').length===2,'mobile row preview reset');
      check(!doc.querySelector('form,input,textarea'),'no mobile capture');
      finish('PASS','real Chromium 320 px synthetic CSV preview');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.srcdoc=__HTML__;
  document.body.appendChild(frame);
  setTimeout(function(){if(!finished)finish('FAIL','iframe load timed out');},2500);
})();
</script></body></html>
HTML;
    $driver = str_replace('__HTML__', $serialized, $driver);
    if (file_put_contents($mobileFile, $driver) === false) {
        fwrite(STDERR, "F3-22: failed creating 320px driver.\n");
        exit(1);
    }

    $mobile = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=640,850', '--virtual-time-budget=3400'],
        'file://'.$mobileFile,
        'data-imagine-320',
    );
    if (!$mobile['passed']) {
        fwrite(STDERR, "F3-22 320px FAIL: {$mobile['reason']}\n");
        exit(1);
    }

    echo "F3-22 PASS: Chromium desktop + 320 CSS px, synthetic offline preview only.\n";
} finally {
    @unlink($desktopFile);
    @unlink($mobileFile);
}
