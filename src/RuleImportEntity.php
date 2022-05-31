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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

use Glpi\Plugin\Hooks;

class RuleImportEntity extends Rule
{
   // From Rule
    public static $rightname = 'rule_import';
    public $can_sort  = true;

    public function getTitle()
    {
        return __('Rules for assigning an item to an entity');
    }


    /**
     * @see Rule::maxActionsCount()
     **/
    public function maxActionsCount()
    {
       // Unlimited
        return 5;
    }

    public function executeActions($output, $params, array $input = [])
    {

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                switch ($action->fields["action_type"]) {
                    case "assign":
                        $output[$action->fields["field"]] = $action->fields["value"];
                        break;

                    case "regex_result":
                      //Assign entity using the regex's result
                        if ($action->fields["field"] == "_affect_entity_by_tag") {
                             //Get the TAG from the regex's results
                            if (isset($this->regex_results[0])) {
                                $res = RuleAction::getRegexResultById(
                                    $action->fields["value"],
                                    $this->regex_results[0]
                                );
                            } else {
                                $res = $action->fields["value"];
                            }
                            if ($res != null) {
                                 //Get the entity associated with the TAG
                                 $target_entity = Entity::getEntityIDByTag(addslashes($res));
                                if ($target_entity != '') {
                                    $output["entities_id"] = $target_entity;
                                }
                            }
                        }
                        break;
                }
            }
        }
        return $output;
    }


    public function getCriterias()
    {

        return [
            'tag' => [
                'field' => 'name',
                'name' => __('Inventory tag')
            ],
            'domain' => [
                'field' => 'name',
                'name' => Domain::getTypeName(1)
            ],
            'subnet' => [
                'field' => 'name',
                'name' => __('Subnet')
            ],
            'ip' => [
                'field' => 'name',
                'name' => IPAddress::getTypeName(1)
            ],
            'name' => [
                'field' => 'name',
                'name' => __("Equipment name")
            ],
            'serial' => [
                'field' => 'name',
                'name' => __('Serial number')
            ],
            '_source' => [
                'table' => '',
                'field' => '_source',
                'name' => __('Source'),
                'allow_condition' => [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT]
            ]
        ];
    }


    /**
     * @since 0.84
     *
     * @see Rule::displayAdditionalRuleCondition()
     **/
    public function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false)
    {
        global $PLUGIN_HOOKS;

        if ($criteria['field'] == '_source') {
            $tab = ['GLPI' => __('GLPI')];
            foreach ($PLUGIN_HOOKS['import_item'] as $plug => $types) {
                if (!Plugin::isPluginActive($plug)) {
                    continue;
                }
                $tab[$plug] = Plugin::getInfo($plug, 'name');
            }
            Dropdown::showFromArray($name, $tab);
            return true;
        }

        switch ($condition) {
            case Rule::PATTERN_FIND:
                return false;

            case Rule::PATTERN_IS_EMPTY:
                Dropdown::showYesNo($name, 0, 0);
                return true;

            case Rule::PATTERN_EXISTS:
                echo Dropdown::showYesNo($name, 1, 0);
                return true;

            case Rule::PATTERN_DOES_NOT_EXISTS:
                echo Dropdown::showYesNo($name, 1, 0);
                return true;
        }
        return false;
    }


    public function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern)
    {

        $crit = $this->getCriteria($ID);
        if (count($crit) && $crit['field'] == '_source') {
            if ($pattern != 'GLPI') {
                $name = Plugin::getInfo($pattern, 'name');
                if (empty($name)) {
                    return false;
                }
            } else {
                $name = $pattern;
            }
            return $name;
        }
        return false;
    }


    public function process(&$input, &$output, &$params, &$options = [])
    {

        if ($this->validateCriterias($options)) {
            $this->regex_results     = [];
            $this->criterias_results = [];
            $input = $this->prepareInputDataForProcess($input, $params);

            if ($this->checkCriterias($input)) {
                unset($output["_no_rule_matches"]);
                $refoutput = $output;
                $output = $this->executeActions($output, $params);
                if (!isset($output['pass_rule'])) {
                    $this->updateOnlyCriteria($options, $refoutput, $output);
                   //Hook
                    $hook_params["sub_type"] = $this->getType();
                    $hook_params["ruleid"]   = $this->fields["id"];
                    $hook_params["input"]    = $input;
                    $hook_params["output"]   = $output;
                    Plugin::doHook(Hooks::RULE_MATCHED, $hook_params);
                    $output["_rule_process"] = true;
                }
            }
        }
    }


    public function getActions()
    {
        $actions = [
            'entities_id' => [
                'name' => Entity::getTypeName(1),
                'type' => 'dropdown',
                'table' => Entity::getTable()
            ],
            'locations_id' => [
                'name' => Location::getTypeName(1),
                'type' => 'dropdown',
                'table' => Location::getTable(),
            ],
            '_affect_entity_by_tag' => [
                'name' => __('Entity from TAG'),
                'type' => 'text',
                'force_actions' => ['regex_result'],
            ],
            '_ignore_import' => [
                'name' => __('Refuse import'),
                'type' => 'yesonly'
            ],
            'is_recursive' => [
                'name' => __('Child entities'),
                'type' => 'yesno'
            ],
            'groups_id_tech' => [
                'name' => __('Group in charge of the hardware'),
                'type' => 'dropdown',
                'table' => Group::getTable()
            ],
            'users_id_tech' => [
                'name' => __('Technician in charge of the hardware'),
                'type' => 'dropdown_users'
            ]
        ];
        $actions = array_merge(parent::getActions(), $actions);

        return $actions;
    }


    public static function getIcon()
    {
        return Entity::getIcon();
    }
}
