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
