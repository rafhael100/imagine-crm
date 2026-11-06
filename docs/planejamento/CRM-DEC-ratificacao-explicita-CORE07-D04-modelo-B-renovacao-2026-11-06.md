# CRM-DEC — Ratificação expressa CORE07-D04: Modelo B de renovação de convites

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação do responsável:** **“sim”** à pergunta “Você aprova o Modelo B como diretriz documental preliminar?”, imediatamente após comparação dos modelos A/B/C e indicação de [CORE07-D04 — reenvio, reemissão e expiração de convites](CRM-CORE-P0-07-D04-reenvio-renovacao-convites-proposta-v0.1.md).  
**Resultado:** **MODELO B RATIFICADO EXPRESSAMENTE SOMENTE COMO DIRETRIZ DOCUMENTAL PRELIMINAR**. **CORE07-D04 integral permanece ABERTA** para especificação de tokens, limites antispam, transições e homologação. O aceite de B não equivale à ratificação integral e independente de todos os itens candidatos D04-A/B/C/D.

## 1. Política aprovada

1. **Renovação manual autorizada:** quando o administrador competente solicitar **expressamente a renovação**, o convite anterior e seus links de aceite são revogados e é gerado **novo convite válido por sete dias**, sem reativação implícita nem sobreposição de links operacionais.
2. **Reserva única:** a renovação mantém **no máximo uma vaga reservada** para a intenção de convite dentro do tenant, **apenas se permanecer válida e elegível** conforme quota/plano/estado e competência do administrador. O convite ainda não gera acesso operacional, nem fatura, adicional ou upgrade.
3. **Revogação de link antigo:** depois da renovação, token/link anterior é inválido; aceitar convite exige identidade legítima, token atual, vaga disponível/reservada válida, MFA aplicável, autorização e validação do backend. E-mail enviado ou confirmação HTTP não criam membership por si.
4. **Sem renovação automática:** expiração, reenvio de mensagem ou falha de entrega não prorrogam silenciosamente validade de sete dias nem preservam vaga após perda da elegibilidade. A interface deve comunicar de forma clara quando o ato **renova o prazo** e invalida o link anterior.
5. **Proteções pendentes:** limite de repetição, antispam, prevenção de reservas indefinidas por renovações sucessivas, distinção concreta entre `reenviar aviso` e `renovar convite`, tratamento de convite já expirado/cancelado, idempotência, concorrência, recuperação e auditoria devem ser especificados e aprovados separadamente.
6. **Sem mistura de contextos:** o modelo não permite múltiplas memberships por mesmo convite, extrapolação de assentos, segundo login no Básico, acesso cruzado entre tenants, alteração de plano ou concessão de permissões por analogia.

## 2. Pendências e limites de efeito

- **CORE07-D04 e D05 permanecem integralmente ABERTAS** apesar das escolhas documentais dos Modelos B e A, respectivamente. CORE07-D06..D10 e demais dependências CORE02, CORE04, CORE08, FIN/CON, SEG-01/02, INT/G01..G06, LGPD e gates globais seguem pendentes.
- Não homologar implementação de token, TTL técnico, ciclo máximo de renovações, políticas de abuso, preço de assentos ou automatismos desta ratificação.
- **Não** autoriza novas tarefas de programação no Codex, testes de homologação, convites reais, concessões de acesso, criação de contas, alteração de código/SQL, merge, infraestrutura, VPS, produção ou contratação. Preservar a branch `dev/fast-track-crm-2026-11-04` e `main`.

**Próxima pauta sugerida:** decisão sobre convite expirado/cancelado e prevenção de retenção indefinida de vagas. Essa pauta ainda não está aprovada.
