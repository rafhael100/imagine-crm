# 02 — Planos, trial e assinaturas

## Regra transversal IN-010 — Avisar limites e orientar upgrade (APROVADA)

**Aprovação em 2026-11-01:** se o cliente tentar realizar ação **não disponível no plano efetivo** ou **superar a quota aplicável**, o CRM deverá informar de maneira compreensível o motivo e oferecer uma oportunidade pertinente de upgrade ou contratação de capacidade adicional, **se existir e resolver o limite**. Oferecer também alternativa legítima para liberar capacidade no próprio plano (ex.: inativar serviço ou pausar automação), quando disponível. A regra vale para todos os planos e módulos CRM, inclusive o Básico Cortesia.

**Não cobrar, ativar upgrade nem alterar assinatura automaticamente** pela tentativa de acesso; contratação paga exige aceite expresso do administrador autorizado, termos, preço, ciclo e confirmação de pagamento conforme regras já aprovadas. Não oferecer upgrade enganoso em falha técnica, restrição de segurança/identidade ou falta de permissão do usuário. Mensagens, UX, API, auditoria e testes em [12 — Limites e upgrade contextual](12-limites-e-upgrade-contextual.md).

## Planos aprovados em conceito
- Três faixas: **Essencial, Profissional e Empresarial**.
- É possível comprar usuários adicionais, sem limite máximo fixo estabelecido.
- Preços finais, usuários incluídos, demais cotas e matriz definitiva de recursos **pendentes**.
- **Campos personalizados por módulo aprovados:** Essencial 10, Profissional 25 e Empresarial 50 (cada um dos módulos leads, contatos e oportunidades). No downgrade, preservar os existentes e bloquear novos enquanto acima do limite.
- Alterações de preço publicado aplicam-se a novos contratos; contratos vigentes respeitam condições contratadas e comunicação válida.
- Preços e assentos negociados para empresas específicas são permitidos a administradores autorizados, com auditoria.
- Descontos percentuais, fixos e temporários: benefícios não cumulativos, aplicar o maior elegível; limite máximo por papel administrativo configurável. Percentuais ilustrativos não são aprovados.

## IN-030 — Mesmo tipo de anexos no Básico e planos pagos (APROVADA)

**Aprovada em 2026-11-01:** o CRM Básico Cortesia permite **anexar a leads e contatos os mesmos tipos de imagens, PDFs e documentos usuais permitidos no CRM Essencial, Profissional e Empresarial**. A lista de extensões/MIME e verificações de segurança será única para todos os planos, com autenticação, permissões, isolamento entre organizações, antivírus e histórico. **Não restringir formatos do Básico por razões comerciais.** Permanece o **limite global já aprovado de 5 MB por arquivo** para todos os planos e formatos. **A capacidade total de arquivos/anexos será revista em conjunto para os quatro planos**: Básico ainda sem franquia aprovada; quotas pagas anteriores **Essencial 1 GB, Profissional 5 GB, Empresarial 10 GB** permanecem registradas **até decisão expressa de revisão**. Dados estruturados do Básico seguem fora dessa franquia (IN-029). A IN-030 não altera retenção, uploads temporários, quotas nem recursos premium. [Matriz IN-030](13-matriz-permissoes-basico-cortesia.md).

## IN-029 — Dados estruturados do Básico separados da franquia de arquivos (APROVADA)

**Aprovada em 2026-11-01:** no CRM Básico Cortesia, **dados estruturados de leads, contatos e seus históricos não consomem uma franquia comercial fixa de armazenamento de arquivos/anexos**. A quantidade de registros continua **sem teto comercial rígido** (IN-024). **Ainda não decidido:** inclusão de anexos no Básico, franquia/capacidade e tamanho de documentos, políticas para uploads temporários de importação e exportação (IN-025/IN-026), retenção, backups e recuperação. Proteções técnicas e uso justo permanecem aplicáveis sem criar quota comercial disfarçada ou perder leads aceitos. Quotas pagas de **1 GB Essencial, 5 GB Profissional e 10 GB Empresarial** já aprovadas **não foram alteradas nem atribuídas ao Básico** por IN-029. [Matriz IN-029](13-matriz-permissoes-basico-cortesia.md).

