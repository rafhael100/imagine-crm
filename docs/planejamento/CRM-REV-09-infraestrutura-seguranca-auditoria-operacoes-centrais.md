# CRM-REV-09 — Infraestrutura, Segurança, Auditoria e Operações Centrais

**Data:** 2026-11-03. **Estado:** **MINUTA DOCUMENTAL PARA REVISÃO — NÃO APROVADA**. Preparada após o [aceite da CRM-REV-08 como base documental](CRM-DEC-aprovacao-REV-08-base.md). Esse aceite **não aprova a CRM-REV-09 antecipadamente**.
**Objetivo:** fechar a ponte entre módulos funcionais CRM e o **gate real de prontidão para programar**, sem prolongar aprovações genéricas nem ignorar os seis bloqueios P0 de IN-168. O CRM completo ainda não foi declarado pronto.
**Fase:** somente análise e documentação; **não programar, executar testes físicos, instalar, criar contas, migrar, contratar ou alterar produção**.
**Método:** registrar diretrizes já aprovadas, fatos distintos e lacunas de especificação com fonte. Não criar papéis, alçadas, provedores, SLAs ou orçamento por suposição.

## 1. Fontes verificadas

| Ref | Fonte | Escopo |
|---|---|---|
| LEG-01 | [01 — Visão geral e arquitetura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md) | Laravel/PostgreSQL, RLS, multiempresa e direitos por domínio |
| LEG-03 | [03 — Administração Geral](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md) | MFA central, acesso excepcional, ações administrativas e auditoria |
| LEG-04 | [04 — Empresas/Usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | RBAC por empresa, convite, MFA, registros e auditoria comercial |
| LEG-06 | [06 — Infraestrutura e operações](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/06-infraestrutura-e-operacoes.md) | Ambientes separados, backups, armazenamento privado e monitoramento |
| CORE | [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) | Base documental de identidade e 10 lacunas CORE-P0 |
| ARCH | [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md) | Oito papéis técnicos; N1/N2/N3 e fronteiras entre serviços |
| ARQ | [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) | ARQ-01/02/03, SEG-01/02 e AGT-01 P0; outras pendências P1/P2 |
| CHOICE | [ARQ-01 — comparativo](ARQ-01-comparativo-infraestrutura-agentes-tecnicos.md) | Opção B aprovada como diretriz, sem escolha física de fornecedor/tamanho |
| SECRETS | [IN-151](IN-151-gestao-inteligente-credenciais-chaves-integracao.md) | Cofre de segredos, rotação, identidade de serviço e autorização |
| OPS | [IN-159](IN-159-central-inteligente-observabilidade-saude-operacional.md) | Central de Observabilidade e monitoramento independente |
| LOGS | [IN-162](IN-162-politica-inteligente-retencao-armazenamento-logs.md) | Retenção categorizada de telemetria e exclusão segura |
| CONT | [IN-143](IN-143-continuidade-operacional-recuperacao-inteligente-desastres.md) | Recuperação e contingência por sistema |
| APPROVAL | [IN-137](IN-137-central-aprovacoes-tecnicas.md) | Aprovação humana única quando exigida, sem dupla aprovação |
| INTGATE | [P0-GATE INT](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) | G01..G06 ainda incompletos; contratos/proveniência/autorizações pendentes |
| INTSEC | [P0-SEC INT](integracoes/P0-SEC-politicas-autorizacao-CT02.md) | Base documental de CT-02, sem grant físico de permissões |

## 2. Diretrizes e regras aprovadas identificadas (34 itens de cotejo)

| ID | Área | Regra e limitação preservada |
|---|---|---|
| OPS-R01 | Arquitetura SaaS | CRM em backend Laravel/PostgreSQL, SaaS multiempresa; tenant_id, aplicação Laravel e PostgreSQL RLS como camadas complementares. |
| OPS-R02 | Produção e homologação | Ambientes separados, credenciais distintas e dados fictícios/anonimizados em homologação; sandbox para pagamentos. |
| OPS-R03 | Fluxo de publicação | Desenvolvimento→homologação→aprovação→produção por autorizado; rastrear versão, responsável, horário e rollback validado. |
| OPS-R04 | Separação física principal | Portal Imagine e CRM Imagine em servidores separados, conforme decisão ARQ-01; não compartilhar automaticamente dados e credenciais. |
| OPS-R05 | Agentes e orquestração | VPS/servidor exclusivo para agentes técnicos e orquestrador, separado da VPS n8n (Opção B aprovada), sem servidor/provedor/tamanho contratado. |
| OPS-R06 | Sites e CRM distintos | Sites Onepage/Landing em Cloudflare Workers Static Assets+GitHub (IN-096), não dentro do Laravel/WHM do CRM. |
| OPS-R07 | Resiliência de leads | API principal e API de contingência independentes, persistência durável, idempotência e confirmação oficial somente após CRM atestar gravação. |
| OPS-R08 | Fila limitada de envios | 3 dias fila normal +15 dias falha, 18 total para payload não entregue; auditoria minimizada e exclusão elegível, não expurgo de leads do CRM. |
| OPS-R09 | Autorização multiempresa | Aplicar tenant/permissão efetiva em consultas, uploads, APIs, jobs, filas, agentes e relatórios; não confiar em tenant informado externamente. |
| OPS-R10 | Acesso administrativo excepcional | Admin Geral Imagine não recebe acesso irrestrito a registros do cliente; suporte excepcional exige competência, propósito, escopo, expiração e auditoria. |
| OPS-R11 | MFA por papel | MFA obrigatória para administradores centrais e da empresa; funcionários MFA opcional por padrão e exigível pela empresa; step-up por ato ainda P0. |
| OPS-R12 | Agentes técnicos | Sete agentes especializados e Supervisor, identidades escopadas e sem superprivilégio; IA não cria autorizações via prompts. |
| OPS-R13 | Autonomia segura | N1 observa, N2 executa playbooks seguros homologados/limitados, N3 operações críticas com uma aprovação humana específica quando exigida; sem dupla aprovação. |
| OPS-R14 | Cofre central | Segredos de serviço em cofre protegido, criptografia, rotação, revogação, segregação por ambiente/cliente/agente; nunca em código do site, prompt ou logs. |
| OPS-R15 | Ferramentas autorizadas | Catálogo tipado de ferramentas por agente/ação/ambiente/tenant e mecanismo externo ao modelo; não executar código livre de fornecedor. |
| OPS-R16 | Observabilidade independente | Central única de saúde técnica e alertas que funciona mesmo quando IA, Supervisor ou UI principal falham; telemetria ausente ≠ estado saudável. |
| OPS-R17 | Logs e rastreamento | Correlation IDs e origem do evento por tenant/serviço; proteger conteúdo pessoal/segredos e distinguir telemetria técnica de auditoria de negócio. |
| OPS-R18 | Retenção técnica diferenciada | Debug 7d, logs operacionais 30d, traces 30d, métricas 90d, auditoria filas 90d, eventos de segurança/incidentes críticos 180d (IN-162). |
| OPS-R19 | Auditorias distintas | Auditoria administrativa central, comercial por tenant e logs técnicos têm autoridades, retenções e acesso distintos; administradores de tenant não apagam auditoria. |
| OPS-R20 | Backups CRM | Backup completo diário, incrementais/PITR projetados, cópias criptografadas fora do servidor principal, guarda operacional histórica 30d e restaurações controladas. |
| OPS-R21 | Metas de recuperação | Valores históricos CRM RPO-alvo 15 min/RTO-alvo 4h são objetivos preliminares, não compromissos validados; CON-01 ainda exige RTO/RPO por serviço/custo. |
| OPS-R22 | Backups dos Sites separados | Backups de código/artefatos/configurações Sites e de dados CRM são regimes separados, sem dependência de restauração de um para o outro. |
| OPS-R23 | Armazenamento privado | Arquivos em objeto privado compatível S3, tenant identificado mas prefixo não substitui permissão; acesso via backend e URL assinada curta/revogável. |
| OPS-R24 | Anexos seguros | Limite 5MB, verificação tipo/MIME/assinatura/antimalware, quotas por plano e bloqueio só de novos anexos a 100%. |
| OPS-R25 | Replicação de anexos | Cópia incremental assíncrona de anexos projetada com intervalo-alvo 15min, servidor separado, retenção técnica 30 dias e recuperação controlada. |
| OPS-R26 | Central de incidentes | Incidentes correlacionados, classificação, impacto/risco, um responsável e escalonamento humano; UI não é o sistema de alerta exclusivo. |
| OPS-R27 | Metas de assunção | Metas internas 5 min críticos, 30 min altos, 4h úteis médios, 1 dia útil baixos; são assunção e não solução garantida. |
| OPS-R28 | Monitoramento 24/7 | Monitoramento automatizado contínuo não implica plantão humano permanente; cobertura e custos reais de resposta dependem de decisão OPS-01. |
| OPS-R29 | Gestão de mudanças | Mudanças críticas precisam revisão/autorização, evidência de versão, monitoramento pós-implantação e retorno seguro; sem ação autônoma fora do playbook. |
| OPS-R30 | Recuperação por serviço | Contingência, restauração e reconciliação só após validação de fatos oficiais, sem prometer perda zero ou rollback universal de efeitos externos. |
| OPS-R31 | Custos internos separados | Modelos/agentes técnicos e observabilidade têm orçamento interno Imagine, distinto de cotas e créditos de IA comerciais de empresas clientes. |
| OPS-R32 | Contratos multissistema | INT coordena execução técnica, mas FIN/CON/ATD/VEN/AGD/CRM confirmam negócio; HTTP/n8n/ACK não confirma efeito final. |
| OPS-R33 | Autoatendimento de dados | Export/import/backup/restore essenciais no CRM em todos os planos via IN-118; não depender de suporte Imagine para portabilidade ordinária. |
| OPS-R34 | Requisitos de fechamento | Não declarar arquitetura pronta por possuir fichas aceitas; fechamento de fonte, autoridade, schemas e segurança, gates G01..06 e aprovação global ainda são necessários. |

**Importante:** uma diretriz histórica aprovada não equivale a solução técnica executável. Em particular, o RPO de 15 min e RTO de 4 horas citados no capítulo histórico do CRM eram **metas iniciais**, enquanto IN-168 **CON-01 permanece em aberto para dimensionamento, custo e valores por serviço**.

## 3. Matriz resumida de arquitetura-alvo (sem instalação autorizada)

| Camada | Decisão de fronteira já registrada | Escolha/contrato ainda pendente |
|---|---|---|
| CRM Imagine SaaS | Laravel, PostgreSQL e isolamento multiempresa RLS/aplicação, servidor distinto do Portal | Dimensionamento, provedores, rede, DB, IAM real, CI/CD, HA e contratos |
| Portal Imagine | Backend/ambiente separado do CRM | Interfaces seguras de pagamento, sites, benefícios e CRM |
| Sites Onepage/Landing | Cloudflare Workers Static Assets/GitHub, sem DB de leads nos sites | Publicação por serviço autorizado, DNS/SSL externo e custos |
| API de leads principal/contingência | Persistência durável independente e entrega com prova CRM | Schema, topologia, filas, chave de idempotência e transições |
| n8n | Serviço de orquestração separado do servidor de agentes técnicos | Contratos por workflow/credencial, observabilidade, versionamento |
| Agentes técnicos e Supervisor | 7+1 papéis lógicos em infraestrutura separada do n8n, N1/N2/N3 | Coleta de sinais, identidade de serviço, ferramentas, playbooks, custos |
| Cofre e Segurança | Centralizada com menor privilégio, rotação e auditoria | Provedor, topologia, delegação, HA, recuperação e schemas |
| Observabilidade e Incidentes | Central única; monitor independente da UI/IA | Componentes independentes e políticas de saúde, métricas, custos |
| Backups e anexos | Backup CRM e Sites separados; arquivos privados com controle por tenant | Confirmação do armazenamento, retenções, restauração, RTO/RPO |
| Auditoria Geral | Fonte de trilha oficial e direitos por categoria; logs técnicos distintos | Esquemas, consulta/exportação, integridade, LGPD e retenções |

## 4. Fronteiras de autoridade que impedem retrabalho

- **Identidade e Segurança** autoriza pessoa/agente/serviço por tenant, recurso, campo e ação; uma conexão externa autenticada não atribui direito de negócio.
- **Auditoria Geral** mantém a fonte oficial de evidência/ações; IN-160/162 tratam telemetria/logs técnicos, enquanto CRM mantém históricos funcionais distintos.
- **Central de Aprovações** serve atos técnicos críticos homologados; respeitar uma aprovação humana específica quando exigida, sem inventar segunda aprovação.
- **Central de Incidentes** gerencia casos e escalonamento; Observabilidade diagnostica com sinais independentes, e agentes atuam só em ações autorizadas.
- **Proprietários dos módulos FIN/CON/VEN/ATD/AGD/CAT/CRM** atestam efeitos oficiais; INT/n8n/IA são transportes/orquestrações e não declaram resultado financeiro ou comercial.
- **Portabilidade** IN-118 e backups físicos diferem: backup não concede privilégio global de exportação ou restauração entre tenants.
- **Gratuidade administrativa** de plano, adicionais e pacotes IA existe no módulo comercial/financeiro e é decisão de Administrador Geral autorizado, não da operação técnica ou do suporte.

## 5. Riscos e precedências que exigem cotejo

| ID | Risco identificado |
|---|---|
| OPS-D01 | IN-168 ARQ-01 tem diretrizes arquiteturais aprovadas mas topologia física, provedor, custo, dimensionamento e contingência ainda pendentes. |
| OPS-D02 | RPO 15 min e RTO 4h de legado são metas iniciais do CRM, não SLAs finais; IN-168 CON-01 P1 mantém números por serviço dependentes de custo/arquitetura. |
| OPS-D03 | Armazenamento de anexos/replicação de 15min e backup diário PostgreSQL são objetos diferentes; não unificar RPO de tudo. |
| OPS-D04 | Log técnico 7/30/90/180 dias, auditoria comercial 12 meses e administrativa histórica 5 anos são categorias e regimes distintos. |
| OPS-D05 | Agent N3 tem uma aprovação humana específica quando exigida, não duas nem acesso irrestrito ao aprovar. |
| OPS-D06 | Manutenção emergencial e incidente não autorizam migração, compra, exclusão ou rotação de credencial sem política aplicável. |
| OPS-D07 | Fonte de saúde não pode depender do próprio modelo ou do painel que está monitorando; detectar falha/ausência de dado. |
| OPS-D08 | INT P0-OWN/SEC/CON parcialmente documentados não equivalem a perfis, contratos/roles físicos aprovados. |
| OPS-D09 | Cobertura operacional de CRM, Portal, Sites e agentes não exige implementar os três sistemas completos simultaneamente, mas requer contratos de interface. |
| OPS-D10 | Arquitetura RLS aprovada não resolve a política concreta de migrations, contas de serviço, jobs, suporte excepcional e relatórios. |
| OPS-D11 | Direito de Admin Geral conceder plano/extra gratuito é função administrativa de FIN/CON, não do agente técnico ou suporte. |
| OPS-D12 | Aprovação de documentação IN-168 não aprova respostas P0/soluções de infraestrutura, nem permite instalação ou teste. |
| OPS-D13 | Testes de continuidade e plano HT são aprovados como método; 0 execuções não bloqueiam a escrita de código, mas os critérios/documentos de aceite devem estar planejados. |
| OPS-D14 | Os critérios G01–G06 são gates documentais: G01..04 parciais, G05/G06 pendentes; não confundir com 18 testes HT. |

## 6. Matriz de fechamento com prioridade e fonte — 18 pendências propostas

| ID de revisão | Contrato ou decisão ainda faltante | Origem rastreável | Prioridade para trabalho |
|---|---|---|---|
| OPS-P01 | Fechar ARQ-01 executável: mapa de componentes, trust zones, serviços, servidores, redes, capacidade e custos | IN-168 ARQ-01 | P0 |
| OPS-P02 | Contratos de API principal/contingência, filas, identidade de submissão, confirmação CRM e recuperação | IN-168 ARQ-02 | P0 |
| OPS-P03 | Monitor/alertador independente da IA e do próprio supervisor, saúde de componentes e SLA de telemetria | IN-168 ARQ-03 | P0 |
| OPS-P04 | Matriz exata de ferramentas×ações×tenant×ambiente×agente, token/approvals e revogação | IN-168 SEG-01 | P0 |
| OPS-P05 | Provedor/infraestrutura do cofre, identidades de serviço, rotação, revogação e contingência | IN-168 SEG-02 | P0 |
| OPS-P06 | Playbooks iniciais por risco, versão, limites, autorização, suspensão, monitor e reversão | IN-168 AGT-01 | P0 |
| OPS-P07 | Contratos de IAM real: usuários, equipes, admins, MFA por ação, RLS por tabela/serviço | CRM-REV01 CORE-P0 | P0 |
| OPS-P08 | Matriz de contratos oficiais e versionamento por OP/CT (estados, erro, idempotência, concorrência, compensações) | INT P0-CON/SEC/OWN | P0 |
| OPS-P09 | Proveniência e completude original INT e demais módulos, incluindo versões/aceites | INT P0-DOC/TR e CRM-MASTER | P0 documental |
| OPS-P10 | Modelo de dados/recursos/roles e auditorias por categoria; exportação e retenção legal | CRM-REV01/03; IN-162 | P0 |
| OPS-P11 | Política de provisionamento de benefício gratuito por plano/extra e contratos de FIN/CON | CRM-DEC gratuidade; CRM-REV02 | P0 para faturamento |
| OPS-P12 | Cadeia de CI/CD, política de branches, ambientes, segredos, reversão e aprovador da produção | LEG-06; IN-154 | P0 para produção |
| OPS-P13 | Arquitetura de backup/PITR/restore por banco, anexos e APIs, auditoria dos testes futuros | LEG-06; IN-143/144/145 | P0 para dados |
| OPS-P14 | Valores RPO/RTO numéricos por serviço e orçamento, exceções/infra homologáveis | IN-168 CON-01 | P1 dependente |
| OPS-P15 | Prazos de plantão, substitutos, canais, custo e nível de resposta humana real | IN-168 OPS-01 | P1 |
| OPS-P16 | Modelo e custos de observabilidade, logs/metrics/traces, alertas e retenção | IN-168 OBS-01 | P1 |
| OPS-P17 | Orçamento interno de IA dos agentes técnicos, homologação de modelo/contingência | IN-168 FIN-01/IA-01 | P1 |
| OPS-P18 | Revisão final global, matriz de dependências, donos reais, orçamento, autorização e commit/tag imutável | IN-168 DOC-02; INT G01–G06 | Gate final |

**Atenção à classificação:** OPS-P01..06 correspondem aos **seis P0 já presentes em IN-168** — ARQ-01/02/03, SEG-01/02 e AGT-01 — e **não criam seis novos bloqueios somados**. Outros pontos cruzam CORE-P0/INT-P0 já existentes; propostas P1 não se convertem automaticamente em P0. Revisão de orçamento pode limitar promessas externas, mas não autoriza compras agora.

## 7. Critérios de aceite para validação futura — nenhum executado

| Caso | Resultado a verificar em homologação futura |
|---|---|
| OPS-T01 | Tentativa de usar tenant B em job com contexto A: negar e auditar |
| OPS-T02 | Suporte Imagine sem autorização específica tenta ler lead: negar |
| OPS-T03 | Revogação de credencial com job pendente: impedir novo ato, reconciliar efeito externo anterior |
| OPS-T04 | API principal falha e contingência recebe mesma submissão: uma entrega lógica |
| OPS-T05 | CRM recebe ACK técnico mas não criou lead: não declarar entregue |
| OPS-T06 | Supervisor IA cai: alerta independente continua funcional |
| OPS-T07 | Telemetria para por 20 min: estado desconhecido/alerta, nunca saúde afirmada |
| OPS-T08 | Agente N2 tenta executar playbook não homologado: negar |
| OPS-T09 | N3 crítica sem aprovação humana aplicável: não executar |
| OPS-T10 | Dois atores tentam aprovar ação: evitar dupla execução da mesma autorização |
| OPS-T11 | Backup restaurado em homologação: não carregar credenciais/usuários ativos de produção |
| OPS-T12 | Restauração anexo de tenant B em A: negar, mesmo prefixo S3 manipulado |
| OPS-T13 | 100% da franquia de anexos: negar só novo upload; preservar dados estruturados |
| OPS-T14 | Em incidente com efeitos externos irreversíveis: reconciliar antes de compensação |
| OPS-T15 | Concessão gratuita administrativa encerra: não disparar mensalidade sem contrato |
| OPS-T16 | Log de depuração contendo chave: mascarar, restringir, responder a incidente sem expor segredo |

**Não executar testes nesta fase documental.** Os cenários OPS-T01..16 são planejamentos de homologação, não execuções e não substituem os 18 HT de INT. Os **critérios de teste devem estar planejados** antes de codificar, mas a **execução efetiva** ocorre somente na fase de implementação autorizada.

## 8. Como tornar o projeto apto a iniciar Codex

1. **Fechar P0 que condicionam o núcleo CRM:** topologia de serviços/conexões, identidade/tenant, cofre, autorização/contexto, contratos de dados/eventos/estados e integração financeira/benefícios.
2. **Conferir integralidade e precedência** das decisões funcionais históricas versus REV-01..09 e aprovações comerciais recentes; concluir checklist P0 com dono e evidência de cada decisão.
3. **Especificar contratos e critérios futuros de teste**, delimitar o primeiro pacote de desenvolvimento/versão e dependências de Portal/Sites sem confundir com implementação destes produtos.
4. **Submeter a versão global do planejamento à aprovação expressa**, fixar SHA/tag do pacote aprovado e **só então** iniciar programação com Codex.
5. A execução de HT e ensaios físicos de recuperação pertence à fase autorizada de desenvolvimento/homologação; não exigir testes executados antes da primeira linha de código como precondição contraditória.

**G01–G06 INT permanecem todos incompletos** segundo [painel P0](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md). Aceites de fichas/instrumentos documentais não encerram P0-OWN-01, P0-SEC-01, P0-CON-01 ou auditoria de fonte.

## 9. Próxima aprovação recomendada

**Proposta:** aprovar CRM-REV-09 **como base documental de revisão da arquitetura e dos bloqueios reais**, reconhecendo 34 diretrizes/fatos, 14 riscos, 18 questões de fechamento e 16 cenários futuros. **Não** homologar topologia física, orçamento, cofre, permissões, metas numéricas por serviço, infraestrutura, implantação, testes ou início de Codex.

Depois, o próximo trabalho deve priorizar **fechamento transversal dos P0** em vez de manter revisões funcionais genéricas em cascata, acompanhado da consolidação final por módulo e revisão global de valores/recursos que o responsável programou para o final.

**Status:** CRM-REV-01..08 aceitas como **bases documentais**, CRM-REV-09 **minuta não aprovada**, planejamento global ainda sem liberação de desenvolvimento.
