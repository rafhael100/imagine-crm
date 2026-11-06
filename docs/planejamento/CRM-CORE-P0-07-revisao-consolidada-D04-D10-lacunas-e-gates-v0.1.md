# CRM Imagine — CORE07-D04..D10: revisão consolidada de lacunas e precedências (v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **REVISÃO DOCUMENTAL PREPARADA; NENHUM FECHAMENTO TÉCNICO OU GATE AUTORIZADO.**  
**Origem:** conclusão da rodada de diretrizes preliminares D04–D10, incluindo [aprovação expressa CORE07-D10-A/B/C/D](CRM-DEC-ratificacao-explicita-CORE07-D10-ABCD-2026-11-06.md), sem modificar implementações ou a base `main`.

## 1. Conclusão documental e estado real

| Código | Política-base selecionada preliminarmente | Pendências reais para fechamento técnico/homologação |
|---|---|---|
| **CORE07-D04** | **Modelo B**: renovar convite apenas por ato expresso de administrador competente, revogar link antigo e emitir nova validade de 7 dias; uma reserva elegível | Contrato de emissão/validação/revogação de token; tratamento de vencimento/reenvio/renovação, concorrência, antispam, e-mail/identidade, exceções e prova de aceite |
| **CORE07-D05** | **Modelo A**: reserva temporária de uma vaga para convite válido; liberação por cancelamento/recusa/expiração; ocupação única por aceite | Contagem por estado do plano, transação de reserva/última vaga, idempotência, suspensão/downgrade, dupla adesão, quota/entitlement real de FIN/CON e testes |
| **CORE07-D06** | **Modelo A**: substituição atômica do administrador principal, sem sobrepor dois logins administrativos no Básico 1/1 e MFA obrigatório | Protocolo de recuperação do último admin/MFA, comprovação de autoridade, transação/rollback, falecimento/disputa de titularidade, revogação e evidência de segurança |
| **CORE07-D07** | **Modelo B**: redistribuição híbrida; automatizar só conforme proprietário VEN/ATD/AGD e demais contratos; caso contrário, pendência supervisionada | Matriz de recursos/posse, autoridades, regras específicas por módulo, fila/revisão, ausência/desligamento, concorrência e proteção da autoria |
| **CORE07-D08** | **Modelo B + D08-A/B/C/D**: revogação seletiva com revalidação de novos efeitos pessoais e continuidade de operações empresariais legitimamente autônomas | Contrato de autoridade causal em jobs/exports, invalidação de sessões/tokens/downloads, política de cache, tratamento de operações pendentes e testes multiempresa |
| **CORE07-D09** | **A/B/C/D**: auditoria central CORE06 de convites, memberships, equipes, transferências e revogações | Catálogo real de eventos por owner, fontes e versões, controle de leitura/exportação de trilhas, retenção fundamentada/LGPD, integridade/provas de auditoria |
| **CORE07-D10** | **A/B/C/D**: matriz por owner, cenários de teste, evidências verificáveis e reconciliação de gates | Catálogo de contratos completo, casos executados, resultados/defeitos/reteste, SHA/ambiente, aceite de QA/Segurança/owners, gate CORE10/INT/SEG |

**Nota de estado:** D01–D03 têm base documental previamente aprovada. **Todos os sete IDs D04–D10 seguem integralmente ABERTOS**, embora as escolhas de princípio ou diretrizes estejam ratificadas. Não há evidência apresentada nesta revisão de que critérios físicos tenham sido implementados/testados/homologados.

## 2. Precedência normativa obrigatória

1. **CORE01/CORE02/CORE04:** o tenant, membership, entitlement, papel, escopo, segurança, MFA, step-up e RLS não podem ser contornados por convites, redistribuição, suporte, automação ou perfil. Política B de grants/denies e administração M2 do CORE02 são mantidas.
2. **CORE03/CORE09/INT:** jobs e agentes só executam com identidade e autoridade técnica legítimas; revogação de usuário requer nova avaliação para efeitos pessoais, sem cancelar automaticamente serviço autônomo legítimo.
3. **CORE06/CORE10/SEG:** evidências e trilhas usam mecanismos centrais, sem motor duplicado e sem confundir documentação com execução real de testes ou aprovações de segurança.
4. **CORE08/FIN/CON:** assentos e planos observam entitlement confirmado, não o número de convites, profissões de agenda ou perfis; Básico 1 usuário MFA, trial 2 usuários, demais quotas e valores dependem das decisões comerciais competentes. Nenhum ato de convite/transferência adquire adicional ou altera cobrança automaticamente.
5. **VEN:** preservar distribuição configurável de leads sem atendimento válido; não redistribuir por atraso uma interação que já recebeu atendimento válido. **ATD:** origem mantém responsabilidade até aceite/início efetivo do destino; desconexão curta não produz transferência. **AGD:** confirmar destino antes de liberar agendamento original; não cancelar por mera ausência de confirmação.
6. **Portal/Sites:** contrato de site/benefício CRM não altera o mecanismo de adesão ou os direitos entre tenants; migração/portabilidade não ocorre implicitamente por transferência de funcionário.

