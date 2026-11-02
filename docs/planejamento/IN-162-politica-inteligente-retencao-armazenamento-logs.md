# IN-162 — Política Inteligente de Retenção e Armazenamento de Logs

**Status:** APROVADA funcionalmente pelo responsável do projeto em **02/11/2026** (confirmação “ok” à proposta e à tabela de prazos).  
**Etapa:** exclusivamente planejamento/documentação; **não iniciar programação, aplicar limpezas reais, ativar coletores ou contratar armazenamento**.  
**Escopo:** telemetria técnica de Sites Imagine, servidores, APIs principal e de contingência, CRM Imagine SaaS, Portal Imagine, n8n, automações, filas, agentes técnicos e Central de Observabilidade.  
**Referências:** IN-101 (backups), IN-127/IN-128 (ciclo e auditoria das filas de leads), IN-139 (biblioteca de aprendizado), IN-142 (custos/capacidade), IN-147 (exclusão de payload não entregue após 18 dias), IN-153 (inventário), IN-157/IN-158 (modelos e consumo), IN-159 (Central de Observabilidade), IN-160 (logs, métricas e rastreamento), IN-161 (qualidade dos dados).

## Objetivo

Definir **prazos padrão diferenciados por categoria** para registros técnicos, controlar volume de armazenamento e custo, aplicar limpeza segura e automatizada quando o prazo vencer e preservar evidências necessárias por obrigação legal, contratual ou investigação autorizada. O histórico reutilizável de soluções dos agentes **não deverá depender da guarda integral dos logs originais**.

## Tabela de retenção aprovada

| Categoria | Prazo padrão aprovado | Observações |
|---|---:|---|
| Logs de depuração (**debug**) | **7 dias** | Informações de diagnóstico de curta duração, minimizadas e protegidas; não ativar detalhamento que exponha segredos ou payloads por padrão. |
| Logs operacionais de servidores, sites e APIs | **30 dias** | Eventos técnicos operacionais que não estejam sujeitos a política específica de auditoria, segurança ou obrigação legal. |
| Rastreamento distribuído (**traces**) | **30 dias** | Dados de correlação e latência entre componentes, sem payload pessoal completo. |
| Métricas históricas de desempenho | **90 dias** | Séries de disponibilidade, erro, latência, recursos, filas e capacidade; agregações futuras poderão ter política distinta quando aprovada. |
| Auditoria técnica minimizada das filas de leads | **90 dias — decisão anterior preservada** | IN-128: metadados de recebimento, tentativas, entrega, falhas, vencimento e descarte, sem conservar o conteúdo completo do formulário apenas para auditoria. |
| Registros de incidentes críticos e eventos de segurança | **180 dias** | Padrão para evidências técnicas relevantes dessa categoria, salvo retenção legal/contratual ou preservação excepcional. |
| Histórico estruturado de soluções e aprendizado dos agentes | **Conforme política própria da biblioteca — prazo ainda a definir** | IN-139/IN-155: preservar conhecimento técnico útil e versionado separadamente, minimizando e anonimizado quando adequado. |

**Interpretação:** esses prazos são **padrões operacionais aprovados**, sujeitos a guarda excepcional legítima e a requisitos legais/contratuais aplicáveis. Não estender todas as categorias indiscriminadamente ao prazo de 180 dias. Caso um mesmo evento tenha naturezas diferentes, a classificação, os controles de acesso e a retenção apropriada precisam ser definidos por política verificável para evitar duplicação sem finalidade.

## Distinção obrigatória entre logs e conteúdo dos leads

A política de logs **não altera** as decisões anteriores sobre os dados temporários dos formulários:

