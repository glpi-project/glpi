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
 * @since version 0.85 (before migration_cleaner)
**/
class MigrationCleaner extends CommonGLPI {

   static $rightname = 'networking';


   static function getTypeName($nb=0) {
      return __('Migration cleaner');
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
   **/
   static function getAdditionalMenuOptions() {

      if (static::canView()) {
         $options['networkportmigration']['title']  = NetworkPortMigration::getTypeName(Session::getPluralNumber());
         $options['networkportmigration']['page']   = NetworkPortMigration::getSearchURL(false);
         $options['networkportmigration']['search'] = NetworkPortMigration::getSearchURL(false);

         return $options;
      }
      return false;
   }


   static function canView() {

      if (!isset($_SESSION['glpishowmigrationcleaner'])) {

         if (TableExists('glpi_networkportmigrations')
             && (countElementsInTable('glpi_networkportmigrations') > 0)) {
            $_SESSION['glpishowmigrationcleaner'] = true;
         } else {
            $_SESSION['glpishowmigrationcleaner'] = false;
         }
      }

      if ($_SESSION['glpishowmigrationcleaner']
          && (Session::haveRight("networking", UPDATE)
              || Session::haveRight("internet", UPDATE))) {
         return true;
      }

      return false;
   }

}
?>