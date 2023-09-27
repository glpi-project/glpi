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

namespace Glpi\Config;

use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Grid;
use Glpi\Config\Option as Option;

final class CoreConfigProvider implements ConfigProviderInterface
{
    private static self $instance;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConfigSections(): array
    {
        $general_setup = new ConfigSection('general', __('General setup'), 'ti ti-adjustments');
        $personalize = new ConfigSection('personalization', __('Personalization'), 'ti ti-adjustments');
        return [
            $general_setup,
            new ConfigSection('translations', __('Translations'), 'ti ti-language', $general_setup),
            new ConfigSection('dynamic_ui', __('Dynamic display'), 'ti ti-list', $general_setup),
            new ConfigSection('search', __('Search engine'), 'ti ti-search', $general_setup),
            new ConfigSection('item_locks', __('Item locks'), 'ti ti-lock', $general_setup),
            new ConfigSection('auto_login', __('Auto login'), 'ti ti-login', $general_setup),
            $personalize,
            new ConfigSection('assistance', __('Assistance'), 'ti ti-headset', $personalize),
            new ConfigSection('sla_progress', __('Due date progression'), 'ti ti-clock-play', $personalize),
            new ConfigSection('dashboards', Dashboard::getTypeName(\Session::getPluralNumber()), Dashboard::getIcon(), $personalize),
            new ConfigSection('notifications', \Notification::getTypeName(\Session::getPluralNumber()), \Notification::getIcon(), $personalize),
        ];
    }

