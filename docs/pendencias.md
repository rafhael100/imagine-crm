# Pendências de especificação — 31/10/2026

## Prioritárias
1. Detalhar exportações CSV/XLSX já aprovadas: política de expiração dos links, limites de volume, formato dos campos e retenção dos arquivos temporários.
2. Detalhar importação já aprovada: normalização de telefone/e-mail, regras de deduplicação, limites de tamanho e política de atualização dos campos.
4. Definir preços dos planos Essencial, Profissional e Empresarial, assentos incluídos, cotas e matriz de recursos.
4. Ratificar estratégia multi-tenant: `tenant_id`, PostgreSQL RLS, isolamento no backend/testes.
5. Desenhar CRM operacional: leads, contatos, empresas, oportunidades, pipelines, etapas, atividades, tarefas e automações.
6. Especificar agentes IA Prospector/Comercial: funções, prompts, ações permitidas, preços e cotas.
7. Definir preços, tributos, provedor de pagamento e integração NFS-e.
8. Detalhar política de LGPD, descarte de backups e pedidos de titulares.
9. Elaborar arquitetura física, tabelas, APIs, filas, eventos, autorização e testes.
10. Planejar recuperação de desastres para comprovar RPO de 15 minutos/RTO de 4 horas.
11. Definir UX, protótipos e critérios de aceite antes de iniciar desenvolvimento.

## Integração Imagine Portal × CRM — pendências após IN-002 (01/11/2026)

- **Aprovado:** comprador de Onepage/futura Landing Page recebe benefício gratuito de CRM Básico e paga somente o site no Portal Imagine; upgrade CRM é contratado e cobrado no próprio CRM, criando segunda mensalidade.
- Definir cotas/recursos Básico e mapeamento para os planos comerciais Essencial/Profissional/Empresarial; não extrapolar limites do trial.
- Definir vigência do benefício e regras de inadimplência, cancelamento, estorno e recontratação do site.
- Definir fallback do plano pago CRM para o Básico, limites excedentes e retenção.
- Definir catálogo e valor da Landing Page e marcos para concessão do benefício.
- Definir API autenticada, vínculos tenant/pedido, fila idempotente de lead forms, privacidade e testes de integração.
- Documento: [07-integracao-site-e-crm-basico.md](07-integracao-site-e-crm-basico.md).

## Observações
- Este conjunto é um marco documental inicial; não significa que todos os capítulos estão fechados.
- Não aprovar automaticamente exemplos, descontos ilustrativos nem arquitetura apenas sugerida.

11. Detalhar campos personalizados: matriz técnica de conversões compatíveis entre tipos, validações específicas por tipo, impacto da obrigatoriedade sobre registros antigos e procedimentos de tratamento dos dados antes da exclusão definitiva (regras gerais de configuração e proteção já aprovadas).
