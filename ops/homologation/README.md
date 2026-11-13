# CRM F3-58 — preflight read-only de staging privado

**Uso futuro, exclusivamente na VPS de homologação autorizada:** execute `php ops/homologation/crm-readonly-preflight.php` com as variáveis de ambiente fornecidas em canal seguro pela operação. O script consulta somente catálogos do PostgreSQL (papel da conexão, versão, RLS/FORCE RLS na tabela `crm_memberships` e contexto de tenant na sessão). Não faz migrations, inserts, escrita, provisionamento, deploy ou liga o formulário.

Configurações exigidas: `APP_ENV=homologation`, `APP_DEBUG=false`, `IMAGINE_HOMOLOGATION_PREPARE=1`, `IMAGINE_PUBLIC_INTAKE_ENABLED=0`, `IMAGINE_DELIVERY_WORKER_ENABLED=0`, `IMAGINE_SYNTHETIC_DATA_ONLY=1`, `DB_CONNECTION=pgsql`; DB_HOST deve ser endereço IPv4 privado/loopback e, fora de loopback, a conexão requer TLS. Credenciais DB apenas pelo ambiente da execução (nunca em comando, PR ou issue). O hostname e nome da base não são exibidos no resultado.

Qualquer falha retorna `NO_GO`. Mesmo com todos os controles técnicos aprovados, o resultado é apenas `READ_ONLY_PREFLIGHT_PASSED_REVIEW_REQUIRED` — não comprova que a VPS está homologada, que as tabelas representam produção, que TLS/rede/firewall/backup são adequados, que o principal de serviço é real, nem autoriza rotas, lead real, worker ou deploy.

**Importante:** a conexão usa a porta 5432 e não consulta dados pessoais. Os testes CI com fixture PostgreSQL descartável não são evidência de acesso ou homologação Leaseweb. CRM #259, Portal #315, Comercial #56 e revisões AppSec/DBA/LGPD permanecem bloqueios de liberação.
