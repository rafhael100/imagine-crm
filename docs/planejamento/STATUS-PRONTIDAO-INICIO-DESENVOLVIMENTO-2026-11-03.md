# STATUS — Prontidão para iniciar desenvolvimento do CRM Imagine

**Data de verificação:** 2026-11-03.
**Natureza:** diagnóstico documental e critérios de decisão **PROPOSTOS**, não aprovação de uma data, alteração da regra de planejamento, autorização de programação nem certificado de conclusão.
**Solicitação:** esclarecer se estamos perto de finalizar as revisões e quando é legítimo iniciar desenvolvimento do sistema.
**Regra já aprovada do projeto:** **terminar planejamento completo, consolidar e versionar no GitHub, revisar e aprovar expressamente a versão final ANTES de iniciar programação com Codex**. Não iniciar implantação, automações reais, testes em ambiente ou contratação nesta fase.

## 1. Observações verificadas no GitHub

| Área | Evidência encontrada | Situação real |
|---|---|---|
| Branch `main` | No exame anterior à nova decisão P0-OWN-SEC, tinha **142 arquivos**: README raiz e 141 documentos em `docs/planejamento/`, sendo 60 documentos em `integracoes/` e 81 arquivos diretamente em `planejamento/` | Várias decisões técnicas versionadas; **não** é por si só um dossiê final do CRM |
| Branch `docs/integracao-site-crm-basico` | **19 arquivos**: visão geral, planos/trial, administração, empresas/usuários, IA, infraestrutura, integração Sites×CRM, Básico, automações, agenda, permissões, decisões e pendências | Contém especificação inicial importante do núcleo; **não está consolidada com a `main`** |
| Branch `docs/especificacao-inicial-2026-10-31` | **10 arquivos** de especificação anterior | Fonte histórica de base, não assumir como versão final |
| Integrações INT-01..12 | Bases documentais CT-01..11 e 24 fichas OP aceitas; CON-MASTER e SEC/STATE/PARAM aprovadas como instrumentos documentais; [P0-OWN-SEC](integracoes/P0-OWN-SEC-matriz-recursos-campos-OP24.md) aceita como base | **Avanço substancial**, mas contratos executáveis, origem literal, perfis/alçadas e versão final ainda não fechados |
| Pendências de Integrações | [INT-P0](integracoes/INT-P0-fechamento-documental.md) e [painel P0](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): **11 bloqueios P0** ainda não encerrados; **G01..G06** ainda não comprovados | **Não liberado** |
| Arquitetura agentes e serviços cruzados | [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) inclui ARQ-01/02/03, SEG-01/02 e AGT-01 como **P0**, além de P1/P2 | Ainda há escolhas/contratos de topologia, leads, cofre, monitoramento e playbooks |
| Integração Portal/Sites | [PORTAL-01](PORTAL-01-matriz-pendencias-plataforma-sites.md) mantém pendências próprias, inclusive documentação final PORTAL-D10 | Impacta pontos de interface com CRM; não significa que todo Portal deva necessariamente ser implementado junto com o CRM |

**Importante:** contagem de arquivos, número de fichas ou volume de texto **não são porcentagem de conclusão do sistema**. Uma regra funcional aprovada em conversa pode ainda não estar na especificação canônica do GitHub. Não presumir que os arquivos em branches antigas prevalecem sobre decisões posteriores.

## 2. O que está suficientemente amadurecido como direção

- SaaS multiempresa, **Laravel e PostgreSQL**, n8n/orquestração separados; dependências de segurança e observabilidade centralizadas.
- Planos, trial, cortesia Básico por site, regras comerciais principais, armazenamento e diversos módulos de CRM têm decisões funcionais registradas historicamente, mas sua **consolidação integral e precedência** ainda precisam de revisão.
- INT-01..12, INT-R01/R02 e CT-01..11 têm diretrizes/bases de planejamento. **24 operações INT** estão mapeadas em fichas conceituais; **18 planos HT** estão documentados; testes reais ainda serão realizados **depois** do desenvolvimento autorizado.
- A arquitetura de agentes técnicos IN-167 e a matriz IN-168 reconhecem limites de autonomia, separação de ambientes, segurança de leads e pendências.
- Há decisões mais recentes sobre preços, Pix, prazos, acesso ao Básico, Sites/Cloudflare e GA4 que **devem substituir interpretações antigas** mediante rastreabilidade, sem reabrir o já aprovado silenciosamente.

## 3. O que impede iniciar o desenvolvimento **com a regra de processo atual**

