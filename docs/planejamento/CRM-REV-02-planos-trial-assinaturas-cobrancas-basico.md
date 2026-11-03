# CRM-REV-02 — Planos, trial, assinaturas, cobrança e benefício CRM Básico

**Data:** 2026-11-03.
**Estado atualizado:** **APROVADA COMO BASE DOCUMENTAL DE RECONCILIAÇÃO**, conforme [decisão contextual de 03/11/2026](CRM-DEC-aprovacao-REV-02-base.md). Permanecem **abertas** as 12 diferenças documentais REV02-D01..12, as 12 pendências CRM-REV02-P01..12, parâmetros físicos e preços definitivos. A [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) foi elaborada **como minuta ainda não aprovada**.
**Escopo:** CRM-04/05, integração CRM-15 e partes comerciais CRM-13/16. O **Portal Imagine é outro produto** com cobrança própria; seus contratos de site e campanha influenciam apenas elegibilidade do benefício.
**Regra de precedência:** distinguir regras históricas expressamente aprovadas, decisões comerciais posteriores e **propostas técnicas pendentes**. **Não alterar preços nem planos automaticamente neste documento**; revisão final de tabela e recursos será conjunta após toda a documentação.
**Fase:** só GitHub e planejamento. Sem cobrança, emissão NFS-e, teste de PSP, implantação, criação de assinaturas reais, programação ou despesa.

## 1. Fontes consultadas e seus limites

