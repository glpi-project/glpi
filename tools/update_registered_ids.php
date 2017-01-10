<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
 * @since version 0.85
* @brief Purge history with some criteria
*/

include ('../inc/includes.php');

$registeredid = new RegisteredID();
$manufacturer = new Manufacturer();
foreach (array('PCI' => 'http://pciids.sourceforge.net/v2.2/pci.ids',
               'USB' => 'http://www.linux-usb.org/usb.ids') as $type => $URL) {
   echo "Processing : $type\n";
   foreach (file($URL) as $line) {
      if ($line[0] == '#') {
         continue;
      }
      $line = rtrim($line);
      if (empty($line)) {
         continue;
      }
      if ($line[0] != '\t') {
         $id   = strtolower(substr($line, 0, 4));
         $name = addslashes(trim(substr($line, 4)));
         if ($registeredid->getFromDBByQuery("WHERE `itemtype` = 'Manufacturer'
                                                    AND `name` = '$id'
                                                    AND `device_type` = '$type'")) {
            $manufacturer->getFromDB($registeredid->fields['items_id']);
         } else {
            if (!$manufacturer->getFromDBByQuery("WHERE `name` = '$name'")) {
               $input = array('name' => $name);
               $manufacturer->add($input);
            }
            $input = array('itemtype'    => $manufacturer->getType(),
                           'items_id'    => $manufacturer->getID(),
                           'device_type' => $type,
                           'name'        => $id);
            $registeredid->add($input);
         }
         continue;
      }
      // if (($line[0] == "\t") && ($line[1] != '\t'))  {
      //    $line = trim($line);
      //    $id   = strtolower(substr($line, 0, 4));
      //    $name = addslashes(trim(substr($line, 4)));
      //    continue;
      // }
   }
}
?>