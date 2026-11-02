# IN-153 — Inventário Inteligente de Infraestrutura e Dependências

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (resposta "ok").  
**Etapa:** exclusivamente planejamento e documentação; **não iniciar programação, descoberta ativa em produção, implantação, mudanças nem contratações**.  
**Escopo:** Imagine Sites, CRM Imagine SaaS, Portal Imagine, hospedagem, APIs, filas de leads, automações n8n, bancos de dados, domínios/certificados, integrações e agentes técnicos internos.  
**Referências:** IN-100/IN-101 (domínios e backups), IN-124 a IN-129 (APIs, filas e integrações), IN-130 a IN-132 (incidentes e agentes), IN-137/IN-138 (autonomia e uma aprovação humana), IN-139 a IN-142 (aprendizado, prevenção e capacidade), IN-143 a IN-146 (continuidade e dependências) e IN-150 a IN-152 (segurança, credenciais e vulnerabilidades).

## Objetivo
Manter um **inventário centralizado, atualizado, pesquisável, versionado e auditável de ativos e dependências técnicas**, permitindo que os agentes saibam quais serviços existem, seus responsáveis, ambientes, versões, relações e possíveis impactos antes de diagnosticar, aplicar medidas preventivas, atualizar componentes ou recuperar uma falha.

O inventário deverá ser fonte de contexto técnico, **não fonte irrestrita de permissões nem de credenciais**.

## Capacidades funcionalmente aprovadas

1. **Inventário de servidores e ambientes:** registrar servidores dedicados, VPS, hosts, sistemas operacionais, ambientes (produção, homologação, desenvolvimento), provedores, serviços executados, propriedade e criticidade.
2. **Inventário de aplicações e serviços:** catalogar sites de clientes, APIs principal/contingência, processadores de filas, CRM Imagine SaaS, Portal Imagine, n8n, bases de dados, componentes de autenticação/segredos e monitoramento, com identificadores estáveis.
3. **Controle de versões:** registrar versões do sistema operacional, PHP, Laravel, Node.js, bibliotecas, imagens de containers, n8n, bancos e demais componentes relevantes, com procedência e data da última verificação.
4. **Mapa de dependências:** representar relações entre componentes e serviços; distinguir dependência necessária, alternativa/contingência, origem/destino de dados e dependência compartilhada que possa ser ponto único de falha.
5. **Detecção de alterações:** comparar o estado observado com o inventário aprovado e sinalizar mudanças imprevistas (*configuration drift*), componentes desconhecidos, versões divergentes e relações possivelmente obsoletas.
6. **Análise de impacto:** identificar o alcance potencial de uma indisponibilidade, atualização ou manutenção, incluindo serviços afetados, criticidade, integração de leads e clientes envolvidos, **com base em evidência verificável**.
7. **Atualização automática autorizada:** permitir que coletores/agentes mantenham atualizados fatos observáveis e proponham correções de cadastro, respeitando escopo, permissões, proveniência, confiança e auditoria. Alterações críticas de topologia, autorização e configuração não decorrem automaticamente de uma inferência da IA.

## Modelo funcional dos registros

Cada ativo ou serviço deverá poder registrar, conforme aplicável:
- ID estável, categoria, nome técnico, descrição e estado do ciclo de vida (planejado, provisionado, em operação, em manutenção, desativado);
- ambiente, provedor/localização técnica em nível adequado, serviço proprietário, responsável técnico e equipe;
- criticidade, exposição, indicadores de disponibilidade, dependências upstream/downstream e plano de contingência associado;
- versão observada, versão aprovada/alvo, origem da informação e data/hora da última conferência;
- referência a repositório/artefato de versão, documentação e procedimento de atualização, sem incorporar segredos;
- referência não sensível ao cofre de credenciais autorizado (IN-151), **jamais conteúdo bruto de chaves, tokens, senhas ou credenciais**;
- vínculos com incidentes, vulnerabilidades, backups, mudanças, homologações, verificações de saúde e responsáveis.

## Mapa de dependências — regras

- Permitir visualizar dependências diretas e transitivas e localizar componentes compartilhados entre serviços.
- Representar as **APIs principal e de contingência de recepção de formulários como serviços independentes**, com armazenamento durável próprio e infraestrutura separada, preferencialmente provedores distintos (IN-124). O inventário deve evidenciar qualquer dependência comum que ameace a resiliência.
- Distinguir **recebimento/persistência na API**, **enfileiramento/processamento** e **entrega efetiva ao CRM**, preservando a política de confirmação da IN-149.
- Lembrar que **sites Imagine não armazenam bancos de dados próprios de leads**: as informações fluem pelos serviços de recepção e integração centralizados.
- As dependências dos backups dos sites e dos backups do CRM são tratadas separadamente (IN-101/IN-143).
- Fluxos n8n essenciais à entrega de leads podem receber prioridade de recuperação equivalente à da função crítica que sustentam (IN-144).
- Evitar expor a topologia de outros clientes aos funcionários de empresas clientes: a visão operacional completa é restrita à administração Imagine e perfis internos autorizados.

## Estados de implantação e fonte confiável

**É obrigatório diferenciar a arquitetura planejada da infraestrutura que já estiver comprovadamente implantada.** Exemplos de componentes **planejados** do projeto, a confirmar mediante inventário real:
- Hospedagem de sites em servidor dedicado com AlmaLinux e WHM/cPanel;
- automações n8n em VPS independente com Docker;
- CRM Imagine SaaS em infraestrutura segregada dos sites/cPanel;
- APIs principal e contingência em ambientes distintos, preferencialmente provedores diferentes;
- Portal Imagine e banco PostgreSQL conforme definições de arquitetura pertinentes.

