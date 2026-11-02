# P0-OP — Catálogo de casos operacionais e contratos/autorização (MINUTA)

**Situação:** proposta de decomposição da P0-OWN-01/P0-CON-01/P0-SEC-01 em **12 casos operacionais de referência**. Não são novas automações, endpoints reais, APIs implementadas nem concessões de autorização.
**Origem:** INT-01 a INT-12, INT-R01/R02, [matriz 53 TR](P0-TR53-vinculo-contratos-aceite.md), [contratos CT](P0-CON-contratos-tecnicos-minuta.md), [matriz de segurança](P0-SEG-matriz-autorizacoes-proposta.md).
**Atenção:** 12 casos não cobrem todas as operações reais do módulo. A exaustividade será estabelecida após recuperar e cotejar as propostas completas; os IDs OP são **referências de trabalho**, não catálogo definitivo.
**Fase:** planejamento e documentação, sem programação, implantação, acesso à produção, contratação ou testes reais.

## 1. Índice
| ID ilustrativo | Ato/ação de referência | Responsabilidade | CT candidatas | TR inicialmente relacionadas | HT planejadas |
|---|---|---|---|---|---|
| OP-INT01-01 | Cadastrar/atualizar conexão externa | INT-01 | CT-01,CT-02,CT-11 | TR-INT01-001..004 | HT-01,HT-18 |
| OP-INT02-01 | Revogar credencial ou escopo externo | INT-02 / Identidade e Segurança | CT-02,CT-09,CT-11 | TR-INT02-001..004 | HT-02 |
| OP-INT03-01 | Validar mapeamento antes da escrita | INT-03 / módulo dono dos dados | CT-03,CT-07 | TR-INT03-001..004 | HT-03,HT-12 |
| OP-INT04-01 | Transmitir comando externo tipado | INT-04 / módulo proprietário | CT-01,CT-02,CT-03,CT-04 | TR-INT04-001..004 | HT-08,HT-12 |
| OP-INT05-01 | Invocar operação via API pública | INT-05 / módulo proprietário | CT-02,CT-03,CT-04 | TR-INT05-001..004 | HT-04,HT-13 |
| OP-INT06-01 | Receber webhook externo | INT-06 / Eventos / proprietário | CT-02,CT-05,CT-04 | TR-INT06-001..004 | HT-05,HT-16 |
| OP-INT07-01 | Retomar sincronização por checkpoint | INT-07 / proprietários dos dados | CT-02,CT-06,CT-04 | TR-INT07-001..004 | HT-06,HT-08 |
| OP-INT08-01 | Importar/restaurar dados por autoatendimento | INT-08 / Central de Backup IN-118 / proprietários | CT-02,CT-07,CT-04,CT-11 | TR-INT08-001..005 | HT-07,HT-13,HT-17 |
| OP-INT09-01 | Executar processo multissistema e compensação | INT-09 / módulos proprietários | CT-03,CT-04,CT-08,CT-10 | TR-INT09-001..006 | HT-09,HT-10,HT-15 |
| OP-INT10-01 | Diagnosticar e recuperar integração | INT-10 / Observabilidade IN-159/160 / proprietário | CT-02,CT-04,CT-09,CT-11 | TR-INT10-001..005 | HT-11,HT-16 |
| OP-INT11-01 | Homologar/publicar versão de conector | INT-11 / Segurança / Aprovações | CT-01,CT-02,CT-11 | TR-INT11-001..004 | HT-12,HT-18 |
| OP-INT12-01 | Investigar e exportar evidências de integração | INT-12 / Auditoria Geral | CT-02,CT-04,CT-09,CT-11 | TR-INT12-001..005 | HT-14,HT-17 |

