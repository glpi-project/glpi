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
if (strpos($_SERVER['PHP_SELF'], "visibility.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_POST['type']) && !empty($_POST['type'])
    && isset($_POST['right'])) {
   $display = false;
   $rand    = mt_rand();
   $prefix = '';
   $suffix = '';
   if (isset($_POST['prefix']) && !empty($_POST['prefix'])) {
      $prefix = $_POST['prefix'].'[';
      $suffix = ']';
   } else {
      $_POST['prefix'] = '';
   }

   echo "<table class='tab_format'><tr>";
   switch ($_POST['type']) {
      case 'User' :
         echo "<td>";
         User::dropdown(['right' => $_POST['right'],
                              'name'  => $prefix.'users_id'.$suffix]);
         echo "</td>";
         $display = true;
         break;

      case 'Group' :
         echo "<td>";
         $params             = ['rand' => $rand,
                                     'name' => $prefix.'groups_id'.$suffix];
         $params['toupdate'] = ['value_fieldname'
                                                  => 'value',
                                     'to_update'  => "subvisibility$rand",
                                     'url'        => $CFG_GLPI["root_doc"]."/ajax/subvisibility.php",
                                     'moreparams' => ['items_id' => '__VALUE__',
                                                           'type'     => $_POST['type'],
                                                           'prefix'   => $_POST['prefix']]];

         Group::dropdown($params);
         echo "</td><td>";
         echo "<span id='subvisibility$rand'></span>";
         echo "</td>";
         $display = true;
         break;

      case 'Entity' :
         echo "<td>";
         Entity::dropdown(['entity' => $_SESSION['glpiactiveentities'],
                                'value'  => $_SESSION['glpiactive_entity'],
                                'name'   => $prefix.'entities_id'.$suffix]);
         echo "</td><td>";
         echo __('Child entities');
         echo "</td><td>";
         Dropdown::showYesNo($prefix.'is_recursive'.$suffix);
         echo "</td>";
         $display = true;
         break;

      case 'Profile' :
         echo "<td>";
         $checkright   = (READ | CREATE | UPDATE | PURGE);
         $righttocheck = $_POST['right'];
         if ($_POST['right'] == 'faq') {
            $righttocheck = 'knowbase';
            $checkright   = KnowbaseItem::READFAQ;
         }
         $params             = ['rand'      => $rand,
                                     'name'      => $prefix.'profiles_id'.$suffix,
                                     'condition' => "`glpi_profilerights`.`name` = '$righttocheck' ".
                                                    " AND `glpi_profilerights`.`rights` & ".$checkright];
         $params['toupdate'] = ['value_fieldname'
                                                  => 'value',
                                     'to_update'  => "subvisibility$rand",
                                     'url'        => $CFG_GLPI["root_doc"]."/ajax/subvisibility.php",
                                     'moreparams' => ['items_id' => '__VALUE__',
                                                           'type'     => $_POST['type'],
                                                           'prefix'   => $_POST['prefix']]];

         Profile::dropdown($params);
         echo "</td><td>";
         echo "<span id='subvisibility$rand'></span>";
         echo "</td>";
         $display = true;
         break;
   }

   if ($display && (!isset($_POST['nobutton']) || !$_POST['nobutton'])) {
      echo "<td><input type='submit' name='addvisibility' value=\""._sx('button', 'Add')."\"
                   class='submit'></td>";
   } else {
      // For table w3c
      echo "<td>&nbsp;</td>";
   }
   echo "</tr></table>";
}
