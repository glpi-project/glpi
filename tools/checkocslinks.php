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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

ini_set('display_errors',1);
restore_error_handler();

$_GET = array();
if (isset($_SERVER['argv'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it = explode("=",$_SERVER['argv'][$i],2);
      $it[0] = preg_replace('/^--/','',$it[0]);

      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}
if (isset($_GET['help']) || !count($_GET)) {
   echo "Usage : php checkocslinks.php [ options ]\n";
   echo "Options values :\n";
   echo "\t--glpi   : check missing computer in GLPI\n";
   echo "\t--ocs    : check missing computer in OCS\n";
   echo "\t--clean  : delete invalid link\n";
   exit (0);
}
$tps    = microtime(true);
$nbchk  = 0;
$nbdel  = 0;
$nbtodo = 0;

$crit = array('is_active' => 1);
foreach ($DB->request('glpi_ocsservers', $crit) as $serv) {
   $ocsservers_id=$serv ['id'];
   echo "\nServeur: ".$serv['name']."\n";

   if (!OcsServer::checkOCSconnection($ocsservers_id)) {
      echo "** no connexion\n";
      continue;
   }

   if (isset($_GET['clean'])) {
      echo "+ Handle ID changes\n";
      OcsServer::manageDeleted($ocsservers_id);
   }

   if (isset($_GET['glpi'])) {
      echo "+ Search links with no computer in GLPI\n";
      $query = "SELECT `glpi_ocslinks`.`id`, `glpi_ocslinks`.`ocs_deviceid`
                FROM `glpi_ocslinks`
                LEFT JOIN `glpi_computers` ON `glpi_computers`.`id`=`glpi_ocslinks`.`computers_id`
                WHERE `glpi_computers`.`id` IS NULL
                      AND `ocsservers_id`='$ocsservers_id'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_array($result)) {
            $nbchk++;
            printf("%12d : %s\n", $data['id'], $data['ocs_deviceid']);
            if (isset($_GET['clean'])) {
               $query2 = "DELETE
                          FROM `glpi_ocslinks`
                          WHERE `id` = '" . $data['id'] . "'";
               if ($DB->query($query2)) {
                  $nbdel++;
               }
            } else {
               $nbtodo++;
            }
         }
      }
   }

   if (isset($_GET['ocs'])) {
      echo "+ Search OCS Computers\n";
      $query_ocs = "SELECT `ID`, `DEVICEID`
                    FROM `hardware`";
      $result_ocs = $DBocs->query($query_ocs);

      $hardware = array ();
      $nb = $DBocs->numrows($result_ocs);
      if ($nb > 0) {
         for ($i=1 ; $data = $DBocs->fetch_array($result_ocs) ; $i++) {
            $data = clean_cross_side_scripting_deep(addslashes_deep($data));
            $hardware[$data["ID"]] = $data["DEVICEID"];
            echo "$i/$nb\r";
         }
         echo "  $nb computers in OCS\n";
      }

      echo "+ Search links with no computer in OCS\n";
      $query = "SELECT `id`, `ocsid`, `ocs_deviceid`
                FROM `glpi_ocslinks`
                WHERE `ocsservers_id` = '$ocsservers_id'";

      $result = $DB->query($query);
      $nb = $DB->numrows($result);
      if ($nb > 0) {
         for ($i=1 ; $data = $DB->fetch_array($result) ; $i++) {
            $nbchk++;
            $data = clean_cross_side_scripting_deep(addslashes_deep($data));
            if (isset ($hardware[$data["ocsid"]])) {
               echo "$i/$nb\r";
            } else {
               printf("%12d : %s\n", $data['id'], $data['ocs_deviceid']);
               if (isset($_GET['clean'])) {
                  $query_del = "DELETE
                                FROM `glpi_ocslinks`
                                WHERE `id` = '" . $data["id"] . "'";
                  if ($DB->query($query_del)) {
                     $nbdel++;
                  }
               } else {
                  $nbtodo++;
               }
            }
         }
         echo "  $nb links checked\n";
      }
   }
}
$tps = microtime(true)-$tps;
printf("\nChecked links : %d\n", $nbchk);
if (isset($_GET['clean'])) {
   printf("Deleted links : %d\n", $nbdel);
} else {
   printf("Corrupt links : %d\n", $nbtodo);
}
printf("Done in %s\n", timestampToString(round($tps,0),true));
