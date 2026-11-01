# 13 — CRM Básico Cortesia: matriz de permissões por módulo

> **Planejamento, sem implementação.** Diferenciar rigorosamente decisões **APROVADAS** de funcionalidades **PROPOSTAS** ou **PENDENTES**. Documento transversal ao CRM Básico Cortesia vinculado a sites Imagine, não uma concessão geral aos planos pagos.

## Fundamentos aprovados

- **IN-003:** o benefício Básico é enxuto, exclusivo de empresa vinculada a contrato elegível de site Onepage/Landing Page; não é Essencial gratuito.
- **IN-020:** no máximo **1 usuário autenticado por organização**, quota distinta de **1 profissional agendável** (IN-008).
- **IN-021:** o único usuário tem perfil **Administrador da Empresa**, **MFA obrigatório**, sem perfil alternativo operacional no Básico e sem poderes de superadministrador Imagine.
- **IN-010:** recurso ausente ou quota excedida exige explicação de plano e opção de upgrade/adicional **somente quando resolver**, com compra voluntária autorizada; falha de MFA, RBAC ou infraestrutura não deve ser tratada como upsell.
- Recursos aprovados do **CRM geral** não se tornam automaticamente incluídos no **Básico**; direitos devem ser verificados por organização, módulo e ação no backend.

## Módulo 1 — Administração da Empresa e Segurança — IN-022 APROVADA em 2026-11-01

| Ação ou capacidade | Decisão para o Administrador único do Básico | Limitações e cuidados |
| --- | --- | --- |
| Consultar dados cadastrais comuns da própria empresa | **PERMITIDO** | Somente organização autorizada, respeitando dados pessoais |
| Atualizar dados cadastrais comuns da própria empresa | **PERMITIDO** | Rastrear alterações relevantes e validar campos |
| Alterar **CNPJ ou identificador fiscal** | **PERMITIDO APENAS COM VALIDAÇÃO ESPECÍFICA** | Não permitir simples alteração irrestrita; processo exato de verificação, evidências e eventual análise manual ficam pendentes |
| Alterar senha de acesso | **PERMITIDO mediante verificação de identidade** | Reautenticação/verificação conforme risco, revogação de sessão quando cabível; nunca expor senhas |
| Alterar e-mail de acesso | **PERMITIDO mediante verificação de identidade** | Validar o novo e-mail, proteger contra tomada de conta e registrar alteração; detalhes específicos pendentes |
| Gerir configuração de MFA | **PERMITIDO mediante verificação de identidade** | **MFA permanece obrigatório**; não autoriza desligar MFA, enfraquecer autenticação ou contornar recuperação segura |
| Consultar sessões e encerrar sessões em outros dispositivos | **PERMITIDO** | Somente sessões da própria identidade e organização dentro das regras do CRM; não autoriza acesso a sessões alheias |
| Consultar plano Básico, recursos incluídos e limites realmente utilizados | **PERMITIDO** | Informação exata dos entitlements da empresa, sem inventar quotas ainda não aprovadas |
| Consultar opções de upgrade e contratar plano pago no CRM | **PERMITIDO com aceite expresso** | Mostrar valores/termos reais, confirmar transação pelo fluxo financeiro do CRM, sem cobrar automaticamente; assinatura do site no Portal Imagine é independente |
| Habilitar **2º usuário** no Básico | **NÃO INCLUÍDO** | Quota **1/1**; explicar limite e upgrade/adicional elegível se existir (IN-010). Não compartilhar credenciais |
| Acessar outras organizações sem associação válida | **PROIBIDO** | Isolamento multiempresa e autorização server-side |
| Administrar o SaaS, empresas alheias ou ferramentas de superadministrador Imagine | **PROIBIDO** | Perfil da empresa não equivale à administração geral da plataforma |

### Regras de autorização e proteção aprovadas

1. Validação de direitos **do plano e do papel** em toda operação administrativa no backend; não confiar somente no frontend.
2. **MFA obrigatório** para o usuário Administrador da Empresa; recuperação de credenciais e medidas de proteção da conta seguem as normas gerais aprovadas do CRM.
3. Alterações cadastrais relevantes, mudanças de identidade/segurança, sessões encerradas, alterações de vínculo fiscal e eventos financeiros devem seguir a auditoria e segurança correspondentes, preservando trilhas sem exposição de segredos.
4. Contratação de upgrade **dentro do CRM** apenas após informação clara e consentimento financeiro explícito; não modificar automaticamente a assinatura do site no Portal.
5. **Não interpretar IN-022 como liberação automática** de funcionalidades de configuração avançada, auditoria global, usuários extras, edição de permissões de terceiros, outras empresas, integrações premium ou acesso financeiro de outros tenants.

### Pendências específicas do módulo 1

- Definir o fluxo e a autoridade para validar solicitação de alteração de CNPJ/identificador fiscal, evitando sequestro de titularidade e impacto indevido em contratos/faturamento.
- Desenhar verificação de e-mail e identidade nas mudanças sensíveis, confirmação, recuperação de MFA, políticas de sessão, histórico de dispositivos e avisos de segurança.
- Detalhar substituição/transferência do administrador único, convites pendentes e recuperação quando a única conta ficar inacessível.
- Aprovar UX específica de contratação e das telas de plano/limites, seguindo regras financeiras já aprovadas.

## Módulo 2 — Leads e Contatos — IN-023 APROVADA em 2026-11-01

**Decisão aprovada:** o **Administrador da Empresa**, único usuário autenticado do CRM Básico Cortesia (IN-020/IN-021), pode **visualizar, cadastrar, editar, pesquisar, registrar observações de atendimento, identificar possíveis duplicidades sem mesclagem automática e arquivar leads/contatos com preservação do histórico**. O uso é restrito à empresa autenticada e aos recursos efetivamente incluídos no benefício, sem herdar a matriz integral dos planos pagos.

A recepção de **leads pelos formulários do Onepage/Landing Page Imagine já havia sido aprovada em IN-001**, pela integração segura servidor-servidor. Um formulário gera um lead, **não uma reserva automática** (IN-007); não registrar sucesso de recebimento se o envio não foi persistido em fila/serviço durável, nem descartar silenciosamente leads já aceitos.

| Ação do Administrador no Básico | IN-023 — estado | Regras e limitações |
| --- | --- | --- |
| Visualizar lista de leads e contatos | **APROVADO** | Apenas dados da própria organização, conforme permissões e proteção de dados |
| Consultar detalhes de lead ou contato | **APROVADO** | Exibir dados cadastrais e origem/site/formulário quando disponíveis, sem expor outros tenants |
| Cadastrar manualmente lead ou contato | **APROVADO** | Um registro por operação humana autorizada; **não concede** importação em massa, API pública ou robôs |
| Editar cadastro, informações de contato e situação básica do lead | **APROVADO** | Preservar histórico/autor/data, validação de campos e origem; movimentação em funil dependerá do módulo Oportunidades/Funis |
| Pesquisar e filtrar por nome, telefone, e-mail, origem e situação | **APROVADO: busca básica** | Não concede analytics avançado, motor de pesquisa global ou acesso irrestrito a outros módulos |
| Registrar observações simples e histórico manual de atendimento | **APROVADO** | Autor/data/hora, preservação de histórico e isolamento de empresa; anexos, atividades avançadas, automações e envios externos dependem de suas próprias permissões |
| Detectar e sinalizar possíveis cadastros duplicados | **APROVADO: alerta** | Utilizar dados disponíveis e critérios a detalhar; **não mesclar, descartar, substituir ou apagar automaticamente** os cadastros, nem perder origens |
| Arquivar/inativar leads e contatos | **APROVADO** | Retirar da visualização ativa sem excluir o registro, seus vínculos e histórico; restauração, consentimentos, prazos e UX requerem detalhamento |
| Excluir definitivamente lead ou contato | **IN-027 APROVADA: exclusão controlada** | Administrador confirma expressamente para registro elegível; **vínculos, obrigações legais ou histórico relevante exigem fluxo protegido**, sem apagar indevidamente informações e auditoria |
| Mesclar manualmente leads e contatos duplicados | **IN-028 APROVADA: mesclagem manual completa e controlada** | Escolher registro principal, revisar conflitos campo a campo, confirmar expressamente, preservar vínculos, origens, históricos e auditoria; **nunca mesclar automaticamente** |
| Importar leads e contatos via CSV e XLSX | **IN-025 APROVADA: importação básica por arquivo** | Administrador autenticado; mapeamento de colunas, prévia, validação e alertas de duplicidade, **sem mesclagem automática**; limites técnicos por arquivo pendentes |
| Criar leads/contatos por API pública, bots ou integrações externas | **NÃO concedido por IN-025** | Importação autorizada somente pelo fluxo de arquivo e permissões; APIs externas exigem autorização distinta |
| Exportar leads e contatos em **CSV ou XLSX** | **IN-026 APROVADA** | Exportar **todos os registros autorizados da organização ou somente os filtrados**, com controle de acesso, proteção do arquivo, auditoria e limites técnicos proporcionais; não concede exportação de relatórios avançados |
| Quantidade de leads/contatos | **IN-024 APROVADA: sem teto comercial rígido** | Vale para leads captados dos sites e leads/contatos cadastrados manualmente, sujeitos a uso justo e controles técnicos contra abuso; sem quota numérica oculta |
| Armazenamento dos dados estruturados (leads, contatos e históricos) | **IN-029 APROVADA: não consomem franquia comercial fixa de arquivos** | Independente da quantidade legítima de registros sem teto rígido (IN-024); manter infraestrutura, uso justo, segurança, capacidade operacional e LGPD |
| Inclusão e tipos de anexos de leads/contatos no Básico | **IN-030 APROVADA: mesmos tipos permitidos nos planos pagos** | Imagens, PDFs e documentos comuns constantes da mesma lista de extensões/MIME permitidas a todos os planos; segurança, autorização, vínculo e histórico. Não criar limitação por tipo só para Básico |
| Franquias de armazenamento de **arquivos/anexos** (por empresa) | **IN-031 APROVADA** | **Básico 1 GB; Essencial 5 GB; Profissional 20 GB; Empresarial 50 GB**. Apenas arquivos/anexos efetivamente armazenados entram na franquia; **dados estruturados não contam em nenhum dos quatro planos** |
| Utilização, alertas e limite de anexos | **IN-032 APROVADA** | Mostrar utilizado e disponível; **avisos internos em 80% e 95%**; a **100% bloquear novos uploads de anexos**, mantendo arquivos existentes e operações de dados estruturados |
| Limite global por arquivo e validação de upload | **Regra geral preexistente: até 5 MB/arquivo** | Vale para todos os planos, conforme diretriz geral já aprovada, até revisão expressa; igualdade de formatos não cria exceção de tamanho |
| Armazenamento temporário de importações/exportações | **PENDENTE** | Políticas próprias de tamanho, processamento, expiração, descarte e proteção de uploads/downloads |
| Retenção e recuperação de leads/contatos e histórico | **PENDENTE** | Definir ciclo de vida e direitos legais sem confundir com a retenção de 90 dias de avisos internos da agenda |

### Critérios de aceite para o escopo aprovado

1. Administrador autenticado e com MFA pode realizar **consultas, cadastros e atualizações manuais**, dentro de sua organização, com verificações de permissão no backend.
2. Todo lead recebido do formulário aprovado é vinculado ao tenant e à origem correta, tratado em integração durável, com prevenção de perda silenciosa de registros confirmados.
3. **Suspeita de duplicidade não executa mesclagem sozinha.** IN-028 aprovou **mesclagem manual controlada, com escolha de registro principal, revisão de conflitos campo a campo e confirmação expressa**, preservando vínculos e auditoria. IN-027 autoriza, separadamente, exclusão definitiva controlada de registros elegíveis; **mesclar não equivale a excluir**.
4. **Arquivar não equivale a excluir:** preservar identificação, origem, histórico de atendimento e vínculos com agenda/oportunidades, respeitando a política de retenção e direitos de privacidade que ainda serão detalhados.
5. Observações manuais ficam atribuídas ao autor e protegidas por isolamento multiempresa. O direito não amplia automaticamente armazenamento de anexos, automações comerciais nem canais externos.
6. Mudanças de status operacionais não liberam **funis configuráveis, oportunidades ou ações avançadas** sem decisões do módulo próprio.

### Quantidade sem teto comercial rígido — IN-024 APROVADA em 2026-11-01

**Aprovação expressa:** o CRM Básico Cortesia **não terá limite comercial numérico rígido para quantidade de leads ou contatos**, sejam recebidos pelos **formulários públicos de captura dos sites Imagine** (IN-001), sejam **cadastrados manualmente pelo Administrador da Empresa** (IN-023). A regra não faz distinção entre leads e contatos para imposição de quota comercial por número de registros, mês ou estoque; o histórico legítimo da empresa e os vínculos operacionais devem ser preservados.

**Proteções e proibições:**
1. **Uso justo e controles técnicos contra abuso** são permitidos e necessários (por exemplo, proteção antispam, limites técnicos proporcionais de requisições, validação de origem e tamanho do payload, integridade do tenant, idempotência, antifraude e monitoração), com parâmetros técnicos a especificar. **Não apresentar controle técnico como cota comercial oculta** nem sugerir upgrade quando o bloqueio decorre de segurança ou infraestrutura (IN-010).
2. **Não recusar nem descartar silenciosamente leads já confirmados como recebidos.** O ponto de confirmação exige persistência durável, fila/outbox ou compromisso equivalente de processamento. Em indisponibilidade transitória, manter pendência/reprocessamento com alertas e idempotência. Para envios não aceitos por motivo legítimo (spam, risco, formato), informar resultado real, sem falsa confirmação.
3. **Nenhuma quota numérica mensal ou total oculta** de leads e contatos no Básico; não reutilizar o limite do trial nem quotas de planos pagos. Número de registros por si só não enseja cobrança ou convite de upgrade.
4. **IN-024, isoladamente, não autorizou importação em massa**. A **IN-025 aprovou posteriormente importação básica por arquivos CSV/XLSX**; API pública de cadastro, bots, rotinas automáticas de bulk, armazenamento irrestrito e outros recursos premium continuam sem autorização específica.
5. **IN-029 separou dados estruturados da franquia de arquivos no Básico; IN-031 estendeu a regra aos quatro planos**, aprovando as capacidades de arquivos/anexos. Inclusão de anexos aprovada por IN-030; **temporários, retenção, backup e descarte continuam PENDENTES**, com respeito à LGPD e sem quota comercial oculta de registros.
6. O cadastro manual legítimo e os formulários homologados devem continuar funcionando conforme permissões e uso justo; empresas não devem ter que contratar plano pago simplesmente por terem mais contatos.

**Situação:** IN-024 (quantidade sem teto), IN-025 (importação básica CSV/XLSX) e IN-026 (exportação básica CSV/XLSX, completa ou filtrada, com segurança e auditoria) **APROVADAS**. Parâmetros técnicos de importação/exportação, armazenamento e retenção permanecem **PENDENTES**. Exclusão/mesclagem serão debatidas separadamente.

### Importação básica de Leads e Contatos — IN-025 APROVADA em 2026-11-01

**Decisão aprovada:** o **CRM Básico Cortesia inclui importação básica de leads e contatos por arquivos CSV e XLSX**, iniciada pelo **Administrador da Empresa** (único usuário autorizado, IN-020/IN-021) dentro da própria organização. A operação terá **mapeamento das colunas do arquivo para os campos compatíveis do CRM**, **prévia dos registros e dos resultados previstos**, **validação de dados** e **alertas de possíveis duplicidades**, **sem executar mesclagem automática de cadastros**.

**Requisitos funcionais diretamente derivados da aprovação:**
1. **CSV e XLSX** são formatos aceitos; o arquivo deve ser validado com segurança e associado à organização autenticada, sem importar conteúdo em outra empresa. O fluxo deve distinguir **lead** de **contato**, conforme os tipos e campos efetivamente incluídos no Básico.
2. **Mapeamento de colunas:** permitir associar cabeçalhos aos campos disponíveis, identificar campos ausentes/incompatíveis e mostrar ao usuário o resultado esperado; não interpretar mapeamento como autorização para criar campos personalizados ou funcionalidades premium.
3. **Prévia antes de efetivar:** exibir registros interpretados, erros/inconsistências relevantes e **avisos de possíveis duplicidades** antes de uma confirmação de execução pelo administrador autorizado.
4. **Validação:** aplicar regras de tipos, campos obrigatórios, integridade, proteção contra conteúdo malicioso, origem e isolamento multiempresa. Registros efetivados devem preservar autoria/origem de importação e trilha de auditoria pertinente; não confirmar inclusão de registros que falharam.
5. **Duplicidades:** o sistema **somente sinaliza possíveis coincidências**. Não mesclar registros automaticamente, não apagar históricos, não substituir informações existentes silenciosamente e não presumir comportamento padrão de ignorar/atualizar: esse fluxo ficará para decisão específica de tratamento.
6. **IN-024 permanece integralmente válida:** a quantidade total/mensal de leads e contatos do Básico continua **sem teto comercial rígido**, sujeita a uso justo e proteção antiabuso. **Limites técnicos proporcionais por arquivo/lote**, tamanho, formato, processamento, tempo e frequência ainda precisam ser especificados; jamais transformar esses parâmetros em quota comercial oculta de quantidade total.
7. **Não concedidos por IN-025:** API pública de criação, importação programática via bots/conectores externos, mesclagem automática, alteração em massa sem controle, ferramentas de data enrichment/scoring, construtor avançado de campos, exportação comercial, exclusão definitiva ou retenção ilimitada de arquivos.
8. **Não perder dados aceitos:** falhas parciais, linhas inválidas e processamento interrompido exigem resultado fiel e possibilidade de recuperação/reprocessamento seguro a detalhar; idempotência/evitar duplicações por repetição de upload é requisito técnico a especificar.

**PENDENTES, sem aprovação presumida:** limite máximo de tamanho e número de linhas por arquivo; formatos/encoding CSV, folhas aceitas em XLSX, campos obrigatórios e especiais; regras de linhas inválidas e tratamento de duplicidades (p.ex. ignorar, atualizar com consentimento ou revisão manual); processamento síncrono/assíncrono, histórico e retenção do arquivo temporário, relatórios finais detalhados, contagem e UX da importação. Essas decisões não revogam a importação mais avançada já aprovada para o **CRM geral**, mas não a concedem integralmente ao Básico.

**IN-026 aprovou posteriormente a exportação CSV/XLSX completa ou filtrada, sujeita a autorização, proteção de dados e auditoria. Próximo assunto: exclusão definitiva e mesclagem dos registros, diferenciando esses atos de arquivamento, correção e direitos legais dos titulares.**

### Exportação básica de Leads e Contatos — IN-026 APROVADA em 2026-11-01

**Decisão expressamente aprovada:** o CRM Básico Cortesia permitirá ao **Administrador da Empresa autenticado** exportar **leads e contatos nos formatos CSV e XLSX**, escolhendo entre **todos os registros aos quais tem acesso na organização** e **somente os registros que atendam aos filtros selecionados**. A exportação deve respeitar autorização e isolamento multiempresa, proteção dos dados pessoais, **trilha de auditoria** e **controles técnicos proporcionais de geração/download**.

**Regras funcionais e de segurança:**
1. **Escopo:** incluir a opção "todos os registros autorizados" e a opção "registros filtrados" (com critérios aplicados e validados no servidor). "Todos" nunca inclui registros de outras organizações, dados fora da autorização ou campos sensíveis inacessíveis ao usuário. Leads e contatos exportados não autorizam extrair dados de módulos pagos ou relatórios avançados.
2. **Formatos:** CSV e XLSX disponíveis no Básico para essa operação; gerar arquivos a partir do escopo efetivamente autorizado, com representação correta dos valores, codificação apropriada e proteção contra injeção de fórmulas em CSV/XLSX.
3. **Proteção:** downloads de dados pessoais acessíveis apenas ao destinatário autenticado e autorizado; não publicar arquivos em URLs permanentes e abertas; revalidar autorização no momento da geração e da retirada, inclusive quando o processamento for assíncrono.
4. **Auditoria:** registrar, conforme política de auditoria do CRM, usuário/organização, data/hora, escopo e filtros solicitados, execução/resultado e referência ao arquivo sem registrar dados pessoais desnecessários no log. Não alterar a base original durante uma exportação.
5. **Capacidade técnica:** controles proporcionais de tamanho, paginação, geração assíncrona, fila, prevenção de abuso e expiração de downloads serão definidos na especificação técnica. **Não criar teto comercial de leads/contatos disfarçado em IN-024**, nem exigir upgrade para portabilidade legal de dados.
6. **Segregação:** exportação de dados do CRM Básico é ação do Administrador da Empresa dentro do CRM, **não uma permissão para o Portal Imagine copiar ou baixar indiscriminadamente os dados pessoais de clientes finais**.
7. **Diferenciação:** exportação de leads e contatos em CSV/XLSX **não concede automaticamente** relatórios gerenciais avançados, PDF, personalização de modelos de relatório, exportação de outros módulos, API pública nem exportações automatizadas agendadas.

**Ainda PENDENTE:** campos específicos autorizados e tratamento de informações sensíveis, política de filtros aplicados e dados arquivados/inativos, link seguro de download e sua expiração, tamanho do lote por arquivo, jobs/filas e limitação de requisições, armazenamento/retencão de arquivos temporários, UX de seleção de escopo e canal de entrega. Regras de acesso/portabilidade e solicitações de titulares conforme LGPD não dependem de upgrade indevido.

**IN-027 aprovou posteriormente a exclusão definitiva controlada de leads/contatos elegíveis, com confirmação expressa e fluxo protegido em registros vinculados.** A mesclagem ainda não foi aprovada para o Básico.

### Exclusão definitiva controlada de Leads e Contatos — IN-027 APROVADA em 2026-11-01

**Decisão expressamente aprovada:** o **Administrador da Empresa** no CRM Básico Cortesia poderá solicitar a **exclusão definitiva de leads ou contatos elegíveis**, mediante **confirmação explícita e inequívoca** da ação. Quando um cadastro possuir **vínculos com agendamentos, oportunidades, registros comerciais, histórico relevante ou obrigações legais de preservação**, a exclusão não pode ocorrer como um apagamento simples: o sistema exigirá **fluxo protegido** e respeito à **LGPD** e às regras de **auditoria**. O arquivamento/inativação aprovado na IN-023 continua disponível como alternativa, sem se confundir com eliminação definitiva.

**Diretrizes funcionais aprovadas:**
1. Somente **usuário autenticado, autorizado e vinculado à organização** poderá iniciar exclusão. Revalidar permissão e escopo do registro no servidor; nunca permitir remoção de outro tenant por troca de identificador.
2. Antes de executar, identificar **elegibilidade**, dependências e motivos que exigem preservação. Cadastros **sem vínculos ou impedimentos** poderão seguir fluxo de exclusão após **confirmação expressa**; mostrar claramente a consequência da operação.
3. Para cadastros **com dependências, histórico relevante, retenção legal/contratual ou direito de terceiros afetado**, **não excluir em cascata nem destruir registros dependentes silenciosamente**. Encaminhar para fluxo protegido com revisão de vínculos, restrições e alternativas conformes; a forma exata (bloqueio, anonimização, desvinculação controlada, análise administrativa) **será definida posteriormente**, não presumida nesta aprovação.
4. **Preservar a integridade de agendamentos, oportunidades, registros comerciais e trilhas de auditoria** conforme suas políticas próprias. Não adulterar autoria, datas ou evidências; reter apenas dados necessários e autorizados quando obrigação legal exigir, sem criar obrigação de conservar dados pessoais indefinidamente.
5. A autorização para exclusão **não concede exclusão em massa**, limpeza de outras empresas, eliminação da auditoria nem apagamento automático como reação a alerta de duplicidade ou importação CSV/XLSX.
6. Tratar solicitações de titulares de dados segundo LGPD e outras obrigações aplicáveis, com verificação da legitimidade e do enquadramento da solicitação. Cumprimento de direitos legais **não depende de upgrade comercial**; não prometer apagamento imediato de dados que precisem legalmente ser mantidos.
7. Registrar **quem solicitou/autorizou, quando, identificação operacional e resultado** da operação em trilha de auditoria protegida, com **minimização de dados sensíveis** e sem expor o conteúdo pessoal apagado em logs desnecessários.
8. A exclusão deve ser explicitamente **diferente do arquivamento e da mesclagem**. A **IN-028 aprovou posteriormente mesclagem manual completa e controlada**; a mesclagem automática permanece vedada.

**Detalhes técnicos/operacionais ainda PENDENTES:** definição completa do que torna registro elegível; exigência de reverificação para operação crítica; critérios de bloqueio/anonimização/desvinculação; histórico de referências após exclusão; transação, concorrência e consistência; eventual período de reversão/restauração (não presumido); retenção de logs, backups, arquivos temporários e política LGPD; confirmação/avisos de UX; políticas de exclusão de contatos ligados a negócios ou atividades e eventual workflow de aprovação. **IN-027 aprova a permissão e suas salvaguardas, não um algoritmo de eliminação irrestrita.**

**IN-028 aprovou posteriormente** a mesclagem manual de cadastros duplicados com escolha do registro principal e resolução de conflitos campo a campo, mediante confirmação explícita e preservação de dados, vínculos, origens, históricos e auditoria.

### Mesclagem manual completa de Leads e Contatos duplicados — IN-028 APROVADA em 2026-11-01

