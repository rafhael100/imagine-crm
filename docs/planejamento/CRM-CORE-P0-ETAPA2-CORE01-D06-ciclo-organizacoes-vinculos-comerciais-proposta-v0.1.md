# CRM-CORE-P0 — Etapa 2 / CORE01-D06: ciclo de organizações, titularidade e vínculos comerciais Sites↔CRM (proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA TÉCNICA DOCUMENTAL NÃO APROVADA — CORE01-D06 ABERTA**. CORE01-D04, D05 e D07 permanecem igualmente ABERTAS, assim como CORE08-D04..D10, FIN/CON, SEG-01/SEG-02, INT P0 e todos os gates.  
**Ordem do responsável:** seguir com **planejamento documental CORE01-D06** após aceite preliminar de CORE01-D05-A/B/C/D, **sem programar e sem executar testes**.  
**Natureza:** descrever fontes de verdade, fronteiras de titularidade, eventos, estados, precedência e riscos. **Não** contratar, criar organizações reais, gerar cobranças, enviar avisos, ativar trial/cortesia, migrar dados nem estabelecer schema, serviço ou mecanismo físico.

## 1. Bases expressamente aprovadas a preservar

1. [CORE01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md), [CORE01 D04-A/B/C](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md), [CORE01 D05-A/B/C/D](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md): identidade global distinta da organização CRM/tenant; uma membership lógica efetiva por identidade×tenant, convite ≠ grant, revogação por tenant; contexto verificado e RLS como defesa adicional. D04/D05 **não estão encerradas**.
2. [CORE08 D01–D03](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md), [CORE08 v0.1](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md), [CRM-REV02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): **Básico é plano público pago**, cortesia é benefício elegível do Site por tenant; contrato Site e assinatura CRM são independentes; gratuidade administrativa exige ato separado; um **trial de 14 dias por CNPJ** não equivale a número de tenants nem se renova com novo Site. Não fixar preços/quotas definitivos agora.
3. [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md), [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md), [PORTAL-COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md): um benefício Básico efetivo por organização, se sustentada por ao menos um contrato de Site elegível; 30 dias de transição a partir de **cancelamento formal do último Site elegível**, em operação plena no cancelamento comum ou **restrita** após suspensão por inadimplência; avisos 0/15/25/29/30; suspensão temporária do Site, sozinha, **não inicia** o prazo de 30 dias.
4. [IN-127-R01..R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): página estática sem CRM pode ser publicada mediante aceite expresso, mas **captação/formulários proibidos sem destino CRM oficialmente vinculado e autorizado**, inclusive cache e páginas antigas; recepção/entrega e política de fila própria não decidem tenant nem benefício. **R01..R06** prevalecem nas respectivas matérias sobre texto histórico de prazos/retentativas.
5. [CORE03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md), [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): idempotência contextual, resultados oficiais por proprietário, eventos fora de ordem/repetidos, autorização por tenant e reconciliação são exigências de planejamento. ACK/HTTP 2xx não são confirmação oficial do contrato, assinatura, benefício, cobrança ou entrega de lead.
6. [CORE07 D01–D03](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md) e [CORE04 D01–D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md): Básico 1 usuário humano autenticado/admin com MFA, assento por organização; profissional agendável não é assento. Suspensão comercial não equivale ao desligamento de uma identidade global.
7. [CORE06](CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md), [IN-118](IN-118-central-backup-portabilidade-dados.md): históricos, portabilidade e retenções por categoria devem ser preservados; **trial expirado: 60 dias** de retenção segundo seu regime; **CRM formalmente cancelado: referência geral 120 dias** a partir do cancelamento CRM, não do cancelamento Site ou simples suspensão, sujeita às regras legítimas aplicáveis.

**Princípio central:** titularidade/legalidade ≠ tenant ≠ membership ≠ contrato Site ≠ assinatura CRM ≠ benefício de cortesia ≠ gratuidade administrativa ≠ trial; é proibido usar uma dessas evidências como atalho de autorização para outra.

## 2. Fonte de verdade e responsabilidade por fronteira

