<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use Glpi\Console\Marketplace\DownloadCommand;
use Glpi\Marketplace\Api\Plugins as PluginsApi;
use Glpi\Marketplace\Controller;
use Glpi\Tests\DbTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DownloadCommandTest extends DbTestCase
{
    public function tearDown(): void
    {
        Controller::$api = null;
        parent::tearDown();
    }

    public function testExitCodeIsNonZeroWhenDownloadFails(): void
    {
        $api = $this->createMock(PluginsApi::class);
        $api->method('getPlugin')->willReturn([
            'key'              => 'unreachable',
            'version'          => '1.0.0',
            'installation_url' => 'https://example.org/unreachable-1.0.0.tar.bz2',
        ]);
        $api->expects($this->once())->method('downloadArchive')->willReturn(false);
        Controller::$api = $api;

        $tester = new CommandTester(new DownloadCommand());
        $exit_code = $tester->execute(['plugins' => ['unreachable']]);

        $this->assertStringContainsString('Plugin "unreachable" could not be downloaded', $tester->getDisplay());
        $this->assertSame(1, $exit_code);
        $this->hasSessionMessages(ERROR, ['Unable to download plugin archive.']);
    }

    public function testExitCodeIsZeroWhenPluginIsAlreadyDownloaded(): void
    {
        $api = $this->createMock(PluginsApi::class);
        $api->expects($this->never())->method('downloadArchive');
        Controller::$api = $api;

        $tester = new CommandTester(new DownloadCommand());
        $exit_code = $tester->execute(['plugins' => ['myplugin']]);

        $this->assertStringContainsString('Plugin "myplugin" is already downloaded', $tester->getDisplay());
        $this->assertSame(0, $exit_code);
    }
}
