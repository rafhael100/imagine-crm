# IN-150 — Segurança Inteligente dos Formulários e APIs Imagine

**Status:** APROVADA funcionalmente pelo responsável em 01/11/2026 (resposta "ok").  
**Etapa:** planejamento e documentação; **não iniciar programação, implantação, alteração de infraestrutura ou contratação**.  
**Escopo:** formulários de Sites Imagine, APIs receptora principal e de contingência, filas, integração com CRM Imagine, administração do Portal Imagine e Agente de Segurança.  
**Referências:** IN-124 a IN-129 (recepção, formulários, filas, retenção e recuperação), IN-130 a IN-132 (incidentes e agentes), IN-137/IN-138 (autonomia e aprovação humana única), IN-139 a IN-141 (aprendizado e prevenção), IN-143 a IN-146 (continuidade), IN-147 (exclusão após 18 dias), IN-148/IN-149 (reconciliação e confirmação de entrega).

## Objetivo
Proteger os formulários públicos e as APIs contra spam, requisições abusivas, ataques, vazamento de dados e acesso não autorizado, mantendo a **disponibilidade para visitantes legítimos** e a **integridade das submissões recebidas**. A resposta técnica deverá combinar regras verificáveis e agentes de IA em atividades de diagnóstico, prevenção, tratamento e aprendizado.

## Funcionalidades aprovadas
1. **Identificação e autenticação das integrações:** validar contexto, origem técnica e permissões das operações entre serviços. Diferenciar formulário público (visitante não autenticado) de integrações internas e operações administrativas autenticadas.
2. **Proteção contra spam:** reconhecer padrões automatizados suspeitos e aplicar medidas proporcionais, com limitação de falsos positivos e mecanismos de revisão.
3. **Limitação de requisições:** aplicar controles de frequência, volume e concorrência por camadas, respeitando casos legítimos de tráfego elevado e tentando evitar sobrecarga.
4. **Proteção de dados:** exigir HTTPS/TLS em trânsito e controles apropriados de criptografia, acesso, segregação e proteção dos dados temporários armazenados em APIs/filas.
5. **Detecção de ataques:** identificar sinais de tentativas de exploração, abuso de endpoints, autenticação irregular, uso indevido de integrações e ataques de negação de serviço.
6. **Bloqueio e contenção inteligentes:** utilizar regras proporcionais, graduais, temporárias e auditadas, sem bloquear indiscriminadamente clientes legítimos; prever desativação/revisão de medidas equivocadas.
7. **Agente de Segurança:** monitorar eventos, correlacionar indicadores, abrir incidentes, propor ou aplicar apenas proteções seguras previamente homologadas, verificar resultados e registrar aprendizado.
8. **Auditoria e observabilidade:** registrar eventos técnicos relevantes e resultados de contenção, com dados minimizados, restrição de acesso e integração à Central de Incidentes Técnicos.

## Regras de arquitetura e privacidade
- **Sites Imagine não possuem banco de dados local.** Formulários enviam dados por HTTPS às APIs centralizadas, com infraestrutura principal e de contingência independentes e persistência durável conforme IN-124.
- **Não incluir segredos, senhas administrativas, tokens privados ou chaves privilegiadas no HTML/JavaScript público** dos sites. Um identificador público do formulário não é credencial secreta e deve ser validado no servidor contra o contexto autorizado.
- **CORS, Referer, Origin e domínio permitido** podem contribuir para a defesa, mas **não são prova suficiente de autenticidade da submissão** nem substituem validação de autorização e controles antiabuso no backend.
- **Segregar rigorosamente** empresas, sites, formulários, CRM de destino e permissões. Usuários externos não podem escolher livremente outra conta CRM por alteração de parâmetros da requisição.
- Validar formato, limites de tamanho, campos permitidos, conteúdo, uploads quando houver e regras de consentimento/privacidade; aplicar prevenção de injeção e saneamento conforme local de uso, sem tratar textos de visitantes como instruções para agentes de IA.
- Aplicar controles de rede/aplicação, proteção antiabuso e mecanismos de resiliência de modo compatível com as duas APIs. Evitar que um único componente de segurança se torne ponto único de falha que impeça a contingência.
- Evitar expor informação sensível em mensagens ao visitante, logs, alertas, relatórios e históricos de aprendizado.
- O controle antiabuso deve permitir revisão de falsos positivos e preferir medidas graduais (observação, desafio apropriado, limitação, bloqueio temporário) quando tecnicamente adequadas, sem presumir que toda automação é maliciosa.
- A conformidade com LGPD, política de privacidade, finalidade e minimização do tratamento será detalhada antes da implementação; não pressupor consentimento como única base legal possível para todos os tratamentos.

