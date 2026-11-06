# CRM Imagine — CORE02-D05-GOV02: limites da delegação, conflitos e proteção de perfis — proposta v0.1

**Data:** 06/11/2026.  
**Estado atualizado em 06/11/2026:** **GOV02-A/B/C/D RATIFICADAS EXPRESSAMENTE APENAS COMO DIRETRIZES DOCUMENTAIS PRELIMINARES**, conforme [registro formal](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV02-ABCD-2026-11-06.md). **CORE02-D05 integral permanece ABERTA**; nenhuma regra executável foi homologada. Menções posteriores a “não aprovadas” documentam o estado histórico da proposta.  
**Dependência:** GOV02 é uma pauta subordinada ao **CORE02-D05 ainda ABERTO**, e não novo identificador CORE. Preservar [alternativa B ratificada](CRM-DEC-ratificacao-explicita-CORE02-D05-alternativa-B-2026-11-06.md) e [M2 ratificado](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV01-M2-2026-11-06.md).  
**Objetivo:** transformar as bases B + M2 em diretrizes operacionais claras para o produto, sem antecipar implementação, direitos reais, algoritmos, escala de risco, MFA detalhado, custos ou limites por plano.

## 1. Exemplos de problema concreto

- O administrador A tem competência para conceder leitura e edição de contatos da equipe Nordeste; ele tenta atribuir a outro usuário `export` de toda empresa. Deve ser negado: **competência de delegar** e escopo insuficientes.
- Um administrador delegado tenta alterar o próprio perfil para retirar uma restrição explícita imposta por autoridade superior. Deve ser negado: alteração não pode criar autoelevação.
- Uma negação de exportação da equipe Nordeste conflita com grant de um perfil de relatórios. Política B determina que o veto válido prevaleça **apenas no subconjunto Nordeste**, não globalmente.
- Dois administradores editam o mesmo perfil; um salva versão desatualizada. O sistema deve detectar conflito e não sobrescrever silenciosamente nem reativar direitos revogados.
- O último administrador do Básico perde MFA. A recuperação exige processo seguro CORE04/CORE07; não abrir segundo assento ou conta permanente por perfil auxiliar.

## 2. Diretrizes GOV02-A/B/C/D preliminarmente ratificadas

### GOV02-A — Regra de competência de delegar e hierarquia limitada

Exigir que cada concessão, revogação, negação explícita ou transferência seja avaliada pela **competência específica de administrar aquela ação, campo e escopo**, limitada ao tenant e entitlement vigentes. Quem apenas possui uma ação para uso pessoal não pode delegá-la por analogia. Administrador delegado não modifica privilégios centrais ou administradores sobre os quais não tenha autoridade expressa. Não criar hierarquia global tácita pelo rótulo “gestor”.

### GOV02-B — Autoalteração e separação das operações sensíveis

Proibir alterações que criem **autoelevação**, ainda que indireta via perfil compartilhado, substituição de aprovador, delegação em cadeia ou edição de deny. Separar: `criar/editar perfil`, `atribuir perfil`, `conceder/revogar grant`, `configurar deny`, `delegar administração` e `transferir admin`. Mudança crítica requer confirmação contextual e step-up/alçada **somente conforme classe de risco e proprietário**, sem impor duas aprovações universais.

### GOV02-C — Prévia de conflitos e explicação dos direitos efetivos

Antes de confirmar modificação, apresentar em linguagem compreensível **quem será afetado e o que será permitido/bloqueado** no escopo aplicável, distinguindo (1) direito concedido, (2) direito não concedido, (3) negativa explícita válida e (4) bloqueio mandatário. Bloquear operações inconclusivas ou que excedam a competência do solicitante. Diagnósticos para administradores autorizados não devem revelar dados/segredos ou outros tenants.

### GOV02-D — Concorrência, revogação e rastreabilidade

Exigir versionamento de perfis e alterações, verificação de conflitos entre edições simultâneas, confirmação de ator/competência/escopo, auditoria sanitizada e revalidação dos novos efeitos em sessões, filas, arquivos, exportações e agentes. Alteração de deny revogada ou sessão antiga não reativa grants por cache ou replay. Não reverter automaticamente fatos comerciais já efetivados legitimamente.

## 3. Pontos que **não** ficam decididos pelas diretrizes

- Número de administradores por plano, quantidade de perfis/atribuições, cobrança por adicional, papéis concretos por módulo, quem detém competência para modificar cada administrador; CORE07/08/FIN/CON.
- Alçadas específicas e quando exigir step-up, recuperação de último administrador, confirmação de identidade, duração de delegações, casos excepcionais, janela de contestação e tempos de propagação; CORE04/05/07, SEG-01/02.
- SQL, RLS e caches, validações Laravel, operações físicas de API, retries, bancos, recursos específicos do Básico, implementação e testes de homologação; CORE01/03/08/09/10, INT e Security.
- Política B, M2, direito de portabilidade legítima, segregação entre tenants e direitos comerciais já aprovados **não** são reabertos.

## 4. Encaminhamento proposto

**Ratificação GOV02-A/B/C/D expressa registrada**; manter **CORE02-D05 integral aberto** e revisar conjuntamente CORE02-D04/D06/D07/D08, CORE04, CORE07 e SEG antes de homologar qualquer política real. A próxima discussão documental prioriza assentos/convites em CORE07-D05, sem nova aprovação tácita.

**Sem autorização nesta pauta para alterar código, branch Codex, `main`, acesso real, concessão, testes executáveis, infraestrutura, VPS, cobrança ou produção.**
