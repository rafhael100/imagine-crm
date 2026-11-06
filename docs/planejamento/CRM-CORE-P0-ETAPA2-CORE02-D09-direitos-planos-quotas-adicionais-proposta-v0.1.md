# CRM Imagine — CORE02-D09: direitos por plano, franquias, adicionais e estado da assinatura — proposta v0.1

**Data:** 05/11/2026.  
**Estado atualizado em 06/11/2026:** **CORE02-D09-A/B/C/D APROVADAS EXPRESSAMENTE SOMENTE COMO DIRETRIZES DOCUMENTAIS PRELIMINARES**, conforme [registro de ratificação](CRM-DEC-ratificacao-explicita-CORE02-D09-ABCD-2026-11-06.md). **CORE02-D09 integral permanece ABERTA** até revisão comercial, matriz de recursos/quotas efetiva e homologação. Referências abaixo a propostas “ainda não aprovadas” descrevem o estado histórico anterior ao aceite.  
**Finalidade:** definir princípios para separar direitos técnicos/permissões do usuário, funcionalidades comerciais do plano, franquias, créditos e efeitos de contratação, cortesia, suspensão e migração. **Não** constitui tabela comercial final, mudança de preço, concessão real de entitlement, implementação, testes ou autorização de cobrança/implantação.

## 1. Fonte e decisões a preservar

- [REV-02 — planos, trial, assinaturas e cobrança](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) aceita como base de reconciliação: **quatro planos** Básico, Essencial, Profissional e Empresarial. Preços atuais de planejamento: **Básico R$ 69**, **Essencial R$ 89**; **Profissional R$ 149** e **Empresarial R$ 299** como referências, todos sujeitos à revisão comercial final expressa. Não reabrir nem alterar preços nesta pauta.
- Franquias de **arquivos/anexos** de referência em REV-02: Básico **1 GB**, Essencial **5 GB**, Profissional **20 GB**, Empresarial **50 GB**; limite geral de **5 MB por arquivo**. A quota comercial de arquivo não abrange automaticamente dados estruturados. Cotas finais e extras permanecem sob revisão conjunta.
- **Básico pago e Básico em cortesia possuem o mesmo conjunto de recursos e um administrador humano com MFA**; cortesia é **benefício condicionado ao site/landing elegível**, não plano separado nem direito perpétuo. Uma organização pode receber apenas uma cortesia efetiva, sem somar múltiplos benefícios pelo número de sites vinculados.
- **Trial** de 14 dias, sem cartão, um por CNPJ, com franquias próprias de teste: dois usuários, vinte leads, dois funis, duas automações, 20 MB, dez análises Prospector e dez solicitações Comercial; tais quotas não migram automaticamente para o Básico.
- Direitos de importação, exportação, backup, restauração e portabilidade autoatendidos nos limites legítimos de [IN-118](IN-118-central-backup-portabilidade-dados.md) são essenciais em todos os quatro planos, sem taxa adicional por operação essencial; nunca concedem acesso a dados de outro tenant.
- CRM pago tem contrato/cobrança independente do site. No CRM pago: tolerância de 7 dias de inadimplência, suspensão posterior e cancelamento aos 30 dias de atraso, sujeitos a contratos técnicos FIN/CON. Cortesia de site observa ciclos distintos de suspensão e transição de 30 dias conforme IN-119..122 e COM-08/09. Cancelamento do CRM ativa retenção aplicável de 120 dias; trial expirado tem regra própria de 60 dias. **Não converter automaticamente** cortesia/trial em assinatura paga.
- Concessões gratuitas de plano ou adicional pelo administrador Imagine competente são **atos comerciais independentes**, não permissão de funcionário/IA e não soma automática de direitos. Upgrade com pró-rata após pagamento e downgrade no ciclo seguinte seguem a base funcional, com definição física pendente.
- [CORE02-D04..D08](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) possuem bases/diretrizes preliminares; CORE02-D05 P1–P4 não foi decidido. [CORE08](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) D04..D10, CORE07-D05 e FIN/CON ainda precisam de contratos concretos e conciliação.

## 2. Separações conceituais obrigatórias

| Camada | Pergunta que resolve | Não deve ser confundida com |
|---|---|---|
| Catálogo de produto | O recurso existe e está previsto neste plano/campanha? | Ter papel de administrador ou botão visível |
| Entitlement | A organização tem direito vigente ao recurso/canal/add-on? | Valor exibido na tabela ou mero pedido de contratação |
| Quota/franquia | Qual o volume, período, unidade e consumo oficial? | Permissão de executar ato, capacidade ilimitada ou preço final |
| Papel e autorização | Este usuário pode executar esta ação, neste escopo/campo? | Recurso contratado por outro tenant ou titularidade por CNPJ |
| Estado e cobrança | A assinatura/benefício está ativa, suspensa, em transição ou cancelada? | Pagamento declarado no browser, 2xx de gateway ou site vinculado |
| Fonte oficial | Qual sistema/owner confirma pagamento, elegibilidade, consumo e efeito? | Log do Codex, webhook não reconciliado ou resposta IA |

