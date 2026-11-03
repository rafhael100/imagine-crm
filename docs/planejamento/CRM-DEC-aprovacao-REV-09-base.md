# CRM-DEC — Aprovação contextual da CRM-REV-09 como base documental

**Data:** 2026-11-03. **Manifestação literal do responsável:** “ok”.
**Objeto imediatamente submetido:** recomendação de [CRM-REV-09 — Infraestrutura, Segurança, Auditoria e Operações Centrais](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) como **base documental de revisão**, **sem** aprovar fornecedores, permissões finais, custos ou topologia física. A sequência anunciada era iniciar o fechamento efetivo dos P0 do desenvolvimento, começando pela **ARQ-01 — topologia e separação dos serviços**.

## Alcance da aprovação

- **CRM-REV-09 aceita como BASE DOCUMENTAL**, preservando os 34 itens OPS-R01..34 e os limites de segurança, isolamento, resiliência, backup, auditoria e supervisão registrados nas fontes históricas.
- **OPS-P01..18 permanecem abertos**; os seis P0 IN-168 (ARQ-01, ARQ-02, ARQ-03, SEG-01, SEG-02 e AGT-01) **não** estão resolvidos pelo aceite e não se transformaram em novos P0 duplicados.
- Autorizada a **preparação documental de um instrumento de fechamento ARQ-01** para demonstrar as diretrizes lógicas aprovadas, as dependências reais e as decisões de implantação/infraestrutura que faltam, **como nova proposta separada para revisão**.
- Preservar a Opção B já aprovada: agentes técnicos/orquestrador em infraestrutura própria, separada do n8n; CRM e Portal em servidores separados; Sites Imagine em Cloudflare Workers Static Assets; recepção de leads por APIs independentes.

## Não aprovado nem executado

O “ok” não seleciona provedor, orçamento, tamanho VPS, tecnologia de cofre, serviços pagos, credenciais, permissões efetivas, HA, RTO/RPO final, contratos físicos, cronograma ou publicação. **G01–G06, INT-P0 e IN-168 continuam abertos**. Não houve programação, testes, provisionamento, instalação, migração, cobrança ou mudança em produção.

**Regra global:** planejar, versionar e obter **aceite final expresso da versão completa no GitHub** antes de iniciar implementação com Codex.
