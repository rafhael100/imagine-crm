# Migration incremental candidata — ledger de automações

Base CRM #634 ebabbe2abc69fd7ead763c11b2c8169c23bfb93d. Não usar schema.sql de CI para atualizar uma instalação existente. DRAFT / NO-MERGE / NO-DEPLOY.

O arquivo infra/homologation/migrations/001_automation_ledger_candidate.sql acrescenta exclusivamente a ledger ao banco privado fictício. Exige nome/owner/marca da instalação, aplica lock e timeouts transacionais, verifica tipos/colunas/quantidade de constraints e owner existentes, RLS FORCE, política única tenant-scope e grants restritos. Não cria usuário, identidade, cliente, cobrança ou integração operacional. Reaplicação não remove registros.

CI inicializa a versão anterior #633, preserva empresa/lead, aplica a migration, insere uma execução sob role não-superusuária/RLS, testa isolamento, reaplica e verifica preservação. Banco incorreto e drift de schema devem falhar e reverter a transação. A checagem estrutural não substitui comparação completa de constraints, políticas, ACLs e dados do host por DBA.

Não executado na Leaseweb. Antes de aplicar: inventário autorizado e versão exata do banco, backup externo e restore, diff de schema/grants, revisão independente e janela de atualização. Aplicar primeiro em clone segregado e desativar ingress/escritas durante rollout. Não executar automaticamente por CI verde.

Reversão preferida: interromper apenas a candidata, voltar à imagem previamente verificada e preservar a tabela/ledger para diagnóstico e retenção; não existe down destrutivo/DROP. Caso a reversão de dados seja necessária, usar restauração em banco/volume separados após procedimento aprovado. Nenhum serviço ou banco de outro projeto deve ser alterado.
