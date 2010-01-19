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


function commonTrackingListHeader($output_type=HTML_OUTPUT) {
   global $LANG,$CFG_GLPI;

   // New Line for Header Items Line
   echo displaySearchNewLine($output_type);
   // $show_sort if
   $header_num=1;

   $items=array();

   $items[$LANG['joblist'][0]] = "glpi_tickets.status";
   $items[$LANG['common'][27]] = "glpi_tickets.date";
   $items[$LANG['common'][26]] = "glpi_tickets.date_mod";
   if (count($_SESSION["glpiactiveentities"])>1) {
      $items[$LANG['Menu'][37]] = "glpi_entities.completename";
   }
   $items[$LANG['joblist'][2]]   = "glpi_tickets.priority";
   $items[$LANG['job'][4]]       = "glpi_tickets.users_id";
   $items[$LANG['joblist'][4]]   = "glpi_tickets.users_id_assign";
   $items[$LANG['common'][1]]    = "glpi_tickets.itemtype,glpi_tickets.items_id";
   $items[$LANG['common'][36]]   = "glpi_ticketcategories.completename";
   $items[$LANG['common'][57]]   = "glpi_tickets.name";

   foreach ($items as $key => $val) {
      $issort = 0;
      $link = "";
      echo displaySearchHeaderItem($output_type,$key,$header_num,$link);
   }

   // End Line for column headers
   echo displaySearchEndLine($output_type);
}


function showCentralJobList($target,$start,$status="process",$showgrouptickets=true) {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("show_all_ticket","1")
       && !haveRight("show_assign_ticket","1")
       && !haveRight("create_ticket","1")) {
      return false;
   }

   $search_users_id = " (`glpi_tickets`.`users_id` = '".$_SESSION["glpiID"]."'
                         AND (`status` = 'new'
                              OR `status` = 'plan'
                              OR `status` = 'assign'
                              OR `status` = 'waiting'))
                       OR ";
   $search_assign = " `users_id_assign` = '".$_SESSION["glpiID"]."' ";
   if ($showgrouptickets) {
      $search_users_id = "";
      $search_assign = " 0 = 1 ";
      if (count($_SESSION['glpigroups'])) {
         $groups = implode("','",$_SESSION['glpigroups']);
         $search_assign = " `groups_id_assign` IN ('$groups') ";
         if (haveRight("show_group_ticket",1)) {
            $search_users_id = " (`groups_id` IN ('$groups')
                                  AND (`status` = 'new'
                                       OR `status` = 'plan'
                                       OR `status` = 'assign'
                                       OR `status` = 'waiting'))
                                OR ";
         }
      }
   }

   $query = "SELECT `id`
             FROM `glpi_tickets`";

   if ($status=="waiting") { // on affiche les tickets en attente
      $query .= "WHERE ($search_assign)
                       AND `status` ='waiting' ".
                       getEntitiesRestrictRequest("AND","glpi_tickets");

   } else { // on affiche les tickets planifiés ou assignés à glpiID
      $query .= "WHERE ($search_users_id (( $search_assign )
                                          AND (`status` ='plan'
                                               OR `status` = 'assign'))) ".
                       getEntitiesRestrictRequest("AND","glpi_tickets");
   }
   $query .= "ORDER BY `date_mod` DESC";

   $result = $DB->query($query);
   $numrows = $DB->numrows($result);

   $query .= " LIMIT ".intval($start).",".intval($_SESSION['glpilist_limit']);

   $result = $DB->query($query);
   $i = 0;
   $number = $DB->numrows($result);

   if ($number > 0) {
      echo "<table class='tab_cadrehov' style='width:420px'>";
      $link_common="&amp;status=$status&amp;reset=reset_before";
      $link="users_id_assign=mine$link_common";
      // Only mine
      if (!$showgrouptickets
          && (haveRight("show_all_ticket","1") || haveRight("show_assign_ticket",'1'))) {
         $link = "users_id_assign=".$_SESSION["glpiID"].$link_common;
      }

      echo "<tr><th colspan='5'>";
      if ($status=="waiting") {
         if ($showgrouptickets) {
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?$link\">".
                   $LANG['central'][16]."</a>";
         } else {
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?$link\">".
                   $LANG['central'][11]."</a>";
         }
      } else {
         echo $LANG['central'][17]."&nbsp;: ";
         if ($showgrouptickets) {
            if (haveRight("show_group_ticket",1)) {
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?group=-1&amp;users_id=".
                      $_SESSION["glpiID"]."&amp;reset=reset_before\">".$LANG['joblist'][5]."</a> / ";
            }
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?$link\">".
                   $LANG['joblist'][21]."</a>";
         } else {
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?users_id=".
                   $_SESSION["glpiID"]."&amp;reset=reset_before\">".$LANG['joblist'][5]."</a> / ".
                 "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?$link\">".
                   $LANG['joblist'][21]."</a>";
         }
      }
      echo "</th></tr>";
      echo "<tr><th></th>";
      echo "<th>".$LANG['job'][4]."</th>";
      echo "<th>".$LANG['common'][1]."</th>";
      echo "<th>".$LANG['joblist'][6]."</th></tr>";
      while ($i < $number) {
         $ID = $DB->result($result, $i, "id");
         showJobVeryShort($ID);
         $i++;
      }
      echo "</table>";
   } else {
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th>";
      if ($status=="waiting") {
         echo $LANG['central'][11];
      } else {
         echo $LANG['central'][9];
      }
      echo "</th></tr>";
      echo "</table>";
   }
}



