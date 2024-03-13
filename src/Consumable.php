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

use Glpi\Event;

//!  Consumable Class
/**
 * This class is used to manage the consumables.
 * @see ConsumableItem
 * @author Julien Dombre
 **/
class Consumable extends CommonDBChild
{
    use Glpi\Features\Clonable;

   // From CommonDBTM
    protected static $forward_entity_to = ['Infocom'];
    public $no_form_page                = true;

    public static $rightname                   = 'consumable';

   // From CommonDBChild
    public static $itemtype             = 'ConsumableItem';
    public static $items_id             = 'consumableitems_id';

    public function getCloneRelations(): array
    {
        return [
            Infocom::class
        ];
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public static function getNameField()
    {
        return 'id';
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Consumable', 'Consumables', $nb);
    }


    public function prepareInputForAdd($input)
    {

        $item = new ConsumableItem();
        if ($item->getFromDB($input["consumableitems_id"])) {
            return ["consumableitems_id" => $item->fields["id"],
                "entities_id"        => $item->getEntityID(),
                "date_in"            => date("Y-m-d")
            ];
        }
        return [];
    }

    public function post_addItem()
    {

       // inherit infocom
        $infocoms = Infocom::getItemsAssociatedTo(ConsumableItem::getType(), $this->fields[ConsumableItem::getForeignKeyField()]);
        if (count($infocoms)) {
            $infocom = reset($infocoms);
            $infocom->clone([
                'itemtype'  => self::getType(),
                'items_id'  => $this->getID()
            ]);
        }

        parent::post_addItem();
    }


    /**
     * send back to stock
     *
     * @param array $input Array of item fields. Only the ID field is used here.
     * @param boolean $history Not used
     *
     * @return bool
     */
    public function backToStock(array $input, $history = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->update(
            $this->getTable(),
            [
                'date_out' => 'NULL'
            ],
            [
                'id' => $input['id']
            ]
        );
        if ($result) {
            return true;
        }
        return false;
    }


    public function getPreAdditionalInfosForName()
    {

        $ci = new ConsumableItem();
        if ($ci->getFromDB($this->fields['consumableitems_id'])) {
            return $ci->getName();
        }
        return '';
    }


    /**
     * UnLink a consumable linked to a printer
     *
     * UnLink the consumable identified by $ID
     *
     * @param integer $ID       consumable identifier
     * @param string  $itemtype itemtype of who we give the consumable
     * @param integer $items_id ID of the item giving the consumable
     *
     * @return boolean
     **/
    public function out($ID, $itemtype = '', $items_id = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (
            !empty($itemtype)
            && ($items_id > 0)
        ) {
            $result = $DB->update(
                $this->getTable(),
                [
                    'date_out'  => date('Y-m-d'),
                    'itemtype'  => $itemtype,
                    'items_id'  => $items_id
                ],
                [
                    'id' => $ID
                ]
            );
            if ($result) {
                return true;
            }
        }
        return false;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $input = $ma->getInput();
        switch ($ma->getAction()) {
            case 'give':
                if (isset($input["entities_id"])) {
                    Dropdown::showSelectItemFromItemtypes(['itemtype_name'
                                                              => 'give_itemtype',
                        'items_id_name'
                                                              => 'give_items_id',
                        'entity_restrict'
                                                              => Session::getMatchingActiveEntities($input["entities_id"]),
                        'itemtypes'
                                                              => $CFG_GLPI["consumables_types"]
                    ]);
                    echo "<br><br>" . Html::submit(
                        _x('button', 'Give'),
                        ['name' => 'massiveaction']
                    );
                    return true;
                }
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var Consumable $item */
        switch ($ma->getAction()) {
            case 'backtostock':
                foreach ($ids as $id) {
                    if ($item->can($id, UPDATE)) {
                        if ($item->backToStock(["id" => $id])) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;
            case 'give':
                $input = $ma->getInput();
                if (
                    ($input["give_items_id"] > 0)
                    && !empty($input['give_itemtype'])
                ) {
                    foreach ($ids as $key) {
                        if ($item->can($key, UPDATE)) {
                            if ($item->out($key, $input['give_itemtype'], $input["give_items_id"])) {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                             $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                             $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                    Event::log(
                        $item->fields['consumableitems_id'],
                        "consumableitems",
                        5,
                        "inventory",
                        //TRANS: %s is the user login
                        sprintf(__('%s gives a consumable'), $_SESSION["glpiname"])
                    );
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * count how many consumable for the consumable item $tID
     *
     * @param integer $tID consumable item identifier.
     *
     * @return integer number of consumable counted.
     **/
    public static function getTotalNumber($tID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => ['consumableitems_id' => $tID]
        ])->current();
        return (int)$result['cpt'];
    }


    /**
     * count how many old consumable for the consumable item $tID
     *
     * @param integer $tID consumable item identifier.
     *
     * @return integer number of old consumable counted.
     **/
    public static function getOldNumber($tID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'consumableitems_id' => $tID,
                'NOT'                => ['date_out' => null]
            ]
        ])->current();
        return (int)$result['cpt'];
    }


    /**
     * count how many consumable unused for the consumable item $tID
     *
     * @param integer $tID consumable item identifier.
     *
     * @return integer number of consumable unused counted.
     **/
    public static function getUnusedNumber($tID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'consumableitems_id' => $tID,
                'date_out'           => null
            ]
        ])->current();
        return(int) $result['cpt'];
    }

    /**
     * The desired stock level
     *
     * This is used when the alarm threshold is reached to know how many to order.
     * @param integer $tID Consumable item ID
     * @return integer
     */
    public static function getStockTarget(int $tID): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $it = $DB->request([
            'SELECT'  => ['stock_target'],
            'FROM'   => ConsumableItem::getTable(),
            'WHERE'  => [
                'id'  => $tID
            ]
        ]);
        if ($it->count()) {
            return $it->current()['stock_target'];
        }
        return 0;
    }

    /**
     * The lower threshold for the stock amount before an alarm is triggered
     *
     * @param integer $tID Consumable item ID
     * @return integer
     */
    public static function getAlarmThreshold(int $tID): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $it = $DB->request([
            'SELECT'  => ['alarm_threshold'],
            'FROM'   => ConsumableItem::getTable(),
            'WHERE'  => [
                'id'  => $tID
            ]
        ]);
        if ($it->count()) {
            return $it->current()['alarm_threshold'];
        }
        return 0;
    }

