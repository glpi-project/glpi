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

namespace tests\units;

/* Test for inc/operatingsystemkernel.class.php */

class OperatingSystemKernelTest extends CommonDropdown
{
    public function getObjectClass()
    {
        return '\OperatingSystemKernel';
    }

    public static function typenameProvider()
    {
        return [
            [\OperatingSystemKernel::getTypeName(), 'Kernels'],
            [\OperatingSystemKernel::getTypeName(0), 'Kernels'],
            [\OperatingSystemKernel::getTypeName(10), 'Kernels'],
            [\OperatingSystemKernel::getTypeName(1), 'Kernel'],
        ];
    }

    protected function getTabs()
    {
        return [
            'OperatingSystemKernel$main'  => "Kernel",
        ];
    }

    /**
     * Create new Kernel in database
     *
     * @return \CommonDBTM
     */
    protected function newInstance(): \CommonDBTM
    {
        $instance = new \OperatingSystemKernel();
        $this->assertGreaterThan(
            0,
            $instance->add([
                'name' => 'Kernel name ' . $this->getUniqueString(),
            ])
        );
        $this->assertTrue($instance->getFromDB($instance->getID()));
        return $instance;
    }
}
