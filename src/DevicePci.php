<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * DevicePci Class
 **/
class DevicePci extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DevicePci', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('PCI device', 'PCI devices', $nb);
    }


    /**
     * @see CommonDevice::getAdditionalFields()
     * @since 0.85
     */
    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [['name'  => 'none',
                'label' => RegisteredID::getTypeName(Session::getPluralNumber()) .
                                        RegisteredID::showAddChildButtonForItemForm(
                                            $this,
                                            '_registeredID',
                                            null,
                                            false
                                        ),
                'type'  => 'registeredIDChooser'
            ],
                ['name'  => 'devicepcimodels_id',
                    'label' => _n('Model', 'Models', 1),
                    'type'  => 'dropdownValue'
                ]
            ]
        );
    }

    public function rawSearchOptions()
    {

        $tab                 = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '17',
            'table'              => 'glpi_devicepcimodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        $tab[] = [
            'id'                 => '95',
            'table'              => 'glpi_devicepcis',
            'field'              => 'designation',
            'name'               => __('Other component'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicepcis',
                    'joinparams'         => $main_joinparams
                ]
            ]
        ];

        return $tab;
    }
}
