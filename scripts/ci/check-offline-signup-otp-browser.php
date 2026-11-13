<?php

declare(strict_types=1);

// F3-240: render the exact static CRM registration fixture in real headless
// Chromium at 1100 CSS pixels and in a 320 CSS-pixel srcdoc iframe.
// No network, route, customer data, real delivery, persistence or service.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$root = dirname(__DIR__, 2).'/prototypes/f3-240-signup-otp';
$html = file_get_contents($root.'/index.html');
if (!is_string($html)
    || strlen($html) > 30000
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")
    || !str_contains($html, "src=\"assets/logo-imagine.png\"")) {
    fwrite(STDERR, "F3-240: missing offline browser fixture or CSP.\n");
    exit(1);
}

$finder = new ExecutableFinder();
$browser = null;
foreach (['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser'] as $name) {
    $browser = $finder->find($name);
    if ($browser !== null) break;
}
if ($browser === null) {
    fwrite(STDERR, "F3-240: Chromium missing; real browser checks not run.\n");
    exit(1);
}

$encoded = json_encode(
    $html,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);

$driver = <<<'HTML'
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Imagine CRM offline signup browser QA</title></head>
<body>
<output id="smoke" data-imagine-browser-smoke="RUNNING"></output>
<script>
(function(){
  'use strict';
  const marker=document.getElementById('smoke');
  const source=__SOURCE__;
  const width=__WIDTH__;
  const frame=document.createElement('iframe');
  frame.setAttribute('title','Protótipo Imagine CRM na largura real');
  frame.setAttribute('width',String(width));
  frame.setAttribute('height','920');
  frame.style.cssText='border:0;display:block;width:'+width+'px;height:920px';
  let done=false;
  function check(ok,msg){if(!ok)throw new Error(msg);}
  function fail(msg){if(done)return;done=true;marker.dataset.imagineBrowserSmoke='FAIL';marker.textContent='F3-240: '+msg;}
  frame.addEventListener('load',function(){
    if(done)return;
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'srcdoc frame unavailable');
      check(win.innerWidth===width,'actual CSS viewport width '+win.innerWidth+' not '+width);
      check(doc.documentElement.scrollWidth<=width+1,'horizontal overflow '+doc.documentElement.scrollWidth+'/'+width);
      check(doc.querySelector('img[alt="Logomarca original da Imagine"]')!==null,'original Imagine logo');
      check(doc.getElementById('step-two').hidden,'OTP step hidden initially');
      check(doc.getElementById('step-three').hidden,'review step hidden initially');
      check(doc.getElementById('step-four').hidden,'trial step hidden initially');
      check(doc.getElementById('finish').disabled,'real registration always unavailable');
      const find=(selector,value)=>Array.from(doc.querySelectorAll(selector)).find(x=>x.getAttribute(value.attr)===value.value);
      const alpha=doc.querySelector('[data-company="alpha"]');
      const whats=doc.querySelector('[data-channel="whatsapp"]');
      alpha.click();whats.click();
      check(alpha.getAttribute('aria-pressed')==='true','alphanumeric company selected');
      check(whats.getAttribute('aria-pressed')==='true','WhatsApp channel selected');
      doc.getElementById('simulate').click();
      check(!doc.getElementById('step-two').hidden,'OTP input step displayed');
      check(doc.activeElement===doc.getElementById('code'),'focus on OTP field');
      check(doc.getElementById('channel-review').textContent.includes('WhatsApp'),'selected channel visible');
      check(doc.getElementById('status').textContent.includes('Não houve'),'no external delivery claimed');
      const code=doc.getElementById('code');
      code.value='654321';
      code.dispatchEvent(new win.Event('input',{bubbles:true}));
      doc.getElementById('validate').click();
      check(doc.getElementById('step-three').hidden,'wrong example never advances');
      check(doc.getElementById('code-status').textContent.includes('não coincide'),'invalid code status');
      code.value='01x2345';
      code.dispatchEvent(new win.Event('input',{bubbles:true}));
      check(code.value==='012345','digits-only input and leading zero preserved');
      doc.getElementById('validate').click();
      check(!doc.getElementById('step-three').hidden,'synthetic matching code shows review');
      check(doc.getElementById('finish').disabled,'matching fixture cannot register');
      check(doc.activeElement===doc.getElementById('third-title'),'focus on review heading');
      doc.getElementById('show-trial').click();
      check(!doc.getElementById('step-four').hidden,'trial preview displayed');
      check(doc.activeElement===doc.getElementById('fourth-title'),'focus on trial heading');
      check(doc.querySelectorAll('.quota[data-meter][data-limit]').length===7,'all seven quota cards visible');
      check(doc.getElementById('trial-company').textContent.includes('alfanumérico'),'selected company follows preview');
      check(doc.getElementById('step-d').classList.contains('current'),'fourth step is active');
      check(doc.getElementById('activate-trial').disabled,'trial activation always unavailable');
      doc.getElementById('trial-duplicate').click();
      check(doc.getElementById('trial-status').textContent.includes('Duplicidade simulada'),'duplicate simulated only');
      doc.getElementById('trial-expire').click();
      check(doc.getElementById('trial-status').textContent.includes('Fim dos 14 dias simulado'),'expiration simulated only');
      doc.getElementById('expire').click();
      check(doc.getElementById('step-three').hidden,'expiry closes review');
      check(doc.getElementById('step-four').hidden,'expiry closes trial preview');
      check(doc.getElementById('code-status').textContent.includes('Expiração'),'expiry status');
      doc.getElementById('validate').click();
      check(doc.getElementById('step-three').hidden,'expired code cannot re-open review');
      doc.getElementById('restart').click();
      check(doc.getElementById('step-two').hidden,'restart closes OTP step');
      check(doc.getElementById('step-three').hidden,'restart closes review');
      check(doc.getElementById('step-four').hidden,'restart closes trial preview');
      check(doc.getElementById('code').value==='','restart clears OTP digits');
      check(doc.querySelector('[data-company="legacy"]').getAttribute('aria-pressed')==='true','company reset');
      check(doc.querySelector('[data-channel="email"]').getAttribute('aria-pressed')==='true','channel reset');
      check(doc.getElementById('finish').disabled,'no real signup after reset');
      check(!doc.querySelector('form'),'no form submission route');
      check(doc.querySelector('a.skip').getAttribute('href')==='#conteudo','skip link');
      done=true;
      marker.dataset.imagineBrowserSmoke='PASS';
      marker.textContent='F3-240 PASS offline interactions and width '+width;
    }catch(error){fail(String(error.message).slice(0,220));}
  });
  frame.srcdoc=source;
  document.body.appendChild(frame);
})();
</script>
</body>
</html>
HTML;

// The 550px viewport approximates a 1100px desktop at 200% browser zoom.
foreach ([1100, 550, 320] as $width) {
    $file = $root.'/.f3-240-qa-'.bin2hex(random_bytes(10)).'.html';
    $source = str_replace(
        ['__SOURCE__', '__WIDTH__'],
        [$encoded, (string) $width],
        $driver,
    );
    if (file_put_contents($file, $source, LOCK_EX) === false) {
        fwrite(STDERR, "F3-240: cannot prepare ephemeral browser driver.\n");
        exit(1);
    }

    try {
        $result = BrowserSmokeRunner::run(
            $browser,
            ['--no-sandbox', '--window-size=1400,1000', '--virtual-time-budget=2000'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$result['passed']) {
            fwrite(STDERR, 'F3-240 browser QA failed at '.$width.'px: '
                .$result['reason'].' (attempts '.$result['attempts'].')'.PHP_EOL);
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-240 PASS: real Chromium 1100/550/320px, OTP and seven trial quotas, no registration or network.\n";
