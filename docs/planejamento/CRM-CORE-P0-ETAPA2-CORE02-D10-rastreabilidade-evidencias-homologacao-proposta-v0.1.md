# CRM Imagine — CORE02-D10: rastreabilidade, evidências e homologação das permissões — proposta v0.1

**Data da proposta:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA — CORE02-D10-A/B/C/D AGUARDAM DECISÃO EXPRESSA**.  
**Objetivo:** definir critérios verificáveis e responsabilidades para especificar, testar futuramente e homologar os contratos de autorização por recurso, ação, campo, tenant, plano e escopo. Não fecha CORE02, não concede permissões, não executa testes, não determina tecnologias físicas, e não autoriza merge ou produção.

## 1. Base aprovada e fronteiras que permanecem abertas

- CORE02-D01..D03 aprovadas como base documental; [D04](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md) e [D05](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md) possuem subdiretrizes preliminares. [D06](CRM-DEC-ratificacao-explicita-CORE02-D06-ABCD-2026-11-05.md), [D07](CRM-DEC-ratificacao-explicita-CORE02-D07-ABCD-2026-11-05.md), [D08](CRM-DEC-ratificacao-explicita-CORE02-D08-ABCD-2026-11-05.md) e [D09](CRM-DEC-ratificacao-explicita-CORE02-D09-ABCD-2026-11-06.md) têm aceites expressos **somente preliminares**. **CORE02-D04..D10 integrais seguem ABERTAS**.
- Seguem sem decisão final: composição de perfis P1–P4, grants/denies específicos, catálogo real de campos/ações por módulo, papéis/delegações, MFA/step-up/alçadas concretos, política executável em Laravel/PostgreSQL RLS, revogação, quotas finais por plano, critérios operacionais e evidências de homologação.
- [CORE10 — pacote de homologação](CRM-CORE-P0-10-pacote-homologacao-documental-nucleo-proposta-v0.1.md), [CORE Etapa 2 — matriz](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md), INT P0/G01..G06 e SEG-01/02 são fontes complementares. Não criar novo gate, suíte duplicada, módulo de auditoria, motor IAM ou bypass de restrições.
- As provas sintéticas executadas no desenvolvimento experimental Codex/CI pertencem a seus próprios incrementos, SHA, evidências e gates. Não são homologação do catálogo completo CORE02, segurança total do CRM ou autorização de produção.

## 2. Estrutura candidata de evidência por operação

| Dimensão obrigatória | Evidência documental/implementável a produzir na fase competente | Lacuna que impede fechamento |
|---|---|---|
| Proprietário, fonte e versão | Módulo dono do recurso/ato, documento normativo, contrato/campanha e versão revisada | Sem proprietário ou regra conflitante |
| Ator e legitimidade | Identidade, membership/mandato, tenant, principal técnico e escopo mínimo | Papel, CNPJ, token ou GUC usado como grant |
| Produto e estado | Entitlement, plano, quota/canal, vigência e estado oficial de assinatura/cortesia/trial | Recurso liberado por sucesso técnico ou UI |
| Recurso, ação e campo | Catálogo efetivo, classificação, visibilidade, posse/equipe, finalidade e consequência | `read` confundido com `export` ou dado sensível exposto |
| Segurança | MFA/step-up, alçada, bloqueios mandatórios, RLS, revogação e trilha | Política P1–P4 não escolhida; bypass ou acesso residual |
| Resultado e auditoria | Decisão allow/deny condicionada a política homologada, fonte do efeito oficial, erro seguro e log sanitizado | HTTP 2xx, fila ou ação IA confundidos com negócio concluído |
| Homologação futura | Cenário, expectativa, ambiente isolado, versão/SHA, resultado real, log de CI, defeito/correção e aceite responsável | Roteiro redigido sem execução, CI de outro SHA ou teste não representativo |

## 3. CORE02-D10-A/B/C/D — propostas para aceite preliminar

### D10-A — Rastreamento de cada permissão ao contrato aprovado

Exigir para toda ação protegida um vínculo versionado entre proprietário, módulo, recurso, ação, classe de campo, plano/entitlement, perfil/escopo, contexto, critério de recusa e regra de negócio vigente. Registrar exceções deliberadas e contradições sem fazer grants implícitos. A matriz de permissão nunca substitui a fonte oficial de cada efeito.

