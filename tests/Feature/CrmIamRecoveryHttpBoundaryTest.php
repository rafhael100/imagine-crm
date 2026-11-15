<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\GuardPrincipalSource;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SyntheticIam;
use Tests\TestCase;

/**
 * CRM-IAM-RECOVERY-HTTP-01: single-use recovery code through the real
 * Laravel HTTP/session/CSRF pipeline, with synthetic fixtures only.
 *
 * All routes below are registered in the PHPUnit process; none are
 * registered in routes/web.php or exposed by the application.
 */
final class CrmIamRecoveryHttpBoundaryTest extends TestCase
{
    use SyntheticIam;

    private function registerRecoveryTestRoutes(): void
    {
        Route::middleware(['web', CrmIamRecoveryCsrfForTest::class])
            ->group(function (): void {
                Route::post('/_ci/iam-recovery/password', function (Request $request) {
                    $login = $request->input('login');
                    $password = $request->input('password');
                    if (!is_string($login) || !is_string($password)) {
                        abort(401);
                    }
                    $this->auth->begin($login, $password);
                    return response()->json(['state' => 'mfa_pending'], 202);
                });

                Route::post('/_ci/iam-recovery/code', function (Request $request) {
                    $code = $request->input('code');
                    if (!is_string($code)) {
                        abort(401);
                    }
                    $this->auth->completeMfa($code, true);
                    return response()->json(['state' => 'authenticated'], 200);
                });

                Route::post('/_ci/iam-recovery/logout', function () {
                    $this->auth->logout();
                    return response()->noContent();
                });
            });

        Route::middleware('web')->get('/_ci/iam-recovery/current', function () {
            $identity = $this->app->make(GuardPrincipalSource::class)->current();
            return response()->json([
                'identity' => $identity->principalId,
                'can_read_leads' => false,
                'can_create_tenant' => false,
                'can_charge' => false,
            ]);
        });
    }

    /** Replay the response cookie unchanged; Laravel has already encrypted it. */
    private function carrySessionCookie(\Illuminate\Testing\TestResponse $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() !== config('session.cookie')) {
                continue;
            }
            $this->assertTrue($cookie->isSecure());
            $this->assertTrue($cookie->isHttpOnly());
            $this->assertSame('strict', $cookie->getSameSite());
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
                ->withCredentials();
            return;
        }
        $this->fail('Response must issue a secure encrypted Laravel session cookie.');
    }

    private function beginHttpPassword(string $csrf): void
    {
        // A previous logout rotates the session ID. Bind this browser
        // request to the CURRENT server session rather than reusing the
        // encrypted response cookie from the earlier authentication cycle.
        $this->withSession(['_token' => $csrf]);
        $this->withCookie((string) config('session.cookie'), $this->browserSession->getId())
            ->withCredentials();
        $response = $this->post('/_ci/iam-recovery/password', [
                'login' => 'fixture-a',
                'password' => self::PASSWORD,
                '_token' => $csrf,
            ])->assertStatus(202)->assertJson(['state' => 'mfa_pending']);
        $this->carrySessionCookie($response);
    }

    public function test_recovery_code_authenticates_after_password_once_and_then_cannot_be_reused(): void
    {
        $this->registerRecoveryTestRoutes();
        $this->getJson('/_ci/iam-recovery/current')->assertUnauthorized();

        $this->beginHttpPassword('recovery-csrf-1');
        $this->getJson('/_ci/iam-recovery/current')->assertUnauthorized();

        $response = $this->postJson('/_ci/iam-recovery/code', [
            'code' => self::RECOVERY,
            '_token' => $this->browserSession->token(),
        ])->assertOk()->assertJson(['state' => 'authenticated']);
        $this->carrySessionCookie($response);

        $this->getJson('/_ci/iam-recovery/current')->assertOk()->assertJson([
            'identity' => self::A,
            'can_read_leads' => false,
            'can_create_tenant' => false,
            'can_charge' => false,
        ]);
        $this->assertNotNull($this->fixtures->table('crm_auth_recovery_codes')
            ->where('identity_id', self::A)->value('consumed_at'));
        $this->assertSame(1, $this->fixtures->table('crm_auth_sessions')
            ->where('identity_id', self::A)->where('state', 'active')->count());

        $this->post('/_ci/iam-recovery/logout', [
            '_token' => $this->browserSession->token(),
        ])->assertNoContent();
        $this->getJson('/_ci/iam-recovery/current')->assertUnauthorized();
        $this->assertSame(0, $this->fixtures->table('crm_auth_sessions')
            ->where('identity_id', self::A)->where('state', 'active')->count());

        $this->beginHttpPassword('recovery-csrf-2');
        $this->getJson('/_ci/iam-recovery/current')->assertUnauthorized();
        $this->postJson('/_ci/iam-recovery/code', [
            'code' => self::RECOVERY,
            '_token' => $this->browserSession->token(),
        ])->assertUnauthorized();

        $this->getJson('/_ci/iam-recovery/current')->assertUnauthorized();
        $this->assertSame(0, $this->fixtures->table('crm_auth_sessions')
            ->where('identity_id', self::A)->where('state', 'active')->count());
        $this->assertNotNull($this->fixtures->table('crm_auth_recovery_codes')
            ->where('identity_id', self::A)->value('consumed_at'));
    }

    public function test_recovery_endpoint_rejects_csrf_missing_and_unregistered_public_paths(): void
    {
        $this->registerRecoveryTestRoutes();
        $this->post('/_ci/iam-recovery/code', ['code' => self::RECOVERY])
            ->assertStatus(419);
        $this->getJson('/_ci/iam-recovery/current')->assertUnauthorized();
        foreach (['/login', '/recovery', '/forgot-password', '/api/recovery'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->assertSame(0, $this->fixtures->table('crm_auth_sessions')
            ->where('state', 'active')->count());
    }
}

final class CrmIamRecoveryCsrfForTest extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
