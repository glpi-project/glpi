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
    $migration->addField("glpi_tickets","takeintoaccountdate","timestamp",['null' => true,'after' => 'solvedate']);
    $migration->addKey("glpi_tickets", "takeintoaccountdate");
    $migration->migrationOneTable("glpi_tickets");

    $tickets_iterator = $DB->request([
        'SELECT'    => ['id'],
        'FROM'      => 'glpi_tickets',
        'WHERE'     => [
            'takeintoaccount_delay_stat' => ['>', 0],
        ]
    ]);

    $query = $DB->buildUpdate('glpi_tickets', 
	    [ 'takeintoaccountdate' => new QueryParam() ],
	    [ 'id' => new QueryParam() ]
    );
    $stmt = $DB->prepare($query);

    foreach ($tickets_iterator as $data) {
       $ticket = new Ticket();
       $ticket->getFromDB($data['id']);
       $tia_delay = $ticket->fields['takeintoaccount_delay_stat'];
       $ticket_date = $ticket->fields['date'];

       // Get calendar ID for the Ticket
       // Existing Ticket::computeTakeIntoAccountDelayStat() used Ticket::getCalendar() so migration has to use same function
       // Currently Ticket::getCalendar() uses SLA TTR calendar and if not defined Entity calendar, this is not correct
       // as it does not count that SLA TTO and OLA TTO count potentially use different calendars with different working hours
       // Also attaching new OLA/SLA TTO does not reset TIA
       $calendars_id = $ticket->getCalendar();
       $calendar = new Calendar();

       if (($calendars_id > 0) && $calendar->getFromDB($calendars_id) && $calendar->hasAWorkingDay()){
          // Compute takeintoaccountdate using Calendar, delay is added to active time of calendar
          // this is just an approximation as time during non-active hours was not counted to delay (should be fixed with introduction of new field)
          // Also if TIA happend before active hours of calendar it is 1 second as 0 has meaning "not taken into account yet"
          $tia_update = $calendar->computeEndDate($ticket_date,$tia_delay,0,false,false);
       } else { 
          // No calendar defined so assume 24/7 working hours
          $tia_update  = date('Y-m-d H:i:s', strtotime($ticket_date) + $tia_delay);
       }
       
       $stmt->bind_param('si', $tia_update, $data['id']);  
       $DB->executeStatement($stmt);
    }
}
