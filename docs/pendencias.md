# Pendências de especificação — 31/10/2026

## Prioritárias
1. Detalhar exportações CSV/XLSX já aprovadas: política de expiração dos links, limites de volume, formato dos campos e retenção dos arquivos temporários.
2. Detalhar importação já aprovada: normalização de telefone/e-mail, regras de deduplicação, limites de tamanho e política de atualização dos campos.
4. Definir preços dos planos Essencial, Profissional e Empresarial, assentos incluídos, cotas e matriz de recursos.
4. Ratificar estratégia multi-tenant: `tenant_id`, PostgreSQL RLS, isolamento no backend/testes.
5. Desenhar CRM operacional: leads, contatos, empresas, oportunidades, pipelines, etapas, atividades, tarefas e automações.
6. Especificar agentes IA Prospector/Comercial: funções, prompts, ações permitidas, preços e cotas.
7. Definir preços, tributos, provedor de pagamento e integração NFS-e.
8. Detalhar política de LGPD, descarte de backups e pedidos de titulares.
9. Elaborar arquitetura física, tabelas, APIs, filas, eventos, autorização e testes.
10. Planejar recuperação de desastres para comprovar RPO de 15 minutos/RTO de 4 horas.
11. Definir UX, protótipos e critérios de aceite antes de iniciar desenvolvimento.

## Integração Imagine Portal × CRM — pendências após IN-002 (01/11/2026)

- **Aprovado:** comprador de Onepage/futura Landing Page recebe benefício gratuito de CRM Básico e paga somente o site no Portal Imagine; upgrade CRM é contratado e cobrado no próprio CRM, criando segunda mensalidade.
- **IN-003 aprovada:** Básico cortesia será uma versão enxuta, exclusiva para site/landing page, diferente do Essencial.
- **IN-004 aprovada:** liberar Agenda e Agendamentos no Básico. **IN-007 APROVADA:** Básico terá somente agendamentos **internos e manuais**; autoagendamento público por site/página do CRM é exclusivo dos **planos pagos**. **IN-008 APROVADA:** apenas **1 profissional com agenda própria por organização Básico**. **IN-009 APROVADA:** até **10 serviços ativos por organização** no Básico; serviços inativos não consomem vaga e históricos permanecem. **IN-011 APROVADA:** horários semanais flexíveis, períodos múltiplos, pausas e bloqueios, feriados manuais, exceções fora do expediente com confirmação de usuário autorizado e sem conflitos. **Ainda definir:** quantidade de usuários administrativos, **IN-012 APROVADA:** agendamentos manuais sem teto comercial mensal, com uso justo e controles antiabuso. **Pendente apenas detalhar** limites técnicos de execução, justificativas/exceções de bloqueios, confirmações/notificações e integrações; propostas em [11 — Agenda manual](11-agenda-manual-basico-cortesia.md). **IN-005 APROVADA:** até 3 automações comerciais ativas por organização, escolhidas de modelos prontos. **Pendente:** catálogo concreto (sugestões T01–T05), parâmetros, avisos de agenda e regras de limite/custo; não aplicar por analogia cotas dos planos pagos. Documento: [09-revisao-agenda-e-automacoes-basico.md](09-revisao-agenda-e-automacoes-basico.md). **Ainda aprovar** matriz recomendada de recursos/quotas (1 usuário, 1 funil, formulários por site, sem agentes de IA; modelos de automação comercial permitidos conforme IN-005/IN-006), após avaliar escala, custo, uso justo e retenção. Ver [08-plano-basico-cortesia.md](08-plano-basico-cortesia.md).
- Definir vigência do benefício e regras de inadimplência, cancelamento, estorno e recontratação do site.
- Definir fallback do plano pago CRM para o Básico, limites excedentes e retenção.
- Definir catálogo e valor da Landing Page e marcos para concessão do benefício.
- Definir API autenticada, vínculos tenant/pedido, fila idempotente de lead forms, privacidade e testes de integração.
- Documento: [07-integracao-site-e-crm-basico.md](07-integracao-site-e-crm-basico.md).

## Biblioteca de Automações — IN-006 (01/11/2026)

- **Diretriz:** biblioteca central de modelos prontos, organizada por módulos e critérios de elegibilidade; modelos simples úteis ao Básico e **modelos mais avançados para planos pagos**. Classificações individuais ainda são propostas; confirmar a interpretação na matriz comercial final.
- **Ordem aprovada do trabalho:** documentar todos os modelos/categorias e seus custos/dependências **antes** de rever a quantidade simultânea por plano. **Não mudar agora** os limites anteriores: Básico Cortesia 3; Essencial 5; Profissional 20; Empresarial 50.
- **Pendência de fechamento:** contar modelos homologados e simular uso/custos, definir quais ficam em cada plano, distinguir automações comerciais de operações obrigatórias da agenda/sistema, e só então submeter novas quotas numéricas para aprovação.
- Referência: [10-biblioteca-automacoes-e-criterios-de-planos.md](10-biblioteca-automacoes-e-criterios-de-planos.md).

