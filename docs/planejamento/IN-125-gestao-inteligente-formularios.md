# IN-125 — Gestão Inteligente de Formulários dos Sites Imagine

**Estado:** APROVADA funcionalmente em 01/11/2026 pelo responsável do projeto (resposta "ok").  
**Fase:** planejamento e documentação; não autoriza programação.  
**Escopo:** sites e landing pages Imagine, Portal Imagine e integração com CRM Imagine.  
**Referências:** IN-123 (painel de integrações) e IN-124 (entrega de leads).

## Objetivo
Permitir que formulários produzidos pela Imagine sejam configurados e mantidos de forma centralizada, integrados ao CRM e acompanhados pelo cliente no Portal Imagine, sem que o próprio cliente altere diretamente o código ou a estrutura publicada do site.

## Funcionalidades aprovadas
1. **Formulários sem limite comercial rígido por site**, sujeitos a política de uso justo, segurança e limites técnicos proporcionais.
2. **Campos personalizáveis**, incluindo nome, telefone, e-mail, seleção, mensagem e campos adicionais de acordo com a finalidade.
3. **Mapeamento dos campos** do formulário para campos correspondentes do CRM Imagine, com validações de compatibilidade.
4. **Consentimento e privacidade:** campos de consentimento configuráveis e registro dos dados de consentimento quando pertinentes, com transparência sobre finalidades e sem pressupor que consentimento é sempre a base legal adequada.
5. **Antispam e abuso:** verificações automáticas para reduzir envios maliciosos, sem descartar indevidamente contatos legítimos.
6. **Notificações de novos leads** ao cliente conforme preferências e permissões, respeitando demais políticas do CRM.
7. **Histórico e rastreabilidade** da origem do lead, incluindo formulário, página e site, quando disponíveis.
8. **Testes automáticos** da integração de formulários antes da publicação e após mudanças relevantes, com registro do resultado, prevenindo contaminação do funil com leads de teste.
9. **Autonomia delimitada do cliente:** poderá acompanhar os formulários e integrações pelo Portal Imagine e solicitar alterações por chamado. A produção/publicação e alterações de código/estrutura do site permanecem sob controle operacional da Imagine, incluindo processos assistidos por agentes de IA e validação adequada.

## Premissa arquitetural reiterada pelo responsável
- **Os sites desenvolvidos pela Imagine não terão banco de dados próprio.**
- O formulário deverá se comunicar com serviços centrais seguros para recepção e encaminhamento ao CRM, em conformidade com IN-124.
- **Onde exatamente residirá a fila persistente de reenvio da IN-124 ainda requer validação arquitetural:** a proposta é um backend central de ingestão com armazenamento durável; não assumir decisão aprovada sobre servidor, banco e tecnologia antes de manifestação específica.

## Itens a detalhar antes da implementação
- Tipos de campo e validações, máscaras e acessibilidade, idioma e mensagem de sucesso/erro.
- Regras de versionamento e revisão de formulários e mapeamentos, inclusive mudanças em formulários publicados.
- Permissões e fluxo de chamados, auditoria e aprovação de alterações críticas.
- Limiares de uso justo, proteção contra spam e proteção de dados pessoais.
- Testes end-to-end e critérios de sucesso, tratamento de falha da API e comportamento quando o recebimento não puder ser confirmado.
- Processamento e anexos, se houver, considerando ausência de banco de dados no site.
- Garantias de entrega, políticas de fila e retenção conforme especificação central da IN-124.

**Aprovação:** funcional. **Programação:** não iniciada.


## Complemento COM-14 — LGPD por finalidade e aceite quando cabível (02/11/2026)

Para atender ao pedido do responsável de incorporar os “aceites da LGPD brasileira”, a gestão de formulários deverá **configurar aviso claro de privacidade, finalidade e controlador do site e campos de consentimento apenas quando a base legal os requerer**, separando contato solicitado pelo visitante de autorização opcional para marketing. Consentimentos não podem vir pré-assinalados nem ser presumidos por envio da mensagem; oferecer mecanismo válido de revogação e preservar comprovante/versionamento proporcional. Integração ao CRM deve manter status e escopo corretos, sem conceder autorização para marketing pela própria Imagine a partir de lead de cliente. O **agente de entrega IA** verificará controles aplicáveis na versão do site e seus testes, bloqueando entrega quando obrigatórios e ausentes; juridicidade da base/aviso deve ser aprovada por responsáveis competentes. **LGPD não impõe consentimento universal a toda coleta**. [COM-14](PORTAL-COM-14-lgpd-aceites-privacidade-consentimentos-checklist.md), [COM-13](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md). Sem programação.