| Domínio/objeto | Fonte autoritativa e decisão permitida | Evidência/contexto indispensável | Proibição de inferência |
|---|---|---|---|
| Pessoa/identidade global e MFA | Identidade e Segurança CRM | Identidade verificada, política MFA e auditoria | Mesmo e-mail/CNPJ autoriza vínculo, novo perfil ou recuperação |
| Organização CRM/tenant e membership | CRM/IAM, com ato do representante autorizado | Organização escolhida, representante e permissões verificados, direito de ativar | Novo site/landing cria automaticamente tenant não solicitado |
| CNPJ/representante da empresa | Processo de verificação cadastral/contratual competente | Registro legal e representação vigente, delegação e finalidade | Mesmo CNPJ unifica tenants, registros, consentimentos ou quotas |
| Contrato Site/landing, serviço e campanha | Portal Imagine/Sites e gestão contratual | Contrato oficial, versão, elegibilidade e estado financeiro oficial | Site publicado, pagamento técnico ou domínio prova direito à cortesia |
| Destino Site→CRM | Portal/Sites + CRM validam vínculo autorizado | Escolha expressa de tenant existente autorizado ou criação de tenant isolado, evidência e versão | Destino deduzido de e-mail, CNPJ, CRM anterior ou campo público |
| Plano/assinatura CRM paga | CRM FIN/CON e fonte financeira competente | Contratação específica, situação financeira e entitlement confirmados | Site cancelado suspende assinatura paga; cortesia gera cobrança implícita |
| Benefício Básico cortesia | Contratos Site elegíveis + CRM FIN/CON/entitlement | Ao menos um contrato sustentador elegível por tenant; no máximo um benefício efetivo por organização | Dois sites no mesmo tenant duplicam benefícios ou assentos |
| Gratuidade administrativa | Admin Geral Imagine competente + FIN/CON | Ato próprio versionado, escopo, plano/extra e autoridade | Agente, suporte ou benefício Site concede qualquer plano |
| Trial | CRM FIN/CON/identidade empresarial | Um trial de 14 dias por CNPJ elegível, confirmação e histórico de consumo | Nova conta/organização, novo e-mail ou novo Site reinicia trial |
| Eventos, notificações e reconciliação | Proprietário do fato, Central de Eventos/Filas e COM | Correlation/causalidade, versão, estado, destinatário e resultado oficial | Mensagem enviada/recebida = negócio concluído |
| Dados, leads e portabilidade | CRM do tenant responsável e serviços de Dados/Arquivo | Tenant/objeto/finalidade autorizados; vínculo Site oficial | Mudança de Site→CRM migra dados ou redireciona registros automaticamente |

**Competências são domínios funcionais propostos**, não designações de pessoas, aprovação de acesso, nem criação de novos motores. O cliente deve escolher expressamente a organização destino; mudança posterior de vinculação por chamado é tecnicamente gratuita à Imagine, **sem transferência de histórico entre contas por ela**; o cliente pode usar importação/exportação do CRM conforme permissão.

## 3. Objetos e estados conceituais — separação estrita, sem schema aprovado

| Entidade lógica | Estados/atributos a documentar separadamente | Advertência |
|---|---|---|
| Organização CRM | Existente, preparada, ativa, operação limitada/suspensa, formalmente cancelada, em retenção, elegível a reativação, com transições próprias | O estado operacional da organização não é sinônimo do estado de cada assinatura ou membership |
| Contrato Site | Elegível/não elegível por cláusula/serviço/campanha, ativo, em tolerância, suspenso, formalmente cancelado, com data e causa | Suspensão não equivale ao cancelamento formal; política depende de contrato |
| Vínculo Site→tenant | Destino escolhido, titularidade, confirmação, versão, efetividade, alteração por chamado, revogação | Vincular site a outro tenant não leva leads antigos automaticamente |
| Assinatura CRM | Contratação paga, plano, faturamento, adimplência, suspensão/cancelamento formal, reativação por regra própria | Plano CRM pago não pode ser suspenso por dívida do Site |
| Benefício cortesia | Elegibilidade comprovada, sustentadores, início, suspensão, restabelecimento, transição, fim | No máximo um benefício Básico efetivo por organização; vários sustentadores não acumulam quotas |
| Trial CRM | Disponibilidade por CNPJ, elegibilidade, ativação, vencimento, expirado, retenção | Apenas uma utilização por CNPJ; 60 dias pós-expiração não viram 120 automaticamente |
| Gratuidade administrativa | Concessão específica por autoridade, vigência, suspensão/revogação, histórico | Independente de Site; precedência simultânea com promoção/plano pago segue **CORE08-D05/GRAT-P** |
| Membership e direitos | Vínculo humano próprio com estado de acesso e direitos da organização | Bloqueio financeiro não apaga login global nem memberships de outros tenants |

