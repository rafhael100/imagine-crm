<?php

declare(strict_types=1);

// F3-197: real Chrome DOM, interactions and 320 CSS px for a purely fictional
// collision-review worklist. Runs a temporary local srcdoc driver, no server.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$filename = dirname(__DIR__, 2).'/prototypes/f3-197-lead-collision-worklist/index.html';
$html = file_get_contents($filename);
if (!is_string($html) || strlen($html) > 21000
    || substr_count($html, '</body>') !== 1
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')) {
    fwrite(STDERR, "F3-197 invalid offline fixture or CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$chrome = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $chrome = $finder->find($name);
    if ($chrome !== null) break;
}
if ($chrome === null) {
    fwrite(STDERR, "F3-197 real Chromium unavailable; refusing simulated PASS.\n");
    exit(1);
}

$serialized = json_encode($html, JSON_HEX_TAG | JSON_HEX_AMP
    | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"></head><body>
<output data-imagine-browser-smoke="RUNNING"></output>
<script>
(function(){
const width=__WIDTH__,content=__HTML__;
const marker=document.querySelector('[data-imagine-browser-smoke]');
const frame=document.createElement('iframe');
frame.style.cssText='display:block;width:'+width+'px;height:790px;border:0';
frame.width=width;frame.height=790;frame.title='F3-197 offline 320';
let done=false;
function check(ok,message){if(!ok)throw new Error(message);}
function finish(state,msg){if(done)return;done=true;
marker.setAttribute('data-imagine-browser-smoke',state);
marker.textContent='F3-197 '+state+' '+msg;}
frame.addEventListener('load',function(){
try {
 const doc=frame.contentDocument,win=frame.contentWindow;
 check(doc&&win&&win.innerWidth===width,'exact CSS width');
 check(win.getComputedStyle(doc.body).backgroundColor==='rgb(247, 247, 248)','Imagine surface');
 check(win.getComputedStyle(doc.querySelector('header')).backgroundColor==='rgb(255, 255, 255)','Imagine white header');
 const logo=doc.querySelector('img.brand-logo');
 check(logo&&logo.alt==='Imagine'&&logo.src.startsWith('data:image/png;base64,'),
  'only original embedded Imagine logo can appear');
 check(logo.complete&&logo.naturalWidth===200&&logo.naturalHeight===55,
  'original Imagine logo is actually decoded by Chromium');
 check(doc.getElementById('total-pairs').textContent==='8','eight fictional pairs');
 check(doc.getElementById('priority-pairs').textContent==='4','four priority pairs');
 check(doc.getElementById('filtered-pairs').textContent==='8','all pairs initially filtered');
 const rows=()=>Array.from(doc.querySelectorAll('#review-items li'));
 const pageInfo=()=>doc.getElementById('page-info').textContent;
 check(rows().length===3,'first page has three rows');
 check(rows()[0].textContent.includes('Exemplo 2 e exemplo 3'),'contradictory pair first');
 check(rows()[1].textContent.includes('Exemplo 3 e exemplo 4'),'second contradiction stable');
 check(rows()[2].textContent.includes('Exemplo 1 e exemplo 3'),'possible match after contradictions');
 check(pageInfo()==='Página 1 de 3','three pages in all mode');
 const next=doc.getElementById('page-next'),previous=doc.getElementById('page-previous');
 check(!next.disabled&&previous.disabled,'initial pagination status');
 next.click();
 check(pageInfo()==='Página 2 de 3','next page changes page only');
 check(rows().length===3,'next page three rows');
 previous.click();
 check(pageInfo()==='Página 1 de 3','previous returns to first');
 const buttons=Array.from(doc.querySelectorAll('[data-reason]'));
 check(buttons.length===5,'five named filters');
 check(buttons.every(b=>b.getBoundingClientRect().height>=44),'44px filter touch targets');
 const chosen=buttons.find(b=>b.dataset.reason==='POSSIBLE_PERSON_MATCH');
 chosen.focus();chosen.click();
 check(doc.activeElement===chosen,'filter preserves keyboard focus');
 check(doc.getElementById('filtered-pairs').textContent==='2','two person collision claims');
 check(rows().length===2,'two filtered rows');
 check(rows().every(r=>r.textContent.includes('Possível coincidência')),'matching reason labels');
 check(pageInfo()==='Página 1 de 1'&&next.disabled,'filter resets page bounds');
 check(buttons.filter(b=>b.getAttribute('aria-pressed')==='true').length===1,'single active filter');
 buttons.find(b=>b.dataset.reason==='RETRY_CLAIM_UNVERIFIED').click();
 check(rows().length===2&&rows().every(r=>r.textContent.includes('Reenvio lógico')),'retry distinguished');
 buttons.find(b=>b.dataset.reason==='CONFLICTING_RETRY_CLAIM').click();
 check(rows().length===2&&rows().every(r=>r.textContent.includes('contato divergente')),'conflicts visible');
 buttons.find(b=>b.dataset.reason==='all').click();
 check(rows().length===3&&pageInfo()==='Página 1 de 3','reset to all and page1');
 check(doc.getElementById('result-summary').textContent.includes('nenhuma conclusão'),
   'no merge or identity assertion');
 check(!doc.querySelector('form,input,textarea,iframe,object,video'),'no customer capture');
 check(doc.documentElement.scrollWidth<=width,'no page-wide horizontal overflow at '+width);
 finish('PASS','real Chrome synthetic review with filtering/paging '+width+'px');
}catch(error){finish('FAIL',error.message);}
});
frame.srcdoc=content;document.body.appendChild(frame);
setTimeout(function(){if(!done)finish('FAIL','iframe render timeout');},4000);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $temp = sys_get_temp_dir().'/imagine-collision-'.bin2hex(random_bytes(10)).'.html';
    try {
        $page = str_replace(['__WIDTH__', '__HTML__'], [(string) $width, $serialized], $driver);
        if (file_put_contents($temp, $page) === false) {
            fwrite(STDERR, "F3-197 temporary browser driver could not be written.\n");
            exit(1);
        }
        $test = BrowserSmokeRunner::run(
            $chrome,
            ['--window-size=1300,950','--virtual-time-budget=5500'],
            'file://'.$temp,
            'data-imagine-browser-smoke',
        );
        if (!$test['passed']) {
            fwrite(STDERR, "F3-197 Chromium {$width}px FAIL: {$test['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($temp);
    }
}
echo "F3-197 Chrome PASS: desktop and 320px offline collision review; no real customer data.\n";
