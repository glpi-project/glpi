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

use Glpi\System\Requirement\ExtensionConstant;

class ExtensionConstantTest extends \GLPITestCase
{
    public function testCheckOnExistingConstant()
    {
        $test_constant = 'TEST_CONSTANT' . mt_rand();
        define($test_constant, 'TEST');
        $instance = new ExtensionConstant('Test constant', $test_constant, false, '');
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['The constant ' . $test_constant . ' is present.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingMandatoryConstant()
    {
        $test_constant = 'TEST_CONSTANT' . mt_rand();
        $instance = new ExtensionConstant('Test constant', $test_constant, false, '');
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The constant ' . $test_constant . ' is missing.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingOptionalConstant()
    {
        $test_constant = 'TEST_CONSTANT' . mt_rand();
        $instance = new ExtensionConstant('Test constant', $test_constant, true, '');
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['The constant ' . $test_constant . ' is not present.'],
            $instance->getValidationMessages()
        );
    }
}
