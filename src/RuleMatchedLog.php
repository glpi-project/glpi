<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Inventory\Request;

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


    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     *
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return __('Matched rules');
    }


    /**
     * Count number of elements
     *
     * @param object $item
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


    /**
     * Get the tab name used for item
     *
     * @param object $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $array_ret = [];

        if ($item->getType() == 'Agent') {
            $array_ret[0] = self::createTabEntry(__('Import information'));
        } else {
            $continue = true;

            switch ($item->getType()) {
                case 'Agent':
                    $array_ret[0] = self::createTabEntry(__('Import information'));
                    break;

                case 'Unmanaged':
                    $cnt = self::countForItem($item);
                    $array_ret[1] = self::createTabEntry(__('Import information'), $cnt);
                    break;

                case 'Computer':
                case 'Monitor':
                case 'NetworkEquipment':
                case 'Peripheral':
                case 'Phone':
                case 'Printer':
                    $continue = $item->isDynamic();
                    break;
                default:
                    break;
            }
            if (!$continue) {
                return [];
            } else if (empty($array_ret)) {
                $cnt = self::countForItem($item);
                $array_ret[1] = self::createTabEntry(__('Import information'), $cnt);
            }
        }
        return $array_ret;
    }


    /**
     * Display the content of the tab
     *
     * @param object $item
     * @param integer $tabnum number of the tab to display
     * @param integer $withtemplate 1 if is a template form
     * @return boolean
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        $rulematched = new self();
        if ($tabnum == '0') {
            if ($item->getID() > 0) {
                $rulematched->showFormAgent($item->getID());
                return true;
            }
        } else if ($tabnum == '1') {
            if ($item->getID() > 0) {
                $rulematched->showItemForm($item->getID(), $item->getType());
                return true;
            }
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
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'items_id'   => $items_id,
                'itemtype'  => $itemtype
            ],
            'ORDER'  => 'date DESC',
            'START'  => 30,
            'LIMIT'  => '50000'
        ]);
        foreach ($iterator as $data) {
            $this->delete(['id' => $data['id']]);
        }
    }


    /**
     * Display form
     *
     * @param integer $items_id
     * @param string $itemtype
     * @return true
     */
    public function showItemForm($items_id, $itemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $rule    = new RuleImportAsset();
        $agent = new Agent();

        if (isset($_GET["start"])) {
            $start = $_GET["start"];
        } else {
            $start = 0;
        }

        $params = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => intval($items_id)
            ],
            'COUNT'  => 'cpt'
        ];
        $iterator = $DB->request($params);
        $number   = $iterator->current()['cpt'];

       // Display the pager
        Html::printAjaxPager(self::getTypeName(2), $start, $number);

        echo "<table class='tab_cadre_fixe' cellpadding='1'>";

        echo "<tr>";
        echo "<th colspan='4'>";
        echo __('Rule import logs');

        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo _n('Date', 'Dates', 1);

        echo "</th>";
        echo "<th>";
        echo __('Rule name');

        echo "</th>";
        echo "<th>";
        echo Agent::getTypeName(1);

        echo "</th>";
        echo "<th>";
        echo __('Module');

        echo "</th>";
        echo "</tr>";

        $params = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => intval($items_id)
            ],
            'ORDER'  => 'date DESC',
            'START'  => (int)$start,
            'LIMIT'  => (int)$_SESSION['glpilist_limit']
        ];
        foreach ($DB->request($params) as $data) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo Html::convDateTime($data['date']);
            echo "</td>";
            echo "<td>";
            if ($rule->getFromDB($data['rules_id'])) {
                echo $rule->getLink(1);
            }
            echo "</td>";
            echo "<td>";
            if ($agent->getFromDB($data['agents_id'])) {
                echo $agent->getLink(1);
            }
            echo "</td>";
            echo "<td>";
            echo Request::getModuleName($data['method']);
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";

       // Display the pager
        Html::printAjaxPager(self::getTypeName(2), $start, $number);

        return true;
    }


    /**
     * Display form for agent
     *
     * @param integer $agents_id
     */
    public function showFormAgent($agents_id)
    {

        $rule = new RuleImportAsset();

        echo "<table class='tab_cadre_fixe' cellpadding='1'>";

        echo "<tr>";
        echo "<th colspan='5'>";
        echo __('Rule import logs');

        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo _n('Date', 'Dates', 1);

        echo "</th>";
        echo "<th>";
        echo __('Rule name');

        echo "</th>";
        echo "<th>";
        echo __('Item type');

        echo "</th>";
        echo "<th>";
        echo _n('Item', 'Items', 1);

        echo "</th>";
        echo "<th>";
        echo __('Module');

        echo "</th>";
        echo "</tr>";

        $allData = $this->find(['agents_id' => $agents_id], ['date DESC']);
        foreach ($allData as $data) {
            echo "<tr class='tab_bg_1'>";
            echo "<td align='center'>";
            echo Html::convDateTime($data['date']);
            echo "</td>";
            echo "<td align='center'>";
            if ($rule->getFromDB($data['rules_id'])) {
                echo $rule->getLink(1);
            }
            echo "</td>";
            echo "<td align='center'>";
            $itemtype = $data['itemtype'];
            $item = new $itemtype();
            echo $item->getTypeName();
            echo "</td>";
            echo "<td align='center'>";
            if ($item->getFromDB($data['items_id'])) {
                echo $item->getLink(1);
            }
            echo "</td>";
            echo "<td>";
            echo Request::getModuleName($data['method']);
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
