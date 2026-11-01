# 08 — CRM Básico cortesia: escopo e limites

> **IN-003 e IN-004 — DIRETRIZES APROVADAS em 2026-11-01:** CRM Básico cortesia será mais enxuto que o Essencial, exclusivo para contratantes elegíveis de Onepage/Landing Page Imagine, **mas incluirá Agenda e Agendamentos**.  
> **MATRIZ DE RECURSOS ABAIXO É PROPOSTA, AINDA NÃO APROVADA.** O CRM continua em planejamento, sem implementação.

## 1. Posicionamento comercial aprovado

- O CRM Básico cortesia é um **perfil de recursos restrito**, não o plano comercial Essencial com desconto integral.
- É concedido sem mensalidade de CRM, **como benefício contratual** do site elegível no Portal Imagine. O contrato Onepage mantém cobrança do site no Portal, e o CRM paga-se somente se o cliente efetuar upgrade voluntário para plano superior no próprio CRM.
- O benefício não está disponível como plano grátis autônomo ao público em geral; não confundir com trial de 14 dias do CRM, e não criar automaticamente fatura de R$ 0.
- Manter isolamento por organização, auditoria, LGPD e possibilidade de um usuário participar de múltiplas empresas. A concessão é por organização vinculada à assinatura do site, não por conta de login.
- Os três planos pagos **Essencial**, **Profissional** e **Empresarial** seguem como famílias aprovadas em conceito; a direção de preço do Essencial (R$ 69/mês) permanece **ilustrativa**, não final.

## 2. Matriz recomendada para aprovação — NÃO APROVADA

| Módulo / capacidade | Básico cortesia — recomendação | Motivo / futuro upgrade |
| --- | --- | --- |
| Usuários incluídos | **1 usuário administrador da empresa** | Equipes e assentos adicionais nos planos pagos |
| Empresas | **1 organização vinculada por contrato elegível**, com múltiplos sites da mesma empresa podendo apontar para ela | Usuário multiempresa continua possível por outros vínculos |
| Formulários de lead | **Formulário por site/landing page contratado**, gerado pelo publicador Imagine; sem construtor livre de formulários no Básico | Form builder e formulários avulsos nos planos pagos |
| Leads recebidos | **Sem teto comercial rígido de recebimento no formulário**, com política antifraude/uso justo; cotas de retenção/armazenamento a definir | Evitar que a cortesia cause perda de oportunidades ao atingir cota |
| Lead / contato | Lista, cadastro, busca e detalhes; identificação do site/formulário/UTM | Funcionalidades avançadas nos planos pagos |
| Funil | **1 funil** de oportunidades, com etapas padrão configuradas e movimentação manual | Múltiplos funis, automações e personalização avançada nos pagos |
| Atividades | Notas e lembretes manuais básicos nos registros, se aprovados | Tarefas avançadas, equipes e automações nos pagos |
| Dashboard | Total de leads, novos leads e situação simples do funil | Relatórios avançados, metas e forecast nos planos pagos |
| Exportação | **Exportação básica CSV dos dados autorizados**, sem relatórios avançados | LGPD/portabilidade devem ser preservadas; XLSX, PDF, filtros avançados nos pagos |
| Campos personalizados | **0 campos personalizados no benefício inicial**, salvo exigências técnicas do formulário padrão | Essencial atualmente prevê até 10 por módulo |
| Automações comerciais | **EM REVISÃO: usuário avalia liberar também automações.** Proposta inicial: biblioteca de 3 modelos e até 3 ativas, **não aprovada** | Essencial/Profissional/Empresarial têm 5/20/50 automações ativas |
| Agentes IA Prospector/Comercial | **Não incluídos** | Opcionais pagos; não cobrar uso de IA sem aceite |
| WhatsApp Business API, chatbot e caixa de entrada | **Não incluídos**; botão WhatsApp do site continua disponível | Integração oficial nos planos pagos ou adicional |
| API pública/integrações externas | **Não incluídas**; apenas integração interna Imagine → CRM autenticada | APIs externas conforme política dos planos pagos |
| **Agenda e Agendamentos** | **INCLUÍDOS por IN-004**: núcleo de agenda e agendamentos. Escopo de página pública, quotas e regras detalhadas ainda pendentes | Recursos avançados, integrações Google/Outlook/Meet/Zoom e multiequipe ainda a definir |
| **Unidades e recursos avançados de agenda** | **PENDENTES**, não automaticamente incluídos por IN-004 | Vários profissionais/unidades/salas, fila de espera, grupos e outras capacidades devem ser avaliados |
| Uploads/armazenamento | **Quota mínima específica a definir**; não herdar automaticamente a quota Essencial de 1 GB | Uploads avançados e capacidade nos pagos |
| Suporte | Ajuda e suporte contratual do produto sob regras a definir | Diferenciação de SLA sem privar de correções e suporte essencial |

