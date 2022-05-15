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

class RuleDictionnaryPrinterCollection extends RuleCollection
{
   // From RuleCollection

    public $stop_on_first_match = true;
    public $can_replay_rules    = true;
    public $menu_type           = 'dictionnary';
    public $menu_option         = 'printer';

    public static $rightname           = 'rule_dictionnary_printer';

    /**
     * @see RuleCollection::getTitle()
     **/
    public function getTitle()
    {
        return __('Dictionnary of printers');
    }


    /**
     * @see RuleCollection::cleanTestOutputCriterias()
     **/
    public function cleanTestOutputCriterias(array $output)
    {

       //If output array contains keys begining with _ : drop it
        foreach ($output as $criteria => $value) {
            if (($criteria[0] == '_') && ($criteria != '_ignore_import')) {
                unset($output[$criteria]);
            }
        }
        return $output;
    }


    public function replayRulesOnExistingDB($offset = 0, $maxtime = 0, $items = [], $params = [])
    {
        global $DB;

        if (isCommandLine()) {
            printf(__('Replay rules on existing database started on %s') . "\n", date("r"));
        }
        $nb = 0;
        $i  = $offset;

       //Select all the differents software
        $criteria = [
            'SELECT' => [
                'glpi_printers.name',
                'glpi_manufacturers.name AS manufacturer',
                'glpi_printers.manufacturers_id AS manufacturers_id',
                'glpi_printers.comment AS comment'
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_printers',
            'LEFT JOIN' => [
                'glpi_manufacturers' => [
                    'ON'  => [
                        'glpi_manufacturers' => 'id',
                        'glpi_printers'      => 'manufacturers_id'
                    ]
                ]
            ],
            'WHERE'     => [
            // Do not replay on trashbin and templates
                'glpi_printers.is_deleted'    => 0,
                'glpi_printers.is_template'   => 0
            ]
        ];

        if ($offset) {
            $criteria['START'] = (int)$offset;
            $criteria['LIMIT'] = 999999999;
        }

        $iterator = $DB->request($criteria);
        $nb   = count($iterator) + $offset;
        $step = (($nb > 1000) ? 50 : (($nb > 20) ? floor(count($iterator) / 20) : 1));

        foreach ($iterator as $input) {
            if (!($i % $step)) {
                if (isCommandLine()) {
                    //TRANS: %1$s is a date, %2$s is a row, %3$s is total row, %4$s is memory
                    printf(
                        __('%1$s - replay rules on existing database: %2$s/%3$s (%4$s Mio)') . "\n",
                        date("H:i:s"),
                        $i,
                        $nb,
                        round(memory_get_usage() / (1024 * 1024), 2)
                    );
                } else {
                    Html::changeProgressBarPosition($i, $nb, "$i / $nb");
                }
            }

           //Replay printer dictionnary rules
            $res_rule = $this->processAllRules($input, [], []);

            foreach (['manufacturer', 'is_global', 'name'] as $attr) {
                if (isset($res_rule[$attr]) && ($res_rule[$attr] == '')) {
                    unset($res_rule[$attr]);
                }
            }

           //If the software's name or version has changed
            if (self::somethingHasChanged($res_rule, $input)) {
                $IDs = [];
               //Find all the printers in the database with the same name and manufacturer
                $print_iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_printers',
                    'WHERE'  => [
                        'name'               => $input['name'],
                        'manufacturers_id'   => $input['manufacturers_id']
                    ]
                ]);

                if (count($print_iterator)) {
                     //Store all the printer's IDs in an array
                    foreach ($print_iterator as $result) {
                        $IDs[] = $result["id"];
                    }
                     //Replay dictionnary on all the printers
                     $this->replayDictionnaryOnPrintersByID($IDs, $res_rule);
                }
            }
            $i++;

            if ($maxtime) {
                $crt = explode(" ", microtime());
                if ($crt[0] + $crt[1] > $maxtime) {
                    break;
                }
            }
        }

        if (isCommandLine()) {
            printf(__('Replay rules on existing database: %1$s/%2$s') . "\n", $i, $nb);
        } else {
            Html::changeProgressBarPosition($i, $nb, "$i / $nb");
        }

        if (isCommandLine()) {
            printf(__('Replay rules on existing database ended on %s') . "\n", date("r"));
        }

