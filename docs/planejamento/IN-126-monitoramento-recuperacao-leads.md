# IN-126 — Monitoramento e Recuperação de Leads

**Status:** APROVADA funcionalmente pelo responsável do projeto em 01/11/2026.  
**Fase:** planejamento/documentação; não autoriza iniciar programação.  
**Escopo:** formulários Imagine, APIs principal e de contingência, filas persistentes e entrega ao CRM Imagine.  
**Dependências:** IN-123 (painel), IN-124 (integração e APIs redundantes), IN-125 (gestão de formulários).

## Objetivo
Detectar tempestivamente falhas na recepção e entrega de leads, permitir recuperação automática sempre que possível e fornecer mecanismos auditáveis de intervenção autorizada quando necessário.

## Funcionalidades aprovadas
1. **Reenvio automático:** tentativas progressivas de entrega ao CRM para envios recebidos e armazenados duravelmente nas APIs, com tratamento de falhas temporárias e de respostas incertas, sem exigir intervenção nas situações comuns.
2. **Alertas administrativos:** avisar a equipe Imagine sobre indisponibilidade das APIs, integrações com falha, pendências acumuladas, problemas persistentes ou risco de expiração de retenção.
3. **Fila de exceções:** separar e identificar envios que não puderem ser entregues após as tentativas automáticas, permitindo investigação e recuperação **dentro da política de retenção a definir**.
4. **Reprocessamento manual:** funcionários Imagine autorizados poderão disparar nova tentativa sobre envios pendentes ou em exceção, respeitando idempotência, autorização e auditoria.
5. **Painel de monitoramento:** apresentar quantidades e situação dos envios **recebidos**, **entregues**, **pendentes** e **com erro**, com associação ao site, formulário, conta CRM vinculada e intervalos de tempo, respeitando segregação e permissões.
6. **Auditoria:** registrar as tentativas, seus resultados, alertas, reprocessamentos, intervenções e seus responsáveis, evitando incluir dados pessoais desnecessários em logs.
7. **Automação como padrão:** mecanismos de detecção e recuperação automáticas devem resolver falhas recuperáveis; atuação humana fica reservada a falhas persistentes, exceções e casos sensíveis.

## Arquitetura e garantias mantidas (IN-124)
- Os sites/landing pages Imagine **não possuem banco de dados próprio**.
- Há uma **API principal** e uma **API de contingência** em infraestruturas independentes, cada uma com armazenamento durável independente, e preferencialmente em provedores diferentes conforme viabilidade.
- A área administrativa do **Portal Imagine** exibe monitoramento, alertas e ações operacionais; ela **não deve ser ponto único de falha** no recebimento.
- Só confirmar sucesso ao visitante após uma das rotas confirmar persistência durável do formulário.
- Usar identificador único por envio e processamento idempotente para evitar duplicação de leads na reconciliação entre filas, retentativas e reprocessamentos.
- Não alegar perda zero absoluta: falhas simultâneas ou envios jamais recebidos por qualquer API não podem ser recuperados apenas pela fila.

## Requisitos operacionais a especificar antes da implementação
- Política de tentativas (intervalos progressivos, tempo máximo, classificação de erros transitórios versus permanentes, limite de concorrência).
- Retenção e descarte seguro de envios temporários, pendências e exceções; proteção contra remoção prematura antes de aviso e oportunidade de recuperação.
- Limiares de alertas, canais e escalonamento, redução de ruído e acompanhamento de incidentes.
- Indicadores de backlog, idade do envio mais antigo, latência, erros por formulário/site/rota, consumo de capacidade e sucesso na entrega.
- Controles de acesso, autenticação reforçada para ações críticas, confirmação para reprocessamento manual e trilha de auditoria resistente a alteração indevida.
- Tratamento de payloads sensíveis e dados pessoais, criptografia em trânsito e em repouso, segregação por conta/cliente e conformidade com LGPD.
- Como tratar alterações de vínculo CRM ocorridas entre a captura e o reprocessamento (não redirecionar automaticamente leads antigos para outra conta).
- Monitoramento independente da disponibilidade do Portal Imagine, diagnóstico quando a integração estiver parcialmente indisponível e testes de contingência.
- Dimensionamento de recursos/custos de APIs, filas e retenção em conjunto com os servidores a selecionar.

## Decisões aprovadas versus pendências
**Aprovado:** reenvio automático progressivo, alertas administrativos, fila de exceções, reprocessamento manual autorizado, painel de recebidos/entregues/pendentes/erros e auditoria; recuperação automática preferencial.  
**Pendente:** prazos numéricos de retenção, intervalos de tentativas, métricas/SLA, capacidade e valores de hospedagem. Estes não foram aprovados implicitamente.  
**Programação:** não iniciada.
