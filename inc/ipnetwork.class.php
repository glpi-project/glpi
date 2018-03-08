<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
* */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class IPNetwork : Represent an IPv4 or an IPv6 network.
/// It fully use IPAddress and IPNetmask to check validity and change representation from binary
/// to textual values.
/// \anchor parameterType Moreover, attributes of checking and retrieving functions allways allows
/// both binary (ie: array of 4 bytes) or IPAddress Object. As such, $version is only use (and
/// checked) with binary format of parameters.
/// \anchor ipAddressToNetwork We have to notice that checking regarding an IP address is the same
/// thing than checking regarding a network with all bits of the netmask set to 1
/// @since 0.84
class IPNetwork extends CommonImplicitTreeDropdown {

   public $dohistory = true;

   static $rightname = 'internet';



   static function getTypeName($nb = 0) {
      return _n('IP network', 'IP networks', $nb);
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'version',
         'name'               => __('IP version'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'address',
         'name'               => IPAddress::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'netmask',
         'name'               => IPNetmask::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'gateway',
         'name'               => __('Gateway'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'addressable',
         'name'               => __('Addressable network'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   function getAddress() {

      if (!isset($this->address)) {
         $this->address = new IPAddress();
         if (!$this->address->setAddressFromArray($this->fields, "version", "address", "address")) {
            return false;
         }
      }
      return $this->address;
   }


   function getNetmask() {

      if (!isset($this->netmask)) {
         $this->netmask = new IPNetmask();
         if (!$this->netmask->setAddressFromArray($this->fields, "version", "netmask", "netmask")) {
            return false;
         }
      }
      return $this->netmask;
   }


   function getGateway() {

      if (!isset($this->gateway)) {
         $this->gateway = new IPAddress();
         if (!$this->gateway->setAddressFromArray($this->fields, "version", "gateway", "gateway")) {
            return false;
         }
      }
      return $this->gateway;
   }


   /**
    * When we load the object, we fill the "network" field with the correct address/netmask values
   **/
   function post_getFromDB () {

      // Be sure to remove addresses, otherwise reusing will provide old objects for getAddress, ...
      unset($this->address);
      unset($this->netmask);
      unset($this->gateway);

      if (isset($this->fields["address"])
          && isset($this->fields["netmask"])) {
         if ($this->fields["version"] == 4) {
            $this->fields["network"] = sprintf(__('%1$s / %2$s'), $this->fields["address"],
                                            $this->fields["netmask"]);
         } else { // IPv6
            $this->fields["network"] = sprintf(__('%1$s / %2$s'), $this->fields["address"],
                                            $this->fields["netmask"]);
         }
      }

   }


   function getAdditionalFields() {

      return [['name'     => 'network',
                         'label'    => self::getTypeName(1),
                         'type'     => 'text',
                         'list'     => true,
                         'comment'  => __('Set the network using notation address/mask')],
                   ['name'     => 'gateway',
                         'label'    => __('Gateway'),
                         'type'     => 'text',
                         'list'     => true],
                   ['name'     => 'addressable',
                         'label'    => __('Addressable network'),
                         'comment'  => __('An addressable network is a network defined on an equipment'),
                         'type'     => 'bool']];
   }


   function getNewAncestor() {

      if (isset($this->data_for_implicit_update)) {
         $params = ["address" => $this->data_for_implicit_update['address'],
                         "netmask" => $this->data_for_implicit_update['netmask']];

         if (isset($this->fields['id'])) {
            $params['exclude IDs'] = $this->fields['id'];
         }

         $parents = self::searchNetworks("contains", $params,
                                         $this->data_for_implicit_update['entities_id']);

         if ((is_array($parents)) && (count($parents) > 0)) {
            return $parents[0];
         }
      }

      return 0;
   }


   /**
    * @param $input
   **/
   function prepareInput($input) {

      // In case of entity transfer, $input['network'] is not defined
      if (!isset($input['network']) && isset($this->fields['network'])) {
         $input['network'] = $this->fields['network'];
      }

      // In case of entity transfer, $input['gateway'] is not defined
      if (!isset($input['gateway']) && isset($this->fields['gateway'])) {
         $input['gateway'] = $this->fields['gateway'];
      }

      // If $this->fields["id"] is not set then, we are adding a new network
      // Or if $this->fields["network"] != $input["network"] we a updating the network
      $address = new IPAddress();
      $netmask = new IPNetmask();
      // Don't validate an empty network
      if (empty($input["network"])) {
         return ['error' => __('Invalid network address'),
                      'input' => false];
      }
      if (!isset($this->fields["id"])
          || !isset($this->fields["network"])
          || ($input["network"] != $this->fields["network"])) {
         $network = explode ("/", $input["network"]);
         if (count($network) != 2) {
            return ['error' => __('Invalid input format for the network'),
                         'input' => false];
         }
         if (!$address->setAddressFromString(trim($network[0]))) {
            return ['error' => __('Invalid network address'),
                         'input' => false];
         }
         if (!$netmask->setNetmaskFromString(trim($network[1]), $address->getVersion())) {
            return ['error' => __('Invalid subnet mask'),
                         'input' => false];
         }

         // After checking that address and netmask are valid, modify the address to be the "real"
         // network address : the first address of the network. This is not required for SQL, but
         // that looks better for the human
         self::computeNetworkRangeFromAdressAndNetmask($address, $netmask, $address);

         // Now, we look for already existing same network inside the database
         $params = ["address" => $address,
                         "netmask" => $netmask];
         if (isset($this->fields["id"])) {
            $params["exclude IDs"] = $this->fields["id"];
         }

         if (isset($this->fields["entities_id"])) {
            $entities_id = $this->fields["entities_id"];
         } else if (isset($input["entities_id"])) {
            $entities_id = $input["entities_id"];
         } else {
            $entities_id = -1;
         }

         // TODO : what is the best way ? recursive or not ?
         $sameNetworks = self::searchNetworks("equals", $params, $entities_id, false);
         // Check unicity !
         if ($sameNetworks && (count($sameNetworks) > 0)) {
            return ['error' => __('Network already defined in visible entities'),
                         'input' => false];
         }

         // Then, update $input to reflect the network and the netmask
         $input = $address->setArrayFromAddress($input, "version", "address", "address");
         $input = $netmask->setArrayFromAddress($input, "", "netmask", "netmask");

         // We check to see if the network is modified
         $previousAddress = new IPAddress();
         $previousAddress->setAddressFromArray($this->fields, "version", "address", "address");
         $previousNetmask = new IPNetmask();
         $previousNetmask->setAddressFromArray($this->fields, "version", "netmask", "netmask");

         if ($previousAddress->equals($address)
             && $previousNetmask->equals($netmask)) {
            $this->networkUpdate = false;
         } else {
            $this->networkUpdate = true;
         }

      } else {
         // If netmask and address are not modified, then, load them from DB to check the validity
         // of the gateway
         $this->networkUpdate = false;
         $address->setAddressFromArray($this->fields, "version", "address", "address");
         $netmask->setAddressFromArray($this->fields, "version", "netmask", "netmask");
         $entities_id = $this->fields['entities_id'];
      }

      // Update class for the CommonImplicitTree update ...
      $this->data_for_implicit_update = ['address'     => $address,
                                              'netmask'     => $netmask,
                                              'entities_id' => $entities_id];

      $returnValue = [];
      // If the gateway has been altered, or the network information (address or netmask) changed,
      // then, we must revalidate the gateway !
      if (!isset($this->fields["gateway"])
          || ($input["gateway"] != $this->fields["gateway"])
          || $this->networkUpdate) {
         $gateway = new IPAddress();

         if (!empty($input["gateway"])) {
            if (!$gateway->setAddressFromString($input["gateway"])
                || !self::checkIPFromNetwork($gateway, $address, $netmask)) {
               $returnValue['error'] = __('Invalid gateway address');

               if (!empty($this->fields["gateway"])) {
                  if (!$gateway->setAddressFromString($this->fields["gateway"])
                      || !self::checkIPFromNetwork($gateway, $address, $netmask)) {
                     $gateway->disableAddress();
                  }
               } else {
                  $gateway->disableAddress();
               }
            }
         }
         $input = $gateway->setArrayFromAddress($input, "", "gateway", "gateway");
      }

      $returnValue['input'] = $input;

      return $returnValue;
   }


   /**
    * @see CommonImplicitTreeDropdown::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      $preparedInput = $this->prepareInput($input);

      if (isset($preparedInput['error'])) {
         Session::addMessageAfterRedirect($preparedInput['error'], false, ERROR);
      }

      $input = $preparedInput['input'];

      if (is_array($input)) {
         return parent::prepareInputForAdd($input);
      }
      return false;
   }


   /**
    * @see CommonImplicitTreeDropdown::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      $preparedInput = $this->prepareInput($input);

      if (isset($preparedInput['error'])) {
         Session::addMessageAfterRedirect($preparedInput['error'], false, ERROR);
      }

      $input = $preparedInput['input'];

      if (is_array($input)) {
         return parent::prepareInputForUpdate($input);
      }
      return false;
   }


   function post_addItem() {

      if ($this->networkUpdate) {
         IPAddress_IPNetwork::linkIPAddressFromIPNetwork($this);
      }

      unset($this->networkUpdate);
      parent::post_addItem();
   }


   /**
    * @see CommonImplicitTreeDropdown::post_updateItem()
   **/
   function post_updateItem($history = 1) {

      if ($this->networkUpdate) {
         IPAddress_IPNetwork::linkIPAddressFromIPNetwork($this);
      }

      unset($this->networkUpdate);
      parent::post_updateItem($history);
   }


   function cleanDBonPurge() {

      $link = new IPNetwork_Vlan();
      $link->cleanDBonItemDelete($this->getType(), $this->getID());

      $link = new IPAddress_IPNetwork();
      $link->cleanDBonItemDelete($this->getType(), $this->getID());

      return true;
   }


   function getPotentialSons() {

      if (isset($this->data_for_implicit_update)) {
         $params = ["address"     => $this->data_for_implicit_update['address'],
                         "netmask"     => $this->data_for_implicit_update['netmask'],
                         "exclude IDs" => $this->getID()];

         $mysons = self::searchNetworks("is contained by", $params,
                                        $this->data_for_implicit_update['entities_id']);

         if (is_array($mysons)) {
            return $mysons;
         }
      }

      return [];
   }


   /**
    * \brief Search any networks that contains the given IP
    * \ref ipAddressToNetwork
    *
    * @param $IP        (see \ref parameterType) given IP
    * @param $entityID  scope of the search (parents and childrens are check) (default -1)
    * @param $recursive set to false to only search in current entity, otherwise, all visible
    *                   entities will be search (true by default)
    * @param $fields    list of fields to return in the result (default : only ID of the networks)
    *                   (default '')
    * @param $where     (default '')
    *
    * @return list of networks (see searchNetworks())
   **/
   static function searchNetworksContainingIP($IP, $entityID = -1, $recursive = true,
                                              $fields = "", $where = "") {

      return self::searchNetworks('contains', ['address'  => $IP,
                                                    'netmask'  => [0xffffffff, 0xffffffff,
                                                                        0xffffffff, 0xffffffff],
                                                    'fields'   => $fields,
                                                    'where'    => $where],
                                  $entityID, $recursive);
   }


   /**
    * Search networks relative to a given network
    *
    * @param $relation        type of relation ("is contained by", "equals" or "contains")
    *                         regarding the networks given as parameter
    * @param $condition array of elements to select the good arrays (see Parameters above)
    *    - fields : the fields of the network we wish to retrieve (single field or array of
    *               fields). This parameter will impact the result of the function
    *    - address (see \ref parameterType) : the address for the query
    *    - netmask (see \ref parameterType) : the netmask for the query
    *    - exclude IDs : the IDs to exclude from the query (for instance, $this->getID())
    *    - where : filters to add to the SQL request
    *
    * @param $entityID        the entity on which the selection should occur (-1 => the current active
    *                         entity) (default -1)
    * @param $recursive       set to false to only search in current entity, otherwise, all visible
    *                         entities will be search (true by default)
    * @param $version         version of IP to look (only use when using arrays or string as input for
    *                         address or netmask n(default 0)
    *
    * @return array of networks found. If we want request several field, the return value will be
    *                 an array of array
    *
    * \warning The order of the elements inside the result are ordered from the nearest one to the
    *          further. (ie. 0.0.0.0 is the further of whatever network if you lool for ones that
    *          contains the current network.
   **/
   static function searchNetworks($relation, $condition, $entityID = -1, $recursive = true,
                                  $version = 0) {
      global $DB;

      if (empty($relation)) {
         return false;
      }

      if (empty($condition["fields"])) {
         $fields = 'id';
      } else {
         $fields = $condition["fields"];
      }

      if (!is_array($fields)) {
         $fields = [$fields];
      }

      $FIELDS     = "`".implode("`, `", $fields)."`";

      $startIndex = (($version == 4) ? 3 : 1);

      $addressDB  = ['address_0', 'address_1', 'address_2', 'address_3'];
      $netmaskDB  = ['netmask_0', 'netmask_1', 'netmask_2', 'netmask_3'];

      $WHERE      = "";
      if (isset($condition["address"])
          && isset($condition["netmask"])) {
         $addressPa = new IPAddress($condition["address"]);

         // Check version equality ...
         if ($version != $addressPa->getVersion()) {
            if ($version != 0) {
               return false;
            }
            $version = $addressPa->getVersion();
         }

         $netmaskPa = new IPNetmask($condition["netmask"], $version);

         // Get the array of the adresses
         $addressPa = $addressPa->getBinary();
         $netmaskPa = $netmaskPa->getBinary();

         // Check the binary is valid
         if (!is_array($addressPa) || (count($addressPa) != 4)) {
            return false;
         }
         if (!is_array($netmaskPa) || (count($netmaskPa) != 4)) {
            return false;
         }

         $startIndex = (($version == 4) ? 3 : 0);

         if ($relation == "equals") {
            for ($i = $startIndex; $i < 4; ++$i) {
               $WHERE .= " AND (`".$addressDB[$i]."` & '".$netmaskPa[$i]."')=
                               ('".$addressPa[$i]."' & '".$netmaskPa[$i]."')
                           AND ('".$netmaskPa[$i]."' = `".$netmaskDB[$i]."`)";
            }
         } else {
            for ($i = $startIndex; $i < 4; ++$i) {
               if ($relation == "is contained by") {
                  $globalNetmask = "'".$netmaskPa[$i]."'";
               } else {
                  $globalNetmask = "`".$netmaskDB[$i]."`";
               }

               $WHERE .= " AND (`".$addressDB[$i]."` & $globalNetmask)=
                               ('".$addressPa[$i]."' & $globalNetmask)
                           AND ('".$netmaskPa[$i]."' & `".$netmaskDB[$i]."`)=$globalNetmask";
            }
         }
      }

      $WHERE = "`version`='$version' $WHERE";

      if ($entityID < 0) {
         $entityID = $_SESSION['glpiactive_entity'];
      }
      $entitiesID = [];
      switch ($relation) {
         case "is contained by" :
            $ORDER_ORIENTATION = 'ASC';
            if ($recursive) {
               $entitiesID = getSonsOf('glpi_entities', $entityID);
            }
            break;

         case "contains" :
            $ORDER_ORIENTATION = 'DESC';
            if ($recursive) {
               $entitiesID = getAncestorsOf('glpi_entities', $entityID);
            }
          break;

         case "equals" :
            $ORDER_ORIENTATION = '';
            if ($recursive) {
               $entitiesID = getSonsAndAncestorsOf('glpi_entities', $entityID);
            }
            break;
      }

      $entitiesID[] = $entityID;
      if (count($entitiesID) > 1) { // At least the current entity is defined
         $WHERE .= " AND `entities_id` IN ('".implode("', '", $entitiesID)."')";
      } else {
         $WHERE .= " AND `entities_id` = '".$entitiesID[0]."'";
      }

      if (!empty($condition["exclude IDs"])) {
         if (is_array($condition["exclude IDs"])) {
            if (count($condition["exclude IDs"]) > 1) {
               $WHERE .= " AND `id` NOT IN ('".implode("', '", $condition["exclude IDs"])."')";
            } else {
               $WHERE .= " AND `id` <> '".$condition["exclude IDs"][0]."'";
            }
         } else {
            $WHERE .= " AND `id` <> '".$condition["exclude IDs"]."'";
         }
      }

      $ORDER = [];
      for ($i = $startIndex; $i < 4; ++$i) {
         $ORDER[] = "BIT_COUNT(`".$netmaskDB[$i]."`) $ORDER_ORIENTATION";
      }

      if (!empty($condition["where"])) {
         $WHERE .= " AND " . $condition["where"];
      }

      $query = "SELECT $FIELDS
                FROM `glpi_ipnetworks`
                WHERE $WHERE
                ORDER BY ".implode(', ', $ORDER);
      // By ordering on the netmask, we ensure that the first element is the nearest one (ie:
      // the last should be 0.0.0.0/0.0.0.0 of x.y.z.a/255.255.255.255 regarding the interested
      // element)

      $returnValues = [];
      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (count($fields) > 1) {
               $returnValue = [];
               foreach ($fields as $field) {
                  $returnValue[$field] = $data[$field];
               }
            } else {
               $returnValue = $data[$fields[0]];
            }
            $returnValues[] = $returnValue;
         }
      }
      return $returnValues;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('IPNetwork_Vlan', $ong, $options);
      $this->addStandardTab('IPAddress', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Get SQL WHERE statement for requesting elements that are contained inside the current network
    *
    * @param $tableName          name of the table containing the element
    *                            (for instance : glpi_ipaddresses)
    * @param $binaryFieldPrefix  prefix of the binary version of IP address
    *                            (binary for glpi ipaddresses)
    * @param $versionField       the name of the field containing the version inside the database
    *
    * @return SQL request "WHERE" element
   **/
   function getWHEREForMatchingElement($tableName, $binaryFieldPrefix, $versionField) {

      $version = $this->fields["version"];
      $start   = null;
      $this->computeNetworkRange($start);

      $result = [];
      for ($i = ($version == 4 ? 3 : 0); $i < 4; ++$i) {
         $result[] = "(`$tableName`.`".$binaryFieldPrefix."_$i` & '".$this->fields["netmask_$i"]."')
                       = ('".$start[$i]."')";
      }

      $result = "`$tableName`.`version` = '$version'
                AND (".implode(" AND ", $result).")";

      return $result;
   }


   /**
    * Check to see if an IP is inside a given network
    * See : \ref ipAddressToNetwork
    *
    * @param $address         (see \ref parameterType) the IP address to check
    * @param $networkAddress  (see \ref parameterType) the address of the network
    * @param $networkNetmask  (see \ref parameterType) the netmask of the network
    * @param $version         of IP : only usefull for binary array as input (default 0)
    *
    * @return true if the network owns the IP address
   **/
   static function checkIPFromNetwork($address, $networkAddress, $networkNetmask, $version = 0) {

      $IPNetmask  = [0xffffffff, 0xffffffff, 0xffffffff, 0xffffffff];
      $relativity = self::checkNetworkRelativity($address, $IPNetmask, $networkAddress,
                                                  $networkNetmask, $version);

      return ($relativity == "equals") || ($relativity == "second contains first");
   }


   /**
    * \brief Check network relativity
    * Check how networks are relative (fully different, equals, first contains second, ...)
    *
    * @param $firstAddress    (see \ref parameterType) address of the first network
    * @param $firstNetmask    (see \ref parameterType) netmask of the first network
    * @param $secondAddress   (see \ref parameterType) address of the second network
    * @param $secondNetmask   (see \ref parameterType) netmask of the second network
    * @param $version         of IP : only usefull for binary array as input (default 0)
    *
    * @return string :
    *           - "different version" : there is different versions between elements
    *           - "?" : There is holes inside the netmask and both networks can partially intersect
    *           - "different" : the networks are fully different;
    *           - "equals" : both networks are equals
    *           - "first contains second" "second contains first" : one include the other
    */
   static function checkNetworkRelativity($firstAddress, $firstNetmask, $secondAddress,
                                          $secondNetmask, $version = 0) {

      if ($firstAddress instanceof IPAddress) {
         if ($version == 0) {
            $version = $firstAddress->getVersion();
         }
         if ($version != $firstAddress->getVersion()) {
            return "different version";
         }
         $firstAddress = $firstAddress->getBinary();
      }

      if ($firstNetmask instanceof IPAddress) {
         if ($version != $firstNetmask->getVersion()) {
            return "different version";
         }
         $firstNetmask = $firstNetmask->getBinary();
      }

      if ($secondAddress instanceof IPAddress) {
         if ($version != $secondAddress->getVersion()) {
            return "different version";
         }
         $secondAddress = $secondAddress->getBinary();
      }

      if ($secondNetmask instanceof IPAddress) {
         if ($version != $secondNetmask->getVersion()) {
            return "different version";
         }
         $secondNetmask = $secondNetmask->getBinary();
      }

      $startIndex = (($version == 4) ? 3 : 0);
      $first      = true;
      $second     = true;
      for ($i = $startIndex; $i < 4; ++$i) {
         $and     = ($firstNetmask[$i] & $secondNetmask[$i]);
         // Be carefull : php integers are 32 bits SIGNED.
         // Thus, checking equality must be done by XOR ...
         $first  &= (($and ^ $firstNetmask[$i]) == 0);
         $second &= (($and ^ $secondNetmask[$i]) == 0);
      }

      if (!$first && !$second) {
         return "?";
      }

      if ($first && $second) {
         $result = "equals";
         $mask   = &$firstNetmask;
      } else if ($first) {
         $result = "first contains second";
         $mask   = &$firstNetmask;
      } else { // $second == true
         $result = "second contains first";
         $mask   = &$secondNetmask;
      }

      for ($i = $startIndex; $i < 4; ++$i) {
         if ((($firstAddress[$i] & $mask[$i]) ^ ($secondAddress[$i] & $mask[$i])) != 0) {
            return "different";
         }
      }
      return $result;
   }


   /**
    * Compute the first and the last address of $this
    * \see computeNetworkRangeFromAdressAndNetmask()
    *
    * @param $start
    * @param $end                         (default NULL)
    * @param $excludeBroadcastAndNetwork  Don't provide extremties addresses
    *                                     ($this->fields['addressable'] by default)
    *                                     (default '')
   **/
   function computeNetworkRange(&$start, &$end = null, $excludeBroadcastAndNetwork = '') {

      if (!is_bool($excludeBroadcastAndNetwork)) {
         if (isset($this->fields['addressable'])) {
            $excludeBroadcastAndNetwork = ($this->fields['addressable'] == 1);
         } else {
            $excludeBroadcastAndNetwork = false;
         }
      }

      self::computeNetworkRangeFromAdressAndNetmask($this->getAddress(), $this->getNetmask(),
                                                    $start, $end, $excludeBroadcastAndNetwork);
   }


   /**
    * \brief Compute the first and the last address of a network.
    * That is usefull, for instance, to compute the "real" network address (the first address)
    * or the broadcast address of the network
    *
    * @param $address                              (see \ref parameterType) the address of the network
    * @param $netmask                              (see \ref parameterType) its netmask
    * @param $firstAddress                         (see \ref parameterType - in/out)
    *                                              the first address (ie real address of the network)
    * @param $lastAddress                          (see \ref parameterType - in/out)
    *                                              the lastAddress of the network
    *                                              (ie. : the broadcast address) (default NULL)
    * @param $excludeBroadcastAndNetwork  boolean  exclude broadcast and network address from the
    *                                              result (false by default)
   **/
   static function computeNetworkRangeFromAdressAndNetmask($address, $netmask, &$firstAddress,
                                                           &$lastAddress = null,
                                                           $excludeBroadcastAndNetwork = false) {
      if ($address instanceof IPAddress) {
         $address = $address->getBinary();
      }
      if ($netmask instanceof IPNetmask) {
         $netmask = $netmask->getBinary();
      }
      $start = [];
      $end   = [];
      for ($i = 0; $i < 4; ++$i) {
         $start[$i] = IPAddress::convertNegativeIntegerToPositiveFloat($address[$i] & $netmask[$i]);
         $end[$i]   = IPAddress::convertNegativeIntegerToPositiveFloat($address[$i] | ~$netmask[$i]);
      }

      if ($excludeBroadcastAndNetwork) {
         IPAddress::addValueToAddress($start, 1);
         IPAddress::addValueToAddress($end, -1);
      }

      if ($firstAddress instanceof IPAddress) {
         $firstAddress->setAddressFromBinary($start);
      } else {
         $firstAddress = $start;
      }

      if ($lastAddress instanceof IPAddress) {
         $lastAddress->setAddressFromBinary($end);
      } else {
         $lastAddress = $end;
      }
   }


   /**
    * \brief Recreate network tree
    * Among others, the migration create plan tree network. This method allows to recreate the tree.
    * You can also use it if you suspect the network tree to be corrupted.
    *
    * First, reset the tree, then, update each network by its own field, letting
    * CommonImplicitTreeDropdown working such as it would in case of standard update
    *
    * @return nothing
   **/
   static function recreateTree() {
      global $DB;

      // Reset the tree
      $DB->update(
         'glpi_ipnetworks', [
            'ipnetworks_id'   => 0,
            'level'           => 1,
            'completename'    => new \QueryExpression($DB->quoteName('name'))
         ], [true]
      );

      // Foreach IPNetwork ...
      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable()
      ]);

      $network = new self();

      while ($network_entry = $iterator->next()) {
         if ($network->getFromDB($network_entry['id'])) {
            $input = $network->fields;
            // ... update it by its own entries
            $network->update($input);
         }
      }
   }


   /**
    * @since 0.84
    *
    * @param $itemtype
    * @param $base                  HTMLTableBase object
    * @param $super                 HTMLTableSuperHeader object (default NULL)
    * @param $father                HTMLTableHeader object (default NULL)
    * @param $options      array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      if ($itemtype != 'IPAddress') {
         return;
      }

      $column_name = __CLASS__;
      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $content     = self::getTypeName();
      $this_header = $base->addHeader($column_name, $content, $super, $father);
      $this_header->setItemType(__CLASS__);
   }


   /**
    * @since 0.84
    *
    * @param $row                HTMLTableRow object (default NULL)
    * @param $item               CommonDBTM object (default NULL)
    * @param $father             HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                            HTMLTableCell $father = null, array $options = []) {
      global $DB, $CFG_GLPI;

      if (empty($item)) {
         if (empty($father)) {
            return;
         }
         $item = $father->getItem();
      }

      if ($item->getType() != 'IPAddress') {
         return;
      }

      $column_name = __CLASS__;
      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $header= $row->getGroup()->getHeaderByName('Internet', __CLASS__);
      if (!$header) {
         return;
      }

      $createRow            = (isset($options['createRow']) && $options['createRow']);
      $options['createRow'] = false;
      $network              = new self();

      foreach (self::searchNetworksContainingIP($item) as $networks_id) {
         if ($network->getFromDB($networks_id)) {

            if ($createRow) {
               $row = $row->createRow();
            }
            //TRANS: %1$s is address, %2$s is netmask
            $content = sprintf(__('%1$s / %2$s'), $network->getAddress()->getTextual(),
                               $network->getNetmask()->getTextual());
            if ($network->fields['addressable'] == 1) {
               $content = "<span class='b'>".$content."</span>";
            }
            $content = sprintf(__('%1$s - %2$s'), $content, $network->getLink());
            $this_cell = $row->addCell($header, $content, $father, $network);
         }
      }
   }


   /**
    * Show all available IPNetwork for a given entity
    *
    * @param $entities_id  entity of the IPNetworks (-1 for all entities)
    *                      (default -1)
   **/
   static function showIPNetworkProperties($entities_id = -1) {
      global $CFG_GLPI;

      $rand = mt_rand();
      self::dropdown(['entity' => $entities_id,
                           'rand'   => $rand]);

      $params = ['ipnetworks_id' => '__VALUE__'];

      Ajax::updateItemOnSelectEvent("dropdown_ipnetworks_id$rand", "show_ipnetwork_$rand",
                                    $CFG_GLPI["root_doc"]. "/ajax/dropdownShowIPNetwork.php",
                                    $params);

      echo "<span id='show_ipnetwork_$rand'>&nbsp;</span>\n";
   }


   /**
    * Override title function to display the link to reinitialisation of the network tree
    **/
   function title() {
      parent::title();

      if (Session::haveRight('internet', UPDATE)
          && Session::isViewAllEntities()) {

         echo "<div class='spaced' id='tabsbody'>";
         echo "<table class='tab_cadre_fixe'>";

         echo "<tr><td class='center'>";
         Html::showSimpleForm(IPNetwork::getFormURL(), 'reinit_network',
                              __('Reinit the network topology'));

         echo "</td></tr>";

         echo "</table>";
         echo "</div>";
      }
   }
}
