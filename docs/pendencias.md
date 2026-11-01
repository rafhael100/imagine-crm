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
- **IN-003 aprovada:** Básico cortesia será uma versão enxuta, exclusiva para site/landing page, diferente do Essencial.
- **IN-004 aprovada:** liberar Agenda e Agendamentos no Básico. **IN-007 APROVADA:** Básico terá somente agendamentos **internos e manuais**; autoagendamento público por site/página do CRM é exclusivo dos **planos pagos**. **IN-008 APROVADA:** apenas **1 profissional com agenda própria por organização Básico**. **IN-009 APROVADA:** até **10 serviços ativos por organização** no Básico; serviços inativos não consomem vaga e históricos permanecem. **IN-011 APROVADA:** horários semanais flexíveis, períodos múltiplos, pausas e bloqueios, feriados manuais, exceções fora do expediente com confirmação de usuário autorizado e sem conflitos. **Ainda definir:** quantidade de usuários administrativos, limite comercial de agendamentos manuais por mês, detalhamento de justificativas/exceções de bloqueios, confirmações/notificações e integrações; propostas em [11 — Agenda manual](11-agenda-manual-basico-cortesia.md). **IN-005 APROVADA:** até 3 automações comerciais ativas por organização, escolhidas de modelos prontos. **Pendente:** catálogo concreto (sugestões T01–T05), parâmetros, avisos de agenda e regras de limite/custo; não aplicar por analogia cotas dos planos pagos. Documento: [09-revisao-agenda-e-automacoes-basico.md](09-revisao-agenda-e-automacoes-basico.md). **Ainda aprovar** matriz recomendada de recursos/quotas (1 usuário, 1 funil, formulários por site, sem agentes de IA; modelos de automação comercial permitidos conforme IN-005/IN-006), após avaliar escala, custo, uso justo e retenção. Ver [08-plano-basico-cortesia.md](08-plano-basico-cortesia.md).
- Definir vigência do benefício e regras de inadimplência, cancelamento, estorno e recontratação do site.
- Definir fallback do plano pago CRM para o Básico, limites excedentes e retenção.
- Definir catálogo e valor da Landing Page e marcos para concessão do benefício.
- Definir API autenticada, vínculos tenant/pedido, fila idempotente de lead forms, privacidade e testes de integração.
- Documento: [07-integracao-site-e-crm-basico.md](07-integracao-site-e-crm-basico.md).

## Biblioteca de Automações — IN-006 (01/11/2026)

- **Diretriz:** biblioteca central de modelos prontos, organizada por módulos e critérios de elegibilidade; modelos simples úteis ao Básico e **modelos mais avançados para planos pagos**. Classificações individuais ainda são propostas; confirmar a interpretação na matriz comercial final.
- **Ordem aprovada do trabalho:** documentar todos os modelos/categorias e seus custos/dependências **antes** de rever a quantidade simultânea por plano. **Não mudar agora** os limites anteriores: Básico Cortesia 3; Essencial 5; Profissional 20; Empresarial 50.
- **Pendência de fechamento:** contar modelos homologados e simular uso/custos, definir quais ficam em cada plano, distinguir automações comerciais de operações obrigatórias da agenda/sistema, e só então submeter novas quotas numéricas para aprovação.
- Referência: [10-biblioteca-automacoes-e-criterios-de-planos.md](10-biblioteca-automacoes-e-criterios-de-planos.md).

## Avisos por plano e upgrade contextual — IN-010 (01/11/2026)

- **Aprovado:** ao tentar recurso fora do plano ou atingir quota, explicar motivo e oferecer upgrade pertinente (ou adicional elegível), preservando opção de liberar capacidade dentro do plano; sem ativação, cobrança ou mudança de plano sem consentimento e autorização.
- Não oferecer upsell por problemas de segurança, falta de permissão de usuário, cobrança pendente, integração fora do ar ou erro técnico.
- **Pendente:** UX detalhada, avisos preventivos, cores/textos, política de exibição, contrato de erros para API, matriz comercial final, custos e preços publicados, casos de trial e assinaturas negociadas.
- Detalhes: [12-limites-e-upgrade-contextual.md](12-limites-e-upgrade-contextual.md).

## Observações
- Este conjunto é um marco documental inicial; não significa que todos os capítulos estão fechados.
- Não aprovar automaticamente exemplos, descontos ilustrativos nem arquitetura apenas sugerida.

11. Detalhar campos personalizados: matriz técnica de conversões compatíveis entre tipos, validações específicas por tipo, impacto da obrigatoriedade sobre registros antigos e procedimentos de tratamento dos dados antes da exclusão definitiva (regras gerais de configuração e proteção já aprovadas).
