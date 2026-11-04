# CRM-CORE01-D04 — Contrato de membership, papéis, convites e assentos (Etapa 2, proposta v0.1)

**Data:** 04/11/2026.  
**Situação:** **PROPOSTA DOCUMENTAL PARA ANÁLISE, NÃO APROVADA**. CORE01-D04 continua ABERTA, assim como CORE01-D05..D07, CORE02-D04..D10, CORE07-D04..D10, SEG-01/SEG-02 e todos os bloqueios transversais.  
**Autorização recebida:** “ok” para continuar aprofundando o planejamento, **não** aprovação desta proposta nem escolha de alternativa técnica.  
**Escopo:** membership (identidade global↔tenant), papéis/escopos, convites de 7 dias, contagem de vagas, mudança de responsáveis, reativação/desligamento e contrato de auditoria. **Não** definir tabelas/índices/DDL, tokens de produção, schemas de API, preços/cotas pagas finais, ferramentas ou implementação.  
**Proibições:** sem programação, Codex, criação de usuários, envio de convites, execução de testes, mudanças de acesso, dados reais, infraestrutura, compras ou cobranças.

## 1. Fontes e normas funcionais já aprovadas

- [CORE01-D01..D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): uma identidade global pode ter vínculos independentes em vários tenants; uma organização CRM é um tenant; backend Laravel + RLS PostgreSQL protegem recursos tenant-bound.
- [CORE02-D01..D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md): direito depende cumulativamente de vínculo ativo, tenant, entitlement/plano, papel, escopo próprio/equipe/empresa, recurso, ação, campo, situação e risco; deny by default, sem autoconcessão. **CORE02-D04..D10** ainda em discussão.
- [CORE07-D01..D03](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md): convite por e-mail e link seguro válido **7 dias**, pessoa cria login ou usa identidade existente; convite pendente não é membership operacional; controle de assentos **por tenant**, equipe múltipla e desligamento restrito à empresa, preservando histórico e outros vínculos. **CORE07-D04..D10** ainda em discussão.
- [CORE04-D01..D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md): MFA obrigatório para administrador geral/empresa, inclusive Básico; funcionário opcional salvo política empresarial; recuperação/step-up concreto pendente.
- [CORE08-D01..D03](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md): quota de usuário humano **Básico = 1** por tenant, independentemente de vários sites; **trial de referência = 2 usuários**, CNPJ único para trial; profissionais agendáveis, equipes e serviços técnicos não são vagas de login; preços e inclusões finais sujeitos à revisão.
- [CORE03-D01..D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md) e [CORE06-D01..D03](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md): jobs revalidam poderes para novos efeitos; identidades causais, executores e fatos anteriores permanecem rastreáveis, sem apagar autoria; Auditoria Geral é fonte de trilha.
- [Matriz CORE-P0 Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md), [CORE01 D04..D07 — abertura da especificação](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md), [CORE07 v0.1](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md), [CORE02 v0.1](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md).
- Referências históricas [LEG-04](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) e [LEG-13](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md), sujeitas à precedência dos aceites posteriores.

**Não redeliberar** os itens acima: este documento propõe apenas como estruturar a decisão técnica pendente, sem homologá-la automaticamente.

## 2. Vocabulário contratual — sem nomes físicos de tabela aprovados

| Objeto lógico | Significado e invariantes | O que não equivale |
|---|---|---|
| `identity_ref` | Identidade global e fatores de autenticação; reutilizável entre várias organizações quando legitimamente autorizada | Cadastro CRM duplicado a cada site, assinatura, papel ou titularidade de empresa |
| `tenant_ref` | Organização CRM isolada, proprietária de recursos e quotas | CNPJ, domínio, plano ou conta do Portal |
| `membership_ref` | Direito de **participação** de identidade em tenant após admissão legítima; registrar estado/vigência e histórico | Convite enviado, login global ou contrato do Site |
| `role_assignment_ref` | Atribuição de papel/escopo ao vínculo (não à pessoa em todos os tenants), com proveniência e revisão | Entitlement comercial, superadmin, autorização de campo por mera função |
| `team_membership_ref` | Relação da pessoa, equipe e tenant para escopos e atribuições; múltiplas equipes possíveis | Nova vaga humana, novo usuário de login ou acesso a outra organização |
| `invitation_ref` | Intenção emitida por admin autorizado, para destinatário e tenant especificados, com prazo de **7 dias** e perfil solicitado | Vínculo ativo, permissão concedida, confirmação de MFA ou assento ocupado |
| `seat_entitlement_ref` | Quota de identidades humanas autenticadas permitidas por tenant e plano/contrato vigente | Profissionais agendáveis, agentes IA, integrações, caixas ou número de sites |
| `membership_audit_ref` | Histórico de emissões, cancelamento/aceite, mudanças de papel/equipe, desativação e readmissão | Segredo de convite, token, credencial ou snapshot irrestrito de dados pessoais |
| `causal_actor_ref` | Pessoa ou autoridade que iniciou atribuição/job; executor técnico distinto | Sessão permanente do iniciador ou permissão que sobrevive à revogação |

