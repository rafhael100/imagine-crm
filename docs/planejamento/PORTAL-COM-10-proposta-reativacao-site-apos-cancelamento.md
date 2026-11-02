# PORTAL-COM-10 — PROPOSTA de reativação e recuperação de site após cancelamento

**Status (02/11/2026): PROPOSTA EM DISCUSSÃO, NÃO APROVADA.** O responsável perguntou: “depois de cancelado se o cliente desejar reativar?”. A ideia de reativação está sendo explorada, sem autorização expressa sobre prazo, preços, taxa, retenção ou compromisso de restauração.
**Etapa:** somente planejamento/documentação; sem programação, publicação, cobrança, alterações operacionais ou recuperação real.
**Referências:** [PORTAL-COM-08 — atraso e suspensão](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md), [PORTAL-COM-09 — cortesia após cancelamento](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md), [PORTAL-D04 — cobrança](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md), IN-100/101 (domínios e backups) em docs/decisoes.md no ramo docs/integracao-site-crm-basico; [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md).

## 1. Objetivo da proposta

Permitir ao cliente solicitar a **reativação do site mesmo após o cancelamento formal** e, se tecnicamente possível, reaproveitar versões, conteúdo, estrutura, dados de configuração e domínio legítimo. Distinguir **suspensão de contrato ainda ativo** (normalmente regularização/restabelecimento, sem nova implantação) de **cancelamento contratual definitivo** (nova contratação/termo específico, com tentativa de recuperação do projeto antigo, se retido).

**A política de cancelamento do site após inadimplência total de 30 dias foi apenas proposta ao usuário e AINDA NÃO aprovada.** O prazo de 7 dias de tolerância para suspensão já foi aprovado. Não promover automaticamente os 30 dias a decisão vigente.

## 2. Fluxos candidatos (todos pendentes de aprovação comercial)

1. **Site suspenso sem cancelamento:** após conciliar pagamento e validar conta, reativar o mesmo site e benefício CRM cortesia conforme IN-119–122; sem taxa adicional de implantação sugerida quando não há nova produção.
2. **Site formalmente cancelado e recuperável:** cliente entra no Portal, escolhe “Solicitar reativação”, valida titularidade, confirma domínio e ativos disponíveis, analisa pendências antigas (quitação ou acordo documentado segundo política aprovada), recebe simulação financeira e aprova expressamente **nova contratação/termo de reativação**. A equipe restaura projeto tecnicamente recuperável, verifica DNS, SSL, formulários, envio de leads ao CRM correto e integridade antes de republicar; sem criar tenant indevido.
3. **Site cancelado sem backup utilizável ou com domínio indisponível:** não prometer restauração. Oferecer orçamento transparente para reconstrução/nova implantação e obter aceite. Não substituir direito de domínio de terceiro nem oferecer transferência não autorizada.

### Preços e condições

**Recomendação — NÃO APROVADA:** sem cobrar segunda taxa de implantação quando bastar religar o site e não houver trabalho adicional substancial; porém custos efetivos de recuperação técnica, alterações, domínio/registro, DNS, hospedagem e revisão devem ser previstos em proposta com aceite. Eventual taxa de reativação fica **administrável pela Imagine**, por tipo de serviço, política e complexidade, com alçada e divulgação prévia. Não cobrar automaticamente taxa “oculta”.

**Mensalidade e promoção:** após **cancelamento formal**, não presumir restabelecimento automático de preço promocional vitalício, condições de carência, cortesia ou prazo do contrato anterior. Ofertar as condições do **novo contrato** (tabela e campanha vigente, ou condição específica de recuperação que o administrador autorizar) e colher novo aceite. **Não alterar retroativamente contratos ainda ativos**. Se o usuário desejar manter promoções antigas em janela de recuperação, precisará aprovar essa exceção expressamente.

**Débitos:** o cancelamento e a reativação não significam perdão automático da dívida antiga, nem cobrança automática indevida; ofertar quitação ou negociação explícita antes da reativação conforme política contratual. Não presumir que dívida contestada pode bloquear direitos de dados.

## 3. Prazo de recuperação e retenção do site — decisão ainda necessária

