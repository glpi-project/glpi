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
// Original Author of file: Nelly Mahu-Lasson
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"visibility.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_REQUEST['type']) && !empty($_REQUEST['type'])) {
   $display = false;
   switch ($_REQUEST['type']) {
      case 'User':
         User::dropdown(array('right' => 'reminder_public'));
         $display = true;
         break;

      case 'Group':
         $rand = Dropdown::show('Group');
         echo "<span id='subvisibility$rand'></span>";
         $params = array('items_id' => '__VALUE__',
                         'type'     => $_REQUEST['type']);

         Ajax::updateItemOnSelectEvent("dropdown_groups_id".$rand,"subvisibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/subvisibility.php",
                                       $params);
         $display = true;
         break;

      case 'Entity':
         Dropdown::show('Entity', array('entity' => $_SESSION['glpiactiveentities'],
                                        'value'  => $_SESSION['glpiactive_entity']));
         $display = true;
         break;

      case 'Profile':
         $rand = Dropdown::show('Profile', array('condition' => "`reminder_public` = 'r'
                                                                 OR `reminder_public` = 'w'"));
         echo "<span id='subvisibility$rand'></span>";
         $params = array('items_id' => '__VALUE__',
                         'type'     => $_REQUEST['type']);

         Ajax::updateItemOnSelectEvent("dropdown_profiles_id".$rand,"subvisibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/subvisibility.php",
                                       $params);
         $display= true;
         break;
   }

   if ($display) {
      echo "&nbsp;<input type='submit' name='addvisibility' value=\"".$LANG['buttons'][8]."\"
                   class='submit'>";
   }
}
?>