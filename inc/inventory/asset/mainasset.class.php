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

use Auth;
use CommonDBTM;
use Glpi\Inventory\Conf;
use RuleImportAssetCollection;
use RuleImportEntityCollection;
use RuleMatchedLog;
use Toolbox;
use Transfer;

abstract class MainAsset extends InventoryAsset
{
    use InventoryNetworkPort;

   /** @var array */
   protected $extra_data = [
      'hardware'     => null,
      'accountinfo'  => null,
      'bios'         => null,
      'users'        => null,
      '\Glpi\Inventory\Asset\NetworkCard' => null
   ];
   /** @var mixed */
   protected $raw_data;
   /* @var array */
   protected $hardware;
   /** @var integer */
   protected $states_id_default;
   /** @var \stdClass */
   private $current_data;
   /** @var array */
   protected $assets = [];
   /** @var Conf */
   protected $conf;

   public function __construct(CommonDBTM $item, $data) {
      $namespaced = explode('\\', static::class);
      $this->itemtype = array_pop($namespaced);
      $this->item = $item;
      //store raw data for reference
      $this->raw_data = $data;
   }

   /**
    * Get model foregien key field name
    *
    * @return string
    */
   abstract protected function getModelsFieldName();

   /**
    * Get model foregien key field name
    *
    * @return string
    */
   abstract protected function getTypesFieldName();

