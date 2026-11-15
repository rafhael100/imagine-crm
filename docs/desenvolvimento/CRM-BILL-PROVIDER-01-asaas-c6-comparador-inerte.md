# CRM-BILL-PROVIDER-01 — Comparação controlada Asaas × C6 Bank

**DRAFT / NO-MERGE / NO-DEPLOY / NO-CHARGE / NO-PII / NO-CONFIG OPERACIONAL**

## Decisão de negócio

A direção deseja preservar o Asaas atualmente considerado para o CRM e poder acrescentar o C6 Bank se a negociação trouxer taxas melhores, com possibilidade futura de selecionar o gateway mais econômico **sem migração implícita de contratos existentes**.

Este recorte implementa o **contrato puro de comparação**, em PHP, antes de qualquer API ou configuração financeira. `GatewayFeeComparisonPreview::compare` recebe o valor de referência em centavos BRL, duas tabelas de taxa **fictícia e explicitamente fornecidas ao teste**, e um eventual gateway já vinculado (`asaas` ou `c6`). Ele retorna uma indicação *apenas de simulação*, detalhamento de taxa e líquido estimado em centavos, sem nenhuma chamada de rede.

- Cálculo inteiro em centavos, arredondando o componente variável para cima, sem float; valida faixa, moeda, taxas, flags e chaves exatas de cada candidato.
- Asaas é o candidato inicial obrigatório. O C6 permanece **opcional** e inativo para comparação quando não tiver cotação simulada completa. Gateway desligado não é sugerido.
- Se duas taxas fictícias forem iguais, o Asaas prevalece por critério determinístico. Isso não representa aprovação comercial, melhor taxa real, disponibilidade ou qualidade do fornecedor.
- Se há pedido/assinatura já vinculado, o código **não sugere troca de provedor** mesmo que a cotação de laboratório do concorrente seja inferior.
- Todos os resultados devolvem `fees_verified_with_provider=false`, `requires_human_commercial_review=true`, `can_create_customer=false`, `can_create_order=false`, `can_issue_invoice=false`, `can_create_charge=false`, `can_switch_existing_provider=false`, `can_activate_subscription=false`.
- O modelo não tem token, credenciais, CNPJ, clientes, cobrança, webhooks, banco, persistência, endpoint nem permissões. Não é um motor de pagamentos ativo.

## Testes

`tests/Unit/GatewayFeeComparisonPreviewTest.php`: comparação com taxas fictícias, minimização do custo estimado sem autorizar cobrança, contrato Asaas fixo sob C6 mais barato, opção inativa, ambos inativos, empate, arredondamento BRL e rejeição de moedas, valores, formato/keys e taxas hostis.

## Pendências para uso real

A configuração de taxas efetivas depende da negociação/comprovantes comerciais e homologação dos contratos Asaas e C6; configurar meio de pagamento por operação; autorização da direção, trilha de auditoria de alteração e segurança de segredos; idempotência, webhooks, estados e conciliação próprios; seleção de gateway para novo contrato; política de preservação de contratos existentes; retries e reversões; testes de Sandbox/ambiente homologado; NFS-e. **Nenhuma dessas etapas foi ativada ou presumida nesta PR.**

Permanece o bloqueio formal da [issue #557](https://github.com/rafhael100/imagine-crm/issues/557), [#27](https://github.com/rafhael100/imagine-crm/issues/27) e [#259](https://github.com/rafhael100/imagine-crm/issues/259).
