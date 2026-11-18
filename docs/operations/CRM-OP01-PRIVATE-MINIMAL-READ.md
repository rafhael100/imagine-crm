# OP-01 — consulta mínima por sessão do CRM em homologação privada

## Autorização e recorte

Em 17/11/2026, após a conclusão da jornada P64 do Imagine AI Agents, a Direção aprovou expressamente a proposta de implementar uma consulta de um lead fictício, em homologação isolada, iniciada por usuário autenticado no CRM. O CRM mantém a autoridade sobre identidade, empresa, recurso e campos. A projeção fica limitada à referência e à etapa do lead.

Esta autorização permite o código funcional deste recorte no repositório CRM. Não representa aceite geral dos contratos produtivos, merge, implantação ou delegação para um serviço Agents remoto.

Base: [CRM PR #647](https://github.com/rafhael100/imagine-crm/pull/647), `a2c7218f28aac58796387c939832ff1bfae3cba0`. A candidata preserva a jornada privada, o IAM e os adaptadores S2S existentes. Os gates de atualização F251/F252 recusaram o escopo acumulado da base; suas políticas não são ampliadas por esta entrega.

## Interface e autoridade

```text
GET /agentes/leads/{id}/minimo
```

A rota só existe quando o ambiente privado já é admitido por `Environment`/`IdentityRuntimeProfile` e o operador do laboratório fixa `CRM_PRIVATE_OP01=minimal-lead-v1`. O valor padrão é vazio. A flag é revalidada no controller e no método de domínio; ela admite o recurso no laboratório e não representa identidade ou permissão.

O único seletor recebido é o UUID canônico minúsculo do recurso, no caminho. Não se aceitam query string, corpo GET, HEAD, método sobrescrito ou cabeçalhos de principal/empresa/identidade de serviço. A rota não possui chamada de escrita de negócio.

`Workspace::minimalLead()` reutiliza `run('view')`, o mesmo direito privado de consulta já usado pelo painel. A cadeia efetiva é:

1. O guard Laravel `crm_identity` resolve a sessão humana.
2. `DatabaseSessionAuthority` verifica credencial, sessão, MFA, versão, validade, revogação e bloqueios pendentes sob locks.
3. O vínculo `crm_private_accounts` da identidade determina a organização; a conta precisa ser cliente e estar ativa.
4. `PostgresActiveMembershipStore` verifica e bloqueia a participação ativa, estabelece o contexto transacional e exige o perfil PostgreSQL/RLS.
5. A leitura seleciona explicitamente `id` e `stage` de `crm_private_leads`, filtrados pela organização verificada e pelo recurso.
6. O evento `lead.minimal_read` é gravado na mesma transação antes de retornar a projeção. Falha na auditoria impede a divulgação.

Owner e member seguem o direito de leitura existente. Um administrador global não recebe acesso aos leads por esta rota. A política global `DenyByDefaultAuthorizer`, o provider de identidade e o middleware privado permanecem intactos.

## Contrato mínimo

O sucesso HTTP 200 tem JSON compacto, nesta ordem de propriedades:

```json
{"contract":"CRM_AIA_HOMOLOGATION_LEAD_READ_V1","operation":"crm.lead.read_minimal","status":"READ","scope":"SYNTHETIC_PRIVATE_HOMOLOGATION","projection":{"ref":"10000000-0000-4000-8000-000000000001","stage":"new"},"operational":false}
```

| Campo de negócio | Origem | Tipo/valores |
|---|---|---|
| `projection.ref` | `crm_private_leads.id` | UUID canônico minúsculo |
| `projection.stage` | `crm_private_leads.stage` | `new`, `qualified`, `proposal`, `won`, `lost` |

Título, valor, funil, empresa e identidade do autor não integram a resposta. O perfil usa exclusivamente dados fictícios. A resposta não é grant, prova de delegação ou licença para executar uma automação.

As falhas preservam o comportamento privado existente: redirecionamento para login em sessão inválida, negação de acesso, recurso indisponível e erro temporário. Ausência e recurso de outra organização são indistinguíveis no mesmo 404. **Apenas o status 200 possui o novo contrato JSON**; o consumidor recusa todos os demais status sem seguir redirecionamentos. Cache é proibido; não é concedido acesso CORS.

## Interoperabilidade comprovável

O consumidor original Agents está fixado em `740abafc813dbde70f5a2ad6e138402ed7945276`, [PR #471](https://github.com/rafhael100/imagine-ai-agents/pull/471). A [CI própria 44213754313](https://github.com/rafhael100/imagine-ai-agents/actions/runs/44213754313) passou. O ensaio CRM usa checkout imutável dessa fonte, executa a CLI original em contêiner sem rede e envia somente o corpo HTTP capturado, status e recurso esperado. Cookies, senha e MFA ficam exclusivamente no cliente de teste do CRM.

O consumidor exige os bytes canônicos, propriedades fechadas, tipos corretos, referência correspondente e etapa reconhecida. Não guarda corpo, tenant, principal, credencial ou autoridade; o resultado contém apenas `ref` e `stage` imutáveis.

## Validação da candidata

O workflow `ci-crm-op01-private-minimal-read.yml` prepara um projeto Docker exclusivo no runner GitHub público, gera credenciais temporárias e não aceita outro repositório, evento, revisão ou ambiente de execução no harness. O fluxo verifica a rota ausente antes da ativação e após a desativação, o consumidor original e o caminho HTTP autenticado.

O ensaio cobre leituras positivas, projeção refletindo a etapa atual, recurso de outra empresa/ausente, seletores e métodos indevidos, sessão sem MFA ou revogada, conta suspensa, membership revogado, administrador global e falha de INSERT da auditoria com recuperação. A comparação dos dados de negócio exige que a consulta não altere leads, tarefas, automações, pedidos ou preferências. Uma consulta admitida cria sua auditoria e pode renovar a atividade da sessão IAM; não se afirma ausência total de escritas.

O teste de admissão PHP cobre flag exata, perfil, rejeição antes de resolver o Workspace e método real/efetivo. A verificação de integridade dos arquivos IAM preservados e os testes históricos de ausência de rota de serviço também são executados. O workflow da jornada privada completa valida a regressão na mesma SHA candidata, com a nova flag desativada.

Os resultados efetivos ficam registrados na PR da SHA executada. Uma revisão de código ou teste isolado não antecipa aprovação da matriz HTTP.

## Limites da evidência

A [CRM PR #57](https://github.com/rafhael100/imagine-crm/pull/57) permanece uma proposta documental independente. Esta consulta humana não fecha seus casos de emissor, audiência, delegação, nonce distribuído ou identidade de serviço. Os contratos sintéticos antigos não são reescritos para aparentar essa comprovação.

Nenhuma migration, nova role SQL ou permissão produtiva é necessária para o recorte: a consulta usa as tabelas e a política privadas existentes. O runtime não recebe a senha de bootstrap; somente o helper de teste descartável pode provocar/restaurar a falha de auditoria. A homologação operacional, os aceites humanos e a instalação na VPS continuam fora desta candidata.
