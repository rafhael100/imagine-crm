# CRM-DEC — Ratificação expressa CORE02-D05-GOV02-A/B/C/D (diretrizes preliminares)

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação do responsável:** **“sim”** em resposta à pergunta explícita **“Você aprova GOV02-A, B, C e D para continuarmos?”**, após apresentação dos quatro princípios de [CORE02-D05-GOV02 — limites de delegação e conflitos](CRM-CORE-P0-ETAPA2-CORE02-D05-GOV02-limites-delegacao-conflitos-proposta-v0.1.md).  
**Natureza:** aprovação **somente documental preliminar**, subordinada à política B de precedência CORE02-D05 e ao Modelo M2 já ratificados. **CORE02-D05 integral permanece ABERTA**, assim como o restante do CORE02 e todos os gates transversais.

## Diretrizes aprovadas

1. **GOV02-A — competência efetiva para delegar:** toda concessão, revogação, negação e atribuição deve respeitar o poder específico de administrar recurso/ação/campo/escopo, no tenant e plano legítimos. Direito de uso não equivale à competência de delegar. Cargo de gestor não produz autorização automática.
2. **GOV02-B — separação de operações e prevenção de autoelevação:** gerenciar papéis, atribuir/remover papéis, conceder/revogar grants, configurar denies, delegar administração e transferir administrador são atos distintos. Bloquear alterações diretas ou indiretas que aumentem indevidamente os próprios privilégios; confirmações e step-up apenas segundo risco/alçada definidos pelo proprietário, sem segunda aprovação universal.
3. **GOV02-C — prévia transparente e conflitos por escopo:** antes de confirmar alteração, informar de modo seguro quem será afetado e quais direitos serão concedidos/bloqueados; distinguir grant, ausência de grant, deny explícito e bloqueio mandatário, sem expor informação protegida, segredo ou dados de outro tenant. Impedir alteração inconclusiva ou além da competência.
4. **GOV02-D — concorrência, revogação e auditoria:** versionar alterações, detectar edições simultâneas conflitantes, registrar contexto/ator/autoridade/escopo e revalidar novos efeitos protegidos em sessões, jobs, anexos, exportações e agentes. Evitar reativar grants por cache ou repetição; preservar fatos oficiais já concluídos legitimamente.

## Limites e pendências

- **GOV02-A/B/C/D preliminarmente ratificadas; GOV02 não é novo item CORE**, e **CORE02-D05 permanece ABERTA** até: definição de papéis concretos, autoridade real por ação, hierarquia de administração, conflitos/versionamento físicos, duração da delegação, tempos de revalidação, MFA/step-up, exceções e matriz de direitos por módulo/plano com homologação.
- Preservar **alternativa B (P1 + P2 específica e contextual)** e **Modelo M2**; não reabrir precedência, nem ampliar poderes de administração por cargo. O plano Básico mantém um único administrador humano autenticado com MFA.
- CORE02-D04/D06..D10, CORE01-D04..D07, CORE03..CORE10-D04..D10, 67 identificadores CORE, SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD e DEV-GATE global ainda não têm fechamento técnico. Nenhuma permissão, quota, preço, schema, algoritmo de cache, política SQL/RLS ou ferramenta nova é homologada.
- Esta decisão **não autoriza programação adicional Codex, testes executáveis, alteração de branch de desenvolvimento, merge, concessões em usuários reais, compras, deploy ou modificação da VPS/produção**. A frente documental permanece isolada do fast-track.

**Próximo trabalho:** reconciliar a governança de perfis com CORE07-D05/D06/D07 e CORE04, concentrando-se primeiro em uma decisão concreta sobre convite pendente, reserva de assento e concorrência, sem presumir política comercial final.
