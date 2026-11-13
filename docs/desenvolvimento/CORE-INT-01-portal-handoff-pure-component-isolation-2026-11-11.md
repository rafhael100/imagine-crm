# CRM CORE-INT-01 — Integração isolada do contrato técnico Portal → CRM na DEV

**Objetivo:** reduzir a dependência da pilha antiga de PRs F3-40 a F3-49 sem importar migrations, IAM experimental, concorrência ou o receptor HTTP de laboratório. Esta branch nasceu diretamente da DEV e contém **cinco componentes puros** mais três suítes PHPUnit e dois smokes PHP independentes.

## Componentes

- `PortalServiceHandoffInspector` e `PortalServiceHandoffAssessment`: validam HMAC-SHA256 somente sobre metadados, com domínios `deliver` e `lookup` independentes, comparação de organização/site/form/destino com contexto passado pelo servidor, nonce e janela temporal. **Não autenticam o chamador nem comprovam replay-safe.**
- `PortalTechnicalEnvelopeDecoder`: exige JSON plano de até 2.048 bytes, exatamente oito campos técnicos, sem PII ou objetos; rejeita chaves repetidas, inclusive representadas com Unicode escapado, antes de validar a estrutura com o inspector já existente na DEV.
- `PortalIngressPreparationBoundary` e `PortalIngressPreparationAssessment`: compõem JSON, binding independente e HMAC, retornando somente um código de resultado. **Todas as capacidades de aceitar/gravar/enfileirar/confirmar/abrir rota são invariavelmente falsas.**

## Evidências e compatibilidade

Os testes PHPUnit de contrato negativo incluem replay repetido sem autoridade, chaves JSON duplicadas, anexação de `email`, `phone`, `consent` ou `payload`, MAC ou operação adulterados, empresa/site/destino trocados e carimbo temporal expirado. Dois smokes PHP puros rodam em **8.3 e 8.4**; os testes do Laravel rodam em **8.4**, conforme `composer.lock`. O workflow `ci-portal-ingress-core-isolation.yml` complementa os gates F0, PostgreSQL/RLS, Sites e Agentes IA já existentes. Nenhuma homologação do Laravel inteiro em PHP 8.3 é inferida.

## Limites e não-go

**Este código não cria endpoints, controllers, middleware, service providers, tabelas, filas, recibos duráveis, armazenamento de lead, identidade de serviço nem permissão.** Uma assinatura válida por chave sintética não comprova identidade, autorização multiempresa ou persistência. Um nonce bem formatado não é nonce registrado em ledger. Consentimento e proteção de dados de contato não estão implementados aqui. F70 Imagine Comercial, CORE/IAM, Segurança, DBA, LGPD, homologação staging e revisão independente permanecem bloqueados para operação. Não houve alterações na `main`, em produção, VPS, DNS, segredos, dados reais ou fornecedores.

Esta é uma PR **DRAFT** com escopo de integração de domínio inspecionável, não liberação de produção; a evidência deve ser confirmada na SHA final antes de qualquer revisão ou merge.
