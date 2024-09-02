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

use Glpi\Socket;

/// Class CableStrand
class CableStrand extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n('Cable strand', 'Cable strands', $nb);
    }


    public static function getFieldLabel()
    {
        return _n('Cable strand', 'Cable strands', 1);
    }

    public function defineTabs($options = [])
    {

        $ong = parent::defineTabs($options);
        $this->addStandardTab(__CLASS__, $ong, $options);

        return $ong;
    }

    public function cleanDBonPurge()
    {
        Rule::cleanForItemAction($this);
        Rule::cleanForItemCriteria($this, '_cablestrands_id%');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case __CLASS__:
                    /** @var CableStrand $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            Cable::getTable(),
                            ['cablestrands_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            /** @var CableStrand $item */
            switch ($tabnum) {
                case 1:
                    $item->showItems();
                    break;
            }
        }
        return true;
    }

    /**
     * Print the HTML array of items related to cable strand.
     *
     * @return void
     */
    public function showItems()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $cablestrands_id = $this->fields['id'];

        if (!$this->can($cablestrands_id, READ)) {
            return false;
        }

        $cable = new Cable();

        $criteria = [
            'SELECT' => [
                'id'
            ],
            'FROM'   => $cable->getTable(),
            'WHERE'  => [
                'cablestrands_id' => $cablestrands_id,
            ]
        ];
        if ($cable->maybeDeleted()) {
            $criteria['WHERE']['is_deleted'] = 0;
        }

        $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
        $criteria['START'] = $start;
        $criteria['LIMIT'] = $_SESSION['glpilist_limit'];

        $iterator = $DB->request($criteria);

       // Execute a second request to get the total number of rows
        unset($criteria['SELECT']);
        unset($criteria['START']);
        unset($criteria['LIMIT']);

        $criteria['COUNT'] = 'total';
        $number = $DB->request($criteria)->current()['total'];

        if ($number) {
            echo "<div class='spaced'>";
            Html::printAjaxPager('', $start, $number);

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . _n('Type', 'Types', 1) . "</th>";
            echo "<th>" . Entity::getTypeName(1) . "</th>";
            echo "<th>" . __('Name') . "</th>";
            echo "<th>" . __('Inventory number') . "</th>";
            echo "<th>" . sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint B')) . "</th>";
            echo "<th>" . sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint B')) . "</th>";
            echo "<th>" . sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint A')) . "</th>";
            echo "<th>" . sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint A')) . "</th>";
            echo "</tr>";

            foreach ($iterator as $data) {
                if (!$cable->getFromDB($data['id'])) {
                    trigger_error(sprintf('Unable to load item %s (%s).', $cable->getType(), $data['id']), E_USER_WARNING);
                    continue;
                }

                echo "<tr class='tab_bg_1'><td>" . $cable->getTypeName() . "</td>";
                echo "<td>" . Dropdown::getDropdownName("glpi_entities", $cable->getEntityID()) . "</td>";
                echo "<td>" . $cable->getLink() . "</td>";
                echo "<td>" . (isset($cable->fields["otherserial"]) ? "" . $cable->fields["otherserial"] . "" : "-") . "</td>";
                echo "<td>";
                if ($cable->fields["items_id_endpoint_b"] > 0) {
                    $item_endpoint_b = getItemForItemtype($cable->fields["itemtype_endpoint_b"]);
                    if (!$item_endpoint_b->getFromDB($cable->fields["items_id_endpoint_b"])) {
                        trigger_error(sprintf('Unable to load item %s (%s).', $cable->fields["itemtype_endpoint_b"], $cable->fields["items_id_endpoint_b"]), E_USER_WARNING);
                    } else {
                        echo $item_endpoint_b->getLink();
                    }
                }
                echo "</td>";
                echo "<td>";
                if ($cable->fields["sockets_id_endpoint_b"] > 0) {
                    $sockets_endpoint_b = new Socket();
                    if (!$sockets_endpoint_b->getFromDB($cable->fields["sockets_id_endpoint_b"])) {
                        trigger_error(sprintf('Unable to load item %s (%s).', Socket::getType(), $cable->fields["sockets_id_endpoint_b"]), E_USER_WARNING);
                    } else {
                        echo $sockets_endpoint_b->getLink();
                    }
                }
                echo "</td>";
                echo "<td>";
                if ($cable->fields["items_id_endpoint_a"] > 0) {
                    $item_endpoint_a = getItemForItemtype($cable->fields["itemtype_endpoint_a"]);
                    if (!$item_endpoint_a->getFromDB($cable->fields["items_id_endpoint_a"])) {
                        trigger_error(sprintf('Unable to load item %s (%s).', $cable->fields["itemtype_endpoint_a"], $cable->fields["items_id_endpoint_a"]), E_USER_WARNING);
                    } else {
                        echo $item_endpoint_a->getLink();
                    }
                }
                echo "</td>";
                echo "<td>";
                if ($cable->fields["sockets_id_endpoint_a"] > 0) {
                    $sockets_endpoint_a = new Socket();
                    if (!$sockets_endpoint_a->getFromDB($cable->fields["sockets_id_endpoint_a"])) {
                        trigger_error(sprintf('Unable to load item %s (%s).', Socket::getType(), $cable->fields["sockets_id_endpoint_a"]), E_USER_WARNING);
                    } else {
                        echo $sockets_endpoint_a->getLink();
                    }
                }
                echo "</td>";
                echo"</tr>";
            }
        } else {
            echo "<p class='center b'>" . __('No item found') . "</p>";
        }
        echo "</table></div>";
    }

    public static function getIcon()
    {
        return Cable::getIcon();
    }
}
