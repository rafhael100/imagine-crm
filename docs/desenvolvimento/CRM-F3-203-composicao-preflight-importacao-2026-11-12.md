# CRM F3-203 — revisão composta de importação CSV/XLSX

**Data:** 12/11/2026. **Origem:** IN-118, issue #35. **Base DEV:** F3-198 (intenção de portabilidade) + F3-202 (metadados de mapeamento posicional). **Natureza:** componente PHP inerte sem acesso a arquivos, dados, rede, sessões ou banco.

## Entrega
`SyntheticImportReadinessInspector` valida ambos os contratos completos **antes** de produzir qualquer decisão. Somente intenções `import_csv`/`import_xlsx` são aceitas; backup, restauração e exportação não são promovidos por engano a uma importação.

A saída informa códigos fixos de risco para:
- pedido cujo pacote declarado não é tabular;
- entidade de destino do mapeamento não selecionada na operação;
- divergência de estado da conta destino (ou estado desconhecido, ainda que ambos concordem);
- contagens de possíveis duplicidades ou suspeitas de fórmula que divergem entre as duas prévias;
- demais avisos de ambas as camadas, prefixados por origem (`OPERATION_` / `MAPPING_`), inclusive ausência de evidência legal de consentimento.

Não pode considerar mapeamento de coluna `consent` como consentimento demonstrado de pessoa real. Não lê títulos de coluna, nomes, contato, linhas, arquivos ou hash. Apenas índices e contagens declaradas.

## Proibições explícitas
O resultado sempre contém `authorized=false`, `informational_only=true`, `can_read_file=false`, `can_import=false`, `can_merge=false`, `can_update_lead=false`, `can_confirm_consent=false`, `can_erase=false`, `can_write_file=false`, `can_persist=false`, com todas as flags `*_verified=false`. Relaciona seis requisitos de aprovação/segurança **ainda não cumpridos**. Nenhuma rota, IAM, worker, banco, transação real, staging, upload, download ou deploy foi acrescentado.

## Testes
PHPUnit cobre contratos alinhados mas sem poderes, divergências de entidade/estado/contagens, arquivo XLSX não verificado, operações erradas, entrada com PII extra, não vazamento de dados e determinismo. A matriz `SyntheticCommercialCapabilityDenyMatrixTest` aumenta de **45→46** módulos sempre deny-only. Exigir sucesso dos quatro pipelines F0, PostgreSQL/RLS, Sites e Agentes na HEAD exata antes de integrar na DEV.

A Central operacional ainda depende de especificação de parser, privilégios por empresa, plano de reversão, LGPD, revisão SEG/DBA independente e autorização expressa; gates #27 e #259 permanecem NO-GO. `main`/VPS/produção não foram modificados.
