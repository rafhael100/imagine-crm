# CRM F3-38 — pacote verificável de decisão para escrita/importação futura (NO-GO)

**Data:** 10/11/2026. **Natureza:** especificação de revisão, não autorização funcional. **Base:** F3-32, F3-36, F3-37, issue SEG/DBA #27, PR #175 e Central IN-118.

## 1. Pergunta de arquitetura que bloqueia a implementação

O laboratório hoje demonstra o comportamento de objetos isolados em memória. **Não há garantia transacional entre processos ou reinícios.** Antes de qualquer importação, confirmação de revisão ou alteração de cadastro, é obrigatório decidir qual banco persistente será autoridade de versão, unicidade de operação, auditoria e fronteira de tenant. Um UUID alegado em payload, um cabeçalho HTTP ou um GUC PostgreSQL não autenticam o solicitante.

## 2. Proposta para análise especializada — ainda NÃO aprovada

Separar dois atos, sem tratar prévia como execução:

1. **Prévia somente leitura**, sem PII nos logs, produzindo um digest estável de arquivo/escopo/transformação e uma versão observada *provenientes de fontes confiáveis*. Não habilitar upload ou leitura de arquivo real nesta fase.
2. **Confirmação posterior**, se e quando expressamente autorizada, exige principal autenticado, MFA conforme risco, membership válida reavaliada, autorização por ação/campo/tenant, revisão humana competente, digest e versão esperada, chave idempotente escopada e trilha auditável.

A futura transação SQL proposta para estudo deve validar versão atual, checar unicidade por (tenant, operação, chave), registrar resultado imutável, atualizar versão monotônica e gravar evento de auditoria **no mesmo commit**. Em caso de disputa, negar/retornar conflito sanitizado sem aplicar parcialmente; retry da mesma operação deve recuperar o resultado anterior sem nova mutação, somente quando payload/escopo/digest equivalentes. **Isto é requisito para revisão, não desenho de schema aprovado.**

## 3. Matriz de evidências obrigatórias

| ID | Cenário de teste posterior | Resultado esperado | Prova exigida |
| --- | --- | --- | --- |
| RV-01 | Principal sem identidade verificada | DENY, nenhuma mutação | Ensaio IAM e trilha sem PII |
| RV-02 | Membership revogada entre prévia e confirmação | DENY | Revogação no limite transacional |
| RV-03 | Mesmo humano com memberships em 2 tenants | Nenhum cruzamento de dados ou chaves | Ensaio RLS com role non-BYPASSRLS |
| RV-04 | Chave idempotente reutilizada com payload divergente | CONFLICT, sem reexecução | Constraint e transação real |
| RV-05 | Mesma chave com retry após perda da resposta HTTP | Recuperação exata do resultado já confirmado | Teste de perda de ACK/restart |
| RV-06 | Dois workers confirmam mesma versão em paralelo | No máximo um commit mutador | PostgreSQL com concorrência real |
| RV-07 | Dois processos diferentes recebem mesma chave | Unicidade escopada no banco, não em memória | Processos e conexões independentes |
| RV-08 | Falha depois da gravação de domínio e antes da auditoria | Rollback integral, nenhum evento órfão | Fault injection transacional |
| RV-09 | Falha depois do commit e antes do retorno | Sem duplicação no retry | Recuperação por registro durável |
| RV-10 | Arquivo/coluna/transformação alterados depois da prévia | Previsão invalidada | Hash/digest verificado em fonte confiável |
| RV-11 | Entrada CSV com fórmula, encoding ruim e grande volume | Rejeição ou tratamento conforme política aprovada | Limites, testes adversariais e orçamento de recursos |
| RV-12 | SQL com sessão pooled e tenant trocado | Nenhum vazamento entre contextos | Reset transacional e teste RLS |
| RV-13 | Tentativa de comando destrutivo/migration fora do CI descartável | Bloqueio antes do banco | ACL/role/rede e proteção externa à migration |
| RV-14 | Cancelamento/suspensão de organização/plano durante revisão | Estado revalidado, DENY quando aplicável | Entitlement e contrato de estados aprovados |
| RV-15 | Exportação de dados de outra organização ou campos proibidos | DENY e resposta sanitizada | Catálogo de campo, LGPD e revisão de escopo |

**Condição de aceite:** resultados produzidos no commit exato e ambiente descartável conhecido; revisão humana independente competente identificada, matriz de risco e aprovação expressa da direção. CI verde com mocks **não** substitui RV-03/RV-06/RV-07/RV-08/RV-09/RV-12/RV-13.

## 4. Responsabilidades e decisões em aberto

- **SEG/DBA (issue #27; PR #175 DRAFT):** modelo de isolamento, roles, RLS, estratégia transacional/CAS, isolamento de CI e migrações; registrar revisor independente efetivo, não presumido.
- **IAM/CORE02/S01:** fonte de identidade, revogação, MFA, membership e política de direitos positivos/negativos; proibir qualquer ALLOW implícito.
- **Produto/FIN/CON:** escopo de planos, limites de importação/exportação, período de retenção, estados elegíveis e tratamento de erro.
- **Privacidade/LGPD:** base legal, minimização, descarte, classificação de campos e audit trail minimizado.
- **Direção:** decidir F3-32-D01..D10, identificar owners e autorizar explicitamente eventual execução por ambiente. Aprovação de documento não é aprovação de SQL nem release.

## 5. Ordem segura de execução quando houver decisões

1. Resolver alternativas e assinaturas das decisões acima, anexando evidência de competência.
2. Modelar arquitetura, ameaças e plano de rollback/restore em documentação independente.
3. Abrir PR DRAFT isolado com **testes negativos primeiro** e falhas esperadas, sempre em ambiente efêmero; nenhum acesso de cliente.
4. Somente após revisão específica, autorizar SQL experimental estritamente isolado e verificar a matriz, incluindo concorrência.
5. Exigir novo GO explícito para integração, migração ou release. Nunca derivar GO de CI verde.

## 6. Escopo estritamente proibido desta entrega

Nenhuma rota, API, upload, importação/exportação, autenticação positiva, política ALLOW, grant real, migration, segredo, acesso a cliente, VPS, produção, alteração de `main` ou gasto. Este documento não altera o status dos 67 CORE, SEG, AUT, S01, IN-118, FIN/CON e LGPD, que continuam abertos.
