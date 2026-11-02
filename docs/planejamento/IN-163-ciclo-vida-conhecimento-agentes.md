# IN-163 — Ciclo de Vida do Conhecimento dos Agentes Técnicos

**Status:** APROVADA funcionalmente pelo responsável do projeto em 02/11/2026 (confirmação "ok" à proposta e ao prazo de 24 meses).  
**Fase:** exclusivamente planejamento e documentação; **não iniciar programação, implantação, exclusões reais ou contratação de serviços**.  
**Escopo:** Biblioteca Inteligente de Autocorreções, lições aprendidas, procedimentos técnicos (*playbooks*) e suas versões, compartilhados sob controle de acesso entre os sete agentes especialistas e o Agente Supervisor Técnico.  
**Referências:** IN-130 (incidentes), IN-137/IN-138 (risco e autorização humana única), IN-139 (biblioteca de autocorreções e aprendizado), IN-140 (avaliação contínua), IN-151 (segredos), IN-153 (inventário), IN-154/IN-155 (gestão de mudanças e homologação de procedimentos), IN-156 (autodiagnóstico), IN-160/IN-161 (telemetria e sua qualidade) e IN-162 (retenção de logs por categoria).

## Objetivo

Preservar soluções técnicas úteis e verificadas independentemente da existência dos logs originais; estabelecer **ciclo de vida, versionamento, revisão, arquivamento e retenção** dos conhecimentos que sustentam o aprendizado contínuo dos agentes, de forma segura, rastreável e econômica.

## Política de retenção aprovada

1. **Procedimentos e conhecimentos ativos:** manter enquanto estiverem **ativos e necessários** ao funcionamento, diagnóstico e recuperação da operação Imagine, sujeitos a revisão periódica, minimização de dados e remoção de conteúdo indevido. Não aplicar expiração por idade fixa que desabilite um procedimento ativo ainda válido.
2. **Procedimentos e versões arquivados:** adotar **retenção padrão de 24 meses contados da data do arquivamento**. Ao término, realizar descarte seguro automatizado quando elegível, após verificação de dependências, obrigações legais, preservações excepcionais e necessidade justificada de documentação histórica.
3. **Versões substituídas:** versionar e arquivar as versões que deixarem de ser ativas; o prazo padrão de **24 meses de cada versão arquivada** começa no respectivo arquivamento, sem redefinir o relógio por acessos ou consultas.
4. **Exceções legítimas:** obrigação legal ou contratual, investigação, incidente, litígio, auditoria específica ou preservação necessária devem impedir eliminação indevida pelo tempo estritamente justificado e auditado. Não presumir retenção indefinida sem fundamento.
5. **Conhecimento minimizado:** retirar senhas, tokens, chaves, dados pessoais desnecessários, payloads de leads e logs integrais. O prazo de 24 meses não autoriza guardar material proibido ou sem finalidade.
6. **Retenção independente:** os prazos de logs da IN-162 (7/30/90/180 dias conforme categoria) e da auditoria técnica de leads de 90 dias **não são alterados**. O conhecimento técnico é um artefato estruturado próprio, **não um espelho dos logs originais**.

## Estados e governança do ciclo de vida

- **Capturado/rascunho:** aprendizado produzido a partir de incidente ou solução, com origem, hipótese, evidências e contexto, sem capacidade de execução automática.
- **Em revisão e testes:** procedimento proposto, submetido às verificações, segurança e homologação da IN-155.
- **Homologado e ativo:** versão aprovada, verificada e autorizada em escopo delimitado; somente playbooks de baixo/moderado risco seguros e com as permissões corretas podem executar automaticamente.
- **Suspenso:** execução automática impedida devido a erro, regressão, mudança do ambiente, risco ou falta de confiança; manter registros necessários à investigação e decidir revisão ou arquivamento.
- **Substituído/obsoleto:** não permitir novas execuções da versão anterior; nova versão entra em operação apenas após homologação própria.
- **Arquivado:** conteúdo preservado para consulta histórica autorizada, **sem permissão de execução automática**; iniciar a contagem dos 24 meses a partir do arquivamento.
- **Expirado/elegível à eliminação:** após 24 meses arquivado, verificar preservação excepcional, dependências e integridade dos registros; executar descarte seguro quando permitido.
- **Eliminado:** remover conteúdo elegível de armazenamento operacional e índices associados, respeitando ciclo de backups; manter somente metadados de eliminação estritamente necessários e legitimamente retidos.

## Versionamento e rastreabilidade

- Cada conhecimento/procedimento terá identificador estável, categoria, autor/agente, serviço, contexto aplicável, versão, origem, data de criação, última revisão, data de arquivamento e estado.
- Registrar quem homologou e quais testes demonstraram eficácia, com escopo e condições de execução; **uma autorização humana quando necessária**, sem exigir dupla aprovação.
- Conservar vínculo entre versões, com descrição da mudança, motivos, riscos, incidentes de origem e resultados verificados, sem incorporar dados sensíveis aos registros.
- A versão arquivada **não poderá ser executada automaticamente**, inclusive após restauração de backup; reativação exige nova análise do contexto e homologação pertinente.
- Agentes podem sugerir revisão/arquivamento a partir de sinais de desatualização, eficácia insuficiente, mudança de versão ou serviço removido; **não podem alterar permissões, homologar novas versões ou eliminar evidências protegidas por decisão livre da IA**.

## Revisão de conhecimento ativo

