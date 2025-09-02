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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Inventory\Request;

use function Safe\file_get_contents;

/// Import rules collection class
class RuleImportAssetCollection extends RuleCollection
{
    // From RuleCollection
    public $stop_on_first_match = true;
    public static $rightname           = 'rule_import';
    public $menu_option         = 'linkcomputer';

    public function defineTabs($options = [])
    {
        $ong = parent::defineTabs();

        $this->addStandardTab(self::class, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$withtemplate) {
            switch ($item::class) {
                case self::class:
                    $ong    = [];
                    $types = $CFG_GLPI['ruleimportasset_types'];
                    foreach ($types as $type) {
                        if (class_exists($type)) {
                            $ong[$type] = $type::getTypeName(Session::getPluralNumber());
                        }
                    }
                    $ong['_global'] = self::createTabEntry(__('Global'));
                    return $ong;
            }
        }
        return '';
    }

    public function getTitle()
    {
        return __('Rules for import and link equipments');
    }

    public function collectionFilter($criteria, $options = [])
    {
        // current tab
        $active_tab = $options['_glpi_tab'] ?? Session::getActiveTab($this->getType());
        $current_tab = str_replace(self::class . '$', '', $active_tab);
        $tabs = $this->getTabNameForItem($this);

        if (!isset($tabs[$current_tab])) {
            return $criteria;
        }

        $criteria['LEFT JOIN']['glpi_rulecriterias AS crit'] = [
            'ON'  => [
                'crit'         => 'rules_id',
                'glpi_rules'   => 'id',
            ],
        ];
        $criteria['GROUPBY'] = ['glpi_rules.id'];

        if ($current_tab != '_global') {
            $where = [
                'crit.criteria'   => 'itemtype',
                'crit.pattern'    => getSingular($current_tab),
            ];
            $criteria['WHERE']  += $where;
        } else {
            if (!is_array($criteria['SELECT'])) {
                $criteria['SELECT'] = [$criteria['SELECT']];
            }
            $criteria['SELECT'][] = QueryFunction::count(
                expression: QueryFunction::if(
                    condition: ['crit.criteria' => 'itemtype'],
                    true_expression: QueryFunction::if(
                        condition: ['crit.pattern' => array_keys($tabs)],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('null')
                    ),
                    false_expression: new QueryExpression('null')
                ),
                alias: 'is_itemtype'
            );
            $where = [];
            $criteria['HAVING'] = ['is_itemtype' => 0];
        }
        return $criteria;
    }

    public function getMainTabLabel()
    {
        return __('All');
    }

    public function prepareInputDataForProcess($input, $params)
    {
        $refused_id = $params['refusedequipments_id'] ?? null;
        if ($refused_id === null) {
            return $input;
        }

        $refused = new RefusedEquipment();
        if ($refused->getFromDB($refused_id) && ($inventory_file = $refused->getInventoryFileName()) !== null) {
            $inventory_request = new Request();
            $contents = file_get_contents($inventory_file);
            $inventory_request
                ->testRules()
                ->handleRequest($contents);

            $inventory = $inventory_request->getInventory();
            $invitem = $inventory->getMainAsset();

            // sanitize input
            if ($input['itemtype'] == 0) {
                unset($input['itemtype']);
            }
            foreach ($input as $key => $value) {
                if (empty($value)) {
                    unset($input[$key]);
                }
            }

            $data = $invitem->getData();
            $rules_input = $invitem->prepareAllRulesInput($data[0]);

            // keep user values if any
            $input += $rules_input;
        } else {
            trigger_error(
                sprintf('Invalid RefusedEquipment "%s" or inventory file missing', $refused_id),
                E_USER_WARNING
            );
            $contents = '';
        }

        return $input;
    }
}
