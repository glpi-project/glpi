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

namespace Glpi\Api\HL\Controller;

use Auth;
use CommonDBTM;
use CommonITILObject;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\UI\ThemeManager;
use Group;
use Planning;
use Profile;
use Session;
use Toolbox;
use User;
use UserEmail;

/**
 * @phpstan-type EmailData = array{id: int, email: string, is_default: int, _links: array{'self': array{href: non-empty-string}}}
 */
#[Route(path: '/Administration', tags: ['Administration'])]
final class AdministrationController extends AbstractController
{
    use CRUDControllerTrait;

    public static function getRawKnownSchemas(): array
    {
        $schemas = [
            'User' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => User::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [ // Object-level extra permissions
                    'read' => static function () {
                        if (!Session::canViewAllEntities()) {
                            return [
                                'LEFT JOIN' => [
                                    'glpi_profiles_users' => [
                                        'ON' => [
                                            'glpi_profiles_users' => 'users_id',
                                            '_' => 'id',
                                        ],
                                    ],
                                ],
                                'WHERE' => [
                                    'glpi_profiles_users.entities_id' => $_SESSION['glpiactiveentities'],
                                ],
                            ];
                        }
                        return true;
                    },
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'username' => [
                        'x-field' => 'name',
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Username',
                    ],
                    'realname' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Real name',
                    ],
                    'firstname' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'First name',
                    ],
                    'phone' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Phone number',
                    ],
                    'phone2' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Phone number 2',
                    ],
                    'mobile' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Mobile phone number',
                    ],
                    'emails' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'Email addresses',
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'EmailAddress',
                            'x-join' => [
                                'table' => 'glpi_useremails',
                                'fkey' => 'id',
                                'field' => 'users_id',
                                'primary-property' => 'id', // Help the search engine understand the 'id' property is this object's primary key since the fkey and field params are reversed for this join.
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'description' => 'ID',
                                ],
                                'email' => [
                                    'type' => Doc\Schema::TYPE_STRING,
                                    'description' => 'Email address',
                                ],
                                'is_default' => [
                                    'type' => Doc\Schema::TYPE_BOOLEAN,
                                    'description' => 'Is default',
                                ],
                                'is_dynamic' => [
                                    'type' => Doc\Schema::TYPE_BOOLEAN,
                                    'description' => 'Is dynamic',
                                ],
                            ],
                        ],
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                    'is_active' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is active',
                    ],
                    'is_deleted' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is deleted',
                    ],
                    'password' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_PASSWORD,
                        'description' => 'Password',
                        'writeOnly' => true,
                    ],
                    'password2' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_PASSWORD,
                        'description' => 'Password confirmation',
                        'writeOnly' => true,
                    ],
                    'picture' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'picture',
                        'x-mapper' => static function ($v) {
                            global $CFG_GLPI;
                            $path = Toolbox::getPictureUrl($v, false);
                            if (!empty($path)) {
                                return $path;
                            }
                            return $CFG_GLPI["root_doc"] . '/pics/picture.png';
                        },
                    ],
                    'date_password_change' => [
                        'x-version-introduced' => '2.1.0',
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'description' => 'Date of last password change',
                        'readOnly' => true,
                        'x-field' => 'password_last_update',
                    ],
                    'location' => self::getDropdownTypeSchema(class: 'Location', full_schema: 'Location') + ['x-version-introduced' => '2.1.0'],
                    'authtype' => [
                        'x-version-introduced' => '2.1.0',
                        'type' => Doc\Schema::TYPE_NUMBER,
                        'enum' => [Auth::DB_GLPI, Auth::MAIL, Auth::LDAP, Auth::EXTERNAL, Auth::CAS, Auth::X509],
                        'description' => <<<EOD
                            - 1: GLPI database
                            - 2: Email
                            - 3: LDAP
                            - 4: External
                            - 5: CAS
                            - 6: X.509 Certificate
EOD,
                    ],
                    'last_login' => [
                        'x-version-introduced' => '2.1.0',
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    ],
                    'default_profile' => self::getDropdownTypeSchema(class: Profile::class, full_schema: 'Profile') + [
                        'x-version-introduced' => '2.1.0',
                        'description' => 'Default profile',
                    ],
                    'default_entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity') + [
                        'x-version-introduced' => '2.1.0',
                        'description' => 'Default entity',
                    ],
                ],
            ],
            'Group' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Group::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name',
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                    'completename' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Complete name',
                        'readOnly' => true,
                    ],
                    'parent' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-itemtype' => Group::class,
                        'x-full-schema' => 'Group',
                        'x-join' => [
                            'table' => 'glpi_groups',
                            'fkey' => 'groups_id',
                            'field' => 'id',
                        ],
                        'description' => 'Parent group',
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'description' => 'Name',
                            ],
                        ],
                    ],
                    'level' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Level',
                        'readOnly' => true,
                    ],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                ],
            ],
            'Entity' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Entity::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name',
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                    'completename' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Complete name',
                        'readOnly' => true,
                    ],
                    'parent' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-itemtype' => Entity::class,
                        'x-full-schema' => 'Entity',
                        'x-join' => [
                            'table' => 'glpi_entities',
                            'fkey' => 'entities_id',
                            'field' => 'id',
                        ],
                        'description' => 'Parent entity',
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'description' => 'ID',
                            ],
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'description' => 'Name',
                            ],
                        ],
                    ],
                    'level' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Level',
                        'readOnly' => true,
                    ],
                ],
            ],
            'Profile' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Profile::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name',
                    ],
                    'comment' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Comment',
                    ],
                ],
            ],
            'EmailAddress' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => UserEmail::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                    ],
                    'email' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Email address',
                    ],
                    'is_default' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is default',
                    ],
                    'is_dynamic' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Is dynamic',
                    ],
                ],
            ],
        ];

        $schemas['UserPreferences'] =  [
            'x-version-introduced' => '2.1.0',
            'x-table' => User::getTable(),
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-rights-conditions' => [
                'read' => $schemas['User']['x-rights-conditions']['read'],
            ],
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'description' => 'User ID',
                    'readOnly' => true,
                ],
                'language' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Language code (POSIX compliant format e.g. en_US or fr_FR)',
                ],
                'use_mode' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'enum' => [Session::NORMAL_MODE, Session::DEBUG_MODE],
                    'description' => <<<EOD
                        - 0: Normal mode
                        - 2: Debug mode
