<?php

declare(strict_types=1);

/**
 * F3-264: validate the *normalized* Docker Compose model, not YAML snippets.
 * A pure, dependency-free CLI policy used ONLY for DRAFT staging review.
 * This is NOT an approval to build, pull, run, deploy or connect to the VPS.
 */
final class StagingRenderedComposePolicy
{
    /** @return list<string> Static denials, never credentials or input values */
    public static function violations(array $model, string $repositoryRoot): array
    {
        $fail = [];
        $require = static function (bool $okay, string $code) use (&$fail): void {
            if (!$okay) {
                $fail[] = $code;
            }
        };
        $keys = static function (array $items): array {
            $k = array_keys($items);
            sort($k, SORT_STRING);
            return $k;
        };

        $require(
            $keys($model) === ['name', 'networks', 'services'],
            'unexpected_top_level_resources',
        );
        $require(($model['name'] ?? null) === 'imagine-crm-stage-review', 'project_name');
        $networks = $model['networks'] ?? null;
        $require(
            is_array($networks) && $keys($networks) === ['crm_private']
            && ($networks['crm_private']['internal'] ?? null) === true,
            'network_not_private',
        );

        $services = $model['services'] ?? null;
        if (!is_array($services) || $keys($services) !== ['app', 'web']) {
            return [...$fail, 'unexpected_service_set'];
        }

        foreach (['app', 'web'] as $name) {
            $svc = $services[$name];
            if (!is_array($svc)) {
                $fail[] = 'invalid_'.$name.'_service';
                continue;
            }
            $require(($svc['read_only'] ?? null) === true, $name.'_writable_root');
            $require(
                ($svc['cap_drop'] ?? null) === ['ALL'],
                $name.'_capabilities',
            );
            $require(
                ($svc['security_opt'] ?? null) === ['no-new-privileges:true'],
                $name.'_privilege_escalation',
            );
            $require(
                ($svc['user'] ?? null) === ($name === 'app' ? '33:33' : '101:101'),
                $name.'_runtime_user',
            );
            $serviceNetworks = $svc['networks'] ?? [];
            $serviceNetworks = is_array($serviceNetworks)
                ? (array_is_list($serviceNetworks) ? $serviceNetworks : array_keys($serviceNetworks))
                : [];
            $require(
                $serviceNetworks === ['crm_private'],
                $name.'_unapproved_network',
            );
            foreach ([
                'volumes', 'devices', 'secrets', 'configs', 'env_file', 'network_mode',
                'privileged', 'pid', 'ipc', 'extra_hosts', 'dns', 'dns_search',
                'cap_add', 'volumes_from', 'device_cgroup_rules', 'userns_mode',
                'extends', 'profiles', 'links', 'sysctls', 'hostname',
                'ports' /* app only, web handled below */,
            ] as $forbidden) {
                if ($forbidden === 'ports' && $name === 'web') {
                    continue;
                }
                $require(
                    !array_key_exists($forbidden, $svc)
                    || $svc[$forbidden] === null
                    || $svc[$forbidden] === []
                    || $svc[$forbidden] === false,
                    $name.'_forbidden_'.$forbidden,
                );
            }
            // A changed entrypoint/command could invoke migrations or
            // bypass the reviewed nginx/FPM startup without opening a port.
            $require(
                ($name === 'app'
                    ? !isset($svc['entrypoint']) || $svc['entrypoint'] === []
                    : ($svc['entrypoint'] ?? null) === ['nginx', '-g', 'daemon off;']),
                $name.'_unreviewed_entrypoint',
            );
            $require(
                !isset($svc['command']) || $svc['command'] === [],
                $name.'_unreviewed_command',
            );
            $build = $svc['build'] ?? null;
            $require(
                is_array($build)
                && ($build['context'] ?? null) === $repositoryRoot
                && ($build['dockerfile'] ?? null) ===
                   ($name === 'app'
                    ? 'infra/staging/Dockerfile'
                    : 'infra/staging/Dockerfile.web')
                && !array_key_exists('args', $build)
                && !array_key_exists('secrets', $build)
                && !array_key_exists('ssh', $build)
                && !array_key_exists('network', $build),
                $name.'_build_boundary',
            );
            $require(
                !array_key_exists('image', $svc) || $svc['image'] === null,
                $name.'_unreviewed_image',
            );
            $tmpfs = $svc['tmpfs'] ?? [];
            $expectedTmpfs = $name === 'app'
                ? ['/tmp', '/run', '/usr/local/var/run',
                   '/var/www/html/storage', '/var/www/html/bootstrap/cache']
                : ['/tmp', '/var/cache/nginx'];
            $actualTmpfs = [];
            if (is_array($tmpfs)) {
                foreach ($tmpfs as $mount) {
                    if (is_string($mount)) {
                        $actualTmpfs[] = explode(':', $mount, 2)[0];
                    } elseif (is_array($mount)) {
                        $actualTmpfs[] = $mount['target'] ?? $mount['destination'] ?? '';
                    }
                }
            }
            sort($actualTmpfs, SORT_STRING);
            sort($expectedTmpfs, SORT_STRING);
            $require($actualTmpfs === $expectedTmpfs, $name.'_tmpfs_targets');
        }

        $app = $services['app'];
        $web = $services['web'];
        $expectedEnv = [
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
        ];
        $env = $app['environment'] ?? null;
        $require(
            is_array($env) && $keys($env) === $keys($expectedEnv)
            && count(array_diff_assoc($expectedEnv, $env)) === 0,
            'app_environment_mismatch',
        );
        $require(
            !array_key_exists('environment', $web)
            || $web['environment'] === null
            || $web['environment'] === [],
            'web_environment',
        );
        $ports = $web['ports'] ?? [];
        $port = is_array($ports) && count($ports) === 1 ? array_values($ports)[0] : null;
        $require(
            is_array($port)
            && ($port['host_ip'] ?? null) === '127.0.0.1'
            && (string) ($port['published'] ?? '') === '18080'
            && (int) ($port['target'] ?? 0) === 8080
            && ($port['protocol'] ?? 'tcp') === 'tcp'
            && ($port['mode'] ?? 'ingress') === 'ingress',
            'non_loopback_or_extra_ports',
        );
        $depends = $web['depends_on'] ?? [];
        $require(
            is_array($depends) && $keys($depends) === ['app'],
            'web_service_dependency',
        );

        return array_values(array_unique($fail));
    }
}

if (isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1) {
        fwrite(STDERR, "F3-264 NO-GO: CLI-only policy.\n");
        exit(2);
    }
    $raw = stream_get_contents(STDIN, 262145);
    if (!is_string($raw) || strlen($raw) > 262144) {
        fwrite(STDERR, "F3-264 NO-GO: missing or oversized Compose model.\n");
        exit(2);
    }
    try {
        $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        $root = realpath(__DIR__.'/../..');
        if (!is_array($data) || !is_string($root)
            || StagingRenderedComposePolicy::violations($data, $root) !== []) {
            throw new RuntimeException('Unsafe staging candidate.');
        }
        fwrite(STDOUT, "F3-264 PASS: rendered Compose is loopback-only, no host mounts, no external DB or live auth; DRAFT/NO-DEPLOY.\n");
    } catch (Throwable) {
        // Never echo environment values, absolute paths or secret-like inputs.
        fwrite(STDERR, "F3-264 NO-GO: rendered staging boundary denied.\n");
        exit(1);
    }
}
