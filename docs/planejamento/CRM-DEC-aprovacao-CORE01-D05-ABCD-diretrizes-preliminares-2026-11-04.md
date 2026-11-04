# CRM-DEC — Aprovação preliminar de CORE01-D05-A, B, C e D

**Data:** 04/11/2026.  
**Declaração literal do responsável:** “Aprovo CORE01-D05-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE01-D04 e D05 abertas, preserve CORE01-D06/D07, SEG-01, SEG-02 e todos os demais bloqueios técnicos. Prossiga com CORE01-D06, sem iniciar programação nem executar testes.”

**Escopo de aprovação:** somente **diretrizes técnicas documentais preliminares**. CORE01-D05 permanece **ABERTA**. Não aprova modelo de banco, política SQL/RLS, papel/role, esquema, serviço, contrato executivo, despesas, programação ou testes.  
**Fonte:** [CORE01-D05 — contexto do tenant, RLS, workers e suporte](CRM-CORE-P0-ETAPA2-CORE01-D05-contexto-tenant-RLS-proposta-v0.1.md).

## 1. Diretrizes preliminares expressamente aceitas

- **CORE01-D05-A — contexto validado por operação:** o backend deve identificar principal legítimo, tenant autorizado, membership/mandato quando pertinente, entitlement, objeto, recurso, ação, campo, política de estado e versão; jamais usar `tenant_id` arbitrário da requisição, do prompt, de mensagem ou do provedor como autorização. Na falta dos elementos exigíveis, negar por padrão o ato protegido, preservando as atividades internas legitimamente autônomas.
- **CORE01-D05-B — isolamento transacional e conexões reutilizáveis:** adotar a diretriz de contexto de tenant mínimo, com vigência delimitada à unidade de operação/transação e encerramento/reset seguro nas conexões reutilizadas, exceções, retry, exportação, filas e troca de organizações. Laravel e PostgreSQL RLS seguem ambos como camadas de proteção. **`SET LOCAL`, `set_config(..., true)`, roles, pool, SQL, GUC, timeouts e política física permanecem alternativas não selecionadas**.
- **CORE01-D05-C — papéis/serviços/mandatos sem bypass:** distinguir executor técnico e autor causal; papéis cotidianos da aplicação, agentes de IA, n8n, integrações e suporte não recebem superuser, BYPASSRLS nem acesso generalizado aos dados de clientes. Controles especiais de manutenção, backup e suporte requerem competência, instrumento específico, alçada e evidência. Conciliação com **SEG-01 e SEG-02** continua obrigatória.
- **CORE01-D05-D — revalidação e resultado oficial:** jobs/integrações/serviços devem revalidar autorização aplicável antes de novos efeitos protegidos e tratar revogações, replay, concorrência e inconclusão sem efeitos indevidos. O módulo proprietário é quem confirma o resultado de negócio; execução técnica, HTTP 200 ou sucesso n8n não são prova. Preservar históricos e fatos legítimos já confirmados.

## 2. Limites, responsáveis e travas NÃO encerradas

CORE01-D04 (parcial, apesar de suas diretrizes A/B/C preliminares) e **CORE01-D05 (parcial por A/B/C/D)** continuam ABERTAS, dependentes dos contratos **CORE02-D04..D10**, CORE03-D04..D10, CORE04-D04..D10, CORE05-D04..D10, CORE07-D04..D10, CORE09-D04..D10, modelos de tabela e RLS em CORE01-D07, provedores/roles/segredos e autorização em **IN-168 SEG-01/SEG-02**, INT P0-OWN/SEC/CON/ACC e G01–G06.

**CORE01-D06 e D07** continuam ABERTAS e não são aprovadas por esta decisão. Preservar igualmente CORE06-D04..D10, CORE08-D04..D10, CORE10-D04..D10, IN-168 ARQ-01/02/03, AGT-01, FIN/CON, contratos, dados/LGPD, licenciamento, custos e aprovação global. Inventário Etapa 2: **67 IDs técnicos abertos**; nenhum P0 está encerrado.

## 3. Próximo passo autorizado

Somente criar e apresentar **proposta documental CORE01-D06 — ciclo de vida das organizações CRM, titularidade, contratos Site↔CRM, cortesia, trial, assinaturas e estados independentes**, conciliando CORE08, REV02, FIN/CON, Portal, INT e IN127-R01..R06. Não criar tenants, benefícios, faturas, assinaturas ou integrações reais.

**Vedações:** sem programação/Codex, SQL, testes, execução de scripts, concessão/revogação de acesso real, credenciais, pagamentos, cobrança, infraestrutura, clientes, contratação ou implantação. Qualquer nova aprovação (inclusive D06) depende de manifestação expressa posterior.
