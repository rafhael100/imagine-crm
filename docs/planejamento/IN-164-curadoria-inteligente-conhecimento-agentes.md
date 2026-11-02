# IN-164 — Curadoria Inteligente do Conhecimento dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (resposta "ok").  
**Fase:** somente planejamento e documentação; **não iniciar programação, operações de produção, exclusões reais ou contratação de serviços**.  
**Escopo:** Biblioteca Inteligente de Autocorreções da Imagine, conhecimentos estruturados, soluções/hipóteses, rascunhos, versões e playbooks de sete agentes técnicos especializados e do Agente Supervisor Técnico.  
**Referências:** IN-130 (incidentes), IN-137/IN-138 (riscos e uma autorização humana para ações críticas), IN-139 (biblioteca e aprendizado), IN-140 (avaliação contínua), IN-153 (inventário), IN-155 (homologação), IN-156 (autodiagnóstico), IN-160/IN-162 (logs e políticas de retenção), IN-163 (ciclo de vida e retenção de 24 meses após arquivamento).

## Objetivo

Organizar e melhorar continuamente a **qualidade, validade, atualização e utilidade do conhecimento técnico dos agentes**, inclusive soluções que ainda não foram homologadas. Permitir curadoria apoiada por IA sem transformar hipóteses em soluções confirmadas, sem perder informações relevantes ao consolidar duplicidades e **sem permitir que agentes homologuem, ativem ou ampliem seus próprios procedimentos executáveis**.

## Funcionalidades e decisões aprovadas

1. **Revisão de rascunhos:** identificar soluções capturadas, propostas, incompletas ou pendentes de validação, exibindo autoria, contexto, evidências, estado e pendências de revisão.
2. **Detecção de duplicidades:** sugerir consolidação de conhecimentos semanticamente semelhantes, levando em conta causa, versão, ambiente, serviço, sintomas, condições e restrições; não fundir automaticamente registros legítimos diferentes apenas por texto semelhante.
3. **Qualidade e confiabilidade:** distinguir claramente hipótese, relato não verificado, tentativa mal sucedida, solução comprovada e procedimento homologado. A IA pode avaliar e sugerir classificação, mas uma afirmação de sucesso exige evidências verificáveis.
4. **Detecção de obsolescência:** comparar conhecimentos, playbooks, versões de componentes, serviços e dependências com o inventário da IN-153 e os resultados operacionais da IN-140; sinalizar incompatibilidades e alterações que possam invalidar uma solução.
5. **Revisão por inatividade:** **rascunhos sem movimentação por 90 dias** entram em revisão e são sinalizados aos responsáveis. **O marco de 90 dias não autoriza exclusão, arquivamento compulsório ou homologação automática**.
6. **Curadoria/arquivamento inteligentes:** os agentes podem organizar, classificar e **sugerir arquivamento** de conhecimento obsoleto, duplicado ou sem relevância, registrando razões e vínculos; a efetivação deve seguir políticas e permissões homologadas, com proteção especial para versões ainda ativas.
7. **Histórico e rastreabilidade:** manter vínculos entre itens consolidados, referências aos incidentes, evidências minimizadas, autoria, versões, mudanças de classificação, revisões, homologações e desativações.
8. **Aprendizado contínuo:** usar observações de curadoria, falhas, revisões e procedimentos corrigidos para melhorar a busca contextual e a precisão das soluções recomendadas, sem autoconceder autonomia executável.

## Regras para rascunhos — prazo de 90 dias

