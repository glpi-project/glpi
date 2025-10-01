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
use Glpi\CalDAV\Backend\Calendar;
use Glpi\DBAL\QueryFunction;
use Glpi\Features\PlanningEvent;
use Glpi\RichText\RichText;
use RRule\RRule;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VTodo;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property\FlatText;
use Sabre\VObject\Property\ICalendar\Recur;
use Sabre\VObject\Reader;
use Safe\DateTime;

use function Safe\parse_url;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\strtotime;

/**
 * Planning Class
 **/
class Planning extends CommonGLPI
{
    public static $rightname = 'planning';

    public static $palette_bg = ['#FFEEC4', '#D4EDFB', '#E1D0E1', '#CDD7A9', '#F8C8D2',
        '#D6CACA', '#D3D6ED', '#C8E5E3', '#FBD5BF', '#E9EBA2',
        '#E8E5E5', '#DBECDF', '#FCE7F2', '#E9D3D3', '#D2DBDC',
    ];

    public static $palette_fg = ['#57544D', '#59707E', '#5B3B5B', '#3A431A', '#58242F',
        '#3B2727', '#272D59', '#2E4645', '#6F4831', '#46481B',
        '#4E4E4E', '#274C30', '#6A535F', '#473232', '#454545',
    ];

    public static $palette_ev = ['#E94A31', '#5174F2', '#51C9F2', '#FFCC29', '#20C646',
        '#364959', '#8C5344', '#FF8100', '#F600C4', '#0017FF',
        '#000000', '#FFFFFF', '#005800', '#925EFF',
    ];

    public static $directgroup_itemtype = ['PlanningExternalEvent', 'ProjectTask', 'TicketTask', 'ProblemTask', 'ChangeTask'];

    public const READMY    =    1;
    public const READGROUP = 1024;
    public const READALL   = 2048;

    public const INFO = 0;
    public const TODO = 1;
    public const DONE = 2;

    public static function getTypeName($nb = 0)
    {
        return __('Planning');
    }

    public static function getMenuContent()
    {
        $menu = [];

        if (self::canView()) {
            $menu = [
                'title'    => static::getMenuName(),
                'shortcut' => static::getMenuShorcut(),
                'page'     => static::getSearchURL(false),
                'icon'     => static::getIcon(),
            ];

            if ($data = static::getAdditionalMenuLinks()) {
                $menu['links'] = $data;
            }

            if ($options = static::getAdditionalMenuOptions()) {
                $menu['options'] = $options;
            }
        }

        return $menu;
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];

        if (self::canView()) {
            $title     = htmlescape(self::getTypeName(Session::getPluralNumber()));
            $planning  = "<i class='ti ti-calendar pointer' title='$title'>
                        <span class='sr-only'>$title</span>
                       </i>";

            $links[$planning] = self::getSearchURL(false);
        }

        if (PlanningExternalEvent::canView()) {
            $ext_title = htmlescape(PlanningExternalEvent::getTypeName(Session::getPluralNumber()));
            $external  = "<i class='ti ti-calendar-week pointer' title='$ext_title'>
                        <span class='sr-only'>$ext_title</span>
                       </i>";

            $links[$external] = PlanningExternalEvent::getSearchURL(false);
        }

        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $caldav_title = __s('CalDAV browser interface');
            $caldav  = "<i class='ti ti-refresh pointer' title='$caldav_title'>
                        <span class='sr-only'>$caldav_title</span>
                       </i>";

