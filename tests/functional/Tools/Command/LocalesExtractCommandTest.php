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

namespace tests\functional\Tools\Command;

use Glpi\Tests\GLPITestCase;
use Glpi\Tools\Command\LocalesExtractCommand;
use Symfony\Component\Console\Tester\CommandTester;

class LocalesExtractCommandTest extends GLPITestCase
{
    private ?string $plugin_dir = null;


    public function tearDown(): void
    {
        if ($this->plugin_dir !== null && is_dir($this->plugin_dir)) {
            $this->removeDirectory($this->plugin_dir);
            $this->plugin_dir = null;
        }
        parent::tearDown();
    }

    public function testPluginExtraction(): void
    {
        // Mimic plugin structure
        $plugin_name = 'testlocales_' . uniqid();
        $plugin_name_uc = strtoupper($plugin_name);
        $this->plugin_dir = GLPI_ROOT . '/plugins/' . $plugin_name;

        if (!mkdir($this->plugin_dir)) {
            $this->markTestSkipped('Could not create temp plugin directory in ' . GLPI_ROOT . '/plugins');
        }
        mkdir($this->plugin_dir . '/locales');

        $setup_content = <<<EOF
<?php
define('PLUGIN_{$plugin_name_uc}_VERSION', '1.0.0');

function plugin_version_{$plugin_name}(): array
{
    return [
        'name'           => 'Test',
        'version'        => PLUGIN_{$plugin_name_uc}_VERSION,
        'author'         => '<a href="https://services.glpi-network.com">Teclib\'</a>',
        'license'        => 'GPLv3+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '11.0.0',
                'max' => '11.0.99',
            ],
        ],
    ];
}
EOF;

        file_put_contents($this->plugin_dir . '/setup.php', $setup_content);
        file_put_contents($this->plugin_dir . '/test.php', "<?php __('My String', '$plugin_name');");

        $command = new LocalesExtractCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--plugin' => $plugin_name,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $dest_pot = $this->plugin_dir . '/locales/' . $plugin_name . '.pot';
        $this->assertFileExists($dest_pot);
        $content = file_get_contents($dest_pot);
        $this->assertStringContainsString('msgid "My String"', $content);
    }
}