## IN-028 — Mesclagem manual de duplicados no CRM Básico (APROVADA)

**Aprovada em 2026-11-01:** o único Administrador da Empresa poderá **mesclar manualmente leads/contatos duplicados**, escolher o registro principal, comparar cadastros, revisar divergências **campo a campo** e confirmar expressamente o resultado. Devem ser preservados **agendamentos, oportunidades, origens dos leads, observações, histórico e auditoria**, em isolamento multiempresa, sem mesclagem automática. Não exigir plano pago para o recurso; não confundir com exclusão controlada (IN-027). Critérios de reversão, consentimentos divergentes, tratamento do registro secundário e limites técnicos ainda serão detalhados. [Matriz IN-028](13-matriz-permissoes-basico-cortesia.md).

## IN-027 — Exclusão definitiva controlada de leads e contatos no Básico (APROVADA)

**Aprovada em 2026-11-01:** o Administrador da Empresa do CRM Básico Cortesia pode **excluir definitivamente leads/contatos elegíveis após confirmação explícita**. Cadastros com vínculos a agendamentos, oportunidades, histórico relevante ou obrigações legais de preservação exigem **fluxo protegido**, respeitando LGPD, integridade e trilhas de auditoria; não realizar exclusão indiscriminada em cascata nem apagar provas legalmente necessárias. Arquivamento já aprovado (IN-023) é operação distinta. **Mesclagem manual de duplicados, eliminação em massa, regras técnicas de elegibilidade, desvinculação, anonimização e recuperação não foram aprovadas por esta decisão.** [Matriz IN-027](13-matriz-permissoes-basico-cortesia.md).

## IN-026 — Exportação CSV/XLSX completa ou filtrada de leads e contatos (APROVADA)

**Aprovada em 2026-11-01:** no CRM Básico Cortesia, o Administrador da Empresa pode exportar **todos os leads/contatos autorizados da própria organização ou apenas os registros filtrados**, em **CSV e XLSX**, com revalidação de acesso, proteção dos downloads, auditoria e controles técnicos proporcionais. Os dados não precisam ser transferidos para o Portal Imagine e a exportação não altera a assinatura do site. **Não** conceder relatórios avançados, API pública, exportação de outros módulos ou dados de outros tenants. Definir expiração dos downloads, campos, lotes e prazo dos arquivos temporários sem bloquear direitos de acesso e portabilidade ou criar quota oculta contrária à IN-024. [Matriz IN-026](13-matriz-permissoes-basico-cortesia.md).

## IN-025 — Importação básica CSV/XLSX no CRM Básico (APROVADA)

**Aprovada em 2026-11-01:** o único Administrador da Empresa pode importar **leads e contatos em arquivos CSV e XLSX**, com mapeamento de colunas, prévia dos registros, validação e alerta de duplicidades, **sem mesclagem automática**. Respeitar permissões multiempresa, integridade, auditoria e escolha explícita de efetivar importação. **Limites técnicos por arquivo/lote, manejo de erros e duplicidades seguem pendentes**, sem introduzir quota comercial total de leads/contatos vedada por IN-024. A autorização não estende ao Básico API pública, automações externas, exportação comercial, exclusão definitiva ou toda a importação avançada do CRM geral. [Matriz IN-025](13-matriz-permissoes-basico-cortesia.md).

## IN-024 — Leads e contatos sem teto comercial de quantidade (APROVADA)

**Aprovada em 2026-11-01:** o CRM Básico Cortesia **não terá teto comercial rígido de quantidade para leads e contatos**, tanto os captados por formulário Imagine como os cadastrados manualmente. Manter **uso justo e proteções antiabuso**, sem limite oculto mensal/total nem bloqueio comercial por quantidade. Não perder leads aceitos e não usar quantidade legítima como motivo para upgrade. **Armazenamento e retenção serão definidos separadamente**; importação em massa, API pública, exportação e exclusão definitiva não são liberadas por essa decisão. A quota do trial de 20 leads não se aplica ao Básico. [Matriz IN-024](13-matriz-permissoes-basico-cortesia.md).

## IN-023 — Leads e Contatos básicos (APROVADA)