### D10-B — Casos positivos, negativos e transversais sem duplicação

Reutilizar os cenários CORE02-T01..T13, CORE01/03/04/05/06/07/08/09-T, REV/OPS e INT HT01..HT18, complementando apenas lacunas com identificador e rastreabilidade. Exigir cobertura futura para: acesso cruzado entre tenants; export sem `export`; campo protegido; autoelevação; revogação em sessão/job; MFA faltante; quota excedida sem compra automática; plano sem recurso; IA sem autoridade; suporte sem mandato; anexo bloqueado; integração com destino não autorizado; permissão em relatório/exportação/API distinta da UI. Não executar testes nesta aprovação.

### D10-C — Evidência verificável, resultados e tratamento de falhas

Separar claramente estados **proposto / preliminarmente aprovado / especificado / implementado / testado / homologado**. Prova de teste exigirá versão/SHA exatos, ambiente, data, procedimento, resultado, logs, falhas e reexecução após correções, com registro de dono e aceitação da competente área. Testes sintéticos e CI verde isolados não conferem direitos reais nem encerram dependências. Defeito crítico de isolamento ou autorização deve bloquear a promoção da funcionalidade afetada, preservando rastreabilidade da decisão.

### D10-D — Homologação coordenada, conflitos e decisão formal de saída

Exigir reconciliação CORE01/02/03/04/05/06/07/08/09/10, IAM, Segurança, módulos DAT/VEN/ATD/AGD/AUT/IA, FIN/CON, Portal/Sites, INT, Auditoria/LGPD e operações. Matriz final deve indicar dependências, responsável por competência, critério, evidência, lacuna, exceção e aceite específico. **Fechamento de CORE02-D10 e CORE02 integral requer decisão posterior expressa**, sem equivaler a aprovação de G01..G06, SEG-01/02, deploy ou produção.

## 4. Exemplos de critérios negativos para futura homologação (não executados)

| Caso | Critério esperado | Dependência |
|---|---|---|
| Identidade de tenant A tenta consultar ou exportar contato B | Negar no backend e no banco; não vazar existência/dado | CORE01/02, SEG |
| Usuário autorizado a visualizar lead tenta exportá-lo em lote | Exigir direito de exportação e limites de campos/escopo | D04/D05/D06, LGPD |
| Permissão removida após export enfileirado | Revalidar antes de disponibilizar resultado | D07/D08, CORE03/04/07 |
| Cliente Básico tenta convidar segundo login | Negar pelo entitlement de 1 usuário, não por controle cosmético de UI | CORE07/08, FIN/CON |
| Administrador Imagine sem mandato tenta ler dados comerciais tenant | Negar e auditar; sem bypass irrestrito | CORE05, SEG, CORE06 |
| IA ou n8n solicita operação externa sem autoridade causal | Negar efeito e registrar motivo seguro | CORE03/09, INT, AGT |
| Trial/cortesia expira e sistema tenta cobrar automaticamente | Não criar assinatura cobrada sem aceite válido | CORE08, FIN/CON |
| Sucesso HTTP/webhook em pagamento não confirmado pelo dono | Não conferir entitlement enquanto confirmação não existir | FIN/CON, INT |
| Anexo bloqueado por malware é solicitado por usuário com permissão | Bloquear acesso ao conteúdo, sem desmembrar permissão conjunta de ver/baixar | ARQ, Segurança |

## 5. Gate de encerramento do bloco — pendente

CORE02 somente poderá ser proposto como **homologado** depois de resolução documentada das escolhas pendentes D04..D09, catálogo físico real, planos/perfis/escopos/denies definidos, autorização Laravel/RLS e revogação tecnicamente validadas, testes positivos/negativos executados no escopo/ambiente apropriado com evidências por SHA e decisão competente. Uma aprovação preliminar de D10 **não altera** os 67 identificadores CORE pendentes, SEG/INT/ARQ/FIN/CON/LGPD, nem os gates G01..G06. Fases de Codex fast-track já autorizadas continuam sob autorização separada.

**Não executar código, testes, configuração de VPS, credenciais, alteração de plano, implantação nem merge por causa deste documento.** A próxima revisão depois de um eventual aceite preliminar deve ser o fechamento transversal **CORE02-D04..D10** (com responsáveis e evidências), ou outro bloco CORE priorizado, sem marcar conclusão por mera assinatura desta pauta.
