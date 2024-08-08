<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Toolbox;

use org\bovigo\vfs\vfsStream;

class FilesystemTest extends \GLPITestCase
{
    public function testCanWriteFile(): void
    {
        $config_dir = vfsStream::setup('config');

        $instance = new \Glpi\Toolbox\Filesystem();

        // Files can be written when they not exists and directory is writable
        $config_dir->chmod(0700);
        $this->assertTrue($instance->canWriteFile(vfsStream::url('config/config_db.php')));
        $this->assertTrue($instance->canWriteFile(vfsStream::url('config/whatever.yml')));
        $this->assertTrue($instance->canWriteFiles([vfsStream::url('config/config_db.php'), vfsStream::url('config/whatever.yml')]));

        // Files cannot be written when they not exists and directory is not writable
        $config_dir->chmod(0500);
        $this->assertFalse($instance->canWriteFile(vfsStream::url('config/config_db.php')));
        $this->assertFalse($instance->canWriteFile(vfsStream::url('config/whatever.yml')));
        $this->assertFalse($instance->canWriteFiles([vfsStream::url('config/config_db.php'), vfsStream::url('config/whatever.yml')]));

        // Files cannot be written when they exist but are not writable (even if directory is writable)
        $config_dir->chmod(0700);
        $file1 = vfsStream::newFile('config_db.php', 0400)->at($config_dir)->setContent('<?php //my config file');
        $this->assertFalse($instance->canWriteFile(vfsStream::url('config/config_db.php')));
        $this->assertTrue($instance->canWriteFile(vfsStream::url('config/whatever.yml')));
        $this->assertFalse($instance->canWriteFiles([vfsStream::url('config/config_db.php'), vfsStream::url('config/whatever.yml')]));

        // Files can be written when they exist and are writable (even if directory is not writable)
        $file1->chmod(0666);
        $this->assertTrue($instance->canWriteFile(vfsStream::url('config/config_db.php')));
        $this->assertTrue($instance->canWriteFile(vfsStream::url('config/whatever.yml')));
        $this->assertTrue($instance->canWriteFiles([vfsStream::url('config/config_db.php'), vfsStream::url('config/whatever.yml')]));
    }

    public static function isFilepathSafeProvider(): iterable
    {
        // Unix paths and file scheme
        foreach (['', 'file://'] as $prefix) {
            yield [
                'path'                  => $prefix . '/path/to/whatever/file',
                'restricted_directory'  => null,
                'is_safe'               => true,
            ];
            yield [
                'path'                  => $prefix . '/path/to/whatever/file',
                'restricted_directory'  => '/path/to/whatever',
                'is_safe'               => true,
            ];
            yield [
                'path'                  => $prefix . '/path/to/whatever/file/in/a/subdir',
                'restricted_directory'  => '/path/to/whatever',
                'is_safe'               => true,
            ];
            yield [
                'path'                  => $prefix . '/path/to/whatever_file',
                'restricted_directory'  => '/path/to/whatever',
                'is_safe'               => false,
            ];
            yield [
                'path'                  => $prefix . '/path/to/whatever/file',
                'restricted_directory'  => '/safedir',
                'is_safe'               => false,
            ];
        }

        // Windows paths (`\` separator)
        yield [
            'path'                  => 'C:\\path\\to\\whatever\\file',
            'restricted_directory'  => null,
            'is_safe'               => true,
        ];
        yield [
            'path'                  => 'C:\\path\\to\\whatever\\file',
            'restricted_directory'  => 'C:\\path\\to\\whatever',
            'is_safe'               => true,
        ];
        yield [
            'path'                  => 'C:\\path\\to\\whatever\\file\\in\\a\\subdir',
            'restricted_directory'  => 'C:\\path\\to\\whatever',
            'is_safe'               => true,
        ];
        yield [
            'path'                  => 'C:\\path\\to\\whatever_file',
            'restricted_directory'  => 'C:\\path\\to\\whatever',
            'is_safe'               => false,
        ];
        yield [
            'path'                  => 'C:\\path\\to\\whatever\\file',
            'restricted_directory'  => 'C:\\safedir',
            'is_safe'               => false,
        ];

        // Windows path on file scheme
        yield [
            'path'                  => 'file:///C:/path/to/whatever/file',
            'restricted_directory'  => null,
            'is_safe'               => true,
        ];
        yield [
            'path'                  => 'file:///C:/path/to/whatever/file',
            'restricted_directory'  => 'C:\\path\\to\\whatever',
            'is_safe'               => true,
        ];
        yield [
            'path'                  => 'file:///C:/path/to/whatever/file/in/a/subdir',
            'restricted_directory'  => 'C:\\path\\to\\whatever',
            'is_safe'               => true,
        ];
        yield [
            'path'                  => 'file:///C:/path/to/whatever_file',
            'restricted_directory'  => 'C:\\path\\to\\whatever',
            'is_safe'               => false,
        ];
        yield [
            'path'                  => 'file:///C:/path/to/whatever/file',
            'restricted_directory'  => 'C:\\safedir',
            'is_safe'               => false,
        ];

        // Streams and remote paths
        foreach (['ftp', 'http', 'https', 'phar', 'whateverstream'] as $scheme) {
            yield [
                'path'                  => $scheme . '://path/to/whatever/file',
                'restricted_directory'  => null,
                'is_safe'               => false, // path using scheme is never considered to be safe
            ];

            yield [
                'path'                  => $scheme . '://path/to/whatever/file',
                'restricted_directory'  => $scheme . '://path/to/whatever',
                'is_safe'               => false, // path using scheme is never considered to be safe
            ];
        }
    }

    /**
     * @dataProvider isFilepathSafeProvider
     */
    public function testIsFilepathSafe(string $path, ?string $restricted_directory, bool $is_safe): void
    {
        $instance = new \Glpi\Toolbox\Filesystem();
        $this->assertSame($is_safe, $instance->isFilepathSafe($path, $restricted_directory));
    }
}
