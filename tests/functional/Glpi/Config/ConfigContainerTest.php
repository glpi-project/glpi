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

namespace tests\units\Glpi\Config;

use Glpi\Config\ConfigContainer;
use Glpi\Tests\GLPITestCase;
use Psr\Log\LogLevel;

final class ConfigContainerTest extends GLPITestCase
{
    public function testOffsetGetReturnsStoredValue(): void
    {
        $config = new ConfigContainer(['app_name' => 'GLPI', 'asset_types' => ['Computer']]);

        $this->assertSame('GLPI', $config['app_name']);
        $this->assertSame(['Computer'], $config['asset_types']);
        $this->assertNull($config['does_not_exist']);
    }

    public function testOffsetSetExistsAndUnset(): void
    {
        $config = new ConfigContainer([]);

        $config['foo'] = 'bar';
        $this->assertTrue(isset($config['foo']));
        $this->assertSame('bar', $config['foo']);

        $config[] = 'appended';
        $this->assertContains('appended', $config->getArrayCopy());

        unset($config['foo']);
        $this->assertFalse(isset($config['foo']));
    }

    public function testValuesAreRealArrays(): void
    {
        $config = new ConfigContainer(['languages' => ['fr_FR' => ['Français'], 'en_GB' => ['English']]]);

        $languages = $config['languages'];
        $this->assertIsArray($languages);
        $this->assertSame(['fr_FR', 'en_GB'], array_keys($languages));
        $this->assertSame('Français', $languages['fr_FR'][0]);
        $this->assertArrayHasKey('de_DE', array_merge($languages, ['de_DE' => ['Deutsch']]));
    }

    public function testNestedAppendPersists(): void
    {
        // The core relies on `$CFG_GLPI['xxx_types'][] = ...` (e.g. Plugin::registerClass).
        $config = new ConfigContainer(['asset_types' => ['Computer']]);

        $config['asset_types'][] = 'Monitor';
        $config['asset_types'][5] = 'Phone';

        $this->assertSame(['Computer', 'Monitor', 5 => 'Phone'], $config['asset_types']);
    }

    public function testNestedKeyWritePersists(): void
    {
        $config = new ConfigContainer(['javascript' => ['assets' => []]]);

        $config['javascript']['assets']['computer'] = ['dashboard'];

        $this->assertSame(['dashboard'], $config['javascript']['assets']['computer']);
    }

    public function testNestedWriteToMissingKeyAutoVivifies(): void
    {
        // The core relies on auto-vivification, e.g. DbUtils builds the
        // `glpitablesitemtype` registry with `$CFG_GLPI['glpitablesitemtype'][$type] = ...`
        // without initializing the key first.
        $config = new ConfigContainer([]);

        $config['glpitablesitemtype']['Computer'] = 'glpi_computers';
        $config['new_types'][] = 'Foo';

        $this->assertSame(['Computer' => 'glpi_computers'], $config['glpitablesitemtype']);
        $this->assertSame(['Foo'], $config['new_types']);
    }

    public function testCreateThenAppendIntoNewKey(): void
    {
        $config = new ConfigContainer([]);

        $config['pluginfoo_types'] = [];
        $config['pluginfoo_types'][] = 'GlpiPlugin\\Foo\\Bar';

        $this->assertSame(['GlpiPlugin\\Foo\\Bar'], $config['pluginfoo_types']);
    }

    public function testCountAndIteration(): void
    {
        $config = new ConfigContainer(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertCount(3, $config);

        $seen = [];
        foreach ($config as $key => $value) {
            $seen[$key] = $value;
        }
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $seen);
    }

    public function testJsonSerializeAndArrayCopy(): void
    {
        $data = ['a' => 1, 'b' => ['x' => 2]];
        $config = new ConfigContainer($data);

        $this->assertSame($data, $config->getArrayCopy());
        $this->assertSame(json_encode($data), json_encode($config));
    }

