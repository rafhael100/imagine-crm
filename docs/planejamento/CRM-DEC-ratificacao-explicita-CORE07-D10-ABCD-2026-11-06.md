# CRM-DEC — Ratificação expressa CORE07-D10-A/B/C/D (diretrizes documentais preliminares)

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação:** o responsável respondeu **“aprovado”** à pergunta explícita **“Você aprova CORE07-D10-A, B, C e D nessas condições?”**, imediatamente após a apresentação de [CORE07-D10 — Matriz de homologação e dependências](CRM-CORE-P0-07-D10-homologacao-dependencias-proposta-v0.1.md).  
**Efeito:** **CORE07-D10-A/B/C/D RATIFICADAS EXPRESSAMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**. Não é aprovação do fechamento integral de CORE07-D10 ou CORE07, nem de SEG/INT, testes, código, deploy, merge ou alterações comerciais.

## 1. Diretrizes expressamente aprovadas

1. **CORE07-D10-A — Contratos e responsáveis:** estruturar inventário versionado de objeto, ação, campo, tenant, fonte normativa, owner funcional/técnico, identidade/competência, entitlement/plano, estados de convite/assento/membership/equipe, operação/evidência e revogação; reconciliar com CORE01/02/04/08/FIN/CON e VEN/ATD/AGD sem conceder direitos por analogia.
2. **CORE07-D10-B — Casos de teste planejados:** preparar cenários positivos, negativos e concorrentes reutilizando CORE07-T, CORE10, SEG e INT existentes, incluindo convite de sete dias e link antigo revogado, reserva única e última vaga, substituição do administrador Básico 1/1 com MFA, mudança de equipe/tenant, redistribuição legítima por módulo, revogação e export pendente. **Não equivale a execução de testes.**
3. **CORE07-D10-C — Evidência e bloqueios:** distinguir *diretriz aprovada*, *especificação*, *implementação*, *teste executado* e *homologação*. Exigir no futuro owner, requisito, SHA/versão, ambiente, dados de teste, procedimento, resultado, defeitos, correções/reteste e aceite. Falhas críticas de isolamento, MFA, excesso de assentos, revogação/exportação e consistência **impedem promoção da funcionalidade afetada** até solução formal.
4. **CORE07-D10-D — Dependências e saída formal:** exigir reconciliação de CORE01/02/03/04/05/06/08/09/10, VEN/ATD/AGD/AUT/IA, SEG-01/02, FIN/CON, INT P0/G01..G06 e Portal/Sites, com registro de owner, critério, evidência, lacuna e decisão. **Encerrar CORE07 tecnicamente só mediante aprovação posterior específica e após provas e gates**, sem inferir fechamento por aprovação deste documento.

## 2. Alcance documental e situação depois da aprovação

- **A rodada de seleção das diretrizes documentais do CORE07-D04..D10 está concluída em nível preliminar**: D04 Modelo B, D05 Modelo A, D06 Modelo A, D07 Modelo B, D08 Modelo B + A/B/C/D, D09 A/B/C/D e D10 A/B/C/D; D01..D03 possuem base documental previamente aprovada.
- **Não confundir rodada preliminar concluída com CORE07 concluído**. **Todos os sete IDs CORE07-D04..D10 permanecem integralmente ABERTOS**, aguardando especificações físicas, reconciliação com módulos e contratos, execução de testes, evidências e homologação.
- O painel histórico de **67 identificadores CORE técnicos abertos** continua válido como métrica de fechamento, não de decisões preliminares pendentes. CORE01-D04..D07, CORE02..CORE10-D04..D10 permanecem com pendências técnicas; SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD e DEV-GATE global não se encerram por esta ratificação.
- **Sem autorização para** alteração de código, SQL, RLS, papéis/permissões de usuários, convites ou dados reais, testes executáveis de homologação, custos/contratações, deploy, merge, `main`, VPS ou produção. Preservar branch Codex `dev/fast-track-crm-2026-11-04`, com governança e aprovações próprias.

## 3. Próxima ação documental autorizada

Preparar **revisão consolidada CORE07-D04..D10** com matriz de lacunas reais, dependências, sequência para especificação e critérios de GO/NO-GO. Essa revisão é **inventário documental**, não encerra os IDs nem pressupõe autorização para implementar/mesclar a documentação na `main`.
