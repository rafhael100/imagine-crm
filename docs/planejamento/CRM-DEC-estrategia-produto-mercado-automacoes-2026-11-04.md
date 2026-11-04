# CRM-DEC — Direção estratégica de produto e mercado: automação simples, motor nativo e agentes Imagine

**Data:** 04/11/2026.  
**Manifestação:** “vamos fazer então dessa forma”, em resposta à proposta de posicionamento do CRM Imagine e diferenciação por experiência, automações e agentes.  
**Estado:** **DIREÇÃO ESTRATÉGICA APROVADA PARA O PLANEJAMENTO**. Não equivale a aprovação individual de catálogo, elegibilidade comercial, precificação final, tecnologia concreta, programação ou implantação.

## 1. Objetivo e proposta de valor

Construir um **CRM acessível e de rápida adoção para pequenas e médias empresas**, com foco no resultado prático em vendas e atendimento: organizar oportunidades, reduzir trabalho manual e recomendar ou realizar ações autorizadas, sem exigir competência técnica para iniciar. Diferenciar-se por **experiência simples + automações prontas úteis + inteligência contextual + ecossistema Imagine**, não por prometer o maior inventário de recursos ou preço mínimo.

O slogan “Sua empresa vende. A Imagine trabalha por você” é **proposta de comunicação a testar**, não identidade/mensagem publicitária homologada.

## 2. Dois caminhos de automação, sem motores duplicados

1. **Uso imediato guiado:** biblioteca de modelos prontos, parametrizações autorizadas e onboarding simples; o cliente contrata e ativa recursos elegíveis, sem precisar montar lógica complexa.
2. **Personalização progressiva:** editores visuais tipados de Fluxos, Gatilhos, Condições e Ações, sujeitos aos direitos de cada plano, contratos, versionamento, simulação e supervisão. Possibilidade futura de descrever intenções em linguagem natural para a IA **propor** regras, sujeitas a validação e aprovação antes da ativação. Não conceder autonomia irrestrita nem publicação silenciosa.
3. **Execução das automações configuradas por clientes:** no **motor nativo do CRM**, reutilizando Eventos, Fluxos, Condições, Ações, Filas, Datas/Prazos, Central de Aprovações e Auditoria já planejados. **Não desenvolver um segundo motor paralelo**.
4. **n8n:** reservado a workflows e orquestrações desenvolvidos e controlados pela Imagine nos usos permitidos pela licença; não atuar como editor oculto nem como mecanismo obrigatório de execução dos fluxos configuráveis pelos clientes.
5. **Agentes técnicos e funcionais:** governança central do ecossistema com isolamento por produto, empresa, dados, recursos e criticidade; preservar a **VPS própria dos agentes técnicos separada da VPS n8n**. Nem todos os agentes dos produtos residem no servidor n8n.
6. **Licenciamento:** verificar e obter enquadramento formal para usos limítrofes do n8n antes de produção comercial. **Não assumir gratuidade universal** por ser backend invisível nem contratar licença agora.

Documentos de precedência:
- [Decisão já aprovada — motor próprio e limite do n8n](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md).
- [Execução segura do motor próprio — base aceita, detalhes pendentes](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md).
- [Arquitetura central de agentes do ecossistema](CRM-DEC-arquitetura-central-agentes-n8n-ecossistema-imagine-2026-11-03.md).
- [Matriz de elegibilidade por plano — base, não autorização comercial](CRM-AUT-MAT01-matriz-elegibilidade-automacoes-por-plano-v0.1.md).
- [Biblioteca de modelos e editores — base, não catálogo final](CRM-AUT-LIB01-biblioteca-modelos-basico-editores-por-plano-v0.1.md).

## 3. Prioridades da experiência de produto

