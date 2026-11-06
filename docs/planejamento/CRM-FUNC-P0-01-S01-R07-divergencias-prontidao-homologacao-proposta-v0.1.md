# FUNC-P0-01 / S01-R07 — Divergências, autoridades e prontidão documental para homologação (PROPOSTA v0.1)

**Data:** 06/11/2026. **Status:** **MINUTA PARA AVALIAÇÃO, NÃO APROVADA**.  
**Precedentes:** decisões R01-D01..D05-A e R02..R06-D01..D08-A ratificadas **somente como diretrizes documentais preliminares**. Ver [DEC-R06](CRM-DEC-FUNC-P0-01-S01-R06-D01-D08-alternativas-A-2026-11-06.md).  
**Objetivo:** transformar perguntas recorrentes em um **inventário verificável de lacunas**, autoridades de resposta e critérios objetivos de prontidão documental dos contratos **R02-C01..C08**, sem fechar nenhum deles.
**Restrições:** somente documentação, em branch isolada. Não alterar `main`, PRs #2/#21, DEV, CI, VPS, código, SQL, ALLOW/GRANT, testes funcionais, produção, cobrança, credenciais ou dados reais.

## 1. Baseline de decisão versus pendência

| Tema | Já ratificado como diretriz documental | Falta definir/provar antes de homologar |
|---|---|---|
| **S01 escopo** | autoconsulta humana da própria organização e do próprio vínculo, só dois campos conceituais | quais estados são exibíveis, comportamento de negativa, catálogo classificado, owner de dados |
| **IAM** | principal/tenant/membership confiáveis; contexto de tenant não arbitrário | evidência de sessão/MFA por papel, versão do vínculo, revogação e precedência |
| **Isolamento** | aplicação + RLS, verificação/revalidação por operação, não enumeração | papéis, controles anti-bypass, consistência de cache/filas e homologação técnica separada |
| **Comercial** | estado do CRM pago independente do site; fontes oficiais por produto; conflitos conciliados | acordo de eventos e autoridade de cada transição, eficácia temporal, retenção, versões |
| **Direitos** | estado comercial ≠ membership ≠ entitlement ≠ permissão | matriz por ação/recurso/campo, exceções e efeitos de revogação |
| **Auditoria/LGPD** | evidência mínima e segregada, retenção por finalidade/categoria | categorias oficiais, responsabilidades, prazos e critérios de descarte |
| **Governança** | fichas de evidências versionadas e aceite por domínio competente | aprovação de responsáveis, documentação íntegra e provas futuras |

**Status em 06/11/2026:** todos os contratos R02-C01..C08 = **PENDENTES**. Todas as decisões preliminares **não** equivalem à conclusão/homologação.

## 2. Inventário de divergências e incertezas (não resolver por presunção)

| Lacuna | Fonte/conflito a cotejar | Encaminhamento documental | Domínio de validação proposto |
|---|---|---|---|
| **GAP-01** | pauta literal original R01-D01..D05 não localizada como arquivo canônico | recuperar fonte original; vincular texto exato às escolhas A ou registrar diferença, sem inventar conteúdo | CORE/SEG + responsável pelo produto |
| **GAP-02** | contas vinculadas e troca de organização; membership ativa vs revogada | definir fonte e validade de contexto confiável, versão e não enumeração | CORE01/02/04 + SEG |
| **GAP-03** | exibição do estado próprio de vínculo suspenso/revogado | decidir precisamente quando há consulta autorizada; caso contrário negar sem revelar existência | CORE01/02 + LGPD/SEG |
| **GAP-04** | compatibilidade entre autorização de aplicação, RLS e política IAM | catalogar papéis, pontos de revalidação e negações conceituais; não criar SQL | CORE01/02/03 + SEG |
| **GAP-05** | prazos/estados trial, CRM pago e cortesia; REV02-D01..D12/P01..P12 | cotejar por produto e evento: 14/60, 7/30/120, transições de 30 dias, com marcos independentes | FIN/CON, Portal, CORE08, LGPD |
| **GAP-06** | contrato Portal↔CRM, múltiplos sites, reativação e inadimplência | identificar owner do fato, org/contrato, eficácia, idempotência e precedência | Portal, FIN/CON, INT |
| **GAP-07** | direito já comprovado em divergência versus bloqueio de novas concessões | definir mecanismo documental de conciliação e contingência, sem suspensão arbitrária | FIN/CON, CORE02/03, SEG |
| **GAP-08** | retenção auditoria: histórico 12 meses comercial vs 5 anos administração global e regimes de segurança | qual categoria, base legal, acesso, mascaramento e descarte; não generalizar prazos | Auditoria, LGPD, SEG |
| **GAP-09** | owners por contrato ainda apenas candidatos | exigir designação formal de autoridade e segregação de revisão por C01..C08 | Governança produto + donos dos domínios |
| **GAP-10** | evidências futuras de segurança não executadas | elaborar casos positivos e negativos com versões e autorização separada para testes | SEG, CORE, owners, DEV-GATE |

**Nenhuma lacuna acima fica resolvida por sua inclusão neste inventário.**

## 3. Checklist de prontidão documental por contrato

