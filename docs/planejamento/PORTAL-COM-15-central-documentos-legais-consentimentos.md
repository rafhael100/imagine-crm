# PORTAL-COM-15 — Central de Documentos Legais e Consentimentos

**Status:** **MÓDULO FUNCIONAL APROVADO em 02/11/2026; POLÍTICA DE PRIVACIDADE PADRÃO POR SITE, ACEITE OU PEDIDO DE ALTERAÇÃO PELO CLIENTE E REVISÃO INTELIGENTE POR IA APROVADOS.** A IA poderá preparar novas versões a partir de solicitações do cliente e submetê-las novamente para aprovação expressa; exceções jurídicas/fora do padrão seguem para revisão humana. O responsável respondeu **“ok”** à pergunta específica sobre aprovar uma **Central de Documentos Legais e Consentimentos**, com **gestão administrativa, versionamento e histórico de aceites**, aplicável ao Portal Imagine e aos sites dos clientes, com documentos apropriados a cada cliente/projeto e aprovação do responsável quando necessária.
**Fase:** apenas planejamento/documentação. Não programar, publicar termos, substituir política existente, disparar solicitações de consentimento ou coletar novas informações antes de aprovação e homologação.
**Dependências:** [COM-14 — LGPD e aceites](PORTAL-COM-14-lgpd-aceites-privacidade-consentimentos-checklist.md), [COM-13 — checklist IA de entrega](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md), [D01 — aceite de versões/orçamentos](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [IN-125 — formulários](IN-125-gestao-inteligente-formularios.md), [IN-150 — segurança](IN-150-seguranca-inteligente-formularios-apis.md), [COM-12/REL-01 — relacionamento](PORTAL-COM-12-relacionamento-inteligente-retencao-expansao-clientes.md).

## 1. Aprovação funcional e propósito

A plataforma deverá oferecer uma **Central administrativa de Documentos Legais e Consentimentos**, capaz de **cadastrar, revisar, atualizar, versionar, publicar e consultar** os documentos aplicáveis à operação da Imagine e aos sites de seus clientes **sem modificar o código a cada alteração de conteúdo**. Registrar **histórico íntegro de aceites e consentimentos por versão, finalidade e pessoa/contexto**, quando exigidos. Atribuir aos projetos o conjunto legal apropriado; textos e bases legais devem ser validados por quem efetivamente tem autoridade.

A **aprovação da Central não aprova textos jurídicos específicos, novas bases legais, cláusulas contratuais, retenção universal, configuração automática de consentimento em todos os formulários ou dispensa de revisão jurídica**. Não substituir aceites expressos já previstos na PORTAL-D01.

## 2. Separação de documentos e finalidades

- **Termos de Uso, contratos e termos comerciais do Portal Imagine:** podem exigir aceite expresso válido de representante autorizado, conforme tipo de documento, contrato e processo comercial.
- **Avisos/Políticas de Privacidade do Portal e dos sites:** publicações informativas versionadas e acessíveis ao público pertinente; **ler ou tomar ciência não equivale a consentimento para toda e qualquer finalidade**.
- **Termos específicos por serviço:** declarações ou autorizações adicionais (ex.: integração/transferência, tratamento de dados em projeto especial), mediante análise e validação da base jurídica antes de exigir aceite.
- **Consentimentos granulares por finalidade**, **somente onde consentimento for a base legal apropriada:** marketing por canal, uso de cookies/tecnologias não essenciais quando pertinentes, formulários ou usos opcionais. Guardar escolhas distintas, com **aceitar, recusar, atualizar e revogar**, conforme exigências aplicáveis; evitar caixas pré-marcadas, aceite forçado ou combinação indevida com termos necessários.
- **Provas de aceite de versões de site e orçamentos (PORTAL-D01)** continuam como eventos próprios, vinculados ao exato documento/versão de proposta e responsáveis autorizados; **não são prova de consentimento publicitário**.

## 3. Cadastro e administração por escopo

**Estrutura proposta para especificação:**
1. **Biblioteca jurídica Imagine:** termos e política da própria Imagine, modelos de avisos e textos de consentimento, categorias por finalidade, responsáveis pela revisão e históricos.
2. **Aplicação por contexto:** organização/projeto/site/serviço/campanha, controlador adequado, versão e língua; distribuir **somente documentos aplicáveis** a cada caso e não compartilhar dados/aceites de clientes diferentes.
3. **Personalização para cada cliente/site:** preenchimento de dados necessários sobre empresa controladora, contato, finalidades, provedores, formulários e cookies efetivamente utilizados; a Imagine pode oferecer **modelos**, mas o **cliente/controlador ou responsável autorizado deve validar** seu conteúdo quando cabível.
4. **Administração baseada em perfis:** rascunho, revisão, homologação/autorizações e publicação por usuários com poderes compatíveis, com logs. **O administrador comum não poderá unilateralmente editar aceites antigos ou publicar alteração jurídica crítica sem processo de aprovação cabível**.
5. **Sem programação para textos rotineiros:** editar e publicar o conteúdo homologado por painel; mudanças em lógica de coleta, backend, script, integração e segurança **ainda poderão exigir engenharia, testes e aprovações específicas**, não sendo simples edição documental.

## 4. Versionamento, vigência e integridade

**Estados sugeridos** para detalhamento: **Rascunho → Em revisão → Aprovado → Publicado/Vigente → Substituído/Arquivado**. A Central deverá registrar:
- ID único e tipo de documento; proprietário/controlador/escopo; versões e código/hash de integridade; idiomas; relação com finalidade/base legal validada; data de vigência e histórico de publicação/retirada.
- Autor e revisor/aprovador, instante de edição/publicação, justificativa e trilha imutável de mudanças relevantes.
- Sempre guardar a **versão exata aceita** em qualquer registro histórico, **sem substituir retroativamente a declaração do titular** por um texto atualizado.
- **Mudanças materiais** que exijam nova manifestação devem dar origem a novo fluxo expresso, conforme avaliação jurídica; **não presumir que toda revisão editorial exige novo consentimento**, nem que a alteração de política concede nova autorização automaticamente.
- Política de armazenamento, acesso e descarte proporcional e legalmente fundamentada a ser definida; não aplicar por analogia os 120 dias da conta CRM ao acervo de provas legais.

## 5. Histórico de aceites e preferências

Criar registro auditável mínimo e protegido para:
- Identidade autorizada do representante ou contexto de titular/visitante conforme pertinência; organização/site/documento/finalidade e versão exata; tipo de evento (aceite contratual, consentimento dado/recusado/alterado/revogado, informação apresentada).
- Data/hora/fuso, canal e mecanismo de manifestação comprovável, conteúdo apresentado, permissões/autenticação cabíveis, origem da escolha e eventual revogação; **não registrar IP/dispositivo indiscriminadamente se não necessário ou proporcional**.
- Painel consulta e exportação para pessoal autorizado e titulares quando aplicável, sem exposição do acervo de outro cliente; **não permitir editar, apagar ou retroagir provas legítimas por operação cotidiana**.
- Disponibilizar centro de preferências e revisão/revogação quando cabível, com propagação às integrações pertinentes; avaliar obrigações de retenção, gestão de direitos e exceções legais.

**Nunca ativar marketing com base no simples contrato, na publicação de um site ou em consentimento dado a outro controlador.** Avisos transacionais e contratos têm finalidade/base próprias.

## 6. Integração com o checklist IA e REL-01

O **agente de qualidade/entrega COM-13** deverá confirmar, antes de marcar ENTREGUE:
- Que os **documentos exigidos e aprovados** para o tipo de produto, finalidade, projeto e controlador correto foram associados/publicados na **versão vigente correta**.
- Que links de privacidade, formulários e cookies/consentimentos condicionais funcionam conforme as bases legais validadas; em especial a recusa não é ignorada, marketing não é pré-marcado e scripts opcionais que dependem de opt-in não são executados antes da autorização.
- Que o **aceite expresso da versão do site (D01)**, quando obrigatório, está validado independentemente dos registros de privacidade e marketing.
- Se faltar documento obrigatório, houver texto não homologado, prova necessária ausente ou conflito legal/material, **bloquear entrega e abrir pendência para revisão humana**. O agente **verifica cumprimento técnico**, mas não certifica sozinho a adequação jurídica de uma política.

A **Central de Relacionamento COM-12/REL-01** deverá consultar preferências e base legal de cada finalidade/canal antes de mensagens comerciais ou educativas, distinguindo os tratamentos; nenhum disparo autorizado por esta decisão.

## 7. Controles e pendências de implantação

**Aprovado pelo “ok”:** módulo Central, cadastro/edição via painel administrativo, **versionamento, histórico auditável de aceites e consentimentos**, aplicação de textos apropriados por site/cliente e aprovação do responsável quando necessária.

**Fluxo de aprovadores definido posteriormente:** documentos da própria Imagine são aprovados pela **administração Imagine** após revisão necessária; políticas de privacidade específicas de sites dos clientes exigem **aprovação expressa de representante autorizado do cliente responsável**, após preparação assistida por IA. **Ainda por decidir/homologar:** perfis/permissões granulares de administração, modelos concretos, cláusulas jurídicas, bases legais por tipo de tratamento, política de cookies por produto, formatos de consentimento, templates por idioma, fluxos de nova manifestação, guarda legal das evidências, gestão de titulares, notificações, calendário de revisão e quais alterações precisam de reaceite; LGPD, segurança e conformidade serão submetidas à revisão humana competente.

**Somente planejamento. Nenhuma programação, publicação de documentos jurídicos ou coleta/alteração de consentimentos realizada.**


## 8. Fluxo de aprovação dos documentos legais — decisão complementar (02/11/2026)

**Pergunta apresentada ao responsável:** “Você aprova esse fluxo de aprovação dos documentos legais?”, após a proposta explícita de que **(a) os documentos da própria Imagine sejam aprovados pela administração Imagine, após a revisão necessária; (b) as políticas de privacidade específicas dos sites dos clientes possam ser preparadas com auxílio de IA, mas dependam da validação do cliente responsável antes da publicação**. **Resposta do responsável: “sim”. FLUXO APROVADO.**

### 8.1 Documentos da Imagine — fluxo aprovado

1. O documento (Termos do Portal Imagine, Aviso/Política de Privacidade Imagine ou outro documento sob responsabilidade dela) é criado/revisado no painel administrativo, podendo receber sugestões de IA.
2. Submeter à **revisão jurídica/privacidade necessária** ao tipo de conteúdo, preservando evidências de quem revisou e da versão examinada; a IA não pode substituir a análise jurídica competente nem presumir suficiência legal.
3. A **administração Imagine**, por representante autorizado a ser configurado no controle de permissões, **aprova expressamente a versão exata** após revisão apropriada. Silêncio e passagem de tempo não equivalem a aprovação.
4. Após autorização válida, o sistema poderá **publicar a versão aprovada** conforme o fluxo técnico homologado, preservando data/hora/fuso, responsável, versão, conteúdo/hash e vínculo de vigência. O detalhamento de alçadas e ativação automática da publicação ainda será definido.

### 8.2 Políticas de Privacidade dos sites dos clientes — fluxo aprovado

1. A Imagine pode disponibilizar **modelo adequado ao site**, preencher dados informados pelo cliente e usar agentes de IA para **preparar uma minuta**, identificar lacunas e recomendar ajustes, respeitando finalidade, controlador, integrações e serviços realmente contratados.
2. O texto **não pode ser presumido verdadeiro, juridicamente válido ou autorizado** por ter sido gerado/revisado por IA. Apresentá-lo no Portal ao **cliente responsável pelo site/controlador ou representante expressamente habilitado para aprová-lo**, permitindo revisar, corrigir, solicitar alterações ou rejeitar.
3. **Antes da publicação**, obter **aprovação explícita e auditável do cliente responsável sobre a versão exata** da política do seu site. A confirmação deve preservar identidade/autorização do aprovador, versão/hash, data/hora/fuso, manifestações e histórico. A aprovação do cliente não dispensa revisão jurídica quando a natureza do tratamento exigir.
4. **Sem aprovação válida do cliente responsável, a política específica não será publicada como versão definitiva**; se essa política for requisito obrigatório, **o agente COM-13 não poderá marcar o site como ENTREGUE**. O projeto poderá permanecer em preparação/prévia sob controles técnicos próprios, sem publicação final do documento não aprovado.
5. Alteração posterior material da política exige **revisão e nova aprovação do responsável quando aplicável**, com rastreabilidade da versão anterior; alteração de política não concede consentimento novo automaticamente nem exige sempre novo consentimento de todos os visitantes.

### 8.3 Separações e limites

- **Aprovação de política de privacidade do site pelo cliente ≠ aceite da versão visual/funcional do site (PORTAL-D01) ≠ consentimento do visitante ≠ aceite contratual do Portal.** São eventos distintos, embora possam aparecer organizados na Central.
- A Imagine administra seus próprios documentos; **não aprova unilateralmente a política do site do cliente em nome dele**. O cliente não recebe por isso acesso de administração aos documentos legais da Imagine.
- A finalidade/bases legais, obrigações relativas a cookies, formulários, direitos de titulares e papéis controlador/operador seguem a COM-14 e exigem análise jurídica contextual; não inserir um checkbox universal de “aceito a LGPD”.
- Definições pendentes: permissões específicas, modelos de notificação, prazos de revisão, delegação de representação, reaprovação por atualização, retenção e fluxos de correção/contestação. **Nenhuma programação, aceite ou publicação real iniciada**.


## 9. Política de Privacidade padrão por site — aprovação ou solicitação de alterações pelo cliente (02/11/2026)

**Decisão expressa do responsável:** “os sites ja terao uma politica de privacidade padrao que o cliente precisa dar o ok para que possa ser liberada no site dele ou solicitar alterações”. **Regra funcional APROVADA, mais específica que a possibilidade anterior de criar uma minuta do zero para cada cliente.**

### 9.1 Modelo padrão e preparação do documento

1. **Cada novo site Imagine (Onepage, Landing Page e futuros produtos) terá, por padrão, uma Política de Privacidade pronta para revisão**, derivada do **modelo oficial versionado e juridicamente revisado** mantido na Central COM-15; não deixar o cliente começar com documento em branco como fluxo normal.
2. O sistema **gera uma instância do modelo por cliente/site**, com **dados reais da empresa responsável**, contatos pertinentes, formulários, finalidades/base legal adequadas, provedores, cookies, integrações/CRM e particularidades efetivamente existentes no projeto. A IA pode sugerir conteúdo e apontar lacunas, mas **não inventar práticas ou bases legais**.
3. O modelo padrão é **ponto de partida, não uma declaração universal de conformidade**: tratamentos incomuns, dados sensíveis, crianças, ferramentas de rastreamento e outros contextos especiais podem exigir revisão humana/jurídica e cláusulas próprias antes de submeter ao cliente.
4. **A política individual do cliente não é a Política de Privacidade do Portal Imagine** e não representa consentimento do visitante para marketing.

### 9.2 Duas ações do cliente no Portal — regra aprovada

Depois de preparada a versão específica para o site, apresentar a **política integral, de forma legível**, no Portal Imagine, com identificação da empresa, site/URL de destino e número da versão, oferecendo **duas ações inequívocas**:

- **“Aprovar Política de Privacidade”**: exige ato positivo de representante do cliente autorizado, para a **versão exata** exibida. Ao aprovar, o documento fica **APROVADO PARA PUBLICAÇÃO NO SITE DO CLIENTE**, condicionado à ausência de outros bloqueios técnicos, legais ou operacionais. Guardar evidência de quem aprovou, papel/autorização, data/hora/fuso, identificador/hash e versão do documento, sem mudar o texto aprovado silenciosamente.
- **“Solicitar Alterações”**: cliente informa correções e observações; pedido cria histórico/chamado vinculado à versão e deixa o documento em **AJUSTES SOLICITADOS / NÃO APROVADO**. A Imagine, com auxílio permitido de IA e supervisão adequada, prepara **nova versão** e devolve ao cliente para **nova análise e aprovação expressa**. Não publicar automaticamente após simples envio de ajustes.

**O cliente poderá revisar, aprovar ou solicitar ajustes; o silêncio, visualização ou encerramento de prazo não constituem aceite.** Não atribuir à IA poder para aprovar em nome do cliente.

### 9.3 Liberação no site e bloqueio de entrega

- **Antes do aceite expresso do cliente, a política é apenas uma minuta/instância interna de revisão no Portal** e **não deve ser publicada como versão definitiva no site público**.
- **Depois da aprovação válida da versão exata**, e da validação técnica aplicável, o fluxo pode **liberar/publicar automaticamente** o texto aprovado no site correspondente, dentro de playbook/permissões homologadas, sem necessidade de confirmação manual de rotina. Esse resultado **não dispensa** outros aceites de versão/contrato da D01 ou a revisão jurídica especial quando exigida.
- **O agente IA da entrega COM-13 deve verificar que a Política de Privacidade específica do site foi aprovada pelo cliente e que a mesma versão está efetivamente publicada/acessível**; se não, o estado do projeto não poderá ser **ENTREGUE**, e consequentemente não haverá evento oficial de Dia 0 REL-01.
- Qualquer nova redação material posterior deve resultar em **nova versão** para avaliação do cliente e eventual nova aprovação, preservando a política anteriormente vigente durante o processo conforme legitimidade, segurança e requisitos legais.
- O aceite do cliente à política de seu site **não equivale ao consentimento de seus visitantes**, nem autoriza usar leads de seu CRM para marketing Imagine.

### 9.4 Estados e evidência de auditoria (modelo de desenho)

**Modelo base instanciado → Em revisão pelo cliente → (Aprovado para publicação | Ajustes solicitados) → Nova versão para revisão, quando necessário → Publicado no site após aceite válido → Versionado/atualizado quando necessário.**

Registro por site: ID do modelo global, versão do modelo, ID da política do projeto, conteúdo/hash da versão individual, empresa/controlador, aprovador/representação, data/hora/fuso, aprovação ou pedido de ajustes, comentários e versões subsequentes, URL/versão publicada, evento de publicação e integridade dos históricos.

### 9.5 Itens de detalhamento ainda pendentes

Layout das telas, checklist jurídico mínimo do modelo padrão, dados e fontes para preencher cada campo, perfil/representação do cliente, notificações e lembretes, prazo para resposta, quantidade/limites contratuais de ajustes (não criar cobrança por simples pedido sem previsão contratual), quando alterações jurídicas específicas exigem revisão especializada, procedimento de publicação, correção/reversão e manutenção das versões após o lançamento.

**Resumo:** **modelo padrão por site + cliente pode APROVAR ou SOLICITAR ALTERAÇÕES + só publicar após aceite expresso da versão correta** são regras aprovadas. **Não iniciar programação nem publicar documentos reais nesta fase.**


## 10. Revisão inteligente das Políticas de Privacidade após pedido de ajustes — APROVADO (02/11/2026)

**Proposta apresentada ao responsável:** “quando o cliente solicitar alterações, o agente de IA analise o pedido, prepare uma versão revisada e a encaminhe novamente ao cliente para aprovação, com intervenção humana quando houver questões jurídicas ou situações fora do padrão”. **Resposta do responsável: “ok”. REGRA FUNCIONAL APROVADA.**

### 10.1 Fluxo de revisão automática assistida por IA

1. **Pedido do cliente:** a partir da política padrão específica do seu site, o representante autorizado escolhe **Solicitar Alterações**, registra os trechos e correções pretendidos e, quando necessário, recebe pedido de esclarecimento. Vincular o pedido ao projeto, controlador, documento e **versão exata** apresentada.
2. **Análise pelo agente IA:** identificar os trechos afetados, consultar apenas dados autorizados da empresa, contrato, funcionalidades do site e modelo jurídico previamente homologado; distinguir **ajuste simples/paramétrico** de **mudança jurídica relevante**, informação desconhecida, pedido contraditório ou uso não previsto de dados. **Não inventar práticas, finalidades, dados, provedores, consentimentos ou bases legais**.
3. **Revisão simples dentro da autonomia aprovada:** agente prepara **nova minuta versionada**, preservando cláusulas padronizadas necessárias e destacando alterações relevantes; executa verificações de completude e consistência com formulários, cookies, integrações e escopo do site. Um procedimento homologado pode preparar uma minuta sem exigir aprovação humana repetitiva para cada ajuste rotineiro.
4. **Exceção:** dúvida de interpretação legal, cláusula que altere finalidade/base legal, dados sensíveis/de crianças, transferência internacional relevante, omissão de direitos/transparência, remoção de aviso essencial, conflito com dados reais, fora do modelo homologado ou tentativa de impor consentimento indevido **bloqueiam a liberação automática da nova minuta**. Encaminhar para responsável humano/jurídico habilitado, com resumo do pedido, diferença entre versões e riscos; só retomar após análise/alteração autorizada.
5. **Nova apresentação:** após passar controles técnicos e revisões exigidas, a versão revisada será **encaminhada ao cliente no Portal** para avaliação. Cliente poderá **Aprovar** ou **Solicitar novas alterações**, mantendo o ciclo rastreável. **Não há aprovação por silêncio e a IA jamais aprova em nome do cliente**.
6. **Publicação:** somente após **aprovação expressa do cliente à versão final** e verificações técnicas/jurídicas cabíveis, autorizar publicação do texto exato no site. Agente COM-13 confirma o vínculo entre texto aprovado e publicado no checklist. **Documento pendente de aprovação ou ajuste impede ENTREGUE**, sem iniciar Dia 0 REL-01.

### 10.2 Controles e histórico mínimos

- Registros por cliente/site: pedido, usuário autorizado, data/hora e fuso, versão original, comentários, classificação (rotineiro/exceção), agente/modelo/playbook usado, diferenças entre versões e testes, identidade do revisor humano quando exigido, nova versão/hash, nova solicitação de aceite e decisão final do cliente.
- Estados sugeridos: **Aguardando revisão IA → Em análise → Aguardando esclarecimento ou revisão humana (se necessário) → Nova versão aguardando aprovação do cliente → Aprovada → Liberada/publicada**. Falhas devem ficar visíveis e recuperáveis, sem alteração silenciosa ou publicação antecipada.
- Segregar políticas e dados por cliente, não permitir que a IA se autoatribua privilégios, não substituir aceitações históricas e não apagar versões/recusas anteriores por atualização.
- Ajustes solicitados e novas versões **não criam cobrança automática**, número mínimo/máximo de revisões nem SLA novo. Condições comerciais e prazos específicos ainda serão definidos em decisão própria.

### 10.3 Aprovado versus pendente

**APROVADO:** após solicitação de alteração, **IA analisa, prepara nova minuta versionada e devolve ao cliente para aprovação**, escalando questões jurídicas e casos fora do padrão à equipe habilitada. A política padrão, aprovação do cliente antes da publicação e bloqueio de entrega sem aprovação continuam vigentes.

**PENDENTE PARA ESPECIFICAÇÃO:** matriz de classificação de risco, modelos jurídico/legais homologados, permissões, regras de intervenção, telas para comparação de versões, forma de notificar/encaminhar a nova versão, limites de tentativas, custos/capacidade, revisão jurídica dos templates e testes. **Não há programação, envio de mensagens ou publicação de documentos reais.**


## 11. Notificações automáticas de análise da Política de Privacidade — Portal e e-mail (02/11/2026)

**Pergunta submetida ao responsável:** “Recomendo que, quando a política estiver pronta para análise ou uma versão revisada estiver disponível, o cliente receba uma notificação no Portal Imagine e um aviso por e-mail, com acesso direto ao documento. Você aprova essas notificações automáticas por Portal e e-mail?” **Resposta: “ok”. APROVADO.**

### 11.1 Gatilhos e destinatários aprovados

- **Política padrão individual do projeto pronta para primeira análise:** disponibilizar ao cliente responsável uma **notificação interna no Portal Imagine** e **um aviso por e-mail** informando a necessidade de sua decisão.
- **Nova versão revisada pronta após pedido de alterações:** repetir notificação interna e e-mail referentes à **nova versão**; não reaproveitar link de versão obsoleta como se ainda estivesse pendente.
- Direcionar para **cliente responsável ou representante com permissão de aprovação no projeto**; destinatários e endereços devem ser validados e vinculados àquela empresa/site. **Não notificar outros clientes ou expor dados de um projeto alheio**.
- Aviso e notificação devem permitir acessar **a versão exata da política no Portal por link autenticado, com controles de autorização e expiração/revogação apropriados**. E-mail poderá trazer identificação mínima do projeto e chamada para revisar, mas **não levar dados pessoais ou informações jurídicas sensíveis desnecessariamente**, nem ser meio de aprovar o documento sem autenticação e aceite expresso.

### 11.2 Operação, auditoria e segurança (requisitos de desenho)

- Um aviso por **evento relevante/versão** em cada canal aprovado, com mecanismos para evitar duplicações por retentativa, processamento concorrente ou reprocessamento do agente IA; notificações de novas versões são eventos legítimos distintos.
- Manter histórico de evento, site/cliente, versão, destinatário autorizado, canal, estado de agendamento/tentativa/entrega conforme retorno efetivo, falha e auditoria, protegendo dados pessoais e seguindo retenção a definir.
- Tratar esses avisos como **comunicações operacionais vinculadas ao fluxo de aprovação requerido**, distintas de marketing; analisar base legal, política de privacidade, canal, endereço, segurança e direitos aplicáveis. **Não confundir com consentimento universal para publicidade**.
- Se e-mail falhar, a notificação interna permanece acessível; falha de e-mail **não é aceite**, não remove a necessidade de aprovação do cliente e deve ficar rastreável para correção. O Portal poderá exibir estado **Aguardando aprovação do cliente**.
- A política só é aprovada quando o cliente realiza ato expresso, independentemente de leitura ou clique no aviso. Se solicitar alteração, gera-se nova minuta e nova notificação após disponibilização; **não publicar versão não aprovada nem marcar site ENTREGUE**.

### 11.3 Escopo não aprovado por inferência

**APROVADO:** notificações **automáticas no Portal e por e-mail** quando a política padrão ou revisão fica disponível para análise, com acesso seguro ao documento; **não** foram aprovados **lembretes além do primeiro de 3 dias úteis e do segundo/último de 7 dias úteis aprovados posteriormente**, nem novos prazos de reenvio, horários detalhados, WhatsApp/SMS, escalonamento por inatividade, aprovação automática, links de aceite sem autenticação nem alteração do conteúdo contratual. A aprovação posterior do primeiro lembrete está na seção 12. Definir esses elementos, modelos de mensagem, provedores, custos e homologação em rodada específica antes de implementar.

**Somente planejamento/documentação. Nenhuma comunicação real enviada.**


## 12. Primeiro lembrete após três dias úteis — Portal e e-mail APROVADO (02/11/2026)

**Pergunta submetida ao responsável:** “Recomendo que, se o cliente não responder, o sistema envie **um lembrete automático após 3 dias úteis**, pelo Portal e por e-mail, sem aprovar nada automaticamente. Você aprova esse primeiro lembrete após 3 dias úteis?” **Resposta: “sim”. REQUISITO FUNCIONAL APROVADO.**

### 12.1 Regra funcional e contagem

1. Para cada **versão específica de Política de Privacidade de um site** colocada à disposição do cliente para aprovação, registrar o marco de início: **disponibilização da versão no Portal e geração da notificação inicial**, após a preparação da política padrão ou da nova versão revisada. A referência formal de relógio, fuso, feriados e exceções será homologada posteriormente.
2. Se a mesma versão permanecer no estado **AGUARDANDO APROVAÇÃO DO CLIENTE** sem manifestação após **3 dias úteis**, emitir **um primeiro lembrete** pelo **Portal Imagine** e **e-mail** para o representante autorizado. O lembrete contém contexto mínimo e link seguro autenticado para **a versão exata**, com opções de **Aprovar** ou **Solicitar Alterações** acessíveis somente no Portal.
3. **Não enviar** o lembrete se, antes do disparo, o cliente **aprovou**, **solicitou alterações**, a versão foi **substituída/cancelada**, houve **suspensão do fluxo** ou o destinatário perdeu autorização. Revalidar status, versão e destinatário no momento de enfileirar/enviar.
4. Quando o cliente solicita alterações, aquela versão deixa de estar aguardando aprovação e **o lembrete pendente é cancelado**. Uma **nova versão disponibilizada** constitui novo ciclo de análise: aviso inicial pelo Portal/e-mail e primeiro lembrete dessa **nova versão após 3 dias úteis**, se continuar pendente. Não disparar o aviso de uma versão obsoleta.
5. **Apenas um primeiro lembrete por versão/ciclo** está aprovado. Evitar duplicação por retentativas de sistemas e múltiplos agentes; registrar evento/versionamento e estado real de envio/erro de cada canal, sem presumir que mensagem foi entregue só porque houve tentativa.
6. **Sem aprovação expressa do cliente não há publicação definitiva da política nem estado ENTREGUE do site pelo agente COM-13.** O lembrete é apenas informativo, não decisão em nome do cliente. A ausência de resposta não autoriza publicação, aceite tácito ou bloqueio adicional não contratado.

### 12.2 Controles de privacidade e pendências

- Os avisos são operacionais e vinculados à aprovação do documento; **não incluir publicidade**. Aplicar base legal, transparência, endereço validado, segurança do link, minimização de dados no e-mail e segregação por empresa/site.
- **Pendente de definição técnica:** calendário de dias úteis (feriados nacionais, estaduais/municipais conforme operação, fuso e horários-limite), expediente de envio, texto do lembrete, limitações e forma de tratamento de falha de provedor/cliente, telas, provedor/custo e eventual deduplicação entre múltiplos sites.
- **REGISTRO HISTÓRICO SUPERADO PARCIALMENTE:** o **segundo e último lembrete após 7 dias úteis desde a disponibilização** foi aprovado posteriormente; continuam NÃO aprovados: recorrência além de dois lembretes, WhatsApp/SMS, escalonamento automático à equipe, aprovação automática, prazo fatal ou penalidade por não responder. Ver seção 13.

**Registro:** primeiro lembrete após 3 dias úteis, por **Portal e e-mail**, **APROVADO**. Planejamento exclusivamente; nenhuma automação ativa nem mensagem enviada.


## 13. Segundo lembrete após 7 dias úteis e site público antes da aprovação da política — decisão posterior APROVADA (02/11/2026)

**Decisão expressa do responsável:** à proposta de **“um segundo e último lembrete após 7 dias úteis contados da disponibilização da política, caso continue sem resposta; depois disso, a pendência ficaria visível para a equipe Imagine, sem envios repetitivos”**, respondeu **“sim”**, com a ressalva determinante de que **“a dependencia do ok do cliente nao impende de colocar o site no ar”**, devendo-se **informar que a parte de LGPD/Política de Privacidade somente será publicada após aprovação do cliente**.

### 13.1 Cadência definitiva dos avisos por versão — APROVADA

| Marco por versão | Canais | Condição |
|---|---|---|
| Dia útil de disponibilização (evento inicial) | Notificação no **Portal Imagine** + **e-mail** | Política padrão individual ou versão revisada pronta para análise |
| **Após 3 dias úteis** | **Primeiro lembrete**, Portal + e-mail | Mesma versão ainda aguardando decisão |
| **Após 7 dias úteis contados da disponibilização inicial** | **Segundo e último lembrete**, Portal + e-mail | Mesma versão ainda sem resposta |
| Depois do segundo lembrete | **Pendência visível no painel da equipe Imagine**, sem novos disparos automáticos por essa pendência | Não autoriza aceite tácito nem cancelamento de direitos |

A contagem de **7 dias úteis é desde a disponibilização da versão**, e não desde o primeiro lembrete. Caso o cliente **aprove, solicite alterações, a versão seja substituída ou o fluxo suspenso**, cancelar avisos remanescentes. Nova versão revisada abre **novo ciclo documental**, com aviso inicial, primeiro/segundo lembrete próprios, se permanecer pendente. A cadência é no máximo **dois lembretes** por versão; evitar duplicidade e registrar canal, data, responsável autorizado e resultado real de entrega. Feriados, fuso e horários de envio devem ser homologados posteriormente. WhatsApp/SMS e lembretes adicionais **não foram aprovados**.

### 13.2 Distinção entre SITE NO AR e POLÍTICA PENDENTE — APROVADA

- **Publicar site (colocar no ar) NÃO depende, por si só, do aceite da Política de Privacidade do cliente.** A publicação técnica/visual do site pode ser realizada **com a política ainda pendente**, desde que a versão do site e as outras condições de publicação/autorização já tenham sido atendidas. A exigência de aceite da **versão do site pelo cliente, quando aplicável (PORTAL-D01)** continua própria e independente.
- Criar status factual separado: **SITE PUBLICADO — POLÍTICA DE PRIVACIDADE AGUARDANDO APROVAÇÃO**. Registrar data/versão da publicação, situação da política, funcionalidades eventualmente desabilitadas, restrições de dados e comunicação ao cliente.
- Informar claramente **ao cliente no Portal e nos avisos operacionais** que o site já está no ar, mas **a Política de Privacidade específica para seu site só será disponibilizada/publicada após aprovação expressa da versão correta**. Não apresentar minuta não aprovada publicamente como política definitiva; não declarar conformidade LGPD integral antes das verificações aplicáveis.
- **Aprovação pendente da política não paralisa a publicação de páginas, layout e conteúdo que possam operar legalmente**. Contudo, **não ativar captura de dados por formulários, newsletter, lead/CRM, cookies/pixels não essenciais, analytics identificáveis e integrações que exijam avisos/consentimentos/condições ainda ausentes**, até que exista base legal apropriada, transparência exigível e controles aprovados. **Mesmo a operação pública mínima envolve potencial tratamento técnico, como logs de servidor**; o agente deve avaliar requisitos de transparência/segurança sobre o tratamento residual. Quando não for possível manter requisitos legais mínimos, suspender as funcionalidades afetadas ou impedir aquela exposição até regularização. **Não usar a pendência do cliente como dispensa das obrigações LGPD.**
- **Somente a Política de Privacidade individual aprovada expressamente poderá ser publicada como versão definitiva**; a aprovação do cliente não é consentimento dos visitantes para marketing. A Imagine poderá usar políticas/modelos homologados para preparar o texto, mas não publicar sua adaptação ao cliente sem o aceite aprovado.

### 13.3 Conclusão técnica, entrega oficial e REL-01

**Separar dois eventos no planejamento**: **(1) SITE NO AR**, permitido se cumprir os requisitos técnicos, contratuais e LGPD cabíveis para a configuração pública efetiva; e **(2) ENTREGA OFICIAL COMPLETA** (COM-13), cujo checklist permanece exigindo que a Política de Privacidade obrigatória tenha sido aprovada e publicada, além dos demais controles.

A decisão atual **retira a aprovação da Política de Privacidade como bloqueio universal à publicação técnica do site**, mas **não declarou que a publicação provisória equivale à entrega oficial integral**. Enquanto a política exigida estiver pendente, manter **SITE PUBLICADO / ENTREGA COMPLETA PENDENTE** e não disparar o marco Dia 0 da REL-01 por publicação isolada. Essa separação preserva a aprovação anterior de que o **Dia 0 é a entrega oficial registrada**, não a mera publicação. A possibilidade de tratar o site já publicado como oficialmente entregue, com pendência legal ainda aberta, deverá ser deliberada separadamente se desejada.

O cliente recebe os avisos de aprovação e a informação do estado da política; **não iniciar mensagens de relacionamento REL-01 sem o evento formal aprovado nem sem suas políticas de comunicação homologadas**. Esta decisão não altera faturamento/contratos automaticamente.

### 13.4 Aprovação, pendências e revisão especializada

**APROVADO:** **segundo e último lembrete após 7 dias úteis do envio inicial**, Portal/e-mail; após isso, pendência no painel Imagine sem novos lembretes repetitivos; **site pode ficar no ar sem o aceite da política** quando a configuração pública é segura/legalmente adequada; **política individual somente após aceite expresso**; cliente será informado do status.

**Não aprovado por inferência:** ausência de requisitos mínimos de transparência LGPD, ativação de formulários/pixels sem base/aviso aplicável, consentimento tácito, política publicada sem aceite, mudança automática da definição de **ENTREGUE** ou início da REL-01 por publicação isolada. **Ainda pendentes:** desenho exato do banner de estado para cliente, análise jurídica de transparência mínima para componentes técnicos do site público, regras de ativação de funcionalidades após aceite, fuso/feriados/horários, mensagens, operação/homologação técnica e alçadas para exceções. Nenhuma publicação, programação ou envio real iniciados.