## 3. Plano de fechamento documental, sem atos executáveis

| Ordem lógica | Pacote de esclarecimento | Quem deve validar a definição | Evidência de encerramento futura |
|---|---|---|---|
| **1** | CORE01/02/04/07: estados, identidades, competências, quotas/assentos e fluxo de convites/transf. admin | Produto CRM, Segurança, FIN/CON e donos de identidade/autorização | Contratos por recurso + matriz de risco/MFA + versão e aceite de owner |
| **2** | CORE03/07-D07/D08 + VEN/ATD/AGD: autoridade causal, redistribuições e revogações em filas/exportações | Produto CRM, proprietários VEN/ATD/AGD/AUT, Segurança/INT | Contratos de eventos/autoridade, estados/transições, critérios de erro e contingência |
| **3** | CORE06/07-D09: catálogo e trilhas reutilizadas, integridade, acesso, retenção e privacidade | Auditoria Geral, Segurança, responsáveis LGPD, INT | Matriz de eventos, classificação, retenção/expurgo por autoridade, aceite de owner |
| **4** | CORE07-D10 + CORE10: cenários, owners, bloqueios e evidências de execução futura | QA, Segurança, Operações, owners CORE/INT/FIN/CON | Casos executados em ambiente aprovado, evidências por SHA/ambiente e decisão de homologação |
| **5** | Reconciliação de gates transversais CORE/SEG/INT/ARQ/AGT/FIN/CON | Responsáveis nomeados por gate e aprovação final específica | Documento GO/NO-GO competente, somente após evidências; **não é produzido por esta revisão** |

A sequência é uma priorização documental. Não designa nomes pessoais de owners, não contrata testes e não concede permissão para alterar o fast-track Codex.

## 4. Bloqueios e provas indispensáveis

**Não promover / não homologar** enquanto houver qualquer risco crítico sem prova de correção, entre outros: (a) leitura/export de tenant errado; (b) aceite de token revogado ou por destinatário distinto; (c) dupla reserva/ocupação ou Básico com dois admins; (d) transferência administrativa sem MFA; (e) redistribuição automática que desrespeite o owner ou apague histórico; (f) export/download após revogação sem nova autorização; (g) artefato/log contendo segredo ou token; (h) ausência de recuperação segura após falha atômica; (i) ausência de rastreabilidade oficial.

**No encerramento futuro**, exigir documento com ID de requisito, controle/owner funcional-técnico, critério de aceite, dependências, versão/SHA do código testado, ambiente, casos e resultados reais, exceções, evidência de defeitos/reteste, aprovação de Segurança/QA/Finanças/Integrações conforme aplicável e decisão formal de homologação. Referência a PR, suite proposta ou CI verde isoladamente não prova fechamento.

## 5. Identificadores e gates ainda abertos

A [matriz técnica da Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) mantém **67 identificadores técnicos pendentes** (CORE01-D04..D07 e CORE02..CORE10-D04..D10); este número se refere ao **fechamento técnico**, e não à quantidade de propostas já ratificadas. Inclui CORE07-D04..D10.

Permanecem abertos SEG-01/02, INT P0/G01..G06, ARQ/AGT, FIN/CON, LGPD, gates CORE e autorização de implantação. Não converter a conclusão das diretrizes preliminares em fechamento técnico, liberação de merge ou autorização de deploy.

**Controle de alterações:** documentação somente na branch `docs/core02-d06-ratificacao-d07-pauta-2026-11-05`, associada ao PR #21 **DRAFT/OPEN/UNMERGED**, sem tocar a `main`, a branch Codex `dev/fast-track-crm-2026-11-04`, CI, VPS ou produção.

## 6. Próximo encaminhamento

Em vez de abrir novas decisões genéricas sobre requisitos já ratificados, o próximo passo útil é **priorizar e reconciliar os contratos concretos de CORE07-D04/D05/D06 com CORE01/02/04/08/FIN/CON**, documentando o que é normativo, o que requer proprietário e o que exige prova futura. Essa tarefa pode produzir uma matriz técnica para apreciação, **sem fechar** os gates e sem alterar código.
