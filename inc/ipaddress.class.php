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
// Purpose of file: Represent an IPv4 or an IPv6 address. Both textual (ie. human readable)
// and binary (ie. : used for request) are present
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/** Class IPAddress : Represents an IPv4 or an IPv6 address. Both textual (ie. human readable)
* and binary (ie. : used for SQL requests) are present inside the DB.
* The class itself contains three protected attributes. If the address is valid, then, these
* attributes are not empty.
* This object is usefull for SQL research and binary<=>textual conversions. There is no update
* mecanism. Entries in the database are create "on the fly" when creating or updating addresses
* To add an address to an item, the form must include the showInputField() method that display
* the HTML TEXTAREA field with previous addresses. When validating the addresses, the item must
* call, before updating or adding the item the checkInputFromItem() method. This method will
* validate all the IP addresses and sorting them as invalid, new or already defined. After having
* updating or adding the item (ie. : when the item exists and the IPAddress can be attached to
* it), the item must call updateDatabase() with the array of addresses. This method add new
* addresses, remove unused addresses and return an array of the addresses attached to the item,
* for the item to update its own IP Address cache field
* @since version 0.84
* \warning textual (ie. human readable) representation is not unique for IPv6 addresses :
* 2001:db8:0:85a3\::ac1f:8001 = 2001:db8:0:85a3:0:0:ac1f:8001
**/
class IPAddress extends CommonDBChild {

   // From CommonDBChild
   public $itemtype              = 'itemtype';
   public $items_id              = 'items_id';
   public $dohistory             = true;
   public $mustBeAttached        = true;
   public $inheritEntityFromItem = true;

   /// $version (integer) : version of the adresse. Should be 4 or 6, or empty if not valid address
   protected $version = '';
   /// $this->textual (string) : human readable of the IP adress (for instance : 192.168.0.0,
   /// 2001:db8:0:85a3\::ac1f:8001)
   protected $textual = '';
   /// $this->binary (bytes[4]) : binary version for the SQL requests. For IPv4 addresses, the
   /// first three bytes are set to [0, 0, 0xffff]
   protected $binary = '';


   function __construct($ipaddress = "") {

      // First, be sure that the parent is correctly initialised
      parent::__construct();

      // If $ipaddress if empty, then, empty address !
      if (!empty($ipaddress)) {

         // If $ipaddress if an IPAddress, then just clone it
         if ($ipaddress instanceof IPAddress) {
            $this->version = $ipaddress->version;
            $this->textual = $ipaddress->textual;
            $this->binary  = $ipaddress->binary;
            $this->fields  = $ipaddress->fields;

         } else {
            // Else, check a binary then a string
            if (!$this->setAddressFromBinary($ipaddress)) {
               $this->setAddressFromString($ipaddress);
            }
         }
      }
   }


   function canCreate() {

      if (!Session::haveRight('internet', 'w')) {
         return false;
      }

      if (!empty($this->fields['itemtype']) && !empty($this->fields['items_id'])) {
         $item = new $this->fields['itemtype']();
         if ($item->getFromDB($this->fields['items_id'])) {
            return $item->canCreate();
         }
      }

      return true;
   }


   function canView() {

      if (!Session::haveRight('internet', 'r')) {
         return false;
      }

      if (!empty($this->fields['itemtype']) && !empty($this->fields['items_id'])) {
         $item = new $this->fields['itemtype']();
         if ($item->getFromDB($this->fields['items_id'])) {
            return $item->canView();
         }
      }

      return true;
   }


   static function getTypeName($nb=0) {

      return _n('IP addresses', 'IP address', $nb);
   }


