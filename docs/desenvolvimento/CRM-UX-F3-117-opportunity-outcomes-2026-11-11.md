# CRM UX F3-117 — Painel fictício de resultados e motivos de perda

**Data:** 11/11/2026. **Escopo:** HTML offline, PHP puro e Chromium em DEV, sem dados de clientes.

## Entrega

A partir do Core `SyntheticOpportunityOutcomeInspector` integrado em F3-116, a página `prototypes/f3-117-opportunity-outcomes/index.html` apresenta quatro cenários locais: resultados variados, somente perdas, ganhos declarados e nenhum resultado.

O painel mostra contagens de alegações de **aberta, ganha e perdida** e de cada um dos cinco motivos de perda ilustrativos. Não expõe registro original, razão em texto livre ou identidade. Os estados não são transições: os botões apenas alternam conjuntos imutáveis e fictícios.

O hub ganha um **oitavo cartão** navegável, preservando os sete anteriores.

## Evidência executável

- `OfflineOpportunityOutcomesParityTest`: extrai as quatro listas JSON do próprio HTML, compara contagens e razões **com o Core PHP real** e exige `recognized_wins=0`, falta de verificação e autorização sempre negada. Testa ausência de campo pessoal, rede, input, HTML injetável ou persistência.
- `check-offline-opportunity-outcomes-browser.php`: executa Chromium verdadeiro em 1100 px e 320 px, troca os quatro cenários, verifica quantidades e motivos, seleção única por `aria-pressed`, foco por teclado, ausência de overflow e negação de todas as ações.
- Workflow F0 passa a exigir o browser smoke; testes PHP/Chromium do hub são atualizados de sete para oito destinos.

## Limites

**Motivo estruturado é requisito ilustrativo, não política de nenhuma empresa.** Nem a palavra `won` nem o contador de ganhos alegados equivale a venda comprovada, receita, comissão, meta ou evento auditado.

Não há API, SQL, chaves, leads reais, clientes, autenticação, alteração de etapas, automação, cobranças, custos, VPS, `main` ou produção. Uma versão operacional precisa de permissão de empresa, campos obrigatórios, motivo configurável, concorrência, auditoria e liberação independente VEN-P03 / CORE/SEG/DBA/LGPD.