**Regra aprovada:** o único **Administrador da Empresa** no CRM Básico Cortesia pode **mesclar manualmente leads e contatos duplicados**. Antes de efetivar, deverá **escolher qual cadastro será o registro principal**, comparar os dados lado a lado e **resolver conflitos campo a campo**, selecionando os valores que prevalecerão. A operação requer **confirmação explícita e informada** e deve **preservar os vínculos com agendamentos e oportunidades, origens dos leads, observações, históricos e trilha de auditoria**, sem perda silenciosa de informação. **Mesclagem automática não é permitida**, inclusive por importação (IN-025), formulário ou detecção de duplicidade (IN-023).

**Critérios funcionais e proteção de integridade:**
1. **Iniciativa humana:** o CRM pode sugerir duplicidades, mas apenas o administrador autenticado e autorizado poderá iniciar uma mesclagem; nenhuma rotina, importação, automação comercial ou formulário público poderá efetivá-la automaticamente.
2. **Escolha do cadastro principal:** identificar claramente o registro a permanecer como referência ativa, o(s) registro(s) de origem a consolidar, seus identificadores e as consequências; não usar heurísticas automáticas para efetivar a escolha.
3. **Prévia comparativa e conflitos:** apresentar dados de cada cadastro e origem de valores, exigir **revisão das divergências campo a campo** e **seleção explícita** de qual valor prevalecerá quando incompatíveis; dados complementares compatíveis e observações/histórico não devem ser perdidos.
4. **Confirmação antes da execução:** exibir resumo do que será consolidado (cadastro principal, campos, vínculos, conflitos resolvidos) e solicitar confirmação inequívoca. Não executar mesclagem por clique acidental na lista ou por abertura da prévia.
5. **Preservação e rastreabilidade:** reatribuir/associar referências de modo consistente e conservar vínculo a agendamentos, oportunidades, fontes de captação, históricos, interações, observações e auditoria. O registro secundário poderá ter identificador histórico ou referência de mesclagem para rastrear origens sem permanecer como duplicata ativa; **sua forma técnica, exposição e prazo de guarda ainda não estão aprovados**.
6. **Segurança multiempresa:** mesclar somente cadastros que pertençam à **mesma empresa autorizada**. Validar identificadores, permissões, estado dos registros e vínculos no backend, prevenindo mesclagem entre tenants e perdas por concorrência. Transação/rollback e idempotência são requisitos técnicos a detalhar.
7. **LGPD e confidencialidade:** não utilizar mesclagem para contornar restrições de acesso, consentimentos, obrigações legais, pedidos de eliminação/correção ou regras de minimização. Dados incompatíveis sensíveis exigem revisão, não combinação indiscriminada.
8. **Operações distintas:** **IN-027** permite exclusão definitiva controlada e **IN-023** permite arquivamento; **mesclar não constitui autorização de apagar historicamente o cadastro secundário ou dados necessários à auditoria**. Detecção de duplicidade continua **somente alerta** até a decisão manual.
9. **Plano e quotas:** a mesclagem manual está incluída no **Básico Cortesia**, sem exigir upgrade; a quantidade legítima de leads e contatos segue sem teto comercial rígido (IN-024). **Não** aprova limpeza/mesclagem em massa sem revisão, motor automático de deduplicação nem API externa de mesclagem.

**PENDÊNCIAS de detalhamento antes da implementação:** tratamento de conflitos entre consentimentos e canais, contatos vinculados a organizações/identificadores incompatíveis, regra para combinações lead-contato, concorrência e transação, notificações e referência ao cadastro secundário, preview e confirmação visual, histórico imutável da operação, possibilidade e prazo de desfazer/reverter (não aprovada), política de inativação dos duplicados, restauração e retenção/anonimização. Nenhuma mesclagem deve ocultar evidências ou excluir vínculos sem autorização própria.

**IN-029 aprovou a separação de dados estruturados no Básico, depois estendida aos quatro planos pela IN-031. IN-030 aprovou os mesmos tipos de anexos; IN-031 fixou franquias 1/5/20/50 GB somente de arquivos; IN-032 aprovou avisos e bloqueio de novos anexos.** Arquivos temporários, retenção e recuperação permanecem pendentes.

### Separação de armazenamento de dados estruturados e arquivos — IN-029 APROVADA em 2026-11-01

**Regra aprovada:** no **CRM Básico Cortesia**, os **dados estruturados de leads, contatos e seus históricos** (como nomes, telefones, e-mails, dados cadastrais, observações textuais e histórico de operações) **não consomem uma franquia comercial fixa de armazenamento de arquivos/anexos**. Isso mantém a decisão **IN-024: sem teto comercial rígido de quantidade de leads e contatos** recebidos dos formulários Imagine ou cadastrados manualmente. A franquia de arquivos, se vier a existir, será contabilizada e definida separadamente.

**Distinções obrigatórias:**
1. **Dados estruturados:** registros normais de leads/contatos, histórico e informações textuais, sob controle de acesso, uso justo, medidas técnicas contra abuso, disponibilidade e segurança. Não mostrar “espaço de anexos esgotado” como motivo para impedir cadastro/recepção legítima de um lead.
2. **Anexos e documentos permanentes:** **IN-030 aprovou os mesmos formatos no Básico e nos pagos**, como imagens, PDFs e documentos comuns; **IN-031 aprovou franquias por empresa de 1 GB (Básico), 5 GB (Essencial), 20 GB (Profissional), 50 GB (Empresarial)** apenas para arquivos/anexos, deixando **dados estruturados fora da franquia nos quatro planos**. O limite geral anterior de **5 MB por arquivo** permanece até alteração explícita.
3. **Arquivos temporários:** uploads de **importação CSV/XLSX** (IN-025), prévias de processamento, downloads/arquivos de **exportação CSV/XLSX** (IN-026) e outros artefatos técnicos devem seguir regras **próprias a definir** para tamanho, segurança, retenção transitória, expiração, limpeza e auditoria. A aprovação dessas operações não concede depósito permanente de arquivos.
4. **Sem ilimitado técnico:** a inexistência de franquia comercial fixa para dados estruturados **não significa recursos computacionais ou armazenamento físico infinitos**. Manter proteções antispam, limites técnicos proporcionais e escalabilidade, **sem quota comercial oculta por número de contatos** e sem descartar silenciosamente leads já confirmados como recebidos.
5. **Integridade e privacidade:** políticas de retenção, backup, restauração, descarte, LGPD e obrigações legais dos registros estruturados **ainda serão definidas**. Preservar vínculos de agenda, oportunidades, origens e trilhas de auditoria conforme regras próprias, inclusive quando houver arquivamento (IN-023), mesclagem (IN-028) ou exclusão controlada (IN-027).
6. **IN-031 (decisão posterior):** para armazenamento de **arquivos/anexos por organização**, passam a vigorar **Básico 1 GB; Essencial 5 GB; Profissional 20 GB; Empresarial 50 GB**, **substituindo as franquias pagas anteriores de 1/5/10 GB**. Dados estruturados não são contabilizados na franquia **em nenhum dos quatro planos**. A IN-029 continua preservada e ampliada.
7. **Separação financeira/operacional:** não criar cobrança, upgrade compulsório ou bloqueio de leads legítimos devido ao uso dos dados estruturados; a futura franquia de arquivos deverá ter condições claras e não alterar os contratos do Onepage sem autorização.

**IN-030 APROVADA:** mesmo catálogo seguro de anexos; **IN-031:** franquias 1/5/20/50 GB só de arquivos/anexos nos quatro planos; **IN-032:** avisos internos em 80%/95% e bloqueio somente de novos anexos a 100%. **PENDENTES:** detalhes técnicos de aviso, arquivos temporários e duração, adicionais, retenção e recuperação. Limite por arquivo permanece 5 MB até eventual revisão. Não extrapolar a retenção de **90 dias das notificações internas da agenda** (IN-018) para dados de contato ou históricos comerciais.

**IN-030 aprovou paridade de tipos de anexos; IN-031 aprovou posteriormente franquias de 1/5/20/50 GB para arquivos/anexos por empresa nos quatro planos, mantendo dados estruturados fora da franquia em todos eles.**

### Paridade de tipos de anexos entre Básico e planos pagos — IN-030 APROVADA em 2026-11-01

**Decisão expressa:** o **CRM Básico Cortesia permite anexar a leads e contatos os mesmos tipos de arquivo que os planos pagos Essencial, Profissional e Empresarial**. Não criar listas de extensões/MIME mais restritivas por se tratar do Básico. Entre os arquivos comuns estão **imagens, PDFs e documentos usuais**, conforme a **lista segura e centralizada de formatos efetivamente autorizados do CRM geral**. O catálogo exato de extensões/MIME e eventuais revisões de formatos são regras transversais, não permissões comerciais diferentes entre esses planos.

**Regras preservadas e alcance da aprovação:**
1. O administrador autenticado poderá anexar e consultar os documentos **nos leads/contatos da própria empresa**, com validação de tipo real de arquivo, antivírus/segurança, proteção de armazenamento, isolamento multiempresa, controle de acesso e histórico de inclusão/substituição/exclusão.
2. **Tipos aceitos iguais** não significam acesso a módulos pagos, a envios de arquivos por APIs externas, a armazenamento ilimitado nem a funcionalidades avançadas de gestão documental não aprovadas no Básico.
3. **Limite técnico global já aprovado no CRM geral: 5 MB por arquivo**, para os planos e formatos permitidos. A IN-030 **não o altera**. Revisões futuras precisam de aprovação explícita.
4. **IN-031 aprovou posteriormente as franquias definitivas para esta etapa de planejamento:** **Básico 1 GB, Essencial 5 GB, Profissional 20 GB, Empresarial 50 GB** de arquivos/anexos por organização. A quota considera arquivos e versões efetivamente armazenados após otimizações, conforme regras gerais; **dados estruturados ficam fora em todos os planos**. Os valores pagos anteriores 1/5/10 GB são históricos e foram substituídos.
5. **IN-029 continua válida:** dados estruturados de leads/contatos e seus históricos textuais no Básico **não consomem a franquia comercial fixa de anexos**. Esgotamento de futura quota de arquivos não deve impedir o recebimento de leads legítimos do formulário (IN-024).
6. Uploads temporários de importação e arquivos temporários de exportação (IN-025/IN-026), política de expiração, retenção, backup e recuperação **ainda serão detalhados separadamente**; não inferir que ocuparão a franquia permanente de anexos.

**IN-031 APROVADA POSTERIORMENTE:** franquias totais de arquivos/anexos **1/5/20/50 GB**, dados estruturados excluídos da franquia **nos quatro planos**. **IN-032 APROVADA POSTERIORMENTE:** uso/disponível visíveis, avisos internos **80%/95%**, apenas novos anexos bloqueados **a 100%**, arquivos anteriores e dados estruturados preservados. **PENDENTES:** adicionais, catálogo MIME, histórico de versões/visibilidade, temporários, retenção e detalhamento técnico/UX dos avisos.

**IN-031 aprovou as capacidades dos quatro planos; IN-032 aprovou mostrar uso/espaço disponível, avisar internamente em 80% e 95%, bloquear apenas novos uploads de anexos ao chegar a 100% e preservar arquivos anteriores e dados estruturados. Próxima decisão: se haverá **armazenamento adicional sem mudança de plano**; capacidade, condições e preços em decisões separadas.

### Franquias de armazenamento de arquivos/anexos para quatro planos — IN-031 APROVADA em 2026-11-01

**Aprovação expressa após revisão do cenário Amplo:** a franquia comercial **por empresa/organização**, compartilhada por todos os usuários autorizados, contabilizará **somente arquivos e anexos**, e **não dados estruturados** (cadastros, textos, leads, contatos, históricos e registros normais) **em nenhum dos quatro planos**. Isso amplia a separação originalmente aprovada para o Básico em IN-029 a Essencial, Profissional e Empresarial.

| Plano | **Franquia aprovada de arquivos/anexos por organização** | Situação |
| --- | ---: | --- |
| **Básico Cortesia** | **1 GB** | Nova franquia aprovada |
| **Essencial** | **5 GB** | Substitui 1 GB anterior |
| **Profissional** | **20 GB** | Substitui 5 GB anteriores |
| **Empresarial** | **50 GB** | Substitui 10 GB anteriores |

**Escopo e salvaguardas:**
1. **Medir apenas arquivos/anexos efetivamente armazenados** sob a organização, depois de otimização segura, contabilizando versões adicionais que permaneçam efetivamente guardadas conforme diretriz geral. Dados estruturados do CRM, inclusive textos, observações, histórico de atendimento, agendamentos e oportunidades, **não consomem a franquia em qualquer plano**.
2. **Não confundir franquia de arquivos com limite de registros.** O Básico mantém leads/contatos sem teto comercial rígido (IN-024); os planos pagos preservam suas próprias regras de uso justo, segurança e integridade.
3. **O limite geral anterior de 5 MB por arquivo e as verificações de segurança permanecem inalterados**, até deliberação própria. A IN-030 mantém os mesmos tipos de anexos em todos os planos.
4. **IN-032 aprovou posteriormente exibição de espaço usado/disponível, avisos internos em 80% e 95% e bloqueio de novos uploads de anexos a 100%.** Preservar acesso autorizado aos arquivos existentes, sem impedir leads/contatos ou dados estruturados. A regra de bloqueio não permite descarte silencioso de arquivo já aceito, nem autorização de cobrança ou upgrade automático.
5. **Sem contratação automática:** não transformar excesso de espaço em assinatura/cobrança adicional, upgrade forçado ou ativação de pacote sem aceite expresso (IN-010). A contratação de armazenamento adicional e seu preço **não foram aprovados**.
6. **Arquivos transitórios** de importação CSV/XLSX (IN-025), exportação CSV/XLSX (IN-026), quarentena, backups, logs e mecanismos de resiliência **ainda precisam de política específica** para determinar contabilização, tempo de vida e descarte. A decisão presente não os transforma automaticamente em anexos permanentes nem autoriza comprometer segurança por economia de franquia.
7. **Isolamento multiempresa, acesso privado, auditoria, LGPD e retenção por categoria** permanecem necessários; franquias não concedem acesso cruzado a dados e não mudam assinaturas comerciais do Portal Imagine.
8. **Histórico documental:** os valores pagos anteriores **Essencial 1 GB / Profissional 5 GB / Empresarial 10 GB** deixam de ser a franquia aprovada vigente após a IN-031. Evitar exibir o baseline histórico como quota atual.

**IN-032 APROVADA POSTERIORMENTE:** exibir uso/disponível, avisos internos em 80%/95% e bloqueio somente de novos uploads de anexos a 100%, preservando arquivos já armazenados e funcionamento dos dados estruturados. **Pendências específicas:** telas, mensagens e disparos técnicos dos avisos; adicionais e preços; contagem detalhada de versões; temporários, backup, retenção/recuperação e upgrades/downgrades. **Nenhum desenvolvimento de software autorizado até a aprovação do planejamento.**

### Transparência, avisos e bloqueio de novos anexos ao atingir a franquia — IN-032 APROVADA em 2026-11-01

**Decisão expressa:** para **todos os quatro planos CRM Imagine** (Básico Cortesia, Essencial, Profissional e Empresarial), exibir ao usuário autorizado o **armazenamento utilizado e o espaço disponível** da franquia de **arquivos/anexos** por empresa (IN-031). Emitir **avisos internos no CRM ao atingir 80% e 95% da franquia**. Ao atingir **100%**, **bloquear somente novos uploads de anexos**, sem apagar nem impedir acesso autorizado aos arquivos existentes e **sem bloquear leads, contatos, históricos, agendamentos ou outros dados estruturados**.

**Regras aprovadas e diretrizes de segurança:**
1. **Visibilidade:** mostrar consumo, capacidade total, saldo disponível e percentual para a organização autenticada, sempre com a quota efetiva do plano (Básico 1 GB, Essencial 5 GB, Profissional 20 GB, Empresarial 50 GB). Somente **arquivos/anexos** compõem o consumo comercial, não registros estruturados em nenhum dos planos.
2. **Alertas preventivos:** notificação **interna no ambiente autenticado do CRM** ao atingir **80%** e outra ao atingir **95%**; conteúdo objetivo com utilização, capacidade e consequência do limite. A IN-032 **não aprova** e-mail, WhatsApp, push externo nem cobrança automática; a estratégia de reaviso, central de notificações e persistência específicas para armazenamento são detalhes a definir e **não herdam automaticamente os 90 dias das notificações da agenda** (IN-018).
3. **Ao esgotar espaço:** a 100% de arquivos/anexos utilizados, **impedir apenas novas inclusões de anexos**, conservando os arquivos existentes acessíveis ao usuário autorizado. Se uma inclusão, mesmo abaixo de 100%, **ultrapassaria o espaço disponível**, ela não deve ser confirmada como aceita: verificar espaço antes de efetivar e evitar excesso por uploads simultâneos. Explicar o limite ao usuário, sem descartar documentos já aceitos.
4. **Continuidade do CRM:** não bloquear criação, recebimento, consulta, edição e arquivamento de leads/contatos ou seus textos e históricos; tampouco parar agendamentos, oportunidades e demais dados estruturados por esgotamento da franquia de anexos. **A integração de formulários Imagine deve continuar recebendo leads sem anexos** conforme IN-001/IN-024.
5. **Não apagar conteúdo para liberar espaço automaticamente:** nenhum arquivo já aceito será apagado, ocultado ou invalidado pelo consumo ter chegado a 100%. Exclusões por iniciativa autorizada, ciclos de retenção e segurança serão regidos por suas políticas próprias, sem destruição indevida ou evasão da auditoria.
6. **Segurança e escopo:** validar autorização e quota no backend por organização, preservando isolamento multiempresa, auditoria e proteção contra condições de corrida. Preservar limite global já aprovado de **5 MB por arquivo**, catálogo comum de anexos (IN-030) e prevenção de uploads inseguros.
7. **Adicionais não implícitos:** exibir ajuda contextual compatível com IN-010 quando atingir limite, mas não comprar, alterar plano, prometer pacotes ainda não aprovados nem condicionar recuperação de arquivos existentes a upgrade.
8. **Arquivos temporários de importação/exportação, backups, quarentena e suas quotas** continuam sob decisões próprias: não bloquear funções já aprovadas de CSV/XLSX por interpretar automaticamente qualquer arquivo técnico transitório como anexo permanente consumindo 100% da franquia.

**PENDÊNCIAS, sem prejuízo do que foi aprovado:** regras de notificação repetida a cada cruzamento de limiar, política de limpeza voluntária do espaço e visualização detalhada por arquivo, mensagens e UX, precisão/arredondamento de quota, processamento concorrente e reservas de quota, classificação dos temporários, pacotes extras e preços, efeitos do downgrade, retenção e recuperação. Não criar obrigações de notificações externas.

**Próxima decisão:** aprovar ou não **armazenamento adicional contratável sem mudança de plano** nos quatro planos, mediante aceite expresso e sem alterar o funcionamento do Básico Cortesia; tamanhos, preços e ciclos comerciais seriam definidos em etapa própria.

### Situação consolidada dos complementos às IN-023 a IN-032

- **IN-024 APROVADA:** sem teto comercial rígido de quantidade de leads e contatos, tanto os recebidos via site como os cadastrados manualmente, sujeitos a uso justo e controles técnicos. **IN-029 aprovada posteriormente:** dados estruturados não contam na franquia comercial fixa de arquivos; parâmetros técnicos de proteção, capacidade operacional e retenção permanecem pendentes, sem quota comercial disfarçada.
- **IN-025 APROVADA:** importação básica via CSV e XLSX, com mapeamento, prévia, validação e alerta de possíveis duplicidades, sem mesclagem automática. **Pendentes:** limites técnicos por arquivo/lote, regras de correspondência de campos, tratamento de linhas inválidas, política de duplicidades (ignorar/atualizar/decidir individualmente), concorrência e relatório final.
- **IN-026 APROVADA:** exportação de leads/contatos em CSV e XLSX, **todos os registros autorizados ou somente os filtrados**, protegida e auditada, com limites técnicos a detalhar. **Pendente:** campos exportáveis sensíveis, duração/link de download e arquivos temporários, tarefas assíncronas, proteção em caso de volumes grandes e política de requisições LGPD (sem paywall para direitos legais).
- **IN-027 APROVADA — exclusão definitiva controlada:** somente registros elegíveis com confirmação expressa; registros com vínculos ou obrigações de preservação exigem fluxo protegido, respeitando LGPD e auditoria. **PENDENTES:** critérios técnicos de elegibilidade, tratamento dos vínculos, política de retenção/anônimização e recuperação. **Mesclagem manual aprovada posteriormente na IN-028**, sem mesclagem automática.
- **Retenção e restauração de arquivados**: duração do histórico, interfaces, auditoria e regras de dados pessoais.
- Detalhes opcionais de campos e filtros, cadastro manual de interações e registro de situação comercial, sem conceder funcionalidades premium por inferência.

**Próxima decisão a submeter ao responsável:** **possibilidade de contratar armazenamento adicional sem alterar o plano**; depois quantidades/preços dos pacotes, arquivos temporários, retenção e recuperação. **Nenhuma programação autorizada até aprovação do planejamento.**

## Módulos seguintes ainda pendentes

3. Oportunidades e funis.
4. Agenda e serviços — direitos operacionais dentro das quotas já aprovadas.
5. Modelos de automações comerciais — biblioteca e vagas ativas correntes.
6. Relatórios, importação/exportação e LGPD.
7. Plano, contratação e cobrança.

**Referências:** [CRM Básico e matriz geral](08-plano-basico-cortesia.md), [Agenda/usuário e IN-021](11-agenda-manual-basico-cortesia.md), [Regra global IN-010](12-limites-e-upgrade-contextual.md), [Registro de decisões](decisoes.md).


## IN-033 — Armazenamento adicional opcional (APROVADA em 01/11/2026)

- Organizações dos quatro planos — **Básico Cortesia, Essencial, Profissional e Empresarial** — podem **contratar capacidade extra para arquivos e anexos**, sem trocar de plano.
- O adicional aumenta somente a capacidade disponível de arquivos/anexos da organização, mantendo inalterados os direitos e limites do plano (usuários, agenda, automações e demais funcionalidades).
- Exemplo: o Básico Cortesia conserva sua franquia de **1 GB** (IN-031) e pode contratar armazenamento adicional sem adquirir usuários ou funcionalidades dos planos superiores.
- A contratação será **opcional e condicionada a aceite explícito**; não autoriza cobrança automática, compra implícita nem alteração unilateral de plano.
- Permanecem válidas as regras da IN-032 (80% e 95%: avisos internos; 100%: bloqueio somente de novos uploads de anexos, preservando arquivos e dados estruturados).
- **Pendências independentes:** tamanhos de pacotes, preços, forma/ciclo de cobrança, renovação/cancelamento, tratamento da capacidade excedida após término do adicional e detalhes de armazenamento temporário. Não presumir valores ou condições.
- **Somente documentação e planejamento:** nenhuma programação iniciada por esta decisão.


## IN-034 — Cinco pacotes de armazenamento adicional (APROVADA em 01/11/2026)

Os seguintes **cinco pacotes opcionais de espaço adicional para arquivos/anexos** estão disponíveis para contratação por organizações de **qualquer um dos quatro planos**: **Extra P +1 GB, Extra M +5 GB, Extra G +10 GB, Extra GG +25 GB e Extra Empresarial +50 GB**. O nome Extra Empresarial não o restringe ao plano Empresarial: também poderá ser contratado por Básico Cortesia, Essencial e Profissional. O espaço contratado acrescenta capacidade à franquia base (IN-031), sem upgrade, sem alterar direitos funcionais ou quantidades de usuários, profissionais, agendamentos e automações. Aplicam-se IN-032 e IN-033 (avisos e bloqueio somente de novos uploads; contratação opcional com aceite explícito). **Não foram aprovados ainda** preços, método e recorrência de cobrança, combinação de pacotes, possibilidade de múltiplas unidades, ciclo de vigência, cancelamento, tratamento de uso excedente após redução nem requisitos operacionais. Nenhuma programação autorizada.


## IN-035 — Pacotes extras cumulativos, inclusive repetidos (APROVADA em 01/11/2026)

- Uma organização de **qualquer dos quatro planos** pode contratar **vários pacotes extras simultâneos**, inclusive repetir o mesmo pacote IN-034, **sem teto comercial fixo** para a capacidade extra contratada.
- A capacidade total de anexos da organização corresponde à **franquia base do plano (IN-031) acrescida da soma das capacidades extras contratadas e ativas**. Exemplo ilustrativo: Básico Cortesia 1 GB + Extra M 5 GB + Extra G 10 GB = 16 GB.
- Cada contratação é opcional e exige **aceite explícito**, sem compra/cobrança automática. Acúmulo de armazenamento **não concede** novos usuários, permissões ou funcionalidades.
- Aplicar controles técnicos de segurança, prevenção de abuso, proteção contra falhas e capacidade operacional. Esses controles não constituem teto comercial oculto.
- Regras IN-032 de visualização, avisos em 80%/95% e bloqueio **apenas de novos uploads de anexos** ao atingir 100% seguem válidas sobre a capacidade efetivamente disponível.
- **Pendentes, sem aprovação implícita:** preços, ciclos e meios de cobrança, faturamento, renovação, cancelamento, redução da capacidade e tratamento de arquivos quando o uso superar a capacidade remanescente.
- **Planejamento/documentação somente; nenhuma implementação autorizada.**


## IN-036 — Cancelamento de capacidade adicional com preservação de arquivos (APROVADA em 01/11/2026)

