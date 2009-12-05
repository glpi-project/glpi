<?php
/*
 * @version $Id: link.class.php 9261 2009-11-08 17:12:32Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Event class
class Event extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_events';
   public $type = 'Event';

   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][30];
   }

   function prepareInputForAdd($input) {
      global $CFG_GLPI;
      if (isset($input['level']) &&  $input['level'] <= $CFG_GLPI["event_loglevel"]) {
         return $input;
      }
      return false;
   }

   /**
    * Log an event.
    *
    * Log the event $event on the glpi_event table with all the others args, if
    * $level is above or equal to setting from configuration.
    *
    * @param $items_id
    * @param $type
    * @param $level
    * @param $service
    * @param $event
    **/
   static function log ($items_id, $type, $level, $service, $event) {
      global $DB,$CFG_GLPI, $LANG;

      $input = array('items_id' => intval($items_id),
                     'type'     => addslashes($type),
                     'date'     => $_SESSION["glpi_currenttime"],
                     'service'  => addslashes($service),
                     'level'    => intval($level),
                     'message'  => addslashes($event));
      $tmp = new self();
      return $tmp->add($input);
   }

   /**
    * Clean old event - Call by cron
    *
    * @param $day integer
    *
    * @return integer number of events deleted
    */
   static function cleanOld ($day) {
      global $DB;

      $secs = $day * DAY_TIMESTAMP;

      $query_exp = "DELETE
                    FROM `glpi_events`
                    WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";

      $DB->query($query_exp);
      return $DB->affected_rows();
   }
}

?>