| Contrato | Evidência mínima futura de prontidão documental | Verificações negativas futuras | Situação |
|---|---|---|---|
| **C01** sessão/MFA | política versionada de autenticação/MFA por papel, tratamento de sessão revogada | sessão forjada, expirada, step-up insuficiente | **PENDENTE** |
| **C02** tenant/membership | mapeamento autoritativo principal×tenant×vínculo e troca segura | outro tenant, identidade de terceiro, vínculo não aceito | **PENDENTE** |
| **C03** RLS/revogação | matriz CORE/SEG sobre autorização Laravel/RLS/revalidação; sem SQL | IDOR, cache após revogação, role bypass | **PENDENTE** |
| **C04** campos e erro | catálogo de dois campos e regras de resposta não enumerativa | exposição de CNPJ, funcionários, outros vínculos | **PENDENTE** |
| **C05** fatos comerciais | fonte oficial de estados CRM/Portal/INT, versão, eficácia, correlação | callback não conciliado, evento tardio, tenant divergente | **PENDENTE** |
| **C06** autorização | matriz de ação×recurso×principal×tenant×entitlement×vigência | plano ativo libera exportação, site concede login extra | **PENDENTE** |
| **C07** reconciliação | políticas documentais de duplicidade, ordem, ausência e incidentes | replay, cobrança dupla, benefício restaurado por evento antigo | **PENDENTE** |
| **C08** privacidade | finalidades, categorias, minimização, acesso, prazo e descarte | token em log, consulta irrestrita, prazo indevido | **PENDENTE** |

A evidência de prontidão documental não substitui **prova executada** de segurança, nem encerra gate. A execução desses testes futuros depende de autorização distinta.

## 4. Proposta de classificação objetiva para gerir o andamento (não muda estados existentes)

Sugere-se distinguir `PENDENTE_FONTE`, `PENDENTE_OWNER`, `EM_REVISAO_DOCUMENTAL`, `DOCUMENTALMENTE_APTO_PARA_HOMOLOGACAO` e `HOMOLOGADO_COM_PROVAS` **apenas como categorias candidatas de acompanhamento**. O estado real de todos C01..C08 permanece `PENDENTE`. Nenhuma mudança de nomenclatura atual é aprovada. Um contrato apto documentalmente **não está seguro, validado em testes ou liberado**.

Para cada futura ficha, exigir referência e versão da fonte, conflito registrado, decisão, owner e aprovador nomeados, data, riscos, critérios negativos e dependências abertas; confirmação explícita dos gates antes de qualquer ação técnica.

## 5. Próximas decisões propostas R07-D01 a R07-D08 — NÃO APROVADAS

| ID | Alternativa A recomendada apenas como diretriz documental preliminar | Alternativa B de contraste |
|---|---|---|
| **R07-D01** | **A:** registrar GAP-01..GAP-10 como divergências abertas e exigir fonte canônica antes de conclusões | B: inferir texto e precedência faltantes |
| **R07-D02** | **A:** preservar separação entre escopo mínimo S01 e fatos comerciais internos | B: adicionar contratos, preços e pagamentos à autoconsulta |
| **R07-D03** | **A:** exigir owners formalmente confirmados por contrato e autoridade | B: tratar owners candidatos como aprovadores automáticos |
| **R07-D04** | **A:** condicionar prontidão documental de C01..C04 a evidências IAM/tenant/RLS/campos e negações | B: considerar apenas sessão ou UI suficientes |
| **R07-D05** | **A:** condicionar prontidão documental de C05..C07 a FIN/CON/Portal/INT, direitos e conciliação | B: aceitar callback, cache ou rótulo como fato final |
| **R07-D06** | **A:** exigir catálogo e parecer LGPD/SEG para auditoria/retenção C08 | B: usar log integral ou retenção universal |
| **R07-D07** | **A:** separar categorias de prontidão documental de homologação com provas; sem executar testes | B: declarar teste e segurança concluídos por redação |
| **R07-D08** | **A:** manter C01..C08, 67 CORE, SEG, AUT e demais gates abertos até fechamento formal independente | B: baixá-los pela sequência de ratificações preliminares |

**Pedido de deliberação:** ratificar, revisar ou rejeitar as alternativas A de R07-D01..D08 **somente como diretrizes documentais preliminares**. Não homologar contratos nem autorizar programação ou teste por consequência.

## 6. Limites e encaminhamento

- **67 IDs técnicos CORE**, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE global e demais gates **ABERTOS**; R02-C01..C08 **PENDENTES**.
- Não escrever **ALLOW/GRANT, SQL/RLS físico, migrations, código, endpoints ou testes funcionais**, não alterar cobranças, meios de pagamento ou contratos reais.
- Não modificar `main`, **PRs #2/#21**, branch `dev/fast-track-crm-2026-11-04`, CI, VPS e produção.
- **Próximo trabalho útil após a decisão:** cotejo efetivo de fontes originais de GAP-01 e de eventos FIN/CON/Portal/INT, confirmação de owners e criação de fichas de evidência individual, **não nova aprovação genérica de segurança**.

Documento de proposta apenas, não conclusão de planejamento nem liberação técnica.