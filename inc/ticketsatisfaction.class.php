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

class TicketSatisfaction extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['satisfaction'][0];
   }

   /**
    * for use showFormHeader
   **/
   function getIndexName() {
      return 'tickets_id';
   }


   function canUpdate() {
      return (haveRight('create_ticket', 1));
   }


   /**
    * Is the current user have right to update the current satisfaction
    *
    * @return boolean
    */
   function canUpdateItem() {

      $tid = $this->fields['tickets_id'];
      $ticket = new ticket();
      if (!$ticket->getFromDB($tid)) {
         return false;
      }

      if ($ticket->fields["users_id"] === getLoginUserID()
          || $this->fields["users_id_recipient"] === getLoginUserID()
          || (!isset($_SESSION["glpigroups"])
              && !in_array($this->fields["groups_id"], $_SESSION['glpigroups']))) {
         return true;
      }
      return false;
   }


   /**
    * form for satisfaction
    *
    * @param $ticket Object : the ticket
   **/
   function showSatisfactionForm($ticket) {
      global $LANG;

      $tid = $ticket->fields['id'];
      $options = array();
      $options['colspan'] = 1;

      $this->showFormHeader($options);

      // Set default satisfaction to 3 if not set
      if (is_null($this->fields["satisfaction"])) {
         $this->fields["satisfaction"] = 3;
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['satisfaction'][1]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      echo "<input type='hidden' name='tickets_id' value='$tid'>";
      echo "<input type='hidden' id='satisfaction' name='satisfaction' value='".
             $this->fields["satisfaction"]."'>";

//      Dropdown::showInteger("satisfaction", $this->fields["satisfaction"], 0, 5);

//      echo "<span class='small_space'><font size='-5'>(".$LANG['satisfaction'][5].")</font></span>";

      echo  "<script type='text/javascript'>\n
         Ext.onReady(function() {
         var md = new Ext.form.StarRate({
                    hiddenName: 'satisfaction',
                    starConfig: {
                    	minValue: 0,
                    	maxValue: 5,
                     value:".$this->fields["satisfaction"]."
                    },
                    applyTo : 'satisfaction'
         });
         })
         </script>";

      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td rowspan='1'>".$LANG['common'][25]."&nbsp;:&nbsp;</td>";
      echo "<td rowspan='1' class='middle'>";
      echo "<textarea cols='45' rows='7' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      if ($this->fields["date_answered"] >0) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='2'>".$LANG['satisfaction'][4]."&nbsp;:&nbsp;";
         echo convDateTime($this->fields["date_answered"])."</td></tr>\n";
      }

      $options['candel'] = false;
      $this->showFormButtons($options);
   }


   function prepareInputForUpdate($input) {
      global $CFG_GLPI;

      if ($input['satisfaction']>0) {
         $input["date_answered"] = $_SESSION["glpi_currenttime"];
      }
      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      if ($CFG_GLPI["use_mailing"]) {
         $ticket = new Ticket;
         if ($ticket->getFromDB($this->fields['tickets_id'])) {
            NotificationEvent::raiseEvent("satisfaction", $ticket);
         }
      }
   }


   /**
    * display satisfaction value
    *
    * @param $value decimal between 0 and 5
   **/
   static function displaySatisfaction($value) {

      if ($value<0) {
         $value = 0;
      }
      if ($value>5) {
         $value = 5;
      }
      echo '<div style="width: 81px;"  class="x-starslider x-starslider-horz">';
      echo '<div  class="x-starslider-end">';
      echo '<div style="width: 81px;" class="x-starslider-inner">';
      echo "<div style='width: ".intval($value*16)."px;' class='x-starslider-thumb'>";
      echo '</div></div></div></div>';
   }

}

?>
