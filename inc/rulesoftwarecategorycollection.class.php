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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class RuleSoftwareCategoryCollection extends RuleCollection {

   // From RuleCollection
   public $stop_on_first_match = true;
   public $right               = 'rule_softwarecategories';
   public $menu_option         = 'softwarecategories';


   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][37];
   }


   /**
    * Get the attributes needed for processing the rules
    *
    * @param $input input data
    * @param $software software data array
    *
    * @return an array of attributes
   **/
   function prepareInputDataForProcess($input,$software) {

      $params["name"] = $software["name"];
      if (isset($software["comment"])) {
         $params["comment"] = $software["comment"];
      }

      if (isset($software["manufacturers_id"])) {
         $params["manufacturer"] = Dropdown::getDropdownName("glpi_manufacturers",
                                                             $software["manufacturers_id"]);
      }
      return $params;
   }

}

?>
