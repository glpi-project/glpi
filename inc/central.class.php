<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Central class
**/
class Central extends CommonGLPI {


   static function getTypeName($nb = 0) {

      // No plural
      return __('Standard interface');
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         $tabs = [
            1 => __('Personal View'),
            2 => __('Group View'),
            3 => __('Global View'),
            4 => _n('RSS feed', 'RSS feeds', Session::getPluralNumber()),
         ];

         $grid = new Glpi\Dashboard\Grid('central');
         if ($grid->canViewOneDashboard()) {
            array_unshift($tabs, __('Dashboard'));
         }

         return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 0 :
               $item->showGlobalDashboard();
               break;

            case 1 :
               $item->showMyView();
               break;

            case 2 :
               $item->showGroupView();
               break;

            case 3 :
               $item->showGlobalView();
               break;

            case 4 :
               $item->showRSSView();
               break;
         }
      }
      return true;
   }

   public function showGlobalDashboard() {
      echo "<table class='tab_cadre_central'>";
      Plugin::doHook('display_central');
      echo "</table>";

      self::showMessages();

      $default   = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('central');
      $dashboard = new Glpi\Dashboard\Grid($default);
      $dashboard->show();
   }


   /**
    * Show the central global view
   **/
   static function showGlobalView() {

      $showticket  = Session::haveRight("ticket", Ticket::READALL);
      $showproblem = Session::haveRight("problem", Problem::READALL);
      $show_change = Session::haveRight('change', Change::READALL);

      echo "<table class='tab_cadre_central'><tr class='noHover'>";
      echo "<td class='top' width='50%'>";
      echo "<table class='central'>";
      echo "<tr class='noHover'><td>";
      if ($showticket) {
         Ticket::showCentralCount();
      } else {
         Ticket::showCentralCount(true);
      }
      if ($showproblem) {
         Problem::showCentralCount();
      }
      if ($show_change) {
         Change::showCentralCount();
      }
      if (Contract::canView()) {
         Contract::showCentral();
      }
      echo "</td></tr>";
      echo "</table></td>";

      if (Session::haveRight("logs", READ)) {
         echo "<td class='top'  width='50%'>";

         //Show last add events
         Event::showForUser($_SESSION["glpiname"]);
         echo "</td>";
      }
      echo "</tr></table>";

      if ($_SESSION["glpishow_jobs_at_login"] && $showticket) {
         echo "<br>";
         Ticket::showCentralNewList();
      }
   }


   /**
    * Show the central personal view
   **/
   static function showMyView() {
      $showticket  = Session::haveRightsOr("ticket",
                                           [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN]);

      $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

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

      $twig_params = [
         'messages'  => self::getMessages(),
         'cards'     => []
      ];
      foreach ($lists as $list) {
         $card_params = [
            'start'              => 0,
            'status'             => $list['status'],
            'showgrouptickets'   => 'false'
         ];
         $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
         $twig_params['cards'][] = [
            'type'         => 'lazy',
            'body_class'   => 'p-0',
            'content'   => [
               'itemtype'  => $list['itemtype'],
               'widget'    => 'central_list',
               'params'    => $card_params + [
                  '_idor_token'  => $idor
               ]
            ]
         ];
      }

      $card_params = [
         'who' => Session::getLoginUserID()
      ];
      $idor = Session::getNewIDORToken(Planning::class, $card_params);
      $twig_params['cards'][] = [
         'type'         => 'lazy',
         'body_class'   => 'p-0',
         'content'   => [
            'itemtype'  => Planning::class,
            'widget'    => 'central_list',
            'params'    => $card_params + [
               '_idor_token'  => $idor
            ]
         ]
      ];

      $idor = Session::getNewIDORToken(Reminder::class);
      $twig_params['cards'][] = [
         'type'         => 'lazy',
         'body_class'   => 'p-0',
         'content'   => [
            'itemtype'  => Reminder::class,
            'widget'    => 'central_list',
            'params'    => [
               '_idor_token'  => $idor
            ]
         ]
      ];
      $idor = Session::getNewIDORToken(Reminder::class, [
         'personal'  => 'false'
      ]);
      if (Session::haveRight("reminder_public", READ)) {
         $twig_params['cards'][] = [
            'type'         => 'lazy',
            'body_class'   => 'p-0',
            'content'   => [
               'itemtype'  => Reminder::class,
               'widget'    => 'central_list',
               'params'    => [
                  'personal'     => 'false',
                  '_idor_token'  => $idor
               ]
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
   static function showRSSView() {

      $idor = Session::getNewIDORToken(RSSFeed::class, [
         'personal'  => 'true'
      ]);
      $twig_params = [
         'messages'  => self::getMessages(),
         'cards'     => [
            [
               'type'         => 'lazy',
               'body_class'   => 'p-0',
               'content'   => [
                  'itemtype'  => RSSFeed::class,
                  'widget'    => 'central_list',
                  'params'    => [
                     'personal'     => 'true',
                     '_idor_token'  => $idor
                  ]
               ]
            ]
         ]
      ];
      if (RSSFeed::canView()) {
         $idor = Session::getNewIDORToken(RSSFeed::class, [
            'personal'  => 'false'
         ]);
         $twig_params['cards'][] = [
            'type'         => 'lazy',
            'body_class'   => 'p-0',
            'content'   => [
               'itemtype'  => RSSFeed::class,
               'widget'    => 'central_list',
               'params'    => [
                  'personal'     => 'false',
                  '_idor_token'  => $idor
               ]
            ]
         ];
      }
      TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);
   }


   /**
    * Show the central group view
   **/
   static function showGroupView() {

      $showticket = Session::haveRightsOr("ticket", [Ticket::READALL, Ticket::READASSIGN]);

      $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

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

      foreach ($lists as $list) {
         $card_params = [
            'start'              => 0,
            'status'             => $list['status'],
            'showgrouptickets'   => 'true'
         ];
         $idor = Session::getNewIDORToken($list['itemtype'], $card_params);
         $twig_params['cards'][] = [
            'type'         => 'lazy',
            'body_class'   => 'p-0',
            'content'   => [
               'itemtype'  => $list['itemtype'],
               'widget'    => 'central_list',
               'params'    => $card_params + [
                     '_idor_token'  => $idor
                  ]
            ]
         ];
      }
      TemplateRenderer::getInstance()->display('central/widget_tab.html.twig', $twig_params);
   }


   public static function getMessages(): array {
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
            $messages['warnings'][] = sprintf(__('For security reasons, please change the password for the default users: %s'),
               implode(" ", $accounts));
         }

         if (file_exists(GLPI_ROOT . "/install/install.php")) {
            $messages['warnings'][] = sprintf(__('For security reasons, please remove file: %s'),
               "install/install.php");
         }

         $myisam_tables = $DB->getMyIsamTables();
         if (count($myisam_tables)) {
            $messages['warnings'][] = sprintf(
               __('%1$s tables not migrated to InnoDB engine.'),
               count($myisam_tables)
            );
         }
         if ($DB->areTimezonesAvailable()) {
            $not_tstamp = $DB->notTzMigrated();
            if ($not_tstamp > 0) {
               $messages['warnings'][] = sprintf(__('%1$s columns are not compatible with timezones usage.'), $not_tstamp)
                  . ' '
                  . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:timestamps');
            }
         }
         if (($non_utf8mb4_tables = $DB->getNonUtf8mb4Tables()->count()) > 0) {
            $messages['warnings'][] = sprintf(__('%1$s tables not migrated to utf8mb4 collation.'), $non_utf8mb4_tables)
               . ' '
               . sprintf(__('Run the "php bin/console %1$s" command to migrate them.'), 'glpi:migration:utf8mb4');
         }
      }

      if ($DB->isSlave() && !$DB->first_connection) {
         $messages['warnings'][] = __('SQL replica: read only');
      }

      return $messages;
   }


   static function showMessages() {

      $messages = self::getMessages();
      TemplateRenderer::getInstance()->display('central/messages.html.twig', [
         'messages'  => $messages
      ]);
   }

}
