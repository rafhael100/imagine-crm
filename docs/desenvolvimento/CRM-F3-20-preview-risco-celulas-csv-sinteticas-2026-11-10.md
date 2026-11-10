# F3-20 — Prévia de risco de células CSV sintéticas (sem arquivos)

**Escopo:** incremento PHP puro, somente em memória, no laboratório do CRM Imagine. Relacionado à [issue F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), ao planejamento [CRM-REV-03](../planejamento/CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) e aos gates [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27).

## O que faz

A classe `App\Support\Synthetic\CsvCellRiskPreview::inspect` recebe uma matriz **já separada de strings inventadas**, com até 100 linhas, até 25 células por linha e até 4096 bytes por célula. Esses tamanhos são **somente limites técnicos das fixtures**, não quotas comerciais do CRM ou do plano Básico.

Ela percorre o conteúdo **sem alterar nem copiar valores para a saída**. O resultado contém `rows`, `max_columns`, `flagged_count` e um vetor `findings` com apenas coordenadas 1-based (`row`, `column`) e códigos de risco:

| Código | Evidência detectada no texto bruto |
|---|---|
| `formula_prefix` | Um dos caracteres ASCII `=`, `+`, `-` ou `@` no início, após BOM UTF-8 opcional e espaço/tab/CR/LF/FF/VT ASCII |
| `apostrophe_formula_ambiguous` | Apóstrofo inicial seguido desses prefixos, opcionalmente com espaços/caracteres de controle entre eles — **não presumir que apóstrofo neutraliza a fórmula** |
| `nul_byte` | Byte NUL em qualquer posição da célula |
| `control_byte` | Outros bytes de controle ASCII e DEL; podem indicar quebras, delimitadores ou ambiguidade de transporte |
| `bom_prefix` | BOM UTF-8 no começo, mesmo sem fórmula, para encaminhar à decisão de encoding |

Prioridade: NUL antes de prefixo de fórmula, prefixo antes de apóstrofo, controles antes de BOM isolado. Um código por célula, determinístico; valores brutos não aparecem no relatório nem nas mensagens de falha.

Exemplo em fixture:

```php
$summary = CsvCellRiskPreview::inspect([
    ['Nome fictício', '=HYPERLINK("https://invalid.example")'],
    ['Registro seguro', 'Texto simples'],
]);
// findings: [['row' => 1, 'column' => 2, 'code' => 'formula_prefix']]
```

## Fronteiras e decisões pendentes

**Não abre, interpreta, detecta encoding, transforma, exporta nem importa um arquivo CSV/XLSX.** Não lê contatos, leads, empresas, dados pessoais ou histórico. Não executa consultas, alterações, `ALLOW`, rotas, tarefas assíncronas ou upload. A detecção é conservadora e gera possíveis falsos positivos (`-12` também é sinalizado). Não pretende cobrir todas as regras de Excel/LibreOffice, CSVs com múltiplas linhas, separadores, fórmulas Unicode, caracteres bidi ou codecs. Não certifica que células não sinalizadas sejam seguras.

**Nenhum escape/sanitização automático** foi adotado, pois o planejamento exige decisão e revisão sobre encoding, serialização, proteção de fórmulas, permissões por campo/empresa, escopo de importação/exportação e consentimentos/privacidade. A Central IN-118 permanece a responsável normativa por portabilidade. Idempotência, deduplicação e backup são contratos separados. A importação real e a geração de download devem permanecer desabilitadas até aprovação técnica específica.

`tests/Unit/CsvCellRiskPreviewTest.php` valida os códigos, coordenadas, exclusão do conteúdo bruto no relatório, casos benignos, fronteiras exatas, estruturas inválidas, falsos positivos conservadores e prefixos ambíguos. O tripwire estático dos entrypoints Laravel foi ampliado para bloquear a ligação direta desta classe a rotas/providers/jobs/bootstrap antes do gate. É apenas um bloqueio de fonte, não uma auditoria completa.

Não altera `main`, VPS, produção, serviços pagos nem o [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) DRAFT de segurança. Merge restrito à DEV após os workflows F0 e PostgreSQL RLS aprovados na HEAD exata. Todos os gates CORE, IAM, S01, LGPD e SEG/DBA permanecem abertos.
