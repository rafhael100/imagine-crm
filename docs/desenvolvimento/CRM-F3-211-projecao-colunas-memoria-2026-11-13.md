# CRM F3-211 — projeção segura de colunas por posição para tabelas em memória

**Data:** 13/11/2026. **Escopo:** F3-02 / IN-118. **Estágio:** biblioteca reutilizável de transformação de dados isolada, **não** implementação de importação, download, consentimento, mesclagem ou banco real.

## Entrega

`PortableCsvColumnProjection::project($table, $positions, $hasHeader=false)` recebe tabela retangular **já em memória**, índices de origem **numéricos explícitos e únicos** (0..N-1), na ordem de seleção, e indicador booleano de cabeçalho **apenas alegado**. Não aplica correspondência textual aproximada, não inventa cabeçalho, não escolhe o destino automaticamente, não altera células e não remove linhas duplicadas.

Retorna a contagem de colunas de origem/selecionadas, total de registros, número de linhas de dados, flag `header_claimed`, `untrusted_header` separado quando alegado, e `untrusted_data_rows` na ordem solicitada. Valores são **conteúdos não confiáveis** da memória do chamador; jamais considerá-los autorizados ou consentidos.

O consumidor pode compor `PortableCsvTableCodec::decode()` → `PortableCsvColumnProjection::project()` → `PortableCsvTableCodec::encode()` em um teste isolado, **sem arquivo**. O codec de saída continua recusando células com prefixos de fórmulas suspeitas. A separação do cabeçalho evita que ele seja contado silenciosamente como registro de uma pessoa.

## Segurança e limites

Valida contiguidade de listas, tipo string, UTF-8 válido, ausência de byte NUL, largura igual por linha, posições dentro da largura e sem duplicatas. Limites conservadores alinhados ao codec F3-210: 100 linhas, 32 colunas, 3.200 células, 4.096 bytes por célula, soma de conteúdo até 256 KiB. Não há tratamento de ACL de campo, role, tenant ou consentimento: estes requisitos **continuam faltantes**, portanto esta função não pode ser conectada a controller, fila ou importação sem revisão e aprovação.

## Testes e integração

PHPUnit verifica pipeline de ida/volta entre o codec e a projeção, preservação de Unicode, exclusão de colunas não selecionadas do resultado, cabeçalho presente/ausente, ordem explícita, duplicidade de linhas preservada, fórmula não neutralizada, entrada malformada, índices duplicados e limites. Regressão específica veda que essa classe ou o codec sejam registrados em `routes/web.php`.

Nenhuma rota, DB, grant, migrations, API, file IO, segredos ou dependência nova. Exigir **4 workflows obrigatórios na HEAD exata** e diff delimitado antes de merge DEV. A política de quais colunas são legalmente elegíveis para exportação/importação depende de autenticação, escopo por tenant/campo, LGPD e aprovação SEG/DBA/CORE das issues #27/#259; UX #416 e arquitetura #434 permanecem em revisão.
