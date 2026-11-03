# P0-CON — CT-01: Conectores, contas, conexões e capacidades (minuta especializada)

**Data:** 2026-11-03.
**Situação:** **ACEITA COMO BASE DOCUMENTAL** pelo responsável em 2026-11-03, conforme [registro de aceite contextual](P0-DEC-aprovacao-CT01-base.md). O aceite **não aprova** estados físicos, campos, níveis de risco, esquemas, ferramentas, quotas, alçadas, transições por provedor ou execução. A [matriz consolidada CT-01..11](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md) segue proposta para revisão.
**Natureza:** CT-01 é uma família de contrato de interoperabilidade previamente catalogada, **não** uma nova INT, automação, catálogo concorrente, cofre de segredos, IAM, motor de estados, API ou execução.
**Fontes funcionais já aprovadas:** [INT-01](INT-01.md), [INT-02](INT-02.md), [INT-04](INT-04.md), [INT-11](INT-11.md), [INT-R01](INT-R01-governanca-contratos-homologacao.md), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md); cruzamento inicial [P0-CON CT-01..11](P0-CON-revisao-cruzada-CT01-CT11.md).
**Contratos complementares:** [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) (base documental aceita), [CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) (base documental aceita), [CT-05..11](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) (bases documentais aceitas). **CT-01 ainda não recebeu aceite documental especializado.**
**Limite de evidência:** INT-01..12 são sínteses; propostas originais integrais permanecem pendentes em [P0-TR-AUD](P0-TR-AUD-auditoria-proveniencia-requisitos.md). A matriz TR/CT é preliminar. Nenhuma implementação, execução de testes, alteração de produção ou contratação.

## 1. Objetivo: separar objetos, donos e elegibilidade

CT-01 descreve as **relações tipadas e versionadas** necessárias para saber qual serviço externo pode ser utilizado por qual conexão e em quais condições. A CT-01 não executa nenhum comando por conta própria, não decide política de acesso, não prova um efeito de negócio e não implementa outro cadastro mestre.

| Objeto distinto | Autoridade indicada | Responsabilidade | O que não pode ser inferido |
|---|---|---|---|
| Parceiro / organização | INT-11 | Identidade do parceiro e relacionamento de homologação, sem concessão de acesso operacional | Ser parceiro não dá acesso técnico, publicação livre, titularidade sobre conta do cliente ou cobrança |
| Provedor / serviço externo | INT-11 e cadastro de referência INT-01 conforme objeto | Identidade/versionamento do serviço e condições de elegibilidade | Fornecedor disponível não equivale a conexão ativa ou credencial válida |
| Conector / artefato homologável | INT-11 | Artefato e versão, origem, análise de risco, homologação, distribuição, restrições e suspensão | Uma versão aprovada não homologa automaticamente outra nem concede autorização ao tenant |
| Capacidade/operação tipada | INT-04 com contrato e referência à versão homologada em INT-11 | Operação suportada, schema/versão e pré-condições técnicas verificadas | Capacidade disponível não é ato autorizado nem confirmação de negócio |
| Conta externa vinculável | INT-01 para cadastro/vínculo de referência; provedor detém conta externa | Identificação segura de conta/tenant/ambiente e autorização de vínculo | Conta registrada não dá direito de operar; evitar expor identificadores sensíveis |
| Conexão por tenant/conta | INT-01 | Referência oficial da instância, tenant, ambiente, conector/versão, estado cadastral e dependências | Conexão existente não equivale a token ou permissão efetivos |
| Autorização e credencial externa | INT-02, Identidade e Segurança; segredos em IN-151 | Escopo, vigência, revogação, delegação e referência segura | Nunca colocar token/segredo em CT-01, log, UI pública ou prompt |
| Aplicação e API pública | INT-05; Segurança/INT-02 quando pertinente | Identidade de app, elegibilidade do plano e uso de contratos de operação | Trial não libera API pública; integrações nativas elegíveis seguem política própria |
| Comando/resultado de negócio | CT-03/04 e módulo proprietário; INT-04 para transmissão | Intenção, tentativa, confirmação de transporte e eventual fato oficial | Ativação/suspensão da conexão não confirma, cancela ou compensa efeitos passados |

