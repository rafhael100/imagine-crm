# FUNC-P0-01/S01-R10-A04 — Estados, versões e revalidação por tenant (proposta documental v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo). **Estado:** MINUTA DOCUMENTAL PARA AVALIAÇÃO, AINDA NÃO APROVADA. Não representa modelo de dados homologado, protocolo de revogação implementado ou política executável.

## 1. Sequência e provas verificadas

- [A03 — proposta original de fontes/isolamento](CRM-FUNC-P0-01-S01-R10-A03-fontes-identidade-isolamento-proposta-v0.1-2026-11-06.md) blob `d06e86e3bf5e6b49f6dc379d00ddb9b734ed1d80`; [ratificação preliminar A03-D01..D08](CRM-DEC-FUNC-P0-01-S01-R10-A03-D01-D08-alternativas-A-2026-11-06.md).
- [R10 — matriz C01..C08](CRM-FUNC-P0-01-S01-R10-matriz-fechamento-evidencias-C01-C08-v0.1-2026-11-06.md), [R10-A02 ratificada](CRM-DEC-FUNC-P0-01-S01-R10-A02-D01-D08-alternativas-A-2026-11-06.md), [R10-A01 — negativas](CRM-FUNC-P0-01-S01-R10-A01-matriz-negativas-C01-C04-proposta-v0.1-2026-11-06.md).
- [CORE01](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1`, [CORE02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6`, [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md) blob `3266eedea35f9d58f0b1fc85f4c991bc8400994a` e [CORE04](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md) blob `adf09f4e790aefd8679735e62111489f3a74db68` — bases documentais parciais, decisões técnicas finais abertas.
- [S01 — 22 cenários de homologação futuros, NÃO executados](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md) blob `78e1d021c0f1c5f66fc4b9f59092383a4d7b4`.

## 2. Quatro eixos de estado que não devem ser confundidos

| Eixo de estado | Fonte lógica de verdade | Exemplo de estados a confirmar; não é ENUM homologado | Impacto na futura S01 |
|---|---|---|---|
| Sessão humana e fator | CORE04 / IAM | válida, expirada, revogada, MFA insuficiente, inconclusiva | Prova obrigatória anterior à leitura; inválida/inconclusiva nega S01 |
| Membership da pessoa no tenant | CORE01/CORE07 / IAM | convidada, ativa, suspensa, removida, revogada, desconhecida | Somente própria, ativa, verificada seria candidata a S01 |
| Organização CRM | CORE01 / owner do cadastro | operacional, inativa, suspensa, em encerramento, encerrada, desconhecida | Elegibilidade de leitura por estado técnico ainda requer decisão específica |
| Contrato/serviço/entitlement | FIN/CON CRM, CORE08, Portal para fatos do site | trial, cortesia, paga, em atraso, restrita, cancelada | Não autentica, não gera membership nem ALLOW; EST05..12 permanecem por decidir |

**Chaves, clock, versões, transições, papel decisório e relação com tabelas reais ainda não foram homologados.** Esta é uma visão de conceitos, não API, schema ou classificação formal LGPD.

## 3. Dependências de revogação a estudar

- Uma revogação de vínculo em A não extingue automaticamente a identidade global e as memberships válidas de B. Também não revoga por si a assinatura comercial do cliente.
- Revalidar antes de *nova operação protegida*, evitando que a UI, o último tenant usado, cache ou worker sejam fontes de direito.
- Em jobs e integrações, o principal técnico, mandato, tenant e efeito autorizado exigem contrato próprio de CORE03/INT; isso não faz parte de uma leitura humana S01 e não pode gerar impersonação.
- Cache, conexões e resultados anteriores nunca suprem prova de autorização atual. Exigir avaliação da consistência e dos limites reais de revogação, sem prometer instantaneidade perfeita ou reativação implícita.
- A exibição prévia não é exclusão de dados nem supressão do direito legítimo de exportação/regularização, que pertence a outro fluxo aprovado.

## 4. Seis decisões novas (A04-D01..D06): somente propostas para ratificação futura

| ID | Contrato | Tema | Alternativa A recomendada | Alternativa B rejeitada como recomendação |
|---|---|---|---|---|
| **A04-D01** | C02 | Estado próprio da membership | Propor inventário de estados de vínculo (convidado/pendente, ativo, suspenso, revogado, expirado/removido, desconhecido) por tenant, distinguindo ato administrativo e estado confirmado; só ativo, próprio e verificado seria elegível ao exame de S01. | Usar qualquer registro de vínculo, inclusive convite, como ativo. |
| **A04-D02** | C01/C02 | Revalidação antes de nova leitura | A cada nova operação protegida S01, exigir condição vigente de sessão/fatores, tenant e membership na fonte competente; conteúdo previamente exibido não é prova de que ainda exista direito. | Manter autorização porque a tela foi aberta ou porque cache confirmou acesso anterior. |
| **A04-D03** | C02/C03 | Revisão de contexto em troca de empresa | Uma mudança de A para B, quando existir fluxo próprio autorizado fora S01, deve reconstruir contexto/políticas do backend para B; S01 não troca tenant por identificador passado em query/header/body. | Reaproveitar contexto de A, grants ou conexão de banco em B. |
| **A04-D04** | C03 | Invalidação e concorrência sem prometer atomicidade irreal | Reconhecer riscos de cache, filas, workers e transações concorrentes e exigir desenho futuro com checagem de revogação nos pontos de decisão e de efeito; em dúvida, falha segura do recorte S01. | Declarar revogação instantânea global ou confiar só no logout visual sem evidência. |
| **A04-D05** | C03/C04 | Contrato mínimo de projeção e negativa | CAM01 só do cadastro da organização autorizada e CAM02 só de membership própria ativa; saída sem CAM03..CAM10, erros genéricos, escaping do nome e logs mínimos classificados. | Mostrar ID/CNPJ/papel, dados de terceiros ou detalhes de falha a usuários sem autorização. |
| **A04-D06** | C02/C03/C06 | Separação de estados operacional, vínculo e comércio | Inventariar em camadas independentes sessão, membership, organização e contrato/entitlement; pagamentos, trial e cortesia jamais constituem identidade ou direito de S01; EST05..12 seguem sem resultado positivo definido. | Converter status pago, benefício de site ou mesmo CNPJ em grant implícito. |

### Dependências que **permanecem abertas**, inclusive após eventual aprovação documental de A

- **A04-D01 (C02)** — Versões/autoridade de mudança e política de recuperação/reativação não definidas; CORE01/07.
- **A04-D02 (C01/C02)** — Momento de revogação efetiva, latência aceitável e canais são CORE01/04/SEG.
- **A04-D03 (C02/C03)** — Contrato separado de seleção de organização, identidade global e isolamento PostgreSQL permanecem abertos.
- **A04-D04 (C03)** — Versão de política/tempo real, GUC, pool, evento de revogação e prova de concorrência pendentes CORE01/03/04/SEG.
- **A04-D05 (C03/C04)** — Fonte específica do atributo, classificação LGPD, formato UX/DTO e resposta HTTP não aprovados.
- **A04-D06 (C02/C03/C06)** — CORE08/FIN/CON, contratos de exportação/portabilidade e estados comerciais ainda abertos.

## 5. Rastreamento para provas futuras (nenhum caso executado)

| Casos preexistentes | Contratos | Risco | Resultado esperado condicionado à futura homologação |
|---|---|---|---|
| S01-T03/T08 | C01 | Sessão inválida/MFA insuficiente | Negar S01, sem revelar empresa |
| S01-T04/T05 | C02/C03 | Mesmo humano nos tenants A e B; tentar forçar B enquanto contexto atual é A | Sem acesso cruzado e sem seleção client-side |
| S01-T06/T07 | C02 | Membership suspensa, revogada ou alterada após página renderizada | Negar nova leitura em A; B permanece independente |
| S01-T09/T10/T11 | C03 | Grant escopado, deny em interseção ou falta de grant | Conceder nada sem política válida; preservar precedência escopada |
| S01-T12/T13 | C04 | Tentativa de solicitar dados de terceiros e CAM03..CAM10 | Omitir/negar campo não homologado; não enumerar |
| S01-T15/T22 | C02/C06 | Situação comercial modificada ou indeterminada | Não inferir direito; fluxo separado de portabilidade/regularização |
| S01-T16/T20 | C03 | Pool reutilizado, role owner/BYPASSRLS e concorrência de revogação | Isolamento exigido em implementação futura, sem alegar prova atual |
| S01-T17/T19/T21 | C01/C03/C04 | Dependência indisponível, erro/trace, API alternativa | Fail-closed S01; erro sanitizado; nenhum canal paralelo |

Cada cenário precisará posteriormente de versão normativa do requisito, Git SHA de implementação, ambiente autorizado, tenant/role/contexto, resultado efetivo, erro sanitizado, evidência íntegra e aceite do revisor competente. **Não marcar passed, failed ou homologado.**

## 6. Decisões e evidências que NÃO podem ser inferidas desta minuta

- Metodologia de identidade/role física e inscrição MFA; TTL e revogação de tokens/sessões; início/encerramento das fases de vínculo; versões/eventos de invalidação; reuso de pool/GUC; SQL/RLS; direitos por campo; respostas HTTP; schema; gestão de cookie/Redis; retenção de logs, base LGPD, autorização de testes.
- Definição de estados EST05..12 e acessos comerciais/restritos exige proprietários CORE08, FIN/CON/Portal, SEG e LGPD conforme o caso; a direção pode aprovar escopo empresarial mas não criar uma prova independente pelo acúmulo de G01–G06.
- Nenhuma consulta de terceiro ou concessão de permissão por estado pago/site/CNPJ; S01 continua com **CAM01 e CAM02 somente** e sem nova funcionalidade.

## 7. Pedido concreto ao titular G01

**Submeter A04-D01 até A04-D06, alternativas A, apenas para adoção como diretrizes documentais preliminares**, mantendo seus bloqueios e pendências. Não homologar os oito contratos nem fechar gates. Após aceite, confrontar cada decisão com a evidência de fonte, política e futuro teste real antes de qualquer fase executável.

## 8. Preservações

**C01..C08 PENDENTES. 67 IDs CORE, SEG01/02, AUT, ARQ/AGT, FIN/CON, INT/G01..G06, LGPD, CORE10/DEV-GATE e todos os demais gates ABERTOS.** Funções internas de governança G01..G06 ≠ gates técnicos INT-G01..G06.
Somente Markdown na branch `docs/func-p0-01-s01-r01-r02-2026-11-06`. **Não alterar main, PRs #2/#21, branch DEV, VPS, produção, CI, código, SQL, grants/ALLOW, testes reais, contratos comerciais ou dados de clientes.**