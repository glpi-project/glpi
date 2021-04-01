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

use DBmysqlIterator;
use Dropdown;
use Entity;
use FieldUnicity;
use Glpi\Inventory\Conf;
use QueryParam;
use RuleDictionnarySoftwareCollection;
use Software as GSoftware;
use SoftwareVersion;
use Toolbox;
use Glpi\Inventory\Asset\OperatingSystem;

class Software extends InventoryAsset
{
   const SEPARATOR = '$$$$';

   private $softwares = [];
   private $versions = [];
   private $entities_id_software;
   private $disable_unicity_check = false;
   private $assetlink_stmt;

   /** @var array */
   protected $extra_data = [
      OperatingSystem::class => null
   ];

   public function prepare() :array {
      $mapping = [
         'publisher'       => 'manufacturers_id',
         'comments'        => 'comment',
         'install_date'     => 'date_install',
         'system_category' => '_system_category'
      ];

      //Dictionnary for softwares
      $rulecollection = new RuleDictionnarySoftwareCollection();

      //Get the default entity for softwares, as defined in entity configuration
      $entities_id = $this->entities_id;
      $entities_id_software = Entity::getUsedConfig(
         'entities_id_software',
         $entities_id
      );

      //By default a software is not recursive
      $is_recursive = 0;

      //Configuration says that software can be created in the computer's entity
      if ($entities_id_software < 0) {
         //inherit from main asset's entity
         $entities_id_software = $entities_id;
      } else if ($entities_id_software != $entities_id) {
         //Software created in an different entity than main asset one
         $is_recursive = 1;
      }
      $this->entities_id_software = $entities_id_software;

      //Count the number of software dictionnary rules
      $count_rules = \countElementsInTable("glpi_rules",
         [
            'sub_type'  => 'RuleDictionnarySoftware',
            'is_active' => 1,
         ]
      );

      $with_manufacturer = [];
      $without_manufacturer = [];
      $mids = []; //keep trace of handled ids

      foreach ($this->data as $k => &$val) {
         foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
               $val->$dest = $val->$origin;
            }
         }

         if (!property_exists($val, 'name') || (property_exists($val, 'name') && $val->name == '')) {
            if (property_exists($val, 'guid') && $val->guid != '') {
               $val->name = $val->guid;
            }
         }

