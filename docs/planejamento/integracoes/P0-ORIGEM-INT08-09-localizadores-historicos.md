# P0-ORIGEM — Localizadores históricos e cadeia de evidência INT-08 / INT-09

**Data da verificação:** 2026-11-03.
**Status:** localizadores parciais para recuperação da fonte primária. **Não é transcrição literal certificada**, nem resolve P0-DOC-01, P0-DOC-02 ou P0-TR-01.
**Escopo:** somente investigação documental, sem programação, testes, acesso à produção, nova automação ou contratação.
**Fontes cotejadas:** referências contextualizadas do histórico de conversas disponível nesta etapa; repositório GitHub privado `rafhael100/imagine-crm`, árvore do branch `main`, histórico de commits por caminho e arquivos INT-08/09; [confronto de 11 TR e 8 lacunas](P0-FOCO-INT08-09-confronto-11TR-8lacunas.md).

## 1. Linha do tempo — evidência e limites

| Registro / marcador | Tempo informado (UTC) | O que o registro permite afirmar | Limite da prova |
|---|---|---|---|
| Referência histórica da **proposta INT-08** | 2026-11-02 15:00:29 | Há um apontamento contextual para proposta extensa apresentada pelo assistente, descrita no índice histórico como possuindo seções 1–41 | **Conteúdo integral não recuperado**; contagem de seções é um localizador secundário, não sumário literal ou prova de todas as cláusulas |
| Referência histórica do **aceite da INT-08** | 2026-11-02 15:45:10 | Há um apontamento contextual a uma mensagem de aprovação completa do usuário | **Texto original integral e identificador imutável não recuperados** nesta verificação; não citar como transcrição |
| Referência histórica da **proposta INT-09** | 2026-11-02 15:49:13 | Há apontamento contextual para proposta extensa do assistente descrita como seções 1–39 | **Conteúdo integral e a mensagem original de aceite INT-09 não recuperados** |
| Primeiro commit conjunto dos arquivos INT-08/09 | 2026-11-02 16:53:09 | Commit GitHub `3ae7cc3d27...` consolidou os arquivos de síntese e outras matrizes | **Não é cópia das propostas originais**; a redação dos arquivos declara tratar-se de síntese |
| Revisão do arquivo INT-08 | 2026-11-02 18:13:06 | Commit `fa25e69889...` acrescentou referência à revisão cruzada | Alteração secundária, não fonte de aceite integral |
| Revisão do arquivo INT-09 | 2026-11-02 18:22:12 | Commit `daaeeb702f...` acrescentou referência à revisão cruzada | Alteração secundária, não fonte de aceite integral |

**Precisão da cronologia:** os horários das três mensagens de conversa são **marcadores obtidos de índice contextual de conversas**, não timestamps auditados a partir da mensagem original exportada. Os tempos e commits do GitHub foram obtidos do histórico do repositório. Não confundir os dois níveis de confiabilidade.

## 2. Busca adicional no repositório

- A árvore recursiva do branch `main` foi conferida. Os arquivos INT-08/09 e seus P0 correlatos estão presentes, mas **não foi encontrado um arquivo identificado como reprodução integral das propostas originais INT-08 e INT-09**.
- O histórico de commits por caminho indica **duas revisões** em cada arquivo INT-08.md/INT-09.md: a consolidação inicial e um complemento de revisão P0.
- Não foram encontrados commits anteriores desses **mesmos caminhos** contendo as propostas originais completas. Isso **não prova** inexistência em outra origem (conversa, exportação, branch anterior, anexo não recuperado).
- Buscas adicionais de Project/Library não forneceram texto original completo recuperável nesta etapa.
- Portanto não criar falsos anexos, mensagens, SHA de transcrição, links permanentes ou datas de aceite de INT-09 que não estejam efetivamente verificadas.

## 3. O que falta confrontar ao recuperar os textos

### INT-08 — Foco P0-GAP-015..018

1. Comparar a proposta original com o ciclo de vida de importação/exportação/migração (prévia, validação, lote, suspensão, retomada, aceite e encerramento), conferindo estados e exceções sem inventar 41 nomes de seção.
2. Verificar autoridade por empresa/unidade/conta/conexão, origem, destino, finalidade, entidade/campo/lote/registro e período, preservando exclusões.
3. Separar os contratos de INT-08 dos serviços de autoatendimento da [IN-118](../IN-118-central-backup-portabilidade-dados.md) e da troca técnica de vínculo [IN-122](../IN-122-multiplos-sites-contas-crm-cortesias.md).
4. Conferir formatos/arquivos/compatibilidade e fronteiras de escrita, preservando CSV/XLSX como essenciais da IN-118 e JSON conforme contrato autorizado.
5. Associar somente as cláusulas comprovadas a TR-INT08-001..005, CT aplicáveis e HT-03/07/13/17; novos TR dependem de fonte literal e decisão.

### INT-09 — Foco P0-GAP-019..022

1. Conferir propostas originais de estados **separados** para modelo, instância, etapa, comando, autorização/aprovação, compensação e encerramento.
2. Verificar condições/paralelismo/convergência, eventos e prazos, limite de iterações, critérios de parada e dependências oficiais.
3. Verificar comandos externos, resultados inconclusivos, contingência com provedores alternativos homologados/compatíveis/autorizados e não duplicação de escritas.
4. Verificar irreversibilidade, reversão/compensação por domínio, cancelamento técnico e confirmação oficial de negócio separada.
5. Associar apenas as cláusulas comprovadas a TR-INT09-001..006, CT aplicáveis e HT-08/09/10/11/12/15/18.

## 4. Regra de classificação da evidência

| Classe documental | Exemplo atual | Pode resolver cotejo literal? |
|---|---|---|
| A — Mensagem original integral e versão identificável | **Não obtida** para propostas INT-08 e INT-09 | Sim, após revisão cláusula a cláusula |
| B — Cópia integral fiel de origem identificável, versão e integridade verificáveis | **Não obtida** | Potencialmente, após comparar procedência |
| C — Texto de aceite preservado sem origem imutável | Registros P0-ORIGEM de outras INTs | Não sozinho |
| D — Síntese de proposta ou resultado do índice de conversas | INT-08.md, INT-09.md, apontamentos sobre 41/39 seções | **Não** |
| E — Documento correlato aprovado de outro assunto | IN-118/122/123/124/148/149, CT-02/03/04 | Confirma apenas a decisão correlata, não a redação original da INT |

**Decisão vigente:** **P0-DOC-01 e P0-TR-01 continuam abertos.** A descoberta de localizadores aumenta a capacidade de procurar a origem, mas não autoriza “concluído”, redação de supostas transcrições nem novas funcionalidades. Nenhuma programação iniciada.
