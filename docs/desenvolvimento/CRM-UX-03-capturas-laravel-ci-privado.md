# CRM-UX-03 — Pacote visual de homologação, gerado do Laravel real no GitHub CI

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-REAL-CUSTOMERS / NO-PAYMENTS**

## O que foi acrescentado

A página comercial e as duas demonstrações do Kanban são renderizadas por **Nginx + PHP/Laravel + PostgreSQL16 de laboratório** dentro do runner descartável. Até aqui os checks verificavam texto, headers, banco e CSS, mas a direção não tinha capturas visuais comparáveis para avaliar a identidade e navegação.

O workflow de smoke agora executa `scripts/ci/capture-crm-private-ux-review.sh` **depois** das provas de dados, RLS, recuperação, segurança HTTP e autorização negada. O script exige repositório esperado, runner GitHub, `CRM_UX_SCREENSHOT_LAB=synthetic-only` e painel fictício contendo apenas cartões `EXEMPLO_*` do tenant de demonstração.

Em **Chrome/Chromium real no runner**, com perfil novo e removido após cada imagem, gera oito arquivos PNG temporários:

1. Home comercial PT — janela desktop 1440×900
2. Home PT — janela mobile-like 390×844
3. Home comercial EN — desktop
4. Home comercial ES — desktop
5. Kanban visual PT — desktop
6. Kanban visual PT — janela mobile-like
7. Kanban PostgreSQL fictício PT — desktop
8. Kanban PostgreSQL fictício PT — janela mobile-like

O browser verifica que cada arquivo PNG existe e não está vazio. O upload por `actions/upload-artifact` **fixado no SHA oficial `ea165f8d65b6e75b540449e92b4886f43607fa02` (v4.6.2)** publica um pacote que expira em **7 dias**. Só as capturas com rótulos fictícios são enviadas; não há backup, token, .env, SQL dump, segredo ou conteúdo de cliente no artefato. Os perfis Chromium são apagados e os volumes do CI destruídos.

## Limites explícitos

- Os screenshots são **evidência para revisão humana**, não auditoria WCAG. A captura em janela de 390px não comprova viewport de emulação mobile real nem interações táteis, zoom a 200%, leitores de tela ou teclado completo; a validação independente deve continuar.
- As páginas são prévias privadas, com planos de referência não homologados. Não permitem cadastro, login, trial, checkout, edição ou contatos.
- Artefatos de repositório público podem ser visíveis a terceiros durante a retenção; as imagens contêm apenas material fictício e uma logomarca original já pública. Nunca colocar dados reais ou capturas de clientes nessa rotina.
- Sem publicação de site, domínio, VPS ou custos externos adicionais.

A UX [#416](https://github.com/rafhael100/imagine-crm/issues/416) continua aberta até comparação visual e aceite independente, assim como SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259) e lançamento [#557](https://github.com/rafhael100/imagine-crm/issues/557).
