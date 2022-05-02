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

namespace Glpi\Inventory\Asset;

use CommonDBTM;
use Printer as GPrinter;
use PrinterLog;
use PrinterModel;
use PrinterType;
use RuleDictionnaryPrinterCollection;
use RuleImportAssetCollection;
use Toolbox;

class Printer extends NetworkEquipment
{
    private $counters;

    public function __construct(CommonDBTM $item, $data)
    {
        $this->extra_data['pagecounters'] = null;
        parent::__construct($item, $data);
    }

    protected function getModelsFieldName(): string
    {
        return PrinterModel::getForeignKeyField();
    }

    protected function getTypesFieldName(): string
    {
        return PrinterType::getForeignKeyField();
    }

    public function prepare(): array
    {
        parent::prepare();

        if (!property_exists($this->raw_data->content ?? new \stdClass(), 'network_device')) {
            $autoupdatesystems_id = $this->data[0]->autoupdatesystems_id;
            $this->data = [];
            foreach ($this->raw_data as $val) {
                $val->autoupdatesystems_id = $autoupdatesystems_id;
                $val->last_inventory_update = $_SESSION['glpi_currenttime'];
                $this->data[] = $val;
            }
        }

        $rulecollection = new RuleDictionnaryPrinterCollection();

        $mapping_pcounter = [
            'total'        => 'total_pages',
            'black'        => 'bw_pages',
            'color'        => 'color_pages',
            'duplex'       => 'rv_pages', //keep first, rectoverso is the standard and should be used if present
            'rectoverso'   => 'rv_pages',
            'scanned'      => 'scanned',
            'printtotal'   => 'prints',
            'printblack'   => 'bw_prints',
            'printcolor'   => 'color_prints',
            'copytotal'    => 'copies',
            'copyblack'    => 'bw_copies',
            'copycolor'    => 'color_copies',
            'faxtotal'     => 'faxed',
        ];

        foreach ($this->data as $k => &$val) {
            if (property_exists($val, 'port') && strstr($val->port, "USB")) {
                $val->have_usb = 1;
            } else {
                $val->have_usb = 0;
            }
            unset($val->port);

           //inventoried printers certainly have ethernet
            if (property_exists($this->raw_data->content ?? new \stdClass(), 'network_device')) {
                $val->have_ethernet = 1;
            }

           // Hack for USB Printer serial
            if (
                property_exists($val, 'serial')
                && preg_match('/\/$/', $val->serial)
            ) {
                $val->serial = preg_replace('/\/$/', '', $val->serial);
            }

            if (property_exists($val, 'ram')) {
                $val->memory_size = $val->ram;
                unset($val->ram);
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
                }

                if (isset($this->extra_data['pagecounters'])) {
                    $pcounter = (object)$this->extra_data['pagecounters'];
                    foreach ($mapping_pcounter as $origin => $dest) {
                        if (property_exists($pcounter, $origin)) {
                             $pcounter->$dest = $pcounter->$origin;
                        }

                        if (property_exists($pcounter, 'total_pages')) {
                            $val->last_pages_counter = $pcounter->total_pages;
                        }
                        $this->counters = $pcounter;
                    }
                }
            } else {
                unset($this->data[$k]);
            }
        }

        return $this->data;
    }

    public function handle()
    {
        if ($this->item->getType() != GPrinter::getType()) {
            return $this->handleConnectedPrinter();
        }

        parent::handle();
        $this->handleMetrics();
    }

    /**
     * Handle a printer connecter to a computer
     *
     * @return void
     */
    protected function handleConnectedPrinter()
    {
        global $DB;

        $rule = new RuleImportAssetCollection();
        $printer = new GPrinter();
        $printers = [];
        $entities_id = $this->entities_id;

        $lclass = null;
        if (class_exists($this->item->getType() . '_Item')) {
            $lclass = $this->item->getType() . '_Item';
        } else if (class_exists('Item_' . $this->item->getType())) {
            $lclass = 'Item_' . $this->item->getType();
        } else {
            throw new \RuntimeException('Unable to find linked item object name for ' . $this->item->getType());
        }
        $link_item = new $lclass();

        foreach ($this->data as $key => $val) {
            $input = [
                'itemtype'     => "Printer",
                'name'         => $val->name,
                'serial'       => $val->serial ?? '',
                'is_dynamic'   => 1
            ];
            $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);
            if (isset($data['found_inventories'])) {
                $items_id = null;
                $itemtype = 'Printer';
                if ($data['found_inventories'][0] == 0) {
                   // add printer
                    $val->entities_id = $entities_id;
                    $val->is_dynamic = 1;
                    $items_id = $printer->add(Toolbox::addslashes_deep((array)$val));
                } else {
                    $items_id = $data['found_inventories'][0];
                }

                $printers[] = $items_id;
                $rulesmatched = new \RuleMatchedLog();
                $agents_id = $this->agent->fields['id'];
                if (empty($agents_id)) {
                    $agents_id = 0;
                }
                $inputrulelog = [
                    'date'      => date('Y-m-d H:i:s'),
                    'rules_id'  => $data['rules_id'],
                    'items_id'  => $items_id,
                    'itemtype'  => $itemtype,
                    'agents_id' => $agents_id,
                    'method'    => 'inventory'
                ];
                $rulesmatched->add($inputrulelog, [], false);
                $rulesmatched->cleanOlddata(end($printers), 'Printer');
            }
        }
        $db_printers = [];
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_printers.id',
                'glpi_computers_items.id AS link_id'
            ],
            'FROM'      => 'glpi_computers_items',
            'LEFT JOIN' => [
                'glpi_printers' => [
                    'FKEY' => [
                        'glpi_printers'         => 'id',
                        'glpi_computers_items'  => 'items_id'
                    ]
                ]
            ],
            'WHERE'     => [
                'itemtype'                          => 'Printer',
                'computers_id'                      => $this->item->fields['id'],
                'entities_id'                       => $entities_id,
                'glpi_computers_items.is_dynamic'   => 1,
                'glpi_printers.is_global'           => 0
            ]
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
            foreach ($db_printers as $idtmp => $data) {
                $link_item->delete(['id' => $idtmp], 1);
            }
        }

        foreach ($printers as $printers_id) {
            $input = [
                'entities_id'  => $entities_id,
                'computers_id' => $this->item->fields['id'],
                'itemtype'     => 'Printer',
                'items_id'     => $printers_id,
                'is_dynamic'   => 1
            ];
            $this->addOrMoveItem($input);
        }
    }

    /**
     * Get printer counters
     *
     * @return \stdClass
     */
    public function getCounters(): \stdClass
    {
        return $this->counters;
    }

    /**
     * Handle printer metrics
     *
     * @return void
     */
    public function handleMetrics()
    {
        if ($this->counters === null) {
            return;
        }

        $unicity_input = [
            'printers_id' => $this->item->fields['id'],
            'date'        => date('Y-m-d', strtotime($_SESSION['glpi_currenttime'])),
        ];
        $input = array_merge((array)$this->counters, $unicity_input);

        $metrics = new PrinterLog();
        if ($metrics->getFromDBByCrit($unicity_input)) {
            $input['id'] = $metrics->fields['id'];
            $metrics->update($input, false);
        } else {
            $metrics->add($input, [], false);
        }
    }
}
