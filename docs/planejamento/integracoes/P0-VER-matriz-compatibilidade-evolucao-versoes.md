# P0-VER — Matriz de compatibilidade, versões e evolução controlada das integrações

**Data:** 2026-11-03. **Situação:** **MINUTA PARA REVISÃO DOCUMENTAL**, elaborada a partir das diretrizes já aprovadas de INT-R01/R02 e da CT-03/CT-04, não aprovada automaticamente pelo aceite de CT-02.
**Fase:** planejamento e documentação apenas. Não programa, migra, distribui, atualiza sistemas reais, publica conectores ou contrata ferramentas.
**Pendência relacionada:** P0-VER-01 (compatibilidade entre versões e instâncias persistentes), P0-DEP-01, P0-SEC-01 e P0-CON-01.
**Competência:** INT-11 homologa conector; INT-01 mantém conexão; INT-02/Identidade e Segurança autoriza; INT-03 schema; INT-04 comando/resultado; INT-06/07/08 fronteiras próprias; INT-09 orquestra; INT-10 recupera; INT-12 audita. Proprietários de negócio confirmam efeitos.

## 1. Regras arquiteturais já aprovadas

- Contratos, esquemas, conectores, mapeamentos, eventos, modelos, políticas e instâncias devem ser **versionáveis e rastreáveis**, respeitando a origem e autoridade.
- Alteração incompatível precisa de compatibilidade/homologação e migração autorizada; **não existe substituição silenciosa de versão em instância ativa**.
- A publicação da versão de um conector não é automaticamente autorização para determinada empresa/conexão; provedor, conector, conexão e credencial são objetos diferentes.
- Preservar idempotência contextual, identidade/correlação, evento/resultado oficial, histórico, retenção aplicável e segregação multiempresa.
- Não prometer exatamente-uma-vez de ponta a ponta; mudança de versão durante erro inconclusivo não permite repetição insegura.
- IA sugere diagnósticos e planos; sem auto-homologação, auto-publicação, autoconcessão de autorização, alteração unilateral de provedor crítico ou compensação de negócio.

## 2. Classificação documental **proposta** de mudanças

| Categoria proposta | Definição operacional para revisão | Tratamento documental sugerido |
|---|---|---|
| V-C01 — Adição compatível | Adição que não muda significado de campos/estados/efeitos e é reconhecida pelo consumidor | Registrar versão e testes de compatibilidade; adoção somente após elegibilidade |
| V-C02 — Compatibilidade condicionada | Mudança depende de versão do consumidor, capacidade, autorização ou adaptação específica | Verificar matriz por combinação provedor/conexão/operação/tenant antes de promover |
| V-C03 — Incompatível | Remoção, alteração semântica, requisito novo ou quebra de estado/schema/assinatura | Bloquear migração silenciosa; requer plano versionado, testes e aprovação aplicável |
| V-C04 — Suspensa/revogada | Versão deixou de ser elegível por segurança, validade, catálogo ou outra política | Impedir novo uso incompatível, preservar fatos/pendências e fornecer recuperação legítima |
| V-C05 — Sem evidência | Não há informação suficiente para provar compatibilidade ou aplicação do efeito | Classificar desconhecida e suspender ato protegido de risco; não presumir sucesso |

**Observação:** códigos V-C01 a V-C05 são classificações **candidatas** para o planejamento; não são enums de API, critérios automáticos de segurança ou novos estados de negócio aprovados.

## 3. Matriz CT-01 a CT-11: identidade da versão, mudança e proteção

