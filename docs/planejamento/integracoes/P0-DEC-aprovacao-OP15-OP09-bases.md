# P0-DEC — Aceite contextual das 24 fichas especializadas como bases documentais

**Data do registro:** 2026-11-03.  
**Manifestação literal do responsável:** “ok”.  
**Contexto imediatamente anterior:** recomendação de “aprovar as 24 fichas especializadas como bases documentais, preservando as decisões técnicas pendentes” e, em seguida, aprofundar “requisitos de autorização, estados e parâmetros por operação”.

## Alcance da aprovação

- As [15 fichas prioritárias](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) e as [9 fichas complementares](P0-CON-OP09-contratos-operacoes-complementares.md), consolidadas no [índice OP24](P0-CON-OP24-indice-consolidado-contratos-por-operacao.md), são **aceitas como bases documentais de arquitetura e planejamento**.
- O aceite abrange a **referência conceitual** às operações e suas fronteiras de competência: identidade/tenant, autorização contextual, efeitos e resultados técnicos separados dos oficiais, versionamento, resiliência, reconciliação e auditoria.
- Os 24 IDs OP já existentes são **casos operacionais de referência**, não inventário exaustivo de todas as ações do CRM/INT.
- As 11 famílias CT-01..11 e a CON-MASTER permanecem com seus próprios aceites de base/instrumento documental. **Aprovar OP15/OP09 não transforma a matriz CT-02 ou P0-SEC em regras de acesso executáveis.**
- **Próxima etapa autorizada:** produzir **minuta documental** de autorização, dimensões de estado e parâmetros por cada OP, com proprietário responsável, fatos oficiais, cenários negativos, lacunas e critérios de decisão. A nova minuta ainda exigirá aprovação própria.

## O que não foi aprovado

- Perfis, papéis privilegiados, MFA específico, delegações, alçadas, condições de reuso de aprovação, direitos por entidade/campo, TTL/retries/quotas/timeout numéricos.
- Schemas físicos, endpoints, nomes finais de eventos/estados/enums, infraestrutura, provedor, custos, regras específicas de migração e compatibilidade ou mecanismos técnicos não decididos.
- Direito de executar ações financeiras/contratuais, compensações, publicação autônoma, acesso entre tenants, conclusão de negócio por HTTP/webhook/n8n ou autorização implícita para IA.
- Homologação técnica ou aceite definitivo dos contratos operacionais; **HT-01..18 não foram executados**.
- Encerramento automático de P0-CON-01, P0-SEC-01, P0-OWN-01, P0-VER-01, P0-DEP-01, P0-TR-01, P0-DOC-01/02, P0-ACC-01, P0-XREF-01, P0-GIT-01, G01–G06.

## Restrições da fase

Somente **documentação no GitHub**. Não iniciar programação, testes, simulações executadas, novas automações, alterações em produção, criação de aplicações/conectores, operações de dados, contratações ou despesas.

**Status:** 24/24 fichas especializadas **aprovadas como bases documentais**, não como especificações físicas finalizadas. O desenvolvimento depende da conclusão e aprovação global do planejamento.
