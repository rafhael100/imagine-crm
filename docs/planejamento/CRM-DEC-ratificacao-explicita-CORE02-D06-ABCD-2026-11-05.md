# CRM-DEC — Ratificação expressa CORE02-D06-A/B/C/D (05/11/2026)

**Natureza:** aprovação de diretrizes técnicas documentais **preliminares** por manifestação expressa do responsável.  
**Manifestação no contexto:** o responsável respondeu **“ok aprovado”** à apresentação das quatro propostas CORE02-D06-A, B, C e D e à pergunta “Você aprova CORE02-D06-A, B, C e D conforme apresentado?”.  
**Fonte:** [CORE02-D06 — catálogo de ações por módulo](CRM-CORE-P0-ETAPA2-CORE02-D06-catalogo-acoes-por-modulo-proposta-v0.1.md), seções 1–7.  
**Precedência:** confirma expressamente o alcance preliminar da [decisão técnica delegada anterior](CRM-DEC-aprovacao-delegada-CORE02-D06-ABCD-preliminar-2026-11-04.md); não reclassifica o catálogo candidato como homologado.

## Diretrizes expressamente ratificadas

1. **D06-A — catálogo por proprietário:** exigir matriz versionada de módulo, recurso, ação, campo, escopo, contexto, finalidade e dono do efeito. Não converter nomes candidatos em permissões/rotas reais sem aceite do módulo.
2. **D06-B — separação de ações e efeitos oficiais:** leitura/edição/exportação/envio/aprovação/exclusão/importação e mudanças contratuais devem ter direitos distintos quando os efeitos forem distintos. **Manter a permissão conjunta visualizar/baixar anexo** quando prevista, separada de upload/exclusão. Sucesso técnico de integração não comprova efeito de negócio.
3. **D06-C — limites cumulativos e proibição de autoelevação:** identidade/mandato legítimo, tenant, plano/entitlement, papel, equipe/unidade/canal, finalidade, classificação, risco, estado e escopo condicionam a operação. IA, n8n, suporte, administradores centrais e agentes não recebem acesso a dados por analogia; nenhuma combinação cruza tenants ou amplia serviço não contratado.
4. **D06-D — validação e rastreabilidade:** proprietário funcional valida contrato semântico, fontes, versões, ações/negativas, critérios futuros e compatibilidade com CORE10/INT/SEG; usar catálogo semântico compartilhado e **não criar segundo motor IAM**.

## Limites de efeito e pendências

- **CORE02-D06 integral permanece ABERTA** até validação por módulo, ações/campos reais, planos, política física, evidências e homologação. Ratificação de diretrizes **não** equivale à concessão real de grants, aprovação de quotas, publicação de API/rota, schema/RLS, execução de testes ou encerramento de qualquer gate.
- **CORE02-D04 e D05** continuam abertas: classificação real de campos e composição concreta de permissões P1–P4 não foram escolhidas. **CORE02-D07..D10** também permanecem abertas.
- Preservar CORE01-D04..D07, CORE03..CORE10-D04..D10, SEG-01/SEG-02, ARQ-01/02/03, AGT-01, FIN/CON, LGPD, INT P0/G01..G06, 67 identificadores CORE técnicos e critérios de homologação. Não alterar regras comerciais, cronogramas, orçamentos ou fornecedores.
- O **Codex permanece na frente de desenvolvimento controlado separada**, na branch `dev/fast-track-crm-2026-11-04`, sob autorizações específicas. Esta ratificação documental **não** autoriza trabalhos adicionais no Codex, merge para `main`, execução em VPS ou produção.

## Próximo ponto

Preparar proposta **CORE02-D07 — operações sensíveis, step-up/MFA, confirmação e alçadas**, para análise e aceite posterior. Esta autorização é de **planejamento documental** somente; não é aprovação de CORE02-D07.
