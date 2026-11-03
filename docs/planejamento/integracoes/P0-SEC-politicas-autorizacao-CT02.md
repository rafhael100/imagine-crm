# P0-SEC — Políticas de autorização contextual CT-02 por operação

**Registro:** 2026-11-03. **Estado:** **MINUTA PARA ANÁLISE E APROVAÇÃO DOCUMENTAL**, derivada das regras aprovadas de CT-02, INT-R01/R02 e [24 operações aceitas como base](P0-DEC-aprovacao-OP24-base.md).
**Aceite anterior:** [CT-02 foi aceita como base documental](P0-DEC-aprovacao-CT02-base.md); **não** estão aprovadas novas regras específicas de MFA, perfis, limiares, alçadas ou mecanismos físicos propostos nesta minuta.
**Escopo:** completar P0-OWN-01 e P0-SEC-01 por referência operacional; não é novo IAM, cofre, motor de políticas, Central de Aprovações ou serviço.
**Fase:** planejamento/documentação exclusivamente; sem programação, homologação real, contratação, produção ou despesa.

## 1. Princípios aprovados — aplicar sem abrir permissões novas

1. **Identidade e Segurança:** fonte de autenticação, MFA quando aplicável, permissões centrais, controles e segregação; INT-02 representa autorização externa e gestão de escopos, sem se tornar IAM paralelo.
2. **Menor privilégio:** tenant, conta, identidade, finalidade, operação tipada, recurso/campo, versão, ambiente, origem/destino e estado devem ser verificados conforme a competência e o contexto.
3. **Separação decisória:** solicitante, autoridade de aprovação, executor técnico e confirmador oficial são papéis distintos. IA não concede privilégio nem substitui titularidade/consentimento legítimo.
4. **Aprovação específica:** não emitir a mesma aprovação humana duas vezes para a mesma decisão válida; aprovações de **atos diferentes** permanecem necessárias.
5. **Revalidação contextual:** em fronteiras protegidas e retomadas, inclusive tarefas assíncronas, mudança de versão/conexão e compensação; comprovação do contexto antes de nova escrita.
6. **Restrição em falha:** indisponibilidade do autorizador, dúvida de contexto ou revogação relevante não podem ser interpretadas como autorização nova; manter fatos anteriores e pendências rastreáveis.
7. **Segredos:** cofre IN-151 e identidades técnicas escopadas; nenhuma credencial privilegiada em browser, IA, prompt, log ou payload público.
8. **Não confundir autorização com negócio concluído:** CT-02 autoriza o ato, CT-03 solicita, CT-04 relata; somente o módulo proprietário confirma o fato de sua competência. Auditoria Geral preserva trilhas.

## 2. Organização proposta da avaliação — não define um motor novo

A sequência abaixo é uma **lista de verificação documental** a ser incorporada pelas fronteiras e serviços já existentes, não uma nova engine:
- **P01 Identidade:** origem e sujeito humano/técnico autenticados pelo serviço central; escopo da delegação verificado.
- **P02 Pertencimento:** tenant, conta, vínculo de conexão e ambiente correspondem ao recurso oficial, não apenas ao tenant declarado pelo cliente.
- **P03 Contrato:** ação tipada/versão, estado permitido, capacidade homologada e operação autorizada.
- **P04 Acesso mínimo:** RBAC/recursos/campos/finalidade, segregação entre contas e classificação de sensibilidade.
- **P05 Autorização externa:** token/escopo do provedor dentro de vigência, quando aplicável; sem assumir que isso concede poder interno.
- **P06 Aprovação:** se a operação/efeito exige alçada, verificar decisão específica vigente e compatível, sem reuso fora do escopo.
- **P07 Consistência:** estado esperado, versão, idempotência, risco de duplicação e concorrência, especialmente após retomada.
- **P08 Evidência:** decisão permitida/negada, responsável, finalidade, operação e correlação registradas de forma minimizada na Auditoria Geral.

**Dependência de política:** quais desses passos são necessários em cada ação, MFA adicional, janela de aprovação, nível de risco e transição de estado permanecem para especificação/validação; não presumir perfis de “superadmin”.