    /**
     * Get the consumable count HTML array for a defined consumable type
     *
     * @param integer $tID             consumable item identifier.
     * @param integer $alarm_threshold threshold alarm value.
     * @param boolean $nohtml          Return value without HTML tags.
     *
     * @return string to display
     **/
    public static function getCount($tID, $alarm_threshold, $nohtml = false)
    {

       // Get total
        $total = self::getTotalNumber($tID);

        if ($total != 0) {
            $unused = self::getUnusedNumber($tID);
            $old    = self::getOldNumber($tID);

            $highlight = "";
            if ($unused <= $alarm_threshold) {
                $highlight = "class='tab_bg_1_2'";
            }
           //TRANS: For consumable. %1$d is total number, %2$d is unused number, %3$d is old number
            $tmptxt = sprintf(__('Total: %1$d, New: %2$d, Used: %3$d'), $total, $unused, $old);
            if ($nohtml) {
                $out = $tmptxt;
            } else {
                $out = "<div $highlight>" . $tmptxt . "</div>";
            }
        } else {
            if ($nohtml) {
                $out = __('No consumable');
            } else {
                $out = "<div class='tab_bg_1_2'><i>" . __('No consumable') . "</i></div>";
            }
        }
        return $out;
    }


    /**
     * Check if a Consumable is New (not used, in stock)
     *
     * @param integer $cID consumable ID.
     *
     * @return boolean
     **/
    public static function isNew($cID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'id'        => $cID,
                'date_out'  => null
            ]
        ])->current();
        return $result['cpt'] == 1;
    }


    /**
     * Check if a consumable is Old (used, not in stock)
     *
     * @param integer $cID consumable ID.
     *
     * @return boolean
     **/
    public static function isOld($cID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'id'     => $cID,
                'NOT'   => ['date_out' => null]
            ]
        ])->current();
        return $result['cpt'] == 1;
    }


    /**
     * Get the localized string for the status of a consumable
     *
     * @param integer $cID consumable ID.
     *
     * @return string
     **/
    public static function getStatus($cID)
    {

        if (self::isNew($cID)) {
            return _nx('consumable', 'New', 'New', 1);
        } else if (self::isOld($cID)) {
            return _nx('consumable', 'Used', 'Used', 1);
        }
        return '';
    }


    /**
     * Print out a link to add directly a new consumable from a consumable item.
     *
     * @param ConsumableItem $consitem
     *
     * @return void
     **/
    public static function showAddForm(ConsumableItem $consitem)
    {

        $ID = $consitem->getField('id');

        if (!$consitem->can($ID, UPDATE)) {
            return;
        }

        if ($ID > 0) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action=\"" . static::getFormURL() . "\">";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><td class='tab_bg_2 center'>";
            echo "<input type='hidden' name='consumableitems_id' value='$ID'>\n";
            Dropdown::showNumber('to_add', ['value' => 1,
                'min'   => 1,
                'max'   => 100
            ]);
            echo " <input type='submit' name='add_several' value=\"" . _sx('button', 'Add consumables') . "\"
                class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
    }


    /**
     * Print out the consumables of a defined type
     *
     * @param ConsumableItem $consitem
     * @param boolean        $show_old show old consumables or not. (default 0)
     *
     * @return void
     **/
    public static function showForConsumableItem(ConsumableItem $consitem, $show_old = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tID = $consitem->getField('id');
        if (!$consitem->can($tID, READ)) {
            return;
        }

        if (isset($_GET["start"])) {
            $start = $_GET["start"];
        } else {
            $start = 0;
        }

        $canedit = $consitem->can($tID, UPDATE);
        $rand = mt_rand();
        $where = ['consumableitems_id' => $tID];
        $order = ['date_in', 'id'];
        if (!$show_old) { // NEW
            $where += ['date_out' => 'NULL'];
        } else { //OLD
            $where += ['NOT'   => ['date_out' => 'NULL']];
            $order = ['date_out DESC'] + $order;
        }

        $number = countElementsInTable("glpi_consumables", $where);

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => $where,
            'ORDER'  => $order,
            'START'  => (int)$start,
            'LIMIT'  => (int)$_SESSION['glpilist_limit']
        ]);

        echo "<div class='spaced'>";

       // Display the pager
        Html::printAjaxPager(Consumable::getTypeName(Session::getPluralNumber()), $start, $number);

        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $actions = [];
            if ($consitem->can($tID, PURGE)) {
                $actions['delete'] = _x('button', 'Delete permanently');
            }
            $actions['Infocom' . MassiveAction::CLASS_ACTION_SEPARATOR . 'activate']
            = __('Enable the financial and administrative information');

            if ($show_old) {
                $actions['Consumable' . MassiveAction::CLASS_ACTION_SEPARATOR . 'backtostock']
                     = __('Back to stock');
            } else {
                $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'give'] = _x('button', 'Give');
            }
            $entparam = ['entities_id' => $consitem->getEntityID()];
            if ($consitem->isRecursive()) {
                $entparam = ['entities_id' => getSonsOf('glpi_entities', $consitem->getEntityID())];
            }
            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                'specific_actions' => $actions,
                'container'        => 'mass' . __CLASS__ . $rand,
                'extraparams'      => $entparam
            ];
            Html::showMassiveActions($massiveactionparams);
            echo "<input type='hidden' name='consumableitems_id' value='$tID'>\n";
        }

        echo "<table class='tab_cadre_fixehov'>";
        if (!$show_old) {
            echo "<tr><th colspan=" . ($canedit ? '5' : '4') . ">";
            echo self::getCount($tID, -1);
            echo "</th></tr>";
        } else { // Old
            echo "<tr><th colspan='" . ($canedit ? '7' : '6') . "'>" . __('Used consumables') . "</th></tr>";
        }

        if ($number) {
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_begin  .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_end    .= "</th>";
            }
            $header_end .= "<th>" . __('ID') . "</th>";
            $header_end .= "<th>" . _x('item', 'State') . "</th>";
            $header_end .= "<th>" . __('Add date') . "</th>";
            if ($show_old) {
                $header_end .= "<th>" . __('Use date') . "</th>";
                $header_end .= "<th>" . __('Given to') . "</th>";
            }
            $header_end .= "<th width='200px'>" . __('Financial and administrative information') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($iterator as $data) {
                $date_in  = Html::convDate($data["date_in"]);
                $date_out = Html::convDate($data["date_out"]);

                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td class='center'>" . $data["id"] . "</td>";
                echo "<td class='center'>" . self::getStatus($data["id"]) . "</td>";
                echo "<td class='center'>" . $date_in . "</td>";
                if ($show_old) {
                    echo "<td class='center'>" . $date_out . "</td>";
                    echo "<td class='center'>";
                    if ($item = getItemForItemtype($data['itemtype'])) {
                        if ($item->getFromDB($data['items_id'])) {
                             echo $item->getLink();
                        }
                    }
                    echo "</td>";
                }
                echo "<td class='center'>";
                Infocom::showDisplayLink('Consumable', $data["id"]);
                echo "</td>";
                echo "</tr>";
            }
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
     * Show the usage summary of consumables by user
     *
     * @return void
     **/
    public static function showSummary()
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!Consumable::canView()) {
            return;
        }

        $iterator = $DB->request([
            'SELECT' => [
                'COUNT'  => ['* AS count'],
                'consumableitems_id',
                'itemtype',
                'items_id'
            ],
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'NOT'                => ['date_out' => null],
                'consumableitems_id' => new \QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_consumableitems',
                    'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems')
                ])
            ],
            'GROUP'  => ['itemtype', 'items_id', 'consumableitems_id']
        ]);
        $used = [];

        foreach ($iterator as $data) {
            $used[$data['itemtype'] . '####' . $data['items_id']][$data["consumableitems_id"]]
            = $data["count"];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'COUNT'  => '* AS count',
                'consumableitems_id',
            ],
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'date_out'           => null,
                'consumableitems_id' => new \QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_consumableitems',
                    'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems')
                ])
            ],
            'GROUP'  => ['consumableitems_id']
        ]);
        $new = [];

        foreach ($iterator as $data) {
            $new[$data["consumableitems_id"]] = $data["count"];
        }

        $iterator = $DB->request([
            'FROM'   => 'glpi_consumableitems',
            'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems')
        ]);
        $types = [];

        foreach ($iterator as $data) {
            $types[$data["id"]] = $data["name"];
        }

        asort($types);
        $total = [];
        if (count($types) > 0) {
           // Produce headline
            echo "<div class='center'><table class='tab_cadrehov'><tr>";

           // Type
            echo "<th>" . __('Give to') . "</th>";

            foreach ($types as $key => $type) {
                echo "<th>$type</th>";
                $total[$key] = 0;
            }
            echo "<th>" . __('Total') . "</th>";
            echo "</tr>";

           // new
            echo "<tr class='tab_bg_2'><td class='b'>" . __('In stock') . "</td>";
            $tot = 0;
            foreach ($types as $id_type => $type) {
                if (!isset($new[$id_type])) {
                    $new[$id_type] = 0;
                }
                echo "<td class='center'>" . $new[$id_type] . "</td>";
                $total[$id_type] += $new[$id_type];
                $tot             += $new[$id_type];
            }
            echo "<td class='numeric'>" . $tot . "</td>";
            echo "</tr>";

            foreach ($used as $itemtype_items_id => $val) {
                echo "<tr class='tab_bg_2'><td>";
                list($itemtype,$items_id) = explode('####', $itemtype_items_id);
                $item = new $itemtype();
                if ($item->getFromDB($items_id)) {
                   //TRANS: %1$s is a type name - %2$s is a name
                    printf(__('%1$s - %2$s'), $item->getTypeName(1), $item->getNameID());
                }
                echo "</td>";
                $tot = 0;
                foreach ($types as $id_type => $type) {
                    if (!isset($val[$id_type])) {
                        $val[$id_type] = 0;
                    }
                    echo "<td class='center'>" . $val[$id_type] . "</td>";
                    $total[$id_type] += $val[$id_type];
                    $tot             += $val[$id_type];
                }
                echo "<td class='numeric'>" . $tot . "</td>";
                echo "</tr>";
            }
            echo "<tr class='tab_bg_1'><td class='b'>" . __('Total') . "</td>";
            $tot = 0;
            foreach ($types as $id_type => $type) {
                $tot += $total[$id_type];
                echo "<td class='numeric'>" . $total[$id_type] . "</td>";
            }
            echo "<td class='numeric'>" . $tot . "</td>";
            echo "</tr>";
            echo "</table></div>";
        } else {
            echo "<div class='center b'>" . __('No consumable found') . "</div>";
        }
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate && Consumable::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case 'ConsumableItem':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  self::countForConsumableItem($item);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    /**
     * @param ConsumableItem $item
     *
     * @return integer
     **/
    public static function countForConsumableItem(ConsumableItem $item)
    {

        return countElementsInTable(['glpi_consumables'], ['glpi_consumables.consumableitems_id' => $item->getField('id')]);
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'ConsumableItem':
                self::showAddForm($item);
                self::showForConsumableItem($item);
                self::showForConsumableItem($item, 1);
                break;
        }
        return true;
    }

    public function getRights($interface = 'central')
    {
        $ci = new ConsumableItem();
        return $ci->getRights($interface);
    }


    public static function getIcon()
    {
        return "ti ti-package";
    }
}
