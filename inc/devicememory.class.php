<?php
/*
 * @version $Id: document.class.php 9112 2009-10-13 20:17:16Z moyo $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Class DeviceMemory
class DeviceMemory extends Device {

   // From CommonDBTM
   public $table = 'glpi_devicememories';
   public $type = 'DeviceMemory';

   static function getTypeName() {
      global $LANG;

      return $LANG['devices'][6];
   }

   function getAdditionalFields() {
      global $LANG;


      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'specif_default',
                                     'label' => $LANG['device_ram'][2]." ".$LANG['devices'][24],
                                     'type'  => 'text'),
                               array('name'  => 'frequence',
                                     'label' => $LANG['device_ram'][1],
                                     'type'  => 'text'),
                               array('name'  => 'devicememorytypes_id',
                                     'label' => $LANG['common'][17],
                                     'type'  => 'dropdownValue')));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']         = $this->table;
      $tab[11]['field']         = 'specif_default';
      $tab[11]['linkfield']     = 'specif_default';
      $tab[11]['name']          = $LANG['device_ram'][2]." ".$LANG['devices'][24];
      $tab[11]['datatype']      = 'text';

      $tab[12]['table']         = $this->table;
      $tab[12]['field']         = 'frequence';
      $tab[12]['linkfield']     = 'frequence';
      $tab[12]['name']          = $LANG['device_ram'][1];
      $tab[12]['datatype']      = 'text';

      $tab[13]['table']         = 'glpi_devicememorytypes';
      $tab[13]['field']         = 'name';
      $tab[13]['linkfield']     = 'devicememorytypes_id';
      $tab[13]['name']          = $LANG['common'][17];

      return $tab;
   }
}

?>