- Após o **término efetivo** de um pacote adicional de armazenamento, recalcular a capacidade como franquia base (IN-031) mais extras ainda ativos (IN-034/IN-035).
- **Preservar integralmente todos os arquivos e anexos já existentes**, mesmo quando a utilização ultrapassar a capacidade remanescente. Arquivos continuam acessíveis conforme permissões; não apagar, ocultar ou tornar inacessíveis automaticamente por excesso de uso.
- Se o uso exceder a nova capacidade, **bloquear exclusivamente novos uploads de anexos** enquanto não houver regularização. Continuam possíveis o cadastro e a operação de leads, contatos, observações, agendamentos e demais dados estruturados, observadas as demais permissões e regras do plano (IN-029/IN-031/IN-032).
- Permitir regularização pela **liberação voluntária de espaço** conforme regras de exclusão e retenção aplicáveis, ou pela **contratação explícita de novo armazenamento adicional**, sem compra, cobrança ou upgrade automático.
- Permanecem em aberto o momento efetivo do cancelamento, questões de cobrança e reembolso, detalhes da exclusão voluntária, retenção, recuperação e notificações operacionais. Nenhuma implementação de software foi autorizada.


## IN-037 — Recorrência mensal de armazenamento adicional (regra confirmada em 01/11/2026)

- Cada pacote extra contratado é uma **assinatura com pagamento recorrente mensal, por prazo indeterminado**. Não há fim de período contratual predeterminado; a assinatura permanece vigente enquanto o cliente não a cancelar.
- A adesão à recorrência exige **contratação e aceite explícitos**; a renovação recorrente do pacote já contratado não equivale a contratar automaticamente novos pacotes extras. Aplica-se a todos os quatro planos e aos pacotes cumulativos IN-033 a IN-035.
- Após o cancelamento **efetivo** e a redução da capacidade, observar integralmente a preservação e o bloqueio seletivo definidos em IN-036.
- **Pendente de decisão:** quando a redução de capacidade ocorre depois do pedido de cancelamento (na hora ou ao encerrar o ciclo mensal já pago), além de preço, detalhes de cobrança, reembolso e rotinas financeiras. Não inferir prazo fixo nem tratar o mero fim de um mês como cancelamento automático.
- Atualização somente documental, sem iniciar implementação.


### Complemento IN-037 — Cancelamento no ciclo mensal já pago (APROVADO em 01/11/2026)

**Complemento aprovado em 01/11/2026:** ao solicitar o cancelamento, a renovação e as próximas cobranças recorrentes do pacote cancelado são interrompidas; a capacidade extra permanece ativa até o encerramento do ciclo mensal já pago. Ao terminar esse ciclo, a capacidade é recalculada e aplica-se a IN-036: arquivos existentes preservados e, havendo excesso, bloqueio apenas de novos uploads. Não se presume reembolso ou nova cobrança.


## IN-039 — Anexos acessíveis somente em sessão autenticada e autorizada (APROVADA em 01/11/2026)

- Visualizar, pré-visualizar ou baixar documentos e demais anexos do CRM requer **usuário logado na plataforma**, com sessão válida, além de **permissão efetiva para o recurso e organização**. Não basta conhecer o endereço do arquivo.
- Impedir links públicos ou URLs pré-assinadas de acesso direto ao provedor que continuem funcionando para terceiros sem login, mesmo por poucos minutos. Uma URL pré-assinada é um token portador e, isoladamente, **não comprova que quem a utiliza é o usuário autenticado**.
- A arquitetura deve validar autenticação, escopo por organização e autorização **no backend em cada requisição de acesso**, inclusive prévias, miniaturas e solicitações de download; pode fazer streaming por gateway/proxy autenticado ou solução técnica equivalente que preserve esta garantia.
- Não expor nomes de buckets, chaves, credenciais nem URLs públicas de objetos como mecanismo de acesso; manter objetos privados e registrar trilha de auditoria pertinente, respeitando a política de privacidade.
- Compartilhar um endereço interno não deve permitir acesso a quem não esteja autenticado e autorizado. Medidas técnicas não conseguem impedir capturas de tela ou redistribuição de arquivo já baixado legitimamente; esse risco requer controles adicionais organizacionais.
- **Detalhes técnicos pendentes:** política de cache, processamento de previews, streaming eficiente, escala, controle de sessões, expiração e nível de auditoria. Somente documentação; não programar nesta etapa.


## IN-040 — Permissão única de visualização e download de anexos (APROVADA em 01/11/2026)

- A autorização de **acesso ao anexo** é uma **permissão única** que abrange **visualização, pré-visualização e download**; não criar direitos distintos de 'somente visualizar' e 'baixar'.
- A permissão continua sujeita ao escopo efetivo do usuário, organização/empresa e registro ao qual o arquivo está vinculado. Não concede acesso irrestrito a outros anexos nem a outras organizações.
- Mantém integralmente a **IN-039**: acesso apenas com sessão autenticada e autorização validada em cada requisição; nenhuma URL pública ou pré-assinada transferível pode permitir acesso fora dessa sessão.
- Pré-visualizações, miniaturas e downloads devem aplicar a mesma regra de autorização. Auditoria e controles técnicos serão detalhados na arquitetura.
- Essa decisão não impede cópia de conteúdo já disponibilizado legitimamente; não autoriza compartilhamento público.
- **Somente documentação; não iniciar programação.**


## IN-041 — Somente logs técnicos e de segurança para acessos a anexos (APROVADA em 01/11/2026)

- **Não implementar auditoria funcional individual de cada visualização, pré-visualização ou download** de documentos/anexos, nem apresentar histórico de quem abriu ou baixou cada arquivo.
- **Manter apenas logs técnicos e de segurança estritamente necessários** para diagnóstico, desempenho, controle de abusos, investigação de incidentes e cumprimento de obrigações aplicáveis, com minimização de dados, proteção e acesso restrito; detalhar futuramente quais eventos são registrados e os prazos de retenção.
- Os logs técnicos eventualmente podem conter metadados de requisições e identificadores necessários à segurança, mas **não devem se transformar em rastreamento funcional de leituras e downloads**.
- A IN-041 **não altera** IN-039 (login, autorização por empresa/arquivo em cada acesso, sem links públicos transferíveis) nem IN-040 (permissão única para visualizar e baixar). Também não elimina trilhas de auditoria de mudanças, exclusões, permissões ou outras ações que já tenham regras específicas.
- **Planejamento/documentação apenas**, nenhuma implementação autorizada.


## IN-042 — Exclusão definitiva imediata de anexos sem lixeira (APROVADA em 01/11/2026)

- Funcionário com permissão efetiva para excluir um anexo poderá solicitar **exclusão definitiva imediata, mediante confirmação explícita**, sem passagem por lixeira e **sem restauração funcional** do anexo excluído.
- Após confirmação e verificação de permissões e obrigações de preservação, o arquivo deverá deixar de estar acessível imediatamente pelo CRM e a remoção definitiva no armazenamento principal deverá ser executada conforme os processos técnicos necessários, sem oferecer período de recuperação ao usuário.
- Quando houver **obrigação legal/regulatória, bloqueio de preservação ou retenção aplicável**, impedir a exclusão indevida ou preservar a cópia obrigatória sob acesso restrito, de acordo com a política legal; informar o usuário da restrição sem divulgar informações indevidas.
- **Backups técnicos, versionamento, replicação e propagação de exclusões** seguem política técnica a definir; não interpretar exclusão imediata da interface como eliminação instantânea de todas as cópias físicas. Não prometer irrecuperabilidade absoluta antes da definição desses prazos.
- Continuam válidas as verificações de autenticação/autorização IN-039, permissão única de visualização/download IN-040 e a decisão IN-041 de não auditar cada leitura/download. Manter trilha de auditoria para a **ação de exclusão** conforme regras comerciais já aprovadas.
- **Somente documentação; implementação não autorizada.**


## IN-043 — Retenção técnica de backups de anexos por 30 dias (APROVADA em 01/11/2026)

- Manter as cópias técnicas de backup dos anexos por **30 dias**, observando exceções de retenção legal, obrigações regulatórias e preservações aplicáveis.
- Os backups destinam-se à continuidade operacional e recuperação de desastre, **não são lixeira** e não autorizam recuperação funcional de anexos intencionalmente excluídos sob a IN-042.
- A exclusão legítima de um anexo deve continuar efetiva mesmo se for restaurado um backup antigo. A arquitetura deverá manter registro durável e confiável das exclusões (por exemplo, tombstones, journal ou mecanismo equivalente), reaplicá-las na restauração **antes de disponibilizar o acesso a arquivos**, e testar a prevenção de reativação indevida.
- Manter segregação de acesso e permissões, políticas de expurgo e preservação legal; evitar restaurar automaticamente versões deletadas a áreas acessíveis pelos usuários.
- **Pendentes técnicos:** desenho de replicação, agendamento, armazenamento protegido, tempo de expurgo, testes de restauração e tratamento de obrigações legais. Nenhuma programação iniciada.


## IN-044 — Replicação incremental assíncrona a cada 15 minutos (APROVADA em 01/11/2026)

- Executar **replicação incremental assíncrona a cada 15 minutos** em **servidor separado** da aplicação CRM, copiando somente objetos/versões elegíveis ainda não replicados, sem bloquear o usuário enquanto a cópia de backup é feita.
- Manter **fila persistente**, tratamento idempotente, controle de concorrência e limites de CPU, RAM e banda; **retentativas** de falhas e **verificação de integridade** nas cópias concluídas.
- Monitorar o **atraso real** (idade do arquivo ainda não protegido mais antigo, falhas e backlog), não apenas o número de execuções. **RPO-alvo de 15 minutos** para anexos é meta de engenharia **dependente de testes**, não SLA/garantia assegurada simplesmente pela periodicidade. Planejar alertas e capacidade de processamento de pico.
- Manter **retenção técnica de backups por 30 dias (IN-043)**; preservar trilha técnica de exclusões para que arquivos legitimamente apagados não reapareçam em restaurações (IN-042/043). Não usar replicação ingênua que restaure exclusões.
- A escolha definitiva de fornecedor de armazenamento e preços dos adicionais (IN-038) continua pendente. O par B2 principal + R2 backup segue somente candidato em análise, não contratação aprovada.
- **Somente planejamento e documentação; nenhuma programação autorizada.**


## IN-045 — Monitoramento completo da replicação de anexos (APROVADA em 01/11/2026)

- Disponibilizar **painel técnico de monitoramento** de replicação de anexos, restrito à equipe operacional autorizada, com status das execuções, atraso real do arquivo pendente mais antigo, backlog de filas, falhas e integridade.
- Gerar alertas quando o **atraso real de proteção ultrapassar o RPO-alvo de 15 minutos** (IN-044), em caso de **falhas persistentes**, crescimento/acúmulo de filas e **falhas de integridade** das cópias.
- Executar **retentativas e recuperação automáticas** quando seguras, com controle para evitar processamento duplicado. Evitar alertas repetitivos por falhas transitórias resolvidas automaticamente: agrupar eventos relacionados, deduplicar notificações e sinalizar resolução.
- A frequência de execução de 15 minutos não garante o RPO; aferir o atraso efetivo e reportar violações. Preservar retenção técnica de 30 dias (IN-043) e impedir reativação de arquivos excluídos (IN-042).
- **Pendências para decisões futuras:** canais e destinatários, gravidade/escalonamento, períodos de silêncio, retenção das métricas/logs e políticas de resposta a incidentes. Não aprovar notificações externas ao cliente por esta decisão.
- **Somente documentação e planejamento; nenhuma programação iniciada.**


## IN-046 — Canais de alertas da replicação (APROVADA em 01/11/2026)

- Apresentar os alertas operacionais de replicação no **painel administrativo interno da Imagine**, acessível apenas à equipe autorizada, nos termos da IN-045.
- Enviar **e-mails aos responsáveis técnicos** quando ocorrerem **falhas persistentes ou incidentes críticos**. Falhas transitórias recuperadas automaticamente não geram e-mails repetitivos; aplicar agrupamento/deduplicação e estado de resolução.
- **Não enviar aos clientes das empresas** mensagens automáticas de alerta de backups/replicação. O e-mail técnico deve conter somente informações operacionais mínimas, sem anexos, conteúdo dos documentos ou dados pessoais desnecessários.
- Preservar verificações e monitoramento de RPO-alvo de 15 minutos, filas, integridade e retentativas (IN-044/IN-045). A definição dos critérios objetivos de gravidade/persistência, lista de responsáveis, escalonamento e prazos será realizada separadamente.
- **Somente planejamento e documentação. Nenhuma programação autorizada.**


## IN-047 — Níveis de gravidade dos alertas de replicação (APROVADA em 01/11/2026)

- **Informativo:** falhas transitórias já recuperadas automaticamente; registrar no painel técnico, sem e-mail automático de incidente.
- **Atenção:** atraso real do anexo ainda não protegido **superior a 15 minutos** (RPO-alvo IN-044); exibir aviso no painel técnico, manter retentativas e acompanhamento.
- **Crítico:** atraso real de replicação **superior a 60 minutos**, **paralisação da replicação** ou **falha de integridade**. Exibir no painel técnico **e enviar e-mail aos responsáveis técnicos**, conforme IN-046. Paralisação detectada e falha de integridade são críticas sem precisar esperar 60 minutos.
- Usar o atraso **efetivamente medido**, não somente a periodicidade de agendamento. Aplicar deduplicação, agrupamento, registro de recuperação e prevenção de alertas repetitivos conforme IN-045/IN-046.
- **Pendências:** definição dos sinais objetivos de paralisação e integridade, tempos de detecção, destinatários e reenvios, escalonamento, plantão e encerramento de incidentes. Não enviar alertas de backup a clientes finais.
- Planejamento/documentação apenas; nenhuma programação autorizada.


## IN-048 — Recuperação automática controlada da replicação (APROVADA em 01/11/2026)

- Permitir **reinicialização segura de processos de replicação**, reconexão aos provedores, **retentativas controladas de transferências** e **reprocessamento idempotente das filas persistentes**, sem bloquear o uso normal do CRM.
- Impor **limites de tentativas, concorrência, CPU, RAM, banda e ciclos de reinício**; aplicar backoff e mecanismos contra tempestades de reprocessamento. Evitar duplicação, inconsistências e loops infinitos.
- Registrar ações automáticas em **auditoria técnica restrita**, preservando a decisão IN-041 de não auditar individualmente visualizações/downloads de anexos.
- Quando a falha persistir ou for crítica, **escalonar à equipe técnica** por painel e e-mail de acordo com IN-045, IN-046 e IN-047, evitando notificações repetidas.
- **Proibir ações destrutivas automáticas**: não excluir anexos, não sobrescrever backups íntegros, não enfraquecer retenções ou permissões, não restaurar arquivos legitimamente excluídos (IN-042/043).
- **Pendências:** critérios operacionais exatos de retentativa/reinício, procedimentos manuais, limites de segurança e testes de recuperação. Somente planejamento; nenhuma programação autorizada.


## IN-049 — Testes periódicos de recuperação de backups (APROVADA em 01/11/2026)

- Executar **verificação de integridade a cada replicação** de anexos, validando a correspondência entre a origem elegível e o backup após a cópia.
- Realizar **diariamente restauração de amostra** dos anexos ainda elegíveis, em ambiente isolado, com conferência de conteúdo, metadados e integridade.
- Realizar **semanalmente teste ampliado de restauração**, cobrindo maior amostra, tipos de arquivos, tamanhos, falhas e cenários de recuperação.
- Realizar **trimestralmente simulação completa de recuperação de desastre**, incluindo procedimentos de recuperação dos arquivos e dependências necessárias, mensurando tempos e validando objetivos RPO/RTO, sem alterar produção.
- **Preservar exclusões legítimas** conforme IN-042 e IN-043: utilizar registros de exclusão/tombstones e controles de autorização para impedir reativação de documentos excluídos em qualquer restauração; testes não podem disponibilizar cópias de arquivos excluídos aos usuários.
- Manter o **ambiente de testes isolado**, acesso técnico restrito, proteção da confidencialidade e eliminação segura de cópias temporárias de teste conforme política técnica futura. Falhas de integridade/restauração são tratadas e notificadas conforme IN-045, IN-046, IN-047 e IN-048.
- **Pendências:** amostragem, duração, critérios de aceitação, procedimentos automatizados, proteção de dados de teste e custeio. Apenas documentação/planejamento; nenhuma programação autorizada.


## IN-050 — Contingência controlada de acesso a anexos em incidentes graves (APROVADA em 01/11/2026)

- Em **incidente grave de indisponibilidade do armazenamento principal**, permitir à **equipe técnica autorizada** ativar uma contingência temporária para servir **arquivos íntegros disponíveis no backup**, sem convertê-lo em armazenamento principal permanente e sem alternância automática não autorizada.
- Todo acesso aos arquivos durante a contingência deve permanecer condicionado a **sessão autenticada no CRM, organização correta e permissão efetiva por anexo**, com validação de cada requisição, sem links públicos ou URLs pré-assinadas compartilháveis (IN-039/040).
- Antes de permitir acesso, conferir a integridade da cópia e a vigência das exclusões legítimas (IN-042/043), aplicando o registro durável de exclusões para **não reativar anexos apagados** por causa de versões antigas do backup.
- Registrar tecnicamente a autorização, ativação, monitoramento e desativação da contingência, com controle restrito de responsáveis e segurança. Não criar auditoria funcional individual de visualizações/downloads (IN-041).
- Contingência é **temporária e controlada**, sem autorizar automaticamente novos uploads no backup, substituição de arquivos íntegros ou sincronização destrutiva. Regras de escrita, retorno ao principal, gatilhos, limites e testes de failover ainda serão especificadas.
- Permanecem válidas a replicação de 15 minutos (IN-044), monitoramento e alertas (IN-045–047), recuperação automática não destrutiva (IN-048) e testes isolados (IN-049).
- Apenas documentação e planejamento; nenhuma programação iniciada.


## IN-051 — Contingência somente leitura, sem novos uploads (APROVADA em 01/11/2026)

- Com a contingência controlada de IN-050 ativada em falha do armazenamento principal, o backup poderá atender **somente visualização, pré-visualização e download de anexos já existentes, íntegros e autorizados**; cumprir obrigatoriamente autenticação e permissões IN-039/040 e impedir reativação de arquivos excluídos (IN-042/043).
- **Bloquear temporariamente todos os novos uploads de anexos** enquanto o armazenamento principal estiver indisponível. Não gravar arquivos de clientes no destino de backup, nem receber novos anexos em buffer/armazenamento temporário como alternativa implícita.
- O **restante do CRM** (leads, contatos, observações, agenda e demais dados estruturados) permanece operacional **desde que os respectivos serviços estejam disponíveis** e suas próprias permissões/limites sejam respeitados.
- Informar de maneira clara aos usuários a indisponibilidade temporária de envio de anexos, sem afirmar que um upload bloqueado foi aceito.
- Contingência continua temporária, de ativação técnica humana e com backup em modo estritamente de leitura. Critérios de retorno ao principal, verificações e UX serão detalhados depois.
- Apenas planejamento e documentação; nenhuma programação autorizada.


## IN-052 — Retorno controlado ao armazenamento principal (APROVADA em 01/11/2026)

- Após a recuperação aparente do provedor principal, executar **verificações automáticas de disponibilidade, integridade dos objetos, isolamento e permissões por empresa/arquivo, exclusões legítimas e testes seguros de upload/download** no armazenamento principal.
- **Exigir autorização explícita de responsável técnico autorizado** para encerrar a contingência da IN-050/IN-051 e reabilitar uploads de anexos. A recuperação do provedor e o sucesso dos testes **não fazem a transição automática**.
- A validação não deve expor arquivos a links públicos, enfraquecer a IN-039/040 ou reativar anexos legitimamente excluídos (IN-042/043). Testes usarão objetos técnicos isolados e não sobrescreverão arquivos de clientes.
- Havendo qualquer falha na validação, **manter a contingência somente leitura**, permanecer com novos uploads bloqueados e registrar/escalonar o problema via logs e alertas técnicos IN-045–048.
- Registrar tecnicamente testes, decisão humana, data/hora e transição; preservar opção de reversão segura se a retomada falhar. Critérios exatos de aceitação, duração da estabilização e rollback ainda pendentes.
- Somente planejamento/documentação; não iniciar programação.


## IN-053 — Proteção automática contra nova falha grave após reativação (APROVADA em 01/11/2026)

- Se o armazenamento principal voltar a apresentar **falha grave após o retorno controlado da IN-052**, o CRM deve **suspender preventivamente novos uploads de anexos** para evitar inconsistência, confirmação indevida de envio ou perda de dados.
- Emitir alertas técnicos pelo painel e, para incidentes críticos, por e-mail aos responsáveis conforme IN-045, IN-046 e IN-047; manter deduplicação e registros técnicos.
- **Não alternar automaticamente ao backup:** a reativação da contingência de leitura da IN-050/051 exige **nova autorização explícita de membro autorizado da equipe técnica**, após checagens de segurança, integridade e exclusões legítimas.
- Proteger os arquivos existentes, permissões por empresa e por recurso, autenticação obrigatória e exclusões legítimas (IN-039–043); não criar mecanismos de upload temporário nem escrita no backup de contingência.
- O restante do CRM continua operacional sempre que seus serviços estiverem disponíveis. Não afirmar sucesso de uploads que foram bloqueados ou não concluídos.
- **Pendências:** sinais objetivos de falha grave, janela de estabilização, comportamento do acesso a arquivos enquanto aguarda contingência e roteiro de retomada. Apenas documentação, sem programação.


## IN-054 — Detecção inteligente e proporcional de falhas do armazenamento (APROVADA em 01/11/2026)

- Diferenciar **falhas isoladas/transitórias** de **incidentes sistêmicos** mediante verificações técnicas de disponibilidade, integridade, resposta do provedor e confirmação de gravação; retentar operações recuperáveis com limites e segurança.
- Em **risco confirmado de indisponibilidade, perda ou corrupção**, **suspender automaticamente novos uploads de anexos**, proporcionalmente ao escopo da falha e sem afetar indevidamente leads, contatos, agenda ou outros dados estruturados.
- **Nunca exibir sucesso em upload cuja gravação íntegra não tenha sido confirmada**. Erros e estados incertos devem ser tratados de modo seguro e transparente, sem perda silenciosa.
- Falhas isoladas não devem disparar bloqueio indiscriminado de todos os uploads ou do CRM. Falhas de integridade relevantes exigem investigação e proteção dos objetos afetados.
- Preservar **IN-050 a IN-053**: contingência somente leitura, retorno validado com aprovação humana e nova ativação da contingência somente por responsável autorizado. Alertas e recuperação técnica conforme IN-045 a IN-049.
- **Pendências:** sinais e limiares objetivos, janelas de detecção, escopo de suspensões e testes de aceitação. Apenas documentação; nenhuma implementação autorizada.


## IN-055 — Recuperação inteligente de uploads interrompidos (APROVADA em 01/11/2026)

- Diante de queda de conexão, timeout ou falha transitória no envio de anexo, aplicar **retentativas automáticas seguras e limitadas**, quando tecnicamente viáveis, com prevenção de duplicidades por operação idempotente (sem criar duas cópias/vínculos por reenvio).
- **Conferir gravação e integridade** do objeto no armazenamento antes de confirmar o upload como concluído ao usuário e antes de estabelecer o vínculo definitivo com o registro no CRM.
- Quando a recuperação automática não for possível, **informar claramente a falha e permitir o reenvio manual**. Não prometer retomada de bytes parcial sem suporte técnico; a estratégia de upload fracionado/retomável será detalhada separadamente.
- Não marcar como bem-sucedido um upload interrompido ou com estado incerto; não contornar quotas de anexos (IN-031/032), controles de segurança, indisponibilidade do armazenamento nem bloqueio durante contingência (IN-039/051/053/054).
- **Pendências:** políticas de timeout e novas tentativas, gestão/validade/limpeza de arquivos temporários, proteção contra uploads órfãos e detalhamento de UX. Apenas documentação; nenhuma programação autorizada.


## IN-056 — Uploads temporários por até 6 horas (APROVADA em 01/11/2026)

- **Reter por no máximo 6 horas** os arquivos ou fragmentos temporários de uploads interrompidos/incompletos, exclusivamente para permitir recuperação segura da operação quando viável (IN-055).
- Após esse prazo, efetuar **limpeza automática segura** de fragmentos, sessões e objetos temporários, com retentativas e monitoramento técnico da limpeza quando necessário; não oferecer recuperação do upload expirado.
- **Não contabilizar o arquivo incompleto/não confirmado na franquia comercial de anexos da empresa**; somente arquivos com gravação íntegra validada e efetivamente aceitos como anexos passam a ocupar franquia, respeitando IN-031/032.
- Os arquivos temporários podem gerar custos reais de infraestrutura, portanto aplicar quotas técnicas, isolamento por organização, autenticação, permissões, proteção contra abuso e controles de concorrência e armazenamento temporário. Não confundir ausência de cobrança de franquia com armazenamento ilimitado de fragmentos.
- Não declarar sucesso nem criar vínculo definitivo antes da confirmação de integridade, e não contornar bloqueios de uploads durante contingência (IN-051/053/054).
- A retenção temporária de 6 horas **não altera** os 30 dias de retenção técnica de backups (IN-043) nem a política de exclusão definitiva (IN-042).
- **Pendências:** contagem do prazo inicial, política de reserva de capacidade, limiares técnicos, gestão de uploads multipart/órfãos e rotinas de limpeza. Somente documentação, sem programação.


