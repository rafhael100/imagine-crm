<?php

declare(strict_types=1);

// F3-56: real browser inspection of a static file:// launcher. Test-only
// srcdoc wrapper supplies assertions because the launcher forbids all JS.
require dirname(__DIR__,2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root=dirname(__DIR__,2).'/prototypes';
$src=file_get_contents($root.'/index.html');
if(!is_string($src)||strlen($src)>8000
    || !str_contains($src,"script-src 'none'")
    || !str_contains($src,"connect-src 'none'")
    || str_contains($src,'<script')) {
    fwrite(STDERR,"F3-56: offline launcher failed no-script guard\n");
    exit(1);
}
$finder=new ExecutableFinder();
$browser=null;
foreach(['google-chrome','google-chrome-stable','chromium','chromium-browser'] as $name) {
    $browser=$finder->find($name);
    if($browser!==null)break;
}
if($browser===null){fwrite(STDERR,"F3-56: real Chrome/Chromium required\n");exit(1);}
$encoded=json_encode($src,JSON_THROW_ON_ERROR|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS);

$template=<<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Offline hub browser check</title></head>
<body><output __MARKER__="RUNNING"></output><script>
(function(){
 const html=__SOURCE__;
 const size=__WIDTH__;
 const result=document.querySelector('output');
 const iframe=document.createElement('iframe');
 iframe.title='Hub de demonstrações Imagine CRM';iframe.width=size;iframe.height=800;
 iframe.style.cssText='border:0;display:block;width:'+size+'px;height:800px;';
 let done=false;
 function finish(kind,msg){if(done)return;done=true;result.setAttribute('__MARKER__',kind);result.textContent='F3-56 '+kind+': '+msg;}
 function check(v,message){if(!v)throw new Error(message);}
 iframe.addEventListener('load',function(){
  if(done)return;
  try{
   const d=iframe.contentDocument,w=iframe.contentWindow;
   check(d!==null&&w!==null&&w.innerWidth===size,'real CSS viewport '+size);
   const links=Array.from(d.querySelectorAll('section.grid article.card a[href]'));
   check(links.length===6,'six actual offline entry links');
   check(links[0].getAttribute('href')==='s01-prot-01/index.html','main entry correct');
   check(links[1].getAttribute('href')==='f3-55-sales-pipeline-lab/index.html','pipeline entry correct');
   check(links[2].getAttribute('href')==='f3-52-delivery-reconciliation/index.html','reconciliation entry correct');
   check(links.every(x=>!x.href.includes('https://')),'no remote link target');
   check(d.querySelectorAll('script,form,input,textarea,iframe').length===0,'no scripts/data capture in launcher');
   check(d.querySelector('meta[http-equiv="Content-Security-Policy"]').content.includes("script-src 'none'"),'inner page script forbidden');
   check(d.documentElement.scrollWidth<=size,'no page horizontal overflow at '+size);
   links[1].focus();
   check(d.activeElement===links[1],'keyboard focus on launcher card link');
   const details=d.querySelector('details');
   check(details&&!details.open,'guidance starts collapsed');
   details.querySelector('summary').click();
   check(details.open,'native keyboard-readable help expands');
   check(d.documentElement.scrollWidth<=size,'help panel no overflow at '+size);
   finish('PASS','six links, no scripts, focus/help, responsive '+size+'px');
  }catch(e){finish('FAIL',e.message);}
 });
 iframe.srcdoc=html;document.body.appendChild(iframe);
 setTimeout(function(){if(!done)finish('FAIL','iframe did not load');},2500);
})();
</script></body></html>
HTML;

$temps=[];
try{
    foreach([[980,'data-imagine-browser-smoke',1160],[320,'data-imagine-320',620]] as [$width,$marker,$window]) {
        $name=sys_get_temp_dir().'/imagine-offline-hub-'.bin2hex(random_bytes(8)).'.html';
        $temps[]=$name;
        $driver=str_replace(['__SOURCE__','__WIDTH__','__MARKER__'],
            [$encoded,(string)$width,$marker],$template);
        if(file_put_contents($name,$driver)===false)throw new RuntimeException('Failed to create ephemeral driver');
        $result=BrowserSmokeRunner::run($browser,
            ['--window-size='.$window.',900','--virtual-time-budget=3500'],
            'file://'.$name,$marker);
        if(!$result['passed'])throw new RuntimeException(
            'F3-56 Chromium '.$width.'px failed: '.$result['reason']);
    }
    echo "F3-56 PASS: real Chromium six-page launcher at 980/320px with focus, help and no external IO.\n";
}catch(Throwable $e){
    fwrite(STDERR,$e->getMessage()."\n");
    exit(1);
}finally{
    foreach($temps as $name)@unlink($name);
}
