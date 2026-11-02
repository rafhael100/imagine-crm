# INT-R01 — Governança, Contratos Técnicos e Critérios de Homologação Integrada

**Status funcional:** APROVADA integralmente pelo responsável.
**Natureza:** especificação transversal das INT-01 a INT-12; **não** INT-13 nem motor/serviço executável novo.
**Fase:** documentação; não autoriza código, implantação, testes em sistemas reais ou alteração de infraestrutura.

## R01.01 — Governança e responsabilidades
Um único módulo proprietário por ato de negócio; INTs solicitam/coordenam/transportam conforme sua competência e não assumem os poderes de FIN, CON, CAT, VEN, AGD, ATD, COM, PVD, RET, MKT, CMS, Identidade e Segurança e sistemas operacionais. Aprovações pela Central de Aprovações; tarefas pelo Motor Central de Tarefas; datas pelo Motor de Datas e Prazos; comunicações dirigidas por COM.

## R01.02 — Contratos compartilhados
Contrato tipado/versionado define ator, tenant, origem, destino, módulo proprietário, operação, recurso, finalidade, campos, pré-condições, permissões, restrições, resultados e tratamento de falhas. Sem URLs arbitrárias, SQL ou código livre. Contratos de INT-04 representam capacidades verificadas, não autorização ilimitada.

## R01.03 — Identificação e correlação
Identificadores estáveis para tenant, conta, conexão, processo, instância, etapa, comando, operação e tentativa. Correlação, causalidade, idempotência e referência de versão e fonte oficial. Sem segredos nos envelopes de execução.

## R01.04 — Autorização, identidade e segurança
Menor privilégio; RBAC e MFA segundo políticas centrais; verificação de escopo e finalidade; revalidação em início, operação protegida, retomada, tarefas assíncronas e compensações, quando aplicável. Aprovação anterior fora de escopo e credencial revogada não autorizam continuidade. Direitos internos da Imagine e de tenants são separados.

## R01.05 — Estados e confirmação oficial
Estados técnicos de interoperabilidade: preparado, enviado, aceito, aplicado, confirmado, rejeitado, pendente, inconclusivo. Estados específicos de modelos, instâncias, sincronizações e negócios continuam nos proprietários. HTTP 2xx, execução n8n e recebimento de webhook não são confirmação final. Confirmações precisam de referência/evidência oficial.

## R01.06 — Idempotência, concorrência e consistência
Chaves de idempotência contextuais; controle de concorrência por recurso/efeito; checkpoints; persistência local, outbox quando aplicável e filas duráveis. Reconhecer ausência de atomicidade distribuída e inexistência de garantia exatamente-uma-vez ponta a ponta. Retentativa de escrita inconclusiva requer reconciliação.

## R01.07 — Retentativas, falhas e recuperação
Categorias temporária, quota, autorização inválida, contrato incompatível, resultado desconhecido, falha persistente, segurança e divergência de negócio. Retentativas limitadas, taxa controlada, suspensão quando necessário, investigação e recuperação gradual após revalidação. INT-10 gerencia recuperação especializada, sem usurpar decisões de negócio.

## R01.08 — Versionamento e compatibilidade
Versionar contratos, capacidades, conectores, mapeamentos, modelos, políticas e referências por instância. Mudança incompatível exige testes, homologação e migração autorizada. Nenhuma mutação silenciosa de instância ativa.

## R01.09 — Eventos, filas e operações assíncronas
Eventos tipados/versionados, entrega durável, deduplicação, causalidade, tratamento de eventos tardios/fora de ordem e estados persistentes. Motor Central de Eventos detém eventos internos; INT-06 gerencia fronteira externa; infraestrutura central provê filas. Não criar motores paralelos.

## R01.10 — Observabilidade, evidências e auditoria
INT-10 monitora e recupera; INT-12 correlaciona evidências; Auditoria Geral governa trilhas, integridade, proveniência, retenção e acesso. Analytics produz indicadores, dashboards e relatórios oficiais. Dados sensíveis minimizados e protegidos.

## R01.11 — Limites técnicos e isolamento multiempresa
Definir posteriormente quotas, concorrência, frequência, processamento, capacidade, custos e controles por tenant. Não bloquear dados estruturados obrigatórios para permitir limites comerciais. Arquivos seguem franquias aprovadas: Básico 1 GB, Essencial 5 GB, Profissional 20 GB, Empresarial 50 GB, avisos 80/95%, a 100% bloqueio apenas de novos anexos. Trial sem API pública, mantendo integrações nativas permitidas. Isolar processos internos Imagine de tenants.

## R01.12 — Homologação integrada e critérios de aceite
Exigir pré-condições, identidade/permissões, dados de teste, sequência, resultados, falhas, evidências, responsáveis e aceite. Cobrir fluxos completos, revogação, timeouts após transmissão, duplicidade, fora de ordem, falhas externas, versões incompatíveis, compensações parciais, isolamento, auditoria, concorrência e continuidade. Sem presunção de testes já executados.

## Referências
- [INT-R02 — Matriz de rastreabilidade e cenários HT](INT-R02-rastreabilidade-dependencias-validacao.md)
- [INT-P0 — Pendências documentais](INT-P0-fechamento-documental.md)

**Detalhamento ainda pendente:** formatos executáveis precisos dos envelopes, catálogo de códigos de erro, quotas numéricas, retenções especializadas, política de concorrência e procedimentos de testes. As diretrizes acima estão aprovadas, mas essas definições não foram inventadas neste documento.