        return (($i == $nb) ? -1 : $i);
    }


    /**
     * @param $res_rule  array
     * @param $input     array
     **/
    public static function somethingHasChanged(array $res_rule, array $input)
    {

        if (
            (isset($res_rule["name"]) && ($res_rule["name"] != $input["name"]))
            || (isset($res_rule["manufacturer"]) && ($res_rule["manufacturer"] != ''))
            || (isset($res_rule['is_global']) && ($res_rule['is_global'] != ''))
        ) {
            return true;
        }
        return false;
    }


    /**
     * Replay dictionnary on several printers
     *
     * @param $IDs       array of printers IDs to replay
     * @param $res_rule  array of rule results
     *
     * @return void
     **/
    public function replayDictionnaryOnPrintersByID(array $IDs, $res_rule = [])
    {
        global $DB;

        $new_printers  = [];
        $delete_ids    = [];

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_printers.id',
                'glpi_printers.name',
                'glpi_printers.entities_id AS entities_id',
                'glpi_printers.is_global AS is_global',
                'glpi_manufacturers.name AS manufacturer'
            ],
            'FROM'      => 'glpi_printers',
            'LEFT JOIN' => [
                'glpi_manufacturers'  => [
                    'FKEY'   => [
                        'glpi_printers'      => 'manufacturers_id',
                        'glpi_manufacturers' => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_printers.is_template'   => 0,
                'glpi_printers.id'            => $IDs
            ]
        ]);

        foreach ($iterator as $printer) {
            //For each printer
            $this->replayDictionnaryOnOnePrinter($new_printers, $res_rule, $printer, $delete_ids);
        }

       //Delete printer if needed
        $this->putOldPrintersInTrash($delete_ids);
    }


    /**
     * @param $IDS array
     */
    public function putOldPrintersInTrash($IDS = [])
    {

        $printer = new Printer();
        foreach ($IDS as $id) {
            $printer->delete(['id' => $id]);
        }
    }


    /**
     * Replay dictionnary on one printer
     *
     * @param &$new_printers   array containing new printers already computed
     * @param $res_rule        array of rule results
     * @param $params          array
     * @param &$printers_ids   array containing replay printer need to be put in trashbin
     **/
    public function replayDictionnaryOnOnePrinter(
        array &$new_printers,
        array $res_rule,
        array $params,
        array &$printers_ids
    ) {
        $p['id']           = 0;
        $p['name']         = '';
        $p['manufacturer'] = '';
        $p['is_global']    = '';
        $p['entity']       = 0;
        foreach ($params as $key => $value) {
            $p[$key] = $value;
        }

        $input["name"]         = $p['name'];
        $input["manufacturer"] = $p['manufacturer'];

        if (empty($res_rule)) {
            $res_rule = $this->processAllRules($input, [], []);
        }

        $printer = new Printer();

       //Printer's name has changed
        if (
            isset($res_rule["name"])
            && ($res_rule["name"] != $p['name'])
        ) {
            $manufacturer = "";

            if (isset($res_rule["manufacturer"])) {
                $manufacturer = addslashes(Dropdown::getDropdownName(
                    "glpi_manufacturers",
                    $res_rule["manufacturer"]
                ));
            } else {
                $manufacturer = addslashes($p['manufacturer']);
            }

           //New printer not already present in this entity
            if (!isset($new_printers[$p['entity']][$res_rule["name"]])) {
               // create new printer or restore it from trashbin
                $new_printer_id = $printer->addOrRestoreFromTrash(
                    $res_rule["name"],
                    $manufacturer,
                    $p['entity']
                );
                $new_printers[$p['entity']][$res_rule["name"]] = $new_printer_id;
            } else {
                $new_printer_id = $new_printers[$p['entity']][$res_rule["name"]];
            }

           // Move direct connections
            $this->moveDirectConnections($p['id'], $new_printer_id);
        } else {
            $new_printer_id  = $p['id'];
            $res_rule["id"]  = $p['id'];

            if (isset($res_rule["manufacturer"])) {
                if ($res_rule["manufacturer"] != '') {
                    $res_rule["manufacturers_id"] = $res_rule["manufacturer"];
                }
                unset($res_rule["manufacturer"]);
            }
            $printer->update($res_rule);
        }

       // Add to printer to deleted list
        if ($new_printer_id != $p['id']) {
            $printers_ids[] = $p['id'];
        }
    }


    /**
     * Move direct connections from old printer to the new one
     *
     * @param $ID                 the old printer's id
     * @param $new_printers_id    the new printer's id
     *
     * @return void
     **/
    public function moveDirectConnections($ID, $new_printers_id)
    {
        $computeritem = new Computer_Item();
       //For each direct connection of this printer
        $connections = getAllDataFromTable(
            'glpi_computers_items',
            [
                'itemtype'  => 'Printer',
                'items_id'  => $ID
            ]
        );
        foreach ($connections as $connection) {
            //Direct connection exists in the target printer ?
            if (
                !countElementsInTable(
                    "glpi_computers_items",
                    ['itemtype'     => 'Printer',
                        'items_id'     => $new_printers_id,
                        'computers_id' => $connection["computers_id"]
                    ]
                )
            ) {
               //Direct connection doesn't exists in the target printer : move it
                $computeritem->update(['id'       => $connection['id'],
                    'items_id' => $new_printers_id
                ]);
            } else {
               //Direct connection already exists in the target printer : delete it
                $computeritem->delete($connection);
            }
        }
    }
}
