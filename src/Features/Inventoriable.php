<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Features;

use Agent;
use CommonDBTM;
use Computer;
use Computer_Item;
use DatabaseInstance;
use Glpi\Inventory\Conf;
use Glpi\Plugin\Hooks;
use Html;
use Plugin;
use RefusedEquipment;

trait Inventoriable
{
    /** @var CommonDBTM|null */
    protected ?CommonDBTM $agent = null;

    public function pre_purgeInventory()
    {
        $file_name = $this->getInventoryFileName();
        if ($file_name === null) {
           //file does not exists
            return true;
        }

        return unlink($file_name);
    }


    /**
     * Get inventory file name.
     *
     * @param bool $prepend_dir_path Indicated wether the GLPI_INVENTORY_DIR have to be prepend to returned value.
     *
     * @return void|string
     */
    public function getInventoryFileName(bool $prepend_dir_path = true): ?string
    {

        if ($this->isField('autoupdatesystems_id')) {
            $source = new \AutoUpdateSystem();
            $source->getFromDBByCrit(['name' => 'GLPI Native Inventory']);

            if (
                !$this->isDynamic()
                || !isset($source->fields['id'])
                || $this->fields['autoupdatesystems_id'] != $source->fields['id']
            ) {
                return null;
            }
        }

        $inventory_dir_path = GLPI_INVENTORY_DIR . '/';
        $itemtype = $this->agent->fields['itemtype'] ?? $this->getType();
        $items_id = $this->agent->fields['items_id'] ?? $this->fields['id'];

        $conf = new Conf();
       //most files will be XML for now
        $filename = $conf->buildInventoryFileName($itemtype, $items_id, 'xml');
        if (!file_exists($inventory_dir_path . $filename)) {
            $filename = $conf->buildInventoryFileName($itemtype, $items_id, 'json');
            if (!file_exists($inventory_dir_path . $filename)) {
                return null;
            }
        }

        return ($prepend_dir_path ? $inventory_dir_path : '') . $filename;
    }

    /**
     * Display information on inventory
     *
     * @return void
     */
    protected function showInventoryInfo()
    {
        global $CFG_GLPI, $DB;

        if (!$this->isDynamic()) {
            return;
        }

        echo '<tr>';
        echo '<th colspan="4">' . __('Inventory information');

        $agent = $this->getInventoryAgent();

        $download_file = $this->getInventoryFileName(false);
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

        if ($agent === null) {
            echo '<tr class="tab_bg_1">';
            echo '<td colspan="4">' . __('No agent has been linked.') . '</td>';
            echo "</tr>";
        } else {
            $this->displayAgentInformation();
        }

       // Display auto inventory information
        if (
            !empty($this->fields['id'])
            && $this->maybeDynamic() && $this->fields["is_dynamic"]
        ) {
            echo "<tr class='tab_bg_1'><td colspan='4'>";
            Plugin::doHook(Hooks::AUTOINVENTORY_INFORMATION, $this);
            echo "</td></tr>";
        }
    }

    /**
     * Display agent information
     */
    protected function displayAgentInformation()
    {
        global $CFG_GLPI;

        echo '<tr class="tab_bg_1">';
        echo '<td>' . Agent::getTypeName(1) . '</td>';
        echo '<td>' . $this->agent->getLink() . '</td>';

        echo '<td>' . __('Useragent') . '</td>';
        echo '<td>' . $this->agent->fields['useragent'] . '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Inventory tag') . '</td>';
        echo '<td>' . $this->agent->fields['tag'] . '</td>';
        echo '<td>' . __('Last inventory') . '</td>';
        echo '<td>' . Html::convDateTime($this->agent->fields['last_contact']) . '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __('Agent status');
        echo "<i id='update-status' class='fas fa-sync' style='float: right;cursor: pointer;' title='" . __s('Ask agent about its current status') . "'></i>";
        echo '</td>';
        echo '<td id="agent_status">' . __('Unknown') . '</td>';
        echo '<td>' .  __('Request inventory');
        echo "<i id='update-inventory' class='fas fa-sync' style='float: right;cursor: pointer;' title='" . __s('Request agent to proceed an new inventory') . "'></i>";
        echo '</td>';
        echo '<td id="inventory_status">' . __('None') . '</td>';
        echo '</tr>';

        $status = Agent::ACTION_STATUS;
        $inventory = Agent::ACTION_INVENTORY;
        $js = <<<JAVASCRIPT
         $(function() {
            $('#update-status').on('click', function() {
               $.post({
                  url: '{$CFG_GLPI['root_doc']}/ajax/agent.php',
                  timeout: 3000, //3 seconds timeout
                  data: {'action': '{$status}', 'id': '{$this->agent->fields['id']}'},
                  success: function(json) {
                     $('#agent_status').html(json.answer);
                  }
               });
            });

            $('#update-inventory').on('click', function() {
               $.post({
                  url: '{$CFG_GLPI['root_doc']}/ajax/agent.php',
                  timeout: 3000, //3 seconds timeout
                  data: {'action': '{$inventory}', 'id': '{$this->agent->fields['id']}'},
                  success: function(json) {
                     $('#inventory_status').html(json.answer);
                  }
               });
            });

         });
JAVASCRIPT;
        echo Html::scriptBlock($js);
    }

    public function getInventoryAgent(): ?Agent
    {
        global $DB;

        $agent = new Agent();
        $iterator = $DB->request([
            'SELECT'    => ['id'],
            'FROM'      => Agent::getTable(),
            'WHERE'     => [
                'itemtype' => $this->getType(),
                'items_id' => $this->fields['id']
            ],
            'ORDERBY'   => ['last_contact DESC'],
            'LIMIT'     => 1
        ]);

        $has_agent = false;
        if (count($iterator)) {
            $has_agent = true;
            $agent->getFromDB($iterator->current()['id']);
        }

       //if no agent has been found, check if there is a linked item for databases
        if (!$has_agent && $this instanceof DatabaseInstance) {
            if (!empty($this->fields['itemtype'] && !empty($this->fields['items_id']))) {
                $has_agent = $agent->getFromDBByCrit([
                    'itemtype' => $this->fields['itemtype'],
                    'items_id' => $this->fields['items_id']
                ]);
            }
        }

       //if no agent has been found, check if there is a linked item, and find its agent
        if (!$has_agent && $this instanceof Computer) {
            $citem = new Computer_Item();
            $has_relation = $citem->getFromDBByCrit([
                'itemtype' => $this->getType(),
                'items_id' => $this->fields['id']
            ]);
            if ($has_relation) {
                $has_agent = $agent->getFromDBByCrit([
                    'itemtype' => Computer::getType(),
                    'items_id' => $citem->fields['computers_id']
                ]);
            }
        }

        if ($has_agent) {
            $this->agent = $agent;
        }
        return $this->agent;
    }
}
