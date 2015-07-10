<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

/**
 * ProjectState Class
 *
 * @since version 0.85
**/
class ProjectState extends CommonDropdown {


   static function getTypeName($nb=0) {
      return _n('Project state', 'Project states', $nb);
   }


   function post_getEmpty() {
      $this->fields['color'] = '#dddddd';
   }


   function getAdditionalFields() {

      return array(array('name'     => 'color',
                         'label'    => __('Color'),
                         'type'     => 'color',
                         'list'     => true),
                   array('name'     => 'is_finished',
                         'label'    => __('Finished state'),
                         'type'     => 'bool',
                         'list'     => true),);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'color';
      $tab[11]['name']     = __('Color');
      $tab[11]['datatype'] = 'color';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'is_finished';
      $tab[12]['name']     = __('Finished state');
      $tab[12]['datatype'] = 'bool';

      return $tab;
   }

}
?>
