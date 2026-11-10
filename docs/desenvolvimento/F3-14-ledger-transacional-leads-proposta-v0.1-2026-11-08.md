# Imagine CRM — F3-14 — Proposta de ledger transacional de leads

**Documento técnico para revisão especializada — NÃO HOMOLOGADO.**  
Data de referência: 08/11/2026, Brasil (America/Sao_Paulo).  
Fase: documentação, sem SQL, migrations, funções operacionais, autorização de cliente, deploy ou custo novo.

**Dependências:** [Issue #35 — F3](https://github.com/rafhael100/imagine-crm/issues/35), [Issue #27 — SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), IN-124, IN-127/128/129, IN-147/148/149, F3-01, F3-07..F3-13 e os gates CORE/AUT/INT/LGPD.

## 1. Objetivo e limites

Uma submissão de formulário Imagine deve ter identificador lógico único e estável, receber confirmação de persistência pela **API receptora**, e ser considerada **entregue ao CRM apenas** mediante evidência confiável de persistência final no destino. Duas APIs podem receber a mesma submissão, mas o CRM não deve criar dois registros por retentativas, timeout, failover ou resposta perdida.

A prova F3-13 verificou concorrência, índice único e RLS em **ledger genérico assíncrono já existente** no PostgreSQL descartável de CI. Essa tabela não armazena site, formulário nem destino CRM; **não é o ledger de leads proposto abaixo**.

Objetivo de F3-14: estabelecer um desenho revisável dos invariantes físicos, do esquema de dados e das decisões que precisam de parecer independente. A existência deste documento não inicia implementação funcional.

## 2. Modelo de identidade e contexto imutável — proposta

| Campo lógico | Característica necessária |
|---|---|
| `tenant_id` | organização proprietária, resolvida pelo servidor confiável, UUID, não recebida como prova do cliente |
| `submission_id` | UUID estável por envio lógico e todas as tentativas |
| `binding_snapshot_id` | vínculo site/formulário/destino em vigor **na primeira aceitação válida**, referenciado por snapshot imutável |
| `site_id`, `form_id`, `destination_crm_tenant_id` | referências originadas de vínculo autenticado, congeladas para o envio |
| `payload_reference` | referência opaca para payload cifrado fora da tabela de auditoria, com ACL/TTL próprios |
| `payload_integrity_tag` | MAC/HMAC com segredo gerenciado, se validado por Segurança; evitar hash simples de valores pessoais ou payload em log |
| `receiver_route` / `received_at` | evidências do receptor; tempo inicial deve ser gerado após **commit durável**, não relógio do navegador |
| `state`, `version`, `updated_at` | estado controlado do processamento e versão monotônica para CAS |
| `crm_delivery_proof_reference` | referência a comprovante verificável no CRM, não simplesmente um HTTP 2xx |
| `expires_at` | calculado a partir da primeira persistência durável, **nunca recalculado por retry/failover** |

**Índice único candidato:** `UNIQUE(tenant_id, submission_id)` no ledger de operação **autoritativa** de idempotência no destino ou em um coordenador validado. As APIs independentes podem manter recebimentos duráveis locais próprios, mas **índice único em cada API isolada não garante ausência de duplicação entre APIs**. O CRM deve aplicar idempotência/único também no ponto de criação/associação do lead.

**Contexto pinado:** repetição com mesmo `(tenant_id, submission_id)` e `binding/site/form/destino` divergentes = conflito de integridade, nunca redirecionamento nem criação de nova operação. Uma segunda submissão legítima com conteúdo idêntico recebe outro `submission_id`. Não fazer merge de pessoas por e-mail, telefone ou conteúdo.

**Aprovações pendentes:** composição exata da chave, chaves estrangeiras compostas, cardinalidade vínculo/formulário/destino, como obter snapshot confiável, nomenclatura e versão do contrato de API, integridade MAC e proteção dos dados pessoais.

## 3. Tabelas lógicas candidatas (NÃO são migrations)

- **`lead_delivery_operations`**: uma operação por submissão lógica e tenant, escopo original imutável, versão CAS e estados; contém *referências mínimas*, não o payload em texto.
- **`lead_receiver_receipts`**: observações verificáveis das APIs principal/contingência, `route`, ID do recibo durável, checksum/versão do envelope, vínculo com operação e timestamp de commit; unicidade por recibo e por tentativa conforme contrato homologado.
- **`lead_delivery_attempts`**: tentativas com token técnico, instante e falha sanitizada, versão do protocolo, resultado incerto/confirmado; sem nome, contato ou credenciais.
- **`lead_crm_delivery_proofs`**: vínculo entre submissão e evidência emitida/consultada pelo CRM após persistir o registro, com identidade de destino confirmada; unicidade/idempotência também no receptor CRM.
- **`lead_delivery_audit_minimized`**: trilha técnica mínima, expurgo padrão de 90 dias, distinta do payload e sujeita a normas LGPD/retenção legal.

Os nomes são provisórios. Não criar tabelas nem disparar migrations até finalizar ownership, privilégios, estratégia de instalação/rollback/restore e parecer SEG/DBA.

## 4. Estados e transições — proposta fail-closed

| Estado proposto | Evidência mínima para entrada | Operação proibida sem prova |
|---|---|---|
| `api_durable_received` | recibo durável do receptor verificado depois do commit | declarar entrega CRM |
| `pending_crm_delivery` | entrada íntegra em fila/outbox apropriada | assumir criação do lead |
| `processing` | reserva atômica válida com fencing/versionamento | segundo worker processar simultaneamente |
| `outcome_unknown` | timeout, queda ou resposta intermediária sem confirmação | reenviar às cegas, excluir, confirmar |
| `crm_delivered_confirmed` | prova de persistência final do CRM associada ao tenant, submissão e destino corretos | aceitar HTTP 202/200 isoladamente |
| `delivery_failed` | falha autenticada e classificada com janela aberta | alterar destino histórico, reiniciar relógio |
| `expiry_reconciliation_required` | prazo de 18 dias atingido + verificação final pendente | apagar com estado conflitante |
| `expired_deleted_undelivered` | elegibilidade confirmada e exclusão auditada segundo IN-147 | afirmar que foi entregue |

A semântica de “recebido com sucesso” ao visitante é distinta de “entregue CRM”. O agente técnico não substitui uma prova transacional confiável. Eventos fora de ordem, respostas tardias e restauros de backup devem ser versionados e reconciliados.

## 5. Sequência segura proposta para reserva e entrega

1. Receptor verifica autenticação/integridade do envelope, canal/site/form, antiabuso e vínculo resolvido no backend. Referências declaradas pelo cliente **não** concedem autoridade.
2. A API principal ou de contingência persiste o **recibo e o payload cifrado duravelmente**. Só então o frontend pode receber o primeiro ACK. Falha após commit com resposta perdida conserva estado incerto, para consulta posterior pela mesma chave.
3. O consumidor do ledger verifica tenant/destino, bloqueios/revogação aplicáveis e realiza reserva transacional com `UNIQUE(tenant_id, submission_id)`, CAS de `version`, fencing/lease homologados e comparação do contexto original. Apenas um worker deve avançar na mesma versão.
4. Em contenção de chave com transação não confirmada, timeout/deadlock/queda -> **inconclusivo e reconciliável**, não sucesso nem reenvio cego. Após rollback/commit, consultar estado da operação em fonte confiável antes de novo envio.
5. O destino CRM aplica sua própria idempotência e confirma com prova verificável após o commit do registro. Um HTTP 202 é apenas aceitação para processamento; um UUID alegado não basta.
6. A conciliação vincula recibos das duas APIs, tentativas, outbox e prova CRM, respeita o contexto original e detecta estados contraditórios. Registros de negócios só podem ser acessados por competências homologadas.

**Ponto aberto de arquitetura:** como coordenar duas infraestruturas independentes sob partição de rede sem depender do sucesso de um único coordenador. Não alegar *exactly once* global: usar idempotência de destino, persistência durável, compensação e testes de recuperação comprovados.

## 6. Isolamento, permissões e RLS

- Isolamento físico por `tenant_id` e FKs compostas entre tabelas; `ENABLE/FORCE RLS` nas relações com dados segregados, inclusive tentativas e evidências.
- Papéis de runtime distintos de owner/admin/migrator, mínimos direitos por tabela/coluna/ação, `NOSUPERUSER`, `NOBYPASSRLS` e bloqueio de `TRUNCATE` fora de políticas operacionais.
- **`app.tenant_id` é GUC modificável, não autenticação.** O backend precisa obter identidade/membership/mandato com prova verificável, estabelecer contexto transacional confiável e limpá-lo em pool/jobs.
- Nenhuma autorização por simples igualdade entre tenant solicitado e tenant alegado. Tenant com múltiplos memberships, revogação no meio do job, suporte global e relatórios cruzados exigem testes negativos dedicados.
- A existência de role `postgres` no serviço efêmero CI não autoriza replicar superusuário em VPS, produção ou testes com persistência.

## 7. Retenção, prazo e eliminação

- **0–72h:** tentativas normais a partir do primeiro commit durável.
- **72–432h:** exceções/fila de falhas; aviso administrativo **72h antes** do vencimento, no marco de 360h.
- **432h / 18 dias:** se não entregue ao CRM, suspender tentativas, reconciliar em fonte confiável e somente então verificar a elegibilidade de descarte. **Não** criar quarentena adicional padrão.
- **Entrega CRM confirmada:** limpar payload temporário **24h após confirmação**, mantendo o lead CRM.
- **Auditoria técnica minimizada:** 90 dias, conforme IN-149, separada da retenção de payload e sujeita a exceções legais.

**Corrida crítica:** confirmação do CRM versus descarte no aniversário de 18 dias exige exclusão protegida por estado/versionamento transacional e verificação de evidências tardias em todas as rotas. Se a verificação for inconclusiva, a integridade prevalece até conciliação controlada (sem reiniciar o relógio). Backups, réplicas, retenção legal e reprocessamento de falhas precisam de política específica.

## 8. Plano de homologação experimental antes de implementação funcional

| ID proposto | Teste necessário | Gate |
|---|---|---|
| F3-14-T01 | `UNIQUE(tenant, submission)` e reenvio da mesma chave concorrendo em duas conexões | PostgreSQL efêmero |
| F3-14-T02 | dois tenants com a mesma chave sem leitura/escrita cruzada por papéis limitados | CORE/SEG/DBA |
| F3-14-T03 | mudança de vínculo/site/form/CRM para submissão existente é conflito, não duplicação | INT/Sites/CRM |
| F3-14-T04 | worker concorrente/manual e automático, CAS/fencing/lease e rollback | SEG/DBA |
| F3-14-T05 | timeout após commit no receptor, ack perdido e reconciliado, nenhum segundo lead | INT/Observabilidade |
| F3-14-T06 | resposta do CRM `202` e UUID falso não marcam `delivered` | SEG/CRM |
| F3-14-T07 | duas APIs duráveis com mesma submissão sob partição de rede | INT/ARQ |
| F3-14-T08 | confirmação tardia simultânea ao descarte em 18 dias | SEG/DBA/LGPD |
| F3-14-T09 | restauração de backup antigo após revogação, sem reabrir acesso/descarte | SEG/DBA |
| F3-14-T10 | dados sensíveis não vazam em headers, exceções, traces e auditoria | LGPD/SEG |
| F3-14-T11 | pool com GUC contaminado, trabalhador sem contexto confiável, RLS sem BYPASS | IAM/CORE/SEG |
| F3-14-T12 | guardrails de CI: identidade do banco descartável, destruição acidental e mudança de role | SEGDBA-01..06 |

**F3-13 já cobriu somente parte dos T01/T02 em uma tabela async genérica preexistente.** Isso não constitui T01/T02 homologados para o futuro ledger de leads.

## 9. Decisões pendentes e competência

**P0 / SEG/DBA:** revisor independente ainda não designado; revisar ownership/migrations, roles, acesso por conexão e pool, recuperação/restore, lock timeouts, unique constraints, transações e SQL. Avaliar a Issue #27 antes de promover este esquema a código.

**P0 / Arquitetura e Integrações:** decidir autoridade de chave idempotente compartilhada entre APIs, armazenamento independente na contingência, confirmação final CRM, assinatura/autenticação e versionamento da API. Concordar com tradeoffs de latência e indisponibilidade sem violar retenção.

**P0 / Privacidade e Produto:** aprovar dados mínimos, finalidade e base legal, aviso de formulário, HMAC dos payloads, cifragem, retenção de evidências e exceções de guarda.

**P1 / Operação:** observabilidade, incidentes, recuperação, alertas, metas de capacidade e limites de gastos sem fornecedores novos até autorização.

**Nenhum destes P0 foi homologado por este documento.** Permanecem abertos os 67 CORE, Issue #27 SEG/DBA, AUT/INT/ARQ/FIN/CON/LGPD e os gates de `main`, VPS, produção, pessoas reais, `ALLOW`, APIs e migrations.
