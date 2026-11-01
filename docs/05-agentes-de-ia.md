# 05 — Agentes de inteligência artificial

## Aprovado
- Agentes opcionais contratados individualmente por empresa, como **IA Prospector** e **IA Comercial**.
- Cotas mensais por agente, isoladas entre empresas.
- Pacotes adicionais de consumo podem ser comprados com autorização expressa do cliente; **sem cobrança extra automática**.
- Cota mensal não acumula para meses futuros.
- Saldo comprado permanece até consumo; usar primeiro a cota mensal e depois o saldo comprado.
- Limite de custo interno configurável por agente/empresa; atingido o limite, novas execuções são suspensas e o administrador alertado, sem consumir saldo do cliente.
- Administrador autorizado pode ajustar/desbloquear com justificativa e auditoria.
- Histórico técnico: empresa, agente, momento, duração, consumo, custo, estado e erro, com limitações de privacidade.
- Interruptor global emergencial por agente para todas as empresas, com reativação autorizada e auditada.

## Pendências
- Preço de cada agente, cota mensal por plano, custo dos pacotes, modelos e prompts, privacidade/retenção de mensagens e integrações específicas.

## Atendimento WhatsApp por IA fora do expediente — APROVADO
- O CRM Imagine oferecerá opção **configurável por empresa** para manter os agentes de IA respondendo aos clientes **24h/7d**, independentemente dos horários de funcionários e setores.
- O agente poderá qualificar leads, responder dúvidas, consultar contexto autorizado, atualizar CRM e preparar encaminhamentos de acordo com as permissões e regras da empresa.
- Fora do expediente do destino humano, comunicar os horários aplicáveis, manter transferência pendente/assistida na origem e contar SLA de aceite somente durante o expediente do destino.
- Se o atendimento estiver sob posse de humano, **não reativar o agente automaticamente** ao fim do expediente; somente o humano autorizado pode devolver a conversa à IA.
- Não usar a disponibilidade 24/7 para contornar políticas oficiais de envio do WhatsApp, regras de consentimento/opt-out, segurança, cotas, limites de custo ou controles de suspensão do agente.
- Configurações mais granulares por agente/número, mensagens de ausência e escalonamentos específicos permanecem a detalhar.

## Transparência e solicitação de atendimento humano no WhatsApp — APROVADO
- Cada agente de IA deve **identificar-se claramente como assistente virtual/inteligência artificial no início do atendimento**, sem fingir ser pessoa ou funcionário humano.
- Permitir configuração por empresa de **nome e mensagem de saudação**, preservando sempre a identificação inequívoca de que se trata de atendimento automatizado.
- Informar e oferecer ao cliente a possibilidade de **solicitar atendimento por um humano**, aceitando pedidos em linguagem natural durante a interação.
- Ao receber solicitação de humano, encaminhar para **equipe/fila permitida** com contexto necessário, histórico e rastreabilidade, respeitando permissões e regras de posse e transferência já aprovadas.
- Fora do expediente humano, informar os horários aplicáveis, registrar a solicitação e **não prometer atendimento humano imediato**; a confirmação de aceite depende do efetivo início ou aceite pelo destino.
- Seguir as políticas aplicáveis à plataforma WhatsApp, consentimento e privacidade, inclusive regras de envio e templates aprovados quando exigidos.
- **Resolvido:** IA pausa imediatamente ao pedido de humano; pode retomar por pedido explícito do cliente somente enquanto ainda não houver atendente humano que assumiu. Após posse humana, retorno exige devolução autorizada.

## Pausa de IA por solicitação do cliente para falar com humano — APROVADA
- Ao reconhecer um pedido inequívoco do cliente para falar com uma pessoa, o agente de IA deve **confirmar o encaminhamento** e **pausar imediatamente suas respostas automáticas naquela conversa**.
- A solicitação é registrada e encaminhada à fila/equipe humana autorizada, mantendo contexto e histórico. Enquanto não houver aceite/início efetivo, a solicitação permanece **pendente**, sem conclusão automática e respeitando o horário de funcionamento e SLA já definidos.
- Quando o cliente **ainda estiver aguardando** a equipe humana, ele poderá pedir explicitamente para voltar a conversar com o assistente virtual; o sistema deve cancelar a solicitação pendente e registrar a retomada, evitando atendimento simultâneo.
- Se **um funcionário já tiver assumido efetivamente** a conversa, somente um humano autorizado poderá devolvê-la à IA. Pedido do cliente isoladamente não pode reativar IA durante a posse humana.
- Não permitir que mensagens automáticas do agente já enfileiradas sejam enviadas após a pausa; revalidar posse e status antes de cada envio e auditar transições.
- A comunicação de solicitação recebida deve ser clara, sem inventar disponibilidade ou prometer tempo exato de resposta humana.

## Assistência de IA em mensagens enviadas por funcionários — APROVADO
- Quando um funcionário utilizar efetivamente uma sugestão de mensagem da IA Comercial ou outro agente autorizado e enviar a mensagem, identificar a interação como **"Assistida por IA"**.
- A **autoria da mensagem enviada é do funcionário**, não do agente, inclusive após revisão e edição. Registrar vínculo ao agente que auxiliou e horário relevante, sem exposição indevida de dados sensíveis.
- A simples visualização ou geração de sugestão sem utilização **não deve contar como mensagem assistida**.
- Esta marcação não converte, por si só, um atendimento **Humano** em **Misto**; esta última categoria requer participação direta de IA e de humano nas interações conforme decisão de classificação comercial.
- Manter contagem de resultados/vendas sem duplicidade e distinguir consumo técnico do agente, envio direto autônomo e assistência ao funcionário em análises futuras.
- **Pendente:** definir regras de edição de sugestões, UX da ferramenta, telemetria de adoção e critérios para alterações substanciais antes do envio.
