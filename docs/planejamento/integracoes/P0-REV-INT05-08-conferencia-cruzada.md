# P0-REV-INT05-08 — Conferência cruzada de INT-05 a INT-08

**Data:** 2026-11-02.
**Situação:** revisão cruzada DOCUMENTAL INICIAL; **não** conferência literal exaustiva dos textos integrais de aprovação, **não** validação técnica e **não** homologação.
**Fase:** somente planejamento e versionamento. Sem novas automações, programação, contratação, execução de testes ou alteração de produção.

## 1. Fontes consultadas e limites

### Documentos principais
- [INT-05](INT-05.md), [INT-06](INT-06.md), [INT-07](INT-07.md), [INT-08](INT-08.md), ainda com natureza de sínteses iniciais.
- [INT-R01](INT-R01-governanca-contratos-homologacao.md), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [rastreabilidade P0](INT-P0-rastreabilidade-requisitos.md), [matriz de autoridades](INT-P0-autoridades-contratos.md).

### Fontes relacionadas, com decisões próprias
- [IN-118 — Central de Backup e Portabilidade de Dados](../IN-118-central-backup-portabilidade-dados.md).
- [IN-122 — Múltiplos Sites, Contas CRM e Cortesias](../IN-122-multiplos-sites-contas-crm-cortesias.md).
- [IN-123 — Painel de Integrações Sites/CRM](../IN-123-painel-integracoes-sites-crm.md).
- [IN-124 — Integração dos Formulários e Leads](../IN-124-integracao-inteligente-formularios-leads.md).
- [IN-125 — Gestão de Formulários](../IN-125-gestao-inteligente-formularios.md).
- [IN-127/128 — Filas de Leads](../IN-127-retencao-seguranca-filas-leads.md) e [limpeza/auditoria](../IN-128-limpeza-segura-auditoria-filas-leads.md).
- [IN-147 — Exclusão de Leads Não Entregues](../IN-147-exclusao-leads-nao-entregues-apos-18-dias.md).
- [IN-148 — Reconciliação](../IN-148-reconciliacao-inteligente-leads.md).
- [IN-149 — Protocolo de Entrega e Confirmação](../IN-149-protocolo-entrega-confirmacao-leads.md).
- [IN-150 — Segurança de Formulários e APIs](../IN-150-seguranca-inteligente-formularios-apis.md).
- [IN-151 — Credenciais e Chaves](../IN-151-gestao-inteligente-credenciais-chaves-integracao.md).
- [IN-162 — Retenção de Logs](../IN-162-politica-inteligente-retencao-armazenamento-logs.md).

**Limitação da evidência:** os textos integrais das aprovações individuais de INT-05 e INT-06 não foram encontrados nas fontes examinadas. Os textos das aprovações detalhadas de INT-07/08 não foram confrontados literalmente em sua integralidade; o projeto deve preservar os detalhes aprovados, mas sem declarar revisão textual concluída. Títulos dos arquivos são rótulos de consolidação e não certificado do título original. A conferência P0-DOC-01 permanece aberta.

## 2. Matriz de resultados

