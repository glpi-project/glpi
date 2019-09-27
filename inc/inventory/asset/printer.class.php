<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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

   public function __construct(CommonDBTM $item, $data) {
      $this->extra_data['pagecounters'] = null;
      parent::__construct($item, $data);
   }

   protected function getModelsFieldName(): string {
      return PrinterModel::getForeignKeyField();
   }

   protected function getTypesFieldName(): string {
      return PrinterType::getForeignKeyField();
   }

   public function prepare() :array {
      parent::prepare();

      if (!property_exists($this->raw_data->content ?? new \stdClass(), 'network_device')) {
         $val = $this->raw_data[0];
         $val->autoupdatesystems_id = $this->data[0]->autoupdatesystems_id;
         $this->data = [$val];
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

         // Hack for USB Printer serial
         if (property_exists($val, 'serial')
               && preg_match('/\/$/', $val->serial)) {
            $val->serial = preg_replace('/\/$/', '', $val->serial);
         }

         $res_rule = $rulecollection->processAllRules(['name' => $val->name]);
         if ((!isset($res_rule['_ignore_ocs_import']) || $res_rule['_ignore_ocs_import'] != "1")
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

   public function handle() {
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
   protected function handleConnectedPrinter() {
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
      $link_item = new $lclass;

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
               $items_id = $printer->add(Toolbox::addslashes_deep((array)$val), [], $this->withHistory());
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

      while ($data = $iterator->next()) {
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
            $link_item->delete(['id'=>$idtmp], 1);
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
         $link_item->add($input, [], $this->withHistory());
      }
   }

   /**
    * Get printer counters
    *
    * @return \stdClass
    */
   public function getCounters(): \stdClass {
      return $this->counters;
   }

   /**
    * Handle printer metrics
    *
    * @return void
    */
   public function handleMetrics() {
      if ($this->counters === null) {
         return;
      }

      $metrics = new PrinterLog();
      $input = (array)$this->counters;
      $input['printers_id'] = $this->item->fields['id'];
      $metrics->add($input, [], false);
   }
}
