# CRM-DEC — Ratificação expressa CORE02-D07-A/B/C/D como diretrizes preliminares

**Data:** 05/11/2026 (America/Sao_Paulo).  
**Origem e escopo:** o responsável respondeu “aprovado” à apresentação de CORE02-D07-A/B/C/D em [CORE02-D07 — operações sensíveis, MFA/step-up e alçadas](CRM-CORE-P0-ETAPA2-CORE02-D07-acoes-sensiveis-step-up-alcadas-proposta-v0.1.md), após proposta explícita de aprovação **preliminar**, com definição exata de fatores, prazos e alçadas reservada à homologação.  
**Status:** **CORE02-D07-A/B/C/D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES; CORE02-D07 integral permanece ABERTA**.

## 1. Quatro diretrizes expressamente aprovadas

1. **D07-A — operações sensíveis e risco:** cada proprietário de operação deve definir risco, finalidade, classe dos dados, tenant, campo, canal, volume, efeito, competência e condições de recusa; sem fixar nesta etapa critérios quantitativos definitivos ou catálogo concreto homologado.
2. **D07-B — MFA e reautenticação proporcional ao risco:** preservar MFA obrigatório dos administradores centrais e de empresas (inclusive Básico), e exigir step-up quando a política competente indicar, vinculado ao ator/contexto/ação/vigência. Autenticação bem-sucedida não cria grant, plano, mandato, consentimento ou poder.
3. **D07-C — confirmação e alçadas:** diferenciar confirmação explícita pelo **mesmo operador autorizado** de aprovação adicional por autoridade distinta, esta apenas quando o fluxo específico exigir segregação. Não impor dupla aprovação universal; não permitir autoaprovação onde houver segregação obrigatória. IA/agentes podem preparar propostas, nunca efetivar atos protegidos sem autorização apropriada.
4. **D07-D — recusa segura, trilha e revalidação:** negar atos sem direitos/entitlement/MFA/mandato/aprovação legítimos, com mensagens seguras e auditoria minimizada. Revalidar direitos antes de efeitos adiados e de expor resultados posteriores; não desfazer fatos legítimos já confirmados por mera revogação subsequente.

## 2. Pendências e não efeitos

- **CORE02-D07 segue ABERTA**, inclusive catálogo real por módulo, criticidade/volume, métodos de MFA/step-up, TTL, regras de aprovação e revogação concretas, testes e homologação. Não conceder nenhum direito ou acesso.
- **CORE02-D04/D05/D06/D08..D10** mantidas abertas; D04/D05/D06 possuem somente diretrizes preliminares, sem política de precedência P1–P4 escolhida, catálogo físico ou matriz de planos homologada.
- Preservar CORE01, CORE03..CORE10, SEG-01/02, ARQ-01/02/03, AGT-01, INT P0/G01..G06, FIN/CON, LGPD, critérios globais e 67 identificadores técnicos CORE abertos. Não encerrar gates pelo registro.
- Nenhuma nova programação Codex autorizada por esta aprovação documental. Manter `main`, branch de desenvolvimento do Codex, VPS, dados e produção inalterados.
- Próxima **proposta ainda não aprovada:** CORE02-D08, sobre aplicação e revogação efetiva das regras em Laravel, API, PostgreSQL RLS, jobs/filas, arquivos, exportações e integrações.

**Integração:** decisão registrada em PR documental em rascunho, sem merge automático.
