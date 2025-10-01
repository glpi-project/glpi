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
use Glpi\Asset\AssetDefinitionManager;
use Glpi\DBAL\QueryExpression;
use Glpi\Event;
use Glpi\Plugin\Hooks;

use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\simplexml_load_string;

class RuleCollection extends CommonDBTM
{
    public const MOVE_BEFORE = 'before';
    public const MOVE_AFTER = 'after';

    /// Rule type
    public $sub_type;
    /// process collection stop on first matched rule
    public $stop_on_first_match                   = false;
    /// Processing several rules : use result of the previous one to computer the current one
    public $use_output_rule_process_as_next_input = false;
    /// Rule collection can be replay (for dictionary)
    public $can_replay_rules                      = false;
    /** @var SingletonRuleList $RuleList */
    public $RuleList                              = null;
    /// Menu type
    public $menu_type                             = "rule";
    /// Menu option
    public $menu_option                           = "";

    public $entity                                = 0;

    public static $rightname                             = 'config';

    /**
     * @var string Tab orientation : horizontal or vertical
     * @phpstan-var 'horizontal'|'vertical'
     */
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
                'ranking ASC',
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
        $where['sub_type'] = static::getRuleClassName();
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
                'ranking ASC',
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

        // Select all the rules of a different type
        $criteria   = $this->getRuleListCriteria($p);

        $iterator   = $DB->request($criteria);

        foreach ($iterator as $data) {
            //For each rule, get a Rule object with all the criterias and actions
            $tempRule               = $this->getRuleClass();
            $tempRule->fields       = $data;

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
        global $DB;

        if ($this->RuleList === null) {
            $this->RuleList = SingletonRuleList::getInstance(
                static::getRuleClassName(),
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

                foreach ($iterator as $rule) {
                    //For each rule, get a Rule object with all the criterias and actions
                    $tempRule = $this->getRuleClass();

                    if (
                        $tempRule->getRuleWithCriteriasAndActions(
                            $rule["id"],
                            (bool) $retrieve_criteria,
                            (bool) $retrieve_action
                        )
                    ) {
                        //Add the object to the list of rules
                        $this->RuleList->list[] = $tempRule;
                    }
                }

                $this->RuleList->load = $need;
            }
        }
    }

    /**
     * @return class-string<Rule> class-string if valid; else empty string
     */
    public static function getRuleClassName()
    {
        $classname = '';
        if (preg_match('/(.*)Collection/', static::class, $rule_class)) {
            if (is_a($rule_class[1], Rule::class, true)) {
                $classname = $rule_class[1];
            }
        }
        return $classname;
    }

    /**
     * Get a instance of the class to manipulate rule of this collection
     * @return Rule|null
     **/
    public function getRuleClass()
    {
        $name = static::getRuleClassName();
        if ($name !==  '' && is_a($name, Rule::class, true)) {
            return new $name();
        }
        return null;
    }

    /**
     * Is a confirmation needed before replay on DB ?
     * If needed need to send 'replay_confirm' in POST
     *
     * @return boolean true if confirmation is needed, else false
     *
     * since 11.0.0 The `$target` parameter has been removed and its value is automatically computed.
     */
    public function warningBeforeReplayRulesOnExistingDB()
    {
        return false;
    }

    /**
     * Count the total items that will be processed if rules are replayed.
     *
     * @param array $params Specific parameters used when rules are replayed.
     */
    public function countTotalItemsForRulesReplay(array $params = []): int
    {
        return 0;
    }

