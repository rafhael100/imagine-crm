# IN-124 — Integração Inteligente de Formulários e Leads

**Status:** APROVADA funcionalmente em 01/11/2026 pelo responsável do projeto.  
**Fase:** planejamento e documentação. NÃO autoriza programação.  
**Escopo:** integração entre formulários de sites/landing pages Imagine e contas do CRM Imagine, inclusive CRM Básico concedido como cortesia quando elegível.  
**Relacionamentos:** IN-118 (portabilidade), IN-119–IN-122 (cortesia/vinculação), IN-123 (painel de integração), regras gerais do CRM para leads e contatos.

## Objetivo
Capturar, entregar, rastrear e recuperar automaticamente envios dos formulários de sites Imagine à conta CRM correta, com tolerância a indisponibilidades temporárias, transparência sobre erros e proteção de dados.

## Requisitos funcionais aprovados
1. **Captura automática:** cada formulário integrado de site ou landing page Imagine encaminha os dados submetidos à conta CRM vinculada e autorizada no momento aplicável da entrega.
2. **Origem identificável:** preservar metadados disponíveis de site, página, formulário e campanha/origem, sem inventar valores não coletados. Evitar exposição indevida de parâmetros sensíveis.
3. **Tratamento de duplicidades:** identificar leads e contatos possivelmente existentes, conforme política do CRM, sem eliminar históricos ou mesclar dados automaticamente de forma destrutiva.
4. **Tolerância a falhas de comunicação:** guardar com segurança envios que não puderem ser entregues por falhas transitórias e processá-los posteriormente, por novas tentativas automáticas, sem exigir intervenção manual para falhas comuns.
5. **Avisos internos:** notificar a equipe Imagine sobre problemas persistentes de entrega, falhas recorrentes, congestionamento ou risco de retenção esgotada.
6. **Mudança de vínculo:** a partir da ativação da nova integração validada pela equipe Imagine, novos leads devem ser encaminhados à nova conta CRM. Os registros já entregues à conta antiga não são migrados automaticamente.
7. **Histórico de entregas:** auditar recebimentos, tentativas, sucesso/erro e reprocessamentos, com visibilidade segundo as permissões aplicáveis.
8. **Validação e privacidade:** validar dados de entrada, aplicar proteção antispam/abuso, segregação por cliente e proteção de dados pessoais em trânsito e quando temporariamente armazenados.
9. **Inclusão nos sites elegíveis:** integrar a funcionalidade na oferta aprovada dos sites Imagine vinculados a conta CRM Básico Cortesia ou outro plano, respeitando os serviços do contrato.

## Comportamentos de segurança e consistência a detalhar
- A integração terá **identificação única por envio** e processamento idempotente: uma mesma mensagem reenviada não deve criar automaticamente leads duplicados por causa de tentativas repetidas.
- A fila deverá ser persistente e isolada por conta/projeto; erro duradouro deve ser identificado e escalado para intervenção autorizada, sem reenvios infinitos.
- Definir como tratar mensagens pendentes criadas **antes** da troca de CRM: não redirecionar silenciosamente dados antigos à conta nova; preservar contexto do vínculo na origem e estabelecer procedimento autorizado para resolução.
- Não afirmar garantia absoluta de “zero perda” antes de validar retenção, capacidade, disponibilidade, backup operacional e comportamento sob falhas extremas.
- Proteger dados sensíveis nos registros de auditoria e mensagens de erro; evitar cópia desnecessária de dados pessoais e compartilhamento entre contas.
- Registrar consentimentos, bases legais e informações pertinentes ao tratamento de dados conforme a configuração do formulário e política aplicável.
- Reprocessamento manual deve ser restrito a colaboradores autorizados da Imagine e respeitar a identificação do envio.
- Dados recebidos pelo CRM não devem ser apagados quando a origem, integração ou site for cancelado; aplicam-se as políticas de armazenamento, acesso, retenção e portabilidade já aprovadas.

## Pontos técnicos pendentes para especificação antes do desenvolvimento
- Contrato dos campos, esquema versionado de payloads, anexos, limites de tamanho e mapeamento de formulários.
- Fila e política de novas tentativas, prazos de retenção, descarte seguro, capacidade, limiares para alertas e mecanismos de recuperação.
- Recepção de webhooks/API, validação de origem/assinaturas, autenticação entre serviços, limites de requisição e bloqueios antispam.
- Critérios de deduplicação, regras por empresa e comportamento diante de contatos preexistentes.
- Testes controlados de ponta a ponta e visualização do resultado no Portal Imagine (IN-123), sem criar leads fictícios no funil de produção.
- Continuidade em mudanças de contrato, CRM suspenso, vinculação em transição ou eventos concorrentes.
- Métricas e SLA, reprocessamentos após limite de tentativas, comunicação e trilha de auditoria.

**Aprovado:** funcionalidades e políticas funcionais descritas.  
**Pendente:** parâmetros técnicos e fluxos detalhados acima.  
**Programação:** não iniciada.

## Arquitetura de recepção redundante — APROVADA em 01/11/2026

O responsável aprovou explicitamente a arquitetura com **API principal + API de contingência**, para reduzir o risco de perda de leads, com as condições abaixo.

