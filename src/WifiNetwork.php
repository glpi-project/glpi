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

/// Class WifiNetwork
/// since version 0.84
class WifiNetwork extends CommonDropdown
{
    public $dohistory          = true;

    public static $rightname          = 'internet';

    public $can_be_translated  = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Wifi network', 'Wifi networks', $nb);
    }

    public static function getWifiCardVersion()
    {
        return [
            ''          => '',
            'a'         => 'a',
            'a/b'       => 'a/b',
            'a/b/g'     => 'a/b/g',
            'a/b/g/n'   => 'a/b/g/n',
            'a/b/g/n/y' => 'a/b/g/n/y',
            'ac'        => 'ac',
            'ax'        => 'ax',
        ];
    }


    public static function getWifiCardModes()
    {

        return [''          => Dropdown::EMPTY_VALUE,
            'ad-hoc'    => __('Ad-hoc'),
            'managed'   => __('Managed'),
            'master'    => __('Master'),
            'repeater'  => __('Repeater'),
            'secondary' => __('Secondary'),
            'monitor'   => Monitor::getTypeName(1),
            'auto'      => __('Automatic')
        ];
    }


    public static function getWifiNetworkModes()
    {

        return [''               => Dropdown::EMPTY_VALUE,
            'infrastructure' => __('Infrastructure (with access point)'),
            'ad-hoc'         => __('Ad-hoc (without access point)')
        ];
    }


    public function defineTabs($options = [])
    {

        $ong  = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('NetworkPort', $ong, $options);

        return $ong;
    }


    public function getAdditionalFields()
    {

        return [['name'  => 'essid',
            'label' => __('ESSID'),
            'type'  => 'text',
            'list'  => true
        ],
            ['name'  => 'mode',
                'label' => __('Wifi network type'),
                'type'  => 'wifi_mode',
                'list'  => true
            ]
        ];
    }


    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        if ($field['type'] == 'wifi_mode') {
            Dropdown::showFromArray(
                $field['name'],
                self::getWifiNetworkModes(),
                [
                    'value' => $this->fields[$field['name']],
                    'width' => '100%',
                ]
            );
        }
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'essid',
            'name'               => __('ESSID'),
            'datatype'           => 'string',
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "fas fa-wifi";
    }
}
