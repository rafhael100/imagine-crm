# CRM-DEC — Aprovação CORE09-D01, D02 e D03 — Cofre, segredos e identidades de serviço

**Data:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE09-D01, D02 e D03 como base documental do CRM Imagine. Registre a aprovação no GitHub, mantenha CORE09-D04 a D10 e todos os demais bloqueios técnicos abertos e prossiga para o CORE-P0-10, sem iniciar programação.”  
**Situação:** **CORE09-D01–D03 APROVADAS COMO BASE DOCUMENTAL**; **CORE-P0-09 PARCIAL/ABERTO**, não homologado fisicamente nem pronto para implantação.  
**Documento deliberado:** [CORE-P0-09 v0.1](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md).

## 1. Aprovação expressa — princípios lógicos, sem criação de privilégios reais

1. **CORE09-D01 — Inventário e identidade por competência:** aprovada a referência lógica de catálogo de identidades técnicas e famílias de segredos por serviço, cliente/organização, ambiente, ferramenta, proprietário e finalidade. Identidades humanas, memberships, assinaturas, mandatos excepcionais, contas de provedores e identidades de serviço são conceitos distintos. Nenhum catálogo de metadados contém material secreto, e nenhum principal recebe autorização universal por ser agente, n8n, operador ou Supervisor.
2. **CORE09-D02 — Cofre protegido e autorização determinística:** aprovada a separação lógica entre cofre de segredos, autenticador/autorizador externo ao texto da IA, operação tipada da ferramenta autorizada e confirmação oficial pelo módulo proprietário. Princípio de menor privilégio, segregação de produção/homologação, tenant/ambiente, restrição de segredo em GitHub, navegador/JavaScript público, prompts, memória IA, tickets e logs. Posse de token de provedor não dispensa direito interno e não autoriza evento financeiro/CRM.
3. **CORE09-D03 — Ciclo de vida e resposta segura:** aprovado o ciclo lógico de solicitação, autorização, ativação, monitoramento, renovação/rotação, comprometimento, revogação, contingência, reconciliação e auditoria, com execução automatizada de playbook apenas quando homologado e autorizado; intervenção crítica exige aprovação humana específica quando prevista, **sem dupla aprovação universal**. Revogação bloqueia novos efeitos não autorizados, sem apagar resultados oficiais anteriores.

**Restrições consolidadas:** motor próprio CRM para automações configuradas pelos clientes; uso de n8n exclusivamente nas hipóteses internas/serviços compatíveis com licenciamento e política de credenciais, sujeito a validação formal antes da implantação. Agentes técnicos/supervisor em VPS separada de n8n; Portal e CRM separados, APIs de recepção independentes, sites em Cloudflare Workers Static Assets. Não presume fornecedor de cofre ou licença n8n homologada.

## 2. CORE09-D04 a D10 — explicitamente NÃO APROVADOS

| ID | Dependência mantida aberta |
|---|---|
| **CORE09-D04** | Produto/provedor de cofre, topologia, KMS/HSM, região, disponibilidade, custos, backup e recuperação; IN-168 SEG-02 |
| **CORE09-D05** | Identidade efetiva entre servidores, autenticação, credenciais por ambiente/tenant, bootstrap, renovação, cadeia de confiança |
| **CORE09-D06** | Matriz executável agente/serviço × ferramenta × ação × alvo × tenant × ambiente × risco, autorizador independente; SEG-01 |
| **CORE09-D07** | TTL/versões, rotação/revogação local-remota, propagação em jobs, idempotência, concorrência e reconciliação |
| **CORE09-D08** | Contingência, disaster recovery e restore de segredos, falhas do cofre, APIs principal/contingência, RPO/RTO |
| **CORE09-D09** | Governança/auditoria/LGPD, retenções, resposta a incidentes, custos e validação de licença/contratos de provedores |
| **CORE09-D10** | Contratos técnicos finais, critérios de homologação, owners, evidência e correlação com INT/G01–G06 e CORE-P0-10 |

**Demais bloqueios intactos:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE06-D04..D10; CORE07-D04..D10; CORE08-D04..D10; CORE-P0-10; INT P0-OWN/SEC/CON/ACC/DOC/TR e outros; IN-168 ARQ-01/02/03, SEG-01/02, AGT-01; G01–G06; FIN/CON, LGPD, licenciamento, homologação e fechamento global.

## 3. Continuidade autorizada: apenas proposta documental CORE-P0-10

Preparar e apresentar **CORE-P0-10 — pacote de homologação documental do núcleo**, reunindo dependências, owners, critérios de aceite, casos negativos e evidências futuras, **sem executar testes**. Nenhum CORE-P0 está encerrado por aprovações D01–D03; tampouco existe autorização de programação ou compra.

**Proibido:** programação/Codex, testes executáveis, geração/uso/rotação de credenciais reais, contratação de cofre/licença, provisionamento, mudanças em produção, operação em dados de cliente, criação de automações ou despesas.
