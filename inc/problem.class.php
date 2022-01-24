<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
 * Problem class
**/
class Problem extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;
   static protected $forward_entity_to = ['ProblemCost'];

   // From CommonITIL
   public $userlinkclass        = 'Problem_User';
   public $grouplinkclass       = 'Group_Problem';
   public $supplierlinkclass    = 'Problem_Supplier';

   static $rightname            = 'problem';
   protected $usenotepad        = true;


   const MATRIX_FIELD         = 'priority_matrix';
   const URGENCY_MASK_FIELD   = 'urgency_mask';
   const IMPACT_MASK_FIELD    = 'impact_mask';
   const STATUS_MATRIX_FIELD  = 'problem_status';

   const READMY               = 1;
   const READALL              = 1024;


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb = 0) {
      return _n('Problem', 'Problems', $nb);
   }


   function canSolve() {

      return (self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], $this->getClosedStatusArray())
              && (Session::haveRight(self::$rightname, UPDATE)
                  || (Session::haveRight(self::$rightname, self::READMY)
                      && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(CommonITILActor::ASSIGN,
                                                   $_SESSION["glpigroups"]))))));
   }


   static function canView() {
      return Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]);
   }


   /**
    * Is the current user have right to show the current problem ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive())) {
         return false;
      }
      return (Session::haveRight(self::$rightname, self::READALL)
              || (Session::haveRight(self::$rightname, self::READMY)
                  && ($this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                      || $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && ($this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])
                              || $this->haveAGroup(CommonITILActor::OBSERVER,
                                                   $_SESSION["glpigroups"])))
                      || ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(CommonITILActor::ASSIGN,
                                                   $_SESSION["glpigroups"]))))));
   }


   /**
    * Is the current user have right to create the current problem ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return Session::haveRight(self::$rightname, CREATE);
   }


   /**
    * is the current user could reopen the current problem
    *
    * @since 9.4.0
    *
    * @return boolean
    */
   function canReopen() {
      return Session::haveRight('followup', CREATE)
             && in_array($this->fields["status"], $this->getClosedStatusArray())
             && ($this->isAllowedStatus($this->fields['status'], self::INCOMING)
                 || $this->isAllowedStatus($this->fields['status'], self::ASSIGNED));
   }


   function pre_deleteItem() {
      global $CFG_GLPI;

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
         NotificationEvent::raiseEvent('delete', $this);
      }
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $timeline    = $item->getTimelineItems();
               $nb_elements = count($timeline);

               $ong = [
                  5 => __("Processing problem")." <sup class='tab_nb'>$nb_elements</sup>",
                  1 => __('Analysis')
               ];

               if ($item->canUpdate()) {
                  $ong[4] = __('Statistics');
               }

               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showAnalysisForm();
                  break;

               case 4 :
                  $item->showStats();
                  break;
               case 5 :
                  echo "<div class='timeline_box'>";
                  $rand = mt_rand();
                  $item->showTimelineForm($rand);
                  $item->showTimeline($rand);
                  echo "</div>";
                  break;
            }
      }
      return true;
   }


   function defineTabs($options = []) {
      $ong = [];
      $this->defineDefaultObjectTabs($ong, $options);
      $this->addStandardTab('Problem_Ticket', $ong, $options);
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('ProblemCost', $ong, $options);
      $this->addStandardTab('Itil_Project', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      if ($this->hasImpactTab()) {
         $this->addStandardTab('Impact', $ong, $options);
      }
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('Problem_Ticket', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      // CommonITILTask does not extends CommonDBConnexity
      $pt = new ProblemTask();
      $pt->deleteByCriteria(['problems_id' => $this->fields['id']]);

      $this->deleteChildrenAndRelationsFromDb(
         [
            Change_Problem::class,
            // Done by parent: Group_Problem::class,
            Item_Problem::class,
            // Done by parent: ITILSolution::class,
            // Done by parent: Problem_Supplier::class,
            Problem_Ticket::class,
            // Done by parent: Problem_User::class,
            ProblemCost::class,
         ]
      );

      parent::cleanDBonPurge();
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      parent::post_updateItem($history);

      $donotif = count($this->updates);

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif && $CFG_GLPI["use_notifications"]) {
         $mailtype = "update";
         if (isset($this->input["status"]) && $this->input["status"]
             && in_array("status", $this->updates)
             && in_array($this->input["status"], $this->getSolvedStatusArray())) {

            $mailtype = "solved";
         }

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status", $this->updates)
             && in_array($this->input["status"], $this->getClosedStatusArray())) {

            $mailtype = "closed";
         }

         // Read again problem to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }
   }


   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);

      if (((isset($input["_users_id_assign"]) && ($input["_users_id_assign"] > 0))
           || (isset($input["_groups_id_assign"]) && ($input["_groups_id_assign"] > 0))
           || (isset($input["_suppliers_id_assign"]) && ($input["_suppliers_id_assign"] > 0)))
          && (in_array($input['status'], $this->getNewStatusArray()))) {

         $input["status"] = self::ASSIGNED;
      }

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI, $DB;

      parent::post_addItem();

      if (isset($this->input['_tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->input['_tickets_id'])) {
            $pt = new Problem_Ticket();
            $pt->add([
                'tickets_id'  => $this->input['_tickets_id'],
                'problems_id' => $this->fields['id'],
            ]);

            if (!empty($ticket->fields['itemtype'])
                && ($ticket->fields['items_id'] > 0)) {
               $it = new Item_Problem();
               $it->add([
                   'problems_id' => $this->fields['id'],
                   'itemtype'    => $ticket->fields['itemtype'],
                   'items_id'    => $ticket->fields['items_id'],
               ]);
            }

            //Copy associated elements
            $iterator = $DB->request([
               'FROM'   => Item_Ticket::getTable(),
               'WHERE'  => [
                  'tickets_id'   => $this->input['_tickets_id']
               ]
            ]);
            $assoc = new Item_Problem;
            while ($row = $iterator->next()) {
               unset($row['tickets_id']);
               unset($row['id']);
               $row['problems_id'] = $this->fields['id'];
               $assoc->add(Toolbox::addslashes_deep($row));
            }
         }
      }

      // Processing Email
      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
         // Clean reload of the problem
         $this->getFromDB($this->fields['id']);

         $type = "new";
         if (isset($this->fields["status"])
             && in_array($this->input["status"], $this->getSolvedStatusArray())) {
            $type = "solved";
         }
         NotificationEvent::raiseEvent($type, $this);
      }

      if (isset($this->input['_from_items_id'])
          && isset($this->input['_from_itemtype'])) {
         $item_problem = new Item_Problem();
         $item_problem->add([
            'items_id'      => (int)$this->input['_from_items_id'],
            'itemtype'      => $this->input['_from_itemtype'],
            'problems_id'   => $this->fields['id'],
            '_disablenotif' => true
         ]);
      }

      $this->handleItemsIdInput();
   }

   /**
    * Get default values to search engine to override
   **/
   static function getDefaultSearchRequest() {

      $search = ['criteria' => [0 => ['field'      => 12,
                                                     'searchtype' => 'equals',
                                                     'value'      => 'notold']],
                      'sort'     => 19,
                      'order'    => 'DESC'];

      return $search;
   }


   function getSpecificMassiveActions($checkitem = null) {
      $actions = parent::getSpecificMassiveActions($checkitem);
      if (ProblemTask::canCreate()) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_task'] = __('Add a new task');
      }
      if ($this->canAdminActors()) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_actor'] = __('Add an actor');
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'update_notif']
               = __('Set notifications for all actors');
      }

      return $actions;
   }


   function rawSearchOptions() {
      $tab = [];

      $tab = array_merge($tab, $this->getSearchOptionsMain());

      $tab[] = [
         'id'                 => '63',
         'table'              => 'glpi_items_problems',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of items'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_items_problems',
         'field'              => 'items_id',
         'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
         'datatype'           => 'specific',
         'comments'           => true,
         'nosort'             => true,
         'nosearch'           => true,
         'additionalfields'   => ['itemtype'],
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '131',
         'table'              => 'glpi_items_problems',
         'field'              => 'itemtype',
         'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'ticket_types',
         'nosort'             => true,
         'additionalfields'   => ['itemtype'],
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab = array_merge($tab, $this->getSearchOptionsActors());

      $tab[] = [
         'id'                 => 'analysis',
         'name'               => __('Analysis')
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => $this->getTable(),
         'field'              => 'impactcontent',
         'name'               => __('Impacts'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => $this->getTable(),
         'field'              => 'causecontent',
         'name'               => __('Causes'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '62',
         'table'              => $this->getTable(),
         'field'              => 'symptomcontent',
         'name'               => __('Symptoms'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab = array_merge($tab, ITILFollowup::rawSearchOptionsToAdd());

      $tab = array_merge($tab, ProblemTask::rawSearchOptionsToAdd());

      $tab = array_merge($tab, $this->getSearchOptionsSolution());

      $tab = array_merge($tab, $this->getSearchOptionsStats());

      $tab = array_merge($tab, ProblemCost::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => 'ticket',
         'name'               => Ticket::getTypeName(Session::getPluralNumber())
      ];

      $tab[] = [
         'id'                 => '141',
         'table'              => 'glpi_problems_tickets',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of tickets'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      return $tab;
   }


   static function rawSearchOptionsToAdd() {

      $tab = [];

      $tab[] = [
         'id'                 => 'problem',
         'name'               => __('Problems')
      ];

      $tab[] = [
         'id'                 => '200',
         'table'              => 'glpi_problems_tickets',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of problems'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '201',
         'table'              => Problem::getTable(),
         'field'              => 'name',
         'name'               => Problem::getTypeName(1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => Problem_Ticket::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                  => '202',
         'table'               => Problem::getTable(),
         'field'               => 'status',
         'name'                => __('Status'),
         'datatype'            => 'specific',
         'searchtype'          => 'equals',
         'searchequalsonfield' => true,
         'massiveaction'       => false,
         'forcegroupby'        => true,
         'joinparams'          => [
            'beforejoin'          => [
               'table'               => Problem_Ticket::getTable(),
               'joinparams'          => [
                  'jointype'            => 'child',
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '203',
         'table'              => Problem::getTable(),
         'field'              => 'solvedate',
         'name'               => __('Resolution date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => Problem_Ticket::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '204',
         'table'              => Problem::getTable(),
         'field'              => 'date',
         'name'               => __('Opening date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => Problem_Ticket::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
               ]
            ]
         ]
      ];

      return $tab;

   }

      /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'status' :
            return Problem::getStatus($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'status':
            return Problem::dropdownStatus(['name' => $name,
                                             'value' => $values[$field],
                                             'display' => false]);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * get the problem status list
    *
    * @param $withmetaforsearch  boolean  (false by default)
    *
    * @return array
   **/
   static function getAllStatusArray($withmetaforsearch = false) {

      // To be overridden by class
      $tab = [self::INCOMING => _x('status', 'New'),
                   self::ACCEPTED => _x('status', 'Accepted'),
                   self::ASSIGNED => _x('status', 'Processing (assigned)'),
                   self::PLANNED  => _x('status', 'Processing (planned)'),
                   self::WAITING  => __('Pending'),
                   self::SOLVED   => _x('status', 'Solved'),
                   self::OBSERVED => __('Under observation'),
                   self::CLOSED   => _x('status', 'Closed')];

      if ($withmetaforsearch) {
         $tab['notold']    = _x('status', 'Not solved');
         $tab['notclosed'] = _x('status', 'Not closed');
         $tab['process']   = __('Processing');
         $tab['old']       = _x('status', 'Solved + Closed');
         $tab['all']       = __('All');
      }
      return $tab;
   }


   /**
    * Get the ITIL object closed status list
    *
    * @since 0.83
    *
    * @return array
   **/
   static function getClosedStatusArray() {

      // To be overridden by class
      $tab = [self::CLOSED];
      return $tab;
   }


   /**
    * Get the ITIL object solved or observe status list
    *
    * @since 0.83
    *
    * @return array
   **/
   static function getSolvedStatusArray() {
      // To be overridden by class
      $tab = [self::OBSERVED, self::SOLVED];
      return $tab;
   }

   /**
    * Get the ITIL object new status list
    *
    * @since 0.83.8
    *
    * @return array
   **/
   static function getNewStatusArray() {
      return [self::INCOMING, self::ACCEPTED];
   }

   /**
    * Get the ITIL object assign, plan or accepted status list
    *
    * @since 0.83
    *
    * @return array
   **/
   static function getProcessStatusArray() {

      // To be overridden by class
      $tab = [self::ACCEPTED, self::ASSIGNED, self::PLANNED];

      return $tab;
   }


   /**
    * @since 0.84
    *
    * @param $start
    * @param $status             (default 'proces)
    * @param $showgroupproblems  (true by default)
   **/
   static function showCentralList($start, $status = "process", $showgroupproblems = true) {
      global $DB, $CFG_GLPI;

      if (!static::canView()) {
         return false;
      }

      $WHERE = [
         'is_deleted' => 0
      ];
      $search_users_id = [
         'glpi_problems_users.users_id'   => Session::getLoginUserID(),
         'glpi_problems_users.type'       => CommonITILActor::REQUESTER
      ];
      $search_assign = [
         'glpi_problems_users.users_id'   => Session::getLoginUserID(),
         'glpi_problems_users.type'       => CommonITILActor::ASSIGN
      ];

      if ($showgroupproblems) {
         $search_users_id  = [0];
         $search_assign = [0];

         if (count($_SESSION['glpigroups'])) {
            $search_users_id = [
               'glpi_groups_problems.groups_id' => $_SESSION['glpigroups'],
               'glpi_groups_problems.type'      => CommonITILActor::REQUESTER
            ];
            $search_assign = [
               'glpi_groups_problems.groups_id' => $_SESSION['glpigroups'],
               'glpi_groups_problems.type'      => CommonITILActor::ASSIGN
            ];
         }
      }

      switch ($status) {
         case "waiting" : // on affiche les problemes en attente
            $WHERE = array_merge(
               $WHERE,
               $search_assign,
               ['status' => self::WAITING]
            );
            break;

         case "process" : // on affiche les problemes planifi??s ou assign??s au user
            $WHERE = array_merge(
               $WHERE,
               $search_assign,
               ['status' => [self::PLANNED, self::ASSIGNED]]
            );
            break;

         default :
            $WHERE = array_merge(
               $WHERE,
               $search_users_id,
               [
                  'status' => [
                     self::INCOMING,
                     self::ACCEPTED,
                     self::PLANNED,
                     self::ASSIGNED,
                     self::WAITING
                  ]
               ]
            );
            $WHERE['NOT'] = $search_assign;
      }

      $criteria = [
         'SELECT'          => ['glpi_problems.id'],
         'DISTINCT'        => true,
         'FROM'            => 'glpi_problems',
         'LEFT JOIN'       => [
            'glpi_problems_users'   => [
               'ON' => [
                  'glpi_problems_users'   => 'problems_id',
                  'glpi_problems'         => 'id'
               ]
            ],
            'glpi_groups_problems'  => [
               'ON' => [
                  'glpi_groups_problems'  => 'problems_id',
                  'glpi_problems'         => 'id'
               ]
            ]
         ],
         'WHERE'           => $WHERE + getEntitiesRestrictCriteria('glpi_problems'),
         'ORDERBY'         => 'date_mod DESC'
      ];
      $iterator = $DB->request($criteria);

      $total_row_count = count($iterator);
      $displayed_row_count = (int)$_SESSION['glpidisplay_count_on_home'] > 0
         ? min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count)
         : $total_row_count;

      if ($displayed_row_count > 0) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr class='noHover'><th colspan='3'>";

         $options  = [
            'criteria' => [],
            'reset'    => 'reset',
         ];
         $forcetab         = '';
         if ($showgroupproblems) {
            switch ($status) {

               case "waiting" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::WAITING;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 8; // groups_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options, '&amp;')."\">".
                         Html::makeTitle(__('Problems on pending status'), $displayed_row_count, $total_row_count)."</a>";
                  break;

               case "process" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = 'process';
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 8; // groups_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options, '&amp;')."\">".
                         Html::makeTitle(__('Problems to be processed'), $displayed_row_count, $total_row_count)."</a>";
                  break;

               default :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = 'notold';
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 71; // groups_id
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options, '&amp;')."\">".
                         Html::makeTitle(__('Your problems in progress'), $displayed_row_count, $total_row_count)."</a>";
            }

         } else {
            switch ($status) {
               case "waiting" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::WAITING;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 5; // users_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = Session::getLoginUserID();
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options, '&amp;')."\">".
                         Html::makeTitle(__('Problems on pending status'), $displayed_row_count, $total_row_count)."</a>";
                  break;

               case "process" :
                  $options['criteria'][0]['field']      = 5; // users_id_assign
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = Session::getLoginUserID();
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 12; // status
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'process';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options, '&amp;')."\">".
                         Html::makeTitle(__('Problems to be processed'), $displayed_row_count, $total_row_count)."</a>";
                  break;

               default :
                  $options['criteria'][0]['field']      = 4; // users_id
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = Session::getLoginUserID();
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 12; // status
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'notold';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                        Toolbox::append_params($options, '&amp;')."\">".
                        Html::makeTitle(__('Your problems in progress'), $displayed_row_count, $total_row_count)."</a>";
            }
         }

         echo "</th></tr>";
         echo "<tr><th></th>";
         echo "<th>"._n('Requester', 'Requesters', 1)."</th>";
         echo "<th>".__('Description')."</th></tr>";
         $i = 0;
         while ($i < $displayed_row_count && ($data = $iterator->next())) {
            self::showVeryShort($data['id'], $forcetab);
            $i++;
         }
         echo "</table>";

      }
   }


   /**
    * Get problems count
    *
    * @since 0.84
    *
    * @param $foruser boolean : only for current login user as requester (false by default)
   **/
   static function showCentralCount($foruser = false) {
      global $DB, $CFG_GLPI;

      // show a tab with count of jobs in the central and give link
      if (!static::canView()) {
         return false;
      }
      if (!Session::haveRight(self::$rightname, self::READALL)) {
         $foruser = true;
      }

      $table = self::getTable();
      $criteria = [
         'SELECT' => [
            'status',
            'COUNT'  => '* AS COUNT',
         ],
         'FROM'   => $table,
         'WHERE'  => getEntitiesRestrictCriteria($table),
         'GROUP'  => 'status'
      ];

      if ($foruser) {
         $criteria['LEFT JOIN'] = [
            'glpi_problems_users' => [
               'ON' => [
                  'glpi_problems_users'   => 'problems_id',
                  $table                  => 'id', [
                     'AND' => [
                        'glpi_problems_users.type' => CommonITILActor::REQUESTER
                     ]
                  ]
               ]
            ]
         ];
         $WHERE = ['glpi_problems_users.users_id' => Session::getLoginUserID()];

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $criteria['LEFT JOIN']['glpi_groups_problems'] = [
               'ON' => [
                  'glpi_groups_problems'  => 'problems_id',
                  $table                  => 'id', [
                     'AND' => [
                        'glpi_groups_problems.type' => CommonITILActor::REQUESTER
                     ]
                  ]
               ]
            ];
            $WHERE['glpi_groups_problems.groups_id'] = $_SESSION['glpigroups'];
         }
         $criteria['WHERE'][] = ['OR' => $WHERE];
      }

      $deleted_criteria = $criteria;
      $criteria['WHERE']['glpi_problems.is_deleted'] = 0;
      $deleted_criteria['WHERE']['glpi_problems.is_deleted'] = 1;
      $iterator = $DB->request($criteria);
      $deleted_iterator = $DB->request($deleted_criteria);

      $status = [];
      foreach (self::getAllStatusArray() as $key => $val) {
         $status[$key] = 0;
      }

      while ($data = $iterator->next()) {
         $status[$data["status"]] = $data["COUNT"];
      }

      $number_deleted = 0;
      while ($data = $deleted_iterator->next()) {
         $number_deleted += $data["COUNT"];
      }

      $options = [];
      $options['criteria'][0]['field']      = 12;
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = 'process';
      $options['criteria'][0]['link']       = 'AND';
      $options['reset']                     ='reset';

      echo "<table class='tab_cadrehov' >";
      echo "<tr class='noHover'><th colspan='2'>";

      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
               Toolbox::append_params($options, '&amp;')."\">".__('Problem followup')."</a>";

      echo "</th></tr>";
      echo "<tr><th>".Problem::getTypeName(Session::getPluralNumber())."</th>
            <th class='numeric'>"._x('quantity', 'Number')."</th></tr>";

      foreach ($status as $key => $val) {
         $options['criteria'][0]['value'] = $key;
         echo "<tr class='tab_bg_2'>";
         echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                    Toolbox::append_params($options, '&amp;')."\">".self::getStatus($key)."</a></td>";
         echo "<td class='numeric'>$val</td></tr>";
      }

      $options['criteria'][0]['value'] = 'all';
      $options['is_deleted']  = 1;
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                 Toolbox::append_params($options, '&amp;')."\">".__('Deleted')."</a></td>";
      echo "<td class='numeric'>".$number_deleted."</td></tr>";

      echo "</table><br>";
   }


   /**
    * @since 0.84
    *
    * @param $ID
    * @param $forcetab  string   name of the tab to force at the display (default '')
   **/
   static function showVeryShort($ID, $forcetab = '') {
      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $viewusers = User::canView();

      $problem   = new self();
      $rand      = mt_rand();
      if ($problem->getFromDBwithData($ID, 0)) {

         $bgcolor = $_SESSION["glpipriority_".$problem->fields["priority"]];
         $name    = sprintf(__('%1$s: %2$s'), __('ID'), $problem->fields["id"]);
         echo "<tr class='tab_bg_2'>";
         echo "<td>
            <div class='priority_block' style='border-color: $bgcolor'>
               <span style='background: $bgcolor'></span>&nbsp;$name
            </div>
         </td>";
         echo "<td class='center'>";

         if (isset($problem->users[CommonITILActor::REQUESTER])
             && count($problem->users[CommonITILActor::REQUESTER])) {
            foreach ($problem->users[CommonITILActor::REQUESTER] as $d) {
               if ($d["users_id"] > 0) {
                  $userdata = getUserName($d["users_id"], 2);
                  $name     = "<span class='b'>".$userdata['name']."</span>";
                  if ($viewusers) {
                     $name = sprintf(__('%1$s %2$s'), $name,
                                     Html::showToolTip($userdata["comment"],
                                                       ['link'    => $userdata["link"],
                                                             'display' => false]));
                  }
                  echo $name;
               } else {
                  echo $d['alternative_email']."&nbsp;";
               }
               echo "<br>";
            }
         }

         if (isset($problem->groups[CommonITILActor::REQUESTER])
             && count($problem->groups[CommonITILActor::REQUESTER])) {
            foreach ($problem->groups[CommonITILActor::REQUESTER] as $d) {
               echo Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               echo "<br>";
            }
         }

         echo "</td>";

         echo "<td>";
         $link = "<a id='problem".$problem->fields["id"].$rand."' href='".
                  Problem::getFormURLWithID($problem->fields["id"]);
         if ($forcetab != '') {
            $link .= "&amp;forcetab=".$forcetab;
         }
         $link .= "'>";
         $link .= "<span class='b'>".$problem->fields["name"]."</span></a>";
         $link = printf(__('%1$s %2$s'), $link,
                        Html::showToolTip($problem->fields['content'],
                                          ['applyto' => 'problem'.$problem->fields["id"].$rand,
                                                'display' => false]));

         echo "</td>";

         // Finish Line
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>".__('No problem in progress.')."</i></td></tr>";
      }
   }

   /**
    * @param $ID
    * @param $options   array
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (!static::canView()) {
         return false;
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '37';

      $default_values = self::getDefaultValues();

      // Restore saved value or override with page parameter
      $saved = $this->restoreInput();

      // Restore saved values and override $this->fields
      $this->restoreSavedValues($saved);

      // Set default options
      if (!$ID) {
         foreach ($default_values as $key => $val) {
            if (!isset($options[$key])) {
               if (isset($saved[$key])) {
                  $options[$key] = $saved[$key];
               } else {
                  $options[$key] = $val;
               }
            }
         }

         if (isset($options['tickets_id']) || isset($options['_tickets_id'])) {
            $tickets_id = $options['tickets_id'] ?? $options['_tickets_id'];
            $ticket = new Ticket();
            if ($ticket->getFromDB($tickets_id)) {
               $options['content']             = $ticket->getField('content');
               $options['name']                = $ticket->getField('name');
               $options['impact']              = $ticket->getField('impact');
               $options['urgency']             = $ticket->getField('urgency');
               $options['priority']            = $ticket->getField('priority');
               if (isset($options['tickets_id'])) {
                  //page is reloaded on category change, we only want category on the very first load
                  $options['itilcategories_id']   = $ticket->getField('itilcategories_id');
               }
               $options['time_to_resolve']     = $ticket->getField('time_to_resolve');
               $options['entities_id']         = $ticket->getField('entities_id');
            }
         }
      }

      $this->initForm($ID, $options);

      $canupdate = !$ID || (Session::getCurrentInterface() == "central" && $this->canUpdateItem());

      $showuserlink = 0;
      if (User::canView()) {
         $showuserlink = 1;
      }

      if (!$this->isNewItem()) {
         $options['formtitle'] = sprintf(
            __('%1$s - ID %2$d'),
            $this->getTypeName(1),
            $ID
         );
         //set ID as already defined
         $options['noid'] = true;
      }

      if (!isset($options['template_preview'])) {
         $options['template_preview'] = 0;
      }

      // Load template if available :
      $tt = $this->getITILTemplateToUse(
         $options['template_preview'],
         $this->getType(),
         ($ID ? $this->fields['itilcategories_id'] : $options['itilcategories_id']),
         ($ID ? $this->fields['entities_id'] : $options['entities_id'])
      );

      // Predefined fields from template : reset them
      if (isset($options['_predefined_fields'])) {
         $options['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
      } else {
         $options['_predefined_fields'] = [];
      }

      // Store predefined fields to be able not to take into account on change template
      // Only manage predefined values on ticket creation
      $predefined_fields = [];
      $tpl_key = $this->getTemplateFormFieldName();
      if (!$ID) {

         if (isset($tt->predefined) && count($tt->predefined)) {
            foreach ($tt->predefined as $predeffield => $predefvalue) {
               if (isset($default_values[$predeffield])) {
                  // Is always default value : not set
                  // Set if already predefined field
                  // Set if ticket template change
                  if (((count($options['_predefined_fields']) == 0)
                       && ($options[$predeffield] == $default_values[$predeffield]))
                      || (isset($options['_predefined_fields'][$predeffield])
                          && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
                      || (isset($options[$tpl_key])
                          && ($options[$tpl_key] != $tt->getID()))
                      // user pref for requestype can't overwrite requestype from template
                      // when change category
                      || (($predeffield == 'requesttypes_id')
                          && empty($saved))
                      || (isset($ticket) && $options[$predeffield] == $ticket->getField($predeffield))
                  ) {

                     // Load template data
                     $options[$predeffield]            = $predefvalue;
                     $this->fields[$predeffield]      = $predefvalue;
                     $predefined_fields[$predeffield] = $predefvalue;
                  }
               }
            }
            // All predefined override : add option to say predifined exists
            if (count($predefined_fields) == 0) {
               $predefined_fields['_all_predefined_override'] = 1;
            }

         } else { // No template load : reset predefined values
            if (count($options['_predefined_fields'])) {
               foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
                  if ($options[$predeffield] == $predefvalue) {
                     $options[$predeffield] = $default_values[$predeffield];
                  }
               }
            }
         }
      }

      foreach ($default_values as $name => $value) {
         if (!isset($options[$name])) {
            if (isset($saved[$name])) {
               $options[$name] = $saved[$name];
            } else {
               $options[$name] = $value;
            }
         }
      }

      // Put ticket template on $options for actors
      $options[str_replace('s_id', '', $tpl_key)] = $tt;

      if ($options['template_preview']) {
         // Add all values to fields of tickets for template preview
         foreach ($options as $key => $val) {
            if (!isset($this->fields[$key])) {
               $this->fields[$key] = $val;
            }
         }
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left' width='$colsize1%'>";
      echo $tt->getBeginHiddenFieldText('date');
      if (!$ID) {
         printf(__('%1$s%2$s'), __('Opening date'), $tt->getMandatoryMark('date'));
      } else {
         echo __('Opening date');
      }
      echo $tt->getEndHiddenFieldText('date');
      echo "</th>";
      echo "<td class='left' width='$colsize2%'>";

      $this->displayHiddenItemsIdInput($options);

      if (isset($tickets_id)) {
         echo "<input type='hidden' name='_tickets_id' value='".$tickets_id."'>";
      }

      if (isset($options['_add_fromitem'])
          && isset($options['_from_items_id'])
          && isset($options['_from_itemtype'])) {
         echo Html::hidden('_from_items_id', ['value' => $options['_from_items_id']]);
         echo Html::hidden('_from_itemtype', ['value' => $options['_from_itemtype']]);
      }

      echo $tt->getBeginHiddenFieldValue('date');
      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeField(
         "date", [
            'value'      => $date,
            'maybeempty' => false,
            'required'   => ($tt->isMandatoryField('date') && !$ID)
         ]
      );
      echo $tt->getEndHiddenFieldValue('date', $this);
      echo "</td>";

      echo "<th>".$tt->getBeginHiddenFieldText('time_to_resolve');
      if (!$ID) {
         printf(__('%1$s%2$s'), __('Time to resolve'), $tt->getMandatoryMark('time_to_resolve'));
      } else {
         echo __('Time to resolve');
      }
      echo $tt->getEndHiddenFieldText('time_to_resolve');
      echo "</th>";
      echo "<td width='$colsize2%' class='left'>";
      echo $tt->getBeginHiddenFieldValue('time_to_resolve');
      if ($this->fields["time_to_resolve"] == 'NULL') {
         $this->fields["time_to_resolve"] = '';
      }
      Html::showDateTimeField(
         "time_to_resolve", [
            'value'    => $this->fields["time_to_resolve"],
            'required'   => ($tt->isMandatoryField('time_to_resolve') && !$ID)
         ]
      );
      echo $tt->getEndHiddenFieldValue('time_to_resolve', $this);

      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'><th>".__('By')."</th><td>";
         User::dropdown(['name'   => 'users_id_recipient',
                              'value'  => $this->fields["users_id_recipient"],
                              'entity' => $this->fields["entities_id"],
                              'right'  => 'all']);
         echo "</td>";
         echo "<th>".__('Last update')."</th>";
         echo "<td>".Html::convDateTime($this->fields["date_mod"])."\n";
         if ($this->fields['users_id_lastupdater'] > 0) {
            printf(__('%1$s: %2$s'), __('By'),
                   getUserName($this->fields["users_id_lastupdater"], $showuserlink));
         }
         echo "</td></tr>";
      }

      if ($ID
          && (in_array($this->fields["status"], $this->getSolvedStatusArray())
              || in_array($this->fields["status"], $this->getClosedStatusArray()))) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>".__('Date of solving')."</th>";
         echo "<td>";
         Html::showDateTimeField("solvedate", ['value'      => $this->fields["solvedate"],
                                                    'maybeempty' => false]);
         echo "</td>";
         if (in_array($this->fields["status"], $this->getClosedStatusArray())) {
            echo "<th>".__('Closing date')."</th>";
            echo "<td>";
            Html::showDateTimeField("closedate", ['value'      => $this->fields["closedate"],
                                                       'maybeempty' => false]);
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";
      }
      echo "</table>";

      echo "<table class='tab_cadre_fixe' id='mainformtable2'>";
      echo "<tr class='tab_bg_1'>";

      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('status');
      printf(__('%1$s%2$s'), __('Status'), $tt->getMandatoryMark('status'));
      echo $tt->getEndHiddenFieldText('status')."</th>";
      echo "<td width='$colsize2%'>";
      echo $tt->getBeginHiddenFieldValue('status');
      if ($canupdate) {
         self::dropdownStatus([
            'value'     => $this->fields["status"],
            'showtype'  => 'allowed',
            'required'  => ($tt->isMandatoryField('status') && !$ID)
         ]);
         ChangeValidation::alertValidation($this, 'status');
      } else {
         echo self::getStatus($this->fields["status"]);
         if ($this->canReopen()) {
            $link = $this->getLinkURL(). "&amp;_openfollowup=1&amp;forcetab=";
            $link .= "Change$1";
            echo "&nbsp;<a class='vsubmit' href='$link'>". __('Reopen')."</a>";
         }
      }
      echo $tt->getEndHiddenFieldValue('status', $this);

      echo "</td>";
      // Only change during creation OR when allowed to change priority OR when user is the creator

      echo "<th>".$tt->getBeginHiddenFieldText('urgency');
      printf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency'));
      echo $tt->getEndHiddenFieldText('urgency')."</th>";
      echo "<td>";

      if ($canupdate) {
         echo $tt->getBeginHiddenFieldValue('urgency');
         $idurgency = self::dropdownUrgency(['value' => $this->fields["urgency"]]);
         echo $tt->getEndHiddenFieldValue('urgency', $this);

      } else {
         $idurgency = "value_urgency".mt_rand();
         echo "<input id='$idurgency' type='hidden' name='urgency' value='".
                $this->fields["urgency"]."'>";
         echo $tt->getBeginHiddenFieldValue('urgency');
         echo self::getUrgencyName($this->fields["urgency"]);
         echo $tt->getEndHiddenFieldValue('urgency', $this);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".sprintf(__('%1$s%2$s'), __('Category'),
                                             $tt->getMandatoryMark('itilcategories_id'))."</th>";
      echo "<td >";

      // Permit to set category when creating ticket without update right
      if ($canupdate) {
         $conditions = ['is_problem' => 1];

         $opt = ['value'  => $this->fields["itilcategories_id"],
                      'entity' => $this->fields["entities_id"]];
         /// Auto submit to load template
         if (!$ID) {
            $opt['on_change'] = 'this.form.submit()';
         }
         /// if category mandatory, no empty choice
         /// no empty choice is default value set on ticket creation, else yes
         if (($ID || $options['itilcategories_id'])
             && $tt->isMandatoryField("itilcategories_id")
             && ($this->fields["itilcategories_id"] > 0)) {
            $opt['display_emptychoice'] = false;
         }

         echo "<span id='show_category_by_type'>";
         $opt['condition'] = $conditions;
         ITILCategory::dropdown($opt);
         echo "</span>";
      } else {
         echo Dropdown::getDropdownName("glpi_itilcategories", $this->fields["itilcategories_id"]);
      }
      echo "</td>";
      echo "<th>".$tt->getBeginHiddenFieldText('impact');
      printf(__('%1$s%2$s'), __('Impact'), $tt->getMandatoryMark('impact'));
      echo $tt->getEndHiddenFieldText('impact')."</th>";
      echo "</th>";
      echo "<td>";
      echo $tt->getBeginHiddenFieldValue('impact');
      if ($canupdate) {
         $idimpact = self::dropdownImpact(['value' => $this->fields["impact"], 'required' => ($tt->isMandatoryField('date') && !$ID)]);
      } else {
         $idimpact = "value_impact".mt_rand();
         echo "<input id='$idimpact' type='hidden' name='impact' value='".$this->fields["impact"]."'>";
         echo self::getImpactName($this->fields["impact"]);
      }
      echo $tt->getEndHiddenFieldValue('impact', $this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$tt->getBeginHiddenFieldText('actiontime');
      printf(__('%1$s%2$s'), __('Total duration'), $tt->getMandatoryMark('actiontime'));
      echo $tt->getEndHiddenFieldText('actiontime')."</th>";
      echo "<td>";
      echo $tt->getBeginHiddenFieldValue('actiontime');
      Dropdown::showTimeStamp(
         'actiontime', [
            'value'           => $options['actiontime'],
            'addfirstminutes' => true
         ]
      );
      echo $tt->getEndHiddenFieldValue('actiontime', $this);
      echo "</td>";
      echo "<th>".$tt->getBeginHiddenFieldText('priority');
      printf(__('%1$s%2$s'), __('Priority'), $tt->getMandatoryMark('priority'));
      echo $tt->getEndHiddenFieldText('priority')."</th>";
      echo "<td>";
      $idajax = 'change_priority_' . mt_rand();

      if (!$tt->isHiddenField('priority')) {
         $idpriority = self::dropdownPriority([
            'value'     => $this->fields["priority"],
            'withmajor' => true
         ]);
         $idpriority = 'dropdown_priority'.$idpriority;
         echo "&nbsp;<span id='$idajax' style='display:none'></span>";
      } else {
         $idpriority = 0;
         echo $tt->getBeginHiddenFieldValue('priority');
         echo "<span id='$idajax'>".self::getPriorityName($this->fields["priority"])."</span>";
         echo "<input id='$idajax' type='hidden' name='priority' value='".$this->fields["priority"]."'>";
         echo $tt->getEndHiddenFieldValue('priority', $this);
      }

      $idajax     = 'change_priority_' . mt_rand();
      echo "&nbsp;<span id='$idajax' style='display:none'></span>";
      $params = [
         'urgency'  => '__VALUE0__',
         'impact'   => '__VALUE1__',
         'priority' => 'dropdown_priority'.$idpriority
      ];
      Ajax::updateItemOnSelectEvent([
         'dropdown_urgency'.$idurgency,
         'dropdown_impact'.$idimpact],
         $idajax,
         $CFG_GLPI["root_doc"]."/ajax/priority.php",
         $params
      );
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      $this->showActorsPartForm($ID, $options);

      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th style='width:$colsize1%'>".$tt->getBeginHiddenFieldText('name');
      printf(__('%1$s%2$s'), __('Title'), $tt->getMandatoryMark('name'));
      echo $tt->getEndHiddenFieldText('name')."</th>";
      echo "<td colspan='3'>";
      echo $tt->getBeginHiddenFieldValue('name');
      echo "<input type='text' style='width:98%' maxlength=250 name='name' ".
               ($tt->isMandatoryField('name') ? " required='required'" : '') .
               " value=\"".Html::cleanInputText($this->fields["name"])."\">";
      echo $tt->getEndHiddenFieldValue('name', $this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th style='width:$colsize1%'>".$tt->getBeginHiddenFieldText('content');
      printf(__('%1$s%2$s'), __('Description'), $tt->getMandatoryMark('content'));
      echo $tt->getEndHiddenFieldText('content')."</th>";
      echo "<td colspan='3'>";

      echo $tt->getBeginHiddenFieldValue('content');
      $rand       = mt_rand();
      $rand_text  = mt_rand();
      $rows       = 10;
      $content_id = "content$rand";

      $content = $this->fields['content'];
      if (!isset($options['template_preview'])) {
         $content = Html::cleanPostForTextArea($content);
      }

      $content = Html::setRichTextContent(
         $content_id,
         $content,
         $rand,
         !$canupdate
      );

      echo "<div id='content$rand_text'>";
      if ($canupdate) {
         $uploads = [];
         if (isset($this->input['_content'])) {
            $uploads['_content'] = $this->input['_content'];
            $uploads['_tag_content'] = $this->input['_tag_content'];
         }
         Html::textarea([
            'name'            => 'content',
            'filecontainer'   => 'content_info',
            'editor_id'       => $content_id,
            'required'        => $tt->isMandatoryField('content'),
            'rows'            => $rows,
            'enable_richtext' => true,
            'value'           => $content,
            'uploads'         => $uploads,
         ]);
      } else {
         echo Toolbox::getHtmlToDisplay($content);
      }
      echo "</div>";

      echo $tt->getEndHiddenFieldValue('content', $this);

      $this->handleFileUploadField($tt, [
         'colwidth' => $colsize1
      ]);

      $options['colspan'] = 2;
      if (!$options['template_preview']) {
         if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
            echo "<input type='hidden' name='$tpl_key' value='".$tt->fields['id']."'>";
            echo "<input type='hidden' name='_predefined_fields'
                     value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
         }

         $this->showFormButtons($options);
      } else {
         echo "</table>";
         echo "</div>";
      }

      return true;

   }


   /**
    * Form to add an analysis to a problem
   **/
   function showAnalysisForm() {

      $this->check($this->getField('id'), READ);
      $canedit = $this->canEdit($this->getField('id'));

      $options            = [];
      $options['canedit'] = false;
      CommonDBTM::showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Impacts')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='impactcontent' name='impactcontent' rows='6' cols='80'>";
         echo $this->getField('impactcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('impactcontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Causes')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='causecontent' name='causecontent' rows='6' cols='80'>";
         echo $this->getField('causecontent');
         echo "</textarea>";
      } else {
         echo $this->getField('causecontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Symptoms')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='symptomcontent' name='symptomcontent' rows='6' cols='80'>";
         echo $this->getField('symptomcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('symptomcontent');
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }


   /**
    * @deprecated 9.5.0
    */
   static function getCommonSelect() {
      Toolbox::deprecated('Use getCommonCriteria with db iterator');
      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_problems`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_problems`.*,
                        `glpi_itilcategories`.`completename` AS catname
                        $SELECT";
   }


   /**
    * @deprecated 9.5.0
    */
   static function getCommonLeftJoin() {
      Toolbox::deprecated('Use getCommonCriteria with db iterator');
      $FROM = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $FROM .= " LEFT JOIN `glpi_entities`
                        ON (`glpi_entities`.`id` = `glpi_problems`.`entities_id`) ";
      }

      return " LEFT JOIN `glpi_groups_problems`
                  ON (`glpi_problems`.`id` = `glpi_groups_problems`.`problems_id`)
               LEFT JOIN `glpi_problems_users`
                  ON (`glpi_problems`.`id` = `glpi_problems_users`.`problems_id`)
               LEFT JOIN `glpi_problems_suppliers`
                  ON (`glpi_problems`.`id` = `glpi_problems_suppliers`.`problems_id`)
               LEFT JOIN `glpi_itilcategories`
                  ON (`glpi_problems`.`itilcategories_id` = `glpi_itilcategories`.`id`)
               $FROM";
   }

   /**
    * Display problems for an item
    *
    * Will also display problems of linked items
    *
    * @param CommonDBTM $item
    * @param boolean    $withtemplate
    *
    * @return void
   **/
   static function showListForItem(CommonDBTM $item, $withtemplate = 0) {
      global $DB;

      if (!Session::haveRight(self::$rightname, self::READALL)) {
         return false;
      }

      if ($item->isNewID($item->getID())) {
         return false;
      }

      $restrict = [];
      $options  = [
         'criteria' => [],
         'reset'    => 'reset',
      ];

      switch ($item->getType()) {
         case 'User' :
            $restrict['glpi_problems_users.users_id'] = $item->getID();

            $options['criteria'][0]['field']      = 4; // status
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';

            $options['criteria'][1]['field']      = 66; // status
            $options['criteria'][1]['searchtype'] = 'equals';
            $options['criteria'][1]['value']      = $item->getID();
            $options['criteria'][1]['link']       = 'OR';

            $options['criteria'][5]['field']      = 5; // status
            $options['criteria'][5]['searchtype'] = 'equals';
            $options['criteria'][5]['value']      = $item->getID();
            $options['criteria'][5]['link']       = 'OR';

            break;

         case 'Supplier' :
            $restrict['glpi_problems_suppliers.suppliers_id'] = $item->getID();

            $options['criteria'][0]['field']      = 6;
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         case 'Group' :
            // Mini search engine
            if ($item->haveChildren()) {
               $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><th>".__('Last problems')."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";
               echo __('Child groups');
               Dropdown::showYesNo('tree', $tree, -1,
                                   ['on_change' => 'reloadTab("start=0&tree="+this.value)']);
            } else {
               $tree = 0;
            }
            echo "</td></tr></table>";

            $restrict['glpi_groups_problems.groups_id'] = ($tree ? getSonsOf('glpi_groups', $item->getID()) : $item->getID());

            $options['criteria'][0]['field']      = 71;
            $options['criteria'][0]['searchtype'] = ($tree ? 'under' : 'equals');
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         default :
            $restrict['items_id'] = $item->getID();
            $restrict['itemtype'] = $item->getType();
            break;
      }

      // Link to open a new problem
      if ($item->getID()
          && Problem::isPossibleToAssignType($item->getType())
          && self::canCreate()
          && !(!empty($withtemplate) && $withtemplate == 2)
          && (!isset($item->fields['is_template']) || $item->fields['is_template'] == 0)) {
         echo "<div class='firstbloc'>";
         Html::showSimpleForm(
            Problem::getFormURL(),
            '_add_fromitem',
            __('New problem for this item...'),
            [
               '_from_itemtype' => $item->getType(),
               '_from_items_id' => $item->getID(),
               'entities_id'    => $item->fields['entities_id']
            ]
         );
         echo "</div>";
      }

      $criteria = self::getCommonCriteria();
      $criteria['WHERE'] = $restrict + getEntitiesRestrictCriteria(self::getTable());
      $criteria['LIMIT'] = (int)$_SESSION['glpilist_limit'];
      $iterator = $DB->request($criteria);
      $number = count($iterator);

      // Ticket for the item
      echo "<div><table class='tab_cadre_fixe'>";

      $colspan = 11;
      if (count($_SESSION["glpiactiveentities"]) > 1) {
         $colspan++;
      }
      if ($number > 0) {

         Session::initNavigateListItems('Problem',
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                $item->getName()));

         echo "<tr><th colspan='$colspan'>";

         //TRANS : %d is the number of problems
         echo sprintf(_n('Last %d problem', 'Last %d problems', $number), $number);
         // echo "<span class='small_space'><a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
         //         Toolbox::append_params($options,'&amp;')."'>".__('Show all')."</a></span>";

         echo "</th></tr>";

      } else {
         echo "<tr><th>".__('No problem found.')."</th></tr>";
      }
      // Ticket list
      if ($number > 0) {
         self::commonListHeader(Search::HTML_OUTPUT);

         while ($data = $iterator->next()) {
            Session::addToNavigateListItems('Problem', $data["id"]);
            self::showShort($data["id"]);
         }
         self::commonListHeader(Search::HTML_OUTPUT);
      }

      echo "</table></div>";

      // Tickets for linked items
      $linkeditems = $item->getLinkedItems();
      $restrict = [];
      if (count($linkeditems)) {
         foreach ($linkeditems as $ltype => $tab) {
            foreach ($tab as $lID) {
               $restrict[] = ['AND' => ['itemtype' => $ltype, 'items_id' => $lID]];
            }
         }
      }

      if (count($restrict)) {
         $criteria = self::getCommonCriteria();
         $criteria['WHERE'] = ['OR' => $restrict]
            + getEntitiesRestrictCriteria(self::getTable());
         $iterator = $DB->request($criteria);
         $number = count($iterator);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='$colspan'>";
         echo __('Problems on linked items');

         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            while ($data = $iterator->next()) {
               // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               self::showShort($data["id"]);
            }
            self::commonListHeader(Search::HTML_OUTPUT);
         } else {
            echo "<tr><th>".__('No problem found.')."</th></tr>";
         }
         echo "</table></div>";

      } // Subquery for linked item

   }


   /**
    * @since 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[READ]);

      $values[self::READALL] = __('See all');
      $values[self::READMY]  = __('See (author)');

      return $values;
   }

   static function getDefaultValues($entity = 0) {
      $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);
      return [
         '_users_id_requester'        => Session::getLoginUserID(),
         '_users_id_requester_notif'  => [
            'use_notification'  => $default_use_notif,
            'alternative_email' => ''
         ],
         '_groups_id_requester'       => 0,
         '_users_id_assign'           => 0,
         '_users_id_assign_notif'     => [
            'use_notification'  => $default_use_notif,
            'alternative_email' => ''],
         '_groups_id_assign'          => 0,
         '_users_id_observer'         => 0,
         '_users_id_observer_notif'   => [
            'use_notification'  => $default_use_notif,
            'alternative_email' => ''
         ],
         '_suppliers_id_assign_notif' => [
            'use_notification'  => $default_use_notif,
            'alternative_email' => ''
         ],
         '_groups_id_observer'        => 0,
         '_suppliers_id_assign'       => 0,
         'priority'                   => 3,
         'urgency'                    => 3,
         'impact'                     => 3,
         'content'                    => '',
         'name'                       => '',
         'entities_id'                => $_SESSION['glpiactive_entity'],
         'itilcategories_id'          => 0,
         'actiontime'                 => 0,
         '_add_validation'            => 0,
         'users_id_validate'          => [],
         '_tasktemplates_id'          => [],
         'items_id'                   => 0,
      ];
   }

   /**
    * get active problems for an item
    *
    * @since 9.5
    *
    * @param string $itemtype     Item type
    * @param integer $items_id    ID of the Item
    *
    * @return DBmysqlIterator
    */
   public function getActiveProblemsForItem($itemtype, $items_id) {
      global $DB;

      return $DB->request([
         'SELECT'    => [
            $this->getTable() . '.id',
            $this->getTable() . '.name',
            $this->getTable() . '.priority',
         ],
         'FROM'      => $this->getTable(),
         'LEFT JOIN' => [
            'glpi_items_problems' => [
               'ON' => [
                  'glpi_items_problems' => 'problems_id',
                  $this->getTable()    => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_items_problems.itemtype'   => $itemtype,
            'glpi_items_problems.items_id'   => $items_id,
            $this->getTable() . '.is_deleted' => 0,
            'NOT'                         => [
               $this->getTable() . '.status' => array_merge(
                  $this->getSolvedStatusArray(),
                  $this->getClosedStatusArray()
               )
            ]
         ]
      ]);
   }


   static function getIcon() {
      return "fas fa-exclamation-triangle";
   }

   public static function getItemLinkClass(): string {
      return Item_Problem::class;
   }
}
