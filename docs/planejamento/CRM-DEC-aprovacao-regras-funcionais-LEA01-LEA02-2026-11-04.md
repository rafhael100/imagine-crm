# CRM-DEC — Aprovação funcional das regras de LEA-01 e LEA-02 (Básico)

**Data:** 04/11/2026.
**Manifestação:** o responsável respondeu **“ok”** à pergunta direta “Você aprova essas regras de funcionamento para LEA-01 e LEA-02?”, após a apresentação de uma tabela completa de recomendações.
**Status:** **REGRAS FUNCIONAIS APROVADAS PARA O PLANEJAMENTO**, com homologações técnicas/comerciais ainda pendentes. Não equivale a publicação dos modelos nem a autorização de implementação.

## 1. Regras aprovadas

| Dimensão | Decisão funcional aprovada |
|---|---|
| LEA-01 — novo lead | Notificação **interna ao CRM** somente após confirmação oficial do registro do lead no CRM, nunca apenas por HTTP 202, recepção inicial do site ou fila técnica. |
| Origem dos leads | Considerar **cadastro manual** e **formulários de sites integrados**, com evento oficial, permissões e identidade da empresa. |
| Importação por planilha/lote | **Não emitir milhares de notificações individuais automaticamente** em uma importação massiva; tratamento específico/agrupamento/opt-in dependerá de política própria. |
| LEA-02 — significado | Avisar quando a empresa **não registrou o primeiro atendimento efetivo** do lead, e não quando o cliente deixa de responder após contato. Semântica já ratificada anteriormente. |
| Prazo padrão do LEA-02 | **24 horas corridas**, contado a partir da confirmação oficial do lead no CRM para o primeiro ciclo; não a partir de persistência temporária no sistema de Sites. |
| Opções de prazo | Configuração guiada: **4, 12, 24, 48 ou 72 horas corridas**; sem edição livre de timers pelo cliente no Básico. |
| Marco de conclusão do primeiro atendimento | **Atendimento efetivo registrado por usuário autorizado**, com fato estruturado e auditável; mera leitura, alteração cadastral ou nota genérica não comprovam atendimento. |
| Tentativa sem sucesso | **Ligação não atendida** é registrada como **tentativa**, não como atendimento efetivo concluído, e não encerra automaticamente a espera de primeiro atendimento. |
| Notificações repetidas | **No máximo um aviso interno por ciclo lógico de primeiro atendimento pendente**; retries, reentregas e revisões de versão não criam um novo ciclo ou novo aviso. |
| Canal e efeito da primeira versão | **Somente aviso interno no CRM** a destinatário autorizado; sem mensagens WhatsApp/e-mail, IA externa ou criação automática de tarefas comerciais. |
| Experiência do Básico | Cliente escolhe modelo homologado, ajusta os parâmetros guiados permitidos e ativa/pausa, sem editar gatilhos, condições ou workflows n8n. |

## 2. Segurança, direitos e consistência

- Execução pelo **motor próprio do CRM**, sem traduzir automações dos clientes para workflows n8n.
- Preservar isolamento por organização, autenticação, consentimentos, idempotência, logs mínimos, revalidação de direitos no momento do efeito, filas duráveis e confirmação oficial de ações.
- **LEA-01/02 são automações comerciais opcionais**: contam vagas/slots vigentes somente depois de homologação, aprovação individual, ativação válida e verificação dos direitos. Referência histórica Básico = 3 vagas ativas, sujeita a revisão final; não ratificar automaticamente esse número.
- **Básico público e Básico cortesia têm os mesmos direitos**. Avisos operacionais obrigatórios da Agenda são de sistema (SO), não consomem vaga comercial e não devem ser duplicados.
- Captação e persistência de leads permanecem obrigatórias e não dependem de ativação dos modelos, saldo IA ou disponibilidade do n8n.

## 3. Limites desta aprovação — permanecem abertos

1. **Não significa aprovação individual de LEA-01 ou LEA-02 para publicação/ativação comercial.** Validar modelos um a um e suas fontes oficiais T01/T13, ação de notificação A03, eventos e contratos tipados CORE/DAT/COM/Datas.
2. Definir contrato estruturado de **registro e comprovação de atendimento efetivo** no Básico; preservar distinção entre tentativa sem sucesso e contato realizado, sem implantar módulo de tarefas ou canal não contratado.
3. Homologar política específica de importação em massa (agregação, supressão de aviso por registro, opt-in), eventos e quotas de volume, sem perda do lead oficial.
4. Especificar aplicação técnica do relógio em UTC/fuso IANA, mudança de prazo com ciclo em andamento, feriados/horário útil se futuramente oferecidos, retentativas, transições e recuperação.
5. Homologar **A03**, destinatário, persistência, consulta, retenção de notificações comerciais, acessibilidade, observabilidade e custos técnicos. Não copiar os 90 dias da Agenda nem os 18 dias da fila temporária de Sites.
6. Detalhar prevenção de loops, suspensão/downgrade, dedupe real por tenant/modelo/evento/ciclo, concorrência, quotas e testes de isolamento/idempotência.
7. Manter **AUT-P01..P14**, **LIB02-P01..P08**, **LIB04-P02/P03/P04/P05/P06/P07/P08/P09 nos aspectos técnicos**, **MAT01**, **CORE/SEG/INT** e gates P0 ainda abertos. Não tratar essa decisão como homologação integral da arquitetura ou fechamento do catálogo.

## 4. Precedência e proibição de implementação

Esta aprovação **ratifica a direção funcional** já aceita em:
- [AUT-LIB04 — interação, prazos e antiduplicidade](CRM-AUT-LIB04-criterios-interacao-prazos-antiduplicidade-LEA01-LEA02-v0.1.md).
- [TAR-A — avisos internos](CRM-DEC-escolha-TAR-A-avisos-internos-LEA01-LEA02-2026-11-04.md).
- [Motor próprio de automações](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md).
- [AUT-P0 — execução segura](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md).

**Não autorizado:** programação, testes reais, instalação, provisionamento, contratação, despesas, alterações em produção, criação de recursos pagos ou disponibilização pública dos modelos. Seguir com planejamento e homologação detalhada antes de qualquer implementação.
