# QA F4-25 — Simulação de separação entre empresas e bloqueio de integração prematura

## Objetivo

Impedir que futuras alterações dos cálculos F4 prejudiquem a contenção de fixtures e que seus componentes sejam expostos por engano em pontos de entrada do Laravel, **antes** de existir uma autorização de leitura de dados reais.

### Evidências novas

1. Duas empresas inteiramente fictícias usam IDs locais idênticos. `FollowUpObservationSeries` é executado separadamente sobre cada lista já separada pelo teste: as contagens e transições de cada amostra são independentes e não carregam IDs em sua saída.
2. Se duas fontes com ID local repetido forem misturadas **dentro de uma única observação**, a projeção recusa a entrada em vez de consolidar silenciosamente dados.
3. Campos de `tenant_id` forjado inseridos em registro ou observação são rejeitados (contrato estrito).
4. Um tripwire **estático** varre `routes`, `app/Http`, `app/Providers`, `app/Jobs`, `app/Console` e `bootstrap` para recusar referências diretas às oito classes F4 de laboratório. Também verifica que `bootstrap/app.php` não registrou rota API.

## Limitações críticas

**Isto NÃO prova isolamento real de tenant.** A escolha do conjunto de registros ocorre antes da função e é assumida pelo teste. Nenhum principal, sessão, membership, contexto de banco, assinatura, grant, deny, RLS, SQL ou acesso é avaliado nesta etapa. IDs coincidentes só geram falha quando efetivamente duplicados no mesmo snapshot: se duas empresas possuírem IDs distintos e forem indevidamente combinadas, a projeção não detectará a mistura. A ausência de IDs em agregados também não é garantia de anonimização ou permissão para divulgar contagens reais.

O tripwire é fonte estática; chamadas indiretas, reflection, includes dinâmicos e outras formas de exposição podem escapar. Não equivale a homologação de rotas, segurança web ou varredura de secrets. Quando surgir um conector **formalmente autorizado**, esse bloqueio deverá ser substituído por controles mais específicos, com revisão independente.

O próximo passo operacional continua sujeito a CORE, privacidade/LGPD, owner de autorização e parecer SEG/DBA independente [#27](https://github.com/rafhael100/imagine-crm/issues/27). Não promover fixture a runtime; não criar `ALLOW`, API, migração, job, exportação, storage, custo, deploy, VPS ou modificação da `main`. Integração deste teste apenas em DEV e somente com ambos os workflows verdes na HEAD exata.