   public function prepare() :array {
      global $DB;

      $models_id = $this->getModelsFieldName();
      $types_id = $this->getTypesFieldName();

      $val = new \stdClass();

      //set update system
      $val->autoupdatesystems_id = $this->raw_data->content->autoupdatesystems_id ?? 'GLPI Native Inventory';

      if (isset($this->extra_data['hardware'])) {
         $hardware = (object)$this->extra_data['hardware'];

         $hw_mapping = [
            'name'           => 'name',
            'winprodid'      => 'licenseid',
            'winprodkey'     => 'license_number',
            'workgroup'      => 'domains_id',
            'lastloggeduser' => 'users_id',
         ];

         foreach ($hw_mapping as $origin => $dest) {
            if (property_exists($hardware, $origin)) {
               $hardware->$dest = $hardware->$origin;
            }
         }
         $this->hardware = $hardware;

         foreach ($hardware as $key => $property) {
            $val->$key = $property;
         }
      }

      if (property_exists($val, 'users_id')) {
         if ($val->users_id == '') {
            unset($val->users_id);
         } else {
            $val->contact = $val->users_id;
            $split_user = explode("@", $val->users_id);
            $iterator = $DB->request([
               'SELECT' => 'id',
               'FROM'   => 'glpi_users',
               'WHERE'  => [
                  'name'   => $split_user[0]
               ],
               'LIMIT'  => 1
            ]);
            $result = $iterator->next();
            if (count($iterator)) {
               $result = $iterator->next();
               $val->users_id = $result['id'];
            } else {
               $val->users_id = 0;
            }
         }
      }

      if (isset($this->extra_data['bios'])) {
         $bios = (object)$this->extra_data['bios'];
         if (property_exists($bios, 'assettag')
               && !empty($bios->assettag)) {
            $val->otherserial = $bios->assettag;
         }
         if (property_exists($bios, 'smanufacturer')
               && !empty($bios->smanufacturer)) {
            $val->manufacturers_id = $bios->smanufacturer;
         } else if (property_exists($bios, 'mmanufacturer')
               && !empty($bios->mmanufacturer)) {
            $val->manufacturers_id = $bios->mmanufacturer;
            $val->mmanufacturer = $bios->mmanufacturer;
         } else if (property_exists($bios, 'bmanufacturer')
               && !empty($bios->bmanufacturer)) {
            $val->manufacturers_id = $bios->bmanufacturer;
            $val->bmanufacturer = $bios->bmanufacturer;
         }

         if (property_exists($bios, 'smodel') && $bios->smodel != '') {
            $val->$models_id = $bios->smodel;
         } else if (property_exists($bios, 'mmodel') && $bios->mmodel != '') {
            $val->$models_id = $bios->mmodel;
            $val->model = $bios->mmodel;
         }

         if (property_exists($bios, 'ssn')) {
            $val->serial = trim($bios->ssn);
            // HP patch for serial begin with 'S'
            if (property_exists($val, 'manufacturers_id')
                  && strstr($val->manufacturers_id, "ewlett")
                  && preg_match("/^[sS]/", $val->serial)) {
               $val->serial = trim(
                  preg_replace(
                     "/^[sS]/",
                     "",
                     $val->serial
                  )
               );
            }
         }

         if (property_exists($bios, 'msn')) {
            $val->mserial = $bios->msn;
         }
      }

      // * Type of the asset
      if (isset($hardware)) {
         if (property_exists($hardware, 'vmsystem')
            && $hardware->vmsystem != ''
            && $hardware->vmsystem != 'Physical'
         ) {
            $val->$types_id = $hardware->vmsystem;
            // HACK FOR BSDJail, remove serial and UUID (because it's of host, not container)
            if ($hardware->vmsystem == 'BSDJail') {
               if (property_exists($val, 'serial')) {
                  $val->serial = '';
               }
               $val->uuid .= '-' . $val->name;
            }
         } else {
            if (property_exists($hardware, 'chassis_type')
                  && !empty($hardware->chassis_type)) {
               $val->$types_id = $hardware->chassis_type;
            } else if (isset($bios) && property_exists($bios, 'type')
                  && !empty($bios->type)) {
               $val->$types_id = $bios->type;
            } else if (isset($bios) && property_exists($bios, 'mmodel')
                  && !empty($bios->mmodel)) {
               $val->$types_id = $bios->mmodel;
            }
         }
      }

      // * USERS
      $cnt = 0;
      if (isset($this->extra_data['users'])) {
         if (count($this->extra_data['users']) > 0) {
            $user_temp = '';
            if (property_exists($val, 'contact')) {
               $user_temp = $val->contact;
            }
            $val->contact = '';
         }
         foreach ($this->extra_data['users'] as $a_users) {
            $user = '';
            if (property_exists($a_users, 'login')) {
               $user = $a_users->login;
               if (property_exists($a_users, 'domain')
                       && !empty($a_users->domain)) {
                  $user .= "@" . $a_users->domain;
               }
            }
            if ($cnt == 0) {
               if (property_exists($a_users, 'login')) {
                  // Search on domain
                  $where_add = [];
                  if (property_exists($a_users, 'domain')
                          && !empty($a_users->domain)) {
                     $ldaps = $DB->request('glpi_authldaps',
                           ['WHERE'  => ['inventory_domain' => $a_users->domain]]
                     );
                     $ldaps_ids = [];
                     foreach ($ldaps as $data_LDAP) {
                        $ldaps_ids[] = $data_LDAP['id'];
                     }
                     if (count($ldaps_ids)) {
                        $where_add['authtype'] = Auth::LDAP;
                        $where_add['auths_id'] = $ldaps_ids;
                     }
                  }
                  $iterator = $DB->request([
                     'SELECT' => ['id'],
                     'FROM'   => 'glpi_users',
                     'WHERE'  => [
                        'name'   => $a_users->login
                     ] + $where_add,
                     'LIMIT'  => 1
                  ]);
                  if ($row = $iterator->next()) {
                     $val->users_id = $row['id'];
                  }
               }
            }

            if ($user != '') {
               if (property_exists($val, 'contact')) {
                  if ($val->contact == '') {
                     $val->contact = $user;
                  } else {
                     $val->contact .= "/" . $user;
                  }
               } else {
                  $val->contact = $user;
               }
            }
            $cnt++;
         }
         if (empty($val->contact)) {
            $val->contact = $user_temp;
         }
      }

      if (method_exists($this, 'postPrepare')) {
         $this->postPrepare($val);
      }

      $this->data = [$val];

      return $this->data;
   }

