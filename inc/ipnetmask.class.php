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

/// Class IPNetmask
/// since version 0.84
class IPNetmask extends IPAddress {

   static protected $notable = true;


   /**
    * @param $ipnetmask (default '')
    * @param $version   (default 0)
   **/
   function __construct($ipnetmask='', $version=0) {

      // First, be sure that the parent is correctly initialised
      parent::__construct();

      // If $ipnetmask if empty, then, empty netmask !
      if ($ipnetmask != '') {

         // If $ipnetmask if an IPNetmask, then just clone it
         if ($ipnetmask instanceof IPNetmask) {
            $this->version = $ipnetmask->version;
            $this->textual = $ipnetmask->textual;
            $this->binary  = $ipnetmask->binary;
            $this->fields  = $ipnetmask->fields;

         } else {
            // Else, check a binary then a string
            if (!$this->setAddressFromBinary($ipnetmask)) {
               $this->setNetmaskFromString($ipnetmask, $version);
            }
         }
      }
   }


   static function getTypeName($nb=0) {
      return _n('Subnet mask', 'Subnet masks', $nb);
   }


   /**
    * \brief Create a Netmask from string
    *
    * Create a binary Netmask from dot notation (for instance : 255.255.255.0) or
    * integer (for instance /24). Rely on setAddressFromString()
    *
    * @param $netmask   string   netmask defined as textual
    * @param $version   integer  =4 or =6 : version of IP protocol
    *
    * @return false if the netmask is not valid or if it does not correspond to version
   **/
   function setNetmaskFromString($netmask, $version) {

      if (is_numeric($netmask)) {
         if ($netmask < 0) {
            return false;
         }
         // Transform the number of bits to IPv6 netmasks ...
         $nbBits = $netmask + (($version == 4) ? 96 : 0);
         if ($nbBits > 128) {
            return false;
         }
         $bits          = str_repeat("1", $nbBits).str_repeat("0", 128 - $nbBits);
         $this->version = $version;
         $this->textual = $netmask;
         $this->binary  = array();
         for ($i = 0 ; $i  < 4 ; $i++) {
            $localBits      = substr($bits, 32 * $i, 32);
            $this->binary[] = bindec($localBits);
         }

      } else {
         if (!$netmask = $this->setAddressFromString($netmask)) {
            return false;
         }
         if ($version != $this->getVersion()) {
            return false;
         }
         if ($version == 4) {
            for ($i = 0 ; $i < 3 ; $i++) {
               $this->binary[$i] = 0xffffffff;
            }
         }
      }

      if ($version == 4) {
         $mask    = decbin($this->binary[3]);
         $textual = array();
         for ($i = 0 ; $i < 4 ; $i++) {
            $textual[] = bindec(substr($mask, 8 * $i, 8));
         }
         $this->textual = implode(".", $textual);
      }
      return true;
   }
}
?>
