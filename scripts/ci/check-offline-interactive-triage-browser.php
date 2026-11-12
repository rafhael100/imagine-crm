<?php

declare(strict_types=1);

// F3-164: actual Chromium checks for interactive, all-fictional lead triage.
// Never connects to a server, performs real assignments or persists state.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f3-164-interactive-lead-triage/index.html';
$html = file_get_contents($source);
if (!is_string($html) || substr_count($html, '</body>') !== 1
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || substr_count($html, 'type="range"') !== 2
    || !str_contains($html, 'id="reset"')) {
    fwrite(STDERR, "F3-164: invalid offline triage or CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $browser = $finder->find($name);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "F3-164: real Chromium required.\n");
    exit(1);
}

$target = json_encode(
    'file://'.$source,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Interactive triage QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  const marker=document.querySelector('[data-imagine-browser-smoke]');
  const width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.style.cssText='display:block;width:'+width+'px;height:850px;border:0';
  frame.width=width;frame.height=850;
  let complete=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(state,why){
    if(complete)return;
    complete=true;
    marker.setAttribute('data-imagine-browser-smoke',state);
    marker.textContent='F3-164 '+state+': '+why;
  }
  frame.addEventListener('load',function(){
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'local iframe access');
      check(win.innerWidth===width,'real requested CSS viewport');
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction notice');
      const get=id=>doc.getElementById(id);
      const cap0=get('capacity0'),cap1=get('capacity1'),reset=get('reset');
      check(cap0&&cap1&&reset,'two sliders and reset control');
      check(get('metric-total').textContent==='5','five fictional leads');
      check(get('metric-planned').textContent==='2','default two planned slots');
      check(get('metric-waiting').textContent==='2','default two waiting');
      check(get('metric-handled').textContent==='1','engaged lead is excluded');
      check(get('ranking').textContent==='2 → 3 → 1 → 5','stable score-first ranking');
      check(get('assigned').querySelectorAll('li').length===2,'default two planned list entries');
      check(get('waiting').querySelectorAll('li').length===2,'default two waiting entries');
      check(get('assigned').textContent.includes('Posição 2 → vaga fictícia 2'),'round robin starts at second slot');
      function set(input,value){
        input.value=String(value);
        input.dispatchEvent(new win.Event('input',{bubbles:true}));
      }
      set(cap0,0);set(cap1,0);
      check(get('metric-planned').textContent==='0','zero capacity prevents planning');
      check(get('metric-waiting').textContent==='4','all unassigned leads waiting');
      check(get('assigned').querySelectorAll('li').length===0,'no ghost assignment');
      check(get('loads').textContent==='0 / 0','no load at zero capacity');
      set(cap0,3);set(cap1,0);
      check(get('metric-planned').textContent==='3','capacity restored for three leads');
      check(get('metric-waiting').textContent==='1','one lead still waiting');
      check(get('loads').textContent==='3 / 0','capacity-limited loads');
      check(get('ranking').textContent==='2 → 3 → 1 → 5','priority independent of capacity');
      reset.click();
      check(cap0.value==='1'&&cap1.value==='1','reset restores capacities');
      check(get('metric-planned').textContent==='2','reset restores assignments');
      check(get('metric-waiting').textContent==='2','reset restores waiting');
      check(doc.activeElement===cap0,'keyboard focus returns to first slider');
      check(get('simulation-status').textContent.includes('Nenhuma atribuição'),'explicit no business effect');
      check(doc.body.textContent.includes('Gravar: bloqueado'),'persistence denied');
      check(doc.documentElement.scrollWidth<=width,'no horizontal overflow');
      finish('PASS','real in-memory capacity changes, reset and mobile layout at '+width+'px');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-triage-'.bin2hex(random_bytes(12)).'.html';
    $driverHtml = str_replace(['__TARGET__', '__WIDTH__'], [$target, (string) $width], $driver);
    if (file_put_contents($file, $driverHtml) === false) {
        fwrite(STDERR, "F3-164: failed writing temporary driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1340,950', '--virtual-time-budget=2500', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-164 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-164 PASS: actual Chromium interactive lead triage at 1100px and 320px.\n";
