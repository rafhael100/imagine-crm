# 06 — Infraestrutura e operações

## Ambientes e implantação — APROVADO
- Produção e homologação separados, com credenciais independentes.
- Homologação com pagamentos sandbox e dados fictícios ou anonimizados.
- Entrega controlada: desenvolvimento → homologação/validação → aprovação → liberação à produção por responsável técnico autorizado.
- Registrar versão, responsável e momento da publicação; monitorar e permitir rollback.
- Manutenção programada: avisos no CRM e por e-mail, página informativa; evitar indisponibilidade quando possível.
- Manutenção emergencial: pode ser imediata, com registro do incidente e comunicação aos clientes afetados.

## Monitoramento
- Painel administrativo e alertas de CRM/API, PostgreSQL, filas, n8n, IA, pagamentos, NFS-e e recursos CPU/memória/armazenamento.
- Não apresentar dados confidenciais de empresas no painel operacional.

## Backup — APROVADO
- Backup completo diário.
- Incrementais ou arquivamento contínuo para reduzir perda entre backups completos.
- Cópias criptografadas em armazenamento externo separado do servidor principal.
- Retenção operacional padrão de **30 dias**.
- Testes periódicos de restauração e alertas de falha.
- RPO inicial planejado: **15 minutos**.
- RTO inicial planejado: **4 horas**.
- RPO/RTO são **metas**, dependem de infraestrutura/testes e não garantem ausência de perda ou recuperação automática.
- Política de descarte precisa conciliar backups, retenção comercial e obrigações LGPD.

## Pendente
- Provedores, custo/SLAs, arquitetura HA, periodicidade exata de testes, mecanismos concretos de PITR, alertas e procedimentos de runbook.
