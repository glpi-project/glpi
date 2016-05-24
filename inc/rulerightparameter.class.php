<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/// LDAP criteria class
class RuleRightParameter extends CommonDropdown {

   static $rightname         = 'rule_ldap';

   public $can_be_translated = false;


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      //LDAP parameters MUST be in lower case
      //because the are retieved in lower case  from the directory
      $input["value"] = Toolbox::strtolower($input["value"]);
      return $input;
   }


   function getAdditionalFields() {

      return array(array('name'  => 'value',
                         'label' => _n('Criterion', 'Criteria', 1),
                         'type'  => 'text',
                         'list'  => false));
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'value';
      $tab[11]['name']     = _n('Criterion', 'Criteria', 1);
      $tab[11]['datatype'] = 'string';

      return $tab;
   }


   static function getTypeName($nb=0) {
      return _n('LDAP criterion', 'LDAP criteria', $nb);
   }

}
