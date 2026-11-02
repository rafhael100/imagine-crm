# PORTAL-COM-11 — Proposta de gestão de produtos, serviços adicionais e automações

**Status:** PROPOSTA PARA REVISÃO; **não aprovada em seus detalhes funcionais**. A fala do responsável em 02/11/2026 indicou interesse em **gerenciar serviços, contratar novos serviços e automações para otimizar a operação**, com trecho final da transcrição pouco claro sobre “produto”, “somar” e “pasta”. **Não inferir aprovação de pacote, pasta, cobrança consolidada ou regras de produto a partir desse trecho incompleto.**
**Fase:** somente planejamento e documentação; sem código, lançamento de catálogo, contratação real, execução automática, publicação de valores ou cobrança.
**Escopo:** Portal Imagine, área do cliente e administração de produtos/serviços ligados a Onepage, Landing Page, eventuais outros produtos Imagine e integrações elegíveis; automações CRM pagas obedecem às regras próprias do CRM Imagine.
**Referências:** [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md), [PORTAL-D01 — aceite de serviços e orçamentos](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-D02 — cobrança e início de adicionais](PORTAL-D02-pagamento-servicos-adicionais.md), [PORTAL-D04 — mensalidades](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-COM-03 — campanha e preços](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md), [PORTAL-COM-04 — precificação por produto](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md), [IN-123 — visão dos projetos e CRM](IN-123-painel-integracoes-sites-crm.md).

## 1. Leitura segura da solicitação

**Pedido compreendido:** o Portal precisa evoluir de contratação inicial de site para uma **central de gestão contínua de produtos e serviços**, permitindo ao cliente descobrir, solicitar e contratar **novos serviços**, funcionalidades extras e **automações** úteis a seus projetos; administração Imagine deve operar, medir e otimizar esses serviços.

**Pontos não compreendidos com precisão:** a frase final mencionou aparentemente “produto”, “somar” e “pasta”, mas não ficou claro se o objetivo seria **somar itens numa fatura única**, **organizar por pasta/projeto**, **gerenciar um conjunto de produtos**, ou outro assunto. Essas opções não são decisões e precisam de confirmação do responsável, sem presumir.

## 2. Arquitetura funcional proposta: Central de Produtos e Serviços

### 2.1 Catálogo unificado (proposta)

- **Produto principal:** Onepage, Landing Page e futuros produtos; cadastrado separadamente de **serviços adicionais** e **automações opcionais**.
- **Serviço adicional avulso:** mudanças estruturais, novas páginas/seções, integrações específicas, configuração de domínio, ajustes fora do escopo, consultoria e outras demandas que se enquadrarem em escopo comercial. **Não cobrar por operações já incluídas no plano**; catálogo/escopo a homologar.
- **Serviço recorrente ou módulo:** manutenção premium, monitoramento adicional, plano de suporte especial, integrações gerenciadas ou recursos adicionais quando tecnicamente aplicáveis; faturamento recorrente e cancelamento sujeitos a decisão própria.
- **Automação opcional contratável:** processos, fluxos n8n, notificações, integrações e operações de agentes de IA para o projeto, quando possuírem escopo, fornecedor, limites e política de consentimento próprios. Distinguir explicitamente **automações internas da Imagine**, não vendidas separadamente, das **automações comercializadas ao cliente** e das **automações nativas/contratadas dentro do CRM Imagine**, para evitar dupla cobrança.
- **Pacotes/combos opcionais:** combinação de produtos e serviços sob oferta; **não aprovado** criar novo combo, desconto ou adesão automática. É apenas possibilidade de catálogo.

### 2.2 Área do cliente e relacionamento com projetos (proposta)

- Painel **Meus produtos e serviços**, por cliente e por projeto/site, mostrando serviços ativos, pendentes, em implantação, suspensos, cancelados e disponíveis para contratação.
- **Adicionar serviço**, **pedir orçamento** ou **ativar automação elegível** a partir do projeto correto; mostrar pré-requisitos (produto existente, plano compatível, conta CRM vinculada), escopo, preço, tipo de cobrança, limite, prazo e forma de entrega.
- **Novos produtos independentes** podem ser contratados pelo mesmo cliente, com escolha de novo projeto e vínculo CRM existente ou novo, conforme IN-122/123.
- **Central de solicitações** para execução e suporte, com status, materiais, histórico, aprovações e teste de funcionamento. Contratações de mudanças estruturais continuam sujeitas às regras existentes de orçamento/aceite.
- Considerar **agrupamento visual por projeto** e **visão consolidada financeira por cliente** como propostas, **sem assumir faturamento num único boleto/fatura, mesmo CNPJ ou partilha comercial**, até aprovação explícita.

### 2.3 Painel administrativo (proposta)

- CRUD/versionamento de produtos, serviços, automações, pacotes e complementos, com classificação: **implantação única / serviço avulso / mensalidade recorrente / cobrança por uso / orçamento personalizado** (modelos de cobrança por uso ainda NÃO aprovados).
- Escopo comercial e técnico, elegibilidade por plano/produto/campanha, dependências, limites, modalidade de entrega, SLA, critérios de aceitação, equipe/agentes autorizados, custo e margem.
- Preço de tabela e promoções por serviço/campanha, sem aplicar descontos de Pix de implantação/mensalidade sobre adicionais/automações por inferência; a política de adicionais PORTAL-D02 é específica e deve ser preservada.
- Gestão de pedidos, aceite, pagamento, fila de execução, aprovações humanas, garantia de reversão, reconciliação financeira, cancelamento e histórico. **Sem executar alterações críticas por aprovação tácita**.
- Dashboard de serviços e automações ativos, consumo/custos, incidentes, versões, alertas, gargalos, indicadores de uso e solicitações; controle de terceiros/assinaturas, sempre que autorizado contratualmente.

