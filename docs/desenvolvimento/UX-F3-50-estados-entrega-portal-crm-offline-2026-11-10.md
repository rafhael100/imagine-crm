# CRM UX F3-50 — Estados visuais da entrega Portal → CRM no protótipo offline

O protótipo estático de nove telas do Imagine CRM recebe, dentro da página **Leads fictícios**, uma demonstração interativa sobre o estado técnico de entrega entre o Portal Imagine e o CRM. A interface ajuda a revisar o comportamento esperado de reenvio e recibo sem misturar estágios comerciais de um lead com o resultado de transporte.

## Fluxos demonstrados (dados 100% fictícios)

| Botão | Mensagem principal | Restrição |
|---|---|---|
| Pendente | Pendente de revisão | Nenhum recibo do CRM foi confirmado |
| Em processamento | Confirmação ausente | Não presumir gravação só porque existe tentativa |
| Resultado incerto | Reenvio bloqueado | Não reenviar automaticamente; reconciliar com fonte confiável |
| Recibo de laboratório | Recibo sintético compatível | HMAC do banco descartável não comprova CRM real nem autoriza ACK |

Os botões são `type=button` com `aria-pressed`, dentro de um `role=group`. A descrição usa `role=status`, `aria-live=polite` e `aria-atomic=true`. Os quatro estados são dados literais fixos em JavaScript e são construídos com `textContent`/elementos DOM, nunca com `innerHTML`. Não existe formulário, campo pessoal, data, cliente, rede, storage, sessão, OAuth ou API; o CSP offline permanece com `connect-src 'none'` e `form-action 'none'`.

## Implementação e QA

- `prototypes/s01-prot-01/index.html` inclui os quatro botões e o preview local, sem alterar o número de telas, a tabela de quatro leads ou os filtros existentes.
- `tests/Unit/OfflinePortalCrmDeliveryDemoTest.php` inspeciona marcação acessível, exclusão de formulários/contatos, mensagens de incerteza, ausência de rede e limite de tamanho do protótipo.
- `scripts/ci/check-offline-prototype-browser.php` exercita os quatro cenários em Chromium, valida que somente um botão está pressionado por vez e que o recibo de laboratório não é apresentado como sucesso real.
- `scripts/ci/check-offline-prototype-320.php` exercita os mesmos cenários em **320 CSS pixels reais**, incluindo ausência de overflow horizontal e de captação de dados.
- Workflow `ci-f3-50-delivery-prototype-browser.yml` usa PHP 8.4/Laravel/PHPUnit e Chromium real. Se o navegador não estiver disponível, a validação **falha**, sem simular resultado positivo.

## Limites e relação com a engenharia

A demonstração representa os estados de laboratório F119/F122 do Portal e F3-40 a F3-49 do CRM, **sem conectá-los à interface**. Não autoriza efetuar atendimento, confirmar lead, consultar CRM, deduplicar, enfileirar ou processar conteúdo. O receptor operacional com identidade de serviço, autorização por empresa, consentimento, armazenamento, recibo e staging depende da revisão SEG/DBA/LGPD. A F70 do Comercial permanece fechada.

Não houve deploy, alteração de `main`, DNS, VPS, produção, dados pessoais ou custo externo.
