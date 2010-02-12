<?php
/*
 * @version $Id: entitytree.php 10411 2010-02-09 07:58:26Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '..');
   include (GLPI_ROOT . "/inc/includes.php");
}

simpleHeader($LANG['ldap'][35]);
checkLoginUser();

checkSeveralRightsOr(array('user'=>'w','ldap_import'=>'w'));

if (isset($_POST['toprocess']) && count($_POST['toprocess']) >0) {
   foreach ($_POST['toprocess'] as $key => $val) {
      if ($val == "on") {
         AuthLdap::ldapImportUserByServerId($key,0,$_SESSION['ldap_import']['ldapservers_id'],true);
      }
   }

   glpi_header($_SERVER['HTTP_REFERER']);
}

$_REQUEST['target']=$_SERVER['PHP_SELF'];
$_REQUEST['interface'] = AuthLdap::SIMPLE_INTERFACE;
$_REQUEST['mode'] = 0;
$_REQUEST['from_ticket'] = 1;
if (!isset($_REQUEST['action'])) {
   $_REQUEST['action'] = 'show';
}

AuthLdap::manageValuesInSession($_REQUEST);
AuthLdap::showUserImportForm($_REQUEST);
if (isset($_SESSION['ldap_import']['ldapservers_id']) &&
   $_SESSION['ldap_import']['ldapservers_id']) {
   echo "<br />";
   $users = AuthLdap::searchUser($_SERVER['PHP_SELF'],$_REQUEST);
}
ajaxFooter();
?>
