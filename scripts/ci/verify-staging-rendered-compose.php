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
        $privateNetwork = is_array($networks) ? ($networks['crm_private'] ?? null) : null;
        $require(
            is_array($networks) && $keys($networks) === ['crm_loopback', 'crm_private']
            && is_array($privateNetwork)
            && ($privateNetwork['internal'] ?? null) === true
            && array_diff(
                array_keys($privateNetwork),
                ['internal', 'name', 'driver', 'attachable', 'external',
                    'driver_opts', 'ipam', 'labels', 'enable_ipv4', 'enable_ipv6'],
            ) === []
            && (!isset($privateNetwork['name'])
                || $privateNetwork['name'] === 'imagine-crm-stage-review_crm_private')
            && (!isset($privateNetwork['driver']) || $privateNetwork['driver'] === 'bridge')
            && (!isset($privateNetwork['attachable']) || $privateNetwork['attachable'] === false)
            && (!isset($privateNetwork['external']) || $privateNetwork['external'] === false)
            && (!isset($privateNetwork['enable_ipv4']) || $privateNetwork['enable_ipv4'] === true)
            && (!isset($privateNetwork['enable_ipv6']) || $privateNetwork['enable_ipv6'] === false)
            && (!isset($privateNetwork['driver_opts']) || $privateNetwork['driver_opts'] === [])
            && (!isset($privateNetwork['labels']) || $privateNetwork['labels'] === [])
            && (!isset($privateNetwork['ipam']) || $privateNetwork['ipam'] === []
                || $privateNetwork['ipam'] === ['driver' => 'default', 'config' => []]),
            'network_not_private',
        );

        // The host-facing Nginx network is distinct from the backend.
        // Only Nginx is allowed on it, with a loopback binding and NAT
        // masquerade disabled. This is NOT proof of host firewall policy.
        $ingressNetwork = is_array($networks) ? ($networks['crm_loopback'] ?? null) : null;
        $require(
            is_array($ingressNetwork)
            && array_diff(
                array_keys($ingressNetwork),
                ['name', 'driver', 'driver_opts', 'internal', 'attachable',
                    'external', 'ipam', 'labels', 'enable_ipv4', 'enable_ipv6'],
            ) === []
            && ($ingressNetwork['name'] ?? null) === 'imagine-crm-stage-review_crm_loopback'
            && ($ingressNetwork['driver'] ?? null) === 'bridge'
            && ($ingressNetwork['driver_opts'] ?? null) === [
                'com.docker.network.bridge.enable_ip_masquerade' => 'false',
                'com.docker.network.bridge.host_binding_ipv4' => '127.0.0.1',
            ]
            && (!isset($ingressNetwork['internal']) || $ingressNetwork['internal'] === false)
            && (!isset($ingressNetwork['attachable']) || $ingressNetwork['attachable'] === false)
            && (!isset($ingressNetwork['external']) || $ingressNetwork['external'] === false)
            && (!isset($ingressNetwork['enable_ipv4']) || $ingressNetwork['enable_ipv4'] === true)
            && (!isset($ingressNetwork['enable_ipv6']) || $ingressNetwork['enable_ipv6'] === false)
            && (!isset($ingressNetwork['labels']) || $ingressNetwork['labels'] === [])
            && (!isset($ingressNetwork['ipam']) || $ingressNetwork['ipam'] === []
                || $ingressNetwork['ipam'] === ['driver' => 'default', 'config' => []]),
            'network_loopback_boundary',
        );

        $services = $model['services'] ?? null;
        if (!is_array($services) || $keys($services) !== ['app', 'web']) {
            return [...$fail, 'unexpected_service_set'];
        }
        if (!is_array($services['app']) || !is_array($services['web'])) {
            return [...$fail, 'invalid_service_shape'];
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
            sort($serviceNetworks, SORT_STRING);
            $require(
                $serviceNetworks === ($name === 'app'
                    ? ['crm_private'] : ['crm_loopback', 'crm_private']),
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
            // Exact non-privileged health probes: no shell for PHP, local /up
            // for Nginx, no external targets or custom commands.
            $health = $svc['healthcheck'] ?? null;
            $expectedProbe = $name === 'app'
                ? ['CMD', 'php', '-r', "\$s=@fsockopen('127.0.0.1',9000,\$e,\$m,2); exit(\$s?0:1);"]
                : ['CMD-SHELL', 'wget -q -T 3 -O /dev/null http://127.0.0.1:8080/up'];
            $durationIs = static function (mixed $value, int $seconds): bool {
                return $value === $seconds.'s' || $value === $seconds * 1000000000;
            };
            $require(
                is_array($health)
                && $keys($health) === ['interval', 'retries', 'start_period', 'test', 'timeout']
                && ($health['test'] ?? null) === $expectedProbe
                && ($health['retries'] ?? null) === 2
                && $durationIs($health['interval'] ?? null, 5)
                && $durationIs($health['timeout'] ?? null, $name === 'app' ? 3 : 4)
                && $durationIs($health['start_period'] ?? null, 10),
                $name.'_unreviewed_healthcheck',
            );
            $build = $svc['build'] ?? null;
            $require(
                is_array($build)
                && ($build['context'] ?? null) === $repositoryRoot
                && ($build['dockerfile'] ?? null) ===
                   ($name === 'app'
                    ? 'infra/staging/Dockerfile'
                    : 'infra/staging/Dockerfile.web')
                && $keys($build) === ['context', 'dockerfile'],
                $name.'_build_boundary',
            );
            $require(
                !array_key_exists('image', $svc) || $svc['image'] === null,
                $name.'_unreviewed_image',
            );
            $tmpfs = $svc['tmpfs'] ?? [];
            $expectedTmpfs = $name === 'app'
                ? ['/tmp', '/run', '/usr/local/var/run',
                   '/var/www/html/storage/framework/cache',
                   '/var/www/html/storage/framework/sessions',
                   '/var/www/html/storage/framework/views',
                   '/var/www/html/storage/logs',
                   '/var/www/html/bootstrap/cache']
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
            is_array($depends) && $keys($depends) === ['app']
            && is_array($depends['app'])
            && ($depends['app']['condition'] ?? null) === 'service_healthy'
            && ($depends['app']['required'] ?? true) === true
            && array_diff(array_keys($depends['app']), ['condition', 'required']) === [],
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
        if (!is_array($data) || !is_string($root)) {
            throw new RuntimeException('Unsafe staging candidate.');
        }
        $codes = StagingRenderedComposePolicy::violations($data, $root);
        if ($codes !== []) {
            // Codes are static literals produced by the policy itself,
            // never input values, environment content, paths or credentials.
            fwrite(STDERR, 'F3-265 NO-GO policy codes: '.implode(',', $codes)."\n");
            if (in_array('network_not_private', $codes, true)) {
                // Only boolean shape probes. Never print a network name,
                // untrusted option key, path, address or user-provided value.
                $net = $data['networks']['crm_private'] ?? null;
                fwrite(STDERR, 'F3-265 network flags: map='. (int) is_array($net)
                    .',internal='.(int) (($net['internal'] ?? null) === true)
                    .',known-keys='.(int) (is_array($net) &&
                        array_diff(array_keys($net), ['internal', 'name']) === [])
                    .',name='.(int) (($net['name'] ?? null)
                        === 'imagine-crm-stage-review_crm_private')."\n");
            }
            exit(1);
        }
        fwrite(STDOUT, "F3-264 PASS: rendered Compose is loopback-only, no host mounts, no external DB or live auth; DRAFT/NO-DEPLOY.\n");
    } catch (Throwable) {
        // Never echo environment values, absolute paths or secret-like inputs.
        fwrite(STDERR, "F3-264 NO-GO: rendered staging boundary denied.\n");
        exit(1);
    }
}
