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

use Glpi\Event;

class RuleCollection extends CommonDBTM
{
    public const MOVE_BEFORE = 'before';
    public const MOVE_AFTER = 'after';

    /// Rule type
    public $sub_type;
    /// process collection stop on first matched rule
    public $stop_on_first_match                   = false;
    /// field used to order rules
    public $orderby                               = "ranking";
    /// Processing several rules : use result of the previous one to computer the current one
    public $use_output_rule_process_as_next_input = false;
    /// Rule collection can be replay (for dictionary)
    public $can_replay_rules                      = false;
    /// List of rules of the rule collection
    public $RuleList                              = null;
    /// Menu type
    public $menu_type                             = "rule";
    /// Menu option
    public $menu_option                           = "";

    public $entity                                = 0;

    public static $rightname                             = 'config';


    /// Tab orientation : horizontal or vertical
    public $taborientation = 'horizontal';

    public static function getTable($classname = null)
    {
        return parent::getTable('Rule');
    }


    /**
     * @param $entity (default 0)
     **/
    public function setEntity($entity = 0)
    {
        $this->entity = $entity;
    }


    public function canList()
    {
        return static::canView();
    }


    public function isEntityAssign()
    {
        return false;
    }


    /**
     * Get Collection Size : retrieve the number of rules
     *
     * @param boolean $recursive (true by default)
     * @param integer $condition (0 by default)
     * @param integer $children (0 by default)
     *
     * @return integer number of rules
     **/
    public function getCollectionSize(
        $recursive = true,
        $condition = 0,
        $children = 0
    ) {
        /** @var \DBmysql $DB */
        global $DB;

        $restrict = $this->getRuleListCriteria([
            'condition' => $condition,
            'active'    => false,
            'inherited' => $recursive,
            'childrens' => $children,
        ]);

        $iterator = $DB->request($restrict);
        return count($iterator);
    }


    /**
     * Get rules list criteria
     *
     * @param array $options Options
     *
     * @return array
     **/
    public function getRuleListCriteria($options = [])
    {
        $p['active']    = true;
        $p['start']     = 0;
        $p['limit']     = 0;
        $p['inherited'] = 1;
        $p['childrens'] = 0;
        $p['condition'] = 0;

        foreach ($options as $key => $value) {
            $p[$key] = $value;
        }

        $criteria = [
            'SELECT' => Rule::getTable() . '.*',
            'FROM'   => Rule::getTable(),
            'ORDER'  => [
                $this->orderby . ' ASC',
            ],
        ];

        $where = [];
        if ($p['active']) {
            $where['is_active'] = 1;
        }

        if ($p['condition'] > 0) {
            $where['condition'] = ['&', (int) $p['condition']];
        }

        //Select all the rules of a different type
        $where['sub_type'] = $this->getRuleClassName();
        if ($this->isRuleRecursive()) {
            $criteria['LEFT JOIN'] = [
                Entity::getTable() => [
                    'ON' => [
                        Entity::getTable()   => 'id',
                        Rule::getTable()     => 'entities_id',
                    ],
                ],
            ];

            if (!$p['childrens']) {
                $where += getEntitiesRestrictCriteria(
                    Rule::getTable(),
                    'entities_id',
                    $this->entity,
                    $p['inherited']
                );
            } else {
                $sons = getSonsOf('glpi_entities', $this->entity);
                $where[Rule::getTable() . '.entities_id'] = $sons;
            }

            $criteria['ORDER'] = [
                Entity::getTable() . '.level ASC',
                $this->orderby . ' ASC',
            ];
        }

        if ($p['limit']) {
            $criteria['LIMIT'] = (int) $p['limit'];
            $criteria['START'] = (int) $p['start'];
        }

        $criteria['WHERE'] = $where;

        if (method_exists($this, 'collectionFilter')) {
            $filter_opts = [];
            if (isset($options['_glpi_tab'])) {
                $filter_opts['_glpi_tab'] = $options['_glpi_tab'];
            }
            $criteria = $this->collectionFilter($criteria, $filter_opts);
        }

        return $criteria;
    }

    /**
     * Get Collection Part : retrieve descriptions of a range of rules
     *
     * @param array $options array of options may be :
     *         - start : first rule (in the result set - default 0)
     *         - limit : max number of rules to retrieve (default 0)
     *         - recursive : boolean get recursive rules
     *         - childirens : boolean get childrens rules
     **/
    public function getCollectionPart($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $p['start']     = 0;
        $p['limit']     = 0;
        $p['recursive'] = true;
        $p['childrens'] = 0;
        $p['condition'] = 0;

        foreach ($options as $key => $value) {
            $p[$key] = $value;
        }

        // no need to use SingletonRuleList::getInstance because we read only 1 page
        $this->RuleList       = new SingletonRuleList();
        $this->RuleList->list = [];

        //Select all the rules of a different type
        $criteria   = $this->getRuleListCriteria($p);

        $iterator   = $DB->request($criteria);

        $active_tab = Session::getActiveTab($this->getType());
        $can_sort = !(str_starts_with($this->getType() . '$', $active_tab));

        foreach ($iterator as $data) {
            //For each rule, get a Rule object with all the criterias and actions
            $tempRule               = $this->getRuleClass();
            $tempRule->fields       = $data;
            $tempRule->can_sort = $can_sort;

            $this->RuleList->list[] = $tempRule;
        }
    }


    /**
     * Get Collection Data: retrieve descriptions and rules
     *
     * @param integer $retrieve_criteria  Retrieve the criteria of the rules ? (default false)
     * @param integer $retrieve_action    Retrieve the action of the rules ? (default 0)
     * @param integer $condition          Retrieve with a specific condition
     **/
    public function getCollectionDatas($retrieve_criteria = 0, $retrieve_action = 0, $condition = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($this->RuleList === null) {
            $this->RuleList = SingletonRuleList::getInstance(
                $this->getRuleClassName(),
                $this->entity
            );
        }
        $need = 1 + ($retrieve_criteria ? 2 : 0) + ($retrieve_action ? 4 : 0) + (8 * $condition);

        // check if load required
        if (($need & $this->RuleList->load) != $need) {
            //Select all the rules of a different type
            $criteria = $this->getRuleListCriteria(['condition' => $condition]);
            $iterator = $DB->request($criteria);

            if (count($iterator)) {
                $this->RuleList->list = [];
                $active_tab = Session::getActiveTab($this->getType());
                $can_sort = !(str_starts_with($this->getType() . '$', $active_tab));

                foreach ($iterator as $rule) {
                    //For each rule, get a Rule object with all the criterias and actions
                    $tempRule = $this->getRuleClass();

                    if (
                        $tempRule->getRuleWithCriteriasAndActions(
                            $rule["id"],
                            $retrieve_criteria,
                            $retrieve_action
                        )
                    ) {
                        $tempRule->can_sort = $can_sort;

                        //Add the object to the list of rules
                        $this->RuleList->list[] = $tempRule;
                    }
                }

                $this->RuleList->load = $need;
            }
        }
    }


    public function getRuleClassName()
    {

        if (preg_match('/(.*)Collection/', get_class($this), $rule_class)) {
            return $rule_class[1];
        }
        return "";
    }


    /**
     * Get a instance of the class to manipulate rule of this collection
     **/
    public function getRuleClass()
    {

        $name = $this->getRuleClassName();
        if ($name !=  '') {
            return new $name();
        }
        return null;
    }


    /**
     * Is a confirmation needed before replay on DB ?
     * If needed need to send 'replay_confirm' in POST
     *
     * @param string $target filename : where to go when done
     *
     * @return boolean true if confirmation is needed, else false
     **/
    public function warningBeforeReplayRulesOnExistingDB($target)
    {
        return false;
    }


    /**
     * Replay Collection on DB
     *
     * @param integer $offset  first row to work on (default 0)
     * @param float   $maxtime max system time to stop working (default 0)
     * @param array   $items   array containing items to replay. If empty -> all
     * @param array   $params  array additional parameters if needed
     *
     * @return int|false -1 if all rows done, else offset for next run, or false on error
     **/
    public function replayRulesOnExistingDB($offset = 0, $maxtime = 0, $items = [], $params = [])
    {
        return false;
    }


    /**
     * Get title used in list of rules
     *
     * @return string Title of the rule collection
     **/
    public function getTitle()
    {
        return __('Rules list');
    }


