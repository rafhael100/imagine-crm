# Pendências de especificação — 31/10/2026

> **ATUALIZAÇÃO POSTERIOR IN-116 (01/11/2026):** os valores atuais de planejamento são **Básico R$ 69,00/mês/empresa** e **Essencial R$ 89,00/mês/empresa**. Referências dos demais: **Profissional R$ 149,00** e **Empresarial R$ 299,00**. **Todos os preços, limites e recursos incluídos serão revisados em conjunto somente ao final de toda a documentação do projeto, antes de aprovação final e implementação.** Parágrafos históricos que contenham R$ 59 ou R$ 79 referem-se a uma decisão anterior substituída por esta atualização; a mensalidade dos sites (R$ 79,90) permanece inalterada.


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


## IN-116 — Revisão comercial: Básico público R$ 59,00 e Essencial R$ 79,00 (01/11/2026)

**Decisão de planejamento:** oferecer **CRM Imagine Básico** como quarto plano público/avulso por **R$ 59,00 mensais por empresa**, com **1 usuário** e todos os limites do Básico anteriormente chamado "Cortesia"; para clientes com Onepage ou Landing Page elegível, **conceder o mesmo produto Básico sem mensalidade adicional de CRM**, mantendo apenas o contrato e cobrança do site no Portal Imagine. **"Cortesia" passa a descrever o benefício de concessão ao assinante do site, não um quinto plano ou versão separada do Básico.** A antiga exclusividade comercial do Básico para clientes de sites (IN-003) fica **substituída nessa dimensão específica**; permanecem suas funcionalidades, restrições e diferenciação em relação ao Essencial. Não transformar benefício em assinatura paga sem aceite expresso, nem duplicar cobrança quando cliente com Básico pago contratar site ou vice-versa.

**Preço do Essencial:** **R$ 79,00 mensais por empresa** (referência anterior R$ 69 substituída). **Profissional R$ 149/mês/empresa e Empresarial R$ 299/mês/empresa continuam apenas referências anteriores**, não preços finais aprovados. O quadro inicial de usuários segue em referência: Básico 1 aprovado, Essencial 2, Profissional 5 e Empresarial 10 sujeitos à validação. **Básico R$ 59 e Essencial R$ 79 são valores escolhidos para o planejamento comercial**, sem ativação pública/financeira; manter validação de custos, diferenciação de recursos, contratos e homologação antes do lançamento. A comunicação do valor de R$ 59 incluído aos assinantes do site exige que o Básico esteja realmente disponível ao público por esse valor, sem preço de comparação fictício.

**Operação e guardrails:** cobrança autônoma do CRM somente pelo CRM Imagine, assinatura do site somente no Portal Imagine; benefício não deve gerar segunda mensalidade de CRM, boleto de valor zero ou desconto fictício. Trial de 14 dias permanece independente. Contratação, cancelamento, retorno de plano pago ao benefício e transição da cobrança Básico pago/benefício exigem regras de ciclo, consentimento, continuidade de dados e proteção contra cobrança duplicada a detalhar. O preço de usuários adicionais, formulários públicos para Básico avulso sem site, matriz de recursos do Essencial e política de prospecção/upgrade ainda são pendências, não concessões automáticas. Preservar condições contratuais dos clientes legados.

**Documento mestre:** [IN-116 — Básico Público e Preços](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/14-in116-basico-publico-e-precos.md). **Somente documentação; nenhuma programação, contratação, cobrança, alteração em produção ou publicação comercial.**


### IN-116 — Revisão posterior de preços e ponto de controle obrigatório ao fim do planejamento (01/11/2026)

**Última decisão comercial do responsável, prevalecendo sobre referências anteriores de preços da IN-116:** **CRM Básico R$ 69,00 por mês por empresa** (em lugar dos R$ 59,00 propostos anteriormente) e **CRM Essencial R$ 89,00 por mês por empresa** (em lugar dos R$ 79,00 propostos anteriormente). **Profissional R$ 149,00/mês/empresa e Empresarial R$ 299,00/mês/empresa permanecem apenas referências anteriores**, ainda não ratificadas definitivamente. Quotas de usuários de referência continuam Básico 1 aprovado; Essencial 2, Profissional 5 e Empresarial 10 sujeitos à revisão final. A diferença entre Básico e Essencial continua sendo **R$ 20,00 por empresa por mês**.

