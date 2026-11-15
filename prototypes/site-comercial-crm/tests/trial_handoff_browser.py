#!/usr/bin/env python3
"""Chromium real: site comercial -> cadastro exclusivamente fictício -> trial de exemplo.

Nunca inicia usuário, cobra, envia OTP ou usa dados reais; apenas arquivo file://.
"""
from __future__ import annotations
import html
import json
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

SITE = (Path(__file__).resolve().parents[1] / "index.html").resolve()

DRIVER = r"""<!doctype html><html lang="pt-BR"><meta charset="utf-8">
<body><output data-crm-handoff="RUNNING">Verificando</output><script>
(() => {
 'use strict';
 const out=document.querySelector('[data-crm-handoff]');
 const frame=document.createElement('iframe');
 frame.style.cssText='display:block;width:__WIDTH__px;height:900px;border:0';
 frame.width=__WIDTH__;frame.height=900;
 let step=0,done=false;
 function check(ok,desc){if(!ok)throw new Error(desc)}
 function finish(code,reason){
  if(done)return;done=true;
  out.setAttribute('data-crm-handoff',code);
  out.textContent=code+' '+reason;
 }
 frame.addEventListener('load',()=>{
  if(done)return;
  try{
   const d=frame.contentDocument,w=frame.contentWindow;
   check(d&&w&&w.innerWidth===__WIDTH__,'mesma origem local e viewport');
   check(d.documentElement.scrollWidth<=__WIDTH__,'sem overflow da etapa');
   if(step===0){
    check(d.querySelector('#planos')!==null,'seção de planos');
    check(d.querySelectorAll('.plan').length===4,'quatro planos');
    const link=d.querySelector('a[href="./fluxo-demo/index.html"]');
    check(link&&link.textContent.includes('demonstração do cadastro'),'link de jornada');
    check(!d.querySelector('form,input'),'nenhum formulário comercial');
    step=1;
    link.click();
   } else if(step===1){
    check(w.location.pathname.endsWith('/fluxo-demo/index.html'),'navegação à tela de trial');
    check(d.querySelector('a[href="../index.html#planos"]'),'voltar aos planos');
    check(d.body.textContent.includes('PROTÓTIPO — DADOS FICTÍCIOS.'),'aviso de dados fictícios');
    const logo=d.querySelector('img[src="../assets/logo-imagine.png"]');
    check(logo&&logo.complete&&logo.naturalWidth===200,'logo original no cadastro');
    check(!d.querySelector('form'),'sem formulário de cadastro');
    const fakeCode=d.querySelector('#code');
    check(fakeCode&&fakeCode.autocomplete==='off','código de exemplo');
    d.querySelector('#simulate').click();
    fakeCode.value='012345';
    d.querySelector('#validate').click();
    check(d.querySelector('#step-three').hidden===false,'revisão de código fictício');
    d.querySelector('#show-trial').click();
    check(d.querySelector('#step-four').hidden===false,'tela do trial fictício');
    check(d.querySelector('#activate-trial').disabled,'trial real permanece desabilitado');
    d.querySelector('#trial-duplicate').click();
    check(d.querySelector('#trial-status').textContent.includes('Duplicidade simulada'),'replay fictício');
    d.querySelector('#trial-expire').click();
    check(d.querySelector('#trial-status').textContent.includes('Fim dos 14 dias simulado'),'fim do trial fictício');
    check(d.documentElement.scrollWidth<=__WIDTH__,'sem overflow da tela final');
    finish('PASS','site -> OTP fictício -> trial ilustrativo; sem ativação');
   }
  }catch(e){finish('FAIL',e.message)}
 });
 frame.src=__URL__;
 document.body.appendChild(frame);
})();
</script></body></html>"""


def main():
    chrome = next((shutil.which(n) for n in
                   ("google-chrome", "google-chrome-stable", "chromium", "chromium-browser")
                   if shutil.which(n)), None)
    if not chrome or not SITE.exists():
        print("FAIL: Chromium/página indisponíveis", file=sys.stderr)
        return 1
    for width in (375, 1280):
        with tempfile.TemporaryDirectory(prefix="crm-trial-handoff-") as temp:
            folder=Path(temp)
            driver=folder/"driver.html"
            driver.write_text(DRIVER.replace("__URL__", json.dumps(SITE.as_uri()))
                              .replace("__WIDTH__", str(width)),encoding="utf-8")
            args=[chrome,"--headless","--no-sandbox","--disable-gpu",
                  "--disable-background-networking","--disable-extensions",
                  "--disable-dev-shm-usage","--no-first-run",
                  "--allow-file-access-from-files","--user-data-dir="+str(folder/"profile"),
                  "--virtual-time-budget=3600","--window-size=1500,1000",
                  "--dump-dom",driver.as_uri()]
            try:
                process=subprocess.run(args,text=True,capture_output=True,timeout=45)
            except subprocess.TimeoutExpired:
                print(f"FAIL {width}: Chromium sem resultado",file=sys.stderr)
                return 1
            match=re.search(r'<output\b[^>]*data-crm-handoff="(PASS|FAIL)"[^>]*>(.*?)</output>',
                            process.stdout,re.DOTALL)
            if process.returncode!=0 or match is None or match.group(1)!="PASS":
                reason=html.unescape(re.sub(r"<[^>]+>","",match.group(2))) if match else "sem PASS do navegador"
                print(f"FAIL {width}: {reason}",file=sys.stderr)
                return 1
            print(f"PASS {width}px: jornada fictícia via Chromium executada")
    return 0

if __name__=="__main__":
    raise SystemExit(main())