   /**
    * Prepare input rules input, for both Entities rules and Import ones
    *
    * @param \stdClass $val Current data values
    *
    * @return array
    */
   public function prepareAllRulesInput(\stdClass $val): array {
      $input = [];

      if (isset($this->getAgent()->fields['tag'])) {
         $input['tag'] = $this->getAgent()->fields['tag'];
      }

      if (property_exists($val, 'serial') && !empty($val->serial)) {
         $input['serial'] = $val->serial;
      }
      if (property_exists($val, 'otherserial') && !empty($val->otherserial)) {
         $input['otherserial'] = $val->otherserial;
      }
      if (property_exists($val, 'uuid') && !empty($val->uuid)) {
         $input['uuid'] = $val->uuid;
      }

      if (isset($this->extra_data['\Glpi\Inventory\Asset\NetworkCard'])) {
         foreach ($this->extra_data['\Glpi\Inventory\Asset\NetworkCard'] as $networkcard) {
            $netports = $networkcard->getNetworkPorts();
            $this->ports += $netports;
            foreach ($netports as $network) {
               if (property_exists($network, 'virtualdev')
                  && $network->virtualdev != 1
                  || !property_exists($network, 'virtualdev')
               ) {
                  if (property_exists($network, 'mac') && !empty($network->mac)) {
                     $input['mac'][] = $network->mac;
                  }
                  foreach ($network->ipaddress as $ip) {
                     if ($ip != '127.0.0.1' && $ip != '::1') {
                        $input['ip'][] = $ip;
                     }
                  }
                  if (property_exists($network, 'subnet') && !empty($network->subnet)) {
                     $input['subnet'][] = $network->subnet;
                  }
               }
            }

            // Case of virtualmachines
            if (!isset($input['mac'])
                     && !isset($input['ip'])) {
               foreach ($netports as $network) {
                  if (property_exists($network, 'mac') && !empty($network->mac)) {
                     $input['mac'][] = $network->mac;
                  }
                  foreach ($network->ipaddress as $ip) {
                     if ($ip != '127.0.0.1' && $ip != '::1') {
                        $input['ip'][] = $ip;
                     }
                  }
                  if (property_exists($network, 'subnet') && !empty($network->subnet)) {
                     $input['subnet'][] = $network->subnet;
                  }
               }
            }
         }
      }

      $models_id = $this->getModelsFieldName();
      if (property_exists($val, $models_id) && !empty($val->$models_id)) {
         $input['model'] = $val->$models_id;
      }

      if (property_exists($val, 'domains_id') && !empty($val->domains_id)) {
         $input['domains_id'] = $val->domains_id;
      }

      if (property_exists($val, 'name') && !empty($val->name)) {
         $input['name'] = $val->name;
      } else {
         $input['name'] = '';
      }
      $input['itemtype'] = $this->item->getType();

      // * entity rules
      $input['entities_id'] = $this->entities_id;

      return $input;
   }

   /**
    * Prepare input for Entities rules
    *
    * @param \stdClass $val   Current data values
    * @param array     $input Input processed or all rules
    *
    * @return array
    */
   public function prepareEntitiesRulesInput(\stdClass $val, array $input): array {
      if (property_exists($val, 'domains_id') && (!empty($val->domains_id))) {
         $input['domain'] = $val->domains_id;
      }

      if (isset($input['serial'])) {
         $input['serialnumber'] = $input['serial'];
      }

      return $input;
   }

