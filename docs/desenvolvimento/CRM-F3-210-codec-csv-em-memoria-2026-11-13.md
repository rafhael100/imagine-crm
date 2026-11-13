# CRM F3-210 — codec CSV reutilizável, estrito e sem acesso a dados de clientes

**Data:** 13/11/2026. **Projeto:** Imagine CRM, IN-118 (F3-02). **Status:** código real de transformação em memória, **não integrado ao fluxo operacional**. Não implementa permissões, importação/exportação de arquivos, download ou atendimento a clientes.

## Valor entregue

`App\Core\Portability\PortableCsvTableCodec` tem duas operações puras independentes de Laravel:

- `decode($csv, $delimiter='comma')`: recebe **somente string em memória**, devolve tabela retangular de strings **não confiáveis**. Suporta delimitador explícito vírgula ou ponto e vírgula, aspas duplas e aspas duplicadas, campos vazios, quebras CRLF/LF entre registros, Unicode UTF-8 e quebras dentro de campos entre aspas. Rejeita BOM inicial até política de versão/encoding ser aprovada, CR solto, aspas incorretas, largura irregular, misturas de CRLF+LF fora das aspas, NUL e UTF-8 inválido.
- `encode($rows, $delimiter='comma')`: recebe tabela retangular e gera **somente uma string canônica com CRLF**; faz escaping de aspas, separador e quebras, cita campos vazios para impedir ambiguidades. **Rejeita**, em vez de "neutralizar", células cuja apresentação em planilhas pode iniciar fórmula: `=`, `+`, `-`, `@`, compatibilidades Unicode, prefixos BOM, whitespace, NBSP, controles invisíveis e alegações de apóstrofo como escape. Isso gera **falsos positivos conscientes**, como `-10`, até uma política aprovada de planilhas. A ausência dessa classe de sinal não prova segurança em Excel, Sheets ou LibreOffice.

Limites voluntariamente conservadores **para uso interno isolado**: entrada/saída até 256 KiB, máximo 100 linhas, 32 colunas, 3.200 células e 4.096 bytes UTF-8/célula. **Esses limites não são quotas comerciais nem parâmetros homologados do produto**. Os erros são mensagens constantes sem eco dos valores. O módulo não lê/grava arquivos nem registra logs, rede, SQL, armazenamento, tenant, IAM ou consentimento.

## QA

`PortableCsvTableCodecTest.php` cobre round-trip e canonicidade, Unicode, delimitadores, CRLF/LF, cabeçalhos implícitos ausentes, multiline, campos vazios, apóstrofos, BOM, injeção de fórmulas e Unicode invisível, formato incorreto, UTF-8 malformado, limites e **200 combinações determinísticas de células fictícias**. A classe ainda não fornece contrato de autorização de negócio ou confirmação de importação.

**Critérios de integração:** quatro pipelines F0/Laravel, PostgreSQL/RLS, Sites e AIA verdes na HEAD exata, diff somente Core puro + PHPUnit + docs e ausência de mudança em rotas, migrations, SQL, perfis de acesso, produção e clientes.

## Pendências essenciais

- Revisão de segurança para o formato produtivo e semântica de escaping de célula em Excel/LibreOffice/Sheets, incluindo mitigação de *formula injection* sem perda de conteúdo legítimo.
- Fluxo real com separação de responsabilidades: autenticação, autorização por tenant/registro/campo, política LGPD, limites e streaming, histórico, proteção de PII, storage privado, auditoria, jobs/retry, idempotência e rollback.
- Integração funcional autorizada à Central IN-118 somente depois das decisões D01–D10 da PR #434 e homologações #27 SEG/DBA, #259 CORE/LGPD, #416 UX; sem implantação antecipada.

**Nota:** `decode` devolve dados não confiáveis. Não conectar a controller nem usar para atualizar banco sem etapa de validação, escopo autenticado e revisão específica. Nenhuma nova permissão de negócio foi criada.
