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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
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
            echo "<div class='firstbloc'>\n";
            echo "<form method='post' action='" . static::getFormURL() . "'>\n";
            echo "<table class='tab_cadre_fixe'>\n";
            echo "<tr><th>" . __('Associate a VLAN') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<input type='hidden' name='ipnetworks_id' value='$ID'>";
            Vlan::dropdown(['used' => $used]);
            echo "&nbsp;<input type='submit' name='add' value='" . _sx('button', 'Associate') .
                      "' class='btn btn-primary'>";
            echo "</td></tr>\n";

            echo "</table>\n";
            Html::closeForm();
            echo "</div>\n";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                'container'     => 'mass' . __CLASS__ . $rand,
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('ID TAG') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        $used = [];
        foreach ($vlans as $data) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
                echo "</td>";
            }
            $name = $data["name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
            }
            echo "<td class='center b'>
               <a href='" . $CFG_GLPI["root_doc"] . "/front/vlan.form.php?id=" . $data["id"] . "'>" . $name .
              "</a>";
            echo "</td>";
            echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
            echo "<td class='numeric'>" . $data["tag"] . "</td>";
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
        /** @var \DBmysql $DB */
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
            switch ($item->getType()) {
                case 'IPNetwork':
                    /** @var IPNetwork $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  countElementsInTable(
                            $this->getTable(),
                            ['ipnetworks_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Vlan::getTypeName(), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'IPNetwork') {
            self::showForIPNetwork($item);
        }
        return true;
    }
}
