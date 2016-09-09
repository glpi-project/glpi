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
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * UserEmail class
**/
class UserEmail  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action = false;

   // From CommonDBChild
   static public $itemtype        = 'User';
   static public $items_id        = 'users_id';
   public $dohistory              = true;


   static function getTypeName($nb=0) {
      return _n('Email', 'Emails', $nb);
   }


   /**
    * Get default email for user. If no default email get first one
    *
    * @param $users_id user ID
    *
    * @return default email, empty if no email set
   **/
   static function getDefaultForUser($users_id) {
      global $DB;

      // Get default one
      foreach ($DB->request("glpi_useremails",
                            "`users_id` = '$users_id' AND `is_default` = '1'") as $data) {
         return $data['email'];
      }

      // Get first if not default set
      foreach ($DB->request("glpi_useremails",
                            "`users_id` = '$users_id' AND `is_default` = '0'") as $data) {
         return $data['email'];
      }
      return '';
   }


   /**
    * Get all emails for user.
    *
    * @param $users_id user ID
    *
    * @return array of emails
   **/
   static function getAllForUser($users_id) {
      global $DB;

      $emails = array();

      // Get default one
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'") as $data) {
         $emails[] = $data['email'];
      }

      return $emails;
   }


   /**
    * is an email of the user
    *
    * @param $users_id           user ID
    * @param $email     string   email to check user ID
    *
    * @return boolean is this email set for the user ?
   **/
   static function isEmailForUser($users_id, $email) {
      global $DB;

      foreach ($DB->request("glpi_useremails",
                            "`users_id` = '$users_id' AND `email` = '$email'") as $data) {
         return true;
      }
      return false;
   }


   /**
    * @since version 0.84
    *
    * @param $field_name
    * @param $child_count_js_var
    *
    * @return string
   **/
   static function getJSCodeToAddForItemChild($field_name, $child_count_js_var) {

      return "<input title=\'".__s('Default email')."\' type=\'radio\' name=\'_default_email\'" .
             " value=\'-'+$child_count_js_var+'\'>&nbsp;" .
             "<input type=\'text\' size=\'30\' ". "name=\'" . $field_name .
             "[-'+$child_count_js_var+']\'>";
   }


   /**
    * @since version 0.85 (since 0.85 but param $id since 0.85)
    *
    * @param $canedit
    * @param $field_name
    * @param $id
   **/
   function showChildForItemForm($canedit, $field_name, $id) {

      if ($this->isNewID($this->getID())) {
         $value = '';
      } else {
         $value = Html::entities_deep($this->fields['email']);
      }

      $field_name = $field_name."[$id]";
      echo "<input title='".__s('Default email')."' type='radio' name='_default_email'
             value='".$this->getID()."'";
      if (!$canedit) {
         echo " disabled";
      }
      if ($this->fields['is_default']) {
         echo " checked";
      }
      echo ">&nbsp;";
      if (!$canedit || $this->fields['is_dynamic']) {
         echo "<input type='hidden' name='$field_name' value='$value'>";
         printf(__('%1$s %2$s'), $value, "<span class='b'>(". __('D').")</span>");
      } else {
         echo "<input type='text' size=30 name='$field_name' value='$value' >";
      }
   }


   /**
    * Show emails of a user
    *
    * @param $user User object
    *
    * @return nothing
   **/
   static function showForUser(User $user) {

      $users_id = $user->getID();

      if (!$user->can($users_id, READ)
          && ($users_id != Session::getLoginUserID())) {
         return false;
      }
      $canedit = ($user->can($users_id, UPDATE) || ($users_id == Session::getLoginUserID()));

      parent::showChildsForItemForm($user, '_useremails', $canedit);

   }


   /**
    * @param $user
   **/
   static function showAddEmailButton(User $user) {

      $users_id = $user->getID();
      if (!$user->can($users_id, READ) && ($users_id != Session::getLoginUserID())) {
         return false;
      }
      $canedit = ($user->can($users_id, UPDATE) || ($users_id == Session::getLoginUserID()));

      parent::showAddChildButtonForItemForm($user, '_useremails', $canedit);

      return;
   }


   function prepareInputForAdd($input) {

      // Check email validity
      if (!isset($input['email']) || empty($input['email'])) {
         return false;
      }

      // First email is default
      if (countElementsInTable($this->getTable(), "`users_id` = '".$input['users_id']."'") == 0) {
         $input['is_default'] = 1;
      }

      return parent::prepareInputForAdd($input);
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBTM::getNameField
    *
    * @return string
   **/
   static function getNameField() {
      return 'email';
   }


   function post_updateItem($history=1) {
      global $DB;

      // if default is set : unsed others for the users
      if (in_array('is_default', $this->updates)
          && ($this->input["is_default"] == 1)) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'
                         AND `users_id` = '".$this->fields['users_id']."'";
         $DB->query($query);
      }

      parent::post_updateItem($history);
   }


   function post_addItem() {
      global $DB;

      // if default is set : unset others for the users
      if (isset($this->fields['is_default']) && ($this->fields["is_default"] == 1)) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'
                         AND `users_id` = '".$this->fields['users_id']."'";
         $DB->query($query);
      }

      parent::post_addItem();

   }


   function post_deleteFromDB() {
      global $DB;

      // if default is set : set default to another one
      if ($this->fields["is_default"] == 1) {
         $query = "UPDATE `". $this->getTable()."`
                   SET `is_default` = '1'
                   WHERE `id` <> '".$this->fields['id']."'
                         AND `users_id` = '".$this->fields['users_id']."'
                   LIMIT 1";
         $DB->query($query);
      }

      parent::post_deleteFromDB();
   }

}
?>