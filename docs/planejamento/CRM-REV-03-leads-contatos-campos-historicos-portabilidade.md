# CRM-REV-03 — Leads, contatos, campos personalizados, histórico e portabilidade

**Data:** 2026-11-03.
**Status atualizado:** **APROVADA COMO BASE DOCUMENTAL**, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-REV-03-base.md) em 03/11/2026. As 12 diferenças DAT-D01..12 e 12 pendências DAT-P01..12 **continuam abertas**, sem schemas nem direitos executáveis. [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) foi elaborada **como minuta não aprovada**.
**Âmbito:** CRM-06/07/16 e fronteiras com CRM-08/10/14/15/17. Consolidar direitos por entidade e fonte de dados, sem duplicar motores INT, Central IN-118, APIs dos Sites Imagine, Auditoria Geral ou IAM.
**Restrições:** planejamento GitHub somente; não programar, executar testes, migrar dados, criar integrações/contas reais, alterar produção, aprovar custos, capturar leads reais ou contratar serviços.
**Método:** comparar documentos históricos etiquetados como aprovados com regras mais específicas IN e decisões mais recentes. Sínteses históricas ainda não substituem verificação literal completa das propostas e aceites originais; P0-DOC/TR permanece aberto.

## 1. Fontes já localizadas e consultadas

