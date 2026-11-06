# CRM Imagine — CORE02-D08: aplicação efetiva, revogação e consistência de autorização — proposta v0.1

**Data:** 05/11/2026.  
**Estado atualizado em 05/11/2026:** **CORE02-D08-A/B/C/D APROVADAS EXPRESSAMENTE APENAS COMO DIRETRIZES DOCUMENTAIS PRELIMINARES**, conforme [ratificação](CRM-DEC-ratificacao-explicita-CORE02-D08-ABCD-2026-11-05.md). **CORE02-D08 integral permanece ABERTA**: mecanismo real, política física, revogação verificável, segurança e homologação ainda pendentes. Menções abaixo a propostas “não aprovadas” referem-se ao estado histórico da minuta antes deste aceite.  
**Escopo:** enforcement e revalidação das regras de autorização por operação, recurso, campo, tenant e contexto, preservando limites e dependências. Não é escolha de esquema, SQL, cache, TTL ou mecanismo final, nem autorização de programação, teste executável, deploy, grant ou credencial real.

## 1. Decisões vigentes e fronteiras

- [CORE02-D01..D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md), [D04](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md), [D05](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md), [D06](CRM-DEC-ratificacao-explicita-CORE02-D06-ABCD-2026-11-05.md) e [D07](CRM-DEC-ratificacao-explicita-CORE02-D07-ABCD-2026-11-05.md) são diretrizes **preliminares**, sem política executável de composição P1–P4, catálogo definitivo de ações/campos, MFA/step-up quantitativo, alçadas ou quotas homologados.
- CORE01-D04..D07 exige tenant e membership legítimos, isolamento Laravel + PostgreSQL RLS e diferenciação de contexto humano, serviço e suporte. CORE03 trata jobs, autorização causal e revalidação; CORE04 sessões/MFA; CORE05 mandatos de suporte; CORE06 auditoria; CORE07 equipes/assentos; CORE09 cofre/serviço; INT/SEG e FIN/CON mantêm seus próprios contratos.
- Administrador geral Imagine não possui leitura comercial automática de cliente. Um identificador `tenant_id`, cabeçalho, GUC `app.tenant_id`, JWT, webhook assinado ou resposta 2xx **não é prova de autorização de negócio**.
- Alteração de permissões do tenant A não deve revogar vínculos legítimos no tenant B. Um ato já confirmado legitimamente não é apagado/revertido apenas por revogação posterior.
- Respeitar a única permissão conjunta `visualizar/baixar anexo` quando prevista; exportar, backup e restore são competências próprias com autoatendimento legítimo em todos os planos conforme contratos aprovados.

## 2. Matriz de fronteiras de aplicação (candidata; sem políticas físicas)

| Fronteira | Verificação indispensável | Negativa/risco a cobrir |
|---|---|---|
| Interface / browser | Mostrar apenas ações elegíveis como conveniência, sem conceder poderes | Ocultar botão não substitui autorização do backend |
| Laravel / API / consultas | Resolver principal, tenant, vínculo/mandato, plano, ação, recurso, campo, escopo e finalidade em serviço confiável | Endpoint alternativo, filtro, relatório, busca ou export não pode contornar a política |
| PostgreSQL RLS / conexões | Defesa tenant-bound com contextos confiáveis por transação, roles mínimas e limpeza/redefinição no reuso | `tenant_id` enviado pelo cliente ou `SET` residual não deve abrir dados da empresa anterior |
| Jobs / filas / automações / IA | Identidade técnica e autoridade causal registradas; revalidar condição temporal antes de novo efeito | Sessão revogada, papel alterado, fila atrasada, retry ou prompt não podem recriar grant |
| Arquivos / anexos / exports / backups | Validar vínculo, direito, escopo e integridade na geração e disponibilização; controlar artefatos/links temporários | URL antiga, arquivo copiado ou download tardio não expõem dado após revogação |
| Integrações / webhooks / Portal e Sites | Fonte oficial, vínculo autorizado, operação tipada, credencial protegida, status e idempotência | ACK externo, assinatura válida ou mesmo CNPJ não permitem importar/deliver a tenant distinto |
| Suporte / Administração Geral | Mandato específico com ator, caso, tenant, ação, finalidade, escopo, período e trilha | Não admitir bypass universal de RLS/entitlement por superadmin ou operador |
| Auditoria / observabilidade | Registros minimizados, motivos sanitizados e correlação da decisão | Log não pode conter segredo, dado sensível excessivo ou ser usado para contornar acesso |