**Aprovada em 2026-11-01:** no CRM Básico Cortesia, o único administrador pode **visualizar, cadastrar/editar manualmente, pesquisar/filtrar, registrar observações e histórico manual e arquivar leads/contatos preservando o histórico**. Detectar duplicidade gera **alerta sem mesclagem automática**. O recebimento de leads dos formulários Imagine permanece autorizado pela integração IN-001. **Não houve aprovação de limites quantitativos de leads/contatos, importação em massa, exportação comercial, exclusão definitiva ou mesclagem**; avaliar uso justo, armazenamento, retenção e direitos de privacidade separadamente. Não aplicar ao Básico a quota de 20 leads do trial. [Matriz de permissões IN-023](13-matriz-permissoes-basico-cortesia.md).

## IN-022 — Permissões de Administração da Empresa e Segurança no Básico (APROVADA)

**Aprovada em 2026-11-01:** o único usuário, com perfil Administrador da Empresa (IN-021), pode consultar/atualizar dados cadastrais comuns da própria empresa; alterar CNPJ/identificador fiscal **somente sob validação específica**; gerir senha/e-mail/configurações de MFA com verificação de identidade (**MFA obrigatório permanece**); consultar sessões e encerrar sessões em outros dispositivos; consultar seu plano/limites e contratar upgrade no CRM com **consentimento financeiro expresso**. Criar um 2º usuário, acessar outros tenants ou utilizar superadmin Imagine **não está permitido**. Validação de CNPJ, segurança das alterações e titularidade ainda precisarão de fluxo técnico aprovado. A aprovação não libera automaticamente leads, funis e recursos premium. [Matriz de permissões Básico — IN-022](13-matriz-permissoes-basico-cortesia.md).

## IN-020 — Uma vaga de usuário autenticado no CRM Básico (APROVADA)

**Aprovada em 2026-11-01:** a empresa beneficiária do CRM Básico Cortesia terá até **1 usuário autenticado com acesso ao CRM**, independente da quota de **1 profissional agendável** (IN-008), independentemente do número de sites. **IN-021 (2026-11-01) aprovou o perfil obrigatório Administrador da Empresa para a única conta do Básico, com MFA obrigatório**, autorizações restritas aos recursos incluídos e sem poderes de superadministrador SaaS. A matriz de operações por módulo, convites e substituição ainda serão detalhadas. Ao tentar incluir o 2º login, informar **1/1**, sugerir upgrade para plano elegível (ou adicional aprovado, se existir), e **não ativar nova assinatura nem cobrar sem aceite expresso**, conforme IN-010. Não alterar as regras já aprovadas de contratação de usuários extras dos planos pagos. [Agenda/usuários Básico](11-agenda-manual-basico-cortesia.md).

## IN-013 — Notificações e lembretes internos no Básico, sem consumir automações

**Aprovada em 2026-11-01:** no CRM Básico Cortesia, a agenda oferece **apenas avisos e lembretes internos na interface autenticada do CRM**, como funções do sistema que **não consomem vagas da biblioteca de automações comerciais**. **E-mails automáticos ao cliente atendido e WhatsApp Business API oficial** ficam disponíveis somente por **planos pagos ou adicionais elegíveis**, com contratação e autorização específicas. Não confundir comunicação interna com mensagem enviada ao visitante. **IN-014 aprova lembretes internos padrão 24h e 1h antes do compromisso, com possibilidade de o profissional ajustar ou desativar cada lembrete independentemente**. **IN-015 (2026-11-01) aprovada:** central de notificações interna persistente com lido/não lido, inclusive para lembretes vencidos durante ausência e consultados após o retorno. **IN-016 (2026-11-01) aprovada:** abrir os detalhes de uma notificação marca-a automaticamente como lida; é possível marcar novamente como não lida, sem limpar todo o histórico. **IN-017 (2026-11-01) aprovou filtros por estado (Todas, Não lidas, Lidas), por tipo de evento e por período, combináveis, com ordenação mais recente primeiro**. **IN-018 (2026-11-01) aprovada:** retenção do histórico de notificações internas do CRM Básico por **90 dias**, independente da retenção de agendamentos e de auditoria. **IN-019 (2026-11-01) APROVADA:** arquivamento manual de notificações lidas e não lidas, com restauração antes dos 90 dias; todas deixam a central ao fim do prazo, independentemente da leitura, sem excluir agendamentos e auditoria. **Pendentes:** pesquisa textual, ações em lote e descarte físico/backup. [Especificação IN-013](11-agenda-manual-basico-cortesia.md).

