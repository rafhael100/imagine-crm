# INT-R02 — Matriz de Rastreabilidade, Dependências e Validação Final

**Status funcional:** APROVADA integralmente pelo responsável em 2026-11-02.
**Natureza:** documento transversal de fechamento arquitetural; não é INT-13 nem sistema implementado.
**Status documental:** matriz inicial versionada; rastreamento individual completo e conferência literal ainda pendentes. Não declara homologação de software.

## 1. Matriz mestre de competências
| INT | Propriedade exclusiva | Dependências principais | Testes propostos |
|---|---|---|---|
| [INT-01](INT-01.md) | Cadastro, ciclo de vida e referências oficiais de provedores, contas e conexões | INT-02, INT-04, INT-11 | HT-01, HT-02, HT-18 |
| [INT-02](INT-02.md) | Gestão dos vínculos de autorização e das credenciais externas por escopo e finalidade | INT-01, Identidade e Segurança, Central de Aprovações | HT-01, HT-02, HT-13 |
| [INT-03](INT-03.md) | Contratos estruturais de dados, mapeamentos versionados e transformações tipadas | INT-04, INT-07, INT-08 | HT-03, HT-07, HT-12 |
| [INT-04](INT-04.md) | Operações tipadas, contratos, adaptadores e capacidades verificadas | INT-02, INT-03, INT-11 | HT-01, HT-03, HT-08, HT-12 |
| [INT-05](INT-05.md) | Gestão de aplicações autorizadas e uso da API pública do CRM | INT-02, INT-04, Identidade e Segurança | HT-04, HT-13 |
| [INT-06](INT-06.md) | Fronteira externa de recepção, entrega e confirmação técnica de webhooks | INT-02, INT-04, Motor Central de Eventos, Filas | HT-05, HT-09, HT-16 |
| [INT-07](INT-07.md) | Sincronizações delimitadas por fontes, destinos, entidades, campos e políticas | INT-03, INT-04, INT-06, INT-10 | HT-06, HT-08, HT-16 |
| [INT-08](INT-08.md) | Movimentações delimitadas e controladas de dados por operação/lote | INT-02, INT-03, INT-04, Arquivos, Aprovações | HT-03, HT-07, HT-13, HT-17 |
| [INT-09](INT-09.md) | Modelos, instâncias, etapas, dependências, estados, coordenação e planos de compensação | INT-01 a INT-08, Motores Centrais, Aprovações, Tarefas, Eventos, Filas | HT-08, HT-09, HT-10, HT-11, HT-15 |
| [INT-10](INT-10.md) | Saúde operacional, diagnóstico, incidentes e recuperação técnica especializada | INT-01 a INT-09, Central de Observabilidade, Eventos, Filas, Aprovações | HT-02, HT-06, HT-08, HT-11, HT-16 |
| [INT-11](INT-11.md) | Catálogo oficial, homologação, compatibilidade, versões, publicação, distribuição e descontinuação de conectores | INT-01 a INT-04, Identidade e Segurança, Aprovações | HT-01, HT-12, HT-18 |
| [INT-12](INT-12.md) | Visão especializada, investigação e correlação de evidências oficiais de integração | INT-01 a INT-11, Auditoria Geral, Analytics, Identidade e Segurança | HT-14, HT-17 |

Dependências representam contratos e ligações, não uma ordem universal de execução. INT-10/11/12 são transversais.

## 2. Serviços centrais e autoridades
- Identidade e Segurança: autenticação, RBAC, MFA e controles centrais; INT-02 trata autorização/credenciais externas dentro desta governança.
- Central de Aprovações: decisões humanas e alçadas, sem duplicação.
- Construtor Visual de Fluxos e Motores Centrais de Gatilhos, Condições e Ações: modelagem e execução autorizada de regras.
- Motor Central de Eventos: eventos oficiais internos; INT-06 fronteira externa.
- Motor Central de Tarefas: intervenções humanas; Motor Central de Datas e Prazos: calendários/vigências, sem confundir timeout técnico e vencimento de negócios.
- Infraestrutura Central de Filas e Infraestrutura Central de Arquivos: transporte durável e documentos.
- COM/Coordenador Central: comunicações aos clientes; Analytics: indicadores/relatórios oficiais; Auditoria Geral: evidências, integridade e retenção.

## 3. Autoridade por domínio
FIN: pagamentos/estornos; CON: contratos/cancelamentos; CAT: catálogo; VEN: leads e vendas; AGD: reservas/agendamentos; ATD: atendimento; COM: comunicações; PVD e RET: atos de seus domínios conforme contratos aprovados; MKT: campanhas; CMS: publicações; Identidade e Segurança: privilégios e acessos; sistemas operacionais: provisionamento/reversões em suas competências. INT-09 coordena; INT-10 recupera tecnicamente; INT-12 audita — nenhum substitui os proprietários.

## 4. Invariantes INV-01 a INV-16
- **INV-01** — Isolamento entre tenants, contas, conexões, execuções e evidências.
- **INV-02** — Um módulo proprietário para cada operação de negócio.
- **INV-03** — Autorização contextual e revalidação em situações exigidas.
- **INV-04** — Aprovação humana não equivale à execução.
- **INV-05** — HTTP, webhook ou n8n não equivalem à confirmação de negócio.
- **INV-06** — Sem promessa de atomicidade distribuída ou exatamente uma vez ponta a ponta.
- **INV-07** — Idempotência contextual, concorrência controlada e reconciliação.
- **INV-08** — Retentativas de escrita inconclusiva somente seguras e autorizadas.
- **INV-09** — Compensação condicionada a reversibilidade e autoridade competente.
- **INV-10** — Versões ativas não sofrem alteração silenciosa.
- **INV-11** — Sem SQL, scripts arbitrários ou código livre a clientes/agentes.
- **INV-12** — n8n sem superprivilégios ou acesso irrestrito a bancos e segredos.
- **INV-13** — IA consultiva, sem autoconcessão de autoridade.
- **INV-14** — Auditoria Geral como fonte oficial, sem trilhas concorrentes.
- **INV-15** — Arquivos e dados estruturados têm políticas distintas.
- **INV-16** — Limites comerciais não comprometem dados e controles essenciais.