Campos são referências **semânticas** de contrato; não são decisões de PK, UUID, índice, política RLS, cronograma de migração ou API.

## 3. Modelo de estados lógico proposto para confronto

### 3.1. Convite e vínculo devem ter ciclos separados

**Convite** (estados candidatos, sujeitos à validação): `em_preparacao` → `emitido_pendente` → `aceito` **OU** `expirado` / `cancelado` / `substituido`. A mudança `emitido_pendente→aceito` só poderá resultar em participação ativa após **validação de identidade e destinatário, tenant, papel, MFA aplicável, titularidade/autorização, cota e concorrência**. Expirado/cancelado não pode se tornar válido pela reentrega de e-mail, nova tentativa ou reuso do link.

**Membership** (estados de referência, não enums): `ativa`, `desativada_na_empresa`, `suspensa_por_politica` ou `encerrada` **se** houver competência para distinguir; `aguardando_convite` não é, por si, membership operacional. Histórico de mudança, reconvites e desligamentos preservado sem obrigar apagar identidade global.

**Atribuição de papel/equipe**: relacionar-se-á ao vínculo **ativo**, com regras de vigência e revalidação próprias; remoção de papel não deverá alterar dados históricos, mas impede novos atos protegidos que dependam dele.

**Assento**: é **capacidade por tenant e período/estado**, não login adicional nem pagamento implícito. A semântica concreta de “convite pendente reserva vaga?” **não está aprovada** e pertence a CORE07-D05.

### 3.2. Transições a auditar / exemplos

| Origem → evento → destino | Competência que deve confirmar | Invariante |
|---|---|---|
| Admin de tenant envia convite legítimo | IAM/administração do tenant, após política do plano | Não concede membership ativa; não ultrapassa direitos do emissor |
| Convidado autenticado aceita | IAM, tenant e plano revalidados no instante efetivo | Um único vínculo ativo legítimo; convite antigo não permite dupla admissão |
| Mesmo login aceita acesso a tenant B | IAM cria/atualiza apenas vínculo em B | A não perde sessão, dados, titularidade ou quota por analogia |
| Admin muda papel de funcionário | IAM + CORE02 + política do módulo | Ninguém aumenta direitos além do seu poder; sessões/jobs revistos quando necessário |
| Funcionário muda de equipe | IAM/equipe e proprietário comercial | Escopo e acesso a leads são reavaliados; histórico preservado |
| Admin desliga funcionário do tenant A | IAM e CORE03/04/07 | Novas operações em A deixam de ser autorizadas, B mantém membership legítima |
| Cliente pede substituição do único admin Básico | Fluxo próprio de recuperação/titularidade | Nunca dois operadores ativos no Básico por conversão implícita; evitar lockout sem bypass |
| Plano/suspensão altera capacidade | FIN/CON/CORE08 e IAM | Quotas e estado de dados/portabilidade não mudam por conclusão técnica isolada |

## 4. Opções ainda NÃO decididas — principal núcleo CORE01-D04 / CORE07-D05

### 4.1. Quando uma vaga humana deve ser considerada ocupada?

| Alternativa | Funcionamento hipotético | Vantagens | Riscos e compensações necessárias |
|---|---|---|---|
| **A — Reserva rígida ao enviar convite** | Convite elegível e ainda não expirado compromete assento do tenant | Pode evitar muitos convites simultâneos concorrendo à última vaga | Vagas ficam indisponíveis por convites não aceitos; ataques/bloqueios e cancelamentos precisam liberar corretamente; Básico 1/1 exigirá procedimento distinto para troca do admin |
| **B — Ocupação apenas na adesão efetiva** | Convites pendentes não ocupam vaga; na aceitação, IAM verifica e atribui atomicamente, se houver capacidade | Capacidade corresponde a usuários realmente habilitados; não prende vagas por convite ignorado | Dois convites podem concorrer à última vaga; um deverá falhar de forma compreensível, sem criar vínculo parcial ou cobrança |
| **C — Reserva temporária limitada com revalidação** | Convite retém uma reserva por período/política diferente da conta ativa; confirmação ainda exige revalidação | Previsibilidade para destinatário e uso controlado da quota | Mais estados, relógios, disputas de reserva, inconsistências e UX; custo de manutenção/complexidade maior |