- **Lead efetivamente confirmado no CRM:** cópia temporária na integração será limpa **24 horas após confirmação**, preservando o registro principal no CRM, conforme IN-128.
- **Lead não entregue ao CRM:** fila normal de **3 dias** e fila de falhas de **15 dias adicionais**, **18 dias totais**, com aviso 72h antes, alerta crítico no vencimento e **permissão de exclusão automática do payload temporário ao completar 18 dias**, após verificação final, conforme IN-147.
- **Logs técnicos de auditoria da fila:** conservados minimizados por **90 dias**, sem reaproveitá-los para reproduzir conteúdo completo excluído nem criar quarentena de leads.
- **Logs operacionais gerais:** não devem conter cópia completa do lead; a expiração de um tipo de log não significa apagar registro efetivamente entregue ao CRM.

## Regras de limpeza automática e conformidade

1. **Classificação na origem:** cada evento recebe categoria, proprietário, serviço/ambiente, finalidade, política de retenção aplicável e metadados mínimos de rastreabilidade, sem expor credenciais.
2. **Expiração e limpeza automática:** ao vencer a retenção padrão, dados elegíveis entram em procedimento seguro de exclusão, registrando execução e falhas em auditoria minimizada; não depender de intervenção humana individual para cada log.
3. **Preservação excepcional:** bloqueio específico de descarte quando existir investigação formal, incidente ainda dependente da evidência, ordem de preservação, obrigação legal ou contratual válida. Exceções devem ter responsável, justificativa, escopo, identificação dos conjuntos abrangidos, revisão e evento auditável.
4. **Sem retenção indefinida por padrão:** preservação excepcional não deve virar extensão arbitrária ou ilimitada para todos os logs; revisar necessidade e finalizar quando cessar a justificativa.
5. **Privacidade e minimização:** proibir armazenamento habitual de senhas, tokens, chaves, dados de autenticação, conteúdo integral dos formulários e dados pessoais desnecessários em logs, métricas, traces ou material de aprendizado.
6. **Limpeza com múltiplas cópias:** considerar logs distribuídos, índices, réplicas, arquivos compactados e política de backup, distinguindo deleção operacional e expiração segura de backups; não prometer eliminação instantânea em cópias imutáveis antes do prazo técnico/obrigação aplicável.
7. **Auditoria de eliminação:** registrar identidade do processo autorizado, categoria, escopo, período, quantidade/tamanho aproximados, resultado e exceções, sem reintroduzir conteúdos que deveriam ter sido apagados.
8. **Configuração e revisão controladas:** alterações de prazos e exceções seguem gestão de mudanças e permissões; agentes não podem ampliar indefinidamente a retenção, ocultar descarte ou apagar evidências por decisão livre de IA.

## Armazenamento e custos

- Organizar registros por categoria, criticidade e prazo, com **compressão, particionamento, indexação e níveis de armazenamento** quando tecnicamente e economicamente adequados.
- Dimensionar métricas de volume, taxa de ingestão, crescimento, consultas, tamanho e custo antes de selecionar ferramentas.
- Aplicar redução de verbosidade, filtros e amostragem **somente quando não comprometer** auditoria obrigatória, rastreamento de incidentes críticos e evidências necessárias.
- Gerar alertas de capacidade e tendência de esgotamento, com diagnóstico do custo projetado e oportunidades de otimização.
- **Não contratar espaço adicional, serviços pagos, upgrades ou recursos tarifados sem autorização humana** conforme IN-142/IN-158.
- Cuidar para que armazenamento excessivo ou ingestão de logs não sobrecarregue APIs de recepção, processamento de leads ou os caminhos independentes de contingência.

## Preservação do aprendizado técnico dos agentes

- A Biblioteca de Autocorreções da IN-139 e os procedimentos homologados da IN-155 são **artefatos de conhecimento distintos** dos logs técnicos brutos.
- Antes do vencimento de logs relevantes, os agentes e mecanismos autorizados poderão registrar causa verificada, solução, versões, condições, testes, resultado e riscos, em formato **minimizado, seguro e versionado**.
- Não salvar senhas, credenciais, conteúdo completo de leads ou dados pessoais identificáveis apenas para manter aprendizado.
- **Prazo de retenção do conhecimento técnico permanece pendente de aprovação específica**; este documento não cria retenção ilimitada.
- A expiração de logs originais não deve apagar automaticamente uma solução homologada, mas não permite conservar cópias brutas em campos do conhecimento como forma de contornar retenção.

