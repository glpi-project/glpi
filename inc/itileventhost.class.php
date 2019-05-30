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

/**
 * ITILEventHost class.
 * This represents a host that is able to be monitored through one or more ITILEventServices.
 *
 * @since 10.0.0
 */
class ITILEventHost extends CommonDBTM {
   use Monitored;

   static $rightname                = 'event';

   /**
    * Host is reachable
    */
   const STATUS_OK         = 0;

   /**
    * Host is not reachable. Service alerts are suppressed.
    */
   const STATUS_DOWN       = 1;

   /**
    * Host availability is not being monitored.
    */
   const STATUS_UNKNOWN    = 2;

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb = 0) {
      return _n('Host', 'Hosts', $nb);
   }

   public function isScheduledDown() : bool {
      return ScheduledDowntime::isHostScheduledDown($this->getID());
   }

   public static function getStatusName($status) : string {
      switch ($status) {
         case self::STATUS_OK:
            return __('OK');
         case self::STATUS_DOWN:
            return __('Down');
         case self::STATUS_UNKNOWN:
         default:
            return __('Unknown');
      }
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Item type'),
         'datatype'           => 'itemtypename'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'items_id',
         'name'               => __('Item ID'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'status_since',
         'name'               => __('Status since'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'is_flapping',
         'name'               => __('Flapping'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_itileventservices',
         'field'              => 'name',
         'linkfield'          => 'itileventservices_id_availability',
         'name'               => __('Availability service'),
         'datatype'           => 'itemlink'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => $this->getTable(),
         'field'              => 'status',
         'name'               => __('Status'),
         'datatype'           => 'specific',
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      return $tab;
   }

   static function getFormURL($id = 0, $full = true) {
      global $router;

      if ($router != null) {
         $page = $router->pathFor(
            'add-asset', [
               'itemtype'  => get_class(),
            ]
         );
         return $page;
      }

      $link     = parent::getFormURL($full);
      $link    .= (strpos($link, '?') ? '&':'?').'id=' . $id;
      return $link;
   }

   public function getBackgroundColorClass() : string {
      if ($this->isFlapping()) {
         return 'bg-warning';
      }
      switch ($this->getHostStatus()) {
         case self::STATUS_DOWN: return 'bg-danger';
         case self::STATUS_OK: return 'bg-success';
         case self::STATUS_UNKNOWN: return 'bg-warning';
      }
      return '';
   }

   public function getAvailabilityService() : ITILEventService {
      if (!$this->fields['itileventservices_id_availability']) {
         return null;
      }
      static $service = null;
      if ($service == null) {
         $service = new ITILEventService();
         if (!$service->getFromDB($this->fields['itileventservices_id_availability'])) {
            return null;
         }
      }
      return $service;
   }

   public function getHostStatus() : int {
      $service = $this->getAvailabilityService();
      if ($service) {
         return $service->fields['status'];
      } else {
         return self::STATUS_UNKNOWN;
      }
   }

   public function getHostName() : string {
      global $DB;

      $hosttype = $this->fields['itemtype'];
      $iterator = $DB->request([
         'SELECT' => ['name'],
         'FROM'   => $hosttype::getTable(),
         'WHERE'  => [
            'id'  => $this->fields['items_id']
         ]
      ]);
      return $iterator->next()['name'];
   }

   public function getLastHostStatusCheck() {
      $service = $this->getAvailabilityService();
      if ($service) {
         return $service->fields['last_check'];
      } else {
         return null;
      }
   }

   public function getLastHostStatusChange() {
      $service = $this->getAvailabilityService();
      if ($service) {
         return $service->fields['status_since'];
      } else {
         return null;
      }
   }

   public function isFlapping() : bool {
      $service = $this->getAvailabilityService();
      if ($service) {
         return $service->fields['is_flapping'];
      } else {
         return false;
      }
   }

   public function getEventRestrictCriteria() : array {
      $restrict = [];
      $restrict['LEFT JOIN'] = [
         ITILEventService::getTable() => [
            'FKEY' => [
               ITILEventService::getTable()  => 'id',
               ITILEvent::getTable()         => 'itileventservices_id'
            ]
         ]
      ];
      $restrict['WHERE'] = [
         'hosts_id'  => $this->getID()
      ];
      return $restrict;
   }

   public function getHostInfoDisplay() {
      global $DB;

      // TODO Switch to twig
      $host_info_bg = $this->getBackgroundColorClass();
      $status = self::getStatusName($this->getHostStatus());

      if ($this->getAvailabilityService()) {
         $status_since_diff = Toolbox::getHumanReadableTimeDiff($this->getLastHostStatusChange());
         $last_check_diff = Toolbox::getHumanReadableTimeDiff($this->getLastHostStatusCheck());
         $host_stats = [
            __('Last status change')   => (is_null($status_since_diff) ? __('No change') : $status_since_diff),
            __('Last check')           => (is_null($last_check_diff) ? __('Not checked') : $last_check_diff),
            __('Flapping')             => $this->isFlapping() ? __('Yes') : __('No')
         ];
      } else {
         $host_stats = [
            __('Host availability not monitored') => __('Set the availability service to monitor the host')
         ];
      }

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
         ],
         [
            'label'  => sprintf(__('Add %s'), ITILEventService::getTypeName(1)),
            'action' => 'addService()',
            'type'   => 'button',
         ]
      ];
      $btn_classes = 'btn btn-primary mx-1';
      $toolbar = "<div id='host-actions-toolbar'><div class='btn-toolbar'>";
      foreach ($toolbar_buttons as $button) {
         if ($button['type'] == 'button') {
            $toolbar .= "<button type='button' class='{$btn_classes}' onclick='{$button['action']}'>{$button['label']}</button>";
         } else if ($button['type'] == 'link') {
            $toolbar .= "<a href='{$button['action']}' class='{$btn_classes}'>{$button['label']}</a>";
         }
      }
      $toolbar .= "</div></div>";

      $out = $toolbar;
      $out .= "<div id='host-info' class='w-25 float-right inline {$host_info_bg}'>";
      $out .= "<table class='text-center w-100'><thead><tr>";
      $out .= "<th colspan='2'><h3>{$status}</h3></th>";
      $out .= "</tr></thead><tbody>";
      foreach ($host_stats as $label => $value) {
         $out .= "<tr><td><p style='font-size: 1.5em; margin: 0px'>{$label}</p><p style='font-size: 1.25em; margin: 0px'>{$value}</p></td></tr>";
      }
      $out .= '</tbody></table></div>';

      $out .= "<div id='host-service-info' class='inline float-left w-75'>";
      if ($this->getAvailabilityService()) {
         $host_service = $this->getAvailabilityService();
         $calendar_name = __('Unspecified');
         if (!is_null($host_service->fields['calendars_id'])) {
            $iterator = $DB->request([
               'SELECT' => ['name'],
               'FROM'   => Calendar::getTable(),
               'WHERE'  => ['id' => $host_service->fields['calendars_id']]
            ]);
            if ($iterator->count()) {
               $calendar_name = $iterator->next()['name'];
            }
         }

         $service_name = $host_service->fields['name'];
         $check_mode = ITILEventService::getCheckModeName($host_service->fields['check_mode']);
         $check_interval = !is_null($host_service->fields['check_interval']) ?
               $host_service->fields['check_interval'] : __('Unspecified');
         $notif_interval = !is_null($host_service->fields['notificationinterval']) ?
               $host_service->fields['notificationinterval'] : __('Unspecified');
         $service_stats = [
            [
               __('Name')                    => $service_name,
               __('Check mode')              => $check_mode,
            ],
            [
               __('Check interval')          => $check_interval,
               __('Notification interval')   => $notif_interval,
            ],
            [
               __('Calendar')                => $calendar_name,
               __('Flap detection')          => $host_service->fields['use_flap_detection'] ? __('Yes') : __('No')
            ]
         ];
         $out .= "<h3>".__('Availability service info') . "</h3>";
         $out .= "<table class='text-center w-100'><tbody>";
         foreach ($service_stats as $statrow) {
            $out .= "<tr>";
            foreach ($statrow as $label => $value) {
               $out .= "<td><p style='font-size: 1.5em; margin: 0px'>{$label}</p><p style='font-size: 1.25em; margin: 0px'>{$value}</p></td>";
            }
            $out .= "</tr>";
         }
         $out .= '</tbody></table>';
         
      } else {
         $out .= "<form>";
         $out .= "<label for='service'>" . __('Service') . "</label>";
         $out .= Plugin::dropdown([
            'name' => 'sservice',
            'display' => false
         ]);
         $out .= Html::closeForm(false);
      }
      $out .= "</div>";
      return $out;
   }
}