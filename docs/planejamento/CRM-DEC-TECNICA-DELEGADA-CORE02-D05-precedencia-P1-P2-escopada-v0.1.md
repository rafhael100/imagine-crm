# CRM Imagine — decisão técnica delegada: precedência de perfis CORE02-D05 (P1 + P2 escopada)

**Data:** 04/11/2026.  
**Fonte da delegação do responsável:** “quero que vc aprove o que ainda falta pois precisamos inicir o desenvolvimento”.  
**Decisão:** **SELECIONAR COMO BASE DE POLÍTICA TÉCNICA DOCUMENTAL** a alternativa **P1 — união de grants positivos válidos**, estritamente subordinada aos bloqueios mandatórios; e **P2 — precedência de negação explícita apenas quando formalmente válida para o mesmo tenant, recurso, ação, campo e escopo pertinente**. Rejeitar como padrão **P3 interseção universal** e **P4 papel principal exclusivo**, sem impedir casos especiais do proprietário de módulo aprovados em contrato.
**Escopo:** esta é uma **escolha normativa de política documentada por avaliação técnica delegada**, não aprova roles SQL, caching, schema, UI, concessões/deny de usuários reais ou o fechamento integral de CORE02-D05. **CORE02-D05 permanece ABERTA.**
**Fontes:** [CORE02-D05 v0.1](CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md), [aceite preliminar D05-A/B/C/D](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md), [CORE02 matriz](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md), [CORE01-D07](CRM-CORE-P0-ETAPA2-CORE01-D07-modelo-fisico-integridade-RLS-proposta-v0.1.md), [E2-R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md).

## 1. Regras normativas escolhidas para projeto posterior

1. **Deny by default:** identidade e tenant/mandato legítimos, membership, entitlement, estado, finalidade, recurso, ação e campo devem ser verificados antes de usar grants de papéis. Ausência/inconclusão de autorização confiável **nega o ato protegido** e oferece diagnóstico seguro.
2. **Bloqueio mandatário é superior a papéis:** isolamento de tenant, RLS, MFA/alçada por risco, limitações de plano/quotas, revogação, consentimento/finalidade exigíveis, classificador de campo e estado do objeto não podem ser contornados por grant do cliente, administrador Imagine ou agente.
3. **P1 grants positivos dentro do limite:** se mais de um perfil do usuário **no mesmo tenant** conceder validamente ação+recurso+campo no escopo verificado, a combinação de concessões pode permitir a ação **apenas na união dos subescopos válidos e explicitamente permitidos**. Nenhuma permissão cruza tenants, canais, planos, equipes ou objetos sem direito próprio. Uma posição de gestor não cria automaticamente direito de export, FIN, ou suporte.
4. **P2 negações explícitas qualificadas:** uma negativa **expressa, ativa, atribuída por autoridade competente e com proveniência**, prevalece sobre qualquer grant positivo que tenha **exatamente** o mesmo tenant, ação, recurso e campo, **na interseção do escopo do deny**. Fora dessa interseção, os grants válidos são avaliados normalmente. Negações de perfis não criam veto global da identidade, de outra organização ou de operações internas essenciais com autoridade técnica própria. Valores de escopo/campo ausentes **não** viram curinga sem contrato específico aprovado.
5. **Herança e delegação:** herdar permissões somente de relações e versões previstas no catálogo. Conceder/gravar um grant requer **competência de delegar** distinta de poder executar a ação. Proibir autoelevação e grant que ultrapasse o entitlement/tenant. Revogar membership, papel ou delegação deve afetar novas operações e entregas ainda protegidas, com idempotência/auditoria.
6. **Exceções legítimas não implícitas:** direitos legais de portabilidade, atendimento, backups de segurança, administração emergencial e eventos históricos confirmados obedecem às próprias autoridades e contratos. Uma negativa humana não cancela serviço técnico legítimo autônomo por analogia, nem autoriza operação proibida.
7. **Canal e dado protegido:** a política aplica-se a API, tela, pesquisa, relatório, arquivo, import/export, automação e worker; direitos de leitura não são automaticamente direitos de exportação, aprovação, exclusão, envio ou acesso a segredo. A permissão conjunta de ver/baixar anexo permanece única, sujeita a bloqueios de segurança de arquivo.
8. **Resposta segura:** decisão do autorizador será explicável ao operador competente (fonte, versão e categoria do motivo) sem expor regras internas confidenciais, PII, segredos e registros de outro tenant. Proprietário funcional confirma qualquer resultado oficial, não o executor n8n.

## 2. Tabela exemplificativa de precedência — contrato conceitual

| Cenário | Resultado esperado pela política escolhida |
|---|---|
| Um perfil concede consulta a contatos da equipe A; segundo concede equipe B | União de contatos **de A e B autorizados**, desde que tenant, campo, estado e entitlement também permitam |
| Perfil P1 concede export de A; negativa explícita válida do perfil P2 veta export de A | Negar export dos registros/campos escopados a A, sem bloquear consultas normais nem B legitimamente autorizada |
| Um perfil da empresa A nega exclusão; usuário também participa da empresa B | Negação da empresa A **não** se propaga a B |
| Papel de funcionário concede edição, mas plano não tem o recurso | Negar, pois entitlement e contrato proprietário prevalecem |
| Usuário pode ver lead, mas não possui `export_data` | Negar export |
| Administrador Geral tenta consultar dados comerciais do cliente sem mandato | Negar, independentemente de eventual role central |
| Agente/n8n recebe instrução de elevar papel ou mudar plano | Negar; identidade técnica e papel humano não conferem alçada comercial |
| Job retoma após revogação de membership e precisaria de autoridade pessoal | Revalidar e negar novo efeito; não desfazer transação oficialmente concluída |
| Arquivo malicioso bloqueado com direito ver/baixar | Negar conteúdo e derivados conforme política de segurança |

## 3. Limites técnicos ainda abertos (NÃO homologados)

- **CORE02-D05 integral ABERTA:** owners por módulo devem validar a regra e exceções, catálogo real de papéis e escopos, representação física de grants/denies, cache/versionamento, contratos de revogação e auditoria, conflitos com times/unidades/canais, critérios de aceitação e riscos LGPD.
- **CORE02-D04/D06..D10 ABERTAS**, CORE01-D04..D07 e CORE03..CORE10-D04..D10 ABERTAS, SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON e baseline final NO-GO. **67 IDs CORE não encerrados.**
- Essa escolha de P1+P2 **é feita sob delegação**, não atribuída a fala específica do responsável; caso o proprietário responsável por operação identifique conflito com regra funcional aprovada, ele requer reconciliação documental, não um grant silencioso.

**Nenhuma programação, SQL, policy real, testes, migração, contas, dados de cliente, credenciais, contratação ou deploy foi autorizado ou executado por esta decisão.**
