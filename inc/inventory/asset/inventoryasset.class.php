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

use Agent;
use Blacklist;
use CommonDBTM;
use Dropdown;
use Glpi\Inventory\Conf;
use Manufacturer;
use OperatingSystemKernelVersion;

abstract class InventoryAsset
{
   /** @var array */
   protected $data = [];
   /** @var CommonDBTM */
   protected $item;
   /** @var string */
   protected $itemtype;
   /** @var array */
   protected $extra_data = [];
   /** @var \Agent */
   protected $agent;
   /** @var integer */
   protected $entities_id = 0;
   /** @var boolean */
   protected $links_handled = false;
   /** @var boolean */
   protected $with_history = true;
   /** @var InventoryAsset */
   protected $main_asset;

   /**
    * Constructor
    *
    * @param array $data Data part, optional
    */
   public function __construct(CommonDBTM $item, array $data = null) {
      $this->item = $item;
      if ($data !== null) {
         $this->data = $data;
      }
   }

   /**
    * Set data from raw data part
    *
    * @param array $data Data part
    *
    * @return InventoryAsset
    */
   public function setData(array $data): InventoryAsset {
      $this->data = $data;
      return $this;
   }

   /**
    * Get current data
    *
    * @return array
    */
   public function getData(): array {
      return $this->data;
   }

   /**
    * Prepare data from raw data part
    *
    * @return array
    */
   abstract public function prepare() :array;

   /**
    * Handle in database
    *
    * @return void
    */
   abstract public function handle();

   /**
    * Set extra sub parts of interest
    * Only declared types in subclass extra_data are handled
    *
    * @param array $data Processed data
    *
    * @return InventoryAsset
    */
   public function setExtraData($data): InventoryAsset {
      foreach (array_keys($this->extra_data) as $extra) {
         if (isset($data[$extra])) {
            $this->extra_data[$extra] = $data[$extra];
         }
      }
      return $this;
   }

   /**
    * Get ignore list declared from asset
    *
    * @param string $type Ignore type ("controllers" only for now)
    *
    * @return array
    */
   public function getIgnored($type): array {
      return $this->ignored[$type] ?? [];
   }

   /**
    * Check if configuration allows that part
    *
    * @param Conf $conf Conf instance
    *
    * @return boolean
    */
   abstract public function checkConf(Conf $conf): bool;

   /**
    * Handle links (manufacturers, models, users, ...), create items if needed
    *
    * @return array
    */
   public function handleLinks() {
      $knowns = [];

      //$blacklists = Blacklist::getBlacklists();
      $blacklist = new Blacklist();

      $data = $this->data;
      foreach ($data as &$value) {
         $blacklist->processBlackList($value);
         // save raw manufacture name before its replacement by id for importing model
         // (we need manufacturers name in when importing model in dictionary)
         $manufacturer_name = "";
         if (property_exists($value, 'manufacturers_id')) {
            $manufacturer_name = $value->manufacturers_id;
         }

         foreach ($value as $key => &$val) {
            if ($val instanceof \stdClass || is_array($val)) {
               continue;
            }

            if ($key == "manufacturers_id" || $key == 'bios_manufacturers_id') {
               $manufacturer = new Manufacturer();
               $value->$key  = $manufacturer->processName($value->$key);
               if ($key == 'bios_manufacturers_id') {
                  $this->foreignkey_itemtype[$key] = getItemtypeForForeignKeyField('manufacturers_id');
               }
            }
            if (!is_numeric($val)) {
               $known_key = md5($key . $val);
               if (isset($knowns[$known_key])) {
                  $value->$key = $knowns[$known_key];
                  continue;
               }

               $entities_id = $this->entities_id;
               if ($key == "locations_id") {
                  $value->$key = Dropdown::importExternal('Location', $value->$key, $entities_id);
               } else if (preg_match('/^.+models_id/', $key)) {
                  // models that need manufacturer relation for dictionary import
                  // see CommonDCModelDropdown::$additional_fields_for_dictionnary
                  $value->$key = Dropdown::importExternal(
                     getItemtypeForForeignKeyField($key),
                     $value->$key,
                     $entities_id,
                     ['manufacturer' => $manufacturer_name]
                  );
               } else if (isset($this->foreignkey_itemtype[$key])) {
                  $value->$key = Dropdown::importExternal($this->foreignkey_itemtype[$key], addslashes($value->$key), $entities_id);
               } else if (isForeignKeyField($key) && $key != "users_id") {
                  $this->foreignkey_itemtype[$key] = getItemtypeForForeignKeyField($key);
                  $value->$key = Dropdown::importExternal($this->foreignkey_itemtype[$key], addslashes($value->$key), $entities_id);

                  if ($key == 'operatingsystemkernelversions_id'
                     && property_exists($value, 'operatingsystemkernels_id')
                     && (int)$value->$key > 0
                  ) {
                     $kversion = new OperatingSystemKernelVersion();
                     $kversion->getFromDB($value->$key);
                     if ($kversion->fields['operatingsystemkernels_id'] != $value->operatingsystemkernels_id) {
                        $kversion->update([
                           'id'                          => $kversion->getID(),
                           'operatingsystemkernels_id'   => $value->operatingsystemkernels_id
                        ], $this->withHistory());
                     }
                  }
               }
               $knowns[$known_key] = $value->$key;
            }
         }
      }
      $this->links_handled = true;
      return $this->data;
   }

   /**
    * Set agent
    *
    * @param Agent $agent Agent instance
    *
    * @return $this
    */
   public function setAgent(Agent $agent): InventoryAsset {
      $this->agent = $agent;
      return $this;
   }

   /**
    * Get agent
    *
    * @return Agent
    */
   public function getAgent(): Agent {
      return $this->agent;
   }

   /**
    * Set entity id from main asset
    *
    * @param integer $id Entity ID
    *
    * @return $this
    */
   public function setEntityID($id): InventoryAsset {
      $this->entities_id = $id;
      return $this;
   }

   /**
    * Are link handled already (call to handleLinks should happen only once
    *
    * @return boolean
    */
   public function areLinksHandled(): bool {
      return $this->links_handled;
   }

   /**
    * Is history enabled on this asset?
    *
    * @param boolean|null $bool To change with_history
    *
    * @return boolean
    */
   public function withHistory($bool = null): bool {
      if ($bool !== null) {
         $this->with_history = (bool)$bool;
      }
      return $this->with_history;
   }

   /**
    * Set item and itemtype
    *
    * @param CommonDBTM $item Item instance
    *
    * @return InventoryAsset
    */
   protected function setItem(CommonDBTM $item): self {
      $this->item = $item;
      $this->itemtype = $item->getType();
      return $this;
   }

   /**
    * Set inventory item
    *
    * @param InventoryAsset $mainitem Main inventory asset instance
    *
    * @return InventoryAsset
    */
   public function setMainAsset(InventoryAsset $mainasset): self {
       $this->main_asset = $mainasset;
       return $this;
   }

   /**
    * Get main inventory asset
    *
    * @return InventoryAsset
    */
   public function getMainAsset(): InventoryAsset {
      return $this->main_asset;
   }
}