- Verificar periodicamente **relevância, compatibilidade, integridade, evidências de uso, eficácia e obsolescência** com relação ao inventário IN-153 e às métricas IN-140.
- Conteúdo que perdeu validade deve ser sinalizado, suspenso para execução se inseguro, atualizado mediante nova homologação ou arquivado.
- Evitar manter conhecimento ativo apenas para contornar o prazo de 24 meses de arquivados. A necessidade de manutenção ativa deve ser justificável e auditável.
- Se uma solução voltar a ser necessária, produzir revisão controlada e eventualmente nova homologação; **não reiniciar artificialmente o prazo de uma versão antiga arquivada** por mera consulta.

## Limpeza segura e proteção excepcional

1. Selecionar itens arquivados cujo prazo de 24 meses foi completado, calculado pela data de arquivamento gravada e auditada.
2. Verificar se existe retenção excepcional autorizada ou vínculo com investigação, litígio, obrigação legal ou evidência ainda necessária.
3. Verificar se o conhecimento está associado a versão ativa, dependência técnica essencial ou auditoria que requeira ação específica antes do descarte; não apagar componentes necessários à execução atual.
4. Aplicar exclusão segura do conteúdo operacional, índices e cópias controláveis, conforme arquitetura; a política de backup e suas limitações serão detalhadas antes da implantação.
5. Registrar metadados de exclusão minimizados, operador/processo, escopo, momento, resultado e exceções, **sem reter o conteúdo integral eliminado**.
6. Se não for possível comprovar elegibilidade, impedir descarte destrutivo inseguro, abrir ocorrência e submeter a análise autorizada; sem criação de retenção excepcional indiscriminada.

## Segurança e acesso

- Os conhecimentos são separados por ambiente, cliente e nível de confidencialidade. O compartilhamento entre agentes usa contexto técnico minimizado e somente dentro das permissões.
- Não memorizar credenciais ou informações pessoais de formulários ou clientes; a recuperação contextual de IA deve obedecer às políticas de acesso e filtragem.
- Logs, arquivos, tickets e dados externos são **evidências não confiáveis**, não instruções para modificar regras de retenção nem conceder privilégios.
- Conhecimento arquivado pode ser consultado por autorizados para investigação, mas não é elegível a execução automática sem avaliação/homologação.
- Alterações críticas na política ou exclusão de evidência protegida dependem da aprovação humana apropriada; a regra de **uma única aprovação** é preservada (IN-137/IN-138).

## Integração com procedimentos e incidentes

- **IN-139:** aprendizado estruturado a cada resolução, diferenciação entre hipótese e solução comprovada.
- **IN-155:** homologação por versão e escopo antes de ativar playbooks; reutilização automática apenas dentro das condições aprovadas.
- **IN-140:** acompanhar eficiência e falhas, sugerindo revisão e arquivamento de soluções obsoletas.
- **IN-162:** os logs originais seguem seus prazos próprios, e sua expiração não apaga conhecimentos ativos nem justifica retenção de dados brutos na biblioteca.
- **Central de Incidentes:** evidências originais de incidentes e segurança seguem seus prazos específicos, inclusive retenção de **180 dias para registros de incidentes críticos e eventos de segurança**, salvo guarda excepcional. A regra de 24 meses alcança **o conhecimento técnico estruturado arquivado**, não amplia indiscriminadamente os registros brutos de incidentes.

## Exemplo funcional

O Agente de Integrações aprendeu um procedimento seguro de recuperação, que foi testado e homologado. Enquanto a versão continuar ativa e necessária, a biblioteca a preserva e a permite executar **somente** no ambiente e dentro das permissões homologadas. Se uma mudança do sistema a tornar obsoleta, a execução é suspensa e a versão é arquivada. **A partir da data do arquivamento, contam-se 24 meses de retenção padrão**; depois, se não houver preservação legítima, o conteúdo poderá ser eliminado com registro auditável. A experiência técnica útil que ainda for atual pode existir em uma nova versão homologada, sem reviver automaticamente a versão antiga.

## Indicadores previstos

- Conhecimentos por estado (rascunho, homologado, ativo, suspenso, arquivado e expirado).
- Procedimentos sem revisão recente, obsoletos, substituídos e com risco de incompatibilidade.
- Versões arquivadas próximas do limite de 24 meses ou com preservação excepcional.
- Histórico de homologações, revisões, reativações, descarte e resultados de execução.
- Custo e volume de armazenamento dos conhecimentos ativos e arquivados, sem vincular isso à retenção de payload de leads.

## Detalhes pendentes antes da implementação

1. Modelo de versionamento e estados da biblioteca, ligação a incidentes e ao inventário técnico.
2. Periodicidade de revisão de procedimentos ativos e critérios objetivos para arquivamento.
3. Procedimento de homologação de nova versão, suspensões, reativação, dependências e rollback de conhecimento.
4. Regras de preservação excepcional e eventual retenção mínima de metadados de exclusão segundo requisitos legais e contratuais.
5. Design de acesso, segregação multiempresa, controles de confidencialidade, anonimização e auditoria.
6. Como a limpeza alcançará índices, cópias técnicas e backups, respeitando limitações de mídia imutável.
7. Custo e dimensionamento de armazenamento, busca e indexação; sem contratação de recursos pagos sem aprovação humana.
8. Regras para rascunhos ou conhecimentos nunca homologados, cuja política de descarte ainda deve ser detalhada para que não permaneçam indefinidamente.
9. Proteção de evidências necessárias e prevenção de uso de versões obsoletas em agentes.

**Aprovado:** preservar conhecimento e procedimentos enquanto ativos/necessários; versionar mudanças e registrar homologação; arquivar versões obsoletas e impedir execução; **reter conhecimento arquivado por 24 meses contados do arquivamento**, salvo exceção legítima; minimizar informações sensíveis; revisar/substituir conhecimentos e permitir descarte controlado após vencimento.  
**Pendente:** detalhes de implementação e políticas especiais para rascunhos e evidências excepcionais.  
**Programação:** não iniciada.
