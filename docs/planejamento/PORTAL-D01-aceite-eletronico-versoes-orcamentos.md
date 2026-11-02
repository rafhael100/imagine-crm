# PORTAL-D01 — Aceite Eletrônico de Versões de Sites e Orçamentos

**Status:** **APROVADA FUNCIONALMENTE em 02/11/2026** — responsável respondeu **“sim”** à proposta expressa de regras de aceite do Portal Imagine.  
**Fase:** somente planejamento e documentação; **não iniciar programação, assinatura de contratos, cobrança, implantação, contratação ou alterações em produção**.  
**Produto:** Portal Imagine / Imagine Sites (Onepage e Landing Page); não altera a política de contratação e faturamento do CRM Imagine SaaS.  
**Referências:** [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md); IN-095 (aceite expresso da versão), IN-099 (suporte), IN-116/117 (benefício e vínculo CRM), IN-123/125 (painéis e formulários), demais regras de contratos e adicionais já aprovadas em planejamento.

## 1. Regras funcionais aprovadas

1. **Aceite por pessoa autenticada e autorizada:** somente o cliente ou representante que tenha permissão válida naquele contrato/projeto poderá aprovar a versão apresentada de um site ou o orçamento de um serviço adicional. A conta de outro cliente não pode visualizar nem agir sobre o objeto.
2. **Identificação inequívoca do objeto:** apresentar no Portal **projeto/site**, **escopo**, **identificador e URL da versão submetida**, data e dados essenciais à decisão; quando for orçamento, apresentar **serviço, escopo, valor, condições e prazo informados**. A ação de aceite deve identificar exatamente o objeto e a versão.
3. **Manifestação positiva expressa:** aceite exige ação consciente e identificável do cliente; **silêncio, mero acesso, leitura, envio da URL e passagem do prazo não significam aprovação**. Não marcar aceite automaticamente por comportamento ou por resposta da IA.
4. **Comprovante auditável:** registrar identidade e autorização do aprovador, identificador da versão/orçamento/termo, conteúdo/condições relevantes aceitos, **data e hora com fuso**, evento e integridade das evidências. Controlar acesso e impedir alteração silenciosa do registro original.
5. **Versionamento e novo aceite:** mudanças materiais de versão, escopo ou condições do orçamento **não sobrescrevem o aceite histórico**; exigem nova apresentação e **novo aceite expresso** conforme a natureza da mudança.
6. **Recusa, ajuste e histórico:** cliente pode solicitar ajustes ou recusar com justificativa pelo Portal, vinculando solicitação à versão e mantendo histórico. Preservar as **duas rodadas de ajustes já incluídas** nos contratos dos produtos, nos limites comerciais anteriormente aprovados. Não converter pedido em cobrança automática.
7. **Orçamento validado pela Imagine:** somente orçamento previamente validado/autorizado internamente pode ser apresentado para aceite; aceite pelo cliente **não equivale a pagamento realizado** e **não constitui, por si só, autorização de débito automático**. Forma e marco financeiro de execução de adicionais serão especificados separadamente em PORTAL-D02.
8. **Questionamento e correção administrativa protegida:** cliente poderá abrir chamado para contestar/solicitar revisão. Eventual correção ou invalidade da evidência segue autorização, justificativa, versionamento e auditoria, **sem apagar o histórico nem reescrever a aprovação passada**.

## 2. Fluxo operacional aprovado — nível funcional

1. Imagine prepara uma versão publicável validada ou orçamento de adicional validado.
2. Portal informa ao cliente autorizado a existência do item a revisar, mostrando os termos, escopo e versão corretos.
3. Cliente examina o item; pode **solicitar ajuste, recusar ou aceitar expressamente**.
4. Portal valida autenticação, permissões e estado do item **no momento da decisão**; registra evidência imutável/auditável, com vínculo ao item exato.
5. Em caso de aprovação de versão, o processo de entrega passa ao estado aprovado correspondente, **sem criar automaticamente autorização para alterações de outros itens**.
6. Em caso de aceite de orçamento, registra-se o aceite comercial. **A execução ou cobrança é condicionada às regras financeiras expressamente aprovadas depois, em PORTAL-D02**, não presumidas nesta PORTAL-D01.
7. Versão/termo novo ou modificado materialmente solicita nova revisão e aceite; históricos permanecem consultáveis por autorizados.

## 3. Dados, LGPD e segurança

- Coletar apenas a evidência de identidade, autorização, documento/versão, conteúdo aceito e momento necessários para demonstrar a decisão.
- IP ou informações do dispositivo **não são exigência genérica desta decisão**; inclusão, base jurídica, proporcionalidade, retenção e acesso ainda deverão ser avaliados e especificados.
- A retenção e o armazenamento seguro dos registros de aceite devem respeitar a legislação e o prazo contratual aplicáveis **a definir na especificação jurídica/técnica**, sem presumir um prazo universal nesta aprovação.
- Projetos, documentos, comprovantes e histórico precisam de **isolamento entre empresas**, regras de permissão e acesso administrativo auditado.
- Alterações sensíveis de permissão, suplantação de identidade, aprovação indevida e correções administrativas exigem controles verificáveis independentes da IA.

