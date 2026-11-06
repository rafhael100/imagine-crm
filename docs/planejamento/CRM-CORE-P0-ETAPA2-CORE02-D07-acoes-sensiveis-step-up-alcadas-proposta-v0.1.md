# CRM Imagine — CORE02-D07: operações sensíveis, MFA/step-up, confirmações e alçadas — pauta v0.1

**Data:** 05/11/2026.  
**Estado atualizado em 05/11/2026:** **CORE02-D07-A/B/C/D RATIFICADAS EXPRESSAMENTE APENAS COMO DIRETRIZES DOCUMENTAIS PRELIMINARES**, conforme [registro de ratificação](CRM-DEC-ratificacao-explicita-CORE02-D07-ABCD-2026-11-05.md). **CORE02-D07 integral permanece ABERTA**, sem catálogo final, fatores/tempos de step-up ou alçadas concretas homologados. Menções posteriores a “não aprovadas” e “para decisão” descrevem o estado histórico desta minuta antes da ratificação.  
**Origem:** continuidade da revisão documental após ratificação expressa preliminar de CORE02-D06-A/B/C/D.  
**Natureza:** critérios funcionais e de risco para aprovação posterior; sem código, políticas executáveis, alteração de contas, dados, grants, segredos, testes, contratação ou implantação.

## 1. Decisões que não serão reabertas por analogia

- [CORE02-D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md), [D04](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md), [D05](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md) e [D06 ratificada](CRM-DEC-ratificacao-explicita-CORE02-D06-ABCD-2026-11-05.md) estabelecem apenas bases semânticas preliminares: negar por padrão, direitos por tenant/membership/entitlement/ação/campo, não superposição de dados, efeitos confirmados pelo proprietário. Precedência concreta P1–P4 segue aberta.
- [CORE04 — autenticação, MFA e sessões](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md): MFA obrigatório para administradores centrais e da empresa (inclusive único administrador Básico); política de funcionário conforme contexto; sessão da administração central com 30 minutos de inatividade conforme decisão histórica. Reautenticação, fator, prazo de step-up, recuperação e revogação concretos seguem pendentes.
- Ação crítica na administração Imagine exige **confirmação pelo mesmo administrador autorizado**, sem impor segunda aprovação universal; alçadas específicas de finanças, concessões, consentimento ou risco não são revogadas.
- Suporte Imagine sem mandato não lê dados comerciais de clientes; agente/IA/n8n sem autoridade específica não eleva acesso. RLS é defesa adicional e não substitui autorização de negócio.
- Direitos à portabilidade/importação/exportação precisam de política própria e autoatendimento nos limites legítimos, sem serem presumidos por `read`; não bloquear direitos legais ou dados do próprio titular por capricho de papel.

## 2. Matriz candidata de risco por ação (exemplos, não autorização)

| Ação ilustrativa | Risco a revisar | Condição candidata a especificar pelo proprietário |
|---|---|---|
| Visualizar lead próprio ou tarefa comum | Baixo a moderado conforme campos | Sessão/membership/plano/escopo válidos, classificação e finalidade; sem step-up universal |
| Exportar em massa contatos, histórico ou evidências | Elevado (volume, dados pessoais e reuso) | Direito `export` próprio, escopo/campo, justificativa quando aplicável, MFA/step-up proporcional, auditoria e controle de arquivo |
| Excluir/mesclar registros ou restaurar backup | Elevado, potencialmente irreversível | Direito distinto de leitura/edição, confirmação contextual, validação do efeito e recuperação quando aplicável |
| Alterar administradores, perfis sensíveis, e-mail titular ou MFA | Elevado (tomada de conta/autoelevação) | Reautenticação/step-up e prova de competência; regras especiais do último admin Básico |
| Conceder plano ou adicional grátis, reembolso, alterar cobrança | Elevado financeiro/contratual | Autoridade central FIN/CON, escopo, step-up e alçada específica; sem grant automático de funcionário/IA |
| Publicar automação, enviar mensagem externa ou executar IA com efeitos | Variável por ferramenta, volume, destino e consequência | Direito de publicação/ação, versão aprovada, finalidade, confirmação/approval proporcional e executor identificado |
| Acesso excepcional de suporte a dados tenant | Elevado (privacidade) | Mandato por caso/tenant/ação/período, autorização competente, registro e revogação; sem BYPASSRLS irrestrito |
| Gerir credencial de provedor, webhooks, segredos | Crítico (efeitos em outros sistemas) | Identidade técnica verificada, ferramenta tipada, proteção de segredo, rotação/revogação, política SEG-01/02 |

