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
use Safe\Exceptions\PcreException;

use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;

/**
 * Criteria Rule class
 */
class RuleCriteria extends CommonDBChild
{
    // From CommonDBChild
    /**
     * @var class-string<Rule>
     */
    public static $itemtype        = Rule::class;
    public static $items_id        = 'rules_id';
    public $dohistory              = true;
    public $auto_message_on_action = false;



    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @param class-string<Rule> $rule_type
     *
     * @return void
     **/
    public function __construct($rule_type = 'Rule')
    {
        static::$itemtype = $rule_type;
    }

    public function post_getFromDB()
    {
        // Get correct itemtype if defult one is used
        if (static::$itemtype === 'Rule') {
            $rule = new Rule();
            if ($rule->getFromDB($this->fields['rules_id'])) {
                static::$itemtype = $rule->fields['sub_type'];
            }
        }
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Criterion', 'Criteria', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-list-check";
    }

    protected function computeFriendlyName()
    {
        if ($rule = getItemForItemtype(static::$itemtype)) {
            $criteria_row = $rule->getMinimalCriteriaText($this->fields);
            $criteria_text = trim(preg_replace(['/<td[^>]*>/', '/<\/td>/'], [' ', ''], $criteria_row));
            return $criteria_text;
        }
        return '';
    }

    public function post_addItem()
    {
        parent::post_addItem();
        if (
            isset($this->input['rules_id'])
            && ($realrule = Rule::getRuleObjectByID($this->input['rules_id']))
        ) {
            $realrule->update(['id'       => $this->input['rules_id'],
                'date_mod' => $_SESSION['glpi_currenttime'],
            ]);
        }
    }

