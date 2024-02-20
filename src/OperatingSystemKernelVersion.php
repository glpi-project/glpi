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

class OperatingSystemKernelVersion extends CommonDropdown
{
    public $can_be_translated = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Kernel version', 'Kernel versions', $nb);
    }

    public function getAdditionalFields()
    {
        $fields   = parent::getAdditionalFields();
        $fields[] = [
            'label'  => OperatingSystemKernel::getTypeName(1),
            'name'   => OperatingSystemKernel::getTypeName(Session::getPluralNumber()),
            'list'   => true,
            'type'   => 'oskernel'
        ];

        return $fields;
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'oskernel':
                OperatingSystemKernel::dropdown([
                    'value'     => $this->fields['operatingsystemkernels_id'],
                    'width'     => '100%'
                ]);
                break;
        }
    }
}
