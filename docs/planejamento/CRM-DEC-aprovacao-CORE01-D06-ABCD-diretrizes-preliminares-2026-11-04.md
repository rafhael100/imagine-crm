# CRM-DEC — Aceite expresso preliminar de CORE01-D06-A, B, C e D — ciclo organizacional e comercial

**Data:** 04/11/2026.

**Manifestação literal do responsável:** “Aprovo CORE01-D06-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE01-D06 aberta até o fechamento dos contratos e dependências com CORE08, FIN/CON, Portal/Sites, INT e Segurança. Preserve CORE01-D04, D05, D07 e todos os demais bloqueios e prossiga com CORE01-D07, sem programação, testes ou implantação.”

**Estado:** **CORE01-D06-A/B/C/D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**; **CORE01-D06 PERMANECE ABERTA** até os contratos e dependências com CORE08, FIN/CON, Portal/Sites, INT e Segurança. Não se homologou arquitetura física, modelo de dados, regras de titularidade concretas, novos entitlements, contratos financeiros, implementação nem evidência de teste.

**Fonte submetida:** [CORE01-D06 v0.1 — ciclo dos tenants e vínculos comerciais](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-tenants-vinculos-comerciais-proposta-v0.1.md). Esta decisão altera **somente** o status preliminar das quatro diretrizes explicitamente aprovadas; texto anterior de “NÃO APROVADAS” na versão histórica fica superado por esta decisão no alcance definido.

## 1. Deliberações aceitas, sem ampliação de escopo

1. **CORE01-D06-A — fontes oficiais de autoridade e vínculos explícitos:** distinguir juridicamente/funcionalmente CNPJ, pessoa/representação, organização CRM como tenant, contratos elegíveis de Site, vínculo autorizado de destino CRM, assinatura paga, benefício de cortesia, trial e gratuidade administrativa. Prova de titularidade, seleção de destino e confirmação por dono de cada fato permanecem obrigatórias; CNPJ ou e-mail iguais não criam direitos, vínculos ou união de bases.
2. **CORE01-D06-B — ciclos independentes e direitos preservados:** contrato Site, vínculo de integração, tenant, assinatura CRM paga, cortesia, trial e gratuidade seguem estados próprios. Manter regras prévias de uma cortesia Básico por tenant, trial único de 14 dias por CNPJ, CRM pago independente do site, avisos do fim da elegibilidade e transição de 30 dias: **operação normal no cancelamento comum**, **acesso restrito se cancelamento após suspensão por inadimplência**, sem cobrança automática.
3. **CORE01-D06-C — titularidade, concorrência e alteração idempotente do vínculo:** criação/vinculação/alteração de tenant/destino requer solicitação legítima, titularidade e autoridade verificadas, versionamento, prevenção de duplicidade, tratamento de eventos fora de ordem e reconciliação segura. Troca Site↔CRM por chamado com nova integração técnica gratuita pela Imagine; **sem migração automática de dados ou redirecionamento de leads pendentes a outro destino**.
4. **CORE01-D06-D — retenção, acesso restrito e portabilidade sem efeitos colaterais:** separar suspensão por site/cortesia, expiração do trial e **cancelamento formal do próprio CRM**, preservando exportação/portabilidade permitidas, histórico, retenção referencial de **60 dias após expiração do trial** e de **120 dias após cancelamento formal do CRM**, observando LGPD/obrigações e políticas próprias. Nem cancelamento do site nem fim de cortesia constitui contratação do CRM pago ou exclusão imediata.

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
