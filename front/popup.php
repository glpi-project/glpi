<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkLoginUser();

if (isset($_GET["popup"])) {
   $_SESSION["glpipopup"]["name"] = $_GET["popup"];
}

if (isset($_SESSION["glpipopup"]["name"])) {
   switch ($_SESSION["glpipopup"]["name"]) {
      case "search_config" :
         Html::popHeader(__('Setup'), $_SERVER['PHP_SELF']);
         if (isset($_POST["add"])
             || isset($_POST["delete"])
             || isset($_POST["delete_x"])
             || isset($_POST["up"])
             || isset($_POST["up_x"])
             || isset($_POST["down"])
             || isset($_POST["down_x"])) {
            echo "<script type='text/javascript' >\n";
            echo "window.opener.location.reload();";
            echo "</script>";
         }
         include "displaypreference.form.php";
         break;

      case "test_rule" :
         Html::popHeader(__('Test'), $_SERVER['PHP_SELF']);
         include "rule.test.php";
         break;

      case "test_all_rules" :
         Html::popHeader(__('Test rules engine'), $_SERVER['PHP_SELF']);
         include "rulesengine.test.php";
         break;

      case "show_cache" :
         Html::popHeader(__('Cache information'), $_SERVER['PHP_SELF']);
         include "rule.cache.php";
         break;

      case "load_bookmark" :
         Html::popHeader(_n('Bookmark', 'Bookmarks', 2), $_SERVER['PHP_SELF']);
         $_GET["action"] = "load";
         include "bookmark.php";
         break;

      case "edit_bookmark" :
         Html::popHeader(_n('Bookmark', 'Bookmarks', 2), $_SERVER['PHP_SELF']);
         $_GET["action"] = "edit";
         include "bookmark.php";
         break;

      case "edit_user_notification" :
         Html::popHeader(__('Email followup'), $_SERVER['PHP_SELF']);
         switch ($_GET["itemtype"]) {
            case 'Ticket' :
               include "ticket_user.form.php";
               break;
            case 'Problem' :
               include "problem_user.form.php";
               break;
         }
         break;

      case "add_ldapuser" :
         Html::popHeader(__('Import a user'), $_SERVER['PHP_SELF']);
         include "ldap.import.php";
         break;

      case "list_notificationtags" :
         Html::popHeader(__('List of available tags'), $_SERVER['PHP_SELF']);
         include "notification.tags.php";
         break;

      case "show_kb" :
         Html::popHeader(__('Knowledge base'), $_SERVER['PHP_SELF']);
         $kb = new KnowbaseItem();
         $kb->check($_GET["id"],'r');
         $kb->showFull(true);
         break;

      case "display_options" :
         Html::popHeader(__('Display options'), $_SERVER['PHP_SELF']);
         include "display.options.php";
         break;

  }
   echo "<div class='center'><br><a href='javascript:window.close()'>".__('Close')."</a>";
   echo "</div>";
   Html::popFooter();
}
?>
