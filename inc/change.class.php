<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * Change Class
**/
class Change extends CommonITILObject {

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = ['ChangeValidation', 'ChangeCost'];

   // From CommonITIL
   public $userlinkclass               = 'Change_User';
   public $grouplinkclass              = 'Change_Group';
   public $supplierlinkclass           = 'Change_Supplier';

   static $rightname                   = 'change';
   protected $usenotepad               = true;

   const MATRIX_FIELD                  = 'priority_matrix';
   const URGENCY_MASK_FIELD            = 'urgency_mask';
   const IMPACT_MASK_FIELD             = 'impact_mask';
   const STATUS_MATRIX_FIELD           = 'change_status';


   const READMY                        = 1;
   const READALL                       = 1024;



   static function getTypeName($nb = 0) {
      return _n('Change', 'Changes', $nb);
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
    * Is the current user have right to show the current change ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
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
    * Is the current user have right to create the current change ?
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
    * is the current user could reopen the current change
    *
    * @since 9.4.0
    *
    * @return boolean
    */
   function canReopen() {
      return Session::haveRight('followup', CREATE)
             && in_array($this->fields["status"], $this->getClosedStatusArray())
             && ($this->isAllowedStatus($this->fields['status'], self::INCOMING)
                 || $this->isAllowedStatus($this->fields['status'], self::EVALUATION));
   }


   function pre_deleteItem() {
      global $CFG_GLPI;

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
         NotificationEvent::raiseEvent('delete', $this);
      }
      return true;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($this->canAdminActors()) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_actor'] = __('Add an actor');
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'update_notif']
               = __('Set notifications for all actors');
      }

      return $actions;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $timeline    = $item->getTimelineItems();
               $nb_elements = count($timeline);

               $ong = [
                  5 => __("Processing change")." <sup class='tab_nb'>$nb_elements</sup>",
                  1 => __('Analysis'),
                  3 => __('Plans')
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

               case 3 :
                  $item->showPlanForm();
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
            break;
      }
      return true;
   }


   function defineTabs($options = []) {
      $ong = [];
      $this->defineDefaultObjectTabs($ong, $options);
      $this->addStandardTab('ChangeValidation', $ong, $options);
      $this->addStandardTab('ChangeCost', $ong, $options);
      $this->addStandardTab('Itil_Project', $ong, $options);
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('Change_Ticket', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      if ($this->hasImpactTab()) {
         $this->addStandardTab('Impact', $ong, $options);
      }
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {

      // CommonITILTask does not extends CommonDBConnexity
      $ct = new ChangeTask();
      $ct->deleteByCriteria(['changes_id' => $this->fields['id']]);

      $this->deleteChildrenAndRelationsFromDb(
         [
            // Done by parent: Change_Group::class,
            Change_Item::class,
            Change_Problem::class,
            // Done by parent: Change_Supplier::class,
            Change_Ticket::class,
            // Done by parent: Change_User::class,
            ChangeCost::class,
            ChangeValidation::class,
            // Done by parent: ITILSolution::class,
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

         // Read again change to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }
   }


   function post_addItem() {
      global $CFG_GLPI, $DB;

      parent::post_addItem();

      if (isset($this->input['_tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->input['_tickets_id'])) {
            $pt = new Change_Ticket();
            $pt->add(['tickets_id' => $this->input['_tickets_id'],
                           'changes_id' => $this->fields['id']]);

            if (!empty($ticket->fields['itemtype']) && $ticket->fields['items_id']>0) {
               $it = new Change_Item();
               $it->add(['changes_id' => $this->fields['id'],
                              'itemtype'   => $ticket->fields['itemtype'],
                              'items_id'   => $ticket->fields['items_id']]);
            }

            //Copy associated elements
            $iterator = $DB->request([
               'FROM'   => Item_Ticket::getTable(),
               'WHERE'  => [
                  'tickets_id'   => $this->input['_tickets_id']
               ]
            ]);
            $assoc = new Change_Item;
            while ($row = $iterator->next()) {
               unset($row['tickets_id']);
               unset($row['id']);
               $row['changes_id'] = $this->fields['id'];
               $assoc->add(Toolbox::addslashes_deep($row));
            }
         }
      }

      if (isset($this->input['_problems_id'])) {
         $problem = new Problem();
         if ($problem->getFromDB($this->input['_problems_id'])) {
            $cp = new Change_Problem();
            $cp->add(['problems_id' => $this->input['_problems_id'],
                           'changes_id'  => $this->fields['id']]);

            //Copy associated elements
            $iterator = $DB->request([
               'FROM'   => Item_Problem::getTable(),
               'WHERE'  => [
                  'problems_id'   => $this->input['_problems_id']
               ]
            ]);
            $assoc = new Change_Item;
            while ($row = $iterator->next()) {
               unset($row['problems_id']);
               unset($row['id']);
               $row['changes_id'] = $this->fields['id'];
               $assoc->add(Toolbox::addslashes_deep($row));
            }

         }
      }

      // Processing notifications
      if ($CFG_GLPI["use_notifications"]) {
         // Clean reload of the change
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
         $change_item = new Change_Item();
         $change_item->add([
            'items_id'      => (int)$this->input['_from_items_id'],
            'itemtype'      => $this->input['_from_itemtype'],
            'changes_id'    => $this->fields['id'],
            '_disablenotif' => true
         ]);
      }

      $this->handleItemsIdInput();
   }


   /**
    * Get default values to search engine to override
   **/
   static function getDefaultSearchRequest() {

      $search = ['criteria' => [ 0 => ['field'      => 12,
                                                      'searchtype' => 'equals',
                                                      'value'      => 'notold']],
                      'sort'     => 19,
                      'order'    => 'DESC'];

      return $search;
   }


   function rawSearchOptions() {
      $tab = [];

      $tab = array_merge($tab, $this->getSearchOptionsMain());

      $tab[] = [
         'id'                 => '68',
         'table'              => 'glpi_changes_items',
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
         'table'              => 'glpi_changes_items',
         'field'              => 'items_id',
         'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
         'datatype'           => 'specific',
         'comments'           => true,
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
         'table'              => 'glpi_changes_items',
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
         'name'               => __('Control list')
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => $this->getTable(),
         'field'              => 'impactcontent',
         'name'               => __('Analysis impact'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => $this->getTable(),
         'field'              => 'controlistcontent',
         'name'               => __('Control list'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '62',
         'table'              => $this->getTable(),
         'field'              => 'rolloutplancontent',
         'name'               => __('Deployment plan'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '63',
         'table'              => $this->getTable(),
         'field'              => 'backoutplancontent',
         'name'               => __('Backup plan'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '67',
         'table'              => $this->getTable(),
         'field'              => 'checklistcontent',
         'name'               => __('Checklist'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab = array_merge($tab, ChangeValidation::rawSearchOptionsToAdd());

      $tab = array_merge($tab, ITILFollowup::rawSearchOptionsToAdd());

      $tab = array_merge($tab, ChangeTask::rawSearchOptionsToAdd());

      $tab = array_merge($tab, $this->getSearchOptionsSolution());

      $tab = array_merge($tab, ChangeCost::rawSearchOptionsToAdd());

      return $tab;
   }


   /**
    * get the change status list
    * To be overridden by class
    *
    * @param $withmetaforsearch boolean (default false)
    *
    * @return array
   **/
   static function getAllStatusArray($withmetaforsearch = false) {

      $tab = [self::INCOMING      => _x('status', 'New'),
                   self::EVALUATION    => __('Evaluation'),
                   self::APPROVAL      => _n('Approval', 'Approvals', 1),
                   self::ACCEPTED      => _x('status', 'Accepted'),
                   self::WAITING       => __('Pending'),
                   self::TEST          => _x('change', 'Testing'),
                   self::QUALIFICATION => __('Qualification'),
                   self::SOLVED        => __('Applied'),
                   self::OBSERVED      => __('Review'),
                   self::CLOSED        => _x('status', 'Closed'),
      ];

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
      return [self::INCOMING, self::ACCEPTED, self::EVALUATION, self::APPROVAL];
   }

   /**
    * Get the ITIL object test, qualification or accepted status list
    * To be overridden by class
    *
    * @since 0.83
    *
    * @return array
   **/
   static function getProcessStatusArray() {

      // To be overridden by class
      $tab = [self::ACCEPTED, self::QUALIFICATION, self::TEST];
      return $tab;
   }


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

         if (isset($options['problems_id']) || isset($options['_problems_id'])) {
            $problems_id = $options['problems_id'] ?? $options['_problems_id'];
            $problem = new Problem();
            if ($problem->getFromDB($problems_id)) {
               $options['content']             = $problem->getField('content');
               $options['name']                = $problem->getField('name');
               $options['impact']              = $problem->getField('impact');
               $options['urgency']             = $problem->getField('urgency');
               $options['priority']            = $problem->getField('priority');
               if (isset($options['problems_id'])) {
                  //page is reloaded on category change, we only want category on the very first load
                  $options['itilcategories_id']   = $problem->getField('itilcategories_id');
               }
               $options['time_to_resolve']     = $problem->getField('time_to_resolve');
               $options['entities_id']         = $problem->getField('entities_id');
            }
         }
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE, $options);
      }

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
                      || (isset($problem) && $options[$predeffield] == $problem->getField($predeffield))
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
      if (isset($problems_id)) {
         echo "<input type='hidden' name='_problems_id' value='".$problems_id."'>";
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
         $conditions = ['is_change' => 1];

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

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldText('_add_validation');
         printf(__('%1$s%2$s'), __('Approval request'), $tt->getMandatoryMark('_add_validation'));
         echo $tt->getEndHiddenFieldText('_add_validation');
      } else {
         echo $tt->getBeginHiddenFieldText('global_validation');
         echo _n('Approval', 'Approvals', 1);
         echo $tt->getEndHiddenFieldText('global_validation');
      }
      echo "</th>";
      echo "<td>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldValue('_add_validation');

         echo "<input type='hidden' name='_add_validation' value='".
                  $options['_add_validation']."'>";

         $params = [
            'name'               => "users_id_validate",
            'entity'             => $this->fields['entities_id'],
            'users_id_validate'  => $options['users_id_validate']
         ];
         ChangeValidation::dropdownValidator($params);

         echo $tt->getEndHiddenFieldValue('_add_validation', $this);
         if ($tt->isPredefinedField('global_validation')) {
            echo "<input type='hidden' name='global_validation' value='".
                   $tt->predefined['global_validation']."'>";
         }
      } else {
         echo $tt->getBeginHiddenFieldValue('global_validation');

         if (Session::haveRightsOr('changevalidation', ChangeValidation::getCreateRights())
             && $canupdate) {
            ChangeValidation::dropdownStatus('global_validation',
                                             ['global' => true,
                                                   'value'  => $this->fields['global_validation']]);
         } else {
            echo ChangeValidation::getStatus($this->fields['global_validation']);
         }
         echo $tt->getEndHiddenFieldValue('global_validation', $this);

      }
      echo "</td>";
      echo "<th></th>";
      echo "<td></td>";
      echo "</tr>";

      if (!$options['template_preview']) {
         echo "</table>";
         $this->showActorsPartForm($ID, $options);
         echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      }

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
      echo $tt->getEndHiddenFieldText('content', $this);
      echo "</th><td colspan='3'>";

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
      echo "</td></tr>";

      $options['colspan'] = 2;

      if (!$options['template_preview']) {
         if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
            echo "<input type='hidden' name='$tpl_key' value='".$tt->fields['id']."'>";
            echo "<input type='hidden' name='_predefined_fields'
                     value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
         }
         if (!$ID) {
            $fields = [
               'controlistcontent',
               'impactcontent',
               'rolloutplancontent',
               'backoutplancontent',
               'checklistcontent'
            ];
            foreach ($fields as $field) {
               if (isset($tt->predefined[$field])) {
                  echo Html::hidden($field, ['value' => $tt->predefined[$field]]);
               }
            }
         }

         $this->showFormButtons($options);
      } else {
         echo "</table>";
         echo "</div>";
      }

      return true;

   }


   /**
    * Form to add an analysis to a change
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
         echo "<textarea id='impactcontent' name='impactcontent' rows='6' cols='110'>";
         echo $this->getField('impactcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('impactcontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Control list')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='controlistcontent' name='controlistcontent' rows='6' cols='110'>";
         echo $this->getField('controlistcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('controlistcontent');
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }

   /**
    * Form to add an analysis to a change
   **/
   function showPlanForm() {

      $this->check($this->getField('id'), READ);
      $canedit            = $this->canEdit($this->getField('id'));

      $options            = [];
      $options['canedit'] = false;
      CommonDBTM::showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Deployment plan')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='rolloutplancontent' name='rolloutplancontent' rows='6' cols='110'>";
         echo $this->getField('rolloutplancontent');
         echo "</textarea>";
      } else {
         echo $this->getField('rolloutplancontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Backup plan')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='backoutplancontent' name='backoutplancontent' rows='6' cols='110'>";
         echo $this->getField('backoutplancontent');
         echo "</textarea>";
      } else {
         echo $this->getField('backoutplancontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Checklist')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='checklistcontent' name='checklistcontent' rows='6' cols='110'>";
         echo $this->getField('checklistcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('checklistcontent');
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }


   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[READ]);

      $values[self::READALL] = __('See all');
      $values[self::READMY]  = __('See (author)');

      return $values;
   }

   /**
    * @deprecated 9.5.0
    */
   static function getCommonSelect() {
      Toolbox::deprecated('Use getCommonCriteria with db iterator');
      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_changes`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_changes`.*,
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
                        ON (`glpi_entities`.`id` = `glpi_changes`.`entities_id`) ";
      }

      return " LEFT JOIN `glpi_changes_groups`
                  ON (`glpi_changes`.`id` = `glpi_changes_groups`.`changes_id`)
               LEFT JOIN `glpi_changes_users`
                  ON (`glpi_changes`.`id` = `glpi_changes_users`.`changes_id`)
               LEFT JOIN `glpi_changes_suppliers`
                  ON (`glpi_changes`.`id` = `glpi_changes_suppliers`.`changes_id`)
               LEFT JOIN `glpi_itilcategories`
                  ON (`glpi_changes`.`itilcategories_id` = `glpi_itilcategories`.`id`)
               $FROM";
   }

   /**
    * Display changes for an item
    *
    * Will also display changes of linked items
    *
    * @param CommonDBTM      $item
    * @param boolean|integer $withtemplate
    *
    * @return boolean|void
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
            $restrict['glpi_changes_users.users_id'] = $item->getID();

            $options['criteria'][0]['field']      = 4; // status
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'OR';

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
            $restrict['glpi_changes_suppliers.suppliers_id'] = $item->getID();

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
               echo "<tr class='tab_bg_1'><th>".__('Last changes')."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";
               echo __('Child groups');
               Dropdown::showYesNo('tree', $tree, -1,
                                   ['on_change' => 'reloadTab("start=0&tree="+this.value)']);
            } else {
               $tree = 0;
            }
            echo "</td></tr></table>";

            $restrict['glpi_changes_groups.groups_id'] = ($tree ? getSonsOf('glpi_groups', $item->getID()) : $item->getID());

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

      // Link to open a new change
      if ($item->getID()
          && Change::isPossibleToAssignType($item->getType())
          && self::canCreate()
          && !(!empty($withtemplate) && $withtemplate == 2)
          && (!isset($item->fields['is_template']) || $item->fields['is_template'] == 0)) {
         echo "<div class='firstbloc'>";
         Html::showSimpleForm(
            Change::getFormURL(),
            '_add_fromitem',
            __('New change for this item...'),
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

         Session::initNavigateListItems('Change',
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                $item->getName()));

         echo "<tr><th colspan='$colspan'>";

         //TRANS : %d is the number of problems
         echo sprintf(_n('Last %d change', 'Last %d changes', $number), $number);

         echo "</th></tr>";

      } else {
         echo "<tr><th>".__('No change found.')."</th></tr>";
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
         $criteria         = self::getCommonCriteria();
         $criteria['WHERE'] = ['OR' => $restrict]
            + getEntitiesRestrictCriteria(self::getTable());
         $iterator = $DB->request($criteria);
         $number = count($iterator);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='$colspan'>";
         echo __('Changes on linked items');

         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            while ($data = $iterator->next()) {
               // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               self::showShort($data["id"]);
            }
            self::commonListHeader(Search::HTML_OUTPUT);
         } else {
            echo "<tr><th>".__('No change found.')."</th></tr>";
         }
         echo "</table></div>";

      } // Subquery for linked item

   }


   /**
    * Display debug information for current object
    *
    * @since 0.90.2
    **/
   function showDebug() {
      NotificationEvent::debugEvent($this);
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
         'entities_id'                => $_SESSION['glpiactive_entity'],
         'name'                       => '',
         'itilcategories_id'          => 0,
         'actiontime'                 => 0,
         '_add_validation'            => 0,
         'users_id_validate'          => [],
         '_tasktemplates_id'          => [],
         'controlistcontent'          => '',
         'impactcontent'              => '',
         'rolloutplancontent'         => '',
         'backoutplancontent'         => '',
         'checklistcontent'           => '',
         'items_id'                   => 0,
      ];
   }

   /**
    * Get active changes for an item
    *
    * @since 9.5
    *
    * @param string $itemtype     Item type
    * @param integer $items_id    ID of the Item
    *
    * @return DBmysqlIterator
    */
   public function getActiveChangesForItem($itemtype, $items_id) {
      global $DB;

      return $DB->request([
         'SELECT'    => [
            $this->getTable() . '.id',
            $this->getTable() . '.name',
            $this->getTable() . '.priority',
         ],
         'FROM'      => $this->getTable(),
         'LEFT JOIN' => [
            'glpi_changes_items' => [
               'ON' => [
                  'glpi_changes_items' => 'changes_id',
                  $this->getTable()    => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_changes_items.itemtype' => $itemtype,
            'glpi_changes_items.items_id'    => $items_id,
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
      return "fas fa-clipboard-check";
   }

   public static function getItemLinkClass(): string {
      return Change_Item::class;
   }
}
