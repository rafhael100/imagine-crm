# CRM F3-140 — Prévia CSV protegida de agregados comerciais fictícios

**Data:** 12/11/2026 · **Escopo:** PHP Core e PHPUnit em DEV; nenhum arquivo exportado, upload, navegador, endpoint ou cliente real · **Referências:** CRM-REV-06 VEN-R25/R26/R27 e VEN-P12.

## Implementação reutilizável

\`SyntheticStageAggregateCsvPreview\` recebe até 20 linhas de laboratório com **somente etapa de uma lista fixa** e valor declarado em centavos BRL. Em vez de produzir relatórios a partir dos registros originais, a classe delega a validação completa e a regra de supressão de grupos pequenos ao componente já integrado \`SyntheticSalesAggregatePrivacyPreview\`.

Depois de receber o relatório validado, produz somente uma pequena **string de visualização** em UTF-8 com cabeçalho fixo \`stage,count,claimed_sum_cents\` e linhas CRLF para etapas **com contagem positiva e maior ou igual ao limite ilustrativo k**. Grupos ocultos e grupos de contagem zero não geram linhas. Não inclui subtotal global, total de linhas de entrada, ID de cliente/empresa, nome, dado pessoal, valores ocultos, nome de arquivo, link ou destinatário.

O texto é construído exclusivamente de códigos de etapa de enumeração e números inteiros previamente validados. Não concatena campos textuais de clientes, portanto não pode incluir fórmulas de planilha nem delimitadores hostis enviados pelo usuário. O teto fixo de visualização é 256 bytes; não há codificação/decodificação genérica de qualquer CSV.

## Segurança e limites

A supressão de grupo com menos de k registros é **apenas uma proteção descritiva de laboratório**, não prova de anonimização, diferencial de privacidade, k-anonimato real, ausência de ataques por comparação de consultas ou autorização de relatórios multiempresa. A linha \`won\` não reconhece receita.

\`recognized_revenue_cents=0\` e todos os sinais de autorização (\`privacy_policy_verified\`, \`tenant_scope_verified\`, \`recipient_permissions_verified\`, \`authorized\`, \`can_read_customers\`, \`can_export\`, \`can_write_file\`, \`can_download\`, \`can_email\`, \`can_persist\`) são **sempre falsos**.

## Testes

\`SyntheticStageAggregateCsvPreviewTest\` verifica cinco etapas, supressão inclusive de grupos com altos valores alegados, arquivo textual somente com cabeçalho quando nada pode ser revelado, paridade com agregador PHP original, números até 2^53−1 em base dez sem arredondamento, tipos inválidos, tentativas de fórmula como etapa, PII em grupo oculto e ausência de efeitos de exportação.

Para uma exportação **real** ainda faltam autorização por usuário/empresa/coluna, RLS, políticas de filtro e retenção, assinaturas/download com validade, auditoria, prevenção de differencing e revisão independente CORE/SEG/DBA/LGPD/VEN-P12. Não há API, SQL, IAM/ALLOW, arquivos, dados reais, deploy, despesas, VPS, main ou produção.
