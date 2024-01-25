<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/// OCS Rules class
// @deprecated 10.0.0 @see RuleImportAsset
class RuleImportComputer extends Rule
{
    const RULE_ACTION_LINK_OR_IMPORT    = 0;
    const RULE_ACTION_LINK_OR_NO_IMPORT = 1;


    public $restrict_matching = Rule::AND_MATCHING;
    public $can_sort          = true;

    public static $rightname         = 'rule_import';



    public function getTitle()
    {
        return __('Rules for import and link computers');
    }


    /**
     * @see Rule::maxActionsCount()
     **/
    public function maxActionsCount()
    {
       // Unlimited
        return 1;
    }


    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['entities_id']['table']         = 'glpi_entities';
        $criterias['entities_id']['field']         = 'entities_id';
        $criterias['entities_id']['name']          = __('Target entity for the computer');
        $criterias['entities_id']['linkfield']     = 'entities_id';
        $criterias['entities_id']['type']          = 'dropdown';

        $criterias['states_id']['table']           = 'glpi_states';
        $criterias['states_id']['field']           = 'name';
        $criterias['states_id']['name']            = __('Find computers in GLPI having the status');
        $criterias['states_id']['linkfield']       = 'state';
        $criterias['states_id']['type']            = 'dropdown';
       //Means that this criterion can only be used in a global search query
        $criterias['states_id']['is_global']       = true;
        $criterias['states_id']['allow_condition'] = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT];

        $criterias['DOMAIN']['name']               = Domain::getTypename(1);

        $criterias['IPSUBNET']['name']             = __('Subnet');

        $criterias['MACADDRESS']['name']           = __('MAC address');

        $criterias['IPADDRESS']['name']            = _sn('IP address', 'IP addresses', 1);

        $criterias['name']['name']                 = __("Computer's name");
        $criterias['name']['allow_condition']      = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT,
            Rule::PATTERN_IS_EMPTY,
            Rule::PATTERN_FIND
        ];

        $criterias['DESCRIPTION']['name']          = __('Description');

        $criterias['serial']['name']               = __('Serial number');

       // Model as Text to allow text criteria (contains, regex, ...)
        $criterias['model']['name']                = _n('Model', 'Models', 1);

       // Manufacturer as Text to allow text criteria (contains, regex, ...)
        $criterias['manufacturer']['name']         = Manufacturer::getTypeName(1);

        return $criterias;
    }


    public function getActions()
    {

        $actions                           = parent::getActions();

        $actions['_ignore_import']['name'] = __('To be unaware of import');
        $actions['_ignore_import']['type'] = 'yesonly';

        return $actions;
    }


    public static function getRuleActionValues()
    {

        return [self::RULE_ACTION_LINK_OR_IMPORT
                                          => __('Link if possible'),
            self::RULE_ACTION_LINK_OR_NO_IMPORT
                                          => __('Link if possible, otherwise imports declined')
        ];
    }


    /**
     * Add more action values specific to this type of rule
     *
     * @see Rule::displayAdditionRuleActionValue()
     *
     * @param string value the value for this action
     *
     * @return string the label's value or ''
     **/
    public function displayAdditionRuleActionValue($value)
    {

        $values = self::getRuleActionValues();
        if (isset($values[$value])) {
            return $values[$value];
        }
        return '';
    }


    /**
     * @param $criteria
     * @param $name
     * @param $value
     **/
    public function manageSpecificCriteriaValues($criteria, $name, $value)
    {

        switch ($criteria['type']) {
            case "state":
                $link_array = ["0" => __('No'),
                    "1" => __('Yes if equal'),
                    "2" => __('Yes if empty')
                ];

                Dropdown::showFromArray($name, $link_array, ['value' => $value]);
        }
        return false;
    }


    /**
     * Add more criteria specific to this type of rule
     **/
    public static function addMoreCriteria()
    {

        return [Rule::PATTERN_FIND     => __('is already present in GLPI'),
            Rule::PATTERN_IS_EMPTY => __('is empty in GLPI')
        ];
    }


    public function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern)
    {

        if ($condition == Rule::PATTERN_IS_EMPTY) {
            return __('Yes');
        }
        return false;
    }


    /**
     * @see Rule::displayAdditionalRuleCondition()
     **/
    public function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false)
    {

        if ($test) {
            return false;
        }

        switch ($condition) {
            case Rule::PATTERN_FIND:
            case Rule::PATTERN_IS_EMPTY:
                Dropdown::showYesNo($name, 0, 0);
                return true;
        }

        return false;
    }


    /**
     * @see Rule::displayAdditionalRuleAction()
     **/
    public function displayAdditionalRuleAction(array $action, $value = '')
    {

        switch ($action['type']) {
            case 'fusion_type':
                Dropdown::showFromArray('value', self::getRuleActionValues());
                return true;
        }
        return false;
    }


    /**
     * @param $ID
     **/
    public function getCriteriaByID($ID)
    {

        $criteria = [];
        foreach ($this->criterias as $criterion) {
            if ($ID == $criterion->fields['criteria']) {
                $criteria[] = $criterion;
            }
        }
        return $criteria;
    }


    /**
     * @see Rule::findWithGlobalCriteria()
     **/
    public function findWithGlobalCriteria($input)
    {
        /**
         * @var \DBmysql $DB
         * @var array $PLUGIN_HOOKS
         */
        global $DB, $PLUGIN_HOOKS;

        $complex_criterias = [];
        $continue          = true;
        $global_criteria   = ['manufacturer', 'model', 'name', 'serial'];

       //Add plugin global criteria
        if (isset($PLUGIN_HOOKS['use_rules'])) {
            foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array($this->getType(), $val)) {
                    $global_criteria = Plugin::doOneHook(
                        $plugin,
                        "ruleImportComputer_addGlobalCriteria",
                        $global_criteria
                    );
                }
            }
        }

        foreach ($global_criteria as $criterion) {
            $criteria = $this->getCriteriaByID($criterion);
            if (!empty($criteria)) {
                foreach ($criteria as $crit) {
                    // is a real complex criteria
                    if ($crit->fields["condition"] == Rule::PATTERN_FIND) {
                        if (!isset($input[$criterion]) || ($input[$criterion] == '')) {
                             $continue = false;
                        } else {
                            $complex_criterias[] = $crit;
                        }
                    }
                }
            }
        }

        foreach ($this->getCriteriaByID('states_id') as $crit) {
            $complex_criterias[] = $crit;
        }

       //If a value is missing, then there's a problem !
        if (!$continue) {
            return false;
        }

       //No complex criteria
        if (empty($complex_criterias)) {
            return true;
        }

       //Build the request to check if the machine exists in GLPI
        if (is_array($input['entities_id'])) {
            $where_entity = implode(',', $input['entities_id']);
        } else {
            $where_entity = $input['entities_id'];
        }

        $it_criteria = [
            'SELECT' => 'glpi_computers.id',
            'WHERE'  => [], //to fill
        ];

        foreach ($complex_criterias as $criteria) {
            switch ($criteria->fields['criteria']) {
                case 'name':
                    if ($criteria->fields['condition'] == Rule::PATTERN_IS_EMPTY) {
                        $it_criteria['WHERE']['OR'] = [
                            ['glpi_computers.name' => ''],
                            ['glpi_computers.name'   => null]
                        ];
                    } else {
                        $it_criteria['WHERE'][] = ['glpi_computers.name' => $input['name']];
                    }
                    break;

                case 'serial':
                    $it_criteria['WHERE'][] = ['glpi_computers.serial' => $input['serial']];
                    break;

                case 'model':
                   // search for model, don't create it if not found
                    $options    = ['manufacturer' => addslashes($input['manufacturer'])];
                    $mid        = Dropdown::importExternal(
                        'ComputerModel',
                        addslashes($input['model']),
                        -1,
                        $options,
                        '',
                        false
                    );
                    $it_criteria['WHERE'][] = ['glpi_computers.computermodels_id' => $mid];
                    break;

                case 'manufacturer':
                   // search for manufacturer, don't create it if not found
                    $mid        = Dropdown::importExternal(
                        'Manufacturer',
                        addslashes($input['manufacturer']),
                        -1,
                        [],
                        '',
                        false
                    );
                    $it_criteria['WHERE'][] = ['glpi_computers.manufacturers_id' => $mid];
                    break;

                case 'states_id':
                    $condition = ['glpi_computers.states_id' => $criteria->fields['pattern']];
                    if ($criteria->fields['condition'] == Rule::PATTERN_IS) {
                        $it_criteria['WHERE'][] = $condition;
                    } else {
                        $it_criteria['WHERE'][] = ['NOT' => $condition];
                    }
                    break;
            }
        }

        if (isset($PLUGIN_HOOKS['use_rules'])) {
            foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array($this->getType(), $val)) {
                    $params      = ['where_entity' => $where_entity,
                        'input'        => $input,
                        'criteria'     => $complex_criterias,
                        'sql_where'    => $it_criteria['WHERE'],
                        'sql_from'     => '',
                        'sql_leftjoin' => ''
                    ];
                    $sql_results = Plugin::doOneHook(
                        $plugin,
                        "ruleImportComputer_getSqlRestriction",
                        $params
                    );

                    $sql_from['FROM']          = $sql_results['sql_from'];
                    $sql_where['WHERE']        = $sql_results['sql_where'];
                    $sql_leftjoin['LEFT JOIN'] = $sql_results['sql_leftjoin'];

                    $it_criteria = array_merge_recursive($it_criteria, $sql_from);
                    $it_criteria = array_merge_recursive($it_criteria, $sql_leftjoin);
                    $it_criteria = array_merge_recursive($it_criteria, $sql_where);
                }
            }
        }

        $result_glpi = $DB->request($it_criteria);

        if (count($result_glpi)) {
            foreach ($result_glpi as $data) {
                $this->criterias_results['found_computers'][] = $data['id'];
            }
            return true;
        }

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action->fields['field'] == '_fusion') {
                    if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function executeActions($output, $params, array $input = [])
    {

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                $executeaction = clone $this;
                $ruleoutput    = $executeaction->executePluginsActions($action, $output, $params, $input);
                foreach ($ruleoutput as $key => $value) {
                    $output[$key] = $value;
                }
            }
        }
        return $output;
    }

    /**
     * Function used to display type specific criterias during rule's preview
     *
     * @see Rule::showSpecificCriteriasForPreview()
     **/
    public function showSpecificCriteriasForPreview($fields)
    {

        $entity_as_criteria = false;
        foreach ($this->criterias as $criteria) {
            if ($criteria->fields['criteria'] == 'entities_id') {
                $entity_as_criteria = true;
                break;
            }
        }
        if (!$entity_as_criteria) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan ='2'>" . Entity::getTypeName(1) . "</td>";
            echo "<td>";
            Dropdown::show('Entity');
            echo "</td></tr>";
        }
    }
}