## 5. Cenários HT-01 a HT-18 (planejados, não executados)
| ID | Cenário | Componentes | Critério mínimo de resultado | Estado |
|---|---|---|---|---|
| HT-01 | Conexão autorizada | INT-01,02,04,11 | Conector homologado, conexão válida e escopos corretos | Não executado |
| HT-02 | Revogação de credenciais | INT-01,02,10 | Novas operações protegidas impedidas | Não executado |
| HT-03 | Transformação de dados | INT-03,04 | Validação e transformação reproduzíveis | Não executado |
| HT-04 | API no trial | INT-05, Segurança | API pública bloqueada; nativas permitidas preservadas | Não executado |
| HT-05 | Webhook duplicado | INT-06, Eventos | Nenhum efeito indevidamente duplicado | Não executado |
| HT-06 | Sincronização interrompida | INT-07,10 | Retomada segura por checkpoint | Não executado |
| HT-07 | Importação parcialmente válida | INT-03,08 | Tratamento por lote e reconciliação | Não executado |
| HT-08 | Operação externa inconclusiva | INT-04,09,10 | Sem repetição de escrita insegura | Não executado |
| HT-09 | Processo com etapas paralelas | INT-09, Eventos | Convergência apenas com resultados confirmados | Não executado |
| HT-10 | Compensação parcial | INT-09,10 | Preservação dos efeitos e pendências | Não executado |
| HT-11 | Provedor indisponível | INT-01,09,10 | Recuperação autorizada e gradual | Não executado |
| HT-12 | Atualização incompatível | INT-03,04,09,11 | Proteção de versões e instâncias ativas | Não executado |
| HT-13 | Acesso entre tenants | Todas, Segurança | Isolamento e bloqueio de ato indevido | Não executado |
| HT-14 | Auditoria ponta a ponta | INT-01 a INT-12 | Reconstrução verificável do histórico | Não executado |
| HT-15 | IA recomenda ação crítica | INT-09 a INT-12, Aprovações | Sem execução autônoma não autorizada | Não executado |
| HT-16 | Falha de filas e eventos | INT-06 a INT-10, Filas | Estados preservados, recuperação segura | Não executado |
| HT-17 | Exportação de evidências | INT-12, Auditoria Geral | Permissão, proteção e rastreabilidade | Não executado |
| HT-18 | Conector suspenso/revogado | INT-01,02,10,11 | Novas ativações bloqueadas; pendências tratadas | Não executado |

Cada HT exigirá documento de procedimento com preparação, permissões, dados, entradas, estado inicial, sequência, falhas simuladas, evidências, versão e aceite. Novos cenários podem ser acrescentados, sem substituir os 18 aprovados.

## 6. Modelo para cada requisito rastreável
Campos: ID (TR-INTxx-###), origem da decisão e sua versão, descrição, escopo/tenant/finalidade, INT proprietária, módulo de negócio, dependências, contrato técnico, políticas e autorizações, INV, cenário HT, critério de aceite, estado documental, evidências, autor da revisão e histórico de alterações.

**Exemplo meramente ilustrativo** TR-INT09-001: comando enviado e aceito não equivale a operação de negócio concluída sem confirmação oficial; INT-09; INV-05; HT-08; teste não executado.

## 7. Estados documentais e técnicos
- Aprovado funcionalmente; documentação pendente; documentado; em revisão cruzada; divergência identificada; validado documentalmente; aguardando homologação; homologado (apenas após teste real); dispensado (justificado e aprovado).
- Não usar “homologado” para um requisito apenas escrito.

## 8. Pendências P0/P1/P2
- **P0:** documentos INT-01 a INT-12 integrais, rastreabilidade de aprovação, fronteiras, contratos/estados/autorizações e isolamento, sem divergências críticas não tratadas.
- **P1:** quotas numéricas, concorrência, dimensionamento, monitoramento e recuperação, versionamento, retenção especializada e custos.
- **P2:** distribuição comercial avançada e documentação complementar. P2 não elimina decisões necessárias antes do lançamento.

## 9. Condições G01 a G06
- **G01:** todas as decisões aprovadas possuem referência rastreável.
- **G02:** INT-01 a INT-12, INT-R01 e INT-R02 estão documentadas integralmente e versionadas.
- **G03:** todas as dependências críticas e conflitos de competência estão resolvidos.
- **G04:** pendências abertas possuem responsável, impacto, prioridade e etapa de fechamento.
- **G05:** revisão de arquitetura e aceite documental expresso concluídos.
- **G06:** versão aprovada identificada por commit/referência imutável no GitHub.

**Estado atual:** G01–G05 em verificação/pendentes de comprovação integral; G06 requer aceite da versão documental fechada, não apenas um commit inicial.

## 10. Referências cruzadas e precedência
- [INT-R01](INT-R01-governanca-contratos-homologacao.md), [Matriz P0](INT-P0-fechamento-documental.md), [Origem e precedência](DECISOES-origem-precedencia.md).
- [IN-167](../IN-167-consolidacao-arquitetura-agentes-tecnicos.md) e [IN-168](../IN-168-matriz-pendencias-fechamento-arquitetura.md) detalham agentes técnicos e pendências adjacentes: não presumir conclusão de suas especificações pelo fechamento da INT-R02.
