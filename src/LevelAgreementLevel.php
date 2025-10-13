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
 * LevelAgreementLevel class
 *
 * Abstract class for common code in SlaLevel & OlaLevel
 *
 * @since  9.2.1
 **/
abstract class LevelAgreementLevel extends RuleTicket
{
    public static $rightname            = 'slm';

    /**
     * LevelAgreement parent class.
     * Have to be redefined by concrete class.
     * @var class-string<LevelAgreement>
     */
    protected static $parentclass;
    /**
     * LevelAgreement parent class foreign key.
     * Have to be redefined by concrete class.
     * @var string
     */
    protected static $fkparent;

    /**
     * Constructor
     **/
    public function __construct()
    {
        // Override in order not to use glpi_rules table.
    }

    abstract public function showForParent(LevelAgreement $la);

    /**
     * @since 0.85
     **/
    public static function getConditionsArray()
    {
        // Override ruleticket one
        return [];
    }

    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Escalation level', 'Escalation levels', $nb);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => static::getTypeName(),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'execution_time',
            'name'               => __('Execution'),
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'match',
            'name'               => __('Logical operator'),
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        switch ($field) {
            case 'execution_time':
                $possible_values = self::getExecutionTimes();
                if (isset($possible_values[$values[$field]])) {
                    return htmlescape($possible_values[$values[$field]]);
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'execution_time':
                return self::dropdownExecutionTime($name, $options);

            case 'match':
                $level = new static();
                $options['value'] = $values[$field];
                return $level->dropdownRulesMatch($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function getActions()
    {
        $actions = parent::getActions();

        // Only append actors
        $actions['_users_id_requester']['force_actions']  = ['append'];
        $actions['_groups_id_requester']['force_actions'] = ['append'];
        $actions['_users_id_assign']['force_actions']     = ['append'];
        $actions['_groups_id_assign']['force_actions']    = ['append'];
        $actions['_suppliers_id_assign']['force_actions'] = ['append'];
        $actions['_users_id_observer']['force_actions']   = ['append'];
        $actions['_groups_id_observer']['force_actions']  = ['append'];

        return $actions;
    }

    public function getCriterias()
    {

        $actions = parent::getActions();

        unset($actions['olas_id']);
        unset($actions['slas_id']);
        // Could not be used as criteria
        unset($actions['users_id_validate_requester_supervisor']);
        unset($actions['users_id_validate_assign_supervisor']);
        unset($actions['affectobject']);
        unset($actions['groups_id_validate']);
        unset($actions['users_id_validate']);
        unset($actions['validationsteps_id']);
        unset($actions['validationsteps_threshold']);
        $actions['status']['name']    = __('Status');
        $actions['status']['type']    = 'dropdown_status';
        return $actions;
    }

    public static function getExecutionTimes($options = [])
    {
        $p = [
            'value'    => '',
            'max_time' => 4 * DAY_TIMESTAMP,
            'used'     => [],
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $possible_values = [];
        for ($i = 10; $i < 60; $i += 10) {
            if (!in_array($i * MINUTE_TIMESTAMP, $p['used'])) {
                $possible_values[$i * MINUTE_TIMESTAMP] = sprintf(_n('+ %d minute', '+ %d minutes', $i), $i);
            }
            if (!in_array(-$i * MINUTE_TIMESTAMP, $p['used'])) {
                if ($p['max_time'] >= $i * MINUTE_TIMESTAMP) {
                    $possible_values[-$i * MINUTE_TIMESTAMP] = sprintf(_n('- %d minute', '- %d minutes', $i), $i);
                }
            }
        }

        for ($i = 1; $i < 24; $i++) {
            if (!in_array($i * HOUR_TIMESTAMP, $p['used'])) {
                $possible_values[$i * HOUR_TIMESTAMP] = sprintf(_n('+ %d hour', '+ %d hours', $i), $i);
            }
            if (!in_array(-$i * HOUR_TIMESTAMP, $p['used'])) {
                if ($p['max_time'] >= $i * HOUR_TIMESTAMP) {
                    $possible_values[-$i * HOUR_TIMESTAMP] = sprintf(
                        _n('- %d hour', '- %d hours', $i),
                        $i
                    );
                }
            }
        }

        for ($i = 1; $i <= 100; $i++) {
            if (!in_array($i * DAY_TIMESTAMP, $p['used'])) {
                $possible_values[$i * DAY_TIMESTAMP] = sprintf(_n('+ %d day', '+ %d days', $i), $i);
            }
            if (!in_array(-$i * DAY_TIMESTAMP, $p['used'])) {
                if ($p['max_time'] >= $i * DAY_TIMESTAMP) {
                    $possible_values[-$i * DAY_TIMESTAMP] = sprintf(_n('- %d day', '- %d days', $i), $i);
                }
            }
        }

        if (
            !in_array(0, $p['used'])
            && isset($p['type'])
        ) {
            if ($p['type'] == 1) {
                $possible_values[0] = __('Time to own');
            } else {
                $possible_values[0] = __('Time to resolve');
            }
        }
        ksort($possible_values);

        return $possible_values;
    }

    /**
     * Dropdown execution time for SLA
     *
     * @param string $name name of the select
     * @param array $options Array of possible options:
     *       - value : default value
     *       - max_time : max time to use
     *       - used : already used values
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function dropdownExecutionTime($name, $options = [])
    {
        $p = [
            'value'    => '',
            'max_time' => 4 * DAY_TIMESTAMP,
            'used'     => [],
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        // Display default value;
        if (($key = array_search($p['value'], $p['used'])) !== false) {
            unset($p['used'][$key]);
        }

        $possible_values = self::getExecutionTimes($p);

        return Dropdown::showFromArray($name, $possible_values, $p);
    }

    /**
     * Get already used execution time for a OLA
     *
     * @param integer $las_id id of the OLA
     *
     * @return array of already used execution times
     **/
    public static function getAlreadyUsedExecutionTime($las_id)
    {
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'SELECT'          => 'execution_time',
            'DISTINCT'        => true,
            'FROM'            => static::getTable(),
            'WHERE'           => [
                static::$fkparent => $las_id,
            ],
        ]);

        foreach ($iterator as $data) {
            $result[$data['execution_time']] = $data['execution_time'];
        }
        return $result;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case static::$parentclass:
                    if (
                        $_SESSION['glpishow_count_on_tabs']
                        && ($item instanceof CommonDBTM)
                    ) {
                        $nb =  countElementsInTable(static::getTable(), [static::$fkparent => $item->getID()]);
                    }
                    return self::createTabEntry(static::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof LevelAgreement) {
            $level = new static();
            $level->showForParent($item);
        }
        return true;
    }

    /**
     * Should calculation on this LA Level target date be done using
     * the "work_in_day" parameter set to true ?
     *
     * @return bool
     * @used-by LevelAgreement::computeExecutionDate()
     */
    public function shouldUseWorkInDayMode(): bool
    {
        // No definition time here so we must guess the unit from the raw seconds value
        return abs($this->fields['execution_time']) >= DAY_TIMESTAMP;
    }

    public function showForm($ID, array $options = [])
    {
        /** @var class-string<LevelAgreement> $parent_class */
        $parent_class = static::$parentclass;
        $canedit = $this->can($ID, UPDATE);
        if (isset($options['la'])) {
            $la = $options['la'];
        } else {
            $la = getItemForItemtype($parent_class);
            $la->getFromDB($this->fields[$parent_class::getForeignKeyField()]);
        }

        TemplateRenderer::getInstance()->display('pages/setup/levelagreement_level.html.twig', [
            'item' => $this,
            'no_header' => $options['no_header'] ?? false,
            'parent_class' => $parent_class,
            'la' => $la,
            'operators' => $this->getRulesMatch(is_string($this->restrict_matching) ? $this->restrict_matching : null),
            'params' => $options + [
                'canedit' => $canedit,
            ],
        ]);

        return true;
    }

    /**
     * @param LevelAgreement $la The Level Agreement object (SLA or OLA)
     * @return void
     */
    final protected function showForLA(LevelAgreement $la): void
    {
        global $DB;

        $ID = $la->getField('id');
        if (!$la->can($ID, READ)) {
            return;
        }

        $parent_class = static::$parentclass;
        $canedit = $la->can($ID, UPDATE);

        if ($canedit) {
            $this->showForm(0, [
                'no_header' => true,
                'la' => $la,
            ]);
        }

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [
                $parent_class::getForeignKeyField()   => $ID,
            ],
            'ORDER'  => 'execution_time',
        ]);

        $entries = [];
        $la_level = new static();
        foreach ($iterator as $data) {
            $la_level->getFromResultSet($data);
            $la_level->getRuleWithCriteriasAndActions($la_level->getID(), true, true);

            if ($la_level->fields["execution_time"] !== 0) {
                $execution_time = Html::timestampToString($la_level->fields["execution_time"], false);
            } else {
                $execution_time = $la->fields['type'] === 1
                    ? __('Time to own')
                    : __('Time to resolve');
            }

            // language=Twig
            $criteria_list = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <table class="table table-sm table-borderless table-striped">
                    {% for criterion in la_level.criterias %}
                        <tr>
                            {{ la_level.getMinimalCriteriaText(criterion.fields, 'class="pt-0 pb-2"')|raw }}
                        </tr>
                    {% endfor %}
                </table>
TWIG, ['la_level' => $la_level]);

            // language=Twig
            $actions_list = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <table class="table table-sm table-borderless table-striped">
                    {% for action in la_level.actions %}
                        <tr>
                            {{ la_level.getMinimalActionText(action.fields, 'class="pt-0 pb-2"')|raw }}
                        </tr>
                    {% endfor %}
                </table>
TWIG, ['la_level' => $la_level]);


            $entries[] = [
                'itemtype' => static::class,
                'id'       => $la_level->getID(),
                'name'     => $la_level->getLink(),
                'execution_time' => $execution_time,
                'is_active' => Dropdown::getYesNo($la_level->fields['is_active']),
                'criteria' => $criteria_list,
                'actions' => $actions_list,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'execution_time' => __('Execution'),
                'is_active' => __('Active'),
                'criteria' => _n('Criterion', 'Criteria', Session::getPluralNumber()),
                'actions' => _n('Action', 'Actions', Session::getPluralNumber()),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'criteria' => 'raw_html',
                'actions' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);

        /**
         * Remove the export action
         * A levelAgreementLevel can not be exported
         */
        unset($actions[Rule::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'export']);

        return $actions;
    }
}
