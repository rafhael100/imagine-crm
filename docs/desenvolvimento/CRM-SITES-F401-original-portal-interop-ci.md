# CRM-SITES-F401 — Emissor original do Portal → CRM F400 → Sites F398 (CI)

**DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / NO-PII.**

## Origem efetiva do wire

O CI F401 não usa apenas uma classe do próprio CRM para representar as duas pontas. Ele extrai o emissor real de código do Portal da **PR #837** na SHA fixa `7dd716fb645b4b133e4c83320fce1ec0a9eda27e` e carrega a classe original PortalSitesCrmSignedBindingCommandLab. O workflow também fixa o emissor Sites F398 na SHA `1e24f47f7dd96c0e5d4b68f5a71aed7c861179f4`. Ambas são verificadas por git rev-parse antes dos testes.

1. Executa script standalone negativo do Portal F401 com chaves falsas e protocolo fixo, sem composer ou dependências de aplicação do Portal.
2. No teste real PHP/Laravel do CRM PG16, substitui apenas o signer de mensagens de CI pelo original do Portal F401. Em cada mensagem exige igualdade completa de campos, canonização e HMAC em relação ao verificador/fixture signer original CRM F400.
3. Exerce o gateway F400 com chaves autoritativas registradas pelo proprietário, receipt inbox anti-replay, pedido server-owned F399 e um lead original Sites F398 sob RLS do CRM; repetição não duplica; revogação atômica impede novas capturas.
4. Os fluxos sintéticos de pagamento/aceite e a fonte das chaves de teste continuam explicitamente não operacionais.

**Sem integração HTTP real ou S2S autenticado:** a originalidade se refere a arquivos/implementação de repositórios diferentes em GitHub CI, não a execução de serviços conectados em produção. HMAC de exemplo é conhecido publicamente; não autentica qualquer fornecedor ou pessoa. Não se gera compra, cartão, token real, provisionamento físico, webhook, login, DNS ou deploy.

Workflow `.github/workflows/ci-crm-sites-f401-pg16.yml`, ainda exigindo `SEC-CI-02` original, conta restrita sem grants e `--fail-on-skipped`. Revisões SEG/DBA CRM #27, CORE/LGPD #259, GO #557 e Sites #38 permanecem abertas.