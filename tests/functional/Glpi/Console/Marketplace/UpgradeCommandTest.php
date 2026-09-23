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

use Glpi\Console\Marketplace\UpgradeCommand;
use Glpi\Tests\GLPITestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommandTest extends GLPITestCase
{
    public function testUpgradeActivePluginsSkipsPluginsThatWereNotUpdated(): void
    {
        $command = new class extends UpgradeCommand {
            public array $processed = [];

            protected function upgradePlugin(string $plugin_key, ?string $username, OutputInterface $output): bool
            {
                $this->processed[] = $plugin_key;
                return true;
            }
        };

        // `asort()`-ed by plugin id, so processing order is `aaa`, `mmm`, `zzz`.
        $active_plugins = [
            'zzz' => 3,
            'aaa' => 1,
            'mmm' => 2,
        ];
        $updated_plugins = ['zzz', 'aaa']; // `mmm` has not been updated and must be skipped.

        $has_errors = $this->callPrivateMethod(
            $command,
            'upgradeActivePlugins',
            $active_plugins,
            $updated_plugins,
            null,
            new NullOutput()
        );

        $this->assertFalse($has_errors);
        $this->assertEquals(['aaa', 'zzz'], $command->processed);
    }

    public function testUpgradeActivePluginsReportsFailureWithoutInterruptingProcessing(): void
    {
        $command = new class extends UpgradeCommand {
            public array $processed = [];

            protected function upgradePlugin(string $plugin_key, ?string $username, OutputInterface $output): bool
            {
                $this->processed[] = $plugin_key;
                // Simulate a subprocess failure in the middle of the batch.
                return $plugin_key !== 'faulty';
            }
        };

        $active_plugins = [
            'first'  => 1,
            'faulty' => 2,
            'last'   => 3,
        ];
        $updated_plugins = ['first', 'faulty', 'last'];

        $has_errors = $this->callPrivateMethod(
            $command,
            'upgradeActivePlugins',
            $active_plugins,
            $updated_plugins,
            null,
            new NullOutput()
        );

        $this->assertTrue($has_errors);
        // All plugins must have been processed, including the ones after the failing one.
        $this->assertEquals(['first', 'faulty', 'last'], $command->processed);
    }

    public function testUpgradeActivePluginsWithNoActivePlugins(): void
    {
        $command = new UpgradeCommand();

        $has_errors = $this->callPrivateMethod($command, 'upgradeActivePlugins', [], [], null, new NullOutput());

        $this->assertFalse($has_errors);
    }
}
