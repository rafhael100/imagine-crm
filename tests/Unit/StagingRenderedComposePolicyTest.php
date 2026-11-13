<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Independent policy tests: no Docker daemon or network needed. */
final class StagingRenderedComposePolicyTest extends TestCase
{
    private static function policy(array $model): array
    {
        require_once dirname(__DIR__, 2).'/scripts/ci/verify-staging-rendered-compose.php';
        return \StagingRenderedComposePolicy::violations($model, '/review/checkout');
    }

    private static function validModel(): array
    {
        $base = [
            'read_only' => true,
            'cap_drop' => ['ALL'],
            'security_opt' => ['no-new-privileges:true'],
            'networks' => ['crm_private' => null],
        ];

        return [
            'name' => 'imagine-crm-stage-review',
            'networks' => [
                'crm_private' => ['internal' => true, 'name' => 'imagine-crm-stage-review_crm_private'],
            ],
            'services' => [
                'app' => array_merge($base, [
                    'user' => '33:33',
                    'build' => [
                        'context' => '/review/checkout',
                        'dockerfile' => 'infra/staging/Dockerfile',
                    ],
                    'tmpfs' => [
                        '/tmp:rw,nosuid,noexec,size=16m,mode=1777',
                        '/run:rw,nosuid,noexec,size=1m',
                        '/usr/local/var/run:rw,nosuid,noexec,size=1m',
                        '/var/www/html/storage:rw,nosuid,noexec,size=16m,uid=33,gid=33',
                        '/var/www/html/bootstrap/cache:rw,nosuid,noexec,size=2m,uid=33,gid=33',
                    ],
                    'environment' => [
                        'APP_NAME' => 'CRM Imagine - Staging Smoke',
                        'APP_ENV' => 'production',
                        'APP_DEBUG' => 'false',
                        'APP_URL' => 'http://127.0.0.1:18080',
                        'CRM_AUTH_EXPERIMENTAL' => 'false',
                        'DB_CONNECTION' => 'sqlite',
                        'DB_DATABASE' => ':memory:',
                        'CACHE_STORE' => 'array',
                        'SESSION_DRIVER' => 'array',
                        'QUEUE_CONNECTION' => 'sync',
                        'MAIL_MAILER' => 'array',
                        'LOG_CHANNEL' => 'stderr',
                        'FILESYSTEM_DISK' => 'local',
                    ],
                ]),
                'web' => array_merge($base, [
                    'user' => '101:101',
                    'build' => [
                        'context' => '/review/checkout',
                        'dockerfile' => 'infra/staging/Dockerfile.web',
                    ],
                    'tmpfs' => [
                        '/tmp:rw,nosuid,noexec,size=8m,mode=1777',
                        '/var/cache/nginx:rw,nosuid,noexec,size=8m,uid=101,gid=101',
                    ],
                    'ports' => [[
                        'host_ip' => '127.0.0.1',
                        'target' => 8080,
                        'published' => '18080',
                        'protocol' => 'tcp',
                        'mode' => 'ingress',
                    ]],
                    'depends_on' => [
                        'app' => ['condition' => 'service_started', 'required' => true],
                    ],
                ]),
            ],
        ];
    }

    public function test_valid_review_only_config_is_accepted_without_granting_deployment(): void
    {
        self::assertSame([], self::policy(self::validModel()));
    }

    public function test_normalized_compose_does_not_accept_unapproved_resources_or_exposure(): void
    {
        $changes = [
            'public binding' => ['web', 'ports', [[
                'host_ip' => '0.0.0.0', 'target' => 8080, 'published' => '18080',
                'protocol' => 'tcp', 'mode' => 'ingress',
            ]], 'non_loopback_or_extra_ports'],
            'host mount' => ['web', 'volumes', [['type' => 'bind', 'source' => '/host/public', 'target' => '/var/www/html/public']], 'web_forbidden_volumes'],
            'socket device' => ['app', 'devices', ['/var/run/docker.sock'], 'app_forbidden_devices'],
            'environment secret' => ['app', 'environment', array_merge(
                self::validModel()['services']['app']['environment'],
                ['APP_KEY' => 'not-a-secret-real-test-placeholder'],
            ), 'app_environment_mismatch'],
            'mail delivery' => ['app', 'environment', array_merge(
                self::validModel()['services']['app']['environment'],
                ['MAIL_MAILER' => 'smtp'],
            ), 'app_environment_mismatch'],
            'live database' => ['app', 'environment', array_merge(
                self::validModel()['services']['app']['environment'],
                ['DB_CONNECTION' => 'pgsql'],
            ), 'app_environment_mismatch'],
            'experimental login' => ['app', 'environment', array_merge(
                self::validModel()['services']['app']['environment'],
                ['CRM_AUTH_EXPERIMENTAL' => 'true'],
            ), 'app_environment_mismatch'],
            'extra network' => ['web', 'networks', ['crm_private' => null, 'default' => null], 'web_unapproved_network'],
            'root app' => ['app', 'user', '0:0', 'app_runtime_user'],
            'writable web' => ['web', 'read_only', false, 'web_writable_root'],
            'build secrets' => ['app', 'build', [
                'context' => '/review/checkout',
                'dockerfile' => 'infra/staging/Dockerfile',
                'secrets' => ['bad'],
            ], 'app_build_boundary'],
            'custom host mode' => ['web', 'network_mode', 'host', 'web_forbidden_network_mode'],
        ];

        foreach ($changes as $label => [$service, $key, $value, $expected]) {
            $changed = self::validModel();
            $changed['services'][$service][$key] = $value;
            self::assertContains($expected, self::policy($changed), $label);
        }
    }

    public function test_new_services_and_top_level_volumes_fail_closed(): void
    {
        $model = self::validModel();
        $model['services']['postgres'] = ['image' => 'postgres:16'];
        self::assertContains('unexpected_service_set', self::policy($model));

        $model = self::validModel();
        $model['volumes'] = ['customer-data' => []];
        self::assertContains('unexpected_top_level_resources', self::policy($model));

        $model = self::validModel();
        $model['networks']['crm_private']['internal'] = false;
        self::assertContains('network_not_private', self::policy($model));
    }

    public function test_host_checkout_must_be_exactly_build_context(): void
    {
        $model = self::validModel();
        $model['services']['web']['build']['context'] = '/tmp/other-repo';
        self::assertContains('web_build_boundary', self::policy($model));
    }

    public function test_rendered_policy_is_not_deployed_as_part_of_application_image(): void
    {
        $dockerfile = file_get_contents(dirname(__DIR__, 2).'/infra/staging/Dockerfile');
        self::assertIsString($dockerfile);
        self::assertStringNotContainsString('COPY scripts/', $dockerfile);
        self::assertStringNotContainsString('COPY tests/', $dockerfile);
    }
}
