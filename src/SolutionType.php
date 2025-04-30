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

/// SolutionType class
class SolutionType extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n('Solution type', 'Solution types', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-check";
    }

    public function getAdditionalFields()
    {
        $tab = [
            [
                'name'      => 'is_incident',
                'label'     => __('Visible for an incident'),
                'type'      => 'bool',
                'list'      => true,
            ],
            [
                'name'      => 'is_request',
                'label'     => __('Visible for a request'),
                'type'      => 'bool',
                'list'      => true,
            ],
            [
                'name'  => 'is_problem',
                'label' => __('Visible for a problem'),
                'type'  => 'bool',
                'list'  => true,
            ],
            [
                'name'  => 'is_change',
                'label' => __('Visible for a change'),
                'type'  => 'bool',
                'list'  => true,
            ],
        ];
        return $tab;
    }


    public function rawSearchOptions()
    {
        $tab                       = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '74',
            'table'              => $this->getTable(),
            'field'              => 'is_incident',
            'name'               => __('Visible for an incident'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '75',
            'table'              => $this->getTable(),
            'field'              => 'is_request',
            'name'               => __('Visible for a request'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '76',
            'table'              => $this->getTable(),
            'field'              => 'is_problem',
            'name'               => __('Visible for a problem'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '85',
            'table'              => $this->getTable(),
            'field'              => 'is_change',
            'name'               => __('Visible for a change'),
            'datatype'           => 'bool',
        ];

        return $tab;
    }


    public function post_getEmpty()
    {

        $this->fields['is_request']         = 1;
        $this->fields['is_incident']        = 1;
        $this->fields['is_problem']         = 1;
        $this->fields['is_change']          = 1;
    }
}
