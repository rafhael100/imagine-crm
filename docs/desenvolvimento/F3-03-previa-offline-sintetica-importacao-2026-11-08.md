# CRM Imagine — F3-03 — Prévia limitada de importação sintética

**Data:** 08/11/2026. **Fase:** código PHP de laboratório e testes em DEV, **sem upload/importação efetiva**. Vinculado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), à F3-02 e à [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md).

## Objetivo

Verificar **apenas a estrutura** de uma matriz de dados sintéticos previamente montada por um teste, sem arquivos, integrações ou dados de clientes. O componente valida fronteiras, identifica posições potencialmente perigosas para uso futuro em planilhas e devolve somente **contagens e coordenadas 1-based**, sem retornar células, rótulos, nomes, contatos ou payloads.

### Limites intencionais deste laboratório
- Máximo de **8 colunas** e **50 linhas**, cada célula exclusivamente `string` com máximo de **512 bytes**.
- Rótulos técnicos fictícios de coluna são identificadores ASCII simples e únicos. **Não representam o esquema final** de leads, contatos, atributos dinâmicos, importação CSV ou XLSX.
- `SpreadsheetTextRiskGuard` sinaliza texto potencialmente interpretável como fórmula, inclusive `-10` textual por cautela. Não corrige, sanitiza ou exporta conteúdo.
- Reporta células vazias; `canImport()` retorna **sempre falso**, sem usar pontuação ou aprovação automática.
- Entradas inválidas geram mensagem genérica sem ecoar células/headers; nenhuma mesclagem por conteúdo é feita.

## O que permanece NÃO IMPLEMENTADO

- Leitura de CSV/XLSX, seleção de arquivo real, upload, parser, transformação, prévia visual de dados pessoais, importação, banco, filas, idempotência persistida, armazenamento, links, exportação, backup, restauração, API ou interface com clientes.
- Verificação de identidade, MFA, tenant, escopo por usuário/campo/recurso, permissões de importação, limites de planos e políticas LGPD; `DenyByDefaultAuthorizer` segue deny-only.
- Proteção completa contra CSV injection; o guard existente é apenas verificação defensiva parcial de texto.
- Homologação do fluxo de importação/exportação pelo proprietário do módulo IN-118/INT-08/CRM e por Segurança/Privacidade.

## Evidências propostas

`tests/Unit/SyntheticImportPreviewInspectorTest.php` cobre resumo sem vazamento de conteúdos, linhas idênticas preservadas sem merge, limites, rejeição de estruturas inválidas, texto potencialmente perigoso e retorno obrigatório `canImport()=false`. Apenas logs de execução do GitHub na SHA exata podem comprovar esses testes.

**Gates:** nenhum SQL, grant, ALLOW, migration, alteração de main, contratação, implantação VPS ou produção. CORE/SEG/DBA/ARQ/INT/FIN/CON/LGPD e Issue #27 permanecem abertos.