**Estados acima são categorias documentais de referência, não enums, IDs, timestamps, autômatos executáveis ou precificação aprovados.** As decisões de precedência de promoções, pagamento, gratuidade administrativa e cortesia são **CORE08-D05**, não devem ser fechadas indiretamente por CORE01-D06.

## 4. Transições contratuais que devem continuar verdadeiras

| Situação | Regra documental já aceita | Especificação ainda pendente |
|---|---|---|
| Cliente adquire novo Site elegível, sem CRM | Solicitar escolha expressa: nova organização CRM isolada ou organização existente que representante pode autorizar | Prova de representação, versionamento do vínculo, idempotência e titularidade |
| Cliente tem uma organização CRM e contrata outro Site | Escolher vincular ao mesmo tenant ou nova conta segregada; não criar por silêncio | Fluxo de seleção, conflitos, créditos, conta já suspensa |
| Sites S1 e S2 sustentam a mesma organização | Uma cortesia efetiva, sem multiplicar franquias ou usuários; cancelar S1 preserva direito se S2 elegível permanece | Critério de sustentador válido durante tolerância, estados simultâneos |
| Mesmo CNPJ, dois contratos elegíveis, organizações A e B | Podem existir benefícios separados por tenant, com dados/quotas segregados; **não** conceder segundo trial | Capacidade, segurança/representação, provas, antifraude |
| Último Site elegível cancelado normalmente | Transição de **30 dias com operação plena**, desde cancelamento formal; avisos dia 0,15,25,29,30; não contratar CRM pago automaticamente | Relógio oficial, timezone, provas de entrega, conflito de eventos |
| Site sustentador suspenso por inadimplência após tolerância de 7 dias | Se não há outro sustentador elegível, operação da cortesia fica **restrita** enquanto não regularizada; não iniciar 30 dias só pela suspensão | Regras exatas de restituição após pagamento, classificação de outras fontes |
| Último Site formalmente cancelado por inadimplência após suspensão | Contar **30 dias restritos** desde cancelamento formal, sem desbloquear operação ao cancelar; permitir portabilidade/contratação paga autorizada | Portas de acesso restrito, avisos oficiais, contagem de dias |
| Assinatura CRM paga continua ativa | CRM pago permanece independente da existência/atraso de Site; não suspender por erro de cortesia | Precedência de direitos e eventos simultâneos, FIN/CON |
| Trial expirou | Operação de trial bloqueada; prazo de retenção específico de **60 dias**, sem novo trial por novo Site | Política e requisitos legais de descarte/reativação |
| Conta CRM formalmente cancelada | Retenção geral de **120 dias a partir do cancelamento CRM**; mera suspensão ou cancelamento Site não aciona esse relógio | Retenções legais/exceções, backups e portabilidade |
| Troca de destino Site→CRM via chamado | Nova integração técnica gratuita; dados antigos continuam no CRM antigo, salvo migração feita pelo cliente | Revalidação de destino, bloqueio de formulários antigos/cache, reconciliação |
| Site estático sem vínculo CRM | Pode publicar se cliente aceitar, mas **formulários e captação ficam bloqueados no backend** | Estados de ativação de formulário e segurança contra versões antigas/cache |
| Mensagens FIN/Portal duplicadas, tardias, inconclusivas | Não duplicar contrato/tenant/benefício ou cobrar; reconciliar com fonte oficial | IDs, schema e chave de idempotência contextual, estados inconclusivos |

**Retenção e portabilidade:** nenhum evento de site ou benefício autoriza exclusão automática de dados da conta CRM. Não aplicar política de payload de fila de lead IN127 a dados oficiais já entregues ao CRM nem generalizar prazo de 432h às filas internas CRM.

## 5. Propostas CORE01-D06 para eventual manifestação expressa — NÃO APROVADAS

### CORE01-D06-A — fontes de autoridade e vínculos explícitos

