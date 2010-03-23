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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Tracking class
class Ticket extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
   protected $forward_entity_to=array('TicketValidation');

   // Specific ones
   /// Hardware datas used by getFromDBwithData
   var $hardwaredatas = NULL;
   /// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
   var $computerfound = 0;

   static function getTypeName() {
      global $LANG;

      return $LANG['job'][38];
   }


   function canCreate() {
      return haveRight('create_ticket', 1);
   }


   function canUpdate() {
 
      return haveRight('update_ticket', 1)
             || haveRight('assign_ticket', 1)
             || haveRight('steal_ticket', 1);
   }


   function canView() {
      return true;
   }


   /**
    * Is the current user have right to show the current ticket ?
    *
    * @return boolean
    */
   function canViewItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }

      return (haveRight("show_all_ticket","1")
              || $this->fields["users_id"] === getLoginUserID()
              || (haveRight("show_group_ticket",'1')
                  && isset($_SESSION["glpigroups"])
                  && in_array($this->fields["groups_id"],$_SESSION["glpigroups"]))
              || (haveRight("show_assign_ticket",'1')
                  && ($this->fields["users_id_assign"] === getLoginUserID()
                      || (isset($_SESSION["glpigroups"])
                          && in_array($this->fields["groups_id_assign"],$_SESSION["glpigroups"]))
                      || (haveRight('assign_ticket',1) && $this->fields["status"]=='new')
                     )
                 )
              || (haveRight('validate_ticket','1') && TicketValidation::canValidate($this->fields["id"]))
             );
   }


   /**
    * Is the current user have right to create the current ticket ?
    *
    * @return boolean
    */
   function canCreateItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return haveRight('create_ticket', '1');
   }


   /**
    * Is the current user have right to update the current ticket ?
    *
    * @return boolean
    */
   function canUpdateItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return $this->canUpdate();
   }


   /**
    * Is the current user have right to delete the current ticket ?
    *
    * @return boolean
    */
   function canDeleteItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return haveRight('delete_ticket', '1');
   }


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete',$this);
      return true;
   }


   function defineTabs($options=array()) {
      global $LANG,$CFG_GLPI;

      if ($this->fields['id'] > 0) {
         if (haveRight('observe_ticket','1')) {
            $ong[1] = $LANG['job'][9];
         }
         if (haveRight('create_validation','1') ||haveRight('validate_ticket','1')) {
            $ong[7] = $LANG['validation'][0];
         }
         if (haveRight('observe_ticket','1')) {
            $ong[2] = $LANG['job'][7];
         }
         $ong[4] = $LANG['jobresolution'][1];
         $ong[3] = $LANG['job'][47];
         $ong[5] = $LANG['Menu'][27];
         $ong[6] = $LANG['title'][38];
         $ong['no_all_tab'] = true;
      } else {
         $ong[1] = $LANG['job'][13];
      }

      return $ong;
   }


   /**
    * Retrieve an item from the database with datas associated (hardwares)
    *
    *@param $ID ID of the item to get
    *@param $purecontent boolean : true : nothing change / false : convert to HTML display
    *@return true if succeed else false
   **/
   function getFromDBwithData ($ID,$purecontent) {
      global $DB,$LANG;

      if ($this->getFromDB($ID)) {
         if (!$purecontent) {
            $this->fields["content"] = nl2br(preg_replace("/\r\n\r\n/","\r\n",
                                                          $this->fields["content"]));
         }
         $this->getHardwareData();
         return true;
      }
      return false;
   }


   /**
    * Retrieve data of the hardware linked to the ticket if exists
    *
    *@return nothing : set computerfound to 1 if founded
   **/
   function getHardwareData() {

      if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
         $item = new $this->fields["itemtype"]();
         if ($item->getFromDB($this->fields["items_id"])) {
            $this->hardwaredatas=$item;
         }
      } else {
         $this->hardwaredatas=NULL;
      }
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '".$this->fields['id']."'";
      $result=$DB->query($query);

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            $querydel = "DELETE
                         FROM `glpi_ticketplannings`
                         WHERE `tickettasks_id` = '".$data['id']."'";
            $DB->query($querydel);
         }
      }
      $query1 = "DELETE
                 FROM `glpi_ticketfollowups`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);
   }


   function prepareInputForUpdate($input) {
      global $LANG,$CFG_GLPI;

      if (isset($input["date"]) && empty($input["date"])) {
         unset($input["date"]);
      }
      if (isset($input["closedate"]) && empty($input["closedate"])) {
         unset($input["closedate"]);
      }

      // Security checks
      if (!haveRight("assign_ticket","1")) {
         if (isset($input["users_id_assign"])) {
            $this->getFromDB($input['id']);
            // must own_ticket to grab a non assign ticket
            if ($this->fields['users_id_assign']==0) {
               if ((!haveRight("steal_ticket","1") && !haveRight("own_ticket","1"))
                   || ($input["users_id_assign"]!=getLoginUserID())) {
                  unset($input["users_id_assign"]);
               }

            } else {
               // Can not steal or can steal and not assign to me
               if (!haveRight("steal_ticket","1")
                   || $input["users_id_assign"] != getLoginUserID()) {
                  unset($input["users_id_assign"]);
               }
            }
         }
         if (isset($input["suppliers_id_assign"])) {
            unset($input["suppliers_id_assign"]);
         }
         if (isset($input["groups_id_assign"])) {
            unset($input["groups_id_assign"]);
         }
      }

      if (!haveRight("update_ticket","1")) {
         if ($this->canApprove()) {
            $ret["status"] = $input["status"];
         }
         // Manage assign and steal right
         if (isset($input["users_id_assign"])) {
            $ret["users_id_assign"] = $input["users_id_assign"];
         }
         if (isset($input["suppliers_id_assign"])) {
            $ret["suppliers_id_assign"] = $input["suppliers_id_assign"];
         }
         if (isset($input["groups_id_assign"])) {
            $ret["groups_id_assign"] = $input["groups_id_assign"];
         }

         // Can only update content if no followups already added
         $ret["id"]=$input["id"];
         if (isset($input["content"])) {
            $ret["content"] = $input["content"];
         }
         if (isset($input["name"])) {
            $ret["name"] = $input["name"];
         }

         $input=$ret;
      }

      // Setting a solution type means the ticket is solved
      if (isset($input["ticketsolutiontypes_id"])
          && $input["ticketsolutiontypes_id"]>0
          && $this->fields['status']!='closed') {
         $input["status"] = 'solved';
      }

      if (isset($input["items_id"])
          && $input["items_id"]>=0
          && isset($input["itemtype"])) {

         if (isset($this->fields['groups_id']) && $this->fields['groups_id']) {
            if ($input["itemtype"] && class_exists($input["itemtype"])) {
               $item = new $input["itemtype"]();
               $item->getFromDB($input["items_id"]);
               if ($item->isField('groups_id')) {
                  $input["groups_id"] = $item->getField('groups_id');
               }
            }
         }
      } else if (isset($input["itemtype"]) && empty($input["itemtype"])) {
         $input["items_id"]=0;
      } else {
         unset($input["items_id"]);
         unset($input["itemtype"]);
      }

      // Add document if needed
      $this->getFromDB($input["id"]); // entities_id field required
      $docadded = $this->addFiles($input["id"]);
      /*
      if (count($docadded)>0) {
         $input["date_mod"]=$_SESSION["glpi_currenttime"];
         if ($CFG_GLPI["add_followup_on_update_ticket"]) {
            $input['_doc_added']=$docadded;
         }
      }
      */

      if (isset($input["document"]) && $input["document"]>0) {
         $doc = new Document();
         if ($doc->getFromDB($input["document"])) {
            $docitem = new Document_Item();
            if ($docitem->add(array('documents_id' => $input["document"],
                                    'itemtype'     => $this->getType(),
                                    'items_id'     => $input["id"]))) {
               // Force date_mod of tracking
               $input["date_mod"] = $_SESSION["glpi_currenttime"];
               $input['_doc_added'][] = $doc->fields["name"];
            }
         }
         unset($input["document"]);
      }

      /*
      // Old values for add followup in change
      if ($CFG_GLPI["add_followup_on_update_ticket"]) {
         $this->getFromDB($input["id"]);
         $input["_old_assign_name"] = Ticket::getAssignName($this->fields["users_id_assign"],'User');
         $input["_old_assign"]      = $this->fields["users_id_assign"];
         $input["_old_assign_supplier_name"]  = Ticket::getAssignName($this->fields["suppliers_id_assign"],
                                                             'Supplier');
         $input["_old_groups_id_assign_name"] = Ticket::getAssignName($this->fields["groups_id_assign"],
                                                              'Group');
         $input["_old_ticketcategories_id"]  = $this->fields["ticketcategories_id"];
         $input["_old_items_id"]       = $this->fields["items_id"];
         $input["_old_itemtype"]       = $this->fields["itemtype"];
         $input["_old_users_id"]       = $this->fields["users_id"];
         $input["_old_recipient"]      = $this->fields["users_id_recipient"];
         $input["_old_group"]          = $this->fields["groups_id"];
         $input["_old_priority"]       = $this->fields["priority"];
         $input["_old_status"]         = $this->fields["status"];
         $input["_old_requesttypes_id"]= $this->fields["requesttypes_id"];
         $input["_old_cost_time"]      = $this->fields["cost_time"];
         $input["_old_cost_fixed"]     = $this->fields["cost_fixed"];
         $input["_old_cost_material"]  = $this->fields["cost_material"];
         $input["_old_date"]           = $this->fields["date"];
         $input["_old_closedate"]      = $this->fields["closedate"];
         $input["_old_soltype"]        = $this->fields["ticketsolutiontypes_id"];
      }
      */
      return $input;
   }


   function pre_updateInDB() {
      global $LANG;

      if (((in_array("users_id_assign",$this->updates) && $this->input["users_id_assign"]>0)
           || (in_array("suppliers_id_assign",$this->updates) 
               && $this->input["suppliers_id_assign"]>0)
           || (in_array("groups_id_assign",$this->updates) && $this->input["groups_id_assign"]>0))
          && $this->fields["status"]=="new") {

         if (!in_array('status', $this->updates)) {
            $this->oldvalues['status'] = $this->fields['status'];
            $this->updates[] = 'status';
         }
         $this->fields['status'] = 'assign';
      }
      if (isset($this->input["status"])) {
         if (isset($this->input["suppliers_id_assign"])
             && $this->input["suppliers_id_assign"] == 0
             && isset($this->input["groups_id_assign"])
             && $this->input["groups_id_assign"] == 0
             && isset($this->input["users_id_assign"])
             && $this->input["users_id_assign"] == 0
             && $this->input["status"]=="assign") {

            if (!in_array('status', $this->updates)) {
               $this->oldvalues['status'] = $this->fields['status'];
               $this->updates[] = 'status';
            }
            $this->fields['status'] = 'new';
         }

         if (in_array("status",$this->updates) && $this->input["status"]=="closed") {
            $this->updates[]="closedate";
            $this->oldvalues['closedate'] = $this->fields["closedate"];
            $this->fields["closedate"] = $_SESSION["glpi_currenttime"];
            // If invalid date : set open date
            if ($this->fields["closedate"] < $this->fields["date"]){
               $this->fields["closedate"] = $this->fields["date"];
            }
         }
      }

      // Status close : check dates
      if ($this->fields["status"]=="closed"
          && (in_array("date",$this->updates) || in_array("closedate",$this->updates))) {

         // Invalid dates : no change
         if ($this->fields["closedate"]<$this->fields["date"]) {
            addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);
            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
            }
            if (($key=array_search('closedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
            }
         }
      }

      // Check dates change interval due to the fact that second are not displayed in form
      if (($key=array_search('date',$this->updates)) !== false
          && (substr($this->fields["date"],0,16) == substr($this->oldvalues['date'],0,16))) {

         unset($this->updates[$key]);
      }
      if (($key=array_search('closedate',$this->updates)) !== false
          && (substr($this->fields["closedate"],0,16) == substr($this->oldvalues['closedate'],0,16))) {
         unset($this->updates[$key]);
      }

      if (in_array("users_id",$this->updates)) {
         $user = new User;
         $user->getFromDB($this->input["users_id"]);
         if (!empty($user->fields["email"])) {
            $this->updates[] = "user_email";
            $this->fields["user_email"] = $user->fields["email"];
         }
      }
      if (($key=array_search('status',$this->updates)) !== false
          && $this->oldvalues['status'] == $this->fields['status']) {
         unset($this->updates[$key]);
         unset($this->oldvalues['status']);
      }

      // Do not take into account date_mod if no update is done
      if (count($this->updates)==1 && ($key=array_search('date_mod',$this->updates)) !== false) {
         unset($this->updates[$key]);
      }
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI,$LANG;

      if (count($this->updates)) {
         // New values for add followup in change
         $change_followup_content="";
         if (isset($this->input['_doc_added']) && count($this->input['_doc_added'])>0) {
            foreach ($this->input['_doc_added'] as $name) {
               $change_followup_content .= $LANG['mailing'][26]." $name\n";
            }
         }
         // Update Ticket Tco
         if (in_array("realtime",$this->updates)
             || in_array("cost_time",$this->updates)
             || in_array("cost_fixed",$this->updates)
             || in_array("cost_material",$this->updates)) {

            if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
               $item = new $this->fields["itemtype"]();
               if ($item->getFromDB($this->fields["items_id"])) {
                  $newinput = array();
                  $newinput['id'] = $this->fields["items_id"];
                  $newinput['ticket_tco'] = self::computeTco($item);
                  $item->update($newinput);
               }
            }
         }

/*
         $global_mail_change_count=0;
         if ($CFG_GLPI["add_followup_on_update_ticket"] && count($this->updates)) {
            foreach ($this->updates as $key) {
               switch ($key) {
                  case "name" :
                     $change_followup_content .= $LANG['mailing'][45]."\n";
                     $global_mail_change_count++;
                     break;

                  case "content" :
                     $change_followup_content .= $LANG['mailing'][46]."\n";
                     break;

                  case "ticketsolutiontypes_id" :
                     $change_followup_content .= $LANG['mailing'][53]." : " .
                                                 Dropdown::getDropdownName('glpi_ticketsolutiontypes',$this->input["_old_soltype"])." -> ".
                                                 Dropdown::getDropdownName('glpi_ticketsolutiontypes',$this->fields["ticketsolutiontypes_id"])."\n";
                     $global_mail_change_count++;
                     break;

                  case "solution" :
                     if (!in_array('ticketsolutiontypes_id', $this->updates)) {
                        $change_followup_content .= $LANG['mailing'][53];
                     }
                     break;

                  case "date" :
                     $change_followup_content .= $LANG['mailing'][48]."&nbsp;: ".
                                                 $this->input["_old_date"]." -> ".$this->fields["date"]."\n";
                     $global_mail_change_count++;
                     break;

                  case "closedate" :
                     // if update status from an not closed status : no mail for change closedate
                     if (!in_array("status",$this->updates) || !$this->input["status"]!="closed") {
                        $change_followup_content .= $LANG['mailing'][49]."&nbsp;: ".
                                                    $this->input["_old_closedate"]." -> ".
                                                    $this->fields["closedate"]."\n";
                        $global_mail_change_count++;
                     }
                     break;

                  case "status" :
                     $new_status=$this->fields["status"];
                     $change_followup_content .= $LANG['mailing'][27]."&nbsp;: ".
                                                 $this->getStatus($this->input["_old_status"])." -> ".
                                                 $this->getStatus($new_status)."\n";
                     if ($new_status=="closed") {
                        $newinput["add_close"]="add_close";
                     }
                     if (in_array("closedate",$this->updates)) {
                        $global_mail_change_count++; // Manage closedate
                     }
                     $global_mail_change_count++;
                     break;

                  case "users_id" :
                     $users_id=new User;
                     $users_id->getFromDB($this->input["_old_users_id"]);
                     $old_users_id_name = $users_id->getName();
                     $users_id->getFromDB($this->fields["users_id"]);
                     $new_users_id_name = $users_id->getName();
                     $change_followup_content .= $LANG['mailing'][18]."&nbsp;: $old_users_id_name -> ".
                                                 $new_users_id_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "users_id_recipient" :
                     $recipient=new User;
                     $recipient->getFromDB($this->input["_old_recipient"]);
                     $old_recipient_name = $recipient->getName();
                     $recipient->getFromDB($this->fields["users_id_recipient"]);
                     $new_recipient_name = $recipient->getName();
                     $change_followup_content .= $LANG['mailing'][50]."&nbsp;: $old_recipient_name -> ".
                                                 $new_recipient_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "groups_id" :
                     $new_group=$this->fields["groups_id"];
                     $old_group_name = str_replace("&nbsp;",$LANG['mailing'][109],
                                                   Dropdown::getDropdownName("glpi_groups",$this->input["_old_group"]));
                     $new_group_name = str_replace("&nbsp;",$LANG['mailing'][109],
                                                   Dropdown::getDropdownName("glpi_groups",$new_group));
                     $change_followup_content .= $LANG['mailing'][20].": ".$old_group_name." -> ".
                                                 $new_group_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "priority" :
                     $new_priority = $this->fields["priority"];
                     $change_followup_content .= $LANG['mailing'][15]."&nbsp;: ".
                                                 Ticket::getPriorityName($this->input["_old_priority"])." -> ".
                                                 Ticket::getPriorityName($new_priority)."\n";
                     $global_mail_change_count++;
                     break;

                  case "ticketcategories_id" :
                     $new_ticketcategories_id = $this->fields["ticketcategories_id"];
                     $old_category_name = str_replace("&nbsp;",$LANG['mailing'][100],
                                                      Dropdown::getDropdownName("glpi_ticketcategories",
                                                                      $this->input["_old_ticketcategories_id"]));
                     $new_category_name = str_replace("&nbsp;",$LANG['mailing'][100],
                                                      Dropdown::getDropdownName("glpi_ticketcategories",
                                                                      $new_ticketcategories_id));
                     $change_followup_content .= $LANG['mailing'][14]."&nbsp;: ".
                                                 $old_category_name." -> ".$new_category_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "requesttypes_id" :
                     $old_requesttype_name = Dropdown::getDropdownName('glpi_requesttypes',
                                                             $this->input["_old_requesttypes_id"]);
                     $new_requesttype_name = Dropdown::getDropdownName('glpi_requesttypes',
                                                             $this->fields["requesttypes_id"]);
                     $change_followup_content .= $LANG['mailing'][21]."&nbsp;: ".
                                                 $old_requesttype_name." -> ".
                                                 $new_requesttype_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "items_id" :
                  case "itemtype" :
                     if (isset($already_done_computer_itemtype_update)) {
                        break;
                     } else {
                        $already_done_computer_itemtype_update=true;
                     }
                     $old_item_name = $LANG['mailing'][107];
                     if ($this->input["_old_itemtype"] && class_exists($this->input["_old_itemtype"])) {
                        $item=new $this->input["_old_itemtype"]();
                        if ($item->getFromDB($this->input["_old_items_id"])) {
                           $old_item_name = $item->getName();
                           if ($old_item_name==NOT_AVAILABLE || empty($old_item_name)) {
                              $old_item_name = $LANG['mailing'][107];
                           }
                        }
                     }
                     $new_item_name=$LANG['mailing'][107];
                     if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
                        $item = new $this->fields["itemtype"]();
                        if ($item->getFromDB($this->fields["items_id"])) {
                           $new_item_name = $item->getName();
                           if ($new_item_name==NOT_AVAILABLE || empty($new_item_name)) {
                              $new_item_name=$LANG['mailing'][107];
                           }
                        }
                     }
                     $change_followup_content .= $LANG['mailing'][17]."&nbsp;:
                                                 $old_item_name -> ".$new_item_name."\n";
                     if (in_array("items_id",$this->updates)) {
                        $global_mail_change_count++;
                     }
                     if (in_array("itemtype",$this->updates)) {
                        $global_mail_change_count++;
                     }
                     break;

                  case "users_id_assign" :
                     $new_assign_name = Ticket::getAssignName($this->fields["users_id_assign"],'User');
                     if ($this->input["_old_assign"]==0) {
                        $this->input["_old_assign_name"]=$LANG['mailing'][105];
                     }
                     $change_followup_content .= $LANG['mailing'][12]."&nbsp;: ".
                                                 $this->input["_old_assign_name"]." -> ".
                                                 $new_assign_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "suppliers_id_assign" :
                     $new_assign_supplier_name = Ticket::getAssignName($this->fields["suppliers_id_assign"],
                                                               'Supplier');
                     $change_followup_content .= $LANG['mailing'][12]."&nbsp;: ".
                                                 $this->input["_old_assign_supplier_name"]." -> ".
                                                 $new_assign_supplier_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "groups_id_assign" :
                     $new_groups_id_assign_name = Ticket::getAssignName($this->fields["groups_id_assign"],
                                                                'Group');
                     $change_followup_content .= $LANG['mailing'][12]."&nbsp;: ".
                                                 $this->input["_old_groups_id_assign_name"]." -> ".
                                                 $new_groups_id_assign_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "cost_time" :
                     $change_followup_content .= $LANG['mailing'][42]."&nbsp;: ".
                                                 formatNumber($this->input["_old_cost_time"])." -> ".
                                                 formatNumber($this->fields["cost_time"])."\n";
                     $global_mail_change_count++;
                     break;

                  case "cost_fixed" :
                     $change_followup_content .= $LANG['mailing'][43]."&nbsp;: ".
                                                 formatNumber($this->input["_old_cost_fixed"])." -> ".
                                                 formatNumber($this->fields["cost_fixed"])."\n";
                     $global_mail_change_count++;
                     break;

                  case "cost_material" :
                     $change_followup_content .= $LANG['mailing'][44]."&nbsp;: ".
                                                 formatNumber($this->input["_old_cost_material"])." -> ".
                                                 formatNumber($this->fields["cost_material"])."\n";
                     $global_mail_change_count++;
                     break;

                  case "use_email_notification" :
                     if ($this->fields["use_email_notification"]) {
                        $change_followup_content .= $LANG['mailing'][101]."\n";
                     } else {
                        $change_followup_content .= $LANG['mailing'][102]."\n";
                     }
                     $global_mail_change_count++;
                     break;
               }
            }
         }
         */
         if (!in_array("users_id_assign",$this->updates)) {
            unset($this->input["_old_assign"]);
         }
/*         $mail_send=false;

         if (!empty($change_followup_content)) { // Add followup if not empty
            $newinput=array();
            $newinput["content"]    = addslashes($change_followup_content);
            $newinput["users_id"]   = getLoginUserID();
            $newinput["is_private"] = 0;
            $newinput["hour"]       = $newinput["minute"] = 0;
            $newinput["tickets_id"] = $this->fields["id"];
            $newinput["type"]       = "update";
            $newinput["_do_not_check_users_id"] = true;
            // pass _old_assign if assig changed
            if (isset($this->input["_old_assign"])) {
               $newinput["_old_assign"] = $this->input["_old_assign"];
            }

            if (isset($this->input["status"])
                && in_array("status",$this->updates)
                && $this->input["status"]=="solved") {

               $newinput["type"]="finish";
            }
            $fup=new TicketFollowup();
            $fup->add($newinput);
            $mail_send=true;
         }
*/
         // Clean content to mail
         $this->fields["content"] = stripslashes($this->fields["content"]);
/*
         if (!$mail_send
             && count($this->updates)>$global_mail_change_count
             && $CFG_GLPI["use_mailing"]) {
*/
         if (count($this->updates)>0 && $CFG_GLPI["use_mailing"]) {
            $mailtype = "update";

            if (isset($this->input["status"])
                && $this->input["status"]
                && in_array("status",$this->updates)
                && $this->input["status"]=="solved") {

               $mailtype = "solved";
            }

            //TODO : manage assignation

            if (isset($this->input["_old_assign"])) {
               $this->fields["_old_assign"] = $this->input["_old_assign"];
            }
            NotificationEvent::raiseEvent($mailtype, $this);
            //$mail = new Mailing($mailtype,$this,$user);
            //$mail->send();
         }
      }
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI,$LANG;

      // Check mandatory
      $mandatory_ok=true;

      // Do not check mandatory on auto import (mailgates)
      if (!isset($input['_auto_import'])) {
         $_SESSION["helpdeskSaved"] = $input;

         if (!isset($input["urgency"])) {
            addMessageAfterRedirect($LANG['tracking'][4], false, ERROR);
            $mandatory_ok = false;
         }
         if ($CFG_GLPI["is_ticket_content_mandatory"]
             && (!isset($input['content']) || empty($input['content']))) {

            addMessageAfterRedirect($LANG['tracking'][8], false, ERROR);
            $mandatory_ok = false;
         }
         if ($CFG_GLPI["is_ticket_title_mandatory"]
             && (!isset($input['name']) || empty($input['name']))) {

            addMessageAfterRedirect($LANG['help'][40], false, ERROR);
            $mandatory_ok = false;
         }
         if ($CFG_GLPI["is_ticket_category_mandatory"]
             && (!isset($input['ticketcategories_id']) || empty($input['ticketcategories_id']))) {

            addMessageAfterRedirect($LANG['help'][41], false, ERROR);
            $mandatory_ok = false;
         }
         if (isset($input['use_email_notification']) && $input['use_email_notification']
             && (!isset($input['user_email']) || empty($input['user_email']))) {

            addMessageAfterRedirect($LANG['help'][16], false, ERROR);
            $mandatory_ok = false;
         }

         if (!$mandatory_ok) {
            return false;
         }
      }
      if (!isset($input["urgency"])
          || !($CFG_GLPI['urgency_mask']&(1<<$input["urgency"]))) {
         $input["urgency"] = 3;
      }
      if (!isset($input["impact"])
          || !($CFG_GLPI['impact_mask']&(1<<$input["impact"]))) {
         $input["impact"] = 3;
      }
      if (!isset($input["priority"])) {
         $input["priority"] = $this->computePriority($input["urgency"], $input["impact"]);
      }

      unset($_SESSION["helpdeskSaved"]);

      // Manage helpdesk.html submission type
      unset($input["type"]);

      // No Auto set Import for external source
      if (!isset($input['_auto_import'])) {
         if (!isset($input["users_id"])) {
            if ($uid=getLoginUserID()) {
               $input["users_id"] = $uid;
            }
         }
      }

      // No Auto set Import for external source
      if (($uid=getLoginUserID()) && !isset($input['_auto_import'])) {
         $input["users_id_recipient"] = $uid;
      } else if ($input["users_id"]) {
         $input["users_id_recipient"] = $input["users_id"];
      }
      if (!isset($input["requesttypes_id"])) {
         $input["requesttypes_id"] = RequestType::getDefault('helpdesk');
      }
      if (!isset($input["status"])) {
         $input["status"]="new";
      }
      if (!isset($input["date"]) || empty($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }

      // Set default dropdown
      $dropdown_fields = array('entities_id','groups_id', 'groups_id_assign', 'items_id',
                               'users_id', 'users_id_assign', 'suppliers_id_assign',
                               'ticketcategories_id');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field] = 0;
         }
      }
      if (!isset($input['itemtype']) || !($input['items_id']>0)) {
         $input['itemtype'] = '';
      }

      $item = NULL;
      if ($input["items_id"]>0 && !empty($input["itemtype"])) {
         if (class_exists($input["itemtype"])) {
            $item = new $input["itemtype"]();
            if (!$item->getFromDB($input["items_id"])) {
               $item = NULL;
            }
         }
      }


      // Auto group define from item
      if ($item != NULL) {
         if ($item->isField('groups_id')) {
            $input["groups_id"] = $item->getField('groups_id');
         }
      }

      if ($CFG_GLPI["use_auto_assign_to_tech"]) {

         // Auto assign tech from item
         if ($input["users_id_assign"]==0 && $item!=NULL) {

            if ($item->isField('users_id_tech')) {
               $input["users_id_assign"] = $item->getField('users_id_tech');
               if ($input["users_id_assign"]>0) {
                  $input["status"] = "assign";
               }
            }
         }

         // Auto assign tech/group from Category
         if ($input['ticketcategories_id']>0
             && (!$input['users_id_assign'] || !$input['groups_id_assign'])) {

            $cat = new TicketCategory();
            $cat->getFromDB($input['ticketcategories_id']);
            if (!$input['users_id_assign'] && $cat->isField('users_id')) {
               $input['users_id_assign'] = $cat->getField('users_id');
            }
            if (!$input['groups_id_assign'] && $cat->isField('groups_id')) {
               $input['groups_id_assign'] = $cat->getField('groups_id');
            }
         }
      }

      // Process Business Rules
      $rules = new RuleTicketCollection($input['entities_id']);

      // Set unset variables with are needed
      $user = new User();
      if ($user->getFromDB($input["users_id"])) {
         $input['users_locations'] = $user->fields['locations_id'];
      }


      $input = $rules->processAllRules($input,$input,array('recursive'=>true));

      if (isset($input["use_email_notification"])
          && $input["use_email_notification"]
          && empty($input["user_email"])) {

         if ($user->getFromDB($input["users_id"])) {
            $input["user_email"] = $user->fields["email"];
         }
      }

      if (($input["users_id_assign"]>0
           || $input["groups_id_assign"]>0
           || $input["suppliers_id_assign"]>0)
          && $input["status"]=="new") {

         $input["status"] = "assign";
      }

      if (isset($input["hour"]) && isset($input["minute"])) {
         $input["realtime"] = $input["hour"]+$input["minute"]/60;
         $input["_hour"]    = $input["hour"];
         $input["_minute"]  = $input["minute"];
         unset($input["hour"]);
         unset($input["minute"]);
      }

      if (isset($input["status"]) && $input["status"]=="closed") {
         if (isset($input["date"])) {
            $input["closedate"] = $input["date"];
         } else {
            $input["closedate"] = $_SESSION["glpi_currenttime"];
         }
      }

      // No name set name
      if (empty($input["name"])) {
         $input["name"] = preg_replace('/\r\n/',' ',$input['content']);
         $input["name"] = preg_replace('/\n/',' ',$input['name']);
         $input["name"] = utf8_substr($input['name'],0,70);
      }
      return $input;
   }


   function post_addItem() {
      global $LANG,$CFG_GLPI;

      // Add document if needed
      $this->addFiles($this->fields['id']);

      // Log this event
      Event::log($this->fields['id'], "ticket", 4, "tracking",
                  getUserName($this->input["users_id"])." ".$LANG['log'][20]);

      $already_mail = false;
      if (((isset($this->input["_followup"])
            && is_array($this->input["_followup"])
            && strlen($this->input["_followup"]['content']) > 0
           )
           || isset($this->input["plan"])
          )
          || (isset($this->input["_hour"])
              && isset($this->input["_minute"])
              && isset($this->input["realtime"])
              && $this->input["realtime"]>0)) {

         $fup = new TicketFollowup();
         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "finish";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->input['id']);
         if (isset($this->input["_hour"])) {
            $toadd["hour"] = $this->input["_hour"];
         }
         if (isset($this->input["_minute"])) {
            $toadd["minute"] = $this->input["_minute"];
         }
         if (isset($this->input["_followup"]['content'])
             && strlen($this->input["_followup"]['content']) > 0) {
            $toadd["content"] = $this->input["_followup"]['content'];
         }
         if (isset($this->input["_followup"]['is_private'])) {
            $toadd["is_private"] = $this->input["_followup"]['is_private'];
         }
         if (isset($this->input["plan"])) {
            $toadd["plan"] = $this->input["plan"];
         }
         $fup->add($toadd);
         $already_mail = true;
      }

      // Processing Email

      if ($CFG_GLPI["use_mailing"] && !$already_mail) {
         $user = new User();
         $user->getFromDB($this->input["users_id"]);
         // Clean reload of the ticket
         $this->getFromDB($this->fields['id']);

         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "finish";
         }

         NotificationEvent::raiseEvent($type,$this);
      }
   }


   // SPECIFIC FUNCTIONS
   /**
    * Number of followups of the ticket
    *
    *@param $with_private boolean : true : all ticket / false : only public ones
    *@return followup count
   **/
   function numberOfFollowups($with_private=1) {
      global $DB;

      $RESTRICT = "";
      if ($with_private!=1) {
         $RESTRICT = " AND `is_private` = '0'";
      }
      // Set number of followups
      $query = "SELECT count(*)
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result,0,0);
   }


   /**
    * Update realtime of the ticket based on realtime of the followups and tasks
    *
    *@param $ID ID of the ticket
    *@return boolean : success
   **/
   function updateRealTime($ID) {
      global $DB;

      $tot = 0;

      $query = "SELECT SUM(`realtime`)
                FROM `glpi_tickettasks`
                WHERE `tickets_id` = '$ID'";

      if ($result = $DB->query($query)) {
         $sum = $DB->result($result,0,0);
         if (!is_null($sum)) {
            $tot += $sum;
         }
      }
      $query2 = "UPDATE `".$this->getTable()."`
                 SET `realtime` = '$tot'
                 WHERE `id` = '$ID'";

      return $DB->query($query2);
   }


   /**
    * Update date mod of the ticket
    *
    *@param $ID ID of the ticket
   **/
   function updateDateMod($ID) {
      global $DB;

      $query = "UPDATE `".$this->getTable()."`
                SET `date_mod` = '".$_SESSION["glpi_currenttime"]."'
                WHERE `id` = '$ID'";
      $DB->query($query);
   }


   /**
    * Get text describing Followups
    *
    * @param $format text or html
    * @param $sendprivate true if both public and private followups have to be printed in the email
    */
   function textFollowups($format="text", $sendprivate=false) {
      global $DB,$LANG;

      // get the last followup for this job and give its content as
      if (isset($this->fields["id"])) {
         $query = "SELECT *
                   FROM `glpi_ticketfollowups`
                   WHERE `tickets_id` = '".$this->fields["id"]."' ".
                         ($sendprivate?"":" AND `is_private` = '0' ")."
                   ORDER by `date` DESC";

         $result = $DB->query($query);
         $nbfollow = $DB->numrows($result);

         $fup = new TicketFollowup();

         if ($format == "html") {
            $message = "<div class='description b'>".$LANG['mailing'][4]."&nbsp;: $nbfollow<br></div>\n";

            if ($nbfollow > 0) {
               while ($data = $DB->fetch_array($result)) {
                  $fup->getFromDB($data['id']);
                  $message .= "<strong>[ ".convDateTime($fup->fields["date"])." ] ".
                               ($fup->fields["is_private"]?"<i>".$LANG['common'][77]."</i>":"").
                               "</strong>\n";
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['job'][4]."&nbsp;:</span> ".
                               $fup->getAuthorName()."\n";
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['mailing'][3]."</span>&nbsp;:<br>".
                               str_replace("\n","<br>",$fup->fields["content"])."\n";
                  if ($fup->fields["realtime"]>0) {
                     $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                                  "text-decoration:underline; '>".$LANG['mailing'][104]."&nbsp;:".
                                  ".</span> ".Ticket::getRealtime($fup->fields["realtime"])."\n";
                  }
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['mailing'][25]."</span> ";

                  // Use tasks instead of followups
                  /*
                  $query2 = "SELECT *
                             FROM `glpi_ticketplannings`
                             WHERE `tickettasks_id` = '".$data['id']."'";
                  $result2=$DB->query($query2);

                  if ($DB->numrows($result2)==0) {
                     $message .= $LANG['job'][32]."\n";
                  } else {
                     $data2 = $DB->fetch_array($result2);
                     $message .= convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
                  }
                  */
                  $message .= $LANG['mailing'][0]."\n";
               }
            }
         } else { // text format
            $message = $LANG['mailing'][1]."\n".$LANG['mailing'][4]." : $nbfollow\n".
                       $LANG['mailing'][1]."\n";

            if ($nbfollow > 0) {
               while ($data=$DB->fetch_array($result)) {
                  $fup->getFromDB($data['id']);
                  $message .= "[ ".convDateTime($fup->fields["date"])." ]".
                               ($fup->fields["is_private"]?"\t".$LANG['common'][77] :"")."\n";
                  $message .= $LANG['job'][4]."&nbsp;: ".$fup->getAuthorName()."\n";
                  $message .= $LANG['mailing'][3]."&nbsp;:\n".$fup->fields["content"]."\n";
                  if ($fup->fields["realtime"]>0) {
                     $message .= $LANG['mailing'][104]."&nbsp;: ".
                                 Ticket::getRealtime($fup->fields["realtime"])."\n";
                  }
                  $message .= $LANG['mailing'][25]." ";

                  // Use tasks instead of followups
                  /*
                  $query2 = "SELECT *
                             FROM `glpi_ticketplannings`
                             WHERE `ticketfollowups_id` = '".$data['id']."'";
                  $result2=$DB->query($query2);

                  if ($DB->numrows($result2)==0) {
                     $message .= $LANG['job'][32]."\n";
                  } else {
                     $data2 = $DB->fetch_array($result2);
                     $message .= convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
                  }
                  */
                  $message .= $LANG['mailing'][0]."\n";
               }
            }
         }
         return $message;
      }
      return "";
   }


   /**
    * Get users_id name
    *
    * @param $link boolean with link ?
    * @return string users_id name
    */
   function getAuthorName($link=0) {
      return getUserName($this->fields["users_id"],$link);
   }


   /**
    * Is the current user have right to add followups to the current ticket ?
    *
    * @return boolean
    */
   function canAddFollowups() {

      return ((haveRight("add_followups","1") && $this->fields["users_id"]===getLoginUserID())
              || haveRight("global_add_followups","1")
              || ($this->fields["users_id_assign"]===getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && in_array($this->fields["groups_id_assign"],$_SESSION['glpigroups'])));
   }


   /**
    * Is the current user have right to show the current ticket ?
    *
    * @return boolean
   function canUserView() {

      return (haveRight("show_all_ticket","1")
              || ($this->fields["users_id"]===getLoginUserID())
              || (haveRight("show_group_ticket",'1')
                  && isset($_SESSION["glpigroups"])
                  && in_array($this->fields["groups_id"],$_SESSION["glpigroups"]))
              || (haveRight("show_assign_ticket",'1')
                  && ($this->fields["users_id_assign"]===getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && in_array($this->fields["groups_id_assign"],$_SESSION["glpigroups"])))));
   }
    */


   /**
    * add files (from $_FILES) to a ticket
    * create document if needed
    * create link from document to ticket
    *
    * @param $id of the ticket
    *
    * @return array of doc added name
    */
   function addFiles ($id) {
      global $LANG, $CFG_GLPI;

      if (!isset($_FILES)) {
         return array();
      }
      $docadded = array();
      $doc = new Document();
      $docitem = new Document_Item();

      // add Document if exists
      if (isset($_FILES['multiple']) ) {
         unset($_FILES['multiple']);
         $TMPFILE = $_FILES;
      } else {
         $TMPFILE = array( $_FILES );
      }
      foreach ($TMPFILE as $_FILES) {
         if (isset($_FILES['filename'])
             && count($_FILES['filename'])>0
             && $_FILES['filename']["size"]>0) {
            // Check for duplicate
            if ($doc->getFromDBbyContent($this->fields["entities_id"],
                                         $_FILES['filename']['tmp_name'])) {
               $docID = $doc->fields["id"];
            } else {
               $input2 = array();
               $input2["name"]                    = addslashes($LANG['tracking'][24]." $id");
               $input2["tickets_id"]              = $id;
               $input2["entities_id"]             = $this->fields["entities_id"];
               $input2["documentcategories_id"]   = $CFG_GLPI["documentcategories_id_forticket"];
               $input2["_only_if_upload_succeed"] = 1;
               $input2["entities_id"]             = $this->fields["entities_id"];
               $docID = $doc->add($input2);
            }
            if ($docID>0) {
               if ($docitem->add(array('documents_id' => $docID,
                                       'itemtype'     => $this->getType(),
                                       'items_id'     => $id))) {
                  $docadded[] = stripslashes($doc->fields["name"] . " - " . $doc->fields["filename"]);
               }
            }

         } else if (!empty($_FILES['filename']['name'])
                    && isset($_FILES['filename']['error'])
                    && $_FILES['filename']['error']) {
            addMessageAfterRedirect($LANG['document'][46], false, ERROR);
         }
      }
      unset ($_FILES);
      return $docadded;
   }


   /** Get default values to search engine to override
   **/
   static function getDefaultSearchRequest() {

      $search = array('field'      => array(0 => 12),
                      'searchtype' => array(0 => 'equals'),
                      'contains'   => array(0 => 'notclosed'),
                      'sort'       => 19,
                      'order'      => 'DESC');
       
      if (haveRight('show_all_ticket',1)) {
         $search['contains'] = array(0 => 'notold');
      }
     return $search;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'content';
      $tab[21]['linkfield']     = '';
      $tab[21]['name']          = $LANG['joblist'][6];

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[12]['table']     = $this->getTable();
      $tab[12]['field']     = 'status';
      $tab[12]['linkfield'] = 'status';
      $tab[12]['name']      = $LANG['joblist'][0];
      $tab[12]['searchtype']= 'equals';

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'urgency';
      $tab[10]['linkfield'] = 'urgency';
      $tab[10]['name']      = $LANG['joblist'][29];
      $tab[10]['searchtype']= 'equals';

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'impact';
      $tab[11]['linkfield'] = 'impact';
      $tab[11]['name']      = $LANG['joblist'][30];
      $tab[11]['searchtype']= 'equals';

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'priority';
      $tab[3]['linkfield'] = 'priority';
      $tab[3]['name']      = $LANG['joblist'][2];
      $tab[3]['searchtype']= 'equals';

      $tab[15]['table']     = $this->getTable();
      $tab[15]['field']     = 'date';
      $tab[15]['linkfield'] = '';
      $tab[15]['name']      = $LANG['reports'][60];
      $tab[15]['datatype'] = 'datetime';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'closedate';
      $tab[16]['linkfield'] = '';
      $tab[16]['name']      = $LANG['reports'][61];
      $tab[16]['datatype'] = 'datetime';

      $tab[19]['table']     = $this->getTable();
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype'] = 'datetime';

      $tab[7]['table']     = 'glpi_ticketcategories';
      $tab[7]['field']     = 'name';
      $tab[7]['linkfield'] = 'ticketcategories_id';
      $tab[7]['name']      = $LANG['common'][36];

      $tab[9]['table']     = 'glpi_requesttypes';
      $tab[9]['field']     = 'name';
      $tab[9]['linkfield'] = 'requesttypes_id';
      $tab[9]['name']      = $LANG['job'][44];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab['validation'] = $LANG['validation'][0];

      $tab[52]['table']     = 'glpi_tickets';
      $tab[52]['field']     = 'global_validation';
      $tab[52]['linkfield'] = 'global_validation';
      $tab[52]['name']      = $LANG['validation'][0];
      $tab[52]['searchtype']= 'equals';

      $tab[53]['table']     = 'glpi_ticketvalidations';
      $tab[53]['field']     = 'comment_submission';
      $tab[53]['linkfield'] = '';
      $tab[53]['name']      = $LANG['validation'][0]." - ".$LANG['validation'][5];
      $tab[53]['datatype']  = 'text';
      $tab[53]['forcegroupby'] = true;

      $tab[54]['table']     = 'glpi_ticketvalidations';
      $tab[54]['field']     = 'comment_validation';
      $tab[54]['linkfield'] = '';
      $tab[54]['name']      = $LANG['validation'][0]." - ".$LANG['validation'][6];
      $tab[54]['datatype']  = 'text';
      $tab[54]['forcegroupby'] = true;

      $tab[55]['table']     = 'glpi_ticketvalidations';
      $tab[55]['field']     = 'status';
      $tab[55]['linkfield'] = '';
      $tab[55]['name']      = $LANG['validation'][0]." - ".$LANG['joblist'][0];
      $tab[55]['searchtype']= 'equals';
      $tab[55]['forcegroupby'] = true;

      $tab[56]['table']     = 'glpi_ticketvalidations';
      $tab[56]['field']     = 'submission_date';
      $tab[56]['linkfield'] = '';
      $tab[56]['name']      = $LANG['validation'][0]." - ".$LANG['validation'][3];
      $tab[56]['datatype']  = 'datetime';
      $tab[56]['forcegroupby'] = true;

      $tab[57]['table']     = 'glpi_ticketvalidations';
      $tab[57]['field']     = 'validation_date';
      $tab[57]['linkfield'] = '';
      $tab[57]['name']      = $LANG['validation'][0]." - ".$LANG['validation'][4];
      $tab[57]['datatype']  = 'datetime';
      $tab[57]['forcegroupby'] = true;

      $tab[58]['table']     = 'glpi_users_validation';
      $tab[58]['field']     = 'name';
      $tab[58]['linkfield'] = 'users_id';
      $tab[58]['name']      = $LANG['validation'][0]." - ".$LANG['job'][4];
      $tab[58]['datatype']      = 'itemlink';
      $tab[58]['itemlink_type'] = 'User';
      $tab[58]['forcegroupby'] = true;

      $tab[59]['table']     = 'glpi_users_validation';
      $tab[59]['field']     = 'name';
      $tab[59]['linkfield'] = 'users_id_validate';
      $tab[59]['name']      = $LANG['validation'][0]." - ".$LANG['validation'][21];
      $tab[59]['datatype']      = 'itemlink';
      $tab[59]['itemlink_type'] = 'User';
      $tab[59]['forcegroupby'] = true;

      if (haveRight("show_all_ticket","1") || haveRight("show_assign_ticket",'1')) {

         $tab['requester'] = $LANG['job'][4];

         $tab[4]['table']     = 'glpi_users';
         $tab[4]['field']     = 'name';
         $tab[4]['linkfield'] = 'users_id';
         $tab[4]['name']      = $LANG['job'][4];

         $tab[71]['table']     = 'glpi_groups';
         $tab[71]['field']     = 'name';
         $tab[71]['linkfield'] = 'groups_id';
         $tab[71]['name']      = $LANG['common'][35];

         $tab['assign'] = $LANG['job'][5];

         $tab[5]['table']     = 'glpi_users';
         $tab[5]['field']     = 'name';
         $tab[5]['linkfield'] = 'users_id_assign';
         $tab[5]['name']      = $LANG['job'][5]." - ".$LANG['job'][6];

         $tab[6]['table']     = 'glpi_suppliers';
         $tab[6]['field']     = 'name';
         $tab[6]['linkfield'] = 'suppliers_id_assign';
         $tab[6]['name']      = $LANG['job'][5]." - ".$LANG['financial'][26];

         $tab[8]['table']     = 'glpi_groups';
         $tab[8]['field']     = 'name';
         $tab[8]['linkfield'] = 'groups_id_assign';
         $tab[8]['name']      = $LANG['job'][5]." - ".$LANG['common'][35];

         $tab[22]['table']     = 'glpi_users';
         $tab[22]['field']     = 'name';
         $tab[22]['linkfield'] = 'users_id_recipient';
         $tab[22]['name']      = $LANG['job'][3];

         $tab['followup'] = $LANG['job'][9];

         $tab[25]['table']     = 'glpi_ticketfollowups';
         $tab[25]['field']     = 'content';
         $tab[25]['linkfield'] = '';
         $tab[25]['name']      = $LANG['job'][9];
         $tab[25]['forcegroupby'] = true;
         $tab[25]['splititems'] = true;

         $tab[26]['table']     = 'glpi_tickettasks';
         $tab[26]['field']     = 'content';
         $tab[26]['linkfield'] = '';
         $tab[26]['name']      = $LANG['job'][7];
         $tab[26]['forcegroupby'] = true;
         $tab[26]['splititems'] = true;

         $tab['solution'] = $LANG['jobresolution'][1];

         $tab[23]['table']     = 'glpi_ticketsolutiontypes';
         $tab[23]['field']     = 'name';
         $tab[23]['linkfield'] = 'ticketsolutiontypes_id';
         $tab[23]['name']      = $LANG['job'][48];

         $tab[24]['table']     = $this->getTable();
         $tab[24]['field']     = 'solution';
         $tab[24]['linkfield'] = '';
         $tab[24]['name']      = $LANG['jobresolution'][1];

         $tab['cost'] = $LANG['financial'][5];

         $tab[42]['table']     = $this->getTable();
         $tab[42]['field']     = 'cost_time';
         $tab[42]['linkfield'] = 'cost_time';
         $tab[42]['name']      = $LANG['job'][40];
         $tab[42]['datatype'] = 'decimal';

         $tab[43]['table']     = $this->getTable();
         $tab[43]['field']     = 'cost_fixed';
         $tab[43]['linkfield'] = 'cost_fixed';
         $tab[43]['name']      = $LANG['job'][41];
         $tab[43]['datatype'] = 'decimal';

         $tab[44]['table']     = $this->getTable();
         $tab[44]['field']     = 'cost_material';
         $tab[44]['linkfield'] = 'cost_material';
         $tab[44]['name']      = $LANG['job'][42];
         $tab[44]['datatype'] = 'decimal';


         $tab['notification'] = $LANG['setup'][704];

         $tab[35]['table']     = $this->getTable();
         $tab[35]['field']     = 'use_email_notification';
         $tab[35]['linkfield'] = 'use_email_notification';
         $tab[35]['name']      = $LANG['job'][19];
         $tab[35]['datatype'] = 'bool';

         $tab[34]['table']     = $this->getTable();
         $tab[34]['field']     = 'user_email';
         $tab[34]['linkfield'] = 'user_email';
         $tab[34]['name']      = $LANG['joblist'][27];
         $tab[34]['datatype'] = 'email';

      }

      return $tab;
   }

   /**
    * Compute Priority
    *
    * @param $urgency integer from 1 to 5
    * @param $impact integer from 1 to 5
    *
    * @return integer from 1 to 5 (priority)
    */
   static function computePriority ($urgency, $impact) {
      global $CFG_GLPI;

      if (isset($CFG_GLPI['priority_matrix'][$urgency][$impact])) {
         return $CFG_GLPI['priority_matrix'][$urgency][$impact];
      }
      // Failback to trivial
      return round(($urgency+$impact)/2);
   }

   /**
   * Dropdown of ticket priority
   *
   * @param $name select name
   * @param $value default value
   * @param $complete see also at least selection (major included)
   * @param $major display major priority
   *
   * @return string id of the select
   */
   static function dropdownPriority($name,$value=0,$complete=false,$major=false) {
      global $LANG;

      $id = "select_$name".mt_rand();
      echo "<select id='$id' name='$name'>";
      if ($complete) {
         echo "<option value='0' ".($value==0?" selected ":"").">".$LANG['common'][66]."</option>";
         echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][3]."</option>";
         echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][4]."</option>";
         echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][5]."</option>";
         echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][6]."</option>";
         echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][7]."</option>";
      }
      if ($complete || $major) {
         echo "<option value='6' ".($value==6?" selected ":"").">".$LANG['help'][2]."</option>";
      }
      echo "<option value='5' ".($value==5?" selected ":"").">".$LANG['help'][3]."</option>";
      echo "<option value='4' ".($value==4?" selected ":"").">".$LANG['help'][4]."</option>";
      echo "<option value='3' ".($value==3?" selected ":"").">".$LANG['help'][5]."</option>";
      echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['help'][6]."</option>";
      echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['help'][7]."</option>";

      echo "</select>";

      return $id;
   }

   /**
    * Get ticket priority Name
    *
    * @param $value status ID
    */
   static function getPriorityName($value) {
      global $LANG;

      switch ($value) {
         case 6 :
            return $LANG['help'][2];

         case 5 :
            return $LANG['help'][3];

         case 4 :
            return $LANG['help'][4];

         case 3 :
            return $LANG['help'][5];

         case 2 :
            return $LANG['help'][6];

         case 1 :
            return $LANG['help'][7];
      }
   }

   /**
    * Dropdown of ticket Urgency
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection
    *
    * @return string id of the select
    */
   static function dropdownUrgency($name, $value=0, $complete=false) {
      global $LANG, $CFG_GLPI;

      $id = "select_$name".mt_rand();
      echo "<select id='$id' name='$name'>";

      if ($complete) {
         echo "<option value='0' ".($value==0?" selected ":"").">".$LANG['common'][66]."</option>";
         echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][42]."</option>";
         echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][43]."</option>";
         echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][44]."</option>";
         echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][45]."</option>";
         echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][46]."</option>";
      }


      if ($complete || ($CFG_GLPI['urgency_mask'] & (1<<5))) {
         echo "<option value='5' ".($value==5?" selected ":"").">".$LANG['help'][42]."</option>";
      }
      if ($complete || ($CFG_GLPI['urgency_mask'] & (1<<4))) {
         echo "<option value='4' ".($value==4?" selected ":"").">".$LANG['help'][43]."</option>";
      }
      echo "<option value='3' ".($value==3?" selected ":"").">".$LANG['help'][44]."</option>";
      if ($complete || ($CFG_GLPI['urgency_mask'] & (1<<2))) {
         echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['help'][45]."</option>";
      }
      if ($complete || ($CFG_GLPI['urgency_mask'] & (1<<1))) {
         echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['help'][46]."</option>";
      }
      echo "</select>";

      return $id;
   }

   /**
    * Get ticket Urgence Name
    *
    * @param $value status ID
    */
   static function getUrgencyName($value) {
      global $LANG;

      switch ($value) {
         case 5 :
            return $LANG['help'][42];

         case 4 :
            return $LANG['help'][43];

         case 3 :
            return $LANG['help'][44];

         case 2 :
            return $LANG['help'][45];

         case 1 :
            return $LANG['help'][46];
      }
   }

   /**
   * Dropdown of ticket Impact
   *
   * @param $name select name
   * @param $value default value
   * @param $complete see also at least selection (major included)
   *
   * @return string id of the select
   */
   static function dropdownImpact($name, $value=0, $complete=false) {
      global $LANG, $CFG_GLPI;

      $id = "select_$name".mt_rand();
      echo "<select id='$id' name='$name'>";


      if ($complete) {
         echo "<option value='0' ".($value==0?" selected ":"").">".$LANG['common'][66]."</option>";
         echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][47]."</option>";
         echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][48]."</option>";
         echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][49]."</option>";
         echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][50]."</option>";
         echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][51]."</option>";
      }


      if ($complete || ($CFG_GLPI['impact_mask'] & (1<<5))) {
         echo "<option value='5' ".($value==5?" selected ":"").">".$LANG['help'][47]."</option>";
      }
      if ($complete || ($CFG_GLPI['impact_mask'] & (1<<4))) {
         echo "<option value='4' ".($value==4?" selected ":"").">".$LANG['help'][48]."</option>";
      }
      echo "<option value='3' ".($value==3?" selected ":"").">".$LANG['help'][49]."</option>";
      if ($complete || ($CFG_GLPI['impact_mask'] & (1<<2))) {
         echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['help'][50]."</option>";
      }
      if ($complete || ($CFG_GLPI['impact_mask'] & (1<<1))) {
         echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['help'][51]."</option>";
      }
      echo "</select>";

      return $id;
   }

   /**
    * Get ticket Impact Name
    *
    * @param $value status ID
    */
   static function getImpactName($value) {
      global $LANG;

      switch ($value) {
         case 5 :
            return $LANG['help'][47];

         case 4 :
            return $LANG['help'][48];

         case 3 :
            return $LANG['help'][49];

         case 2 :
            return $LANG['help'][50];

         case 1 :
            return $LANG['help'][51];
      }
   }

   /**
    * get the Ticket status list
    *
    * @param $withmetaforsearch boolean
    * @return an array
    */
   static function getAllStatusArray($withmetaforsearch=false) {
      global $LANG;

      $tab = array(
         'new'          => $LANG['joblist'][9],
         'assign'       => $LANG['joblist'][18],
         'plan'         => $LANG['joblist'][19],
         'waiting'      => $LANG['joblist'][26],
         'solved'       => $LANG['joblist'][32],
         'closed'       => $LANG['joblist'][33]);

      if ($withmetaforsearch) {
         $tab['notold']    = $LANG['joblist'][34];
         $tab['notclosed'] = $LANG['joblist'][35];
         $tab['process']   = $LANG['joblist'][21];
         $tab['old']       = $LANG['joblist'][32]." + ".$LANG['joblist'][33];
         $tab['all']       = $LANG['common'][66];
      }
      return $tab;
   }

   /**
    * get the Ticket status allowed for a current status
    *
    * @param $current status
    * @return an array
    */
   static function getAllowedStatusArray($current) {
      global $LANG;

      $tab = self::getAllStatusArray();
      if (!isset($current)) {
         $current = 'new';
      }
      foreach ($tab as $status => $label) {
         if ($status != $current
             && isset($_SESSION['glpiactiveprofile']['helpdesk_status'][$current][$status])
             && !$_SESSION['glpiactiveprofile']['helpdesk_status'][$current][$status]) {
            unset($tab[$status]);
         }
      }
      return $tab;
   }


   /**
    * Dropdown of ticket status
    *
    * @param $name select name
    * @param $value default value
    * @param $option list proposed 0:normal, 1:search, 2:allowed
    *
    * @return nothing (display)
    */
   static function dropdownStatus($name, $value='new', $option=0) {

      if ($option == 2) {
         $tab = self::getAllowedStatusArray($value);
      } else if ($option == 1) {
         $tab = self::getAllStatusArray(true);
      } else {
         $tab = self::getAllStatusArray(false);
      }
      echo "<select name='$name'>";
      foreach ($tab as $key => $val) {
         echo "<option value='$key' ".($value==$key?" selected ":"").">$val</option>";
      }
      echo "</select>";
   }

   /**
    * Get ticket status Name
    *
    * @param $value status ID
    */
   static function getStatus($value) {
      global $LANG;

      $tab = self::getAllStatusArray(true);
      return (isset($tab[$value]) ? $tab[$value] : '');
   }

   /**
    * Show the current ticket sumnary --- NOT USED
   function showSummary() {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("observe_ticket", "1") && !haveRight("show_full_ticket", "1")) {
         return false;
      }

      $tID = $this->getField('id');

      // Display existing Followups
      $showprivate = haveRight("show_full_ticket", "1");
      $caneditall = haveRight("update_followups", "1");

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                                     OR `users_id` ='" . getLoginUserID() . "') ";
      }

      // TODO keep this for a union with followup + task + histo + ...
      $query = "(SELECT 'TicketFollowup' as itemtype, `id`, `date`
                 FROM `glpi_ticketfollowups`
                 WHERE `tickets_id` = '$tID'
                        $RESTRICT)
                UNION
                (SELECT 'TicketTask' as itemtype, `id`, `date`
                 FROM `glpi_tickettasks`
                 WHERE `tickets_id` = '$tID'
                        $RESTRICT)
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $rand = mt_rand();

      echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";

      echo "<div class='center'>";

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['event'][20]."</th></tr>";
         echo "</table>";
         echo "<br>";
       } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan ='7'>".$LANG['title'][38]."</th></tr>";
         echo "<tr class='tab_bg_2 center'><td colspan ='7'>";
         printPagerForm();
         echo "</td></tr></table>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['common'][17]."</th><th>" . $LANG['common'][27] . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th><th>" . $LANG['job'][31] . "</th>";
         echo "<th>" . $LANG['common'][37] . "</th>";
         if ($showprivate) {
            echo "<th>" . $LANG['common'][77] . "</th>";
         }
         echo "<th>" . $LANG['job'][35] . "</th></tr>\n";

         while ($data = $DB->fetch_array($result)) {
            if (class_exists($data['itemtype'])) {
               $item = new $data['itemtype'];
               if ($item->getFromDB($data['id'])) {
                  $item->showInTicketSumnary($this, $rand, $showprivate, $caneditall);
               }
            }
         }
         echo "</table>";
      }
      echo "</div>";
   }
    */

   /**
   * Form to add a solution to a ticket
   *
   */
   function showSolutionForm() {
      global $DB,$LANG,$CFG_GLPI;

      $this->check($this->getField('id'), 'r');
      $canedit = $this->can($this->getField('id'), 'w');

      $options=array();
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['job'][48]."</td><td colspan='3'>";

      $current = $this->fields['status'];
      if (!$canedit
          || ($current!='solved'
              && isset($_SESSION['glpiactiveprofile']['helpdesk_status'][$current]['solved'])
              && !$_SESSION['glpiactiveprofile']['helpdesk_status'][$current]['solved'])) {
         // Settings a solution will set status to solved
         echo Dropdown::getDropdownName('glpi_ticketsolutiontypes',
                                        $this->getField('ticketsolutiontypes_id'));
      } else {
         Dropdown::show('TicketSolutionType',
                        array('value' => $this->getField('ticketsolutiontypes_id')));
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['joblist'][6]."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea name='solution' rows='12' cols='100'>";
         echo $this->getField('solution') . "</textarea>";
      } else {
         echo nl2br($this->getField('solution'));
      }
      echo "</td></tr>";

      $options['candel'] = false;
      $this->showFormButtons($options);
   }


   /**
   * Make a select box for Ticket my devices
   *
   *
   * @param $userID User ID for my device section
   * @param $entity_restrict restrict to a specific entity
   * @param $itemtype of selected item
   * @param $items_id of selected item
   *
   * @return nothing (print out an HTML select box)
   */
   static function dropdownMyDevices($userID=0, $entity_restrict=-1, $itemtype=0, $items_id=0) {
      global $DB,$LANG,$CFG_GLPI;

      if ($userID==0) {
         $userID=getLoginUserID();
      }

      $rand=mt_rand();
      $already_add=array();

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)) {
         $my_devices="";

         $my_item= $itemtype.'_'.$items_id;

         // My items
         foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
            if (class_exists($itemtype) && self::isPossibleToAssignType($itemtype)) {
               $itemtable=getTableForItemType($itemtype);
               $item = new $itemtype();
               $query="SELECT *
                     FROM `$itemtable`
                     WHERE `users_id`='".$userID."'";
               if ($item->maybeDeleted()) {
                  $query.=" AND `is_deleted`='0' ";
               }
               if ($item->maybeTemplate()) {
                  $query.=" AND `is_template`='0' ";
               }
               if (in_array($itemtype,$CFG_GLPI["helpdesk_visible_types"])){
                  $query.=" AND `is_helpdesk_visible`='1' ";
               }

               $query.=getEntitiesRestrictRequest("AND",$itemtable,"",$entity_restrict,
                                                $item->maybeRecursive());
               $query.=" ORDER BY `name` ";

               $result=$DB->query($query);
               if ($DB->numrows($result)>0) {
                  $type_name=$item->getTypeName();

                  while ($data=$DB->fetch_array($result)) {
                     $output=$data["name"];
                     if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                        $output.=" (".$data['id'].")";
                     }
                     $output = $type_name . " - " . $output;
                     if ($itemtype != 'Software') {
                        if (!empty($data['serial'])) {
                           $output.=" - ".$data['serial'];
                        }
                        if (!empty($data['otherserial'])) {
                           $output.=" - ".$data['otherserial'];
                        }
                     }
                     $my_devices.="<option title=\"$output\" value='".$itemtype."_".$data["id"]."' ";
                     $my_devices.=($my_item==$itemtype."_".$data["id"]?"selected":"").">";
                     $my_devices.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"])."</option>";

                     $already_add[$itemtype][]=$data["id"];
                  }
               }
            }
         }
         if (!empty($my_devices)) {
            $my_devices="<optgroup label=\"".$LANG['tracking'][1]."\">".$my_devices."</optgroup>";
         }

         // My group items
         if (haveRight("show_group_hardware","1")) {
            $group_where="";
            $groups=array();
            $query="SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
                  FROM `glpi_groups_users`
                  LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                  WHERE `glpi_groups_users`.`users_id`='".$userID."' ".
                        getEntitiesRestrictRequest("AND","glpi_groups","",$entity_restrict);
            $result=$DB->query($query);
            $first=true;
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  if ($first) {
                     $first=false;
                  } else {
                     $group_where.=" OR ";
                  }
                  $group_where.=" `groups_id` = '".$data["groups_id"]."' ";
               }

               $tmp_device="";
               foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                  if (class_exists($itemtype) && self::isPossibleToAssignType($itemtype)) {
                     $itemtable=getTableForItemType($itemtype);
                     $item = new $itemtype();
                     $query="SELECT *
                           FROM `$itemtable`
                           WHERE ($group_where) ".
                                 getEntitiesRestrictRequest("AND",$itemtable,"",
                                    $entity_restrict,$item->maybeRecursive());

                     if ($item->maybeDeleted()) {
                        $query.=" AND `is_deleted`='0' ";
                     }
                     if ($item->maybeTemplate()) {
                        $query.=" AND `is_template`='0' ";
                     }

                     $result=$DB->query($query);
                     if ($DB->numrows($result)>0) {
                        $type_name=$item->getTypeName();
                        if (!isset($already_add[$itemtype])) {
                           $already_add[$itemtype]=array();
                        }
                        while ($data=$DB->fetch_array($result)) {
                           if (!in_array($data["id"],$already_add[$itemtype])) {
                              $output='';
                              if (isset($data["name"])) {
                                 $output = $data["name"];
                              }
                              if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                 $output .= " (".$data['id'].")";
                              }
                              $output = $type_name . " - " . $output;
                              if (isset($data['serial'])) {
                                 $output .= " - ".$data['serial'];
                              }
                              if (isset($data['otherserial'])) {
                                 $output .= " - ".$data['otherserial'];
                              }
                              $tmp_device.="<option title=\"$output\" value='".$itemtype."_".$data["id"];
                              $tmp_device.="' ".($my_item==$itemtype."_".$data["id"]?"selected":"").">";
                              $tmp_device.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"]);
                              $tmp_device.="</option>";

                              $already_add[$itemtype][]=$data["id"];
                           }
                        }
                     }
                  }
               }
               if (!empty($tmp_device)) {
                  $my_devices.="<optgroup label=\"".$LANG['tracking'][1]." - ".$LANG['common'][35]."\">";
                  $my_devices.=$tmp_device."</optgroup>";
               }
            }
         }
         // Get linked items to computers
         if (isset($already_add['Computer']) && count($already_add['Computer'])) {
            $search_computer=" XXXX IN (".implode(',',$already_add['Computer']).') ';
            $tmp_device="";

            // Direct Connection
            $types=array('Peripheral', 'Monitor', 'Printer', 'Phone');
            foreach ($types as $itemtype) {
               if (in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                  && class_exists($itemtype)) {
                  $itemtable=getTableForItemType($itemtype);
                  $item = new $itemtype();
                  if (!isset($already_add[$itemtype])) {
                     $already_add[$itemtype]=array();
                  }
                  $query="SELECT DISTINCT `$itemtable`.*
                        FROM `glpi_computers_items`
                        LEFT JOIN `$itemtable`
                              ON (`glpi_computers_items`.`items_id`=`$itemtable`.`id`)
                        WHERE `glpi_computers_items`.`itemtype`='$itemtype'
                              AND  ".str_replace("XXXX","`glpi_computers_items`.`computers_id`",
                                                   $search_computer);
                  if ($item->maybeDeleted()) {
                     $query.=" AND `is_deleted`='0' ";
                  }
                  if ($item->maybeTemplate()) {
                     $query.=" AND `is_template`='0' ";
                  }
                  $query.=getEntitiesRestrictRequest("AND",$itemtable,"",$entity_restrict)
                        ." ORDER BY `$itemtable`.`name`";

                  $result=$DB->query($query);
                  if ($DB->numrows($result)>0) {
                     $type_name=$item->getTypeName();
                     while ($data=$DB->fetch_array($result)) {
                        if (!in_array($data["id"],$already_add[$itemtype])) {
                           $output=$data["name"];
                           if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                              $output.=" (".$data['id'].")";
                           }
                           $output = $type_name . " - " . $output;
                           if ($itemtype != 'Software') {
                              $output.=" - ".$data['serial']." - ".$data['otherserial'];
                           }
                           $tmp_device.="<option title=\"$output\" value='".$itemtype."_".$data["id"];
                           $tmp_device.="' ".($my_item==$itemtype."_".$data["id"]?"selected":"").">";
                           $tmp_device.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"]);
                           $tmp_device.="</option>";

                           $already_add[$itemtype][]=$data["id"];
                        }
                     }
                  }
               }
            }
            if (!empty($tmp_device)) {
               $my_devices.="<optgroup label=\"".$LANG['reports'][36]."\">".$tmp_device."</optgroup>";
            }

            // Software
            if (in_array('Software',$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
               $query = "SELECT DISTINCT `glpi_softwareversions`.`name` AS version,
                                       `glpi_softwares`.`name` AS name, `glpi_softwares`.`id`
                        FROM `glpi_computers_softwareversions`, `glpi_softwares`,
                              `glpi_softwareversions`
                        WHERE `glpi_computers_softwareversions`.`softwareversions_id`=
                                 `glpi_softwareversions`.`id`
                              AND `glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`
                              AND ".str_replace("XXXX","`glpi_computers_softwareversions`.`computers_id`",
                                                $search_computer)."
                              AND `glpi_softwares`.`is_helpdesk_visible`='1' ".
                              getEntitiesRestrictRequest("AND","glpi_softwares","",$entity_restrict)."
                        ORDER BY `glpi_softwares`.`name`";

               $result=$DB->query($query);
               if ($DB->numrows($result)>0) {
                  $tmp_device="";
                  $item = new Software();
                  $type_name=$item->getTypeName();
                  if (!isset($already_add['Software'])) {
                     $already_add['Software'] = array();
                  }
                  while ($data=$DB->fetch_array($result)) {
                     if (!in_array($data["id"],$already_add['Software'])) {
                        $output  = "$type_name - ".$data["name"]." (v. ".$data["version"].")";
                        $output .= ($_SESSION["glpiis_ids_visible"]?" (".$data["id"].")":"");

                        $tmp_device.="<option title=\"$output\" value='Software_".$data["id"]."' ";
                        $tmp_device.=($my_item == 'Software'."_".$data["id"]?"selected":"").">";
                        $tmp_device.=utf8_substr($output,0,$_SESSION["glpidropdown_chars_limit"]);
                        $tmp_device.="</option>";

                        $already_add['Software'][]=$data["id"];
                     }
                  }
                  if (!empty($tmp_device)) {
                     $my_devices.="<optgroup label=\"".ucfirst($LANG['software'][17])."\">";
                     $my_devices.=$tmp_device."</optgroup>";
                  }
               }
            }
         }
         echo "<div id='tracking_my_devices'>";
         echo $LANG['tracking'][1].":&nbsp;<select id='my_items' name='_my_items'><option value=''>--- ";
         echo $LANG['help'][30]." ---</option>$my_devices</select></div>";
      }
   }
   /**
   * Make a select box for Tracking All Devices
   *
   * @param $myname select name
   * @param $itemtype preselected value.for item type
   * @param $items_id preselected value for item ID
   * @param $admin is an admin access ?
   * @param $entity_restrict Restrict to a defined entity
   * @return nothing (print out an HTML select box)
   */
   static function dropdownAllDevices($myname,$itemtype,$items_id=0,$admin=0,$entity_restrict=-1) {
      global $LANG,$CFG_GLPI,$DB;

      $rand=mt_rand();

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]==0) {
         echo "<input type='hidden' name='$myname' value='0'>";
         echo "<input type='hidden' name='items_id' value='0'>";
      } else {
         echo "<div id='tracking_all_devices'>";
         if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE)) {
            // Display a message if view my hardware
            if (!$admin
               && $_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)) {
               echo $LANG['tracking'][2]."&nbsp;: ";
            }

            $types = self::getAllTypesForHelpdesk();
            echo "<select id='search_$myname$rand' name='$myname'>\n";
            echo "<option value='-1' >-----</option>\n";
            echo "<option value='' ".(empty($itemtype)?" selected":"").">".$LANG['help'][30]."</option>";
            $found_type=false;
            foreach ($types as $type => $label) {
               if ($type==$itemtype) {
                  $found_type=true;
               }
               echo "<option value='".$type."' ".(($type==$itemtype)?" selected":"").">".$label;
               echo "</option>\n";
            }
            echo "</select>";

            $params=array('itemtype'=>'__VALUE__',
                        'entity_restrict'=>$entity_restrict,
                        'admin'=>$admin,
                        'myname'=>"items_id",);

            ajaxUpdateItemOnSelectEvent("search_$myname$rand","results_$myname$rand",$CFG_GLPI["root_doc"].
                                       "/ajax/dropdownTrackingDeviceType.php",$params);
            echo "<span id='results_$myname$rand'>\n";

            // Display default value if itemtype is displayed
            if ($found_type && $itemtype && class_exists($itemtype) && $items_id) {
               $item = new $itemtype();
               if ($item->getFromDB($items_id)) {
                  echo "<select name='items_id'>\n";
                  echo "<option value='$items_id'>".$item->getName();
                  echo "</option></select>";
               }
            }
            echo "</span>\n";
         }
         echo "</div>";
      }
      return $rand;
   }

   function showCost($target) {
      global $DB,$LANG;

      $this->check($this->getField('id'), 'r');
      $canedit = $this->can($this->getField('id'), 'w');

      $options = array('colspan' => 1);
      $this->showFormHeader($options);

      echo "<tr><th colspan='4'>".$LANG['job'][47]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left' width='50%'>".$LANG['job'][20]."&nbsp;: </td>";

      echo "<td class='b'>".Ticket::getRealtime($this->fields["realtime"])."</td>";
      echo "</tr>";

      if ($canedit) {  // admin = oui on affiche les couts liés à l'interventions
         echo "<tr class='tab_bg_1'>";
         echo "<td class='left'>".$LANG['job'][40]."&nbsp;: </td>";

         echo "<td><input type='text' maxlength='100' size='15' name='cost_time' value='".
                    formatNumber($this->fields["cost_time"],true)."'></td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='left'>".$LANG['job'][41]."&nbsp;: </td>";

         echo "<td><input type='text' maxlength='100' size='15' name='cost_fixed' value='".
                    formatNumber($this->fields["cost_fixed"],true)."'></td>";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='left'>".$LANG['job'][42]."&nbsp;: </td>";

         echo "<td><input type='text' maxlength='100' size='15' name='cost_material' value='".
                    formatNumber($this->fields["cost_material"],true)."'></td>";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='left'>".$LANG['job'][43]."&nbsp;: </td>";

         echo "<td class='b'>";
         echo self::trackingTotalCost($this->fields["realtime"], $this->fields["cost_time"],
                                      $this->fields["cost_fixed"],$this->fields["cost_material"]);
         echo "</td>";
         echo "</tr>\n";
      }
      $options['candel'] = false;
      $this->showFormButtons($options);
   }

   /**
    * Calculate Ticket TCO for an item
    *
    *@param $item CommonDBTM object of the item
    *
    *@return float
    *
    **/
   static function computeTco(CommonDBTM $item) {
      global $DB;

      $totalcost=0;

      $query = "SELECT `realtime`, `cost_time`, `cost_fixed`, `cost_material`
                FROM `glpi_tickets`
                WHERE `itemtype` = '".get_class($item)."'
                      AND `items_id` = '".$item->getField('id')."'
                      AND (`cost_time` > '0'
                           OR `cost_fixed` > '0'
                           OR `cost_material` > '0')";
      $result = $DB->query($query);

      $i = 0;
      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_array($result)) {
            $totalcost += self::trackingTotalCost($data["realtime"],$data["cost_time"],$data["cost_fixed"],
                                            $data["cost_material"]);
         }
      }
      return $totalcost;
   }

   /**
    * Computer total cost of a ticket
    *
    * @param $realtime float : ticket realtime
    * @param $cost_time float : ticket time cost
    * @param $cost_fixed float : ticket fixed cost
    * @param $cost_material float : ticket material cost
    * @return total cost formatted string
    */
   static function trackingTotalCost($realtime,$cost_time,$cost_fixed,$cost_material) {
      return formatNumber(($realtime*$cost_time)+$cost_fixed+$cost_material,true);
   }

   function showForm($ID, $options=array()) {
      global $DB,$CFG_GLPI,$LANG;
      $canupdate = haveRight('update_ticket','1');
      $canpriority = haveRight('update_priority','1');
      $showuserlink=0;
      if (haveRight('user','r')) {
         $showuserlink=1;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w',$options);
      }

      $this->showTabs($options);

      $canupdate_descr = $canupdate || ($this->numberOfFollowups()==0
                                        && $this->fields['users_id']===getLoginUserID());


      echo "<form method='post' name='form_ticket' enctype='multipart/form-data'
               action='".$CFG_GLPI["root_doc"]."/front/ticket.form.php'>";
      echo '<div class="center" id="tabsbody">';
      echo "<table class='tab_cadre_fixe'>";

      // Optional line
      if (isMultiEntitiesMode()) {
         echo '<tr>';
         echo '<th colspan="4">';
         if ($ID) {
            echo $this->getTypeName()." - ".$LANG['common'][2]." $ID (";
            echo Dropdown::getDropdownName('glpi_entities',$this->fields['entities_id']) . ")";
         } else {
            echo $LANG['job'][46]."&nbsp;:&nbsp;".Dropdown::getDropdownName("glpi_entities",
                                                                  $this->fields['entities_id']);
         }
         echo '</th>';
         echo '</tr>';
      }

      echo "<tr>";
      echo "<th class='left' colspan='2' width='50%'>";

      echo "<table>";
      echo "<tr>";
      echo "<td><span class='tracking_small'>".$LANG['joblist'][11]."&nbsp;: </span></td>";
      echo "<td>";
      if ($ID) {
         showDateTimeFormItem("date",$this->fields["date"],1,false,$canupdate);
      } else {
         showDateTimeFormItem("date",date("Y-m-d H:i:s"),1);
      }
      echo "</td></tr>";
      if ($ID) {
         echo "<tr><td><span class='tracking_small'>".$LANG['job'][2]." &nbsp;:</span></td><td>";
         if ($canupdate) {
            User::dropdown(array('name'   => 'users_id_recipient',
                                 'value'  => $this->fields["users_id_recipient"],
                                 'entity' => $this->fields["entities_id"],
                                 'right'  => 'all'));
         } else {
            echo getUserName($this->fields["users_id_recipient"],$showuserlink);
         }
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</th>";

      echo "<th class='left'  colspan='2' width='50%'>";
      if ($ID) {
         echo "<table>";

         if ($this->fields["status"]=='closed') {
            echo "<tr>";
            echo "<td><span class='tracking_small'>".$LANG['joblist'][12]."&nbsp;: </span></td>";
            echo "<td>";
            showDateTimeFormItem("closedate",$this->fields["closedate"],1,false,$canupdate);
            echo "</td>";
         }


         echo "<tr><td><span class='tracking_small'>".$LANG['common'][26]."&nbsp;:</span></td><td>";
         echo "<span class='tracking_small'>".convDateTime($this->fields["date_mod"])."</span>\n";
         echo "</td></tr>";
         echo "</table>";

      }
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left' width='60'>".$LANG['joblist'][0]."&nbsp;: </td>";
      echo "<td>";
      if ($canupdate) {
         Ticket::dropdownStatus("status",$this->fields["status"],2); // Allowed status
      } else {
         echo Ticket::getStatus($this->fields["status"]);
      }
      echo "</td>";
      echo "<th class='center b' colspan='2'>".$LANG['job'][4]."&nbsp;: </th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['joblist'][29]."&nbsp;: </td>";
      echo "<td>";
      if ($canupdate
          && ($canpriority || !$ID || $this->fields["users_id_recipient"]===getLoginUserID())) {
         // Only change during creation OR when allowed to change priority OR when user is the creator
         $idurgency = Ticket::dropdownUrgency("urgency",$this->fields["urgency"]);
      } else {
         $idurgency = "value_urgency".mt_rand();
         echo "<input id='$idurgency' type='hidden' name='urgency' value='".$this->fields["urgency"]."'>";
         echo Ticket::getUrgencyName($this->fields["urgency"]);
      }
      echo "</td>";
      echo "<td class='left'>";
      if (!$ID && haveRight("update_ticket","1")) {
         echo $LANG['job'][4]."&nbsp;: </td>";
         echo "<td>";

         //List all users in the active entity (and all it's sub-entities if needed)
         User::dropdown(array('value'        => $options["users_id"],
                              'entity'       => $this->fields["entities_id"],
                              'entity_sons'  => haveAccessToEntity($this->fields["entities_id"],true),
                              'right'        => 'all',
                              'helpdesk_ajax'=> 1,
                              'ldap_import'=> true));

         //Get all the user's entities
         $all_entities = Profile_User::getUserEntities($options["users_id"], true);
         $values = array();

         //For each user's entity, check if the technician which creates the ticket have access to it
         foreach ($all_entities as $tmp => $ID_entity) {
            if (haveAccessToEntity($ID_entity)) {
               $values[] = $ID_entity;
            }
         }

         $count = count($values);

         if ($count>0 && !in_array($this->fields["entities_id"],$values)) {
            // If entity is not in the list of user's entities,
            // then use as default value the first value of the user's entites list
            $this->fields["entities_id"] = $values[0];
         }

         //If user have access to more than one entity, then display a combobox
         if ($count > 1) {
            $rand = Dropdown::show('Entity',
                           array('value'        => $this->fields["entities_id"],
                                 'entity'       => $values,
                                 'auto_submit'  => 1));

         } else {
            echo "<input type='hidden' name='entities_id' value='".$this->fields["entities_id"]."'>";
         }
      } else if ($canupdate){
         echo $LANG['common'][34]."&nbsp;: </td>";
         echo "<td>";
         User::dropdown(array('value'  => $this->fields["users_id"],
                              'entity' => $this->fields["entities_id"],
                              'right'  => 'all',
                              'ldap_import'=> true));
      } else {
         echo $LANG['common'][34]."&nbsp;: </td>";
         echo "<td>";
         echo getUserName($this->fields["users_id"],$showuserlink);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['joblist'][30]."&nbsp;: </td>";
      echo "<td>";
      if ($canupdate) {
         $idimpact = Ticket::dropdownImpact("impact",$this->fields["impact"]);
      } else {
         echo Ticket::getImpactName($this->fields["impact"]);
      }
      echo "</td>";
      echo "<td class='left'>".$LANG['common'][35]."&nbsp;: </td>";
      echo "<td>";
      if ($canupdate) {
         Dropdown::show('Group',
                  array('value'  => $this->fields["groups_id"],
                        'entity' => $this->fields["entities_id"]));
      } else {
         echo Dropdown::getDropdownName("glpi_groups",$this->fields["groups_id"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['joblist'][2]."&nbsp;: </td>";
      echo "<td>";
      if ($canupdate && $canpriority) {
         $idpriority = Ticket::dropdownPriority("priority",$this->fields["priority"],false,true);
         $idajax = 'change_priority_' . mt_rand();
         echo "&nbsp;<span id='$idajax' style='display:none'></span>";
      } else {
         $idajax = 'change_priority_' . mt_rand();
         $idpriority = 0;
         echo "<span id='$idajax'>".Ticket::getPriorityName($this->fields["priority"])."</span>";
      }
      if ($canupdate) {
         $params=array('urgency'  => '__VALUE0__',
                       'impact'   => '__VALUE1__',
                       'priority' => $idpriority);
         ajaxUpdateItemOnSelectEvent(array($idurgency, $idimpact), $idajax,
                                     $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      }
      echo "</td>";
      echo "<th class='center b' colspan='2'>".$LANG['job'][5]."&nbsp;: </th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['common'][36]."&nbsp;: </td>";
      echo "<td >";
      if ($canupdate) {
         Dropdown::show('TicketCategory',
                  array('value'  => $this->fields["ticketcategories_id"],
                        'entity' => $this->fields["entities_id"]));
      } else {
         echo Dropdown::getDropdownName("glpi_ticketcategories",$this->fields["ticketcategories_id"]);
      }
      echo "</td>";
      if (haveRight("assign_ticket","1")) {
         echo "<td class='left'>".$LANG['job'][6]."&nbsp;: </td>";
         echo "<td>";
         User::dropdown(array('name'   => 'users_id_assign',
                              'value'  => $this->fields["users_id_assign"],
                              'right'  => 'own_ticket',
                              'entity' => $this->fields["entities_id"],
                              'ldap_import'=> true));
         echo "</td>";
      } else if (haveRight("steal_ticket","1")) {
         echo "<td class='right'>".$LANG['job'][6]."&nbsp;: </td>";
         echo "<td>";
         User::dropdown(array('name'   => 'users_id_assign',
                              'value'  => $this->fields["users_id_assign"],
                              'entity' => $this->fields["entities_id"],
                              'ldap_import'=> true));
         echo "</td>";
      } else if (haveRight("own_ticket","1") && $this->fields["users_id_assign"]==0) {
         echo "<td class='right'>".$LANG['job'][6]."&nbsp;: </td>";
         echo "<td>";
         User::dropdown(array('name'   => 'users_id_assign',
                              'value'  => $this->fields["users_id_assign"],
                              'entity' => $this->fields["entities_id"],
                              'ldap_import'=> true));
         echo "</td>";
      } else {
         echo "<td class='left'>".$LANG['job'][6]."&nbsp;: </td>";
         echo "<td>";
         echo getUserName($this->fields["users_id_assign"],$showuserlink);
         echo "</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['job'][44]."&nbsp;: </td>";
      echo "<td>";
      if ($canupdate) {
         Dropdown::show('RequestType',array('value' => $this->fields["requesttypes_id"]));
      } else {
         echo Dropdown::getDropdownName('glpi_requesttypes', $this->fields["requesttypes_id"]);
      }
      echo "</td>";
      if (haveRight("assign_ticket","1")) {
         echo "<td class='left'>".$LANG['common'][35]."&nbsp;: </td>";
         echo "<td>";
         Dropdown::show('Group',
                  array('name'   => 'groups_id_assign',
                        'value'  => $this->fields["groups_id_assign"],
                        'entity' => $this->fields["entities_id"]));
         echo "</td>";
      } else {
         echo "<td class='left'>".$LANG['common'][35]."&nbsp;: </td>";
         echo "<td>";
         echo Dropdown::getDropdownName("glpi_groups",$this->fields["groups_id_assign"]);
         echo "</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left' rowspan='2'>".$LANG['common'][1]."&nbsp;: </td>";
      echo "<td rowspan='2'>";
      if ($canupdate) {
         if ($ID && $this->fields['itemtype'] && class_exists($this->fields['itemtype'])) {
            $item = new $this->fields['itemtype']();
            if ($item->can($this->fields["items_id"],'r')) {
               echo $item->getTypeName()." - ".$item->getLink(true);
            } else {
               echo $item->getTypeName()." ".$item->getNameID();
            }
         } else {
            Ticket::dropdownMyDevices($this->fields["users_id"],$this->fields["entities_id"],
                              $this->fields["itemtype"], $this->fields["items_id"]);
         }
         Ticket::dropdownAllDevices("itemtype", $this->fields["itemtype"], $this->fields["items_id"],
                                    1, $this->fields["entities_id"]);
      } else {
         if ($ID && $this->fields['itemtype'] && class_exists($this->fields['itemtype'])) {
            $item = new $this->fields['itemtype']();
            echo $item->getTypeName()." ".$item->getNameID();
         } else {
            echo $LANG['help'][30];
         }
      }
      echo "</td>";

      echo "<td class='left'>".$LANG['financial'][26]."&nbsp;: </td>";
      if (haveRight("assign_ticket","1")) {
         echo "<td>";
         Dropdown::show('Supplier',
                  array('name'   => 'suppliers_id_assign',
                        'value'  => $this->fields["suppliers_id_assign"],
                        'entity' => $this->fields["entities_id"]));

         echo "</td>";
      } else {
         echo "<td>";
         echo Dropdown::getDropdownName("glpi_suppliers",$this->fields["suppliers_id_assign"]);
         echo "</td>";
      }
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      // Need comment right to add a followup with the realtime
      if (haveRight("global_add_followups","1") && !$ID) {
         echo "<td class='left'>".$LANG['job'][20]."&nbsp;: </td>";
         echo "<td class='center' colspan='3'>";
         Dropdown::showInteger('hour',$options['hour'],0,100);
         echo "&nbsp;".$LANG['job'][21]."&nbsp;&nbsp;";
         Dropdown::showInteger('minute',$options['minute'],0,59);
         echo "&nbsp;".$LANG['job'][22]."&nbsp;&nbsp;";
      } else { // Display validation state
         echo "<td>".$LANG['validation'][0]."</td>";
         if ($canupdate){
            echo "<td>";
            TicketValidation::dropdownStatus('global_validation',
                                       array('value'=>$this->fields['global_validation']));
         } else {
            echo "<td>".TicketValidation::getStatus($this->fields['global_validation']);
         }
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['common'][57]."&nbsp;: </th>";
      echo "<th>";
      if ($canupdate_descr) {
         $rand = mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showName$rand(){\n";
         echo "Ext.get('name$rand').setDisplayed('none');";
         $params = array('maxlength' => 250,
                         'size'      => 50,
                         'name'      => 'name',
                         'data'      => rawurlencode($this->fields["name"]));
         ajaxUpdateItemJsCode("viewname$rand",$CFG_GLPI["root_doc"]."/ajax/inputtext.php",$params,
                              false);
         echo "}";
         echo "</script>\n";
         echo "<div id='name$rand' class='tracking' onClick='showName$rand()'>\n";
         if (empty($this->fields["name"])) {
            echo $LANG['reminder'][15];
         } else {
            echo $this->fields["name"];
         }
         echo "</div>\n";

         echo "<div id='viewname$rand'>\n";
         echo "</div>\n";
         if (!$ID) {
            echo "<script type='text/javascript' >\n
            showName$rand();
            </script>";
         }
      } else {
         if (empty($this->fields["name"])) {
            echo $LANG['reminder'][15];
         } else {
            echo $this->fields["name"];
         }
      }
      echo "</th>";
      echo "<th colspan='2'>";
      if ($CFG_GLPI["use_mailing"]==1) {
         echo $LANG['title'][10];
      }
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='2'>".$LANG['joblist'][6]."</td>";
      echo "<td class='left' rowspan='2'>";
      if ($canupdate_descr) { // Admin =oui on autorise la modification de la description
         $rand = mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showDesc$rand(){\n";
         echo "Ext.get('desc$rand').setDisplayed('none');";
         $params = array('rows'  => 6,
                         'cols'  => 50,
                         'name'  => 'content',
                         'data'  => rawurlencode($this->fields["content"]));
         ajaxUpdateItemJsCode("viewdesc$rand",$CFG_GLPI["root_doc"]."/ajax/textarea.php",$params,
                              false);
         echo "}";
         echo "</script>\n";
         echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
         if (!empty($this->fields["content"])) {
            echo nl2br($this->fields["content"]);
         } else {
            echo $LANG['job'][33];
         }
         echo "</div>\n";

         echo "<div id='viewdesc$rand'></div>\n";
         if (!$ID) {
            echo "<script type='text/javascript' >\n
            showDesc$rand();
            </script>";
         }
      } else {
         echo nl2br($this->fields["content"]);
      }
      echo "</td>";
      // Mailing ? Y or no ?
      if ($CFG_GLPI["use_mailing"]==1) {
         echo "<td class='left'>".$LANG['job'][19]."&nbsp;: </td>";
         echo "<td>";
         if (!$ID) {
            $query = "SELECT `email`
                      FROM `glpi_users`
                      WHERE `id` ='".$this->fields["users_id"]."'";
            $result=$DB->query($query);

            $email = "";
            if ($result && $DB->numrows($result)) {
               $email=$DB->result($result,0,"email");
            }
            Dropdown::showYesNo('use_email_notification',!empty($email));
         } else {
            if ($canupdate){
               Dropdown::showYesNo('use_email_notification',$this->fields["use_email_notification"]);
            } else {
               if ($this->fields["use_email_notification"]) {
                  echo $LANG['choice'][1];
               } else {
                  echo $LANG['choice'][0];
               }
            }
         }
      } else {
         echo "<td colspan='2'>&nbsp;";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      // Mailing ? Y or no ?
      if ($CFG_GLPI["use_mailing"] == 1) {
         echo "<td class='left'>".$LANG['joblist'][27]."&nbsp;: </td>";
         echo "<td>";
         if (!$ID) {
            echo "<input type='text' size='30' name='user_email' value='$email'>";
         } else {
            if ($canupdate) {
               autocompletionTextField($this,"user_email");
               if (!empty($this->fields["user_email"])) {
                  echo "<a href='mailto:".$this->fields["user_email"]."'>";
                  echo "<img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='Mail'></a>";
               }
            } else if (!empty($this->fields["user_email"])) {
               echo "<a href='mailto:".$this->fields["user_email"]."'>".$this->fields["user_email"]."</a>";
            } else {
               echo "&nbsp;";
            }
         }
      } else {
          echo "<td colspan='2'>&nbsp;";
      }
      echo "</td></tr>";

      if ($canupdate
          || $canupdate_descr
          || haveRight("global_add_followups","1")
          ||(haveRight("add_followups","1") && !strstr($this->fields["status"],'old_'))
          || haveRight("assign_ticket","1")
          || haveRight("steal_ticket","1")) {

         echo "<tr class='tab_bg_1'>";
         if ($ID) {
            if (haveRight('delete_ticket',1)){
               echo "<td class='tab_bg_2 center' colspan='2'>";
               echo "<input type='submit' class='submit' name='update' value='".$LANG['buttons'][7]."'>";
               echo "</td><td class='tab_bg_2 center' colspan='2'>";
               echo "<input type='submit' class='submit' name='delete' value='".$LANG['buttons'][6]."'
                              OnClick='return window.confirm(\"".$LANG['common'][50]."\");'>";
            } else {
               echo "<td class='tab_bg_2 center' colspan='4'>";
               echo "<input type='submit' class='submit' name='update' value='".$LANG['buttons'][7]."'>";
            }
         } else {
            echo "<td class='tab_bg_2 center' colspan='2'>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.form.php'>";
            echo "<input type='button' value='".$LANG['buttons'][16]."' class='submit'></a></td>";
            echo "<td class='tab_bg_2 center' colspan='2'>";
            echo "<input type='submit' name='add' value='".$LANG['buttons'][2]."' class='submit'>";
         }
         echo "</td></tr>";
      }

      echo "</table>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "</div>";

      echo "</form>";

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }


   static function showCentralList($start,$status="process",$showgrouptickets=true) {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("show_all_ticket","1")
         && !haveRight("show_assign_ticket","1")
         && !haveRight("create_ticket","1")) {
         return false;
      }

      $search_users_id = " (`glpi_tickets`.`users_id` = '".getLoginUserID()."') ";
      $search_assign = " `users_id_assign` = '".getLoginUserID()."' ";
      if ($showgrouptickets) {
         $search_users_id = " 0 = 1 ";
         $search_assign = " 0 = 1 ";
         if (count($_SESSION['glpigroups'])) {
            $groups = implode("','",$_SESSION['glpigroups']);
            $search_assign = " `groups_id_assign` IN ('$groups') ";
            if (haveRight("show_group_ticket",1)) {
               $search_users_id = " (`groups_id` IN ('$groups')) ";
            }
         }

      }

      $query = "SELECT `id`
               FROM `glpi_tickets`";

      switch ($status) {
         case "waiting" : // on affiche les tickets en attente
               $query .= "WHERE ($search_assign)
                           AND `status` ='waiting' ".
                           getEntitiesRestrictRequest("AND","glpi_tickets");
               break;

         case "process" : // on affiche les tickets planifiés ou assignés au user
               $query .= "WHERE ( $search_assign )
                                                AND (`status` IN ('plan','assign')) ".
                           getEntitiesRestrictRequest("AND","glpi_tickets");
               break;
         case "toapprove" : // on affiche les tickets planifiés ou assignés au user

               $query .= "WHERE ( `status` = 'solved' ) AND ($search_users_id";
               if (!$showgrouptickets) {
                  $query.=" OR `glpi_tickets`.users_id_recipient = '".getLoginUserID()."' ";
               }
               $query .= ")".getEntitiesRestrictRequest("AND","glpi_tickets");
               break;
         case "requestbyself" : // on affiche les tickets demandés le user qui sont planifiés ou assignés
               // à quelqu'un d'autre (exclut les self-tickets)
         default :
            $query .= "WHERE ($search_users_id)
                        AND (`status` IN ('new', 'plan', 'assign', 'waiting'))
                        AND NOT ( $search_assign ) ".
                           getEntitiesRestrictRequest("AND","glpi_tickets");
               break;
      }

      $query .= " ORDER BY date_mod DESC";
      //echo $query;
      $result = $DB->query($query);
      $numrows = $DB->numrows($result);

      $query .= " LIMIT ".intval($start).",".intval($_SESSION['glpilist_limit']);
      //echo $query;
      $result = $DB->query($query);
      $i = 0;
      $number = $DB->numrows($result);
      //echo $query;
      if ($number > 0) {
         echo "<table class='tab_cadrehov' style='width:420px'>";

         echo "<tr><th colspan='5'>";

         $options['reset']  = 'reset';
         if ($showgrouptickets) {
            switch ($status) {
               case 'waiting' :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'waiting';
                  $options['link'][0]        = 'AND';

                  $num=1;
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 8; // groups_id_assign
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]        = ($num==1?'AND':'OR');
                     $num++;

                  }

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                        "\">".$LANG['joblist'][13]." (".$LANG['joblist'][26].")"."</a>";
                  break;
               case 'toapprove' :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'solved';
                  $options['link'][0]        = 'AND';

                  $num=1;
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 71; // groups_id
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]        = ($num==1?'AND':'OR');
                     $num++;

                  }

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                        "\">".$LANG['central'][18]."</a>";
                  break;

                  case "process";
                     $options['field'][0]      = 12; // status
                     $options['searchtype'][0] = 'equals';
                     $options['contains'][0]   = 'process';
                     $options['link'][0]        = 'AND';

                     $num=1;
                     foreach ($_SESSION['glpigroups'] as $gID) {
                        $options['field'][$num]      = 8; // groups_id_assign
                        $options['searchtype'][$num] = 'equals';
                        $options['contains'][$num]   = $gID;
                        $options['link'][$num]        = ($num==1?'AND':'OR');
                        $num++;

                     }

                     echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                           "\">".$LANG['joblist'][13]."</a>";
                     break;
                  case 'requestbyself':
                  default :
                     $options['field'][0]      = 12; // status
                     $options['searchtype'][0] = 'equals';
                     $options['contains'][0]   = 'process';
                     $options['link'][0]        = 'AND';

                     $num=1;
                     foreach ($_SESSION['glpigroups'] as $gID) {
                        $options['field'][$num]      = 71; // groups_id
                        $options['searchtype'][$num] = 'equals';
                        $options['contains'][$num]   = $gID;
                        $options['link'][$num]        = ($num==1?'AND':'OR');
                        $num++;

                     }

                     echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                           "\">".$LANG['central'][9]."</a>";
                     break;
            }
         } else {
            switch ($status) {
               case 'waiting' :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'waiting';
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 5; // users_id_assign
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = getLoginUserID();
                  $options['link'][1]        = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                        "\">".$LANG['joblist'][13]." (".$LANG['joblist'][26].")"."</a>";
                  break;
               case "process" :
                  $options['field'][0]      = 5; // users_id_assign
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = getLoginUserID();
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'process';
                  $options['link'][1]        = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                           "\">".$LANG['joblist'][13]."</a>";
                  break;
               case "toapprove" :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'solved';
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 4; // users_id_assign
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = getLoginUserID();
                  $options['link'][1]        = 'AND';

                  $options['field'][2]      = 22; // users_id_recipient
                  $options['searchtype'][2] = 'equals';
                  $options['contains'][2]   = getLoginUserID();
                  $options['link'][2]        = 'OR';


                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                           "\">".$LANG['central'][18]."</a>";
                  break;
               case 'requestbyself':
               default:
                  $options['field'][0]      = 4; // users_id
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = getLoginUserID();
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'notold';
                  $options['link'][1]        = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
                           "\">".$LANG['central'][9]."</a>";

                  break;
            }
         }

         echo "</th></tr>";
         echo "<tr><th></th>";
         echo "<th>".$LANG['job'][4]."</th>";
         echo "<th>".$LANG['common'][1]."</th>";
         echo "<th>".$LANG['joblist'][6]."</th></tr>";
         while ($i < $number) {
            $ID = $DB->result($result, $i, "id");
            Ticket::showVeryShort($ID);
            $i++;
         }
         echo "</table>";
      } else {
         echo "<table class='tab_cadrehov' style='width:420px'>";
         echo "<tr><th>";
         switch ($status) {
            case 'waiting' :
               echo $LANG['joblist'][13]." (".$LANG['joblist'][26].")";
               break;

            case 'process' :
               echo $LANG['joblist'][13];
               break;
            case 'toapprove' :
               echo $LANG['central'][18];
               break;
            case 'requestbyself' :
            default :
               echo $LANG['central'][9];
               break;
         }
         echo "</th></tr>";
         echo "</table>";
      }
   }

   static function showCentralCount() {
      global $DB,$CFG_GLPI, $LANG;

      // show a tab with count of jobs in the central and give link
      if (!haveRight("show_all_ticket","1")) {
         return false;
      }

      $query = "SELECT `status`, count(*) AS COUNT
               FROM `glpi_tickets` ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets")."
               GROUP BY `status`";
      $result = $DB->query($query);

      $status = array('new'      => 0,
                     'assign'   => 0,
                     'plan'     => 0,
                     'waiting'  => 0,
                     'solved'  => 0,
                     'closed'  => 0);

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_assoc($result)) {
            $status[$data["status"]] = $data["COUNT"];
         }
      }

      $options['field'][0]      = 12;
      $options['searchtype'][0] = 'equals';
      $options['contains'][0]   = 'all';
      $options['link'][0]        = 'AND';
      $options['reset']='reset';

      echo "<table class='tab_cadrehov' >";
      echo "<tr><th colspan='2'>";

      $options['contains'][0]   = 'process';
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['title'][10]."</a></th></tr>";
      echo "<tr><th>".$LANG['title'][28]."</th><th>".$LANG['tracking'][29]."</th></tr>";

      $options['contains'][0]   = 'new';
      echo "<tr class='tab_bg_2'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['tracking'][30]."</a> </td>";
      echo "<td>".$status["new"]."</td></tr>";

      $options['contains'][0]   = 'assign';
      echo "<tr class='tab_bg_2'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['tracking'][31]."</a></td>";
      echo "<td>".$status["assign"]."</td></tr>";

      $options['contains'][0]   = 'plan';
      echo "<tr class='tab_bg_2'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['tracking'][32]."</a></td>";
      echo "<td>".$status["plan"]."</td></tr>";

      $options['contains'][0]   = 'waiting';
      echo "<tr class='tab_bg_2'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['joblist'][26]."</a></td>";
      echo "<td>".$status["waiting"]."</td></tr>";

      $options['contains'][0]   = 'solved';
      echo "<tr class='tab_bg_2'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['joblist'][32]."</a></td>";
      echo "<td>".$status["waiting"]."</td></tr>";

      $options['contains'][0]   = 'closed';
      echo "<tr class='tab_bg_2'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."\">".
            $LANG['joblist'][33]."</a></td>";
      echo "<td>".$status["waiting"]."</td></tr>";

      echo "</table><br>";
   }

   static function showCentralNewList() {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("show_all_ticket","1")) {
         return false;
      }

      $query = "SELECT ".Ticket::getCommonSelect()."
               FROM `glpi_tickets` ".Ticket::getCommonLeftJoin()."
               WHERE `status` = 'new' ".
                     getEntitiesRestrictRequest("AND","glpi_tickets")."
               ORDER BY `glpi_tickets`.`date_mod` DESC
               LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         initNavigateListItems('Ticket');

         $options['field'][0]      = 12;
         $options['searchtype'][0] = 'equals';
         $options['contains'][0]   = 'new';
         $options['link'][0]        = 'AND';
         $options['reset']='reset';

         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='10'>".$LANG['central'][10]." ($number)&nbsp;: &nbsp;";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options).
               "'>".$LANG['buttons'][40]."</a>";
         echo "</th></tr>";

         Ticket::commonListHeader(HTML_OUTPUT);

         while ($data=$DB->fetch_assoc($result)) {
            addToNavigateListItems('Ticket',$data["id"]);
            Ticket::showShort($data, 0);
         }
         echo "</table></div>";
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
         echo "</table>";
         echo "</div><br>";
      }
   }

   static function commonListHeader($output_type=HTML_OUTPUT) {
      global $LANG,$CFG_GLPI;

      // New Line for Header Items Line
      echo Search::showNewLine($output_type);
      // $show_sort if
      $header_num=1;

      $items=array();

      $items[$LANG['joblist'][0]] = "glpi_tickets.status";
      $items[$LANG['common'][27]] = "glpi_tickets.date";
      $items[$LANG['common'][26]] = "glpi_tickets.date_mod";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $items[$LANG['Menu'][37]] = "glpi_entities.completename";
      }
      $items[$LANG['joblist'][2]]   = "glpi_tickets.priority";
      $items[$LANG['job'][4]]       = "glpi_tickets.users_id";
      $items[$LANG['joblist'][4]]   = "glpi_tickets.users_id_assign";
      $items[$LANG['common'][1]]    = "glpi_tickets.itemtype,glpi_tickets.items_id";
      $items[$LANG['common'][36]]   = "glpi_ticketcategories.completename";
      $items[$LANG['common'][57]]   = "glpi_tickets.name";

      foreach ($items as $key => $val) {
         $issort = 0;
         $link = "";
         echo Search::showHeaderItem($output_type,$key,$header_num,$link);
      }

      // End Line for column headers
      echo Search::showEndLine($output_type);
   }

   /**
   * Display tickets for an item
   *
   * Will also display tickets of linked items
   *
   * @param $itemtype
   * @param $items_id
   *
   * @return nothing (display a table)
   */
   static function showListForItem($itemtype,$items_id) {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("show_all_ticket","1")) {
         return false;
      }
      if (!class_exists($itemtype)) {
         return false;
      }
      $item=new $itemtype();
      if (!$item->getFromDB($items_id)) {
         return false;
      }

      $query = "SELECT ".Ticket::getCommonSelect()."
               FROM `glpi_tickets` ".Ticket::getCommonLeftJoin()."
               WHERE (`items_id` = '$items_id'
                     AND `itemtype` = '$itemtype') ".
                     getEntitiesRestrictRequest("AND","glpi_tickets")."
               ORDER BY `glpi_tickets`.`date_mod` DESC
               LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      // Ticket for the item
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      if ($number > 0) {
         initNavigateListItems('Ticket',$item->getTypeName()." = ".$item->getName());

         $options['field'][0]      = 12;
         $options['searchtype'][0] = 'equals';
         $options['contains'][0]   = 'new';
         $options['link'][0]        = 'AND';

         $options['itemtype2'][0]  = $itemtype;
         $options['field2'][0]      = 2;
         $options['searchtype2'][0] = 'equals';
         $options['contains2'][0]   = $items_id;
         $options['link2'][0]        = 'AND';

         $options['reset']='reset';


         echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;: &nbsp;";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."'>".
                     $LANG['buttons'][40]."</a>";
         echo "</th></tr>";
      } else {
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
      }

      // Link to open a new ticcket
      if ($items_id) {
         echo "<tr><td class='tab_bg_2 center' colspan='10'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?items_id=$items_id&amp;itemtype=".
               "$itemtype\"><strong>".$LANG['joblist'][7]."</strong></a>";
         echo "</td></tr>";
      }

      // Ticket list
      if ($number > 0) {
         Ticket::commonListHeader(HTML_OUTPUT);

         while ($data=$DB->fetch_assoc($result)) {
            addToNavigateListItems('Ticket',$data["id"]);
            Ticket::showShort($data, 0);
         }
      }
      echo "</table></div><br>";

      // Tickets for linked items
      if ($subquery = $item->getSelectLinkedItem()) {
         $query = "SELECT ".Ticket::getCommonSelect()."
                  FROM `glpi_tickets` ".Ticket::getCommonLeftJoin()."
                  WHERE (`itemtype`,`items_id`) IN (" . $subquery . ")".
                        getEntitiesRestrictRequest(' AND ', 'glpi_tickets') . "
                  ORDER BY `glpi_tickets`.`date_mod` DESC
                  LIMIT ".intval($_SESSION['glpilist_limit']);
         $result = $DB->query($query);
         $number = $DB->numrows($result);

         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='10'>".$LANG['joblist'][28]."</th></tr>";
         if ($number > 0) {
            Ticket::commonListHeader(HTML_OUTPUT);

            while ($data=$DB->fetch_assoc($result)) {
               // addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               Ticket::showShort($data, 0);
            }
         } else {
            echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
         }
         echo "</table></div><br>";

      } // Subquery for linked item
   }

   static function showListForSupplier($entID) {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("show_all_ticket","1")) {
         return false;
      }

      $query = "SELECT ".Ticket::getCommonSelect()."
               FROM `glpi_tickets` ".Ticket::getCommonLeftJoin()."
               WHERE (`suppliers_id_assign` = '$entID') ".
                     getEntitiesRestrictRequest("AND","glpi_tickets")."
               ORDER BY `glpi_tickets`.`date_mod` DESC
               LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         $ent=new Supplier();
         $ent->getFromDB($entID);
         initNavigateListItems('Ticket',$LANG['financial'][26]." = ".$ent->fields['name']);


         $options['field'][0]      = 6;
         $options['searchtype'][0] = 'equals';
         $options['contains'][0]   = $entID;
         $options['link'][0]        = 'AND';

         $options['reset']='reset';

         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;:&nbsp;";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."'>".
                           $LANG['buttons'][40]."</a>";
         echo "</th></tr>";

         Ticket::commonListHeader(HTML_OUTPUT);

         while ($data=$DB->fetch_assoc($result)) {
            addToNavigateListItems('Ticket',$data["id"]);
            Ticket::showShort($data, 0);
         }
         echo "</table></div>";
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
         echo "</table>";
         echo "</div><br>";
      }
   }

   static function showListForUser($userID) {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("show_all_ticket","1")) {
         return false;
      }

      $query = "SELECT ".Ticket::getCommonSelect()."
               FROM `glpi_tickets` ".Ticket::getCommonLeftJoin()."
               WHERE (`glpi_tickets`.`users_id` = '$userID') ".
                     getEntitiesRestrictRequest("AND","glpi_tickets")."
               ORDER BY `glpi_tickets`.`date_mod` DESC
               LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         $user=new User();
         $user->getFromDB($userID);
         initNavigateListItems('Ticket',$LANG['common'][34]." = ".$user->getName());

         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;: &nbsp;";

         $options['reset']  = 'reset';
         $options['field'][0]      = 4; // status
         $options['searchtype'][0] = 'equals';
         $options['contains'][0]   = $userID;
         $options['link'][0]        = 'AND';

         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($options)."'>".$LANG['buttons'][40]."</a>";
         echo "</th></tr>";

         Ticket::commonListHeader(HTML_OUTPUT);

         while ($data=$DB->fetch_assoc($result)) {
            addToNavigateListItems('Ticket',$data["id"]);
            Ticket::showShort($data, 0);
         }
         echo "</table></div>";
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
         echo "</table>";
         echo "</div><br>";
      }
   }


   static function showShort($data, $followups,$output_type=HTML_OUTPUT,$row_num=0) {
      global $CFG_GLPI, $LANG;

      $rand=mt_rand();

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $job=new Ticket();
      $job->fields = $data;
      $candelete = haveRight("delete_ticket","1");
      $canupdate = haveRight("update_ticket","1");
      $align = "class='center";
      $align_desc = "class='left";
      if ($followups) {
         $align .= " top'";
         $align_desc .= " top'";
      } else {
         $align .= "'";
         $align_desc .= "'";
      }
      if ($data["id"]) {
         $item_num=1;
         $bgcolor=$_SESSION["glpipriority_".$data["priority"]];

         echo Search::showNewLine($output_type,$row_num%2);

         // First column
         $first_col = "ID : ".$data["id"];
         if ($output_type==HTML_OUTPUT) {
            $first_col .= "<br><img src=\"".$CFG_GLPI["root_doc"]."/pics/".$data["status"].".png\"
                           alt='".Ticket::getStatus($data["status"])."' title='".
                           Ticket::getStatus($data["status"])."'>";
         } else {
            $first_col .= " - ".Ticket::getStatus($data["status"]);
         }
         if (($candelete || $canupdate)
            && $output_type==HTML_OUTPUT) {

            $sel = "";
            if (isset($_GET["select"]) && $_GET["select"]=="all") {
               $sel = "checked";
            }
            if (isset($_SESSION['glpimassiveactionselected'][$data["id"]])) {
               $sel = "checked";
            }
            $first_col .= "&nbsp;<input type='checkbox' name='item[".$data["id"]."]' value='1' $sel>";
         }

         echo Search::showItem($output_type,$first_col,$item_num,$row_num,$align);

         // Second column
         $second_col = "";
         if (!strstr($data["status"],"old_")) {
            $second_col .= "<span class='tracking_open'>".$LANG['joblist'][11]."&nbsp;:";
            if ($output_type==HTML_OUTPUT) {
               $second_col .= "<br>";
            }
            $second_col .= "&nbsp;".convDateTime($data["date"])."</span>";
         } else {
            $second_col .= "<div class='tracking_hour'>";
            $second_col .= "".$LANG['joblist'][11]."&nbsp;:";
            if ($output_type==HTML_OUTPUT) {
               $second_col .= "<br>";
            }
            $second_col .= "&nbsp;<span class='tracking_bold'>".convDateTime($data["date"])."</span><br>";
            $second_col .= "".$LANG['joblist'][12]."&nbsp;:";
            if ($output_type==HTML_OUTPUT) {
               $second_col .= "<br>";
            }
            $second_col .= "&nbsp;<span class='tracking_bold'>".convDateTime($data["closedate"]).
                           "</span><br>";
            if ($data["realtime"]>0) {
               $second_col .= $LANG['job'][20]."&nbsp;: ";
            }
            if ($output_type==HTML_OUTPUT) {
               $second_col .= "<br>";
            }
            $second_col .= "&nbsp;".Ticket::getRealtime($data["realtime"]);
            $second_col .= "</div>";
         }

         echo Search::showItem($output_type,$second_col,$item_num,$row_num,$align." width=130");

         // Second BIS column
         $second_col = convDateTime($data["date_mod"]);
         echo Search::showItem($output_type,$second_col,$item_num,$row_num,$align." width=90");

         // Second TER column
         if (count($_SESSION["glpiactiveentities"])>1) {
            if ($data['entityID']==0) {
               $second_col = $LANG['entity'][2];
            } else {
               $second_col = $data['entityname'];
            }
            echo Search::showItem($output_type,$second_col,$item_num,$row_num,$align." width=100");
         }

         // Third Column
         echo Search::showItem($output_type,"<strong>".Ticket::getPriorityName($data["priority"])."</strong>",
                              $item_num,$row_num,"$align bgcolor='$bgcolor'");

         // Fourth Column
         $fourth_col = "";
         if ($data['users_id']) {
            $userdata = getUserName($data['users_id'],2);
            $fourth_col .= "<strong>".$userdata['name']."</strong>&nbsp;";
            if ($output_type==HTML_OUTPUT) {
               $fourth_col .= showToolTip($userdata["comment"],array('link'=>$userdata["link"],'display'=>false));
            }

         }

         if ($data["groups_id"]) {
            $fourth_col .= "<br>".$data["groupname"];
         }
         echo Search::showItem($output_type,$fourth_col,$item_num,$row_num,$align);

         // Fifth column
         $fifth_col = "";
         if ($data["users_id_assign"]>0) {
            $userdata = getUserName($data['users_id_assign'],2);
            $comment_display = "";
            $fifth_col = "<strong>".$userdata['name']."</strong>&nbsp;";
            $fifth_col .= showToolTip($userdata["comment"],array('link'=>$userdata["link"],'display'=>false));
         }

         if ($data["groups_id_assign"]>0) {
            if (!empty($fifth_col)) {
               $fifth_col .= "<br>";
            }
            $fifth_col .= Ticket::getAssignName($data["groups_id_assign"],'Group',1);
         }

         if ($data["suppliers_id_assign"]>0) {
            if (!empty($fifth_col)) {
               $fifth_col .= "<br>";
            }
            $fifth_col .= Ticket::getAssignName($data["suppliers_id_assign"],'Supplier',1);
         }
         echo Search::showItem($output_type,$fifth_col,$item_num,$row_num,$align);

         // Sixth Colum
         $sixth_col = "";
         $is_deleted = false;
         if (!empty($data["itemtype"]) && $data["items_id"]>0) {
            if (class_exists($data["itemtype"])) {
               $item=new $data["itemtype"]();
               if ($item->getFromDB($data["items_id"])) {
                  $is_deleted=$item->isDeleted();

                  $sixth_col .= $item->getTypeName();

                  $sixth_col .= "<br><strong>";
                  if ($item->canView()) {
                     $sixth_col .= $item->getLink($output_type==HTML_OUTPUT);
                  } else {
                     $sixth_col .= $item->getNameID();
                  }
                  $sixth_col .= "</strong>";
               }
            }
         } else if (empty($data["itemtype"])) {
            $sixth_col=$LANG['help'][30];
         }

         echo Search::showItem($output_type,$sixth_col,$item_num,$row_num,
                              ($is_deleted?" class='center deleted' ":$align));

         // Seventh column
         echo Search::showItem($output_type,"<strong>".$data["catname"]."</strong>",$item_num,
                              $row_num,$align);

         // Eigth column
         $eigth_column = "<strong>".$data["name"]."</strong>&nbsp;";

         // Add link
         if ($job->canViewItem()) {
            $eigth_column = "<a id='ticket".$data["id"]."$rand' href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
                              $data["id"]."\">$eigth_column</a>";

            if ($followups && $output_type==HTML_OUTPUT) {
               $eigth_column .= TicketFollowup::showShortForTicket($data["id"]);
            } else {
               $eigth_column .= "&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket",
                                                                           "1")).")";
            }
         }

         if ($output_type==HTML_OUTPUT) {
            $eigth_column .= "&nbsp;".showToolTip($data['content'],
                  array('display'=>false,'applyto'=>"ticket".$data["id"].$rand));
         }

         echo Search::showItem($output_type,$eigth_column,$item_num,$row_num,$align_desc."width='300'");

         // Finish Line
         echo Search::showEndLine($output_type);

      } else {
         echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG['joblist'][16]."</i></td></tr>";
      }
   }

   static function showVeryShort($ID) {
      global $CFG_GLPI, $LANG;

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $viewusers = haveRight("user","r");
      $job=new Ticket;
      $rand= mt_rand();
      if ($job->getFromDBwithData($ID,0)) {
         $bgcolor = $_SESSION["glpipriority_".$job->fields["priority"]];
         $rand = mt_rand();
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' bgcolor='$bgcolor' >ID : ".$job->fields["id"]."</td>";
         echo "<td class='center'>";

         $userdata = getUserName($job->fields['users_id'],2);
         echo "<strong>".$userdata['name']."</strong>&nbsp;";
         if ($viewusers) {
            showToolTip($userdata["comment"],array('link'=>$userdata["link"]));
         }

         if ($job->fields["groups_id"]) {
            echo "<br>".Dropdown::getDropdownName("glpi_groups",$job->fields["groups_id"]);
         }
         echo "</td>";

         if ($job->hardwaredatas && $job->hardwaredatas->canView()) {
            echo "<td class='center";
            if ($job->hardwaredatas->isDeleted()) {
               echo " tab_bg_1_2";
            }
            echo "'>";
            echo $job->hardwaredatas->getTypeName()."<br>";
            echo "<strong>".$job->hardwaredatas->getLink()."</strong>";
            echo "</td>";
         } else if ($job->hardwaredatas) {
            echo "<td class='center' >".$job->hardwaredatas->getTypeName()."<br><strong>".
                  $job->hardwaredatas->getNameID()."</strong></td>";
         } else {
            echo "<td class='center' >".$LANG['help'][30]."</td>";
         }
         echo "<td>";

         echo "<a id='ticket".$job->fields["id"].$rand."' href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
                  $job->fields["id"]."\">";
         echo "<strong>".$job->fields["name"]."</strong>";
         echo "</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;";
         showToolTip($job->fields['content'],array('applyto'=>'ticket'.$job->fields["id"].$rand));

         echo "</td>";

         // Finish Line
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG['joblist'][16]."</i></td></tr>";
      }
   }

   static function getRealtime($realtime) {
      global $LANG;

      $output = "";
      $hour = floor($realtime);
      if ($hour>0) {
         $output .= $hour." ".$LANG['job'][21]." ";
      }
      $output .= round((($realtime-floor($realtime))*60))." ".$LANG['job'][22];
      return $output;
   }

   static function getCommonSelect() {

      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                     `glpi_tickets`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_tickets`.*,
                        `glpi_ticketcategories`.`completename` AS catname,
                        `glpi_groups`.`name` AS groupname
                        $SELECT";
   }


   static function getCommonLeftJoin() {

      $FROM = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $FROM .= " LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id` = `glpi_tickets`.`entities_id`) ";
      }

      return " LEFT JOIN `glpi_groups` ON (`glpi_tickets`.`groups_id` = `glpi_groups`.`id`)
               LEFT JOIN `glpi_ticketcategories`
                  ON (`glpi_tickets`.`ticketcategories_id` = `glpi_ticketcategories`.`id`)
               $FROM";
   }

   static function showPreviewAssignAction($output) {
      global $LANG,$CFG_GLPI;

      //If ticket is assign to an object, display this information first
      if (isset($output["entities_id"]) && isset($output["items_id"]) && isset($output["itemtype"])) {

         if (class_exists($output["itemtype"])) {
            $item = new $output["itemtype"]();
            if ($item->getFromDB($output["items_id"])) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>".$LANG['rulesengine'][48]."</td>";

               echo "<td>";
               echo $item->getLink(true);
               echo "</td>";
               echo "</tr>";
            }
         }

            //Clean output of unnecessary fields (already processed)
            unset($output["items_id"]);
            unset($output["itemtype"]);
      }
      unset($output["entities_id"]);
      return $output;
   }


   /**
   * Get all available types to which a ticket can be assigned
   *
   */
   static function getAllTypesForHelpdesk() {
      global $LANG, $PLUGIN_HOOKS, $CFG_GLPI;

      $types = array();

      //Types of the plugins (keep the plugin hook for right check)
      if (isset($PLUGIN_HOOKS['assign_to_ticket'])) {
         foreach ($PLUGIN_HOOKS['assign_to_ticket'] as $plugin => $value) {
            $types = doOneHook($plugin,'AssignToTicket',$types);
         }
      }

      //Types of the core (after the plugin for robustness)
      foreach($CFG_GLPI["helpdesk_types"] as $itemtype) {
         if (class_exists($itemtype)) {
            if (!isPluginItemType($itemtype) // No plugin here
               && in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
               $item = new $itemtype();
               $types[$itemtype] = $item->getTypeName();
            }
         }
      }
      ksort($types); // core type first... asort could be better ?

      return $types;
   }


   /**
   * Check if it's possible to assign ticket to a type (core or plugin)
   * @param $itemtype the object's type
   * @return true if ticket can be assign to this type, false if not
   */
   static function isPossibleToAssignType($itemtype) {
      global $PLUGIN_HOOKS;


      // Plugin case
      if (isPluginItemType($itemtype)){
         /// TODO maybe only check plugin of itemtype ?
         //If it's not a core's type, then check plugins
         $types = array();
         if (isset($PLUGIN_HOOKS['assign_to_ticket'])) {
            foreach ($PLUGIN_HOOKS['assign_to_ticket'] as $plugin => $value) {
               $types = doOneHook($plugin,'AssignToTicket',$types);
            }
            if (array_key_exists($itemtype,$types)) {
               return true;
            }
         }
      } else { // standard case
         if (in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            return true;
         }
      }

      return false;
   }

   static function getAssignName($ID,$itemtype,$link=0) {
      global $CFG_GLPI;

      switch ($itemtype) {
         case 'User' :
            if ($ID==0) {
               return "";
            }
            return getUserName($ID,$link);
            break;

         case 'Supplier' :
         case 'Group' :
            $item=new $itemtype();
            if ($item->getFromDB($ID)) {
               $before = "";
               $after = "";
               if ($link) {
                  return $item->getLink(1);
               }
               return $item->getNameID();
            }
            return "";
      }
   }

   /** Get users which have intervention assigned to  between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct users which have any intervention assigned to.
   */
   static function getUsedTechBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_tickets`.`users_id_assign`, `glpi_users`.`name` AS name,
                     `glpi_users`.`realname` AS realname, `glpi_users`.`firstname` AS firstname
               FROM `glpi_tickets`
               LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id_assign`) ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab=array();

      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["users_id_assign"];
            $tmp['link']=formatUserName($line["users_id_assign"],$line["name"],$line["realname"],
                                       $line["firstname"],1);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /** Get users which have followup assigned to  between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct users which have any followup assigned to.
   */
   static function getUsedTechFollowupBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_ticketfollowups`.`users_id` AS users_id,
                     `glpi_users`.`name` AS name, `glpi_users`.`realname` AS realname,
                     `glpi_users`.`firstname` AS firstname
               FROM `glpi_tickets`
               LEFT JOIN `glpi_ticketfollowups`
                        ON (`glpi_tickets`.`id` = `glpi_ticketfollowups`.`tickets_id`)
               LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_ticketfollowups`.`users_id`)".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .="     AND `glpi_ticketfollowups`.`users_id` <> '0'
                     AND `glpi_ticketfollowups`.`users_id` IS NOT NULL
               ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab=array();

      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["users_id"];
            $tmp['link']=formatUserName($line["users_id"],$line["name"],$line["realname"],
                                       $line["firstname"],1);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /** Get enterprises which have followup assigned to between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct enterprises which have any tickets assigned to.
   */
   static function getUsedSupplierBetween($date1='',$date2='') {
      global $DB,$CFG_GLPI;

      $query = "SELECT DISTINCT `glpi_tickets`.`suppliers_id_assign` AS suppliers_id_assign,
                     `glpi_suppliers`.`name` AS name
               FROM `glpi_tickets`
               LEFT JOIN `glpi_suppliers`
                     ON (`glpi_suppliers`.`id` = `glpi_tickets`.`suppliers_id_assign`) ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query.= " ORDER BY name";

      $tab=array();
      $result = $DB->query($query);
      if ($DB->numrows($result) >0) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp["id"]=$line["suppliers_id_assign"];
            $tmp["link"]="<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".
                           $line["suppliers_id_assign"]."'>";
            $tmp["link"].=$line["name"];
            $tmp["link"].="</a>";
            $tab[]=$tmp;
         }
      }
      return $tab;
   }


   /** Get users_ids of tickets between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct users_ids which have tickets
   */
   static function getUsedAuthorBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_tickets`.`users_id`, `glpi_users`.`name` AS name,
                     `glpi_users`.`realname` AS realname, `glpi_users`.`firstname` AS firstname
               FROM `glpi_tickets`
               INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`)".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["users_id"];
            $tmp['link']=formatUserName($line["users_id"],$line["name"],$line["realname"],
                                       $line["firstname"],1);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /** Get recipient of tickets between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct recipents which have tickets
   */
   static function getUsedRecipientBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_tickets`.`users_id_recipient`, `glpi_users`.`name` AS name,
                     `glpi_users`.`realname` AS realname, `glpi_users`.`firstname` AS firstname
               FROM `glpi_tickets`
               LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id_recipient`)".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["users_id_recipient"];
            $tmp['link']=formatUserName($line["users_id_recipient"],$line["name"],$line["realname"],
                                       $line["firstname"],1);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /** Get groups which have tickets between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct groups of tickets
   */
   static function getUsedGroupBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_groups`.`id`, `glpi_groups`.`name`
               FROM `glpi_tickets`
               LEFT JOIN `glpi_groups` ON (`glpi_tickets`.`groups_id` = `glpi_groups`.`id`)".
               getEntitiesRestrictRequest(" WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `glpi_groups`.`name`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["id"];
            $tmp['link']=$line["name"];
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /** Get groups assigned to tickets between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct groups assigned to a tickets
   */
   static function getUsedAssignGroupBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_groups`.`id`, `glpi_groups`.`name`
               FROM `glpi_tickets`
               LEFT JOIN `glpi_groups` ON (`glpi_tickets`.`groups_id_assign` = `glpi_groups`.`id`)".
               getEntitiesRestrictRequest(" WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `glpi_groups`.`name`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["id"];
            $tmp['link']=$line["name"];
            $tab[]=$tmp;
         }
      }
      return $tab;
   }
   /**
   * Get priorities of tickets between 2 dates
   *
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct priorities of tickets
   */
   static function getUsedPriorityBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `priority`
               FROM `glpi_tickets` ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `priority`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         $i = 0;
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["priority"];
            $tmp['link']=Ticket::getPriorityName($line["priority"]);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /**
   * Get urgencies of tickets between 2 dates
   *
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct priorities of tickets
   */
   static function getUsedUrgencyBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `urgency`
               FROM `glpi_tickets` ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `urgency`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         $i = 0;
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["urgency"];
            $tmp['link']=Ticket::getUrgencyName($line["urgency"]);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /**
   * Get impacts of tickets between 2 dates
   *
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct priorities of tickets
   */
   static function getUsedImpactBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `impact`
               FROM `glpi_tickets` ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `impact`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         $i = 0;
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["impact"];
            $tmp['link']=Ticket::getImpactName($line["impact"]);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }


   /**
   * Get request types of tickets between 2 dates
   *
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct request types of tickets
   */
   static function getUsedRequestTypeBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `requesttypes_id`
               FROM `glpi_tickets` ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `requesttypes_id`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["requesttypes_id"];
            $tmp['link']=Dropdown::getDropdownName('glpi_requesttypes',$line["requesttypes_id"]);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }

   /**
   * Get solution types of tickets between 2 dates
   *
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @return array contains the distinct request types of tickets
   */
   static function getUsedSolutionTypeBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `ticketsolutiontypes_id`
               FROM `glpi_tickets` ".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `ticketsolutiontypes_id`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line["ticketsolutiontypes_id"];
            $tmp['link']=Dropdown::getDropdownName('glpi_ticketsolutiontypes',$line["ticketsolutiontypes_id"]);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }


   /** Get recipient of tickets between 2 dates
   * @param $date1 date : begin date
   * @param $date2 date : end date
   * @param title : indicates if stat if by title (true) or type (false)
   * @return array contains the distinct recipents which have tickets
   */
   static function getUsedUserTitleOrTypeBetween($date1='',$date2='',$title=true) {
      global $DB;

      if ($title) {
         $table = "glpi_usertitles";
         $field = "usertitles_id";
      } else {
         $table = "glpi_usercategories";
         $field = "usercategories_id";
      }

      $query = "SELECT DISTINCT `glpi_users`.`$field`
               FROM `glpi_tickets`
               INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`)
               LEFT JOIN `$table` ON (`$table`.`id` = `glpi_users`.`$field`)".
               getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
         $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .=" ORDER BY `glpi_users`.`$field`";

      $result = $DB->query($query);
      $tab=array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']= $line[$field];
            $tmp['link']=Dropdown::getDropdownName($table,$line[$field]);
            $tab[]=$tmp;
         }
      }
      return $tab;
   }


   /**
    * Is the current user have right to approve solution of the current ticket ?
    *
    * @return boolean
    */
   function canApprove() {

      return ($this->fields["status"] == 'solved'
              && ($this->fields["users_id_recipient"] === getLoginUserID()
                 || $this->fields["users_id"] === getLoginUserID()
                 || (isset($_SESSION["glpigroups"])
                     && in_array($this->fields["groups_id"],$_SESSION['glpigroups']))));
   }

   /**
    * Give cron informations
    * @param $name : task's name
    *
    * @return arrray of informations
    *
    */
   static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case 'closeticket' :
            return array('description' => $LANG['crontask'][14]);
         case 'alertnotclosed' :
            return  array('description' => $LANG['crontask'][15]);
      }
      return array();
   }


   /**
    * Cron for ticket's automatic close
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
    *
    */
   static function cronCloseTicket($task) {
      global $DB;

      $ticket = new Ticket();

      // Recherche des entités
      $tot = 0;
      foreach (Entity::getEntitiesToNotify('autoclose_delay') as $entity => $delay) {
         $query = "SELECT *
                   FROM `glpi_tickets`
                   WHERE `entities_id` = '".$entity."'
                     AND `status` = 'solved'
                     AND ADDDATE(`date_mod`, INTERVAL ".$delay." DAY) < CURDATE()";
         $nb = 0;
         foreach ($DB->request($query) as $tick) {
            $ticket->update(array('id'     => $tick['id'],
                                  'status' => 'closed'));
            $nb++;
         }
         if ($nb) {
            $tot += $nb;
            $task->addVolume($nb);
            $task->log(Dropdown::getDropdownName('glpi_entities',$entity)." : $nb");
         }
      }

      return ($tot > 0);
   }

   /**
    * Cron for alert old tickets which are not solved
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
    *
    */
   static function cronAlertNotClosed($task) {
      global $DB,$CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }
      // Recherche des entités
      $tot = 0;

      foreach (Entity::getEntitiesToNotify('notclosed_delay') as $entity => $value) {
         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                        LEFT JOIN `glpi_alerts` ON (`glpi_tickets`.`id` = `glpi_alerts`.`items_id`
                                                     AND `glpi_alerts`.`itemtype` = 'Ticket'
                                                        AND `glpi_alerts`.`type`='".Alert::NOTCLOSED."')
                   WHERE  `glpi_tickets`.`entities_id` = '".$entity."'
                     AND  `glpi_tickets`.`status` IN ('new','assign','plan','waiting')
                        AND  `glpi_tickets`.`closedate` IS NULL
                           AND ADDDATE(`glpi_tickets`.`date`, INTERVAL ".$value." DAY) < CURDATE()
                              AND `glpi_alerts`.`date` IS NULL";
         $tickets = array();
         foreach ($DB->request($query) as $tick) {
            $tickets[] = $tick;
         }

         if (!empty($tickets)) {
            if (NotificationEvent::raiseEvent('alertnotclosed',
                                              new Ticket(),
                                              array('tickets'=>$tickets,
                                                    'entities_id'=>$entity))) {
               $alert=new Alert();
               $input["itemtype"] = 'Ticket';
               $input["type"]=Alert::NOTCLOSED;
               foreach ($tickets as $ticket) {
                  $input["items_id"]=$ticket['id'];
                  $alert->add($input);
                  unset($alert->fields['id']);
               }

               $tot += count($tickets);
               $task->addVolume(count($tickets));
               $task->log(Dropdown::getDropdownName('glpi_entities',$entity)." : ".count($tickets));
            }
         }
      }

      return ($tot > 0);
   }

}
?>