## 3. CORE02-D09-A/B/C/D — propostas para manifestação expressa

### D09-A — Matriz única produto × recurso × plano × ação

Exigir matriz versionada por produto, plano, recurso, ação, campo/canal e estado, com proprietário do catálogo e vínculo ao contrato oficial. Diferenciar **recurso contratado** de **permissão individual**: empregado com papel amplo não habilita módulo fora do plano; plano superior não autoriza exportar dados por qualquer funcionário. Preservar Básico pago e cortesia funcionalmente equivalentes quanto ao plano. Não acrescentar WhatsApp, IA premium, automações livres ou recursos de planos superiores ao Básico por analogia.

### D09-B — Franquias e adicionais explícitos, consumo e prevenção de cobrança indevida

Exigir para cada franquia unidade (GB, usuários, leads, mensagens, ações de IA etc.), período de medição, evento oficial de consumo, política de excedente/bloqueio, alertas e opções de contratação **expressa** quando autorizadas. Não cobrar, alterar plano, comprar crédito nem habilitar adicional automaticamente por uma tentativa que excedeu quota. Não bloquear direito de portabilidade legítima como instrumento de venda. Números comerciais finais e custos de extras continuam **pendentes de revisão** com CORE08-D09 e FIN/CON, sem nova tabela aprovada aqui.

### D09-C — Direitos por estado de trial, plano pago, cortesia e gratuidade

Exigir política versionada para distinguir trial, conta CRM paga, cortesia elegível por contrato de site, gratuidade administrativa, adicional gratuito, promoção, suspensão, cancelamento e reativação. Preservar que uma inadimplência de **site** não pode suspender assinatura **CRM paga** autônoma. Estado restrito deve preservar os direitos mínimos legítimos de login seguro, gestão da assinatura e portabilidade conforme contrato/LGPD. Sem conversão, extensão ou cobrança automática sem aceite válido. Cada mudança exige evento oficial idempotente e rastreável.

### D09-D — Compatibilidade e homologação por proprietários comerciais/técnicos

Exigir revisão conjunta das matrizes por CORE08, CORE07, FIN/CON, Produto, Portal/Sites, IAM/Segurança e responsáveis dos módulos. Para cada entitlement, documentar origem, contrato/campanha, tenant, vigência, ator, estado, quota, canais, transições e casos negativos, inclusive downgrade, excedente, múltiplos sites, créditos IA e restauração de dados. Catálogo de permissões compartilha contratos com o catálogo comercial sem criar segundo mecanismo de autorização. Evidências e testes **futuros** devem ser vinculados a CORE10, INT e SEG.

## 4. Exemplos de regras e casos a homologar no futuro — SEM EXECUÇÃO

| Cenário | Resultado exigido em princípio |
|---|---|
| Básico cortesia vs Básico pago | Mesmas capacidades do plano; cobrança/benefício e fonte de elegibilidade diferentes |
| Plano superior contratado, funcionário sem direito de exportar | Recurso do plano não amplia ação sensível do usuário |
| Mais de um site sustenta uma organização CRM | Não duplicar cortesia, assentos, GB ou créditos por site |
| Mesmo CNPJ cria nova organização CRM elegível | Tenant, quota, dados e contrato próprios; não gerar segundo trial por CNPJ |
| Cliente atinge franquia de anexos | Bloquear uploads excedentes conforme política; não eliminar dados ou impedir portabilidade permitida |
| Trial termina sem contratação | Encerrar operação normal conforme política, não gerar pagamento |
| Site inadimplente sustenta cortesia, mas CRM pago está ativo | Preservar CRM pago conforme seu contrato |
| Downgrade com dados acima da franquia inferior | Preservar dados e aplicar política de restrição de novos usos, com transparência |
| Pedido de upgrade sem pagamento confirmado | Não habilitar entitlement por resposta técnica ou mera solicitação |
| IA ou funcionário solicita gratuidade | Não conceder benefício sem autoridade comercial própria |

## 5. Reservas e dependências

- Não escolher preços definitivos, assentos pagos, descontos, limites de IA, usuários adicionais, custo de armazenamento extra, componentes dos planos ou datas de cobrança neste aceite. Esses itens dependem da **revisão comercial consolidada** e aprovação específica.
- Manter CORE02-D04..D10 integrais abertos, CORE01 e CORE03..CORE10, SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, CORE08-D04..D10 e critérios de homologação.
- Não confundir esta pauta documental com aprovação de programação adicional do Codex; preservar branch do fast-track, `main` e VPS/produção sem alterações.

## 6. Próxima decisão

Submeter ao responsável a **aprovação preliminar de CORE02-D09-A/B/C/D**. Se aprovada, manter CORE02-D09 integral ABERTA até políticas comerciais e de autorização concretas, provas e aceite final. Preparar depois CORE02-D10 — rastreabilidade de critérios, evidências, testes e homologação — sem fechar automaticamente CORE02.