   public function handle() {
      foreach ($this->data as $key => $data) {
         $this->current_key = $key;
         $input = $this->prepareAllRulesInput($data);

         if (!property_exists($data, 'is_ap') || $data->is_ap != true) {
            $entity_input = $this->prepareEntitiesRulesInput($data, $input);

            $ruleEntity = new RuleImportEntityCollection();
            $ruleEntity->getCollectionPart();
            $dataEntity = $ruleEntity->processAllRules($entity_input, []);

            if (isset($dataEntity['_ignore_import'])) {
               $input['rules_id'] = $dataEntity['rules_id'];
               $this->addRefused($input);
               return;
            }

            if (!isset($dataEntity['entities_id']) || $dataEntity['entities_id'] == -1) {
               $input['entities_id'] = 0;
            } else {
               $input['entities_id'] = $dataEntity['entities_id'];
            }
            $this->entities_id = $input['entities_id'];
         }

         //call rules on current collected data to find item
         //a callback on rulepassed() will be done if one is found.
         $rule = new RuleImportAssetCollection();
         $rule->getCollectionPart();
         $datarules = $rule->processAllRules($input, [], ['class' => $this]);

         if (isset($datarules['_no_rule_matches']) AND ($datarules['_no_rule_matches'] == '1')) {
            //no rule matched, this is a new one
            $this->rulepassed(0, $this->item->getType(), null);
         } else if (!isset($datarules['found_inventories'])) {
             $input['rules_id'] = $datarules['rules_id'];
             $this->addRefused($input);
         }
      }
   }

   protected function addRefused(array $input) {
      $refused_input = [
        'name'         => $input['name'],
        'itemtype'     => $input['itemtype'],
        'serial'       => $input['serial'] ?? '',
        'ip'           => $input['ip'] ?? '',
        'mac'          => $input['mac'] ?? '',
        'uuid'         => $input['uuid'] ?? '',
        'rules_id'     => $input['rules_id'],
        'entities_id'  => $input['entities_id']
      ];

      foreach (['ip', 'mac'] as $array) {
         if (is_array($refused_input[$array])) {
            $refused_input[$array] = exportArrayToDB($refused_input[$array]);
         }
      }

      $refused = new \RefusedEquipment();
      $refused->add($refused_input);
      $this->setItem($refused);
   }

   public function checkConf(Conf $conf): bool {
      $this->conf = $conf;
      $this->states_id_default = $conf->states_id_default;
      return true;
   }