## 3. Matriz das 24 operações — pontos de revalidação e autoridade

| Ficha | Categoria | Momento de revalidar (proposta) | Escopo/verificações obrigatórios segundo o tipo | Quando exigir alçada adicional (a definir) | Fonte de confirmação |
|---|---|---|---|---|---|
| OP-01-CAD | Configuração de conexão | alteração inicial e confirmação de vínculo | Tenant+conta+capacidade homologada; mudança de destino exige autoridade correspondente | Alteração de vínculo com impacto em pendências / dados de outros contextos | INT-01 (estado de conexão); negócio continua com proprietário |
| OP-01-CIC | Suspensão/desativação de conexão | antes de restringir, notificar e retomar | Conexão exata, impacto em operações ativas e fronteiras de tenant | Suspensão com risco operacional/contratual ou alteração irreversível | INT-01; confirmação de efeitos prévios por proprietários |
| OP-02-AUT | Credencial ou autorização externa | concessão, renovação e utilização | Escopo externo+identidade e delegação legítimas; segredo no cofre IN-151 | Ampliação de escopos, acesso sensível ou mudança de autorização | INT-02/Segurança; provedor para fato externo |
| OP-02-REV | Revogação | na ordem e antes de novas operações | Credencial/tenant/provedor corretos e ator competente; registro dos impactos | Revogação sensível/interruptiva conforme política ou contenção segura homologada | INT-02/Segurança; confirmar revogação remota separadamente |
| OP-03-VAL | Validação de dados | ao selecionar origem/destino e exibir prévia | Leitura dos campos de origem/destino, classificação e minimização | Prévia de dados sensíveis ou extração fora de escopo | INT-03 confirma validação, não escrita |
| OP-03-VER | Mudança de mapeamento | antes de publicação e uso em instância | Versão compatível, responsável pelos campos e ambiente autorizados | Mudança incompatível ou impacto em fluxos ativos | INT-03 registra versão; proprietários confirmam escritas |
| OP-04-ENV | Comando técnico de escrita | antes de transmitir e antes de escrita oficial | CT-03 tipado, estado esperado, autorização do proprietário e conector vigente | Efeito de negócio crítico na alçada do módulo competente | INT-04 transporte; proprietário confirma o efeito |
| OP-04-RSL | Resultado técnico | ao ingerir retorno e correlacionar | Fonte autenticada, comando/tenant/versão correlacionados | Retificação de negócio ou alegação de confirmação fora da competência | INT-04 registra técnico; proprietário confirma negócio |
| OP-05-APP | Aplicativo integrado | ao habilitar e ao alterar escopos | App/tenant/plano, identidade e escopos; sem acesso presumido | Ampliação de permissão ou mudança sensível de aplicação | INT-05 estado de app; Segurança identidade |
| OP-05-API | Chamada API pública | em cada operação protegida | Plano elegível, trial sem API pública; tenant/recurso/ação autorizados | Escrita de negócio conforme regras do proprietário | INT-05 acesso; proprietário efeito |
| OP-06-IN | Webhook de ingresso | a cada recebimento e encaminhamento | Assinatura, origem, evento, versionamento, tenant, anti-replay | Ato de negócio decorrente exige sua autorização independente | INT-06 recepção; Eventos centrais; proprietário efeito |
| OP-06-OUT | Webhook de saída | em assinatura, seleção de payload e entrega | Destino cadastrado, campos e evento permitidos, sem segredo indevido | Mudança sensível de assinatura/escopo | INT-06 entrega técnica; destino por contrato |
| OP-07-SYNC | Sincronização contínua | na configuração, retomada e escrita por campo | Autoridade fonte/destino, campo/entidade/direção e checkpoint | Conflito, exclusão, mudança de propriedade ou dados sensíveis | INT-07 checkpoint; proprietário alterações |
| OP-07-RET | Retomada de sincronização | a cada retomada após suspensão/erro | Conexão, credencial, política e versão atuais; posição comprovada | Retomada com risco de duplicação ou conflito de negócio | INT-07 processo; proprietário dados |
| OP-08-IMP | Importação ou restauração | prévia, confirmação e cada lote protegido | Cliente autorizado, origem/destino/manifesto, versão e permissões sobre dados | Mesclagem sensível, sobrescrita/destruição ou migração cross-tenant | INT-08 lote; proprietário persistência |
| OP-08-EXP | Exportação/backup | na solicitação e na emissão de acesso ao arquivo | Identidade com direito de exportar, escopo, finalidade, arquivo seguro | Exportação sensível/volume excepcional conforme política futura | INT-08/IN-118 arquivo; Auditoria Geral acesso |
| OP-09-INI | Processo multissistema | instanciação, etapa protegida e retomada | Modelo homologado, tenant/estado, autorização por etapa e proprietário | Cada ato de negócio sensível requer decisão competente | INT-09 coordenação; proprietários efeitos |
| OP-09-COM | Compensação | antes de solicitar, executar e verificar | Efeito original, reversibilidade, titularidade, escopo e aprovação específica | Toda compensação de negócio condicionada à competência/alçada do dono | INT-09 coordenação; proprietário compensação |
| OP-10-DIA | Diagnóstico técnico | acesso a telemetria e dados protegidos | Papel técnico autorizado, tenant/ambiente e minimização | Acesso a dados sensíveis além de rotina de diagnóstico | INT-10 investigação; Observabilidade central |
| OP-10-REC | Recuperação técnica | pré-playbook, execução e retomada | Procedimento homologado, menor privilégio, risco da ação e estado do recurso | Intervenção crítica ou não homologada requer autoridade humana aplicável | INT-10 saúde; proprietários efeitos |
| OP-11-HOM | Homologação/publicação | submissão, aprovação e distribuição por versão | Revisor/publicador qualificado, versão/artefato, testes isolados e ambiente | Publicação exige aprovação humana apropriada; sem terceiro publicar | INT-11 catálogo e versão |
| OP-11-SUS | Suspensão/revogação de versão | ao bloquear novas ativações e tratar ativos | Alcance por versão, clientes impactados, autoridade de segurança e histórico | Descontinuação de alto impacto requer alçada, salvo contenção segura homologada | INT-11 catálogo; INT-01 conexões |
| OP-12-INV | Investigação de auditoria | em cada consulta e ampliação de escopo | Finalidade, caso, tenant, classificação e direito de ver | Acesso ampliado a dados sensíveis requer controles centrais | Auditoria Geral comprova fatos; INT-12 consulta |
| OP-12-AUD | Exportação de auditoria | na seleção e no download/entrega | Permissão específica de exportação, finalidade, minimização e retenção | Extração sensível/abrangente exige alçada conforme política | Auditoria Geral exportação/auditoria da auditoria |

