<?php

/*
 * @version $Id: budget.class.php 10545 2010-02-16 03:02:15Z moyo $
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
class TicketValidation extends CommonDBTM{

   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['validation'][0];
   }

   function canCreate() {
      return haveRight('approve_ticket', 'w');
   }

   function canUpdate() {
      return haveRight('approve_ticket', 'r');
   }
   
   //TO BE CUSTOM
   function canApprove($tickets_id) {
      global $DB;

      $query = "SELECT `users_id_approval` 
            FROM `".$this->getTable()."`
            WHERE `tickets_id` = '".$tickets_id."' ";
      $result = $DB->query($query);
      $row = $DB->fetch_assoc($result);
      
      if ($row["users_id_approval"] == getLoginUserID())
         return true;
      else
         return false;
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['title'][26];

      return $ong;
   }
   
   function prepareInputForAdd($input) {
		
		$input["users_id"] = getLoginUserID();
		$input["submission_date"] = $_SESSION["glpi_currenttime"];

		return $input;
	}
   
   function post_addItem() {
		global $LANG;
		
      /*$job = new Ticket;
      $mailsend = false;
      if ($job->getFromDB($this->input["tickets_id"]) && $CFG_GLPI["use_mailing"]) {
         $mailsend = NotificationEvent::raiseEvent('send_validation',$job,$options);
      }*/
		// Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $LANG['validation'][13]." ".getUserName($this->fields["users_id_approval"]);
      Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_LOG_SIMPLE_MESSAGE);
	}
   
   function post_updateItem($history=1) {
		global $LANG;
		
		/*$job = new Ticket;
      $mailsend = false;
      if ($job->getFromDB($this->input["tickets_id"]) && $CFG_GLPI["use_mailing"]) {
         $mailsend = NotificationEvent::raiseEvent('send_validation',$job,$options);
      }*/
		// Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      if ($this->fields["status"]=="accepted") {
         $validation = getUserName($this->fields["users_id_approval"]). " : ".$LANG['validation'][19];
      } else {
         $validation = getUserName($this->fields["users_id_approval"]). " : ".$LANG['validation'][20];
      }  
      $changes[2] = $validation;
      Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_LOG_SIMPLE_MESSAGE);
	}
	
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'id';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['common'][2];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'TicketValidation';

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
      $tab[4]['linkfield'] = 'users_id_approval';
      $tab[4]['name']      = $LANG['validation'][14];
      $tab[4]['datatype']      = 'itemlink';
      $tab[4]['itemlink_type'] = 'User';
      
      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'comment_submission';
      $tab[5]['linkfield'] = 'comment_submission';
      $tab[5]['name']      = $LANG['validation'][5];
      $tab[5]['datatype']  = 'text';
      
      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'comment_approval';
      $tab[6]['linkfield'] = 'comment_approval';
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
      $tab[8]['datatype']  = 'date';
      
      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'approval_date';
      $tab[9]['linkfield'] = 'approval_date';
      $tab[9]['name']      = $LANG['validation'][4];
      $tab[9]['datatype']  = 'date';
      
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
    * Print the validation form into ticket
    *
    * @param $ticket class
    *
    **/
   function showApprobationTicketForm($ticket) {
      global $LANG;
      
      if (!haveRight('approve_ticket','r')) return false;
      $canedit = haveRight('approve_ticket','w');
      if ($ticket->canUpdateItem()) {
         echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>".$LANG['validation'][1]."</th></tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][34]."</td>";
         echo "<td>";
         echo "<input type='hidden' name='tickets_id' value='".$ticket->getField('id')."'>";
         echo "<input type='hidden' name='entities_id' value='".$ticket->getField('entities_id')."'>";
         User::dropdown(array('name'  => "users_id_approval",
                              'entity' => $ticket->getField('entities_id'),
                              'right'  => 'all'));
         echo "</td>";
         echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][25]."</td>";
         echo "<td><textarea cols='45' rows='3' name='comment_submission' maxlength='254'></textarea></td>"; 
         echo "</tr>";
         
         if ($canedit) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan= '2' align='center'>";
            echo "<input type=\"submit\" name=\"add\" class=\"submit\" value=\"".$LANG['help'][14]."\" ></td>";
            echo "</tr>";
         }
         echo "</table>";
         echo "</form>";
      }
   }
   
   /**
    * Print the validation list into ticket
    *
    * @param $ticket class
    *
    **/
   function showSummary($ticket) {
      global $DB, $LANG, $CFG_GLPI;

      $query = "SELECT * 
            FROM `".$this->getTable()."`
            WHERE `tickets_id` = '".$ticket->getField('id')."'
            ORDER BY submission_date DESC";
      $result = $DB->query($query);
      $number = $DB->numrows($result);
      
      if ($number) {
         $colonnes = array($LANG['validation'][2],
                        $LANG['validation'][3],
                        $LANG['validation'][5],
                        $LANG['validation'][4],
                        $LANG['validation'][14],
                        $LANG['validation'][6], 
                       "");
         $nb_colonnes = count($colonnes);
         
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='".$nb_colonnes."'>".$LANG['validation'][7]."</th></tr>";
         
         echo "<tr>";
         foreach ($colonnes as $colonne) {
            echo "<th>".$colonne."</th>";
         }
         echo "</tr>";
               
         while ($row = $DB->fetch_assoc($result)) {
            
            
            $bgcolor = $this->getStatusColor($row['status']);
            $status = $this->getStatus($row['status']);
            if ($row['is_deleted'] == 1) {
               $status = $LANG['validation'][8];
               $bgcolor = "#cf9b9b";
            }
            echo "<tr class='tab_bg_1'>";
            echo "<td><div style=\"background-color:".$bgcolor.";\">".$status."</div></td>";
				
            echo "<td>".convDateTime($row["submission_date"])."</td>";
            echo "<td>".$row["comment_submission"]."</td>";
            echo "<td>".convDateTime($row["approval_date"])."</td>";
            echo "<td>".getUserName($row["users_id_approval"])."</td>";
            echo "<td>".$row["comment_approval"]."</td>";
            
            echo "<td>";
            /*if ($row["status"]=='waiting' && $row['is_deleted']!=1) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/validation.form.php?resend=&amp;id=".$row["id"]."'>".$LANG['validation'][12]."</a>";
            }*/
            echo "</td>";
            
            echo "</tr>";
         }
         echo "</table>";
      }
   }
   
   /**
    * Print the approbation form
    *
    * @param $ID integer ID of the item
    *
    **/
   function showApprobationForm($ID){
      global $LANG;
      
      $this->getFromDB($ID);
      $ticket = new Ticket();
      $ticket->getFromDB($this->fields["tickets_id"]);
     
      $canedit = haveRight('approve_ticket','w');
      
      echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['validation'][15]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][4].":</td>";
      echo "<td>".getUserName($ticket->fields["users_id"])."</td>";
      echo "</tr>";

      if ($ticket->fields["users_id_assign"]) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][34].":</td>";
         echo "<td>".getUserName($ticket->fields["users_id_assign"])."</td>";
         echo "</tr>";
      }
      
      echo "<tr class='tab_bg_1'>";
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
      
      if (!empty($this->fields["comment_submission"])) {
         echo "<tr class='tab_bg_2 b'>";
         echo "<td>".$LANG['validation'][5].":</td>";
         echo "<td>".$this->fields["comment_submission"]."</td>";
         echo "</tr>";
      }
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][6]." (".$LANG['validation'][16]."):</td>";
      echo "<td><textarea cols='70' rows='3' name='comment_approval' maxlength='254'></textarea>";
      echo "</td>";
      echo "</tr>";
      
      if ($canedit) {
         echo "<tr class='tab_bg_2 center'>";
         echo "<td>";
         echo "<input type='hidden' name='id' value='".$this->fields["id"]."'>";
         echo "<input type='submit' name='accept' value='".$LANG['validation'][17]."' class='submit'>";
         echo "</td>";
         echo "<td>";
         echo "<input type='submit' name='reject' value='".$LANG['validation'][18]."' class='submit'>";
         echo "</td>";
         echo "</tr>";
      }
      echo "</table></div></form>";
   }
   
   /**
    * Print the approbation list
    *
    * @param $ID integer ID of the item
    *
    **/
   function showValidation($ID){
      global $LANG;
      
      $this->getFromDB($ID);
      
      $status = $this->getStatus($this->fields['status']);
      $bgcolor = $this->getStatusColor($this->fields['status']);
      $ticket = new Ticket();
      $tickets_id = $this->fields["tickets_id"];
      
      echo "<div align='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['validation'][7]."</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][2]."</td>";
      if ($this->fields['is_deleted'] == 1) {
         $status = $LANG['validation'][8];
         $bgcolor = "#cf9b9b";
      }
      echo "<td><div style=\"background-color:".$bgcolor.";\">".$status."</div></td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][38]."</td>";
      
      
      $ticket->getFromDB($tickets_id);
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
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][3]."</td>";
      echo "<td>".convDateTime($this->fields["submission_date"])."</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][5]."</td>";
      echo "<td>".nl2br($this->fields["comment_submission"])."</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][4]."</td>";
      echo "<td>".convDateTime($this->fields["approval_date"])."</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['validation'][6]."</td>";
      echo "<td>".nl2br($this->fields["comment_approval"])."</td>";
      echo "</tr>";

      echo "</table>";
      
      echo "<a href='".getItemTypeSearchURL('TicketValidation')."'>".$LANG['buttons'][13]."</a>";
      echo "</div>";
      
   }
   
   function showPendingValidations() {
      global $DB, $CFG_GLPI, $LANG;

      $query = "SELECT `".$this->getTable()."`.*,
                        `glpi_tickets`.`name` AS `tname`, `glpi_tickets`.`content`
                 FROM `".$this->getTable()."` 
                 LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `".$this->getTable()."`.`tickets_id`)
                 WHERE `".$this->getTable()."`.`status` = 'waiting'
                 AND `".$this->getTable()."`.`is_deleted` <> '1'
                 AND `".$this->getTable()."`.`users_id_approval` = '".$_SESSION['glpiID']."' ";
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