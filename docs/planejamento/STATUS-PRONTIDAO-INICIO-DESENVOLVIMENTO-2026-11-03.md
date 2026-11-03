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

## 7. Aceite do núcleo e revisão comercial em andamento (03/11/2026)

- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) foi aceita como **base documental**, segundo [CRM-DEC](CRM-DEC-aprovacao-REV-01-base.md). Os CORE-P0 continuam pendentes de solução concreta.
- [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) foi preparada **em minuta para revisão**, sem aprovar novos preços ou modificar benefícios. As decisões comerciais finais permanecem sujeitas à revisão conjunta ao fim do planejamento, como acordado.
- Não há mudança no gate global: **desenvolvimento ainda não autorizado**; nenhum teste ou implementação executados.

## 8. Continuidade do fechamento global: CRM-REV-02 e CRM-REV-03 (03/11/2026)

- [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) recebeu [aceite como base documental](CRM-DEC-aprovacao-REV-02-base.md) sem fechar preços, assinaturas executáveis ou 12 pendências.
- [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) reúne dados de leads/contatos, campos, históricos e portabilidade **em minuta**, não aceita automaticamente.
- A conclusão das minutas não substitui fechamento P0 ou aceite global. **Não iniciado desenvolvimento, testes ou implantação.**

## 9. Revisões funcionais CRM-REV-03/04 (03/11/2026)

- [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) está **aceita como base documental**, sem fechar especificação física de lead, campo, exclusão, backup ou portabilidade.
- [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) está **em minuta para revisão**, a partir de fontes históricas do WhatsApp oficial, IA, posse, distribuição, transferência e fila FIFO.
- Não concluir que o MVP inteiro está especificado, nem executar testes ou iniciar desenvolvimento até aprovação global.

## 10. Continuidade CRM-REV-04/05 (03/11/2026)

- [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) recebeu [aprovação como base documental](CRM-DEC-aprovacao-REV-04-base.md); ATD-P01..14 permanecem pendentes.
- [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) está **em minuta**, separando operação obrigatória, automações comerciais e chatbots/IA/canais; limites de planos permanecem sujeitos à revisão final.
- Os documentos são avanços de consolidação, não liberação de programação. Gates P0 de identidade/segurança/integrações/arquitetura continuam abertos.

## 11. CRM-REV-05 aprovada e REV-06 em revisão documental (03/11/2026)

- [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) foi [aprovada como base documental](CRM-DEC-aprovacao-REV-05-base.md); a biblioteca de modelos, quotas finais e o motor físico ainda não estão finalizados.
- [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md) foi preparada **em minuta**, para vendas, funis, propostas, catálogo e relatórios, preservando a fronteira FIN/CON. O levantamento não encerra os blocos VEN originais ou especificação final.
- Nenhuma programação foi autorizada, e P0 transversais/INT e G01–G06 continuam abertos.

## 12. Aceite VEN e revisão de Agenda (03/11/2026)

- [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md): **aceita como base documental**, com [registro CRM-DEC](CRM-DEC-aprovacao-REV-06-base.md). São 16 lacunas VEN para especificação posterior, não homologação de contratos financeiros.
- [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md): **minuta preparada**, com 42 itens funcionais de referência, seis exclusões expressas, 18 lacunas, sem iniciar integrações de calendário ou agenda pública.
- O CRM global ainda não tem fechamento P0 de identidade, segurança, contratos e infraestrutura; nenhum teste executado ou programação iniciada.

## 13. Aprovação da Agenda e gratuidade administrativa (03/11/2026)

- [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md) foi [aprovada como base documental](CRM-DEC-aprovacao-REV-07-base.md), mantendo 18 lacunas AGD e seis rejeições expressas.
- [CRM-DEC — Gratuidade administrativa de qualquer plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md): requisito funcional **aprovado** para Administrador Geral Imagine conceder plano Básico, Essencial, Profissional ou Empresarial a empresa cliente específica sem mensalidade base. Distinto da cortesia Básico por site, com **oito decisões GRAT-P pendentes** sobre implementação e cobranças.
- A nova aprovação **não encerra CORE-P0, regras de FIN/CON, INT/IN-168/G01..G06 nem libera código, testes ou mudanças em produção**. Continuar revisão global.