**Regra de precedência:** INT-11 homologa versão/capacidade, INT-01 mantém **a conexão do tenant**, INT-02 mantém autorização externa referenciada com segredo seguro na IN-151, INT-04 conhece operação técnica tipada, Identidade e Segurança valida identidade/permissões, Central de Aprovações decide quando exigida e **somente o módulo proprietário atesta o ato de negócio**. Nenhuma verificação de status sozinha equivale à autorização para executar.

## 2. Envelope conceitual CT-01 (não JSON nem campos definitivos)

| Grupo candidato | Informação necessária conforme operação | Fonte/competência |
|---|---|---|
| Catálogo | Parceiro/provedor/conector, capacidade homologada, identidade e versão, condições de distribuição | INT-11 e capacidade INT-04 |
| Instância de conexão | Tenant, empresa/unidade/marca, conta externa, recurso/destino, ambiente, versão do conector e vínculo existente | INT-01 |
| Políticas e direitos | Ator/service principal, finalidade, operação, módulo e recurso, escopo externo, aprovação aplicável | CT-02, INT-02, Identidade e Segurança e Aprovações |
| Estado e elegibilidade | Referências de estado/versionamento da conexão, homologação, autorização, plano e capability permitida | INT-01/02/04/11 e política proprietária |
| Continuidade | Comandos/instâncias pendentes afetados, versão congelada, data de troca e referências de migração/reconciliação | CT-03/04, INT-09/10, P0-VER |
| Resultado/evidência | Motivo/proveniência da decisão, correlação, histórico de alterações e Auditoria Geral | Módulo competente e Auditoria Geral via INT-12 |

**Não incluir:** chave/token em claro, segredos em browser/prompts/logs, SQL/script livre, URL arbitrária, privilégio de superadministrador inferido nem número de limite inventado. Identificadores e referências devem ser tipados e escopados; nomes físicos, obrigatoriedade e serialização aguardam aprovação.

## 3. Verificações de elegibilidade propostas — sem novo motor

O seguinte é uma **sequência conceitual de conferência nas responsabilidades existentes**, não um fluxo síncrono imposto a toda ação:

1. Identificar tenant, empresa/conta, conexão e objeto oficial sem confiar no valor declarado pelo próprio cliente.
2. Confirmar que o provedor/conector e a **versão da capacidade requerida** estão homologados e elegíveis para esse ambiente/política de distribuição.
3. Confirmar que o registro da **conexão** e do vínculo de conta corresponde ao destino/recurso esperado e não está restringido no escopo da ação.
4. Revalidar **autorização externa, credencial/escopos e identidade interna** por CT-02/INT-02/Segurança, sem assumir que o catálogo ou o token dão permissão para outros recursos.
5. Verificar **plano e competência** quando aplicável; API pública permanece bloqueada no trial, mantendo integrações nativas elegíveis.
6. Conferir **operação tipada, schema e versão de contrato** em INT-04/INT-03; não oferecer execução de endpoint livre.
7. Revalidar pré-condições, concorrência, dependências e eventual aprovação específica **antes do ato protegido**, seguindo CT-03/04; no retry de escrita inconclusiva, reconciliar primeiro.
8. Registrar decisão e sua proveniência na Auditoria Geral; se autorizado, a CT-03 solicita e a CT-04 descreve transporte/resultados, com confirmação pelo proprietário.

**Nota:** a sequência não cria requisito de nova aprovação humana para cada simples leitura; a alçada real segue política existente e continua pendente por operação.

## 4. Marcos e transições conceituais, separados por objeto

