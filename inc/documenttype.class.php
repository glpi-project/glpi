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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// DocumentType class
class DocumentType  extends CommonDropdown {

   function getAdditionalFields() {

      return array(array('name'  => 'icon',
                         'label' => __('Icon'),
                         'type'  => 'icon'),
                   array('name'  => 'is_uploadable',
                         'label' => __('Authorized download'),
                         'type'  => 'bool'),
                   array('name'  => 'ext',
                         'label' => __('Extension'),
                         'type'  => 'text'),
                   array('name'  => 'mime',
                         'label' => __('MIME Type'),
                         'type'  => 'text'));
   }


   static function getTypeName($nb=0) {
      return _n('Document Type', 'Document Types', $nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'ext';
      $tab[3]['name']            = __('Extension');

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'icon';
      $tab[6]['name']            = __('Icon');
      $tab[6]['massiveaction']   = false;

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'mime';
      $tab[4]['name']            = __('MIME Type');

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'is_uploadable';
      $tab[5]['name']            = __('Download');
      $tab[5]['datatype']        = 'bool';

      return $tab;
   }


   function canCreate() {
      return Session::haveRight('typedoc', 'w');
   }


   function canView() {
      return Session::haveRight('typedoc', 'r');
   }

}
?>