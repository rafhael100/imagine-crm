# CRM-DEC — Cloudflare Pro quando vantajoso e três VPS independentes para o CRM Imagine

**Data:** 03/11/2026.
**Manifestação do responsável:** Cloudflare Pro poderá ser contratado se oferecer diferenciais proporcionais de segurança ou outros benefícios ao CRM; também será possível contratar terceira VPS para preservar a VPS de desenvolvimento sem alterações.
**Status:** **DIRETRIZES DE PLANEJAMENTO APROVADAS**, sem contratação e sem homologação física/orçamentária.

## 1. Cloudflare
- **Não restringir o CRM Imagine ao plano Free por princípio de economia.** Avaliar **Pro como preferência de segurança para a entrada em produção**, condicionado a ganho relevante, compatibilidade técnica, custo total e homologação. Pro **não está contratado** nem foi dado aceite financeiro automático.
- Diferenciais documentados em novembro/2026: Cloudflare Managed Ruleset e OWASP Core Ruleset disponíveis em Pro, não Free; 20 regras WAF customizadas em Pro vs 5 em Free; Super Bot Fight Mode com exceções controláveis em Pro, ao contrário de Bot Fight Mode do Free.
- Verificar falsos positivos para login, aplicações SPA, webhooks, OAuth, gateways, verificações de saúde, APIs legítimas, clientes externos e integrações; exigir regras de exceção escopadas, sem desproteger endpoints.
- Preço público de referência: **US$25/mês em contrato mensal, ou US$20/mês faturado anualmente**; confirmar por zona/domínio, tributos e escopo efetivo.
- O Pro não substitui MFA, RBAC/RLS, segurança de APIs, rede privada/túnel, proteção de origem, antimalware ou backups.

Fontes: https://developers.cloudflare.com/waf/managed-rules/ ; https://developers.cloudflare.com/waf/custom-rules/ ; https://developers.cloudflare.com/waf/feature-interoperability/ ; https://www.cloudflare.com/plans/

## 2. Separação das três VPS

Configuração candidata de planejamento com **3 servidores/VPS distintos**:
- **VPS Desenvolvimento/Homologação** (Leaseweb VPS 1 como porte de referência): exclusiva do ambiente de desenvolvimento, com segredos, dados e credenciais independentes; **não migrar, formatar ou reaproveitar como banco produtivo** para economizar.
- **VPS Produção — Laravel** (Leaseweb VPS 2 como porte de referência): backend/API do CRM, jobs e cache conforme capacidade e durabilidade homologadas.
- **VPS Produção — PostgreSQL** (Leaseweb VPS 1 como hipótese sujeita a IOPS/RAM/capacidade): isolada da aplicação e sem exposição pública do banco; comunicação VPN/TLS/firewall ou arquitetura homologada.
- Portal, n8n, agentes técnicos e APIs principal/contingência de leads permanecem separados, conforme requisitos anteriores. O desenvolvimento não pode compartilhar dados/segredos reais de produção.
- Três VPS **não significam alta disponibilidade do CRM**; o E1B permanece arquitetura-alvo condicional à homologação e restauração.

## 3. Custo ilustrativo e limites

Se preços de terceiros divulgados para EUA fossem confirmados: VPS 1 dev US$6,29 + VPS 2 app US$9,89 + VPS 1 PostgreSQL US$6,29 = **US$22,47/mês de computação**. Com Cloudflare Pro em cobrança mensal, **US$47,47/mês** antes de qualquer backup, object storage, monitoramento, VPN, antimalware, impostos e operação. **Não são cotações oficiais nem TCO real.**

O alvo anterior de US$40–60/mês do CRM é **referência preliminar sem teto financeiro aprovado** e fica apertado se Pro e três VPS forem necessários; **não reduzir proteções essenciais para forçar a conta**. Propor orçamento revisado com custos oficiais e contingência, preservando diferenças entre CRM e ecossistema completo.

## 4. O que está e não está autorizado
**Aprovadas:** permissão de planejar três VPS distintas sem reaproveitar a de desenvolvimento; permissão de considerar o Cloudflare Pro mesmo com custo superior, se houver proteção/benefícios proporcionais.

**Pendentes:** aprovação financeira do plano Cloudflare Pro, escolha final de plano/região/VPS, contratualização, orçamento global, validação de capacidade do banco, segurança/rede e RPO/RTO, teste de recuperação e aceitação do risco E1B.

**Não autorizado:** compra, pagamento, provisionamento, mudança de DNS ou conta Cloudflare, login/SSH, migração, instalação, programação, execução de testes reais. ARQ-01 continua P0 parcial. 
