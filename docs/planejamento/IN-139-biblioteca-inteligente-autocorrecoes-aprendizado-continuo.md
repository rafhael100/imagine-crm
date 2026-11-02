# IN-139 — Biblioteca Inteligente de Autocorreções e Aprendizado Contínuo

**Status:** APROVADA funcionalmente em 01/11/2026, com reforço expresso do responsável: **“muito importante os agentes irem aprendendo com cada resolução”**.  
**Fase:** planejamento e documentação; NÃO iniciar programação, treinar modelos, implantar agentes nem assumir novas despesas.  
**Escopo:** Central de Incidentes Técnicos do Portal Imagine e agentes especializados + Supervisor Técnico.  
**Dependências:** IN-129 a IN-138, especialmente IN-130 (incidentes), IN-131 (autonomia), IN-132 (agentes), IN-137 (aprovação humana simplificada) e IN-138 (matriz de riscos sem dupla aprovação).

## Objetivo e diretriz essencial

Criar uma **biblioteca central, versionada, pesquisável e auditável de conhecimento técnico e procedimentos de autocorreção**. **Todo incidente analisado, especialmente cada resolução confirmada, deverá contribuir para o aprendizado operacional dos agentes**, reduzindo retrabalho, tempo de diagnóstico e recorrência de falhas.

O aprendizado consiste em **memória estruturada, recuperação contextual, comparação de casos, síntese de lições e avaliação de procedimentos**. Isso **não implica retreinamento autônomo de pesos de modelos**, nem concede permissão automática para criar/alterar código ou executar novas ações de maior risco.

## Especialidades cobertas
- **Integrações:** formulários, APIs redundantes, CRM e recuperação de leads.
- **Infraestrutura:** servidores, hospedagem, disponibilidade e desempenho.
- **Domínios:** DNS, SSL e certificados.
- **Automações:** workflows n8n e falhas de execução.
- **Backup:** cópias de segurança, integridade e recuperação.
- **Segurança:** detecção e tratamento de incidentes autorizados.
- **Publicação:** implantação/atualização de sites e reversão controlada.
- **Supervisor Técnico:** coordenação, avaliação das soluções, identificação de recorrências e acompanhamento da qualidade.

## Funcionalidades aprovadas

1. **Registro de cada incidente e sua resolução:** guardar, em estrutura autorizada, sintomas, origem, diagnóstico, causa identificada, ações tentadas, ações bem-sucedidas/fracassadas, resultado verificado, impacto e contexto técnico útil.
2. **Aprendizado a cada resolução:** após resolver um caso, o agente deverá produzir uma **lição aprendida** estruturada com sinais que permitem reconhecer o problema, a causa demonstrada, a solução utilizada, condições de aplicação, possíveis riscos e método de validação.
3. **Memória compartilhada e contextual:** os agentes podem consultar conhecimentos homologados e casos anteriores relevantes, respeitando segregação entre clientes, ambientes e permissões de acesso.
4. **Busca por semelhança:** ao detectar erro novo, consultar incidentes e soluções semelhantes antes de sugerir ou aplicar um procedimento; registrar a fonte e sua versão para rastreabilidade.
5. **Biblioteca de playbooks versionados:** procedimentos devem declarar escopo, pré-condições, identidade/permissões, nível de risco, etapas, validação, limites de repetição, reversão (quando viável), proprietário e data/versão da homologação.
6. **Autocorreção com segurança:** executar automaticamente **somente playbooks previamente homologados, de risco baixo ou moderado, dentro das permissões e limites autorizados**, conforme IN-131 e IN-138.
7. **Homologação de novas soluções:** os agentes podem **propor automaticamente** novos procedimentos e melhorias com base nos casos resolvidos, mas **não podem promovê-los por conta própria** a automações executáveis; revisão e homologação humana/autorizada conforme o risco e a governança.
8. **Reutilização gradual e controlada:** resultados de procedimentos homologados alimentam estatísticas de acerto, falha e regressão. O Supervisor Técnico recomenda melhorias, suspensão ou revisão quando houver recorrência ou efeito adverso.
9. **Verificação de eficácia:** considerar resolução bem-sucedida apenas após evidências de recuperação do serviço. Falhas, soluções inconclusivas ou alternativas descartadas também podem gerar lições, explicitamente marcadas como **não confirmadas**.
10. **Escalonamento inteligente:** caso não exista procedimento seguro e aplicável, o agente registra diagnóstico e abre/encaminha caso à equipe humana; ações críticas continuam sujeitas a **uma única aprovação humana** quando exigida, sem dupla aprovação.
11. **Auditoria e proteção de dados:** preservar vínculo com o incidente e a versão de conhecimento consultada/executada; proibir segredos, tokens, senhas e dados pessoais desnecessários em memórias ou exemplos compartilhados.
12. **Não repetição de falhas:** quando um procedimento demonstrar ineficácia, efeitos adversos ou risco em determinado contexto, documentar limitação e impedir sua execução automática naquele contexto até revisão.

