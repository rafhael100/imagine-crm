# CRM-DEC — Aprovação expressa do Modelo A de reserva de assentos (CORE07-D05)

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação do responsável:** **“aprovado”** em resposta à pergunta explícita “Você aprova o Modelo A como diretriz documental preliminar para o Imagine CRM?”, após comparação das alternativas na [proposta CORE07-D05](CRM-CORE-P0-07-D05-reserva-assentos-convites-modelos-proposta-v0.1.md).  
**Resultado:** **MODELO A APROVADO EXPRESSAMENTE COMO DIRETRIZ DOCUMENTAL PRELIMINAR**. **CORE07-D05 integral permanece ABERTA** até contratos reais, concorrência, exceções, quotas finais, evidências e homologação.

## 1. Decisão e invariantes ratificados

1. **Reserva enquanto convite estiver válido e pendente:** emitir convite autorizado para usuário humano elegível reserva **uma vaga disponível da organização/tenant**, sem criar membership operacional, conceder acesso a dados ou faturar valores adicionais. A reserva é associada ao convite e ao tenant, sem duplicidade para o mesmo destinatário/vínculo.
2. **Prazo vigente de 7 dias:** a reserva dura, no máximo, enquanto o convite válido permanecer pendente, respeitando a validade do convite **de sete dias**. Cancelamento, recusa ou expiração liberam a reserva de forma segura, sem cobrança. Reenvio e eventual reemissão de token/novo prazo são assunto específico de **CORE07-D04**, sem extensão automática por esta aprovação.
3. **Aceitação atômica e autorizada:** ao aceitar, comprovar identidade e titularidade da conta, token/convite vigente, tenant, papel e competência legítimos, MFA exigível, status do plano, quota e ausência de duplicação; **converter a reserva em assento ocupado exatamente uma vez**, sem duas memberships ou oversubscription em concorrência.
4. **Transparência e gestão:** na interface futura distinguir vagas **ocupadas, reservadas e disponíveis**, permitindo a administrador com competência cancelar convite e liberar reserva. Convite não dá privilégio, não eleva papel nem cria benefício.
5. **Básico e demais planos:** preservar o **Básico com um único administrador humano autenticado com MFA**; se 1/1 ocupado, proibir segundo login e reserva adicional. Trial tem 2 usuários em sua quota própria; valores, assentos dos planos pagos e adicionais permanecem para revisão final. Profissional agendável e identidade técnica não são vagas humanas por analogia.
6. **Sem aquisição automática:** não comprar adicional, alterar assinatura, emitir cobrança, fazer upgrade/downgrade ou prorrogar trial automaticamente por emissão, expiração ou aceite de convite. Direitos da assinatura CRM são independentes dos contratos de Sites.
7. **Concorrência, revalidação e auditoria:** emissão/aceitação/cancelamento/expiração/alteração de quota devem ser idempotentes e impedir reserva dupla ou uso de vaga revogada; registrar eventos necessários sem guardar tokens em claro ou vazar dados pessoais. Regras físicas, casos de plano suspenso e reemissão são pendências.

## 2. Pendências preservadas

- **CORE07-D05 integral permanece ABERTA**: esquema e transações reais, estados e contagem por quota, disputa da última vaga, reservas durante downgrade, reativação de membership, convites duplicados, rastreabilidade, testes executados e homologação.
- **CORE07-D04/D06..D10** e **CORE02-D04..D10** permanecem abertos, assim como os demais 67 identificadores CORE, SEG-01/02, INT P0/G01..G06, FIN/CON, LGPD e gate global.
- **Não** autoriza programação adicional do Codex, testes executáveis, SQL/grants, criação de convites reais, preços ou quotas comerciais novas, merge, contratação, VPS, implantação ou produção. A branch `dev/fast-track-crm-2026-11-04` e a `main` não são alteradas por esta decisão.

## 3. Próxima pauta

Elaborar **CORE07-D04**: reenvio/reemissão de convite, revogação do token anterior, validade de sete dias e vínculo com reserva de vaga, para deliberação futura expressa. Não presumir aprovação antecipada.