## Central administrativa, alertas e governança

- Consultar a aplicação de prazos, consumo, expiração, exceções, falhas de limpeza e tendência de capacidade por serviço no Portal Imagine, integrando-se às IN-159/IN-160.
- Acesso por perfis internos autorizados, com segregação por cliente/empresa, registro de consultas e exportações sensíveis e proteção de informações de infraestrutura.
- Agentes podem sugerir otimizações e executar limpezas por **procedimentos seguros previamente homologados**, com checagem determinística da categoria, prazo e bloqueios de preservação.
- Exclusão de evidências protegidas, alteração crítica de política ou intervenção destrutiva não autorizada não pode ocorrer por mera sugestão de IA. Quando uma ação crítica exigir aprovação, usar **uma única aprovação humana**, sem dupla aprovação, conforme IN-137/IN-138.
- A indisponibilidade da Central de Observabilidade não deverá bloquear os serviços essenciais nem ser interpretada como autorização para limpeza indiscriminada.

## Exemplo funcional

Um log de erro operacional da API completa **30 dias**. O processo de retenção identifica a categoria, confirma que não há ordem de preservação ou vínculo que exija guarda excepcional, executa limpeza segura do conjunto elegível e registra auditoria minimizada. Se o evento tiver sido parte de incidente crítico com evidência especificamente classificada, aplicar a política de **180 dias** dessa evidência, em armazenamento controlado e minimizado, sem reter indiscriminadamente todos os logs da API.

## Indicadores previstos

- Volume armazenado, custo estimado, crescimento e retenção por categoria, serviço e ambiente.
- Quantidade de registros elegíveis, eliminados, retidos por exceção e com exclusão pendente/falha.
- Cumprimento dos prazos e ocorrências de armazenamento além do período sem justificativa.
- Integridade de evidências preservadas, acesso a registros sensíveis e auditoria das limpezas.
- Proporção de logs brutos que puderam expirar sem perda do conhecimento técnico homologado.

## Pendências antes de qualquer implementação

1. Modelo de classificação por evento, conflitos entre categorias, precedência de políticas e marco temporal exato para cálculo de vencimentos.
2. Desenho de expiração em armazenamento principal, índices, réplicas, arquivos compactados e backups técnicos.
3. Política de preservação excepcional, responsável, revisão, escopo, auditoria e integração com incidentes jurídicos/segurança.
4. Políticas de minimização por campo, mascaramento antes da coleta, acesso, LGPD e tratamento de obrigações legais/contratuais.
5. Definição específica de retenção do **histórico estruturado de soluções dos agentes** e de eventuais outras auditorias não abrangidas pela tabela.
6. Tecnologia de armazenamento, compressão, indexação, amostragem, projeção de custos, alertas e contingência.
7. Procedimentos de limpeza homologados, testes de integridade e evidência de descarte sem guardar novamente o conteúdo expirado.
8. Política operacional para registros com dados mistos, duplicações de eventos entre sistemas e investigação iniciada próximo à expiração.
9. Métricas de qualidade e disponibilidade da limpeza/telemetria integradas às IN-159/IN-161.

**Aprovado:** prazos padrão de 7, 30, 30, 90, 90 e 180 dias conforme tabela; limpeza automática; armazenamento otimizado; proteção dos dados; preservação excepcional controlada; alertas de capacidade; vedação de despesas automáticas; aprendizado técnico separado dos logs brutos.  
**Pendente:** prazo da biblioteca de conhecimento, exceções específicas, parâmetros técnicos, ferramentas, custos e testes.  
**Programação:** não iniciada.