**Decisão de processo de planejamento:** **somente quando a documentação funcional, técnica, comercial, operacional e contratual de todo o CRM Imagine e da integração com o Portal Imagine estiver integralmente concluída**, realizar uma **revisão geral consolidada de todos os preços e serviços/recursos incluídos de cada plano**, com matriz comparativa completa, limites reais, cotas, opcionais, usuários, automações, armazenamento, agenda, IA, custos de infraestrutura/atendimento, margem, preço percebido, upgrades, elegibilidade promocional do site, contratos, impostos e sustentabilidade comercial. Apresentar os resultados para **nova aprovação expressa do responsável** antes de declarar tabela definitiva, habilitar vendas ou iniciar programação. **Não interromper agora o planejamento dos módulos para revisar continuamente cada diferença de preço/funcionalidade.**

**Preservar a estratégia aprovada na IN-116:** o **Básico** é um plano real e público do CRM, que poderá ser contratado autonomamente por **R$ 69,00/mês** após aprovação final e lançamento; o **mesmo Básico**, sem restrição adicional ou quinta versão, é concedido como benefício **sem segunda mensalidade CRM** ao cliente elegível de Onepage/Landing Page. Somente mostrar o preço comparativo ao público quando este for **real e efetivamente praticado**, com regras claras de elegibilidade e sem criar desconto fictício. As mensalidades de **sites Imagine R$ 79,90** não foram alteradas, bem como suas implantações e contratos. Manter trial separado, fluxos de aceite, proteção contra cobranças duplicadas e direitos já aprovados; a revisão de preços não autoriza modificação de serviços, cotas ou arquitetura agora.

**Estado:** valores R$ 69/R$ 89 **aprovados como referências atuais de planejamento**, com **revisão final obrigatória** quando toda a documentação do projeto estiver concluída; nenhum preço publicado ou cobrado, nenhum código, contrato ou produção alterado.


### IN-117 — Complemento aprovado: CRM Básico independente incluído por novo site, inclusive mesmo CNPJ (01/11/2026)

**Decisão posterior que resolve a pendência comercial da IN-117:** **cada novo contrato de Onepage ou Landing Page Imagine elegível** poderá **conceder um novo ambiente/organização CRM Básico independente, com os mesmos recursos e quotas do Básico e sem segunda mensalidade CRM, mesmo quando todos os contratos pertencem ao mesmo CNPJ**. O cliente escolhe de forma expressa no **Portal Imagine**: **(A) vincular o novo site a uma organização CRM existente autorizada; ou (B) criar uma organização CRM nova, segregada e incluída por esse novo contrato de site elegível**. A escolha de novo ambiente **não cria automaticamente outra identidade, senha ou usuário de login**; o mesmo administrador autenticado pode alternar organizações autorizadas e ocupa uma vaga por organização. Se optar pelo CRM existente, múltiplos sites não somam nem duplicam usuários, agendas, automações ou franquias na mesma organização; distinguir leads por site, formulário e campanha. Se optar pelo ambiente separado, cada organização Básico possui sua própria franquia, acesso e direitos, **condicionados ao respectivo vínculo contratual** e com isolamento multiempresa.

**Elegibilidade/financeiro/cancelamento:** não criar ambiente por mera repetição de CNPJ ou evento de pagamento duplicado; requer um contrato adicional real elegível, titularidade e autorização verificadas e provisionamento idempotente. O Básico permanece um plano público real (referência de planejamento **R$ 69/mês**) que se concede sem segunda cobrança enquanto o benefício do site for elegível; **não é cobrança fictícia de R$ 0 nem inclusão permanente sem contrato**. Cada site segue com sua cobrança própria no Portal. Cancelamento/inadimplência de um site não cancela automaticamente outro site, outro ambiente CRM ou uma assinatura CRM paga. Organizações ainda sustentadas por outros contratos elegíveis preservam o benefício. Se não houver mais contrato elegível para determinada organização, **não converter em plano pago nem cobrar automaticamente**: oferecer opção de contratação expressa e seguir políticas futuras de ciclo, suspensão e retenção, sem apagar dados inadvertidamente.

**Governança e pendências restantes:** controle de titularidade, direitos de representante, prevenção de ambientes duplicados por erro, criação sob mesmo CNPJ permitida por contrato real, proteção LGPD, auditoria, origem dos leads e eventual mudança posterior do destino continuam exigindo especificação; a faculdade de novo ambiente **já foi aprovada**, e esses controles **não criam um novo limite numérico por CNPJ, taxa ou necessidade de login novo**. Preservar as decisões IN-001/002, IN-020/021, IN-116, e a revisão de preços/serviços incluídos ao término da documentação total. **Aprovação exclusivamente documental; sem programação, criação de tenant real, cobrança, deploy ou publicação.**

**Referência mestre revisada:** [IN-117 — Destino CRM de cada novo site](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md).
