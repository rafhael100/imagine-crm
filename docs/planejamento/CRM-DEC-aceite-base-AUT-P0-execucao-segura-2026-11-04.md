# CRM-DEC — Aceite da base de execução segura do motor próprio (AUT-P0)

**Data:** 04/11/2026.
**Manifestação:** o responsável respondeu “ok” durante a apresentação do avanço de planejamento do motor próprio, com responsabilidades dos motores centrais e quatro proteções fundamentais.
**Escopo do aceite:** **diretrizes apresentadas e continuidade do detalhamento como base documental**, não ratificação individual de todos os contratos da minuta AUT-P0 v0.1.

## Diretrizes aceitas

- **Motor próprio do CRM** para automações configuradas por clientes, preservando a decisão anterior [CRM-DEC](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md); o n8n não é o motor obrigatório dessas automações.
- **Reutilizar os componentes centrais já planejados**: Eventos, Fluxos, Condições, Ações, Filas, Datas/Prazos, Aprovações e Auditoria Geral, com módulos de negócio como fontes oficiais dos efeitos.
- **Separação entre atividades obrigatórias do sistema e automações comerciais opcionais**: captação de leads, segurança, confirmação da Agenda e demais atividades oficiais não podem ficar bloqueadas por ausência ou esgotamento de slots comerciais.
- Proteger a execução com **isolamento por empresa**, **prevenção de duplicidade**, **confirmação oficial dos resultados** e **recuperação controlada de falhas com histórico e rastreabilidade**.
- A **hipótese de usar workers do Laravel/filas duráveis na infraestrutura do CRM E1B inicialmente** pode ser estudada para economizar uma VPS adicional, **desde que capacidade, resiliência e isolamento sejam comprovados na futura homologação**. Não há aprovação física de implantação ou da escolha definitiva da tecnologia de fila.

## O que permanece em revisão

A [minuta técnica AUT-P0 v0.1](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md) detalha versões, esquemas de eventos, outbox/inbox, deduplicação, concorrência, recuperação e 18 cenários de homologação. **Esses detalhes continuam propostas técnicas, não foram ratificados individualmente pelo “ok”**.

Permanecem **AUT-P01..14**, contratos CORE/INT, catálogo e schemas definitivos por módulo, números de concorrência, retentativas, prazos e retenções, quotas e direitos comerciais finais, versões/compatibilidade, segurança/segredos, monitoramento e comprovação de recuperação. Não copiar a fila de 18 dias de leads como prazo de automações nem prometer *exactly-once* distribuído.

## Restrição de execução

**Não iniciar programação, criar recursos, configurar serviços, contratar infraestrutura/licenças, testar em produção ou efetuar gastos.** O planejamento completo deve ser concluído, registrado e aprovado globalmente antes do desenvolvimento. ARQ-01, CORE/INT e demais gates P0 continuam abertos.
