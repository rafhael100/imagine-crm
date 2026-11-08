# CRM Imagine — FUNC-P0-01 / S01-PROT-01 — Proposta de protótipo navegável offline v0.1

**Data:** 08/11/2026. **Situação:** **PROPOSTA DOCUMENTAL, NÃO AUTORIZADA PARA CODIFICAÇÃO**.  
**Autorização que fundamenta esta redação:** R02-D05, aprovada **somente para preparar esta proposta de escopo** pela Direção.  
**Referências:** [R02-D01..D05 aprovadas documentalmente](CRM-FUNC-P0-01-S01-R02-DEC-ratificacao-documental-D01-D05-2026-11-08.md), [S01-R02](CRM-FUNC-P0-01-S01-R02-contratos-exibicao-estados-e-evidencias-proposta-2026-11-08.md), [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22), [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27).

## 1. Objetivo e escopo mínimo

Preparar um **protótipo de interface navegável, estático e autocontido**, capaz de demonstrar a experiência visual do primeiro recorte do CRM: **autoconsulta do nome da própria organização e da condição genérica de vínculo ativo**, sem qualquer autenticação, sessão, API, banco, rotas reais, permissão ou integração. Serve para revisão de UX e acessibilidade, não para homologação funcional.

**Conceito visual candidato:** interface limpa em português brasileiro, adaptável a desktop/mobile, com marca “Imagine CRM”, navegação lateral simples, cabeçalho com aviso permanente **“PROTÓTIPO — DADOS FICTÍCIOS”**, componentes com contraste e foco visível. Layout e identidade de marca deverão ser validados por Produto; esta descrição não obriga design final.

### Telas candidatas

| ID | Tela | Conteúdo exclusivamente fictício | O que não representa |
| --- | --- | --- | --- |
| **P-01 — Boas-vindas** | Marca, breve texto “Demonstração de navegação”, orientação para abrir “Minha organização” e aviso de dados de exemplo. | Dashboard comercial real, métricas, funções de contratação ou autenticação. |
| **P-02 — Minha organização** | `Organização: Empresa Exemplo`; `Seu vínculo: Ativo`, com aviso de que a exibição é **simulada**. | Consulta real, membership validada, tenant autenticado ou `ALLOW`. |
| **P-03 — Estados da tela** | Seleção demonstrativa (não de segurança) dos estados **UI-01** carregando, **UI-03** negado/indisponível e **UI-04** revalidação; UI-02 aparece apenas como **representação ilustrativa** do futuro resumo, sem prova de autorização. | Testes funcionais ou simulação de resultado de infraestrutura real. |
| **P-04 — Sobre o protótipo** | Descrição do propósito, exclusões, informação de inexistência de login, armazenamento ou conexão com clientes. | Documentação de conformidade, termos operacionais ou política de privacidade produtiva. |

**Navegação candidata:** P-01 → P-02 → P-03 → P-04, acessível por menu; retorno a P-01 em qualquer tela. Não há login, alternância entre empresas, convite, equipe, leads, contato, agenda, IA, exportação/importação, configurações de contas ou contratação de planos.

## 2. Estados e interações estritamente visuais

- **UI-01 / carregando:** mostra apenas texto/indicador neutro sem nome de empresa, sem spinner infinito; usuário pode escolher outro estado do *mock* manualmente.
- **UI-02 / exemplo ilustrativo:** mostra `Empresa Exemplo` e `Seu vínculo: Ativo` apenas como texto embutido fictício. Nunca declarar que passou em checagem IAM/CORE/SEG.
- **UI-03 / negado/indisponível:** esconde CAM-01/CAM-02 e mostra exclusivamente “Não foi possível apresentar estas informações.” Sem erros técnicos, identificação ou enumeração de organizações.
- **UI-04 / revalidação:** remove os dados previamente exibidos no protótipo e mostra aviso genérico “Informações precisam ser verificadas novamente.” O controle de troca de estado existe só no demonstrador.

**Dado sintético fixo mínimo:** `Empresa Exemplo`, `Seu vínculo: Ativo`. Não gerar CPFs, CNPJs, e-mails, telefones, nomes reais, chaves, tokens, IDs de conta ou qualquer perfil de terceiro. Não armazenar nem transmitir dados do navegador; não criar formulários que aceitem dados pessoais.