| Família | Objeto versionado | Proprietário | O que precisa ser identificável | Alteração a avaliar | Proteção para trabalho em andamento |
|---|---|---|---|---|---|
| CT-01 | Conector/capacidade/conexão | INT-11 homologa conector e versão; INT-01 detém instância de conexão | Conector, versão, capacidade e referência de conexão separadas | Mudança de operação suportada, retirada de versão, suspensão ou troca de provedor | Não substituir conector da instância ativa sem elegibilidade, compatibilidade e autorização |
| CT-02 | Política/autorização/escopo | Identidade e Segurança + INT-02 + Aprovações | Política aplicável e identidade/escopo/credencial em vigor | Revogação, expansão/redução de permissão, mudança de tenant/finalidade | Nunca reinterpretar política antiga para ampliar poderes; nova ação exige autorização atual |
| CT-03 | Comando tipado | INT-04 + módulo proprietário, INT-09 se coordena | Contrato/operação/schema, id de comando, contexto e estado esperado | Mudança de campo obrigatório, semântica de idempotência ou pré-condição | Preservar pedido lógico; novo schema só se migrável e autorizado, não reemitir escrita desconhecida |
| CT-04 | Resultados e confirmação | INT-04 traduz técnica; proprietário confirma efeito | Tipo de fato, referência oficial, fonte de evidência, significado do estado | Nova classe de resultado, mudança de significado, evento tardio | Não reinterpretar aceite técnico antigo como confirmação oficial nova |
| CT-05 | Eventos/webhooks | INT-06 fronteira, Motor Central de Eventos interno | Tipo/versão de evento, origem, assinatura, esquema e subscriber | Payload novo/incompatível, ordenação, revogação de assinatura | Manter interpretação da versão recebida; não aceitar evento inválido nem duplicar efeito |
| CT-06 | Sincronização/checkpoints | INT-07 + proprietários dos dados | Fonte/destino, cursor, esquema, autoridade de campo e checkpoint | Cursor incompatível, mudança de esquema/direção, origem de verdade alterada | Não avançar/reescrever checkpoint sem reconciliação ou migração autorizada |
| CT-07 | Lote, arquivo e migração | INT-08 + Central IN-118 + proprietários | Versão de manifesto/esquema/arquivo/lote, mapeamento e destino | Backup/exportação antiga, formato incompatível, alteração de campos | Prévia/validação de compatibilidade; não mesclar nem perder históricos automaticamente |
| CT-08 | Modelo/instância/etapa | INT-09; motores centrais para suas competências | Versão de modelo, instância persistida, etapa e plano de dependências | Nova etapa obrigatória, mudança de dependência, rollback ou versão de comando | Instâncias em execução continuam referenciando suas versões; migração explícita se aprovada |
| CT-09 | Incidente e recuperação | INT-10 + Observabilidade/Incidentes existentes | Versão de playbook, escopo, identidade técnica, condição, efeito esperado | Alteração de playbook/riscos/permissões, provedor degradado | Não transformar automação N2 antiga em ação N3 sem aprovação; revalidar na retomada |
| CT-10 | Compensação | INT-09 coordena; proprietário de negócio executa/atesta | Tipo/versão de compensação, efeito original, elegibilidade, alçada | Mudança de reversibilidade, estado de efeito, regras de estorno/desfazimento | Revalidar elegibilidade atual; nunca aplicar versão nova silenciosamente em compensação pendente |
| CT-11 | Evidência/auditoria | Auditoria Geral oficial; INT-12 correlaciona | Origem/versão de evento, classificação, formato, política de retenção aplicável | Evolução do esquema de evidência, migração de logs/retention | Preservar proveniência e capacidade de interpretar histórico; não mudar retenção legal por atualização técnica |

**Não criar banco de versões concorrente:** catálogo da INT-11, registros oficiais dos módulos, modelos de INT-09 e Auditoria Geral são proprietários das respectivas entidades. Esta matriz apenas impõe rastreabilidade compartilhada.

## 4. Vinte e quatro operações representativas — matrizes por INT