    /**
     * Indicates if the rule can be affected to an entity or if it's global
     *
     * @return boolean
     **/
    public function isRuleEntityAssigned()
    {

        $rule = $this->getRuleClass();
        return $rule->isEntityAssign();
    }


    /**
     * Indicates if the rule can be affected to an entity or if it's global
     *
     * @return boolean
     **/
    public function isRuleRecursive()
    {

        $rule = $this->getRuleClass();
        return $rule->maybeRecursive();
    }

    /**
     * Indicates if the rule use conditions
     *
     * @return boolean
     **/
    public function isRuleUseConditions()
    {

        $rule = $this->getRuleClass();
        return $rule->useConditions();
    }

    /**
     * Get default rule conditions
     *
     * @return integer
     **/
    public function getDefaultRuleConditionForList()
    {

        $rule = $this->getRuleClass();
        $cond = $rule->getConditionsArray();
        // Get max value
        if (count($cond)) {
            return max(array_keys($cond));
        }
        return 0;
    }

    public function showEngineSummary()
    {

        echo "<table class='tab_cadre_fixe'><tr><th>";

        //Display information about how the rules engine process the rules
        if ($this->stop_on_first_match) {
            //The engine stop on the first matched rule
            echo "<span class='center b'>" . __('The engine stops on the first checked rule.') .
              "</span><br>";
        } else {
            //The engine process all the rules
            echo "<span class='center b'>" . __('The engine treats all the rules.') . "</span><br>";
        }

        if ($this->use_output_rule_process_as_next_input) {
            //The engine keep the result of a rule to be processed further
            echo "<span class='center b'>" .
                __('The engine passes the result of a rule to the following one.') . "</span><br>";
        }

        if ($this->isRuleUseConditions()) {
            //The engine keep the result of a rule to be processed further
            echo "<span class='center b'>" .
                __('Rules are conditionals. Each one can be used on multiple actions.');
            echo "</span><br>";
        }

        echo "</th></tr>";
        echo "</table>\n";
    }


