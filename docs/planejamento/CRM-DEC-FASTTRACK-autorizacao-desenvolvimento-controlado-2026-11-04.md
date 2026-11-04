# CRM Imagine — DEC-FASTTRACK-01: aprovação ampla de continuidade e autorização de desenvolvimento controlado

**Data:** 04/11/2026.
**Manifestação do responsável:** “quero que aprove todas as pendencias em abertas pois precisamos inciiar o desenvolvimento do projeto ja estamso atrasdos”
**Escopo:** CRM Imagine (`rafhael100/imagine-crm`); a autorização abaixo **não** pode ser estendida implicitamente a Imagine Portal, Sites ou Performance AI.
**Branch de trabalho:** `dev/fast-track-crm-2026-11-04`, derivada de `main`. `main` permanece a referência de documentação/decisões.

## 1. Decisão executiva: acelerar e parar de bloquear o início por votações individuais

O responsável determina a **aprovação global da continuidade do projeto e do encaminhamento das pendências técnicas para execução controlada**, dispensando a exigência anterior de colher aprovação específica sobre **cada subdiretriz documental** para **começar a implementação do núcleo em branch isolada**. A ordem anterior de **não programar** é substituída **para a Fase F0 (preparação técnica e código local de desenvolvimento), somente nesta branch e sem produção**.

- O acervo documental existente permanece a referência funcional/comercial; as propostas técnicas ainda não fechadas podem orientar desenho e avaliação, **não** equivalem a contratos, grants ou comportamento validado.
- Os **67 identificadores CORE01-D04..D07 e CORE02..CORE10-D04..D10** são **autorizados para tratamento técnico pelo fluxo acelerado**: cada especificação concreta deve ser descrita em mudanças pequenas e revisáveis, com fonte/owner, riscos e critérios negativos, sem necessidade de outra rodada de aprovação conceitual para simplesmente avançar.
- As pendências IN-168 **ARQ-01/02/03, SEG-01/02, AGT-01**, INT P0 e G01..G06, CORE/FIN/CON/Portal/Sites, LGPD, preços/contratos finais e riscos transversais **não estão resolvidos nem homologados** por uma aprovação geral. São tratados como **obrigações de projeto que precisam ser satisfeitas antes da integração sensível, entrega ao cliente ou produção**, conforme seu risco e dependências.
- Nenhuma exceção dá autorização para adotar regra financeira nova, realizar gasto, implantar ambiente, conceder acesso de cliente, tratar dados reais, abrir integração externa ou operar dados de produção.
- O desenvolvimento não exige declarar **falsamente** que os 67 itens foram testados ou encerrados antes de existirem software e evidências; deve exigir resultados de teste e revisão **antes de liberar funcionalidades correspondentes**.

## 2. Regime de aprovação e classificação, a partir desta decisão

| Categoria | Situação após autorização |
|---|---|
| **Diretriz funcional/documental historicamente aprovada** | Preservada; constitui fonte de requisitos, sujeita a precedência de decisões posteriores |
| **Pendência técnica P0 sem solução física especificada** | **AUTORIZADA PARA IMPLEMENTAÇÃO/ESPECIFICAÇÃO CONTROLADA NA BRANCH F0**, mas **ABERTA QUANTO AO FECHAMENTO TÉCNICO** |
| **Alternativas não deliberadas (p.ex. P1–P4 de grants/denies; cofre/provedor; topologia; custos)** | Devem ser comparadas, ter escolha justificada/registrada antes de uso relevante, revisão por competência, e não são decisões já aprovadas por inferência |
| **Código experimental e scaffold isolado, sem dados reais nem serviços externos** | **GO F0**, com commits revisáveis e sem fusão à produção |
| **Código que concede acesso a dados tenant-bound, movimenta dinheiro, envia mensagens, roda IA/segredos ou processa leads reais** | **NO-GO de integração/uso real** até contratos específicos, testes de isolamento/segurança, evidência e aprovação de release |
| **Homologação, merge de funcionalidades sensíveis e implantação** | **NÃO AUTORIZADOS** automaticamente por esta decisão; gates próprios e responsáveis permanecem |

