<?php

declare(strict_types=1);

// F3-60 — actual headless Chromium page/layout checks for static hub.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$source = $root.'/prototypes/index.html';
$html = file_get_contents($source);
if (!is_string($html)
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, "script-src 'none'")
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')) {
    fwrite(STDERR, "F3-60: invalid hub HTML/CSP.\n");
    exit(1);
}
$paths = ['s01-prot-01/index.html', 'f3-59-offline-kanban/index.html', 'f3-22-csv-risk-preview/index.html', 'f3-104-retention-lifecycle/index.html', 'f3-109-sales-quote-subtotal/index.html', 'f3-113-scoring-trend/index.html', 'f3-115-weighted-forecast/index.html', 'f3-117-opportunity-outcomes/index.html', 'f3-138-commercial-journey/index.html', 'f3-148-private-stage-dashboard/index.html', 'f3-155-commercial-workload/index.html'];
foreach ($paths as $path) {
    if (!is_file($root.'/prototypes/'.$path)) {
        fwrite(STDERR, "F3-60: a local destination is absent; refusing a false navigation PASS.\n");
        exit(1);
    }
}
$finder = new ExecutableFinder();
$chrome = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $chrome = $finder->find($name);
    if ($chrome !== null) {
        break;
    }
}
if ($chrome === null) {
    fwrite(STDERR, "F3-60: real Chrome/Chromium unavailable.\n");
    exit(1);
}

$target = json_encode(
    'file://'.$source,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Offline hub browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  const marker=document.querySelector('[data-imagine-browser-smoke]');
  const frame=document.createElement('iframe');
  const width=__WIDTH__;
  frame.style.cssText='display:block;width:'+width+'px;height:800px;border:0';
  frame.width=width;
  frame.height=800;
  let completed=false;
  function check(ok,reason){if(!ok)throw new Error(reason);}
  function finish(state,reason){
    if(completed)return;
    completed=true;
    marker.setAttribute('data-imagine-browser-smoke',state);
    marker.textContent='F3-60 '+state+': '+reason;
  }
  frame.addEventListener('load',function(){
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'local frame contents available');
      check(win.innerWidth===width,'real requested CSS viewport');
      check(doc.querySelectorAll('.card').length===11,'eleven prototype cards rendered');
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'local-only warning visible');
      const links=Array.from(doc.querySelectorAll('.cards a'));
      check(links.length===11,'eleven navigable links');
      check(JSON.stringify(links.map(x=>x.getAttribute('href')))===JSON.stringify([
        's01-prot-01/index.html',
        'f3-59-offline-kanban/index.html',
        'f3-22-csv-risk-preview/index.html',
        'f3-104-retention-lifecycle/index.html',
        'f3-109-sales-quote-subtotal/index.html',
        'f3-113-scoring-trend/index.html',
        'f3-115-weighted-forecast/index.html',
        'f3-117-opportunity-outcomes/index.html',
        'f3-138-commercial-journey/index.html',
        'f3-148-private-stage-dashboard/index.html',
        'f3-155-commercial-workload/index.html'
      ]),'exact local destinations');
      check(links.every(x=>x.getAttribute('aria-label')!==null),'accessible link labels');
      links[1].focus();
      check(doc.activeElement===links[1],'keyboard focus on Kanban link');
      check(doc.documentElement.scrollWidth<=width,'no horizontal overflow');
      if(width===320){
        const grid=win.getComputedStyle(doc.querySelector('.cards')).gridTemplateColumns;
        check(grid.split(' ').length===1,'mobile cards stack into one column');
      }
      finish('PASS','eleven local destinations rendered at '+width+' CSS px');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-hub-'.bin2hex(random_bytes(12)).'.html';
    $content = str_replace(
        ['__TARGET__', '__WIDTH__'],
        [$target, (string) $width],
        $driver,
    );
    if (file_put_contents($file, $content) === false) {
        fwrite(STDERR, "F3-60: failed creating ephemeral browser driver.\n");
        exit(1);
    }
    try {
        $result = BrowserSmokeRunner::run(
            $chrome,
            ['--window-size=1340,950', '--virtual-time-budget=2500', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-60 {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-60 real Chromium hub: PASS eleven cards at desktop (1100px) and mobile (320px).\n";