| Ordem sugerida | Bloqueio para liberação | Como encerrar na fase documental |
|---|---|---|
| 1 | **Referência única de planejamento**: decisões aprovadas distribuídas entre conversas, `main` e branches históricas | Criar índice mestre por módulo/decisão/versão, confrontar com branches antigas e registrar precedência de decisões recentes; não fazer merge cego |
| 2 | **Cobertura integral do CRM**: documentação funcional dos módulos essenciais não unificada na `main` | Completar ou reconciliar empresas/usuários/RBAC, clientes e dados, atendimento, vendas/funis, agenda/tarefas, comunicação, automações, IA, cobrança/planos, configurações, relatórios, suporte e integrações; classificar aprovado/proposto/pendente |
| 3 | **P0 de segurança e contratos**: matrizes por perfil, recurso/campo, schemas, estados, efeitos oficiais, idempotência, limites de recuperação, versions ainda sem detalhamento final | Encerrar as pendências **bloqueantes** das INTs e do núcleo transversal; decisões específicas por operação, aprovação do responsável e revisão de autoridade competente |
| 4 | **P0 de infraestrutura e integração**: ARQ-01/02/03, SEG-01/02, AGT-01 e interfaces com Sites/Portal que condicionam CRM | Definir topologia e contratos aprovados em detalhe suficiente para iniciar código com isolamento, segurança, filas, cofre, monitoramento, orçamento/risco adequados |
| 5 | **Modelo de dados/APIs e aceites**: granularidade necessária à codificação ainda dispersa | Consolidar entidades, relacionamentos, permissões, contratos de API/eventos, fluxos críticos, tratamento de erro e critérios de aceite. Não é preciso executar os 18 HT antes da primeira linha de código |
| 6 | **Revisão e autorização final**: versão documental única aprovada | Auditar pendências P0, aprovar explicitamente a versão final do planejamento do CRM e fixar commit/tag de referência. Só então abrir a fase de Codex/implementação |

**Critério de honestidade:** “Está perto de terminar a revisão INT?” — **o desenho conceitual está avançado**, mas fechamento P0 não. “Está perto de desenvolver todo o CRM?” — **ainda não é demonstrável** enquanto faltar a reconciliação global, especificação crítica e aceite final. Não informar percentual ou data especulativos.

## 4. O que NÃO precisa estar concluído antes da primeira programação

- **Testes HT executados:** os planos e critérios precisam estar especificados e aprovados; testes físicos serão feitos na implementação/homologação posteriores, por etapas.
- **Todos os detalhes P1/P2 sem impacto bloqueante:** decisões não críticas podem ficar agendadas, com responsável, etapa e dependências claras, desde que não afetem contratos/segurança/arquitetura básica da primeira entrega e que o plano global explicite seu tratamento.
- **Implementar o Portal/Sites e a Universidade Imagine:** não é pré-requisito para iniciar código do CRM completo se interfaces necessárias estiverem contratadas e testáveis em futuro ambiente de desenvolvimento. Universidade só depois dos sistemas principais, conforme decisão própria.

## 5. Marcos para iniciar programação — sem prometer data artificial

1. **Inventário global reconciliado**: branches e decisões consolidadas por módulo, atualizações mais recentes prevalecendo quando houve aprovação expressa.
2. **Arquitetura e requisitos críticos fechados**: isolamentos multi-tenant, autenticação, RBAC, contratos API/eventos, dados, autorização de agentes, cobrança, backups e integrações.
3. **Gates documentais e checklist de aceite resolvidos**: evidências, exceções fundamentadas, donos, critérios para testes futuros.
4. **Autorização final de desenvolvimento pelo responsável**: commit/tag da documentação aprovada; escolha da primeira entrega e backlog no GitHub para Codex, **sem começar antes do marco**.

Se o responsável posteriormente quiser **alterar expressamente o processo** e autorizar uma etapa de fundação técnica isolada antes do fechamento global, isso precisará de aprovação de mudança de estratégia, escopo/risco e critérios próprios. **A presente avaliação não propõe nem executa essa exceção por padrão.**

## 6. Primeiras entregas do inventário global (03/11/2026)

- [CRM-MASTER-01](CRM-MASTER-01-indice-modulos-fontes-status.md): **20 áreas do CRM e 2 projetos/interfaces relacionados** classificadas por fontes, fase de consolidação e prioridade. Documentação histórica localizada em branch separada não deve ser descartada nem copiada cegamente para a `main`.
- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md): **13 princípios funcionais localizados** em fontes históricas e atuais, **10 lacunas propostas CORE-P0** e **10 cenários negativos documentais** para o núcleo multiempresa, usuários/MFA/RBAC e isolamento. **A minuta ainda não recebeu aceite como base**.
- Exemplo confirmado de precedência interna: o capítulo histórico 01 inicialmente diz que `tenant_id` + RLS era proposta, **mas uma seção posterior do mesmo capítulo registra aprovação explícita**. Essa aprovação deve ser preservada, sujeita ao cotejo de eventuais decisões ainda posteriores.
- **Sem nova data de início nem autorização de programação.** O índice mestre é mapa de trabalho, não atestado de fechamento.

## 7. Próxima recomendação objetiva

**Interromper por ora o aprofundamento indefinido de minutas INT em cascata** e priorizar um **Índice Mestre de Prontidão do CRM**, que compare `main` com as duas branches históricas e decisões conversacionais, classificando cada módulo em:
- **Aprovado e consolidado**
- **Aprovado mas não consolidado/versionado na referência atual**
- **Parcialmente decidido / pendente de aprovação**
- **Dependência P0 específica**
- **P1/P2 com etapa de definição posterior**

Em seguida, fechar primeiro os P0 que realmente bloqueiam a arquitetura e a programação do núcleo e então submeter a **revisão global do CRM**.

**Status final desta avaliação:** **NÃO LIBERADO para programação**. Trata-se de controle de prontidão documental, não adiamento por tempo, recusa de trabalho ou exigência de execução antecipada de testes.
