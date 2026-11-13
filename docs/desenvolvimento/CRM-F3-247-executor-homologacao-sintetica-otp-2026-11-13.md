# CRM F3-247 — Executor de homologação sintética de OTP/trial sem dados e sem envio

`php scripts/ci/emit-signup-otp-lifecycle-evidence.php` executa **o Core de OTP real** via `Tests\\Support\\SignupOtpLifecycleSandbox` (F3-246), exigindo Composer `autoload-dev`, com parâmetros exclusivamente fictícios fixados no código. O processo percorre emissão, cooldown, reenvio, nova validação, replay, tentativa de outro contato/canal, cinco falhas e bloqueio na sexta, expiração exata de 900 segundos, limite de 20 solicitações por origem, estado independente em outra instância, janela trial de 14 dias e verificação estrutural de CNPJ alfanumérico.

O código usa entropia criptográfica real do OTP e pode criar valores distintos a cada execução, mas o **relatório gerado é determinístico**: uma única linha JSON (limite 4096 bytes) contendo apenas códigos de estado, booleanos, duração fixa e flags de NO-GO. Nunca emite códigos OTP, digests, e-mails, telefones, segredos, chaves, CNPJ, cliente ou mensagem. Rejeita qualquer argumento CLI para não receber dados externos. O PHPUnit executa o CLI como subprocesso duas vezes e compara os resultados byte a byte, além de conferir campos, ausência de vazamentos e falha segura para parâmetros extras.

**Precisão:** se dois OTP de seis dígitos sorteados independentemente forem numericamente idênticos, o código anterior pode coincidir com o desafio atual; o teste evita declarar uma garantia impossível de ausência de colisão. O reenvio substitui o verificador em memória e nunca é prova de invalidação atômica distribuída. Todas as flags de emissão, registro, acesso e persistência permanecem **false**.

Não há acesso a SQL, Mail, WhatsApp, Rede, APIs, VPS ou produção, nenhuma identidade/trial elegível, nem implementação de rate-limit durável. Os testes de sandbox são uma etapa de validação técnica, não uma homologação funcional operacional. Gates SEG/DBA #27, CORE/LGPD #259, visual #416 continuam NO-GO. Merge somente na DEV após testes e diff revisados.

## Critérios de aprovação automática

O teste falha se qualquer um dos seguintes itens ocorrer: reenvio antes dos 60 segundos, reuso do desafio na mesma instância, verificação cruzada de contato ou canal, sexta tentativa após cinco erros, validade após 900 segundos, vigésima primeira emissão no mesmo contexto de origem ou compartilhamento involuntário de estado entre instâncias. A saída do CLI deve manter uma linha JSON com protocolo e versão conhecidos, no máximo 4096 bytes, e dois resultados idênticos em execuções independentes. O teste também rejeita argumentos em linha de comando e verifica que nenhum dado de contato, OTP ou chave foi serializado.

Os limites e a validade são apenas **critérios de ensaio**, não SLA, decisão de risco, política empresarial ou evidência de aprovação do canal de envio.
