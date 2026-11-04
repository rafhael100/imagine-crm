# CRM-DEC — Aprovação de CORE07-D01, D02 e D03 como base documental

**Data:** 04/11/2026.  
**Manifestação expressa do responsável:** “Aprovo CORE07-D01, D02 e D03 como base documental do CRM Imagine. Registre no GitHub, mantenha CORE07-D04 a D10 e os demais bloqueios técnicos abertos e prossiga para o CORE-P0-08, sem iniciar programação.”  
**Estado:** **CORE07-D01–D03 APROVADAS COMO BASE DOCUMENTAL**; **CORE-P0-07 PARCIAL/ABERTO**, não homologado para implementação.  
**Documento deliberado:** [CORE-P0-07 — convites, assentos, equipes e desligamento v0.1](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md).

## 1. Escopo exato do aceite

1. **CORE07-D01 — Convites por tenant e adesão.** Aceito o modelo lógico de convite seguro de **7 dias**, emitido por administrador da organização autorizado, com destinatário/perfil inicial, criação de conta ou aproveitamento de identidade global existente, verificação e adesão efetiva somente após validação do vínculo, direitos, MFA/segurança e elegibilidade. Convite pendente não equivale a membership ativa. Cancelamento, reenvio e aceite idempotente são diretrizes; estados físicos, token, concorrência e contagem de vaga pendente ainda abertos.
2. **CORE07-D02 — Assentos por organização e membership.** Aceita a separação de identidade global e cota/assento autenticado por organização/tenant e plano, sem compartilhar quotas ou permissões entre organizações do mesmo CNPJ. **Básico possui um único usuário autenticado por organização, Administrador da Empresa com MFA**. Profissionais agendáveis, equipes, agentes de IA e identidades de serviço não criam assentos humanos adicionais por equiparação. Sem criação de valores, cobranças ou reservas de vaga não aprovados.
3. **CORE07-D03 — Equipes, alcance comercial e desligamento proporcional.** Aceita a participação em múltiplas equipes da própria organização e os escopos próprio/equipe/empresa, sujeitos à matriz real de direitos. Transferência de leads/oportunidades preserva responsáveis e históricos; desligamento revoga acesso e sessões **do tenant específico**, sem eliminar a identidade global ou memberships legítimas de outras empresas, nem apagar evidências/fatos oficiais. Redistribuição e continuidade de trabalhos pertencem aos módulos proprietários; jobs devem revalidar autoridade segundo CORE-P0-03.

## 2. Pendências que permanecem expressamente abertas

| ID | Objeto não aprovado pelo presente aceite |
|---|---|
| **CORE07-D04** | Token, mecanismo/TTL físico do convite, reenvio/cancelamento, verificação, concorrência e idempotência |
| **CORE07-D05** | Regra de reserva/ocupação de assentos por convite pendente, contagem por estado, contratação e limite de plano |
| **CORE07-D06** | Recuperação/substituição/transferência do administrador principal e último administrador (inclusive Básico) |
| **CORE07-D07** | Matriz detalhada de equipes, gestores, unidades, distribuição, reatribuições e critérios dos módulos VEN/ATD/AGD |
| **CORE07-D08** | Revogação técnica de sessões, APIs, caches, filas/jobs, anexos, exportações e automações após desligamento |
| **CORE07-D09** | Registros e regimes de auditoria de convite, papéis, equipes, transferências, desligamento e reativação |
| **CORE07-D10** | Estados/invariantes executáveis, casos negativos e pacote final de homologação e rastreabilidade |

**Outros bloqueios preservados:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE06-D04..D10; CORE-P0-08 a 10; INT P0-OWN/SEC/CON/ACC; IN-168 ARQ/SEG/AGT; G01–G06 e fechamento global de planejamento. Aceite documental não significa P0 encerrado ou política executável final.

## 3. Continuidade permitida

Somente **planejar CORE-P0-08 — CRM Básico, organizações múltiplas, vínculos elegíveis de Sites e trial por CNPJ**, preservando a separação entre identidade global/tenants, benefício de cortesia e assinatura paga, bem como decisões comerciais da [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) e [IN-117](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md). As transições financeiras precisas e contratos físicos continuam em revisão.

**Não iniciar programação, testes executados, instalação, provisionamento, cobrança, criação/envio de convites reais, migração, contratação, alteração de produção ou despesas.**
