<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Transfer extends CommonDBTM {

   // Specific ones
   /// Already transfer item
   var $already_transfer      = array();
   /// Items simulate to move - non recursive item or recursive item not visible in destination entity
   var $needtobe_transfer     = array();
   /// Items simulate to move - recursive item visible in destination entity
   var $noneedtobe_transfer   = array();
   /// Search in need to be transfer items
   var $item_search           = array();
   /// Search in need to be exclude from transfer
   var $item_recurs           = array();
   /// Options used to transfer
   var $options               = array();
   /// Destination entity id
   var $to                    = -1;
   /// type of initial item transfered
   var $inittype              = 0;

   static $rightname = 'transfer';


   /**
    * @see CommonGLPI::defineTabs()
    *
    * @since version 0.85
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);

      return $ong;
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      return $tab;
   }


   /**
    * Transfer items
    *
    *@param $items      items to transfer
    *@param $to         entity destination ID
    *@param $options    options used to transfer
   **/
   function moveItems($items, $to, $options) {
      global $CFG_GLPI;

      // unset mailing
      $CFG_GLPI["use_mailing"] = 0;

      $this->options = array('keep_ticket'         => 0,
                             'keep_networklink'    => 0,
                             'keep_reservation'    => 0,
                             'keep_history'        => 0,
                             'keep_device'         => 0,
                             'keep_infocom'        => 0,

                             'keep_dc_monitor'     => 0,
                             'clean_dc_monitor'    => 0,

                             'keep_dc_phone'       => 0,
                             'clean_dc_phone'      => 0,

                             'keep_dc_peripheral'  => 0,
                             'clean_dc_peripheral' => 0,

                             'keep_dc_printer'     => 0,
                             'clean_dc_printer'    => 0,

                             'keep_supplier'       => 0,
                             'clean_supplier'      => 0,

                             'keep_contact'        => 0,
                             'clean_contact'       => 0,

                             'keep_contract'       => 0,
                             'clean_contract'      => 0,

                             'keep_disk'           => 0,

                             'keep_software'       => 0,
                             'clean_software'      => 0,

                             'keep_document'       => 0,
                             'clean_document'      => 0,

                             'keep_cartridgeitem'  => 0,
                             'clean_cartridgeitem' => 0,
                             'keep_cartridge'      => 0,

                             'keep_consumable'     => 0);

      if ($to >= 0) {
         // Store to
         $this->to = $to;
         // Store options
         if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
               $this->options[$key] = $val;
            }
         }

         // Simulate transfers To know which items need to be transfer
         $this->simulateTransfer($items);

         //Html::printCleanArray($this->needtobe_transfer);

         // Software first (to avoid copy during computer transfer)
         $this->inittype = 'Software';
         if (isset($items['Software']) && count($items['Software'])) {
            foreach ($items['Software'] as $ID) {
               $this->transferItem('Software', $ID, $ID);
            }
         }

         // Computer before all other items
         $this->inittype = 'Computer';
         if (isset($items['Computer']) && count($items['Computer'])) {
            foreach ($items['Computer'] as $ID) {
               $this->transferItem('Computer', $ID, $ID);
            }
         }

         // Inventory Items : MONITOR....
         $INVENTORY_TYPES = array('CartridgeItem', 'ConsumableItem', 'Monitor', 'NetworkEquipment',
                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense', );

         foreach ($INVENTORY_TYPES as $itemtype) {
            $this->inittype = $itemtype;
            if (isset($items[$itemtype]) && count($items[$itemtype])) {
               foreach ($items[$itemtype] as $ID) {
                  $this->transferItem($itemtype, $ID, $ID);
               }
            }
         }

         // Clean unused
         $this->cleanSoftwareVersions();

         // Management Items
         $MANAGEMENT_TYPES = array('Contact', 'Contract', 'Document', 'Supplier');
         foreach ($MANAGEMENT_TYPES as $itemtype) {
            $this->inittype = $itemtype;
            if (isset($items[$itemtype]) && count($items[$itemtype])) {
               foreach ($items[$itemtype] as $ID) {
                  $this->transferItem($itemtype, $ID, $ID);
               }
            }
         }

         // Tickets
         $OTHER_TYPES = array('Group', 'Link', 'Ticket', 'Problem', 'Change');
         foreach ($OTHER_TYPES as $itemtype) {
            $this->inittype = $itemtype;
            if (isset($items[$itemtype]) && count($items[$itemtype])) {
               foreach ($items[$itemtype] as $ID) {
                  $this->transferItem($itemtype, $ID, $ID);
               }
            }
         }
      } // $to >= 0
   }


   /**
    * Add an item in the needtobe_transfer list
    *
    * @param $itemtype  of the item
    * @param $ID        of the item
   **/
   function addToBeTransfer($itemtype, $ID) {

      if (!isset($this->needtobe_transfer[$itemtype])) {
         $this->needtobe_transfer[$itemtype] = array();
      }

      // Can't be in both list (in fact, always false)
      if (isset($this->noneedtobe_transfer[$itemtype][$ID])) {
         unset($this->noneedtobe_transfer[$itemtype][$ID]);
      }

      $this->needtobe_transfer[$itemtype][$ID] = $ID;
   }


   /**
    * Add an item in the noneedtobe_transfer list
    *
    * @param $itemtype  of the item
    * @param $ID        of the item
   **/
   function addNotToBeTransfer($itemtype, $ID) {

      if (!isset($this->noneedtobe_transfer[$itemtype])) {
         $this->noneedtobe_transfer[$itemtype] = array();
      }

      // Can't be in both list (in fact, always true)
      if (!isset($this->needtobe_transfer[$itemtype][$ID])) {
         $this->noneedtobe_transfer[$itemtype][$ID] = $ID;
      }
   }


   /**
    * simulate the transfer to know which items need to be transfer
    *
    * @param $items Array of the items to transfer
   **/
   function simulateTransfer($items) {
      global $DB, $CFG_GLPI;

      // Init types :
      $types = array('CartridgeItem', 'Change', 'Computer', 'ConsumableItem', 'Contact', 'Contract',
                     'Document', 'Link', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                     'Printer', 'Problem', 'Software', 'SoftwareLicense', 'SoftwareVersion',
                     'Supplier', 'Ticket');
      $types = array_merge($types, $CFG_GLPI['device_types']);
      $types = array_merge($types, Item_Devices::getDeviceTypes());
      foreach ($types as $t) {
         if (!isset($this->needtobe_transfer[$t])) {
            $this->needtobe_transfer[$t] = array();
         }
         if (!isset($this->noneedtobe_transfer[$t])) {
            $this->noneedtobe_transfer[$t] = array();
         }
         $this->item_search[$t]
               = $this->createSearchConditionUsingArray($this->needtobe_transfer[$t]);
         $this->item_recurs[$t]
               = $this->createSearchConditionUsingArray($this->noneedtobe_transfer[$t]);
      }

      $to_entity_ancestors = getAncestorsOf("glpi_entities", $this->to);

      // Copy items to needtobe_transfer
      foreach ($items as $key => $tab) {
         if (count($tab)) {
            foreach ($tab as $ID) {
               $this->addToBeTransfer($key,$ID);
            }
         }
      }

      // Computer first
      $this->item_search['Computer']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Computer']);

      // DIRECT CONNECTIONS

      $DC_CONNECT = array();
      if ($this->options['keep_dc_monitor']) {
         $DC_CONNECT[] = 'Monitor';
      }
      if ($this->options['keep_dc_phone']) {
         $DC_CONNECT[] = 'Phone';
      }
      if ($this->options['keep_dc_peripheral']) {
         $DC_CONNECT[] = 'Peripheral';
      }
      if ($this->options['keep_dc_printer']) {
         $DC_CONNECT[] = 'Printer';
      }

      if (count($DC_CONNECT)
          && (count($this->needtobe_transfer['Computer']) > 0)) {

         foreach ($DC_CONNECT as $itemtype) {
            $itemtable = getTableForItemType($itemtype);

            // Clean DB / Search unexisting links and force disconnect
            $query = "SELECT `glpi_computers_items`.`id`
                      FROM `glpi_computers_items`
                      LEFT JOIN `$itemtable`
                        ON (`glpi_computers_items`.`items_id` = `$itemtable`.`id` )
                      WHERE `glpi_computers_items`.`itemtype` = '$itemtype'
                            AND `$itemtable`.`id` IS NULL";

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $conn = new Computer_Item();
                     $conn->delete(array('id'             => $data['id'],
                                         '_no_history'    => true,
                                         '_no_auto_action'=> true));
                  }
               }
            }

            if (!($item = getItemForItemtype($itemtype))) {
               continue;
            }

            $query = "SELECT DISTINCT `items_id`
                      FROM `glpi_computers_items`
                      WHERE `itemtype` = '$itemtype'
                            AND `computers_id` IN ".$this->item_search['Computer'];

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     if ($item->getFromDB($data['items_id'])
                         && $item->isRecursive()
                         && in_array($item->getEntityID(), $to_entity_ancestors)) {
                        $this->addNotToBeTransfer($itemtype, $data['items_id']);
                     } else {
                        $this->addToBeTransfer($itemtype, $data['items_id']);
                     }
                  }
               }
            }

            $this->item_search[$itemtype]
                  = $this->createSearchConditionUsingArray($this->needtobe_transfer[$itemtype]);

            if ($item->maybeRecursive()) {
               $this->item_recurs[$itemtype]
                     = $this->createSearchConditionUsingArray($this->noneedtobe_transfer[$itemtype]);
            }
         }
      } // End of direct connections

      // License / Software :  keep / delete + clean unused / keep unused
      if ($this->options['keep_software']) {
         // Clean DB
         $query = "SELECT DISTINCT `glpi_computers_softwareversions`.`computers_id`
                   FROM  `glpi_computers_softwareversions`
                   WHERE `glpi_computers_softwareversions`.`computers_id`
                     NOT IN (SELECT id FROM `glpi_computers`)";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_computers_softwareversions`
                            WHERE `computers_id` = '".$data['computers_id']."'";
                  $DB->query($query);
               }
            }
         }

         // Clean DB
         $query = "SELECT DISTINCT `glpi_computers_softwareversions`.`softwareversions_id`
                   FROM  `glpi_computers_softwareversions`
                   WHERE `glpi_computers_softwareversions`.`softwareversions_id`
                     NOT IN (SELECT id FROM `glpi_softwareversions`)";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_computers_softwareversions`
                            WHERE `softwareversions_id` = '".$data['softwareversions_id']."'";
                  $DB->query($query);
               }
            }
         }

         // Clean DB
         $query = "SELECT `glpi_softwareversions`.`id`
                   FROM `glpi_softwareversions`
                   LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwares`.`id` = `glpi_softwareversions`.`softwares_id`)
                   WHERE `glpi_softwares`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_softwareversions`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         $query = "SELECT `glpi_softwares`.`id`,
                          `glpi_softwares`.`entities_id`,
                          `glpi_softwares`.`is_recursive`,
                          `glpi_softwareversions`.`id` AS vID
                   FROM `glpi_computers_softwareversions`
                   INNER JOIN `glpi_softwareversions`
                        ON (`glpi_computers_softwareversions`.`softwareversions_id`
                            = `glpi_softwareversions`.`id`)
                   INNER JOIN `glpi_softwares`
                        ON (`glpi_softwares`.`id` = `glpi_softwareversions`.`softwares_id`)
                   WHERE `glpi_computers_softwareversions`.`computers_id`
                        IN ".$this->item_search['Computer'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($data['is_recursive']
                      && in_array($data['entities_id'], $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('SoftwareVersion', $data['vID']);
                  } else {
                     $this->addToBeTransfer('SoftwareVersion', $data['vID']);
                  }
               }
            }
         }
      }

      // Software: From user choice only
      $this->item_search['Software']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Software']);
      $this->item_recurs['Software']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Software']);

      // Move license of software
      // TODO : should we transfer "affected license" ?
      $query = "SELECT `id`, `softwareversions_id_buy`, `softwareversions_id_use`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` IN ".$this->item_search['Software'];

      foreach ($DB->request($query) AS $lic) {
         $this->addToBeTransfer('SoftwareLicense', $lic['id']);

         // Force version transfer (remove from item_recurs)
         if ($lic['softwareversions_id_buy'] > 0) {
            $this->addToBeTransfer('SoftwareVersion', $lic['softwareversions_id_buy']);
         }
         if ($lic['softwareversions_id_use'] > 0) {
            $this->addToBeTransfer('SoftwareVersion', $lic['softwareversions_id_use']);
         }
      }

      // Licenses: from softwares  and computers (affected)
      $this->item_search['SoftwareLicense']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['SoftwareLicense']);
      $this->item_recurs['SoftwareLicense']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['SoftwareLicense']);

      // Versions: from affected licenses and installed versions
      $this->item_search['SoftwareVersion']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['SoftwareVersion']);
      $this->item_recurs['SoftwareVersion']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['SoftwareVersion']);

      $this->item_search['NetworkEquipment']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['NetworkEquipment']);


      // Devices
      if ($this->options['keep_device']) {
         foreach (Item_Devices::getConcernedItems() as $itemtype) {
            $itemtable = getTableForItemType($itemtype);
            if (isset($this->item_search[$itemtype])) {
               foreach (Item_Devices::getItemAffinities($itemtype) as $itemdevicetype) {
                  $itemdevicetable = getTableForItemType($itemdevicetype);
                  $devicetype      = $itemdevicetype::getDeviceType();
                  $devicetable     = getTableForItemType($devicetype);
                  $fk              = getForeignKeyFieldForTable($devicetable);
                  $query = "SELECT DISTINCT `$itemdevicetable`.`$fk`,
                                 `$devicetable`.`entities_id`,
                                 `$devicetable`.`is_recursive`
                           FROM `$itemdevicetable`
                           LEFT JOIN `$devicetable`
                                 ON (`$itemdevicetable`.`$fk` = `$devicetable`.`id`)
                           WHERE `$itemdevicetable`.`itemtype` = '$itemtype'
                                 AND `$itemdevicetable`.`items_id`
                                       IN ".$this->item_search[$itemtype];

                  foreach ($DB->request($query) as $data) {
                     if ($data['is_recursive']
                         && in_array($data['entities_id'], $to_entity_ancestors)) {
                        $this->addNotToBeTransfer($devicetype, $data[$fk]);
                     } else {
                        if (!isset($this->needtobe_transfer[$devicetype][$data[$fk]])) {
                           $this->addToBeTransfer($devicetype, $data[$fk]);
                           $query2 = "SELECT `$itemdevicetable`.`id`
                                      FROM `$itemdevicetable`
                                      WHERE `$itemdevicetable`.`$fk` = '".$data[$fk]."'
                                            AND `$itemdevicetable`.`itemtype` = '$itemtype'
                                            AND `$itemdevicetable`.`items_id`
                                                IN ".$this->item_search[$itemtype];
                           foreach ($DB->request($query2) as $data2) {
                              $this->addToBeTransfer($itemdevicetype, $data2['id']);
                           }
                        }
                     }
                  }
               }
            }
         }
      }

      foreach ($CFG_GLPI['device_types'] as $itemtype) {
         $this->item_search[$itemtype]
               = $this->createSearchConditionUsingArray($this->needtobe_transfer[$itemtype]);
         $this->item_recurs[$itemtype]
               = $this->createSearchConditionUsingArray($this->noneedtobe_transfer[$itemtype]);
      }
      foreach (Item_Devices::getDeviceTypes() as $itemtype) {
         $this->item_search[$itemtype]
               = $this->createSearchConditionUsingArray($this->needtobe_transfer[$itemtype]);
         $this->item_recurs[$itemtype]
               = $this->createSearchConditionUsingArray($this->noneedtobe_transfer[$itemtype]);
      }


      // Tickets
      if ($this->options['keep_ticket']) {
         foreach ($CFG_GLPI["ticket_types"] as $itemtype) {
            if (isset($this->item_search[$itemtype])) {
               $query = "SELECT DISTINCT `glpi_tickets`.`id`
                         FROM `glpi_tickets`
                         LEFT JOIN `glpi_items_tickets`
                            ON `glpi_items_tickets`.`tickets_id` = `glpi_tickets`.`id`
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` IN ".$this->item_search[$itemtype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $this->addToBeTransfer('Ticket', $data['id']);
                     }
                  }
               }
            }
         }
      }
      $this->item_search['Ticket']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Ticket']);

      // Contract : keep / delete + clean unused / keep unused
      if ($this->options['keep_contract']) {
         foreach ($CFG_GLPI["contract_types"] as $itemtype) {
            if (isset($this->item_search[$itemtype])) {
               $itemtable = getTableForItemType($itemtype);
               $this->item_search[$itemtype]
                     = $this->createSearchConditionUsingArray($this->needtobe_transfer[$itemtype]);
                     
               // Clean DB
               $query = "SELECT `glpi_contracts_items`.`id`
                         FROM `glpi_contracts_items`
                         LEFT JOIN `$itemtable`
                           ON (`glpi_contracts_items`.`items_id` = `$itemtable`.`id`)
                         WHERE `glpi_contracts_items`.`itemtype` = '$itemtype'
                               AND `$itemtable`.`id` IS NULL";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $query = "DELETE
                                  FROM `glpi_contracts_items`
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }
               }
               // Clean DB
               $query = "SELECT `glpi_contracts_items`.`id`
                         FROM `glpi_contracts_items`
                         LEFT JOIN `glpi_contracts`
                           ON (`glpi_contracts_items`.`contracts_id` = `glpi_contracts`.`id`)
                         WHERE `glpi_contracts`.`id` IS NULL";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $query = "DELETE
                                  FROM `glpi_contracts_items`
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }
               }

               $query = "SELECT `contracts_id`,
                                `glpi_contracts`.`entities_id`,
                                `glpi_contracts`.`is_recursive`
                         FROM `glpi_contracts_items`
                         LEFT JOIN `glpi_contracts`
                               ON (`glpi_contracts_items`.`contracts_id` = `glpi_contracts`.`id`)
                         WHERE `glpi_contracts_items`.`itemtype` = '$itemtype'
                               AND `glpi_contracts_items`.`items_id`
                                    IN ".$this->item_search[$itemtype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        if ($data['is_recursive']
                            && in_array($data['entities_id'], $to_entity_ancestors)) {
                           $this->addNotToBeTransfer('Contract', $data['contracts_id']);
                        } else {
                           $this->addToBeTransfer('Contract', $data['contracts_id']);
                        }
                     }
                  }
               }
            }
         }
      }
      $this->item_search['Contract']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Contract']);
      $this->item_recurs['Contract']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Contract']);
      // Supplier (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused

      if ($this->options['keep_supplier']) {
         // Clean DB
         $query = "SELECT `glpi_contracts_suppliers`.`id`
                   FROM `glpi_contracts_suppliers`
                   LEFT JOIN `glpi_contracts`
                        ON (`glpi_contracts_suppliers`.`contracts_id` = `glpi_contracts`.`id`)
                   WHERE `glpi_contracts`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_contracts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }
         // Clean DB
         $query = "SELECT `glpi_contracts_suppliers`.`id`
                   FROM `glpi_contracts_suppliers`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_contracts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`)
                   WHERE `glpi_suppliers`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_contracts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }
         // Supplier Contract
         $query = "SELECT DISTINCT `suppliers_id`,
                                   `glpi_suppliers`.`is_recursive`,
                                   `glpi_suppliers`.`entities_id`
                   FROM `glpi_contracts_suppliers`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_suppliers`.`id` = `glpi_contracts_suppliers`.`suppliers_id`)
                   WHERE `contracts_id` IN ".$this->item_search['Contract'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($data['is_recursive']
                      && in_array($data['entities_id'], $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                  } else {
                     $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                  }
               }
            }
         }
         // Ticket Supplier
         $query = "SELECT DISTINCT `glpi_suppliers_tickets`.`suppliers_id`,
                                   `glpi_suppliers`.`is_recursive`,
                                   `glpi_suppliers`.`entities_id`
                   FROM `glpi_tickets`
                   LEFT JOIN `glpi_suppliers_tickets`
                         ON (`glpi_suppliers_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_suppliers`.`id` = `glpi_suppliers_tickets`.`suppliers_id`)
                   WHERE `glpi_suppliers_tickets`.`suppliers_id` > '0'
                         AND `glpi_tickets`.`id` IN ".$this->item_search['Ticket'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($data['is_recursive']
                      && in_array($data['entities_id'], $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                  } else {
                     $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                  }

               }
            }
         }
         // Problem Supplier
         $query = "SELECT DISTINCT `glpi_problems_suppliers`.`suppliers_id`,
                                   `glpi_suppliers`.`is_recursive`,
                                   `glpi_suppliers`.`entities_id`
                   FROM `glpi_problems`
                   LEFT JOIN `glpi_problems_suppliers`
                         ON (`glpi_problems_suppliers`.`problems_id` = `glpi_problems`.`id`)
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_suppliers`.`id` = `glpi_problems_suppliers`.`suppliers_id`)
                   WHERE `glpi_problems_suppliers`.`suppliers_id` > '0'
                         AND `glpi_problems`.`id` IN ".$this->item_search['Problem'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($data['is_recursive']
                      && in_array($data['entities_id'], $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                  } else {
                     $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                  }

               }
            }
         }
         // Change Supplier
         $query = "SELECT DISTINCT `glpi_changes_suppliers`.`suppliers_id`,
                                   `glpi_suppliers`.`is_recursive`,
                                   `glpi_suppliers`.`entities_id`
                   FROM `glpi_changes`
                   LEFT JOIN `glpi_changes_suppliers`
                         ON (`glpi_changes_suppliers`.`changes_id` = `glpi_changes`.`id`)
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_suppliers`.`id` = `glpi_changes_suppliers`.`suppliers_id`)
                   WHERE `glpi_changes_suppliers`.`suppliers_id` > '0'
                         AND `glpi_changes`.`id` IN ".$this->item_search['Change'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($data['is_recursive']
                      && in_array($data['entities_id'], $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                  } else {
                     $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                  }

               }
            }
         }
         // Supplier infocoms
         if ($this->options['keep_infocom']) {
            foreach (Infocom::getItemtypesThatCanHave() as $itemtype) {
               if (isset($this->item_search[$itemtype])) {
                  $itemtable = getTableForItemType($itemtype);
                  $this->item_search[$itemtype]
                   = $this->createSearchConditionUsingArray($this->needtobe_transfer[$itemtype]);

                  // Clean DB
                  $query = "SELECT `glpi_infocoms`.`id`
                            FROM `glpi_infocoms`
                            LEFT JOIN `$itemtable`
                               ON (`glpi_infocoms`.`items_id` = `$itemtable`.`id`)
                            WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                                  AND `$itemtable`.`id` IS NULL";
                  if ($result = $DB->query($query)) {
                     if ($DB->numrows($result) > 0) {
                        while ($data = $DB->fetch_assoc($result)) {
                           $query = "DELETE
                                     FROM `glpi_infocoms`
                                     WHERE `id` = '".$data['id']."'";
                           $DB->query($query);
                        }
                     }
                  }
                  $query = "SELECT DISTINCT `suppliers_id`,
                                            `glpi_suppliers`.`is_recursive`,
                                            `glpi_suppliers`.`entities_id`
                            FROM `glpi_infocoms`
                            LEFT JOIN `glpi_suppliers`
                              ON (`glpi_suppliers`.`id` = `glpi_infocoms`.`suppliers_id`)
                            WHERE `suppliers_id` > '0'
                                  AND `itemtype` = '$itemtype'
                                  AND `items_id` IN ".$this->item_search[$itemtype];

                  if ($result = $DB->query($query)) {
                     if ($DB->numrows($result) > 0) {
                        while ($data = $DB->fetch_assoc($result)) {
                           if ($data['is_recursive']
                               && in_array($data['entities_id'], $to_entity_ancestors)) {
                              $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                           } else {
                              $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                           }
                        }
                     }
                  }
               }
            }
         }

      }

      $this->item_search['Supplier']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Supplier']);
      $this->item_recurs['Supplier']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Supplier']);

      // Contact / Supplier : keep / delete + clean unused / keep unused
      if ($this->options['keep_contact']) {
         // Clean DB
         $query = "SELECT `glpi_contacts_suppliers`.`id`
                   FROM `glpi_contacts_suppliers`
                   LEFT JOIN `glpi_contacts`
                         ON (`glpi_contacts_suppliers`.`contacts_id` = `glpi_contacts`.`id`)
                   WHERE `glpi_contacts`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_contacts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         // Clean DB
         $query = "SELECT `glpi_contacts_suppliers`.`id`
                   FROM `glpi_contacts_suppliers`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`)
                   WHERE `glpi_suppliers`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $query = "DELETE
                            FROM `glpi_contacts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         // Supplier Contact
         $query = "SELECT DISTINCT `contacts_id`,
                                   `glpi_contacts`.`is_recursive`,
                                   `glpi_contacts`.`entities_id`
                   FROM `glpi_contacts_suppliers`
                   LEFT JOIN `glpi_contacts`
                        ON (`glpi_contacts`.`id` = `glpi_contacts_suppliers`.`contacts_id`)
                   WHERE `suppliers_id` IN ".$this->item_search['Supplier'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($data['is_recursive']
                      && in_array($data['entities_id'], $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Contact', $data['contacts_id']);
                  } else {
                     $this->addToBeTransfer('Contact', $data['contacts_id']);
                  }
               }
            }
         }
      }

      $this->item_search['Contact']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Contact']);
      $this->item_recurs['Contact']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Contact']);

      // Document : keep / delete + clean unused / keep unused
      if ($this->options['keep_document']) {
         foreach (Document::getItemtypesThatCanHave() as $itemtype) {
            if (isset($this->item_search[$itemtype])) {
               $itemtable = getTableForItemType($itemtype);
               // Clean DB
               $query = "SELECT `glpi_documents_items`.`id`
                         FROM `glpi_documents_items`
                         LEFT JOIN `$itemtable`
                           ON (`glpi_documents_items`.`items_id` = `$itemtable`.`id`)
                         WHERE `glpi_documents_items`.`itemtype` = '$itemtype'
                               AND `$itemtable`.`id` IS NULL";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $query = "DELETE
                                  FROM `glpi_documents_items`
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }
               }

               $query = "SELECT `documents_id`, `glpi_documents`.`is_recursive`,
                                `glpi_documents`.`entities_id`
                         FROM `glpi_documents_items`
                         LEFT JOIN `glpi_documents`
                              ON (`glpi_documents`.`id` = `glpi_documents_items`.`documents_id`)
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` IN ".$this->item_search[$itemtype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        if ($data['is_recursive']
                            && in_array($data['entities_id'], $to_entity_ancestors)) {
                           $this->addNotToBeTransfer('Document', $data['documents_id']);
                        } else {
                           $this->addToBeTransfer('Document', $data['documents_id']);
                        }
                     }
                  }
               }
            }
         }
      }

      $this->item_search['Document']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['Document']);
      $this->item_recurs['Document']
            = $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Document']);

      // printer -> cartridges : keep / delete + clean
      if ($this->options['keep_cartridgeitem']) {
         if (isset($this->item_search['Printer'])) {
            $query = "SELECT `cartridgeitems_id`
                      FROM `glpi_cartridges`
                      WHERE `printers_id` IN ".$this->item_search['Printer'];

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $this->addToBeTransfer('CartridgeItem',$data['cartridgeitems_id']);
                  }
               }
            }
         }
      }

      $this->item_search['CartridgeItem']
            = $this->createSearchConditionUsingArray($this->needtobe_transfer['CartridgeItem']);

      // Init all item_search if not defined
      foreach ($types as $itemtype) {
         if (!isset($this->item_search[$itemtype])) {
            $this->item_search[$itemtype] = "(-1)";
         }
      }

   }


   /**
    * Create IN condition for SQL requests based on a array if ID
    *
    * @param $array array of ID
    *
    * @return string of the IN condition
   **/
   function createSearchConditionUsingArray($array) {

      if (is_array($array) && count($array)) {
         return "('".implode("','",$array)."')";
      }
      return "(-1)";
   }


   /**
    * transfer an item to another item (may be the same) in the new entity
    *
    * @param $itemtype     item type to transfer
    * @param $ID           ID of the item to transfer
    * @param $newID        new ID of the ite
    *
    * Transfer item to a new Item if $ID==$newID : only update entities_id field :
    *                                $ID!=$new ID -> copy datas (like template system)
    * @return nothing (diplays)
   **/
   function transferItem($itemtype, $ID, $newID) {
      global $CFG_GLPI, $DB;

      if (!($item = getItemForItemtype($itemtype))) {
         return;
      }

      // Is already transfer ?
      if (!isset($this->already_transfer[$itemtype][$ID])) {
         // Check computer exists ?
         if ($item->getFromDB($newID)) {

            // Network connection ? keep connected / keep_disconnected / delete
            if (in_array($itemtype, array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                          'Phone', 'Printer'))) {
               $this->transferNetworkLink($itemtype, $ID, $newID);
            }

            // Device : keep / delete : network case : delete if net connection delete in import case
            if (in_array($itemtype, Item_Devices::getConcernedItems())) {
               $this->transferDevices($itemtype, $ID, $newID);
            }

            // Reservation : keep / delete
            if (in_array($itemtype, $CFG_GLPI["reservation_types"])) {
               $this->transferReservations($itemtype, $ID, $newID);
            }

            // History : keep / delete
            $this->transferHistory($itemtype, $ID, $newID);
            // Ticket : delete / keep and clean ref / keep and move
            $this->transferTickets($itemtype, $ID, $newID);
            // Infocoms : keep / delete

            if (InfoCom::canApplyOn($itemtype)) {
               $this->transferInfocoms($itemtype, $ID, $newID);
            }

            if ($itemtype == 'Software') {
               $this->transferSoftwareLicensesAndVersions($ID);
            }

            // Connected item is transfered
            if (in_array($itemtype, $CFG_GLPI["directconnect_types"])) {
               $this->manageConnectionComputer($itemtype, $ID);
            }

            // Computer Direct Connect : delete link if it is the initial transfer item (no recursion)
            if (($this->inittype == $itemtype)
                && in_array($itemtype, array('Monitor', 'Phone', 'Peripheral', 'Printer'))) {
               $this->deleteDirectConnection($itemtype, $ID);
            }

            // Contract : keep / delete + clean unused / keep unused
            if (in_array($itemtype,$CFG_GLPI["contract_types"])) {
               $this->transferContracts($itemtype, $ID, $newID);
            }

            // Contact / Supplier : keep / delete + clean unused / keep unused
            if ($itemtype == 'Supplier') {
               $this->transferSupplierContacts($ID, $newID);
            }

            // Document : keep / delete + clean unused / keep unused
            if (Document::canApplyOn($itemtype)) {
               $this->transferDocuments($itemtype, $ID, $newID);
            }

            // Transfer compatible printers
            if ($itemtype == 'CartridgeItem') {
               $this->transferCompatiblePrinters($ID, $newID);
            }

            // Cartridges  and cartridges items linked to printer
            if ($itemtype == 'Printer') {
               $this->transferPrinterCartridges($ID, $newID);
            }

            // Transfer Item
            $input = array('id'          => $newID,
                           'entities_id' => $this->to);

            // Manage Location dropdown
            if (isset($item->fields['locations_id'])) {
               $input['locations_id'] = $this->transferDropdownLocation($item->fields['locations_id']);
            }

            if (in_array($itemtype, array('Ticket', 'Problem', 'Change'))) {
               $input2 = $this->transferHelpdeskAdditionalInformations($item->fields);
               $input  = array_merge($input, $input2);
               $this->transferTaskCategory($itemtype, $ID, $newID);
               $this->transferLinkedSuppliers($itemtype, $ID, $newID);
            }

            $item->update($input);
            $this->addToAlreadyTransfer($itemtype,$ID,$newID);

            // Do it after item transfer for entity checks
            if ($itemtype == 'Computer') {
               // Monitor Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Monitor');
               // Peripheral Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Peripheral');
               // Phone Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Phone');
               // Printer Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Printer');
               // License / Software :  keep / delete + clean unused / keep unused
               $this->transferComputerSoftwares($ID);
               // Computer Disks :  delete them or not ?
               $this->transferComputerDisks($ID);
            }

            Plugin::doHook("item_transfer", array('type'        => $itemtype,
                                                  'id'          => $ID,
                                                  'newID'       => $newID,
                                                  'entities_id' => $this->to));
         }
      }
   }


   /**
    * Add an item to already transfer array
    *
    * @param $itemtype  item type
    * @param $ID        item original ID
    * @param $newID     item new ID
   **/
   function addToAlreadyTransfer($itemtype,$ID,$newID) {

      if (!isset($this->already_transfer[$itemtype])) {
         $this->already_transfer[$itemtype] = array();
      }
      $this->already_transfer[$itemtype][$ID]=$newID;
   }


   /**
    * Transfer location
    *
    * @param $locID location ID
    *
    * @return new location ID
   **/
   function transferDropdownLocation($locID) {
      global $DB;

      if ($locID > 0) {
         if (isset($this->already_transfer['locations_id'][$locID])) {
            return $this->already_transfer['locations_id'][$locID];
         }
         // else  // Not already transfer
         // Search init item
         $location = new Location();
         if ($location->getFromDB($locID)) {
            $data = Toolbox::addslashes_deep($location->fields);

            $input['entities_id']  = $this->to;
            $input['completename'] = $data['completename'];
            $newID                 = $location->findID($input);

            if ($newID < 0) {
               $newID = $location->import($input);
            }

            $this->addToAlreadyTransfer('locations_id', $locID, $newID);
            return $newID;
         }
      }
      return 0;
   }


   /**
    * Transfer netpoint
    *
    * @param $netpoints_id netpoint ID
    *
    * @return new netpoint ID
   **/
   function transferDropdownNetpoint($netpoints_id) {
      global $DB;

      if ($netpoints_id > 0) {
         if (isset($this->already_transfer['netpoints_id'][$netpoints_id])) {
            return $this->already_transfer['netpoints_id'][$netpoints_id];
         }
         // else  // Not already transfer
         // Search init item
         $netpoint = new Netpoint();
         if ($netpoint->getFromDB($netpoints_id)) {
            $data  = Toolbox::addslashes_deep($netpoint->fields);
            $locID = $this->transferDropdownLocation($netpoint->fields['locations_id']);

            // Search if the locations_id already exists in the destination entity
            $query = "SELECT `id`
                      FROM `glpi_netpoints`
                      WHERE `entities_id` = '".$this->to."'
                            AND `name` = '".$netpoint->fields['name']."'
                            AND `locations_id` = '$locID'";

            if ($result_search = $DB->query($query)) {
               // Found : -> use it
               if ($DB->numrows($result_search) > 0) {
                  $newID = $DB->result($result_search,0,'id');
                  $this->addToAlreadyTransfer('netpoints_id', $netpoints_id, $newID);
                  return $newID;
               }
            }

            // Not found :
            // add item
            $newID    = $netpoint->add(array('name'         => $data['name'],
                                             'comment'      => $data['comment'],
                                             'entities_id'  => $this->to,
                                             'locations_id' => $locID));

            $this->addToAlreadyTransfer('netpoints_id', $netpoints_id, $newID);
            return $newID;
         }
      }
      return 0;
   }


   /**
    * Transfer cartridges of a printer
    *
    * @param $ID     original ID of the printer
    * @param $newID  new ID of the printer
   **/
   function transferPrinterCartridges($ID, $newID) {
      global $DB;

      // Get cartrdiges linked
      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `glpi_cartridges`.`printers_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $cart     = new Cartridge();
            $carttype = new CartridgeItem();

            while ($data = $DB->fetch_assoc($result)) {
               $need_clean_process = false;

               // Foreach cartridges
               // if keep
               if ($this->options['keep_cartridgeitem']) {
                  $newcartID     =- 1;
                  $newcarttypeID = -1;

                  // 1 - Search carttype destination ?
                  // Already transfer carttype :
                  if (isset($this->already_transfer['CartridgeItem'][$data['cartridgeitems_id']])) {
                     $newcarttypeID
                           = $this->already_transfer['CartridgeItem'][$data['cartridgeitems_id']];

                  } else {
                     // Not already transfer cartype
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `glpi_cartridges`
                               WHERE `glpi_cartridges`.`cartridgeitems_id`
                                          = '".$data['cartridgeitems_id']."'
                                     AND `glpi_cartridges`.`printers_id` > '0'
                                     AND `glpi_cartridges`.`printers_id`
                                          NOT IN ".$this->item_search['Printer'];
                     $result_search = $DB->query($query);

                     // Is the carttype will be completly transfer ?
                     if ($DB->result($result_search, 0, 'cpt') == 0) {
                        // Yes : transfer
                        $need_clean_process = false;
                        $this->transferItem('CartridgeItem', $data['cartridgeitems_id'],
                                            $data['cartridgeitems_id']);
                        $newcarttypeID = $data['cartridgeitems_id'];

                     } else {
                        // No : copy carttype
                        $need_clean_process = true;
                        $carttype->getFromDB($data['cartridgeitems_id']);
                        // Is existing carttype in the destination entity ?
                        $query = "SELECT *
                                  FROM `glpi_cartridgeitems`
                                  WHERE `entities_id` = '".$this->to."'
                                        AND `name` = '".addslashes($carttype->fields['name'])."'";

                        if ($result_search = $DB->query($query)) {
                           if ($DB->numrows($result_search) > 0) {
                              $newcarttypeID = $DB->result($result_search,0,'id');
                           }
                        }

                        // Not found -> transfer copy
                        if ($newcarttypeID < 0) {
                           // 1 - create new item
                           unset($carttype->fields['id']);
                           $input                = $carttype->fields;
                           $input['entities_id'] = $this->to;
                           unset($carttype->fields);
                           $newcarttypeID        = $carttype->add(toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                           $this->transferItem('CartridgeItem', $data['cartridgeitems_id'],
                                               $newcarttypeID);
                        }

                        // Founded -> use to link : nothing to do
                     }

                  }

                  // Update cartridge if needed
                  if (($newcarttypeID > 0)
                      && ($newcarttypeID != $data['cartridgeitems_id'])) {
                     $cart->update(array('id'                => $data['id'],
                                         'cartridgeitems_id' => $newcarttypeID));
                  }

               } else { // Do not keep
                  // If same printer : delete cartridges
                  if ($ID == $newID) {
                     $del_query = "DELETE
                                   FROM `glpi_cartridges`
                                   WHERE `printers_id` = '$ID'";
                     $DB->query($del_query);
                  }
                  $need_clean_process = true;
               }

               // CLean process
               if ($need_clean_process
                   && $this->options['clean_cartridgeitem']) {

                  // Clean carttype
                  $query2 = "SELECT COUNT(*) AS cpt
                             FROM `glpi_cartridges`
                             WHERE `cartridgeitems_id` = '" . $data['cartridgeitems_id'] . "'";
                  $result2 = $DB->query($query2);

                  if ($DB->result($result2, 0, 'cpt') == 0) {
                     if ($this->options['clean_cartridgeitem'] == 1) { // delete
                        $carttype->delete(array('id' => $data['cartridgeitems_id']));
                     }
                     if ($this->options['clean_cartridgeitem'] == 2) { // purge
                        $carttype->delete(array('id' => $data['cartridgeitems_id']),1);
                     }
                  }
               }

            }

         }
      }

   }


   /**
    * Copy (if needed) One software to the destination entity
    *
    * @param $ID of the software
    *
    * @return $ID of the new software (could be the same)
   **/
   function copySingleSoftware($ID) {
      global $DB;

      if (isset($this->already_transfer['Software'][$ID])) {
         return $this->already_transfer['Software'][$ID];
      }

      $soft = new Software();
      if ($soft->getFromDB($ID)) {
         if ($soft->fields['is_recursive']
             && in_array($soft->fields['entities_id'], getAncestorsOf("glpi_entities",
                                                                      $this->to))) {
            // no need to copy
            $newsoftID = $ID;

         } else {
            $query = "SELECT *
                      FROM `glpi_softwares`
                      WHERE `entities_id` = ".$this->to."
                            AND `name` = '".addslashes($soft->fields['name'])."'";

            if ($data = $DB->request($query)->next()) {
               $newsoftID = $data["id"];

            } else {
               // create new item (don't check if move possible => clean needed)
               unset($soft->fields['id']);
               $input                = $soft->fields;
               $input['entities_id'] = $this->to;
               unset($soft->fields);
               $newsoftID            = $soft->add(toolbox::addslashes_deep($input));
            }

         }

         $this->addToAlreadyTransfer('Software', $ID, $newsoftID);
         return $newsoftID;
      }

      return -1;
   }


   /**
    * Copy (if needed) One softwareversion to the Dest Entity
    *
    * @param $ID of the version
    *
    * @return $ID of the new version (could be the same)
   **/
   function copySingleVersion($ID) {
      global $DB;

      if (isset($this->already_transfer['SoftwareVersion'][$ID])) {
         return $this->already_transfer['SoftwareVersion'][$ID];
      }

      $vers = new SoftwareVersion();
      if ($vers->getFromDB($ID)) {
         $newsoftID = $this->copySingleSoftware($vers->fields['softwares_id']);

         if ($newsoftID == $vers->fields['softwares_id']) {
            // no need to copy
            $newversID = $ID;

         } else {
            $query = "SELECT `id`
                      FROM `glpi_softwareversions`
                      WHERE `softwares_id` = $newsoftID
                            AND `name` = '".addslashes($vers->fields['name'])."'";

            if ($data = $DB->request($query)->next()) {
               $newversID = $data["id"];

            } else {
               // create new item (don't check if move possible => clean needed)
               unset($vers->fields['id']);
               $input                 = $vers->fields;
               $vers->fields = array();
               // entities_id and is_recursive from new software are set in prepareInputForAdd
               $input['softwares_id'] = $newsoftID;
               $newversID             = $vers->add(toolbox::addslashes_deep($input));
            }

         }

         $this->addToAlreadyTransfer('SoftwareVersion', $ID, $newversID);
         return $newversID;
      }

      return -1;
   }


   /**
    * Transfer disks of a computer
    *
    * @param $ID ID of the computer
   **/
   function transferComputerDisks($ID) {

      if (!$this->options['keep_disk']) {
         $disk = new ComputerDisk();
         $disk->cleanDBonItemDelete('Computer', $ID);
      }
   }


   /**
    * Transfer softwares of a computer
    *
    * @param $ID           ID of the computer
   **/
   function transferComputerSoftwares($ID) {
      global $DB;

      // Get Installed version
      $query = "SELECT *
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$ID'
                      AND `softwareversions_id` NOT IN ".$this->item_recurs['SoftwareVersion'];

      foreach ($DB->request($query) AS $data) {
         if ($this->options['keep_software']) {
            $newversID = $this->copySingleVersion($data['softwareversions_id']);

            if (($newversID > 0)
                && ($newversID != $data['softwareversions_id'])) {
               $query = "UPDATE `glpi_computers_softwareversions`
                         SET `softwareversions_id` = '$newversID'
                         WHERE `id` = ".$data['id'];
               $DB->query($query);
            }

         } else { // Do not keep
            // Delete inst software for computer
            $del_query = "DELETE
                          FROM `glpi_computers_softwareversions`
                          WHERE `id` = ".$data['id'];
            $DB->query($del_query);
         }
      } // each installed version

      // Affected licenses
      if ($this->options['keep_software']) {
         $query = "SELECT `id`
                   FROM `glpi_computers_softwarelicenses`
                   WHERE `computers_id` = '$ID'";
         foreach ($DB->request($query) AS $data) {
            $this->transferAffectedLicense($data['id']);
         }
      } else {
         $query = "DELETE
                   FROM `glpi_computers_softwarelicenses`
                   WHERE `computers_id` = '$ID'";
         $DB->query($query);
      }
   }


   /**
    * Transfer affected licenses to a computer
    *
    * @param $ID ID of the License
   **/
   function transferAffectedLicense($ID) {
      global $DB;

      $computer_softwarelicense = new Computer_SoftwareLicense();
      $license                  = new SoftwareLicense();

      if ($computer_softwarelicense->getFromDB($ID)) {
         if ($license->getFromDB($computer_softwarelicense->getField('softwarelicenses_id'))) {

            //// Update current : decrement number by 1 if valid
            if ($license->getField('number') > 1) {
               $license->update(array('id'     => $license->getID(),
                                      'number' => ($license->getField('number')-1)));
            } else if ($license->getField('number') == 1) {
               // Drop license
               $license->delete(array('id' => $license->getID()));
            }

            // Create new license : need to transfer softwre and versions before
            $input     = array();
            $newsoftID = $this->copySingleSoftware($license->fields['softwares_id']);

            if ($newsoftID > 0) {
               //// If license already exists : increment number by one
               $query = "SELECT *
                         FROM `glpi_softwarelicenses`
                         WHERE `softwares_id` = '$newsoftID'
                               AND `name` = '".addslashes($license->fields['name'])."'
                               AND `serial` = '".addslashes($license->fields['serial'])."'";
               $newlicID = -1;
               if ($result = $DB->query($query)) {
                  //// If exists : increment number by 1
                  if ($DB->numrows($result) > 0) {
                     $data     = $DB->fetch_assoc($result);
                     $newlicID = $data['id'];
                     $license->update(array('id'     => $data['id'],
                                            'number' => $data['number']+1));

                  } else {
                     //// If not exists : create with number = 1
                     $input = $license->fields;
                     foreach (array('softwareversions_id_buy',
                                    'softwareversions_id_use') as $field) {
                        if ($license->fields[$field] > 0) {
                           $newversID = $this->copySingleVersion($license->fields[$field]);
                           if (($newversID > 0)
                               && ($newversID != $license->fields[$field])) {
                              $input[$field] = $newversID;
                           }
                        }
                     }

                     unset($input['id']);
                     $input['number']       = 1;
                     $input['entities_id']  = $this->to;
                     $input['softwares_id'] = $newsoftID;
                     $newlicID              = $license->add(toolbox::addslashes_deep($input));
                  }
               }

               if ($newlicID > 0) {
                  $input = array('id'                  => $ID,
                                 'softwarelicenses_id' => $newlicID);
                  $computer_softwarelicense->update($input);
               }
            }
         }
      } // getFromDB

   }


   /**
    * Transfer License and Version of a Software
    *
    * @param $ID ID of the Software
   **/
   function transferSoftwareLicensesAndVersions($ID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` = '$ID'";

      foreach ($DB->request($query) AS $data) {
         $this->transferItem('SoftwareLicense', $data['id'], $data['id']);
      }

      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$ID'";

      foreach ($DB->request($query) AS $data) {
         // Just Store the info.
         $this->addToAlreadyTransfer('SoftwareVersion', $data['id'], $data['id']);
      }
   }


   function cleanSoftwareVersions() {

      if (!isset($this->already_transfer['SoftwareVersion'])) {
         return;
      }

      $vers = new SoftwareVersion();
      foreach ($this->already_transfer['SoftwareVersion'] AS $old => $new) {
         if ((countElementsInTable("glpi_softwarelicenses", "softwareversions_id_buy=$old") == 0)
             && (countElementsInTable("glpi_softwarelicenses", "softwareversions_id_use=$old") == 0)
             && (countElementsInTable("glpi_computers_softwareversions",
                                      "softwareversions_id=$old") == 0)) {

            $vers->delete(array('id' => $old));
         }
      }
   }


   function cleanSoftwares() {

      if (!isset($this->already_transfer['Software'])) {
         return;
      }

      $soft = new Software();
      foreach ($this->already_transfer['Software'] AS $old => $new) {
         if ((countElementsInTable("glpi_softwarelicenses", "softwares_id=$old") == 0)
             && (countElementsInTable("glpi_softwareversions", "softwares_id=$old") == 0)) {

            if ($this->options['clean_software'] == 1) { // delete
               $soft->delete(array('id' => $old), 0);

            } else if ($this->options['clean_software'] ==  2) { // purge
               $soft->delete(array('id' => $old),1);
            }
         }
      }

   }


   /**
    * Transfer contracts
    *
    * @param $itemtype  original type of transfered item
    * @param $ID        original ID of the contract
    * @param $newID     new ID of the contract
   **/
   function transferContracts($itemtype, $ID, $newID) {
      global $DB;

      $need_clean_process = false;

      // if keep
      if ($this->options['keep_contract']) {
         $contract = new Contract();
         // Get contracts for the item
         $query = "SELECT *
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '$ID'
                         AND `itemtype` = '$itemtype'
                         AND `contracts_id` NOT IN ".$this->item_recurs['Contract'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // Foreach get item
               while ($data = $DB->fetch_assoc($result)) {
                  $need_clean_process = false;
                  $item_ID            = $data['contracts_id'];
                  $newcontractID      = -1;

                  // is already transfer ?
                  if (isset($this->already_transfer['Contract'][$item_ID])) {
                     $newcontractID = $this->already_transfer['Contract'][$item_ID];
                     if ($newcontractID != $item_ID) {
                        $need_clean_process = true;
                     }

                  } else {
                     // No
                     // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                     $canbetransfer = true;
                     $query = "SELECT DISTINCT `itemtype`
                               FROM `glpi_contracts_items`
                               WHERE `contracts_id` = '$item_ID'";

                     if ($result_type = $DB->query($query)) {
                        if ($DB->numrows($result_type) > 0) {
                           while (($data_type = $DB->fetch_assoc($result_type))
                                  && $canbetransfer) {
                              $dtype = $data_type['itemtype'];

                              if (isset($this->item_search[$dtype])) {
                                 // No items to transfer -> exists links
                                 $query_search = "SELECT COUNT(*) AS cpt
                                                  FROM `glpi_contracts_items`
                                                  WHERE `contracts_id` = '$item_ID'
                                                        AND `itemtype` = '$dtype'
                                                        AND `items_id`
                                                             NOT IN ".$this->item_search[$dtype];
                                 $result_search = $DB->query($query_search);

                                 if ($DB->result($result_search, 0, 'cpt') > 0) {
                                    $canbetransfer = false;
                                 }

                              } else {
                                 $canbetransfer = false;
                              }

                           }
                        }
                     }

                     // Yes : transfer
                     if ($canbetransfer) {
                        $this->transferItem('Contract', $item_ID, $item_ID);
                        $newcontractID = $item_ID;

                     } else {
                        $need_clean_process = true;
                        $contract->getFromDB($item_ID);
                        // No : search contract
                        $query = "SELECT *
                                  FROM `glpi_contracts`
                                  WHERE `entities_id` = '".$this->to."'
                                        AND `name` = '".addslashes($contract->fields['name'])."'";

                        if ($result_search = $DB->query($query)) {
                           if ($DB->numrows($result_search) > 0) {
                              $newcontractID = $DB->result($result_search, 0, 'id');
                              $this->addToAlreadyTransfer('Contract', $item_ID, $newcontractID);
                           }
                        }

                        // found : use it
                        // not found : copy contract
                        if ($newcontractID < 0) {
                           // 1 - create new item
                           unset($contract->fields['id']);
                           $input                = $contract->fields;
                           $input['entities_id'] = $this->to;
                           unset($contract->fields);
                           $newcontractID        = $contract->add(toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                           $this->transferItem('Contract', $item_ID, $newcontractID);
                        }

                     }
                  }

                  // Update links
                  if ($ID == $newID) {
                     if ($item_ID != $newcontractID) {
                        $query = "UPDATE `glpi_contracts_items`
                                  SET `contracts_id` = '$newcontractID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }

                  // Same Item -> update links
                  } else {
                     // Copy Item -> copy links
                     if ($item_ID != $newcontractID) {
                        $query = "INSERT INTO `glpi_contracts_items`
                                         (`contracts_id`, `items_id`, `itemtype`)
                                  VALUES ('$newcontractID', '$newID', '$itemtype')";
                        $DB->query($query);

                     } else { // same contract for new item update link
                        $query = "UPDATE `glpi_contracts_items`
                                  SET `items_id` = '$newID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }

                  // If clean and unused ->
                  if ($need_clean_process
                      && $this->options['clean_contract']) {
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `glpi_contracts_items`
                               WHERE `contracts_id` = '$item_ID'";

                     if ($result_remaining = $DB->query($query)) {
                        if ($DB->result($result_remaining, 0, 'cpt') == 0) {
                           if ($this->options['clean_contract'] == 1) {
                              $contract->delete(array('id' => $item_ID));
                           }
                           if ($this->options['clean_contract']==2) { // purge
                              $contract->delete(array('id' => $item_ID), 1);
                           }
                        }
                     }

                  }

               }
            }
         }

      } else {// else unlink
         $query = "DELETE
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '$ID'
                         AND `itemtype` = '$itemtype'";
         $DB->query($query);
      }
   }


   /**
    * Transfer documents
    *
    * @param $itemtype  original type of transfered item
    * @param $ID        original ID of the document
    * @param $newID     new ID of the document
   **/
   function transferDocuments($itemtype, $ID, $newID) {
      global $DB;

      $need_clean_process = false;

      // if keep
      if ($this->options['keep_document']) {
         $document = new Document();
         // Get contracts for the item
         $query = "SELECT *
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '$ID'
                         AND `itemtype` = '$itemtype'
                         AND `documents_id` NOT IN ".$this->item_recurs['Document'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
            // Foreach get item
               while ($data = $DB->fetch_assoc($result)) {
                  $need_clean_process = false;
                  $item_ID            = $data['documents_id'];
                  $newdocID           = -1;

                  // is already transfer ?
                  if (isset($this->already_transfer['Document'][$item_ID])) {
                     $newdocID = $this->already_transfer['Document'][$item_ID];
                     if ($newdocID != $item_ID) {
                        $need_clean_process = true;
                     }

                  } else {
                     // No
                     // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                     $canbetransfer = true;
                     $query = "SELECT DISTINCT `itemtype`
                               FROM `glpi_documents_items`
                               WHERE `documents_id` = '$item_ID'";

                     if ($result_type = $DB->query($query)) {
                        if ($DB->numrows($result_type) >0) {
                           while (($data_type = $DB->fetch_assoc($result_type))
                                  && $canbetransfer) {
                              $dtype = $data_type['itemtype'];
                              if (isset($this->item_search[$dtype])) {
                                 // No items to transfer -> exists links
                                 $query_search = "SELECT COUNT(*) AS cpt
                                                  FROM `glpi_documents_items`
                                                  WHERE `documents_id` = '$item_ID'
                                                        AND `itemtype` = '$dtype'
                                                        AND `items_id`
                                                             NOT IN ".$this->item_search[$dtype];

                                 // contacts, contracts, and enterprises are linked as device.
                                 if (isset($this->item_recurs[$dtype])) {
                                    $query_search .= " AND `items_id`
                                                            NOT IN ".$this->item_recurs[$dtype];
                                 }

                                 $result_search = $DB->query($query_search);
                                 if ($DB->result($result_search, 0, 'cpt') > 0) {
                                    $canbetransfer = false;
                                 }

                              }
                           }
                        }
                     }

                     // Yes : transfer
                     if ($canbetransfer) {
                        $this->transferItem('Document', $item_ID, $item_ID);
                        $newdocID = $item_ID;

                     } else {
                        $need_clean_process = true;
                        $document->getFromDB($item_ID);
                        // No : search contract
                        $query = "SELECT *
                                  FROM `glpi_documents`
                                  WHERE `entities_id` = '".$this->to."'
                                        AND `name` = '".addslashes($document->fields['name'])."'";

                        if ($result_search = $DB->query($query)) {
                           if ($DB->numrows($result_search) > 0) {
                              $newdocID = $DB->result($result_search,0,'id');
                              $this->addToAlreadyTransfer('Document', $item_ID, $newdocID);
                           }
                        }

                        // found : use it
                        // not found : copy doc
                        if ($newdocID < 0) {
                           // 1 - create new item
                           unset($document->fields['id']);
                           $input    = $document->fields;
                           // Not set new entity Do by transferItem
                           unset($document->fields);
                           $newdocID = $document->add(toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                           $this->transferItem('Document', $item_ID, $newdocID);
                        }
                     }
                  }

                  // Update links
                  if ($ID == $newID) {
                     if ($item_ID != $newdocID) {
                        $query = "UPDATE `glpi_documents_items`
                                  SET `documents_id` = '$newdocID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }

                  // Same Item -> update links
                  } else {
                     // Copy Item -> copy links
                     if ($item_ID != $newdocID) {
                        $query = "INSERT INTO `glpi_documents_items`
                                         (`documents_id`, `items_id`, `itemtype`)
                                  VALUES ('$newdocID','$newID','$itemtype')";
                        $DB->query($query);

                     } else { // same doc for new item update link
                        $query = "UPDATE `glpi_documents_items`
                                  SET `items_id` = '$newID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }

                  }

                  // If clean and unused ->
                  if ($need_clean_process
                      && $this->options['clean_document']) {
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `glpi_documents_items`
                               WHERE `documents_id` = '$item_ID'";

                     if ($result_remaining = $DB->query($query)) {
                        if ($DB->result($result_remaining,0,'cpt') == 0) {
                           if ($this->options['clean_document'] == 1) {
                              $document->delete(array('id' => $item_ID));
                           }
                           if ($this->options['clean_document'] == 2) { // purge
                              $document->delete(array('id' => $item_ID), 1);
                           }
                        }
                     }

                  }

               }
            }
         }

      } else {// else unlink
         $query = "DELETE
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '$ID'
                         AND `itemtype` = '$itemtype'";
         $DB->query($query);
      }
   }


   /**
    * Delete direct connection for a linked item
    *
    * @param $itemtype        original type of transfered item
    * @param $ID              ID of the item
    * @param $link_type       type of the linked items to transfer
   **/
   function transferDirectConnection($itemtype ,$ID, $link_type) {
      global $DB;

      // Only same Item case : no duplication of computers
      // Default : delete
      $keep      = 0;
      $clean     = 0;

      switch ($link_type) {
         case 'Printer' :
            $keep      = $this->options['keep_dc_printer'];
            $clean     = $this->options['clean_dc_printer'];
            break;

         case 'Monitor' :
            $keep      = $this->options['keep_dc_monitor'];
            $clean     = $this->options['clean_dc_monitor'];
            break;

         case 'Peripheral' :
            $keep      = $this->options['keep_dc_peripheral'];
            $clean     = $this->options['clean_dc_peripheral'];
            break;

         case 'Phone' :
            $keep  = $this->options['keep_dc_phone'];
            $clean = $this->options['clean_dc_phone'];
            break;
      }

      if (!($link_item = getItemForItemtype($link_type))) {
         return;
      }

      // Get connections
      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$ID'
                      AND `itemtype` = '".$link_type."'";

      if ($link_item->maybeRecursive()) {
         $query .= " AND `items_id` NOT IN ".$this->item_recurs[$link_type];
      }

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            // Foreach get item
            while ($data = $DB->fetch_assoc($result)) {
               $item_ID = $data['items_id'];
               if ($link_item->getFromDB($item_ID)) {
                  // If global :
                  if ($link_item->fields['is_global'] == 1) {
                     $need_clean_process = false;
                     // if keep
                     if ($keep) {
                        $newID = -1;

                        // Is already transfer ?
                        if (isset($this->already_transfer[$link_type][$item_ID])) {
                           $newID = $this->already_transfer[$link_type][$item_ID];
                           // Already transfer as a copy : need clean process
                           if ($newID != $item_ID) {
                              $need_clean_process = true;
                           }

                        } else { // Not yet tranfer
                           // Can be managed like a non global one ?
                           // = all linked computers need to be transfer (so not copy)
                           $query = "SELECT COUNT(*) AS cpt
                                     FROM `glpi_computers_items`
                                     WHERE `itemtype` = '".$link_type."'
                                           AND `items_id` = '$item_ID'
                                           AND `computers_id`
                                                NOT IN ".$this->item_search['Computer'];
                           $result_search = $DB->query($query);

                           // All linked computers need to be transfer -> use unique transfer system
                           if ($DB->result($result_search, 0, 'cpt') == 0) {
                              $need_clean_process = false;
                              $this->transferItem($link_type, $item_ID, $item_ID);
                              $newID = $item_ID;

                           } else { // else Transfer by Copy
                              $need_clean_process = true;
                              // Is existing global item in the destination entity ?
                              $query = "SELECT *
                                        FROM `".getTableForItemType($link_type)."`
                                        WHERE `is_global` = '1'
                                              AND `entities_id` = '".$this->to."'
                                              AND `name`
                                                   = '".addslashes($link_item->getField('name'))."'";

                              if ($result_search = $DB->query($query)) {
                                 if ($DB->numrows($result_search) > 0) {
                                    $newID = $DB->result($result_search, 0, 'id');
                                    $this->addToAlreadyTransfer($link_type, $item_ID, $newID);
                                 }
                              }

                              // Not found -> transfer copy
                              if ($newID <0) {
                                 // 1 - create new item
                                 unset($link_item->fields['id']);
                                 $input                = $link_item->fields;
                                 $input['entities_id'] = $this->to;
                                 unset($link_item->fields);
                                 $newID = $link_item->add(toolbox::addslashes_deep($input));
                                 // 2 - transfer as copy
                                 $this->transferItem($link_type,$item_ID,$newID);
                              }

                              // Founded -> use to link : nothing to do
                           }
                        }

                        // Finish updated link if needed
                        if (($newID > 0)
                            && ($newID != $item_ID)) {
                           $query = "UPDATE `glpi_computers_items`
                                     SET `items_id` = '$newID'
                                     WHERE `id` = '".$data['id']."' ";
                           $DB->query($query);
                        }

                     } else {
                        // Else delete link
                        // Call Disconnect for global device (no disconnect behavior, but history )
                        $conn = new Computer_Item();
                        $conn->delete(array('id'              => $data['id'],
                                            '_no_auto_action' => true));

                        $need_clean_process = true;

                     }
                     // If clean and not linked dc -> delete
                     if ($need_clean_process && $clean) {
                        $query = "SELECT COUNT(*) AS cpt
                                  FROM `glpi_computers_items`
                                  WHERE `items_id` = '$item_ID'
                                        AND `itemtype` = '".$link_type."'";

                        if ($result_dc = $DB->query($query)) {
                           if ($DB->result($result_dc, 0, 'cpt') == 0) {
                              if ($clean == 1) {
                                 $link_item->delete(array('id' => $item_ID));
                              }
                              if ($clean == 2) { // purge
                                 $link_item->delete(array('id' => $item_ID), 1);
                              }
                           }
                        }

                     }

                  } else { // If unique :
                     //if keep -> transfer list else unlink
                     if ($keep) {
                        $this->transferItem($link_type, $item_ID, $item_ID);

                     } else {
                        // Else delete link (apply disconnect behavior)
                        $conn = new Computer_Item();
                        $conn->delete(array('id' => $data['id']));

                        //if clean -> delete
                        if ($clean == 1) {
                           $link_item->delete(array('id' => $item_ID));

                        } else if ($clean == 2) { // purge
                           $link_item->delete(array('id' => $item_ID), 1);
                        }

                     }

                  }

               } else {
                  // Unexisting item / Force disconnect
                  $conn = new Computer_Item();
                  $conn->delete(array('id'             => $data['id'],
                                      '_no_history'    => true,
                                      '_no_auto_action'=> true));
               }

            }
         }
      }
   }


   /**
    * Delete direct connection beetween an item and a computer when transfering the item
    *
    * @param $itemtype        itemtype to tranfer
    * @param $ID              ID of the item
    *
    * @since version 0.84.4
    **/
   function manageConnectionComputer($itemtype ,$ID) {
      global $DB;

      // Get connections
      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` NOT IN ".$this->item_search['Computer']."
                      AND `itemtype` = '".$itemtype."'
                         AND `items_id` = $ID";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            // Foreach get item
            $conn = new Computer_Item();
            $comp = New Computer();
            while ($data = $DB->fetch_assoc($result)) {
               $item_ID = $data['items_id'];
               if ($comp->getFromDB($item_ID)) {
                  $conn->delete(array('id' => $data['id']));
               } else {
                  // Unexisting item / Force disconnect
                  $conn->delete(array('id'             => $data['id'],
                        '_no_history'    => true,
                        '_no_auto_action'=> true));
               }

            }
         }
      }
   }


   /**
    * Delete direct connection for a linked item
    *
    * @param $itemtype  item type
    * @param $ID        ID of the item
   **/
   function deleteDirectConnection($itemtype, $ID) {
      global $DB;

      // Delete Direct connection to computers for item type
      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '$itemtype'";
      $result = $DB->query($query);
   }


   /**
    * Transfer tickets
    *
    * @param $itemtype  type of transfered item
    * @param $ID        original ID of the ticket
    * @param $newID     new ID of the ticket
   **/
   function transferTickets($itemtype, $ID, $newID) {
      global $DB;

      $job   = new Ticket();
      $query = "SELECT *
                FROM `glpi_tickets`
                LEFT JOIN `glpi_items_tickets`
                   ON `glpi_items_tickets`.`tickets_id` = `glpi_tickets`.`id`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '$itemtype'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            switch ($this->options['keep_ticket']) {
               // Transfer
               case 2 :
                  // Same Item / Copy Item -> update entity
                  while ($data = $DB->fetch_assoc($result)) {
                     $input                = $this->transferHelpdeskAdditionalInformations($data);
                     $input['id']          = $data['id'];
                     $input['entities_id'] = $this->to;
                     $input['items_id']    = $newID;
                     $input['itemtype']    = $itemtype;

                     $job->update($input);
                     $this->addToAlreadyTransfer('Ticket', $data['id'], $data['id']);
                     $this->transferTaskCategory('Ticket', $input['id'], $input['id']);
                  }
                  break;

               // Clean ref : keep ticket but clean link
               case 1 :
                  // Same Item / Copy Item : keep and clean ref
                  while ($data = $DB->fetch_assoc($result)) {
                     $job->update(array('id'       => $data['id'],
                                        'itemtype' => 0,
                                        'items_id' => 0));
                     $this->addToAlreadyTransfer('Ticket', $data['id'], $data['id']);
                  }
                  break;

               // Delete
               case 0 :
                  // Same item -> delete
                  if ($ID == $newID) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $job->delete(array('id' => $data['id']));
                     }
                  }
                  // Copy Item : nothing to do
                  break;
            }
         }
      }

   }

   /**
    * Transfer suppliers for specified tickets or problems
    *
    * @since version 0.84
    *
    * @param $itemtype  itemtype : Problem / Ticket
    * @param $ID        original ticket ID
    * @param $newID     new ticket ID
   **/
   function transferLinkedSuppliers($itemtype, $ID, $newID) {
      global $DB;

      switch ($itemtype) {
         case 'Ticket' :
            $table = 'glpi_suppliers_tickets';
            $field = 'tickets_id';
            $link  = new Supplier_Ticket();
            break;

         case 'Problem' :
            $table = 'glpi_problems_suppliers';
            $field = 'problems_id';
            $link  = new Problem_Supplier();
            break;

         case 'Change' :
            $table = 'glpi_changes_suppliers';
            $field = 'changes_id';
            $link  = new Change_Supplier();
            break;
      }

      $query = "SELECT *
                FROM `$table`
                WHERE `$field` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            while ($data = $DB->fetch_assoc($result)) {
               $input = array();

               if ($data['suppliers_id'] > 0) {
                  $supplier = new Supplier();

                  if ($supplier->getFromDB($data['suppliers_id'])) {
                     $newID = -1;
                     $query = "SELECT *
                               FROM `glpi_suppliers`
                               WHERE `entities_id` = '".$this->to."'
                                     AND `name` = '".addslashes($supplier->fields['name'])."'";

                     if ($result_search = $DB->query($query)) {
                        if ($DB->numrows($result_search) > 0) {
                           $newID = $DB->result($result_search,0,'id');
                        }
                     }
                     if ($newID < 0) {
                        // 1 - create new item
                        unset($supplier->fields['id']);
                        $input                 = $supplier->fields;
                        $input['entities_id']  = $this->to;
                        // Not set new entity Do by transferItem
                        unset($supplier->fields);
                        $newID                 = $supplier->add(toolbox::addslashes_deep($input));
                     }

                     $input2['id']           = $data['id'];
                     $input2[$field]         = $ID;
                     $input2['suppliers_id'] = $newID;
                     $link->update($input2);
                  }

               }

            }
         }
      }

   }


   /**
    * Transfer task categories for specified tickets
    *
    * @since version 0.83
    *
    * @param $itemtype  itemtype : Problem / Ticket
    * @param $ID        original ticket ID
    * @param $newID     new ticket ID
   **/
   function transferTaskCategory($itemtype, $ID, $newID) {
      global $DB;

      switch ($itemtype) {
         case 'Ticket' :
            $table = 'glpi_tickettasks';
            $field = 'tickets_id';
            $task  = new TicketTask();
            break;

         case 'Problem' :
            $table = 'glpi_problemtasks';
            $field = 'problems_id';
            $task  = new ProblemTask();
            break;

         case 'Change' :
            $table = 'glpi_changetasks';
            $field = 'changes_id';
            $task  = new ProblemTask();
            break;
      }

      $query = "SELECT *
                FROM `$table`
                WHERE `$field` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            while ($data = $DB->fetch_assoc($result)) {
               $input = array();

               if ($data['taskcategories_id'] > 0) {
                  $categ = new TaskCategory();

                  if ($categ->getFromDB($data['taskcategories_id'])) {
                     $inputcat['entities_id']  = $this->to;
                     $inputcat['completename'] = addslashes($categ->fields['completename']);
                     $catid                    = $categ->findID($inputcat);
                     if ($catid < 0) {
                        $catid = $categ->import($inputcat);
                     }
                     $input['id']                = $data['id'];
                     $input[$field]              = $ID;
                     $input['taskcategories_id'] = $catid;
                     $task->update($input);
                  }

               }

            }
         }
      }

   }


   /**
    * Transfer ticket/problem infos
    *
    * @param $data ticket data fields
    *
    * @since version 0.85 (before transferTicketAdditionalInformations)
   **/
   function transferHelpdeskAdditionalInformations($data) {

      $input               = array();
      $suppliers_id_assign = 0;

//       if ($data['suppliers_id_assign'] > 0) {
//          $suppliers_id_assign = $this->transferSingleSupplier($data['suppliers_id_assign']);
//       }

      // Transfer ticket category
      $catid = 0;
      if ($data['itilcategories_id'] > 0) {
         $categ = new ITILCategory();

         if ($categ->getFromDB($data['itilcategories_id'])) {
            $inputcat['entities_id']  = $this->to;
            $inputcat['completename'] = addslashes($categ->fields['completename']);
            $catid                    = $categ->findID($inputcat);
            if ($catid < 0) {
               $catid = $categ->import($inputcat);
            }
         }

      }

      $input['itilcategories_id'] = $catid;
      return $input;
   }


   /**
    * Transfer history
    *
    * @param $itemtype  original type of transfered item
    * @param $ID        original ID of the history
    * @param $newID     new ID of the history
   **/
   function transferHistory($itemtype, $ID, $newID) {
      global $DB;

      switch ($this->options['keep_history']) {
         // delete
         case 0 :
            // Same item -> delete
            if ($ID == $newID) {
               $query = "DELETE
                         FROM `glpi_logs`
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` = '$ID'";
               $result = $DB->query($query);
            }
            // Copy -> nothing to do
            break;

         // Keep history
         default :
            // Copy -> Copy datas
            if ($ID != $newID) {
               $query = "SELECT *
                         FROM `glpi_logs`
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` = '$ID'";
               $result = $DB->query($query);

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) != 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $data = Toolbox::addslashes_deep($data);
                        $query = "INSERT
                                  INTO `glpi_logs`
                                  (`items_id`, `itemtype`, `itemtype_link`, `linked_action`,
                                   `user_name`, `date_mod`, `id_search_option`, `old_value`,
                                   `new_value`)
                                  VALUES ('$newID', '$itemtype', '".$data['itemtype_link']."',
                                          '".$data['linked_action']."', '". $data['user_name']."',
                                          '".$data['date_mod']."', '".$data['id_search_option']."',
                                          '".$data['old_value']."', '".$data['new_value']."')";
                        $DB->query($query);
                     }
                  }
               }

            }
            // Same item -> nothing to do
            break;
      }
   }


   /**
    * Transfer compatible printers for a cartridge type
    *
    * @param $ID     original ID of the cartridge type
    * @param $newID  new ID of the cartridge type
   **/
   function transferCompatiblePrinters($ID, $newID) {
      global $DB;

      if ($ID != $newID) {
         $query = "SELECT *
                   FROM `glpi_cartridgeitems_printermodels`
                   WHERE `cartridgeitems_id` = '$ID'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) != 0) {
               $cartitem = new CartridgeItem();

               while ($data = $DB->fetch_assoc($result)) {
                  $data = Toolbox::addslashes_deep($data);
                  $cartitem->addCompatibleType($newID, $data["printermodels_id"]);
               }

            }
         }

      }
   }


   /**
    * Transfer infocoms of an item
    *
    * @param $itemtype  type of the item to transfer
    * @param $ID        original ID of the item
    * @param $newID     new ID of the item
   **/
   function transferInfocoms($itemtype, $ID, $newID) {
      global $DB;

      $ic = new Infocom();
      if ($ic->getFromDBforDevice($itemtype,$ID)) {
         switch ($this->options['keep_infocom']) {
            // delete
            case 0 :
               // Same item -> delete
               if ($ID == $newID) {
                  $query = "DELETE
                            FROM `glpi_infocoms`
                            WHERE `itemtype` = '$itemtype'
                                  AND `items_id` = '$ID'";
                  $result = $DB->query($query);
               }
               // Copy : nothing to do
               break;

            // Keep
            default :
               // transfer enterprise
               $suppliers_id = 0;
               if ($ic->fields['suppliers_id'] > 0) {
                  $suppliers_id = $this->transferSingleSupplier($ic->fields['suppliers_id']);
               }

               // Copy : copy infocoms
               if ($ID != $newID) {
                  // Copy items
                  $input                 = $ic->fields;
                  $input['items_id']     = $newID;
                  $input['suppliers_id'] = $suppliers_id;
                  unset($input['id']);
                  unset($ic->fields);
                  $ic->add(toolbox::addslashes_deep($input));

               } else {
                  // Same Item : manage only enterprise move
                  // Update enterprise
                  if (($suppliers_id > 0)
                      && ($suppliers_id != $ic->fields['suppliers_id'])) {
                     $ic->update(array('id'           => $ic->fields['id'],
                                       'suppliers_id' => $suppliers_id));
                  }
               }

               break;
         }
      }
   }


   /**
    * Transfer an enterprise
    *
    * @param $ID ID of the enterprise
   **/
   function transferSingleSupplier($ID) {
      global $DB, $CFG_GLPI;

      // TODO clean system : needed ?
      $ent = new Supplier();
      if ($this->options['keep_supplier']
          && $ent->getFromDB($ID)) {

         if (isset($this->noneedtobe_transfer['Supplier'][$ID])) {
            // recursive enterprise
            return $ID;
         }
         if (isset($this->already_transfer['Supplier'][$ID])) {
            // Already transfer
            return $this->already_transfer['Supplier'][$ID];
         }

         $newID           = -1;
         // Not already transfer
         $links_remaining = 0;
         // All linked items need to be transfer so transfer enterprise ?
         // Search for contract
         $query = "SELECT COUNT(*) AS cpt
                   FROM `glpi_contracts_suppliers`
                   WHERE `suppliers_id` = '$ID'
                         AND `contracts_id` NOT IN ".$this->item_search['Contract'];
         $result_search   = $DB->query($query);
         $links_remaining = $DB->result($result_search, 0, 'cpt');

         if ($links_remaining == 0) {
            // Search for infocoms
            if ($this->options['keep_infocom']) {
               foreach (Infocom::getItemtypesThatCanHave() as $itemtype) {
                  if (isset($this->item_search[$itemtype])) {
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `glpi_infocoms`
                               WHERE `suppliers_id` = '$ID'
                                     AND `itemtype` = '$itemtype'
                                     AND `items_id` NOT IN ".$this->item_search[$itemtype];

                     if ($result_search = $DB->query($query)) {
                        $links_remaining += $DB->result($result_search, 0,' cpt');
                     }
                  }
               }
            }
         }

         // All linked items need to be transfer -> use unique transfer system
         if ($links_remaining == 0) {
            $this->transferItem('Supplier', $ID, $ID);
            $newID = $ID;

         } else { // else Transfer by Copy
            // Is existing item in the destination entity ?
            $query = "SELECT *
                      FROM `glpi_suppliers`
                      WHERE `entities_id` = '".$this->to."'
                            AND `name` = '".addslashes($ent->fields['name'])."'";

            if ($result_search = $DB->query($query)) {
               if ($DB->numrows($result_search) > 0) {
                  $newID = $DB->result($result_search, 0, 'id');
                  $this->addToAlreadyTransfer('Supplier', $ID, $newID);
               }
            }

            // Not found -> transfer copy
            if ($newID < 0) {
               // 1 - create new item
               unset($ent->fields['id']);
               $input                = $ent->fields;
               $input['entities_id'] = $this->to;
               unset($ent->fields);
               $newID                = $ent->add(toolbox::addslashes_deep($input));
               // 2 - transfer as copy
               $this->transferItem('Supplier',$ID,$newID);
            }

            // Founded -> use to link : nothing to do
         }
         return $newID;
      }
      return 0;
   }


   /**
    * Transfer contacts of an enterprise
    *
    * @param $ID     original ID of the enterprise
    * @param $newID  new ID of the enterprise
   **/
   function transferSupplierContacts($ID, $newID) {
      global $DB;

      $need_clean_process = false;
      // if keep
      if ($this->options['keep_contact']) {
         $contact = new Contact();
         // Get contracts for the item
         $query = "SELECT *
                   FROM `glpi_contacts_suppliers`
                   WHERE `suppliers_id` = '$ID'
                         AND `contacts_id` NOT IN " . $this->item_recurs['Contact'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // Foreach get item
               while ($data = $DB->fetch_assoc($result)) {
                  $need_clean_process = false;
                  $item_ID            = $data['contacts_id'];
                  $newcontactID       = -1;

                  // is already transfer ?
                  if (isset($this->already_transfer['Contact'][$item_ID])) {
                     $newcontactID = $this->already_transfer['Contact'][$item_ID];
                     if ($newcontactID != $item_ID) {
                        $need_clean_process = true;
                     }

                  } else {
                     $canbetransfer = true;
                     // Transfer enterprise : is the contact used for another enterprise ?
                     if ($ID == $newID) {
                        $query_search = "SELECT COUNT(*) AS cpt
                                         FROM `glpi_contacts_suppliers`
                                         WHERE `contacts_id` = '$item_ID'
                                               AND `suppliers_id`
                                                    NOT IN ".$this->item_search['Supplier'] ."
                                               AND `suppliers_id`
                                                    NOT IN ".$this->item_recurs['Supplier'];
                        $result_search = $DB->query($query_search);
                        if ($DB->result($result_search, 0, 'cpt') > 0) {
                           $canbetransfer = false;
                        }
                     }

                     // Yes : transfer
                     if ($canbetransfer) {
                        $this->transferItem('Contact',$item_ID,$item_ID);
                        $newcontactID = $item_ID;

                     } else {
                        $need_clean_process = true;
                        $contact->getFromDB($item_ID);
                        // No : search contract
                        $query = "SELECT *
                                  FROM `glpi_contacts`
                                  WHERE `entities_id` = '".$this->to."'
                                        AND `name` = '".addslashes($contact->fields['name'])."'
                                        AND `firstname`
                                                = '".addslashes($contact->fields['firstname'])."'";

                        if ($result_search = $DB->query($query)) {
                           if ($DB->numrows($result_search) > 0) {
                              $newcontactID = $DB->result($result_search, 0, 'id');
                              $this->addToAlreadyTransfer('Contact', $item_ID, $newcontactID);
                           }
                        }

                        // found : use it
                        // not found : copy contract
                        if ($newcontactID < 0) {
                           // 1 - create new item
                           unset($contact->fields['id']);
                           $input                = $contact->fields;
                           $input['entities_id'] = $this->to;
                           unset($contact->fields);
                           $newcontactID         = $contact->add(toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                           $this->transferItem('Contact',$item_ID,$newcontactID);
                        }

                     }
                  }

                  // Update links
                  if ($ID == $newID) {
                     if ($item_ID != $newcontactID) {
                        $query = "UPDATE `glpi_contacts_suppliers`
                                  SET `contacts_id` = '$newcontactID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }

                  // Same Item -> update links
                  } else {
                     // Copy Item -> copy links
                     if ($item_ID != $newcontactID) {
                        $query = "INSERT INTO `glpi_contacts_suppliers`
                                         (`contacts_id`, `suppliers_id`)
                                  VALUES ('$newcontactID','$newID')";
                        $DB->query($query);

                     } else { // transfer contact but copy enterprise : update link
                        $query = "UPDATE `glpi_contacts_suppliers`
                                  SET `suppliers_id` = '$newID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }

                  // If clean and unused ->
                  if ($need_clean_process
                      && $this->options['clean_contact']) {
                     $query = "SELECT COUNT(*) AS cpt
                               FROM `glpi_contacts_suppliers`
                               WHERE `contacts_id` = '$item_ID'";

                     if ($result_remaining = $DB->query($query)) {
                        if ($DB->result($result_remaining,0,'cpt') == 0) {
                           if ($this->options['clean_contact'] == 1) {
                              $contact->delete(array('id' => $item_ID));
                           }
                           if ($this->options['clean_contact'] == 2) { // purge
                              $contact->delete(array('id' => $item_ID), 1);
                           }
                        }
                     }

                  }

               }
            }
         }

      } else {// else unlink
         $query = "DELETE
                   FROM `glpi_contacts_suppliers`
                   WHERE `suppliers_id` = '$ID'";
         $DB->query($query);
      }
   }


   /**
    * Transfer reservations of an item
    *
    * @param $itemtype  original type of transfered item
    * @param $ID        original ID of the item
    * @param $newID     new ID of the item
   **/
   function transferReservations($itemtype, $ID, $newID) {
      global $DB;

      $ri = new ReservationItem();

      if ($ri->getFromDBbyItem($itemtype,$ID)) {
         switch ($this->options['keep_reservation']) {
            // delete
            case 0 :
               // Same item -> delete
               if ($ID == $newID) {
                  $ri->delete(array('id' => $ri->fields['id']));
               }
               // Copy : nothing to do
               break;

            // Keep
            default :
               // Copy : set item as reservable
               if ($ID != $newID) {
                  $input['itemtype']  = $itemtype;
                  $input['items_id']  = $newID;
                  $input['is_active'] = $ri->fields['is_active'];
                  unset($ri->fields);
                  $ri->add(toolbox::addslashes_deep($input));
               }
               // Same item -> nothing to do
               break;
         }
      }
   }


   /**
    * Transfer devices of an item
    *
    * @param $itemtype        original type of transfered item
    * @param $ID              ID of the item
    * @param $newID           new ID of the item
   **/
   function transferDevices($itemtype, $ID, $newID) {
      global $DB, $CFG_GLPI;

      // Only same case because no duplication of computers
      switch ($this->options['keep_device']) {
         // delete devices
         case 0 :
            foreach (Item_Devices::getItemAffinities($itemtype) as $type) {
               $table = getTableForItemType($type);
               $query = "DELETE
                         FROM `$table`
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` = '$ID'";
               $result = $DB->query($query);
            }

         // Keep devices
         default :
            foreach (Item_Devices::getItemAffinities($itemtype) as $itemdevicetype) {
               $itemdevicetable = getTableForItemType($itemdevicetype);
               $devicetype      = $itemdevicetype::getDeviceType();
               $devicetable     = getTableForItemType($devicetype);
               $fk              = getForeignKeyFieldForTable($devicetable);

               $device          = new $devicetype();
               // Get contracts for the item
               $query = "SELECT *
                         FROM `$itemdevicetable`
                         WHERE `items_id` = '$ID'
                               AND `itemtype` = '$itemtype'
                               AND `$fk` NOT IN ".$this->item_recurs[$devicetype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     // Foreach get item
                     while ($data = $DB->fetch_assoc($result)) {
                        $item_ID     = $data[$fk];
                        $newdeviceID = -1;

                        // is already transfer ?
                        if (isset($this->already_transfer[$devicetype][$item_ID])) {
                           $newdeviceID = $this->already_transfer[$devicetype][$item_ID];

                        } else {
                           // No
                           // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                           $canbetransfer = true;
                           $query = "SELECT DISTINCT `itemtype`
                                     FROM `$itemdevicetable`
                                     WHERE `$fk` = '$item_ID'";

                           if ($result_type = $DB->query($query)) {
                              if ($DB->numrows($result_type) > 0) {
                                 while (($data_type = $DB->fetch_assoc($result_type))
                                        && $canbetransfer) {
                                    $dtype = $data_type['itemtype'];

                                    if (isset($this->item_search[$dtype])) {
                                       // No items to transfer -> exists links
                                       $query_search = "SELECT COUNT(*) AS cpt
                                                        FROM `$itemdevicetable`
                                                        WHERE `$fk` = '$item_ID'
                                                              AND `itemtype` = '$dtype'
                                                              AND `items_id`
                                                                  NOT IN ".$this->item_search[$dtype];
                                       $result_search = $DB->query($query_search);

                                       if ($DB->result($result_search, 0, 'cpt') > 0) {
                                          $canbetransfer = false;
                                       }

                                    } else {
                                       $canbetransfer = false;
                                    }

                                 }
                              }
                           }

                           // Yes : transfer
                           if ($canbetransfer) {
                              $this->transferItem($devicetype, $item_ID, $item_ID);
                              $newdeviceID = $item_ID;

                           } else {
                              $device->getFromDB($item_ID);
                              // No : search device
                              $field = "name";
                              if (!FieldExists($devicetable, "name")) {
                                 $field = "designation";
                              }

                              $query = "SELECT *
                                        FROM `$devicetable`
                                        WHERE `entities_id` = '".$this->to."'
                                              AND `".$field."` = '".addslashes($device->fields[$field])."'";

                              if ($result_search = $DB->query($query)) {
                                 if ($DB->numrows($result_search) > 0) {
                                    $newdeviceID = $DB->result($result_search, 0, 'id');
                                    $this->addToAlreadyTransfer($devicetype, $item_ID, $newdeviceID);
                                 }
                              }

                              // found : use it
                              // not found : copy contract
                              if ($newdeviceID < 0) {
                                 // 1 - create new item
                                 unset($device->fields['id']);
                                 $input                = $device->fields;
                                 // Fix for fields with NULL in DB
                                 foreach ($input as $key=>$value) {
                                    if ($value == '') {
                                       unset($input[$key]);
                                    }
                                 }
                                 $input['entities_id'] = $this->to;
                                 unset($device->fields);
                                 $newdeviceID = $device->add(Toolbox::addslashes_deep($input));
                                 // 2 - transfer as copy
                                 $this->transferItem($devicetype, $item_ID, $newdeviceID);
                              }
                           }
                        }

                        // Update links
                        $query = "UPDATE `$itemdevicetable`
                                  SET `$fk` = '$newdeviceID',
                                      `items_id` = '$newID'
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                        $this->transferItem($itemdevicetype, $data['id'], $data['id']);
                     }
                  }
               }
            }
            break;
      }
   }


   /**
    * Transfer network links
    *
    * @param $itemtype     original type of transfered item
    * @param $ID           original ID of the item
    * @param $newID        new ID of the item
   **/
   function transferNetworkLink($itemtype, $ID, $newID) {
      global $DB;
      /// TODO manage with new network system
      $np = new NetworkPort();
      $nn = new NetworkPort_NetworkPort();

      $query = "SELECT `glpi_networkports`.*, `glpi_networkportethernets`.`netpoints_id`
                FROM `glpi_networkports`
                LEFT JOIN `glpi_networkportethernets`
                  ON (`glpi_networkports`.`id` = `glpi_networkportethernets`.`networkports_id`)
                WHERE `glpi_networkports`.`items_id` = '$ID'
                      AND `glpi_networkports`.`itemtype` = '$itemtype'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {

            switch ($this->options['keep_networklink']) {
               // Delete netport
               case 0 :
                  // Not a copy -> delete
                  if ($ID == $newID) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $np->delete(array('id' => $data['id']));
                     }
                  }
                  // Copy -> do nothing
                  break;

               // Disconnect
               case 1 :
                  // Not a copy -> disconnect
                  if ($ID == $newID) {
                     while ($data = $DB->fetch_assoc($result)) {
                        if ($nn->getFromDBForNetworkPort($data['id'])) {
                           $nn->delete($data);
                        }
                        if ($data['netpoints_id']) {
                           $netpointID  = $this->transferDropdownNetpoint($data['netpoints_id']);
                           $input['id']           = $data['id'];
                           $input['netpoints_id'] = $netpointID;
                           $np->update($input);
                        }
                     }
                  } else { // Copy -> copy netports
                     while ($data = $DB->fetch_assoc($result)) {
                        $data             = Toolbox::addslashes_deep($data);
                        unset($data['id']);
                        $data['items_id'] = $newID;
                        $data['netpoints_id']
                                          = $this->transferDropdownNetpoint($data['netpoints_id']);
                        unset($np->fields);
                        $np->add(toolbox::addslashes_deep($data));
                     }
                  }
                  break;

               // Keep network links
               default :
                  // Copy -> Copy netpoints (do not keep links)
                  if ($ID != $newID) {
                     while ($data = $DB->fetch_assoc($result)) {
                        unset($data['id']);
                        $data['items_id'] = $newID;
                        $data['netpoints_id']
                                          = $this->transferDropdownNetpoint($data['netpoints_id']);
                        unset($np->fields);
                        $np->add(toolbox::addslashes_deep($data));
                     }
                  } else {
                     while ($data = $DB->fetch_assoc($result)) {
                        // Not a copy -> only update netpoint
                        if ($data['netpoints_id']) {
                           $netpointID  = $this->transferDropdownNetpoint($data['netpoints_id']);
                           $input['id']           = $data['id'];
                           $input['netpoints_id'] = $netpointID;
                           $np->update($input);
                        }
                     }
                  }
               }

         }
      }
   }


   /**
    * Print the transfer form
    *
    * @param $ID        integer : Id of the contact to print
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $edit_form = true;
      if (!strpos($_SERVER['HTTP_REFERER'],"transfer.form.php")) {
         $edit_form = false;
      }

      $this->initForm($ID, $options);

      $params = array();
      if (!Session::haveRightsOr("transfer", array(CREATE, UPDATE, PURGE))) {
         $params['readonly'] = true;
      }

      if ($edit_form) {
         $this->showFormHeader($options);

      } else {
         echo "<form method='post' name=form action='".$options['target']."'>";
         echo "<div class='center' id='tabsbody' >";
         echo "<table class='tab_cadre_fixe'>";

         echo "<tr><td class='tab_bg_2 top' colspan='4'>";
         echo "<div class='center'>";
         Entity::dropdown(array('name' => 'to_entity'));
         echo "&nbsp;<input type='submit' name='transfer' value=\"".__s('Execute')."\"
                      class='submit'></div>";
         echo "</td></tr>";
      }

      if ($edit_form) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Name')."</td><td>";
         Html::autocompletionTextField($this, "name");
         echo "</td>";
         echo "<td rowspan='3' class='middle right'>".__('Comments')."</td>";
         echo "<td class='center middle' rowspan='3'>
               <textarea cols='45' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>".($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
         echo "</td></tr>";
      }

      $keep  = array(0 => _x('button', 'Delete permanently'),
                     1 => __('Preserve'));

      $clean = array(0 => __('Preserve'),
                     1 => _x('button', 'Put in dustbin'),
                     2 => _x('button', 'Delete permanently'));

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Historical')."</td><td>";
      $params['value'] = $this->fields['keep_history'];
      Dropdown::showFromArray('keep_history', $keep,$params);
      echo "</td>";
      if (!$edit_form) {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center b'>".__('Assets')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Network port', 'Network ports', Session::getPluralNumber())."</td><td>";
      $options = array(0 => _x('button', 'Delete permanently'),
                       1 => _x('button', 'Disconnect') ,
                       2 => __('Keep') );
      $params['value'] = $this->fields['keep_networklink'];
      Dropdown::showFromArray('keep_networklink',$options,$params);
      echo "</td>";
      echo "<td>"._n('Ticket', 'Tickets', Session::getPluralNumber())."</td><td>";
      $options = array(0 => _x('button', 'Delete permanently'),
                       1 => _x('button', 'Disconnect') ,
                       2 => __('Keep') );
      $params['value'] = $this->fields['keep_ticket'];
      Dropdown::showFromArray('keep_ticket',$options,$params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Software of computers')."</td><td>";
      $params['value'] = $this->fields['keep_software'];
      Dropdown::showFromArray('keep_software', $keep,$params);
      echo "</td>";
      echo "<td>".__('If software are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_software'];
      Dropdown::showFromArray('clean_software', $clean,$params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Reservation', 'Reservations', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_reservation'];
      Dropdown::showFromArray('keep_reservation',$keep, $params);
      echo "</td>";
      echo "<td>"._n('Component', 'Components', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_device'];
      Dropdown::showFromArray('keep_device',$keep, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Links between printers and cartridge types and cartridges');
      echo "</td><td>";
      $params['value'] = $this->fields['keep_cartridgeitem'];
      Dropdown::showFromArray('keep_cartridgeitem', $keep, $params);
      echo "</td>";
      echo "<td>".__('If the cartridge types are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_cartridgeitem'];
      Dropdown::showFromArray('clean_cartridgeitem', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Links between cartridge types and cartridges')."</td><td>";
      $params['value'] = $this->fields['keep_cartridge'];
      Dropdown::showFromArray('keep_cartridge', $keep, $params);
      echo "</td>";
      echo "<td>".__('Financial and administrative information')."</td><td>";
      $params['value'] = $this->fields['keep_infocom'];
      Dropdown::showFromArray('keep_infocom', $keep, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Links between consumable types and consumables')."</td><td>";
      $params['value'] = $this->fields['keep_consumable'];
      Dropdown::showFromArray('keep_consumable', $keep, $params);
      echo "</td>";
      echo "<td>".__('Links between computers and volumes')."</td><td>";
      $params['value'] = $this->fields['keep_disk'];
      Dropdown::showFromArray('keep_disk', $keep, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center b'>".__('Direct connections')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Monitor', 'Monitors', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_dc_monitor'];
      Dropdown::showFromArray('keep_dc_monitor', $keep, $params);
      echo "</td>";
      echo "<td>".__('If monitors are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_dc_monitor'];
      Dropdown::showFromArray('clean_dc_monitor', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Printer', 'Printers', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_dc_printer'];
      Dropdown::showFromArray('keep_dc_printer', $keep, $params);
      echo "</td>";
      echo "<td>".__('If printers are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_dc_printer'];
      Dropdown::showFromArray('clean_dc_printer', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Device', 'Devices', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_dc_peripheral'];
      Dropdown::showFromArray('keep_dc_peripheral', $keep, $params);
      echo "</td>";
      echo "<td>".__('If devices are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_dc_peripheral'];
      Dropdown::showFromArray('clean_dc_peripheral', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Phone', 'Phones', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_dc_phone'];
      Dropdown::showFromArray('keep_dc_phone', $keep, $params);
      echo "</td>";
      echo "<td>".__('If phones are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_dc_phone'];
      Dropdown::showFromArray('clean_dc_phone', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center b'>".__('Management')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Supplier', 'Suppliers', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_supplier'];
      Dropdown::showFromArray('keep_supplier', $keep, $params);
      echo "</td>";
      echo "<td>".__('If suppliers are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_supplier'];
      Dropdown::showFromArray('clean_supplier', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Links between suppliers and contacts')."&nbsp;:</td><td>";
      $params['value'] = $this->fields['keep_contact'];
      Dropdown::showFromArray('keep_contact', $keep, $params);
      echo "</td>";
      echo "<td>".__('If contacts are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_contact'];
      Dropdown::showFromArray('clean_contact', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Document', 'Documents', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_document'];
      Dropdown::showFromArray('keep_document', $keep, $params);
      echo "</td>";
      echo "<td>".__('If documents are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_document'];
      Dropdown::showFromArray('clean_document', $clean, $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Contract', 'Contracts', Session::getPluralNumber())."</td><td>";
      $params['value'] = $this->fields['keep_contract'];
      Dropdown::showFromArray('keep_contract', $keep, $params);
      echo "</td>";
      echo "<td>".__('If contracts are no longer used')."</td><td>";
      $params['value'] = $this->fields['clean_contract'];
      Dropdown::showFromArray('clean_contract', $clean, $params);
      echo "</td></tr>";

      if ($edit_form) {
         $this->showFormButtons($options);
      } else {
         echo "</table></div>";
         Html::closeForm();
      }
   }


/// Display items to transfers
   function showTransferList() {
      global $DB, $CFG_GLPI;

      if (isset($_SESSION['glpitransfer_list']) && count($_SESSION['glpitransfer_list'])) {
         echo "<div class='center b'>".
                __('You can continue to add elements to be transferred or execute the transfer now');
         echo "<br>".__('Think of making a backup before transferring items.')."</div>";
         echo "<table class='tab_cadre_fixe' >";
         echo '<tr><th>'.__('Items to transfer').'</th><th>'.__('Transfer mode')."&nbsp;";
         $rand = Transfer::dropdown(array('name'     => 'id',
                                          'comments' => false,
                                          'toupdate' => array('value_fieldname'
                                                                           => 'id',
                                                              'to_update'  => "transfer_form",
                                                              'url'        => $CFG_GLPI["root_doc"].
                                                                              "/ajax/transfers.php")));
         echo '</th></tr>';

         echo "<tr><td class='tab_bg_1 top'>";
         foreach ($_SESSION['glpitransfer_list'] as $itemtype => $tab) {
            if (count($tab)) {
               $table = getTableForItemType($itemtype);
               $query = "SELECT `$table`.`id`,
                                 `$table`.`name`,
                                 `glpi_entities`.`completename` AS locname,
                                 `glpi_entities`.`id` AS entID
                          FROM `$table`
                          LEFT JOIN `glpi_entities`
                               ON (`$table`.`entities_id` = `glpi_entities`.`id`)
                          WHERE `$table`.`id` IN ".$this->createSearchConditionUsingArray($tab)."
                         ORDER BY locname, `$table`.`name`";
               $entID = -1;

               if (!($item = getItemForItemtype($itemtype))) {
                  continue;
               }

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)) {
                     echo '<h3>'.$item->getTypeName().'</h3>';
                     while ($data = $DB->fetch_assoc($result)) {
                        if ($entID != $data['entID']) {
                           if ($entID != -1) {
                              echo '<br>';
                           }
                           $entID = $data['entID'];
                           echo "<span class='b spaced'>".$data['locname']."</span><br>";
                        }
                        echo ($data['name'] ? $data['name'] : "(".$data['id'].")")."<br>";
                     }
                  }
               }

            }
         }
         echo "</td><td class='tab_bg_2 top'>";

         if (countElementsInTable('glpi_transfers') == 0) {
            _e('No item found');
         } else {
            $params = array('id' => '__VALUE__');
            Ajax::updateItemOnSelectEvent("dropdown_id$rand", "transfer_form",
                                          $CFG_GLPI["root_doc"]."/ajax/transfers.php", $params);
         }

         echo "<div class='center' id='transfer_form'><br>";
         Html::showSimpleForm($CFG_GLPI["root_doc"]."/front/transfer.action.php", 'clear',
                              __('To empty the list of elements to be transferred'));
         echo "</div>";
         echo '</td></tr>';
         echo '</table>';

      } else {
         _e('No selected element or badly defined operation');
      }
   }


}
?>