**Recomendação técnica de trabalho, SEM APROVAÇÃO:** comparar prioritariamente **B** como base de simplicidade e integridade — só ativações confirmadas ocupam vagas — exigindo decisão separada de concorrência e mensagens de falta de vaga, cancelamento de convites e substituição de último administrador. Se a experiência comercial exigir reserva, avaliar **C** com limites; não adotar A/C por suposição.

**Atenção:** para **Básico (1/1)**, convidar segundo funcionário não pode conceder acesso adicional. Troca do único administrador é cenário de titularidade **CORE07-D06/CORE04-D07**, não “liberar 2ª vaga temporária” sem aprovação.

### 4.2. Quantos vínculos e atribuições por identidade × tenant?

| Tema | Opção de menor complexidade para análise | Alternativa | Decisão em aberto |
|---|---|---|---|
| Unicidade de participação atual | Uma membership lógica atual por identidade×tenant, com histórico de estados/reativações | Múltiplas instâncias com vigência não sobreposta | D04 + D07 |
| Papel inicial de convite | Papel/escopo inicial explícito, limitado pelo emissor e pelo plano, com revalidação na adesão | Papel genérico padrão com ajuste posterior | D04 e CORE02-D05 |
| Múltiplos papéis | Papel(s) por membership com direito efetivo calculado por restrições cumulativas do CORE02 | Perfil principal único com exceções escopadas | CORE02-D05; evitar “somar permissões de A+B” |
| Estados de inatividade | Revogação isolada da membership de um tenant preservando identidade global e autoria | Encerramento do vínculo e novo vínculo com outro ID em readmissão | D04, CORE07-D08/D09 |
| Suspensão por assinatura/site | Bloquear operações do tenant segundo estado do entitlement, sem apagar membership | Desabilitar membership por evento financeiro | CORE08-D07/08: escolher sem contaminação indevida e preservar portabilidade |

**Recomendação de documentação, não decisão:** evitar multiplicar conceitos e permitir **restituição de uma membership com histórico**, mas só após confronto de implicações legais, concorrência e trilhas de auditoria. **Não** presumir que um token possua direitos por ter um `membership_ref`.

## 5. Contrato de aceite seguro de convite — requisitos a homologar futuramente

1. O administrador emissor deve ter **direito de convidar** naquela organização e perfil compatível com plano/papel; ser Administrador Geral Imagine não dispensa mandato quando tratar dados do cliente.
2. O convite registra **destinatário, tenant, emissor, versão de política e papel proposto**, sem introduzir dados sensíveis desnecessários. Validade funcional aprovada: **7 dias**; momento exato, relógio, reenvio e token físico seguem CORE07-D04.
3. O link não é prova isolada de titularidade. O aceitante autentica-se com conta global existente ou conclui criação/verificação legítima de identidade; política MFA é aplicada conforme papel e tenant antes do acesso.
4. Nenhum direito ativo surge por receber e-mail, visualizar página, confirmar endereço sem autenticação apropriada ou possuir URL copiada.
5. Durante a efetivação, revalidar **tenant ativo, emissor/convite legítimos, destinatário, estado, papel, quotas/assentos, entitlement e concorrência**. Falha deve resultar em vínculo **não ativo**, motivo seguro, orientação de suporte e trilha, sem cobrança.
6. **Aceite único e idempotente**, evitando duas memberships ou dois assentos por clique duplicado, reenvio ou retries; prova de ordem/versão e decisão final do IAM.
7. Caso login global tenha associação anterior no tenant, não criar usuário duplicado. Readmissão/reconvite deve seguir política de estado e autoridade ainda pendentes.
8. A identidade do emissor e do aceitante e a referência do evento são auditáveis; **não guardar o segredo do token de convite em claro** nos logs ou no histórico comercial.
9. Mudança de e-mail, papel, titularidade, capacidade ou perfil após emissão demanda revisão da validade da autorização original, não atualização silenciosa do token.
10. Sem confirmador oficial do IAM, **não anunciar acesso ativo** por HTTP 2xx, disparo de e-mail ou sucesso de worker.

**Estes são critérios de planejamento. Não criar nem enviar convites nesta etapa.**

## 6. Papéis, equipes e permissões — dependência direta do CORE02-D04..D10

