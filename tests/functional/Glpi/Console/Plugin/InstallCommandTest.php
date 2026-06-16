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

namespace tests\functional\Glpi\Console\Plugin;

use Glpi\Console\Cache\ClearCommand;
use Glpi\Console\Plugin\InstallCommand;
use Glpi\Tests\DbTestCase;
use Plugin;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InstallCommandTest extends DbTestCase
{
    private ?string $plugin_dir = null;
    private string $plugin_name = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->plugin_name = 'testcacheinstall' . uniqid();
        $this->plugin_dir  = GLPI_ROOT . '/plugins/' . $this->plugin_name;

        mkdir($this->plugin_dir);
        file_put_contents($this->plugin_dir . '/setup.php', sprintf(
            <<<'PHP'
            <?php
            function plugin_version_%1$s(): array {
                return ['name' => 'Test Cache Install', 'version' => '1.0.0', 'author' => 'Test', 'license' => 'GPL v2+', 'requirements' => ['glpi' => ['min' => '9.5.0']]];
            }
            function plugin_%1$s_install(): bool { return true; }
            function plugin_%1$s_uninstall(): bool { return true; }
            PHP,
            $this->plugin_name
        ));
    }

    public function tearDown(): void
    {
        $plugin = new Plugin();
        if ($plugin->getFromDBByCrit(['directory' => $this->plugin_name])) {
            $plugin->delete(['id' => $plugin->fields['id']], true);
        }

        if ($this->plugin_dir !== null && is_dir($this->plugin_dir)) {
            $this->removeDirectory($this->plugin_dir);
        }

        parent::tearDown();
    }

    private function buildApplication(): Application
    {
        $app = new Application();
        $app->setAutoExit(false);
        $app->add(new InstallCommand());
        $app->add(new ClearCommand());
        return $app;
    }

    public function testClearsCacheAfterInstall(): void
    {
        $tester = new CommandTester($this->buildApplication()->find('plugin:install'));
        $tester->execute(['directory' => [$this->plugin_name]]);

        $this->assertStringContainsString('Cache cleared successfully.', $tester->getDisplay());
    }

    public function testClearsCacheAfterForceInstall(): void
    {
        $plugin = new Plugin();
        $plugin->checkPluginState($this->plugin_name);
        $plugin->getFromDBByCrit(['directory' => $this->plugin_name]);
        $plugin->install($plugin->fields['id']);

        $tester = new CommandTester($this->buildApplication()->find('plugin:install'));
        $tester->execute(['directory' => [$this->plugin_name], '--force' => true]);

        $this->assertStringContainsString('Cache cleared successfully.', $tester->getDisplay());
    }

    public function testClearsCacheAfterInstallAll(): void
    {
        // --all sets directory=['*'] in interact() without prompting, then normalizeInput()
        // expands it to all not-yet-installed plugins (including our test plugin).
        // Even if other plugins fail, cache clearing is unconditional.
        $tester = new CommandTester($this->buildApplication()->find('plugin:install'));
        $tester->execute(['--all' => true]);

        $this->assertStringContainsString('Cache cleared successfully.', $tester->getDisplay());
    }
}
