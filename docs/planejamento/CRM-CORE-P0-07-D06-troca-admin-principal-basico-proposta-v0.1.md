# CRM Imagine — CORE07-D06: transferência de administrador principal e recuperação de titularidade — proposta v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PAUTA DOCUMENTAL NÃO APROVADA — CORE07-D06 ABERTA**.  
**Natureza:** proposta para definir a continuidade administrativa de uma organização CRM quando o responsável muda ou perde acesso, sem violar o limite de um usuário do Básico, sem novos grants, sem programação/testes e sem tornar suporte Imagine um administrador permanente.

## 1. Decisões a preservar

- Básico pago/cortesia: **um administrador humano autenticado, com MFA obrigatório**. Não criar segundo assento ou bypass temporário por padrão. No trial há quota própria de dois usuários. Planos superiores podem ter administradores adicionais se contratualmente elegíveis, com competências delimitadas.
- Uma identidade global pode manter memberships independentes em organizações diferentes; transferir a titularidade de A não afeta automaticamente os direitos em B.
- [CORE07-D05 Modelo A](CRM-DEC-ratificacao-explicita-CORE07-D05-modelo-A-reserva-assentos-2026-11-06.md): convites de funcionários pendentes reservam vaga existente, não concedem acesso e não compram adicional.
- [CORE07-D04 Modelo B](CRM-DEC-ratificacao-explicita-CORE07-D04-modelo-B-renovacao-2026-11-06.md): renovação de convite por ação expressa invalida link anterior e gera novo prazo de 7 dias, sem reserva duplicada.
- [CORE02-D05 alternativa B e M2](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV01-M2-2026-11-06.md): alterar administrador/perfil, atribuir e delegar são ações distintas e requerem autoridade específica, sem autoelevação.
- CORE04/CORE05/SEG: recuperação de MFA e de administrador titular exigem verificação apropriada, trilha, proteção de credenciais e mandato restrito; e-mail isolado é insuficiente no caso crítico de perda conjunta do fator e códigos. Não impor segundo aprovador universal; casos excepcionais podem exigir checagem independente segundo risco.

## 2. Alternativas para o Básico — NÃO APROVADAS

| Modelo | Como transferir | Vantagem | Risco |
|---|---|---|---|
| **A — substituição atômica e verificada** | Titular autorizado inicia transferência, novo titular confirma identidade/MFA; no momento oficial da ativação, o vínculo anterior perde a função e o novo assume **o mesmo único assento**, com revalidação e auditoria | Continuidade segura, sem duas sessões administrativas operacionais no Básico | Exige transação e procedimentos de falha/retomada para evitar perda de controle |
| **B — dois administradores temporários** | Adiciona novo administrador enquanto antigo continua ativo por prazo temporário | Facilita transição aparente | Contraria a quota 1/1 do Básico e aumenta risco de acesso simultâneo |
| **C — cancelar conta antiga e abrir outra** | Recriar organização e migrar dados | Evita fluxo de transferência | Fragmenta históricos, assinaturas, site vinculado e dados, sendo inadequado como regra geral |

**Recomendação:** **Modelo A como diretriz documental preliminar**, sem liberar login duplo no Básico, sem excluir histórico ou forçar nova assinatura. A transferência deve ser tratada como **operação sensível distinta de convite comum**; não presumir que a reserva por convite comum autorize sobreposição 2/1.

## 3. Condições propostas para A

1. **Transferência iniciada pelo titular legítimo:** exigir autoridade, reautenticação/step-up apropriado, identificação clara da organização, novo titular, ação e consequências. Registrar intenção pendente sem conceder acesso administrativo antecipado; cancelamento/expiração não causa troca.
2. **Aceite do sucessor:** novo titular comprova identidade e configura MFA obrigatório antes da troca. Não herda sessões, senhas, códigos ou tokens do titular anterior e não obtém acesso por conhecer e-mail/CNPJ.
3. **Troca oficial única e atômica:** na ativação autorizada, o novo titular assume a única membership/assento de administrador; anterior é revogado no mesmo ponto de consistência, com revalidação de sessões e efeitos pendentes. Garantir que o tenant não fique inadvertidamente sem responsável por erro/queda de fluxo.
4. **Mesmo histórico e contrato:** preservar organização, dados, auditoria, fluxos comerciais, assinatura e vinculações oficiais; transição não concede plano/benefício, duplica trial, reinicia assinatura ou altera CNPJ automaticamente.
5. **Perda do único administrador ou do MFA:** usar procedimento excepcional com verificação forte e competência específica de suporte/segurança, mandato limitado e auditoria; **sem** login de suporte permanente, sem acesso irrestrito a dados comerciais e sem segundo assento para contornar recuperação. O fluxo detalhado pertence a CORE04-D07/CORE05 e SEG-01/02.
6. **Outros planos:** em organização com vários admins permitidos, exigir regras para preservar ao menos um administrador válido e evitar transferência sem legitimidade, conforme papéis e quotas; não impor a política do Básico à capacidade de planos superiores.
7. **Prevenção de conflito e evidências:** transferência concorrente, falha de aceite, tentativa de autoelevação, revogação tardia, conta do sucessor comprometida e rollback devem ter casos futuros de teste, versão, auditoria e procedimentos de recuperação.

## 4. Pendências que permanecem após eventual aceite do Modelo A

- Política concreta de transição, janela, expiração, métodos de MFA, competência para abrir e concluir transferência, checagem legal/documental em caso de falecimento/incapacidade/disputa societária, prazos de revogação e comunicação, suporte emergencial, fraude e titularidade contratual.
- Contratos de API, transações, RLS, estados, locks, comportamento diante de falhas, dados pessoais e provas executadas, em CORE01/02/03/04/05/06/07/08/10, SEG, FIN/CON, LGPD e INT.
- CORE07-D06 **permanece ABERTA** como identificador técnico; nenhuma alteração real é autorizada. CORE07-D04/D05/D07..D10 e demais 67 IDs CORE e gates transversais continuam pendentes.

**Próxima deliberação proposta:** escolher explicitamente **Modelo A — transferência atômica de titularidade**, limitada às diretrizes documentais. Não programar, conceder acessos, emitir convites, alterar `main`, branch do Codex, VPS ou produção por esta pauta.
