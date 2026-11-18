<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Domain\AutomationAdministration\AutomationS2SClient;
use App\Domain\AutomationAdministration\AutomationStateReader;
use App\Domain\AutomationAdministration\DatabaseAutomationAuthority;
use App\Domain\PortalAccess\Security\PortalAdminTotp;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Mount in the pinned Portal's tests/Feature only after the disposable target guard succeeds. */
final class PortalPrivateAuthorityTest extends TestCase
{
    private const COMPANY = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const OTHER = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    private int $actor;
    private string $email;
    private string $secret;
    private int $sourceCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // Missing preparation is a failure, never a skipped/green integration test.
        $this->assertSame('true', getenv('CI'));
        $this->assertSame('1', getenv('P20_INTEGRATED_PRIVATE_CI'));
        $this->assertSame('1', getenv('P20_AUTHORITY_PG_CI'));
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('portal_automation_p20_ci', DB::connection()->getDatabaseName());
        config()->set('session.driver', 'array');
        config()->set('automation_admin.transport_enabled', true);
        $this->email = 'integrated-p20-'.Str::uuid().'@example.test';
        $this->secret = bin2hex(random_bytes(20));
        $this->actor = (int) DB::table('portal_admin_users')->insertGetId([
            'email' => $this->email, 'password_hash' => Hash::make('SyntheticP20#Password'),
            'totp_secret_ciphertext' => Crypt::encryptString($this->secret),
            'is_active' => true, 'last_totp_counter' => -1, 'session_version' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('portal_automation_grants')->insert([
            'id' => (string) Str::uuid(), 'admin_user_id' => $this->actor,
            'company_id' => self::COMPANY, 'operation' => 'automation.state.read',
            'revision' => 1, 'state' => 'active', 'expires_at' => time()+600,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->actor)) {
            DB::table('portal_automation_access_audit')->where('admin_user_id', $this->actor)->delete();
            DB::table('portal_automation_grants')->where('admin_user_id', $this->actor)->delete();
            DB::table('portal_admin_users')->where('id', $this->actor)->delete();
        }
        DB::purge('automation_authority');
        parent::tearDown();
    }

    private function login(bool $mfa = true): void
    {
        $this->post('/portal-admin/login', ['email'=>$this->email, 'password'=>'SyntheticP20#Password'])
            ->assertRedirect('/portal-admin/mfa');
        if ($mfa) {
            $this->post('/portal-admin/mfa', ['code'=>PortalAdminTotp::code(hex2bin($this->secret), intdiv(time(),30))])
                ->assertRedirect('/portal-admin');
        }
    }

    private function reader(?\Closure $afterSource = null): AutomationStateReader
    {
        $client = new AutomationS2SClient('/certs/ca.crt', '/certs/portal.crt', '/certs/portal.key', '/certs/signing.key');
        $authority = new DatabaseAutomationAuthority;
        return new AutomationStateReader(function (string $company) use ($client, $afterSource): array {
            $this->sourceCalls++;
            // Real HTTPS/mTLS transport to the private CRM container; no state fixture or fallback.
            $state = $client->read($company);
            if ($afterSource) $afterSource();
            return $state;
        }, fn(int $actor, string $company): bool => $authority->allows($actor, $company));
    }

    private function denied(AutomationStateReader $reader, string $company, string $reason = 'AUTOMATION_READ_DENIED'): void
    {
        try {
            $reader->read($this->actor, $company);
            $this->fail('Expected authority denial');
        } catch (\RuntimeException $error) {
            $this->assertSame($reason, $error->getMessage());
        }
    }

    public function test_password_without_mfa_never_calls_private_crm(): void
    {
        $this->login(false);
        $this->denied($this->reader(), self::COMPANY);
        $this->assertSame(0, $this->sourceCalls);
        // Records the current early-denial audit gap; it is not a claim of complete denial auditing.
        $this->assertSame(0, DB::table('portal_automation_access_audit')->where('admin_user_id', $this->actor)->count());
    }

    public function test_mfa_and_persisted_grant_read_real_private_crm_state(): void
    {
        $this->login();
        $state = $this->reader()->read($this->actor, self::COMPANY);
        $this->assertSame(self::COMPANY, $state['company_id']);
        $this->assertSame('synthetic_private_database', $state['source_kind']);
        $this->assertSame(1, array_sum($state['counts']));
        $this->assertSame(0, $state['ai_provider_calls']);
        $this->assertFalse($state['operational_authorization']);
        $this->assertSame(1, $this->sourceCalls);
        $this->assertSame(2, DB::table('portal_automation_access_audit')->where('admin_user_id', $this->actor)->where('outcome', 'granted')->count());
    }

    public function test_other_company_and_revoked_grant_never_call_private_crm(): void
    {
        $this->login();
        $reader = $this->reader();
        $this->denied($reader, self::OTHER);
        DB::table('portal_automation_grants')->where('admin_user_id', $this->actor)
            ->update(['state'=>'revoked', 'revoked_at'=>time(), 'revision'=>2]);
        $this->denied($reader, self::COMPANY);
        $this->assertSame(0, $this->sourceCalls);
        $this->assertSame(2, DB::table('portal_automation_access_audit')->where('admin_user_id', $this->actor)->where('outcome', 'denied')->count());
    }

    public function test_revocation_after_real_transport_prevents_returning_state(): void
    {
        $this->login();
        $reader = $this->reader(function (): void {
            DB::table('portal_automation_grants')->where('admin_user_id', $this->actor)
                ->update(['state'=>'revoked', 'revoked_at'=>time(), 'revision'=>2]);
        });
        $this->denied($reader, self::COMPANY, 'AUTOMATION_READ_REVOKED');
        $this->assertSame(1, $this->sourceCalls);
        $this->assertSame(['granted'=>1, 'denied'=>1], [
            'granted'=>DB::table('portal_automation_access_audit')->where('admin_user_id', $this->actor)->where('outcome', 'granted')->count(),
            'denied'=>DB::table('portal_automation_access_audit')->where('admin_user_id', $this->actor)->where('outcome', 'denied')->count(),
        ]);
    }
}