    /**
     * Replay Collection on DB
     *
     * @param int     $offset  first row to work on (default 0)
     * @param int     $maxtime max system time to stop working (default 0)
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
     * @used-by templates/pages/admin/rules/engine_summary.html.twig
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
        $cond = $rule::getConditionsArray();
        // Get max value
        if (count($cond)) {
            return max(array_keys($cond));
        }
        return 0;
    }

    public function showEngineSummary()
    {
        TemplateRenderer::getInstance()->display('pages/admin/rules/engine_summary.html.twig', [
            'collection' => $this,
        ]);
    }

    final public static function showCollectionsList(): void
    {

        $rules = self::getRules();
        // exclude inventory rules from the "others" block
        $rules = array_filter($rules, fn($rule) => !in_array($rule['sub_type'], [
            'RuleImportEntity',
            'RuleLocation',
            'RuleImportAsset',
            'RuleAsset',
            'RuleDefineItemtype',
        ]));

        TemplateRenderer::getInstance()->display('pages/admin/rules/index.html.twig', [
            'rules_group' => [
                [
                    'type'    => __('Other rules'),
                    'icon'    => 'ti ti-book',
                    'entries' => $rules,
                ],
            ],
        ]);
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
        global $CFG_GLPI;

        $p['inherited'] = 1;
        $p['childrens'] = 0;
        $p['active']    = false;
        $p['condition'] = 0;
        $p['_glpi_tab'] = $options['_glpi_tab'];
        $p['display_criterias'] = false;
        $p['display_actions']   = false;

        foreach (['inherited','childrens', 'condition'] as $param) {
            if (isset($options[$param]) && $this->isRuleRecursive()) {
                $p[$param] = (int) $options[$param];
            }
        }

        foreach (['display_criterias', 'display_actions'] as $param) {
            if (isset($options[$param])) {
                $p[$param] = (bool) $options[$param];
            }
        }

        $rule              = $this->getRuleClass();
        $display_entities  = ($this->isRuleRecursive() && ($p['inherited'] || $p['childrens']));
        $display_criterias = $p['display_criterias'];
        $display_actions   = $p['display_actions'];

        // Do not know what it is ?
        $canedit    = self::canUpdate() && !$display_entities;

        $use_conditions = false;
        if ($rule->useConditions()) {
            // First get saved option
            $p['condition'] = (int) Session::getSavedOption(static::class, 'condition', 0);
            if ($p['condition'] === 0) {
                $p['condition'] = $this->getDefaultRuleConditionForList();
            }
            $use_conditions = true;
            $twig_params = [
                'label' => __('Rules used for'),
                'conditions' => $rule::getConditionsArray(),
                'p' => $p,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="d-flex justify-content-center">
                    {{ fields.dropdownArrayField('condition', p.condition, conditions, label, {
                        on_change: 'reloadTab("start=0&inherited=' ~ p.inherited ~ '&childrens=' ~ p.childrens ~ '&condition=" + this.value)'
                    }) }}
                </div>
TWIG, $twig_params);
        }

        $nb         = $this->getCollectionSize((bool) $p['inherited'], $p['condition'], $p['childrens']);
        $p['start'] = $options['start'] ?? 0;

        if ($p['start'] >= $nb) {
            $p['start'] = 0;
        }

        $p['limit'] = $_SESSION['glpilist_limit'];
        $this->getCollectionPart($p);

        $ruletype = static::getRuleClassName();

        $entries = [];
        for ($i = $p['start'],$j = 0; isset($this->RuleList->list[$j]); $i++,$j++) {
            $entries[] = [
                'itemtype' => $ruletype,
                'id'       => $this->RuleList->list[$j]->fields['id'],
            ] + $this->RuleList->list[$j]->getDataForList($display_criterias, $display_actions, $display_entities, $canedit);
        }

        $columns = [
            'name' => __('Name'),
            'description' => __('Description'),
        ];
        if ($use_conditions) {
            $columns['condition'] = __('Use rule for');
        }
        if ($display_criterias) {
            $columns['criteria'] = RuleCriteria::getTypeName(Session::getPluralNumber());
        }
        if ($display_actions) {
            $columns['actions'] = RuleAction::getTypeName(Session::getPluralNumber());
        }
        $columns['is_active'] = __('Active');
        if ($display_entities) {
            $columns['entities_id'] = Entity::getTypeName(1);
        }
        $columns['rank'] = __('Position');
        $columns['sort'] = '';

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'rulelist',
            'table_class_style' => 'table-striped table-hover card-table',
            'is_tab' => true,
            'start' => $p['start'],
            'limit' => $p['limit'],
            'nofilter' => true,
            'nosort' => true,
            'super_header' => $this->getTitle(),
            'columns' => $columns,
            'formatters' => [
                'rank' => 'raw_html',
                'name' => 'raw_html',
                'criteria' => 'raw_html',
                'actions' => 'raw_html',
                'entity' => 'raw_html',
                'is_active' => 'raw_html',
                'sort' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $nb,
            'filtered_number' => count($entries),
            'showmassiveactions' => true,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
                'extraparams'   => [
                    'entity' => $this->entity,
                    'condition' => $p['condition'],
                    'rule_class_name' => static::getRuleClassName(),
                ],
                'item'          => $this,
            ],
        ]);
        $collection_classname = jsescape(static::class);
        echo <<<HTML
            <script>
                $(() => {
                    sortable('#rulelist tbody', {
                        handle: '.grip-rule',
                        placeholder: '<tr><td colspan="8" class="sortable-placeholder">&nbsp;</td></tr>'
                    })[0].addEventListener('sortupdate', (e) => {
                       const sort_detail = e.detail;
                       const new_index = sort_detail.destination.index;
                       const old_index = sort_detail.origin.index;

                       $.post(CFG_GLPI['root_doc'] + '/ajax/rule.php', {
                          'action': 'move_rule',
                          'rule_id': sort_detail.item.dataset.id,
                          'collection_classname':  "{$collection_classname}",
                          'sort_action': (old_index > new_index) ? 'before' : 'after',
                          'ref_id': sort_detail.destination.itemsBeforeUpdate[new_index].dataset.id,
                       });

                       displayAjaxMessageAfterRedirect();
                    });
                });
            </script>
HTML;

        $url = $CFG_GLPI["root_doc"];
        if ($plugin = isPluginItemType(static::class)) {
            $url .= "/plugins/{$plugin['plugin']}";
        }

        $twig_params = [
            'rule_class' => $rule::class,
            'can_reset' => $rule instanceof Rule && $rule::hasDefaultRules() && Config::canUpdate()
                && Session::getActiveEntity() === 0 && Session::getIsActiveEntityRecursive(),
            'can_replay' => $this->can_replay_rules,
            'reset_label' => __('Reset rules'),
            'reset_warning' => __('Rules will be erased and recreated from defaults. All existing rules will be lost.'),
            'test_label' => __('Test rules engine'),
            'replay_label' => __('Replay the dictionary rules'),
            'test_url' => $url . "/front/rulesengine.test.php?sub_type=" . $rule::class . "&condition={$p['condition']}",
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="d-flex justify-content-center">
                {% if can_reset %}
                    <button type="button" class="btn btn-ghost-danger mx-1" data-bs-toggle="modal" data-bs-target="#reset_rules">
                        {{ reset_label }}
                    </button>

                    {% set reset_btn %}
                        <a class="btn btn-danger w-100" role="button" href="{{ rule_class|itemtype_search_path }}?reinit=true&amp;subtype={{ rule_class|url_encode }}">
                            {{ reset_label }}
                        </a>
                    {% endset %}

                    {{ include('components/danger_modal.html.twig', {
                        'modal_id': 'reset_rules',
                        'confirm_btn': reset_btn,
                        'content': reset_warning
                    }) }}
                {% endif %}
                <button type="button" class="btn btn-primary mx-1" data-bs-toggle="modal" data-bs-target="#allruletest">{{ test_label }}</button>
                {% do call('Ajax::createIframeModalWindow', ['allruletest', test_url, {title: test_label}]) %}
                {% if can_replay %}
                    <a class="btn btn-primary mx-1" role="button" href="{{ rule_class|itemtype_search_path }}?replay_rule=replay_rule">{{ replay_label }}</a>
                {% endif %}
            </div>
TWIG, $twig_params);

        echo "<div class='mb-2'>";
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
        if (count($iterator) === 1) {
            $result = $iterator->current();
            $current_rank = (int) $result['ranking'];
            // Search rules to switch
            $criteria = [
                'SELECT' => ['id', 'ranking'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => [
                    'sub_type'  => static::getRuleClassName(),
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
            if (count($iterator2) === 1) {
                $result2 = $iterator2->current();
                $other_ID = $result2['id'];
                $new_rank = (int) $result2['ranking'];

                $rule = $this->getRuleClass();
                $result = false;
                $criteria = [
                    'SELECT' => ['id', 'ranking'],
                    'FROM'   => 'glpi_rules',
                    'WHERE'  => ['sub_type' => static::getRuleClassName()],
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
        global $DB;

        $result = $DB->update(
            'glpi_rules',
            [
                'ranking' => new QueryExpression($DB::quoteName('ranking') . ' - 1'),
            ],
            [
                'sub_type'  => static::getRuleClassName(),
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
     * @param string|integer  $type  Movement type, one of self::MOVE_AFTER or self::MOVE_BEFORE or the new rank
     *
     * @return boolean
     **/
    public function moveRule($ID, $ref_ID, $type = self::MOVE_AFTER, $new_rule = false)
    {
        global $DB;

        $ruleDescription = new Rule();

        // Get actual ranking of Rule to move
        $ruleDescription->getFromDB($ID);
        $old_rank = $ruleDescription->fields["ranking"];

        $max_ranking_criteria = [
            'SELECT' => ['MAX' => 'ranking AS maxi'],
            'FROM' => 'glpi_rules',
            'WHERE' => ['sub_type' => static::getRuleClassName()],
        ];

        if (is_numeric($type)) {
            if ($new_rule) {
                // The ranking for new rules should be more permissive. helps avoid issues during import when the rules
                // may not be in the order of ranking and therefore earlier rules may be higher than the current max + 1 ranking.
                $rank = max(0, $type);
            } else {
                $max_rank = $DB->request($max_ranking_criteria)->current()['maxi'];
                $rank = max(0, min($max_rank + 1, $type));
            }
        } else {
            // Compute new ranking
            if ($ref_ID) { // Move after/before an existing rule
                $ruleDescription->getFromDB($ref_ID);
                $rank = $ruleDescription->fields["ranking"];
            } elseif ($type === self::MOVE_AFTER) {
                // Move after all
                $result = $DB->request($max_ranking_criteria)->current();
                $rank = $result['maxi'];
            } else {
                // Move before all
                $rank = 0;
            }
        }

        $rule   = $this->getRuleClass();
        if ($rule === null) {
            return false;
        }

        $result = is_numeric($type);

        // Move others rules in the collection
        // If it is a new rule, there is no need to move any other rules back
        if (!$new_rule && $old_rank < $rank) {
            if ($type === self::MOVE_BEFORE) {
                $rank--;
            }

            // Move back all rules between old and new rank
            $iterator = $DB->request([
                'SELECT' => ['id', 'ranking AS _ranking'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => [
                    'sub_type'  => static::getRuleClassName(),
                    ['ranking'  => ['>', $old_rank]],
                    ['ranking'  => ['<=', $rank]],
                ],
            ]);
            foreach ($iterator as $data) {
                $data['_ranking']--;
                $result = $rule->update($data);
            }
        } elseif ($new_rule || $old_rank > $rank) {
            if ($type === self::MOVE_AFTER) {
                $rank++;
            }

            // Move forward all rule  between old and new rank
            $iterator = $DB->request([
                'SELECT' => ['id', 'ranking AS _ranking'],
                'FROM'   => 'glpi_rules',
                'WHERE'  => [
                    'sub_type'  => static::getRuleClassName(),
                    ['ranking'  => ['>=', $rank]],
                    ['ranking'  => ['<', $old_rank]],
                ],
            ]);
            foreach ($iterator as $data) {
                $data['_ranking']++;
                $result = $rule->update($data);
            }
        } else { // $old_rank == $rank : nothing to do
            $result = false;
        }

        // Move the rule
        if ($result && ($old_rank !== $rank)) {
            $result = $rule->update([
                'id'      => $ID,
                '_ranking' => $rank,
            ]);
        }
        return $result;
    }

    /**
     * Print a title for backup rules
     *
     * @since 0.85
     *
     * @return void
     * @todo Not used in GLPI core. Used by glpiinventory plugin
     **/
    public static function titleBackup()
    {
        TemplateRenderer::getInstance()->display('pages/admin/rules/backup_header.html.twig');
    }

    /**
     * Export rules in XML format
     *
     * @param array $items the input data to transform to XML
     *
     * @since 0.85
     *
     * @return void send attachment to browser
     **/
    public static function exportRulesToXML($items = [])
    {
        // get rules XML file
        $xml = self::getRulesXMLFile($items);

        if (!$xml) {
            return;
        }

        // send attachment to browser
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename="rules.xml"');
        echo $xml;
    }

    /**
     * Export rules in a xml format
     *
     * @param array $items array the input data to transform to xml
     *
     * @return ?string
     */
    public static function getRulesXMLFile($items = [])
    {
        if (!count($items)) {
            return null;
        }

        $rulecollection = new self();
        $rulecritera    = new RuleCriteria();
        $ruleaction     = new RuleAction();

        //create XML
        $xmlE           = new SimpleXMLElement('<rules/>');

        //parse all rules
        foreach ($items as $ID) {
            $rulecollection->getFromDB($ID);
            if (!class_exists($rulecollection->fields['sub_type']) || !is_a($rulecollection->fields['sub_type'], Rule::class, true)) {
                continue;
            }
            $rule = new $rulecollection->fields['sub_type']();
            unset($rulecollection->fields['id'], $rulecollection->fields['date_mod']);

            $name = Dropdown::getDropdownName(
                "glpi_entities",
                $rulecollection->fields['entities_id']
            );
            $rulecollection->fields['entities_id'] = $name;

            // add root node
            $xmlERule = $xmlE->addChild('rule');

            //convert rule direct indexes in XML
            foreach ($rulecollection->fields as $key => $val) {
                $xmlERule->$key = $val;
            }

            //find criterias
            $criterias = $rulecritera->find(['rules_id' => $ID]);
            foreach ($criterias as &$criteria) {
                unset($criteria['id'], $criteria['rules_id']);

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
                    ($action['action_type'] === "assign")
                    && (str_contains($action['field'], '_id'))
                    && !(($action['field'] === "entities_id")
                     && ((int) $action['value'] === 0))
                ) {
                    $field = $action['field'];
                    if ($action['field'][0] === "_") {
                        $field = substr($action['field'], 1);
                    }

                    $table = getTableNameForForeignKeyField($field);
                    $available_actions = $rule->getActions();
                    if (isset($available_actions[$field]['table'])
                        && !empty($available_actions[$field]['table'])
                    ) {
                        $table = $available_actions[$field]['table'];
                    }

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

        // convert SimpleXMLElement to xml string
        return $xmlE->asXML();
    }

    /**
     * Print a form to select a XML file for import rules
     *
     * @since 0.85
     *
     * @return void
     **/
    public static function displayImportRulesForm()
    {
        TemplateRenderer::getInstance()->display('pages/admin/rules/import.html.twig');
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
        $type = $available_criteria[$criterion]['type'] ?? false;
        return (in_array($condition, [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT, Rule::PATTERN_UNDER], true)
              && ($type === 'dropdown'));
    }

    /**
     * Print a form to inform user when conflicts appear during the import of rules from a XML file
     *
     * @since 0.85
     *
     * @return boolean true if all ok
     **/
    public static function previewImportRules()
    {
        global $DB;

        if (!isset($_FILES["xml_file"]) || ($_FILES["xml_file"]["size"] == 0)) {
            return false;
        }

        if ($_FILES["xml_file"]["error"] !== UPLOAD_ERR_OK) {
            Session::addMessageAfterRedirect(__s("No file was uploaded"));
            return false;
        }
        // get XML file content
        $xml           = file_get_contents($_FILES["xml_file"]["tmp_name"]);
        // convert a XML string into a SimpleXml object
        if (!$xmlE = simplexml_load_string($xml)) {
            Session::addMessageAfterRedirect(__s('Unauthorized file type'), false, ERROR);
        }
        // convert SimpleXml object into an array and store it in session
        $rules         = json_decode(json_encode((array) $xmlE), true);
        // check rules (check if entities, criteria and actions is always good in this glpi)
        $entity        = new Entity();
        $rules_refused = [];
        /** @var array<class-string<Rule>, Rule> $rule_subtypes Cache of rule subtype instances */
        $rule_subtypes = [];

        // In case there's only one rule to import, recreate an array with key => value
        if (isset($rules['rule']['entities_id'])) {
            $rules['rule'] = [0 => $rules['rule']];
        }

        foreach ($rules['rule'] as $k_rule => &$rule) {
            if (!isset($rule_subtypes[$rule['sub_type']])) {
                if (!is_a($rule['sub_type'], Rule::class, true)) {
                    continue;
                }
                $tmprule = new $rule['sub_type']();
                $rule_subtypes[$tmprule::class] = $tmprule;
            } else {
                $tmprule = $rule_subtypes[$rule['sub_type']];
            }

            $refused_rule = [
                'uuid' => $rule['uuid'],
                'rule_name' => $rule['name'],
                'type_title' => $tmprule->getTitle(),
                'reasons' => [],
            ];
            // check entities
            if ($tmprule->isEntityAssign()) {
                $entities_found = $entity->find(['completename' => $rule['entities_id']]);
                if (empty($entities_found)) {
                    $refused_rule['reasons']['entity'] = $rule['entities_id'];
                }
            }

            // process direct attributes
            foreach ($rule as &$val) {
                if (
                    $val === []
                ) {
                    $val = "";
                }
            }
            unset($val);

            // check criterias
            if (isset($rule['rulecriteria'])) {
                // check and correct criterias array format
                if (isset($rule['rulecriteria']['criteria'])) {
                    $rule['rulecriteria'] = [$rule['rulecriteria']];
                }

                foreach ($rule['rulecriteria'] as $k_crit => $criteria) {
                    // Fix patterns decoded as empty arrays to prevent empty IN clauses in SQL generation.
                    if ($criteria['pattern'] === []) {
                        $criteria['pattern'] = '';
                    }

                    $available_criteria = $tmprule->getCriterias();
                    $crit               = $criteria['criteria'];
                    // check FK (just in case of "is", "is_not" and "under" criteria)
                    if (
                        self::isCriteraADropdown(
                            $available_criteria,
                            $criteria['condition'],
                            $crit
                        )
                    ) {
                        $item = getItemForTable($available_criteria[$crit]['table']);
                        if ($item instanceof CommonTreeDropdown) {
                            $found = $item->find(['completename' => $criteria['pattern']]);
                        } else {
                            $found = $item->find(['name' => $criteria['pattern']]);
                        }
                        if (empty($found)) {
                            $criteria = $rules['rule'][$k_rule]['rulecriteria'][$k_crit];
                            $refused_rule['reasons']['criteria'][] = [
                                'id' => $k_crit,
                                'name' => $tmprule->getCriteriaName($criteria["criteria"]),
                                'label' => RuleCriteria::getConditionByID($criteria["condition"], $item::class, $criteria["criteria"]),
                                'pattern' => $criteria["pattern"],
                            ];
                        } else {
                            $tmp = array_pop($found);
                            $rules['rule'][$k_rule]['rulecriteria'][$k_crit]['pattern'] = $tmp['id'];
                        }
                    }
                }
            }

            // check actions
            if (isset($rule['ruleaction'])) {
                // check and correct actions array format
                if (isset($rule['ruleaction']['field'])) {
                    $rule['ruleaction'] = [$rule['ruleaction']];
                }

                foreach ($rule['ruleaction'] as $k_action => $action) {
                    // Fix values decoded as empty arrays to prevent empty IN clauses in SQL generation.
                    if ($action['value'] === []) {
                        $action['value'] = '';
                    }
                    $available_actions = $tmprule->getActions();
                    $act               = $action['field'];

                    if (
                        ($action['action_type'] === "assign")
                        && (isset($available_actions[$act]['type'])
                        && ($available_actions[$act]['type'] === 'dropdown'))
                    ) {
                        //pass root entity and empty array (N/A value)
                        if (
                            (in_array($action['field'], ['entities_id', 'new_entities_id'], true))
                            && (($action['value'] == 0)
                            || ($action['value'] == ''))
                        ) {
                            continue;
                        }

                        $item = getItemForTable($available_actions[$act]['table']);
                        if ($item instanceof CommonTreeDropdown) {
                            $found = $item->find(['completename' => $action['value']]);
                        } else {
                            $found = $item->find(['name' => $action['value']]);
                        }
                        if (empty($found)) {
                            $action = $rule['ruleaction'][$k_action];
                            $refused_rule['reasons']['actions'][] = [
                                'id' => $k_action,
                                'name' => $tmprule->getActionName($action["field"]),
                                'label' => RuleAction::getActionByID($action["action_type"]),
                                'value' => $action["value"] ?? '',
                            ];
                        } else {
                            $tmp = array_pop($found);
                            $rules['rule'][$k_rule]['ruleaction'][$k_action]['value'] = $tmp['id'];
                        }
                    }
                }
            }

            if (count($refused_rule['reasons'])) {
                $rules_refused[$k_rule] = $refused_rule;
            }
        }
        unset($rule);

        // save rules for ongoing processing
        $_SESSION['glpi_import_rules']         = $rules;
        $rules_refused_for_session = [];
        foreach ($rules_refused as $k => $rule) {
            $r = [];
            if (isset($rule['reasons']['entity'])) {
                $r['entity'] = true;
            }
            if (isset($rule['reasons']['criteria'])) {
                $r['criterias'] = array_map(static fn($c) => $c['id'], $rule['reasons']['criteria']);
            }
            if (isset($rule['reasons']['actions'])) {
                $r['actions'] = array_map(static fn($c) => $c['id'], $rule['reasons']['actions']);
            }
            $rules_refused_for_session[$k] = $r;
        }

        $_SESSION['glpi_import_rules_refused'] = $rules_refused_for_session;

        // if no conflict detected, we can directly process the import
        if (!count($rules_refused)) {
            Html::redirect("rule.backup.php?action=process_import");
        }

        TemplateRenderer::getInstance()->display('pages/admin/rules/import_preview.html.twig', [
            'refused_rules' => $rules_refused,
        ]);

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
        global $DB;
        $ruleCriteria = new RuleCriteria();
        $ruleAction   = new RuleAction();
        $entity       = new Entity();

        // get session vars
        $rules         = $_SESSION['glpi_import_rules'];
        $rules_refused = $_SESSION['glpi_import_rules_refused'];
        $rr_keys       = array_keys($rules_refused);
        unset($_SESSION['glpi_import_rules'], $_SESSION['glpi_import_rules_refused']);

        // unset all refused rules
        foreach ($rules['rule'] as $k_rule => &$rule) {
            if (in_array($k_rule, $rr_keys, true)) {
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

        // import all right rules
        while (!empty($rules['rule'])) {
            $current_rule             = array_shift($rules['rule']);
            $add_criteria_and_actions = false;
            $params                   = [];
            $itemtype                 = $current_rule['sub_type'];
            $item                     = getItemForItemtype($itemtype);

            // Find a rule by it's uuid
            $found    = $item->find(['uuid' => $current_rule['uuid']]);
            $params   = $current_rule;
            unset($params['rulecriteria'], $params['ruleaction']);

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
                // Should not be necessary but without it there's an sql error...
                if (!isset($params[$field]) || ($params[$field] === '')) {
                    $params[$field] = 0;
                }
            }

            // if uuid not exist, create rule
            if (empty($found)) {
                // Manage entity
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

                    // remove all dependent criterias and action
                    $ruleCriteria->deleteByCriteria(["rules_id" => $rules_id]);
                    $ruleAction->deleteByCriteria(["rules_id" => $rules_id]);
                    $add_criteria_and_actions = true;
                }
            }

            if ($add_criteria_and_actions) {
                // Add criteria
                if (isset($current_rule['rulecriteria'])) {
                    foreach ($current_rule['rulecriteria'] as $criteria) {
                        $criteria['rules_id'] = $rules_id;
                        // fix array in value key
                        // (simplexml bug, empty XML node are converted in empty array instead of null)
                        if (is_array($criteria['pattern'])) {
                            $criteria['pattern'] = null;
                        }
                        $ruleCriteria->add($criteria);
                    }
                }

                // Add actions
                if (isset($current_rule['ruleaction'])) {
                    foreach ($current_rule['ruleaction'] as $action) {
                        $action['rules_id'] = $rules_id;
                        // fix array in value key
                        // (simplexml bug, empty XML node are converted in empty array instead of null)
                        if (is_array($action['value'])) {
                            $action['value'] = null;
                        }
                        $ruleAction->add($action);
                    }
                }
            }
        }

        Session::addMessageAfterRedirect(__s('Successful importation'));

        return true;
    }

    /**
     * Process all the rules collection
     *
     * @param array $input    The input data used to check criterias
     * @param array $output   The initial ouput array used to be manipulate by actions
     * @param array $params   Parameters for all internal functions
     * @param array $options  Options :
     *                            - condition : specific condition to limit rule list
     *                            - only_criteria : only react on specific criteria
     *
     * @return array The output array updated by actions
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
        $params['rule_itemtype']    = static::getRuleClassName();

        if (count($this->RuleList->list)) {
            foreach ($this->RuleList->list as $rule) {
                if ($p['condition'] && !($rule->fields['condition'] & $p['condition'])) {
                    // Rule is loaded in the cache but is not relevant for the current condition
                    continue;
                }

                // If the rule is active, process it

                if ($rule->fields["is_active"]) {
                    $output["_rule_process"] = false;
                    $rule->process($input, $output, $params, $p);

                    if (
                        (isset($output['_stop_rules_processing']) && (int) $output['_stop_rules_processing'] === 1)
                        || ($output["_rule_process"] && $this->stop_on_first_match)
                    ) {
                        unset($output["_stop_rules_processing"], $output["_rule_process"]);
                        $output["_ruleid"] = $rule->fields["id"];
                        return $output;
                    }
                }

                if ($this->use_output_rule_process_as_next_input) {
                    $output = $this->prepareInputDataForProcessWithPlugins($output, $params);
                    $input  = $output;
                }
            }
        }

        return $output;
    }

    /**
     * Show form displaying results for rule collection preview
     *
     * @param array   $values    array of data
     * @param integer $condition condition to limit rules (default 0)
     *
     * @return array
     *
     * @since 11.0.0 The `$target` parameter has been removed.
     */
    public function showRulesEnginePreviewCriteriasForm(array $values, $condition = 0)
    {
        $input = $this->prepareInputDataForTestProcess($condition);
        $rule      = $this->getRuleClass();
        if ($rule === null) {
            return $input;
        }
        $criterias = $rule->getAllCriteria();

        if (count($input)) {
            // Add all used criteria on rule as `Rule::showSpecificCriteriasForPreview()`
            // adapt its output depending on used criteria
            $rule->criterias = [];
            foreach ($input as $criteria) {
                $rule->criterias[] = (object) [
                    'fields' => ['criteria' => $criteria],
                ];
            }
        }

        $target = '/front/rulesengine.test.php';
        if ($plugin = isPluginItemType(static::class)) {
            $target = '/plugins/' . $plugin['plugin'] . $target;
        }

        TemplateRenderer::getInstance()->display('pages/admin/rules/engine_preview_criteria.html.twig', [
            'rule' => $rule,
            'input' => $input,
            'values' => $values,
            'criteria' => $criterias,
            'rule_classname' => static::getRuleClassName(),
            'condition' => $condition,
            'params' => [
                'target' => $target,
            ],
        ]);

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
                // If the rule is active, process it
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
     * @param array $input  The input data used to check criteria
     * @param array $params Parameters
     *
     * @return array The updated input data
     **/
    public function prepareInputDataForProcess($input, $params)
    {
        return $input;
    }

    /**
     * Prepare input data for the rules collection, also using plugins values
     *
     * @since 0.84
     *
     * @param array $input  the input data used to check criteria
     * @param array $params parameters
     *
     * @return array The updated input data
     **/
    public function prepareInputDataForProcessWithPlugins($input, $params)
    {
        global $PLUGIN_HOOKS;

        $input = $this->prepareInputDataForProcess($input, $params);
        if (isset($PLUGIN_HOOKS[Hooks::USE_RULES])) {
            foreach ($PLUGIN_HOOKS[Hooks::USE_RULES] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array(static::getRuleClassName(), $val, true)) {
                    $results = Plugin::doOneHook(
                        $plugin,
                        Hooks::AUTO_RULE_COLLECTION_PREPARE_INPUT_DATA_FOR_PROCESS,
                        ['rule_itemtype' => static::getRuleClassName(),
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
                'glpi_rules.sub_type'   => static::getRuleClassName(),
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
     * @param array   $input     array of data
     * @param integer $condition condition to limit rules (DEFAULT 0)
     *
     * @return void
     *
     * @since 11.0.0 The `$target` parameter has been removed.
     */
    public function showRulesEnginePreviewResultsForm(array $input, $condition = 0)
    {
        global $DB;
        $output = [];

        if ($this->use_output_rule_process_as_next_input) {
            $output = $input;
        }
        $output = $this->testAllRules($input, $output, $input, $condition);
        $rule   = $this->getRuleClass();
        if ($rule === null) {
            return;
        }
        $results = [];

        foreach ($output["result"] as $ID => $rule_result) {
            $it = $DB->request([
                'SELECT' => ['name'],
                'FROM'   => $rule::getTable(),
                'WHERE'  => ['id' => $ID],
                'LIMIT'  => 1,
            ]);
            $name = $it->current()['name'] ?? '';
            $result = match ($rule_result['result']) {
                0, 1 => Dropdown::getYesNo($rule_result['result']),
                2 => __('Inactive'),
                default => ''
            };
            $results[] = [
                'name'   => $name,
                'result' => $result,
            ];
        }

        $output        = $this->cleanTestOutputCriterias($output);
        unset($output["result"]);
        $global_result = (count($output) ? 1 : 0);
        $actions = $rule->getAllActions();
        $output = $this->preProcessPreviewResults($output);
        $result_actions = [];

        foreach ($output as $criteria => $value) {
            if (!isset($actions[$criteria])) {
                continue;
            }
            $action_type = $actions[$criteria]['action_type'] ?? '';
            $result_actions[] = [
                'name' => $actions[$criteria]['name'] ?? '',
                'value' => $rule->getActionValue($criteria, $action_type, $value),
            ];
        }

        TemplateRenderer::getInstance()->display('pages/admin/rules/engine_preview_results.html.twig', [
            'results' => $results,
            'global_result_raw' => $global_result,
            'global_result' => Dropdown::getYesNo($global_result),
            'result_actions' => $result_actions,
        ]);
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
        if ($rule === null) {
            return $output;
        }
        $actions = $rule->getAllActions();

        // If output array contains keys begining with _ : drop it
        foreach (array_keys($output) as $criteria) {
            if ($criteria[0] === '_' && !isset($actions[$criteria])) {
                unset($output[$criteria]);
            }
        }
        return $output;
    }

    /**
     * @param array $output
     *
     * @return array
     **/
    public function preProcessPreviewResults($output)
    {
        global $PLUGIN_HOOKS;

        if (isset($PLUGIN_HOOKS[Hooks::USE_RULES])) {
            $params['rule_itemtype'] = static::class;
            foreach ($PLUGIN_HOOKS[Hooks::USE_RULES] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array($params['rule_itemtype'], $val, true)) {
                    $results = Plugin::doOneHook(
                        $plugin,
                        Hooks::AUTO_PRE_PROCESS_RULE_COLLECTION_PREVIEW_RESULTS,
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
        global $CFG_GLPI;

        if ($plug = isPluginItemType($itemtype)) {
            $typeclass = 'Plugin' . $plug['plugin'] . $plug['class'] . 'Collection';
        } else {
            if (in_array($itemtype, $CFG_GLPI["dictionnary_types"], true)) {
                $typeclass = 'RuleDictionnary' . $itemtype . "Collection";
            } else {
                $typeclass = $itemtype . "Collection";
            }
        }

        if (
            ($check_dictionnary_type && in_array($itemtype, $CFG_GLPI["dictionnary_types"], true))
            || !$check_dictionnary_type
        ) {
            $item = getItemForItemtype($typeclass);
            if ($item instanceof RuleCollection) {
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
                'glpi_rules.sub_type'   => static::getRuleClassName(),
            ],
        ]);

        foreach ($iterator as $data) {
            $params[] = Toolbox::strtolower($data["criteria"]);
        }
        return $params;
    }

    public function isNewItem()
    {
        // For tabs management : force isNewItem
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong               = [];
        $this->addStandardTab(self::class, $ong, $options);
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
        if ($item instanceof self) {
            $ong = [];
            if ($item->showInheritedTab()) {
                $ong[1] = self::createTabEntry(
                    sprintf(
                        // TRANS: %s is the entity name
                        __('Rules applied: %s'),
                        Dropdown::getDropdownName(
                            'glpi_entities',
                            $_SESSION['glpiactive_entity']
                        )
                    )
                );
            }
            $title = $item->getMainTabLabel();
            if ($item->isRuleRecursive()) {
                $title = self::createTabEntry(
                    sprintf(
                        // TRANS: %s is the entity name
                        __('Local rules: %s'),
                        Dropdown::getDropdownName(
                            'glpi_entities',
                            $_SESSION['glpiactive_entity']
                        )
                    )
                );
            }
            $ong[2] = $title;
            if ($item->showChildrensTab()) {
                $ong[3] = self::createTabEntry(__('Rules applicable in the sub-entities'));
            }
            return $ong;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
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
        global $CFG_GLPI;

        $rules = [];
        foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
            if (!is_a($rulecollectionclass, RuleCollection::class, true)) {
                continue;
            }
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
                    'label'    => $title,
                    'link'     => $ruleClassName::getSearchURL(),
                    'icon'     => $ruleClassName::getIcon(),
                    'sub_type' => $ruleClassName,
                ];
            }
        }

        if (
            Session::haveRight("transfer", READ)
            && Session::isMultiEntitiesMode()
        ) {
            $rules[] = [
                'label'    => Transfer::getTypeName(),
                'link'     => Transfer::getSearchURL(),
                'icon'     => Transfer::getIcon(),
                'sub_type' => Transfer::class,
            ];
        }

        if (Session::haveRight("config", READ)) {
            $rules[] = [
                'label'     => _n('Blacklist', 'Blacklists', Session::getPluralNumber()),
                'link'      => Blacklist::getSearchURL(),
                'icon'      => Blacklist::getIcon(),
                'sub_type'  => Blacklist::class,
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

        $custom_assets = AssetDefinitionManager::getInstance()->getDefinitions(true);

        if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
            $model_dictionaries = [
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

            foreach ($custom_assets as $custom_asset) {
                $model_class = $custom_asset->getAssetModelClassName();
                $model_dictionaries['entries'][] = [
                    'label' => $model_class::getTypeName(Session::getPluralNumber()),
                    'link'  => $custom_asset->getAssetModelDictionaryCollectionClassName()::getRuleClassName()::getSearchURL(),
                    'icon'  => $model_class::getIcon(),
                ];
            }

            $dictionnaries[] = $model_dictionaries;
        }

        if (Session::haveRight("rule_dictionnary_dropdown", READ)) {
            $type_dictionaries = [
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

            foreach ($custom_assets as $custom_asset) {
                $type_class = $custom_asset->getAssetTypeClassName();
                $type_dictionaries['entries'][] = [
                    'label' => $type_class::getTypeName(Session::getPluralNumber()),
                    'link'  => $custom_asset->getAssetTypeDictionaryCollectionClassName()::getRuleClassName()::getSearchURL(),
                    'icon'  => $type_class::getIcon(),
                ];
            }

            $dictionnaries[] = $type_dictionaries;
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