**Proposta:** adotar como referência contratual a **separação de fontes oficiais** para pessoa/CNPJ, organização CRM, contrato de Site, seleção explícita de destino, assinatura paga, cortesia, gratuidade e trial; somente ator/serviço competente confirma os fatos e direitos de sua esfera. Uma organização não é criada por coincidência de CNPJ/e-mail, clique duplicado, evento técnico ou envio de formulário.

**Sem aprovar:** schema, titularidade documental exigida, alçada ou serviço provisionador.

### CORE01-D06-B — ciclo independente e preservação de direitos

**Proposta:** documentar **máquinas lógicas distintas** de contrato Site, vínculo ao CRM, organização, assinatura paga, benefício cortesia, trial e gratuidade administrativa, com verificação de estado e fonte oficial antes de novos efeitos. Preservar as transições de 30 dias comuns/restritas, os avisos aprovados, a não cumulação por tenant, a independência de CRM pago e a vedação de cobrança automática.

**Sem aprovar:** transições físicas, relógios, precedência exata entre múltiplos benefícios ou habilitação de avisos reais (CORE08-D05/D07 e FIN/CON).

### CORE01-D06-C — titularidade, concorrência e vínculo idempotente

**Proposta:** condicionar criação/vinculação/alteração de organização ao **pedido legítimo e verificado**, comprovando titularidade do contrato e autoridade sobre tenant destino. Exigir identificação e causalidade de eventos oficiais, proteção contra duplicações e reordenações, reconciliação de situações inconclusivas, não mesclagem de dados e trilhas auditáveis; mudança de destino via chamado, sem migração automática.

**Sem aprovar:** tipo exato de comprovação, esquema/versionamento, limite por CNPJ, chave de idempotência ou ferramenta técnica (CORE08-D04/D06, INT P0-CON/SEC).

### CORE01-D06-D — retenção, restrição e portabilidade sem efeitos colaterais

**Proposta:** diferenciar **restrição operacional por site/cortesia**, **expiração de trial** e **cancelamento formal CRM** para aplicar somente o regime de acesso, portabilidade e retenção correspondente. Preservar 60 dias após trial expirado e 120 dias após cancelamento formal CRM como referências de política já aceitas, sujeitas a exceções legais; mudança/expiração da cortesia não aciona descarte, não apaga lead já entregue e não converte o cliente em plano pago sem contratação expressa.

**Sem aprovar:** algoritmos de expurgo, exceções/impedimentos LGPD, reativações exatas, definição de portabilidade restrita em cada estado (CORE06/08 e FIN/CON).

**A eventual aprovação de A–D seria apenas aprovação de diretrizes documentais preliminares**, não encerraria CORE01-D06 nem CORE08-D04..D10/FIN/CON. Nenhuma opção nesta proposta foi aprovada por sua publicação.

## 6. Pendências técnicas e dependências para fechamento integral da D06

| Código | Questão que precisa de especificação e decisão formal | Dependência |
|---|---|---|
| **CORE01-D06-01** | Como comprovar representante do contrato/tenant para criação e alteração de vínculo, quando titular diverge do CNPJ ou e-mail do login | CORE04/05/07/08, CON, LGPD |
| **CORE01-D06-02** | Qual contrato de evento tipado e versionado confirma cada fato oficial e sua causalidade, idempotência, erro/inconclusão e reordenação | CORE03, INT P0-OWN/CON/SEC, FIN |
| **CORE01-D06-03** | Em quais estados contrato Site efetivamente sustenta cortesia, especialmente tolerância, suspensão, pagamentos pendentes e múltiplos Sites | CORE08-D05/D07, FIN/CON, Sites |
| **CORE01-D06-04** | Precedência e convivência de cortesia, assinatura CRM paga, promoção, extras e gratuidade administrativa | CORE08-D05/D09, GRAT-P, FIN/CON |
| **CORE01-D06-05** | Como calcular relógios oficiais, fusos, transição, avisos e exceções/contestação sem reiniciar prazo com evento repetido | CORE08-D07/D08, COM, INT Datas/Prazos |
| **CORE01-D06-06** | Que operações permanecem permitidas em acesso restrito (login MFA, backup, export, suporte, pagamento, migração) | CORE04/05/06/08, LGPD |
| **CORE01-D06-07** | Quais garantias de formulário e recepção de leads ao mudar vínculo ou perder destino CRM, inclusive cache e indisponibilidade temporária | IN127-R01..R06, IN-150, ARQ-02 |
| **CORE01-D06-08** | Esquema físico e integridade do vínculo, limites, isolamento entre tenants, rastros e critérios da homologação futura | CORE01-D07, CORE02/09/10, SEG-01/02 |

