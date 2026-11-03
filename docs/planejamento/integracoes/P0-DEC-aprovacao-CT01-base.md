# P0-DEC — Aceite contextual de CT-01 como base documental

**Registro:** 2026-11-03.  
**Manifestação literal do responsável:** “ok”.  
**Contexto imediatamente anterior:** recomendação de “aprovar a CT-01 como base documental, mantendo pendentes os parâmetros, estados executáveis, perfis e regras específicas dos provedores” e prosseguir à matriz consolidada de contratos e critérios de fechamento de P0-CON-01.
**Interpretação expressamente limitada:** aceite da [minuta CT-01](P0-CON-CT01-conectores-conexoes-capacidades-minuta.md) **como base documental de trabalho**, não como contrato pronto para execução, homologação ou aprovação de cada exemplo de campo/transição.

## Escopo do aceite
- CT-01 separa **catálogo e homologação de conector por versão** (INT-11), **cadastro/vínculo da conexão por empresa/conta** (INT-01), **autorização externa, identidade, credenciais e escopos** (INT-02 e Segurança/cofre IN-151) e **operação/capacidade técnica tipada** (INT-04).
- Preservar dependências de instâncias, comandos e operações anteriormente despachadas; mudança de versão, suspensão ou reativação não é confirmação/cancelamento de fato de negócio, nem autoriza redirecionamento silencioso.
- As **11 famílias CT-01..CT-11** passam a ter **bases documentais aceitas**. Não são 11 serviços novos. A aprovação de CT-01 não reabre nem substitui os aceites de CT-02, CT-03/04 e CT-05..11.
- Próxima etapa documental autorizada: **matriz consolidada das onze famílias, critérios e evidências para futuro fechamento P0-CON-01**.

## O que NÃO foi aprovado por este “ok”
- Campos de schema/API, enumeração de estados, endpoints, nomes de direito, perfis, MFA/step-up por operação, aprovação/alçada específica, versionamento físico, regra de migração por provedor.
- Timeout, TTL, quotas, limites, retry, concorrência, retenção, custos, serviços reais e quaisquer parâmetros comerciais/técnicos não decididos.
- Liberação de programação, criação de novas automações, testes, implantação, migração, publicação de conector, configuração de produção ou contratação.

## Situação do fechamento
**P0-CON-01 permanece ABERTO/PARCIAL** até revisão dos contratos por operação, fonte de autoridade e evidência, definição e aprovação dos parâmetros que precisam estar definidos antes da implementação e aceite da versão documental final. G01–G06, P0-DOC-01/02, P0-TR-01, P0-OWN-01, P0-SEC-01, P0-DEP-01, P0-VER-01, P0-ACC-01, P0-XREF-01 e P0-GIT-01 permanecem sujeitos a suas próprias evidências e aceites.

**Importante:** aprovar **o plano documental de testes HT** não significa **executar testes**; estes exigirão uma etapa futura autorizada após implementação/homologação planejada. A aprovação global de planejamento permanece pré-condição para começar a programar.
