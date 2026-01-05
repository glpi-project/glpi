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

namespace tests\functional\Tools\Plugin\Command;

use Glpi\Tests\GLPITestCase;
use Glpi\Tools\Plugin\Command\PluginReleaseCommand;
use Symfony\Component\Console\Tester\CommandTester;

class PluginReleaseCommandTest extends GLPITestCase
{
    private string $test_dir;
    private string $plugin_dir;

    public function setUp(): void
    {
        parent::setUp();

        $this->plugin_dir = GLPI_ROOT . '/plugins/testrelease_' . uniqid();
        if (!mkdir($this->plugin_dir)) {
            $this->markTestSkipped('Could not create temp plugin directory in ' . GLPI_ROOT . '/plugins');
        }
        $this->test_dir = $this->plugin_dir;
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->test_dir);
        parent::tearDown();
    }

    public function testCheckOnlySuccess(): void
    {
        $plugin_name = basename($this->plugin_dir);
        // setup.php
        file_put_contents($this->plugin_dir . '/setup.php', "<?php define('PLUGIN_" . strtoupper($plugin_name) . "_VERSION', '1.0.0');");
        // plugin.xml
        file_put_contents($this->plugin_dir . '/plugin.xml', "<root><versions><version><num>1.0.0</num></version></versions></root>");

        // Use anonymous class to mock git interaction
        $command = new class extends PluginReleaseCommand {
            protected function getGitTags(): array
            {
                return ['1.0.0'];
            }
        };

        $tester = new CommandTester($command);
        $tester->execute([
            '--plugin' => $plugin_name,
            '--release' => '1.0.0',
            '--check-only' => true,
            '--dont-check' => false,
        ]);

        $this->assertEquals(0, $tester->getStatusCode(), "Command failed with output:\n" . $tester->getDisplay());
        $this->assertStringContainsString('Check-only mode finished.', $tester->getDisplay());
    }

    public function testCheckOnlyVersionMismatch(): void
    {
        $plugin_name = basename($this->plugin_dir);
        // setup.php
        file_put_contents($this->plugin_dir . '/setup.php', "<?php define('PLUGIN_" . strtoupper($plugin_name) . "_VERSION', '1.0.1');");
        // plugin.xml
        file_put_contents($this->plugin_dir . '/plugin.xml', "<root><versions><version><num>1.0.0</num></version></versions></root>");

        $command = new class extends PluginReleaseCommand {
            protected function getGitTags(): array
            {
                return ['1.0.0'];
            }
        };

        $tester = new CommandTester($command);
        $tester->execute([
            '--plugin' => $plugin_name,
            '--release' => '1.0.0',
            '--check-only' => true,
        ]);

        $this->assertStringContainsString('Plugin version check has failed', $tester->getDisplay());
        $this->assertEquals(1, $tester->getStatusCode());
    }
}
