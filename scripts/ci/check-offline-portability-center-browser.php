<?php

declare(strict_types=1);

// F3-201: real Chrome checks for an offline IN-118 navigation rehearsal.
// No external HTTP, account, file, storage, permission or real payload.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$path = dirname(__DIR__, 2).'/prototypes/f3-201-portability-center/index.html';
$html = file_get_contents($path);
if (!is_string($html) || strlen($html) > 23000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')) {
    fwrite(STDERR, "F3-201 invalid offline HTML/CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$chrome = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $candidate) {
    $chrome = $finder->find($candidate);
    if ($chrome !== null) break;
}
if ($chrome === null) {
    fwrite(STDERR, "F3-201 real Chromium unavailable; refusing simulated PASS.\n");
    exit(1);
}

$serialized = json_encode($html, JSON_HEX_TAG | JSON_HEX_AMP
    | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"></head><body>
<output data-imagine-browser-smoke="RUNNING"></output>
<script>
(function(){
'use strict';
const width=__WIDTH__,content=__HTML__;
const marker=document.querySelector('[data-imagine-browser-smoke]');
const frame=document.createElement('iframe');
frame.style.cssText='display:block;width:'+width+'px;height:980px;border:0';
frame.width=width;frame.height=980;frame.title='IN-118 local synthetic experience';
let finished=false;
function check(value,why){if(!value)throw new Error(why);}
function done(status,why){if(finished)return;finished=true;
 marker.setAttribute('data-imagine-browser-smoke',status);
 marker.textContent='F3-201 '+status+' '+why;}
frame.addEventListener('load',function(){
try {
 const d=frame.contentDocument,w=frame.contentWindow;
 check(d&&w&&w.innerWidth===width,'viewport must be real '+width+'px');
 const logo=d.querySelector('img.brand-logo');
 check(logo&&logo.alt==='Imagine'&&logo.src.startsWith('data:image/png;base64,'),
  'only original embedded Imagine logo');
 check(logo.complete&&logo.naturalWidth===200&&logo.naturalHeight===55,
  'official Imagine PNG decodes');
 check(w.getComputedStyle(d.body).backgroundColor==='rgba(0, 0, 0, 0)'||
  w.getComputedStyle(d.documentElement).backgroundColor==='rgb(247, 247, 248)',
  'Imagine surface computed');
 const buttons=(type)=>Array.from(d.querySelectorAll('[data-'+type+']'));
 const selected=(type)=>buttons(type).filter(b=>b.getAttribute('aria-pressed')==='true');
 const pick=(type,value)=>{const b=buttons(type).find(x=>x.dataset[type]===value);
  check(!!b,'option missing '+type+' '+value);b.focus();b.click();
  check(d.activeElement===b,'focus lost on '+type);return b;};
 const txt=id=>d.getElementById(id).textContent;
 check(buttons('plan').length===4,'four plans');
 check(buttons('operation').length===7,'seven IN-118 operations');
 check(buttons('target').length===3,'three fictional targets');
 check(selected('plan').length===1&&selected('operation').length===1&&selected('target').length===1,
  'one option selected per group');
 check(txt('warning-count')==='1','initial export only needs permission review');
 check(txt('operation-display')==='Exportar CSV','CSV default');
 check(buttons('plan').every(x=>x.getBoundingClientRect().height>=44) &&
   buttons('operation').every(x=>x.getBoundingClientRect().height>=44) &&
   buttons('target').every(x=>x.getBoundingClientRect().height>=44),
  'all options are >=44px touch targets');
 pick('plan','enterprise');
 check(txt('plan-display')==='Empresarial','enterprise plan selected');
 pick('operation','backup_complete');
 check(txt('warning-count')==='2','backup scope is not verified');
 check(txt('operation-display')==='Backup completo','backup mode');
 pick('operation','restore_complete');
 pick('target','populated');
 check(txt('warning-count')==='3','existing target must be separately reviewed');
 check(txt('warning-list').includes('não mesclar automaticamente'),'no automatic merge');
 pick('target','unknown');
 check(txt('warning-count')==='3','unknown target not treated as empty');
 check(txt('warning-list').includes('desconhecido'),'unknown target warning');
 pick('operation','migrate_account');
 check(txt('warning-count')==='4','cross account secret warning');
 check(txt('warning-list').includes('Segredos e credenciais'),'secrets exclusion');
 pick('operation','import_xlsx');
 check(txt('warning-count')==='2','spreadsheet import preview requires field review');
 pick('operation','export_xlsx');
 check(txt('warning-count')==='1','full quota does not block existing export');
 check(txt('operation-status').includes('nenhuma operação autorizada'),'status never authorizes');
 const review=d.getElementById('review-example');
 review.focus();review.click();
 check(d.activeElement===review,'review button preserves focus');
 check(txt('review-result').includes('Nenhum arquivo foi lido, gravado ou enviado.'),
  'review is not an execution');
 check(!d.querySelector('form,input,textarea,select,iframe,object,video'),'no capture or external embed');
 check(d.querySelectorAll('img').length===1,'only approved image');
 check(d.documentElement.scrollWidth<=width,'no horizontal document overflow '+width+'px');
 done('PASS','real Chrome synthetic IN-118 UX '+width+'px');
}catch(error){done('FAIL',error.message);}
});
frame.srcdoc=content;document.body.appendChild(frame);
setTimeout(function(){if(!finished)done('FAIL','real Chrome iframe timeout');},4500);
})();
</script></body></html>
HTML;

foreach ([1100, 550, 320] as $width) {
    $temp = sys_get_temp_dir().'/imagine-portability-'.bin2hex(random_bytes(10)).'.html';
    try {
        $driverHtml = str_replace(
            ['__WIDTH__', '__HTML__'], [(string) $width, $serialized], $driver,
        );
        if (file_put_contents($temp, $driverHtml) === false) {
            fwrite(STDERR, "F3-201 temporary local browser file unavailable.\n");
            exit(1);
        }
        $out = BrowserSmokeRunner::run(
            $chrome, ['--window-size=1340,1000','--virtual-time-budget=6500'],
            'file://'.$temp, 'data-imagine-browser-smoke',
        );
        if (!$out['passed']) {
            fwrite(STDERR, "F3-201 Chrome {$width}px FAIL: {$out['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($temp);
    }
}

echo "F3-201 Chromium PASS: 1100/550/320px, 4 plans, 7 operations, 3 targets, 44px, official logo, no data IO.\n";
