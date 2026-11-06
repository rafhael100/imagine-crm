# CRM Imagine — CORE02: revisão consolidada D04–D10 e precedência B ratificada

**Data:** 06/11/2026.  
**Estado atualizado em 06/11/2026:** **ALTERNATIVA B DE PRECEDÊNCIA CORE02-D05 RATIFICADA EXPRESSAMENTE**, conforme [decisão formal](CRM-DEC-ratificacao-explicita-CORE02-D05-alternativa-B-2026-11-06.md). Esta é uma revisão documental, **não** homologação CORE02, não concede grants e não fecha identificadores técnicos.  
**Objetivo:** verificar o que foi aprovado preliminarmente no ciclo CORE02 e selecionar a próxima decisão concreta que precisa de manifestação expressa do responsável. Este documento não fecha nenhum identificador técnico.

## 1. Estado consolidado das diretrizes CORE02

| Decisão | Diretrizes A/B/C/D | Lacuna técnica/documental que impede encerramento integral |
|---|---|---|
| **CORE02-D04** — classes de campos e recursos | Preliminarmente aprovadas em 04/11 | Inventário real dos campos e proprietários por módulo, privacidade, anexos, classificação/canais, exceções e evidência |
| **CORE02-D05** — combinação de perfis | Diretrizes preliminares aprovadas; **alternativa B/P1+P2 escopada ratificada em 06/11** | Detalhar autoridade para conceder/negá-las, delegações, herança, dados/escopos, TTL, conflitos, UX/auditoria e homologação |
| **CORE02-D06** — catálogo de ações | Ratificação expressa preliminar de 05/11 | Catálogo por módulo/recurso/ação/campo/escopo homologado pelos donos funcionais e integrado aos planos |
| **CORE02-D07** — ações sensíveis | Ratificação expressa preliminar de 05/11 | Matriz concreta risco/MFA/step-up, condições, tempos de reautenticação, alçadas e tipos de aprovação por operação |
| **CORE02-D08** — enforcement/revogação | Ratificação expressa preliminar de 05/11 | Políticas efetivas Laravel + RLS, APIs, filas, anexos, expiração, cache e revogações com segurança comprovada |
| **CORE02-D09** — plano, direitos e quotas | Ratificação expressa preliminar de 06/11 | Quotas e preços finais, matriz real de funcionalidades por plano, contagem, adicionais, FIN/CON/CORE08 e direitos em estados restritos |
| **CORE02-D10** — rastreabilidade e homologação | Ratificação expressa preliminar de 06/11 | Casos por módulo, owners e evidência por SHA/ambiente/teste, regressão, exceções e aceite global após implementação |

**Atenção:** todos os sete identificadores CORE02-D04..D10 permanecem **ABERTOS** apesar de suas subdiretrizes aprovadas preliminarmente. CORE02-D01..D03 têm aceite de base documental. As dependências CORE01, CORE03..10, SEG-01/02, INT P0 G01..G06, ARQ/AGT, FIN/CON, LGPD e 67 identificadores técnicos CORE continuam sem fechamento global. Desenvolvimento experimental controlado do Codex não é homologação integral.

## 2. CORE02-D05 — escolha de precedência já ratificada

### Contexto preservado

A organização/tenant e a membership verificadas, o contrato/entitlement, o estado do objeto, o campo protegido, as regras mandatórias de segurança e a autorização do proprietário sempre limitam os direitos. **Não existe transferência de poderes entre empresas.** Falta escolher se e quando uma negativa explícita atribuída por papel deve prevalecer sobre concessões de outros papéis **no mesmo contexto**.

### Alternativas avaliadas (histórico; **B aprovada como princípio**)

