# IN-144 — Matriz de Criticidade e Metas de Recuperação da Imagine

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (resposta "ok").  
**Fase:** planejamento e documentação; **não iniciar programação, provisionamento, migração ou contratação**.  
**Escopo:** Imagine Sites, infraestrutura e APIs de formulários, armazenamento/filas de leads, CRM Imagine SaaS, automações n8n e Portal Imagine administrativo.  
**Referências:** IN-101 (backup dos sites), IN-124 (recepção de leads e APIs principal/contingência), IN-126 a IN-129 (recuperação de leads), IN-130 a IN-142 (incidentes, autonomia, alertas, prevenção e capacidade) e IN-143 (continuidade operacional e recuperação de desastres).

## Objetivo

Estabelecer a classificação inicial de criticidade **dos serviços para fins de continuidade e ordem de recuperação**, e definir como serão posteriormente dimensionadas as metas RTO e RPO por serviço. Priorizar a preservação dos envios de leads já confirmados e evitar que uma dependência compartilhada seja negligenciada.

## Matriz de criticidade de recuperação — aprovada

| Serviço | Prioridade inicial de recuperação | Observações |
|---|---|---|
| **APIs de recebimento de leads** | **Crítica** | Preservar a possibilidade de aceitar e persistir submissões. Principal e contingência devem ser independentes, conforme IN-124. |
| **Filas de armazenamento e entrega de leads** | **Crítica** | Preservar registros já recebidos, integridade, reconciliação e processamento idempotente, sem exclusão silenciosa. |
| **CRM Imagine SaaS** | **Alta** | Recuperação e backups independentes da política de sites. Elevá-la conforme impacto real e serviços afetados. |
| **Sites dos clientes Imagine** | **Alta** | Considerar disponibilidade dos sites, domínio e SSL, formulários e exposição de clientes; componentes essenciais à recepção de leads podem exigir tratamento crítico. |
| **Automações n8n** | **Alta, conforme o serviço afetado** | Priorizar fluxos que sustentem APIs, filas, alertas ou outros serviços críticos, independentemente da classificação nominal. |
| **Portal administrativo Imagine** | **Média** | Preservar recepção/entrega de leads e emissão de alertas críticos por infraestrutura independente do painel; elevar prioridade se uma falha afetar função crítica. |

**Importante:** essa classificação é uma **base de planejamento**, não uma regra rígida que ignore impacto real ou dependências. A prioridade de recuperação de um serviço não é necessariamente igual à prioridade de um incidente técnico na IN-133.

## Metas de recuperação: conceitos aprovados; números ainda pendentes

- **RTO (Recovery Time Objective):** objetivo de prazo para recuperar um serviço após indisponibilidade. Não é um tempo de reparo garantido nem o SLA de um contrato por si só.
- **RPO (Recovery Point Objective):** objetivo para o ponto mais recente dos dados que deve ser recuperável após falha, expresso como janela temporal tolerável de perda de dados.
- **RTO e RPO serão definidos por serviço**, com valores concretos, arquitetura adequada, custos, testes e aprovação em etapa posterior.
- Diferenciar os objetivos por **site, API principal/contingência, fila, banco de dados CRM, automações n8n e Portal**. Nem todo serviço precisa da mesma meta.
- Nos fluxos de leads, **preservar todos os envios cuja recepção durável foi confirmada** é o requisito funcional; tal requisito não significa que já exista infraestrutura capaz de assegurar perda zero em qualquer desastre. Antes de prometer RPO zero, projetar, validar e testar os mecanismos necessários.
- Se as duas APIs receptoras falharem, **não informar sucesso ao visitante** sem confirmação de gravação durável. Aplicar as estratégias de recuperação e aviso já aprovadas.
- Não presumir recuperação instantânea nem ausência absoluta de perda de dados; metas técnicas e comerciais somente após validação operacional.

## Regras de ordenação e dependências — aprovadas

