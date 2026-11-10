<?php

declare(strict_types=1);

// UX-F4-22: actual Chromium smoke tests for the independent follow-up
// dashboard. Both runs use only local HTML and synthetic fixture numbers.
// No web server, external requests, client data or persisted browser profile.

require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$sourcePath = dirname(__DIR__, 2).'/prototypes/f4-15-followup-dashboard/index.html';
$html = file_get_contents($sourcePath);
if (!is_string($html)
    || strlen($html) > 30000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "UX-F4-22: invalid synthetic dashboard or offline CSP.\n");
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
    fwrite(STDERR, "UX-F4-22: actual Chrome/Chromium unavailable; refusing false browser pass.\n");
    exit(1);
}

$assertions = <<<'JS'
(function(){
  const output=document.createElement('output');
  output.id='followup-browser-result';
  output.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(output);
  function check(condition,reason){if(!condition)throw new Error(reason);}
  function count(id){return document.getElementById(id).textContent.trim();}
  function meter(bucket){return document.getElementById('meter-'+bucket);}
  function selected(){return Array.from(document.querySelectorAll('[data-period][aria-pressed="true"]'));}
  function verify(values,period){
    check(count('overdue')===String(values[0]),period+' overdue');
    check(count('due24')===String(values[1]),period+' due24');
    check(count('later')===String(values[2]),period+' later');
    check(count('total')===String(values[3]),period+' total');
    for(const [i,name] of ['overdue','due24','later'].entries()){
      check(meter(name).value===values[i],period+' meter '+name);
      check(meter(name).max===Math.max(values[3],1),period+' meter maximum '+name);
      check(count('text-'+name)===values[i]+' de '+values[3],period+' meter text '+name);
    }
  }
  try{
    check(document.querySelector('main')!==null,'dashboard main landmark');
    check(document.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS'),'fiction warning');
    check(!document.querySelector('form,input,textarea,iframe'),'no data capture surface');
    const buttons=Array.from(document.querySelectorAll('[data-period]'));
    check(buttons.length===4,'four period choices');
    check(selected().length===1&&selected()[0]===buttons[0],'one initial selected period');
    verify([2,3,4,9],'A');
    check(count('delta').includes('sem comparação anterior'),'first period has no invented delta');

    buttons[1].click();
    verify([3,2,5,10],'B');
    check(count('delta').includes('Vencidos: +1'),'B positive overdue change');
    check(count('delta').includes('Até 24h: -1'),'B negative due24 change');
    check(count('delta').includes('Total: +1'),'B total change');
    check(selected().length===1&&selected()[0]===buttons[1],'B alone selected');

    buttons[2].focus();
    buttons[2].click();
    verify([1,4,4,9],'C');
    check(count('delta').includes('Vencidos: -2'),'C overdue change');
    check(count('delta').includes('Até 24h: +2'),'C due24 change');
    check(count('delta').includes('Total: -1'),'C total change');
    check(document.activeElement===buttons[2],'keyboard focus retained');
    check(selected().length===1&&selected()[0]===buttons[2],'C alone selected');

    buttons[3].click();
    verify([0,0,0,0],'D empty');
    check(count('delta').includes('Total: -9'),'D signed change after C');
    check(!document.getElementById('empty-period-note').hidden,'empty-state explanation visible');
    check(selected().length===1&&selected()[0]===buttons[3],'D alone selected');

    buttons[0].click();
    verify([2,3,4,9],'A restored');
    check(document.getElementById('empty-period-note').hidden,'empty-state explanation hidden again');
    check(count('delta').includes('sem comparação anterior'),'reset initial period');
    check(selected().length===1&&selected()[0]===buttons[0],'A alone selected');
    const link=document.querySelector('a[href="../s01-prot-01/index.html"]');
    check(link!==null,'local return link');
    output.setAttribute('data-imagine-browser-smoke','PASS');
    output.textContent='UX-F4-22 real Chromium dashboard controls PASS';
  }catch(error){
    output.setAttribute('data-imagine-browser-smoke','FAIL');
    output.textContent='UX-F4-22 FAIL: '+error.message;
  }
})();
JS;

$temp = sys_get_temp_dir().'/imagine-f4-22-'.bin2hex(random_bytes(12)).'.html';
$driverTemp = sys_get_temp_dir().'/imagine-f4-22-320-'.bin2hex(random_bytes(12)).'.html';
if (file_put_contents($temp, str_replace('</body>', '<script>'.$assertions.'</script></body>', $html)) === false) {
    fwrite(STDERR, "UX-F4-22: could not create temporary browser fixture.\n");
    exit(1);
}

try {
    $desktop = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=880,850', '--virtual-time-budget=2200'],
        'file://'.$temp,
        'data-imagine-browser-smoke',
    );
    if (!$desktop['passed']) {
        fwrite(STDERR, "UX-F4-22 desktop browser FAIL: {$desktop['reason']}\n");
        exit(1);
    }

    // The narrow test uses an actual 320px same-origin srcdoc iframe.
    // Serialize with HEX_TAG to avoid accidental script termination.
    $serialized = json_encode(
        $html,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    );
    $driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Follow-up 320px QA</title></head>
<body>
<output data-imagine-320="RUNNING"></output>
<script>
(function(){
  const marker=document.querySelector('[data-imagine-320]');
  const source=__HTML__;
  const frame=document.createElement('iframe');
  frame.title='Painel fictício em 320 pixels';
  frame.width=320;
  frame.height=700;
  frame.style.cssText='display:block;width:320px;height:700px;border:0';
  let done=false;
  function check(value,reason){if(!value)throw new Error(reason);}
  function finish(status,reason){
    if(done)return;
    done=true;
    marker.setAttribute('data-imagine-320',status);
    marker.textContent='UX-F4-22 '+status+': '+reason;
  }
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument, win=frame.contentWindow;
      check(doc&&win,'same-origin fixture');
      check(win.innerWidth===320,'true 320 CSS px viewport');
      check(doc.documentElement.scrollWidth<=320,'no page horizontal overflow');
      const buttons=Array.from(doc.querySelectorAll('[data-period]'));
      check(buttons.length===4,'four period choices at mobile width');
      check(doc.getElementById('total').textContent==='9','initial nine fixtures');
      buttons[1].focus();
      buttons[1].click();
      check(doc.activeElement===buttons[1],'mobile selection keeps focus');
      check(doc.getElementById('total').textContent==='10','mobile period B');
      check(doc.getElementById('meter-overdue').value===3,'mobile updated overdue meter');
      check(doc.getElementById('text-overdue').textContent==='3 de 10','mobile meter label');
      check(doc.querySelectorAll('[data-period][aria-pressed="true"]').length===1,'one pressed choice');
      buttons[2].click();
      check(doc.getElementById('delta').textContent.includes('Total: -1'),'mobile period C delta');
      buttons[3].click();
      check(doc.getElementById('total').textContent==='0','mobile empty period');
      check(doc.getElementById('meter-overdue').value===0,'mobile zero overdue meter');
      check(doc.getElementById('meter-overdue').max===1,'mobile safe meter maximum at zero');
      check(doc.getElementById('text-overdue').textContent==='0 de 0','mobile zero label');
      check(!doc.getElementById('empty-period-note').hidden,'mobile empty-state explanation');
      check(doc.documentElement.scrollWidth<=320,'no overflow after interaction');
      check(!doc.querySelector('form,input,textarea'),'no mobile data capture');
      finish('PASS','Chromium 320px synthetic follow-up dashboard');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.srcdoc=source;
  document.body.appendChild(frame);
  setTimeout(function(){if(!done)finish('FAIL','iframe load timed out');},2400);
})();
</script></body></html>
HTML;
    $driver = str_replace('__HTML__', $serialized, $driver);
    if (file_put_contents($driverTemp, $driver) === false) {
        fwrite(STDERR, "UX-F4-22: could not write 320px driver.\n");
        exit(1);
    }

    $mobile = BrowserSmokeRunner::run(
        $browser,
        ['--window-size=620,820', '--virtual-time-budget=3200'],
        'file://'.$driverTemp,
        'data-imagine-320',
    );
    if (!$mobile['passed']) {
        fwrite(STDERR, "UX-F4-22 320px browser FAIL: {$mobile['reason']}\n");
        exit(1);
    }

    echo "UX-F4-22 PASS: real Chromium desktop and 320px interactions, synthetic offline only.\n";
} finally {
    @unlink($temp);
    @unlink($driverTemp);
}