## 2. Casos e delimitações obrigatórias
### OP-INT01-01 — Cadastrar/atualizar conexão externa
**Autoridade e fronteira:** INT-01.  
**Pré-condições a verificar:** Identidade com poder de administrar conexões; tenant e conta legítimos; conector/versão elegíveis.  
**Famílias documentais propostas:** CT-01,CT-02,CT-11.  
**Confirmação/evidência:** INT-01 registra estado da conexão; serviços centrais preservam auditoria.  
**Teste de recusa ou falha obrigatória:** Conexão não ativa sem autorização; alteração não redireciona execuções antigas.  
**Requisitos-base:** TR-INT01-001..004; **cenários HT:** HT-01,HT-18 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT02-01 — Revogar credencial ou escopo externo
**Autoridade e fronteira:** INT-02 / Identidade e Segurança.  
**Pré-condições a verificar:** Ator/serviço autorizado; motivo e escopo específico; alçada da operação; cofre central IN-151.  
**Famílias documentais propostas:** CT-02,CT-09,CT-11.  
**Confirmação/evidência:** INT-02 e Identidade/Segurança confirmam revogação; evidência no serviço oficial.  
**Teste de recusa ou falha obrigatória:** Nova ação protegida bloqueada; pendências exigem decisão própria.  
**Requisitos-base:** TR-INT02-001..004; **cenários HT:** HT-02 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT03-01 — Validar mapeamento antes da escrita
**Autoridade e fronteira:** INT-03 / módulo dono dos dados.  
**Pré-condições a verificar:** Esquemas/versões conhecidos; campos e finalidade elegíveis; prévia sem efeitos.  
**Famílias documentais propostas:** CT-03,CT-07.  
**Confirmação/evidência:** INT-03 comprova validade estrutural, não sucesso de negócio.  
**Teste de recusa ou falha obrigatória:** Sem mesclagem, exclusão ou aplicação sem autorização do proprietário.  
**Requisitos-base:** TR-INT03-001..004; **cenários HT:** HT-03,HT-12 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT04-01 — Transmitir comando externo tipado
**Autoridade e fronteira:** INT-04 / módulo proprietário.  
**Pré-condições a verificar:** Capacidade/contrato homologados; conexão/autorizações vigentes; pedido lógico e versão de estado.  
**Famílias documentais propostas:** CT-01,CT-02,CT-03,CT-04.  
**Confirmação/evidência:** INT-04 comprova transmissão/aceite técnico; proprietário confirma efeito.  
**Teste de recusa ou falha obrigatória:** Timeout de escrita não libera retry arriscado sem reconciliação.  
**Requisitos-base:** TR-INT04-001..004; **cenários HT:** HT-08,HT-12 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT05-01 — Invocar operação via API pública
**Autoridade e fronteira:** INT-05 / módulo proprietário.  
**Pré-condições a verificar:** Aplicação autorizada, plano elegível, tenant e escopos vigentes, operação tipada.  
**Famílias documentais propostas:** CT-02,CT-03,CT-04.  
**Confirmação/evidência:** INT-05 comprova acesso válido; proprietário confirma recurso/efeito.  
**Teste de recusa ou falha obrigatória:** Trial bloqueia API pública sem bloquear integrações nativas aprovadas.  
**Requisitos-base:** TR-INT05-001..004; **cenários HT:** HT-04,HT-13 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT06-01 — Receber webhook externo
**Autoridade e fronteira:** INT-06 / Eventos / proprietário.  
**Pré-condições a verificar:** Assinatura/origem autorizadas, integridade e correlação verificáveis, versão compatível.  
**Famílias documentais propostas:** CT-02,CT-05,CT-04.  
**Confirmação/evidência:** INT-06 comprova recepção técnica; Evento interno central e proprietário confirmam respectivos atos.  
**Teste de recusa ou falha obrigatória:** Duplicidade/replay/fora de ordem não deve provocar efeito duplicado.  
**Requisitos-base:** TR-INT06-001..004; **cenários HT:** HT-05,HT-16 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT07-01 — Retomar sincronização por checkpoint
**Autoridade e fronteira:** INT-07 / proprietários dos dados.  
**Pré-condições a verificar:** Origem/destino/campos autorizados; checkpoint íntegro; versão/estado atuais.  
**Famílias documentais propostas:** CT-02,CT-06,CT-04.  
**Confirmação/evidência:** INT-07 registra checkpoint; proprietário confirma alterações realmente aplicadas.  
**Teste de recusa ou falha obrigatória:** Conflito ou escrita inconclusiva não permite sobrescrita baseada só em horário.  
**Requisitos-base:** TR-INT07-001..004; **cenários HT:** HT-06,HT-08 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT08-01 — Importar/restaurar dados por autoatendimento
**Autoridade e fronteira:** INT-08 / Central de Backup IN-118 / proprietários.  
**Pré-condições a verificar:** Cliente autorizado; arquivo e manifesto compatíveis; prévia e confirmação reforçada quando exigida.  
**Famílias documentais propostas:** CT-02,CT-07,CT-04,CT-11.  
**Confirmação/evidência:** INT-08 documenta lotes; módulos proprietários confirmam persistência dos registros.  
**Teste de recusa ou falha obrigatória:** Inclui Básico; sem cópia de segredos de outro tenant nem exclusão destrutiva.  
**Requisitos-base:** TR-INT08-001..005; **cenários HT:** HT-07,HT-13,HT-17 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT09-01 — Executar processo multissistema e compensação
**Autoridade e fronteira:** INT-09 / módulos proprietários.  
**Pré-condições a verificar:** Modelo/instância versionados; dependências, autorização por etapa; aprovações e efeitos elegíveis.  
**Famílias documentais propostas:** CT-03,CT-04,CT-08,CT-10.  
**Confirmação/evidência:** INT-09 confirma estado da coordenação; cada proprietário certifica seu ato.  
**Teste de recusa ou falha obrigatória:** Compensação não estorna nem cancela automaticamente efeitos irreversíveis.  
**Requisitos-base:** TR-INT09-001..006; **cenários HT:** HT-09,HT-10,HT-15 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT10-01 — Diagnosticar e recuperar integração
**Autoridade e fronteira:** INT-10 / Observabilidade IN-159/160 / proprietário.  
**Pré-condições a verificar:** Telemetria suficiente, escopo de incidente, playbook homologado; risco e autorização válidos.  
**Famílias documentais propostas:** CT-02,CT-04,CT-09,CT-11.  
**Confirmação/evidência:** INT-10 verifica normalização técnica e documenta pendências; proprietários confirmam seus fatos.  
**Teste de recusa ou falha obrigatória:** IA não executa recuperação crítica; saúde não equivale a entrega/negócio concluído.  
**Requisitos-base:** TR-INT10-001..005; **cenários HT:** HT-11,HT-16 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT11-01 — Homologar/publicar versão de conector
**Autoridade e fronteira:** INT-11 / Segurança / Aprovações.  
**Pré-condições a verificar:** Origem/artefato/versionamento identificados; testes isolados e revisão humana obrigatória.  
**Famílias documentais propostas:** CT-01,CT-02,CT-11.  
**Confirmação/evidência:** INT-11 comprova homologação/publicação específica por versão.  
**Teste de recusa ou falha obrigatória:** Terceiro não publica autonomamente; nova versão não herda aprovação anterior.  
**Requisitos-base:** TR-INT11-001..004; **cenários HT:** HT-12,HT-18 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.

