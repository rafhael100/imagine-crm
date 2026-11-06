# CRM Imagine — CORE07-D05: reserva de assentos e convites pendentes — proposta de decisão v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA — CORE07-D05 INTEGRAL ABERTA**.  
**Escopo:** definir em que momento convites de funcionários ocupam um assento do CRM, preservando os limites comerciais e de segurança existentes. Não define novas quotas, preços, política executável, cobrança, testes ou ativação.

## 1. Fontes e invariantes

- [CORE07 — convites, assentos e equipes](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md): convites seguros de **7 dias**, intenção de associação ao tenant sem grant operacional antes do aceite. CORE07-D01..D03 têm base documental aprovada; D04..D10 (inclusive D05) permanecem abertos.
- [CORE08 — Básico/cortesia/trial](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) e [REV02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): Básico 1 administrador humano autenticado com MFA, trial 2 usuários; planos pagos superiores e assentos adicionais dependem de revisão comercial final. Profissionais agendáveis, equipes, caixas de atendimento e identidades técnicas não são assentos humanos de login por analogia.
- [CORE02-D05 alternativa B e Modelo M2](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV01-M2-2026-11-06.md) + [GOV02](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV02-ABCD-2026-11-06.md): convite e gestão de acesso exigem autoridade específica; nenhum perfil ou convite cria autoelevação.
- Cobrança adicional, upgrade, novo plano, concessão gratuita de assento ou trial **nunca** são efeitos automáticos de criar/aceitar convite. A identidade global pode participar de tenants independentes, com assentos contados por tenant quando efetivamente elegíveis.

## 2. Alternativas — não escolhidas

| Modelo | O que ocupa a vaga? | Vantagem | Risco operacional |
|---|---|---|---|
| **A — reservar durante convite pendente** | Reserva uma vaga quando convite válido é emitido, até aceitação, cancelamento, recusa ou expiração; após aceite, converte reserva em ocupação efetiva | Quem recebe convite dentro da cota tem maior previsibilidade de conseguir entrar; proteção contra sobreconvites concorrentes | Convites não aceitos ocupam vagas temporariamente; a interface precisa mostrar e permitir cancelamento |
| **B — contar só após aceite** | Convite pendente não reserva; ao aceitar, só entra se houver vaga livre naquele instante | Não bloqueia assento sem participação ativa | Pessoa pode aceitar convite ainda válido e descobrir que a última vaga já foi ocupada |
| **C — reserva opcional por convite** | Administrador escolhe reservar ou não cada convite | Flexível | Complexidade e inconsistência comercial; difícil explicar se dois convites disputarem última vaga |

**Recomendação para apreciação:** **Modelo A**, com **reserva temporária durante a validade do convite**, liberação segura ao expirar/cancelar/recusar e conversão atômica em assento ocupado ao aceitar; sem cobrança, assento extra ou direito de negócio antes da aceitação. Esta é uma **proposta** e não uma escolha ratificada.

## 3. Regras mínimas sugeridas no Modelo A

1. **Checar disponibilidade antes de emitir convite:** uma reserva pendente por usuário convidado e tenant, com idempotência e validação da autoridade que está convidando; não reservar assentos inexistentes ou de plano não contratado. No Básico, o administrador único já ocupa a vaga 1/1 e um convite para segundo operador deve ser impedido.
2. **Contagem transparente na interface:** assentos **ocupados**, **reservados por convite pendente** e **livres** mostrados separadamente. A reserva não cria membership ativo nem libera acesso a dados.
3. **Duração do convite:** manter prazo de **7 dias** já aprovado. Ao expirar, recusar ou cancelar, liberar reserva sem cobrar nem alterar a assinatura, com trilha de auditoria. Reenvio de convite não deve prolongar uma reserva antiga automaticamente; política concreta de reemissão em CORE07-D04.
4. **Aceite seguro:** autenticação de identidade, token/escopo válidos, MFA do papel, direito de organização e plano, status e vínculo não duplicado; em transação consistente, a reserva vira assento ocupado uma vez. Convite de usuário existente em outro tenant não aproveita grants de outro tenant.
5. **Concorrência e revalidação:** bloquear dois convites que tentem reservar a última vaga simultaneamente, mesmo com APIs/jobs/retries; convites já pendentes precisam revalidação se quota/estado da conta mudar. Nenhuma reserva pode autorizar oversubscription na ativação.
6. **Downgrade, suspensão ou alteração financeira:** não cancelar silenciosamente memberships ativos ou gerar fatura por causa de convites reservados; especificar transições/restrições e comunicação em CORE07-D05/D08, CORE08/FIN/CON. Tratar o assento já ocupado com regras de downgrade aprovadas, não por analogia.
7. **Sem retenção indevida:** dados pessoais mínimos do convite, sem token em logs; auditoria, privacidade e expurgo seguem CORE06/LGPD, não nova política presumida.

## 4. Pontos a definir posteriormente

- Se um convidado já tiver membership suspensa ou reativável, regra de reserva/ocupação e reativação.
- Reenvio, troca de e-mail, duplicatas e convites conflitantes, expiração e prova de identidade (CORE07-D04 e CORE04).
- Mudança de plano durante convite pendente, ordem de liberação em downsizing, titularidade/último administrador, concessão de adicionais, números finais dos planos pagos (CORE07-D06, CORE08-D09, FIN/CON).
- Modelo físico, concorrência/idempotência, segurança, critérios de testes e evidências (CORE01, CORE02, CORE03, CORE10, SEG-01/02, INT).
- Mesmo se A for ratificado **CORE07-D05 integral permanecerá ABERTA** até especificação física, interfaces, exceções e homologação.

## 5. Manifestação futura

**Questão para aprovação:** escolher **Modelo A** como diretriz documental para convites/assentos, sem encerrar CORE07-D05, sem prometer assentos adicionais e sem autorização de programação.

**Nenhuma aprovação de CORE07-D05 é inferida da aprovação GOV02.** Este documento apenas prepara a próxima deliberação. Preservar branch do Codex, `main`, VPS, produção e demais bloqueios.