## Benefício CRM Básico vinculado ao Site Imagine — IN-002 APROVADA em 2026-11-01

- Quem contrata Site Onepage ou futura Landing Page da Imagine recebe **acesso ao CRM Básico sem cobrança extra**, enquanto elegível ao benefício vinculado ao contrato do site. **Portal Imagine cobra somente o site** (Onepage: implantação R$ 990 em D0/D30/D60; assinatura R$ 79,90/mês a partir de D90).
- Ao contratar **upgrade para um plano pago** pelo CRM Imagine, passa a pagar **duas mensalidades distintas**: site no Portal Imagine e plano avançado no CRM Imagine. Upgrade não substitui nem altera a assinatura do site.
- **Não há assinatura CRM Básico financeira de valor zero a ser cobrada; o acesso é uma concessão/entitlement vinculado ao site.** O upgrade no CRM requer contratação e autorização financeira próprias.
- Esta cortesia é **exceção condicionada ao contrato do site**, não um plano gratuito permanente aberto ao público. O teste de 14 dias permanece independente e não deve ser usado como substituto do benefício.
- **IN-003 (2026-11-01) aprovada:** o **CRM Básico cortesia terá funcionalidades mais enxutas do que o Essencial**, e será exclusivo dos contratantes elegíveis de Onepage/Landing Page.
- **IN-004 (2026-11-01) aprovada:** incluir **Agenda e Agendamentos** no CRM Básico cortesia. **IN-007 aprovada (2026-11-01):** agenda e agendamentos do Básico são **internos/manuais**, enquanto página pública e reservas automáticas pelo site são **exclusivas dos planos pagos**. **IN-008 (2026-11-01) aprovada:** somente **1 profissional agendável por organização** no Básico; **IN-009 (2026-11-01) aprovada:** até **10 serviços ativos** na agenda desse profissional. **IN-011 (2026-11-01) aprovada:** agenda manual do Básico tem configuração flexível dos dias/períodos, pausas, bloqueios de férias/folgas/feriados manuais e agendamentos excepcionais fora do expediente com aviso, confirmação expressa de usuário autorizado e prevenção de conflitos. **IN-012 (2026-11-01) aprovada:** os agendamentos **manuais** do Básico Cortesia ficam **sem teto comercial mensal rígido**, sujeitos a uso justo e controles técnicos contra abuso. Não confundir com autoagendamento público, que continua restrito aos planos pagos. Usuários CRM e número mensal de reservas são quotas diferentes e continuam a definir. [Detalhamento da agenda manual](11-agenda-manual-basico-cortesia.md). Quotas e recursos avançados da agenda interna ainda pendentes; os agendamentos ilimitados já aprovados para os planos pagos **não** se estendem automaticamente ao Básico. **IN-005 aprovada em 2026-11-01:** Básico Cortesia permite até **3 automações comerciais ativas por empresa**, exclusivamente por **modelos prontos**, sem liberar editor completo. **IN-006:** biblioteca segmentada; alguns modelos avançados ficam reservados aos planos pagos, catálogo exato pendente. Rever ao fim do planejamento as quantidades de automações por plano, incluindo as três do Básico. Catálogo e parâmetros ainda pendentes; modelos de exemplo em [revisão Agenda e Automações](09-revisao-agenda-e-automacoes-basico.md). [Revisão de Agenda e Automações](09-revisao-agenda-e-automacoes-basico.md). Não redefinir os planos comerciais **Essencial, Profissional e Empresarial**. A **matriz de recursos e quotas** continua proposta, sujeita à aprovação em [08 — Plano Básico cortesia](08-plano-basico-cortesia.md).
- O vínculo é por empresa/organização e pedido elegível, com isolamento multi-tenant; usuário de várias empresas não recebe automaticamente o benefício em todas.
- Upgrade self-service e pró-rata, liberação após pagamento e downgrade no próximo ciclo seguem as regras já aprovadas para **assinaturas pagas**. Retorno ao Básico quando terminar plano CRM pago e o site ainda estiver elegível exige regra detalhada para excedentes, período de acesso, retenção e comunicação.
- Cancelamento/suspensão do site não pode cancelar automaticamente uma assinatura CRM avançada contratada à parte. Política específica de elegibilidade do benefício durante inadimplência, encerramento e recontratação permanece pendente.
- **Integração técnica e comercial detalhada:** [07 — Integração de sites Imagine com CRM Básico cortesia](07-integracao-site-e-crm-basico.md).

