<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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


$NEEDED_ITEMS = array ('computer', 'document', 'group', 'infocom', 'monitor', 'networking',
                       'peripheral', 'phone', 'planning', 'printer', 'rulesengine', 'rule.tracking',
                       'software', 'supplier', 'tracking', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("create_ticket","1");

commonHeader("Helpdesk",$_SERVER['PHP_SELF'],"maintain","helpdesk");

if (isset($_POST["add"])) {
   $track = new Job();
   if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
      $splitter = explode("_",$_POST["_my_items"]);
      if (count($splitter) == 2) {
         $_POST["itemtype"] = $splitter[0];
         $_POST["items_id"] = $splitter[1];
      }
   }
   if ($newID=$track->add($_POST)) {
      logEvent($newID, "tracking", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][20]." $newID.");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   // TODO check the use of real name of field...(could also use Job->getEmpty)
   // Set default value...
   $values = array('users_id'             => $_SESSION["glpiID"],
                   'groups_id'            => 0,
                   'users_id_assign'      => 0,
                   'groups_id_assign'     => 0,
                   'name'                 => '',
                   'content'              => '',
                   'ticketscategories_id' => 0,
                   'priority'             => 3,
                   'requesttypes_id'      => $_SESSION["glpidefault_requesttypes_id"],
                   'hour'                 => 0,
                   'minute'               => 0,
                   'date'                 => date("Y-m-d H:i:s"),
                   'entities_id'          => $_SESSION["glpiactive_entity"],
                   'status'               => 'new',
                   'followup'             => array(),
                   'itemtype'             => 0,
                   'items_id'             => 0,
                   'plan'                 => array());

   // Restore saved value or override with page parameter
   foreach ($values as $name => $value) {
      if (isset($_REQUEST[$name])) {
         $values[$name] = $_REQUEST[$name];
      } else if (isset($_SESSION["helpdeskSaved"][$name])) {
         $values[$name] = $_SESSION["helpdeskSaved"]["$name"];
      }
   }
   // Clean text fields
   $values['name'] = stripslashes($values['name']);
   $values['content'] = cleanPostForTextArea($values['content']);

   if (isset($_SESSION["helpdeskSaved"])) {
      unset($_SESSION["helpdeskSaved"]);
   }

   showJobDetails($_SERVER['PHP_SELF'], 0, $values);
}

commonFooter();

?>