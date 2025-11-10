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

namespace Glpi\Inventory\Asset;

use AutoUpdateSystem;
use CommonDBTM;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Inventory\Conf;
use Printer as GPrinter;
use RuleDictionnaryPrinterCollection;
use RuleImportAssetCollection;
use RuleMatchedLog;
use RuntimeException;

use function Safe\preg_match;
use function Safe\preg_replace;

class Printer extends InventoryAsset
{
    public function prepare(): array
    {
        $rulecollection = new RuleDictionnaryPrinterCollection();

        foreach ($this->data as $k => &$val) {
            //set update system
            $val->autoupdatesystems_id = AutoUpdateSystem::NATIVE_INVENTORY;
            $val->last_inventory_update = $_SESSION["glpi_currenttime"];
            $val->is_deleted = 0;

            if (property_exists($val, 'port') && strstr($val->port, "USB")) {
                $val->have_usb = 1;
            } else {
                $val->have_usb = 0;
            }
            unset($val->port);

            // Hack for USB Printer serial
            if (
                property_exists($val, 'serial')
                && preg_match('/\/$/', $val->serial)
            ) {
                $val->serial = preg_replace('/\/$/', '', $val->serial);
            }

            $res_rule = $rulecollection->processAllRules(['name' => $val->name]);
            if (
                (!isset($res_rule['_ignore_ocs_import']) || $res_rule['_ignore_ocs_import'] != "1")
                && (!isset($res_rule['_ignore_import']) || $res_rule['_ignore_import'] != "1")
            ) {
                if (isset($res_rule['name'])) {
                    $val->name = $res_rule['name'];
                }
                if (isset($res_rule['manufacturer'])) {
                    $val->manufacturers_id = $res_rule['manufacturer'];
                    $known_key = md5('manufacturers_id' . $res_rule['manufacturer']);
                    $this->known_links[$known_key] = $res_rule['manufacturer'];
                }
            } else {
                unset($this->data[$k]);
            }
        }

        return $this->data;
    }

    public function handle()
    {
        global $DB;

        $rule = new RuleImportAssetCollection();
        $printer = new GPrinter();
        $printers = [];
        $entities_id = $this->entities_id;

        $lclass = null;
        if (class_exists($this->item->getType() . '_Item')) {
            $lclass = $this->item->getType() . '_Item';
        } elseif (class_exists('Item_' . $this->item->getType())) {
            $lclass = 'Item_' . $this->item->getType();
        } elseif (in_array($this->item->getType(), Asset_PeripheralAsset::getPeripheralHostItemtypes(), true)) {
            $lclass = Asset_PeripheralAsset::class;
        }

        if (!\is_a($lclass, CommonDBTM::class, true)) {
            throw new RuntimeException('Unable to find linked item object name for ' . $this->item->getType());
        }

        foreach ($this->data as $key => $val) {
            $input = [
                'itemtype'     => GPrinter::class,
                'name'         => $val->name,
                'serial'       => $val->serial ?? '',
                'is_dynamic'   => 1,
            ];
            $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);
            if (isset($data['found_inventories'])) {
                $items_id = null;
                $itemtype = GPrinter::class;
                if ($data['found_inventories'][0] == 0) {
                    // add printer
                    $val->entities_id = $entities_id;
                    $val->is_recursive = $this->is_recursive;
                    $val->is_dynamic = 1;
                    $items_id = $printer->add($this->handleInput($val, $printer));
                } else {
                    $items_id = $data['found_inventories'][0];
                }

                $printers[] = $items_id;
                $rulesmatched = new RuleMatchedLog();
                $agents_id = $this->agent->fields['id'];
                if (empty($agents_id)) {
                    $agents_id = 0;
                }
                $inputrulelog = [
                    'date'      => date('Y-m-d H:i:s'),
                    'rules_id'  => $data['_ruleid'],
                    'items_id'  => $items_id,
                    'itemtype'  => $itemtype,
                    'agents_id' => $agents_id,
                    'method'    => 'inventory',
                ];
                $rulesmatched->add($inputrulelog, [], false);
                $rulesmatched->cleanOlddata(end($printers), 'Printer');
            }
        }
        $db_printers = [];
        $relation_table = Asset_PeripheralAsset::getTable();
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_printers.id',
                $relation_table . '.id AS link_id',
            ],
            'FROM'      => $relation_table,
            'LEFT JOIN' => [
                'glpi_printers' => [
                    'FKEY' => [
                        'glpi_printers' => 'id',
                        $relation_table => 'items_id_peripheral',
                    ],
                ],
            ],
            'WHERE'     => [
                'itemtype_peripheral'           => GPrinter::class,
                'itemtype_asset'                => $this->item::class,
                'items_id_asset'                => $this->item->fields['id'],
                'entities_id'                   => $entities_id,
                $relation_table . '.is_dynamic' => 1,
                'glpi_printers.is_global'       => 0,
            ],
        ]);

        foreach ($iterator as $data) {
            $idtmp = $data['link_id'];
            unset($data['link_id']);
            $db_printers[$idtmp] = $data['id'];
        }
        if (count($db_printers)) {
            // Check all fields from source:
            foreach ($printers as $key => $printers_id) {
                foreach ($db_printers as $keydb => $prints_id) {
                    if ($printers_id == $prints_id) {
                        unset($printers[$key]);
                        unset($db_printers[$keydb]);
                        break;
                    }
                }
            }

            // Delete printers links in DB
            foreach (array_keys($db_printers) as $idtmp) {
                (new $lclass())->delete(['id' => $idtmp], true);
            }
        }

        foreach ($printers as $printers_id) {
            $input = [
                'entities_id'  => $entities_id,
                'itemtype_asset' => $this->item::class,
                'items_id_asset' => $this->item->fields['id'],
                'itemtype_peripheral' => GPrinter::class,
                'items_id_peripheral' => $printers_id,
                'is_dynamic'   => 1,
            ];
            $this->addOrMoveItem($input);
        }
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        return $conf->import_printer == 1 && in_array($this->item::class, $CFG_GLPI['peripheralhost_types']);
    }

    public function getItemtype(): string
    {
        return GPrinter::class;
    }
}