   /**
    * After rule engine passed, update task (log) and create item if required
    *
    * @param integer $items_id id of the item (0 if new)
    * @param string  $itemtype Item type
    * @param integer $rules_id Matched rule id, if any
    * @param integer $ports_id Matched port id, if any
    */
   public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = 0) {
      global $CFG_GLPI;

      $key = $this->current_key;
      $val = &$this->data[$key];
      $entities_id = $this->entities_id;
      $val->is_dynamic = 1;
      $val->entities_id = $entities_id;

      $val->states_id = $this->states_id_default ?? $this->item->fields['states_id'] ?? 0;
      $val->locations_id = $this->locations_id ?? $val->locations_id ?? $this->item->fields['locations_id'] ?? 0;

      if (!isset($_SESSION['glpiactive_entity'])) {
         //set entity in session; if it does not exists
         $_SESSION['glpiactiveentities']        = [$entities_id];
         $_SESSION['glpiactiveentities_string'] = $entities_id;
         $_SESSION['glpiactive_entity']         = $entities_id;
      }

      //handleLinks relies on $this->data; update it before the call
      $this->handleLinks();

      if ($items_id == 0) {
         $input = (array)$val;
         unset($input['ap_port']);
         unset($input['firmware']);
         $items_id = $this->item->add(Toolbox::addslashes_deep($input));
         $this->agent->update(['id' => $this->agent->fields['id'], 'items_id' => $items_id]);

         $this->with_history = false;//do not handle history on main item first import
      } else {
         $this->item->getFromDB($items_id);
         if (($this->agent->fields['items_id'] ?? 0) != $items_id) {
            $this->agent->update(['id' => $this->agent->fields['id'], 'items_id' => $items_id]);
         }
      }

      $val->id = $this->item->fields['id'];

      if ($entities_id == -1) {
         $entities_id = $this->item->fields['entities_id'];
      }
      $val->entities_id = $entities_id;

      if ($entities_id != $this->item->fields['entities_id']) {
         //asset entity has changed in rules; do transfer
         if ($CFG_GLPI['transfers_id_auto']) {
            $transfer = new Transfer();
            $transfer->getFromDB($CFG_GLPI['transfers_id_auto']);
            $item_to_transfer = [$this->itemtype => [$items_id => $items_id]];
            $transfer->moveItems($item_to_transfer, $entities_id, $transfer->fields);
         }

         //and set new entity in session
         $_SESSION['glpiactiveentities']        = [$entities_id];
         $_SESSION['glpiactiveentities_string'] = $entities_id;
         $_SESSION['glpiactive_entity']         = $entities_id;
      }

      //Ports are handled a different way on network equipments
      if ($this->item->getType() != 'NetworkEquipment') {
          $this->handlePorts();
      }

      if (method_exists($this, 'isWirelessController') && $this->isWirelessController()) {
         if (property_exists($val, 'firmware') && $val->firmware instanceof \stdClass) {
            $fw = new Firmware($this->item, [$val->firmware]);
            if ($fw->checkConf($this->conf)) {
               $fw->setAgent($this->getAgent());
               $fw->setEntityID($this->getEntityID());
               $fw->prepare();
               $fw->handleLinks();
               $this->assets['Glpi\Inventory\Asset\Firmware'] = [$fw];
               unset($val->firmware);
            }
         }

         if (property_exists($val, 'ap_port')) {
            $this->setManagementPorts(['management' => $val->ap_port]);
            unset($val->ap_port);
         }
      }

      $this->handleAssets();

      $input = (array)$val;

      $this->item->update(Toolbox::addslashes_deep($input), $this->withHistory());

      $rulesmatched = new RuleMatchedLog();
      $inputrulelog = [
         'date'      => date('Y-m-d H:i:s'),
         'rules_id'  => $rules_id,
         'items_id'  => $items_id,
         'itemtype'  => $itemtype,
         'agents_id' => $this->agent->fields['id'],
         'method'    => 'inventory'
      ];
      $rulesmatched->add($inputrulelog, [], false);
      $rulesmatched->cleanOlddata($items_id, $itemtype);
   }

   /**
    * Get modified hardware
    *
    * @return \stdClass
    */
   public function getHardware() {
      return $this->hardware;
   }

   /**
    * Retrieve computer entities id
    *
    * @return integer
    */
   public function getEntityID() {
      return $this->entities_id;
   }

   public function handleAssets() {
      $key = $this->current_key;
      $val = $this->data[$key];

      $mainasset = clone $this;
      $mainasset->setData([$key => $val]);

      $assets_list = $this->assets;

      $controllers = [];
      $ignored_controllers = [];

      //ensure controllers are done last, some components will
      //ask to ignore their associated controller
      if (isset($assets_list['\Glpi\Inventory\Asset\Controller'])) {
         $controllers = $assets_list['\Glpi\Inventory\Asset\Controller'];
         unset($assets_list['\Glpi\Inventory\Asset\Controller']);
      }

      foreach ($assets_list as $assets) {
         foreach ($assets as $asset) {
            $asset->setExtraData($this->assets);
            $asset->setExtraData(['\\' . get_class($this) => $mainasset]);
            $asset->handleLinks();
            $asset->handle();
            $ignored_controllers = array_merge($ignored_controllers, $asset->getIgnored('controllers'));
         }
      }

      //do controlers
      foreach ($controllers as $asset) {
         $asset->setExtraData($this->assets);
         $asset->setExtraData(['\\' . get_class($this) => $mainasset]);
         //do not handle ignored controllers
         $asset->setExtraData(['ignored' => $ignored_controllers]);
         $asset->handleLinks();
         $asset->handle();
      }
   }

   /**
    * Set prepared assets
    *
    * @param array $assets Prepared assets list
    *
    * @return MainAsset
    */
   public function setAssets(array $assets): MainAsset {
      $this->assets = $assets;
      $this->setExtraData($assets);
      return $this;
   }

   /**
    * Get current item
    *
    * @return CommonDBTM
    */
   public function getItem(): CommonDBTM {
      return $this->item;
   }
}
