#!/usr/bin/env python3
"""Chromium smoke real da jornada visual: comercial -> Cliente -> Kanban -> Admin.

Somente dados fictícios em file://; nenhum login, request de rede ou persistência.
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

PAGE = (Path(__file__).resolve().parents[1] / "index.html").resolve()
DRIVER = r"""<!doctype html><html lang="pt-BR"><meta charset="utf-8">
<output data-ux="RUNNING">Iniciando</output><script>
(() => {
 'use strict';
 const output=document.querySelector('[data-ux]');
 const frame=document.createElement('iframe');
 frame.style.cssText='border:0;width:__WIDTH__px;height:940px;display:block';
 frame.width=__WIDTH__;frame.height=940;
 let step=0,finished=false;
 const check=(v,message)=>{if(!v)throw Error(message)};
 const done=(code,message)=>{if(finished)return;finished=true;output.dataset.ux=code;output.textContent=code+': '+message};
 frame.addEventListener('load',()=>{
  if(finished)return;
  try{
   const doc=frame.contentDocument,win=frame.contentWindow;
   check(doc&&win&&win.innerWidth===__WIDTH__,'contexto HTML local acessível');
   check(doc.documentElement.scrollWidth<=__WIDTH__,'sem rolagem horizontal da página');
   if(step===0){
     check(!!doc.querySelector('#planos'),'preços comerciais visíveis');
     const nav=doc.querySelector('a[href="./painel-demo/index.html"]');
     check(!!nav,'link para demonstração de painéis');
     step=1;
     nav.click();
     return;
   }
   if(step===1){
     check(win.location.pathname.endsWith('/painel-demo/index.html'),'navegou ao painel');
     check(doc.querySelector('meta[name=robots]').content==='noindex,nofollow','noindex');
     const logo=doc.querySelector('.entry-head img');
     check(logo.complete&&logo.naturalWidth===200,'marca original carregada');
     check(doc.querySelector('#workspace').hidden,'painel oculto antes da escolha');
     check(!doc.querySelector('form,input'),'não solicita credenciais/dados');
     doc.querySelector('[data-enter="cliente"]').click();
     check(doc.querySelector('#entry').hidden,'entrada recolhida');
     check(doc.querySelector('#section-title').textContent==='Visão geral','visão geral do cliente');
     check(doc.querySelectorAll('.stat').length===4,'indicadores do cliente');
     const nav=doc.querySelector('[data-view="kanban"]');
     check(!!nav,'menu kanban visível');
     nav.click();
     check(doc.querySelectorAll('.lane').length===4,'quatro etapas Kanban');
     check(doc.querySelectorAll('.deal').length===7,'oportunidades de exemplo');
     const cta=doc.querySelector('#section-cta');
     cta.click();
     check(doc.querySelector('#modal').hidden===false,'aviso de ação indisponível abriu');
     check(doc.activeElement===doc.querySelector('#modal-x'),'foco do modal');
     doc.dispatchEvent(new win.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
     check(doc.querySelector('#modal').hidden===true,'Escape fechou modal');
     check(doc.activeElement===cta,'foco devolvido');
     doc.querySelector('[data-view="relatorios"]').click();
     check(doc.querySelector('#section-title').textContent==='Relatórios','tela relatórios');
     doc.querySelector('[data-exit]').click();
     check(!doc.querySelector('#entry').hidden,'volta à seleção');
     doc.querySelector('[data-enter="administrador"]').click();
     check(doc.querySelector('#section-kicker').textContent==='ADMINISTRAÇÃO IMAGINE','perfil administrador fictício');
     doc.querySelector('[data-view="empresas"]').click();
     check(doc.querySelectorAll('.data-table tbody tr').length===4,'quatro empresas fictícias');
     doc.querySelector('[data-view="assinaturas"]').click();
     check(doc.querySelector('#view').textContent.includes('Nenhuma assinatura'),'não há pagamento');
     check(!doc.querySelector('form,input'),'painel não coleta dados');
     check(doc.documentElement.scrollWidth<=__WIDTH__,'layout sem overflow');
     done('PASS','comercial -> cliente -> Kanban -> relatório -> admin -> empresas -> assinaturas');
   }
  }catch(e){done('FAIL',e.message)}
 });
 frame.src=__URL__;
 document.body.appendChild(frame);
})();
</script>"""


def main() -> int:
    chrome = next((shutil.which(x) for x in
                   ("google-chrome", "google-chrome-stable", "chromium", "chromium-browser")
                   if shutil.which(x)), None)
    if chrome is None or not PAGE.is_file():
        print("FAIL: Chromium ou HTML ausente", file=sys.stderr)
        return 1
    for width in (320, 375, 768, 1280):
        with tempfile.TemporaryDirectory(prefix="crm-dashboard-review-") as folder:
            root = Path(folder)
            path = root / "driver.html"
            path.write_text(DRIVER.replace("__URL__", json.dumps(PAGE.as_uri()))
                            .replace("__WIDTH__", str(width)), "utf-8")
            args = [
                chrome, "--headless", "--no-sandbox", "--disable-gpu",
                "--disable-background-networking", "--disable-extensions",
                "--disable-dev-shm-usage", "--no-first-run",
                "--allow-file-access-from-files", "--user-data-dir=" + str(root / "profile"),
                "--virtual-time-budget=4500", "--window-size=1500,1100",
                "--dump-dom", path.as_uri(),
            ]
            try:
                result = subprocess.run(args, capture_output=True, text=True, timeout=50)
            except subprocess.TimeoutExpired:
                print("FAIL: browser sem resposta", file=sys.stderr)
                return 1
            match = re.search(r'<output\b[^>]*data-ux="(PASS|FAIL)"[^>]*>(.*?)</output>',
                              result.stdout, re.DOTALL)
            if result.returncode != 0 or match is None or match.group(1) != "PASS":
                message = html.unescape(re.sub('<[^>]*>', '', match.group(2))) if match else "sem resultado de navegador"
                print(f"FAIL {width}px: {message}", file=sys.stderr)
                return 1
            print(f"PASS {width}px: jornada visual Cliente/Admin sem autenticação ou dados reais")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
