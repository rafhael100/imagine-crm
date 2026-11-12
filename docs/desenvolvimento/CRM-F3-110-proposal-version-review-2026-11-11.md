# CRM F3-110 — Leitura ilustrativa de versões de proposta (sem autoridade)

**Data:** 11/11/2026 · **Escopo:** PHP puro em DEV · **Rastreabilidade:** CRM-REV-06, VEN-R11/R14/R16/R17/R18 e VEN-P05/P06

## Capacidade

`SyntheticProposalVersionReview` recebe de 0 a 8 versões **inventadas**, sequenciais de 1 até N, cada uma composta por `version` (inteiro), `status` (código entre `draft`, `sent`, `accepted`, `declined`, `expired`) e `viewed` (booleano). A ordem pode ser validada com uma lista estrita: versões repetidas, fora de sequência, extras ou com campo pessoal são rejeitadas de forma genérica.

O relatório retorna somente contagens por status, a última versão e seu **estado declarado e não verificado**, contagem de versões antigas que alegam aceite e a quantidade de visualizações não aceitas. Não identifica cliente, destinatário, vendedor, usuário, produto, valor, canal, assinatura, documento ou horário.

### Invariantes importantes

- Uma versão anterior que alegue `accepted` **não concede aceite à versão posterior**;
- `viewed=true` **não representa aceite**;
- Mesmo uma versão atual com `status='accepted'` **não comprova identidade, prova de aceite, assinatura eletrônica ou pagamento**;
- `prior_acceptance_applies_to_latest=false`, `viewed_equals_acceptance=false`, `authority_verified=false`; enviar, aceitar, assinar, cobrar e persistir estão sempre proibidos.

## Limites

Os códigos e o máximo de oito versões são **convenções do laboratório**, não estados de produção ou limites de plano já homologados. Não é implementada transição, expiração temporal, revogação de link, autenticação, alçada de desconto, envio, emissão de PDF ou aceite.

Testes cobrem aceite fictício de v1 vs rascunho v2, visualização independente de aceite, lista vazia, até oito versões, estados inválidos, duplicações, injeção de tenant e esquema de saída fechado. Nenhuma migration, banco, usuário real, API, scheduler, `main`, VPS, custo ou deploy. CORE/SEG/DBA/LGPD e VEN-P05/P06 continuam NO-GO para a proposta real.