## 4. Pontos a detalhar posteriormente — não aprovados por inferência

- Formato final das telas, modelos dos termos/contratos e campos precisos do comprovante de aceite.
- Se determinados instrumentos exigirão **mecanismo adicional de assinatura eletrônica** e com qual padrão, conforme análise jurídica.
- Regra de autenticação reforçada para atos de risco elevado; prazo de validade do orçamento e expiração de links de revisão.
- Prazo concreto de retenção, requisitos de preservação/auditoria, prova de integridade, impugnação e tratamento de dados pessoais.
- Marco financeiro de cobrança e autorização para iniciar adicionais; vencimentos, parcelamento/juros, inadimplência, estorno, desistência e reembolso — **PORTAL-D02**.
- Exatamente o que constitui rodada de ajuste, mudança material, alteração estrutural e critérios de uso justo — **PORTAL-D03**, preservando decisões comerciais existentes.
- Revisão jurídica dos termos; esta especificação funcional **não afirma que uma confirmação eletrônica isolada seja juridicamente suficiente para todo tipo de contrato**.

## 5. Decisão e integridade histórica

**Aprovado em 02/11/2026:** fluxo funcional PORTAL-D01 conforme as regras acima. A proposta anterior na PORTAL-01 foi expressamente aceita, após o responsável responder “sim” à pergunta “Você aprova a PORTAL-D01 com essas regras?”. Não reabrir essa regra sem pedido do responsável.

**Pendente:** PORTAL-D02, PORTAL-D03, detalhamento técnico e jurídico do comprovante, homologação, implementação e aprovação final de toda a documentação. **Nenhum software, cobrança ou contrato foi iniciado/alterado.**


## Complemento REL-01 — distinção entre aceite e entrega oficial (02/11/2026)

O responsável aprovou que a **jornada de relacionamento REL-01** (dias 0, 3, 7, 15, 30) só inicie com a **entrega oficial do site confirmada no Portal Imagine**, e **não apenas por publicação, submissão/aceite de versão, contratação ou pagamento**. Este complemento **não altera o fluxo de aceite eletrônico aprovado nesta PORTAL-D01**, que continuará exigindo ato expresso e registro auditável conforme suas regras. O evento de **confirmação de entrega** é um estado próprio, articulado com publicação e aceite; **essa confirmação manual da equipe Imagine foi SUBSTITUÍDA em decisão posterior pela entrega automática com checklist de IA, que somente pode ocorrer após verificar publicação, funcionamento e aceites necessários**. O ato de aceite do cliente continua distinto da confirmação administrativa de entrega. Precisará de rastreabilidade e validação de autorização, além de prevenir dois inícios de jornada por republicação/retentativa. [REL-01](PORTAL-REL-01-jornada-pos-entrega-onboarding-30-dias.md). Apenas planejamento, sem implementação.


## Complemento REL-01 — confirmação formal pela equipe Imagine (02/11/2026)

**HISTÓRICO SUBSTITUÍDO pela decisão posterior COM-13:** o agente de IA autorizado deverá executar checklist completo e, se **todos os controles aplicáveis e aceites obrigatórios estiverem válidos**, o Portal marca automaticamente o site como **ENTREGUE**, sem confirmação manual universal da equipe Imagine. A equipe continua responsável por exceções e ações críticas. A confirmação, com auditabilidade, comunica o estado de entrega ao cliente dentro do Portal e **ancora a REL-01**. **Não se confunde com o aceite eletrônico de versão exigido por esta PORTAL-D01**, tampouco permite ignorá-lo. **A definir:** perfil administrativo autorizado, checklist/evidência dos testes, operação de erro/correção, notificações externas e cronograma detalhado. [REL-01](PORTAL-REL-01-jornada-pos-entrega-onboarding-30-dias.md). Não iniciar programação.


## Complemento COM-13 — entrega automática mediante checklist sem dispensar aceite eletrônico (02/11/2026)

O responsável determinou que **a entrega oficial do projeto será automática quando o agente de IA concluir todos os itens obrigatórios do checklist com evidências verificáveis**, substituindo o clique administrativo final da equipe Imagine. **Este novo evento de entrega NÃO substitui o aceite expresso da versão pelo cliente aprovado nesta PORTAL-D01**; o checklist deve confirmar a existência e validade do aceite para a versão entregue. Publicação isolada, silêncio ou URL visualizada não equivalem a aceite nem a entrega.

**Falha de teste, ausência de evidências ou aprovação pendente bloqueia o estado ENTREGUE**. Alteração material de versão exige novo aceite quando aplicável. Intervenção crítica exigirá autorização humana própria; a equipe monitora pendências e exceções, sem confirmar manualmente cada site. O evento ENTREGUE inicia a REL-01, mas não dá autorização automática de disparos externos. A regra manual anterior deste documento é histórica e foi superada. [COM-13](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md). Somente planejamento.
