# S01-PROT-01-F1 — Protótipo navegável offline do Imagine CRM

**Natureza:** demonstração estática de UX com conteúdo 100% fictício, sem ambiente de clientes, integração ou autorização funcional. A expansão F2 acrescenta três telas de negócio puramente ilustrativas; não representa ativação de módulos.

## Como executar

1. Baixe a pasta `prototypes/s01-prot-01/` ou abra o arquivo `index.html` diretamente no navegador (protocolo `file://`).
2. Use o menu **Boas-vindas**, **Minha organização**, **Visão geral**, **Leads fictícios**, **Funil ilustrativo**, **Estados da tela**, **Sobre o protótipo**.
3. Em **Estados da tela**, experimente as opções UI-01/02/03/04, que trocam **somente texto simulado**.

**Não é necessário instalar pacotes ou iniciar servidor**. A demonstração foi construída em um único HTML, com CSS e JS embutidos; há uma política CSP local de bloqueio de conexões, sem recursos remotos.

## Conteúdo fictício e exclusões

- CAM-01: `Empresa Exemplo`.
- CAM-02: `Seu vínculo: Ativo`.
- Todos os dados são fictícios, não relacionados a clientes ou organizações reais.
- Nenhum login/senha/formulário, cookies, `localStorage`, `sessionStorage`, `fetch`, XHR, WebSocket, API, backend Laravel, banco, Redis, autorização ou `ALLOW`.
- Nenhum dado real é coletado ou transmitido. **A aparição do resumo é ilustrativa**, não uma consulta de verdade.
- Troca de estado UI-03/UI-04 remove o conteúdo do painel de estado; dados estáticos da tela **Minha organização** ficam ocultos quando essa tela não está selecionada.


## Extensão F2: visão comercial exclusivamente sintética

- **Visão geral**: quatro leads *inventados*, três etapas visuais e zero dados de clientes. Os indicadores são constantes, não cálculos sobre empresas.
- **Leads fictícios**: tabela ilustrativa com Lead Exemplo A/B/C/D; scores 30/55/85/70 e faixas frio/morno/quente são constantes embutidas na UI.
- **Funil ilustrativo**: colunas Entrada / Em contato / Qualificação sem arrastar, salvar, contatar ou gerar oportunidades.
- Nenhum lead pode ser cadastrado; não há CNPJ, telefone, e-mail, nome de pessoa real, dados persistidos nem permissões reais.
- A classificação do F2 é **visual e independente** do componente PHP de Lead Scoring preparado em outro PR. Não interpretar pontuação fictícia como pontuação apurada ou processada pelo CRM.
- `tests/Unit/OfflinePrototypeContractTest.php` adiciona quatro verificações regressivas estáticas para número de telas, aviso de demonstração, fronteira offline e estados de UI. **Não substituem** inspeção em navegador real, revisão de acessibilidade nem testes de autorização.
- Esta extensão é **PR filha** de #31, mantendo o diff isolado; não deve ser integrada diretamente à DEV contornando a revisão e o eventual aceite da base.

## Critérios de revisão UX-01..UX-08

| Critério | Verificação proposta | Observação |
| --- | --- | --- |
| UX-01 | Sete seções alternadas com botões de navegação, sem rotas reais. | Revisão visual manual necessária. |
| UX-02 | Aviso permanente `PROTÓTIPO — DADOS FICTÍCIOS`. | Presente no cabeçalho. |
| UX-03 | Apenas nome e vínculo próprios **fictícios** em `Minha organização`. | Não expor planos/usuários/leads. |
| UX-04 | UI-03/UI-04 removem o conteúdo do painel de demonstração antes de apresentar a mensagem neutra. | Validar por navegação. |
| UX-05 | Inspecionar código para ausência de conexões, credenciais e coleta de dados. | Não atribuir à CSP a segurança de backend. |
| UX-06 | Navegação por teclado, botões com foco visível, estados com `aria-pressed`, painel `aria-live`, layout responsivo. | Exige revisão humana e teste visual 320px+. |
| UX-07 | Apenas `prototypes/s01-prot-01` na branch isolada; PR DRAFT para a DEV, sem merge. | Não altera rotas Laravel/DEV/`main`. |
| UX-08 | Evidência UX separada dos 22 cenários S01-T01..T22. | Nenhum desses testes é considerado executado. |

## Riscos e bloqueios

A demonstração **não** implementa nem testa IAM, tenant, membership, RLS, permissões, privacidade operacional, autorização por campo ou sessão de clientes. `DenyByDefaultAuthorizer` no CRM permanece deny-only. Os 67 itens CORE, SEG/DBA, AUT/INT/FIN/CON/LGPD e demais gates permanecem abertos. Não há alteração de `main`, na branch DEV existente, na VPS ou em produção.

## Rastreabilidade

- [Issue #22 — FUNC-P0-01](https://github.com/rafhael100/imagine-crm/issues/22)
- [Escopo S01-PROT-01](https://github.com/rafhael100/imagine-crm/blob/docs/func-p0-01-s01-r01-r02-2026-11-06/docs/desenvolvimento/CRM-FUNC-P0-01-S01-PROT-01-escopo-prototipo-navegavel-offline-proposta-v01-2026-11-08.md)
- Base da branch de implementação: `dev/fast-track-crm-2026-11-04`, HEAD prévia `8c9683acfd8decf98544402acda360d7e0f0a489`.
- Execução limitada após o “ok” da Direção, exclusivamente para prototipação fictícia em branch filha e PR em rascunho. **Nenhum outro pacote foi liberado por analogia**.
