<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Identity\GuardPrincipalSource;
use App\Core\Identity\PrincipalReference;
use App\Core\Identity\Totp;
use App\Core\Tenancy\ActiveMembershipStore;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantAccessBoundary;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * CRM-IAM-HTTP-01: real Laravel request pipeline, synthetic identities only.
 *
 * Routes are registered INSIDE this PHPUnit test process. No controller,
 * routes/web.php, production feature flag, API exposure or access grant.
 * Database-backed session registry/TOTP and the Laravel web session are real.
 */
final class CrmIamHttpBoundaryTest extends TestCase
{
    use SyntheticIam;

    private int $businessEffects = 0;

    private function registerLaboratoryRoutes(): void
    {
        Route::middleware(['web', CrmIamHttpCsrfForTest::class])->group(function (): void {
            Route::post('/_ci/iam/password', function (Request $request) {
                $login = $request->input('login');
                $password = $request->input('password');
                if (!is_string($login) || !is_string($password)) {
                    throw new AuthenticationException('CRM authentication required.');
                }
                $this->auth->begin($login, $password);

                return response()->json(['state' => 'mfa_pending'], 202);
            });

            Route::post('/_ci/iam/mfa', function (Request $request) {
                $code = $request->input('code');
                if (!is_string($code)) {
                    throw new AuthenticationException('CRM authentication required.');
                }
                $this->auth->completeMfa($code);

                return response()->json(['state' => 'authenticated'], 200);
            });

            Route::post('/_ci/iam/logout', function () {
                $this->auth->logout();

                return response()->noContent();
            });
        });

        Route::middleware('web')->group(function (): void {
            Route::get('/_ci/iam/state-check', function (Request $request) {
                return response()->json([
                    'same_server_session' => $request->session()->getId()
                        === $this->browserSession->getId(),
                    'pending' => $request->session()->has('crm.auth.pending'),
                    'client_cookie_present' => $request->cookies->has(config('session.cookie')),
                ]);
            });

            Route::get('/_ci/iam/current', function () {
                $source = $this->app->make(GuardPrincipalSource::class);

                // Not a production profile, API, or tenant lookup.
                return response()->json([
                    'identity' => $source->current()->principalId,
                    'can_read_customer_data' => false,
                    'can_charge' => false,
                ]);
            });
            Route::get('/_ci/iam/tenant/{organization}', function (string $organization) {
                $source = $this->app->make(GuardPrincipalSource::class);
                $store = new class implements ActiveMembershipStore {
                    public function within(
                        PrincipalReference $principal,
                        string $tenantId,
                        Closure $operation,
                    ): mixed {
                        return $operation(new MembershipSnapshot(
                            $tenantId, $principal->principalId,
                            MembershipStatus::Active, 1,
                        ));
                    }
                };
                $boundary = new TenantAccessBoundary(
                    $source, $store, new DenyByDefaultAuthorizer(),
                );

                // Even a verified MFA session and a deliberately invented
                // active membership NEVER authorizes any business callback.
                return $boundary->execute(
                    $organization, 'lead', 'read',
                    function () {
                        $this->businessEffects++;
                        return response()->json(['invalid' => 'would_be_an_access_grant']);
                    },
                );
            });
        });
    }