## 14. Minuta de IA comercial e separação dos agentes técnicos (03/11/2026)

- [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md) foi documentada **como minuta não aprovada** sobre agentes comerciais, créditos, consumo, Base de Conhecimento, modelos, supervisão e limites de ferramentas, com 15 pendências IA-P01..15.
- Identificada divergência de vigência dos créditos adicionais (saldo até consumo versus apontamento posterior de **90 dias**); exige cotejo da decisão original antes de faturamento/implementação.
- A [decisão de gratuidade administrativa de qualquer plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md) **não autoriza compras extras ou gastos ilimitados de IA**. Direitos comerciais, limites de segurança e custos internos Imagine continuam separados.
- CORE-P0, INT-P0, IN-168 e G01–G06 ainda abertos; **sem programação ou testes executados**.

## 15. Vigência da gratuidade administrativa (03/11/2026)

- [CRM-DEC — Modalidades](CRM-DEC-gratuidade-vigencia-modalidades.md): **aprovadas as concessões por prazo determinado ou indeterminado**, sem prazo padrão, máximo ou modelo financeiro executável.
- **Não converter o término da gratuidade em cobrança recorrente sem contratação expressa do cliente**. GRAT-P01/P04 permanecem parcialmente abertos; GRAT-P02/P03/P05..08 abertos.
- A nova decisão não é aprovação da [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md) e **não libera programação**.

## 16. Adicionais gratuitos com concessão administrativa independente (03/11/2026)

- A [CRM-DEC — Gratuidade de adicionais específicos](CRM-DEC-gratuidade-administrativa-adicionais-especificos.md) registra **aprovação funcional** para o Administrador Geral Imagine conceder separadamente adicionais elegíveis (usuários extras, armazenamento e pacotes IA) gratuitamente por organização.
- **Não há gratuidade automática de todos os extras** quando plano é gratuito; ao encerrar adicional gratuito não gerar cobrança recorrente sem contratação expressa. **GRAT-P05 parcialmente resolvido** e **GRAT-EX-P01..08 pendentes** sobre vigência, quantidades, cobrança, alçadas, preços e limites.
- [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md) continua **minuta não aprovada**. Nenhuma programação/teste/implantação foi autorizada.

## 17. Condições de prazo dos adicionais e pacote IA quantificado (03/11/2026)

- [CRM-DEC — Vigência dos extras](CRM-DEC-gratuidade-adicionais-vigencia-e-pacotes-ia.md): **usuários extras e armazenamento adicional** gratuitos com vigência determinada ou indeterminada, e **créditos adicionais de IA** concedidos em pacote de **quantidade definida**, respeitando validade própria ainda pendente de cotejo.
- **GRAT-EX-P02/P03/P05 parcialmente resolvidas**; não se encerraram contratos de planos/FIN/CON, créditos, quotas de dados nem os P0 transversais. **Sem cobrança automática ao término de concessões**, implementação ou testes.
- A [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md) permanece **minuta não aprovada**; o aceite desta regra funcional independente não a aprovou.

## 18. Próxima recomendação objetiva

**Interromper por ora o aprofundamento indefinido de minutas INT em cascata** e priorizar um **Índice Mestre de Prontidão do CRM**, que compare `main` com as duas branches históricas e decisões conversacionais, classificando cada módulo em:
- **Aprovado e consolidado**
- **Aprovado mas não consolidado/versionado na referência atual**
- **Parcialmente decidido / pendente de aprovação**
- **Dependência P0 específica**
- **P1/P2 com etapa de definição posterior**

Em seguida, fechar primeiro os P0 que realmente bloqueiam a arquitetura e a programação do núcleo e então submeter a **revisão global do CRM**.

**Status final desta avaliação:** **NÃO LIBERADO para programação**. Trata-se de controle de prontidão documental, não adiamento por tempo, recusa de trabalho ou exigência de execução antecipada de testes.