| INT | Fichas OP24 cobertas | Contratos relacionados | Estado/versionamento a proteger | Dependência crítica |
|---|---|---|---|---|
| INT-01 | OP-01-CAD / OP-01-CIC | CT-01,CT-02,CT-11 | Conector homologado + conexão por tenant | Suspensão, alteração de destino ou versão afeta operações pendentes |
| INT-02 | OP-02-AUT / OP-02-REV | CT-02,CT-01,CT-11 | Credencial, escopos e política atuais | Revogação impede novo ato, não reverte efeito enviado |
| INT-03 | OP-03-VAL / OP-03-VER | CT-03,CT-07 | Schema/mapeamento e campos permitidos | Transformação incompatível não substitui versão ativa |
| INT-04 | OP-04-ENV / OP-04-RSL | CT-03,CT-04,CT-01 | Versão de comando, capacidade e confirmação | Resultado antigo interpretado pela semântica da própria versão |
| INT-05 | OP-05-APP / OP-05-API | CT-02,CT-03,CT-04 | Versão de app/API, escopos, plano | API pública no trial continua desabilitada, mesmo que versão suporte acesso |
| INT-06 | OP-06-IN / OP-06-OUT | CT-05,CT-04 | Schema/assinatura de evento, subscrição | Payload/evento tardio continua associado à versão e pedido original |
| INT-07 | OP-07-SYNC / OP-07-RET | CT-06,CT-03,CT-04 | Checkpoint, fonte/destino, autoridade de campo | Migração de cursor somente reconciliada e autorizada |
| INT-08 | OP-08-IMP / OP-08-EXP | CT-07,CT-03,CT-04 | Manifesto/arquivo, schema, mapeamento e destino | Portabilidade do Básico preservada; backup ≠ exportação simples |
| INT-09 | OP-09-INI / OP-09-COM | CT-08,CT-10,CT-03,CT-04 | Modelo/instância/etapa/compensação versionados | Não atualizar processo vivo/compensação pendente silenciosamente |
| INT-10 | OP-10-DIA / OP-10-REC | CT-09,CT-02,CT-11 | Playbook autorizado, telemetria e versão | Mudança de risco ou autonomia requer revalidação, não nova autoridade da IA |
| INT-11 | OP-11-HOM / OP-11-SUS | CT-01,CT-02 | Conector e homologação por versão | Nova versão não herda homologação; clientes ativos exigem compatibilidade |
| INT-12 | OP-12-INV / OP-12-AUD | CT-11,CT-02 | Schema e classificação da evidência | Preservar interpretação e retenção oficial de histórico |

Os pares OP24 são **amostras de operações aceitas como base documental**, não catálogo exaustivo; para conclusão P0-VER-01 é necessária conferência integral das capacidades originalmente aprovadas e contratos efetivos por operação.

## 5. Política candidata para atualização e promoção de versão

**Regras de verificação propostas; a ordem pode variar por serviço já existente e não implementa outro publicador:**

1. **Registrar mudança proposta:** objeto, versão anterior e nova, autor, escopo de tenant, capacidade, dependências e justificativa, preservando referência ao registro oficial.
2. **Comparar contratos/esquemas:** tipos, campos, enum, operação, pré-condição, idempotência e interpretação de retorno; classificar compatibilidade baseada em evidência, nunca por aparência do nome.
3. **Mapear consumidores ativos:** conexões de clientes, webhooks, sync/checkpoints, lotes de portabilidade, processos/instâncias e compensações pendentes, respeitando segregação e minimização.
4. **Rever autorizações:** nova versão não herda acesso indevido, segredo, consentimento ou aprovação; CT-02 é reavaliado conforme mudança e política, sem dupla aprovação da mesma decisão válida.
5. **Planejar migração/convivência:** instâncias abertas preservam referências; definir se coexistem duas versões, se há transição compatível ou se exige intervenção. Sem regra universal de “última versão ganha”.
6. **Revisar homologação:** INT-11 confirma versão/capacidades de conector; proprietários de negócio validam efeitos; testes em ambiente futuro dependem de autorização para implementação/homologação.
7. **Publicar/habilitar quando permitido:** uso posterior só a conexões/tenants/ambientes elegíveis. Sem publicação autônoma da IA ou de terceiros.
8. **Observar e reconciliar:** monitorar versões/correlações/erros com IN-159/160 e INT-10, preservar registros na Auditoria Geral e confirmação oficial por proprietário; suspender diante de comportamento incompatível.
9. **Descontinuar e encerrar:** respeitar efeitos pendentes e suporte das versões necessárias para interpretar evidências, sem apagar histórico ou prometer reversão universal.

## 6. Situações de conflito a documentar antes de qualquer desenho executável

