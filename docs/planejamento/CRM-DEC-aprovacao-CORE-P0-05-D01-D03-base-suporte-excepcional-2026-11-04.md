# CRM-DEC — Aprovação CORE05-D01, D02 e D03 como base documental

**Data da decisão:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE05-D01, D02 e D03 como base documental do CRM Imagine. Registre minha aprovação no GitHub, mantenha CORE05-D04 a D10 e os demais bloqueios técnicos abertos e prossiga para o CORE-P0-06, sem iniciar programação.”  
**Estado:** **CORE05-D01–D03 APROVADAS COMO BASE DOCUMENTAL. CORE-P0-05 PARCIAL/ABERTO.**  
**Fonte deliberada:** [CORE-P0-05 — proposta v0.1](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md).

## 1. Escopo aprovado — sem concessão executável de acessos

1. **CORE05-D01 — Separação entre Administração Geral/telemetria e dados dos clientes.** Administradores Imagine, suporte, operadores de infraestrutura, auditoria e agentes técnicos **não recebem acesso irrestrito automático** a leads, contatos, mensagens, anexos, históricos, oportunidades e demais dados dos clientes. Monitoramento e administração central não conferem poderes de consulta, alteração, exportação ou exclusão de conteúdo do tenant. Mantidos isolamento Laravel/PostgreSQL RLS e autorizações distintas por ação e campo.
2. **CORE05-D02 — Mandato lógico excepcional por caso.** Aceita-se como referência a autorização contextual com caso legítimo, finalidade, tenant, recursos/campos, ações, operador, autoridade competente, validade, revogação e rastreabilidade. **Chamado aberto não equivale a mandato concedido.** Este aceite não resolve quem autoriza, o título jurídico, aceite do cliente, duração, mecanismos técnicos ou alçadas; não cria papel de superusuário nem impersonação invisível, nem exige aprovação duplicada de ato já autorizado.
3. **CORE05-D03 — Ciclo de autorização, atuação e encerramento.** Adotada como diretriz a sequência conceitual solicitação → avaliação/decisão → uso no escopo → expiração/revogação/encerramento, com revalidação antes de novos efeitos e auditoria. A expiração impede novos atos e tarefas dependentes do mandato, sem desfazer fatos oficiais já concluídos; resultados incertos passam por reconciliação, conforme CORE-P0-03.

**Limites preservados:** MFA central obrigatório e sessão da Administração Geral com 30 minutos de inatividade (regras históricas); ações críticas confirmadas pelo mesmo administrador autorizado salvo obrigação específica diversa; portabilidade IN-118 autoatendida no CRM; IA/n8n/serviço não autoconcedem autorização de suporte.

## 2. CORE05-D04 a D10 — explicitamente NÃO aprovadas

| ID | Dependência mantida aberta |
|---|---|
| **CORE05-D04** | Catálogo de suporte com níveis de telemetria, recurso, ação, campo, leitura/escrita/exportação e classificação sensível |
| **CORE05-D05** | Titularidade, finalidade e fundamento legal/contratual, quando exigir autorização do cliente, representantes, alçadas e exceções legítimas |
| **CORE05-D06** | Contrato técnico executável de mandato, identidade, token/credencial, sessão, RLS sem bypass universal, propagação de revogação |
| **CORE05-D07** | Procedimento de emergência/break-glass e contenções previamente homologadas, autoridade, duração, notificação e revisão posterior |
| **CORE05-D08** | Transparência, comunicação, consulta de acesso, contestação e revogação de mandato pelo cliente dentro das competências |
| **CORE05-D09** | Auditoria por categoria, integridade, retenção, consultas/exportação, proveniência e auditoria da própria auditoria |
| **CORE05-D10** | Contratos por operação, critérios de aceite, responsáveis, testes futuros e homologação documental |

**Demais bloqueios técnicos abertos:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE-P0-06..10; INT P0-OWN/SEC/CON/ACC; IN-168 ARQ/SEG/AGT; G01–G06; LGPD e fechamento global. A aprovação não atribui novos papéis, direitos, prazos ou acessos reais.

## 3. Próxima etapa permitida

**Autorizada:** somente preparação/revisão documental do **CORE-P0-06 — auditoria, rastreabilidade, acesso e políticas de retenção por categoria**, preservando direitos existentes e distinguindo auditoria central, comercial por tenant, trilhas de integração, logs técnicos e históricos oficiais de negócio.

**Não autorizados:** programação, testes executáveis, configuração de sistemas, acesso efetivo a dados de clientes, alteração de infraestrutura, credenciais reais, contratação e despesas. Nenhum CORE-P0 está declarado encerrado ou pronto para desenvolvimento.
