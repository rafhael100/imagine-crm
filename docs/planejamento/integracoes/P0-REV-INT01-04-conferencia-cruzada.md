# INT — P0-REV-INT01-04: conferência cruzada das quatro primeiras automações

**Data:** 02/11/2026. **Situação:** revisão documental cruzada iniciada, com achados registrados. **Não é conferência literal integral nem homologação.**
**Fase:** planejamento e versionamento apenas; sem código, implantação, produção, testes reais, contratação ou custos novos.

## 1. Fontes verificadas e limite de confiabilidade

Documentos revisados no repositório:
- [INT-01](INT-01.md), [INT-02](INT-02.md), [INT-03](INT-03.md), [INT-04](INT-04.md): sínteses funcionais versionadas.
- [INT-R01](INT-R01-governanca-contratos-homologacao.md), [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [matriz P0](INT-P0-fechamento-documental.md), [matriz de autoridades](INT-P0-autoridades-contratos.md) e [registro de precedência](DECISOES-origem-precedencia.md).
- [IN-124 — Integração de formulários e leads](../IN-124-integracao-inteligente-formularios-leads.md).
- [IN-148 — Reconciliação de leads](../IN-148-reconciliacao-inteligente-leads.md).
- [IN-149 — Protocolo de entrega e confirmação](../IN-149-protocolo-entrega-confirmacao-leads.md).
- [IN-150 — Segurança de formulários e APIs](../IN-150-seguranca-inteligente-formularios-apis.md).
- [IN-151 — Gestão de credenciais e chaves](../IN-151-gestao-inteligente-credenciais-chaves-integracao.md).
- [IN-167 — Arquitetura dos agentes técnicos](../IN-167-consolidacao-arquitetura-agentes-tecnicos.md) e [IN-168 — Pendências dessa arquitetura](../IN-168-matriz-pendencias-fechamento-arquitetura.md).

**Limitação essencial:** as transcrições integrais das aprovações originais de INT-01 a INT-04 não foram recuperadas entre as fontes consultadas. Os arquivos INT-01 a INT-04 já são sínteses e os seus títulos expandidos não estão certificados como reprodução literal do nome originalmente aprovado. A conferência aqui é **cruzada**, pelas regras documentadas nos módulos vizinhos e nos documentos transversais. Não considerar P0-DOC-01 concluída.

## 2. Matriz da conferência realizada

| ID | Conferência | Evidência obtida | Estado |
|---|---|---|---|
| P0-RV01 | INT-01: conexão ≠ homologação do conector | INT-R01/R02 e INT-11 separam conta/conexão da homologação e publicação de conector | Coerente transversalmente; literalidade da INT-01 não verificada |
| P0-RV02 | INT-01: mudança de vínculo de CRM | IN-124 e IN-149 proíbem redirecionamento silencioso de envios anteriores, sem migração automática dos dados já entregues | Regra especializada confirmada; detalhamento de INT-01 pendente |
| P0-RV03 | INT-02: credenciais e cofre de segredos | IN-151 define cofre central seguro, segregação de serviços e ambientes, rotação e revogação controladas | Política correlata confirmada; não criar cofre paralelo |
| P0-RV04 | INT-02: formulário público x integração interna | IN-150 proíbe credenciais privilegiadas no navegador e diferencia visitante público de serviço autenticado | Separação confirmada; contratos específicos pendentes |
| P0-RV05 | INT-02: aprovações críticas | IN-151/167 preservam execução segura homologada e aprovação humana específica quando exigida, sem duplicar a aprovação da mesma decisão | Coerente; matriz por tipo de ato pendente |
| P0-RV06 | INT-03: mapeamento não decide negócio | INT-R01/R02 reservam propriedade e confirmação ao módulo competente; IN-124 não autoriza mesclagem destrutiva automática | Fronteira coerente; regras granulares originais pendentes |
| P0-RV07 | INT-03: payload de formulários e campos | IN-124/148/149 exigem identidade estável, contexto da origem/destino e validade de dados | Dependência confirmada; esquemas finais pendentes |
| P0-RV08 | INT-04: aceite não é confirmação de negócio | IN-149 distingue persistência em API receptora de entrega efetiva ao CRM; HTTP 2xx/202 não é conclusão por si | Regra confirmada em fluxo especializado; sem extrapolar todo status |
| P0-RV09 | INT-04: idempotência e incerteza | IN-148/149 exigem consulta/reconciliação antes de repetir escrita com resposta perdida, inclusive na contingência | Regra confirmada para leads; contrato geral INT ainda a especificar |
| P0-RV10 | INT-01..04 e n8n/agentes | IN-167/168 exigem privilégios mínimos, identidades técnicas e autorização independente do texto da IA | Fronteiras coerentes; desenho técnico pendente |

**Achado:** nas evidências examinadas não apareceu contradição inequívoca entre as quatro sínteses e os documentos relacionados. Isso não equivale a provar ausência de divergências no texto integral de todas as aprovações.

## 3. Observações e cautelas por INT

### INT-01 — Provedores, contas e conexões
- INT-01 administra o objeto conexão; INT-11 homologa conector e versão; INT-02 controla credenciais e autorizações externas.
- Mudanças de vínculo de site/formulário para nova conta CRM não alteram retroativamente o destino autorizado de submissões anteriores nem transportam automaticamente histórico da antiga conta.
- **Verificar na aprovação integral:** nomenclatura/título, lista exata de estados de conexão, aprovações de alteração, vigências e condições de desativação, responsabilidades por objetos compartilhados e pendências.

### INT-02 — Credenciais, escopos e autorização
- O cofre técnico e a governança de segredos já constam da IN-151; INT-02 deve utilizá-los sem implantar sistema de segredos concorrente.
- Token/segredo de integração interna nunca deve ser entregue a frontend público. Identificador público de formulário não é senha nem dispensa segurança no backend.
- Rotações de baixo risco podem ser automatizadas somente se homologadas e autorizadas; ações críticas seguem aprovação específica aplicável, sem aprovação duplicada para o mesmo ato. Aprovações de negócio distintas não podem ser suprimidas.
- Autorizações, escopos e eventual consentimento possuem finalidades distintas; a simples palavra 'consentimentos' no título resumido não comprova que a INT-02 tem autoridade legal exclusiva sobre tratamentos.
- **Verificar na aprovação integral:** políticas por provedor/identidade/tenant, condições de delegação, revogação, consentimentos, segregação e o título textual.

### INT-03 — Esquemas, mapeamentos e transformações
- Transformação e validação não concedem permissão para gravar, mesclar, excluir ou alterar registro oficial, nem autorizam execução de código livre/SQL.
- Identificadores de submissão, origem, vínculo temporal e destino autorizado precisam permanecer íntegros ao serem transformados.
- Envio repetido da *mesma* submissão lógica não gera outro lead, mas submissões legítimas distintas com conteúdo idêntico não devem ser fundidas cegamente.
- **Verificar na aprovação integral:** esquemas, tipos, formatos, prévias, validações e restrições granularmente aprovadas.

### INT-04 — Operações técnicas, adaptadores e capacidades
- Um adaptador prepara/envia operações tipadas; o módulo proprietário mantém decisão e confirmação de fato de negócio.
- No fluxo de leads, são distintos o recebimento **duravelmente persistido na API** e a entrega **efetivamente comprovada no CRM**. Um código HTTP que apenas aceita a solicitação não conclui automaticamente a operação.
- Timeout depois da transmissão ou escrita parcialmente aplicada exigem estado inconclusivo e reconciliação antes de repetir algo que possa duplicar efeitos.
- **Verificar na aprovação integral:** capacidades, contratos, estados, idempotência, erros, limites, documentação e versões por provedor.

## 4. Políticas específicas que não podem virar regras universais

- **IN-147/148/149:** 3 dias de fila normal + 15 dias de falhas, total de 18 dias desde persistência inicial; aviso preventivo 72 horas antes; descarte do payload temporário não entregue após verificação e requisitos aplicáveis; limpeza 24 horas depois de entrega confirmada; auditoria técnica minimizada 90 dias. Essas políticas são específicas de ingestão de leads e não definem retenção global das INTs, contratos, pagamentos ou CRM.
- **IN-151/167:** políticas de autonomia e credenciais dos agentes técnicos internos não substituem autorizações comerciais, financeiras, de identidade e demais competências oficiais.
- **INT-R01/02:** comandos, mapeamentos, eventos e modelos versionados não presumem autorização vigente nem conclusão de negócio.

## 5. Pendências rastreáveis

| Item | Ação necessária | Estado |
|---|---|---|
| P0-LIT-01 | Recuperar/cotejar deliberação integral da INT-01, título original e exceções | Não verificado |
| P0-LIT-02 | Recuperar/cotejar deliberação integral da INT-02, incluindo escopos e consentimentos | Não verificado |
| P0-LIT-03 | Recuperar/cotejar deliberação integral da INT-03, mapeamentos e restrições | Não verificado |
| P0-LIT-04 | Recuperar/cotejar deliberação integral da INT-04, capacidades/estados/contratos | Não verificado |
| P0-OWN-01 | Matriz de propriedade e autorização por operação, recurso e campo | Em especificação |
| P0-CON-01 | Schema de comando/evento/resultado e enumerações por contrato | Em especificação |
| P0-SEC-01 | Revogação/revalidação por identidade, tenant, escopo e finalidade | Em especificação |
| P0-XREF-01 | Verificar políticas IN-124/148/149/150/151/167/168 sem duplicação | Amostra inicial cruzada, não concluído integralmente |

## 6. Resultado e próximos critérios
A conferência cruzada inicial INT-01..04 foi realizada e registrada. **P0-DOC-01, P0-TR-01, P0-OWN-01, P0-CON-01 e P0-SEC-01 continuam abertos** até documentação integral, vínculo a fontes originais e aceites. Não há programação ou homologação realizada.

Próximo bloco documental previsto: conferência INT-05 a INT-08, além de continuar buscando as fontes originais e resolver os contratos técnicos P0.