<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Tools\Command\LicenceHeadersCheckCommand;
use Symfony\Component\Console\Tester\CommandTester;

class LicenceHeadersCheckCommandTest extends GLPITestCase
{
    private string $testDir;

    public function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/glpi_test_header_' . uniqid();
        if (!mkdir($this->testDir) && !is_dir($this->testDir)) {
             $this->markTestSkipped('Could not create temp directory');
        }
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testMissingHeader(): void
    {
        file_put_contents($this->testDir . '/no_header1.php', "<?php echo 'foo';");
        file_put_contents($this->testDir . '/no_header2.php', "<?php echo 'foo';");

        $command = new LicenceHeadersCheckCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--directory' => $this->testDir,
            '--fix' => true,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[OK] Fixed 2 files without header.', $output);
    }

    public function testOutdatedHeader(): void
    {
        $content = <<<PHP
<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * @copyright 2015-2020 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * ---------------------------------------------------------------------
 */
PHP;
        file_put_contents($this->testDir . '/outdated.php', $content);

        $command = new LicenceHeadersCheckCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--directory' => $this->testDir,
            '--fix' => true,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[OK] Fixed 1 file with outdated header.', $output);
    }

    public function testMixedIssues(): void
    {
        file_put_contents($this->testDir . '/no_header.php', "<?php echo 'foo';");
        $content = <<<PHP
<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * @copyright 2015-2020 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * ---------------------------------------------------------------------
 */
PHP;
        file_put_contents($this->testDir . '/outdated.php', $content);

        $command = new LicenceHeadersCheckCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--directory' => $this->testDir,
            '--fix' => true,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[OK] Fixed 1 file without header and 1 file with outdated header.', $output);
    }

    public function testNoFixOption(): void
    {
        file_put_contents($this->testDir . '/no_header.php', "<?php echo 'foo';");

        $command = new LicenceHeadersCheckCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--directory' => $this->testDir,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[ERROR] Found 1 file without header. Use --fix option to fix these files.', $output);
        $this->assertEquals(LicenceHeadersCheckCommand::ERROR_FOUND_MISSING_OR_OUTDATED, $tester->getStatusCode());
    }

    public function testSuccess(): void
    {
        // Empty dir, should pass
        $command = new LicenceHeadersCheckCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            '--directory' => $this->testDir,
        ]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('[OK] Files headers are valid.', $output);
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
