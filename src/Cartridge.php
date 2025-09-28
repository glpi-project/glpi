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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\Clonable;

use function Safe\mktime;

/**
 * Cartridge class.
 * This class is used to manage printer cartridges.
 * @see CartridgeItem
 * @author Julien Dombre
 **/
class Cartridge extends CommonDBRelation
{
    use Clonable;

    // From CommonDBTM
    protected static $forward_entity_to = ['Infocom'];
    public $dohistory                   = true;
    public $no_form_page                = true;

    public static $rightname = 'cartridge';

    public static $itemtype_1 = 'CartridgeItem';
    public static $items_id_1 = 'cartridgeitems_id';

    public static $itemtype_2 = 'Printer';
    public static $items_id_2 = 'printers_id';
    public static $mustBeAttached_2 = false;

    public function getCloneRelations(): array
    {
        return [
            Infocom::class,
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
                $maxpages = isset($input['maxpages']) ? (int) $input['maxpages'] : '';

                echo "<input type='text' name='pages' value=\"" . $maxpages . "\" size='6'>";
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

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public function prepareInputForAdd($input)
    {
        $item = static::getItemFromArray(CartridgeItem::class, CartridgeItem::getForeignKeyField(), $input);
        if ($item === false) {
            return false;
        }

        return [
            "cartridgeitems_id" => $item->fields["id"],
            "entities_id"       => $item->getEntityID(),
            "date_in"           => date("Y-m-d"),
        ];
    }

    public function post_addItem()
    {
        // inherit infocom
        $infocoms = Infocom::getItemsAssociatedTo(CartridgeItem::class, $this->fields[CartridgeItem::getForeignKeyField()]);
        if (count($infocoms)) {
            $infocom = reset($infocoms);
            $infocom->clone([
                'itemtype'  => self::class,
                'items_id'  => $this->getID(),
            ]);
        }

        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        if (in_array('pages', $this->updates, true)) {
            $printer = new Printer();
            if (
                $printer->getFromDB($this->fields['printers_id'])
                && (($this->fields['pages'] > $printer->getField('last_pages_counter'))
                    || ($this->oldvalues['pages'] == $printer->getField('last_pages_counter')))
            ) {
                $printer->update([
                    'id' => $printer->getID(),
                    'last_pages_counter' => $this->fields['pages'],
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
        /** @var Cartridge $item */
        switch ($ma->getAction()) {
            case 'uninstall':
                foreach ($ids as $key) {
                    if ($item->can($key, UPDATE)) {
                        if ($item->uninstall($key)) {
                            $ma->itemDone($item::class, $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item::class, $key, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item::class, $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'backtostock':
                foreach ($ids as $id) {
                    if ($item->can($id, UPDATE)) {
                        if ($item->backToStock(["id" => $id])) {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item::class, $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
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
                                    'pages' => $input['pages'],
                                ])
                            ) {
                                $ma->itemDone($item::class, $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item::class, $key, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item::class, $key, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item::class, $ids, MassiveAction::ACTION_KO);
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
     * @param boolean $history
     * @return bool
     */
    public function backToStock(array $input, $history = true)
    {
        global $DB;

        $result = $DB->update(
            static::getTable(),
            [
                'date_out'     => 'NULL',
                'date_use'     => 'NULL',
                'printers_id'  => 0,
            ],
            [
                'id' => $input['id'],
            ]
        );
        return $result && ($DB->affectedRows() > 0);
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
            'FROM'   => static::getTable(),
            'WHERE'  => [
                'cartridgeitems_id'  => $tID,
                'date_use'           => null,
            ],
            'LIMIT'  => 1,
        ]);

        if (count($iterator)) {
            $result = $iterator->current();
            $cID = $result['id'];
            // Update cartridge taking care of multiple insertion
            $result = $DB->update(
                static::getTable(),
                [
                    'date_use'     => date('Y-m-d'),
                    'printers_id'  => $pID,
                ],
                [
                    'id'        => $cID,
                    'date_use'  => null,
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
            Session::addMessageAfterRedirect(__s('No free cartridge'), false, ERROR);
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
                static::getTable(),
                [
                    'date_out'  => date('Y-m-d'),
                ] + $toadd,
                [
                    'id'  => $ID,
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
        }
        return false;
    }

    /**
     * Print the cartridge count HTML array for the cartridge item $tID
     *
     * @param integer         $tID      ID of the cartridge item
     * @param integer         $alarm_threshold Alarm threshold value
     * @param integer|boolean $nohtml          True if the return value should be without HTML tags (default 0/false).
     *                                         The return value will anyway be a safe HTML string.
     *
     * @return string String to display
     **/
    public static function getCount($tID, $alarm_threshold, $nohtml = 0)
    {
        // Get total
        $total = self::getTotalNumber($tID);
        $out   = "";
        if ($total !== 0) {
            $unused     = self::getUnusedNumber($tID);
            $used       = self::getUsedNumber($tID);
            $old        = self::getOldNumber($tID);
            $highlight  = $unused <= $alarm_threshold;

            $counts = [
                'new' => [
                    'label' => _nx('cartridge', 'New', 'New', $unused),
                    'value' => $unused,
                ],
                'used' => [
                    'label' => _nx('cartridge', 'Used', 'Used', $used),
                    'value' => $used,
                ],
                'worn' => [
                    'label' => _nx('cartridge', 'Worn', 'Worn', $old),
                    'value' => $old,
                ],
                'total' => [
                    'label' => __('Total'),
                    'value' => $total,
                ],
            ];

            if (!$nohtml) {
                // language=Twig
                $out .= TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    <table class="table table-sm table-borderless {{ highlight ? 'table-danger' : '' }}">
                        <tr>
                            <td>{{ counts['total']['label'] }}</td>
                            <td>{{ counts['total']['value'] }}</td>
                            <td class="fw-bold">{{ counts['new']['label'] }}</td>
                            <td class="fw-bold">{{ counts['new']['value'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ counts['used']['label'] }}</td>
                            <td>{{ counts['used']['value'] }}</td>
                            <td>{{ counts['worn']['label'] }}</td>
                            <td>{{ counts['worn']['value'] }}</td>
                        </tr>
                    </table>
TWIG, ['counts' => $counts, 'highlight' => $highlight]);
            } else {
                //TRANS : for display cartridges count : %1$d is the total number,
                //        %2$d the new one, %3$d the used one, %4$d worn one
                $out .= htmlescape(
                    sprintf(
                        __('Total: %1$d (%2$d new, %3$d used, %4$d worn)'),
                        $total,
                        $unused,
                        $used,
                        $old
                    )
                );
            }
        } else {
            if (!$nohtml) {
                $out .= "<div class='bg-danger-lt fst-italic'>" . __s('No cartridge') . "</div>";
            } else {
                $out .= __s('No cartridge');
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
     * @param integer|boolean $nohtml True if the return value should be without HTML tags (default 0/false).
     *                                The return value will anyway be a safe HTML string.
     *
     * @return string String to display
     **/
    public static function getCountForPrinter($pID, $nohtml = 0)
    {
        // Get total
        $total = self::getTotalNumberForPrinter($pID);
        $out   = "";
        if ($total !== 0) {
            $used       = self::getUsedNumberForPrinter($pID);
            $old        = self::getOldNumberForPrinter($pID);
            $highlight  = $used === 0;

            $counts = [
                'used' => [
                    'label' => _nx('cartridge', 'Used', 'Used', $used),
                    'value' => $used,
                ],
                'worn' => [
                    'label' => _nx('cartridge', 'Worn', 'Worn', $old),
                    'value' => $old,
                ],
                'total' => [
                    'label' => __('Total'),
                    'value' => $total,
                ],
            ];

            if (!$nohtml) {
                // language=Twig
                $out .= TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    <table class="table table-sm table-borderless {{ highlight ? 'table-danger' : '' }}">
                        <tr>
                            <td>{{ counts['total']['label'] }}</td>
                            <td>{{ counts['total']['value'] }}</td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td>{{ counts['used']['label'] }}</td>
                            <td>{{ counts['used']['value'] }}</td>
                            <td>{{ counts['worn']['label'] }}</td>
                            <td>{{ counts['worn']['value'] }}</td>
                        </tr>
                    </table>
TWIG, ['counts' => $counts, 'highlight' => $highlight]);
            } else {
                //TRANS : for display cartridges count : %1$d is the total number,
                //        %2$d the used one, %3$d the worn one
                $out .= htmlescape(sprintf(__('Total: %1$d (%2$d used, %3$d worn)'), $total, $used, $old));
            }
        } else {
            if (!$nohtml) {
                $out .= "<div class='bg-danger-lt fst-italic'>" . __s('No cartridge') . "</div>";
            } else {
                $out .= __s('No cartridge');
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
            'WHERE'  => ['cartridgeitems_id' => $tID],
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
            'WHERE'  => ['printers_id' => $pID],
        ])->current();
        return (int) $row['cpt'];
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
                    'date_use'  => null,
                ],
            ],
        ])->current();
        return (int) $row['cpt'];
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
                'NOT'          => ['date_use' => null],
            ],
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
                'NOT'                => ['date_out' => null],
            ],
        ])->current();
        return $result['cpt'];
    }

    /**
     * count how many old cartbridge for theprinter $pID
     *
     * @since 0.85
     *
     * @param integer $pID printer identifier.
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
                'NOT'          => ['date_out' => null],
            ],
        ])->current();
        return $result['cpt'];
    }

    /**
     * count how many cartridge unused for the cartridge item $tID
     *
     * @param integer $tID cartridge item identifier.
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
                'date_use'           => null,
            ],
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
            'SELECT'  => ['stock_target'],
            'FROM'   => CartridgeItem::getTable(),
            'WHERE'  => [
                'id'  => $tID,
            ],
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
            'SELECT'  => ['alarm_threshold'],
            'FROM'   => CartridgeItem::getTable(),
            'WHERE'  => [
                'id'  => $tID,
            ],
        ]);
        return $it->count() ? $it->current()['alarm_threshold'] : 0;
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
        if (empty($date_use)) {
            return _nx('cartridge', 'New', 'New', 1);
        }
        if (empty($date_out)) {
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
        $start = (int) ($_GET["start"] ?? 0);

        $canedit = $cartitem->can($tID, UPDATE);

        $where = ['glpi_cartridges.cartridgeitems_id' => $tID];
        $order = [
            'glpi_cartridges.date_use ASC',
            'glpi_cartridges.date_out DESC',
            'glpi_cartridges.date_in',
        ];

        if (!$show_old) { // NEW
            $where['glpi_cartridges.date_out'] = null;
            $order = [
                'glpi_cartridges.date_out ASC',
                'glpi_cartridges.date_use ASC',
                'glpi_cartridges.date_in',
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
                'glpi_printers.init_pages_counter',
            ],
            'FROM'   => self::getTable(),
            'LEFT JOIN' => [
                'glpi_printers'   => [
                    'FKEY'   => [
                        self::getTable()  => 'printers_id',
                        'glpi_printers'   => 'id',
                    ],
                ],
            ],
            'WHERE'     => $where,
            'ORDER'     => $order,
            'START'     => (int) $start,
            'LIMIT'     => (int) $_SESSION['glpilist_limit'],
        ]);

        $number = count($iterator);

        $rand = mt_rand();

        // Display the pager
        $actions = [];
        if ($canedit && $number) {
            $actions = [
                'purge' => _x('button', 'Delete permanently'),
                'Infocom' . MassiveAction::CLASS_ACTION_SEPARATOR . 'activate' => __s('Enable the financial and administrative information'),
            ];
            if (!$show_old) {
                $actions['Cartridge' . MassiveAction::CLASS_ACTION_SEPARATOR . 'backtostock'] = __s('Back to stock');
            }
        }
        $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
            'specific_actions' => $actions,
            'container'        => 'mass' . self::class . $rand,
            'rand'             => $rand,
        ];

        $pages = [];

        $entries = [];
        foreach ($iterator as $data) {
            $printer  = $data["printers_id"];

            $printer_link = '';
            if (!is_null($data["date_use"])) {
                if ($data["printID"] > 0) {
                    $printname = $data["printname"];
                    if ($_SESSION['glpiis_ids_visible'] || empty($printname)) {
                        $printname = sprintf(__('%1$s (%2$s)'), $printname, $data["printID"]);
                    }
                    $printer_link = "<a href='" . htmlescape(Printer::getFormURLWithID($data["printID"])) . "'><span class='fw-bold'>" . htmlescape($printname) . "</span></a>";
                } else {
                    $printer_link = htmlescape(NOT_AVAILABLE);
                }
                $tmp_dbeg       = explode("-", $data["date_in"]);
                $tmp_dend       = explode("-", $data["date_use"]);
                $stock_time_tmp = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                             - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                $stock_time    += $stock_time_tmp;
            }
            if ($show_old) {
                $tmp_dbeg      = explode("-", $data["date_use"]);
                $tmp_dend      = explode("-", $data["date_out"]);
                $use_time_tmp  = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                             - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                $use_time     += $use_time_tmp;
            }

            if ($show_old) {
                // Get initial counter page
                if (!isset($pages[$printer])) {
                    $pages[$printer] = $data['init_pages_counter'];
                }
                if ($pages[$printer] < $data['pages']) {
                    $pages_printed   += $data['pages'] - $pages[$printer];
                    $nb_pages_printed++;
                    $pp               = $data['pages'] - $pages[$printer];
                    $pages[$printer]  = $data['pages'];
                }
            }
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'state'    => self::getStatus($data["date_use"], $data["date_out"]),
                'used_on'  => $printer_link,
                'date_in' => $data['date_in'],
                'date_use' => $data['date_use'],
                'date_out' => $data['date_out'],
                'printer_counter' => $pp ?? 0,
                'infocom' => Infocom::showDisplayLink('Cartridge', $data["id"], false),
            ];
        }

        $footers = [];
        if (
            $show_old
            && ($number > 0)
        ) {
            if ($nb_pages_printed === 0) {
                $nb_pages_printed = 1;
            }
            $time_stock = round($stock_time / $number / 60 / 60 / 24 / 30.5);
            $avg_stock = __('Average time in stock') . "\n" . $time_stock . " " . _n('month', 'months', (int) $time_stock);
            $time_use = round($use_time / $number / 60 / 60 / 24 / 30.5);
            $avg_use = __('Average time in use') . "\n" . $time_use . " " . _n('month', 'months', (int) $time_use);
            $avg_pages = __('Average number of printed pages') . "\n" . round($pages_printed / max($nb_pages_printed, 1));
            $footers = [['', '', '', $avg_stock, '', $avg_use, $avg_pages]];
        }

        $columns = [
            'id' => __('ID'),
            'state' => _x('item', 'State'),
            'date_in' => __('Add date'),
            'date_use' => __('Use date'),
            'used_on' => __('Used on'),
        ];
        if ($show_old) {
            $columns['date_out'] = __('End date');
            $columns['printer_counter'] = __('Printer counter');
        }
        $columns['infocom'] = __('Financial and administrative information');

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => $show_old ? __('Worn cartridges') : __('Used cartridges'),
            'columns' => $columns,
            'formatters' => [
                'used_on' => 'raw_html',
                'date_add' => 'date',
                'date_use' => 'date',
                'date_out' => 'date',
                'printer_counter' => 'integer',
                'infocom' => 'raw_html',
            ],
            'entries' => $entries,
            'footers' => $footers,
            'footer_class' => 'fw-bold',
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ] + $massiveactionparams,
        ]);
    }

    /**
     * Print out a link to add directly a new cartridge from a cartridge item.
     *
     * @param CartridgeItem $cartitem
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
            $twig_params = [
                'add_label' => __('Add cartridges'),
                'cartridgeitems_id' => $ID,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Cartridge'|itemtype_form_path }}"/>
                        <div class="d-flex row">
                            {{ fields.numberField('to_add', 1, null, {
                                min: 1,
                                max: 100,
                                field_class: 'col-4',
                            }) }}
                            {% set btn %}
                                <button type="submit" name="add" class="btn btn-primary">{{ add_label }}</button>
                                <input type="hidden" name="cartridgeitems_id" value="{{ cartridgeitems_id }}">
                                <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {% endset %}
                            {{ fields.htmlField('', btn, null, {
                                no_label: true,
                                field_class: 'col-4',
                                mb: 'mb-2'
                            }) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
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
        global $DB;

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
                'glpi_cartridgeitemtypes.name AS typename',
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                'glpi_cartridgeitems'      => [
                    'FKEY'   => [
                        self::getTable()        => 'cartridgeitems_id',
                        'glpi_cartridgeitems'   => 'id',
                    ],
                ],
                'glpi_cartridgeitemtypes'  => [
                    'FKEY'   => [
                        'glpi_cartridgeitems'      => 'cartridgeitemtypes_id',
                        'glpi_cartridgeitemtypes'  => 'id',
                    ],
                ],
            ],
            'WHERE'     => $where,
            'ORDER'     => [
                'glpi_cartridges.date_out ASC',
                'glpi_cartridges.date_use DESC',
                'glpi_cartridges.date_in',
            ],
        ]);

        $number = count($iterator);

        if ($canedit && !$old) {
            $twig_params = [
                'printer' => $printer,
                'install_label' => _x('button', 'Install'),
                'count_label' => __('Count'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Cartridge'|itemtype_form_path }}"/>
                        <div class="d-flex row">
                            {% set has_cartridges = false %}
                            {% set dropdown %}
                                {% set has_cartridges = call('CartridgeItem::dropdownForPrinter', [printer]) %}
                            {% endset %}
                            {% if has_cartridges %}
                                {{ fields.htmlField('', dropdown, null, {
                                    field_class: 'col-4',
                                }) }}
                                {{ fields.numberField('nbcart', 1, count_label, {
                                    min: 1,
                                    max: 5,
                                    field_class: 'col-4',
                                }) }}
                                {% set btn_install %}
                                    <input type="hidden" name="printers_id" value="{{ printer.getID() }}">
                                    <input type="submit" name="install" value="{{ install_label }}" class="btn btn-primary">
                                    <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                                {% endset %}
                                {{ fields.htmlField('', btn_install, null, {
                                    no_label: true,
                                    field_class: 'col-4',
                                    mb: 'mb-2'
                                }) }}
                            {% endif %}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div id="viewcartridge"></div>
            <script>
                function viewEditCartridge(cart_id) {
                    $('#viewcartridge').load(
                        '{{ path('ajax/viewsubitem.php') }}',
                        {
                            type: 'Cartridge',
                            parenttype: 'Printer',
                            printers_id: {{ printer_id }},
                            id: cart_id
                        }
                    );
                }
                $('tr[data-itemtype="Cartridge"]').on('click', function() {
                    viewEditCartridge($(this).data('id'));
                });
            </script>
TWIG, ['printer_id' => $printer->getID()]);

        $pages = $printer->fields['init_pages_counter'];
        if (!$old) {
            $actions = [
                self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'uninstall' => __s('End of life'),
                self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'backtostock' => __s('Back to stock'),
            ];
        } else {
            $actions = [
                self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'updatepages' => __s('Update printer counter'),
                'purge' => _sx('button', 'Delete permanently'),
            ];
        }
        $massiveactionparams = [
            'specific_actions' => $actions,
            'rand'             => $rand,
            'extraparams'      => [
                'maxpages' => $printer->fields['last_pages_counter'],
            ],
        ];

        $stock_time       = 0;
        $use_time         = 0;
        $pages_printed    = 0;
        $nb_pages_printed = 0;

        $entries = [];
        foreach ($iterator as $data) {
            $model = '<a href="' . htmlescape(CartridgeItem::getFormURLWithID($data["tID"])) . '">'
                . htmlescape(sprintf(__('%1$s - %2$s'), $data["type"], $data["ref"]))
                . '</a>';

            $tmp_dbeg       = explode("-", $data["date_in"]);
            $tmp_dend       = explode("-", $data["date_use"]);

            $stock_time_tmp = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                           - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
            $stock_time    += $stock_time_tmp;
            if ($old) {
                $tmp_dbeg      = explode("-", $data["date_use"]);
                $tmp_dend      = explode("-", $data["date_out"]);
                $use_time_tmp  = mktime(0, 0, 0, (int) $tmp_dend[1], (int) $tmp_dend[2], (int) $tmp_dend[0])
                              - mktime(0, 0, 0, (int) $tmp_dbeg[1], (int) $tmp_dbeg[2], (int) $tmp_dbeg[0]);
                $use_time     += $use_time_tmp;

                if ($pages < $data['pages']) {
                    $pages_printed   += $data['pages'] - $pages;
                    $nb_pages_printed++;
                    $pp               = $data['pages'] - $pages;
                    $pages            = $data['pages'];
                }
            }
            $entries[] = [
                'row_class' => $data["is_deleted"] ? 'table-danger cursor-pointer' : 'cursor-pointer',
                'itemtype' => self::class,
                'id'       => $data['id'],
                'model'     => $model,
                'type'     => $data["typename"],
                'date_add' => $data['date_in'],
                'date_use' => $data['date_use'],
                'date_out' => $data['date_out'],
                'pages'    => $data['pages'],
                'pages_printed' => $pp ?? 0,
            ];
        }

        $columns = [
            'id' => __('ID'),
            'model' => _n('Cartridge model', 'Cartridge models', 1),
            'type' => _n('Cartridge type', 'Cartridge types', 1),
            'date_add' => __('Add date'),
            'date_use' => __('Use date'),
        ];
        $footers = [];

        if ($old) {
            $columns['date_out'] = __('End date');
            $columns['pages'] = __('Printer counter');
            $columns['pages_printed'] = __('Printed pages');

            if ($number > 0) {
                $time_stock = round($stock_time / $number / 60 / 60 / 24 / 30.5);
                $avg_stock = __('Average time in stock') . "\n" . $time_stock . " " . _n('month', 'months', (int) $time_stock);
                $time_use = round($use_time / $number / 60 / 60 / 24 / 30.5);
                $avg_use = __('Average time in use') . "\n" . $time_use . " " . _n('month', 'months', (int) $time_use);
                $avg_pages = __('Average number of printed pages') . "\n" . round($pages_printed / max($nb_pages_printed, 1));
                $footers = [['', '', '', $avg_stock, $avg_use, '', '', $avg_pages]];
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => $old ? __('Worn cartridges') : __('Used cartridges'),
            'columns' => $columns,
            'formatters' => [
                'model' => 'raw_html',
                'date_add' => 'date',
                'date_use' => 'date',
                'date_out' => 'date',
                'pages' => 'integer',
                'pages_printed' => 'integer',
            ],
            'entries' => $entries,
            'footers' => $footers,
            'footer_class' => 'fw-bold',
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ] + $massiveactionparams,
        ]);
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
        $printer = new Printer();
        if (!empty($options['parent'])) {
            $printer = $options['parent'];
        }
        if (!$this->getFromDB($ID)) {
            return false;
        }

        $printer->check($this->getField('printers_id'), UPDATE);

        $cartitem = new CartridgeItem();
        $cartitem->getFromDB($this->getField('cartridgeitems_id'));

        TemplateRenderer::getInstance()->display('pages/assets/cartridge.html.twig', [
            'item' => $this,
            'printer' => $printer,
            'model' => $cartitem,
            'params' => [
                'canedit' => !empty($this->fields['date_use']),
                'candel' => false,
                'formfooter' => false,
            ],
        ]);
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && self::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Printer::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForPrinter($item);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

                case CartridgeItem::class:
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
        switch ($item::class) {
            case Printer::class:
                $info = new Printer_CartridgeInfo();
                $info->showForPrinter($item);
                self::showForPrinter($item);
                self::showForPrinter($item, 1);
                break;

            case CartridgeItem::class:
                self::showAddForm($item);
                self::showForCartridgeItem($item);
                self::showForCartridgeItem($item, 1);
                break;
        }
        return true;
    }

    public function getRights($interface = 'central')
    {
        return (new CartridgeItem())->getRights($interface);
    }

    public static function getIcon()
    {
        return "ti ti-droplet-half-2-filled";
    }
}
