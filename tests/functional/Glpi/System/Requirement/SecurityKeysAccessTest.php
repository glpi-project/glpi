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

namespace tests\units\Glpi\System\Requirement;

use Glpi\System\Requirement\SecurityKeysAccess;
use Glpi\Tests\GLPITestCase;
use org\bovigo\vfs\vfsStream;

class SecurityKeysAccessTest extends GLPITestCase
{
    public function testCheckOnReadableKeys()
    {
        vfsStream::setup('config', 0o555, [
            'glpicrypt.key' => 'key',
            'oauth.pem'     => 'private',
            'oauth.pub'     => 'public',
        ]);

        $instance = new SecurityKeysAccess(vfsStream::url('config'));
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Security key files are accessibles.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnUnreadableKeys()
    {
        $structure = vfsStream::setup('config', 0o555, [
            'glpicrypt.key' => 'key',
            'oauth.pem'     => 'private',
            'oauth.pub'     => 'public',
        ]);
        $structure->getChild('oauth.pem')->chmod(0o000);

        $instance = new SecurityKeysAccess(vfsStream::url('config'));
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The security key file ' . vfsStream::url('config/oauth.pem') . ' is not readable.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingKeysInWritableDir()
    {
        vfsStream::setup('config', 0o777, []);

        $instance = new SecurityKeysAccess(vfsStream::url('config'));
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Security key files are accessibles.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingKeysInProtectedDir()
    {
        vfsStream::setup('config', 0o555, ['glpicrypt.key' => 'key']);

        $instance = new SecurityKeysAccess(vfsStream::url('config'));
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            [
                'The security key file ' . vfsStream::url('config/oauth.pem') . ' could not be created.',
                'The security key file ' . vfsStream::url('config/oauth.pub') . ' could not be created.',
            ],
            $instance->getValidationMessages()
        );
    }
}
