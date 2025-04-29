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

class Item_Enclosure extends CommonDBRelation
{
    public static $itemtype_1 = 'Enclosure';
    public static $items_id_1 = 'enclosures_id';
    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';
    public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
    public static $mustBeAttached_1 = false; // FIXME It make no sense for an enclosure item to not be attached to an Enclosure.
    public static $mustBeAttached_2 = false; // FIXME It make no sense for an enclosure item to not be attached to an Item.

    public static function getTypeName($nb = 0)
    {
        return _n('Item', 'Item', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countForMainItem($item);
        }
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showItems($item);
        return true;
    }

    /**
     * Print enclosure items
     *
     * @return void
     **/
    public static function showItems(Enclosure $enclosure)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $enclosure->getID();
        $rand = mt_rand();

        if (
            !$enclosure->getFromDB($ID)
            || !$enclosure->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $enclosure->canEdit($ID);

        $items = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'enclosures_id' => $enclosure->getID(),
            ],
        ]);

        Session::initNavigateListItems(
            self::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $enclosure->getTypeName(1),
                $enclosure->getName()
            )
        );

        if ($enclosure->canAddItem('itemtype')) {
            echo "<div class='firstbloc'>";
            Html::showSimpleForm(
                Item_Enclosure::getFormURL(),
                '_add_fromitem',
                __('Add new item to this enclosure...'),
                [
                    'enclosure'   => $enclosure->getID(),
                    'position'  => 1,
                ]
            );
            echo "</div>";
        }

        $items = iterator_to_array($items);

        if (!count($items)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>";
        } else {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
                    'container'       => 'mass' . __CLASS__ . $rand,
                    'specific_actions' => [
                        'purge' => _x('button', 'Delete permanently the relation with selected elements'),
                    ],
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
            $header .= "<th>" . __('Position') . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($items as $row) {
                $item = new $row['itemtype']();
                $item->getFromDB($row['items_id']);
                echo "<tr lass='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
                    echo "</td>";
                }
                echo "<td>" . $item->getLink() . "</td>";
                echo "<td>{$row['position']}</td>";
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

    public function showForm($ID, array $options = [])
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        echo "<div class='center'>";

        $this->initForm($ID, $options);
        $this->showFormHeader();

        $enclosure = new Enclosure();
        $enclosure->getFromDB($this->fields['enclosures_id']);

        $rand = mt_rand();

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='dropdown_itemtype$rand'>" . __('Item type') . "</label></td>";
        echo "<td>";
        $types = $CFG_GLPI['rackable_types'];
        $translated_types = [];
        unset($types[array_search('Enclosure', $types)]);
        foreach ($types as $type) {
            $translated_types[$type] = $type::getTypeName(1);
        }
        Dropdown::showFromArray(
            'itemtype',
            $translated_types,
            [
                'display_emptychoice'   => true,
                'value'                 => $this->fields["itemtype"],
                'rand'                  => $rand,
            ]
        );

        //get all used items
        $used = [];
        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
        ]);
        foreach ($iterator as $row) {
            $used [$row['itemtype']][] = $row['items_id'];
        }

        // get used items by racks
        $iterator = $DB->request([
            'FROM'  => Item_Rack::getTable(),
            'WHERE' => [
                'is_reserved' => 0,
            ],
        ]);
        foreach ($iterator as $row) {
            $used [$row['itemtype']][] = $row['items_id'];
        }

        Ajax::updateItemOnSelectEvent(
            "dropdown_itemtype$rand",
            "items_id",
            $CFG_GLPI["root_doc"] . "/ajax/dropdownAllItems.php",
            [
                'idtable'   => '__VALUE__',
                'name'      => 'items_id',
                'value'     => $this->fields['items_id'],
                'rand'      => $rand,
                'used'      => $used,
            ]
        );

        //TODO: update possible positions according to selected item number of units
        //TODO: update positions on rack selection
        //TODO: update hpos from item model info is_half_rack
        //TODO: update orientation according to item model depth

        echo "</td>";
        echo "<td><label for='dropdown_items_id$rand'>" . _n('Item', 'Items', 1) . "</label></td>";
        echo "<td id='items_id'>";
        if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
            $itemtype = new $itemtype();
            $itemtype::dropdown([
                'name'   => "items_id",
                'value'  => $this->fields['items_id'],
                'rand'   => $rand,
            ]);
        } else {
            Dropdown::showFromArray(
                'items_id',
                [],
                [
                    'display_emptychoice'   => true,
                    'rand'                  => $rand,
                ]
            );
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='dropdown_enclosures_id$rand'>" . Enclosure::getTypeName(1) . "</label></td>";
        echo "<td>";
        Enclosure::dropdown(['value' => $this->fields["enclosures_id"], 'rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_position$rand'>" . __('Position') . "</label></td>";
        echo "<td>";
        Dropdown::showNumber(
            'position',
            [
                'value'  => $this->fields["position"],
                'min'    => 1,
                'step'   => 1,
                'used'   => $enclosure->getFilled($this->fields['itemtype'], $this->fields['items_id']),
                'rand'   => $rand,
            ]
        );
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
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
     * @return false|array
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
            ($this->isNewItem() && (!isset($input['enclosures_id']) || empty($input['enclosures_id'])))
            || (isset($input['enclosures_id']) && empty($input['enclosures_id']))
        ) {
            $error_detected[] = __('An enclosure is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['position']) || empty($input['position'])))
            || (isset($input['position']) && empty($input['position']))
        ) {
            $error_detected[] = __('A position is required');
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
        return Enclosure::getIcon();
    }
}
