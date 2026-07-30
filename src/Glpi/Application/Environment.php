<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Application;

use Agent;
use Auth;
use GLPINetwork;
use Planning;
use Psr\Log\LogLevel;
use RSSFeed;
use UnexpectedValueException;
use Webhook;

use function Safe\define;

enum Environment: string
{
    /**
     * Production environment.
     */
    case PRODUCTION = 'production';

    /**
     * Staging environment.
     * Suitable for pre-production servers and customer acceptance tests.
     */
    case STAGING = 'staging';

    /**
     * Testing environment.
     * Suitable for CI runners, quality control and internal acceptance tests.
     */
    case TESTING = 'testing';

    /**
     * E2E testing environment.
     * Suitable for CI runners and local test execution.
     */
    case E2E = 'e2e_testing';

    /**
     * Development environment.
     * Suitable for developer machines and development servers.
     */
    case DEVELOPMENT = 'development';

    public static function isSet(): bool
    {
        return defined('GLPI_ENVIRONMENT_TYPE');
    }

    /**
     * @return array
     */
    public static function getValues()
    {
        $values = [];
        foreach (self::cases() as $env) {
            $values[] = $env->value;
        }
        return $values;
    }

    public static function get(): self
    {
        // Read GLPI_ENVIRONMENT_TYPE if it exist
        if (defined('GLPI_ENVIRONMENT_TYPE')) {
            $value = GLPI_ENVIRONMENT_TYPE;
        } else {
            // In some rare case, the kernel may not be booted yet and thus we must
            // rely on global vars to find the env value.
            // If no value is given, we fallback to the production env.
            $value = $_ENV['GLPI_ENVIRONMENT_TYPE']
                ?? $_SERVER['GLPI_ENVIRONMENT_TYPE']
                ?? self::PRODUCTION->value
            ;
        }

        // Avoid a crash if an unexpected value is supplied.
        if (!is_string($value)) {
            $value = "";
        }

        // Try to load the given env, with a fallback to production.
        return self::tryFrom($value) ?? self::PRODUCTION->value;
    }

    public static function set(self $environment): void
    {
        define('GLPI_ENVIRONMENT_TYPE', $environment->value);
    }

    public static function validate(): void
    {
        // Store valid environments keys
        $allowed_keys = self::getValues();

        // Validate GLPI_ENVIRONMENT_TYPE if it exists.
        if (!in_array(GLPI_ENVIRONMENT_TYPE, $allowed_keys)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Invalid GLPI_ENVIRONMENT_TYPE constant value `%s`. Allowed values are: `%s`',
                    GLPI_ENVIRONMENT_TYPE,
                    implode('`, `', $allowed_keys)
                )
            );
        }
    }

    /**
     * See SystemConfigurator::computeConstants() for all available values that
     * can be overridden.
     */
    public function getConstantsOverride(string $root_dir): array
    {
        $test_token = getenv('TEST_TOKEN');

        return match ($this) {
            default => [],
            self::TESTING     => [
                'GLPI_CONFIG_DIR'               => $root_dir . '/tests/config',
                'GLPI_VAR_DIR'                  => $root_dir . '/tests/files' . (($test_token !== false && $test_token !== '' && $test_token > 1) ? "-$test_token" : ''),
                'GLPI_LOG_LVL'                  => LogLevel::DEBUG,
                'GLPI_STRICT_ENV'               => true,
                'GLPI_SERVERSIDE_URL_ALLOWED_PRIVATE_NETWORKS_CONTEXTS' => [
                    // Enable all context for test suite
                    Agent::class,
                    Auth::class,
                    GLPINetwork::class,
                    Planning::class,
                    RSSFeed::class,
                    Webhook::class,
                ],
                'GLPI_MARKETPLACE_DIR'          => $root_dir . '/tests/fixtures/marketplace',
                'GLPI_PLUGINS_DIRECTORIES'      => [
                    $root_dir . '/plugins',
                    '{GLPI_MARKETPLACE_DIR}',
                    $root_dir . '/tests/fixtures/plugins',
                ],
            ],
            self::DEVELOPMENT => [
                'GLPI_LOG_LVL'                       => LogLevel::DEBUG,
                'GLPI_STRICT_ENV'                    => true,
                'GLPI_WEBHOOK_ALLOW_RESPONSE_SAVING' => '1',
            ],
            self::E2E => [
                'GLPI_CONFIG_DIR'          => $root_dir . '/tests/e2e/glpi_config',
                'GLPI_VAR_DIR'             => $root_dir . '/tests/e2e/glpi_files',
                'GLPI_LOG_LVL'             => LogLevel::DEBUG,
                'GLPI_STRICT_ENV'          => true,
                'GLPI_PLUGINS_DIRECTORIES' => [
                    $root_dir . '/plugins',
                    $root_dir . '/tests/fixtures/plugins',
                ],
            ],
        };
    }

    /**
     * Will the files of this environment change ?
     * This may affect which cache we decide to set (twig, http cache on the
     * generated css and locale, ...)
     */
    public function shouldExpectResourcesToChange(string $root_dir = GLPI_ROOT): bool
    {
        // Only production/staging environment are considered as environments
        // where resources are not supposed to change.
        // In other environments, we must watch for changes.
        if (
            $this === self::TESTING
            || $this === self::DEVELOPMENT
            || $this === self::E2E
        ) {
            return true;
        }

        // If GLPI is install direcly by cloning the git repository, then it is preferable to check
        // resources state.
        if (is_dir($root_dir . '/.git')) {
            return true;
        }

        return false;
    }

    /**
     * Should the HTTP response contains extra headers to force the caching on the browser side ?
     */
    public function shouldForceExtraBrowserCache(): bool
    {
        // Prevent intensive caching on dev env.
        return match ($this) {
            default           => true,
            self::DEVELOPMENT => false,
        };
    }

    public function shouldSetupTesterPlugin(): bool
    {
        // Specific for tests, should never be enabled anywhere else.
        return match ($this) {
            default           => false,
            self::TESTING     => true,
            self::E2E         => true,
        };
    }

    public function shouldEnableExtraDevAndDebugTools(): bool
    {
        // Specific for dev, should never be enabled anywhere else.
        return match ($this) {
            default           => false,
            self::DEVELOPMENT => true,
        };
    }

    public function shouldAddExtraE2EDataDuringInstallation(): bool
    {
        return
            $this->shouldAddExtraCypressDataDuringInstallation()
            || $this->shouldAddExtraPlaywrightDataDuringInstallation()
        ;
    }

    public function shouldAddExtraCypressDataDuringInstallation(): bool
    {
        // Note: this will be removed when we switch to playwright.
        return match ($this) {
            default       => false,
            self::TESTING => true,
        };
    }

    public function shouldAddExtraPlaywrightDataDuringInstallation(): bool
    {
        // Note: this is a temporary method, it should be replaced by a proper
        // seeder system.
        return match ($this) {
            default   => false,
            self::E2E => true,
        };
    }

    public function shouldEnableTestResources(): bool
    {
        return match ($this) {
            default   => false,
            self::E2E => true,
        };
    }
}
