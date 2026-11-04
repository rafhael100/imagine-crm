# CRM-CORE-P0 — Etapa 2 / CORE01-D06: ciclo de organizações, titularidade e vínculos comerciais Sites↔CRM (proposta canônica v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE01-D06-A/B/C/D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**, conforme [aceite literal](CRM-DEC-aprovacao-CORE01-D06-ABCD-diretrizes-preliminares-2026-11-04.md). **CORE01-D06 PERMANECE ABERTA** (assim como D04/D05/D07, SEG-01/02, INT, G01–G06 e demais bloqueios). Passagens históricas abaixo que tratam A/B/C/D como “não aprovadas” antecedem o aceite e são superadas apenas no escopo preliminar.  
**Natureza:** única pauta canônica para deliberação de CORE01-D06. [Anexo de exemplos, riscos e critérios ](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-tenants-vinculos-comerciais-proposta-v0.1.md) para leitura complementar sem votos independentes.  
**Autorização:** apenas continuidade do planejamento após [aceite preliminar CORE01-D05-A/B/C/D](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md). **CORE01-D04 e D05 permanecem abertas**; CORE01-D07, SEG-01/02 e demais bloqueios igualmente abertos.  
**Vedado:** programação, Codex, scripts, SQL, execução de testes, uso ou criação de tenants/planos/benefícios reais, emissão de cobranças/avisos, migração de dados, provisionamento, contratos ou despesas.

## 1. Referências, limites de autoridade e normas aprovadas

