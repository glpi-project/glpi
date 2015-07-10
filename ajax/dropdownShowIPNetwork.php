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

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$network = new IPNetwork();

if ($network->can($_POST['ipnetworks_id'], READ)) {
   echo "<br>\n";
   echo "<a href='".$network->getLinkURL()."'>".$network->fields['completename']."</a><br>\n";

   $address = $network->getAddress()->getTextual();
   $netmask = $network->getNetmask()->getTextual();
   $gateway = $network->getGateway()->getTextual();

   $start   = new IPAddress();
   $end     = new IPAddress();

   $network->computeNetworkRange($start, $end);

   //TRANS: %1$s is address, %2$s is netmask
   printf(__('IP network: %1$s/%2$s')."<br>\n", $address, $netmask);
   printf(__('First/last addresses: %1$s/%2$s'), $start->getTextual(), $end->getTextual());
   if (!empty($gateway)) {
      echo "<br>\n";
      printf(__('Gateway: %s')."\n", $gateway);
   }
}
?>