    public function testCloneIsIndependentSnapshot(): void
    {
        // SavedSearch_Alert relies on `clone $CFG_GLPI` to snapshot the config.
        $config = new ConfigContainer(['app_name' => 'GLPI']);
        $snapshot = clone $config;

        $config['app_name'] = 'CHANGED';

        $this->assertSame('GLPI', $snapshot['app_name']);
        $this->assertSame('CHANGED', $config['app_name']);
    }

    public function testDeprecatedKeyIsLoggedAsInfo(): void
    {
        // Integration: the E_USER_DEPRECATED emitted by Toolbox::deprecated()
        // flows to the GLPI logger as an INFO-level record.
        $config = new ConfigContainer(['languages' => ['fr_FR' => ['Français']]]);
        $config->deprecateKey('languages', 'Accessing languages is deprecated, use Foo instead.', '11.0');

        $reporting_level = \error_reporting(E_ALL);
        $value = $config['languages'];
        \error_reporting($reporting_level);

        $this->assertSame(['fr_FR' => ['Français']], $value);
        $this->hasPhpLogRecordThatContains(
            'Accessing languages is deprecated, use Foo instead.',
            LogLevel::INFO
        );
    }

    public function testDeprecatedKeyEmittedOnlyOncePerKey(): void
    {
        $config = new ConfigContainer(['languages' => ['fr_FR' => ['Français']]]);
        $config->deprecateKey('languages', 'deprecated', '11.0');

        $count = $this->countDeprecationsWhileReading(static function () use ($config): void {
            $config['languages'];
            $config['languages']; // second read must NOT emit again
            $config['languages'];
        });

        $this->assertSame(1, $count);
    }

    public function testNonDeprecatedKeyDoesNotEmitNotice(): void
    {
        $config = new ConfigContainer(['asset_types' => ['Computer']]);
        $config->deprecateKey('languages', 'deprecated', '11.0');

        $count = $this->countDeprecationsWhileReading(static function () use ($config): void {
            $config['asset_types'];
        });

        $this->assertSame(0, $count);
    }

    public function testDeprecationSkippedForFutureVersion(): void
    {
        $config = new ConfigContainer(['legacy' => 'value']);
        $config->deprecateKey('legacy', 'deprecated in the future', '99.0');

        $count = $this->countDeprecationsWhileReading(static function () use ($config): void {
            $config['legacy'];
        });

        $this->assertSame(0, $count);
    }

    public function testGetSafeConfigExcludesSecretsAndAppliesSession(): void
    {
        $config = new ConfigContainer([
            'url_base'    => 'https://glpi.test',
            'smtp_passwd' => 'secret',            // in Config::$undisclosedFields
            'admin_email' => 'admin@example.com', // only excluded when $safer
        ]);

        $_SESSION['glpiurl_base'] = 'https://session.override';

        $safe = $config->getSafeConfig();
        $this->assertArrayNotHasKey('smtp_passwd', $safe);
        $this->assertArrayHasKey('admin_email', $safe);
        $this->assertSame('https://session.override', $safe['url_base']);

        $safer = $config->getSafeConfig(true);
        $this->assertArrayNotHasKey('smtp_passwd', $safer);
        $this->assertArrayNotHasKey('admin_email', $safer);
    }

    /**
     * Count the E_USER_DEPRECATED notices emitted while running the given
     * callback, swallowing them so they don't reach the GLPI logger.
     */
    private function countDeprecationsWhileReading(callable $callback): int
    {
        $count = 0;
        \set_error_handler(
            static function (int $errno) use (&$count): bool {
                if ($errno === E_USER_DEPRECATED) {
                    $count++;
                }
                return true;
            }
        );
        $reporting_level = \error_reporting(E_ALL);
        try {
            $callback();
        } finally {
            \error_reporting($reporting_level);
            \restore_error_handler();
        }

        return $count;
    }
}