         //If the software name exists and is defined
         if (property_exists($val, 'name') && $val->name != '') {
            $res_rule       = [];

            //Only play rules engine if there's at least one rule
            //for software dictionnary
            if ($count_rules > 0) {
               $rule_input = [
                  "name"               => $val->name,
                  "manufacturer"       => $val->manufacturers_id ?? 0,
                  "old_version"        => $val->version ?? null,
                  "entities_id"        => $entities_id_software,
                  "_system_category"   => $val->_system_category ?? null
               ];
               $res_rule = $rulecollection->processAllRules($rule_input);
            }

            if (isset($res_rule['_ignore_import']) && $res_rule['_ignore_import'] == 1) {
               //ignored by rules
               unset($this->data[$k]);
               continue;
            }

            //If the name has been modified by the rules engine
            if (isset($res_rule["name"])) {
               $val->name = $res_rule["name"];
            }
            //If the version has been modified by the rules engine
            if (isset($res_rule["version"])) {
               $val->version = $res_rule["version"];
            }

            //If the manufacturer has been modified or set by the rules engine
            if (isset($res_rule["manufacturer"])) {
               $val->manufacturers_id = Dropdown::import(
                  'Manufacturer',
                  ['name' => $res_rule['manufacturer']]
               );
            } else if (property_exists($val, 'manufacturers_id')
               && $val->manufacturers_id != ''
               && $val->manufacturers_id != '0'
            ) {
               if (!isset($mids[$val->manufacturers_id])) {
                  $new_value = Dropdown::importExternal(
                     'Manufacturer',
                     addslashes($val->manufacturers_id),
                     $this->entities_id
                  );
                  $mids[$val->manufacturers_id] = $new_value;
               }
               $val->manufacturers_id = $mids[$val->manufacturers_id];
            } else {
               $val->manufacturers_id = 0;
            }

            //The rules engine has modified the entity
            //(meaning that the software is recursive and defined
            //in an upper entity)
            if (isset($res_rule['new_entities_id'])) {
               $val->entities_id = $res_rule['new_entities_id'];
               $is_recursive    = 1;
            }

            //Entity is not set, get from configuration
            if (!property_exists($val, 'entities_id') || $val->entities_id == '') {
               $val->entities_id = $entities_id_software;
            }
            //version is undefined, set it to blank
            if (!property_exists($val, 'version')) {
               $val->version = '';
            }

            //not a template, not deleted, ...
            $val->is_template_item = 0;
            $val->is_deleted_item = 0;
            $val->operatingsystems_id = 0;

            //Store recursivity
            $val->is_recursive = $is_recursive;

            //String with the manufacturer
            $comp_key = $this->getSimpleCompareKey($val);

            if ($val->manufacturers_id == 0) {
               //soft w/o manufacturer. Keep it to see later if one exists with manufacturer
               $without_manufacturer[$comp_key] = $k;
            } else {
               $with_manufacturer[$comp_key] = true;
            }
         }
      }

      //NOTE: A same software may have a manufacturer or not. Keep the one with manufacturer.
      foreach ($without_manufacturer as $comp_key => $data_index) {
         if (isset($with_manufacturer[$comp_key])) {
            //same software do exists with a manufacturer, remove current duplicate
            unset($this->data[$data_index]);
         }
      }

      return $this->data;
   }

   public function handle() {
      global $DB;

      //Get configured entity
      $entities_id  = $this->entities_id_software;
      //Get operating system
      $operatingsystems_id = 0;

      if (isset($this->extra_data['\Glpi\Inventory\Asset\OperatingSystem'])) {
         $os = $this->extra_data['\Glpi\Inventory\Asset\OperatingSystem'][0];
         $operatingsystems_id = $os->getId();
      }

      $db_software = [];

      //Load existing software versions from db. Grab required fields
      //to build comparison key @see getFullCompareKey
      $iterator = $DB->request([
         'SELECT' => [
            'glpi_items_softwareversions.id as sid',
            'glpi_softwares.name',
            'glpi_softwareversions.name AS version',
            'glpi_softwares.manufacturers_id',
            'glpi_softwareversions.entities_id',
            'glpi_softwareversions.operatingsystems_id',
         ],
         'FROM'      => 'glpi_items_softwareversions',
         'LEFT JOIN' => [
            'glpi_softwareversions' => [
               'ON'  => [
                  'glpi_items_softwareversions' => 'softwareversions_id',
                  'glpi_softwareversions'       => 'id'
               ]
            ],
            'glpi_softwares'        => [
               'ON'  => [
                  'glpi_softwareversions' => 'softwares_id',
                  'glpi_softwares'        => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_items_softwareversions.items_id' => $this->item->fields['id'],
            'glpi_items_softwareversions.itemtype'    => $this->item->getType(),
            'glpi_items_softwareversions.is_dynamic'  => 1
         ]
      ]);

      while ($data = $iterator->next()) {
         $softid = $data['sid'];
         unset($data['sid']);
         $db_software[$this->getFullCompareKey((object)$data)] = $softid;
      }

      //check for existing links
      foreach ($this->data as $k => &$val) {
         //operating system id is not known before handle(); set it in value
         $val->operatingsystems_id = $operatingsystems_id;
         $key = $this->getFullCompareKey($val);
         if (isset($db_software[$key])) {
            //link already exists in database, drop it
            unset($this->data[$k]);
            unset($db_software[$key]);
         }
      }

      //remaining entries in $db_software means relation must be dropped
      if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_software) > 0) {
         $DB->delete(
            'glpi_items_softwareversions', [
            'id' => $db_software
            ]
         );
      }

      if (!count($this->data)) {
         //nothing to do!
         return;
      }

      //check unicity
      $this->disable_unicity_check = (count(FieldUnicity::getUnicityFieldsConfig("Software", $entities_id)) === 0);

      try {
         $this->populateSoftwares();
         $this->storeSoftwares();
         $this->populateVersions();
         $this->storeVersions();
         $this->storeAssetLink();
      } catch (\Exception $e) {
         throw $e;
      }
   }

   /**
    * Get software comparison key
    *
    * @param string  $name             Software name
    * @param integer $manufacturers_id Manufacturers id
    *
    * @return string
    */
   protected function getSoftwareKey($name, $manufacturers_id): string {
      return $this->getCompareKey([$name, $manufacturers_id]);
   }

   /**
    * Get software version comparison key
    *
    * @param string  $name                Software name
    * @param integer $softwares_id        Manufacturers id
    * @param integer $operatingsystems_id Operating system ID
    *
    * @return string
    */
   protected function getVersionKey($version, $softwares_id, $operatingsystems_id): string {
      return $this->getCompareKey([
         strtolower($version),
         $softwares_id,
         $operatingsystems_id
      ]);
   }

   /**
    * Get full comparison keys for a software (including manufacturer and operating system)
    *
    * @param \stdClass $val Object values
    *
    * @return string
    */
   protected function getFullCompareKey(\stdClass $val): string {
      return $this->getCompareKey([
         strtolower($val->name),
         strtolower($val->version),
         $val->manufacturers_id,
         $val->entities_id,
         $val->operatingsystems_id
      ]);
   }

   /**
    * Get full comparison keys for a software (including manufacturer and operating system)
    *
    * @param \stdClass $val Object values
    *
    * @return string
    */
   protected function getSimpleCompareKey(\stdClass $val): string {
      return $this->getCompareKey([
         strtolower($val->name),
         strtolower($val->version),
         $val->entities_id,
         $val->operatingsystems_id
      ]);
   }

   /**
    * Build comparison key from values
    *
    * @param array $parts Values parts
    *
    * @return string
    */
   protected function getCompareKey(array $parts): string {
      return implode(
         self::SEPARATOR,
         $parts
      );
   }

   /**
    * Populates softwares list
    *
    * @return  void
    */
   private function populateSoftwares() {
      global $DB;
      $entities_id  = $this->entities_id_software;

      $criteria = [
         'SELECT' => ['id', 'name', 'manufacturers_id'],
         'FROM'   => \Software::getTable(),
         'WHERE'  => [
            'entities_id'        => $entities_id,
            'name'               => new QueryParam(),
            'manufacturers_id'   => new QueryParam()
         ]
      ];

      $it = new DBmysqlIterator(null);
      $it->buildQuery($criteria);
      $query = $it->getSql();
      $stmt = $DB->prepare($query);

      foreach ($this->data as $val) {
         $key = $this->getSoftwareKey(
            $val->name,
            $val->manufacturers_id
         );

         if (isset($this->softwares[$key])) {
            //already loaded
            continue;
         }

         $stmt->bind_param(
            'ss',
            $val->name,
            $val->manufacturers_id
         );
         $stmt->execute();
         $results = $stmt->get_result();

         while ($row = $results->fetch_object()) {
            $this->softwares[$key] = $row->id;
         }
      }
      $stmt->close();
   }

   /**
    * Populates softwares versions list
    *
    * @return  void
    */
   private function populateVersions() {
      global $DB;
      $entities_id  = $this->entities_id_software;

      if (!count($this->softwares)) {
         //no existing software, no existing versions :)
         return;
      }

      $criteria = [
         'SELECT' => ['id', 'name', 'softwares_id', 'operatingsystems_id'],
         'FROM'   => \SoftwareVersion::getTable(),
         'WHERE'  => [
            'entities_id'           => $entities_id,
            'name'                  => new QueryParam(),
            'softwares_id'          => new QueryParam(),
            'operatingsystems_id'   => new QueryParam()
         ]
      ];

      $it = new DBmysqlIterator(null);
      $it->buildQuery($criteria);
      $query = $it->getSql();
      $stmt = $DB->prepare($query);

      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey(
            $val->name,
            $val->manufacturers_id
         );

         if (!isset($this->softwares[$skey])) {
            continue;
         }

         $softwares_id = $this->softwares[$skey];

         $key = $this->getVersionKey(
            $val->version,
            $softwares_id,
            $val->operatingsystems_id
         );

         if (isset($this->versions[$key])) {
            //already loaded
            continue;
         }

         $stmt->bind_param(
            'sss',
            $val->version,
            $softwares_id,
            $val->operatingsystems_id
         );
         $stmt->execute();
         $results = $stmt->get_result();

         while ($row = $results->fetch_object()) {
            $this->versions[$key] = $row->id;
         }
      }
      $stmt->close();
   }

   /**
    * Store softwares
    *
    * @return void
    */
   private function storeSoftwares() {
      $software = new GSoftware();
      $options = [];
      if ($this->disable_unicity_check === true) {
         $options['disable_unicity_check'] = true;
      }

      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey($val->name, $val->manufacturers_id);
         if (!isset($this->softwares[$skey])) {
            $softwares_id = $software->add(
               Toolbox::addslashes_deep((array)$val),
               $options,
               false
            );
            $this->softwares[$skey] = $softwares_id;
         }
      }
   }

   /**
    * Store softwares versions
    *
    * @return void
    */
   private function storeVersions() {
      $version = new SoftwareVersion();
      $options = [];
      if ($this->disable_unicity_check === true) {
         $options['disable_unicity_check'] = true;
      }

      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey($val->name, $val->manufacturers_id);
         $softwares_id = $this->softwares[$skey];

         $input = (array)$val;
         $input['softwares_id']  = $softwares_id;
         $input['_no_history']   = true;
         $input['name']          = $val->version;

         $vkey = $this->getVersionKey(
            $val->version,
            $softwares_id,
            $val->operatingsystems_id
         );

         if (!isset($this->versions[$vkey])) {
            $versions_id = $version->add(
               Toolbox::addslashes_deep($input),
               $options,
               false
            );
            $this->versions[$vkey] = $versions_id;
         }
      }
   }

   /**
    * Store asset link to softwares
    *
    * @return void
    */
   private function storeAssetLink() {
      global $DB;

      if (!count($this->data)) {
         return;
      }

      if ($this->assetlink_stmt === null) {
         $insert_query = $DB->buildInsert(
            'glpi_items_softwareversions', [
               'itemtype'              => $this->item->getType(),
               'items_id'              => new QueryParam(),
               'softwareversions_id'   => new QueryParam(),
               'is_dynamic'            => new QueryParam(),
               'entities_id'           => new QueryParam(),
               'date_install'          => new QueryParam()
            ]
         );
         $stmt = $DB->prepare($insert_query);
         $this->assetlink_stmt = $stmt;
      }
      $stmt = $this->assetlink_stmt;

      $inputs = [];
      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey($val->name, $val->manufacturers_id);
         $softwares_id = $this->softwares[$skey];

         $vkey = $this->getVersionKey(
            $val->version,
            $softwares_id,
            $val->operatingsystems_id
         );
         $versions_id = $this->versions[$vkey];

         $inputs[] = [
            'itemtype'              => $this->item->getType(),
            'items_id'              => $this->item->fields['id'],
            'softwareversions_id'   => $versions_id,
            'is_dynamic'            => 1,
            'entities_id'           => $this->item->fields['entities_id'],
            'date_install'          => $val->date_install ?? null
         ];
      }

      foreach ($inputs as $input) {
         $stmt->bind_param(
            'sssss',
            $input['items_id'],
            $input['softwareversions_id'],
            $input['is_dynamic'],
            $input['entities_id'],
            $input['date_install']
         );
         $stmt->execute();
      }
   }

   public function checkConf(Conf $conf): bool {
      return $conf->import_software == 1;
   }
}
