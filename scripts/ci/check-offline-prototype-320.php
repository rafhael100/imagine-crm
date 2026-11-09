<?php

declare(strict_types=1);

// UX-F3-13: run the synthetic CRM demo in an actual 320-CSS-pixel iframe
// viewport inside headless Chromium. Parent driver loads only local srcdoc.
// No web server, network requests, credentials, real data or deployment.

require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

$html = file_get_contents(dirname(__DIR__, 2).'/prototypes/s01-prot-01/index.html');
if (!is_string($html)
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || strlen($html) > 100000) {
    fwrite(STDERR, "UX-F3-13: unexpected local demo or missing offline CSP.\n");
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
    fwrite(STDERR, "UX-F3-13: no Chrome/Chromium; browser QA NOT performed.\n");
    exit(1);
}

// JSON_HEX_TAG ensures the embedded prototype's </script> cannot terminate
// the parent driver's script. The child's CSP still applies inside srcdoc.
$serialized = json_encode(
    $html,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Imagine UX 320 browser test</title></head>
<body>
<output id="smoke320" data-imagine-320="RUNNING"></output>
<script>
(function(){
  const marker=document.getElementById('smoke320');
  const source=__PROTO_STRING__;
  const frame=document.createElement('iframe');
  frame.setAttribute('title','Protótipo Imagine em 320 pixels');
  frame.setAttribute('width','320');
  frame.setAttribute('height','740');
  frame.style.cssText='border:0;display:block;width:320px;height:740px';
  function check(ok,message){if(!ok)throw new Error(message);}
  let done=false;
  function fail(message){
    if(done)return;done=true;
    marker.setAttribute('data-imagine-320','FAIL');
    marker.textContent='UX-F3-13 FAIL: '+message;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument;
      const win=frame.contentWindow;
      check(doc&&win,'same-origin srcdoc frame');
      check(win.innerWidth===320,'actual CSS viewport '+win.innerWidth+' not 320');
      check(doc.querySelectorAll('[data-screen]').length===9,'all nine screens exist');
      const pageWidth=doc.documentElement.scrollWidth;
      if(pageWidth>win.innerWidth){
        const suspects=Array.from(doc.querySelectorAll('body *')).map(el=>{
          const rect=el.getBoundingClientRect();
          return {tag:el.tagName.toLowerCase(),className:typeof el.className==='string'?el.className:'',
            right:Math.round(rect.right)};
        }).filter(item=>item.right>win.innerWidth+1).slice(0,4);
        throw new Error('page width '+pageWidth+'/320, suspects '+JSON.stringify(suspects));
      }
      const nav=doc.getElementById('demo-navigation');
      check(nav!==null,'navigation exists');
      check(win.getComputedStyle(nav).overflowX==='auto','navigation scrolls inside sidebar');
      check(win.getComputedStyle(doc.querySelector('.mobile-nav-hint')).display!=='none','mobile instructions visible');

      const buttons=Array.from(doc.querySelectorAll('#demo-navigation button[data-page]'));
      check(buttons.length===9,'all navigation buttons exist');
      const leads=buttons.find(node=>node.getAttribute('data-page')==='leads');
      const pipeline=buttons.find(node=>node.getAttribute('data-page')==='pipeline');
      const monitor=buttons.find(node=>node.getAttribute('data-page')==='monitor');
      monitor.click();
      check(doc.getElementById('page-title').textContent==='Monitoramento de leads','page switch at 320');
      check(doc.activeElement===doc.getElementById('page-title'),'heading focus');
      check(doc.querySelectorAll('[data-screen]:not([hidden])').length===1,'one active screen');
      leads.focus();
      leads.dispatchEvent(new win.KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true,cancelable:true}));
      check(doc.activeElement===pipeline,'arrow focus at 320');
      check(doc.getElementById('page-title').textContent==='Monitoramento de leads','no implicit activation');
      // Stage filtering is demo-only, but its DOM behavior must work in 320px.
      leads.click();
      const stageButtons=Array.from(doc.querySelectorAll('#screen-leads [data-lead-stage]'));
      const leadRows=Array.from(doc.querySelectorAll('#screen-leads [data-lead-fixture-stage]'));
      const count=()=>leadRows.filter(node=>!node.hidden).length;
      check(stageButtons.length===4&&leadRows.length===4,'four stage controls and demo records');
      stageButtons.find(node=>node.dataset.leadStage==='entrada').click();
      check(count()===2,'two synthetic entries in 320px');
      check(doc.getElementById('lead-filter-summary').textContent.includes('2 de 4'),'filter status announces count');
      stageButtons.find(node=>node.dataset.leadStage==='all').click();
      check(count()===4,'all synthetic entries restored');
      check(doc.documentElement.scrollWidth<=320,'no horizontal page overflow on lead screen');

      done=true;
      marker.setAttribute('data-imagine-320','PASS');
      marker.textContent='UX-F3-13 PASS: actual 320 CSS pixels, no page overflow, offline keyboard and nav';
    }catch(error){fail(error.message);}
  });
  frame.srcdoc=source;
  document.body.appendChild(frame);
  setTimeout(function(){if(!done)fail('iframe load timed out');},2200);
})();
</script>
</body></html>
HTML;
$driver = str_replace('__PROTO_STRING__', $serialized, $driver);
$temp = sys_get_temp_dir().'/imagine-crm-320-'.bin2hex(random_bytes(10)).'.html';
if (file_put_contents($temp, $driver) === false) {
    fwrite(STDERR, "UX-F3-13: could not create temporary 320px test fixture.\n");
    exit(1);
}

try {
    $result = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=600,800', '--virtual-time-budget=3000'],
        'file://'.$temp,
        'data-imagine-320',
    );
    if (!$result['passed']) {
        fwrite(STDERR, 'UX-F3-13 browser failure: '.$result['reason']
            .' (attempts '.$result['attempts'].')'.PHP_EOL);
        exit(1);
    }

    echo "UX-F3-13 PASS: 320px CSS viewport, keyboard interactions and no page overflow in real Chromium.\n";
} finally {
    @unlink($temp);
}
