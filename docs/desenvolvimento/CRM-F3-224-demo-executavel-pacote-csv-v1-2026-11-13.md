# F3-224 — Prova executável offline do pacote CSV V1

O comando `php scripts/ci/emit-portable-csv-review-demo.php` executa **código Core real** de F3-210..223 sobre exatamente dois registros fictícios embutidos em memória. Produz apenas uma linha JSON V1 de `PortableCsvReviewBundleV1` contendo contadores e avisos, com `can_import/can_export/can_persist=false`. A amostra contém repetição após omissão de coluna e um marcador bidi em coluna **não selecionada**. Com isso, as duas fontes de risco são verificadas em conjunto usando o mesmo caminho que uma interface offline poderia consumir.

O CLI **recusa todos os argumentos**, não aceita caminho/arquivo CSV de cliente, não usa .env, rede, banco, permissões, tenant, download, API nem escrita em disco. Ele não processa dados reais; o JSON não ecoa nem os valores fictícios. Não é homologação operacional, não produz backup e não valida Excel/Sheets/LibreOffice.

`PortableCsvReviewDemoCliTest.php` realmente executa PHP em subprocesso usando `PHP_BINARY`, verifica o protocolo e as negações, o tamanho e contadores, inspeciona não vazamento e confirma rejeição de argumentos. Requisitos da CI na SHA exata: F0, PostgreSQL/RLS, Sites e Agents. Gates #27 SEG/DBA, #259 CORE/LGPD, #416 visual e IN-118 seguem NO-GO; sem deploy, produção ou `main`.

**Integração da dependência:** F3-223 / PR #461 já integrada somente à DEV; esta entrega agora mira diretamente a HEAD DEV. A execução de CI deve ser verificada novamente sobre a SHA atual.
