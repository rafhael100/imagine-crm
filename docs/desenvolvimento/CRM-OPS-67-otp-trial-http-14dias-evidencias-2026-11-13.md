# CRM OPS-67 — Ciclo HTTP OTP → trial e janela de 14 dias (13/11/2026)

**Objetivo:** trilha auditável de duas entregas de código **merged exclusivamente em DEV**. Ambas executam o kernel HTTP real do Laravel em PHPUnit, com códigos criptográficos Core e identificadores totalmente sintéticos; **nenhum endpoint é publicado**. Isso não é homologação de identidade real ou trial de cliente.

## Baseline e escopo

- DEV após os dois merges: 44801218d0b28d83658e2f0b7117d00a339c946a.
- Main preservada: e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012.
- F3-258 [PR #510](https://github.com/rafhael100/imagine-crm/pull/510), merge DEV a7189ad95c772bd5ec86f65bb0bbfb32ac75d6ce. Sequência OTP local antes do marcador fictício de trial, sinal de uso único por canal, negação de replay/cross-channel, expiração de 900s, JSON não confiável recusado e prova de não publicação das rotas. Também estendeu a verificação Composer sem autoload-dev para a nova classe de testes.
- F3-259 [PR #511](https://github.com/rafhael100/imagine-crm/pull/511), merge DEV 44801218d0b28d83658e2f0b7117d00a339c946a. Depois do ciclo HTTP, PHPUnit lê o snapshot Core do trial: início de 14 dias, fim exato em 14 × 86.400s, 7 franquias ilustrativas, impossibilidade de reiniciar o marcador de CNPJ fictício com outro OTP no mesmo objeto PHP, deduplicação entre dois canais, clock inválido rejeitado. Snapshot não é incluído em resposta HTTP.

## Cinco checks por PR na HEAD exata (todos completed/success)

| Etapa | SHA testada da PR | Execuções |
| --- | --- | --- |
| F3-258 [#510](https://github.com/rafhael100/imagine-crm/pull/510) | 7b56e620ad21c179ad89122263617910826090f3 | [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43490722994) · [PG16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43490722977) · [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43490723069) · [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43490723007) · [No-dev](https://github.com/rafhael100/imagine-crm/actions/runs/43490722983) |
| F3-259 [#511](https://github.com/rafhael100/imagine-crm/pull/511) | 855c6879fb6370a725f206b41d92fbb9ac68c7bb | [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43491684433) · [PG16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43491684543) · [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43491684410) · [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43491684430) · [No-dev](https://github.com/rafhael100/imagine-crm/actions/runs/43491684730) |

A HEAD DEV pós-merge da F3-258 também passou [F0](https://github.com/rafhael100/imagine-crm/actions/runs/43491003839), [PG16/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43491003934), [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43491003849), [AIA](https://github.com/rafhael100/imagine-crm/actions/runs/43491003973). Para F3-259, os checks na HEAD da PR foram confirmados antes do merge; verificações do novo commit DEV são independentes e precisam ser checadas no GitHub antes de declarar uma validação pós-merge.

## O que ainda falta para homologação privada funcional

1. Dados de teste autorizados, VPS/staging privado com credenciais próprias e segregação verificável de produção.
2. Identidade de serviço e humana realmente autenticada, vínculo de contato, autorização por empresa e políticas de sessão; todos os sinais F3-258/259 são apenas memória PHP.
3. Consumo atômico, antiabuso, HMAC/rotação de chaves, proteção PostgreSQL RLS com papéis não privilegiados, concorrência e unicidade de trial durável, backup/restore e auditoria.
4. Transporte OTP real homologado, observabilidade, consentimento e retenção/exclusão de dados segundo LGPD e requisitos de produto.
5. Revisão competente/independente e aceite expresso conforme [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) e [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259); UI conforme [UX #416](https://github.com/rafhael100/imagine-crm/issues/416) ainda sob revisão.

**NO-GO mantido:** esta documentação não autoriza mudança em main, VPS ou produção, endpoint público, dados de cliente, e-mail/WhatsApp real, criação de conta, trial, cobrança, grants nem despesas.
