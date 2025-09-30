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

namespace tests\units\Glpi\System\Requirement;

use Glpi\System\Requirement\DirectoriesWriteAccess;
use org\bovigo\vfs\vfsStream;

/**
 * Nota: Complex ACL are not tested.
 */
class DirectoriesWriteAccessTest extends \GLPITestCase
{
    public function testCheckOnExistingWritableDirs()
    {
        vfsStream::setup(
            'root',
            null,
            [
                'a' => [],
                'b' => [],
            ]
        );
        $path_a = vfsStream::url('root/a');
        $path_b = vfsStream::url('root/b');

        $instance = new DirectoriesWriteAccess('test', [$path_a, $path_b]);
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            [
                'Write access to ' . $path_a . ' has been validated.',
                'Write access to ' . $path_b . ' has been validated.',
            ],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnFaultyDirs()
    {
        $structure = vfsStream::setup(
            'root',
            null,
            [
                'writable' => [],
                'not_writable' => [],
            ]
        );
        $structure->getChild('not_writable')->chmod(0o444);

        $writable_path = vfsStream::url('root/writable');
        $not_writable_path = vfsStream::url('root/not_writable');
        $invalid_path = vfsStream::url('root/invalid');

        $instance = new DirectoriesWriteAccess('test', [$writable_path, $not_writable_path, $invalid_path]);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            [
                'Write access to ' . $writable_path . ' has been validated.',
                'The directory could not be created in ' . $not_writable_path . '.',
                'The directory could not be created in ' . $invalid_path . '.',
            ],
            $instance->getValidationMessages()
        );
    }
}
