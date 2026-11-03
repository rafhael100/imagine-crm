# P0-DEC — Aceite contextual de CT-05 a CT-11 como bases documentais

**Data:** 2026-11-03. **Origem:** resposta “ok” do responsável à recomendação imediatamente anterior de **aprovar CT-05 a CT-11 como bases documentais**, mantendo schemas, parâmetros, permissões e regras por provedor pendentes, e prosseguir à **revisão cruzada dos 11 contratos CT-01 a CT-11**.
**Texto efetivamente fornecido na resposta:** “ok”. Não inventar ID de mensagem, ata assinada ou transcrição mais extensa.
**Natureza:** aceite **documental contextual limitado**, não homologação técnica nem aprovação integral de novos critérios físicos.

## Base aceita

- [P0-CON CT-05 a CT-11](P0-CON-CT05-CT11-contratos-integracoes-minuta.md): **aceita como base documental** para identidade/versionamento, estados conceituais de interoperabilidade, direitos contextuais, falhas, resultados inconclusivos, reconciliação e auditoria.
- Os sete objetos são **famílias de contratos** já previstas em [INT-R01/R02](INT-R01-governanca-contratos-homologacao.md), e **não** sete novas automações, serviços ou motores.
- [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) e [CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) continuam bases documentais anteriormente aceitas; **CT-01 permanece família conceitual no inventário**, ainda sujeita ao próprio detalhamento.
- Preservam-se autoridade dos módulos proprietários para atos de negócio, Identidade e Segurança, INT-02, Central de Aprovações, Motor Central de Eventos, Filas, Tarefas, Datas/Prazos, Arquivos, COM, Observabilidade, Auditoria Geral e IN-118, sem duplicar serviços.

## Limites explícitos da interpretação do “ok”

O aceite **não aprova automaticamente**: schemas de API/JSON, nomes definitivos de campos/enums, tipos de eventos ou endpoints específicos, política por provedor, timeout/TTL/quotas/retries numéricos, formatos adicionais, perfis/MFA/alçadas específicas, direito de compensação, garantias exactly-once, critérios de versão/migração ou execução de scripts/testes.

**P0-CON-01 continua ABERTO/PARCIAL** até contratos completos e aprovados por operação, autoridades e fontes originais cotejadas. P0-DOC-01/02, P0-TR-01, P0-OWN-01, P0-SEC-01, P0-DEP-01, P0-VER-01, P0-ACC-01, P0-XREF-01, P0-GIT-01 e G01–G06 mantêm o estágio anterior.

## Etapa seguinte autorizada

- **Revisão cruzada documental CT-01..11**: responsabilidades, identificadores/versões, fronteiras de estados, vínculos TR/INV/HT, autorização, fonte de confirmação, dependências, potenciais contradições e propostas de tratamento.
- Distinguir a **família CT-01 ainda sem ficha especializada aprovada**, sem inferir equivalência com CT-02..11.
- Registrar uma matriz de pendências e recomendações por contrato, classificando o que é princípio aprovado versus proposta técnica ainda sujeita a aceite.

**Proibição preservada:** não programar, criar automações, instalar provedores, executar testes, publicar conectores, alterar produção, contratar ferramentas ou incorrer em despesas. O projeto CRM Imagine continua em fase de planejamento.