    /**
     * Show the list of rules
     *
     * @param string $target
     * @param array $options
     *
     * @return void
     **/
    public function showListRules($target, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p['inherited'] = 1;
        $p['childrens'] = 0;
        $p['active']    = false;
        $p['condition'] = 0;
        $p['_glpi_tab'] = $options['_glpi_tab'];
        $rand           = mt_rand();
        $p['display_criterias'] = false;
        $p['display_actions']   = false;

        foreach (['inherited','childrens', 'condition'] as $param) {
            if (
                isset($options[$param])
                && $this->isRuleRecursive()
            ) {
                $p[$param] = $options[$param];
            }
        }

        foreach (['display_criterias', 'display_actions'] as $param) {
            if (
                isset($options[$param])
            ) {
                $p[$param] = $options[$param];
            }
        }

        $rule              = $this->getRuleClass();
        $display_entities  = ($this->isRuleRecursive()
                            && ($p['inherited'] || $p['childrens']));
        $display_criterias = $p['display_criterias'];
        $display_actions   = $p['display_actions'];

        // Do not know what it is ?
        $canedit    = (self::canUpdate()
                     && !$display_entities);

        $use_conditions = false;
        if ($rule->useConditions()) {
            // First get saved option
            $p['condition'] = Session::getSavedOption($this->getType(), 'condition', 0);
            if ($p['condition'] == 0) {
                $p['condition'] = $this->getDefaultRuleConditionForList();
            }
            $use_conditions = true;
            // Mini Search engine
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><td class='center' width='50%'>";
            echo __('Rules used for') . "</td><td>";
            $rule->dropdownConditions(['value' => $p['condition'],
                'on_change'  => 'reloadTab("start=0&inherited=' . $p['inherited']
                                                         . '&childrens=' . $p['childrens'] . '&condition="+this.value)',
            ]);
            echo "</td></tr></table>";
        }

        $nb         = $this->getCollectionSize($p['inherited'], $p['condition'], $p['childrens']);
        $p['start'] = ($options["start"] ?? 0);

        if ($p['start'] >= $nb) {
            $p['start'] = 0;
        }

        $p['limit'] = $_SESSION['glpilist_limit'];
        $this->getCollectionPart($p);

        Html::printAjaxPager('', $p['start'], $nb);

        Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
        echo "\n<div class='spaced'>";

        if ($canedit && $nb) {
            $massiveactionparams = ['num_displayed' => min($p['limit'], $nb),
                'container'     => 'mass' . __CLASS__ . $rand,
                'extraparams'   => ['entity' => $this->entity,
                    'condition' => $p['condition'],
                    'rule_class_name'
                                                                 => $this->getRuleClassName(),
                ],
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='table table-striped table-hover card-table'>";
        $colspan = 4;

        if ($display_entities) {
            $colspan++;
        }
        if ($use_conditions) {
            $colspan++;
        }
        if ($display_criterias) {
            $colspan++;
        }
        if ($display_actions) {
            $colspan++;
        }

        $ruletype = $this->getRuleClassName();
        $can_sort = $canedit && $nb;
        if (count($this->RuleList->list)) {
            $can_sort = $this->RuleList->list[0]->can_sort && $canedit && $nb;
            Session::initNavigateListItems($ruletype, '', '');
        }

        if ($can_sort) {
            $colspan += 2;
        }

        echo "<tr><th colspan='$colspan'>" . $this->getTitle() . "</th></tr>";
        $header_row = "<tr>";
        $header_row .= "<th>";
        if ($canedit) {
            $header_row .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
        }
        $header_row .= "</th>";
        $header_row .= "<th>" . __('Name') . "</th>";
        $header_row .= "<th>" . __('Description') . "</th>";
        if ($use_conditions) {
            $header_row .= "<th>" . __('Use rule for') . "</th>";
        }
        if ($display_criterias) {
            $header_row .= "<th>" . RuleCriteria::getTypeName(2) . "</th>";
        }
        if ($display_actions) {
            $header_row .= "<th>" . RuleAction::getTypeName(2) . "</th>";
        }
        $header_row .= "<th>" . __('Active') . "</th>";
        if ($display_entities) {
            $header_row .= "<th>" . Entity::getTypeName(1) . "</th>";
        }
        if ($nb && $canedit && $can_sort) {
            $header_row .= "<th></th><th></th>";
        }
        $header_row .= "</tr>";
        echo $header_row;

        echo "<tbody class='sortable-rules'>";
        for ($i = $p['start'],$j = 0; isset($this->RuleList->list[$j]); $i++,$j++) {
            $this->RuleList->list[$j]->showMinimalForm(
                $target,
                $i == 0,
                $i == $nb - 1,
                $display_entities,
                $p['condition'],
                $display_criterias,
                $display_actions
            );
            Session::addToNavigateListItems($ruletype, $this->RuleList->list[$j]->fields['id']);
        }
        echo "</tbody>";

        if ($nb) {
            echo $header_row;
        }
        echo "</table>";

        if ($canedit && $nb) {
            $collection_classname = $this->getType();
            $js = <<<JAVASCRIPT
         $(function() {
            sortable('.sortable-rules', {
               handle: '.grip-rule',
               placeholder: '<tr><td colspan="7" class="sortable-placeholder">&nbsp;</td></tr>'
            })[0].addEventListener('sortupdate', function(e) {
               var sort_detail          = e.detail;
               var rule_id              = sort_detail.item.dataset.ruleId;
               var collection_classname = "{$collection_classname}";
               var new_index            = sort_detail.destination.index;
               var old_index            = sort_detail.origin.index;
               var ref_id               = sort_detail.destination.itemsBeforeUpdate[new_index].dataset.ruleId;
               var sort_action          = 'after';

               if (old_index > new_index) {
                  sort_action = 'before';
               }

               $.post(CFG_GLPI['root_doc']+'/ajax/rule.php', {
                  'action': 'move_rule',
                  'rule_id': rule_id,
                  'collection_classname': collection_classname,
                  'sort_action': sort_action,
                  'ref_id': ref_id,
               });

               displayAjaxMessageAfterRedirect();
            });
         });
JAVASCRIPT;
            echo Html::scriptBlock($js);

            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
        }

        echo "</div>";
        Html::closeForm();

        Html::printAjaxPager('', $p['start'], $nb);

        echo "<div class='spaced center'>";

        if ($plugin = isPluginItemType($this->getType())) {
            $url = Plugin::getWebDir($plugin['plugin']);
        } else {
            $url = $CFG_GLPI["root_doc"];
        }

        // if rules provides has default rules, then we're able to reset them
        $ruleclass = $this->getRuleClass();
        if ($ruleclass instanceof Rule && $ruleclass->hasDefaultRules()) {
            echo "<a class='btn btn-primary' id='reset_rules' href='" . $rule->getSearchURL() . "?reinit=true&subtype=" . $ruleclass->getType() . "' " .
            "onClick='if(confirm(\"" . __s('Rules will be erased and recreated from default. Are you sure?') . "\"))
            { return true } else { return false; };' " .
            "title='" . __s("Delete all rules and recreate them by default") . "'" .
            ">" . __('Reset rules') . "</a>&nbsp;";
        }
        echo "<a class='btn btn-primary' href='#' data-bs-toggle='modal' data-bs-target='#allruletest$rand'>" .
                  __('Test rules engine') . "</a>";
        Ajax::createIframeModalWindow(
            'allruletest' . $rand,
            $url . "/front/rulesengine.test.php?" .
                                          "sub_type=" . $ruleclass->getType() .
                                          "&condition=" . $p['condition'],
            ['title' => __('Test rules engine')]
        );
        echo "</div>";

        if ($this->can_replay_rules) {
            echo "<div class='spaced center'>";
            echo "<a class='btn btn-primary' href='" . $rule->getSearchURL() . "?replay_rule=replay_rule'>" .
               __s('Replay the dictionary rules') . "</a>";
            echo "</div>";
        }

        echo "<div class='spaced'>";
        $this->showAdditionalInformationsInForm($target);
        echo "</div>";
    }


    /**
     * Show the list of rules
     *
     * @param string $target
     *
     * @return void
     **/
    public function showAdditionalInformationsInForm($target) {}


    /**
     * Modify rule's ranking and automatically reorder all rules
     *
     * @param integer $ID        rule ID whose ranking must be modified
     * @param string  $action    up or down
     * @param integer $condition action on a specific condition
     *
     * @return boolean
     **/
    public function changeRuleOrder($ID, $action, $condition = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [
            'SELECT' => 'ranking',
            'FROM'   => 'glpi_rules',
            'WHERE'  => ['id' => $ID],
        ];

        $add_condition = [];
        if ($condition > 0) {
            $add_condition = ['condition' => ['&', (int) $condition]];
        }

        $iterator = $DB->request($criteria);
        if (count($iterator) == 1) {
            $result = $iterator->current();
            $current_rank = $result['ranking'];
            // Search rules to switch
            $criteria = [
                'SELECT' => ['id', 'ranking'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => [
                    'sub_type'  => $this->getRuleClassName(),
                ] + $add_condition,
                'LIMIT'  => 1,
            ];

            switch ($action) {
                case "up":
                    $criteria['WHERE']['ranking'] = ['<', $current_rank];
                    $criteria['ORDERBY'] = 'ranking DESC';
                    break;

                case "down":
                    $criteria['WHERE']['ranking'] = ['>', $current_rank];
                    $criteria['ORDERBY'] = 'ranking ASC';
                    break;

                default:
                    return false;
            }

            $iterator2 = $DB->request($criteria);
            if (count($iterator2) == 1) {
                $result2 = $iterator2->current();
                $other_ID = $result2['id'];
                $new_rank = $result2['ranking'];
                echo $current_rank . ' ' . $ID . '<br>';
                echo $new_rank . ' ' . $other_ID . '<br>';

                $rule = $this->getRuleClass();
                $result = false;
                $criteria = [
                    'SELECT' => ['id', 'ranking'],
                    'FROM'   => 'glpi_rules',
                    'WHERE'  => ['sub_type' => $this->getRuleClassName()],
                ];
                $diff = $new_rank - $current_rank;
                switch ($action) {
                    case "up":
                        $criteria['WHERE'] = array_merge(
                            $criteria['WHERE'],
                            [
                                ['ranking' => ['>', $new_rank]],
                                ['ranking' => ['<=', $current_rank]],
                            ]
                        );
                        $diff += 1;
                        break;

                    case "down":
                        $criteria['WHERE'] = array_merge(
                            $criteria['WHERE'],
                            [
                                ['ranking' => ['>=', $current_rank]],
                                ['ranking' => ['<', $new_rank]],
                            ]
                        );
                        $diff -= 1;
                        break;

                    default:
                        return false;
                }

                if ($diff != 0) {
                    // Move several rules
                    $iterator3 = $DB->request($criteria);
                    foreach ($iterator3 as $data) {
                        $data['ranking'] += $diff;
                        $result = $rule->update($data);
                    }
                } else {
                    // Only move one
                    $result = $rule->update([
                        'id'      => $ID,
                        'ranking' => $new_rank,
                    ]);
                }

                // Update reference
                if ($result) {
                    $result = $rule->update([
                        'id'      => $other_ID,
                        'ranking' => $current_rank,
                    ]);
                }
                return $result;
            }
        }
        return false;
    }


    /**
     * Update Rule Order when deleting a rule
     *
     * @param integer $ranking rank of the deleted rule
     *
     * @return boolean
     **/
    public function deleteRuleOrder($ranking)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->update(
            'glpi_rules',
            [
                'ranking' => new \QueryExpression($DB->quoteName('ranking') . ' - 1'),
            ],
            [
                'sub_type'  => $this->getRuleClassName(),
                'ranking'   => ['>', $ranking],
            ]
        );
        return $result;
    }


    /**
     * Move a rule in an ordered collection
     *
     * @param integer $ID        ID of the rule to move
     * @param integer $ref_ID    ID of the rule position  (0 means all, so before all or after all)
     * @param string  $type      Movement type, one of self::MOVE_AFTER or self::MOVE_BEFORE
     *
     * @return boolean
     **/
    public function moveRule($ID, $ref_ID, $type = self::MOVE_AFTER)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ruleDescription = new Rule();

        // Get actual ranking of Rule to move
        $ruleDescription->getFromDB($ID);
        $old_rank = $ruleDescription->fields["ranking"];

        // Compute new ranking
        if ($ref_ID) { // Move after/before an existing rule
            $ruleDescription->getFromDB($ref_ID);
            $rank = $ruleDescription->fields["ranking"];
        } elseif ($type == self::MOVE_AFTER) {
            // Move after all
            $result = $DB->request([
                'SELECT' => ['MAX' => 'ranking AS maxi'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => ['sub_type' => $this->getRuleClassName()],
            ])->current();
            $rank   = $result['maxi'];
        } else {
            // Move before all
            $rank = 1;
        }

        $rule   = $this->getRuleClass();

        $result = false;

        // Move others rules in the collection
        if ($old_rank < $rank) {
            if ($type == self::MOVE_BEFORE) {
                $rank--;
            }

            // Move back all rules between old and new rank
            $iterator = $DB->request([
                'SELECT' => ['id', 'ranking'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => [
                    'sub_type'  => $this->getRuleClassName(),
                    ['ranking'  => ['>', $old_rank]],
                    ['ranking'  => ['<=', $rank]],
                ],
            ]);
            foreach ($iterator as $data) {
                $data['ranking']--;
                $result = $rule->update($data);
            }
        } elseif ($old_rank > $rank) {
            if ($type == self::MOVE_AFTER) {
                $rank++;
            }

            // Move forward all rule  between old and new rank
            $iterator = $DB->request([
                'SELECT' => ['id', 'ranking'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => [
                    'sub_type'  => $this->getRuleClassName(),
                    ['ranking'  => ['>=', $rank]],
                    ['ranking'  => ['<', $old_rank]],
                ],
            ]);
            foreach ($iterator as $data) {
                $data['ranking']++;
                $result = $rule->update($data);
            }
        } else { // $old_rank == $rank : nothing to do
            $result = false;
        }

        // Move the rule
        if ($result && ($old_rank != $rank)) {
            $result = $rule->update([
                'id'      => $ID,
                'ranking' => $rank,
            ]);
        }
        return ($result ? true : false);
    }


    /**
     * Print a title for backup rules
     *
     * @since 0.85
     *
     * @return void
     **/
    public static function titleBackup()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $base_url = "{$CFG_GLPI['root_doc']}/front/rule.backup.php";
        $buttons  = [
            "{$base_url}?action=import" =>
            "<i class='fas fa-upload'></i><span>" . _x('button', 'Import') . "</span>",
            "{$base_url}?action=export" =>
            "<i class='fas fa-download'></i><span>" . _x('button', 'Export') . "</span>",
        ];

        echo "<div class='center mb-3'>";
        foreach ($buttons as $key => $val) {
            echo "<a class='btn btn-primary me-2' href='" . $key . "'>" . $val . "</a></td>";
        }
        echo "</div>";
    }


    /**
     * Export rules in a xml format
     *
     * @param array $items array the input data to transform to xml
     *
     * @since 0.85
     *
     * @return void send attachment to browser
     **/
    public static function exportRulesToXML($items = [])
    {

        if (!count($items)) {
            return false;
        }

        $rulecollection = new self();
        $rulecritera    = new RuleCriteria();
        $ruleaction     = new RuleAction();

        //create xml
        $xmlE           = new SimpleXMLElement('<rules/>');

        //parse all rules
        foreach ($items as $key => $ID) {
            $rulecollection->getFromDB($ID);
            if (!class_exists($rulecollection->fields['sub_type'])) {
                continue;
            }
            $rule = new $rulecollection->fields['sub_type']();
            unset($rulecollection->fields['id']);
            unset($rulecollection->fields['date_mod']);

            $name = Dropdown::getDropdownName(
                "glpi_entities",
                $rulecollection->fields['entities_id']
            );
            $rulecollection->fields['entities_id'] = $name;

            //add root node
            $xmlERule = $xmlE->addChild('rule');

            //convert rule direct indexes in XML
            foreach ($rulecollection->fields as $key => $val) {
                $xmlERule->$key = $val;
            }

            //find criterias
            $criterias = $rulecritera->find(['rules_id' => $ID]);
            foreach ($criterias as &$criteria) {
                unset($criteria['id']);
                unset($criteria['rules_id']);

                $available_criteria = $rule->getCriterias();
                $crit               = $criteria['criteria'];
                if (self::isCriteraADropdown($available_criteria, $criteria['condition'], $crit)) {
                    $criteria['pattern'] = Dropdown::getDropdownName(
                        $available_criteria[$crit]['table'],
                        $criteria['pattern'],
                        false,
                        true,
                        false,
                        ''
                    );
                }

                //convert criterias in XML
                $xmlECritiera = $xmlERule->addChild('rulecriteria');
                foreach ($criteria as $key => $val) {
                    $xmlECritiera->$key = $val;
                }
            }

            //find actions
            $actions = $ruleaction->find(['rules_id' => $ID]);
            foreach ($actions as &$action) {
                unset($action['id']);
                unset($action['rules_id']);

                //process FK (just in case of "assign" action)
                if (
                    ($action['action_type'] == "assign")
                    && (strpos($action['field'], '_id') !== false)
                    && !(($action['field'] == "entities_id")
                     && ($action['value'] == 0))
                ) {
                    $field = $action['field'];
                    if ($action['field'][0] == "_") {
                        $field = substr($action['field'], 1);
                    }
                    $table = getTableNameForForeignKeyField($field);

                    $action['value'] = Dropdown::getDropdownName(
                        $table,
                        $action['value'],
                        false,
                        true,
                        false,
                        ''
                    );
                }

                //convert actions in XML
                $xmlEAction = $xmlERule->addChild('ruleaction');
                foreach ($action as $key => $val) {
                    $xmlEAction->$key = $val;
                }
            }
        }

        //convert SimpleXMLElement to xml string
        $xml = $xmlE->asXML();

        //send attachment to browser
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename="rules.xml"');
        echo $xml;

        //exit;
    }


    /**
     * Print a form to select a xml file for import rules
     *
     * @since 0.85
     *
     * @return void
     **/
    public static function displayImportRulesForm()
    {

        echo "<form name='form' method='post' action='rule.backup.php' " .
             "enctype='multipart/form-data' >";
        echo "<div class='center'>";

        echo "<h2>" . __("Import rules from a XML file") . "</h2>";
        echo "<input type='file' name='xml_file'>&nbsp;";
        echo "<input type='hidden' name='action' value='preview_import'>";
        echo "<input type='submit' name='import' value=\"" . _sx('button', 'Import') .
             "\" class='btn btn-primary'>";

        // Close for Form
        echo "</div>";
        Html::closeForm();
    }


    /**
     *
     * Check if a criterion is a dropdown or not
     *
     * @since 0.85
     *
     * @param array   $available_criteria available criteria for this rule
     * @param integer $condition          the rulecriteria condition
     * @param string  $criterion          the criterion
     *
     * @return boolean true if a criterion is a dropdown, false otherwise
     **/
    public static function isCriteraADropdown($available_criteria, $condition, $criterion)
    {

        if (isset($available_criteria[$criterion]['type'])) {
            $type = $available_criteria[$criterion]['type'];
        } else {
            $type = false;
        }
        return (in_array(
            $condition,
            [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT, Rule::PATTERN_UNDER]
        )
              && ($type == 'dropdown'));
    }


    /**
     * Print a form to inform user when conflicts appear during the import of rules from a xml file
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public static function previewImportRules()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!isset($_FILES["xml_file"]) || ($_FILES["xml_file"]["size"] == 0)) {
            return false;
        }

        if ($_FILES["xml_file"]["error"] != UPLOAD_ERR_OK) {
            Session::addMessageAfterRedirect(__("No file was uploaded"));
            return false;
        }
        //get xml file content
        $xml           = file_get_contents($_FILES["xml_file"]["tmp_name"]);
        //convert a xml string into a SimpleXml object
        if (!$xmlE = simplexml_load_string($xml)) {
            Session::addMessageAfterRedirect(__('Unauthorized file type'), false, ERROR);
        }
        //convert SimpleXml object into an array and store it in session
        $rules         = json_decode(json_encode((array) $xmlE), true);
        //check rules (check if entities, criterias and actions is always good in this glpi)
        $entity        = new Entity();
        $rules_refused = [];

        //In case there's only one rule to import, recreate an array with key => value
        if (isset($rules['rule']['entities_id'])) {
            $rules['rule'] = [0 => $rules['rule']];
        }

        foreach ($rules['rule'] as $k_rule => &$rule) {
            $tmprule = new $rule['sub_type']();
            //check entities
            if ($tmprule->isEntityAssign()) {
                $rule['entities_id'] = $DB->escape(Html::entity_decode_deep($rule['entities_id']));
                $entities_found = $entity->find(['completename' => $rule['entities_id']]);
                if (empty($entities_found)) {
                    $rules_refused[$k_rule]['entity'] = true;
                }
            }

            //process direct attributes
            foreach ($rule as &$val) {
                if (
                    is_array($val)
                    && empty($val)
                ) {
                    $val = "";
                }
            }

            //check criterias
            if (isset($rule['rulecriteria'])) {
                //check and correct criterias array format
                if (isset($rule['rulecriteria']['criteria'])) {
                    $rule['rulecriteria'] = [$rule['rulecriteria']];
                }

                foreach ($rule['rulecriteria'] as $k_crit => $criteria) {
                    // Fix patterns decoded as empty arrays to prevent empty IN clauses in SQL generation.
                    if (is_array($criteria['pattern']) && empty($criteria['pattern'])) {
                        $criteria['pattern'] = '';
                    }

                    $available_criteria = $tmprule->getCriterias();
                    $crit               = $criteria['criteria'];
                    //check FK (just in case of "is", "is_not" and "under" criteria)
                    if (
                        self::isCriteraADropdown(
                            $available_criteria,
                            $criteria['condition'],
                            $crit
                        )
                    ) {
                        //escape pattern
                        $criteria['pattern'] = $DB->escape(Html::entity_decode_deep($criteria['pattern']));
                        $itemtype = getItemTypeForTable($available_criteria[$crit]['table']);
                        $item     = new $itemtype();
                        if ($item instanceof CommonTreeDropdown) {
                            $found = $item->find(['completename' => $criteria['pattern']]);
                        } else {
                            $found = $item->find(['name' => $criteria['pattern']]);
                        }
                        if (empty($found)) {
                            $rules_refused[$k_rule]['criterias'][] = $k_crit;
                        } else {
                            $tmp = array_pop($found);
                            $rules['rule'][$k_rule]['rulecriteria'][$k_crit]['pattern'] = $tmp['id'];
                        }
                    }
                }
            }

            //check actions
            if (isset($rule['ruleaction'])) {
                //check and correct actions array format
                if (isset($rule['ruleaction']['field'])) {
                    $rule['ruleaction'] = [$rule['ruleaction']];
                }

                foreach ($rule['ruleaction'] as $k_action => $action) {
                    // Fix values decoded as empty arrays to prevent empty IN clauses in SQL generation.
                    if (is_array($action['value']) && empty($action['value'])) {
                        $action['value'] = '';
                    }
                    $available_actions = $tmprule->getActions();
                    $act               = $action['field'];

                    if (
                        ($action['action_type'] == "assign")
                        && (isset($available_actions[$act]['type'])
                        && ($available_actions[$act]['type'] == 'dropdown'))
                    ) {
                        //pass root entity and empty array (N/A value)
                        if (
                            (in_array($action['field'], ['entities_id', 'new_entities_id'], true))
                            && (($action['value'] == 0)
                            || ($action['value'] == ''))
                        ) {
                            continue;
                        }

                        //escape value
                        $action['value'] = $DB->escape(Html::entity_decode_deep($action['value']));
                        $itemtype = getItemTypeForTable($available_actions[$act]['table']);
                        $item     = new $itemtype();
                        if ($item instanceof CommonTreeDropdown) {
                            $found = $item->find(['completename' => $action['value']]);
                        } else {
                            $found = $item->find(['name' => $action['value']]);
                        }
                        if (empty($found)) {
                            $rules_refused[$k_rule]['actions'][] = $k_action;
                        } else {
                            $tmp = array_pop($found);
                            $rules['rule'][$k_rule]['ruleaction'][$k_action]['value'] = $tmp['id'];
                        }
                    }
                }
            }
        }

        //save rules for ongoing processing
        $_SESSION['glpi_import_rules']         = $rules;
        $_SESSION['glpi_import_rules_refused'] = $rules_refused;

        //if no conflict detected, we can directly process the import
        if (!count($rules_refused)) {
            Html::redirect("rule.backup.php?action=process_import");
        }

        //print report
        echo "<form name='form' method='post' action='rule.backup.php' >";
        echo "<div class='spaced' id='tabsbody'>";
        echo "<table class='tab_cadre'>";
        echo "<input type='hidden' name='action' value='process_import'>";
        echo "<tr><th colspan='3'>" . __('Rules refused') . "</th></tr>";
        echo "<tr>";
        echo "<th>" . _n('Type', 'Type', 1) . "</th>";
        echo "<th>" . __('Name') . "</th>";
        echo "<th>" . __('Reason of rejection') . "</th>";
        echo "</tr>";

        $odd = true;
        foreach ($rules_refused as $k_rule => $refused) {
            $odd = !$odd;
            if ($odd) {
                $class = " class='tab_bg_1' ";
            } else {
                $class = " class='tab_bg_2' ";
            }

            $sub_type = $rules['rule'][$k_rule]['sub_type'];
            $item     = new $sub_type();

            echo "<tr $class>";
            echo "<td>" . $item->getTitle() . "</td>";
            echo "<td>" . $rules['rule'][$k_rule]['name'] . "</td>";
            echo "<td>";

            echo "<table class='tab_cadre' style='width:100%'>";
            //show entity select
            if (!isset($refused['criterias']) && !isset($refused['actions'])) {
                if (isset($refused['entity'])) {
                    echo "<tr class='tab_bg_1_2'>";
                    echo "<td>";
                    printf(
                        __('%1$s (%2$s)'),
                        __('Entity not found'),
                        $rules['rule'][$k_rule]['entities_id']
                    );
                    echo "</td>";
                    echo "<td>";
                    echo __('Select the desired entity') . "&nbsp;";
                    Dropdown::show(
                        'Entity',
                        ['comments' => false,
                            'name'     => "new_entities[" .
                        $rules['rule'][$k_rule]['uuid'] . "]",
                        ]
                    );
                    echo "</td>";
                    echo "</tr>";
                }
            }

            //show criterias refused for this rule
            if (isset($refused['criterias'])) {
                echo "<tr class='tab_bg_1_2'>";
                echo "<td>" . __('Criteria refused') . "</td>";
                echo "<td>";

                echo "<table class='tab_cadre' style='width:100%'>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='center b'>" . _n('Criterion', 'Criteria', 1) . "</th>\n";
                echo "<th class='center b'>" . __('Condition') . "</th>\n";
                echo "<th class='center b'>" . __('Reason') . "</th>\n";
                echo "</tr>\n";
                foreach ($refused['criterias'] as $k_criteria) {
                    $criteria = $rules['rule'][$k_rule]['rulecriteria'][$k_criteria];

                    //fix empty empty array values
                    if (empty($criteria['value'])) {
                        $criteria['value'] = null;
                    }
                    echo "<tr class='tab_bg_1'>";
                    echo "<td>" . $item->getCriteriaName($criteria["criteria"]) . "</td>";
                    echo "<td>" . RuleCriteria::getConditionByID(
                        $criteria["condition"],
                        get_class($item),
                        $criteria["criteria"]
                    ) . "</td>";
                    echo "<td>" . $criteria["pattern"] . "</td>";
                    echo "</tr>";
                }
                echo "</table>\n";
                echo "</td>";
                echo "</tr>";
            }

            //show actions refused for this rule
            if (isset($refused['actions'])) {
                echo "<tr class='tab_bg_1_2'>";
                echo "<td>" . __('Actions refused') . "</td>";
                echo "<td>";

                echo "<table class='tab_cadre' style='width:100%'>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='center b'>" . _n('Field', 'Fields', Session::getPluralNumber()) . "</th>";
                echo "<th class='center b'>" . __('Action type') . "</th>";
                echo "<th class='center b'>" . __('Value') . "</th>";
                echo "</tr>\n";
                foreach ($refused['actions'] as $k_action) {
                    $action = $rules['rule'][$k_rule]['ruleaction'][$k_action];
                    //fix empty empty array values
                    if (empty($action['value'])) {
                        $action['value'] = null;
                    }
                    echo "<tr class='tab_bg_1'>";
                    echo "<td>" . $item->getActionName($action["field"]) . "</td>";
                    echo "<td>" . RuleAction::getActionByID($action["action_type"]) . "</td>";
                    echo "<td>" . $action["value"] . "</td>";
                    echo "</tr>";
                }
                echo "</table>\n";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>\n";
            echo "</td></tr>";
        }

        //display buttons
        $class = ($odd ? " class='tab_bg_1' " : " class='tab_bg_2' ");
        echo "<tr $class><td colspan='3' class='center'>";
        echo "<input type='submit' name='import' value=\"" . _sx('button', 'Post') .
             "\" class='btn btn-primary'>";
        echo "</td></tr>";

        // Close for Form
        echo "</table></div>";
        Html::closeForm();

        return true;
    }


    /**
     * import rules in glpi after user validation
     *
     * @since 0.85
     *
     * @return boolean
     **/
    public static function processImportRules()
    {
        /** @var \DBmysql $DB */
        global $DB;
        $ruleCriteria = new RuleCriteria();
        $ruleAction   = new RuleAction();
        $entity       = new Entity();

        //get session vars
        $rules         = $_SESSION['glpi_import_rules'];
        $rules_refused = $_SESSION['glpi_import_rules_refused'];
        $rr_keys       = array_keys($rules_refused);
        unset($_SESSION['glpi_import_rules']);
        unset($_SESSION['glpi_import_rules_refused']);

        // unset all refused rules
        foreach ($rules['rule'] as $k_rule => &$rule) {
            if (in_array($k_rule, $rr_keys)) {
                //Do not process rule with actions or criterias refused
                if (
                    isset($rules_refused[$k_rule]['criterias'])
                    || isset($rules_refused[$k_rule]['actions'])
                ) {
                    unset($rules['rule'][$k_rule]);
                } else {// accept rule with only entity not found (change entity)
                    $rule['entities_id'] = $_REQUEST['new_entities'][$rule['uuid']];
                }
            }
        }

        //import all right rules
        while (!empty($rules['rule'])) {
            $current_rule             = array_shift($rules['rule']);
            $add_criteria_and_actions = false;
            $params                   = [];
            $itemtype                 = $current_rule['sub_type'];
            $item                     = new $itemtype();

            //Find a rule by it's uuid
            $found    = $item->find(['uuid' => $current_rule['uuid']]);
            $params   = Toolbox::addslashes_deep($current_rule);
            unset($params['rulecriteria']);
            unset($params['ruleaction']);

            if (!$item->isEntityAssign()) {
                $params['entities_id'] = 0;
            } else {
                $entities_found = $entity->find(['completename' => $DB->escape($current_rule['entities_id'])]);
                if (!empty($entities_found)) {
                    $entity_found          = array_shift($entities_found);
                    $params['entities_id'] = $entity_found['id'];
                } else {
                    $params['entities_id'] = 0;
                }
            }
            foreach (['is_recursive', 'is_active'] as $field) {
                //Should not be necessary but without it there's an sql error...
                if (!isset($params[$field]) || ($params[$field] == '')) {
                    $params[$field] = 0;
                }
            }

            //if uuid not exist, create rule
            if (empty($found)) {
                //Manage entity
                $params['_add'] = true;
                $rules_id       = $item->add($params);
                if ($rules_id) {
                    Event::log(
                        $rules_id,
                        "rules",
                        4,
                        "setup",
                        sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $rules_id)
                    );
                    $add_criteria_and_actions = true;
                }
            } else { //if uuid exists, then update the rule
                $tmp               = array_shift($found);
                $params['id']      = $tmp['id'];
                $params['_update'] = true;
                $rules_id          = $tmp['id'];
                if ($item->update($params)) {
                    Event::log(
                        $rules_id,
                        "rules",
                        4,
                        "setup",
                        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
                    );

                    //remove all dependent criterias and action
                    $ruleCriteria->deleteByCriteria(["rules_id" => $rules_id]);
                    $ruleAction->deleteByCriteria(["rules_id" => $rules_id]);
                    $add_criteria_and_actions = true;
                }
            }

            if ($add_criteria_and_actions) {
                //Add criteria
                if (isset($current_rule['rulecriteria'])) {
                    foreach ($current_rule['rulecriteria'] as $criteria) {
                        $criteria['rules_id'] = $rules_id;
                        //fix array in value key
                        //(simplexml bug, empty xml node are converted in empty array instead of null)
                        if (is_array($criteria['pattern'])) {
                            $criteria['pattern'] = null;
                        }
                        $criteria = Toolbox::addslashes_deep($criteria);
                        $ruleCriteria->add($criteria);
                    }
                }

                //Add actions
                if (isset($current_rule['ruleaction'])) {
                    foreach ($current_rule['ruleaction'] as $action) {
                        $action['rules_id'] = $rules_id;
                        //fix array in value key
                        //(simplexml bug, empty xml node are converted in empty array instead of null)
                        if (is_array($action['value'])) {
                            $action['value'] = null;
                        }
                        $action = Toolbox::addslashes_deep($action);
                        $ruleAction->add($action);
                    }
                }
            }
        }

        Session::addMessageAfterRedirect(__('Successful importation'));

        return true;
    }


    /**
     * Process all the rules collection
     *
     * @param array $input    Input data used to check criterias (need to be clean slashes)
     * @param array $output   Initial ouput array used to be manipulate by actions (need to be clean slashes)
     * @param array $params   Parameters for all internal functions (need to be clean slashes)
     * @param array $options  Options :
     *                         - condition : specific condition to limit rule list
     *                         - only_criteria : only react on specific criteria
     *
     * @return array the output array updated by actions (addslashes datas)
     **/
    public function processAllRules($input = [], $output = [], $params = [], $options = [])
    {

        $p['condition']     = 0;
        $p['only_criteria'] = null;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        // Get Collection datas
        $this->getCollectionDatas(1, 1, $p['condition']);
        $input                      = $this->prepareInputDataForProcessWithPlugins($input, $params);
        $output["_no_rule_matches"] = true;

        //Store rule type being processed (for plugins)
        $params['rule_itemtype']    = $this->getRuleClassName();

        if (count($this->RuleList->list)) {
            /** @var Rule $rule */
            foreach ($this->RuleList->list as $rule) {
                if ($p['condition'] && !($rule->fields['condition'] & $p['condition'])) {
                    // Rule is loaded in the cache but is not relevant for the current condition
                    continue;
                }

                //If the rule is active, process it

                if ($rule->fields["is_active"]) {
                    $output["_rule_process"] = false;
                    $rule->process($input, $output, $params, $p);

                    if (
                        (isset($output['_stop_rules_processing']) && (int) $output['_stop_rules_processing'] === 1)
                        || ($output["_rule_process"] && $this->stop_on_first_match)
                    ) {
                        unset($output["_stop_rules_processing"], $output["_rule_process"]);
                        $output["_ruleid"] = $rule->fields["id"];
                        return Toolbox::addslashes_deep($output);
                    }
                }

                if ($this->use_output_rule_process_as_next_input) {
                    $output = $this->prepareInputDataForProcessWithPlugins($output, $params);
                    $input  = $output;
                }
            }
        }

        return Toolbox::addslashes_deep($output);
    }


    /**
     * Show form displaying results for rule collection preview
     *
     * @param string  $target    where to go
     * @param array   $values    array of data
     * @param integer $condition condition to limit rules (default 0)
     *
     * @return array
     **/
    public function showRulesEnginePreviewCriteriasForm($target, array $values, $condition = 0)
    {
        $input = $this->prepareInputDataForTestProcess($condition);

        if (count($input)) {
            $rule      = $this->getRuleClass();
            $criterias = $rule->getAllCriteria();
            echo "<form name='testrule_form' id='testrulesengine_form' method='post' action='$target'>";
            echo "\n<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='2'>" . _n('Criterion', 'Criteria', Session::getPluralNumber()) . "</th></tr>\n";

            //Brower all criterias
            foreach ($input as $criteria) {
                echo "<tr class='tab_bg_1'>";

                if (isset($criterias[$criteria])) {
                    $criteria_constants = $criterias[$criteria];
                    echo "<td>" . $criteria_constants["name"] . "</td>";
                } else {
                    echo "<td>" . $criteria . "</td>";
                }

                echo "<td>";
                $rule->displayCriteriaSelectPattern(
                    $criteria,
                    $criteria,
                    Rule::PATTERN_IS,
                    $values[$criteria] ?? ''
                );
                echo "</td></tr>\n";
            }

            // Add all used criteria on rule as `Rule::showSpecificCriteriasForPreview()`
            // adapt its output depending on used criteria
            $rule->criterias = [];
            foreach ($input as $criteria) {
                $rule->criterias[] = (object) [
                    'fields' => ['criteria' => $criteria],
                ];
            }
            $rule->showSpecificCriteriasForPreview($_POST);

            echo "<tr><td class='tab_bg_2 center' colspan='2'>";
            echo "<input type='submit' name='test_all_rules' value='" . _sx('button', 'Test') . "'
                class='btn btn-primary'>";
            echo "<input type='hidden' name='sub_type' value='" . $this->getRuleClassName() . "'>";
            echo "<input type='hidden' name='condition' value='$condition'>";
            echo "</td></tr>\n";
            echo "</table></div>";
            Html::closeForm();
        } else {
            echo '<br><div class="center b">' . __('No element to be tested') . '</div>';
        }

        return $input;
    }


    /**
     * Test all the rules collection
     *
     * @param array   $input     array the input data used to check criterias
     * @param array   $output    array the initial output array used to be manipulated by actions
     * @param array   $params    array parameters for all internal functions
     * @param integer $condition condition to limit rules (DEFAULT 0)
     *
     * @return array the output array updated by actions
     **/
    public function testAllRules($input = [], $output = [], $params = [], $condition = 0)
    {

        // Get Collection data
        $this->getCollectionDatas(1, 1, $condition);
        $input = $this->prepareInputDataForProcess($input, $params);

        $output["_no_rule_matches"] = true;

        if (count($this->RuleList->list)) {
            foreach ($this->RuleList->list as $rule) {
                //If the rule is active, process it
                if ($rule->fields["is_active"]) {
                    $output["_rule_process"]                     = false;
                    $output["result"][$rule->fields["id"]]["id"] = $rule->fields["id"];
                    $rule->process($input, $output, $params);

                    if ((isset($output['_stop_rules_processing']) && (int) $output['_stop_rules_processing'] === 1) || ($output["_rule_process"] && $this->stop_on_first_match)) {
                        unset($output["_stop_rules_processing"], $output["_rule_process"]);
                        $output["result"][$rule->fields["id"]]["result"] = 1;
                        $output["_ruleid"]                               = $rule->fields["id"];
                        return $output;
                    } elseif ($output["_rule_process"]) {
                        $output["result"][$rule->fields["id"]]["result"] = 1;
                    } else {
                        $output["result"][$rule->fields["id"]]["result"] = 0;
                    }
                } else {
                    //Rule is inactive
                    $output["result"][$rule->fields["id"]]["result"] = 2;
                }

                if ($this->use_output_rule_process_as_next_input) {
                    $input = $output;
                }
            }
        }

        return $output;
    }


    /**
     * Prepare input data for the rules collection
     *
     * @param array $input  the input data used to check criteria
     * @param array $params parameters
     *
     * @return array the updated input data
     **/
    public function prepareInputDataForProcess($input, $params)
    {
        return $input;
    }


    /**
     * Prepare input datas for the rules collection, also using plugins values
     *
     * @since 0.84
     *
     * @param array $input  the input data used to check criterias
     * @param array $params parameters
     *
     * @return array the updated input datas
     **/
    public function prepareInputDataForProcessWithPlugins($input, $params)
    {
        /** @var array $PLUGIN_HOOKS */
        global $PLUGIN_HOOKS;

        $input = $this->prepareInputDataForProcess($input, $params);
        if (isset($PLUGIN_HOOKS['use_rules'])) {
            foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array($this->getRuleClassName(), $val)) {
                    $results = Plugin::doOneHook(
                        $plugin,
                        'ruleCollectionPrepareInputDataForProcess',
                        ['rule_itemtype' => $this->getRuleClassName(),
                            'values'        => ['input' => $input,
                                'params' => $params,
                            ],
                        ]
                    );
                    if (is_array($results)) {
                        foreach ($results as $id => $result) {
                            $input[$id] = $result;
                        }
                    }
                }
            }
        }
        return $input;
    }


