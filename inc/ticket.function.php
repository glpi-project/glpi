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














function showJobShort($data, $followups,$output_type=HTML_OUTPUT,$row_num=0) {
   global $CFG_GLPI, $LANG;

   // Prints a job in short form
   // Should be called in a <table>-segment
   // Print links or not in case of user view
   // Make new job object and fill it from database, if success, print it
   $job = new Ticket;
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

      echo displaySearchNewLine($output_type,$row_num%2);

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

      echo displaySearchItem($output_type,$first_col,$item_num,$row_num,$align);

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
         $second_col .= "&nbsp;".getRealtime($data["realtime"]);
         $second_col .= "</div>";
      }

      echo displaySearchItem($output_type,$second_col,$item_num,$row_num,$align." width=130");

      // Second BIS column
      $second_col = convDateTime($data["date_mod"]);
      echo displaySearchItem($output_type,$second_col,$item_num,$row_num,$align." width=90");

      // Second TER column
      if (count($_SESSION["glpiactiveentities"])>1) {
         if ($data['entityID']==0) {
            $second_col = $LANG['entity'][2];
         } else {
            $second_col = $data['entityname'];
         }
         echo displaySearchItem($output_type,$second_col,$item_num,$row_num,$align." width=100");
      }

      // Third Column
      echo displaySearchItem($output_type,"<strong>".Ticket::getPriorityName($data["priority"])."</strong>",
                             $item_num,$row_num,"$align bgcolor='$bgcolor'");

      // Fourth Column
      $fourth_col = "";
      if ($data['users_id']) {
         $userdata = getUserName($data['users_id'],2);
         $comment_display = "";
         if ($output_type==HTML_OUTPUT) {
            $comment_display  = "<a href='".$userdata["link"]."'>";
            $comment_display .= "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' ".
                                 "onmouseout=\"cleanhide('comment_trackusers_id".$data['id']."')\" ".
                                 "onmouseover=\"cleandisplay('comment_trackusers_id".$data['id']."')\">";
            $comment_display .= "</a>";
            $comment_display .= "<span class='over_link' id='comment_trackusers_id".$data['id']."'>".
                                 $userdata["comment"]."</span>";
         }
         $fourth_col .= "<strong>".$userdata['name']."&nbsp;".$comment_display."</strong>";
      }

      if ($data["groups_id"]) {
         $fourth_col .= "<br>".$data["groupname"];
      }
      echo displaySearchItem($output_type,$fourth_col,$item_num,$row_num,$align);

      // Fifth column
      $fifth_col = "";
      if ($data["users_id_assign"]>0) {
         $userdata = getUserName($data['users_id_assign'],2);
         $comment_display = "";
         if ($output_type==HTML_OUTPUT) {
            $comment_display  = "<a href='".$userdata["link"]."'>";
            $comment_display .= "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' ".
                                 "onmouseout=\"cleanhide('comment_trackassign".$data['id']."')\" ".
                                 "onmouseover=\"cleandisplay('comment_trackassign".$data['id']."')\">";
            $comment_display .= "</a>";
            $comment_display .= "<span class='over_link' id='comment_trackassign".$data['id']."'>".
                                 $userdata["comment"]."</span>";
         }
         $fifth_col = "<strong>".$userdata['name']."&nbsp;".$comment_display."</strong>";
      }

      if ($data["groups_id_assign"]>0) {
         if (!empty($fifth_col)) {
            $fifth_col .= "<br>";
         }
         $fifth_col .= getAssignName($data["groups_id_assign"],'Group',1);
      }

      if ($data["suppliers_id_assign"]>0) {
         if (!empty($fifth_col)) {
            $fifth_col .= "<br>";
         }
         $fifth_col .= getAssignName($data["suppliers_id_assign"],'Supplier',1);
      }
      echo displaySearchItem($output_type,$fifth_col,$item_num,$row_num,$align);

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

      echo displaySearchItem($output_type,$sixth_col,$item_num,$row_num,
                             ($is_deleted?" class='center deleted' ":$align));

      // Seventh column
      echo displaySearchItem($output_type,"<strong>".$data["catname"]."</strong>",$item_num,
                             $row_num,$align);

      // Eigth column
      $eigth_column = "<strong>".$data["name"]."</strong>&nbsp;";
      if ($output_type==HTML_OUTPUT) {
         $eigth_column .= "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' ".
                           "onmouseout=\"cleanhide('comment_tracking".$data["id"]."')\" ".
                           "onmouseover=\"cleandisplay('comment_tracking".$data["id"]."')\" >";
         $eigth_column .= "<span class='over_link' id='comment_tracking".$data["id"]."'>".
                           nl2br($data['content'])."</span>";
      }

      // Add link
      if ($_SESSION["glpiactiveprofile"]["interface"]=="central") {
         if ($job->canViewItem()) {
            $eigth_column = "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
                              $data["id"]."\">$eigth_column</a>";

            if ($followups && $output_type==HTML_OUTPUT) {
               $eigth_column .= showFollowupsShort($data["id"]);
            } else {
               $eigth_column .= "&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket",
                                                                            "1")).")";
            }
         }
      } else {
         $eigth_column = "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=".
                           "user&amp;id=".$data["id"]."\">$eigth_column</a>";
         if ($followups && $output_type==HTML_OUTPUT) {
            $eigth_column .= showFollowupsShort($data["id"]);
         } else {
            $eigth_column .= "&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket","1")).")";
         }
      }
      echo displaySearchItem($output_type,$eigth_column,$item_num,$row_num,$align_desc."width='300'");

      // Finish Line
      echo displaySearchEndLine($output_type);

   } else {
      echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG['joblist'][16]."</i></td></tr>";
   }
}


