#!/usr/bin/env python3
"""Smoke test de INTERAÇÕES REAIS em Chromium para prévia CRM (somente file://).

Sem servidor, backend, conta, CNPJ, pagamento, rede de terceiros ou clientes.
Usa somente Python stdlib e Chrome/Chromium já disponível no runner.
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

TARGET = (Path(__file__).resolve().parents[1] / "index.html").resolve()
SIZES = (320, 375, 768, 1280)
# DRIVER mantém um iframe local de largura exatamente conhecida.
DRIVER = r"""<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>QA CRM</title>
<body><output data-crm-preview="RUNNING">Iniciando</output><script>
(() => {
  'use strict';
  const out = document.querySelector('[data-crm-preview]');
  const target = __TARGET__;
  const width = __WIDTH__;
  const frame = document.createElement('iframe');
  frame.style.cssText = 'border:0;display:block;width:' + width + 'px;height:1000px';
  frame.width = width;
  frame.height = 1000;
  let finished = false;
  function check(value, message) { if (!value) throw new Error(message); }
  function done(status, message) {
    if (finished) return;
    finished = true;
    out.setAttribute('data-crm-preview', status);
    out.textContent = status + ': ' + width + 'px: ' + message;
  }
  frame.addEventListener('load', () => {
    if (finished) return;
    try {
      const doc = frame.contentDocument;
      const win = frame.contentWindow;
      check(doc && win, 'iframe local acessível');
      check(win.innerWidth === width, 'largura CSS do viewport');
      check(doc.documentElement.scrollWidth <= width, 'sem rolagem horizontal');
      const logo = doc.querySelector('.brand-logo');
      check(logo && logo.complete && logo.naturalWidth === 200, 'logo original carregada');
      check(doc.querySelectorAll('h1').length === 1, 'um título principal');
      check(doc.querySelector('meta[name=robots]').content === 'noindex,nofollow', 'noindex');
      check(!doc.querySelector('form,input,iframe'), 'ausência de captura de dados');
      const menu = doc.querySelector('#menu');
      const mobileLinks = doc.querySelector('#mobilelinks');
      check(!!menu && !!mobileLinks, 'navegação disponível');
      if (width <= 800) {
        check(win.getComputedStyle(menu).display !== 'none', 'botão menu móvel visível');
        menu.click();
        check(menu.getAttribute('aria-expanded') === 'true', 'menu aberto');
        check(mobileLinks.classList.contains('open'), 'opções móveis exibidas');
        mobileLinks.querySelector('a[href="#recursos"]').click();
        check(menu.getAttribute('aria-expanded') === 'false', 'menu fechado após navegar');
      } else {
        check(win.getComputedStyle(doc.querySelector('.links')).display !== 'none',
              'navegação desktop visível');
      }
      const faq = doc.querySelector('details.faq');
      check(!!faq, 'FAQ existe');
      faq.querySelector('summary').click();
      check(faq.open, 'FAQ expande');
      const ctas = doc.querySelectorAll('[data-action]');
      check(ctas.length >= 6, 'CTAs de planos e teste');
      const button = doc.querySelector('[data-action="Plano Essencial"]');
      button.click();
      const veil = doc.querySelector('#veil');
      check(veil.classList.contains('show'), 'modal demonstrativo abriu');
      check(doc.activeElement === doc.querySelector('#close'), 'foco no botão fechar');
      check(doc.querySelector('#dialogmsg').textContent.includes('pagamentos reais ainda não estão liberados'),
            'mensagem de proibição de pagamentos reais');
      doc.dispatchEvent(new win.KeyboardEvent('keydown', {key:'Escape', bubbles:true}));
      check(!veil.classList.contains('show'), 'Escape fecha modal');
      check(doc.activeElement === button, 'foco retornou ao acionador');
      done('PASS', 'menu, FAQ, modal, foco, logo e ausência de overflow');
    } catch (error) { done('FAIL', error.message); }
  });
  frame.src = target;
  document.body.appendChild(frame);
})();
</script></body></html>"""


def main() -> int:
    browser = next((shutil.which(n) for n in
                    ("google-chrome", "google-chrome-stable", "chromium", "chromium-browser")
                    if shutil.which(n)), None)
    if not browser or not TARGET.is_file():
        print("FAIL: Chromium ou página ausente; não há aprovação por inspeção estática", file=sys.stderr)
        return 1

    for width in SIZES:
        with tempfile.TemporaryDirectory(prefix="crm-preview-qa-") as td:
            directory = Path(td)
            driver = directory / "driver.html"
            source = DRIVER.replace("__WIDTH__", str(width))
            source = source.replace("__TARGET__", json.dumps(TARGET.as_uri(), ensure_ascii=True))
            driver.write_text(source, encoding="utf-8")
            command = [
                browser, "--headless", "--no-sandbox", "--disable-gpu",
                "--disable-background-networking", "--disable-extensions",
                "--disable-dev-shm-usage", "--no-first-run",
                "--allow-file-access-from-files", "--user-data-dir=" + str(directory / "profile"),
                "--virtual-time-budget=3800", "--window-size=1500,1100",
                "--dump-dom", driver.as_uri(),
            ]
            try:
                run = subprocess.run(command, capture_output=True, text=True, timeout=45,
                                     check=False)
            except subprocess.TimeoutExpired:
                print(f"FAIL {width}px: Chromium não concluiu", file=sys.stderr)
                return 1
            match = re.search(
                r'<output\b[^>]*data-crm-preview="(PASS|FAIL)"[^>]*>(.*?)</output>',
                run.stdout, flags=re.DOTALL,
            )
            if not match or run.returncode != 0 or match.group(1) != "PASS":
                reason = html.unescape(re.sub("<[^>]*>", "", match.group(2))) if match else (
                    run.stderr[-900:] or "ausência de resultado executado"
                )
                print(f"FAIL {width}px: {reason}", file=sys.stderr)
                return 1
            print(f"PASS {width}px: interação Chromium real executada")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
