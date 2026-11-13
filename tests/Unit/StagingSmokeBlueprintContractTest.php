<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3-263 checks the source of the candidate, not live Docker, TLS or VPS.
 * This test MUST NOT claim staging is deployed, verified or release-ready.
 */
final class StagingSmokeBlueprintContractTest extends TestCase
{
    public function test_compose_is_loopback_only_deny_only_and_has_no_data_service(): void
    {
        $compose = file_get_contents(
            dirname(__DIR__, 2).'/infra/staging/compose.yaml',
        );
        self::assertIsString($compose);
        foreach ([
            'name: imagine-crm-stage-review',
            'APP_ENV: production', 'APP_DEBUG: "false"',
            'CRM_AUTH_EXPERIMENTAL: "false"',
            'DB_CONNECTION: sqlite', 'DB_DATABASE: ":memory:"',
            'CACHE_STORE: array', 'SESSION_DRIVER: array',
            'QUEUE_CONNECTION: sync', 'MAIL_MAILER: array',
            'LOG_CHANNEL: stderr', 'read_only: true',
            'internal: true', 'cap_drop:', '- ALL',
            'no-new-privileges:true', '127.0.0.1:18080:8080',
            'dockerfile: infra/staging/Dockerfile',
            'dockerfile: infra/staging/Dockerfile.web',
        ] as $required) {
            self::assertStringContainsString($required, $compose, $required);
        }
        foreach ([
            '0.0.0.0:', '5432:', '5432"', '6379:', '6379"',
            'POSTGRES_PASSWORD', 'POSTGRES_USER', 'MYSQL_PASSWORD',
            'DB_PASSWORD', 'APP_KEY:', 'CRM_IAM_PASSWORD',
            'CRM_PROVISION_PASSWORD', 'AUTH_GUARD:',
            'env_file:', 'privileged:', 'network_mode: host',
            'volumes:', '../../public', './nginx.conf:',
            'docker.sock', 'secrets:', 'volumes_from:',
            'command: php artisan migrate', 'php artisan serve',
            'image: postgres', 'image: redis',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $compose);
        }
        self::assertSame(1, substr_count($compose, '127.0.0.1:18080:8080'));
        self::assertSame(2, substr_count($compose, 'read_only: true'));
    }

    public function test_dockerfile_contains_only_application_sources_not_dev_fixtures_or_secrets(): void
    {
        $file = file_get_contents(
            dirname(__DIR__, 2).'/infra/staging/Dockerfile',
        );
        self::assertIsString($file);
        foreach ([
            'FROM php:8.4-fpm-bookworm',
            'pdo_pgsql pdo_sqlite',
            'composer install --no-dev --no-scripts --classmap-authoritative',
            'composer dump-autoload --no-dev --classmap-authoritative --no-scripts',
            'COPY app/ app/', 'COPY config/ config/',
            'COPY database/migrations/ database/migrations/',
            'COPY routes/ routes/', 'COPY public/ public/',
            'COPY artisan artisan', 'USER www-data',
        ] as $required) {
            self::assertStringContainsString($required, $file, $required);
        }
        foreach ([
            'COPY . .', 'COPY .env', 'COPY tests/', 'COPY docs/',
            'COPY scripts/', 'COPY database/experimental-migrations',
            'APP_KEY=', 'ARG PASSWORD', 'ARG TOKEN',
            'RUN php artisan migrate', 'RUN php artisan serve',
            'EXPOSE 80', 'EXPOSE 443',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $file);
        }
    }

    public function test_nginx_artifact_copies_only_reviewed_public_files_and_config(): void
    {
        $file = file_get_contents(
            dirname(__DIR__, 2).'/infra/staging/Dockerfile.web',
        );
        self::assertIsString($file);
        foreach ([
            'FROM nginx:1.29-alpine',
            'COPY infra/staging/nginx.conf /etc/nginx/nginx.conf',
            'COPY public/ /var/www/html/public/',
            'USER nginx',
            'EXPOSE 8080',
        ] as $required) {
            self::assertStringContainsString($required, $file);
        }
        foreach ([
            'COPY . .', 'COPY .env', 'COPY tests/',
            'COPY docs/', 'COPY database/', 'COPY app/',
            'RUN apk add', 'RUN php artisan',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $file);
        }
        self::assertDoesNotMatchRegularExpression('/^EXPOSE 80$/m', $file);
    }

    public function test_nginx_never_publishes_php_files_except_front_controller(): void
    {
        $config = file_get_contents(
            dirname(__DIR__, 2).'/infra/staging/nginx.conf',
        );
        self::assertIsString($config);
        foreach ([
            'listen 8080;', 'server_tokens off;',
            'location = /index.php', 'fastcgi_pass app:9000;',
            'location ~ \.php(?:/|$) { return 404; }',
            'location ~ /\. { deny all; }',
            'X-Content-Type-Options nosniff',
            'X-Frame-Options DENY',
            'Cache-Control "no-store"',
            'client_max_body_size 32k;',
        ] as $required) {
            self::assertStringContainsString($required, $config, $required);
        }
        self::assertStringNotContainsString('listen 443', $config);
        self::assertStringNotContainsString('autoindex on', $config);
        self::assertStringNotContainsString('allow all', $config);
    }
}
