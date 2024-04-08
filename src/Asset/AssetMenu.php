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

use CommonTreeDropdown;
use Session;

class AssetMenu extends CommonTreeDropdown
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

    public static function reservedEntries()
    {
        // we don't offer plugins entry (reserved for the plugins to add their own menu)
        return [
            1 => _n('Asset', 'Assets', Session::getPluralNumber()),
            2 => __('Assistance'),
            3 => __('Management'),
            4 => __('Tools'),
            5 => __('Administration'),
            6 => __('Setup'),
        ];
    }

    public function canDeleteItem()
    {

        $reserved = self::reservedEntries();
        if (isset($reserved[$this->fields['id']])) {
            return false;
        }

        return parent::canDeleteItem();
    }
}