    /**
     * @return ConfigOption[] The list of options provided by this provider
     */
    public function getConfigOptions(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $central = new \Central();

        $general = ConfigManager::getInstance()->getSectionByFullName('general');
        $translations = ConfigManager::getInstance()->getSectionByFullName('general.translations');
        $dynamic_ui = ConfigManager::getInstance()->getSectionByFullName('general.dynamic_ui');
        $search = ConfigManager::getInstance()->getSectionByFullName('general.search');
        $item_locks = ConfigManager::getInstance()->getSectionByFullName('general.item_locks');
        $auto_login = ConfigManager::getInstance()->getSectionByFullName('general.auto_login');
        $personalize = ConfigManager::getInstance()->getSectionByFullName('personalization');
        $personalize_assistance = ConfigManager::getInstance()->getSectionByFullName('personalization.assistance');
        $personalize_sla = ConfigManager::getInstance()->getSectionByFullName('personalization.sla_progress');
        $personalize_dashboards = ConfigManager::getInstance()->getSectionByFullName('personalization.dashboards');
        $personalize_notifications = ConfigManager::getInstance()->getSectionByFullName('personalization.notifications');

        $scopes_global = [ConfigScope::GLOBAL];
        $scopes_userpref = [ConfigScope::GLOBAL, ConfigScope::USER];

        $options = [
            new Option\TextOption($scopes_global, $general, 'url_base', __('URL of the application')),
            new Option\TextOption(
                scopes: $scopes_global,
                section: $general,
                name: 'text_login',
                label: __('Text in the login box'),
                input_type: InputType::TEXTAREA,
                enable_richtext: true,
                enable_images: false,
            ),
            new Option\TextOption($scopes_global, $general, 'helpdesk_doc_url', __('Simplified interface help link')),
            new Option\TextOption($scopes_global, $general, 'central_doc_url', __('Standard interface help link')),
            new Option\NumberOption(
                scopes: $scopes_global,
                section: $general,
                name: 'decimal_number',
                label: __('Default decimals limit'),
                input_type: InputType::NUMBER,
                min: 1,
                max: 4
            ),
            new Option\BooleanOption($scopes_global, $general, 'use_public_faq', __('Allow FAQ anonymous access')),
            new Option\NumberOption($scopes_global, $dynamic_ui, 'dropdown_max', __('Page size for dropdown (paging using scroll)')),
            new Option\NumberOption(
                scopes: $scopes_global,
                section: $dynamic_ui,
                name: 'ajax_limit_count',
                label: __('Don\'t show search engine in dropdowns if the number of items is less than'),
                input_type: InputType::DROPDOWN_NUMBER,
                min: 1,
                max: 200,
                toadd: ['0' => __('Never')],
            ),
            new Option\ArrayOption(
                scopes: $scopes_global,
                section: $search,
                name: 'allow_search_view',
                label: __('Allow using Items seen search criteria'),
                elements: [
                    __('No'),
                    sprintf(__('%1$s (%2$s)'), __('Yes'), __('last criterion')),
                    sprintf(__('%1$s (%2$s)'), __('Yes'), __('default criterion'))
                ]
            ),
            new Option\BooleanOption($scopes_global, $search, 'allow_search_global', __('Global search'), InputType::DROPDOWN_YES_NO),
            new Option\ArrayOption(
                scopes: $scopes_global,
                section: $search,
                name: 'allow_search_all',
                label: __('Allow using "All" search criteria'),
                elements: [
                    __('No'),
                    sprintf(__('%1$s (%2$s)'), __('Yes'), __('last criterion')),
                ]
            ),
            new Option\NumberOption(
                scopes: $scopes_global,
                section: $search,
                name: 'list_limit_max',
                label: __('Default search results limit (page)'),
                input_type: InputType::NUMBER,
                min: 5,
                max: 200,
                step: 5
            ),
            new Option\NumberOption(
                scopes: $scopes_global,
                section: $search,
                name: 'cut',
                label: __('Default characters limit (summary text boxes)'),
                input_type: InputType::NUMBER,
                max: 250,
                step: 50
            ),
            new Option\NumberOption(
                scopes: $scopes_global,
                section: $search,
                name: 'url_maxlength',
                label: __('Default url length limit'),
                input_type: InputType::NUMBER,
                min: 20,
                max: 80,
                step: 5
            ),
            new Option\BooleanOption($scopes_global, $item_locks, 'lock_use_lock_item', __('Use locks'), InputType::CHECKBOX),
            new Option\ItemOption(
                scopes: $scopes_global,
                section: $item_locks,
                name: 'lock_lockprofile_id',
                label: __('Profile to be used when locking items'),
                itemtype: \Profile::class
            ),
            new Option\ArrayOption(
                scopes: $scopes_global,
                section: $item_locks,
                name: 'lock_item_list',
                label: __('List of items to lock'),
                elements: \ObjectLock::getLockableObjects(),
                multiple: true,
            ),
            new Option\TimestampOption(
                scopes: $scopes_global,
                section: $auto_login,
                name: 'login_remember_time',
                label: __('Time to allow "Remember Me"'),
                min: 0,
                max: MONTH_TIMESTAMP * 2,
                step: DAY_TIMESTAMP,
                toadd: [HOUR_TIMESTAMP, HOUR_TIMESTAMP * 2, HOUR_TIMESTAMP * 6, HOUR_TIMESTAMP * 12],
                emptylabel: __('Disabled')
            ),
            new Option\BooleanOption($scopes_global, $auto_login, 'login_remember_default', __('Default state of checkbox'), InputType::CHECKBOX),
            new Option\BooleanOption($scopes_global, $auto_login, 'display_login_source', __('Display source dropdown on login page'), InputType::CHECKBOX),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'language',
                label: __('Language'),
                elements: \Dropdown::getLanguages()
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'date_format',
                label: __('Date format'),
                elements: \Toolbox::phpDateFormats()
            ),
            new Option\ArrayOption($scopes_userpref, $personalize, 'names_format', __('Display order of surnames firstnames'), [
                'elements' => [
                    \User::REALNAME_BEFORE => __('Surname, First name'),
                    \User::FIRSTNAME_BEFORE => __('First name, Surname'),
                ]
            ]),
            new Option\ArrayOption($scopes_userpref, $personalize, 'number_format', __('Number format'), [
                'elements' => ['1 234.56', '1,234.56', '1 234,56', '1234.56', '1234,56']
            ]),
            new Option\NumberOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'list_limit',
                label: __('Results to display by page'),
                input_type: InputType::DROPDOWN_NUMBER,
                min: 5,
                step: 5,
            ),
            new Option\BooleanOption($scopes_userpref, $personalize, 'backcreated', __('Go to created item after creation'), InputType::DROPDOWN_YES_NO),
            new Option\BooleanOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'use_flat_dropdowntree',
                label: __('Display the tree dropdown complete name in dropdown inputs'),
                input_type: InputType::DROPDOWN_YES_NO
            ),
            new Option\BooleanOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'use_flat_dropdowntree_on_search_result',
                label: __('Display the complete name of tree dropdown in search results'),
                input_type: InputType::DROPDOWN_YES_NO
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'show_count_on_tabs',
                label: __('Display counters'),
                elements: [
                    -1 => __('Never'),
                    0 => __('No'),
                    1 => __('Yes'),
                ]
            ),
            new Option\BooleanOption($scopes_userpref, $personalize, 'is_ids_visible', __('Show GLPI ID'), InputType::DROPDOWN_YES_NO),
            new Option\BooleanOption($scopes_userpref, $personalize, 'keep_devices_when_purging_item', __('Keep devices when purging an item'), InputType::DROPDOWN_YES_NO),
            new Option\BooleanOption($scopes_userpref, $personalize, 'notification_to_myself', __('Notifications for my changes'), InputType::DROPDOWN_YES_NO),
            new Option\NumberOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'display_count_on_home',
                label: __('Results to display on home page'),
                input_type: InputType::DROPDOWN_NUMBER,
                min: 0,
                max: 30
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'pdffont',
                label: __('PDF export font'),
                elements: \GLPIPDF::getFontList()
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'csv_delimiter',
                label: __('CSV delimiter'),
                elements: [
                    ';' => ';',
                    ',' => ',',
                ]
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'palette',
                label: __('Color palette'),
                elements: (new \Config())->getPalettes(),
                escapeMarkup: 'function(m) { return m; }'
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'page_layout',
                label: __('Page layout'),
                elements: [
                    'horizontal' => __('Horizontal (menu in header)'),
                    'vertical' => __('Vertical (menu in sidebar)')
                ]
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'richtext_layout',
                label: __('Rich text field layout'),
                elements: [
                    'inline' => __('Inline (no toolbars)'),
                    'classic' => __('Classic (toolbar on top)')
                ]
            ),
            new Option\BooleanOption($scopes_userpref, $personalize, 'highcontrast_css', __('Enable high contrast'), InputType::DROPDOWN_YES_NO),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'timezone',
                label: __('Timezone'),
                elements: $DB->use_timezones ? $DB->getTimezones() : [],
                emptylabel: __('Use server configuration'),
                display_emptychoice: true
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'default_central_tab',
                label: __('Default central tab'),
                elements: $central->getTabNameForItem($central),
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize,
                name: 'timeline_order',
                label: __('Timeline order'),
                elements: [
                    \CommonITILObject::TIMELINE_ORDER_NATURAL => __('Natural order (old items on top, recent on bottom)'),
                    \CommonITILObject::TIMELINE_ORDER_REVERSE => __('Reverse order (old items on bottom, recent on top)'),
                ],
            ),
            new Option\BooleanOption($scopes_userpref, $personalize_assistance, 'followup_private', __('Private followups by default'), InputType::DROPDOWN_YES_NO),
            new Option\BooleanOption($scopes_userpref, $personalize_assistance, 'show_jobs_at_login', __('Show new tickets on the home page'), InputType::DROPDOWN_YES_NO),
            new Option\BooleanOption($scopes_userpref, $personalize_assistance, 'task_private', __('Private tasks by default'), InputType::DROPDOWN_YES_NO),
            new Option\ItemOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'default_requesttypes_id',
                label: __('Request sources by default'),
                itemtype: \RequestType::class,
                condition: ['is_active' => 1, 'is_ticketheader' => 1]
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'task_state',
                label: __('Tasks state by default'),
                elements: [
                    \Planning::INFO => _n('Information', 'Information', 1),
                    \Planning::TODO => __('To do'),
                    \Planning::DONE => __('Done')
                ],
            ),
            new Option\NumberOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'refresh_views',
                label: __('Automatically refresh data (tickets list, project kanban) in minutes.'),
                input_type: InputType::DROPDOWN_NUMBER,
                min: 1,
                max: 30,
                toadd: [0 => __('Never')]
            ),
            new Option\BooleanOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'set_default_tech',
                label: __('Pre-select me as a technician when creating a ticket'),
                input_type: InputType::DROPDOWN_YES_NO
            ),
            new Option\BooleanOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'set_default_requester',
                label: __('Pre-select me as a requester when creating a ticket'),
                input_type: InputType::DROPDOWN_YES_NO
            ),
            new Option\BooleanOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'set_followup_tech',
                label: __('Add me as a technician when adding a ticket follow-up'),
                input_type: InputType::DROPDOWN_YES_NO
            ),
            new Option\BooleanOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'set_solution_tech',
                label: __('Add me as a technician when adding a ticket solution'),
                input_type: InputType::DROPDOWN_YES_NO
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'timeline_action_btn_layout',
                label: __('Action button layout'),
                elements: [
                    \Config::TIMELINE_ACTION_BTN_MERGED => __('Merged'),
                    \Config::TIMELINE_ACTION_BTN_SPLITTED => __('Splitted'),
                ],
            ),
            new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'timeline_date_format',
                label: __('Timeline date display'),
                elements: [
                    \Config::TIMELINE_RELATIVE_DATE => __('Relative'),
                    \Config::TIMELINE_ABSOLUTE_DATE => __('Precise'),
                ],
            ),
        ];

        for ($i = 1; $i <= 6; $i++) {
            $options[] = new Option\ColorOption(
                scopes: $scopes_userpref,
                section: $personalize_assistance,
                name: 'priority_' . $i,
                label: sprintf(__('Priority color (%s)'), \Ticket::getPriorityName($i)),
            );
        }

        $options[] = new Option\ColorOption($scopes_userpref, $personalize_sla, 'duedateok_color', __('OK state color'));
        $sla_states = ['warning' => 'Warning', 'critical' => 'Critical'];

        foreach ($sla_states as $state => $label) {
            $options[] = new Option\ColorOption($scopes_userpref, $personalize_sla, "duedate{$state}_color", __("{$label} state color"));
            $options[] = new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize_sla,
                name: "duedate{$state}_unit",
                label: __("{$label} state unit"),
                elements: [
                    '%' => '%',
                    'hours' => _n('Hour', 'Hours', \Session::getPluralNumber()),
                    'days' => _n('Day', 'Days', \Session::getPluralNumber()),
                ]
            );
            $options[] = new Option\NumberOption($scopes_userpref, $personalize_sla, "duedate{$state}_less", __("{$label} state threshold"), InputType::DROPDOWN_NUMBER);
        }
        $options = [
            ...$options,
            ...[
                new Option\BooleanOption($scopes_userpref, $personalize_assistance, 'lock_autolock_mode', __('Auto-lock Mode'), InputType::DROPDOWN_YES_NO),
                new Option\BooleanOption(
                    scopes: $scopes_userpref,
                    section: $personalize_assistance,
                    name: 'lock_directunlock_notification',
                    label: __('Direct Notification (requester for unlock will be the notification sender)'),
                    input_type: InputType::DROPDOWN_YES_NO
                ),
            ]
        ];

        $dashboards = ['central' => 'Central', 'assets' => 'Assets', 'helpdesk' => 'Assistance', 'mini_ticket' => 'Tickets (mini dashboard)'];
        foreach ($dashboards as $dashboard => $label) {
            $options[] = new Option\ArrayOption(
                scopes: $scopes_userpref,
                section: $personalize_dashboards,
                name: "default_dashboard_{$dashboard}",
                label: __("Default dashboard for {$label}"),
                elements: Grid::getDashboardDropdownOptions(['context' => $dashboard === 'mini_ticket' ? 'mini_core' : 'core'])
            );
        }

        $options = [
            ...$options,
            ...[
                new Option\BooleanOption($scopes_userpref, $personalize_notifications, 'is_notif_enable_default', __('Enable notifications'), InputType::DROPDOWN_YES_NO),
                new Option\ArrayOption(
                    scopes: $scopes_userpref,
                    section: $personalize_notifications,
                    name: 'toast_location',
                    label: __('Notifcation location'),
                    elements: [
                        'top-left' => __('Top left'),
                        'top-right' => __('Top right'),
                        'bottom-left' => __('Bottom left'),
                        'bottom-right' => __('Bottom right'),
                    ]
                ),
            ]
        ];

        return $options;
    }
}