## IN-057 — Reserva temporária de espaço para uploads concorrentes (APROVADA em 01/11/2026)

- Antes do início do upload de anexo, verificar a **capacidade efetiva da empresa** (franquia base IN-031 + extras ativos IN-033–035) e criar **reserva temporária atômica** para o tamanho do arquivo, respeitando limite de 5 MB por arquivo (IN-030), autorizações e bloqueios de IN-032/051/053/054.
- Aplicar **controle de concorrência por organização**, impedindo que uploads simultâneos reservem mais bytes do que a disponibilidade calculada como capacidade total menos arquivos efetivamente ocupados menos reservas ativas. As reservas diminuem o espaço **disponível para novos uploads**, mas **não são contabilizadas como arquivos efetivamente utilizados** na franquia comercial.
- **Confirmar e contabilizar uso efetivo somente após gravação íntegra, verificação e vínculo válido do anexo** (IN-055); efetuar conversão idempotente da reserva em uso efetivo, sem dupla contagem nem anexos duplicados.
- **Liberar reserva** quando houver falha, cancelamento ou expiração; a retenção máxima dos uploads temporários incompletos permanece de **6 horas (IN-056)**. Conciliar reservas órfãs e fragmentos com limpeza segura e monitoramento técnico, sem liberar prematuramente reservas de uploads ainda válidos.
- Preservar isolamento entre empresas e impedir uso de metadados de tamanho manipulados para ultrapassar a franquia. Detalhes exatos de validação de tamanho, renovação da reserva, transações, expiração e reconciliação serão definidos tecnicamente.
- Planejamento/documentação somente; nenhuma programação autorizada.


## IN-058 — Até 5 uploads simultâneos por empresa (APROVADA em 01/11/2026)

- Cada organização/empresa poderá manter **até 5 uploads de anexos em processamento simultâneo**, considerados de forma agregada entre todos os seus usuários e sessões. **Demais uploads aguardam em fila** até a liberação de uma vaga.
- Este é um **controle técnico de concorrência, não uma quota comercial**: não fixa teto para a quantidade total de anexos legítimos armazenados dentro da franquia contratada e não gera exigência de upgrade de plano.
- Na futura implementação, o controle deverá ser **coordenado por organização no backend**, para que usuários, navegadores ou servidores diferentes não ultrapassem o limite compartilhado; a fila pode ter apresentação no navegador, mas não depender somente dele para a garantia.
- Respeitar reserva atômica de capacidade e prevenção de excesso (IN-057), limite atual de 5 MB por arquivo (IN-030), validação de integridade e idempotência (IN-055) e limpeza de uploads temporários incompletos em até 6 horas (IN-056). Não confirmar arquivo antes da gravação íntegra e não permitir uploads durante contingência bloqueada (IN-051/053/054).
- **A definir separadamente:** ordenação e política de prioridade da fila, cancelamento, limite de espera, tratamento de queda de sessão, momento de reserva de capacidade para itens aguardando e experiência do usuário. Não presumir contratação automática ou cobrança por esse controle.
- Exclusivamente planejamento/documentação; nenhuma programação autorizada.


## IN-059 — Fila FIFO de uploads por organização (APROVADA em 01/11/2026)

- Processar uploads de anexos **pela ordem de chegada (FIFO — First In, First Out) à fila de cada empresa/organização**, independentemente do funcionário que solicitou o envio. Não aplicar rodízio por usuário nem prioridade configurável sem aprovação futura.
- Respeitar **até 5 uploads em processamento simultâneo por empresa** (IN-058). Quando uma vaga é liberada, o próximo item **elegível** na ordem de chegada pode iniciar, com controle centralizado entre todos os usuários, sessões e instâncias.
- Garantir **ordenação consistente no backend**, inclusive sob concorrência; filas de diferentes empresas ficam isoladas. Uma tarefa bloqueada ou inválida não deve permitir estouro de limite, desrespeito a permissões ou confirmação indevida de upload.
- Preservar reserva atômica de capacidade (IN-057), gravação íntegra e idempotente (IN-055), retenção temporária por até 6 horas para uploads incompletos (IN-056), regras de quotas e bloqueio em contingência (IN-032/051/054).
- **Pendências:** como lidar com cancelamentos, expiração e desconexão de itens aguardando, retentativas/reentrada na fila, quando reservar espaço de itens em espera e tratamento de uploads que não podem iniciar. Não presumir prazo de fila ou direito de ultrapassagem.
- **Somente planejamento/documentação; nenhuma programação autorizada.**


## IN-060 — Cancelamento seguro de uploads em fila e em transmissão (APROVADA em 01/11/2026)

- Permitir ao usuário autorizado **cancelar uploads ainda aguardando na fila FIFO** (IN-059) ou **em transmissão**, antes da conclusão definitiva. Remover o item pendente ou interromper com segurança o envio em andamento.
- Liberar a **reserva de capacidade** do upload cancelado (IN-057) e a **vaga de concorrência** quando efetivamente desocupada (IN-058), permitindo iniciar o próximo item elegível pela ordem FIFO. Evitar liberações prematuras enquanto a operação não foi efetivamente interrompida.
- Executar **limpeza segura dos arquivos, fragmentos e sessões temporárias**, com controle de objetos órfãos, retentativas e respeito à política de temporários (IN-056). Não manter lixo técnico ou consumo comercial de um upload não confirmado.
- Tratar de maneira **idempotente a disputa entre cancelamento e conclusão**: se o arquivo tiver sido finalizado/confirmado antes da efetivação do cancelamento, informar isso ao usuário e encaminhar eventual remoção à **IN-042** (exclusão definitiva, sob autorização e confirmação), sem apagá-lo silenciosamente pelo processo de cancelamento.
- Preservar autenticação, permissão por empresa, prevenção de duplicidades, confirmação apenas após integridade verificada (IN-039/055) e o bloqueio de uploads em contingência (IN-051/053/054).
- **Pendências técnicas:** estado exato de cancelamento, UX, sincronização entre servidores, corrida cancelamento × conclusão, forma de interromper multipart upload e tempo operacional de limpeza. Somente planejamento/documentação; nenhuma programação iniciada.


## IN-061 — Recuperação segura de uploads interrompidos após saída do navegador (APROVADA em 01/11/2026)

- Ao fechar a aba/navegador, perder conectividade ou sair do CRM, a transmissão que depende da conexão do navegador pode ser interrompida, **sem considerar a operação automaticamente concluída**. Preservar dados temporários elegíveis para recuperação, conforme IN-055 e IN-056, por **até 6 horas**; o prazo não é renovado indefinidamente por novas tentativas.
- Qualquer tentativa de **retomada exige sessão autenticada válida, verificação renovada de permissões do usuário, organização, recurso, capacidade e bloqueios operacionais**, antes de ler ou completar os dados enviados; não retomar apenas com um identificador ou link compartilhável.
- Quando a retomada parcial não for tecnicamente possível ou o navegador perder acesso ao arquivo local, **permitir reenvio manual com nova seleção do arquivo**, usando prevenção de duplicidade e conferência de integridade. Não prometer que o navegador guarda acesso ao arquivo original após fechamento.
- **Cancelar com segurança itens pendentes não recuperáveis**, liberar vagas e reservas somente quando a tarefa correspondente deixar de estar ativa, reconciliar concorrência e fila FIFO (IN-057 a IN-060), e limpar automaticamente dados temporários expirados (IN-056).
- Arquivos já confirmados com gravação íntegra permanecem como anexos válidos; nenhuma ação de reconexão pode confirmar um envio incompleto, criar duplicidade ou contornar quotas e bloqueios de contingência (IN-032/051/054/055).
- **Detalhes pendentes:** token de sessão retomável, proteção de fragmentos, ordem de retorno à fila, estado e tempo de reserva, limites de tentativas, mensagens da interface e implementação de upload retomável no navegador.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-062 — Upload recuperado retorna ao fim da fila FIFO (APROVADA em 01/11/2026)

- Se um upload interrompido por perda de conexão ou encerramento do navegador puder ser recuperado dentro do prazo máximo de **6 horas** (IN-056/061), ele **não preserva sua antiga posição** e **volta ao final da fila FIFO da própria organização** (IN-059), após as verificações exigidas.
- Enquanto a transmissão estiver efetivamente interrompida/desconectada, o upload **não reserva uma das 5 vagas de processamento simultâneo** da empresa (IN-058); a vaga desocupada pode ser destinada ao próximo upload elegível pela ordem FIFO. A vaga só é liberada quando o processamento anterior estiver efetivamente interrompido, sem contagem dupla em situações de corrida.
- Antes de permitir recuperação e reenfileiramento, **revalidar sessão autenticada, permissões por usuário/empresa/recurso, integridade e elegibilidade do upload, bloqueios de contingência e capacidade disponível** (IN-032/039/051/054/057/061). Nenhuma credencial antiga ou identificador de upload dispensa a autorização atual.
- Respeitar idempotência, prevenção de duplicidades e confirmação somente após gravação íntegra (IN-055); um upload expirado, cancelado ou já concluído não pode ser reintroduzido como envio novo por erro de concorrência.
- A **regra de retenção/liberação da reserva de capacidade de armazenamento durante a desconexão ainda precisa de definição específica**: a IN-062 define a liberação da vaga de processamento, mas não determina automaticamente a política dessa reserva de bytes, a ser tratada na IN-063.
- Apenas planejamento e documentação; nenhuma programação autorizada.


## IN-063 — Liberação da reserva após interrupção confirmada (APROVADA em 01/11/2026)

- Após a **interrupção efetivamente confirmada** de um upload (por queda de conexão, fechamento de aba/navegador ou outra interrupção recuperável), **liberar a reserva temporária de capacidade** do arquivo, sem reter espaço indisponível aos demais funcionários durante a desconexão. A liberação deve ser coordenada, segura e idempotente, sem agir sobre gravações ainda efetivamente ativas.
- Os **fragmentos e metadados temporários elegíveis à recuperação** podem ser preservados por **até 6 horas** desde o prazo aplicável da IN-056/061; ocupam recursos técnicos reais, mas **não são contabilizados como uso comercial efetivo de anexos confirmados**. Aplicar controles de consumo, isolamento, proteção e limpeza/expiração.
- Antes de qualquer retomada, exigir **nova autenticação, verificação de permissões do usuário/empresa/recurso, elegibilidade, bloqueios operacionais, tamanho e capacidade disponível**, com **nova reserva atômica** conforme IN-057. Se a capacidade estiver indisponível, não retomar nem confirmar upload por conta da reserva antiga.
- Após validações e nova reserva bem-sucedidas, o upload retomado **reentra no final da fila FIFO** conforme IN-059/062, respeitando até **5 uploads simultâneos por empresa** (IN-058); não reivindica a vaga nem a posição original.
- Evitar dupla contagem, reservas órfãs, liberações prematuras, anexos duplicados e confirmações sem gravação íntegra (IN-055/060). A política para apresentar insuficiência de espaço no retorno será especificada separadamente (IN-064).
- **Somente planejamento e documentação; nenhuma programação autorizada.**


## IN-064 — Insuficiência de espaço na recuperação de upload (APROVADA em 01/11/2026)

- Quando um upload interrompido for elegível à recuperação, mas **não houver capacidade disponível para nova reserva** (IN-057/063), comunicar claramente ao usuário a falta de espaço, sem iniciar transmissão, sem declarar upload concluído e sem utilizar a reserva antiga já liberada.
- **Preservar os fragmentos temporários recuperáveis somente até o fim do prazo original máximo de 6 horas** definido em IN-056/061, **sem renovar ou reiniciar esse período** por tentativas de recuperação, falta de espaço, fechamento de navegador ou reconexão.
- O funcionário poderá **solicitar manualmente nova tentativa** enquanto o prazo original estiver válido, após liberar espaço na franquia ou ativar capacidade adicional devidamente contratada. Em cada tentativa exigir **nova autenticação válida, autorização de usuário/organização/recurso, checagem de integridade e elegibilidade, limites técnicos, bloqueios operacionais e reserva atômica da capacidade necessária** (IN-032/039/051/054/057/063).
- Enquanto aguarda espaço ou ação do usuário, o upload **não ocupa nenhuma das cinco vagas de transmissão** (IN-058) e **não tem posição garantida na fila FIFO**. Após nova validação e reserva bem-sucedida, **reentra no fim da fila** (IN-059/062), sem ultrapassar uploads já aguardando.
- Se o prazo de recuperação expirar, **cancelar a recuperação e limpar com segurança os fragmentos e metadados temporários**, conforme IN-056/060/061, sem consumir franquia comercial como anexo confirmado.
- Não criar cobrança automática de armazenamento nem obrigar upgrade: capacidade adicional continua sujeita a consentimento comercial explícito (IN-033–037). Operações simultâneas podem esgotar novamente o espaço; reserva atômica evita sobrealocação.
- **Pendências:** mensagens de interface, exibição opcional de tempo restante, detalhes de corrida entre disponibilidade e reserva, tratamento técnico de fragmentos órfãos. Apenas planejamento/documentação; nenhuma programação autorizada.


## IN-065 — Validação rigorosa do tamanho real dos arquivos (APROVADA em 01/11/2026)

- Para cada upload, comparar o **tamanho real dos bytes recebidos e verificados no servidor** com (a) a **reserva temporária atômica** concedida para a empresa conforme IN-057/063 e (b) o **limite máximo de 5 MB por arquivo** vigente na IN-030. Não confiar apenas no tamanho declarado pelo navegador, extensão ou cabeçalhos do pedido.
- **Rejeitar sem aumento automático de reserva** qualquer arquivo que ultrapasse o tamanho reservado ou o limite de 5 MB, mesmo se a empresa ainda possuir franquia disponível. Interromper o processamento de modo seguro quando o excesso for identificado; não confirmar, publicar ou vincular como anexo válido.
- Quando o tamanho real for **menor que a reserva**, reconciliar a diferença e **liberar de modo atômico a capacidade excedente**, após a validação adequada, sem causar dupla liberação. Se o arquivo estiver dentro do espaço reservado, só converter a quantidade efetivamente confirmada em **uso comercial** após gravação íntegra, validação e vínculo idempotente (IN-055/057).
- Em qualquer rejeição, **limpar com segurança fragmentos/objetos temporários e sessões incompletas e liberar reservas e vagas** quando a interrupção estiver efetivamente confirmada, respeitando IN-056/058/060. Registrar eventos técnicos essenciais, sem exibir arquivo inválido como concluído.
- Manter controles de autenticação, autorização, isolamento por empresa, quotas, bloqueios de contingência e fila FIFO (IN-032/039/051/054/059/062). Não permitir que dados de cliente tenham seu tamanho declarado manipulado para superar limite ou reserva.
- **Detalhes pendentes:** convenção exata de MB (decimal/binária), implementação de verificação de tamanho em fluxo e mensagens da interface. Somente planejamento/documentação; nenhuma programação autorizada.


## IN-066 — Verificação rigorosa do formato real dos anexos (APROVADA em 01/11/2026)

- Para uploads de anexos, **validar no servidor o tipo real e a consistência do conteúdo**, considerando conjuntamente **extensão**, **tipo MIME detectado do conteúdo** (não apenas o MIME informado pelo navegador), **assinatura/magic bytes** e **estrutura interna quando aplicável** ao formato.
- Manter estritamente o **conjunto de formatos permitidos já aprovado na IN-030** para todos os planos; esta decisão não amplia os tipos aceitos nem altera o limite de **5 MB por arquivo**.
- **Rejeitar com segurança** arquivos disfarçados por extensão renomeada, arquivos malformados, incompatíveis ou cuja combinação de indicadores/estrutura não seja aceita pelos validadores do formato. Em falha ou resultado inconclusivo, **não confirmar, vincular nem disponibilizar** o upload como anexo válido.
- Implementar verificadores adequados por classe de arquivo, com limites seguros de análise para conteúdo comprimido/incorporado; **uma assinatura compatível ou MIME aparentemente correto não comprovam, por si sós, ausência de malware**, de conteúdo ativo ou de arquivos poliglotas. A política de varredura antimalware será tratada em decisão separada (proposta IN-067).
- Ao rejeitar, **interromper e limpar fragmentos/objetos temporários com segurança, liberar a reserva de capacidade e a vaga de processamento somente após encerramento efetivo**, sem dupla liberação, seguindo IN-056–060 e IN-063–065. Registrar dados técnicos mínimos necessários à análise e incidentes, sem expor conteúdo sensível.
- Preservar autenticação, permissões e isolamento por empresa (IN-039/040), limites de franquia, suspensão em contingência, fila FIFO, idempotência e **confirmação somente após gravação íntegra e verificações concluídas** (IN-032/051/054/055/057/059/062).
- **Detalhes pendentes:** matriz exata de validadores por formato, critérios para assinaturas ambíguas e arquivos compostos, mensagens da interface, processos de testes e análise de ameaças. **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-067 — Análise antimalware obrigatória para todos os anexos (APROVADA em 01/11/2026)

- **Todos os arquivos de anexo**, em todos os planos, serão submetidos a **análise antimalware obrigatória** antes de serem disponibilizados a qualquer usuário, somada à validação de extensão, MIME, assinatura e estrutura da IN-066 e à conferência de tamanho real da IN-065.
- Receber e manter o arquivo **isolado em área técnica privada de quarentena** durante as verificações. A quarentena não equivale a um anexo válido; os arquivos não poderão ser visualizados, baixados, vinculados como concluídos ou acessados por links públicos enquanto a análise estiver pendente, falhar ou resultar inconclusiva. Os controles de login, permissão por empresa e arquivo da IN-039 continuam obrigatórios.
- **Somente promover o arquivo ao estado de anexo confirmado** após gravação íntegra e validação de tamanho, formato e análise antimalware com resultado aprovado. Não informar sucesso definitivo antes disso; exibir estado de processamento com linguagem precisa, sem confundir recebimento temporário com conclusão.
- **Ameaças detectadas:** rejeitar o arquivo e efetuar descarte/limpeza segura de dados temporários, impedir disponibilização e registrar evento técnico mínimo para apuração, sem incluir o conteúdo sensível do anexo no log. A ação de rejeição não é uma exclusão funcional de anexo já concluído (IN-042).
- **Falha, indisponibilidade ou resultado inconclusivo da análise:** adotar política de **falha segura (fail closed)**: bloquear disponibilização e confirmação, aplicar **retentativas controladas**, sem liberação por confiança no MIME, ausência de resultado ou autorização comercial. A especificação dos intervalos e das tentativas será decidida posteriormente.
- Preservar o **prazo máximo original de 6 horas para arquivos temporários incompletos ou ainda não confirmados**, de acordo com IN-056/061, sem reiniciar o relógio por novas análises. Ao esgotar o prazo, encerrar o processamento pendente, remover fragmentos/objetos temporários com segurança e liberar reservas/vagas de forma coordenada e idempotente (IN-057/058/060/063–065), conforme os estados efetivos da operação.
- Respeitar quotas, concorrência, FIFO, exclusões legítimas, acesso autenticado e proteção contra duplicação; a análise de arquivos não deve violar isolamento entre empresas. Registrar **auditoria técnica** com data, resultado/código de erro, versão do mecanismo, correlação técnica e providências, minimizando dados pessoais e sem criar log comercial individual de visualizações ou downloads (IN-041).
- **O fornecedor/mecanismo antimalware permanece em aberto**, assim como tratamento de falsos positivos, horários e limiares de retentativas, fila de análise, disponibilidade do motor, critérios de aceitação técnica e resposta operacional. Nenhum antivírus é considerado infalível.
- **Apenas planejamento e documentação; nenhuma programação autorizada.**


## IN-068 — Indisponibilidade sistêmica do antivírus e suspensão preventiva de novos uploads (APROVADA em 01/11/2026)

- Diante de **indisponibilidade sistêmica confirmada** ou incapacidade generalizada do serviço antimalware de concluir suas análises obrigatórias, **suspender preventivamente a aceitação de novos uploads de anexos**. Falhas isoladas e transitórias devem ser distinguidas de uma indisponibilidade sistêmica para não bloquear uploads indevidamente (em coerência com a IN-054).
- **Anexos já recebidos, mas ainda não aprovados**, permanecem **isolados em quarentena privada**, sem visualização/download nem vínculo como anexo definitivo; nenhuma falha ou resultado inconclusivo libera o arquivo (IN-039/066/067).
- Aplicar **retentativas controladas** de verificação antimalware enquanto o serviço volta a funcionar, respeitando rigorosamente o **prazo original máximo de 6 horas** para temporários não confirmados (IN-056/061/067). A falha do antivírus, as retentativas e a reconexão não renovam a janela. Uma vez expirado o prazo, encerrar com estado não concluído, remover temporários com segurança e liberar/conferir reservas e vagas sem dupla liberação (IN-057/058/060/063–065).
- **Anexos já aprovados anteriormente continuam acessíveis**, sujeitos a login e autorização atual por empresa e arquivo; indisponibilidade do motor de varredura **não revoga a aprovação anterior por si só**. Os demais módulos do CRM permanecem operacionais desde que seus serviços estejam disponíveis.
- **Retomar a aceitação de novos uploads somente após validação da recuperação funcional do antivírus**, incluindo capacidade de executar análises e emitir resultados confiáveis; uma mera resposta de conectividade não é suficiente. A operação respeitará validação de formato/tamanho, integridade, quotas, fila FIFO, limites e os demais bloqueios ativos, sem contorná-los (IN-030/032/051/054/055/057–059/065–067).
- Manter alertas técnicos, logs mínimos de indisponibilidade e recuperação, estados de quarentena e mensagens de interface claras; nunca informar sucesso definitivo para arquivo ainda não analisado. Evitar dados pessoais/conteúdo de anexos em logs e não criar auditoria comercial de visualização/download (IN-041/045–047/067).
- **A definir posteriormente:** limiares de confirmação de indisponibilidade sistêmica, frequência/limite de retentativas, verificações de recuperação e procedimento de liberação do bloqueio, tratamento de casos suspeitos e falsos positivos. O mecanismo antivírus segue **não escolhido**, conforme IN-067.
- Exclusivamente planejamento/documentação; nenhuma programação autorizada.


## IN-069 — Revisão técnica controlada de falsos positivos do antivírus (APROVADA em 01/11/2026)

- Quando um arquivo enviado for **rejeitado por detecção antimalware**, permitir que um funcionário autorizado da empresa **solicite revisão técnica de possível falso positivo**. Registrar a solicitação e seus dados mínimos (empresa, usuário, data/hora, identificação técnica do evento, status e motivo declarado) sem armazenar conteúdo malicioso em logs ou expor dados sensíveis.
- A análise da contestação será responsabilidade da **equipe técnica Imagine com atribuições autorizadas**, com registro das etapas relevantes, resultado, responsável e justificativa em **auditoria técnica protegida**. Preservar isolamento entre empresas, acesso mínimo necessário e rastreabilidade.
- **Não restaurar nem disponibilizar arquivos já rejeitados**; a contestação **não constitui exceção** à quarentena, às exclusões legítimas, à proibição de acesso não autenticado nem às regras de análise antimalware. **Nenhum funcionário ou administrador, inclusive da Imagine, poderá liberar manualmente um anexo não aprovado** (IN-039/042/066–068).
- Se a avaliação indicar possível falso positivo e houver caminho seguro, orientar o funcionário a realizar **um novo upload**, após eventual correção legítima do problema de detecção/configuração. O novo arquivo deve cumprir **todas as etapas normalmente exigidas**, inclusive autorização, tamanho de até 5 MB, validação real de formato, integridade, reserva/capacidade, análise antimalware positiva e confirmação após gravação íntegra (IN-030/055/057/065–068).
- O processo de revisão **não altera nem renova** o prazo de retenção de temporários incompletos de **até 6 horas** (IN-056/061) e não autoriza resgate de arquivos que já foram eliminados.
- **Pendências:** canal de solicitação, perfis técnicos, níveis de urgência e prazo de resposta, orientações ao usuário e retenção da auditoria técnica. Apenas planejamento e documentação; nenhuma programação autorizada.


## IN-070 — Reanálise inteligente de segurança de anexos armazenados (APROVADA em 01/11/2026)

- Anexos anteriormente aprovados e armazenados poderão passar por **reanálise antimalware direcionada e baseada em risco**, quando houver atualizações relevantes de segurança, indicadores de novas ameaças, novas informações de detecção ou outros riscos concretos identificados.
- **Identificar e selecionar os arquivos potencialmente afetados** conforme as evidências técnicas disponíveis, em vez de impor indiscriminadamente uma nova varredura completa de todo o acervo a cada atualização. A reanálise será executada de modo seguro, com respeito a isolamento por empresa, recursos técnicos e integridade de objetos.
- Se uma **ameaça for confirmada em arquivo já armazenado**, **suspender o acesso ao arquivo afetado** (visualização, pré-visualização e download, inclusive por fluxos internos), sem expor seu conteúdo a funcionários; gerar alerta técnico, registrar evento e justificativa em **auditoria técnica protegida**, e encaminhar o caso para análise/remediação.
- A reanálise e o bloqueio **não autorizam exclusão automática** de um anexo já concluído. Qualquer exclusão deverá seguir a IN-042 e demais exigências legais/contratuais. Não restaurar objetos excluídos legitimamente (IN-043) nem criar exceções de disponibilização para arquivos identificados como perigosos (IN-067/069).
- **Preservar o acesso normal dos demais arquivos não afetados**, sempre sob sessão autenticada, autorização atual por organização/arquivo e controles de IN-039/040; não revogar em massa anexos sem indicação de risco.
- Minimizar dados pessoais nos alertas e registros técnicos, preservando evidências relevantes, identificação do mecanismo e resultado, responsáveis e momentos das mudanças de estado; não registrar visualizações/downloads no log de auditoria comercial (IN-041).
- **Pendências para próximas IN:** critérios detalhados de gatilho e seleção, prioridade e ritmo da reanálise, decisões sobre suspeita ainda não confirmada, triagem e desbloqueio de arquivos, controles de custo e desempenho. O mecanismo antivírus continua em aberto.
- Somente planejamento/documentação. **Nenhuma programação autorizada.**


