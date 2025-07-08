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

use Glpi\System\Requirement\DirectoryWriteAccess;
use org\bovigo\vfs\vfsStream;

/**
 * Nota: Complex ACL are not tested.
 */
class DirectoryWriteAccessTest extends \GLPITestCase
{
    public function testCheckOnExistingWritableDir()
    {
        vfsStream::setup('root', 0o777, []);
        $path = vfsStream::url('root');

        $instance = new DirectoryWriteAccess($path);
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Write access to ' . $path . ' has been validated.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnUnexistingDir()
    {
        vfsStream::setup('root', 0o777, []);
        $path = vfsStream::url('root/not-existing');

        $instance = new DirectoryWriteAccess($path);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The directory could not be created in ' . $path . '.'],
            $instance->getValidationMessages()
        );
    }
}
