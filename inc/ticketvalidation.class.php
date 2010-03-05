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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * TicketValidation class
 */
class TicketValidation  extends CommonDBChild {

   // From CommonDBTM
   public $dohistory = true;
   
   // From CommonDBChild
   public $itemtype = 'Ticket';
   public $items_id = 'tickets_id';
   
   static function getTypeName() {
      global $LANG;

      return $LANG['validation'][0];
   }

   function canCreate() {
      return haveRight('create_validation', '1');
   }
   
   function canView() {
      return (haveRight('create_validation', 1)
              || haveRight('validate_ticket', 1));
   }
   
   function canUpdate() {

      return haveRight('validate_ticket', '1');
   }
   
   function canDelete() {
      return (haveRight('create_validation', 1)
              || (haveRight('update_ticket', 1) && haveRight('show_all_ticket', 1)));
   }
   
   /**
    * Is the current user have right to update the current validation ?
    *
    * @return boolean
    */
   function canUpdateItem() {
      global $LANG;
      
      if ($this->fields["users_id_validate"] != getLoginUserID()) {
         return false;
      }
/*      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }*/
 
      return true;
   }
   
   /**
    * Is the current user have right to delete the current validation ?
    *
    * @return boolean
    */
   function canDeleteItem() {
      if ($this->fields["users_id"] != getLoginUserID()) {
         return false;
      }
      return true;
   }
   
   static function canValidate($tickets_id) {
      global $DB;

      $query = "SELECT `users_id_validate` 
            FROM `glpi_ticketvalidations`
            WHERE `tickets_id` = '".$tickets_id."' AND users_id_validate='".getLoginUserID()."'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return true;
      }
      
      return false;
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['title'][26];

