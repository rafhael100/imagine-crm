<?php

declare(strict_types=1);

// Real Chrome/Chromium in isolated ephemeral profiles. The tested page is
// file:// only; no web server, network, client data or persistent state.
require dirname(__DIR__,2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$path=dirname(__DIR__,2).'/prototypes/f3-55-sales-pipeline-lab/index.html';
$html=file_get_contents($path);
if (!is_string($html) || strlen($html)>28000
    || substr_count($html,'</body>')!==1
    || !str_contains($html,"connect-src 'none'")
    || !str_contains($html,"form-action 'none'")) {
    fwrite(STDERR,"F3-55: invalid offline reconciliation dashboard\n");
    exit(1);
}
$browser=null;
$finder=new ExecutableFinder();
foreach (['google-chrome','google-chrome-stable','chromium','chromium-browser'] as $name) {
    $browser=$finder->find($name);
    if ($browser!==null)break;
}
if ($browser===null) {
    fwrite(STDERR,"F3-55: actual Chromium unavailable. No browser verification.\n");
    exit(1);
}

$checks=<<<'JS'
(function(){
  const result=document.createElement('output');
  result.setAttribute('data-imagine-browser-smoke','RUNNING');
  document.body.appendChild(result);
  function check(cond,msg){if(!cond)throw new Error(msg);}
  const count=i=>Number(document.getElementById('count-'+i).textContent);
  const selected=()=>Array.from(document.querySelectorAll('[data-select][aria-pressed="true"]'));
  function pick(id){const btn=document.querySelector('[data-select="'+id+'"]');check(btn,'card exists '+id);btn.focus();btn.click();}
  const action=code=>document.querySelector('[data-action="'+code+'"]');
  const history=()=>Array.from(document.querySelectorAll('#action-history li')).map(x=>x.textContent);
  try{
    check(count(0)===2&&count(1)===1&&count(2)===1,'original four cards 2/1/1');
    check(document.getElementById('total-count').textContent==='4','fixed four fictitious leads');
    check(selected().length===1&&selected()[0].dataset.select==='a','A selected by default');
    check(action('back').disabled&&action('undo').disabled,'back/undo disabled at initial state');
    action('next').click();
    check(count(0)===1&&count(1)===2&&count(2)===1,'move A into contact');
    check(document.getElementById('selection-status').textContent.includes('Em contato'),'selection tracks moved stage');
    check(!action('undo').disabled,'undo enabled after move');
    check(history()[0].includes('Lead Exemplo A'),'history records synthetic move');
    action('undo').click();
    check(count(0)===2&&count(1)===1&&count(2)===1,'undo restores counts');
    check(document.getElementById('undo-count').textContent==='1','undo count 1');
    pick('c');
    check(document.activeElement.dataset.select==='c','card focus survives rerender');
    check(action('next').disabled&&!action('back').disabled,'last stage only back');
    action('back').click();
    check(count(1)===2&&count(2)===0,'C moved backward one stage');
    check(document.getElementById('action-status').textContent.includes('Nenhum lead real foi modificado'),'action explicitly not real');
    action('undo').click();
    check(count(1)===1&&count(2)===1,'C undo restored');
    pick('d');
    action('next').click();
    action('next').click();
    check(count(0)===1&&count(2)===2,'D reached qualification');
    check(action('next').disabled,'cannot advance beyond last stage');
    check(document.getElementById('moved-count').textContent==='4','move event tally bounded and accurate');
    action('reset').click();
    check(count(0)===2&&count(1)===1&&count(2)===1,'reset returns initial distribution');
    check(document.getElementById('moved-count').textContent==='0','reset clears move total');
    check(document.getElementById('undo-count').textContent==='0','reset clears undo total');
    check(action('undo').disabled,'reset clears undo stack');
    check(history().length===1&&history()[0].includes('Nenhum evento'),'reset clears history');
    check(selected().length===1&&selected()[0].dataset.select==='a','reset selects initial card A');
    check(!document.querySelector('form,input,textarea'),'no client intake');
    check(document.querySelector('a[href="../s01-prot-01/index.html"]')!==null,'local return link');
    result.setAttribute('data-imagine-browser-smoke','PASS');
    result.textContent='CRM F3-55 desktop pipeline move/undo/reset PASS';
  }catch(e){
    result.setAttribute('data-imagine-browser-smoke','FAIL');
    result.textContent='CRM F3-55 desktop FAIL: '+e.message;
  }
})();
JS;
$desktopTemp=sys_get_temp_dir().'/crm-f352-desktop-'.bin2hex(random_bytes(8)).'.html';
$mobileTemp=sys_get_temp_dir().'/crm-f352-mobile-'.bin2hex(random_bytes(8)).'.html';
if (file_put_contents($desktopTemp,str_replace('</body>','<script>'.$checks.'</script></body>',$html))===false) {
    fwrite(STDERR,"F3-55 could not create temporary desktop test\n");exit(1);
}
$serialized=json_encode($html,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS|JSON_THROW_ON_ERROR);
$mobile=<<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>CRM F3-55 mobile smoke</title></head>
<body><output data-imagine-320="RUNNING"></output><script>
(function(){
  const source=__HTML__;
  const status=document.querySelector('output[data-imagine-320]');
  const iframe=document.createElement('iframe');
  iframe.title='Simulador fictício de funil em 320 pixels';iframe.width=320;iframe.height=740;
  iframe.style.cssText='border:0;display:block;width:320px;height:740px';
  let completed=false;
  function finish(kind,msg){if(completed)return;completed=true;status.setAttribute('data-imagine-320',kind);status.textContent='F3-55 mobile '+kind+': '+msg;}
  function check(cond,msg){if(!cond)throw new Error(msg);}
  iframe.addEventListener('load',function(){
    if(completed)return;
    try {
      const d=iframe.contentDocument,w=iframe.contentWindow;
      check(d!==null&&w!==null&&w.innerWidth===320,'same-origin real 320 CSS pixels');
      const count=i=>Number(d.getElementById('count-'+i).textContent);
      const action=code=>d.querySelector('[data-action="'+code+'"]');
      check(d.documentElement.scrollWidth<=320,'initial no horizontal page overflow');
      check(count(0)===2&&count(1)===1&&count(2)===1,'original mobile stages');
      const pick=d.querySelector('[data-select="b"]');
      pick.focus();pick.click();
      check(d.activeElement.dataset.select==='b','mobile card keeps keyboard focus');
      action('next').click();
      check(count(1)===0&&count(2)===2,'B moved forward to qualification');
      check(action('next').disabled,'B cannot exceed final stage');
      action('undo').click();
      check(count(1)===1&&count(2)===1,'undo mobile returns B to contact');
      action('reset').click();
      check(count(0)===2&&count(1)===1&&count(2)===1,'mobile reset originals');
      check(action('undo').disabled,'mobile undo stack empty after reset');
      check(d.documentElement.scrollWidth<=320,'no overflow after real mobile interactions');
      check(!d.querySelector('form,input,textarea'),'no mobile collection form');
      finish('PASS','keyboard selection + stage/undo/reset within 320 CSS pixels');
    }catch(e){finish('FAIL',e.message);}
  });
  iframe.srcdoc=source;document.body.appendChild(iframe);
  setTimeout(function(){if(!completed)finish('FAIL','iframe did not load');},2500);
})();
</script></body></html>
HTML;
$mobile=str_replace('__HTML__',$serialized,$mobile);
if(file_put_contents($mobileTemp,$mobile)===false) {
    @unlink($desktopTemp);fwrite(STDERR,"F3-55 could not create mobile driver\n");exit(1);
}
try {
    $desktop=BrowserSmokeRunner::run($browser,['--window-size=1100,860','--virtual-time-budget=2400'],
        'file://'.$desktopTemp,'data-imagine-browser-smoke');
    if(!$desktop['passed']) {
        fwrite(STDERR,"F3-55 desktop FAIL: ".$desktop['reason']."\n");exit(1);
    }
    $mobileRun=BrowserSmokeRunner::run($browser,['--window-size=620,860','--virtual-time-budget=3600'],
        'file://'.$mobileTemp,'data-imagine-320');
    if(!$mobileRun['passed']) {
        fwrite(STDERR,"F3-55 320px FAIL: ".$mobileRun['reason']."\n");exit(1);
    }
    echo "F3-55 PASS: real Chromium desktop & 320 CSS px, 4 synthetic lead cards, no network or PII.\n";
} finally {
    @unlink($desktopTemp);@unlink($mobileTemp);
}
