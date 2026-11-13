# CRM F3-259 — Janela trial e quotas após jornada HTTP fictícia

## Entrega

Amplia o fixture de onboarding F3-258, ainda restrito ao namespace Tests/Support do Composer autoload-dev. A operação HTTP de laboratório continua retornando apenas resposta neutra e negativa, sem autorização, trial real ou exposição de CNPJ. **Somente o próprio PHPUnit** consulta o resultado de leitura descritiva do Core após emitir OTP fictício, conferir o código e registrar um marcador em memória de empresa sintética.

O novo teste HTTP cobre:
- Antes da sequência OTP → trial: nenhum marcador de empresa;
- Inicio da janela: 14 dias e quotas ilustrativas, sem uso real;
- Segundo anterior ao fim da janela: fase ativa, um dia restante arredondado;
- Momento exato do fim (14 × 86.400 segundos): janela encerrada, zero dia restante;
- Sete indicadores demonstrativos: 2 usuários, 20 leads, 2 automações, 2 funis, 20 MiB, 10+10 créditos de IA;
- Novo OTP **após expiração** não reinicia trial do mesmo identificador fictício, apenas identifica duplicidade no mesmo objeto PHP;
- Dois canais fictícios e-mail/telefone não geram dois marcadores do mesmo CNPJ sintético;
- Relógios negativos/fora do horizonte rejeitados, rotas operacionais de cadastro ainda ausentes.

## Limites de evidência / NO-GO

O start do trial é um marcador em array no processo de teste, **não** uma reserva durável ou direito. Não houve validação cadastral real do CNPJ, titular, consentimento, entrega OTP, login, verificação de propriedade de contato, PostgreSQL persistente, cobrança ou ativação de quotas. O snapshot expõe apenas dados fictícios a PHPUnit, não ao HTTP. A existência do Core PHP e o resultado verde de CI **não autorizam** runtime, ALTER TABLE, nova rota pública, VPS, produção, gasto, dados reais ou merge em main.

Preservar gates [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [UX #416](https://github.com/rafhael100/imagine-crm/issues/416). O recorte é independente de parecer técnico e não o substitui. Merge admissível apenas na DEV após revisão de diff e checks completos da HEAD.
