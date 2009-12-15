<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// TicketFollowup class
class TicketFollowup  extends CommonDBTM {


   // From CommonDBTM
   public $table = 'glpi_ticketfollowups';
   public $type = 'TicketFollowup';

   function cleanDBonPurge($ID) {
      global $DB;

      $querydel = "DELETE
                   FROM `glpi_ticketplannings`
                   WHERE `ticketfollowups_id` = '$ID'";
      $DB->query($querydel);
   }

   static function canCreate() {
      return (haveRight('comment_all_ticket', 1)
              || haveRight('comment_ticket', 1)
              || haveRight('own_ticket', 1));
   }

   static function canView() {
      return (haveRight('observe_ticket', 1)
              || haveRight('show_full_ticket', 1)
              || haveRight('own_ticket', 1));
   }

   /**
   * Check right on an item
   *
   * @param $ID ID of the item (-1 if new item)
   * @param $right Right to check : r / w / recursive
   * @param $input array of input data (used for adding item)
   *
   * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      if (empty($ID)||$ID<=0) {
         if (!count($this->fields)) {
            // Only once
            $this->getEmpty();
         }
         if (is_array($input)) {
            // Copy input field to allow getEntityID() to work
            // from entites_id field or from parent item ref
            foreach ($input as $key => $val) {
               if (isset($this->fields[$key])) {
                  $this->fields[$key] = $val;
               }
            }
         }
         return $this->canCreateItem();
      }

      // Get item if not already loaded
      if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
         // Item not found : no right
         if (!$this->getFromDB($ID)) {
            return false;
         }
      }

      switch ($right) {
         case 'r':
            return $this->canViewItem();

         case 'd':
            return $this->canDeleteItem();

         case 'w':
            return $this->canUpdateItem();
      }
      return false;
   }

   /**
    * Is the current user have right to show the current followup ?
    *
    * @return boolean
    */
   function canViewItem() {

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      if (haveRight('show_full_ticket', 1)) {
         return true;
      }
      if (!$this->is_private && haveRight('observe_ticket',1)) {
         return true;
      }
      if ($this->fields["users_id"]==$_SESSION["glpiID"]) {
         return true;
      }
      return false;
   }

   /**
    * Is the current user have right to create the current followup ?
    *
    * @return boolean
    */
   function canCreateItem() {
      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      // From canAddFollowup
      return ((haveRight("comment_ticket","1") && $this->fields["users_id"]==$_SESSION["glpiID"])
              || haveRight("comment_all_ticket","1")
              || (isset($_SESSION["glpiID"])
                  && $ticket->fields["users_id_assign"]==$_SESSION["glpiID"])
              || (isset($_SESSION["glpigroups"])
                  && in_array($ticket->fields["groups_id_assign"],$_SESSION['glpigroups'])));
   }

   /**
    * Is the current user have right to update the current followup ?
    *
    * @return boolean
    */
   function canUpdateItem() {
      if (!haveRight('update_followup',1)) {
         return false;
      }
      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      return true;
   }

   /**
    * Is the current user have right to delete the current followup ?
    *
    * @return boolean
    */
   function canDeleteItem() {
      return $this->canUpdateItem();
   }



   function post_deleteFromDB($ID) {

      $job = new Ticket();
      $job->updateRealtime($this->fields['tickets_id']);
      $job->updateDateMod($this->fields["tickets_id"]);
   }


   function prepareInputForUpdate($input) {

      $input["realtime"] = $input["hour"]+$input["minute"]/60;
      if (isset($_SESSION["glpiID"])) {
         $input["users_id"] = $_SESSION["glpiID"];
      }
      if (isset($input["plan"])) {
         $input["_plan"] = $input["plan"];
         unset($input["plan"]);
      }
      return $input;
   }


