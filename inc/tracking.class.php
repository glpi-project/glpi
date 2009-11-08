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

/// Tracking class
class Job extends CommonDBTM {


   // From CommonDBTM
   public $table = 'glpi_tickets';
   public $type = TRACKING_TYPE;
   public $entity_assign = true;

   // Specific ones
   /// Hardware datas used by getFromDBwithData
   var $hardwaredatas = array();
   /// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
   var $computerfound = 0;


   function defineTabs($ID,$withtemplate) {
      global $LANG,$CFG_GLPI;

      $job=new Job();
      $job->getFromDB($ID);

      $ong[1] = $LANG['job'][38]." ".$ID;
      if ($_SESSION["glpiactiveprofile"]["interface"]=="central") {
         if ($job->canAddFollowups()) {
            $ong[2]=$LANG['job'][29];
         }
      } else if (haveRight("comment_ticket","1")) {
         $ong[1] = $LANG['job'][38]." ".$ID;
         if (!strstr($job->fields["status"],"old_")
             && $job->fields["users_id"]==$_SESSION["glpiID"]) {
            $ong[2] = $LANG['job'][29];
         }
      }
      $ong[3] = $LANG['job'][47];
      $ong[4] = $LANG['jobresolution'][1];
      $ong['no_all_tab']=true;

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

      $m= new CommonItem;
      $this->computerfound = $m->getFromDB($this->fields["itemtype"],$this->fields["items_id"]);
      $this->hardwaredatas=$m;
   }


