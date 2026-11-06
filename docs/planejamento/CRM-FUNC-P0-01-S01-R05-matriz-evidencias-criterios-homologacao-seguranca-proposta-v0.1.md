# FUNC-P0-01 / S01-R05 — Matriz de evidências e critérios de homologação de segurança (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** PROPOSTA PARA AVALIAÇÃO, **NÃO APROVADA**.  
**Antecedentes documentais:** R01-D01..D05-A; R02-D01..D08-A; R03-D01..D08-A; R04-D01..D08-A, todos **apenas diretrizes preliminares**, conforme registros específicos nesta branch.  
**Objeto único:** planejar evidências, critérios negativos e autoridades de revisão de **R02-C01..C08**, **sem homologar nenhum contrato**.  
**Recorte funcional:** S01 = leitura humana **exclusivamente** do nome exibível da organização corrente autorizada e do estado exibível do próprio vínculo autenticado. Não criar catálogo comercial, perfis de terceiros, autorização global, serviços, API, banco, nem novos direitos.  
**Fontes de cotejo:** REV-01 (IAM/CORE), REV-02 (comercial/FIN/CON), pauta R04 v0.1 (contratos e owners candidatos), demais matrizes técnicas ainda abertas. **R01-D01..D05** requerem recuperação da pauta literal original.

## 1. Forma obrigatória de evidência futura (estrutura conceitual, sem preencher com fatos inexistentes)

| Campo de rastreabilidade | Descrição documental proposta | Limite |
|---|---|---|
| `contract_id` | referência exata a R02-C01..C08, versão da especificação, dependências | não confundir com status fechado |
| `responsavel_dominio` | owner proposto e aprovador competente por IAM, CORE, FIN/CON, Portal, INT, LGPD/SEG | owner candidato ≠ aceitação formal |
| `risco_objetivo` | ameaça de acesso indevido, enumeração, vazamento, autoconcessão, cobrança etc. | não inferir mitigação implementada |
| `invariante` | afirmação verificável do comportamento esperado futuro | ainda não é ALLOW/política executável |
| `cenario_positivo_negativo` | pelo menos um caminho permitido **condicionado** e um negado por cada tipo relevante | somente cenários escritos, não testes |
| `fonte_autoritativa` | IAM/CORE, FIN/CON, Portal/INT, cadastro organização ou LGPD, conforme atributo | HTTP 2xx e cache não são prova comercial |
| `artefato_futuro` | política aprovada, matriz de acesso, diagrama, evidência reproduzível, auditoria, parecer | nenhum artefato de execução declarado presente |
| `versao_e_integridade` | SHA/versão de documento, política e resultado quando autorizado | evidência anterior à mudança não comprova versão nova |
| `aprovacoes` | avaliação separada de segurança e do owner de domínio; LGPD quando aplicável | ratificação preliminar não é homologação |
| `situacao` | **PENDENTE / EM REVISÃO / APROVADO DOCUMENTALMENTE / HOMOLOGADO APÓS PROVAS** (estados de controle propostos) | todos C01..C08 permanecem **PENDENTES** agora |

## 2. Matriz por contrato: risco, prova requerida e critérios negativos futuros

| Contrato pendente | Owner candidato | Evidência documental mínima a especificar | Cenário negativo **futuro**, não executado | Critério de liberação futuro |
|---|---|---|---|---|
| **C01** Autenticação | CORE04/IAM + SEG | matriz MFA/step-up por papel; confiança e revogação de sessão; semântica de expiração | usuário anônimo, sessão expirada, fator insuficiente ou identidade trocada solicita S01 | parecer CORE04/SEG + prova negativa autorizada posteriormente |
| **C02** Contexto e vínculo | CORE01/02 + SEG + dono de membership | definição canônica principal-tenant-membership, vigência, versão e transição entre organizações | principal A usa membership B, solicita tenant arbitrário ou tenta ler vínculo não aceito | contrato de resolução confiável por operação + revisão SEG |
| **C03** RLS/revogação | CORE01/03/04 + SEG | matriz lógica camada aplicação ↔ PostgreSQL RLS; papéis e privilégios não bypass; regras de cache/revalidação | IDOR, role com BYPASSRLS, revogação concorrente, resposta com registro de tenant alheio | revisão de desenho e, somente em etapa autorizada, provas isoladas de RLS e concorrência |
| **C04** Campos e não enumeração | CORE01/02 + LGPD + responsável UX | catálogo com finalidade, classificação, projeção exata dos dois campos e política de respostas negativas | solicitação acrescenta CNPJ, pessoas, organização alheia ou obtém existência por diferença de erro | revisão de minimização e casos de não enumeração |
| **C05** Fatos comerciais | FIN/CON CRM + Portal + INT | catálogo de fatos por produto, titularidade, origem verificável, eficácia, versão, idempotência e conciliação | callback 2xx ou evento fora de ordem altera direitos/induz cobrança | aprovação FIN/CON+Portal+INT independente de IAM/S01 |
| **C06** Direitos vs estado | CORE02/08 + FIN/CON + SEG | matriz principal×tenant×ação×recurso×vigência×entitlement×deny; precedência e quotas | benefício ativo cria usuário extra, plano ativo libera exportação geral ou vínculo revogado usa direito comercial | matriz homologada por módulos e SEG, **sem novos ALLOW nesta fase** |
| **C07** Falhas e concorrência | CORE03 + FIN/CON + INT + SEG | plano documental de retries, ordem, duplicidade, autoridade de conciliação, notificações e compensações | replay recria benefício cancelado, falha de origem cobra de novo, erro suspende direito comprovado sem regra | dono comercial/técnico aprova política de conciliação e prova negativa futura |
| **C08** Auditoria e privacidade | SEG + auditoria + LGPD | matriz de categorias, finalidade, campos mínimos, consultas, retenção, descarte, integridade, segregação | log de token/payload completo, consulta irrestrita, retenção comercial aplicada indevidamente à auditoria | LGPD+SEG+auditoria homologam escopo e evidências, prova futura de restrição |

