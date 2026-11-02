# IN-165 — Versionamento e Homologação das Instruções dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável em 02/11/2026 (resposta "ok").  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, implantação, alterações reais de instruções, permissões ou infraestrutura**.  
**Escopo:** instruções operacionais, prompts de sistema e de especialidade, regras de roteamento, instruções de uso de ferramentas e configurações comportamentais dos sete agentes técnicos especializados e do Agente Supervisor Técnico Imagine.  
**Referências:** IN-131/IN-132 (autonomia e agentes), IN-137/IN-138 (matriz de risco e aprovação humana única), IN-139/IN-140 (aprendizado e avaliação), IN-142 (controle de custos), IN-151 (credenciais), IN-153 (inventário), IN-154/IN-155 (mudanças e procedimentos homologados), IN-156 (autodiagnóstico), IN-157 (gestão de modelos de IA), IN-160 (auditoria/telemetria), IN-163/IN-164 (ciclo de vida e curadoria do conhecimento).

## Objetivo e decisão central

Estabelecer **versionamento integral, testes antes de publicação, homologação proporcional ao risco, atualização controlada, reversão segura e trilha de auditoria** para as instruções que definem o comportamento dos agentes técnicos Imagine.

**Ajustes automáticos de baixo risco são permitidos somente dentro de parâmetros, escopo e procedimentos já homologados.** Mudanças relevantes de comportamento, autonomia, ferramentas, privilégios ou capacidade de produzir efeitos críticos **não podem ser promovidas automaticamente** por uma IA; devem passar pelo controle e pela **única aprovação humana aplicável**, conforme IN-137/IN-138.

## Funcionalidades aprovadas

1. **Versionamento por agente e instrução:** manter uma versão identificável, imutável após publicação, com histórico de alterações, autor, justificativa, origem, data, escopo e vínculo com testes e homologação.
2. **Testes de qualidade e segurança:** verificar comportamento, compatibilidade com ferramentas/modelos, confiabilidade de respostas, respeito a permissões e capacidade de obedecer regras em cenários esperados e adversos antes de publicar.
3. **Homologação de mudanças relevantes:** mudanças de orientação com impacto significativo na forma de atuar devem ser avaliadas e aprovadas por responsável habilitado. Não criar fluxo de dupla aprovação.
4. **Atualização controlada:** publicar apenas versões autorizadas, verificadas e rastreáveis, com escopo definido para agente, ambiente, modelo e ferramentas compatíveis.
5. **Ajustes automáticos seguros:** permitir modificações restritas de baixo risco que estejam previstas em política, tenham limites e testes definidos e não alterem capacidade executável, autorização, identidade, ferramentas ou princípios de segurança.
6. **Reversão segura:** restaurar versão anterior homologada quando ocorrer regressão ou comportamento anômalo, após verificar compatibilidade e autorizações; recuperação automática somente por procedimento homologado.
7. **Auditoria contextual:** cada execução deve permitir identificar a **versão exata das instruções**, modelo de IA utilizado, agente, ferramentas autorizadas e procedimento aplicado, sem salvar segredos ou dados pessoais desnecessários.
8. **Supervisão e aprendizagem:** o Supervisor Técnico poderá propor revisões, acompanhar métricas, identificar regressões e sugerir bloqueio de versões, mas não poderá conceder a si mesmo permissão para publicar mudanças críticas.

## Separação entre instruções, permissões e playbooks

- **Instrução comportamental:** orienta interpretação, análise, tom, sequência de diagnóstico e critérios de encaminhamento; não é autorização técnica para executar ações.
- **Política de segurança/autonomia:** define permissões verificadas **fora da saída do modelo**, níveis de risco, acesso a ferramentas, escopo por serviço e necessidade de aprovação.
- **Playbook homologado (IN-155):** procedimento executável com versão, pré-condições e permissões próprias; alterar um prompt **não homologa nem atualiza** um playbook.
- **Conhecimento estruturado (IN-139/IN-163/IN-164):** fatos, lições, casos e soluções de referência; não é automaticamente uma instrução de alto privilégio.
- **Modelo de IA (IN-157):** uma troca de modelo pode exigir teste de compatibilidade das instruções, mas não altera autorização ou habilita novas ferramentas.

