# CRM-DEC — Ratificação expressa do Modelo M2 de governança de perfis (CORE02-D05-GOV01)

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação:** o responsável respondeu **“aprovado”** à pergunta “Você aprova o Modelo M2 como diretriz documental para continuarmos?”, após apresentação do [CORE02-D05-GOV01 — perfis, delegação e negações](CRM-CORE-P0-ETAPA2-CORE02-D05-GOV01-perfis-delegacao-negacoes-proposta-v0.1.md).  
**Alcance:** **MODELO M2 APROVADO EXPRESSAMENTE COMO DIRETRIZ DOCUMENTAL DE GOVERNANÇA**, subordinado a CORE02-D05 e à política de precedência alternativa B já ratificada. **CORE02-D05 integral permanece ABERTA**, sem grants reais, algoritmo físico, autorização de teste/implementação ou homologação.

## 1. Diretriz escolhida — M2

**Administrador competente com delegação restrita:** o administrador principal da organização, ou outro administrador **expressamente autorizado** nos planos e contextos em que isso for elegível, pode gerenciar exclusivamente os perfis, concessões e negações para os quais detém **competência específica de delegar**, sempre limitada pelo próprio tenant, plano, recurso, ação, campo, equipe/unidade/canal/posse, finalidade, riscos, estados e políticas superiores.

1. **Poder de executar não equivale a poder de conceder:** `read`, `edit`, `export`, `approve` ou gerência comercial isolada não delegam automaticamente o mesmo direito. Criar/editar perfil, atribuir/remover perfil, conceder grant, configurar deny, delegar competência, aprovar atos críticos e transferir titularidade são competências distintas.
2. **Sem delegação automática por título/cargo:** gestor ou responsável de equipe não ganha `manage_roles` por ocupar o cargo; a delegação precisa de autoridade, escopo e evidência oficiais. O modelo M3 não é padrão; M1 não é regra universal para planos com administradores adicionais elegíveis.
3. **Proibição de autoelevação e bypass:** o delegado não pode criar privilégios para si ou terceiros fora de sua competência, eliminar negações mandatórias, desabilitar MFA, conceder plano, créditos, gratuidade, assentos ou módulos não contratados, atuar em outro tenant ou editar poderes centrais Imagine.
4. **Básico:** manter **um único administrador humano autenticado com MFA**; M2 não autoriza segundo login, administrador permanente adicional ou recuperação sem comprovação.
5. **Prévia, proteção e auditoria:** alterações de perfil/grant/deny devem informar escopos e pessoas afetados de modo seguro, diferenciar ausência de grant de negação expressa, registrar autor/versão/competência/justificativa proporcional e revalidar acessos/novos efeitos protegidos. Confirmação e step-up proporcionais ao risco; sem segunda aprovação universal por inferência.
6. **Política B permanece:** concessões positivas válidas no mesmo tenant/subescopo se acumulam; **negação explícita qualificada prevalece apenas na interseção legítima do escopo**; bloqueios mandatórios de segurança, plano, RLS, MFA, LGPD etc. são superiores.
7. **Imagine, suporte, n8n e IA:** papéis centrais, técnicos ou prompts não são memberships do cliente nem autoridade para editar permissões comerciais sem contrato/mandato legítimo e controle próprio.

## 2. Questões abertas e próximos passos

A ratificação não fixa quantos perfis são permitidos, delegação temporal/permanente, limites físicos de recurso/campo, quem pode alterar denies específicos, mecanismos de aprovação, Step-up/TTL, prevenção técnica de conflitos, transações simultâneas, herança/revogação, auditoria, escopo em jobs, modelo Laravel/RLS ou contratos reais de cada módulo.

**Pendente:** CORE02-D05 e CORE02-D04/D06..D10; CORE01-D04..D07, CORE03..CORE10-D04..D10; SEG-01/02, INT P0 G01..G06, CORE07/08, FIN/CON, LGPD e arquitetura. Nenhum dos 67 identificadores técnicos CORE é fechado por este aceite.

**Esta decisão não autoriza** programação adicional do Codex, merge, grant, policy SQL, testes de segurança reais, contratação, infraestrutura, publicação, uso de dados reais ou alterações na `main`/VPS. O desenvolvimento controlado continua exclusivamente em sua branch sob autorizações próprias.

**Próxima pauta documental sugerida:** CORE02-D05-GOV02 — limites operacionais de delegação, conflitos, UX de permissões e revalidação; sem decisão automática.
