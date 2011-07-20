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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * UserEmail class
 */
class UserEmail  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action = false;

   // From CommonDBChild
   public $itemtype = 'User';
   public $items_id = 'users_id';


   static function getTypeName() {
      global $LANG;

      return $LANG['mailing'][118];
   }


   function canCreate() {
      // All users can create own emails
      return true;
   }

   function canCreateItem() {
      return (haveRight('user','w') || $this->fields['users_id'] == getLoginUserID());
   }

   function canView() {
      return true;
   }

   function canViewItem() {
      return (haveRight('user','r') || $this->fields['users_id'] == getLoginUserID());
   }

   function canUpdate() {
      // All users can update own emails
      return true;
   }


   function canDelete() {
      // All users can delete own emails
      return true;
   }

   /**
    * Get default email for user. If no default email get first one
    *
    * @param $users_id user ID
    * @return default email, empty if no email set
   **/
   static function getDefaultForUser($users_id) {
      global $DB;
      // Get default one
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'
                                                AND `is_default` = '1'") as $data) {
         return $data['email'];
      }

      // Get first if not default set
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'
                                                AND `is_default` = '0'") as $data) {
         return $data['email'];
      }
      return '';
   }

   /**
    * is an email of the user
    *
    * @param $users_id user ID
    * @param $email string email to check user ID
    * @return boolean is this email set for the user ?
   **/
   static function isEmailForUser($users_id, $email) {
      global $DB;

      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'
                                                AND `email` = '$email'") as $data) {
         return true;
      }
      return false;
   }

   /**
    * Show emails of a user
    *
    * @param $user User object
    * @return nothing
   **/
   static function showForUser(User $user) {
      global $DB, $LANG, $CFG_GLPI;

      $users_id = $user->getID();

      if (!$user->can($users_id,'r')
            && $users_id != getLoginUserID()) {
         return false;
      }
      $canedit = ($user->can($users_id,"w") || $users_id == getLoginUserID());

      $count = 0;
      /// Display default email
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'
                                                AND `is_default` = '1'") as $data) {

         if ($count) {
            echo '<br>';
         }
         $count++;
         echo "<strong>".$data['email']."</strong>";

         if (!NotificationMail::isUserAddressValid($data['email'])) {
            echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
         }
         if ($canedit) {
            // Can edit if not dynamic
            if (!$data['is_dynamic']) {
               echo "<a href='".$CFG_GLPI['root_doc'].
                     "/front/useremail.form.php?delete=delete&amp;id=".$data['id']."'>";
               echo "<img title=\"".$LANG['buttons'][6]."\" alt=\"".$LANG['buttons'][6]."\"
                        src='".$CFG_GLPI["root_doc"]."/pics/delete2.png'>";
               echo "</a>";
            }
         }
      }
 
      // Display others email
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'
                                                AND `is_default` = '0'") as $data) {
         if ($count) {
            echo '<br>';
         }
         $count++;

         echo $data['email'];

         if ($canedit) {
            // Can edit if not dynamic
            if (!$data['is_dynamic']) {
               echo "<a href='".$CFG_GLPI['root_doc'].
                     "/front/useremail.form.php?delete=delete&amp;id=".$data['id']."'>";
               echo "<img title=\"".$LANG['buttons'][6]."\" alt=\"".$LANG['buttons'][6]."\"
                        src='".$CFG_GLPI["root_doc"]."/pics/delete2.png'>";
               echo "</a>";
            }

            echo "<a href='".$CFG_GLPI['root_doc'].
                  "/front/useremail.form.php?update=update&amp;id=".$data['id']."&amp;is_default=1'>";
            echo "<img title=\"".$LANG['title'][26]."\" alt=\"".$LANG['title'][26]."\"
                     src='".$CFG_GLPI["root_doc"]."/pics/deplier_up.png'>";
            echo "</a>";
         }
      }
      if ($canedit) {
         echo "<div style='display:none' id='emailadd$users_id'>";
         echo "<input type='text' name='_add_email' value='' size='40'>\n";
         echo "</div>";
      }
   }

   static function showAddEmailButton(User $user) {
      global $LANG,$CFG_GLPI;

      $users_id = $user->getID();
      if (!$user->can($users_id,'r')
            && $users_id != getLoginUserID()) {
         return false;
      }
      $canedit = ($user->can($users_id,"w") || $users_id == getLoginUserID());

      if ($canedit) {
         echo "&nbsp;<a href='#' onClick=\"Ext.get('emailadd$users_id').setDisplayed('block')\">";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                  src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
         echo '</a>';
      }
   }

   function prepareInputForAdd($input) {

      // Check email validity
      if (!isset($input['email']) || !isset($input['users_id'])
         || empty($input['email']) || empty($input['users_id'])) {
         return false;
      }

      // First email is default
      if (countElementsInTable($this->getTable(),"`users_id`='".$input['users_id']."'") == 0) {
         $input['is_default'] = 1;
      }
      

      return $input;
   }



   function post_updateItem($history=1) {
      global $DB;

      // if default is set : unsed others for the users
      if (in_array('is_default',$this->updates) && $this->input["is_default"]==1) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'
                        AND `users_id` = '".$this->fields['users_id']."'";
         $DB->query($query);
      }

   }

   function post_addItem() {
      global $DB;

      // if default is set : unsed others for the users
      if (isset($this->fields['is_default']) && $this->fields["is_default"]==1) {
         $query = "UPDATE ". $this->getTable()."
                  SET `is_default` = '0'
                  WHERE `id` <> '".$this->fields['id']."'
                        AND `users_id` = '".$this->fields['users_id']."'";
         $DB->query($query);
      }
   }

}

?>