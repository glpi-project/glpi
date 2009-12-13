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

/** Determine if a table is a dropdown one for stat
* @param $table string : table name
* @return boolean
*/
function is_dropdown_stat($table) {
   return !is_numeric($table);
}


function getStatsItems($date1,$date2,$type) {
   global $CFG_GLPI,$DB;

   $val=array();

   switch ($type) {
      case "technicien" :
         $val = getNbIntervTech($date1,$date2);
         break;

      case "technicien_followup" :
         $val = getNbIntervTechFollowup($date1,$date2);
         break;

      case "enterprise" :
         $val = getNbIntervSupplier($date1,$date2);
         break;

      case "user" :
         $val = getNbIntervAuthor($date1,$date2);
         break;

      case "users_id_recipient" :
         $val = getNbIntervRecipient($date1,$date2);
         break;

      case "ticketcategories_id" :
         $val = getNbIntervCategory($date1,$date2);
         break;

      case "group" :
         $val = getNbIntervGroup($date1,$date2);
         break;

      case "groups_id_assign" :
         $val = getNbIntervAssignGroup($date1,$date2);
         break;

      case "priority" :
         $val = getNbIntervPriority($date1,$date2);
         break;

      case "requesttypes_id" :
         $val = getNbIntervRequestType($date1,$date2);
         break;

      case "usertitles_id" :
         $val = getNbIntervTitleOrType($date1,$date2,true);
         break;

      case "usercategories_id" :
         $val = getNbIntervTitleOrType($date1,$date2,false);
         break;

      case "glpi_computertypes" :
      case "glpi_computermodels" :
      case "glpi_operatingsystems" :
      case "glpi_locations" :
         $val = getNbIntervDropdown($type);
         break;

      // DEVICE CASE
      default :
         $device_table = getDeviceTable($type);

         //select devices IDs (table row)
         $query = "SELECT `id`, `designation`
                   FROM `".$device_table."`
                   ORDER BY `designation`";
         $result = $DB->query($query);

         if ($DB->numrows($result) >=1) {
            $i = 0;
            while ($line = $DB->fetch_assoc($result)) {
               $val[$i]['id'] = $line['id'];
               $val[$i]['link'] = $line['designation'];
               $i++;
            }
         }
   }
   return $val;
}


