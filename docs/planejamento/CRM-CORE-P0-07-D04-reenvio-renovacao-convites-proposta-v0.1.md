# CRM Imagine — CORE07-D04: reenvio, reemissão e expiração de convites — proposta v0.1

**Data:** 06/11/2026.  
**Estado atualizado em 06/11/2026:** **MODELO B DE RENOVAÇÃO DE CONVITES RATIFICADO EXPRESSAMENTE SOMENTE COMO DIRETRIZ DOCUMENTAL PRELIMINAR**, conforme [decisão formal](CRM-DEC-ratificacao-explicita-CORE07-D04-modelo-B-renovacao-2026-11-06.md). **CORE07-D04 integral permanece ABERTA**; limites antispam, token, reemissões, exceções e homologação ainda pendentes. Referências posteriores a modelos não escolhidos são históricas anteriores à ratificação.  
**Finalidade:** decidir o comportamento de reenvio de convites de funcionários com validade de 7 dias, integrado à [reserva de assentos Modelo A aprovada preliminarmente](CRM-DEC-ratificacao-explicita-CORE07-D05-modelo-A-reserva-assentos-2026-11-06.md). Sem criação de tokens reais, código, política de senha, MFA, quotas novas, cobrança ou testes.

## 1. Invariantes já ratificados

- Convites são emitidos por administrador competente para um destinatário, papel e tenant específicos; expiram após **7 dias**. Convite emitido não é membership ativa, nem concede acesso antecipado.
- **CORE07-D05 Modelo A:** convite pendente válido reserva vaga disponível dentro da quota; convite cancelado/recusado/expirado libera reserva; aceite legitimamente validado converte reserva em assento ocupado uma vez. Não há segundo login no Básico quando a vaga única está ocupada.
- Reenvio de link não pode duplicar membership, reserva de assento, entitlement ou faturamento; autenticação do destinatário e validação backend permanecem obrigatórias.
- Mesmo e-mail/CNPJ não dá acesso a outros tenants; funcionários não compram assentos, alteram plano ou obtêm privilégios por receber um convite.

## 2. Alternativas avaliadas — Modelo B ratificado como diretriz preliminar

| Modelo | Ao acionar **Reenviar convite** | Benefício | Atenção |
|---|---|---|---|
| **A — reenviar o mesmo convite sem alterar validade** | Envia nova mensagem mantendo o token/expiração original, sem ampliar o prazo | Simples, sem prorrogação oculta | Link já compartilhado permanece válido até vencer; destinatário pode não ter tempo para aceitar |
| **B — revogar link anterior e emitir novo convite com 7 dias** | Ação expressa do administrador invalida token anterior e gera novo token/validade de 7 dias; mantém **uma única reserva**, desde que ainda elegível | Recupera o prazo de forma clara e substitui link potencialmente perdido | Necessita impedir reenvio automático ilimitado, spam e reserva perpetuamente prolongada |
| **C — cada reenvio cria convite independente** | Novo convite coexistiria com anterior e exigiria resolver concorrência | Nenhuma vantagem clara frente a B | Aumenta risco de duplicação e confusão de reservas, portanto não recomendado |

**Modelo expressamente escolhido:** **Modelo B, somente quando o administrador competente solicitar expressamente a reemissão**. Não permitir renovação automática do prazo nem reenvio silencioso; distinguir na interface **“Reenviar mensagem”** (mesma validade, quando houver) e **“Renovar convite”** (revogar token anterior e emitir outro com novo prazo). Se a opção de produto for unificada em um único botão, informar claramente que a operação revoga o link antigo e reinicia 7 dias. Limites antispam e ciclos máximos de renovações precisam ser especificados e aprovados depois.

## 3. Diretrizes adicionais candidatas (não aprovadas)

1. **D04-A — identidade, token e destinatário:** convite é vínculo entre tenant, destinatário verificado, competência de quem convidou e papel autorizado; token aleatório de uso único, não exibido em logs, protegido e revogável. Não fixar algoritmo/armazenamento físico nesta pauta.
2. **D04-B — reenvio/renovação expressa:** ao renovar, invalidar todos os links anteriores referentes à mesma intenção de convite e emitir novo link com validade de 7 dias; nenhuma renovação automática, sem mudança de papel/escopo por silêncio. Vaga reservada só permanece se ainda houver entitlement e convite elegível.
3. **D04-C — aceite e revogação seguros:** aceitar convite exige conta autenticada da pessoa correta, token atual e válido, status e assento elegíveis, MFA do papel quando exigido, ausência de duplicidade e competência legítima; cancelado, recusado, expirado ou substituído é ineficaz. Impedir duplicidade de identidade/membership com reenvio e concorrência.
4. **D04-D — comunicação, abuso e auditoria:** mostrar administrador/destinatário o prazo, status e efeitos do reenvio; notificar de modo apropriado, registrar versão, autor, envio e aceite sem tokens em claro; rate limiting, limites de renovação, falha de envio e antifraude a detalhar com CORE04/SEG e LGPD, sem números presumidos.

## 4. Casos negativos a homologar futuramente — NÃO EXECUTADOS

| Cenário | Critério |
|---|---|
| Convite original aceito após renovação B | Recusar token revogado |
| Duas requisições simultâneas para renovar | No máximo um convite ativo/reserva efetiva; controlar versão e idempotência |
| Pessoa que não é destinatária abre link | Não associar membership nem vazar dados da empresa |
| Convite foi cancelado/expirou e ficou sem reserva | Renovação só após nova verificação de vaga e competência; sem reserva fantasma |
| Convite da última vaga no Básico já ocupado | Não convidar segundo usuário nem comprar automaticamente assento |
| Plano suspenso no meio da renovação | Não reativar acesso ou entitlement por reenvio |
| Reenvio automático de e-mail falhou | Não gerar múltiplos links ativos, nem estender validade silenciosamente |

## 5. Pendências preservadas

**CORE07-D04 e D05 integrais permanecem ABERTAS** até contratos físicos, limites operacionais, regras de reserva, concorrência, recuperação de conta, reenvio, expiração, e homologação. CORE07-D06..D10, CORE02-D04..D10, CORE01/03..10, SEG-01/02, INT P0/G01..G06, FIN/CON, LGPD e baseline global não são encerrados. Uma deliberação sobre o Modelo B **não equivale a aprovar D04-A/B/C/D integrais ou suas implementações**, salvo manifestação expressa específica.

**Nenhum efeito em `main`, no código do Codex, VPS ou produção; sem autorização de desenvolvimento por esta pauta.**
