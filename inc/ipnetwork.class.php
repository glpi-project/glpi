<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
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
class IPNetwork extends CommonDropdown {

   public $dohistory = true;


   function canCreate() {
      return Session::haveRight('internet', 'w');
   }


   function canView() {
      return Session::haveRight('internet', 'r');
   }


   static function getTypeName($nb=0) {
      return _n('IP network', 'IP networks', $nb);
   }


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[10]['table']    = $this->getTable();
      $tab[10]['field']    = 'version';
      $tab[10]['name']     = __('IP version');

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'address';
      $tab[11]['name']     = IPAddress::getTypeName();

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'netmask';
      $tab[12]['name']     = IPNetmask::getTypeName();

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'gateway';
      $tab[13]['name']     = __('Gateway');

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

      if (isset($this->fields["address"]) && isset($this->fields["netmask"])) {
         $this->fields["network"] = $this->fields["address"]."/".$this->fields["netmask"];
      }
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'     => 'network',
                         'label'    => self::getTypeName(1),
                         'type'     => 'text',
                         'list'     => true,
                         'comment' => $LANG['Internet'][18]),
                   array('name'  => 'gateway',
                         'label' => __('Gateway'),
                         'type'  => 'text',
                         'list'  => true));
   }


   function prepareInput($input) {
      global $LANG;

      // If $this->fields["network"] is not set then, we are adding a new network
      // Or if $this->fields["network"] != $input["network"] we a updating the network
      $address = new IPAddress();
      $netmask = new IPNetmask();
      if (!isset($this->fields["network"]) || ($input["network"] != $this->fields["network"])) {
         $network = explode ("/", $input["network"]);
         if (count($network) != 2) {
            return array('error' => $LANG['Internet'][19], 'input' => false);
         }
         if (!$address->setAddressFromString($network[0])) {
            return array('error' => $LANG['Internet'][20], 'input' => false);
         }
         if (!$netmask->setNetmaskFromString($network[1], $address->getVersion())) {
            return array('error' => $LANG['Internet'][21], 'input' => false);
         }

         // After checking that address and netmask are valid, modify the address to be the "real"
         // network address : the first address of the network. This is not required for SQL, but
         // that looks better for the human
         self::computeNetworkRangeFromAdressAndNetmask($address, $netmask, $address);

         // Now, we look for already existing same network inside the database
         $params = array("address" => $address,
                         "netmask" => $netmask);
         if (isset($this->fields["id"])) {
            $params["exclude IDs"] = $this->fields["id"];
         }

         if (isset($this->fields["entities_id"])) {
            $entity = $this->fields["entities_id"];
         } else {
            $entity = -1;
         }
         $sameNetworks = self::searchNetworks("equals", $params, $entity);
         // Check unicity !
         if ($sameNetworks && count($sameNetworks) > 0) {
            return array('error' => __('Network already defined in visible entities'),
                         'input' => false);
         }
         $networkUpdate = true;
         // Then, update $input to reflect the network and the netmask
         $input = $address->setArrayFromAddress($input, "version", "address", "address");
         $input = $netmask->setArrayFromAddress($input, "", "netmask", "netmask");
      } else {
         // If netmask and address are not modified, then, load them from DB to check the validity
         // of the gateway
         $networkUpdate = false;
         $address->setAddressFromArray($this->fields, "version", "address", "address");
         $netmask->setAddressFromArray($this->fields, "version", "netmask", "netmask");
      }

      $returnValue = array();
      // If the gateway has been altered, or the network informations (address or netmask) changed,
      // then, we must revalidate the gateway !
      if (!isset($this->fields["gateway"])
          || ($input["gateway"] != $this->fields["gateway"])
          || $networkUpdate) {
         $gateway = new IPAddress();

         if (!empty($input["gateway"])) {
            if (!$gateway->setAddressFromString($input["gateway"])
                || (!self::checkIPFromNetwork($gateway, $address, $netmask))){
               $returnValue['error'] = $LANG['Internet'][23];

               if (!empty($this->fields["gateway"])) {
                  if (!$gateway->setAddressFromString($this->fields["gateway"])
                      || (!self::checkIPFromNetwork($gateway, $address, $netmask))) {
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


   /**
    * \brief Search any networks that contains the given IP
    * \ref ipAddressToNetwork
    *
    * @param $IP (see \ref parameterType) given IP
    * @param $entityID scope of the search (parents and childrens are check)
    * @param $fields list of fields to return in the result (default : only ID of the networks)
    *
    * @return list of networks (see searchNetworks())
   **/
   static function searchNetworksContainingIP($IP, $entityID = -1, $fields = "") {

      return self::searchNetworks(array("relation" => "contains",
                                        "address"  => $IP,
                                        "netmask"  => array(0xffffffff, 0xffffffff,
                                                            0xffffffff, 0xffffffff),
                                        "fields"   => $fields),
                                  $entityID);
   }


   /**
    * Search networks relative to a given network
    *
    * $condition array possible elements :
    *    - fields : the fields of the network we wish to retrieve (single field or array of
    *               fields). This parameter will impact the result of the function
    *    - address (see \ref parameterType) : the address for the query
    *    - netmask (see \ref parameterType) : the netmask for the query
    *    - exclude IDs : the IDs to exclude from the query (for instance, $this->getID())
    *
    * @param $relation type of relation ("is contained by", "equals" or "contains")
    *                  regarding the networks given as parameter
    * @param $condition array of elements to select the good arrays (see Parameters above)
    * @param $entityID the entity on which the selection should occur (-1 => the current active
    *                  entity)
    * @param $version version of IP to look (only use when using arrays or string as input for
    *                 address or netmask
    * @return array of networks found. If we want request several field, the return value will be
    *                 an array of array
    *
    * \warning The order of the elements inside the result are ordered from the nearest one to the
    *          further. (ie. 0.0.0.0 is the further of whatever network if you lool for ones that
    *          contains the current network.
   **/
   static function searchNetworks($relation, $condition, $entityID = -1, $version = 0) {
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
         $fields = array($fields);
      }

      $FIELDS = "`".implode("`, `", $fields)."`";

      $startIndex = (($version == 4) ? 3 : 1);

      $addressDB = array('address_0', 'address_1', 'address_2', 'address_3');
      $netmaskDB = array('netmask_0', 'netmask_1', 'netmask_2', 'netmask_3');

      $WHERE = "";
      if ((isset($condition["address"])) && isset($condition["netmask"])) {

         if (is_string($condition["address"])) {
            $addressPa = new IPAddress();
            if (!$addressPa->setAddressFromString($condition["address"])) {
               return false;
            }
         } else {
            $addressPa = $condition["address"];
         }

         if ($addressPa instanceof IPAddress) {
            if (($version != 0) && ($version != $addressPa->getVersion())) {
               return false;
            }
            $version = $addressPa->getVersion();
            $addressPa = $addressPa->getBinary();
         }

         if (!is_array($addressPa) || (count($addressPa) != 4)) {
            return false;
         }

         if (is_string($condition["netmask"])) {
            $netmaskPa = new IPNetmask();
            if (!$netmaskPa->setNetmaskFromString($condition["netmask"])) {
               return false;
            }
         } else {
            $netmaskPa = $condition["netmask"];
         }

         if ($netmaskPa instanceof IPNetmask) {
            if (($version != 0) && ($version != $netmaskPa->getVersion())) {
               return false;
            }
            $version = $netmaskPa->getVersion();
            $netmaskPa = $netmaskPa->getBinary();
         }

         if (!is_array($netmaskPa) || (count($netmaskPa) != 4)) {
            return false;
         }
         $startIndex = (($version == 4) ? 3 : 0);

         if ($relation == "equals") {
            for ($i = $startIndex ; $i < 4 ; ++$i) {
               $WHERE .= " AND (`".$addressDB[$i]."` & '".$netmaskPa[$i]."')=
                               ('".$addressPa[$i]."' & '".$netmaskPa[$i]."')
                           AND ('".$netmaskPa[$i]."' = `".$netmaskDB[$i]."`)";
            }
         } else {
            for ($i = $startIndex ; $i < 4 ; ++$i) {
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
         if (isset($_SESSION['glpiactive_entity'])) {
            $entityID = $_SESSION['glpiactive_entity'];
         } else {
            $entityID = 0; // Case of migration ...
         }
      }
      $entitiesID = array();
      switch ($relation) {
         case "is contained by" :
            $ORDER_ORIENTATION = 'ASC';
            $entitiesID        = getSonsOf('glpi_entities', $entityID);
            break;

         case "contains" :
            $ORDER_ORIENTATION = 'DESC';
            $entitiesID        = getAncestorsOf('glpi_entities', $entityID);
          break;

         case "equals" :
            $ORDER_ORIENTATION = '';
            $entitiesID        = getSonsAndAncestorsOf('glpi_entities', $entityID);
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

      $ORDER = array();
      for ($i = $startIndex ; $i < 4 ; ++$i) {
         $ORDER[] = "BIT_COUNT(`".$netmaskDB[$i]."`) $ORDER_ORIENTATION";
      }

      $query = "SELECT $FIELDS
                FROM `glpi_ipnetworks`
                WHERE $WHERE
                ORDER BY ".implode(', ', $ORDER);
      // By ordering on the netmask, we ensure that the first element is the nearest one (ie:
      // the last should be 0.0.0.0/0.0.0.0 of x.y.z.a/255.255.255.255 regarding the interested
      // element)

      $returnValues = array();
      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (count($fields) > 1) {
               $returnValue = array();
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


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('NetworkName',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
   }


   /**
    * Get SQL WHERE statement for requesting elements that are contained inside the current network
    *
    * @param $tableName name of the table containing the element (for instance : glpi_ipaddresses)
    * @param $binaryFieldPrefix prefix of the binary version of IP address (binary for glpi
    *                           ipaddresses)
    * @param $versionField the name of the field containing the version inside the database
    *
    * @return SQL request "WHERE" element
   **/
   function getWHEREForMatchingElement($tableName, $binaryFieldPrefix, $versionField) {

      $version = $this->fields["version"];
      $start   = NULL;
      $this->computeNetworkRange($start);

      $result = array();
      for ($i = ($version == 4 ? 3 : 0) ; $i < 4 ; ++$i) {
         $result[] = "(`$tableName`.`".$binaryFieldPrefix."_$i` & '".$this->fields["netmask_$i"]."')
                      = ('".$start[$i]."')";
      }

      $result = "`$tableName`.`version` = '$version'
                AND (".implode(" AND ", $result).")";

      return $result;
   }


   /**
    * Get SQL searching criterion to know if a NetworkName uses this as FQDN
    *
    * @return SQL request "WHERE" element
   **/
   function getCriterionForMatchingNetworkNames() {

      return "`id` IN (SELECT `items_id`
                       FROM `glpi_ipaddresses`
                       WHERE `itemtype` = 'NetworkName'
                             AND ".$this->getWHEREForMatchingElement('glpi_ipaddresses', 'binary',
                                                                     'version')."
                       GROUP BY `items_id`)";
   }


   /**
    * Check to see if an IP is inside a given network
    * See : \ref ipAddressToNetwork
    *
    * @param $address (see \ref parameterType) the IP address to check
    * @param $networkAddress (see \ref parameterType) the address of the network
    * @param $networkNetmask (see \ref parameterType) the netmask of the network
    * @param $version of IP : only usefull for binary array as input
    *
    * @return true if the network owns the IP address
   **/
   static function checkIPFromNetwork($address, $networkAddress, $networkNetmask, $version = 0) {

      $IPNetmask  = array(0xffffffff, 0xffffffff, 0xffffffff, 0xffffffff);
      $relativity = self::checkNetworkRelativity($address, $IPNetmask, $networkAddress,
                                                  $networkNetmask, $version);
      return ($relativity == "equals") || ($relativity == "second contains first");
   }


   /**
    * \brief Check network relativity
    * Check how networks are relative (fully different, equals, first contains second, ...)
    *
    * @param $firstAddress (see \ref parameterType) address of the first network
    * @param $firstNetmask (see \ref parameterType) netmask of the first network
    * @param $secondAddress (see \ref parameterType) address of the second network
    * @param $secondNetmask (see \ref parameterType) netmask of the second network
    * @param $version of IP : only usefull for binary array as input
    *
    * @return string :
    *           - "different version" : there is different versions between elements
    *           - "?" : There is holes inside the netmask and both networks can partially intersect
    *           - "different" : the networks are fully different ;
    *           - "equals" : both networks are equals
    *           - "first contains second" "second contains first" : one include the other
    */
   static function checkNetworkRelativity($firstAddress, $firstNetmask, $secondAddress,
                                          $secondNetmask, $version=0) {
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
      for ($i = $startIndex ; $i < 4 ; ++$i) {
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

      for ($i = $startIndex ; $i < 4 ; ++$i) {
         if ((($firstAddress[$i] & $mask[$i]) ^ ($secondAddress[$i] & $mask[$i])) != 0) {
            return "different";
         }
      }
      return $result;
   }


   /**
    * Compute the first and the last address of $this
    * \see computeNetworkRangeFromAdressAndNetmask()
   **/
   function computeNetworkRange(&$start, &$end=NULL, $excludeBroadcastAndNetwork=false) {

      self::computeNetworkRangeFromAdressAndNetmask($this->getAddress(), $this->getNetmask(),
                                                    $start, $end, $excludeBroadcastAndNetwork);
   }


   /**
    * \brief Compute the first and the last address of a network.
    * That is usefull, for instance, to compute the "real" network address (the first address)
    * or the broadcast address of the network
    *
    * @param $address (see \ref parameterType) the address of the network
    * @param $netmask (see \ref parameterType) its netmask
    * @param $firstAddress (see \ref parameterType - in/out) the first address (ie real
    *                      address of the network)
    * @param $lastAddress (see \ref parameterType - in/out) the lastAddress of the network
    *                      (ie. : the broadcast address)
    * @param $excludeBroadcastAndNetwork (boolean) exclude broadcast and network address from the
    *                      result
   **/
   static function computeNetworkRangeFromAdressAndNetmask($address, $netmask, &$firstAddress,
                                                           &$lastAddress=NULL,
                                                           $excludeBroadcastAndNetwork=false) {
      if ($address instanceof IPAddress) {
         $address = $address->getBinary();
      }
      if ($netmask instanceof IPNetmask) {
         $netmask = $netmask->getBinary();
      }
      $start = array();
      $end   = array();
      for ($i = 0 ; $i < 4 ; ++$i) {
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

}
?>