## 3. Jornada comercial sugerida e separação de responsabilidade

1. Cliente entra no Portal e seleciona projeto/empresa corretos.
2. Explora catálogo **somente de itens elegíveis**, com escopo claro, preço unitário ou solicitação de orçamento; evitar itens indevidamente oferecidos como pagos quando já incluídos no contrato.
3. Seleciona novo serviço ou automação e escolhe vínculo com projeto/site/CRM autorizado. Simula custos de **implantação, assinatura recorrente, pacotes e uso** quando cada modalidade for efetivamente aprovada; termos explícitos.
4. Recebe orçamento ou checkout com **aceite eletrônico de versão**, seguindo PORTAL-D01.
5. **Serviços adicionais avulsos já aprovados**: só liberar execução após aceite e confirmação integral do pagamento à vista ou primeira parcela do cartão, conforme PORTAL-D02; **até R$ 270 à vista, acima disso à vista ou até 3x cartão sem juros**. Pix parcelado não é modalidade aprovada. Outros tipos de produto/recorrência exigem política própria.
6. Pedido elegível é encaminhado à equipe/automação autorizada. **Agentes de IA** podem preparar materiais, conferir pré-requisitos e gerar propostas, mas **não contratar terceiros, gerar custos novos, publicar alterações críticas ou assumir obrigações sem alçada/aceite permitido**.
7. Homologação, teste, aprovação final de entregáveis quando aplicável, ativação, suporte, auditoria e notificação ao cliente.
8. Gestão após venda: suspender/cancelar/alterar serviços conforme contrato, preservar dados e dependências, evitar impactos no site ou CRM pago de outros vínculos e impedir cobranças recorrentes duplicadas.

## 4. Requisitos mínimos de automações opcionais — proposta

- **Catálogo de modelos** por finalidade: notificações, formulários e leads, acompanhamento de tarefas, integrações específicas, rotinas de marketing e fluxos entre serviços. São exemplos, **nenhum modelo comercial aprovado individualmente**.
- **Segurança e privacidade:** definir controlador/operador e permissões de acesso, consentimentos quando necessários, sigilo, segregação por cliente, destino de dados, logs, LGPD e retenção.
- **Ativação por cliente e projeto:** parametrização, teste no ambiente apropriado, controle de versões, limites de execução, detecção de falhas e opção de pausar/reativar sob contrato.
- **Regras de consumo:** se IA, APIs e provedores tiverem custos variáveis, franquias/alertas/bloqueios preventivos precisariam ser definidos; não criar faturas variáveis imprevistas.
- **Orquestração sem conflitos:** automações comerciais do CRM Imagine devem ser contratadas e administradas no CRM nos casos de responsabilidade daquele produto; o Portal pode exibir atalhos, vínculos e status autorizados sem duplicar contratação/cobrança.
- **Automação de produção interna:** agentes de IA que criam ou mantêm sites Imagine são parte da operação da própria Imagine e **não tornam seus serviços faturáveis separadamente por simples uso interno**.

## 5. Relação com decisões já aprovadas

- **Aprovação do orçamento e execução de serviços adicionais:** PORTAL-D01/D02, já existentes; este documento complementa descoberta e gestão, não altera suas condições financeiras.
- **Preços e promoções por serviço:** PORTAL-COM-03/04/05 e outros complementos definem arquitetura para sites, mas **não aprovam novas tarifas de automações** por analogia.
- **Vínculo site–CRM:** IN-119–123 (e COM-08/09) preservam cortesia por elegibilidade, transição e autonomia de assinatura paga; não duplicar tenant, plano, cobrança ou benefício.
- **Backups e domínios:** IN-100/101 no histórico do projeto; mudanças exigem preservação de titularidade/e-mail, pontos de restauração e aprovação humana para riscos.
- **Só planejamento:** não criar integração de produção nem agendar execuções sem aprovação e projeto técnico.

## 6. Pontos para decisões subsequentes

1. Confirmar se a central reúne **somente adicionais ligados a um site** ou **todos os produtos Imagine** de um mesmo cliente.
2. Confirmar o significado da parte final da fala sobre “produto”, “somar” e “pasta”: **agrupamento por projeto/pasta? carrinho unificado? fatura única? outro?** Não presumir.
3. Escolher se automações comercializadas terão **pagamento único, assinatura, cobrança por uso ou pacote**; definir se a administração da automação ocorrerá no Portal ou dentro do CRM conforme responsabilidade.
4. Definir catálogo inicial de itens, preços e limites, alçadas, escopo e responsabilidade por suporte/implantação.
5. Avaliar descontos, pacotes e elegibilidade por campanha com aceite claro, e modo de mostrar produtos ativos/novos.
6. Aprovar se o módulo será especificamente uma **Central de Produtos, Serviços e Automações** no Portal, para então detalhar telas e contratos.

**Conclusão:** proposta registrada para a central de gestão e contratação de serviços e automações. **Não está aprovada como sistema pronto nem como conjunto fechado de funcionalidades**; aguarda confirmação da intenção final e aprovação dos escopos.
