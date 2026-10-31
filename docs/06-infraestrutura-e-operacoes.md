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

## Armazenamento privado de arquivos compatível com S3 — APROVADO
- Armazenar documentos, propostas, imagens e anexos em serviço de armazenamento de objetos privado compatível com S3, em vez de depender do disco local do servidor Laravel.
- Organizar objetos por empresa (`tenant_id`) e identificadores não previsíveis; não considerar prefixos de objetos como substitutos de autorização.
- Exigir autenticação e verificação de tenant e permissões na aplicação antes de liberar upload, visualização ou download; objetos privados por padrão.
- Disponibilizar downloads por URLs assinadas com validade curta; compartilhamentos externos devem ter escopo específico, validade configurada e revogação efetiva (por controle na aplicação, quando necessário).
- Aplicar quotas de armazenamento por plano e monitorar consumo por empresa; valores de limites dos planos permanecem pendentes, salvo limites de trial já definidos.
- Respeitar políticas de retenção, exclusão, auditoria e privacidade, incluindo remoção segura de objetos e referências conforme regras aprovadas.
- Prever proteção e recuperação dos arquivos com versionamento/backup e procedimentos de restauração testáveis; escolher fornecedor e parâmetros técnicos detalhados posteriormente.
