# CRM UX F3-138 — Jornada comercial integrada navegável (offline)

**Data:** 12/11/2026 · **Ambiente:** DEV, HTML local, PHPUnit e Chromium descartável, sem backend ou dados de clientes.

## Experiência adicionada

O menu principal de protótipos passa de oito para **nove** experiências. A nona página está em \`prototypes/f3-138-commercial-journey/index.html\` e oferece dois cenários predefinidos:

- **Fluxo sem conflitos:** lead inventado com score 90/quente, vendedor local de demonstração disponível, oportunidade declarada ganha, proposta declarada aceita, atividade declarada concluída e orçamento fictício de R$ 190,00.
- **Fluxo com quatro conflitos:** não há vendedor de laboratório elegível, a oportunidade é declarada ganha sem aceite alegado da versão atual da proposta, o desconto hipotético excede o teto de revisão gerencial e a atividade fictícia está vencida. Orçamento ilustrativo de R$ 120,00.

**Nos dois casos**, a proposta não pode ser enviada nem cobrada, nenhum lead ou tarefa pode ser criado e o valor de receita comprovada é **R$ 0,00**. O indicador de zero avisos significa somente coerência entre os exemplos inventados, nunca autorização.

## Garantia de paridade

Os exemplos e seus resultados esperados estão em um JSON literal fechado na própria página; não há consultas nem JavaScript que executa o PHP do servidor. O teste \`OfflineCommercialJourneyParityTest\` extrai as duas entradas reais do HTML e as executa com \`SyntheticCommercialJourneyInspector\`, comparando pontuação, atribuição, status, subtotal, bloqueios, avisos e receita. Assim o protótipo falha na CI se for exibido resultado incompatível com o código PHP.

A nova rotina \`scripts/ci/check-offline-commercial-journey-browser.php\` usa Chromium **de verdade** para selecionar os dois cenários em 1100 e 320 px, verificar foco de teclado, \`aria-pressed\`, estados de cinco módulos, valores em BRL, avisos, receitas nulas, direitos negados, navegação local e ausência de overflow. \`ci-f0.yml\`, \`OfflinePrototypeHubTest\` e o smoke do índice foram atualizados para os nove destinos reais.

## Escopo e restrições

Navegação 100% offline e sem comunicação de rede, formulário, armazenamento, iframe externo, contas ou scripts de terceiros. A interface não é painel CRM conectado. Não há SQL, IAM/ALLOW, RLS operacional, notificação, exportação, assinatura, links, cobrança, clientes, custos, VPS, main nem produção. A autorização de qualquer ação real continua bloqueada pelos gates independentes CORE/SEG/DBA/LGPD e validação de políticas por tenant.
