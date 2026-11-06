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

namespace tests\units\Glpi\DependencyInjection;

use Glpi\DependencyInjection\PluginContainer;
use Glpi\Tests\GLPITestCase;
use ReflectionProperty;

class PluginContainerTest extends GLPITestCase
{
    public function testContainerIsBuiltOnFirstUse(): void
    {
        $plugin_container = $this->getPluginContainer();
        $this->assertFalse($this->isBuilt($plugin_container));

        $this->assertTrue($plugin_container->has('glpi_plugin_router'));
        $this->assertTrue($this->isBuilt($plugin_container));
    }

    public function testParametersCanBeReadBeforeAnyService(): void
    {
        $this->assertSame('testing', $this->getPluginContainer()->getParameter('kernel.environment'));
        $this->assertTrue($this->getPluginContainer()->hasParameter('kernel.environment'));
    }

    private function getPluginContainer(): PluginContainer
    {
        global $kernel;

        $symfony_container = $kernel->getContainer();

        return new PluginContainer($symfony_container, $symfony_container->getParameterBag(), $kernel);
    }

    private function isBuilt(PluginContainer $plugin_container): bool
    {
        return (new ReflectionProperty(PluginContainer::class, 'internal_container'))->getValue($plugin_container) !== null;
    }
}
