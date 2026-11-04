# CRM-DEC — LEA-02: tentativa de contato sem sucesso não é atendimento efetivo

**Data:** 04/11/2026.
**Manifestação do responsável:** “ok” em resposta à recomendação expressa de **registrar uma ligação não atendida como tentativa, mas não como atendimento efetivo**, distinguindo também atendimento realizado e resposta do cliente.
**Status:** **DECISÃO FUNCIONAL APROVADA PARA PLANEJAMENTO**, sem schemas/contratos técnicos, parâmetros finais ou implementação.

## 1. Diretrizes aprovadas

1. **Ligação não atendida:** registrar como **tentativa de contato sem sucesso**, com ator, data/hora, canal/resultado e vínculo ao lead quando autorizado. **Não** marcar como atendimento efetivo nem como resposta do cliente.
2. **Primeiro atendimento pendente (LEA-02):** uma tentativa não atendida **não encerra por si só** o estado de primeiro atendimento pendente. O prazo/alerta permanece sujeito à regra oficial do modelo; eventual gestão de tentativas repetidas ou escalonamento terá contrato separado.
3. **Atendimento efetivamente realizado:** pode encerrar a condição de primeiro atendimento pendente **quando registrado e validado** pelo módulo proprietário, com resultado real, identidade/autorização e trilha. Os critérios exatos de contato bem-sucedido e prova ainda precisam ser especificados e aprovados.
4. **Resposta do cliente:** é fato distinto de tentativa feita pela empresa e de atendimento efetivo. Uma mensagem recebida sem tratamento pela equipe **não comprova** que a empresa atendeu. A política para ausência de resposta **após contato da empresa** continua fora do LEA-02 desta primeira versão.
5. **Ações administrativas** como abrir ficha, editar campo/etiqueta, ler aviso, gerar nota genérica ou receber sugestão de IA **não são prova automática** de atendimento.
6. **TAR-A preservada:** LEA-02 permanece candidato a **aviso comercial interno**, sem criação automática de tarefas no Básico. Avisos internos obrigatórios da Agenda (SO) continuam fora dos slots comerciais.

## 2. Classificação mínima de eventos — proposta de especificação, não contrato final

| Família conceitual | Resultado de negócio | Efeito esperado sobre LEA-02 |
|---|---|---|
| Tentativa sem sucesso | A empresa tentou contato, mas não houve contato efetivo | **Não encerrar** o primeiro atendimento pendente |
| Atendimento efetivo confirmado | Empresa realizou atendimento autorizado e registrou resultado válido | **Encerrar** a condição pendente conforme validação oficial |
| Resposta do cliente | Cliente enviou resposta/mensagem de canal autorizado | **Não encerrar por si só** sem atendimento real registrado |
| Nota administrativa | Anotação/edição interna sem prova de atendimento | **Não encerrar** o primeiro atendimento pendente |

A tabela acima **não autoriza criar novos endpoints, tabelas, enum de banco, automações ou canais**. Registro estruturado e validações dependem de CORE/DAT/ATD/SEG e direitos do Básico. A confirmação de uma ligação atendida exige critério de prova e resultado que serão homologados; não basta selecionar qualquer etiqueta “Atendido” sem controles.

## 3. Pendências preservadas

- **LIB04-P02 parcialmente resolvida**: ligação não atendida é tentativa, não atendimento. Ainda falta definir a classificação exata de interações válidas (ex.: contato telefônico atendido, atendimento presencial, conversa no canal contratado, marcação manual e prova) e o controle de tentativas.
- **LIB04-P04 parcialmente resolvida por decisão posterior:** o prazo padrão de **24 horas corridas** e opções **4/12/24/48/72 horas corridas** já foram **aprovados funcionalmente** na [CRM-DEC abrangente](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md). **Continuam pendentes** relógio técnico, fuso, mudança de prazo durante execução, capacidade, retenção, exceções e homologação individual dos modelos.
- **LIB04-P03/P05..P09** continuam sujeitos a aceite, assim como a homologação individual LEA-01/LEA-02, seus gatilhos T01/T13, comando A03, retenção das notificações, contratos e testes.
- **AUT-P01..P14, LIB02-P01..P08, CORE/SEG/INT e gates P0** permanecem abertos.
- Não foi autorizada programação, testes reais, provisionamento, contratação, custo, envio de mensagens, ativação de automação ou mudança de direitos comerciais.

## 4. Referências

- [CRM-DEC — semântica de LEA-02 aprovada](CRM-DEC-semantica-LEA02-primeiro-atendimento-2026-11-04.md)
- [AUT-LIB04 — Interações e prazos](CRM-AUT-LIB04-criterios-interacao-prazos-antiduplicidade-LEA01-LEA02-v0.1.md)
- [CRM-DEC — TAR-A avisos internos](CRM-DEC-escolha-TAR-A-avisos-internos-LEA01-LEA02-2026-11-04.md)
- [CRM-REV-03 — Leads, contatos e histórico](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md)

**Somente documentação; sem programação ou implantação.**


## 5. Conciliação posterior com a aprovação funcional abrangente

A [CRM-DEC de regras funcionais LEA-01/LEA-02](CRM-DEC-aprovacao-regras-funcionais-LEA01-LEA02-2026-11-04.md) já aprovou **24h corridas como padrão**, opções guiadas **4/12/24/48/72h**, os avisos internos por ciclo e a distinção de tentativa sem atendimento. A [LIB05](CRM-AUT-LIB05-registro-classificacao-contato-evidencias-LEA02-v0.1.md) documenta a **proposta não aprovada** de como registrar, validar e corrigir os fatos em histórico existente. Esta conciliação não amplia direitos, não homologa contratos nem autoriza programação.
