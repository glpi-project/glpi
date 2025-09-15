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

namespace Glpi;

use Ajax;
use CommonDBTM;
use CommonGLPI;
use CronTask;
use DbUtils;
use Document;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\System\Log\LogViewer;
use Html;
use Infocom;
use ITILSolution;
use RuntimeException;
use Session;
use Toolbox;

use function Safe\ob_get_clean;
use function Safe\ob_start;

/**
 * Event Class
 **/
class Event extends CommonDBTM
{
    public static $rightname = 'system_logs';

    public static function getTypeName($nb = 0)
    {
        return _n('Event log', 'Event logs', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['admin', LogViewer::class, self::class];
    }

    public static function getMenuContent()
    {
        $menu = parent::getMenuContent();
        if ($menu !== false) {
            unset($menu['links']['search'], $menu['links']['lists']);
        }
        return $menu;
    }

    public function add(array $input, $options = [], $history = true)
    {
        throw new RuntimeException(
            \sprintf(
                'Events must be added by calling the `%s::log()` method.',
                static::class,
            )
        );
    }

    public function update(array $input, $history = true, $options = [])
    {
        throw new RuntimeException('Events cannot be updated.');
    }

    public function delete(array $input, $force = false, $history = true)
    {
        throw new RuntimeException('Events cannot be deleted.');
    }

    /**
     * Log an event.
     *
     * Log the event $event on the glpi_event table with all the others args, if
     * $level is above or equal to setting from configuration.
     *
     * @param $items_id
     * @param $type
     * @param $level
     * @param $service
     * @param $event
     **/
    public static function log($items_id, $type, $level, $service, $event)
    {
        global $CFG_GLPI, $DB;

        if ($level >= $CFG_GLPI["event_loglevel"]) {
            return;
        }

        $input = [
            'items_id' => intval($items_id),
            'type'     => $type,
            'date'     => $_SESSION["glpi_currenttime"],
            'service'  => $service,
            'level'    => intval($level),
            'message'  => $event,
        ];

        $DB->insert(self::getTable(), $input);

        $id = $DB->insertId();

        //only log in file, important events (connections and critical events; TODO : we need to add a general option to filter this in 9.1)
        if ($level <= 3) {
            $message_type = "";
            if ($type != 'system') {
                $message_type = "[" . $type . " " . $id . "] ";
            }

            $full_message = "[" . $service . "] "
                         . $message_type
                         . $level . ": "
                         . $event . "\n";

            Toolbox::logInFile("event", $full_message);
        }
    }

    /**
     * Clean old event - Call by cron
     *
     * @param $day integer
     *
     * @return integer number of events deleted
     **/
    public static function cleanOld($day)
    {
        global $DB;

        $secs = $day * DAY_TIMESTAMP;

        $DB->delete(
            'glpi_events',
            [
                new QueryExpression("UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs"),
            ]
        );
        return $DB->affectedRows();
    }

    /**
     * Return arrays for function showEvent et lastEvent
     **/
    public static function logArray()
    {

        static $logItemtype = [];
        static $logService  = [];

        if (count($logItemtype)) {
            return [$logItemtype, $logService];
        }

        $logItemtype = [
            'system'      => __('System'),
            'devices'     => _n('Component', 'Components', Session::getPluralNumber()),
            'planning'    => __('Planning'),
            'reservation' => _n('Reservation', 'Reservations', Session::getPluralNumber()),
            'dropdown'    => _n('Dropdown', 'Dropdowns', Session::getPluralNumber()),
            'rules'       => _n('Rule', 'Rules', Session::getPluralNumber()),
        ];

        $logService = [
            'inventory'    => _n('Asset', 'Assets', Session::getPluralNumber()),
            'tracking'      => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'maintain'      => __('Assistance'),
            'planning'      => __('Planning'),
            'tools'         => __('Tools'),
            'financial'     => __('Management'),
            'login'         => _n('Connection', 'Connections', 1),
            'setup'         => __('Setup'),
            'security'      => __('Security'),
            'reservation'   => _n('Reservation', 'Reservations', Session::getPluralNumber()),
            'cron'          => CronTask::getTypeName(Session::getPluralNumber()),
            'document'      => Document::getTypeName(Session::getPluralNumber()),
            'notification'  => _n('Notification', 'Notifications', Session::getPluralNumber()),
            'plugin'        => _n('Plugin', 'Plugins', Session::getPluralNumber()),
            'socket'        => Socket::getTypeName(Session::getPluralNumber()),
            'Impersonate'   => __('Impersonate'),
        ];

        return [$logItemtype, $logService];
    }

    /**
     * @param $type
     * @param $items_id
     **/
    public static function displayItemLogID($type, $items_id)
    {
        global $CFG_GLPI;

        $items_id = (int) $items_id;

        // If ID less than or equal to 0 (or Entity with ID less than 0 since Root Entity is 0)
        if ($items_id < 0 || ($type !== Entity::class && $items_id == 0)) {
            echo "&nbsp;";//$item;
        } else {
            switch ($type) {
                case "rules":
                    echo "<a href=\"" . \htmlescape($CFG_GLPI["root_doc"]) . "/front/rule.generic.form.php?id="
                     . $items_id . "\">" . $items_id . "</a>";
                    break;

                case "infocom":
                    $rand = mt_rand();
                    echo " <a href='#' data-bs-toggle='modal' data-bs-target='#infocom$rand'>$items_id</a>";
                    Ajax::createIframeModalWindow(
                        'infocom' . $rand,
                        Infocom::getFormURLWithID($items_id),
                        ['dialog_class' => 'modal-xl']
                    );
                    break;

                case "devices":
                    echo $items_id;
                    break;

                case "reservationitem":
                    echo "<a href=\"" . \htmlescape($CFG_GLPI["root_doc"]) . "/front/reservation.php?reservationitems_id="
                     . $items_id . "\">" . $items_id . "</a>";
                    break;

                default:
                    $url  = '';
                    if (!is_a($type, CommonDBTM::class, true)) {
                        $type = getSingular($type);
                    }
                    if ($item = getItemForItemtype($type)) {
                        $url  =  $item->getFormURLWithID($items_id);
                    }
                    if (!empty($url)) {
                        echo "<a href=\"" . \htmlescape($url) . "\">" . $items_id . "</a>";
                    } else {
                        echo $items_id;
                    }
                    break;
            }
        }
    }

    /**
     * Print a nice tab for last event from inventory section
     *
     * Print a great tab to present lasts events occurred on glpi
     *
     * @param string $user  name user to search on message (default '')
     * @param bool $display if false, return html
     **/
    public static function showForUser(string $user = "", bool $display = true)
    {
        global $CFG_GLPI, $DB;

        // Show events from $result in table form
        [$logItemtype, $logService] = self::logArray();

        // define default sorting
        $usersearch = "";
        if (!empty($user)) {
            $usersearch = $user . " ";
        }

        // Query Database
        $iterator = $DB->request([
            'FROM'   => 'glpi_events',
            'WHERE'  => ['message' => ['LIKE', $usersearch . '%']],
            'ORDER'  => 'date DESC',
            'LIMIT'  => (int) $_SESSION['glpilist_limit'],
        ]);

        // Number of results
        $number = count($iterator);

        // No Events in database
        if ($number < 1) {
            $twig_params = [
                'class'        => 'table table-hover table-bordered',
                'header_rows'  => [
                    [__s('No Event')],
                ],
                'rows'         => [],
            ];
            $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
            if ($display) {
                echo $output;
                return;
            } else {
                return $output;
            }
        }

        $twig_params = [
            'class'        => 'table table-hover table-striped table-bordered',
            'header_rows'  => [
                [
                    [
                        'colspan'   => 5,
                        'content'   => "<a href=\"" . \htmlescape($CFG_GLPI["root_doc"]) . "/front/event.php\">"
                            . \htmlescape(sprintf(__('Last %d events'), $_SESSION['glpilist_limit']))
                            . "</a>",
                    ],
                ],
                [
                    __s('Source'),
                    __s('Id'),
                    _sn('Date', 'Dates', 1),
                    [
                        'content'   => __s('Service'),
                        'style'     => 'width: 10%',
                    ],
                    [
                        'content'   => __s('Message'),
                        'style'     => 'width: 50%',
                    ],
                ],
            ],
            'rows'   => [],
        ];

        foreach ($iterator as $data) {
            $items_id = $data['items_id'];
            $type     = $data['type'];
            $date     = $data['date'];
            $service  = $data['service'];
            $message  = $data['message'];

            $itemtype = "&nbsp;";
            if (isset($logItemtype[$type])) {
                $itemtype = $logItemtype[$type];
            } else {
                if (!is_a($type, CommonDBTM::class, true)) {
                    $type = getSingular($type);
                }
                if ($item = getItemForItemtype($type)) {
                    $itemtype = $item->getTypeName(1);
                }
            }

            // Capture the 'echo' output of the function
            ob_start();
            self::displayItemLogID($type, $items_id);
            $item_log_id = ob_get_clean();

            $twig_params['rows'][] = [
                'class'  => 'tab_bg_2',
                'values' => [
                    \htmlescape($itemtype),
                    $item_log_id, // safe HTML returned by `displayItemLogID()`
                    \htmlescape(Html::convDateTime($date)),
                    \htmlescape($logService[$service] ?? ''),
                    \htmlescape($message),
                ],
            ];
        }

        $output = TemplateRenderer::getInstance()->render('components/table.html.twig', $twig_params);
        if ($display) {
            echo $output;
            return;
        } else {
            return $output;
        }
    }

    public static function getIcon()
    {
        return "ti ti-news";
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '155',
            'table'         => self::getTable(),
            'field'         => 'type',
            'name'          => __('Source'),
            'datatype'      => 'specific',
            'massiveaction' => false,
            'searchtype'    => ['equals', 'notequals', 'contains', 'notcontains'],
        ];

        $tab[] = [
            'id'            => '156',
            'table'         => self::getTable(),
            'field'         => 'items_id',
            'name'          => _n('Item', 'Items', 1),
            'datatype'      => 'specific',
            'nosearch'      => true,
            'massiveaction' => false,
            'additionalfields' => ['type'],
        ];

        $tab[] = [
            'id'            => '157',
            'table'         => self::getTable(),
            'field'         => 'date',
            'name'          => _n('Date', 'Dates', 1),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '158',
            'table'         => self::getTable(),
            'field'         => 'service',
            'name'          => __('Service'),
            'datatype'      => 'specific',
            'massiveaction' => false,
            'searchtype'    => ['equals', 'notequals', 'contains', 'notcontains'],
        ];

        $tab[] = [
            'id'            => '159',
            'table'         => self::getTable(),
            'field'         => 'level',
            'name'          => __('Level'),
            'datatype'      => 'integer',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '160',
            'table'         => self::getTable(),
            'field'         => 'message',
            'name'          => __('Message'),
            'datatype'      => 'text',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * Get the possibles values for the 'Source' search option, which target
     * the `type` column in glpi_events.
     * Possibles values are :
     * - Some specials types (see self::logArray)
     * - Used itemtypes
     *
     * @return array
     */
    private static function getTypeValuesForDropdown(): array
    {
        // Get specials types
        $specials = self::logArray()[0];

        // Get itemtypes and build their display names
        $itemtypes = [];
        foreach (self::getUsedItemtypes() as $value) {
            $itemtype = self::getItemtypeFromType($value);
            if (is_a($itemtype, CommonGLPI::class, true)) {
                $itemtypes[$value] = $itemtype::getTypeName(1);
            } else {
                trigger_error("Unsupported type: $value", E_USER_WARNING);
                $itemtypes[$value] = $value;
            }
        }

        return [
            __('Special') => $specials,
            __('Items') => $itemtypes,
        ];
    }

    /**
     * Get all itemtypes referenced in the `type` columns of glpi_events
     * Note that these values are not real itemtypes but strings like "users".
     * You need to call self::getItemtypeFromType() to get a valid GLPI itemtype
     *
     * @return array
     */
    private static function getUsedItemtypes(): array
    {
        global $DB;

        // These values are not itemtypes
        $blacklist = array_keys(self::logArray()[0]);

        $data = $DB->request([
            'SELECT'   => ['type'],
            'DISTINCT' => 'true',
            'FROM'     => self::getTable(),
            'WHERE'    => [
                'NOT' => ['type' => $blacklist],
            ],
        ]);

        return array_column(iterator_to_array($data), 'type');
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if ($field === 'service') {
            $value = $values['service'];
            if (empty($value)) {
                $value = 0;
            }
            return Dropdown::showFromArray($name, self::logArray()[1], [
                'value' => $value,
                'display' => false,
                'display_emptychoice' => true,
            ]);
        } elseif ($field === 'type') {
            $value = $values['type'];
            if (empty($value)) {
                $value = 0;
            }
            return Dropdown::showFromArray($name, self::getTypeValuesForDropdown(), [
                'value' => $value,
                'display' => false,
                'display_emptychoice' => true,
            ]);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if ($field === 'service') {
            $value = $values['service'];
            if (empty($value)) {
                return \htmlescape(NOT_AVAILABLE);
            }
            $services = self::logArray()[1];
            return \htmlescape($services[$value] ?? $value);
        } elseif ($field === 'items_id') {
            $type = $values['type'] ?? null;
            if (
                ((int) $values['items_id']) > 0
                && $type !== null
                && ($itemtype = self::getItemtypeFromType($type)) !== null
                && is_a($itemtype, CommonDBTM::class, true)
            ) {
                $item = new $itemtype();
                if ($item->getFromDB($values['items_id'])) {
                    return $item->getLink(['complete' => true]);
                }
            }
            // Show the ID at least if it is valid (There may be a plugin that is disabled)
            return \htmlescape(((int) $values['items_id']) > 0 ? $values['items_id'] : NOT_AVAILABLE);
        } elseif ($field === 'type') {
            $value = $values['type'];
            if (empty($value)) {
                return \htmlescape(NOT_AVAILABLE);
            }

            if (($itemtype = self::getItemtypeFromType($value)) !== null) {
                $display_value = $itemtype::getTypeName(1);
                $icon = $itemtype::getIcon() ?? '';
            } else {
                $types = self::logArray()[0];
                $display_value = $types[$value] ?? $value;
                $icon = '';
            }

            return '<i class="text-muted me-1 ' . \htmlescape($icon) . '"></i><span>' . \htmlescape($display_value) . '</span>';
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Extract itemtype from type field value.
     *
     * @param string $type
     *
     * @return string|null
     */
    private static function getItemtypeFromType(string $type): ?string
    {
        if (is_a($type, CommonGLPI::class, true)) {
            return $type;
        }

        static $mapping = [];

        if (array_key_exists($type, $mapping)) {
            return $mapping[$type];
        }

        $dbu = new DbUtils();

        // In many cases, `type` corresponds to a lowercase itemtype (e.g. `change`).
        $fallback_type = $dbu->fixItemtypeCase($type);
        if (is_a($fallback_type, CommonGLPI::class, true)) {
            $mapping[$type] = $fallback_type;
            return $fallback_type;
        }

        // In many cases, it also uses plural form of the lowercase itemtype (e.g. `users`).
        $fallback_type = $dbu->fixItemtypeCase($dbu->getSingular($type));
        if (is_a($fallback_type, CommonGLPI::class, true)) {
            $mapping[$type] = $fallback_type;
            return $fallback_type;
        }

        if ($type == 'solution') {
            return ITILSolution::class;
        }

        return null;
    }

    public function getRights($interface = 'central'): array
    {
        return [ READ => __('Read')];
    }
}
