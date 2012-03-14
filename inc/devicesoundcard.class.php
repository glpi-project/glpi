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

/// Class DeviceSoundCard
class DeviceSoundCard extends CommonDevice {

   static function getTypeName($nb=0) {
      return _n('Soundcard', 'Soundcards', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'type',
                                     'label' => __('Type'),
                                     'type'  => 'text')));
   }


   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'type';
      $tab[12]['name']     = __('Type');
      $tab[12]['datatype'] = 'text';

      return $tab;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {

      $data['label'] = $data['value'] = array();

      if (!empty($this->fields["type"])) {
         $data['label'][] = __('Type');
         $data['value'][] = $this->fields["type"];
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

      $previous_header = $elements['type'] = $group->addHeader($super, 'type',
                                                               __('Type'), $previous_header);

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

      if ($this->fields["type"]) {
         $cell_value = $this->fields["type"];
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['type'], $cell_value, $previous_cell);

      if (!empty($this->fields["manufacturers_id"])) {
         $cell_value = Dropdown::getDropdownName("glpi_manufacturers",
                                                 $this->fields["manufacturers_id"]);
      } else {
         $cell_value = '';
      }
      $previous_cell = $row->addCell($headers['manu'], $cell_value, $previous_cell);
   }

}
?>