**Nota importante:** as quantidades **1 usuário**, **1 funil**, e a ausência de limite rígido de **envio de leads** são recomendações concretas para debate, não aprovações do usuário. Quotas efetivas de armazenamento, contatos, relatórios e retenção continuam pendentes. A captação de leads deve ter fila durável; nunca descartar silenciosamente dados após dizer ao visitante que foram recebidos.

### Observação IN-004 sobre o módulo de agenda

**Aprovado:** permitir Agenda e Agendamentos no CRM Básico cortesia; **não está aprovada** a concessão de todas as funcionalidades avançadas do módulo geral. Limites de agendamentos ilimitados do Essencial/Profissional/Empresarial não se estendem automaticamente ao Básico. Revisão completa das automações e opções de liberação em [09 — Agenda e automações do Básico](09-revisao-agenda-e-automacoes-basico.md).

## 3. Aplicação dos direitos por produto

- O provisionador atribui `entitlement=site_bundle_basic` apenas após comprovar elegibilidade contratual e vínculo do tenant.
- O CRM avalia o recurso por direitos efetivos: **assinatura CRM paga ativa prevalece**; sem ela, usar Básico se houver vínculo site elegível; caso contrário, regras de trial/cancelamento e retenção aprovadas.
- Um cliente pode ter vários Onepages/Landing Pages ligados à mesma organização, sem ganhar cotas de usuário duplicadas por multiplicação de sites, salvo política futura expressa.
- Sem produto elegível, não oferecer o Básico como assinatura autônoma.
- Após upgrade, os mesmos leads, histórico, domínio e configuração dos formulários permanecem; nenhuma migração de tenant/duplicação de dados.
- Se o cliente sair do plano pago e continuar elegível ao Básico, retornar no momento contratual adequado, preservando registros e aplicando limites sem exclusão surpresa.

## 4. Fluxo padrão do cliente

1. Compra site Onepage/Landing Page pelo Portal Imagine.
2. Pagamento inicial confirmado desencadeia vinculação do CRM ao tenant autorizado.
3. Acesso ao CRM Básico fica disponível independentemente de a produção do site ter terminado, sujeito à política de elegibilidade e ao estado real da integração.
4. Site aprovado/publicado inclui formulário conectado ao tenant CRM, com origem por página e campanha.
5. Contatos novos entram no CRM e podem ser tratados manualmente com o funil básico.
6. O cliente pode clicar **“Conhecer planos do CRM”**, comparar recursos e contratar plano superior **no próprio CRM**; boleto/fatura do site continuam no Portal.
7. Cancelamento ou downgrade seguem a política própria de cada serviço sem apagar leads ou cancelar o outro contrato inadvertidamente.

## 5. Segurança e privacidade inegociáveis

- Formulário não expõe segredo/token de API de CRM no navegador.
- Não aceitar `tenant_id`/organização enviado por visitante; derivar vínculo de `form_public_id` e registros internos.
- Captura pública protegida contra spam, abuso, injeção, vazamento e IDs previsíveis. Consentimento e aviso LGPD conforme finalidade.
- Integração usa fila durável, reentrega idempotente, logs mínimos e trilha de auditoria.
- Retenção, portabilidade, direito de acesso e exportação autorizada não devem depender de comprar upgrade indevido.

## 6. Decisões necessárias antes da implementação

1. Aprovar o **restante da matriz concreta** ou editar limites/funcionalidades (especialmente 1 usuário, 1 funil, formulário por site e regras de leads). **Agenda e Agendamentos já estão incluídos por IN-004; detalhes e quotas pendentes.**
2. Definir quota armazenamento e limites de uso justo, sem bloquear captação legítima silenciosamente.
3. Estabelecer recursos mínimos do Essencial pago para justificar upgrade e preservar diferenciação.
4. Estabelecer quando benefício termina se site for inadimplente/cancelado, e como preservar leads em retenção e exportação.
5. Formalizar telas de ativação, upgrade e acesso por empresa, além de preços finais dos planos pagos.
6. Concluir testes de idempotência, autorização multi-tenant, cobranças independentes e fluxo de formulário integrado.

**Estado:** IN-001 (captação integrada), IN-002 (benefício gratuito + upgrade cobrado no CRM), IN-003 (versão enxuta exclusiva) e **IN-004 (Agenda e Agendamentos incluídos)** aprovadas como **diretrizes**. Matriz/limites deste documento estão **propostos**, não aprovados. Não iniciar programação.
