# CRM F3-121 — Matriz de negação integrada das funcionalidades comerciais

**Data:** 11/11/2026. **Escopo:** teste PHPUnit que conecta **dezesseis** componentes de laboratório existentes, sem endpoint nem dados reais.

## Objetivo

A evolução paralela de cadastro de itens, versões e transições de propostas, previsão ponderada, etapas de funil e seus campos obrigatórios, reaberturas, unicidade de eventos, validade, scoring, recorrência de tarefas e janelas de assinatura não pode permitir que a alegação positiva de um módulo vire **permissão operacional** em outro.

`SyntheticCommercialCapabilityDenyMatrixTest` executa **dezesseis componentes PHP reais** com exemplos artificiais de estados que parecem favoráveis: proposta `accepted`, negócio `won`, probabilidade 100%, evento único, proposta dentro da vigência, score quente, janela de trial ativa, cancelamento informado e 30 dias após vencimento. A expansão F3-126 inclui sequência de proposta declarada aceita, transição de oportunidade declarada ganha, todos os campos de etapa ilustrativamente preenchidos e recorrências futuras; F3-129 acrescenta desconto fora da política com aprovação gerencial meramente alegada e pré-validação de envio com bloqueios. Nenhuma situação concede direito de aceitar, mover, reconhecer receita, enviar propostas ou agendar serviços.

A suíte verifica todas as capacidades `can_*` permanentemente falsas, marcas de verificação/proveniência sempre negadas, `recognized_*` e `verified_event_count` sempre zero, além de impedir IDs/PII no JSON da saída. Garante também que as classes não aparecem como endpoints no `routes/web.php` atual.

## Interpretação

A matriz **não transforma dados falsos em autenticação, pagamento, receita ou venda verificada**. Seu valor é detectar regressões locais caso outra mudança passe a conceder autorização somente porque o status fictício tem a palavra `accepted`, `won`, `within_validity` ou uma probabilidade de 100%.

A liberação de recursos reais continua dependendo de identidade/tenant, RLS, escopo por recurso, alçada de desconto, auditoria, prova de aceite e aprovação externa VEN/CORE/SEG/DBA/LGPD. Não foi habilitado banco, rota, autorização, API, despesas, VPS, `main` ou produção.

## Expansão F3-126 — 11/11/2026

As quatro novas projeções F3-122, F3-123, F3-124 e F3-125 agora são exercitadas **na mesma matriz de CI**, além de seus testes unitários individuais. A matriz associa casos positivos fictícios a negações operacionais, inclusive `requirements_met=true` sem `can_move`, `claimed_status=accepted` sem `can_accept`, `claimed_status=won` sem `recognized_wins` e `upcoming_count=2` sem `can_schedule_job`.

Esta extensão só protege contra regressões determinísticas dos redutores em memória; não substitui concorrência com PostgreSQL, política de calendário por empresa, controle de acesso, assinatura, comprovante financeiro ou aprovação independente.

## Expansão F3-129 — 11/11/2026

A matriz agora exerce 16 componentes Core PHP, com casos positivos declarados mas não autenticados de preço/desconto: `manager_approval_claimed=true` e proposta em rascunho. Confirma que `manager_approval_verified=false`, o bloqueio `manager_approval_unverified` permanece presente e `can_issue_proposal/can_send=false`. A validação não substitui aprovação gerencial independente, comprovação de identidade ou contrato de pagamento.
