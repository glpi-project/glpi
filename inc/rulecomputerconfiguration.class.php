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

class RuleComputerConfiguration extends Rule {
   static $rightname           = 'rule_compconf';
   public $orderby             = "name";
   public $specific_parameters = true;

   /**
    * @see Rule::maxActionsCount()
   **/
   function maxActionsCount() {
      return 1;
   }

   function getTitle() {
      return __('Automatic assignment for computer configurations');
   }


   /**
    * @see Rule::getCriterias()
   **/
   function getCriterias() {

      static $criterias = array();

      if (count($criterias)) {
         return $criterias;
      }

      $criterias['entities_id']['table']                        = 'glpi_entities';
      $criterias['entities_id']['field']                        = 'entities_id';
      $criterias['entities_id']['name']                         = __('Entity');
      $criterias['entities_id']['linkfield']                    = 'entities_id';
      $criterias['entities_id']['type']                         = 'dropdown';

      $criterias['locations_id']['table']                       = 'glpi_locations';
      $criterias['locations_id']['field']                       = 'name';
      $criterias['locations_id']['name']                        = __('Location');
      $criterias['locations_id']['linkfield']                   = 'locations_id';
      $criterias['locations_id']['type']                        = 'dropdown';

      $criterias['DOMAIN']['name']                              = __('Domain');

      $criterias['operatingsystems_id']['table']                = 'glpi_operatingsystems';
      $criterias['operatingsystems_id']['field']                = 'name';
      $criterias['operatingsystems_id']['name']                 = __('Operating system');
      $criterias['operatingsystems_id']['linkfield']            = 'operatingsystems_id';
      $criterias['operatingsystems_id']['type']                 = 'dropdown';

      $criterias['operatingsystemversions_id']['table']         = 'glpi_operatingsystemversions';
      $criterias['operatingsystemversions_id']['field']         = 'name';
      $criterias['operatingsystemversions_id']['name']          = __('Version of the operating system');
      $criterias['operatingsystemversions_id']['linkfield']     = 'operatingsystemversions_id';
      $criterias['operatingsystemversions_id']['type']          = 'dropdown';

      $criterias['operatingsystemservicepacks_id']['table']     = 'glpi_operatingsystemservicepacks';
      $criterias['operatingsystemservicepacks_id']['field']     = 'name';
      $criterias['operatingsystemservicepacks_id']['name']      = __('Service pack');
      $criterias['operatingsystemservicepacks_id']['linkfield'] = 'operatingsystemservicepacks_id';
      $criterias['operatingsystemservicepacks_id']['type']      = 'dropdown';

      $criterias['states_id']['table']                          = 'glpi_states';
      $criterias['states_id']['field']                          = 'name';
      $criterias['states_id']['name']                           = __('Status');
      $criterias['states_id']['linkfield']                      = 'state';
      $criterias['states_id']['type']                           = 'dropdown';

      $criterias['computertypes_id']['table']                   = 'glpi_computertypes';
      $criterias['computertypes_id']['field']                   = 'name';
      $criterias['computertypes_id']['name']                    = __('Type');
      $criterias['computertypes_id']['linkfield']               = 'computertypes_id';
      $criterias['computertypes_id']['type']                    = 'dropdown';

      $criterias['manufacturers_id']['table']                   = 'glpi_manufacturers';
      $criterias['manufacturers_id']['field']                   = 'name';
      $criterias['manufacturers_id']['name']                    = __('Manufacturer');
      $criterias['manufacturers_id']['linkfield']               = 'manufacturers_id';
      $criterias['manufacturers_id']['type']                    = 'dropdown';

      $criterias['computermodels_id']['table']                  = 'glpi_computermodels';
      $criterias['computermodels_id']['field']                  = 'name';
      $criterias['computermodels_id']['name']                   = __('Computer model');
      $criterias['computermodels_id']['linkfield']              = 'computermodels_id';
      $criterias['computermodels_id']['type']                   = 'dropdown';

      $criterias['IPSUBNET']['name']                            = __('Subnet');

      $criterias['MACADDRESS']['name']                          = __('MAC address');

      $criterias['IPADDRESS']['name']                           = __('IP address');

      $criterias['name']['name']                                = __("Computer's name");
      $criterias['name']['allow_condition']                     = array(Rule::PATTERN_IS, 
                                                                        Rule::PATTERN_IS_NOT,
                                                                        Rule::PATTERN_IS_EMPTY,
                                                                        Rule::PATTERN_FIND);

      $criterias['DESCRIPTION']['name']                         = __('Description');

      $criterias['serial']['name']                              = __('Serial number');


      return $criterias;
   }


   /**
    * @see Rule::getActions()
   **/
   function getActions() {
      $actions = array();
      
      $actions['_affect_configuration']['name']  = _n('Computer Configuration', 'Computer Configurations', 1);
      $actions['_affect_configuration']['table']  = ComputerConfiguration::getTable();
      $actions['_affect_configuration']['type']  = "dropdown";
      $actions['_affect_configuration']['force_actions'] = array('assign');

      return $actions;
   }


   /**
    * Execute the actions as defined in the rule
    *
    * @see Rule::executeActions()
    *
    * @param $output the result of the actions
    * @param $params the parameters
    *
    * @return the fields modified
   **/
   function executeActions($output, $params) {
      global $CFG_GLPI;

      $assigned_conf = '';

      if (count($this->actions)) {
         $entity = array();
         foreach ($this->actions as $action) {
            $assigned_conf = $action->fields["value"];
         } 
      }


      if (count($assigned_conf)) {
         $output["_affect_configuration"][] = $assigned_conf;
      } 

      return $output;
   }   
}

?>