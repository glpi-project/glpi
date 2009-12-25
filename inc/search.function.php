<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * Generic Function to get transcript table name
 *
 *@param $table reference table
 *@param $devicetype device type ID
 *@param $meta_type meta table type ID
 *
 *@return Left join string
 *
 **/
function translate_table($table,$devicetype=0,$meta_type=0) {
   /// TODO will be deleted when clean management of devices
   $ADD="";
   if ($meta_type) {
      $ADD="_".$meta_type;
   }

   switch ($table) {
      case "glpi_computers_devices" :
         if (empty($devicetype)) {
            return "$table$ADD";
         }
         return "DEVICE_$devicetype$ADD";

      default :
         return "$table$ADD";
   }
}



?>