   /**
    * \brief Fill an array from the the local address object
    * Fill an array from the the local address object. Usefull for feeding $input variable for
    * preparing input to alter database.
    * If the field name is empty, then, the field is not set
    * If the object is not valid, then, version = 0, textual = "" and binary = (0, 0, 0, 0)
    *
    * @param $array the array to Fill
    * @param $versionField the name of the key inside $array that contains de IP version number
    * @param $textualField the name of the key inside $array that contains de textual version
    * @param $binaryField the name of the key inside $array that contains de binary. Each element
    *        of the array is post-fixed by _i, with i the index
    *
    * @return result the array altered
   **/
   function setArrayFromAddress($array, $versionField, $textualField, $binaryField) {

      if (!empty($versionField)) {
         $version = $this->getVersion();
         if ($version !== false) {
            $array[$versionField] = $version;
         } else {
            $array[$versionField] = "0";
         }
      }

      if (!empty($textualField)) {
         $textual = $this->getTextual();
         if ($textual !== false) {
            $array[$textualField] = $textual;
         } else {
            $array[$textualField] = "";
         }
      }

      if (!empty($binaryField)) {
         $binary = $this->getBinary();
         for ($i = 0 ; $i < 4 ; ++$i) {
            if ($binary !== false) {
               $array[$binaryField."_".$i] = $binary[$i];
            } else {
               $array[$binaryField."_".$i] = '0';
            }
         }
      }
      return $array;
   }


   /**
    * \brief Fill the local address object from an array
    * Fill the local address object from an array. Usefull for reading $input
    *
    * @param $array the array to Fill
    * @param $versionField the name of the key inside $array that contains de IP version number
    * @param $textualField the name of the key inside $array that contains de textual version
    * @param $binaryField the name of the key inside $array that contains de binary. Each element
    *                     of the array is post-fixed by _i, with i the index
    *
    * If the field name is empty, then, the field is not set
    *
    * @return true is succeffully defined
   **/
   function setAddressFromArray($array, $versionField, $textualField, $binaryField) {
      // First, we empty the fields to notify that this address is not valid
      $this->disableAddress();

      if (!isset($array[$versionField])) {
         return false;
      }
      if (!isset($array[$textualField])) {
         return false;
      }
      if (!isset($array[$binaryField."_0"])
          || !isset($array[$binaryField."_1"])
          || !isset($array[$binaryField."_2"])
          || !isset($array[$binaryField."_3"])) {
         return false;
      }

      $this->version    = $array[$versionField];
      $this->textual    = $array[$textualField];
      $this->binary     = array();
      $this->binary[0]  = ($array[$binaryField."_0"] + 0);
      $this->binary[1]  = ($array[$binaryField."_1"] + 0);
      $this->binary[2]  = ($array[$binaryField."_2"] + 0);
      $this->binary[3]  = ($array[$binaryField."_3"] + 0);
      return true;
   }


