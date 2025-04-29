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

/**
 * BlacklistedMailContent Class
 *
 * @since 0.85
 **/
class BlacklistedMailContent extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory       = false;

    public static $rightname       = 'config';

    public $can_be_translated = false;


    public static function getTypeName($nb = 0)
    {
        return __('Blacklisted mail content');
    }


    public static function canCreate()
    {
        return static::canUpdate();
    }


    public static function canPurge()
    {
        return static::canUpdate();
    }


    public function getAdditionalFields()
    {

        return [['name'  => 'content',
            'label' => __('Content'),
            'type'  => 'textarea',
            'rows'  => 20,
            'list'  => true,
            'enable_richtext' => false,
        ],
        ];
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'content',
            'name'               => __('Content'),
            'datatype'           => 'text',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "fas fa-envelope-square";
    }
}
