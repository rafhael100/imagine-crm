# CRM F3-121 — Matriz de negação integrada das funcionalidades comerciais

**Data:** 11/11/2026. **Escopo:** teste PHPUnit que conecta dez slices de laboratório existentes, sem endpoint nem dados reais.

## Objetivo

A evolução paralela de cadastro de itens, versões de proposta, previsão ponderada, resultados, unicidade de eventos, expiração, scoring e janelas de assinatura não pode permitir que a alegação positiva de um módulo vire **permissão operacional** em outro.

`SyntheticCommercialCapabilityDenyMatrixTest` executa **dez componentes PHP reais** com exemplos artificiais de estados que parecem favoráveis: proposta `accepted`, negócio `won`, probabilidade 100%, evento único, proposta dentro da vigência, score quente, janela de trial ativa, cancelamento informado e 30 dias após vencimento.

A suíte verifica todas as capacidades `can_*` permanentemente falsas, marcas de verificação/proveniência sempre negadas, `recognized_*` e `verified_event_count` sempre zero, além de impedir IDs/PII no JSON da saída. Garante também que as classes não aparecem como endpoints no `routes/web.php` atual.

## Interpretação

A matriz **não transforma dados falsos em autenticação, pagamento, receita ou venda verificada**. Seu valor é detectar regressões locais caso outra mudança passe a conceder autorização somente porque o status fictício tem a palavra `accepted`, `won`, `within_validity` ou uma probabilidade de 100%.

A liberação de recursos reais continua dependendo de identidade/tenant, RLS, escopo por recurso, alçada de desconto, auditoria, prova de aceite e aprovação externa VEN/CORE/SEG/DBA/LGPD. Não foi habilitado banco, rota, autorização, API, despesas, VPS, `main` ou produção.
