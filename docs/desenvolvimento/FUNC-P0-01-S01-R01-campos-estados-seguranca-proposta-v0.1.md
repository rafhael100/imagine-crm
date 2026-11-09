# CRM Imagine — FUNC-P0-01 / S01-R01 — Campos, estados e critérios de segurança da autoconsulta

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Status:** **PROPOSTA DOCUMENTAL PARA NOVA AVALIAÇÃO — NÃO APROVADA**.  
**Recorte vinculado:** FUNC-P0-01, alternativa **A**, aprovada apenas para aprofundamento documental.  
**Base preliminar já ratificada:** [S01-D01/D02/D03 e orientações gerais D04..D08](FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md).  
**Discussão/decisão:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22).  
**Antecedentes:** [S01 v0.1](FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md), [F1-10](F1-10-consolidacao-gates-seguranca.md), [CORE02 PR #21](https://github.com/rafhael100/imagine-crm/pull/21).

> **ESTE R01 NÃO É CÓDIGO NEM CONTRATO EXECUTÁVEL.** Não autoriza `ALLOW`, SQL, migração, novos testes funcionais, dados reais, autenticação pública, merge de PR #2/#21, `main` ou VPS. Os **67 IDs CORE** e gates SEG/AUT/ARQ/INT/FIN/CON/LGPD permanecem integralmente abertos. Nenhuma das opções R01 abaixo está aprovada por esta publicação.

## 1. Visão funcional mínima: o que o usuário veria no futuro

A intenção aprovada *para estudo* é mostrar no CRM Imagine, para pessoa **já legitimamente autenticada e vinculada**, somente a identificação curta da **própria organização atualmente verificada** e a informação de que **seu próprio vínculo está ativo**. Não existe seletor de organização, lista de outros membros, cadastro/convite, edição de perfil, status de pagamento, dados de leads nem integração com Portal nesta fatia.

**Exemplo exclusivamente ilustrativo, não interface funcional:**  
`Organização: Empresa Exemplo`  
`Seu vínculo: Ativo`

A tela não é mecanismo de autorização: somente política e dados oficiais validados no servidor poderão determinar acesso em eventual implementação posterior. Um dado já renderizado precisa ser considerado potencialmente desatualizado após revogação ou mudança de tenant.

## 2. R01-CAM — Catálogo candidato de campos e decisão de exposição

A tabela divide atributos **visíveis**, **internos** e **excluídos**. Códigos são *semânticos para discussão*, não colunas definitivas. Classificações são **hipóteses de trabalho**, sem aprovar taxonomia CORE02-D04, base LGPD ou autorização por campo.

| ID | Atributo lógico candidato | Proposta de uso | Proteção e fonte oficial a confirmar | Status documental |
|---|---|---|---|---|
| CAM-01 | Nome de apresentação da organização corrente | **Visível**; único nome exibido | Owner de Organizações deve confirmar fonte, atualização, limitação de comprimento, tratamento como possível dado pessoal em nome empresarial e escaping contra HTML/XSS | Proposto, condicionado |
| CAM-02 | Rótulo “Seu vínculo: Ativo” | **Visível**, apenas após validar membership ativa | Derivar de estado oficial do **próprio vínculo**, jamais de entrada do usuário ou cache antigo. Não revelar histórico de suspensões ou de terceiros | Proposto, condicionado |
| CAM-03 | Identificador opaco da organização | **Interno** apenas se indispensável à correlação; não visível na V0 | Não funciona como credencial, prova de posse, chave de autorização ou query confiável | Não aprovar exibição |
| CAM-04 | Identificador opaco da membership própria | **Interno** apenas se indispensável; não visível | Associação obrigatória com principal autenticado e tenant ativo; evitar correlação entre organizações | Não aprovar exibição |
| CAM-05 | Nome/rótulo do papel do usuário | **Excluído da V0** | Exige catálogo/core02/privacidade e regra de múltiplos papéis; GOV01 M2 não transforma título em grant | Pendente de futuro recorte |
| CAM-06 | Situação da organização/assinatura/plano | **Excluído da V0** | CORE08/FIN/CON e owner devem definir diferenças entre estado operacional e financeiro | Pendente |
| CAM-07 | CNPJ, e-mails, telefone, endereço, documentos | **Excluído da V0** | Dados de identificação/contato e possível PII; exige finalidade e revisão LGPD específica | Fora da V0 |
| CAM-08 | Outros usuários, equipes, memberships, convites | **Excluído** | Enumeration e relação com terceiros; outro módulo, outra autorização | Fora do recorte |
| CAM-09 | Preço, crédito, pagamento, status de fatura, plano | **Excluído** | Sensibilidade comercial, owner financeiro e estados restritos | Fora do recorte |
| CAM-10 | Leads, contatos, arquivos, tokens, integrações, IA | **Excluído** | Recursos pertencentes a outros módulos/autorizadores | Fora do recorte |

**Questões de aceite CAM:** proprietário real de `display_name` e membership; comprimento e política de normalização/exibição; se a organização é pessoa física/MEI e como evitar divulgação indevida; classificação CORE02-D04; existência de valores herdados/teste; ausência de enriquecimento com telefone/CNPJ; necessidade de identificador em logs; respostas seguras em caso de inconsistência.

**Recomendação R01-CAM-A (para decidir):** manter **apenas CAM-01 + CAM-02 visíveis**, e não disponibilizar CAM-03..CAM-10 na V0. CAM-03/04 podem existir internamente **apenas após justificativa/autoridade**. Campos desconhecidos, inexistentes ou inconclusivos não devem receber preenchimento de fonte alternativa por suposição.

## 3. R01-EST — Estados: separar quatro dimensões que não são equivalentes

**Nunca confundir** estado de (a) sessão/identidade, (b) membership, (c) organização e (d) contrato/plano. Um status de “conta ativa” não prova que pessoa possa ler; vínculo ativo não comprova plano; plano pago não substitui sessão. `app.tenant_id` não autentica.

### 3.1 Dimensão da sessão humana / autenticação
- **Verificada e vigente**: apenas condição necessária, nunca suficiente.
- **Ausente, inválida, expirada, revogada, comprometida ou MFA insuficiente quando exigido**: **negação** de autoconsulta, sem revelação da organização; recuperação/reautenticação é fluxo separado.
- **Inconclusiva / dependência indisponível**: fail-closed. Não reaproveitar uma sessão de outro tenant ou um grant em cache.

### 3.2 Dimensão de vínculo (membership) na organização ativa
- **Ativo, verificado e pertencente ao sujeito e ao tenant correto**: condição necessária para leitura **futura**, sujeita a todas as outras políticas aprovadas.
- **Convidado/pendente, suspenso, revogado, expirado, removido ou desconhecido**: **negação da autoconsulta S01**; não exibir nome da empresa, ID ou papel por associação especulativa.
- **Reativado**: não herdar autoridade de sessão/cache anterior; revalidar pelas fontes oficiais antes de nova operação.
- **Múltiplos vínculos A/B**: o recorte **não inclui troca de organização**. O tenant corrente é apurado no backend e nunca selecionado por query arbitrária.

### 3.3 Dimensão do estado da organização
**Valores possíveis são ilustrativos, não taxonomia aprovada:** existente/operacional, inativa, suspensa, em encerramento, encerrada, desconhecida. A fonte oficial e o owner devem decidir que estados permitem **autoconsulta própria**. Enquanto não há regra aprovada, **não se presume acesso**; também não se presume que suspensão comercial deva suprimir direitos legais de gerenciamento/portabilidade, que serão atendidos por fluxo próprio a definir.

### 3.4 Dimensão do estado comercial/contratual
**Categorias para análise, não regra final:** trial válido/expirado, Básico cortesia ativo/bloqueado, contrato pago vigente, inadimplente, suspensão, cancelamento em processo, cancelado, retenção pós-cancelamento. São distintas de membership e do estado técnico da organização. Exigir homologação com CORE08, FIN/CON e Privacidade, inclusive direitos mínimos para visualizar informações da própria conta quando suspensa. Não fixar prazos, quotas, gratuidade, tempo de expiração ou bloqueios automáticos nesta S01.

### 3.5 Matriz de estados — propostas a submeter ao owner

| ID | Sessão | Membership | Organização/contrato | Orientação proposta do recorte S01, **não executável** |
|---|---|---|---|---|
| EST-01 | Verificada | Ativa no tenant certo | Estado contratual com direito de autoconsulta homologado | Elegível somente após política + concessão por ação/campo/escopo futura; hoje deny-only |
| EST-02 | Ausente/revogada | Qualquer | Qualquer | Deny, sem identificar tenant |
| EST-03 | Verificada | Pendente/invitada | Qualquer | Deny do recorte S01; eventual fluxo de aceite de convite é separado |
| EST-04 | Verificada | Suspensa/revogada | Qualquer | Deny do recorte S01; recuperação administrativa é fluxo distinto |
| EST-05 | Verificada | Ativa | Organização com estado técnico desconhecido | Fail-closed até regra do owner, sem supor extinção de direitos legais |
| EST-06 | Verificada | Ativa | Trial vigente | **A decidir** pelo owner; nenhum direito inferido de trial |
| EST-07 | Verificada | Ativa | Trial expirado | **A decidir** com CORE08/FIN/CON/Privacidade; nenhum bloqueio universal inferido |
| EST-08 | Verificada | Ativa | Básico cortesia ativo | **A decidir**; não presumir entitlement por exibir preço/plano |
| EST-09 | Verificada | Ativa | Pago vigente | **A decidir**; pagamento não substitui autorização |
| EST-10 | Verificada | Ativa | Inadimplência/suspensão contratual | **A decidir**, inclusive autoatendimento legítimo para regularização |
| EST-11 | Verificada | Ativa | Cancelamento solicitado/retido | **A decidir**, preservando direitos de acesso/portabilidade quando aplicáveis por fluxo próprio |
| EST-12 | Verificada | Ativa | Cancelado e fora da retenção legítima | **A decidir**; não inventar política de disponibilidade ou retenção |
| EST-13 | Verificada | Ativa A e B | Tenant A autenticado; parâmetro tenta B | Deny da troca ilegítima e nenhuma associação B |
| EST-14 | Inconclusiva | Inconclusiva | Qualquer | Deny/fail-closed, sem consulta em cache |

**Recomendação R01-EST-A para apreciação:** ratificar **a separação das quatro dimensões e a regra de que sessão/membership inválida sempre nega S01**. Não escolher agora resultado final de EST-05..12; encaminhar a matriz contratual/finanças ao owner competente, sem criar `ALLOW` por ausência de definição.

## 4. R01-SEG — Critérios de segurança e proteção a detalhar

Todos os itens abaixo são **critérios de futura homologação propostos**; sua publicação não os implementa e não autoriza testes funcionais.

| Eixo | Critério proposto | Evidência que será exigida antes de eventual GO |
|---|---|---|
| Identidade | Principal humano autenticado em sessão protegida e verificada server-side, sem aceitar impersonação, staff, n8n, agente, webhook, token arbitrário | Contrato IAM/CORE04 e testes de sessão, autenticação/revogação |
| Tenant | Contexto atual obtido de fonte confiável, com membership própria ativa e vinculada ao mesmo tenant | Provas positivas/negativas A/B, sessão alterada e revalidação |
| Política | `DenyByDefaultAuthorizer` continua exclusivo até futura decisão específica; regra CORE02-D05 B (P1+P2 escopada) e M2 são bases documentais, GOV02 não aprovada | Contrato versionado de recurso/ação/campo/escopo, proveniência de grants/denies e revisão de competência; nenhuma promoção automática |
| MFA | Exigir o nível proporcional ao risco do ato segundo CORE04/SEG; sem determinar fator, TTL ou step-up nesta etapa | Matriz de risco e aceite do owner Segurança, com cenários de reautenticação |
| PostgreSQL/RLS | RLS non-bypass com role não-owner; FKs tenant-bound, integridade, GUC transacional isolada e limpa, tratamento de pool contaminado | Evidência PostgreSQL16 real em ambiente autorizado, roles/SQL revisados e testes cross-tenant |
| Campos | Apenas CAM-01/02 visíveis após homologação; proibir listagem e vazamento de CAM-03..10; escaping seguro | Inventário de campos/owners, mapeamento D04/D06, análise LGPD, casos de injection/XSS e dados de terceiros |
| Estado | Não inferir identidade, autorização ou direito apenas de plano pago/ativo; estado de trial/cortesia/suspenso deve ter contrato próprio | Matriz EST-01..14 com decisões CORE08/FIN/CON e provas por tenant |
| Revogação | Revalidar antes da operação protegida e invalidar permissões/cache que não sejam mais vigentes | Casos de revogação entre requests e em concorrência; sem prometer linearização global sem teste |
| Interface/canais | Tela interna do CRM apenas; nenhum endpoint externo, export ou canal alternativo liberado por UI | Revisão de rotas/autorização independente de UI, estado de carregamento/negação genérico |
| Cache | Evitar cache compartilhado, conteúdo sensível em localStorage, histórico/browser indevido ou reuso entre identidades; `no-store` é **candidato**, não política aprovada | Threat model de caching, sessão, headers e renderização; aceite de Segurança |
| Observabilidade | Negação/erro sanitizado, proporcional e com acesso restrito, sem ID de terceiro, CPF, SQL, JWT, segredo ou payload | Contrato CORE06/LGPD, classificação, base/finalidade, retenção e evidência controlada |
| Falhas técnicas | DB, Redis, sessão, MFA, auditoria mandatória ou contexto contaminado indisponíveis => fail-closed, sem mascarar como dado válido | Negativos de infraestrutura, timeout, rollback e isolamento por conexão |

### 4.1 Ameaças específicas do recorte
- **Tenant spoofing:** query/header/hidden form manipula `tenant_id`; não resolver acesso apenas por `app.tenant_id`.
- **IDOR:** principal altera URL/identificador para extrair nome/estado de empresa B ou membership de outra pessoa.
- **Sessão obsoleta:** renderizada antes da revogação e reutilizada após mudança de vínculo; exigir nova prova por requisição protegida e desenhar limites de consistência.
- **Escalada por papel:** rótulo “administrador” ou M2 usado como prova de permissão; proibir competências implícitas.
- **Vazamento entre canais:** UI interna oculta campo, mas resposta ou endpoint alternativo ainda entrega o dado; enforcement deve ocorrer no servidor.
- **XSS por nome comercial:** organization.display_name pode conter caracteres não confiáveis; parametrizar/escapar na UI futura.
- **Logs/cache:** correlator interno ou nome de empresa deixado em logs/analytics/headers/caches compartilhados; não armazenar sem finalidade e política.
- **Falha de dependência:** ausência de registro/membership/Redis interpretada como “ativar por padrão”; negar sem revelar existência do tenant.

**Proposta de negativas a usar na homologação futura:** S01-T03..T22 da [especificação original](FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md), com cobertura obrigatória para controle de campo, P1+P2 contextual, múltiplos tenants, membership revogada, estado comercial desconhecido, falha de banco e ausência de canal alternativo. Não executar estas provas agora.

## 5. Escopo de responsabilidade e bloqueios antes de código

**Donos por função sugeridos, NÃO pessoas formalmente designadas:**
- **Organizações/Produto:** atributo de nome, fonte oficial, regra de exibição e lista de campos.
- **IAM/CORE07:** identidade/membership, associação do próprio vínculo, seleção do contexto corrente e revogação.
- **Segurança/CORE04/SEG/DBA:** sessão, MFA, SQL/RLS, papéis de banco, caching, análise de ameaças, evidências.
- **CORE08/FIN/CON:** separar assinatura/plano/entitlement de identidade, política para trial/cortesia/atraso/cancelamento e acessos mínimos legítimos.
- **Privacidade/LGPD/CORE06:** classificação, finalidade, log mínimo, retenção, acesso a evidências e direitos legais.
- **QA/CORE10 e direção:** matriz de aceites, critérios de não-regressão, autorização futura de branch/código e avaliação de risco.

**Gates impeditivos:** 67 IDs CORE (incluindo CORE01-D04..D07, CORE02-D04..D10, CORE04/06/07/08/09/10), SEG-01/02, AUT-P01..14, ARQ/INT/FIN/CON/LGPD/G01..G06; CORE02-D05-B e GOV01 M2 ratificados documentalmente mas não implementados, GOV02 A–D permanece NÃO aprovada. Nenhum deles é considerado fechado por esta R01.

## 6. Próxima deliberação recomendada à direção: R01-D01..D05

| Decisão futura | Alternativa A (recomendada para estudo) | Alternativa B | O que continua pendente mesmo com aceite |
|---|---|---|---|
| **R01-D01 — Visibilidade** | Mostrar só CAM-01 e CAM-02; CAM-03..10 não visíveis | Acrescentar nome de papel, referência ou estado | Catálogo físico, classificação e autorização de campo continuam abertos |
| **R01-D02 — Membership** | Só vínculo próprio **ativo e comprovado** torna a operação elegível; outros estados negam S01 | Incluir estados não ativos em autoconsulta | CORE07/04, recuperação e fluxos de convite separados |
| **R01-D03 — Estados da organização** | Separar estado técnico da organização e estado comercial, **sem decidir ainda acesso final** para estados não homologados | Definir uma política consolidada neste momento | CORE08, FIN/CON, portabilidade e entitlement por estado |
| **R01-D04 — Proteções** | Priorizar sessões verificadas, tenant server-side, MFA por risco, RLS non-bypass, cache restrito e fail-closed; detalhar provas antes do código | Abrir exceções/atalhos já nesta fase | SEG/CORE01/04/06 e metodologia executável continuam abertas |
| **R01-D05 — Evidência** | Manter S01-T01..T22 como **matriz de aceitação futura** e exigir owner/ambiente/SHA/risco por caso | Reduzir ou mudar a cobertura mediante justificação | QA/CORE10, aprovação de execução de testes e homologação continuam abertas |

**Recomendação:** ratificar **somente as alternativas A de R01-D01..D05 como diretrizes documentais de trabalho**, **sem fixar regra de estados comerciais**, política concreta de MFA, algoritmo P1/P2, dados pessoais, formato JSON, endpoints, SQL, `ALLOW`, requisitos de infraestrutura ou aceite de testes. Estas diretrizes seriam base da próxima especificação, não GO para programação.

**Solicitação de decisão:** aprovar, ajustar ou rejeitar cada R01-D01..D05. Sem manifestação expressa, todas permanecem **PROPOSTAS NÃO APROVADAS**. A equipe não deve iniciar Codex ou engenharia funcional só porque o documento existe.

**Preservações:** branch `main` inalterada, [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) e [PR #21](https://github.com/rafhael100/imagine-crm/pull/21) DRAFT sem merge, VPS e clientes sem mudanças.
