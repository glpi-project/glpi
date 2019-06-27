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
 * SIEMEvent class
 * @since 10.0.0
 */
class SIEMEvent extends CommonDBTM
{

   /**
    * An event that doesn't require any response
    */
   const INFORMATION = 0;

   /**
    * An event that indicates a potential issue that requires monitoring or preventative measures.
    */
   const WARNING = 1;

   /**
    * An event that indicates an issue and requires a response.
    */
   const EXCEPTION = 2;

   /**
    * An event that was logged but not acted on. For informational alerts, this is the only valid status.
    */
   const STATUS_NEW = 0;

   /**
    * An event that has been acknowledged by a technician.
    * Duplicate alerts should be dropped for a period of time.
    * Valid only for events on volatile services.
    */
   const STATUS_ACKNOWLEDGED = 1;

   /**
    * An event that is currently being remediated by a technician or automatically.
    * Similar to acknowledged events, duplicate events are dropped.
    * Valid only for events on volatile services.
    */
   const STATUS_REMEDIATING = 2;

   /**
    * An event that may be resolved. The event will be considered resolved after a time.
    * If a duplicate event is logged, it is linked and this event is downgraded to acknowledged.
    * Valid only for events on volatile services.
    */
   const STATUS_MONITORING = 3;

   /**
    * An event that has been determined to be resolved either manually or automatically after a time period.
    * If a duplicate alert comes in, it is treated as a new event and not linked.
    * Valid only for events on volatile services.
    */
   const STATUS_RESOLVED = 4;

   /**
    * An event that went so long without being resolved that another event has replaced it through correlation rules or a timeout period.
    * This event will be linked to the replacement event if one exists.
    * If there is no replacement (timeout), then new events will not be linked.
    * Valid only for events on volatile services.
    */
   const STATUS_EXPIRED = 5;

