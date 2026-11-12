# CRM F3-195 — prévia sintética de tentativas de envio versus possíveis duplicados de pessoas

**Data:** 12/11/2026. **Base:** [issue #35](https://github.com/rafhael100/imagine-crm/issues/35); [CRM-REV-03 DAT-R08/DAT-R10](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md); IN-148/149. **Escopo:** Core PHP puro, sem persistência, operações e identidade real.

## Problema delimitado
1. Repetição de **uma mesma submissão lógica** por retry/failover/reconciliação: a identidade da submissão deve continuar estável, mas essa prévia NÃO comprova a entrega no CRM, a associação correta nem a idempotência real.
2. **Submissões lógicas distintas** podem ter sinais coincidentes de e-mail/telefone. Isso exige revisão de possível duplicidade de pessoa; não autoriza mesclagem, sobrescrita, exclusão ou dedução de consentimento.

## Entrega
`App\Core\Leads\SyntheticLeadDuplicateEvidenceInspector` recebe apenas `leadCount` inteiro de 0 a 20 e até 64 comparações posicionais `left`/`right`. Cada comparação recebe três relações enumeradas `same`/`different`/`unavailable` — submissão, e-mail e telefone —, **não os valores dos contatos**, IDs reais ou hashes.

Classifica por par somente em quatro motivos permitidos:
- `RETRY_CLAIM_UNVERIFIED`: mesmo envio declarado sem contradição de contato.
- `CONFLICTING_RETRY_CLAIM`: mesmo envio declarado, mas ao menos um contato diverge.
- `POSSIBLE_PERSON_MATCH`: envio distinto ou desconhecido, mas e-mail ou telefone coincide **segundo alegação**.
- `INSUFFICIENT_EVIDENCE`: sinais insuficientes para revisar como colisão.

Todos os resultados usam **índices locais e contagens**, nenhum identificador/PII ou confirmação de pessoa, cliente, tenant, identidade de envio ou vínculo. Mesmo sinais coincidentes em ambos os contatos não permitem fusão. Saída contém `authorized=false`, `can_deduplicate=false`, `can_reconcile=false`, `can_merge=false`, `can_acknowledge_delivery=false`, `can_update_lead=false`, `can_persist=false`.

## Invariantes comprovados por testes
- Contratos de listas e chaves estritos; nenhuma chave extra com PII.
- Cada par é ordenado por posição; pares repetidos, índices fora de intervalo e relações desconhecidas falham imediatamente com erro genérico.
- Restrições 0..20 leads / até 64 pares; resultados determinísticos e sem mutações.
- Mesmo envio com contatos divergentes resulta em alerta específico (não ACK).
- Mesmo contato em envio diferente indica possível pessoa coincidente, não autorização.
- `SyntheticCommercialCapabilityDenyMatrixTest` integra a **40ª** prévia, verifica zero capacidades operacionais e mantém `routes/web.php` sem registro público desse módulo.
- Os quatro workflows obrigatórios (F0, PostgreSQL/RLS, Sites e Agentes) devem passar na **HEAD exata** antes de qualquer merge na DEV.

## Fora do escopo / NO-GO
Não consultar e-mails/telefones reais; sem normalização/criptografia de PII, fuzzy match, prova de propriedade, exclusão, fusão, banco, queue, API, controller, sessão, grants ou dados de clientes. Nada desta prévia transforma `HTTP 202` em entrega confirmada. Os pareceres [#27](https://github.com/rafhael100/imagine-crm/issues/27) e [#259](https://github.com/rafhael100/imagine-crm/issues/259) continuam pendentes. `main`/VPS/produção intocadas.
