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
 * Problem class
**/
class Problem extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonITIL
   public $userlinkclass        = 'Problem_User';
   public $grouplinkclass       = 'Group_Problem';
   public $supplierlinkclass    = 'Problem_Supplier';

   static $rightname            = 'problem';
   protected $usenotepad        = true;

   static protected $forward_entity_to = ['ProblemCost'];

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


   function canAdminActors() {
      return Session::haveRight(self::$rightname, UPDATE);
   }


   function canAssign() {
      return Session::haveRight(self::$rightname, UPDATE);
   }


   function canAssignToMe() {
      return Session::haveRight(self::$rightname, UPDATE);
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
         $nb = 0;
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

               case 2 :
                  if (!isset($_GET['load_kb_sol'])) {
                     $_GET['load_kb_sol'] = 0;
                  }
                  $item->showSolutions($_GET['load_kb_sol']);
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

      // show related tickets and changes
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Problem_Ticket', $ong, $options);
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('ProblemCost', $ong, $options);
      $this->addStandardTab('Itil_Project', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('Problem_Ticket', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;

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


   function prepareInputForUpdate($input) {

      // Get problem : need for comparison
      // $this->getFromDB($input['id']);

      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function pre_updateInDB() {
      parent::pre_updateInDB();
   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      $donotif = count($this->updates);

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif
          && $CFG_GLPI["use_notifications"]) {
         $mailtype = "update";

         if (isset($this->input["status"])
             && $this->input["status"]
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
      global $CFG_GLPI;

      parent::post_addItem();

      if (isset($this->input['_tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->input['_tickets_id'])) {
            $pt = new Problem_Ticket();
            $pt->add(['tickets_id'  => $this->input['_tickets_id'],
                           'problems_id' => $this->fields['id'],
                           /*'_no_notif'   => true*/]);

            if (!empty($ticket->fields['itemtype'])
                && ($ticket->fields['items_id'] > 0)) {
               $it = new Item_Problem();
               $it->add(['problems_id' => $this->fields['id'],
                              'itemtype'    => $ticket->fields['itemtype'],
                              'items_id'    => $ticket->fields['items_id'],
                              /*'_no_notif'   => true*/]);
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

      $search_users_id = " (`glpi_problems_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_problems_users`.`type` = '".CommonITILActor::REQUESTER."') ";
      $search_assign   = " (`glpi_problems_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_problems_users`.`type` = '".CommonITILActor::ASSIGN."')";
      $is_deleted      = " `glpi_problems`.`is_deleted` = 0 ";

      if ($showgroupproblems) {
         $search_users_id = " 0 = 1 ";
         $search_assign   = " 0 = 1 ";

         if (count($_SESSION['glpigroups'])) {
            $groups          = implode("','", $_SESSION['glpigroups']);
            $search_assign   = " (`glpi_groups_problems`.`groups_id` IN ('$groups')
                                  AND `glpi_groups_problems`.`type`
                                        = '".CommonITILActor::ASSIGN."')";

            $search_users_id = " (`glpi_groups_problems`.`groups_id` IN ('$groups')
                                  AND `glpi_groups_problems`.`type`
                                        = '".CommonITILActor::REQUESTER."') ";
         }
      }

      $query = "SELECT DISTINCT `glpi_problems`.`id`
                FROM `glpi_problems`
                LEFT JOIN `glpi_problems_users`
                     ON (`glpi_problems`.`id` = `glpi_problems_users`.`problems_id`)
                LEFT JOIN `glpi_groups_problems`
                     ON (`glpi_problems`.`id` = `glpi_groups_problems`.`problems_id`)";

      switch ($status) {
         case "waiting" : // on affiche les problemes en attente
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` = '".self::WAITING."' ".
                             getEntitiesRestrictRequest("AND", "glpi_problems");
            break;

         case "process" : // on affiche les problemes planifi??s ou assign??s au user
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND (`status` IN ('".self::PLANNED."','".self::ASSIGNED."')) ".
                             getEntitiesRestrictRequest("AND", "glpi_problems");
            break;

         default :
            $query .= "WHERE $is_deleted
                             AND ($search_users_id)
                             AND (`status` IN ('".self::INCOMING."',
                                               '".self::ACCEPTED."',
                                               '".self::PLANNED."',
                                               '".self::ASSIGNED."',
                                               '".self::WAITING."'))
                             AND NOT ($search_assign) ".
                             getEntitiesRestrictRequest("AND", "glpi_problems");
      }

      $query  .= " ORDER BY date_mod DESC";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      if ($_SESSION['glpidisplay_count_on_home'] > 0) {
         $query  .= " LIMIT ".intval($start).','.intval($_SESSION['glpidisplay_count_on_home']);
         $result  = $DB->query($query);
         $number  = $DB->numrows($result);
      } else {
         $number = 0;
      }

      if ($numrows > 0) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr class='noHover'><th colspan='3'>";

         $options['reset'] = 'reset';
         $forcetab         = '';
         $num              = 0;
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
                         Html::makeTitle(__('Problems on pending status'), $number, $numrows)."</a>";
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
                         Html::makeTitle(__('Problems to be processed'), $number, $numrows)."</a>";
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
                         Html::makeTitle(__('Your problems in progress'), $number, $numrows)."</a>";
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
                         Html::makeTitle(__('Problems on pending status'), $number, $numrows)."</a>";
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
                         Html::makeTitle(__('Problems to be processed'), $number, $numrows)."</a>";
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
                        Html::makeTitle(__('Your problems in progress'), $number, $numrows)."</a>";
            }
         }

         echo "</th></tr>";
         if ($number) {
            echo "<tr><th></th>";
            echo "<th>".__('Requester')."</th>";
            echo "<th>".__('Description')."</th></tr>";
            for ($i = 0; $i < $number; $i++) {
               $ID = $DB->result($result, $i, "id");
               self::showVeryShort($ID, $forcetab);
            }
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

      $query = "SELECT `status`,
                       COUNT(*) AS COUNT
                FROM `glpi_problems` ";

      if ($foruser) {
         $query .= " LEFT JOIN `glpi_problems_users`
                        ON (`glpi_problems`.`id` = `glpi_problems_users`.`problems_id`
                            AND `glpi_problems_users`.`type` = '".CommonITILActor::REQUESTER."')";

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $query .= " LEFT JOIN `glpi_groups_problems`
                           ON (`glpi_problems`.`id` = `glpi_groups_problems`.`problems_id`
                               AND `glpi_groups_problems`.`type` = '".CommonITILActor::REQUESTER."')";
         }
      }
      $query .= getEntitiesRestrictRequest("WHERE", "glpi_problems");

      if ($foruser) {
         $query .= " AND (`glpi_problems_users`.`users_id` = '".Session::getLoginUserID()."' ";

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $groups = implode(",", $_SESSION['glpigroups']);
            $query .= " OR `glpi_groups_problems`.`groups_id` IN (".$groups.") ";
         }
         $query.= ")";
      }
      $query_deleted = $query;

      $query         .= " AND `glpi_problems`.`is_deleted` = 0
                         GROUP BY `status`";
      $query_deleted .= " AND `glpi_problems`.`is_deleted` = 1
                         GROUP BY `status`";

      $result         = $DB->query($query);
      $result_deleted = $DB->query($query_deleted);

      $status = [];
      foreach (self::getAllStatusArray() as $key => $val) {
         $status[$key] = 0;
      }

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $status[$data["status"]] = $data["COUNT"];
         }
      }

      $number_deleted = 0;
      if ($DB->numrows($result_deleted) > 0) {
         while ($data = $DB->fetch_assoc($result_deleted)) {
            $number_deleted += $data["COUNT"];
         }
      }
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
      echo "<tr><th>"._n('Problem', 'Problems', Session::getPluralNumber())."</th><th>"._x('quantity', 'Number')."</th></tr>";

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
      global $CFG_GLPI;

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $viewusers = User::canView();

      $problem   = new self();
      $rand      = mt_rand();
      if ($problem->getFromDBwithData($ID, 0)) {
         $bgcolor = $_SESSION["glpipriority_".$problem->fields["priority"]];
         // $rand    = mt_rand();
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' bgcolor='$bgcolor'>".sprintf(__('%1$s: %2$s'), __('ID'),
                                                               $problem->fields["id"])."</td>";
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
      global $CFG_GLPI, $DB;

      if (!static::canView()) {
         return false;
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '37';

      $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);

      // Set default options
      if (!$ID) {
         $values = ['_users_id_requester'        => Session::getLoginUserID(),
                         '_users_id_requester_notif'  => ['use_notification'  => $default_use_notif,
                                                               'alternative_email' => ''],
                         '_groups_id_requester'       => 0,
                         '_users_id_assign'           => 0,
                         '_users_id_assign_notif'     => ['use_notification'  => $default_use_notif,
                                                               'alternative_email' => ''],
                         '_groups_id_assign'          => 0,
                         '_users_id_observer'         => 0,
                         '_users_id_observer_notif'   => ['use_notification'  => $default_use_notif,
                                                               'alternative_email' => ''],
                         '_suppliers_id_assign_notif' => ['use_notification'  => $default_use_notif,
                                                               'alternative_email' => ''],
                         '_groups_id_observer'        => 0,
                         '_suppliers_id_assign'       => 0,
                         'priority'                   => 3,
                         'urgency'                    => 3,
                         'impact'                     => 3,
                         'content'                    => '',
                         'name'                       => '',
                         'entities_id'                => $_SESSION['glpiactive_entity'],
                         'itilcategories_id'          => 0];
         foreach ($values as $key => $val) {
            if (!isset($options[$key])) {
               $options[$key] = $val;
            }
         }

         if (isset($options['tickets_id'])) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($options['tickets_id'])) {
               $options['content']             = $ticket->getField('content');
               $options['name']                = $ticket->getField('name');
               $options['impact']              = $ticket->getField('impact');
               $options['urgency']             = $ticket->getField('urgency');
               $options['priority']            = $ticket->getField('priority');
               $options['itilcategories_id']   = $ticket->getField('itilcategories_id');
               $options['time_to_resolve']     = $ticket->getField('time_to_resolve');
            }
         }
      }

      $this->initForm($ID, $options);

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
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left' width='$colsize1%'>".__('Opening date')."</th>";
      echo "<td class='left' width='$colsize2%'>";

      if (isset($options['tickets_id'])) {
         echo "<input type='hidden' name='_tickets_id' value='".$options['tickets_id']."'>";
      }

      if (isset($options['_add_fromitem'])
          && isset($options['_from_items_id'])
          && isset($options['_from_itemtype'])) {
         echo Html::hidden('_from_items_id', ['value' => $options['_from_items_id']]);
         echo Html::hidden('_from_itemtype', ['value' => $options['_from_itemtype']]);
      }

      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeField("date", ['value'      => $date,
                                            'timestep'   => 1,
                                            'maybeempty' => false]);
      echo "</td>";
      echo "<th width='$colsize1%'>".__('Time to resolve')."</th>";
      echo "<td width='$colsize2%' class='left'>";

      if ($this->fields["time_to_resolve"] == 'NULL') {
         $this->fields["time_to_resolve"] = '';
      }
      Html::showDateTimeField("time_to_resolve", ['value'    => $this->fields["time_to_resolve"],
                                                  'timestep' => 1]);

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
                                                    'timestep'   => 1,
                                                    'maybeempty' => false]);
         echo "</td>";
         if (in_array($this->fields["status"], $this->getClosedStatusArray())) {
            echo "<th>".__('Closing date')."</th>";
            echo "<td>";
            Html::showDateTimeField("closedate", ['value'      => $this->fields["closedate"],
                                                       'timestep'   => 1,
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
      echo "<th width='$colsize1%'>".__('Status')."</th>";
      echo "<td width='$colsize2%'>";
      self::dropdownStatus(['value'    => $this->fields["status"],
                                 'showtype' => 'allowed']);
      echo "</td>";
      echo "<th width='$colsize1%'>".__('Urgency')."</th>";
      echo "<td width='$colsize2%'>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgency = self::dropdownUrgency(['value' => $this->fields["urgency"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Category')."</th>";
      echo "<td >";
      $opt = [
         'value'     => $this->fields["itilcategories_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_problem' => 1]
      ];
      ITILCategory::dropdown($opt);
      echo "</td>";
      echo "<th>".__('Impact')."</th>";
      echo "<td>";
      $idimpact = self::dropdownImpact(['value' => $this->fields["impact"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Total duration')."</th>";
      echo "<td>".parent::getActionTime($this->fields["actiontime"])."</td>";
      echo "<th class='left'>".__('Priority')."</th>";
      echo "<td>";
      $idpriority = parent::dropdownPriority(['value'     => $this->fields["priority"],
                                                   'withmajor' => true]);
      $idajax     = 'change_priority_' . mt_rand();
      echo "&nbsp;<span id='$idajax' style='display:none'></span>";
      $params = ['urgency'  => '__VALUE0__',
                      'impact'   => '__VALUE1__',
                      'priority' => 'dropdown_priority'.$idpriority];
      Ajax::updateItemOnSelectEvent(['dropdown_urgency'.$idurgency,
                                          'dropdown_impact'.$idimpact],
                                    $idajax,
                                    $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      $this->showActorsPartForm($ID, $options);

      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".__('Title')."</th>";
      echo "<td colspan='3'>";
      echo "<input type='text' size='90' maxlength=250 name='name' ".
             " value=\"".Html::cleanInputText($this->fields["name"])."\">";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Description')."</th>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<textarea id='content$rand' name='content' cols='90' rows='6'>".
             Html::clean(Html::entity_decode_deep($this->fields["content"]))."</textarea>";
      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='2'  width='".($colsize1+$colsize2)."%'>";
         $docnb = Document_Item::countForItem($this);
         echo "<a href=\"".$this->getLinkURL()."&amp;forcetab=Document_Item$1\">";
         //TRANS: %d is the document number
         echo sprintf(_n('%d associated document', '%d associated documents', $docnb), $docnb);
         echo "</a></th>";
         echo "<td colspan='2'></td>";
         echo "</tr>";
      }

      $options['colspan'] = 2;
      $this->showFormButtons($options);

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
      $this->showFormHeader($options);

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



   static function getCommonSelect() {

      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_problems`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_problems`.*,
                        `glpi_itilcategories`.`completename` AS catname
                        $SELECT";
   }


   static function getCommonLeftJoin() {

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
    * @param $item CommonDBTM object
    *
    * @return nothing (display a table)
   **/
   static function showListForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight(self::$rightname, self::READALL)) {
         return false;
      }

      if ($item->isNewID($item->getID())) {
         return false;
      }

      $restrict         = '';
      $order            = '';
      $options['reset'] = 'reset';

      switch ($item->getType()) {
         case 'User' :
            $restrict   = "(`glpi_problems_users`.`users_id` = '".$item->getID()."')";
            $order      = '`glpi_problems`.`date_mod` DESC';

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
            $restrict   = "(`glpi_problems_suppliers`.`suppliers_id` = '".$item->getID()."')";
            $order      = '`glpi_problems`.`date_mod` DESC';

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

            if ($tree) {
               $restrict = "IN (".implode(',', getSonsOf('glpi_groups', $item->getID())).")";
            } else {
               $restrict = "='".$item->getID()."'";
            }
            $restrict   = "(`glpi_groups_problems`.`groups_id` $restrict)";
            $order      = '`glpi_problems`.`date_mod` DESC';

            $options['criteria'][0]['field']      = 71;
            $options['criteria'][0]['searchtype'] = ($tree ? 'under' : 'equals');
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         default :
            $restrict   = "(`items_id` = '".$item->getID()."'
                            AND `itemtype` = '".$item->getType()."')";
            $order      = '`glpi_problems`.`date_mod` DESC';
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

      $query = "SELECT ".self::getCommonSelect()."
                FROM `glpi_problems`
                LEFT JOIN `glpi_items_problems`
                  ON (`glpi_problems`.`id` = `glpi_items_problems`.`problems_id`) ".
                self::getCommonLeftJoin()."
                WHERE $restrict ".
                      getEntitiesRestrictRequest("AND", "glpi_problems")."
                ORDER BY $order
                LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

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

         while ($data = $DB->fetch_assoc($result)) {
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
               $restrict[] = "(`itemtype` = '$ltype' AND `items_id` = '$lID')";
            }
         }
      }

      if (count($restrict)) {

         $query = "SELECT ".self::getCommonSelect()."
                   FROM `glpi_problems`
                   LEFT JOIN `glpi_items_problems`
                        ON (`glpi_problems`.`id` = `glpi_items_problems`.`problems_id`) ".
                   self::getCommonLeftJoin()."
                   WHERE ".implode(' OR ', $restrict).
                         getEntitiesRestrictRequest(' AND ', 'glpi_problems') . "
                   ORDER BY `glpi_problems`.`date_mod` DESC
                   LIMIT ".intval($_SESSION['glpilist_limit']);
         $result = $DB->query($query);
         $number = $DB->numrows($result);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='$colspan'>";
         echo __('Problems on linked items');

         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            while ($data = $DB->fetch_assoc($result)) {
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
}
