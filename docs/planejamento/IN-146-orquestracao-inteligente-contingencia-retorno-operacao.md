# IN-146 — Orquestração Inteligente de Contingência e Retorno à Operação

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026 (resposta "ok").  
**Fase:** somente planejamento e documentação; **não iniciar programação, mudanças de infraestrutura, testes operacionais nem contratação**.  
**Escopo:** Imagine Sites, APIs de formulários principal e contingência, filas de leads, integrações com CRM Imagine, serviços de suporte e Central de Incidentes Técnicos.  
**Referências:** IN-124 (APIs redundantes e confirmação após persistência durável), IN-126 a IN-129 (filas, retenção, monitoramento e recuperação de leads), IN-130 a IN-136 (incidentes, agentes, comunicação e plantão), IN-137/IN-138 (uma aprovação humana para intervenções críticas), IN-139/IN-140 (aprendizado e avaliação), IN-141/IN-142 (prevenção e capacidade), IN-143 (continuidade operacional), IN-144 (prioridades e RTO/RPO pendentes) e IN-145 (testes de continuidade).

## Objetivo

Permitir que agentes técnicos e mecanismos de contingência **detectem falhas, acionem alternativas homologadas, acompanhem a recuperação e realizem o retorno controlado ao ambiente principal (failback)**, sem perder dados confirmados, sem processamento duplicado indevido e sem alternância instável entre destinos. A operação deve ser o mais automática possível **dentro dos limites de segurança previamente aprovados**.

## Funcionalidades aprovadas

1. **Detecção de indisponibilidade real:** combinar verificações de saúde e capacidade de executar a operação real (ex.: persistência de submissão sintética), evitando decisões baseadas exclusivamente em um único ping ou alerta isolado.
2. **Ativação de contingência:** quando a principal falhar, encaminhar novas operações para o serviço alternativo **previamente configurado e homologado**, utilizando um mecanismo com dependências e limites conhecidos.
3. **Prevenção de conflitos:** impedir condições de concorrência ou *split-brain* que gerem alterações incompatíveis, duplicações ou resultados divergentes durante a troca entre ambientes.
4. **Monitoramento da recuperação:** avaliar saúde, estabilidade e funcionalidade real do serviço principal antes de considerar possível o retorno.
5. **Retorno à operação normal (failback):** executar retorno automático **somente quando o procedimento for seguro, homologado e autorizado**; se houver risco elevado ou condição não prevista, manter a contingência ativa e solicitar uma autorização humana.
6. **Reconciliação de dados:** verificar e reconciliar mensagens persistidas, pendentes e processadas nos dois ambientes, preservando IDs estáveis dos envios, histórico de confirmações, rastreabilidade e integridade.
7. **Aprendizado contínuo:** registrar cada troca, recuperação, tentativas, divergências e resultados, alimentando a biblioteca de soluções (IN-139) e os indicadores de eficácia (IN-140).

## Regras críticas de integridade de leads

- **Recepção confirmada apenas com armazenamento durável.** O site não exibirá sucesso se nenhuma das duas APIs confirmar recebimento persistido, conforme IN-124.
- **APIs com persistência independente.** A principal e a de contingência devem suportar gravação durável em infraestrutura separada, preferencialmente fornecedores distintos. A arquitetura de orquestração não pode criar uma nova dependência única que invalide essa proteção.
- **Identificador idempotente por submissão.** Cada envio deve portar um identificador estável em tentativas, troca de API e reprocessamento, para permitir reconhecer a mesma submissão ao reconciliar eventos. A tecnologia e a geração/validação exata do ID serão detalhadas posteriormente.
- **Não confundir recepção, persistência e entrega ao CRM.** Receber um lead na API de contingência não significa que o CRM já o processou. Os estados e suas transições devem ser auditáveis.
- **Deduplicação sem perda indevida.** Uma tentativa repetida não pode gerar dois leads equivalentes indevidamente, mas detecção de duplicata ambígua não autoriza descartar uma submissão genuína.
- **Reprocessamento seguro.** Mensagens persistidas em cada ambiente devem continuar rastreáveis até entrega confirmada ao CRM, falha/escalonamento ou tratamento explícito autorizado.
- **Retenção das filas preservada.** Permanecem as regras de **3 dias de fila normal e 15 dias de fila de falhas** contados da primeira persistência; alternância entre ambientes não reinicia a contagem nem permite descarte silencioso. A destinação final de não entregues após 18 dias segue pendente (IN-128).
- **Reconciliação controlada.** Quando um ambiente retorna, confrontar eventos por IDs e estados e verificar diferenças de processamento antes de corrigir qualquer inconsistência, especialmente se houver risco de sobrescrever dados.

## Condições para troca automática (failover)

- Serviço principal não atende os critérios de saúde definidos para receber novas operações.
- Serviço alternativo está apto e previamente homologado para receber/persistir as operações correspondentes.
- A troca está dentro de escopo, permissões e limites de execução aprovados e não exige nova despesa/alteração crítica.
- Existência de registro do incidente, alertas proporcionais ao impacto e observação ativa da plataforma.
- Se ambas as vias estiverem indisponíveis, classificar incidente crítico e acionar em paralelo agente especializado e responsável humano, sem confirmar falsamente o recebimento de novos leads.

