# Identidade visual Imagine no CRM — contrato local v1

Fonte aprovada: Imagine Portal original Node, identidade documentada na [PR #589 do imagine-portal](https://github.com/rafhael100/imagine-portal/pull/589), **ainda não necessariamente integrada ao Portal**. Tokens refletem `design-system/tokens.css` dessa PR, sem dependência runtime do Portal. Referência histórica: `imagine-portal/portal.css`.

## Aplicação F3-195 (issue #416)
Os aliases `--imagine-*` são copiados para os estilos inline das três superfícies iniciais (`prototypes/index.html`, `prototypes/s01-prot-01/index.html` e `prototypes/f3-59-offline-kanban/index.html`) porque a política CSP offline não permite importar folhas externas nem fazer requisições. O arquivo `tokens.css` é o contrato local para verificação de consistência, não uma importação pelo navegador.

Laranja #F9941D em ações com texto grafite; texto pequeno sobre fundos claros usa laranja escuro #9B4D00; foco #B75C00; fundo #F7F7F8; navegação/cabeçalhos brancos e bordas #E8E8E8. Cores semânticas de confirmação/erro continuam próprias. No protótipo principal a barra lateral fica branca e o item ativo é laranja suave.

**Limitações atuais:** estas são demonstrações offline e não a interface operacional homologada. As demais dez telas de protótipos independentes ainda precisam passar pelo mesmo inventário visual. O arquivo de logotipo original `assets/logo-imagine.png` do Portal foi copiado **byte a byte** para `prototypes/assets/logo-imagine.png`, conservando o SHA do blob Git `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77`. Como o protótipo principal é aberto por arquivo local e os drivers Chromium geram cópias temporárias, o PNG é incorporado ali por `data:image/png;base64` dentro da CSP já permitida `img-src data:`, sem rede. O logotipo real é exibido no menu principal; hub e Kanban passam a referenciar o mesmo PNG oficial em caminhos relativos locais, na revisão filha do PR #418. O Kanban permanece menor que 50 KB; somente o HTML descartável do teste Chromium incorpora o PNG validado via `data:` para funcionar fora da pasta do repositório. Nenhum SSO, coleta, dados de cliente, backend, migrations, acesso real, implantação ou gasto alterado.

## Aceite
Testes estáticos de tokens/CSP e scripts Chromium em 1100 e 320 px inspecionam estilos computados, navegação, foco e ausência de overflow. Esta PR é **DRAFT, sem merge/deploy automático** pela própria issue #416; os riscos #27 e #259 seguem NO-GO.
