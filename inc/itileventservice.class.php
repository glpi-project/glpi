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
use Glpi\EventDispatcher\EventDispatcher;

/**
 * ITILEventService class.
 * This represents a software, service, or metric on a host device that is able to be monitored.
 *
 * @since 10.0.0
 */
class ITILEventService extends CommonDBTM {
   use Monitored;

   /** Service is functioning as expected */
   const STATUS_OK         = 0;

   /** Service is completely non functional */
   const STATUS_DOWN       = 1;

   /** Service is not being monitored */
   const STATUS_UNKNOWN    = 2;

   /** Service is functional but functionality or performance is limited */
   const STATUS_DEGRADED   = 3;

   /** This service is actively polled */
   const CHECK_MODE_ACTIVE    = 0;

   /** This service sends events to GLPI as needed */
   const CHECK_MODE_PASSIVE   = 1;

   /** This service can operate in both active and passive modes */
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
      $template = new ITILEventServiceTemplate();
      $template->getFromDB($this->fields['itileventservicetemplates_id']);
      foreach($template->fields as $field => $value) {
         if ($field !== 'id') {
            $this->fields[$field] = $value;
         }
      }
   }

   public function isScheduledDown() : bool {
      $iterator = ScheduledDowntime::getForHostOrService($this->getID(), 1);
      return $iterator->count() > 0;
   }

   public function isHostless() : bool {
      return $this->fields['hosts_id'] < 0;
   }

   public static function getStatusName($status) {
      switch ($status) {
         case self::STATUS_OK: return __('OK');
         case self::STATUS_DOWN: return __('Down');
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
      $host = null;
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
      return $host->getHostName();
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
      $dispatcher->dispatcher($eventName, new ITILEventServiceEvent($this));
   }
}