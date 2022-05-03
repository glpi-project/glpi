<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * Cartridge class.
 * This class is used to manage printer cartridges.
 * @see CartridgeItem
 * @author Julien Dombre
 **/
class Cartridge extends CommonDBRelation
{
    use Glpi\Features\Clonable;

   // From CommonDBTM
    protected static $forward_entity_to = ['Infocom'];
    public $dohistory                   = true;
    public $no_form_page                = true;

    public static $itemtype_1 = 'CartridgeItem';
    public static $items_id_1 = 'cartridgeitems_id';

    public static $itemtype_2 = 'Printer';
    public static $items_id_2 = 'printers_id';
    public static $mustBeAttached_2 = false;

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


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'updatepages':
                $input = $ma->getInput();
                if (!isset($input['maxpages'])) {
                    $input['maxpages'] = '';
                }
                echo "<input type='text' name='pages' value=\"" . $input['maxpages'] . "\" size='6'>";
                echo "<br><br>" . Html::submit(_x('button', 'Update'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    public static function getNameField()
    {
        return 'id';
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Cartridge', 'Cartridges', $nb);
    }


    public function prepareInputForAdd($input)
    {

        $item = static::getItemFromArray(CartridgeItem::class, CartridgeItem::getForeignKeyField(), $input);
        if ($item === false) {
            return false;
        }

        return ["cartridgeitems_id" => $item->fields["id"],
            "entities_id"       => $item->getEntityID(),
            "date_in"           => date("Y-m-d")
        ];
    }

    public function post_addItem()
    {

       // inherit infocom
        $infocoms = Infocom::getItemsAssociatedTo(CartridgeItem::getType(), $this->fields[CartridgeItem::getForeignKeyField()]);
        if (count($infocoms)) {
            $infocom = reset($infocoms);
            $infocom->clone([
                'itemtype'  => self::getType(),
                'items_id'  => $this->getID()
            ]);
        }

        parent::post_addItem();
    }


    public function post_updateItem($history = 1)
    {

        if (in_array('pages', $this->updates)) {
            $printer = new Printer();
            if (
                $printer->getFromDB($this->fields['printers_id'])
                && (($this->fields['pages'] > $printer->getField('last_pages_counter'))
                 || ($this->oldvalues['pages'] == $printer->getField('last_pages_counter')))
            ) {
                $printer->update(['id'                 => $printer->getID(),
                    'last_pages_counter' => $this->fields['pages']
                ]);
            }
        }
        parent::post_updateItem($history);
    }


    public function getPreAdditionalInfosForName()
    {

        $ci = new CartridgeItem();
        if ($ci->getFromDB($this->fields['cartridgeitems_id'])) {
            return $ci->getName();
        }
        return '';
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'uninstall':
                foreach ($ids as $key) {
                    if ($item->can($key, UPDATE)) {
                        if ($item->uninstall($key)) {
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
                return;

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

            case 'updatepages':
                $input = $ma->getInput();
                if (isset($input['pages'])) {
                    foreach ($ids as $key) {
                        if ($item->can($key, UPDATE)) {
                            if (
                                $item->update(['id' => $key,
                                    'pages' => $input['pages']
                                ])
                            ) {
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
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * Send the cartridge back to stock.
     *
     * @since 0.85 (before name was restore)
     * @param array   $input
     * @param integer $history
     * @return bool
     */
    public function backToStock(array $input, $history = 1)
    {
        global $DB;

        $result = $DB->update(
            $this->getTable(),
            [
                'date_out'     => 'NULL',
                'date_use'     => 'NULL',
                'printers_id'  => 0
            ],
            [
                'id' => $input['id']
            ]
        );
        if ($result && ($DB->affectedRows() > 0)) {
            return true;
        }
        return false;
    }


   // SPECIFIC FUNCTIONS

    /**
     * Link a cartridge to a printer.
     *
     * Link the first unused cartridge of type $Tid to the printer $pID.
     *
     * @param integer $tID ID of the cartridge
     * @param integer $pID : ID of the printer
     *
     * @return boolean True if successful
     **/
    public function install($pID, $tID)
    {
        global $DB;

       // Get first unused cartridge
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'cartridgeitems_id'  => $tID,
                'date_use'           => null
            ],
            'LIMIT'  => 1
        ]);

        if (count($iterator)) {
            $result = $iterator->current();
            $cID = $result['id'];
            // Update cartridge taking care of multiple insertion
            $result = $DB->update(
                $this->getTable(),
                [
                    'date_use'     => date('Y-m-d'),
                    'printers_id'  => $pID
                ],
                [
                    'id'        => $cID,
                    'date_use'  => null
                ]
            );
            if ($result && ($DB->affectedRows() > 0)) {
                 $changes = [
                     '0',
                     '',
                     __('Installing a cartridge'),
                 ];
                 Log::history($pID, 'Printer', $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);
                 return true;
            }
        } else {
            Session::addMessageAfterRedirect(__('No free cartridge'), false, ERROR);
        }
        return false;
    }


    /**
     * Unlink a cartridge from a printer by cartridge ID.
     *
     * @param integer $ID ID of the cartridge
     *
     * @return boolean
     **/
    public function uninstall($ID)
    {
        global $DB;

        if ($this->getFromDB($ID)) {
            $printer = new Printer();
            $toadd   = [];
            if ($printer->getFromDB($this->getField("printers_id"))) {
                $toadd['pages'] = $printer->fields['last_pages_counter'];
            }

            $result = $DB->update(
                $this->getTable(),
                [
                    'date_out'  => date('Y-m-d')
                ] + $toadd,
                [
                    'id'  => $ID
                ]
            );

            if (
                $result
                && ($DB->affectedRows() > 0)
            ) {
                 $changes = [
                     '0',
                     '',
                     __('Uninstalling a cartridge'),
                 ];
                 Log::history(
                     $this->getField("printers_id"),
                     'Printer',
                     $changes,
                     0,
                     Log::HISTORY_LOG_SIMPLE_MESSAGE
                 );

                 return true;
            }
            return false;
        }
    }


    /**
     * Print the cartridge count HTML array for the cartridge item $tID
     *
     * @param integer         $tID      ID of the cartridge item
     * @param integer         $alarm_threshold Alarm threshold value
     * @param integer|boolean $nohtml          True if the return value should be without HTML tags (default 0/false)
     *
     * @return string String to display
     **/
    public static function getCount($tID, $alarm_threshold, $nohtml = 0)
    {

       // Get total
        $total = self::getTotalNumber($tID);
        $out   = "";
        if ($total != 0) {
            $unused     = self::getUnusedNumber($tID);
            $used       = self::getUsedNumber($tID);
            $old        = self::getOldNumber($tID);
            $highlight  = "";
            if ($unused <= $alarm_threshold) {
                $highlight = "tab_bg_1_2";
            }

            if (!$nohtml) {
                $out .= "<table  class='tab_format $highlight' width='100%'><tr><td>";
                $out .= __('Total') . "</td><td>$total";
                $out .= "</td><td class='b'>";
                $out .= _nx('cartridge', 'New', 'New', $unused);
                $out .= "</td><td class='b'>$unused</td></tr>";
                $out .= "<tr><td>";
                $out .= _nx('cartridge', 'Used', 'Used', $used);
                $out .= "</td><td>$used</td><td>";
                $out .= _nx('cartridge', 'Worn', 'Worn', $old);
                $out .= "</td><td>$old</td></tr></table>";
            } else {
               //TRANS : for display cartridges count : %1$d is the total number,
               //        %2$d the new one, %3$d the used one, %4$d worn one
                $out .= sprintf(
                    __('Total: %1$d (%2$d new, %3$d used, %4$d worn)'),
                    $total,
                    $unused,
                    $used,
                    $old
                );
            }
        } else {
            if (!$nohtml) {
                $out .= "<div class='tab_bg_1_2'><i>" . __('No cartridge') . "</i></div>";
            } else {
                $out .= __('No cartridge');
            }
        }
        return $out;
    }


    /**
     * Print the cartridge count HTML array for the printer $pID
     *
     * @since 0.85
     *
     * @param integer         $pID    ID of the printer
     * @param integer|boolean $nohtml True if the return value should be without HTML tags (default 0/false)
     *
     * @return string String to display
     **/
    public static function getCountForPrinter($pID, $nohtml = 0)
    {

       // Get total
        $total = self::getTotalNumberForPrinter($pID);
        $out   = "";
        if ($total != 0) {
            $used       = self::getUsedNumberForPrinter($pID);
            $old        = self::getOldNumberForPrinter($pID);
            $highlight  = "";
            if ($used == 0) {
                $highlight = "tab_bg_1_2";
            }

            if (!$nohtml) {
                $out .= "<table  class='tab_format $highlight' width='100%'><tr><td>";
                $out .= __('Total') . "</td><td>$total";
                $out .= "</td><td colspan='2'></td><tr>";
                $out .= "<tr><td>";
                $out .= _nx('cartridge', 'Used', 'Used', $used);
                $out .= "</td><td>$used</span></td><td>";
                $out .= _nx('cartridge', 'Worn', 'Worn', $old);
                $out .= "</td><td>$old</span></td></tr></table>";
            } else {
               //TRANS : for display cartridges count : %1$d is the total number,
               //        %2$d the used one, %3$d the worn one
                $out .= sprintf(__('Total: %1$d (%2$d used, %3$d worn)'), $total, $used, $old);
            }
        } else {
            if (!$nohtml) {
                $out .= "<div class='tab_bg_1_2'><i>" . __('No cartridge') . "</i></div>";
            } else {
                $out .= __('No cartridge');
            }
        }
        return $out;
    }


    /**
     * Count the total number of cartridges for the cartridge item $tID.
     *
     * @param integer $tID ID of cartridge item.
     *
     * @return integer Number of cartridges counted.
     **/
    public static function getTotalNumber($tID)
    {
        global $DB;

        $row = $DB->request([
            'FROM'   => self::getTable(),
            'COUNT'  => 'cpt',
            'WHERE'  => ['cartridgeitems_id' => $tID]
        ])->current();
        return $row['cpt'];
    }


    /**
     * Count the number of cartridges used for the printer $pID
     *
     * @since 0.85
     *
     * @param integer $pID ID of the printer.
     *
     * @return integer Number of cartridges counted.
     **/
    public static function getTotalNumberForPrinter($pID)
    {
        global $DB;

        $row = $DB->request([
            'FROM'   => self::getTable(),
            'COUNT'  => 'cpt',
            'WHERE'  => ['printers_id' => $pID]
        ])->current();
        return (int)$row['cpt'];
    }


    /**
     * Count the number of used cartridges for the cartridge item $tID.
     *
     * @param integer $tID ID of the cartridge item.
     *
     * @return integer Number of used cartridges counted.
     **/
    public static function getUsedNumber($tID)
    {
        global $DB;

        $row = $DB->request([
            'SELECT' => ['id'],
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_cartridges',
            'WHERE'  => [
                'cartridgeitems_id'  => $tID,
                'date_out'           => null,
                'NOT'                => [
                    'date_use'  => null
                ]
            ]
        ])->current();
        return (int)$row['cpt'];
    }


    /**
     * Count the number of used cartridges used for the printer $pID.
     *
     * @since 0.85
     *
     * @param integer $pID ID of the printer.
     *
     * @return integer Number of used cartridge counted.
     **/
    public static function getUsedNumberForPrinter($pID)
    {
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'printers_id'  => $pID,
                'date_out'     => null,
                'NOT'          => ['date_use' => null]
            ]
        ])->current();
        return $result['cpt'];
    }


    /**
     * Count the number of old cartridges for the cartridge item $tID.
     *
     * @param integer $tID ID of the cartridge item.
     *
     * @return integer Number of old cartridges counted.
     **/
    public static function getOldNumber($tID)
    {
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'cartridgeitems_id'  => $tID,
                'NOT'                => ['date_out' => null]
            ]
        ])->current();
        return $result['cpt'];
    }


    /**
     * count how many old cartbridge for theprinter $pID
     *
     * @since 0.85
     *
     * @param $pID integer: printer identifier.
     *
     * @return integer : number of old cartridge counted.
     **/
    public static function getOldNumberForPrinter($pID)
    {
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'printers_id'  => $pID,
                'NOT'          => ['date_out' => null]
            ]
        ])->current();
        return $result['cpt'];
    }


    /**
     * count how many cartbridge unused for the cartridge item $tID
     *
     * @param $tID integer: cartridge item identifier.
     *
     * @return integer : number of cartridge unused counted.
     **/
    public static function getUnusedNumber($tID)
    {
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'cartridgeitems_id'  => $tID,
                'date_use'           => null
            ]
        ])->current();
        return $result['cpt'];
    }

    /**
     * The desired stock level
     *
     * This is used when the alarm threshold is reached to know how many to order.
     * @param integer $tID Cartridge item ID
     * @return integer
     */
    public static function getStockTarget(int $tID): int
    {
        global $DB;

        $it = $DB->request([
            'COUNT'  => 'stock_target',
            'FROM'   => CartridgeItem::getTable(),
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
     * @param integer $tID Cartridge item ID
     * @return integer
     */
    public static function getAlarmThreshold(int $tID): int
    {
        global $DB;

        $it = $DB->request([
            'COUNT'  => 'alarm_threshold',
            'FROM'   => CartridgeItem::getTable(),
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
     * Get the translated value for the status of a cartridge based on the use and out date (if any).
     *
     * @param string $date_use  Date of use (May be null or empty)
     * @param string $date_out  Date of delete (May be null or empty)
     *
     * @return string : Translated value for the cartridge status.
     **/
    public static function getStatus($date_use, $date_out)
    {

        if (is_null($date_use) || empty($date_use)) {
            return _nx('cartridge', 'New', 'New', 1);
        }
        if (is_null($date_out) || empty($date_out)) {
            return _nx('cartridge', 'Used', 'Used', 1);
        }
        return _nx('cartridge', 'Worn', 'Worn', 1);
    }


    /**
     * Print out the cartridges of a defined type
     *
     * @param CartridgeItem   $cartitem  The cartridge item
     * @param boolean|integer $show_old  Show old cartridges or not (default 0/false)
     *
     * @return boolean|void
     **/
    public static function showForCartridgeItem(CartridgeItem $cartitem, $show_old = 0)
    {
        global $DB;

        $tID = $cartitem->getField('id');
        if (!$cartitem->can($tID, READ)) {
            return false;
        }
        $canedit = $cartitem->can($tID, UPDATE);

        $where = ['glpi_cartridges.cartridgeitems_id' => $tID];
        $order = [
            'glpi_cartridges.date_use ASC',
            'glpi_cartridges.date_out DESC',
            'glpi_cartridges.date_in'
        ];

        if (!$show_old) { // NEW
            $where['glpi_cartridges.date_out'] = null;
            $order = [
                'glpi_cartridges.date_out ASC',
                'glpi_cartridges.date_use ASC',
                'glpi_cartridges.date_in'
            ];
        } else { //OLD
            $where['NOT'] = ['glpi_cartridges.date_out' => null];
        }

        $stock_time       = 0;
        $use_time         = 0;
        $pages_printed    = 0;
        $nb_pages_printed = 0;

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_cartridges.*',
                'glpi_printers.id AS printID',
                'glpi_printers.name AS printname',
                'glpi_printers.init_pages_counter'
            ],
            'FROM'   => self::gettable(),
            'LEFT JOIN' => [
                'glpi_printers'   => [
                    'FKEY'   => [
                        self::getTable()  => 'printers_id',
                        'glpi_printers'   => 'id'
                    ]
                ]
            ],
            'WHERE'     => $where,
            'ORDER'     => $order
        ]);

        $number = count($iterator);

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $actions = ['purge' => _x('button', 'Delete permanently'),
                'Infocom' . MassiveAction::CLASS_ACTION_SEPARATOR . 'activate'
                              => __('Enable the financial and administrative information')
            ];
            if (!$show_old) {
                $actions['Cartridge' . MassiveAction::CLASS_ACTION_SEPARATOR . 'backtostock']
                  = __('Back to stock');
            }
            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                'specific_actions' => $actions,
                'container'        => 'mass' . __CLASS__ . $rand,
                'rand'             => $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        if (!$show_old) {
            echo "<tr class='noHover'><th colspan='" . ($canedit ? '7' : '6') . "'>" .
               self::getCount($tID, -1) . "</th>";
            echo "</tr>";
        } else { // Old
            echo "<tr class='noHover'><th colspan='" . ($canedit ? '9' : '8') . "'>" . __('Worn cartridges');
            echo "</th></tr>";
        }

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';

        if ($canedit && $number) {
            $header_begin  .= "<th width='10'>";
            $header_top     = Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom  = Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_end    .= "</th>";
        }
        $header_end .= "<th>" . __('ID') . "</th>";
        $header_end .= "<th>" . _x('item', 'State') . "</th>";
        $header_end .= "<th>" . __('Add date') . "</th><th>" . __('Use date') . "</th>";
        $header_end .= "<th>" . __('Used on') . "</th>";

        if ($show_old) {
            $header_end .= "<th>" . __('End date') . "</th>";
            $header_end .= "<th>" . __('Printer counter') . "</th>";
        }

        $header_end .= "<th width='18%'>" . __('Financial and administrative information') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        $pages = [];

        if ($number) {
            foreach ($iterator as $data) {
                $date_in  = Html::convDate($data["date_in"]);
                $date_use = Html::convDate($data["date_use"]);
                $date_out = Html::convDate($data["date_out"]);
                $printer  = $data["printers_id"];

                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td>" . $data['id'] . '</td>';
                echo "<td class='center'>" . self::getStatus($data["date_use"], $data["date_out"]);
                echo "</td><td class='center'>" . $date_in . "</td>";
                echo "<td class='center'>" . $date_use . "</td>";
                echo "<td class='center'>";
                if (!is_null($date_use)) {
                    if ($data["printID"] > 0) {
                        $printname = $data["printname"];
                        if ($_SESSION['glpiis_ids_visible'] || empty($printname)) {
                            $printname = sprintf(__('%1$s (%2$s)'), $printname, $data["printID"]);
                        }
                        echo "<a href='" . Printer::getFormURLWithID($data["printID"]) . "'><span class='b'>" . $printname . "</span></a>";
                    } else {
                        echo NOT_AVAILABLE;
                    }
                    $tmp_dbeg       = explode("-", $data["date_in"]);
                    $tmp_dend       = explode("-", $data["date_use"]);
                    $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                                 - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
                    $stock_time    += $stock_time_tmp;
                }
                if ($show_old) {
                    echo "</td><td class='center'>";
                    echo $date_out;
                    $tmp_dbeg      = explode("-", $data["date_use"]);
                    $tmp_dend      = explode("-", $data["date_out"]);
                    $use_time_tmp  = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                                 - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
                    $use_time     += $use_time_tmp;
                }

                echo "</td>";
                if ($show_old) {
                   // Get initial counter page
                    if (!isset($pages[$printer])) {
                        $pages[$printer] = $data['init_pages_counter'];
                    }
                    echo "<td class='center'>";
                    if ($pages[$printer] < $data['pages']) {
                        $pages_printed   += $data['pages'] - $pages[$printer];
                        $nb_pages_printed++;
                        $pp               = $data['pages'] - $pages[$printer];
                        printf(_n('%d printed page', '%d printed pages', $pp), $pp);
                        $pages[$printer]  = $data['pages'];
                    } else if ($data['pages'] != 0) {
                        echo "<span class='tab_bg_1_2'>" . __('Counter error') . "</span>";
                    }
                    echo "</td>";
                }
                echo "<td class='center'>";
                Infocom::showDisplayLink('Cartridge', $data["id"]);
                echo "</td>";
                echo "</tr>";
            }
            if (
                $show_old
                && ($number > 0)
            ) {
                if ($nb_pages_printed == 0) {
                    $nb_pages_printed = 1;
                }
                echo "<tr class='tab_bg_2'><td colspan='" . ($canedit ? '4' : '3') . "'>&nbsp;</td>";
                echo "<td class='center b'>" . __('Average time in stock') . "<br>";
                echo round($stock_time / $number / 60 / 60 / 24 / 30.5, 1) . " " . _n('month', 'months', 1) . "</td>";
                echo "<td>&nbsp;</td>";
                echo "<td class='center b'>" . __('Average time in use') . "<br>";
                echo round($use_time / $number / 60 / 60 / 24 / 30.5, 1) . " " . _n('month', 'months', 1) . "</td>";
                echo "<td class='center b'>" . __('Average number of printed pages') . "<br>";
                echo round($pages_printed / $nb_pages_printed) . "</td>";
                echo "<td colspan='" . ($canedit ? '3' : '1') . "'>&nbsp;</td></tr>";
            } else {
                echo $header_begin . $header_bottom . $header_end;
            }
        }

        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>\n\n";
    }


    /**
     * Print out a link to add directly a new cartridge from a cartridge item.
     *
     * @param $cartitem  CartridgeItem object
     *
     * @return boolean|void
     **/
    public static function showAddForm(CartridgeItem $cartitem)
    {

        $ID = $cartitem->getField('id');
        if (!$cartitem->can($ID, UPDATE)) {
            return false;
        }
        if ($ID > 0) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action=\"" . static::getFormURL() . "\">";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><td class='center tab_bg_2' width='20%'>";
            echo "<input type='hidden' name='cartridgeitems_id' value='$ID'>\n";
            Dropdown::showNumber('to_add', ['value' => 1,
                'min'   => 1,
                'max'   => 100
            ]);
            echo "</td><td>";
            echo " <input type='submit' name='add' value=\"" . __s('Add cartridges') . "\"
                class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
    }


    /**
     * Show installed cartridges
     *
     * @since 0.84 (before showInstalled)
     *
     * @param Printer         $printer Printer object
     * @param boolean|integer $old     Old cartridges or not? (default 0/false)
     *
     * @return boolean|void
     **/
    public static function showForPrinter(Printer $printer, $old = 0)
    {
        global $DB, $CFG_GLPI;

        $instID = $printer->getField('id');
        if (!self::canView()) {
            return false;
        }
        $canedit = Session::haveRight("cartridge", UPDATE);
        $rand    = mt_rand();

        $where = ['glpi_cartridges.printers_id' => $instID];
        if ($old) {
            $where['NOT'] = ['glpi_cartridges.date_out' => null];
        } else {
            $where['glpi_cartridges.date_out'] = null;
        }
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_cartridgeitems.id AS tID',
                'glpi_cartridgeitems.is_deleted',
                'glpi_cartridgeitems.ref AS ref',
                'glpi_cartridgeitems.name AS type',
                'glpi_cartridges.id',
                'glpi_cartridges.pages AS pages',
                'glpi_cartridges.date_use AS date_use',
                'glpi_cartridges.date_out AS date_out',
                'glpi_cartridges.date_in AS date_in',
                'glpi_cartridgeitemtypes.name AS typename'
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                'glpi_cartridgeitems'      => [
                    'FKEY'   => [
                        self::getTable()        => 'cartridgeitems_id',
                        'glpi_cartridgeitems'   => 'id'
                    ]
                ],
                'glpi_cartridgeitemtypes'  => [
                    'FKEY'   => [
                        'glpi_cartridgeitems'      => 'cartridgeitemtypes_id',
                        'glpi_cartridgeitemtypes'  => 'id'
                    ]
                ]
            ],
            'WHERE'     => $where,
            'ORDER'     => [
                'glpi_cartridges.date_out ASC',
                'glpi_cartridges.date_use DESC',
                'glpi_cartridges.date_in',
            ]
        ]);

        $number = count($iterator);

        if ($canedit && !$old) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action=\"" . static::getFormURL() . "\">";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><td class='center tab_bg_2' width='50%'>";
            echo "<input type='hidden' name='printers_id' value='$instID'>\n";
            if (CartridgeItem::dropdownForPrinter($printer)) {
                //TRANS : multiplier
                echo "</td><td>" . __('x') . "&nbsp;";
                Dropdown::showNumber("nbcart", ['value' => 1,
                    'min'   => 1,
                    'max'   => 5
                ]);
                echo "</td><td><input type='submit' name='install' value=\"" . _sx('button', 'Install') . "\"
                                  class='btn btn-primary'>";
            } else {
                echo __('No cartridge available');
            }

            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div id='viewcartridge$rand'></div>";

        $pages = $printer->fields['init_pages_counter'];
        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            if (!$old) {
                $actions = [__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'uninstall'
                                       => __('End of life'),
                    __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'backtostock'
                                       => __('Back to stock')
                ];
            } else {
                $actions = [__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'updatepages'
                                      => __('Update printer counter'),
                    'purge' => _x('button', 'Delete permanently')
                ];
            }
            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                'specific_actions' => $actions,
                'container'        => 'mass' . __CLASS__ . $rand,
                'rand'             => $rand,
                'extraparams'      => ['maxpages'
                                                       => $printer->fields['last_pages_counter']
                ]
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'>";
        if ($old == 0) {
            echo "<th colspan='" . ($canedit ? '6' : '5') . "'>" . __('Used cartridges') . "</th>";
        } else {
            echo "<th colspan='" . ($canedit ? '9' : '8') . "'>" . __('Worn cartridges') . "</th>";
        }
        echo "</tr>";

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_end    = '';

        if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_end    .= "</th>";
        }
        $header_end .= "<th>" . __('ID') . "</th><th>" . _n('Cartridge model', 'Cartridge models', 1) . "</th>";
        $header_end .= "<th>" . _n('Cartridge type', 'Cartridge types', 1) . "</th>";
        $header_end .= "<th>" . __('Add date') . "</th>";
        $header_end .= "<th>" . __('Use date') . "</th>";
        if ($old != 0) {
            $header_end .= "<th>" . __('End date') . "</th>";
            $header_end .= "<th>" . __('Printer counter') . "</th>";
            $header_end .= "<th>" . __('Printed pages') . "</th>";
        }
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        $stock_time       = 0;
        $use_time         = 0;
        $pages_printed    = 0;
        $nb_pages_printed = 0;

        foreach ($iterator as $data) {
            $cart_id    = $data["id"];
            $typename   = $data["typename"];
            $date_in    = Html::convDate($data["date_in"]);
            $date_use   = Html::convDate($data["date_use"]);
            $date_out   = Html::convDate($data["date_out"]);
            $viewitemjs = ($canedit ? "style='cursor:pointer' onClick=\"viewEditCartridge" . $cart_id .
                        "$rand();\"" : '');
            echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
            if ($canedit) {
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $cart_id);
                echo "</td>";
            }
            echo "<td class='center' $viewitemjs>";
            if ($canedit) {
                echo "\n<script type='text/javascript' >\n";
                echo "function viewEditCartridge" . $cart_id . "$rand() {\n";
                $params = ['type'        => __CLASS__,
                    'parenttype'  => 'Printer',
                    'printers_id' => $printer->fields["id"],
                    'id'          => $cart_id
                ];
                Ajax::updateItemJsCode(
                    "viewcartridge$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    $params
                );
                echo "};";
                echo "</script>\n";
            }
            echo $data["id"] . "</td>";
            echo "<td class='center' $viewitemjs>";
            echo "<a href=\"" . CartridgeItem::getFormURLWithID($data["tID"]) . "\">";
            printf(__('%1$s - %2$s'), $data["type"], $data["ref"]);
            echo "</a></td>";
            echo "<td class='center' $viewitemjs>" . $typename . "</td>";
            echo "<td class='center' $viewitemjs>" . $date_in . "</td>";
            echo "<td class='center' $viewitemjs>" . $date_use . "</td>";

            $tmp_dbeg       = explode("-", $data["date_in"]);
            $tmp_dend       = explode("-", $data["date_use"]);

            $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                           - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
            $stock_time    += $stock_time_tmp;
            if ($old != 0) {
                echo "<td class='center' $viewitemjs>" . $date_out;

                $tmp_dbeg      = explode("-", $data["date_use"]);
                $tmp_dend      = explode("-", $data["date_out"]);

                $use_time_tmp  = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0])
                              - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
                $use_time     += $use_time_tmp;

                echo "</td><td class='numeric' $viewitemjs>" . $data['pages'] . "</td>";
                echo "<td class='numeric' $viewitemjs>";

                if ($pages < $data['pages']) {
                    $pages_printed   += $data['pages'] - $pages;
                    $nb_pages_printed++;
                    $pp               = $data['pages'] - $pages;
                    echo $pp;
                    $pages            = $data['pages'];
                } else {
                    echo "&nbsp;";
                }
                echo "</td>";
            }
            echo "</tr>";
        }

        if ($old) { // Print average
            if ($number > 0) {
                if ($nb_pages_printed == 0) {
                    $nb_pages_printed = 1;
                }
                echo "<tr class='tab_bg_2'><td colspan='" . ($canedit ? "4" : '3') . "'>&nbsp;</td>";
                echo "<td class='center b'>" . __('Average time in stock') . "<br>";
                $time_stock = round($stock_time / $number / 60 / 60 / 24 / 30.5, 1);
                echo sprintf(_n('%d month', '%d months', $time_stock), $time_stock) . "</td>";
                echo "<td class='center b'>" . __('Average time in use') . "<br>";
                $time_use = round($use_time / $number / 60 / 60 / 24 / 30.5, 1);
                echo sprintf(_n('%d month', '%d months', $time_use), $time_use) . "</td>";
                echo "<td class='center b' colspan='2'>" . __('Average number of printed pages') . "<br>";
                echo round($pages_printed / $nb_pages_printed) . "</td>";
                echo "</tr>";
            }
        }

        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>\n\n";
    }


    /**
     * Show form for Cartridge
     * @since 0.84
     *
     * @param integer $ID       Id of the cartridge
     * @param array   $options  Array of possible options:
     *     - parent Object : the printers where the cartridge is used
     *
     * @return boolean False if there was a rights issue. Otherwise, returns true.
     */
    public function showForm($ID, array $options = [])
    {

        if (isset($options['parent']) && !empty($options['parent'])) {
            $printer = $options['parent'];
        }
        if (!$this->getFromDB($ID)) {
            return false;
        }
        $printer = new Printer();
        $printer->check($this->getField('printers_id'), UPDATE);

        $cartitem = new CartridgeItem();
        $cartitem->getFromDB($this->getField('cartridgeitems_id'));

        $is_old  = !empty($this->fields['date_out']);
        $is_used = !empty($this->fields['date_use']);

        $options['colspan'] = 2;
        $options['candel']  = false; // Do not permit delete here
        $options['canedit'] = $is_used; // Do not permit edit if cart is not used
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Printer', 'Printers', 1) . "</td><td>";
        echo $printer->getLink();
        echo "<input type='hidden' name='printers_id' value='" . $this->getField('printers_id') . "'>\n";
        echo "<input type='hidden' name='cartridgeitems_id' value='" .
             $this->getField('cartridgeitems_id') . "'>\n";
        echo "</td>\n";
        echo "<td>" . _n('Cartridge model', 'Cartridge models', 1) . "</td>";
        echo "<td>" . $cartitem->getLink() . "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Add date') . "</td>";
        echo "<td>" . Html::convDate($this->fields["date_in"]) . "</td>";

        echo "<td>" . __('Use date') . "</td><td>";
        if ($is_used && !$is_old) {
            Html::showDateField("date_use", ['value'      => $this->fields["date_use"],
                'maybeempty' => false,
                'canedit'    => true,
                'min'        => $this->fields["date_in"]
            ]);
        } else {
            echo Html::convDate($this->fields["date_use"]);
        }
        echo "</td></tr>\n";

        if ($is_old) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('End date') . "</td><td>";
            Html::showDateField("date_out", ['value'      => $this->fields["date_out"],
                'maybeempty' => false,
                'canedit'    => true,
                'min'        => $this->fields["date_use"]
            ]);
            echo "</td>";
            echo "<td>" . __('Printer counter') . "</td><td>";
            echo "<input type='text' name='pages' value=\"" . $this->fields['pages'] . "\">";
            echo "</td></tr>\n";
        }
        $this->showFormButtons($options);

        return true;
    }


    /**
     * Get notification parameters by entity
     *
     * @param integer $entity The entity (default 0)
     * @return array Array of notification parameters
     */
    public static function getNotificationParameters($entity = 0)
    {
        global $DB, $CFG_GLPI;

       //Look for parameters for this entity
        $iterator = $DB->request([
            'SELECT' => ['cartridges_alert_repeat'],
            'FROM'   => 'glpi_entities',
            'WHERE'  => ['id' => $entity]
        ]);

        if (!count($iterator)) {
           //No specific parameters defined, taking global configuration params
            return $CFG_GLPI['cartridges_alert_repeat'];
        } else {
            $data = $iterator->current();
           //This entity uses global parameters -> return global config
            if ($data['cartridges_alert_repeat'] == -1) {
                return $CFG_GLPI['cartridges_alert_repeat'];
            }
           // ELSE Special configuration for this entity
            return $data['cartridges_alert_repeat'];
        }
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate && self::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Printer':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForPrinter($item);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

                case 'CartridgeItem':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForCartridgeItem($item);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    /**
     * Count the number of cartridges associated with the given cartridge item.
     * @param CartridgeItem $item CartridgeItem object
     * @return integer
     */
    public static function countForCartridgeItem(CartridgeItem $item)
    {

        return countElementsInTable(['glpi_cartridges'], ['glpi_cartridges.cartridgeitems_id' => $item->getField('id')]);
    }


    /**
     * Count the number of cartridges associated with the given printer.
     * @param Printer $item Printer object
     * @return integer
     */
    public static function countForPrinter(Printer $item)
    {

        return countElementsInTable(['glpi_cartridges'], ['glpi_cartridges.printers_id' => $item->getField('id')]);
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Printer':
                $info = new Printer_CartridgeInfo();
                $info->showForPrinter($item);
                self::showForPrinter($item);
                self::showForPrinter($item, 1);
                return true;

            case 'CartridgeItem':
                self::showAddForm($item);
                self::showForCartridgeItem($item);
                self::showForCartridgeItem($item, 1);
                return true;
        }
    }

    public function getRights($interface = 'central')
    {
        $ci = new CartridgeItem();
        return $ci->getRights($interface);
    }


    public static function getIcon()
    {
        return "ti ti-droplet-filled-2";
    }
}
