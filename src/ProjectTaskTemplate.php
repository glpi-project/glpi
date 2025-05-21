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
 * Template for task
 * @since 9.2
 **/
class ProjectTaskTemplate extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory          = true;
    public $can_be_translated  = true;

    public static $rightname          = 'project';

    public static function getTypeName($nb = 0)
    {
        return _n('Project task template', 'Project task templates', $nb);
    }


    public function getAdditionalFields()
    {

        return [['name'  => 'projectstates_id',
            'label' => _x('item', 'State'),
            'type'  => 'dropdownValue',
            'list'  => true,
        ],
            ['name'  => 'projecttasktypes_id',
                'label' => _n('Type', 'Types', 1),
                'type'  => 'dropdownValue',
            ],
            ['name'  => 'projecttasks_id',
                'label' => __('As child of'),
                'type'  => 'dropdownValue',
            ],
            ['name'  => 'percent_done',
                'label' => __('Percent done'),
                'type'  => 'percent_done',
            ],
            ['name'  => 'is_milestone',
                'label' => __('Milestone'),
                'type'  => 'bool',
            ],
            ['name'  => 'plan_start_date',
                'label' => __('Planned start date'),
                'type'  => 'datetime',
            ],
            ['name'  => 'real_start_date',
                'label' => __('Real start date'),
                'type'  => 'datetime',
            ],
            ['name'  => 'plan_end_date',
                'label' => __('Planned end date'),
                'type'  => 'datetime',
            ],
            ['name'  => 'real_end_date',
                'label' => __('Real end date'),
                'type'  => 'datetime',
            ],
            ['name'  => 'planned_duration',
                'label' => __('Planned duration'),
                'type'  => 'actiontime',
            ],
            ['name'  => 'effective_duration',
                'label' => __('Effective duration'),
                'type'  => 'actiontime',
            ],
            ['name'  => 'comments',
                'label' => _n('Comment', 'Comments', Session::getPluralNumber()),
                'type'  => 'textarea',
            ],
            ['name'  => 'description',
                'label' => __('Description'),
                'type'  => 'tinymce',
                // Images should remains in base64 in templates.
                // When an element will be created from a template, tinymce will catch the base64 image and trigger the
                // document upload process.
                'convert_images_to_documents' => false,

            ],
        ];
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'       => '4',
            'name'     => _x('item', 'State'),
            'field'    => 'name',
            'table'    => 'glpi_projectstates',
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'       => '5',
            'name'     => _n('Type', 'Types', 1),
            'field'    => 'name',
            'table'    => 'glpi_projecttasktypes',
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'       => '6',
            'name'     => __('As child of'),
            'field'    => 'name',
            'table'    => 'glpi_projecttasks',
            'datatype' => 'itemlink',
        ];

        $tab[] = [
            'id'       => '7',
            'name'     => __('Percent done'),
            'field'    => 'percent_done',
            'table'    => $this->getTable(),
            'datatype' => 'progressbar',
        ];

        $tab[] = [
            'id'       => '8',
            'name'     => __('Milestone'),
            'field'    => 'is_milestone',
            'table'    => $this->getTable(),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '9',
            'name'     => __('Planned start date'),
            'field'    => 'plan_start_date',
            'table'    => $this->getTable(),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'       => '10',
            'name'     => __('Real start date'),
            'field'    => 'real_start_date',
            'table'    => $this->getTable(),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'       => '11',
            'name'     => __('Planned end date'),
            'field'    => 'plan_end_date',
            'table'    => $this->getTable(),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'       => '12',
            'name'     => __('Real end date'),
            'field'    => 'real_end_date',
            'table'    => $this->getTable(),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'       => '13',
            'name'     => __('Planned duration'),
            'field'    => 'planned_duration',
            'table'    => $this->getTable(),
            'datatype' => 'timestamp',
        ];

        $tab[] = [
            'id'       => '14',
            'name'     => __('Effective duration'),
            'field'    => 'effective_duration',
            'table'    => $this->getTable(),
            'datatype' => 'timestamp',
        ];

        $tab[] = [
            'id'       => '15',
            'name'     => __('Description'),
            'field'    => 'description',
            'table'    => $this->getTable(),
            'datatype' => 'text',
            'htmltext' => true,
        ];

        return $tab;
    }


    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        switch ($field['type']) {
            case 'percent_done':
                Dropdown::showNumber("percent_done", [
                    'value'     => $this->fields['percent_done'],
                    'min'       => 0,
                    'max'       => 100,
                    'step'      => 5,
                    'unit'      => '%',
                    'width'     => '100%',
                ]);
                break;
            case 'actiontime':
                Dropdown::showTimeStamp($field["name"], [
                    'min'             => 0,
                    'max'             => 100 * HOUR_TIMESTAMP,
                    'step'            => HOUR_TIMESTAMP,
                    'value'           => $this->fields[$field["name"]],
                    'addfirstminutes' => true,
                    'inhours'         => true,
                    'width'           => '100%',
                ]);
                break;
        }
    }


    public function defineTabs($options = [])
    {

        $ong = parent::defineTabs($options);
        $this->addStandardTab(Document_Item::class, $ong, $options);

        return $ong;
    }

    public static function getIcon()
    {
        return "ti ti-stack-2-filled";
    }
}
