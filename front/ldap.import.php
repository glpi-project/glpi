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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$user = new User();
$user->checkGlobal('w');
checkRight('user_authtype','w');

commonHeader($LANG['setup'][3],$_SERVER['PHP_SELF'],"admin","user","ldap");

AuthLdap::manageValuesInSession($_REQUEST);

if ($_SESSION['ldap_import']['action'] == 'show') {
   $_REQUEST['target']=$_SERVER['PHP_SELF'];
   AuthLdap::showUserImportForm($_REQUEST);
   if (isset($_SESSION['ldap_import']['ldapservers_id']) &&
       $_SESSION['ldap_import']['ldapservers_id'] != NOT_AVAILABLE) {
      echo "<br />";
      AuthLdap::searchUser($_SERVER['PHP_SELF'],$_REQUEST);
   }
} else {
   if (isset($_SESSION["ldap_process"])) {
      if ($count = count($_SESSION["ldap_process"])) {
         $percent = min(100,round(100*($_SESSION["ldap_process_count"]-$count)/
                                  $_SESSION["ldap_process_count"],0));

         displayProgressBar(400,$percent);
         $key = array_pop($_SESSION["ldap_process"]);
         AuthLdap::ldapImportUserByServerId($key,
                                            $_SESSION['ldap_import']["mode"],
                                            $_SESSION['ldap_import']["ldapservers_id"],
                                            true);
         glpi_header($_SERVER['PHP_SELF']);

      } else {
         unset($_SESSION["ldap_process"]);
         displayProgressBar(400,100);

         echo "<div class='center b'>".$LANG['ocsng'][8]."<br>";
         echo "<a href='".$_SERVER['PHP_SELF']."'>".$LANG['buttons'][13]."</a></div>";
         unset($_SESSION["ldapservers_id"]);
         unset($_SESSION["mode"]);
         unset($_SESSION["interface"]);
         $_SESSION['ldap_import']['action'] = 'show';

      }
 } else {
      if (count($_POST['toprocess']) >0) {
         $_SESSION["ldap_process_count"] = 0;
         $_SESSION["ldapservers_id"] = $_SESSION['ldap_import']['ldapservers_id'];
         foreach ($_POST['toprocess'] as $key => $val) {
            if ($val == "on") {
               $_SESSION["ldap_process"][] = $key;
               $_SESSION["ldap_process_count"]++;
            }
         }
      }
      glpi_header($_SERVER['PHP_SELF']);
   }
}

commonFooter();

?>