   function prepareInputForAdd($input) {

      // Update $input to get informations from the local object
      $input = $this->setArrayFromAddress($input, "version", "name", "binary");

      // Don't forget that if we cannot set the object, then, $inout === false;
      if (!is_array($input)) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   function post_getFromDB () {

      // Don't forget set local object from DB field
      $this->setAddressFromArray($this->fields, "version", "name", "binary");
   }


   /**
    * Display the input field inside the form : a textarea that contains previous addresses for
    * the given item
    *
    * @param $itemtype type of the item that owns the addresses
    * @param $items_id id of the item that owns the addresses
    * @param $objectFieldName the name of the field inside the form
   **/
   static function showInputField($itemtype, $items_id, $objectFieldName) {
      global $DB;

      $query = "SELECT `name`
                FROM `glpi_ipaddresses`
                WHERE `items_id` = '$items_id'
                      AND `itemtype` = '$itemtype'
                ORDER BY `version`";

      $addresses = array();
      foreach ($DB->request($query) as $address) {
         $addresses[] = $address["name"];
      }
      echo "<textarea cols='45' rows='".(count($addresses) + 3)."' name='$objectFieldName' >".
             implode("\n", $addresses)."</textarea>\n";
   }


   /**
    * \brief check input validity from the object prepareInput*
    * Create the objects associated with each entry in the IP address field of the form of the item.
    * Each address is sort as :
    *            "previous" : addresses already registered for the given item
    *            "new" : addresses that are currently not attached to the item
    *            "invalid" : addresses that not valid
    *
    * @param $inputAddresses string of comma, CR, LF or space separated addresses
    * @param $itemtype type of the item this address has to be attached
    * @param $items_id id of the item this address has to be attached
    *
    * @return array of "previous", "new" and "invalid" addresses
   **/
   static function checkInputFromItem($inputAddresses, $itemtype, $items_id) {

      // replace delimiters by \n
      $delimiters = array(',', ' ', '\r', '\n');
      $mainDelim  = $delimiters[count($delimiters)-1]; // dernier
      array_pop($delimiters);

      foreach ($delimiters as $delimiter) {
         $inputAddresses = str_replace($delimiter, $mainDelim, $inputAddresses);
      }
      $invalidAddresses  = array();
      $previousAddresses = array();
      $newAddresses      = array();
      // then, check each address
      foreach (explode($mainDelim, $inputAddresses) as $ipaddress) {
         // Create the object
         $addressObject = new self();
         if ($addressObject->setAddressFromString($ipaddress, $itemtype, $items_id)) {
            // If it is valid and its ID is not NULL, then, the address already exists
            if ($addressObject->getID() > 0) {
               $previousAddresses[] = $addressObject;
            } else { // Otherwise, it is a new address
               $newAddresses[] = $addressObject;
            }
         } else {
            unset($addressObject); // Clear the object : we don't need it any more
            if (!empty($ipaddress)) {// There may be empty lines
               $invalidAddresses[] = addslashes($ipaddress);
            }
         }
      }
      return array("previous" => $previousAddresses,
                   "new"      => $newAddresses,
                   "invalid"  => $invalidAddresses);
   }


   /**
    * Update the database by removing old address and creating new ones.
    * Already known addresses remains
    *
    * @param $addressesFromCheck the array previously created by checkInputFromItem()
    * @param $itemtype type of the item this address has to be attached
    * @param $items_id id of the item this address has to be attached
    *
    * @return an array of all addresses attached the the item
    *         That should be use to fill cache IP field of the item
   **/
   static function updateDatabase($addressesFromCheck, $itemtype, $items_id) {
      global $DB;

      $currentAddresses  = array();
      $previousAddresses = array();

      // Get the addresses that was previously inside the database
      foreach ($addressesFromCheck["previous"] as $ipAddress) {
         $previousAddresses[] = $ipAddress->getID();
         $currentAddresses[]  = $ipAddress->getTextual();
      }

      // Remove all the addresses attached to the item except previous ones
      $query = "SELECT `id`, `name`
                FROM `glpi_ipaddresses`
                WHERE `itemtype` = '".$itemtype."'
                AND `items_id` = '".$items_id."'
                AND `id` NOT IN ('".implode("', '", $previousAddresses)."')";
      $addressObject = new self();
      foreach ($DB->request($query) as $previousAddress) {
         if ($addressObject->can($previousAddress["id"], "d")) {
            $addressObject->delete(array($addressObject->getIndexName() => $previousAddress["id"]));
         }
      }

      // Add the new addresses
      $newEntry = array("items_id" => $items_id,
                        "itemtype" => $itemtype);
      foreach ($addressesFromCheck["new"] as $ipAddress) {
         if (!$ipAddress->can(-1, 'w', $newEntry)) {
            continue;
         }
         if ($ipAddress->add($newEntry)) {
            $currentAddresses[] = $ipAddress->getTextual();
         }
      }

      // Return all the addresses attached to the item
      return $currentAddresses;
   }


   /**
    * Remove the unused addresses from the database. That is used when deleting an item, to clean
    * the database. That is also use by updateDatabase() to remove unuse addresses
    *
    * @param $itemtype type of the item this address has to be attached
    * @param $items_id id of the item this address has to be attached
    * @param $excludeFromDelete array of the IDs that must not be delete
   **/
   static function cleanAddress($itemtype, $items_id, $excludeFromDelete=array()) {
      global $DB;

      $addressObject = new IPaddress();
      $query = "SELECT `id`, `name`
                FROM `".$addressObject->getTable()."`
                WHERE `itemtype` = '".$itemtype."'
                      AND `items_id` = '".$items_id."'
                      AND `id` NOT IN ('".implode("', '", $excludeFromDelete)."')";
      $result = array();

      foreach ($DB->request($query) as $previousAddress) {
         if ($addressObject->can($previousAddress["id"], "d")) {
            $addressObject->delete(array($addressObject->getIndexName() => $previousAddress["id"]));
         }
      }
      return $result;
   }


   /**
    * Disable the address
   **/
   function disableAddress() {

      $this->version = "";
      $this->textual = "";
      $this->binary  = "";
   }


   /**
    * Check address validity
   **/
   function is_valid() {
      return (!empty($this->version) && !empty($this->textual) && !empty($this->binary));
   }


   function getVersion() {

      if (!empty($this->version)) {
         return $this->version;
      }
      return false;
   }


   function is_ipv4() {
      return ($this->getVersion() == 4);
   }


   function is_ipv6() {
      return ($this->getVersion() == 6);
   }


   function getTextual() {

      if (!empty($this->textual)) {
         return $this->textual;
      }
      return false;
   }


   function getBinary() {

      if (!empty($this->binary)) {
         return $this->binary;
      }
      return false;
   }


   /**
    * Transform an IPv4 address to IPv6
    *
    * @param $address (bytes[4] or bytes) the address to transform.
    *
    * @return IPv6 mapped address
   **/
   static function getIPv4ToIPv6Address($address) {

      if (is_numeric($address)) {
         return array(0, 0, 0xffff, $address);
      }
      if ((is_array($address)) && (count($address) == 4)) {
         return self::getIPv4ToIPv6Address($address[3]);
      }
      return false;
   }


   /**
    * Check an address to see if it is IPv4 mapped to IPv6 address
    *
    * @param $address (bytes[4]) the address to check
    *
    * @return true if the address is IPv4 mapped to IPv6
   **/
   static function isIPv4MappedToIPv6Address($address) {

      if (is_array($address) && (count($address) == 4)) {
         if (($address[0] == 0) && ($address[1] == 0) && ($address[2] == 0xffff)) {
            return true;
         }
         return false;
      }
      return false;
   }


   /**
    * \brief define an address from a string
    * Convert a textual address (string) to binary one. Opposite function that
    * setAddressFromBinary(). If item is valid ($itemtype not empty and $items_id > 0) then first
    * try to find it inside the database and load it from database.
    * \warning The resulting binary form is created inside the current object
    *
    * @param $address (string) textual (ie. human readable) address
    * @param $itemtype type of the item this address has to be attached
    * @param $items_id id of the item this address has to be attached
    *
    * @return true if the address is valid.
   **/
   function setAddressFromString($address, $itemtype = "", $items_id = -1) {
      global $DB;

      $this->disableAddress();
      if (empty($address) || !is_string($address)) {
         return false;
      }
      if (!empty($itemtype) && ($items_id > 0)) {
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `items_id` = '$items_id'
                         AND `itemtype` = '$itemtype'
                         AND `name` = '$address'";
         $result = $DB->query($query);
         if ($DB->numrows($result) == 1) {
            $line = $DB->fetch_assoc($result);
            if ($this->getFromDB($line["id"])) {
               return true;
            }
         }
      }
      $singletons = explode(".", $address);
      // First, check to see if it is an IPv4 address
      if (count($singletons) == 4) {
         $binary = 0;
         foreach ($singletons as $singleton) {
            if (!is_numeric($singleton)) {
               return false;
            }
            $singleton = intval($singleton);
            if (($singleton < 0) || ($singleton > 255)) {
               return false;
            }
            $binary *= 256;
            $binary += intval($singleton);
         }
         $this->version = 4;
         $this->textual = $address;
         $this->binary  = self::getIPv4ToIPv6Address($binary);
         return true;
      }

      // Else, it should be an IPv6 address
      $singletons = explode(":", $address);
      // Minimum IPv6 address is "::". So, we check that there is at least 3 elements in the array
      if ((count($singletons) > 2) && (count($singletons) < 9)) {
         $expanded = array();
         foreach ($singletons as $singleton) {
            if ($singleton == "") {
               $expanded = array_merge($expanded, array_fill(0, 9 - count($singletons), "0000"));
            } else {
               if (!preg_match("/^[0-9A-Fa-f]{1,4}$/", $singleton, $regs)) {
                  return false;
               }
               $expanded[] = str_pad($singleton, 4, "0", STR_PAD_LEFT);
            }
         }
         // Among others, be sure that it is not a MAC address (ie 6 digits seperated by ':')
         if (count($expanded) != 8) {
            return false;
         }
         $binary = array();
         for ($i = 0 ; $i < 4 ; $i++) {
            $binary[$i] = hexdec($expanded[2 * $i + 0].$expanded[2 * $i + 1]);
         }
         $this->version = 6;
         $this->textual = $address;
         $this->binary  = $binary;
         return true;
      }
      return false;
   }


   /**
    * \brief define an address from a binary
    * Convert a binary address (bytes[4]) to textual one. Opposite function that
    * setAddressFromString(). If item is valid ($itemtype not empty and $items_id > 0) then first
    * try to find it inside the database and load it from database. textual version is condensed
    * one (ie : 2001:db8:0:85a3\::ac1f:8001 rather than 2001:0db8:0000:85a3:0000:0000:ac1f:8001)
    * \warning The resulting binary form is created inside the current object
    *
    * @param $address (bytes[4]) binary (ie. SQL requests) address
    * @param $itemtype type of the item this address has to be attached
    * @param $items_id id of the item this address has to be attached
    *
    * @return true if the address is valid.
   **/
   function setAddressFromBinary($address, $itemtype="", $items_id=-1) {
      global $DB;

      $this->disableAddress();
      if ((!is_array($address)) || (count($address) != 4)) {
         return false;
      }
      if (!empty($itemtype) && ($items_id > 0)) {
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `items_id` = '$items_id'
                         AND `itemtype` = '$itemtype'";

         for ($i = 0 ; $i < 4 ; ++$i) {
            $query .= " AND `binary_".$i."` = '".$address[$i]."'";
         }
         $result = $DB->query($query);
         if ($DB->numrows($result) == 1) {
            $line = $DB->fetch_assoc($result);
            if ($this->getFromDB($line["id"])) {
               return true;
            }
         }
      }
      $binary      = array();
      $textual     = array();
      $currentNull = "";
      foreach ($address as $singleton) {
         if (is_numeric($singleton)) {
            $singleton = floatval($singleton);
         }
         if (is_float($singleton) || is_double($singleton)) {
            $binary[]  = floatval($singleton);
            $singleton = str_pad(dechex($singleton), 8, "0", STR_PAD_LEFT);
            $elt       = ltrim(substr($singleton, 0, 4), "0");
            if (empty($elt)) {
               $textual[]    = "0";
               $currentNull .= "1";
            } else {
               $currentNull .= "0";
               $textual[]    = $elt;
            }
            $elt = ltrim(substr($singleton, 4, 4), "0");
            if (empty($elt)) {
               $textual[]    = "0";
               $currentNull .= "1";
            } else {
               $currentNull .= "0";
               $textual[]    = $elt;
            }
         } else {
            return false;
         }
      }

      if (isset($binary) && (count($binary) == 4)) {
         if (self::isIPv4MappedToIPv6Address($binary)) {
            $this->version = 4;
         } else {
            $this->version = 6;
         }
      } else {
         return false;
      }

      $this->binary = $binary;
      if ($this->getVersion() == 4) {
         $hexValue = str_pad($textual[6], 4, "0", STR_PAD_LEFT).str_pad($textual[7], 4, "0",
                                                                        STR_PAD_LEFT);
         $textual  = array();
         for ($i = 0 ; $i < 4 ; $i++) {
            $textual[] = hexdec($hexValue[2*$i+0].$hexValue[2*$i+1]);
         }
         $textual = implode('.', $textual);
      } else {
         foreach (array("11111111", "1111111", "111111", "11111", "1111", "111", "11") as $elt) {
            $pos = strpos($currentNull, $elt);
            if ($pos !== false) {
               $first = array_slice($textual, 0, $pos);
               if (count($first) == 0) {
                  $first = array("");
               }
               $second = array_slice($textual, $pos + strlen($elt));
               if (count($second) == 0) {
                  $second = array("");
               }
               $textual = array_merge($first, array(""), $second);
               break;
            }
         }
         $textual = implode(':', $textual);
      }
      $this->textual = $textual;
      return true;
   }


   /**
    * \brief add value to the address for iterator on addresses
    *
    * @param $address (in and out) the address to increment or decrement
    * @param $value the value to add or remove. Must be betwwen -0xffffffff and +0xffffffff
    *
    * @return true if the increment is valid
   **/
   static function addValueToAddress(&$address, $value) {

      if (!is_array($address)
          || (count($address) != 4)
          || !is_numeric($value)
          || ($value < -0xffffffff)
          || ($value > 0xffffffff)) {
         return false;
      }

      for ($i = 3 ; $i >= 0 ; --$i) {
         $address[$i] += $value;
         if ($address[$i] < 0) {
            $address[$i] += (0x80000000 * 2);
            $value        = -1; // For next value for right to left ...
         } else if ($address[$i] > 0xffffffff) {
            $address[$i] -= (0x80000000 * 2);
            $value        = 1; // For next value for right to left ...
         } else {
            break;
         }
      }

      return true;
   }


   /**
    * \brief get absolute value of an integer
    * Convert a negative integer to positiv float. That is usefull as integer, in PHP are signed 32
    * bits values. As such, they are limited from +2 147 483 647 to −2 147 483 648. Thus, when
    * working on integer with bit-wise boolean operations (&, |, ^, ~), the sign of the operand
    * remain inside the result. That make problem as IP address are only positiv ones.
    *
    * @param $value the integer that we want the absolute value
    *
    * @return float value that is the absolute of $value
    *
   **/
   static function convertNegativeIntegerToPositiveFloat($value) {

      if (intval($value) && ($value < 0)) {
         $value = floatval($value) + floatval(0x80000000 * 2);
      }
      return $value;
   }

   /**
    * Search IP Addresses
    *
    * @param $IPaddress the address to search
    *
    * @return (array) each value of the array (corresponding to one IPAddress) is an array of the
    *                 items from the master item to the IPAddress
   **/
   static function getItemsByIPAddress($IPaddress) {
      global $DB;

      // We must resolv binary address :
      //    1°) we don't know if the IP address is valid
      //    2°) we don't know its version
      //    3°) binary request is more efficient than textual one (polymorphism of IPv6 addresses)
      $address = new self();

      if (!$address->setAddressFromString($IPaddress)) {
         return array();
      }

      $query = "SELECT `gip`.`id`
                FROM `glpi_ipaddresses` as gip
                WHERE `gip`.`version`='".$address->version."'\n";
      $startIndex = (($address->version == 4) ? 3 : 1);
      $binaryIP = $address->getBinary();
      for ($i = $startIndex ; $i < 4 ; ++$i) {
         $query .= "AND `gip`.`binary_$i` = '".$binaryIP[$i]."'";
      }

      $addressesWithItems = array();
      foreach ($DB->request($query) as $result) {
         if ($address->getFromDB($result['id'])) {
            $addressesWithItems[] = array_merge(array_reverse($address->recursivelyGetItems()),
                                                array(clone $address));
         }
      }
      return $addressesWithItems;
   }

   /**
    * Get an Object ID by its IP address (only if one result is found in the entity)
    *
    * @param $value the ip address
    * @param $entity the entity to look for
    *
    * @return an array containing the object ID
    *         or an empty array is no value of serverals ID where found
   **/
   static function getUniqueItemByIPAddress($value, $entity) {

      $addressesWithItems = self::getItemsByIPAddress($value);

      if (count($addressesWithItems) == 1) {
         $addressWithItems = $addressesWithItems[0];
         $item = $addressWithItems[0];
         if ($item->getEntityID() == $entity) {
            $result = array("id"       => $item->getID(),
                            "itemtype" => $item->getType());
            unset($addressesWithItems);
            return $result;
         }

      }

      return array();
   }


   /**
    * Check if two addresses are equals
    *
    * @param $ipaddress the ip address to check with this
    *
    * @return return true if and only if both addresses are binary equals.
   **/
   function equals($ipaddress) {

      // To normalise the address, just make new one
      $ipaddress = new self($ipaddress);

      if ((count($this->binary) != 4) || (count($ipaddress->binary) != 4)
          || ($this->version != $ipaddress->version)) {
         return false;
      }

      for ($index = 0 ; $index < 4 ; $index ++) {
         if ($this->binary[$index] != $ipaddress->binary[$index]) {
            return false;
         }
      }

      return true;
   }
}
?>