## 3. Diretrizes CORE02-D08-A/B/C/D — PROPOSTAS NÃO APROVADAS

### CORE02-D08-A — Uma decisão coerente, aplicada no servidor em todas as superfícies

Exigir catálogo de contrato e decisão semântica por operação, consultado por todos os pontos relevantes (Laravel, API, pesquisa, dashboards, relatórios, exportações, uploads, integrações e jobs), com fonte única de políticas do IAM **sem criar segundo motor IAM**. A interface é auxiliar, nunca controle de segurança suficiente. Negar operação sem regra/autoridade válida, respeitando plano, objeto, campo, papel, escopo, estado e finalidade. Não decidir ainda arquitetura de biblioteca, tabela de grants ou cache de decisão.

### CORE02-D08-B — Defesa complementar PostgreSQL RLS e integridade de contexto

Exigir que a implementação futura valide fronteiras Laravel + PostgreSQL RLS para dados tenant-bound, preservando identificação de principal e tenant no backend e contexto transacional confiável. Prever roles não privilegiadas, integridade de chaves, limpeza de conexões, worker/pool, transações, exceções globais justificadas e suporte por mandato. **Não aprovar por esta diretriz** SQL específico, funções, GUC, `SET LOCAL`, roles/grants executáveis ou uma prova de identidade baseada só em `tenant_id`; compatibilizar CORE01-D05/D07 e SEG-01/02.

### CORE02-D08-C — Revogação efetiva, consistência e operações assíncronas

Exigir política coordenada para revogação de membership/papel, alteração de equipe/escopo, perda de MFA, suspensão de plano, encerramento de mandato e bloqueio de conexão: sessões e tokens derivados, caches, workers/jobs, filas, acesso a anexos, exportações e automações devem refletir a alteração antes de novos efeitos protegidos, conforme contrato do proprietário e operação. Distinguir operações já confirmadas de comandos pendentes; falhas de sincronização ou falta de autoridade nunca geram `allow` por fallback. Definir tempos máximos, semântica de eventual consistência, cancelamento, reprocessamento e compensação **posteriormente**, sem TTL implícito ou rollback automático indiscriminado.

### CORE02-D08-D — Evidências, auditoria e homologação de acessos negativos

Exigir matriz verificável de principal × tenant × papel × plano × recurso × ação × campo × escopo × fase (início/execução/disponibilização), associada ao dono funcional, versões, resposta segura, auditoria sanitizada e critérios de teste. Casos obrigatórios para desenho futuro: usuário A lendo B; mudança de papel após abertura da tela; tenant trocado por header; fila pós-revogação; exportação pronta depois da suspensão; link antigo para anexo; role BYPASSRLS indevida; IA ou suporte sem mandato; endpoint API alternativo; operação financeira sem step-up/alçada. **Nenhum teste ou política produtiva é aprovado/executado por esta pauta**.

## 4. Dependências abertas e critérios de fechamento posterior

- CORE02-D04/D05/D06/D07/D08 e D09/D10 exigem catálogo final por domínio, composição P1–P4 de grants/denies, política concreta MFA/step-up e alçadas, matriz real de planos, classificação de campos, provas de segurança e revisão por proprietário.
- CORE01-D04..D07, CORE03/04/05/06/07/08/09/10, SEG-01/02, ARQ-01/02/03, AGT-01, INT P0/G01..G06, FIN/CON, LGPD e baseline global ainda não estão homologados; 67 identificadores técnicos CORE permanecem abertos até aceites específicos.
- O desenvolvimento Codex fast-track em `dev/fast-track-crm-2026-11-04` possui autorização restrita própria. Este documento não libera implementação nova, merge para `main` ou operações em VPS/produção.

## 5. Próxima deliberação proposta

**Submeter ao responsável somente a aceitação preliminar de D08-A/B/C/D.** Se aprovadas, D08 integral permanece ABERTA até fechamento físico/comprovação. Em seguida preparar pauta CORE02-D09 de direitos por plano, quotas, adicionais e ciclo de assinatura em coordenação com CORE08, FIN/CON e matrizes comerciais existentes — sem estabelecer números por inferência.
