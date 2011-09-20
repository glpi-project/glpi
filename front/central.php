<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT."/inc/includes.php");

Session::checkCentralAccess();

// Change profile system
if (isset($_POST['newprofile'])) {
   if (isset($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
      Session::changeProfile($_POST['newprofile']);
      if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         Html::redirect($CFG_GLPI['root_doc']."/front/helpdesk.public.php");
      }
      Html::redirect($_SERVER['PHP_SELF']);
   }
   Html::redirect(preg_replace("/entities_id.*/","",$_SERVER['HTTP_REFERER']));
}

// Manage entity change
if (isset($_GET["active_entity"])) {
   if (!isset($_GET["is_recursive"])) {
      $_GET["is_recursive"] = 0;
   }
   if (Session::changeActiveEntities($_GET["active_entity"],$_GET["is_recursive"])) {
      if (($_GET["active_entity"] == $_SESSION["glpiactive_entity"])
          && isset($_SERVER['HTTP_REFERER'])) {
         Html::redirect(preg_replace("/entities_id.*/","",$_SERVER['HTTP_REFERER']));
      }
   }
}

Html::header($LANG['common'][56],$_SERVER['PHP_SELF']);

// Redirect management
if (isset($_GET["redirect"])) {
   Toolbox::manageRedirect($_GET["redirect"]);
}

$central = new Central();
$central->show();


   $profiles = array('hotliner' => array('name'                      => 'hotliner',
                                         'interface'                 => 'central',
                                         'user'                      => 'r',
                                         'import_externalauth_users' => 'w',
                                         'create_ticket'             => '1',
                                         'assign_ticket'             => '1',
                                         'global_add_followups'      => '1',
                                         'update_ticket'             => '1',
                                         'show_all_ticket'           => '1',
                                         'show_full_ticket'          => '1',
                                         'show_planning'             => '1',
                                         'show_group_planning'       => '1',
                                         'show_all_planning'         => '1',
                                         'statistic'                 => '1',
                                         'password_update'           => '1',
                                         'helpdesk_hardware'         => '3',
                                         'helpdesk_item_type'        => addslashes('["Computer","Software","Phone"]'),
                                         'show_group_ticket'         => '1',
                                         'create_validation'         => '1',
                                         'update_own_followups'      => '1',
                                         'create_ticket_on_login'    => '1',),

                                 );
   foreach ($profiles as $profile => $data) {
      $query  = "INSERT INTO `glpi_profiles` (`".implode("`, `",array_keys($data))."`)
                  VALUES ('".implode("', '",$data)."')";
      echo $query;
   }

Html::footer();
?>