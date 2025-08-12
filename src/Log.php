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
use Glpi\DBAL\QueryParam;
use Glpi\RichText\RichText;
use Glpi\Search\SearchOption;

use function Safe\preg_match;

/**
 * Log Class
 **/
class Log extends CommonDBTM
{
    public const HISTORY_ADD_DEVICE         = 1;
    public const HISTORY_UPDATE_DEVICE      = 2;
    public const HISTORY_DELETE_DEVICE      = 3;
    public const HISTORY_INSTALL_SOFTWARE   = 4;
    public const HISTORY_UNINSTALL_SOFTWARE = 5;
    public const HISTORY_DISCONNECT_DEVICE  = 6;
    public const HISTORY_CONNECT_DEVICE     = 7;
    public const HISTORY_LOCK_DEVICE        = 8;
    public const HISTORY_UNLOCK_DEVICE      = 9;

    public const HISTORY_LOG_SIMPLE_MESSAGE = 12;
    public const HISTORY_DELETE_ITEM        = 13;
    public const HISTORY_RESTORE_ITEM       = 14;
    public const HISTORY_ADD_RELATION       = 15;
    public const HISTORY_DEL_RELATION       = 16;
    public const HISTORY_ADD_SUBITEM        = 17;
    public const HISTORY_UPDATE_SUBITEM     = 18;
    public const HISTORY_DELETE_SUBITEM     = 19;
    public const HISTORY_CREATE_ITEM        = 20;
    public const HISTORY_UPDATE_RELATION    = 21;
    public const HISTORY_LOCK_RELATION      = 22;
    public const HISTORY_LOCK_SUBITEM       = 23;
    public const HISTORY_UNLOCK_RELATION    = 24;
    public const HISTORY_UNLOCK_SUBITEM     = 25;
    public const HISTORY_LOCK_ITEM          = 26;
    public const HISTORY_UNLOCK_ITEM        = 27;

    public const HISTORY_SEND_WEBHOOK       = 28;

    // Plugin must use value starting from
    public const HISTORY_PLUGIN             = 1000;

    public static $rightname = 'logs';

    /** @var array  */
    public static array $queue = [];
    /** @var bool  */
    public static bool $use_queue = false;


    public static function getTypeName($nb = 0)
    {
        return __('Historical');
    }

    public static function getIcon()
    {
        return 'ti ti-history';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!self::canView()) {
            return '';
        }

