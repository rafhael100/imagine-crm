# CRM Imagine — CORE02-D05-GOV01: quem gerencia perfis, grants e denies — proposta v0.1

**Data:** 06/11/2026.  
**Estado atualizado em 06/11/2026:** **MODELO M2 RATIFICADO EXPRESSAMENTE COMO DIRETRIZ DOCUMENTAL**, conforme [decisão de aprovação](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV01-M2-2026-11-06.md). CORE02-D05 integral permanece ABERTA; este documento é pauta subordinada, não novo identificador CORE nem liberação de programação. As referências abaixo a modelos “não aprovados” refletem a discussão histórica antes do aceite.
**Base:** [ratificação expressa da alternativa B](CRM-DEC-ratificacao-explicita-CORE02-D05-alternativa-B-2026-11-06.md): unir grants válidos dentro do mesmo tenant e fazer prevalecer negação explícita válida apenas no escopo correspondente, sob bloqueios mandatórios.

## Problema de produto

O CRM permitirá perfis para equipes, vendedores, gestores e administradores, conforme plano. Precisamos decidir **quem poderá criar, modificar, atribuir ou revogar um perfil e estabelecer um deny explícito**, sem transformar qualquer usuário com `read` ou `edit` em alguém autorizado a delegar poderes.

## Alternativas avaliadas — M2 aprovado como diretriz, M1/M3 não selecionados

| Modelo | Governança proposta | Benefícios e ressalvas |
|---|---|---|
| **M1 — somente administrador principal** | Apenas o administrador principal da organização pode criar/editar/atribuir funções e denies de seu tenant, respeitando direitos do plano | Fácil de controlar, mas pode sobrecarregar empresas maiores e dificultar substituições autorizadas |
| **M2 — administrador competente com delegação restrita** | Administrador principal ou outro administrador **expressamente delegado, quando o plano permitir**, pode gerir apenas perfis, grants e denies para os quais tem **competência de delegar**, respeitando tenant/ação/campo/escopo/plano e políticas superiores; criar, atribuir e negar são direitos distintos | Escala bem para equipes e filiais, exige UX de prévia e prevenção de autoelevação |
| **M3 — gestor de equipe edita perfis da equipe por padrão** | O cargo de gestor habilita administração das permissões de sua equipe automaticamente | Simples à primeira vista, mas cargo não comprova competência de delegar; pode criar elevação e conflito entre equipes |

**Modelo expressamente escolhido:** **M2**. É compatível com a política B recém-ratificada e evita exigir um segundo aprovador para toda mudança. Casos de risco elevado exigem MFA/step-up ou alçada específica conforme CORE02-D07 e CORE04, sem impor aprovação dupla universal.

## Princípios da diretriz M2 ratificada

1. **Separação de competências:** criar/editar perfis, atribuir/remover perfis, configurar denies, delegar competência e aprovar ações críticas são operações distintas. Direito de usar `export` não é direito de conceder `export` a outros.
2. **Escopo máximo de delegação:** o administrador competente só gerencia ações, campos, equipes, unidades, canais e recursos que sua competência de **delegar** efetivamente cobre, e jamais cria entitlement, assento ou plano não contratado. Cargo de gestor não basta.
3. **Proibição de autoelevação:** usuário não pode usar edição de papel/deny, delegação cruzada, sessão de suporte ou IA para ampliar os próprios direitos, desativar MFA, suprimir negação mandatória, obter acesso a outro tenant ou alterar permissões centrais Imagine.
4. **Proteção do administrador único Básico:** manter exatamente um usuário humano autenticado com MFA; modelo não autoriza um segundo login, administrador substituto permanente ou bypass de recuperação.
5. **Prévia e explicação:** na interface de administração mostrar quais usuários e escopos serão afetados por novo grant/deny, diferenças entre sem permissão e negado explicitamente, alertas de conflito e motivos sanitizados; operações críticas exigem confirmação contextual apropriada.
6. **Revisão, revogação e auditoria:** registrar autoria competente, fonte, objeto, tenant, escopo, versão e momento da alteração, revalidar efeitos posteriores em sessões, jobs, arquivos/exportações e acessos; preservar fatos já confirmados legitimamente.
7. **Acesso excepcional da Imagine:** nenhum empregado/suporte/agente Imagine ganha poderes em tenants por função central; somente mandato específico ou contrato autônomo competente, segundo CORE05/SEG/INT.

## Itens ainda não decididos mesmo sob M2

- Limites quantitativos de perfis por plano e extras, escopo detalhado de delegação, quais alterações exigem step-up e como resolver conflito entre administradores ou último admin.
- Herança, apresentação visual, mudanças simultâneas, versionamento, cache, tempos de expiração, procedimentos de revogação/contestação e auditoria efetiva.
- Catálogo por módulo e classificação de dados, CORE02-D04/D06, CORE07, CORE04, CORE08, SEG-01/02, FIN/CON e INT; nenhuma homologação ou implementação.

**Decisão de M2 concluída documentalmente:** aprovada pelo responsável; [ratificação expressa](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV01-M2-2026-11-06.md). Próxima pauta: CORE02-D05-GOV02, sobre limites de delegação e resolução de conflitos, que permanece não aprovada. Sem fechar D05 ou qualquer gate, alterar código, `main`, branch Codex, VPS ou produção.
