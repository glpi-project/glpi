<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkLoginUser();

if (isset($_GET["popup"])) {
   $_SESSION["glpipopup"]["name"] = $_GET["popup"];
}

if (isset($_SESSION["glpipopup"]["name"])) {
   switch ($_SESSION["glpipopup"]["name"]) {
      case "search_config" :
         popHeader($LANG['common'][12],$_SERVER['PHP_SELF']);
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
         popHeader($LANG['buttons'][50],$_SERVER['PHP_SELF']);
         include "rule.test.php";
         break;

      case "test_all_rules" :
         popHeader($LANG['rulesengine'][84],$_SERVER['PHP_SELF']);
         include "rulesengine.test.php";
         break;

      case "show_cache" :
         popHeader($LANG['rulesengine'][100],$_SERVER['PHP_SELF']);
         include "rule.cache.php";
         break;

      case "load_bookmark" :
         popHeader($LANG['Menu'][40],$_SERVER['PHP_SELF']);
         $_GET["action"] = "load";
         include "bookmark.php";
         break;

      case "edit_bookmark" :
         popHeader($LANG['Menu'][40],$_SERVER['PHP_SELF']);
         $_GET["action"] = "edit";
         include "bookmark.php";
         break;
      case "add_ldapuser" :
         popHeader($LANG['ldap'][35],$_SERVER['PHP_SELF']);
         include "ldap.import.php";
         break;
      case "add_ruleparameter" :
         popHeader($LANG['ldap'][35],$_SERVER['PHP_SELF']);
         include strtolower($_GET['sub_type']."Parameter.php");
         break;
      case "list_notificationtags" :
         popHeader($LANG['mailing'][138],$_SERVER['PHP_SELF']);
         include "notification.tags.php";
         break;
   }
   echo "<div class='center'><br><a href='javascript:window.close()'>".$LANG['buttons'][13]."</a>";
   echo "</div>";
   popFooter();
}

?>