### Princípios aprovados
1. **Sites e landing pages Imagine sem banco de dados próprio.** Os formulários enviam os dados via HTTPS à camada central de ingestão.
2. **Duas APIs de recepção:** uma **principal** e uma **secundária de contingência**, implantadas em infraestruturas independentes, sem dependência operacional única que invalide a redundância.
3. **Armazenamento durável independente para cada rota de recepção** ou outra solução que preserve a independência comprovável das duas rotas. Uma segunda API que dependa do mesmo banco indisponível da primeira não constitui contingência suficiente.
4. **Failover automático:** quando a principal estiver indisponível ou não puder confirmar a persistência, tentar a secundária com o **mesmo identificador único de envio**.
5. **Confirmação somente após persistência:** o visitante vê mensagem de sucesso apenas depois de uma das rotas confirmar gravação durável do envio; retorno não recebido ou resultado incerto não autoriza indicar sucesso nem descartar o conteúdo.
6. **Envio idempotente e deduplicação:** falhas de rede e respostas incertas podem provocar tentativas nas duas rotas. A ingestão e a entrega ao CRM devem impedir criação duplicada do mesmo lead decorrente do reenvio.
7. **Entrega posterior ao CRM:** cada rota persiste o envio com identificação do projeto, vínculo da conta CRM e contexto temporal; trabalhadores de entrega reprocessam quando o CRM voltar, com rastreabilidade, limites, alertas e tratamento de pendências.
8. **Portal Imagine:** a área administrativa concentra o **monitoramento e gestão das filas, tentativas, falhas e reprocessamento**, mas sua indisponibilidade não deverá interromper as APIs.
9. **Independência do CRM:** indisponibilidade temporária do CRM não interrompe a recepção de envios pelas APIs, desde que sua própria infraestrutura esteja operacional.
10. **Indisponibilidade simultânea:** se ambas as APIs não puderem confirmar recebimento, o formulário não exibirá sucesso; deverá preservar os campos enquanto possível na página e permitir nova tentativa, com informação clara ao visitante e cuidados de privacidade.
11. **Sem alegação de garantia absoluta de zero perda:** falhas simultâneas, indisponibilidade da rede do visitante e fechamento da página antes de qualquer confirmação podem impedir a entrega.

### Diretriz de tecnologia e operação
- **Opção arquitetural recomendada, não fixada como decisão de tecnologia:** backend Laravel e armazenamento relacional PostgreSQL para persistência/fila, com trabalhadores assíncronos e controles de idempotência.
- Implementação de duas rotas precisa incluir **domínios/endpoints e armazenamento realmente independentes**, políticas de autenticação e CORS adequadas, monitoramento de saúde, roteamento de contingência e alertas.
- A operação e visualização ficam integradas à **área administrativa do Portal Imagine**, sem tornar seu frontend ou seu backend administrativo um ponto único de falha no recebimento.

### Pendências para dimensionamento e detalhamento (não impedem a aprovação funcional)
- Servidores/provedores e isolamento físico/lógico das duas APIs, incluindo DNS, certificados, gateway e bancos de dados.
- Estratégia de failover, timeout, resposta ambígua, reconciliação entre filas e identificação única end-to-end.
- Retenção e limpeza dos dados temporários, criptografia, permissões, conformidade com LGPD, monitoramento e custos.
- Objetivos mensuráveis de disponibilidade, capacidade, testes periódicos de falha e recuperação.
- Opções de infraestrutura existentes da Imagine e custo de manter um ambiente secundário.

**Status desta arquitetura:** aprovada funcionalmente; escolhas de tecnologia e de hospedagem serão detalhadas e revisadas antes da implementação. **Não iniciar programação.**


## Hospedagem da API principal e de contingência — APROVADA em 01/11/2026

Decisão expressamente aprovada pelo responsável do projeto, dando continuidade à arquitetura redundante acima:

1. **API principal:** serviço central da Imagine, separado da interface/painel administrativo do Portal Imagine, com execução independente da disponibilidade do painel.
2. **Persistência principal:** banco/fila central durável (PostgreSQL como opção arquitetural preferida, a confirmar na arquitetura técnica), sob operação do serviço de integração.
3. **API de contingência:** implantação **em outro servidor**, independente do servidor da API principal, com preferência por **outro provedor** quando a análise de custos e disponibilidade permitir. A aprovação não escolhe provedor nem contrata infraestrutura.
4. **Persistência de contingência:** banco/fila durável **independente** do principal, de forma que o colapso de uma infraestrutura não impeça a outra de receber os formulários.
5. **Portal Imagine — Administração:** centralizar consulta de status, monitoramento e gestão de falhas, filas e reprocessamentos. O painel não será dependência operacional necessária para receber leads nas APIs.
6. **Integração CRM Imagine:** processadores de entrega a partir de ambas as filas, com identificador único por envio, deduplicação, rastreabilidade e tratamento seguro de retornos incertos.
7. **Sem banco nos sites:** as páginas e landing pages hospedadas não precisam de banco de dados próprio.
8. **Regra de confirmação:** só informar envio bem-sucedido ao visitante quando uma rota confirmar persistência durável; na indisponibilidade simultânea, não exibir sucesso.

### Definições técnicas e comerciais ainda pendentes
- Dimensionamento da API principal e da secundária, servidores/provedores concretos, custos fixos e variáveis.
- Localização dos bancos, administração e acesso seguro à fila de contingência, políticas de backups, retenção e limpeza.
- Testes regulares de recuperação e failover, alertas, limite de espera, circuit breaker, política de reenvios e idempotência inter-regiões.
- Pontos únicos de falha que possam restar em DNS, certificados, proxy, autenticação e dependências compartilhadas.

**Status:** topologia lógica e independência das infraestruturas **aprovadas**; alocação concreta de servidores e tecnologias **a dimensionar**. Esta decisão é documental, sem iniciar desenvolvimento ou contratar serviços.
