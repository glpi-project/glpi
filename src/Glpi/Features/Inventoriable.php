<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Computer;
use DatabaseInstance;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Inventory\Conf;
use Glpi\Plugin\Hooks;
use Html;
use Plugin;
use RefusedEquipment;
use Safe\Exceptions\FilesystemException;

use function Safe\unlink;

trait Inventoriable
{
    protected ?Agent $agent = null;

    public function pre_purgeInventory()
    {
        $file_name = $this->getInventoryFileName();
        if ($file_name === null) {
            //file does not exist
            return true;
        }

        try {
            unlink($file_name);
            return true;
        } catch (FilesystemException $e) {
            return false;
        }
    }


    /**
     * Get inventory file name.
     *
     * @param bool $prepend_dir_path Indicated whether the GLPI_INVENTORY_DIR have to be prepended to returned value.
     *
     * @return string|null
     */
    public function getInventoryFileName(bool $prepend_dir_path = true): ?string
    {
        if (!$this->isDynamic()) {
            return null;
        }

        $inventory_dir_path = GLPI_INVENTORY_DIR . '/';
        $itemtype = $this->agent->fields['itemtype'] ?? $this->getType();
        $items_id = $this->agent->fields['items_id'] ?? $this->fields['id'];

        $conf = new Conf();
        //Check for JSON file, and the XML if JSON does not exist
        $filename = $conf->buildInventoryFileName($itemtype, $items_id, 'json');
        if (!file_exists($inventory_dir_path . $filename)) {
            $filename = $conf->buildInventoryFileName($itemtype, $items_id, 'xml');
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

        //TODO: GLPI 11.1
        //Toolbox::deprecated();

        if (!$this->isDynamic()) {
            return;
        }

        echo '<tr>';
        echo '<th colspan="4">' . __s('Inventory information');

        $agent = $this->getInventoryAgent();

        $download_file = $this->getInventoryFileName(false);
        if ($download_file !== null) {
            $href = sprintf(
                "%s/front/document.send.php?file=_inventory/%s",
                $CFG_GLPI["root_doc"],
                $download_file
            );

            echo sprintf(
                "<a href='%s' style='float: right;' target='_blank'><i class='ti ti-download' title='%s'></i></a>",
                \htmlescape($href),
                sprintf(
                    //TRANS: parameter is the name of the asset
                    __s('Download "%1$s" inventory file'),
                    htmlescape($this->getName())
                )
            );

            if (static::class === RefusedEquipment::class) { //@phpstan-ignore-line - phpstan bug with traits
                $url = \htmlescape($CFG_GLPI['root_doc'] . '/Inventory/RefusedEquipment');
                $title = __s('Try a reimport from stored inventory file');
                echo <<<HTML
                        <button type="submit" class="btn btn-sm btn-ghost-secondary" name="redo_inventory"
                                title="{$title}"
                                data-bs-toggle="tooltip" data-bs-placement="top"
                                style="float: right;margin-right: .5em;"
                                formaction="{$url}">
                           <i class="ti ti-reload"></i>
                        </button>
HTML;
            }
        } else {
            echo sprintf(
                "<span style='float: right;'><i class='ti ti-ban'></i> <span class='sr-only'>%s</span></span>",
                __s('Inventory file missing')
            );
        }

        echo '</th>';
        echo '</tr>';

        if ($agent === null) {
            echo '<tr class="tab_bg_1">';
            echo '<td colspan="4">' . __s('Agent information is not available.') . '</td>';
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

        //TODO: GLPI 11.1
        //Toolbox::deprecated();

        echo '<tr class="tab_bg_1">';
        echo '<td>' . htmlescape(Agent::getTypeName(1)) . '</td>';
        echo '<td>' . $this->agent->getLink() . '</td>';

        echo '<td>' . __s('Useragent') . '</td>';
        echo '<td>' . htmlescape($this->agent->fields['useragent']) . '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __s('Inventory tag') . '</td>';
        echo '<td>' . htmlescape($this->agent->fields['tag']) . '</td>';
        echo '<td>' . __s('Last inventory') . '</td>';
        echo '<td>' . htmlescape(Html::convDateTime($this->agent->fields['last_contact'])) . '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td>' . __s('Agent status');
        echo "<i id='update-status' class='ti ti-refresh' style='float: right;cursor: pointer;' title='" . __s('Ask agent about its current status') . "'></i>";
        echo '</td>';
        echo '<td id="agent_status">' . __s('Unknown') . '</td>';
        echo '<td>' . __s('Request inventory');
        echo "<i id='update-inventory' class='ti ti-refresh' style='float: right;cursor: pointer;' title='" . __s('Request agent to proceed an new inventory') . "'></i>";
        echo '</td>';
        echo '<td id="inventory_status">' . __s('None') . '</td>';
        echo '</tr>';

        $status = Agent::ACTION_STATUS;
        $inventory = Agent::ACTION_INVENTORY;
        $agents_id = (int) $this->agent->fields['id'];
        $root_doc = \jsescape($CFG_GLPI['root_doc']);
        $js = <<<JAVASCRIPT
            $(function() {
                $('#update-status').on('click', function() {
                    $.post({
                        url: '{$root_doc}/ajax/agent.php',
                        timeout: 3000, //3 seconds timeout
                        data: {'action': '{$status}', 'id': '{$agents_id}'},
                        success: function(json) {
                            $('#agent_status').html(json.answer);
                        }
                    });
                });

                $('#update-inventory').on('click', function() {
                    $.post({
                        url: '{$root_doc}/ajax/agent.php',
                        timeout: 3000, //3 seconds timeout
                        data: {'action': '{$inventory}', 'id': '{$agents_id}'},
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

        $agent = $this->getMostRecentAgent([
            'itemtype' => $this->getType(),
            'items_id' => $this->getID(),
        ]);

        if (
            $agent === null
            && $this instanceof DatabaseInstance
            && !empty($this->fields['itemtype'])
            && !empty($this->fields['items_id'])
        ) {
            // if no agent has been found, check if there is an agent linked to database host asset
            $agent = $this->getMostRecentAgent([
                'itemtype' => $this->fields['itemtype'],
                'items_id' => $this->fields['items_id'],
            ]);
        } elseif (
            $agent === null
            && $this instanceof Computer
        ) {
            // if no agent has been found, check if there is are linked items, and find most recent agent
            $relations_iterator = $DB->request(
                [
                    'SELECT' => [
                        'itemtype_peripheral',
                        'items_id_peripheral',
                    ],
                    'FROM'   => Asset_PeripheralAsset::getTable(),
                    'WHERE'  => [
                        'itemtype_asset' => Computer::class,
                        'items_id_asset' => $this->getID(),
                    ],
                ]
            );
            if (count($relations_iterator) > 0) {
                $conditions = ['OR' => []];
                $itemtype_ids = [];
                foreach ($relations_iterator as $relation_data) {
                    if (!isset($itemtype_ids[$relation_data['itemtype_peripheral']])) {
                        $itemtype_ids[$relation_data['itemtype_peripheral']] = [];
                    }
                    $itemtype_ids[$relation_data['itemtype_peripheral']][] = $relation_data['items_id_peripheral'];
                }
                foreach ($itemtype_ids as $itemtype => $ids) {
                    /** @phpstan-ignore-next-line  */
                    if (count($ids) > 0) {
                        $conditions['OR'][] = [
                            'itemtype' => $itemtype,
                            'items_id' => $ids,
                        ];
                    }
                }
                $agent = $this->getMostRecentAgent($conditions);
            }
        }

        $this->agent = $agent;

        return $this->agent;
    }

    /**
     * Get most recent agent corresponding to given conditions.
     *
     * @param array $conditions
     *
     * @return Agent|null
     */
    private function getMostRecentAgent(array $conditions): ?Agent
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => ['id'],
            'FROM'      => Agent::getTable(),
            'WHERE'     => $conditions,
            'ORDERBY'   => ['last_contact DESC'],
            'LIMIT'     => 1,
        ]);
        if (count($iterator) === 0) {
            return null;
        }

        $agent = new Agent();
        $agent->getFromDB($iterator->current()['id']);
        return $agent;
    }
}
