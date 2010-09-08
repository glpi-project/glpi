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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Class SLA
class SLA extends CommonDBTM {

   // From CommonDBTM
   var $dohistory=true;


   static function getTypeName() {
      global $LANG;

      return $LANG['sla'][1];
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
      return haveRight('config', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['title'][26];
      return $ong;
   }

   function post_getEmpty () {
      $this->fields['resolution_time']=DAY_TIMESTAMP;
   }


   /**
    * Print the sla form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
    **/
   function showForm ($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      // Show device or blank form

      if (!haveRight("config","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name", array('value' => $this->fields["name"]));


      echo "<td rowspan='3'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='3'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";

      echo "</td></tr>";

      if ($ID>0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][26]."&nbsp;: </td>";
         echo "<td>";
         echo ($this->fields["date_mod"] ? convDateTime($this->fields["date_mod"]) : $LANG['setup'][307]);
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".$LANG['buttons'][15]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('Calendar', array('value' => $this->fields["calendars_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['sla'][2]."&nbsp;:</td>";
      echo "<td>";
      $possible_values=array();
      for ($i=1 ; $i<24 ; $i++) {
         $possible_values[$i*HOUR_TIMESTAMP]=$i." ".$LANG['job'][21];
      }
      for ($i=1 ; $i<30 ; $i++) {
         $possible_values[$i*DAY_TIMESTAMP]=$i." ".$LANG['stats'][31];
      }
      Dropdown::showFromArray('resolution_time',$possible_values,array('value'=>$this->fields["resolution_time"]));
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[4]['table']     = 'glpi_calendars';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'calendars_id';
      $tab[4]['name']      = $LANG['buttons'][15];

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }

   /**
   * Get due date based on a sla
   *
   * @param $start_date datetime start date
   * @param $additional_delay integer additional delay to add or substract (for waiting time)
   *
   * @return due date time (NULL if sla not exists)
   **/
   function computeDueDate($start_date,$additional_delay=0) {
      if (isset($this->fields['id'])) {
         // Based on a calendar
         if ($this->fields['calendars_id']>0) {
            $cal=new Calendar();
            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->computeEndDate($start_date,$this->fields['resolution_time']+$additional_delay);
            }
         }
         // No calendar defined or invalide calendar
         $starttime=strtotime($start_date);
         $endtime=$starttime+$this->fields['resolution_time'];
         return date('Y-m-d H:i:s',$endtime);
      }
      return NULL;
   }

   /**
   * Get execution date of a sla level
   *
   * @param $start_date datetime start date
   * @param $slalevels_id integer sla level id
   * @param $additional_delay integer additional delay to add or substract (for waiting time)
   *
   * @return execution date time (NULL if sla not exists)
   **/
   function computeExecutionDate($start_date,$slalevels_id,$additional_delay=0) {
      if (isset($this->fields['id'])) {
         $slalevel=new SlaLevel();
         if ($slalevel->getFromDB($slalevels_id)) { // sla level exists
            if ($slalevel->fields['slas_id']==$this->fields['id']) { // correct sla level
               $force_work_in_days=($this->fields['resolution_time']>=DAY_TIMESTAMP);
               $delay=$this->fields['resolution_time']+$slalevel->fields['execution_time']+$additional_delay;
               // Based on a calendar
               if ($this->fields['calendars_id']>0) {
                  $cal=new Calendar();
                  if ($cal->getFromDB($this->fields['calendars_id'])) {
                     return $cal->computeEndDate($start_date,$delay,$force_work_in_days);
                  }
               }
               // No calendar defined or invalide calendar
               $starttime=strtotime($start_date);
               $endtime=$starttime+$delay;
               return date('Y-m-d H:i:s',$endtime);
            }
         }
      }
      return NULL;
   }

   /**
    * Get active time between to date time for the active calendar
    *
    * @param $start datetime begin
    * @param $end datetime end
    *
    * @return timestamp of delay
    */
   function getActiveTimeBetween($start,$end) {
      if ($end<$start) {
         return 0;
      }

      if (isset($this->fields['id'])) {
         $slalevel=new SlaLevel();
         $force_work_in_days=($this->fields['resolution_time']>=DAY_TIMESTAMP);

         $cal=new Calendar();

         // Based on a calendar
         if ($this->fields['calendars_id']>0) {
            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->getActiveTimeBetween($start,$end,$force_work_in_days);
            }
         } else { // No calendar
            $timestart=strtotime($start);
            $timeend=strtotime($end);
            return ($timeend-$timestart);
         }
      }
      return 0;
   }

   /**
   * Add a level to do for a ticket
   *
   * @param $ticket Ticket object
   *
   * @return execution date time (NULL if sla not exists)
   **/
   function addLevelToDo(Ticket $ticket) {

      if ($ticket->fields["slalevels_id"]>0) {
         $toadd=array();
         $toadd['date']=$this->computeExecutionDate($ticket->fields['date'],
                                                   $ticket->fields["slalevels_id"],
                                                   $ticket->fields['sla_waiting_duration']);
         $toadd['slalevels_id']=$ticket->fields["slalevels_id"];
         $toadd['tickets_id']=$ticket->fields["id"];
         $slalevelticket=new SlaLevel_Ticket();
         $slalevelticket->add($toadd);
      }
   }

   /**
   * Add a level to do for a ticket
   *
   * @param $ticket Ticket object
   *
   * @return execution date time (NULL if sla not exists)
   **/
   function deleteLevelsToDo(Ticket $ticket) {
      global $DB;
      if ($ticket->fields["slalevels_id"]>0) {
         $query="SELECT *
                  FROM `glpi_slalevels_tickets`
                  WHERE `tickets_id` = '".$ticket->fields["id"]."'";
         $slalevelticket=new SlaLevel_Ticket();
         foreach ($DB->request($query) as $data) {
            $slalevelticket->delete(array('id'=>$data['id']));
         }
      }
   }

}

?>
