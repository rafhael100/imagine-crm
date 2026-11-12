# CRM F3-119 — Vigência e expiração de proposta fictícia

**Data:** 11/11/2026. **Ambiente:** somente DEV, PHP puro e testes. **Referências:** CRM-REV-06 VEN-R11/R15, VEN-T10, VEN-P05.

## Regra matemática implementada

`SyntheticProposalValidityPreview` recebe três instantes **inventados** e estritamente UTC: início declarado da versão, término declarado da janela e instante observado no laboratório. O formato é `YYYY-MM-DDTHH:mm:ssZ`, anos 2000–2099, com validação de calendário e comparação estrita. A janela precisa ter duração positiva e estar dentro do limite **puramente técnico do laboratório** de 366 dias; esse teto não constitui uma política de validade comercial.

As fases descritivas são `before_issue`, `within_validity` e `expired`. No instante exato do término, a fase é `expired`. Retorna apenas os instantes em segundos UTC, a duração e os segundos até o início/término, sem URLs, cliente, identidade, token, documento ou valor.

## Segurança e produto

**Uma versão dentro da janela não é uma proposta aceitável:** mesmo nesse estado, não há comprovação de envio, link autorizado, validade contratual, aceite do destinatário, alçada de aprovação ou assinatura. Todas as capacidades de enviar, aceitar, renovar, assinar, cobrar e gravar são `false`; `validity_policy_verified`, `link_authorized` e `acceptance_verified` também são `false`.

`SyntheticProposalValidityPreviewTest` verifica instante anterior, início, meio da vigência, último segundo, expiração exata e posterior; limite máximo de laboratório; data inexistente, fuso não canônico, datas invertidas, período vazio e mensagens de erro que não revelam entrada.

A operação real de propostas exige revisão de identidade, acesso, expiração, versão exata, revogação de token, renovação, trilha auditável e política por empresa (VEN-P05/CORE/SEG/DBA/LGPD). Sem endpoint, SQL, PDF, aceite real, cobrança, VPS, custos, `main` ou produção.
