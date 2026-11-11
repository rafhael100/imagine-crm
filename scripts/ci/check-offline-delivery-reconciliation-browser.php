<?php

declare(strict_types=1);

// Real Chrome/Chromium in isolated ephemeral profiles. The tested page is
// file:// only; no web server, network, client data or persistent state.
require dirname(__DIR__,2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$path=dirname(__DIR__,2).'/prototypes/f3-52-delivery-reconciliation/index.html';
$html=file_get_contents($path);
if (!is_string($html) || strlen($html)>28000
    || substr_count($html,'</body>')!==1
    || !str_contains($html,"connect-src 'none'")
    || !str_contains($html,"form-action 'none'")) {
    fwrite(STDERR,"F3-52: invalid offline reconciliation dashboard\n");
    exit(1);
}
$browser=null;
$finder=new ExecutableFinder();
foreach (['google-chrome','google-chrome-stable','chromium','chromium-browser'] as $name) {
    $browser=$finder->find($name);
    if ($browser!==null)break;
}
if ($browser===null) {
    fwrite(STDERR,"F3-52: actual Chromium unavailable. No browser verification.\n");
    exit(1);
}

$checks=<<<'JS'
(function(){
  const result=document.createElement('output');
  result.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(result);
  function check(cond,msg){if(!cond)throw new Error(msg);}
  const filters=Array.from(document.querySelectorAll('[data-filter]'));
  const sorters=Array.from(document.querySelectorAll('[data-sort]'));
  const rows=()=>Array.from(document.querySelectorAll('#case-rows tr'));
  const visible=()=>rows().filter(x=>!x.hidden);
  const detail=document.getElementById('case-detail');
  const summary=document.getElementById('list-status');
  function pick(code){document.querySelector('[data-filter="'+code+'"]').click();}
  function inspect(code){document.querySelector('[data-detail="'+code+'"]').click();}
  try {
    check(document.getElementById('metric-total').textContent==='6','total six fixture cases');
    check(document.getElementById('metric-review').textContent==='2','two review cases');
    check(document.getElementById('metric-wait').textContent==='3','three waiting cases');
    check(document.getElementById('metric-lab').textContent==='1','one mock receipt');
    check(filters.length===4 && sorters.length===2,'accessible filter/sort control cardinality');
    check(filters.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'initial single filter');
    check(visible().length===6 && detail.hidden,'six initial cases, no selected detail');
    inspect('c');
    check(!detail.hidden && detail.textContent.includes('Não reenviar automaticamente'),'unknown outcome detail blocks retry');
    check(document.querySelector('[data-detail="c"]').getAttribute('aria-expanded')==='true','details exposed');
    pick('waiting');
    check(visible().length===3 && detail.hidden,'switching filter removes hidden selected case');
    check(summary.textContent.includes('3 de 6'),'waiting summary count');
    pick('review');
    check(visible().length===2,'two review cases');
    check(JSON.stringify(visible().map(x=>x.dataset.case))===JSON.stringify(['c','d']),'review A/B ordering');
    inspect('d');
    check(detail.textContent.includes('Negar processamento'),'tenant mismatch denies');
    pick('lab');
    check(visible().length===1 && detail.hidden,'mock receipt only, old detail cleared');
    inspect('e');
    check(detail.textContent.includes('Não declarar lead entregue a um CRM real'),'mock receipt not real');
    pick('all');
    check(visible().length===6 && !detail.hidden,'return all restores selected visible row');
    sorters.find(x=>x.dataset.sort==='review-first').click();
    check(rows()[0].dataset.case==='c' && rows()[1].dataset.case==='d','review-first stable sort');
    check(sorters.filter(x=>x.getAttribute('aria-pressed')==='true').length===1,'single active sort');
    sorters.find(x=>x.dataset.sort==='original').click();
    check(JSON.stringify(rows().map(x=>x.dataset.case))===JSON.stringify(['a','b','c','d','e','f']),'original order restored');
    inspect('e');
    check(detail.hidden,'toggle selected details closed');
    check(!document.querySelector('form,input,textarea,iframe'),'no intake controls');
    check(document.querySelector('a[href="../s01-prot-01/index.html"]')!==null,'local navigation link');
    result.setAttribute('data-imagine-browser-smoke','PASS');
    result.textContent='CRM F3-52 desktop six-case reconciliation browser PASS';
  } catch(error) {
    result.setAttribute('data-imagine-browser-smoke','FAIL');
    result.textContent='CRM F3-52 desktop FAIL: '+error.message;
  }
})();
JS;

$desktopTemp=sys_get_temp_dir().'/crm-f352-desktop-'.bin2hex(random_bytes(8)).'.html';
$mobileTemp=sys_get_temp_dir().'/crm-f352-mobile-'.bin2hex(random_bytes(8)).'.html';
if (file_put_contents($desktopTemp,str_replace('</body>','<script>'.$checks.'</script></body>',$html))===false) {
    fwrite(STDERR,"F3-52 could not create temporary desktop test\n");exit(1);
}
$serialized=json_encode($html,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS|JSON_THROW_ON_ERROR);
$mobile=<<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>CRM F3-52 mobile smoke</title></head>
<body><output data-imagine-320="RUNNING"></output><script>
(function(){
  const source=__HTML__;
  const status=document.querySelector('output[data-imagine-320]');
  const iframe=document.createElement('iframe');
  iframe.title='Conferência fictícia em 320 pixels'; iframe.width=320;iframe.height=740;
  iframe.style.cssText='border:0;display:block;width:320px;height:740px';
  let completed=false;
  function finish(kind,msg){if(completed)return;completed=true;status.setAttribute('data-imagine-320',kind);status.textContent='F3-52 mobile '+kind+': '+msg;}
  function check(cond,msg){if(!cond)throw new Error(msg);}
  iframe.addEventListener('load',function(){
    if(completed)return;
    try {
      const d=iframe.contentDocument,w=iframe.contentWindow;
      check(d!==null&&w!==null,'same-origin srcdoc');
      check(w.innerWidth===320,'actual 320 CSS pixel viewport');
      check(d.documentElement.scrollWidth<=320,'initial page no horizontal overflow');
      check(d.getElementById('metric-review').textContent==='2','two review cases mobile');
      const shown=()=>d.querySelectorAll('#case-rows tr:not([hidden])').length;
      const review=d.querySelector('[data-filter="review"]');
      review.focus();review.click();
      check(d.activeElement===review,'keyboard focus retained after filter activation');
      check(shown()===2,'mobile review filter');
      d.querySelector('[data-detail="c"]').click();
      check(d.getElementById('case-detail').textContent.includes('Não reenviar automaticamente'),'mobile unknown result does not retry');
      d.querySelector('[data-sort="review-first"]').click();
      check(d.querySelector('#case-rows tr').dataset.case==='c','mobile priority sort');
      d.querySelector('[data-filter="waiting"]').click();
      check(shown()===3 && d.getElementById('case-detail').hidden,'mobile hidden detail cleared');
      d.querySelector('[data-filter="all"]').click();
      check(shown()===6,'mobile all filter');
      check(d.querySelectorAll('[data-filter][aria-pressed="true"]').length===1,'mobile one selected filter');
      check(d.documentElement.scrollWidth<=320,'no horizontal page overflow after interactions');
      check(!d.querySelector('form,input,textarea'),'no mobile data capture');
      finish('PASS','six synthetic reconciliation cases in true 320px iframe');
    }catch(e){finish('FAIL',e.message);}
  });
  iframe.srcdoc=source;
  document.body.appendChild(iframe);
  setTimeout(function(){if(!completed)finish('FAIL','iframe did not load');},2400);
})();
</script></body></html>
HTML;
$mobile=str_replace('__HTML__',$serialized,$mobile);
if(file_put_contents($mobileTemp,$mobile)===false) {
    @unlink($desktopTemp);fwrite(STDERR,"F3-52 could not create mobile driver\n");exit(1);
}
try {
    $desktop=BrowserSmokeRunner::run($browser,['--window-size=1100,860','--virtual-time-budget=2400'],
        'file://'.$desktopTemp,'data-imagine-browser-smoke');
    if(!$desktop['passed']) {
        fwrite(STDERR,"F3-52 desktop FAIL: ".$desktop['reason']."\n");exit(1);
    }
    $mobileRun=BrowserSmokeRunner::run($browser,['--window-size=620,860','--virtual-time-budget=3600'],
        'file://'.$mobileTemp,'data-imagine-320');
    if(!$mobileRun['passed']) {
        fwrite(STDERR,"F3-52 320px FAIL: ".$mobileRun['reason']."\n");exit(1);
    }
    echo "F3-52 PASS: real Chromium desktop & 320 CSS px, 6 offline cases, no network or PII.\n";
} finally {
    @unlink($desktopTemp);@unlink($mobileTemp);
}
