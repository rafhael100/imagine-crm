# Central de automações — interface privada candidata

Base verificada: `review/crm-private-complete-journey-2026-11-15` / `f12d57aa0057387729d6dba89987a94975b67478`. DRAFT / NO-MERGE / NO-DEPLOY.

Reaproveita os dois follow-ups nativos, preferências JSON, isolamento por organização, sessão nominal/MFA, CSRF, trial e autorização owner/member da jornada privada. Nenhuma migration, credencial, volume, rota pública ou configuração VPS nova.

O responsável pode selecionar/pausar os dois modelos existentes e salvar uma revisão. Formulário envia versão, não tenant/ator. Servidor resolve ambos a partir da identidade, bloqueia perfil member e trial vencido, serializa com advisory lock da organização e row lock de preferências, nega gravação de aba desatualizada e registra evento de auditoria. Histórico armazena versões, regras, ator backend e instante no JSON; limite explícito de 20 revisões evita crescimento sem arquivo auditável. Não é ledger imutável contra administradores de banco.

JSON legado em lista é lido sem mudança e sua próxima edição inicia revisão 1. Configuração inválida falha fechada. Pausar bloqueia novas tarefas; não apaga tarefas já existentes. Mantém o comportamento anterior de follow-up no dia seguinte, agora com limite de 200 tarefas também no caminho automático; atingir limite reverte a transação do lead. Não escolhe novas regras comerciais por suposição.

O motor mais amplo/durable em CRM #632 é candidato separado para DEV. Esta PR **não** alega ter implantado seu worker/scheduler nem mapeado seus novos modelos à jornada. Não presume integração de branches DRAFT. UI atual configura os efeitos nativos já existentes da jornada, sem workflow n8n gerado. A visão global do Portal ainda depende de contrato de metadados/políticas por autoridade homologada.

Verificação: PHPUnit de leitura legada, versões, regras sensíveis e limite; teste HTTP existente ampliado para salvar, pausar, conflito 409, reativar e member 403; pipeline real de containers/PG/HTTP/Chromium da jornada reutilizado em CI descartável. Nenhum acesso à VPS ou deploy autorizado por esses testes. Evidências devem citar a SHA executada.