function displayStats($type,$date1,$date2,$start,$value,$value2="") {
   global $LANG,$CFG_GLPI;

   // Set display type for export if define
   $output_type=HTML_OUTPUT;
   if (isset($_GET["display_type"])) {
      $output_type=$_GET["display_type"];
   }
   if ($output_type==HTML_OUTPUT) { // HTML display
      echo "<div class ='center'>";
   }
   if (is_array($value)) {
      $end_display=$start+$_SESSION['glpilist_limit'];
      $numrows=count($value);
      if (isset($_GET['export_all'])) {
         $start=0;
         $end_display=$numrows;
      }
      $nbcols=8;
      if ($output_type!=HTML_OUTPUT) { // not HTML display
      $nbcols--;
      }
      echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols);
      echo displaySearchNewLine($output_type);
      $header_num=1;
      echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
      if ($output_type==HTML_OUTPUT) { // HTML display
         echo displaySearchHeaderItem($output_type,"",$header_num);
      }
      echo displaySearchHeaderItem($output_type,$LANG['stats'][13],$header_num);
      echo displaySearchHeaderItem($output_type,$LANG['stats'][11],$header_num);
      echo displaySearchHeaderItem($output_type,$LANG['stats'][15],$header_num);
      echo displaySearchHeaderItem($output_type,$LANG['stats'][25],$header_num);
      echo displaySearchHeaderItem($output_type,$LANG['stats'][27],$header_num);
      echo displaySearchHeaderItem($output_type,$LANG['stats'][30],$header_num);
      // End Line for column headers
      echo displaySearchEndLine($output_type);
      $row_num=1;
      for ($i=$start ; $i< $numrows && $i<($end_display) ; $i++) {
         $row_num++;
         $item_num=1;
         echo displaySearchNewLine($output_type,$i%2);
         echo displaySearchItem($output_type,$value[$i]['link'],$item_num,$row_num);
         if ($output_type==HTML_OUTPUT) { // HTML display
            $link="";
            if ($value[$i]['id']>0) {
               $link="<a href='stat.graph.php?id=".$value[$i]['id']."&amp;date1=$date1&amp;date2=".
                       "$date2&amp;type=$type".(!empty($value2)?"&amp;champ=$value2":"")."'>".
                       "<img src=\"".$CFG_GLPI["root_doc"]."/pics/stats_item.png\" alt='' title=''>".
                       "</a>";
            }
            echo displaySearchItem($output_type,$link,$item_num,$row_num);
         }

         //le nombre d'intervention - the number of intervention
         $opened=constructEntryValues("inter_total",$date1,$date2,$type,$value[$i]["id"],$value2);
         $nb_opened=array_sum($opened);
         echo displaySearchItem($output_type,$nb_opened,$item_num,$row_num);
         //le nombre d'intervention resolues - the number of resolved intervention
         $solved=constructEntryValues("inter_solved",$date1,$date2,$type,$value[$i]["id"],$value2);
         $nb_solved=array_sum($solved);
         echo displaySearchItem($output_type,$nb_solved,$item_num,$row_num);
         //Le temps moyen de resolution - The average time to resolv
         $data=constructEntryValues("inter_avgsolvedtime",$date1,$date2,$type,$value[$i]["id"],$value2);
         foreach ($data as $key2 => $val2) {
            $data[$key2]*=$solved[$key2];
         }
         if ($nb_solved>0) {
            $nb=array_sum($data)/$nb_solved;
         } else {
            $nb=0;
         }
         $timedisplay = $nb*HOUR_TIMESTAMP;
         if ($output_type==HTML_OUTPUT
             || $output_type==PDF_OUTPUT_LANDSCAPE
             || $output_type==PDF_OUTPUT_PORTRAIT) {
            $timedisplay=timestampToString($timedisplay,0);
         }
         echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);
         //Le temps moyen de l'intervention reelle - The average realtime to resolv
         $data=constructEntryValues("inter_avgrealtime",$date1,$date2,$type,$value[$i]["id"],$value2);
         foreach ($data as $key2 => $val2) {
            if (isset($solved[$key2])) {
               $data[$key2]*=$solved[$key2];
            } else {
               $data[$key2]*=0;
            }
         }
         $total_realtime=array_sum($data);
         if ($nb_solved>0) {
            $nb=$total_realtime/$nb_solved;
         } else {
            $nb=0;
         }
         $timedisplay=$nb*MINUTE_TIMESTAMP;
         if ($output_type==HTML_OUTPUT || $output_type==PDF_OUTPUT_LANDSCAPE
             || $output_type==PDF_OUTPUT_PORTRAIT) {
            $timedisplay=timestampToString($timedisplay,0);
         }
         echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);
         //Le temps total de l'intervention reelle - The total realtime to resolv
         $timedisplay=$total_realtime*MINUTE_TIMESTAMP;
         if ($output_type==HTML_OUTPUT
             || $output_type==PDF_OUTPUT_LANDSCAPE
             || $output_type==PDF_OUTPUT_PORTRAIT) {
            $timedisplay=timestampToString($timedisplay,0);
         }
         echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);
         //Le temps moyen de prise en compte du ticket - The average time to take a ticket into account
         $data=constructEntryValues("inter_avgtakeaccount",$date1,$date2,$type,$value[$i]["id"],
                                    $value2);
         foreach ($data as $key2 => $val2) {
            $data[$key2]*=$solved[$key2];
         }
         if ($nb_solved>0) {
            $nb=array_sum($data)/$nb_solved;
         } else {
            $nb=0;
         }
         $timedisplay=$nb*HOUR_TIMESTAMP;
         if ($output_type==HTML_OUTPUT
             || $output_type==PDF_OUTPUT_LANDSCAPE
             || $output_type==PDF_OUTPUT_PORTRAIT) {
            $timedisplay=timestampToString($timedisplay,0);
         }
         echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);
         echo displaySearchEndLine($output_type);
      }
      // Display footer
      echo displaySearchFooter($output_type);
   } else {
      echo $LANG['stats'][23];
   }
   if ($output_type==HTML_OUTPUT) { // HTML display
      echo "</div>";
   }
}


/** Get users which have intervention assigned to  between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct users which have any intervention assigned to.
*/
function getNbIntervTech($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_tickets`.`users_id_assign`, `glpi_users`.`name` AS name,
                    `glpi_users`.`realname` AS realname, `glpi_users`.`firstname` AS firstname
             FROM `glpi_tickets`
             LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id_assign`) ".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY realname, firstname, name";

   $result = $DB->query($query);
   $tab=array();

   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["users_id_assign"];
         $tmp['link']=formatUserName($line["users_id_assign"],$line["name"],$line["realname"],
                                     $line["firstname"],1);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get users which have followup assigned to  between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct users which have any followup assigned to.
