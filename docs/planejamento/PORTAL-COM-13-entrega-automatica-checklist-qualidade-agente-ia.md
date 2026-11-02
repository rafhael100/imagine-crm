# PORTAL-COM-13 — Entrega Automática de Sites por Agente de IA e Checklist de Qualidade

**Status:** **DIRETRIZ FUNCIONAL APROVADA em 02/11/2026.** O responsável corrigiu expressamente a aprovação anterior de confirmação humana de cada entrega: **“a entrega sera considerada automatica, assim que estiver tudo ok, dara o projeto omo entregue. Seria importante ter um checklist para que o agente de IA possa verificar todos os pontos e estando tudo ok, dar como entregue”**. A **confirmação manual da equipe Imagine deixa de ser pré-requisito padrão**. Um agente autorizado deverá validar integralmente os requisitos e **marcar automaticamente o site como ENTREGUE quando TODOS os controles obrigatórios aplicáveis estiverem OK e houver aceites obrigatórios válidos**; exceções críticas, não verificáveis ou fora da autonomia **não podem ser aprovadas por IA**.
**Fase:** exclusivamente planejamento e documentação; sem criar agentes, programar deploy, testar site real, alterar status de projetos, publicar sites ou enviar comunicações reais.
**Escopo:** Onepage, Landing Page e futuros serviços de criação de sites Imagine, respeitadas as regras contratuais de cada modalidade.
**Documentos relacionados:** [PORTAL-REL-01 — jornada pós-entrega](PORTAL-REL-01-jornada-pos-entrega-onboarding-30-dias.md), [PORTAL-D01 — aceite eletrônico](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-COM-12 — relacionamento](PORTAL-COM-12-relacionamento-inteligente-retencao-expansao-clientes.md), [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md), [IN-123 — integração CRM](IN-123-painel-integracoes-sites-crm.md), [IN-131 — autonomia de agentes](IN-131-niveis-autonomia-agentes-tecnicos.md), [IN-155 — procedimentos homologados](IN-155-homologacao-inteligente-procedimentos-agentes.md). Histórico funcional IN-091–IN-101 (particularmente IN-093–IN-098, IN-100 e IN-101) documentado em docs/decisoes.md no ramo docs/integracao-site-crm-basico.

## 1. Regra principal aprovada: entrega por IA quando tudo estiver validado

1. A equipe e agentes de produção preparam, revisam e publicam a **versão exata e aprovada do site**, segundo os fluxos e limites de autonomia definidos.
2. Um **agente de IA de verificação de entrega** (função conceitual; nome/catálogo técnico ainda a definir) executa o **checklist da versão que será entregue** e obtém **evidências verificáveis** de testes. Análises subjetivas sem teste confiável não autorizam aprovação automática.
3. **Somente quando todos os itens obrigatórios aplicáveis estiverem APROVADOS**, nenhum bloqueador estiver aberto e **o aceite explícito do cliente para a versão, quando exigido, estiver registrado e válido** (PORTAL-D01/IN-095), o agente **muda automaticamente o estado do projeto para ENTREGUE no Portal**, com data/hora/fuso, IDs de versão, do checklist e do conjunto de evidências.
4. **A entrega automática é posterior aos aceites obrigatórios**: a IA **não simula, substitui ou dispensa** a aprovação expressa do cliente; silêncio, ausência de resposta ou apenas abertura do link não são aceite.
5. A equipe Imagine passa a **supervisionar, revisar pendências e tratar exceções**, **sem precisar confirmar manualmente todos os projetos bem-sucedidos**. A aprovação humana continua obrigatória para decisões e ações classificadas críticas, sensíveis, irreversíveis ou fora das permissões dos agentes, conforme IN-098, IN-131 e IN-155.
6. Ao gravar **ENTREGUE**, o Portal emite evento oficial de entrega, atualizado no painel do cliente, que **passa a ser o marco único do Dia 0 da REL-01**, com ações previstas para os dias 0/3/7/15/30 quando seus respectivos canais e políticas forem homologados. **Não iniciar REL-01** por publicação isolada ou aprovação isolada sem o estado ENTREGA confirmado automaticamente.
7. A regra **substitui expressamente a aprovação anterior** segundo a qual **a equipe Imagine teria que confirmar cada entrega no painel administrativo**. Histórico anterior é preservado como supersedido, não apagado.

## 2. Checklist funcional de qualidade — base aprovada em princípio, parâmetros técnicos a homologar

