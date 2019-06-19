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

use Glpi\Event\ScheduledDowntimeEvent;
use Glpi\EventDispatcher\EventDispatcher;

/**
 * ScheduledDowntime class.
 * This represents a period of time when a host or service will be down and all alerts during that time can be ignored.
 *
 * @since 10.0.0
 */
class ScheduledDowntime extends CommonDBTM {

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb = 0) {
      return _n('Scheduled Downtime', 'Scheduled Downtimes', $nb);
   }

   public static function getForHostOrService(int $items_id, bool $is_service = true, array $params = []) : DBmysqlIterator {
      global $DB;

      $p = [
         'start'     => $_SESSION['glpi_currenttime'],
         'end'       => $_SESSION['glpi_currenttime']
      ];
      $p = array_replace($p, $params);

      $downtimetable = self::getTable();
      $monitoredtable = $is_service ? SIEMService::getTable() : SIEMHost::getTable();

      $where = [
         "$downtimetable.items_id_target" => $items_id,
         "$downtimetable.is_service"      => $is_service
      ];

      if (!is_null($p['start'])) {
         $where[] = new QueryExpression("'{$p['start']}' >= begin_date");
      }
      if (!is_null($p['end'])) {
         $where[] = new QueryExpression("'{$p['end']}' <= end_date");
      }

      $iterator = $DB->request([
         'FROM'   => $downtimetable,
         'LEFT JOIN' => [
            $monitoredtable => [
               'FKEY' => [
                  $monitoredtable   => 'id',
                  $downtimetable    => 'items_id_target'
               ]
            ]
         ],
         'WHERE'  => $where
      ]);

      return $iterator;
   }

   public function prepareInputForUpdate($input): array {
      if (isset($input['_cancel'])) {
         $input['end_date'] = $_SESSION['glpi_currenttime'];
      }
   }

   public function post_updateItem($history = 1) {
      if (isset($this->input['_cancel'])) {
         $this->dispatchScheduledDowntimeEvent('scheduleddowntime.cancel');
      }
   }

   private function dispatchScheduledDowntimeEvent(string $eventName) {
      global $CONTAINER;

      if (!isset($CONTAINER) || !$CONTAINER->has(EventDispatcher::class)) {
         return;
      }

      $dispatcher = $CONTAINER->get(EventDispatcher::class);
      $dispatcher->dispatch($eventName, new ScheduledDowntimeEvent($this));
   }
}