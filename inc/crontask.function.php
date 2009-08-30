<?php

/*
 * @version $Id: bookmark.class.php 8095 2009-03-19 18:27:00Z moyo $
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

/**
 * Display detail of a runned task
 *
 * @param $ID : crontasks_id
 * @param $logid : crontaskslogs_id
 */
function showCronTaskHistoryDetail($ID,$logid) {
   global $DB, $CFG_GLPI, $LANG;

   echo "<br><div class='center'>";
   echo "<p><a href='javascript:reloadTab(\"crontaskslogs_id=0\");'>".$LANG['crontask'][47]."</a></p>";

   $query = "SELECT *
      FROM `glpi_crontaskslogs`
      WHERE `id`='$logid' OR `crontaskslogs_id`='$logid'
      ORDER BY `id` ASC";

   if ($result=$DB->query($query)){
      if ($data=$DB->fetch_assoc($result)){
         echo "<table class='tab_cadrehov'><tr>";
         echo "<th>".$LANG['common'][27]."</th>"; // Date
         echo "<th>".$LANG['joblist'][0]."</th>"; // statut
         echo "<th>".$LANG['job'][31]."</th>"; // Duration
         echo "<th>".$LANG['tracking'][29]."</th>"; // Number
         echo "<th>".$LANG['crontask'][30]."</th>"; // Dexcription
         echo "</tr>\n";

         $first=true;
         do {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>".($first ? $data['date'] : "&nbsp;")."</a></td>";
            switch ($data['state']) {
               case CRONTASKLOG_STATE_START:
                  echo "<td>".$LANG['crontask'][48]."</td>";
                  break;
               case CRONTASKLOG_STATE_STOP:
                  echo "<td>".$LANG['crontask'][49]."</td>";
                  break;
               default:
                  echo "<td>".$LANG['crontask'][33]."</td>";
            }
            echo "<td class='right'>".number_format($data['elapsed'],3)."s</td>";
            echo "<td class='right'>".$data['volume']."</td>";
            echo "<td>".$data['content']."</td>";
            echo "</tr>\n";
            $first=false;
         } while ($data=$DB->fetch_assoc($result));

         echo "</table>";

      } else { // Not found
         echo $LANG['search'][15];
      }
   } // Query

   echo "</div>";
}
/**
 * Display list of a runned tasks
 *
 * @param $ID : crontasks_id
 */
function showCronTaskHistory($ID) {
   global $DB, $CFG_GLPI, $LANG;

   if (isset($_REQUEST["crontaskslogs_id"]) && $_REQUEST["crontaskslogs_id"]) {
      return showCronTaskHistoryDetail($ID,$_REQUEST["crontaskslogs_id"]);
   }

   if (isset($_REQUEST["start"])) {
      $start = $_REQUEST["start"];
   } else {
      $start = 0;
   }

   // Total Number of events
   $number = countElementsInTable('glpi_crontaskslogs',
          "`crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'");

   echo "<br><div class='center'>";
   if ($number < 1) {
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['search'][15]."</th></tr>";
      echo "</table>";
      echo "</div>";
      return;
   }

   // Display the pager
   printAjaxPager($LANG['crontask'][47],$start,$number);

   $query = "SELECT *
      FROM `glpi_crontaskslogs`
      WHERE `crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'
      ORDER BY `id` DESC
      LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

   if ($result=$DB->query($query)){
      if ($data=$DB->fetch_assoc($result)){
         echo "<table class='tab_cadrehov'><tr>";
         echo "<th>".$LANG['common'][27]."</th>"; // Date
         echo "<th>".$LANG['job'][20]."</th>"; // Duration
         echo "<th>".$LANG['tracking'][29]."</th>"; // Number
         echo "<th>".$LANG['crontask'][30]."</th>"; // Dexcription
         echo "</tr>\n";

         do {
            echo "<tr class='tab_bg_2'>";
            echo "<td><a href='javascript:reloadTab(\"crontaskslogs_id=".
               $data['crontaskslogs_id']."\");'>".$data['date']."</a></td>";
            echo "<td class='right'>".number_format($data['elapsed'],3)."s</td>";
            echo "<td class='right'>".$data['volume']."</td>";
            echo "<td>".$data['content']."</td>";
            echo "</tr>\n";
         } while ($data=$DB->fetch_assoc($result));

         echo "</table>";

      } else { // Not found
         echo $LANG['search'][15];
      }
   } // Query
   echo "</div>";
}

/**
 * Display statistics of a task
 *
 * @param $ID : crontasks_id
 */
function showCronStatistics($ID) {
   global $DB, $CFG_GLPI, $LANG;

   echo "<br><div class='center'>";
   echo "<table class='tab_cadre'><tr>";
   echo "<th colspan='2'>&nbsp;".$LANG['Menu'][13]."&nbsp;</th>"; // Date
   echo "</tr>\n";

   $nbstart = countElementsInTable('glpi_crontaskslogs',
          "`crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_START."'");
   $nbstop = countElementsInTable('glpi_crontaskslogs',
          "`crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'");

   echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][50]."&nbsp;:</td><td class='right'>";
   if ($nbstart==$nbstop) {
      echo $nbstart;
   } else {
      // This should not appen => task crash ?
      echo $LANG['crontask'][48]." = $nbstart<br>".$LANG['crontask'][49]." = $nbstop";
   }
   echo "</td></tr>";

   if ($nbstop) {
      $query = "SELECT
                  MIN(`elapsed`) AS elapsedmin,
                  MAX(`elapsed`) AS elapsedmax,
                  AVG(`elapsed`) AS elapsedavg,
                  SUM(`elapsed`) AS elapsedtot,
                  MIN(`volume`) AS volmin,
                  MAX(`volume`) AS volmax,
                  AVG(`volume`) AS volavg,
                  SUM(`volume`) AS voltot
               FROM `glpi_crontaskslogs`
               WHERE `crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'";
      $result = $DB->query($query);
      if ($data = $DB->fetch_assoc($result)) {
         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][51]."&nbsp;:</td>";
         echo "<td class='right'>".number_format($data['elapsedmin'],2)."s</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][52]."&nbsp;:</td>";
         echo "<td class='right'>".number_format($data['elapsedmax'],2)."s</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][53]."&nbsp;:</td>";
         echo "<td class='right'>".number_format($data['elapsedavg'],2)."s</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][54]."&nbsp;:</td>";
         echo "<td class='right'>".number_format($data['elapsedtot'],2)."s</td></tr>";
      }
      if ($data && $data['voltot']>0) {
         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][55]."&nbsp;:</td>";
         echo "<td class='right'>".$data['volmin']."</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][56]."&nbsp;:</td>";
         echo "<td class='right'>".$data['volmax']."</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][57]."&nbsp;:</td>";
         echo "<td class='right'>".number_format($data['volavg'],2)."</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][58]."&nbsp;:</td>";
         echo "<td class='right'>".$data['voltot']."</td></tr>";

         echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][59]."&nbsp;:</td>";
         echo "<td class='right'>".number_format($data['voltot']/$data['elapsedtot'],2)."</td></tr>";
      }
   }
   echo "</table></div>";
}
?>