Aprovada em IN-101 a necessidade de **backups independentes de sites**, com verificações de integridade, recuperação segura, histórico de versões e respeito a domínios. **PRAZO DE RETENÇÃO DOS BACKUPS DE SITES NÃO FOI APROVADO**, e não deve ser confundido com os **120 dias de retenção após CANCELAMENTO FORMAL DE CONTA CRM** da IN-120.

**Proposta inicial para deliberação:** permitir configurar um período de recuperação comercial por serviço, **sugerido de 60 dias após cancelamento formal**, desde que os arquivos estejam efetivamente retidos e a política de backup comportar esse prazo. O período técnico de preservação e exclusão/deidentificação, custos, proteção de dados, condições de acesso e avisos exigem decisões próprias; não anunciar “recuperação garantida por 60 dias” sem infraestrutura/backup homologados.

**Após janela/sem backup:** nova produção, com proposta comercial específica, sujeita a direitos de dados, contratualidade e limites de retenção.

## 4. Conciliação com CRM Básico cortesia e planos pagos

1. Se o **novo contrato do site for elegível à cortesia** e a conta CRM correta estiver válida, reavaliar elegibilidade e reativar o Básico conforme IN-119–122. Se o contrato/campanha nova **não conceder cortesia**, oferecer CRM pago somente por aceite expresso.
2. Durante os **30 dias de transição do CRM** após cancelamento do último site, observar o tipo aprovado: **cancelamento comum** → CRM funciona normalmente; **cancelamento por inadimplência após suspensão** → CRM permanece **restrito à exportação/portabilidade e contratação de plano pago** (COM-09). Uma reativação legítima de vínculo elegível suspende o cronograma de expiração após validar contratos/vínculos.
3. **Outro site ativo elegível** preserva a cortesia da mesma conta mesmo que este continue cancelado; **assinaturas CRM pagas independentes** continuam operando.
4. **120 dias após cancelamento formal de conta CRM** são política própria do CRM e não são promessa de retenção/reversibilidade do site, domínio ou banco de dados. Não apagar/levar em conta dados de CRM como se fossem parte do backup do site. Não gerar cobranças ou múltiplas cortesias automaticamente.

## 5. Interface sugerida — sujeita à aprovação

- Cliente: projeto cancelado → **Solicitar reativação** → mostrar o que é recuperável e o que precisa de nova contratação, preço/mensalidade/campanha ou orçamento, eventuais débitos e encargos declarados, domínio, prazo e aceite → acompanhamento da análise e ativação.
- Admin: estado/razão/data do cancelamento; disponibilidade do backup validada; titularidade e domínio; custo/margem; definição de preço, taxa e termos; conciliação de débito e titularidade; aprovação; recuperação segura; integração CRM; auditoria e comunicação.
- NUNCA reativar automaticamente serviço cancelado por um pagamento tardio que não possua contrato válido ou aceite de reativação. Tratar depósito inesperado sem contratar automaticamente, com reembolso/apropriação conforme política legal.

## 6. Perguntas ainda abertas

1. **Aprovar a possibilidade de reativar sites cancelados** sob novo aceite, reaproveitando conteúdo quando recuperável.
2. Definir **janela administrativa de recuperação**: proposta de 60 dias por padrão, parametrizável por serviço, sujeita à retenção real dos arquivos e contratos.
3. Aprovar se um contrato reativado pode manter o preço e as promoções anteriores ou **sempre adota o novo preço**, exceto exceção administrativa expressa.
4. Aprovar **gratuidade de reativação simples** ou taxa configurável por serviço; manter cobranças novas com aceite explícito.
5. Definir **retenção e política técnica de backups de sites** (IN-101), aviso de exclusão, domínio e custos.
6. Fechar prazo exato de **cancelamento do site por inadimplência** (30 dias total foi PROPOSTO, não aprovado), marcos de suspensão, reativação e reversões.
7. Definir coordenação com suporte/IA, dias de restauração e atualização de DNS, SSL, banco e destino de leads.

**NENHUMA REGRA NOVA DE REATIVAÇÃO, PRAZO 60 DIAS OU TAXA FOI APROVADA.** A conversa solicita análise e decisão posterior.
