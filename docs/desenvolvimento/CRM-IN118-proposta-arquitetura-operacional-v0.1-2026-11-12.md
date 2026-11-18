# CRM Imagine — IN-118: proposta de arquitetura técnica e plano de homologação v0.1

**Data:** 12/11/2026. **Status:** PROPOSTA PARA REVISÃO; **não aprovada**, **não autoriza implementação operacional nem deploy**.  
**Fontes internas:** [IN-118 funcional](../planejamento/IN-118-central-backup-portabilidade-dados.md), F3-198..F3-205 desenvolvidos como laboratórios inertes, [#27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), [#259 CORE/SEG/DBA/LGPD](https://github.com/rafhael100/imagine-crm/issues/259), [#416 UX Imagine](https://github.com/rafhael100/imagine-crm/issues/416).

## 1. O que já está aprovado versus o que precisa de decisão

Aprovação **funcional** em IN-118: Central de Backup/Importação/Exportação, histórico, CSV/XLSX, backup completo estruturado, restauração compatível, migração sob autorização, funcionalidades essenciais em todos os quatro planos sem tarifa por operação, prévia e confirmação antes de escrita, colisões analisadas, segredos e faturamento não transferidos entre contas, segregação entre empresas, não bloquear exportação de dados existentes quando anexos chegam a 100%, diferenciação entre backup do cliente e backup operacional interno.

**NÃO aprovados**: endpoints, papéis IAM, acesso a qualquer banco, manifesto definitivo, algoritmos/perfis de criptografia, localização de armazenamento, TTL, volume operacional máximo, fila, restauração real, transferência de consentimentos, custos, rotas de produto, release/staging ou autorizações de merge/deploy. Toda escolha abaixo é candidata, com dono e aceite ainda não definidos. Os protótipos offline e os testes PHPUnit anteriores **não são uma implementação da Central**.

## 2. Invariantes de segurança não negociáveis

1. **Escopo do tenant resolvido no servidor**, a partir de identidade real validada e vínculo ativo; não confiar em tenant ID, e-mail, plano, role, `binding_reference`, `confirmed=true` ou assinatura declarados pelo cliente.
2. **Falhar fechado:** ausência de autenticação, autorização por operação/tenant, versão/consentimento, integridade, confirmação ou estado do destino retorna NO-GO, com resposta pública neutra e sem vazamento de outro tenant.
3. **Isolamento em camadas:** controles de aplicação + papéis DB sem superuser/BYPASSRLS + política RLS efetiva + separação de buckets/prefixos/objetos + chaves por escopo + auditoria. Um desses controles não substitui os demais.
4. **Arquivos e backups sempre privados:** nenhum endpoint público de listagem, link previsível, download anônimo ou artefato de outra empresa; TLS, criptografia em repouso e reautenticação forte para ações de maior risco a especificar.
5. **Não restaurar segredos nem faturamento da outra conta**, mesmo se estiverem presentes em um pacote antigo; excluir da importação e registrar alerta técnico sem copiar conteúdo sensível ao log.
6. **Consentimento não é uma coluna booleana automaticamente transferível**: finalidade, origem, controlador e prova precisam ser analisados conforme regras LGPD/contratuais aprovadas; não transformar preenchimento de planilha em autorização.
7. **Restauração reversível:** nunca prometer sucesso antes de commit durável confirmado; não sobrescrever registros preexistentes sem resolução explícita e trilha de auditoria.
8. **Operações idempotentes e limitadas:** retry de timeout deve consultar estado do job antes de repetir efeitos; limites por tamanho/taxa concorrência e operação dependem de política ainda pendente.
9. **Backups de autoatendimento não substituem os backups operacionais internos**, que precisam ter política, localização e testes de restauração independentes.
10. **Nenhum custo/fornecedor novo é autorizado** por este documento.

## 3. Fluxos operacionais CANDIDATOS (ainda não implementados)

### 3.1 Exportação CSV/XLSX (somente dados elegíveis)

```text
Sessão autenticada + tenant no servidor
    -> autorização read/export por recurso, campo e finalidade
    -> snapshot consistente e limitado pelo tenant (RLS)
    -> filtros/seleção de colunas aprovada + classificação de dados
    -> serialização segura para formato e cliente alvo [DECISÃO PENDENTE]
    -> validação de formula injection e normalização [DECISÃO PENDENTE]
    -> criptografia e artefato privado temporário
    -> download autorizado de curta duração [TTL PENDENTE]
    -> evento auditado de operação e acesso
```

A triagem F3-205 é **sinal**, não prova de fórmula neutralizada. A política final deve ser testada com dados fictícios no Excel, LibreOffice e Google Sheets, incluindo aspas, delimitadores, quebras de linha, Unicode, fórmulas, valores negativos legítimos e importação reversa. Não criar URLs reais antes de IAM e política de download.

### 3.2 Backup completo estruturado

```text
Autorização + quota/taxa separadas da franquia de novos anexos
    -> snapshot transacional consistente por empresa
    -> grafo versionado (entidades, vínculos, histórico, configurações elegíveis)
    -> manifesto canônico com inventário/classificação e digests reais
    -> rejeição explícita de segredos, credenciais, tokens e billing não portável
    -> compactação controlada + criptografia autenticada [PERFIL PENDENTE]
    -> verificação independente de integridade + registro de auditoria
    -> armazenamento privado temporário + download autorizado
```

**NÃO adotar** a estrutura simplificada de `SyntheticBackupManifestContractInspector` como schema de produção. Os números 1..3 daquele laboratório são marcadores sintáticos, não versões aprovadas. Manifesto operacional deve conter versão, formato canônico, idempotência, classificação, relacionamentos, referência de anexo permitido, tamanho/algoritmo/verificação de conteúdo, compatibilidade e assinaturas ou autenticação de origem em perfil aprovado. Sem armazenar PII em nome do objeto, URL, logs ou chave pública.

### 3.3 Importação e restauração sem sobrescrita

```text
Identidade/reautenticação e autorização por tenant destino
    -> upload privado em quarentena, jamais em rota pública
    -> defesa contra ZIP-bomb, traversal, links/symlinks, mimetype falso
       macros, payloads malformados e limites de CPU/memória [PERFIL PENDENTE]
    -> verificação criptográfica, schema, versão e origem [PERFIL PENDENTE]
    -> prévia dos campos, linhas, vínculos, consentimentos e conflitos
    -> resolução EXPLÍCITA: ignorar / criar / mapear / revisar conflito
       (ações permitidas ainda a aprovar; nenhuma mesclagem implícita)
    -> ponto de recuperação / registro de operações idempotentes
    -> aplicação transacional com isolamento e RLS não-bypass
    -> validação de consistência pós-commit e auditoria durável
    -> aviso de conclusão apenas com confirmação verificável
```

Conta vazia pode permitir restauração compatível **após verificação real**. Conta preenchida exige comparação e decisão de conflito; estado desconhecido nunca é tratado como vazio. Falha parcial, timeout, cancelamento e colisão entre operações simultâneas precisam de estados observáveis e procedimento de reconciliação antes de repetir ou sinalizar sucesso.

### 3.4 Mudança de CRM vinculado ao Imagine Sites

O suporte Imagine faz **somente a troca técnica do vínculo**, gratuitamente conforme IN-118. Não executar migração automática junto da troca. A empresa escolhe se exporta os dados da conta antiga e solicita/importa os dados na nova por fluxo próprio autorizado. A conta anterior não é apagada pela desvinculação; políticas de elegibilidade, retenção e cancelamento continuam próprias.

## 4. Estruturas de dados propostas para REVISÃO, não migrations

| Grupo candidato | Função | Invariantes |
|---|---|---|
| `portability_operations` | máquina de estados, tipo, solicitante, tenant e idempotência | RLS, transições estritas, escopo autenticado |
| `portability_artifacts` | referência privada, tipo, versão, digest e prazo | sem URL público/permanente nem PII em chave |
| `portability_manifests` | vínculo ao inventário versionado e seu digest | integridade/assinatura não inferida |
| `portability_conflict_decisions` | deliberação explícita por conflito | sem merge automático, autor/horário/versão auditáveis |
| `portability_audit` | fatos mínimos de operação e acesso a arquivos | não gravar conteúdo de contato, secrets ou arquivo |

**Esses nomes NÃO são um schema aprovado.** Antes de migrations, especificar cardinalidades, chaves e classificações, políticas RLS, transações, modo de ledger, limites, retenção e expurgo, ownership de cada tabela e papéis com `NOBYPASSRLS`. Não executar `migrate:fresh`, `db:wipe` ou DDL em ambiente persistente; o parecer #27 continua pendente.

## 5. Planos, armazenamento e custos (já aprovado vs indefinido)

| Plano | Franquia contratual aprovada em IN-118 | Regras funcionais |
|---|---:|---|
| Básico | 1 GB | exportação, importação e backup essenciais |
| Essencial | 5 GB | mesmas funcionalidades essenciais |
| Profissional | 20 GB | mesmas funcionalidades essenciais |
| Empresarial | 50 GB | mesmas funcionalidades essenciais |

Avisos em **80% e 95%**. Em **100%**, restringir **somente novos uploads de anexos**, não os downloads/exportações já permitidos. Não confundir franquia contratual com tamanho de job ou espaço transitório em quarentena. **Definições pendentes:** unidade exata de GB/GiB, cota de job temporário, TLL/TTL do arquivo, retenção/auditoria, custos de armazenamento e criptografia, limite por empresa e plano de contingência. Sem estimar custo zero como garantia.

## 6. Máquina de estados proposta

`requested -> validating -> preview_ready -> awaiting_approval -> applying -> completed`

Saídas explícitas `rejected`, `expired`, `cancelled`, `failed_reviewable`, `rollback_required`, `rolled_back`, `reconciliation_required` (transições detalhadas pendentes). Apenas `completed` com transação verificada pode comunicar sucesso; status desconhecido / timeout não tem direito a ACK definitivo. Auditoria deve registrar transições e motivo por código, com proteção contra replay e concorrência, sem PII no log.

## 7. Matriz mínima de evidências de QA em staging **privado autorizado**

| ID | Cenário obrigatório | Resultado esperado |
|---|---|---|
| T01 | Cliente A tenta listar/exportar arquivo de B | NEGAR, sem revelar existência de B |
| T02 | Sessão expirada/revogada durante download | NEGAR e invalidar acesso |
| T03 | Identidade possui memberships em A e B | Alternância explícita, nenhum vazamento cruzado |
| T04 | Role sem direito `export` apresenta `tenant_id` correto | NEGAR |
| T05 | Mesmo arquivo solicitado duas vezes simultaneamente | Idempotência/reconciliação, não gerar efeitos duplicados |
| T06 | Export com campos restritos | Aplicar direitos por campo ou negar |
| T07 | Fórmulas `=,+,-,@` e variantes Unicode em CSV | Proteção verificada em clientes-alvo sem destruir texto legítimo |
| T08 | CSV com aspas, CR/LF, delimitador e encoding inválido | Parser/serializer conforme política, erro neutro |
| T09 | XLSX com fórmulas, links, macros ou formatos malformados | Quarentena e rejeição conforme política |
| T10 | Backup "completo" faltando histórico/vínculos | Incompatível/incompleto, não restaurável silenciosamente |
| T11 | Manifesto adulterado ou digest inconsistente | NEGAR e auditar |
| T12 | Payload criptografado com chave errada | NEGAR sem eco de conteúdo |
| T13 | Arquivo ZIP traversal, symlink ou inflação extrema | NEGAR sem extrair fora de quarentena |
| T14 | Snapshot enquanto outro job escreve no mesmo tenant | Consistência/serialização conforme design aprovado |
| T15 | Restauração para tenant vazio autorizado | Dados elegíveis coerentes, sem segredos de origem |
| T16 | Restauração para tenant preenchido | Prévia e deliberação explícita, sem overwrite automático |
| T17 | Estado do destino desconhecido | BLOQUEAR aplicação |
| T18 | Consentimento representado apenas por coluna booleana | Não criar autorização legal fictícia |
| T19 | Erro após parte das gravações | Rollback ou reconciliação controlada, auditável |
| T20 | Timeout após commit durável | Não repetir escrita; verificar estado |
| T21 | Role de runtime ou CI tenta `BYPASSRLS`/superuser | NEGAR; revisar ownership/ACL |
| T22 | Cota de anexos em 100% | Negar novos anexos; não impedir exportação existente autorizada |
| T23 | Troca de CRM do Imagine Sites | Não migrar/limpar a conta antiga automaticamente |
| T24 | Cancelamento/retenção/expiração de arquivo | Direitos e prazos aplicados conforme política contratual |
| T25 | Mesmo usuário com conta revogada após iniciar job | Revalidar autorização nos pontos definidos, negar vazamento |
| T26 | Aplicativo em 320px e zoom real 200% | Sem overflow funcional; teclado/foco/leitor de tela conforme aceite UX |

Os laboratórios PHP e pipelines CI atuais **não executam esta matriz de staging**. Cada caso precisa de cenário reproduzível, ambiente/versão e evidência auditável por revisor competente.

## 8. Dependências e ordem de implementação proposta após aprovações

1. **PORT-P0-01 — CORE/IAM/SEG/LGPD:** identidade, escopo por empresa, RBAC por operação/campo, sessão/revogação, trilha de consentimentos, criptografia e segurança de arquivos. Bloqueia todos os demais recortes operacionais.
2. **PORT-P0-02 — DBA:** schema e RLS, ownership, roles mínimas, transações/rollback, ensaios de falha em banco efêmero e staging autorizado; revisão independente da issue #27.
3. **PORT-P0-03 — Formatos:** contrato de manifesto versionado e incompatibilidades, classificação de campos, parser CSV/XLSX, neutralização de fórmulas aprovada e limites de arquivos.
4. **PORT-P0-04 — Storage/crypto/filas:** objeto temporário privado, envelope autenticado, chave/rotação, TTL, expurgo, observabilidade, custo aprovado.
5. **PORT-P0-05 — Export e backup:** pipeline com snapshot e download por autorização; incluir ferramentas de diagnóstico/recuperação sem acesso irrestrito.
6. **PORT-P0-06 — Import/restore/migração:** quarentena, prévia, decisões auditadas, execução transacional e compensação de falhas.
7. **PORT-P0-07 — UX:** integração da interface Imagine da PR visual #429 após aceite humano #416, com estados reais não enganosos, histórico, erros, expiração e suporte.
8. **PORT-P0-08 — Staging QA:** casos T01–T26, revisão de segurança, recuperação real ensaiada **sem clientes**, RLS, LGPD, registro de risco residual e parecer independente.
9. **PORT-P0-09 — Release controlado:** somente depois de autorização da Direção; planejamento separado de deploy, DNS, monitoramento e rollback.

**Não executar os itens 1–9 automaticamente com base neste documento.** A validação independe de percentual de funcionalidades implementadas ou de status de testes sintéticos.

## 9. Decisões P0/P1 explicitamente em aberto

| ID | Decisão a ser registrada | Disciplina |
|---|---|---|
| D01 | Fonte autenticada de tenant, roles/escopos, revogação e MFA/step-up | CORE/IAM/SEG |
| D02 | Schema/versionamento/canonização e assinatura/verificação do manifesto | DBA/SEG/Produto |
| D03 | Dados/histórico/anexos elegíveis e políticas de consentimento entre contas | Produto/LGPD |
| D04 | Criptografia autenticada, gestão/rotação de chaves, armazenamento/TTL | SEG/Infra |
| D05 | Limites de operação, tamanho, paralelismo, quotas e custo incremental | Produto/Infra |
| D06 | Rollback de restauração, estado desconhecido e ledger de idempotência | DBA/Engenharia |
| D07 | Avaliação contra Excel/LibreOffice/Sheets e política de CSV formula injection | Engenharia/SEG |
| D08 | Expiração, retenção, cancelamento, exclusão, direito de acesso e auditoria LGPD | LGPD/Produto |
| D09 | Comprovação de staging sem dados reais, independência do revisor e riscos aceitos | SEG/DBA/Direção |
| D10 | Aceite visual humano da identidade e fluxo navegável (PRs #426/#429) | Direção/UX |

**Critério para sair de DRAFT:** responsáveis e decisões auditáveis D01–D10; gates #27/#259/#416 atendidos nos escopos correspondentes; especificação congelada/versionada; plano de CI e staging sem acesso a produção; autorização explícita posterior para implementar a parte operacional. O presente documento é material de análise, não assinatura de aceites.