    public function post_purgeItem()
    {
        parent::post_purgeItem();
        if (
            isset($this->fields['rules_id'])
            && ($realrule = Rule::getRuleObjectByID($this->fields['rules_id']))
        ) {
            $realrule->update(['id'       => $this->fields['rules_id'],
                'date_mod' => $_SESSION['glpi_currenttime'],
            ]);
        }
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['criteria'])) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'criteria',
            'name'               => __('Name'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['rules_id'],
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'condition',
            'name'               => __('Condition'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['rules_id', 'criteria'],
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'pattern',
            'name'               => __('Reason'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['rules_id', 'criteria', 'condition'],
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'criteria':
                $generic_rule = new Rule();
                if (
                    !empty($values['rules_id'])
                    && $generic_rule->getFromDB($values['rules_id'])
                ) {
                    $rule = getItemForItemtype($generic_rule->fields["sub_type"]);
                    if ($rule instanceof Rule) {
                        return htmlescape($rule->getCriteriaName($values[$field]));
                    }
                }
                break;

            case 'condition':
                $generic_rule = new Rule();
                if (
                    !empty($values['rules_id'])
                    && $generic_rule->getFromDB($values['rules_id'])
                ) {
                    $criterion = '';
                    if (isset($values['criteria']) && !empty($values['criteria'])) {
                        $criterion = $values['criteria'];
                    }
                    return htmlescape(self::getConditionByID($values[$field], $generic_rule->fields["sub_type"], $criterion));
                }
                break;

            case 'pattern':
                if (!isset($values["criteria"]) || !isset($values["condition"])) {
                    return htmlescape(NOT_AVAILABLE);
                }
                $generic_rule = new Rule();
                if (
                    isset($values['rules_id'])
                    && !empty($values['rules_id'])
                    && $generic_rule->getFromDB($values['rules_id'])
                ) {
                    $rule = getItemForItemtype($generic_rule->fields["sub_type"]);
                    if ($rule instanceof Rule) {
                        return htmlescape($rule->getCriteriaDisplayPattern(
                            $values["criteria"],
                            $values["condition"],
                            $values[$field]
                        ));
                    }
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
            case 'criteria':
                $generic_rule = new Rule();
                if (
                    isset($values['rules_id'])
                    && !empty($values['rules_id'])
                    && $generic_rule->getFromDB($values['rules_id'])
                ) {
                    $rule = getItemForItemtype($generic_rule->fields["sub_type"]);
                    if ($rule instanceof Rule) {
                        $options['value'] = $values[$field];
                        $options['name']  = $name;
                        return $rule->dropdownCriteria($options);
                    }
                }
                break;

            case 'condition':
                $generic_rule = new Rule();
                if (
                    isset($values['rules_id'])
                    && !empty($values['rules_id'])
                    && $generic_rule->getFromDB($values['rules_id'])
                ) {
                    $rule = getItemForItemtype($generic_rule->fields["sub_type"]);
                    if ($rule instanceof Rule) {
                        if (isset($values['criteria']) && !empty($values['criteria'])) {
                            $options['criterion'] = $values['criteria'];
                        }
                        $options['value'] = $values[$field];
                        $options['name']  = $name;
                        return $rule->dropdownConditions($options);
                    }
                }
                break;

            case 'pattern':
                if (!isset($values["criteria"]) || !isset($values["condition"])) {
                    return NOT_AVAILABLE;
                }
                $generic_rule = new Rule();
                if (
                    isset($values['rules_id'])
                    && !empty($values['rules_id'])
                    && $generic_rule->getFromDB($values['rules_id'])
                ) {
                    $rule = getItemForItemtype($generic_rule->fields["sub_type"]);
                    if ($rule instanceof Rule) {
                        /// TODO : manage display param to this function : need to send ot to all under functions
                        $rule->displayCriteriaSelectPattern(
                            $name,
                            $values["criteria"],
                            $values["condition"],
                            $values[$field]
                        );
                    }
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Get all criteria for a given rule
     *
     * @param integer $rules_id the rule ID
     *
     * @return array of RuleCriteria objects
     **/
    public function getRuleCriterias($rules_id)
    {
        global $DB;

        $rules_list = [];
        $params = [
            'FROM'  => static::getTable(),
            'WHERE' => [static::$items_id => $rules_id],
            'ORDER' => 'id',
        ];
        foreach ($DB->request($params) as $rule) {
            $tmp          = new self();
            $tmp->fields  = $rule;
            $rules_list[] = $tmp;
        }
        return $rules_list;
    }

    /**
     * Try to match a defined rule
     *
     * @param RuleCriteria &$criterion         RuleCriteria object
     * @param ?string      $field              the field to match
     * @param array        &$criterias_results
     * @param array        &$regex_result
     *
     * @return boolean
     **/
    public static function match(RuleCriteria &$criterion, $field, &$criterias_results, &$regex_result)
    {
        $field ??= '';

        $condition = $criterion->fields['condition'];
        $pattern   = $criterion->fields['pattern'];
        $criteria  = $criterion->fields['criteria'];
        //If pattern is wildcard, don't check the rule and return true
        //or if the condition is "already present in GLPI" : will be processed later
        if (
            ($pattern == Rule::RULE_WILDCARD)
            || ($condition == Rule::PATTERN_FIND)
        ) {
            return true;
        }

        $pattern_raw = $pattern;
        $pattern = trim($pattern);

        switch ($condition) {
            case Rule::PATTERN_EXISTS:
                return (!empty($field));

            case Rule::PATTERN_DOES_NOT_EXISTS:
                return (empty($field));

            case Rule::PATTERN_IS:
                if (is_array($field)) {
                    // Special case (used only by UNIQUE_PROFILE, for now)
                    // $pattern is an ID
                    if (in_array($pattern, $field)) {
                        $criterias_results[$criteria] = $pattern_raw;
                        return true;
                    }
                } else {
                    //Perform comparison with fields in lower case
                    $field                        = Toolbox::strtolower($field);
                    $pattern                      = Toolbox::strtolower($pattern);
                    if ($field == $pattern) {
                        $criterias_results[$criteria] = $pattern_raw;
                        return true;
                    }
                }
                return false;

            case Rule::PATTERN_IS_NOT:
                //Perform comparison with fields in lower case
                $field   = Toolbox::strtolower($field);
                $pattern = Toolbox::strtolower($pattern);
                if ($field != $pattern) {
                    $criterias_results[$criteria] = $pattern_raw;
                    return true;
                }
                return false;

            case Rule::PATTERN_UNDER:
                $table  = getTableNameForForeignKeyField($criteria);
                $values = getSonsOf($table, $pattern);
                if (isset($values[$field])) {
                    return true;
                }
                return false;

            case Rule::PATTERN_NOT_UNDER:
                $table  = getTableNameForForeignKeyField($criteria);
                $values = getSonsOf($table, $pattern);
                if (isset($values[$field])) {
                    return false;
                }
                return true;

            case Rule::PATTERN_END:
                if (empty($pattern) || empty($field)) {
                    return false;
                }

                if (str_ends_with(mb_strtolower($field), mb_strtolower($pattern))) {
                    $criterias_results[$criteria] = $pattern_raw;
                    return true;
                }
                return false;

            case Rule::PATTERN_BEGIN:
                if (empty($pattern) || empty($field)) {
                    return false;
                }
                $value = mb_stripos($field, $pattern, 0, 'UTF-8');
                if (($value !== false) && ($value == 0)) {
                    $criterias_results[$criteria] = $pattern_raw;
                    return true;
                }
                return false;

            case Rule::PATTERN_CONTAIN:
                if (empty($pattern) || empty($field)) {
                    return false;
                }
                $value = mb_stripos($field, $pattern, 0, 'UTF-8');
                if ($value !== false) {
                    $criterias_results[$criteria] = $pattern_raw;
                    return true;
                }
                return false;

            case Rule::PATTERN_NOT_CONTAIN:
                if (empty($pattern)) {
                    return false;
                }
                $value = mb_stripos($field, $pattern, 0, 'UTF-8');
                if ($value === false) {
                    $criterias_results[$criteria] = $pattern_raw;
                    return true;
                }
                return false;

            case Rule::REGEX_MATCH:
                $results = [];
                try {
                    $match_result = @preg_match_all($pattern . "si", $field, $results);
                    if ($match_result > 0) {
                        // Drop $result[0] : complete match result
                        array_shift($results);
                        // And add to $regex_result array
                        $res = [];
                        foreach ($results as $data) {
                            foreach ($data as $val) {
                                $res[] = $val;
                            }
                        }
                        $regex_result[] = $res;
                        $criterias_results[$criteria] = $pattern_raw;
                        return true;
                    }
                } catch (PcreException $e) {
                    trigger_error(
                        sprintf('Invalid regular expression `%s`.', $pattern),
                        E_USER_WARNING
                    );
                }
                return false;

            case Rule::REGEX_NOT_MATCH:
                try {
                    $match_result = @preg_match($pattern . "si", $field);
                    if ($match_result === 0) {
                        $criterias_results[$criteria] = $pattern_raw;
                        return true;
                    }
                } catch (PcreException $e) {
                    trigger_error(
                        sprintf('Invalid regular expression `%s`.', $pattern),
                        E_USER_WARNING
                    );
                }
                return false;

            case Rule::PATTERN_FIND:
            case Rule::PATTERN_IS_EMPTY:
                // Global criteria will be evaluated later
                return true;

            case Rule::PATTERN_CIDR:
            case Rule::PATTERN_NOT_CIDR:
                $exploded = explode('/', $pattern);
                $subnet   = ip2long($exploded[0]);
                $bits     = (int) ($exploded[1] ?? 0);
                $mask     = -1 << (32 - $bits);
                $subnet  &= $mask; // nb: in case the supplied subnet wasn't correctly aligned

                if (is_array($field)) {
                    foreach ($field as $ip) {
                        if ($ip != '') {
                            $ip = ip2long($ip);
                            if (($ip & $mask) == $subnet) {
                                return $condition == Rule::PATTERN_CIDR;
                            }
                        }
                    }
                } else {
                    if ($field != '') {
                        $ip = ip2long($field);
                        if (
                            $condition == Rule::PATTERN_CIDR && ($ip & $mask) == $subnet
                            || $condition == Rule::PATTERN_NOT_CIDR && ($ip & $mask) != $subnet
                        ) {
                            return true;
                        }
                    }
                }
                break;

            case Rule::PATTERN_DATE_IS_NOT_EQUAL:
            case Rule::PATTERN_DATE_IS_EQUAL:
                $target_date = Html::computeGenericDateTimeSearch($pattern);

                if (
                    $target_date != $pattern
                    && !str_contains("MINUTE", $pattern)
                    && !str_contains("HOUR", $pattern)
                ) {
                    // We are using a dynamic date with a precision of at least
                    // one day (e.g. 2 days ago).
                    // In this case we must compare using date instead of datetime
                    $field = substr($field, 0, 10);
                    $target_date = substr($target_date, 0, 10);
                }

                return $condition == Rule::PATTERN_DATE_IS_EQUAL
                    ? $field == $target_date
                    : $field != $target_date;

            case Rule::PATTERN_DATE_IS_BEFORE:
                return $field < Html::computeGenericDateTimeSearch($pattern);

            case Rule::PATTERN_DATE_IS_AFTER:
                return $field > Html::computeGenericDateTimeSearch($pattern);
        }
        return false;
    }

    /**
     * Return the condition label by giving his ID
     *
     * @param integer $ID        condition's ID
     * @param string  $itemtype  itemtype
     * @param string  $criterion (default '')
     *
     * @return string condition's label
     **/
    public static function getConditionByID($ID, $itemtype, $criterion = '')
    {
        $conditions = self::getConditions($itemtype, $criterion);
        return $conditions[$ID] ?? "";
    }

    /**
     * @param class-string<Rule> $itemtype  itemtype
     * @param string $criterion (default '')
     *
     * @return array<int, string> array of criteria
     **/
    public static function getConditions($itemtype, $criterion = '')
    {
        $criteria =  [
            Rule::PATTERN_IS                => __('is'),
            Rule::PATTERN_IS_NOT            => __('is not'),
            Rule::PATTERN_CONTAIN           => __('contains'),
            Rule::PATTERN_NOT_CONTAIN       => __('does not contain'),
            Rule::PATTERN_BEGIN             => __('starting with'),
            Rule::PATTERN_END               => __('finished by'),
            Rule::REGEX_MATCH               => __('regular expression matches'),
            Rule::REGEX_NOT_MATCH           => __('regular expression does not match'),
            Rule::PATTERN_EXISTS            => __('exists'),
            Rule::PATTERN_DOES_NOT_EXISTS   => __('does not exist'),
        ];

        if (in_array($criterion, ['ip', 'subnet'])) {
            $criteria += [
                Rule::PATTERN_CIDR     => __('is CIDR'),
                Rule::PATTERN_NOT_CIDR => __('is not CIDR'),
            ];
        }

        $extra_criteria = call_user_func([$itemtype, 'addMoreCriteria'], $criterion);

        foreach ($extra_criteria as $key => $value) {
            $criteria[$key] = $value;
        }

        if ($item = getItemForItemtype($itemtype)) {
            $crit = $item->getCriteria($criterion);

            if (isset($crit['type']) && ($crit['type'] == 'dropdown')) {
                $crititemtype = getItemTypeForTable($crit['table']);

                if (
                    ($item = getItemForItemtype($crititemtype))
                    && $item instanceof CommonTreeDropdown
                ) {
                    $criteria[Rule::PATTERN_UNDER]     = __('under');
                    $criteria[Rule::PATTERN_NOT_UNDER] = __('not under');
                }
            } elseif (isset($crit['type']) && in_array($crit['type'], ['date', 'datetime'])) {
                $criteria[Rule::PATTERN_DATE_IS_BEFORE]    = __('before');
                $criteria[Rule::PATTERN_DATE_IS_AFTER]     = __('after');
                $criteria[Rule::PATTERN_DATE_IS_EQUAL]     = __('is');
                $criteria[Rule::PATTERN_DATE_IS_NOT_EQUAL] = __('is not');
                unset($criteria[Rule::PATTERN_IS], $criteria[Rule::PATTERN_IS_NOT]);
            }
        }

        return $criteria;
    }

    /**
     * Display a dropdown with all the criteria
     *
     * @param string $itemtype
     * @param array  $params
     **/
    public static function dropdownConditions($itemtype, $params = [])
    {
        $p['name']             = 'condition';
        $p['criterion']        = '';
        $p['allow_conditions'] = [];
        $p['value']            = '';
        $p['display']          = true;

        foreach ($params as $key => $value) {
            $p[$key] = $value;
        }
        $elements = [];
        foreach (self::getConditions($itemtype, $p['criterion']) as $pattern => $label) {
            if (
                empty($p['allow_conditions'])
                || (!empty($p['allow_conditions']) && in_array($pattern, $p['allow_conditions']))
            ) {
                $elements[$pattern] = $label;
            }
        }
        return Dropdown::showFromArray($p['name'], $elements, ['value' => $p['value']]);
    }

    public function showForm($ID, array $options = [])
    {
        // Yllen: you always have parent for criteria
        $rule = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $options[static::$items_id] = $rule->getField('id');

            //force itemtype of parent
            static::$itemtype = get_class($rule);

            $this->check(-1, CREATE, $options);
        }

        TemplateRenderer::getInstance()->display('pages/admin/rules/criteria.html.twig', [
            'rule' => $rule,
            'rules_id_field' => static::$items_id,
            'item' => $this,
            'rand' => mt_rand(),
        ]);

        return true;
    }
}
