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

namespace tests\units\Glpi\Console\Marketplace;

use Glpi\Console\Marketplace\UpgradePluginCommand;
use Glpi\Tests\DbTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpgradePluginCommandTest extends DbTestCase
{
    public function testCommandIsHidden(): void
    {
        $command = new UpgradePluginCommand();

        $this->assertTrue($command->isHidden());
    }

    public function testExecuteFailsOnUnknownPlugin(): void
    {
        $command = new UpgradePluginCommand();
        $tester  = new CommandTester($command);

        $tester->execute([
            'plugin_key' => 'this_plugin_does_not_exist',
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString(
            'Plugin "this_plugin_does_not_exist" not found.',
            $tester->getDisplay()
        );
    }
}