EOD,
                ],
                'list_limit' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'min' => 5,
                    'multipleOf' => 5,
                ],
                'date_format' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [0, 1, 2],
                    'description' => <<<EOD
                        - 0: YYYY-MM-DD
                        - 1: DD-MM-YYYY
                        - 2: MM-DD-YYYY
EOD,
                ],
                'number_format' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [0, 1, 2, 3, 4],
                    'description' => <<<EOD
                        - 0: 1 234.56
                        - 1: 1,234.56
                        - 2: 1 234,56,
                        - 3: 1234.56
                        - 4: 1234,56
EOD,
                ],
                'name_format' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [User::REALNAME_BEFORE, User::FIRSTNAME_BEFORE],
                    'description' => <<<EOD
                        - 0: Surname First name
                        - 1: First name Surname
EOD,
                    'x-field' => 'names_format',
                ],
                'csv_delimiter' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [';', ','],
                ],
                'is_ids_visible' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                ],
                'use_flat_dropdowntree' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Display the tree dropdown complete name in dropdown inputs',
                ],
                'use_flat_dropdowntree_on_search_result' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Display the complete name of tree dropdown in search results',
                ],
                'show_new_tickets_on_home' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Show new tickets on the home page',
                    'x-field' => 'show_jobs_at_login',
                ],
                'priority_color_verylow' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for very low priority',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'priority_1',
                ],
                'priority_color_low' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for low priority',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'priority_2',
                ],
                'priority_color_medium' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for medium priority',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'priority_3',
                ],
                'priority_color_high' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for high priority',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'priority_4',
                ],
                'priority_color_veryhigh' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for very high priority',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'priority_5',
                ],
                'priority_color_major' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for major priority',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'priority_6',
                ],
                'private_followups_by_default' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Private followups by default',
                    'x-field' => 'followup_private',
                ],
                'private_tasks_by_default' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Private tasks by default',
                    'x-field' => 'task_private',
                ],
                'default_requesttype' => self::getDropdownTypeSchema(class: 'RequestType', field: 'default_requesttypes_id', full_schema: 'RequestType'),
                'show_count_on_tabs' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Show counters on tabs',
                ],
                'refresh_view_interval' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'description' => 'Auto-refresh interval for tickets list, kanbans, and dashboards in minutes',
                    'min' => 0,
                    'max' => 30,
                    'x-field' => 'refresh_views',
                ],
                'set_default_tech' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Pre-select me as a technician when creating a ticket',
                ],
                'set_default_requester' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Pre-select me as a requester when creating a ticket',
                ],
                'set_followup_tech' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Add me as a technician when adding a ticket followup',
                ],
                'set_solution_tech' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Add me as a technician when adding a ticket solution',
                ],
                'home_list_limit' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'min' => 0,
                    'max' => 30,
                    'description' => 'Results to display on home page',
                    'x-field' => 'display_count_on_home',
                ],
                'notification_to_myself' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Notifications for my changes',
                ],
                'duedate_color_ok' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for on-time due dates',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'duedateok_color',
                ],
                'duedate_color_warning' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for warning due dates',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'duedatewarning_color',
                ],
                'duedate_color_critical' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Hex color code for overdue due dates',
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                    'x-field' => 'duedatecritical_color',
                ],
                'duedate_threshold_warning' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'x-field' => 'duedatewarning_less',
                ],
                'duedate_threshold_warning_unit' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['%', 'hours', 'days'],
                    'x-field' => 'duedatewarning_unit',
                ],
                'duedate_threshold_critical' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'x-field' => 'duedatecritical_less',
                ],
                'duedate_threshold_critical_unit' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['%', 'hours', 'days'],
                    'x-field' => 'duedatecritical_unit',
                ],
                'pdf_font' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'PDF export font',
                    'enum' => array_keys(\GLPIPDF::getFontList()),
                    'x-field' => 'pdffont',
                ],
                'keep_devices_when_purging_item' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Keep linked devices when purging an item',
                ],
                'show_new_item_after_creation' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Go to created item after creation',
                    'x-field' => 'backcreated',
                ],
                'default_task_state' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [Planning::INFO, Planning::TODO, Planning::DONE],
                    'description' => <<<EOT
                        Default state for new tasks
                        - 1: Information
                        - 2: To do
                        - 3: Done