    /**
     * Prepare input data for the rules collection
     *
     * @param integer $condition condition to limit rules (DEFAULT 0)
     *
     * @return array the updated input data
     **/
    public function prepareInputDataForTestProcess($condition = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $limit = [];
        if ($condition > 0) {
            $limit = ['glpi_rules.condition' => ['&', (int) $condition]];
        }
        $input = [];

        $iterator = $DB->request([
            'SELECT'          => 'glpi_rulecriterias.criteria',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_rulecriterias',
            'INNER JOIN'      => [
                'glpi_rules'   => [
                    'ON' => [
                        'glpi_rulecriterias' => 'rules_id',
                        'glpi_rules'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_rules.is_active'  => 1,
                'glpi_rules.sub_type'   => $this->getRuleClassName(),
            ] + $limit,
        ]);

        foreach ($iterator as $data) {
            $input[] = $data["criteria"];
        }
        return $input;
    }


    /**
     * Show form displaying results for rule engine preview
     *
     * @param string  $target    where to go
     * @param array   $input     array of data
     * @param integer $condition condition to limit rules (DEFAULT 0)
     *
     * @return void
     **/
    public function showRulesEnginePreviewResultsForm($target, array $input, $condition = 0)
    {

        $output = [];

        if ($this->use_output_rule_process_as_next_input) {
            $output = $input;
        }

        $output = $this->testAllRules($input, $output, $input, $condition);

        $rule   = $this->getRuleClass();

        echo "<div class='center'>";

        if (isset($output["result"])) {
            echo "<table class='tab_cadrehov'>";
            echo "<tr><th colspan='2'>" . __('Result details') . "</th></tr>\n";

            foreach ($output["result"] as $ID => $rule_result) {
                echo "<tr class='tab_bg_1'>";
                $rule->getFromDB($ID);
                echo "<td>" . $rule->fields["name"] . "</td>";
                echo "<td class='b'>";

                switch ($rule_result["result"]) {
                    case 0:
                    case 1:
                        echo Dropdown::getYesNo($rule_result["result"]);
                        break;

                    case 2:
                        echo __('Inactive');
                        break;
                }

                echo "</td></tr>\n";
            }

            echo "</table>";
        }

        $output        = $this->cleanTestOutputCriterias($output);
        unset($output["result"]);
        $global_result = (count($output) ? 1 : 0);

        echo "<br><table class='tab_cadrehov'>";
        $this->showTestResults($rule, $output, $global_result);
        echo "</table></div>";
    }


    /**
     * Unset criteria from the rule's ouput results (begins by _)
     *
     * @param array $output array clean output array to clean
     *
     * @return array cleaned array
     **/
    public function cleanTestOutputCriterias(array $output)
    {

        $rule   = $this->getRuleClass();
        $actions = $rule->getAllActions();

        //If output array contains keys begining with _ : drop it
        foreach ($output as $criteria => $value) {
            if ($criteria[0] == '_' && !isset($actions[$criteria])) {
                unset($output[$criteria]);
            }
        }
        return $output;
    }


    /**
     * Show test results for a rule
     *
     * @param Rule    $rule          rule object
     * @param array   $output        output data array
     * @param boolean $global_result global result
     *
     * @return void
     **/
    public function showTestResults($rule, array $output, $global_result)
    {

        $actions = $rule->getAllActions();
        echo "<table class='tab_cadrehov'>";
        echo "<tr><th colspan='2'>" . __('Rule results') . "</th></tr>\n";
        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . _n('Validation', 'Validations', 1) . "</td>";
        echo "<td><span class='b'>" . Dropdown::getYesNo($global_result) . "</span></td>";

        $output = $this->preProcessPreviewResults($output);

        foreach ($output as $criteria => $value) {
            if (isset($actions[$criteria])) {
                echo "<tr class='tab_bg_2'>";
                echo "<td>" . $actions[$criteria]["name"] . "</td>";
                $action_type = ($actions[$criteria]['action_type'] ?? '');
                echo "<td>" . $rule->getActionValue($criteria, $action_type, $value);
                echo "</td></tr>\n";
            }
        }
        echo "</tr></table>\n";
    }


    /**
     * @param array $output
     *
     * @return array
     **/
    public function preProcessPreviewResults($output)
    {
        /** @var array $PLUGIN_HOOKS */
        global $PLUGIN_HOOKS;

        if (isset($PLUGIN_HOOKS['use_rules'])) {
            $params['rule_itemtype'] = $this->getType();
            foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array($this->getType(), $val)) {
                    $results = Plugin::doOneHook(
                        $plugin,
                        "preProcessRuleCollectionPreviewResults",
                        ['output' => $output,
                            'params' => $params,
                        ]
                    );
                    if (is_array($results)) {
                        foreach ($results as $id => $result) {
                            $output[$id] = $result;
                        }
                    }
                }
            }
        }
        return $this->cleanTestOutputCriterias($output);
    }


