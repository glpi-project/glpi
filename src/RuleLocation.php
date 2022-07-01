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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

class RuleLocation extends Rule
{
    public static $rightname = 'rule_location';
    public $can_sort  = true;

    public function getTitle()
    {
        return __('Location rules');
    }


    public function maxActionsCount()
    {
        return 2;
    }


    public function getCriterias()
    {
        return [
            'itemtype' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('Item type')),
                'type'            => 'dropdown_inventory_itemtype',
                'is_global'       => false,
                'allow_condition' => [
                    Rule::PATTERN_IS,
                    Rule::PATTERN_IS_NOT,
                    Rule::PATTERN_EXISTS,
                    Rule::PATTERN_DOES_NOT_EXISTS,
                ],
            ],
            'tag' => [
                'name'            => sprintf('%s > %s', Agent::getTypeName(1), __('Inventory tag')),
            ],
            'domain' => [
                'name'            => Domain::getTypeName(1),
            ],
            'subnet' => [
                'name'            => __("Subnet"),
            ],
            'ip' => [
                'name'            => sprintf('%s > %s', NetworkPort::getTypename(1), __('IP')),
            ],
            'name' => [
                'name'            => __("Name"),
            ],
            'serial' => [
                'name'            => __("Serial number"),
            ],
            'oscomment' => [
                'name'            => sprintf('%s > %s', OperatingSystem::getTypeName(1), __('Comments')),
            ],
        ];
    }


    public function getActions()
    {
        return [
            'locations_id' => [
                'name'  => _n('Location', 'Locations', 1),
                'type'  => 'dropdown',
                'table' => Location::getTable(),
                'force_actions' => [
                    'assign',
                    'regex_result',
                ]
            ]
        ];
    }


    public static function getIcon()
    {
        return Location::getIcon();
    }
}
