# IN-152 — Gestão Inteligente de Vulnerabilidades e Atualizações de Segurança

**Status:** APROVADA funcionalmente em 02/11/2026 (confirmação "ok").  
**Etapa:** planejamento e documentação; **não iniciar programação, aplicar atualizações reais, executar testes em produção ou contratar serviços**.  
**Escopo:** servidores, sistemas operacionais, sites Imagine, bibliotecas e dependências, PHP/Laravel, Node.js, n8n, APIs, filas, CRM Imagine e componentes de infraestrutura.  
**Referências:** IN-101 (backups), IN-130–IN-132 (incidentes e agentes), IN-137/IN-138 (risco e autorização única), IN-139/IN-140 (aprendizado e avaliação), IN-141 (prevenção), IN-142 (capacidade/custos), IN-143–IN-146 (continuidade/testes/recuperação), IN-150 (segurança das APIs), IN-151 (gestão de credenciais).

## Objetivo
Manter os serviços Imagine protegidos contra vulnerabilidades conhecidas, componentes desatualizados e falhas de segurança. Os agentes técnicos identificarão, priorizarão, testarão, corrigirão e validarão problemas de segurança **com máxima automação segura e mínima intervenção humana**, sem impor atualizações que causem interrupção ou comprometam dados de clientes.

## Capacidades aprovadas

1. **Monitoramento contínuo de vulnerabilidades conhecidas:** acompanhar comunicados confiáveis de segurança, advisories de fornecedores e sinais observados nos ambientes. Diferenciar vulnerabilidade conhecida, suspeita, alerta ainda não confirmado e incidente de exploração.
2. **Verificação de componentes e dependências:** acompanhar as versões de sistemas operacionais, pacotes, bibliotecas e ferramentas em uso, incluindo, conforme aplicável, Laravel/PHP, Node.js, n8n, banco de dados, containers e serviços de borda.
3. **Análise de impacto:** identificar componentes efetivamente afetados, ambiente, exposição pública, explorabilidade, serviços dependentes e clientes potencialmente impactados; não tratar toda atualização disponível como urgente.
4. **Atualizações automáticas seguras:** permitir execução de correções de **baixo risco** ou **risco moderado com procedimento homologado**, limites específicos e permissões necessárias, desde que sejam testadas e sem efeitos relevantes não previstos.
5. **Testes prévios:** verificar compatibilidade, dependências, comportamento das APIs, filas, CRM, sites e automações, preferencialmente em ambiente isolado e com dados sintéticos. Registrar resultados e critérios de aceite.
6. **Reversão/recuperação:** planejar rollback de artefatos e configurações quando tecnicamente possível; tratar alterações irreversíveis e migrações de banco com controles de recuperação adequados, sem pressupor que retornar código automaticamente reverte dados.
7. **Aprendizado contínuo:** registrar vulnerabilidade, diagnóstico, atualização, testes, resultado, falha, rollback, recomendações e lições reutilizáveis na biblioteca de autocorreções homologadas.
8. **Supervisão técnica:** Agente de Segurança acompanha o risco; especialistas de Infraestrutura, Integrações, Automações, Backup e Publicação colaboram conforme impacto; Agente Supervisor Técnico monitora resultado, recorrências e riscos não resolvidos.

## Priorização, autonomia e aprovação humana

- Classificar tanto **a gravidade da vulnerabilidade** quanto o **risco da intervenção**, que são conceitos diferentes. Uma correção pequena pode atingir um componente muito crítico; não autorizar somente pelo tamanho do patch.
- Monitoramento, diagnóstico, verificação, comparação de versões e produção de recomendações são executáveis dentro das permissões concedidas.
- **Autocorreção:** executar atualização apenas quando o procedimento estiver previamente homologado, dentro do escopo autorizado, com pré-condições satisfeitas, verificação pós-alteração, limites de tentativa e plano de contingência apropriado.
- **Operação crítica:** mudanças que possam interromper serviços, afetar componentes produtivos importantes, modificar credenciais/permissões, sobrescrever dados ou exigir migração arriscada dependem de **uma única aprovação humana** quando classificadas como críticas ou fora do procedimento autorizado. Não existe dupla aprovação (IN-137/IN-138).
- **Urgência sem autorização:** agentes continuam monitorando, mitigando com medidas seguras já autorizadas e escalando para humano; a urgência não concede permissões adicionais.
- **Despesas:** aquisição de ferramentas, novos serviços, upgrades pagos ou recursos tarifados exigem aprovação humana de custo, conforme IN-142.

## Ciclo funcional aprovado