| Fronteira | Direito distinto | Regra já ratificada ou pendência |
|---|---|---|
| Administrar funcionários | Convidar, editar papéis, desligar, reativar e consultar trilhas | Só usuário autorizado no tenant e plano; escopo e política fina pendem CORE02 |
| Visibilidade comercial | Próprio / equipe / organização (e unidade quando módulo suportar) | Não ampliar leitura de lead por integrar outra equipe sem regra de escopo |
| Mover responsável comercial | Transferir lead/oportunidade | Requer direito de transferir; preservar histórico/origem e validar destino, não equivale a editar usuário |
| Ler vs exportar registros | Operações distintas | Consulta não implica exportação de dados pessoais nem backups |
| Anexos | `view_or_download_attachment` conjunta; upload e exclusão separados | IN-040 já aprovou **uma permissão conjunta de ver/baixar**; não partir em duas sem nova decisão |
| Relatório/auditoria | Consulta/exportação conforme categoria e finalidade | Admin de tenant não pode apagar histórico oficial ou conhecer segredos |
| Perfil administrativo | MFA obrigatório, step-up conforme risco a fechar | Admin Imagine não acessa automaticamente dados dos clientes |
| Contratos financeiros | Privilégio CRM/FIN e alçada própria | Administrar funcionários não dá direito de conceder gratuidade/alterar mensalidade |
| Agente/workflow/n8n | Identidade técnica com ação tipada e escopo | Não ocupa vaga humana por mera execução; não recebe permissão por prompt |

**CORE02-D05** (herança e precedência de direitos), **CORE02-D06/D07** (matriz efetiva de módulo/campo/ações críticas), CORE07-D07 (equipes) devem ser decididos de forma compatível; esta minuta não aprova uma política de papéis múltiplos.

## 7. Último administrador e exceções de troca — dependência CORE07-D06/CORE04-D07

**Obrigação inegociável de segurança:**
- Não deixar o Básico operando com **dois administradores humanos simultâneos** por tolerância de convite, mesmo CNPJ, profissão agendável ou substituição improvisada.
- Também não criar **perda irrecuperável de acesso** ou usar o serviço técnico/suporte como novo administrador invisível.
- Recuperação de MFA/senha é diferente de **transferência de titularidade**; exige prova, competência, alçada e auditoria próprias.
- Não aplicar indiscriminadamente regra de “não excluir último admin” para bloquear recuperação legítima; especificar fluxo protegido e estados transitórios quando houver aceitação.
- Não considerar “convite pendente” uma participação plenamente autorizada durante a transferência; nenhuma migração de dados para novo tenant por esse motivo.

**Caminhos candidatos (SEM escolha):**
- **Transferência planejada com admin existente:** confirmação do administrador atual, prova da identidade receptora, verificação MFA, operação atômica de revogação/atribuição dentro do limite 1 e verificação pós-ato.
- **Recuperação quando único admin está inacessível:** processo de comprovação de titularidade da empresa e identidade por equipe Imagine habilitada, mandato/caso específico, step-up e trilha, sem conceder acesso a dados de negócio à equipe de suporte por default.
- **Suspensão financeira:** preservar caminho de portabilidade e reativação autenticado segundo CORE08, sem criar assento extra como efeito colateral.

**Pendente:** prova jurídica exigida, titulares legitimados, multiadministrador em planos pagos, janela de transição, ação de emergência e contestação, erro de meio caminho, alçadas e efeitos em jobs/sessões.

## 8. Riscos de concorrência e integridade a documentar (nada foi testado)

| Risco | Invariante/futuro critério de aceite | Referência cruzada |
|---|---|---|
| Dois convites são aceitos para a última vaga | Nunca duas memberships ativas acima da quota nem cobrança automática; motivo claro ao convidado sem vaga | CORE07-T, CORE01-T, CORE10 |
| Clique repetido ou link reemitido | Não duplicar memberships/assentos/papéis; token antigo revogado conforme política | CORE07-D04 |
| Mesmo e-mail em identidades ou mudança de contato | Verificar titularidade na admissão, sem vincular por coincidência textual | CORE04-D04/D07 |
| Desligamento enquanto job pessoal aguarda | Revalidar e negar novo efeito pessoal em tenant revogado; manter registros prévios oficiais | CORE03-D05/D06 |
| Aceite de convite na empresa B com sessão selecionada em A | Confirmar contexto de B em fluxo seguro; nenhuma alteração de dados/acesso de A | CORE01-D05 |
| Mudança de plano com assentos já ocupados | Tratar excesso conforme regra e contratos ainda pendentes; não excluir dados ou cobrar sem aceite | CORE08-D07/D09 |
| Admin Básico tenta incluir profissional como login novo | Profissional agendável ≠ assento; não conceder 2º login | IN-020/021, CORE07 |
| Autoatribuição de papel especial | Negar pelo autorizador e alçadas; preservar trilha de tentativa | CORE02-D05/D07 |
| Admin geral consulta dados de cliente por ter conta técnica | Negar sem mandato específico, RLS e acesso por recurso | CORE05, SEG-01 |
| Convite antigo aceito depois de desativação de tenant | Revalidar estado oficial de tenant/contrato e negar | CORE08-D04/D07 |
| Token de convite no log/telemetria | Não gravar valor; manter só referência não sensível e categoria de auditoria | CORE06-D04/D06 |
| Conflito entre convites e readmissão | Preservar histórico e único vínculo efetivo, decisão idempotente e auditável | CORE01-D07, CORE07-D08 |

