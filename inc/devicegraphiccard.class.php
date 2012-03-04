<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

/// Class DeviceGraphicCard
class DeviceGraphicCard extends CommonDevice {

   static function getTypeName($nb=0) {
      return _n('Graphics Card', 'Graphics Cards', $nb);
   }


   static function getSpecifityLabel() {
      //TRANS: (MB) is for MegaBytes
      return array('specificity' => __('Memory (MB)'));
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'specif_default',
                                     'label' => __('Memory by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')),
                               array('name'  => 'interfacetypes_id',
                                     'label' => __('Interface'),
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'specif_default';
      $tab[12]['name']     = __('Memory by default');
      $tab[12]['datatype'] = 'text';

      $tab[14]['table']    = 'glpi_interfacetypes';
      $tab[14]['field']    = 'name';
      $tab[14]['name']     = __('Interface');

      return $tab;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {

      $data['label'] = $data['value'] = array();
      if ($this->fields["interfacetypes_id"]) {
         $data['label'][] = __('Interface');
         $data['value'][] = Dropdown::getDropdownName("glpi_interfacetypes",
                                                      $this->fields["interfacetypes_id"]);
      }
      // Specificity
      $data['label'][] = __('Memory');
      $data['size']    = 10;

      return $data;
   }


   static function getHTMLTableHeaderForComputer_Device(HTMLTable_Group $group,
                                                        HTMLTable_SuperHeader $super,
                                                        HTMLTable_Header &$previous_header) {

      $elements        = array();

      $previous_header = $elements['inter'] = $group->addHeader($super, 'interface',
                                                                __('Interface'), $previous_header);

      $previous_header = $elements['manu'] = $group->addHeader($super, 'manufacturer',
                                                               __('Manufacturer'),
                                                               $previous_header);

      return $elements;
   }

   function getHTMLTableCellsForComputer_Device(HTMLTable_Row $row, $headers,
                                                HTMLTable_Cell &$previous_cell) {

      if ($this->fields["interfacetypes_id"]) {
         $cell_value = Dropdown::getDropdownName("glpi_interfacetypes",
                                                 $this->fields["interfacetypes_id"]);
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['inter'], $cell_value, $previous_cell);
      $previous_cell->setHTMLStyle('text-align: center;');

      if (!empty($this->fields["manufacturers_id"])) {
         $cell_value = Dropdown::getDropdownName("glpi_manufacturers",
                                                 $this->fields["manufacturers_id"]);
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['manu'], $cell_value, $previous_cell);
      $previous_cell->setHTMLStyle('text-align: center;');
   }

}
?>