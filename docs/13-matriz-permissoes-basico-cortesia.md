# 13 — CRM Básico Cortesia: matriz de permissões por módulo

> **Planejamento, sem implementação.** Diferenciar rigorosamente decisões **APROVADAS** de funcionalidades **PROPOSTAS** ou **PENDENTES**. Documento transversal ao CRM Básico Cortesia vinculado a sites Imagine, não uma concessão geral aos planos pagos.

## Fundamentos aprovados

- **IN-003:** o benefício Básico é enxuto, exclusivo de empresa vinculada a contrato elegível de site Onepage/Landing Page; não é Essencial gratuito.
- **IN-020:** no máximo **1 usuário autenticado por organização**, quota distinta de **1 profissional agendável** (IN-008).
- **IN-021:** o único usuário tem perfil **Administrador da Empresa**, **MFA obrigatório**, sem perfil alternativo operacional no Básico e sem poderes de superadministrador Imagine.
- **IN-010:** recurso ausente ou quota excedida exige explicação de plano e opção de upgrade/adicional **somente quando resolver**, com compra voluntária autorizada; falha de MFA, RBAC ou infraestrutura não deve ser tratada como upsell.
- Recursos aprovados do **CRM geral** não se tornam automaticamente incluídos no **Básico**; direitos devem ser verificados por organização, módulo e ação no backend.

## Módulo 1 — Administração da Empresa e Segurança — IN-022 APROVADA em 2026-11-01

| Ação ou capacidade | Decisão para o Administrador único do Básico | Limitações e cuidados |
| --- | --- | --- |
| Consultar dados cadastrais comuns da própria empresa | **PERMITIDO** | Somente organização autorizada, respeitando dados pessoais |
| Atualizar dados cadastrais comuns da própria empresa | **PERMITIDO** | Rastrear alterações relevantes e validar campos |
| Alterar **CNPJ ou identificador fiscal** | **PERMITIDO APENAS COM VALIDAÇÃO ESPECÍFICA** | Não permitir simples alteração irrestrita; processo exato de verificação, evidências e eventual análise manual ficam pendentes |
| Alterar senha de acesso | **PERMITIDO mediante verificação de identidade** | Reautenticação/verificação conforme risco, revogação de sessão quando cabível; nunca expor senhas |
| Alterar e-mail de acesso | **PERMITIDO mediante verificação de identidade** | Validar o novo e-mail, proteger contra tomada de conta e registrar alteração; detalhes específicos pendentes |
| Gerir configuração de MFA | **PERMITIDO mediante verificação de identidade** | **MFA permanece obrigatório**; não autoriza desligar MFA, enfraquecer autenticação ou contornar recuperação segura |
| Consultar sessões e encerrar sessões em outros dispositivos | **PERMITIDO** | Somente sessões da própria identidade e organização dentro das regras do CRM; não autoriza acesso a sessões alheias |
| Consultar plano Básico, recursos incluídos e limites realmente utilizados | **PERMITIDO** | Informação exata dos entitlements da empresa, sem inventar quotas ainda não aprovadas |
| Consultar opções de upgrade e contratar plano pago no CRM | **PERMITIDO com aceite expresso** | Mostrar valores/termos reais, confirmar transação pelo fluxo financeiro do CRM, sem cobrar automaticamente; assinatura do site no Portal Imagine é independente |
| Habilitar **2º usuário** no Básico | **NÃO INCLUÍDO** | Quota **1/1**; explicar limite e upgrade/adicional elegível se existir (IN-010). Não compartilhar credenciais |
| Acessar outras organizações sem associação válida | **PROIBIDO** | Isolamento multiempresa e autorização server-side |
| Administrar o SaaS, empresas alheias ou ferramentas de superadministrador Imagine | **PROIBIDO** | Perfil da empresa não equivale à administração geral da plataforma |

### Regras de autorização e proteção aprovadas

1. Validação de direitos **do plano e do papel** em toda operação administrativa no backend; não confiar somente no frontend.
2. **MFA obrigatório** para o usuário Administrador da Empresa; recuperação de credenciais e medidas de proteção da conta seguem as normas gerais aprovadas do CRM.
3. Alterações cadastrais relevantes, mudanças de identidade/segurança, sessões encerradas, alterações de vínculo fiscal e eventos financeiros devem seguir a auditoria e segurança correspondentes, preservando trilhas sem exposição de segredos.
4. Contratação de upgrade **dentro do CRM** apenas após informação clara e consentimento financeiro explícito; não modificar automaticamente a assinatura do site no Portal.
5. **Não interpretar IN-022 como liberação automática** de funcionalidades de configuração avançada, auditoria global, usuários extras, edição de permissões de terceiros, outras empresas, integrações premium ou acesso financeiro de outros tenants.

