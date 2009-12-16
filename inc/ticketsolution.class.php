<?php
/*
 * @version $Id: ticketfollowup.class.php 9663 2009-12-13 11:38:45Z yllen $
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

/// TicketSolution class
class TicketSolution  extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_ticketsolutions';
   public $type = 'TicketSolution';

   static function getTypeName() {
      global $LANG;

      return $LANG['jobresolution'][1];
   }

   function canCreate() {
      return (haveRight('comment_all_ticket', 1)
              || haveRight('comment_ticket', 1)
              || haveRight('own_ticket', 1));
   }

   function canView() {
      return (haveRight('observe_ticket', 1)
              || haveRight('show_full_ticket', 1)
              || haveRight('own_ticket', 1));
   }

   /**
    * Is the current user have right to show the current followup ?
    *
    * @return boolean
    */
   function canViewItem() {

      $ticket = new Ticket();
      return $ticket->can($this->getField('tickets_id'),'r');
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
      return (haveRight("comment_all_ticket","1")
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

   function showInTicketSumnary (Ticket $ticket, $rand=0, $showprivate=0, $caneditall=0) {
      global $CFG_GLPI, $LANG;

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$this->getTypeName()."</td>";
      echo "<td>".convDateTime($this->fields["date"]) . "</td>";
      echo "<td class='left'><b>";
      echo Dropdown::getDropdownName("glpi_ticketsolutiontypes",$this->fields["ticketsolutiontypes_id"]);
      echo "</b><br>".nl2br($this->fields["content"]) . "</td>";
      if ($rand) {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      echo "</tr>\n";
   }

   static function showForTicket (Ticket $ticket) {
      global $DB, $LANG;

      $item  =new self();
      $query = "SELECT `id`
                FROM `glpi_ticketsolutions`
                WHERE `tickets_id`='".$ticket->getField('id')."'
                ORDER BY `date` DESC";

      $result = $DB->query($query);
      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th class='b'>" . $LANG['job'][12];
         echo "</th></tr></table>";
      } else {
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th>".$LANG['common'][17]."</th><th>" . $LANG['common'][27] . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th><th>" . $LANG['common'][37] . "</th>";
         echo "</tr>\n";

         while ($data = $DB->fetch_array($result)) {
            if ($item->getFromDB($data['id'])) {
               $item->showInTicketSumnary($ticket);
            }
         }
         echo "</table>";
      }
   }

   function prepareInputForAdd($input) {
      global $LANG;

      if (!isset($input["users_id"])) {
         $input["users_id"] = $_SESSION["glpiID"];
      }
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }

   /**
    * Form to add a solution to a ticket
    *
    * @param $tID integer : ticket ID
    * @param $massiveaction boolean : add followup using massive action
    */
   static function showAddForm($tID, $massiveaction=false) {
      global $DB,$LANG,$CFG_GLPI;

      $sol = new self();
      if ($tID>0) {
         $input = array('tickets_id'=>$tID);
         $sol->check(-1, 'w', $input);
      } else {
         checkRight("comment_all_ticket","1");
      }

      if ($tID>0) {
         echo "<form name='followups' method='post' action='".$sol->getFormURL()."'>\n";
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['jobresolution'][2]."</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['job'][48]."</td><td>";
      Dropdown::dropdownValue('glpi_ticketsolutiontypes', 'ticketsolutiontypes_id','',1);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['joblist'][6]."</td>";
      echo "<td><textarea name='content' rows='12' cols='100'>";
      echo "</textarea></td>";
      echo "</tr>";

      if ($tID>0 || $massiveaction) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan=2'>";
         echo "<input type='hidden' name='tickets_id' value='$tID'>";
         echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit'>";
         echo "</td></tr>\n";
      }

      if ($tID>0) {
         echo "</form>";
      }
   }
}

?>