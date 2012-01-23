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
    *
    * @param $network IPNetwork object
   **/
   static function linkIPAddressFromIPNetwork(IPNetwork $network) {
      global $DB;

      $linkObject    = new self();
      $linkTable     = $linkObject->getTable();
      $ipnetworks_id = $network->getID();

      // First, remove all links of the current Network
      $query = "SELECT `id`
                FROM `$linkTable`
                WHERE `ipnetworks_id` = '$ipnetworks_id'";
      foreach ($DB->request($query) as $link) {
         $linkObject->delete(array('id' => $link['id']));
      }

      // Then, look each IP address contained inside current Network
      $query = "SELECT $ipnetworks_id as ipnetworks_id, `items_id` as networknames_id
                FROM `glpi_ipaddresses`
                WHERE `itemtype` = 'NetworkName'
                      AND ".$network->getWHEREForMatchingElement('glpi_ipaddresses', 'binary',
                                                                 'version')."
                GROUP BY `items_id`";
      foreach ($DB->request($query) as $link) {
         $linkObject->add($link);
      }
   }


   /**
    * Update NetworkName's dependency
    *
    * @param $networkname NetworkName object
   **/
   static function updateIPAddressOfIPNetwork(NetworkName $networkname) {
      global $DB;

      $linkObject      = new self();
      $linkTable       = $linkObject->getTable();
      $networknames_id = $networkname->getID();

      $query = "SELECT `id`
                FROM `$linkTable`
                WHERE `networknames_id` = '$networknames_id'";

      // First, remove all previous networks
      foreach ($DB->request($query) as $link) {
         $linkObject->delete(array('id' => $link['id']));
      }

      // Then, get all IP addresses of local network
      $query = "SELECT `version`, `name`, `binary_0`, `binary_1`, `binary_2`, `binary_3`
                FROM `glpi_ipaddresses`
                WHERE `items_id` = '$networknames_id'
                      AND `itemtype` = 'NetworkName'";

      $ipaddress      = new IPAddress();
      $ipnetworks_ids = array();
      foreach ($DB->request($query) as $address) {
         $ipaddress->setAddressFromArray($address, 'version', 'name', 'binary');
         $ipnetworks_ids = array_merge($ipnetworks_ids,
                                     IPNetwork::searchNetworksContainingIP($ipaddress));
      }

      $link = array('networknames_id' => "$networknames_id");
      foreach ($ipnetworks_ids as $ipnetworks_id) {
         $link['ipnetworks_id'] = $ipnetworks_id;
         $linkObject->add($link);
      }
   }


   /**
    * \brief Recreate the links between NetworkName and IPNetwork
    * Among others, the migration don't create it. So, an update is necessary
    * WARNING : this method does not work properly for the moment ...
    *
    * First, reset the link table then reinit the links for each network
    *
    * @return nothing
   **/
   static function recreateAllConnexities() {
      global $DB;

      $query = "DELETE
                FROM `glpi_networknames_ipnetworks`";
      $DB->query($query);

      $network    = new IPNetwork();
      $query      = "SELECT `id`
                     FROM `glpi_ipnetworks`";
      foreach ($DB->request($query) as $ipnetwork_row) {
         $ipnetworks_id = $ipnetwork_row['id'];
         if ($network->getFromDB($ipnetwork_row['id'])) {
            $query = "SELECT `items_id`
                      FROM `glpi_ipaddresses`
                      WHERE `itemtype` = 'NetworkName'
                            AND ".$network->getWHEREForMatchingElement('glpi_ipaddresses', 'binary',
                                                                       'version')."
                      GROUP BY `items_id`";
            foreach ($DB->request($query) as $link) {
               $query = "INSERT INTO `glpi_networknames_ipnetworks`
                                ( `ipnetworks_id`, `networknames_id`)
                         VALUES ('$ipnetworks_id', '".$link['items_id']."')";
               $DB->query($query);
               unset($query);
            }
            unset($query);
         }
      }
   }
}
?>
