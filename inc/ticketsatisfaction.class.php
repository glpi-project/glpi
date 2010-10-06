<?php
/*
 * @version $Id: ticketsolutiontype.class.php 10411 2010-02-09 07:58:26Z moyo $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class TicketSatisfaction extends CommonDropdown {

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


   /**
    * form for satisfaction
    *
    * @param $ticket Object : the ticket
   **/
   function showSatisfactionForm($ticket){
      global $LANG;

      $tid = $ticket->fields['id'];
      $options = array();
      $options['colspan'] = 1;

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['satisfaction'][1]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      echo "<input type='hidden' name='tickets_id' value='$tid'>";
      Dropdown::showInteger("satisfaction", $this->fields["satisfaction"], 0, 5);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td rowspan='1'>".$LANG['common'][25]."&nbsp;:&nbsp;</td>";
      echo "<td rowspan='1' class='middle'>";
      echo "<textarea cols='45' rows='7' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      $options['candel'] = false;
      $this->showFormButtons($options);
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

}

?>
