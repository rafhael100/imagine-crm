# CRM-DEC — Vigência dos adicionais gratuitos e concessão de créditos de IA por pacote

**Data:** 2026-11-03.
**Status:** **DECISÃO FUNCIONAL APROVADA** para as modalidades de gratuidade de usuários extras, armazenamento adicional e formato de concessão de créditos de IA.
**Manifestação literal:** “ok”.
**Contexto exato da decisão:** concordância com a recomendação de permitir **usuários extras e armazenamento adicional gratuitos com prazo determinado ou indeterminado** e conceder **créditos de IA em pacotes com quantidade definida**, respeitando a política própria de validade ainda não encerrada.

## 1. Regras funcionalmente aprovadas

| Tipo de adicional | Formato da concessão gratuita aprovado | Limite de interpretação |
|---|---|---|
| **Usuários adicionais** | O Administrador Geral Imagine autorizado poderá conceder uma quantidade **especificada** de usuários extras para uma organização, gratuitamente, por **prazo determinado** ou **indeterminado** | Não é liberação ilimitada de usuários; quantidade, critérios de seleção, suspensão/desativação ao final e compatibilidade com o plano ainda precisam de especificação |
| **Armazenamento adicional** | O Administrador Geral Imagine autorizado poderá conceder capacidade adicional **especificada**, gratuitamente, por **prazo determinado** ou **indeterminado** | Não é armazenamento ilimitado; unidades/franquias, redução de capacidade e conservação dos dados existentes devem respeitar regras de anexos já aprovadas |
| **Créditos adicionais de IA** | O Administrador Geral Imagine autorizado poderá conceder **um pacote com quantidade definida de créditos**, sem cobrança daquele pacote, à organização específica | Não é crédito ilimitado, nem concessão recorrente presumida; **validade, expiração, uso, consumos e regras de pacotes** obedecerão à política de IA que ainda precisa ser reconciliada e aprovada |

**Cada concessão é independente da mensalidade do plano e das demais concessões.** A gratuidade de um adicional não concede automaticamente outro; pode existir em conta de plano pago ou plano administrativamente gratuito, conforme elegibilidade e direitos efetivos.

**Sem cobrança automática:** término de vigência ou encerramento de qualquer gratuidade não converte o adicional em assinatura/compra paga sem contratação/aceite expresso. Para usuários e armazenamento, a transição deverá preservar históricos, dados e regras de quota/retenção; seu tratamento operacional ainda não está decidido.

## 2. Limites NÃO aprovados por este “ok”

- Nenhum prazo **padrão**, duração máxima, número de usuários, franquia em GB ou volume padrão de créditos foi fixado.
- Nenhuma regra de **acumulação**, multiplicação de pacotes, concessão recorrente, início futuro, pró-rata, estorno, migração entre tenants, transferência entre clientes ou ampliação para serviços de terceiros foi aprovada.
- As duas modalidades de vigência para usuários extras e armazenamento **não** são extensão automática a pacotes de IA. Créditos de IA seguem **seu próprio contrato de validade e consumo**; a divergência entre “até consumo” em fonte antiga e **90 dias** em decisão posterior ainda exige conferência literal e fechamento.
- Não foi homologado processo de conversão de benefícios, alçadas/MFA adicionais, eventos, schemas, APIs, entidade de entitlement, auditoria física ou regra fiscal.
- O Administrador da Empresa beneficiária não recebe o poder de conceder gratuidade a si próprio; ato permanece restrito à administração geral Imagine autorizada.

## 3. Perguntas de fechamento que permanecem abertas

| ID de pendência | Aspecto | Situação após aprovação |
|---|---|---|
| **GRAT-EX-P02 — PARCIAL** | Vigência de usuário e armazenamento | **Duas modalidades aprovadas**; detalhes de início, data final, renovação, avisos e convivência com contrato permanecem abertos |
| **GRAT-EX-P02-IA — PARCIAL** | Pacotes de IA | **Quantidade definida aprovada**; não decidir por esta aprovação o prazo/validade, expiração, ordem de consumo ou recarga |
| **GRAT-EX-P03 — PARCIAL** | Quantidade e acumulação | Quantidade deve ser **especificada em cada concessão**; limites máximos, acumulação de concessões e efeitos de pacote pago ainda em aberto |
| **GRAT-EX-P05 — PARCIAL** | Encerramento dos adicionais | Vedada cobrança automática; faltam avisos, desligamento de usuários extras, comportamento da quota e acesso aos arquivos acima da capacidade |
| **GRAT-EX-P01/P04/P06/P07/P08** | Catálogo, faturamento, segurança, elegibilidade e precificação | Permanecem abertos, sem decisão implícita |

Não converter um status “parcial” em fechamento documental completo; reconciliar no checklist de contratos FIN/CON, segurança, armazenamento e IA comercial.

## 4. Casos futuros de validação (NÃO executados)

1. Usuários extras gratuitos por três meses: ao final, não cobrar nem excluir informações; aplicar política de capacidade/usuários ainda a detalhar.
2. Armazenamento adicional gratuito por prazo indeterminado: permanecer até encerramento autorizado, sem supor expiração anual.
3. Capacidade concedida vence com arquivos acima da franquia-base: preservar arquivos existentes e bloquear **somente novos uploads que excedam a capacidade vigente**, conforme regras de armazenamento aprovadas.
4. Pacote gratuito de IA com número exato de créditos: saldo e uso idempotentes, sem gerar crédito adicional infinito, recarga ou cobrança.
5. Pacote gratuito de IA em disputa de prazo: aplicar apenas regra de validade aprovada após cotejo do contrato, **não** inventar novo vencimento.
6. Plano gratuito + usuário extra gratuito + pacote IA gratuito: manter três entitlements distintos, escopados à mesma organização, sem cobrança automática ao terminar qualquer um.

## 5. Proveniência e próximos documentos

- [Gratuidade administrativa de qualquer plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md): modalidade do **plano base**.
- [Modalidades de vigência do plano](CRM-DEC-gratuidade-vigencia-modalidades.md): determinado/indeterminado para plano base, já aprovado antes.
- [Gratuidade de adicionais específicos](CRM-DEC-gratuidade-administrativa-adicionais-especificos.md): concessão separada e exemplos de extras, já aprovados antes.
- [CRM-REV-02 — Planos e faturamento](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): entitlements, autorização de cobrança e transições.
- [CRM-REV-03 — Dados e armazenamento](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md): preservação de arquivos, quotas e portabilidade.
- [CRM-REV-08 — IA e consumo](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md): **continua minuta para revisão**, não aprovada por este “ok”.

**Regra global preservada:** este registro é apenas planejamento. Não executar programação, testes, pagamentos, provisionamento de concessões reais nem implementação até aprovação final do planejamento completo no GitHub.
