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
 * ComputerConfiguration_ComputerConfiguration class
**/
class ComputerConfiguration_ComputerConfiguration extends CommonDBRelation {
      // From CommonDBRelation
   static public $itemtype_1     = 'ComputerConfiguration';
   static public $items_id_1     = 'computerconfigurations_id_1';
   static public $itemtype_2     = 'ComputerConfiguration';
   static public $items_id_2     = 'computerconfigurations_id_2';

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      switch ($item->getType()) {
         case "ComputerConfiguration":
            $ong = array();
            $nb = count(ComputerConfiguration::getChildren($item->getID()));
            $ong[1] = self::createTabEntry(_n('Child configuration', 'Children Configurations', $nb), $nb);
            return $ong;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch ($item->getType()) {
         case "ComputerConfiguration" :
            switch ($tabnum) {
               case 1 :
                  $item->showChildsConfigurations();
                  return true;
            }
      }
      return false;
   }

}