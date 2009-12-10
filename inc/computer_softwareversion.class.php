<?php
/*
 * @version $Id: contract_item.class.php 9363 2009-11-26 21:02:42Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
// TODO move this as a CommonDBRelation
class Computer_SoftwareVersion extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_computers_softwareversions';
   public $type = 'Computer_SoftwareVersion';

   // From CommonDBRelation
   //public $itemtype_1 = 'Computer';
   //public $items_id_1 = 'computers_id';
   //public $itemtype_2 = 'SoftwareVersion';
   //public $items_id_2 = 'softwareversions_id';

   /**
    * Get number of installed licenses of a version
    *
    * @param $softwareversions_id version ID
    * @param $entity to search for computer in (default = all active entities)
    *
    * @return number of installations
    */
   static function countForVersion($softwareversions_id, $entity='') {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwareversions`.`id`)
                FROM `glpi_computers_softwareversions`
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_computers_softwareversions`.`softwareversions_id`='$softwareversions_id'
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
    * Get number of installed versions of a software
    *
    * @param $softwares_id software ID
    * @return number of installations
    */
   static function countForSoftware($softwares_id) {
      global $DB;

      $query = "SELECT COUNT(`glpi_computers_softwareversions`.`id`)
                FROM `glpi_softwareversions`
                INNER JOIN `glpi_computers_softwareversions`
                      ON (`glpi_softwareversions`.`id`
                          = `glpi_computers_softwareversions`.`softwareversions_id`)
                INNER JOIN `glpi_computers`
                      ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                WHERE `glpi_softwareversions`.`softwares_id` = '$softwares_id'
                      AND `glpi_computers`.`is_deleted` = '0'
                      AND `glpi_computers`.`is_template` = '0' " .
                      getEntitiesRestrictRequest('AND', 'glpi_computers');

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }

}

?>