Os critérios abaixo operacionalizam o pedido de **verificar todos os pontos** e respeitam IN-091–IN-101. Cada item precisa ter classificação **OBRIGATÓRIO / CONDICIONAL / NÃO APLICÁVEL JUSTIFICADO**, resultado objetivo, verificador, evidência, data/versão e critério de aceitação. Um status **SEM EVIDÊNCIA / NÃO TESTADO / FALHOU** em qualquer item obrigatório bloqueia a entrega. O conteúdo exato, métricas/thresholds e métodos por tipo de produto serão homologados antes da implementação.

| Grupo | Verificação candidata para homologação | Caráter |
|---|---|---|
| Contrato e versão | Empresa/projeto correto; escopo/serviço e materiais correspondentes; versão e URL exatas; **aceite explícito válido do cliente para a versão exigida** | Obrigatório |
| Implantação/publicação | Versão aprovada efetivamente implantada no destino previsto; site acessível na URL pública e sem falha crítica | Obrigatório |
| Domínio, DNS e SSL | Domínio correto e autorizado; resolução; HTTPS/certificado válido; não quebrar configurações MX, SPF, DKIM, DMARC e e-mails preexistentes | Obrigatório conforme domínio/ambiente; verificações de e-mail condicionais |
| Conteúdo e identidade | Nome, marca, contatos, textos, imagens, páginas/seções e materiais correspondentes ao briefing/versão aprovada; sem informações inventadas pela IA | Obrigatório conforme contrato |
| Visual e responsividade | Layout corresponde à versão aprovada; funcionamento legível e interativo em amostras representativas de mobile/tablet/desktop; ausência de transbordamento grave | Obrigatório; tamanhos/testes a definir |
| Navegação | Menus, âncoras, links internos/externos, botões, CTAs e rotas essenciais funcionam, sem erros bloqueadores | Obrigatório |
| Formulários e captação | Campos, validação, privacidade e antispam apropriados; testar evento sintético, persistência segura e confirmação end-to-end; não marcar recebimento falso | Obrigatório quando houver formulário |
| Destino no CRM | Organização/tenant e site/formulário/versão corretos; lead de teste chega ao destino certo e é confirmado sem duplicação; limpar ou marcar dados sintéticos segundo política | Condicional à integração contratada; obrigatório se aplicável |
| Segurança | Não expor tokens, segredos, credenciais privadas ou dados de outros clientes; cabeçalhos e controles exigidos, permissões corretas; ausência de achados críticos conhecidos | Obrigatório conforme arquitetura |
| Privacidade e acessibilidade | Aviso de privacidade e tratamento adequado dos dados dos formulários; recursos de acessibilidade pertinentes; imagens com alternativas quando necessário | Obrigatório/condicional ao escopo e requisitos aplicáveis |
| SEO básico | Title/meta, títulos estruturados, sitemap/indexação conforme contrato, robots/canonical quando aplicáveis; não prometer ranking em busca | Obrigatório quando SEO básico incluso |
| Desempenho | Carregamento aceitável em condições e métricas homologadas, sem erro de runtime grave ou recursos principais quebrados | Obrigatório; limites métricos a definir |
| Terceiros e integrações | Analytics, pixels, mapas, chatbot, agendamentos, APIs e extensões funcionam **se efetivamente contratados e autorizados**; consentimentos adequados | Condicional, não criar obrigação de integrações não contratadas |
| Backup e recuperação | Versão/código/artefatos e configurações essenciais recuperáveis conforme política IN-101; ponto de recuperação e verificações de integridade disponíveis | Obrigatório segundo políticas técnicas a aprovar; não confundir GitHub com backup total |
| Qualidade final | Sem incidentes críticos abertos, sem pendência impeditiva de aceite/revisão, domínio e configurações corretos; resultado integral do checklist é consistente e recente para a mesma versão | Obrigatório |

**Não há aprovação de números de performance, tamanhos fixos de tela, duração mínima de teste ou SLA por esta decisão.** Esses parâmetros deverão ser homologados antes de executar o checklist em produção. O checklist **não autoriza declarar OK por julgamento textual da IA sem teste/evidência**.

## 3. Estados e mecanismo de decisão — proposta de implementação

Estados conceituais: **EM PRODUÇÃO** → **AGUARDANDO ACEITE DA VERSÃO** → **PUBLICADO/EM VALIDAÇÃO FINAL** → (**PENDÊNCIAS DE ENTREGA** ou **ENTREGUE AUTOMATICAMENTE**). Dependências, ordem de publicação/aceite e existência de prévia homologação podem variar por produto/contrato; não alterar os fluxos de aceite válidos.

Cada checagem retorna status **APROVADO**, **FALHOU**, **NÃO TESTADO/SEM EVIDÊNCIA**, ou **NÃO APLICÁVEL com motivo validado**. **Todos os obrigatórios aprovados, condicionais aplicáveis aprovados, nenhum bloqueador, nenhuma autorização/aceite pendente** = entrega elegível à transição automática. Se qualquer critério falha, permanece não entregue e abre pendência técnica com responsável, evidência e tentativa segura de correção.

