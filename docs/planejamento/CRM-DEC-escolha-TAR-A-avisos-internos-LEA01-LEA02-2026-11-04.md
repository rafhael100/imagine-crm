# CRM-DEC — Escolha TAR-A: avisos internos para os primeiros modelos do Básico

**Data:** 04/11/2026.
**Manifestação:** “ok” após recomendação expressa da **Alternativa A — avisos internos** para a primeira versão de LEA-01 e LEA-02.
**Estado:** **DIRETRIZ FUNCIONAL TAR-A APROVADA**, limitada ao efeito desejado para estes dois modelos. A ativação de cada modelo e seus contratos técnicos ainda requerem decisões específicas.

## 1. Escolha aprovada
- **LEA-01 (Novo lead):** planejar aviso interno de acompanhamento ao administrador autorizado **somente após confirmação oficial do lead no CRM**.
- **LEA-02 (Lead sem retorno):** planejar aviso interno após **inatividade efetivamente confirmada** por regra do Motor de Datas, evitando repetição no mesmo ciclo.
- A primeira versão do Básico **não criará automaticamente tarefas comerciais por esses dois modelos**. Ações de tarefas básicas ou avançadas (TAR-B/TAR-C) continuam fora deste escopo e exigem aprovação posterior.
- Aceita-se a **categoria funcional de aviso comercial interno para o escopo restrito LEA-01/02**, distinta dos avisos obrigatórios da Agenda (SO). A especificação e autorização executável A03, a política de retenção, a interface, os parâmetros de aviso e os direitos necessários continuam em homologação.
- Os dois modelos, **quando aprovados e ativados individualmente**, deverão utilizar vagas comerciais vigentes da organização, diferentemente dos avisos obrigatórios da Agenda que não consomem slot. O valor-base histórico de três vagas do Básico segue sujeito à revisão comercial final.
- O mesmo conjunto de direitos vale para Básico avulso e Básico concedido como benefício por site; nenhum canal externo, WhatsApp, IA, editor livre, agenda pública ou n8n é concedido.

## 2. O que permanece pendente
- **Aprovação individual LEA-01/LEA-02 como modelos publicáveis**, com gatilhos canônicos, fontes elegíveis (formulário, entrada manual, importação), condições e parâmetros fechados.
- **Definir interação válida e prazo de LEA-02**, mudança de estado, arquivamento/reativação, deduplicação e controle de repetição.
- **Homologar A03** para estes modelos e os contratos do serviço de notificações: destino, persistência, estados, retenção, política de leitura e segurança de dados. Não copiar automaticamente os 90 dias de retenção dos avisos da Agenda.
- Testes futuros de isolamento por tenant, confirmação CRM, reprocessamento, retentativas, suspensão/downgrade, identidade/consentimento e não duplicação.
- Permanecem abertos LIB02-P01..P08 nos aspectos técnicos, AUT-P01..P14, MAT01, CORE/SEG/INT e gates P0.

**Não autoriza programação, publicação, testes reais, contratação, provisionamento, pagamentos nem alteração dos planos.**

## 3. Rastreamento
[LIB02 — Revisão individual e alternativas](CRM-AUT-LIB02-revisao-individual-modelos-basico-v0.1.md) · [LIB01 — Biblioteca base](CRM-AUT-LIB01-biblioteca-modelos-basico-editores-por-plano-v0.1.md) · [CRM-DEC — Prioridade LEA-01/02](CRM-DEC-prioridade-LEA01-LEA02-2026-11-04.md).
