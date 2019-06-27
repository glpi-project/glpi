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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SIEMEventManagement class.
 * Contains functions for managing/viewing the dashboard and other top-level functions.
 * @since 10.0.0
 */
class SIEMEventManagement
{

   public static function getDashboardCards()
   {
      global $DB;

      $cards = [];

      $hosttable = SIEMHost::getTable();
      $servicetable = SIEMService::getTable();

      $host_statuses = [
         'up'              => 0,
         'down'            => 0,
         'unknown'         => 0,
         'unreachable'     => 0,
         'acknowledged'    => 0,
         'scheduleddown'   => 0
      ];
      $service_statuses = [
         'ok'              => 0,
         'warning'         => 0,
         'critical'        => 0,
         'unknown'         => 0,
         'acknowledged'    => 0,
         'scheduleddown'   => 0,
      ];
      $actively_down = [
         'SIEMService'   => [],
         'SIEMHost'      => []
      ];
      $actively_acknowledged = Acknowledgement::getActivelyAcknowldged();
      $actively_down = [
         'SIEMService'   => [],
         'SIEMHost'      => []
      ];
      $actively_down = ScheduledDowntime::getActivelyDown();

      $iterator = $DB->request([
         'SELECT' => ['glpi_siemhosts.id', 'status', 'is_reachable'],
         'FROM'   => $hosttable,
         'LEFT JOIN' => [
            $servicetable => [
               'FKEY'   => [
                  $servicetable  => 'id',
                  $hosttable     => 'siemservices_id_availability'
               ]
            ]
         ]
      ]);
      while ($data = $iterator->next()) {
         if (isset($actively_acknowledged['SIEMHost']) &&
               (in_array($data['id'], $actively_acknowledged['SIEMHost']))) {
            $host_statuses['acknowledged']++;
         } else if (isset($actively_down['SIEMHost']) &&
               (in_array($data['id'], $actively_down['SIEMHost']))) {
            $host_statuses['scheduleddown']++;
         } else if (!$data['is_reachable']) {
            $host_statuses['unreachable']++;
         } else {
            switch ($data['status']) {
               case SIEMService::STATUS_OK:
               case SIEMService::STATUS_WARNING:
                  $host_statuses['up']++;
                  break;
               case SIEMService::STATUS_CRITICAL:
                  $host_statuses['down']++;
                  break;
               case SIEMService::STATUS_UNKNOWN:
                  $host_statuses['unknown']++;
                  break;
            }
         }
      }

      $iterator = $DB->request([
         'SELECT' => ['id', 'status'],
         'FROM'   => $servicetable,
      ]);
      while ($data = $iterator->next()) {
         if (isset($actively_acknowledged['SIEMService']) &&
               (in_array($data['id'], $actively_acknowledged['SIEMService']))) {
            $service_statuses['acknowledged']++;
         } else if (isset($actively_down['SIEMService']) &&
               (in_array($data['id'], $actively_down['SIEMService']))) {
            $service_statuses['scheduleddown']++;
         } else {
            switch ($data['status']) {
               case SIEMService::STATUS_OK:
                  $service_statuses['ok']++;
                  break;
               case SIEMService::STATUS_WARNING:
                  $service_statuses['warning']++;
                  break;
               case SIEMService::STATUS_CRITICAL:
                  $service_statuses['critical']++;
                  break;
               case SIEMService::STATUS_UNKNOWN:
                  $service_statuses['unknown']++;
                  break;
            }
         }
      }

      $cards[0][] = [
         'title'  => __('Monitored Hosts and Services'),
         'type'   => 'table-v',
         'headers'   => [
            SIEMHost::getTypeName(1),
            SIEMService::getTypeName(1)
         ],
         'rows'  => [
            [
               'value' => countElementsInTable($hosttable)
            ],
            [
               'value' => countElementsInTable($servicetable)
            ],
         ]
      ];

      $cards[0][] = [
         'title'  => __('Host Status Summary'),
         'type'   => 'table-v',
         'headers'   => [
            __('Up'),
            __('Down'),
            __('Unknown'),
            __('Unreachable'),
            __('Acknowledged'),
            __('Scheduled down')],
         'rows'    => [
            [
               'value' => $host_statuses['up']
            ],
            [
               'class' => $host_statuses['down'] ? 'bg-danger' : '',
               'value' => $host_statuses['down']
            ],
            [
               'value' => $host_statuses['unknown']
            ],
            [
               'class' => $host_statuses['unreachable'] ? 'bg-danger' : '',
               'value' => $host_statuses['unreachable']
            ],
            [
               'class' => $host_statuses['acknowledged'] ? 'alert-danger' : '',
               'value' => $host_statuses['acknowledged']
            ],
            [
               'value' => $host_statuses['scheduleddown']
            ]
         ]
      ];

      $cards[0][] = [
         'title'  => __('Service Status Summary'),
         'type'   => 'table-v',
         'headers'   => [
            __('OK'),
            __('Warning'),
            __('Critical'),
            __('Unknown'),
            __('Acknowledged'),
            __('Scheduled down')],
         'rows'    => [
            [
               'value' => $service_statuses['ok']
            ],
            [
               'class' => $service_statuses['warning'] ? 'bg-warning' : '',
               'value' => $service_statuses['warning']
            ],
            [
               'class' => $service_statuses['critical'] ? 'bg-danger' : '',
               'value' => $service_statuses['critical']
            ],
            [
               'value' => $service_statuses['unknown']
            ],
            [
               'class' => $service_statuses['acknowledged'] ? 'alert-danger' : '',
               'value' => $service_statuses['acknowledged']
            ],
            [
               'value' => $service_statuses['scheduleddown']
            ]
         ]
      ];

      $active_alerts = SIEMEvent::getActiveAlerts();
      $card_activealerts = [
         'title'     => __('Active alerts'),
         'type'      => 'table',
         'headers'   => [
            __('Service'),
            __('Name'),
            __('Stateless'),
            __('Significance'),
            __('Date'),
            __('Content')
         ],
         'rows'      => []
      ];
      foreach ($active_alerts as $alert) {
         $card_activealerts['rows'][] = [
            [
               'value'  => $alert['service_name']
            ],
            [
               'value'  => $alert['name']
            ],
            [
               'value'  => $alert['service_stateless']
            ],
            [
               'value'  => $alert['significance']
            ],
            [
               'value'  => $alert['date']
            ],
            [
               'value'  => substr(json_decode($alert['content']), 0, 100)
            ]
         ];
      }
      $cards[1][] = $card_activealerts;

      $card_hostacknowledgements = [
         'title'     => __('Host acknowledgements'),
         'type'      => 'table',
         'headers'   => [
            __('Host'),
            __('User'),
            __('Comment'),
            __('Date'),
            __('Sticky'),
         ],
         'rows'      => []
      ];
      $card_serviceacknowledgements = [
         'title'     => __('Service acknowledgements'),
         'type'      => 'table',
         'headers'   => [
            __('Service'),
            __('User'),
            __('Comment'),
            __('Date'),
            __('Sticky'),
         ],
         'rows'      => []
      ];
      foreach ($actively_acknowledged as $acknowledgement) {
         if ($acknowledgement['is_service']) {
            $card_serviceacknowledgements['rows'][] = [
               [
                  //TODO Use name
                  'value'  => $acknowledgement['items_id']
               ],
               [
                  //TODO Use name
                  'value'  => $acknowledgement['users_id']
               ],
               [
                  'value'  => substr($acknowledgement['content'], 0, 100)
               ],
               [
                  'value'  => $acknowledgement['date']
               ],
               [
                  'value'  => $acknowledgement['is_sticky'] ? __('Yes') : __('No')
               ]
            ];
         } else {
            $card_hostacknowledgements['rows'][] = [
               [
                  //TODO Use name
                  'value'  => $acknowledgement['items_id']
               ],
               [
                  //TODO Use name
                  'value'  => $acknowledgement['users_id']
               ],
               [
                  'value'  => substr($acknowledgement['content'], 0, 100)
               ],
               [
                  'value'  => $acknowledgement['date']
               ],
               [
                  'value'  => $acknowledgement['is_sticky'] ? __('Yes') : __('No')
               ]
            ];
         }
      }
      $cards[2][] = $card_hostacknowledgements;
      $cards[3][] = $card_serviceacknowledgements;

      return $cards;
   }
}