**A menção a esses itens não representa confirmação de instalação, versão em execução ou disponibilidade atual.** Cada entrada deverá carregar status e evidência da descoberta/importação, evitando que um agente confunda uma decisão arquitetural com infraestrutura operacional existente.

As fontes poderão incluir documentação aprovada, registros de implantação, catálogo de ambientes, metadados autorizados de servidores, sistemas de monitoramento, artefatos/versionamento do GitHub e APIs técnicas de inventário. Divergências devem ser registradas e reconciliadas, com distinção entre **documentado**, **observado** e **validado**.

## Governança e autonomia dos agentes

1. **Coleta com privilégio mínimo:** agentes e coletores somente poderão inventariar recursos explicitamente autorizados, usando identidades e ferramentas com permissão delimitada.
2. **Atualização de fatos observáveis:** metadados técnicos verificados podem ser atualizados conforme procedimento aprovado; alterações que concedam privilégios, alterem a configuração produtiva ou encubram riscos não podem ser executadas apenas porque o inventário sugere inconsistência.
3. **Mudanças não previstas:** gerar evento ou incidente proporcional à severidade; não reverter alterações automaticamente sem saber se são legítimas, emergenciais ou fruto de implantação autorizada.
4. **Aprovação humana única para operações críticas:** aplicar IN-137/IN-138; não há dupla aprovação. Nenhum agente autoconcede acesso, reduz risco unilateralmente ou publica segredo em cadastro.
5. **Auditabilidade:** guardar histórico de inclusão, alteração, exclusão lógica, descoberta, fonte, versão, autor/agente, justificativa e confirmação de dados relevantes.
6. **Confiança e atualização:** marcar como possivelmente obsoletos os ativos sem observação recente; antes de alterar produção, exigir verificação atual do estado do alvo e das dependências relevantes.
7. **Aprendizado sem aumentar autoridade:** incidentes, correções e testes podem atualizar conhecimentos e sugerir vínculos de dependência, mas novos procedimentos executáveis dependem de homologação (IN-139).
8. **Controle financeiro:** inventariar capacidade e referências de custos contratados quando autorizado, mas não contratar servidores, recursos ou serviços pagos automaticamente (IN-142).

## Integração entre os oito agentes técnicos

| Agente | Uso previsto do inventário |
|---|---|
| **Integrações** | Rastrear formulários, APIs, filas, rotas, vínculos CRM e falhas de entrega |
| **Infraestrutura** | Localizar hosts, sistemas, recursos, disponibilidade e dependências técnicas |
| **Domínios** | Relacionar domínios, DNS, certificados, endpoints e serviços publicados |
| **Automações** | Mapear workflows n8n, integrações, gatilhos, credenciais referenciadas e serviços dependentes |
| **Backup** | Associar ativos a políticas de cópia, verificações e recuperação |
| **Segurança** | Identificar versões vulneráveis, ativos expostos, mudanças inesperadas e escopo de resposta |
| **Publicação** | Identificar versões publicadas de sites, ambientes, artefatos e dependências para validações |
| **Supervisor Técnico** | Correlacionar risco, incidentes, dependências, correções e qualidade do inventário |

## Exemplo ilustrativo

Um aviso confiável informa vulnerabilidade numa versão de PHP. O Agente de Segurança consulta o inventário e encontra os servidores com essa versão **observada**, identifica os sites potencialmente afetados e solicita validação atual do estado. Agentes de Infraestrutura/Publicação verificam compatibilidade e testes, aplicando correção automática **somente** se segura e já homologada, ou solicitando uma autorização humana se a intervenção for crítica. O histórico registra versões, testes e eventual recuperação.

## Indicadores e relatórios futuros

- Quantidade de ativos por serviço, ambiente, criticidade, estado e proprietário.
- Percentual de registros com evidência recente de verificação, grau de completude e ativos desatualizados.
- Divergências de configuração, alterações não previstas, dependências não documentadas e pontos únicos de falha identificados.
- Componentes com versões vulneráveis ou sem suporte, por escopo de impacto.
- Incidentes e mudanças que puderam usar o inventário para diagnóstico/recuperação com resultado confirmado.

## Pendências antes da implementação

1. Modelo de dados do inventário e do grafo de dependências, tipos de relacionamento, IDs e ciclo de vida.
2. Métodos de descoberta, frequência de coleta e fontes de verdade por tipo de componente.
3. Matriz de acesso a ativos e topologia, isolamento multiempresa e tratamento de informações sensíveis.
4. Critérios para conciliar documentação planejada versus estado observado, revisão humana de vínculos inferidos e controle de qualidade.
5. Modelo de interface para mapa de dependências e análise de impacto.
6. Integração com gestão de mudanças, incidentes, atualizações, backups, métricas e biblioteca de soluções.
7. Tratamento de infraestrutura legada, ativos desconhecidos, terceiros e recursos sem acesso direto.
8. Retenção de histórico de mudanças, custos, níveis de serviço de coleta e testes de confiabilidade.
9. Critérios de versionamento e rollback de configurações quando permitido, a serem detalhados no planejamento seguinte.

**Aprovado:** inventário de servidores, aplicações, versões e dependências; detecção de alterações; análise de impacto; atualização por mecanismos e agentes autorizados; compartilhamento controlado com oito agentes técnicos, sem registrar segredos.  
**Pendente:** especificação técnica, modelo, integrações, custos e implantação.  
**Programação:** não iniciada.
