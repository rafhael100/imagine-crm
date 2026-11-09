# CRM Imagine — F3-06 — Mínimo sintético para formulário de Sites Imagine

**Data:** 08/11/2026. **Estado:** contrato PHP puro de laboratório, com testes sintéticos na DEV. [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35). Referências: [IN-125](../planejamento/IN-125-gestao-inteligente-formularios.md), [IN-124](../planejamento/IN-124-integracao-inteligente-formularios-leads.md), [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md).

## Objetivo

Apenas para o canal **formulários de Sites/Landing Pages Imagine**, analisar se um rascunho sintético contém **nome + ao menos um contato plausível** (e-mail ou telefone), sem tornar isso uma regra universal de criação de leads no CRM.

`ImagineSiteFormMinimumInspector` recebe `LeadDraftFields` já construído e devolve `ImagineSiteFormMinimumAssessment` composto somente por booleanos. A presença de outro campo fornecido mas inválido também impede atender ao mínimo.

- E-mail: triagem sintática por `filter_var`; não comprova existência ou titularidade.
- Telefone: heurística **provisória de laboratório**, caracteres comuns e 8–15 dígitos; **não é padrão internacional ou validação final homologada**.
- O método `meetsStructuralMinimum()` informa completude estrutural, **não** autorização, consentimento, antispam, sucesso de recebimento ou criação de lead.
- `canDeliver()` devolve **sempre falso**. Nada neste lote retém ou transmite contatos, integra o CRM, confirma vínculo nem cria registro.
- Consentimento para marketing não é automático nem campo obrigatório universal. Transparência, finalidade, base legal, aviso e aprovação jurídica quando cabíveis dependem de COM-14/COM-15, LGPD e do cliente controlador.

## Limites

Sem formulário público, HTTPS, API principal/contingência, fila, armazenamento, leitura de leads reais, integração CRM, permissões, conta, database, job, migrations, autorização `ALLOW`, deploy, VPS ou produção.

Critérios técnicos finais de telefones, formatos e aceitação internacional são pendências de Produto/Privacidade/Integrações. A triagem em laboratório **não deve ser usada diretamente como autorização de captura/entrega**. CORE/SEG/DBA, Issue #27 e demais gates permanecem abertos.
