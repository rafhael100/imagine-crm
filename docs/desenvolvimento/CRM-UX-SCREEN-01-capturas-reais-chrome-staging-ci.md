# CRM-UX-SCREEN-01 — Capturas de tela reais do Laravel em 360, 768 e 1440 px

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-AUTO-ACCEPT-UX**

## Por que esta etapa é útil

A [PR #590](https://github.com/rafhael100/imagine-crm/pull/590) preservou byte a byte a logomarca original Imagine e criou testes reais de entrega HTTP da imagem pelo Nginx. Porém, conferir um hash PNG **não demonstra que as páginas funcionem visualmente** em telefone, tablet e desktop. Esta entrega captura **PNG de navegador real** do candidato de Laravel, permitindo análise visual independente sem abrir a aplicação na VPS.

## Implementação

- `scripts/ci/rehearse-crm-ux-screen-captures.sh`: executa exclusivamente em GitHub Actions com `CRM_UX_SCREEN_LAB=synthetic-only`, verifica health do Laravel em `http://127.0.0.1:18080/up` e encontra **Chrome/Chromium real**, falhando se ausente.
- Exige 200 da aplicação, presença do `logo-imagine.png` e ausência de `EXEMPLO_OMEGA` do tenant B e de formulários. Na versão persistida, exige que o indicador de origem fictícia do PostgreSQL16 esteja visível no HTML.
- Navega **somente em três caminhos locais hardcoded**: página comercial `/?lang=pt`, Kanban estático `/demo?lang=pt` e Kanban de banco fictício `/demo/live?lang=pt`. Não aceita URL externa ou parametrização de cliente.
- Para cada caminho gera três capturas: **360×900 (celular), 768×1024 (tablet), 1440×900 (desktop)**, resultando em nove imagens PNG em pasta temporária do próprio runner. O script confere a assinatura PNG, `IHDR`, dimensões exatas e faixa de tamanho, gerando `manifest.tsv` com SHA-256 de cada captura. Não usa OCR e não afirma revisão visual automática.
- O próprio script também gera **`index.html` offline, sem JavaScript/formulários ou recursos externos**, com grade responsiva das nove capturas, indicação de viewport e links locais para ampliar. Assim o responsável UX pode abrir o arquivo após extrair o ZIP do artifact, inspecionar lado a lado e confrontar com o `manifest.tsv` de SHA-256.
- Os screenshots são publicados **como artifact do GitHub Actions** `crm-private-ux-pt-360-768-1440`, com validade de **cinco dias**, `if-no-files-found: error` e versão do uploader imutavelmente pinada. O runner e os contêineres com fixtures são descartados após a execução; nenhuma imagem contém nome real de cliente ou informação financeira.
- Workflow mantém preflight, Host local, noindex, páginas PT/EN/ES, Kanban PostgreSQL com duas empresas fictícias e RLS, restauração completa em PG16 isolado, ataques HTTP, Laravel/F0, Asaas/Sites/Agents.

## Critérios de aceite visual ainda pendentes

Essas capturas são material para inspeção manual do layout, não prova de WCAG AA. Precisam de revisão por responsável UX: consistência com screenshots canônicos do Portal Node, integridade da logomarca, overflow horizontal real, contraste, zoom 200%, teclado/VoiceOver/TalkBack, navegabilidade e estados vazios/erro/carregamento. A página de cadastro/login e o painel operacional autenticado ainda não estão liberados para clientes. **Não fechar a UX #416 com base apenas em imagens ou testes unitários.**

Gates ainda abertos: [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [CRM lançamento #557](https://github.com/rafhael100/imagine-crm/issues/557). Nenhum deploy, ativação de cobrança ou merge automático está autorizado.
