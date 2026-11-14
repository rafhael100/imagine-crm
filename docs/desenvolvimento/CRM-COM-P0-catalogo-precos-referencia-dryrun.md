# CRM COM-P0 — Catálogo comercial apenas informativo (DRAFT / NO-GO)

**Origem:** CRM-REV-02 (03/11/2026), documentação de planejamento. Esta mudança não modifica a tabela de preços final, não aprova descontos, não determina elegibilidade de cortesia, não cria rota ou checkout.

## Implementado

`app/Core/Plans/CommercialPlanReferenceCatalog.php` centraliza quatro **valores mensais de referência**, com moeda BRL e cálculos em centavos exatos, sem usar ponto flutuante. A API `plans()` só retorna descrições; `preview(codigo, ciclos)` calcula exclusivamente de 1 a 12 ciclos de **referência aritmética**, sem validar contratação ou prometer duração de contrato.

| Código | Mensalidade de referência | Usuários de referência | Anexos |
| --- | ---: | ---: | ---: |
| basico | R$ 69,00 | 1 | 1 GB |
| essencial | R$ 89,00 | 2 | 5 GB |
| profissional | R$ 149,00 | 5 | 20 GB |
| empresarial | R$ 299,00 | 10 | 50 GB |

As franquias são somente rótulos da documentação, não cálculos de quota. **Todos os números estão sujeitos à revisão comercial**. Profissional/Empresarial permanecem referências não homologadas. Desconto para Pix Automático, pró-rata, cobrança, assinaturas, franquias vinculantes, NF, corte por inadimplência e entitlement não integram esta prova.

Todo retorno indica `informational_only=true`, `price_homologated=false` e `can_checkout=false`. O retorno de cotação também marca `can_charge=false` e `can_activate=false`. Um código desconhecido, alteração de caixa/whitespace ou ciclo fora de 1–12 é recusado sem ecoar entrada.

## Gates para a próxima fase de contratação real

1. A direção comercial deve homologar preços, usuários incluídos, cotas por plano e benefício do Básico, sem confundir trial de 14 dias.
2. Catálogo oficial deverá possuir versão e vigência com aceite explícito, fonte confiável do preço e vinculação da cotação/contrato; **nunca** confiar em preço enviado pelo navegador.
3. Escolha do plano não confere direito de uso: requer identidade e CNPJ verificados, consentimento/contrato, cotação final, cobrança/idempotência, confirmação por gateway, conciliação e emissor fiscal homologados.
4. Após essas provas, implementar downgrade/upgrade, pró-rata, cancelamento, retenção, reversão e auditoria sem efeitos inconsistentes.

## Evidência

Testes de unidade `CommercialPlanReferenceCatalogTest` confirmam tabela de referência, aritmética, entradas negadas e ausência de métodos que causariam cobrança ou ativação. F0 CI executa esses testes. Não há dados de pessoas, SQL, provider, rede, migração ou alteração em `main`.

**Estado:** DRAFT / NO-GO para checkout, trial real, deploy ou preços públicos finais. Este pacote é um passo de controle de divergências comerciais, não uma contratação funcional.