## Ciclo de aprendizado — aprovado funcionalmente

**Detecção → Diagnóstico → Consulta aos casos e soluções anteriores → Correção autorizada ou escalonamento humano → Teste de recuperação → Registro da resolução → Extração automática de lições → Sugestão de melhorias → Homologação controlada do novo procedimento → Reutilização segura em problemas semelhantes.**

O registro e a sugestão de lições são automáticos. A concessão de novas ferramentas, permissões ou capacidade de executar um novo playbook **não é automática**.

## Exemplo ilustrativo

- Um site Imagine apresenta erro de entrega de leads porque um processo de envio autorizado parou.
- O Agente de Integrações abre caso, consulta a biblioteca, confirma sintomas e pré-condições, executa reinício controlado apenas se esse playbook já estiver homologado e dentro do seu nível de autonomia, e testa a entrega com idempotência.
- Após verificar recuperação, o agente registra sinais, causa, procedimento, resultado e limites, sem armazenar dados pessoais dos leads.
- Se identificar uma melhoria possível, cria sugestão para homologação; outros agentes poderão consultar a solução e usá-la **somente quando o mesmo contexto e as mesmas permissões autorizarem**.

## Critérios de confiança do aprendizado

- Distinguir **fato verificado**, **hipótese**, **ação tentada sem sucesso** e **solução homologada**.
- Priorizar soluções validadas sobre sugestões experimentais; citar contexto e versão, não assumir que dois ambientes semelhantes são equivalentes.
- Proteger contra instruções maliciosas contidas em logs, arquivos, formulários, tickets e conteúdo externo.
- Evitar generalizar alterações críticas de um cliente para outro; respeitar isolamento e políticas contratuais.
- Registrar responsável pela homologação e pela eventual desativação de playbook.
- Preservar reversibilidade quando possível e limitar execuções repetidas.

## Pontos pendentes para especificar antes da implantação

- Modelo de dados para casos, lições, procedimentos, versões, evidências, validação, revogação e desempenho.
- Mecanismos de recuperação contextual (busca estruturada e/ou semântica), fronteiras de acesso e anonimização para uso entre clientes.
- Métricas de aprendizado (redução de tempo de resolução, recorrência, taxa de sucesso, reversões, escalonamentos e falhas).
- Processo de homologação, teste em ambiente controlado, revisão e desativação de playbooks, alinhado ao risco da ação.
- Aplicação técnica do ciclo de vida e retenção de conhecimentos da **IN-163**: manter conhecimentos ativos enquanto necessários, arquivar versões obsoletas e reter por **24 meses após o arquivamento**, salvo exceção legítima. A retenção dos registros de incidentes e dos logs de leads continua independente.
- Governança de sugestões imprecisas, falsos positivos e mudanças de infraestrutura ou versões de software.
- Custos e limites de modelos de IA, ferramentas e armazenamento.
- Integração com futura avaliação contínua do desempenho dos agentes.

## Delimitação da decisão

**Aprovado:** biblioteca central, versionamento, registro e aprendizado a cada resolução, reutilização contextual das lições, proposta de novas soluções, homologação antes de execução automática, acompanhamento do Supervisor Técnico e preservação da autonomia segura.  
**Pendente:** modelagem detalhada, aplicação técnica da retenção aprovada na IN-163, critérios operacionais e implementação.  
**Programação:** não iniciada.


## Atualização posterior — IN-163 (02/11/2026)

A política de ciclo de vida do conhecimento foi aprovada na [IN-163 — Ciclo de Vida do Conhecimento dos Agentes Técnicos](IN-163-ciclo-vida-conhecimento-agentes.md): manter conhecimento e playbooks **ativos enquanto necessários**; versões **arquivadas permanecem por 24 meses contados do arquivamento**, com exclusão controlada quando elegível, preservação excepcional legítima, versionamento e impossibilidade de execução de procedimentos arquivados. A retenção de logs e registros brutos de incidentes continua regida pelas políticas específicas, sem extensão automática para 24 meses. Esta decisão posterior prevalece sobre qualquer indicação histórica de que o prazo da biblioteca ainda estivesse pendente.
