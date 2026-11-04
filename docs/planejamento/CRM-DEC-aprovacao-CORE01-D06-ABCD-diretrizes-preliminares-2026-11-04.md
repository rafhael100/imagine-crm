# CRM-DEC — Aceite expresso preliminar de CORE01-D06-A, B, C e D — ciclo organizacional e comercial

**Data:** 04/11/2026.

**Manifestação literal do responsável:** “Aprovo CORE01-D06-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE01-D04, D05, D06 e D07 abertas, preserve SEG-01, SEG-02 e todos os demais bloqueios técnicos. Prossiga com o planejamento documental de CORE01-D07, sem programação nem execução de testes.”

**Estado:** **CORE01-D06-A/B/C/D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**; **CORE01-D06 PERMANECE ABERTA** até os contratos e dependências com CORE08, FIN/CON, Portal/Sites, INT e Segurança. Não se homologou arquitetura física, modelo de dados, regras de titularidade concretas, novos entitlements, contratos financeiros, implementação nem evidência de teste.

**Fonte submetida:** [CORE01-D06 v0.1 — ciclo de organizações, titularidade e vínculos comerciais](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-organizacoes-vinculos-comerciais-proposta-v0.1.md). [Material técnico complementar](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-tenants-vinculos-comerciais-proposta-v0.1.md) aprofunda cenários, sem redefinir os códigos A–D. **Precedência:** os rótulos A/B/C/D correspondem à proposta canônica efetivamente apresentada ao responsável; o anexo histórico usa classificações temáticas diferentes, que não constituem novas deliberações. Textos antigos que digam “NÃO APROVADAS” foram superados somente no alcance do aceite preliminar.

## 1. Deliberações aceitas segundo a pauta canônica apresentada

1. **CORE01-D06-A — fontes oficiais e vínculos explícitos:** distinguir identidade/representação/CNPJ, organização CRM (tenant), contrato de Site elegível, seleção de destino Site↔CRM, assinatura paga, cortesia, gratuidade administrativa e trial; somente a autoridade legítima de cada domínio confirma o fato e o vínculo. Sem criação de tenant ou concessão de direitos por coincidência de CNPJ, e-mail, clique ou silêncio.
2. **CORE01-D06-B — fontes de direito independentes e não cumulativas:** Básico público, benefício condicionado a Site elegível, **um trial de 14 dias por CNPJ**, assinatura CRM paga autônoma e gratuidades administrativas são direitos distintos; uma cortesia efetiva no máximo por tenant, sem dobrar quotas por múltiplos Sites, nem gerar fatura/contratação automática.
3. **CORE01-D06-C — estados e marcos de transição próprios:** Site, vínculo, assinatura CRM, benefício, trial e tenant têm marcos oficiais distintos. Preservar cancelamento comum do último Site elegível com **30 dias de operação normal** e avisos 0/15/25/29/30; suspensão do único sustentador por inadimplência após tolerância restringe a cortesia **sem iniciar** 30 dias; cancelamento formal por inadimplência após suspensão inicia **30 dias restritos**; não afetar CRM pago legítimo. Retenção de trial **60 dias após expiração** e de CRM **120 dias após cancelamento formal do próprio CRM**, não por cancelamento Site ou suspensão, com exceções legítimas a definir.
4. **CORE01-D06-D — idempotência, confirmação oficial, reconciliação e auditoria:** eventos de criar tenant, vincular Site, consumir trial, conceder benefício, cancelar, restabelecer e cobrar são tipados/versionados, com causalidade, direitos e resultado confirmado pelo proprietário, sem aceitar ACK/HTTP 2xx/n8n como confirmação financeira ou contratual. Repetições, atrasos, conflitos e alterações de destino não devem duplicar entidades, cobrança ou transferir leads/dados para tenant não autorizado. Mudança de CRM via chamado e nova integração técnica gratuita pela Imagine, sem migração automática de dados.

**Natureza de A–D:** apenas diretrizes **documentais preliminares**. As escolhas de método de identificação, titularidade, timestamps, schemas, papéis, políticas de precedência, controles de fila, retenções técnicas, cobrança, LGPD e prova de homologação continuam P0 e dependem de aceite ulterior.

## 2. Itens NÃO decididos que mantêm CORE01-D06 ABERTA

| Fronteira | Contrato/validação ainda necessária |
|---|---|
| **CORE08-D04..D10** | entidades/estados formais por organização, elegibilidade, assinatura, trial, gratuidade, entitlements, cotas e provas de reativação |
| **FIN/CON e GRAT-P** | autoridade financeira oficial, assinaturas, faturas, efeitos de pagamento, disputa de benefícios, precedência, pró-rata, cobrança, contratação expressa, estornos e retenção |
| **Portal/Sites e IN-117..IN-122** | titularidade, campanhas elegíveis, vínculo/destino, momento de ativação/suspensão, transição, notificações, mudança de destino e portabilidade |
| **INT P0-OWN/CON/SEC/ACC, CT03/04 e IN127-R01..R06** | contratos tipados, autenticidade, idempotência contextual, concorrência, reconciliação, lead em trânsito, destino original autorizado, T0/432h e não redirecionamento |
| **Segurança / CORE01-D04/D05/D07, CORE02..CORE09, SEG-01/02** | memberships/roles/MFA, controle multiempresa, RLS físico, assinaturas, service principals, autorizações e auditabilidade |
| **LGPD, Jurídico, Auditoria e Operações** | portabilidade em modo restrito, classificação de dados, retenção/expurgo/backup e exceções legais, fuso e marco dos eventos |
| **Critérios CORE10 e gates** | owners e evidências de especificação, documentação versionada, futuros testes negativos (não executados), G01..G06 e aceite global |

**Limite da aprovação:** nenhuma precedência nova entre descontos/benefícios, política antifraude física, cronograma técnico de cobrança ou notificação, máquina de estados, tabela, índice, fila, contrato financeiro, exceção LGPD, mecanismo de migração ou garantia de disponibilidade foi ratificada.

## 3. Demais bloqueios preservados e continuidade

**CORE01-D04**, embora com diretrizes A/B/C preliminares aprovadas, **permanece ABERTA** até CORE02/04/07/08. **CORE01-D05**, embora com A/B/C/D preliminares aprovadas, **permanece ABERTA** até CORE02/03/04/05/09, SEG-01/02. **CORE01-D07 continua ABERTA.** Todos os **CORE02..CORE10-D04..D10**, IN-168 ARQ-01/02/03, SEG-01/02, AGT-01, INT P0 e G01..G06, FIN/CON, LGPD, licenças, custos e aprovação global continuam abertos. A matriz da Etapa 2 conserva **67 identificadores técnicos em aberto**; esta aprovação é subdiretriz preliminar, não baixa de D06.

**Continuidade autorizada:** elaborar **CORE01-D07 — proposta documental de integridade, classes de dados, modelo físico/RLS, contratos e critérios de homologação futura**, com alternativas e riscos, **sem aprovar qualquer escolha física por inferência**.

**Vedação expressa:** não iniciar programação/Codex, SQL, alterações de banco, testes executáveis, migrações, provisionamento, contas, operações financeiras, implantação, acesso a dados reais, contratação ou despesas.
