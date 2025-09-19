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

use Glpi\Inventory\Request;

use function Safe\file_get_contents;

class RuleDefineItemtypeCollection extends RuleCollection
{
    // From RuleCollection
    public $stop_on_first_match = true;
    public static $rightname           = 'rule_import';
    public $menu_option         = 'defineasset';

    public function getTitle()
    {
        return __('Rules to define inventoried itemtype');
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
