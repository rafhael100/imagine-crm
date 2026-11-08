#!/usr/bin/env php
<?php

declare(strict_types=1);

// SEG-REC-02 TEST-ONLY FAKE CLI. No Docker, PostgreSQL, network or real data.
// PHPUnit copies this script into a private temporary directory under the
// names "docker" and "psql". It must never be used for provisioning/deploy.

$tool = basename((string) ($argv[0] ?? ''));
$args = array_slice($argv, 1);
$case = getenv('SEG_REC_02_CASE') ?: 'ok';
$log = getenv('SEG_REC_02_LOG');
if (is_string($log) && $log !== '') {
    file_put_contents($log, json_encode(['tool' => $tool, 'args' => $args], JSON_THROW_ON_ERROR).PHP_EOL, FILE_APPEND);
}

$expectedId = '0123456789ab';
$cluster = '7642745692381711784';
$ok = 'imagine_crm_ci|postgres|'.$cluster;

if ($tool === 'docker') {
    if (($args[0] ?? '') === 'ps') {
        if ($case === 'docker-ps-error') {
            exit(8);
        }
        if ($case === 'missing-container') {
            exit(0);
        }
        if ($case === 'duplicate-containers') {
            echo $expectedId."\n".'aabbccddeeff'."\n";
            exit(0);
        }
        if ($case === 'malformed-container-id') {
            echo "not-a-docker-id\n";
            exit(0);
        }
        echo $expectedId."\n";
        exit(0);
    }

    if (($args[0] ?? '') === 'inspect' && ($args[2] ?? '') !== '') {
        if ($case === 'docker-inspect-error') {
            exit(9);
        }
        $status = $case === 'stopped-container' ? 'false' : 'true';
        $image = $case === 'wrong-image' ? 'postgres:15' : 'postgres:16';
        $mount = $case === 'bind-mount' ? 'bind;' : '';
        $ip = $case === 'no-container-ip' ? '' : '172.18.0.2';
        echo $status.'|'.$image.'|'.$mount.'|'.$ip."\n";
        exit(0);
    }

    if (($args[0] ?? '') === 'exec' && ($args[1] ?? '') === $expectedId) {
        if ($case === 'inside-query-error') {
            exit(10);
        }
        if ($case === 'wrong-db') {
            echo "imagine_crm_prod|postgres|".$cluster."\n";
        } elseif ($case === 'wrong-role') {
            echo "imagine_crm_ci|superuser|".$cluster."\n";
        } elseif ($case === 'malformed-system-id') {
            echo "imagine_crm_ci|postgres|not-a-cluster\n";
        } else {
            echo $ok."\n";
        }
        exit(0);
    }

    exit(90); // Never emulate Docker commands outside the read-only preflight.
}

if ($tool === 'psql') {
    if ($case === 'outside-query-error') {
        exit(11);
    }
    if ($case === 'cluster-mismatch') {
        echo "imagine_crm_ci|postgres|7642745692381711785\n";
    } else {
        echo $ok."\n";
    }
    exit(0);
}

exit(91);
