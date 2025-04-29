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

class Link_Itemtype extends CommonDBChild
{
    // From CommonDbChild
    public static $itemtype = 'Link';
    public static $items_id = 'links_id';


    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * Print the HTML array for device on link
     *
     * @param $link : Link
     *
     * @return void
     **/
    public static function showForLink($link)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $links_id = $link->getField('id');

        $canedit  = $link->canEdit($links_id);
        $rand     = mt_rand();

        if (
            !Link::canView()
            || !$link->can($links_id, READ)
        ) {
            return false;
        }

        $iterator = $DB->request([
            'FROM'   => 'glpi_links_itemtypes',
            'WHERE'  => ['links_id' => $links_id],
            'ORDER'  => 'itemtype',
        ]);
        $types  = [];
        $used   = [];
        $numrows = count($iterator);
        foreach ($iterator as $data) {
            $types[$data['id']]      = $data;
            $used[$data['itemtype']] = $data['itemtype'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add an item type') . "</th></tr>";

            echo "<tr class='tab_bg_2'><td class='right'>";
            echo "<input type='hidden' name='links_id' value='$links_id'>";
            Dropdown::showItemTypes('itemtype', $CFG_GLPI["link_types"], ['used' => $used]);
            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $numrows),
                'container'      => 'mass' . __CLASS__ . $rand,
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixe'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $numrows) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($types as $data) {
            $typename = NOT_AVAILABLE;
            if ($item = getItemForItemtype($data['itemtype'])) {
                $typename = $item->getTypeName(1);
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td class='center'>$typename</td>";
                echo "</tr>";
            }
        }
        echo $header_begin . $header_bottom . $header_end;
        echo "</table>";
        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Link':
                    /** @var Link $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['links_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(_n(
                        'Associated item type',
                        'Associated item types',
                        Session::getPluralNumber()
                    ), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Link') {
            self::showForLink($item);
        }
        return true;
    }


    /**
     *
     * Remove all associations for an itemtype
     *
     * @since 0.85
     *
     * @param string $itemtype  itemtype for which all link associations must be removed
     */
    public static function deleteForItemtype($itemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $DB->delete(
            self::getTable(),
            [
                'itemtype'  => ['LIKE', "%Plugin$itemtype%"],
            ]
        );
    }
}
