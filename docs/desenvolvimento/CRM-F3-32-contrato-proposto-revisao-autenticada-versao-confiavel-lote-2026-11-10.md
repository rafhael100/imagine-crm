# F3-32 — Proposta de contrato para revisão autenticada e versionamento confiável de lote CSV

**Estado:** PROPOSTA TÉCNICA, **NÃO APROVADA**. Data: 10/11/2026.  
**Âmbito:** preparar decisão funcional/SEG/DBA/Privacidade e testes futuros do CRM Imagine. **Não é autorização para código operacional, `ALLOW`, arquivo real, banco persistente ou deploy.**

Fontes: [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [S01 #22](https://github.com/rafhael100/imagine-crm/issues/22), Central IN-118, CRM-REV-03, F3-20..31 e [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) (DRAFT sem parecer).

## 1. Por que o laboratório não pode ser reutilizado como backend

O laboratório F3-29 fornece `fixture-sha256-v1` **público** calculado sobre bytes e posição de colunas. F3-30 compara posições entre duas matrizes; F3-31 apenas ilustra esses resultados. O chamador **pode construir outra matriz e seu hash**, reproduzir dados antigos e falsificar estados de revisão. Não há fonte confiável do lote, banco, assinatura, identidade, sessão, trilha ou direitos por tenant. **Nenhum desses retornos concede permissão para importar.**

A futura solução precisará de uma **versão monotônica armazenada de forma confiável**; checksum poderá auxiliar na detecção de corrupção, mas **não deve ser bearer token, prova de pessoa ou autorização**. A alteração de um único dado, mapeamento, dialeto, catálogo de campos ou política de conversão deve exigir reavaliação e impedir o reaproveitamento automático da aprovação anterior.

## 2. Fronteiras propostas

| Fronteira | Responsabilidade e limite |
|---|---|
| Entrada do arquivo | Somente após autorização de origem/empresa e escopo; tamanho/limites, identificação de mídia, quarentena, malware, política de encoding/dialeto e controles de infraestrutura ainda a homologar |
| Snapshot do lote | Identificador opaco interno `batch_id`, `organization_id` resolvida no **servidor**, `batch_version` **inteira monotônica**, estado controlado e revisão do parser/catálogo; conteúdo e mapeamento imutáveis **por versão** |
| Diagnóstico | Resultado por posição com código e minimização; os dados brutos não entram em logs, telemetria, mensagens de erro ou relatórios gerais |
| Revisão humana | Ator global autenticado e membership ativa na organização correta, ação/campo/escopo autorizados; MFA conforme risco; decisão vinculada a `batch_id + batch_version + policy_version` por evidência auditável |
| Concorrência | Verificação de versão e estado no servidor **dentro da mesma transação** da alteração; cliente não escolhe `tenant_id` nem pode aumentar `batch_version` |
| Execução da importação | **Outra competência separada**, inicialmente sempre negada. Só após revisão SEG/DBA/Produto/Privacidade, regras idempotentes, backups/restore e decisão expressa sobre escrita real |
| Auditoria/retention | Registro mínimo de quem/ação/versão/horário/decisão e motivo tipificado sem PII extra; política de retenção, exclusão, acesso e restauração pela Central IN-118 e LGPD |

**Princípio:** o usuário humano, a organização atual, a membership, o objeto e a ação devem ser confirmados no backend confiável. `tenant_id`, CNPJ, JWT não validado, cabeçalho HTTP, parâmetro de rota, GUC PostgreSQL e checksum **não são provas de direito**. RLS é defesa adicional, jamais substituta da autorização da aplicação.

## 3. Estados e transições — PROPOSTA NÃO APROVADA

Estados conceituais candidatos: `received_quarantined`, `diagnostic_pending`, `review_pending`, `review_in_progress`, `needs_correction`, `review_recorded`, `invalidated`, `cancelled`, `expired`. Não habilitar `ready_to_import` nem `imported` no primeiro recorte, pois seriam ambíguos e sugeririam GO de escrita.

- Criar uma nova versão de conteúdo/mapeamento deve **invalidar** as decisões da versão anterior; não reclassificar uma linha detectada como limpa.
- `review_recorded` significa **registro de decisão competente**, ainda **não** significa lote seguro, livre de conflito, LGPD aprovado ou permissão para importar.
- Cancelamento/expiração/revogação bloqueiam nova revisão; a retenção dos snapshots anteriores e das evidências aguarda política aprovada.
- Nem `0` alertas nem `0` pendências equivalem a GO de importação ou aceitação de duplicidades.
- A remoção/inserção de linha muda referências de posição: usar identificador de linha **específico da versão**, gerado no servidor; nunca presumir que “linha 3” de duas versões seja a mesma pessoa.
- Em caso de falha de parser ou drift de catálogo, negar transição e exigir nova revisão; não “aceitar o mais recente” silenciosamente.

## 4. Contrato mínimo sugerido para revisão (não implementado)

O comando conceitual de registrar revisão teria como entrada **apenas** um identificador opaco do lote, a versão observada, uma lista limitada de decisões tipificadas por posição **na versão observada**, e eventual chave idempotente escopada. Campos sensíveis não devem ser devolvidos na prévia global.

**Validações obrigatórias, no backend e na mesma transação autorizada:**

1. Resolver sessão/principal autenticado e vínculo ativo com a organização por fonte confiável; verificar MFA/revogação e direito por recurso, ação, campo e escopo.
2. Carregar `batch_id` exclusivamente no escopo da organização resolvida; negar de forma indistinguível objetos inexistentes/fora de tenant quando necessário para evitar enumeração.
3. Conferir versão monotônica, estado, `parser_version`, `field_catalog_version` e `policy_version` confiáveis; mudanças produzem conflito e invalidam decisões.
4. Verificar tipos, tamanho, unicidade dos números de linha e compatibilidade de cada decisão com alertas **calculados pelo servidor**; impedir `approve_all`/wildcard e atualização destrutiva.
5. Registrar evidência mínima e mudança de estado com compare-and-swap atômico `(organization_id, batch_id, expected_version, expected_state)`; se nenhuma linha for afetada, retornar conflito sanitizado **sem gravar revisão**.
6. Conferir idempotência apenas de **mesma operação lógica**. Repetição com mesmo idempotency key e payload diferente deve gerar conflito; nunca misturar idempotência de submissão com deduplicação/merge de pessoas.
7. Aplicar auditoria e resposta com minimização; nenhuma alteração do objeto de lead/contact ou autorização de importação ocorre nesta operação.

**Pseudofluxo, não implementável diretamente:** autenticar → autorizar read/review com competência → carregar snapshot de mesmo tenant → validar versões e alertas → transação compare-and-swap + trilha → retornar resultado sanitizado. Nunca confiar em um `approved_by`, `tenant_id` ou `snapshot_matches` submetido pelo cliente.

## 5. Códigos de resposta/resultado propostos, sem criar API

| Situação | Resultado conceitual | Política |
|---|---|---|
| Não autenticado/sessão inválida | `unauthenticated` | Nenhuma existência de lote exposta |
| Principal sem membership/direito | `forbidden` / não distinguível conforme threat model | Não informar outras empresas, usuários ou lotes |
| Versão/snapshot/política alterados | `stale_batch` | Nenhuma revisão gravada; cliente precisa refazer diagnóstico/revisão |
| Estado expirado/cancelado/revogado | `batch_not_reviewable` | Sem ressurreição ou bypass por token |
| Colisão de decisão, campo, linha ou operação | `invalid_review_payload` | Sem entrada permissiva, sem eco de PII |
| Repetição idempotente igual | `already_recorded` (se política aprovar) | Não duplicar auditoria/efeito |
| Divergência com a mesma chave idempotente | `idempotency_conflict` | Nunca aplicar payload alternativo |
| Revisão registrada com sucesso | `review_recorded_only` | **Importação continua proibida** |

Mensagens não devem revelar versão de outro tenant, coluna confidencial ou se determinado lead existe.

## 6. Matriz mínima de homologação FUTURA — testes ainda NÃO executados

| ID | Caso | Resultado obrigatório |
|---|---|---|
| RV-01 | Mesmo lote, versão e membership válidos, ação específica aprovada | Registra **somente revisão**, sem importação |
| RV-02 | Nenhuma identidade autenticada | Nega, zero efeitos |
| RV-03 | Principal da empresa A tenta revisar lote da empresa B | Nega, sem enumeração e sem efeitos |
| RV-04 | Mesmo humano com vínculos A e B tenta trocar contexto no meio do fluxo | Nega fora do escopo; contexto transacional não vaza |
| RV-05 | Membership revogada após carregar página e antes do envio | Nega no momento do comando |
| RV-06 | MFA ausente/expirado quando obrigatório | Nega |
| RV-07 | Versão muda entre visualização e confirmação | Conflito; não registra decisão antiga |
| RV-08 | Duas revisões concorrentes sobre mesma versão | No máximo uma transição válida; a outra conflita |
| RV-09 | Conteúdo igual, mas parser, catálogo ou política mudou | Revalidação obrigatória; sem reutilizar decisão |
| RV-10 | Inclusão/remoção/reordenação de linha | Invalida referências de linha anteriores |
| RV-11 | Alteração de mapeamento sem alterar bytes da matriz | Invalida revisão anterior |
| RV-12 | Marcador público F3-29 válido, sem sessão/direito | Nega: hash não confere privilégio |
| RV-13 | Payload forja `tenant_id`, `approved_by` ou `import_permitted=true` | Rejeita campos, sem eco da entrada |
| RV-14 | Linha sinalizada marcada `reviewed_no_findings` | Nega, não limpa alerta |
| RV-15 | Posição duplicada, fora do lote, wildcard, lote vazio ou tamanho excessivo | Rejeita integralmente |
| RV-16 | Repetição idempotente igual e payload diferente | Não duplica efeito; conflito para divergência |
| RV-17 | Lote cancelado/expirado ou vínculo suspenso | Nega, sem ressuscitar estado |
| RV-18 | Auditoria indisponível/falha transacional | Falha fechada; nenhuma confirmação sem trilha exigida |
| RV-19 | Conta PostgreSQL com RLS non-bypass e GUC manipulada | Camadas app/RLS testadas separadamente; sem privilégio por GUC |
| RV-20 | Falha de parser, encoding, fórmula, tamanho, campo ou origem | Não registra revisão permissiva |
| RV-21 | Snapshot antigo restaurado ou replay de versão monotônica | Não aceita rollback silencioso |
| RV-22 | Resultado sem alertas e todas as decisões preenchidas | Não ativa importação |
| RV-23 | Teste de backup/restore em ambiente descartável previamente autorizado | Recupera ledger consistente, sem cruzar tenants |
| RV-24 | Usuário de suporte/admin global sem mandato específico | Nega acesso comercial interempresa |

Os testes acima **ainda não foram executados** e **não podem ser rotulados como homologados**. Precisam ser implementados contra infraestrutura/sessões e credenciais de teste autorizadas quando os gates de segurança permitirem.

## 7. Decisões pendentes — não transformar recomendações em regras

| Decisão | Responsáveis efetivos necessários | Estado |
|---|---|---|
| F3-32-D01: versão monotônica e armazenamento imutável por lote | Arquitetura + DBA + SEG | PENDENTE |
| F3-32-D02: competência `review_batch`, alçada, MFA e delegação | IAM/CORE + SEG + Direção | PENDENTE |
| F3-32-D03: catálogo mínimo de campos e minimização de logs | Produto + Privacidade | PENDENTE |
| F3-32-D04: retenção, exclusão, consentimentos e backups de lote | Central IN-118 + Privacidade + DBA | PENDENTE |
| F3-32-D05: semântica idempotente e deduplicação de pessoas | Produto + Arquitetura + SEG | PENDENTE |
| F3-32-D06: parser/dialeto/encoding e proteção contra fórmulas | Arquitetura + Segurança + QA | PENDENTE |
| F3-32-D07: concorrência, auditoria e recovery | SEG/DBA + Arquitetura | PENDENTE |
| F3-32-D08: política por empresa/recurso/ação/campo e RLS | CORE/IAM + SEG/DBA + Direção | PENDENTE |
| F3-32-D09: política de tratamento de erros e transparência ao titular | Produto + Privacidade | PENDENTE |
| F3-32-D10: liberação de implementação, ambientes, reviewer e critérios de GO | Direção + revisor independente competente | PENDENTE |

**Ação legítima seguinte:** owner/revisor independente designado examina esta proposta em conjunto com os PRs #23/#26/#49, issue SEG/DBA #27 e o pacote DRAFT #175. Decisões devem ser registradas por competência, com evidência e autorização específica. Não atribuir parecer ao autor deste documento.

## 8. Escopo do presente PR

Somente documentação de engenharia, **sem código funcional, controller, endpoint, senha, token, role, migration, SQL, login, RLS, upload, persistência ou deploy**. `DenyByDefaultAuthorizer` e os gates CORE/S01/SEG/LGPD seguem intocados. Este texto pode ser versionado **somente DEV como proposta**, e não equivale a GO de revisão ou importação real.
