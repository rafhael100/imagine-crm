# INT-11 — Gestão Inteligente de Parceiros, Provedores e Conectores Homologados

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Catálogo oficial, homologação, compatibilidade, versões, publicação, distribuição e descontinuação de conectores.
**Dependências e referências:** INT-01 a INT-04, Identidade e Segurança, Aprovações.
**Cenários propostos de homologação:** HT-01, HT-12, HT-18 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Separar parceiro (organização), provedor (serviço), conector (artefato/capacidade homologada) e conexão (instância cadastrada pela INT-01).
- Fluxo de submissão, análise de origem/risco, validação técnica e de segurança, testes isolados, aprovação humana, publicação e reavaliação.
- Catálogo e distribuição por plano/política, versões compatíveis, suporte, suspensão, descontinuação e revogação com impacto aos tenants.
- Governança de conectores externos, parceiros e condições comerciais sem presumir marketplace lançado.
- Atualizações incompatíveis com migração/compatibilidade, sem alterar silenciosamente instâncias/processos ativos; versões posteriores exigem homologação.

## Competências reservadas e exclusões
- Nenhuma publicação autônoma por terceiros ou execução de código livre no ambiente compartilhado.
- INT-01 é proprietária das conexões; INT-02 das credenciais; FIN e CON das cobranças/contratos.
- IA pode sugerir testes e revisões, mas não homologar/publicar/revogar autonomamente.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Complemento de rastreabilidade P0 (sem nova aprovação)
- O catálogo distingue **parceiro**, **provedor**, **conector** e **conexão**; INT-11 homologa/publica conectores por versão, INT-01 administra conexões e INT-02 administra autorizações e segredos.
- A proposta aprovada prevê estados de rascunho, avaliação, homologação, homologado, publicado, restrito, suspenso, descontinuando, descontinuado e revogado, sob políticas por versão. Nova versão não é automaticamente homologada.
- Submissão por terceiro não autoriza publicação autônoma nem execução livre de código na plataforma. Possível marketplace futuro não é lançamento aprovado.
- Condições comerciais e distribuição administráveis não concedem à INT-11 poder de cobrança (FIN), contrato (CON) ou mudança de provedores críticos.
- [Conferência P0 INT-09 a INT-12](P0-REV-INT09-12-conferencia-cruzada.md). Matriz de transições por versão/conexão e fontes originais ainda pendentes.

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
