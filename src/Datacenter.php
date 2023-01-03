<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Features\AssetImage;

/**
 * Datacenter Class
 **/
class Datacenter extends CommonDBTM
{
    use AssetImage;

   // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname                   = 'datacenter';

    public static function getTypeName($nb = 0)
    {
       //TRANS: Test of comment for translation (mark : //TRANS)
        return _n('Data center', 'Data centers', $nb);
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        return $this->managePictures($input);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('DCRoom', $ong, $options);
        return $ong;
    }


    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false, // implicit key==1
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    public static function rawSearchOptionsToAdd($itemtype)
    {
        return [
            [
                'id'                 => 'datacenter',
                'name'               => _n('Data center', 'Data centers', Session::getPluralNumber())
            ],
            [
                'id'                 => '178',
                'table'              => $itemtype::getTable(),
                'field'              => '_virtual_datacenter_position', // virtual field
                'additionalfields'   => [
                    'id',
                    'name'
                ],
                'name'               => __('Data center position'),
                'datatype'           => 'specific',
                'nosearch'           => true,
                'nosort'             => true,
                'massiveaction'      => false
            ],
        ];
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];
        if (static::canView()) {
            $rooms = "<i class='ti ti-building pointer'
                      title=\"" . DCRoom::getTypeName(Session::getPluralNumber()) . "\"></i>
            <span class='d-none d-xxl-block ps-1'>
               " . DCRoom::getTypeName(Session::getPluralNumber()) . "
            </span>";
            $links[$rooms] = DCRoom::getSearchURL(false);
        }
        if (count($links)) {
            return $links;
        }
        return false;
    }

    public static function getAdditionalMenuOptions()
    {
        if (static::canView()) {
            return [
                'dcroom' => [
                    'title' => DCRoom::getTypeName(Session::getPluralNumber()),
                    'page'  => DCRoom::getSearchURL(false),
                    'icon'  => DCRoom::getIcon(),
                    'links' => [
                        'add'    => '/front/dcroom.form.php',
                        'search' => '/front/dcroom.php',
                    ]
                ]
            ];
        }
        return false;
    }


    public static function getIcon()
    {
        return "ti ti-building-warehouse";
    }
}