## IN-071 — Bloqueio preventivo de anexos proporcional ao risco (APROVADA em 01/11/2026)

- Na **reanálise de segurança de anexos já armazenados** (IN-070), manter disponíveis para usuários devidamente autorizados os arquivos submetidos a **reanálise rotineira sem indícios concretos de ameaça elevada**; não bloquear todo o acervo nem todos os arquivos em análise.
- Se surgirem **indícios técnicos concretos de risco elevado**, aplicar **bloqueio preventivo temporário ao arquivo especificamente afetado**, abrangendo visualização, pré-visualização e download, inclusive em fluxos internos e eventuais acessos por backup/contingência. Bloquear acesso antes da conclusão da reanálise quando o risco justificar essa proteção.
- Quando uma reanálise com resultado **confiável e verificações de segurança concluídas descartar a suspeita**, **restabelecer o acesso normal** sujeito a autenticação e permissões atuais (IN-039/040). A reanálise isoladamente inconclusiva não autoriza desbloqueio de arquivo previamente bloqueado por risco elevado.
- Se houver **ameaça confirmada ou suspeita grave ainda não resolvida**, manter o bloqueio, notificar os responsáveis técnicos, registrar os resultados e submeter a investigação a fluxo técnico controlado; não criar exceção manual para liberar arquivo reconhecidamente perigoso.
- Preservar **auditoria técnica protegida** de indícios, gatilhos, estados, resultados, responsável/serviço, horários e decisões, minimizando dados sensíveis e sem criar registro comercial de visualização/download (IN-041).
- **Não excluir arquivos automaticamente** em razão de suspeita, reanálise ou confirmação; respeitar IN-042, retenções aplicáveis e exclusões legítimas, sem restaurar objetos excluídos. Arquivos não afetados continuam disponíveis conforme permissões vigentes.
- **A detalhar:** critérios técnicos objetivos de risco elevado, validação do resultado seguro, aprovação humana ou automática para desbloqueio e tratamento da análise inconclusiva. Apenas planejamento e documentação; nenhuma programação autorizada.


## IN-072 — Desbloqueio híbrido e seguro após reanálise de anexos (APROVADA em 01/11/2026)

- **Bloqueio preventivo sem ameaça confirmada (IN-071):** se novas verificações completas e confiáveis **descartarem a suspeita de risco elevado**, o sistema pode **restabelecer automaticamente o acesso ao arquivo**, sem aprovação humana adicional. O desbloqueio não dispensa a autenticação atual, as permissões por empresa/arquivo e as demais condições de acesso da IN-039/040.
- **Arquivo que já teve ameaça confirmada (IN-070):** só poderá voltar a ficar acessível se uma **nova análise completa tiver resultado aprovado, sem detecção pendente ou inconclusiva, E houver autorização explícita de integrante habilitado da equipe técnica Imagine**. Nenhuma dessas condições isoladamente basta.
- **Proibida a liberação manual por exceção** de arquivo cuja detecção de risco permaneça positiva, não concluída ou inconclusiva. A equipe não pode ignorar controles antimalware, substituir resultados técnicos por julgamento informal ou disponibilizar arquivos perigosos; preservar IN-067/069/070/071.
- Registrar **auditoria técnica protegida** de causa do bloqueio, versões/resultados das análises, transições de estado, identidade e data/hora do autorizador quando exigido, e justificativas. Minimizar dados sensíveis, sem auditoria comercial de eventos individuais de visualização/download (IN-041).
- Aplicar transições **atômicas, seguras e rastreáveis**, impedindo acesso entre uma reanálise positiva e a confirmação do desbloqueio. Qualquer nova indicação concreta de ameaça deverá reativar os controles de proteção de IN-070/071.
- **Não restaurar, reabrir ou reintroduzir anexos legitimamente excluídos**, inclusive a partir de backup (IN-042/043); o desbloqueio limita-se a arquivos ainda existentes de forma legítima.
- **A detalhar posteriormente:** critérios técnicos específicos de confirmação de resultado seguro, perfil e dupla checagem de autorização, prazos, mensagens de estado e comunicação de bloqueio/desbloqueio à empresa.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-073 — Notificações direcionadas sobre bloqueio e desbloqueio de anexos (APROVADA em 01/11/2026)

- Em **bloqueio preventivo, confirmação de ameaça ou desbloqueio de segurança** de um anexo já armazenado (IN-070–072), produzir **notificação interna direcionada** ao **administrador autorizado da organização** e ao **funcionário responsável pelo anexo**, caso ainda tenha permissão atual para conhecer o arquivo e seu estado. Evitar notificação em massa aos demais funcionários.
- A notificação deve informar de maneira clara o **estado do anexo (acesso suspenso por segurança ou acesso restabelecido)** e orientar o usuário sobre a indisponibilidade, **sem revelar assinatura de malware, dados de investigação, indicadores técnicos sensíveis ou conteúdo do anexo**. Exibir o próprio arquivo somente quando a autorização atual e o estado de segurança permitirem.
- **Checar permissões no momento de direcionar e de consultar a notificação**, preservando fronteiras entre empresas; retirar ou ocultar detalhes de notificações quando a pessoa perder acesso ao registro. O papel de administrador não deve suprimir restrições específicas de segurança do arquivo.
- Alertas sobre ameaças, falhas de varredura e decisões de bloqueio/desbloqueio permanecem no **painel e nos canais técnicos da Imagine**, de acordo com os controles já aprovados (IN-045–047/067–072), sem exposição de dados desnecessários ao cliente.
- Registrar transições e comunicações essenciais em **auditoria técnica protegida**, sem criar auditoria comercial individual de visualizações e downloads (IN-041). Integrar notificações à infraestrutura existente do CRM e preservar regras já aprovadas de retenção e arquivamento de notificações, sem inventar novos prazos nesta decisão.
- **Pendências:** identificação e transferência de responsabilidade do anexo, deduplicação e agregação de eventos, mudanças de permissão/conta desativada, mensagens e experiência da interface, critérios de status da notificação.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-074 — Referência visível e conteúdo inacessível para anexos bloqueados (APROVADA em 01/11/2026)

- Quando um anexo já armazenado estiver **bloqueado por segurança** conforme IN-070–073, **preservar a referência e os vínculos do anexo no histórico do CRM**, incluindo seus registros de lead, contato, oportunidade e demais entidades relacionadas. Não apagar ou ocultar indiscriminadamente o histórico empresarial.
- Somente **usuários atualmente autorizados** a conhecer o registro e o anexo poderão ver uma representação informativa com **metadados permitidos** (por exemplo, nome do arquivo, se permitido) e o status **acesso suspenso por segurança**. Não revelar conteúdo, ameaças técnicas detectadas, indicadores sigilosos ou outros metadados protegidos. Usuários sem permissão não poderão inferir informações do anexo por busca, notificações ou referências.
- **Impedir acesso ao conteúdo do arquivo bloqueado** por todos os caminhos, incluindo visualização, pré-visualização, download, miniaturas que exponham conteúdo, processamento por IA, extração/consulta automatizada, automações, exportações ou integrações; impor verificação de estado de bloqueio e permissões também no serviço de entrega, sem depender somente da interface. Manter a vedação a URLs de objeto reutilizáveis da IN-039.
- **Preservar o objeto original e os vínculos comerciais**, sem exclusão automática por bloqueio, conforme IN-042/070/071. O tratamento de cópias derivadas, conteúdo já extraído, caches e tarefas iniciadas anteriormente será especificado separadamente para impedir rotas indiretas de exposição.
- **Restabelecer acesso somente após as condições de desbloqueio seguro da IN-072** serem cumpridas; em toda tentativa de acesso, validar novamente autenticação, permissões por empresa/arquivo e estado atual de segurança.
- Os avisos direcionados seguem IN-073 e a auditoria técnica de segurança segue IN-070–072, sem criar auditoria comercial de cada visualização/download (IN-041).
- **A detalhar:** apresentação da referência e metadados por perfil, aplicação da política a cache/índices/derivados, cancelamento de trabalhos pendentes, mensagens de UX e testes de autorização. Somente planejamento e documentação; nenhuma programação autorizada.


## IN-075 — Proteção completa de conteúdos derivados de anexos bloqueados (APROVADA em 01/11/2026)

- Ao bloquear um anexo por segurança (IN-070–074), **propagar o bloqueio a todas as representações e conteúdos derivados gerenciados pelo CRM**, incluindo prévias, miniaturas, textos extraídos, metadados enriquecidos que exponham o conteúdo, índices de busca e vetoriais, respostas e consultas de agentes de IA, automações, integrações e rotas de exportação. Não depender exclusivamente de ocultação na interface: verificar estado de segurança e permissões também nos serviços de entrega/consulta.
- **Interromper ou cancelar os processamentos pendentes relacionados ao arquivo bloqueado**, impedindo geração, persistência, disponibilização ou distribuição de novos derivados após o bloqueio. Tratar eventos concorrentes de processamento × bloqueio por mecanismos seguros e idempotentes.
- **Invalidar caches gerenciados pelo CRM**, inclusive cópias temporárias e resultados de processamento sob seu controle, e impedir que índices e camadas de busca continuem servindo material derivado do anexo. Aplicar controles de revogação em serviços distribuídos e minimizar janelas de inconsistência; não afirmar revogação de cópias previamente baixadas por terceiros/dispositivos externos.
- **Preservar o arquivo original bloqueado, sua referência, seus vínculos com registros comerciais e os históricos legítimos** (IN-042/074), **sem exclusão automática** por detecção de ameaça. Quando resumos e outros registros históricos contiverem conteúdo extraído do arquivo bloqueado, **restringir o acesso aos segmentos associados** de maneira adequada, até decisão específica de rastreabilidade e apresentação dos trechos; não eliminar indiscriminadamente informações empresariais legítimas.
- **Revalidar autenticação, autorização por organização/arquivo e estado do bloqueio em cada acesso**, evitando vazamento por caminhos indiretos e respeitando a política de acesso autenticado (IN-039/040), sem links públicos reutilizáveis. Propagação a derivados deve preservar isolamento por empresa.
- **Desbloqueio dos conteúdos derivados somente quando o anexo puder ser desbloqueado com segurança conforme IN-072**, com revalidação dos derivados antes de reutilizá-los e sem restaurar objetos legitimamente excluídos (IN-042/043). Registrar ocorrências e ações de contenção em auditoria técnica mínima, sem logar conteúdo sensível ou criar auditoria comercial de visualizações/downloads (IN-041).
- **A detalhar:** rastreabilidade e classificação de trechos derivados em resumos de IA e históricos, mecanismos de invalidação de cache distribuído, reconstrução segura de índices, cancelamento de tarefas, tempos máximos de propagação e testes de ausência de acesso indireto.
- **Somente planejamento e documentação; nenhuma programação autorizada.**


## IN-076 — Rastreabilidade das fontes e restrição seletiva de conteúdo derivado por IA (APROVADA em 01/11/2026)

- Registrar, nas saídas de **agentes de IA** que utilizem anexos e outras fontes, **proveniência rastreável** entre o resumo, trecho ou unidade de conteúdo gerado e suas fontes identificáveis: organização, recurso de origem, anexo e versão/identificador de conteúdo quando aplicável, sem inserir cópias desnecessárias de dados confidenciais nos metadados. Aplicar segregação por empresa, permissões e acesso mínimo necessário.
- Se uma **fonte/anexo for bloqueado por suspeita ou ameaça de segurança**, propagar a restrição de IN-070–075 aos **trechos de resumos, históricos e demais produções de IA derivados dessa fonte**, de forma seletiva, impedindo visualização, exportação, busca, consulta por IA, integrações e reutilização do material protegido. A restrição deve ser aplicada nos serviços de acesso e não apenas como ocultação visual.
- **Preservar e manter acessíveis as informações realmente independentes do anexo afetado**, desde que os usuários tenham autorização atual. Quando a proveniência do material misto for insuficiente para distinguir com confiança as partes derivadas da fonte bloqueada, **bloquear a seção ou todo o resumo envolvido**, sem fingir precisão que não existe.
- **Preservar históricos empresariais legítimos, resumos, metadados de proveniência e versionamento**, com as restrições necessárias, **sem exclusão automática** de registros em razão do bloqueio. Respeitar a política de exclusão e retenção aplicável, incluindo IN-042/043, sem restaurar anexos legitimamente excluídos; a preservação não autoriza retenção ilimitada além de regras aplicáveis.
- Manter trilha de **auditoria técnica** de fontes relacionadas, alterações de estado, bloqueios seletivos, causa e resultados, com proteção contra acesso indevido, minimização de dados e sem auditoria comercial individual de visualizações/downloads (IN-041).
- **Bloquear o acesso indireto** via caches, índices, embeddings, extração, automações, integrações ou agentes de IA; interromper tarefas que tentem incorporar ou republicar os trechos bloqueados, em alinhamento à IN-075.
- **Pendências de especificação:** granularidade e estrutura da proveniência, retroatividade para históricos antigos sem fontes registradas, reconstrução e revalidação após desbloqueio seguro, tratamento de fontes alteradas/deletadas, testes de autorização, custos de índices/metadados e possíveis riscos de texto misto impossível de separar.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-077 — Recuperação controlada de derivados de IA após desbloqueio seguro (APROVADA em 01/11/2026)

- Quando o anexo de origem for **desbloqueado pelos critérios aprovados na IN-072**, considerar elegíveis à recuperação os conteúdos de IA antes restritos por IN-075/076, **sem liberar automaticamente tudo apenas porque a fonte voltou a estar acessível**.
- Antes de cada restabelecimento, **revalidar o estado atual de segurança da fonte, sua identidade e versão, proveniência dos trechos, integridade dos artefatos derivados e permissões atuais do usuário/organização/recurso**, inclusive no serviço de consulta/entrega. A validação da fonte deve respeitar as verificações de segurança e a autorização humana quando requerida na IN-072.
- **Restabelecer o acesso apenas aos trechos e representações efetivamente verificados como seguros**, preservando partes independentes e proibindo acesso indireto a derivados ainda restritos por busca, índices, cache, integrações, exportação, automações ou agentes de IA (IN-074–076).
- Se o conteúdo antigo estiver desatualizado, inconsistente com a versão atual da fonte ou não puder ser recuperado com segurança, **regenerar os trechos quando necessário e tecnicamente possível** a partir de fontes ainda existentes, aprovadas e autorizadas, sem sobrescrever silenciosamente versões anteriores; registrar nova versão e sua proveniência. A regeneração não pode ignorar limites, permissões, aprovações e controles de consumo de IA já definidos.
- **Manter as versões históricas antigas com acesso restrito** quando sua segurança, correspondência de fonte ou integridade não puder ser confirmada. Preservar histórico legítimo e rastreabilidade, sem exclusão automática por falha de validação e **sem reintroduzir anexos legitimamente excluídos** (IN-042/043).
- Registrar **auditoria técnica protegida** de bloqueio, condições de revalidação, fontes e versões utilizadas, regeneração, liberação de trechos e motivos de restrição, minimizando dados confidenciais e sem gerar logs comerciais de visualização/download (IN-041).
- **Pendências:** políticas detalhadas de reconciliação, critérios objetivos de integridade de derivados, custo/aprovação de regenerações com IA, avisos aos usuários e política de tratamento de registros antigos sem proveniência suficiente (proposta IN-078).
- **Apenas documentação de planejamento; nenhuma programação autorizada.**


## IN-078 — Reconstrução gradual da rastreabilidade de resumos antigos ou importados (APROVADA em 01/11/2026)

- **Resumos, conteúdos gerados por IA e históricos antigos ou importados**, criados antes dos mecanismos de proveniência da IN-076, poderão passar por **reconstrução gradual da rastreabilidade das fontes**, sem exigir regeneração geral indiscriminada nem alterar silenciosamente o texto original ou seu histórico de versões.
- **Registrar uma fonte como verificada somente quando houver evidências confiáveis e auditáveis** que associem a informação gerada ao anexo/documento/registro de origem, inclusive à versão correta quando possível. Correlações aproximadas, sem prova suficiente, devem ser explicitamente **marcadas como hipótese ou origem desconhecida**, nunca promovidas automaticamente a proveniência confirmada. Não inventar vínculos ou declarar segurança com base em confiança do modelo.
- **Sinalizar origens desconhecidas ou não verificadas** em metadados protegidos; preservar o isolamento por organização, a autenticação e a autorização por usuário/registro/anexo. Proteger esses metadados para que a rastreabilidade não vaze informações restritas de outros recursos.
- Quando um **anexo/fonte potencialmente relacionada** for bloqueado por motivo de segurança (IN-070–075), aplicar **restrição preventiva proporcional** ao conteúdo antigo que possa depender dessa fonte: restringir seletivamente trechos separáveis de modo confiável ou, se não houver separação segura, **bloquear a seção ou todo o resumo potencialmente afetado**, sem liberar conteúdo incerto por IA, busca, índices, exportações, cache, automações ou integrações (IN-075/076).
- **Preservar resumos, versões e históricos comerciais legítimos sem exclusão automática** por falta de proveniência ou por bloqueio de fonte, respeitando regras de retenção, exclusão legítima (IN-042/043) e desbloqueio seguro (IN-072/077). Preservar conteúdos efetivamente independentes da fonte bloqueada quando essa independência puder ser demonstrada com segurança.
- Registrar **auditoria técnica** sobre evidências utilizadas, alterações de status de proveniência, restrições e decisões, minimizando dados sensíveis. O procedimento deve ter limites de recursos e consumo de IA, sem presumir processamento ilimitado, cobrança extra ou uso de créditos sem política aprovada.
- **Pendências:** padrões de evidência e níveis de confiança, ordem de priorização dos legados, gatilhos de reconstrução, necessidade de revisão humana em casos inconclusivos, custo/consumo de IA, política para fontes alteradas ou excluídas, testes de segregação.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-079 — Reconstrução gradual de proveniência com prioridade de segurança (APROVADA em 01/11/2026)

- A reconstrução de proveniência de resumos antigos e importados definida na IN-078 será **gradual**, sem exigir processamento integral e imediato de todo o histórico. A prioridade será determinada sobretudo por **risco concreto de segurança e necessidade autorizada**.
- **Prioridade elevada:** conteúdos potencialmente associados a anexos bloqueados, incidentes de segurança, suspeitas relevantes de exposição ou outras necessidades de contenção. Aplicar imediatamente restrições necessárias de IN-070–078, **independentemente** de a reconstrução detalhada das fontes ainda estar pendente.
- **Prioridade intermediária:** solicitações de reconstrução por usuários e responsáveis técnicos **devidamente autorizados**, após validação de permissões por organização, resumo e fontes. Sem tratamento privilegiado que comprometa contenção de riscos elevados.
- **Prioridade baixa:** resumos legados sem risco conhecido e sem solicitação específica, processados progressivamente conforme capacidade técnica disponível. Na ausência de evidência confiável, classificar a proveniência como desconhecida/não verificada; não atribuir origens inventadas nem liberar dados restritos.
- Aplicar **limites técnicos de carga, CPU, memória, concorrência, orçamento e processamento** para evitar saturação do CRM, prejuízo às operações e consumo descontrolado. Mecanismos e quotas exatos ficam pendentes de especificação.
- **Não debitar automaticamente créditos comerciais de IA do cliente por verificações, contenções e rastreabilidade indispensáveis à segurança da plataforma.** Manter rastreabilidade de custos operacionais internos; eventuais análises ou regenerações opcionais que gerem cobrança/créditos exigirão política comercial e autorização explícitas, sem alteração implícita dos contratos. Não presumir processamento de IA irrestrito ou gratuito para solicitações opcionais.
- Preservar **auditoria técnica protegida**, controles de acesso e isolamento entre empresas, versões e históricos legítimos, restrições proporcionais e exclusão somente sob a política aplicável (IN-039–043/070–078). Não permitir acesso por IA, busca, exportação ou integrações a conteúdo bloqueado.
- **Pendências:** dimensionamento de filas/prioridades, limites e orçamentos, responsabilidade por custos de reconstruções opcionais, critérios de autorização e transparência de status ao usuário.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-080 — Reconstruções de proveniência por IA exclusivamente para segurança (APROVADA em 01/11/2026)

- **Não disponibilizar reconstruções opcionais de proveniência por IA para clientes** como funcionalidade de enriquecimento, melhoria comercial ou reprocessamento voluntário de resumos antigos/importados, **mesmo se o cliente possuir créditos comerciais ou plano pago**. Esta decisão encerra a hipótese de cobrança ou contratação de reconstruções opcionais por IA aventada na IN-079, sem alterar outros recursos de IA do CRM.
- Permitir reconstrução de proveniência com IA **exclusivamente quando necessária a controles de segurança** da plataforma (IN-075–079), como investigar fontes potencialmente contaminadas, conter exposição por anexos bloqueados, comprovar independência de trechos e reconstruir gradualmente a rastreabilidade dos legados enquanto controle de segurança. A prioridade continua baseada em risco, com processamento de baixa prioridade do passivo que integra o programa de segurança; solicitações de usuários autorizados só orientam priorização quando houver finalidade legítima de segurança, não criam uma opção comercial nova.
- **Não debitar créditos comerciais de IA do cliente** por essas verificações ou reconstruções obrigatórias e não gerar compras, cobranças ou consumo automático de franquias de IA em consequência delas. O custo operacional é técnico/interno da plataforma e sujeito a monitoramento, limites de CPU, memória, processamento, concorrência, taxa de utilização e orçamento, a detalhar.
- **Falhas e limites de processamento não podem causar liberação insegura:** manter restrições a fontes ou trechos incertos, auditoria técnica e rastreabilidade; não usar sem evidência confiável uma associação como fonte verificada, conforme IN-076–079.
- Preservar autenticação e permissões por empresa, versões e históricos legítimos, dados estruturados e medidas de bloqueio de conteúdo, **sem exclusão automática**; não restaurar anexos legitimamente excluídos (IN-039/042/043/070–079).
- **A definir posteriormente:** orçamento interno e limites por prioridade, critérios objetivos de necessidade de segurança, mecanismos de fila, recuperação de falhas do processamento de proveniência e acompanhamento técnico. Nenhuma reconstrução opcional de proveniência por IA está aprovada.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-081 — Recuperação automática controlada das reconstruções obrigatórias de proveniência por IA (APROVADA em 01/11/2026)

- Para reconstruções de proveniência de resumos antigos/importados **necessárias à segurança** (IN-076–080), manter **fila técnica persistente e segura**, isolada por empresa, com estados rastreáveis e operações idempotentes para evitar reprocessamento ou associação duplicada de fontes.
- Em **falha temporária do modelo, serviço de IA, infraestrutura ou processamento**, aplicar **retentativas automáticas controladas e limitadas**, com intervalos adequados e limites técnicos de concorrência, recursos e orçamento operacional interno. Não executar ciclos indefinidos de repetição nem promover resultado inconclusivo a proveniência verificada.
- **Priorizar incidentes críticos e tarefas ligadas a bloqueios ativos**, em detrimento de reconstruções de baixa prioridade, respeitando a política da IN-079. Quando houver indisponibilidade sistêmica ou limite técnico atingido, preservar tarefas pendentes na fila e pausar/reduzir processamento de prioridade inferior; alertar equipe técnica Imagine diante de falhas persistentes, atrasos ou incidentes críticos.
- **Retomar processamento de maneira gradual** após verificar a recuperação funcional do serviço e a capacidade disponível, com revalidação de autorizações e estado atual das fontes. A retomada **não desbloqueia automaticamente** conteúdos de fonte suspeita, não verificados ou com risco não resolvido: manter as restrições de IN-070–078 até as condições de desbloqueio seguro da IN-072/077.
- **Preservar resumos, versões, históricos legítimos, trilhas de auditoria e isolamento empresarial**, sem exclusão automática por falha do processamento, nem restauração de anexos excluídos legitimamente. Fontes sem prova confiável permanecem marcadas como desconhecidas/não verificadas (IN-078).
- **Não debitar créditos comerciais de IA dos clientes** nem gerar cobrança implícita pelo processamento obrigatório de segurança, conforme IN-079/080. Contabilizar custos técnicos internos, com orçamento/limites seguros e sem comprometer os controles de contenção.
- Registrar **auditoria técnica protegida** de tentativas, falhas, retomadas, versões de modelo/procedimento quando aplicável e resultados, sem exposição do conteúdo protegido nos logs. Alertas técnicos seguem IN-045–047 no que for compatível com essa rotina.
- **Pendências:** número máximo de tentativas, estratégia de retentativa, critérios de recuperação funcional, limites operacionais e tratamento dos itens que esgotarem as tentativas (proposta IN-082).
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-082 — Fila técnica para falhas persistentes da reconstrução obrigatória de proveniência por IA (APROVADA em 01/11/2026)