Esses subtópicos são itens de trabalho **internos à D06**, não oito novos P0 nem oito decisões automaticamente abertas no inventário principal. Resolver uma dependência não aprova as outras por inferência.

## 7. Cenários de aceitação futura — planejamento apenas, testes NÃO EXECUTADOS

| Referência | Cenário | Resultado esperado (sem declaração de PASS) |
|---|---|---|
| CORE01-D06-T01 | Mesmo CNPJ contrata Site S2 e escolhe nova organização B; trial já consumido em A | Tenant B segregado se contrato/representação permitirem, benefício elegível independente; nenhum novo trial |
| CORE01-D06-T02 | Dois Sites elegíveis sustentam mesma organização; S1 é cancelado | Cortesia mantém-se por S2 se válido; não duplica quotas |
| CORE01-D06-T03 | Site único suspenso por inadimplência, depois cancelado formalmente | Restrição desde suspensão; 30 dias restritos desde cancelamento, não desde suspensão |
| CORE01-D06-T04 | Último Site é cancelado voluntariamente sem inadimplência | Transição 30 dias plenos e avisos 0/15/25/29/30, sem fatura tácita |
| CORE01-D06-T05 | CRM pago ativo, Site entra em dívida | CRM pago não é suspenso por Site; módulo financeiro mantém fatos separados |
| CORE01-D06-T06 | Cancelamento Site tenta iniciar retenção geral CRM de 120 dias | Negar; relógio da conta CRM apenas no cancelamento formal da própria conta |
| CORE01-D06-T07 | Evento duplicado ou fora de ordem cria segundo tenant/benefício/cobrança | Deduplicar/reconciliar com fonte oficial; não criar ato econômico indevido |
| CORE01-D06-T08 | Troca de destino de Site A→B por chamado | Não migrar históricos nem captar em destino não autorizado; validar backend e versões em cache |
| CORE01-D06-T09 | Site estático sem CRM tenta enviar formulário | Bloquear captação no backend, inclusive versões antigas |
| CORE01-D06-T10 | Extinção de última cortesia coincide com gratuidade administrativa/pagamento CRM | Não bloquear outro direito válido; precedência pendente CORE08-D05, registrar inconclusão sem inventar direito |
| CORE01-D06-T11 | Ação de atualização cadastral tenta unir dados de dois tenants do mesmo CNPJ | Não unir, transferir ou expor registros sem autorização/migração expressa |
| CORE01-D06-T12 | Suspensão do contrato tenta apagar histórico/arquivos/lead CRM | Não apagar; preservar dados conforme política e portabilidade, LGPD e contrato |

**Não multiplicar suítes:** relacionar CORE01-D06-T a CORE08-T, CORE01-T, REV01-S, OPS-T, INT HT-04/05/13 e CORE10-D06; escolher IDs canônicos no fechamento CORE10. Nada foi testado ou validado em ambiente.

## 8. Proposta de deliberação futura e continuidade

Pauta para o responsável: avaliar **CORE01-D06-A/B/C/D como diretrizes técnicas documentais preliminares**, sem fechar CORE01-D06, sem decidir mecanismo de prova, schemas/estados/timestamps, cobrança, direito efetivo de assinatura, LGPD/retentão, preços ou fornecedores. CORE01-D04/D05/D07, CORE08-D04..D10, FIN/CON, SEG-01/02 e demais pendências permanecerão ABERTAS.

Próxima análise após eventual aceite: **CORE01-D07 — modelos físicos, relações, PK/FK/constraints, políticas RLS por classe e contratos de integração**, somente documental, sem implementar.

**Vedações:** nenhuma programação, scripts, testes reais, consultas a dados de cliente, credenciais, migração, geração de plano/cortesia, envio de avisos, operações financeiras, provisionamento, compra, implantação ou homologação física foi autorizada.
