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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// NetworkPort_NetworkPort class
class NetworkPort_NetworkPort {

   /// ID of the NetworkPort_NetworkPort
   var $ID = 0;
   /// first connected port ID
   var $networkports_id_1 = 0;
   /// second connected port ID
   var $networkports_id_2 = 0;

   /**
    * Get port opposite port ID
    *
    *@param $ID networking port ID
    *
    *@return integer ID of opposite port. false if not found
    **/
   function getOppositeContact ($ID) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_networkports_networkports`
                WHERE `networkports_id_1` = '$ID'
                      OR `networkports_id_2` = '$ID'";
      if ($result=$DB->query($query)) {
         $data = $DB->fetch_array($result);
         if (is_array($data)) {
            $this->networkports_id_1 = $data["networkports_id_1"];
            $this->networkports_id_2 = $data["networkports_id_2"];
         }
         if ($this->networkports_id_1 == $ID) {
            return $this->networkports_id_2;
         } else if ($this->networkports_id_2 == $ID) {
            return $this->networkports_id_1;
         } else {
            return false;
         }
      }
   }

}

?>
