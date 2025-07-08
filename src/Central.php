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
use Glpi\Dashboard\Dashboard;
use Glpi\Event;
use Glpi\Plugin\Hooks;
use Glpi\System\Requirement\PhpSupportedVersion;
use Glpi\System\Requirement\SessionsSecurityConfiguration;

/**
 * Central class
 **/
class Central extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {

        // No plural
        return __('Standard interface');
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);

        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            $tabs = [
                1 => self::createTabEntry(__('Personal View'), 0, null, User::getIcon()),
                2 => self::createTabEntry(__('Group View'), 0, null, Group::getIcon()),
                3 => self::createTabEntry(__('Global View'), 0, null, 'ti ti-world'),
                4 => self::createTabEntry(_n('RSS feed', 'RSS feeds', Session::getPluralNumber()), 0, null, RSSFeed::getIcon()),
            ];

            $grid = new Glpi\Dashboard\Grid('central');
            if ($grid::canViewOneDashboard()) {
                array_unshift($tabs, self::createTabEntry(__('Dashboard'), 0, null, Dashboard::getIcon()));
            }

            return $tabs;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item instanceof self) {
            switch ($tabnum) {
                case 0:
                    $item->showGlobalDashboard();
                    break;

                case 1:
                    $item->showMyView();
                    break;

                case 2:
                    $item->showGroupView();
                    break;

                case 3:
                    $item->showGlobalView();
                    break;

                case 4:
                    $item->showRSSView();
                    break;
            }
        }
        return true;
    }

    public function showGlobalDashboard()
    {
        echo "<table class='tab_cadre_central'>";
        Plugin::doHook(Hooks::DISPLAY_CENTRAL);
        echo "</table>";

        if (GLPI_CENTRAL_WARNINGS) {
            self::showMessages();
        }

        $default   = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('central');
        $dashboard = new Glpi\Dashboard\Grid($default);
        $dashboard->show();
    }


    /**
     * Show the central global view
     **/
    public static function showGlobalView()
    {

        $showticket  = Session::haveRight("ticket", Ticket::READALL);
        $showproblem = Session::haveRight("problem", Problem::READALL);
        $show_change = Session::haveRight('change', Change::READALL);

        $grid_items = [];

        $grid_items[] = Ticket::showCentralCount(!$showticket, false);
        if ($showproblem) {
            $grid_items[] = Problem::showCentralCount(false, false);
        }
        if ($show_change) {
            $grid_items[] = Change::showCentralCount(false, false);
        }
        if (Contract::canView()) {
            $grid_items[] = Contract::showCentral(false);
        }
        if (Session::haveRight(Log::$rightname, READ)) {
            //Show last add events
            $grid_items[] = Event::showForUser($_SESSION["glpiname"], false);
        }

        if ($_SESSION["glpishow_jobs_at_login"] && $showticket) {
            Ticket::showCentralNewList();
        }

        TemplateRenderer::getInstance()->display('components/masonry_grid.html.twig', [
            'grid_items' => $grid_items,
        ]);
    }


    /**
     * Show the central personal view
     **/
    public static function showMyView()
    {
        $showticket  = Session::haveRightsOr(
            "ticket",
            [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN]
        );

        $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

        $showchanges = Session::haveRightsOr('change', [Change::READALL, Change::READMY]);

        $lists = [];

        if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'tovalidate',
            ];
        }

        if ($showticket) {
            if (Ticket::isAllowedStatus(Ticket::SOLVED, Ticket::CLOSED)) {
                $lists[] = [
                    'itemtype'  => Ticket::class,
                    'status'    => 'toapprove',
                ];
            }

            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'survey',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'validation.rejected',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'solution.rejected',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'requestbyself',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'observed',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'process',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'waiting',
            ];
            $lists[] = [
                'itemtype'  => TicketTask::class,
                'status'    => 'todo',
            ];
        }

        if ($showproblem) {
            $lists[] = [
                'itemtype'  => Problem::class,
                'status'    => 'process',
            ];
            $lists[] = [
                'itemtype'  => ProblemTask::class,
                'status'    => 'todo',
            ];
        }

        if (Session::haveRightsOr('changevalidation', ChangeValidation::getValidateRights())) {
            $lists[] = [
                'itemtype'  => Change::class,
                'status'    => 'tovalidate',
            ];
        }

        if ($showchanges) {
            $lists[] = [
                'itemtype'  => Change::class,
                'status'    => 'process',
            ];
            $lists[] = [
                'itemtype'  => ChangeTask::class,
                'status'    => 'todo',
            ];
        }

        $twig_params = [
            'cards' => [],
        ];
        foreach ($lists as $list) {
            $card_params = [
                'start'              => 0,
                'status'             => $list['status'],
                'showgrouptickets'   => 'false',
            ];
            $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
            $twig_params['cards'][] = [
                'itemtype'  => $list['itemtype'],
                'widget'    => 'central_list',
                'params'    => $card_params + [
                    '_idor_token'  => $idor,
                ],
            ];
        }

        $card_params = [
            'who' => Session::getLoginUserID(),
        ];
        $idor = Session::getNewIDORToken(Planning::class, $card_params);
        $twig_params['cards'][] = [
            'itemtype'  => Planning::class,
            'widget'    => 'central_list',
            'params'    => $card_params + [
                '_idor_token'  => $idor,
            ],
        ];

        $idor = Session::getNewIDORToken(Reminder::class);
        $twig_params['cards'][] = [
            'itemtype'  => Reminder::class,
            'widget'    => 'central_list',
            'params'    => [
                '_idor_token'  => $idor,
            ],
        ];
        $idor = Session::getNewIDORToken(Reminder::class, [
            'personal'  => 'false',
        ]);
        if (Session::haveRight("reminder_public", READ)) {
            $twig_params['cards'][] = [
                'itemtype'  => Reminder::class,
                'widget'    => 'central_list',
                'params'    => [
                    'personal'     => 'false',
                    '_idor_token'  => $idor,
                ],
            ];
        }
        $idor = Session::getNewIDORToken(Project::class);
        if (Session::haveRight("project", Project::READMY)) {
            $twig_params['cards'][] = [
                'itemtype'  => Project::class,
                'widget'    => 'central_list',
                'params'    => $card_params + [
                    'itemtype'      => \User::getType(),
                    '_idor_token'  => $idor,
                ],
            ];
        }
        $idor = Session::getNewIDORToken(ProjectTask::class);
        if (Session::haveRight("projecttask", ProjectTask::READMY)) {
            $twig_params['cards'][] = [
                'itemtype'  => ProjectTask::class,
                'widget'    => 'central_list',
                'params'    => $card_params + [
                    'itemtype'      => \User::getType(),
                    '_idor_token'  => $idor,
                ],
            ];
        }

        TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);
    }


    /**
     * Show the central RSS view
     *
     * @since 0.84
     **/
    public static function showRSSView()
    {

        $idor = Session::getNewIDORToken(RSSFeed::class, [
            'personal'  => 'true',
        ]);
        $twig_params = [
            'cards'     => [
                [
                    'itemtype'  => RSSFeed::class,
                    'widget'    => 'central_list',
                    'params'    => [
                        'personal'     => 'true',
                        '_idor_token'  => $idor,
                    ],
                ],
            ],
        ];
        if (RSSFeed::canView()) {
            $idor = Session::getNewIDORToken(RSSFeed::class, [
                'personal'  => 'false',
            ]);
            $twig_params['cards'][] = [
                'itemtype'  => RSSFeed::class,
                'widget'    => 'central_list',
                'params'    => [
                    'personal'     => 'false',
                    '_idor_token'  => $idor,
                ],
            ];
        }
        TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);
    }


    /**
     * Show the central group view
     **/
    public static function showGroupView()
    {

        $showticket = Session::haveRightsOr("ticket", [Ticket::READALL, Ticket::READASSIGN]);

        $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

        $showchange = Session::haveRightsOr('change', [Change::READALL, Change::READMY]);

        $lists = [];

        if ($showticket) {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'process',
            ];
            $lists[] = [
                'itemtype'  => TicketTask::class,
                'status'    => 'todo',
            ];
        }
        if (Session::haveRight('ticket', Ticket::READGROUP)) {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'waiting',
            ];
        }
        if ($showproblem) {
            $lists[] = [
                'itemtype'  => Problem::class,
                'status'    => 'process',
            ];
            $lists[] = [
                'itemtype'  => ProblemTask::class,
                'status'    => 'todo',
            ];
        }

        if ($showchange) {
            $lists[] = [
                'itemtype'  => Change::class,
                'status'    => 'process',
            ];
            $lists[] = [
                'itemtype'  => ChangeTask::class,
                'status'    => 'todo',
            ];
        }

        if (Session::haveRight('ticket', Ticket::READGROUP)) {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'observed',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'toapprove',
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'requestbyself',
            ];
        } else {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'waiting',
            ];
        }

        $twig_params = [
            'cards' => [],
        ];
        foreach ($lists as $list) {
            $card_params = [
                'start'             => 0,
                'status'            => $list['status'],
                'showgrouptickets'  => 'true',
            ];
            $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
            $twig_params['cards'][] = [
                'itemtype'  => $list['itemtype'],
                'widget'    => 'central_list',
                'params'    => $card_params + [
                    '_idor_token'  => $idor,
                ],
            ];
        }

        $idor = Session::getNewIDORToken(Project::class);
        if (Session::haveRight("project", Project::READMY)) {
            $twig_params['cards'][] = [
                'itemtype'  => Project::class,
                'widget'    => 'central_list',
                'params'    => [
                    'itemtype'    => \Group::getType(),
                    '_idor_token' => $idor,
                ],
            ];
        }
        $idor = Session::getNewIDORToken(ProjectTask::class);
        if (Session::haveRight("projecttask", ProjectTask::READMY)) {
            $twig_params['cards'][] = [
                'itemtype'  => ProjectTask::class,
                'widget'    => 'central_list',
                'params'    => [
                    'itemtype'    => \Group::getType(),
                    '_idor_token' => $idor,
                ],
            ];
        }

        TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);
    }


    private static function getMessages(): array
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $messages = [];

        $user = new User();
        $user->getFromDB(Session::getLoginUserID());
        $expiration_msg = $user->getPasswordExpirationMessage();
        if ($expiration_msg !== null) {
            $messages['warnings'][] = $expiration_msg
             . ' '
             . '<a href="' . $CFG_GLPI['root_doc'] . '/front/updatepassword.php">'
             . __('Update my password')
             . '</a>';
        }

        if (Session::haveRight("config", UPDATE)) {
            $logins = User::checkDefaultPasswords();
            $user   = new User();
            if (!empty($logins)) {
                $accounts = [];
                foreach ($logins as $login) {
                    $user->getFromDBbyNameAndAuth($login, Auth::DB_GLPI, 0);
                    $accounts[] = $user->getLink();
                }
                $messages['warnings'][] = sprintf(
                    __('For security reasons, please change the password for the default users: %s'),
                    implode(" ", $accounts)
                );
            }

            if (file_exists(GLPI_ROOT . "/install/install.php")) {
                $messages['warnings'][] = sprintf(
                    __('For security reasons, please remove file: %s'),
                    "install/install.php"
                );
            }

            if (($myisam_count = $DB->getMyIsamTables()->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%d tables are using the deprecated MyISAM storage engine.'), $myisam_count)
                . ' '
                . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:myisam_to_innodb');
            }
            if (($datetime_count = $DB->getTzIncompatibleTables()->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%1$s columns are using the deprecated datetime storage field type.'), $datetime_count)
                . ' '
                . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:timestamps');
            }
            if (($non_utf8mb4_count = $DB->getNonUtf8mb4Tables()->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%1$s tables are using the deprecated utf8mb3 storage charset.'), $non_utf8mb4_count)
                . ' '
                . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:utf8mb4');
            }
            if (($signed_keys_col_count = $DB->getSignedKeysColumns()->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%d primary or foreign keys columns are using signed integers.'), $signed_keys_col_count)
                . ' '
                . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:unsigned_keys');
            }

            /*
             * Check if there are pending reasons items and the notification is not active
             * If so, display a warning message
             */
            $notification = new \Notification();
            if (
                \Config::getConfigurationValue('core', 'use_notifications')
                && countElementsInTable('glpi_pendingreasons_items') > 0
                && !count($notification->find([
                    'itemtype' => 'Ticket',
                    'event'     => 'auto_reminder',
                    'is_active'  => true,
                ]))
            ) {
                $criteria = [
                    'criteria' => [
                        0 => [
                            'link' => 'AND',
                            'field' => 2,
                            'searchtype' => 'equals',
                            'value' => 'Ticket$#$auto_reminder',
                        ],
                    ],
                ];
                $link = '<a href="' . Notification::getSearchURL() . '?' . Toolbox::append_params($criteria) . '">' . __('notification') . '</a>';

                $messages['warnings'][] = sprintf(
                    __('You have defined pending reasons without any respective active %s.'),
                    $link
                );
            }

            $security_requirements = [
                new PhpSupportedVersion(),
                new SessionsSecurityConfiguration(),
            ];
            foreach ($security_requirements as $requirement) {
                if (!$requirement->isValidated()) {
                    $messages['warnings'] = array_merge(($messages['warnings'] ?? []), $requirement->getValidationMessages());
                }
            }
        }

        if ($DB->isSlave() && !$DB->first_connection) {
            $messages['warnings'][] = __('SQL replica: read only');
        }

        return $messages;
    }


    public static function showMessages()
    {

        $messages = self::getMessages();
        TemplateRenderer::getInstance()->display('central/messages.html.twig', [
            'messages'  => $messages,
        ]);
    }
}
