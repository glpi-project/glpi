<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (strpos($_SERVER['PHP_SELF'],"private_public.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

if (isset($_POST['is_private'])) {
   Session::checkLoginUser();

   switch ($_POST['is_private']) {
      case true :
         echo "<input type='hidden' name='is_private' value='1'>\n";
         echo "<input type='hidden' name='entities_id' value='-1'>\n";
         echo "<input type='hidden' name='is_recursive' value='0'>\n";
         $private =  __('Personal');
         $link    = "<a onClick='setPublic".$_POST['rand']."()'>".__('Set public')."</a>";
         printf(__('%1$s - %2$s'), $private, $link);
         break;

      case false :
         if (isset($_POST['entities_id'])
             && in_array($_POST['entities_id'], $_SESSION['glpiactiveentities'])) {
            $val = $_POST['entities_id'];
         } else {
            $val = $_SESSION['glpiactive_entity'];
         }
         echo "<input type='hidden' name='is_private' value='0'>\n";
         echo __('Public')."&nbsp;";
         Entity::dropdown(array('value' => $val));
         echo "&nbsp;";
         _e('Child entities');
         echo "&nbsp;";
         Dropdown::showYesNo('is_recursive', $_POST["is_recursive"]);
         echo "&nbsp;<a onClick='setPrivate".$_POST['rand']."()'>".__('Set personal')."</a>";
         break;
   }
}
?>
