# CRM Imagine — CORE07-D07: equipes, visibilidade e redistribuição híbrida — diretriz documental v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **MODELO B EXPRESSAMENTE ESCOLHIDO COMO DIRETRIZ DOCUMENTAL PRELIMINAR**, conforme [ratificação específica](CRM-DEC-ratificacao-explicita-CORE07-D07-modelo-B-redistribuicao-hibrida-2026-11-06.md). **CORE07-D07 INTEGRAL PERMANECE ABERTA** para contratos reais por módulo, políticas de escopo, transações, segurança e homologação. Este documento é detalhamento da escolha B, não nova autorização de implementação.

## 1. Deliberação e alternativas consideradas

Após a apresentação ao responsável das alternativas:
- **A (manual):** um administrador redistribui manualmente os leads e responsabilidades;
- **B (híbrida):** o sistema redistribui automaticamente **somente** quando o proprietário do recurso já prevê e autoriza a automação; casos não cobertos seguem para revisão humana supervisionada;
- **C (automático geral):** transferir todos os recursos automaticamente ao sair/mudar de equipe;

o responsável respondeu **“aprovado”** à pergunta específica “Você aprova o Modelo B como diretriz documental preliminar para o CORE07-D07?”.

**Efeito limitado:** aceita-se a **política B**, sem homologar todos os algoritmos/limites de VEN/ATD/AGD ou autorizar novo mecanismo genérico de redistribuição.

## 2. Fronteiras funcionais por proprietário

| Domínio | Ação autorizável no modelo híbrido | Restrições obrigatórias |
|---|---|---|
| **VEN — leads e oportunidades** | Aplicar distribuição e redistribuição previamente configuradas e autorizadas pelo módulo proprietário, considerando ausência, indisponibilidade ou SLA de leads novos sem atendimento | Não redistribuir automaticamente lead que já recebeu atendimento válido por simples atraso; respeitar exceções de leads estratégicos, destinatário ativo, elegibilidade, número de tentativas e rastreabilidade |
| **ATD — conversas e atendimento** | Encaminhar conforme distribuição de fila, capacidade, aceite do destino, estados do atendente e regras de desconexão prolongada previstas pelo ATD | Breve desconexão não encerra/redistribui conversa; origem permanece responsável até aceite ou início efetivo no destino; preservar posse humana, histórico e controles de comunicação/IA |
| **AGD — profissionais, agenda e reservas** | Em caso de ausência/transferência, executar somente regras de reagendamento/reatribuição **expressamente autorizadas pelo AGD e pelo responsável** | Não cancelar/reagendar reservas automaticamente por analogia a leads; revalidar disponibilidade física do destino e confirmar antes de liberar origem |
| **AUT/IA e tarefas** | Executar somente fluxos internos previamente aprovados e autoridades causais verificáveis | Agentes/n8n não assumem direitos de funcionário desligado, nem enviam comunicações externas sem autorização válida |
| **Demais módulos** | Caso não exista regra explícita aprovada, produzir **pendência supervisionada** destinada ao operador competente | Não presumir poder de transferir, editar, concluir ou excluir registros de módulos diferentes |

## 3. Diretrizes do modelo B

1. **Separar vínculo/escopo de propriedade do registro:** mudar gestor/equipe, afastar ou desativar membership não transfere automaticamente a titularidade histórica do objeto. Autor do ato e histórico ficam preservados; o vínculo do funcionário deve ser revalidado por tenant e equipe.
2. **Respeitar o proprietário:** VEN, ATD, AGD, AUT/IA e outros serviços decidem quando existe autoridade para redistribuir. CORE07 coordena identidade, equipes, papéis e integridade sem criar motor universal de distribuição.
3. **Ação automática apenas onde permitida:** usar critérios previamente aceitos por domínio (e.g. VEN: regra habilitada e lead ainda não atendido; ATD: fila/estado/prazo e aceite do destino; AGD: confirmação de recurso físico). Não aplicar o mesmo algoritmo universal a qualquer entidade.
4. **Fila de pendência supervisionada:** nas situações não cobertas, detectar necessidade e apresentar ao gestor/administrador **com competência específica de transferência**, com estado, motivo, impacto e ação proposta. Essa fila **não executa reatribuição por silêncio**. A identidade do revisor, a competência, a decisão e os efeitos devem ser registrados.
5. **Autoridade de quem inicia e recebe:** confirmar tenant, plano/entitlement, membership, perfil e escopo; em operações diferidas, revalidar a autorização pertinente antes de novo efeito. Mudanças no tenant A não alteram direitos no tenant B.
6. **Conflitos e continuidade:** registrar origem, destino, estado, tentativas, concorrência, duplicatas, repetição e efeitos confirmados; origem conserva responsabilidade até destino legitimamente assumir, quando exigido pelo módulo. Não apagar histórico por desligamento, nem reverter atos oficiais já concluídos legitimamente.
7. **Segurança/LGPD:** campos sensíveis, mensagens, anotações e registros protegidos continuam sob classificação e finalidades dos módulos, sem acesso por cargo de gestor, IA, suporte ou equipe sem permissão.
8. **Sem direitos comerciais implícitos:** redistribuição e ingresso em equipe não concedem assentos, novos canais, assinatura, adicional pago, funcionalidades superiores ou bypass de MFA.

## 4. Exemplos negativos para homologação futura — sem testes executados

| Situação | Resultado esperado |
|---|---|
| Vendedor sai, lead já foi atendido validamente | Não redistribuir automaticamente por regra de atraso reservada a leads sem atendimento; encaminhar para competência/fluxo do VEN |
| Atendimento humano está em posse da origem; destino ainda não aceitou | Manter responsabilidade na origem até condição oficial do ATD |
| Atendente caiu por breve desconexão | Não redistribuir só por essa queda |
| Agenda necessita mudar profissional e destino não tem disponibilidade | Não cancelar/liberar origem nem declarar nova reserva confirmada |
| Gestor perdeu equipe e tem export antigo pendente | Revalidar direitos antes de nova disponibilização |
| Agente IA recebe instrução para transferir todo o histórico de outro tenant | Negar; nenhuma instrução/prompt cria autoridade |
| Duas reatribuições concorrentes alcançam o mesmo registro | Exigir idempotência/consistência e resultado oficial único |
| Caso não coberto por política do módulo | Pendência supervisionada, sem reatribuição automática genérica |

## 5. Pendências técnicas e limites

**CORE07-D07 integral ABERTA:** faltam catálogo real de objetos/escopos por módulo, contratos de eventos/filas, competências de transferência, transições, versão e idempotência, UX da pendência, critérios de seleção, tolerância/alerta, casos de ausência, segurança RLS e homologação. Cruzar CORE02-D04..D10, CORE03, CORE04, CORE06, CORE07-D08..D10, VEN/ATD/AGD/AUT/IA, INT e SEG-01/02.

Preservam-se **CORE07-D04..D10 e os 67 identificadores CORE** como decisões técnicas abertas; INT P0/G01..G06, FIN/CON, LGPD e gate global não são fechados por esta escolha. O modelo B **não é aprovação de programação, SQL, jobs, testes executáveis, contratos, compra, merge ou deploy**.

**A branch do Codex `dev/fast-track-crm-2026-11-04`, `main`, VPS e produção não são alteradas por esta pauta.**
