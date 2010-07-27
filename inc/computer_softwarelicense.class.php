<?php
/*
 * @license $Id: computer_softwarelicense.class.php 11680 2010-06-11 17:10:12Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either license 2 of the License, or
 (at your option) any later license.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

class Computer_SoftwareLicense extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Computer';
   public $items_id_1 = 'computers_id';
   public $itemtype_2 = 'SoftwareLicence';
   public $items_id_2 = 'softwarelicenses_id';

   /**
    * Get number of installed licenses of a license
    *
    * @param $softwarelicenses_id license ID
    * @param $entity to search for computer in (default = all active entities)
    *
    * @return number of installations
    */
   static function countForLicense($softwarelicenses_id, $entity='') {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwarelicenses`.`id`)
                FROM `glpi_computers_softwarelicenses`
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_computers_softwarelicenses`.`softwarelicenses_id`='$softwarelicenses_id'
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0' " .
                      getEntitiesRestrictRequest('AND', 'glpi_computers','',$entity);

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }

   /**
    * Get number of installed licenses of a software
    *
    * @param $softwares_id software ID
    * @return number of installations
    */
   static function countForSoftware($softwares_id) {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwarelicenses`.`id`)
                FROM `glpi_softwarelicenses`
                INNER JOIN `glpi_computers_softwarelicenses`
                      ON (`glpi_softwarelicenses`.`id`
                          = `glpi_computers_softwarelicenses`.`softwarelicenses_id`)
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwarelicenses`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_softwarelicenses`.`softwares_id` = '$softwares_id'
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0' " .
                      getEntitiesRestrictRequest('AND', 'glpi_computers');

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }

   /**
    * Show number of installation per entity
    *
    * @param $license SoftwareVersion object
    *
    * @return nothing
    */
   static function showForLicenseByEntity(SoftwareLicense $license) {
      global $DB, $CFG_GLPI, $LANG;

      $softwarelicense_id = $license->getField('id');

      if (!haveRight("software", "r") || !$softwarelicense_id) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th>&nbsp;".$LANG['entity'][0]."&nbsp;</th>";
      echo "<th>&nbsp;".$LANG['software'][19]."&nbsp;</th>";
      echo "</tr>\n";

      $tot=0;
      if (in_array(0,$_SESSION["glpiactiveentities"])) {
         $nb = self::countForLicense($softwarelicense_id,0);
         if ($nb>0) {
            echo "<tr class='tab_bg_2'><td>" . $LANG['entity'][2] . "</td>";
            echo "<td class='right'>" . $nb . "</td></tr>\n";
            $tot+=$nb;
         }
      }
      $sql = "SELECT `id`, `completename`
              FROM `glpi_entities` " .
              getEntitiesRestrictRequest('WHERE', 'glpi_entities') ."
              ORDER BY `completename`";

      foreach ($DB->request($sql) as $ID => $data) {
         $nb = self::countForLicense($softwarelicense_id,$ID);
         if ($nb>0) {
            echo "<tr class='tab_bg_2'><td>" . $data["completename"] . "</td>";
            echo "<td class='right'>".$nb."</td></tr>\n";
            $tot+=$nb;
         }
      }
      if ($tot>0) {
         echo "<tr class='tab_bg_1'><td class='right b'>".$LANG['common'][33]."</td>";
         echo "<td class='right b'>".$tot."</td></tr>\n";
      } else {
         echo "<tr class='tab_bg_1'><td colspan='2 b'>" . $LANG['search'][15] . "</td></tr>\n";
      }
      echo "</table></div>";
   }


}

?>