**Princípio de segurança:** nenhuma pressão de prazo justifica **fail-open**, permissão global por padrão, bypass RLS, acesso cruzado entre tenants, segredos no repositório ou cobrança sem consentimento. No início do trabalho, usar **dados fictícios** e acesso de engenharia autorizado à branch.

## 3. Fase F0 — desenvolvimento autorizado imediatamente na branch

**Entregas iniciais (escopo limitado):**
1. Preparar base de projeto e estrutura do backend **Laravel 13 / PHP >=8.3 / PostgreSQL**, usando versões suportadas e documentação oficial; versões de dependências concretas devem ficar rastreáveis e travadas em lockfile ao instalar, não inferidas.
2. Criar contratos de código mínimos para **TenantContext e decisão de autorização deny-by-default**, sem implementar autenticação, RLS de produção ou APIs que manipulem dados de cliente enquanto a matriz de permissões estiver indefinida.
3. Adicionar documentação de instalação, `.gitignore`, modelos `.env.example` sem valores reais e matriz de implementação em PRs/commits pequenos.
4. Planejar testes **antes de acessar dados reais**: isolamento entre tenants, negações por ação/campo, troca de contexto em conexões, MFA/sessões e autorização de serviços. **Nenhum teste será marcado como executado sem execução registrada**.
5. Congelar como **não autorizados nessa F0**: emissão de certificados reais, conexão com gateway de pagamento, CRM operacional público, integração Meta/WhatsApp, cofre de produção, n8n de cliente, migrations em ambiente real, implantação, despesas.

**Laravel 13**, lançado em março/2026, aceita PHP >=8.3; sua escolha para a base de desenvolvimento F0 é **direção técnica do fast-track**, sujeita a compatibilidade do ambiente. Referências: https://laravel.com/framework/docs/13.x/releases e https://laravel.com/framework/docs/deployment. Não se supõe que exista Composer ou PHP correto na infraestrutura do cliente.

## 4. Fases seguintes (não iniciadas nem liberadas automaticamente)

- **F1 — Núcleo:** esquema relacional, identidade, memberships, roles, sessões/MFA, RLS, autorização em APIs/filas, logs protegidos e testes unitários/integrados, **primeiro em ambiente segregado** e sob revisão de segurança.
- **F2 — Fluxos essenciais:** DAT leads/contatos, anexos e import/export, com contratos e quotas por plano; escopos de ATD/VEN/AGD/AUT conforme entitlements e donos.
- **F3 — Integrações/financeiro/IA:** Portal/Sites, mensageria, n8n interno, motor nativo de automações, cobrança, agentes e cofre; dependências e licenciamento resolvidos **antes do uso real**.
- **F4 — Homologação e produção:** revisão de segurança e LGPD, 18 cenários INT HT e demais roteiros aplicáveis executados com evidência, G01..G06, recuperação de falhas/backup, aprovação operacional e release/GO **específico**.

A designação F0–F4 é um **mecanismo de organização do trabalho**, não cria planos comerciais nem substitui os códigos normativos CORE, REV, INT, SEG, ARQ e DEV-GATE.

## 5. Operação, versionamento e rastreabilidade

- Toda decisão de implementação será ligada à fonte correspondente e a uma pendência técnica, com revisão das dependências antes de integrar funcionalidades que exerçam privilégio, dinheiro ou dados pessoais.
- O registro de F0 não encerra silenciosamente CORE01-D04/D05/D06/D07 ou CORE02-D04/D05/D06, nem altera o histórico de diretrizes já aprovadas.
- A branch **não** é mergeada automaticamente em `main`; sem autorização implícita para merge, produção, gastos ou serviços de terceiros.
- Evitar aprovar texto genérico como se fosse a escolha de todas as alternativas mutuamente excludentes; a finalidade do fast-track é **tirar o bloqueio para iniciar desenvolvimento seguro**, mantendo rastreabilidade e verificação por etapa.

**Resultado desta decisão:** **GO LIMITADO PARA F0 DE DESENVOLVIMENTO ISOLADO; NO-GO PARA PRODUÇÃO, CLIENTES, FINANCEIRO E INTEGRAÇÕES REAIS.** O encerramento técnico de P0 continua dependente de evidência e contratos concretos.
