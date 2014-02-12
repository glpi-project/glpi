<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief 
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class PrinterModel
class PrinterModel extends CommonDropdown {

   var $additional_fields_for_dictionnary = array('manufacturer');


   static function getTypeName($nb=0) {
      return _n('Printer model', 'Printer models', $nb);
   }


   function cleanDBonPurge() {
      global $DB;

      // Temporary solution to clean wrong updated items
      $query = "DELETE
                FROM `glpi_cartridgeitems_printermodels`
                WHERE `printermodels_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }







}
?>