Classificação final, exemplos, limiares de volume, canal, campos e ações serão ratificados pelos proprietários de IAM, DAT, VEN, ATD, AGD, AUT, IA/AGT, FIN/CON, INT, Segurança e Auditoria. Não inferir que recurso esteja em todos os planos.

## 3. Propostas CORE02-D07-A/B/C/D — AINDA NÃO APROVADAS

### D07-A — catálogo de operações sensíveis, risco e proprietário

Exigir que cada ação/efeito defina proprietário, categoria de risco, dados envolvidos, limites por tenant/campo/canal/volume, titular da decisão e condições de recusa. Diferenciar risco de consultar, exportar em massa, apagar, administrar identidades, conceder gratuidade, mandar comando externo ou atuar via agente. **Não criar escala numérica final ou limiares por esta diretriz.**

### D07-B — MFA/step-up proporcionais à ação e ao contexto

Exigir MFA conforme política já aprovada por ator/tenant e pedir reautenticação/step-up para ações sensíveis quando a política do proprietário estabelecer. Um desafio bem-sucedido **não cria** permissão, plano, mandato ou escopo; vincular prova de step-up a identidade, contexto, ação e validade. Reuso, expiração, recuperação de fator, interrupção e efeito em sessões/worker serão decididos junto a CORE04/SEG. **Não exigir MFA repetido para toda leitura rotineira.**

### D07-C — confirmação e alçada de aprovação por operação

Separar **confirmação explícita do operador autorizado** de **aprovação por outro responsável** (aplicável somente a classes de risco/competência previstas). Para ações administrativas críticas comuns, preservar confirmação pelo próprio administrador autorizado; alçada de dois responsáveis somente quando proprietário e política exigirem (ex.: operação financeira excepcional com segregação). A IA pode preparar proposta, mas nunca efetivar ação protegida por inferência de prompt. **Não impor dupla aprovação universal nem permitir autoaprovação quando segregação for obrigatória.**

### D07-D — recusa segura, evidência e revalidação

Exigir negação segura quando faltar competência, MFA, aprovação, mandato, finalidade ou plano; mostrar ao usuário mensagem compreensível sem vazar dados/segredos. Registrar trilha mínima do ator, contexto, decisão, proprietário, versão e motivo técnico sanitizado. Revalidar ao executar operação adiada e ao disponibilizar resultados/exportações após mudança de direitos, sem desfazer automaticamente fato já confirmado. Preparar casos negativos de escalada de privilégio, repetição de ação, sessão revogada, troca de tenant, conflito de aprovação e perda de fator; **sem executar testes nesta pauta**.

## 4. Questões reservadas para decisão posterior

1. Política de reautenticação: quais ações efetivamente exigem step-up, métodos aceitos, janela de validade, falhas/retries, efeitos em sessão e dispositivos.
2. Matriz de alçadas: quais operações exigem confirmação simples, aprovação adicional, delegação legítima ou bloqueio definitivo; quem pode aprovar e prevenir conflito de interesses.
3. Classificação por volume/sensibilidade: limite para exportação em massa e exclusão, política de dados financeiros, histórico, anexos, consentimento e evidência.
4. Resposta de segurança: diferenças de revogação entre sessão humana, job, agente, integrador e suporte; artefatos temporários e auditoria.
5. Compatibilidade entre planos/escopos e direitos legítimos (inclusive direito de portabilidade), CORE02-D05 P1–P4, CORE04, CORE05, CORE06, CORE07, CORE08, CORE09, FIN/CON, INT e SEG-01/02.

## 5. Roteiro de análise com o responsável

- **Decisão 1:** ratificar D07-A/B/C/D apenas como diretrizes preliminares, com CORE02-D07 integral ABERTA; ou solicitar alterações específicas.
- **Decisão 2 (se houver aceite preliminar):** fechar posteriormente a matriz concreta de operações sensíveis com donos dos módulos e Segurança, começando por CORE02-D08 (aplicação/revogação em Laravel, API, RLS, jobs, arquivos e integrações).
- **Não liberar código:** esta pauta não altera o fast-track do Codex. Manter `main`, VPS e produção inalteradas; futuras integrações documentais exigem revisão separada.

**Gates preservados:** CORE02-D04..D10 e demais CORE-P0 por concluir; SEG-01/02, ARQ/AGT, INT P0 G01..G06, FIN/CON, LGPD e aceites finais seguem abertos.
