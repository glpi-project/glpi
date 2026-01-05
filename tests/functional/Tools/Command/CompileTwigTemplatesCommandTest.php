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
use Glpi\Tools\Command\CompileTwigTemplatesCommand;
use Symfony\Component\Console\Tester\CommandTester;

class CompileTwigTemplatesCommandTest extends GLPITestCase
{
    private string $test_dir;
    private string $tpl_dir;
    private string $output_dir;

    public function setUp(): void
    {
        parent::setUp();
        $this->test_dir = sys_get_temp_dir() . '/glpi_test_compile_' . uniqid();
        $this->tpl_dir = $this->test_dir . '/templates';
        $this->output_dir = $this->test_dir . '/output';

        mkdir($this->test_dir);
        mkdir($this->tpl_dir);
        mkdir($this->output_dir);
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->test_dir);
        parent::tearDown();
    }

    public function testCompileSuccess(): void
    {
        // Create a dummy twig file
        file_put_contents($this->tpl_dir . '/test.twig', "Hello {{ name }}");

        $command = new CompileTwigTemplatesCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            'templates-directory' => $this->tpl_dir,
            'output-directory' => $this->output_dir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());

        // Check if output file exists
        // The filename hash depends on Twig's internal hashing, but we can check if *any* php file exists
        // The command preserves the extension (containing PHP code)
        $files = glob($this->output_dir . '/*.twig');
        $this->assertNotEmpty($files, 'Compiled file should exist');
        $this->assertStringContainsString('<?php', file_get_contents($files[0]), 'Compiled file should contain PHP code');
    }

    public function testCompileSubdirectorySuccess(): void
    {
        mkdir($this->tpl_dir . '/subdir');
        file_put_contents($this->tpl_dir . '/subdir/test.twig', "Hello {{ name }}");

        $command = new CompileTwigTemplatesCommand();
        $tester = new CommandTester($command);
        $tester->execute([
            'templates-directory' => $this->tpl_dir,
            'output-directory' => $this->output_dir,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $files = glob($this->output_dir . '/subdir/*.twig');
        $this->assertNotEmpty($files, 'Compiled file should exist in subdirectory');
    }
}