| ID | Cruzamento | Resultado encontrado | Estado |
|---|---|---|---|
| P0-RV11 | INT-05 × INT-02/04/Identidade e Segurança | Aplicação autorizada/API não substitui autenticação, autorização, operações tipadas e decisões proprietárias | Coerente na síntese; operações/contratos por plano pendentes |
| P0-RV12 | INT-05 × trial | API pública desabilitada no trial; integrações nativas autorizadas não devem ser desabilitadas por essa regra | Requisito funcional preservado |
| P0-RV13 | INT-05 × IN-150/151 | API interna autenticada e formulário público têm fronteiras distintas; não expor segredos em frontend ou conceder acesso por identificador público | Regra correlata documentada |
| P0-RV14 | INT-06 × Motor Central de Eventos | INT-06 é a fronteira de webhooks externos; Eventos central mantém eventos oficiais internos e seus contratos | Coerente; esquema, semântica de confirmação e fluxo por origem pendentes |
| P0-RV15 | INT-06 × IN-149/148 | Recebimento de evento/HTTP 2xx ou 202 não significa que o CRM persistiu o dado; requer comprovação no módulo proprietário | Evidência positiva nos fluxos de leads |
| P0-RV16 | INT-06 × COM | Webhook técnico não autoriza mensagem ao cliente; comunicações dirigidas continuam na COM | Fronteira preservada |
| P0-RV17 | INT-07 × INT-08 | INT-07 governa sincronizações contínuas/checkpoints; INT-08, operações delimitadas de migração/importação/exportação | Competências distintas; granularidade de tarefas e transferências pendente |
| P0-RV18 | INT-07 × fonte oficial/identidade | Autoridade por campo; ausência de dado não equivale a exclusão, nem 'último escritor' é regra universal; conflitos ficam explícitos | Coerente com síntese e governança; regras por entidade ainda pendentes |
| P0-RV19 | INT-07 × IN-124/148/149 | Idempotência e checkpoint não autorizam presumir entrega ao CRM; retorno inconclusivo exige investigação antes de reescrita | Regra especializada em leads preservada |
| P0-RV20 | INT-08 × IN-118 | **Todos os planos, inclusive Básico**, têm autoatendimento essencial: exportação/importação CSV/XLSX, backup completo para download, restauração compatível, migração entre contas autorizadas e histórico, sem cobrança adicional por operação | **Lacuna de referência explícita na síntese INT-08; não é alteração comercial** |
| P0-RV21 | INT-08 × IN-118 | Exportação simples ≠ backup completo. Restauração em conta não vazia exige análise de conflitos e ação controlada; não migrar credenciais/segredos/financeiro de outra empresa | Regras documentadas; limites de portabilidade e schema pendentes |
| P0-RV22 | INT-08 × IN-122/123 | Troca de conta CRM vinculada requer chamado e alteração técnica gratuita pela Imagine; migração de dados é autoatendimento do cliente, não parte do serviço gratuito | Regra comercial/operacional preservada |
| P0-RV23 | INT-08 × franquias/limites | 1/5/20/50 GB, avisos 80/95%, bloqueio só de novos uploads de anexos em 100%; exportação/backup de dados existentes não pode ser impedida pela franquia cheia | Regra aprovada em IN-118 e transversais |
| P0-RV24 | INT-08 × trial | Importação CSV no trial permitida; API pública continua desabilitada; exigências de permissão e limites técnicos ainda valem | Regra preservada |
| P0-RV25 | INT-06/07/08 × IN-127/128/147/162 | Prazo 3+15 dias, descarte condicionado após 18, limpeza 24h e auditoria 90 dias pertencem às filas temporárias de **leads dos Sites Imagine**, não à retenção global das INTs | Escopos segregados |
| P0-RV26 | INT-05..08 × INT-R01/R02 | Todas reutilizam Serviços Centrais (arquivos, eventos, aprovações, filas, auditoria, COM) e registram causalidade, autorização e confirmação proprietária | Coerência transversal na amostra, contratos detalhados abertos |

A revisão não revelou contradição inequívoca entre os documentos consultados. Há lacunas de referência e detalhamento; **ausência de conflito nesta amostra não prova validação integral**.

## 3. Observações particulares

### INT-05 — Aplicações, API e acesso programático
- Regra vigente do trial: API pública desabilitada; integrações nativas autorizadas disponíveis conforme as funcionalidades do trial.
- Credenciais, escopos e revogações permanecem INT-02/Identidade e Segurança; contratos de operações tipadas em INT-04. INT-05 registra e governa a aplicação e interface pública, sem criar acesso direto a dados de negócio.
- Formulários de Sites Imagine e APIs internas de recepção (IN-124/150) não devem ser confundidos com liberação de API pública do SaaS.
- Pendente: texto original completo e título literal, distinção de planos, rate limits e permissões por endpoint.

### INT-06 — Webhooks e entregas externas
- Eventos internos oficiais continuam com Motor Central de Eventos. INT-06 lida com assinatura, autenticação, deduplicação e entrega de webhooks na fronteira externa.
- Recebimento, entrega, aceite técnico, aplicação e confirmação de negócio são marcos diferentes; respostas ou eventos assíncronos exigem verificação da fonte oficial.
- Não assumir que webhooks externos dispensam INT-02 ou autorizam mensagens aos clientes fora de COM.
- Pendente: texto original completo e título literal, garantias e contratos de assinatura, versionamento, códigos, tentativas e estados.

