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

// FUNCTIONS Tracking System











function showFollowupsShort($ID) {
   global $DB,$CFG_GLPI, $LANG;

   // Print Followups for a job
   $showprivate = haveRight("show_full_ticket","1");

   $RESTRICT = "";
   if (!$showprivate) {
      $RESTRICT = " AND (`is_private` = '0'
                         OR `users_id` ='".$_SESSION["glpiID"]."') ";
   }

   // Get Number of Followups
   $query = "SELECT *
             FROM `glpi_ticketfollowups`
             WHERE `tickets_id` = '$ID'
                   $RESTRICT
             ORDER BY `date` DESC";
   $result=$DB->query($query);

   $out = "";
   if ($DB->numrows($result)>0) {
      $out .= "<div class='center'><table class='tab_cadre' width='100%'>\n
               <tr><th>".$LANG['common'][27]."</th><th>".$LANG['job'][4]."</th>
               <th>".$LANG['joblist'][6]."</th></tr>\n";

      while ($data=$DB->fetch_array($result)) {
         $out .= "<tr class='tab_bg_3'>
                  <td class='center'>".convDateTime($data["date"])."</td>
                  <td class='center'>".getUserName($data["users_id"],1)."</td>
                  <td width='70%' class='b'>".resume_text($data["content"],$CFG_GLPI["cut"])."</td>
                  </tr>";
      }
      $out .= "</table></div>";
   }
   return $out;
}


function getAssignName($ID,$itemtype,$link=0) {
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









?>
