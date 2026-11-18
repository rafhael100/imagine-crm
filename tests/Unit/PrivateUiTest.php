<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Homologation\PrivateUi;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class PrivateUiTest extends TestCase
{
    private function request(string $uri, array $values = []): Request
    {
        $request = Request::create($uri);
        $session = new Store('private-ui-unit', new ArraySessionHandler(10));
        $session->start();
        $session->put($values);
        $request->setLaravelSession($session);
        return $request;
    }

    /** Cached validation results only: a second validation would fail this test. */
    private function exception(array $messages, array $failed = []): ValidationException
    {
        $validator = $this->createMock(Validator::class);
        foreach (['validate', 'validated', 'passes', 'fails', 'getPresenceVerifier'] as $method) {
            $validator->expects(self::never())->method($method);
        }
        $validator->method('failed')->willReturn($failed);
        $validator->method('errors')->willReturn(new MessageBag($messages));
        $validator->method('getTranslator')->willReturn(new Translator(new ArrayLoader(), 'en'));
        return new ValidationException($validator);
    }

    public function test_locale_is_a_closed_presentation_value(): void
    {
        foreach (['pt', 'en', 'es'] as $locale) self::assertSame($locale, PrivateUi::normalize($locale));
        foreach ([null, false, 1, [], ['en'], new \stdClass(), '', 'EN', 'pt_BR', ' en ', '<script>sentinel</script>'] as $invalid) {
            self::assertSame('pt', PrivateUi::normalize($invalid));
        }
        self::assertSame('pt', PrivateUi::language(Request::create('/login')));
        self::assertSame('es', PrivateUi::language(Request::create('/login?lang=es')));
    }

    public function test_explicit_query_beats_cached_and_session_language_with_safe_fallback(): void
    {
        foreach (['lang=EN', 'lang=pt_BR', 'lang%5B%5D=en', 'lang=%3Cscript%3Esentinel%3C%2Fscript%3E', 'lang='] as $query) {
            $request = $this->request('/painel?'.$query, ['crm.private.lang'=>'en']);
            self::assertSame('pt', PrivateUi::captureLanguage($request));
            self::assertSame('pt', $request->session()->get('crm.private.lang'));
        }
        $request = $this->request('/painel', ['crm.private.lang'=>'es']);
        self::assertSame('es', PrivateUi::captureLanguage($request));
        $request->session()->forget('crm.private.lang');
        self::assertSame('es', PrivateUi::language($request));
        $request->query->set('lang', 'en');
        self::assertSame('en', PrivateUi::language($request));
        self::assertSame('pt', PrivateUi::language($this->request('/login', ['crm.private.lang'=>['es']])));
    }

    public function test_remember_after_session_invalidation_restores_only_visual_preference(): void
    {
        $request = $this->request('/login?lang=es', [
            'crm.auth.token'=>'unit-old-token', 'crm.auth.pending'=>'unit-old-pending',
            'tenant'=>'unit-old-tenant', 'login_web_guard'=>'unit-old-identity',
        ]);
        $language = PrivateUi::captureLanguage($request);
        $oldSession = $request->session()->getId();
        $oldCsrf = $request->session()->token();
        // The private controller runs this helper only after the IAM reset.
        // Real IAM rotation and replay denial are covered by the HTTP journey.
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        PrivateUi::rememberLanguage($request, $language);
        self::assertNotSame($oldSession, $request->session()->getId());
        self::assertNotSame($oldCsrf, $request->session()->token());
        self::assertSame('es', PrivateUi::language($request));
        $values = $request->session()->all();
        unset($values['_token']);
        self::assertSame(['crm'=>['private'=>['lang'=>'es']]], $values);
        PrivateUi::rememberLanguage($request, ['en']);
        self::assertSame('pt', $request->session()->get('crm.private.lang'));
    }

    public function test_closed_labels_translate_without_reinterpreting_unknown_codes(): void
    {
        self::assertSame('Completed', PrivateUi::label('task_state', 'done', 'en'));
        self::assertSame('Aprobado', PrivateUi::label('order_state', 'approved', 'es'));
        self::assertSame('Suspenso', PrivateUi::label('account_status', 'suspended', 'pt'));
        self::assertSame('Automation settings · version 20', PrivateUi::label('activity', 'automation.settings.v20', 'en'));
        self::assertSame('Actividad registrada', PrivateUi::label('activity', 'automation.settings.v21', 'es'));
        self::assertSame('Unrecognized', PrivateUi::label('task_state', '<script>sentinel</script>', 'en'));
        self::assertSame('No reconocido', PrivateUi::label('unknown', ['active'], 'es'));
        self::assertSame('Sales funnels · 2 of 2', PrivateUi::text('Funis comerciais · :count de 2', 'en', ['count'=>2]));
    }

    public function test_unknown_validation_copy_never_reflects_messages_or_attribute_names(): void
    {
        $sentinel = '<script>PRIVATE_UI_SENTINEL</script> SQLSTATE secret';
        $attribute = 'unknown_'.$sentinel;
        $error = $this->exception([$attribute=>[$sentinel, 'Use apenas o identificador fictício indicado no campo.'],
            'title'=>[$sentinel]]);
        foreach (['pt'=>'Confira os dados informados.', 'en'=>'Check the submitted information.', 'es'=>'Revisa los datos enviados.'] as $locale=>$fallback) {
            $translated = PrivateUi::validationErrors($error, $locale);
            self::assertSame([$attribute=>[$fallback, $fallback], 'title'=>[$fallback]], $translated);
            foreach (array_merge(...array_values($translated)) as $copy) {
                self::assertStringNotContainsString('PRIVATE_UI_SENTINEL', $copy);
                self::assertStringNotContainsString($attribute, $copy);
                self::assertStringNotContainsString('SQLSTATE', $copy);
            }
        }
    }

    public function test_known_domain_errors_require_the_exact_field_and_message(): void
    {
        $message = 'Use apenas o identificador fictício indicado no campo.';
        $error = $this->exception(['title'=>[$message], 'password'=>[$message], 'value_cents'=>['Valor inválido.']]);
        self::assertSame([
            'title'=>['Use only the test identifier indicated in the field.'],
            'password'=>['Check the submitted information.'],
            'value_cents'=>['Invalid amount.'],
        ], PrivateUi::validationErrors($error, 'en'));
    }

    public function test_cached_rule_results_translate_units_and_preserve_error_shape(): void
    {
        $failed = [
            'login'=>['Required'=>[]], 'password'=>['Min'=>['12'], 'Max'=>['72']],
            'code'=>['Size'=>['6']], 'value_cents'=>['Min'=>['0'], 'Max'=>['100000000']],
            'rules'=>['Array'=>[], 'Max'=>['2']], 'csv'=>['File'=>[], 'Max'=>['20']],
            'rules.1'=>['In'=>['followup_qualified','followup_proposal']],
            'lead'=>['Uuid'=>[]], 'due'=>['DateFormat'=>['Y-m-d']],
            'version'=>['Integer'=>[]], 'title'=>['String'=>[]],
        ];
        $messages = [];
        foreach ($failed as $attribute=>$rules) {
            $messages[$attribute] = array_map(static fn ($rule) => 'RAW_'.$attribute.'_'.$rule, array_keys($rules));
        }
        $error = $this->exception($messages, $failed);
        $expected = [
            'login'=>['The Test username field is required.'],
            'password'=>['The Password field must have at least 12 characters.', 'The Password field must have at most 72 characters.'],
            'code'=>['The Verification or recovery code field must have exactly 6 characters.'],
            'value_cents'=>['The Amount in BRL cents field must be at least 0.', 'The Amount in BRL cents field must be at most 100000000.'],
            'rules'=>['The Automation rules field must be a list.', 'The Automation rules field must have at most 2 items.'],
            'csv'=>['Upload a valid file for Test CSV file.', 'The file for Test CSV file must be at most 20 KB.'],
            'rules.1'=>['Choose an allowed value for Automation rules.'],
            'lead'=>['The identifier for Lead is invalid.'],
            'due'=>['Use a valid date in YYYY-MM-DD format for Due date.'],
            'version'=>['The Version field must be an integer.'],
            'title'=>['The Title field must contain text.'],
        ];
        self::assertSame($expected, PrivateUi::validationErrors($error, 'en'));
        foreach (['pt', 'es'] as $locale) {
            $translated = PrivateUi::validationErrors($error, $locale);
            self::assertSame(array_keys($messages), array_keys($translated));
            foreach ($messages as $attribute=>$copies) {
                self::assertCount(count($copies), $translated[$attribute]);
                foreach ($translated[$attribute] as $copy) self::assertStringNotContainsString('RAW_', $copy);
            }
        }
    }

    public function test_unknown_rules_and_unsafe_rule_parameters_use_the_localized_fallback(): void
    {
        $failed = [
            'title'=>['CustomSqlRule'=>['PRIVATE_UI_SENTINEL']],
            'password'=>['Min'=>['<script>PRIVATE_UI_SENTINEL</script>']],
            'due'=>['DateFormat'=>['PRIVATE_UI_SENTINEL']],
            'csv'=>['Max'=>['100000001']],
            'unknown_field'=>['Required'=>[]],
        ];
        $messages = array_fill_keys(array_keys($failed), ['PRIVATE_UI_SENTINEL']);
        self::assertSame(array_fill_keys(array_keys($failed), ['Revisa los datos enviados.']),
            PrivateUi::validationErrors($this->exception($messages, $failed), 'es'));
    }

    public function test_old_text_preserves_strings_and_does_not_change_session_state(): void
    {
        foreach (['login', 'company', 'email'] as $field) {
            foreach (['lab-old-input', '', '<img src=x onerror="PRIVATE_OLD_INPUT_SENTINEL">'] as $value) {
                $request = $this->request('/cadastro', [
                    '_old_input'=>[$field=>$value], 'crm.private.lang'=>'es',
                    'crm.auth.pending'=>'unit-pending-unchanged',
                ]);
                $before = [$request->session()->getId(), $request->session()->all()];
                // Escaping belongs to Blade; the helper must not rewrite strings.
                self::assertSame($value, PrivateUi::oldText($request, $field, 'unit-fallback'));
                self::assertSame($before, [$request->session()->getId(), $request->session()->all()]);
            }
        }
    }

    public function test_old_text_rejects_invalid_types_and_nested_arrays_without_casting(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                throw new \LogicException('Old input objects must not be stringified.');
            }
        };
        foreach ([[], ['PRIVATE_OLD_INPUT_SENTINEL'], ['nested'=>['value'=>'PRIVATE_OLD_INPUT_SENTINEL']],
            0, 17, 1.5, false, true, new \stdClass(), $stringable] as $value) {
            $request = $this->request('/cadastro', [
                '_old_input'=>array_fill_keys(['login', 'company', 'email'], $value),
            ]);
            $before = [$request->session()->getId(), $request->session()->all()];
            foreach (['login', 'company', 'email'] as $field) {
                self::assertSame('unit-fallback-'.$field,
                    PrivateUi::oldText($request, $field, 'unit-fallback-'.$field));
            }
            self::assertSame($before, [$request->session()->getId(), $request->session()->all()]);
        }
    }

    public function test_old_text_distinguishes_null_empty_and_missing_values(): void
    {
        $request = $this->request('/cadastro', ['_old_input'=>['login'=>null, 'company'=>'']]);
        $before = $request->session()->all();
        self::assertSame('', PrivateUi::oldText($request, 'login', 'unit-fallback'));
        self::assertSame('', PrivateUi::oldText($request, 'company', 'unit-fallback'));
        self::assertSame('unit-fallback', PrivateUi::oldText($request, 'email', 'unit-fallback'));
        self::assertSame('', PrivateUi::oldText($request, 'email'));
        self::assertSame($before, $request->session()->all());
    }

    public function test_old_text_never_reads_secrets_or_fields_outside_the_allowlist(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('hasSession')->willReturn(true);
        $request->expects(self::never())->method('session');
        foreach (['password', 'code', 'recovery', 'invitation', '_token', 'crm.auth.token',
            'crm.auth.pending', 'tenant', 'organization_id', 'login.nested', 'Login', 'unknown'] as $field) {
            self::assertSame('unit-safe-fallback', PrivateUi::oldText($request, $field, 'unit-safe-fallback'));
        }
    }

    public function test_old_text_without_a_session_uses_fallback_without_reading_session(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('hasSession')->willReturn(false);
        $request->expects(self::never())->method('session');
        foreach (['login', 'company', 'email'] as $field) {
            self::assertSame('unit-safe-fallback', PrivateUi::oldText($request, $field, 'unit-safe-fallback'));
        }
    }
}
