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

use Glpi\Features\Clonable;

/**
 * Template for task
 * @since 9.1
 **/
class TaskTemplate extends AbstractITILChildTemplate
{
    use Clonable;

    // From CommonDBTM
    public $dohistory          = true;
    public $can_be_translated  = true;

    public static $rightname          = 'tasktemplate';

    public function post_getFromDB()
    {
        if (isset($this->fields['use_current_user']) && $this->fields['use_current_user']) {
            $this->fields['users_id_tech'] = -1;
        }
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Task template', 'Task templates', $nb);
    }


    public function getAdditionalFields()
    {

        return [['name'  => 'content',
            'label' => __('Content'),
            'type'  => 'tinymce',
            // Images should remains in base64 in templates.
            // When an element will be created from a template, tinymce will catch the base64 image and trigger the
            // document upload process.
            'convert_images_to_documents' => false,
        ],
            ['name'  => 'taskcategories_id',
                'label' => TaskCategory::getTypeName(1),
                'type'  => 'dropdownValue',
                'list'  => true,
            ],
            ['name'  => 'state',
                'label' => __('Status'),
                'type'  => 'state',
            ],
            ['name'  => 'is_private',
                'label' => __('Private'),
                'type'  => 'bool',
            ],
            ['name'  => 'actiontime',
                'label' => __('Duration'),
                'type'  => 'actiontime',
            ],
            ['name'  => 'users_id_tech',
                'label' => __('By'),
                'type'  => 'users_id_tech',
            ],
            ['name'  => 'groups_id_tech',
                'label' => Group::getTypeName(1),
                'type'  => 'groups_id_tech',
            ], [
                'name'  => 'pendingreasons_id',
                'label' => PendingReason::getTypeName(1),
                'type'  => 'dropdownValue',
                'list'  => true,
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
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '3',
            'name'               => TaskCategory::getTypeName(1),
            'field'              => 'name',
            'table'              => getTableForItemType('TaskCategory'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'is_private',
            'name'               => __('Private'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'users_id_tech',
            'name'               => __('By'),
            'searchtype'         => [
                '0'                  => 'equals',
                '1'                  => 'notequals',
            ],
            'datatype'           => 'specific',
            'additionalfields'   => ['use_current_user'],
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_task' => 1],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'actiontime',
            'name'               => __('Total duration'),
            'datatype'           => 'timestamp',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'state',
            'name'               => __('Status'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
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
                return htmlescape(Planning::getState($values[$field]));
            case 'users_id_tech':
                if (isset($values['use_current_user']) && $values['use_current_user'] == 1) {
                    return __s('Current logged-in user');
                }

                return getUserLink($values[$field]);
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
            case 'users_id_tech':
                return User::dropdown([
                    'name'   => $name,
                    'aria_label' => __('By'),
                    'right'  => 'own_ticket',
                    'value'  => $values[$field],
                    'width'  => '100%',
                    'display' => false,
                    'toadd'  => [
                        [
                            'id'   => -1,
                            'text' => __('Current logged-in user'),
                        ],
                    ],
                ]);
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
                    'aria_label' => __('By'),
                    'right'  => "own_ticket",
                    'value'  => $this->fields["users_id_tech"],
                    'entity' => $this->fields["entities_id"],
                    'width'  => '100%',
                    'toadd'  => [
                        [
                            'id'   => -1,
                            'text' => __('Current logged-in user'),
                        ],
                    ],
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

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        $input = $this->prepareInput($input);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);

        $input = $this->prepareInput($input);

        return $input;
    }

    private function prepareInput($input)
    {
        if ($input === false) {
            return false;
        }

        if (isset($input['users_id_tech']) && (int) $input['users_id_tech'] == -1) {
            $input['use_current_user'] = 1;
            $input['users_id_tech'] = 0;
        } elseif (isset($input['users_id_tech'])) {
            $input['use_current_user'] = 0;
        }

        return $input;
    }

    public static function getIcon()
    {
        return "ti ti-stack-2-filled";
    }

    public function getCloneRelations(): array
    {
        return [];
    }

    public static function addWhere($link, $nott, $itemtype, $ID, $searchtype, $val)
    {
        if ($itemtype !== self::class) {
            return false;
        }

        $searchopt = Search::getOptions($itemtype);
        if (!isset($searchopt[$ID]['field'])) {
            return false;
        }

        $field = $searchopt[$ID]['field'];
        $table = self::getTable();

        if ($field === 'users_id_tech') {
            $positive_condition = ($nott == 0 && $searchtype == 'equals') || ($nott == 1 && $searchtype == 'notequals') || ($nott == 0 && $searchtype == 'empty');
            $table_use_current_user = "`$table`.`use_current_user`";
            $table_users_id_tech = "`$table`.`users_id_tech`";
            $int_val = (int) $val;

            if ($val == -1) {
                if ($positive_condition) {
                    return " $link ($table_use_current_user = 1)";
                } else {
                    return " $link ($table_use_current_user = 0)";
                }
            } elseif ($val == 0) {
                if ($positive_condition) {
                    return " $link ($table_use_current_user = 0 AND $table_users_id_tech = 0)";
                } else {
                    return " $link ($table_use_current_user = 1 OR $table_users_id_tech != 0)";
                }
            } elseif ($val == 'null' && $searchtype == 'empty') {
                if ($positive_condition) {
                    return " $link ($table_use_current_user = 0 AND $table_users_id_tech = 0)";
                } else {
                    return " $link ($table_use_current_user = 1 OR $table_users_id_tech != 0)";
                }
            } else {
                if ($positive_condition) {
                    return " $link ($table_use_current_user = 0 AND $table_users_id_tech = $int_val)";
                } else {
                    return " $link ($table_use_current_user = 1 OR $table_users_id_tech != $int_val)";
                }
            }
        }

        return false;
    }
}
