<?php

declare(strict_types=1);

// F3-163 — actual Chromium validation of four offline sales-workload tabs.
// Tests local browser behavior at desktop and 320 CSS px; not CRM runtime.
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/BrowserSmokeRunner.php';

use Symfony\Component\Process\ExecutableFinder;

$source = dirname(__DIR__, 2).'/prototypes/f3-155-commercial-workload/index.html';
$html = file_get_contents($source);
if (!is_string($html)
    || substr_count($html, '</body>') !== 1
    || substr_count($html, 'type="radio"') !== 4
    || !str_contains($html, 'PROTÓTIPO — DADOS FICTÍCIOS.')
    || !str_contains($html, "script-src 'none'")
    || !str_contains($html, "connect-src 'none'")
    || !str_contains($html, "form-action 'none'")) {
    fwrite(STDERR, "F3-163: invalid offline dashboard/CSP.\n");
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
    fwrite(STDERR, "F3-163: real Chromium is required.\n");
    exit(1);
}

$target = json_encode(
    'file://'.$source,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$driver = <<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Offline sales workload browser QA</title></head>
<body><output data-imagine-browser-smoke="RUNNING"></output><script>
(function(){
  const marker=document.querySelector('[data-imagine-browser-smoke]');
  const frame=document.createElement('iframe');
  const width=__WIDTH__;
  frame.style.cssText='display:block;width:'+width+'px;height:850px;border:0';
  frame.width=width;
  frame.height=850;
  let finished=false;
  function check(ok,why){if(!ok)throw new Error(why);}
  function finish(state,why){
    if(finished)return;
    finished=true;
    marker.setAttribute('data-imagine-browser-smoke',state);
    marker.textContent='F3-163 '+state+': '+why;
  }
  frame.addEventListener('load',function(){
    try{
      const doc=frame.contentDocument,win=frame.contentWindow;
      check(doc&&win,'local document is readable');
      check(win.innerWidth===width,'actual CSS viewport');
      check(doc.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'fiction warning');
      const modes=['pressure','balanced','trend','triage'];
      check(doc.querySelectorAll('input[type="radio"][name="case"]').length===4,'four radio tabs');
      check(doc.querySelectorAll('.panels article.panel').length===4,'four panels');
      check(doc.querySelectorAll('.prohibition').length===4,'operational actions denied in every panel');
      check(doc.getElementById('pressure').checked,'initial mode is pressure');
      const visible=()=>modes.filter(mode=>{
        const panel=doc.querySelector('article.panel.'+mode);
        return panel&&win.getComputedStyle(panel).display!=='none';
      });
      check(JSON.stringify(visible())===JSON.stringify(['pressure']),'only pressure initially visible');
      const snippets={
        pressure:'Fila comercial exige atenção',
        balanced:'Capacidade ilustrativamente suficiente',
        trend:'Evolução ilustrativa da pressão comercial',
        triage:'Fila única de leads priorizados e tarefas'
      };
      for(const mode of modes){
        const radio=doc.getElementById(mode);
        const label=doc.querySelector('label[for="'+mode+'"]');
        check(radio&&label,'radio with visible text label '+mode);
        radio.focus();
        radio.click();
        check(doc.activeElement===radio,'keyboard-focusable control '+mode);
        check(radio.checked,'selected control '+mode);
        check(JSON.stringify(visible())===JSON.stringify([mode]),'exclusive visible panel '+mode);
        const panel=doc.querySelector('article.panel.'+mode);
        check(panel.textContent.includes(snippets[mode]),'mode content '+mode);
        check(panel.textContent.includes('Atribuir: bloqueado'),'no operational assignment '+mode);
        check(panel.textContent.includes('Gravar: bloqueado'),'no persistence '+mode);
        check(doc.documentElement.scrollWidth<=width,'no horizontal overflow '+mode);
      }
      check(doc.querySelector('.panel.triage').textContent.includes('cross_module_authorization_unverified'),
        'triage authorization still unverified');
      check(doc.querySelector('.panel.triage').textContent.includes('fictional_lead_capacity_shortfall'),
        'triage capacity warning');
      check(doc.querySelector('.panel.triage').textContent.includes('fictional_task_backlog_overdue'),
        'triage overdue task warning');
      const back=doc.querySelector('header a[href="../index.html"]');
      check(back&&back.textContent.includes('Voltar'),'offline return link');
      finish('PASS','four real DOM tab transitions at '+width+'px; offline and deny-only');
    }catch(error){finish('FAIL',error.message);}
  });
  frame.src=__TARGET__;
  document.body.appendChild(frame);
})();
</script></body></html>
HTML;

foreach ([1100, 320] as $width) {
    $file = sys_get_temp_dir().'/imagine-workload-'.bin2hex(random_bytes(12)).'.html';
    $driverHtml = str_replace(
        ['__TARGET__', '__WIDTH__'],
        [$target, (string) $width],
        $driver,
    );
    if (file_put_contents($file, $driverHtml) === false) {
        fwrite(STDERR, "F3-163: failed to create temporary browser driver.\n");
        exit(1);
    }
    try {
        $outcome = BrowserSmokeRunner::run(
            $browser,
            ['--window-size=1340,950', '--virtual-time-budget=2500', '--allow-file-access-from-files'],
            'file://'.$file,
            'data-imagine-browser-smoke',
        );
        if (!$outcome['passed']) {
            fwrite(STDERR, "F3-163 {$width}px FAIL: {$outcome['reason']}\n");
            exit(1);
        }
    } finally {
        @unlink($file);
    }
}

echo "F3-163 PASS: actual Chromium four tab transitions at 1100px and 320px, offline only.\n";
