# CORE01-D04 — Proposta técnica de decisão: contrato de membership, estados e unicidade

**Data:** 04/11/2026. **Estado:** **PROPOSTA TÉCNICA NÃO APROVADA; CORE01-D04 permanece ABERTA**. Documento de apoio à [Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) e à [especificação CORE01-D04..D07](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md). Não implementar, testar, alterar usuários ou dados reais.

## 1. Problema a resolver

CORE01-D01..D03 já aprovaram **uma identidade global**, **organizações=tenants isolados**, **membership por tenant** e Laravel+PostgreSQL RLS. Falta especificar um contrato que evite (a) duplicar vínculo da mesma pessoa em uma organização, (b) contar convites/assentos de modo inconsistente, (c) restaurar privilégios quando alguém é recontratado, (d) derrubar acesso de outras organizações e (e) criar acesso ao CRM com token de convite não verificado.

As definições de **preço/quantidade de assentos pagos, reserva no convite, regra de múltiplos papéis, MFA e administrador único** ainda pertencem CORE02/04/07/08. D04 **não pode invadir essas decisões**.

## 2. Alternativas documentais

| Questão | A — referência recomendada | B — alternativa e risco |
|---|---|---|
| Unicidade de vínculo | **No máximo um vínculo efetivo identidade×tenant**. Readmissões reabrem o mesmo vínculo lógico com novo registro de transição e revisão de papéis | Criar nova membership em toda reativação; aumenta risco de políticas duplicadas, histórico fragmentado e vagas contadas duas vezes |
| Convite e vínculo | **Convite é entidade/intenção separada**, com prazo 7 dias, uso único/cancelável, e não é membership com acesso | Criar membership com status `pending` imediatamente ao enviar e-mail; exige garantir que nenhuma permissão ou contagem indevida decorra dela |
| Históricos de papéis/equipes | **Estado atual separado da trilha histórica oficial**; novas permissões exigem ato competente e versão | Sobrescrever papel antigo sem trilha; dificulta revogação e auditoria |
| Estado de desligamento | **Desativar membership do tenant**, invalidar novos efeitos e sessões do tenant, mantendo identidade e outras memberships | Desativar identidade global; interromperia acesso legítimo a outras organizações |
| Capacidade de plano | Validar autorização e quota vigente **antes da ativação/reativação efetiva**. Reserva de vaga por convite fica CORE07-D05 | Supor que todo convite reserva vaga ou nunca reserva sem decisão; risco de exceder 1 usuário Básico ou cobrar sem aceite |
| Admin único Básico | **Não permitir desativação destrutiva sem procedimento de sucessão/recuperação homologado** | Autoexclusão imediata do único admin deixa empresa sem administração ou exige bypass posterior |

**Recomendação de arquitetura:** escolher **Alternativa A** como contrato semântico de referência, mas exigir consenso explícito de Segurança, administração empresarial e FIN/CON sobre alçadas e quotas. **Nenhuma opção é aprovada pela publicação deste texto.**

## 3. Estados e transições candidatos (não schema)

| Estado de vínculo | Pode usar recursos tenant-bound? | Transições admissíveis apenas por ator competente |
|---|---|---|
| Inexistente | Não | Convite validamente aceito → preparação/ativação |
| Ativo | Sim, somente segundo direito por ação/recurso/campo, plano e MFA | Suspender; desativar; alterar papel/equipe; permanecer ativo |
| Suspenso | Não para operação normal do vínculo; preservar auditoria | Reativar com quota, MFA e entitlement revalidados; desativar |
| Desativado | Não, revogação de sessão/contexto por tenant | Reativação apenas após prova de direito, nova revisão de atribuições, quota e auditoria |
| Convite pendente | **Não é estado da membership** nesta alternativa; é estado de convite | Aceitar, cancelar, expirar, reenviar, sempre com validade de sete dias e revalidação |

**Observações:** os nomes `ativo/suspenso/desativado` são rótulos semânticos; assinatura/tenant em suspensão e conta humana desativada são coisas diferentes. Não inferir a contagem de vagas em um vínculo suspenso; essa política segue CORE07-D05. Nenhum estado restaura automaticamente grants antigos.

## 4. Contrato de autoria, auditoria e concorrência a detalhar

- **Chave lógica candidata:** par identidade global + tenant. Índice/constraint físico e estratégia de readmissão/duplicatas serão CORE01-D07; não se cria banco agora.
- **Convites concorrentes:** duas aceitações simultâneas não criam vínculos duplicados nem ultrapassam quota. Decidir reserva e prioridade pela CORE07-D05 antes de fechar contrato físico.
- **Evidência oficial:** evento de convite, aceite, alteração de papel, suspensão, desligamento e reativação com executor/ator, tenant, data, referência do recurso e resultados, sem expor tokens ou MFA; categoria de retenção CORE06.
- **Troca A↔B:** trocar tenant autorizado não muda a identidade, não soma cotas nem expõe equipes/leads de outra organização. Revogar membership A preserva B.
- **Trabalho assíncrono:** fatos oficiais da empresa não desaparecem quando funcionário sai; novos efeitos pessoais/exports/jobs exigem revalidação CORE03/07.
- **Recuperação de titularidade:** admin único/sem MFA precisa procedimento específico CORE04-D07, CORE07-D06, não bypass implícito.

## 5. Deliberações objetivas propostas, para aprovação expressa posterior

1. **CORE01-D04-A — unicidade:** um vínculo lógico por identidade×tenant, com histórico de transições e readmissão controlada, sem múltiplas memberships efetivas simultâneas.
2. **CORE01-D04-B — separação:** convite de 7 dias é intenção independente de concessão, e só gera elegibilidade de vínculo após aceitação e revalidação de direitos/assentos/MFA.
3. **CORE01-D04-C — revogação:** desativar/suspender vínculo impede novos acessos e efeitos na **organização específica**, preservando outras organizações e histórico oficial; reativação exige nova validação de direito, escopo e capacidade.

**D04 permanece ABERTA** mesmo se A/B/C forem consideradas boas hipóteses, até cotejo com CORE02-D04/D05, CORE04-D07, CORE07-D04/D05/D06/D08, CORE08-D09, segurança e aceites formais; **D05–D07 igualmente abertas.** Não criar policy RLS, migração, teste, conta, convite ou alteração real.

## 6. Critérios de homologação futuros — NÃO EXECUTADOS

- Mesmo login em tenants A e B: desativação A não extingue B.
- Dois convites concorrentes para última vaga: o resultado jamais cria segunda membership efetiva não autorizada.
- Reativação após perda de papel: não recuperar permissões antigas silenciosamente.
- Convite cancelado/expirado não ativa membership ou quota.
- Básico com admin único não permite segundo login nem troca sem fluxo homologado.
- Jobs e exportações pendentes revalidam vínculo no momento do novo efeito.
- Histórico comercial e trilhas ficam preservados após desligamento.

**Controle de gate:** INT G01–G06, IN-168 SEG-01/02, CORE02/03/04/07/08 e fechamento global continuam abertos. Sem programação, testes reais, provisionamento nem contratação.