## Condições para retorno automático (failback)

- Saúde consistente da principal durante janela de observação configurável; **não alternar imediatamente por uma única sondagem bem-sucedida**.
- Sem indícios de instabilidade persistente ou risco de perda de disponibilidade do serviço de contingência.
- Reconciliação e estados das mensagens compatíveis com retorno ou plano de reconciliação seguro que não comprometa processamento.
- Troca limitada por mecanismos de *histerese*, número máximo de reversões, *cooldown* e controles de concorrência, a dimensionar.
- Procedimento previamente homologado para o ambiente e compatível com a matriz de riscos. **Ações críticas não homologadas dependem de uma única aprovação humana**, sem dupla aprovação.
- Possibilidade de suspender o retorno automático diante de divergências ou perda de integridade, mantendo a contingência quando saudável.

## Responsabilidades dos agentes

- **Agente de Integrações:** acompanhar recepção/entrega de formulários e leads, sinalizar falhas e recuperar processamento de integrações autorizado.
- **Agente de Infraestrutura:** observar estado de servidores, rede, processos e capacidade; aplicar procedimentos seguros homologados.
- **Agente de Automações:** coordenar e validar workflows dependentes da transição, quando autorizados.
- **Agente de Backup:** orientar recuperação com verificação de integridade, sem restauração destrutiva autônoma.
- **Agente de Segurança:** detectar risco de acesso indevido ou corrupção que possa impedir transições automáticas.
- **Agente Supervisor Técnico:** correlacionar incidentes, coordenar agentes, verificar conclusão e coletar lições; **não amplia permissões nem contorna aprovação humana**.
- **Administração Imagine:** aprovar intervenções críticas e gastos, configurar políticas e acompanhar histórico de contingência.

## Exemplo operacional

1. Health checks detectam que a API principal não consegue persistir novos envios.
2. O mecanismo de failover homologado encaminha novas submissões à API de contingência saudável, que confirma sucesso **somente após gravar duravelmente** cada envio.
3. Os agentes diagnosticam a principal e executam recuperação segura autorizada; abertura/alerta de incidente são registrados.
4. A principal restabelecida permanece em observação para comprovar estabilidade; novas submissões não oscilam entre destinos prematuramente.
5. O sistema compara identificadores e estados de persistência/processamento em cada ambiente, evitando entrega duplicada ao CRM.
6. Quando as pré-condições do playbook de failback estiverem satisfeitas, retorna as novas submissões à principal, mantendo processamento das filas remanescentes e validação de consistência.
7. Registra resultado e lição aprendida para prevenção e reutilização controlada.

## Restrições técnicas e de segurança

- Não confiar na análise textual de IA, isoladamente, para conceder acesso ou comandar alterações perigosas; uso de regras determinísticas, verificações e mecanismos de execução com permissões limitadas.
- Não realizar mudanças de DNS, credenciais, permissões, exclusão, sobrescrita de dados ou migração destrutiva automaticamente quando classificadas como críticas.
- Não contratar recursos, mudar plano de provedor ou gerar custos adicionais sem aprovação humana específica (IN-142).
- Não tratar *failover* como recuperação do backlog automaticamente; a recepção precisa continuar independente da conclusão do reprocessamento.
- Não presumir que failover elimina todos os pontos de falha; analisar DNS, autenticação, certificados, serviços externos, persistência, monitoramento e rotas de acesso.
- Registrar eventos de alteração de estado, saúde, decisões, autorização, execução, reconciliação, resultado e auditoria, minimizando dados pessoais.

## Questões pendentes para detalhamento técnico

1. Desenho exato do mecanismo de roteamento/failover sem ponto único de falha e sua relação com os clientes/sites.
2. Critérios quantitativos de saúde, janelas de observação, *cooldown*, limites de comutação e detecção de falso positivo.
3. Modelo de identificadores idempotentes, persistência de estados, deduplicação distribuída, reconciliação pós-falha e atrasos de sincronização.
4. Procedimentos específicos para failover/failback de cada serviço (API, fila, banco de dados, aplicação e integrações), distinguindo aplicações com e sem capacidade de escrita simultânea.
5. Ambientes de teste, cenários de falha parcial/partição de rede, métricas, logs, alçadas operacionais e avaliação de custos.
6. Definição de RTO/RPO por serviço (valores numéricos ainda **não aprovados** na IN-144).
7. Políticas de acesso, autenticação técnica, autorização humana para riscos críticos e guarda de evidências.
8. Proteção de leads já confirmados e política final para registros não entregues após a janela de 18 dias, ainda não aprovada.

**Aprovado:** detecção de falha, acionamento automático de contingência homologada, prevenção de conflitos e duplicações, observação da recuperação, retorno automático seguro e autorizado, reconciliação de dados e aprendizado contínuo.  
**Pendente:** arquitetura executável, parâmetros técnicos, provas de consistência, dimensionamento e testes.  
**Programação:** não iniciada.