- Após **esgotar o número limitado de retentativas automáticas** permitido pela IN-081, **suspender automaticamente somente a tarefa de reconstrução de proveniência afetada**, evitando repetições indefinidas, loops de custo ou bloqueio de outras tarefas independentes.
- **Encaminhar a tarefa a uma fila técnica de falhas persistentes**, com estado explícito, prioridade de segurança, registro do erro/causa conhecida, contagem de tentativas, identificadores técnicos mínimos e horários relevantes; proteger a fila por autenticação e autorização da equipe técnica Imagine e por isolamento entre empresas, minimizando conteúdo sensível.
- **Alertar a equipe técnica Imagine** de acordo com a gravidade e regras de monitoramento, evitando alertas duplicados; permitir diagnóstico e tratamento das causas com trilha de **auditoria técnica** de alterações, responsáveis, justificativas e resultados.
- **Preservar imediatamente os bloqueios e restrições já aplicados** a anexos, trechos e resumos com risco não resolvido (IN-070–078). Falha persistente não transforma origem desconhecida em verificada, **não autoriza desbloqueio** e não exclui automaticamente arquivos, resumos, versões ou históricos legítimos (IN-042/043/075–079).
- Permitir **reprocessamento controlado da tarefa após correção comprovada da causa ou intervenção/autorização da equipe técnica habilitada**, sempre com novas verificações de permissões, estado de segurança das fontes e idempotência. Autorização técnica é para reiniciar o processamento, **não para contornar detecções, ignorar falhas sem solução ou liberar conteúdos sem verificação**; preservar fila e prioridades da IN-079/081.
- **Não consumir créditos comerciais de IA do cliente** por diagnósticos, retentativas e reconstruções obrigatórias de segurança (IN-079/080). Aplicar controles internos de infraestrutura, concorrência e custo, sem provocar cobrança automática.
- **Pendências:** classificação das causas e severidades, prazos de triagem, critérios exatos de correção e reentrada em fila, limites de novas tentativas após reprocessamento e notificações técnicas. Apenas documentação e planejamento; nenhuma programação autorizada.


## IN-083 — Classificação automática das falhas persistentes com supervisão humana (APROVADA em 01/11/2026)

- Nas tarefas de reconstrução obrigatória de proveniência por IA encaminhadas à fila técnica da **IN-082**, classificar automaticamente as falhas **por causa e gravidade/prioridade**, com base em sinais verificáveis, registrando evidências e justificativas sem inventar diagnóstico ou atribuição de causa.
- Utilizar categorias iniciais: **(1) falha técnica recuperável** (ex.: serviço temporariamente indisponível); **(2) limitação de recursos/capacidade**; **(3) evidência insuficiente para identificar a fonte**; **(4) risco elevado ou incidente crítico de segurança**; **(5) falha de integridade/inconsistência da origem, do derivado ou de suas versões**. Admitir estado **inconclusivo/não classificado** quando os dados não sustentarem uma categoria.
- Quando uma **falha estritamente técnica tiver causa efetivamente corrigida e validações suficientes**, permitir **reprocessamento controlado**, respeitando filas, prioridades e limites técnicos das IN-079/081/082, com novas checagens de autenticação/autorização, integridade, versão/estado das fontes e idempotência. Classificação como recuperável, isoladamente, **não comprova que a causa foi corrigida**.
- **Exigir supervisão e avaliação de integrante autorizado da equipe técnica Imagine** para incidentes ou riscos críticos, falhas de integridade e casos inconclusivos, antes de definir o tratamento, reprocessamento excepcional ou encerramento técnico. Não confundir avaliação humana da tarefa com autorização para ignorar ameaças ou desbloquear material restrito, o que continua regido pelas IN-070–077.
- **Preservar bloqueios e restrições de segurança**, históricos, versões e metadados legítimos; não promover origens desconhecidas a verificadas, não excluir registros automaticamente e não permitir que uma tarefa com falha impeça outras operações independentes.
- Registrar **auditoria técnica protegida** com classe, prioridade, sinais disponíveis, status, tentativas, responsável pela avaliação, autorizações, reprocessamentos e resultado; minimizar dados sensíveis e alertar a equipe Imagine proporcionalmente à gravidade, evitando notificações redundantes.
- Essas tarefas são controles obrigatórios de segurança e **não consomem créditos comerciais de IA dos clientes** (IN-079/080). Aplicar limites internos de custo e capacidade.
- **Pendências:** regras objetivas de severidade e prioridade, catálogo versionado de códigos de falha, limiares de encaminhamento, critérios de correção verificada e prazos de triagem (próxima proposta IN-084).
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-084 — Severidade e escalonamento automáticos baseados em risco (APROVADA em 01/11/2026)

- Classificar as falhas persistentes da reconstrução obrigatória de proveniência por IA (IN-081–083) em **quatro níveis: CRÍTICA, ALTA, MÉDIA e BAIXA**. Considerar **evidências verificáveis, risco real ou potencial de exposição, impacto sobre integridade e proteção dos conteúdos, urgência e persistência**, com justificativa compreensível e histórico da classificação. Não inventar causa, risco ou impacto.
- **CRÍTICA:** sinais concretos de risco ativo ou grave de exposição de conteúdos protegidos, falha grave dos controles de segurança ou incidente de elevada urgência; **alerta técnico imediato**, triagem prioritária e escalonamento à equipe Imagine autorizada, sem aguardar a rotina comum.
- **ALTA:** falha significativa de integridade, proteção importante comprometida ou incidente relevante ainda não resolvido que demande atenção prioritária; encaminhamento e escalonamento técnico proporcionais.
- **MÉDIA:** falha persistente que exija intervenção, sem sinais que justifiquem urgência maior; atendimento na fila técnica conforme prioridade.
- **BAIXA:** reconstrução legada ou outra tarefa sem impacto imediato ou risco identificado, sujeita a processamento/tratamento gradual segundo a capacidade técnica.
- **Inconclusão não equivale automaticamente a severidade crítica**: classificar provisoriamente conforme sinais disponíveis, solicitar revisão humana quando necessário e reavaliar à luz de novas evidências. A classificação baixa/média ou inconclusiva **jamais remove bloqueios de segurança** já aplicados nem transforma proveniência desconhecida em fonte verificada.
- Permitir à equipe técnica Imagine autorizada **revisar a severidade mediante justificativa e trilha de auditoria**, sem alterar ou contornar as condições independentes de bloqueio/desbloqueio definidas nas IN-070–077. Registrar alterações de prioridade, evidências, notificações, responsáveis e transições, com proteção e minimização de dados sensíveis.
- **Alertar imediatamente incidentes críticos** via canais internos técnicos da Imagine; escalonar ocorrências altas, médias e baixas de forma proporcional à gravidade e persistência, com deduplicação e sem envio indiscriminado de informações ao cliente. Preservar isolamento por organização, históricos legítimos, recursos técnicos e idempotência (IN-079–083).
- Estes controles permanecem **sem consumo de créditos comerciais de IA dos clientes** (IN-079/080). **Pendentes:** limiares quantitativos, prazos de primeira resposta e resolução, gatilhos de reescalonamento e rota de plantão (proposta IN-085).
- **Somente planejamento/documentação; nenhuma programação autorizada.**


## IN-085 — Metas internas de primeira resposta e escalonamento por severidade (APROVADA em 01/11/2026)

- Definir **metas internas de primeira resposta humana (triagem inicial)** para falhas persistentes de reconstrução de proveniência por IA (IN-081–084), distintas de resolução do incidente e de garantias contratuais:
  - **CRÍTICA:** meta de **até 30 minutos**, **somente quando estiver estruturada e efetivamente ativa uma cobertura técnica 24 horas por dia, 7 dias por semana**. Até lá, registrar a meta planejada, lacuna de cobertura e necessidade de implantação, **sem afirmar um plantão existente ou resposta garantida em 30 minutos**.
  - **ALTA:** meta de **até 4 horas úteis**.
  - **MÉDIA:** meta de **até 1 dia útil**.
  - **BAIXA:** meta de **até 3 dias úteis**.
- Contabilizar a primeira resposta humana a partir de um marco auditável de registro e classificação acionável, sujeito a especificação dos detalhes de contagem, calendário de horas úteis, mudanças de severidade, feriados, contatos e responsabilidades de plantão.
- Utilizar **avisos internos e escalonamento técnico proporcionais** à gravidade, prioridade e extrapolação de metas, com deduplicação e histórico protegido do momento da detecção, classificação, acionamentos, primeira resposta e eventuais atrasos. Manter alertas **imediatos** para incidentes críticos conforme IN-084, independentemente da cobertura humana já ter sido implantada.
- **Bloqueios, contenção e regras de não liberação de conteúdo inseguro permanecem automáticos** e independentes da resposta humana (IN-070–084). A ausência de plantão ou atraso de triagem não autoriza desbloqueios, exclusões ou alteração de proveniência não comprovada.
- Metas são **objetivos operacionais internos**, **não prazos de resolução nem SLA contratual** e não autorizam comunicar disponibilidade 24/7 sem equipe, escala e procedimento efetivamente definidos.
- **Não consumir créditos comerciais de IA dos clientes** por tratamento obrigatório de segurança (IN-079/080). Preservar auditoria, autorizações, isolamento empresarial e históricos.
- **Pendências:** dimensionamento de plantão 24/7, calendário oficial de expediente técnico, papéis de acionamento e substituição, mecanismo de medição e escalonamento, revisões e validação operacional. **Somente planejamento/documentação, nenhuma programação autorizada.**


## IN-086 — Implantação futura de plantão técnico 24/7 (APROVADA em 01/11/2026)

- **Não implantar nem considerar operacional neste momento plantão humano 24 horas por dia, 7 dias por semana**, seja por sobreaviso, seja por turnos dedicados. Essa cobertura fica para uma etapa futura de planejamento e validação operacional.
- A **meta interna de primeira resposta humana de até 30 minutos para incidentes CRÍTICOS** da IN-085 permanece **condicionada** à efetiva implantação de cobertura técnica 24/7; **não está vigente**, não pode ser comunicada como garantia de atendimento, SLA contratual ou compromisso humano existente até a estrutura estar pronta e formalmente validada.
- **Preservar monitoramento contínuo, geração de alertas técnicos, classificação automática de severidade e contenção/bloqueios automáticos de riscos**, independentemente do expediente ou da disponibilidade de uma pessoa, conforme IN-045–048 e IN-070–085. A ausência de plantão não pode remover restrições, desbloquear conteúdos não verificados, permitir exclusões ou comprometer históricos e permissões.
- **Incidentes CRÍTICOS continuam gerando alertas técnicos imediatos** pelos canais já aprovados (painel Imagine e e-mail técnico em situações críticas/persistentes, IN-046/047). O recebimento de alerta não deve ser confundido com confirmação de resposta humana fora do horário coberto.
- **Metas ALTA/MÉDIA/BAIXA de 4 horas úteis, 1 dia útil e 3 dias úteis** permanecem como metas internas condicionadas ao calendário técnico de expediente a detalhar; não prometer primeira resposta humana de 30 minutos fora dele.
- A **ativação futura** do plantão 24/7 exigirá aprovação e documentação de equipe responsável e substituta, escala e disponibilidade real, canais de acionamento, escalonamento, treinamentos, dimensionamento, conformidade trabalhista aplicável e testes periódicos que comprovem a operação e a medição de resposta. Somente após essa validação a meta humana de 30 minutos poderá ser considerada em vigor.
- Tratamento provisório de incidentes críticos detectados fora do expediente, critérios de comunicação e escalonamento até o próximo atendimento humano serão detalhados na proposta IN-087, respeitando os canais existentes e a ausência de garantia de resposta imediata.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-087 — Incidentes críticos fora do expediente: contenção imediata, triagem humana no próximo expediente (APROVADA em 01/11/2026)

- Para incidentes **CRÍTICOS** relacionados à segurança e reconstrução obrigatória de proveniência por IA, **detectados de madrugada, fins de semana, feriados ou fora do expediente técnico**, executar **imediatamente contenções e bloqueios automáticos apropriados**, inclusive restrição de anexos/trechos suspeitos, preservação da integridade e impedimento de liberação de conteúdos não verificados. As proteções de IN-070–086 não podem depender da presença de funcionários.
- **Gerar alertas técnicos imediatos** pelos canais previamente aprovados (painel administrativo Imagine e **e-mail técnico para incidentes críticos**, conforme IN-046/047), com deduplicação, dados mínimos necessários e preservação de confidencialidade; **alerta enviado não significa atendimento humano iniciado**.
- **Registrar automaticamente a ocorrência como prioridade crítica na fila técnica**, com estado, indícios, horário de detecção, providências de contenção, alertas emitidos e trilha de **auditoria técnica**; preservar sua prioridade para **triagem humana no primeiro período de atendimento técnico disponível**, conforme calendário a ser definido. Tratar outras ocorrências segundo as respectivas regras de severidade (IN-084/085).
- **Não oferecer nem anunciar resposta humana imediata fora do expediente**, pois o plantão humano 24/7 foi adiado na IN-086. A meta de **primeira resposta em 30 minutos** para críticos na IN-085 permanece condicionada à implantação e validação futura da cobertura e **não está vigente**.
- **Preservar bloqueios, permissões atuais, isolamento entre empresas, arquivos e históricos legítimos**, sem exclusão automática, restauração de anexos legitimamente excluídos nem desbloqueios por ausência de técnicos, falhas de IA ou expiração de alertas. A intervenção posterior seguirá as validações e aprovações de segurança aplicáveis (IN-042/043/070–083).
- A triagem do próximo expediente deve identificar o incidente como crítico e registrar início e responsáveis, mas **não constitui prazo contratual de resolução**, nem pressupõe que o horário/calendário técnico já esteja formalmente definido.
- **Pendências:** calendário oficial do expediente técnico (dias, horas, fuso e feriados), início de contagem de metas em horas úteis, regra de primeiro atendimento após feriados e comunicação interna de atrasos, a tratar na IN-088. Sem débito de créditos comerciais de IA por controles obrigatórios (IN-079/080).
- **Apenas planejamento/documentação; nenhuma programação autorizada.**


## IN-088 — Calendário oficial de atendimento técnico e contagem de metas internas (APROVADA em 01/11/2026)

- Definir o **expediente técnico padrão da Imagine de segunda a sexta-feira, das 09h00 às 18h00, horário de Brasília**, representado tecnicamente pelo fuso **`America/Sao_Paulo`** e considerando o horário legal vigente, sem hardcode de UTC fixo em toda a arquitetura.
- **Excluir da contagem** sábados, domingos, **feriados nacionais** e **feriados locais legalmente aplicáveis à sede operacional**. O calendário deverá permitir manutenção e versionamento dos feriados aplicáveis, considerando mudanças legais e localização operacional efetiva, sem presumir feriados de cidades onde a empresa não opera.
- Para as metas **ALTA (até 4 horas úteis), MÉDIA (até 1 dia útil) e BAIXA (até 3 dias úteis)** previstas na IN-085, **contabilizar apenas tempo dentro dos intervalos úteis**. Eventos registrados fora do horário entram na fila imediatamente, mas a contagem operacional de horas úteis e a triagem humana começam ou retomam no **próximo expediente disponível**. A conversão formal de dias úteis em marcos/horários e políticas de pausas deverá ser documentada sem reduzir as proteções de segurança.
- Eventos **CRÍTICOS** detectados fora do expediente seguem a IN-087: **contenção/bloqueios automáticos, monitoramento e alertas técnicos imediatos 24/7**, registro crítico prioritário e triagem humana priorizada na primeira abertura de expediente; não inventar presença de pessoal fora do período coberto.
- **A meta de 30 minutos de resposta humana para CRÍTICOS não está vigente**, pois o plantão 24/7 foi postergado pela IN-086. Não divulgar como SLA, cobertura ativa ou garantia contratual. As demais metas são **objetivos internos de primeira resposta**, não de resolução e não garantem prestação contratual ou disponibilidade contínua de equipe.
- Registrar em **auditoria técnica protegida** instantes de detecção, classificação, alertas, início e fim da contagem do calendário, feriados considerados, primeira resposta e escalonamentos, resguardando permissões, isolamento empresarial e históricos. As regras automáticas de bloqueio/desbloqueio e de não consumo de créditos comerciais de IA permanecem inalteradas (IN-070–087).
- **Pendências:** definição/cadastro de feriados da sede operacional, critérios exatos de medição de dias úteis e horários de resposta, pausas de expediente e dimensionamento/capacidade da equipe, além de revisões do calendário caso a sede ou a escala mude.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-089 — Administração centralizada e auditável do calendário técnico (APROVADA em 01/11/2026)

- Manter **um calendário oficial centralizado de atendimento técnico Imagine**, correspondente ao expediente aprovado na IN-088 (segunda a sexta-feira, das 09h às 18h, fuso `America/Sao_Paulo`), com **administração exclusiva por profissionais da Imagine com permissão específica**; usuários/administradores das empresas clientes não podem alterar feriados, horários ou períodos úteis de atendimento técnico.
- **Cadastrar e validar os feriados nacionais e os feriados locais legalmente aplicáveis à sede operacional**, identificando jurisdição, data, fundamento/origem e vigência. Evitar presumir que feriados de todas as cidades dos clientes sejam automaticamente aplicáveis à equipe Imagine.
- Permitir **exceções operacionais justificadas**, como fechamento extraordinário ou ajuste pontual do expediente, com responsável, motivo, data/hora de início/fim, abrangência e aprovação conforme perfil, sempre com efeito temporal explícito; não tratar exceções como suspensão dos controles de segurança.
- **Versionar o calendário** e manter **auditoria técnica protegida** de inclusão, alteração, cancelamento e aprovação, com autor, instantes, justificativa, versão anterior e nova e efeitos operacionais. Limitar acesso aos dados administrativos.
- **Preservar histórico de métricas e prazos de atendimento já apurados**, inclusive a versão de calendário utilizada no cálculo; alterações posteriores não podem reescrever silenciosamente indicadores históricos. Eventuais correções históricas justificadas, quando necessárias, precisam ser explícitas, auditáveis e preservar valor/cálculo anterior, sem fraude de cumprimento de metas.
- A administração do calendário afeta **somente janelas de primeira resposta humana e cálculo de horas/dias úteis** (IN-085–088). **Monitoramento, alertas de incidentes críticos, fila, bloqueios e contenção automática de segurança continuam 24/7**, mesmo nos feriados e exceções (IN-070–087). Não divulgar plantão humano 24/7 como vigente; a meta crítica de 30 minutos continua condicionada (IN-086).
- **Pendências:** perfis exatos de administração e revisão, política de retroatividade/correções, validação do calendário inaugural e regras de recálculo e mudanças de severidade (proposta IN-090).
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## IN-090 — Recálculo controlado de metas após reclassificação de severidade (APROVADA em 01/11/2026)

- Quando novas evidências justificarem **mudança da severidade de um incidente**, atualizar **imediatamente sua prioridade operacional**, mantendo todas as classificações anteriores e registrando razões, sinais, instante e responsável ou processo automatizado, com revisão humana quando exigida pelas IN-083/084.
- **Recalcular a nova meta de primeira resposta a partir da reclassificação**, usando a severidade nova e a versão aplicável do calendário técnico centralizado IN-088/089. **Não sobrescrever o prazo original, nem apagar atrasos já ocorridos, alertas gerados, metas não cumpridas e métricas anteriores**. Alterações retroativas exigem correção separada, justificada, versionada e auditável; não reescrever silenciosamente o histórico.
- **Elevação de severidade** aciona imediatamente os alertas e medidas de contenção proporcionais, inclusive alerta crítico quando necessário, sem aguardar equipe humana. **Redução da severidade** exige justificação verificável e não remove automaticamente alertas, registros, bloqueios e restrições de segurança legítimos.
- A meta humana de **30 minutos para incidentes críticos permanece não vigente** até efetiva implantação/validação de plantão 24/7 (IN-085/086). Aplicar IN-087 para incidentes críticos fora do expediente, com proteção e alerta automáticos e triagem humana prioritária no próximo expediente; jamais registrar promessa de resposta humana 24/7 inexistente.
- Se a **primeira resposta humana já tiver sido efetivamente realizada**, preservar sua medição concluída; eventual nova meta decorrente de aumento de severidade refere-se a **nova avaliação/triagem** e deverá ser registrada separadamente, sem reiniciar ou substituir o indicador original.
- Registrar trilha de auditoria técnica protegida para severidades sucessivas, fontes e evidências, versão de calendário, horários de início e vencimento de metas, respostas, escalonamentos e justificativas, com proteção de dados, segregação entre empresas e autorização de acesso. O recálculo **não autoriza desbloqueio nem exclusão automática de dados** (IN-070–089), nem consumo de créditos comerciais de IA para segurança obrigatória (IN-079/080).
- **Encerramento funcional deste bloco:** decisões IN-039 a IN-090 consolidadas para revisão de coerência; detalhes de cálculos de calendário e regras técnicas de execução serão tratados em especificação técnica e testes, sem abrir novas perguntas funcionais sobre este mesmo tópico salvo descoberta de conflito relevante.
- **Somente documentação de planejamento; nenhuma programação autorizada.**


## Fechamento da revisão consolidada — IN-039 a IN-090 (01/11/2026)

**Escopo revisado:** 52 decisões, de IN-039 a IN-090, conferidas quanto a presença/numeração e coerência funcional. **Resultado:** sem lacunas de numeração, duplicidades ou conflito funcional impeditivo identificado nesta revisão; as regras mais recentes esclarecem hipóteses e pendências de decisões anteriores. **Bloco de decisões funcionais encerrado**, sem significar que a especificação técnica, fornecedores, dimensionamento e testes já tenham sido concluídos.

| Faixa | Assunto consolidado | Garantia essencial |
| --- | --- | --- |
| IN-039–041 | Acesso a anexos e logs | Usuário autenticado e autorizado, isolamento por organização; sem links públicos compartilháveis; somente registros técnicos de segurança para downloads/visualizações. |
| IN-042–054 | Exclusão, backups, replicação e contingência | Exclusão funcional controlada, backups de 30 dias com tombstones, replicação assíncrona com RPO-alvo de 15 min, contingência autenticada e somente leitura ativada por técnicos; retorno ao principal com validação e aprovação. |
| IN-055–069 | Uploads e antimalware | Uploads recuperáveis por até 6h, reserva atômica de capacidade, até cinco uploads simultâneos por empresa, fila FIFO, checagem de tamanho/formato e antimalware obrigatório antes da confirmação; falha sistêmica de antivírus impede novos uploads. |
| IN-070–083 | Bloqueio por ameaça e rastreabilidade de IA | Bloqueio proporcional de fontes/derivados/caches/trechos, proveniência comprovada e tratamento seguro de legados; reconstrução por IA somente para segurança obrigatória, sem créditos comerciais, com filas, recuperação controlada e supervisão técnica quando necessário. |
| IN-084–090 | Severidade, resposta humana e calendário | Severidade baseada em risco, expediente segunda a sexta 09h–18h Brasília exceto feriados aplicáveis; incidentes críticos com proteção/alerta automático 24/7, triagem humana no próximo expediente; metas auditáveis e recalculadas sem apagar atraso ou histórico. |

**Esclarecimentos de precedência e condições:**

1. **IN-080 complementa e restringe IN-079:** solicitações autorizadas podem sinalizar necessidades de **segurança**, mas **não autorizam reconstruções opcionais por IA**, cobrança por créditos ou enriquecimento comercial dos resumos. Custos de controles obrigatórios são internos à Imagine.
2. **IN-086 e IN-087 condicionam IN-085:** não existe plantão humano 24/7 aprovado como operacional no momento; a meta crítica humana de 30 minutos **não está vigente** até implantação e validação do plantão. O monitoramento, a contenção e os alertas críticos automáticos continuam previstos 24/7.
3. **IN-088 e IN-089 completam o calendário pendente nas IN-085/087:** segunda a sexta, 09h–18h, `America/Sao_Paulo`, com feriados aplicáveis e exceções versionadas; é necessário cadastrar/validar datas reais antes da operação.
4. **IN-090 complementa IN-089:** mudar a severidade altera a prioridade imediatamente e produz uma nova meta a partir da reclassificação, sem reescrever prazos, atrasos ou medição de primeira resposta já concluída.
5. **IN-042/043 e IN-070–078 coexistem:** restrição de segurança não significa exclusão; arquivo legitimamente excluído não pode ser restaurado por backup ou desbloqueio. A recuperação de conteúdo derivado somente ocorre com fonte e autorização verificadas.

**Pendências remanescentes de especificação/implantação (não são novas decisões funcionais deste bloco):** dimensionamento, fornecedores e custos de infraestrutura/antimalware; testes e métricas concretas de RPO/retentativas/filas; definição de papéis técnicos e manutenção do calendário de feriados; orçamento interno de segurança, critérios de evidência e observabilidade; implantação futura de plantão se for escolhida; políticas de retenção técnica dos logs compatíveis com requisitos legais. **IN-038 (preços de pacotes extras de armazenamento) permanece decisão comercial separada e não aprovada**, fora deste fechamento.

**Encerramento:** prosseguir para outro assunto do planejamento do CRM Imagine. **Não iniciar programação** até aprovação do planejamento global; alterações documentais permanecem nas branches de planejamento, não em `main`.


## IN-091 — Jornada operacional integrada Portal Imagine → CRM Básico Cortesia (APROVADA em 01/11/2026)