## Leads e Contatos do CRM Básico — IN-023 (01/11/2026)

- **IN-023 APROVADA:** o Administrador da Empresa pode visualizar lista/detalhes, cadastrar/editar manualmente, pesquisar/filtrar, adicionar observações/histórico manual, receber alertas de possíveis duplicidades **sem mesclagem automática** e arquivar leads/contatos **preservando histórico e vínculos**.
- **IN-001 já aprovada:** integração de captação dos formulários Onepage/Landing Page para leads na organização correta; não descartar envio confirmado por erro/quota não aprovada.
- **IN-024 APROVADA:** leads/contatos sem teto comercial rígido de quantidade — tanto captados pelo site quanto cadastrados manualmente — sujeitos a uso justo/antiabuso. Não inventar quota oculta nem perder leads já aceitos; a quota de 20 leads do trial não se aplica.
- **IN-025 APROVADA:** importação básica de leads e contatos por **CSV e XLSX**, com mapeamento, prévia, validação e alertas de duplicidade, **sem mesclagem automática**. O upload é manual e autorizado pelo Administrador da Empresa, sem API pública ou integração de importação automatizada.
- **IN-026 APROVADA:** exportar todos os leads e contatos autorizados ou somente os filtrados em **CSV e XLSX**, com segurança dos arquivos, auditoria, isolamento multiempresa e controles técnicos, sem condicionar portabilidade legal a upgrade.
- **IN-027 APROVADA:** exclusão definitiva **controlada** de leads/contatos elegíveis por Administrador da Empresa após **confirmação explícita**; com vínculos, histórico ou obrigação de preservação, exigir **fluxo protegido**, respeitar LGPD, integridade e auditoria, sem cascata indevida.
- **IN-028 APROVADA:** mesclagem **manual** de leads/contatos duplicados com escolha do cadastro principal, revisão campo a campo de conflitos, confirmação explícita e preservação de agendamentos, oportunidades, origens, históricos, observações e auditoria; **proibida mesclagem automática**.
- **IN-029 APROVADA:** **dados estruturados de leads, contatos e históricos não consomem franquia comercial fixa de arquivos/anexos**; quantidade de registros sem teto comercial rígido IN-024. Uso justo, segurança, continuidade de captação e integridade dos leads aceitos continuam obrigatórios.
- **IN-030 APROVADA:** Básico aceita os mesmos tipos de anexos dos planos pagos, com segurança e histórico; limite geral já aprovado **5 MB por arquivo**. **IN-031 substituiu posteriormente a pendência de franquias.**
- **IN-031 APROVADA:** quotas por empresa **exclusivas para arquivos/anexos**: **Básico 1 GB; Essencial 5 GB; Profissional 20 GB; Empresarial 50 GB**. **Dados estruturados não consomem franquia em nenhum dos quatro planos**; valores pagos anteriores 1/5/10 GB foram substituídos.
- **IN-032 APROVADA:** exibir utilização e saldo da franquia para todos os planos, emitir **avisos internos no CRM em 80% e 95%**, e a **100% impedir somente novos uploads de anexos**, preservando todos os arquivos existentes, a entrada/gestão de leads e contatos e os demais dados estruturados. Sem aviso externo ou cobrança automática implícitos.
- **PENDENTE:** critérios exatos de elegibilidade e fluxo de registros vinculados; tratamento legal de anonimização/desvinculação e eventual recuperação; **detalhes técnicos de mesclagem manual IN-028 (conflitos de consentimento, histórico do secundário, reversão e tratamento de vínculos)**; limites técnicos de importação/exportação, arquivos temporários, filtros/campos sensíveis, uso justo, **detalhes técnicos/UX e recorrência dos avisos da IN-032, armazenamento adicional e preços**, arquivos temporários e **retenção/recuperação** (dados estruturados já separados da franquia comercial em IN-029). Total de registros segue sem teto comercial rígido (IN-024).
- [Matriz detalhada IN-023](13-matriz-permissoes-basico-cortesia.md).

## Quota de usuários da empresa no CRM Básico — IN-020 (01/11/2026)

