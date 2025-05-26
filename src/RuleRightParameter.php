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

/// LDAP criteria class
class RuleRightParameter extends CommonDropdown
{
    public static $rightname         = 'rule_ldap';

    public $can_be_translated = false;


    /**
     * @see CommonDBTM::prepareInputForAdd()
     **/
    public function prepareInputForAdd($input)
    {
        //LDAP parameters MUST be in lower case
        //because they are retrieved in lower case  from the directory
        $input["value"] = Toolbox::strtolower($input["value"]);
        return $input;
    }

    public function getAdditionalFields()
    {

        return [
            [
                'name'  => 'value',
                'label' => _n('Criterion', 'Criteria', 1),
                'type'  => 'text',
                'list'  => false,
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => _n('Criterion', 'Criteria', 1),
            'datatype'           => 'string',
        ];

        return $tab;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('LDAP criterion', 'LDAP criteria', $nb);
    }
}
