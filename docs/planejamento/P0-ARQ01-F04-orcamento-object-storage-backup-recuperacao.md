# P0-ARQ01-F04 — Orçamento progressivo de armazenamento, backups e continuidade

**Data:** 03/11/2026
**Estado:** MINUTA PARA REVISÃO, NÃO APROVADA/NÃO HOMOLOGADA.
**Origem:** F03 aprovada como base documental; estratégia econômica Leaseweb aprovada.
**Limites:** nenhum servidor, assinatura, domínio, bucket ou aplicação foi contratado/configurado. Sem programação ou teste de produção.

## 1. Decisão a avaliar
Para minimizar custo sem comprometer dados: **Cloudflare R2 Standard** como armazenamento primário candidato de anexos, **Leaseweb Object Storage On Demand** como destino independente candidato da réplica de anexos, **PostgreSQL autogerido em VPS isolada** como hipótese econômica e **backup de PostgreSQL orientado a PITR**, preferencialmente com pgBackRest ou ferramenta equivalente, gravado em armazenamento externo protegido. Nenhuma dessas escolhas físicas está homologada.

## 2. Preços públicos verificados (03/11/2026)
| Item | Preço publicado | Custos adicionais e cuidados |
|---|---|---|
| Cloudflare R2 Standard | US$0,015/GB-mês; 10 GB-mês gratuitos + 1 milhão Class A e 10 milhões Class B / mês | Acima da franquia: Class A US$4,50/milhão e B US$0,36/milhão; egress grátis; arredondamentos e condições de cobrança |
| Leaseweb Object Storage On Demand | €0,01733/GB-mês; sem mínimo reservado | Transferência de saída €0,0022/GB até 10 TB; PUT €0,00382/mil; GET €0,00031/mil |
| Leaseweb Object Storage Commitment | €0,01485/GB-mês | Compromisso mínimo 500 GB; **não é econômico de partida** quando o uso real é pequeno |
| Leaseweb Acronis backup | €3,75/25 GB, €10/100 GB, €18,25/200 GB, €41,25/500 GB por mês | Produto de backup de workloads; **não pressupor compatibilidade com PITR PostgreSQL nem usá-lo como único backup do banco** |

Fontes:
- Cloudflare R2: https://developers.cloudflare.com/r2/pricing/
- Leaseweb Object Storage: https://www.leaseweb.com/products-services/object-storage
- Leaseweb Acronis: https://www.leaseweb.com/en/products-services/back-up/acronis

**Não converter moedas automaticamente para somar**; câmbio, impostos, suporte, localização, método de pagamento e descontos podem alterar o custo final.

## 3. Simulação somente de espaço, mensal e ilustrativa
Supondo o volume armazenado de anexos no R2 Standard e uma segunda cópia de tamanho igual no Leaseweb Object Storage On Demand. Não são preços finais; excluem versões, WAL, banco, operações, egress, metadados, redundância maior e todos os servidores.

| Anexos vivos (GB-mês) | R2 primário após franquia 10GB (US$) | Leaseweb segunda cópia (€) |
|---:|---:|---:|
| 10 | 0,00 | 0,17 |
| 50 | 0,60 | 0,87 |
| 100 | 1,35 | 1,73 |
| 250 | 3,60 | 4,33 |
| 500 | 7,35 | 8,67 |
| 1000 | 14,85 | 17,33 |

**Crítico:** a segunda cópia real precisa de retenção histórica de 30 dias e pode consumir MAIS espaço do que o volume vivo. Se o R2 também armazenar backups do banco, a franquia de 10 GB é compartilhada pelos dados daquela conta; os números acima deixam de representar o total faturado.

