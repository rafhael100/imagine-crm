# CRM F3-202 — revisão sintética de mapeamento de colunas em importação

**Data:** 12/11/2026. **Base:** [issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [IN-118](../planejamento/IN-118-central-backup-portabilidade-dados.md) e CRM-REV-03. **Estágio:** biblioteca PHP pura, sem leitura de CSV/XLSX ou dados reais.

## Entrega
`SyntheticImportColumnMappingInspector` recebe exclusivamente **metadados fictícios de posições de coluna**, não o cabeçalho, conteúdo de células, e-mails, telefones, nomes, IDs, caminhos, tokens ou bytes de arquivo. Uma requisição contém `entity_kind` (`leads`/`contacts`), até 20 linhas **declaradas**, até 12 colunas posicionais (índices 0..N-1), contagem de campos não vazios por coluna, contagem fictícia de suspeitas de fórmula, destino de mapeamento, estado do destino, contagem de candidatas a duplicidade e alegação de revisão.

Destinos **ilustrativos**, não um schema aprovado: `name`, `email`, `phone`, `origin`, `consent`, `notes`, `status`, ou `ignore`. Não impõe nome + contato como requisito universal. Detecta colisões quando várias colunas apontam para um único destino; alerta sobre fórmulas, campos ignorados, evidência de consentimento não comprovada, duplicidades, destino existente/desconhecido e falta de alegação de revisão.

Mesmo que os campos coincidam, a prévia **não comprova consentimento** de uma pessoa nem o direito de reutilizá-lo em outra empresa. A saída contém apenas contagens e códigos fixos; não revela linhas nem cabeçalhos.

## Segurança e testes
Rejeita entradas com campos extras/PII, listas esparsas, posições repetidas/inválidas, destinos desconhecidos, tipos ambíguos, contagens maiores do que o declarado, limites excedidos, estados não enumerados. Resultado determinístico e sem mutação. Toda verificação `*_verified=false`, `authorized=false`, `can_import/can_merge/can_assign/can_confirm_consent/can_write_file/can_persist=false`.

Testes PHPUnit abrangem colunas duplicadas, consentimentos, fórmulas, destinos existentes e desconhecidos, limite 12/20, arquivos vazios, contagem de candidatos, contratos rígidos e zero autoridade. Matriz comercial **44→45** prévias deny-only e selo de inexistência de novas rotas. Só integrar na DEV com 4 pipelines obrigatórios (F0/PG-RLS/Sites/Agentes) verdes na HEAD exata.

**Fora do escopo:** leitura efetiva, detecção de encoding, parser CSV/XLSX, transformação destrutiva, matching de pessoas, consentimento real, persistência, importação, download, endpoints, fila, credenciais, tenant, acesso público, main e produção. Direitos/segurança devem ser aprovados separadamente (issues #27 e #259).
