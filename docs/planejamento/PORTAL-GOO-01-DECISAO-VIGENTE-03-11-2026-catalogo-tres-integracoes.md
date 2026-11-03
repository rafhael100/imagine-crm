# PORTAL-GOO-01 — Decisão vigente de 03/11/2026: catálogo inicial de três integrações Google

**ESTADO: APROVADO PELO RESPONSÁVEL — SUBSTITUI A DECISÃO ANTERIOR DE CINCO INTEGRAÇÕES.**
**Fase:** somente planejamento, sem programação, acesso a contas ou configuração de conectores.

## Decisão

Após considerar inicialmente cinco serviços, o responsável determinou expressamente: **“entao vamso retirar o Para Google Ads e Perfil da Empresa no Google,”**. No contexto da discussão, isso significa **retirar Google Ads e Perfil da Empresa no Google do escopo de integrações do Portal Imagine**.

O catálogo inicial vigente de **Portal Imagine → Integrações** passa a conter somente:

| Serviço | Finalidade | Interface |
|---|---|---|
| Google Analytics 4 (GA4) | Acessos, fontes de tráfego e eventos | Explicação + botão **Realizar integração** |
| Google Search Console | Consultas, cliques, impressões, SEO e indexação | Explicação + botão **Realizar integração** |
| Google Tag Manager | Contêineres, tags e eventos com controles de privacidade | Explicação + botão **Realizar integração** |

**Fora do escopo atual:** Google Ads e Google Business Profile / Perfil da Empresa no Google. Não exigir developer token Ads nem aprovação de API Business Profile como pré-requisito de lançamento. Não implementar serviços desses produtos sem nova decisão do responsável.

## Regras preservadas

O cliente escolhe ou cria sua própria conta Google, efetua login pelo fluxo oficial, concede permissões e seleciona conta/propriedade/container adequado. **A Imagine constrói e mantém os conectores e automatiza o que for possível depois da autorização do cliente**. Não coletar senhas Google. Separar dados por empresa/site, evitar tags duplicadas e proteger tokens/escopos. **Autorização OAuth não é consentimento de visitantes para rastreamento**: atender à LGPD e às preferências aplicáveis antes de ativar medição não essencial.

SEO básico, monitoramento técnico e PageSpeed/CrUX por URL podem ser especificados separadamente; não constituem obrigatoriamente outros cartões autenticados do catálogo de três ferramentas. O planejamento de métricas e SEO continua sujeito a detalhamento.

## Prevalência documental

Esta decisão é **posterior e prevalece** sobre a seção 10 do arquivo `PORTAL-GOO-01-integracoes-google-propriedades-contas-clientes-autosservico.md`, que anteriormente aprovava cinco integrações, e sobre o registro de cinco integrações no `PORTAL-01-matriz-pendencias-plataforma-sites.md`. Essas seções antigas são **histórico substituído**, não especificação vigente.

**Nenhuma programação ou integração real foi iniciada.**
