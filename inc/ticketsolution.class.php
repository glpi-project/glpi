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

   function showInTicketSumnary (Ticket $ticket, $rand, $showprivate, $caneditall) {
      global $CFG_GLPI, $LANG;

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$this->getTypeName()."</td>";
      echo "<td>".convDateTime($this->fields["date"]) . "</td>";
      echo "<td class='left'><b>";
      echo Dropdown::getDropdownName("glpi_ticketsolutiontypes",$this->fields["ticketsolutiontypes_id"]);
      echo "</b><br>".nl2br($this->fields["content"]) . "</td>";
      echo "<td colspan='2'>&nbsp;</td>";
      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      echo "</tr>\n";
   }
}

?>