**Regra de leitura:** “quando exigir alçada adicional” é um **gatilho candidato de revisão**, não decisão nova de exigir MFA, aprovação humana ou bloqueio em todas essas situações. A existência de um ponto de revalidação não significa que será necessário um novo consentimento ou uma nova aprovação a cada tentativa.

## 4. Vigência, revogação e aprovações em operações assíncronas

| Cenário | Conduta documental proposta | Competência |
|---|---|---|
| Token externo revogado depois de enfileirar comando | Nova ação protegida fica impedida; efeitos já transmitidos passam por reconciliação | INT-02/Segurança; proprietário para negócio |
| Aprovação da mesma decisão ainda válida | Preservar referência, **sem pedir segunda aprovação idêntica**, mas revalidar escopo e condições atuais | Central de Aprovações |
| Mudança de destinatário CRM ou troca de tenant | Não redirecionar silenciosamente entregas/comandos anteriores; migração depende de operação própria | Módulo de vínculo, INT-01 e proprietários |
| Expiração do prazo técnico da operação | Não confundir timeout com revogação da decisão de negócio nem cancelar efeito já aplicado | Motor de Datas e Prazos / proprietário |
| Mudança de versão da política/conector | Não usar política nova para ampliar privilégio já emitido nem presumir continuidade de autorização incompatível | Segurança, INT-11 e proprietário |
| Execução N2 de playbook seguro previamente homologado | Continuar apenas nos limites da autorização vigente; IA não escolhe poder/ambiente livremente | Autorizador central e agente técnico escopado |
| Intervenção N3 crítica | Aprovação humana específica quando exigida, evitando duplicação de decisão; não substituir alçada de negócio | Central de Aprovações e proprietário |
| Falha de auditoria essencial | Não declarar execução irrestritamente concluída sem evidência requerida; classificar e escalar | Auditoria Geral / Incidentes |