### Pendências específicas do módulo 1

- Definir o fluxo e a autoridade para validar solicitação de alteração de CNPJ/identificador fiscal, evitando sequestro de titularidade e impacto indevido em contratos/faturamento.
- Desenhar verificação de e-mail e identidade nas mudanças sensíveis, confirmação, recuperação de MFA, políticas de sessão, histórico de dispositivos e avisos de segurança.
- Detalhar substituição/transferência do administrador único, convites pendentes e recuperação quando a única conta ficar inacessível.
- Aprovar UX específica de contratação e das telas de plano/limites, seguindo regras financeiras já aprovadas.

## Módulo 2 — Leads e Contatos — PROPOSTA PARA REVISÃO (AINDA NÃO APROVADA como matriz Básico)

A integração de **formulários do Onepage/Landing Page com o CRM** já foi aprovada em **IN-001**. A entrada pública é de **leads**, e não cria automaticamente um agendamento (IN-007). Formulários aceitos **não podem ser descartados silenciosamente** por quota não aprovada ou erro transitório. A matriz seguinte especifica permissões e inclusão **a deliberar**, não concede funcionalidades novas ao Básico.

| Funcionalidade | Proposta de inclusão no Básico | Limite de escopo / decisão futura |
| --- | --- | --- |
| Receber leads dos formulários de sites Imagine vinculados à mesma organização | **Incluir** | Integração interna autenticada, origem identificada, validação antispam e fila durável; número de formulários por site ainda a definir |
| Consultar lista e detalhes de leads e contatos | **Incluir** | Somente da própria empresa, com nome, contatos, origem e situação permitidos |
| Cadastrar leads e contatos manualmente | **Incluir** | Sem conceder automaticamente importação em massa, API pública ou robôs |
| Editar cadastro e atualizar situação comercial manualmente | **Incluir** | Histórico de mudanças e integridade de origem; se mover no funil implica recursos de outro módulo ainda não aprovados, separar direitos |
| Pesquisar e filtrar leads/contatos | **Incluir busca básica** | Por nome, telefone, e-mail, origem e situação, sem relatórios avançados automaticamente incluídos |
| Registrar observações/anotações simples e histórico de atendimento manual | **Proposta de inclusão** | Campos, permissões, anexos e atividades avançadas serão definidos sem pressupor automações pagas |
| Identificar possíveis duplicidades | **Incluir alerta básico, se aprovado** | Não mesclar nem excluir dados automaticamente; mesclagem manual avançada e auditoria exigem decisão |
| Arquivar/inativar registros | **Incluir com preservação de histórico, se aprovado** | Definir distinção entre inativar, excluir, atender direitos LGPD e conservar auditoria |
| Excluir definitivamente ou mesclar leads/contatos | **A definir separadamente** | Não fazer exclusão/mesclagem irreversível por padrão; critérios LGPD e auditoria |
| Exportar dados e exercer portabilidade LGPD | **Permitir cumprimento da lei independentemente de upgrade** | Escopo e fluxo de CSV básico, exportações em massa/avançadas e evidências de autorização ainda a definir |
| Importação em massa (CSV/XLSX), API externa, lead scoring avançado, campos personalizados | **Não incluir automaticamente** | Verificar recursos de planos pagos e aprovar diferenciação, sem proibir direitos legais |
| Limite comercial mensal/total de leads e contatos | **Não aprovado** | Evitar perda de leads capturados/confirmados; definir quotas de armazenamento, retenção e uso justo antes de qualquer limite novo |

**Próxima decisão:** validar quais dessas operações de Leads e Contatos o Administrador da Empresa do Básico poderá executar. Depois tratar separadamente limites, duplicidades, arquivamento/mesclagem, importação/exportação e retenção. **Não programar antes do fechamento do planejamento.**

## Módulos seguintes ainda pendentes

3. Oportunidades e funis.
4. Agenda e serviços — direitos operacionais dentro das quotas já aprovadas.
5. Modelos de automações comerciais — biblioteca e vagas ativas correntes.
6. Relatórios, importação/exportação e LGPD.
7. Plano, contratação e cobrança.

**Referências:** [CRM Básico e matriz geral](08-plano-basico-cortesia.md), [Agenda/usuário e IN-021](11-agenda-manual-basico-cortesia.md), [Regra global IN-010](12-limites-e-upgrade-contextual.md), [Registro de decisões](decisoes.md).
