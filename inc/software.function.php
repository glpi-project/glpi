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

/**
 * Install a software on a computer
 *
 * @param $computers_id ID of the computer where to install a software
 * @param $softwareversions_id ID of the version to install
 * @param $dohistory Do history ?
 * @return nothing
 */
function installSoftwareVersion($computers_id, $softwareversions_id, $dohistory=1) {
   global $DB,$LANG;

   if (!empty ($softwareversions_id) && $softwareversions_id > 0) {
      $query_exists = "SELECT `id`
                       FROM `glpi_computers_softwareversions`
                       WHERE (`computers_id` = '$computers_id'
                              AND `softwareversions_id` = '$softwareversions_id')";
      $result = $DB->query($query_exists);
      if ($DB->numrows($result) > 0) {
         return $DB->result($result, 0, "id");
      }
      $tmp = new Computer_SoftwareVersion();
      return $tmp->add(array('computers_id'        => $computers_id,
                             'softwareversions_id' => $softwareversions_id,
                             '_no_history'         => !$dohistory));
   }
   return 0;
}


/**
 * Update version installed on a computer
 *
 * @param $instID ID of the install software lienk
 * @param $newvID ID of the new version
 * @param $dohistory Do history ?
 * @return nothing
 */
function updateInstalledVersion($instID, $newvID, $dohistory=1) {
   global $DB;

   $query_exists = "SELECT *
                    FROM `glpi_computers_softwareversions`
                    WHERE `id` = '$instID'";
   $result = $DB->query($query_exists);
   if ($DB->numrows($result) > 0) {
      $computers_id=$DB->result($result, 0, "computers_id");
      $softwareversions_id=$DB->result($result, 0, "softwareversions_id");
      if ($softwareversions_id!=$newvID && $newvID>0) {
         uninstallSoftwareVersion($instID, $dohistory);
         installSoftwareVersion($computers_id, $newvID, $dohistory);
      }
   }
}


/**
 * Uninstall a software on a computer
 *
 * @param $ID ID of the install software link (license/computer)
 * @param $dohistory Do history ?
 * @return nothing
 */
function uninstallSoftwareVersion($ID, $dohistory = 1) {
   global $DB;

   $query2 = "SELECT *
              FROM `glpi_computers_softwareversions`
              WHERE `id` = '$ID'";
   $result2 = $DB->query($query2);
   $data = $DB->fetch_array($result2);
   // Not found => nothing to do
   if (!$data) {
      return false;
   }

   $tmp = new Computer_SoftwareVersion();
   return $tmp->delete(array('id'          => $ID,
                             '_no_history' => !$dohistory));
}


?>