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
 * Rule class store all information about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
 **/
class RuleDictionnaryPrinter extends Rule
{
   // From Rule
    public $can_sort  = true;

    public static $rightname = 'rule_dictionnary_printer';


    public function getTitle()
    {
        return __('Dictionnary of printers');
    }


    /**
     * @see Rule::maxActionsCount()
     **/
    public function maxActionsCount()
    {
        return 4;
    }

    /**
     * @see Rule::getCriterias()
     **/
    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['name']['field']         = 'name';
        $criterias['name']['name']          = __('Name');
        $criterias['name']['table']         = 'glpi_printers';

        $criterias['manufacturer']['field'] = 'name';
        $criterias['manufacturer']['name']  = Manufacturer::getTypeName(1);
        $criterias['manufacturer']['table'] = '';

        $criterias['comment']['field']      = 'comment';
        $criterias['comment']['name']       = __('Comments');
        $criterias['comment']['table']      = '';

        return $criterias;
    }


    /**
     * @see Rule::getActions()
     **/
    public function getActions()
    {

        $actions                               = parent::getActions();

        $actions['name']['name']               = __('Name');
        $actions['name']['force_actions']      = ['assign', 'regex_result'];

        $actions['_ignore_import']['name']     = __('To be unaware of import');
        $actions['_ignore_import']['type']     = 'yesonly';

        $actions['manufacturer']['name']       = Manufacturer::getTypeName(1);
        $actions['manufacturer']['table']      = 'glpi_manufacturers';
        $actions['manufacturer']['type']       = 'dropdown';

        $actions['is_global']['name']          = __('Management type');
        $actions['is_global']['type']          = 'dropdown_management';

        return $actions;
    }
}
