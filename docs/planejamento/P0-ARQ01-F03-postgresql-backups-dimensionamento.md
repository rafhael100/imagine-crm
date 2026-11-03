# P0-ARQ01-F03 — PostgreSQL, armazenamento e backups

**Data:** 03/11/2026.
**Status:** APROVADA COMO BASE DE PLANEJAMENTO, não homologada tecnicamente nem contratada.
**Decisão:** aceite “ok” após apresentação do escopo F03 de PostgreSQL, armazenamento e backups.

## Diretrizes de planejamento

1. **PostgreSQL** em instância própria, separada da aplicação CRM/Laravel, com isolamento multiempresa, autorização e RLS; a Leaseweb VPS independente é a candidata econômica inicial, sem escolha física definitiva.
2. **Aplicação Laravel** em VPS distinta, com comunicação banco-aplicação criptografada, restrita e protegida por firewall/VPN ou outra solução homologada.
3. **Anexos de clientes** em Object Storage privado e separado da aplicação; Leaseweb Object Storage e Cloudflare R2 seguem em comparação.
4. **Banco de dados:** backups externos criptografados e restauráveis; avaliar backups de base, arquivamento de WAL e recuperação pontual (PITR). Ainda definir periodicidade, retenção, mecanismos, custo, integridade e testes.
5. **Anexos:** preservar regra aprovada de replicação assíncrona incremental a cada 15 minutos para destino independente e retenção técnica de 30 dias; não confundir com retenção do banco.
6. **Monitoramento:** alertas independentes do próprio CRM para falha/atraso em banco, filas, replicação e backups.
7. **Custos:** estudo comparativo entre PostgreSQL autogerido em VPS e serviço gerenciado, somando armazenamento, backup, saída, observabilidade, segurança e suporte, sem considerar apenas a mensalidade do servidor.
8. **Regiões:** comparar Washington, Montreal e Europa com cotação oficial, latência a partir do Brasil e requisitos de proteção de dados.
9. **Continuidade:** não presumir alta disponibilidade com uma VPS de aplicação e outra de banco. RPO 15 min e RTO 4h permanecem referências preliminares, não metas finais aprovadas.

## Pendências

- Dimensionamento e orçamento oficial do banco e do armazenamento;
- Escolha de ferramentas de backup/PITR, retenção e restauração;
- Rede restrita entre aplicação e PostgreSQL;
- Definição de RPO/RTO e aceitação do risco de falhas;
- Homologação da região, LGPD e segurança de dados;
- Homologação do ambiente de produção após autorização.

**ARQ-01 permanece P0 parcial.** Esta aprovação documental não autoriza programação, instalação, contratação, testes ou operação em servidores.