**Não aplicável não pode ser usado para ocultar falha**: exige justificativa baseada na oferta contratada, com rastreabilidade. A IA não altera por conta própria o que é obrigatório, os critérios, a política de segurança ou a exigência de aceite.

## 4. Reavaliação, correções e intervenção humana

- Correções simples e seguras podem ser realizadas automaticamente **apenas dentro de escopo e playbook homologados**, com verificação independente posterior.
- Falta de material, informações conflitantes, aceites pendentes, falhas de formulário ou integração, segurança crítica, DNS sensível, domínio/credenciais, despesas e intervenção fora da autonomia **bloqueiam a entrega e seguem fila da equipe Imagine para resolução/aprovação pertinente**.
- Intervenções críticas requerem **autorização humana expressa para a ação**, mesmo que o objetivo final de marcar como ENTREGUE seja automático depois de concluir as verificações e atos autorizados.
- Falha transitória de provedor não pode virar “APROVADO” por suposição; retentar com limites e registrar logs. Não disparar eventos de entrega repetidos ao mesmo projeto/versão por reexecução do checklist.
- Atualização de versão relevante após o último aceite ou teste pode invalidar o checklist/aceite anterior conforme PORTAL-D01 e exigir revalidação e novo aceite quando aplicável.
- Se detectar erro **após** a entrega, abrir incidente, comunicar conforme política de suporte e acionar revisão/correção controlada; **não apagar retroativamente o histórico de entrega**. Política de reversão de estado e de reinício da REL-01 precisa ser especificada.
- Um administrador Imagine poderá **inspecionar relatório, pendências e evidências e intervir nos limites autorizados**, sem poder aprovar entrega insegura por ação genérica sem cumprir aceites/controles; eventual exceção controlada ainda necessita de aprovação explícita de regras, risco e alçadas.

## 5. Registro auditável e evento REL-01

Armazenar, em modo seguro: IDs de empresa/contrato/projeto/site, domínio e URL, versão da publicação, hash/referência do checklist e políticas aplicadas, cada teste e status/evidência, identificador da automação/agente e sua versão de procedimento, logs essenciais, aceite da versão e autorizador, exceções/aprovações humanas de alto risco, horário/fuso do evento final, resultado **ENTREGUE**, responsável técnico pelo procedimento homologado, tentativas de reprocessamento e notificações internas relevantes.

**Evento único por entrega válida** (idempotente, transacional; consumidor REL-01 deduplica) para registrar **Entrega Oficial Confirmada Automaticamente** e iniciar calendário REL-01. **Registrar entrega ≠ enviar automaticamente e-mail/WhatsApp:** os canais, bases legais e autorização de envio ainda não foram aprovados. O cliente poderá ver o estado no Portal; comunicação externa só quando houver política aprovada.

## 6. Governança e pendências técnicas, sem voltar à confirmação manual universal

**Aprovado agora:** automatizar a entrega após checklist integral, com agente de IA autorizado, sem necessidade de clique final da equipe Imagine em cada caso. **Permanece obrigatório:** aceite expresso do cliente para a versão quando aplicável, e aprovações humanas de mudanças críticas/fora da autonomia conforme decisões anteriores.

**Ainda é necessário especificar/homologar:** identificação e autorização do agente; checklist final por produto e campanha; quais itens são condicionais; thresholds; testes/evidências; pontos de liberação e integração com publicação/aceite; supervisão e histórico; idempotência do evento; publicação e status anteriores; retentativas e falhas de infraestrutura; notificações; consentimentos da REL-01; cronograma de validação final e limite de custo, logs e retenção. **Não marcar projetos como entregues até haver implementação aprovada, testada e homologada.**

## 7. Alteração do registro anterior (supersessão)

O registro anterior REL-01/COM-12/D01/PORTAL-01 de **“equipe Imagine confirma manualmente a entrega no painel administrativo” foi substituído expressamente por esta decisão posterior**. O requisito de ter **site publicado, funcional e com os aceites necessários** permanece; muda **quem confirma a entrega e como**: um agente de IA verifica, e a plataforma registra automaticamente a entrega se os critérios estiverem comprovados. A equipe supervisiona e recebe exceções, sem confirmação manual universal.

**Conclusão:** **AUTOMATIZAR A ENTREGA COM CHECKLIST DE QUALIDADE E EVIDÊNCIAS** é diretriz funcional aprovada; o método de teste e critérios quantitativos ainda requerem detalhamento. Somente planejamento/documentação.
