# CRM F3-201 — Central de Portabilidade IN-118, demonstração offline

**Data:** 12/11/2026. **Estado:** PR visual **DRAFT**, sem integração automática; [issue #416](https://github.com/rafhael100/imagine-crm/issues/416) exige aceite visual humano, [#35](https://github.com/rafhael100/imagine-crm/issues/35) estabelece limites de operação. Esta página **não** é o produto liberado.

## Entrega de experiência
Nova superfície `prototypes/f3-201-portability-center/index.html`, 100% autônoma e sem API, mostrando as sete intenções da [IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md): exportação CSV/XLSX, importação CSV/XLSX, backup completo, restauração completa e migração entre contas. Quatro planos comerciais elegíveis aparecem como **seleções fictícias**; o protótipo não consulta assinatura nem direitos reais. Três cenários de destino simulam conta vazia, com registros ou desconhecida.

A prévia descreve riscos de duplicidades, formatos e diferenças entre planilha e backup completo, integridade, criptografia, segredos e permissões de empresa. Como IN-118 determina, exemplo com **100% de anexos** não bloqueia automaticamente o download dos dados existentes. Botão `Simular revisão — sem executar` altera apenas uma mensagem local; nunca concede autorização nem conclui operação.

Identidade: cor Imagine `#F9941D`, grafite `#171B19`, branco, superfície `#F7F7F8`; logomarca PNG original incorporada da versão Node com SHA Git `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77`. Os controles oferecem altura mínima 44px, foco visível e `aria-pressed`, com painel responsivo. O texto informativo em laranja usa `#9B4D00`; **nunca** texto branco comum sobre o laranja claro da marca.

## Evidência técnica
- PHPUnit: nomes e quantidades dos controles, PNG byte a byte, contrato de CSP sem network/file/storage/PII e ausência de operação real.
- BrowserSmokeRunner + Chromium real: largura de 1100, 550 (referência de reflow ao equivalente de 200% de layout, **não prova de zoom nativo**), 320 px; planos e sete operações clicáveis, alertas de restauração e destino, teclado, alvo 44px, logo decodificado e falta de overflow.
- Workflow dedicado em `.github/workflows/crm-f3-201-portability-visual-evidence.yml` grava oito capturas (4 viewports × dois estados fictícios) com hashes SHA256. Como a tela é **nova**, não existe imagem "antes" válida: não foi inventado baseline.
- Regressões gerais F0, PG16/RLS, Sites e AIA devem passar na HEAD exata.

## Bloqueios e relação com o Core
A lógica é uma **representação visual de regras**, sem chamar diretamente `SyntheticPortabilityOperationPreflight`, `SyntheticBackupManifestContractInspector` ou `SyntheticRestoreReadinessInspector` do PHP. Não implica paridade operacional entre JavaScript e backend. Nenhum arquivo, payload, e-mail, telefone, token, upload, download, banco, fila, sessão, conta, servidor ou solicitação de rede é processado.

Exige revisão visual humana (teclado, contrastes efetivos, foco, zoom nativo 200%, estados, 320px) antes de qualquer merge. A versão operacional da Central IN-118 requer direito real por empresa, arquivo versionado/criptografado, transação, undo, auditoria e homologações independentes [#27](https://github.com/rafhael100/imagine-crm/issues/27) SEG/DBA e [#259](https://github.com/rafhael100/imagine-crm/issues/259) CORE/SEG/DBA/LGPD. `main`, VPS e produção permanecem intocadas.
