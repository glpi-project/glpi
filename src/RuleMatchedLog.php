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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Inventory\Request;

use function Safe\json_decode;

/**
 * Logs rules used during inventory
 */
class RuleMatchedLog extends CommonDBTM
{
    /**
     * The right name for this class
     *
     * @var string
     */
    public static $rightname = 'inventory';

    public static function getTypeName($nb = 0)
    {
        return __('Matched rules');
    }

    public static function getIcon()
    {
        return Rule::getIcon();
    }

    /**
     * Count number of elements
     *
     * @param CommonDBTM $item
     *
     * @return integer
     */
    public static function countForItem(CommonDBTM $item)
    {
        return countElementsInTable(
            self::getTable(),
            [
                'itemtype' => $item->getType(),
                'items_id' => $item->getField('id'),
            ]
        );
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $array_ret = [];

        if ($item::class === Agent::class) {
            $array_ret[0] = self::createTabEntry(__('Import information'), 0, $item::class);
        } elseif ($item instanceof CommonDBTM) {
            $continue = true;

            switch ($item::class) {
                case Agent::class:
                    $array_ret[0] = self::createTabEntry(__('Import information'), 0, $item::class);
                    break;

                case Unmanaged::class:
                    $cnt = self::countForItem($item);
                    $array_ret[1] = self::createTabEntry(__('Import information'), $cnt, $item::class);
                    break;

                case Computer::class:
                case Monitor::class:
                case NetworkEquipment::class:
                case Peripheral::class:
                case Phone::class:
                case Printer::class:
                    $continue = $item->isDynamic();
                    break;
                default:
                    break;
            }
            if (!$continue) {
                return [];
            } elseif (empty($array_ret)) {
                $cnt = self::countForItem($item);
                $array_ret[1] = self::createTabEntry(__('Import information'), $cnt, $item::class);
            }
        } else {
            throw new LogicException("Item must be CommonDBTM");
        }
        return $array_ret;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof CommonDBTM && ($tabnum == '0' || $tabnum == '1') && $item->getID() > 0) {
            self::showForItem($item);
            return true;
        }
        return false;
    }

    /**
     * Clean old data
     *
     * @global object $DB
     * @param integer $items_id
     * @param string $itemtype
     */
    public function cleanOlddata($items_id, $itemtype)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'items_id'   => $items_id,
                'itemtype'  => $itemtype,
            ],
            'ORDER'  => 'date DESC',
            'START'  => 30,
            'LIMIT'  => '50000',
        ]);
        foreach ($iterator as $data) {
            $this->delete(['id' => $data['id']]);
        }
    }

    /**
     * Display logs for item.
     *
     * @param CommonDBTM $item
     *
     * @return void
     */
    private static function showForItem(CommonDBTM $item): void
    {
        global $DB, $CFG_GLPI;

        $criteria = [
            'FROM' => self::getTable(),
        ];
        if ($item instanceof Agent) {
            $criteria['WHERE'] = [
                'agents_id' => $item->getID(),
                'itemtype'  => $CFG_GLPI['inventory_types'],
            ];
        } else {
            $criteria['WHERE'] = [
                'itemtype' => $item::class,
                'items_id' => $item->getID(),
            ];
        }

        $start = (int) ($_GET['start'] ?? 0);
        $count = $DB->request($criteria + ['COUNT' => 'cpt'])->current()['cpt'];
        $limit = (int) $_SESSION['glpilist_limit'];

        $iterator = $DB->request(
            $criteria + [
                'ORDER'  => 'date DESC',
                'START'  => $start,
                'LIMIT'  => $limit,
            ]
        );

        $rows = [];
        foreach ($iterator as $data) {
            $rows[] = [
                'date'       => $data['date'],
                'rules_id'   => $data['rules_id'],
                'agents_id'  => $data['agents_id'],
                'itemtype'   => $data['itemtype'],
                'items_id'   => $data['items_id'],
                'modulename' => Request::getModuleName($data['method']),
                'input'      => json_decode($data['input'] ?? '[]', true),
            ];
        }

        TemplateRenderer::getInstance()->display('components/form/rulematchedlogs.html.twig', [
            'item'  => $item,
            'start' => $start,
            'count' => $count,
            'rows'  => $rows,
        ]);
    }
}
