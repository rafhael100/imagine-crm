# FUNC-P0-01/S01-R10 — Matriz de fechamento por evidência C01–C08 (proposta v0.1)

Data: 06/11/2026. Estado: **PLANEJAMENTO DOCUMENTAL, NÃO HOMOLOGADO**. Onda A/B/C são prioridades desta análise, não alteração oficial de gates CORE.

## 1. Base comprovada de governança

- [R09 — G01, G02 e G03](CRM-DEC-FUNC-P0-01-S01-R09-G03-designacao-portal-fincon-integracoes-2026-11-06.md) e [R09 — G04, G05 e G06](CRM-DEC-FUNC-P0-01-S01-R09-G04-G06-acumulo-governanca-2026-11-06.md): **um mesmo declarante assumiu as seis funções internas**.
- Isso resolve a **atribuição de coordenação interna**, mas não a eventual **independência de revisão, competência especializada, aprovação de contratos, parecer jurídico ou prova técnica**. Atualização conceitual: GAP-09A (coordenação) documentado; GAP-09B (pareceres e competências necessárias) PENDENTE. Não fechar GAP-09 tecnicamente.
- Fontes: [R01 literal](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) blob 36a353caf695aa03aea3b0d6e0e13331a1fda5b3; [fichas C01–C08](CRM-FUNC-P0-01-S01-R07-PAINEL-fichas-evidencias-pendencias-v0.1-2026-11-06.md); [EVID03 eventos](CRM-FUNC-P0-01-S01-R07-EVID03-matriz-eventos-comerciais-fonte-autoritativa-2026-11-06.md); [EVID04 responsáveis](CRM-FUNC-P0-01-S01-R07-EVID04-matriz-responsaveis-owner-aprovadores-2026-11-06.md).

## 2. Oito contratos, lacunas verificadas e resultado documental esperado

| Contrato | Tema | Ordem | Fontes | Lacuna impeditiva | Evidência ou documento ainda faltante | Responsável e revisão | Gates afetados |
|---|---|---|---|---|---|---|---|
| C01 | Sessão humana e MFA | A / prioritário | R01; CORE04; REV01 | Regras por perfil, step-up, revogação e falha de dependência | Matriz de sessões/negativas, ameaça de sequestro e prova futura de expiração | G02 prepara, G04 analisa internamente; revisor competente independente pendente quando exigido | CORE04-D04..D10; SEG01/02 |
| C02 | Principal, tenant e membership | A / prioritário | R01; CORE01; REV01 | Contexto backend e vínculo próprio ativo; estados inválidos; troca entre tenants | Matriz A/B, vínculo ativo/revogado, fonte oficial e cenário de revogação | G02/G04 internos, sem parecer independente | CORE01-D04..D07; CORE07; SEG |
| C03 | Autorização Laravel e RLS PostgreSQL | A / prioritário | R01; CORE01/02/03 | Deny-by-default, non-bypass RLS, reuso de conexão, integridade cross-tenant e cache | Inventário lógico de recursos/roles/políticas, threat model; prova PostgreSQL/SQL somente em futura fase autorizada | G02/G04 mesma pessoa: revisão especializada independente não comprovada | CORE01/02/03; SEG; CORE10 |
| C04 | Dois campos e proteção de saída | A / prioritário | R01 CAM01..10; CORE02; REV01 | Fonte do display_name e próprio vínculo, sanitação/XSS, não enumeração, minimização | Catálogo de campos e classificação, projeção conceitual 2 campos e negativas sem vazamentos | G01/G02 produzem; G04/G05 coordenam; LGPD especializada pendente | CORE02-D04/D06; LGPD |
| C05 | Fatos comerciais e fontes oficiais | B / após A | REV02; CORE08; IN119..122; COM08/09; EVID03 | Fonte de verdade/versão, fuso e marcos 7/30/60/120; IN-092 não recuperada | Ato oficial de contrato, pagamento e cancelamento; conciliação por produto e parecer jurídico | G03 coordena Portal e CRM; validação comercial e parecer independente quando aplicável | FIN/CON; REV02; Portal |
| C06 | Entitlements vs membership | B / após A | CORE02; CORE08; R01 EST | Estados EST05..12, plano/ação/campo, direitos restritos e portabilidade separada | Matriz recurso x ação x estado x vínculo, com decisão de cada proprietário competente | G01/02/03 internos; SEG/LGPD revisam conforme operação | CORE02/08; FIN/CON; LGPD |
| C07 | Idempotência, conflito e conciliação | B / após A | CORE03; INT-GATE; EVID03 | Eventos fora de ordem, duplicados e tardios; eficácia e correlação | Tabela de casos temporais, autoridade oficial, retry/compensação, prova futura por contrato | G02/G03 coordenam; revisão SEG/INT e QA pendente | CORE03; INT G01..G06; FIN/CON |
| C08 | Auditoria e proteção de dados | C / paralelo a A | CORE06; IN162; REV01/02 | Finalidade, minimização, acesso, retenção por categoria e descarte | Catálogo evidência x prazo x base/finalidade x acesso x revisão e expurgo | G05/G06 coordenam internamente; parecer especializado e revisão independente pendentes quando exigidos | CORE06-D04..D10; SEG; LGPD |

