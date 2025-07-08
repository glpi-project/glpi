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

/* Test for inc/operatingsystemkernelversion.class.php */

class OperatingSystemKernelVersionTest extends CommonDropdown
{
    public function getObjectClass()
    {
        return '\OperatingSystemKernelVersion';
    }

    public static function typenameProvider()
    {
        return [
            [\OperatingSystemKernelVersion::getTypeName(), 'Kernel versions'],
            [\OperatingSystemKernelVersion::getTypeName(0), 'Kernel versions'],
            [\OperatingSystemKernelVersion::getTypeName(10), 'Kernel versions'],
            [\OperatingSystemKernelVersion::getTypeName(1), 'Kernel version'],
        ];
    }

    public function testGetAdditionalFields()
    {
        $instance = $this->newInstance();
        $this->assertSame(
            [
                [
                    'label'  => 'Kernel',
                    'name'   => 'Kernels',
                    'list'   => true,
                    'type'   => 'oskernel',
                ],
            ],
            $instance->getAdditionalFields()
        );
    }

    protected function getTabs()
    {
        return [
            'OperatingSystemKernelVersion$main' => "Kernel version",
        ];
    }

    /**
     * Create new kernel version in database
     *
     * @return \CommonDBTM
     */
    protected function newInstance(): \CommonDBTM
    {
        $kernel = new \OperatingSystemKernel();
        $this->assertGreaterThan(
            0,
            $kernel->add([
                'name'   => 'linux',
            ])
        );
        $instance = new \OperatingSystemKernelVersion();
        $this->assertGreaterThan(
            0,
            $instance->add([
                'name'                        => 'Version name ' . $this->getUniqueString(),
                'operatingsystemkernels_id'   => $kernel->getID(),
            ])
        );
        $this->assertTrue($instance->getFromDB($instance->getID()));
        return $instance;
    }
}