   function cleanDBonPurge($ID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_ticketsfollowups`
                WHERE `tickets_id` = '$ID'";
      $result=$DB->query($query);

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            $querydel = "DELETE
                         FROM `glpi_ticketsplannings`
                         WHERE `ticketsfollowups_id` = '".$data['id']."'";
            $DB->query($querydel);
         }
      }
      $query1 = "DELETE
                 FROM `glpi_ticketsfollowups`
                 WHERE `tickets_id` = '$ID'";
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
                   || ($input["users_id_assign"]!=$_SESSION["glpiID"])) {
                  unset($input["users_id_assign"]);
               }
            } else {
               // Can not steal or can steal and not assign to me
               if (!haveRight("steal_ticket","1")
                   || $input["users_id_assign"] != $_SESSION["glpiID"]) {
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
         // Manage assign and steal right
         if (isset($input["users_id_assign"])) {
            $ret["users_id_assign"]=$input["users_id_assign"];
         }
         if (isset($input["suppliers_id_assign"])) {
            $ret["suppliers_id_assign"]=$input["suppliers_id_assign"];
         }
         if (isset($input["groups_id_assign"])) {
            $ret["groups_id_assign"]=$input["groups_id_assign"];
         }

         // Can only update content if no followups already added
         $ret["id"]=$input["id"];
         if (isset($input["content"])) {
            $ret["content"]=$input["content"];
         }
         if (isset($input["name"])) {
            $ret["name"]=$input["name"];
         }
         $input=$ret;
      }

      // NEEDED ????
      if (isset($input["itemtype"]) && $input["itemtype"]==0 && !isset($input["items_id"])) {
         $input["items_id"]=0;
      }

      if (isset($input["items_id"])
          && $input["items_id"]>=0
          && isset($input["itemtype"])
          && $input["itemtype"]>=0) {

         if (isset($this->fields['groups_id']) && $this->fields['groups_id']) {
            $ci=new CommonItem;
            $ci->getFromDB($input["itemtype"],$input["items_id"]);
            if ($tmp=$ci->getField('groups_id')) {
               $input["groups_id"] = $tmp;
            }
         }
      } else if (isset($input["itemtype"]) && $input["itemtype"]==0) {
         $input["items_id"]=0;
      } else {
         unset($input["items_id"]);
         unset($input["itemtype"]);
      }

      // Add document if needed
      $this->getFromDB($input["id"]); // entities_id field required
      $docadded = $this->addFiles($input["id"]);
      if (count($docadded)>0) {
         $input["date_mod"]=$_SESSION["glpi_currenttime"];
         if ($CFG_GLPI["add_followup_on_update_ticket"]) {
            $input['_doc_added']=$docadded;
         }
      }

      if (isset($input["document"]) && $input["document"]>0) {
         $doc=new Document();
         if ($doc->getFromDB($input["document"])) {
            $docitem=new DocumentItem();
            if ($docitem->add(array('documents_id' => $input["document"],
                                    'itemtype' => $this->type,
                                    'items_id' => $input["id"]))) {
               // Force date_mod of tracking
               $input["date_mod"]=$_SESSION["glpi_currenttime"];
               $input['_doc_added'][]=$doc->fields["name"];
            }
         }
         unset($input["document"]);
      }

      // Old values for add followup in change
      if ($CFG_GLPI["add_followup_on_update_ticket"]) {
         $this->getFromDB($input["id"]);
         $input["_old_assign_name"] = getAssignName($this->fields["users_id_assign"],USER_TYPE);
         $input["_old_assign"]      = $this->fields["users_id_assign"];
         $input["_old_assign_supplier_name"]  = getAssignName($this->fields["suppliers_id_assign"],
                                                             ENTERPRISE_TYPE);
         $input["_old_groups_id_assign_name"] = getAssignName($this->fields["groups_id_assign"],
                                                              GROUP_TYPE);
         $input["_old_ticketscategories_id"]  = $this->fields["ticketscategories_id"];
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
      }
      return $input;
   }


   function pre_updateInDB($input,$updates,$oldvalues=array()) {
      global $LANG;

      if (((in_array("users_id_assign",$updates) && $input["users_id_assign"]>0)
           || (in_array("suppliers_id_assign",$updates) && $input["suppliers_id_assign"]>0)
           || (in_array("groups_id_assign",$updates) && $input["groups_id_assign"]>0)
          )
          &&$this->fields["status"]=="new") {

         $updates[]="status";
         $this->fields["status"]="assign";
      }
      if (isset($input["status"])) {
         if (isset($input["suppliers_id_assign"])
             && $input["suppliers_id_assign"]==0
             && isset($input["groups_id_assign"])
             && $input["groups_id_assign"]==0
             && isset($input["users_id_assign"])
             && $input["users_id_assign"]==0
             && $input["status"]=="assign") {

            $updates[]="status";
            $this->fields["status"]="new";
         }

         if (in_array("status",$updates) && strstr($input["status"],"old_")) {
            $updates[]="closedate";
            $oldvalues['closedate']=$this->fields["closedate"];
            $this->fields["closedate"]=$_SESSION["glpi_currenttime"];
            // If invalid date : set open date
            if ($this->fields["closedate"]<$this->fields["date"]){
               $this->fields["closedate"]=$this->fields["date"];
            }
         }
      }

      // Status close : check dates
      if (strstr($this->fields["status"],"old_")
          && (in_array("date",$updates) || in_array("closedate",$updates))) {

         // Invalid dates : no change
         if ($this->fields["closedate"]<$this->fields["date"]) {
            addMessageAfterRedirect($LANG['tracking'][3],false,ERROR);
            if (($key=array_search('date',$updates))!==false) {
               unset($updates[$key]);
            }
            if (($key=array_search('closedate',$updates))!==false) {
               unset($updates[$key]);
            }
         }
      }

      // Check dates change interval due to the fact that second are not displayed in form

      if (($key=array_search('date',$updates))!==false
          && (substr($this->fields["date"],0,16) == substr($oldvalues['date'],0,16))) {
         unset($updates[$key]);
      }
      if (($key=array_search('closedate',$updates))!==false
          && (substr($this->fields["closedate"],0,16) == substr($oldvalues['closedate'],0,16))) {
         unset($updates[$key]);
      }

      if (in_array("users_id",$updates)) {
         $user=new User;
         $user->getFromDB($input["users_id"]);
         if (!empty($user->fields["email"])) {
            $updates[]="user_email";
            $this->fields["user_email"]=$user->fields["email"];
         }
      }

      // Do not take into account date_mod if no update is done
      if (count($updates)==1 && ($key=array_search('date_mod',$updates))!==false) {
         unset($updates[$key]);
      }

      return array($input,$updates);
   }


   function post_updateItem($input,$updates,$history=1) {
      global $CFG_GLPI,$LANG;

      if (count($updates)) {
         // New values for add followup in change
         $change_followup_content="";
         if (isset($input['_doc_added']) && count($input['_doc_added'])>0) {
            foreach ($input['_doc_added'] as $name) {
               $change_followup_content .= $LANG['mailing'][26]." $name\n";
            }
         }
         $global_mail_change_count=0;

         // Update Ticket Tco
         if (in_array("realtime",$updates)
             || in_array("cost_time",$updates)
             || in_array("cost_fixed",$updates)
             || in_array("cost_material",$updates)) {

            $ci=new CommonItem;
            if ($ci->getFromDB($this->fields["itemtype"],$this->fields["items_id"])) {
               $newinput=array();
               $newinput['id']=$this->fields["items_id"];
               $newinput['ticket_tco'] = computeTicketTco($this->fields["itemtype"],
                                                          $this->fields["items_id"]);
               $ci->obj->update($newinput);
            }
         }

         if ($CFG_GLPI["add_followup_on_update_ticket"] && count($updates)) {
            foreach ($updates as $key) {
               switch ($key) {
                  case "name" :
                     $change_followup_content .= $LANG['mailing'][45]."\n";
                     $global_mail_change_count++;
                     break;

                  case "content" :
                     $change_followup_content .= $LANG['mailing'][46]."\n";
                     $global_mail_change_count++;
                     break;

                  case "date" :
                     $change_followup_content .= $LANG['mailing'][48]."&nbsp;: ".
                                                 $input["_old_date"]." -> ".$this->fields["date"]."\n";
                     $global_mail_change_count++;
                     break;

                  case "closedate" :
                     // if update status from an not closed status : no mail for change closedate
                     if (!in_array("status",$updates) || !strstr($input["status"],"old_")) {
                        $change_followup_content .= $LANG['mailing'][49]."&nbsp;: ".
                                                    $input["_old_closedate"]." -> ".
                                                    $this->fields["closedate"]."\n";
                        $global_mail_change_count++;
                     }
                     break;

                  case "status" :
                     $new_status=$this->fields["status"];
                     $change_followup_content .= $LANG['mailing'][27]."&nbsp;: ".
                                                 getStatusName($input["_old_status"])." -> ".
                                                 getStatusName($new_status)."\n";
                     if (strstr($new_status,"old_")) {
                        $newinput["add_close"]="add_close";
                     }
                     if (in_array("closedate",$updates)) {
                        $global_mail_change_count++; // Manage closedate
                     }
                     $global_mail_change_count++;
                     break;

                  case "users_id" :
                     $users_id=new User;
                     $users_id->getFromDB($input["_old_users_id"]);
                     $old_users_id_name = $users_id->getName();
                     $users_id->getFromDB($this->fields["users_id"]);
                     $new_users_id_name = $users_id->getName();
                     $change_followup_content .= $LANG['mailing'][18]."&nbsp;: $old_users_id_name -> ".
                                                 $new_users_id_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "users_id_recipient" :
                     $recipient=new User;
                     $recipient->getFromDB($input["_old_recipient"]);
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
                                                   getDropdownName("glpi_groups",$input["_old_group"]));
                     $new_group_name = str_replace("&nbsp;",$LANG['mailing'][109],
                                                   getDropdownName("glpi_groups",$new_group));
                     $change_followup_content .= $LANG['mailing'][20].": ".$old_group_name." -> ".
                                                 $new_group_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "priority" :
                     $new_priority = $this->fields["priority"];
                     $change_followup_content .= $LANG['mailing'][15]."&nbsp;: ".
                                                 getPriorityName($input["_old_priority"])." -> ".
                                                 getPriorityName($new_priority)."\n";
                     $global_mail_change_count++;
                     break;

                  case "ticketscategories_id" :
                     $new_ticketscategories_id = $this->fields["ticketscategories_id"];
                     $old_category_name = str_replace("&nbsp;",$LANG['mailing'][100],
                                                      getDropdownName("glpi_ticketscategories",
                                                                      $input["_old_ticketscategories_id"]));
                     $new_category_name = str_replace("&nbsp;",$LANG['mailing'][100],
                                                      getDropdownName("glpi_ticketscategories",
                                                                      $new_ticketscategories_id));
                     $change_followup_content .= $LANG['mailing'][14]."&nbsp;: ".
                                                 $old_category_name." -> ".$new_category_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "requesttypes_id" :
                     $old_requesttype_name = getDropdownName('glpi_requesttypes',
                                                             $input["_old_requesttypes_id"]);
                     $new_requesttype_name = getDropdownName('glpi_requesttypes',
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
                     $ci=new CommonItem;
                     $ci->getFromDB($input["_old_itemtype"],$input["_old_items_id"]);
                     $old_item_name = $ci->getName();
                     if ($old_item_name=="N/A" || empty($old_item_name)) {
                        $old_item_name = $LANG['mailing'][107];
                     }
                     $ci->getFromDB($this->fields["itemtype"],$this->fields["items_id"]);
                     $new_item_name = $ci->getName();
                     if ($new_item_name=="N/A" || empty($new_item_name)) {
                        $new_item_name=$LANG['mailing'][107];
                     }
                     $change_followup_content .= $LANG['mailing'][17]."&nbsp;:
                                                 $old_item_name -> ".$new_item_name."\n";
                     if (in_array("items_id",$updates)) {
                        $global_mail_change_count++;
                     }
                     if (in_array("itemtype",$updates)) {
                        $global_mail_change_count++;
                     }
                     break;

                  case "users_id_assign" :
                     $new_assign_name = getAssignName($this->fields["users_id_assign"],USER_TYPE);
                     if ($input["_old_assign"]==0) {
                        $input["_old_assign_name"]=$LANG['mailing'][105];
                     }
                     $change_followup_content .= $LANG['mailing'][12]."&nbsp;: ".
                                                 $input["_old_assign_name"]." -> ".
                                                 $new_assign_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "suppliers_id_assign" :
                     $new_assign_supplier_name = getAssignName($this->fields["suppliers_id_assign"],
                                                               ENTERPRISE_TYPE);
                     $change_followup_content .= $LANG['mailing'][12]."&nbsp;: ".
                                                 $input["_old_assign_supplier_name"]." -> ".
                                                 $new_assign_supplier_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "groups_id_assign" :
                     $new_groups_id_assign_name = getAssignName($this->fields["groups_id_assign"],
                                                                GROUP_TYPE);
                     $change_followup_content .= $LANG['mailing'][12]."&nbsp;: ".
                                                 $input["_old_groups_id_assign_name"]." -> ".
                                                 $new_groups_id_assign_name."\n";
                     $global_mail_change_count++;
                     break;

                  case "cost_time" :
                     $change_followup_content .= $LANG['mailing'][42]."&nbsp;: ".
                                                 formatNumber($input["_old_cost_time"])." -> ".
                                                 formatNumber($this->fields["cost_time"])."\n";
                     $global_mail_change_count++;
                     break;

                  case "cost_fixed" :
                     $change_followup_content .= $LANG['mailing'][43]."&nbsp;: ".
                                                 formatNumber($input["_old_cost_fixed"])." -> ".
                                                 formatNumber($this->fields["cost_fixed"])."\n";
                     $global_mail_change_count++;
                     break;

                  case "cost_material" :
                     $change_followup_content .= $LANG['mailing'][44]."&nbsp;: ".
                                                 formatNumber($input["_old_cost_material"])." -> ".
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
         if (!in_array("users_id_assign",$updates)) {
            unset($input["_old_assign"]);
         }
         $mail_send=false;

         if (!empty($change_followup_content)) { // Add followup if not empty
            $newinput=array();
            $newinput["content"]    = addslashes($change_followup_content);
            $newinput["users_id"]   = $_SESSION['glpiID'];
            $newinput["is_private"] = 0;
            $newinput["hour"]       = $newinput["minute"] = 0;
            $newinput["tickets_id"] = $this->fields["id"];
            $newinput["type"]       = "update";
            $newinput["_do_not_check_users_id"] = true;
            // pass _old_assign if assig changed
            if (isset($input["_old_assign"])) {
               $newinput["_old_assign"] = $input["_old_assign"];
            }

            if (isset($input["status"])
                && in_array("status",$updates)
                && strstr($input["status"],"old_")) {

               $newinput["type"]="finish";
            }
            $fup=new Followup();
            $fup->add($newinput);
            $mail_send=true;
         }

         // Clean content to mail
         $this->fields["content"] = stripslashes($this->fields["content"]);

         if (!$mail_send
             && count($updates)>$global_mail_change_count
             && $CFG_GLPI["use_mailing"]) {

            $user=new User;
            $user->getFromDB($_SESSION["glpiID"]);
            $mailtype = "update";

            if (isset($input["status"])
                && $input["status"]
                && in_array("status",$updates)
                && strstr($input["status"],"old_")) {

               $mailtype="finish";
            }
            if (isset($input["_old_assign"])) {
               $this->fields["_old_assign"] = $input["_old_assign"];
            }
            $mail = new Mailing($mailtype,$this,$user);
            $mail->send();
         }
      }
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI,$LANG;

      // Check mandatory
      $mandatory_ok=true;

      // Do not check mandatory on auto import (mailgates)
      if (!isset($input['_auto_import'])) {
         $_SESSION["helpdeskSaved"]=$input;

         if (!isset($input["priority"])) {
            addMessageAfterRedirect($LANG['tracking'][4],false,ERROR);
            $mandatory_ok=false;
         }
         if ($CFG_GLPI["is_ticket_content_mandatory"]
             && (!isset($input['content']) || empty($input['content']))) {

            addMessageAfterRedirect($LANG['tracking'][8],false,ERROR);
            $mandatory_ok=false;
         }
         if ($CFG_GLPI["is_ticket_title_mandatory"]
             && (!isset($input['name']) || empty($input['name']))) {

            addMessageAfterRedirect($LANG['help'][40],false,ERROR);
            $mandatory_ok=false;
         }
         if ($CFG_GLPI["is_ticket_category_mandatory"]
             && (!isset($input['ticketscategories_id']) || empty($input['ticketscategories_id']))) {

            addMessageAfterRedirect($LANG['help'][41],false,ERROR);
            $mandatory_ok=false;
         }
         if (isset($input['use_email_notification']) && $input['use_email_notification']
             && (!isset($input['user_email']) || empty($input['user_email']))) {

            addMessageAfterRedirect($LANG['help'][16],false,ERROR);
            $mandatory_ok=false;
         }

         if (!$mandatory_ok) {
            return false;
         }
      }
      unset($_SESSION["helpdeskSaved"]);

      // Manage helpdesk.html submission type
      unset($input["type"]);

      // No Auto set Import for external source
      if (!isset($input['_auto_import'])) {
         if (!isset($input["users_id"])) {
            if (isset($_SESSION["glpiID"]) && $_SESSION["glpiID"]>0) {
               $input["users_id"] = $_SESSION["glpiID"];
            }
         }
      }

      // No Auto set Import for external source
      if (isset($_SESSION["glpiID"]) && !isset($input['_auto_import'])) {
         $input["users_id_recipient"] = $_SESSION["glpiID"];
      } else if ($input["users_id"]) {
         $input["users_id_recipient"] = $input["users_id"];
      }
      if (!isset($input["requesttypes_id"])) {
         $input["requesttypes_id"]=RequestType::getDefault('helpdesk');
      }
      if (!isset($input["status"])) {
         $input["status"]="new";
      }
      if (!isset($input["date"]) || empty($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }

      // Set default dropdown
      $dropdown_fields = array('entities_id','groups_id','groups_id_assign', 'itemtype','items_id',
                               'users_id','users_id_assign', 'suppliers_id_assign',
                               'ticketscategories_id');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field]=0;
         }
      }
      // Auto group define from item
      if ($input["items_id"]>0 && $input["itemtype"]>0) {
         $ci=new CommonItem;
         $ci->getFromDB($input["itemtype"],$input["items_id"]);
         if ($tmp=$ci->getField('groups_id')) {
            $input["groups_id"] = $tmp;
         }
      }

      if ($CFG_GLPI["use_auto_assign_to_tech"]) {

         // Auto assign tech from item
         if ($input["users_id_assign"]==0 && $input["items_id"]>0 && $input["itemtype"]>0) {

            $ci = new CommonItem();
            $ci->getFromDB($input["itemtype"],$input["items_id"]);
            if ($tmp=$ci->getField('users_id_tech')) {
               $input["users_id_assign"] = $tmp;
               if ($input["users_id_assign"]>0) {
                  $input["status"] = "assign";
               }
            }
         }

         // Auto assign tech/group from Category
         if ($input['ticketscategories_id']>0
             && (!$input['users_id_assign'] || !$input['groups_id_assign'])) {

            $cat = new TicketCategory();
            $cat->getFromDB($input['ticketscategories_id']);
            if (!$input['users_id_assign'] && $tmp=$cat->getField('users_id')) {
               $input['users_id_assign'] = $tmp;
            }
            if (!$input['groups_id_assign'] && $tmp=$cat->getField('groups_id')) {
               $input['groups_id_assign'] = $tmp;
            }
         }
      }

      // Process Business Rules
      $rules=new TrackingBusinessRuleCollection();

      // Set unset variables with are needed
      $user=new User();
      if ($user->getFromDB($input["users_id"])) {
         $input['users_locations']=$user->fields['locations_id'];
      }


      $input=$rules->processAllRules($input,$input);

      if (isset($input["use_email_notification"])
          && $input["use_email_notification"]
          && empty($input["user_email"])) {

         $user=new User();
         $user->getFromDB($input["users_id"]);
         $input["user_email"] = $user->fields["email"];
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

      if (isset($input["status"]) && strstr($input["status"],"old_")) {
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


   function post_addItem($newID,$input) {
      global $LANG,$CFG_GLPI;

      // Add document if needed
      $this->addFiles($newID);

      // Log this event
      logEvent($newID,"tracking",4,"tracking",getUserName($input["users_id"])." ".$LANG['log'][20]);

      $already_mail=false;
      if (((isset($input["_followup"])
            && is_array($input["_followup"])
            && strlen($input["_followup"]['content']) > 0
           )
           || isset($input["plan"])
          )
          || (isset($input["_hour"])
              && isset($input["_minute"])
              && isset($input["realtime"])
              && $input["realtime"]>0)) {

         $fup=new Followup();
         $type="new";
         if (isset($this->fields["status"]) && strstr($this->fields["status"],"old_")) {
            $type="finish";
         }
         $toadd = array("type"      => $type,
                        "tickets_id"=> $newID);
         if (isset($input["_hour"])) {
            $toadd["hour"] = $input["_hour"];
         }
         if (isset($input["_minute"])) {
            $toadd["minute"] = $input["_minute"];
         }
         if (isset($input["_followup"]['content']) && strlen($input["_followup"]['content']) > 0) {
            $toadd["content"] = $input["_followup"]['content'];
         }
         if (isset($input["_followup"]['is_private'])) {
            $toadd["is_private"] = $input["_followup"]['is_private'];
         }
         if (isset($input["plan"])) {
            $toadd["plan"] = $input["plan"];
         }
         $fup->add($toadd);
         $already_mail=true;
      }

      // Processing Email
      if ($CFG_GLPI["use_mailing"] && !$already_mail) {
         $user = new User();
         $user->getFromDB($input["users_id"]);
         // Clean reload of the ticket
         $this->getFromDB($newID);

         $type = "new";
         if (isset($this->fields["status"]) && strstr($this->fields["status"],"old_")) {
            $type = "finish";
         }
         $mail = new Mailing($type,$this,$user);
         $mail->send();
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
                FROM `glpi_ticketsfollowups`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result,0,0);
   }


   /**
    * Update realtime of the ticket based on realtim of the followups
    *
    *@param $ID ID of the ticket
    *@return boolean : success
   **/
   function updateRealTime($ID) {
      global $DB;

      // update Status of Job
      $query = "SELECT SUM(`realtime`)
                FROM `glpi_ticketsfollowups`
                WHERE `tickets_id` = '$ID'";

      if ($result = $DB->query($query)) {
         $sum = $DB->result($result,0,0);
         if (is_null($sum)) {
            $sum=0;
         }
         $query2 = "UPDATE `".
                    $this->table."`
                    SET `realtime` = '$sum'
                    WHERE `id` = '$ID'";
         $DB->query($query2);
         return true;
      }
      return false;
   }


   /**
    * Update date mod of the ticket
    *
    *@param $ID ID of the ticket
   **/
   function updateDateMod($ID) {
      global $DB;

      $query = "UPDATE `".
                $this->table."`
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
                   FROM `glpi_ticketsfollowups`
                   WHERE `tickets_id` = '".$this->fields["id"]."' ".
                         ($sendprivate?"":" AND `is_private` = '0' ")."
                   ORDER by `date` DESC";
         $result=$DB->query($query);
         $nbfollow=$DB->numrows($result);

         if ($format=="html") {
            $message = "<div class='description b'>".$LANG['mailing'][4]."&nbsp;: $nbfollow<br></div>\n";

            if ($nbfollow>0) {
               $fup = new Followup();
               while ($data=$DB->fetch_array($result)) {
                  $fup->getFromDB($data['id']);
                  $message .= "<strong>[ ".convDateTime($fup->fields["date"])." ] ".
                               ($fup->fields["is_private"]?"<i>".$LANG['common'][77]."</i>":"").
                               "</strong>\n";
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['job'][4].":</span> ".
                               $fup->getAuthorName()."\n";
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['mailing'][3]."</span>:<br>".
                               str_replace("\n","<br>",$fup->fields["content"])."\n";
                  if ($fup->fields["realtime"]>0) {
                     $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                                  "text-decoration:underline; '>".$LANG['mailing'][104].":</span> ".
                                  getRealtime($fup->fields["realtime"])."\n";
                  }
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['mailing'][25]."</span> ";

                  $query2 = "SELECT *
                             FROM `glpi_ticketsplannings`
                             WHERE `ticketsfollowups_id` = '".$data['id']."'";
                  $result2=$DB->query($query2);

                  if ($DB->numrows($result2)==0) {
                     $message .= $LANG['job'][32]."\n";
                  } else {
                     $data2 = $DB->fetch_array($result2);
                     $message .= convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
                  }
                  $message .= $LANG['mailing'][0]."\n";
               }
            }
         } else { // text format
            $message = $LANG['mailing'][1]."\n".$LANG['mailing'][4]."&nbsp;: $nbfollow\n".
                       $LANG['mailing'][1]."\n";

            if ($nbfollow>0) {
               $fup=new Followup();
               while ($data=$DB->fetch_array($result)) {
                  $fup->getFromDB($data['id']);
                  $message .= "[ ".convDateTime($fup->fields["date"])." ]".
                               ($fup->fields["is_private"]?"\t".$LANG['common'][77] :"")."\n";
                  $message .= $LANG['job'][4]."&nbsp;: ".$fup->getAuthorName()."\n";
                  $message .= $LANG['mailing'][3]."&nbsp;:\n".$fup->fields["content"]."\n";
                  if ($fup->fields["realtime"]>0) {
                     $message .= $LANG['mailing'][104]."&nbsp;: ".
                                 getRealtime($fup->fields["realtime"])."\n";
                  }
                  $message .= $LANG['mailing'][25]." ";

                  $query2 = "SELECT *
                             FROM `glpi_ticketsplannings`
                             WHERE `ticketsfollowups_id` = '".$data['id']."'";
                  $result2=$DB->query($query2);

                  if ($DB->numrows($result2)==0) {
                     $message .= $LANG['job'][32]."\n";
                  } else {
                     $data2 = $DB->fetch_array($result2);
                     $message .= convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
                  }
                  $message .= $LANG['mailing'][0]."\n";
               }
            }
         }
         return $message;
      } else {
         return "";
      }
   }


   /**
    * Get text describing ticket
    *
    * @param $format text or html
    */
   function textDescription($format="text") {
      global $DB,$LANG;

      $name = $LANG['help'][30];
      $contact = '';
      $tech = '';
      $name = $this->hardwaredatas->getType()." ".$this->hardwaredatas->getName();
      if ($this->hardwaredatas->obj!=NULL) {
         if (isset($this->hardwaredatas->obj->fields["serial"])
             && !empty($this->hardwaredatas->obj->fields["serial"])) {

            $name .= " - #".$this->hardwaredatas->obj->fields["serial"];
         }
         $modeltable = $this->hardwaredatas->obj->table."models";
         $modelfield = getForeignKeyFieldForTable($modeltable);
         if (isset($this->hardwaredatas->obj->fields[$modelfield])
             && $this->hardwaredatas->obj->fields[$modelfield]>0) {

            $name .= " - ".getDropdownName($modeltable,$this->hardwaredatas->obj->fields[$modelfield]);
         }
         if (isset($this->hardwaredatas->obj->fields["users_id_tech"])
             && $this->hardwaredatas->obj->fields["users_id_tech"]>0) {

            $tech = getUserName($this->hardwaredatas->obj->fields["users_id_tech"]);
         }
         if (isset($this->hardwaredatas->obj->fields["contact"])) {
            $contact = $this->hardwaredatas->obj->fields["contact"];
         }
         if (isset($this->hardwaredatas->obj->fields["users_id"])) {
            $contact = getUserName($this->hardwaredatas->obj->fields["users_id"]);
         }
         if (isset($this->hardwaredatas->obj->fields["groups_id"])) {
            if (!empty($contact)) {
               $contact.=" / ";
            }
            $contact .= getDropdownName("glpi_groups",$this->hardwaredatas->obj->fields["groups_id"]);
         }
      }

      if ($format=="html") {
         $message  = "<html><head> <style type='text/css'>";
         $message .= ".description{ color: inherit; background: #ebebeb; border-style: solid; ".
                                    "border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }";
         $message .= "</style></head><body>";

         $message .= "<div class='description b'>".$LANG['mailing'][5]."</div>\n";
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline; '>".
                      $LANG['common'][57]."&nbsp;:</span> ".$this->fields["name"]."\n";
         $users_id = $this->getAuthorName();
         if (empty($users_id)) {
            $users_id = $LANG['mailing'][108];
         }
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['job'][4]."&nbsp;:</span> ".$users_id."\n";
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['search'][8]."&nbsp;:</span> ".convDateTime($this->fields["date"])."\n";
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['job'][44]."&nbsp;:</span> ".
                      getDropdownName('glpi_requesttypes', $this->fields["requesttypes_id"])."\n";
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['mailing'][7]."&nbsp;:</span> ".$name."\n";
         if (!empty($tech)) {
            $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                         $LANG['common'][10]."&nbsp;:</span> ".$tech."\n";
         }
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['joblist'][0]."&nbsp;:</span> ".getStatusName($this->fields["status"])."\n";
         $assign = getAssignName($this->fields["users_id_assign"],USER_TYPE);
         $group_assign = "";
         if (isset($this->fields["groups_id_assign"])) {
            $group_assign = getAssignName($this->fields["groups_id_assign"],GROUP_TYPE);
         }
         if ($assign=="[Nobody]") {
            if (!empty($group_assign)) {
               $assign = $group_assign;
            } else {
               $assign = $LANG['mailing'][105];
            }
         } else if (!empty($group_assign)) {
            $assign .= " / ".$group_assign;
         }
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['mailing'][8]."&nbsp;:</span> ".$assign."\n";
         $message .="<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['joblist'][2].":</span> ".getPriorityName($this->fields["priority"])."\n";
         if ($this->fields["itemtype"]!=SOFTWARE_TYPE && !empty($contact)) {
            $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                         $LANG['common'][18]."&nbsp;:</span> ".$contact."\n";
         }
         if (isset($this->fields["use_email_notification"])
             && $this->fields["use_email_notification"]) {

            $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                         $LANG['mailing'][103]."&nbsp;:</span> ".$LANG['choice'][1]."\n";
         } else {
            $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                         $LANG['mailing'][103]."&nbsp;:</span> ".$LANG['choice'][0]."\n";
         }
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline;'>".
                      $LANG['common'][36]."&nbsp;:</span> ";

         if (isset($this->fields["ticketscategories_id"])
             && $this->fields["ticketscategories_id"]) {

            $message .= getDropdownName("glpi_ticketscategories",
                                        $this->fields["ticketscategories_id"]);
         } else {
            $message .= $LANG['mailing'][100];
         }
         $message .= "\n";
         $message .= "<span style='color:#8B8C8F; font-weight:bold; text-decoration:underline; '>".
                      $LANG['mailing'][3]."&nbsp;:</span><br>".
                      str_replace("\n","<br>",$this->fields["content"])."<br>\n";

      } else { //text format
         $message  = $LANG['mailing'][1]."\n*".$LANG['mailing'][5]."*\n".$LANG['mailing'][1]."\n";
         $message .= mailRow($LANG['common'][57],$this->fields["name"]);
         $users_id = $this->getAuthorName();
         if (empty($users_id)) {
            $users_id = $LANG['mailing'][108];
         }
         $message .= mailRow($LANG['job'][4],$users_id);
         $message .= mailRow($LANG['search'][8],convDateTime($this->fields["date"]));
         $message .= mailRow($LANG['job'][44],getDropdownName('glpi_requesttypes',
                                                              $this->fields["requesttypes_id"]));
         $message .= mailRow($LANG['mailing'][7],$name);
         if (!empty($tech)) {
            $message .= mailRow($LANG['common'][10],$tech);
         }
         $message .= mailRow($LANG['joblist'][0],getStatusName($this->fields["status"]));
         $assign = getAssignName($this->fields["users_id_assign"],USER_TYPE);
         $group_assign = "";
         if (isset($this->fields["groups_id_assign"])) {
            $group_assign = getAssignName($this->fields["groups_id_assign"],GROUP_TYPE);
         }
         if ($assign=="[Nobody]") {
            if (!empty($groups_id_assign)) {
               $assign=$group_assign;
            } else {
               $assign=$LANG['mailing'][105];
            }
         } else if (!empty($groups_id_assign)) {
           $assign.=" / ".$group_assign;
         }

         $message .= mailRow($LANG['mailing'][8],$assign);
         $message .= mailRow($LANG['joblist'][2],getPriorityName($this->fields["priority"]));
         if ($this->fields["itemtype"]!=SOFTWARE_TYPE && !empty($contact)) {
            $message .= mailRow($LANG['common'][18],$contact);
         }
         if (isset($this->fields["use_email_notification"])
             && $this->fields["use_email_notification"]) {
            $message .= mailRow($LANG['mailing'][103],$LANG['choice'][1]);
         } else {
            $message .= mailRow($LANG['mailing'][103],$LANG['choice'][0]);
         }

         if (isset($this->fields["ticketscategories_id"])
             && $this->fields["ticketscategories_id"]) {

            $message .= mailRow($LANG['common'][36],
                                getDropdownName("glpi_ticketscategories",
                                                $this->fields["ticketscategories_id"]));
         } else {
            $message .= mailRow($LANG['common'][36],$LANG['mailing'][100]);
         }
         $message .= "--\n";
         $message .= $LANG['mailing'][3]."&nbsp;: \n".$this->fields["content"]."\n";
         $message .= "\n\n";

      }
      return $message;
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

      return ((haveRight("comment_ticket","1") && $this->fields["users_id"]==$_SESSION["glpiID"])
              || haveRight("comment_all_ticket","1")
              || (isset($_SESSION["glpiID"])
                  && $this->fields["users_id_assign"]==$_SESSION["glpiID"])
              || (isset($_SESSION["glpigroups"])
                  && in_array($this->fields["groups_id_assign"],$_SESSION['glpigroups'])));
   }


   /**
    * Is the current user have right to show the current ticket ?
    *
    * @return boolean
    */
   function canView() {

      return (haveRight("show_all_ticket","1")
              || (isset($_SESSION["glpiID"]) && $this->fields["users_id"]==$_SESSION["glpiID"])
              || (haveRight("show_group_ticket",'1')
                  && isset($_SESSION["glpigroups"])
                  && in_array($this->fields["groups_id"],$_SESSION["glpigroups"]))
              || (haveRight("show_assign_ticket",'1')
                  && ((isset($_SESSION["glpiID"])
                       && $this->fields["users_id_assign"]==$_SESSION["glpiID"]
                      )
                      || (isset($_SESSION["glpigroups"])
                          && in_array($this->fields["groups_id_assign"],$_SESSION["glpigroups"]))
                     )
                 )
             );
   }


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
      $docadded=array();
      $doc=new Document();
      $docitem=new DocumentItem();

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
               $input2=array();
               $input2["name"]         = addslashes($LANG['tracking'][24]." $id");
               $input2["tickets_id"]   = $id;
               $input2["entities_id"]  = $this->fields["entities_id"];
               $input2["documentscategories_id"]   = $CFG_GLPI["documentscategories_id_forticket"];
               $input2["_only_if_upload_succeed"]  = 1;
               $input2["entities_id"]  = $this->fields["entities_id"];
               $docID = $doc->add($input2);
            }
            if ($docID>0) {
               if ($docitem->add(array('documents_id' => $docID,
                                       'itemtype' => $this->type,
                                       'items_id' => $id))) {
                  $docadded[]=stripslashes($doc->fields["name"]);
               }
            }

         } else if (!empty($_FILES['filename']['name'])
                    && isset($_FILES['filename']['error'])
                    && $_FILES['filename']['error']){
            addMessageAfterRedirect($LANG['document'][46],false,ERROR);
         }
      }
      unset ($_FILES);
      return $docadded;
   }

   function getSearchOptions() {
      global $LANG;

      // TRACKING_TYPE - used for massive actions
      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[2]['table']     = 'glpi_tickets';
      $tab[2]['field']     = 'status';
      $tab[2]['linkfield'] = 'status';
      $tab[2]['name']      = $LANG['joblist'][0];

      $tab[3]['table']     = 'glpi_tickets';
      $tab[3]['field']     = 'priority';
      $tab[3]['linkfield'] = 'priority';
      $tab[3]['name']      = $LANG['joblist'][2];

      $tab[4]['table']     = 'glpi_users';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'users_id';
      $tab[4]['name']      = $LANG['job'][4];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

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

      $tab[7]['table']     = 'glpi_ticketscategories';
      $tab[7]['field']     = 'name';
      $tab[7]['linkfield'] = 'ticketscategories_id';
      $tab[7]['name']      = $LANG['common'][36];

      $tab[9]['table']     = 'glpi_requesttypes';
      $tab[9]['field']     = 'name';
      $tab[9]['linkfield'] = 'requesttypes_id';
      $tab[9]['name']      = $LANG['job'][44];

      return $tab;
   }
}