O executor e o orquestrador deverão validar identidade, permissões, limites financeiros, homologação e aprovação crítica por mecanismos técnicos independentes da IA. **Nenhum texto de prompt substitui essas verificações.**

## Classes funcionais de mudança

| Categoria | Exemplos ilustrativos | Diretriz |
|---|---|---|
| **Baixo risco, dentro de escopo homologado** | Ajuste não funcional de clareza, formatação interna, organização de orientações ou parâmetro seguro já permitido | Pode ser automatizado quando existir procedimento homologado e testes suficientes |
| **Mudança relevante de comportamento** | Alterar critérios de diagnóstico, escalonamento, uso de ferramentas ou como interpretar um risco | Exige avaliação/homologação por responsável habilitado antes de publicação |
| **Mudança crítica ou sensível** | Ampliar autonomia, permissões, acesso a ferramentas, capacidade de alterar produção, regras de segurança ou decisões financeiras | **Não pode decorrer de ajuste automático de instrução**; exige uma autorização humana específica quando cabível e controles técnicos apropriados, sem dupla aprovação |
| **Reversão de versão** | Restaurar versão anterior comprovadamente estável após regressão | Automática somente quando homologada, compatível e dentro das permissões; caso contrário, escalar |

**Não inferir risco baixo somente por uma alteração textual pequena.** Uma frase curta pode mudar profundamente o comportamento do agente. A classificação deve considerar o efeito real, a criticidade da tarefa e o ambiente.

## Ciclo de vida das versões

1. **Versão atual homologada:** agente executa com conjunto exato de instruções autorizado.
2. **Proposta de alteração:** IA ou responsável sugere mudança, explicando objetivo, justificativa, diferenças e impacto esperado.
3. **Classificação e avaliação:** analisar risco, escopo, autorização, ferramentas envolvidas, compatibilidade com modelo e políticas externas.
4. **Testes em ambiente apropriado:** usar avaliações controladas, cenários representativos, casos adversos e verificações de segurança, sem executar efeitos reais não autorizados.
5. **Homologação:** se mudança relevante/crítica, responsável habilitado aprova **uma vez** a publicação da versão e seu escopo; se ajuste seguro pré-homologado e permitido, seguir fluxo automático condicionado.
6. **Publicação controlada:** registrar versão final, responsável/agente que executou publicação, ambiente e momento; evitar versões parciais/mistas, com mecanismo consistente de ativação.
7. **Monitoramento pós-publicação:** medir falhas, desvios, qualidade, custo, taxa de escalonamento, incidentes e resultados técnicos efetivos; não se limitar a respostas linguisticamente plausíveis.
8. **Suspensão e rollback:** suspender versão com desvio ou risco, restabelecer versão anterior comprovadamente segura se autorizado, validar recuperação e registrar incidente.
9. **Revisão e arquivamento:** preservar rastreabilidade e histórico das versões de instruções por política de retenção própria, ainda a especificar; não confundir automaticamente com os 24 meses após arquivamento de **conhecimentos técnicos** da IN-163.

## Controles obrigatórios de segurança

- **Imutabilidade de versão publicada:** alterações geram nova versão e registro de diferenças; não editar silenciosamente uma versão já identificada como homologada.
- **Integridade e origem:** registrar fonte e evidência de aprovação da versão; definição do mecanismo de assinatura/hash e proteção contra modificação não autorizada permanece técnica.
- **Matriz de risco externa ao prompt:** nenhuma instrução pode conceder privilégios, habilitar nova ferramenta, dispensar teste, retirar supervisão, alterar alçadas ou esconder eventos.
- **Controles contra injeção de prompt:** textos recebidos de sites, formulários, tickets, logs, documentos e saídas de ferramentas são dados não confiáveis, sem autorização para mudar instruções de sistema ou homologação.
- **Limites financeiros:** atualização de instruções não pode contratar modelos/provedores, ampliar cotas, exceder orçamento ou modificar controles da IN-142/IN-157/IN-158.
- **Confidencialidade:** não incluir chaves, tokens, senhas, dados pessoais desnecessários ou conteúdo completo de formulários em prompts, diffs, testes, evidências e logs.
- **Segregação:** versões e configurações não podem transpor empresas, ambientes ou perfis de acesso sem política explícita.
- **Compatibilidade de modelo e ferramentas:** revalidar quando houver alteração de modelo, formato de ferramentas, infraestrutura, políticas ou serviços associados; fallback não significa reutilização automaticamente segura de qualquer instrução.
- **Mudanças críticas:** seguir IN-137/IN-138 e IN-154; exige-se **uma única aprovação humana** quando aplicável, sem reautorizar todas as execuções seguras homologadas.
- **Estado seguro em falhas:** se a integridade da instrução, autorização ou versão ativa estiver incerta, impedir novas operações sensíveis e escalar; preservar monitoramento determinístico e alertas essenciais.