| Alternativa de política | Regra | Benefício | Risco/tradeoff |
|---|---|---|---|
| **A — União de concessões válidas com bloqueios mandatórios** | Somar concessões dos papéis dentro do mesmo tenant/objeto/escopo; bloqueios mandatórios prevalecem; tratar a negativa expressa de papéis somente onde houver regra específica futura | Simples para usuários com múltiplas funções | Restrição administrativa marcada como “negação explícita” pode não prevalecer por padrão |
| **B — União de concessões válidas com negação explícita específica e contextual prevalecente** | Somar concessões dos papéis dentro do **mesmo tenant e escopo efetivo**; se houver **negação explicitamente configurada para a mesma operação, registro/campo e escopo válido**, ela prevalece; bloqueios mandatórios sempre prevalecem; falta de concessão não se transforma em veto global | Permite acumular responsabilidades sem desrespeitar proibições intencionais; auditável | Exige interface clara para diferenciar “não concedido” de “negado expressamente” e evitar conflitos/negações excessivamente amplas |
| **C — Interseção dos papéis** | Somente permitir se todos os papéis atribuídos concederem a ação | Muito restritiva | Pode inutilizar funções adicionais ou papéis complementares |
| **D — Papel principal único** | Apenas um papel determina direitos; demais não agregam | Fácil de explicar | Não atende bem usuários com múltiplas equipes/funções |

**Decisão expressa do responsável: ALTERNATIVA B RATIFICADA**, com limites mínimos: bloqueios mandatórios acima de qualquer concessão; negativa explícita deve ter fonte, dono, vigência, ação, campo e escopo específicos; papel sem grant não é veto; proibição da equipe A não deve afetar operação legítima da equipe B quando os escopos forem realmente distintos; nunca conceder direito por falta de política confiável; documentação e auditoria do motivo de recusa sem expor segredo.

**Exemplos ilustrativos da diretriz ratificada, sujeitos à homologação do modelo real:**
1. Usuário com papel Vendas (ler leads) e papel Relatórios (exportar leads da própria equipe). Sem deny explícito e com entitlement vigente, ambos podem ser válidos **somente no escopo autorizado**.
2. A administração do tenant adiciona uma **proibição expressa de exportar contatos da equipe Nordeste**, para o mesmo usuário. Mesmo que outro papel tenha `export` naquela equipe, o ato fica bloqueado **na equipe Nordeste**.
3. Se a proibição foi configurada exclusivamente para a equipe Nordeste, ela **não deve** negar automaticamente uma exportação legitimamente autorizada de dados da equipe Sul, sem uma regra mandatória superior.
4. Usuário com leitura permitida, mas sem `export` em qualquer papel, **não pode exportar**, ainda que nenhuma proibição expressa exista.
5. Um usuário com perfis Vendas e Financeiro não pode, pela simples união, elevar permissões para conceder plano gratuito, abrir dados de outra empresa ou editar logs protegidos.

### Deliberações que permanecerão abertas mesmo após escolha de B

- Modelo exato de recursos e escopos, funções e delegações, herança, proveniência de denies, interfaces UX, API, cache e auditoria.
- Prevenção de autoelevação por quem administra papéis; quais negativas podem ser criadas por cliente vs. pela Imagine.
- Política RLS/identidades/competência efetiva, inclusões temporárias, vigência e testes de negação contextual, em CORE01/SEG/CORE02-D08.
- Catálogo funcional, quotas de planos e alçadas financeiras de cada proprietário.

## 3. Sequência após ratificação da alternativa B

1. **Concluído documentalmente:** alternativa B ratificada expressamente e registrada em decisão própria, sem fechamento de D05. Prosseguir para a competência de criar/editar negações, delegações e tratamento de conflitos.
2. Detalhar catálogo concreto de campos/ações por módulo (D04/D06), em especial operações de exportação, administração e automações.
3. Compatibilizar D07/D08 com políticas técnicas de identidade, MFA, RLS, suporte e agentes; preparar testes com owners e matriz de risco.
4. Revisar comercialmente D09 com CORE08 e FIN/CON; não presumir preços finais.
5. Consolidar D10 com CORE10 e INT/G01..G06, produzindo evidências e aceites após fases autorizadas.

**Sem programação, testes, alterações de dados, grants, merge, implantação ou mudanças de infraestrutura por este documento.** Branch de Codex `dev/fast-track-crm-2026-11-04` e `main` continuam fora do escopo deste PR documental.