function showJobVeryShort($ID) {
   global $CFG_GLPI, $LANG;

   // Prints a job in short form
   // Should be called in a <table>-segment
   // Print links or not in case of user view
   // Make new job object and fill it from database, if success, print it
   $job = new Ticket;
   $viewusers = haveRight("user","r");
   if ($job->getFromDBwithData($ID,0)) {
      $bgcolor = $_SESSION["glpipriority_".$job->fields["priority"]];
      $rand = mt_rand();
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' bgcolor='$bgcolor' >ID : ".$job->fields["id"]."</td>";
      echo "<td class='center'>";

      if ($viewusers) {
         $userdata = getUserName($job->fields['users_id'],2);
         $comment_display  = "<a href='".$userdata["link"]."'>";
         $comment_display .= "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' ".
                              "onmouseout=\"cleanhide('comment_trackusers_id".$rand.$ID."')\" ".
                              "onmouseover=\"cleandisplay('comment_trackusers_id".$rand.$ID."')\">";
         $comment_display .= "</a>";
         $comment_display .= "<span class='over_link' id='comment_trackusers_id".$rand.$ID."'>".
                              $userdata["comment"]."</span>";

         echo "<strong>".$userdata['name']."&nbsp;".$comment_display."</strong>";
      } else {
         echo "<strong>".$job->getAuthorName()."</strong>";
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

      if ($_SESSION["glpiactiveprofile"]["interface"]=="central") {
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
               $job->fields["id"]."\">";
      } else {
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;id=".
               $job->fields["id"]."\">";
      }
      echo "<strong>".$job->fields["name"]."</strong>&nbsp;";
      echo "<img alt='".$LANG['joblist'][6]."' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' ".
            "onmouseout=\"cleanhide('comment_tracking".$rand.$job->fields["id"]."')\" ".
            "onmouseover=\"cleandisplay('comment_tracking".$rand.$job->fields["id"]."')\" >";
      echo "<span class='over_link' id='comment_tracking".$rand.$job->fields["id"]."'>".
            nl2br($job->fields['content'])."</span>";
      echo "</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;";
      echo "</td>";

      // Finish Line
      echo "</tr>";
   } else {
      echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG['joblist'][16]."</i></td></tr>";
   }
}


function getRealtime($realtime) {
   global $LANG;

   $output = "";
   $hour = floor($realtime);
   if ($hour>0) {
      $output .= $hour." ".$LANG['job'][21]." ";
   }
   $output .= round((($realtime-floor($realtime))*60))." ".$LANG['job'][22];
   return $output;
}


function getCommonSelectForTrackingSearch() {

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


function getCommonLeftJoinForTrackingSearch() {

   $FROM = "";
   if (count($_SESSION["glpiactiveentities"])>1) {
      $FROM .= " LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id` = `glpi_tickets`.`entities_id`) ";
   }

   return " LEFT JOIN `glpi_groups` ON (`glpi_tickets`.`groups_id` = `glpi_groups`.`id`)
            LEFT JOIN `glpi_ticketcategories`
               ON (`glpi_tickets`.`ticketcategories_id` = `glpi_ticketcategories`.`id`)
            $FROM";
}




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




function showPreviewAssignAction($output) {
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
function getAllTypesForHelpdesk() {
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
function isPossibleToAssignType($itemtype) {
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



?>