### INT-07 — Sincronização contínua
- Propriedade de checkpoint, cursor, conflitos, recomposição e sincronização contínua; INT-08 detém movimentações delimitadas.
- Autoridade por campo, estado original e versão validada; não equiparar ausência em origem a instrução de excluir, nem aplicar regra genérica de sobrescrita por timestamp.
- Resultado inconclusivo deve ser reconciliado antes de repetir gravações perigosas; instâncias precisam de identidade/versão e autorização vigente.
- Pendente: recuperação literal do título e das operações aprovadas, limites de loops, direção, concorrência, origem e destino por entidade.

### INT-08 — Importações, exportações e migrações
- IN-118 garante a **Central de Backup e Portabilidade por autoatendimento**, inclusive no Básico, com exportação e importação CSV/XLSX, pacote completo de backup, restauração compatível, migração entre contas autorizadas e histórico. INT-08 deve apoiar essas operações delimitadas e respeitar sua governança, **sem automaticamente assumir a propriedade integral da Central de Backup**.
- Atingir 100% da franquia de anexos impede apenas **novos uploads de anexos**, não exportação ou download dos dados existentes. Os limites técnicos de pacote temporário, processamento e taxa serão dimensionados separadamente.
- Não copiar credenciais, tokens/segredos ou faturamento vinculado ao contrato de outro tenant; dados, anexos e históricos portáveis exigem manifesto/versionamento e revisão apropriada.
- Restauração em conta com dados exige compatibilidade, análise de conflitos, prévia sem escrita, permissão e decisão legítima; nenhuma mesclagem destrutiva automática.
- Mudança de vínculo de site por chamado é gratuita somente para a alteração técnica; a migração de dados cabe ao cliente pelas ferramentas do CRM.
- Pendente: texto original integral, manifestos e classes de dados portáveis, limites, recuperação, retenções e confirmação de resultado.

## 4. Cautela sobre títulos documentais
Os arquivos atuais usam os títulos resumidos “Aplicações Integradas e API Pública” (INT-05), “Webhooks, Assinaturas e Entregas de Eventos Externos” (INT-06), “Sincronizações Contínuas e Checkpoints” (INT-07), e o título expandido de INT-08. Esses nomes não comprovam, isoladamente, a nomenclatura literal aprovada. Antes do fechamento P0-DOC-01, conferir decisões originais e normalizar título/alias no índice sem alterar significado funcional.

## 5. Pendências para fechamento
| ID | Próxima ação | Estado |
|---|---|---|
| P0-LIT-05 | Verificar aprovação integral e título original INT-05 | Pendente de fonte original |
| P0-LIT-06 | Verificar aprovação integral e título original INT-06 | Pendente de fonte original |
| P0-LIT-07 | Confrontar texto detalhado original e título INT-07 | Pendente de cotejo literal |
| P0-LIT-08 | Confrontar texto detalhado original e título INT-08 | Pendente de cotejo literal |
| P0-XREF-INT08-118 | Relacionar formalmente INT-08 à IN-118 sem duplicar competências; preservar garantia de portabilidade no Básico | **Referência e lacuna mapeadas; conferência de todos os requisitos pendente** |
| P0-CON-INT05-08 | Especificar contratos de aplicação, webhook, sync, migração, confirmação, idempotência e versionamento | Pendente |
| P0-SEC-INT05-08 | Revisar permissões por operação/tenant, download de dados, revogação e segregação | Pendente |
| P0-HT-INT05-08 | Completar critérios de teste HT-03 a HT-08 e HT-13/16/17; sem executar testes | Pendente |

## 6. Regra de saída
- [x] Fontes de INT-05 a INT-08 e documentos correlatos examinados.
- [x] Conferência cruzada e lacuna específica INT-08/IN-118 documentadas.
- [ ] Recuperar/conferir literalmente cada aprovação original, com referência e versão.
- [ ] Completar vínculos de requisitos TR por origem, contrato, propriedade, inv HT e aceite.
- [ ] Resolver divergências confirmadas e obter aceite documental final.

**Resultado:** revisão cruzada concluída nesta amostra, **P0-DOC-01 não encerrado**. Próximo grupo recomendado: INT-09 a INT-12 e reconciliação transversal de P0.
