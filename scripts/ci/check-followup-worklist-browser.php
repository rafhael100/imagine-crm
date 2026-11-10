<?php

declare(strict_types=1);

// F4-28: actual Chrome/Chromium smoke for the isolated, invented worklist.
// No app backend, web server, external assets or customer information.

require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f4-28-followup-worklist/index.html';
$html = file_get_contents($source);
if (!is_string($html)
    || strlen($html) > 28000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "UX-F4-28: invalid offline fixture or CSP.\n");
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
    fwrite(STDERR, "UX-F4-28: Chromium is required, no false test pass.\n");
    exit(1);
}

$assertions = <<<'JS'
(function(){
  const output=document.createElement('output');
  output.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(output);
  function check(yes,reason){if(!yes)throw new Error(reason);}
  function rows(){return Array.from(document.querySelectorAll('#worklist-rows tr')).map(tr=>tr.querySelector('td')?.textContent);}
  function click(selector){const button=document.querySelector(selector);check(button!==null,'missing '+selector);button.click();return button;}
  function page(){return document.getElementById('page-indicator').textContent.trim();}
  function summary(){return document.getElementById('result-summary').textContent.trim();}
  function onlyPressed(selector,value){const pressed=Array.from(document.querySelectorAll(selector+'[aria-pressed="true"]'));check(pressed.length===1 && pressed[0].dataset[selector==='[data-filter]'?'filter':'sample']===value,'pressed '+selector+' '+value);}
  try{
    check(document.querySelector('main')!==null,'semantic main landmark');
    check(document.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS'),'fiction warning');
    check(document.querySelector('a[href="../f4-15-followup-dashboard/index.html"]')!==null,'local dashboard link');
    check(!document.querySelector('form,input,textarea,iframe'),'no capture surface');
    check(rows().join('|')==='fixture-overdue|fixture-now','initial chronological page 1');
    check(page()==='Página 1 de 3','three pages');
    check(summary().includes('Total da amostra: 5'),'global sample total');
    check(document.getElementById('previous').disabled,'first page prev disabled');
    check(!document.getElementById('next').disabled,'first page next enabled');

    click('#next');
    check(rows().join('|')==='fixture-a|fixture-b','second page tie by ID');
    check(page()==='Página 2 de 3','page 2 label');
    click('#next');
    check(rows().join('|')==='fixture-late','last page one row');
    check(document.getElementById('next').disabled,'last page next disabled');
    click('#previous');
    check(page()==='Página 2 de 3','previous restores page 2');

    click('[data-filter="due_24h"]');
    check(rows().join('|')==='fixture-now|fixture-a','due24 page one');
    check(page()==='Página 1 de 2','filter resets page and calculates pages');
    onlyPressed('[data-filter]','due_24h');
    click('#next');
    check(rows().join('|')==='fixture-b','due24 page two');
    click('[data-filter="overdue"]');
    check(rows().join('|')==='fixture-overdue','overdue filter');
    check(page()==='Página 1 de 1','filtered one page');
    click('[data-filter="due_later"]');
    check(rows().join('|')==='fixture-late','due later filter');
    const emptyChoice=click('[data-sample="empty"]');
    check(document.getElementById('empty-note').hidden===false,'explicit empty note');
    check(rows().length===0,'empty sample has no rows');
    check(page()==='Página 1 de 1','empty page label');
    check(document.getElementById('previous').disabled&&document.getElementById('next').disabled,'empty page disabled navigation');
    onlyPressed('[data-filter]','all');
    onlyPressed('[data-sample]','empty');
    emptyChoice.focus();
    check(document.activeElement===emptyChoice,'focus remains on empty sample selection');
    click('[data-sample="a"]');
    check(document.getElementById('empty-note').hidden,'empty warning resets');
    check(rows().join('|')==='fixture-overdue|fixture-now','sample restore');
    onlyPressed('[data-sample]','a');

    output.setAttribute('data-imagine-browser-smoke','PASS');
    output.textContent='F4-28 Chromium filtering pagination and empty-state PASS';
  }catch(error){
    output.setAttribute('data-imagine-browser-smoke','FAIL');
    output.textContent='F4-28 FAIL: '+error.message;
  }
})();
JS;

$temp = sys_get_temp_dir().'/imagine-f4-28-'.bin2hex(random_bytes(12)).'.html';
$driverTemp = sys_get_temp_dir().'/imagine-f4-28-320-'.bin2hex(random_bytes(12)).'.html';
if (file_put_contents($temp, str_replace('</body>', '<script>'.$assertions.'</script></body>', $html)) === false) {
    fwrite(STDERR, "UX-F4-28: cannot write desktop fixture.\n");
    exit(1);
}

try {
    $desktop = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=900,850', '--virtual-time-budget=2500'],
        'file://'.$temp,
        'data-imagine-browser-smoke',
    );
    if (!$desktop['passed']) {
        fwrite(STDERR, "UX-F4-28 desktop FAIL: {$desktop['reason']}\n");
        exit(1);
    }

    $sourceJson = json_encode(
        $html,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    );
    $driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>UX F4-28 320px</title></head>
<body><output data-imagine-320="RUNNING"></output>
<script>
(function(){
  const marker=document.querySelector('[data-imagine-320]');
  const source=__HTML__;
  const frame=document.createElement('iframe');
  frame.title='Lista fictícia em 320 pixels';
  frame.width=320;
  frame.height=700;
  frame.style.cssText='display:block;width:320px;height:700px;border:0';
  let finished=false;
  function check(condition,reason){if(!condition)throw new Error(reason);}
  function finish(status,reason){
    if(finished)return;
    finished=true;
    marker.setAttribute('data-imagine-320',status);
    marker.textContent='UX-F4-28 '+status+': '+reason;
  }
  frame.addEventListener('load',function(){
    if(finished)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'same origin local fixture');
      check(win.innerWidth===320,'real 320 CSS px viewport');
      check(doc.documentElement.scrollWidth<=320,'no horizontal overflow');
      check(doc.querySelectorAll('#worklist-rows tr').length===2,'initial two mobile rows');
      const filter=doc.querySelector('[data-filter="due_24h"]');
      filter.focus();
      filter.click();
      check(doc.activeElement===filter,'filter keeps keyboard focus');
      check(doc.getElementById('page-indicator').textContent==='Página 1 de 2','mobile filter paging');
      doc.getElementById('next').click();
      check(doc.querySelectorAll('#worklist-rows tr').length===1,'mobile last row');
      doc.querySelector('[data-sample="empty"]').click();
      check(doc.getElementById('empty-note').hidden===false,'mobile empty warning');
      check(doc.querySelectorAll('#worklist-rows tr').length===0,'mobile empty rows');
      check(doc.documentElement.scrollWidth<=320,'no page overflow after interactions');
      check(!doc.querySelector('form,input,textarea'),'no capture UI');
      finish('PASS','actual 320px worklist filters, pagination and empty state');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.srcdoc=source;
  document.body.appendChild(frame);
  setTimeout(function(){if(!finished)finish('FAIL','iframe timed out');},2500);
})();
</script></body></html>
HTML;
    $driver = str_replace('__HTML__', $sourceJson, $driver);
    if (file_put_contents($driverTemp, $driver) === false) {
        fwrite(STDERR, "UX-F4-28: cannot write 320px fixture.\n");
        exit(1);
    }

    $mobile = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=620,820', '--virtual-time-budget=3500'],
        'file://'.$driverTemp,
        'data-imagine-320',
    );
    if (!$mobile['passed']) {
        fwrite(STDERR, "UX-F4-28 320px FAIL: {$mobile['reason']}\n");
        exit(1);
    }
    echo "UX-F4-28 PASS: real Chromium desktop and 320px, synthetic offline only.\n";
} finally {
    @unlink($temp);
    @unlink($driverTemp);
}
