# CRM F3-212 — mapeamento explícito de CSV para campos candidatos do CRM

**Data:** 13/11/2026. **Origem:** IN-118, CRM-REV-03. **Natureza:** transformador PHP puro para dados **já em memória e não confiáveis**. Não é API, importação real, migração, consentimento ou persistência.

## Implementação funcional

`PortableCsvFieldMapping::map($table, $bindings, $hasHeader=false)` recebe uma tabela retangular, já interpretada pelo codec F3-210, e um conjunto **explícito** de vínculos `{source_position: int, target_field: string}`. Permite sete rótulos candidatos: `name`, `email`, `phone`, `origin`, `consent`, `notes`, `status`. Esses rótulos **não são schema definitivo** e não autorizam acesso. O método reaproveita `PortableCsvColumnProjection` F3-211 para aplicar índices na ordem selecionada, sem correspondência aproximada por nome nem inferência de campos.

O retorno `untrusted_mapped_rows` é uma sequência de dicionários `código_de_campo -> valor_de_string`, preservando a ordem de linhas e conteúdo **sem normalização**, sem deduplicação, mesclagem ou mudança de status. Quando `hasHeader=true`, o primeiro registro não vira lead: títulos `untrusted_header_titles` retornam separados. Campos omitidos **não são copiados** ao resultado.

Se houver coluna `consent`, emite `CONSENT_COLUMN_DOES_NOT_VERIFY_LEGAL_CONSENT`. O valor fictício `true` não comprova base legal, controlador, finalidade ou transferência válida. Todas `*_verified=false`, `authorized=false`, `can_import/can_merge/can_confirm_consent/can_write_file/can_persist=false`, `informational_only=true`.

## Contrato de segurança

Rejeita chaves extras, posições inválidas/duplicadas, destinos não enumerados/duplicados, tipos ambíguos e tabelas irregulares; mensagens constantes de erro. A classe delega os limites do projetor F3-211: máximo de 100 registros, 32 colunas de origem, 3.200 células, 4.096 bytes UTF-8 por célula. No máximo sete destinos candidatos na mesma transformação.

Não lê nem escreve arquivos, banco, rede, storage, fila, log ou API. O retorno pode conter dados sensíveis se um chamador inadequado o utilizar com dados reais; **não conectar a controllers ou serviços de cliente sem política IAM/LGPD, sandbox de upload e auditoria aprovadas**.

## Testes

`PortableCsvFieldMappingTest.php` valida um fluxo de exemplo `codec.decode -> mapping`, campos omitidos, ordenação, cabeçalho separado, permissão de nome sem obrigatoriedade de outro contato, duplicatas preservadas, valor de consentimento não autenticado, todos os sete candidatos, entrada malformada, células com fórmula não convertidas em exportação segura e inexistência de rota pública.

**Critério de integração DEV:** quatro workflows obrigatórios F0, PostgreSQL/RLS, Sites e Agentes com status success na HEAD exata, revisão de diff sem side effects. Gates #27 SEG/DBA e #259 CORE/LGPD mantêm NO-GO; #416 UX e PRs visuais/arquiteturais seguem em revisão. `main`/VPS/produção intocadas.