   static function getTypeName($nb = 0)
   {
      return _n('Event', 'Events', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'SIEMEvent' :
               return '';
            default:
               return self::createTabEntry('Event Management');
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      switch ($item->getType()) {
         case 'SIEMEvent' :
            self::showForSIEMEvent($item);
            break;
         default:
            self::showEventManagementTab($item);
            break;
      }
      return true;
   }

   static function getForbiddenActionsForMenu() {
      return ['add'];
   }

   static function getAdditionalMenuContent() {

      $menu['siemevent']['title'] = __('Event Management');
      $menu['siemevent']['page']  = static::getDashboardURL(false);

      $menu['siemevent']['options']['SIEMHost']['title'] = __('Hosts');
      $menu['siemevent']['options']['SIEMHost']['page'] = SIEMHost::getSearchURL(false);
      $menu['siemevent']['options']['SIEMHost']['links']['search'] = SIEMHost::getSearchURL(false);
      $menu['siemevent']['options']['SIEMHost']['links']['add'] = SIEMHost::getFormURL(false);

      $menu['siemevent']['options']['SIEMService']['title'] = __('Services');
      $menu['siemevent']['options']['SIEMService']['page'] = SIEMService::getSearchURL(false);
      $menu['siemevent']['options']['SIEMService']['links']['search'] = SIEMService::getSearchURL(false);
      $menu['siemevent']['options']['SIEMService']['links']['add'] = SIEMService::getFormURL(false);

      return $menu;
   }

   function prepareInputForAdd($input)
   {
      $input = parent::prepareInputForAdd($input);

      // All events must be associated to a service or have a service id of -1 for internal
      if (!isset($input['siemservices_id']) && $input['siemservices_id'] != -1) {
         return false;
      }

      if (isset($input['_sensor_fault'])) {
         $input['significance'] = self::EXCEPTION;
         $input['name'] = 'sensor_fault';
      }

      if (isset($input['content']) && !is_string($input['content'])) {
         $input['content'] = json_encode($input['content']);
      }

      if (!isset($input['significance']) || $input['significance'] < 0 || $input['significance'] > 2) {
         $input['significance'] = self::INFORMATION;
      }

      // Process event filtering rules
      $rules = new RuleSIEMEventFilterCollection();

      $input['_accept'] = true;
      $input = $rules->processAllRules($input,
                                       $input,
                                       ['recursive' => true],
                                       ['condition' => RuleSIEMEvent::ONADD]);
      $input = Toolbox::stripslashes_deep($input);

      if (!$input['_accept']) {
         // Drop the event
         return false;
      } else {
         if ($input['siemservices_id'] >= 0) {
            $service = new SIEMService();
            $service->getFromDB($input['siemservices_id']);
            if ($service->fields['suppress_informational']) {
               // Process event to update service/host state, then drop it so it doesn't get saved.
               $event = new self();
               $event->fields = $input;
               $service->onEventAdd($event);
               return false;
            }
         }
         return $input;
      }
   }

   function post_addItem()
   {
      if (!isset($this->input['correlation_id']) && !isset($this->fields['correlation_id'])) {
         // Create a new correlation ID in case one isn't assigned by the correlation engine
         $this->fields['correlation_id'] = uniqid('', true);
      }

      $this->update([
         'id'                 => $this->getID(),
         'correlation_id'   => $this->fields['correlation_id']
      ]);

      // Process event business rules. Only used for correlation, notifications, and tracking
      $rules = new RuleSIEMEventCollection();
      $input = $rules->processAllRules($this->fields, $this->fields, ['recursive' => true], ['condition' => RuleSIEMEvent::ONADD]);
      $input = Toolbox::stripslashes_deep($input);

      $this->update([
         'id' => $this->getID()
      ] + $input);

      // Update the related service
      SIEMService::onEventAdd($this);
     
      parent::post_addItem();
   }

   function cleanDBonPurge()
   {
      $this->deleteChildrenAndRelationsFromDb(
         [
            Itil_SIEMEvent::class
         ]
      );

         parent::cleanDBonPurge();
   }

   /**
    * Gets the name of a significance level from the int value
    *
    * @since 10.0.0
    *
    * @param int $significance The significance level
    *
    * @return string The significance level name
    */
   static function getSignificanceName($significance)
   {
      switch ($significance) {
         case 1:
            return __('Warning');
         case 2:
            return __('Exception');
         case 0:
         default:
            return __('Information');
      }
   }

   /**
    * Displays or gets a dropdown menu of significance levels.
    * The default functionality is to display the dropdown.
    *
    * @since 10.0.0
    *
    * @param array $options Dropdown options
    *
    * @return void|string
    * @see Dropdown::showFromArray()
    */
   static function dropdownSignificance(array $options = [])
   {
      global $CFG_GLPI;

      $p = [
         'name'     => 'significance',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = [];
      $values[0] = self::getSignificanceName(0);
      $values[1] = self::getSignificanceName(1);
      $values[2] = self::getSignificanceName(2);

      return Dropdown::showFromArray($p['name'], $values, $p);
   }

   /**
    * Gets the name of an event status from the int value
    *
    * @since 10.0.0
    *
    * @param int $status The event status
    *
    * @return string The event status name
    */
   static function getStatusName($status) : string
   {
      switch ($status) {
         case 0:
            return __('New');
         case 1:
            return __('Acknowledged');
         case 2:
            return __('Remediating');
         case 3:
            return __('Monitoring');
         case 4:
            return __('Resolved');
         case 5:
            return __('Expired');
         default:
            return __('Unknown');
      }
   }

   /**
    * Displays or gets a dropdown menu of event statuses.
    * The default functionality is to display the dropdown.
    *
    * @since 10.0.0
    *
    * @param array $options Dropdown options
    *
    * @return void|string
    * @see Dropdown::showFromArray()
    */
   static function dropdownStatus(array $options = [])
   {
      global $CFG_GLPI;

      $p = [
         'name'     => 'status',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = [];
      $values[0] = self::getStatusName(0);
      $values[1] = self::getStatusName(1);
      $values[2] = self::getStatusName(2);
      $values[3] = self::getStatusName(3);
      $values[4] = self::getStatusName(4);
      $values[5] = self::getStatusName(5);

      return Dropdown::showFromArray($p['name'], $values, $p);
   }

   /**
    * Get an array of statuses that indicate the alert is still active.
    *    By default, this includes New, Acknowledged, and Remediating.
    *
    * @since 10.0.0
    *
    * @return array Array of status integers
    */
   public static function getActiveStatusArray()
   {
      return [self::STATUS_NEW, self::STATUS_ACKNOWLEDGED, self::STATUS_REMEDIATING, self::STATUS_MONITORING];
   }

   public static function getEventsForHostOrService(int $items_id, bool $is_service = true, array $params) {
      global $DB;

      $p = [
         'start'  => 0,
         'limit'  => -1
      ];
      $p = array_replace($p, $params);

      $events = [];
      $servicetable = SIEMService::getTable();
      $hosttable = SIEMHost::getTable();
      $eventtable = self::getTable();

      $criteria = [
         'SELECT' => [
            'glpi_siemevents.*',
         ],
         'FROM'   => $eventtable,
         'LEFT JOIN' => [
            $servicetable => [
               'FKEY'   => [
                  $servicetable  => 'id',
                  $eventtable => 'siemservices_id'
               ]
            ]
         ],
         'ORDERBY'  => ['date DESC']
      ];
      if ($p['start'] > 0) {
         $criteria['START'] = $p['start'];
      }
      if ($p['limit'] > 1) {
         $criteria['LIMIT'] = $p['limit'];
      }
      if ($is_service) {
         $criteria['WHERE'] = [
            'siemservices_id' => $items_id
         ];
      } else {
         $criteria['SELECT'][] = 'glpi_siemservices.siemhosts_id';
         $criteria['WHERE'] = [
            'siemhosts_id' => $items_id
         ];
         $criteria['LEFT JOIN'][$hosttable] = [
            'FKEY'   => [
               $hosttable  => 'id',
               $servicetable => 'siemhosts_id'
            ]
         ];
      }

      $iterator = $DB->request($criteria);
      while ($data = $iterator->next()) {
         $events[] = $data;
      }

      return $events;
   }

   /**
    * Gets all events with the same correlation UUID as this event
    *
    * @since 10.0.0
    *
    * @param bool $exclusive True if the results should not include this event
    * @return DBmysqlIterator
    */
   public function getCorrelated(bool $exclusive = true)
   {
      global $DB;
      $query = [
         'FROM' => self::getTable(),
         'WHERE' => [
            'correlation_id' => $this->fields['correlation_id']
         ]
      ];
      if ($exclusive) {
         $query['WHERE'][] = [
            'NOT' => ['id' => $this->getID()]
         ];
      }
      return $DB->request($query);
   }

   /**
    * Update all events with the same correlation UUID
    *
    * @since 10.0.0
    *
    * @param array $params Query parameters ([:field name => field value)
    * @param array $where  WHERE clause
    * @param bool $exclusive True if this event should also be updated
    * @return PDOStatement|boolean
    */
   public function updateCorrelated(array $params, array $where = [], bool $exclusive = true)
   {
      global $DB;

      $where = [
         'NOT' => [
            'id' => $this->getID()
         ],
         'correlation_id' => $this->fields['correlation_id']
      ] + $where;

      if ($exclusive) {
         $where[] = [
            'NOT' => ['id' => $this->getID()]
         ];
      }

      return $DB->update(self::getTable(), $params, $where);
   }

   /**
    * Gets the translated event name from the event's logger (GLPI or plugin)
    *
    * @since 10.0.0
    *
    * @param string $name   The unlocalized event name
    * @param string $logger The plugin that created the event or null if made by GLPI
    * @return string The localized name if possible, otherwise the unlocalized name is returned
    */
   public static function getLocalizedEventName(string $name, $logger) {
      if ($logger !== null) {
         return Plugin::doOneHook($logger, 'translateEventName', $name);
      }
      return $name;
   }

   /**
    * Get an associative array of event properties from the content JSON field
    *
    * @since 10.0.0
    *
    * @param boolean $translate Attempt to translate the event properties.
    * @return array|string Associative array or HTML display of event properties
    */
   public static function getEventProperties($content, $logger, array $params = [])
   {
      $p = [
         'translate' => true,
         'format'    => 'array'
      ];
      $p = array_replace($p, $params);

      if (!in_array($p['format'], ['array', 'pretty', 'plain'])) {
         $p['format'] = 'array';
      }

      if ($content !== null) {
         $properties = json_decode($content, true);
      } else {
         return '';
      }
      if ($properties == null) {
         return '';
      }

      $props = [];

      foreach ($properties as $key => $value) {
         $props[$key] = [
            'name'   => $key, // Potentially localized property name
            'value'  => $value // Property value
         ];
      }

      if ($p['translate']) {
         if ($logger !== null) {
            $props_t = Plugin::doOneHook($logger, 'translateEventProperties', $props);
            if ($props_t) {
               $props = $props_t;
            }
         } else {
            Glpi\Event::translateEventProperties($props);
         }
      }

      if ($p['format'] == 'array') {
         return $props;
      } else {
         $text_content = '';
         foreach ($props as $event_property) {
            $propname = strip_tags($event_property['name']);
            $propvalue = strip_tags($event_property['value']);

            if ($p['format'] == 'pretty') {
               $text_content .= "<b>{$propname}</b>: {$propvalue}<br>";
            } else {
               $text_content .= "{$propname}: {$propvalue}<br>";
            }
         }
         return $text_content;
      }
   }

   public static function getVisibilityCriteria() {
      $servicetable = SIEMService::getTable();
      $service_templatetable = SIEMServiceTemplate::getTable();
      $eventtable = self::getTable();

      return [
         'LEFT JOIN' => [
            $servicetable => [
               $servicetable  => 'id',
               $eventtable    => 'siemservices_id'
            ],
            $service_templatetable => [
               $service_templatetable  => 'id',
               $servicetable           => 'siemservicetemplates_id'
            ]
         ]
      ];
   }

   /**
    * Create a ticket, change, or problem from this event
    *
    * @since 10.0.0
    *
    * @param string $tracking_type  The tracking class (Ticket, Change, or Problem)
    * @return boolean True if the tracking was created successfully
    */
   public function createTracking(string $tracking_type)
   {
      global $DB;

      if (is_subclass_of($tracking_type, 'CommonITILObject')) {
         $tracking = new $tracking_type();

         $content = SIEMEvent::getEventProperties($this->fields['content'], $this->fields['logger'], [
            'format' => 'plain'
         ]);

         $tracking_id = $tracking->add([
            'name'               => $this->fields['name'],
            'content'            => $content,
            '_correlation_id'    => $this->fields['correlation_id']
         ]);

         if (!$tracking_id) {
            return false;
         } else {
            // Add related items if they exist
            if ($tracking_type == 'Change') {
               $items_tracking = 'glpi_items_changes';
            } else if ($tracking_type == 'Problem') {
               $items_tracking = 'glpi_items_problems';
            } else {
               $items_tracking = 'glpi_items_tickets';
            }
            // TODO Replace Item_SIEMEvent with host or serivce?
            $iterator = $DB->request([
               'SELECT'    => ['itemtype', 'items_id'],
               'FROM'      => SIEMHost::getTable(),
               'WHERE'     => [
                  'siemevents_id_availability'   => $this->getID()
               ]
            ] + self::getVisibilityCriteria());

            $actors_responsible = ['user' => [], 'group' => []];
            while ($data = $iterator->next()) {
               $DB->insert($items_tracking, [
                  'itemtype'                       => $data['itemtype'],
                  'items_id'                       => $data['items_id'],
                  $tracking::getForeignKeyField()  => $tracking_id
               ]);
               // Get responsible tech and group
               $actors = $DB->request([
                  'SELECT' => ['users_id_tech', 'groups_id_tech'],
                  'FROM'   => $data['itemtype']::getTable(),
                  'WHERE'  => ['id' => $data['items_id']]
               ])->next();
               if (!is_null($actors['users_id_tech'])) {
                  $actors_responsible['user'][] = $actors['users_id_tech'];
               }
               if (!is_null($actors['groups_id_tech'])) {
                  $actors_responsible['group'][] = $actors['groups_id_tech'];
               }
            }

            // Assign responsible actors
            // TODO Respect Entity assignment settings?
            $tracking_user = new $tracking->userlinkclass();
            $tracking_group = new $tracking->grouplinkclass();
            foreach ($actors_responsible as $type => $actor_id) {
               if ($type == 'user') {
                  $tracking_user->add([
                     'type'                           => CommonITILActor::ASSIGN,
                     'users_id'                       => $actor_id[0],
                     $tracking::getForeignKeyField()  => $tracking_id
                  ]);
               } else if ($type == 'group') {
                  $tracking_group->add([
                     'type'                           => CommonITILActor::ASSIGN,
                     'groups_id'                      => $actor_id[0],
                     $tracking::getForeignKeyField()  => $tracking_id
                  ]);
               }
            }

            $itil_siemevent = new Itil_SIEMEvent();
            $itil_siemevent->add([
               'itemtype'        => $tracking_type,
               'items_id'        => $tracking_id,
               'siemevents_id'   => $this->getID()
            ]);
         }
      } else {
         Toolbox::logError(__('Tracking type must be a subclass of CommonITILObject'));
         return false;
      }
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
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
         'field'              => 'significance',
         'name'               => __('Significance'),
         'datatype'           => 'specific',
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'correlation_id',
         'name'               => __('Correlation ID'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'logger',
         'name'               => __('Logger'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __('Content'),
         'datatype'           => 'text'
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
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
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

   /**
    * Get the dashboard page URL for the current class
    *
    * @since 10.0.0
    *
    * @param $full path or relative one (true by default)
   **/
   static function getDashboardURL($full = true) {
      global $CFG_GLPI, $router;

      if ($router != null) {
         $page = $router->pathFor('siemevent-dashboard');
         return $page;
      }

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      // SIEMEvents are only compatible with the new UI
      return "$dir/front/central.php";
   }

   static function showEventManagementTab(CommonDBTM $item) {
      global $DB, $PLUGIN_HOOKS;

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      $eventhost = new SIEMHost();
      $eventservice = new SIEMService();
      $matchinghosts = $eventhost->find(['items_id' => $item->getID(), 'itemtype' => $item::getType()], [], 1);
      $has_host = (count($matchinghosts) == 1);
      $matchingservices = [];
      $has_services = false;
      if (!$has_host) {
         $has_services = false;
      } else {
         $eventhost->getFromDB($matchinghosts[0]['id']);
         $matchingservices = $eventservice->find(['siemhosts_id' => $matchinghosts[0]]);
         $has_services = (count($matchingservices) > 0);
      }

      if (!$has_host && !$has_services) {
         echo "<div class='alert alert-warning'>" . __('This host is not monitored by any plugin') . "</div>";
         Html::showSimpleForm(SIEMHost::getFormURL(),
               'add', __('Enable monitoring'),
               ['itemtype' => $item->getType(),
               'items_id' => $item->getID()]);
         return;
      } else if (!$has_services) {
         echo "<div class='alert alert-warning'>" . __('No services on this host are monitored by any plugin') . "</div>";
      } else if (!$eventhost->getAvailabilityService()) {
         echo "<div class='alert alert-warning'>" . __('No host availability service set') . "</div>";
      }

      $out = $eventhost->getHostInfoDisplay();
      $out .= SIEMService::getFormForHost($eventhost);

      $events = self::getEventsForHostOrService($eventhost->getID(), false, [
         'start' => $start,
         'limit' => $_SESSION['glpilist_limit']
      ]);

      $historical = Html::printAjaxPager('', $start, count($events), '', false);
      $historical .= "<table class='tab_cadre_fixehov'><thead>";
      $historical .= "<tr><th colspan='5'>Historical</th></tr><tr><th></th>";
      $historical .= "<th>".__('Name')."</th>";
      $historical .= "<th>".__('Significance')."</th>";
      $historical .= "<th>".__('Date')."</th>";
      $historical .= "<th>".__('Status')."</th>";
      $historical .= "</tr></thead><tbody>";

      $temp_service = new SIEMService();
      foreach ($events as $event) {
         $style = '';
         $icon = 'fas fa-info-circle';
         $active = in_array($event['status'], self::getActiveStatusArray());
         $temp_service->getFromDB($event['siemservices_id']);
         $localized_name = self::getLocalizedEventName($event['name'], $temp_service->fields['logger']);
         if ($event['significance'] == SIEMEvent::WARNING) {
            if ($active) {
               $style = "style='background-color: {$_SESSION['glpieventwarning_color']}'";
            }
            $icon = 'fas fa-exclamation-triangle';
         } else if ($event['significance'] == SIEMEvent::EXCEPTION) {
            if ($active) {
               $style = "style='background-color: {$_SESSION['glpieventexception_color']}'";
            }
            $icon = 'fas fa-exclamation-circle';
         }
         $historical .= "<tr id='siemevent_{$event['id']}' class='tab_bg_2' $style onclick='toggleEventDetails(this);'>";
         $historical .= "<td class='center'><i class='{$icon} fa-lg' title='".
               SIEMEvent::getSignificanceName($event['significance'])."'/></td>";
         $historical .= "<td>{$localized_name}</td>";
         $historical .= "<td>" . self::getSignificanceName($event['significance']) . "</td>";
         $historical .= "<td>{$event['date']}</td>";
         $historical .= "<td>" . self::getStatusName($event['status']) . "</td>";
         $historical .= "</tr>";
         $historical .= "<tr id='siemevent_{$event['id']}_content' class='tab_bg_2' $style hidden='hidden'>";
         $historical .= "<td colspan='6'><p>";
         $historical .= self::getEventProperties($event['content'], $temp_service->fields['logger'], [
            'format' => 'pretty'
         ]);
         $historical .= "</p></td></tr>\n";
      }

      $historical .= "</tbody></table>";
      $historical .= Html::printAjaxPager('', $start, count($events), '', false);

      $out .= $historical;
      echo $out;
   }

   public static function getListForHostOrService(int $items_id, bool $is_service = true, array $params) {

      $events = self::getEventsForHostOrService($eventhost->getID(), false, [
         'start' => $start,
         'limit' => $_SESSION['glpilist_limit']
      ]);

      $out = Html::printAjaxPager('', $start, count($events), '', false);
      $out .= "<table class='tab_cadre_fixehov'><thead>";
      $out .= "<tr><th colspan='5'>Historical</th></tr><tr><th></th>";
      $out .= "<th>".__('Name')."</th>";
      $out .= "<th>".__('Significance')."</th>";
      $out .= "<th>".__('Date')."</th>";
      $out .= "<th>".__('Status')."</th>";
      $out .= "</tr></thead><tbody>";

      $temp_service = new SIEMService();
      foreach ($events as $event) {
         $style = '';
         $icon = 'fas fa-info-circle';
         $active = in_array($event['status'], self::getActiveStatusArray());
         $temp_service->getFromDB($event['siemservices_id']);
         $localized_name = self::getLocalizedEventName($event['name'], $temp_service->fields['logger']);
         if ($event['significance'] == SIEMEvent::WARNING) {
            if ($active) {
               $style = "style='background-color: {$_SESSION['glpieventwarning_color']}'";
            }
            $icon = 'fas fa-exclamation-triangle';
         } else if ($event['significance'] == SIEMEvent::EXCEPTION) {
            if ($active) {
               $style = "style='background-color: {$_SESSION['glpieventexception_color']}'";
            }
            $icon = 'fas fa-exclamation-circle';
         }
         $out .= "<tr id='siemevent_{$event['id']}' class='tab_bg_2' $style onclick='toggleEventDetails(this);'>";
         $out .= "<td class='center'><i class='{$icon} fa-lg' title='".
               SIEMEvent::getSignificanceName($event['significance'])."'/></td>";
         $out .= "<td>{$localized_name}</td>";
         $out .= "<td>" . self::getSignificanceName($event['significance']) . "</td>";
         $out .= "<td>{$event['date']}</td>";
         $out .= "<td>" . self::getStatusName($event['status']) . "</td>";
         $out .= "</tr>";
         $out .= "<tr id='siemevent_{$event['id']}_content' class='tab_bg_2' $style hidden='hidden'>";
         $out .= "<td colspan='6'><p>";
         $out .= self::getEventProperties($event['content'], $temp_service->fields['logger'], [
            'format' => 'pretty'
         ]);
         $out .= "</p></td></tr>\n";
      }

      $out .= "</tbody></table>";
      $out .= Html::printAjaxPager('', $start, count($events), '', false);

      return $out;
   }

   public static function getReportList() {
      return [
        'downtime_by_entity'     => __('Downtime by entity'),
        'downtime_by_location'   => __('Downtime by location'),
        'downtime_by_itemtype'   => __('Downtime by host type'),
      ];
   }

   public static function archiveOldEvents() {
      $p = [
         'max-age-informational'    => 30 * DAY_TIMESTAMP,
         'max-age-warning'          => 60 * DAY_TIMESTAMP,
         'max-age-exception'        => 60 * DAY_TIMESTAMP,
         'archive-resolved-only'    => true,
         'archive-full-correlated'  => true,
         'archive-correlate-mode'   => 'max',
         'keep-tracking'            => true,
         'archive-location'         => GLPI_DUMP_DIR,
         'keep-last-events'         => 5 // Always keep last 5 events for each service/host
      ];  
   }

   /**
    * Checks for any active or hybrid services that are due to check for events.
    * Then, signals the service's logger to poll for the events.
    * @since 10.0.0
    * @return void
    */
   public static function cronPollEvents(CronTask $task) {
      global $DB;

      $event = new SIEMEvent();
      $to_poll = $DB->request([
         'FROM'   => SIEMService::getTable(),
         'LEFT JOIN' => [
            SIEMServiceTemplate::getTable() => [
               'FKEY' => [
                  SIEMService::getTable()          => 'siemservicetemplates_id',
                  SIEMServiceTemplate::getTable()  => 'id',
               ]
            ]
         ],
         'WHERE'  => [
            new QueryExpression('DATE_ADD(last_check, INTERVAL check_interval MINUTE) <= NOW()'),
            'check_mode'   => [SIEMService::CHECK_MODE_ACTIVE, SIEMService::CHECK_MODE_HYBRID],
            'is_active'    => 1,
            new QueryExpression('logger IS NOT NULL'),
            new QueryExpression('sensor IS NOT NULL'),  
         ]
      ]);

      $eventdatas = [];
      $allservices = [];
      $poll_queue = [];

      while ($data = $to_poll->next()) {
         array_push($allservices, $data['id']);
         $poll_queue[$data['logger']][$data['sensor']][] = $data['id'];
      }

      foreach ($poll_queue as $logger => $sensors) {
         foreach ($sensors as $sensor => $service_ids) {
            $results = Plugin::doOneHook($logger, 'poll_sensor', ['sensor' => $sensor, 'service_ids' => $service_ids]);
            $eventdatas[$logger][$sensor] = $results;
         }
      }

      // Array of service ids that had some data from the sensors
      $reported = [];

      // Create event from the results
      foreach ($eventdatas as $logger => $sensors) {
         foreach ($sensors as $sensor => $results) {
            foreach ($results as $service_id => $result) {
               if (!is_null($result) && is_array($result)) {
                  $input = $result;
                  $input['siemservices_id'] = $service_id;
                  $event->add($input);
                  array_push($reported, $service_id);
               }
            }
         }
      }

      // Report sensor fault for all services that had no data
      $faulted = array_diff($allservices, $reported);

      foreach ($faulted as $service_id) {
         // This will create a sensor fault event
         $event->add([
            '_sensor_fault'   => true,
            'siemservices_id' => $service_id,
            'date'            => $_SESSION['glpi_currenttime']
         ]);
      }

      $task->addVolume(count($reported));

      if (count($reported) > 0) {
         return 1;
      } else {
         return 0;
      }
   }

   public static function getActiveAlerts() {
      global $DB;

      $eventtable = self::getTable();
      $servicetable = SIEMService::getTable();

      $iterator = $DB->request([
         'SELECT'    => [
            'glpi_siemevents.*',
            'glpi_siemservices.name AS service_name',
            'glpi_siemservices.is_stateless AS service_stateless'
         ],
         'FROM'      => $eventtable,
         'LEFT JOIN' => [
            $servicetable => [
               'FKEY'   => [
                  $servicetable  => 'id',
                  $eventtable    => 'siemservices_id' 
               ]
            ]
         ],
         'WHERE'     => [
            'NOT' => [
               'glpi_siemservices.status' => 0,
               'glpi_siemservices.status' => 2
            ]
         ]
      ]);

      $alerts = [];
      while ($data = $iterator->next()) {
         $alerts[] = $data;
      }
      return $alerts;
   }
}