### OP-INT12-01 — Investigar e exportar evidências de integração
**Autoridade e fronteira:** INT-12 / Auditoria Geral.  
**Pré-condições a verificar:** Autorização por tenant, escopo/ finalidade/identidade; dados minimizados e regime de retenção aplicável.  
**Famílias documentais propostas:** CT-02,CT-04,CT-09,CT-11.  
**Confirmação/evidência:** INT-12 correlaciona referências; Auditoria Geral prova proveniência e auditoria da consulta.  
**Teste de recusa ou falha obrigatória:** IA não muda evidência nem reclassifica fato técnico como negócio confirmado.  
**Requisitos-base:** TR-INT12-001..005; **cenários HT:** HT-14,HT-17 (não executados).

**Ainda pendente por operação:** ator/perfil/escopo por campo, versão de contrato/endpoint, estados próprios e transições, códigos e mapping de erros, idempotência técnica, aprovação/alçada se exigida, prazo, limites, critérios completos de homologação e vínculo literal à decisão de origem.


## 3. Padrão comum de aceite proposto
Em cada caso, o roteiro documental de homologação deverá definir:
- **Origem da autoridade:** aprovação do requisito e contrato versionado; identidade, tenant, vínculo, finalidade e recurso.
- **Ato que está sendo pedido:** tipado e pertencente ao proprietário; escopo mínimo e pré-condição validada.
- **Transição técnica e resultado:** preparado/enviado/aceito/aplicado/confirmado/rejeitado/pendente/inconclusivo conforme dimensão pertinente, com versões.
- **Evidência oficial e conclusões admissíveis:** confirmação do proprietário, identificadores e carimbo de tempo, sem inventar sucesso quando faltante.
- **Falha autorizada / negativa:** credencial revogada, tenant alheio, duplicidade, timeout após transmissão, schema incompatível, versão suspensa.
- **Retomada e compensação:** retentativa somente segura/permitida, reconciliação ou intervenção humana quando pertinente.
- **Evidência e auditoria:** trilhas oficiais da Auditoria Geral, instrumentação central IN-159/160, sem criar segunda fonte de auditoria.
- **Critério de saída:** comparação entre resultado esperado e constatado, sem marcar “homologado” antes da execução futura.

