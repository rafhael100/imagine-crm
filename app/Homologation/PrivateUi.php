<?php

declare(strict_types=1);

namespace App\Homologation;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Presentation only: no global locale, identity, tenant or validation rule is changed. */
final class PrivateUi
{
    private const REQUEST_LANGUAGE = '_crm_private_ui_language';
    private const SESSION_LANGUAGE = 'crm.private.lang';

    private const LABELS = [
        'task_state' => ['todo'=>'Em aberto', 'done'=>'Concluída'],
        'account_status' => ['active'=>'Ativo', 'suspended'=>'Suspenso'],
        'role' => ['client'=>'Cliente', 'admin'=>'Administrador'],
        'team_role' => ['owner'=>'Responsável', 'member'=>'Membro'],
        'plan' => ['basic'=>'Básico', 'essential'=>'Essencial', 'professional'=>'Profissional', 'enterprise'=>'Empresarial'],
        'payment_method' => ['pix'=>'Pix simulado', 'card'=>'Cartão simulado', 'boleto'=>'Boleto simulado'],
        'order_state' => ['pending'=>'Pendente', 'approved'=>'Aprovado', 'refused'=>'Recusado', 'cancelled'=>'Cancelado'],
        'activity' => [
            'lead.created'=>'Lead criado', 'lead.imported'=>'Lead importado',
            'lead.moved'=>'Lead movido', 'lead.updated'=>'Lead atualizado',
            'task.created'=>'Tarefa criada', 'task.completed'=>'Tarefa concluída',
            'preferences.language_changed'=>'Idioma alterado', 'preferences.updated'=>'Preferências atualizadas',
            'automation.queued'=>'Acompanhamento agendado', 'automation.cancelled'=>'Acompanhamento cancelado',
            'automation.failed'=>'Falha no acompanhamento', 'automation.retry'=>'Nova tentativa de acompanhamento',
            'automation.task_created'=>'Tarefa de acompanhamento criada',
            'order.simulated'=>'Pedido simulado criado',
            'payment.simulated_approved'=>'Pagamento simulado aprovado',
            'payment.simulated_refused'=>'Pagamento simulado recusado',
            'payment.simulated_cancelled'=>'Pagamento simulado cancelado',
            'funnel.created'=>'Funil criado', 'team.invited'=>'Convite de equipe criado',
            'resume'=>'Conta reativada', 'suspend'=>'Conta suspensa',
        ],
    ];

    private const FIELDS = [
        'login'=>'Login fictício', 'password'=>'Senha', 'company'=>'Empresa fictícia',
        'cnpj'=>'CNPJ de laboratório', 'email'=>'E-mail fictício', 'phone'=>'Telefone fictício',
        'terms'=>'Termos simulados', 'plan'=>'Plano', 'code'=>'Código de verificação ou recuperação',
        'recovery'=>'Código de recuperação', 'title'=>'Título', 'value_cents'=>'Valor em centavos',
        'funnel'=>'Funil', 'lead'=>'Lead', 'due'=>'Prazo', 'version'=>'Versão',
        'stage'=>'Etapa', 'automation_version'=>'Versão', 'delay_seconds'=>'Prazo da automação',
        'language'=>'Idioma', 'rules'=>'Regras de automação', 'method'=>'Pagamento fictício',
        'key'=>'Chave do pedido', 'state'=>'Estado simulado', 'status'=>'Status',
        'csv'=>'Arquivo CSV fictício', 'invitation'=>'Código do convite',
    ];

    // Domain messages have no failed rule metadata. Match both the known field
    // and the exact reviewed copy; never translate arbitrary exception text.
    private const DOMAIN_ERRORS = [
        'signup' => [
            'Use exclusivamente os dados fictícios indicados e aceite os termos simulados.',
            'Limite de três emissões em 15 minutos. Aguarde para tentar novamente.',
            'Informe seis dígitos.',
            'Código inválido, expirado ou já utilizado.',
            'Configuração do trial indisponível.',
            'Histórico de chaves do trial incompleto.',
            'Informe o código do autenticador.',
            'Código MFA inválido, expirado ou limite de tentativas atingido.',
            'Empresa ou identificador já utilizado. Um trial por CNPJ.',
            'Convite ou identidade fictícia inválidos.',
            'Convite inválido ou encerrado.',
            'Dados de recuperação inválidos.',
            'Recuperação negada ou limite de tentativas atingido.',
        ],
        'title' => ['Use apenas o identificador fictício indicado no campo.'],
        'csv' => ['O arquivo deve ter de 1 a 20 linhas fictícias.'],
        'value_cents' => ['Valor inválido.'],
    ];

    private static ?array $catalog = null;

    public static function normalize(mixed $language): string
    {
        return in_array($language, ['pt', 'en', 'es'], true) ? $language : 'pt';
    }

    public static function language(Request $request): string
    {
        $query = $request->query();
        // An explicitly invalid value also resets an older session preference.
        if (array_key_exists('lang', $query)) return self::normalize($query['lang']);
        if ($request->attributes->has(self::REQUEST_LANGUAGE)) {
            return self::normalize($request->attributes->get(self::REQUEST_LANGUAGE));
        }
        return $request->hasSession()
            ? self::normalize($request->session()->get(self::SESSION_LANGUAGE, 'pt'))
            : 'pt';
    }

    public static function captureLanguage(Request $request): string
    {
        $language = self::language($request);
        self::rememberLanguage($request, $language);
        return $language;
    }

