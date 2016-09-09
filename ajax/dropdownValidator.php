<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
* @since version 0.85
*/

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["validatortype"])) {
   switch ($_POST["validatortype"]){
      case 'user' :
         if (isset($_POST['users_id_validate']['groups_id'])) {
            $_POST['users_id_validate'] = array();
         }
         $value = (isset($_POST['users_id_validate'][0]) ? $_POST['users_id_validate'][0] : 0);
         User::dropdown(array('name'   => !empty($_POST['name']) ? $_POST['name'].'[]'
                                                                 :'users_id_validate[]',
                              'entity' => $_POST['entity'],
                              'value'  => $value,
                              'right'  => $_POST['right']));
         break;

      case 'group' :
         $name = !empty($_POST['name']) ? $_POST['name'].'[groups_id]':'groups_id';
         $value = (isset($_POST['users_id_validate']['groups_id']) ? $_POST['users_id_validate']['groups_id'] : $_POST['groups_id']);

         $rand = Group::dropdown(array('name'      => $name,
                                       'value'     => $value,
                                       'entity'    => $_POST["entity"]));

         $param                        = array('validatortype' => 'list_users');
         $param['name']                = !empty($_POST['name']) ? $_POST['name'] : '';
         $param['users_id_validate']   = isset($_POST['users_id_validate'])
                                             ? $_POST['users_id_validate'] : '';
         $param['right']               = $_POST['right'];
         $param['entity']              = $_POST["entity"];
         $param['groups_id']           = '__VALUE__';
         Ajax::updateItemOnSelectEvent("dropdown_$name$rand", "show_list_users",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php",
                                       $param);
         if ($value) {
            $param['validatortype'] = 'list_users';
            $param['groups_id']     = $value;
            unset($param['users_id_validate']['groups_id']);
            Ajax::updateItem('show_list_users', $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php",
            $param);
         }
         echo "<br><span id='show_list_users'>&nbsp;</span>\n";
         break;

      case 'list_users' :
         if (isset($_POST['users_id_validate']['groups_id'])) {
            $_POST['users_id_validate'] = array();
         }
         $opt             = array('groups_id' => $_POST["groups_id"],
                                  'right'     => $_POST['right'],
                                  'entity'    => $_POST["entity"]);
         $data_users      = TicketValidation::getGroupUserHaveRights($opt);
         $users           = array();
         $param['values'] = array();
         $values          = array();
         if (isset($_POST['users_id_validate']) && is_array($_POST['users_id_validate'])) {
            $values = $_POST['users_id_validate'];
         }
         foreach($data_users as $data){
            $users[$data['id']] = formatUserName($data['id'], $data['name'], $data['realname'],
                                                 $data['firstname']);
            if (in_array($data['id'], $values)) {
               $param['values'][] = $data['id'];
            }
         }

         // Display all users
         if (isset($_POST['all_users'])
             && $_POST['all_users']) {
            $param['values'] =  array_keys($users);
         }
         $param['multiple']= true;
         $param['display'] = true;
         $param['size']    = count($users);

         $users = Toolbox::stripslashes_deep($users);
         $rand  = Dropdown::showFromArray(!empty($_POST['name']) ? $_POST['name']:'users_id_validate',
                                          $users, $param);

         // Display all/none buttons to select all or no users in group
         if (!empty($_POST['groups_id'])){
            echo "<br><br><a id='all_users' class='vsubmit'>".__('All')."</a>";
            $param_button['validatortype']      = 'list_users';
            $param_button['name']               = !empty($_POST['name']) ? $_POST['name']:'';
            $param_button['users_id_validate']  = '';
            $param_button['all_users']          = 1;
            $param_button['groups_id']          = $_POST['groups_id'];
            $param_button['entity']             = $_POST['entity'];
            $param_button['right']              = $_POST['right'];
            Ajax::updateItemOnEvent('all_users', 'show_list_users',
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php",
                                    $param_button, array('click'));

            echo "&nbsp;<a id='no_users' class='vsubmit'>".__('None')."</a>";
            $param_button['all_users'] = 0;
            Ajax::updateItemOnEvent('no_users', 'show_list_users',
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php",
                                    $param_button, array('click'));
         }
         break;
   }
}
?>