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

class SingletonRuleList {
   /// Items list
   public $list = array();
   /// Items loaded ?
   public $load = 0;


   /**
    * get a unique instance of a SingletonRuleList for a type of RuleCollection
    *
    * @param $type   type of the Rule listed
    * @param $entity entity where the rule Rule is processed
    *
    * @return unique instance of an object
   **/
   public static function &getInstance($type, $entity) {
      static $instances = array();

      if (!isset($instances[$type][$entity])) {
         $instances[$type][$entity] = new self();
      }
      return $instances[$type][$entity];
   }

}
