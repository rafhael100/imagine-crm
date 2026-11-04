# CRM Imagine — DEC-F1-P0-01: aceite do pacote-base documental de identidade, RLS e Segurança

**Data:** 04/11/2026.  
**Manifestação literal do responsável nesta conversa:** “aprovado”.  
**Objeto da manifestação contextual:** [F1-P0 — pacote consolidado de identidade, isolamento multiempresa, RLS e segurança (v0.1)](CRM-F1-IAM-RLS-SEG-PACOTE-TECNICO-GO-CHECKLIST-v0.1.md), que foi apresentado com o convite de avançar das recomendações de segurança para contratos físicos.  
**Decisão:** **PACOTE F1-P0 ACEITO COMO BASE DOCUMENTAL DE PLANEJAMENTO E DIREÇÃO TÉCNICA**, preservando decisões anteriores. **NÃO é aprovação de implementação privilegiada F1 nem homologação das escolhas físicas pendentes**, e não é ato de produção.  
**Regra de precedência:** [DEC-FASTTRACK-01](CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md) autoriza **F0** em branch isolada de scaffold sem privilégios. **F1 além do escopo F0 continua condicionada a contratos físicos, segurança, critérios futuros e liberação separada.**

## 1. Conteúdo documentariamente aceito

1. **F1-01 — entidades e integridade:** exigir inventário IAM global × tenants separados × memberships, convites, roles, assentos e dados tenant-bound; integridade sem referências cruzadas entre tenants; a versão inicial é modelo lógico, não DDL homologada.
2. **F1-02 — Laravel e PostgreSQL RLS:** autorização de negócio no backend e proteção por linha no banco; negar por padrão; não usar função de aplicação, superuser, BYPASSRLS ou variável SQL adulterável como autorização isolada; garantir contexto/validação de tenant e limpeza entre transações/filas.
3. **F1-03 — autenticação, sessões, MFA:** admin Imagine e admin do tenant, inclusive Básico, exigem MFA; identidade global não é membership; convite de 7 dias não é privilégio. Métodos concretos, recuperação e tempos ainda exigem decisão/revisão.
4. **F1-04 — autorização/revogação:** reconhecer política-base delegada P1 + P2 escopada e bloqueios superiores; recursos, campos, escopos, plano e canais independentes; ação leitura não concede exportação/gerenciamento/financeiro; mudanças de papéis e memberships devem impedir novos efeitos não autorizados.
5. **F1-05 — serviços/segredos:** nenhuma IA, worker, n8n, provedor ou suporte recebe privilégio por prompt, token técnico ou condição de funcionário; SEG-01 e SEG-02 exigem especificação e evidência por ferramenta/ator/operação/tenant/risco/cofre.
6. **Governança:** manter lista de decisões físicas e provas ainda necessárias, marcos separados de design, código F0, testes reais e produção; usar matriz [R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md) para rastrear 67 identificadores sem criar novos motores.

## 2. Não aprovados por este “aprovado”

- **Nenhum schema, migration, DDL, chave/índice final, GUC/RLS/função SQL real, role ou grant em produção**; nem chaves de segredo, senha, tenant de clientes, provedores, contratação, dados pessoais reais, cobranças ou operação externa.
- Nenhum método definitivo de MFA, recuperação, prazo de sessão, mecanismo físico de contexto confiável, ou produto de cofre SEG-02; esses itens dependem de especificação física/custos/owners e verificação posterior.
- **F1 não tem GO técnico de implementação sensível**; [DEV-GATE-00](CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md) mantém apenas GO F0 já documentado, e **NO-GO** para F1 privilegiada, merge sensível, dados reais, integração externa e produção.
- **CORE01-D04..D07, CORE02-D04..D10 e CORE03..10-D04..D10 (67 IDs)** continuam abertos integralmente; **SEG-01/SEG-02, ARQ-01/02/03, AGT-01, INT P0/G01..G06, FIN/CON e LGPD** abertos.
- Não declarar testes ou revisão de segurança efetivamente executados.

## 3. Encaminhamento autorizado

Prosseguir com **a especificação física documental de F1 em um pacote único**, escolhendo ou comparando alternativas para: (a) chaves/relações e constraint tenant-bound; (b) desenho RLS por tabela/operação/role e ciclo transacional; (c) perfis, delegações e revogações; (d) MFA/convites/sessões; (e) matriz SEG-01, cofre SEG-02 e ambiente dev; (f) riscos, aprovações e requisitos de validação. Indicar para cada escolha se **proposta**, **política documental aceita**, **decisão técnica delegada** ou **homologação técnica pendente**.

**Nenhuma programação, testes, configuração, compras, instalação ou implantação é realizada por este registro.** Código F0 pré-existente permanece independente e sob seu GO original.
