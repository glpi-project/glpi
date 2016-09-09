<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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


$group = new Group();
Session::checkRightsOr('group', array(CREATE, UPDATE));
Session::checkRight('user', User::UPDATEAUTHENT);

Html::header(__('LDAP directory link'), $_SERVER['PHP_SELF'], "admin", "group", "ldap");

if (isset($_GET['next'])) {
   AuthLdap::ldapChooseDirectory($_SERVER['PHP_SELF']);
} else {
   if (isset($_POST["change_ldap_filter"])) {
      if (isset($_POST["ldap_filter"])) {
         $_SESSION["ldap_group_filter"] = $_POST["ldap_filter"];
      }
      if (isset($_POST["ldap_filter2"])) {
         $_SESSION["ldap_group_filter2"] = $_POST["ldap_filter2"];
      }
      Html::redirect($_SERVER['PHP_SELF']);

   } else {
      if (!isset($_GET['start'])) {
         $_GET['start'] = 0;
      }
      if (isset($_SESSION["ldap_import"])) {
         unset($_SESSION["ldap_import"]);
      }

      if (!isset($_SESSION["ldap_server"])) {
         if (isset($_POST["ldap_server"])) {
            $_SESSION["ldap_server"] = $_POST["ldap_server"];
         } else {
            Html::redirect($CFG_GLPI["root_doc"]."/front/ldap.php");
         }
      }

      if (!AuthLdap::testLDAPConnection($_SESSION["ldap_server"])) {
         unset($_SESSION["ldap_server"]);
         echo "<div class='center b'>".__('Unable to connect to the LDAP directory')."<br>";
         echo "<a href='".$_SERVER['PHP_SELF']."?next=listservers'>".__('Back')."</a></div>";

      } else {
         if (!isset($_SESSION["ldap_group_filter"])) {
            $_SESSION["ldap_group_filter"] = '';
         }
         if (!isset($_SESSION["ldap_group_filter2"])) {
            $_SESSION["ldap_group_filter2"] = '';
         }
         if (isset($_GET["order"])) {
            $_SESSION["ldap_sortorder"] = $_GET["order"];
         }
         if (!isset($_SESSION["ldap_sortorder"])) {
            $_SESSION["ldap_sortorder"] = "ASC";
         }

         AuthLdap::displayLdapFilter($_SERVER['PHP_SELF'], false);

         AuthLdap::showLdapGroups($_SERVER['PHP_SELF'], $_GET['start'], 0,
                                  $_SESSION["ldap_group_filter"], $_SESSION["ldap_group_filter2"],
                                  $_SESSION["glpiactive_entity"], $_SESSION["ldap_sortorder"]);
      }

   }
}

Html::footer();
?>