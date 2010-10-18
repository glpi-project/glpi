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
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class DeviceHardDrive
class DeviceHardDrive extends CommonDevice {

   static function getTypeName() {
      global $LANG;

      return $LANG['devices'][1];
   }


   static function getSpecifityLabel() {
      global $LANG;

      return array('specificity' => $LANG['device_hdd'][4]);
   }


   function getAdditionalFields() {
      global $LANG;

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'specif_default',
                                     'label' => $LANG['device_hdd'][4]." ".$LANG['devices'][24],
                                     'type'  => 'text'),
                               array('name'  => 'rpm',
                                     'label' => $LANG['device_hdd'][0],
                                     'type'  => 'text'),
                               array('name'  => 'cache',
                                     'label' => $LANG['device_hdd'][1],
                                     'type'  => 'text'),
                               array('name'  => 'interfacetypes_id',
                                     'label' => $LANG['common'][65],
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'specif_default';
      $tab[11]['name']     = $LANG['device_hdd'][4]." ".$LANG['devices'][24];
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'rpm';
      $tab[12]['name']     = $LANG['device_hdd'][0];
      $tab[12]['datatype'] = 'text';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'cache';
      $tab[13]['name']     = $LANG['device_hdd'][1];
      $tab[13]['datatype'] = 'text';

      $tab[14]['table']    = 'glpi_interfacetypes';
      $tab[14]['field']    = 'name';
      $tab[14]['name']     = $LANG['common'][65];

      return $tab;
   }


   /**
    * return the display data for a specific device
    *
    * @return array
   **/
   function getFormData() {
      global $LANG;

      $data['label'] = $data['value'] = array();

      if (!empty($this->fields["rpm"])) {
         $data['label'][] = $LANG['device_hdd'][0];
         $data['value'][] = $this->fields["rpm"];
      }

      if ($this->fields["interfacetypes_id"]) {
         $data['label'][] = $LANG['common'][65];
         $data['value'][] = Dropdown::getDropdownName("glpi_interfacetypes",
                                                      $this->fields["interfacetypes_id"]);
      }

      if (!empty($this->fields["cache"])) {
         $data['label'][] = $LANG['device_hdd'][1];
         $data['value'][] = $this->fields["cache"];
      }
      // Specificity
      $data['label'][] = $LANG['device_hdd'][4];
      $data['size']    = 10;

      return $data;
   }

}

?>