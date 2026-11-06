# CRM-DEC — Ratificação expressa de CORE07-D09-A/B/C/D

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Fonte:** o responsável respondeu **“aprovado”** à pergunta explícita **“Você aprova CORE07-D09-A, B, C e D como diretrizes documentais preliminares?”**, após apresentação da [proposta D09 de auditoria e rastreabilidade](CRM-CORE-P0-07-D09-auditoria-vinculos-equipes-revogacoes-proposta-v0.1.md).  
**Estado:** **CORE07-D09-A/B/C/D APROVADAS EXPRESSAMENTE COMO DIRETRIZES DOCUMENTAIS PRELIMINARES**. **CORE07-D09 integral e CORE-P0-07 permanecem ABERTOS** até especificação operacional, testes e homologação competente.

## 1. Diretrizes aprovadas

1. **D09-A — catálogo de eventos e causalidade:** aproveitar o sistema central CORE06 e seus proprietários, registrando convites (emissão, renovação, expiração, cancelamento, aceite), reserva/ocupação/liberação de vagas, memberships, mudanças de papel/equipe/escopo, transferência de administrador, redistribuição, revogação e resultados. Distinguir solicitação, decisão autorizativa e efeito de negócio confirmado; HTTP, e-mail enviado ou job aceito não provam conclusão.
2. **D09-B — proveniência, integridade e autoria:** vincular evidência ao tenant, autor/ator técnico, mandato ou membership, ação/recurso, owner, escopo, versão da política, estado anterior/posterior, momento e resultado de forma proporcional. Preservar autoria e histórico mesmo após desligamento ou redistribuição. Proteção contra adulteração/remoção indevida subordinada a CORE06/SEG.
3. **D09-C — consulta, privacidade, retenção e direitos:** impedir tokens de convite, códigos MFA, segredos e informações protegidas em claro em trilhas; consultar/exportar auditoria requer competência e finalidade específicas. Retenção/expurgo, direitos LGPD e eventuais provas legais dependem de contratos, classificação e políticas centrais; **não** criar prazos de guarda por analogia.
4. **D09-D — reconciliação, evidências e testes futuros:** definir cenários de reemissão com token antigo inválido e reserva única, disputa de última vaga, administração Básico 1/1, transferência/redistribuição com autoria, revogação antes de disponibilizar exportação e isolamento entre tenants. Futuras evidências exigem owner, procedimento, SHA/versão, ambiente, resultado e aceite; **nenhum teste foi executado por esta ratificação**.

## 2. Decisões preservadas e limites

- Ratificações anteriores: CORE07-D04 Modelo B (convite de 7 dias e revogação do link anterior), D05 Modelo A (vaga reservada), D06 Modelo A (transferência segura de administrador), D07 Modelo B (redistribuição híbrida), D08 Modelo B e A/B/C/D (revogação seletiva). Esses aceites são **preliminares**, não homologam funcionalidades.
- **CORE07-D04..D10 integrais continuam ABERTOS** e CORE07-D01..D03 permanecem base documental, sem fechamento do CORE07. CORE01-D04..D07, CORE02..CORE10-D04..D10 e os **67 identificadores CORE** seguem abertos no painel, assim como SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD e DEV-GATE.
- CORE07-D09 não cria novo motor de auditoria ou homologação, e não aprova esquema SQL, retenção final, visualização irrestrita dos logs, grant, testes, implantação ou alteração de dados reais.
- Esta aprovação **não** autoriza código do Codex, execução de testes, alterações da `main`, merge do PR, migração de banco, credenciais, novas contas, compra, contratação ou alteração da VPS/produção. A branch fast-track `dev/fast-track-crm-2026-11-04` é independente.

## 3. Próximo trabalho

Preparar **CORE07-D10** — matriz de dependências, cenários e evidências para homologação futura, como proposta não aprovada. O fechamento integral do CORE07 e dos gates transversais exigirá decisão posterior expressa, após os requisitos e evidências.
