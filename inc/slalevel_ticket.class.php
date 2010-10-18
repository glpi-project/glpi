<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class SLA
class SlaLevel_Ticket extends CommonDBTM {


   /**
    * Retrieve an item from the database
    *
    *@param $ID ID of the item to get
    *@return true if succeed else false
    *
   **/
   function getFromDBForTicket ($ID) {
      global $DB;

      // Make new database object and fill variables
      if (empty($ID)) {
         return false;
      }

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `tickets_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $this->fields = $DB->fetch_assoc($result);
            return true;
         } else {
            return false;
         }
      } else {
         return false;
      }
   }


   /**
    * Give cron informations
    * @param $name : task's name
    *
    * @return arrray of informations
    *
    */
   static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case 'slaticket' :
            return array('description' => $LANG['crontask'][16]);
      }
      return array();
   }

   /**
    * Cron for ticket's automatic close
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
    *
    */
   static function cronSlaTicket($task) {
      global $DB;

      $tot = 0;

      $query="SELECT * FROM `glpi_slalevels_tickets` WHERE `date` < NOW()";
      foreach ($DB->request($query) as $data) {
         $tot++;
         $ticket=new Ticket();
         if ($ticket->getFromDB($data['tickets_id'])) {
            $slalevel=new SlaLevel();
            $sla=new SLA();
            // Check if sla datas are OK
            if ($ticket->fields['slas_id']>0
               && $ticket->fields['slalevels_id'] == $data['slalevels_id']) {
               $input=$ticket->fields;

               if ($slalevel->getRuleWithCriteriasAndActions($data['slalevels_id'],0,1)
                  && $sla->getFromDB($ticket->fields['slas_id'])) {
                  // Process rules
                  $input=$slalevel->executeActions($input,array());
               }
               // Put next level in todo list
               $next=$slalevel->getNextSlaLevel($ticket->fields['slas_id'],$ticket->fields['slalevels_id']);
               $input['slalevels_id']=$next;
               $ticket->update($input);
               $sla->addLevelToDo($ticket);
            }
         }
         // Drop line
         $slalevelticket=new SlaLevel_Ticket();
         $slalevelticket->delete(array('id'=>$data['id']));
      }

      $task->setVolume($tot);
      return ($tot > 0);
   }


}

?>