| Ref | Documento localizado | Tema |
|---|---|---|
| LEG-02 | [02 — Planos, trial e assinaturas](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/02-planos-trial-e-assinaturas.md) | Preço vigente de planejamento, 14 dias/trial, mudanças de plano, inadimplência, cobrança, quotas |
| LEG-08 | [08 — CRM Básico e recursos](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/08-plano-basico-cortesia.md) | Direitos do Básico, agenda manual e diferença de recursos para planos pagos |
| LEG-13 | [13 — Matriz de permissões do Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | Admin único, MFA, direitos por módulo e franquias |
| LEG-15 | [15 — IN-117, destino CRM por site](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md) | Escolha explícita de organização nova/existente; novo Básico por contrato elegível mesmo CNPJ |
| LEG-05 | [05 — Agentes de IA](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/05-agentes-de-ia.md) | Modelo histórico de consumo; há regra sobre saldo comprado a reconciliar com decisões posteriores |
| IN-118 | [Central de Backup e Portabilidade](IN-118-central-backup-portabilidade-dados.md) | Export/import/backup/restore/migração autoatendida e gratuita por operação em todos os planos |
| IN-119 | [Ciclo de cortesia](IN-119-gestao-cortesia-crm-basico.md) | Benefício por vínculo elegível e sua transição ao término |
| IN-120 | [Fim da cortesia e continuidade](IN-120-encerramento-cortesia-continuidade-conta.md) | Cancelamento último site, 30 dias e acesso restrito; retenção após cancelamento do próprio CRM |
| IN-121 | [Notificações e reativação](IN-121-notificacoes-reativacao-automatica-cortesia.md) | Avisos 0/15/25/29/30, reativação com direito válido |
| IN-122 | [Múltiplos sites e contas](IN-122-multiplos-sites-contas-crm-cortesias.md) | Uma cortesia por organização; novos sites podem sustentar novas organizações ou uma existente |
| IN-123 | [Painel de integração Portal↔CRM](IN-123-painel-integracoes-sites-crm.md) | Estados de vínculo e benefício apresentados ao cliente |
| COM-08 | [Site inadimplente e benefício](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md) | 7 dias de tolerância do site, suspensão condicional do CRM cortesia |
| COM-09 | [Transição por tipo de cancelamento](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md) | 30 dias funcionando em cancelamento comum, 30 dias restrito após cancelamento por inadimplência |
| PORTAL-D02 | [Serviços adicionais Portal](PORTAL-D02-pagamento-servicos-adicionais.md) | Parcelamento apenas no cartão para extras do Portal; não confundir com mensalidade CRM |
| PORTAL-07 | [Vencimentos dos sites](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md) | Calendário do Portal, sem transpor automaticamente para cobrança CRM |
| INT-P0 | [Painel de pendências Integrações](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) | Contratos de operações e fatos oficiais ainda abertos |

As referências históricas indicam decisões funcionais, porém a recuperação da **proposta e do aceite originais completos** de todas as alterações ainda não está certificada. A referência a decisão conversacional posterior **não equivale a transcrição literal verificada no GitHub**.

## 2. Quadro comercial de planejamento (sem preço final homologado)

| Plano | Valor mensal atual de planejamento | Franquia base de arquivos/anexos aprovada | Assentos para revisão | Observação |
|---|---:|---:|---|---|
| **Básico** | **R$ 69,00** | **1 GB** | **1 autenticado aprovado** | Plano público real; cortesia quando elegível por site |
| **Essencial** | **R$ 89,00** | **5 GB** | 2 como referência | Preço de planejamento, matriz completa de recursos ainda revisável |
| **Profissional** | R$ 149,00 (referência) | **20 GB** | 5 como referência | Preço/assentos pagos a ratificar na revisão conjunta |
| **Empresarial** | R$ 299,00 (referência) | **50 GB** | 10 como referência | Preço/assentos pagos a ratificar na revisão conjunta |

**Todos os valores e recursos incluídos serão revisados juntos ao final do planejamento.** O quadro não substitui nem reabre o aceite do Básico/Essencial; números históricos R$ 59/79 são anteriores e superados. Franquia comercial conta **arquivos/anexos**, excluindo dados estruturados em todos os planos. Limite global por arquivo **5 MB**.

**Regras transversais já documentadas:** exibir armazenamento usado/disponível; avisos internos ao chegar a 80% e 95%; a 100% bloquear **somente novos uploads de anexos**, sem bloquear operações de dados estruturados ou apagar arquivos existentes. Armazenamento adicional, retenção técnica e temporários devem seguir suas aprovações especializadas, sem contratação automática.

## 3. Regras funcionais identificadas nas fontes

| ID de cotejo — não novo requisito | Área | Regra funcional localizada | Fontes | Condição de fechamento |
|---|---|---|---|---|
| COM-R01 | Quatro planos do CRM | Básico público, Essencial, Profissional e Empresarial; mensalidade fixa por empresa com assentos incluídos e possibilidade de extras autorizados | LEG-02; IN-119 | Preços/assentos/limites definitivos revistos conjuntamente no fim do planejamento |
| COM-R02 | Preços de referência atuais | **Básico R$ 69/mês**, **Essencial R$ 89/mês**; Profissional **R$ 149/mês** e Empresarial **R$ 299/mês** são **referências**, não tabela final homologada | LEG-02; LEG-08; LEG-13 | Substituem R$ 59/79 anteriores no planejamento, mas todos preços/benefícios serão revisados no fechamento |
| COM-R03 | Trial independente | 14 dias sem cartão, CNPJ obrigatório, um trial por CNPJ, OTP de e-mail+telefone, sem prorrogação; bloqueio operacional ao expirar e guarda de 60 dias conforme regras | LEG-02 | Separar 'um trial por CNPJ' de múltiplas organizações Básico elegíveis por novos contratos de sites |
| COM-R04 | Quotas trial | 2 usuários, 20 leads, 2 funis, 2 automações, 20 MB, importação CSV permitida, API pública desligada, IA 10 análises Prospector + 10 solicitações Comercial | LEG-02 | Distinguir limite de trial do Básico público/cortesia; quotas IA não são cobrança automática |
| COM-R05 | Mudança de plano | Upgrade com pró-rata sobre ciclo remanescente após pagamento, downgrade no ciclo seguinte com recursos excedentes preservados/restritos; cancelamento voluntário autoatendido | LEG-02 | Faturamento idempotente, direitos de armazenamento, recursos excedentes e transição de benefício precisam contratos físicos |
| COM-R06 | Inadimplência do CRM pago | Tolerância de 7 dias corridos após vencimento; suspensão depois; cancelamento aos 30 dias de atraso; retenção geral de 120 dias após cancelamento da assinatura | LEG-02 | Não confundir atraso do CRM com dívida do site ou expiração do trial |
| COM-R07 | Pagamento do CRM | Cartão recorrente e Pix Automático preferenciais; Pix avulso e boleto previstos; troca autorizada de meio para próximos ciclos; evitar cobrança duplicada | LEG-02 | PSP, estados de mandato, conciliação, NFS-e, estornos, créditos, métodos efetivamente disponíveis e UX pendentes |
| COM-R08 | Franquias de arquivos | Básico 1 GB, Essencial 5 GB, Profissional 20 GB, Empresarial 50 GB; só anexos/arquivos, não dados estruturados; limite de 5 MB por arquivo mantido | LEG-02; LEG-13 | Versões, armazenamento extra, temporários e backups têm regras próprias; não recalcular quota de lead |
| COM-R09 | Uso e bloqueio de anexos | Avisar internamente em 80% e 95%; em 100% bloquear **somente novos uploads de anexos**, preservar arquivos existentes, leads, contatos, históricos e portabilidade | LEG-02; LEG-13; IN-118 | Sem compra/upgrade automático por excedente ou falha técnica |
| COM-R10 | CRM Básico público e cortesia | Básico tem preço de tabela; cortesia é **benefício condicional** de um contrato de site elegível, não freemium perpétuo ou nova fatura de R$ 0; CRM pago é independente | LEG-02; LEG-08; IN-119 | Configuração serviço/campanha on/off, entitlement e vínculo financeiro oficial |
| COM-R11 | Uma vaga Básico | A organização Básico tem 1 usuário autenticado, Administrador da Empresa com MFA; profissional agendável é quota diferente; agenda Básico interna/manual conforme recursos aprovados | LEG-08; LEG-13 | Não herdar permissões dos planos superiores; inclusão de segundo login exige plano/capacidade elegível e aceite |
| COM-R12 | Novo site e novo destino | Escolha expressa entre **organização CRM autorizada existente** e **nova organização segregada** incluída pelo novo site elegível, inclusive mesmo CNPJ; mesmo login pode alternar empresas com direito | LEG-15; IN-122 | Contrato real por site, titularidade, prevenção de duplicidade de evento; múltiplos sites em uma conta não somam franquias |
| COM-R13 | Alteração posterior de vínculo | Troca do CRM de destino mediante chamado autenticado e operação técnica gratuita da Imagine; migração de dados históricos entre contas é do cliente via autoatendimento protegido | LEG-15; IN-118; IN-122; IN-123 | Revalidar origem/destino, in-flight leads e proveniência, sem migrar cadastros/segredos por simples mudança |
| COM-R14 | Manutenção do benefício | Enquanto existir outro site ativo e elegível sustentando a mesma conta, não encerrar cortesia; conta CRM paga não pode ser bloqueada por inadimplência/cancelamento do site | IN-119; IN-122; COM-08; COM-09 | Diferenciar contrato, site, organização, serviço/campanha e estado de entitlement |
| COM-R15 | Cancelamento comum último site | 30 dias de transição do Básico com **operação normal**, avisos dia 0/15/25/29/30; após o prazo, sem site elegível nem CRM pago, suspender operação normal sem cobrança automática | IN-119; IN-120; IN-121; COM-09 | Portabilidade/contratação paga com acesso autenticado; começo do prazo no cancelamento formal |
| COM-R16 | Inadimplência do site e cortesia | Site suspenso após regra própria de tolerância de 7 dias: se era o único suporte do benefício, suspender acesso operacional do Básico; preservar pagamento CRM e outras cortesias | COM-08; IN-119; IN-122 | Não confundir suspensão temporária com cancelamento e início de 30 dias |
| COM-R17 | Cancelamento por inadimplência último site | 30 dias de transição desde cancelamento **formal**, mas **CRM cortesia permanece restrito** desde a suspensão anterior; permitir exportação/contratação paga; não liberar operação plena só por cancelar | COM-09; IN-120; IN-121 | Após transição, restrição continua sem exclusão nem cobrança automáticas |
| COM-R18 | Retenção e reativação | 120 dias da política geral são contados após **cancelamento formal da própria conta CRM**, não da simples suspensão ou cancelamento do site; reativação exige direito vigente | IN-120; COM-09; LEG-02 | Cotejar texto anterior IN-092 com cronologia mais nova; requisitos legais e estados reais pendentes |
| COM-R19 | Portabilidade universal | Central IN-118 fornece CSV/XLSX, backup completo, importação/restauração compatível e migração autorizada nos quatro planos; sem taxa adicional por operação essencial | IN-118; IN-122 | Não confundir backup, exportação delimitada, quota de anexos ou operação de vínculo |
| COM-R20 | Limites transparentes e aceites | Ao exceder quota de plano, informar condição e opção legítima de upgrade/extra apenas quando resolver; nenhum upgrade ou compra de crédito por tentativa automática | LEG-02; LEG-13 | Direitos/entitlements e ofertas são por plano/empresa/usuário, não só por interface |

## 4. Regimes jurídicos/operacionais distintos que NÃO podem ser fundidos

| Situação | Direito ou operação durante a situação | Marco de prazo documentado | O que NÃO acontece automaticamente |
|---|---|---|---|
| **Trial CRM** | Acesso durante trial 14 dias; expira e bloqueia operação normal | Retenção própria de **60 dias após expiração** | Novo trial por contrato de site ou conversão para CRM pago sem aceite |
| **Assinatura CRM paga ativa** | Plano e franquias da empresa conforme contratação | Ciclo mensal; upgrade pró-rata, downgrade próximo ciclo | Bloqueio por inadimplência de site independente |
| **CRM pago em atraso** | Tolerância de **7 dias corridos**; após isso suspensão, e cancelamento aos **30 dias de atraso** | Retenção **120 dias após cancelamento** da assinatura, conforme política | Apagar dados na suspensão ou considerar quitação por mero HTTP |
| **Básico por site elegível ativo** | Recursos do Básico enquanto benefício sustentado por contrato site/campanha válido | Por contrato de site elegível, sem fatura CRM paga | Somar franquias por ter vários sites na mesma organização |
| **Site suspenso por dívida, único sustentador** | **Básico operacional restrito**; permitir regularização, contratação paga e portabilidade conforme política | Não é por si cancelamento; não inicia 30 dias de transição | Bloquear outra organização sustentada por site ativo, cobrar CRM, apagar dados |
| **Cancelamento comum último site elegível** | **30 dias de operação normal do Básico** e avisos | Transição começa no **cancelamento formal** do último vínculo | Iniciar retenção definitiva 120 dias do CRM só porque site cancelou |
| **Cancelamento por inadimplência último site elegível** | **30 dias de transição com Básico operacional restrito**, exportação e contratação paga disponíveis | Transição a partir do **cancelamento formal** após suspensão por dívida | Restabelecer operação normal durante transição sem elegibilidade real |
| **Básico sem sustentação após transição** | Área restrita autenticada, exportação/portabilidade e contratação voluntária | Política de guarda CRM após **cancelamento formal próprio**, separada da transição | Cobrança automática ou exclusão imediata por fim de cortesia |
| **Conta CRM formalmente cancelada** | Restabelecimento dentro de limites legais/contratuais e exportação conforme política | **120 dias após cancelamento formal do CRM**, não do site | Usar o prazo de 60 dias de trial ou 18 dias da fila de leads |
| **Fila técnica de leads dos sites** | Reenvio durável/recuperação segundo contrato separado | **3 dias aguardando + 15 de falha = 18 dias** quando aplicável | Transformar 18 dias em retenção de contatos/cadastros CRM |

**Nota:** o regime de 30 dias em cancelamentos de sites foi expressamente desdobrado pela decisão posterior COM-09. Não inferir que a regra do CRM pago de 30 dias de atraso é a mesma regra de cancelamento de sites. **O marco de retenção em texto histórico IN-092 precisa ser conciliado** com IN-120 e COM-09, não eliminado sem rastreabilidade.

## 5. Pontos de precedência, versões antigas e decisões ainda abertas

| ID | Diferença ou risco identificado | Diretriz conservadora de revisão | Fontes | Situação |
|---|---|---|---|---|
| REV02-D01 | Preços históricos do Básico/Essencial | Parágrafos históricos citam R$ 59/79; cabeçalho posterior estabelece R$ 69/89 | Manter 69/89 como referência de planejamento; não reabrir revisão geral agora | LEG-02 | Reconciliação de precedência; não criação de nova decisão |
| REV02-D02 | R$ 149/299 e usuários incluídos pagos | Aparecem como referências, não valores finais fechados em documento mestre | Marcar como hipóteses para revisão conjunta do fim | LEG-02 | Pendente de validação comercial |
| REV02-D03 | Um trial/CNPJ vs várias organizações Básico/CNPJ | Restrições de trial e direito a organização Básico por contrato de site coexistem | Separar evento de trial de elegibilidade do site e exigir prova do novo contrato | LEG-02; LEG-15 | Sem nova exceção gratuita para trial |
| REV02-D04 | Regra antiga de benefício IN-092 × COM-09 | Histórico IN-092 pode sugerir que retenção começa no fim do benefício; IN-120/COM-09 registram marco mais recente no cancelamento formal do CRM | Preservar interpretação expressa mais recente; documentar divergência textual para revisão, sem apagar IN-092 | IN-120; COM-09; histórico LEG | Fonte original IN-092 e precedência ainda precisam cotejo completo |
| REV02-D05 | Cancelamento comum × por inadimplência | Ambos têm 30 dias, mas acesso normal em cancelamento comum e restrito em cancelamento ligado à inadimplência anterior | Conservar ambas transições e causa, sem iniciar prazo na mera suspensão | COM-08/09 | Regras de transição técnica ainda pendentes |
| REV02-D06 | Cobrança site × CRM | Portal cobra site e adicionais; CRM cobra sua assinatura paga; cortesia é benefício contratual, não mensalidade de CRM | Contratos de faturamento e autorização separados; não bloquear CRM pago por dívida do site | LEG-02; IN-119/122 | Contratos de evento/PSP e conciliação |
| REV02-D07 | Desconto 5% via Pix e cartão 3x | Decisão comercial posterior de planejamento na conversa (02/11) define Pix à vista 5% para implantação/compra, Pix Automático 5% nas mensalidades, cartão até 3x na compra sem desconto e cartão recorrente sem desconto; sem Pix parcelado | Registrar como **apontamento de reconciliação pendente de consolidação literal no GitHub**, distingui-lo do histórico de propostas PORTAL (Pix parcelado/20%/4x etc.). **Não** aplicar automaticamente 3x à mensalidade CRM ou extras | Referência conversacional posterior; PORTAL-D02; LEG-02 | Cotejo de origem e matriz por produto ainda pendentes |
| REV02-D08 | Créditos IA comprados | LEG-05 descreve saldo comprado até consumo; há apontamento de decisão posterior de pacote com validade de 90 dias em histórico conversacional | **Não estabelecer prazo nesta REV02** sem cotejar original e tipo de pacote; encaminhar para CRM-13 IA/consumo e revisão comercial | LEG-05; histórico conversacional a recuperar | Pendência P0 se afeta cobrança/entitlement |
| REV02-D09 | Retenção de 60/120 dias | Trial expirado 60 dias; assinatura CRM encerrada 120 dias; fila de lead Site→CRM 18 dias; backup de anexo 30 dias: regimes distintos | Registrar quatro objetos e marcos diversos; não copiar regra universal de retenção | LEG-02; COM-09; IN-118; IN-127/147 | Validação LGPD/categorias e descarte |
| REV02-D10 | Capacidade de armazenamento | Quotas 1/5/20/50 GB e anexos 5MB já aprovados; pacotes extras e custos/regimes de retenção têm documentação própria | Preservar quota base, não usar quota como restrição oculta de leads, revisão de extras comercial/técnica separada | LEG-02; LEG-13 | Aprovação/comportamento final de pacotes e custos |
| REV02-D11 | Plano Básico sem benefício de site | Básico público é contratável sem site, com preço nominal e mesmas regras funcionais relevantes; cortesia depende de site elegível | Separar em entidade de plano, assinatura financeira e vínculo de concessão, sem 'plano grátis fictício' | IN-119; LEG-02 | Esquema de entitlement e estado efetivo |
| REV02-D12 | Direitos e preços por campanha | A cortesia pode ser ativada/desativada por serviço/campanha; contratos antigos e plano pago precisam permanecer com direitos válidos | Não conceder benefício a contrato sem elegibilidade nem retirar de contrato vigente sem regra/aceite | COM-08; IN-123 | Vigência, grandfathering e contrato de mudança |

**Política Pix/cartão:** há apontamento de decisão posterior nos registros conversacionais desta linha de projeto sobre **Pix à vista (5%) em compra/implantação, Pix Automático (5%) em mensalidades, até 3x no cartão para compra sem desconto, cartão recorrente sem desconto e sem Pix parcelado**. Neste documento, esse apontamento é tratado **como regra a consolidar e conferir na documentação mestre por produto**, jamais como autorização para cobrar, fazer promoção, prometer desconto em cartão ou alterar proposta histórica sem cotejo. O parcelamento de serviços adicionais do Portal tem **contrato específico** e não deve herdar preços/limites do CRM.

## 6. Doze pendências de especificação e decisões para fechar

| ID | Bloqueio ou definição | Evidência documental necessária | Responsável funcional a envolver, não pessoa nomeada | Prioridade proposta |
|---|---|---|---|---|
| CRM-REV02-P01 | Matriz de produto × recursos/quotas | Básico vs Essencial/Profissional/Empresarial, assentos, automações, agenda, API, WhatsApp, IA, armazenamento/adicionais | Comercial de CRM + proprietários ATD/VEN/AGD/IA | Revisão conjunta ao fim; P0 se bloqueia v1 |
| CRM-REV02-P02 | Estados formais das assinaturas CRM | Trial, ativa paga, benefício, transição, suspensa por cobrança, cancelada, retenção, reativada e precedência de eventos | FIN/CON CRM + Segurança | P0 |
| CRM-REV02-P03 | Estados de contrato site e benefício | Site ativo/tolerância/suspenso/cancelado; serviço/campanha on/off, múltiplos vínculos e organização | Portal + CRM + IN-119/122 | P0 |
| CRM-REV02-P04 | PSP, Pix Automático e fiscal | Provedores, autorização de mandato, estorno, captura, conciliação, recibos, NFS-e, impostos e erros | FIN/CON + jurídico/fiscal | P0 |
| CRM-REV02-P05 | Descontos por produto/pagamento | Formalizar 5% Pix à vista/Pix Automático, cartão 3x só em compra, limites de campanhas, cumulatividade, arredondamento e condições contratuais | Comercial/FIN CRM e Portal por produto | P0/P1 segundo impacto |
| CRM-REV02-P06 | Elegibilidade e provisão idempotente | Criar organização Básico só por contrato real elegível e escolha do cliente; separar trial único por CNPJ | Portal + CRM/Segurança + IN-122 | P0 |
| CRM-REV02-P07 | Inadimplência e precedência de eventos | Confirmar recebimento, 7 dias tolerância, 30 dias de atraso CRM, duas transições cortesia e exceção por outro site/CRM pago | FIN/CON Portal + CRM + Integrações | P0 |
| CRM-REV02-P08 | Retenção/exportação e descarte | Tratar trial 60 dias, CRM cancelado 120 dias, dados com obrigações legais e acesso restrito, sem exclusão em suspensão | LGPD/Segurança + IN-118 | P0 |
| CRM-REV02-P09 | Pró-rata/downgrade/upgrade e sobrecapacidade | Calcular exatamente datas, crédito, estorno, limites, status de recursos excedentes, cancelamento de adicionais | FIN/CON CRM + donos de módulos | P0 |
| CRM-REV02-P10 | Direitos por plano e contexto | RBAC mais entitlement por organização e usuário; avisos de limite sem ativação indevida; trial API off; Básico 1 admin | CRM-REV01 + Segurança + módulos | P0 |
| CRM-REV02-P11 | Cotas e consumo de IA | Créditos por empresa, validade de pacote, tarifa por operação, recarga automática opcional, segurança e custo | CRM-13 IA + FIN/CON | P0 antes faturamento de IA |
| CRM-REV02-P12 | Fonte primária das decisões comerciais posteriores | Extrair proposta+aceite originais de 02/11 sobre Pix/cartão e versões de preços; reconciliar IN-092 histórico | Arquitetura documental + Comercial | P0-DOC parcial |

Os 12 itens são **questões de revisão**, não 12 novas funcionalidades ou aprovações comerciais. A prioridade indicada é sugestão de análise e poderá mudar no controle global de dependências.

## 7. Critérios de revisão negativos para homologação futura (SEM TESTES)

1. Uma conta que já usou trial tenta novo trial em outro ambiente com o mesmo CNPJ, mas há contrato de outro site: impedir novo trial e permitir apenas a **nova organização Básico elegível** quando contratualmente autorizada.
2. Pagamento de site em duplicidade solicita duas organizações: provisionamento idempotente não cria ambiente sem contrato adicional verdadeiro.
3. Um site entre três associados à mesma organização é suspenso: não revogar cortesia sustentada por outro vínculo elegível.
4. Site único suspenso por dívida: restringir Básico, preservar dados/exportação e não iniciar transição de 30 dias antes de cancelamento formal.
5. Site único formalmente cancelado por inadimplência: durante 30 dias manter **acesso restrito**, não funcionamento normal.
6. Cancelamento comum último site: durante 30 dias manter **operação normal**, avisos e contratação expressa.
7. Assinatura CRM paga independente continua ativa enquanto site em atraso: não bloquear nem converter para cortesia automaticamente.
8. Downgrade com dados/arquivos acima do limite destino: preservar conteúdo preexistente e restringir novas operações excedentes, sem apagar registros.
9. Quota de anexos 100%: negar novo anexo, mas permitir lead, dados estruturados e exportação autorizada.
10. Pix/cartão/mandato com resultado inconclusivo: reconciliar antes de nova captura; não criar fatura paga nem acesso com base em ack técnico.
11. Usuário Básico tenta incluir outro usuário ou chamar API pública sem direito: negado com explicação do plano, nunca exigir novo pagamento automaticamente.
12. Cancelamento de site aciona suposta retenção de 120 dias do CRM: preservar separação do evento **cancelamento formal da própria conta CRM**, com consulta/exportação segura.
13. Agência Imagine muda vinculação gratuita para outra conta CRM: não migrar históricos nem credenciais automaticamente; preservar in-flight leads e direito de origem/destino.
14. Pacote de IA adquirido é consumido perto de prazo contestado: aplicar somente regra de validade confirmada por sua versão e decisão, **não escolher 90 dias vs até consumo por inferência**.

Os 14 casos são **cenários documentais candidatos** a futuros critérios de aceite, não testes executados, nem novos números HT.

## 8. Decisão posterior: gratuidade administrativa de qualquer plano (03/11/2026)

- [CRM-DEC — Gratuidade administrativa em qualquer plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md): **nova decisão funcional aprovada expressamente**. O Administrador Geral Imagine pode conceder a **uma organização específica** qualquer plano público do CRM, inclusive Essencial, Profissional e Empresarial, **sem mensalidade base enquanto a concessão for válida**, sem exigir contrato de site e sem utilizar trial.
- Esta modalidade **não altera preços de tabela**, não estende privilégios a outra organização do mesmo CNPJ e **não substitui** a cortesia Básico por contrato elegível da IN-119. O plano concedido mantém seus recursos e franquias próprios; adicionais e consumo de terceiros **não estão automaticamente cobertos**.
- **Vigência administrativa aprovada em decisão posterior:** [prazo determinado **ou** indeterminado](CRM-DEC-gratuidade-vigencia-modalidades.md). **Não cobrar automaticamente** quando terminar. Ainda pendem prazo padrão, início, avisos, prorrogação, concessão a cliente pagante, revogação/transição, alçadas, auditoria, benefícios concorrentes, extras, NFS-e e conciliação financeira (GRAT-P01/P04 parciais; P02/P03/P05..08 abertos).
- O aceite anterior de CRM-REV-02 permanece como base documental; a nova decisão está aprovada em seu núcleo, **não** em parâmetros e fluxo operacional final.

## 9. Complemento aprovado: gratuidade separada de adicionais específicos (03/11/2026)

- A [CRM-DEC — Gratuidade administrativa de adicionais](CRM-DEC-gratuidade-administrativa-adicionais-especificos.md) registra **aprovação funcional** para o Administrador Geral Imagine conceder **usuários adicionais, capacidade de armazenamento e pacotes de créditos IA**, ou outro adicional elegível a homologar, **sem preço ao cliente pela unidade específica concedida**.
- A concessão exige **ato administrativo separado da gratuidade do plano** e vale para a organização escolhida; **não** concede todos os extras automaticamente, não modifica tabela pública, nem aprova quantidade, prazo, preços, custo de provedor ou benefícios ilimitados.
- A expiração/revogação **não ativa automaticamente cobrança recorrente**; qualquer continuidade paga exige aceite expresso. **GRAT-P05 parcialmente resolvido** e GRAT-EX-P01..08 abertos para contratos de FIN/CON e regras de entitlements.

## 10. Aceite documental contextual e limites mantidos

**CRM-REV-02 foi aceita como base documental de reconciliação funcional** no [CRM-DEC correspondente](CRM-DEC-aprovacao-REV-02-base.md), reconhecendo COM-R01..20, os regimes distintos de trial/assinatura/site e o registro de divergências REV02-D01..12. O aceite **não homologou** preços finais, pacotes de IA, alçadas de cobrança, provedores, prazos físicos, campanhas ou contratos de API.

A [CRM-REV-03 — Leads, contatos, campos personalizados, histórico e portabilidade](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) foi **redigida em minuta documental para revisão**. As decisões comerciais finais permanecem agrupadas para revisão no fechamento global; pendências CRM-REV02-P01..12 seguem por impacto.

**Estado de prontidão:** CRM-REV-02 e CRM-REV-01 estão **aceitas como bases documentais**; CRM-REV-03 está **em minuta**, e o CRM global segue com P0 de identidade, negócio, contratos, segurança, cobranças e integração ainda abertos. **Não iniciar programação nem testar integrações reais**.
