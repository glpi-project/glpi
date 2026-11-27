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

namespace tests\units\Glpi\Controller\Security;

use Glpi\Controller\Security\CspPluginsController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\CspTesterTrait;
use Glpi\Tests\DbTestCase;

class CspPluginsControllerTest extends DbTestCase
{
    use CspTesterTrait;

    public function testPageListsIncompatiblePlugins(): void
    {
        $this->login();
        $this->setActivePlugins(['tester', 'cspnotcompliant', 'cspexplicitlynotcompliant']);

        // The page header cannot be rendered with the fake plugins active, as they have no files.
        global $HEADER_LOADED;
        $HEADER_LOADED = true;

        $content = (new CspPluginsController())()->getContent();

        $this->assertStringContainsString('Incompatible plugins on this instance', $content);
        $this->assertStringContainsString('Send this page to your plugin maintainers.', $content);
        $this->assertStringContainsString('<td>cspnotcompliant</td>', $content);
        $this->assertStringContainsString('<td>CSP tester</td>', $content);
        $this->assertStringContainsString('href="https://example.org/cspnotcompliant"', $content);
        $this->assertStringContainsString('<td>cspexplicitlynotcompliant</td>', $content);
        $this->assertStringContainsString('<td>2.0.0</td>', $content);
        $this->assertStringNotContainsString('<td>tester</td>', $content);

        // Unsafe homepage URLs are not displayed
        $this->assertStringNotContainsString('javascript:alert(1)', $content);

        // Instructions are displayed
        $this->assertStringContainsString('Instructions for plugin developers', $content);
        $this->assertStringContainsString('&lt;script nonce="{{ csp_nonce() }}"&gt;', $content);
    }

    public function testPageWithoutIncompatiblePlugins(): void
    {
        $this->login();
        $this->setActivePlugins(['tester']);

        $content = (new CspPluginsController())()->getContent();

        $this->assertStringContainsString('All active plugins are compatible with the CSP: it is enabled.', $content);
        $this->assertStringNotContainsString('Incompatible plugins on this instance', $content);
        $this->assertStringContainsString('Instructions for plugin developers', $content);
    }

    public function testPageRequiresConfigUpdateRight(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['config'] = READ;

        $this->expectException(AccessDeniedHttpException::class);
        (new CspPluginsController())();
    }
}
