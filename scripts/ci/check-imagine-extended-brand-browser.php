<?php

declare(strict_types=1);

// DS-V1: actual headless Chromium computed-color and responsive proof for
// ten independent, local-only synthetic CRM screens. No network or storage.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2);
$folders = [
    'f3-22-csv-risk-preview', 'f3-104-retention-lifecycle',
    'f3-109-sales-quote-subtotal', 'f3-113-scoring-trend',
    'f3-115-weighted-forecast', 'f3-117-opportunity-outcomes',
    'f3-138-commercial-journey', 'f3-148-private-stage-dashboard',
    'f3-155-commercial-workload', 'f3-164-interactive-lead-triage',
];
$urls = [];
foreach ($folders as $folder) {
    $path = $root.'/prototypes/'.$folder.'/index.html';
    $html = file_get_contents($path);
    if (!is_string($html) || !str_contains($html, "connect-src 'none'")
        || !str_contains($html, "form-action 'none'")
        || !str_contains($html, 'IMAGINE-DS-V1')) {
        fwrite(STDERR, 'Imagine DS-V1 invalid offline screen: '.$folder.PHP_EOL);
        exit(1);
    }
    $urls[] = 'file://'.$path;
}
$browser = null;
$finder = new ExecutableFinder();
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $browser = $finder->find($name);
    if ($browser !== null) {
        break;
    }
}
if ($browser === null) {
    fwrite(STDERR, "Imagine DS-V1: actual Chromium required.\n");
    exit(1);
}

$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Imagine DS-V1 browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  const marker=document.querySelector('[data-imagine-browser-smoke]');
  const width=__WIDTH__;
  const urls=__URLS__;
  let remaining=urls.length;
  let issue=null;
  function check(value,reason){if(!value)throw new Error(reason);}
  function finish(){
    if(remaining!==0)return;
    marker.setAttribute('data-imagine-browser-smoke',issue?'FAIL':'PASS');
    marker.textContent=issue||'ten original Imagine palette screens at '+width+'px';
  }
  urls.forEach(function(url,index){
    const frame=document.createElement('iframe');
    frame.width=width;
    frame.height=780;
    frame.style.cssText='display:block;width:'+width+'px;height:780px;border:0';
    frame.addEventListener('load',function(){
      try{
        const doc=frame.contentDocument,win=frame.contentWindow;
        check(doc&&win,'file fixture accessible '+index);
        check(win.innerWidth===width,'exact viewport '+index);
        check(win.getComputedStyle(doc.body).backgroundColor==='rgb(247, 247, 248)',
          'surface mismatch '+index);
        check(win.getComputedStyle(doc.querySelector('header')).backgroundColor==='rgb(255, 255, 255)',
          'white header mismatch '+index);
        check(win.getComputedStyle(doc.documentElement).fontFamily.includes('Arial'),
          'Node font mismatch '+index);
        check(doc.documentElement.scrollWidth<=width,
          'horizontal overflow '+index+' at '+width+': '+doc.documentElement.scrollWidth);
      }catch(error){
        if(!issue)issue=String(index)+': '+error.message;
      }finally{
        remaining-=1;
        finish();
      }
    });
    frame.src=url;
    document.body.appendChild(frame);
  });
})();
</script></body></html>
HTML;

$payload = json_encode(
    $urls,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
foreach ([1100, 320] as $width) {
    $file = tempnam(sys_get_temp_dir(), 'imagine-ds-v1-');
    if ($file === false) {
        fwrite(STDERR, 'Imagine DS-V1 could not create temporary driver.'.PHP_EOL);
        exit(1);
    }
    try {
        $page = str_replace(
            ['__WIDTH__', '__URLS__'], [(string) $width, $payload], $driver,
        );
        if (file_put_contents($file, $page) === false) {
            fwrite(STDERR, 'Imagine DS-V1 could not write temporary driver.'.PHP_EOL);
            exit(1);
        }
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--allow-file-access-from-files', '--virtual-time-budget=10000',
                '--window-size=1240,1050'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, 'Imagine DS-V1 browser FAIL ('.$width.'px): '.$result['reason'].PHP_EOL);
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}
echo "Imagine DS-V1 ten static CRM screens: real Chromium 1100/320px PASS.\n";