    /** Call before dispatch or after an auth rotation, never after all requests. */
    public static function rememberLanguage(Request $request, mixed $language): void
    {
        $language = self::normalize($language);
        $request->attributes->set(self::REQUEST_LANGUAGE, $language);
        if ($request->hasSession()) $request->session()->put(self::SESSION_LANGUAGE, $language);
    }

    /** Read only the existing non-secret form fields; Blade still escapes them. */
    public static function oldText(Request $request, string $field, string $fallback = ''): string
    {
        if (!in_array($field, ['login', 'company', 'email'], true) || !$request->hasSession()) return $fallback;
        $value = $request->session()->getOldInput($field, $fallback);
        return is_string($value) ? $value : ($value === null ? '' : $fallback);
    }

    public static function text(string $source, mixed $language, array $replacements = []): string
    {
        self::$catalog ??= require dirname(__DIR__, 2).'/resources/lang/private-crm.php';
        $language = self::normalize($language);
        $copy = $language === 'pt' ? $source : (self::$catalog[$source][$language] ?? $source);
        $tokens = [];
        foreach ($replacements as $name => $value) {
            if (is_string($name) && preg_match('/\A[a-z][a-z0-9_]*\z/D', $name)
                && (is_string($value) || is_int($value))) $tokens[':'.$name] = (string) $value;
        }
        return strtr($copy, $tokens);
    }

    public static function label(string $group, mixed $code, mixed $language): string
    {
        if ($group === 'activity' && is_string($code)
            && preg_match('/\Aautomation\.settings\.v([1-9]|1[0-9]|20)\z/D', $code, $match)) {
            return self::text('Configuração de automação · versão :version', $language, ['version'=>$match[1]]);
        }
        $fallback = $group === 'activity' ? 'Atividade registrada' : 'Não reconhecido';
        $source = is_string($code) ? (self::LABELS[$group][$code] ?? $fallback) : $fallback;
        return self::text($source, $language);
    }

    public static function validationErrors(ValidationException $error, mixed $language): array
    {
        // The thrown exception already contains the completed validator. Reading
        // these results must not validate again or invoke database-backed rules.
        $failed = $error->validator->failed();
        $translated = [];
        foreach ($error->errors() as $attribute => $messages) {
            $ruleMessages = [];
            foreach ($failed[$attribute] ?? [] as $rule => $parameters) {
                $ruleMessages[] = self::ruleError((string) $attribute, $rule, $parameters, $language);
            }
            $translated[$attribute] = [];
            foreach (array_values($messages) as $position => $message) {
                if (isset($ruleMessages[$position])) {
                    $translated[$attribute][] = $ruleMessages[$position];
                    continue;
                }
                $known = is_string($message)
                    && in_array($message, self::DOMAIN_ERRORS[$attribute] ?? [], true);
                $translated[$attribute][] = self::text($known ? $message : 'Confira os dados informados.', $language);
            }
        }
        return $translated;
    }

    private static function ruleError(string $attribute, mixed $rule, mixed $parameters, mixed $language): string
    {
        $field = self::FIELDS[$attribute]
            ?? (preg_match('/\Arules\.[0-9]+\z/D', $attribute) ? 'Regras de automação' : null);
        $generic = 'Confira os dados informados.';
        if ($field === null || !is_string($rule) || !is_array($parameters)) return self::text($generic, $language);
        $template = match (strtolower($rule)) {
            'required'=>'O campo :field é obrigatório.',
            'string'=>'O campo :field deve conter texto.',
            'integer'=>'O campo :field deve ser um número inteiro.',
            'in'=>'Escolha um valor permitido para :field.',
            'uuid'=>'O identificador de :field é inválido.',
            'dateformat'=>$parameters === ['Y-m-d'] ? 'Use uma data válida no formato AAAA-MM-DD em :field.' : $generic,
            'file','uploaded'=>'Envie um arquivo válido para :field.',
            'array'=>'O campo :field deve ser uma lista.',
            default=>$generic,
        };
        $replacements = ['field'=>self::text($field, $language)];
        if (in_array(strtolower($rule), ['min', 'max', 'size'], true)) {
            $limit = $parameters[0] ?? null;
            if (count($parameters) !== 1 || (!is_string($limit) && !is_int($limit))
                || !preg_match('/\A(?:0|[1-9][0-9]{0,8})\z/D', (string) $limit)
                || (int) $limit > 100000000) return self::text($generic, $language);
            $replacements['limit'] = (string) $limit;
            $rule = strtolower($rule);
            $numeric = in_array($attribute, ['value_cents', 'version', 'automation_version', 'delay_seconds'], true);
            $template = match (true) {
                $attribute === 'csv' && $rule === 'max'=>'O arquivo de :field deve ter no máximo :limit KB.',
                $attribute === 'rules' && $rule === 'max'=>'O campo :field deve ter no máximo :limit itens.',
                $attribute === 'csv' || $attribute === 'rules'=>$generic,
                $numeric && $rule === 'min'=>'O campo :field deve ser pelo menos :limit.',
                $numeric && $rule === 'max'=>'O campo :field deve ser no máximo :limit.',
                $numeric=>$generic,
                $rule === 'min'=>'O campo :field deve ter pelo menos :limit caracteres.',
                $rule === 'max'=>'O campo :field deve ter no máximo :limit caracteres.',
                $rule === 'size'=>'O campo :field deve ter exatamente :limit caracteres.',
                default=>$generic,
            };
        }
        return self::text($template, $language, $replacements);
    }
}
