# CRM-DEC — Aprovação CORE08-D01, D02 e D03: Básico público/cortesia, organizações e trial

**Data:** 04/11/2026.  
**Manifestação expressa do responsável:** “Aprovo CORE08-D01, D02 e D03 como base documental do CRM Imagine. Registre no GitHub, mantenha CORE08-D04 a D10 e os demais bloqueios técnicos abertos e prossiga para o CORE-P0-09, sem iniciar programação.”  
**Situação:** **CORE08-D01–D03 APROVADAS COMO BASE DOCUMENTAL**, **CORE-P0-08 PARCIAL/ABERTO**. Não corresponde a homologação de schema, concessão de benefício, preços finais ou autorização de desenvolvimento.  
**Documento deliberado:** [CORE-P0-08 v0.1](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md).

## 1. Base documental aprovada

1. **CORE08-D01 — Separação lógico-contratual entre identidade, CNPJ, organização CRM, assinatura paga, Básico público, cortesia e trial.** Uma organização CRM é tenant próprio com dados, plano, quotas, memberships e direitos segregados; uma identidade global pode vincular-se a várias organizações de modo legítimo. O plano **Básico é público e contratável**, com valor de planejamento sujeito à revisão final; a **cortesia é benefício condicional por contrato elegível**, e não novo plano nem gratuidade indiscriminada. A regra **um trial de 14 dias por CNPJ**, sem cartão, permanece separada: múltiplos sites ou organizações do mesmo CNPJ não criam novos trials. Gratuidade administrativa de qualquer plano por Administrador Geral competente é uma quarta base de concessão **distinta**, ainda com GRAT-P pendentes.
2. **CORE08-D02 — Elegibilidade, vínculo Site↔tenant e cortesia não cumulativa.** Para contrato Site/landing elegível o cliente seleciona expressamente **organização CRM existente autorizada** ou **nova organização segregada**, inclusive sob mesmo CNPJ; cada organização pode receber no máximo **uma cortesia Básico efetiva**, sustentada por pelo menos um Site elegível. Dois sites vinculados à mesma organização não duplicam limites, licenças, créditos ou cortesias; dois sites elegíveis vinculados a duas organizações distintas podem sustentar cortesia em ambas. Mudança de CRM vinculado a Site requer **chamado** e nova integração técnica gratuita da Imagine, **não** migração automática de históricos; importação/exportação de dados pela própria organização segue IN-118. Serviços/campanhas e contrato determinam a elegibilidade, sem revogação retroativa indevida. Páginas estáticas sem CRM só poderão publicar sem formulários captadores, conforme IN127-R06.
3. **CORE08-D03 — Ciclos independentes e precedência de direitos já aprovados.** Preservar a separação entre trial, CRM pago, cortesia, gratuidade administrativa, inadimplência do site, suspensão, cancelamento, retenção e reativação. Assinatura CRM paga **não é bloqueada** por dívida/cancelamento de site; se outro Site ativo elegível ainda sustenta a mesma organização, a cortesia é preservada. **Cancelamento comum do último Site elegível:** 30 dias de transição com funcionamento normal e avisos dia 0/15/25/29/30. **Cancelamento formal por inadimplência após suspensão do último Site elegível:** 30 dias com operação restrita, preservando portabilidade e possibilidade de contratação paga expressa; a suspensão anterior por atraso **não inicia** esses 30 dias. Nenhuma conversão paga/cobrança automática por perda de benefício. Retenção do trial expirado de 60 dias e retenção geral de 120 dias **após cancelamento formal da conta CRM**, não após cancelamento de Site ou mera suspensão, continuam regimes diferentes sujeitos a políticas/obrigações aplicáveis.

## 2. CORE08-D04 a D10 — permanecem ABERTAS e NÃO HOMOLOGADAS

| ID | Pendência |
|---|---|
| **CORE08-D04** | Entidades e contratos físicos versão/tenant/CNPJ/Site/CRM, elegibilidade, assinatura, gratuidade, trial, estados e idempotência |
| **CORE08-D05** | Precedência concreta de benefícios simultâneos e adicionais, disputas, promoções, gratuidade administrativa e estados de suspensão |
| **CORE08-D06** | Prova contratual, antifraude e titularidade de vínculo, concorrência, criação/ativação idempotentes e prevenção de contas fantasmas |
| **CORE08-D07** | Máquina física de estados, clocks/fusos, causalidade de eventos, confirmação financeira, restauração e reconciliação |
| **CORE08-D08** | Direitos na restrição, portabilidade, MFA, suporte, avisos e mensagens, LGPD |
| **CORE08-D09** | Cotas/assentos e preços definitivos, planos pagos, descontos, downgrade e armazenamento/IA |
| **CORE08-D10** | Casos de homologação, competências, contratos, rastreabilidade integral, INT/FIN/Portal e gates G01–G06 |

**Bloqueios anteriores/transversais abertos:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE06-D04..D10; CORE07-D04..D10; CORE-P0-09 e 10; CRM-REV02-D01..12/P01..12, GRAT-P01..08, INT-P0-OWN/SEC/CON/ACC, IN-168 ARQ-01/02/03, SEG-01/02, AGT-01, G01..G06, revisão comercial e aprovação final global. Nenhuma pendência é encerrada por aceitação apenas conceitual.

## 3. Próximo passo

**Somente elaboração/revisão documental do CORE-P0-09 — cofre de segredos, credenciais e identidades de serviços e agentes**, de acordo com IN-151/166/167/168, CORE-P0-01/02/03 e INT-02/P0-SEC. **Sem programação, Codex, testes executáveis, credenciais reais, provisionamento, contratação, alteração de ambiente/produção ou despesa.**
