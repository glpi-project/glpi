<?php
/*
 * @version $Id: networkname_ipnetwork.class.php 16719 2012-01-03 14:29:38Z remi $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class NetworkName_IPNetwork : Connection between NetworkName and IPNetwork
/// @since 0.84
class NetworkName_IPNetwork extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'NetworkName';
   public $items_id_1 = 'networknames_id';

   public $itemtype_2 = 'IPNetwork';
   public $items_id_2 = 'ipnetworks_id';

   /**
    * Update IPNetwork's dependency
   **/
   static function linkIPAddressFromIPNetwork(IPNetwork $network) {
      global $DB;

      $thisTable = "glpi_networknames_ipnetworks";
      $ipnetworks_id = $network->getID();

      // First, remove all previous networks
      $query = "DELETE FROM `$thisTable`
                WHERE `ipnetworks_id`='$ipnetworks_id'";
      $DB->query($query);

      // Then add all current NetworkNames
      $query = "INSERT INTO `$thisTable` (`ipnetworks_id`, `networknames_id`)
                       SELECT $ipnetworks_id, `items_id`
                       FROM `glpi_ipaddresses`
                       WHERE `itemtype` = 'NetworkName'
                       AND ".$network->getWHEREForMatchingElement('glpi_ipaddresses', 'binary',
                                                                  'version')."
                       GROUP BY `items_id`";

     $DB->query($query);
   }

   /**
    * Update NetworkName's dependency
   **/
   static function updateIPAddressOfIPNetwork(NetworkName $networkname) {
      global $DB;

      $thisTable = "glpi_networknames_ipnetworks";
      $networknames_id = $networkname->getID();

      // First, remove all previous networks
      $query = "DELETE FROM `$thisTable`
                WHERE `networknames_id`='$networknames_id'";
      $DB->query($query);

      $query = "SELECT `version`, `name`, `binary_0`, `binary_1`, `binary_2`, `binary_3`
                FROM `glpi_ipaddresses`
                WHERE `items_id`='$networknames_id'
                AND `itemtype`='NetworkName'";
      $ipaddress = new IPAddress();
      $networks_ids = array();
      foreach ($DB->request($query) as $address) {
         $ipaddress->setAddressFromArray($address, 'version', 'name', 'binary');
         $networks_ids = array_merge($networks_ids,
                                     IPNetwork::searchNetworksContainingIP($ipaddress));
      }

      $query = "INSERT INTO `$thisTable` (`networknames_id`, `ipnetworks_id`) VALUES";
      $first = true;
      foreach ($networks_ids as $network_id) {
         if (!$first)
            $query .= ',';
         $query .= " ('$networknames_id', '$network_id')";
         $first = false;
      }
      $DB->query($query);
   }
}

?>
