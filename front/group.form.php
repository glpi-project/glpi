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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$group = new Group;
$groupuser = new Group_User();

if (isset($_POST["add"])) {
   $group->check(-1,'w',$_POST);
   if ($newID=$group->add($_POST)) {
      Event::log($newID, "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $group->check($_POST["id"],'w');
   $group->delete($_POST);
   Event::log($_POST["id"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   glpi_header($CFG_GLPI["root_doc"]."/front/group.php");

} else if (isset($_POST["update"])) {
   $group->check($_POST["id"],'w');
   $group->update($_POST);
   Event::log($_POST["id"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["adduser"])) {
   $groupuser->check(-1,'w',$_POST);
   if ($groupuser->add($_POST)) {
      Event::log($_POST["groups_id"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][48]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["deleteuser"])) {
   if (isset($_POST["item"]) && count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         if ($groupuser->can($key,'w')) {
            $groupuser->delete(array('id'=>$key));
         }
      }
   }
   Event::log($_POST["groups_id"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][49]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["changegroup"]) && isset($_POST["groups_id"])) {
   if ($_POST["groups_id"] > 0 && isset($_POST['item'])) {
      foreach ($_POST['item'] as $type => $ids) {
         if (class_exists($type)) {
            $item = new $type();
            foreach ($ids as $id => $val) {
               if ($val && $item->can($id,'w')) {
                  $item->update(array('id'        => $id,
                                      'groups_id' => $_POST["groups_id"]));
               }
            }
         }
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   commonHeader($LANG['Menu'][36],$_SERVER['PHP_SELF'],"admin","group");
   $group->showForm($_GET["id"]);
   commonFooter();
}

?>