1. **Inventariar dependências técnicas** entre serviços e recursos (rede, DNS, certificados, máquinas, banco, persistência das filas, APIs, n8n, monitoramento, serviços externos e mecanismos de alerta).
2. **Recuperar primeiro capacidades essenciais à função crítica**, mesmo que sejam componentes de um serviço classificado nominalmente como "alto" ou "médio". Exemplo: se um worker n8n for condição necessária para a entrega de leads, seu restabelecimento recebe prioridade compatível.
3. **Coordenar ações com os agentes especializados** e com o Supervisor Técnico, preservando um responsável principal pelo incidente e o registro do encadeamento da recuperação.
4. **Diagnosticar e selecionar procedimentos homologados**, executar automaticamente apenas ações seguras e previamente autorizadas; intervenção crítica ou destrutiva depende de **uma aprovação humana**, sem dupla aprovação, conforme IN-137/IN-138.
5. **Validar o retorno por testes de integridade e funcionalidade**, e não por simples execução de comando ou indicador isolado.
6. **Preservar idempotência e reconciliação** dos envios pendentes; retomada da fila deve evitar tanto perda quanto duplicação de leads.
7. **Documentar alterações de prioridade por impacto real**, incluindo indisponibilidade simultânea, clientes atingidos e efeito cascata sobre serviços críticos.
8. **Manter isolamento entre clientes e entre políticas de backup**: backups dos sites (IN-101) são independentes dos backups e políticas de dados do CRM Imagine.

## Exemplo de recuperação de dependências

Falha conjunta do processamento das filas e de um workflow n8n que entrega leads ao CRM: o serviço n8n tem classificação inicial alta, mas o **fluxo específico responsável pela entrega** deve ser tratado como parte da recuperação de um processo crítico. Verificar primeiro armazenamento íntegro da fila, retomar workers com segurança e reconciliar IDs antes de confirmar entrega.

## Informações necessárias para definir RTO/RPO na etapa seguinte

- Arquitetura real de cada serviço, dependências e limites de provedores.
- Volume, idade máxima e durabilidade dos envios e dados, estimativas de tráfego e carga.
- Backups, replicação, redundância e testes de restauração disponíveis ou projetados.
- Custos incrementais das opções de redundância e recuperação.
- Consequências operacionais e financeiras de interrupção/indisponibilidade por componente.
- Cobertura humana real de plantão (IN-136), meios de alerta, responsabilidades e restrições de acesso.
- Objetivos de teste, auditoria e revisão periódica da matriz.

## Pendências expressas

- **Nenhum valor numérico de RTO ou RPO foi aprovado nesta IN.**
- Prioridades dentro de subcomponentes, tempos de recuperação e planos de failback serão dimensionados por serviço.
- Não autorizar gastos automáticos nem elevação unilateral de permissões por agentes.
- Não tratar a futura definição de RPO como autorização para apagar leads efetivamente entregues ao CRM nem alterar a política de **3+15 dias** para envios não entregues; o descarte desses payloads após completar 18 dias foi **aprovado posteriormente na IN-147**, com auditoria e salvaguardas.

**Aprovado:** prioridades iniciais crítica/alta/média da matriz, priorização real por dependências e impacto, conceitos de RTO/RPO e a exigência de dimensioná-los futuramente, preservando dados de leads confirmados e recuperação verificável.  
**Pendente:** metas numéricas, custos, arquitetura detalhada, testes e plano operacional de recuperação.  
**Programação:** não iniciada.


## Complemento posterior — IN-147 (01/11/2026)

O descarte de envios **não entregues** após o prazo de **3 dias na fila normal + 15 dias na fila de falhas** está autorizado pela [IN-147](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md). Preservam-se os avisos 72h antes, alerta crítico no vencimento, verificação determinística antes de excluir, auditoria minimizada por 90 dias e exceções legais. Não criar quarentena operacional adicional. Esta atualização prevalece sobre qualquer indicação histórica de que o descarte final permanecia sem aprovação; **não se aplica a leads confirmados no CRM**.
