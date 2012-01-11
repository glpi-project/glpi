<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

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


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Session::haveRight("printer","r")) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(2), self::countForCartridge($item));
         }
         return self::getTypeName(2);
      }
      return '';
   }


   static function countForCartridge(CartridgeItem $item) {

      $restrict = "`glpi_cartridgeitems_printermodels`.`cartridgeitems_id`
                           = '".$item->getField('id') ."'
                   AND `glpi_cartridgeitems_printermodels`.`printermodels_id`
                           = `glpi_printermodels`.`id`";

      return countElementsInTable(array('glpi_printermodels', 'glpi_cartridgeitems_printermodels'),
                                  $restrict);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $item->showCompatiblePrinters();
      return true;
   }
}
?>
