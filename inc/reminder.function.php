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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

function showCentralReminder($entity = -1, $parent = false) {
   global $DB,$CFG_GLPI, $LANG;

   // show reminder that are not planned
   $users_id=$_SESSION['glpiID'];
   $today=$_SESSION["glpi_currenttime"];

   if ($entity < 0) {
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `users_id` = '$users_id'
                      AND `is_private` = '1'
                      AND (`end` >= '$today'
                           OR `is_planned` = '0')
                ORDER BY `name`";
      $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG['reminder'][0]."</a>";
      $is_private = 1;
   } else if ($entity == $_SESSION["glpiactive_entity"]) {
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `is_private` = '0' ".
                      getEntitiesRestrictRequest("AND","glpi_reminders","",$entity)."
                ORDER BY `name`";
      $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG['reminder'][1].
               "</a> (".CommonDropdown::getDropdownName("glpi_entities", $entity).")";

      if (haveRight("reminder_public","w")) {
         $is_private = 0;
      }
   } else if ($parent) {
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `is_private` = '0'
                      AND `is_recursive` = '1' ".
                      getEntitiesRestrictRequest("AND","glpi_reminders","",$entity)."
                ORDER BY `name`";
      $titre = $LANG['reminder'][1]." (".CommonDropdown::getDropdownName("glpi_entities", $entity).")";
   } else { // Filles
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `is_private` = '0' ".
                      getEntitiesRestrictRequest("AND","glpi_reminders","",$entity)."
                ORDER BY `name`";
      $titre = $LANG['reminder'][1]." (".CommonDropdown::getDropdownName("glpi_entities", $entity).")";
   }

   $result = $DB->query($query);
   $nb=$DB->numrows($result);

   if ($nb || isset($is_private)) {
      echo "<br><table class='tab_cadrehov'>";
      echo "<tr><th><div class='relative'><span>$titre</span>";
      if (isset($is_private)) {
         echo "<span class='reminder_right'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?is_private=$is_private\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".
                $LANG['buttons'][8]."'></a></span>";
      }
      echo "</div></th></tr>\n";
   }
   if ($nb) {
      $rand=mt_rand();
      while ($data =$DB->fetch_array($result)) {
         echo "<tr class='tab_bg_2'><td><div class='relative reminder_list'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$data["id"]."\">".
                $data["name"]."</a>&nbsp;";
         echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png'
                onmouseout=\"cleanhide('content_reminder_".$data["id"].$rand."')\"
                onmouseover=\"cleandisplay('content_reminder_".$data["id"].$rand."')\">";
         echo "<div class='over_link' id='content_reminder_".$data["id"].$rand."'>".$data["text"]."</div>";

         if ($data["is_planned"]) {
            $tab=explode(" ",$data["begin"]);
            $date_url=$tab[0];
            echo "<span class='reminder_right'>";
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticketplanning.php?date=".$date_url.
                  "&amp;type=day\">";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG['Menu'][29].
                   "' title='".convDateTime($data["begin"])."=>".convDateTime($data["end"])."'>";
            echo "</a></span>";
         }
         echo "</div></td></tr>\n";
      }
   }

   if ($nb || isset($is_private)) {
      echo "</table>\n";
   }
}

function showListReminder($is_private=1,$is_recursive=0) {
   global $DB,$CFG_GLPI, $LANG;

   // show reminder that are not planned
   $planningRight=haveRight("show_planning","1");
   $users_id=$_SESSION['glpiID'];

   if (!$is_private && $is_recursive) { // show public reminder
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `is_private` = '0'
                      AND `is_recursive` = '1' ".
                      getEntitiesRestrictRequest("AND","glpi_reminders","","",true);
      $titre=$LANG['reminder'][16];
   } else if (!$is_private && !$is_recursive) { // show public reminder
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `is_private` = '0'
                      AND `is_recursive` = '0' ".
                      getEntitiesRestrictRequest("AND","glpi_reminders");
      $titre=$LANG['reminder'][1];
   } else { // show private reminder
      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `users_id` = '$users_id'
                      AND `is_private` = '1'";
      $titre=$LANG['reminder'][0];
   }
   $result = $DB->query($query);

   $tabremind=array();
   $remind=new Reminder();

   if ($DB->numrows($result)>0) {
      for ($i=0 ; $data=$DB->fetch_array($result) ; $i++) {
         $remind->getFromDB($data["id"]);
         if ($data["is_planned"]) { //Un rdv on va trier sur la date begin
            $sort=$data["begin"];
         } else { // non programmé on va trier sur la date de modif...
            $sort=$data["date"];
         }
         $tabremind[$sort."$$".$i]["reminders_id"]=$remind->fields["id"];
         $tabremind[$sort."$$".$i]["users_id"]=$remind->fields["users_id"];
         $tabremind[$sort."$$".$i]["entity"]=$remind->fields["entities_id"];
         $tabremind[$sort."$$".$i]["begin"]=($data["is_planned"]?"".$data["begin"]."":"".
                                             $data["date"]."");
         $tabremind[$sort."$$".$i]["end"]=($data["is_planned"]?"".$data["end"]."":"");
         $tabremind[$sort."$$".$i]["name"]=resume_text($remind->fields["name"],$CFG_GLPI["cut"]);
         $tabremind[$sort."$$".$i]["text"]=resume_text($remind->fields["text"],$CFG_GLPI["cut"]);
      }
   }
   ksort($tabremind);

   echo "<br><table class='tab_cadre_fixehov'>";
   if ($is_private) {
      echo "<tr><th>"."$titre"."</th><th colspan='2'>".$LANG['common'][27]."</th></tr>\n";
   } else {
      echo "<tr><th colspan='5'>"."$titre"."</th></tr>\n";
      echo "<tr><th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][37]."</th>";
      echo "<th>".$LANG['title'][37]."</th>";
      echo "<th colspan='2'>".$LANG['common'][27]."</th></tr>\n";
   }

   if (count($tabremind)>0) {
      foreach ($tabremind as $key => $val) {
         echo "<tr class='tab_bg_2'>";
         if (!$is_private) {
            // preg to split line (if needed) before ">" sign in completename
            echo "<td>" .preg_replace("/ ([[:alnum:]])/", "&nbsp;\\1",
                                      CommonDropdown::getDropdownName("glpi_entities", $val["entity"])). "</td>";
            echo "<td>" .CommonDropdown::getDropdownName("glpi_users", $val["users_id"]) . "</td>";
         }
         echo "<td width='60%' class='left'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".
                $val["reminders_id"]."\">".$val["name"]."</a>";
         echo "<div class='kb_resume'>".resume_text($val["text"],125)."</div></td>";

         if ($val["end"]!="") {
            echo "<td class='center'>";
            $tab=explode(" ",$val["begin"]);
            $date_url=$tab[0];
            if ($planningRight) {
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticketplanning.php?date=".$date_url.
                      "&amp;type=day\">";
            }
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG['Menu'][29].
                   "' title='".$LANG['Menu'][29]."'>";
            if ($planningRight) {
               echo "</a>";
            }
            echo "</td>";
            echo "<td class='center' >".convDateTime($val["begin"]);
            echo "<br>".convDateTime($val["end"])."";
         } else {
            echo "<td>&nbsp;</td>";
            echo "<td class='center'>";
            echo "<span style='color:#aaaaaa;'>".convDateTime($val["begin"])."</span>";
         }
         echo "</td></tr>\n";
      }
   }
   echo "</table>\n";
}

?>