Inventariar componentes/versões → detectar vulnerabilidade ou atualização de segurança → confirmar aplicabilidade → estimar impacto, urgência, dependências e risco da intervenção → selecionar procedimento homologado e planejar testes/rollback → testar em ambiente apropriado → executar automaticamente se seguro e já autorizado, ou solicitar uma aprovação humana se crítico → monitorar saúde e regressões → validar proteção e integridade → registrar resultado, auditoria e lições aprendidas.

## Regras de segurança e estabilidade

- Priorizar correções de vulnerabilidades exploráveis e expostas, mas **não alterar automaticamente toda versão disponível** sem avaliação de compatibilidade.
- Proteger APIs principal e de contingência, buscando evitar que uma manutenção atualize/desabilite simultaneamente ambos os caminhos críticos de recepção de leads.
- Verificar que testes e deploys não eliminem, dupliquem ou alterem indevidamente leads; a entrega ao CRM continua exigindo comprovação efetiva de persistência, conforme IN-148/IN-149.
- Respeitar a política de integração: 3 dias de fila normal + 15 dias de falhas; não reiniciar prazo por manutenção; a exclusão de payload não entregue após 18 dias está autorizada na IN-147, com avisos e auditoria.
- Antes de atualizar, identificar compatibilidade de bibliotecas, sistema operacional, PHP/Node.js, bancos, n8n e outros serviços; inclusive impactos em fluxos e integrações de terceiros.
- Manter evidências de versão de origem, versão instalada, justificativa, testes, janela de intervenção quando aplicável, executante, autorização, resultado, estado do serviço e eventual rollback.
- Não incluir tokens, credenciais ou dados pessoais desnecessários em relatórios, logs, bases de aprendizado ou prompts; gerir segredos conforme IN-151.
- Ao detectar regressão após patch, abrir incidente e executar somente recuperação autorizada; evitar repetir automaticamente uma atualização que já demonstrou falha.
- Não permitir ao agente reclassificar unilateralmente o risco ou conceder a si mesmo autorização para atualizar ambientes críticos.
- Preservar independência entre backups de sites e backups do CRM, sem tratar restauração de um como substituta do outro.

## Exemplos ilustrativos

- **Biblioteca com atualização corretiva compatível:** agente identifica versão afetada, prepara análise, executa testes e sugere ou aplica atualização de baixo risco previamente homologada, verificando regressões e registrando resultado.
- **Falha crítica em API pública:** agente emite alerta, avalia exposição, aplica medidas seguras autorizadas e prepara proposta para mudança crítica com uma aprovação humana se exigida.
- **Atualização do n8n:** confirmar impactos nas automações e credenciais, testar workflows em ambiente isolado, definir plano de contingência e aplicar somente conforme nível de risco autorizado.
- **Atualização falha:** interromper tentativas repetidas, reverter artefatos quando possível, confirmar saúde da aplicação, registrar incidente e melhorar o playbook.

## Indicadores e relatórios
- Quantidade e distribuição de vulnerabilidades por gravidade, exposição e serviço.
- Tempo entre identificação, triagem, mitigação e correção verificada.
- Percentual de componentes atualizados, desatualizados ou com risco aceito explicitamente.
- Taxa de sucesso de atualizações homologadas, regressões, rollbacks e recorrência.
- Diferença entre itens corrigidos automaticamente, itens com intervenção humana e casos pendentes.
- Falhas de compatibilidade e dependências críticas descobertas durante testes.

## Pendências antes da implementação

1. Inventário técnico dos componentes reais, versões e dependências (base para a futura IN-153).
2. Fontes confiáveis e processos de ingestão/validação de avisos de segurança e mapeamento de aplicabilidade.
3. Critérios objetivos de prioridade e risco, periodicidade de análise, limiares e regras de acionamento.
4. Matriz de manutenção por serviço, janelas, fallback, ambientes de teste, backups e testes de regressão.
5. Catálogo de patches/procedimentos homologados executáveis automaticamente, limites e mecanismos determinísticos de autorização.
6. Política de gerenciamento de exceções, adiamentos, riscos residuais, aprovações e auditoria.
7. Retenção de telemetria e evidências técnicas, ferramentas escolhidas e custos de operação.
8. Procedimentos específicos para migrações de banco ou atualizações que não possuam rollback simples.
9. Interface administrativa e relatórios de vulnerabilidades, atualizações concluídas e pendências.

**Aprovado:** monitoramento de vulnerabilidades, verificação de dependências, análise de impacto, atualização segura autorizada, testes prévios, rollback quando possível, aprendizado com falhas e supervisão técnica.  
**Pendente:** inventário efetivo, ferramentas, matriz operacional, políticas quantitativas, testes e dimensionamento.  
**Programação:** não iniciada.