**Prioridade de planejamento para o primeiro valor percebido**:
- Entrada facilitada: criação de conta, configuração inicial, importação/portabilidade e primeira automação guiada; acesso às funcionalidades dependerá do plano.
- Operação essencial estável: leads, tarefas, atendimento, alertas comerciais e distribuição conforme os módulos e direitos oficialmente aprovados.
- Modelos prontos: começar pelos casos simples de maior utilidade e baixo custo variável, com execução auditável e recuperável.
- IA consultiva/assistiva: sugestões de próxima ação, oportunidades esquecidas e suporte ao trabalho humano conforme disponibilidade do módulo, créditos, consentimentos e limites de autonomia.
- Supervisão clara: estados oficiais, aprovações de risco, rastreabilidade, prevenção de duplicidade e isolamento entre tenants.

**Evolução condicionada ao planejamento e à validação de demanda**: maior amplitude de construtores visuais, geração assistida por linguagem natural, agentes de processos mais complexos e mais conectores. Não é autorização automática para disponibilizar todos os recursos no primeiro lançamento.

## 4. Estratégia comercial e distribuição

- **Público inicial recomendado:** PMEs com captação de leads, atendimento e rotina comercial recorrente. Seleção do primeiro segmento específico permanece **em aberto**; não lançar em todos os nichos simultaneamente.
- **Porta de entrada pelo Imagine Sites:** cliente que contrata um site elegível pode receber **benefício contratual CRM Básico**, preservando preço público, ciclo de vida e direitos do Básico, integração apenas à conta CRM vinculada e autorizada e independência dos produtos.
- **Expansão:** clientes podem contratar e evoluir no CRM e contratar Imagine Performance AI ou outros serviços separadamente, sem venda casada, conversão compulsória ou compartilhamento indevido de dados.
- **Canais candidatos:** demonstrações práticas, indicações e parcerias de agências. Política de comissões, campanhas, verba e metas comerciais dependem de aprovação própria.
- **Preços públicos de referência já definidos:** Básico R$69/mês; Essencial R$89/mês, por empresa, sujeitos à revisão financeira final. Não alterar nesta decisão os preços, os limites/slots, os direitos dos planos ou o modelo de créditos de IA.
- **Margem e controle de custo:** considerar suporte, infraestrutura, filas, licenças, APIs, canais pagos, IA por créditos, tributos, recuperação de falhas e aquisição de clientes. Nenhum modelo deve prometer IA, WhatsApp, serviços externos ou execução ilimitados por presunção.

## 5. Validação de produto e qualidade

Antes de escalar comercialmente, executar **pilotos somente após o planejamento completo, homologação, implantação autorizada e condições legais/técnicas satisfeitas**. No desenho dos pilotos, propor métricas:
- Tempo até a primeira configuração/automação útil.
- Percentual de empresas que ativam e de fato utilizam automações elegíveis.
- Tarefas manuais evitadas, taxa de acompanhamento de leads e resultados observáveis (sem atribuição causal indevida).
- Custo variável e suporte por empresa, erros/duplicidades, retenção e cancelamentos.

Estas são **métricas a definir e validar**, não metas numéricas aprovadas nem garantias de resultado. O “primeiro valor no primeiro dia” é aspiração de experiência, não SLA contratado.

## 6. Limites da aprovação e próximos trabalhos documentais

**Aprovado nesta interação:** direção de crescimento por facilidade e automação; coexistência de modelos prontos e personalização progressiva; estratégia de entrada e expansão via ecossistema; manutenção da separação arquitetural já decidida entre motor nativo e n8n; disciplina de custos e crescimento por fases.

**Permanece pendente:** homologação comercial exata por plano e preço final; escolha de segmentos e de piloto; marca/slogan final; catálogo e contratos definitivos; limites, quotas e metas; análise formal de licenciamento do n8n; gates P0 CORE/INT/AUT/ARQ, capacidade, segurança, LGPD, orçamento e testes controlados.

**Proibição expressa:** este aceite **não autoriza programação, instalação, contratação, migração, criação de infraestrutura, alteração de serviços reais ou gasto**. Toda implementação depende da aprovação global do planejamento, das decisões P0 e de autorização específica.

**Precedência:** se houver divergência com contratos técnicos, preços, direitos ou aprovações normativas vigentes, prevalecem esses documentos específicos até nova decisão explícita.