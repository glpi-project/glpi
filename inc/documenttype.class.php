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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// DocumentType class
class DocumentType  extends CommonDropdown {

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'icon',
                         'label' => $LANG['document'][10],
                         'type'  => 'icon'),
                   array('name'  => 'is_uploadable',
                         'label' => $LANG['document'][11],
                         'type'  => 'bool'),
                   array('name'  => 'ext',
                         'label' => $LANG['document'][9],
                         'type'  => 'text'),
                   array('name'  => 'mime',
                         'label' => $LANG['document'][4],
                         'type'  => 'text'));
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['document'][7];
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'ext';
      $tab[3]['name']      = $LANG['document'][9];

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'icon';
      $tab[6]['name']          = $LANG['document'][10];
      $tab[6]['massiveaction'] = false;

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'mime';
      $tab[4]['name']      = $LANG['document'][4];

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'is_uploadable';
      $tab[5]['name']      = $LANG['document'][15];
      $tab[5]['datatype']      = 'bool';

      return $tab;
   }

   function canCreate() {
      return haveRight('typedoc','w');
   }

   function canView() {
      return haveRight('typedoc','r');
   }

}

?>
