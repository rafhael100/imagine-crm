# 06 — Infraestrutura e operações

## Ambientes e implantação — APROVADO
- Produção e homologação separados, com credenciais independentes.
- Homologação com pagamentos sandbox e dados fictícios ou anonimizados.
- Entrega controlada: desenvolvimento → homologação/validação → aprovação → liberação à produção por responsável técnico autorizado.
- Registrar versão, responsável e momento da publicação; monitorar e permitir rollback.
- Manutenção programada: avisos no CRM e por e-mail, página informativa; evitar indisponibilidade quando possível.
- Manutenção emergencial: pode ser imediata, com registro do incidente e comunicação aos clientes afetados.

## Monitoramento
- Painel administrativo e alertas de CRM/API, PostgreSQL, filas, n8n, IA, pagamentos, NFS-e e recursos CPU/memória/armazenamento.
- Não apresentar dados confidenciais de empresas no painel operacional.

## Backup — APROVADO
- Backup completo diário.
- Incrementais ou arquivamento contínuo para reduzir perda entre backups completos.
- Cópias criptografadas em armazenamento externo separado do servidor principal.
- Retenção operacional padrão de **30 dias**.
- Testes periódicos de restauração e alertas de falha.
- RPO inicial planejado: **15 minutos**.
- RTO inicial planejado: **4 horas**.
- RPO/RTO são **metas**, dependem de infraestrutura/testes e não garantem ausência de perda ou recuperação automática.
- Política de descarte precisa conciliar backups, retenção comercial e obrigações LGPD.

## Pendente
- Provedores, custo/SLAs, arquitetura HA, periodicidade exata de testes, mecanismos concretos de PITR, alertas e procedimentos de runbook.

## Armazenamento privado de arquivos compatível com S3 — APROVADO
- Armazenar documentos, propostas, imagens e anexos em serviço de armazenamento de objetos privado compatível com S3, em vez de depender do disco local do servidor Laravel.
- Organizar objetos por empresa (`tenant_id`) e identificadores não previsíveis; não considerar prefixos de objetos como substitutos de autorização.
- Exigir autenticação e verificação de tenant e permissões na aplicação antes de liberar upload, visualização ou download; objetos privados por padrão.
- Disponibilizar downloads por URLs assinadas com validade curta; compartilhamentos externos devem ter escopo específico, validade configurada e revogação efetiva (por controle na aplicação, quando necessário).
- Aplicar quotas de armazenamento por plano e monitorar consumo por empresa; valores de limites dos planos permanecem pendentes, salvo limites de trial já definidos.
- Respeitar políticas de retenção, exclusão, auditoria e privacidade, incluindo remoção segura de objetos e referências conforme regras aprovadas.
- Prever proteção e recuperação dos arquivos com versionamento/backup e procedimentos de restauração testáveis; escolher fornecedor e parâmetros técnicos detalhados posteriormente.

## Validação automática de segurança dos arquivos enviados — APROVADO
- Aplicar verificação de segurança a documentos, imagens, anexos e arquivos recebidos por integrações, antes de disponibilizar download, visualização ou compartilhamento.
- Arquitetura: Laravel, armazenamento S3 privado com área de quarentena, ClamAV para análise antivírus e filas assíncronas para processamento.
- Validar tamanho máximo por tipo, extensões e tipos permitidos, assinatura/formato real (não confiar em nome ou MIME fornecido pelo cliente), integridade básica e arquivos compactados; rejeitar executáveis e conteúdo perigoso.
- Não permitir execução dos arquivos enviados no servidor; armazenar com nomes internos não previsíveis e permissões mínimas.
- Enquanto pendente, arquivo fica indisponível. Somente liberar após verificações bem-sucedidas; se antivírus/serviço de verificação falhar ou estiver indisponível, manter bloqueado (fail closed) e prever retentativas e acompanhamento operacional.
- Registrar empresa, usuário ou integração de origem, data/hora, resultado da análise, motivo de rejeição e eventos relevantes; respeitar privacidade e retenção.
- Informar ao usuário status de processamento/aprovação/rejeição; não prometer detecção absoluta de ameaças. Limites exatos de tamanho e lista final de formatos permanecem para definição posterior.

## Limite máximo de tamanho por arquivo — APROVADO
- Limite global máximo de **5 MB por arquivo** para todos os tipos de upload permitidos (imagens, PDFs, documentos de escritório, ZIP e outros formatos autorizados), em todos os planos.
- Aplicar o limite na interface, na API e na validação de backend, inclusive integrações, sem confiar apenas na validação do navegador; rejeitar arquivos acima de 5 MB antes de processar antivírus sempre que possível.
- O limite é por arquivo e não substitui a quota total de armazenamento da empresa; as validações de segurança e a quarentena anteriormente aprovadas continuam obrigatórias.
- Não adotar os limites anteriormente propostos de 10/25/50 MB por categoria.

## Otimização automática de imagens e documentos visuais — APROVADO COM AJUSTE
- Otimizar automaticamente todas as imagens recebidas, inclusive fotografias, imagens de produtos, documentos digitalizados, comprovantes, assinaturas e imagens com texto, priorizando redução de armazenamento sem prejudicar nitidez, legibilidade, fidelidade ou integridade do conteúdo.
- Limite de entrada permanece **5 MB por arquivo**. Para fotografias comuns, redimensionar proporcionalmente até cerca de 1920 pixels na maior dimensão, usando qualidade inicial de 80% a 85% e preferindo WebP quando gerar economia real; parâmetros podem ser ajustados conforme o resultado.
- Para comprovantes, documentos digitalizados, assinaturas e imagens com texto pequeno, usar compressão conservadora, técnicas sem perda quando necessárias e evitar redimensionamento que comprometa informações, traços, números ou transparência.
- Salvar somente a versão definitiva otimizada quando ela preservar a qualidade exigida; se não for possível reduzir sem risco de degradação relevante, manter o arquivo original. Evitar armazenar simultaneamente original e versão otimizada de fotografias comuns sem necessidade.
- Para PDFs assinados digitalmente ou arquivos cuja integridade binária seja necessária, não modificar conteúdo de modo a invalidar assinatura, prova ou integridade; não aplicar conversões automáticas destrutivas a documentos de escritório.
- Otimizar após validação de segurança e antes de disponibilização definitiva, com tratamento seguro em filas; contabilizar quota pelo tamanho efetivamente armazenado, incluindo quaisquer versões adicionais que venham a ser necessárias.
- Os valores de economia são variáveis e não garantidos; a preservação de qualidade prevalece sobre metas de compressão.
