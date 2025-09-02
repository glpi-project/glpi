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
use Glpi\DBAL\QuerySubQuery;
use Glpi\Event;
use Glpi\Features\Clonable;

use function Safe\ob_get_clean;
use function Safe\ob_start;

//!  Consumable Class
/**
 * This class is used to manage the consumables.
 * @see ConsumableItem
 * @author Julien Dombre
 **/
class Consumable extends CommonDBChild
{
    use Clonable;

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
            Infocom::class,
        ];
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'ObjectLock:unlock';
        $forbidden[] = 'add_note';
        $forbidden[] = 'add_transfer_list';

        // Despite using the Clonable trait, the 'clone' option was not available
        // in the massive actions defined by the old Consumable::showForConsumableItem()
        // method.
        // To keep things consistent, clone is blacklisted here.
        $forbidden[] = 'clone';

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
                "date_in"            => date("Y-m-d"),
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
                'items_id'  => $this->getID(),
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
        global $DB;

        $result = $DB->update(
            static::getTable(),
            [
                'date_out' => 'NULL',
            ],
            [
                'id' => $input['id'],
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
        global $DB;

        if (
            !empty($itemtype)
            && ($items_id > 0)
        ) {
            $result = $DB->update(
                static::getTable(),
                [
                    'date_out'  => date('Y-m-d'),
                    'itemtype'  => $itemtype,
                    'items_id'  => $items_id,
                ],
                [
                    'id' => $ID,
                ]
            );
            if ($result) {
                return true;
            }
        }
        return false;
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {
        // Special actions only for self
        if ($itemtype !== static::class) {
            return;
        }

        $action_prefix = self::getType() . MassiveAction::CLASS_ACTION_SEPARATOR;
        $actions[$action_prefix . 'backtostock'] = __s('Back to stock');
        $actions[$action_prefix . 'give'] = _sx('button', 'Give');
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;

        $input = $ma->getInput();
        switch ($ma->getAction()) {
            case 'give':
                // Retrieve entity restrict from consumable item
                $consumable_id = current($input['items'][self::getType()]);
                $consumable = new self();
                if (
                    $consumable_id === false
                    || !$consumable->getFromDB($consumable_id)
                    || ($consumable_item = $consumable->getItem()) === false
                ) {
                    // Cannot show form
                    break;
                }
                $entity_restrict = $consumable_item->isRecursive()
                    ? getSonsOf('glpi_entities', $consumable_item->getEntityID())
                    : $consumable_item->getEntityID();

                Dropdown::showSelectItemFromItemtypes([
                    'itemtype_name'   => 'give_itemtype',
                    'items_id_name'   => 'give_items_id',
                    'entity_restrict' => $entity_restrict,
                    'itemtypes'       => $CFG_GLPI["consumables_types"],
                ]);
                echo "<br><br>" . Html::submit(
                    _x('button', 'Give'),
                    ['name' => 'massiveaction']
                );
                return true;
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
            case 'give':
                $input = $ma->getInput();
                if (
                    ($input["give_items_id"] > 0)
                    && !empty($input['give_itemtype'])
                ) {
                    foreach ($ids as $key) {
                        if ($item->can($key, UPDATE)) {
                            if ($item->out($key, $input['give_itemtype'], $input["give_items_id"])) {
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
                    Event::log(
                        $item->fields['consumableitems_id'],
                        "consumableitems",
                        5,
                        "inventory",
                        //TRANS: %s is the user login
                        sprintf(__('%s gives a consumable'), $_SESSION["glpiname"])
                    );
                } else {
                    $ma->itemDone($item::class, $ids, MassiveAction::ACTION_KO);
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
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => ['consumableitems_id' => $tID],
        ])->current();
        return (int) $result['cpt'];
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
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'consumableitems_id' => $tID,
                'NOT'                => ['date_out' => null],
            ],
        ])->current();
        return (int) $result['cpt'];
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
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'consumableitems_id' => $tID,
                'date_out'           => null,
            ],
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
        global $DB;

        $it = $DB->request([
            'SELECT'  => ['stock_target'],
            'FROM'   => ConsumableItem::getTable(),
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
     * @param integer $tID Consumable item ID
     * @return integer
     */
    public static function getAlarmThreshold(int $tID): int
    {
        global $DB;

        $it = $DB->request([
            'SELECT'  => ['alarm_threshold'],
            'FROM'   => ConsumableItem::getTable(),
            'WHERE'  => [
                'id'  => $tID,
            ],
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
     *                                 The return value will anyway be a safe HTML string.
     *
     * @return string to display
     **/
    public static function getCount($tID, $alarm_threshold, $nohtml = false)
    {
        // Get total
        $total = self::getTotalNumber($tID);

        if ($total !== 0) {
            $unused = self::getUnusedNumber($tID);
            $old    = self::getOldNumber($tID);

            $highlight = "";
            if ($unused <= $alarm_threshold) {
                $highlight = "class='tab_bg_1_2'";
            }
            //TRANS: For consumable. %1$d is total number, %2$d is unused number, %3$d is old number
            $tmptxt = sprintf(__('Total: %1$d, New: %2$d, Used: %3$d'), $total, $unused, $old);
            if ($nohtml) {
                $out = htmlescape($tmptxt);
            } else {
                $out = "<div $highlight>" . htmlescape($tmptxt) . "</div>";
            }
        } else {
            if ($nohtml) {
                $out = __s('No consumable');
            } else {
                $out = "<div class='tab_bg_1_2'><i>" . __s('No consumable') . "</i></div>";
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
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'id'        => $cID,
                'date_out'  => null,
            ],
        ])->current();
        return $result['cpt'] === 1;
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
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'id'     => $cID,
                'NOT'   => ['date_out' => null],
            ],
        ])->current();
        return $result['cpt'] === 1;
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
        } elseif (self::isOld($cID)) {
            return _nx('consumable', 'Used', 'Used', 1);
        }
        return '';
    }

    /**
     * Display a consumable list for a given consumable item
     *
     * @param ConsumableItem $parent Parent consumable item
     *
     * @return void
     */
    public static function displayConsumableList(ConsumableItem $parent): void
    {
        // Search criteria used to display lists (used and unused items)
        $criteria_parent_consumable = [
            'link'       => 'AND',
            'field'      => 8, // Parent consumable
            'searchtype' => 'equals',
            'value'      => $parent->getID(),
        ];
        $criteria_unused = [
            [
                'link'       => 'AND',
                'field'      => 5, // Date out
                'searchtype' => 'empty',
                'value'      => "0",
            ],
            $criteria_parent_consumable,
        ];
        $criteria_used = [
            [
                'link' => 'AND NOT',
                'criteria' => $criteria_unused,
            ],
            $criteria_parent_consumable,
        ];

        // Count used and unused items
        $count_unused = countElementsInTable(self::getTable(), [
            'date_out' => 'NULL',
            ConsumableItem::getForeignKeyField() => $parent->getID(),
        ]);
        $count_used = countElementsInTable(self::getTable(), [
            'NOT' => ['date_out' => 'NULL'],
            ConsumableItem::getForeignKeyField() => $parent->getID(),
        ]);

        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/assets/consumable_list.html.twig', [
            'item'            => new self(),
            'parent'          => $parent,
            'itemtype'        => self::class,
            'can_edit'        => $parent->canUpdate() && $parent->canUpdateItem(),
            'criteria_unused' => $criteria_unused,
            'criteria_used'   => $criteria_used,
            'count_unused'    => $count_unused,
            'count_used'      => $count_used,
        ]);
    }

    public static function showForUser(User $user)
    {
        global $DB;

        $itemtype = $user::class;
        $items_id = $user->getField('id');

        $start       = (int) ($_GET["start"] ?? 0);
        $sort        = $_GET["sort"] ?? "";
        $order       = strtoupper($_GET["order"] ?? "");
        $filters     = $_GET['filters'] ?? [];
        $is_filtered = count($filters) > 0;
        $sql_filters = self::convertFiltersValuesToSqlCriteria($filters);

        if (strlen($sort) == 0) {
            $sort = "name";
        }
        if (strlen($order) == 0) {
            $order = "ASC";
        }

        $query = [
            'SELECT' => [
                'glpi_consumables.*',
                'glpi_consumableitems.name AS itemname',
                'glpi_consumableitems.ref AS ref',
                'glpi_consumableitems.entities_id AS entities_id',
                'glpi_consumableitems.is_recursive AS is_recursive',
            ],
            'FROM' => self::getTable(),
            'LEFT JOIN' => [
                'glpi_consumableitems' => [
                    'ON' => [
                        'glpi_consumableitems' => 'id',
                        'glpi_consumables'     => 'consumableitems_id',
                    ],
                ],
            ],
            'WHERE'  => [
                'glpi_consumables.items_id' => $items_id,
                'glpi_consumables.itemtype' => $itemtype,
                'NOT' => ['glpi_consumables.date_out' => 'NULL'],
            ] + getEntitiesRestrictCriteria('glpi_consumableitems', '', '', true),
        ];

        $total_number = (int) $DB->request($query + [
            'COUNT'  => 'cpt',
        ])->current()['cpt'];

        $filtered_query = $query;
        $filtered_query['WHERE'] += $sql_filters;
        $filtered_data = $DB->request($filtered_query + [
            'LIMIT' => $_SESSION['glpilist_limit'],
            'START' => $start,
            'ORDER' => "$sort $order",
        ]);

        $filtered_number = (int) $DB->request($filtered_query + [
            'COUNT'  => 'cpt',
        ])->current()['cpt'];

        $envs = [];
        foreach ($filtered_data as $env) {
            $env['itemtype'] = self::getType();
            $envs[$env['id']] = $env;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'sort' => $sort,
            'order' => $order,
            'additional_params' => $is_filtered ? http_build_query([
                'filters' => $filters,
            ]) : "",
            'is_tab' => true,
            'items_id' => $items_id,
            'filters' => $filters,
            'columns' => [
                'id' => __('ID'),
                'itemname' => __('Name'),
                'ref' => __('Reference'),
                'date_in' => __('Add date'),
                'date_out' => __('Use date'),
            ],
            'entries' => $envs,
            'total_number' => $total_number,
            'filtered_number' => $filtered_number,
            'showmassiveactions' => true,
            'massiveactionparams' => [
                'num_displayed'    => min($_SESSION['glpilist_limit'], $filtered_number),
                'container'        => 'mass' . self::class . mt_rand(),
                'specific_actions' => [
                    'delete' => __('Delete permanently'),
                    'Consumable' . MassiveAction::CLASS_ACTION_SEPARATOR . 'backtostock' => __('Back to stock'),
                ],
            ],
        ]);
    }

    /**
     * Show the usage summary of consumables by user
     *
     * @return void
     **/
    public static function showSummary()
    {
        global $DB;

        if (!self::canView()) {
            return;
        }

        $iterator = $DB->request([
            'SELECT' => [
                'COUNT'  => ['* AS count'],
                'consumableitems_id',
                'itemtype',
                'items_id',
            ],
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'NOT'                => ['date_out' => null],
                'consumableitems_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_consumableitems',
                    'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems'),
                ]),
            ],
            'GROUP'  => ['itemtype', 'items_id', 'consumableitems_id'],
        ]);
        $used = [];

        foreach ($iterator as $data) {
            $used[$data['itemtype'] . '####' . $data['items_id']][$data["consumableitems_id"]] = $data["count"];
        }

        $iterator = $DB->request([
            'SELECT' => [
                'COUNT'  => '* AS count',
                'consumableitems_id',
            ],
            'FROM'   => 'glpi_consumables',
            'WHERE'  => [
                'date_out'           => null,
                'consumableitems_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_consumableitems',
                    'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems'),
                ]),
            ],
            'GROUP'  => ['consumableitems_id'],
        ]);
        $new = [];

        foreach ($iterator as $data) {
            $new[$data["consumableitems_id"]] = $data["count"];
        }

        $iterator = $DB->request([
            'FROM'   => 'glpi_consumableitems',
            'WHERE'  => getEntitiesRestrictCriteria('glpi_consumableitems'),
        ]);
        $columns = [
            'give_to' => __('Give to'),
        ];
        $formatters = [];
        $entries = [];
        $types = [];

        foreach ($iterator as $data) {
            $types[$data["id"]] = $data["name"];
        }

        asort($types);
        foreach ($types as $key => $type) {
            $columns[$key] = $type;
            $formatters[$key] = 'integer';
        }
        $columns['total'] = __('Total');
        $formatters['total'] = 'integer';

        $new_entry = [
            'give_to' => __('In stock'),
            'total'   => 0,
        ];
        foreach (array_keys($types) as $id_type) {
            if (!isset($new[$id_type])) {
                $new[$id_type] = 0;
            }
            $new_entry[$id_type] = $new[$id_type];
            $new_entry['total'] += $new[$id_type];
        }
        $entries[] = $new_entry;

        foreach ($used as $itemtype_items_id => $val) {
            [$itemtype, $items_id] = explode('####', $itemtype_items_id);
            $item = getItemForItemtype($itemtype);
            $item_name = '';
            if ($item->getFromDB($items_id)) {
                //TRANS: %1$s is a type name - %2$s is a name
                $item_name = sprintf(__('%1$s - %2$s'), $item->getTypeName(1), $item->getNameID());
            }
            $entry = [
                'give_to' => $item_name,
                'total'   => 0,
            ];

            foreach (array_keys($types) as $id_type) {
                if (!isset($val[$id_type])) {
                    $val[$id_type] = 0;
                }
                $entry[$id_type] = $val[$id_type];
                $entry['total'] += $val[$id_type];
            }
            $entries[] = $entry;
        }

        $footer = [__('Total')];
        foreach (array_keys($types) as $id_type) {
            $footer[] = array_sum(array_column($entries, $id_type));
        }
        $footer[] = array_sum(array_column($entries, 'total'));

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'footers' => [$footer],
            'footer_class' => 'fw-bold',
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && self::canView()) {
            $nb = 0;
            switch ($item::class) {
                case ConsumableItem::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  self::countForConsumableItem($item);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                case User::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForUser($item);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
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

    /**
     * @param User $item
     *
     * @return integer
     **/
    public static function countForUser(User $item)
    {
        return countElementsInTable(['glpi_consumables'], [
            'glpi_consumables.itemtype' => 'User',
            'glpi_consumables.items_id' => $item->getField('id'),
            'NOT' => ['glpi_consumables.date_out' => 'NULL'],
        ]);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case ConsumableItem::class:
                self::displayConsumableList($item);
                break;
            case User::class:
                self::showForUser($item);
                break;
        }
        return true;
    }

    public function getRights($interface = 'central')
    {
        return (new ConsumableItem())->getRights($interface);
    }

    public static function getIcon()
    {
        return "ti ti-package";
    }

    public static function convertFiltersValuesToSqlCriteria(array $filters = []): array
    {
        $sql_filters = [];

        $like_filters = [
            'id'        => 'glpi_consumables.id',
            'itemname'  => 'glpi_consumableitems.name',
            'ref'       => 'glpi_consumableitems.ref',
            'date_in'   => 'glpi_consumables.date_in',
            'date_out'  => 'glpi_consumables.date_out',
        ];
        foreach ($like_filters as $filter_key => $filter_field) {
            if (($filters[$filter_key] ?? "") !== '') {
                $sql_filters[$filter_field] = ['LIKE', '%' . $filters[$filter_key] . '%'];
            }
        }

        return $sql_filters;
    }

    public function rawSearchOptions()
    {
        $options = parent::rawSearchOptions();

        $options[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $options[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'date_out',
            'name'               => _n('State', 'States', 1),
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific',
        ];

        $options[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'date_in',
            'name'               => __('Add date'),
            'massiveaction'      => false,
            'datatype'           => 'date',
        ];

        $options[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'date_out',
            'name'               => __('Use date'),
            'massiveaction'      => false,
            'datatype'           => 'date',
        ];

        $options[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'items_id',
            'name'               => __('Given to'),
            'massiveaction'      => false,
            'additionalfields'   => ['itemtype'],
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $infocom_label = Infocom::getTypeName();
        $options[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => $infocom_label,
            'massiveaction'      => false,
            'nosearch'           => true,
            'datatype'           => 'specific',
            'nosort'             => 'true',
        ];

        $options[] = [
            'id'                 => '8',
            'table'              => ConsumableItem::getTable(),
            'field'              => 'name',
            'name'               => ConsumableItem::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'searchtype'         => ['equals', 'notequals'],
        ];

        return $options;
    }

    public static function getSpecificValueToDisplay(
        $field,
        $values,
        array $options = []
    ) {
        switch ($options['searchopt']['id']) {
            case '3': // State
                $date_out = $values['date_out'];
                return empty($date_out) ? __s("New") : __s("Used");

            case '6': // Given to
                $itemtype = $values['itemtype'];
                $items_id = $values['items_id'];
                if (is_a($itemtype, CommonDBTM::class, true)) {
                    $item = new $itemtype();
                    if ($item->getFromDB($items_id)) {
                        return $item->getLink();
                    }
                }

                // Must not be empty
                return " ";

            case '7': // Infocom shortcut
                $id = (int) $values['id'];
                ob_start();
                Infocom::showDisplayLink(self::class, $id);
                return ob_get_clean();
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
