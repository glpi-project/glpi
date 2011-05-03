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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file: Purge history with some criterias
// ----------------------------------------------------------------------
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

if ($argv) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it = explode("=",$_SERVER['argv'][$i]);
      $it[0] = preg_replace('/^--/','',$it[0]);
      $_GET[$it[0]] = $it[1];
   }
}

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$CFG_GLPI["debug"]=0;


if (!isset($_GET['delay'])) {
   print "
*******************************************
 This script kill babies : don t use it !!

   If you really want to try it:
   Do a full backup before use.
*******************************************

Usage : php cleanhistory.php [ --item=# ] [ --type=# ] [ --old=<regex> ] [ --new=<regex> ]
                             [ --run=1 ] [ --optimize=1 ] --delay=#

   With item a string value in  (optionnal):
      Computer                Software
      NetworkEquipment        SoftwareLicense
      Printer                 SoftwareVersion
      Monitor                 Ticket
      Peripheral
      Phone                   Others : see inc/*.class.php
   With type integer value in (optionnal):
      1 : Add device
      2 : Update device          11 : OCS Link
      3 : Delete device          12 : Other (often from plugin)
      4 : Install software       13 : Delete item (put in trash)
      5 : Uninstall software     14 : Restore item from trash
      6 : Disconnect device      15 : Add relation
      7 : Connect device         16 : Delete relation
      8 : OCS Import             17 : Add sub item
      9 : OCS Delete             18 : Update sub item
     10 : OCS ID Changed         19 : Delete sub item
   With old an optional regex pattern on old_value
   With new an optional regex pattern on new_value
   With delay in month (mandatory).\n\n";
   die();
}

$table = 'glpi_logs';
echo "    Total entries in history : ".countElementsInTable($table)."\n";

$where = "`date_mod` < SUBDATE(NOW(), INTERVAL ".$_GET['delay']." month)";

if (isset($_GET['item'])) {
   $where .= " AND `itemtype` = '".$_GET['item']."'";
}

if (isset($_GET['type'])) {
   $where .= " AND `linked_action` = ".intval($_GET['type']);
}

if (isset($_GET['old'])) {
   $where .= " AND `old_value` REGEXP '".$_GET['old']."'";
}

if (isset($_GET['new'])) {
   $where .= " AND `new_value` REGEXP '".$_GET['new']."'";
}

if (isset($_GET['run'])) {
   $query = "DELETE QUICK
             FROM `$table`
             WHERE $where";
   $res = $DB->query($query);

   if (!$res) {
      die("SQL request: $query\nSQL error : ".$DB->error()."\n");
   }

   echo "  Deleted entries in history : ".$DB->affected_rows()."\n";
   echo "Remaining entries in history : ".countElementsInTable($table)."\n";

   if (isset($_GET['optimize'])) {
      foreach ($DB->request("OPTIMIZE TABLE `$table`") as $data) {
         echo "Table Optimization for ".$data['Table'].": ".$data['Msg_type']." = ".
               $data['Msg_text']."\n";
      }
   }

} else {
   echo " Selected entries in history : ".countElementsInTable($table, $where)."\n";
}

?>