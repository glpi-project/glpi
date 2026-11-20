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

namespace Glpi\Tests;

use Glpi\Security\ContentSecurityPolicy\CspState;
use Plugin;
use ReflectionProperty;

/**
 * Helpers to control the CSP state, by changing the active plugins list.
 *
 * The fake plugins defined in `tests/fixtures/csp_plugins.php` can be used.
 * The initial active plugins list is restored after each test by `GLPITestCase::tearDown()`.
 */
trait CspTesterTrait
{
    /**
     * @param array<string> $plugins
     */
    protected function setActivePlugins(array $plugins): void
    {
        require_once FIXTURE_DIR . '/csp_plugins.php';

        $property = new ReflectionProperty(Plugin::class, 'activated_plugins');
        $property->setValue(null, $plugins);
    }

    /**
     * Disable the CSP, by activating a plugin that is not compatible with it.
     */
    protected function disableCsp(): void
    {
        $this->setActivePlugins([...Plugin::getPlugins(), 'cspnotcompliant']);
        $this->assertFalse(CspState::isEnabled());
    }
}
