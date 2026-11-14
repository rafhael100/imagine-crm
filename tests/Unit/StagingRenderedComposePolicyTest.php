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
                        '/var/www/html/storage/framework/cache:rw,nosuid,noexec,size=4m,uid=33,gid=33',
                        '/var/www/html/storage/framework/sessions:rw,nosuid,noexec,size=2m,uid=33,gid=33',
                        '/var/www/html/storage/framework/views:rw,nosuid,noexec,size=8m,uid=33,gid=33',
                        '/var/www/html/storage/logs:rw,nosuid,noexec,size=2m,uid=33,gid=33',
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
                    'entrypoint' => ['nginx', '-g', 'daemon off;'],
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
            'storage tree masked' => ['app', 'tmpfs', [
                '/tmp:rw,nosuid,noexec,size=16m,mode=1777',
                '/run:rw,nosuid,noexec,size=1m',
                '/usr/local/var/run:rw,nosuid,noexec,size=1m',
                '/var/www/html/storage:rw,nosuid,noexec,size=16m,uid=33,gid=33',
                '/var/www/html/bootstrap/cache:rw,nosuid,noexec,size=2m,uid=33,gid=33',
            ], 'app_tmpfs_targets'],
            'build secrets' => ['app', 'build', [
                'context' => '/review/checkout',
                'dockerfile' => 'infra/staging/Dockerfile',
                'secrets' => ['bad'],
            ], 'app_build_boundary'],
            'custom host mode' => ['web', 'network_mode', 'host', 'web_forbidden_network_mode'],
            'capability readded' => ['app', 'cap_add', ['SYS_ADMIN'], 'app_forbidden_cap_add'],
            'unsafe extra security option' => ['web', 'security_opt',
                ['no-new-privileges:true', 'seccomp:unconfined'], 'web_privilege_escalation'],
            'capability drop weakened' => ['web', 'cap_drop',
                ['ALL', 'NET_ADMIN'], 'web_capabilities'],
            'malicious app command' => ['app', 'command',
                ['php', 'artisan', 'migrate', '--force'], 'app_unreviewed_command'],
            'web entrypoint replaced' => ['web', 'entrypoint',
                ['sh', '-c', 'sleep 3600'], 'web_unreviewed_entrypoint'],
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

    public function test_build_extensions_and_attachable_or_external_network_are_denied(): void
    {
        $extensions = [
            'target' => 'debug',
            'additional_contexts' => ['other' => 'https://example.invalid/example.git'],
            'dockerfile_inline' => 'FROM scratch',
            'cache_from' => ['example.invalid/external-cache'],
            'tags' => ['example.invalid/public-image'],
            'pull' => true,
        ];
        foreach ($extensions as $name => $value) {
            foreach (['app', 'web'] as $service) {
                $model = self::validModel();
                $model['services'][$service]['build'][$name] = $value;
                self::assertContains(
                    $service.'_build_boundary',
                    self::policy($model),
                    $service.': '.$name,
                );
            }
        }

        foreach ([
            'attachable' => true,
            'external' => true,
            'driver_opts' => ['com.docker.network.bridge.enable_ip_masquerade' => 'true'],
            'driver' => 'overlay',
            'name' => 'another-organization_private',
        ] as $name => $value) {
            $model = self::validModel();
            $model['networks']['crm_private'][$name] = $value;
            self::assertContains('network_not_private', self::policy($model), $name);
        }
    }

    public function test_only_harmless_normalized_network_defaults_are_accepted(): void
    {
        $model = self::validModel();
        $model['networks']['crm_private'] += [
            'driver' => 'bridge',
            'attachable' => false,
            'external' => false,
            'enable_ipv4' => true,
            'enable_ipv6' => false,
            'driver_opts' => [],
            'ipam' => [],
            'labels' => [],
        ];
        self::assertSame([], self::policy($model));

        $model['networks']['crm_private']['driver_opts'] = [
            'com.docker.network.bridge.enable_ip_masquerade' => 'true',
        ];
        self::assertContains('network_not_private', self::policy($model));
    }

    public function test_malformed_service_shape_is_denied_without_php_type_errors(): void
    {
        foreach ([null, 'not-a-service', false, 0, []] as $malformed) {
            $model = self::validModel();
            $model['services']['app'] = $malformed;
            $result = self::policy($model);
            self::assertNotSame([], $result);
            if (!is_array($malformed)) {
                self::assertContains('invalid_service_shape', $result);
            }
        }
    }

    public function test_actual_cli_accepts_only_review_fixture_and_never_echoes_rejected_input(): void
    {
        $root = realpath(dirname(__DIR__, 2));
        self::assertIsString($root);
        $script = $root.'/scripts/ci/verify-staging-rendered-compose.php';
        $model = self::validModel();
        foreach (['app', 'web'] as $service) {
            $model['services'][$service]['build']['context'] = $root;
        }

        $run = static function (string $input) use ($script, $root): \Symfony\Component\Process\Process {
            $process = new \Symfony\Component\Process\Process([PHP_BINARY, $script], $root);
            $process->setInput($input);
            $process->run();

            return $process;
        };

        $valid = $run(json_encode($model, JSON_THROW_ON_ERROR));
        self::assertSame(0, $valid->getExitCode(), $valid->getErrorOutput());
        self::assertStringContainsString('DRAFT/NO-DEPLOY', $valid->getOutput());

        $model['services']['app']['environment']['DB_HOST'] = 'SYNTHETIC_DO_NOT_ECHO_265';
        $denied = $run(json_encode($model, JSON_THROW_ON_ERROR));
        self::assertSame(1, $denied->getExitCode());
        self::assertSame('', $denied->getOutput());
        self::assertStringContainsString('NO-GO', $denied->getErrorOutput());
        self::assertStringNotContainsString('SYNTHETIC_DO_NOT_ECHO_265', $denied->getErrorOutput());

        $invalid = $run('{broken-json');
        self::assertSame(1, $invalid->getExitCode());
        self::assertStringContainsString('NO-GO', $invalid->getErrorOutput());

        $oversized = $run(str_repeat('x', 262145));
        self::assertSame(2, $oversized->getExitCode());
        self::assertStringContainsString('NO-GO', $oversized->getErrorOutput());
    }

    public function test_rendered_policy_is_not_deployed_as_part_of_application_image(): void
    {
        $dockerfile = file_get_contents(dirname(__DIR__, 2).'/infra/staging/Dockerfile');
        self::assertIsString($dockerfile);
        self::assertStringNotContainsString('COPY scripts/', $dockerfile);
        self::assertStringNotContainsString('COPY tests/', $dockerfile);
    }
}
