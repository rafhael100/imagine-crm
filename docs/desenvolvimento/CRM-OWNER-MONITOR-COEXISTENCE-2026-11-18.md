# Convivência privada: rascunhos owner e monitor n8n

Este recorte acrescenta uma prova sintética da instalação combinada à
[PR #660](https://github.com/rafhael100/imagine-crm/pull/660). Não cria um perfil
operacional de backup, não ativa uma instalação restaurada e não altera os
contratos de autorização dos produtos. DRAFT / NO-MERGE / NO-DEPLOY.

## Problema tratado

As jornadas owner e monitor haviam passado em laboratórios separados. Isso não
comprovava que ambas funcionariam com os mesmos dados, sessão e composição, nem
que a recuperação preservaria simultaneamente o addon owner e os objetos S2S.
O perfil owner contém 27 tabelas; o monitor isolado contém 33. A união tem 35.
Nenhum PASS de uma dessas fontes substitui a execução da união na HEAD atual.

O laboratório owner já usa o Agents original pinado, mTLS e sessões humanas com
MFA. O novo estágio reutiliza essa instalação depois das provas HTTP,
recuperação27 e Chromium existentes, antes da desativação e do cleanup. Os
estágios anteriores continuam obrigatórios.

## Composição e preparação

A admissão original continua conferindo evento, repositório, runner hospedado,
branch/base permitidas, HEAD/imagem e ancestrais. Os pins de Agents P65, P67 e
Central permanecem os mesmos. A prova não obtém autoridade operacional.

O estágio combinado instala os quatro SQLs opcionais já revisados, na ordem
`install-candidate`, `outbound-candidate`, `execution-feedback-candidate` e
`execution-monitor-candidate`. Toda recriação do app conserva o overlay owner.
A ativação do monitor deve alterar apenas sua flag, preservando imagem,
ambiente restante, redes e os dois mounts restritos existentes do owner.

Os certificados e chaves do owner permanecem montados somente nos caminhos
previstos, em modo somente leitura. O monitor não recebe credenciais de serviço
adicionais. O laboratório isolado do monitor continua proibindo esses mounts;
seu guard não é afrouxado para acomodar o laboratório combinado.

Os dois planos nativos necessários são criados antes do seed de apresentação
do monitor. A origem deve conter os nove leads produzidos pelos estágios owner;
os dois planos levam a onze, ainda dentro da quota. O seed original acrescenta
seus 56 eventos de clientA e um de clientB depois disso. Não há ajuste de quota,
remoção de dados ou repetição de seed de identidade para fazer a prova passar.

O ensaio preserva os fatores emitidos e usa índices de recuperação distintos.
Os estágios anteriores consomem clientA `[0]`, `[1]` e `[4]`; a sessão HTTP
combinada usa `[2]`, e o Chromium combinado usa `[3]`. Não há regeneração de MFA
ou troca de senha. O member sintético usa seus próprios códigos reservados.

## O que é provado na aplicação

O owner autenticado cria dois rascunhos por chamadas reais ao Agents pinado:
um confirmado e outro com resposta perdida após o commit remoto. O segundo
deve permanecer localmente pendente até a reconciliação autorizada.

Na mesma sessão, o owner consulta o rascunho e navega pela lista e pelo detalhe
do monitor. O member pode consultar o monitor da própria empresa e continua
sem acesso ao rascunho owner. Objetos de outra empresa permanecem inacessíveis.
O Chromium segue links reais de owner, monitor, detalhe, idiomas PT/EN/ES e
retorno à lista, verificando os parâmetros permitidos e as respostas.

Existem duas janelas de efeitos, verificadas separadamente:

| Janela | Efeito permitido e exigido |
|---|---|
| Consulta owner | Uma auditoria `agents.draft.read` para a consulta esperada; fingerprints por ID dos eventos preexistentes iguais e demais tabelas de negócio, addon e S2S preservadas. |
| Navegação somente no monitor | Fingerprints iguais de todas as tabelas de negócio, addon e S2S explicitamente enumeradas; nenhuma auditoria owner adicional. |

Os efeitos de autenticação no IAM não são apresentados como imutabilidade de
toda a instalação durante o login. Na recuperação, por sua vez, os hashes
abrangem integralmente as 35 tabelas, inclusive IAM e auditorias, no snapshot.

Depois da desativação do owner, a prova confere que o monitor permanece opt-in
e privado. Uma nova sessão member com fator ainda não utilizado consulta a
lista e o detalhe próprios; o evento estrangeiro é recusado e o rascunho real
continua indisponível com a função desligada. A janela de leitura continua
sujeita aos fingerprints. O cleanup original encerra o projeto descartável.

## Inventário fechado da recuperação

O inventário esperado é constante no harness. Ele não é construído a partir
dos objetos encontrados no banco.

| Conjunto | Conteúdo |
|---|---|
| `public` | As 25 tabelas base, mais `crm_private_owner_draft_grants` e `crm_private_owner_draft_intents`. |
| `crm_auto_service` | `grants`, `nonces`, `audit`, `outbound_grants`, `outbox`, `execution_read_grants`, `execution_observations` e `execution_projections`. |
| Sequência | Somente `crm_auto_service.audit_id_seq`. |
| Funções | As quatro funções S2S/P22, quatro de feedback e `read_execution_monitor(uuid)`, com as nove assinaturas explícitas no harness. |
| Roles restauradas | As cinco roles base, `crm_auto_reader_owner`, `crm_auto_transport`, `crm_auto_sender` e `crm_auto_execution_reconciler`. |

O contrato exige owners restritos, FORCE RLS, policies, grants de coluna do
owner, ausência de acesso direto do app às tabelas S2S e limites das funções.
O leitor do monitor mantém somente o EXECUTE autorizado ao app. As roles do
clone não têm LOGIN, senha, privilégios elevados, herança ou memberships.

O detector genérico de backup e o perfil owner27 devem recusar explicitamente
o conjunto35 tanto na origem quanto na restauração. A prova não modifica esses
detectores nem fornece um caminho alternativo de aceitação operacional.

## Snapshot e contraprova temporal

Um único snapshot exportado alimenta inventário, catálogo, segurança, hashes
das 35 tabelas, escopos por tenant, modelos do monitor e dump. Enquanto ele
permanece aberto, um GET owner autorizado reconcilia o rascunho pendente na
origem viva. O resultado exigido é:

1. A intenção está pendente no snapshot.
2. A consulta real confirma a intenção na origem viva, sem recriar o rascunho
   remoto nem alterar o planejamento nativo.
3. O snapshot continua vendo todos os dados anteriores, apesar do commit.
4. A restauração do dump conserva a intenção pendente e os hashes das 35
   tabelas capturados antes da reconciliação.

Sequências não são congeladas por esse snapshot. Por isso, a prova compara
explicitamente estado e parâmetros da sequência antes/depois do dump e no
clone, exigindo estabilidade nessa janela controlada.

O arquivo é mantido em memória, criptografado e descriptografado pelo utilitário
existente. Uma adulteração do ciphertext deve ser recusada sem retornar texto
em claro. Dumps, chaves, credenciais e arquivos de sessão não entram nos
artefatos da CI.

## Fidelidade e inércia do clone

A restauração ocorre em um novo PostgreSQL 16, com rede desabilitada, sem porta
publicada e com dados temporários. Não existe CRM conectado a esse destino.
Os owners originais são preservados; o dump não é restaurado com `--no-owner`.

O catálogo de ambos os schemas usa o módulo compartilhado
`private_crm_backup_catalog.py`. Os vínculos entre constraints e manifestos de
segurança são exatos. A única equivalência permitida continua sendo a regra
fechada dos quinze pares PostgreSQL já exercitada pelo owner27; todas as
demais definições, colunas, collations e propriedades de segurança devem
permanecer iguais. As 18 negativas do catálogo e as três de segurança são
reutilizadas sobre os manifestos realmente capturados.

Os modelos do leitor restaurado devem preservar eventos, estados e histórico
por empresa. Apenas `generated_at`, calculado a cada consulta, é excluído dessa
comparação de modelos. Isso não remove nenhum campo persistido dos hashes ou
dos manifestos de segurança. A ausência de tenant e o acesso estrangeiro
continuam sujeitos às recusas esperadas.

As negativas SQL incluem os limites do addon owner, acesso direto do app ao
outbox, acesso do reader owner às intenções owner e imutabilidade do histórico
de execução. Após essas recusas, hashes das tabelas e estado da sequência
continuam iguais. O contêiner de restauração deve ser removido ao concluir.

## Evidência e limites

Os resultados dinâmicos pertencem à HEAD exata que executar a CI e são
registrados na PR. Este documento descreve o contrato esperado e não antecipa
um PASS. O relatório distingue hashes brutos e equivalências; não apresenta
uma reescrita aceita como igualdade textual.

As chamadas owner ao Agents são reais dentro do laboratório pinado. Os estados
do monitor nesta prova continuam sendo as fixtures de apresentação declaradas
da #654, com **zero novas execuções n8n**. A prova nativa de execução do Agents
é uma evidência separada e não é inferida dessas telas.

O ensaio cobre fidelidade de recuperação e convivência sintética, sem afirmar
restauração de autoridade atual, reutilização de sessão, recuperação distribuída
CRM/Agents ou aptidão para religar o clone. Aceite humano, infraestrutura,
insumos externos e autorização operacional continuam etapas próprias.