| Marco proposto | Quem decide/atesta | Pré-condições e evidência a conferir | Limite obrigatório |
|---|---|---|---|
| Conector submetido → em avaliação | INT-11 e Segurança | Origem, versionamento, integridade, riscos e procedimento de homologação | Não distribuir ou executar código livre |
| Em avaliação → homologado | INT-11 com aprovador competente quando exigido | Evidência de homologação isolada por **versão exata** | Não ativar todas as conexões |
| Homologado → publicado/permitido para distribuição | INT-11 + políticas de plano/empresa aplicáveis | Publicação controlada, condições e abrangência definidas | Não confundir publicação com autorização de tenant |
| Conta/conexão proposta → cadastrada | INT-01 | Tenant, conta, ambiente, destino, versão, finalidade e autor do vínculo | Ainda pode estar sem credencial/escopo elegível |
| Cadastrada → elegível para operação | INT-01/02/04/11 e Identidade e Segurança | Conector/versão homologados, autorização externa vigente, permissão interna, capacidade tipada, estado atual e plano quando aplicável | Elegibilidade é uma **verificação composta**, não estado universal novo |
| Elegível → operação despachada | INT-04 + proprietário e CT-02/03 | Ato tipado e contexto/approvação específicos, sem contornar concorrência ou versões | CT-04 preserva resultado técnico e fonte oficial do negócio |
| Conexão elegível → restrita/suspensa | INT-01 por decisão competente; INT-11/02 se causa está na versão ou credencial | Motivo, alcance, impacto, operações já enviadas/pendentes | Não cancelar automaticamente negócio, não apagar fila pendente |
| Conector publicado → restrito/suspenso/descontinuando | INT-11 + Segurança e Aprovações conforme política | Versão exata, impacto por tenant/conexão, preservação de instâncias antigas | Não migrar todas conexões para versão nova silenciosamente |
| Versão antiga → candidata de migração | INT-11/01/02/03/04 + proprietários | Compatibilidade, conector/contratos/operações/credenciais, plano explícito de coexistência ou transição | Nenhum efeito enfileirado pode ser redirecionado sem autorização e reconciliação |
| Suspensão/erro → reativação permitida | INT-01/02/11 e Segurança; INT-10 para diagnóstico | Causa corrigida com evidência; escopos/credenciais atuais e capacidade/versionamento verificados | Reativação técnica ≠ conclusão de comandos inconclusivos |
| Conexão → desativada/encerrada | INT-01; INT-02 para credenciais externas e INT-11 para versões quando aplicável | Dependências, filas, evidências, vínculo de conta, retenção e impactos tratados | Não excluir registros oficiais nem transferir cliente automaticamente |

**Estados distintos:** catálogo/versão do conector, cadastro/conexão, autorização/credencial, disponibilidade de capacidade, comando técnico, resultado e fato de negócio **não** compartilham uma máquina de estados universal. Os verbos da tabela são **rótulos de análise, não enums físicos aprovados**. Não converter “conexão elegível” em fato transacional de pagamento/lead/reserva.

## 5. Atualização, suspensão e operações em andamento

| Situação | Preservação obrigatória na revisão | Confirmação competente |
|---|---|---|
| Mudança de versão homologada do conector | Identificar instâncias, comandos, assinaturas e checkpoints vinculados à versão anterior; migração/convivência explícitas | INT-11 para homologação; INT-01 para conexão; proprietário para efeito |
| Revogação de autorização externa | Impedir novos atos protegidos no escopo revogado; não desfazer efeitos anteriores automaticamente | INT-02/Segurança; provedor confirma revogação externa quando aplicável |
| Suspensão de conexão | Rastrear contexto/alcance e tentativas já despachadas; bloquear novos usos conforme política | INT-01; INT-10 acompanha falhas |
| Indisponibilidade de provedor | Distinguir saúde desconhecida/degradada de resultados de negócio, sem afirmar rejeição de comandos pendentes | Observabilidade/INT-10; proprietários atestam fatos |
| Troca de provedor ou conta | Não reemitir escrita inconclusiva na alternativa; exigir compatibilidade, autorização e plano válido | INT-11/01/02/04/09 e proprietário |
| Alteração do CRM vinculado a um site | Mudança via chamado e ajuste técnico gratuito da Imagine; sem migração automática de dados ou redirecionamento silencioso das submissões anteriores | Fluxos de Sites/CRM aprovados; IN-122/123/124/149 |
| Desativação/encerramento | Respeitar guarda de evidências, desativação de novas operações e plano para instâncias/filas, sem exclusão de fatos proprietários | INT-01; outros objetos mantidos por seus donos |

**Limite de segurança:** uma autorização de uso concedida no passado não permite a operação atual com token revogado; por outro lado, o bloqueio de uma nova tentativa não permite classificar como rejeitada uma escrita externa já enviada e sem confirmação.

## 6. Aplicação nas operações de referência (OP24, não exaustivo)

