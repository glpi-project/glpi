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

/**
 * @since 0.84
 */

include ('../inc/includes.php');

if (isset($_POST['itemtype'])) {

   $itemtype    = $_POST['itemtype'];
   $source_item = new $itemtype();
   if ($source_item->can($_POST['id'], UPDATE)) {

      $devices = Item_Devices::getDeviceTypes();
      $actions = array_merge($CFG_GLPI['inventory_lockable_objects'], array_values($devices));

      if (isset($_POST["unlock"])) {
         foreach ($actions as $type) {
            if (isset($_POST[$type]) && count($_POST[$type])) {
               $item = new $type();
               foreach (array_keys($_POST[$type]) as $key) {
                  if (!$item->can($key, UPDATE)) {
                     Session::addMessageAfterRedirect(
                        sprintf(
                           __('You do not have rights to restore %s item.'),
                           $type
                        ),
                        true,
                        ERROR
                     );
                     continue;
                  }

                  //Force unlock
                  $item->restore(['id' => $key]);
               }
            }
         }

         //Execute hook to unlock fields managed by a plugin, if needed
         Plugin::doHookFunction('unlock_fields', $_POST);

      } else if (isset($_POST["purge"])) {
         foreach ($actions as $type) {
            if (isset($_POST[$type]) && count($_POST[$type])) {
               $item = new $type();
               foreach (array_keys($_POST[$type]) as $key) {
                  if (!$item->can($key, PURGE)) {
                     Session::addMessageAfterRedirect(
                        sprintf(
                           __('You do not have rights to delete %s item.'),
                           $type
                        ),
                        true,
                        ERROR
                     );
                     continue;
                  }

                  //Force unlock
                  $item->delete(['id' => $key], 1);
               }
            }
         }
      }
   }
}

Html::back();
