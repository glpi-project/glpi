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

/**
 * Store ports connections log
 *
 * FIXME This class should inherit from CommonDBRelation, as it is linked
 * to both 'networkports_id_source' and 'networkports_id_destination'
 */
class NetworkPortConnectionLog extends CommonDBChild
{
    public static $itemtype        = 'NetworkPort';
    public static $items_id        = 'networkports_id';
    public $dohistory              = false;


    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     *
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return __('Port connection history');
    }

    /**
     * Get the tab name used for item
     *
     * @param CommonGLPI $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $array_ret = [];

        if ($item->getType() == 'NetworkPort') {
            $cnt = countElementsInTable([static::getTable()], $this->getCriteria($item));
            $array_ret[] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $cnt);
        }
        return $array_ret;
    }

    public function getCriteria(NetworkPort $netport)
    {
        return [
            'OR' => [
                'networkports_id_source'      => $netport->fields['id'],
                'networkports_id_destination' => $netport->fields['id']
            ]
        ];
    }

    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param integer $tabnum number of the tab to display
     * @param integer $withtemplate 1 if is a template form
     * @return boolean
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (get_class($item) == NetworkPort::class && $item->getID() > 0) {
            $connectionlog = new self();
            $connectionlog->showForItem($item);
            return true;
        }
        return false;
    }

    public function showForItem(NetworkPort $netport, $user_filters = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => $this->getCriteria($netport)
        ]);

        echo "<table class='tab_cadre_fixehov'>";
        echo "<thead><tr>";
        echo "<th>" . _n('State', 'States', 1)  . "</th>";
        echo "<th>" . _n('Date', 'Dates', 1)  . "</th>";
        echo "<th>" . __('Connected item')  . "</th>";
        echo "</tr></thead>";

        echo "<tbody>";

        if (!count($iterator)) {
            echo "<tr><td colspan='4' class='center'>" . __('No result found')  . "</td></tr>";
        }

        foreach ($iterator as $row) {
            echo "<tr>";
            echo "<td>";

            if ($row['connected'] == 1) {
                $co_class = 'fa-link netport green';
                $title = __('Connected');
            } else {
                $co_class = 'fa-unlink netport red';
                $title = __('Not connected');
            }
            echo "<i class='fas $co_class' title='$title'></i> <span class='sr-only'>$title</span>";
            echo "</td>";
            echo "<td>" . $row['date']  . "</td>";
            echo "<td>";

            $is_source = $netport->fields['id'] == $row['networkports_id_source'];
            $netports_id = $row[($is_source ? 'networkports_id_destination' : 'networkports_id_source')];

            $cport = new NetworkPort();
            if ($cport->getFromDB($netports_id)) {
                $citem = new $cport->fields["itemtype"]();
                $citem->getFromDB($cport->fields["items_id"]);

                $cport_link = sprintf(
                    "<a href='%1\$s'>%2\$s</a>",
                    $cport->getFormURLWithID($cport->fields['id']),
                    (trim($cport->fields['name']) == '' ? __('Without name') : $cport->fields['name'])
                );

                echo sprintf(
                    '%1$s on %2$s',
                    $cport_link,
                    $citem->getLink()
                );
            } else if ($row['connected'] == 1) {
                echo __('No longer exists in database');
            }

            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
    }
}
