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

/**
 * @since 0.84
 **/
class IPNetwork_Vlan extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1          = 'IPNetwork';
    public static $items_id_1          = 'ipnetworks_id';

    public static $itemtype_2          = 'Vlan';
    public static $items_id_2          = 'vlans_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @param $portID
     * @param $vlanID
     **/
    public function unassignVlan($portID, $vlanID)
    {

        $this->getFromDBByCrit([
            'ipnetworks_id'   => $portID,
            'vlans_id'        => $vlanID,
        ]);

        return $this->delete($this->fields);
    }


    /**
     * @param $port
     * @param $vlan
     **/
    public function assignVlan($port, $vlan)
    {

        $input = ['ipnetworks_id' => $port,
            'vlans_id'      => $vlan,
        ];

        return $this->add($input);
    }


    /**
     * @param $port   IPNetwork object
     **/
    public static function showForIPNetwork(IPNetwork $port)
    {
        global $CFG_GLPI, $DB;

        $ID = $port->getID();
        if (!$port->can($ID, READ)) {
            return false;
        }

        $canedit = $port->canEdit($ID);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT'    => [
                self::getTable() . '.id AS assocID',
                'glpi_vlans.*',
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                'glpi_vlans'   => [
                    'ON' => [
                        self::getTable()  => 'vlans_id',
                        'glpi_vlans'      => 'id',
                    ],
                ],
            ],
            'WHERE'     => ['ipnetworks_id' => $ID],
        ]);

        $vlans  = [];
        $used   = [];
        $number = count($iterator);
        foreach ($iterator as $line) {
            $used[$line["id"]]       = $line["id"];
            $vlans[$line["assocID"]] = $line;
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action='" . htmlescape(static::getFormURL()) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __s('Associate a VLAN') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<input type='hidden' name='ipnetworks_id' value='$ID'>";
            Vlan::dropdown(['used' => $used]);
            echo "&nbsp;<input type='submit' name='add' value='" . _sx('button', 'Associate')
                      . "' class='btn btn-primary'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . self::class . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                'container'     => 'mass' . self::class . $rand,
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . __s('Name') . "</th>";
        $header_end .= "<th>" . htmlescape(Entity::getTypeName(1)) . "</th>";
        $header_end .= "<th>" . __s('ID TAG') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;
        foreach ($vlans as $data) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(self::class, $data["assocID"]);
                echo "</td>";
            }
            $name = htmlescape($data["name"]);
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                $name = sprintf(__s('%1$s (%2$s)'), $name, (int) $data["id"]);
            }
            echo "<td class='center b'>
               <a href='" . htmlescape(Vlan::getFormURLWithID($data["id"])) . "'>" . $name
              . "</a>";
            echo "</td>";
            echo "<td class='center'>" . htmlescape(Dropdown::getDropdownName("glpi_entities", $data["entities_id"])) . '</td>';
            echo "<td class='numeric'>" . htmlescape($data["tag"]) . "</td>";
            echo "</tr>";
        }
        if ($number) {
            echo $header_begin . $header_bottom . $header_end;
        }
        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * @param $portID
     **/
    public static function getVlansForIPNetwork($portID)
    {
        global $DB;

        $vlans = [];
        $iterator = $DB->request([
            'SELECT' => 'vlans_id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['ipnetworks_id' => $portID],
        ]);
        foreach ($iterator as $data) {
            $vlans[$data['vlans_id']] = $data['vlans_id'];
        }

        return $vlans;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch (true) {
                case $item instanceof IPNetwork:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  countElementsInTable(
                            $this->getTable(),
                            ['ipnetworks_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Vlan::getTypeName(), $nb, $item::getType());
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item instanceof IPNetwork) {
            self::showForIPNetwork($item);
        }
        return true;
    }
}
