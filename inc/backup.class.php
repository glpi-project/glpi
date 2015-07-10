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
 * Backup class
 *
 * @since version 0.85
**/


class Backup extends CommonGLPI {

   static $rightname = 'backup';

   const CHECKUPDATE = 1024;



   /**
    * @since version 0.85.3
    **/
   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }


   static function getTypeName($nb=0) {
      return __('Maintenance');
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface='central') {

      $values = array(READ                => __('Read'),
                      CREATE              => __('Create'),
                      PURGE               => _x('button', 'Delete permanently'),
                      self::CHECKUPDATE   => __('Check for upgrade'));
      return $values;
   }

}
?>