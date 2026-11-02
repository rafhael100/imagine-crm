# PORTAL-COM-15 — Central de Documentos Legais e Consentimentos

**Status:** **MÓDULO FUNCIONAL APROVADO em 02/11/2026.** O responsável respondeu **“ok”** à pergunta específica sobre aprovar uma **Central de Documentos Legais e Consentimentos**, com **gestão administrativa, versionamento e histórico de aceites**, aplicável ao Portal Imagine e aos sites dos clientes, com documentos apropriados a cada cliente/projeto e aprovação do responsável quando necessária.
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