EOT,
                    'x-field' => 'task_state',
                ],
                'default_task_state_planned' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [Planning::INFO, Planning::TODO, Planning::DONE],
                    'description' => <<<EOT
                        Default state for new planned tasks
                        - 1: Information
                        - 2: To do
                        - 3: Done
EOT,
                    'x-field' => 'planned_task_state',
                ],
                'palette' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'Color palette/theme',
                    'enum' => array_map(static fn($theme) => $theme->getKey(), ThemeManager::getInstance()->getAllThemes()),
                ],
                'page_layout' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['horizontal', 'vertical'],
                ],
                'timeline_order' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [CommonITILObject::TIMELINE_ORDER_NATURAL, CommonITILObject::TIMELINE_ORDER_REVERSE],
                    'description' => <<<EOT
                        - 0: Natural order (oldest first)
                        - 1: Reverse order (newest first)
EOT,
                ],
                'richtext_layout' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['inline', 'classic'],
                    'description' => <<<EOT
                        - inline: Toolbar displays at the cursor position and some options in right-click menu
                        - classic: Toolbar displays at the top of the text area
EOT,
                ],
                'autolock_mode' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Automatically lock items when editing',
                    'x-field' => 'lock_autolock_mode',
                ],
                'directunlock_notification' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Direct Notification (requester for unlock will be the notification sender)',
                    'x-field' => 'lock_directunlock_notification',
                ],
                'highcontrast_css' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Enable high contrast',
                    'x-field' => 'highcontrast_css',
                ],
                //TODO Add default dashboard options when dashboards added to HLAPI
                'default_homepage_tab' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [0, 1, 2, 3, 4],
                    'description' => <<<EOT
                        Default homepage tab
                        - 0: Dashboard
                        - 1: Personal view
                        - 2: Group view
                        - 3: Global view
                        - 4: RSS feeds
EOT,
                    'x-field' => 'default_central_tab',
                ],
                'toast_location' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => ['top-lest', 'top-right', 'bottom-left', 'bottom-right'],
                    'description' => 'Location for toast notifications',
                ],
                'timeline_action_button_layout' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [0, 1],
                    'description' => <<<EOT
                        Timeline action buttons layout
                        - 0: Merged
                        - 1: Split
EOT,
                    'x-field' => 'timeline_action_btn_layout',
                ],
                'timeline_date_format' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [0, 1],
                    'description' => <<<EOT
                        Timeline date format
                        - 0: Relative (e.g. "2 hours ago")
                        - 1: Absolute (e.g. "2025-01-01 14:00")
