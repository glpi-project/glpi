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

namespace tests\units;

require_once 'CommonDropdown.php';

/* Test for inc/operatingsystemkernel.class.php */

class OperatingSystemKernel extends CommonDropdown
{
    public function getObjectClass()
    {
        return '\OperatingSystemKernel';
    }

    public function typenameProvider()
    {
        return [
            [\OperatingSystemKernel::getTypeName(), 'Kernels'],
            [\OperatingSystemKernel::getTypeName(0), 'Kernels'],
            [\OperatingSystemKernel::getTypeName(10), 'Kernels'],
            [\OperatingSystemKernel::getTypeName(1), 'Kernel']
        ];
    }

    protected function getTabs()
    {
        return [
            'OperatingSystemKernel$main'  => 'Kernel',
        ];
    }

    /**
     * Create new Kernel in database
     *
     * @return void
     */
    protected function newInstance()
    {
        $this->newTestedInstance();
        $this->integer(
            (int)$this->testedInstance->add([
                'name' => 'Kernel name ' . $this->getUniqueString()
            ])
        )->isGreaterThan(0);
        $this->boolean($this->testedInstance->getFromDB($this->testedInstance->getID()))->isTrue();
    }
}
