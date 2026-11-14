# CRM-UX-01 — Alinhamento das duas interfaces Laravel ao Imagine Design System V1

**DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-ACCESS-GRANT**

A diretriz de UX aprovada em [#416](https://github.com/rafhael100/imagine-crm/issues/416) estabelece a identidade original do Portal Node no CRM. Fonte canônica: [tokens CSS](https://github.com/rafhael100/imagine-portal/blob/design/imagine-node-original-visual-v1-2026-11-12/design-system/tokens.css) e [Experience System](https://github.com/rafhael100/imagine-portal/blob/design/imagine-node-original-visual-v1-2026-11-12/design-system/experience-system.md).

## Entrega neste recorte

- Novo parcial Blade `resources/views/partials/imagine-staging-tokens.blade.php` com aliases locais da paleta e da tipografia aprovadas: **laranja #F9941D, grafite #171B19, superfície #F7F7F8, branco #FFFFFF, borda #E8E8E8, Arial/Helvetica**, sidebar 240px, sombras leves e mínimo de 44px para toques. Não importa o CSS/backend de outro repositório.
- Páginas comerciais e Kanban passam a usar os mesmos tokens sem gradiente de fundo e sombras pesadas. Painéis permanecem responsivos, 3 idiomas e com a segurança original inalterada.
- Links de idioma, navegação e CTA passam a usar altura mínima de 44px. Foco do teclado destacado por `focus-visible`, link `skip to content` em português/inglês/espanhol, âncora de main no painel e `aria-current=location` no menu de seção.
- Teste PHPUnit inspeciona as fontes e guardas de acessibilidade. Smoke GitHub Actions valida CSS canônico **renderizado via Laravel+Nginx**, idiomas, link de pular conteúdo e indicação de navegação ativa sem acessar dados reais.
- O aplicativo segue em staging privado `127.0.0.1:18080`, com rotas de demonstração read-only e flag de ambiente; `main`, VPS e clientes intocados.

## O que ainda NÃO pode ser declarado concluído

A **logomarca original PNG**, acessibilidade auditada em dispositivos reais, capturas comparativas, contraste por ferramenta apropriada e revisão visual independente continuam pendentes. Este recorte traz os tokens e as correções verificáveis de navegação, mas **não encerra a issue #416**. O logo Node não foi copiado por não haver caminho confiável de transferência binária via integração GitHub nesta rodada; não utilizar substituto fabricado como se fosse o oficial. Também não são homologadas páginas operacionais de login/edição, pois não estão liberadas.

Continuam abertos: SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), CORE/LGPD [#259](https://github.com/rafhael100/imagine-crm/issues/259), UX [#416](https://github.com/rafhael100/imagine-crm/issues/416) e lançamento [#557](https://github.com/rafhael100/imagine-crm/issues/557).