**Objetivo:** consolidar em um fluxo único e seguro a contratação elegível de um site Imagine, a ativação do benefício CRM Básico Cortesia, a publicação do formulário e a entrada de leads, sem transformar propostas de API em código ou serviços já operacionais.

1. **Elegibilidade e pagamento:** após a confirmação financeira **conciliada** do primeiro pagamento elegível de contrato de Onepage ou futura Landing Page, Portal Imagine solicita de maneira **idempotente** a ativação do benefício CRM Básico Cortesia, **sem segunda mensalidade do Básico**. O benefício pertence à organização vinculada ao contrato do site; o Portal continua sendo o sistema de contratação/cobrança do site. Não ativar por evento de checkout não confirmado.
2. **Vínculo de identidade e organização:** localizar e reutilizar a organização CRM correta quando já existir; **não duplicar empresa, benefício, assinatura básica ou usuário** em casos de tentativas repetidas, mais de um site ou cliente pré-existente. Se o cliente possuir acesso a múltiplas organizações ou houver ambiguidade de titularidade, exigir **escolha/autorização verificada**; não assumir a empresa apenas pelo e-mail do comprador ou por parâmetro do navegador.
3. **Convite e acesso:** criar ou vincular o **único Administrador da Empresa autenticado do CRM Básico** (IN-020/021), mediante **convite seguro**, validação de identidade e **MFA obrigatório**, sem compartilhamento de senhas, sessão, tokens ou credenciais de Portal e CRM; respeitar as permissões e quotas efetivas do plano.
4. **Produção padronizada:** agentes de IA e equipe Imagine produzem o site utilizando componentes e formulários oficiais, associados por identificadores públicos e metadados configurados pelo backend; **jamais incluir chaves privadas, segredos, tokens administrativos ou IDs internos que determinem a organização destinatária no HTML/JavaScript público**. A IA não recebe acesso genérico aos dados privados do CRM.
5. **Publicação e ativação do formulário:** habilitar captura somente após validação da publicação/versão, domínio e associação segura entre pedido, site, formulário e organização no backend. Se mudança de domínio, configuração ou vínculo comprometer a associação, revalidar antes de aceitar novos envios; site/formulário não publicados ou suspensos não devem registrar falsos aceites.
6. **Captação durável e idempotente:** validar campos, consentimentos/avisos de privacidade quando aplicáveis, antispam, antifraude, tenant de destino e origem de cada envio; **persistir duravelmente o recebimento antes de confirmar sucesso ao visitante**. Entregar por canal autenticado servidor-servidor, com identificador único e **idempotência do evento de submissão** (não confundir com mesclagem automática de leads, proibida pela IN-028). Preservar rastreabilidade da origem e isolamento multiempresa.
7. **Recuperação de falhas:** em indisponibilidade do CRM, **reter em fila persistente os envios já aceitos** e aplicar retentativas controladas, reconciliar estados e alertar operadores em falhas persistentes; **não perder silenciosamente submissões aceitas, não confirmar recebimento quando a persistência inicial falhar e não criar registros duplicados por reentrega**. Regras de expiração/retenção, limites e contingência serão detalhadas tecnicamente.
8. **Separação de responsabilidades e visibilidade:** Portal administra contrato/pagamentos, produção, publicação e status técnico mínimo da ativação e captura; CRM administra organização, usuário/MFA, recursos de plano e os **leads, contatos, anexos e históricos privados**. Portal e agentes de produção não ganham acesso amplo a dados CRM do cliente; status técnico pode indicar pendente/entregue/erro sem expor payload desnecessário. Upgrade pago é feito e cobrado **somente no CRM**, mantendo mensalidade do site no Portal (IN-002).
9. **Direitos já aprovados do Básico:** um administrador autenticado e um profissional agendável, agenda apenas manual (não autoagendamento público), até dez serviços ativos, leads sem teto comercial rígido, armazenamento e demais quotas conforme IN-001–037/039–090; nenhum novo benefício, plano, cobrança, automação adicional ou alteração de limites é introduzido por esta integração.
10. **Ciclo financeiro posterior:** inadimplência, suspensão/cancelamento do contrato do site, retorno ao Básico após término de plano CRM pago, múltiplos sites e preservação/portabilidade dos dados serão consolidados em bloco financeiro posterior para aprovação; não inferir eliminação automática de leads ou encerramento da assinatura CRM paga.

**Pendências técnicas sem novas decisões funcionais deste bloco:** API e contratos de eventos, mecanismos de autenticação serviço a serviço, outbox/filas e idempotência, estados e alertas, homologação de formulários, LGPD, observabilidade e testes de aceitação. **Esta aprovação é exclusivamente documental. Não iniciar desenvolvimento, deploy nem alteração de produção antes do planejamento global aprovado.**


## IN-092 — Ciclo de vida financeiro integrado Portal Imagine × CRM Básico Cortesia (APROVADA em 01/11/2026)

**Escopo e prevalência:** aprovada como **bloco funcional consolidado** de elegibilidade, assinaturas, inadimplência, retorno de plano, múltiplos sites, reativação e retenção; detalha o ciclo de vida pendente das IN-002/003/091, sem mudar valores, condições, duração ou faturamento das assinaturas do site e dos planos CRM pagos.

1. **Cobranças e autonomia dos produtos:** o **Portal Imagine cobra e gerencia exclusivamente a contratação do site** Onepage (e eventual futura Landing Page, quando aprovada). O benefício **CRM Básico Cortesia não gera mensalidade separada** enquanto elegível. O **upgrade/assinatura paga CRM** é contratado com aceite expresso e cobrado no **próprio CRM**, com fatura distinta da assinatura do site; não realizar upgrade ou cobrança automática no Portal. Cancelamento ou inadimplência do site **não encerra nem suspende por si só uma assinatura CRM paga vigente**.
2. **Benefício durante tolerância contratual do site:** manter a elegibilidade à cortesia durante a **tolerância já aplicável ao contrato do site**, enquanto o **Portal** considerar o vínculo elegível, com comunicação de inadimplência pelos canais contratualmente previstos. **Não inventar um novo prazo** de tolerância para o site a partir da política geral de CRM pago. A elegibilidade deverá derivar de estados financeiros conciliados e de eventos autorizados, não de suposições em resposta a pagamentos pendentes.
3. **Suspensão do site / perda temporária de elegibilidade:** quando o Portal indicar, segundo contrato, que a assinatura do site **deixou de ser elegível por inadimplência/suspensão**, suspender o **entitlement Básico**, com registro de motivo, instante, avisos e possibilidade de regularização. **Preservar dados, arquivos, vínculos e históricos**; nenhuma exclusão automática por suspensão. Se houver **assinatura CRM paga vigente**, preservar o plano pago com sua cobrança e direitos autônomos. Suspensão temporária **não equivale a encerramento definitivo da cortesia** e **não inicia automaticamente o prazo de 120 dias**, salvo mudança formal para estado terminal.
4. **Cancelamento definitivo de site e término do benefício:** ao cancelar definitivamente o último contrato de site elegível, encerrar o direito à cortesia na data efetiva contratual, **sem cancelar o CRM pago** e sem exclusão imediata de dados. Registrar eventos de cancelamento e data efetiva e permitir verificações/contestação conforme política de atendimento.
5. **Retorno após plano pago:** quando uma assinatura CRM paga terminar, se ao menos um site da organização continuar elegível, **retornar ao CRM Básico no fim do período pago conforme ciclo contratual aprovado**, sem segunda mensalidade. Conservar leads, contatos, arquivos, agendas e históricos; **não apagar recursos excedentes** apenas pelo downgrade. Aplicar os **entitlements e quotas do Básico** a novos acessos e operações: manter dados válidos existentes em estado seguro, restringir criação/uso de funcionalidades não incluídas, novos usuários além de 1, uploads além da capacidade e automações/serviços incompatíveis, com avisos de gestão/upgrade sem cobrança automática. Preservar capacidade de consulta/exportação legalmente autorizada e auditoria.
6. **Vários sites para mesma empresa:** manter **um único tenant/organização e um único benefício efetivo de Básico**, com registro de todos os contratos de site elegíveis como origens do direito, de modo idempotente. O encerramento de um site **não cancela a cortesia** enquanto outro contrato elegível permanecer; não multiplicar usuário, quota, agenda, franquia ou mensalidade de CRM por quantidade de sites.
7. **Recontratação e reativação:** após nova contratação ou pagamento/regularização confirmados e conciliação do estado de elegibilidade, revalidar identidade/titularidade, tenant correto, permissões e vínculos, então **reativar somente o benefício devido**, com trilha de auditoria e sem criar duplicidades ou reconstituir dados legitimamente eliminados. Restaurar acesso aos dados **ainda retidos** de acordo com as regras do plano vigente e de segurança.
8. **Retenção após término definitivo do Básico:** quando houver **encerramento definitivo do último benefício Básico** e **não existir assinatura CRM paga ativa**, iniciar **retenção de 120 dias contados da data efetiva do encerramento definitivo** (não da primeira parcela atrasada, simples aviso ou suspensão temporária). Nesse intervalo, **não restabelecer o uso operacional completo do plano**; permitir somente **consulta e exportação autorizadas de dados retidos** por identidades verificadas (MFA, autorização por empresa, controles técnicos e auditoria), para recuperação/portabilidade, salvo restrições legais/de segurança específicas. Depois de 120 dias, aplicar política controlada de eliminação/anonimização, com observância de obrigações legais, bases de retenção, litígios/preservação obrigatória, LGPD e dos tombstones/backups aplicáveis; sem apagamento silencioso instantâneo no cancelamento e sem promessa de recuperação após descarte legítimo.
9. **Respeito a políticas distintas:** políticas de teste gratuito, conta CRM com plano pago cancelado (retenção própria já aprovada), exclusão sob pedido de titular, contratos do site e regras legais **não são anuladas nem misturadas** pelo prazo específico de término definitivo do benefício Básico; se houver direitos simultâneos, determinar o acesso pela assinatura efetiva e elegibilidade real, sem perda de dados em transições.
10. **Conciliação, logs e privacidade:** sincronizar estados entre Portal e CRM por eventos autenticados, idempotentes e auditáveis, com reconciliação de atrasos/duplicidades; Portal tem acesso aos **metadados estritamente necessários de elegibilidade e operação**, nunca acesso irrestrito à base de leads, contatos e arquivos privados CRM. Avisos de suspensão/cancelamento/retorno e datas efetivas deverão ser exibidos de modo claro, sem prometer prazos não aprovados.

**Pendências de implementação e conformidade:** enumerar estados e transições de contratos/entitlements, verificações antifraude e reconciliação financeira, momento dos avisos e abertura da janela de consulta/exportação, calendário de cobrança/tolerância contratual real do Portal, políticas de proteção de dados de titulares, e regras de expurgo com salvaguardas legais. **Não iniciar programação ou alterações em produção antes da aprovação integral do planejamento.**


## IN-093 — Formulários públicos e captação de leads dos sites Imagine (APROVADA em 01/11/2026)

**Escopo:** bloco funcional consolidado de formulários públicos dos sites Onepage e futuras Landing Pages da Imagine, integrados ao CRM Imagine. Complementa IN-001/007/023–028/091/092, sem ativar funcionalidades comerciais ainda não aprovadas nem iniciar programação.

1. **Campos mínimos do formulário padrão:** solicitar **nome** e **ao menos um meio de contato válido**, entre **e-mail ou telefone/WhatsApp**, como obrigatórios. E-mail, telefone/WhatsApp e mensagem compõem o formulário padrão; o outro meio de contato e a mensagem são opcionais se já houver contato válido. Validar preenchimento e formato de acordo com a finalidade, com mensagens claras de erro, sem obrigar ambos os contatos.
2. **Personalização controlada:** permitir **campos adicionais configuráveis conforme a necessidade de cada site**, com tipagem, obrigatoriedade, rótulos e validação no servidor, sob limites técnicos e critérios de minimização de dados. Não permitir captura irrestrita de informações sensíveis ou campos de coleta excessiva sem revisão de conformidade, finalidade e segurança; configuração e versão do formulário devem ser rastreáveis e autorizadas.
3. **Privacidade e consentimento:** apresentar **aviso de privacidade claro** com identificação adequada do responsável pelo tratamento e finalidades; informar tratamento de dados conforme a LGPD e **solicitar/registrar consentimento quando a base legal e a finalidade o exigirem**. Consentimento de marketing quando utilizado deve ser específico, separado, opcional e não pré-marcado; não torná-lo condição indevida para enviar mensagem de contato. Guardar evidências pertinentes de aceite/preferência, versão do aviso, momento e origem, minimizando dados pessoais.
4. **Proteção de formulários públicos:** validação de payload, tamanho, formato e campos no backend, defesa contra spam/bots, rate limiting proporcional, honeypot/desafio adaptativo quando adequado, antifraude, controles contra injeção e abuso, mensagens seguras sem exposição de segredos. Rejeições por segurança/validação devem ser distinguidas de envios aceitos, com tratamento responsável de falsos positivos.
5. **Origem verificável e destino seguro:** associar a submissão ao **site, formulário público, versão de configuração, data/hora e campanha/UTM quando aplicável e lícito**; o servidor identifica a organização CRM destinatária pelo cadastro interno validado do formulário. **Nunca confiar em `crm_org_id`, tenant, plano ou identificador de destino fornecidos livremente pelo navegador** e não permitir origem falsificada determinar organização de gravação.
6. **Aceite durável:** após validação e proteção antiabuso, **persistir duravelmente a submissão/registro de entrada antes de comunicar sucesso ao visitante**. Se a persistência inicial falhar, exibir erro fiel e possibilitar novo envio sem afirmar recebimento; se o CRM estiver indisponível **após aceite durável**, manter evento protegido em fila/outbox persistente até a entrega ou resolução controlada, com estado técnico rastreável e alertas quando falhar persistentemente.
7. **Entrega idempotente e recuperação:** integrar Portal/serviço de ingestão e CRM por comunicação servidor-servidor autenticada e autorizada; **cada submissão terá identificador único e chave idempotente** para que retentativas, duplicação de eventos e reconciliação não gerem o mesmo evento/lead duas vezes. Empregar retentativas controladas, tratamento de inconsistências e observabilidade, sem descarte silencioso de leads previamente aceitos, respeitando tenant e direitos efetivos.
8. **Duplicidade de negócio separada de idempotência:** dois envios legítimos da mesma pessoa **não podem ser mesclados automaticamente** pelo e-mail/telefone. No CRM Básico, possíveis duplicidades podem ser sinalizadas e a mesclagem só ocorre mediante a operação manual autorizada da IN-028, preservando histórico e origem. Idempotência diz respeito à reentrega do **mesmo evento técnico**, não à união automática de contatos ou oportunidades diferentes.
9. **Produção inteligente e publicação:** agentes de IA e equipe de produção utilizarão **componentes oficiais padronizados** e configurações autorizadas, com revisão/validação de campos, segurança, privacidade, vínculo e versão antes da publicação. Não inserir em HTML ou JavaScript **senhas, tokens administrativos, credenciais privadas do CRM, chaves de integração servidor-servidor nem acesso interno aos leads**. Formulário público pode usar identificador público próprio, não autorização de acesso aos dados privados.
10. **Limite funcional do CRM Básico Cortesia:** submissões aprovadas podem **criar leads e registrar sua origem**, mas **não criam agendamentos, reservas ou autoagendamento público** no Básico (IN-007). Formulário e integração não concedem acesso do Portal a históricos privados, não ampliam usuários, automações, franquias ou outras funcionalidades do CRM, nem criam cobranças separadas.

**Pendências para especificação e homologação:** catálogo de campos adicionais permitidos, esquema/versões de formulário e privacidade, bases legais por finalidade, consentimentos e trilhas de prova, limites de tamanho/antispam e tratamentos de falso positivo, políticas de fila/retenção/retentativa, disponibilidade, scripts de validação, testes de integração e isolamento multiempresa, acessibilidade, mensagens de erro e monitoramento. **Somente documentação de planejamento; nenhuma programação, publicação ou alteração de produção autorizada.**


## IN-094 — Publicação, manutenção e monitoramento dos formulários Imagine (APROVADA em 01/11/2026)

**Escopo:** bloco consolidado para o ciclo de publicação e pós-publicação dos formulários públicos ligados a sites Onepage e futuras Landing Pages da Imagine. Complementa as IN-091–093 e não altera contratos, permissões ou regras comerciais aprovadas.

1. **Pré-publicação e ativação:** validar versão publicada do site, domínio autorizado, vínculo `site/formulario/organizacao` mantido no backend, campos e validações, controles antispam, privacidade/consentimentos aplicáveis, configuração de segurança, idempotência e um teste confiável de aceite durável/entrega ao CRM antes de habilitar a captação. Não habilitar formulário com validação/homologação necessária não concluída; não publicar tokens ou segredos privados.
2. **Versionamento de mudanças:** alterações de domínio, campos, regras de obrigatoriedade, integração, aviso de privacidade, configuração e componentes oficiais de formulário geram versão rastreável com responsável/processo, data/hora, motivo e compatibilidade; revalidar o tenant destinatário e as permissões. Preservar a versão aplicável às submissões históricas e impedir redirecionamento retroativo indevido.
3. **Atualizações produzidas por IA:** a produção inteligente usa os componentes aprovados, mas atualizações automatizadas do site **precisam passar por validações e homologação de formulário, destino e privacidade** antes da publicação. O agente não ganha credenciais privadas, poder de aprovar exceções de segurança nem acesso automático aos leads do cliente. Se uma alteração não passar nas verificações, não ativar sua versão insegura.
4. **Entrega resiliente:** aceitar novos envios **somente após validação e persistência durável**; submissões já aceitas ficam em fila/outbox persistente diante de indisponibilidade de integração/CRM, com retentativas limitadas, idempotência, reconciliação, alertas proporcionais e preservação do histórico. **Nunca informar sucesso a um visitante quando falhou o recebimento inicial**. Não converter falha técnica em descarte silencioso, mesclagem automática de leads ou troca do tenant de destino.
5. **Monitoramento Imagine:** disponibilizar painel técnico interno com status de publicação e atividade do formulário, versão/ambiente/domínio, últimas submissões e entregas confirmadas (metadados mínimos), quantidade/idade de pendências, falhas e ações de suporte/recuperação, além de alertas conforme criticidade. Proteger acesso por perfis e auditoria; **o Portal e operadores de site não recebem acesso irrestrito a payloads, históricos, arquivos e dados pessoais dos leads privados do CRM**.
6. **Recuperação e rollback controlado:** se mudança de versão, domínio ou configuração gerar problema, suspender a ativação insegura e **permitir reversão controlada para a última versão/configuração tecnicamente validada somente após verificar compatibilidade, integridade, segurança, origem e tenant**. O rollback é da configuração/artefatos publicáveis, não de dados de produção: **não excluir, reescrever, duplicar ou redirecionar leads e envios já aceitos**, nem restaurar credenciais comprometidas ou configurações sabidamente inseguras.
7. **Desativação:** impedir novos envios quando formulário/site estiver desativado ou contrato sem direito à captação, com resposta fiel ao visitante e canais alternativos autorizados quando existentes. **Preservar submissões aceitas, leads e históricos legítimos** conforme elegibilidade, ciclo financeiro e retenção (IN-092), inclusive processamento e tratamento autorizados de eventos recebidos antes da desativação, sem acesso indevido após a perda de direitos.
8. **Segregação e limites:** Portal responde por produção/publicação e monitoramento técnico mínimo; CRM controla organização, permissões, lead e histórico. Nenhuma alteração do formulário habilita **autoagendamento no Básico Cortesia**, mesclagem automática, credenciais privadas públicas ou quotas e cobranças não aprovadas.

**Pendências de especificação/homologação:** checklist formal de publicação e testes sintéticos, políticas de versão e compatibilidade, indicadores/limiares e privacidade do painel, operação segura de rollback, estados e alertas da fila, retenção de eventos, tratamento de domínio e relatórios de incidentes. **Decisão documental apenas: nenhuma programação, publicação ou alteração de produção autorizada antes do planejamento global aprovado.**


## IN-095 — Produção inteligente dos sites Imagine, revisão e aprovação do cliente (APROVADA em 01/11/2026)

**Escopo:** decisão funcional consolidada da produção dos sites Onepage e eventuais Landing Pages elegíveis, sob equipe Imagine e agentes de IA, desde o recebimento dos materiais até a entrega, solicitação de ajustes e aprovação explícita do cliente; complementa IN-091–094, sem estabelecer novas condições comerciais.

1. **Recebimento e organização de materiais:** reunir materiais fornecidos pelo cliente por canais aprovados (textos, logotipo, imagens, dados de contato, informações empresariais e orientações), associando-os ao pedido, versão e permissões correspondentes. Validar integridade, propriedade/uso autorizado e consistência; sinalizar informações ausentes, ambiguidades, problemas de qualidade ou conteúdo potencialmente inadequado, sem fabricar dados do cliente.
2. **Revisão inteligente assistida:** agentes de IA e fluxos automatizados podem organizar, analisar e sugerir melhorias nos materiais e na estrutura, usando fontes e informações aprovadas. Toda sugestão com impacto em fatos sobre o negócio, promessas comerciais, termos legais, preços, dados pessoais ou identidade de marca requer comprovação e aprovação do responsável quando aplicável; não publicar afirmações inventadas.
3. **Desenvolvimento padronizado:** produzir e ajustar sites a partir de templates, componentes e diretrizes oficiais da Imagine, com registro de versão, escopo e origem das alterações. Os agentes operam **com credenciais e permissões restritas à finalidade**, sem acessar indiscriminadamente leads ou dados privados do CRM, e sem expor segredos em páginas públicas.
4. **Validação de qualidade antes de disponibilizar:** executar checklist apropriado de visualização responsiva, navegação, links, conteúdo, SEO básico, desempenho, acessibilidade pertinente, integridade técnica, segurança, privacidade/consentimentos quando aplicáveis e formulários. Formulários destinados ao CRM exigem **homologação de vínculo, destino, versão, antispam, persistência e entrega idempotente** conforme IN-091–094; reprovações relevantes impedem a disponibilização insegura.
5. **Publicação conforme autonomia autorizada:** publicar versão para revisão ou em ambiente de destino somente quando o **escopo de autonomia efetivamente concedido** ao agente/processo e as validações de homologação autorizarem tal operação; respeitar separação entre prévia, publicação e ativação de recursos produtivos. Se for exigido ato humano ou aceitação do cliente para determinada transição, não inferir autorização tácita; manter estados reais de produção/publicação e canais de recuperação.
6. **Entrega da URL e acompanhamento:** registrar qual versão do site está acessível, em qual ambiente/domínio, com data, estado e vínculo ao pedido, então **enviar a URL para avaliação/aprovação do cliente** pelos canais contratualmente autorizados. Portal Imagine apresenta o andamento real (materiais pendentes, produção, validação, versão para revisão, ajustes, aguardando aprovação, aprovado/publicado, impedimentos), sem simular etapas concluídas.
7. **Solicitações de ajustes e versões:** receber e registrar alterações solicitadas pelo cliente, com descrição, autorização, versão anterior, versão resultante e histórico de comunicação. Submeter alterações novamente às validações pertinentes, especialmente integração de formulário/destino/privacidade, evitando retroceder silenciosamente conteúdo aprovado ou perder histórico. **Quantidade, prazo e custo de revisões não são criados nem alterados pela IN-095**; aplicar os termos comerciais quando formalmente definidos.
8. **Aprovação explícita:** aprovação final demanda **ação expressa do cliente autorizado** sobre uma **versão identificável** e registro do aceite, momento e contexto, com auditoria. **Silêncio, falta de retorno ou simples visualização da URL não constituem aprovação automática**. Aprovação de uma versão não autoriza modificar substancialmente outras versões sem processo apropriado.
9. **Supervisão e exceções humanas:** riscos de segurança, falhas de validação, conteúdo sem base comprovada, conflito de instruções, questões de direitos de uso, mudanças de escopo ou ações acima da autonomia exigem encaminhamento e revisão por responsável humano habilitado. IA não pode contornar requisitos técnicos nem se autoautorizar a dispensar um controle.
10. **Privacidade, responsabilidades e integração:** equipe/Portal Imagine mantém tarefas de produção e suporte técnico conforme permissão; CRM controla sua organização, usuários, leads, anexos, consentimentos relacionados e permissões. A produção automatizada não recebe acesso generalizado aos dados de clientes CRM, não altera planos/cobranças e não ativa funcionalidades do Básico não aprovadas (IN-007/092–094).

**Pendências para a especificação posterior:** graus de autonomia e aprovações por tipo de operação; política comercial de quantidade/prazos/valores de revisões; checklist de QA e acessibilidade; canais e evidências de aceite do cliente; ambientes de homologação e produção, rollback seguro, métricas de agentes e tratamento de falhas; controle de propriedade/licenças dos materiais; fluxos de suporte. **Esta decisão autoriza exclusivamente documentação: nenhuma programação, deployment ou alteração de produção até a aprovação do planejamento global.**


## IN-096 — Hospedagem e operação automatizada dos sites Imagine com agentes de IA (APROVADA — REVISADA em 01/11/2026)

**Escopo da decisão:** adotar uma arquitetura de publicação simples, rápida, integrável por API e de baixo custo para os **novos sites Onepage/Landing Pages da Imagine**, com gestão operacional conduzida por agentes de IA e automações. Esta é a **versão revisada e aprovada** da proposta de IN-096; a proposta anterior de hospedagem/atendimento predominantemente manual e a alternativa WHM/cPanel **não são a arquitetura escolhida para esses sites**. Nenhum serviço foi contratado ou implantado por esta aprovação.

