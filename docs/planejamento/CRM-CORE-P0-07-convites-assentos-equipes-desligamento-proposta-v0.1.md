# CRM-CORE-P0-07 — Convites, assentos, equipes, movimentação e desligamento de usuários (v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE07-D01, D02 e D03 APROVADAS COMO BASE DOCUMENTAL**, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md). **CORE-P0-07 permanece PARCIAL/ABERTO; D04–D10 pendentes.** As menções abaixo a D01–D03 como propostas não aprovadas refletem a redação anterior ao aceite, superada pela decisão expressa.  
**Aditamento 06/11/2026 — CORE07-D05:** o responsável [ratificou expressamente o Modelo A de reserva temporária de assento durante convite válido e pendente](CRM-DEC-ratificacao-explicita-CORE07-D05-modelo-A-reserva-assentos-2026-11-06.md). A reserva ocupa uma vaga disponível enquanto o convite de 7 dias permanecer válido, mas não concede acesso nem gera cobrança; sua aceitação converte a reserva em ocupação efetiva uma única vez, e cancelamento/recusa/expiração libera a vaga. **CORE07-D05 integral permanece ABERTA**; os trechos abaixo que afirmam não escolhida a reserva são históricos anteriores a essa ratificação, sem alterar CORE07-D04/D06..D10 e outros gates.  
**Aditamento 06/11/2026 — CORE07-D04:** o responsável [ratificou expressamente o Modelo B para renovação manual de convites](CRM-DEC-ratificacao-explicita-CORE07-D04-modelo-B-renovacao-2026-11-06.md): nova validade de 7 dias somente por ato autorizado explícito, revogação do link antigo, uma única reserva elegível e nenhuma cobrança/acesso automático. **CORE07-D04 integral continua ABERTA** para regras de abuso, reenvio, token, concorrência e homologação. O texto histórico abaixo com D04 totalmente indecidida fica superado apenas quanto à escolha preliminar B.  
**Aditamento 06/11/2026 — CORE07-D06:** o responsável [ratificou expressamente o Modelo A de substituição atômica e verificada do administrador principal](CRM-DEC-ratificacao-explicita-CORE07-D06-modelo-A-transferencia-admin-2026-11-06.md). No Básico, o novo administrador assume a mesma vaga única sem acesso simultâneo com o anterior, preservando organização/contratos/históricos e exigindo identidade/MFA e revogação apropriadas. Perda do MFA, falhas parciais, suporte excepcional e competências finais seguem pendentes. **CORE07-D06 integral permanece ABERTA**; referências históricas de D06 como totalmente indecidida passam a significar pendência de fechamento e homologação.  
**Aditamento 06/11/2026 — CORE07-D07:** o responsável [ratificou expressamente o Modelo B de redistribuição híbrida](CRM-DEC-ratificacao-explicita-CORE07-D07-modelo-B-redistribuicao-hibrida-2026-11-06.md): executar automaticamente somente as redistribuições já autorizadas e aplicáveis por VEN/ATD/AGD e demais proprietários; situações não cobertas seguem para revisão supervisionada, sem transferência por silêncio. Preservar a posse/histórico, permissões de tenant/equipe e limites funcionais. **CORE07-D07 integral continua ABERTA**, inclusive contratos por proprietário, concorrência, exceções e homologação.  
**Aditamento 06/11/2026 — CORE07-D08:** o responsável [ratificou expressamente o Modelo B de revogação seletiva e D08-A/B/C/D](CRM-DEC-ratificacao-explicita-CORE07-D08-modelo-B-ABCD-2026-11-06.md) como diretrizes documentais preliminares: novos efeitos pessoais devem revalidar autorização após perda de vínculo/escopo, inclusive jobs e exportações; serviços autônomos com fundamento legítimo independente podem continuar; preservar fatos já confirmados e auditar a decisão. **CORE07-D08 integral permanece ABERTA** para contratos físicos, exceções, Segurança, INT e homologação.  
**Aditamento 06/11/2026 — CORE07-D09:** o responsável [ratificou expressamente D09-A/B/C/D como diretrizes documentais preliminares](CRM-DEC-ratificacao-explicita-CORE07-D09-ABCD-2026-11-06.md): catálogo de eventos de convites/memberships/equipes/transferências/revogação, autoria/integração de evidências, minimização e acesso conforme LGPD e roteiros futuros de homologação, reaproveitando CORE06/INT. **CORE07-D09 integral continua ABERTA** para contratos reais por módulo, retenções aprovadas, controles de acesso e provas de testes.  
**Aditamento 06/11/2026 — CORE07-D10:** o responsável [ratificou expressamente D10-A/B/C/D apenas como diretrizes técnicas documentais preliminares](CRM-DEC-ratificacao-explicita-CORE07-D10-ABCD-2026-11-06.md): matriz de contratos/owners, cobertura de cenários positivos/negativos/concorrentes, evidências verificáveis e reconciliação de gates. **Está concluída a rodada de escolhas documentais preliminares CORE07-D04..D10, MAS CORE07-D04..D10 e o CORE07 integral PERMANECEM ABERTOS** até especificação executável, validação entre módulos, testes e homologação formal. Referências históricas abaixo que indicam ausência total de escolha de D04–D10 não substituem as decisões expressas posteriores.  
**Objetivo:** consolidar o ciclo da participação de uma identidade global em organizações independentes (tenant), os convites de 7 dias, os limites de usuários, equipes, atribuições e efeitos do desligamento, com prevenção de ocupação dupla de assentos, vazamento entre empresas, elevação de privilégios e perda de histórico.  
**Fase:** somente planejamento, GitHub e aprovação expressa. **Não programar, testar em ambiente real, criar credenciais, ativar convites, contratar, alterar dados de clientes ou iniciar desenvolvimento.**

