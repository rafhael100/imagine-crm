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
  function onlyPressed(selector,value){const pressed=Array.from(document.querySelectorAll(selector+'[aria-pressed="true"]'));const key=selector==='[data-filter]'?'filter':selector==='[data-page-size]'?'pageSize':'sample';check(pressed.length===1 && pressed[0].dataset[key]===value,'pressed '+selector+' '+value);}
  try{
    check(document.querySelector('main')!==null,'semantic main landmark');
    check(document.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS'),'fiction warning');
    check(document.querySelector('a[href="../f4-15-followup-dashboard/index.html"]')!==null,'local dashboard link');
    check(!document.querySelector('form,input,textarea,iframe'),'no capture surface');
    check(rows().join('|')==='fixture-overdue|fixture-now','initial chronological page 1');
    check(page()==='Página 1 de 3','three pages');
    check(summary().includes('Total da amostra: 5'),'global sample total');
    check(document.getElementById('count-overdue').textContent==='1','overdue sample count');
    check(document.getElementById('count-due24').textContent==='3','due24 sample count');
    check(document.getElementById('count-later').textContent==='1','future sample count');
    check(document.getElementById('count-all').textContent==='5','sample total card');
    check(document.getElementById('previous').disabled,'first page prev disabled');
    check(!document.getElementById('next').disabled,'first page next enabled');
    const detailPanel=document.getElementById('record-detail');
    check(detailPanel.hidden,'detail is hidden at start');
    const firstAction=document.querySelector('#worklist-rows tr button');
    check(firstAction && firstAction.getAttribute('aria-expanded')==='false','first detail action collapsed');
    firstAction.click();
    check(!detailPanel.hidden,'details open on click');
    check(document.activeElement===detailPanel,'detail receives focus');
    check(firstAction.getAttribute('aria-expanded')==='true','detail action expanded');
    check(document.getElementById('detail-id').textContent==='fixture-overdue','selected synthetic id');
    check(document.getElementById('detail-due').textContent==='2026-11-10T09:00:00Z','selected synthetic due date');
    check(document.getElementById('detail-bucket').textContent==='Vencidos','selected synthetic bucket');
    click('#close-detail');
    check(detailPanel.hidden,'details close on command');
    check(document.activeElement===firstAction,'focus returns to detail trigger');
    check(firstAction.getAttribute('aria-expanded')==='false','detail trigger collapses');
    check(document.getElementById('detail-id').textContent==='','closed detail is cleared');
    firstAction.click();
    check(!detailPanel.hidden,'details can reopen');

    click('#next');
    check(detailPanel.hidden,'pagination hides stale record details');
    check(document.getElementById('detail-id').textContent==='','pagination clears previous identity');
    check(rows().join('|')==='fixture-a|fixture-b','second page tie by ID');
    check(page()==='Página 2 de 3','page 2 label');
    click('#next');
    check(rows().join('|')==='fixture-late','last page one row');
    check(document.getElementById('next').disabled,'last page next disabled');
    click('#previous');
    check(page()==='Página 2 de 3','previous restores page 2');

    const secondAction=document.querySelector('#worklist-rows tr button');
    secondAction.click();
    check(!detailPanel.hidden,'details open on another page');
    click('[data-filter="due_24h"]');
    check(detailPanel.hidden,'filter hides details from previous page');
    check(document.getElementById('detail-id').textContent==='','filter clears stale data');
    check(rows().join('|')==='fixture-now|fixture-a','due24 page one');
    check(page()==='Página 1 de 2','filter resets page and calculates pages');
    onlyPressed('[data-filter]','due_24h');
    click('#next');
    check(rows().join('|')==='fixture-b','due24 page two');
    click('[data-filter="overdue"]');
    check(rows().join('|')==='fixture-overdue','overdue filter');
    check(page()==='Página 1 de 1','filtered one page');
    check(document.getElementById('count-all').textContent==='5','filter does not change global total');
    click('[data-filter="due_later"]');
    check(rows().join('|')==='fixture-late','due later filter');
    const futureAction=document.querySelector('#worklist-rows tr button');
    futureAction.click();
    check(!detailPanel.hidden,'filtered item detail opens');
    const emptyChoice=click('[data-sample="empty"]');
    check(detailPanel.hidden,'empty sample closes previous detail');
    check(document.getElementById('detail-id').textContent==='','empty sample clears old item');
    check(document.getElementById('empty-note').hidden===false,'explicit empty note');
    check(rows().length===0,'empty sample has no rows');
    check(['count-overdue','count-due24','count-later','count-all'].every(id=>document.getElementById(id).textContent==='0'),'empty sample count cards reset');
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
    check(document.getElementById('count-all').textContent==='5','sample restore total');
    click('[data-page-size="5"]');
    onlyPressed('[data-page-size]','5');
    check(rows().length===5,'five rows in expanded page');
    check(page()==='Página 1 de 1','five rows fit one page');
    check(document.getElementById('next').disabled,'expanded page next disabled');
    click('[data-filter="due_24h"]');
    check(rows().length===3,'three due24 rows with page size five');
    check(document.getElementById('count-due24').textContent==='3','due24 count stable after filtering');
    click('[data-page-size="2"]');
    onlyPressed('[data-page-size]','2');
    check(rows().length===2,'page size two restored');
    check(page()==='Página 1 de 2','page size reset to first page');

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
      const panel=doc.getElementById('record-detail');
      const mobileAction=doc.querySelector('#worklist-rows tr button');
      check(panel.hidden,'mobile detail initially hidden');
      mobileAction.click();
      check(!panel.hidden,'mobile detail shown');
      check(doc.activeElement===panel,'mobile focus transferred to region');
      check(doc.documentElement.scrollWidth<=320,'detail no page overflow');
      panel.dispatchEvent(new win.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
      check(panel.hidden,'Escape closes mobile detail');
      check(doc.activeElement===mobileAction,'Escape restores mobile focus');
      check(doc.getElementById('count-all').textContent==='5','mobile global count');
      doc.querySelector('[data-page-size="5"]').click();
      check(doc.querySelectorAll('#worklist-rows tr').length===5,'mobile expanded page');
      check(doc.getElementById('page-indicator').textContent==='Página 1 de 1','mobile expanded page indicator');
      doc.querySelector('[data-page-size="2"]').click();
      check(doc.querySelectorAll('#worklist-rows tr').length===2,'mobile reset to two records');
      const filter=doc.querySelector('[data-filter="due_24h"]');
      filter.focus();
      filter.click();
      check(panel.hidden,'mobile filter does not retain details');
      check(doc.activeElement===filter,'filter keeps keyboard focus');
      check(doc.getElementById('page-indicator').textContent==='Página 1 de 2','mobile filter paging');
      doc.getElementById('next').click();
      check(doc.querySelectorAll('#worklist-rows tr').length===1,'mobile last row');
      doc.querySelector('[data-sample="empty"]').click();
      check(doc.getElementById('empty-note').hidden===false,'mobile empty warning');
      check(doc.getElementById('count-all').textContent==='0','mobile zero total card');
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
