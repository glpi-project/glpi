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

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["start"])) {
   $_GET["start"] = 0;
}

if (!isset($_GET["sort"])) {
   $_GET["sort"]="";
}
if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}

$user = new User();
$groupuser = new Group_User();
//print_r($_POST);exit();
if (empty($_GET["id"]) && isset($_GET["name"])) {

   $user->getFromDBbyName($_GET["name"]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.form.php?id=".$user->fields['id']);
}

if (empty($_GET["name"])) {
   $_GET["name"] = "";
}

if (isset($_REQUEST['getvcard'])) {
   if (empty($_GET["id"])) {
      glpi_header($CFG_GLPI["root_doc"]."/front/user.php");
   }
   $user->check($_GET['id'],'r');
   $user->generateVcard($_GET["id"]);

} else if (isset($_POST["add"])) {
   $user->check(-1,'w',$_POST);

   // Pas de nom pas d'ajout
   if (!empty($_POST["name"]) && $newID=$user->add($_POST)) {
      Event::log($newID, "users", 4, "setup",
                 $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $user->check($_POST['id'],'w');
   $user->delete($_POST);
   Event::log(0,"users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]." ".$_POST["id"].".");
   glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset($_POST["restore"])) {
   $user->check($_POST['id'],'w');
   $user->restore($_POST);
   Event::log($_POST["id"],"users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset($_POST["purge"])) {
   $user->check($_POST['id'],'w');
   $user->delete($_POST,1);
   Event::log($_POST["id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset ($_POST["force_ldap_resynch"])) {
   checkRight('user_authtype','w');
   $user->check($_POST['id'],'w');

   $user->getFromDB($_POST["id"]);
   AuthLdap::ldapImportUserByServerId(array('method'=>AuthLDAP::IDENTIFIER_LOGIN,
                                            'value'=>$user->fields["name"]),true,
                                      $user->fields["auths_id"],true);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["update"])) {
   $user->check($_POST['id'],'w');
   $user->update($_POST);
   Event::log(0,"users", 5, "setup", $_SESSION["glpiname"]."  ".$LANG['log'][21]."  ".$_POST["name"].".");
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["addgroup"])) {
   $groupuser->check(-1,'w',$_POST);
   if ($groupuser->add($_POST)) {
      Event::log($_POST["users_id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][48]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["deletegroup"])) {
   if (count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         if ($groupuser->can($key,'w')) {
            $groupuser->delete(array('id' => $key));
         }
      }
   }
   Event::log($_POST["users_id"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][49]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["change_auth_method"])) {
   checkRight('user_authtype','w');
   $user->check($_POST['id'],'w');

   if (isset($_POST["auths_id"])) {
      User::changeAuthMethod(array($_POST["id"]), $_POST["authtype"], $_POST["auths_id"]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   if (!isset($_GET["ext_auth"])) {
      checkRight("user","r");
      commonHeader($LANG['title'][13],'',"admin","user");
      $user->showForm($_GET["id"]);
      commonFooter();

   } else {
      checkRight("import_externalauth_users","w");

      if (isset($_GET['add_ext_auth_ldap'])) {
         if (isset($_GET['login']) && !empty($_GET['login'])) {
            AuthLdap::importUserFromServers(array('name'=>$_GET['login']));
         }
         glpi_header($_SERVER['HTTP_REFERER']);
      }
      if (isset($_GET['add_ext_auth_simple'])) {
         if (isset($_GET['login']) && !empty($_GET['login'])) {
            $input = array('name'     => $_GET['login'],
                           '_extauth' => 1,
                           'add'      => 1);
            $user->check(-1,'w',$input);
            $newID = $user->add($input);
            Event::log($newID, "users", 4, "setup",
                       $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_GET['login'].".");
         }
         glpi_header($_SERVER['HTTP_REFERER']);
      }

      commonHeader($LANG['title'][13],'',"admin","user");
      User::showAddExtAuthForm();
      commonFooter();
   }
}

?>