## Exemplo ilustrativo

O Agente de Integrações produz respostas excessivamente longas e repetitivas, mas seu procedimento técnico é adequado. Um ajuste de formatação previamente permitido é testado com casos padronizados e publicado automaticamente como nova versão de baixo risco. Se uma mudança proposta passar a sugerir alterações de credenciais, de parâmetros de produção ou de permissões de forma mais autônoma, isso é **mudança relevante/crítica**, não pode ser publicado automaticamente sob a justificativa de 'melhoria de prompt'. Um responsável habilitado deverá revisar e homologar a mudança no escopo adequado; a capacidade técnica de executar operações continuará subordinada aos controles externos.

## Auditoria e indicadores

- Registrar identificador do agente, versão ativa das instruções, versão/fornecedor do modelo, ferramentas, escopo e versão do playbook aplicado, além do ID de tarefa/incidente.
- Registrar propostas, diferenças relevantes, testes, classificações, aprovações, publicação, suspensão, retorno de versão e resultado.
- Medir versões publicadas e revertidas, regressões, comportamento fora do esperado, falsas aprovações, falhas de testes e efeitos nos indicadores de qualidade.
- Distinguir respostas de IA de evidências de execução e resultado técnico.
- Controles de retenção de auditoria e das próprias versões de instrução ainda requerem especificação; não guardar payloads sensíveis apenas para análise de comportamento.

## Pendências para especificação antes da implementação

1. Repositório/catálogo de versões, esquema de manifestos, distribuição, integridade e controle de alterações.
2. Limites exatos de ajustes automáticos de baixo risco e matriz para definir alterações relevantes ou críticas.
3. Conjunto de testes para diferentes modelos, ferramentas, ambientes, comportamentos adversos e instruções potencialmente maliciosas.
4. Processo e permissões de publicação, homologação humana única, rollback, suspensão e restauração.
5. Contrato técnico de orquestração para associar versões de instrução, modelo, playbook, ferramenta e tarefa.
6. Auditoria, proteção de dados, retenção de versões e revisões, e regras de confidencialidade.
7. Critérios de detecção de regressão e verificação de melhora de comportamento, com indicadores quantitativos e custos.
8. Gestão de mudança de ferramentas, modelos e políticas externas que invalide a homologação anterior.
9. Mecanismos de contingência e operação degradada quando não houver versão segura disponível.

**Aprovado:** controle de versões das instruções dos agentes, testes de qualidade/segurança antes de publicação, homologação de mudanças relevantes, atualização controlada, ajustes automáticos de baixo risco dentro das regras homologadas, reversão segura, auditoria por versão e impedimento de ampliação não autorizada de permissões, segurança ou custos.  
**Pendente:** detalhes técnicos, limites, seleção de ferramentas, critérios quantitativos e política específica de retenção das versões de instruções.  
**Programação:** não iniciada.


## Complemento posterior — IN-166 (02/11/2026)

A [IN-166 — Gestão Inteligente das Ferramentas e Permissões dos Agentes Técnicos](IN-166-gestao-inteligente-ferramentas-permissoes-agentes.md) aprovou catálogo técnico de ferramentas, permissões **por agente/operação/alvo/ambiente**, verificação de autorização **antes de cada ação por mecanismo independente da IA**, homologação/controle de versões de ferramentas, revogação de acessos e auditoria. **Alteração de prompt, troca de modelo, transferência de tarefa ou homologação de procedimento não concede novos privilégios.** Playbooks seguros previamente autorizados podem executar automaticamente dentro dos limites aplicáveis; intervenção crítica mantém **uma única aprovação humana específica**, quando cabível, sem dupla aprovação. Não iniciar programação.