            $links[$caldav] = '/caldav.php';
        }

        return $links;
    }

    public static function getAdditionalMenuOptions()
    {
        if (PlanningExternalEvent::canView()) {
            return [
                PlanningEvent::class => [
                    'title' => PlanningExternalEvent::getTypeName(Session::getPluralNumber()),
                    'page'  => PlanningExternalEvent::getSearchURL(false),
                    'links' => [
                        'add'    => '/front/planningexternalevent.form.php',
                        'search' => '/front/planningexternalevent.php',
                    ] + static::getAdditionalMenuLinks(),
                ],
            ];
        }
        return false;
    }

    public static function getMenuShorcut()
    {
        return 'p';
    }

    public static function canView(): bool
    {
        return Session::haveRightsOr(self::$rightname, [self::READMY, self::READGROUP,
            self::READALL,
        ]);
    }

    public function defineTabs($options = [])
    {
        $ong               = [];
        $ong['no_all_tab'] = true;

        $this->addStandardTab(self::class, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::class === self::class) {
            $tabs[1] = self::createTabEntry(self::getTypeName());

            return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === self::class) {
            switch ($tabnum) {
                case 1: // all
                    self::showPlanning($_SESSION['glpiID']);
                    break;
            }
        }
        return true;
    }

    /**
     * Get planning state name
     *
     * @param int $value status ID
     **/
    public static function getState($value)
    {
        return match ($value) {
            static::INFO => _n('Information', 'Information', 1),
            static::TODO => __('To do'),
            static::DONE => __('Done'),
            default      => '',
        };
    }

    /**
     * Get status icon
     *
     * @param int $status status ID
     * @return string
     * @since 10.0.9
     */
    public static function getStatusIcon($status): string
    {
        $label = htmlescape(self::getState($status));
        if (empty($label)) {
            return '';
        }
        $class = self::getStatusClass($status);
        $color = self::getStatusColor($status);
        return "<i class='itilstatus $class $color me-1' title='$label' data-bs-toggle='tooltip'></i><span>" . $label . "</span>";
    }

    /**
     * Get status class
     *
     * @param int $status status ID
     * @return string
     * @since 10.0.9
     */
    public static function getStatusClass($status): string
    {
        return match ($status) {
            static::INFO => 'ti ti-info-square-filled',
            static::TODO => 'ti ti-alert-square-filled',
            static::DONE => 'ti ti-square-check-filled',
            default      => '',
        };
    }

    /**
     * Get status color
     *
     * @param int $status status ID
     * @return string
     * @since 10.0.9
     */
    public static function getStatusColor($status): string
    {
        return match ($status) {
            static::INFO => 'text-info',
            static::TODO => 'text-warning',
            static::DONE => 'text-success',
            default      => '',
        };
    }

    /**
     * Dropdown of planning state
     *
     * @param string $name   Select name
     * @param string $value  Default value (default '')
     * @param boolean $display  Display of send string ? (true by default)
     * @param array $options
     **/
    public static function dropdownState($name, $value = '', $display = true, $options = [])
    {
        $js = <<<JAVASCRIPT
        templateTaskStatus = function(option) {
            if (option === false) {
                // Option is false when element does not match searched terms
                return null;
            }
            var status = option.id;
            var classes = "";
            switch (parseInt(status)) {
                case 0 :
                    classes = 'planned ti ti-info-square-filled';
                    break;
                case 1 :
                    classes = 'waiting ti ti-alert-square-filled';
                    break;
                case 2 :
                    classes = 'new ti ti-square-check-filled';
                    break;

            }
            return $('<span><i class="itilstatus ' + classes + '"></i> ' + _.escape(option.text) + '</span>');
        }
JAVASCRIPT;

        $p = [
            'value'             => $value,
            'showtype'          => 'normal',
            'display'           => $display,
            'templateResult'    => $js,
            'templateSelection' => $js,
        ];

        $values = [
            static::INFO => _n('Information', 'Information', 1),
            static::TODO => __('To do'),
            static::DONE => __('Done'),
        ];

        return Dropdown::showFromArray($name, $values, array_merge($p, $options));
    }

    /**
     * Check already planned user for a period
     *
     * @param integer $users_id user id
     * @param string  $begin    begin date
     * @param string  $end      end date
     * @param array   $except   items which not be into account ['Reminder' => [1, 2, id_of_items]]
     **/
    public static function checkAlreadyPlanned($users_id, $begin, $end, $except = [])
    {
        global $CFG_GLPI;

        if ($users_id === 0) {
            return false;
        }

        $planned = false;
        $message = '';

        foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            if (
                !is_a($itemtype, CommonDBTM::class, true)
            ) {
                continue;
            }
            $item = new $itemtype();
            if (
                // methods from the `Glpi\Features\PlanningEvent` trait
                !method_exists($item, 'populatePlanning')
                || !method_exists($item, 'getAlreadyPlannedInformation')
            ) {
                continue;
            }

            $data = $item->populatePlanning([
                'who'           => $users_id,
                'whogroup'      => 0,
                'begin'         => $begin,
                'end'           => $end,
                'check_planned' => true,
            ]);
            if (isPluginItemType($itemtype)) {
                $data = $data['items'] ?? [];
            }

            foreach ($data as $val) {
                if (
                    !isset($except[$itemtype])
                    || (is_array($except[$itemtype]) && !in_array($val['id'], $except[$itemtype]))
                ) {
                    $planned  = true;
                    $message .= '- ' . $item->getAlreadyPlannedInformation($val);
                    $message .= '<br/>';
                }
            }
        }
        if ($planned) {
            $user = new User();
            $user->getFromDB($users_id);
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('The user %1$s is busy at the selected timeframe.'),
                    '<a href="' . htmlescape($user::getFormURLWithID($users_id)) . '">' . htmlescape($user->getName()) . '</a>'
                ) . '<br/>' . $message,
                false,
                WARNING
            );
        }
        return $planned;
    }

    /**
     * Show the availability of a user
     *
     * @since 0.83
     *
     * @param array $params   array of params
     *    must contain :
     *          - begin: begin date to check (default '')
     *          - end: end date to check (default '')
     *          - itemtype : User or Object type (Ticket...)
     *          - foreign key field of the itemtype to define which item to used
     *    optional :
     *          - limitto : limit display to a specific user
     *
     * @return void
     **/
    public static function checkAvailability($params = [])
    {
        global $CFG_GLPI;

        if (!isset($params['itemtype'])) {
            return;
        }
        if (!($item = getItemForItemtype($params['itemtype']))) {
            return;
        }
        if (
            !isset($params[$item::getForeignKeyField()])
            || !$item->getFromDB($params[$item::getForeignKeyField()])
        ) {
            return;
        }
        // No limit by default
        $params['limitto'] ??= 0;
        $begin = $params['begin'] ?? date('Y-m-d');
        $end  = max($params['end'] ?? date('Y-m-d'), $begin);

        $users = [];

        switch ($item::class) {
            case User::class:
                $users[$item->getID()] = $item->getName();
                break;

            case CommonITILObject::class:
                foreach ($item->getUsers(CommonITILActor::ASSIGN) as $data) {
                    $users[$data['users_id']] = getUserName($data['users_id']);
                }
                foreach ($item->getGroups(CommonITILActor::ASSIGN) as $data) {
                    foreach (Group_User::getGroupUsers($data['groups_id']) as $data2) {
                        $users[$data2['id']] = formatUserName(
                            $data2["id"],
                            $data2["name"],
                            $data2["realname"],
                            $data2["firstname"]
                        );
                    }
                }

                $task = getItemForItemtype($item::getTaskClass());
                if ($task->getFromDBByCrit(['tickets_id' => $item->fields['id']])) {
                    $users[$task->fields['users_id_tech']] = getUserName($task->fields['users_id_tech']);
                    $group_id = $task->fields['groups_id_tech'];
                    if ($group_id) {
                        foreach (Group_User::getGroupUsers($group_id) as $data2) {
                            $users[$data2['id']] = formatUserName(
                                $data2["id"],
                                $data2["name"],
                                $data2["realname"],
                                $data2["firstname"]
                            );
                        }
                    }
                }
                break;
        }
        asort($users);

        if (($params['limitto'] > 0) && isset($users[$params['limitto']])) {
            $displayuser[$params['limitto']] = $users[$params['limitto']];
        } else {
            $displayuser = $users;
        }

        TemplateRenderer::getInstance()->display('pages/assistance/planning/availability.html.twig', [
            'begin' => $begin,
            'end'   => $end,
            'item'  => $item,
            'users' => $users,
            'displayed_users' => $displayuser,
            'params' => $params,
        ]);
    }

    /**
     * Show the planning
     *
     * Function name change since version 0.84 show() => showPlanning
     * Function prototype changes in 9.1 (no more parameters)
     *
     * @return void
     **/
    public static function showPlanning($fullview = true)
    {
        if (!static::canView()) {
            return;
        }

        self::initSessionForCurrentUser();

        // define options for current page
        if ($fullview) {
            $options = [
                'full_view'    => true,
                'default_view' => $_SESSION['glpi_plannings']['lastview'] ?? 'timeGridWeek',
                'resources'    => self::getTimelineResources(),
                'now'          => date("Y-m-d H:i:s"),
                'can_create'   => PlanningExternalEvent::canCreate(),
                'can_delete'   => PlanningExternalEvent::canPurge(),
                'rand'         => mt_rand(),
            ];
        } else {
            // short view (on Central page)
            $options = [
                'full_view'    => false,
                'default_view' => 'listFull',
                'header'       => false,
                'height'       => 'auto',
                'rand'         => mt_rand(),
                'now'          => date("Y-m-d H:i:s"),
            ];
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->render(
            'pages/assistance/planning/planning.html.twig',
            [
                'options' => $options,
            ]
        );
    }

    public static function getTimelineResources()
    {
        $resources = [];
        foreach ($_SESSION['glpi_plannings']['plannings'] as $planning_id => $planning) {
            if ($planning['type'] === 'external') {
                $resources[] = [
                    'id'         => $planning_id,
                    'title'      => $planning['name'],
                    'group_id'   => false,
                    'is_visible' => $planning['display'],
                    'itemtype'   => null,
                    'items_id'   => null,
                ];
                continue; // Ignore external calendars
            }

            $exploded = explode('_', $planning_id);
            if ($planning['type'] === 'group_users') {
                $group_exploded = explode('_', $planning_id);
                $group_id = (int) $group_exploded[1];
                $group = new Group();
                $group->getFromDB($group_id);
                $resources[] = [
                    'id'         => $planning_id,
                    'title'      => $group->getName(),
                    'eventAllow' => false,
                    'is_visible' => $planning['display'],
                    'itemtype'   => 'Group_User',
                    'items_id'   => $group_id,
                ];
                foreach (array_keys($planning['users']) as $planning_id_user) {
                    $child_exploded = explode('_', $planning_id_user);
                    $user = new User();
                    $users_id = (int) $child_exploded[1];
                    $user->getFromDB($users_id);
                    $planning_id_user = "gu_" . $planning_id_user;
                    $resources[] = [
                        'id'         => $planning_id_user,
                        'title'      => $user->getName(),
                        'is_visible' => $planning['display'],
                        'itemtype'   => 'User',
                        'items_id'   => $users_id,
                        'parentId'   => $planning_id,
                    ];
                }
            } else {
                $itemtype   = $exploded[0];
                $object = getItemForItemtype($itemtype);
                $users_id = (int) $exploded[1];
                $object->getFromDB($users_id);

                $resources[] = [
                    'id'         => $planning_id,
                    'title'      => $object->getName(),
                    'group_id'   => false,
                    'is_visible' => $planning['display'],
                    'itemtype'   => $itemtype,
                    'items_id'   => $users_id,
                ];
            }
        }

        return $resources;
    }

    /**
     * Return a palette array (for example self::$palette_bg)
     * @param  string $palette_name  the short name for palette (bg, fg, ev)
     * @return mixed                 the palette array or false
     *
     * @since  9.1.1
     */
    public static function getPalette($palette_name = 'bg')
    {
        if (in_array($palette_name, ['bg', 'fg', 'ev'])) {
            return self::${"palette_$palette_name"};
        }

        return false;
    }

    /**
     * Return an hexa color from a palette
     * @param  string  $palette_name the short name for palette (bg, fg, ev)
     * @param  integer $color_index  The color index in this palette
     * @return mixed                 the color in hexa (ex: #FFFFFF) or false
     *
     * @since  9.1.1
     */
    public static function getPaletteColor($palette_name = 'bg', $color_index = 0)
    {
        if ($palette = self::getPalette($palette_name)) {
            if ($color_index >= count($palette)) {
                $color_index %= count($palette);
            }

            return $palette[$color_index];
        }

        return false;
    }

    public static function getPlanningTypes()
    {
        global $CFG_GLPI;

        return array_merge(
            $CFG_GLPI['planning_types'],
            ['NotPlanned', 'OnlyBgEvents', 'StateDone']
        );
    }

    /**
     * Init $_SESSION['glpi_plannings'] var with thses keys :
     *  - 'filters' : type of planning available (ChangeTask, Reminder, etc)
     *  - 'plannings' : all plannings definided for current user.
     *
     * If currently logged user, has no plannings or filter, this function wiil init them
     *
     * Also manage color index in $_SESSION['glpi_plannings_color_index']
     *
     * @return void
     */
    public static function initSessionForCurrentUser()
    {
        // new user in planning, init session
        if (!isset($_SESSION['glpi_plannings']['filters'])) {
            $_SESSION['glpi_plannings']['filters']   = [];
            $_SESSION['glpi_plannings']['plannings'] = ['user_' . $_SESSION['glpiID'] => [
                'color'   => self::getPaletteColor('bg', 0),
                'display' => true,
                'type'    => 'user',
            ],
            ];
        }

        // complete missing filters
        $filters = &$_SESSION['glpi_plannings']['filters'];
        $index_color = 0;
        foreach (self::getPlanningTypes() as $planning_type) {
            if (in_array($planning_type, ['NotPlanned', 'OnlyBgEvents', 'StateDone']) || $planning_type::canView()) {
                if (!isset($filters[$planning_type])) {
                    $filters[$planning_type] = [
                        'color'   => self::getPaletteColor('ev', $index_color),
                        'display' => !in_array($planning_type, ['NotPlanned', 'OnlyBgEvents']),
                        'type'    => 'event_filter',
                    ];
                }
                $index_color++;
            }
        }

        // compute color index for plannings
        $_SESSION['glpi_plannings_color_index'] = 0;
        foreach ($_SESSION['glpi_plannings']['plannings'] as $planning) {
            if ($planning['type'] === 'group_users') {
                $_SESSION['glpi_plannings_color_index'] += count($planning['users']);
            } else {
                $_SESSION['glpi_plannings_color_index']++;
            }
        }
    }

    /**
     * Display left part of planning who contains filters and planning with delete/toggle buttons
     * and color choosing.
     * Call self::showSingleLinePlanningFilter for each filters and plannings
     *
     * @return void
     */
    public static function showPlanningFilter()
    {
        TemplateRenderer::getInstance()->display('pages/assistance/planning/filters.html.twig');
    }

    /**
     * Display a single line of planning filter.
     * See self::showPlanningFilter function
     *
     * @param $filter_key  : identify curent line of filter
     * @param $filter_data : array of filter date, must contains :
     *   * 'show_delete' (boolean): show delete button
     *   * 'filter_color_index' (integer): index of the color to use in self::$palette_bg
     * @param $options
     *
     * @return void
     * @used-by templates/pages/assistance/planning/filters.html.twig
     * @used-by templates/pages/assistance/planning/single_filter.html.twig
     */
    public static function showSingleLinePlanningFilter($filter_key, $filter_data, $options = [])
    {
        global $CFG_GLPI;

        // Invalid data, skip
        if (!isset($filter_data['type'])) {
            return;
        }

        $params['show_delete']        = true;
        $params['filter_color_index'] = 0;
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $actor = explode('_', $filter_key);
        $uID = 0;
        $gID = 0;
        $expanded = '';
        $title = '';
        $caldav_item_url = null;
        if ($filter_data['type'] === 'user') {
            $uID = $actor[1];
            $user = new User();
            $user_exists = $user->getFromDB($actor[1]);
            $title = $user->getName(); // Will return N/A if it doesn't exist anymore
            if ($user_exists) {
                $caldav_item_url = self::getCaldavBaseCalendarUrl($user);
            }
        } elseif ($filter_data['type'] === 'group_users') {
            $group = new Group();
            $group_exists = $group->getFromDB($actor[1]);
            $title = $group->getName(); // Will return N/A if it doesn't exist anymore
            if ($group_exists) {
                $caldav_item_url = self::getCaldavBaseCalendarUrl($group);
            }
            $enabled = $disabled = 0;
            foreach ($filter_data['users'] as $user) {
                if ($user['display']) {
                    $enabled++;
                } else {
                    $disabled++;
                    $filter_data['display'] = false;
                }
            }
            if ($enabled > 0 && $disabled > 0) {
                $expanded = ' expanded';
            }
        } elseif ($filter_data['type'] === 'group') {
            $gID = $actor[1];
            $group = new Group();
            $group_exists = $group->getFromDB($actor[1]);
            $title = $group->getName(); // Will return N/A if it doesn't exist anymore
            if ($group_exists) {
                $caldav_item_url = self::getCaldavBaseCalendarUrl($group);
            }
        } elseif ($filter_data['type'] === 'external') {
            $title = $filter_data['name'];
        } elseif ($filter_data['type'] === 'event_filter') {
            if ($filter_key === 'NotPlanned') {
                $title = __('Not planned tasks');
            } elseif ($filter_key === 'OnlyBgEvents') {
                $title = __('Only background events');
            } elseif ($filter_key === 'StateDone') {
                $title = __('Done elements');
            } else {
                if (!getItemForItemtype($filter_key)) {
                    return;
                } elseif (!$filter_key::canView()) {
                    return;
                }
                $title = $filter_key::getTypeName();
            }
        }


        if (!empty($filter_data['color'])) {
            $color = $filter_data['color'];
        } else {
            $params['filter_color_index']++;
            $color = self::getPaletteColor('bg', $params['filter_color_index']);
        }

        $login_user = null;
        $webcal_base_url = null;
        $show_export_buttons = in_array($filter_data['type'], ['user', 'group'], true);
        if ($show_export_buttons) {
            $parsed_url = parse_url($CFG_GLPI["url_base"]);

            $url_port = array_key_exists('port', $parsed_url)
                ? $parsed_url['port']
                : ($parsed_url['scheme'] === 'https' ? 443 : null);

            $webcal_base_url = 'webcal://'
                . $parsed_url['host']
                . ($url_port !== null ? ':' . $url_port : '')
                . ($parsed_url['path'] ?? '');

            $login_user = new User();
            $login_user->getFromDB(Session::getLoginUserID(true));
        }

        TemplateRenderer::getInstance()->display('pages/assistance/planning/single_filter.html.twig', [
            'filter_key'            => $filter_key,
            'filter_data'           => $filter_data,
            'expanded'              => $expanded,
            'title'                 => $title,
            'params'                => $params,
            'color'                 => $color,
            'show_export_buttons'   => $show_export_buttons,
            'uID'                   => $uID,
            'gID'                   => $gID,
            'login_user'            => $login_user,
            'webcal_base_url'       => $webcal_base_url,
            'caldav_url'            => $caldav_item_url !== null ? $CFG_GLPI['url_base'] . '/caldav.php/' . $caldav_item_url : null,
        ]);
    }

    /**
     * Display ajax form to add actor on planning
     *
     * @return void
     */
    public static function showAddPlanningForm()
    {
        $planning_types = ['user' => User::getTypeName(1)];
        if (Session::haveRightsOr('planning', [self::READGROUP, self::READALL])) {
            $planning_types['group_users'] = __('All users of a group');
            $planning_types['group']       = Group::getTypeName(1);
        }
        $planning_types['external'] = __('External calendar');


        $twig_params = [
            'planning_types' => $planning_types,
            'rand'           => mt_rand(),
            'label'          => __('Actor'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            <form action="{{ 'Planning'|itemtype_form_path }}">
                {{ fields.dropdownArrayField('planning_type', 0, planning_types, label, {
                    display_emptychoice: true,
                    rand: rand
                }) }}
                <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                <script>
                    $(() => {
                        $('#dropdown_planning_type{{ rand }}').on('change', function() {
                            const planning_type = $(this).val();
                            $('#add_planning_subform{{ rand }}').load('{{ path('ajax/planning.php')|e('js') }}', {
                                action: 'add_' + planning_type + '_form'
                            });
                        });
                    });
                </script>
                <br><br>
                <div id="add_planning_subform{{ rand }}"></div>
            </form>
TWIG, $twig_params);
    }

    /**
     * Display 'User' part of self::showAddPlanningForm spcified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @return void
     */
    public static function showAddUserForm()
    {
        $used = [];
        foreach (array_keys($_SESSION['glpi_plannings']) as $actor) {
            $actor = explode("_", $actor);
            if ($actor[0] === "user") {
                $used[] = $actor[1];
            }
        }

        // show only users with right to add planning events
        $rights = ['change', 'problem', 'reminder', 'task', 'projecttask'];
        // Can we see only personnal planning ?
        if (!Session::haveRightsOr('planning', [self::READALL, self::READGROUP])) {
            $rights = 'id';
        }
        // Can we see user of my groups ?
        if (
            Session::haveRight('planning', self::READGROUP)
            && !Session::haveRight('planning', self::READALL)
        ) {
            $rights = 'groups';
        }

        $twig_params = [
            'add_msg' => _x('button', 'Add'),
            'rights'  => $rights,
            'used'    => $used,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
            {{ fields.dropdownField('User', 'users_id', 0, 'User'|itemtype_name, {
                entity: session('glpiactive_entity'),
                entity_sons: session('glpiactive_entity_recursive'),
                right: rights,
                used: used
            }) }}
            <input type="hidden" name="action" value="send_add_user_form">
            {{ inputs.submit('submit', add_msg, 1) }}
TWIG, $twig_params);
    }

    /**
     * Recieve 'User' data from self::showAddPlanningForm and save them to session and DB
     *
     * @param array $params Must contain form data (typically $_REQUEST)
     */
    public static function sendAddUserForm($params = [])
    {
        if (!isset($params['users_id']) || (int) $params['users_id'] <= 0) {
            Session::addMessageAfterRedirect(__s('A user selection is required'), false, ERROR);
            return;
        }
        $_SESSION['glpi_plannings']['plannings']["user_" . $params['users_id']]
         = ['color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
             'display' => true,
             'type'    => 'user',
         ];
        self::savePlanningsInDB();
        $_SESSION['glpi_plannings_color_index']++;
    }

    /**
     * Display 'All users of a group' part of self::showAddPlanningForm spcified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @return void
     */
    public static function showAddGroupUsersForm()
    {
        $condition = [];
        // filter groups
        if (!Session::haveRight('planning', self::READALL) && count($_SESSION['glpigroups'])) {
            $condition['id'] = $_SESSION['glpigroups'];
        }

        $twig_params = [
            'add_msg' => _x('button', 'Add'),
            'condition' => $condition,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
            {{ fields.dropdownField('Group', 'groups_id', 0, 'Group'|itemtype_name(1), {
                entity: session('glpiactive_entity'),
                entity_sons: session('glpiactive_entity_recursive'),
                condition: condition
            }) }}
            <input type="hidden" name="action" value="send_add_group_users_form">
            {{ inputs.submit('submit', add_msg, 1) }}
TWIG, $twig_params);
    }

    /**
     * Recieve 'All users of a group' data from self::showAddGroupUsersForm and save them to session and DB
     *
     * @since 9.1
     *
     * @param array $params Must contain form data (typically $_REQUEST)
     */
    public static function sendAddGroupUsersForm($params = [])
    {
        if (!isset($params['groups_id']) || (int) $params['groups_id'] <= 0) {
            Session::addMessageAfterRedirect(__s('A group selection is required'), false, ERROR);
            return;
        }
        $current_group = &$_SESSION['glpi_plannings']['plannings']["group_" . $params['groups_id'] . "_users"];
        $current_group = [
            'display' => true,
            'type'    => 'group_users',
            'users'   => [],
        ];
        $users = Group_User::getGroupUsers($params['groups_id'], [
            'glpi_users.is_active'  => 1,
            'glpi_users.is_deleted' => 0,
            [
                'OR' => [
                    ['glpi_users.begin_date' => null],
                    ['glpi_users.begin_date' => ['<', QueryFunction::now()]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_users.end_date' => null],
                    ['glpi_users.end_date' => ['>', QueryFunction::now()]],
                ],
            ],
        ]);

        foreach ($users as $user_data) {
            $current_group['users']['user_' . $user_data['id']] = [
                'color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
                'display' => true,
                'type'    => 'user',
            ];
            $_SESSION['glpi_plannings_color_index']++;
        }
        self::savePlanningsInDB();
    }

    public static function editEventForm($params = [])
    {
        $item = getItemForItemtype($params['itemtype']);
        if ($item instanceof CommonDBTM) {
            $item->getFromDB((int) $params['id']);
            $url = $item->getLinkURL();

            $rand = mt_rand();
            $options = [
                'from_planning_edit_ajax' => true,
                'form_id'                 => "edit_event_form$rand",
                'start'                   => date("Y-m-d", strtotime($params['start'])),
            ];
            if (isset($params['parentitemtype'])) {
                $options['parent'] = getItemForItemtype($params['parentitemtype']);
                $options['parent']->getFromDB($params['parentid']);
                $url = $options['parent']->getLinkURL();
            }

            echo "<div class='center'>";
            echo "<a href='" . htmlescape($url) . "' class='btn btn-outline-secondary'>"
                . "<i class='ti ti-eye'></i>"
                . "<span>" . __s("View this item in its context") . "</span>"
            . "</a>";
            echo "</div>";
            echo "<hr>";
            $item->showForm((int) $params['id'], $options);
            $callback = "glpi_close_all_dialogs();
                      GLPIPlanning.refresh();
                      displayAjaxMessageAfterRedirect();";
            Html::ajaxForm("#edit_event_form$rand", $callback);
        }
    }

    /**
     * Display 'Group' part of self::showAddPlanningForm spcified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @since 9.1
     *
     * @return void
     */
    public static function showAddGroupForm()
    {
        $condition = ['is_task' => 1];
        // filter groups
        if (!Session::haveRight('planning', self::READALL) && count($_SESSION['glpigroups'])) {
            $condition['id'] = $_SESSION['glpigroups'];
        }

        $twig_params = [
            'add_msg' => _x('button', 'Add'),
            'condition' => $condition,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
            {{ fields.dropdownField('Group', 'groups_id', 0, 'Group'|itemtype_name(1), {
                entity: session('glpiactive_entity'),
                entity_sons: session('glpiactive_entity_recursive'),
                condition: condition
            }) }}
            <input type="hidden" name="action" value="send_add_group_form">
            {{ inputs.submit('submit', add_msg, 1) }}
TWIG, $twig_params);
    }

    /**
     * Recieve 'Group' data from self::showAddGroupForm and save them to session and DB
     *
     * @since 9.1
     *
     * @param array $params Must contain form data (typically $_REQUEST)
     */
    public static function sendAddGroupForm($params = [])
    {
        if (!isset($params['groups_id']) || (int) $params['groups_id'] <= 0) {
            Session::addMessageAfterRedirect(__s('A group selection is required'), false, ERROR);
            return;
        }
        $_SESSION['glpi_plannings']['plannings']["group_" . $params['groups_id']]
         = ['color'   => self::getPaletteColor(
             'bg',
             $_SESSION['glpi_plannings_color_index']
         ),
             'display' => true,
             'type'    => 'group',
         ];
        self::savePlanningsInDB();
        $_SESSION['glpi_plannings_color_index']++;
    }

    /**
     * Display 'External' part of self::showAddPlanningForm specified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @since 9.5
     *
     * @return void
     */
    public static function showAddExternalForm()
    {
        $twig_params = [
            'add_msg' => _x('button', 'Add'),
            'name_label'   => __('Calendar name'),
            'url_label'    => __('Calendar URL'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
            {% set rand = random() %}
            {{ fields.textField('name', '', name_label, {id: 'name' ~ rand}) }}
            {{ fields.urlField('url', '', url_label, {id: 'url' ~ rand}) }}
            <input type="hidden" name="action" value="send_add_external_form">
            {{ inputs.submit('submit', add_msg, 1) }}
TWIG, $twig_params);
    }

    /**
     * Receive 'External' data from self::showAddExternalForm and save them to session and DB
     *
     * @since 9.5
     *
     * @param array $params Form data
     *
     * @return void
     */
    public static function sendAddExternalForm($params = [])
    {
        if (empty($params['url'])) {
            Session::addMessageAfterRedirect(__s('A url is required'), false, ERROR);
            return;
        }

        if (!Toolbox::isUrlSafe($params['url'])) {
            Session::addMessageAfterRedirect(
                sprintf(__s('URL "%s" is not allowed by your administrator.'), htmlescape($params['url'])),
                false,
                ERROR
            );
            return;
        }

        $_SESSION['glpi_plannings']['plannings']['external_' . md5($params['url'])] = [
            'color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
            'display' => true,
            'type'    => 'external',
            'name'    => $params['name'],
            'url'     => $params['url'],
        ];
        self::savePlanningsInDB();
        $_SESSION['glpi_plannings_color_index']++;
    }

    public static function showAddEventForm($params = [])
    {
        global $CFG_GLPI;

        if (count($CFG_GLPI['planning_add_types']) === 1) {
            $params['itemtype'] = $CFG_GLPI['planning_add_types'][0];
            self::showAddEventSubForm($params);
        } else {
            $select_options = [];
            foreach ($CFG_GLPI['planning_add_types'] as $add_types) {
                $select_options[$add_types] = $add_types::getTypeName(1);
            }

            $twig_params = [
                'label' => __('Event type'),
                'select_options' => $select_options,
                'params' => $params,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
            {% set rand = random() %}
            {{ fields.dropdownArrayField('itemtype', '', select_options, label, {
                display_emptychoice: true,
                rand: rand
            }) }}
            <script>
                $(() => {
                    $('#dropdown_itemtype{{ rand }}').on('change', function() {
                        const current_itemtype = $(this).val();
                        $('#add_planning_subform{{ rand }}').load('{{ path('ajax/planning.php')|e('js') }}', {
                            action: 'add_event_sub_form',
                            itemtype: current_itemtype,
                            begin: '{{ params.begin|e('js') }}',
                            end: '{{ params.end|e('js') }}'
                        });
                    });
                });
            </script>
            <div id="add_planning_subform{{ rand }}"></div>
TWIG, $twig_params);
        }
    }

    /**
     * Display form after selecting date range in planning
     *
     * @since 9.1
     *
     * @param array $params Must contain these keys:
     *  - begin : start of selection range.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end : end of selection range.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *
     * @return void
     */
    public static function showAddEventSubForm($params = [])
    {
        $rand   = mt_rand();
        $params = self::cleanDates($params);

        $params['res_itemtype'] ??= '';
        $params['res_items_id'] ??= 0;
        if ($item = getItemForItemtype($params['itemtype'])) {
            $item->showForm(-1, [
                'from_planning_ajax' => true,
                'begin'              => $params['begin'],
                'end'                => $params['end'],
                'res_itemtype'       => $params['res_itemtype'],
                'res_items_id'       => $params['res_items_id'],
                'form_id'            => "ajax_reminder$rand",
            ]);
            $callback = "glpi_close_all_dialogs();
                      GLPIPlanning.refresh();
                      displayAjaxMessageAfterRedirect();";
            Html::ajaxForm("#ajax_reminder$rand", $callback);
        }
    }

    /**
     * Former front/planning.php before 9.1.
     * Display a classic form to plan an event (with begin field and duration)
     *
     * @since 9.1
     *
     * @param array $params Array of parameters whou should contain :
     *   - id (integer): id of item who receive the planification
     *   - itemtype (string): itemtype of item who receive the planification
     *   - begin (string) : start date of event
     *   - _display_dates (bool) : display dates fields (default true)
     *   - end (optionnal) (string) : end date of event. Ifg missing, it will computerd from begin+1hour
     *   - rand_user (integer) : a random number for planning user avaibility or not specified if no user availability check should be done
     *   - rand : specific rand if needed (default is generated one)
     */
    public static function showAddEventClassicForm($params = [])
    {
        global $CFG_GLPI;

        if (isset($params["id"]) && ($params["id"] > 0)) {
            echo "<input type='hidden' name='plan[id]' value='" . ((int) $params["id"]) . "'>";
        }

        $display_dates = $params['_display_dates'] ?? true;
        $mintime = $CFG_GLPI["planning_begin"];
        if (!empty($params["begin"])) {
            $begin = $params["begin"];
            $begintime = date("H:i:s", strtotime($begin));
            if ($begintime < $mintime) {
                $mintime = $begintime;
            }
        } else {
            $ts = $CFG_GLPI['time_step'] * 60; // passage in minutes
            $time = time() + $ts - 60;
            $time = ((int) floor($time / $ts)) * $ts;
            $begin = date("Y-m-d H:i", $time);
        }

        if (!empty($params["end"])) {
            $end = $params["end"];
        } else {
            $end = date("Y-m-d H:i:s", strtotime($begin) + HOUR_TIMESTAMP);
        }

        $default_delay = $params['duration'] ?? 0;
        if ($display_dates) {
            $default_delay = floor((strtotime($end) - strtotime($begin)) / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP) * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;
        }

        TemplateRenderer::getInstance()->display('pages/assistance/planning/add_classic_event.html.twig', [
            'params' => $params,
            'begin'  => $begin,
            'end'    => $end,
            'mintime' => $mintime,
            'default_delay' => $default_delay,
        ]);
    }

    /**
     * @param array $data
     * @return void
     * @used-by templates/pages/assistance/planning/add_classic_event.html.twig
     */
    public static function showPlanningCheck(array $data): void
    {
        global $CFG_GLPI;

        $append_params = [
            "checkavailability" => "checkavailability",
        ];

        if (isset($data['users_id']) && ($data['users_id'] > 0)) {
            $append_params["itemtype"] = User::class;
            $append_params[User::getForeignKeyField()] = $data['users_id'];
        } elseif (
            isset($data['parent_itemtype'], $data['parent_items_id'], $data['parent_fk_field'])
            && class_exists($data['parent_itemtype']) && ($data['parent_items_id'] > 0) && ($data['parent_fk_field'] !== '')
        ) {
            $append_params["itemtype"] = $data['parent_itemtype'];
            $append_params[$data['parent_fk_field']] = $data['parent_items_id'];
        }

        if (count($append_params) > 1) {
            $rand = mt_rand();
            echo "<a href='#' title=\"" . __s('Availability') . "\" data-bs-toggle='modal' data-bs-target='#planningcheck$rand'>";
            echo "<i class='ti ti-calendar'></i>";
            echo "<span class='sr-only'>" . __s('Availability') . "</span>";
            echo "</a>";
            Ajax::createIframeModalWindow(
                'planningcheck' . $rand,
                $CFG_GLPI["root_doc"] . "/front/planning.php?" . Toolbox::append_params($append_params),
                ['title'  => __('Availability')]
            );
        }
    }

    /**
     * Clone an event
     *
     * @since 9.5
     *
     * @param array $event the event to clone
     *
     * @return integer|false the id (integer) or false if it failed
     */
    public static function cloneEvent(array $event = [])
    {
        $item = getItemForItemtype($event['old_itemtype']);
        $item->getFromDB((int) $event['old_items_id']);

        $input = array_merge($item->fields, [
            'plan' => [
                'begin' => date("Y-m-d H:i:s", strtotime($event['start'])),
                'end'   => date("Y-m-d H:i:s", strtotime($event['end'])),
            ],
        ]);
        unset($input['id'], $input['uuid']);

        if (isset($item->fields['name'])) {
            $input['name'] = sprintf(__('Copy of %s'), $item->fields['name']);
        }

        // manage change of assigment for CommonITILTask
        if (isset($event['actor']['itemtype'], $event['actor']['items_id']) && $item instanceof CommonITILTask) {
            $key = match ($event['actor']['itemtype']) {
                "group" => "groups_id_tech",
                "user" => isset($item->fields['users_id_tech']) ? "users_id_tech" : "users_id",
                default => throw new RuntimeException(sprintf('Unexpected event actor itemtype `%s`.', $event['actor']['itemtype'])),
            };

            unset(
                $input['users_id_tech'],
                $input['users_id'],
                $input['groups_id_tech'],
                $input['groups_id']
            );

            $input[$key] = $event['actor']['items_id'];
        }

        $new_items_id = $item->add($input);

        // manage all assigments for ProjectTask
        if (isset($event['actor']['itemtype'], $event['actor']['items_id']) && $item instanceof ProjectTask) {
            $team = new ProjectTaskTeam();
            $team->add([
                'projecttasks_id' => $new_items_id,
                'itemtype'        => ucfirst($event['actor']['itemtype']),
                'items_id'        => $event['actor']['items_id'],
            ]);
        }

        return $new_items_id;
    }

    /**
     * Delete an event
     *
     * @since 9.5
     *
     * @param array $event the event to clone (with itemtype and items_id keys)
     *
     * @return bool
     */
    public static function deleteEvent(array $event = []): bool
    {
        $item = getItemForItemtype($event['itemtype']);

        if (
            isset($event['day'], $event['instance'])
            && $event['instance']
            && method_exists($item, "deleteInstance")
        ) {
            return $item->deleteInstance((int) $event['items_id'], $event['day']);
        }

        return $item->delete([
            'id' => (int) $event['items_id'],
        ]);
    }

    /**
     * toggle display for selected line of $_SESSION['glpi_plannings']
     *
     * @since 9.1
     *
     * @param  array $options: should contain :
     *  - type : event type, can be event_filter, user, group or group_users
     *  - parent : in case of type=users_group, must contains the id of the group
     *  - name : contains a string with type and id concatened with a '_' char (ex user_41).
     *  - display : boolean value to set to his line
     * @return void
     */
    public static function toggleFilter($options = [])
    {
        $key = 'filters';
        if (in_array($options['type'], ['user', 'group', 'group_users', 'external'])) {
            $key = 'plannings';
        }
        if (empty($options['parent'])) {
            $_SESSION['glpi_plannings'][$key][$options['name']]['display'] = ($options['display'] === 'true');
        } else {
            $_SESSION['glpi_plannings']['plannings'][$options['parent']]['users']
            [$options['name']]['display']
            = ($options['display'] === 'true');
        }
        self::savePlanningsInDB();
    }

    /**
     * change color for selected line of $_SESSION['glpi_plannings']
     *
     * @since 9.1
     *
     * @param  array $options: should contain:
     *  - type : event type, can be event_filter, user, group or group_users
     *  - parent : in case of type=users_group, must contains the id of the group
     *  - name : contains a string with type and id concatened with a '_' char (ex user_41).
     *  - color : rgb color (preceded by '#'' char)
     * @return void
     */
    public static function colorFilter($options = [])
    {
        $key = 'filters';
        if (in_array($options['type'], ['user', 'group', 'group_users', 'external'])) {
            $key = 'plannings';
        }
        if (empty($options['parent'])) {
            $_SESSION['glpi_plannings'][$key][$options['name']]['color'] = $options['color'];
        } else {
            $_SESSION['glpi_plannings']['plannings'][$options['parent']]['users']
            [$options['name']]['color'] = $options['color'];
        }
        self::savePlanningsInDB();
    }

    /**
     * delete selected line in $_SESSION['glpi_plannings']
     *
     * @since 9.1
     *
     * @param  array $options: should contain:
     *  - type : event type, can be event_filter, user, group or group_users
     *  - filter : contains a string with type and id concatened with a '_' char (ex user_41).
     * @return void
     */
    public static function deleteFilter($options = [])
    {
        $current = $_SESSION['glpi_plannings']['plannings'][$options['filter']];
        if ($current['type'] === 'group_users') {
            $_SESSION['glpi_plannings_color_index'] -= count($current['users']);
        } else {
            $_SESSION['glpi_plannings_color_index']--;
        }

        unset($_SESSION['glpi_plannings']['plannings'][$options['filter']]);
        self::savePlanningsInDB();
    }

    public static function savePlanningsInDB()
    {
        $user = new User();
        $user->update(['id' => $_SESSION['glpiID'],
            'plannings' => exportArrayToDB($_SESSION['glpi_plannings']),
        ]);
    }

    /**
     * Prepare a set of events for jquery fullcalendar.
     * Call populatePlanning functions for all $CFG_GLPI['planning_types'] types
     *
     * @since 9.1
     *
     * @param array $options with these keys:
     *  - begin: mandatory, planning start.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end: mandatory, planning end.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - force_all_events: even if the range is big, don't reduce the returned set
     * @return array $events : array with events in fullcalendar.io format
     */
    public static function constructEventsArray($options = [])
    {
        global $CFG_GLPI;

        $param['start']               = '';
        $param['end']                 = '';
        $param['view_name']           = '';
        $param['force_all_events']    = false;
        $param['state_done']          = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $timezone = new DateTimeZone(date_default_timezone_get());
        $time_begin = strtotime($param['start']) - $timezone->getOffset(new DateTime($param['start']));
        $time_end   = strtotime($param['end']) - $timezone->getOffset(new DateTime($param['end']));

        // if the dates range is greater than a certain amount, and we're not on a list view
        // we certainly are on this view (as our biggest view apart list is month one).
        // we must avoid at all cost to calculate rrules events on a big range
        if (
            !$param['force_all_events']
            && $param['view_name'] !== "listFull"
            && ($time_end - $time_begin) > (2 * MONTH_TIMESTAMP)
        ) {
            $param['view_name'] = "listFull";
            return [];
        }

        $param['begin'] = date("Y-m-d H:i:s", $time_begin);
        $param['end']   = date("Y-m-d H:i:s", $time_end);

        if (!$_SESSION['glpi_plannings']['filters']['StateDone']['display']) {
            $param['state_done'] = false;
        }

        $raw_events = [];
        $not_planned = [];
        foreach ($CFG_GLPI['planning_types'] as $planning_type) {
            if (!$planning_type::canView()) {
                continue;
            }
            if ($_SESSION['glpi_plannings']['filters'][$planning_type]['display']) {
                $event_type_color = $_SESSION['glpi_plannings']['filters'][$planning_type]['color'];
                foreach ($_SESSION['glpi_plannings']['plannings'] as $actor => $actor_params) {
                    if ($actor_params['type'] === 'external') {
                        continue; // Ignore external calendars
                    }
                    $actor_params['event_type_color'] = $event_type_color;
                    $actor_params['planning_type'] = $planning_type;
                    self::constructEventsArraySingleLine(
                        $actor,
                        array_merge($param, $actor_params),
                        $raw_events,
                        $not_planned
                    );
                }
            }
        }

        //handle not planned events
        $raw_events = array_merge($raw_events, $not_planned);

        // get external calendars events (ical)
        // and on list view, only get future events
        $begin_ical = $param['begin'];
        if ($param['view_name'] === "listFull") {
            $begin_ical = date('Y-m-d 00:00:00');
        }
        $raw_events = array_merge(
            $raw_events,
            self::getExternalCalendarRawEvents($begin_ical, $param['end'])
        );

        // construct events (in fullcalendar format)
        $events = [];
        foreach ($raw_events as $event) {
            if (
                $_SESSION['glpi_plannings']['filters']['OnlyBgEvents']['display']
                && (!isset($event['background']) || !$event['background'])
            ) {
                continue;
            }

            $users_id = (!empty($event['users_id_tech'])
                        ? $event['users_id_tech']
                        : $event['users_id']);
            $content = self::displayPlanningItem($event, $users_id, 'in', false) ?: ($event['content'] ?? "");
            $tooltip = self::displayPlanningItem($event, $users_id, 'in', true) ?: ($event['tooltip'] ?? "");

            // dates should be set with the user timezone
            $begin = $event['begin'];
            $end   = $event['end'];

            // retrieve all day events
            if (
                strpos($event['begin'], "00:00:00")
                && (strtotime($event['end']) - strtotime($event['begin'])) % DAY_TIMESTAMP === 0
            ) {
                $begin = date('Y-m-d', strtotime($event['begin']));
                $end = date('Y-m-d', strtotime($event['end']));
            }

            // get duration in milliseconds
            $ms_duration = (strtotime($end) - strtotime($begin)) * 1000;

            $index_color = array_search("user_$users_id", array_keys($_SESSION['glpi_plannings']));
            $new_event = [
                'title'       => $event['name'],
                'content'     => $content,
                'tooltip'     => $tooltip,
                'start'       => $begin,
                'end'         => $end,
                'duration'    => $ms_duration,
                '_duration'   => $ms_duration, // sometimes duration is removed from event object in fullcalendar
                '_editable'   => $event['editable'], // same, avoid loss of editable key in fullcalendar
                'rendering'   => isset($event['background'])
                             && $event['background']
                             && !$_SESSION['glpi_plannings']['filters']['OnlyBgEvents']['display']
                              ? 'background'
                              : '',
                'color'       => (empty($event['color'])
                              ? self::$palette_bg[$index_color]
                              : $event['color']),
                'borderColor' => (empty($event['event_type_color'])
                              ? self::getPaletteColor('ev', $event['itemtype'])
                              : $event['event_type_color']),
                'textColor'   => self::$palette_fg[$index_color],
                'typeColor'   => (empty($event['event_type_color'])
                              ? self::getPaletteColor('ev', $event['itemtype'])
                              : $event['event_type_color']),
                'url'         => $event['url'] ?? "",
                'ajaxurl'     => $event['ajaxurl'] ?? "",
                'itemtype'    => $event['itemtype'] ?? "",
                'parentitemtype' => $event['parentitemtype'] ?? "",
                'items_id'    => $event['id'] ?? "",
                'resourceId'  => $event['resourceId'] ?? "",
                'priority'    => $event['priority'] ?? "",
                'state'       => $event['state'] ?? "",
            ];

            // if duration is full day and start is midnight, force allDay to true
            if (date('H:i:s', strtotime($begin)) === '00:00:00' && (int) $ms_duration % (DAY_TIMESTAMP * 1000) === 0) {
                $new_event['allDay'] = true;
            }

            // if we can't update the event, pass the editable key
            if (!$event['editable']) {
                $new_event['editable'] = false;
            }

            // override color if view is ressource and category color exists
            // maybe we need a better way for displaying categories color
            if (
                $param['view_name'] === "resourceWeek"
                && !empty($event['event_cat_color'])
            ) {
                $new_event['color'] = $event['event_cat_color'];
            }

            // manage reccurent events
            if (isset($event['rrule']) && count($event['rrule'])) {
                $rrule = $event['rrule'];

                // the fullcalencard plugin waits for integer types for number (not strings)
                if (isset($rrule['interval'])) {
                    $rrule['interval'] = (int) $rrule['interval'];
                }
                if (isset($rrule['count'])) {
                    $rrule['count'] = (int) $rrule['count'];
                }

                // clean empty values in rrule
                foreach ($rrule as $key => $value) {
                    if (is_null($value) || $value === '') {
                        unset($rrule[$key]);
                    }
                }

                $rset = PlanningExternalEvent::getRsetFromRRuleField($rrule, $new_event['start']);

                // append icon to distinguish reccurent event in views
                // use UTC datetime to avoid some issues with rlan/phprrule
                $dtstart_datetime  = new DateTime($new_event['start']);
                unset($rrule['exceptions']); // remove exceptions key (as libraries throw exception for unknow keys)
                $hr_rrule_o = new RRule(
                    array_merge(
                        $rrule,
                        [
                            'dtstart' => $dtstart_datetime->format('Ymd\THis\Z'),
                        ]
                    )
                );
                $new_event = array_merge($new_event, [
                    'icon'     => 'ti ti-history',
                    'icon_alt' => $hr_rrule_o->humanReadable(),
                ]);

                // for fullcalendar, we need to pass start in the rrule key
                unset($new_event['start'], $new_event['end']);

                // For list view, only display only the next occurrence
                // to avoid issues performances (range in list view can be 10 years long)
                if ($param['view_name'] === "listFull") {
                    /** @var ?DateTime $next_date */
                    $next_date = $rset->getNthOccurrenceAfter(new DateTime(), 1);
                    if ($next_date) {
                        $new_event = array_merge($new_event, [
                            'start'    => $next_date->format('c'),
                            'end'      => $next_date->add(new DateInterval("PT" . ($ms_duration / 1000) . "S"))
                                            ->format('c'),
                        ]);
                    }
                } else {
                    $rrule_string = "";
                    foreach ($rset->getRRules() as $occurence) {
                        $rrule_string .= $occurence->rfcString(false) . "\n";
                    }
                    $ex_dates = [];
                    foreach ($rset->getExDates() as $occurence) {
                        // we forge the ex date with only the date part of the exception
                        // and the hour of the dtstart.
                        // This to presents only date selection to the user
                        $ex_dates[] = "EXDATE:" . $occurence->format('Ymd\THis');
                    }

                    if (count($ex_dates)) {
                        $rrule_string .= implode("\n", $ex_dates) . "\n";
                    }

                    $new_event = array_merge($new_event, [
                        'is_recurrent' => true,
                        'rrule'        => $rrule_string,
                        'duration'     => $ms_duration,
                    ]);
                }
            }

            $events[] = $new_event;
        }

        return $events;
    }

    /**
     * construct a single line for self::constructEventsArray()
     * Recursively called to construct $raw_events param.
     *
     * @since 9.1
     *
     * @param string $actor: a type and id concaneted separated by '_' char, ex 'user_41'
     * @param array  $params: must contains this keys :
     *  - display: boolean for pass or not the consstruction of this line (a group of users can be displayed but its users not).
     *  - type: event type, can be event_filter, user, group or group_users
     *  - who: integer for identify user
     *  - whogroup: integer for identify group
     *  - color: string with #rgb color for event's foreground color.
     *  - event_type_color : string with #rgb color for event's foreground color.
     * @param array  $raw_events: (passed by reference) the events array in construction
     * @param array  $not_planned (passed by references) not planned events array in construction
     * @return void
     */
    public static function constructEventsArraySingleLine($actor, $params = [], &$raw_events = [], &$not_planned = [])
    {
        if ($params['display']) {
            $actor_array = explode("_", $actor);
            if ($params['type'] === "group_users") {
                $subparams = $params;
                unset($subparams['users']);
                $subparams['from_group_users'] = true;
                foreach ($params['users'] as $user => $userdata) {
                    $subparams = array_merge($subparams, $userdata);
                    self::constructEventsArraySingleLine($user, $subparams, $raw_events, $not_planned);
                }
            } else {
                $params['who']       = $actor_array[1];
                $params['whogroup']  = 0;
                if (
                    $params['type'] === "group"
                    && in_array($params['planning_type'], self::$directgroup_itemtype, true)
                ) {
                    $params['who']       = 0;
                    $params['whogroup']  = $actor_array[1];
                }

                $current_events = $params['planning_type']::populatePlanning($params);
                if (count($current_events) > 0) {
                    $raw_events = array_merge($raw_events, $current_events);
                }
                if (
                    $_SESSION['glpi_plannings']['filters']['NotPlanned']['display']
                    && method_exists($params['planning_type'], 'populateNotPlanned')
                ) {
                    $not_planned = array_merge($not_planned, $params['planning_type']::populateNotPlanned($params));
                }
            }
        }

        if (isset($params['from_group_users']) && $params['from_group_users']) {
            $actor = "gu_" . $actor;
        }

        // fill type of planning
        $raw_events = array_map(static fn($arr) => $arr + ['resourceId' => $actor], $raw_events);

        if ($_SESSION['glpi_plannings']['filters']['NotPlanned']['display']) {
            $not_planned = array_map(static fn($arr) => $arr + [
                'not_planned' => true,
                'resourceId' => $actor,
                'event_type_color' => $_SESSION['glpi_plannings']['filters']['NotPlanned']['color'],
            ], $not_planned);
        }
    }

    /**
     * Return events fetched from user external calendars.
     *
     * @param string $limit_begin
     * @param string $limit_end
     * @return array
     * @throws Exception
     */
    private static function getExternalCalendarRawEvents(string $limit_begin, string $limit_end): array
    {
        $raw_events = [];

        foreach ($_SESSION['glpi_plannings']['plannings'] as $planning_id => $planning_params) {
            if ('external' !== $planning_params['type'] || !$planning_params['display']) {
                continue; // Ignore non-external and inactive calendars
            }
            $calendar_data = Toolbox::getURLContent($planning_params['url']);
            if (empty($calendar_data)) {
                continue;
            }
            try {
                $vcalendar = Reader::read($calendar_data);
            } catch (ParseException $exception) {
                global $PHPLOGGER;
                $PHPLOGGER->error(
                    sprintf('Unable to parse calendar data from URL "%s"', $planning_params['url']),
                    ['exception' => $exception]
                );

                continue;
            }
            if (!$vcalendar instanceof VCalendar) {
                trigger_error(
                    sprintf('No VCalendar object found at URL "%s"', $planning_params['url']),
                    E_USER_WARNING
                );
                continue;
            }
            foreach ($vcalendar->getComponents() as $vcomp) {
                if (!($vcomp instanceof VEvent || $vcomp instanceof VTodo)) {
                    continue;
                }

                $end_date_prop = $vcomp instanceof VTodo ? 'DUE' : 'DTEND';
                if (
                    !$vcomp->DTSTART instanceof Sabre\VObject\Property\ICalendar\DateTime
                    || !$vcomp->$end_date_prop instanceof Sabre\VObject\Property\ICalendar\DateTime
                ) {
                    continue;
                }
                $user_tz  = new DateTimeZone(date_default_timezone_get());
                $begin_dt = $vcomp->DTSTART->getDateTime();
                $begin_dt = $begin_dt->setTimeZone($user_tz);
                $end_dt   = $vcomp->$end_date_prop->getDateTime();
                $end_dt   = $end_dt->setTimeZone($user_tz);

                if (
                    !($vcomp->RRULE instanceof Recur)
                    && ($limit_end < $begin_dt->format('Y-m-d H:i:s') || $limit_begin > $end_dt->format('Y-m-d H:i:s'))
                ) {
                    continue; // Ignore events not inside dates range
                }

                $title = $vcomp->SUMMARY instanceof FlatText ? $vcomp->SUMMARY->getValue() : '';
                $description = $vcomp->DESCRIPTION instanceof FlatText ? $vcomp->DESCRIPTION->getValue() : '';

                $tooltip_rows = [];
                if ($title !== '') {
                    $tooltip_rows[] = htmlescape($title);
                }
                if ($description !== '') {
                    $tooltip_rows[] = htmlescape($description);
                }

                $raw_events[] = [
                    'users_id'         => Session::getLoginUserID(),
                    'name'             => $title,
                    'tooltip'          => implode('<br>', $tooltip_rows),
                    'content'          => htmlescape($description),
                    'begin'            => $begin_dt->format('Y-m-d H:i:s'),
                    'end'              => $end_dt->format('Y-m-d H:i:s'),
                    'event_type_color' => $planning_params['color'],
                    'color'            => $planning_params['color'],
                    'rrule'            => $vcomp->RRULE instanceof Recur
                  ? current($vcomp->RRULE->getJsonValue())
                  : null,
                    'editable'         => false,
                    'resourceId'       => $planning_id,
                ];
            }
        }

        return $raw_events;
    }

    /**
     * Change dates of a selected event.
     * Called from a drag&drop in planning
     *
     * @since 9.1
     *
     * @param array $params must contains this keys :
     *  - items_id : integer to identify items
     *  - itemtype : string to identify items
     *  - start : planning start .
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end : planning end .
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     * @return bool
     */
    public static function updateEventTimes($params = [])
    {
        if ($item = getItemForItemtype($params['itemtype'])) {
            $params = self::cleanDates($params);

            if (
                $item->getFromDB($params['items_id'])
                && empty($item->fields['is_deleted'])
                && $item::canUpdate()
                && $item->canUpdateItem()
            ) {
                // item exists and is not in bin

                // if event has rrule property, check if we need to create a clone instance
                if (
                    isset($item->fields['rrule'])
                    && strlen($item->fields['rrule'])
                ) {
                    if (
                        isset($params['move_instance'])
                        && filter_var($params['move_instance'], FILTER_VALIDATE_BOOLEAN)
                        && method_exists($item, 'createInstanceClone')
                    ) {
                        $item = $item->createInstanceClone(
                            $item->fields['id'],
                            $params['old_start']
                        );
                        $params['items_id'] = $item->fields['id'];
                    }
                }

                $update = [
                    'id'   => $params['items_id'],
                    'plan' => [
                        'begin' => $params['start'],
                        'end'   => $params['end'],
                    ],
                ];

                if (isset($item->fields['users_id_tech'])) {
                    $update['users_id_tech'] = $item->fields['users_id_tech'];
                }

                // manage moving event between resource (actors)
                if (!empty($params['new_actor_itemtype']) && !empty($params['new_actor_items_id'])) {
                    $new_actor_itemtype = strtolower($params['new_actor_itemtype']);

                    // reminders don't have group assignement for planning
                    if (
                        !($new_actor_itemtype === 'group'
                        && $item instanceof Reminder)
                    ) {
                        switch ($new_actor_itemtype) {
                            case "group":
                                $update['groups_id_tech'] = $params['new_actor_items_id'];
                                if (strtolower($params['old_actor_itemtype']) === "user") {
                                    $update['users_id_tech']  = 0;
                                }
                                break;

                            case "user":
                                if (isset($item->fields['users_id_tech'])) {
                                    $update['users_id_tech']  = $params['new_actor_items_id'];
                                    if (strtolower($params['old_actor_itemtype']) === "group") {
                                        $update['groups_id_tech']  = 0;
                                    }
                                } else {
                                    $update['users_id'] = $params['new_actor_items_id'];
                                }
                                break;
                        }
                    }

                    // special case for project tasks
                    // which have a link tables for their relation with groups/users
                    if ($item instanceof ProjectTask) {
                        // get actor for finding relation with item
                        $actor = getItemForItemtype($params['old_actor_itemtype']);
                        $actor->getFromDB((int) $params['old_actor_items_id']);

                        // get current relation
                        $team_old = new ProjectTaskTeam();
                        $team_old->getFromDBForItems($item, $actor);

                        // if new relation already exists, delete old relation
                        $actor_new = getItemForItemtype($params['new_actor_itemtype']);
                        $actor_new->getFromDB((int) $params['new_actor_items_id']);
                        $team_new  = new ProjectTaskTeam();
                        if ($team_new->getFromDBForItems($item, $actor_new)) {
                            $team_old->delete([
                                'id' => $team_old->fields['id'],
                            ]);
                        } else {
                            // else update relation
                            $team_old->update([
                                'id'       => $team_old->fields['id'],
                                'itemtype' => $params['new_actor_itemtype'],
                                'items_id' => $params['new_actor_items_id'],
                            ]);
                        }
                    }
                }

                if (is_subclass_of($item, "CommonITILTask")) {
                    $parentitemtype = $item::getItilObjectItemType();
                    if (!$update["_job"] = getItemForItemtype($parentitemtype)) {
                        return false;
                    }

                    $fkfield = $update["_job"]::getForeignKeyField();
                    $update[$fkfield] = $item->fields[$fkfield];
                }

                return $item->update($update);
            }
        }

        return false;
    }

    /**
     * Clean timezone information from dates fields,
     * as fullcalendar doesn't support easily timezones, let's consider it sends raw dates
     * (remove timezone suffix), we will manage timezone directy on database
     * see https://fullcalendar.io/docs/timeZone
     *
     * @since 9.5
     *
     * @param array $params parameters send by fullcalendar
     *
     * @return array cleaned $params
     */
    public static function cleanDates(array $params = []): array
    {
        $dates_fields = [
            'start', 'begin', 'end',
        ];

        foreach ($params as $key => &$value) {
            if (in_array($key, $dates_fields, true)) {
                $value  = date("Y-m-d H:i:s", strtotime(trim($value, 'Z')));
            }
        }

        return $params;
    }

    /**
     * Display a Planning Item
     *
     * @param array $val       Array of the item to display
     * @param integer $who             ID of the user (0 if all)
     * @param 'in'|'through'|'begin'|'end'|'' $type Position of the item in the time block (in, through, begin or end)
     * @param boolean $complete        complete display (more details)
     *
     * @return string
     **/
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = false)
    {
        $html = "";

        // bg event shouldn't have content displayed
        if (!$complete && $_SESSION['glpi_plannings']['filters']['OnlyBgEvents']['display']) {
            return "";
        }

        // Plugins case
        if (
            !empty($val['itemtype'])
            && $val['itemtype'] !== 'NotPlanned'
            && method_exists($val['itemtype'], "displayPlanningItem")
        ) {
            $html .= $val['itemtype']::displayPlanningItem($val, $who, $type, $complete);
        }

        return $html;
    }

    /**
     * Show the planning for the central page of a user
     *
     * @param integer $who ID of the user
     *
     * @return void
     **/
    public static function showCentral($who)
    {
        if (
            !Session::haveRight(self::$rightname, self::READMY)
            || ($who <= 0)
        ) {
            return;
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="table-responsive card-table">
                <table class="table">
                    <thead>
                        <tr class="noHover">
                            <th><a href="{{ path('front/planning.php') }}">{{ msg }}</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="noHover">
                            <td class="planning_on_central">{% do call('Planning::showPlanning', [false]) %}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
TWIG, ['msg' => __('Your planning')]);
    }

    //*******************************************************************************************************************************
    // *********************************** Implementation ICAL ***************************************************************
    //*******************************************************************************************************************************

    /**
     *  Generate ical file content
     *
     * @param integer $who             user ID
     * @param integer $whogroup        group ID
     * @param string  $limititemtype   itemtype only display this itemtype (default '')
     *
     * @return void Outputs ical contents
     **/
    public static function generateIcal($who, $whogroup, $limititemtype = '')
    {
        global $CFG_GLPI;

        if (
            ($who === 0)
            && ($whogroup === 0)
        ) {
            return;
        }

        if (!empty($CFG_GLPI["version"])) {
            $unique_id = "GLPI-Planning-" . trim($CFG_GLPI["version"]);
        } else {
            $unique_id = "GLPI-Planning-UnknownVersion";
        }

        // create vcalendar
        $vcalendar = new VCalendar();

        // $xprops = array( "X-LIC-LOCATION" => $tz );
        // iCalUtilityFunctions::createTimezone( $v, $tz, $xprops );

        $interv = [];
        $begin  = time() - MONTH_TIMESTAMP * 12;
        $end    = time() + MONTH_TIMESTAMP * 12;
        $begin  = date("Y-m-d H:i:s", $begin);
        $end    = date("Y-m-d H:i:s", $end);
        $params = [
            'genical'   => true,
            'who'       => $who,
            'whogroup'  => $whogroup,
            'begin'     => $begin,
            'end'       => $end,
        ];

        if (empty($limititemtype)) {
            foreach ($CFG_GLPI['planning_types'] as $itemtype) {
                $interv = array_merge($interv, $itemtype::populatePlanning($params));
            }
        } else {
            $interv = $limititemtype::populatePlanning($params);
        }

        if (count($interv) > 0) {
            foreach ($interv as $key => $val) {
                $vevent = [];

                if (isset($val['itemtype'])) {
                    if (isset($val[getForeignKeyFieldForItemType($val['itemtype'])])) {
                        $uid = $val['itemtype'] . "#" . $val[getForeignKeyFieldForItemType($val['itemtype'])];
                    } else {
                        $uid = "Other#" . $key;
                    }
                } else {
                    $uid = "Other#" . $key;
                }

                $vevent['UID']     = $uid;

                $dateBegin = new DateTime($val["begin"]);
                $dateBegin->setTimeZone(new DateTimeZone('UTC'));

                $dateEnd = new DateTime($val["end"]);
                $dateEnd->setTimeZone(new DateTimeZone('UTC'));

                $vevent['DTSTART'] = $dateBegin;
                $vevent['DTEND']   = $dateEnd;

                $summary = '';
                if (isset($val["tickets_id"])) {
                    $summary = sprintf(__('Ticket #%1$s %2$s'), $val["tickets_id"], $val["name"]);
                } elseif (isset($val["name"])) {
                    $summary = $val["name"];
                }
                $vevent['SUMMARY'] = $summary;

                $description = '';
                if (isset($val["content"])) {
                    $description = $val["content"];
                } elseif (isset($val["text"])) {
                    $description = $val["text"];
                } elseif (isset($val["name"])) {
                    $description = $val["name"];
                }
                $vevent['DESCRIPTION'] = RichText::getTextFromHtml($description);

                if (isset($val["url"])) {
                    $vevent['URL'] = $val["url"];
                }

                // RRULE
                if (isset($val['rrule']) && count($val['rrule'])) {
                    $rrule_parts = [];
                    foreach ($val['rrule'] as $rrule_key => $rrule_value) {
                        if (empty($rrule_value)) {
                            continue;
                        }

                        if ($rrule_key === 'exceptions') {
                            $vevent['EXDATE;VALUE=DATE'] = array_map(
                                static function ($datestring) {
                                    $date = new DateTime($datestring);
                                    $date->setTimeZone(new DateTimeZone('UTC'));
                                    return $date->format('Ymd');
                                },
                                $rrule_value
                            );
                            continue;
                        }

                        if ($rrule_key === 'until') {
                            $until_date = new DateTime($rrule_value);
                            $until_date->setTimeZone(new DateTimeZone('UTC'));
                            $rrule_parts['UNTIL'] = $until_date->format('Ymd');
                            continue;
                        }

                        $rrule_parts[strtoupper($rrule_key)] = $rrule_value;
                    }
                    if (count($rrule_parts) > 0) {
                        $vevent['RRULE'] = $rrule_parts;
                    }
                }

                $vcalendar->add('VEVENT', $vevent);
            }
        }

        $output   = $vcalendar->serialize();
        $filename = date('YmdHis') . '.ics';

        @header("Content-Disposition: attachment; filename=\"$filename\"");
        //@header("Content-Length: ".Toolbox::strlen($output));
        @header("Connection: close");
        @header("content-type: text/calendar; charset=utf-8");

        echo $output;
    }

    public function getRights($interface = 'central')
    {
        $values[self::READMY]    = __('See personnal planning');
        $values[self::READGROUP] = __('See schedule of people in my groups');
        $values[self::READALL]   = __('See all plannings');

        return $values;
    }

    /**
     * Save the last view used in fullcalendar
     *
     * @since 9.5
     *
     * @param string $view_name
     * @return void
     */
    public static function viewChanged($view_name = "ListView")
    {
        $_SESSION['glpi_plannings']['lastview'] = $view_name;
    }

    /**
     * Returns actor type from 'planning' key (key comes from user 'plannings' field).
     *
     * @param string $key
     *
     * @return string|null
     */
    public static function getActorTypeFromPlanningKey($key)
    {
        if (preg_match('/group_\d+_users/', $key)) {
            return Group_User::getType();
        }
        $itemtype = ucfirst(preg_replace('/^([a-z]+)_\d+$/', '$1', $key));
        return class_exists($itemtype) ? $itemtype : null;
    }

    /**
     * Returns actor id from 'planning' key (key comes from user 'plannings' field).
     *
     * @param string $key
     *
     * @return integer|null
     */
    public static function getActorIdFromPlanningKey($key)
    {
        $items_id = preg_replace('/^[a-z]+_(\d+)(?:_[a-z]+)?$/', '$1', $key);
        return is_numeric($items_id) ? (int) $items_id : null;
    }

    /**
     * Returns planning key for given actor (key is used in user 'plannings' field).
     *
     * @param string  $itemtype
     * @param integer $items_id
     *
     * @return string
     */
    public static function getPlanningKeyForActor($itemtype, $items_id)
    {
        if ('Group_User' === $itemtype) {
            return 'group_' . $items_id . '_users';
        }

        return strtolower($itemtype) . '_' . $items_id;
    }

    /**
     * Get CalDAV base calendar URL for given actor.
     *
     * @param CommonDBTM $item
     *
     * @return string|null
     */
    private static function getCaldavBaseCalendarUrl(CommonDBTM $item)
    {
        $calendar_uri = null;

        switch (get_class($item)) {
            case Group::class:
                $calendar_uri = Calendar::PREFIX_GROUPS
                 . '/' . $item->fields['id']
                 . '/' . Calendar::BASE_CALENDAR_URI;
                break;
            case User::class:
                $calendar_uri = Calendar::PREFIX_USERS
                . '/' . $item->fields['name']
                . '/' . Calendar::BASE_CALENDAR_URI;
                break;
        }

        return $calendar_uri;
    }

    public static function getIcon()
    {
        return "ti ti-calendar-time";
    }
}
