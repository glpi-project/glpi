<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class Ticket_User
class Ticket_User extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Ticket';
   public $items_id_1 = 'tickets_id';
   public $itemtype_2 = 'User';
   public $items_id_2 = 'users_id';

   var $no_form_page = true;


   static function getTicketUsers($tickets_id) {
      global $DB;

      $users = array();
      $query = "SELECT `glpi_tickets_users`.*
                FROM `glpi_tickets_users`
                WHERE `tickets_id` = '$tickets_id'";

      foreach ($DB->request($query) as $data) {
         $users[$data['type']][$data['users_id']] = $data;
      }
      return $users;
   }


   function canUpdateItem() {
      return (parent::canUpdateItem() || $this->fields['users_id'] == getLoginUserID());
   }


   /**
    * Check right on an item - overloaded to check user access to its datas
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID, $right, &$input=NULL) {

      if ($ID>0) {
       if ($this->fields['users_id']===getLoginUserID()) {
         return true;
       }
      }
      return parent::can($ID,$right,$input);
   }


   /**
    * Print the ticket user form for notification
    *
    * @param $ID integer ID of the item
    * @param $options array
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI,$LANG;

      $this->check($ID,'w');

      echo "<br><form method='post' action='".$CFG_GLPI['root_doc']."/front/popup.php'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'><td>".$LANG['job'][38]."&nbsp;:</td>";
      echo "<td>";
      $ticket = new Ticket();
      if ($ticket->getFromDB($this->fields["tickets_id"])) {
         echo $ticket->getField('name');
      }
      echo "</td></tr>";

      $user  = new User;
      $email = "";
      if ($user->getFromDB($this->fields["users_id"])) {
         $email = $user->getField('email');
      }

      echo "<tr class='tab_bg_2'><td>".$LANG['common'][34]."&nbsp;:</td>";
      echo "<td>".$user->getName()."</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['job'][19]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo('use_notification', $this->fields['use_notification']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['mailing'][118]."&nbsp;:</td>";
      echo "<td>";
      if (!empty($email) && NotificationMail::isUserAddressValid($email)) {
         echo $email;
      } else {
         echo "<input type='text' size='40' name='alternative_email' value='".
                $this->fields['alternative_email']."'>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>";
      echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "</td></tr>";

      echo "</table></div></form>";
   }


   function post_deleteFromDB() {

      $t = new Ticket();
      $t->updateDateMod($this->fields['tickets_id']);
      parent::post_deleteFromDB();
   }


   function post_addItem() {

      $t = new Ticket();
      $t->updateDateMod($this->fields['tickets_id']);
      parent::post_addItem();
   }

}

?>