*/
function getNbIntervTechFollowup($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_ticketfollowups`.`users_id` AS users_id,
                    `glpi_users`.`name` AS name, `glpi_users`.`realname` AS realname,
                    `glpi_users`.`firstname` AS firstname
             FROM `glpi_tickets`
             LEFT JOIN `glpi_ticketfollowups`
                       ON (`glpi_tickets`.`id` = `glpi_ticketfollowups`.`tickets_id`)
             LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_ticketfollowups`.`users_id`)".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .="     AND `glpi_ticketfollowups`.`users_id` <> '0'
                  AND `glpi_ticketfollowups`.`users_id` IS NOT NULL
              ORDER BY realname, firstname, name";

   $result = $DB->query($query);
   $tab=array();

   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["users_id"];
         $tmp['link']=formatUserName($line["users_id"],$line["name"],$line["realname"],
                                     $line["firstname"],1);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get enterprises which have followup assigned to between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct enterprises which have any tickets assigned to.
*/
function getNbIntervSupplier($date1,$date2) {
   global $DB,$CFG_GLPI;

   $query = "SELECT DISTINCT `glpi_tickets`.`suppliers_id_assign` AS suppliers_id_assign,
                    `glpi_suppliers`.`name` AS name
             FROM `glpi_tickets`
             LEFT JOIN `glpi_suppliers`
                  ON (`glpi_suppliers`.`id` = `glpi_tickets`.`suppliers_id_assign`) ".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query.= " ORDER BY name";

   $tab=array();
   $result = $DB->query($query);
   if ($DB->numrows($result) >0) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp["id"]=$line["suppliers_id_assign"];
         $tmp["link"]="<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".
                        $line["suppliers_id_assign"]."'>";
         $tmp["link"].=$line["name"];
         $tmp["link"].="</a>";
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get dropdown values
* @param $dropdown string : table name
* @return array contains the distinct dropdown values
*/
function getNbIntervDropdown($dropdown) {
   global $DB,$CFG_GLPI;

   $field = "name";

   if (in_array($dropdown,$CFG_GLPI["dropdowntree_tables"])) {
      $field="completename";
   }
   $where = '';
   $order = " ORDER BY `$field`";
   if (in_array($dropdown,$CFG_GLPI["specif_entities_tables"])) {
      $where = getEntitiesRestrictRequest(" WHERE",$dropdown);
      $order = " ORDER BY `entities_id`, `$field`";
   }

   $query = "SELECT *
             FROM `$dropdown`
             $where
             $order";

   $tab=array();
   $result = $DB->query($query);
   if ($DB->numrows($result) >0) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["id"];
         $tmp['link']=$line[$field];
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get users_ids of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct users_ids which have tickets
*/
function getNbIntervAuthor($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_tickets`.`users_id`, `glpi_users`.`name` AS name,
                    `glpi_users`.`realname` AS realname, `glpi_users`.`firstname` AS firstname
             FROM `glpi_tickets`
             INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`)".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY realname, firstname, name";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["users_id"];
         $tmp['link']=formatUserName($line["users_id"],$line["name"],$line["realname"],
                                     $line["firstname"],1);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get recipient of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @param title : indicates if stat if by title (true) or type (false)
* @return array contains the distinct recipents which have tickets
*/
function getNbIntervTitleOrType($date1,$date2,$title=true) {
   global $DB;

   if ($title) {
      $table = "glpi_usertitles";
      $field = "usertitles_id";
   } else {
      $table = "glpi_usercategories";
      $field = "usercategories_id";
   }

   $query = "SELECT DISTINCT `glpi_users`.`$field`
             FROM `glpi_tickets`
             INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`)
             LEFT JOIN `$table` ON (`$table`.`id` = `glpi_users`.`$field`)".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .=" ORDER BY `glpi_users`.`$field`";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line[$field];
         $tmp['link']=Dropdown::getDropdownName($table,$line[$field]);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get recipient of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct recipents which have tickets
*/
function getNbIntervRecipient($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_tickets`.`users_id_recipient`, `glpi_users`.`name` AS name,
                    `glpi_users`.`realname` AS realname, `glpi_users`.`firstname` AS firstname
             FROM `glpi_tickets`
             LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id_recipient`)".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY realname, firstname, name";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["users_id_recipient"];
         $tmp['link']=formatUserName($line["users_id_recipient"],$line["name"],$line["realname"],
                                     $line["firstname"],1);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get priorities of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct priorities of tickets
*/
function getNbIntervPriority($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `priority`
             FROM `glpi_tickets` ".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY `priority`";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      $i = 0;
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["priority"];
         $tmp['link']=Ticket::getPriorityName($line["priority"]);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get request types of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct request types of tickets
*/
function getNbIntervRequestType($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `requesttypes_id`
             FROM `glpi_tickets` ".
             getEntitiesRestrictRequest("WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY `requesttypes_id`";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["requesttypes_id"];
         $tmp['link']=Dropdown::getDropdownName('glpi_requesttypes',$line["requesttypes_id"]);
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get categories of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct categories of tickets
*/
function getNbIntervCategory($date1,$date2) {
   global $DB;

   // Get all ticket categories for tree merge management
   $query = "SELECT DISTINCT `glpi_ticketcategories`.`id`,
                    `glpi_ticketcategories`.`completename` AS category
             FROM `glpi_ticketcategories`
             ORDER BY category";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["id"];
         $tmp['link']=$line["category"];
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get groups which have tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct groups of tickets
*/
function getNbIntervGroup($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_groups`.`id`, `glpi_groups`.`name`
             FROM `glpi_tickets`
             LEFT JOIN `glpi_groups` ON (`glpi_tickets`.`groups_id` = `glpi_groups`.`id`)".
             getEntitiesRestrictRequest(" WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY `glpi_groups`.`name`";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["id"];
         $tmp['link']=$line["name"];
         $tab[]=$tmp;
      }
   }
   return $tab;
}


/** Get groups assigned to tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct groups assigned to a tickets
*/
function getNbIntervAssignGroup($date1,$date2) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_groups`.`id`, `glpi_groups`.`name`
             FROM `glpi_tickets`
             LEFT JOIN `glpi_groups` ON (`glpi_tickets`.`groups_id_assign` = `glpi_groups`.`id`)".
             getEntitiesRestrictRequest(" WHERE","glpi_tickets");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("`glpi_tickets`.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
   }
   $query .= " ORDER BY `glpi_groups`.`name`";

   $result = $DB->query($query);
   $tab=array();
   if ($DB->numrows($result) >=1) {
      while ($line = $DB->fetch_assoc($result)) {
         $tmp['id']= $line["id"];
         $tmp['link']=$line["name"];
         $tab[]=$tmp;
      }
   }
   return $tab;
}


function constructEntryValues($type,$begin="",$end="",$param="",$value="",$value2="") {
   global $DB;

   $query = "";
   $WHERE = getEntitiesRestrictRequest("WHERE","glpi_tickets");
   $LEFTJOIN = "";

   switch ($param) {
      case "technicien" :
         $WHERE .= " AND `glpi_tickets`.`users_id_assign` = '$value'";
         break;

      case "technicien_followup" :
         $WHERE .= " AND `glpi_ticketfollowups`.`users_id` = '$value'";
         $LEFTJOIN = "LEFT JOIN `glpi_ticketfollowups`
                           ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`)";
         break;

      case "enterprise" :
         $WHERE .= " AND `glpi_tickets`.`suppliers_id_assign` = '$value'";
         break;

      case "user" :
         $WHERE .= " AND `glpi_tickets`.`users_id` = '$value'";
         break;

      case "users_id_recipient" :
         $WHERE .= " AND `glpi_tickets`.`users_id_recipient` = '$value'";
         break;

      case "ticketcategories_id" :
         if (!empty($value)) {
            $categories=getSonsOf("glpi_ticketcategories",$value);
            $condition=implode("','",$categories);
            $WHERE .= " AND `glpi_tickets`.`ticketcategories_id` IN ('$condition')";
         } else {
            $WHERE .= " AND `glpi_tickets`.`ticketcategories_id` = '$value' ";
         }
         break;

      case "group" :
         $WHERE .= " AND `glpi_tickets`.`groups_id` = '$value'";
         break;

      case "groups_id_assign" :
         $WHERE .= " AND `glpi_tickets`.`groups_id_assign` = '$value'";
         break;

      case "priority" :
         $WHERE .= " AND `glpi_tickets`.`priority` = '$value'";
         break;

      case "requesttypes_id" :
         $WHERE .= " AND `glpi_tickets`.`requesttypes_id` = '$value'";
         break;

      case "usertitles_id" :
         $LEFTJOIN = "INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`
                                                  AND `glpi_users`.`usertitles_id` = '$value')";
         break;

      case "usercategories_id" :
         $LEFTJOIN = "INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`
                                                  AND `glpi_users`.`usercategories_id` = '$value')";
         break;

      case "device":
         //select computers IDs that are using this device;
         $LEFTJOIN = "INNER JOIN `glpi_computers`
                            ON (`glpi_computers`.`id` = `glpi_tickets`.`items_id`
                                AND `glpi_tickets`.`itemtype` = 'Computer')
                      INNER JOIN `glpi_computers_devices`
                            ON (`glpi_computers`.`id` = `glpi_computers_devices`.`computers_id`
                                AND `glpi_computers_devices`.`devicetype` = '$value2'
                                AND `glpi_computers_devices`.`devices_id` = '$value')";
         $WHERE .= " AND `glpi_computers`.`is_template` <> '1' ";
         break;

      case "comp_champ" :
         $LEFTJOIN = "INNER JOIN `glpi_computers`
                            ON (`glpi_computers`.`id` = `glpi_tickets`.`items_id`
                                AND `glpi_tickets`.`itemtype` = 'Computer')";
         $WHERE .= " AND `glpi_computers`.`$value2` = '$value'
                     AND `glpi_computers`.`is_template` <> '1'";
         break;
   }

   switch($type) {
      case "inter_total" :
         $WHERE .= " AND ".getDateRequest("`glpi_tickets`.`date`",$begin,$end);

         $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`glpi_tickets`.`date`),'%Y-%m') AS date_unix,
                          COUNT(`glpi_tickets`.`id`) AS total_visites
                   FROM `glpi_tickets`
                   $LEFTJOIN
                   $WHERE
                   GROUP BY date_unix
                   ORDER BY `glpi_tickets`.`date`";
         break;

      case "inter_solved" :
         $WHERE .= " AND (`glpi_tickets`.`status` = 'old_done'
                          OR `glpi_tickets`.`status` = 'old_notdone')
                     AND `glpi_tickets`.`closedate` IS NOT NULL ";
         $WHERE .= " AND ".getDateRequest("`glpi_tickets`.`closedate`",$begin,$end);

         $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`glpi_tickets`.`closedate`),'%Y-%m')
                              AS date_unix,
                          COUNT(`glpi_tickets`.`id`) AS total_visites
                   FROM `glpi_tickets`
                   $LEFTJOIN
                   $WHERE
                   GROUP BY date_unix
                   ORDER BY `glpi_tickets`.`closedate`";
         break;

      case "inter_avgsolvedtime" :
         $WHERE .= " AND (`glpi_tickets`. `status` = 'old_done'
                          OR `glpi_tickets`.`status` = 'old_notdone')
                     AND `glpi_tickets`.`closedate` IS NOT NULL ";
         $WHERE .= " AND ".getDateRequest("`glpi_tickets`.`closedate`",$begin,$end);

         $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`glpi_tickets`.`closedate`),'%Y-%m')
                              AS date_unix,
                          AVG(TIME_TO_SEC(TIMEDIFF(`glpi_tickets`.`closedate`, `glpi_tickets`.`date`))
                          /".HOUR_TIMESTAMP.") AS total_visites
                   FROM `glpi_tickets`
                   $LEFTJOIN
                   $WHERE
                   GROUP BY date_unix
                   ORDER BY `glpi_tickets`.`closedate`";
         break;

      case "inter_avgrealtime" :
         if ($param=="technicien_followup") {
            $realtime_table = "glpi_ticketfollowups";
         } else {
            $realtime_table = "glpi_tickets";
         }
         $WHERE .= " AND `$realtime_table`.`realtime` > '0' ";
         $WHERE .= " AND ".getDateRequest("`glpi_tickets`.`closedate`",$begin,$end);

         $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`glpi_tickets`.`closedate`),'%Y-%m')
                              AS date_unix,
                          ".MINUTE_TIMESTAMP." * AVG(`$realtime_table`.`realtime`) AS total_visites
                   FROM `glpi_tickets`
                   $LEFTJOIN
                   $WHERE
                   GROUP BY date_unix
                   ORDER BY `glpi_tickets`.`closedate`";
         break;

      case "inter_avgtakeaccount" :
         $WHERE .= " AND (`glpi_tickets`.`status` = 'old_done'
                          OR `glpi_tickets`.`status` = 'old_notdone')
                     AND `glpi_tickets`.`closedate` IS NOT NULL ";
         $WHERE .= " AND ".getDateRequest("`glpi_tickets`.`closedate`",$begin,$end);

         $query = "SELECT `glpi_tickets`.`id`,
                          FROM_UNIXTIME(UNIX_TIMESTAMP(`glpi_tickets`.`closedate`),'%Y-%m')
                               AS date_unix,
                          MIN(UNIX_TIMESTAMP(`glpi_tickets`.`closedate`)
                              - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS OPEN,
                          MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`)
                              - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST
                   FROM `glpi_tickets`
                   LEFT JOIN `glpi_ticketfollowups`
                        ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`) ";

         if (!strstr($LEFTJOIN,"glpi_ticketfollowups")) {
            $query .= $LEFTJOIN;
         }
         $query .= "$WHERE
                    GROUP BY `glpi_tickets`.`id`";
         break;
   }

   $entrees=array();
   $count=array();
   if (empty($query)) {
      return array();
   }

   $result=$DB->query($query);
   if ($result && $DB->numrows($result)>0) {
      while ($row = $DB->fetch_array($result)) {
         $date = $row['date_unix'];
         if ($type=="inter_avgtakeaccount") {
            $min=$row["OPEN"];
            if (!empty($row["FIRST"]) && !is_null($row["FIRST"]) && $row["FIRST"]<$min) {
               $min=$row["FIRST"];
            }
            if (!isset($entrees["$date"])) {
               $entrees["$date"]=$min;
               $count["$date"]=1;
            } else {
               $entrees["$date"]+=$min;
               $count["$date"]++;
            }
         } else {
            $visites = round($row['total_visites']);
            $entrees["$date"] = $visites;
         }
      }
   }

   if ($type=="inter_avgtakeaccount") {
      foreach ($entrees as $key => $val) {
         $entrees[$key] = round($entrees[$key] / $count[$key] / HOUR_TIMESTAMP);
      }
   }

   // Remplissage de $entrees pour les mois ou il n'y a rien
   $min=-1;
   $max=0;
   if (count($entrees)==0) {
      return $entrees;
   }
   foreach ($entrees as $key => $val) {
      $time=strtotime($key."-01");
      if ($min>$time || $min<0) {
         $min=$time;
      }
      if ($max<$time) {
         $max=$time;
      }
   }

   $end_time=strtotime(date("Y-m",strtotime($end))."-01");
   $begin_time=strtotime(date("Y-m",strtotime($begin))."-01");

   if ($max<$end_time) {
      $max=$end_time;
   }
   if ($min>$begin_time) {
      $min=$begin_time;
   }
   $current=$min;

   while ($current<=$max) {
      $curentry=date("Y-m",$current);
      if (!isset($entrees["$curentry"])) {
         $entrees["$curentry"]=0;
      }
      $month=date("m",$current);
      $year=date("Y",$current);
      $current=mktime(0,0,0,intval($month)+1,1,intval($year));
   }

   return $entrees;
}


