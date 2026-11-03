# P0-SEG — Matriz de autorização contextual e alçadas (MINUTA)

**Estado:** proposta documental para P0-OWN-01 e P0-SEC-01, sem ampliar privilégios já aprovados.
**Proprietários:** Identidade e Segurança, INT-02 (autorização externa), cofre central IN-151, Central de Aprovações, módulos de negócio e Auditoria Geral. Não criar outro IAM, cofre, aprovador ou serviço de política.

## 1. Verificações de operação protegida
| Dimensão | Fonte/controle competente | Tratamento se a condição não for satisfeita |
|---|---|---|
| Tenant/conta | Identidade + vínculo oficial da conexão e proprietário | Bloquear ato; preservar evento auditável |
| Identidade | Sessão ou principal técnico autorizado, MFA aplicável | Bloquear, não tratar como exceção comercial |
| Finalidade | Autorização/contrato e política do módulo de negócio | Negar/escalar segundo política |
| Recurso/campo | Permissão para o alvo real, classificação de confidencialidade | Não ampliar escopo pelo payload |
| Operação | Comando tipado, conector/versão/capacidade e autoridade proprietária | Não executar operação incompatível |
| Conexão/escopo externo | INT-01/02, cofre IN-151 e provedor | Não executar com segredo revogado |
| Estado | Pré-condições, versão, disponibilidade, regras de negócio | Reconciliar/rejeitar/aguardar conforme tipo |
| Aprovação | Referência vigente e específica da decisão pela Central | Solicitar aprovação aplicável quando exigida |
| Idempotência | Contexto lógico, concorrência e histórico de efeitos | Não duplicar escrita inconclusiva |
| Evidência | IDs de comando/ator/versão, resultado e confirmação proprietária | Não declarar operação concluída |

Algumas verificações são condicionais ao tipo de ato; a matriz final indicará as exceções legítimas de forma explícita, sem flexibilização geral.

## 2. Momentos de revalidação
- Ao iniciar operação protegida e antes da escrita no proprietário.
- Após suspensão, retomada, failover, alteração de versão/conector, mudança de contexto ou revogação.
- Antes de compensar ou reexecutar operação inconclusiva.
- Em tarefas assíncronas, utilizando identidade técnica válida, não autorização fictícia derivada do evento.
- Aprovação humana concedida não é repetida para a mesma decisão se política permite reuso, mas não dispensa verificar identidade, escopo e precondições **atuais**.

## 3. Execução técnica ≠ autonomia de IA
IN-167 permite execução por **procedimento determinístico, seguro, homologado e previamente autorizado** dentro do nível de risco aplicável; IA não autoconcede direitos nem altera a classificação para fugir de aprovação. Uma intervenção crítica exige aprovação humana específica quando aplicável, **sem dupla aprovação do mesmo ato**. Aprovação de negócio adicional só se decisão distinta a exigir.

## 4. Casos e exceções de domínio
- **Formulários de Sites Imagine:** visitante público não é cliente autenticado da API pública; backend valida identidade do site/formulário, destino autorizado, prevenção de abuso (IN-124/150). Nenhum segredo privilegiado em HTML/JS público.
- **Trial:** API pública desabilitada, mas integrações nativas permitidas preservadas.
- **Troca de vínculo do site para CRM:** por chamado e atuação técnica gratuita da Imagine, sem migração histórica automática; operações pendentes preservam destino original conforme IN-118/122/123/124/149.
- **Importação/exportação/backup/restauração:** autoatendimento essencial em todos os planos, inclusive Básico, conforme IN-118; confirmação reforçada e acesso delimitado a dados/arquivos.
- **Contingência externa:** alternativas homologadas, compatíveis e autorizadas; sem troca crítica unilateral de provedor.

## 5. Aplicação aos casos de referência

- [P0-OP — Casos operacionais INT-01..INT-12](P0-OP-casos-operacionais-contratos-alcadas.md) apresenta pré-condições, propriedade do ato e recusa esperada por operação representativa, sem definir alçada numérica ou perfil novo.
- [P0-TR53 — Vinculação de requisitos](P0-TR53-vinculo-contratos-aceite.md) mantém a associação **proposta** aos contratos e invariantes.

## 6. Detalhamento CT-02 por operação

- [Matriz P0-CT02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) distingue solicitante, autorizações centrais, executor técnico e confirmador proprietário para as 24 fichas OP aceitas como base.
- São **propostas de verificações contextuais por operação**, não criação de papéis, alçadas, MFA obrigatório geral ou políticas de acesso. P0-SEC-01 e P0-OWN-01 continuam pendentes de fechamento integral.

## 7. Complemento de política CT-02 — 03/11/2026

- [P0-DEC](P0-DEC-aprovacao-CT02-base.md) registra CT-02 aceita como **base documental**.
- [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md) relaciona revisão e revalidação às 24 fichas de operação, sem aprovar perfis/MFA/alçadas exatos.
- [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md) relaciona evolução de política/credencial/contrato/instância à autorização corrente.
- **P0-OWN-01 e P0-SEC-01 permanecem abertos**, pois o detalhamento final por direito, recurso, campo, perfil e decisão ainda depende de validação.

## 8. Matriz que ainda precisa ser preenchida
| ID | Detalhamento necessário | Situação |
|---|---|---|
| SEG-A | Catálogo de operações e módulo proprietário por efeito | Pendente |
| SEG-B | Identidades, perfis, RBAC e MFA por ação/ambiente | Pendente |
| SEG-C | Campos, finalidade, base legal e classificação | Pendente |
| SEG-D | Alçadas, aprovação/reuso permitido e vigência por operação | Pendente |
| SEG-E | Revogação, retomada, contingência e revalidação por categoria | Pendente |
| SEG-F | Regras de decisão automática limitada e intervenção crítica por módulo | Pendente |
| SEG-G | Vinculação a HT e evidências de bloqueio/aceite por tenant | Pendente |

**Conclusão:** P0-OWN-01/P0-SEC-01 parcialmente especificados e ainda abertos; nenhuma nova aprovação ou programação.
