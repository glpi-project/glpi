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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// CLASSES TicketPlanning

class TicketPlanning extends CommonDBTM {

   function canCreate() {
      return (haveRight('show_planning', 1));
   }

   function canView() {
      return (haveRight('observe_ticket', 1)
              || haveRight('show_full_ticket', 1)
              || haveRight('own_ticket', 1));
   }

   /**
    * Read the planning information associated with a task
    *
    * @param $tickettasks_id integer ID of the task
    *
    * @return bool, true if exists
    */
   function getFromDBbyTask($tickettasks_id) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `tickettasks_id` = '$tickettasks_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
      }
      return false;
   }

   function showFormForTask(Ticket $ticket, TicketTask $task) {
      global $CFG_GLPI, $LANG;

      $taskid = $task->getField('id');
      if ($taskid>0 && $this->getFromDBbyTask($taskid)) {
         if ($this->canCreate()) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$taskid."(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array (
               'form' => 'followups',
               'users_id' => $this->fields["users_id"],
               'id' => $this->fields["id"],
               'state' => $this->fields["state"],
               'begin' => $this->fields["begin"],
               'end' => $this->fields["end"],
               'entity' => $ticket->fields["entities_id"]
            );
            ajaxUpdateItemJsCode('viewplan', $CFG_GLPI["root_doc"] . "/ajax/planning.php", $params);
            echo "}";
            echo "</script>\n";
            echo "<div id='plan' onClick='showPlan".$taskid."()'>\n";
            echo "<span class='showplan'>";
         }
         echo Planning::getState($this->fields["state"])."<br>".convDateTime($this->fields["begin"]).
              "<br>->".convDateTime($this->fields["end"])."<br>".
              getUserName($this->fields["users_id"]);
         if ($this->canCreate()) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         }
      } else {
         if ($this->canCreate()) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'state'    => 1,
                            'users_id' => getLoginUserID(),
                            'entity'   => $_SESSION["glpiactive_entity"]);
            ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params);
            echo "};";
            echo "</script>";

            echo "<div id='plan'  onClick='showPlanUpdate()'>\n";
            echo "<span class='showplan'>".$LANG['job'][34]."</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         } else {
            echo $LANG['job'][32];
         }
      }
   }

   function prepareInputForUpdate($input) {

      $this->getFromDB($input["id"]);
      // Save fields
      $oldfields=$this->fields;
      // Needed for test already planned
      $this->fields["users_id"] = $input["users_id"];
      $this->fields["begin"] = $input["begin"];
      $this->fields["end"] = $input["end"];

      if (!$this->test_valid_date()) {
         $this->displayError("date");
         return false;
      }
      Planning::checkAlreadyPlanned($input["users_id"],$input["begin"],$input["end"],
                  array('TicketPlanning'=>array($input["id"])));

/*      if ($this->is_alreadyplanned()) {
         $this->displayError("is_res");
         return false;
      }*/
      // Restore fields
      $this->fields=$oldfields;

      return $input;
   }

   function post_updateItem($history=1) {
      global $CFG_GLPI;

      // Auto update Status
      $job=new Ticket();
      $job->getFromDB($this->input["tickets_id"]);
      if ($job->fields["status"]=="new" || $job->fields["status"]=="assign") {
         $job->fields["status"]="plan";
         $this->updates[]="status";
         $job->updateInDB($this->updates);
      }

      // Auto update actiontime
      $fup=new TicketTask();
      $fup->getFromDB($this->input["tickettasks_id"]);
      $timestart=strtotime($this->input["begin"]);
      $timeend=strtotime($this->input["end"]);

      $updates2[]="actiontime";
      $fup->fields["actiontime"]=$timeend-$timestart;
      $fup->updateInDB($updates2);
      $job->updateActionTime($this->input["tickets_id"]);

      if ((!isset($this->input["_nomail"]) || $this->input["_nomail"]==0)
          && count($this->updates)>0 && $CFG_GLPI["use_mailing"]) {

//          $user=new User;
//          $user->getFromDB(getLoginUserID());

         $options = array('task_id' => $this->fields["id"]);
         NotificationEvent::raiseEvent('update_task',$job,$options);

//          $mail = new Mailing("followup",$job,$user,$fup->fields["is_private"]);
//          $mail->send();
      }
   }

   function prepareInputForAdd($input) {

      if (!isset($input["begin"]) || !isset($input["end"]) ){
         return false;
      }

      // Needed for test already planned
      $this->fields["users_id"] = $input["users_id"];
      $this->fields["begin"] = $input["begin"];
      $this->fields["end"] = $input["end"];

      if (!$this->test_valid_date()) {
         $this->displayError("date");
         return false;
      }
      Planning::checkAlreadyPlanned($input["users_id"],$input["begin"],$input["end"]);
/*      if ($this->is_alreadyplanned()) {
         $this->displayError("is_res");
         return false;
      }*/
      return $input;
   }

   function post_addItem() {
      global $CFG_GLPI;

      // Auto update Status
      $job=new Ticket();
      $job->getFromDB($this->input["tickets_id"]);
      if ($job->fields["status"]=="new" || $job->fields["status"]=="assign") {
         $job->fields["status"]="plan";
         $updates[]="status";
         $job->updateInDB($updates);
      }

      // Auto update actiontime
      $fup=new TicketTask();
      $fup->getFromDB($this->input["tickettasks_id"]);
      if ($fup->fields["actiontime"]==0) {
         $timestart=strtotime($this->input["begin"]);
         $timeend=strtotime($this->input["end"]);

         $updates2[]="actiontime";
         $fup->fields["actiontime"]=$timeend-$timestart;
         $fup->updateInDB($updates2);
         $job->updateActionTime($this->input["tickets_id"]);
      }

      if ((!isset($this->input["_nomail"]) || $this->input["_nomail"]==0)
          && $CFG_GLPI["use_mailing"]) {

         $user=new User;
         $user->getFromDB(getLoginUserID());

 	 $options = array('task_id' => $this->fields["id"]);
         NotificationEvent::raiseEvent('update_task',$job,$options);

      }
   }

   function pre_deleteItem() {

      if (isset($this->fields["users_id"]) &&
          ($this->fields["users_id"] === getLoginUserID() || haveRight("global_add_tasks","1"))) {

         // Auto update actiontime
         $fup=new TicketTask();
         $fup->getFromDB($this->fields["tickettasks_id"]);
         $updates2[]="actiontime";
         $fup->fields["actiontime"]=0;
         $fup->updateInDB($updates2);
      }
      return true;
   }


   // SPECIFIC FUNCTIONS

   /**
    * Is the user assigned to the current planning already planned ?
    *
    *@return boolean
    **/
   function is_alreadyplanned() {
      global $DB;

      if (!isset($this->fields["users_id"]) || empty($this->fields["users_id"])) {
         return true;
      }

      // When modify a planning do not itself take into account
      $ID_where="";
      if (isset($this->fields["id"])) {
         $ID_where=" (`id` <> '".$this->fields["id"]."') AND ";
      }
      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE $ID_where
                      `users_id` = '".$this->fields["users_id"]."'
                      AND '".$this->fields["end"]."' > `begin`
                      AND '".$this->fields["begin"]."' < `end`";

      if ($result=$DB->query($query)) {
         return ($DB->numrows($result)>0);
      }
      return true;
   }

   /**
    * Current dates are valid ? begin before end
    *
    *@return boolean
    **/
   function test_valid_date() {
      return (!empty($this->fields["begin"]) && !empty($this->fields["end"])
              && strtotime($this->fields["begin"]) < strtotime($this->fields["end"]));
   }

   /**
    * Add error message to message after redirect
    * @param $type error type : date / is_res / other
    *@return nothing
    **/
   function displayError($type) {
      global $LANG;

      switch ($type) {
         case "date" :
            addMessageAfterRedirect($LANG['planning'][1],false,ERROR);
            break;

         case "is_res" :
            addMessageAfterRedirect($LANG['planning'][0],false,ERROR);
            break;

         default :
            addMessageAfterRedirect($LANG['common'][61],false,ERROR);
            break;
      }
   }

   /**
    * Populate the planning with planned ticket tasks
    *
    * @param $who ID of the user (0 = undefined)
    * @param $who_group ID of the group of users (0 = undefined, mine = login user ones)
    * @param $begin Date
    * @param $end Date
    *
    * @return array of planning item
    */
   static function populatePlanning($who, $who_group, $begin, $end) {
      global $DB, $CFG_GLPI;

      $interv = array();
      // Get items to print
      $ASSIGN="";

      if ($who_group==="mine") {
         if (count($_SESSION["glpigroups"])) {
            $groups=implode("','",$_SESSION['glpigroups']);
            $ASSIGN=" `users_id` IN (SELECT DISTINCT `users_id`
                                    FROM `glpi_groups_users`
                                    WHERE `groups_id` IN ('$groups'))
                                          AND ";
         } else { // Only personal ones
            $ASSIGN="`users_id` = '$who'
                     AND ";
         }
      } else {
         if ($who>0) {
            $ASSIGN="`users_id` = '$who'
                     AND ";
         }
         if ($who_group>0) {
            $ASSIGN="`users_id` IN (SELECT `users_id`
                                    FROM `glpi_groups_users`
                                    WHERE `groups_id` = '$who_group')
                                          AND ";
         }
      }
      if (empty($ASSIGN)) {
         $ASSIGN="`users_id` IN (SELECT DISTINCT `glpi_profiles_users`.`users_id`
                                 FROM `glpi_profiles`
                                 LEFT JOIN `glpi_profiles_users`
                                    ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`)
                                 WHERE `glpi_profiles`.`interface`='central' ";

         $ASSIGN.=getEntitiesRestrictRequest("AND","glpi_profiles_users", '',
                                             $_SESSION["glpiactive_entity"],1);
         $ASSIGN.=") AND ";
      }

      $query = "SELECT *
                FROM `glpi_ticketplannings`
                WHERE $ASSIGN
                      '$begin' < `end` AND '$end' > `begin`
                ORDER BY `begin`";

      $result=$DB->query($query);

      $fup=new TicketTask();
      $job=new Ticket();
      $interv=array();
      if ($DB->numrows($result)>0) {
         for ($i=0 ; $data=$DB->fetch_array($result) ; $i++) {
            if ($fup->getFromDB($data["tickettasks_id"])) {
               if ($job->getFromDBwithData($fup->fields["tickets_id"],0)) {
                  if (haveAccessToEntity($job->fields["entities_id"])) {
                     $interv[$data["begin"]."$$$".$i]["tickettasks_id"]=$data["tickettasks_id"];
                     $interv[$data["begin"]."$$$".$i]["state"]=$data["state"];
                     $interv[$data["begin"]."$$$".$i]["tickets_id"]=$fup->fields["tickets_id"];
                     $interv[$data["begin"]."$$$".$i]["users_id"]=$data["users_id"];
                     $interv[$data["begin"]."$$$".$i]["id"]=$data["id"];
                     if (strcmp($begin,$data["begin"])>0) {
                        $interv[$data["begin"]."$$$".$i]["begin"]=$begin;
                     } else {
                        $interv[$data["begin"]."$$$".$i]["begin"]=$data["begin"];
                     }
                     if (strcmp($end,$data["end"])<0) {
                        $interv[$data["begin"]."$$$".$i]["end"]=$end;
                     } else {
                        $interv[$data["begin"]."$$$".$i]["end"]=$data["end"];
                     }
                     $interv[$data["begin"]."$$$".$i]["name"]=$job->fields["name"];
                     $interv[$data["begin"]."$$$".$i]["content"]=resume_text($job->fields["content"],
                                                                           $CFG_GLPI["cut"]);
                     $interv[$data["begin"]."$$$".$i]["device"]=($job->hardwaredatas ?$job->hardwaredatas->getName():'');
                     $interv[$data["begin"]."$$$".$i]["status"]=$job->fields["status"];
                     $interv[$data["begin"]."$$$".$i]["priority"]=$job->fields["priority"];
                  }
               }
            }
         }
      }
      return $interv;
   }

   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    *
    * @return Already planned information
    **/
   static function getAlreadyPlannedInformation($val) {
      global $CFG_GLPI;

      $out=Ticket::getTypeName().' : '.convDateTime($val["begin"]).' -> '.convDateTime($val["end"]).' : ';
      $out.="<a href='".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$val["tickets_id"]."'>";
      $out.=resume_text($val["name"],80).'</a>';
      return $out;
   }

   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
    **/
   static function displayPlanningItem($val,$who,$type="",$complete=0) {
      global $CFG_GLPI, $LANG;

      $rand=mt_rand();
      $styleText="";
      if (isset($val["state"])) {
         switch ($val["state"]) {
            case 2 : // Done
               $styleText="color:#747474;";
               break;
         }
      }

      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv_interv.png' alt='' title='".
            $LANG['planning'][8]."'>&nbsp;&nbsp;";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/".$val["status"].".png\" alt='".
            Ticket::getStatus($val["status"])."' title='".Ticket::getStatus($val["status"])."'>&nbsp;";
      echo "<a id='content_tracking_".$val["id"].$rand."'
               href='".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$val["tickets_id"]."'
               style='$styleText'>";
      switch ($type) {
         case "in" :
            echo date("H:i",strtotime($val["begin"]))."/".date("H:i",strtotime($val["end"])).": ";
            break;

         case "through" :
            break;

         case "begin" :
            echo $LANG['buttons'][33]." ".date("H:i",strtotime($val["begin"])).": ";
            break;

         case "end" :
            echo $LANG['buttons'][32]." ".date("H:i",strtotime($val["end"])).": ";
            break;
      }
      echo "<br>- #".$val["tickets_id"]." ";
      echo  resume_text($val["name"],80). " ";
      if (!empty($val["device"])) {
         echo "<br>- ".$val["device"];
      }

      if ($who<=0) { // show tech for "show all and show group"
         echo "<br>- ";
         echo $LANG['common'][95]." ".getUserName($val["users_id"]);
      }
      echo "</a>";
      if ($complete) {
         echo "<br><strong>".Planning::getState($val["state"])."</strong><br>";
         echo "<strong>".$LANG['joblist'][2]."&nbsp;:</strong> ".Ticket::getPriorityName($val["priority"]);
         echo "<br><strong>".$LANG['joblist'][6]."&nbsp;:</strong><br>".$val["content"];
      } else {
         $content="<strong>".Planning::getState($val["state"])."</strong><br>".
         "<strong>".$LANG['joblist'][2]."&nbsp;:</strong> ".Ticket::getPriorityName($val["priority"]).
         "<br><strong>".$LANG['joblist'][6]."&nbsp;:</strong><br>".$val["content"]."</div>";
         showToolTip($content,array('applyto'=>"content_tracking_".$val["id"].$rand));
      }
   }
}
?>
