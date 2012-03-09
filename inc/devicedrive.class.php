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

/// Class DeviceDrive
class DeviceDrive extends CommonDevice {

   static function getTypeName($nb=0) {
      return _n('Drive', 'Drives', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'is_writer',
                                     'label' => __('Writing ability'),
                                     'type'  => 'bool'),
                               array('name'  => 'speed',
                                     'label' => __('Speed'),
                                     'type'  => 'text'),
                               array('name'  => 'interfacetypes_id',
                                     'label' => __('Interface'),
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'is_writer';
      $tab[12]['name']     = __('Writing ability');
      $tab[12]['datatype'] = 'bool';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'speed';
      $tab[13]['name']     = __('Speed');
      $tab[13]['datatype'] = 'text';

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

      if ($this->fields["is_writer"]) {
         $data['label'][] = __('Writing ability');
         $data['value'][] = Dropdown::getYesNo($this->fields["is_writer"]);
      }

      if (!empty($this->fields["speed"])) {
         $data['label'][] = __('Speed');
         $data['value'][] = $this->fields["speed"];
      }

      if ($this->fields["interfacetypes_id"]) {
         $data['label'][] = __('Interface');
         $data['value'][] = Dropdown::getDropdownName("glpi_interfacetypes",
                                                      $this->fields["interfacetypes_id"]);
      }

      return $data;
   }


   /**
    * @since version 0.84
    *
    * @param $group              HTMLTable_Group object
    * @param $super              HTMLTable_SuperHeader object
    * @param &$previous_header   HTMLTable_Header object
   **/
   static function getHTMLTableHeaderForComputer_Device(HTMLTable_Group $group,
                                                        HTMLTable_SuperHeader $super,
                                                        HTMLTable_Header &$previous_header) {

      $elements        = array();

      $previous_header = $elements['writer'] = $group->addHeader($super, 'writer',
                                                                 __('Writing ability'),
                                                                 $previous_header);

      $previous_header = $elements['speed'] = $group->addHeader($super, 'speed', __('Speed'),
                                                                $previous_header);

      $previous_header = $elements['inter'] = $group->addHeader($super, 'interface',
                                                                __('Interface'), $previous_header);

      $previous_header = $elements['manu'] = $group->addHeader($super, 'manufacturer',
                                                               __('Manufacturer'),
                                                               $previous_header);

      return $elements;
   }


   /**
    * @since version 0.84
    *
    * @see inc/CommonDevice::getHTMLTableCellsForComputer_Device()
   **/
   function getHTMLTableCellsForComputer_Device(HTMLTable_Row $row, $headers,
                                                HTMLTable_Cell &$previous_cell) {

      if ($this->fields["is_writer"]) {
         $cell_value = Dropdown::getYesNo($this->fields["is_writer"]);
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['writer'], $cell_value, $previous_cell);
      $previous_cell->setHTMLClass('center');

      if ($this->fields["speed"]) {
         $cell_value = $this->fields["speed"];
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['speed'], $cell_value, $previous_cell);
      $previous_cell->setHTMLClass('center');

      if ($this->fields["interfacetypes_id"]) {
         $cell_value = Dropdown::getDropdownName("glpi_interfacetypes",
                                                 $this->fields["interfacetypes_id"]);
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['inter'], $cell_value, $previous_cell);
      $previous_cell->setHTMLClass('center');

      if (!empty($this->fields["manufacturers_id"])) {
         $cell_value = Dropdown::getDropdownName("glpi_manufacturers",
                                                 $this->fields["manufacturers_id"]);
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['manu'], $cell_value, $previous_cell);
      $previous_cell->setHTMLClass('center');
   }

}
?>