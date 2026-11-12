# CRM OPS-59 — Kanban fictício: drag seguro, ordenação e paridade CSV (12/11/2026)

**Status:** evidência operacional de desenvolvimento, não homologação.  
**Base:** `dev/fast-track-crm-2026-11-04`; **HEAD após F3-191 (snapshot):** `9f053226988ee02a22c20b871cf1f0ac63aaa510`. Reconsultar a HEAD no GitHub; não assumir que continuará fixa.  
**`main` verificada:** `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`, `compare` idêntico em 12/11/2026. Não foi alterada por estes incrementos.

## Entregas incorporadas somente na DEV

| Entrega, PR e merge | Incremento revisado e testado | Quatro workflows `success` na SHA final (Laravel F0 / PostgreSQL-RLS / Sites / AIA) |
|---|---|---|
| [UX F3-188 #409](https://github.com/rafhael100/imagine-crm/pull/409), merge `22f2a0b99550862ca696b1f895045375563341d4` | Drop de cartão ou seleção em grupo rejeita `data-drop-stage` adulterado (`NaN`, fora de 0–3, decimal ou vazio) **antes** da mutação; Chromium desktop e 320 px não detecta alteração nem histórico | [F0 #43298317488](https://github.com/rafhael100/imagine-crm/actions/runs/43298317488), [PG #43298317510](https://github.com/rafhael100/imagine-crm/actions/runs/43298317510), [Sites #43298317392](https://github.com/rafhael100/imagine-crm/actions/runs/43298317392), [AIA #43298317451](https://github.com/rafhael100/imagine-crm/actions/runs/43298317451) |
| [UX F3-189 #410](https://github.com/rafhael100/imagine-crm/pull/410), merge `a81bb736528dd7598ff5b1ba451ca0c31e3daa50` | Um único destino de drag é destacado de cada vez, mesmo que `dragleave` seja omitido; destino inválido não ganha destaque; cancelamento limpa estado sem ação | [F0 #43298955336](https://github.com/rafhael100/imagine-crm/actions/runs/43298955336), [PG #43298955415](https://github.com/rafhael100/imagine-crm/actions/runs/43298955415), [Sites #43298955275](https://github.com/rafhael100/imagine-crm/actions/runs/43298955275), [AIA #43298955361](https://github.com/rafhael100/imagine-crm/actions/runs/43298955361) |
| [UX F3-190 #411](https://github.com/rafhael100/imagine-crm/pull/411), merge `b00dff6e8834655faa83c51dd48ff9cff66fbfca` | Ordenação de cartões por **maior valor fictício**, além de pontuação e ordem original. Critério determinístico por ID; só apresenta, não modifica dados ou ações | [F0 #43299461906](https://github.com/rafhael100/imagine-crm/actions/runs/43299461906), [PG #43299462043](https://github.com/rafhael100/imagine-crm/actions/runs/43299462043), [Sites #43299462220](https://github.com/rafhael100/imagine-crm/actions/runs/43299462220), [AIA #43299462036](https://github.com/rafhael100/imagine-crm/actions/runs/43299462036) |
| [UX F3-191 #412](https://github.com/rafhael100/imagine-crm/pull/412), merge `9f053226988ee02a22c20b871cf1f0ac63aaa510` | Prévia CSV **não exportável** segue a ordem exata dos cartões efetivamente renderizados (etapa, filtros, ordenação e adições); testes DOM/CSV em 1100 e 320 px | [F0 #43300286839](https://github.com/rafhael100/imagine-crm/actions/runs/43300286839), [PG #43300286770](https://github.com/rafhael100/imagine-crm/actions/runs/43300286770), [Sites #43300286878](https://github.com/rafhael100/imagine-crm/actions/runs/43300286878), [AIA #43300286838](https://github.com/rafhael100/imagine-crm/actions/runs/43300286838) |

## Regressão identificada e corrigida

A **primeira SHA** da #412 falhou no workflow [F0 #43299977119](https://github.com/rafhael100/imagine-crm/actions/runs/43299977119): o teste legado de navegador `check-offline-kanban-browser.php` pressupunha que `F09` e `F12` aparecessem sempre nas últimas linhas do CSV. Após alinhar a prévia à ordem **visível por etapa**, a premissa deixou de ser válida. Corrigiu-se o teste para comparar IDs e ordem **CSV ↔ DOM atual**, mantendo validação de fixtures inventadas, 12 cartões máximos e reset. A nova SHA `d68bff395bccdebfbc25aeb538db822e07111953` passou novamente nos quatro workflows acima, incluindo o Chromium real; **não** atribuir o sucesso da SHA final à execução falha intermediária.

## Fronteira e próximos critérios

Todas as alterações estão restritas ao protótipo Kanban HTML, testes de navegador e PHPUnit estático; não criam rotas, clientes, exports CSV reais, autorização `ALLOW`, conexão persistente, dados pessoais, worker, serviços, alterações `main`, VPS ou deploy. O protótipo é uma simulação limitada a doze oportunidades inteiramente fictícias.

**NO-GO mantido:** [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/SEG/DBA/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [dossiê OPS-52](CRM-OPS-52-dossie-revisao-P0-SEG-DBA-CORE-LGPD-2026-11-12.md). CI não é parecer independente, autorização de segurança, conformidade LGPD ou homologação de staging.

Próximos recortes seguros: continuar QA de acessibilidade e coerência de interfaces offline; definir/revisar com responsáveis controles reais de identidade multiempresa, políticas por ação, RLS de produção, backups, privacidade, dados e integrações, sem confundir mocks com funcionalidades lançadas.