/**
 * Display tickets for an item
 *
 * Will also display tickets of linked items
 *
 * @param $itemtype
 * @param $items_id
 *
 * @return nothing (display a table)
 */
function showJobListForItem($itemtype,$items_id) {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("show_all_ticket","1")) {
      return false;
   }
   if (!class_exists($itemtype)) {
      return false;
   }
   $item=new $itemtype();
   if (!$item->getFromDB($items_id)) {
      return false;
   }

   $query = "SELECT ".getCommonSelectForTrackingSearch()."
             FROM `glpi_tickets` ".getCommonLeftJoinForTrackingSearch()."
             WHERE (`items_id` = '$items_id'
                    AND `itemtype` = '$itemtype') ".
                   getEntitiesRestrictRequest("AND","glpi_tickets")."
             ORDER BY `glpi_tickets`.`date_mod` DESC
             LIMIT ".intval($_SESSION['glpilist_limit']);
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   // Ticket for the item
   echo "<div class='center'><table class='tab_cadre_fixe'>";
   if ($number > 0) {
      initNavigateListItems('Ticket',$item->getTypeName()." = ".$item->getName());

      echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;: &nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "all&amp;items_id=$items_id&amp;itemtype=$itemtype'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";
   } else {
      echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
   }

   // Link to open a new ticcket
   if ($items_id) {
      echo "<tr><td class='tab_bg_2 center' colspan='10'>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?items_id=$items_id&amp;itemtype=".
             "$itemtype\"><strong>".$LANG['joblist'][7]."</strong></a>";
      echo "</td></tr>";
   }

   // Ticket list
   if ($number > 0) {
      commonTrackingListHeader(HTML_OUTPUT);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems('Ticket',$data["id"]);
         showJobShort($data, 0);
      }
   }
   echo "</table></div><br>";

   // Tickets for linked items
   if ($subquery = $item->getSelectLinkedItem()) {
      $query = "SELECT ".getCommonSelectForTrackingSearch()."
                FROM `glpi_tickets` ".getCommonLeftJoinForTrackingSearch()."
                WHERE (`itemtype`,`items_id`) IN (" . $subquery . ")".
                      getEntitiesRestrictRequest(' AND ', 'glpi_tickets') . "
                ORDER BY `glpi_tickets`.`date_mod` DESC
                LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$LANG['joblist'][28]."</th></tr>";
      if ($number > 0) {
         commonTrackingListHeader(HTML_OUTPUT);

         while ($data=$DB->fetch_assoc($result)) {
            // addToNavigateListItems(TRACKING_TYPE,$data["id"]);
            showJobShort($data, 0);
         }
      } else {
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
      }
      echo "</table></div><br>";

   } // Subquery for linked item
}


function showJobListForSupplier($entID) {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("show_all_ticket","1")) {
      return false;
   }

   $query = "SELECT ".getCommonSelectForTrackingSearch()."
             FROM `glpi_tickets` ".getCommonLeftJoinForTrackingSearch()."
             WHERE (`suppliers_id_assign` = '$entID') ".
                   getEntitiesRestrictRequest("AND","glpi_tickets")."
             ORDER BY `glpi_tickets`.`date_mod` DESC
             LIMIT ".intval($_SESSION['glpilist_limit']);
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   if ($number > 0) {
      $ent=new Supplier();
      $ent->getFromDB($entID);
      initNavigateListItems('Ticket',$LANG['financial'][26]." = ".$ent->fields['name']);

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;:&nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "all&amp;suppliers_id_assign=$entID'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";

      commonTrackingListHeader(HTML_OUTPUT);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems('Ticket',$data["id"]);
         showJobShort($data, 0);
      }
      echo "</table></div>";
   } else {
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
      echo "</table>";
      echo "</div><br>";
   }
}


function showJobListForUser($userID) {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("show_all_ticket","1")) {
      return false;
   }

   $query = "SELECT ".getCommonSelectForTrackingSearch()."
             FROM `glpi_tickets` ".getCommonLeftJoinForTrackingSearch()."
             WHERE (`glpi_tickets`.`users_id` = '$userID') ".
                   getEntitiesRestrictRequest("AND","glpi_tickets")."
             ORDER BY `glpi_tickets`.`date_mod` DESC
             LIMIT ".intval($_SESSION['glpilist_limit']);
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   if ($number > 0) {
      $user=new User();
      $user->getFromDB($userID);
      initNavigateListItems('Ticket',$LANG['common'][34]." = ".$user->getName());

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;: &nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "all&amp;users_id=$userID'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";

      commonTrackingListHeader(HTML_OUTPUT);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems('Ticket',$data["id"]);
         showJobShort($data, 0);
      }
      echo "</table></div>";
   } else {
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
      echo "</table>";
      echo "</div><br>";
   }
}


function showNewJobList() {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("show_all_ticket","1")) {
      return false;
   }

   $query = "SELECT ".getCommonSelectForTrackingSearch()."
             FROM `glpi_tickets` ".getCommonLeftJoinForTrackingSearch()."
             WHERE `status` = 'new' ".
                   getEntitiesRestrictRequest("AND","glpi_tickets")."
             ORDER BY `glpi_tickets`.`date_mod` DESC
             LIMIT ".intval($_SESSION['glpilist_limit']);
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   if ($number > 0) {
      initNavigateListItems('Ticket');

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$LANG['central'][10]." ($number)&nbsp;: &nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "new'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";

      commonTrackingListHeader(HTML_OUTPUT);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems('Ticket',$data["id"]);
         showJobShort($data, 0);
      }
      echo "</table></div>";
   } else {
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
      echo "</table>";
      echo "</div><br>";
   }
}


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
