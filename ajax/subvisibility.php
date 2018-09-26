<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "subvisibility.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_POST['type']) && !empty($_POST['type'])
    && isset($_POST['items_id']) && ($_POST['items_id'] > 0)) {

   $prefix = '';
   $suffix = '';
   if (isset($_POST['prefix']) && !empty($_POST['prefix'])) {
      $prefix = $_POST['prefix'].'[';
      $suffix = ']';
   }

   switch ($_POST['type']) {
      case 'Group' :
      case 'Profile' :
         $params = ['value' => $_SESSION['glpiactive_entity'],
                         'name'  => $prefix.'entities_id'.$suffix];
         if (Session::canViewAllEntities()) {
            $params['toadd'] = [-1 => __('No restriction')];
         }
         echo "<table class='tab_format'><tr><td>";
         echo __('Entity');
         echo "</td><td>";
         Entity::dropdown($params);
         echo "</td><td>";
         echo __('Child entities');
         echo "</td><td>";
         Dropdown::showYesNo($prefix.'is_recursive'.$suffix);
         echo "</td></tr></table>";
         break;
   }
}
