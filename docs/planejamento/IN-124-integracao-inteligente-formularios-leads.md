# IN-124 — Integração Inteligente de Formulários e Leads

**Status:** APROVADA funcionalmente em 01/11/2026 pelo responsável do projeto.  
**Fase:** planejamento e documentação. NÃO autoriza programação.  
**Escopo:** integração entre formulários de sites/landing pages Imagine e contas do CRM Imagine, inclusive CRM Básico concedido como cortesia quando elegível.  
**Relacionamentos:** IN-118 (portabilidade), IN-119–IN-122 (cortesia/vinculação), IN-123 (painel de integração), regras gerais do CRM para leads e contatos.

## Objetivo
Capturar, entregar, rastrear e recuperar automaticamente envios dos formulários de sites Imagine à conta CRM correta, com tolerância a indisponibilidades temporárias, transparência sobre erros e proteção de dados.

## Requisitos funcionais aprovados
1. **Captura automática:** cada formulário integrado de site ou landing page Imagine encaminha os dados submetidos à conta CRM vinculada e autorizada no momento aplicável da entrega.
2. **Origem identificável:** preservar metadados disponíveis de site, página, formulário e campanha/origem, sem inventar valores não coletados. Evitar exposição indevida de parâmetros sensíveis.
3. **Tratamento de duplicidades:** identificar leads e contatos possivelmente existentes, conforme política do CRM, sem eliminar históricos ou mesclar dados automaticamente de forma destrutiva.
4. **Tolerância a falhas de comunicação:** guardar com segurança envios que não puderem ser entregues por falhas transitórias e processá-los posteriormente, por novas tentativas automáticas, sem exigir intervenção manual para falhas comuns.
5. **Avisos internos:** notificar a equipe Imagine sobre problemas persistentes de entrega, falhas recorrentes, congestionamento ou risco de retenção esgotada.
6. **Mudança de vínculo:** a partir da ativação da nova integração validada pela equipe Imagine, novos leads devem ser encaminhados à nova conta CRM. Os registros já entregues à conta antiga não são migrados automaticamente.
7. **Histórico de entregas:** auditar recebimentos, tentativas, sucesso/erro e reprocessamentos, com visibilidade segundo as permissões aplicáveis.
8. **Validação e privacidade:** validar dados de entrada, aplicar proteção antispam/abuso, segregação por cliente e proteção de dados pessoais em trânsito e quando temporariamente armazenados.
9. **Inclusão nos sites elegíveis:** integrar a funcionalidade na oferta aprovada dos sites Imagine vinculados a conta CRM Básico Cortesia ou outro plano, respeitando os serviços do contrato.

## Comportamentos de segurança e consistência a detalhar
- A integração terá **identificação única por envio** e processamento idempotente: uma mesma mensagem reenviada não deve criar automaticamente leads duplicados por causa de tentativas repetidas.
- A fila deverá ser persistente e isolada por conta/projeto; erro duradouro deve ser identificado e escalado para intervenção autorizada, sem reenvios infinitos.
- Definir como tratar mensagens pendentes criadas **antes** da troca de CRM: não redirecionar silenciosamente dados antigos à conta nova; preservar contexto do vínculo na origem e estabelecer procedimento autorizado para resolução.
- Não afirmar garantia absoluta de “zero perda” antes de validar retenção, capacidade, disponibilidade, backup operacional e comportamento sob falhas extremas.
- Proteger dados sensíveis nos registros de auditoria e mensagens de erro; evitar cópia desnecessária de dados pessoais e compartilhamento entre contas.
- Registrar consentimentos, bases legais e informações pertinentes ao tratamento de dados conforme a configuração do formulário e política aplicável.
- Reprocessamento manual deve ser restrito a colaboradores autorizados da Imagine e respeitar a identificação do envio.
- Dados recebidos pelo CRM não devem ser apagados quando a origem, integração ou site for cancelado; aplicam-se as políticas de armazenamento, acesso, retenção e portabilidade já aprovadas.

## Pontos técnicos pendentes para especificação antes do desenvolvimento
- Contrato dos campos, esquema versionado de payloads, anexos, limites de tamanho e mapeamento de formulários.
- Fila e política de novas tentativas, prazos de retenção, descarte seguro, capacidade, limiares para alertas e mecanismos de recuperação.
- Recepção de webhooks/API, validação de origem/assinaturas, autenticação entre serviços, limites de requisição e bloqueios antispam.
- Critérios de deduplicação, regras por empresa e comportamento diante de contatos preexistentes.
- Testes controlados de ponta a ponta e visualização do resultado no Portal Imagine (IN-123), sem criar leads fictícios no funil de produção.
- Continuidade em mudanças de contrato, CRM suspenso, vinculação em transição ou eventos concorrentes.
- Métricas e SLA, reprocessamentos após limite de tentativas, comunicação e trilha de auditoria.

**Aprovado:** funcionalidades e políticas funcionais descritas.  
**Pendente:** parâmetros técnicos e fluxos detalhados acima.  
**Programação:** não iniciada.
