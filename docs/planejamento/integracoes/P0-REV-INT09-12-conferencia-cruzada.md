# P0-REV-INT09-12 — Conferência cruzada INT-09 a INT-12

**Data:** 2026-11-02.
**Estado:** conferência documental cruzada realizada. **Não** é homologação técnica e **não** encerra P0-DOC-01.
**Fontes:** aprovações do conjunto INT-09 a INT-12 presentes no planejamento conversacional deste projeto (sem endereço/identificador imutável dessas mensagens na documentação GitHub), sínteses INT-09..12, regras INT-R01/R02 e documentos IN relacionados no repositório.
**Fase:** documentação somente. Não iniciar programação, testes em ambiente real, implantação, contratações, acesso à produção ou novas automações.

## 1. Documentos correlatos examinados

- [INT-09](INT-09.md), [INT-10](INT-10.md), [INT-11](INT-11.md), [INT-12](INT-12.md).
- [INT-R01](INT-R01-governanca-contratos-homologacao.md) e [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- [IN-159 — Central de Observabilidade](../IN-159-central-inteligente-observabilidade-saude-operacional.md).
- [IN-160 — Logs, métricas e rastreamento distribuído](../IN-160-gestao-inteligente-logs-metricas-rastreamento-distribuido.md).
- [IN-162 — Política de retenção de logs](../IN-162-politica-inteligente-retencao-armazenamento-logs.md).
- [IN-167 — Arquitetura dos agentes técnicos](../IN-167-consolidacao-arquitetura-agentes-tecnicos.md) e [IN-168 — Pendências dos agentes técnicos](../IN-168-matriz-pendencias-fechamento-arquitetura.md).
- [IN-151 — Credenciais e chaves](../IN-151-gestao-inteligente-credenciais-chaves-integracao.md).
- [IN-148 — Reconciliação de leads](../IN-148-reconciliacao-inteligente-leads.md) e [IN-149 — Confirmação de entrega](../IN-149-protocolo-entrega-confirmacao-leads.md).

## 2. Matriz de conferências (escopo, competência e efeitos)

| ID | Módulo(s) | Critério de cotejo | Resultado |
|---|---|---|---|
| P0-RV27 | INT-09 | Modelos e instâncias possuem IDs, tenant, versões e estados distintos; mudança de modelo não altera instância ativa silenciosamente | Preservado na síntese; matriz granular dos estados/versões ainda incompleta |
| P0-RV28 | INT-09 | Estados separados de processo solicitado/autorizado/programado/iniciado/parcial/suspenso/bloqueado/compensação/reconciliado/concluído/ressalvas; de etapa, comando, aprovação e compensação | **Lacuna de detalhamento da síntese:** enumerações completas estão na deliberação aprovada, não no arquivo resumido |
| P0-RV29 | INT-09 | Dependências, caminhos condicionais/paralelos e convergência baseada em confirmação oficial; iterações delimitadas | Diretriz preservada; critérios de elegibilidade, parada e convergência ainda precisam tabela |
| P0-RV30 | INT-09 | Revalidar autorização por tenant, identidade, conexão, módulo, recurso, campo, operação e finalidade nas fronteiras e retomadas | Consistente com INT-R01, sem autorizador duplicado; matriz por operação pendente |
| P0-RV31 | INT-09 | Comando enviado, aceito, aplicado e confirmado são marcos diferentes; timeout após transmissão pode exigir reconciliação | Coerente em INT-R01/02 e IN-149; não presumir HTTP/n8n/webhook como conclusão |
| P0-RV32 | INT-09 | Compensação não é rollback universal nem estorno/cancelamento/desprovisionamento livre; atos proprietários permanecem nos domínios | Coerente; plano tipado por efeito/reversibilidade/alçada ainda pendente |
| P0-RV33 | INT-09 | Contingência externa somente homologada/compatível/autorizada; IA não seleciona provedor financeiro/contratual crítico por conta própria | Preservado; regras específicas de substituição continuam pendentes |
| P0-RV34 | INT-10 | Saúde técnico-operacional, diagnóstico, incidentes e recuperação especializados reutilizam IN-159/160, Motor de Eventos, filas e serviços centrais | **Ponto de não duplicação:** não criar Central de Observabilidade/Incidentes paralela |
| P0-RV35 | INT-10 | Estados saudável/degradado/indisponível/sob investigação/recuperação/aguardando intervenção/verificação/normalizado/ressalvas | **Lacuna de detalhamento da síntese**; faltam tabela de transições e relação com fontes observadas |
| P0-RV36 | INT-10 | Telemetria ausente ou antiga não comprova saúde; UI e modelo IA indisponíveis não podem cessar alertas essenciais | IN-159/160/167 confirmam regra correlata |
| P0-RV37 | INT-10 | Ações automáticas somente seguras, previamente homologadas e escopadas; criticidade de intervenção exige autoridade e aprovação humana aplicável | Compatível com IN-167: execução técnica N2 homologada ≠ IA tomar decisão autônoma |
| P0-RV38 | INT-10 | Retentativa de escrita com resultado inconclusivo depende de reconciliação; normalização do provedor ≠ conclusão dos processos afetados | Coerente com INT-09 e IN-148/149 |
| P0-RV39 | INT-11 | Parceiro, provedor, conector, conexão são objetos distintos; INT-11 publica/homologa, INT-01 cadastra conexão, INT-02 autoriza | Coerente; fronteiras e licenciamento ainda precisam cadastro detalhado |
| P0-RV40 | INT-11 | Estados de catálogo, homologação, publicação, restrição, suspensão, descontinuação e revogação por versão | **Lacuna de detalhamento da síntese**; versões homologadas não tornam automaticamente nova versão autorizada |
| P0-RV41 | INT-11 | Sem código livre publicado por terceiros; parceiros podem submeter para homologação, não publicar em produção autonomamente | Coerente com aprovação e diretrizes de segurança; marketplace futuro não é lançamento aprovado |
| P0-RV42 | INT-11 | Cobranças pertencem FIN/CON; catálogos e condições de parceiros não geram automaticamente preço ou contratação | Coerente; parâmetros comerciais de conectores ficam para revisão posterior |
| P0-RV43 | INT-12 | Auditoria especializada é vista/correlação sobre **Auditoria Geral**, não um banco de auditoria ou trilha paralela | Coerente e obrigatório; Analytics mantém indicadores e relatórios oficiais |
| P0-RV44 | INT-12 | Linha do tempo por comando, operação, conexão, tenant, tentativas e fontes, com resultados inconclusivos registrados sem inventar confirmação | Coerente; campos e proveniência exata permanecem para especificação |
| P0-RV45 | INT-12 | IN-162 estabelece categorias de **logs técnicos** (7/30/30/90/180 dias conforme classe) e não define automaticamente retenção dos fatos auditáveis de negócio | **Dependência de classificação:** não reutilizar prazo de log de debug para evidência obrigatória de contrato/financeiro |
| P0-RV46 | INT-12 | Exportações e consultas auditáveis, privacidade, RBAC/MFA aplicável, IA consultiva, auditoria da auditoria | Coerente; detalhamento por perfil/campo e prazo de evidência ainda pendente |
| P0-RV47 | INT-09..12 | Separar operações internas da Imagine das instâncias de cada tenant e manter n8n sem superprivilégios | Coerente com INT-R01 e IN-167/168; configuração técnica do autorizador pendente |
| P0-RV48 | INT-09..12 | Essenciais Básico Cortesia e trial preservados; arquivos 1/5/20/50 GB, avisos 80/95 e restrição a novos anexos em 100%, sem teto rígido de logs estruturados obrigatórios | Diretriz preservada; distribuição comercial de recursos avançados continua revisão posterior |

## 3. Principais lacunas DOCUMENTAIS (não novas regras)

### INT-09
- **Granularidade de estados:** a síntese omite a lista completa da deliberação aprovada para modelo, instância, etapa, comando, aprovação, compensação e encerramento fundamentado.
- **Convergência e paralelismo:** documentar critérios de não dispensar etapa obrigatória pelo sucesso de etapa independente; limites de iteração e prevenção de ciclo inválido.
- **Autorizações e compensações:** catalogar por operação e módulo proprietário, com estados, elegibilidade, reversibilidade, efeitos confirmados e tratamento de resultados desconhecidos.
- **Atores:** Laravel, n8n e serviços centrais atuam somente nas competências aprovadas. Comunicação ao cliente exclusivamente COM, eventos e datas nos motores centrais.
- **Sem garantia exatamente-uma-vez distribuída**; outbox/filas/checkpoints/reconciliação quando aplicável.

### INT-10
- **Estados de saúde:** completar estados específicos e critérios de aferição (telemetria ausente = desconhecido, não saudável).
- **Níveis de recuperação:** técnica automática segura; assistida/condicionada; operação crítica sob módulo competente. Não confundir níveis funcionais INT-10 com autonomia N1/N2/N3 dos agentes técnicos, embora devam ser compatíveis.
- **Central única:** IN-159/160 já governam observabilidade técnica da Imagine; INT-10 usa essa capacidade sem manter painel de saúde ou central de incidentes concorrente.
- **Provedor restabelecido ≠ processo concluído.** Reconciliação antes da escrita e retomada gradual após revalidação.

### INT-11
- **Homologação versionada:** completar critérios de aceitação e estados de conector por versão, impacto sobre conexões ativas e política de descontinuação.
- **Objetos distintos:** parceiro ≠ provedor ≠ conector ≠ conexão; disponibilização comercial ≠ autorização.
- **Sem marketplace lançado nem publicação autônoma por terceiros.** Alternativas e integrações listadas nas propostas são ilustrativas, não fornecedores já homologados.
- **FIN/CON:** cobrança/contratos permanecem em seus domínios; IA não aprova versões, custos ou publicação.

### INT-12
- **Auditoria Geral é fonte de verdade.** INT-12 cria recursos de consulta, correlação e investigação especializados, sem trilhas paralelas nem retenção concorrente.
- **Aprovação humana, execução e confirmação externa são eventos diferentes.** Complementos a evidências devem permanecer rastreáveis.
- **Retenção:** IN-162 estabelece prazo de logs técnicos; retenções de atos de negócio, evidências legais e históricos estruturados precisam classificação e política própria.
- **Autosserviço de auditoria:** exportações por tenant/perfil/escopo, mascaramento, finalidade e auditoria da própria auditoria.
- **IA produz interpretações, não evidências, estados ou decisões oficiais.**

## 4. Riscos de interpretação que devem ser evitados
1. Tratar a recomendação de IA como autorização para executar. Um executor determinístico pode aplicar um playbook seguro, homologado e previamente autorizado no nível técnico correspondente, conforme IN-167, sem que a IA receba poderes próprios.
2. Repetir ações financeiras/contratuais por timeout ou trocar provedor crítico automaticamente por hipótese de IA.
3. Instalar serviços de monitoramento, tarefas, aprovações, eventos ou auditoria paralelos aos existentes.
4. Aplicar uma tabela de retenção de logs técnicos a todos os registros de auditoria do CRM.
5. Concluir que a INT-R02 homologou implementações ou que os exemplos de conectores são integrações existentes.

## 5. Proveniência e pendências abertas
| Item | Escopo | Evidência necessária | Estado |
|---|---|---|---|
| P0-LIT-09 | INT-09 | Vincular a deliberação integral e versionar todas as enumerações/exceções | Parcial: aprovação detalhada disponível no diálogo; URL/ID imutável não registrado |
| P0-LIT-10 | INT-10 | Vincular proposta integral e aceite expresso, completar estados e políticas | Parcial; referência externa à mensagem não registrada |
| P0-LIT-11 | INT-11 | Vincular proposta integral e aceite expresso, completar estados/versionamento | Parcial; referência externa à mensagem não registrada |
| P0-LIT-12 | INT-12 | Vincular proposta integral e aceite expresso, completar categorias de evidência e retenção | Parcial; referência externa à mensagem não registrada |
| P0-COMP-01 | INT-09/10 | Matriz de compensação/reconciliação por operação proprietária | Pendente de especificação |
| P0-OBS-01 | INT-10 × IN-159/160/162 | Unificar consumo de telemetria sem duplicar centrais | Diretrizes coerentes; contrato de consumo pendente |
| P0-HOMO-01 | INT-11 | Estados de homologação e compatibilidade por versão/conexão | Pendente de especificação |
| P0-AUD-01 | INT-12 | Taxonomia de evidências e retenção por domínio/finalidade | Pendente de classificação e critérios legais |
| P0-ACC-09-12 | HT-08..HT-18 | Planos de teste, pré-condições, evidências e aceite | Planejados, não executados |

## 6. Resultado
- [x] Revisão cruzada INT-09 a INT-12 concluída como **amostra documental** com **22 pontos** P0-RV27 a P0-RV48.
- [x] Lacunas de detalhamento e sobreposições potenciais documentadas.
- [ ] Texto integral de todas as aprovações com vínculo de fonte e cotejo literal arquivado (P0-DOC-01).
- [ ] Contratos e estados granulares, permissões e evidências por operação.
- [ ] Aceite documental final; nenhum teste técnico ou programação realizada.

**Próximo artefato:** matriz de reconciliação transversal P0 dos 12 blocos e INT-R01/R02, com dependências e critérios de passagem G01 a G06. Não marcar pendências como concluídas sem comprovação.