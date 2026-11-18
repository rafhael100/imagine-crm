# CENTRAL-AUTO-P1 — candidata integrada privada

Autorização da direção: 16/11/2026. DRAFT / NO-MERGE / NO-DEPLOY / NO-CHARGE.

## Bases verificadas

CRM #633 `cc17350fd4a0dc82b29e88d485a62f20e9e57c93` é a base desta candidata. O planejador `RuleDefinition` é reaproveitado byte a byte da #632 `f39d0077ca1db45b6ecc1eb495675c19c73addcb`. O LabRuleStore da #632 permanece ensaio isolado, não é promovido a autorizador ou conectado à UI. Novo adaptador NativeFollowupEngine usa as conexões/grants/RLS e a autoridade nominal já existentes da jornada privada. Nenhuma branch normativa foi mesclada.

Portal #903 `3d948486609c05d0df2f053870519cc8bc47c7f5` permanece visão de políticas independente. Agents #384 `21c820b56d53bce243969a4fdd440bf1e26644e5` e #387 `d3860e1e3d95b79ca3a2c6376651a62028820141` são irmãs sobre RC03, não incorporadas entre si: a primeira mantém chave até commit, a segunda comprova workflow n8n oficial em CI network-none. Não confundir nenhuma delas com instalação/identidade operacional.

## Fluxo integrado

Responsável salva modelos permitidos, versão, ator autenticado e atraso na UI. Workspace verifica sessão/MFA, vínculo ativo, empresa e trial; o servidor deriva tenant/ator/destinatário. Novo evento de etapa é interpretado por RuleDefinition. O comando tipado persiste numa ledger tenant-scoped; a execução nativa grava tarefa, resultado e auditoria na mesma transação. Tarefa usa ID da execução; índice único de idempotência e locks por organização evitam duplicidade.

Somente os follow-ups qualificado/proposta existentes são habilitados. Novos modelos de distribuição/avisos do catálogo não são expostos sem integração completa. Atraso padrão zero preserva criação imediata da tarefa para o próximo dia. Atrasos maiores criam pendências recuperáveis. Edição/pausa invalida as pendências anteriores; cancelamento é idempotente e exige responsável. Tarefas concluídas nunca são apagadas pela pausa.

Processamento de pendências exige POST com CSRF e sessão nominal atual. Um membro pode processar apenas execuções próprias; não se escolhe empresa, ator ou destinatário no navegador. Membership/credencial/sessão ficam sob locks até commit; cada execução consulta também a sessão causal persistida. Sessão antiga revogada/expirada cancela a pendência, mesmo após novo login. Não há daemon ou credencial técnica que finja identidade humana. CORE DenyByDefaultAuthorizer permanece intacto.

Só ação `task.followup.create` é consumida; destinatário é o ator atual, lead/versão/configuração precisam continuar válidos. Limites trial existentes permanecem; teto de segurança 200 execuções/200 tarefas e lote de 20. Capacidade de tarefas indisponível gera até três tentativas com backoff, sem envio externo/IA. Preços ou limites de planos não homologados não são inferidos.

## Persistência e segurança

A nova tabela integra exclusivamente o schema de instalação **descartável de CI**, com FK composta empresa/lead, UNIQUE empresa/chave, CHECKs de modelos/estados/tentativas, RLS FORCE e role de aplicação sem BYPASSRLS/ownership. Não é migration operacional nem instrução de aplicar na VPS existente. O backup criptografado/restore existente inclui a ledger e a verifica sob RLS. Nenhuma sessão/token é exposta na UI; apenas ID interno de autoridade é armazenado, sem secret/token humano para outro serviço.

## Evidências exigidas

Pipeline de containers/HTTP/Chromium existente reutilizado na SHA da candidata. Inclui configuração pela UI, novo agendamento de 1s, tarefa real no PG fictício, processamento repetido sem duplicar, cancelamento repetido, pausa, versões e permissões. Passo separado reinicia apenas o app efêmero e compara quantidade/hash de toda a ledger. Backup/restore e navegador existentes continuam obrigatórios. Resultados devem ser registrados após execução; arquivo de teste não é prova de aprovação.

## Limites P2/P3/P4

P2 requer um provedor autorizado de metadados entre aplicações, sem leitura direta do banco CRM pelo Portal. P3 reutiliza envelopes assinados e inbox/outbox existentes; nenhum workflow do cliente será compilado para n8n. P4 não autoriza instalação: inventário real de portas/recursos, licença, segredos, TLS, egress, backup externo/restore e rollback precisam estar comprovados. Nenhum acesso à VPS, merge, deploy, API paga ou dado de cliente nesta candidata.