/// Followup class
class Followup  extends CommonDBTM {


   // From CommonDBTM
   public $table = 'glpi_ticketsfollowups';
   public $type = FOLLOWUP_TYPE;

   function cleanDBonPurge($ID) {
      global $DB;

      $querydel = "DELETE
                   FROM `glpi_ticketsplannings`
                   WHERE `ticketsfollowups_id` = '$ID'";
      $DB->query($querydel);
   }


   function post_deleteFromDB($ID) {

      $job = new Job();
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

      $job = new Job;
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
         $pt = new PlanningTracking();
         // Update case
         if (isset($input["_plan"]["id"])) {
            $input["_plan"]['ticketsfollowups_id'] = $input["id"];
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = $mailsend;

            if (!$pt->update($input["_plan"])) {
               return false;
            }
            unset($input["_plan"]);
         // Add case
         } else {
            $input["_plan"]['ticketsfollowups_id'] = $input["id"];
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
      $input["_job"] = new Job;

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

      // Pass old assign From Job in case of assign change
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
            $input["_plan"]['ticketsfollowups_id'] = $newID;
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = 1;
            $pt = new PlanningTracking();

            if (!$pt->add($input["_plan"])) {
               return false;
            }
         }

         if ($input["_close"] && $input["_type"]!="update" && $input["_type"]!="finish") {
            $updates[] = "status";
            $updates[] = "closedate";
            $input["_job"]->fields["status"] = "old_done";
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