## 4. Plano lógico proposto para objetos e backup
- Anexos primários: R2 privado, acesso autorizado por backend e tenant, logs/quotas, antimalware e regras de uploads já aprovados.
- **Localização:** para app/banco nos EUA, avaliar R2 com jurisdição US (garantia de armazenar/processar nos EUA). Dica de localização regional não equivale a garantia; não há jurisdição de dados Brasil anunciada em R2. Montar avaliação LGPD.
- Replica de anexos: outra conta/provedor ou domínio de falha, candidato Leaseweb Object Storage On Demand em Montreal, replicação assíncrona alvo de 15 min e retenção técnica 30 dias. Confirmar disponibilidade específica e custos por região.
- Não confundir retenção técnica da réplica com retenção legal dos arquivos dos clientes. Preservar expurgos autorizados e LGPD.
- Proteção contra apagamento: usar versão/retention lock (Cloudflare R2 oferece bucket locks), mas a proteção depende das permissões de alteração das próprias regras. Separar contas, usuários, autorizações e alertas; não prometer WORM inviolável sem homologação.
- Backups do banco: ferramenta candidata pgBackRest com backup base + arquivamento de WAL, suporte a PITR e cópia externa criptografada, monitorada e restaurável. Backups do SO são auxiliares, não a prova da recuperabilidade lógica do banco.
- Cópia DB externa deve ser independente da VPS Leaseweb, por exemplo bucket R2 dedicado ao backup, com credenciais distintas da aplicação e do armazenamento primário. Verificar cenário de falha de Cloudflare e segunda cópia se necessário.
- Monitoramento externo: atraso do WAL, réplica, armazenamento, falha de backup, tentativas de acesso, indisponibilidade dos serviços; revisão de integridade e procedimento de restauração em ambiente isolado depois da autorização.

Documentação:
- R2 localização: https://developers.cloudflare.com/r2/reference/data-location/
- R2 bucket locks: https://developers.cloudflare.com/r2/buckets/bucket-locks/
- PostgreSQL PITR: https://www.postgresql.org/docs/current/continuous-archiving.html
- pgBackRest: https://pgbackrest.org/user-guide.html

## 5. Metas de recuperação e lacunas
- **RPO**: perda máxima de dados admissível (não é frequência de backup). **RTO**: tempo-alvo para recuperar a operação. Ambos devem ser avaliados por CRM app, banco, anexos, captação de leads e filas, em documento CON-01.
- Os 15 minutos de replicação dos anexos são **alvo de ciclo de réplica**, não garantia de RPO; o PITR PostgreSQL exige WAL íntegro arquivado e restauração testada.
- Sem réplica/failover de PostgreSQL e sem segunda instância Laravel, o primeiro ambiente produtivo terá **pontos únicos de falha**. As metas históricas de RPO 15 min/RTO 4 h ainda não foram aprovadas nem comprovadas.
- Aprovar previamente responsabilidades operacionais, tempo de resposta humano, incidente severo, custos de recuperação, contingência e riscos residuais.

## 6. Fases e orçamento
| Fase | Serviços | Regra financeira |
|---|---|---|
| Planejamento | Somente documentação | **Sem nova contratação** |
| Desenvolvimento autorizado | VPS dev isolada, banco sintético, backup dev | Cotar apenas quando necessário; não impor produção completa |
| Primeiro cliente real | VPS app + instância PostgreSQL independente + R2 anexos + destino separado da réplica + PITR DB + monitor independente | Só após cotação e homologação; não assumir que duas VPS baratas bastam |
| Crescimento | Mais instâncias ou Public Cloud/dedicado e banco com alta disponibilidade | Por métricas, incidentes, receita e requisitos, não apenas número de empresas |

**Custos ainda não apurados:** preço real VPS por região/renovação, snapshots, bandwidth/ops, backups WAL e sua retenção, trabalho técnico, ferramentas de segurança, monitoramento, filas/SMTP/WhatsApp, agentes, n8n e Portal.

## 7. Pendências submetidas à aprovação posterior
- F04-P01: R2 Standard como storage principal de anexos versus Leaseweb On Demand.
- F04-P02: armazenamento Leaseweb On Demand Montreal ou outra região/fornecedor para cópia de 30 dias.
- F04-P03: banco backup com base+WAL/PITR; retenção e mecanismo de isolamento.
- F04-P04: jurisdição dos buckets, base jurídica de transferência internacional, chaves/contas.
- F04-P05: retenção e exclusão governadas, versionamento e controles de bloqueio de deleção.
- F04-P06: modelo de custo completo, aprovação de RPO/RTO e tolerância à indisponibilidade.
- F04-P07: homologação futura de restauração, integridade e failover.

**ARQ-01 continua P0 parcial.** F04 é minuta informativa. Sua revisão/aceite não autoriza programação, contratação, instalação, testes ou implantação.