## 5. Condições de fronteira obrigatórias

- **Trial e API:** trial bloqueia API pública, mantendo as integrações nativas elegíveis, sem permitir que INT-05 contorne a política por uma credencial de serviço.
- **Sites e leads:** identificação pública de formulário não é segredo; backend valida vínculo site→CRM, origem e dados. Submissões anteriores preservam destino autorizado, e posse de token de infraestrutura não torna usuário de outro tenant autorizado.
- **Portabilidade:** IN-118 garante importação/exportação/restauração compatível e autoatendimento essenciais em todos os planos, inclusive Básico; validações de risco não podem eliminar a funcionalidade aprovada. Franquia de anexos esgotada impede somente novos uploads, não exportação de dados existentes.
- **Assistência de IA:** modelos analisam e recomendam; um playbook técnico homologado executa com identidade própria, regras independentes da IA e audita resultado.
- **Auditoria:** INT-12 consulta a Auditoria Geral sob autorização, sem trilha concorrente nem acesso de leitura/exportação universal. Retenções técnicas IN-162 não são automaticamente as retenções de documentos oficiais de negócio.

## 6. Casos de validação documental (não executados)

| ID proposto | Verificação futura | Resultado de aceite esperado | HT aprovada |
|---|---|---|---|
| SEC-V01 | Tenant diferente do dono do recurso | Bloqueio e registro minimizado, sem vazamento | HT-13 |
| SEC-V02 | Revogação após emissão de evento e antes da execução | Nova ação protegida negada; efeito anterior mantido pendente/confirmado conforme fonte | HT-02, HT-16 |
| SEC-V03 | Alçada de aprovação referente a outra finalidade | Sem reuso indevido, exigência da autoridade correta | HT-15 |
| SEC-V04 | Um mesmo ato já aprovado é reprocessado com segurança | Não duplicar aprovação humana da mesma decisão; revalidar condições | HT-09, HT-15 |
| SEC-V05 | Cliente Básico exporta dados com anexos em 100% da franquia | Exportação permitida conforme privilégios; upload novo segue bloqueado | HT-07, HT-17 |
| SEC-V06 | Webhook assinado solicita efeito sem permissão de negócio | Webhook pode ser recebido; efeito não executado sem autoridade | HT-05 |
| SEC-V07 | Agente IA sugere operação de alta criticidade | Sugestão registrada; ausência de aprovação impede execução crítica | HT-15 |
| SEC-V08 | Conector suspenso quando execução estava pendente | Bloqueio de novo despacho incompatível; reconciliação das tentativas anteriores | HT-18 |

## 7. Pendências para fechamento P0-SEC-01 / P0-OWN-01

| ID | Definição ainda necessária | Situação |
|---|---|---|
| SEC-P01 | Vocabulário de direitos por módulo/entidade/campo e vínculo às permissões centrais existentes | Pendente |
| SEC-P02 | Matriz efetiva de papéis e delegações por empresa, unidade, conta e usuário | Pendente |
| SEC-P03 | Situações e alçadas que exigem step-up/MFA, revisão de risco e aprovação humana | Pendente |
| SEC-P04 | Escopo/validade/reuso/revogação de aprovação por operação e efeito | Pendente |
| SEC-P05 | Contrato de avaliação de política e campos físicos de evidência | Pendente |
| SEC-P06 | Controles de contingência de autorização e integridade da Auditoria Geral | Pendente |
| SEC-P07 | Fonte integral das aprovações individuais e critérios HT completos | Pendente |

**Conclusão:** matriz de política CT-02 documentada para revisão; **P0-SEC-01 e P0-OWN-01 não estão encerrados**. Nenhum perfil, escopo ou alçada implantado, nenhuma programação iniciada.
