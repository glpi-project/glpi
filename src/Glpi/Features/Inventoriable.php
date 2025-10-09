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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Inventory\Conf;
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
        if (!$this->isDynamic()) {
            return;
        }

        echo '<tr><td colspan="4">';
        echo TemplateRenderer::getInstance()->render('components/form/inventory_info.html.twig', ['item' => $this]);
        echo "</td></tr>";
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
