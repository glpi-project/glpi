<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var DB $DB
 * @var Migration $migration
 */

if (!$DB->fieldExists("glpi_tickets", "takeintoaccountdate")) {
    $migration->addField("glpi_tickets","takeintoaccountdate","timestamp",['null' => true]);
    $migration->addKey("glpi_tickets", "takeintoaccountdate");

        //foreach ($DB->request('glpi_tickets', "`takeintoaccount_delay_stat` > 0") as $data)
        foreach (getAllDataFromTable('glpi_tickets', ['takeintoaccount_delay_stat' => ['>', 0]]) as $data)
        {
	   $ticket = new Ticket();
	   $ticket->getFromDB($data['id']);
	   $tia_delay = $ticket->getField('takeintoaccount_delay_stat');
	   $ticket_date = $ticket->getField('date');

	   // Get default calendar ID for ticket. Actually Ticket::getCalendar() uses SLA TTR calendar and if not defined Entity calendar, this is not 100% correct
	   // as it does not count with SLA TTO and OLA TTO, which potentially could use different calendar with different working hours
	   // Also attaching new OLA/SLA TTO does not reset TIA
	   $calendars_id = $ticket->getCalendar();

	   // There is no clear definition what should happen if both OLA and SLA are defined
	   // Lets assume OLA is overidding the SLA as it probably was attached after SLA
	   if(isset($ticket->fields['olas_id_tto']) && $this->fields['olas_id_tto'] > 0){
		   $la = new OLA();
		   $la->getFromDB($ticket->fields['olas_id_tto']);
	   } elseif (isset($ticket->fields['slas_id_tto']) && $this->fields['slas_id_tto'] > 0){
		   $la = new SLA();
		   $la->getFromDB($ticket->fields['slas_id_tto']);
	   }
	   if(isset($la) && !$la->getField('use_ticket_calendar')){
		   $calendards_id = $la->getFeild('calendars_id');
	   }

	   $calendar = new Calendar();
           if (($calendars_id > 0) && $calendar->getFromDB($calendars_id) && $calendar->hasAWorkingDay())
           {
	      // Compute takeintoaccountdate using Calendar, delay is added to active time of calendar
	      // this is just approximation as time during non-active hours was not counted
              // Also if TIA happend before active hours of calendar it is 1 second as 0 has meaning "not being taken into account"
              $tia_update = $calendar->computeEndDate($ticket_date,$tia_delay,0,false,false);
	   } else { 
              // No calendar defined so assume 24/7 working hours
              $tia_update  = date('Y-m-d H:i:s', $strtotime($ticket_date) + $tia_delay);
           }
	
           $query = "UPDATE `glpi_tickets` SET takeintoaccountdate = '" . $tia_update . "' WHERE id=" . $data['id'];
           $DB->queryOrDie($query, '10.0.3 initialize tickets takeintoaccountdate');
        }
}


