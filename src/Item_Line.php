<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class Item_Line extends CommonDBRelation
{
    public static $itemtype_1 = 'Line';
    public static $items_id_1 = 'lines_id';
    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Line item', 'Line items', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        if ($item instanceof Line) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForMainItem($item) + self::countSimcardItemsForLine($item);
            }
            return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::getType(), 'ti ti-package');
        } else {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item) + self::countSimcardLinesForItem($item);
            }
            return self::createTabEntry(Line::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Line) {
            self::showItemsForLine($item);
        } else {
            self::showLinesForItem($item);
        }
        return true;
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'MassiveAction:update';
        $forbidden[] = 'CommonDBConnexity:affect';
        $forbidden[] = 'CommonDBConnexity:unaffect';

        return $forbidden;
    }

    public static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'add':
            case 'remove':
                return 1;

            case 'add_item':
            case 'remove_item':
                return 2;
        }
        return 0;
    }


    public static function getRelationMassiveActionsSpecificities()
    {
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = $CFG_GLPI['line_types'];

        // Define normalized action for add_item and remove_item
        $specificities['normalized']['add'][]          = 'add_item';
        $specificities['normalized']['remove'][]       = 'remove_item';

        // Set the labels for add_item and remove_item
        $specificities['button_labels']['add_item']    = $specificities['button_labels']['add'];
        $specificities['button_labels']['remove_item'] = $specificities['button_labels']['remove'];

        return $specificities;
    }

    /**
     * Count the number of lines associated to an item through a simcard.
     *
     * @param CommonDBTM $item
     * @return int
     */
    protected static function countSimcardLinesForItem(CommonDBTM $item)
    {
        return countElementsInTable(Item_DeviceSimcard::getTable(), [
            'items_id' => $item->getID(),
            'itemtype' => $item->getType(),
            'NOT'   => [
                'lines_id' => 0
            ]
        ]);
    }

    /**
     * Count the number of items associated to a line through a simcard.
     *
     * @param Line $line
     * @return int
     */
    protected static function countSimcardItemsForLine(Line $line)
    {
        return countElementsInTable(Item_DeviceSimcard::getTable(), [
            'lines_id' => $line->getID()
        ]);
    }

    /**
     * Show a list of items linked to a Line
     *
     * This includes directly linked items and items linked by a simcard.
     * It allows linking items directly to a line.
     *
     * @return void|false False if the line is not valid or the user does not have the right to view the line
     **/
    public static function showItemsForLine(Line $line)
    {
        global $DB, $CFG_GLPI;

        $ID = $line->fields['id'];
        $rand = mt_rand();

        if (
            !$line->getFromDB($ID)
            || !$line->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $line->canEdit($ID);

        $items = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'lines_id' => $ID,
            ]
        ]);

        $simcards = $DB->request([
            'FROM'   => Item_DeviceSimcard::getTable(),
            'WHERE'  => [
                'lines_id' => $ID
            ]
        ]);

        Session::initNavigateListItems(
            self::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $line->getTypeName(1),
                $line->getName()
            )
        );

        if (!count($simcards)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No simcard found') . "</th></tr>";
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr>";
            $header .= "<th>" . Item_DeviceSimcard::getTypeName(1) . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($simcards as $row) {
                $item = new Item_DeviceSimcard();
                $item->getFromDB($row['id']);
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . $item->getLink() . "</td>";
                echo "</tr>";
            }
            echo $header;
            echo "</table>";
        }

        if (static::canCreate()) {
            echo '<form method="post" action="' . static::getFormURL() . '">';
            echo '<table class="tab_cadre_fixe">';

            echo '<tr class="tab_bg_2"><th colspan="3">' . __('Add an item') . '</th></tr>';

            echo '<tr class="tab_bg_1">';
            echo '<td><label for="dropdown_items_id' . $rand . '">' . _n('Item', 'Items', 1) . '</label></td>';
            echo '<td>';

            //get all used items
            $used = [];
            $iterator = $DB->request([
                'FROM'   => static::getTable(),
                'WHERE'  => [
                    'lines_id' => $line->getID()
                ]
            ]);
            foreach ($iterator as $row) {
                $used[$row['itemtype']][$row['items_id']] = $row['items_id'];
            }

            Dropdown::showSelectItemFromItemtypes([
                'itemtypes'       => $CFG_GLPI['line_types'],
                'used'            => $used,
                'entity_restrict' => $line->isRecursive() ? getSonsOf('glpi_entities', $line->getEntityID()) : $line->getEntityID()
            ]);
            echo '</td>';
            echo '<td class="center">';
            echo '<input type="submit" name="add" value=" ' . _sx('button', 'Add') . '" class="btn btn-primary" />';
            echo '</td>';
            echo '</tr>';

            echo '</table>';
            echo Html::hidden('lines_id', ['value' => $line->getID()]);
            Html::closeForm();
        }

        if (!count($items)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>";
        } else {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
                    'container'       => 'mass' . __CLASS__ . $rand
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr>";
            if ($canedit) {
                $header .= "<th width='10'>";
                $header .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header .= "</th>";
            }
            $header .= "<th>" . _n('Item', 'Items', 1) . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($items as $row) {
                if (!is_a($row['itemtype'], CommonDBTM::class, true)) {
                    continue;
                }
                $item = new $row['itemtype']();
                $item->getFromDB($row['items_id']);
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
                    echo "</td>";
                }
                echo "<td>" . $item->getLink() . "</td>";
                echo "</tr>";
            }
            echo $header;
            echo "</table>";

            if ($canedit && count($items)) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
            }
            if ($canedit) {
                Html::closeForm();
            }
        }
    }

    /**
     * Show a list of lines linked to an item.
     *
     * This includes directly linked lines and lines linked by a simcard.
     * It allows linking lines directly to an item.
     *
     * @param CommonDBTM $item
     * @return void|false False if the item is not valid or the user does not have the right to view the item
     **/
    public static function showLinesForItem(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item::getType();
        $ID = $item->fields['id'];
        $rand = mt_rand();

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $item->canEdit($ID);

        $lines = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $ID,
            ]
        ]);

        $lines_from_sim = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Item_DeviceSimcard::getTable(),
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $ID,
                'NOT'   => [
                    'lines_id' => 0
                ]
            ]
        ]);

        Session::initNavigateListItems(
            self::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $item->getTypeName(1),
                $item->getName()
            )
        );

        if (!count($lines_from_sim)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No lines from simcard found') . "</th></tr>";
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr>";
            $header .= "<th>" . Item_DeviceSimcard::getTypeName(1) . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($lines_from_sim as $row) {
                $item = new Item_DeviceSimcard();
                $item->getFromDB($row['id']);
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . $item->getLink() . "</td>";
                echo "</tr>";
            }
            echo $header;
            echo "</table>";
        }

        if (static::canCreate()) {
            echo '<form method="post" action="' . static::getFormURL() . '">';
            echo '<table class="tab_cadre_fixe">';

            echo '<tr class="tab_bg_2"><th colspan="3">' . __('Add a line') . '</th></tr>';

            echo '<tr class="tab_bg_1">';
            echo '<td><label for="dropdown_items_id' . $rand . '">' . Line::getTypeName(1) . '</label></td>';
            echo '<td>';
            //get all used items
            $used = [];
            $iterator = $DB->request([
                'FROM'   => static::getTable(),
                'WHERE'  => [
                    'itemtype' => $itemtype,
                    'items_id' => $ID,
                ]
            ]);
            foreach ($iterator as $row) {
                $used[] = $row['lines_id'];
            }

            Line::dropdown([
                'rand'   => $rand,
                'used'   => $used,
                'entity' => $item->isRecursive() ? getSonsOf('glpi_entities', $item->getEntityID()) : $item->getEntityID(),
            ]);
            echo '</td>';
            echo '<td class="center">';
            echo '<input type="submit" name="add" value=" ' . _sx('button', 'Add') . '" class="btn btn-primary" />';
            echo '</td>';
            echo '</tr>';

            echo '</table>';
            echo Html::hidden('itemtype', ['value' => $itemtype]);
            echo Html::hidden('items_id', ['value' => $ID]);
            echo Html::hidden('_from', ['value' => 'item']);
            Html::closeForm();
        }

        if (!count($lines)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No line found') . "</th></tr>";
            echo "</table>";
        } else {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($lines)),
                    'container'       => 'mass' . __CLASS__ . $rand
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr>";
            if ($canedit) {
                $header .= "<th width='10'>";
                $header .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header .= "</th>";
            }
            $header .= "<th>" . Line::getTypeName(1) . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($lines as $row) {
                $line = new Line();
                $line->getFromDB($row['lines_id']);
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
                    echo "</td>";
                }
                echo "<td>" . $line->getLink() . "</td>";
                echo "</tr>";
            }
            echo $header;
            echo "</table>";

            if ($canedit && count($lines)) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
            }
            if ($canedit) {
                Html::closeForm();
            }
        }
    }

    public function showForm($ID, array $options = [])
    {
        return false;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    /**
     * Prepares input (for update and add)
     *
     * @param array $input Input data
     *
     * @return array
     */
    private function prepareInput($input)
    {
        $error_detected = [];

        //check for requirements
        if (
            ($this->isNewItem() && (!isset($input['itemtype']) || empty($input['itemtype'])))
            || (isset($input['itemtype']) && empty($input['itemtype']))
        ) {
            $error_detected[] = __('An item type is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['items_id']) || empty($input['items_id'])))
            || (isset($input['items_id']) && empty($input['items_id']))
        ) {
            $error_detected[] = __('An item is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['lines_id']) || empty($input['lines_id'])))
            || (isset($input['lines_id']) && empty($input['lines_id']))
        ) {
            $error_detected[] = __('A line is required');
        }

        if (count($error_detected)) {
            foreach ($error_detected as $error) {
                Session::addMessageAfterRedirect(
                    $error,
                    true,
                    ERROR
                );
            }
            return false;
        }

        return $input;
    }


    public static function getIcon()
    {
        return Line::getIcon();
    }
}
