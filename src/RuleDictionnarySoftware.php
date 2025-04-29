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
 * Rule class store all information about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
 **/
class RuleDictionnarySoftware extends Rule
{
    public $additional_fields_for_dictionnary = ['manufacturer'];
    public $can_sort                          = true;

    public static $rightname                         = 'rule_dictionnary_software';



    /**
     * @see Rule::getTitle()
     **/
    public function getTitle()
    {
        //TRANS: plural for software
        return __('Dictionary of software');
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
        $criterias['name']['name']          = _n('Software', 'Software', 1);
        $criterias['name']['table']         = 'glpi_softwares';

        $criterias['manufacturer']['field'] = 'name';
        $criterias['manufacturer']['name']  = __('Publisher');
        $criterias['manufacturer']['table'] = 'glpi_manufacturers';

        $criterias['entities_id']['field']  = 'completename';
        $criterias['entities_id']['name']   = Entity::getTypeName(1);
        $criterias['entities_id']['table']  = 'glpi_entities';
        $criterias['entities_id']['type']   = 'dropdown';

        $criterias['_system_category']['field'] = 'name';
        $criterias['_system_category']['name']  = __('Category from inventory tool');

        return $criterias;
    }


    /**
     * @see Rule::getActions()
     **/
    public function getActions()
    {

        $actions                                  = parent::getActions();

        $actions['name']['name']                  = _n('Software', 'Software', 1);
        $actions['name']['force_actions']         = ['assign', 'regex_result'];

        $actions['_ignore_import']['name']        = __('To be unaware of import');
        $actions['_ignore_import']['type']        = 'yesonly';

        $actions['version']['name']               = _n('Version', 'Versions', 1);
        $actions['version']['force_actions']      = ['assign','regex_result',
            'append_regex_result',
        ];

        $actions['manufacturer']['name']          = __('Publisher');
        $actions['manufacturer']['table']         = 'glpi_manufacturers';
        $actions['manufacturer']['force_actions'] = ['append_regex_result', 'assign','regex_result'];

        $actions['is_helpdesk_visible']['name']   = __('Associable to a ticket');
        $actions['is_helpdesk_visible']['table']  = 'glpi_softwares';
        $actions['is_helpdesk_visible']['type']   = 'yesno';

        $actions['new_entities_id']['name']       = Entity::getTypeName(1);
        $actions['new_entities_id']['table']      = 'glpi_entities';
        $actions['new_entities_id']['type']       = 'dropdown';

        $actions['softwarecategories_id']['name']  = _n('Category', 'Categories', 1);
        $actions['softwarecategories_id']['type']  = 'dropdown';
        $actions['softwarecategories_id']['table'] = 'glpi_softwarecategories';
        $actions['softwarecategories_id']['force_actions'] = ['assign','regex_result'];

        return $actions;
    }


    /**
     * @see Rule::addSpecificParamsForPreview()
     **/
    public function addSpecificParamsForPreview($params)
    {

        if (isset($_POST["version"])) {
            $params["version"] = $_POST["version"];
        }
        return $params;
    }


    /**
     * @see Rule::showSpecificCriteriasForPreview()
     **/
    public function showSpecificCriteriasForPreview($fields)
    {

        if (isset($this->fields['id'])) {
            $this->getRuleWithCriteriasAndActions($this->fields['id'], 0, 1);
        }

        //if there's a least one action with type == append_regex_result, then need to display
        //this field as a criteria
        foreach ($this->actions as $action) {
            if ($action->fields["action_type"] == "append_regex_result") {
                $value = ($fields[$action->fields['field']] ?? '');
                //Get actions for this type of rule
                $actions = $this->getAllActions();

                //display the additionnal field
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . $this->fields['match'] . "</td>";
                echo "<td>" . $actions[$action->fields['field']]['name'] . "</td>";
                echo "<td><input type='text' name='version' value='$value'></td></tr>";
            }
        }
    }
}
