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

namespace Glpi\Asset;

use CommonDropdown;
use Dropdown;
use Session;

class AssetMenu extends CommonDropdown
{
    public $can_be_translated = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Asset menu', 'Assets menus', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-menu-2";
    }

    public static function getParentMenus()
    {
        $max_id = pow(2, 32) - 100;

        return [
            $max_id => _n('Asset', 'Assets', Session::getPluralNumber()),
            $max_id + 1 => __('Assistance'),
            $max_id + 2 => __('Management'),
            $max_id + 3 => __('Tools'),
            $max_id + 4 => __('Administration'),
            $max_id + 5 => __('Setup'),
        ];
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'assets_assetmenus_id',
                'label' => __('Menu'),
                'type'  => 'menu_parent'
            ],
        ];
    }


   /**
    * Display specific fields
    *
    * @param integer $ID
    * @param array $field
    */
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'menu_parent':
                Dropdown::showFromArray('menu_parent', self::getParentMenus(), [
                    'value' => $this->fields['menu_parent'],
                ]);
                break;
        }
    }
}
