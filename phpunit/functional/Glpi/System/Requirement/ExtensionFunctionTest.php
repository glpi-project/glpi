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

use Glpi\System\Requirement\ExtensionFunction;

class ExtensionFunctionTest extends \GLPITestCase
{
    public function testCheckOnExistingExtension()
    {
        $instance = new ExtensionFunction('simplexml', 'simplexml_load_string');
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['simplexml extension is installed.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingMandatoryExtension()
    {
        $instance = new ExtensionFunction('fake_ext', 'fake_extension_function');
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['fake_ext extension is missing.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingOptionalExtension()
    {
        $instance = new ExtensionFunction('fake_ext', 'fake_extension_function', true);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['fake_ext extension is not present.'],
            $instance->getValidationMessages()
        );
    }
}