- [CORE01-D01..D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade global, um tenant lógico por organização CRM, mesmo CNPJ podendo ter várias organizações independentes, RLS como segunda camada de isolamento.
- [CORE01-D04-A/B/C](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md): uma membership lógica por identidade×tenant, convite como intenção não vinculante, revogação por organização. D04 ainda não encerrada.
- [CORE01-D05-A/B/C/D](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md): contexto validado por operação, isolamento transacional, serviços/mandatos sem BYPASSRLS, revalidação e resultado do proprietário. D05 ainda não encerrada.
- [CORE08-D01..D03](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md), [CRM-REV02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), IN-119..122, PORTAL-COM-08/09: plano Básico público distinto de cortesia, trial único de 14 dias por CNPJ, assinatura CRM paga independente de Sites e gratuidade administrativa independente. **Uma cortesia efetiva no máximo por tenant**, desde que exista contrato Site elegível.
- [IN-127-R01..R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): páginas estáticas sem CRM podem publicar com aceite, mas **não podem captar formulário sem destino CRM oficial e autorizado**; T0 é recepção durável, prazo de 72h+360h=432h sem relógio reiniciado, e nenhuma troca de vinculação deve redirecionar leads já recebidos sem autorização própria. Essas regras específicas prevalecem sobre exemplos históricos incompatíveis.
- [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): evento técnico, webhook recebido, HTTP 2xx ou fluxo n8n finalizado não comprovam resultado financeiro ou contratual; cada módulo dono confirma o fato.
- [IN-118](IN-118-central-backup-portabilidade-dados.md): migração entre contas CRM é responsabilidade do cliente via exportação/importação; a nova integração técnica mediante chamado é gratuita. Sem cópia de histórico automática.
- [CORE06](CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md): reter trial expirado por 60 dias conforme política aplicável; retenção geral de CRM pós-cancelamento formal por 120 dias, não a partir de cancelamento de Site ou mera suspensão. Privacidade e exceções ainda precisam de detalhamento.

**Não confundir:** CNPJ, representante, identidade global, tenant, membership, vínculo Site↔CRM, contrato Site, assinatura CRM paga, gratuidade administrativa, benefício de cortesia e trial são objetos de competência diferentes. Nenhum automaticamente concede acesso, benefício, cobrança, desconto ou titularidade do outro.

## 2. Fontes oficiais e vinculação explícita

| Objeto/fato | Quem deve confirmar o resultado oficial | Invariante |
|---|---|---|
| CNPJ, titularidade e representação | Autoridade cadastral/contratual pertinente | Mesmo e-mail ou CNPJ não dá direito a outros tenants |
| Organização CRM e membership | CRM/Identidade e Segurança | Um tenant lógico isolado, papéis/vagas próprios |
| Site, contrato, campanha e elegibilidade | Portal Sites e proprietário contratual/FIN | Site só concede benefício previsto nas condições válidas |
| Vinculação Site↔CRM | Portal e CRM com confirmação bilateral e cliente autorizado | Escolha explícita de organização existente ou nova; mudança via chamado, sem migração automática |
| Plano/assinatura CRM paga | FIN/CON CRM e confirmação financeira oficial | Dívida/cancelamento do Site não bloqueia serviço CRM pago válido |
| Benefício cortesia Básico | Entitlement do CRM após prova de Site elegível | Um benefício por tenant, sem cumular franquias por vários Sites |
| Trial | CRM FIN/CON e histórico de CNPJ | Um trial por CNPJ, não renovado por Sites/tenants adicionais |
| Gratuidade administrativa | Admin Imagine competente e FIN/CON | Independente do Site; extras separados e alçada/precedência pendentes |
| Formulário/lead CRM | Backend de recepção e confirmação do CRM destino autorizado | Sem destino autorizado, captação bloqueada no backend, inclusive cache |
| Auditoria/retencão | Auditoria Geral e proprietários | Preservar causalidade, marcos, direitos de portabilidade e prazos distintos |

**Titularidade documental, comprovação do representante, versões, contratos e alçadas ainda não estão homologados**. Esses domínios não são contas técnicas novas nem serviços adicionais automaticamente autorizados.

## 3. CORE01-D06-A — Objetos e fontes oficiais independentes (PROPOSTA)

Aceitar como **diretriz preliminar** que criação da organização CRM, verificação de representante, vínculo Site↔CRM, contrato elegível, assinatura paga, benefício e trial exigem fatos oficiais separados, direitos e estados próprios. Cliente escolhe expressamente destino (tenant existente autorizado ou tenant novo segregado); nenhuma escolha por silêncio, e-mail, CNPJ, simples pagamento ou trigger duplicado.

**Não aprova:** entidade física, esquema, limites por CNPJ, prova exata de titularidade, poderes administrativos novos, criação automatizada de tenant.

## 4. CORE01-D06-B — Fontes de direito não cumulativas/independentes (PROPOSTA)

Aceitar como **diretriz preliminar**: Básico público pode ser contratado diretamente; cortesia depende do contrato Sites elegível e não acumula direitos pelo número de Sites no mesmo tenant; duas organizações elegíveis sob o mesmo CNPJ podem ter cortesias distintas, sem criar segundo trial. Assinatura CRM paga continua independente, inclusive na inadimplência do Site. Gratuidade administrativa de qualquer plano e extras depende de concessão própria e não é derivada do Site. Fim da cortesia não gera cobrança automática.

**Não aprova:** prioridade exata de múltiplas concessões, descontos, isenção, planos superiores, pacotes, limites finais ou cobrança. Seguem CORE08-D05/D09, GRAT-P, FIN/CON.

## 5. CORE01-D06-C — Estados e marcos separados (PROPOSTA)

Aceitar como **diretriz preliminar** que suspensão de Site, cancelamento Site, estado de tenant CRM, expiração de trial, cancelamento de assinatura paga, concessão administrativa e portabilidade têm **relógios e fatos oficiais independentes**:

| Condição oficial | Consequência funcional já aprovada |
|---|---|
| Último Site elegível cancelado ordinariamente | **30 dias de transição operacional normal**, a partir do cancelamento formal; avisos dias **0, 15, 25, 29 e 30**; sem cobrança automática |
| Último Site único suspenso por dívida após tolerância | Restrição da cortesia se nenhum outro sustentador; **não** iniciar 30 dias só pela suspensão |
| Cancelamento por inadimplência após suspensão | **30 dias de transição restrita** contados do cancelamento formal; não desbloquear operação por cancelar Site |
| Outro Site elegível permanece sustentando tenant | Preservar cortesia sem iniciar transição indevida |
| CRM pago ativo, Site cancelado | CRM pago segue seu contrato e estado próprios |
| Trial expira | Retenção de referência de **60 dias**, sem segundo trial e sem contaminar cortesia |
| CRM formalmente cancelado | Retenção geral de referência **120 dias após o cancelamento CRM**, não após Site ou suspensão |
| Fim de cortesia sem contratação | Suspender operação comercial normal, mas preservar acesso restrito para portabilidade/contratação quando devido |

**Não aprova:** máquinas físicas, timestamps, fuso, regras de reativação/contestação, precedência dos demais benefícios, disparos reais ou mecanismo de expurgo.

## 6. CORE01-D06-D — Idempotência, confirmação e reconciliação (PROPOSTA)

Aceitar como **diretriz preliminar** que os eventos de contrato, concessão de benefício, criação de tenant, consumo de trial, cancelamento, reativação e alteração de destino deverão ser identificados, versionados, idempotentes, auditáveis e reconciliados com **fonte oficial**. Webhook/HTTP 200 e sucesso do agente ou n8n não comprovam negócio; não executar cobranças, segunda cortesia, segundo trial, mudança de tenant ou migração automática por evento repetido/fora de ordem. Em troca de destino, leads pendentes não migram de CRM silenciosamente. O módulo dono confirma resultado legítimo.

**Não aprova:** envelope/schema, chave de idempotência, fila, retry, gestão de locks, prazos de validade, provedor/gateway e contratos INT CT físicos.

## 7. Casos adversos e critérios de homologação futura — NÃO EXECUTADOS

| Risco | Resultado exigível para etapa futura |
|---|---|
| Mesmo CNPJ com tenants A/B e Sites distintos | Isolamento de contas, trial único e cortesia por contrato/tenant elegível |
| Dois Sites apontam a um tenant; um cancela | Uma cortesia; preservá-la enquanto outro sustentador válido existir |
| Suspensão por inadimplência vs cancelamento formal | Relógios distintos, transição restrita somente desde marco formal correto |
| CRM pago e Site inadimplente | Não suspender assinatura CRM paga por evento do Site |
| Troca de vínculo com formulário em cache | Impedir captação ou redirecionamento sem destino oficial autorizado |
| Evento financeiro/contratual duplicado ou tardio | Não cobrar, criar tenant, trial ou benefício duplicados |
| Tentativa de iniciar 120 dias por cancelamento do Site | Negar; prazo da conta CRM só no cancelamento próprio |
| Admin Imagine ou IA tenta alterar plano, tenant ou titularidade sem competência | Negar, registrar tentativa e escalar conforme política |
| Fim de cortesia coincide com gratuidade/pagamento válido | Não revogar direito independente; conciliar precedência CORE08-D05 pendente |
| Histórico de lead de A é direcionado ao tenant B apenas por novo vínculo | Não migrar automaticamente nem revelar registros entre tenants |

Esses roteiros não foram executados, não criam conjunto redundante de testes e precisam ser reconciliados com CORE08-T, CORE01-T, OPS-T, INT HT e CORE10-D06.

## 8. Pendências que NÃO serão encerradas com eventual aceite A–D

- **CORE01-D04/D05/D06/D07** continuam abertos até conciliar CORE02/03/04/05/07/08/09, documentação física, versão, owner e aceites específicos.
- **CORE08-D04..D10**, CRM-REV02-D/P, GRAT-P, FIN/CON, Sites/Portal, IN-117..122, IN-127-R01..R06 (já aprovadas, preservar), contratos INT, retenções, LGPD, limites/preços e titularidade permanecem exigíveis.
- **IN-168 SEG-01 e SEG-02**, ARQ-01/02/03, AGT-01, INT-P0-OWN/SEC/CON/DOC/TR/ACC etc. e **G01..G06** permanecem ABERTOS.
- A matriz mantém **67 IDs técnicos abertos**, sem declarar encerramento de qualquer CORE-P0 por diretrizes parciais.

## 9. Pauta para decisão expressa futura

- **CORE01-D06-A:** fontes oficiais e escolha do tenant autorizada.
- **CORE01-D06-B:** direitos comerciais distintos sem benefício/cobrança indevidos.
- **CORE01-D06-C:** marcos de suspensão/cancelamento/transição/retentão separados.
- **CORE01-D06-D:** confirmação pelo dono, idempotência, reconciliação e auditoria.

**Propostas não aprovadas** até manifestação literal do responsável. Após eventual aceite preliminar, preparar somente a pauta documental **CORE01-D07** (esquemas/constraints/RLS e homologação futura). Nenhum teste/SQL/código/contratação/alteração operacional autorizado.

## 10. Aditamento de aprovação preliminar — 04/11/2026

O responsável aprovou **CORE01-D06-A/B/C/D como diretrizes técnicas documentais preliminares**, segundo [manifestação expressa integral](CRM-DEC-aprovacao-CORE01-D06-ABCD-diretrizes-preliminares-2026-11-04.md), com a classificação adotada neste documento: A fontes oficiais/vínculos explícitos; B direitos comerciais distintos e não cumulativos; C marcos de suspensão/cancelamento/retencão próprios; D idempotência, confirmação e reconciliação oficiais. **D06 não foi encerrada.** CORE01-D04/D05/D07 e os 67 IDs da Etapa 2 permanecem abertos, além de SEG-01/02 e INT/G01–G06. Continuar somente com minuta documental CORE01-D07, sem SQL, testes ou código.