- A contagem de inatividade considera o instante da última **movimentação relevante e auditável** do rascunho, definida tecnicamente antes da implementação. Consultas triviais, acessos automáticos ou leituras pela IA não devem reiniciar artificialmente o prazo.
- Ao completar **90 dias sem movimentação**, o item será **marcado para revisão**, com aviso ao responsável e sugestão de ação: completar informações, submeter a testes, relacionar a solução existente, arquivar ou manter sob justificativa.
- A revisão distingue rascunhos com valor potencial para segurança/continuidade daqueles sem evidência suficiente; não transformar uma hipótese em procedimento executável.
- A regra **não estabelece prazo de descarte direto de rascunhos não arquivados**. Se o rascunho for legitimamente arquivado, aplicar a IN-163: **24 meses a partir do arquivamento**, ressalvadas exceções legais ou de preservação.
- O agente não pode criar alterações artificiais ou reclassificações sem conteúdo substancial apenas para evitar a revisão periódica.

## Critérios de qualidade e classificação

Cada conhecimento poderá conter, conforme aplicável:
- ID estável, título, categoria, agente/especialidade e serviço;
- estado de validação: hipótese, observação, tentativa falha, solução comprovada, procedimento em homologação, homologado, suspenso ou arquivado;
- fonte e evidências técnicas verificáveis, com ligação ao incidente sem copiar logs integrais;
- versão de software, ambiente, dependências e limites de aplicabilidade;
- riscos, impactos, efeitos adversos, critérios de falha e condições de segurança;
- autoria, versionamento, data da última revisão relevante, responsável e histórico de alterações;
- referências a soluções correlatas, versões substituídas e registros consolidados;
- homologador humano e escopo autorizado quando houver playbook executável.

**Critério fundamental:** a **semelhança entre textos** não equivale à mesma causa, ao mesmo risco ou à mesma solução. Considerar ambiente e versões para evitar generalização inadequada.

## Gestão de duplicidades e consolidação

1. Identificar candidatos por conteúdo, sintomas, causa, contexto e dependências.
2. Verificar se representam **o mesmo conhecimento** ou variantes legítimas com condições diferentes.
3. Propor registro de referência principal e vínculos com versões/variantes, **preservando origem, diferenças, histórico de execução e aprendizados**.
4. Classificar o impacto da consolidação; se envolver alteração de playbook ativo, autorização, limites ou execução futura, aplicar processo de revisão/homologação da IN-155.
5. Executar mudanças de organização por procedimentos autorizados e auditáveis; **não eliminar silenciosamente conteúdos úteis nem reescrever evidências históricas**.
6. Verificar que a consolidação não misturou dados/segredos entre clientes, ambientes ou perfis com permissões diferentes.

## Obsolescência, suspensão e arquivamento

- Quando uma mudança de versão, serviço, credencial ou infraestrutura tornar um playbook potencialmente inseguro, **suspender novas execuções de modo preventivo quando as políticas homologadas autorizarem**, abrir ocorrência e solicitar revisão; não presumir obsolescência somente pela ausência de uso recente.
- Agentes podem **sugerir arquivamento** e produzir justificativa, escopo e efeitos; efetivar arquivamento e eventual desativação apenas conforme permissões e procedimentos apropriados.
- Uma versão **arquivada não pode executar automaticamente**. Para reativar, revisar contexto, riscos e cumprir homologação aplicável.
- Conhecimento ativo e necessário não perde validade apenas porque envelheceu; a revisão de qualidade continua pertinente.
- Após arquivamento, **24 meses de retenção padrão** contados da data real de arquivamento, sem reiniciar por consulta ou referência. Exclusão segura após esse prazo, quando elegível, com ressalvas de preservação (IN-163).
- Não aplicar automaticamente os 90 dias de auditoria de filas ou 180 dias de incidentes críticos a toda a biblioteca; logs originais e conhecimentos estruturados têm políticas independentes (IN-162/IN-163).

## Limites dos agentes e homologação humana

