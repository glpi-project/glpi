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
 * @brief
 * @since version 0.84
*/

include ('../inc/includes.php');

if (isset($_POST['itemtype']) && isset($_POST["unlock"])) {
   $itemtype    = $_POST['itemtype'];
   $source_item = new $itemtype();
   if ($source_item->canCreate()) {
      $source_item->check($_POST['id'], UPDATE);

      $actions = array("Computer_Item", "Computer_SoftwareLicense", "Computer_SoftwareVersion",
                       "ComputerDisk", "ComputerVirtualMachine", "NetworkPort", "NetworkName",
                       "IPAddress");
      $devices = Item_Devices::getDeviceTypes();
      $actions = array_merge($actions, array_values($devices));
      foreach ($actions as $type) {
         if (isset($_POST[$type]) && count($_POST[$type])) {
            $item = new $type();
            foreach ($_POST[$type] as $key => $val) {
               //Force unlock
               $item->restore(array('id' => $key));
            }
         }
      }
   }
}
//Execute hook to unlock fields managed by a plugin, if needed
Plugin::doHookFunction('unlock_fields', $_POST);
Html::back();
?>