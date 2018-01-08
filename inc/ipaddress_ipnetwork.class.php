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
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Class IPAddress_IPNetwork : Connection between IPAddress and IPNetwork
 *
 * @since 0.84
**/
class IPAddress_IPNetwork extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1 = 'IPAddress';
   static public $items_id_1 = 'ipaddresses_id';

   static public $itemtype_2 = 'IPNetwork';
   static public $items_id_2 = 'ipnetworks_id';


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
         $linkObject->delete(['id' => $link['id']]);
      }

      // Then, look each IP address contained inside current Network
      $query = "SELECT '".$ipnetworks_id."' AS ipnetworks_id,
                       `id` AS ipaddresses_id
                FROM `glpi_ipaddresses`
                WHERE ".$network->getWHEREForMatchingElement('glpi_ipaddresses', 'binary',
                                                             'version')."
                GROUP BY `id`";
      foreach ($DB->request($query) as $link) {
         $linkObject->add($link);
      }
   }


   /**
    * @param $ipaddress IPAddress object
   **/
   static function addIPAddress(IPAddress $ipaddress) {

      $linkObject = new self();
      $input      = ['ipaddresses_id' => $ipaddress->getID()];

      $entity         = $ipaddress->getEntityID();
      $ipnetworks_ids = IPNetwork::searchNetworksContainingIP($ipaddress, $entity);
      if ($ipnetworks_ids !== false) {
         // Beware that invalid IPaddresses don't have any valid address !
         $entity = $ipaddress->getEntityID();
         foreach (IPNetwork::searchNetworksContainingIP($ipaddress, $entity) as $ipnetworks_id) {
            $input['ipnetworks_id'] = $ipnetworks_id;
            $linkObject->add($input);
         }
      }
   }

}
