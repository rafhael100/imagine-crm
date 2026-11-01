# IN-122 — Múltiplos Sites, Contas CRM e Cortesias

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026.  
**Fase:** planejamento/documentação; **não autoriza programação**.  
**Integrações:** Imagine Sites/landing pages, contratos elegíveis, CRM Imagine, IN-118, IN-119, IN-120, IN-121.

## Objetivo

Permitir que clientes com múltiplos sites ou landing pages elegíveis tenham contas CRM distintas por projeto ou centralizem vários projetos na mesma conta, com distribuição correta da cortesia do plano Básico e sem duplicidade de benefícios na mesma conta.

## Regras aprovadas

1. Cada contrato de site/landing page **elegível** pode sustentar a cortesia do CRM Básico para **uma conta CRM vinculada**, de acordo com suas condições contratuais.
2. **Uma conta CRM recebe no máximo uma cortesia do Básico**, ainda que tenha dois ou mais sites elegíveis vinculados; cortesias não são acumuláveis e não geram crédito.
3. Um cliente com múltiplos sites elegíveis pode vincular cada site a uma conta CRM distinta; cada conta poderá receber sua própria cortesia, desde que exista ao menos um site elegível vinculado a ela.
4. Na contratação de outro site ou landing page, o cliente decide se deseja **uma nova conta CRM** para o projeto ou integrar o projeto a **conta CRM já existente**, de sua titularidade ou sob autorização apropriada.
5. Para **alterar posteriormente a conta CRM vinculada** a um site, o cliente deverá abrir um **chamado**. A Imagine realizará **gratuitamente apenas a desvinculação e a nova integração técnica**, validando o recebimento dos novos leads.
6. **Migração de dados entre contas não está incluída** na alteração gratuita: o próprio cliente utiliza as ferramentas de backup, exportação e importação do CRM (IN-118), respeitando permissões e compatibilidade.
7. Alterações de vínculo e cancelamentos acionam a **reavaliação automática de elegibilidade de todas as contas afetadas**, considerando os outros sites ativos elegíveis e evitando cancelamento ou benefício indevido.
8. A perda do último site elegível de determinada conta aciona as políticas de **30 dias de transição, notificações, suspensão e reativação** das IN-119 a IN-121. Se outro site elegível permanecer naquela conta, a cortesia continua sem interrupção.
9. **Preço comercial de referência do CRM Básico:** R$ 69,00/mês, para contratação paga quando não houver cortesia aplicável; valores gerais serão revisados posteriormente.
10. Registrar de modo auditável cada vínculo, alteração, justificativa de elegibilidade, concessão e encerramento de cortesia, com controles de autorização e segregação por empresa/conta.

## Exemplos aprovados

| Configuração | Número de cortesias |
|---|---:|
| 1 site elegível → 1 conta CRM | 1 |
| 2 sites elegíveis → a mesma conta CRM | 1 |
| 2 sites elegíveis → 2 contas CRM distintas | 2, uma por conta |
| 3 sites elegíveis → 2 contas CRM distintas | Até 2, uma por conta com site elegível |
| 1 site tem seu vínculo trocado para outra conta | Reavaliar as duas contas e seus demais vínculos |

**Observação:** o número máximo de cortesias efetivas é limitado simultaneamente pelos sites elegíveis e pelas contas distintas que tenham vínculo elegível; não significa cortesia irrestrita em contas sem site.

## Requisitos de consistência para detalhamento técnico

- Registrar identidade persistente do site/landing page, da conta CRM e do contrato que dá elegibilidade; não basear vínculos apenas em domínio ou e-mail.
- Garantir autorização do titular/administrador de ambas as contas em alteração de vínculo.
- Troca de vínculo deve ser transacional ou contar com compensação e testes de integração; novos leads devem chegar à conta correta após ativação.
- Contas e dados prévios permanecem segregados, sem cópia de históricos automaticamente.
- Tratar alteração de vínculo, cancelamento, reativação e disputa de titularidade sem duplicar benefícios.
- Respeitar a situação de cada assinatura e políticas de armazenamento, retenção e portabilidade já aprovadas.
- Detalhar os critérios de elegibilidade, antifraude, limites de contratação e eventuais restrições de plano antes de qualquer implementação.

**Aprovado:** regra de uma cortesia por conta com ao menos um site elegível; várias contas podem ser cortesia se cada uma estiver vinculada a site elegível; reavaliação automática dos vínculos.  
**Pendente:** detalhes operacionais de autorização e consistência.  
**Programação:** não iniciada.
