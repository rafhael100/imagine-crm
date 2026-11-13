<?php

declare(strict_types=1);

// F3-225 — Chromium real, fixture exclusivamente offline e fictício.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$html = file_get_contents(dirname(__DIR__, 2).'/prototypes/f3-225-csv-review-board/index.html');
if (!is_string($html) || strlen($html) > 30000
    || !str_contains($html, "script-src 'none'")
    || !str_contains($html, "connect-src 'none'")
    || substr_count($html, 'data-metric=') !== 4) {
    fwrite(STDERR, "F3-225 invalid offline fixture or security boundary.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$chrome = null;
foreach (['google-chrome','google-chrome-stable','chromium','chromium-browser'] as $candidate) {
    $chrome = $finder->find($candidate);
    if ($chrome !== null) break;
}
if ($chrome === null) {
    fwrite(STDERR, "F3-225 actual Chromium unavailable: NO-GO.\n");
    exit(1);
}

$serialized = json_encode(
    $html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"></head><body>
<output data-imagine-browser-smoke="RUNNING">F3-225 em verificação</output>
<script>
(function(){
'use strict';
const width=__WIDTH__,source=__HTML__;
const marker=document.querySelector('[data-imagine-browser-smoke]');
const frame=document.createElement('iframe');
frame.style.cssText='display:block;width:'+width+'px;height:950px;border:0';
frame.width=width;frame.height=950;frame.title='Imagine CRM CSV review offline synthetic';
let complete=false;
function verify(cond,msg){if(!cond)throw new Error(msg);}
function done(status,msg){if(complete)return;complete=true;
 marker.setAttribute('data-imagine-browser-smoke',status);
 marker.textContent='F3-225 '+status+' '+msg;}
frame.addEventListener('load',function(){
 try{
  const d=frame.contentDocument,w=frame.contentWindow;
  verify(!!d&&!!w&&w.innerWidth===width,'real viewport width '+width);
  const img=d.querySelector('img.brand-logo');
  verify(img&&img.complete&&img.naturalWidth===200&&img.naturalHeight===55,'official Imagine PNG');
  verify(d.querySelectorAll('img').length===1,'only approved logo');
  const css=w.getComputedStyle(d.documentElement);
  verify(css.getPropertyValue('--orange').trim()==='#f9941d','approved orange');
  verify(css.getPropertyValue('--ink').trim()==='#171b19','approved graphite');
  const expected={data_rows:'2',projection_collision_extra_rows:'1',
   source_bidi_control_cells:'1',unmapped_claimed_required_fields:'1'};
  Object.entries(expected).forEach(([key,val])=>{
   const out=d.querySelector('[data-metric="'+key+'"]');
   verify(out&&out.textContent.trim()===val,'synthetic Core metric '+key);
   verify(out.getBoundingClientRect().width>0,'metric visible '+key);
  });
  const columns=w.getComputedStyle(d.querySelector('.grid')).gridTemplateColumns.split(' ').filter(Boolean).length;
  verify(columns===(width<=480?1:width<=900?2:4),'responsive metrics');
  verify(d.querySelector('h1')?.textContent.includes('CSV'),'semantic heading');
  verify(d.querySelector('.notice')?.textContent.includes('bloqueadas'),'NO-GO prominent');
  const details=d.querySelector('details'),summary=details?.querySelector('summary');
  verify(details&&summary&&!details.open,'disclosure closed');
  summary.focus();summary.click();
  verify(details.open&&d.activeElement===summary,'disclosure keyboard focus');
  verify(details.textContent.includes('não permitido'),'deny-only actions');
  verify(!d.querySelector('form,input,textarea,select,button,iframe,script,object'),'no customer IO controls');
  verify(d.documentElement.scrollWidth<=width,'no horizontal overflow '+width);
  done('PASS','actual Chromium offline CSV at '+width+'px');
 }catch(e){done('FAIL',e.message);}
});
frame.srcdoc=source;document.body.appendChild(frame);
setTimeout(function(){if(!complete)done('FAIL','Chrome did not finish');},4500);
})();
</script></body></html>
HTML;

foreach ([1100, 550, 320] as $width) {
    $temp = sys_get_temp_dir().'/imagine-csv-review-'.bin2hex(random_bytes(10)).'.html';
    try {
        $driverHtml = str_replace(['__WIDTH__','__HTML__'], [(string) $width, $serialized], $driver);
        if (file_put_contents($temp, $driverHtml) === false) {
            fwrite(STDERR, "F3-225 temporary browser fixture unavailable.\n");
            exit(1);
        }
        $result = BrowserSmokeRunner::run(
            $chrome, ['--window-size=1350,1000','--virtual-time-budget=7000'],
            'file://'.$temp, 'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, "F3-225 Chrome {$width}px FAIL: {$result['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($temp);
    }
}
echo "F3-225 Chrome PASS: 1100/550/320px, official logo, Core parity, no customer IO.\n";
