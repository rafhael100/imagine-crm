# F3-05 — Fronteira negativa de portabilidade multiempresa (laboratório)

**Data:** 08/11/2026. **Situação:** implementação de contratos e testes sintéticos sob autorização geral do responsável; execução isolada em branch DEV, PR revisável. Referência: [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) e os módulos F3-01 a F3-04.

## Escopo

`SyntheticPortabilityDenyGate` é uma **sonda negativa**, não um novo serviço de autorização comercial. Recebe um `TenantContext` descritivo, um `SyntheticImportPreviewSummary` e uma intenção declarada de importar/exportar campos. Rejeita formato inválido, alvo diferente ou solicitações genéricas; mesmo quando todos os identificadores e campos parecem válidos retorna **`SCOPE_NOT_VERIFIED` com `allowed=false`**.

A classe nunca concede direitos, não resolve uma membership legítima e não deve ser conectada a rotas, banco de dados, importação de arquivos, exportação, Redis, APIs ou dados reais. A igualdade de `tenantId` e `targetTenantId` é exclusivamente um teste sintático, **não autenticação**. Um contexto construído pelo cliente não é uma prova de identidade. O relatório de prévia não contém prova de proprietário, papéis, escopo de campos ou permissões.

## Cobertura de testes proposta

- Mesmo tenant descrito + import/export = **negação**, não ALLOW.
- Tenant alvo diferente, ID inválido, fraude em campos, ação de exclusão, curingas e cabeçalhos duplicados = **CONTRACT_INVALID**.
- Construção de `TenantContext` idêntico por solicitante não autentica ninguém.
- Ausência de dados ou aparência de prévia limpa não transforma uma operação de importação em permitida.
- Sinal mínimo de auditoria nunca inclui tenant, lead, células ou dados de contato; correlation_id fora de UUID é descartado.
- Validação de escopo e interface com RLS, usuário, campo, plano, revogação e retenção serão tratadas apenas após critérios e revisão independente.

## Limites normativos

Todos os **67 CORE**, SEG-01/02, AUT, LGPD, DEV-GATE, Issue #27 SEG/DBA e demais gates continuam abertos. `DenyByDefaultAuthorizer` permanece sem qualquer ALLOW; o protótipo com oito telas não acessa esta classe. Nenhuma tabela, endpoint, UI funcional, CSV/XLSX real, rede, migração, grants, deploy, main, VPS, produção ou custo foi alterado. A CI sintética deste incremento não constitui homologação de isolamento entre tenants em ambiente real.
