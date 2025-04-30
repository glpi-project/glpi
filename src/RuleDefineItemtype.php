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

use Glpi\Application\View\TemplateRenderer;

class RuleDefineItemtype extends Rule
{
    public const PATTERN_ENTITY_RESTRICT       = 202;
    public const PATTERN_NETWORK_PORT_RESTRICT = 203;
    public const PATTERN_ONLY_CRITERIA_RULE    = 204;

    public $restrict_matching = Rule::AND_MATCHING;

    public static $rightname         = 'rule_import';

    public function getTitle()
    {
        $col = new RuleDefineItemtypeCollection();
        return $col->getTitle();
    }

    public function getCriterias()
    {
        static $criteria = [];

        if (count($criteria)) {
            return $criteria;
        }

        //we rely on RuleImportAsset criteria
        $ria = new RuleImportAsset();
        $criteria = $ria->getCriterias();

        $criteria['itemtype']['type'] = 'dropdown_defineitemtype_itemtype';

        return $criteria;
    }

    public function getActions()
    {
        $actions = [
            '_assign'   => [
                'name'   => __('Assign itemtype'),
                'type'   => 'inventory_itemtype',
            ],
        ];
        return $actions;
    }

    public function displayAdditionRuleActionValue($value)
    {
        $values = self::getItemTypesForRules();
        return $values[$value] ?? '';
    }

    /**
     * Add more criteria
     *
     * @param string $criterion
     * @return array
     */
    public static function addMoreCriteria($criterion = '')
    {
        return \RuleImportAsset::addMoreCriteria($criterion);
    }

    public function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern)
    {
        if (
            $condition == self::PATTERN_IS_EMPTY
            || $condition == self::PATTERN_ENTITY_RESTRICT
            || $condition == self::PATTERN_NETWORK_PORT_RESTRICT
            || $condition == self::PATTERN_ONLY_CRITERIA_RULE
        ) {
            return __('Yes');
        }
        if ($condition == self::PATTERN_IS || $condition == self::PATTERN_IS_NOT) {
            $crit = $this->getCriteria($ID);
            if (
                isset($crit['type'])
                 && $crit['type'] == 'dropdown_inventory_itemtype'
            ) {
                $array = static::getItemTypesForRules();
                return $array[$pattern];
            }
        }
        return false;
    }

    public function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false)
    {
        if ($test) {
            return false;
        }

        switch ($condition) {
            case self::PATTERN_ENTITY_RESTRICT:
            case self::PATTERN_NETWORK_PORT_RESTRICT:
                return true;

            case Rule::PATTERN_FIND:
            case Rule::PATTERN_IS_EMPTY:
                Dropdown::showYesNo($name, 0, 0);
                return true;

            case Rule::PATTERN_EXISTS:
            case Rule::PATTERN_DOES_NOT_EXISTS:
                Dropdown::showYesNo($name, 1, 0);
                return true;
        }

        return false;
    }

    public function displayAdditionalRuleAction(array $action, $value = '')
    {
        switch ($action['type']) {
            case 'inventory_itemtype':
                Dropdown::showFromArray('value', self::getItemTypesForRules());
                return true;
        }
        return false;
    }

    public function findWithGlobalCriteria($input)
    {
        //FIXME: I do not understand why I need to pass here...
        return true;
    }

    public function executeActions($output, $params, array $input = [])
    {
        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action->fields['field'] == '_assign') {
                    $output['new_itemtype'] = $action->fields['value'];
                    return $output;
                }
            }
        }
        return $output;
    }

    public function showSpecificCriteriasForPreview($fields)
    {
        $twig_params = [
            'entity_as_criterion' => false,
            'fields'              => $fields,
            'type_match'          => $this->fields['match'] === Rule::AND_MATCHING ? __('AND') : __('OR'),
        ];
        foreach ($this->criterias as $criterion) {
            if ($criterion->fields['criteria'] === 'entities_id') {
                $twig_params['entity_as_criterion'] = true;
                break;
            }
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if not entity_as_criterion %}
                {{ fields.htmlField('', type_match|e, '', {
                    no_label: true,
                    field_class: 'col-2',
                    input_class: 'col-12'
                }) }}
                {{ fields.dropdownField('Entity', 'entities_id', 0, 'Entity'|itemtype_name, {
                    field_class: 'col-10',
                    label_class: 'col-5',
                    input_class: 'col-7'
                }) }}
            {% endif %}
            {{ fields.htmlField('', loop.first ? '' : type_match|e, '', {
                no_label: true,
                field_class: 'col-2',
                input_class: 'col-12'
            }) }}
            {{ fields.dropdownField('RefusedEquipment', 'refusedequipments_id', fields['refusedequipments_id']|default(null), 'RefusedEquipment'|itemtype_name, {
                field_class: 'col-10',
                label_class: 'col-5',
                input_class: 'col-7'
            }) }}
TWIG, $twig_params);
    }

    /**
     * Get itemtypes
     *
     * @global array $CFG_GLPI
     * @return array
     */
    public static function getItemTypesForRules()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $types = [];
        foreach ($CFG_GLPI['inventory_types'] as $itemtype) {
            if (class_exists($itemtype)) {
                /** @var CommonDBTM $item */
                $item = new $itemtype();
                $types[$itemtype] = $item->getTypeName(1);
            }
        }
        ksort($types);
        return $types;
    }

    /**
     * Get criteria related to network ports
     *
     * @return array
     */
    public function getNetportCriteria(): array
    {
        return [
            'mac',
            'ip',
            'ifnumber',
            'ifdescr',
        ];
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) { //@phpstan-ignore-line array is an array in phpdoc **only**
            $values = [$field => $values];
        }
        switch ($field) {
            case 'id':
                $rule = new static();
                $rule->getFromDB($values['id']);
                return $rule->getLink();
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        switch ($field) {
            case 'id':
                $options['display'] = false;
                return Rule::dropdown(
                    [
                        'sub_type' => static::class,
                        'display' => false,
                        'name' => $name,
                    ] + $options
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }
}
