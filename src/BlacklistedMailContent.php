<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
            'list'  => true
        ]
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
            'massiveaction'      => false
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "fas fa-envelope-square";
    }
}
