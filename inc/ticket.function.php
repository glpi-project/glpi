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

/**
 * get the allowed Soft options for the tickets list
 *
 * @return array of options (title => field)
 */
function &getTrackingSortOptions() {
   global $LANG,$CFG_GLPI;
   static $items=array();

   if (!count($items)) {
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
   }
   return ($items);
}


function commonTrackingListHeader($output_type=HTML_OUTPUT,$target="",$parameters="",$sort="",
                                  $order="",$nolink=false) {
   global $LANG,$CFG_GLPI;

   // New Line for Header Items Line
   echo displaySearchNewLine($output_type);
   // $show_sort if
   $header_num=1;

   // Force nolink on ajax :
   if (strpos($target,'ajax')>0) {
      $nolink=true;
   }

   foreach (getTrackingSortOptions() as $key => $val) {
      $issort = 0;
      $link = "";
      if (!$nolink) {
         if ($sort==$val) {
            $issort=1;
         }
         $link = $target."?".$parameters."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;sort=$val";
         if (strpos($target,"helpdesk.public.php")) {
            $link .= "&amp;show=user";
         }
      }
      echo displaySearchHeaderItem($output_type,$key,$header_num,$link,$issort,$order);
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


function showCentralJobCount() {
   global $DB,$CFG_GLPI, $LANG;

   // show a tab with count of jobs in the central and give link
   if (!haveRight("show_all_ticket","1")) {
      return false;
   }

   $query = "SELECT `status`, count(*) AS COUNT
             FROM `glpi_tickets` ".
             getEntitiesRestrictRequest("WHERE","glpi_tickets")."
             GROUP BY `status`";
   $result = $DB->query($query);

   $status = array('new'=>0,
                   'assign'=>0,
                   'plan'=>0,
                   'waiting'=>0);

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_assoc($result)) {
         $status[$data["status"]] = $data["COUNT"];
      }
   }
   echo "<table class='tab_cadrehov' >";
   echo "<tr><th colspan='2'>";
   echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?status=process&amp;reset=reset_before\">".
          $LANG['title'][10]."</a></th></tr>";
   echo "<tr><th>".$LANG['title'][28]."</th><th>".$LANG['tracking'][29]."</th></tr>";
   echo "<tr class='tab_bg_2'>";
   echo "<td>";
   echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?status=new&amp;reset=reset_before\">".
          $LANG['tracking'][30]."</a> </td>";
   echo "<td>".$status["new"]."</td></tr>";
   echo "<tr class='tab_bg_2'>";
   echo "<td>";
   echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?status=assign&amp;reset=reset_before\">".
          $LANG['tracking'][31]."</a></td>";
   echo "<td>".$status["assign"]."</td></tr>";
   echo "<tr class='tab_bg_2'>";
   echo "<td>";
   echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?status=plan&amp;reset=reset_before\">".
          $LANG['tracking'][32]."</a></td>";
   echo "<td>".$status["plan"]."</td></tr>";
   echo "<tr class='tab_bg_2'>";
   echo "<td>";
   echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?status=waiting&amp;reset=reset_before\">".
          $LANG['joblist'][26]."</a></td>";
   echo "<td>".$status["waiting"]."</td></tr>";
   echo "</table><br>";
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
   $ci = new CommonItem();
   if (!$ci->getFromDB($itemtype,$items_id)) {
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
      initNavigateListItems(TRACKING_TYPE,$ci->getType()." = ".$ci->getName());

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
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.php?items_id=$items_id&amp;itemtype=".
             "$itemtype\"><strong>".$LANG['joblist'][7]."</strong></a>";
      echo "</td></tr>";
   }

   // Ticket list
   if ($number > 0) {
      commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"id=$items_id","","",true);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems(TRACKING_TYPE,$data["id"]);
         showJobShort($data, 0);
      }
   }
   echo "</table></div><br>";

   // Tickets for linked items
   if ($subquery = $ci->obj->getSelectLinkedItem()) {
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
         commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"id=$items_id","","",true);

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
      initNavigateListItems(TRACKING_TYPE,$LANG['financial'][26]." = ".$ent->fields['name']);

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;:&nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "all&amp;suppliers_id_assign=$entID'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";

      commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"","","",true);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems(TRACKING_TYPE,$data["id"]);
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
      initNavigateListItems(TRACKING_TYPE,$LANG['common'][34]." = ".$user->getName());

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$number." ".$LANG['job'][8]."&nbsp;: &nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "all&amp;users_id=$userID'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";

      commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"","","",true);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems(TRACKING_TYPE,$data["id"]);
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
      initNavigateListItems(TRACKING_TYPE);

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='10'>".$LANG['central'][10]." ($number)&nbsp;: &nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?reset=reset_before&amp;status=".
            "new'>".$LANG['buttons'][40]."</a>";
      echo "</th></tr>";

      commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"","","",true);

      while ($data=$DB->fetch_assoc($result)) {
         addToNavigateListItems(TRACKING_TYPE,$data["id"]);
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
                        alt='".getStatusName($data["status"])."' title='".
                        getStatusName($data["status"])."'>";
      } else {
         $first_col .= " - ".getStatusName($data["status"]);
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
         $fifth_col .= getAssignName($data["groups_id_assign"],GROUP_TYPE,1);
      }

      if ($data["suppliers_id_assign"]>0) {
         if (!empty($fifth_col)) {
            $fifth_col .= "<br>";
         }
         $fifth_col .= getAssignName($data["suppliers_id_assign"],ENTERPRISE_TYPE,1);
      }
      echo displaySearchItem($output_type,$fifth_col,$item_num,$row_num,$align);

      $ci = new CommonItem();
      $ci->getFromDB($data["itemtype"],$data["items_id"]);
      // Sixth Colum
      $sixth_col = "";

      $sixth_col .= $ci->getType();
      if ($data["itemtype"]>0 && $data["items_id"]>0) {
         $sixth_col .= "<br><strong>";
         if (haveTypeRight($data["itemtype"],"r")) {
            $sixth_col .= $ci->getLink($output_type==HTML_OUTPUT);
         } else {
            $sixth_col .= $ci->getNameID();
         }
         $sixth_col .= "</strong>";
      }
      echo displaySearchItem($output_type,$sixth_col,$item_num,$row_num,$align." ".
                             ($ci->getField("is_deleted")?" class='deleted' ":""));

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
         if ($job->canView()) {
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
         echo "<br>".getDropdownName("glpi_groups",$job->fields["groups_id"]);
      }
      echo "</td>";

      if (haveTypeRight($job->fields["itemtype"],"r")) {
         echo "<td class='center";
         if ($job->hardwaredatas->getField("is_deleted")) {
            echo " tab_bg_1_2";
         }
         echo "'>";
         echo $job->hardwaredatas->getType()."<br>";
         echo "<strong>".$job->hardwaredatas->getLink()."</strong>";
         echo "</td>";
      } else {
         echo "<td class='center' >".$job->hardwaredatas->getType()."<br><strong>".
               $job->hardwaredatas->getNameID()."</strong></td>";
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


function getTrackingFormFields($_POST) {

   $params = array('group'                => 0,
                   'itemtype'             => 0,
                   'users_id_assign'      => 0,
                   'groups_id_assign'     => 0,
                   'ticketcategories_id' => 0,
                   'priority'             => 3,
                   'hour'                 => 0,
                   'minute'               => 0,
                   'requesttypes_id'      => 1,
                   'name'                 => '',
                   'content'              => '',
                   'target'               => '');

   $params_ajax = array();
   foreach ($params as $param => $default_value) {
      $params_ajax[$param] = (isset($_POST[$param])?$_POST[$param]:$default_value);
   }
   return $params_ajax;
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


function searchSimpleFormTracking($extended=0,$target,$status="all",$tosearch='',$search='',
                                  $group=-1,$showfollowups=0,$ticketcategories_id=0) {
   global $CFG_GLPI,  $LANG;

   echo "<div class='center' >";
   echo "<form method='get' name='form' action='$target'>";
   echo "<table class='tab_cadre_fixe'>";

   echo "<tr><th colspan='5' class='middle'><div class='relative'>";
   echo "<span>".$LANG['search'][0]."</span>";
   $parm = "";
   if ($_SESSION["glpiactiveprofile"]["interface"]=="helpdesk") {
      $parm = "show=user&amp;";
   }

   if ($extended) {
      echo "<span class='tracking_right'><a href='$target?".$parm."extended=0'>";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''>".
            $LANG['buttons'][36]."</a></span>";
   } else {
      echo "<span class='tracking_right'><a href='$target?".$parm."extended=1'>";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''>".
            $LANG['buttons'][35]."</a></span>";
   }
   echo "</div></th></tr>";

   echo "<tr class='tab_bg_1 center'>";
   echo "<td colspan='1' >".$LANG['joblist'][0]."&nbsp;:&nbsp;";
   dropdownStatus('status',$status,1);
   echo "</td>";

   if (haveRight("show_group_ticket",1)) {
      echo "<td class='center'>";
      echo "<select name='group'>";
      echo "<option value='-1' ".($group==-1?" selected ":"").">".$LANG['common'][66]."</option>";
      echo "<option value='0' ".($group==0?" selected ":"").">".$LANG['joblist'][1]."</option>";
      echo "</select>";
      echo "</td>";
   } else {
      echo '<td>&nbsp;</td>';
   }

   echo "<td class='center' colspan='2'>".$LANG['reports'][59]."&nbsp;:&nbsp;";
   dropdownYesNo('showfollowups',$showfollowups);
   echo "</td>";

   if ($extended) {
      echo "<td>".$LANG['common'][36]."&nbsp;:&nbsp;";
      dropdownValue("glpi_ticketcategories","ticketcategories_id",$ticketcategories_id);
      echo "</td></tr>";
      echo "<tr class='tab_bg_1 center'>";
      echo "<td class='center' colspan='2'>";
      $elts = array('name'                   => $LANG['common'][57],
                    'content'                => $LANG['joblist'][6],
                    'followup'               => $LANG['Menu'][5],
                    'name_content'           => $LANG['common'][57]." / ".$LANG['joblist'][6],
                    'name_content_followup'  => $LANG['common'][57]." / ".$LANG['joblist'][6]." / ".
                                                $LANG['Menu'][5],
                    'id'                     => "id");
      echo "<select name='tosearch'>";
      foreach ($elts as $key => $val) {
         $selected = "";
         if ($tosearch==$key) {
            $selected = "selected";
         }
         echo "<option value='$key' $selected>$val</option>";
      }
      echo "</select>";

      echo "&nbsp;".$LANG['search'][2]."&nbsp;";
      echo "<input type='text' size='15' name='search' value='".stripslashes($search)."'>";
      echo "</td>";
      echo "<td colspan='2'>&nbsp;</td>";
   }

   echo "<td class='center' colspan='1'>";
   echo" <input type='submit' value'".$LANG['buttons'][0]."' class='submit'></td>";
   echo "</tr></table>";
   echo "<input type='hidden' name='start' value='0'>";
   echo "<input type='hidden' name='extended' value='$extended'>";

   // helpdesk case
   if (strpos($target,"helpdesk.public.php")) {
      echo "<input type='hidden' name='show' value='user'>";
   }
   echo "</form></div>";
}


function searchFormTracking($extended=0,$target,$start="",$status="new",$tosearch="",$search="",
                            $users_id=0,$group=0,$showfollowups=0,$ticketcategories_id=0,
                            $users_id_assign=0,$suppliers_id_assign=0,$groups_id_assign=0,
                            $priority=0,$requesttypes_id=0,$items_id=0,$itemtype=0,$field="",
                            $contains="",$date1="",$date2="",$computers_search="",$enddate1="",
                            $enddate2="",$datemod1="",$datemod2="",$recipient=0) {
   global $CFG_GLPI,  $LANG, $DB;

   // Print Search Form
   if (!haveRight("show_all_ticket","1")) {
      if (haveRight("show_assign_ticket","1")) {
         $users_id_assign = 'mine';
      } else if ($users_id==0 && $users_id_assign==0) {
         if (!haveRight("own_ticket","1")) {
            $users_id = $_SESSION["glpiID"];
         } else {
            $users_id_assign = $_SESSION["glpiID"];
         }
      }
   }

   if ($extended) {
      $option["comp.id"]                                 = $LANG['common'][2];
      $option["comp.name"]                               = $LANG['common'][16];
      $option["glpi_locations.name"]                     = $LANG['common'][15];
      $option["glpi_computertypes.name"]                = $LANG['common'][17];
      $option["glpi_computermodels.name"]               = $LANG['common'][22];
      $option["glpi_operatingsystems.name"]              = $LANG['computers'][9];
      $option["glpi_operatingsystemversions.name"]      = $LANG['computers'][52];
      $option["glpi_operatingsystemservicepacks.name"]  = $LANG['computers'][53];
      $option["glpi_autoupdatesystems.name"]             = $LANG['computers'][51];
      $option["glpi_manufacturers.name"]                 = $LANG['common'][5];
      $option["glpi_deviceprocessors.designation"]      = $LANG['computers'][21];
      $option["comp.serial"]                             = $LANG['common'][19];
      $option["comp.otherserial"]                        = $LANG['common'][20];
      $option["glpi_devicememories.designation"]        = $LANG['computers'][23];
      $option["glpi_devicenetworkcards.designation"]    = $LANG['setup'][9];
      $option["glpi_devicesoundcards.designation"]      = $LANG['devices'][7];
      $option["glpi_devicegraphiccards.designation"]    = $LANG['devices'][2];
      $option["glpi_devicemotherboards.designation"]    = $LANG['devices'][5];
      $option["glpi_deviceharddrives.designation"]      = $LANG['computers'][36];
      $option["comp.comment"]                            = $LANG['common'][25];
      $option["comp.contact"]                            = $LANG['common'][18];
      $option["comp.contact_num"]                        = $LANG['common'][21];
      $option["comp.date_mod"]                           = $LANG['common'][26];
      $option["glpi_networkports.ip"]                    = $LANG['networking'][14];
      $option["glpi_networkports.mac"]                   = $LANG['networking'][15];
      $option["glpi_netpoints.name"]                     = $LANG['networking'][51];
      $option["glpi_suppliers.name"]                     = $LANG['common'][5];
      $option["resptech.name"]                           =$LANG['common'][10];
   }
   echo "<form method='get' name='form' action='$target'>";

   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='6' class='middle'><div class='relative'>";
   echo "<span>".$LANG['search'][0]."</span>";
   if ($extended) {
      echo "<span class='tracking_right'><a href='$target?extended=0'>";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''>".
            $LANG['buttons'][36]."</a></span>";
   } else {
      echo "<span class='tracking_right'><a href='$target?extended=1'>";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''>".
            $LANG['buttons'][35]."</a></span>";
   }
   echo "</div></th></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td colspan='1' class='center'>".$LANG['joblist'][0]."&nbsp;:<br>";
   dropdownStatus('status',$status,1);
   echo "</td>";

   echo "<td colspan='1' class='center'>".$LANG['joblist'][2]."&nbsp;:<br>";
   Ticket::dropdownPriority("priority",$priority,1);
   echo "</td>";

   echo "<td colspan='2' class='center'>".$LANG['common'][36]."&nbsp;:<br>";
   dropdownValue("glpi_ticketcategories","ticketcategories_id",$ticketcategories_id);
   echo "</td>";

   echo "<td colspan='2' class='center'>".$LANG['job'][44]."&nbsp;:<br>";
   getDropdownName('glpi_requesttypes',"requesttypes_id",$requesttypes_id);
   echo "</td>";
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='center' colspan='2'>";
   echo "<table border='0'><tr><td>".$LANG['common'][1]."&nbsp;:</td><td>";
   dropdownAllItems("items_id",$itemtype,$items_id,-1,array_keys(getAllTypesForHelpdesk()));
   echo "</td></tr></table>";

   echo "</td>";

   echo "<td  colspan='2' class='center'>".$LANG['job'][4]."&nbsp;:<br>";
   dropdownUsersTracking("users_id",$users_id,"users_id");

   echo "<br>".$LANG['common'][35]."&nbsp;: ";
   dropdownValue("glpi_groups","group",$group);
   echo "</td>";

   echo "<td colspan='2' class='center'>".$LANG['job'][5]."&nbsp;:<br>";
   if (strcmp($users_id_assign,"mine")==0) {
      echo formatUserName($_SESSION["glpiID"],$_SESSION["glpiname"],$_SESSION["glpirealname"],
                          $_SESSION["glpifirstname"]);
      // Display the group if unique
      if (count($_SESSION['glpigroups'])==1) {
         echo "<br>".getDropdownName("glpi_groups",current($_SESSION['glpigroups']));
      } else if (count($_SESSION['glpigroups'])>1) { // Display limited dropdown
         echo "<br>";
         $groups[0]='-----';
         foreach (getDropdownArrayNames('glpi_groups',
                                        $_SESSION['glpigroups']) as $tmpgroupid => $tmpgroupname) {
            $groups[$tmpgroupid] = $tmpgroupname;
         }
         dropdownArrayValues('groups_id_assign',$groups,$groups_id_assign);
      }
   } else {
      dropdownUsers("users_id_assign",$users_id_assign,"own_ticket",1);
      echo "<br>".$LANG['common'][35]."&nbsp;: ";
      dropdownValue("glpi_groups","groups_id_assign",$groups_id_assign);
      echo "<br>".$LANG['financial'][26]."&nbsp;:&nbsp;";
      dropdownValue("glpi_suppliers","suppliers_id_assign",$suppliers_id_assign);
   }

   echo "</td></tr>";

   if ($extended) {
      echo "<tr class='tab_bg_1'><td colspan='6' class='center'>".$LANG['job'][3]."&nbsp;:";
      dropdownUsersTracking("users_id_recipient",$recipient,"users_id_recipient");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center' colspan='6'>";
      $selected = "";
      if ($computers_search) {
         $selected = "checked";
      }
      echo "<input type='checkbox' name='only_computers' value='1' $selected>".
            $LANG['reports'][24].":&nbsp;";
      echo "<input type='text' size='15' name='contains' value=\"". stripslashes($contains) ."\" >";
      echo "&nbsp;".$LANG['search'][10]."&nbsp;";

      echo "<select name='field' size='1'>";
      echo "<option value='all' ";
      if ($field == "all") {
         echo "selected";
      }
      echo ">".$LANG['common'][66]."</option>";
      reset($option);
      foreach ($option as $key => $val) {
         echo "<option value='$key'";
         if ($key == $field) {
            echo "selected";
         }
         echo ">'$val'</option>\n";
      }
      echo "</select>&nbsp;";

      echo "</td></tr>";
   }
   if ($extended) {
      echo "<tr class='tab_bg_1'><td class='right'>".$LANG['reports'][60]."&nbsp;:</td>";
      echo "<td class='center' colspan='2'>".$LANG['search'][8]."&nbsp;:</td><td>";
      showDateFormItem("date1",$date1);
      echo "</td><td class='center'>".$LANG['search'][9]."&nbsp;:</td><td>";
      showDateFormItem("date2",$date2);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td class='right'>".$LANG['reports'][61]."&nbsp;:</td>";
      echo "<td class='center' colspan='2'>".$LANG['search'][8]."&nbsp;:</td><td>";
      showDateFormItem("enddate1",$enddate1);
      echo "</td><td class='center'>".$LANG['search'][9]."&nbsp;:</td><td>";
      showDateFormItem("enddate2",$enddate2);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td class='right'>".$LANG['common'][26]."&nbsp;:</td>";
      echo "<td class='center' colspan='2'>".$LANG['search'][8]."&nbsp;:</td><td>";
      showDateFormItem("datemod1",$datemod1);
      echo "</td><td class='center'>".$LANG['search'][9]."&nbsp;:</td><td>";
      showDateFormItem("datemod2",$datemod2);
      echo "</td></tr>";
   }
   echo "<tr class='tab_bg_1'>";
   echo "<td class='center' colspan='2'>";
   $elts = array('name'                   => $LANG['common'][57],
                 'content'                => $LANG['joblist'][6],
                 'followup'               => $LANG['Menu'][5],
                 'name_content'           => $LANG['common'][57]." / ".$LANG['joblist'][6],
                 'name_content_followup'  => $LANG['common'][57]." / ".$LANG['joblist'][6]." / ".
                                             $LANG['Menu'][5],
                 'id'                     => "ID");
   echo "<select name='tosearch'>";
   foreach ($elts as $key => $val) {
      $selected = "";
      if ($tosearch==$key) {
         $selected = "selected";
      }
      echo "<option value='$key' $selected>$val</option>";
   }
   echo "</select>";

   echo "&nbsp;".$LANG['search'][2]."&nbsp;";
   echo "<input type='text' size='15' name='search' value=\"".stripslashes($search)."\">";
   echo "</td>";

   echo "<td class='center' colspan='2'>".$LANG['reports'][59]."&nbsp;:&nbsp;";
   dropdownYesNo('showfollowups',$showfollowups);
   echo "</td>";

   echo "<td class='center' colspan='1'>";
   echo "<input type='submit' value='".$LANG['buttons'][0]."' class='submit'></td>";

   echo "<td class='center' colspan='1'>";
   echo "<input type='submit' name='reset' value='".$LANG['buttons'][16]."' class='submit'>&nbsp;";
   Bookmark::showSaveButton(BOOKMARK_SEARCH,TRACKING_TYPE);
   // Needed for bookmark
   echo "<input type='hidden' name='extended' value='$extended'>";
   echo "</td>";
   echo "</tr></table>";
   echo "<input type='hidden' name='start' value='0'>";
   echo "</form>";
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


function showTrackingList($target,$start="",$sort="",$order="",$status="new",$tosearch="",
                          $search="",$users_id=0,$group=0,$showfollowups=0,$ticketcategories_id=0,
                          $users_id_assign=0,$suppliers_id_assign=0,$groups_id_assign=0,$priority=0,
                          $requesttypes_id=0,$items_id=0,$itemtype=0,$field="",$contains="",$date1="",
                          $date2="",$computers_search="",$enddate1="",$enddate2="",$datemod1="",
                          $datemod2="",$recipient=0) {
   global $DB,$CFG_GLPI, $LANG;

   // Lists all Tickets, needs $show which can have keywords
   // (individual, unassigned) and $contains with search terms.
   // If $items_id is given, only jobs for a particular machine are listed.
   // group = 0 : not use
   // group = -1 : groups of the users_id if session variable OK
   // group > 0 : specific group

   $candelete = haveRight("delete_ticket","1");
   $canupdate = haveRight("update_ticket","1");

   if (!haveRight("show_all_ticket","1")) {
      if (haveRight("show_assign_ticket","1")) {
         $users_id_assign = 'mine';
      } else if ($users_id==0 && $users_id_assign==0) {
         if (!haveRight("own_ticket","1")) {
            $users_id = $_SESSION["glpiID"];
         } else {
            $users_id_assign = $_SESSION["glpiID"];
         }
      }
   }

   // Reduce computer list
   if ($computers_search && !empty($contains)) {
      $SEARCH = makeTextSearch($contains);
      // Build query
      if ($field == "all") {
         $wherecomp = " (";
         $query = "SHOW COLUMNS
                   FROM `glpi_computers`";
         $result = $DB->query($query);
         $i = 0;
         $exclude_fields = array('domains_id','entities_id','networks_id',
                                 'users_id','users_id_tech','states_id');
         while ($line = $DB->fetch_array($result)) {
            if (!in_array($line["Field"],$exclude_fields)) {
               if ($i != 0) {
                  $wherecomp .= " OR ";
               }
               $table = getTableNameForForeignKeyField($line["Field"]);

               if (!empty($table)) {
                  $wherecomp .= "$table.`name` $SEARCH" ;
               } else {
                  $wherecomp .= "comp.".$line["Field"] . $SEARCH;
               }
               $i++;
            }
         }
         // Add devices
         $wherecomp .= " OR `glpi_devicemotherboards`.`designation` $SEARCH
                         OR `glpi_deviceprocessors`.`designation` $SEARCH
                         OR `glpi_devicegraphiccards`.`designation` $SEARCH
                         OR `glpi_deviceharddrives`.`designation` $SEARCH
                         OR `glpi_devicenetworkcards`.`designation` $SEARCH
                         OR `glpi_devicememories`.`designation` $SEARCH
                         OR `glpi_devicesoundcards`.`designation` $SEARCH
                         OR `glpi_networkports`.`ip` $SEARCH
                         OR `glpi_networkports`.`mac` $SEARCH
                         OR `glpi_netpoints`.`name` $SEARCH
                         OR `glpi_suppliers`.`name` $SEARCH
                         OR `resptech`.`name` $SEARCH)";
      } else {
         $wherecomp = "($field $SEARCH)";
      }
   }
   if (!$start) {
      $start = 0;
   }
   $SELECT = "SELECT ".getCommonSelectForTrackingSearch();
   $FROM = " FROM `glpi_tickets` ".
             getCommonLeftJoinForTrackingSearch();

   if ($search!="" && strpos($tosearch,"followup")!==false) {
      $FROM .= " LEFT JOIN `glpi_ticketfollowups`
                     ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`)";
   }
   $where = " WHERE ";

   switch ($status) {
      case "new" :
         $where .= "`glpi_tickets`.`status` = 'new'";
         break;

      case "notold" :
         $where .= "(`glpi_tickets`.`status` = 'new'
                     OR `glpi_tickets`.`status` = 'plan'
                     OR `glpi_tickets`.`status` = 'assign'
                     OR `glpi_tickets`.`status` = 'waiting')";
         break;

      case "old" :
         $where .= "(`glpi_tickets`.`status` = 'old_done'
                     OR `glpi_tickets`.`status` = 'old_notdone')";
         break;

      case "process" :
         $where .= "(`glpi_tickets`.`status` = 'plan'
                     OR `glpi_tickets`.`status` = 'assign')";
         break;

      case "waiting" :
         $where .= "(`glpi_tickets`.`status` = 'waiting')";
         break;

      case "old_done" :
         $where .= "(`glpi_tickets`.`status` = 'old_done')";
         break;

      case "old_notdone" :
         $where .= "(`glpi_tickets`.`status` = 'old_notdone')";
         break;

       case "assign" :
         $where .= "(`glpi_tickets`.`status` = 'assign')";
         break;

      case "plan" :
         $where .= "(`glpi_tickets`.`status` = 'plan')";
         break;

      default :
         $where .= " (1)";
   }

   if ($ticketcategories_id > 0) {
      $where .= " AND ".getRealQueryForTreeItem("glpi_ticketcategories",$ticketcategories_id,
                                                "glpi_tickets.ticketcategories_id");
   }

   if ($date1 || $date2) {
      $where .= " AND " .getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
   }
   if ($enddate1 || $enddate1) {
      $where .= " AND " .getDateRequest("`glpi_tickets`.`closedate`",$enddate1,$enddate2);
   }
   if ($datemod1 || $datemod2) {
      $where .= " AND " .getDateRequest("`glpi_tickets`.`date_mod`",$datemod1,$datemod2);
   }

   if ($recipient!=0) {
      $where .= " AND `glpi_tickets`.`users_id_recipient` = '$recipient'";
   }
   if ($itemtype!=0) {
      $where .= " AND `glpi_tickets`.`itemtype` = '$itemtype'";
   }
   if ($items_id!=0 && $itemtype!=0) {
      $where .= " AND `glpi_tickets`.`items_id` = '$items_id'";
   }
   $search_users_id=false;

   if ($group>0) {
      $where .= " AND `glpi_tickets`.`groups_id` = '$group'";
   } else if ($group==-1 && $users_id!=0 && haveRight("show_group_ticket",1)) {
      // Get Author group's
      if (count($_SESSION["glpigroups"])) {
         $groups = implode("','",$_SESSION['glpigroups']);
         $where .= " AND (`glpi_tickets`.`groups_id` IN ('$groups') ";

         if ($users_id!=0) {
            $where .= " OR `glpi_tickets`.`users_id` = '$users_id'";
            $search_users_id = true;
         }
         $where .= ")";
      }
   }

   if ($users_id!=0 && !$search_users_id) {
      $where .= " AND `glpi_tickets`.`users_id` = '$users_id' ";
   }

   if (strcmp($users_id_assign,"mine")==0) {
      // Case : central acces with show_assign_ticket but without show_all_ticket
      $search_assign =" (`glpi_tickets`.`users_id_assign` = '".$_SESSION["glpiID"]."'
                         OR `glpi_tickets`.`groups_id_assign` ";
      if (count($_SESSION['glpigroups'])) {
         if ($groups_id_assign>0) {
            $search_assign .= "= '$groups_id_assign'";
         } else {
            $groups = implode("','",$_SESSION['glpigroups']);
            $search_assign .= "IN ('$groups')";
         }
      }

      // Display mine but also the ones which i am the users_id
      $users_id_part = "";
      if (!$search_users_id && isset($_SESSION['glpiID'])) {
         $users_id_part .= " OR `glpi_tickets`.`users_id` = '".$_SESSION['glpiID']."'";

         // Get Author group's
         if (haveRight("show_group_ticket",1) && count($_SESSION["glpigroups"])) {
            $groups = implode("','",$_SESSION['glpigroups']);
            $users_id_part .= " OR `glpi_tickets`.`groups_id` IN ('$groups') ";
         }
      }
      $search_assign .= ")";
      $where .= " AND ($search_assign $users_id_part ) ";

   } else {
      if ($suppliers_id_assign!=0) {
         $where .= " AND `glpi_tickets`.`suppliers_id_assign` = '$suppliers_id_assign'";
      }
      if ($users_id_assign!=0) {
         $where .= " AND `glpi_tickets`.`users_id_assign` = '$users_id_assign'";
      }
      if ($groups_id_assign!=0) {
         $where .= " AND `glpi_tickets`.`groups_id_assign` = '$groups_id_assign'";
      }
   }

   if ($requesttypes_id!=0) {
      $where .= " AND `glpi_tickets`.`requesttypes_id` = '$requesttypes_id'";
   }
   if ($priority>0) {
      $where .= " AND `glpi_tickets`.`priority` = '$priority'";
   }
   if ($priority<0) {
      $where .= " AND `glpi_tickets`.`priority` >= '".abs($priority)."'";
   }

   if ($search!="") {
      $SEARCH2 = makeTextSearch($search);
      if ($tosearch=="id") {
         $where .= " AND `glpi_tickets`.`id` = '".$search."'";
      }
      $TMPWHERE = "";
      $first = true;
      if (strpos($tosearch,"followup")!== false) {
         $first = false;
         $TMPWHERE.= " OR `glpi_ticketfollowups`.`content` $SEARCH2 ";
      }
      if (strpos($tosearch,"name")!== false) {
         if ($first) {
            $first = false;
         } else {
            $TMPWHERE .= " OR ";
         }
         $TMPWHERE .= "`glpi_tickets`.`name` $SEARCH2 ";
      }
      if (strpos($tosearch,"content")!== false) {
         if ($first) {
            $first = false;
         } else {
            $TMPWHERE .= " OR ";
         }
         $TMPWHERE .= "`glpi_tickets`.`content` $SEARCH2 ";
      }

      if (!empty($TMPWHERE)) {
         $where .= " AND ($TMPWHERE) ";
      }
   }

   $where .= getEntitiesRestrictRequest(" AND","glpi_tickets");

   if (!empty($wherecomp)) {
      $where .= " AND `glpi_tickets`.`itemtype` = '1'
                  AND `glpi_tickets`.`items_id`
                          IN (SELECT `comp`.`id`
                              FROM `glpi_computers` AS comp
                              LEFT JOIN `glpi_computers_devices` AS gcdev
                                    ON (comp.`id` = gcdev.`computers_id`)
                              LEFT JOIN `glpi_devicemotherboards`
                                    ON (`glpi_devicemotherboards`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".MOBOARD_DEVICE."')
                              LEFT JOIN `glpi_deviceprocessors`
                                    ON (`glpi_deviceprocessors`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".PROCESSOR_DEVICE."')
                              LEFT JOIN `glpi_devicegraphiccards`
                                    ON (`glpi_devicegraphiccards`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".GFX_DEVICE."')
                              LEFT JOIN `glpi_deviceharddrives`
                                    ON (`glpi_deviceharddrives`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".HDD_DEVICE."')
                              LEFT JOIN `glpi_devicenetworkcards`
                                    ON (`glpi_devicenetworkcards`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".NETWORK_DEVICE."')
                              LEFT JOIN `glpi_devicememories`
                                    ON (`glpi_devicememories`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".RAM_DEVICE."')
                              LEFT JOIN `glpi_devicesoundcards`
                                    ON (`glpi_devicesoundcards`.`id` = gcdev.`devices_id`
                                        AND gcdev.`devicetype` = '".SND_DEVICE."')
                              LEFT JOIN `glpi_networkports`
                                    ON (comp.`id` = `glpi_networkports`.`items_id`
                                        AND `glpi_networkports`.`itemtype` = '1')
                              LEFT JOIN `glpi_netpoints`
                                    ON (`glpi_netpoints`.`id` = `glpi_networkports`.`netpoints_id`)
                              LEFT JOIN `glpi_operatingsystems`
                                    ON (`glpi_operatingsystems`.`id` = comp.`operatingsystems_id`)
                              LEFT JOIN `glpi_operatingsystemversions`
                                    ON (`glpi_operatingsystemversions`.`id`
                                         = comp.`operatingsystemversions_id`)
                              LEFT JOIN `glpi_operatingsystemservicepacks`
                                    ON (`glpi_operatingsystemservicepacks`.`id`
                                         = comp.`operatingsystemservicepacks_id`)
                              LEFT JOIN `glpi_autoupdatesystems`
                                    ON (`glpi_autoupdatesystems`.`id` = comp.`autoupdatesystems_id`)
                              LEFT JOIN `glpi_manufacturers`
                                    ON (`glpi_manufacturers`.`id` = comp.`manufacturers_id`)
                              LEFT JOIN `glpi_locations`
                                    ON (`glpi_locations`.`id` = comp.`locations_id`)
                              LEFT JOIN `glpi_computermodels`
                                    ON (`glpi_computermodels`.`id` = comp.`computermodels_id`)
                              LEFT JOIN `glpi_computertypes`
                                    ON (`glpi_computertypes`.`id` = comp.`computertypes_id`)
                              LEFT JOIN `glpi_suppliers`
                                    ON (`glpi_suppliers`.`id` = comp.`manufacturers_id`)
                              LEFT JOIN `glpi_users` AS resptech
                                    ON (resptech.`id` = comp.`users_id_tech`)
                              WHERE $wherecomp)";
   }

   if (!in_array($sort,getTrackingSortOptions())) {
      $sort = "`glpi_tickets`.`date_mod`";
   }
   if ($order != "ASC") {
      $order=" DESC";
   }

   $query = "$SELECT
            $FROM
            $where
            ORDER BY $sort $order";

   // Get it from database
   if ($result = $DB->query($query)) {
      $numrows=$DB->numrows($result);
      if ($start<$numrows) {
         // Set display type for export if define
         $output_type=HTML_OUTPUT;
         if (isset($_GET["display_type"])) {
            $output_type=$_GET["display_type"];
         }

         // Pager
         $parameters2 = "field=$field&amp;contains=$contains&amp;date1=$date1&amp;date2=$date2".
                        "&amp;only_computers=$computers_search&amp;tosearch=$tosearch".
                        "&amp;search=$search&amp;users_id_assign=$users_id_assign".
                        "&amp;suppliers_id_assign=$suppliers_id_assign&amp;groups_id_assign=".
                        "$groups_id_assign&amp;users_id=$users_id&amp;group=$group&amp;start=".
                        "$start&amp;status=$status&amp;ticketcategories_id=".
                        "$ticketcategories_id&amp;priority=$priority&amp;itemtype=$itemtype&amp;".
                        "showfollowups=$showfollowups&amp;enddate1=$enddate1&amp;enddate2=".
                        "$enddate2&amp;datemod1=$datemod1&amp;datemod2=$datemod2&amp;items_id=".
                        "$items_id&amp;requesttypes_id=$requesttypes_id";

         // Specific case of showing tickets of an item
         if (isset($_GET["id"])) {
            $parameters2 .= "&amp;id=".$_GET["id"];
         }

         $parameters = $parameters2."&amp;sort=$sort&amp;order=$order";
         if (strpos($_SERVER['PHP_SELF'],"user.form.php")) {
            $parameters.="&amp;id=$users_id";
         }
         // Manage helpdesk
         if (strpos($target,"helpdesk.public.php")) {
            $parameters .= "&amp;show=user";
         }
         if ($output_type==HTML_OUTPUT) {
            if (!strpos($target,"helpdesk.public.php")) {
               printPager($start,$numrows,$target,$parameters,TRACKING_TYPE);
            } else {
               printPager($start,$numrows,$target,$parameters);
            }
         }
         $nbcols = 9;

         // Form to delete old item
         if (($candelete||$canupdate) && $output_type==HTML_OUTPUT) {
            echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"".
                  $CFG_GLPI["root_doc"]."/front/massiveaction.php\">";
         }

         $i = $start;
         if (isset($_GET['export_all'])) {
            $i = 0;
         }
         if ($i>0) {
            $DB->data_seek($result,$i);
         }

         $end_display = $start+$_SESSION['glpilist_limit'];
         if (isset($_GET['export_all'])) {
            $end_display=$numrows;
         }
         // Display List Header
         echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols);

         commonTrackingListHeader($output_type,$target,$parameters2,$sort,$order);
         if ($output_type==HTML_OUTPUT) {
            initNavigateListItems(TRACKING_TYPE,$LANG['common'][53]);
         }

         while ($i < $numrows && $i<$end_display && $data=$DB->fetch_array($result)) {
            addToNavigateListItems(TRACKING_TYPE,$data["id"]);
            showJobShort($data, $showfollowups,$output_type,$i-$start+1);
            $i++;
         }
         $title = "";
         // Title for PDF export
         if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT) {
            $title .= $LANG['joblist'][0]." = ";
            switch ($status) {
               case "new" :
                  $title .= $LANG['joblist'][9];
                  break;

               case "assign" :
                  $title .= $LANG['joblist'][18];
                  break;

               case "plan" :
                  $title .= $LANG['joblist'][19];
                  break;

               case "waiting" :
                  $title .= $LANG['joblist'][26];
                  break;

               case "old_done" :
                  $title .= $LANG['joblist'][10];
                  break;

               case "old_notdone" :
                  $title .= $LANG['joblist'][17];
                  break;

               case "notold" :
                  $title .= $LANG['joblist'][24];
                  break;

               case "process" :
                  $title .= $LANG['joblist'][21];
                  break;

               case "old" :
                  $title .= $LANG['joblist'][25];
                  break;

               case "all" :
                  $title .= $LANG['common'][66];
                  break;
            }
            if ($users_id!=0) {
               $title .= " - ".$LANG['job'][4]." = ".getUserName($users_id);
            }
            if ($group>0) {
               $title.=" - ".$LANG['common'][35]." = ".getDropdownName("glpi_groups",$group);
            }
            if ($users_id_assign!=0 || $suppliers_id_assign!=0 || $groups_id_assign!=0) {
               $title .= " - ".$LANG['job'][5]." =";
               if ($users_id_assign!=0) {
                  $title .= " ".$LANG['job'][6]." = ".getUserName($users_id_assign);
               }
               if ($groups_id_assign!=0) {
                  $title .= " ".$LANG['common'][35]." = ".getDropdownName("glpi_groups",
                                                                          $groups_id_assign);
               }
               if ($suppliers_id_assign!=0) {
                  $title .= " ".$LANG['financial'][26]." = ".getDropdownName("glpi_suppliers",
                                                                             $suppliers_id_assign);
               }
            }
            if ($requesttypes_id!=0) {
               $title .= " - ".$LANG['job'][44]." = ".getDropdownName('glpi_requesttypes',
                                                                      $requesttypes_id);
            }
            if ($ticketcategories_id!=0) {
               $title .= " - ".$LANG['common'][36]." = ".getDropdownName("glpi_ticketcategories",
                                                                         $ticketcategories_id);
            }
            if ($priority!=0) {
               $title .= " - ".$LANG['joblist'][2]." = ".Ticket::getPriorityName($priority);
            }
            if ($itemtype!=0 && $items_id!=0) {
               $ci = new CommonItem();
               $ci->getFromDB($itemtype,$items_id);
               $title .= " - ".$LANG['common'][1]." = ".$ci->getType()." / ".$ci->getNameID();
            }
         }
         // Display footer
         echo displaySearchFooter($output_type,$title);

         // Delete selected item
         if (($candelete||$canupdate) && $output_type==HTML_OUTPUT) {
            openArrowMassive("massiveaction_form");
            dropdownMassiveAction(TRACKING_TYPE);
            closeArrowMassive();

             // End form for delete item
            echo "</form>";
         }

         // Pager
         if ($output_type==HTML_OUTPUT) { // In case of HTML display
            echo "<br>";
            printPager($start,$numrows,$target,$parameters);
         }
      } else {
         echo "<div class='center b'>".$LANG['joblist'][8]."</div>";
      }
   }
   // Clean selection
   $_SESSION['glpimassiveactionselected'] = array();
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
      case USER_TYPE :
         if ($ID==0) {
            return "-----";
         }
         return getUserName($ID,$link);
         break;

      case ENTERPRISE_TYPE :
      case GROUP_TYPE :
         $ci=new CommonItem();
         if ($ci->getFromDB($itemtype,$ID)) {
            $before = "";
            $after = "";
            if ($link && haveTypeRight($itemtype,'r')) {
               $ci->getLink(1);
            }
            return $ci->getNameID();
         }
         return "";
   }
}


function showJobDetails($target, $ID,$array=array()) {
   global $DB,$CFG_GLPI,$LANG;

   $job=new Ticket();
   $canupdate = haveRight('update_ticket','1');
   $canpriority = haveRight('update_priority','1');
   $showuserlink=0;
   if (haveRight('user','r')) {
      $showuserlink=1;
   }
   if (!$ID) {
      $job->getEmpty();
      $job->fields["users_id"]             = $array["users_id"];
      $job->fields["groups_id"]            = $array["groups_id"];
      $job->fields["users_id_assign"]      = $array["users_id_assign"];
      $job->fields["groups_id_assign"]     = $array["groups_id_assign"];
      $job->fields["name"]                 = $array["name"];
      $job->fields["content"]              = $array["content"];
      $job->fields["ticketcategories_id"]  = $array["ticketcategories_id"];
      $job->fields["urgence"]              = $array["urgence"];
      $job->fields["impact"]               = $array["impact"];
      $job->fields["priority"]             = $array["priority"];
      $job->fields["requesttypes_id"]      = $array["requesttypes_id"];
      $job->fields["hour"]                 = $array["hour"];
      $job->fields["minute"]               = $array["minute"];
      $job->fields["date"]                 = $array["date"];
      $job->fields["entities_id"]          = $array["entities_id"];
      $job->fields["status"]               = $array["status"];
      $job->fields["followup"]             = $array["followup"];
      $job->fields["itemtype"]             = $array["itemtype"];
      $job->fields["items_id"]             = $array["items_id"];

   } else if (!$job->getFromDB($ID) || !$job->can($ID,'r')) {
      echo "<div class='center'><strong>".$LANG['common'][54]."</strong></div>";
      return false;
   }

   $canupdate_descr = $canupdate || ($job->numberOfFollowups()==0
                                     && $job->fields['users_id']==$_SESSION['glpiID']);
   $item=new CommonItem();
   $item->getFromDB($job->fields['itemtype'],$job->fields['items_id']);

   echo "<form method='post' name='form_ticket' action='$target' enctype='multipart/form-data'>";
   echo '<div class="center" id="tabsbody">';
   echo "<table class='tab_cadre_fixe'>";

   if (!$ID) {
      echo '<tr>';
      echo '<th colspan="4">'.$LANG['job'][13].'</th>';
      echo '</tr>';
   }
   // Optional line
   if (isMultiEntitiesMode()) {
      echo '<tr>';
      echo '<th colspan="4">';
      if ($ID) {
         echo getDropdownName('glpi_entities',$job->fields['entities_id']);
      } else {
         echo $LANG['job'][46]."&nbsp;:&nbsp;".getDropdownName("glpi_entities",
                                                               $job->fields['entities_id']);
      }
      echo '</th>';
      echo '</tr>';
   }

   echo "<tr>";
   echo "<th class='left' colspan='2' width='50%'>";

   echo "<table>";
   echo "<tr>";
   echo "<td><span class='tracking_small'>".$LANG['joblist'][11]."&nbsp;: </span></td>";
   echo "<td>";
   if ($ID) {
      showDateTimeFormItem("date",$job->fields["date"],1,false,$canupdate);
   } else {
      showDateTimeFormItem("date",date("Y-m-d H:i:s"),1);
   }
   echo "</td>";
   if ($ID) {
      echo "<td><span class='tracking_small'>&nbsp;&nbsp; ".$LANG['job'][2]." &nbsp;: </span>";
      if ($canupdate) {
         dropdownAllUsers("users_id_recipient",$job->fields["users_id_recipient"],1,
                          $job->fields["entities_id"]);
      } else {
         echo getUserName($job->fields["users_id_recipient"],$showuserlink);
      }
      echo "</td>";
      if (strstr($job->fields["status"],"old_")) {
         echo "<td>";
         echo "</tr><tr>";
         echo "<td><span class='tracking_small'>".$LANG['joblist'][12]."&nbsp;: </td>";
         echo "<td>";
         showDateTimeFormItem("closedate",$job->fields["closedate"],1,false,$canupdate);
         echo "</span>";
         echo "</td>";
      }
      echo "</tr>";
   }
   echo "</table>";
   echo "</th>";

   echo "<th colspan='2' width='50%'>";
   if ($ID) {
      echo "<span class='tracking_small'>".$LANG['common'][26]."&nbsp;:<br>";
      echo convDateTime($job->fields["date_mod"])."\n";
      echo "</span>";
   }
   echo "</th>";
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left' width='60'>".$LANG['joblist'][0]."&nbsp;: </td>";
   echo "<td>";
   if ($canupdate) {
      dropdownStatus("status",$job->fields["status"],2); // Allowed status
   } else {
      echo getStatusName($job->fields["status"]);
   }
   echo "</td>";
   echo "<th class='center b' colspan='2'>".$LANG['job'][4]."&nbsp;: </th>";
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left'>".$LANG['joblist'][29]."&nbsp;: </td>";
   echo "<td>";
   if ($canupdate && ($canpriority || !$ID || $job->fields["users_id_recipient"]==$_SESSION["glpiID"])) {
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgence = Ticket::dropdownUrgence("urgence",$job->fields["urgence"]);
   } else {
      $idurgence = "value_urgence".mt_rand();
      echo "<input id='$idurgence' type='hidden' name='urgence' value='".$job->fields["urgence"]."'>";
      echo Ticket::getUrgenceName($job->fields["urgence"]);
   }
   echo "</td>";
   echo "<td class='left'>";
   if (!$ID && haveRight("update_ticket","1")) {
      echo $LANG['job'][4]."&nbsp;: </td>";
      echo "<td>";

      ///Check if the user have access to this entity only, or subentities too
      if (haveAccessToEntity($_SESSION["glpiactive_entity"],true)) {
         $entities = getSonsOf("glpi_entities",$_SESSION["glpiactive_entity"]);
      } else {
         $entities = $_SESSION["glpiactive_entity"];
      }

      //List all users in the active entity (and all it's sub-entities if needed)
      $users_id_rand = dropdownAllUsers("users_id",$array["users_id"],1,$entities,1);

      //Get all the user's entities
      $all_entities = getUserEntities($array["users_id"], true);
      $values = array();

      //For each user's entity, check if the technician which creates the ticket have access to it
      foreach ($all_entities as $tmp => $ID_entity) {
         if (haveAccessToEntity($ID_entity)) {
            $values[] = $ID_entity;
         }
      }

      $count = count($values);

      if ($count>0 && !in_array($job->fields["entities_id"],$values)) {
         // If entity is not in the list of user's entities,
         // then use as default value the first value of the user's entites list
         $job->fields["entities_id"] = $values[0];
      }

      //If user have access to more than one entity, then display a combobox
      if ($count > 1) {
         $rand = dropdownValue("glpi_entities", "entities_id", $job->fields["entities_id"], 1,
                               $values,'',array(),1);
      } else {
         echo "<input type='hidden' name='entities_id' value='".$job->fields["entities_id"]."'>";
      }
   } else if ($canupdate){
      echo $LANG['common'][34]."&nbsp;: </td>";
      echo "<td>";
      dropdownAllUsers("users_id",$job->fields["users_id"],1,$job->fields["entities_id"]);
   } else {
      echo $LANG['common'][34]."&nbsp;: </td>";
      echo "<td>";
      echo getUserName($job->fields["users_id"],$showuserlink);
   }
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left'>".$LANG['joblist'][30]."&nbsp;: </td>";
   echo "<td>";
   if ($canupdate) {
      $idimpact = Ticket::dropdownImpact("impact",$job->fields["impact"]);
   } else {
      echo Ticket::getImpactName($job->fields["impact"]);
   }
   echo "</td>";
   echo "<td class='left'>".$LANG['common'][35]."&nbsp;: </td>";
   echo "<td>";
   if ($canupdate) {
      dropdownValue("glpi_groups","groups_id",$job->fields["groups_id"],1,
                    $job->fields["entities_id"]);
   } else {
      echo getDropdownName("glpi_groups",$job->fields["groups_id"]);
   }
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left'>".$LANG['joblist'][2]."&nbsp;: </td>";
   echo "<td>";
   if ($canupdate && $canpriority) {
      $idpriority = Ticket::dropdownPriority("priority",$job->fields["priority"]);
      $idajax = 'change_priority_' . mt_rand();
      echo "&nbsp;<span id='$idajax' style='display:none'></span>";
   } else {
      $idajax = 'change_priority_' . mt_rand();
      $idpriority = 0;
      echo "<span id='$idajax'>".Ticket::getPriorityName($job->fields["priority"])."</span>";
   }
   if ($canupdate) {
      $params=array('urgence'  => '__VALUE0__',
                    'impact'   => '__VALUE1__',
                    'priority' => $idpriority);
      ajaxUpdateItemOnSelectEvent(array($idurgence, $idimpact), $idajax,
                                  $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
   }
   echo "</td>";
   echo "<th class='center b' colspan='2'>".$LANG['job'][5]."&nbsp;: </th>";
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left'>".$LANG['common'][36]."&nbsp;: </td>";
   echo "<td >";
   if ($canupdate) {
      dropdownValue("glpi_ticketcategories","ticketcategories_id",
                    $job->fields["ticketcategories_id"],1,$job->fields["entities_id"]);
   } else {
      echo getDropdownName("glpi_ticketcategories",$job->fields["ticketcategories_id"]);
   }
   echo "</td>";
   if (haveRight("assign_ticket","1")) {
      echo "<td class='left'>".$LANG['job'][6]."&nbsp;: </td>";
      echo "<td>";
      dropdownUsers("users_id_assign",$job->fields["users_id_assign"],"own_ticket",0,1,
                    $job->fields["entities_id"]);
      echo "</td>";
   } else if (haveRight("steal_ticket","1")) {
      echo "<td class='right'>".$LANG['job'][6]."&nbsp;: </td>";
      echo "<td>";
      dropdownUsers("users_id_assign",$job->fields["users_id_assign"],"id",0,1,
                    $job->fields["entities_id"]);
      echo "</td>";
   } else if (haveRight("own_ticket","1") && $job->fields["users_id_assign"]==0) {
      echo "<td class='right'>".$LANG['job'][6]."&nbsp;: </td>";
      echo "<td>";
      dropdownUsers("users_id_assign",$job->fields["users_id_assign"],"id",0,1,
                    $job->fields["entities_id"]);
      echo "</td>";
   } else {
      echo "<td class='left'>".$LANG['job'][6]."&nbsp;: </td>";
      echo "<td>";
      echo getUserName($job->fields["users_id_assign"],$showuserlink);
      echo "</td>";
   }
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left'>".$LANG['job'][44]."&nbsp;: </td>";
   echo "<td>";
   if ($canupdate) {
      dropdownValue('glpi_requesttypes',"requesttypes_id",$job->fields["requesttypes_id"]);
   } else {
      echo getDropdownName('glpi_requesttypes', $job->fields["requesttypes_id"]);
   }
   echo "</td>";
   if (haveRight("assign_ticket","1")) {
      echo "<td class='left'>".$LANG['common'][35]."&nbsp;: </td>";
      echo "<td>";
      dropdownValue("glpi_groups","groups_id_assign",$job->fields["groups_id_assign"],1,
                    $job->fields["entities_id"]);
      echo "</td>";
   } else {
      echo "<td class='left'>".$LANG['common'][35]."&nbsp;: </td>";
      echo "<td>";
      echo getDropdownName("glpi_groups",$job->fields["groups_id_assign"]);
      echo "</td>";
   }
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left' rowspan='2'>".$LANG['common'][1]."&nbsp;: </td>";
   echo "<td rowspan='2''>";
   if ($canupdate) {
      if ($ID) {
         if (haveTypeRight($job->fields["itemtype"],'r')) {
            echo $item->getType()." - ".$item->getLink(true);
         } else {
            echo $item->getType()." ".$item->getNameID();
         }
      } else {
         dropdownMyDevices($array["users_id"],$job->fields["entities_id"],
                           $job->fields["itemtype"], $job->fields["items_id"]);
      }
      dropdownTrackingAllDevices("itemtype", $job->fields["itemtype"], $job->fields["items_id"],
                                 1, $job->fields["entities_id"]);
   } else {
      echo $item->getType()." ".$item->getNameID();
   }
   echo "</td>";

   if (haveRight("assign_ticket","1")) {
      echo "<td class='left'>".$LANG['financial'][26]."&nbsp;: </td>";
      echo "<td>";
      dropdownValue("glpi_suppliers","suppliers_id_assign",
                    $job->fields["suppliers_id_assign"],1,$job->fields["entities_id"]);
      echo "</td>";
   } else {
      echo "<td colspan='2'>&nbsp;</td>";
   }
   echo "</tr>\n";

   echo "<tr class='tab_bg_1'>";
   // Need comment right to add a followup with the realtime
   if (haveRight("comment_all_ticket","1") && !$ID) {
      echo "<td class='left'>".$LANG['job'][20]."&nbsp;: </td>";
      echo "<td class='center' colspan='3'>";
      dropdownInteger('hour',$array['hour'],0,100);
      echo "&nbsp;".$LANG['job'][21]."&nbsp;&nbsp;";
      dropdownInteger('minute',$array['minute'],0,59);
      echo "&nbsp;".$LANG['job'][22]."&nbsp;&nbsp;";
   } else {
      echo "<td colspan='2'>&nbsp;";
   }
   echo "</td>";
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<th>".$LANG['common'][57]."&nbsp;: </th>";
   echo "<th>";
   if ($canupdate_descr) {
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showName$rand(){\n";
      echo "Ext.get('name$rand').setDisplayed('none');";
      $params = array('maxlength' => 250,
                      'size'      => 50,
                      'name'      => 'name',
                      'data'      => rawurlencode($job->fields["name"]));
      ajaxUpdateItemJsCode("viewname$rand",$CFG_GLPI["root_doc"]."/ajax/inputtext.php",$params,
                           false);
      echo "}";
      echo "</script>\n";
      echo "<div id='name$rand' class='tracking' onClick='showName$rand()'>\n";
      if (empty($job->fields["name"])) {
         echo $LANG['reminder'][15];
      } else {
         echo $job->fields["name"];
      }
      echo "</div>\n";

      echo "<div id='viewname$rand'>\n";
      echo "</div>\n";
      if (!$ID) {
         echo "<script type='text/javascript' >\n
         showName$rand();
         </script>";
      }
   } else {
      if (empty($job->fields["name"])) {
         echo $LANG['reminder'][15];
      } else {
         echo $job->fields["name"];
      }
   }
   echo "</th>";
   echo "<th colspan='2'>";
   if ($CFG_GLPI["use_mailing"]==1) {
      echo $LANG['title'][10];
   }
   echo "</th></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td rowspan='4'>".$LANG['joblist'][6]."</td>";
   echo "<td class='left' rowspan='4'>";
   if ($canupdate_descr) { // Admin =oui on autorise la modification de la description
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showDesc$rand(){\n";
      echo "Ext.get('desc$rand').setDisplayed('none');";
      $params = array('rows'  => 6,
                      'cols'  => 50,
                      'name'  => 'content',
                      'data'  => rawurlencode($job->fields["content"]));
      ajaxUpdateItemJsCode("viewdesc$rand",$CFG_GLPI["root_doc"]."/ajax/textarea.php",$params,
                           false);
      echo "}";
      echo "</script>\n";
      echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
      if (!empty($job->fields["content"])) {
         echo nl2br($job->fields["content"]);
      } else {
         echo $LANG['job'][33];
      }
      echo "</div>\n";

      echo "<div id='viewdesc$rand'></div>\n";
      if (!$ID) {
         echo "<script type='text/javascript' >\n
         showDesc$rand();
         </script>";
      }
   } else {
      echo nl2br($job->fields["content"]);
   }
   echo "</td>";
   // Mailing ? Y or no ?
   if ($CFG_GLPI["use_mailing"]==1) {
      echo "<td class='left'>".$LANG['job'][19]."&nbsp;: </td>";
      echo "<td>";
      if (!$ID) {
         $query = "SELECT `email`
                   FROM `glpi_users`
                   WHERE `id` ='".$job->fields["users_id"]."'";
         $result=$DB->query($query);

         $email = "";
         if ($result && $DB->numrows($result)) {
            $email=$DB->result($result,0,"email");
         }
         dropdownYesNo('use_email_notification',!empty($email));
      } else {
         if ($canupdate){
            dropdownYesNo('use_email_notification',$job->fields["use_email_notification"]);
         } else {
            if ($job->fields["use_email_notification"]) {
               echo $LANG['choice'][1];
            } else {
               echo $LANG['choice'][0];
            }
         }
      }
   } else {
      echo "<td colspan='2'>&nbsp;";
   }
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   // Mailing ? Y or no ?
   if ($CFG_GLPI["use_mailing"] == 1) {
      echo "<td class='left'>".$LANG['joblist'][27]."&nbsp;: </td>";
      echo "<td>";
      if (!$ID) {
         echo "<input type='text' size='30' name='user_email' value='$email'>";
      } else {
         if ($canupdate) {
            autocompletionTextField("user_email","glpi_tickets","user_email",
                                    $job->fields["user_email"],35,$job->fields["entities_id"]);
            if (!empty($job->fields["user_email"])) {
               echo "<a href='mailto:".$job->fields["user_email"]."'>";
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='Mail'></a>";
            }
         } else if (!empty($job->fields["user_email"])) {
            echo "<a href='mailto:".$job->fields["user_email"]."'>".$job->fields["user_email"]."</a>";
         } else {
            echo "&nbsp;";
         }
      }
      echo "</td>";
   } else {
       echo "<td colspan='2'>&nbsp;";
   }
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<th colspan='2'>".$LANG['document'][21]."</th>";
   echo "</tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='top' colspan='2'>";

   // File associated ?
   $query2 = "SELECT *
              FROM `glpi_documents_items`
              WHERE `glpi_documents_items`.`items_id` = '".$job->fields["id"]."'
                    AND `glpi_documents_items`.`itemtype` = '".TRACKING_TYPE."' ";
   $result2 = $DB->query($query2);
   $numfiles=$DB->numrows($result2);

   echo "<table width='100%'>";

   if ($numfiles>0) {
      $doc=new Document;
      while ($data=$DB->fetch_array($result2)) {
         $doc->getFromDB($data["documents_id"]);
         echo "<tr><td>";
         if (empty($doc->fields["filename"])) {
            if (haveRight("document","r")) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?id=".
                     $data["documents_id"]."'>".$doc->fields["name"]."</a>";
            } else {
               echo $LANG['document'][37];
            }
         } else {
            echo $doc->getDownloadLink("&tickets_id=$ID");
         }
         if (haveRight("document","w")) {
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?deletedocumentitem=".
                  "1&amp;id=".$data["id"]."&amp;documents_id=".$data["documents_id"]."'>";
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/delete.png' alt='".$LANG['buttons'][6]."'>";
            echo "</a>";
         }
         echo "</td></tr>";
      }
   }
   if ($canupdate || haveRight("comment_all_ticket","1")
       || (haveRight("comment_ticket","1") && !strstr($job->fields["status"],'old_'))) {
      echo "<tr>";
      echo "<td colspan='2'>";
      echo "<input type='file' name='filename' size='20'>";
      if ($canupdate && haveRight("document","r")) {
         echo "<br>";
         dropdownDocument("document",$job->fields["entities_id"]);
      }
      echo "</td></tr>";
   }
   echo "</table>";
   echo "</td></tr>";

   if ($canupdate
       || $canupdate_descr
       || haveRight("comment_all_ticket","1")
       ||(haveRight("comment_ticket","1") && !strstr($job->fields["status"],'old_'))
       || haveRight("assign_ticket","1")
       || haveRight("steal_ticket","1")) {

      echo "<tr class='tab_bg_1'>";
      if ($ID) {
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' class='submit' name='update' value='".$LANG['buttons'][7]."'>";
      } else {
         echo "<td colspan='2' class='center'>";
         echo "<a href='$target'>";
         echo "<input type='button' value='".$LANG['buttons'][16]."' class='submit'/></a></td>";
         echo "<td colspan='2' class='center'>";
         echo "<input type='submit' name='add' value='".$LANG['buttons'][2]."' class='submit'>";
      }
      echo "</td></tr>";
   }

   echo "</table>";
   echo "<input type='hidden' name='id' value='$ID'>";
   echo "</div>";

   if (!$ID) {
      $commentall = haveRight("update_followups","1");
      $prefix = "";
      $postfix = "";
      $randfollow = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showFollow$randfollow(){\n";
      echo "document.getElementById('follow$randfollow').style.display='block';\n";
      echo "document.getElementById('followLink$randfollow').style.display='none';\n";
      echo "}";
      echo "</script>\n";

      // Follow for add ticket
      echo "<br/>";
      echo "<div class='center'>";

      echo "<div id='followLink$randfollow'>";
      echo "<a href='javascript:onClick=showFollow$randfollow();'>".$LANG['job'][29]."</a></div>";

      echo "<div id='follow$randfollow' style='display:none'>";
      showAddFollowupForm(-1,false);
      echo "</div>";

   }
   echo "<input type='hidden' name='id' value='$ID'>";
   echo "</div>";
   echo "</form>";

   return true;
}


function showFollowupsSummary($tID) {
   global $DB,$LANG,$CFG_GLPI;

   if (!haveRight("observe_ticket","1") && !haveRight("show_full_ticket","1")) {
      return false;
   }

   $job = new Ticket();
   $job->getFromDB($tID);
   // Display existing Followups
   $showprivate = haveRight("show_full_ticket","1");
   $caneditall = haveRight("update_followups","1");

   $RESTRICT = "";
   if (!$showprivate) {
      $RESTRICT = " AND (`is_private` = '0'
                         OR `users_id` ='".$_SESSION["glpiID"]."') ";
   }
   $query = "SELECT *
             FROM `glpi_ticketfollowups`
             WHERE `tickets_id` = '$tID'
                   $RESTRICT
             ORDER BY `date` DESC";
   $result=$DB->query($query);

   $rand=mt_rand();

   echo "<div id='viewfollowup".$tID."$rand'></div>\n";

   echo "<div class='center'>";
   echo "<h3>".$LANG['job'][37]."</h3>";

   if ($DB->numrows($result)==0) {
      echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th class='b'>".$LANG['job'][12];
      echo "</th></tr></table>";
   } else {
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th>&nbsp;</th><th>".$LANG['common'][27]."</th><th>".$LANG['joblist'][6]."</th>";
      echo "<th>".$LANG['job'][31]."</th><th>".$LANG['job'][35]."</th>";
      echo "<th>".$LANG['common'][37]."</th>";
      if ($showprivate) {
         echo "<th>".$LANG['common'][77]."</th>";
      }
      echo "</tr>";
      while ($data=$DB->fetch_array($result)) {
         $canedit = ($caneditall||$data['users_id']==$_SESSION['glpiID']);
         echo "<tr class='tab_bg_".($data['is_private']==1?"4":"2")."' ".
               ($canedit?"style='cursor:pointer' onClick=\"viewEditFollowup".$tID.$data["id"].
                "$rand();\"":"style='cursor:none'")." id='viewfollowup".$tID.$data["id"]."$rand'>";
         echo "<td>".$data["id"]."</td>";

         echo "<td>";
         if ($canedit) {
            echo "<script type='text/javascript' >\n";
            echo "function viewEditFollowup".$tID.$data["id"]."$rand(){\n";
            $params = array('id'=>$data["id"]);
            ajaxUpdateItemJsCode("viewfollowup".$tID."$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php",$params,false);
            echo "};";
            echo "</script>\n";
         }

         echo convDateTime($data["date"])."</td>";
         echo "<td class='left'>".nl2br($data["content"])."</td>";

         $hour = floor($data["realtime"]);
         $minute = round(($data["realtime"]-$hour)*60,0);
         echo "<td>";
         if ($hour) {
            echo "$hour ".$LANG['job'][21]."<br>";
         }
         if ($minute || !$hour) {
            echo "$minute ".$LANG['job'][22]."</td>";
         }
         echo "<td>";
         $query2 = "SELECT *
                    FROM `glpi_ticketplannings`
                    WHERE `ticketfollowups_id` = '".$data['id']."'";
         $result2=$DB->query($query2);

         if ($DB->numrows($result2)==0) {
            echo $LANG['job'][32];
         } else {
            $data2 = $DB->fetch_array($result2);
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$data['id']."(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'users_id' => $data2["users_id"],
                            'id'       => $data2["id"],
                            'state'    => $data2["state"],
                            'begin'    => $data2["begin"],
                            'end'      => $data2["end"],
                            'entity'   => $job->fields["entities_id"]);
            ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,
                                 false);
            echo "}";
            echo "</script>\n";

            echo getPlanningState($data2["state"])."<br>".convDateTime($data2["begin"])."<br>->".
                 convDateTime($data2["end"])."<br>".getUserName($data2["users_id"]);
         }
         echo "</td>";

         echo "<td>".getUserName($data["users_id"])."</td>";
         if ($showprivate) {
            echo "<td>";
            if ($data["is_private"]) {
               echo $LANG['choice'][1];
            } else {
               echo $LANG['choice'][0];
            }
            echo "</td>";
         }
         echo "</tr>";
      }
      echo "</table>";
   }
   echo "</div>";
}


/** Form to add a followup to a ticket
* @param $tID integer : ticket ID
* @param $massiveaction boolean : add followup using massive action
* @param $datas array : datas to preset form
*/
function showAddFollowupForm($tID,$massiveaction=false,$datas=array()) {
   global $DB,$LANG,$CFG_GLPI;

   $job=new Ticket();
   if ($tID>0) {
      $job->getFromDB($tID);
   } else {
      $job->getEmpty();
   }
   $prefix = "";
   $postfix = "";
   // Add followup at creating ticket : prefix values
   if ($tID<0 && !$massiveaction) {
      $prefix = "_followup[";
      $postfix = "]";
   }
   if (!haveRight("comment_ticket","1")
       && !haveRight("comment_all_ticket","1")
       && $job->fields["users_id_assign"] != $_SESSION["glpiID"]
       && !in_array($job->fields["groups_id_assign"],$_SESSION["glpigroups"])) {
      return false;
   }

   $commentall = (haveRight("comment_all_ticket","1")
                  || $job->fields["users_id_assign"]==$_SESSION["glpiID"]
                  || in_array($job->fields["groups_id_assign"],$_SESSION["glpigroups"]));
   $editticket = haveRight("update_ticket","1");

   if ($_SESSION["glpiactiveprofile"]["interface"]=="central") {
      $target = $CFG_GLPI["root_doc"]."/front/ticket.form.php";
   } else {
      $target = $CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user";
   }
   // Display Add Table
   echo "<div class='center'>";
   if ($tID>0) {
      echo "<form name='followups' method='post' action='$target'>\n";
   }
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='2'>".$LANG['job'][29]."</th></tr>";

   if ($commentall) {
      $width_left = $width_right = "50%";
      $cols = 50;
   } else {
      $width_left = "80%";
      $width_right = "20%";
      $cols = 80;
   }

   echo "<tr class='tab_bg_2'><td width='$width_left'>";
   echo "<table width='100%'>";
   echo "<tr><td>".$LANG['joblist'][6]."</td>";
   echo "<td><textarea name='".$prefix."content".$postfix."' rows='12' cols='$cols'>";
   if (isset($datas['content'])) {
      echo cleanPostForTextArea($datas['content']);
   }
   echo "</textarea>";
   echo "</td></tr></table>";
   echo "</td>";

   echo "<td width='$width_right' class='top'>";
   echo "<table width='100%'>";

   if ($commentall) {
      echo "<tr>";
      echo "<td>".$LANG['common'][77]."&nbsp;:</td>";

      echo "<td>";
      $default_private = $_SESSION['glpifollowup_private'];
      if (isset($datas['is_private'])) {
         $default_private = $datas['is_private'];
      }
      echo "<select name='".$prefix."is_private".$postfix."'>";
      echo "<option value='0' ".(!$default_private?"selected":"").">".$LANG['choice'][0]."</option>";
      echo "<option value='1' ".($default_private?"selected":"").">".$LANG['choice'][1]."</option>";
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      if ($tID>0) {
         echo "<tr><td>".$LANG['job'][31]."&nbsp;:</td><td>";
         dropdownInteger('hour',0,0,100);
         echo $LANG['job'][21]."&nbsp;&nbsp;";
         dropdownInteger('minute',0,0,59);
         echo $LANG['job'][22];
         echo "</tr>";
      }

      if (haveRight("show_planning","1") && !$massiveaction) {
         echo "<tr>";
         echo "<td>".$LANG['job'][35]."</td>";

         echo "<td>";
         $rand=mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showPlanAdd$rand(){\n";
         echo "Ext.get('plan$rand').setDisplayed('none');";
         $params = array('form'     => 'followups',
                         'state'    => 1,
                         'users_id' => $_SESSION['glpiID'],
                         'entity'   => $_SESSION["glpiactive_entity"]);

         if (isset($datas['plan']) && isset($datas['plan']['state'])) {
            $params['state'] = $datas['plan']['state'];
         }
         if (isset($datas['plan']) && isset($datas['plan']['users_id'])) {
            $params['users_id'] = $datas['plan']['users_id'];
         }
         if (isset($datas['plan']) && isset($datas['plan']['begin'])) {
            $params['begin'] = $datas['plan']['begin'];
         }
         if (isset($datas['plan']) && isset($datas['plan']['end'])) {
            $params['end'] = $datas['plan']['end'];
         }
         ajaxUpdateItemJsCode('viewplan'.$rand,$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,
                              false);
         echo "};";
         echo "</script>";

         echo "<div id='plan$rand'  onClick='showPlanAdd$rand()'>\n";
         echo "<span class='showplan'>".$LANG['job'][34]."</span>";
         echo "</div>\n";

         echo "<div id='viewplan$rand'></div>\n";
         echo "<script type='text/javascript' >\n";

         // Display form
         if (isset($params['end']) && isset($params['begin'])) {
            echo "showPlanAdd$rand();";
         }
         echo "</script>";

         echo "</td></tr>";
      }
   }
   if ($tID>0 || $massiveaction) {
      $cancloseopen = false;
      if ($commentall && $editticket && $tID>0) {
         $cancloseopen=true;
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' ".(!$cancloseopen?"colspan=2":"").">";
      echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit'>";
      echo "</td>";
      if ($cancloseopen) {
         echo "<td class='center'>";
         // closed ticket
         if (strstr($job->fields['status'],'old_')) {
            echo "<input type='submit' name='add_reopen' value='".
                  $LANG['buttons'][54]."' class='submit'>";
         } else { // not closed ticket
            echo "<input type='submit' name='add_close' value='".
                  $LANG['buttons'][26]."' class='submit'>";
         }
         echo "</td>";
      }
      echo "</tr>";
   }

   echo "</table>";
   echo "</td></tr>";
   echo "</table>";
   if ($tID>0) {
      echo "<input type='hidden' name='tickets_id' value='$tID'>";
      echo "</form>";
   }
   echo "</div>";
}


/** Form to update a followup to a ticket
* @param $ID integer : followup ID
*/
function showUpdateFollowupForm($ID) {
   global $DB,$LANG,$CFG_GLPI;

   $fup=new TicketFollowup();

   if ($fup->getFromDB($ID)) {
      if ($fup->fields["users_id"]!=$_SESSION['glpiID'] && !haveRight("update_followups","1")) {
         return false;
      }

      $commentall = haveRight("update_followups","1");
      $canplan = haveRight("show_planning","1");

      $job=new Ticket();
      $job->getFromDB($fup->fields["tickets_id"]);

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['job'][39]."</th></tr>";
      echo "<tr class='tab_bg_2'><td>";
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php\">\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td width='50%'>";

      echo "<table width='100%' bgcolor='#FFFFFF'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center' width='10%'>".$LANG['joblist'][6]."<br><br>".$LANG['common'][27].
            "&nbsp;:<br>".convDateTime($fup->fields["date"])."</td>";
      echo "<td width='90%'>";
      if ($commentall) {
         echo "<textarea name='content' cols='50' rows='6'>".$fup->fields["content"]."</textarea>";
      } else {
         echo nl2br($fup->fields["content"]);
      }
      echo "</td></tr></table>";

      echo "</td>";
      echo "<td width='50%' class='top'>";

      echo "<table width='100%'>";
      if ($commentall) {
         echo "<tr><td>".$LANG['common'][77]."&nbsp;:</td>";
         echo "<td><select name='is_private'>";
         echo "<option value='0' ".(!$fup->fields["is_private"]?" selected":"").">".
               $LANG['choice'][0]."</option>";
         echo "<option value='1' ".($fup->fields["is_private"]?" selected":"").">".
               $LANG['choice'][1]."</option>";
         echo "</select></td>";
         echo "</tr>";
      }

      echo "<tr><td>".$LANG['job'][31]."&nbsp;:</td><td>";
      $hour = floor($fup->fields["realtime"]);
      $minute = round(($fup->fields["realtime"]-$hour)*60,0);

      if ($commentall) {
         dropdownInteger('hour',$hour,0,100);
         echo $LANG['job'][21]."&nbsp;&nbsp;";
         dropdownInteger('minute',$minute,0,59);
         echo $LANG['job'][22];
      } else {
         echo $hour." ".$LANG['job'][21]." ".$minute." ".$LANG['job'][22];
      }

      echo "</tr>";

      echo "<tr><td>".$LANG['job'][35]."</td>";
      echo "<td>";
      $query2 = "SELECT *
                 FROM `glpi_ticketplannings`
                 WHERE `ticketfollowups_id` = '".$fup->fields['id']."'";
      $result2=$DB->query($query2);

      if ($DB->numrows($result2)==0) {
         if ($canplan) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'state'    => 1,
                            'users_id' => $_SESSION['glpiID'],
                            'entity'   => $_SESSION["glpiactive_entity"]);
            ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,
                                 false);
            echo "};";
            echo "</script>";

            echo "<div id='plan'  onClick='showPlanUpdate()'>\n";
            echo "<span class='showplan'>".$LANG['job'][34]."</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         } else {
            echo $LANG['job'][32];
         }
      } else {
         $fup->fields2 = $DB->fetch_array($result2);
         if ($canplan) {
            echo "<div id='plan' onClick='showPlan".$ID."()'>\n";
            echo "<span class='showplan'>";
         }
         echo getPlanningState($fup->fields2["state"])."<br>".convDateTime($fup->fields2["begin"]).
              "<br>->".convDateTime($fup->fields2["end"])."<br>".
              getUserName($fup->fields2["users_id"]);
         if ($canplan) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         }
      }

      echo "</td></tr>";

      if ($commentall) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan='2'>";
         echo "<table width='100%'><tr><td class='center'>";
         echo "<input type='submit' name='update_followup' value='".
               $LANG['buttons'][14]."' class='submit'>";
         echo "</td><td class='center'>";
         echo "<input type='submit' name='delete_followup' value='".
               $LANG['buttons'][6]."' class='submit'>";
         echo "</td></tr></table>";
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</td></tr></table>";

      if ($commentall) {
         echo "<input type='hidden' name='id' value='".$fup->fields["id"]."'>";
         echo "<input type='hidden' name='tickets_id' value='".$fup->fields["tickets_id"]."'>";
         echo "</form>";
      }
      echo "</td></tr>";
      echo "</table>";
      echo "</div>";
   }
}


/** Computer total cost of a ticket
* @param $realtime float : ticket realtime
* @param $cost_time float : ticket time cost
* @param $cost_fixed float : ticket fixed cost
* @param $cost_material float : ticket material cost
* @return total cost formatted string
*/
function trackingTotalCost($realtime,$cost_time,$cost_fixed,$cost_material) {
   return formatNumber(($realtime*$cost_time)+$cost_fixed+$cost_material,true);
}


/**
 * Calculate Ticket TCO for a device
 *
 *@param $itemtype device type
 *@param $items_id ID of the device
 *
 *@return float
 *
 **/
function computeTicketTco($itemtype,$items_id) {
   global $DB;

   $totalcost=0;

   $query = "SELECT *
             FROM `glpi_tickets`
             WHERE `itemtype` = '$itemtype'
                   AND `items_id` = '$items_id'
                   AND (`cost_time` > '0'
                        OR `cost_fixed` > '0'
                        OR `cost_material` > '0')";
   $result = $DB->query($query);

   $i = 0;
   if ($DB->numrows($result)) {
      while ($data=$DB->fetch_array($result)) {
         $totalcost += trackingTotalCost($data["realtime"],$data["cost_time"],$data["cost_fixed"],
                                         $data["cost_material"]);
      }
   }
   return $totalcost;
}


function showPreviewAssignAction($output) {
   global $LANG,$INFOFORM_PAGES,$CFG_GLPI;

   //If ticket is assign to an object, display this information first
   if (isset($output["entities_id"]) && isset($output["items_id"]) && isset($output["itemtype"])) {
      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['rulesengine'][48]."</td>";

      $commonitem = new CommonItem;
      $commonitem->getFromDB($output["itemtype"],$output["items_id"]);
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$output["itemtype"]]."?id=".
            $output["items_id"]."\">".$commonitem->obj->fields["name"]."</a>";
      echo "</td>";
      echo "</tr>";

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
   $ci = new CommonItem();
   foreach($CFG_GLPI["helpdesk_types"] as $itemtype) {
      if ($itemtype<1000 // No plugin here
          && in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
         $ci->setType($itemtype);
         $types[$itemtype] = $ci->getType();
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
   if (isPluginItem($itemtype)){
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


function showJobCost($target,$ID) {
   global $DB,$LANG;

   $job=new Ticket();
   $job->getFromDB($ID)&&haveAccessToEntity($job->fields["entities_id"]);

   echo "<form method='post' name='form_ticket_cost' action='$target' >\n";
   echo "<div class='center' id='tabsbody'>";
   echo "<table class='tab_cadre_fixe'>";

   echo "<tr><th colspan='2'>".$LANG['job'][47]."</th></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td class='left' width='50%'>".$LANG['job'][20]."&nbsp;: </td>";

   echo "<td class='b'>".getRealtime($job->fields["realtime"])."</td>";
   echo "</tr>";

   if (haveRight("contract","r")) {  // admin = oui on affiche les couts liés à l'interventions
      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['job'][40]."&nbsp;: </td>";

      echo "<td><input type='text' maxlength='100' size='15' name='cost_time' value='".
                 formatNumber($job->fields["cost_time"],true)."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['job'][41]."&nbsp;: </td>";

      echo "<td><input type='text' maxlength='100' size='15' name='cost_fixed' value='".
                 formatNumber($job->fields["cost_fixed"],true)."'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['job'][42]."&nbsp;: </td>";

      echo "<td><input type='text' maxlength='100' size='15' name='cost_material' value='".
                 formatNumber($job->fields["cost_material"],true)."'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['job'][43]."&nbsp;: </td>";

      echo "<td class='b'>";
      echo trackingTotalCost($job->fields["realtime"],$job->fields["cost_time"],
                             $job->fields["cost_fixed"],$job->fields["cost_material"]);
      echo "</td>";
      echo "</tr>\n";
   }

   echo "<tr class='tab_bg_1'>";
   echo "<td class='center' colspan='2'>";
   echo "<input type='submit' class='submit' name='update' value='".$LANG['buttons'][14]."'></td>";
   echo "</tr>";
   echo "</table>";
   echo "<input type='hidden' name='id' value='$ID'>";
   echo "</div>";
   echo "</form>";
}

?>