   function post_updateItem($input,$updates,$history=1) {
      global $CFG_GLPI;

      $job = new Ticket;
      $mailsend = false;

      if ($job->getFromDB($input["tickets_id"])) {
         $job->updateDateMod($input["tickets_id"]);

         if (count($updates)) {
            if ($CFG_GLPI["use_mailing"]
                && (in_array("content",$updates) || isset($input['_need_send_mail']))) {

               $user = new User;
               $user->getFromDB($_SESSION["glpiID"]);
               $mail = new Mailing("followup",$job,$user,
                                   (isset($input["is_private"]) && $input["is_private"]));
               $mail->send();
               $mailsend = true;
            }

            if (in_array("realtime",$updates)) {
               $job->updateRealTime($input["tickets_id"]);
            }
         }
      }

      if (isset($input["_plan"])) {
         $pt = new TicketPlanning();
         // Update case
         if (isset($input["_plan"]["id"])) {
            $input["_plan"]['ticketfollowups_id'] = $input["id"];
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = $mailsend;

            if (!$pt->update($input["_plan"])) {
               return false;
            }
            unset($input["_plan"]);
         // Add case
         } else {
            $input["_plan"]['ticketfollowups_id'] = $input["id"];
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = 1;

            if (!$pt->add($input["_plan"])) {
               return false;
            }
            unset($input["_plan"]);
            $input['_need_send_mail'] = true;
         }
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;

      $input["_isadmin"] = haveRight("comment_all_ticket","1");
      $input["_job"] = new Ticket;

      if ($input["_job"]->getFromDB($input["tickets_id"])) {
         // Security to add unusers_idized followups
         if (!isset($input['_do_not_check_users_id'])
             && $input["_job"]->fields["users_id"]!=$_SESSION["glpiID"]
             && !$input["_job"]->canAddFollowups()) {
            return false;
         }
      } else {
         return false;
      }

      // Manage File attached (from mailgate)
      $docadded=$input["_job"]->addFiles($input["tickets_id"]);
      if (count($docadded)>0) {
         foreach ($docadded as $name) {
            $input['content'] .= "\n".$LANG['mailing'][26]." $name";
         }
      }

      // Pass old assign From Ticket in case of assign change
      if (isset($input["_old_assign"])) {
         $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
      }

      if (!isset($input["type"])) {
         $input["type"] = "followup";
      }
      $input["_type"] = $input["type"];
      unset($input["type"]);
      $input['_close'] = 0;
      unset($input["add"]);

      if (!isset($input["users_id"])) {
         $input["users_id"] = $_SESSION["glpiID"];
      }
      if ($input["_isadmin"] && $input["_type"]!="update") {
         if (isset($input['plan'])) {
            $input['_plan'] = $input['plan'];
            unset($input['plan']);
         }
         if (isset($input["add_close"])) {
            $input['_close'] = 1;
         }
         unset($input["add_close"]);
         if (isset($input["add_reopen"])) {
            $input['_reopen'] = 1;
         }
         unset($input["add_reopen"]);
         if (!isset($input["hour"])) {
            $input["hour"] = 0;
         }
         if (!isset($input["minute"])) {
            $input["minute"] = 0;
         }
         if ($input["hour"]>0 || $input["minute"]>0) {
            $input["realtime"] = $input["hour"]+$input["minute"]/60;
         }
      }
      unset($input["minute"]);
      unset($input["hour"]);
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function post_addItem($newID,$input) {
      global $CFG_GLPI;

      $input["_job"]->updateDateMod($input["tickets_id"]);

      if (isset($input["realtime"]) && $input["realtime"]>0) {
         $input["_job"]->updateRealTime($input["tickets_id"]);
      }

      if ($input["_isadmin"] && $input["_type"]!="update") {
         if (isset($input["_plan"])) {
            $input["_plan"]['ticketfollowups_id'] = $newID;
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = 1;
            $pt = new TicketPlanning();

            if (!$pt->add($input["_plan"])) {
               return false;
            }
         }

         // TODO add + close from solution tab, not from followup
         if ($input["_close"] && $input["_type"]!="update" && $input["_type"]!="finish") {
            $updates[] = "status";
            $updates[] = "closedate";
            $input["_job"]->fields["status"] = "solved";
            $input["_job"]->fields["closedate"] = $_SESSION["glpi_currenttime"];
            $input["_job"]->updateInDB($updates);
         }
      }

      // No check on admin because my be used by mailgate
      if (isset($input["_reopen"])
          && $input["_reopen"]
          && strstr($input["_job"]->fields["status"],"old_")) {

         $updates[]="status";
         if ($input["_job"]->fields["users_id_assign"]>0
             || $input["_job"]->fields["groups_id_assign"]>0
             || $input["_job"]->fields["suppliers_id_assign"]>0) {

            $input["_job"]->fields["status"]="assign";
         } else {
            $input["_job"]->fields["status"] = "new";
         }
         $input["_job"]->updateInDB($updates);
      }

      if ($CFG_GLPI["use_mailing"]) {
         if ($input["_close"]) {
            $input["_type"] = "finish";
         }
         $user = new User;
         if (!isset($input['_auto_import']) && isset($_SESSION["glpiID"])) {
            $user->getFromDB($_SESSION["glpiID"]);
         }
         $mail = new Mailing($input["_type"],$input["_job"],$user,
                             (isset($input["is_private"]) && $input["is_private"]));
         $mail->send();
      }
   }


   // SPECIFIC FUNCTIONS

   /**
    * Get the users_id name of the followup
    * @param $link insert link ?
    *
    *@return string of the users_id name
   **/
   function getAuthorName($link=0) {
      return getUserName($this->fields["users_id"],$link);
   }

}

?>