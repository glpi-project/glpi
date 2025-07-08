<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class DomainRelation extends CommonDropdown
{
    public const BELONGS = 1;
    public const MANAGE = 2;
    // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname                   = 'dropdown';

    public static $knowrelations = [
        [
            'id'        => self::BELONGS,
            'name'      => 'Belongs',
            'comment'   => 'Item belongs to domain',
        ], [
            'id'        => self::MANAGE,
            'name'      => 'Manage',
            'comment'   => 'Item manages domain',
        ],
    ];

    public static function getTypeName($nb = 0)
    {
        return _n('Domain relation', 'Domains relations', $nb);
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Domain_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public static function getDefaults()
    {
        return array_map(
            function ($e) {
                $e['is_recursive'] = 1;
                return $e;
            },
            self::$knowrelations
        );
    }

    public function pre_deleteItem()
    {
        if (in_array($this->fields['id'], [self::BELONGS, self::MANAGE])) {
            //keep defaults
            return false;
        }
        return true;
    }
}