## 3. Dependências e sequência sem atalhos

- **Onda A: C01–C04.** Primeiro, sessão humana autenticada, tenant/membership verificados, autorização Laravel+RLS e apenas CAM-01 (nome da própria organização) e CAM-02 (seu próprio vínculo ativo) visíveis. CAM-03..CAM-10 não visíveis. **Não há ALLOW real**; falha ou estado inconclusivo implica negação do recorte S01, sem impedir fluxos legais separados.
- **Onda B: C05–C07.** Separar fatos comerciais do CRM e Portal, contratos/planos, estados técnicos e direitos por operação. C05 define autoridade do fato, C06 direito aplicável por ação/campo, C07 versões/ordem/retry de eventos. HTTP 2xx não é comprovação de pagamento.
- **Onda C: C08.** Classificação de auditoria e logs começa em paralelo, mas só se conclui com campos/ações/estado definidos. Não reutilizar prazos de um tipo de log para dados CRM, contrato ou prova fiscal.
- **Risco de autovalidação:** G02/G04/G05/G06 acumulados não constituem parecer independente de segurança, privacidade, jurídico ou QA. Registrar parecer especializado faltante quando obrigatório.

## 4. Definição de pronto DOCUMENTAL (nunca equivale a homologação)

Para apresentar cada ficha Cxx para revisão, exigir: (1) fontes e SHA/precedência; (2) campos/ações/estados/negações expressos; (3) owner interno identificável e lacunas de parecer expostas; (4) ameaça e critérios positivos e negativos rastreáveis às matrizes S01-T01..T22 ou CORE01-T01..T10; (5) não declarar teste executado nem autorização técnica sem evidência. ‘Pronto para revisão’ não é GO de produção.

## 5. Divergências sem decisão técnica final

- R01 EST-05..EST-12 (organização e estados comerciais): direito de S01 a decidir por proprietário funcional com CORE08/FIN/CON/Privacidade. Não conceder acesso por plano, cobrança ou vínculo isoladamente.
- CORE01-D04..D07, CORE02-D04..D10, CORE04-D04..D10, CORE06-D04..D10 e CORE08-D04..D10: manter suas pendências; decisões preliminares não as homologam.
- IN-092 histórica não localizada como arquivo independente nas árvores consultadas; não substituir por reconstrução inventada. Ver [EVID02](CRM-FUNC-P0-01-S01-R07-EVID02-rastreamento-IN092-2026-11-06.md).
- Testes de PostgreSQL/RLS, segurança, concorrência, rotas, MFA e portabilidade devem ser planejados agora, **não executados**.

## 6. Próximo recorte documental

[R10-A01 — matriz de negativas para C01–C04](CRM-FUNC-P0-01-S01-R10-A01-matriz-negativas-C01-C04-proposta-v0.1-2026-11-06.md) detalha estados e riscos, sem endpoints, ALLOW, schema, SQL ou testes funcionais.

## 7. Preservações

**C01..C08 PENDENTES; 67 IDs CORE e SEG-01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, DEV-GATE e demais gates ABERTOS.** R08-Gxx (funções) não são INT-Gxx (gates).
Somente documentação na branch isolada. Não tocar main, PRs #2/#21, branch DEV, CI, código, SQL/RLS físico, ALLOW/GRANT, testes, VPS, produção, pagamentos ou dados reais.