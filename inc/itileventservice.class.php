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

use Glpi\Event\ITILEventServiceEvent;
use Glpi\Event\ITILEventHostEvent;
use Glpi\EventDispatcher\EventDispatcher;

/**
 * ITILEventService class.
 * This represents a software, service, or metric on a host device that is able to be monitored.
 *
 * @since 10.0.0
 */
class ITILEventService extends CommonDBTM {
   use Monitored;

   /** Service is functioning as expected. */
   const STATUS_OK         = 0;

   /** Service is not functioning properly.
    * Valid for non-volatile services only. */
   const STATUS_CRITICAL       = 1;

   /** Service is not being monitored */
   const STATUS_UNKNOWN    = 2;

   /** Service is functional but functionality or performance is limited.
    * Valid for non-volatile services only.*/
   const STATUS_DEGRADED   = 3;

   /** This service is actively polled. */
   const CHECK_MODE_ACTIVE    = 0;

   /** This service sends events to GLPI as needed. */
   const CHECK_MODE_PASSIVE   = 1;

   /** This service can operate in both active and passive modes. */
   const CHECK_MODE_HYBRID    = 2;

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb = 0) {
      return _n('Service', 'Services', $nb);
   }

   public function post_getFromDB() {
      // Merge fields with the template
      $template = new ITILEventServiceTemplate();
      $template->getFromDB($this->fields['itileventservicetemplates_id']);
      foreach($template->fields as $field => $value) {
         if ($field !== 'id') {
            $this->fields[$field] = $value;
         }
      }
   }

   public function isScheduledDown() : bool {
      static $is_down = null;
      if ($is_down == null) {
         $iterator = ScheduledDowntime::getForHostOrService($this->getID(), true);
         $is_down = $iterator->count() > 0;
      }
      return $is_down;
   }

   public function isHostless() : bool {
      return $this->fields['hosts_id'] < 0;
   }

   public static function getStatusName($status) {
      switch ($status) {
         case self::STATUS_OK: return __('OK');
         case self::STATUS_CRITICAL: return __('Critical');
         case self::STATUS_DEGRADED: return __('Degraded');
         case self::STATUS_UNKNOWN:
         default:
            return __('Unknown');
      }
   }

   public static function getCheckModeName($check_mode) : string {
      switch ($check_mode) {
         case self::CHECK_MODE_ACTIVE: return __('Active');
         case self::CHECK_MODE_PASSIVE: return __('Passive');
         case self::CHECK_MODE_HYBRID: return __('Hybrid');
         default:
            return __('Unknown');
      }
   }

   public function isFlapping() : bool {
      return $this->fields['is_flapping'];
   }

   public function getHost() {
      static $host = null;
      if ($host == null) {
         $host = new ITILEventHost();
         $host->getFromDB($this->fields['hosts_id']);
      }
      return $host;
   }

   public function getHostName() : string {
      if ($this->isHostless()) {
         return null;
      }
      $host = $this->getHost();
      return $host ? $host->getHostName() : null;
   }

   public function getEventRestrictCriteria() : array {
      $restrict = [];
      $restrict['WHERE'] = [
         'itileventservices_id' => $this->getID()
      ];
      return $restrict;
   }

   public function getServiceInfoDisplay() {
      // TODO Switch to twig
      $status = self::getStatusName($this->fields['status']);

      $status_since_diff = Toolbox::getHumanReadableTimeDiff($this->fields['status_since']);
      $last_check_diff = Toolbox::getHumanReadableTimeDiff($this->fields['last_check']);
      $service_stats = [
         ITILEventHost::getTypeName(1) => $this->getHostName(),
         __('Last status change')   => (is_null($status_since_diff) ? __('No change') : $status_since_diff),
         __('Last check')           => (is_null($last_check_diff) ? __('Not checked') : $last_check_diff),
         __('Flapping')             => $this->fields['is_flapping'] ? __('Yes') : __('No')
      ];

      $toolbar_buttons = [
         [
            'label'  => __('Check now'),
            'action' => 'hostCheckNow()',
            'type'   => 'button',
         ],
         [
            'label'  => __('Schedule downtime'),
            'action' => 'hostScheduleDowtime()',
            'type'   => 'button',
         ]
      ];
      $btn_classes = 'btn btn-primary mx-1';
      $toolbar = "<div id='service-actions-toolbar'><div class='btn-toolbar'>";
      foreach ($toolbar_buttons as $button) {
         if ($button['type'] == 'button') {
            $toolbar .= "<button type='button' class='{$btn_classes}' onclick='{$button['action']}'>{$button['label']}</button>";
         } else if ($button['type'] == 'link') {
            $toolbar .= "<a href='{$button['action']}' class='{$btn_classes}'>{$button['label']}</a>";
         }
      }
      $toolbar .= "</div></div>";

      $out = $toolbar;
      $out .= "<div id='service-info' class='inline'>";
      $out .= "<table class='text-center w-100'><thead><tr>";
      $out .= "<th colspan='2'><h3>{$status}</h3></th>";
      $out .= "</tr></thead><tbody>";
      foreach ($service_stats as $label => $value) {
         $out .= "<tr><td><p style='font-size: 1.5em; margin: 0px'>{$label}</p><p style='font-size: 1.25em; margin: 0px'>{$value}</p></td></tr>";
      }
      $out .= '</tbody></table></div>';
      return $out;
   }

   public function dispatchITILEventServiceEvent(string $eventName) {
      global $CONTAINER;

      if (!isset($CONTAINER) || !$CONTAINER->has(EventDispatcher::class)) {
         return;
      }

      $dispatcher = $CONTAINER->get(EventDispatcher::class);
      $dispatcher->dispatch($eventName, new ITILEventServiceEvent($this));
   }

   public function checkFlappingState() {
      //FIXME Does not seem to work right. Allow soft/hard states.
      global $DB;

      if (!$this->fields['use_flap_detection'] || $this->isScheduledDown()) {
         // Ignore flapping status if check is disabled or if the service is expected to be down.
         return;
      }

      // Get caches array of last 20 state changes
      $flap_cache = $this->getFlappingStateCache();

      $total_state_change = 0;
      // Number of states that get cached
      $flap_check_max = 20;

      $weight = 0.80;
      $state_changes = 0.00;
      $last_state = $flap_cache[0];
      for ($i = 0; $i < count($flap_cache); $i++) {
         if ($flap_cache[$i] != $last_state) {
            $state_changes += $weight;
         }
         // Newer state changes are weighted heigher
         $weight += 0.02;
         $last_state = $flap_cache[$i];
      }

      $total_state_change = (int) (($state_changes / 20.00) * 100.00);

      if ($total_state_change < $this->fields['flap_threshold_low']) {
         // End flapping
         $this->update([
            'id'           => $this->getID(),
            'is_flapping'  => 0
         ]);
      } else if ($total_state_change > $this->fields['flap_threshold_high']) {
         // Begin flapping
         $this->update([
            'id'           => $this->getID(),
            'is_flapping'  => 1
         ]);
      }
   }

   /**
    * Called every time an ITILEvent is added so that the related service state can be updated.
    * 
    * @since 10.0.0
    * @param ITILEvent $event The event that was added
    * @return bool True if the service was updated successfully
    */
   public static function onEventAdd(ITILEvent $event) {
      $service = new ITILEventService();
      if ($event->fields['itileventservices_id'] >= 0 &&
            $service->getFromDB($event->fields['itileventservices_id'])) {
         $last_status = $service->fields['status'];
         $significance = $event->fields['significance'];

         // Check downtime
         $in_downtime = $service->isScheduledDown();
         if (!$service->fields['is_volatile']) {
            $to_update = [
               'id'           => $service->getID(),
               'last_check'   => $_SESSION['glpi_currenttime']
            ];
            // Stateful service checks
            if ($significance == ITILEvent::EXCEPTION && $last_status == self::STATUS_OK) {
               if (!$in_downtime) {
                  // Transition to problem state
                  $to_update['_problem'] = true;
               }
            } else if ($significance == ITILEvent::INFORMATION && $last_status != self::STATUS_OK) {
               // Transition to recovery state
               $to_update['_recovery'] = true;
               // Recoveries should cancel all non-fixed, active downtimes
               if ($in_downtime) {
                  $downtime = new ScheduledDowntime();
                  $downtimes = ScheduledDowntime::getForHostOrService($service->getID());
                  while ($data = $downtimes->next()) {
                     if ($data['is_fixed'] == 0) {
                        $downtime->update([
                           'id'        => $data['id'],
                           '_cancel'   => true
                        ]);
                     }
                  }
               }
            }

            $flap_cache = $service->getFlappingStateCache();
            array_shift($flap_cache);
            $flap_cache[] = $event->fields['significance'];
            $to_update['flap_state_cache'] = json_encode($flap_cache);
            $service->update($to_update);
            // Check flapping state if not in downtime and if it is enabled
            $service->checkFlappingState();
         } else {
            // Stateless service checks
         }
      }
   }

   private function resetFlappingStateCache() {
      $flap_cache = array_fill(0, 20, (string) self::STATUS_OK);
      $this->update([
         'id'                 => $this->getID(),
         'flap_state_cache'   => json_encode($flap_cache)
      ]);
   }

   private function rebuildFlappingStateCache() {
      
   }

   private function getFlappingStateCache() {
      $flap_cache = json_decode($this->fields['flap_state_cache'], true);
      if (!$flap_cache || !count($flap_cache)) {
         $this->resetFlappingStateCache();
      } else if (count($flap_cache) < 20) {
         $flap_cache = array_merge(array_fill(0, 20 - count($flap_cache), self::STATUS_OK), $flap_cache);
         $this->update([
            'id'                 => $this->getID(),
            'flap_state_cache'   => json_encode($flap_cache)
         ]);
      } else if (count($flap_cache) > 20) {
         $flap_cache = array_slice($flap_cache, -20);
         $this->update([
            'id'                 => $this->getID(),
            'flap_state_cache'   => json_encode($flap_cache)
         ]);
      }
      return json_decode($this->fields['flap_state_cache'], true);
   }

   public function prepareInputForUpdate($input): array
   {
      if (isset($input['_problem'])) {
         $input['status'] = self::STATUS_CRITICAL;
      } else if (isset($input['_recovery'])) {
         $input['status'] = self::STATUS_OK;
      }
      return $input;
   }

   public function post_updateItem($history = 1) {

      $host = new ITILEventHost();
      $is_hostservice = false;
      if ($host = $this->getHost()) {
         if ($host->fields['itileventservices_id_availability'] == $this->getID()) {
            $is_hostservice = true;
         }
      }
      if (isset($this->input['_problem'])) {
         if ($is_hostservice) {
            $host->dispatchITILEventHostEvent(ITILEventHostEvent::HOST_DOWN);
         } else {
            $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_PROBLEM);
         }
      } else if (isset($this->input['_recovery'])) {
         if ($is_hostservice) {
            $host->dispatchITILEventHostEvent(ITILEventHostEvent::HOST_UP);
         } else {
            $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_RECOVERY);
         }
      }
      if (isset($input['is_active']) && $input['is_active'] != $this->fields['is_active']) {
         if ($input['is_active']) {
            $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_ENABLE);
         } else {
            $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_DISABLE);
            if ($is_hostservice) {
               $host->update([
                  'id'     => $host->getID(),
                  'status' => ITILEventHost::STATUS_UNKNOWN
               ]);
            }
         }
      }
      if (isset($this->input['_acknowledge'])) {
         if ($is_hostservice) {
            $host->dispatchITILEventHostEvent(ITILEventHostEvent::HOST_ACKNOWLEDGE);
         } else {
            $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_ACKNOWLEDGE);
         }
      }
      if (isset($input['use_flap_detection']) &&
            $input['use_flap_detection'] != $this->fields['use_flap_detection']) {
         if (!$input['use_flap_detection']) {
            if ($is_hostservice) {
               $host->dispatchITILEventHostEvent(ITILEventHostEvent::HOST_DISABLE_FLAPPING);
            } else {
               $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_DISABLE_FLAPPING);
            }
         }
      } else if (isset($input['is_flapping']) && $input['is_flapping'] != $this->fields['is_flapping']) {
         if ($input['is_flapping']) {
            if ($is_hostservice) {
               $host->dispatchITILEventHostEvent(ITILEventHostEvent::HOST_START_FLAPPING);
            } else {
               $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_START_FLAPPING);
            }
         } else {
            if ($is_hostservice) {
               $host->dispatchITILEventHostEvent(ITILEventHostEvent::HOST_STOP_FLAPPING);
            } else {
               $this->dispatchITILEventServiceEvent(ITILEventServiceEvent::SERVICE_STOP_FLAPPING);
            }
         }
      }
   }
}