**Nenhuma linha acima é resultado observado.** IDs de teste existentes CORE01-T/CORE07-T/REV01-S/INT-HT serão usados como referências canônicas; não abrir suíte nova nem indicar PASS.

## 9. Matriz de decisões associadas — evitar fechamento cruzado automático

| Identificador | Pergunta que ainda necessita deliberação | Estado |
|---|---|---|
| **CORE01-D04** | Contrato lógico-físico do vínculo, convite, papel, equipe e assento, inclusive unicidade/concorrência | **ABERTO** |
| **CORE01-D05** | Contexto seguro tenant em sessão/worker/RLS, principalmente na ativação/desligamento | ABERTO |
| **CORE01-D07** | PK/FK/constraints, índices, histórico de readmissão e modelo efetivo em banco | ABERTO |
| **CORE02-D04..D10** | Campos, recursos, papel múltiplo, herança/negação, alçadas, ações e direitos executáveis por módulo | ABERTOS |
| **CORE04-D04/D05/D07/D08/D10** | Identidade/contato, MFA, sessões, recuperação e step-up | ABERTOS |
| **CORE07-D04** | Contrato físico do token e reemissão | ABERTO |
| **CORE07-D05** | Reserva vs ocupação de assento, quotas, concorrência, adicionais | ABERTO |
| **CORE07-D06** | Recuperação/substituição do último admin e titularidade | ABERTO |
| **CORE07-D07/D08/D09/D10** | Escopos por equipe, desligamento/reações de jobs, auditoria e testes | ABERTOS |
| **CORE08-D04/D07/D09** | Entitlement, tenant/assinatura, ciclo, assentos e recursos por plano | ABERTOS |
| **SEG-01/SEG-02**, INT P0-SEC/OWN, G01–G06 | Autorização final, identidades técnicas, contratos, rastreabilidade e gates | **ABERTOS** |

## 10. Proposta de encaminhamento para nova decisão expressa — não aprovada

**CORE01-D04-A — versão candidata (recomendada para discussão, NÃO APROVADA):**
- Membership efetiva como vínculo explícito identidade↔tenant, com histórico e estados, independente de convite;
- convite de 7 dias como intenção revogável, sem autorização operacional; aceitação autenticada e revalidada, com limite e idempotência;
- papéis e escopos atribuídos à membership conforme CORE02 e plano, não ao login global;
- reserva comercial de assento e contagem pendentes até escolha CORE07-D05; comparar alternativas A/B/C, com preferência técnica inicial a **B** sem reserva;
- desligamento por tenant sem destruir o login global ou autorias históricas;
- trocas do último admin Básico tratadas por CORE07-D06/CORE04-D07, sem inventar assento extra.

**A aprovação eventual desta base de D04-A não será aprovação de D04 completo**, porque permanecem alternativas de reserva, papel múltiplo, estados de readmissão, fluxo de recuperação e contratos físicos a decidir. A pergunta para o responsável deverá especificar se aceita **a base conceitual D04-A para continuar**, mantendo D04 e CORE07-D05/06 abertos, ou se prefere escolher depois todas as opções simultaneamente.

**Próximo trabalho recomendado:** ao receber aceite de continuar, elaborar tabela de critérios comparativos para CORE07-D05 (contagem de assentos) e depois CORE02-D05 (precedência de papéis) e CORE07-D06 (último admin), sem programar nem executar testes.

**Resumo do estado:** Documento publicado como **MINUTA**. Nenhum CORE-P0 nem SEG/INT/G01–G06 foi encerrado. Todas as opções concretas e D04 permanecem abertas, à espera de deliberação específica.
