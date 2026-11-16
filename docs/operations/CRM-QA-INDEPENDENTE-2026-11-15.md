# CRM QA INDEPENDENTE — Revisão do candidato privado (15/11/2026)

> Escopo: revisão independente de **código, logs de GitHub Actions e seis imagens Chromium**.  
> Não é parecer de segurança independente SEG/DBA, auditoria formal LGPD, pentest ou teste autenticado contra a VPS.
> Não foram utilizados dados pessoais, credenciais, endpoints operacionais externos ou cobranças.

## Repositório / inventário temporal

- PR candidata: [#614](https://github.com/rafhael100/imagine-crm/pull/614), DRAFT; base DEV, sem merge em `main`.
- HEAD auditada antes desta PR de QA: `f12d57aa0057387729d6dba89987a94975b67478`.
- **20/20 workflows completed/success**, verificado na HEAD anterior; jornada privada: [GitHub Actions #43772930799](https://github.com/rafhael100/imagine-crm/actions/runs/43772930799).
- Log real da jornada: **879 verificações HTTP, 69 afirmações Chromium, 177 verificações de resiliência e 49 verificações finais de acessos**. Em CI, PostgreSQL16 é descartável, DOM/HTTP percorrem dados fictícios.
- Evidência visual disponível no artifact `private-crm-desktop-mobile` do job `#43772930799`: seis PNGs, marketing desktop/mobile, cliente desktop/mobile e admin desktop/mobile.
- O histórico de PR #614 informa como **última revisão publicada** na VPS `ea5c16725f61fcad837b87e41dd5d86f96680716` (página comercial), enquanto o aplicativo completo inicialmente publicado foi `ea5d320addaeb7ab970bdfa3931d55ddea37afac`. Não há evidência de upgrade da VPS para `f12d57aa`. Não inferir que CI verde = deploy na VPS.
- Host informado: `https://crm-dev.imaginecomunica.com.br/`, com BasicAuth. A auditoria não possui credenciais de revisão nem acesso SSH; **nenhuma operação autenticada na VPS foi reproduzida nesta revisão**.

## Matriz por fluxo

| Módulo | Evidência confirmada | Limite |
|---|---|---|
| Página comercial PT/EN/ES | CI HTTP e browser + screenshots desktop/mobile | A calculadora `Visitas → Leads → Clientes` existe na branch nova, não confirmada na VPS |
| Cadastro/OTP de teste | Teste HTTP transacional; sessão, expiração, cinco tentativas, replay | Código exibido só na sessão privada; sem SMTP/SMS e sem comprovar propriedade de contato real |
| Cadastro de empresa e trial 14 dias | Prova de unicidade CNPJ fictício e prazo; OTP+MFA | Só CNPJs predefinidos e trial de laboratório |
| Login, MFA, recuperação, logout | CI HTTP, Redis, revogação de sessão, 49 verificações de acesso final | Sem execução manual na VPS pela auditoria |
| Leads e Kanban | HTTP cria/edita/move, versionamento, ganho/perdido, 20/21 e PostgreSQL RLS | Dados e identificadores estritamente fictícios |
| Funis e tarefas/automação interna | Dois funis, mudança de etapa, tarefas e regras internas | Sem disparos reais ou provedores externos |
| Relatórios | Dois tenants, funis e conversões isolados, zero quando vazio | Visitas externas não medidas; `won` não significa cliente pagante |
| CSV export/import | Exportação de dados falsos, reimportação, transação atômica, rejeição de linhas ruins | Sem importação de contatos reais |
| Equipe/convite | Convite único, vencimento e duas vagas; owner/member | Sem e-mail real |
| Pedido/pagamento | Pedidos simulados, idempotência, 4 estados; não ativa direito real | Sem Asaas/C6 reais, conciliação externa ou NFS-e |
| Administração | Metadados, suspensão/revoga sessão, reativação com novo login | Sem acesso operacional a dados reais |
| Backup/restore/rollback | CI confere backup criptografado, dados e grants/FORCE RLS; VPS anterior reportada | Não foi repetido na VPS atual a partir desta auditoria |
| Portal/Sites/Agents | F398-F401, F248 em CI de assinatura/NO-GO | Integrações operacionais continuam bloqueadas, não são E2E reais |

## Achados por prioridade

### QA-01 — P1: revisão instalada diferente do código mais recente

A PR #614 explicitamente registra **ausência de deploy das últimas alterações F248–F255**. O workflow F252 exige aprovação privada, revisão de SHA, checks, backup/restore, ingress TLS/BasicAuth, canário F253 e rollback. **Executar runbook somente com operador autorizado**. Não iniciar novo deploy por esta PR.

### QA-02 — P2: formulários com labels sem associação semântica

Na HEAD `f12d57aa`, inspeção estática da view `resources/views/crm-private-journey.blade.php` encontrou **32 `<label>` e 16 sem `for` nem controle filho**, apesar de estarem visualmente próximos de inputs/selects. Isso afeta leitores de tela e a capacidade de clicar no rótulo para focar o campo.

Esta branch de QA corrige os 16 casos usando `for/id` exclusivos, sem alterar nomes dos campos, handlers, banco, CSRF, rotas nem configuração de segurança; adiciona testes unitários para impedir regressões. **Não é uma certificação de acessibilidade**; exige browser e teste com tecnologia assistiva.

### QA-03 — P2: navegação por rolagem horizontal em celular pouco evidente

Capturas Chromium em 390 px mostram apenas uma parte das colunas Kanban e o início da tabela de contas. O CSS contém `.kanban{overflow:auto}` e `.tablewrap{overflow:auto}`, portanto não se pode afirmar que botões estejam inacessíveis; entretanto o gesto/scroll não está sinalizado. Validar com toque real, teclado, leitor de tela, aria-label e instrução de rolagem. Se confirmado, inserir indicador visual responsivo sem criar scroll da página inteira.

### QA-04 — P2: tradução incompleta do aplicativo interno

A página comercial tem PT/EN/ES, mas a view do cliente/admin mantém textos fixos em português (por exemplo, `Contas e acesso` e `Uma visão clara da operação.`). Testar todas as telas com `?lang=en` e `?lang=es`; documentar o contrato de internacionalização antes de declarar CRM integralmente trilíngue.

### QA-05 — P0 para clientes reais: operacionalização não homologada

Permanecem sem testes reais de serviço: entregabilidade OTP, identidade/titularidade do CNPJ, aceite LGPD, Asaas/C6 autenticados, cancelamentos/faturamento, integrações de Portal/Sites/Agents/IA e revisão independente SEG/DBA, CORE/LGPD, UX. Issue de lançamento [#557](https://github.com/rafhael100/imagine-crm/issues/557) segue aberta.

## Próximos testes de aceite na VPS (exigem acesso privado autorizado)

1. Comprovar SHA instalada no runtime e comparação exata com código e scripts da PR.
2. Validar HTTPS, certificado, BasicAuth, MFA e logout, sem publicar fatores/credenciais.
3. Criar empresa fictícia; OTP inválido/vencido/repetido; completar MFA; único trial por CNPJ.
4. Criar leads fictícios A/B, mover cinco estados, testar 20/21, 2/3 funis, tarefas/automação.
5. Alterar cliente/admin, convite e suspensão, confirmar revogação da sessão.
6. Testar CSV com caracteres maliciosos/linhas inválidas, tenant cruzado e transação.
7. Simular pagamentos aprovados, recusados, cancelados sem conceder direitos de produção.
8. Provar isolamentos, cabeçalhos indevidos, CSRF, acesso não autenticado e URLs de APIs bloqueadas.
9. Testar 320/390/768/1440 px e teclado; verificar ações de admin e rolagem Kanban.
10. Repetir backup criptografado, restore isolado, canário F253 e rollback F252 na VPS sob operador.

**Condição de aceite:** resultado em aplicativo e servidor, não apenas CI; evidências com SHA e data; pendências e controles de privacidade encerrados por responsáveis competentes.

**Nenhum endpoint público, conta real, cobrança, merge ou deploy autorizado por esta auditoria.**