/** Get groups assigned to tickets between 2 dates
* BASED ON SPIP DISPLAY GRAPH : www.spip.net
* @param $type string : "month" or "year"
* @param $entrees array : array containing data to displayed
* @param $titre string : title
* @param $unit string : unit
* @param $showtotal boolean : also show total values ?
* @return array contains the distinct groups assigned to a tickets
*/
function graphBy($entrees,$titre="",$unit="",$showtotal=1,$type="month") {
   global $DB,$CFG_GLPI,$LANG;

   ksort($entrees);
   $total="";
   if ($showtotal==1) {
      $total=array_sum($entrees);
   }

   echo "<p class='center'>";
   echo "<font face='verdana,arial,helvetica,sans-serif' size='2'>";
   echo "<strong>$titre - $total $unit</strong></font>";
   echo "<div class='center'>";

   if (count($entrees)>0) {
      $max = max($entrees);
      $maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));

      if ($maxgraph < 10) {
         $maxgraph = 10;
      }
      if (1.1 * $maxgraph < $max) {
         $maxgraph.="0";
      }
      if (0.8*$maxgraph > $max) {
         $maxgraph = 0.8 * $maxgraph;
      }
      $rapport = 200 / $maxgraph;

      $largeur = floor(420 / (count($entrees)));
      if ($largeur < 1) {
         $largeur = 1;
      }
      if ($largeur > 50) {
         $largeur = 50;
      }
   }

   echo "<table class='tab_glpi'><tr>";
   echo "<td style='background-image:url(".$CFG_GLPI["root_doc"]."/pics/fond-stats.gif)' >";
   echo "<table><tr><td bgcolor='black'>";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width='1' height='200' alt=''></td>";

   // Presentation graphique
   $n = 0;
   $decal = 0;
   $tab_moyenne = "";
   $total_loc = 0;
   while (list($key, $value) = each($entrees)) {
      $n++;
      if ($decal == 30) {
         $decal = 0;
      }
      $decal ++;
      $tab_moyenne[$decal] = $value;

      $total_loc = $total_loc + $value;
      reset($tab_moyenne);

      $moyenne = 0;
      while (list(,$val_tab) = each($tab_moyenne)) {
         $moyenne += $val_tab;
      }
      $moyenne = $moyenne / count($tab_moyenne);

      $hauteur_moyenne = round($moyenne * $rapport) ;
      $hauteur = round($value * $rapport)	;
      echo "<td class='bottom' width=".$largeur.">";

      if ($hauteur >= 0) {
         if ($hauteur_moyenne > $hauteur) {
            $difference = ($hauteur_moyenne - $hauteur) -1;
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/moyenne.png' width=".$largeur." height='1' >";
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/rien.gif' width=".$largeur." height=".$difference." >";
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/noir.png' width=".$largeur." height='1' >";
            if (strstr($key,"-01")) { // janvier en couleur foncee
               echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                      "/pics/fondgraph1.png' width=".$largeur." height=".$hauteur." >";
            } else {
               echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                      "/pics/fondgraph2.png' width=".$largeur." height=".$hauteur." >";
            }
         } else if ($hauteur_moyenne < $hauteur) {
            $difference = ($hauteur - $hauteur_moyenne) -1;
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/noir.png' width=".$largeur." height='1'>";
            if (strstr($key,"-01")) { // janvier en couleur foncee
               $couleur = "1";
               $couleur2 = "2";
            } else {
               $couleur = "2";
               $couleur2 = "1";
            }
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/fondgraph$couleur.png' width=".$largeur." height=".$difference.">";
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/moyenne.png' width=".$largeur." height='1'>";
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/fondgraph$couleur.png' width=".$largeur." height=".$hauteur_moyenne.">";
         } else {
            echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                   "/pics/noir.png' width=".$largeur." height='1'>";
            if (strstr($key,"-01")) { // janvier en couleur foncee
               echo "<img alt=\"$key: $val_tab\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                      "/pics/fondgraph1.png' width=".$largeur." height=".$hauteur.">";
            } else {
               echo "<img alt=\"$key: $value\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"].
                      "/pics/fondgraph2.png' width=".$largeur." height=".$hauteur.">";
            }
         }
      }
      echo "<img alt=\"$value\" title=\"$value\" src='".$CFG_GLPI["root_doc"].
             "/pics/rien.gif' width=".$largeur." height='1'>";
      echo "</td>\n";
   }
   echo "<td bgcolor='black'>";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width='1' height='1' alt=''></td></tr>";
   if ($largeur>10) {
      echo "<tr><td></td>";
      foreach ($entrees as $key => $val) {
         if ($type=="month") {
            $splitter=explode("-",$key);
            echo "<td class='center'>".utf8_substr($LANG['calendarM'][$splitter[1]-1],0,3)."</td>";
         } else if ($type=="year") {
            echo "<td class='center'>".substr($key,2,2)."</td>";
         }
      }
      echo "</tr>";
   }

   if ($maxgraph<=10) {
      $r=2;
   } else if ($maxgraph<=100) {
      $r=1;
   } else {
      $r=0;
   }
   echo "</table>";
   echo "</td>";
   echo "<td style='background-image:url(".$CFG_GLPI["root_doc"]."/pics/fond-stats.gif)' class='bottom'>";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rien.gif' style='background-color:black;' ".
          "width='3' height='1' alt=''></td>";
   echo "<td><img src='".$CFG_GLPI["root_doc"]."/pics/rien.gif' width='5' height='1' alt=''></td>";
   echo "<td class='top'>";
   echo "<table>";
   echo "<tr><td height='15' class='top'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' class ='b'>".
          formatNumber($maxgraph,false,$r)."</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".
          formatNumber(7*($maxgraph/8),false,$r)."</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1'>".formatNumber(3*($maxgraph/4),false,$r);
   echo "</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".
          formatNumber(5*($maxgraph/8),false,$r)."</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' class ='b'>".
          formatNumber($maxgraph/2,false,$r)."</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".
          formatNumber(3*($maxgraph/8),false,$r)."</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1'>".formatNumber($maxgraph/4,false,$r);
   echo "</font></td></tr>";
   echo "<tr><td height='25' class='middle'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".
          formatNumber(1*($maxgraph/8),false,$r)."</font></td></tr>";
   echo "<tr><td height='10' class='bottom'>";
   echo "<font face='arial,helvetica,sans-serif' size='1' class='b'>0</font></td></tr>";

   echo "</table>";
   echo "</td></tr></table>";
   echo "</div>";
}