| Ref | Documento histórico/atual | Abrangência |
|---|---|---|
| LEG-04 | [04 — Empresas, usuários, funis e dados](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | CRUD, importação/exportação, campos personalizados, mesclagem, scoring e formulários genéricos do CRM |
| LEG-13 | [13 — Matriz Básico por módulo](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | IN-023..032 e IN-093; ações básicas, duplicidades, dados e privacidade |
| LEG-02 | [02 — Planos/trial](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md) | Cotas e limites por plano; trial 20 leads, 20 MB |
| LEG-08 | [08 — Básico cortesia](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/08-plano-basico-cortesia.md) | Delimitação das funções Basic vs pagos; não estender direitos indevidamente |
| IN-118 | [IN-118 — Central de Backup e Portabilidade](IN-118-central-backup-portabilidade-dados.md) | Autoatendimento de exportação/importação, backup, restauração e migração em todos os planos |
| IN-124 | [IN-124 — Recepção de formulários e leads](IN-124-integracao-inteligente-formularios-leads.md) | Identidade de origem, duas APIs, persistência durável, falhas/duplicidades, vínculo correto |
| IN-125 | [IN-125 — Gestão de formulários](IN-125-gestao-inteligente-formularios.md) | Campos de formulário, mapeamento, consentimento, antispam, origem e versão |
| IN-127 | [IN-127 — Fila de leads](IN-127-retencao-seguranca-filas-leads.md) | Fila normal até 3 dias + falhas até 15 dias, 18 dias totais |
| IN-147 | [IN-147 — Descarte após 18 dias](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md) | Expiração somente de payload de envio não entregue, exceções e auditoria técnica minimizada |
| IN-148 | [IN-148 — Reconciliação de leads](IN-148-reconciliacao-inteligente-leads.md) | Estado, resultado verificável, lead único por envio lógico |
| IN-149 | [IN-149 — Confirmação de entrega](IN-149-protocolo-entrega-confirmacao-leads.md) | Separação da persistência na API da criação/associação efetiva no CRM |
| INT-03 | [INT-03 — Mapeamentos](integracoes/INT-03.md) | Transformação/esquema técnico versionado sem autoridade para escrever dados |
| INT-08 | [INT-08 — Importação, exportação, migração](integracoes/INT-08.md) | Movimentações por lote controladas; não absorver backup/portabilidade IN-118 |
| REV01 | [CRM-REV-01 — Multiempresa/Segurança](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) | RLS e tenant, perfis, autoria, limites do acesso |
| REV02 | [CRM-REV-02 — Planos/Trial](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) | Benefício Básico, quotas de anexos, dados estruturados e trial |

**Regra de leitura:** IN-023..028 delimita direitos específicos do Básico e não restringe automaticamente o CRM pago; IN-118 estabelece portabilidade essencial **para todos os planos**; IN-127/147 define descarte **somente da cópia temporária de envio ainda não entregue**, não do registro já gravado no CRM.

## 2. Regras funcionais preservadas (26 itens de cotejo, não novos requisitos)

| ID | Assunto | Regra localizada | Referências | Ponto a especializar |
|---|---|---|---|---|
| DAT-R01 | Tenancy e propriedade dos dados | Leads, contatos, campos, históricos, relatórios e importações pertencem à organização/tenant; usuário participa de múltiplas organizações sem acesso cruzado. | REV01; LEG-04 | Concretizar escopos por registro/campo, RLS e processo assíncrono; não inferir ID informado pelo cliente. |
| DAT-R02 | Leads e contatos no Básico | Administrador único do Básico pode visualizar, cadastrar, editar, pesquisar, filtrar, adicionar observações/histórico manual e arquivar preservando histórico. | LEG-13 (IN-023) | Recursos de funis/automação premium continuam conforme seus próprios módulos e planos. |
| DAT-R03 | Volume de leads e contatos Básico | Sem teto comercial rígido de quantidade de leads/contatos no Básico; uso justo e proteções técnicas permanecem. | LEG-13 (IN-024); REV02 | Trial de 20 leads é regime distinto; não criar quota comercial oculta via API/armazenamento. |
| DAT-R04 | Origem e captação | Registrar site, página, formulário, versão, campanha/UTM e canal quando dados disponíveis, com trilha e proteção de origem. | LEG-04; IN-124; IN-125 | Não inventar campanha, consentimento ou fonte; versões e vínculo tenant devem ser persistidos. |
| DAT-R05 | Campos mínimos em formulário Imagine | Nome e ao menos um contato válido — e-mail ou telefone/WhatsApp; outro contato e mensagem opcionais segundo formulário padrão. | LEG-13 (IN-093) | Regra do formulário padrão Sites Imagine; não transformar automaticamente em schema universal de todo lead CRM. |
| DAT-R06 | Campos adicionais e privacidade | Campos configuráveis com tipo/validação, finalidade e aviso LGPD; consentimento somente quando exigido, marketing separado/opcional e não pré-marcado. | LEG-13 (IN-093); IN-125 | Evitar coleta excessiva de dados e dependência indevida de consentimento para mensagem de contato. |
| DAT-R07 | Recepção confiável do lead | Formulário só informa envio recebido com sucesso após persistência durável na API principal ou de contingência; entrega ao CRM exige confirmação independente e confiável do CRM. | IN-124; IN-148; IN-149 | HTTP 2xx/202 intermediário não equivale ao lead criado/associado no CRM. |
| DAT-R08 | Identificador idempotente do envio | Uma submissão lógica mantém ID estável por todas as tentativas/API/failover/reconciliação; envios distintos com conteúdo igual têm IDs distintos. | IN-148; IN-149 | Não mesclar automaticamente pessoas só por submissão igual ou repetir registro em retry. |
| DAT-R09 | Fila temporária distinta de dados CRM | Para payload ainda não entregue: fila 3 dias e falhas adicionais 15 dias; após 18 dias, exclusão de cópias temporárias não entregues é permitida com verificações e auditoria técnica minimizada. | IN-127; IN-147 | Não apagar lead/contato confirmado no CRM nem reutilizar 18 dias como retenção de histórico comercial. |
| DAT-R10 | Duplicados: detecção e aviso | Detectar possíveis coincidências por e-mail/telefone/identificador com evidência; não aplicar mesclagem automática destrutiva em captura, importação ou integração. | LEG-04; LEG-13 (IN-023/028); IN-124 | Não converter idempotência de submissão em deduplicação de pessoa; manter casos genuinamente distintos. |
| DAT-R11 | Mesclagem humana no Básico | Único Administrador da Empresa pode comparar duplicados lado a lado, escolher principal, resolver conflito campo a campo e confirmar; preservar origens, vínculos de agenda/oportunidades, observações e auditoria. | LEG-13 (IN-028) | Forma técnica do secundário, consentimentos divergentes, reversibilidade e retenção precisam validação. |
| DAT-R12 | Exclusão definitiva protegida | Admin Básico pode solicitar exclusão definitiva de lead/contato elegível com confirmação; vínculos, obrigações legais e históricos relevantes impedem exclusão simples em cascata. | LEG-13 (IN-027) | Definir quando anonimizar, desvincular, bloquear, reter, excluir e fazer revisão; arquivar não é excluir. |
| DAT-R13 | Importação Básico CSV/XLSX | Mapeamento de colunas, prévia/validação, aviso de duplicados e confirmação humana, sem sobrescrita/mesclagem automática. | LEG-13 (IN-025); IN-118 | Ignorar/atualizar duplicados são decisões distintas e exigem confirmação/permissões; o genérico dos pagos não revoga o limite do Básico. |
| DAT-R14 | Exportação Básico CSV/XLSX | Todos os leads/contatos autorizados ou registros filtrados, com validação do escopo no servidor, download protegido e trilha de auditoria. | LEG-13 (IN-026); IN-118 | Acesso a um campo não implica direito universal de exportá-lo; evitar injeção de fórmulas em arquivos. |
| DAT-R15 | Export/import geral CRM | Exportar leads, contatos, empresas, oportunidades e atividades autorizadas; importação com prévia, mapeamento e relatório de erros; grandes volumes em processamento controlado. | LEG-04 | Diferenciar recursos por plano/ator; importar não autoriza transformar qualquer lead em outra entidade ou escrever fora da organização. |
| DAT-R16 | Campos personalizados: tipos | Cada empresa pode criar campos em leads/contatos/oportunidades; tipos: texto, número, moeda, data, seleção única/múltipla e verdadeiro/falso. | LEG-04; INT-03 | Validações no backend, formulários, relatórios, filtros e integrações com versão e escopo. |
| DAT-R17 | Campos personalizados: quotas pagas | Limites por módulo/empresa: Essencial 10, Profissional 25, Empresarial 50; downgrade conserva campos acima da quota, restringindo novas criações. | LEG-04 | Não inferir quota/benefício Básico sem autorização de sua matriz própria. |
| DAT-R18 | Campos personalizados: ciclo de vida | Obrigatório/padrão/validações, ordem e opções configuráveis; arquivar campo preserva dados; exclusão com dados/dependências requer proteção; mudança de tipo com dados apenas por conversão compatível prévia/auditada. | LEG-04 | Schema físico/versões, destino de campo arquivado em exportação/restore, relatórios e automações ficam para decisão. |
| DAT-R19 | Lead Scoring | Pontuação 0–100 por regras configuráveis de origem/perfil/engajamento/inatividade, com classificação e uso em rotinas aprovadas. | LEG-04 | Pesos e limites de faixa não aprovados; IA não precisa participar do cálculo inicial. |
| DAT-R20 | Histórico e proveniência | Preservar autor, data, responsável, transferências, origem, observações, relações com oportunidades/agendamentos e registros de operações; resumos IA têm histórico interno versionado com acesso restrito conforme decisão específica. | LEG-04; LEG-13; REV01 | Distinguir trilha de negócio, auditoria comercial, resumos de IA e log técnico; regras de expurgo são próprias. |
| DAT-R21 | Dados estruturados versus anexos | Leads, contatos, históricos, oportunidades e textos não consomem franquia comercial de arquivos; cotas anexos 1/5/20/50 GB, avisos 80/95 e bloqueio apenas de novo anexo a 100%. | LEG-13 (IN-029..032); REV02 | Cota plena não bloqueia captação de leads, exportação permitida ou leitura de arquivo existente. |
| DAT-R22 | Portabilidade self-service | Central IN-118 em todos os planos inclusive Básico: CSV/XLSX, backup completo, restauração compatível, migração entre contas autorizadas e histórico, sem taxa adicional por operação essencial. | IN-118 | Não confundir planilha com backup completo nem criar superusuário/credencial migrável. |
| DAT-R23 | Troca de conta CRM vinculada ao site | Cliente escolhe conta nova/existente na contratação; alteração posterior via chamado, com trabalho técnico gratuito da Imagine; migração histórica entre contas é do cliente por IN-118. | IN-118; REV02 | Não redirecionar leads pendentes ou dados antigos automaticamente; nova entrega segue vínculo autorizado e auditado. |
| DAT-R24 | Fronteiras dos motores de integração | INT-03 valida/mapeia; INT-08 executa movimentação delimitada por lote; IN-118 governa portabilidade; proprietários de lead/contato validam e confirmam persistência e alterações. | INT-03; INT-08; IN-118 | Nenhuma INT/IA recebe autoridade de mesclar, excluir, sobrescrever ou concluir efeito de negócio por transporte. |
| DAT-R25 | Backup/restore e consentimentos | Backup completo deve preservar quando portável dados, relacionamentos, históricos, anexos e configurações; restauração tem prévia, compatibilidade, direitos, confirmação e tratamento explícito de conflitos. | IN-118; LEG-13 | Não copiar tokens, segredos nem dados fiscais de outra conta; preservar provas de consentimento conforme finalidade e lei. |
| DAT-R26 | Segurança dos dados e retenção | Controles multiempresa, exportação restrita, auditoria, LGPD, logs minimizados e regras distintas de retenção para lead CRM, fila de envio, backup/anexo e conta cancelada. | REV01; REV02; LEG-13; IN-147 | Não unificar os prazos 18 dias da fila, 90 dias de auditoria de payload, 120 dias da conta CRM cancelada, ou 30 dias de backup de anexos. |

## 3. Distinção entre operações e suas autoridades

| Operação | Titularidade/autoridade do ato | Procedimento e restrições já existentes | Pendência de contrato |
|---|---|---|---|
| Cadastro/edição de lead/contato | Módulo proprietário de dados comerciais | Por tenant e perfis reais, inclusive ações manuais Básico | Identificadores, autoria, estados, validação de campo e relações |
| Captura por formulário de Site Imagine | Sites/Portal originam; CRM confirma registro; APIs/Filas entregam | ID único por submissão, API durável, CRM confirma em etapa própria | Payload/versionamento, estados de erro, destino e consentimento |
| Campo personalizado | CRM de dados; INT-03 apenas transforma/mapa entre sistemas | Tipos e quotas pagas por módulo, validação no backend, arquivamento/versão | Schema/compatibilidade, direitos por campo e tratamento de dados legados |
| Identificação de possíveis duplicados | Módulo de dados do CRM | Sugerir e comparar sem fusão automática | Evidências/algoritmos/proteção anti falsos positivos |
| Mesclagem definitiva | Admin autorizado/proprietário de cadastro | Escolha de registro principal e conflito campo a campo, confirmação | Reversão, consentimentos divergentes, vínculo secundário e trilha |
| Exclusão definitiva | Proprietário dos dados, privacidade, autenticação | Confirmar elegibilidade; sem cascata destrutiva | Dado pessoal/obrigação de retenção, anonimização, expurgo e reversão |
| Importação/exportação em lote | IN-118 oferece Central; INT-08 opera lote; proprietário grava/atesta | Prévia, validação, autorização por empresa e campo, histórico | Manifesto, formatos técnicos, política de conflitos e download |
| Backup completo/restauração | IN-118 e infraestrutura de backups/Arquivos competentes | Relações/histórico/anexos/configurações portáveis; aprovação antes de gravar | Schema do backup, integridade, criptografia, compatibilidade e exceções |
| Migração entre organizações | Cliente autorizado em origem/destino por Central IN-118 | Autoatendimento; Imagine troca tecnicamente o vínculo de site de graça, **não dados históricos** | Escopo de titularidade, reconciliação de vínculos, estado da conta e logs |
| Auditoria/histórico | Auditoria Geral é fonte oficial; proprietário mantém histórico de negócio | Acionar logs de autor/ação/versão sem abrir dados de outro tenant | Classes de retenção separadas, direito de leitura/exportação e correlação |

Nenhum motor INT pode determinar diretamente fusão, exclusão, compensação, autorização de leitura/exportação ou confirmação oficial de CRM pela simples conclusão de um job n8n.

## 4. Matriz de direitos do Básico versus contrato de portabilidade

| Recurso/ação | Básico — evidência específica | Outros planos / regime geral | Restrição permanente |
|---|---|---|---|
| Lead/Contato manual | Ver, criar/editar, pesquisar, arquivar e anotar IN-023 | Perfis e recursos gerais por plano | Sem acesso a outro tenant |
| Quantidade de leads/contatos | **Sem teto comercial rígido** IN-024 | Regime próprio dos planos pagos; trial **20 leads** | Uso justo, sem limite oculto que faça perder leads recebidos |
| Importação CSV/XLSX | IN-025 e IN-118 | Importação geral conforme plano e IN-118 | Sem merge/overwrite automático |
| Exportação CSV/XLSX | IN-026 e IN-118 | Exportação por recursos permitidos e IN-118 | Direito de exportação não decorre da simples visualização |
| Mesclagem de duplicados | Administrador único, confirmação e conflito campo a campo IN-028 | Perfis legítimos de outros planos | Não usar IA para mesclagem automática |
| Exclusão definitiva | Elegibilidade e confirmação protegidas IN-027 | Políticas de privacidade e dono da entidade | Histórico, relações e obrigações legais preservados |
| Campos personalizados | **A franquia exata do Básico não foi confirmada pela tabela geral dos pagos** | Essencial **10**, Profissional **25**, Empresarial **50** por módulo (leads, contatos, oportunidades) | Não criar nem negar campo Básico por analogia de plano pago |
| Dados estruturados | Fora da franquia de anexos | Fora da franquia nos quatro planos | Cota anexos cheia não impede criar lead/registro |
| Backup completo / restore compatível | IN-118 essencial para Básico | IN-118 essencial em todos os planos | Não copiar tokens/segredos/contratos financeiros entre empresas |
| API pública | Não incluída automaticamente pelo Básico | Trial pública desligada; demais direitos por contrato | Backend nativo autorizado ≠ acesso API pública do cliente |

## 5. Divergências e riscos de reconciliação que não devem ser resolvidos por suposição

| ID | Tema | Diferença real ou risco documental | Direção de conferência | Fontes |
|---|---|---|---|---|
| DAT-D01 | Importação genérica vs Básico | Capítulo geral permite ignorar/atualizar possíveis duplicados mediante permissão; IN-025 do Básico exige apenas alerta/prévia, sem presumir ação automática padrão. | Aplicar a regra mais específica por plano e contexto; definir ações humanas permitidas no geral e no Básico, jamais overwrite silencioso. | LEG-04; LEG-13 |
| DAT-D02 | Deduplicação por pessoa vs idempotência por envio | Submissão lógica reentregue deve ser idempotente, mas pessoas com mesmo telefone podem representar casos legítimos diferentes. | Separar submission_id estável de identificação de cadastro e processo manual de mesclagem. | IN-149; LEG-13 |
| DAT-D03 | Campos do formulário vs do registro CRM | IN-093 exige nome + contato no formulário público padrão; outros cadastros CRM e importações podem ter regras próprias. | Não impor obrigatoriedade como coluna universal sem contrato da entidade. | LEG-13; LEG-04 |
| DAT-D04 | Formulário Imagine gerenciado vs criador genérico no CRM | LEG-04 descreve formulários públicos criados pela empresa; IN-125 governa formulários de Sites Imagine pela Imagine/Portal com autonomia do cliente delimitada. | Separar produto/competência; não permitir cliente editar código publicado do site por possuir CRM. | LEG-04; IN-125 |
| DAT-D05 | Backup completo vs exportação simples | IN-118 define backup estruturado com relações/anexos/configurações quando portáveis; CSV/XLSX por filtro não basta. | Definir formato, manifesto, esquemas/versionamento e restauração antes de desenvolvimento. | IN-118 |
| DAT-D06 | Cotas arquivos vs dados estruturados | Franquiagem dos anexos e limite físico do fluxo de import/export não são quota de leads. | Planejar limites técnicos de lote/arquivo sem criar teto comercial oculto. | LEG-13; REV02 |
| DAT-D07 | Exclusão de fila vs exclusão CRM | IN-147 permite descartar payload temporário não entregue após 18 dias; IN-027 protege exclusão de lead existente no CRM. | Distinguir storage temporário, evidências minimizadas e dados oficiais. | IN-147; LEG-13 |
| DAT-D08 | Mesclagem/consentimentos divergentes | IN-028 exige revisão dos valores campo a campo e preservar origens/histórico, mas não define conflitos de consentimento. | Elaborar regra LGPD por finalidade, consentimento e trilha, sem fundir automaticamente autorizações. | LEG-13; IN-118 |
| DAT-D09 | Portabilidade entre contas vs alteração do vínculo site | Migração histórica é do cliente, não da troca gratuita de vínculo pela Imagine. | Contrato por organização origem/destino e direitos separados; não mover credenciais/assinaturas nem redirecionar fila antiga. | IN-118; REV02 |
| DAT-D10 | Campos personalizados Básico | O catálogo geral define tipos e quotas dos três planos pagos, sem quota de campos do Básico nessa cláusula. | Não conceder/restringir campos Básico por extrapolação; cotejar direitos da matriz Básico e revisar ao final. | LEG-04; LEG-13 |
| DAT-D11 | Histórico de resumos IA e auditoria | Resumo de IA no atendimento, histórico comercial, alterações do registro e log técnico são objetos distintos. | Versionar e limitar acesso por finalidade; fontes oficiais, retentões e direitos separados. | REV01; LEG-04 |
| DAT-D12 | Sucesso da API vs do CRM | Recebimento durável na API não é persistência do registro no CRM, e 2xx/202 não equivale a conclusão. | Usar confirmação oficial com ID do registro correto, consulta/reconciliação e resultado inconclusivo. | IN-149; IN-148 |

## 6. Pendências concretas para especificação final

| ID | Assunto | Evidência/decisão necessária | Competência responsável | Prioridade proposta |
|---|---|---|---|---|
| DAT-P01 | Dicionário de entidades e chaves | Definir Lead, Contato, Empresa, Oportunidade, origem, relacionamento, fonte e lifecycle, por tenant e versão. | CRM dados/VEN/ATD + Arquitetura | P0 |
| DAT-P02 | Direitos por entidade, campo e ação | Separar visualizar, criar, alterar, mesclar, arquivar, excluir, exportar, importar, restaurar e administrar; perfis reais. | Identidade/Segurança + módulos proprietários | P0 |
| DAT-P03 | Esquema e validação de campos personalizados | Tipos, versões, migrations, schema por plano, classes sensíveis, regra de compatibilidade e dados legados. | CRM dados + INT-03 + Segurança | P0 |
| DAT-P04 | Mesclagem e tratamento de duplicados | Definir chave de candidatos, conflitos por campo/consentimento, registro secundário, reversibilidade, vínculos e auditabilidade. | CRM dados + ATD/VEN/AGD + privacidade | P0 |
| DAT-P05 | Exclusão, anonimização e retenção | Critérios LGPD, vínculos impeditivos, exclusão/anonimização legal, impacto em backups, auditoria e solicitações de titulares. | Segurança/LGPD + proprietários | P0 |
| DAT-P06 | Histórico, mudanças e proveniência | Modelo de autoria, transferências, origens/campanhas, versão de resumo IA e classes de evidência. | CRM dados + ATD/VEN + Auditoria Geral | P0 |
| DAT-P07 | Ingestão e confirmação de leads | Contrato API principal/contingência, submission_id, vínculo de destino, esquema de payload, transições, erros e provas oficiais. | IN-124/148/149 + CRM dados/INT | P0 |
| DAT-P08 | Portabilidade e backup completo | Schema de pacote, manifesto, formatos, anexos, relacionamentos, versionamento, permissões origem/destino e restauração segura. | IN-118 + INT-08 + Arquivos + CRM dados | P0 |
| DAT-P09 | Processamento de arquivos temporários | Tamanho/lotes, antimalware, criptografia, expiração, quotas técnicas e confirmação de exclusão em integração. | INT-08 + Arquivos + Segurança | P0 |
| DAT-P10 | Consentimentos e finalidades por origem | Versionar aviso/formulário/base legal, distinguir consentimento marketing, preservar histórico e LGPD. | Privacidade/LGPD + Sites/Portal/CRM dados | P0 |
| DAT-P11 | Histórico e acesso em estados contratuais | Portabilidade/consulta após suspensão, trial expirado, cancelamento CRM pago e benefício, sem liberar funções indevidas. | FIN/CON + IN-118 + Segurança | P0 |
| DAT-P12 | Cotejo da fonte original e critérios de aceite | Recuperar texto integral de aprovações, confrontar as diferenças e definir cenários negativos; sem executar testes. | Arquitetura documental + proprietários | P0 |

As prioridades são de análise, **não** uma aprovação de novos parâmetros de operação. O fechamento depende de contrato por entidade, revisão de fonte primária quando disponível e competência de cada módulo.

## 7. Critérios de aceitação futuros (somente documentação; não executar testes)

| Caso de revisão | Cenário negativo ou fronteira | Resultado esperado |
|---|---|---|
| DAT-T01 | Empresa A tenta ler/exportar lead da B por ID conhecido | Acesso negado no backend e RLS, com trilha segura |
| DAT-T02 | Retry de envio API com mesmo submission_id; conteúdo similar enviado com novo ID | Somente um registro por submissão lógica; novo envio distinto preservado conforme política |
| DAT-T03 | API recebe HTTP 202 sem prova de persistência em CRM | Não marcar entregue no CRM; manter pendente/reconciliar |
| DAT-T04 | Fila de envio expira em 18 dias com confirmação contraditória | Conferência final, não apagar registro confirmado, política de descarte somente do payload elegível |
| DAT-T05 | Importação Básico com duplicados e conflitos | Exibir prévia, não atualizar/mesclar automaticamente |
| DAT-T06 | Administrador Básico solicita mesclar registros com agendamentos | Exigir escolha do principal, revisão campo a campo e preservar vínculos/consentimentos |
| DAT-T07 | Exclusão definitiva de contato com oportunidade/obrigação legal | Bloquear exclusão simples e indicar fluxo protegido |
| DAT-T08 | Downgrade com campos personalizados acima da quota paga | Dados/funcionalidades existentes preservados, novas criações excedentes negadas |
| DAT-T09 | Exportação solicitada por pessoa com direito apenas de leitura | Negar extração de campos não autorizados; download seguro independente |
| DAT-T10 | Cota anexos esgotada e lead novo chega por formulário | Preservar entrada legítima de dado estruturado e exportação autorizada |
| DAT-T11 | Restaurar backup com tokens de conta antiga em outra empresa | Não migrar segredos, fatura/contrato alheio, exigir autorização em origem e destino |
| DAT-T12 | Troca gratuita do CRM de destino de um site com leads em fila | Não redirecionar mensagens antigas silenciosamente nem migrar históricos |
| DAT-T13 | Formulário exige consentimento de marketing para simples contato | Respeitar finalidade e base legal; consentimento de marketing separado e opcional |
| DAT-T14 | Alteração de tipo de campo personalizado com registros incompatíveis | Negar conversão destrutiva e preservar dados originais, auditoria e dependências |

Esses DAT-T01..14 são **casos propostos de revisão**, não uma nova família executada de testes HT nem software criado.

## 8. Complemento posterior: capacidade de armazenamento adicional concedida gratuitamente (03/11/2026)

A [CRM-DEC — Gratuidade de adicionais específicos](CRM-DEC-gratuidade-administrativa-adicionais-especificos.md) registra aprovação funcional para o Administrador Geral Imagine **conceder uma capacidade/pacote de armazenamento adicional gratuito a uma organização específica, como ação separada da gratuidade de plano**. Não altera quotas incluídas nem regras de anexos (avisos 80%/95%, bloqueio de novos uploads a 100%); quantidades, duração, unidades, transição e custos permanecem pendentes em GRAT-EX-P01..08. A concessão não deve remover arquivos existentes ao expirar nem gerar cobrança automática sem contratação expressa.

## 9. Status da CRM-REV-03 e recomendação de próxima aprovação

- **26 regras funcionais cotejadas**, com fontes históricas/currentes indicadas, sem declarar que cada detalhe de implementação esteja integralmente aprovado.
- **12 divergências/riscos**, **12 pendências técnicas/funcionais** e **14 casos negativos para aceite futuro** organizados.
- A [CRM-REV-03 foi aceita como base documental](CRM-DEC-aprovacao-REV-03-base.md), mas **não** foram aprovados schemas, parâmetros ou contratos executáveis, nem encerradas as pendências.
- A CRM-REV-03 **já recebeu aceite como base documental**. A [CRM-REV-04 — Atendimento, WhatsApp e gestão de conversas](CRM-REV-04-atendimento-whatsapp-conversas.md) foi **preparada como minuta para revisão**, mantendo interfaces com VEN e os motores já existentes.
- CORE-P0, IN-168, INT P0, G01..G06 e os P0 comerciais continuam em aberto. Nenhuma implementação ou execução de teste foi iniciada.