| Caso | Risco de interpretação | Conduta documental exigida |
|---|---|---|
| Conector novo tem capacidade adicional | Capacidade interpretada como autorização do tenant | Contrato CT-01 e política CT-02 separados; homologação não concede acesso |
| Token renovado em meio a execução | Token atual passa a autorizar comando antigo irrestritamente | Revalidar escopo/identidade/conexão, preservar pedido lógico e tentativas |
| Schema de webhook muda com eventos atrasados | Evento antigo é interpretado como se tivesse formato novo | Identificar versão do evento na origem e aplicar contrato correspondente |
| Cursor de sync muda de estrutura | Avançar sobre alterações não confirmadas | Reconciliação de checkpoint, autoridade por campo e migração aprovada |
| Backup antigo é restaurado | Campos mais recentes sobrescritos/destruídos | Prévia, compatibilidade e confirmação legítima; política IN-118 |
| Processo INT-09 foi instanciado em modelo antigo | Etapa obrigatória nova aplicada no meio do processo sem revisão | Pin de versão e plano de migração explícito, sem recomposição silenciosa |
| Conector suspenso após envio com timeout | Reenviar escrita sob conector alternativo e criar efeito duplo | Tratar resultado inconclusivo e reconciliação pela fonte oficial antes de alternativa |
| Playbook N2 passa a ser classificado N3 | Permissão antiga de agente aplicada à nova operação crítica | Reavaliar risco e autorização aplicável, sem autorizar IA |
| Auditoria muda formato/retention | Evidências antigas tornam-se ininteligíveis ou desaparecem | Conversão controlada, proveniência/retention de cada categoria e trilha oficial preservadas |

## 7. Casos HT de compatibilidade — propostas documentais, não executadas

| ID proposto | Verificação a especificar | Resultado esperado | HT já aprovada |
|---|---|---|---|
| VER-T01 | Atualizar conector sem alterar conexões elegíveis | Versão nova publicada não ativa automaticamente todas as contas | HT-12, HT-18 |
| VER-T02 | Alterar schema de webhook com evento antigo pendente | Evento interpretado na versão correta; sem duplicidade | HT-05, HT-12 |
| VER-T03 | Atualizar mapeamento enquanto há lote INT-08 parcialmente aplicado | Lote continua/reconcilia por versão; sem regravação indevida | HT-07, HT-12 |
| VER-T04 | Modificar versão de modelo INT-09 com instância aberta | Instância não sofre mutação silenciosa | HT-09, HT-12 |
| VER-T05 | Revogar conector durante resultado de escrita inconclusivo | Sem retry perigoso por provedor alternativo | HT-08, HT-18 |
| VER-T06 | Atualizar política de autorização no meio de operação assíncrona | Revalidação e limitação de escopo; sem privilégio novo presumido | HT-02, HT-13 |
| VER-T07 | Processar checkpoints após mudança incompatível no cursor | Suspensão/reconciliação, sem avanço não comprovado | HT-06, HT-12 |
| VER-T08 | Recuperar evidência histórica de versão antiga | Consulta auditável com origem e interpretação correta | HT-14, HT-17 |

## 8. Pendências P0-VER-01 ainda não resolvidas

| ID | Definição faltante | Situação |
|---|---|---|
| VER-P01 | Identificadores definitivos e regras de semver/compatibilidade por tipo de artefato | Pendente |
| VER-P02 | Política de duração de suporte, retenção e coexistência de versões | Pendente |
| VER-P03 | Contratos de migração/checkpoint/rollback técnico por domínio | Pendente |
| VER-P04 | Dono/aprovação necessária por alteração crítica; integração CT-02 e N1/N2/N3 | Pendente |
| VER-P05 | Política de alteração/revogação com versões usadas em fluxos pendentes | Pendente |
| VER-P06 | Testes de compatibilidade/homologação com evidências e ambientes definidos | Planejados, não executados |
| VER-P07 | Proveniência das decisões originais, cobertura de todas as operações e revisão cruzada | Pendente |
| VER-P08 | Custos, capacidade, provedores e janelas operacionais por ambiente | P1 / dimensionamento pendente |

**Conclusão:** P0-VER-01 agora possui uma matriz inicial de análise de compatibilidade de CT-01 a CT-11 e das 24 fichas; **não foi encerrada nem homologada**. Propostas da seção 2 e política de evolução exigem revisão/aceite documental. Nenhum código ou mudança de produção foi realizada.