function showItemStats($target,$date1,$date2,$start) {
   global $DB,$CFG_GLPI,$LANG;

   $view_entities=isMultiEntitiesMode();

   if ($view_entities) {
      $entities=getAllDatasFromTable('glpi_entities');
   }

   $output_type=HTML_OUTPUT;
   if (isset($_GET["display_type"])) {
      $output_type=$_GET["display_type"];
   }
   if (empty($date2)) {
      $date2=date("Y-m-d");
   }
   $date2.=" 23:59:59";

   // 1 an par defaut
   if (empty($date1)) {
      $date1 = date("Y-m-d",mktime(0,0,0,date("m"),date("d"),date("Y")-1));
   }
   $date1.=" 00:00:00";

   $query = "SELECT `itemtype`, `items_id`, COUNT(*) AS NB
             FROM `glpi_tickets`
             WHERE `date` <= '$date2'
                   AND `date` >= '$date1' ".
                   getEntitiesRestrictRequest("AND","glpi_tickets")."
             GROUP BY `itemtype`, `items_id`
             ORDER BY NB DESC";

   $result=$DB->query($query);
   $numrows=$DB->numrows($result);

   if ($numrows>0) {
      if ($output_type==HTML_OUTPUT) {
         printPager($start,$numrows,$target,
                    "date1=".$date1."&amp;date2=".$date2."&amp;type=hardwares&amp;start=$start",
                    'Stat');
         echo "<div class='center'>";
      }

      $end_display=$start+$_SESSION['glpilist_limit'];
      if (isset($_GET['export_all'])) {
         $end_display=$numrows;
      }
      echo displaySearchHeader($output_type,$end_display-$start+1,2,1);
      $header_num=1;
      echo displaySearchNewLine($output_type);
      echo displaySearchHeaderItem($output_type,$LANG['common'][1],$header_num);
      if ($view_entities) {
         echo displaySearchHeaderItem($output_type,$LANG['entity'][0],$header_num);
      }
      echo displaySearchHeaderItem($output_type,$LANG['stats'][13],$header_num);
      echo displaySearchEndLine($output_type);

      $DB->data_seek($result,$start);

      $i=$start;
      if (isset($_GET['export_all'])) {
         $start=0;
      }
      for ($i = $start ;$i < $numrows && $i<$end_display ;$i++) {
         $item_num=1;
         // Get data and increment loop variables
         $data=$DB->fetch_assoc($result);
         if (!class_exists($data["itemtype"])) {
            continue;
         }
         $item = new $data["itemtype"]();
         if ($item->getFromDB($data["items_id"])) {

            echo displaySearchNewLine($output_type,$i%2);
            echo displaySearchItem($output_type,$item->getTypeName()." - ".$item->getLink(),$item_num,
                                   $i-$start+1,"class='center'"." ".
                                   ($item->getField("is_deleted")?" class='deleted' ":""));
            if ($view_entities) {
               $ent=$item->getField('entities_id');
               if ($ent==0) {
                  $ent=$LANG['entity'][2];
               } else {
                  $ent=$entities[$ent]['completename'];
               }
               echo displaySearchItem($output_type,$ent,$item_num,$i-$start+1,"class='center'"." ".
                                      ($item->getField("is_deleted")?" class='deleted' ":""));
            }
            echo displaySearchItem($output_type,$data["NB"],$item_num,$i-$start+1,
                                   "class='center'"." ".
                                   ($item->getField("is_deleted")?" class='deleted' ":""));
         }
      }

      echo displaySearchFooter($output_type);
      if ($output_type==HTML_OUTPUT) {
         echo "</div>";
      }
   }
}

?>
