<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

/* Test for inc/planning.class.php */

class Planning extends \DbTestCase {

   public function testpostClonedEvent() {
      $this->login();

      $input = [
         'name'  => "test event to clone",
         'plan'  => [
            'begin'     => date('Y-m-d H:i:s'),
            '_duration' => 2 * HOUR_TIMESTAMP
         ],
         'rrule' => '{"freq":"weekly","interval":"1"}'
      ];

      $event = new \PlanningExternalEvent;
      $this->integer($event_id = $event->add($input))->isGreaterThan(0);

      $timestamp = time()+DAY_TIMESTAMP;
      $new_start = date('Y-m-d H:i:s', $timestamp);
      $new_end   = date('Y-m-d H:i:s', $timestamp + 2 * HOUR_TIMESTAMP);

      $this->integer($clone_events_id = \Planning::postClonedEvent([
         'old_itemtype' => 'PlanningExternalEvent',
         'old_items_id' => $event_id,
         'start'        => $new_start,
         'end'          => $new_end
      ]))->isGreaterThan(0);

      // check cloned event
      $this->boolean($event->getFromDB($clone_events_id))->isTrue();
      $this->array($event->fields)
         ->string['begin']->isEqualTo($new_start)
         ->string['end']->isEqualTo($new_end)
         ->string['rrule']->isEqualTo($input['rrule']);
      $this->string($event->fields['name'])->contains(sprintf(__('Copy of %s'), $input['name']));
   }
}