| OP | Tipo de ação | Proprietário competente | Controles em CT-01 | Resultado que não pode ser presumido | HT |
|---|---|---|---|---|---|
| OP-01-CAD | Cadastrar/alterar conexão | INT-01 | Conta/tenant/destino, versão homologada e ator competente | Estado cadastral confirmado em INT-01; permissão e credencial são validações distintas | HT-01/13 |
| OP-01-CIC | Restringir/suspender/desativar conexão | INT-01 | Direito de mudança, análise de dependências e comandos em andamento | Estado e alcance da restrição; tentativas enviadas seguem sua própria confirmação | HT-08/18 |
| OP-02-AUT | Vincular/renovar autorização | INT-02 + Segurança | Escopos externos, identidade, credencial em cofre e vigência | Autorização válida não cria conexão por si nem habilita operação de negócio | HT-01/02 |
| OP-02-REV | Revogar autorização | INT-02 + Segurança | Credencial/escopo exatos e autorização de mudança | Não reverter atos passados nem presumir revogação remota em caso inconclusivo | HT-02/18 |
| OP-04-ENV | Despachar operação técnica tipada | INT-04 e proprietário | Capacidade homologada, conexão, permissão atual e CT-03 válido | CT-04 não equipara aceitação externa a conclusão do negócio | HT-01/08 |
| OP-04-RSL | Registrar evidência técnica | INT-04 e proprietário | Origem, comando, versão, tenant e estado prévio verificáveis | Resulta em evidência técnica; confirmação oficial depende do dono | HT-08/14 |
| OP-05-APP | Cadastrar aplicação integrada | INT-05 | Plano elegível, app, tenant, escopo e política de acesso | App publicada não implica conexão externa e direitos a registros | HT-04/13 |
| OP-05-API | Invocar API pública | INT-05 + proprietário | API habilitada, app e ato permitido, trial bloqueado conforme política | Negação de API pública não impede serviço nativo elegível | HT-04/13 |
| OP-09-INI | Iniciar instância multissistema | INT-09 + proprietários | Modelo/contratos fixados, conector/versionamento elegível a cada etapa | Mudança posterior não migra instância já iniciada sem aprovação | HT-09/12 |
| OP-11-HOM | Homologar/publicar conector | INT-11 + Segurança/Aprovações | Versão exata, procedência, testes isolados e aprovação humana aplicável | Homologação publicada não cria conta/conexão/segredo | HT-01/12 |
| OP-11-SUS | Restringir/revogar conector | INT-11 + INT-01/02 para efeitos derivados | Versão, motivos, abrangência e conexões/instâncias ativas | Bloqueia novas ações incompatíveis, preservando resultados anteriores | HT-12/18 |

## 7. Rastreabilidade dos 11 vínculos iniciais TR→CT-01

Os vínculos abaixo já constam de [P0-TR53](P0-TR53-vinculo-contratos-aceite.md), mas **não são cláusulas originais verificadas** e não certificam que 11 requisitos esgotem a CT-01:

| TR inicial | Tema sintetizado | INV inicial | HT inicial |
|---|---|---|---|
| TR-INT01-001 | Cadastro por tenant de provedor/conta/conexão | INV-01/02 | HT-01 |
| TR-INT01-002 | Ciclo de vida e versão | INV-01/10 | HT-18 |
| TR-INT01-003 | Conexão separada de credenciais | INV-02/03 | HT-02 |
| TR-INT01-004 | Dependências/efeitos pendentes em mudança | INV-05/07 | HT-18 |
| TR-INT03-004 | Esquema e compatibilidade | INV-10 | HT-12 |
| TR-INT04-001 | Operação tipada/capacidade | INV-02/11 | HT-01 |
| TR-INT09-001 | Instância/modelo versionados | INV-10 | HT-09 |
| TR-INT11-001 | Homologação de conector/versão | INV-10 | HT-01 |
| TR-INT11-002 | Submissão/testes/aprovação/publicação | INV-03/10 | HT-18 |
| TR-INT11-003 | Sem execução/publicação livre de terceiros | INV-11 | HT-18 |
| TR-INT11-004 | Suspensão/descontinuação com impactos | INV-02/10 | HT-18 |

**Proveniência:** sínteses INT-01/03/04/09/11 e respectivas decisões transversais; [P0-REV INT-01..04](P0-REV-INT01-04-conferencia-cruzada.md) para regras correlatas IN-124/149/150/151/167/168; origem literal integral ainda indisponível. A CT-01 não altera os TR existentes.

## 8. Roteiros negativos propostos — subdivisões dos HT existentes, NÃO executados