1. **Hospedagem inicial:** escolher **Cloudflare Workers Static Assets** como plataforma inicial de disponibilização dos sites Imagine **predominantemente estáticos**, distribuindo arquivos HTML, CSS, JS e mídias otimizadas. Evitar dependência de PHP/WordPress e banco de dados dedicado por site quando a finalidade puder ser suprida pela arquitetura estática; componentes dinâmicos necessários permanecem em serviços de backend/API separados, sujeitos ao planejamento e homologação.
2. **Versionamento e entrega contínua:** usar **GitHub** para armazenar o código/artefatos relevantes, manter histórico de versões e permitir publicação automatizada por integrações de API/CI quando autorizada. Definir posteriormente a organização de repositórios por site/cliente e a gestão de artefatos, segredos e branches. Nunca publicar segredo ou credencial no site estático.
3. **Orquestração centralizada de agentes:** planejar **n8n + integrações via APIs autenticadas** como camada de orquestração dos agentes de IA da Imagine, a partir dos fluxos do Portal. Agentes cobrirão **publicação, operações rotineiras de hospedagem, monitoramento de disponibilidade/SSL/formulários, manutenção corretiva/preventiva, backups e testes de recuperação, atendimento e diagnóstico de suporte, versionamento e comunicação de status**. Não pressupor que todos os agentes sejam simultâneos ou ilimitados; controlar concorrência, custos, qualidade, revisão e permissões.
4. **Limites de responsabilidade técnica:** **Cloudflare presta a hospedagem e infraestrutura de distribuição**; agentes da Imagine **administram operações, configurações e processos autorizados**. Portal permanece responsável por contratos, pedidos, produção, status e suporte dos sites. CRM mantém usuários, leads, contatos e históricos privados conforme IN-091–094. Dados de formulários passam pelo serviço de ingestão/backend e por integração segura com o CRM; não hospedar dados privados dos leads em repositórios públicos ou em arquivos estáticos do site.
5. **Autonomia com controles:** permitir execução automática apenas de ações **pré-autorizadas, verificáveis e reversíveis quando aplicável**, com contas técnicas restritas por finalidade, segregação entre clientes, armazenamento seguro/rotação de segredos, limites de gastos, registros de ações, monitoramento e validação antes de alterações em produção. **Exceções sensíveis** — alterações de propriedade/transferência de domínio, mudanças DNS de alto impacto, operações destrutivas, despesas/contratações, incidentes graves ou decisões fora da autonomia — exigem autorização humana adequada. Esta regra **não substitui a aprovação explícita do cliente sobre a versão do site exigida na IN-095**, nem revoga controles já aprovados.
6. **Qualidade, segurança e recuperação:** publicação e manutenção por IA exigem validações automáticas/homologação aplicáveis, verificação de segurança/privacidade e testes dos formulários. Fazer **backups automatizados do código/artefatos/configurações necessários**, manter capacidade de restauração testada e distinguir claramente **rollback de versão estática** de **recuperação de dados e eventos do backend/CRM**, que não podem ser apagados ou redirecionados. Restrições, periodicidade e destino dos backups serão dimensionados tecnicamente, sem presumir que o controle de versão substitui todas as cópias de recuperação.
7. **Domínios, DNS e SSL:** planejar ativação/renovação controladas, validação de domínio e configurações de DNS e certificados, com automação segura quando suportada e comprovação de titularidade/autorização. Mudanças que atinjam e-mail ou serviços externos do cliente exigem verificação e autorização apropriadas; não assumir que todo domínio externo pode ser migrado automaticamente.
8. **Evolução de escala:** manter **Cloudflare Workers for Platforms** como **possibilidade futura**, condicionada a análise de volume de sites, arquitetura de isolamento, limites técnicos, riscos, custo total e testes. **Não contratar nem migrar automaticamente** para esse produto por esta decisão. Preços e franquias da Cloudflare não são aprovados como orçamentos internos fixos neste registro e exigem confirmação comercial no momento oportuno.
9. **Isolamento de escopo:** **desconsiderar WHM/cPanel exclusivamente para a nova hospedagem dos sites Imagine** dentro desta solução. **Não alterar, migrar, desligar ou reprojetar por consequência o Portal Imagine, CRM Imagine SaaS, Talci.Work ou outras aplicações/servidores existentes**; cada sistema mantém sua arquitetura e decisões próprias. A orquestração n8n poderá aproveitar decisões anteriores de infraestrutura apenas após avaliação específica de capacidade e isolamento.
10. **Planejamento, não implantação:** detalhar posteriormente arquitetura operacional, contas/permissões, custo por site, domínio, recursos Cloudflare/API, provisionamento, backups, métricas de confiabilidade, suporte e rotas de intervenção humana; nenhuma programação, contratação, migração, alteração DNS ou publicação é autorizada antes da aprovação global do planejamento.

**Integração com IN-095:** agentes podem desenvolver, testar e publicar versões no limite da autonomia concedida, mas **o aceite do cliente sobre o site é sempre explícito**; silêncio não significa aprovação, independentemente da automação.

**Pendências para especificação:** topologia multiempresa e escala (inclusive Worker por site versus arquitetura compartilhada), política de repositórios e credenciais, design dos workflows n8n, testes automatizados de qualidade, modelo de suporte por IA, backups/recuperação, domínios e custos completos. **Somente documentação; nenhuma programação, contratação, publicação ou migração autorizada.**


## IN-097 — Central de Sites Imagine multiempresa operada por agentes de IA (APROVADA em 01/11/2026)

**Objetivo:** permitir que a Imagine administre dezenas, centenas ou milhares de sites com procedimentos automatizados e custo operacional mensurável, sem criar uma operação manual por site. Complementa IN-091–096 e não concede acesso novo aos dados privados do CRM.

1. **Inventário central por site:** registrar cada site com identificador imutável e vínculo inequívoco à organização/cliente, pedido/contrato e ambiente, incluindo status de provisionamento, produção/publicação, domínio, certificado, formulários, versão ativa, artefatos, backup e incidentes. Vários sites podem pertencer à mesma empresa, sem duplicar benefício CRM Básico (IN-092).
2. **Provisionamento e publicação automatizados:** agentes de IA orquestrados por n8n e integrações de API executam configuração e publicação a partir de templates/componentes aprovados e controle de versões no GitHub, com validações técnicas antes de ativar o site ou os formulários. Respeitar autonomia concedida, homologação e aceite explícito do cliente quando exigido (IN-095/096).
3. **Isolamento multiempresa:** garantir segregação de configuração, ambiente lógico, publicação, domínios, repositórios/artefatos, chaves, credenciais e dados de cada site/organização; nenhuma ação do agente ou falha em um cliente pode atravessar permissões e afetar outro. Serviços compartilhados deverão impor controle de escopo por site/tenant no backend e no fluxo de deploy, com autenticação e auditoria.
4. **Domínios e certificados:** acompanhar titularidade/autorização de DNS, vínculos de hostname e ambiente, emissão/renovação de SSL/TLS e mudanças de domínio. Ações de maior impacto (transferência de domínio, DNS que afetem e-mail/serviços, mudança de titularidade) exigem avaliação/autorização adequada. A ativação deve validar o domínio e o destino correto antes de rotear tráfego ou formulários.
5. **Versões, backups e recuperação:** manter histórico de artefatos/configurações de cada site, automação de backups e testes apropriados de restauração. Permitir rollback/restauração **controlados** apenas após verificar versão, integridade, credenciais, domínio, configurações e tenant corretos, sem excluir ou reescrever silenciosamente envios aceitos, leads, registros e históricos no backend/CRM (IN-093/094).
6. **Monitoramento e central operacional:** painel Imagine com saúde/alcance dos sites, estado de publicação, disponibilidade, SSL, domínio, formulário, última entrega comprovada, backlog de filas, falhas e prioridades. Alertas e procedimentos de triagem proporcionais; expor somente metadados técnicos necessários a profissionais autorizados, nunca conteúdo privado de leads do cliente por mera relação com o site.
7. **Automações em lote, quotas e contenção:** aplicar limites de concorrência por empresa/site/serviço, escalonamento, detecção de anomalias, rate limits e orçamento, com interrupção segura de implantações em massa e limitação do raio de impacto de falhas. Erro em um site não deve provocar publicação, rollback, alteração de DNS ou bloqueio indiscriminado em outros sites.
8. **Medição técnica de custos:** atribuir, quando tecnicamente possível, a cada site e por período os recursos usados (execução/deploy, requisições e processamento faturável, armazenamento, backups, domínios, integração de formulários, uso de APIs e agentes). Registrar custos compartilhados e critérios de rateio de modo transparente, com alertas de consumo; não criar cobranças para clientes, preços ou quotas comerciais não aprovados. Tratar valores/limites publicados por provedores como variáveis, a confirmar antes da implantação.
9. **Topologia ainda aberta:** não aprovar antecipadamente um Worker separado por site nem um Worker/infraestrutura compartilhada para todos. Selecionar a estratégia após **dimensionamento e testes verificáveis** de limites de plataforma, gerenciamento de domínios/rotas, isolamento, desempenho, custo por site, observabilidade, recuperação e escalabilidade. Cloudflare Workers Static Assets segue arquitetura inicial aprovada (IN-096); Workers for Platforms permanece **opção futura**, não contratada ou ativada.
10. **Escopo e responsabilidade:** Central de Sites no domínio de gestão/Portal Imagine para operação e suporte. CRM continua proprietário e controlador de seus módulos, dados de leads/contatos e permissões; canais de ingestão/integração não são substituídos por armazenamento estático. Esta aprovação não afeta arquitetura de outros produtos Imagine ou Talci.Work.

**Pendências técnicas para especificação e homologação:** modelo de identidade/tenants, políticas de contas API e segredos, estrutura GitHub por site, catálogo de versões, APIs de domínios/DNS/SSL, backups e testes, métricas de saúde e custos, limites operacionais, plano de migração de arquitetura em escala e autorização de operações sensíveis. **Somente documentação; nenhuma programação, compra, ativação de serviço, migração ou mudança em produção autorizada.**


## IN-098 — Autonomia, segurança e supervisão dos agentes de IA da Central de Sites Imagine (APROVADA em 01/11/2026)

**Escopo:** decisão funcional consolidada para os agentes da Central de Sites Imagine previstos nas IN-095–097, cobrindo produção, publicação, suporte, manutenção, domínio, SSL, backups, monitoramento e recuperação. Não concede acesso implícito aos dados do CRM nem autorização para contratar serviços ou iniciar programação.

1. **Três níveis de autonomia por tipo de ação, não privilégios genéricos por agente:**
   - **Nível 1 — Operação automática:** tarefas rotineiras, previamente autorizadas, de baixo risco e não destrutivas: monitoramento, testes técnicos e de segurança, verificação de certificados, auditoria de links, criação e conferência de backups, diagnóstico e respostas informativas de suporte dentro de fontes aprovadas. Não conceder acesso privado ou alterar estados sensíveis somente por serem atividades chamadas de rotina.
   - **Nível 2 — Execução automatizada condicionada a validação:** correção de erros simples, atualização de componentes, publicação de versões autorizadas, rollback tecnicamente seguro e ajustes operacionais permitidos **apenas** quando o escopo do agente/cliente tiver sido previamente autorizado, os testes e políticas de segurança forem aprovados e houver mecanismos de revalidação, limitação de impacto e rastreamento. Se aplicável, **aprovação explícita do cliente quanto à versão do site continua obrigatória** (IN-095); não converter publicação automatizada em autorização tácita.
   - **Nível 3 — Aprovação humana obrigatória:** transferência de titularidade/domínio, alterações DNS de impacto elevado (inclusive serviços de e-mail), exclusões definitivas, acesso excepcional a informações privadas, novas contratações, elevações de custo/despesas, operações irreversíveis ou de alto risco e qualquer ação fora do escopo/autonomia previamente concedidos. Um agente não pode pedir e aprovar sua própria exceção nem dispensar o ato humano.
2. **Central de Aprovações Imagine:** centralizar solicitações que exigem decisão humana, incluindo **autor/agente solicitante, organização e site, ação exata e parâmetros, justificativa, evidências/testes, avaliação de risco, potencial de custo, destinatário autorizador, validade temporal, estado pendente/aprovada/recusada/expirada, execução e resultado**. Verificar permissões do aprovador e revalidar o contexto antes da execução. Recusa, silêncio e expiração **não autorizam** executar a operação. Preservar trilha de auditoria imutável para operadores comuns e evitar dados sensíveis excessivos nos logs.
3. **Menor privilégio e isolamento:** agentes terão identidades técnicas próprias e credenciais com escopo restrito por função, ambiente, organização e site; segregação de produção e testes, proteção/rotação de segredos e credenciais, e sem acesso cruzado por decisão do modelo. O Portal/serviço de produção não ganha acesso indiscriminado aos leads, contatos e anexos privados do CRM (IN-091–097). Controles de API e autorização do backend prevalecem sobre o texto ou a intenção do agente.
4. **Supervisão independente:** verificações por agente supervisor e validadores técnicos independentes conferirão qualidade, segurança, conformidade com a versão autorizada, efeitos reais, logs, desvios e custo. Esses componentes podem interromper, negar ou escalar ações e detectar falhas; **não substituem as autorizações humanas obrigatórias** do Nível 3 ou o aceite de cliente da IN-095.
5. **Limites de recursos e custo:** estabelecer orçamento técnico interno, limites de tokens/chamadas/execuções, taxa, tempo, concorrência e gastos máximos por agente, site e operação, com alertas graduais e bloqueio preventivo de ações não essenciais antes do excedente. Não criar cobrança ou consumo de créditos comerciais do CRM para tarefas internas de manutenção dos sites sem política expressamente aprovada.
6. **Operações em lote e isolamento de falhas:** limites de concorrência e capacidade, validação por site, execução gradual quando adequado, parada segura e contenção de falhas para impedir publicação, alteração de DNS, exclusão, rollback ou reconfiguração indevidos em outros clientes. Ação em lote não dispensa aprovações exigidas para cada escopo afetado.
7. **Suspensão e resposta a risco:** permitir **pausar ou suspender imediatamente agentes, tarefas, tokens e credenciais** diante de comportamentos anômalos, falhas repetidas, suspeita de comprometimento, consumo excessivo ou instruções conflitantes, preservando evidências, filas seguras, histórico de ações e operação dos sites/serviços independentes. A retomada requer verificação e autorização apropriadas conforme gravidade; não liberar conteúdo inseguro nem executar ações bloqueadas durante a suspensão.
8. **Auditoria e controle:** registrar responsáveis, identidade técnica, autorização, versão de site, antes/depois quando apropriado, testes, decisão e resultado, custos e alertas, minimizando dados pessoais. Não revelar segredos em rastros, prompts ou mensagens de suporte, nem permitir que instruções de páginas ou clientes substituam políticas do sistema (defesa contra injeção de prompts).
9. **Respeito às decisões anteriores:** manter regras de publicação/integração, idempotência e privacidade dos formulários (IN-091–094), necessidade de aceite explícito do cliente (IN-095), arquitetura Cloudflare/GitHub/n8n em planejamento (IN-096) e operação multiempresa segura (IN-097). **Não há autorização para programação, migração, contratação ou implantação.**

**Pendências técnicas a detalhar sem fragmentar decisões:** catálogo de operações por nível, perfis técnicos e escopos de tokens, arquitetura da Central de Aprovações, dupla checagem e segregação de papéis, thresholds e orçamento de custos, limites de lote, critérios de parada/retomada, retenção de logs e testes de segurança. **Somente registro de planejamento; nenhuma programação autorizada.**


## IN-099 — Central de Suporte Inteligente Imagine com atendimento por agentes de IA (APROVADA em 01/11/2026)

**Escopo:** bloco funcional consolidado do suporte aos sites Imagine pelo Portal, com agentes de IA como atendimento prioritário, procedimentos rastreáveis e escalonamento humano de exceções, sem substituir direitos contratuais, controles de segurança ou autorizações definidas nas IN-095–098.

1. **Canal e identificação:** disponibilizar experiência de atendimento aos clientes no Portal Imagine. Associar toda solicitação a **cliente/empresa, site, contrato, identificador de protocolo, solicitante autorizado, origem e horário**, preservando histórico de mensagens, decisões, providências, responsáveis e comunicações. Validar identidade e escopo da empresa; impedir acesso cruzado a tickets, domínios ou dados de outros clientes.
2. **Estados transparentes:** estabelecer estados **recebida, em análise, aguardando informações, em execução, aguardando aprovação, resolvida e encerrada**, com histórico de transições, causas de espera, próximos passos e responsável lógico/operacional. Evitar encerramentos silenciosos e promessas de atendimento não confirmadas; regras detalhadas de prazos serão definidas conforme contrato, sem criar SLA implícito.
3. **Classificação e diagnóstico automatizados:** agentes podem classificar finalidade, impacto e urgência e consultar **somente dados técnicos permitidos** da Central de Sites (disponibilidade, DNS/SSL, formulário, publicação, versões, logs técnicos minimizados e incidentes). Identificar possíveis causas com evidências; indicar incerteza quando a origem do problema não puder ser comprovada. Não inventar diagnósticos, resultados de testes nem ações supostamente executadas.
4. **Correção dentro da autonomia:** executar automaticamente apenas ações **pré-autorizadas pela IN-098**, com credenciais mínimas e verificações de configuração, segurança, isolamento, versão, reversibilidade e testes. As alterações de conteúdo, escopo ou domínio devem respeitar a IN-095 e a IN-096, o aceite do cliente quando aplicável e a política de aprovação humana de ações sensíveis. Correções não podem comprometer formulários ou redirecionar leads, apagar histórico ou alterar dados de outros clientes.
5. **Validação de contrato e cobrança:** antes de atender mudança solicitada, verificar direitos, elegibilidade e escopo contratual vigente no Portal; distinguir **manutenção corretiva coberta** de **alterações comerciais ou solicitações fora do escopo**. Nunca cobrar, contratar extra, ampliar franquias ou executar serviço oneroso sem aprovação financeira/comercial explícita conforme política aplicável. **Não presumir** número de revisões gratuitas, tempos de resposta, SLA ou suporte humano 24/7.
6. **Escalonamento e exceções:** casos de alto risco, irreversíveis, sem evidência suficiente, falhas persistentes, decisões contratuais, acesso excepcional a dados pessoais ou operações além da autonomia são **encaminhados ao responsável humano autorizado**, pela Central de Aprovações/fluxo de suporte. A supervisão por outro agente não substitui aprovação humana obrigatória nem permite contornar bloqueios.
7. **Verificação da solução:** testar de forma segura os efeitos da correção, registrar **evidências reais** (resultado técnico, data/hora, versão e verificações pertinentes), comunicar o resultado ao cliente e só indicar resolução quando validada. Permitir **reabertura de solicitação** quando a falha persistir ou voltar, preservando o protocolo e o histórico ou um vínculo entre protocolos, sem apagar investigações anteriores.
8. **Privacidade e segregação:** os agentes do Portal acessam somente informações necessárias para prestar suporte técnico. **Não conceder acesso indiscriminado a leads, contatos, anexos e dados privados do CRM** pelo fato de um site estar ligado ao CRM Básico, nem guardar payloads privados em logs, mensagens de IA ou repositórios públicos.
9. **Auditoria, qualidade e recursos:** registrar todas as ações, aprovações, tarefas de agentes, falhas, suspensões, custos internos e resultados de forma protegida; aplicar limites de consumo por agente, empresa, ticket e operação, políticas de uso justo e proteção contra abuso e automações em loop. O agente supervisor identifica inconformidades e pode suspender fluxos indevidos, respeitando a IN-098.
10. **Disponibilidade operacional:** priorizar atendimento automatizado conforme disponibilidade dos serviços técnicos, sem afirmar existência de plantão humano. O suporte deve informar interrupções reais e encaminhar solicitações que exijam intervenção humana aos canais e horários operacionalmente definidos, respeitando condições contratuais.

**Pendências técnicas/comerciais para especificação:** catálogo de solicitações, níveis de impacto, regras de prioridade, notificações e canais, estados e reaberturas, evidências de validação, políticas de conhecimento e atualização, limites de custos, regras contratuais de suporte e parametrização de papéis e aprovações. **Somente documentação de planejamento; nenhuma programação, contratação ou alteração em produção autorizada.**


## IN-100 — Gestão inteligente de domínios, DNS e SSL dos sites Imagine (APROVADA em 01/11/2026)

**Escopo:** bloco funcional consolidado de domínios próprios e novos registros, configurações DNS, certificados HTTPS/SSL, vencimentos, mudanças e suporte na Central de Sites Imagine, com operação por agentes IA sob a política IN-098. **A escolha de Cloudflare Workers Static Assets para hospedagem não transfere automaticamente a titularidade dos domínios nem inclui seu registro/renovação no preço do site** (IN-096/097).

1. **Domínios do cliente e novos registros:** permitir vincular domínio já registrado de titular legítimo, sob autorização verificável e sem exigir transferência ao nome da Imagine. Solicitações de novo registro ou renovação devem observar titularidade, disponibilidade, política contratual e **aprovação expressa de despesas/contratações** pelos autorizadores adequados; nenhuma compra, renovação paga ou mudança de titularidade por decisão unilateral do agente.
2. **Cadastro e titularidade:** inventariar domínio, titular e papel administrativo autorizados, registrador/provedor DNS, datas de expiração verificáveis, sites/ambientes vinculados, histórico e restrições de operação; segredos e credenciais permanecem protegidos em cofre técnico e nunca são exibidos em logs, páginas públicas ou prompts sem necessidade e controle. **Não tratar mera posse de um endereço digitado como prova de titularidade.**
3. **Preservação do DNS e do e-mail existente:** antes de editar qualquer zona, verificar registros relevantes já existentes, inclusive **MX, SPF, DKIM, DMARC**, além de serviços de e-mail, verificações de propriedade, integrações e subdomínios. **Não remover, sobrescrever ou alterar registros críticos sem validação de impacto e aprovação humana quando exigida**; executar mudanças autorizadas com snapshot/registro prévio, testes e plano de recuperação, considerando limitações do registrador e propagações DNS.
4. **Autonomia dos agentes:** agentes podem inspecionar resoluções, estado de certificado, domínio, DNS e vencimentos, alertar sobre risco e sugerir correções (**nível 1**); aplicar configurações técnicas rotineiras **previamente autorizadas** e com validação, limites e rollback quando seguro (**nível 2**). **Transferência de titularidade/domínio, mudança DNS de alto impacto, acesso excepcional e despesas novas** ficam no **nível 3, com aprovação humana obrigatória pela Central de Aprovações Imagine**, sem aprovação tácita. Segurança e titularidade prevalecem sobre a conveniência da automação.
5. **SSL/TLS:** planejar emissão e renovação gerenciadas de certificados onde tecnicamente suportadas e sob domínio corretamente validado; monitorar expiração, HTTPS, cadeia de certificados e erros, com alertas e remediação segura. **Não prometer emissão/renovação automática** quando dependente de autorização DNS, conta externa ou limitações do provedor.
6. **Vencimentos e renovação:** monitorar datas disponíveis e emitir avisos internos/ao cliente nos canais contratuais com antecedência parametrizável; diferenciar aviso, solicitação de renovação, pagamento autorizado e confirmação junto ao registrador. Não declarar domínio renovado sem evidência do registrador, nem efetuar pagamentos sem autorização adequada.
7. **Mudanças e migrações:** revalidar titularidade, domínios/rotas, ambientes, certificados, redirects, formulários e associação servidor-servidor com a organização CRM antes e após uma troca de domínio. Preservar **origens e leads históricos** e impedir que mudanças reencaminhem formulários para empresa diferente (IN-091–094). Prever fallback/rollback apenas após verificar compatibilidade e segurança, sem reintroduzir registros comprometidos.
8. **Diagnóstico e recuperação:** detectar falhas de DNS/SSL, divergências de configuração, expiração iminente e indisponibilidade, registrar incidente/protocolo e permitir correções autorizadas via agentes, com testes técnicos posteriores; casos de alto risco, causa desconhecida ou risco a serviços externos exigem supervisão humana conforme IN-098/099.
9. **Cancelamento e portabilidade:** preservar direitos legítimos do titular sobre o domínio, comprovantes e informações necessárias para transferência/continuidade conforme contrato; encerramento de hospedagem ou do benefício CRM não autoriza apropriação, bloqueio de transferência ou alteração arbitrária de titularidade. Separar políticas de domínio, hospedagem do site e retenção do CRM (IN-092).
10. **Auditoria, custo e isolamento:** registrar cada leitura/mudança relevante, identidade do agente/técnico, solicitação, aprovação, registros anteriores e novos quando adequados, testes, status, resultado e custo autorizado; limitar escopo por cliente/site/domínio e impedir acesso cruzado. As taxas de registro, renovação, transferência e serviços de domínio **não estão incluídas automaticamente na hospedagem** e exigem condições comerciais específicas.

**Pendências técnicas/comerciais posteriores:** catálogo de registradores/provedores de DNS compatíveis e respectivos limites de API, prazos/canais de avisos, evidências de titularidade, modelo de aprovação de novas despesas, checklist de DNS de e-mail, limites de execução, testes de HTTPS e rollback, tratamento de domínios externos e contratos. **Somente planejamento: nenhuma programação, compra, registro, renovação, alteração DNS, migração ou modificação de produção autorizada.**
