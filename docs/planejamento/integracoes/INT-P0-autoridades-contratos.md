# INT-P0 — Matriz de autoridades e contratos de fronteira

**Estado:** primeira especificação documental P0 para revisão cruzada; decisões de autoridade aprovadas, granularidade por endpoint/campo ainda pendente.
**Não é implementação nem autorização de operação.**

## Matriz de propriedade de efeitos de negócio
| Domínio | Operações/efeitos reservados | Proprietário da decisão | Participação das INTs | Fonte de confirmação |
|---|---|---|---|---|
| FIN | Pagamentos, cobranças, estornos e fatos financeiros | Financeiro | INT-04/09 solicita; INT-10 diagnostica; INT-12 audita | Confirmação de FIN ou fonte oficial autorizada |
| CON | Contratos, vigência e cancelamento contratual | Contratos | INT-09 coordena etapas; INT-10 não cancela | Confirmação de CON |
| CAT | Catálogo, produto, serviço e preço sob competência de catálogo | Catálogo | INT-03 mapeia; INT-04 transporta | Confirmação de CAT |
| VEN | Leads, oportunidades e efeitos comerciais | Vendas | INT-06 recebe webhooks; INT-07 sincroniza; INT-09 coordena | Confirmação de VEN/CRM |
| AGD | Reservas, agenda, bloqueios e disponibilidade | Agenda | INT-09 não encerra reserva por timeout | Confirmação de AGD |
| ATD | Atendimentos e estados de atendimento | Atendimento | INT-09 não conclui caso por mera resposta externa | Confirmação de ATD |
| COM | Mensagens ao cliente, supressões, janelas e frequência | COM/Coordenador Central | INT-06 somente notificações técnicas externas adequadas | Confirmação do componente COM |
| PVD/RET | Atos de seus domínios, conforme especificações próprias | Módulos PVD e RET | INT-09 não assume suas decisões | Confirmações dos respectivos módulos |
| MKT | Campanhas, segmentação e ações de marketing | Marketing | INT-09 coordena apenas operações permitidas | Confirmação de MKT |
| CMS | Publicações, conteúdo e estados editoriais | CMS | INT-09 não retira publicação unilateralmente | Confirmação de CMS |
| Identidade e Segurança | Acesso, identidade, RBAC, MFA e revogação | Identidade e Segurança | INT-02 integra referências de autorização externa | Fonte oficial de Segurança |
| Operacionais | Provisionamento, serviços e reversões técnicas | Sistema operacional proprietário | INT-09/10 sem desprovisionamento unilateral | Confirmação oficial do sistema competente |

**Regra invariável:** solicitação, transporte, aceite técnico, aprovação humana e resultado de negócio confirmado são fatos diferentes; nenhuma INT executa ou confirma ato fora de sua competência.

## Contrato mínimo compartilhado: campos conceituais
Os campos abaixo são **classes de informação aprovadas**, não um schema JSON/API definitivo:

| Classe | Referências que devem ser representáveis | Quem valida |
|---|---|---|
| Identidade | tenant, unidade/marca, identidade humana/técnica, origem, finalidade | Identidade e Segurança / módulo proprietário |
| Competência | módulo origem, módulo destino, operação tipada, recurso/campo | Contrato INT-04 / proprietário |
| Autorização | permissão vigente, escopo, referência de aprovação, revogação | INT-02 + serviços centrais |
| Versão | contrato, conector, esquema, modelo, instância e política | Proprietários de cada versão |
| Rastreamento | comando, operação, instância, etapa, tentativa, correlação, causalidade | Contrato do componente e Auditoria Geral |
| Consistência | chave idempotente contextual, estado esperado, concorrência e checkpoint | Módulo proprietário / operações correspondentes |
| Tempo | prazo técnico, horário, janela e vigência quando aplicável | Motor Central de Datas e Prazos para regras de negócio |
| Resultado | preparado/enviado/aceito/aplicado/confirmado/rejeitado/pendente/inconclusivo; fonte e evidência | Somente proprietário para confirmação de negócio |
| Recuperação | diagnóstico, retentativa autorizada, reconciliação e eventual compensação | INT-10 / proprietário / Aprovações |

## Matriz de fronteiras INT
- INT-01 cadastra a conexão; INT-02 autoriza/gerencia escopos e segredos; INT-11 homologa o conector. Conector homologado **não é** autorização de tenant.
- INT-03 fornece mapeamentos; INT-04 fornece operações tipadas; INT-07 sincroniza continuamente; INT-08 importa/exporta/migra por operação delimitada; INT-09 coordena dependências.
- INT-06 é fronteira externa dos webhooks; Motor Central de Eventos é fonte de eventos internos.
- INT-10 cuida de monitoramento e recuperação técnica; INT-12 investiga/audita via Auditoria Geral. Nenhuma oferece autoridade de confirmação de negócio.
- COM controla comunicações ao cliente; INT-06 emite/recebe notificações técnicas externas somente nas condições próprias.
- IA sugere e documenta, mas não inventa autorizações, confirmações, pagamentos, contratos, compensações ou mudanças de produção.

## Pendências de especificação antes de encerrar P0-CON-01/P0-OWN-01
1. Definir catálogo exato de operações e sua propriedade por recurso/campo.
2. Formalizar schema(s), semântica de códigos de erro, enums de estados e versionamento compatível.
3. Relacionar cada operação a alçadas, revalidação, idempotência, concorrência, retentativas e compensações permitidas.
4. Elaborar casos de teste vinculados a HT-01..HT-18 e evidências requeridas.
5. Resolver dúvidas de domínio e autorização apenas com deliberação expressa, preservando o texto aprovado.

## Referências
[INT-R01](INT-R01-governanca-contratos-homologacao.md) · [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md) · [Matriz P0](INT-P0-fechamento-documental.md)
