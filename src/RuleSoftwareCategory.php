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

/**
 * Rule class store all information about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
 *
 **/
class RuleSoftwareCategory extends Rule
{
   // From Rule
    public static $rightname = 'rule_softwarecategories';
    public $can_sort  = true;


    public function getTitle()
    {
        return __('Rules for assigning a category to software');
    }


    /**
     * @see Rule::maxActionsCount()
     **/
    public function maxActionsCount()
    {
        return 1;
    }


    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['name']['field']         = 'name';
        $criterias['name']['name']          = _n('Software', 'Software', Session::getPluralNumber());
        $criterias['name']['table']         = 'glpi_softwares';

        $criterias['manufacturer']['field'] = 'name';
        $criterias['manufacturer']['name']  = __('Publisher');
        $criterias['manufacturer']['table'] = 'glpi_manufacturers';

        $criterias['comment']['field']      = 'comment';
        $criterias['comment']['name']       = __('Comments');
        $criterias['comment']['table']      = 'glpi_softwares';

        $criterias['_system_category']['field'] = 'name';
        $criterias['_system_category']['name']  = __('Category from inventory tool');

        return $criterias;
    }


    public function getActions()
    {

        $actions                                   = parent::getActions();

        $actions['softwarecategories_id']['name']  = _n('Category', 'Categories', 1);
        $actions['softwarecategories_id']['type']  = 'dropdown';
        $actions['softwarecategories_id']['table'] = 'glpi_softwarecategories';
        $actions['softwarecategories_id']['force_actions'] = ['assign','regex_result'];

        $actions['_import_category']['name'] = __('Import category from inventory tool');
        $actions['_import_category']['type'] = 'yesonly';

        $actions['_ignore_import']['name']  = __('To be unaware of import');
        $actions['_ignore_import']['type']  = 'yesonly';

        return $actions;
    }

    public static function getIcon()
    {
        return SoftwareCategory::getIcon();
    }
}