      return $ong;
   }
   
   function prepareInputForAdd($input) {
		global $LANG;
		
		// Not attached to tickets -> not added
      if (!isset($input['tickets_id']) || $input['tickets_id'] <= 0) {
         return false;
      } else {
      
         $job = new Ticket;
         $job->getFromDB($input["tickets_id"]);
         if (strstr($job->fields["status"],"solved") 
                  || strstr($job->fields["status"],"closed")) {
            return false;
         }
         
         if (!isset($input['entities_id'])) {
            $input['entities_id'] = $job->fields["entities_id"];
         }
         
         $input["name"] = addslashes($LANG['validation'][26]." - ".$LANG['job'][38]." ".$input["tickets_id"]);
         $input["users_id"] = getLoginUserID();
         $input["submission_date"] = $_SESSION["glpi_currenttime"];
      }
		return $input;
	}
   
   function post_addItem() {
		global $LANG,$CFG_GLPI;
		
      $job = new Ticket;
      $mailsend = false;
      if ($job->getFromDB($this->fields["tickets_id"]) && $CFG_GLPI["use_mailing"]) {
         $options = array('validation_id' => $this->fields["id"]);
         $mailsend = NotificationEvent::raiseEvent('validation',$job,$options);
      }
      if ($mailsend) {
         $user = new User();
         $user->getFromDB($this->fields["users_id_validate"]);
         if (!empty($user->fields["email"])) {
            addMessageAfterRedirect($LANG['validation'][13]." ".$user->getName());
         } else {
            addMessageAfterRedirect($LANG['validation'][23],false,ERROR);
         }
      }
		// Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = addslashes($LANG['validation'][13]." ".getUserName($this->fields["users_id_validate"]));
      Log::history($this->getField('tickets_id'),'Ticket',
                  $changes,$this->getType(),HISTORY_LOG_SIMPLE_MESSAGE);
	}
	
	function prepareInputForUpdate($input) {
		global $LANG;
		
		$job = new Ticket;
		
      if ($input["status"] == "rejected" && (!isset($input["comment_validation"]) || $input["comment_validation"] == '')) {
         addMessageAfterRedirect($LANG['validation'][29],false,ERROR);
         return false;
      }
      
      if ($this->fields["users_id_validate"] != getLoginUserID()) {
         return false;
      }
      
      if ($input["status"] == "waiting") {
         $input["comment_validation"] ='';
         $input["validation_date"] ='NULL';
      } else {
         $input["validation_date"] = $_SESSION["glpi_currenttime"];
      }
      
		return $input;
	}
   
   function post_updateItem($history=1) {
		global $LANG,$CFG_GLPI;
		
		$job = new Ticket;
      $mailsend = false;
      if ($job->getFromDB($this->fields["tickets_id"]) && $CFG_GLPI["use_mailing"]) {
         $options = array('validation_id' => $this->fields["id"]);
         $mailsend = NotificationEvent::raiseEvent('validation',$job,$options);
      }
		// Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      if ($this->fields["status"]=="accepted") {
         $validation = getUserName($this->fields["users_id_validate"]). " : ".$LANG['validation'][19];
      } else {
         $validation = getUserName($this->fields["users_id_validate"]). " : ".$LANG['validation'][20];
      }
      $changes[2] = $validation;
      Log::history($this->getField('tickets_id'),'Ticket',
                  $changes,$this->getType(),HISTORY_LOG_SIMPLE_MESSAGE);
	}
	
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][2];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = 'glpi_users';
      $tab[2]['field']     = 'name';
      $tab[2]['linkfield'] = 'users_id';
      $tab[2]['name']      = $LANG['job'][4];
      $tab[2]['datatype']      = 'itemlink';
      $tab[2]['itemlink_type'] = 'User';
      
      $tab[3]['table']     = 'glpi_tickets';
      $tab[3]['field']     = 'name';
      $tab[3]['linkfield'] = 'tickets_id';
      $tab[3]['name']      = $LANG['job'][38];
      $tab[3]['itemlink_type'] = 'Ticket';
      
      $tab[4]['table']     = 'glpi_users';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'users_id_validate';
      $tab[4]['name']      = $LANG['validation'][21];
      $tab[4]['datatype']      = 'itemlink';
      $tab[4]['itemlink_type'] = 'User';
      
      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'comment_submission';
      $tab[5]['linkfield'] = 'comment_submission';
      $tab[5]['name']      = $LANG['validation'][5];
      $tab[5]['datatype']  = 'text';
      
      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'comment_validation';
      $tab[6]['linkfield'] = 'comment_validation';
      $tab[6]['name']      = $LANG['validation'][6];
      $tab[6]['datatype']  = 'text';

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'status';
      $tab[7]['linkfield'] = 'status';
      $tab[7]['name']      = $LANG['joblist'][0];
      $tab[7]['searchtype']= 'equals';
      
      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'submission_date';
      $tab[8]['linkfield'] = 'submission_date';
      $tab[8]['name']      = $LANG['validation'][3];
      $tab[8]['datatype']  = 'datetime';
      
      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'validation_date';
      $tab[9]['linkfield'] = 'validation_date';
      $tab[9]['name']      = $LANG['validation'][4];
      $tab[9]['datatype']  = 'datetime';
      
      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      return $tab;
   }
   
   /**
    * get the Ticket validation status list
    *
    * @param $withmetaforsearch boolean
    * @return an array
    */
   static function getAllStatusArray($withmetaforsearch=false) {
      global $LANG;

      $tab = array(
         'waiting'   => $LANG['validation'][9],
         'rejected'  => $LANG['validation'][10],
         'accepted'  => $LANG['validation'][11]);

      if ($withmetaforsearch) {
         $tab['all']     = $LANG['common'][66];
      }
      return $tab;
   }
   
   /**
    * Dropdown of validation status
    *
    * @param $name select name
    * @param $value default value
    * @param $option list proposed 0:normal, 1:search, 2:allowed
    *
    * @return nothing (display)
    */
   static function dropdownStatus($name, $value='waiting', $option=0) {

      if ($option == 1) {
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
    * Get Ticket validation status Name
    *
    * @param $value status ID
    */
   static function getStatus($value) {

      $tab = self::getAllStatusArray(true);
      return (isset($tab[$value]) ? $tab[$value] : '');
   }
   
   /**
    * Get Ticket validation status Color
    *
    * @param $value status ID
    */
   static function getStatusColor($value) {
      
      switch ($value) {
         case "waiting":
            $style = "#FFC65D";
            break;
         case "rejected":
            $style = "#cf9b9b";
            break;
         case "accepted":
            $style = "#9BA563";
            break;
         default:
            $style = "#cf9b9b";
            break;
      }
      return $style;
   }
   
   /**
    * Get Ticket validation demands number 
    *
    * @param $value tickets_id
    */
   static function getNumberValidationForTicket($tickets_id) {
      global $DB;

      $query = "SELECT COUNT(`id`) AS `total`
            FROM `glpi_ticketvalidations`
            WHERE `tickets_id` = '".$tickets_id."'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result,0,"total");
      }
      
      return false;
   }
   
   /**
    * Get total status validation tickets_id
    *
    * @param $value status ID
    */
   static function getTicketStatus($tickets_id,$status) {
      global $DB;

      $query = "SELECT COUNT(`status`) AS `total`
            FROM `glpi_ticketvalidations`
            WHERE `tickets_id` = '".$tickets_id."' 
            AND `status` = '".$status."'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result,0,"total");
      }
      
      return false;
   }
   
   /**
    * Print the validation form into ticket
    *
    * @param $ticket class
    *
    **/
   function showForm($ID, $options=array()) {
      global $LANG;
      
      if (isset($options['ticket']) && !empty($options['ticket'])) {
         $ticket = $options['ticket'];
      }

      echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['validation'][1]."</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][21]."</td>";
      echo "<td>";
      echo "<input type='hidden' name='tickets_id' value='".$ticket->fields['id']."'>";
      echo "<input type='hidden' name='entities_id' value='".$ticket->fields['entities_id']."'>";
      User::dropdown(array('name'  => "users_id_validate",
                           'entity' => $ticket->fields['entities_id'],
                           'right'  => 'validate_ticket'));
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][25]."</td>";
      echo "<td><textarea cols='45' rows='3' name='comment_submission'></textarea></td>"; 
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan= '2' align='center'>";
      echo "<input type=\"submit\" name=\"add\" class=\"submit\" value=\"".$LANG['help'][14]."\" ></td>";
      echo "</tr>";
      
      echo "</table>";
      echo "</form>";
   }
   
   /**
    * Form for Followup on Massive action
    */
   static function showFormMassiveAction() {
      global $LANG;

      echo "&nbsp;".$LANG['validation'][21]."&nbsp;: ";
       User::dropdown(array('name'  => "users_id_validate",
                              'entity' => $_SESSION["glpiactive_entity"],
                              'right'  => 'validate_ticket'));

      echo "<br>".$LANG['common'][25]."&nbsp;: ";
      echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
   }
   
   /**
    * Print the validation list into ticket
    *
    * @param $ticket class
    *
    **/
   function showSummary($ticket) {
      global $DB, $LANG, $CFG_GLPI;
      
      $tID = $ticket->fields['id'];
      //$canadd = haveRight("create_validation", "1");

      $tmp = array('tickets_id'=>$tID);
      $canadd = $this->can(-1,'w',$tmp);
      $rand = mt_rand();
      
      if ($canadd) {
         echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";
      }
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddValidation" . $tID . "$rand(){\n";
         $params = array ('type'       => __CLASS__,
                          'tickets_id' => $tID,
                          'id'         => -1);
         ajaxUpdateItemJsCode("viewfollowup" . $tID . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php", $params, false);
         echo "};";
         echo "</script>\n";
         if ($ticket->fields["status"] != 'solved' && $ticket->fields["status"] != 'closed') {
            echo "<p><a href='javascript:viewAddValidation".$tID."$rand();'>";
            echo $LANG['validation'][1]."</a></p><br>\n";
         }
      }
      
      $query = "SELECT * 
            FROM `".$this->getTable()."`
            WHERE `tickets_id` = '".$ticket->getField('id')."'
            ORDER BY submission_date DESC";
      $result = $DB->query($query);
      $number = $DB->numrows($result);
      
      if ($number) {
         $colonnes = array($LANG['validation'][2],
                        $LANG['validation'][3],
                        $LANG['validation'][18],
                        $LANG['validation'][5],
                        $LANG['validation'][4],
                        $LANG['validation'][21],
                        $LANG['validation'][6]);
         $nb_colonnes = count($colonnes);
         
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='".$nb_colonnes."'>".$LANG['validation'][7]."</th></tr>";
         
         echo "<tr>";
         foreach ($colonnes as $colonne) {
            echo "<th>".$colonne."</th>";
         }
         echo "</tr>";
         
         initNavigateListItems('TicketValidation',$LANG['validation'][26]." = ".$ticket->fields['name']);
             
         while ($row = $DB->fetch_assoc($result)) {
            addToNavigateListItems('TicketValidation',$row["id"]);
            $bgcolor = $this->getStatusColor($row['status']);
            $status = $this->getStatus($row['status']);
            if ($row['is_deleted'] == 1) {
               $status = $LANG['validation'][8];
               $bgcolor = "#cf9b9b";
            }
            echo "<tr class='tab_bg_1'>";
            echo "<td><div style=\"background-color:".$bgcolor.";\">".$status."</div></td>";
				
				if ($ticket->can($ticket->fields['id'], 'r') 
                     && !strstr($ticket->fields["status"],"solved") 
                        && !strstr($ticket->fields["status"],"closed")) {
               
               $link_validation=getItemTypeFormURL('TicketValidation');
               echo "<td><a href=\"".$link_validation."?id=".$row["id"]."\">".
                  convDateTime($row["submission_date"])."</a></td>";
            } else {
               echo "<td>".convDateTime($row["submission_date"])."</a></td>";
            }
				
				$users_id = $row["users_id"];
            $link=getItemTypeFormURL('User');
            $out  = "<a href=\"".$link;
            $out .= (strstr($link,'?') ?'&amp;' :  '?');
            $out .= 'id='.$users_id."\">";
            $out .= getUserName($users_id);
            $out .= "</a>";
            echo "<td>".$out."</td>";
            echo "<td>".$row["comment_submission"]."</td>";
            echo "<td>".convDateTime($row["validation_date"])."</td>";
            $users_id_validate = $row["users_id_validate"];
            $link=getItemTypeFormURL('User');
            $out  = "<a href=\"".$link;
            $out .= (strstr($link,'?') ?'&amp;' :  '?');
            $out .= 'id='.$users_id_validate."\">";
            $out .= getUserName($users_id_validate);
            $out .= "</a>";
            echo "<td>".$out."</td>";
            echo "<td>".$row["comment_validation"]."</td>";
            
            /*if ($row["status"]=='waiting' 
               && $row['is_deleted']!=1
                  && $ticket->can($ticket->fields['id'], 'r') 
                     && !strstr($ticket->fields["status"],"solved") 
                        && !strstr($ticket->fields["status"],"closed")) {
               echo "<td>";
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/validation.form.php?resend=&amp;id=".$row["id"]."'>".$LANG['validation'][12]."</a>";
               echo "</td>";
            }*/
            
            
            echo "</tr>";
         }
         echo "</table>";
      }
   }
   
   /**
    * Print the validation form
    *
    * @param $ID integer ID of the item
    *
    **/
   function showValidationTicketForm($ID, $options=array()) {
      global $LANG;
      
      $this->check($ID,'r');

      if ($_SESSION["glpiactiveprofile"]["interface"] != "helpdesk")
         $this->showTabs($options);
      $this->showFormHeader($options);
      
      $ticket = new Ticket();
      $ticket->getFromDB($this->fields["tickets_id"]);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][17].":</td>";
      echo "<td>".getUserName($ticket->fields["users_id"])."</td>";

      echo "<td>".$LANG['common'][57].":</td>";
      
      $tickets_id = $this->fields["tickets_id"];
      $link=getItemTypeFormURL('Ticket');
      $out  = "<a id='ticket".$tickets_id."' href=\"".$link;
      $out .= (strstr($link,'?') ?'&amp;' :  '?');
      $out .= 'id='.$tickets_id."\">";
      $out .= $ticket->fields["name"];
      if ($_SESSION["glpiis_ids_visible"] || empty($ticket->fields["name"])) {
         $out .= " (".$tickets_id.")";
      }
      $out .= "</a>";

      $out.= showToolTip(nl2br($ticket->fields["content"]),
               array('applyto'=>'ticket'.$tickets_id,'display'=>false));
      echo "<td>".$out."</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4'>&nbsp;</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][18].":</td>";
      echo "<td>".getUserName($this->fields["users_id"])."</td>";
      
      if (!empty($this->fields["comment_submission"])) {
         echo "<td>".$LANG['validation'][5].":</td>";
         echo "<td>".$this->fields["comment_submission"]."</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4'>&nbsp;</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][21].":</td>";
      echo "<td>".getUserName($this->fields["users_id_validate"])."</td>";

      echo "<td>".$LANG['validation'][28].":</td>";
      echo "<td>";
      TicketValidation::dropdownStatus("status",$this->fields["status"]);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4'>&nbsp;</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>".$LANG['validation'][6]." (".$LANG['validation'][16]."):</td>";
      echo "<td colspan='2'><textarea cols='70' rows='3' name='comment_validation'>".$this->fields["comment_validation"]."</textarea>";
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);
      
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }
   
   /**
    * Print the pending validation list from helpdesk interface
    **/
   function showPendingValidations() {
      global $DB, $CFG_GLPI, $LANG;

      $query = "SELECT `".$this->getTable()."`.*,
                        `glpi_tickets`.`name` AS `tname`, `glpi_tickets`.`content`
                 FROM `".$this->getTable()."` 
                 LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `".$this->getTable()."`.`tickets_id`)
                 WHERE `".$this->getTable()."`.`status` = 'waiting'
                 AND `".$this->getTable()."`.`is_deleted` <> '1'
                 AND `".$this->getTable()."`.`users_id_validate` = '".$_SESSION['glpiID']."' ";
      $query.= getEntitiesRestrictRequest(" AND", "glpi_tickets");

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      
      if ($number) {
         echo "<table class='tab_cadre_fixe' align='center'>";
         echo "<tr><th colspan='4'>".$LANG['validation'][15]."</th></tr>";
         echo "<tr>";
         echo "<th>".$LANG['validation'][3]."</th>";
         echo "<th>".$LANG['job'][38]."</th>";
         echo "<th>".$LANG['validation'][5]."</th>";
         echo "<th></th>";
         echo "</tr>";
         
         while ($row=$DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".convDateTime($row["submission_date"])."</td>";
            $tickets_id = $row["tickets_id"];
            $link=getItemTypeFormURL('Ticket');
            $out  = "<a id='ticketvalidation".$tickets_id."' href=\"".$link;
            $out .= (strstr($link,'?') ?'&amp;' :  '?');
            $out .= 'id='.$tickets_id."\">";
            $out .= $row["tname"];
            if ($_SESSION["glpiis_ids_visible"] || empty($row["tname"])) {
               $out .= " (".$tickets_id.")";
            }
            $out .= "</a>";
            $out.= showToolTip(nl2br($row["content"]),
               array('applyto'=>'ticketvalidation'.$tickets_id,'display'=>false));
            echo "<td>".$out."</td>";
            echo "<td>".$row["comment_submission"]."</td>";
            echo "<td>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticketvalidation.form.php?id=".$row["id"]."'>";
            echo $LANG['validation'][24];
            echo "</a>";
            echo "</td>";
            echo "</tr>";
         }
         echo "</table>";
      } else {
         echo "<div align='center'>";
         echo $LANG['validation'][25];
         echo "</div>";
      }
      
   }
}

?>