## Teste gratuito — APROVADO
- **14 dias**, sem cartão.
- **CNPJ obrigatório**, um único teste por CNPJ.
- OTP obrigatório para e-mail e telefone; telefone validado por SMS ou API oficial WhatsApp, à escolha do usuário.
- Sem extensão manual ou automática.
- Ao expirar: bloqueio de acesso operacional e preservação de dados por mais **60 dias**, após os quais exclusão elegível sujeita a obrigações legais.
- Abuso ou fraude: administrador autorizado pode encerrar antecipadamente com justificativa e auditoria; não concede segundo teste.

### Limites no trial
| Recurso | Limite |
|---|---:|
| Usuários | 2 |
| Leads | 20 |
| Pipelines | 2 |
| Automações ativas | 2 |
| Armazenamento | 20 MB |
| Importação CSV | Permitida |
| API pública | Desativada |
| Agente IA Prospector | 10 análises |
| Agente IA Comercial | 10 solicitações |

As duas cotas de agentes são independentes.

## Mudanças de plano
- Upgrade self-service: diferença proporcional ao restante do ciclo; mantém vencimento/renovação original; recursos liberados após confirmação do pagamento.
- Downgrade: vale no próximo ciclo; antes da mudança é necessário tratar recursos que excedam os limites do destino.
- Cancelamento self-service: desativa renovação automática; mantém acesso até o fim do período pago.
- Após fim de assinatura paga: **120 dias** de retenção; recontratação dentro do prazo pode restaurar dados via autoatendimento, com recursos acima do plano bloqueados/restritos, sem apagar dados.

## Inadimplência
- **7 dias corridos** após vencimento: tolerância com acesso e avisos.
- Após o sétimo dia: suspensão automática.
- Após **30 dias de atraso**: cancelamento automático e início da retenção de 120 dias.
- Enquanto suspensa por inadimplência e ainda não cancelada, área limitada de cobrança e reativação após confirmação de pagamento.
- Suspensão administrativa independente não é revertida automaticamente pelo pagamento.

## Cobrança
- Recorrência preferencial por cartão de crédito e **Pix Automático**.
- Também permitir Pix avulso e boleto.
- Troca self-service do método de pagamento para próximos ciclos, sem alterar data de renovação.
- Falha de cobrança: registrar/notificar, oferecer método alternativo e tentativas permitidas; evitar débito duplicado.
- Portal financeiro do cliente: faturas, situações, histórico, recibos, próxima cobrança e composição de valores.
- NFS-e automática via provedor fiscal, vinculada à fatura; monitorar falhas. Provedor e detalhes tributários pendentes.
- Estornos totais e parciais exigem permissão financeira, justificativa e auditoria, não cancelam automaticamente a assinatura.
- Créditos financeiros para cobranças futuras, por empresa, com motivo e auditoria; sem expiração automática enquanto ativa; no cancelamento manter saldo durante retenção, tratamento final sujeito a contrato/lei.

## Limites de automações por plano — APROVADO
- Limite de automações **ativas simultaneamente por empresa**: Essencial **5**, Profissional **20**, Empresarial **50**.
- **Revisão futura determinada em 2026-11-01 (IN-006):** manter essas quantidades aprovadas como baseline para o planejamento, **sem alterar agora**. Primeiro documentar toda a biblioteca de modelos, categorias, custos e direitos por plano. Ao fim, submeter nova proposta consolidada de quotas para Básico Cortesia e os três planos pagos, com aprovação explícita. [Biblioteca e critérios](10-biblioteca-automacoes-e-criterios-de-planos.md).
- Automações em rascunho não consomem esse limite e não há limite de quantidade de rascunhos definido nesta decisão.
- Em downgrade, preservar configurações e históricos, pausar automações excedentes e permitir que administrador escolha quais permanecerão ativas dentro do novo limite.

