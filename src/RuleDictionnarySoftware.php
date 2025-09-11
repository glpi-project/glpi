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

use Glpi\Application\View\TemplateRenderer;

/**
 * Rule class store all information about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
 **/
class RuleDictionnarySoftware extends Rule
{
    public $additional_fields_for_dictionnary = ['manufacturer'];

    public static $rightname                         = 'rule_dictionnary_software';


    public function getTitle()
    {
        //TRANS: plural for software
        return __('Dictionary of software');
    }

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

    public function addSpecificParamsForPreview($params)
    {
        if (isset($_POST["version"])) {
            $params["version"] = $_POST["version"];
        }
        return $params;
    }

    public function showSpecificCriteriasForPreview($fields)
    {
        if (isset($this->fields['id'])) {
            $this->getRuleWithCriteriasAndActions($this->fields['id'], false, true);
        }

        $twig_params = [
            'actions' => $this->actions,
            'values' => $fields,
            'action_names' => [],
            'type_match' => ($this->fields['match'] ?? Rule::AND_MATCHING) ? __('AND') : __('OR'),
        ];
        $actions = $this->getAllActions();
        foreach ($actions as $key => $action) {
            $twig_params['action_names'][$key] = $action['name'];
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% for action in actions %}
                {% if action.fields['action_type'] == 'append_regex_result' %}
                    {{ fields.htmlField('', type_match|e, '', {
                        no_label: true,
                        field_class: 'col-2',
                        input_class: 'col-12'
                    }) }}
                    {{ fields.textField('version', values[action.fields['field']]|default(''), action_names[action.fields['field']], {
                        field_class: 'col-10',
                        label_class: 'col-5',
                        input_class: 'col-7'
                    }) }}
                {% endif %}
            {% endfor %}
TWIG, $twig_params);
    }
}