| Caso candidato | Condição futura a simular | Resultado esperado | Referência HT |
|---|---|---|---|
| CT01-V01 | Tentativa de conexão em tenant B com conector e conta de A | Negar uso cross-tenant e preservar sigilo, sem efeito externo | HT-01/13 |
| CT01-V02 | Nova versão publicada, mas não homologada para conexão ativa | Não transferir elegibilidade nem instância por simples publicação | HT-12/18 |
| CT01-V03 | Credencial revogada enquanto comando aguardava fila | Bloquear novo despacho, preservar tentativa previamente enviada e reconciliar | HT-02/08 |
| CT01-V04 | Alterar vínculo Site→CRM durante envio antigo | Preservar destino autorizado do envio anterior, sem migração implícita | HT-08/13/18 |
| CT01-V05 | Conector suspenso após timeout de escrita | Não repetir comando no provedor alternativo sem reconciliação do primeiro | HT-08/18 |
| CT01-V06 | Provedor normaliza, mas resultados anteriores seguem inconclusivos | Não confirmar operações de negócio pela recuperação de saúde | HT-11/18 |
| CT01-V07 | Terceiro submete código ou URL livre como conector | Reprovar publicação/execução fora dos artefatos homologáveis | HT-01/12/18 |
| CT01-V08 | App pública do trial tenta reutilizar conexão técnica interna | Negar API pública no trial, preservar integrações nativas elegíveis | HT-04/13 |
| CT01-V09 | Suspensão de versão com instâncias INT-09 ativas | Preservar identidade/versionamento e autorização das etapas, sem mutação silenciosa | HT-09/12/18 |

**Evidências previstas:** versões e origens do conector, tenant/conta/conexão, identidade/escopo/permite-nega, autorização humana quando aplicável, comando/attempt e referência do fato oficial, impacto sobre instâncias ativas, histórico de alteração na Auditoria Geral. Nenhuma ferramenta ou ambiente de teste foi acionado.

## 9. Pendências para aceite documental e fechamento P0-CON-01

| ID | Decisão/especificação faltante | Pendência |
|---|---|---|
| CT01-P01 | Esquema e IDs físicos | Identidade e versão de parceiro/provedor/conector/conta/conexão/capacidade, origem e referências condicionais | P0-CON-01 |
| CT01-P02 | Estados distintos | Estados exatos de conector, conexão, autorização, capacidade, publicação e descontinuação; transições/erros | P0-CON-01 |
| CT01-P03 | Critérios de ativação | Plano, escopos, ambiente, conta, homologação, status de serviço, versão e precondição por operação | P0-SEC/OWN |
| CT01-P04 | Políticas de contenção | Suspensão urgente vs programada, alcance de novos despachos, tarefas existentes, filas e versões ativas | P0-DEP/VER |
| CT01-P05 | Migração e convivência | Compatibilidade de versões, instâncias ativas, alteração de destino e reconciliação antes de troca de provedor | P0-VER/CON |
| CT01-P06 | Provedores e condições | Critérios por provedor/conta, protocolo de autorização, tratamento de erro, homologação e atualização | P0-CON/SEC |
| CT01-P07 | Prazo, concorrência e orçamento | Timeout, TTL, quotas, custo, limites de uso, retenções especializadas, janela de migração | P1 + P0-CON se bloqueante |
| CT01-P08 | Fontes originais | Confrontar propostas/aceites integrais INT-01/02/04/11 e R01/R02 com TR/CT | P0-DOC/TR |
| CT01-P09 | Validação documentada | Responsáveis, evidências e revisão de HT-01/02/12/13/18, todos não executados | P0-ACC |

**Revisão por competências:** INT-01/02/04/11, Identidade e Segurança, Central de Aprovações, módulo proprietário afetado e Auditoria Geral deverão validar a fronteira aplicável; isso não nomeia pessoas nem cria novas alçadas.

## 10. Conclusão desta etapa

- A **11ª família CT** recebeu ficha especializada em **minuta**, permitindo confrontar as CT-01..11 sem que a CT-01 permaneça apenas um título no índice.
- **O escopo documental da CT-01 ainda não está aprovado especificamente**; nem os campos físicos, transições, políticas de provedor, custos, direitos ou compatibilidade de migração.
- **P0-CON-01, P0-OWN-01, P0-SEC-01, P0-VER-01, P0-DEP-01, P0-DOC-01/02, P0-TR-01, P0-ACC-01, P0-XREF-01 e P0-GIT-01 continuam abertos/parciais. G01–G06 não concluídos.**
- **Nenhuma implementação, teste, homologação técnica, criação de automação ou alteração de produção foi iniciada.**
