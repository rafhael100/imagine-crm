# Registro consolidado de decisões — 31/10/2026

**Origem:** aprovações fornecidas pelo responsável do projeto em conversas de especificação do CRM Imagine.

| Tema | Decisão aprovada |
|---|---|
| Plataforma | SaaS multiempresa, conta em múltiplos tenants |
| Tecnologia | Laravel REST, PostgreSQL, n8n VPS separado e OpenAI |
| Planos | Essencial, Profissional, Empresarial; assentos adicionais |
| Trial | 14 dias, CNPJ único, e-mail + telefone OTP, sem extensão |
| Trial — cotas | 2 usuários, 20 leads, 2 pipelines, 2 automações, 20 MB, CSV sim, API não |
| Trial — IA | Prospector 10 análises, Comercial 10 solicitações |
| Retenção pós-trial | 60 dias após expiração |
| Inadimplência | Tolerância 7 dias; cancelamento aos 30 dias de atraso |
| Retenção pós-assinatura | 120 dias |
| Pagamentos | Cartão, Pix Automático, Pix avulso, boleto |
| Financeiro | Créditos, estornos, NFS-e automatizada, portal financeiro |
| IA | Agentes opcionais, cotas isoladas, compras extras autorizadas |
| Gestão de clientes | Suspensão/reativação administrativas justificadas e auditadas |
| Administração geral | Permissões detalhadas, MFA, timeout 30 min, auditoria 5 anos |
| Operações | Produção/homologação, deploy controlado, painel de saúde |
| Backups | Completo diário, incrementais/PITR, criptografia externa, 30 dias |
| Recuperação | Metas RPO 15 min e RTO 4 h |
| Administração da empresa | Administrador principal e adicionais isolados |
| Permissões do cliente | Perfis por ação/módulo; escopo próprio/equipe/empresa |
| Equipes | Gestor, integrantes e vínculo em várias equipes |
| Transferências | Individual/em lote com histórico e auditoria |
| Desativação | Revogar acesso/sessões na empresa, preservar histórico |
| Convites | E-mail, validade 7 dias, limite de assentos |
| Segurança cliente | MFA obrigatório para admins da empresa; opcional ou exigível aos demais |
| Auditoria comercial | Imutável por admin cliente, retenção padrão 12 meses |
| Exportação comercial | CSV/XLSX, permissão específica, escopo de visibilidade, auditoria, grandes volumes assíncronos, link seguro temporário, sem credenciais |
| Importação comercial | CSV/XLSX, mapeamento, prévia, detecção de duplicados, ignorar/atualizar, relatório, processamento assíncrono, limites e auditoria |

**Atenção:** os arquivos detalhados prevalecem sobre resumos desta tabela. Uma proposta em discussão não equivale a aprovação.
