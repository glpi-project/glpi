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
 * Template for task
 * @since 9.1
 **/
class TaskTemplate extends AbstractITILChildTemplate
{
   // From CommonDBTM
    public $dohistory          = true;
    public $can_be_translated  = true;

    public static $rightname          = 'taskcategory';



    public static function getTypeName($nb = 0)
    {
        return _n('Task template', 'Task templates', $nb);
    }


    public function getAdditionalFields()
    {

        return [['name'  => 'content',
            'label' => __('Content'),
            'type'  => 'tinymce',
            'rows' => 10
        ],
            ['name'  => 'taskcategories_id',
                'label' => TaskCategory::getTypeName(1),
                'type'  => 'dropdownValue',
                'list'  => true
            ],
            ['name'  => 'state',
                'label' => __('Status'),
                'type'  => 'state'
            ],
            ['name'  => 'is_private',
                'label' => __('Private'),
                'type'  => 'bool'
            ],
            ['name'  => 'actiontime',
                'label' => __('Duration'),
                'type'  => 'actiontime'
            ],
            ['name'  => 'users_id_tech',
                'label' => __('By'),
                'type'  => 'users_id_tech'
            ],
            ['name'  => 'groups_id_tech',
                'label' => Group::getTypeName(1),
                'type'  => 'groups_id_tech'
            ],
        ];
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '4',
            'name'               => __('Content'),
            'field'              => 'content',
            'table'              => $this->getTable(),
            'datatype'           => 'text',
            'htmltext'           => true
        ];

        $tab[] = [
            'id'                 => '3',
            'name'               => TaskCategory::getTypeName(1),
            'field'              => 'name',
            'table'              => getTableForItemType('TaskCategory'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'is_private',
            'name'               => __('Private'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('By'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_task' => 1],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'actiontime',
            'name'               => __('Total duration'),
            'datatype'           => 'actiontime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'state',
            'name'               => __('Status'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific'
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'state':
                return Planning::getState($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'state':
                return Planning::dropdownState($name, $values[$field], false);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        switch ($field['type']) {
            case 'state':
                Planning::dropdownState("state", $this->fields["state"], true, [
                    'width'     => '100%',
                ]);
                break;
            case 'users_id_tech':
                User::dropdown([
                    'name'   => "users_id_tech",
                    'right'  => "own_ticket",
                    'value'  => $this->fields["users_id_tech"],
                    'entity' => $this->fields["entities_id"],
                    'width'  => '100%',
                ]);
                break;
            case 'groups_id_tech':
                Group::dropdown([
                    'name'     => "groups_id_tech",
                    'condition' => ['is_task' => 1],
                    'value'     => $this->fields["groups_id_tech"],
                    'entity'    => $this->fields["entities_id"],
                    'width'     => '100%',
                ]);
                break;
            case 'actiontime':
                $toadd = [];
                for ($i = 9; $i <= 100; $i++) {
                    $toadd[] = $i * HOUR_TIMESTAMP;
                }
                Dropdown::showTimeStamp(
                    "actiontime",
                    [
                        'min'             => 0,
                        'max'             => 8 * HOUR_TIMESTAMP,
                        'value'           => $this->fields["actiontime"],
                        'addfirstminutes' => true,
                        'inhours'         => true,
                        'toadd'           => $toadd,
                        'width'           => '100%',
                    ]
                );
                break;
        }
    }

    public static function getIcon()
    {
        return "fas fa-layer-group";
    }
}