## 1. Fontes de referência e precedência

| Fonte | Decisão funcional já aprovada / fronteira |
|---|---|
| [LEG-04 — Empresas, usuários, equipes e auditoria](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Admin da empresa convida por e-mail com link seguro de **7 dias**, define perfil; usuário novo cria conta e usuário existente utiliza login global; cancelamento/reenvio; não ultrapassar assentos, não duplicar vínculo; equipes com gestor/integrantes, funcionário pode integrar múltiplas equipes; escopos próprio/equipe/empresa; transferir leads/oportunidades preservando histórico; desligamento revoga acesso e sessões **da empresa**, mantendo outras memberships; reativação por autorizado dentro dos limites |
| [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) N-02/04/05/06/07/10/11 | Identidade global ≠ membership da organização; convites e transferências rastreáveis; Básico 1 administrador autenticado; um trial por CNPJ não impede múltiplas contas Básico elegíveis por contratos distintos |
| [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md) | Uma organização CRM = tenant lógico; uma identidade global pode ter vínculos independentes por tenant; RLS e verificação Laravel; mesmo CNPJ não concede ponte automática |
| [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md) | Permissões por identidade + vínculo + tenant + plano/benefício + papel + escopo + recurso/ação/campo, revalidação, negação por padrão; nenhum papel ou grant novo por convite |
| [CORE-P0-03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md) | Jobs e eventos preservam identidade causal e executor; revogação de vínculo bloqueia novos efeitos pessoais, mas não reverte atos oficiais nem paralisa rotinas obrigatórias |
| [CORE-P0-04 D01–D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md) | MFA obrigatório para admins geral/empresa, inclusive Básico; funcionário opcional por padrão salvo exigência da organização; expiração de 30 min somente painel central; recuperação segura não cria assento/tenant |
| [CORE-P0-05 D01–D03](CRM-DEC-aprovacao-CORE-P0-05-D01-D03-base-suporte-excepcional-2026-11-04.md) | Suporte Imagine não equivale a membership de funcionário nem acesso irrestrito; mandato excepcional por caso independente |
| [CORE-P0-06 D01–D03](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md) | Trilha oficial de adesão, mudanças de perfil, transferências e desligamentos em categoria adequada; administrator tenant não apaga trilha; sem log de segredos |
| [LEG-13 — Matriz Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | **Básico: um único usuário autenticado**, Administrador da Empresa com MFA obrigatório; 1 profissional agendável é cota diferente de assentos; segundo usuário não está incluído |
| [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) e [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md) | Entitlements, benefícios, cotas de assentos, unidades e profissionais, cobrança e planos permanecem com FIN/CON/AGD; limites comerciais finais ainda não fechados |
| [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) e [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) | Contratos físicos, segurança, serviços técnicos, testes, alçadas e G01–G06 abertos |

**Cuidado específico:** não confundir (a) número de usuários autenticados no CRM com (b) número de profissionais agendáveis, (c) número de equipes, (d) caixas de atendimento e (e) identidades técnicas. Cada quota pertence a contrato próprio. Convite ou pertencimento a equipe **não concede módulo pago nem muda automaticamente a fatura**.

## 2. Conceitos e atores — sem schema aprovado

| Termo lógico | Significado para o planejamento | Regra de isolamento |
|---|---|---|
| `identity_ref` | Login global que pode estar associado a várias empresas | Não é por si papel nem acesso a tenant |
| `tenant_ref` | Organização CRM isolada, com plano/benefício próprios | O mesmo CNPJ pode ter tenants diferentes, sem unificação de dados/assentos |
| `membership_ref` | Vínculo efetivo entre identidade e tenant, com status, escopo e papéis autorizados | Desativação em A não desativa B; um convite não é membership operacional automaticamente |
| `invitation_ref` | Intenção de conceder vínculo, por e-mail e papel inicial, limitada a 7 dias | Token e aceite validáveis; envio não é sessão nem grant |
| `seat_entitlement_ref` | Limite de usuários humanos autenticados habilitados para um tenant | Depende de plano, contratação/add-on expressamente aprovados e estado real |
| `role_assignment_ref` | Função e permissões dentro de membership | O próprio usuário não se eleva ao alterar perfil/convite |
| `team_ref`, `team_member_ref` | Agrupamento interno de trabalho e relações gestor/integrante | Escopo de equipe por tenant, sem virar vínculo entre empresas |
| `resource_assignment_ref` | Responsável pelo lead, oportunidade, conversa ou tarefa conforme proprietário | Reatribuição ≠ mudança do autor original ou apagamento de histórico |
| `audit_ref` | Evidência do convite, aceite, alteração e revogação | Auditoria Geral rege retenção e consulta; não armazenar token de convite em claro |

**Sem valores assumidos:** identificadores, índices, estados físicos, expiração no instante exato, contagem de convite pendente, cobrança/assentos adicionais, limite de equipes e API são objeto de decisões posteriores.

## 3. CORE07-D01 — Convites e adesão por tenant (PROPOSTA PARA ACEITE)

**Diretriz lógica sugerida:** preservar o convite seguro **válido por 7 dias**, com emissão e aceitação verificadas no backend, e diferenciar a criação de identidade da adesão a um tenant.

1. **Emissão:** administrador efetivamente autorizado no tenant informa e-mail e perfil inicial dentro dos direitos e do limite de plano. Não pode convidar como Admin Geral Imagine nem transferir implicitamente dados de outra organização.
2. **Vínculo existente:** e-mail já correspondente a login global pode aceitar convite e vincular essa identidade a uma nova empresa sem criar conta paralela. **Não assumir que e-mail enviado por si comprova titularidade**; aceite requer autenticação/verificação cabível.
3. **Nova identidade:** convidado sem conta cria a identidade por fluxo seguro e confirma legitimamente o convite; nenhum acesso efetivo ao tenant antes da adesão válida.
4. **Proteção:** link e token com expiração de 7 dias, escopo de tenant, destinatário e papel; cancelamento, reenvio e múltiplas tentativas não podem ativar duas memberships ou estender silenciosamente o convite antigo. Como reiniciar expiração em reenvio é pendência D04.
5. **Adesão:** confirmar convite não revogado nem vencido, identidade e prova de controle da conta, espaço/assento e competência do perfil, política MFA do papel e estado do tenant. **Não concluir aceite se qualquer condição obrigatória falhar.**
6. **Auditoria:** registrar quem convidou, quem aceitou, tenant, papel inicial, momento e resultado, minimizando dados e sem guardar token/link em claro.
7. **Segurança comercial:** convite pendente, sem aceite, não significa funcionário ativo, direito de uso de recurso premium nem contratação adicional.

### Estados lógicos candidatos, não homologados

`preparado → emitido/pendente → (aceito → membership ativa | cancelado | expirado | recusado/inválido)`.

Revogações, reenvios, aceites simultâneos e falhas de e-mail precisam transições versionadas e idempotentes a definir em D04, sem duas adesões.

## 4. CORE07-D02 — Assentos por organização e mudanças de membership (PROPOSTA PARA ACEITE)

**Diretriz lógica sugerida:** ter uma referência de ocupação e elegibilidade **por tenant**, independente da identidade global e de quantidade de equipes/profissionais; impedir tanto extrapolar quota como bloquear acessos legítimos em outras organizações.

- **Quota efetiva:** cada tenant usa entitlement de seu plano/contrato, benefícios e adicionais **validamente contratados**, considerando suspensão, trial e mudanças financeiras somente conforme política do proprietário FIN/CON.
- **Básico:** um único usuário humano autenticado com perfil Administrador da Empresa e MFA obrigatório; **não habilitar segundo login** por convite, substituição de administrador, profissional de Agenda, benefício de site ou recuperação de conta.
- **Outros planos:** permitir administradores adicionais, funcionários e perfis configurados conforme serviços/assentos que o plano permitir, sem criar número de usuários incluídos ou preço de extra por suposição.
- **Identidade multiempresa:** uma pessoa pode ocupar um assento em A e outro em B com memberships distintas; não usar CNPJ ou e-mail como quota global, nem compartilhar direitos entre essas organizações.
- **Convites pendentes:** exigir verificação contra oversubscription e aceites concorrentes. **Se convite reserva assento antes de ser aceito ou apenas quando é aceito** depende de CORE07-D05; não antecipar escolha econômica/técnica.
- **Ciclos de vínculo:** ativo, desativado, reativável, convidado/pedido e situações especiais têm tratamento distinto; **quem ocupa assento em cada estado** e efeito de suspensão da organização ficam pendentes.
- **Elevação:** convidar/adicionar administrador é ação de competência própria e requer MFA. Admin de tenant não transforma colega em Admin Geral Imagine nem gera grants superiores ao disponível.
- **Reativação:** exige administrador autorizado, tenant legítimo, assento elegível, segurança do perfil e auditoria; não restaurar permissões e sessões vencidas automaticamente por silêncio.
- **Alteração de plano e cobrança:** nenhuma inclusão de usuário compra automaticamente assento, faz upgrade, prorroga trial, emite boleto ou modifica benefício do site; eventual extra exige negociação e aceite financeiro conforme FIN/CON.

## 5. CORE07-D03 — Equipes, transferências e desligamento seguro (PROPOSTA PARA ACEITE)

**Diretriz lógica sugerida:** tratar equipes, distribuição comercial e desligamento como operações internas **de uma única organização**, com direitos por módulo/ação e preservação de histórico.

### 5.1. Equipes e alcance

- Gestores e integrantes designados conforme direitos do próprio tenant; **uma pessoa pode participar de várias equipes da mesma empresa**.
- Visibilidade comercial segue escopos preexistentes **próprio / equipe / empresa**, com vínculos atuais e política do módulo; somente estar na equipe não concede exportação, edição, exclusão, mudança de dono ou acesso a campo protegido.
- Alterar composição da equipe/gestor impacta alcance de leitura/edição; reavaliar permissões nas novas consultas, tarefas, exports e operações diferidas.
- Equipes diferentes entre organizações não se relacionam implicitamente, mesmo com funcionário comum ou CNPJ igual.
- Ausência, substituição, unidades, caixas de atendimento e regras automáticas de distribuição seguem os módulos proprietários ATD/VEN/AGD, **não** são impostos aqui como privilégio novo.

### 5.2. Transferir e reatribuir

- **Leads e oportunidades** podem ser transferidos individualmente ou em lote por usuário autorizado, conforme LEG-04; preservar executor, anterior/novo responsável, data/hora e histórico comercial, sem apagar origem.
- Reatribuição de conversa, atendimento, agenda, tarefa e automação **somente** segundo competência e estado do módulo respectivo; um desligamento não equivale a permissão geral para editar registros de todos os módulos.
- Transferência de responsabilidade e revogação de vínculo são **atos diferentes**: não reatribuir automaticamente registros sem política previamente aprovada; tarefas críticas pendentes exigem tratamento seguro, diagnóstico e escalonamento.
- Nenhuma transferência entre tenants decorre de equipe, convite, desligamento ou troca de CRM vinculado ao site. Migração/portabilidade entre contas segue IN-118 e contratos específicos.

### 5.3. Desligamento e retorno

1. Administrador competente desativa o **membership naquela organização**; revogar imediatamente seu acesso e sessões/credenciais efetivas **da organização**. Não eliminar identidade global ou memberships válidas em outras empresas.
2. Bloquear novas ações humanas, downloads, exports, jobs que requerem autorização pessoal revogada e acesso aos objetos que exigem o vínculo atual, conforme CORE-P0-03.
3. **Preservar registros, históricos, autoria original e trilhas imutáveis**; a saída do funcionário não elimina leads, contatos, conversas e atividades da empresa.
4. Manter rotinas obrigatórias do sistema cuja autoridade não dependa da sessão do funcionário desligado; revalidar automações comerciais, posses de conversa, distribuição e ações pendentes.
5. A redistribuição de leads/oportunidades permanece possível por autorizado, com histórico; ordem, escolha de substituto e automatismo ficam pendentes D07.
6. Reativar não equivale a restaurar sessões, MFA, chaves, permissões anteriores ou assentos já ocupados; cada requisito volta a ser verificado.
7. Desligamento por tenant não representa desativar login global, rescindir contrato de prestação de serviços externo ou eliminar dados pessoais do titular independentemente de LGPD.

## 6. Exemplos e casos especiais

| Situação | Resultado de referência |
|---|---|
| Usuário possui vínculo legítimo nas empresas A e B e é desligado de A | Acesso a A removido; B preservado se seus direitos continuarem válidos |
| Admin Básico tenta convidar segundo operador | Bloquear segundo usuário autenticado (quota 1/1), sem usar falha de segurança como upsell |
| Um profissional agendável não possui login | Profissional consome cota de Agenda, **não** assento humano de login |
| Dois administradores enviam convites simultaneamente para o último assento | Nenhuma aceitação pode criar oversubscription; definição de reserva/ordem D05 |
| Funcionário de B aceita convite de A | Mesma identidade global, nova membership A distinta, sem ver dados de B a partir de A |
| Convite tem 7 dias, mas está cancelado | Cancelamento prevalece, sem permitir adesão posterior pelo mesmo token |
| E-mail do convite corresponde à identidade existente mas não ao usuário autenticado | Não ligar conta de terceiros sem validação apropriada |
| Gestor de equipe muda e perde visibilidade anterior | Consultas e exports subsequentes respeitam equipe/papel efetivos; histórico de atos anteriores preservado |
| Funcionário com lead sob responsabilidade é desligado | Preservar lead e histórico; reatribuição somente por fluxo do proprietário e autorização adequada |
| Job de exportação foi solicitado antes do desligamento | Bloquear novo efeito que dependa da membership revogada; preservar evidência e revalidar autorizações |
| Admin único Básico perde MFA e solicita substituição | CORE-P0-04 D07 e CORE-P0-07 D06 precisam fluxo próprio, sem criar segundo assento permanente ou titularidade presumida |
| Down/upgrade de plano reduz ou amplia entitlements | Tratar com FIN/CON e quotas efetivas, sem comprar/cancelar add-on por iniciativa de convite |
| Suporte Imagine recebe chamado para alterar perfil de funcionário | Mandato excepcional CORE-P0-05 não gera automaticamente permissão de admin tenant nem permite impersonation oculta |

## 7. Casos negativos futuros — NÃO EXECUTADOS

| ID | Teste documental futuro | Critério |
|---|---|---|
| CORE07-T01 | Mesmo e-mail convidado simultaneamente por duas empresas | Aceitar dois vínculos independentes se legítimos, sem conflar dados/perfis |
| CORE07-T02 | Convite expirado ou cancelado tenta gerar membership | Negar |
| CORE07-T03 | Reenvio/corrida de aceite gera duas memberships iguais | Idempotência e unicidade por tenant × identidade |
| CORE07-T04 | Convites simultâneos excedem um assento disponível | Nunca extrapolar quota; tratar reserva/contagem segundo contrato homologado |
| CORE07-T05 | Básico tenta cadastrar segundo admin ou funcionário | Negar pelo limite 1/1 |
| CORE07-T06 | Convidado sem MFA tenta entrar como administrador | Exigir MFA obrigatório antes de atuar |
| CORE07-T07 | Login de B tenta usar token de convite enviado a A sem comprovação | Negar vínculo indevido |
| CORE07-T08 | User desativado em A tenta entrar em A, mas conserva B | Negar A, preservar B |
| CORE07-T09 | Troca de gestor mantém cache antigo de visibilidade da equipe | Revalidar e negar acesso fora da equipe |
| CORE07-T10 | Desligamento enquanto exportação/download ainda está pendente | Bloquear nova operação não autorizada; auditar |
| CORE07-T11 | Redistribuição preserva lead mas elimina histórico anterior | Negar alteração que apague histórico; conservar autoria e trilha |
| CORE07-T12 | Técnico Imagine se promove a admin da empresa usando ticket | Negar autoelevação; CORE05 válido não substitui mandato/alçada |
| CORE07-T13 | Usuário muda de equipe em A, tenta visualizar equipe de B com mesmo ID | RLS e escopo bloqueiam |
| CORE07-T14 | Administrador desativa último administrador da organização | Fluxo futuro deve impedir perda irrecuperável de gestão sem procedimento competente; não presumir bypass/2º usuário |
| CORE07-T15 | Trial já usado mesmo CNPJ e novo site elegível cria tenant Básico | Aplicar CORE-P0-08 sem conceder novo trial ou fundir tenants |
| CORE07-T16 | Reativação recupera secret/sessão antiga ainda válida | Revogação preservada; exigir nova autenticação/política |

Confrontar CORE01..06, REV-01 S01..S10, REV09 e INT HT sem testes redundantes; não executar agora.

## 8. Decisões propostas AGORA — NÃO APROVADAS

- **CORE07-D01 — PROPOSTA:** aceitar o **modelo lógico de convites seguros por organização, válidos por 7 dias**, com separação entre identidade existente/nova, convite e membership efetiva, verificação de destinatário e administrador, cancelamento/reenvio e aceite idempotente, **sem decidir a semântica exata de reserva de assentos nem esquemas/tokens físicos**.
- **CORE07-D02 — PROPOSTA:** aceitar a **regra lógica de assentos por tenant e ciclo de membership**, preservando Básico 1 usuário Admin com MFA, quotas distintas de Agenda/equipes, direitos por plano e autenticação multiempresa; não criar cobrança, assento incluído, reserva de convite ou reativação automática não aprovados.
- **CORE07-D03 — PROPOSTA:** aceitar a **base lógica de equipes, escopos, transferências e desligamento proporcional**, com múltiplas equipes por usuário na mesma organização, visibilidade próprio/equipe/empresa condicionada ao direito, revogação somente do tenant desligado, preservação de históricos e reatribuição de trabalhos conforme módulos competentes.

## 9. CORE07-D04 a D10 — pendências deliberativas/técnicas abertas

| Código | Pendência e competência responsável |
|---|---|
| **CORE07-D04** | Contratos físicos do convite: token seguro, expiração, reenvio/cancelamento, troca de e-mail, aceite de conta existente, concorrência e idempotência; Identidade/CORE-P0-04 |
| **CORE07-D05** | Semântica de ocupação/reserva de assentos durante convites pendentes, contagem por estado, último assento, trial/planos/benefícios, quotas e extras; FIN/CON, CORE-P0-08 |
| **CORE07-D06** | Gestão do administrador principal, transferência de titularidade e substituição/recuperação do último administrador, especialmente Básico, sem bypass de MFA/assento; Segurança, CORE04-D07, CORE-P0-08 |
| **CORE07-D07** | Regras por equipe, gestor, unidades, caixa de atendimento, visibilidade de leads/oportunidades, políticas de redistribuição/ausência e separação de reatribuição de membership; VEN/ATD/AGD |
| **CORE07-D08** | Revogação efetiva em sessões, tokens, cache, jobs, anexos, exports, automações, conexões e interfaces; resposta a downgrade e usuários bloqueados; CORE01/02/03/04, SEG |
| **CORE07-D09** | Auditoria por convite, alteração de vínculo/papel/equipe, acesso, transferência, desligamento e reativação, mascaramento/LGPD/retencão por categoria; CORE-P0-06, Auditoria Geral |
| **CORE07-D10** | Estados detalhados, invariantes/testes futuros (simultaneidade, multiempresa, quotas, escalada, desligamento) e fechamento de dependências com INT/SEG/G01–G06; CORE-P0-10 |

**Continuam abertos:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE06-D04..D10; CORE-P0-08..10; INT P0-OWN/SEC/CON/ACC; IN-168 ARQ-01/02/03, SEG-01/02 e AGT-01; G01–G06 e gate global de planejamento. Esta proposta não encerra nenhum P0.

## 10. Próxima sequência e controle de mudança

Após eventual aprovação documental expressa de CORE07-D01..D03, seguir **CORE-P0-08 — Básico, múltiplas organizações e trial por CNPJ**, sem programar. Nenhuma das propostas altera preço, limites finais de planos, direito a segundo usuário, benefício do site, livre migração entre contas ou capacidade de suporte.

**Proibições mantidas:** Codex/programação, Laravel/SQL, criação ou envio real de convites, alteração de contas e memberships de clientes, testes em ambiente real, licenças, contratações, infraestrutura e qualquer despesa. Somente documentação e aprovação específica do responsável.


## 11. Aditamento de aprovação documental — 04/11/2026

O responsável aprovou expressamente **CORE07-D01, D02 e D03 como base documental**, nos exatos limites da [decisão](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md): convites de 7 dias e membership por tenant; assentos humanos e planos segregados; equipes e desligamento da organização preservando identidade/históricos. **Não houve autorização de concessão de vagas, troca de titulares, convites reais ou implementação.**

**CORE07-D04 a D10 continuam abertas**, assim como todas as dependências CORE/INT/SEG/ARQ/G01–G06 e a revisão comercial final. Próximo passo restrito ao **planejamento CORE-P0-08**, sem programação, testes, contas ou contratações.