## Limites de fluxos ativos do chatbot WhatsApp — APROVADO
| Plano | Fluxos ativos por empresa |
|---|---:|
| Essencial | 3 |
| Profissional | 10 |
| Empresarial | 30 |

Rascunhos não contam; upgrade amplia capacidade. No downgrade, configurações são preservadas e os fluxos excedentes pausados; administrador seleciona quais ficam ativos. Limite independente de automações comerciais.

## Limites de unidades de atendimento por plano — APROVADO
| Plano | Unidades incluídas por empresa |
|---|---:|
| Essencial | 1 |
| Profissional | 3 |
| Empresarial | 10 |

Permitir unidades adicionais com cobrança extra sem mudança de plano (preço a definir). Upgrade amplia capacidade; downgrade preserva unidades e históricos, mas inativa as excedentes até regularização, sem excluir agendamentos passados.

## Agendamentos ilimitados por plano — APROVADO
- Essencial, Profissional e Empresarial incluem agendamentos mensais ilimitados, sem cobrança adicional por volume de reservas.
- Diferenciação dos planos permanece em funcionalidades, usuários, unidades, automações, armazenamento e demais limites específicos.
- Aplicar medidas técnicas proporcionais contra abuso e excesso de requisições em todos os planos, sem estabelecer teto comercial de agendamentos.

## Quotas totais de armazenamento por plano — APROVADO
- Plano **Essencial: 1 GB** de armazenamento total por empresa.
- Plano **Profissional: 5 GB** de armazenamento total por empresa.
- Plano **Empresarial: 10 GB** de armazenamento total por empresa (ajuste explícito da proposta inicial de 20 GB).
- Quota compartilhada entre todos os funcionários da empresa, contabilizada pelo tamanho efetivamente armazenado após otimização e incluindo versões adicionais quando existentes.
- Ao atingir a quota, impedir novos uploads, mantendo acesso autorizado aos arquivos existentes; respeitar também o limite global de 5 MB por arquivo.
- Definir posteriormente tratamento de downgrade quando o armazenamento existente ultrapassar a quota do novo plano e eventuais pacotes extras de armazenamento.

## Direção comercial de lançamento — APROVADA (preços ainda em validação)
- Adotar modelo híbrido de **mensalidade fixa por empresa, com usuários incluídos por plano**, em vez de cobrar por usuário desde o primeiro acesso.
- Manter os planos Essencial, Profissional e Empresarial; referências comerciais **não definitivas**: R$ 69/mês com 2 usuários, R$ 149/mês com 5 usuários e R$ 299/mês com 10 usuários, sujeitas a validação de mercado e aprovação posterior.
- Direção aprovada: contatos ilimitados nos planos pagos sujeitos a política de uso justo, sem cobrança por contato individual; permitir contratação paga de usuários adicionais, sem teto rígido; agentes de IA e WhatsApp como opcionais, com custos próprios claramente identificados.
- Manter teste gratuito de 14 dias sem cartão e sem plano gratuito público permanente no lançamento inicial, **ressalvada a cortesia condicionada à contratação de site Imagine aprovada em IN-002**.
- Estratégia inicial de aquisição: foco em pequenas empresas que recebem leads e precisam organizar vendas; testar parcerias com agências e consultores, aquisição via produto e marketing segmentado; validar preços e disposição a pagar com potenciais clientes antes do lançamento.
- Acompanhar ativação, conversão trial-pago, MRR, CAC, churn, retenção e margem para ajustar estratégia de crescimento.
- **Revisão pendente:** quotas técnicas de armazenamento de 1/5/10 GB anteriormente aprovadas permanecem registradas; definir política comercial e operacional de uso justo, sem prometer armazenamento absolutamente ilimitado.
- Não tratar métricas-alvo, comissões de parceiros ou preços ilustrativos como valores aprovados.
