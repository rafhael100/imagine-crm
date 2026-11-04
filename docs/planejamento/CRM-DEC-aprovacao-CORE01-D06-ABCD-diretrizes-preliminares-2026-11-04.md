# CRM-DEC — Aceite expresso preliminar de CORE01-D06-A, B, C e D — ciclo organizacional e comercial

**Data:** 04/11/2026.

**Manifestação literal do responsável:** “Aprovo CORE01-D06-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE01-D06 aberta até o fechamento dos contratos e dependências com CORE08, FIN/CON, Portal/Sites, INT e Segurança. Preserve CORE01-D04, D05, D07 e todos os demais bloqueios e prossiga com CORE01-D07, sem programação, testes ou implantação.”

**Estado:** **CORE01-D06-A/B/C/D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**; **CORE01-D06 PERMANECE ABERTA** até os contratos e dependências com CORE08, FIN/CON, Portal/Sites, INT e Segurança. Não se homologou arquitetura física, modelo de dados, regras de titularidade concretas, novos entitlements, contratos financeiros, implementação nem evidência de teste.

**Fonte submetida:** [CORE01-D06 v0.1 — ciclo de tenants e vínculos comerciais](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-tenants-vinculos-comerciais-proposta-v0.1.md). A seção 8 é a pauta única A/B/C/D; os subtemas da minuta e as versões históricas não criam segunda aprovação. Passagens antigas com “NÃO APROVADAS” ficam superadas **apenas** quanto às diretrizes preliminares aceitas.

## 1. Diretrizes preliminares expressamente aceitas

1. **CORE01-D06-A — fontes oficiais e vínculos explícitos:** distinguir CNPJ/representação, organização CRM/tenant, Site e campanha/contrato elegível, destino Site↔CRM autorizado, assinatura, cortesia, trial e gratuidade administrativa. Somente proprietário competente confirma fato, titularidade ou direito, sem inferir pelo CNPJ, e-mail ou ACK.
2. **CORE01-D06-B — ciclos independentes e preservação de direitos:** separar contrato Site, vínculo, tenant, trial, assinatura CRM paga, cortesia e gratuidade em regimes próprios. Manter cortesia única por organização, trial único por CNPJ e 14 dias, independência do CRM pago, suspensão/reativação condicionadas, transições de 30 dias comuns (operacionais) e após cancelamento por inadimplência (restritas), avisos pré-aprovados e ausência de contratação/cobrança automática.
3. **CORE01-D06-C — titularidade, concorrência e vínculo idempotente:** exigir solicitação legítima, prova de titularidade e autoridade de origem/destino para criação/alteração de tenant e ligação de Site, prevenindo duplicidade, reordenação e efeitos inconclusivos; trocar destino via chamado e nova integração técnica gratuita, sem migração automática de dados ou redirecionamento tácito de leads pendentes.
4. **CORE01-D06-D — retenção, restrição e portabilidade sem efeitos colaterais:** diferenciar suspensão operacional de benefício/site, expiração de trial e cancelamento formal do próprio CRM; manter acesso restrito para portabilidade/contratação expressa quando cabível, retenções próprias de 60 dias de trial expirado e 120 dias após cancelamento formal CRM (com obrigações e exceções legítimas), sem apagar fatos entregues ou cobrar por inferência.

**Aceite estritamente preliminar:** a segurança física, a precedência exata entre benefícios, o esquema de dados, as normas financeiras executáveis, a autenticação de contratos e os mecanismos de migração/revogação continuam ABERTOS, com owners CORE08, FIN/CON, Portal/Sites, INT e Segurança.

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
