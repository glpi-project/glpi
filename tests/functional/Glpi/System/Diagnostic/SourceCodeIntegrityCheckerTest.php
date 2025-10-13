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

namespace tests\units\Glpi\System\Diagnostic;

use Glpi\System\Diagnostic\SourceCodeIntegrityChecker;
use Glpi\Toolbox\VersionParser;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LogLevel;

class SourceCodeIntegrityCheckerTest extends \GLPITestCase
{
    /**
     * Setup the minimal VFS structure required to compute the manifest, and containing some fake files.
     */
    private function setupVFS(): void
    {
        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        vfsStream::setup('check_root_dir', null, [
            'ajax' => [],
            'bin' => [
                'console' => 'GLPI console',
            ],
            'css' => [],
            'dependency_injection' => [],
            'front' => [],
            'files' => [
                '_tmp' => [],
            ],
            'inc' => [],
            'install' => [],
            'lib' => [],
            'locales' => [],
            'public' => [
                'index.php' => 'GLPI router',
            ],
            'resources' => [],
            'routes' => [],
            'src' => [
                'test.php' => 'test1',
                'test2.php' => <<<EOL
line1
line2
line3

EOL,
            ],
            'templates' => [],
            'vendor' => [],
            'version' => [
                $version => '',
            ],
        ]);
    }

    public function testGenerateManifest()
    {
        $this->setupVFS();

        $checker = new SourceCodeIntegrityChecker(vfsStream::url('check_root_dir'));

        $this->assertEquals(
            [
                'algorithm' => 'CRC32c',
                'files' => [
                    'bin/console' => '01f78965',
                    'public/index.php' => '8461e565',
                    'src/test.php' => '53fe1f55',
                    'src/test2.php' => '2803299a',
                ],
            ],
            $checker->generateManifest('CRC32c')
        );
    }

    public function testGetSummary()
    {
        $this->setupVFS();

        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);

        $checker = new SourceCodeIntegrityChecker(vfsStream::url('check_root_dir'));

        file_put_contents(vfsStream::url('check_root_dir/version/' . $version), json_encode($checker->generateManifest('CRC32c'), JSON_THROW_ON_ERROR));
        file_put_contents(vfsStream::url('check_root_dir/src/test.php'), 'changed');
        file_put_contents(vfsStream::url('check_root_dir/src/test3.php'), 'added');
        file_put_contents(vfsStream::url('check_root_dir/src/test4.php'), 'added (with EOL)' . "\n");
        unlink(vfsStream::url('check_root_dir/src/test2.php'));

        $this->assertEquals(
            [
                'src/test.php' => 1, // 1 = STATUS_ALTERED
                'src/test2.php' => 2, // 2 = STATUS_MISSING
                'src/test3.php' => 3, // 3 = STATUS_ADDED
                'src/test4.php' => 3, // 3 = STATUS_ADDED
            ],
            $checker->getSummary()
        );
    }

    public function testGetDiff()
    {
        $this->setupVFS();

        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        $version_full = VersionParser::getNormalizedVersion(GLPI_VERSION);

        $checker = new SourceCodeIntegrityChecker(vfsStream::url('check_root_dir'));

        file_put_contents(vfsStream::url('check_root_dir/version/' . $version), json_encode($checker->generateManifest('CRC32c'), JSON_THROW_ON_ERROR));

        // Create tgz file from the vfs directory and save it to files/_tmp/ in the vfs.
        // No, it cannot be created from a stream or saved directly to a stream.
        if (file_exists(GLPI_TMP_DIR . '/glpi-' . $version_full . '.tar.gz')) {
            unlink(GLPI_TMP_DIR . '/glpi-' . $version_full . '.tar.gz');
        }
        $phar = new \PharData(GLPI_TMP_DIR . '/glpi-' . $version_full . '.tgz');
        // recursively iterate through the files in the vfs and manually add to phar using addFromString
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(vfsStream::url('check_root_dir')));
        $path_pattern = '/^' . preg_quote(vfsStream::url('check_root_dir') . '/', '/') . '/';
        foreach ($iterator as $file) {
            if ($file->isDir() || str_ends_with($file->getPathname(), '.tgz')) {
                continue;
            }
            $path = preg_replace($path_pattern, '', $file->getPathname());
            $phar->addFromString('glpi/' . $path, file_get_contents($file->getPathname()));
        }
        $phar->compress(\Phar::GZ);
        $this->assertTrue(rename(GLPI_TMP_DIR . '/glpi-' . $version_full . '.tar.gz', GLPI_TMP_DIR . '/glpi-' . $version_full . '.tgz'));

        unlink(vfsStream::url('check_root_dir/src/test.php'));
        file_put_contents(
            vfsStream::url('check_root_dir/src/test2.php'),
            <<<EOL
line1
lineb
line3

EOL
        );
        file_put_contents(vfsStream::url('check_root_dir/src/test3.php'), 'added');
        file_put_contents(vfsStream::url('check_root_dir/src/test4.php'), 'added (with EOL)' . "\n");

        $errors = [];
        $diff = $checker->getDiff(false, $errors);
        // Why not isEmpty? Because then atoum will not tell you what the contents are when this fails.
        $this->assertEmpty($errors);
        $this->assertEquals(
            <<<EOL
diff --git a/src/test.php b/src/test.php
deleted file mode 100644
--- a/src/test.php
+++ /dev/null
@@ -1 +1,0 @@
-test1
\ No newline at end of file

diff --git a/src/test2.php b/src/test2.php
--- a/src/test2.php
+++ b/src/test2.php
@@ -1,3 +1,3 @@
 line1
-line2
+lineb
 line3

diff --git a/src/test3.php b/src/test3.php
new file mode 100666
--- /dev/null
+++ b/src/test3.php
@@ -1,0 +1 @@
+added
\ No newline at end of file

diff --git a/src/test4.php b/src/test4.php
new file mode 100666
--- /dev/null
+++ b/src/test4.php
@@ -1,0 +1 @@
+added (with EOL)
EOL,
            trim($diff)
        );
    }

    public function testGenerateManifestExceptionOnMissingSourceDirectory()
    {
        vfsStream::setup('root', null, []);
        $root_dir = vfsStream::url('root');

        $checker = new SourceCodeIntegrityChecker($root_dir);

        $this->expectExceptionMessage(sprintf('`%s/ajax` does not exist in the filesystem.', $root_dir));
        $checker->generateManifest('CRC32c');
    }

    public function testGetSummaryExceptionOnMissingManifestFile()
    {
        vfsStream::setup('root', null, []);
        $root_dir = vfsStream::url('root');

        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);

        $checker = new SourceCodeIntegrityChecker($root_dir);

        $this->expectExceptionMessage('Error while trying to read the source code file manifest.');
        try {
            $checker->getSummary();
        } finally {
            $this->hasPhpLogRecordThatContains('file_get_contents(vfs://root/version/' . $version . '): Failed to open stream', LogLevel::WARNING);
        }
    }

    public function testGetSummaryExceptionOnInvalidManifestFile()
    {
        $version = VersionParser::getNormalizedVersion(GLPI_VERSION, false);

        vfsStream::setup('root', null, ['version' => [$version => '{invalid_json']]);
        $root_dir = vfsStream::url('root');

        $checker = new SourceCodeIntegrityChecker($root_dir);

        $this->expectExceptionMessage('The source code file manifest is invalid.');
        $checker->getSummary();
    }
}
