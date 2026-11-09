<?php

declare(strict_types=1);

// UX-F3-12: real headless-browser smoke test for a STATIC, synthetic,
// offline HTML prototype. No web server, client data, requests, or persistence.
// Fail CI if Google Chrome/Chromium is unavailable; do not silently fake QA.

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

$source = dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html';
$html = file_get_contents($source);
if (!is_string($html)
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "UX-F3-12: unexpected prototype or offline CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $candidate = $finder->find($name);
    if ($candidate !== null) {
        $browser = $candidate;
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "UX-F3-12: Chromium/Chrome not available; browser verification NOT performed.\n");
    exit(1);
}

// Inject a harness into a TEMPORARY COPY. Never alter or publish the demo.
// Original app navigation runs first, then the browser checks the DOM/events.
$testScript = <<<'JS'
(function(){
  const output=document.createElement('output');
  output.id='imagine-browser-smoke';
  output.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(output);
  function check(value,message){if(!value) throw new Error(message);}
  try{
    const nav=Array.from(document.querySelectorAll('#demo-navigation button[data-page]'));
    check(nav.length===9,'nine navigation buttons');
    check(document.querySelectorAll('[data-screen]').length===9,'nine demo screens');
    check(document.getElementById('page-title').textContent==='Boas-vindas','initial screen');

    const monitor=nav.find(x=>x.dataset.page==='monitor');
    monitor.click();
    check(document.getElementById('page-title').textContent==='Monitoramento de leads','click switches screen');
    check(document.activeElement===document.getElementById('page-title'),'click focuses heading');
    check(!document.getElementById('screen-monitor').hidden,'active screen visible');
    check(document.querySelectorAll('[data-screen]:not([hidden])').length===1,'one screen visible');
    check(document.querySelectorAll('#demo-navigation [aria-current="page"]').length===1,'one selected item');

    const leads=nav.find(x=>x.dataset.page==='leads');
    const pipeline=nav.find(x=>x.dataset.page==='pipeline');
    leads.focus();
    leads.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true,cancelable:true}));
    check(document.activeElement===pipeline,'arrow moves focus');
    check(document.getElementById('page-title').textContent==='Monitoramento de leads','arrow does not activate');

    pipeline.dispatchEvent(new KeyboardEvent('keydown',{key:'End',bubbles:true,cancelable:true}));
    check(document.activeElement===nav[8],'End focuses last');
    nav[8].dispatchEvent(new KeyboardEvent('keydown',{key:'Home',bubbles:true,cancelable:true}));
    check(document.activeElement===nav[0],'Home focuses first');

    // Exercise the fixture-only stage filter in the real narrow browser.
    leads.click();
    check(document.getElementById('page-title').textContent==='Leads fictícios','open lead screen');
    const filterButtons=Array.from(document.querySelectorAll('[data-lead-stage]'));
    const rows=Array.from(document.querySelectorAll('#screen-leads [data-lead-fixture-stage]'));
    const visible=()=>rows.filter(x=>!x.hidden).length;
    check(filterButtons.length===4&&rows.length===4,'stage filter fixture cardinality');
    check(visible()===4,'default stage shows all');
    filterButtons.find(x=>x.dataset.leadStage==='entrada').click();
    check(visible()===2,'entry stage count');
    check(document.getElementById('lead-filter-summary').textContent.includes('2 de 4'),'live count updates');
    filterButtons.find(x=>x.dataset.leadStage==='em-contato').click();
    check(visible()===1,'contact stage count');
    filterButtons.find(x=>x.dataset.leadStage==='qualificacao').click();
    check(visible()===1,'qualification stage count');
    filterButtons.find(x=>x.dataset.leadStage==='all').click();
    check(visible()===4,'all stage restores every fixture');
    check(document.querySelectorAll('[data-lead-stage][aria-pressed="true"]').length===1,'one pressed filter');

    const skip=document.querySelector('.skip-link');
    check(skip&&skip.getAttribute('href')==='#conteudo','skip link exists');
    skip.focus();
    check(document.activeElement===skip,'skip link is focusable');
    skip.click();
    check(location.hash==='#conteudo','skip navigates to main anchor');

    const menu=document.getElementById('demo-navigation');
    check(window.innerWidth<=900,'narrow viewport used');
    check(getComputedStyle(menu).overflowX==='auto','mobile navigation is scrollable');
    check(getComputedStyle(document.querySelector('.mobile-nav-hint')).display!=='none','mobile hint visible');
    const visibleWidth=window.innerWidth, docWidth=document.documentElement.scrollWidth;
    if(docWidth>visibleWidth){
      const culprits=Array.from(document.querySelectorAll('body *')).map(el=>{
        const r=el.getBoundingClientRect();
        return {name:el.tagName.toLowerCase()+(el.className && typeof el.className==='string'?'.'+el.className.split(' ').join('.'):''),
          right:Math.round(r.right),width:Math.round(r.width)};
      }).filter(x=>x.right>visibleWidth+1).slice(0,6);
      throw new Error('horizontal overflow '+docWidth+'/'+visibleWidth+' culprits '+JSON.stringify(culprits));
    }

    check(!document.querySelector('form,input,textarea'),'no data-entry form');
    output.setAttribute('data-imagine-browser-smoke','PASS');
    output.textContent='UX-F3-12 PASS: offline keyboard/mobile interaction';
  }catch(error){
    output.setAttribute('data-imagine-browser-smoke','FAIL');
    output.textContent='UX-F3-12 FAIL: '+error.message;
  }
})();
JS;

$temp = sys_get_temp_dir().'/imagine-crm-ux-'.bin2hex(random_bytes(12)).'.html';
if (file_put_contents($temp, str_replace('</body>', '<script>'.$testScript.'</script></body>', $html)) === false) {
    fwrite(STDERR, "UX-F3-12: temporary fixture creation failed.\n");
    exit(1);
}

try {
    $url = 'file://'.$temp;
    $process = new Process([
        $browser, '--headless', '--disable-gpu', '--disable-extensions',
        '--disable-background-networking', '--no-first-run',
        '--no-default-browser-check', '--window-size=520,780',
        '--virtual-time-budget=1500', '--dump-dom', $url,
    ]);
    $process->setTimeout(30);
    $process->run();

    $output = $process->getOutput();
    // Match the OUTPUT ELEMENT, not an incidental string in script source.
    $match = [];
    $found = preg_match(
        '/<output\b[^>]*data-imagine-browser-smoke="(PASS|FAIL)"[^>]*>(.*?)<\/output>/s',
        $output,
        $match,
    ) === 1;

    if (!$process->isSuccessful() || !$found || $match[1] !== 'PASS') {
        // Synthetic demo only. No user fields or secrets in this HTML.
        $reason = $found
            ? html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : 'no output element from executed test script';
        $reason = mb_substr($reason, 0, 240);
        $stderr = mb_substr(trim($process->getErrorOutput()), -420);
        fwrite(STDERR, 'UX-F3-12 browser failure: '.$reason
            .' (exit '.var_export($process->getExitCode(), true)
            .'; DOM bytes '.strlen($output)
            .'; Chrome stderr tail: '.$stderr.')'.PHP_EOL);
        exit(1);
    }

    echo "UX-F3-12 PASS: real headless browser, narrow viewport, nav/keyboard/skip link; offline prototype only.\n";
} finally {
    @unlink($temp);
}
