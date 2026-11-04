# CRM-DEC — Aceite expresso CORE03-D01 a D03: ponte de autoridade nas operações assíncronas

**Data:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE03-D01, D02 e D03 como base documental do CRM Imagine. Registre no GitHub, mantenha CORE03-D04 a D10 e os demais bloqueios abertos e prossiga para o CORE-P0-04, sem iniciar programação.”  
**Estado:** **CORE03-D01–D03 APROVADAS COMO BASE DOCUMENTAL**. **CORE-P0-03 permanece PARCIAL/ABERTO**. Documento não homologa contratos físicos, políticas executáveis, credenciais, alçadas, fornecedores nem testes; não autoriza Codex.

## 1. Conteúdo efetivamente aprovado

1. **CORE03-D01 — Envelope lógico de identidade e contexto.** Aceita-se como modelo de referência para interoperabilidade a proposta descrita na [CORE-P0-03 v0.1](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md): organização/tenant e ambiente, identidade causal humana/técnica, serviço executor, ação/recurso e direitos a revalidar, proveniência, versões, correlação, causalidade, idempotência, tentativa e estado. O envelope **não é autorização autossuficiente** nem implica aprovação de payload, nomes de campos, transporte ou esquema físico.
2. **CORE03-D02 — Cadeia de responsabilidade.** Aceita-se como diretriz a sequência: origem legitimada → persistência durável quando exigida → fila/entrega rastreável → revalidação de autorização no destino → comando tipado ao módulo competente → confirmação oficial pelo proprietário → reconciliação/auditoria. Êxito técnico HTTP, ACK, evento, webhook, workflow n8n ou gravação intermediária não é, por si, confirmação final de negócio. Não cria motores centrais paralelos.
3. **CORE03-D03 — Classes de operação e revogação.** Aprovado como referência distinguir: **A** atos humanos diferidos, **B** processos internos obrigatórios, **C** automações comerciais do cliente, **D** ingresso externo, **E** operações técnicas Imagine, **F** callbacks/resultados externos. Credenciais e permissões mutáveis são revalidadas antes de novos efeitos conforme a classe e o proprietário; desligamento de funcionário **não invalida automaticamente fatos legítimos nem obrigações sistêmicas independentes da sessão humana**. Falha segura, proibição de contaminação entre tenants e tratamento inconclusivo permanecem obrigatórios.

## 2. Decisões anteriores expressamente preservadas

- [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): uma organização CRM = um tenant lógico, identidade global, memberships por tenant, isolamento Laravel e PostgreSQL RLS.
- [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md): autorização cumulativa, catálogo semântico de ações e matriz conceitual, **sem concessão de novos direitos**.
- [IN-127-R01–R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): recepção durável T0; 72 + 360 = 432 horas contínuas de entrega elegível de leads, prazos, expurgo, metadados técnicos mínimos, reconciliação e ausência de captação sem CRM oficialmente vinculado; **não aplicar 432h às filas internas do CRM**.
- [Motor próprio do CRM e uso interno condicionado do n8n](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md), [CT-03/04 INT](integracoes/P0-CON-CT03-CT04-comandos-resultados-transicoes.md), Central de Eventos/Filas/Aprovações/Auditoria: sem delegação generalizada a n8n, IA ou provedor.

## 3. Dependências técnicas que CONTINUAM ABERTAS

| ID | Assunto não aprovado pelo presente aceite | Competência |
|---|---|---|
| **CORE03-D04** | Schemas/versões, erros e mecanismos concretos de autenticação e transporte de contexto | CORE/SEG/INT |
| **CORE03-D05** | Delegação, expiração e revalidação exatas por classe A–F, plano e sessão | CORE-P0-02/04/07/08, FIN/CON |
| **CORE03-D06** | Contexto RLS/tenant na conexão, workers e transações, reset e cache | CORE-P0-01, SEG-01/02 |
| **CORE03-D07** | Tecnologia e contratos de filas duráveis, outbox/inbox, concorrência, retry, DLQ, replay e retenção por domínio | Eventos/Filas/AUT/INT |
| **CORE03-D08** | Envelopes oficiais por Sites, WhatsApp, Finanças, Agenda, IA e outras integrações | Módulos proprietários, INT |
| **CORE03-D09** | Limites, quotas, auditoria/retencão de jobs, alertas e segredo de serviço | CORE-P0-06/09, IN-159/162 |
| **CORE03-D10** | Autorização efetiva por operação e critérios de aceite/homologação | CORE-P0-10, INT P0 e G01–G06 |

**Também permanecem abertos** CORE01-D04..D07, CORE02-D04..D10, CORE-P0-04..10, INT-P0-OWN/SEC/CON, IN-168 (ARQ-01/02/03, SEG-01/02, AGT-01) e G01..G06. A aprovação de uma base não comprova prontidão para desenvolvimento.

## 4. Continuidade e restrição

Próxima revisão expressamente autorizada: **CORE-P0-04 — autenticação e recuperação por papel, MFA e sessões**, na forma de nova proposta sujeita a aceite. **Não iniciar programação, testes, contas reais, provisionamento, implantação, cobrança ou contratação**. A liberação de desenvolvimento depende da reconciliação e aprovação final global do planejamento.
