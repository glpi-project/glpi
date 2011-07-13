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
    * Show emails of a user
    *
    * @param $user User object
    * @return nothing
   **/
   static function showForUser(User $user) {
      global $DB;
      /// TODO to finish : option for edit / add / delete

      $users_id = $user->getID();

      if (!$user->can($users_id,'r')
            && $users_id != getLoginUserID()) {
         return false;
      }
      $canedit = $user->can($users_id,"w");

      $count = 0;
      $email = new UserEmail;
      foreach ($DB->request("glpi_useremails", "`users_id` = '$users_id'") as $data) {
         if ($count) {
            echo '<br>';
         }
         $count++;
         /// TODO Better solution maybe to put it on the front (specific request ?)
         if ($data['is_default']) {
            echo "<strong>";
         }
         echo $data['email'];
         if ($data['is_default']) {
            echo "</strong>";
         }

         if ($canedit) {
            echo "  -> canedit this TODO delete if not dynamic";
         }
      }
      if ($canedit) {
         echo "TODO ADD EMAIL";
      }
   }

   function prepareInputForAdd($input) {

      /// TODO first email is default
      

      return $input;
   }



   function post_updateItem($history=1) {
      global $LANG,$CFG_GLPI;


      /// TODO if default is set : unsed others for the users
   }


   function post_deleteFromDB() {
      global $LANG;


      /// TODO if default was set : set default to another email



      parent::post_deleteFromDB();
   }

}

?>