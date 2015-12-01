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
 * SLA Class
**/
class SLA extends CommonDBTM {

   // From CommonDBTM
   var $dohistory                      = true;

   static $rightname                   = 'sla';

   static protected $forward_entity_to = array('SLALevel');


   static function getTypeName($nb=0) {
      // Acronymous, no plural
      return __('SLA');
   }


   /**
    * Define calendar of the ticket using the SLA when using this calendar as sla-s calendar
    *
    * @param $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1 ) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('SlaLevel', $ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);

      return $ong;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::post_getEmpty()
   */
   function post_getEmpty() {

      $this->fields['resolution_time'] = 4;
      $this->fields['definition_time'] = 'hour';
   }


   function cleanDBonPurge() {
      global $DB;

      // Clean sla_levels
      $query = "SELECT `id`
                FROM `glpi_slalevels`
                WHERE `slas_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $slalevel = new SlaLevel();
            while ($data = $DB->fetch_assoc($result)) {
               $slalevel->delete($data);
            }
         }
      }

      // Update tickets : clean SLA
      $query = "SELECT `id`
                FROM `glpi_tickets`
                WHERE `slas_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $ticket = new Ticket();
            while ($data = $DB->fetch_assoc($result)) {
               $ticket->deleteSLA($data['id']);
            }
         }
      }

      Rule::cleanForItemAction($this);
   }


   /**
    * Print the sla form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
   **/
   function showForm($ID, $options=array()) {

      $rowspan = 4;
      if ($ID > 0) {
         $rowspan = 5;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", array('value' => $this->fields["name"]));
      echo "<td rowspan='".$rowspan."'>".__('Comments')."</td>";
      echo "<td rowspan='".$rowspan."'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>".($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".__('Calendar')."</td>";
      echo "<td>";

      Calendar::dropdown(array('value'      => $this->fields["calendars_id"],
                               'emptylabel' => __('24/7'),
                               'toadd'      => array('-1' => __('Calendar of the ticket'))));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Maximum time to solve')."</td>";
      echo "<td>";
      Dropdown::showNumber("resolution_time", array('value' => $this->fields["resolution_time"],
                                                    'min'   => 0));
      $possible_values = array('minute'   => _n('Minute', 'Minutes', Session::getPluralNumber()),
                               'hour'     => _n('Hour', 'Hours', Session::getPluralNumber()),
                               'day'      => _n('Day', 'Days', Session::getPluralNumber()));
      $rand = Dropdown::showFromArray('definition_time', $possible_values,
                                      array('value'     => $this->fields["definition_time"],
                                            'on_change' => 'appearhideendofworking()'));
      echo "\n<script type='text/javascript' >\n";
      echo "function appearhideendofworking() {\n";
      echo "if ($('#dropdown_definition_time$rand option:selected').val() == 'day') {
         $('#title_endworkingday').show();
         $('#dropdown_endworkingday').show();
      } else {
         $('#title_endworkingday').hide();
         $('#dropdown_endworkingday').hide();
      }";
      echo "}\n";
      echo "appearhideendofworking();\n";
      echo "</script>\n";

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td><div id='title_endworkingday'>".__('End of working day')."</div></td>";
      echo "<td><div id='dropdown_endworkingday'>";
      Dropdown::showYesNo("end_of_working_day", $this->fields["end_of_working_day"]);
      echo "</div></td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   function getSearchOptions() {

      $tab                        = array();
      $tab['common']              = __('Characteristics');

      $tab[1]['table']            = $this->getTable();
      $tab[1]['field']            = 'name';
      $tab[1]['name']             = __('Name');
      $tab[1]['datatype']         = 'itemlink';
      $tab[1]['massiveaction']    = false;

      $tab[2]['table']            = $this->getTable();
      $tab[2]['field']            = 'id';
      $tab[2]['name']             = __('ID');
      $tab[2]['massiveaction']    = false;
      $tab[2]['datatype']         = 'number';

      $tab[4]['table']            = 'glpi_calendars';
      $tab[4]['field']            = 'name';
      $tab[4]['name']             = __('Calendar');
      $tab[4]['datatype']         = 'dropdown';

      $tab[5]['table']            = $this->getTable();
      $tab[5]['field']            = 'resolution_time';
      $tab[5]['name']             = __('Resolution time');
      $tab[5]['datatype']         = 'specific';
      $tab[5]['massiveaction']    = false;
      $tab[5]['nosearch']         = true;
      $tab[5]['additionalfields'] = array('definition_time');

      $tab[6]['table']            = $this->getTable();
      $tab[6]['field']            = 'end_of_working_day';
      $tab[6]['name']             = __('End of working day');
      $tab[6]['datatype']         = 'bool';
      $tab[6]['massiveaction']    = false;

      $tab[16]['table']           = $this->getTable();
      $tab[16]['field']           = 'comment';
      $tab[16]['name']            = __('Comments');
      $tab[16]['datatype']        = 'text';

      $tab[80]['table']           = 'glpi_entities';
      $tab[80]['field']           = 'completename';
      $tab[80]['name']            = __('Entity');
      $tab[80]['massiveaction']   = false;
      $tab[80]['datatype']        = 'dropdown';

      $tab[86]['table']           = $this->getTable();
      $tab[86]['field']           = 'is_recursive';
      $tab[86]['name']            = __('Child entities');
      $tab[86]['datatype']        = 'bool';

      return $tab;
   }


   /**
    * @since version 0.85
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'resolution_time' :
            switch ($values['definition_time']) {
               case 'minute' :
                  return sprintf(_n('%d minute', '%d minutes', $values[$field]), $values[$field]);

               case 'hour' :
                  return sprintf(_n('%d hour', '%d hours', $values[$field]), $values[$field]);

               case 'day' :
                  return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * Get due date based on a sla
    *
    * @param $start_date         datetime start date
    * @param $additional_delay   integer  additional delay to add or substract (for waiting time)
    *                                     (default 0)
    *
    * @return due date time (NULL if sla not exists)
   **/
   function computeDueDate($start_date, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         $delay = $this->getResolutionTime();
         // Based on a calendar
         if ($this->fields['calendars_id'] > 0) {
            $cal          = new Calendar();
            $work_in_days = ($this->fields['definition_time'] == 'day');

            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->computeEndDate($start_date, $delay,
                                           $additional_delay, $work_in_days,
                                           $this->fields['end_of_working_day']);
            }
         }

         // No calendar defined or invalid calendar
         if ($this->fields['resolution_time'] >= 0) {
            $starttime = strtotime($start_date);
            $endtime   = $starttime+$delay+$additional_delay;
            return date('Y-m-d H:i:s',$endtime);
         }
      }

      return NULL;
   }


   /**
    * Get computed resolution time
    *
    * @since version 0.85
    *
    * @return resolution time
   **/
   function getResolutionTime() {

      if (isset($this->fields['id'])) {
         if ($this->fields['definition_time'] == "minute") {
            return $this->fields['resolution_time'] * MINUTE_TIMESTAMP;
         }
         if ($this->fields['definition_time'] == "hour") {
            return $this->fields['resolution_time'] * HOUR_TIMESTAMP;
         }
         if ($this->fields['definition_time'] == "day") {
            return $this->fields['resolution_time'] * DAY_TIMESTAMP;
         }
      }
      return 0;
   }


   /**
    * Get execution date of a sla level
    *
    * @param $start_date         datetime    start date
    * @param $slalevels_id       integer     sla level id
    * @param $additional_delay   integer     additional delay to add or substract (for waiting time)
    *                                        (default 0)
    *
    * @return execution date time (NULL if sla not exists)
   **/
   function computeExecutionDate($start_date, $slalevels_id, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         $slalevel = new SlaLevel();

         if ($slalevel->getFromDB($slalevels_id)) { // sla level exists
            if ($slalevel->fields['slas_id'] == $this->fields['id']) { // correct sla level
               $work_in_days = ($this->fields['definition_time'] == 'day');
               $delay        = $this->getResolutionTime();
               // Based on a calendar
               if ($this->fields['calendars_id'] > 0) {
                  $cal = new Calendar();
                  if ($cal->getFromDB($this->fields['calendars_id'])) {
                     return $cal->computeEndDate($start_date, $delay,
                                                 $slalevel->fields['execution_time'] + $additional_delay,
                                                 $work_in_days);
                  }
               }
                // No calendar defined or invalid calendar
                $delay    += $additional_delay+$slalevel->fields['execution_time'];
                $starttime = strtotime($start_date);
                $endtime   = $starttime+$delay;
                return date('Y-m-d H:i:s',$endtime);

            }
         }
      }
      return NULL;
   }


   /**
    * Get active time between to date time for the active calendar
    *
    * @param $start  datetime begin
    * @param $end    datetime end
    *
    * @return timestamp of delay
   **/
   function getActiveTimeBetween($start, $end) {

      if ($end < $start) {
         return 0;
      }

      if (isset($this->fields['id'])) {
         $cal          = new Calendar();
         $work_in_days = ($this->fields['definition_time'] == 'day');

         // Based on a calendar
         if ($this->fields['calendars_id'] > 0) {
            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->getActiveTimeBetween($start, $end, $work_in_days);
            }

         } else { // No calendar
            $timestart = strtotime($start);
            $timeend   = strtotime($end);
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
         $toadd                 = array();
         $toadd['date']         = $this->computeExecutionDate($ticket->fields['date'],
                                                              $ticket->fields['slalevels_id'],
                                                              $ticket->fields['sla_waiting_duration']);
         $toadd['slalevels_id'] = $ticket->fields["slalevels_id"];
         $toadd['tickets_id']   = $ticket->fields["id"];
         $slalevelticket        = new SlaLevel_Ticket();
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
   static function deleteLevelsToDo(Ticket $ticket) {
      global $DB;

      if ($ticket->fields["slalevels_id"] > 0) {
         $query = "SELECT *
                   FROM `glpi_slalevels_tickets`
                   WHERE `tickets_id` = '".$ticket->fields["id"]."'";

         $slalevelticket = new SlaLevel_Ticket();
         foreach ($DB->request($query) as $data) {
            $slalevelticket->delete(array('id' => $data['id']));
         }
      }
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if ($input['definition_time'] != 'day') {
         $input['end_of_working_day'] = 0;
      }
      return $input;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      if ($input['definition_time'] != 'day') {
         $input['end_of_working_day'] = 0;
      }
      return $input;
   }

}
?>
