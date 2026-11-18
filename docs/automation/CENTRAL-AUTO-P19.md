# CENTRAL-AUTO-P19 — receiver privado candidato

Base CRM #635 (inclui #634), Portal #907 sobre #904. Staging sintético apenas, DRAFT/NO-GO, sem provider administrativo implícito. V2 do envelope adiciona operação explícita ao formato MAC da trilha existente; o snapshot interno CRM_AUTOMATION_STATE_V1 é preservado.

O nginx mTLS dedicado fornece certificado verificado por parâmetros FastCGI reservados. HTTP headers não comprovam identidade. Laravel valida MAC, destinatário, operação e janela; PostgreSQL mantém concessão por empresa/fingerprint/operação sob FOR SHARE até commit, nonce único concorrente e auditoria. Revogação que completa antes da admissão nega; revogação concorrente espera uma leitura previamente admitida terminar. Não se promete retirar dados de uma resposta já enviada ou desfazer commit anterior.

Transporte SQL: runtime EXECUTE-only, fonte com owner NOLOGIN/NOBYPASSRLS e RLS FORCE por empresa. Nenhuma alteração comercial ou sessão humana. Instalação SQL é candidata apenas para banco sintético exclusivo: requer revisão incremental/grants do host antes de eventual rollout. A migration #635 não é substituída pelo schema completo CI.

CI inicia Laravel Portal real e Laravel CRM/FPM real em contêineres separados com nginx e PG privados, sem portas host. PostgreSQL usa CA/hostname verificados. Dados de negócio são fixtures em tabelas CRM; o teste observa sua alteração pelo receiver, não por servidor mock. O ensaio não repete a prova UI/motor #634 nem afirma que as linhas seedadas foram produzidas por um cliente real. Testa duas chamadas simultâneas, replay, revogação, certificado não concedido, empresa cruzada, indisponibilidade e restauração do CRM. Callback nominal Portal de CI é sintética, não concessão operacional ou prova de MFA.

Pendente: revisão externa, integração da autoridade nominal por empresa no Portal, CA/custódia reais, política de retenção da inbox/auditoria, backup externo, inventário Leaseweb, MFA e licença n8n. Sem binding automático da UI ou dispatch n8n. Adaptador de eventos Agents deve vir após a validação deste transporte. Não aplicar SQL de CI à VPS.