    /** Carry the actual encrypted server-session cookie across real HTTP requests. */
    private function carryBrowserSessionCookie(\Illuminate\Testing\TestResponse $response): void
    {
        $sessionCookie = config('session.cookie');
        $found = false;
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() !== $sessionCookie) {
                continue;
            }
            $this->assertTrue($cookie->isSecure());
            $this->assertTrue($cookie->isHttpOnly());
            $this->assertSame('strict', $cookie->getSameSite());
            // HTTP TestCase normally ENCRYPTS withCookie() inputs and omits
            // cookies entirely on JSON requests. Response cookies already
            // have Laravel's encrypted+signed value: forward them unchanged.
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
                ->withCredentials();
            $found = true;
        }
        $this->assertTrue($found, 'Real web response must issue a session cookie.');
    }

    public function test_real_laravel_http_password_mfa_session_business_deny_and_logout(): void
    {
        $this->registerLaboratoryRoutes();
        $this->getJson('/_ci/iam/current')->assertUnauthorized();
        $this->getJson('/_ci/iam/tenant/'.self::C)->assertUnauthorized();
        $this->assertSame(0, $this->businessEffects);

        $passwordResponse = $this->withSession(['_token' => 'crm-test-csrf-a'])
            ->post('/_ci/iam/password', [
                'login' => 'fixture-a',
                'password' => self::PASSWORD,
                '_token' => 'crm-test-csrf-a',
            ])->assertStatus(202)->assertJson(['state' => 'mfa_pending']);
        $this->carryBrowserSessionCookie($passwordResponse);
        $this->assertTrue($this->browserSession->has('crm.auth.pending'));

        $this->getJson('/_ci/iam/current')->assertUnauthorized();
        $this->assertSame(0, $this->businessEffects);
        $this->getJson('/_ci/iam/state-check')->assertOk()->assertJson([
            'pending' => true,
            'same_server_session' => true,
            'client_cookie_present' => true,
        ]);

        // Begin() rotates session+CSRF. Use ONLY its freshly issued token.
        $fresh = $this->browserSession->token();
        $this->assertNotSame('crm-test-csrf-a', $fresh);
        $mfaResponse = $this->postJson('/_ci/iam/mfa', [
            'code' => (new Totp())->code(self::SECRET, now()->getTimestamp()),
            '_token' => $fresh,
        ])->assertOk()->assertJson(['state' => 'authenticated']);
        $this->carryBrowserSessionCookie($mfaResponse);

        $this->getJson('/_ci/iam/current')->assertOk()->assertJson([
            'identity' => self::A,
            'can_read_customer_data' => false,
            'can_charge' => false,
        ]);
        $this->getJson('/_ci/iam/tenant/'.self::C)->assertForbidden();
        $this->assertSame(0, $this->businessEffects);

        $csrf = $this->browserSession->token();
        $this->post('/_ci/iam/logout', ['_token' => $csrf])->assertNoContent();
        $this->getJson('/_ci/iam/current')->assertUnauthorized();
        $this->getJson('/_ci/iam/tenant/'.self::C)->assertUnauthorized();
        $this->assertSame(0, $this->businessEffects);
        $this->assertSame(0, $this->db->table('crm_auth_sessions')
            ->where('state', 'active')->count());
    }

    public function test_bad_credentials_and_mfa_rejection_never_produce_authorized_http_session(): void
    {
        $this->registerLaboratoryRoutes();
        $this->withSession(['_token' => 'crm-test-csrf-b'])
            ->postJson('/_ci/iam/password', [
                'login' => 'fixture-a',
                'password' => 'invalid-password',
                '_token' => 'crm-test-csrf-b',
            ])->assertUnauthorized();
        $this->getJson('/_ci/iam/current')->assertUnauthorized();

        $this->withSession(['_token' => 'crm-test-csrf-c'])
            ->post('/_ci/iam/password', [
                'login' => 'fixture-a',
                'password' => self::PASSWORD,
                '_token' => 'crm-test-csrf-c',
            ])->assertStatus(202);
        $fresh = $this->browserSession->token();
        $this->postJson('/_ci/iam/mfa', [
            'code' => '999invalid',
            '_token' => $fresh,
        ])->assertUnauthorized();

        $this->getJson('/_ci/iam/current')->assertUnauthorized();
        $this->getJson('/_ci/iam/tenant/'.self::C)->assertUnauthorized();
        $this->assertSame(0, $this->businessEffects);
        $this->assertSame(0, $this->db->table('crm_auth_sessions')
            ->where('state', 'active')->count());
    }

    public function test_real_http_csrf_and_unregistered_public_login_are_denied(): void
    {
        $this->registerLaboratoryRoutes();
        // A normal HTML browser request must fail 401, not crash while
        // redirecting to the deliberately absent named "login" route.
        $denied = $this->get('/_ci/iam/current');
        $denied->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->assertStringContainsString(
            'no-store',
            (string) $denied->headers->get('Cache-Control'),
        );
        $this->post('/_ci/iam/password', [
            'login' => 'fixture-a', 'password' => self::PASSWORD,
        ])->assertStatus(419);
        $this->assertSame(0, $this->db->table('crm_auth_sessions')->count());

        foreach (['/login', '/mfa', '/register', '/api/auth', '/api/leads'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}

/** Forces the ordinary CSRF check disabled by Laravel's test environment. */
final class CrmIamHttpCsrfForTest extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