EOT,
                ],
                'default_is_notifications_enabled' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Enable notifications by default. If disabled, notifications on tickets, change and problems can be optionally enabled as needed but other items will not send notifications at all',
                    'x-field' => 'is_notif_enable_default',
                ],
                'show_search_form' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Show search form above results',
                ],
                'search_pagination_on_top' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Show search pagination above results',
                ],
            ],
        ];

        return $schemas;
    }

    #[Route(path: '/User', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'User')]
    public function searchUsers(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Group', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Group')]
    public function searchGroups(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Entity', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Entity')]
    public function searchEntities(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Profile', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(schema_name: 'Profile')]
    public function searchProfiles(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getParameters());
    }

    /**
     * @param int $users_id
     * @return EmailData[]
     */
    private function getEmailDataForUser(int $users_id): array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM' => UserEmail::getTable(),
            'WHERE' => [
                'users_id' => $users_id,
            ],
        ]);
        $emails = [];
        foreach ($iterator as $data) {
            $emails[] = [
                'id' => (int) $data['id'],
                'email' => (string) $data['email'],
                'is_default' => (int) $data['is_default'],
                '_links' => [
                    'self' => [
                        'href' => self::getAPIPathForRouteFunction(self::class, 'getMyEmail', ['id' => $data['id']]),
                    ],
                ],
            ];
        }
        return $emails;
    }

    #[Route(path: '/User/Me', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'User',
        description: 'Get the current user',
    )]
    public function me(Request $request): Response
    {
        $my_user_id = $this->getMyUserID();
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), ['id' => $my_user_id], $request->getParameters());
    }

    #[Route(path: '/User/Me/Email', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the current user\'s email addresses',
        responses: [
            new Doc\Response(new Doc\SchemaReference('EmailAddress[]')),
        ]
    )]
    public function getMyEmails(Request $request): Response
    {
        return new JSONResponse($this->getEmailDataForUser($this->getMyUserID()));
    }

    #[Route(path: '/User/Me/Email', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Create a new email address for the current user',
        parameters: [
            new Doc\Parameter(
                name: 'email',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                description: 'The email address to add',
                location: Doc\Parameter::LOCATION_BODY,
                required: true,
            ),
            new Doc\Parameter(
                name: 'is_default',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_BOOLEAN, default: false),
                description: 'Whether this email address should be the default one',
                location: Doc\Parameter::LOCATION_BODY,
            ),
        ],
    )]
    public function addMyEmail(Request $request): Response
    {
        if (!$request->hasParameter('email')) {
            return self::getInvalidParametersErrorResponse([
                'missing' => ['email'],
            ]);
        }
        $new_email = $request->getParameter('email');
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            return self::getInvalidParametersErrorResponse([
                'invalid' => [
                    ['name' => 'email', 'reason' => 'The provided email address does not appear to be formatted as an email address'],
                ],
            ]);
        }
        // Check if the email address is already in the DB
        $emails = $this->getEmailDataForUser($this->getMyUserID());
        foreach ($emails as $email) {
            if ($email['email'] === $new_email) {
                return new JSONResponse(
                    self::getErrorResponseBody(self::ERROR_ALREADY_EXISTS, 'The provided email address is already associated with this user'),
                    409,
                    [
                        'Location' => self::getAPIPathForRouteFunction(self::class, 'getMyEmail', ['id' => $email['id']]),
                    ]
                );
            }
        }

        // Create the new email address
        $email = new UserEmail();
        $emails_id = $email->add([
            'users_id' => $this->getMyUserID(),
            'email' => $new_email,
            'is_default' => $request->hasParameter('is_default') ? $request->getParameter('is_default') : false,
        ]);
        return self::getCRUDCreateResponse($emails_id, self::getAPIPathForRouteFunction(self::class, 'getMyEmail', ['id' => $emails_id]));
    }

    #[Route(path: '/User/Me/Email/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'EmailAddress',
        description: 'Get a specific email address for the current user',
    )]
    public function getMyEmail(Request $request): Response
    {
        $emails = $this->getEmailDataForUser($this->getMyUserID());
        foreach ($emails as $email) {
            if ($email['id'] == $request->getAttribute('id')) {
                return new JSONResponse($email);
            }
        }
        return self::getNotFoundErrorResponse();
    }

    #[Route(path: '/User/Me/Emails/Default', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: 'EmailAddress',
        description: 'Get the default email address for the current user',
    )]
    public function getMyDefaultEmail(Request $request): Response
    {
        $emails = $this->getEmailDataForUser($this->getMyUserID());
        foreach ($emails as $email) {
            if ($email['is_default']) {
                return new JSONResponse($email);
            }
        }
        return self::getNotFoundErrorResponse();
    }

    /**
     * Get the specified user picture as a Response
     * @param string $username The username of the user. Used in Content-Disposition header.
     * @param string|null $picture_path The path to the picture from the user's "picture" field.
     * @return Response A response with the picture as binary content (or the placeholder user picture if the user has no picture).
     */
    private function getUserPictureResponse(string $username, ?string $picture_path): Response
    {
        if ($picture_path !== null) {
            $picture_path = GLPI_PICTURE_DIR . '/' . $picture_path;
        } else {
            $picture_path = 'public/pics/picture.png';
        }
        $symfony_response = Toolbox::getFileAsResponse($picture_path, $username);

        return new Response($symfony_response->getStatusCode(), $symfony_response->headers->all(), $symfony_response->getContent());
    }

    #[Route(path: '/User/Me/Picture', methods: ['GET'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the picture for the current user'
    )]
    public function getMyPicture(Request $request): Response
    {
        global $DB;
        $it = $DB->request([
            'SELECT' => ['name', 'picture'],
            'FROM' => User::getTable(),
            'WHERE' => [
                'id' => $this->getMyUserID(),
            ],
        ]);
        $data = $it->current();
        return $this->getUserPictureResponse($data['name'], $data['picture']);
    }

    #[Route(path: '/User', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'User')]
    public function createUser(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getUserByID']);
    }

    #[Route(path: '/User/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'User')]
    public function getUserByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/username/{username}', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'User')]
    public function getUserByUsername(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters(), 'username');
    }

    #[Route(path: '/User/{id}/Picture', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the picture for the current user'
    )]
    public function getUserPictureByID(Request $request): Response
    {
        global $DB;
        $it = $DB->request([
            'SELECT' => ['name', 'picture'],
            'FROM' => User::getTable(),
            'WHERE' => [
                'id' => $request->getAttribute('id'),
            ],
        ]);
        $data = $it->current();
        return $this->getUserPictureResponse($data['name'], $data['picture']);
    }

    #[Route(path: '/User/username/{username}/Picture', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the picture for the current user'
    )]
    public function getUserPictureByUsername(Request $request): Response
    {
        global $DB;
        $it = $DB->request([
            'SELECT' => ['name', 'picture'],
            'FROM' => User::getTable(),
            'WHERE' => [
                'name' => $request->getAttribute('username'),
            ],
        ]);
        $data = $it->current();
        return $this->getUserPictureResponse($data['name'], $data['picture']);
    }

    #[Route(path: '/User/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'User')]
    public function updateUserByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/username/{username}', methods: ['PATCH'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'User')]
    public function updateUserByUsername(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters(), 'username');
    }

    #[Route(path: '/User/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'User')]
    public function deleteUserByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/username/{username}', methods: ['DELETE'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'User')]
    public function deleteUserByUsername(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters(), 'username');
    }

    private function getUsedOrManagedItems(int $users_id, bool $is_managed, array $request_params, string $api_version): Response
    {
        global $CFG_GLPI;

        // Create a union schema with all relevant item types
        $schema = Doc\Schema::getUnionSchemaForItemtypes(
            itemtypes: array_filter($CFG_GLPI['assignable_types'], static function ($t) use ($is_managed) {
                if (!\is_a($t, CommonDBTM::class, true)) {
                    return false; // Ignore invalid classes
                }
                return (new $t())->isField($is_managed ? 'users_id_tech' : 'users_id');
            }),
            api_version: $api_version
        );
        $rsql_filter = $request_params['filter'] ?? '';
        if (!empty($rsql_filter)) {
            $rsql_filter = "($rsql_filter);";
        }
        $user_field = $is_managed ? 'user_tech.id' : 'user.id';
        $rsql_filter .= "$user_field==$users_id";
        $request_params['filter'] = $rsql_filter;
        return ResourceAccessor::searchBySchema($schema, $request_params);
    }

    #[Route(path: '/User/Me/UsedItem', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the used items for the current user',
    )]
    public function getMyUsedItems(Request $request): Response
    {
        return $this->getUsedOrManagedItems($this->getMyUserID(), false, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/{id}/UsedItem', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the used items for a user',
    )]
    public function getUserUsedItemsByID(Request $request): Response
    {
        return $this->getUsedOrManagedItems($request->getAttribute('id'), false, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/username/{username}/UsedItem', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the used items for a user by username',
    )]
    public function getUserUsedItemsByUsername(Request $request): Response
    {
        $users_id = ResourceAccessor::getIDForOtherUniqueFieldBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), 'username', $request->getAttribute('username'));
        return $this->getUsedOrManagedItems($users_id, false, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/Me/ManagedItem', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the managed items for the current user',
    )]
    public function getMyManagedItems(Request $request): Response
    {
        return $this->getUsedOrManagedItems($this->getMyUserID(), true, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/{id}/ManagedItem', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the managed items for a user',
    )]
    public function getUserManagedItemsByID(Request $request): Response
    {
        return $this->getUsedOrManagedItems($request->getAttribute('id'), true, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/User/username/{username}/ManagedItem', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        description: 'Get the managed items for a user by username',
    )]
    public function getUserManagedItemsByUsername(Request $request): Response
    {
        $users_id = ResourceAccessor::getIDForOtherUniqueFieldBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), 'username', $request->getAttribute('username'));
        return $this->getUsedOrManagedItems($users_id, true, $request->getParameters(), $this->getAPIVersion($request));
    }

    #[Route(path: '/Group', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Group')]
    public function createGroup(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getGroupByID']);
    }

    #[Route(path: '/Group/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Group')]
    public function getGroupByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Group/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Group')]
    public function updateGroupByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Group/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Group')]
    public function deleteGroupByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Group', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Entity', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Entity')]
    public function createEntity(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getEntityByID']);
    }

    #[Route(path: '/Entity/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Entity')]
    public function getEntityByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Entity/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Entity')]
    public function updateEntityByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Entity/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Entity')]
    public function deleteEntityByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Entity', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Profile', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(schema_name: 'Profile')]
    public function createProfile(Request $request): Response
    {
        return ResourceAccessor::createBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getProfileByID']);
    }

    #[Route(path: '/Profile/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(schema_name: 'Profile')]
    public function getProfileByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Profile/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(schema_name: 'Profile')]
    public function updateProfileByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Profile/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(schema_name: 'Profile')]
    public function deleteProfileByID(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Profile', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/{id}/Preference', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\GetRoute(schema_name: 'UserPreferences')]
    public function getUserPreferencesByID(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('UserPreferences', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/Me/Preference', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\GetRoute(schema_name: 'UserPreferences')]
    public function getMyPreferences(Request $request): Response
    {
        $my_user_id = $this->getMyUserID();
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('UserPreferences', $this->getAPIVersion($request)), ['id' => $my_user_id], $request->getParameters());
    }

    #[Route(path: '/User/{username}/Preference', methods: ['GET'], requirements: ['username' => '[a-zA-Z0-9_]+'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\GetRoute(schema_name: 'UserPreferences')]
    public function getUserPreferencesByUsername(Request $request): Response
    {
        $users_id = ResourceAccessor::getIDForOtherUniqueFieldBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), 'username', $request->getAttribute('username'));
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('UserPreferences', $this->getAPIVersion($request)), ['id' => $users_id], $request->getParameters());
    }

    #[Route(path: '/User/{id}/Preference', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\UpdateRoute(schema_name: 'UserPreferences')]
    public function updateUserPreferencesByID(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('UserPreferences', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/User/Me/Preference', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\UpdateRoute(schema_name: 'UserPreferences')]
    public function updateMyPreferences(Request $request): Response
    {
        $my_user_id = $this->getMyUserID();
        return ResourceAccessor::updateBySchema($this->getKnownSchema('UserPreferences', $this->getAPIVersion($request)), ['id' => $my_user_id], $request->getParameters());
    }

    #[Route(path: '/User/{username}/Preference', methods: ['PATCH'], requirements: ['username' => '[a-zA-Z0-9_]+'])]
    #[RouteVersion(introduced: '2.1')]
    #[Doc\UpdateRoute(schema_name: 'UserPreferences')]
    public function updateUserPreferencesByUsername(Request $request): Response
    {
        $users_id = ResourceAccessor::getIDForOtherUniqueFieldBySchema($this->getKnownSchema('User', $this->getAPIVersion($request)), 'username', $request->getAttribute('username'));
        return ResourceAccessor::updateBySchema($this->getKnownSchema('UserPreferences', $this->getAPIVersion($request)), ['id' => $users_id], $request->getParameters());
    }
}