- Os agentes podem **organizar, identificar, recomendar, classificar e preparar revisões**, dentro de ferramentas e permissões previamente concedidas.
- Nenhum agente pode **homologar sozinho** procedimento novo, elevar sua própria autonomia, criar permissão de execução, ocultar risco ou reabilitar versão suspensa/arquivada por simples decisão do modelo.
- Playbooks de **baixo ou moderado risco** seguros podem ser executados automaticamente somente quando **já homologados, ativos e dentro do escopo** (IN-155); a curadoria não altera essa condição.
- Uma intervenção específica de risco alto/crítico continua exigindo **uma única autorização humana**, quando necessária, conforme IN-137/IN-138. Não criar dupla aprovação.
- Mudanças de classificação que afetem permissões de execução exigem checagem independente e a homologação adequada; a classificação fornecida por IA não é autorização.
- Nenhum agente pode apagar evidências sob preservação excepcional, guardar credenciais em memória, transferir dados de clientes entre contextos ou contratar novos recursos pagos sem autorização (IN-142/IN-151).

## Fluxo funcional aprovado

1. Capturar caso/solução com origem e confiança inicial; registrar como rascunho ou conhecimento.
2. Consultar duplicidades e versões existentes na biblioteca, distinguindo casos aparentemente semelhantes.
3. Conferir contexto, evidências, aplicabilidade e compatibilidade com inventário atualizado.
4. Classificar qualidade, estágio e pendências, com resultado auditável e justificativa.
5. Se rascunho completar **90 dias de inatividade relevante**, sinalizar **revisão**, sem eliminação automática por esse motivo.
6. Sugerir completar evidência, consolidar vínculos, submeter a testes, revisar ou arquivar; aplicar apenas mudanças permitidas por política.
7. Playbook novo/modificado segue homologação pela IN-155 antes de receber capacidade de execução.
8. Quando obsoleto, suspender execução insegura se autorizado e efetivar arquivamento mediante governança aplicável; iniciar contagem de 24 meses.
9. Monitorar utilidade e resultados, registrar decisões, incidentes correlatos, aprendizado e revisões.

## Indicadores propostos

- Volume de conhecimentos ativos, rascunhos, hipóteses, comprovados, homologados, suspensos, arquivados e expirados.
- Rascunhos com **90 dias ou mais de inatividade**, pendências de revisão e idade média de rascunhos.
- Duplicidades candidatas, registros consolidados com rastreabilidade e variantes preservadas.
- Conhecimentos incompatíveis ou obsoletos sinalizados, revisados, suspensos e arquivados.
- Taxa de reuso de soluções comprovadas e erros atribuíveis a conhecimento obsoleto.
- Itens arquivados próximos de completar 24 meses, bloqueios de preservação e descarte autorizado.
- Tempo e esforço de curadoria; redução de diagnósticos incorretos por melhora da biblioteca.

## Pendências técnicas antes da implementação

1. Definição verificável de **movimentação relevante** e referência temporal para os 90 dias de inatividade.
2. Processo de revisão e notificações, responsáveis, prioridades e decisão sobre manutenção de rascunhos não arquivados.
3. Critérios de avaliação de qualidade/confiança, fontes de evidência, trilha auditável e separação entre hipótese e sucesso verificado.
4. Critérios de duplicidade, semelhança, equivalência técnica, consolidação com históricos e preservação de variantes.
5. Matriz de operações de curadoria permitidas aos agentes por perfil e risco; revisão/homologação de procedimentos executáveis.
6. Integração com catálogo de ativos, versões, incidentes, avaliações de agentes e Central de Observabilidade.
7. Arquivamento, preservação excepcional e eliminação de versões arquivadas após 24 meses conforme IN-163.
8. Proteção de informações sensíveis, segregação por empresa/ambiente e LGPD.
9. Custos, volume e meios de armazenamento, indexação e consulta; sem contratação automática de recursos.

**Aprovado:** curadoria de rascunhos, identificação de duplicidades, distinção entre hipóteses e soluções confirmadas, detecção de obsolescência, **revisão de rascunhos após 90 dias de inatividade**, sugestões de arquivamento, histórico/auditoria e aprendizado contínuo.  
**Ressalva:** o prazo de **90 dias aciona revisão**, não descarte; conhecimento arquivado segue a **retenção de 24 meses da IN-163**.  
**Programação:** não iniciada.
