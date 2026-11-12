# CRM UX F3-111 — Revisão visual de versões de proposta

**Data:** 11/11/2026 · **Ambiente:** somente HTML offline, PHP puro e Chrome descartável · **Dependência:** F3-110 na DEV

## Entrega funcional

Novo arquivo de laboratório `prototypes/f3-111-proposal-versions/index.html`, acessível pelo pré-orçamento fictício `f3-109-sales-quote-subtotal`, sem adicionar rotas de produção. O painel apresenta cinco situações descritivas, **todas inventadas**:

1. Versão 1 em rascunho;
2. Versão 1 enviada e visualizada, mas não aceita;
3. Versão 1 rotulada como aceita e versão 2 ainda em rascunho, sem herdar aceite;
4. Versão 1 rotulada como aceita (alegação não autenticada);
5. Nenhuma versão informada.

A tela exibe contagem de versões, versão atual, estado declarado atual, aceites declarados anteriores e visualizações sem aceite, com tabela de códigos seguros em memória. Usa `textContent` e `replaceChildren`, sem conteúdo HTML de usuário ou dados externos. Os botões funcionam com teclado e `aria-pressed`, têm mensagens `aria-live` e grade responsiva a 320 px.

## Paridade executável

`OfflineProposalVersionParityTest` extrai o próprio JSON do protótipo e compara o resumo `expected` de cada amostra **com a classe Core PHP real `SyntheticProposalVersionReview`**, garantindo que visualização não signifique aceite e aceite anterior não seja propagado. Verifica o esquema fechado, flags de negativa e ausência de IDs, CPF/CNPJ, assinatura ou dados comerciais.

`check-offline-proposal-version-browser.php` executa Chromium real em 1100 e 320 px, aciona cada situação, valida contadores e estados, foco/seleção, história, estado vazio, ausência de overflow e proibição incondicional de envio, assinatura, cobrança e persistência. O workflow F0 passa a exigir esse teste.

## Limites

Esta demonstração não verifica identidade do destinatário, versão real da proposta, validade, alçada, desconto, assinatura eletrônica, aceite jurídico, pagamento, revogação de link ou prazo. Todos os direitos operacionais continuam **falsos**, mesmo quando o exemplo contém a palavra `accepted`.

Nenhum endpoint, banco, armazenamento, rede, contato, cliente, `main`, VPS, despesa ou produção. Para operação real, dependências VEN-P05/P06, CORE/SEG/DBA/LGPD e autorização por tenant seguem NO-GO.
