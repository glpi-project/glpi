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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use Glpi\Plugin\Hooks;

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
                1 => __('Personal View'),
                2 => __('Group View'),
                3 => __('Global View'),
                4 => _n('RSS feed', 'RSS feeds', Session::getPluralNumber()),
            ];

            $grid = new Glpi\Dashboard\Grid('central');
            if ($grid::canViewOneDashboard()) {
                array_unshift($tabs, __('Dashboard'));
            }

            return $tabs;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
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
        if (Session::haveRight("logs", READ)) {
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
                'status'    => 'tovalidate'
            ];
        }

        if ($showticket) {
            if (Ticket::isAllowedStatus(Ticket::SOLVED, Ticket::CLOSED)) {
                $lists[] = [
                    'itemtype'  => Ticket::class,
                    'status'    => 'toapprove'
                ];
            }

            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'survey'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'validation.rejected'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'solution.rejected'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'requestbyself'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'observed'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'process'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'waiting'
            ];
            $lists[] = [
                'itemtype'  => TicketTask::class,
                'status'    => 'todo'
            ];
        }

        if ($showproblem) {
            $lists[] = [
                'itemtype'  => Problem::class,
                'status'    => 'process'
            ];
            $lists[] = [
                'itemtype'  => ProblemTask::class,
                'status'    => 'todo'
            ];
        }

        if ($showchanges) {
            $lists[] = [
                'itemtype'  => Change::class,
                'status'    => 'process'
            ];
            $lists[] = [
                'itemtype'  => ChangeTask::class,
                'status'    => 'todo'
            ];
        }

        $twig_params = [
            'cards' => []
        ];
        foreach ($lists as $list) {
            $card_params = [
                'start'              => 0,
                'status'             => $list['status'],
                'showgrouptickets'   => 'false'
            ];
            $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
            $twig_params['cards'][] = [
                'itemtype'  => $list['itemtype'],
                'widget'    => 'central_list',
                'params'    => $card_params + [
                    '_idor_token'  => $idor
                ]
            ];
        }

        $card_params = [
            'who' => Session::getLoginUserID()
        ];
        $idor = Session::getNewIDORToken(Planning::class, $card_params);
        $twig_params['cards'][] = [
            'itemtype'  => Planning::class,
            'widget'    => 'central_list',
            'params'    => $card_params + [
                '_idor_token'  => $idor
            ]
        ];

        $idor = Session::getNewIDORToken(Reminder::class);
        $twig_params['cards'][] = [
            'itemtype'  => Reminder::class,
            'widget'    => 'central_list',
            'params'    => [
                '_idor_token'  => $idor
            ]
        ];
        $idor = Session::getNewIDORToken(Reminder::class, [
            'personal'  => 'false'
        ]);
        if (Session::haveRight("reminder_public", READ)) {
            $twig_params['cards'][] = [
                'itemtype'  => Reminder::class,
                'widget'    => 'central_list',
                'params'    => [
                    'personal'     => 'false',
                    '_idor_token'  => $idor
                ]
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
            'personal'  => 'true'
        ]);
        $twig_params = [
            'cards'     => [
                [
                    'itemtype'  => RSSFeed::class,
                    'widget'    => 'central_list',
                    'params'    => [
                        'personal'     => 'true',
                        '_idor_token'  => $idor
                    ]
                ]
            ]
        ];
        if (RSSFeed::canView()) {
            $idor = Session::getNewIDORToken(RSSFeed::class, [
                'personal'  => 'false'
            ]);
            $twig_params['cards'][] = [
                'itemtype'  => RSSFeed::class,
                'widget'    => 'central_list',
                'params'    => [
                    'personal'     => 'false',
                    '_idor_token'  => $idor
                ]
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
                'status'    => 'process'
            ];
            $lists[] = [
                'itemtype'  => TicketTask::class,
                'status'    => 'todo'
            ];
        }
        if (Session::haveRight('ticket', Ticket::READGROUP)) {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'waiting'
            ];
        }
        if ($showproblem) {
            $lists[] = [
                'itemtype'  => Problem::class,
                'status'    => 'process'
            ];
            $lists[] = [
                'itemtype'  => ProblemTask::class,
                'status'    => 'todo'
            ];
        }

        if ($showchange) {
            $lists[] = [
                'itemtype'  => Change::class,
                'status'    => 'process'
            ];
            $lists[] = [
                'itemtype'  => ChangeTask::class,
                'status'    => 'todo'
            ];
        }

        if (Session::haveRight('ticket', Ticket::READGROUP)) {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'observed'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'toapprove'
            ];
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'requestbyself'
            ];
        } else {
            $lists[] = [
                'itemtype'  => Ticket::class,
                'status'    => 'waiting'
            ];
        }

        $twig_params = [
            'cards' => [],
        ];
        foreach ($lists as $list) {
            $card_params = [
                'start'             => 0,
                'status'            => $list['status'],
                'showgrouptickets'  => 'true'
            ];
            $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
            $twig_params['cards'][] = [
                'itemtype'  => $list['itemtype'],
                'widget'    => 'central_list',
                'params'    => $card_params + [
                    '_idor_token'  => $idor
                ]
            ];
        }
        TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);
    }


    private static function getMessages(): array
    {
        global $DB, $CFG_GLPI;

        $messages = [];

        $user = new User();
        $user->getFromDB(Session::getLoginUserID());
        if ($user->fields['authtype'] == Auth::DB_GLPI && $user->shouldChangePassword()) {
            $expiration_msg = sprintf(
                __('Your password will expire on %s.'),
                Html::convDateTime(date('Y-m-d H:i:s', $user->getPasswordExpirationTime()))
            );
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
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:myisam_to_innodb');
            }
            if (($datetime_count = $DB->getTzIncompatibleTables()->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%1$s columns are using the deprecated datetime storage field type.'), $datetime_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:timestamps');
            }
            /*
             * FIXME: Remove `$exclude_plugins = true` condition in GLPI 10.1.
             * This condition is here only to prevent having this message displayed after installation of plugins that
             * may not have yet handle the switch to utf8mb4.
             */
            if (($non_utf8mb4_count = $DB->getNonUtf8mb4Tables(true)->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%1$s tables are using the deprecated utf8mb3 storage charset.'), $non_utf8mb4_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:utf8mb4');
            }
            /*
             * FIXME: Remove `$exclude_plugins = true` condition in GLPI 10.1.
             * This condition is here only to prevent having this message displayed after installation of plugins that
             * may not have yet handle the switch to unsigned keys.
             */
            if (($signed_keys_col_count = $DB->getSignedKeysColumns(true)->count()) > 0) {
                $messages['warnings'][] = sprintf(__('%d primary or foreign keys columns are using signed integers.'), $signed_keys_col_count)
                . ' '
                . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:unsigned_keys');
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
            'messages'  => $messages
        ]);
    }
}
