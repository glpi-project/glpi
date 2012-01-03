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

/// Class DeviceMemory
class DeviceMemory extends CommonDevice {

   static function getTypeName($nb=0) {
      return _n('Memory', 'Memories', $nb);
   }


   static function getSpecifityLabel() {
      return array('specificity' => __('Size'));
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'specif_default',
                                     'label' => __('Size by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')),
                               array('name'  => 'frequence',
                                     'label' => __('Frequency'),
                                     'type'  => 'text',
                                     'unit'  => __('MHz')),
                               array('name'  => 'devicememorytypes_id',
                                     'label' => __('Type'),
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'specif_default';
      $tab[11]['name']     = __('Size by default');
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'frequence';
      $tab[12]['name']     = __('Frequency');
      $tab[12]['datatype'] = 'text';

      $tab[13]['table'] = 'glpi_devicememorytypes';
      $tab[13]['field'] = 'name';
      $tab[13]['name']  = __('Type');

      return $tab;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {

      $data['label'] = $data['value'] = array();

      if ($this->fields["devicememorytypes_id"]) {
         $data['label'][] = __('Type');
         $data['value'][] = Dropdown::getDropdownName("glpi_devicememorytypes",
                                                      $this->fields["devicememorytypes_id"]);
      }

      if (!empty($this->fields["frequence"])) {
         $data['label'][] = __('Frequency');
         $data['value'][] = $this->fields["frequence"];
      }

      // Specificity
      $data['label'][] = __('Size');
      $data['size']    = 10;

      return $data;
   }

}
?>