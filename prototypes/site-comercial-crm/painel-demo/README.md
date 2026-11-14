# CRM UX-P0 — Jornada de demonstração Cliente e Administração Imagine

**Status:** protótipo para homologação visual, SEM contas operacionais e sem direito a acesso/contratação. Isolado da Laravel DEV e sem deploy.

## Caminho navegável

`site-comercial-crm/index.html` → link "Painel de demonstração" → `painel-demo/index.html`. Também se chega após `fluxo-demo/index.html` (cadastro e OTP apenas fictícios), pelo link para os painéis demonstrativos.

Na entrada, existem **dois botões sem formulário de credenciais**:
- **Cliente:** visão geral, leads, Kanban, automações, relatórios, configurações.
- **Administrador Imagine:** visão geral, empresas, assinaturas, aprovações, auditoria, configurações.

Os botões apenas alteram o estado de apresentação da **mesma página**, exclusivamente em memória JavaScript, sem senha, permissão, sessão, cookies, armazenamento, conexão, backend, CNPJ ou identidade. Trocar a demonstração não autentica ninguém.

## Comportamento

- Layout de referência Imagine (logo original do Portal Node, laranja #F9941D, grafite #171B19, base clara, navegação discreta e cartões leves).
- Menus responsivos e foco visível; navegação por teclado; controles móveis; avisos de operações bloqueadas com Escape e retorno do foco; dados inventados.
- Indicadores e Kanban são amostras estáticas, sem arrastar ou persistir. Nenhuma oportunidade real, lead, documento, telefone, e-mail, valor contratual ou assinatura é utilizada.
- Todos os controles de criar, exportar, importar, alterar e salvar exibem a impossibilidade de operação. Não há rede, formulários ou efeitos no servidor.

## Integridade, isolamento e testes

- HTML/CSS/JS do painel ficam em `painel-demo/`; CSP estrita `default-src none`, `style/script/img self`, `connect/form-action none`, `noindex/nofollow`.
- `tests/test_preview.py` verifica ausência de formulário/captação/armazenamento, marca e links entre as páginas.
- `tests/dashboard_journey_browser.py` testa Chromium nas larguras 320/375/768/1280, percorrendo comercial → Cliente → Kanban → relatórios → Administração → empresas/assinaturas e modal/foco.
- A CI `ci-crm-commercial-preview.yml` inclui três novos recursos no bundle `manifest.sha256`, validados por `staging/verify_bundle.py` com allowlist.
- Os modelos Nginx e os experimentos Docker HTTP/TLS entregam apenas esses arquivos com Basic Auth fictício e cabeçalhos restritos. Os certificados temporários de CI não são certificados operacionais.

## O que falta para estes painéis serem funcionais

**Cliente:** autenticação/MFA real, convite/assento, sessão/identidade global, escolha de organização autorizada e verificação por request/consulta, ACL por recurso/ação, RLS com papel DB sem bypass, estado de assinatura/trial real, recuperação segura e vínculo das APIs de leads/funil/automações/relatórios.

**Administrador Imagine:** acesso administrativo segregado, mandatos de suporte, MFA reforçado, verificação independente de autorização, auditoria imutável, isolamento e visibilidade minimizada, proteção de dados e processamento dos pagamentos reconciliados. Admin não é login do cliente com um campo `role=admin`.

**Comercial:** homologação de valores finais/recursos, contrato, cobrança idempotente, PSP, fatura/NFS-e, transições de direitos e expiração. Nenhum desses itens está resolvido por esta prévia.

**Infra:** staging protegido real na VPS Leaseweb, DNS/TLS, segurança do host, isolamento, segredo fora do Git, backup/restore, rollback e aceites formais.

## Fronteira de liberação

Manter **DRAFT / NO-MERGE / NO-DEPLOY** para o protótipo até revisão técnica. As issues [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [UX #416](https://github.com/rafhael100/imagine-crm/issues/416) continuam abertas. O aceite das telas demonstrativas não significa aceite de IAM, DB ou produção.
