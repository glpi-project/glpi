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
 * TaskCategory class
 **/
class TaskCategory extends CommonTreeDropdown
{
    use Clonable;

    // From CommonDBTM
    public $dohistory          = true;
    public $can_be_translated  = true;

    public static $rightname          = 'taskcategory';

    public function getAdditionalFields()
    {

        $tab = parent::getAdditionalFields();

        $tab[] = ['name'  => 'is_active',
            'label' => __('Active'),
            'type'  => 'bool',
        ];

        $tab[] = ['name'  => 'knowbaseitemcategories_id',
            'label' => KnowbaseItemCategory::getTypeName(),
            'type'  => 'dropdownValue',
            'list'  => true,
        ];

        return $tab;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        return $tab;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Task category', 'Task categories', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-tags";
    }

    public function getCloneRelations(): array
    {
        return [];
    }
}
