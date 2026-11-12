# CRM UX F3-104 — Painel offline do ciclo de vida

**Data:** 11/11/2026. **Situação:** laboratório puramente local, sem dados de assinaturas reais.

## Entrega funcional

A partir dos módulos de tempo integrados na DEV (F3-102 trial/60 dias e F3-103 cancelamento/120 dias), acrescenta-se um painel interativo em `prototypes/f3-104-retention-lifecycle/index.html`, acessível a partir do quarto cartão do hub offline.

A interface mostra **nove cenários fixos** e verificáveis (cinco relacionados ao trial e quatro ao cancelamento). Para cada situação, apresenta fase, limites em UTC, contagem regressiva de dias arredondada para cima e segundos restantes. Existem opções de teclado, rótulos de acessibilidade, anúncio de alteração via `aria-live` e grade responsiva para viewport de 320 px. A troca de cenário é visual: não há formulário, API, URL externa, upload, storage, autenticação ou relógio atual.

### Validação executável

- `OfflineRetentionLifecycleParityTest` extrai o conjunto JSON presente no HTML e compara os nove registros **contra o código PHP real** `SyntheticTrialRetentionTimeline` e `SyntheticCancellationRetentionTimeline`, inclusive segundos extremos e flags de negação.
- `check-offline-retention-lifecycle-browser.php` opera **Chromium real**, em 1100 px e 320 px, interage com os nove botões e exige igualdade de fase, dias, segundos e tipo, foco preservado, uma seleção por vez, ausência de rolagem horizontal, proibição visível de operações e aviso explícito de dados fictícios.
- `ci-f0.yml` torna a prova do navegador uma etapa obrigatória do pipeline.
- Atualizados contratos PHPUnit e Chromium do hub para quatro rotas, preservando os três protótipos anteriores.

## Limites e dependências

**A janela de 60 dias pós-trial e a de 120 dias após cancelamento são candidatas a apresentação, não políticas jurídicas finais.** Os testes não constituem aprovação para exclusão, cobrança, reativação, confirmação de CNPJ, concessão de acesso ou retenção de backups. Para operações reais, CORE/IAM, SEG, DBA, LGPD e liberação de produto seguem NO-GO.

O draft UX [#255](https://github.com/rafhael100/imagine-crm/pull/255) mantém um hub alternativo com seis links e requer revisão independente. Esta entrega adiciona **um** quarto link à DEV atual, sem mesclar #255 nem impedir futura reconciliação do hub após autorização.
