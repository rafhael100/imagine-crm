#!/usr/bin/env python3
"""Screenshots dos painéis fictícios sob Chromium headless em CI.

Sem rede, login, coleta de dados, credencial ou dados de clientes.
"""
from __future__ import annotations

import argparse
import json
import re
import shutil
import subprocess
import tempfile
from pathlib import Path

PAGE=(Path(__file__).resolve().parents[1]/"painel-demo"/"index.html").resolve()
CASES=(
    ("cliente","visao-geral",1280,900,"cliente-visao-geral-1280.png"),
    ("cliente","kanban",1280,900,"cliente-kanban-1280.png"),
    ("administrador","assinaturas",1280,900,"administracao-assinaturas-1280.png"),
    ("cliente","kanban",375,900,"cliente-kanban-375.png"),
)
DRIVER=r"""<!doctype html><html lang="pt-BR"><meta charset="utf-8">
<title>Render visual demonstrativo — Imagine CRM</title>
<style>html,body{padding:0;margin:0;overflow:hidden}iframe{display:block;border:0;width:__WIDTH__px;height:__HEIGHT__px}</style>
<span id="result" hidden>RUNNING</span>
<script>
const frame=document.createElement('iframe');
frame.src=__PAGE__;
frame.width=__WIDTH__;frame.height=__HEIGHT__;
frame.addEventListener('load',()=>{
 try{
  const d=frame.contentDocument;
  const role=__ROLE__,view=__VIEW__;
  const entry=d.querySelector('button[data-enter="'+role+'"]');
  if(!entry)throw Error('Role unavailable');
  entry.click();
  const target=d.querySelector('button[data-view="'+view+'"]');
  if(!target)throw Error('Navigation unavailable');
  target.click();
  if(d.querySelector('#section-title').textContent.trim()==='')throw Error('Heading missing');
  if(d.documentElement.scrollWidth>__WIDTH__)throw Error('Page overflows viewport');
  if(d.querySelector('#workspace').hidden)throw Error('Workspace hidden');
  document.querySelector('#result').textContent='PASS';
 }catch(e){document.querySelector('#result').textContent='FAIL '+e.message}
});
document.body.appendChild(frame);
</script></html>"""


def main():
    p=argparse.ArgumentParser()
    p.add_argument("--output",type=Path,required=True)
    args=p.parse_args()
    browser=next((shutil.which(x) for x in ("google-chrome","chromium","google-chrome-stable","chromium-browser")
                  if shutil.which(x)),None)
    assert browser and PAGE.is_file(), "Chromium ou página HTML ausentes"
    args.output.mkdir(parents=True,exist_ok=True)
    for role,view,width,height,filename in CASES:
        with tempfile.TemporaryDirectory(prefix="crm-ux-capture-") as td:
            root=Path(td)
            driver=root/"driver.html"
            html=DRIVER.replace("__PAGE__",json.dumps(PAGE.as_uri()))
            html=html.replace("__ROLE__",json.dumps(role)).replace("__VIEW__",json.dumps(view))
            html=html.replace("__WIDTH__",str(width)).replace("__HEIGHT__",str(height))
            driver.write_text(html,encoding="utf-8")
            common=[browser,"--headless","--no-sandbox","--disable-gpu",
                    "--disable-background-networking","--disable-extensions",
                    "--disable-dev-shm-usage","--no-first-run",
                    "--allow-file-access-from-files","--user-data-dir="+str(root/"profile"),
                    "--window-size="+str(width)+","+str(height),
                    "--virtual-time-budget=2200"]
            result=subprocess.run(common+["--dump-dom",driver.as_uri()],
                                  capture_output=True,text=True,timeout=50)
            assert result.returncode==0 and re.search(r'<span id="result" hidden="">PASS</span>',result.stdout),\
                f"Render de {filename} não passou na checagem JS"
            # New profile prevents reuse of chromium's exited process state.
            capture=subprocess.run([*common[:-2], "--user-data-dir="+str(root/"capture-profile"),
                                    "--window-size="+str(width)+","+str(height),
                                    "--virtual-time-budget=2200",
                                    "--screenshot="+str((args.output/filename).resolve()),driver.as_uri()],
                                   capture_output=True,text=True,timeout=50)
            path=args.output/filename
            assert capture.returncode==0 and path.is_file() and path.stat().st_size>5000,\
                f"Captura de {filename} falhou"
            print("PASS screenshot",filename, path.stat().st_size)


if __name__=="__main__":
    main()
