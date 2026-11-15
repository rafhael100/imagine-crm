# CRM-STG-SEC-01 — Nginx de prévia privada não aceita hostname externo

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-LOGIN / NO-CHECKOUT**

A PR candidata atual expõe apenas `127.0.0.1:18080`, porém usava `server_name _` e permitia encaminhamento de qualquer cabeçalho `Host` ao Laravel. Uma publicação acidental via proxy reverso poderia transformar a página de prévia privada em site publicamente navegável.

## Mitigação implementada e prova de CI

- Nginx no Compose privado fixa `server_name 127.0.0.1` e retorna **HTTP 421** quando o `Host` normalizado não coincide. Não consulta nem confia em `X-Forwarded-Host`, `X-Forwarded-Proto` ou claims de usuário/tenant.
- `X-Robots-Tag: noindex, nofollow, noarchive` é emitido em todas as respostas, inclusive health/404/421, além de `Cache-Control: no-store` e dos cabeçalhos anti-frame existentes.
- Checker estático do Compose rejeita remoção do gate de hostname/noindex, preservando o binding de porta local `127.0.0.1:18080:8080` e ausência de serviços financeiros.
- Smoke HTTP CI, em Nginx real do runner, exige `/up` 200 e homepage legítima 200 com `Host: 127.0.0.1`, rejeitando `Host: preview.customer.example` com **421** nos caminhos `/`, `/demo`, `/up` e `/api/leads`, mesmo se o cliente adicionar `X-Forwarded-Host: 127.0.0.1`. A presença de `X-Forwarded-Host: preview.customer.example` **sem modificar o Host verdadeiro** não deve alterar a autorização.
- Mesma HEAD também executa o Kanban fictício persistido, recuperação da base migrada, testes Laravel e RLS, Asaas mock, Sites, Agents e negação de rotas no perfil production.

## Limites

Isso é uma **redução de exposição acidental**, não autenticação. Um proxy controlado por terceiros ainda poderia reescrever `Host` para `127.0.0.1`; esse controle **não substitui** VPN, rede privada, TLS, identidade autenticada nem autorização de ambientes de teste. A configuração não é guia de publicação pública e continua sem uso na Leaseweb.

Bloqueios para acesso de cliente e go-live: SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416), concorrência [#564](https://github.com/rafhael100/imagine-crm/issues/564), release [#557](https://github.com/rafhael100/imagine-crm/issues/557).
