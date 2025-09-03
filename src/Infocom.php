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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Exception\Http\NotFoundHttpException;
use Safe\DateTime;

use function Safe\mktime;
use function Safe\preg_match;
use function Safe\strtotime;

/**
 * Infocom class
 **/
class Infocom extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';
    public $dohistory              = true;
    public $auto_message_on_action = false; // Link in message can't work'
    public static $logs_for_parent = false;
    public static $rightname              = 'infocom';

    //Option to automatically fill dates
    public const ON_STATUS_CHANGE   = 'STATUS';
    public const COPY_WARRANTY_DATE = 1;
    public const COPY_BUY_DATE      = 2;
    public const COPY_ORDER_DATE    = 3;
    public const COPY_DELIVERY_DATE = 4;
    public const ON_ASSET_IMPORT    = 5;


    /**
     * Check if given object can have Infocom
     *
     * @since 0.85
     *
     * @param string|object $item  an object or a string
     *
     * @return boolean true if $object is an object that can have Infocom
     *
     **/
    public static function canApplyOn($item)
    {
        global $CFG_GLPI;

        // All devices are subjects to infocom !
        if (is_a($item, 'Item_Devices', true)) {
            return true;
        }

        // We also allow direct items to check
        if ($item instanceof CommonGLPI) {
            $item = $item->getType();
        }

        if (in_array($item, $CFG_GLPI['infocom_types'])) {
            return true;
        }

        return false;
    }


    /**
     * Get all the types that can have an infocom
     *
     * @since 0.85
     *
     * @return array of the itemtypes
     **/
    public static function getItemtypesThatCanHave()
    {
        global $CFG_GLPI;

        $types = array_merge(
            $CFG_GLPI['infocom_types'],
            Item_Devices::getDeviceTypes()
        );
        return array_unique($types);
    }


    public static function getTypeName($nb = 0)
    {
        //TRANS: Always plural
        return __('Financial and administrative information');
    }


    public function post_getEmpty()
    {

        if (isset($_SESSION['glpiactive_entity'])) {
            $this->fields["alert"] = Entity::getUsedConfig(
                "use_infocoms_alert",
                $_SESSION['glpiactive_entity'],
                "default_infocom_alert",
                0
            );
        }
    }


    public function getLogTypeID()
    {
        return [$this->fields['itemtype'], $this->fields['items_id']];
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        // Can exists on template
        if (
            Session::haveRight(self::$rightname, READ)
            && ($item instanceof CommonDBTM)
        ) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Supplier':
                    /** @var Supplier $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForSupplier($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::getType());

                default:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_infocoms',
                            ['itemtype' => $item->getType(),
                                'items_id' => $item->getID(),
                            ]
                        );
                    }
                    return self::createTabEntry(__('Management'), $nb, $item::getType());
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        switch (true) {
            case $item instanceof Supplier:
                $item->showInfocoms();
                break;

            default:
                self::showForItem($item, $withtemplate);
        }
        return true;
    }


    /**
     * @param $item   Supplier  object
     **/
    public static function countForSupplier(Supplier $item)
    {

        return countElementsInTable(
            'glpi_infocoms',
            [
                'suppliers_id' => $item->getField('id'),
                'NOT' => ['itemtype' => ['ConsumableItem', 'CartridgeItem', 'Software']],
            ] + getEntitiesRestrictCriteria('glpi_infocoms', '', $_SESSION['glpiactiveentities'])
        );
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'sink_type':
                return htmlescape(self::getAmortTypeName($values[$field]));

            case 'alert':
                return htmlescape(self::getAlertName($values[$field]));
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name               (default '')
     * @param $values             (default '')
     * @param $options      array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case "sink_type":
                return self::dropdownAmortType($name, $values[$field], false);

            case "alert":
                $options['name']  = $name;
                $options['value'] = $values[$field];
                return self::dropdownAlert($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Retrieve an item from the database for a device
     *
     * @param string  $itemtype  type of the device to retrieve infocom
     * @param integer $ID        ID of the device to retrieve infocom
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDBforDevice($itemtype, $ID)
    {

        if (
            $this->getFromDBByCrit([
                $this->getTable() . '.items_id'  => $ID,
                $this->getTable() . '.itemtype'  => $itemtype,
            ])
        ) {
            return true;
        }
        $this->getEmpty();
        $this->fields["items_id"] = $ID;
        $this->fields["itemtype"] = $itemtype;
        return false;
    }

    /**
     * @param class-string<CommonDBTM> $itemtype The itemtype to get data for. The itemtype must have a `ticket_tco` field.
     * @param string $begin Date string for the beginning of the period to retrieve (based on buy or use date)
     * @param string $end Date string for the end of the period to retrieve (based on buy or use date)
     * @return ?array
     */
    public static function getDataForAssetInfocomReport(string $itemtype, string $begin, string $end): ?array
    {
        global $DB;
        $itemtable = getTableForItemType($itemtype);
        if (!$DB->fieldExists($itemtable, "ticket_tco", false)) {
            return null;
        }

        $criteria = [
            'SELECT'       => [
                'glpi_infocoms.*',
                "$itemtable.name AS itemname",
                "$itemtable.ticket_tco",
                'glpi_entities.completename AS entityname',
                'glpi_entities.id AS entID',

            ],
            'FROM'         => 'glpi_infocoms',
            'INNER JOIN'   => [
                $itemtable  => [
                    'ON'  => [
                        'glpi_infocoms'   => 'items_id',
                        $itemtable        => 'id', [
                            'AND' => [
                                'glpi_infocoms.itemtype'   => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
            'LEFT JOIN'    => [
                'glpi_entities'   => [
                    'ON'  => [
                        'glpi_entities'   => 'id',
                        $itemtable        => 'entities_id',
                    ],
                ],
            ],
            'WHERE'        => [
                "$itemtable.is_template" => 0,
            ] + getEntitiesRestrictCriteria($itemtable) + $itemtype::getSystemSQLCriteria(),
            'ORDERBY'      => ['entityname ASC', 'buy_date', 'use_date'],
        ];

        if (!empty($begin)) {
            $criteria['WHERE'][] = [
                'OR'  => [
                    'glpi_infocoms.buy_date'   => ['>=', $begin],
                    'glpi_infocoms.use_date'   => ['>=', $begin],
                ],
            ];
        }

        if (!empty($end)) {
            $criteria['WHERE'][] = [
                'OR'  => [
                    'glpi_infocoms.buy_date'   => ['<=', $end],
                    'glpi_infocoms.use_date'   => ['<=', $end],
                ],
            ];
        }

        return iterator_to_array($DB->request($criteria));
    }

    /**
     * @param class-string<CommonDBTM> $itemtype The itemtype to get data for. The itemtype must have a `ticket_tco` field.
     * @param string $begin Date string for the beginning of the period to retrieve (based on buy or use date)
     * @param string $end Date string for the end of the period to retrieve (based on buy or use date)
     * @return ?array
     */
    public static function getDataForOtherInfocomReport(string $itemtype, string $begin, string $end): ?array
    {
        global $DB;
        $itemtable = getTableForItemType($itemtype);
        if ($DB->fieldExists($itemtable, "ticket_tco", false)) {
            // This is already handled by the asset infocom report
            return null;
        }

        $criteria = [
            'SELECT'       => 'glpi_infocoms.*',
            'FROM'         => 'glpi_infocoms',
            'INNER JOIN'   => [
                $itemtable  => [
                    'ON'  => [
                        $itemtable        => 'id',
                        'glpi_infocoms'   => 'items_id', [
                            'AND' => [
                                'glpi_infocoms.itemtype' => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'        => $itemtype::getSystemSQLCriteria(),
        ];

        switch ($itemtype) {
            case 'SoftwareLicense':
                $criteria['INNER JOIN']['glpi_softwares'] = [
                    'ON'  => [
                        'glpi_softwarelicenses' => 'softwares_id',
                        'glpi_softwares'        => 'id',
                    ],
                ];
                $criteria['WHERE'][] =  getEntitiesRestrictCriteria("glpi_softwarelicenses");
                break;
            default:
                if (is_a($itemtype, CommonDBChild::class, true)) {
                    $childitemtype = $itemtype::$itemtype; // acces to child via $itemtype static
                    $criteria['INNER JOIN'][$childitemtype::getTable()] = [
                        'ON'  => [
                            $itemtype::getTable() => $itemtype::$items_id,
                            $childitemtype::getTable() => 'id',
                        ],
                    ];
                    $criteria['WHERE'][] =  getEntitiesRestrictCriteria($itemtable);
                }
                break;
        }

        if (!empty($begin)) {
            $criteria['WHERE'][] = [
                'OR'  => [
                    'glpi_infocoms.buy_date'   => ['>=', $begin],
                    'glpi_infocoms.use_date'   => ['>=', $begin],
                ],
            ];
        }
        if (!empty($end)) {
            $criteria['WHERE'][] = [
                'OR'  => [
                    'glpi_infocoms.buy_date'   => ['<=', $end],
                    'glpi_infocoms.use_date'   => ['<=', $end],
                ],
            ];
        }

        $criteria['WHERE'] = array_filter($criteria['WHERE']);
        return iterator_to_array($DB->request($criteria));
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->getFromDBforDevice($input['itemtype'], $input['items_id'])) {
            if ($item = static::getItemFromArray(static::$itemtype, static::$items_id, $input)) {
                $input['alert'] = Entity::getUsedConfig('default_infocom_alert', $item->getEntityID());
                return parent::prepareInputForAdd($input);
            }
        }
        return false;
    }


    /**
     * Fill, if necessary, automatically some dates when status changes
     *
     * @param CommonDBTM $item          CommonDBTM object: the item whose status have changed
     * @param boolean $action_add    true if object is added, false if updated (true by default)
     *
     * @return void
     **/
    public static function manageDateOnStatusChange(CommonDBTM $item, $action_add = true)
    {
        $itemtype = get_class($item);
        $changes  = $item->fields;

        //Autofill date on item's status change ?
        $infocom = new self();
        $infocom->getFromDB($changes['id']);
        $tmp           = ['itemtype' => $itemtype,
            'items_id' => $changes['id'],
        ];
        $add_or_update = false;

        //For each date that can be automatically filled
        foreach (self::getAutoManagemendDatesFields() as $date => $date_field) {
            $resp   = [];
            $result = Entity::getUsedConfig($date, $changes['entities_id']);

            //Date must be filled if status corresponds to the one defined in the config
            if (
                preg_match('/' . self::ON_STATUS_CHANGE . '_(.*)/', $result, $values)
                && ($values[1] == $changes['states_id'])
            ) {
                $add_or_update    = true;
                $tmp[$date_field] = $_SESSION["glpi_currenttime"];
            }
        }

        //One date or more has changed
        if ($add_or_update) {
            if (!$infocom->getFromDBforDevice($itemtype, $changes['id'])) {
                $infocom->add($tmp);
            } else {
                $tmp['id'] = $infocom->fields['id'];
                $infocom->update($tmp);
            }
        }
    }


    /**
     * Automatically manage copying one date to another is necessary
     *
     * @param array $infocoms   array of item's infocom to modify
     * @param string $field            the date to modify (default '')
     * @param integer $action           the action to peform (copy from another date) (default 0)
     * @param array $params     array of additional parameters needed to perform the task
     *
     * @return void
     **/
    public static function autofillDates(&$infocoms = [], $field = '', $action = 0, $params = [])
    {
        if (isset($infocoms[$field]) || is_null($infocoms[$field])) {
            switch ($action) {
                default:
                case 0:
                    break;

                case self::COPY_WARRANTY_DATE:
                    if (isset($infocoms['warranty_date'])) {
                        $infocoms[$field] = $infocoms['warranty_date'];
                    }
                    break;

                case self::COPY_BUY_DATE:
                    if (isset($infocoms['buy_date'])) {
                        $infocoms[$field] = $infocoms['buy_date'];
                    }
                    break;

                case self::COPY_ORDER_DATE:
                    if (isset($infocoms['order_date'])) {
                        $infocoms[$field] = $infocoms['order_date'];
                    }
                    break;

                case self::COPY_DELIVERY_DATE:
                    if (isset($infocoms['delivery_date'])) {
                        $infocoms[$field] = $infocoms['delivery_date'];
                    }
                    break;
            }
        }
    }


    /**
     * Return all infocom dates that could be automaticall filled
     *
     * @return array with all dates (configuration field & real field)
     **/
    public static function getAutoManagemendDatesFields()
    {

        return [
            'autofill_buy_date'         => 'buy_date',
            'autofill_use_date'         => 'use_date',
            'autofill_delivery_date'    => 'delivery_date',
            'autofill_warranty_date'    => 'warranty_date',
            'autofill_order_date'       => 'order_date',
            'autofill_decommission_date' => 'decommission_date',
        ];
    }


    public function prepareInputForUpdate($input)
    {

        //Check if one or more dates needs to be updated
        foreach (self::getAutoManagemendDatesFields() as $key => $field) {
            $result = Entity::getUsedConfig($key, $this->fields['entities_id']);

            //Only update date if it's empty in DB. Otherwise do nothing
            if (
                ($result > 0)
                && !isset($this->fields[$field])
            ) {
                if (!isset($input[$field])) {
                    $input[$field] = null;
                }
                self::autofillDates($input, $field, $result);
            }
        }

        return parent::prepareInputForUpdate($input);
    }


    public function pre_updateInDB()
    {

        // Clean end alert if warranty_date is after old one
        // Or if duration is greater than old one
        if (
            (isset($this->oldvalues['warranty_date'])
            && ($this->oldvalues['warranty_date'] < $this->fields['warranty_date']))
            || (isset($this->oldvalues['warranty_duration'])
              && ($this->oldvalues['warranty_duration'] < $this->fields['warranty_duration']))
        ) {
            $alert = new Alert();
            $alert->clear($this->getType(), $this->fields['id'], Alert::END);
        }
        // Check budgets link validity
        if (
            (in_array('budgets_id', $this->updates)
            || in_array('buy_date', $this->updates))
            && $this->fields['budgets_id']
            && ($budget = getItemForItemtype('Budget'))
            && $budget->getFromDB($this->fields['budgets_id'])
        ) {
            if (
                (!is_null($budget->fields['begin_date'])
                && $this->fields['buy_date'] < $budget->fields['begin_date'])
                || (!is_null($budget->fields['end_date'])
                 && ($this->fields['buy_date'] > $budget->fields['end_date']))
            ) {
                $msg = sprintf(
                    __('Purchase date incompatible with the associated budget. %1$s not in budget period: %2$s / %3$s'),
                    Html::convDate($this->fields['buy_date']),
                    Html::convDate($budget->fields['begin_date']),
                    Html::convDate($budget->fields['end_date'])
                );
                Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
            }
        }
    }

    /**
     * @since 0.84
     **/
    public function cleanDBonPurge()
    {

        $class = new Alert();
        $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
    }


    /**
     * @param $name
     **/
    public static function cronInfo($name)
    {
        return ['description' => __('Send alarms on financial and administrative information')];
    }


    /**
     * Cron action on infocom : alert on expired warranty
     *
     * @param CronTask $task to log, if NULL use display (default NULL)
     *
     * @return integer 0 : nothing to do 1 : done with success
     **/
    public static function cronInfocom($task = null)
    {
        global $CFG_GLPI, $DB;

        if (!$CFG_GLPI["use_notifications"]) {
            return 0;
        }
        $cron_status    = 0;
        $items_infos    = [];
        $items_messages = [];

        foreach (Entity::getEntitiesToNotify('use_infocoms_alert') as $entity => $value) {
            $before    = Entity::getUsedConfig('send_infocoms_alert_before_delay', $entity);
            $table = self::getTable();
            $iterator = $DB->request([
                'SELECT'    => "$table.*",
                'FROM'      => $table,
                'LEFT JOIN'  => [
                    'glpi_alerts'  => [
                        'ON' => [
                            'glpi_alerts'  => 'items_id',
                            $table         => 'id', [
                                'AND' => [
                                    'glpi_alerts.itemtype'  => self::getType(),
                                    'glpi_alerts.type'      => Alert::END,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    new QueryExpression(
                        '(' . $DB->quoteName('glpi_infocoms.alert') . ' & ' . 2 ** Alert::END . ') > 0'
                    ),
                    "$table.entities_id"       => $entity,
                    "$table.warranty_duration" => ['>', 0],
                    'NOT'                      => ["$table.warranty_date" => null],
                    new QueryExpression(QueryFunction::dateDiff(
                        expression1: QueryFunction::dateAdd(
                            date: 'glpi_infocoms.warranty_date',
                            interval: new QueryExpression($DB::quoteName('glpi_infocoms.warranty_duration')),
                            interval_unit: 'MONTH'
                        ),
                        expression2: QueryFunction::curdate()
                    ) . ' <= ' . $DB::quoteValue($before)),
                    'glpi_alerts.date'         => null,
                ],
            ]);

            $items_messages[$entity] = [];

            foreach ($iterator as $data) {
                if ($item_infocom = getItemForItemtype($data["itemtype"])) {
                    if ($item_infocom->getFromDB($data["items_id"])) {
                        $entity   = $data['entities_id'];
                        $warranty = self::getWarrantyExpir($data["warranty_date"], $data["warranty_duration"]);
                        //TRANS: %1$s is a type, %2$s is a name (used in croninfocom)
                        $name    = sprintf(
                            __('%1$s - %2$s'),
                            $item_infocom->getTypeName(1),
                            $item_infocom->getName()
                        );
                        //TRANS: %1$s is the warranty end date and %2$s the name of the item
                        $items_messages[$entity][] = sprintf(
                            __('Item reaching the end of warranty on %1$s: %2$s'),
                            $warranty,
                            $name
                        );

                        $data['warrantyexpiration']        = $warranty;
                        $data['item_name']                 = $item_infocom->getName();
                        $data['is_deleted']                = $item_infocom->maybeDeleted() ? (int) $item_infocom->fields['is_deleted'] : 0;
                        $items_infos[$entity][$data['id']] = $data;
                    }
                }
            }
            if (count($items_messages[$entity]) === 0) {
                $items_messages[$entity] = __('No item reaching the end of warranty.');
            }
        }

        foreach ($items_infos as $entity => $items) {
            // We will ignore items that have been deleted but aren't expired, in case they are restored before the warranty expires
            $not_deleted_items = array_filter($items, static fn($item) => $item['is_deleted'] === 0);
            $deleted_expired_items = array_filter($items, static fn($item) => $item['is_deleted'] === 1 && $item['warrantyexpiration'] < $_SESSION['glpi_currenttime']);
            if (
                NotificationEvent::raiseEvent("alert", new self(), [
                    'entities_id' => $entity,
                    'items'       => $not_deleted_items,
                ])
            ) {
                $messages    = $items_messages[$entity];
                $cron_status = 1;
                if ($task) {
                    $task->log(sprintf(
                        __('%1$s: %2$s') . "\n",
                        Dropdown::getDropdownName("glpi_entities", $entity),
                        implode("\n", $messages)
                    ));
                    $task->addVolume(1);
                } else {
                    Session::addMessageAfterRedirect(
                        sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape(Dropdown::getDropdownName("glpi_entities", $entity)),
                            implode('<br>', array_map('htmlescape', $messages))
                        )
                    );
                }

                $alert             = new Alert();
                $input = [
                    'itemtype' => 'Infocom',
                    'type'     => Alert::END,
                ];
                foreach (array_keys($not_deleted_items) as $id) {
                    $input["items_id"] = $id;
                    $alert->add($input);
                    unset($alert->fields['id']);
                }
            } else {
                $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
                //TRANS: %s is entity name
                $msg = sprintf(__('%1$s: %2$s'), $entityname, __('send infocom alert failed'));
                if ($task) {
                    $task->log($msg);
                } else {
                    Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
                }
            }

            $alert = new Alert();
            foreach (array_keys($deleted_expired_items) as $id) {
                $alert->add([
                    'itemtype' => 'Infocom',
                    'type'     => Alert::END,
                    'items_id' => $id,
                ]);
                unset($alert->fields['id']);
            }
        }
        return $cron_status;
    }


    /**
     * Get the possible value for infocom alert
     *
     * @since 0.84 (before in alert.class)
     *
     * @param integer|string|null $val if not set, ask for all values, else for 1 value (default NULL)
     *
     * @return array|string
     **/
    public static function getAlertName($val = null)
    {

        $tmp[0]                  = Dropdown::EMPTY_VALUE;
        $tmp[2 ** Alert::END] = __('Warranty expiration date');

        if (is_null($val)) {
            return $tmp;
        }
        // Default value for display
        $tmp[0] = __('None');

        if (isset($tmp[$val])) {
            return $tmp[$val];
        }
        // If not set and is a string return value
        if (is_string($val)) {
            return $val;
        }
        return NOT_AVAILABLE;
    }


    /**
     * @param $options array
     **/
    public static function dropdownAlert($options)
    {

        $p['name']           = 'alert';
        $p['value']          = 0;
        $p['display']        = true;
        $p['inherit_parent'] = false;

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $tab = [];
        if ($p['inherit_parent']) {
            $tab[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
        }

        $tab += self::getAlertName();

        return Dropdown::showFromArray($p['name'], $tab, $p);
    }


    /**
     * Dropdown of amortissement type for infocoms
     *
     * @param string  $name      select name
     * @param integer $value     default value (default 0)
     * @param boolean $display   display or get string (true by default)
     **/
    public static function dropdownAmortType($name, $value = 0, $display = true)
    {

        $values = [
            2 => __('Linear'),
            1 => __('Decreasing'),
        ];

        return Dropdown::showFromArray($name, $values, [
            'value'               => $value,
            'display'             => $display,
            'display_emptychoice' => true,
            'width'               => '100%',
        ]);
    }


    /**
     * Get amortissement type name for infocoms
     *
     * @param integer $value status ID
     **/
    public static function getAmortTypeName($value)
    {

        switch ($value) {
            case 2:
                return __('Linear');

            case 1:
                return __('Decreasing');

            case 0:
                return " ";
        }
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'financial';
    }

    public static function displayFullPageForItem($id, ?array $menus = null, array $options = []): void
    {
        $ic = new self();

        $item = false;

        if (isset($_GET["id"])) {
            $ic->getFromDB($_GET["id"]);
            $_GET["itemtype"] = $ic->fields["itemtype"];
            $_GET["items_id"] = $ic->fields["items_id"];
        }

        if (
            isset($_GET["itemtype"])
            && ($item = getItemForItemtype($_GET["itemtype"]))
            && (
                !isset($_GET["items_id"])
                || !$item->getFromDB($_GET["items_id"])
            )
        ) {
            throw new NotFoundHttpException();
        }

        Html::popHeader(self::getTypeName());

        self::showForItem($item);

        Html::popFooter();
    }

    public static function getPostFormAction(string $form_action, bool $action_success): ?string
    {
        // Always return to the previous page
        return 'back';
    }

    /**
     * Calculate TCO and TCO by month for an item
     *
     * @param string|number $ticket_tco    Tco part of tickets
     * @param number        $value
     * @param string        $date_achat    (default '')
     *
     * @return string
     **/
    public static function showTco($ticket_tco, $value, $date_achat = "")
    {
        if ($ticket_tco == NOT_AVAILABLE) {
            return '-';
        }

        // Affiche le TCO ou le TCO mensuel pour un mat??riel
        $totalcost = $ticket_tco;

        if ($date_achat) { // on veut donc le TCO mensuel
            // just to avoid IDE warning
            $date_Y = $date_m = $date_d = 0;

            sscanf($date_achat, "%4s-%2s-%2s", $date_Y, $date_m, $date_d);

            $timestamp2 = mktime(0, 0, 0, $date_m, $date_d, $date_Y);
            $timestamp  = mktime(0, 0, 0, (int) date("m"), (int) date("d"), (int) date("Y"));

            $diff = floor(($timestamp - $timestamp2) / (MONTH_TIMESTAMP)); // Mois d'utilisation

            if ($diff) {
                return Html::formatNumber((($totalcost + $value) / $diff)); // TCO mensuel
            }
            return "";
        }
        return Html::formatNumber(($totalcost + $value)); // TCO
    }


    /**
     * Show infocom link to display modal
     *
     * @param class-string<CommonDBTM> $itemtype item type
     * @param integer $device_id item ID
     * @param boolean $display  display or not the link (default true)
     *
     * @return void|string
     **/
    public static function showDisplayLink($itemtype, $device_id, bool $display = true)
    {
        global $CFG_GLPI, $DB;

        if (
            !Session::haveRight(self::$rightname, READ)
            || !($item = getItemForItemtype($itemtype))
        ) {
            return;
        }

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_infocoms',
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $device_id,
            ],
        ])->current();

        $add    = "add";
        $text   = __s('Add');
        if ($result['cpt'] > 0) {
            $add  = "";
            $text = _sx('button', 'Show');
        } elseif (!Infocom::canUpdate()) {
            return;
        }

        $out = '';
        if ($item->canView()) {
            $out .= "<span class='infocom_link' style='cursor:pointer' data-itemtype='" . htmlescape($itemtype) . "' data-items_id='" . htmlescape($device_id) . "'>
               <img src=\"" . htmlescape($CFG_GLPI["root_doc"] . "/pics/dollar$add.png") . "\" alt=\"$text\" title=\"$text\">
               </span>";
            $form_url = Infocom::getFormURL();
            $html = <<<HTML
                <div id="infocom_display_modal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                <h3></h3>
                            </div>
                            <div class="modal-body">
                                <iframe id='iframeinfocom_display_modal' class="iframe hidden border-0 w-100" style="height: 600px"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
HTML;
            $js = "
                $(() => {
                    if ($('#infocom_display_modal').length === 0) {
                        $('body').append(`" . jsescape($html) . "`);
                        const modal_el = $('#infocom_display_modal');
                        $(document).on('click', '.infocom_link', (e) => {
                            modal_el.data('itemtype', e.currentTarget.getAttribute('data-itemtype'));
                            modal_el.data('items_id', e.currentTarget.getAttribute('data-items_id'));
                            modal_el.modal('show');
                        });
                        modal_el.on('shown.bs.modal', () => {
                            $('#iframeinfocom_display_modal')
                                .attr('src', '" . jsescape($form_url) . "?itemtype=' + modal_el.data('itemtype') + '&items_id=' + modal_el.data('items_id'))
                                .removeClass('hidden');
                        });
                    }
                });
            ";
            $out .= Html::scriptBlock($js);
        }
        if ($display) {
            echo $out;
        } else {
            return $out;
        }
    }


    /**
     * Calculate amortization values
     *
     * @param number        $value       Purchase value
     * @param number        $duration    Amortise duration
     * @param string        $fiscaldate  Begin of fiscal excercise
     * @param string $buydate     Buy date
     * @param string $usedate     Date of use
     *
     * @return array|boolean
     */
    public static function linearAmortise($value, $duration, $fiscaldate, $buydate = '', $usedate = '')
    {
        //Set timezone to UTC; see https://stackoverflow.com/a/40358744
        $TZ = 'UTC';

        try {
            if ($fiscaldate == '') {
                throw new RuntimeException('Empty date');
            }
            $fiscaldate = new DateTime($fiscaldate, new DateTimeZone($TZ));
        } catch (Throwable $e) {
            Session::addMessageAfterRedirect(
                __s('Please fill you fiscal year date in preferences.'),
                false,
                ERROR
            );
            return false;
        }

        //get begin date. Work on use date if provided.
        try {
            if ($buydate == '' && $usedate == '') {
                throw new RuntimeException('Empty date');
            }
            if ($usedate != '') {
                $usedate = new DateTime($usedate, new DateTimeZone($TZ));
            } else {
                $usedate = new DateTime($buydate, new DateTimeZone($TZ));
            }
        } catch (Throwable $e) {
            Session::addMessageAfterRedirect(
                __s('Please fill either buy or use date in preferences.'),
                false,
                ERROR
            );
            return false;
        }

        $now = new DateTime('now', new DateTimeZone($TZ));

        $elapsed_years = $now->format('Y') - $usedate->format('Y');

        $annuity = 0;
        if ($duration) {
            $annuity = $value * (1 / $duration);
        }

        $years = [];
        for ($i = 0; $i <= $elapsed_years; ++$i) {
            $begin_value      = $value;
            $current_annuity  = $annuity;
            $fiscal_end       = new DateTime(
                $fiscaldate->format('d-m-') . ($usedate->format('Y') + $i),
                new DateTimeZone($TZ)
            );

            if ($i == 0) {
                //first year, calculate prorata
                if ($fiscal_end < $usedate) {
                    $fiscal_end->modify('+1 year');
                }
                $days = $fiscal_end->diff($usedate);
                $days = (int) $days->format('%m') * 30 + (int) $days->format('%d');
                $current_annuity = $annuity * $days / 360;
            } elseif ($i == $duration) {
                $current_annuity = $value;
            }
            if ($i > $duration) {
                $value = 0;
                $current_annuity = 0;
            } else {
                //calculate annuity
                //full year case
                $value -= $current_annuity;
            }

            $years[$usedate->format('Y') + $i] = [
                'start_value'  => (float) $begin_value,
                'value'        => $value,
                'annuity'      => $current_annuity,
            ];
        }

        return $years;
    }

    /**
     * Maps new amortise format to old one...
     * To not rewrite all the old method.
     *
     * @param array $values New format amortise values
     * @param boolean $current True to get only current year, false to get the whole array
     *
     * @return array|double
     */
    public static function mapOldAmortiseFormat($values, $current = true)
    {

        if ($current === true) {
            return $values[date('Y')]['value'];
        }

        $old = [
            'annee'     => [],
            'annuite'   => [],
            'vcnetdeb'  => [],
            'vcnetfin'  => [],
        ];
        foreach ($values as $year => $value) {
            $old['annee'][]      = $year;
            $old['annuite'][]    = $value['annuity'];
            $old['vcnetdeb'][]   = $value['start_value'];
            $old['vcnetfin'][]   = $value['value'];
        }

        return $old;
    }

    /**
     * Calculate depreciation for an item
     *
     * @param integer $type_amort    type of depreciation "linear=2" or "degressive=1"
     * @param number  $va            valeur d'acquisition
     * @param number  $duree         acquisition value
     * @param number  $coef          amortization coefficient
     * @param string|null  $date_achat    Date of purchase
     * @param string|null  $date_use      Startup date
     * @param string|null  $date_tax      start date of fiscal year
     * @param string  $view          "n" for the current year or "all" for the complete table (default 'n')
     *
     * @return float|array|string Depreciation value or array of values. If an error occurs, return '-'.
     **/
    public static function Amort(
        $type_amort,
        $va,
        $duree,
        $coef,
        $date_achat,
        $date_use,
        $date_tax,
        $view = "n"
    ) {
        // By Jean-Mathieu Doleans qui s'est un peu pris le chou :p

        // Attention date mise en service/dateachat ->amort lineaire  et $prorata en jour !!
        // amort degressif au prorata du nombre de mois.
        // Son point de depart est le 1er jour du mois d'acquisition et non date de mise en service

        if ($type_amort == "2") {
            $values = self::linearAmortise($va, $duree, $date_tax, $date_achat, $date_use);
            if ($values == false) {
                return '-';
            }
            return self::mapOldAmortiseFormat($values, $view != 'all');
        }

        $prorata             = 0;
        $ecartfinmoiscourant = 0;
        $ecartmoisexercice   = 0;
        $date_Y  =  $date_m  =  $date_d  =  $date_H  =  $date_i  =  $date_s  =  0;
        sscanf(
            ($date_achat ?? ""),
            "%4s-%2s-%2s %2s:%2s:%2s",
            $date_Y,
            $date_m,
            $date_d,
            $date_H,
            $date_i,
            $date_s
        ); // un traitement sur la date mysql pour recuperer l'annee

        // un traitement sur la date mysql pour les infos necessaires
        $date_Y2 = $date_m2 = $date_d2 = $date_H2 = $date_i2 = $date_s2 = 0;
        sscanf(
            ($date_tax ?? ""),
            "%4s-%2s-%2s %2s:%2s:%2s",
            $date_Y2,
            $date_m2,
            $date_d2,
            $date_H2,
            $date_i2,
            $date_s2
        );

        switch ($type_amort) {
            case "1":
                //########################### Calcul amortissement degressif ###########################
                if (
                    ($va > 0)
                    && ($duree > 0)
                    && ($coef > 1)
                    && !empty($date_achat)
                ) {
                    //## calcul du prorata temporis en mois ##
                    // si l'annee fiscale debute au dela de l'annee courante
                    if ($date_m > $date_m2) {
                        $date_m2 += 12;
                    }
                    $ecartmois      = ($date_m2 - $date_m) + 1; // calcul ecart entre mois d'acquisition
                    // et debut annee fiscale
                    $prorata        = $ecartfinmoiscourant + $ecartmois - $ecartmoisexercice;
                    // calcul tableau d'amortissement ##
                    $txlineaire     = (100 / $duree); // calcul du taux lineaire virtuel
                    $txdegressif    = $txlineaire * $coef; // calcul du taux degressif
                    $dureelineaire  = (int) (100 / $txdegressif); // calcul de la duree de l'amortissement
                    // en mode lineaire
                    $dureedegressif = $duree - $dureelineaire; // calcul de la duree de l'amortissement
                    // en mode degressif
                    $mrt            = $va;

                    $tab = [
                        'annee'    => [],
                        'vcnetdeb' => [],
                        'vcnetfin' => [],
                        'annuite'  => [],
                    ];

                    // amortissement degressif pour les premieres annees
                    for ($i = 1; $i <= $dureedegressif; $i++) {
                        $tab['annee'][$i]    = $date_Y + $i - 1;
                        $tab['vcnetdeb'][$i] = $mrt; // Pour chaque annee on calcule la valeur comptable nette
                        // de debut d'exercice
                        $tab['annuite'][$i]  = $tab['vcnetdeb'][$i] * $txdegressif / 100;
                        $tab['vcnetfin'][$i] = $mrt - $tab['annuite'][$i]; //Pour chaque annee on calcule la valeur
                        //comptable nette de fin d'exercice
                        // calcul de la premiere annuite si prorata temporis
                        if ($prorata > 0) {
                            $tab['annuite'][1]  = ($va * $txdegressif / 100) * ($prorata / 12);
                            $tab['vcnetfin'][1] = $va - $tab['annuite'][1];
                        }
                        $mrt = $tab['vcnetfin'][$i];
                    }
                    // amortissement en lineaire pour les derneres annees
                    if ($dureelineaire != 0) {
                        $txlineaire = (100 / $dureelineaire); // calcul du taux lineaire
                    } else {
                        $txlineaire = 100;
                    }
                    $annuite = ($tab['vcnetfin'][$dureedegressif] * $txlineaire) / 100; // calcul de l'annuite
                    $mrt     = $tab['vcnetfin'][$dureedegressif];
                    for ($i = $dureedegressif + 1; $i <= $dureedegressif + $dureelineaire; $i++) {
                        $tab['annee'][$i]    = $date_Y + $i - 1;
                        $tab['annuite'][$i]  = $annuite;
                        $tab['vcnetdeb'][$i] = $mrt; // Pour chaque annee on calcule la valeur comptable nette
                        // de debut d'exercice
                        $tab['vcnetfin'][$i] = abs(($mrt - $annuite)); // Pour chaque annee on calcule la valeur
                        // comptable nette de fin d'exercice
                        $mrt                 = $tab['vcnetfin'][$i];
                    }
                    // calcul de la derniere annuite si prorata temporis
                    if ($prorata > 0) {
                        $tab['annuite'][$duree] = $tab['vcnetdeb'][$duree];
                        if (isset($tab['vcnetfin'][$duree - 1])) {
                            $tab['vcnetfin'][$duree] = ($tab['vcnetfin'][$duree - 1] - $tab['annuite'][$duree]);
                        } else {
                            $tab['vcnetfin'][$duree] = 0;
                        }
                    }
                } else {
                    return "-";
                }
                break;

            default:
                return "-";
        }

        // le return
        if ($view == "all") {
            // on retourne le tableau complet
            return $tab;
        }
        // on retourne juste la valeur residuelle
        // si on ne trouve pas l'annee en cours dans le tableau d'amortissement dans le tableau,
        // le materiel est amorti
        if (!array_search(date("Y"), $tab["annee"])) {
            $vnc = 0;
        } elseif (
            mktime(0, 0, 0, $date_m2, $date_d2, (int) date("Y"))
                 - mktime(0, 0, 0, (int) date("m"), (int) date("d"), (int) date("Y")) < 0
        ) {
            // on a depasse la fin d'exercice de l'annee en cours
            //on prend la valeur residuelle de l'annee en cours
            $vnc = $tab["vcnetfin"][array_search(date("Y"), $tab["annee"])];
        } else {
            // on se situe avant la fin d'exercice
            // on prend la valeur residuelle de l'annee n-1
            $vnc = $tab["vcnetdeb"][array_search(date("Y"), $tab["annee"])];
        }
        return $vnc;
    }


    /**
     * Show Infocom form for an item (not a standard showForm)
     *
     * @param $item                  CommonDBTM object
     * @param $withtemplate integer  template or basic item (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        // Show Infocom or blank form
        if (!self::canView()) {
            return false;
        }

        $dev_ID   = $item->getField('id');
        $ic       = new self();

        if (in_array($item->getType(), self::getExcludedTypes())) {
            echo "<div class='firstbloc center'>"
                . __s('For this type of item, the financial and administrative information are only a model for the items which you should add.')
                . "</div>";
        }

        $ic->getFromDBforDevice($item->getType(), $dev_ID);
        $can_input = [
            'itemtype'    => $item->getType(),
            'items_id'    => $dev_ID,
            'entities_id' => $item->getEntityID(),
        ];
        TemplateRenderer::getInstance()->display('components/infocom.html.twig', [
            'item'              => $item,
            'infocom'           => $ic,
            'withtemplate'      => $withtemplate,
            'can_create'        => $ic->can(-1, CREATE, $can_input),
            'can_edit'          => ($ic->canEdit($ic->fields['id']) && ($withtemplate != 2)),
            'can_global_update' => Session::haveRight(self::$rightname, UPDATE),
            'can_global_purge'  => Session::haveRight(self::$rightname, PURGE),
        ]);
    }

    /**
     * @param $itemtype
     **/
    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $specific_itemtype = '';
        $beforejoin        = [];

        switch ($itemtype) {
            case 'CartridgeItem':
                // Return the infocom linked to the license, not the template linked to the software
                $beforejoin        = ['table'      => 'glpi_cartridges',
                    'joinparams' => ['jointype' => 'child'],
                ];
                $specific_itemtype = 'Cartridge';
                break;

            case 'ConsumableItem':
                // Return the infocom linked to the license, not the template linked to the software
                $beforejoin        = ['table'      => 'glpi_consumables',
                    'joinparams' => ['jointype' => 'child'],
                ];
                $specific_itemtype = 'Consumable';
                break;
        }

        $joinparams        = ['jointype'          => 'itemtype_item',
            'specific_itemtype' => $specific_itemtype,
        ];
        $complexjoinparams = [];
        if (count($beforejoin)) {
            $complexjoinparams['beforejoin'][] = $beforejoin;
            $joinparams['beforejoin']          = $beforejoin;
        }
        $complexjoinparams['beforejoin'][] = ['table'      => 'glpi_infocoms',
            'joinparams' => $joinparams,
        ];

        $tab = [];

        $tab[] = [
            'id'                 => 'financial',
            'name'               => __('Financial and administrative information'),
        ];

        $tab[] = [
            'id'                 => '25',
            'table'              => 'glpi_infocoms',
            'field'              => 'immo_number',
            'name'               => __('Immobilization number'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '26',
            'table'              => 'glpi_infocoms',
            'field'              => 'order_number',
            'name'               => __('Order number'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => 'glpi_infocoms',
            'field'              => 'delivery_number',
            'name'               => __('Delivery form'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => 'glpi_infocoms',
            'field'              => 'bill',
            'name'               => __('Invoice number'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '37',
            'table'              => 'glpi_infocoms',
            'field'              => 'buy_date',
            'name'               => __('Date of purchase'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '38',
            'table'              => 'glpi_infocoms',
            'field'              => 'use_date',
            'name'               => __('Startup date'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '142',
            'table'              => 'glpi_infocoms',
            'field'              => 'delivery_date',
            'name'               => __('Delivery date'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '124',
            'table'              => 'glpi_infocoms',
            'field'              => 'order_date',
            'name'               => __('Order date'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '123',
            'table'              => 'glpi_infocoms',
            'field'              => 'warranty_date',
            'name'               => __('Start date of warranty'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '125',
            'table'              => 'glpi_infocoms',
            'field'              => 'inventory_date',
            'name'               => __('Date of last physical inventory'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => 'glpi_budgets',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => Budget::getTypeName(1),
            'forcegroupby'       => true,
            'joinparams'         => $complexjoinparams,
        ];

        $tab[] = [
            'id'                 => '51',
            'table'              => 'glpi_infocoms',
            'field'              => 'warranty_duration',
            'name'               => __('Warranty duration'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'number',
            'unit'               => 'month',
            'max'                => '120',
            'toadd'              => [
                '-1'                 => __('Lifelong'),
            ],
        ];

        $tab[] = [
            'id'                 => '52',
            'table'              => 'glpi_infocoms',
            'field'              => 'warranty_info',
            'name'               => __('Warranty information'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '120',
            'table'              => 'glpi_infocoms',
            'field'              => 'end_warranty',
            'name'               => __('Warranty expiration date'),
            'datatype'           => 'date_delay',
            'datafields'         => [
                '1'                  => 'warranty_date',
                '2'                  => 'warranty_duration',
            ],
            'searchunit'         => 'MONTH',
            'delayunit'          => 'MONTH',
            'maybefuture'        => true,
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '53',
            'table'              => 'glpi_suppliers',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => Supplier::getTypeName(1),
            'forcegroupby'       => true,
            'joinparams'         => $complexjoinparams,
        ];

        $tab[] = [
            'id'                 => '54',
            'table'              => 'glpi_infocoms',
            'field'              => 'value',
            'name'               => _x('price', 'Value'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '55',
            'table'              => 'glpi_infocoms',
            'field'              => 'warranty_value',
            'name'               => __('Warranty extension value'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '56',
            'table'              => 'glpi_infocoms',
            'field'              => 'sink_time',
            'name'               => __('Amortization duration'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'number',
            'max'                => '15',
            'unit'               => 'year',
        ];

        $tab[] = [
            'id'                 => '57',
            'table'              => 'glpi_infocoms',
            'field'              => 'sink_type',
            'name'               => __('Amortization type'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'specific',
            'searchequalsonfield' => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '58',
            'table'              => 'glpi_infocoms',
            'field'              => 'sink_coeff',
            'name'               => __('Amortization coefficient'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '59',
            'table'              => 'glpi_infocoms',
            'field'              => 'alert',
            'name'               => __('Email alarms'),
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '122',
            'table'              => 'glpi_infocoms',
            'field'              => 'comment',
            'name'               => __('Comments on financial and administrative information'),
            'datatype'           => 'text',
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        $tab[] = [
            'id'                 => '173',
            'table'              => 'glpi_businesscriticities',
            'field'              => 'completename',
            'name'               => _n('Business criticity', 'Business criticities', 1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'joinparams'         => $complexjoinparams,
        ];

        $tab[] = [
            'id'                 => '159',
            'table'              => 'glpi_infocoms',
            'field'              => 'decommission_date',
            'name'               => __('Decommission date'),
            'datatype'           => 'date',
            'maybefuture'        => true,
            'forcegroupby'       => true,
            'joinparams'         => $joinparams,
        ];

        return $tab;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'buy_date',
            'name'               => __('Date of purchase'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'use_date',
            'name'               => __('Startup date'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => $this->getTable(),
            'field'              => 'delivery_date',
            'name'               => __('Delivery date'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => $this->getTable(),
            'field'              => 'order_date',
            'name'               => __('Order date'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '25',
            'table'              => $this->getTable(),
            'field'              => 'warranty_date',
            'name'               => __('Start date of warranty'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => $this->getTable(),
            'field'              => 'inventory_date',
            'name'               => __('Date of last physical inventory'),
            'datatype'           => 'date',
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => $this->getTable(),
            'field'              => 'decommission_date',
            'name'               => __('Decommission date'),
            'maybefuture'        => true,
            'datatype'           => 'date',
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'warranty_duration',
            'name'               => __('Warranty duration'),
            'datatype'           => 'number',
            'unit'               => 'month',
            'max'                => '120',
            'toadd'              => [
                '-1'                 => __('Lifelong'),
            ],
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'warranty_info',
            'name'               => __('Warranty information'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'warranty_value',
            'name'               => __('Warranty extension value'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => 'glpi_suppliers',
            'field'              => 'name',
            'name'               => Supplier::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'order_number',
            'name'               => __('Order number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'delivery_number',
            'name'               => __('Delivery form'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'immo_number',
            'name'               => __('Immobilization number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => _x('price', 'Value'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'sink_time',
            'name'               => __('Amortization duration'),
            'datatype'           => 'number',
            'max'                => '15',
            'unit'               => 'year',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'sink_type',
            'name'               => __('Amortization type'),
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => 'sink_coeff',
            'name'               => __('Amortization coefficient'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => $this->getTable(),
            'field'              => 'bill',
            'name'               => __('Invoice number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => 'glpi_budgets',
            'field'              => 'name',
            'name'               => Budget::getTypeName(1),
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'infocom_types',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('ID'),
            'datatype'           => 'integer',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => $this->getTable(),
            'field'              => 'alert',
            'name'               => __('Alarms on financial and administrative information'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '173',
            'table'              => 'glpi_businesscriticities',
            'field'              => 'completename',
            'name'               => _n('Business criticity', 'Business criticities', 1),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }


    /**
     * Get date using a begin date and a period in month
     *
     * @param string  $from          begin date
     * @param integer $addwarranty   period in months
     * @param integer $deletenotice  period in months of notice (default 0)
     * @param boolean $color         if show expire date in red color (false by default)
     * @param boolean $auto_renew
     * @param integer $periodicity   renewal periodicity in month if different from addwarranty
     *
     * @return string Expiration date automatically converted to the user's preferred date format.
     *                The returned value is a safe HTML string.
     **/
    public static function getWarrantyExpir($from, $addwarranty, $deletenotice = 0, $color = false, $auto_renew = false, $periodicity = 0)
    {

        // Life warranty
        if (
            ($addwarranty == -1)
            && ($deletenotice == 0)
        ) {
            return __s('Never');
        }

        if (empty($from)) {
            return "";
        }

        $timestamp = strtotime("$from+$addwarranty month -$deletenotice month");

        $periodicity = ($periodicity > 0) ? $periodicity : $addwarranty;

        if ($auto_renew && $periodicity > 0) {
            while ($timestamp < strtotime($_SESSION['glpi_currenttime'])) {
                $datetime = new DateTime();
                $datetime->setTimestamp($timestamp);
                $timestamp = strtotime($datetime->format("Y-m-d H:i:s") . "+$periodicity month");
            }
        }

        if ($color && ($timestamp < strtotime($_SESSION['glpi_currenttime']))) {
            return "<span class='red'>" . htmlescape(Html::convDate(date("Y-m-d", $timestamp))) . "</span>";
        }
        return htmlescape(Html::convDate(date("Y-m-d", $timestamp)));
    }


    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {

        $action_name = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'activate';

        if (
            Infocom::canApplyOn($itemtype)
            && static::canCreate()
        ) {
            $actions[$action_name] = "<i class='" . htmlescape(self::getIcon()) . "'></i>"
                                  . __s('Enable the financial and administrative information');
        }
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'activate':
                $ic = new self();
                if ($ic->canCreate()) {
                    $itemtype = $item->getType();
                    foreach ($ids as $key) {
                        if (!$ic->getFromDBforDevice($itemtype, $key)) {
                            $input = ['itemtype' => $itemtype,
                                'items_id' => $key,
                            ];
                            if ($ic->can(-1, CREATE, $input)) {
                                if ($ic->add($input)) {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                                } else {
                                    $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                    $ma->addMessage($ic->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                                $ma->addMessage($ic->getErrorMessage(ERROR_RIGHT));
                            }
                        } else {
                            // Infocom already exists for this item, nothing to do.
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * @since 9.1.7
     * @see CommonDBChild::canUpdateItem()
     **/
    public function canUpdateItem(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }


    /**
     * @since 9.1.7
     * @see CommonDBChild::canPurgeItem()
     **/
    public function canPurgeItem(): bool
    {
        return Session::haveRight(static::$rightname, PURGE);
    }


    /**
     * @since 9.1.7
     * @see CommonDBChild::canCreateItem()
     **/
    public function canCreateItem(): bool
    {
        return Session::haveRight(static::$rightname, CREATE);
    }

    /**
     * Get item types
     *
     * @since 9.3.1
     *
     * @param array $where Where clause
     *
     * @return DBmysqlIterator
     */
    public static function getTypes($where)
    {
        global $DB;

        $types_iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_infocoms',
            'WHERE'           => [
                'NOT'          => ['itemtype' => self::getExcludedTypes()],
            ] + $where,
            'ORDER'           => 'itemtype',
        ]);
        return $types_iterator;
    }


    /**
     * Get excluded itemtypes
     *
     * @since 9.3.1
     *
     * @return array
     */
    public static function getExcludedTypes()
    {
        return ['ConsumableItem', 'CartridgeItem'];
    }


    public static function getIcon()
    {
        return "ti ti-wallet";
    }
}
