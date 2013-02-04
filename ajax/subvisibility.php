<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"subvisibility.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_REQUEST['type']) && !empty($_REQUEST['type'])
    && isset($_REQUEST['items_id']) && $_REQUEST['items_id'] > 0) {
   switch ($_REQUEST['type']) {
      case 'Group' :
      case 'Profile' :
         
         $params = array('value' => $_SESSION['glpiactive_entity']);
         if (Session::isViewAllEntities()) {
            $params['toadd'] = array(-1 => $LANG['reminder'][3]);
         }
         echo "&nbsp;".$LANG['entity'][0]."&nbsp;:&nbsp;";
         Dropdown::show('Entity', $params);
         echo "&nbsp;".$LANG['entity'][9]."&nbsp;:&nbsp;";
         Dropdown::showYesNo('is_recursive');
         break;

   }
}
?>