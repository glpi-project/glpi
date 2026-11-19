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

namespace tests\units\Glpi\Security\ContentSecurityPolicy;

use Glpi\Security\ContentSecurityPolicy\CspState;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Plugin;
use ReflectionProperty;

class CspStateTest extends GLPITestCase
{
    /**
     * @var array<string>
     */
    private array $initial_plugins;

    public function setUp(): void
    {
        parent::setUp();

        require_once FIXTURE_DIR . '/csp_plugins.php';

        $this->initial_plugins = Plugin::getPlugins();
    }

    public function tearDown(): void
    {
        $this->setActivePlugins($this->initial_plugins);

        parent::tearDown();
    }

    public static function pluginsProvider(): iterable
    {
        yield 'no plugins' => [
            'plugins'  => [],
            'expected' => true,
        ];
        yield 'compliant plugins' => [
            'plugins'  => ['tester', 'cspcompliant'],
            'expected' => true,
        ];
        yield 'plugin without the `csp_compliant` information' => [
            'plugins'  => ['cspcompliant', 'cspnotcompliant'],
            'expected' => false,
        ];
        yield 'plugin explicitly not compliant' => [
            'plugins'  => ['cspexplicitlynotcompliant', 'cspcompliant'],
            'expected' => false,
        ];
        yield 'plugin with a truthy but not `true` value' => [
            'plugins'  => ['cspnotstrictlycompliant'],
            'expected' => false,
        ];
    }

    /**
     * @param array<string> $plugins
     */
    #[DataProvider('pluginsProvider')]
    public function testIsEnabled(array $plugins, bool $expected): void
    {
        $this->setActivePlugins($plugins);

        $this->assertSame($expected, CspState::isEnabled());
    }

    public function testStateIsUpdatedWhenActivePluginsChange(): void
    {
        $this->setActivePlugins(['cspcompliant']);
        $this->assertTrue(CspState::isEnabled());

        $this->setActivePlugins(['cspcompliant', 'cspnotcompliant']);
        $this->assertFalse(CspState::isEnabled());

        $this->setActivePlugins(['cspcompliant']);
        $this->assertTrue(CspState::isEnabled());
    }

    public function testTestsRunWithCspEnabled(): void
    {
        // The tester plugin is compliant, so the test suite runs with the CSP enabled.
        $this->assertTrue(CspState::isEnabled());
    }

    /**
     * @param array<string> $plugins
     */
    private function setActivePlugins(array $plugins): void
    {
        $property = new ReflectionProperty(Plugin::class, 'activated_plugins');
        $property->setValue(null, $plugins);
    }
}
