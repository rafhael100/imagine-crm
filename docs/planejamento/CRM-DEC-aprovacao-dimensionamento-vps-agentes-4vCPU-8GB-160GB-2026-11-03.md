# CRM-DEC — Referência de dimensionamento da VPS dos agentes técnicos Imagine

**Data:** 03/11/2026.
**Manifestação do responsável:** “ok”, em resposta à proposta expressa de aprovação de **4 vCPU, 8 GB de RAM e aproximadamente 160 GB SSD** como referência inicial para a VPS exclusiva dos agentes técnicos, sem decidir fornecedor ou compra.
**Status:** **APROVADA COMO REFERÊNCIA DE PLANEJAMENTO**, não como SKU contratado, capacidade homologada ou autorização de programação.

## Deliberação aprovada

- Dimensionamento inicial de estudo: **4 vCPU / 8 GB RAM / ~160 GB SSD** para **sete agentes técnicos especializados, Supervisor Técnico e orquestrador técnico**, usando modelos externos via API, sem GPU ou inferência local pesada como premissa atual.
- Preservar **Opção B**: VPS/servidor de agentes técnicos **independente** da VPS do **n8n**. Não adicionar uma VPS para cada agente; agentes funcionais e motores particulares de CRM, Portal, Sites, Performance AI e Universidade permanecem sujeitos às fronteiras de cada produto.
- Avaliar reaproveitamento da VPS do n8n conforme inventário, cujo porte histórico **6 vCPU / 16 GB RAM / 200 GB NVMe** não está confirmado como disponibilidade atual ou capacidade ociosa.
- Não confundir a referência **4/8/160** com ofertas comerciais anteriores de VPS **4 vCPU/6 GB/100 GB**: devem ser cotados recursos equivalentes e IOPS reais, especialmente para ferramentas de navegador, documentos, filas e picos.
- Dimensionar segurança, isolamento de credenciais, backup, recuperação, monitoramento independente, limites de concorrência e custos de modelos por API em orçamento separado. Crescer com métricas, não por número de agentes cadastrados.

## Pendências preservadas

- Contrato, provedor, região, mensalidade, CPU compartilhada/dedicada, capacidade IOPS e oferta comercial específica.
- Confirmar VPS n8n realmente ativa e sua utilização, proteção, licença, banco/workflows e despesas recorrentes.
- Decidir condições de licenciamento do n8n ao integrar com funcionalidades de automação configuráveis por clientes do CRM, sem presumir uso gratuito permitido.
- Validar cargas reais na etapa autorizada: simultaneidade, consumo de CPU/RAM, latência, falhas, retenção, navegador, documentos e custos de tokens.
- RTO/RPO e arquitetura de contingência, critérios de segurança e homologação de produção.
- ARQ-01 segue **P0 parcial** e demais gates permanecem abertos.

**Não autorizado:** contratação de VPS, mudança no n8n, provisionamento, teste real, implantação, acesso operacional ou programação. A decisão vale **somente para planejamento**.

Referências: [Estudo de dimensionamento](ARQ-01-dimensionamento-vps-agentes-tecnicos.md) e [Arquitetura transversal aprovada](CRM-DEC-arquitetura-central-agentes-n8n-ecossistema-imagine-2026-11-03.md).