        $nb = 0;
        if (
            $_SESSION['glpishow_count_on_tabs']
            && ($item instanceof CommonDBTM)
        ) {
            $nb = countElementsInTable(
                'glpi_logs',
                ['itemtype' => $item->getType(),
                    'items_id' => $item->getID(),
                ]
            );
        }
        return self::createTabEntry(self::getTypeName(1), $nb, $item::getType());
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        self::showForItem($item);
        return true;
    }


    /**
     * Construct  history for an item
     *
     * @param $item               CommonDBTM object
     * @param $oldvalues    array of old values updated
     * @param $values       array of all values of the item
     *
     * @return boolean for success (at least 1 log entry added)
     **/
    public static function constructHistory(CommonDBTM $item, $oldvalues, $values)
    {

        if (!count($oldvalues)) {
            return false;
        }
        // needed to have  $SEARCHOPTION
        [$real_type, $real_id] = $item->getLogTypeID();
        $searchopt = SearchOption::getOptionsForItemtype($real_type);
        $result = 0;

        foreach ($oldvalues as $key => $oldval) {
            if (in_array($key, $item->getNonLoggedFields())) {
                continue;
            }
            $changes = [];

            // Parsing $SEARCHOPTION to find changed field
            foreach ($searchopt as $key2 => $val2) {
                if (!isset($val2['table'])) {
                    // skip sub-title
                    continue;
                }
                // specific for profile
                if (
                    ($item->getType() == 'ProfileRight')
                    && ($key == 'rights')
                ) {
                    if (
                        isset($val2['rightname'])
                        && ($val2['rightname'] == $item->fields['name'])
                    ) {
                        $id_search_option = $key2;
                        $changes          =  [$id_search_option, $oldval ?? '', $values[$key] ?? ''];
                    }
                } elseif (
                    ($val2['linkfield'] == $key && $real_type === $item->getType())
                       || ($key == $val2['field'] && $val2['table'] == $item->getTable())
                       || ($val2['linkfield'] == $key && $item->getType() == 'Infocom')
                ) {
                    // Linkfield or standard field not massive action enable
                    $id_search_option = $key2; // Give ID of the $SEARCHOPTION

                    if ($val2['table'] == $item->getTable()) {
                        $changes = [$id_search_option, $oldval ?? '', $values[$key] ?? ''];
                    } else {
                        // other cases; link field -> get data from dropdown
                        $changes = [$id_search_option,
                            sprintf(
                                __('%1$s (%2$s)'),
                                Dropdown::getDropdownName(
                                    $val2["table"],
                                    $oldval
                                ),
                                $oldval
                            ),
                            sprintf(
                                __('%1$s (%2$s)'),
                                Dropdown::getDropdownName(
                                    $val2["table"],
                                    $values[$key]
                                ),
                                $values[$key]
                            ),
                            $oldval,
                            (int) $values[$key],
                        ];
                    }
                    break;
                }
            }
            if (count($changes)) {
                $result = self::history($real_id, $real_type, $changes);
            }
        }
        return $result;
    }


    /**
     * Log history
     *
     * @param $items_id
     * @param $itemtype
     * @param $changes
     * @param $itemtype_link   (default '')
     * @param $linked_action   (default '0')
     *
     * @return boolean success
     **/
    public static function history($items_id, $itemtype, $changes, $itemtype_link = '', $linked_action = '0')
    {
        global $DB;

        $date_mod = $_SESSION["glpi_currenttime"];
        if (empty($changes)) {
            return false;
        }

        // create a query to insert history
        $id_search_option = $changes[0];
        $old_value        = $changes[1] ?? '';
        $new_value        = $changes[2] ?? '';
        $old_id           = $changes[3] ?? null;
        $new_id           = $changes[4] ?? null;

        // Remove json values
        $decoded_old_value = json_decode($old_value); //@phpstan-ignore theCodingMachineSafe.function
        $decoded_new_value = json_decode($new_value); //@phpstan-ignore theCodingMachineSafe.function
        if (is_array($decoded_old_value) || is_object($decoded_old_value)) {
            $old_value = '';
        }
        if (is_array($decoded_new_value) || is_object($decoded_new_value)) {
            $new_value = '';
        }

        if ($uid = Session::getLoginUserID(false)) {
            if (is_numeric($uid)) {
                $username = User::getNameForLog($uid);
            } else { // For cron management
                $username = $uid;
            }
        } else {
            $username = "";
        }

        if (Session::isImpersonateActive()) {
            $impersonator_id = Session::getImpersonatorId();
            $username = sprintf(
                __('%1$s impersonated by %2$s'),
                $username,
                User::getNameForLog($impersonator_id)
            );
        }

        // Security to be sure that values do not pass over the max length
        $old_value = mb_substr($old_value, 0, 255);
        $new_value = mb_substr($new_value, 0, 255);

        $params = [
            'items_id'          => $items_id,
            'itemtype'          => $itemtype,
            'itemtype_link'     => $itemtype_link,
            'linked_action'     => $linked_action,
            'user_name'         => $username,
            'date_mod'          => $date_mod,
            'id_search_option'  => $id_search_option,
            'old_value'         => $old_value,
            'old_id'            => $old_id,
            'new_value'         => $new_value,
            'new_id'            => $new_id,
        ];

        if (static::$use_queue) {
            //use queue rather than direct insert
            static::$queue[] = $params;
            return true;
        }

        $result = $DB->insert(self::getTable(), $params);

        if ($result && $DB->affectedRows() > 0) {
            return $_SESSION['glpi_maxhistory'] = $DB->insertId();
        }
        return false;
    }


    /**
     * Show History of an item
     *
     * @param $item                     CommonDBTM object
     * @param $withtemplate    integer  withtemplate param (default 0)
     *
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        $itemtype = $item->getType();
        $items_id = $item->getField('id');

        $start       = intval(($_GET["start"] ?? 0));
        $filters     = $_GET['filters'] ?? [];
        $is_filtered = count($filters) > 0;
        $sql_filters = self::convertFiltersValuesToSqlCriteria($filters);

        // Total Number of events
        $total_number    = countElementsInTable("glpi_logs", ['items_id' => $items_id, 'itemtype' => $itemtype ]);
        $filtered_number = countElementsInTable("glpi_logs", ['items_id' => $items_id, 'itemtype' => $itemtype ] + $sql_filters);

        TemplateRenderer::getInstance()->display('components/logs.html.twig', [
            'total_number'      => $total_number,
            'filtered_number'   => $filtered_number,
            'logs'              => $filtered_number > 0
            ? self::getHistoryData($item, $start, $_SESSION['glpilist_limit'], $sql_filters)
            : [],
            'start'             => $start,
            'href'              => $item::getFormURLWithID($items_id),
            'additional_params' => $is_filtered ? http_build_query(['filters' => $filters]) : "",
            'is_tab'            => true,
            'items_id'          => $items_id,
            'filters'           => $filters,
            'user_names'        => $is_filtered
            ? Log::getDistinctUserNamesValuesInItemLog($item)
            : [],
            'affected_fields'   => $is_filtered
            ? Log::getDistinctAffectedFieldValuesInItemLog($item)
            : [],
            'linked_actions'    => $is_filtered
            ? Log::getDistinctLinkedActionValuesInItemLog($item)
            : [],
            'csv_url'           => $CFG_GLPI['root_doc'] . "/front/log/export.php?" . http_build_query([
                'filter'   => $filters,
                'itemtype' => $item::getType(),
                'id'       => $item->getId(),
            ]),
        ]);
        ;
    }

    /**
     * Retrieve last history Data for an item
     *
     * @param CommonDBTM $item       Object instance
     * @param integer    $start      First line to retrieve (default 0)
     * @param integer    $limit      Max number of line to retrieve (0 for all) (default 0)
     * @param array      $sqlfilters SQL filters applied to history (default [])
     *
     * @return array of log entries, each containing the following keys:
     *      - int id: the           id of the entry in the `glpi_logs` table
     *      - bool display_history: whether the data should be displayed in the history tab
     *      - string date_mod:      the entry date
     *      - string user_name:     the name of the user that made the change
     *      - string field:         the name of the updated field
     *      - string change:        the description of the change (contains HTML)
     */
    public static function getHistoryData(CommonDBTM $item, $start = 0, $limit = 0, array $sqlfilters = [])
    {
        $DBread = DBConnection::getReadConnection();

        $itemtype  = $item->getType();
        $items_id  = $item->getField('id');
        $itemtable = $item->getTable();

        $SEARCHOPTION = SearchOption::getOptionsForItemtype($itemtype);

        $query = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'items_id'  => $items_id,
                'itemtype'  => $itemtype,
            ] + $sqlfilters,
            'ORDER'  => 'id DESC',
        ];

        if ($limit) {
            $query['START'] = (int) $start;
            $query['LIMIT'] = (int) $limit;
        }

        $iterator = $DBread->request($query);

        $changes = [];
        foreach ($iterator as $data) {
            $tmp = [];

            $tmp['display_history'] = true;
            $tmp['id']              = $data["id"];
            $tmp['date_mod']        = $data["date_mod"];
            $tmp['user_name']       = $data["user_name"];
            $tmp['field']           = "";
            $tmp['change']          = "";
            $tmp['datatype']        = "";

            // This is an internal device ?
            if ($data["linked_action"]) {
                $action_label = self::getLinkedActionLabel($data["linked_action"]);

                // Yes it is an internal device
                switch ($data["linked_action"]) {
                    case self::HISTORY_CREATE_ITEM:
                    case self::HISTORY_DELETE_ITEM:
                    case self::HISTORY_LOCK_ITEM:
                    case self::HISTORY_UNLOCK_ITEM:
                    case self::HISTORY_RESTORE_ITEM:
                        $tmp['change'] = htmlescape($action_label);
                        break;

                    case self::HISTORY_ADD_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            if ($item2 instanceof Item_Devices) {
                                $tmp['field'] = $item2->getDeviceTypeName(1);
                            } else {
                                $tmp['field'] = $item2->getTypeName(1);
                            }
                        }
                        //TRANS: %s is the component name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["new_value"])
                        );
                        break;

                    case self::HISTORY_UPDATE_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        $linktype_field = explode('#', $data["itemtype_link"]);
                        $linktype       = $linktype_field[0];
                        $field          = $linktype_field[1];
                        $devicetype     = $linktype::getDeviceType();
                        $tmp['field']   = $devicetype;
                        $specif_fields  = $linktype::getSpecificities();
                        if (isset($specif_fields[$field]['short name'])) {
                            $tmp['field']   = $devicetype;
                            $tmp['field']  .= " (" . $specif_fields[$field]['short name'] . ")";
                        }
                        //TRANS: %1$s is the old_value, %2$s is the new_value
                        $tmp['change']  = sprintf(
                            __s('%1$s: %2$s'),
                            sprintf(
                                __s('%1$s (%2$s)'),
                                htmlescape($action_label),
                                htmlescape($tmp['field'])
                            ),
                            sprintf(
                                __s('%1$s by %2$s'),
                                htmlescape($data["old_value"]),
                                htmlescape($data[ "new_value"])
                            )
                        );
                        break;

                    case self::HISTORY_DELETE_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            if ($item2 instanceof Item_Devices) {
                                $tmp['field'] = $item2->getDeviceTypeName(1);
                            } else {
                                $tmp['field'] = $item2->getTypeName(1);
                            }
                        }
                        //TRANS: %s is the component name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["old_value"])
                        );
                        break;

                    case self::HISTORY_LOCK_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            if ($item2 instanceof Item_Devices) {
                                $tmp['field'] = $item2->getDeviceTypeName(1);
                            } else {
                                $tmp['field'] = $item2->getTypeName(1);
                            }
                        }
                        //TRANS: %s is the component name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["old_value"])
                        );
                        break;

                    case self::HISTORY_UNLOCK_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            if ($item2 instanceof Item_Devices) {
                                $tmp['field'] = $item2->getDeviceTypeName(1);
                            } else {
                                $tmp['field'] = $item2->getTypeName(1);
                            }
                        }
                        //TRANS: %s is the component name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["new_value"])
                        );
                        break;

                    case self::HISTORY_INSTALL_SOFTWARE:
                        $tmp['field']  = _n('Software', 'Software', 1);
                        //TRANS: %s is the software name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["new_value"])
                        );
                        break;

                    case self::HISTORY_UNINSTALL_SOFTWARE:
                        $tmp['field']  = _n('Software', 'Software', 1);
                        //TRANS: %s is the software name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["old_value"])
                        );
                        break;

                    case self::HISTORY_DISCONNECT_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            if ($item2 instanceof Item_Devices) {
                                $tmp['field'] = $item2->getDeviceTypeName(1);
                            } else {
                                $tmp['field'] = $item2->getTypeName(1);
                            }
                        }
                        //TRANS: %s is the item name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["old_value"])
                        );
                        break;

                    case self::HISTORY_CONNECT_DEVICE:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            if ($item2 instanceof Item_Devices) {
                                $tmp['field'] = $item2->getDeviceTypeName(1);
                            } else {
                                $tmp['field'] = $item2->getTypeName(1);
                            }
                        }
                        //TRANS: %s is the item name
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["new_value"])
                        );
                        break;

                    case self::HISTORY_LOG_SIMPLE_MESSAGE:
                        $tmp['field']  = "";
                        $tmp['change'] = htmlescape($data["new_value"]);
                        break;

                    case self::HISTORY_ADD_RELATION:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["new_value"])
                        );

                        if ($data['itemtype'] == 'Ticket') {
                            /** @var CommonITILObject $item */
                            if ($data['id_search_option']) { // Recent record - see CommonITILObject::getSearchOptionsActors()
                                $as = $SEARCHOPTION[$data['id_search_option']]['name'];
                            } else { // Old record
                                $is = $isr = $isa = $iso = false;
                                switch ($data['itemtype_link']) {
                                    case 'Group':
                                        $is = 'isGroup';
                                        break;

                                    case 'User':
                                        $is = 'isUser';
                                        break;

                                    case 'Supplier':
                                        $is = 'isSupplier';
                                        break;
                                }
                                if ($is) {
                                    $iditem = intval(substr($data['new_value'], strrpos($data['new_value'], '(') + 1)); // This is terrible idea
                                    $isr = $item->$is(CommonITILActor::REQUESTER, $iditem);
                                    $isa = $item->$is(CommonITILActor::ASSIGN, $iditem);
                                    $iso = $item->$is(CommonITILActor::OBSERVER, $iditem);
                                }
                                // Simple Heuristic, of course not enough
                                if ($isr && !$isa && !$iso) {
                                    $as = _n('Requester', 'Requesters', 1);
                                } elseif (!$isr && $isa && !$iso) {
                                    $as = __('Assigned to');
                                } elseif (!$isr && !$isa && $iso) {
                                    $as = _n('Observer', 'Observers', 1);
                                } else {
                                    // Deleted or Ambiguous
                                    $as = false;
                                }
                            }
                            if ($as) {
                                $tmp['change'] = sprintf(
                                    __s('%1$s: %2$s'),
                                    htmlescape($action_label),
                                    sprintf(
                                        __s('%1$s (%2$s)'),
                                        htmlescape($data["new_value"]),
                                        htmlescape($as)
                                    )
                                );
                            } else {
                                $tmp['change'] = sprintf(
                                    __s('%1$s: %2$s'),
                                    htmlescape($action_label),
                                    htmlescape($data["new_value"])
                                );
                            }
                        }
                        break;

                    case self::HISTORY_UPDATE_RELATION:
                        $linktype_field = explode('#', $data["itemtype_link"]);
                        $linktype     = $linktype_field[0];
                        $tmp['field'] = is_a($linktype, CommonGLPI::class, true) ? $linktype::getTypeName() : $linktype;
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            sprintf(
                                __s('%1$s (%2$s)'),
                                htmlescape($data["old_value"]),
                                htmlescape($data["new_value"])
                            )
                        );
                        break;

                    case self::HISTORY_DEL_RELATION:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["old_value"])
                        );
                        break;

                    case self::HISTORY_LOCK_RELATION:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["old_value"])
                        );
                        break;

                    case self::HISTORY_UNLOCK_RELATION:
                        $tmp['field'] = NOT_AVAILABLE;
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            htmlescape($data["new_value"])
                        );
                        break;

                    case self::HISTORY_ADD_SUBITEM:
                        $tmp['field'] = '';
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            sprintf(
                                __s('%1$s (%2$s)'),
                                htmlescape($tmp['field']),
                                htmlescape($data["new_value"])
                            )
                        );

                        break;

                    case self::HISTORY_UPDATE_SUBITEM:
                        $tmp['field'] = '';
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        if (empty($data["new_value"])) {
                            $tmp['change'] = sprintf(
                                __s('%1$s: %2$s'),
                                htmlescape($action_label),
                                htmlescape($tmp['field']),
                            );
                        } else {
                            $tmp['change'] = sprintf(
                                __s('%1$s: %2$s'),
                                htmlescape($action_label),
                                sprintf(
                                    __s('%1$s (%2$s)'),
                                    htmlescape($tmp['field']),
                                    htmlescape($data["new_value"])
                                )
                            );
                        }

                        break;

                    case self::HISTORY_DELETE_SUBITEM:
                        $tmp['field'] = '';
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            sprintf(
                                __s('%1$s (%2$s)'),
                                htmlescape($tmp['field']),
                                htmlescape($data["old_value"])
                            )
                        );
                        break;

                    case self::HISTORY_LOCK_SUBITEM:
                        $tmp['field'] = '';
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            sprintf(
                                __s('%1$s (%2$s)'),
                                htmlescape($tmp['field']),
                                htmlescape($data["old_value"])
                            )
                        );
                        break;

                    case self::HISTORY_UNLOCK_SUBITEM:
                        $tmp['field'] = '';
                        if ($item2 = getItemForItemtype($data["itemtype_link"])) {
                            $tmp['field'] = $item2->getTypeName(1);
                        }
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            sprintf(
                                __s('%1$s (%2$s)'),
                                htmlescape($tmp['field']),
                                htmlescape($data["new_value"])
                            )
                        );
                        break;

                    case self::HISTORY_SEND_WEBHOOK:
                        $tmp['change'] = sprintf(
                            __s('%1$s: %2$s'),
                            htmlescape($action_label),
                            sprintf(
                                __s('%1$s (Status %2$s -> %3$s)'),
                                htmlescape($data["itemtype_link"]),
                                htmlescape($data["old_value"]),
                                htmlescape($data["new_value"])
                            )
                        );
                        break;

                    default:
                        $fct = [$data['itemtype_link'], 'getHistoryEntry'];
                        if (
                            ($data['linked_action'] >= self::HISTORY_PLUGIN)
                            && $data['itemtype_link']
                            && is_callable($fct)
                        ) {
                            $tmp['field']  = $data['itemtype_link']::getTypeName(1);
                            $tmp['change'] = call_user_func($fct, $data);
                        }
                        $tmp['display_history'] = !empty($tmp['change']);
                }
            } else {
                $fieldname = "";
                $searchopt = [];
                $tablename = '';
                // It's not an internal device
                foreach ($SEARCHOPTION as $key2 => $val2) {
                    if ($key2 === $data["id_search_option"]) {
                        $tmp['field'] =  $val2["name"];
                        $tablename    =  $val2["table"];
                        $fieldname    = $val2["field"];
                        $searchopt    = $val2;
                        if (isset($val2['datatype'])) {
                            $tmp['datatype'] = $val2["datatype"];
                        }
                        break;
                    }
                }
                if (
                    ($itemtable == $tablename)
                    || ($tmp['datatype'] == 'right')
                ) {
                    switch ($tmp['datatype']) {
                        // specific case for text field
                        case 'text':
                            $tmp['change'] = __s('Update of the field');
                            break;

                        default:
                            $data["old_value"] = RichText::getTextFromHtml($item->getValueToDisplay($searchopt, $data["old_value"]) ?? '', false, true);
                            $data["new_value"] = RichText::getTextFromHtml($item->getValueToDisplay($searchopt, $data["new_value"]) ?? '', false, true);
                            break;
                    }
                }

                if (empty($tmp['change'])) {
                    $newval = $data["new_value"];
                    $oldval = $data["old_value"];

                    if ($data['id_search_option'] == '70') {
                        $newval_expl = explode(' ', $newval);
                        $oldval_expl = explode(' ', $oldval);

                        if ($oldval_expl[0] == '&nbsp;') {
                            $oldval = $data["old_value"];
                        } else {
                            $old_iterator = $DBread->request(['FROM' => 'glpi_users', 'WHERE' => ['name' => $oldval_expl[0]]]);
                            foreach ($old_iterator as $val) {
                                $oldval = sprintf(
                                    __('%1$s %2$s'),
                                    formatUserName(
                                        $val['id'],
                                        $oldval_expl[0],
                                        $val['realname'],
                                        $val['firstname']
                                    ),
                                    ($oldval_expl[1] ?? "0")
                                );
                            }
                        }

                        if ($newval_expl[0] == '&nbsp;') {
                            $newval = $data["new_value"];
                        } else {
                            $new_iterator = $DBread->request(['FROM' => 'glpi_users', 'WHERE' => ['name' => $newval_expl[0]]]);
                            foreach ($new_iterator as $val) {
                                $newval = sprintf(
                                    __('%1$s %2$s'),
                                    formatUserName(
                                        $val['id'],
                                        $newval_expl[0],
                                        $val['realname'],
                                        $val['firstname']
                                    ),
                                    ($newval_expl[1] ?? "0")
                                );
                            }
                        }
                    }
                    $tmp['change'] = sprintf(
                        __s('Change %1$s to %2$s'),
                        '<del>' . htmlescape($oldval) . '</del>',
                        '<ins>' . htmlescape($newval) . '</ins>'
                    );
                }
            }
            $changes[] = $tmp;
        }
        return $changes;
    }

    /**
     * Retrieve distinct values for user_name field in item log.
     * Return is made to be used as select tag options.
     *
     * @param CommonDBTM $item  Object instance
     *
     * @return array
     *
     * @since 9.3
     **/
    public static function getDistinctUserNamesValuesInItemLog(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getField('id');

        $iterator = $DB->request([
            'SELECT'          => 'user_name',
            'DISTINCT'        => true,
            'FROM'            => self::getTable(),
            'WHERE'  => [
                'items_id'  => $items_id,
                'itemtype'  => $itemtype,
            ],
            'ORDER'  => 'id DESC',
        ]);

        $values = [];
        foreach ($iterator as $data) {
            if (empty($data['user_name'])) {
                continue;
            }
            $values[$data['user_name']] = $data['user_name'];
        }

        asort($values, SORT_NATURAL | SORT_FLAG_CASE);

        return $values;
    }

    /**
     * Retrieve distinct values for affected field in item log.
     * Return is made to be used as select tag options.
     *
     * @param CommonDBTM $item  Object instance
     *
     * @return array
     *
     * @since 9.3
     **/
    public static function getDistinctAffectedFieldValuesInItemLog(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getField('id');

        $affected_fields = ['linked_action', 'itemtype_link', 'id_search_option'];

        $iterator = $DB->request([
            'SELECT'  => $affected_fields,
            'FROM'    => self::getTable(),
            'WHERE'   => [
                'items_id'  => $items_id,
                'itemtype'  => $itemtype,
            ],
            'GROUPBY' => $affected_fields,
            'ORDER'   => 'id DESC',
        ]);

        $values = [];
        foreach ($iterator as $data) {
            $key = null;
            $value = null;

            // This is an internal device ?
            if ($data["linked_action"]) {
                // Yes it is an internal device
                switch ($data["linked_action"]) {
                    case self::HISTORY_ADD_DEVICE:
                    case self::HISTORY_DELETE_DEVICE:
                    case self::HISTORY_LOCK_DEVICE:
                    case self::HISTORY_UNLOCK_DEVICE:
                    case self::HISTORY_DISCONNECT_DEVICE:
                    case self::HISTORY_CONNECT_DEVICE:
                    case self::HISTORY_ADD_RELATION:
                    case self::HISTORY_DEL_RELATION:
                    case self::HISTORY_LOCK_RELATION:
                    case self::HISTORY_UNLOCK_RELATION:
                    case self::HISTORY_ADD_SUBITEM:
                    case self::HISTORY_UPDATE_SUBITEM:
                    case self::HISTORY_DELETE_SUBITEM:
                    case self::HISTORY_UPDATE_RELATION:
                    case self::HISTORY_LOCK_SUBITEM:
                    case self::HISTORY_UNLOCK_SUBITEM:
                        $linked_action_values = [
                            self::HISTORY_ADD_DEVICE,
                            self::HISTORY_DELETE_DEVICE,
                            self::HISTORY_LOCK_DEVICE,
                            self::HISTORY_UNLOCK_DEVICE,
                            self::HISTORY_DISCONNECT_DEVICE,
                            self::HISTORY_CONNECT_DEVICE,
                            self::HISTORY_ADD_RELATION,
                            self::HISTORY_UPDATE_RELATION,
                            self::HISTORY_DEL_RELATION,
                            self::HISTORY_LOCK_RELATION,
                            self::HISTORY_UNLOCK_RELATION,
                            self::HISTORY_ADD_SUBITEM,
                            self::HISTORY_UPDATE_SUBITEM,
                            self::HISTORY_DELETE_SUBITEM,
                            self::HISTORY_LOCK_SUBITEM,
                            self::HISTORY_UNLOCK_SUBITEM,
                        ];
                        $key = 'linked_action::' . implode(',', $linked_action_values) . ';'
                        . 'itemtype_link::' . $data['itemtype_link'] . ';';

                        if ($linked_item = getItemForItemtype($data["itemtype_link"])) {
                            if ($linked_item instanceof Item_Devices) {
                                $value = $linked_item->getDeviceTypeName(1);
                            } else {
                                $value = $linked_item->getTypeName(1);
                            }
                        }
                        break;

                    case self::HISTORY_UPDATE_DEVICE:
                        $key = 'linked_action::' . self::HISTORY_UPDATE_DEVICE . ';'
                        . 'itemtype_link::' . $data['itemtype_link'] . ';';

                        $linktype_field = explode('#', $data["itemtype_link"]);
                        $linktype       = $linktype_field[0];
                        $field          = $linktype_field[1];
                        $devicetype     = $linktype::getDeviceType();
                        $specif_fields  = $linktype::getSpecificities();

                        $value = $devicetype;
                        if (isset($specif_fields[$field]['short name'])) {
                            $value .= " (" . $specif_fields[$field]['short name'] . ")";
                        }
                        break;

                    case self::HISTORY_INSTALL_SOFTWARE:
                    case self::HISTORY_UNINSTALL_SOFTWARE:
                        $linked_action_values = [
                            self::HISTORY_INSTALL_SOFTWARE,
                            self::HISTORY_UNINSTALL_SOFTWARE,
                        ];
                        $key = 'linked_action::' . implode(',', $linked_action_values) . ';';

                        $value = _n('Software', 'Software', 1);
                        break;

                    default:
                        $linked_action_values_to_exclude = [
                            0, //Exclude lines corresponding to no action.
                            self::HISTORY_ADD_DEVICE,
                            self::HISTORY_DELETE_DEVICE,
                            self::HISTORY_LOCK_DEVICE,
                            self::HISTORY_UNLOCK_DEVICE,
                            self::HISTORY_DISCONNECT_DEVICE,
                            self::HISTORY_CONNECT_DEVICE,
                            self::HISTORY_ADD_RELATION,
                            self::HISTORY_UPDATE_RELATION,
                            self::HISTORY_DEL_RELATION,
                            self::HISTORY_LOCK_RELATION,
                            self::HISTORY_UNLOCK_RELATION,
                            self::HISTORY_ADD_SUBITEM,
                            self::HISTORY_UPDATE_SUBITEM,
                            self::HISTORY_DELETE_SUBITEM,
                            self::HISTORY_LOCK_SUBITEM,
                            self::HISTORY_UNLOCK_SUBITEM,
                            self::HISTORY_UPDATE_DEVICE,
                            self::HISTORY_INSTALL_SOFTWARE,
                            self::HISTORY_UNINSTALL_SOFTWARE,
                        ];

                        $key = 'linked_action:NOT:' . implode(',', $linked_action_values_to_exclude) . ';';
                        $value = __('Others');
                        break;
                }
            } else {
                // It's not an internal device
                $opts = SearchOption::getOptionsForItemtype($itemtype);
                foreach ($opts as $search_opt_key => $search_opt_val) {
                    if ($search_opt_key == $data["id_search_option"]) {
                        $key = 'id_search_option::' . $data['id_search_option'] . ';';
                        $value = $search_opt_val["name"];
                        break;
                    }
                }
            }

            if (null !== $key && null !== $value) {
                $values[$key] = $value;
            }
        }

        uasort(
            $values,
            function ($a, $b) {
                $other = __('Others');
                if ($a === $other) {
                    return 1;
                } elseif ($b === $other) {
                    return -1;
                }

                return strcmp($a, $b);
            }
        );

        return $values;
    }

    /**
     * Retrieve distinct values for action in item log.
     * Return is made to be used as select tag options.
     *
     * @param CommonDBTM $item  Object instance
     *
     * @return array
     *
     * @since 9.3
     **/
    public static function getDistinctLinkedActionValuesInItemLog(CommonDBTM $item)
    {
        global $DB;

        $itemtype = $item->getType();
        $items_id = $item->getField('id');

        $iterator = $DB->request([
            'SELECT'          => 'linked_action',
            'DISTINCT'        => true,
            'FROM'            => self::getTable(),
            'WHERE'  => [
                'items_id'  => $items_id,
                'itemtype'  => $itemtype,
            ],
            'ORDER'           => 'id DESC',
        ]);

        $values = [];
        foreach ($iterator as $data) {
            $key = $data["linked_action"];
            $value = null;

            // This is an internal device ?
            if ($data["linked_action"]) {
                $value = self::getLinkedActionLabel($data["linked_action"]);

                if (null === $value) {
                    $key = 'other';
                    $value = __('Others');
                }
            } else {
                $value = __('Update a field');
            }

            if (null !== $value) {
                $values[$key] = $value;
            }
        }

        uasort(
            $values,
            function ($a, $b) {
                $other = __('Others');
                if ($a === $other) {
                    return 1;
                } elseif ($b === $other) {
                    return -1;
                }

                return strcmp($a, $b);
            }
        );

        return $values;
    }

    /**
     * Returns label corresponding to the linked action of a log entry.
     *
     * @param integer $linked_action  Linked action value of a log entry.
     *
     * @return string
     *
     * @since 9.3
     **/
    public static function getLinkedActionLabel($linked_action)
    {
        $label = null;

        switch ($linked_action) {
            case self::HISTORY_CREATE_ITEM:
                $label = __('Add the item');
                break;

            case self::HISTORY_DELETE_ITEM:
                $label = __('Delete the item');
                break;

            case self::HISTORY_LOCK_ITEM:
                $label = __('Lock the item');
                break;

            case self::HISTORY_UNLOCK_ITEM:
                $label = __('Unlock the item');
                break;

            case self::HISTORY_RESTORE_ITEM:
                $label = __('Restore the item');
                break;

            case self::HISTORY_ADD_DEVICE:
                $label = __('Add a component');
                break;

            case self::HISTORY_UPDATE_DEVICE:
                $label = __('Change a component');
                break;

            case self::HISTORY_DELETE_DEVICE:
                $label = __('Delete a component');
                break;

            case self::HISTORY_LOCK_DEVICE:
                $label = __('Lock a component');
                break;

            case self::HISTORY_UNLOCK_DEVICE:
                $label = __('Unlock a component');
                break;

            case self::HISTORY_INSTALL_SOFTWARE:
                $label = __('Install a software');
                break;

            case self::HISTORY_UNINSTALL_SOFTWARE:
                $label = __('Uninstall a software');
                break;

            case self::HISTORY_DISCONNECT_DEVICE:
                $label = __('Disconnect an item');
                break;

            case self::HISTORY_CONNECT_DEVICE:
                $label = __('Connect an item');
                break;

            case self::HISTORY_ADD_RELATION:
                $label = __('Add a link with an item');
                break;

            case self::HISTORY_UPDATE_RELATION:
                $label = __('Update a link with an item');
                break;

            case self::HISTORY_DEL_RELATION:
                $label = __('Delete a link with an item');
                break;

            case self::HISTORY_LOCK_RELATION:
                $label = __('Lock a link with an item');
                break;

            case self::HISTORY_UNLOCK_RELATION:
                $label = __('Unlock a link with an item');
                break;

            case self::HISTORY_ADD_SUBITEM:
                $label = __('Add an item');
                break;

            case self::HISTORY_UPDATE_SUBITEM:
                $label = __('Update an item');
                break;

            case self::HISTORY_DELETE_SUBITEM:
                $label = __('Delete an item');
                break;

            case self::HISTORY_LOCK_SUBITEM:
                $label = __('Lock an item');
                break;

            case self::HISTORY_UNLOCK_SUBITEM:
                $label = __('Unlock an item');
                break;

            case self::HISTORY_SEND_WEBHOOK:
                $label = __('Send a queued webhook');
                break;

            case self::HISTORY_LOG_SIMPLE_MESSAGE:
            default:
                break;
        }

        return $label;
    }

    /**
     * Convert filters values into SQL filters usable in 'WHERE' condition of request build with 'DBmysqlIterator'.
     *
     * @param array $filters  Filters values.
     *    Filters values must be passed as indexed array using following rules :
     *     - 'affected_fields' key for values corresponding to values built in 'self::getDistinctAffectedFieldValuesInItemLog()',
     *     - 'date' key for a date value in 'Y-m-d H:i:s' format,
     *     - 'linked_actions' key for values corresponding to values built in 'self::getDistinctLinkedActionValuesInItemLog()',
     *     - 'users_names' key for values corresponding to values built in 'self::getDistinctUserNamesValuesInItemLog()'.
     *
     * @return array
     *
     * @since 9.3
     **/
    public static function convertFiltersValuesToSqlCriteria(array $filters)
    {
        $sql_filters = [];

        if (isset($filters['affected_fields']) && !empty($filters['affected_fields'])) {
            $affected_field_crit = [];
            foreach ($filters['affected_fields'] as $index => $affected_field) {
                $affected_field_crit[$index] = [];
                foreach (explode(";", $affected_field) as $var) {
                    if (1 === preg_match('/^(?P<key>.+):(?P<operator>.*):(?P<values>.+)$/', $var, $matches)) {
                        $key = $matches['key'];
                        $operator = $matches['operator'];
                        // Each field can have multiple values for a given filter
                        $values = explode(',', $matches['values']);

                        // linked_action and id_search_option are stored as integers
                        if (in_array($key, ['linked_action', 'id_search_option'])) {
                            $values = array_map('intval', $values);
                        }

                        if (!empty($operator)) {
                            $affected_field_crit[$index][$operator][$key] = $values;
                        } else {
                            $affected_field_crit[$index][$key] = $values;
                        }
                    }
                }
            }
            $sql_filters[] = [
                'OR' => $affected_field_crit,
            ];
        }

        if (isset($filters['date']) && !empty($filters['date'])) {
            $sql_filters[] = [
                ['date_mod' => ['>=', "{$filters['date']} 00:00:00"]],
                ['date_mod' => ['<=', "{$filters['date']} 23:59:59"]],
            ];
        }

        if (isset($filters['linked_actions']) && !empty($filters['linked_actions'])) {
            $linked_action_crit = [];
            foreach ($filters['linked_actions'] as $linked_action) {
                if ($linked_action === 'other') {
                    $linked_action_crit[] = ['linked_action' => self::HISTORY_LOG_SIMPLE_MESSAGE];
                    $linked_action_crit[] = ['linked_action' => ['>=', self::HISTORY_PLUGIN]];
                } else {
                    $linked_action_crit[] = ['linked_action' => $linked_action];
                }
            }
            $sql_filters[] = ['OR' => $linked_action_crit];
        }

        if (isset($filters['users_names']) && !empty($filters['users_names'])) {
            $sql_filters['user_name'] = $filters['users_names'];
        }

        return $sql_filters;
    }

    /**
     * Actions done after the ADD of the item in the database
     *
     * @since 0.83
     *
     * @see CommonDBTM::post_addItem()
     **/
    public function post_addItem()
    {
        $_SESSION['glpi_maxhistory'] = $this->fields['id'];
    }

    public function getRights($interface = 'central')
    {

        $values = [ READ => __('Read')];
        return $values;
    }

    public static function useQueue(): void
    {
        static::$use_queue = true;
    }

    public static function queue($var): void
    {
        static::$queue[] = $var;
    }

    public static function resetQueue(): void
    {
        static::$queue = [];
    }

    public static function handleQueue(): void
    {
        global $DB;

        $queue = static::$queue;
        if (!count($queue)) {
            return;
        }

        $dparams = array_fill_keys(array_keys($queue[0]), new QueryParam());
        $update = $DB->buildInsert(
            static::getTable(),
            $dparams
        );
        $stmt = $DB->prepare($update);

        foreach (static::$queue as $input) {
            $stmt->bind_param(
                str_pad('', count($input), 's'),
                ...array_values($input)
            );
            $DB->executeStatement($stmt);
        }
        $stmt->close();
        static::resetQueue();
    }
}
