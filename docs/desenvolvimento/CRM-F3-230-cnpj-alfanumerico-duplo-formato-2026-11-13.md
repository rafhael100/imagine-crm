# F3-230 — CNPJ alfanumérico e legado, validação estrutural no Core

A Receita Federal adotou a identificação **alfanumérica de 14 posições para novas inscrições em julho de 2026**, preservando o formato apenas numérico dos CNPJs existentes. De acordo com a [Receita Federal](https://www.gov.br/receitafederal/pt-br/assuntos/noticias/2024/outubro/cnpj-tera-letras-e-numeros-a-partir-de-julho-de-2026), os 12 primeiros caracteres podem ser `0-9`/`A-Z`, mas os dois dígitos verificadores continuam numéricos. O módulo 11 usa valores `ord(caractere) - 48` e pesos legados.

O `OfflineCnpjChecksumInspector` recebe somente um candidato em memória, aceita 14 caracteres ASCII compactos ou pontuação canônica `XX.XXX.XXX/XXXX-DD`, normaliza somente letras ASCII maiúsculas e espaços ASCII externos, rejeita caracteres Unicode semelhantes, separadores não canônicos e dígitos verificadores falsos. Para CNPJ numérico, rejeita sequências uniformes conhecidas como inválidas. A saída nunca retorna o número, partes, documento normalizado, hash ou dados pessoais/empresariais; apenas `format_kind`, status e booleanos de avaliação.

O teste oficial de referência é `12.ABC.345/01DE-35`, e o teste numérico `11.222.333/0001-81`. A validação de DV é **apenas estrutural**: nem o CNPJ real existente nem a titularidade, situação cadastral, elegibilidade de trial, o limite um trial por CNPJ, vínculo com tenant, LGPD ou acesso são verificados ou autorizados. As flags `can_register`, `can_reserve_trial`, `can_grant_access`, `can_persist` continuam false.

Não cria rota, banco, migration, chamada à Receita, pagamento, cliente ou VPS. Gates SEG/DBA #27, CORE/LGPD #259 e visual #416 continuam NO-GO. Integrar somente na DEV após diff revisado e CI F0/PG16/Sites/AIA na SHA exata.