## 4. Dependências exclusivas já aprovadas
- **API pública no trial:** permanece desabilitada; integrações nativas elegíveis continuam possíveis.
- **Central de Portabilidade (IN-118):** operações essenciais de autoatendimento disponíveis inclusive no Básico; a INT-08 não substitui toda a Central de Backup.
- **Sites e leads (IN-124/148/149):** confirmação técnica durável da API receptora é distinta de entrega efetiva ao CRM; retenções 3+15 dias/24h/90d são especializadas de filas.
- **Observabilidade/recuperação (IN-159/160/167):** INT-10 é especialização sem nova Central; playbooks técnicos autorizados não conferem autonomia decisória à IA.
- **Auditoria e retenção (INT-12/IN-162):** logs técnicos possuem política por categoria; evidências estruturadas de negócio seguem proprietários e Auditoria Geral.
- **Parceiros (INT-11):** homologação de versão não é autorização de conta, contratação ou acesso ao ambiente.

## 5. Refinamento de comando e resultado

- [Minuta P0-CON CT-03/CT-04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) define os marcos de transporte, processamento técnico, confirmação de negócio e reconciliação, com exemplos de aceite documental ainda não testados.
- O catálogo de 12 casos continua ilustrativo; o detalhamento não define automaticamente schema definitivo, prazo ou alçada.

## 6. Pendências abertas
| P0 | O que este documento melhora | Para fechar de fato |
|---|---|---|
| P0-TR-01 | Relaciona famílias CT e casos OP às 53 TR iniciais | Cobertura completa e fonte original por requisito |
| P0-OWN-01 | Registra 12 casos com proprietário indicado e fonte da confirmação | Todas as operações, recursos, campos, perfis e alçadas |
| P0-CON-01 | Organiza 11 famílias de contrato e casos de aplicação | Schemas, códigos, estados, transições, erros, versões e parâmetros definitivos |
| P0-SEC-01 | Identifica precondições e recusas por operação de referência | Matriz detalhada por identidade/tenant/finalidade/recurso/campo |
| P0-ACC-01 | Liga casos a HT-01..HT-18 existentes | Procedimentos completos de teste, sem execução nesta etapa |
| P0-DOC-01 | Indica origem resumida | Conferência literal das aprovações originais |

**Conclusão:** catálogo de revisão criado, nenhuma pendência P0 declarada encerrada. Decisões novas sobre parâmetros executáveis, versões de API, valores, prazos ou concessões exigem futura aprovação expressa, sem iniciar programação.
