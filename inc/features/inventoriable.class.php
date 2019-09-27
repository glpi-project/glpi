<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Features;

use Agent;
use Computer_Item;
use Html;
use RefusedEquipment;
use Toolbox;

trait Inventoriable {

   public function pre_purgeInventory() {
      $file_name = $this->getInventoryFileName();
      if ($file_name === null) {
         //file does not exists
         return true;
      }

      return unlink(GLPI_INVENTORY_DIR . '/' . $file_name);
   }


   public function getInventoryFileName() {
      if (!$this->isDynamic()) {
         return;
      }

      $download_file = Toolbox::slugify(static::getType()) . '/' . $this->fields['id'];
      $inventory_file = GLPI_INVENTORY_DIR . '/' . $download_file;
      if (file_exists($inventory_file . '.json')) {
         $download_file .= '.json';
      } else if (file_exists($inventory_file . '.xml')) {
         $download_file .= '.xml';
      } else {
         Toolbox::logWarning('Inventory file missing: ' . $inventory_file);
         $download_file = null;
      }

      return $download_file;
   }

   /**
    * Display information on inventory
    *
    * @return void
    */
   protected function showInventoryInfo() {
      global $CFG_GLPI;

      if (!$this->isDynamic()) {
         return;
      }

      echo '<tr>';
      echo '<th colspan="4">'.__('Inventory information');

      $download_file = $this->getInventoryFileName();
      if ($download_file !== null) {
          $href = sprintf(
             "%s/front/document.send.php?file=_inventory/%s",
             $CFG_GLPI["root_doc"],
             $download_file
          );
          $title = sprintf(
             //TRANS: parameter is the name of the asset
             __('Download "%1$s" inventory file'),
             $this->getName()
          );

         echo sprintf(
            "<a href='%s' style='float: right;' target='_blank'><i class='fas fa-download' title='%s'></i></a>",
            $href,
            $title
         );

         if (static::class == RefusedEquipment::class) {
            echo sprintf(
               "<a href='%s' target='_blank' style='float: right;margin-right: .5em;'><i class='fas fa-redo' title='%s'></i></a>",
               $CFG_GLPI['root_doc'] . '/front/inventory.php?refused=' . $this->fields['id'],
               __('Try a reimport from stored inventory file')
            );
         }

      } else {
         echo sprintf(
            "<span style='float: right;'><i class='fas fa-ban'></i> <span class='sr-only'>%s</span></span>",
            __('Inventory file missing')
         );
      }

      echo '</th>';
      echo '</tr>';

      $agent = new Agent();
      $has_agent = $agent->getFromDBByCrit([
         'itemtype' => $this->getType(),
         'items_id' => $this->fields['id']
      ]);

      //if no agent has been found, check if there is a linked item, and find its agent
      if (!$has_agent && $this->getType() == 'Computer') {
         $citem = new Computer_Item;
         $has_relation = $citem->getFromDBByCrit([
            'itemtype' => $this->getType(),
            'items_id' => $this->fields['id']
         ]);
         if ($has_relation) {
            $has_agent = $agent->getFromDBByCrit([
               'itemtype' => \Computer::getType(),
               'items_id' => $citem->fields['computers_id']
            ]);
         }
      }

      if (!$has_agent) {
         echo '<tr class="tab_bg_1">';
         echo '<td colspan="4">'.__('No agent has been linked.').'</td>';
         echo "</tr>";
         return;
      }

      echo '<tr class="tab_bg_1">';
      echo '<td>'.Agent::getTypeName(1).'</td>';
      echo '<td>'.$agent->getLink().'</td>';

      echo '<td>'.__('Useragent').'</td>';
      echo '<td>'.$agent->fields['useragent'].'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Inventory tag').'</td>';
      echo '<td>'.$agent->fields['tag'].'</td>';
      echo '<td>' . __('Last inventory') . '</td>';
      echo '<td>' . Html::convDateTime($agent->fields['last_contact']) . '</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'.__('Agent status');
      echo "<i id='update-status' class='fas fa-sync' style='float: right;cursor: pointer;' title='".__s('Ask agent about its current status')."'></i>";
      echo '</td>';
      echo '<td id="agent_status">' . __('Unknown') . '</td>';
      echo '<td>' .  __('Request inventory');
      echo "<i id='update-inventory' class='fas fa-sync' style='float: right;cursor: pointer;' title='".__s('Request agent to proceed an new inventory')."'></i>";
      echo '</td>';
      echo '<td id="inventory_status">' . __('None') . '</td>';
      echo '</tr>';

      $status = Agent::ACTION_STATUS;
      $inventory = Agent::ACTION_INVENTORY;
      $js = <<<JAVASCRIPT
         $(function() {
            $('#update-status').on('click', function() {
               $.ajax({
                  type: 'GET',
                  url: '{$CFG_GLPI['root_doc']}/ajax/agent.php',
                  timeout: 3000, //3 seconds timeout
                  dataType: 'json',
                  data: {'action': '{$status}', 'id': '{$agent->fields['id']}'},
                  success: function(json) {
                     $('#agent_status').html(json.answer);
                  }
               });
            });

            $('#update-inventory').on('click', function() {
               $.ajax({
                  type: 'GET',
                  url: '{$CFG_GLPI['root_doc']}/ajax/agent.php',
                  timeout: 3000, //3 seconds timeout
                  dataType: 'json',
                  data: {'action': '{$inventory}', 'id': '{$agent->fields['id']}'},
                  success: function(json) {
                     $('#inventory_status').html(json.answer);
                  }
               });
            });

         });
JAVASCRIPT;
      echo Html::scriptBlock($js);
   }
}
