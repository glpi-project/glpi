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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"visibility.php")) {
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

   switch ($_POST['type']) {
      case 'User' :
         User::dropdown(array('right' => $_POST['right']));
         $display = true;
         break;

      case 'Group' :
         $params             = array('rand' => $rand);
         $params['toupdate'] = array('value_fieldname'
                                                  => 'value',
                                     'to_update'  => "subvisibility$rand",
                                     'url'        => $CFG_GLPI["root_doc"]."/ajax/subvisibility.php",
                                     'moreparams' => array('items_id' => '__VALUE__',
                                                           'type'     => $_POST['type']));

         Group::dropdown($params);

         echo "<span id='subvisibility$rand'></span>";

         $display = true;
         break;

      case 'Entity' :
         Entity::dropdown(array('entity' => $_SESSION['glpiactiveentities'],
                                'value'  => $_SESSION['glpiactive_entity']));

         _e('Child entities');
         echo "&nbsp;";
         Dropdown::showYesNo('is_recursive');

         $display = true;
         break;

      case 'Profile' :
         $params             = array('rand'      => $rand,
                                     'condition' => "`".$_POST['right']."` IN ('r','w')");
         $params['toupdate'] = array('value_fieldname'
                                                  => 'value',
                                     'to_update'  => "subvisibility$rand",
                                     'url'        => $CFG_GLPI["root_doc"]."/ajax/subvisibility.php",
                                     'moreparams' => array('items_id' => '__VALUE__',
                                                           'type'     => $_POST['type']));

         Profile::dropdown($params);

         echo "<span id='subvisibility$rand'></span>";

         $display = true;
         break;
   }

   if ($display) {
      echo "&nbsp;<input type='submit' name='addvisibility' value=\""._sx('button','Add')."\"
                   class='submit'>";
   }
}
?>