**Sem resultados de teste.** Todos os cenários são hipóteses documentais de futura homologação, e não descrição de comportamento implementado.

## 3. Matriz de precedência e fontes semânticas

1. **IAM e membership** determinam identidade e vínculo, não plano pago.
2. **FIN/CON CRM** determina estados oficiais do trial e assinatura; **Portal** determina estados de site/contrato elegível; **INT** transporta fatos conforme contrato homologado, sem conceder privilégio.
3. **CORE02/SEG** avaliam autorização da operação sob tenant e recurso próprios; entitlements e direitos comerciais não são sinônimos de membership.
4. **LGPD e auditoria** determinam finalidade, acesso e retenção aplicáveis; cronograma de retenção comercial não se transfere automaticamente a logs.
5. **Conflitos** entram em conciliação pelo owner responsável: ausência de prova não gera concessão/cobrança nova nem revogação automática de direito anteriormente comprovado sem política especializada.
6. **S01** não deve retornar informações comerciais/financeiras; não confundir insumos internos da autorização com campos de resposta.
7. Trâmites de **SEG/CORE/AUT/FIN/CON/INT/LGPD e 67 CORE** não são encerrados por uma aprovação de redação R05.

## 4. Critérios de entrada para futura homologação (ainda não satisfeitos)

- [ ] Rastrear texto literal e fonte de R01-D01..D05 e resolver divergências.
- [ ] Cada C01..C08 ter versão, proprietário e aprovador oficialmente confirmados, com aceite do domínio.
- [ ] Catálogo semântico de campos/estados/ações do S01 classificado e revisado por LGPD/CORE/SEG.
- [ ] Matriz de tenant, vínculos, sessões e negação validada documentalmente, inclusive concorrência.
- [ ] Matrizes CORE02/08, FIN/CON, INT, Portal e políticas de RLS/segredos/revogação conciliadas.
- [ ] Roteiro dos testes negativos futuros e ambiente isolado autorizados por decisão específica; separar suíte documentada de resultado executado.
- [ ] Evidência de auditoria sem conteúdo pessoal desnecessário; retenção/categoria/versão aprovadas.
- [ ] Bloqueios globais e 67 CORE submetidos ao processo **próprio** de encerramento, sem baixar IDs por inferência.

Sem satisfazer esses itens, não afirmar que o módulo é seguro, pronto para clientes, homologado ou implantável.

## 5. Propostas para deliberação R05-D01 a R05-D08 — **todas pendentes**

| Decisão | **Alternativa A recomendada, apenas documental preliminar** | Alternativa B para contraste |
|---|---|---|
| **R05-D01** | **A:** exigir ficha de evidência versionada por contrato com owner, risco, fonte e decisão | B: registrar aprovação geral sem rastreabilidade individual |
| **R05-D02** | **A:** prever cenário positivo condicionado e casos negativos específicos por C01..C08 | B: validar somente caminho feliz |
| **R05-D03** | **A:** separar parecer documental de prova executada e homologação, com status inequívoco | B: chamar critérios escritos de testes concluídos |
| **R05-D04** | **A:** homologação por owner e SEG/LGPD/FIN/CON quando aplicável, sem delegação implícita | B: qualquer aprovação genérica fecha todos os domínios |
| **R05-D05** | **A:** versionar origem, contexto de tenant, política e evidência futura; preservar integridade | B: aceitar prints/logs sem identificação de versão e autoria |
| **R05-D06** | **A:** rejeitar autoconcessão por estado comercial/cache/webhook; tratar conflito por conciliação | B: confiar em evento isolado para liberar acesso ou cobrança |
| **R05-D07** | **A:** exigir critérios específicos de RLS, revogação, não enumeração, auditoria mínima e privacidade | B: aceitar filtro de UI e log amplo como segurança suficiente |
| **R05-D08** | **A:** preservar todos C01..C08 e 67 CORE/SEG/AUT demais gates **ABERTOS** até fechamento próprio | B: encerrar contratos/gates por ratificação preliminar |

Aprovação futura das alternativas A **não** autoriza testar, implementar, homologar qualquer C01..C08, alterar `main`, PRs, CI, VPS ou iniciar produção. A próxima etapa, se ratificada, deve buscar **pendências remanescentes de owner, precedência e homologação**, sem tratar decisões preliminares como conclusão.

## 6. Limites absolutos desta entrega

**Somente Markdown na branch documental isolada.** Não criar código, SQL, migrations, ALLOW/GRANT, testes funcionais, dados reais, rotas, CI, contratos de serviço, faturas, eventos reais, integrações, credenciais ou implantação. Não abrir/editar PR #2/#21, alterar `main`, `dev/fast-track-crm-2026-11-04` ou VPS.  
**67 IDs CORE, SEG-01/02, AUT e demais gates permanecem ABERTOS**. R02-C01..C08 **PENDENTES**.  
**Próxima ação:** apresentar R05-D01..D08 ao responsável para avaliação específica.
