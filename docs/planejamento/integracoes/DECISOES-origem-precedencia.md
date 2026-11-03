# INT — Registro de decisões e precedência

**Aprovado funcionalmente pelo responsável:**
- INT-01 a INT-08: aprovações individuais prévias do módulo Integrações.
- INT-09: orquestração multissistema completa, com autorização, confirmação oficial, idempotência, compensação limitada e governança de proprietários.
- INT-10: monitoramento, diagnóstico, contingência, recuperação controlada, reconciliação e auditoria.
- INT-11: catálogo, homologação, versões, distribuição e governança de parceiros/conectores, sem código livre nem publicação autônoma.
- INT-12: rastreabilidade, integridade, proveniência, investigação e auditoria especializada via Auditoria Geral.
- Revisão consolidada inicial INT-01 a INT-12: aprovada.
- INT-R01: 12 seções transversais de governança, contratos, estados, segurança, concorrência, falhas, versionamento, eventos, evidências, limites e homologação: aprovada.
- INT-R02: matriz, invariantes INV-01 a INV-16, cenários HT-01 a HT-18, estados, pendências P0/P1/P2 e critérios G01 a G06: aprovada.

**Controle de fidelidade:** este registro é um índice de aprovações resumidas e não contém links verificáveis às mensagens originais. A validação P0-DOC exige conferência do texto integral de cada deliberação original, inclusive títulos, exceções, parâmetros e políticas. Não rotular como “conferido integralmente” antes disso.

## Ordem de autoridade
1. Deliberação expressa mais recente do responsável, para o mesmo requisito e escopo.
2. Deliberações aprovadas anteriores, quando não substituídas.
3. INT-R01 e INT-R02 como especificações transversais aprovadas.
4. Sínteses dos arquivos INT-01..12 como documentos auxiliares de consolidação.

Novas sugestões, interpretações da IA, exemplos e alternativas não são decisões aprovadas. Nenhuma modificação funcional, comercial ou de autoridade pode ser inferida da atualização documental.

## Revisões cruzadas P0 e natureza da origem
- [INT-01 a INT-04](P0-REV-INT01-04-conferencia-cruzada.md), [INT-05 a INT-08](P0-REV-INT05-08-conferencia-cruzada.md), [INT-09 a INT-12](P0-REV-INT09-12-conferencia-cruzada.md), e [reconciliação geral](P0-REC-conciliacao-transversal.md).
- Os detalhes aprovados de INT-09 a INT-12 aparecem no histórico conversacional deste projeto, mas a documentação do repositório **ainda não possui uma referência imutável verificável para cada mensagem original**. Para INT-01 a INT-08 também falta conferência completa dos originais.
- Evitar classificar a revisão cruzada, a redação de síntese ou um commit de trabalho como equivalentes a transcrição literal, validação final ou homologação.

## Aceites expressos agora transcritos nesta documentação

- [P0-ORIGEM — Mensagens de aprovação disponíveis no diálogo](P0-ORIGEM-aprovacoes-dialogo.md) contém os textos de aceite de INT-10, 11, 12, INT-R01 e INT-R02, com ressalva explícita sobre falta de identificador imutável e cotejo com as propostas integrais.
- O registro de aceites reduz lacuna de reprodução textual, **não fecha P0-DOC-01, G01 nem G05**. A transcrição não aprova contratos técnicos novos.
- INT-01 a INT-09 continuam sem transcrições integrais comprovadas no material deste diretório.

## Aprovação específica CT-03/CT-04 (2026-11-02)

- [P0-DEC-aprovacao-CT03-CT04](P0-DEC-aprovacao-CT03-CT04.md) transcreve a **aprovação expressa** da base documental dos contratos, estados técnicos e resultados oficiais, idempotência, concorrência, retentativas seguras e reconciliação.
- [P0-CON-CT03-CT04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) é a especificação documental de base aprovada, sem implicar homologação, parâmetros executáveis ou encerramento de P0-CON-01.
- [P0-OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) é detalhamento **proposto**, não nova aprovação funcional, 24 novas automações nem APIs programadas.

## Aceite contextual das fichas e proposta CT-02

- [P0-DEC — Aprovação OP24 como base](P0-DEC-aprovacao-OP24-base.md): a resposta “ok” autorizou a continuidade documental, sem criar nova regra funcional ou implementar direitos.
- [P0-CT02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md): matriz nova **proposta para revisão**; os princípios gerais de segurança já aprovados prevalecem e os papéis/alçadas específicos continuam pendentes.

## Aceite contextual CT-02 e novas minutas P0 (03/11/2026)

- [P0-DEC — Aceite contextual CT-02](P0-DEC-aprovacao-CT02-base.md): “ok” subsequente à recomendação de aprovação da base CT-02, limitado a prosseguir com documentação. Não aprova por inferência perfis, MFA por ação, alçadas ou esquemas técnicos.
- [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md) e [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md) são **minutas para revisão**, não escolhas técnicas ou operações automaticamente aprovadas.

## Aprovação SEC/VER e autorização de documentação de homologação (03/11/2026)

- [P0-DEC — Aceite SEC/VER](P0-DEC-aprovacao-SEC-VER-base.md): transcrição da aprovação expressa como bases documentais, sem fechar perfis, MFA/alçadas, parâmetros ou aceites finais.
- [P0-ACC — HT-01 a HT-18](P0-ACC-planos-homologacao-HT01-HT18.md) e [controle de evidências](P0-ACC-matriz-controle-evidencias-HT01-HT18.md) são **minutas para revisão**, criadas sem executar testes, não constituindo nova automação ou homologação.

## Aceite P0-ACC e verificação de origem/dependências (03/11/2026)

- [P0-DEC — 18 planos HT como base documental](P0-DEC-aprovacao-ACC-HT01-HT18-base.md) documenta a aprovação contextual “ok”, sem aceite individual definitivo ou execução de teste.
- [P0-TR-AUD](P0-TR-AUD-auditoria-proveniencia-requisitos.md) diferencia aceites transcritos, sínteses funcionais e propostas integrais não cotejadas. Os 53 IDs iniciais não são cobertura exaustiva certificada.
- [P0-DEP](P0-DEP-dependencias-criticas-e-fronteiras.md) e [P0-GATE](P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) registram dependências e bloqueios sem reabrir decisões aprovadas, nem declarar os critérios G01–G06 cumpridos.

## Regra de mudança
Toda divergência deve registrar: ID do requisito, texto/referência original, texto divergente, impacto, decisão expressa requerida, estado e histórico de resolução. Sem correção silenciosa da decisão de negócio.