## Relação com recepção, entrega e expiração de leads
- **Somente confirmar envio bem-sucedido ao visitante após a API persistir duravelmente a submissão.** Uma requisição barrada por proteção antiabuso não poderá ser apresentada como lead recebido com sucesso.
- **Entrega ao CRM só após comprovação real** de gravação/associação no destino, com chave idempotente e reconciliação (IN-148/IN-149).
- Quando o controle de segurança falhar, preservar diagnósticos/alertas apropriados sem criar duplicidade ou afirmar que o lead foi entregue.
- Permanecem os **3 dias de fila normal + 15 dias de falhas**, aviso 72 horas antes do vencimento e **exclusão automática permitida após 18 dias de envios não entregues**, com verificação final e auditoria, conforme IN-147. Não criar quarentena adicional.
- Para lead entregue, eliminar a cópia temporária **24 horas** após confirmação; preservar histórico de auditoria técnico **minimizado por 90 dias** conforme IN-128.
- Controles de segurança não concedem autorização para alterar esses prazos nem para excluir registros já entregues ao CRM.

## Governança de agentes e resposta a incidentes
1. Detecção por regras/telemetria e análise assistida → classificação de gravidade e risco → criação/atualização do incidente → seleção de procedimento de proteção homologado → execução dentro do escopo previamente permitido → validação, auditoria e lições.
2. Medidas de **baixo/moderado risco** podem ser automáticas quando homologadas, seguras e autorizadas pelo serviço/ambiente (IN-138).
3. Mudanças críticas (por exemplo, revogação de credenciais amplamente usadas, alterações de permissões, regras que bloqueiem serviços essenciais ou produção) exigem **uma autorização humana** quando não abrangidas pelas permissões aprovadas. **Não há dupla aprovação.**
4. Falta de autorização humana não interrompe monitoramento, diagnóstico, alertas ou medidas seguras previamente permitidas.
5. O Agente Supervisor Técnico acompanha incidentes recorrentes, eficácia, taxa de falsos positivos e necessidades de revisão de playbooks (IN-139/IN-140).
6. Nenhum agente pode autoampliar privilégios, reduzir classificação de risco para contornar aprovações ou executar novas ações somente porque uma sugestão foi produzida por IA.
7. **Novas despesas** com provedores de proteção, serviços de mitigação, ampliação de cotas ou recursos dependem de autorização humana específica (IN-142).

## Cenários para homologação
- Robôs inundam formulário com envios repetidos sem tirar capacidade de visitantes legítimos.
- Visitantes legítimos atrás de NAT/IP compartilhado submetem formulários sem bloqueio excessivo.
- Requisição simula outro site/formulário/empresa; API rejeita associação indevida.
- Cliente tenta usar token/chave expostos em scripts públicos; não existe segredo privilegiado no cliente.
- Oscilação da API principal leva à de contingência sem duplicidade ou perda de identidades.
- Ataque contra uma camada de segurança não desliga simultaneamente os dois caminhos de recepção por falha de dependência comum.
- Agente detecta falso positivo de defesa e propõe revisão segura sem liberar tráfego abusivo.
- Dados pessoais não aparecem indevidamente em logs, avisos ou memória técnica dos agentes.

## Detalhes pendentes para a arquitetura
- Modelo de identidade pública dos formulários, controles internos autenticados, assinatura/credenciais entre serviços e rotação de segredos.
- Limites quantitativos por endpoint, formulário, cliente, IP/rede e sinais de comportamento, com critérios de exceção e revisão.
- Tecnologias concretas de WAF, rate limiting, anti-bot, mitigação de DDoS e contingência de componentes de segurança, com custos.
- Estratégia para consentimentos, dados pessoais, retenção de telemetria de segurança e procedimentos para solicitações de titulares.
- Política de bloqueios, tempos de duração, níveis de risco, mecanismos de revisão e indicadores de falsos positivos.
- Métodos de autenticação/controle de acesso administrativos, segurança de chaves, criptografia, trilhas de auditoria e resposta a vazamentos.
- Testes de invasão e abuso em ambiente isolado, detecção, métricas, monitoramento e processos de recuperação.
- Procedimentos de incidentes de segurança envolvendo dados pessoais e obrigações de comunicação a avaliar conforme o caso.

**Aprovado:** autenticação/validação de integrações, proteção contra spam e abuso, rate limiting, proteção dos dados, detecção de ataques, bloqueio proporcional, atuação segura do Agente de Segurança e aprendizado contínuo.  
**Pendente:** escolha de ferramentas, limites, políticas concretas e detalhamento técnico.  
**Programação:** não iniciada.


## Complemento COM-14 — controles LGPD no checklist pré-entrega (02/11/2026)

Em complemento à segurança dos formulários e APIs já aprovada, o responsável solicitou previsão dos **avisos, aceites e consentimentos exigíveis pela LGPD brasileira**. A política por finalidade/base legal e o funcionamento técnico de avisos de privacidade, controles de marketing, cookies não essenciais quando aplicáveis, revogação/preferências, registro de versões, envio ao tenant correto e ausência de tratamento fora de finalidade passam a fazer parte do **núcleo verificável do checklist de entrega IA**, com condições por produto. **Distinguir consentimento de outras bases legais**, não forçar autorizações genéricas nem afirmar conformidade jurídica total apenas por verificação da IA. Falhas relevantes bloqueiam entrega; exceções legais requerem validação humana. Ver [COM-14](PORTAL-COM-14-lgpd-aceites-privacidade-consentimentos-checklist.md) e [COM-13](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md). Sem programação.
