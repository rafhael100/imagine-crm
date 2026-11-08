# CRM Imagine — F3-02 — Triagem conservadora de risco de fórmula em células de planilha

**Data:** 08/11/2026. **Situação:** componente PHP puro, proposto exclusivamente para DEV/CI com dados sintéticos. Vinculado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) e IN-118/INT-08.

## O que foi implementado

`SpreadsheetTextRiskGuard::isPotentiallyExecutable(string)` classifica de maneira conservadora valores de **texto não confiável** que podem ser interpretados como fórmulas de planilha quando iniciados por `=`, `+`, `-`, `@`, inclusive após espaços/Unicode invisível/BOM. Entradas com UTF-8 inválido ou primeiro caractere de controle também são consideradas suspeitas. `assertPlainText` rejeita tais valores com erro genérico, sem ecoar conteúdo.

Há casos negativos intencionais: `-10` como **texto** é bloqueado; o tratamento correto de campos numéricos tipados exige política própria. O componente **não higieniza** o texto: somente sinaliza/rejeita.

## O que NÃO está implementado

- Nenhuma exportação CSV/XLSX, parser de importação, download, armazenamento, API, endpoint, payload com dados reais, banco, permissões ou cliente de e-mail.
- O guard **não torna um CSV seguro sozinho**: delimitação, aspas, quebras de linha, tipo de célula, importação por aplicativos de planilha, round-trip, encoding, escapamento específico do formato, injeção de fórmulas após transformações e efeitos de ferramentas externas exigem avaliação própria.
- Não confundir exportação de planilha com backup/restore completo (IN-118). Autorização por tenant, ação, campo, plano e direito de exportação permanece obrigatória no backend.
- Esta implementação **não é homologação de segurança para Excel/LibreOffice**, não concede `ALLOW` e não autoriza processamento de dados de clientes.

## Testes sintéticos

`tests/Unit/SpreadsheetTextRiskGuardTest.php`: prefixos comuns e ocultos, UTF-8 inválido, caracteres de controle, casos considerados texto neutro, ausência de mutação e falso positivo conservador. A execução PHPUnit e o SHA final são registrados no PR/CI, não presumidos neste documento.

## Gates preservados

`DenyByDefaultAuthorizer` deny-only. 67 CORE, SEG/DBA Issue #27, AUT/ARQ/INT/FIN/CON/LGPD, e critérios de portabilidade e exportação permanecem abertos. Sem `main`, VPS, produção ou custo externo.
