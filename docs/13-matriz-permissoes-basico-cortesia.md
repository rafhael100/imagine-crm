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
| Quotas de armazenamento e retenção de leads/contatos | **PENDENTES** | Definir sem contrariar a proteção dos leads já aceitos e os direitos de privacidade |

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
5. **Armazenamento e retenção de dados, tamanho de anexos, política de backup e descarte** continuam **PENDENTES**, com respeito à LGPD, à integridade das informações já aceitas e sem criar indiretamente um teto comercial de registros incompatível com IN-024.
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

**Próxima etapa:** definir capacidade e política de armazenamento do CRM Básico, distinguindo registros estruturados ilimitados comercialmente (IN-024) de anexos/arquivos e arquivos temporários; depois retenção e recuperação.

### Situação consolidada dos complementos às IN-023 a IN-028

- **IN-024 APROVADA:** sem teto comercial rígido de quantidade de leads e contatos, tanto os recebidos via site como os cadastrados manualmente, sujeitos a uso justo e controles técnicos. **Pendentes:** parâmetros operacionais de proteção antiabuso, capacidade física de armazenamento e retenção, que não podem impor uma quota comercial disfarçada.
- **IN-025 APROVADA:** importação básica via CSV e XLSX, com mapeamento, prévia, validação e alerta de possíveis duplicidades, sem mesclagem automática. **Pendentes:** limites técnicos por arquivo/lote, regras de correspondência de campos, tratamento de linhas inválidas, política de duplicidades (ignorar/atualizar/decidir individualmente), concorrência e relatório final.
- **IN-026 APROVADA:** exportação de leads/contatos em CSV e XLSX, **todos os registros autorizados ou somente os filtrados**, protegida e auditada, com limites técnicos a detalhar. **Pendente:** campos exportáveis sensíveis, duração/link de download e arquivos temporários, tarefas assíncronas, proteção em caso de volumes grandes e política de requisições LGPD (sem paywall para direitos legais).
- **IN-027 APROVADA — exclusão definitiva controlada:** somente registros elegíveis com confirmação expressa; registros com vínculos ou obrigações de preservação exigem fluxo protegido, respeitando LGPD e auditoria. **PENDENTES:** critérios técnicos de elegibilidade, tratamento dos vínculos, política de retenção/anônimização e recuperação. **Mesclagem manual aprovada posteriormente na IN-028**, sem mesclagem automática.
- **Retenção e restauração de arquivados**: duração do histórico, interfaces, auditoria e regras de dados pessoais.
- Detalhes opcionais de campos e filtros, cadastro manual de interações e registro de situação comercial, sem conceder funcionalidades premium por inferência.

**Próxima decisão a submeter ao responsável:** política de **armazenamento dos dados do CRM Básico** (especialmente distinção entre registros estruturados, anexos/arquivos e uploads temporários), depois retenção/recuperação e detalhes técnicos. **Nenhuma programação autorizada até aprovação do planejamento.**

## Módulos seguintes ainda pendentes

3. Oportunidades e funis.
4. Agenda e serviços — direitos operacionais dentro das quotas já aprovadas.
5. Modelos de automações comerciais — biblioteca e vagas ativas correntes.
6. Relatórios, importação/exportação e LGPD.
7. Plano, contratação e cobrança.

**Referências:** [CRM Básico e matriz geral](08-plano-basico-cortesia.md), [Agenda/usuário e IN-021](11-agenda-manual-basico-cortesia.md), [Regra global IN-010](12-limites-e-upgrade-contextual.md), [Registro de decisões](decisoes.md).