**Acessibilidade UX proposta:** HTML semântico, navegação por teclado, indicação clara de foco, contraste suficiente, labels dos controles de demonstração, adaptação a 320 px sem perda de conteúdo; eventual padrão WCAG a definir em revisão de Produto/QA, sem declarar certificação.

## 3. Arquitetura de demonstração — opções a decidir

**Opção A — recomendada para eventual autorização posterior:** pacote de arquivos estáticos isolados (HTML/CSS/JavaScript mínimo, sem bibliotecas remotas e sem dependências), executados localmente em navegador ou em runner sintético, com navegação/estados fictícios. Nenhuma conexão com Laravel, PostgreSQL, Redis, Cloudflare, Portal ou VPS.

**Opção B:** protótipo separado em ferramenta/framework de interface com dependências; maior custo de manutenção e superfície de integração. Exige nova avaliação de dependências, supply chain, licença e custo.

**Recomendação:** documentar preferência **A** para uma etapa futura; não iniciar implementação enquanto não houver autorização específica de código/branch/PR.

## 4. Critérios propostos para a futura entrega de protótipo — NÃO EXECUTADOS

| Critério | Evidência futura a exigir |
| --- | --- |
| **UX-01** | Navegação entre as quatro telas sem rotas ou chamadas externas. |
| **UX-02** | Rótulo permanente de “dados fictícios” em todas as telas e estados. |
| **UX-03** | O resumo mostra apenas CAM-01/CAM-02 fictícios; CAM-03..CAM-10 não aparecem. |
| **UX-04** | UI-03/UI-04 não mostram o nome “Empresa Exemplo” nem outros dados ao alternar a apresentação. |
| **UX-05** | Nenhum login, senha, sessão, cookie, API, `fetch`, conexão de banco, credencial ou entrada de dado real. |
| **UX-06** | Acesso via teclado e apresentação responsiva; revisão visual e checklist acessível. |
| **UX-07** | Alterações em branch filha isolada, diff delimitado, PR DRAFT **se e somente se** autorizado futuramente; zero alteração na DEV/`main`, VPS e produção. |
| **UX-08** | Verificações estáticas e de navegação específicas ao protótipo, sem afirmar homologação dos S01-T01..T22 ou segurança do runtime CRM. |

**Os 22 testes S01-T01..T22 continuam dependentes de política real por recurso/campo/tenant e de autorização separada; UX-01..08 não os substituem.**

## 5. Dependências, proibições e proteção de custos

- Nenhum `ALLOW`, novo autorizador, integração Laravel/DB, rotas CRM, login real, MFA real, endpoints, SQL, migrations, grants, rede, Cloudflare Workers, Leaseweb, SaaS contratado, terceiro, produção, merge, alterações do Portal ou dos demais projetos Imagine.
- Sem bibliotecas externas ou serviços de pagamento (opção A), nenhum runner auto-hospedado, segredos GitHub ou dados de clientes. Não presumir custo zero se futuras decisões mudarem o modelo.
- Branch `main` e `dev/fast-track-crm-2026-11-04` preservadas; PRs #2/#21/#23..#30 continuam sujeitos a gates e revisões próprios.
- `DenyByDefaultAuthorizer` permanece negando qualquer operação real. Gates **67 CORE, SEG-01/02, Issue #27, AUT, ARQ, INT, FIN, CON, LGPD e DEV-GATE** seguem abertos.
- Nenhum revisor SEG/DBA independente foi designado ou seu parecer produzido por esta proposta.

## 6. Decisões para avaliação futura — PROT-D01..D04, NÃO APROVADAS

| Decisão | Opção recomendada |
| --- | --- |
| **PROT-D01 — amplitude** | **A:** quatro telas fictícias P-01..P-04, sem módulos comerciais/administrativos, autenticador ou backend. |
| **PROT-D02 — tecnologia** | **A:** HTML/CSS/JS estáticos e autocontidos, sem dependências remotas nem rede. |
| **PROT-D03 — ensaio UX** | **A:** aprovar apenas critérios UX-01..UX-08 como checklist de futura entrega, sem converter testes UX em homologação S01. |
| **PROT-D04 — gate de execução** | **A:** manter **GO de programação específico e futuro** para branch/PR de protótipo; nenhum código, CI ou deploy agora. |

Após eventual ratificação documental, a Direção pode avaliar uma **ordem executiva separada** de prototipação estática, com escopo, branch base SHA, arquivos, testes, custos e limites exatos. **Ratificação de diretrizes não é ordem de programação.**