    /**
     * Print a title if needed which will be displayed above list of rules
     *
     * @return void
     **/
    public function title() {}


    /**
     * Get rulecollection classname by giving his itemtype
     *
     * @param string  $itemtype               itemtype
     * @param boolean $check_dictionnary_type check if the itemtype is a dictionary or not
     *                                  (false by default)
     *
     * @return RuleCollection|null
     */
    public static function getClassByType($itemtype, $check_dictionnary_type = false)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($plug = isPluginItemType($itemtype)) {
            $typeclass = 'Plugin' . $plug['plugin'] . $plug['class'] . 'Collection';
        } else {
            if (in_array($itemtype, $CFG_GLPI["dictionnary_types"])) {
                $typeclass = 'RuleDictionnary' . $itemtype . "Collection";
            } else {
                $typeclass = $itemtype . "Collection";
            }
        }

        if (
            ($check_dictionnary_type && in_array($itemtype, $CFG_GLPI["dictionnary_types"]))
            || !$check_dictionnary_type
        ) {
            if ($item = getItemForItemtype($typeclass)) {
                return $item;
            }
        }
        return null;
    }


    public function showInheritedTab()
    {
        return false;
    }


    public function showChildrensTab()
    {
        return false;
    }


    /**
     * Get all the fields needed to perform the rule
     *
     * @return array
     **/
    public function getFieldsToLookFor()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $params = [];

        $iterator = $DB->request([
            'SELECT'          => 'glpi_rulecriterias.criteria',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_rulecriterias',
            'INNER JOIN'      => [
                'glpi_rules'   => [
                    'ON' => [
                        'glpi_rulecriterias' => 'rules_id',
                        'glpi_rules'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => [
                'glpi_rules.is_active'  => 1,
                'glpi_rules.sub_type'   => $this->getRuleClassName(),
            ],
        ]);

        foreach ($iterator as $data) {
            $params[] = Toolbox::strtolower($data["criteria"]);
        }
        return $params;
    }


    /**
     * For tabs management : force isNewItem
     *
     * @since 0.83
     *
     * @return boolean
     **/
    public function isNewItem()
    {
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong               = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        $ong['no_all_tab'] = true;
        return $ong;
    }

    /**
     * Get label for main tab
     *
     * @return string
     */
    public function getMainTabLabel()
    {
        return _n('Rule', 'Rules', Session::getPluralNumber());
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item instanceof RuleCollection) {
            $ong = [];
            if ($item->showInheritedTab()) {
                //TRANS: %s is the entity name
                $ong[1] = sprintf(
                    __('Rules applied: %s'),
                    Dropdown::getDropdownName(
                        'glpi_entities',
                        $_SESSION['glpiactive_entity']
                    )
                );
            }
            $title = $item->getMainTabLabel();
            if ($item->isRuleRecursive()) {
                //TRANS: %s is the entity name
                $title = sprintf(
                    __('Local rules: %s'),
                    Dropdown::getDropdownName(
                        'glpi_entities',
                        $_SESSION['glpiactive_entity']
                    )
                );
            }
            $ong[2] = $title;
            if ($item->showChildrensTab()) {
                $ong[3] = __('Rules applicable in the sub-entities');
            }
            return $ong;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item instanceof RuleCollection) {
            $options = $_GET;
            switch ($tabnum) {
                case 1:
                    $options['inherited'] = 1;
                    break;

                case 2:
                    $options['inherited'] = 0;
                    break;

                case 3:
                    $options['inherited'] = 0;
                    $options['childrens'] = 1;
                    break;
            }
            if ($item->isRuleEntityAssigned()) {
                $item->setEntity($_SESSION['glpiactive_entity']);
            }
            $item->title();
            $item->showEngineSummary();
            $item->showListRules(Toolbox::cleanTarget($_GET['_target']), $options);
            return true;
        }
        return false;
    }


    /**
     * Get list of Rules
     *
     * @return array
     */
    public static function getRules(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $rules = [];
        foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
            $rulecollection = new $rulecollectionclass();
            if ($rulecollection->canList()) {
                if ($plug = isPluginItemType($rulecollectionclass)) {
                    $title = sprintf(
                        __('%1$s - %2$s'),
                        Plugin::getInfo($plug['plugin'], 'name'),
                        $rulecollection->getTitle()
                    );
                } else {
                    $title = $rulecollection->getTitle();
                }
                $ruleClassName = $rulecollection->getRuleClassName();

                $rules[] = [
                    'label' => $title,
                    'link'  => $ruleClassName::getSearchURL(),
                    'icon'  => $ruleClassName::getIcon(),
                ];
            }
        }

        if (
            Session::haveRight("transfer", READ)
            && Session::isMultiEntitiesMode()
        ) {
            $rules[] = [
                'label' => __('Transfer'),
                'link'  => Transfer::getSearchURL(),
                'icon'  => Transfer::getIcon(),
            ];
        }

        if (Session::haveRight("config", READ)) {
            $rules[] = [
                'label' => _n('Blacklist', 'Blacklists', Session::getPluralNumber()),
                'link'  => Blacklist::getSearchURL(),
                'icon'  => Blacklist::getIcon(),
            ];
        }

        return $rules;
    }


    /**
     * Get list of dictionaries
     *
     * @return array
     */
    public static function getDictionnaries(): array
    {
        $dictionnaries = [];

        $entries = [];

        if (Session::haveRight("rule_dictionnary_software", READ)) {
            $entries[] = [
                'label'  => Software::getTypeName(Session::getPluralNumber()),
                'link'   => 'ruledictionnarysoftware.php',
                'icon'   => Software::getIcon(),
            ];
        }

        if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
            $entries[] = [
                'label'  => Manufacturer::getTypeName(Session::getPluralNumber()),
                'link'   => 'ruledictionnarymanufacturer.php',
                'icon'   => Manufacturer::getIcon(),
            ];
        }

        if (Session::haveRight("rule_dictionnary_printer", READ)) {
            $entries[] = [
                'label'  => Printer::getTypeName(Session::getPluralNumber()),
                'link'   => 'ruledictionnaryprinter.php',
                'icon'   => Printer::getIcon(),
            ];
        }

        if (count($entries)) {
            $dictionnaries[] = [
                'type'      => __('Global dictionary'),
                'entries'   => $entries,
            ];
        }

        if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
            $dictionnaries[] = [
                'type'      => _n('Model', 'Models', Session::getPluralNumber()),
                'entries'   => [
                    [
                        'label'  => ComputerModel::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnarycomputermodel.php',
                        'icon'   => ComputerModel::getIcon(),
                    ], [
                        'label'  => MonitorModel::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnarymonitormodel.php',
                        'icon'   => MonitorModel::getIcon(),
                    ], [
                        'label'  => PrinterModel::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryprintermodel.php',
                        'icon'   => PrinterModel::getIcon(),
                    ], [
                        'label'  => CommonDeviceModel::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryperipheralmodel.php',
                        'icon'   => CommonDeviceModel::getIcon(),
                    ], [
                        'label'  => NetworkEquipmentModel::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnarynetworkequipmentmodel.php',
                        'icon'   => NetworkEquipmentModel::getIcon(),
                    ], [
                        'label'  => PhoneModel::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryphonemodel.php',
                        'icon'   => PhoneModel::getIcon(),
                    ],
                ],
            ];
        }

        if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
            $dictionnaries[] = [
                'type'      => _n('Type', 'Types', Session::getPluralNumber()),
                'entries'   => [
                    [
                        'label'  => ComputerType::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnarycomputertype.php',
                        'icon'   => ComputerType::getIcon(),
                    ], [
                        'label'  => MonitorType::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnarymonitortype.php',
                        'icon'   => MonitorType::getIcon(),
                    ], [
                        'label'  => PrinterType::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryprintertype.php',
                        'icon'   => PrinterType::getIcon(),
                    ], [
                        'label'  => CommonDeviceType::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryperipheraltype.php',
                        'icon'   => CommonDeviceType::getIcon(),
                    ], [
                        'label'  => NetworkEquipmentType::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnarynetworkequipmenttype.php',
                        'icon'   => NetworkEquipmentType::getIcon(),
                    ], [
                        'label'  => PhoneType::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryphonetype.php',
                        'icon'   => PhoneType::getIcon(),
                    ],
                ],
            ];
        }

        if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
            $dictionnaries[] = [
                'type'      => OperatingSystem::getTypeName(Session::getPluralNumber()),
                'entries'   => [
                    [
                        'label'  => OperatingSystem::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryoperatingsystem.php',
                        'icon'   => OperatingSystem::getIcon(),
                    ], [
                        'label'  => OperatingSystemServicePack::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryoperatingsystemservicepack.php',
                        'icon'   => OperatingSystemServicePack::getIcon(),
                    ], [
                        'label'  => OperatingSystemVersion::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryoperatingsystemversion.php',
                        'icon'   => OperatingSystemVersion::getIcon(),
                    ], [
                        'label'  => OperatingSystemArchitecture::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryoperatingsystemarchitecture.php',
                        'icon'   => OperatingSystemArchitecture::getIcon(),
                    ], [
                        'label'  => OperatingSystemEdition::getTypeName(Session::getPluralNumber()),
                        'link'   => 'ruledictionnaryoperatingsystemedition.php',
                        'icon'   => OperatingSystemEdition::getIcon(),
                    ],
                ],
            ];
        }

        return $dictionnaries;
    }
}
