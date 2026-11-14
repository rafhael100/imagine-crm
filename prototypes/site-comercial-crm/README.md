# CRM WEB-P0 — Preparação de homologação comercial privada

**Estado:** protótipo estático aprovado como direção visual, versão de revisão. Não publicado, não operacional e sem autorização para signup, login ou cobrança. A versão de referência está na PR #520 e a tarefa na issue #519.

## Pacote de prévia

- `index.html` — conteúdo e navegação comercial, quatro planos **provisórios**, botões de simulação sem transação.
- `assets/logo-imagine.png` — **binário idêntico** ao logo do Portal Node, Git blob `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77` (200 × 55).
- `tests/test_preview.py` — verificador offline de integridade, navegação, preços provisórios, CSP e ausência de formulários e chamadas de rede.
- CI `ci-crm-commercial-preview.yml` — valida os arquivos da branch de revisão; não efetua deploy.
- Não há dependência de API, banco de dados, pagamento, cookies ou scripts de terceiros.

## Sequência operacional proposta (não executada na VPS)

1. Inspecionar o HTML e o layout com a marca original em desktop e celular. Confirmar a tabela de planos e os textos; preços definitivos dependem de homologação comercial.
2. Validar CI na **HEAD exata** da PR; não considerar testes anteriores como evidência da HEAD atual.
3. Preparar pacote estático com `index.html` e `assets/logo-imagine.png`, vinculando seu SHA à revisão.
4. Fazer a análise de segurança e conformidade. Não confundir a página estática com as rotas do cadastro/OTP/trial reais, cuja liberação continua bloqueada pelos gates #27, #259 e #416.
5. Somente com permissões e configuração operacionais verificadas, publicar **apenas em staging protegido**, com HTTPS e autenticação obrigatória fora do repositório. Evitar indexação (cabeçalho `X-Robots-Tag: noindex, nofollow`) e cache de documentos (`Cache-Control: no-store`); aplicar `Content-Security-Policy` e `X-Content-Type-Options: nosniff` no vhost.
6. Verificar na VPS real: visitante anônimo recebe **401** sem revelar conteúdo; usuário autorizado recebe **200** com HTTPS; logo carrega localmente; links/menus funcionam; sem requisições externas, formulário ou persistência.
7. Registrar evidências dos testes em desktop e mobile, assinatura da revisão e link interno para validação do responsável.

## Restrições explícitas

Não incluir dados reais, endpoints autenticados de CRM, OTP, CNPJ, token, gateway, credenciais, notificações, deploy público, DNS, alteração em `main` ou custo adicional. A página demonstra ações de interesse; **nenhuma contratação é realizada**.

## Saída de homologação

Separar dois aceites:
- **Aceite visual/comercial da prévia** — layout, mensagens e comparação de planos, sem operação.
- **Aceite técnico de cadastro/contratação** — fluxo real de trial, IAM, segurança, dados e pagamentos, a ser avaliado em etapa própria.

A aprovação visual do protótipo não substitui os gates técnicos de runtime.
