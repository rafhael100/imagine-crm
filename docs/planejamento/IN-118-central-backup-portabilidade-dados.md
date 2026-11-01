# IN-118 — Central de Backup e Portabilidade de Dados do CRM Imagine

**Estado:** APROVADO funcionalmente pelo responsável do projeto em 01/11/2026.  
**Etapa:** planejamento e documentação; NÃO autoriza implementação.  
**Produto:** CRM Imagine SaaS multiempresa, inclusive o plano Básico ofertado comercialmente e como cortesia em condições elegíveis.

## Objetivo
Assegurar que cada empresa cliente consiga, **por autoatendimento**, fazer cópias de segurança, exportar, importar, recuperar e transportar seus dados entre contas autorizadas, sem depender de suporte para essas operações usuais.

## Regras aprovadas

1. Criar no próprio CRM uma **Central de Backup, Importação e Exportação**.
2. Disponibilizar as funcionalidades essenciais em **todos os planos**, inclusive Básico, sem cobrança adicional por operação: exportação CSV/XLSX, importação CSV/XLSX, backup completo para download, restauração compatível, migração entre contas Imagine e histórico das operações.
3. Distinguir **exportação simples** de **backup completo**:
   - Exportação simples: seleção de leads, contatos e demais registros elegíveis, com filtros e arquivos CSV/XLSX.
   - Backup completo: pacote estruturado com dados da empresa e, quando portáveis, relacionamentos, históricos, anexos e configurações; escopo e formato devem ser documentados. Uma planilha isolada não é backup completo.
4. Assistente de importação/restauração: identificar arquivo, validar formato e compatibilidade, mapear campos quando necessário, apontar duplicidades e incompatibilidades, exibir prévia, permitir revisão e confirmar explicitamente antes de gravar. Evitar sobrescritas não intencionais e registrar o resultado.
5. **Conta nova ou vazia:** permitir restauração completa dos dados compatíveis.
6. **Conta com registros existentes:** analisar conflitos e oferecer importação/mesclagem controlada, sem exclusão automática de informações existentes.
7. Migração de dados entre contas do CRM Imagine será executável **pelo próprio cliente**, mediante operações autorizadas de exportação e importação/restauração.
8. Não copiar automaticamente para outra conta credenciais, segredos, tokens de terceiros ou informações de faturamento vinculadas ao contrato de outra empresa.
9. Aplicar controles de autorização, segregação de empresas, proteção de dados, logs e confirmação reforçada para restaurações ou operações de risco. Somente pessoas autorizadas podem obter arquivos de exportação/backup.
10. Atingir **100% da franquia de anexos** não deve impedir exportação e download dos dados existentes. O bloqueio aprovado refere-se apenas a **novos uploads de anexos**, preservando dados estruturados e operações permitidas.
11. **Backups do cliente** e seus arquivos exportados não substituem os **backups internos operacionais** da plataforma. A política de proteção/recuperação dos sites Imagine (IN-101) é independente da política de backup do CRM.

## Franquias de armazenamento já aprovadas
| Plano | Franquia |
|---|---:|
| Básico | 1 GB |
| Essencial | 5 GB |
| Profissional | 20 GB |
| Empresarial | 50 GB |

Essas franquias são de armazenamento contratual da conta, e não devem ser confundidas automaticamente com tamanho de pacotes temporários de backup ou com limites de processamento. Permanecem os avisos internos de 80% e 95% e a restrição somente a novos uploads de anexos ao atingir 100%.

## Integração com Imagine Sites e mudança de conta vinculada

- Cada site/landing page terá uma conta CRM vinculada, escolhida pelo cliente na contratação, conforme a regra de escolha entre nova conta ou conta existente.
- Para alterar posteriormente o vínculo, o cliente abre um **chamado**.
- A Imagine executa **gratuitamente somente a desvinculação técnica da conta anterior e a integração com a nova conta**; valida que novos formulários/leads sejam encaminhados corretamente.
- O cliente é responsável por baixar/exportar os dados da conta anterior e enviar/importar/restaurar os dados que desejar na conta nova.
- Nenhuma migração de dados é incluída automaticamente no serviço gratuito de alteração de vínculo.
- A conta anterior não é apagada por causa da troca; o acesso e a cortesia seguem as regras de elegibilidade/contratação aplicáveis.

## Pontos técnicos a especificar antes da implementação

- Esquema versionado de backup completo, manifesto e verificações de integridade.
- Quais dados, históricos, vínculos, anexos e parametrizações são portáveis; exceções documentadas.
- Regras para identificar registros e mesclar duplicidades entre empresas, incluindo preservação de consentimentos e trilha de auditoria.
- Tamanho máximo por operação, processamento assíncrono, disponibilização e validade dos downloads, armazenamento temporário e limpeza segura.
- Criptografia dos arquivos, proteção de acesso, confirmação de identidade, auditoria e limites de taxa.
- Reversibilidade e pontos de recuperação de restaurações, especialmente em contas com dados preexistentes.
- Tratamento de backups de clientes com conta suspensa, cancelada ou em retenção, conforme políticas contratuais e legais.
- Testes de restauração, compatibilidade entre versões e experiências claras de erro/recuperação.
- Retenção e custos operacionais a dimensionar, sem presumir retenção ilimitada.

## Status
**Aprovado:** requisitos funcionais e a disponibilidade por plano acima.  
**Pendente:** especificação técnica e validação antes de programar.  
**Programação:** não iniciada por este documento.
