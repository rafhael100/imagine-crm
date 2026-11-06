# CRM-DEC — Ratificação expressa da alternativa B de precedência CORE02-D05

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação do responsável:** **“aprovado”** em resposta à pergunta explícita sobre a **alternativa B como diretriz de precedência para o CORE02-D05**.  
**Natureza:** ratificação **expressa de princípio normativo/documental**, sem homologação técnica, código ou grants reais.  
**Fontes:** [revisão consolidada CORE02-D04..D10](CRM-CORE-P0-ETAPA2-CORE02-revisao-consolidada-D04-D10-precedencia-proposta-v0.1.md), [CORE02-D05 original](CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md) e [decisão técnica delegada P1+P2 anterior, de 04/11](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md).

## 1. Política-base expressamente escolhida — alternativa B

O modelo escolhido corresponde à **união de concessões positivas válidas (P1), limitada por bloqueios mandatórios, com precedência de negação explícita específica e contextual (P2)**. Não selecionar a interseção universal (P3) ou papel principal único (P4) como regra geral.

1. **Poder cumulativo limitado:** permissões válidas de diferentes perfis da mesma identidade podem ser combinadas **somente no mesmo tenant e dentro dos respectivos subescopos/ações/campos autorizados**, observados plano/entitlement, membership/mandato, estado, finalidade e regras do módulo. Uma concessão num papel não torna válido acesso ou operação fora de seu próprio escopo.
2. **Bloqueios mandatórios sempre superiores:** tenant, RLS, segurança, plano/quota, titularidade/escopo do objeto, proteção de dados/campo, MFA/step-up, alçadas e revogação não são anuláveis por perfis, UI, IA, n8n, suporte ou administração central.
3. **Negação explícita prevalecente no escopo correspondente:** uma negativa formalmente ativa, legítima e atribuída por autoridade competente, com recurso, ação, campo e abrangência identificáveis, prevalece sobre um grant positivo conflitante **somente na interseção real dos escopos afetados**. A regra não transforma qualquer negativa em veto global da identidade, da empresa inteira ou de outros tenants.
4. **Ausência de grant:** papel sem concessão não é automaticamente um deny explícito. Mas ausência de autorização válida para a operação solicitada implica **negação por padrão**.
5. **Proveniência e vigência:** manter fonte/owner, quem concedeu/vetou, período, motivo, versão e trilha proporcional, com mensagem de recusa apropriada sem exposição de dados/segredos. A definição executável de quem pode editar vetos, heranças, delegação, TTL e interfaces permanece para aprovação específica.
6. **Competência e proteção contra autoelevação:** nenhum direito de executar autoriza, por si, delegar o mesmo direito; perfis não criam recurso de plano, acesso cruzado, gratuidade ou bypass de segurança. Respeitar portabilidade legítima e serviços técnicos obrigatórios conforme seus contratos específicos, sem bloqueá-los por analogia.
7. **Superfícies abrangidas:** tela, API, busca, relatórios, arquivos, import/export, jobs, agentes, automações e integrações deverão usar política coerente, após futura homologação. Revalidar operações pendentes conforme os contratos proprietários; não desfazer fatos oficiais legitimamente concluídos.

## 2. Exemplos que tornam a decisão verificável

- Vendedor que possui papel de Relatórios pode acumular `read_contact` e `export_contacts` **somente nos conjuntos de dados para os quais cada direito foi realmente concedido**, se plano/segurança permitirem.
- Se existir **deny explícito válido** sobre `export_contacts` para a equipe Nordeste, o usuário não poderá exportar registros daquela equipe, mesmo que outro papel lhe conceda exportação. A negativa não se estende automaticamente à equipe Sul se escopos são independentes e a operação Sul tem autorização própria.
- Se só há `read_contact`, não há `export_contacts`; a exportação é negada sem necessidade de deny configurado.
- Perfil do tenant A não concede ou nega atuação no tenant B. Admin Imagine sem mandato e IA sem autoridade não recebem permissões comerciais por esta composição.

## 3. Precedência documental e limites de efeito

Esta aprovação **ratifica expressamente** a combinação P1+P2 escopada que constava em decisão técnica **delegada** de 04/11/2026, agora com manifestação do responsável, sem alterar as reservas da decisão anterior.

**CORE02-D05 integral permanece ABERTA.** Pendem: catálogo real por módulo, modelo de representação e criação de grants/denies, precedência detalhada de exceções, escopos de equipe/unidade/canal, herança, revogação, TTL, política de delegação, prevenção de conflitos, logs/UX e homologação executável com IAM, CORE01, CORE03/04/05/07/08/09, Segurança, INT e FIN/CON.

CORE02-D04/D06..D10 e 67 IDs técnicos CORE mantêm seus bloqueios de fechamento. SEG-01/02, ARQ/AGT, INT P0 G01..G06, FIN/CON, LGPD e DEV-GATE global não são encerrados.

**Não há autorização para código adicional do Codex, alteração de `main`, implantação, testes novos, grants, schema/RLS físico, acesso a dados de clientes, contratação ou mudança de VPS.** Registro em PR documental DRAFT, sem merge automático.
