# CRM F3-141 — Preparação de relatório protegido e revalidação de destinatário

**Data:** 12/11/2026 · **Ambiente:** DEV, PHP Core e PHPUnit somente, sem arquivo, API ou destinatário real. **Rastreabilidade:** CRM-REV-06 VEN-R25/R26/R27, VEN-P12 e gates CORE/SEG/DBA/LGPD.

## Integração de três contratos existentes

\`SyntheticReportDeliveryCompositeInspector\` une, de modo determinístico e apenas em memória:

1. A supressão de grupos pequenos implementada por \`SyntheticSalesAggregatePrivacyPreview\` (por meio da prévia CSV F3-140).
2. A produção de um cabeçalho e contagens descritivas do CSV fictício por \`SyntheticStageAggregateCsvPreview\`.
3. A revalidação da permissão alegada do destinatário **na entrega**, usando \`SyntheticScheduledReportDeliveryPreflight\` (F3-135).

**Diferencial de segurança:** mesmo após construir o texto CSV apenas com dados fictícios e grupos permitidos pela regra ilustrativa, o componente **não devolve o conteúdo CSV ao seu chamador**, pois a identidade/empresa do destinatário e o direito de consulta/exportação não foram comprovados. O retorno expõe exclusivamente contagens, estados declarados e códigos fechados de advertência.

As advertências incluem grupo pequeno suprimido, acesso alegado revogado, alteração da versão de política ou relatório alegadamente não pronto. Os bloqueios independentes de identidade, empresa e canal de entrega sempre permanecem.

## Testes e limites

\`SyntheticReportDeliveryCompositeInspectorTest\` exercita:
- acesso aparentemente positivo sem concessão nem texto CSV;
- grupo pequeno oculto, inclusive valores elevados ocultos;
- destinatário sem acesso ao entregar, mudança de política e relatório expirado no mesmo cenário;
- ausência de supressão sem liberar a entrega, inclusive com zero avisos;
- entrada inválida ou privada na seção de dados mesmo quando a seção de acesso parece bloqueada;
- esquema de saída sem nome, ID, relógio, bytes de CSV, token, URL de download ou dado pessoal.

A saída fixa \`recognized_revenue_cents=0\` e \`csv_text_returned=false\`. \`report_data_verified\`, \`tenant_scope_verified\`, \`privacy_policy_verified\`, \`recipient_permissions_verified\`, \`transport_verified\`, \`authorized\` e todas as capacidades \`can_*\` são sempre falsas.

Isto **não é relatório que possa ser enviado, proteção real contra differencing, prova de RLS, link público, exportação, entrega ou autorização**. Requer aprovação independente, sessão e destinatário autenticados, políticas de campos/tenant, confirmação de retenção, auditoria e revisão de segurança antes de qualquer operação. Não houve SQL, endpoints, IAM/ALLOW, clientes, custos, VPS, main ou produção.