- **APROVADO:** até **1 usuário autenticado com login por empresa/organização do Básico**, quota independente de **1 profissional agendável** (IN-008).
- Excesso de usuários: informar limite **1/1**, sugerir upgrade ou adicional de assento que realmente resolva, sem cobrança automática (IN-010), sem compartilhar identidade ou criar login por serviço/site.
- **IN-021 APROVADA:** único usuário Básico usa perfil **Administrador da Empresa**, com **MFA obrigatório**, direitos administrativos somente sobre módulos incluídos no plano, sem superadmin SaaS; preservar auditoria, RBAC e isolamento entre empresas.
- **IN-022 APROVADA:** consultar/atualizar cadastro comum da empresa; CNPJ somente com validação específica; senha/e-mail/MFA sob verificação de identidade, com MFA obrigatório; consultar/encerrar sessões; consultar plano/limites e contratar upgrade após aceite expresso; sem 2º usuário, outros tenants ou superadmin.
- **PENDENTE:** fluxo exato de validação de CNPJ e mudança de identidade, titularidade e troca/recuperação da conta, convites e participação em várias empresas. **Leads e Contatos: IN-023 (operações), IN-024 (quantidade), IN-025 (importação CSV/XLSX), IN-026 (exportação CSV/XLSX), IN-027 (exclusão definitiva controlada) e IN-028 (mesclagem manual controlada) APROVADAS.** Pendentes: **detalhes técnicos da mesclagem**, critérios de elegibilidade e fluxo de exclusão vinculada, parâmetros técnicos de importação/exportação, **detalhes operacionais dos avisos IN-032, adicionais e arquivos temporários**, retenção e proteção LGPD; dados estruturados fora da franquia fixa de arquivos por IN-029. [Matriz de permissões](13-matriz-permissoes-basico-cortesia.md).
- Documento principal: [11-agenda-manual-basico-cortesia.md](11-agenda-manual-basico-cortesia.md).

## Notificações e lembretes internos de agenda — IN-013 (01/11/2026)

- **APROVADO:** somente avisos e lembretes internos no CRM Básico; não consomem quota das automações comerciais.
- **APROVADO:** e-mails automáticos ao cliente e mensagens pela API oficial do WhatsApp são recursos de planos pagos ou adicionais elegíveis, com autorização e contratos/custos próprios; **não incluídos no Básico**.
- **IN-014 APROVADA:** lembretes internos 24h e 1h antes do agendamento, independentes e ajustáveis/desativáveis por compromisso. **IN-015 APROVADA:** central de avisos interna persistente com estados lido/não lido, inclusive após o usuário retornar. **IN-016 APROVADA:** abrir os detalhes marca a notificação como lida, com opção para voltar a não lida; ver a lista não altera estados. **IN-017 APROVADA:** filtros por estado (Todas/Não lidas/Lidas), tipo de evento e período, ordenação dos mais recentes primeiro. **IN-018 APROVADA:** histórico de notificações internas no Básico retido por **90 dias**, independente de agendamentos e trilhas de auditoria. **IN-019 APROVADA:** arquivar e restaurar manualmente notificações lidas e não lidas antes dos 90 dias, com saída de todas da central ao vencer o prazo e sem excluir agendamentos/auditoria. **PENDENTE:** descarte técnico, filtros/aba arquivadas e detalhes de UX, pesquisa textual, ações em lote, soneca/repetição, preferências globais, push e canais externos dos pagos.
- Referência: [11-agenda-manual-basico-cortesia.md](11-agenda-manual-basico-cortesia.md).

## Avisos por plano e upgrade contextual — IN-010 (01/11/2026)

- **Aprovado:** ao tentar recurso fora do plano ou atingir quota, explicar motivo e oferecer upgrade pertinente (ou adicional elegível), preservando opção de liberar capacidade dentro do plano; sem ativação, cobrança ou mudança de plano sem consentimento e autorização.
- Não oferecer upsell por problemas de segurança, falta de permissão de usuário, cobrança pendente, integração fora do ar ou erro técnico.
- **Pendente:** UX detalhada, avisos preventivos, cores/textos, política de exibição, contrato de erros para API, matriz comercial final, custos e preços publicados, casos de trial e assinaturas negociadas.
- Detalhes: [12-limites-e-upgrade-contextual.md](12-limites-e-upgrade-contextual.md).

## Observações
- Este conjunto é um marco documental inicial; não significa que todos os capítulos estão fechados.
- Não aprovar automaticamente exemplos, descontos ilustrativos nem arquitetura apenas sugerida.

11. Detalhar campos personalizados: matriz técnica de conversões compatíveis entre tipos, validações específicas por tipo, impacto da obrigatoriedade sobre registros antigos e procedimentos de tratamento dos dados antes da exclusão definitiva (regras gerais de configuração e proteção já aprovadas).
