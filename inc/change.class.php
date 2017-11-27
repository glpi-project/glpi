<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
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



   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
   **/
   static function getTypeName($nb = 0) {
      return _n('Change', 'Changes', $nb);
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
    * Is the current user have right to approve solution of the current change ?
    *
    * @return boolean
   **/
   function canApprove() {

      return (($this->fields["users_id_recipient"] === Session::getLoginUserID())
              || $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])));
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


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete', $this);
      return true;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $actions = parent::getSpecificMassiveActions($checkitem);
      $isadmin = static::canUpdate();

      if ($this->canAdminActors()) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_actor'] = __('Add an actor');
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'update_notif']
               = __('Set notifications for all actors');
      }

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuOptions() {
      if (ChangeTemplate::canView()) {
         $menu['ChangeTemplate']['title']           = ChangeTemplate::getTypeName(Session::getPluralNumber());
         $menu['ChangeTemplate']['page']            = ChangeTemplate::getSearchURL(false);
         $menu['ChangeTemplate']['links']['search'] = ChangeTemplate::getSearchURL(false);
         if (ChangeTemplate::canCreate()) {
            $menu['ChangeTemplate']['links']['add'] = ChangeTemplate::getFormURL(false);
         }
         return $menu;
      }
      return false;
   }



   /**
    * @see CommonGLPI::getAdditionalMenuLinks()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuLinks() {

      $links = [];
      if (ChangeTemplate::canView()) {
         $links['template'] = ChangeTemplate::getSearchURL(false);
      }
      if (count($links)) {
         return $links;
      }
      return false;
   }



   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong = [1 => __('Analysis'),
                       3 => __('Plans')];
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

               case 3 :
                  $item->showPlanForm();
                  break;

               case 4 :
                  $item->showStats();
                  break;
            }
            break;
      }
      return true;
   }


   function defineTabs($options = []) {
      $ong = [];
      // show related tickets and changes
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('ITILSolution', $ong, $options);
      $this->addStandardTab('ChangeValidation', $ong, $options);
      $this->addStandardTab('ChangeTask', $ong, $options);
      $this->addStandardTab('ChangeCost', $ong, $options);
      $this->addStandardTab('Change_Project', $ong, $options);
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('Change_Ticket', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;

      $query1 = "DELETE
                 FROM `glpi_changetasks`
                 WHERE `changes_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $cp = new Change_Problem();
      $cp->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $ct = new Change_Ticket();
      $ct->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $cp = new Change_Project();
      $cp->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $ci = new Change_Item();
      $ci->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $cv = new ChangeValidation();
      $cv->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $cc = new ChangeCost();
      $cc->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      parent::cleanDBonPurge();
   }


   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function pre_updateInDB() {
      parent::pre_updateInDB();
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      $donotif =  count($this->updates);

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

         if (isset($this->input["status"]) && $this->input["status"]
             && in_array("status", $this->updates)
             && in_array($this->input["status"], $this->getClosedStatusArray())) {

            $mailtype = "closed";
         }

         // Read again change to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }
   }


   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);

      // Do not check mandatory on auto import (mailgates)
      if (!isset($input['_auto_import'])) {
         if (isset($input['_changetemplates_id']) && $input['_changetemplates_id']) {
            $ct = new ChangeTemplate();
            if ($ct->getFromDBWithDatas($input['_changetemplates_id'])) {
               if (count($ct->mandatory)) {
                  $mandatory_missing = [];
                  $fieldsname        = $ct->getAllowedFieldsNames(true);
                  foreach ($ct->mandatory as $key => $val) {
                     // for title if mandatory (restore initial value)
                     if ($key == 'name') {
                        $input['name']                     = $title;
                     }
                     // Check only defined values : Not defined not in form
                     if (isset($input[$key])) {
                        // If content is also predefined need to be different from predefined value
                        if (($key == 'content')
                            && isset($ct->predefined['content'])) {
                           // Clean new lines to be fix encoding
                           if (strcmp(preg_replace("/\r?\n/", "",
                                                   Html::cleanPostForTextArea($input[$key])),
                                      preg_replace("/\r?\n/", "",
                                                   $ct->predefined['content'])) == 0) {
                              $mandatory_missing[$key] = $fieldsname[$val];
                           }
                        }

                        if (empty($input[$key]) || ($input[$key] == 'NULL')
                            || (is_array($input[$key])
                                && ($input[$key] === [0 => "0"]))) {
                           $mandatory_missing[$key] = $fieldsname[$val];
                        }
                     }

                     if (($key == '_add_validation')
                         && !empty($input['users_id_validate'])
                         && isset($input['users_id_validate'][0])
                         && ($input['users_id_validate'][0] > 0)) {

                        unset($mandatory_missing['_add_validation']);
                     }

                     // For time_to_resolve and time_to_own : check also slas
                     // For internal_time_to_resolve and internal_time_to_own : check also olas
                     foreach ([SLM::TTR, SLM::TTO] as $slmType) {
                        list($dateField, $slaField) = SLA::getFieldNames($slmType);
                        if (($key == $dateField)
                            && isset($input[$slaField]) && ($input[$slaField] > 0)
                            && isset($mandatory_missing[$dateField])) {
                           unset($mandatory_missing[$dateField]);
                        }
                        list($dateField, $olaField) = OLA::getFieldNames($slmType);
                        if (($key == $dateField)
                            && isset($input[$olaField]) && ($input[$olaField] > 0)
                            && isset($mandatory_missing[$dateField])) {
                           unset($mandatory_missing[$dateField]);
                        }
                     }

                     // For document mandatory
                     if (($key == '_documents_id')
                           && !isset($input['_filename'])
                           && !isset($input['_tag_filename'])
                           && !isset($input['_stock_image'])
                           && !isset($input['_tag_stock_image'])) {

                        $mandatory_missing[$key] = $fieldsname[$val];
                     }
                  }
                  if (count($mandatory_missing)) {
                     //TRANS: %s are the fields concerned
                     $message = sprintf(__('Mandatory fields are not filled. Please correct: %s'),
                                        implode(", ", $mandatory_missing));
                     Session::addMessageAfterRedirect($message, false, ERROR);
                     return false;
                  }
               }
            }
         }
      }
      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

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
         }
      }

      if (isset($this->input['_problems_id'])) {
         $problem = new Problem();
         if ($problem->getFromDB($this->input['_problems_id'])) {
            $cp = new Change_Problem();
            $cp->add(['problems_id' => $this->input['_problems_id'],
                           'changes_id'  => $this->fields['id']]);
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


   function getSearchOptionsNew() {
      $tab = [];

      $tab = array_merge($tab, $this->getSearchOptionsMain());

      $tab = array_merge($tab, $this->getSearchOptionsActors());

      $tab[] = [
         'id'                 => 'analysis',
         'name'               => __('Control list')
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => $this->getTable(),
         'field'              => 'impactcontent',
         'name'               => __('Impact'),
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

      $tab = array_merge($tab, Notepad::getSearchOptionsToAddNew());

      $tab = array_merge($tab, ChangeValidation::getSearchOptionsToAddNew());

      $tab = array_merge($tab, ChangeTask::getSearchOptionsToAddNew());

      $tab = array_merge($tab, $this->getSearchOptionsSolution());

      $tab = array_merge($tab, ChangeCost::getSearchOptionsToAddNew());

      return $tab;
   }


   /**
    * get the change status list
    * To be overridden by class
    *
    * @param $withmetaforsearch boolean (default false)
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch = false) {

      $tab = [self::INCOMING      => _x('status', 'New'),
                   self::EVALUATION    => __('Evaluation'),
                   self::APPROVAL      => __('Approval'),
                   self::ACCEPTED      => _x('status', 'Accepted'),
                   self::WAITING       => __('Pending'),
                   self::TEST          => _x('change', 'Test'),
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
    * To be overridden by class
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getClosedStatusArray() {

      // To be overridden by class
      $tab = [self::CLOSED];
      return $tab;
   }


   /**
    * Get the ITIL object solved or observe status list
    * To be overridden by class
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getSolvedStatusArray() {
      // To be overridden by class
      $tab = [self::OBSERVED, self::SOLVED];
      return $tab;
   }

   /**
    * Get the ITIL object new status list
    *
    * @since version 0.83.8
    *
    * @return an array
   **/
   static function getNewStatusArray() {
      return [self::INCOMING, self::ACCEPTED, self::EVALUATION, self::APPROVAL];
   }

   /**
    * Get the ITIL object test, qualification or accepted status list
    * To be overridden by class
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getProcessStatusArray() {

      // To be overridden by class
      $tab = [self::ACCEPTED, self::QUALIFICATION, self::TEST];
      return $tab;
   }


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
         $default_values = ['_users_id_requester'        => Session::getLoginUserID(),
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
                         'entities_id'                => $_SESSION['glpiactive_entity'],
                         'name'                       => '',
                         'itilcategories_id'          => 0];
         // Restore saved value or override with page parameter
         $saved = $this->restoreInput();
         foreach ($default_values as $name => $value) {
            if (!isset($options[$name])) {
               if (isset($saved[$name])) {
                  $options[$name] = $saved[$name];
               } else {
                  $options[$name] = $value;
               }
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

         if (isset($options['problems_id'])) {
            $problem = new Problem();
            if ($problem->getFromDB($options['problems_id'])) {
               $options['content']             = $problem->getField('content');
               $options['name']                = $problem->getField('name');
               $options['impact']              = $problem->getField('impact');
               $options['urgency']             = $problem->getField('urgency');
               $options['priority']            = $problem->getField('priority');
               $options['itilcategories_id']   = $problem->getField('itilcategories_id');
               $options['time_to_resolve']     = $problem->getField('time_to_resolve');
            }
         }
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE, $options);
      }

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

      // Load change template if available :
      if ($ID) {
         $ct = $this->getChangeTemplateToUse($options['template_preview'],
                                             $this->fields['itilcategories_id'], $this->fields['entities_id']);
      } else {
         $ct = $this->getChangeTemplateToUse($options['template_preview'],
                                             $options['itilcategories_id'], $options['entities_id']);
      }

      // Predefined fields from template : reset them
      if (isset($options['_predefined_fields'])) {
         $options['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
      } else {
         $options['_predefined_fields'] = [];
      }

      // Store predefined fields to be able not to take into account on change template
      // Only manage predefined values on change creation
      $predefined_fields = [];
      if (!$ID) {

         if (isset($ct->predefined) && count($ct->predefined)) {
            foreach ($ct->predefined as $predeffield => $predefvalue) {
               if (isset($default_values[$predeffield])) {
                  // Is always default value : not set
                  // Set if already predefined field
                  // Set if change template change
                  if (((count($options['_predefined_fields']) == 0)
                       && ($options[$predeffield] == $default_values[$predeffield]))
                      || (isset($options['_predefined_fields'][$predeffield])
                          && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
                      || (isset($options['_changetemplates_id'])
                          && ($options['_changetemplates_id'] != $ct->getID()))
                      // user pref for requestype can't overwrite requestype from template
                      // when change category
                      || (empty($saved))) {

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
      // Put change template on $options for actors
      $options['_changetemplate'] = $ct;

      if ($options['template_preview']) {
         // Add all values to fields of changes for template preview
         foreach ($options as $key => $val) {
            if (!isset($this->fields[$key])) {
               $this->fields[$key] = $val;
            }
         }
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left' width='$colsize1%'>";
      echo $ct->getBeginHiddenFieldText('date');
      if (!$ID) {
         printf(__('%1$s%2$s'), __('Opening date'), $ct->getMandatoryMark('date'));
      } else {
         echo __('Opening date');
      }
      echo $ct->getEndHiddenFieldText('date');
      echo "</th>";
      echo "<td class='left' width='$colsize2%'>";

      if (isset($options['tickets_id'])) {
         echo "<input type='hidden' name='_tickets_id' value='".$options['tickets_id']."'>";
      }
      if (isset($options['problems_id'])) {
         echo "<input type='hidden' name='_problems_id' value='".$options['problems_id']."'>";
      }
      echo $ct->getBeginHiddenFieldValue('date');
      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeField("date", ['value'      => $date,
                                       'timestep'   => 1,
                                       'maybeempty' => false,
                                       'required'   => ($ct->isMandatoryField('date') && !$ID)]);
      echo $ct->getEndHiddenFieldValue('date', $this);
      echo "</td>";
      echo "<th width='$colsize1%'>";
      echo $ct->getBeginHiddenFieldText('time_to_resolve');
      printf(__('%1$s%2$s'), __('Time to resolve'), $ct->getMandatoryMark('time_to_resolve'));
      echo $ct->getEndHiddenFieldText('time_to_resolve');
      echo "</th>";
      echo "<td width='$colsize2%' class='left'>";
      echo $ct->getBeginHiddenFieldText('time_to_resolve');
      if ($this->fields["time_to_resolve"] == 'NULL') {
         $this->fields["time_to_resolve"] = '';
      }
      Html::showDateTimeField("time_to_resolve", ['value'    => $this->fields["time_to_resolve"],
                                                  'timestep' => 1,
                                                  'required' => $ct->isMandatoryField('time_to_resolve')]);
      echo $ct->getEndHiddenFieldText('time_to_resolve');
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
      echo "<th width='$colsize1%'>";
      echo $ct->getBeginHiddenFieldText('status');
      printf(__('%1$s%2$s'), __('Status'), $ct->getMandatoryMark('status'));
      echo $ct->getEndHiddenFieldText('status');
      echo "</th>";
      echo "<td width='$colsize2%'>";
      self::dropdownStatus(['value'    => $this->fields["status"],
                            'showtype' => 'allowed']);
      ChangeValidation::alertValidation($this, 'status');
      echo "</td>";
      echo "<th width='$colsize1%'>";
      echo $ct->getBeginHiddenFieldText('urgency');
      printf(__('%1$s%2$s'), __('Urgency'), $ct->getMandatoryMark('status'));
      echo $ct->getEndHiddenFieldText('urgency');
      echo "</th>";
      echo "<td width='$colsize2%'>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      echo $ct->getBeginHiddenFieldValue('urgency');
      $idurgency = self::dropdownUrgency(['value' => $this->fields["urgency"]]);
      echo $ct->getEndHiddenFieldValue('urgency', $this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      sprintf(__('%1$s%2$s'), __('Category'), $ct->getMandatoryMark('itilcategories_id'));
      echo "</th>";
      echo "<td >";
      $opt = ['value'  => $this->fields["itilcategories_id"],
                   'entity' => $this->fields["entities_id"],
                   'condition' => "`is_change`='1'"];
      /// Auto submit to load template
      if (!$ID) {
         $opt['on_change'] = 'this.form.submit()';
      }
      ITILCategory::dropdown($opt);
      echo "</td>";
      echo "<th>";
      echo $ct->getBeginHiddenFieldText('impact');
      printf(__('%1$s%2$s'), __('Impact'), $ct->getMandatoryMark('status'));
      echo $ct->getEndHiddenFieldText('impact', $this);
      echo "</th>";
      echo "<td>";
      echo $ct->getBeginHiddenFieldValue('impact');
      $idimpact = self::dropdownImpact(['value' => $this->fields["impact"]]);
      echo $ct->getEndHiddenFieldValue('impact', $this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo $ct->getBeginHiddenFieldText('actiontime');
      printf(__('%1$s%2$s'), __('Total duration'), $ct->getMandatoryMark('actiontime'));
      echo $ct->getEndHiddenFieldText('actiontime');
      echo "</th>";
      echo "<td>".parent::getActionTime($this->fields["actiontime"])."</td>";
      echo "<th class='left'>";
      echo $ct->getBeginHiddenFieldText('priority');
      printf(__('%1$s%2$s'), __('Priority'), $ct->getMandatoryMark('status'));
      echo $ct->getEndHiddenFieldText('priority', $this);
      echo "</th>";
      echo "<td>";
      echo $ct->getBeginHiddenFieldValue('priority');
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
      echo $ct->getEndHiddenFieldValue('priority', $this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo $ct->getBeginHiddenFieldText('global_validation');
      echo __('Approval');
      echo $ct->getEndHiddenFieldText('global_validation');
      echo "</th>";
      echo "<td>";
      echo $ct->getBeginHiddenFieldValue('global_validation');
      echo ChangeValidation::getStatus($this->fields['global_validation']);
      echo $ct->getEndHiddenFieldValue('global_validation', $this);
      echo "</td>";
      echo "<th></th>";
      echo "<td></td>";
      echo "</tr>";
      echo "</table>";

      $this->showActorsPartForm($ID, $options);

      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>";
      echo $ct->getBeginHiddenFieldText('name');
      printf(__('%1$s%2$s'), __('Title'), $ct->getMandatoryMark('name'));
      echo $ct->getEndHiddenFieldText('name');
      echo "</th>";
      echo "<td colspan='3'>";
      echo $ct->getBeginHiddenFieldValue('name');
      echo "<input type='text' size='90' maxlength=250 name='name' ".
              ($ct->isMandatoryField('name') ? " required='required'" : '') .
             " value=\"".Html::cleanInputText($this->fields["name"])."\">";
      echo $ct->getEndHiddenFieldValue('name', $this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo $ct->getBeginHiddenFieldText('content');
      printf(__('%1$s%2$s'), __('Description'), $ct->getMandatoryMark('content'));
      echo $ct->getEndHiddenFieldText('content');
      echo "</th>";
      echo "<td colspan='3'>";
      echo $ct->getBeginHiddenFieldValue('content');
      $rand = mt_rand();
      echo "<textarea id='content$rand' name='content' cols='90' rows='6'>".
            Html::clean(Html::entity_decode_deep($this->fields["content"]))."</textarea>";
      echo $ct->getEndHiddenFieldValue('content', $this);

      if ($ct->isField('id') && ($ct->fields['id'] > 0)) {
         echo "<input type='hidden' name='_changetemplates_id' value='".$ct->fields['id']."'>";
         echo "<input type='hidden' name='_predefined_fields'
                value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
      }
      echo "</td>";
      echo "</tr>";
      $options['colspan'] = 3;

      $this->showFormButtons($options);

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
      $this->showFormHeader($options);

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
      $this->showFormHeader($options);

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


   /**
    * @since version 0.85
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


   /**
    * Number of tasks of the problem
    *
    * @return followup count
   **/
   function numberOfTasks() {
      global $DB;
      // Set number of followups
      $query = "SELECT COUNT(*)
                FROM `glpi_changetasks`
                WHERE `changes_id` = '".$this->fields["id"]."'";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }

   static function getCommonSelect() {

      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_changes`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_changes`.*,
                        `glpi_itilcategories`.`completename` AS catname
                        $SELECT";
   }

   static function getCommonLeftJoin() {

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
            $restrict   = "(`glpi_changes_users`.`users_id` = '".$item->getID()."')";
            $order      = '`glpi_changes`.`date_mod` DESC';

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
            $restrict   = "(`glpi_changes_suppliers`.`suppliers_id` = '".$item->getID()."')";
            $order      = '`glpi_changes`.`date_mod` DESC';

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

            if ($tree) {
               $restrict = "IN (".implode(',', getSonsOf('glpi_groups', $item->getID())).")";
            } else {
               $restrict = "='".$item->getID()."'";
            }
            $restrict   = "(`glpi_changes_groups`.`groups_id` $restrict
                            AND `glpi_changes_groups`.`type` = ".CommonITILActor::REQUESTER.")";
            $order      = '`glpi_changes`.`date_mod` DESC';

            $options['criteria'][0]['field']      = 71;
            $options['criteria'][0]['searchtype'] = ($tree ? 'under' : 'equals');
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         default :
            $restrict   = "(`items_id` = '".$item->getID()."'
                            AND `itemtype` = '".$item->getType()."')";
            $order      = '`glpi_changes`.`date_mod` DESC';
            break;
      }

      $query = "SELECT ".self::getCommonSelect()."
                FROM `glpi_changes`
                LEFT JOIN `glpi_changes_items`
                  ON (`glpi_changes`.`id` = `glpi_changes_items`.`changes_id`) ".
                self::getCommonLeftJoin()."
                WHERE $restrict ".
                      getEntitiesRestrictRequest("AND", "glpi_changes")."
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

         Session::initNavigateListItems('Change',
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                $item->getName()));

         echo "<tr><th colspan='$colspan'>";

         //TRANS : %d is the number of problems
         echo sprintf(_n('Last %d change', 'Last %d changes', $number), $number);
         // echo "<span class='small_space'><a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
         //            Toolbox::append_params($options,'&amp;')."'>".__('Show all')."</a></span>";

         echo "</th></tr>";

      } else {
         echo "<tr><th>".__('No change found.')."</th></tr>";
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
                   FROM `glpi_changes`
                   LEFT JOIN `glpi_changes_items`
                        ON (`glpi_changes`.`id` = `glpi_changes_items`.`changes_id`) ".
                   self::getCommonLeftJoin()."
                   WHERE ".implode(' OR ', $restrict).
                         getEntitiesRestrictRequest(' AND ', 'glpi_changes') . "
                   ORDER BY `glpi_changes`.`date_mod` DESC
                   LIMIT ".intval($_SESSION['glpilist_limit']);
         $result = $DB->query($query);
         $number = $DB->numrows($result);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='$colspan'>";
         echo __('Changes on linked items');

         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            while ($data = $DB->fetch_assoc($result)) {
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
    * @since version 0.90.2
    **/
   function showDebug() {
      NotificationEvent::debugEvent($this);
   }


   /**
    * Get change template to use
    * Use force_template first, then try on template define for type and category
    * then use default template of active profile of connected user and then use default entity one
    *
    * @param $force_template      integer changetemplate_id to used (case of preview for example)
    *                             (default 0)
    * @param $itilcategories_id   integer change category (default 0)
    * @param $entities_id         integer (default -1)
    *
    * @since version 9.3
    *
    * @return change template object
   **/
   function getChangeTemplateToUse($force_template = 0, $itilcategories_id = 0,
                                   $entities_id = -1) {

      // Load change template if available :
      $ct              = new ChangeTemplate();
      $template_loaded = false;

      if ($force_template) {
         // with category
         if ($ct->getFromDBWithDatas($force_template, true)) {
            $template_loaded = true;
         }
      }

      if (!$template_loaded
          && $itilcategories_id) {

         $categ = new ITILCategory();
         if ($categ->getFromDB($itilcategories_id)) {
            $field = 'changetemplates_id';

            if (!empty($field) && $categ->fields[$field]) {
               // without category
               if ($ct->getFromDBWithDatas($categ->fields[$field], false)) {
                  $template_loaded = true;
               }
            }
         }
      }

      // If template loaded from category do not check after
      if ($template_loaded) {
         return $ct;
      }

      if (!$template_loaded) {
         // load default profile one if not already loaded
         if (isset($_SESSION['glpiactiveprofile']['changetemplates_id'])
             && $_SESSION['glpiactiveprofile']['changetemplates_id']) {
            // with category
            if ($ct->getFromDBWithDatas($_SESSION['glpiactiveprofile']['changetemplates_id'],
                                        true)) {
               $template_loaded = true;
            }
         }
      }

      if (!$template_loaded
          && ($entities_id >= 0)) {

         // load default entity one if not already loaded
         if ($template_id = Entity::getUsedConfig('changetemplates_id', $entities_id)) {
            // with category
            if ($ct->getFromDBWithDatas($template_id, true)) {
               $template_loaded = true;
            }
         }
      }

      // Check if profile / entity set category and try to load template for these values
      if ($template_loaded) { // template loaded for profile or entity
         $newitilcategories_id = $itilcategories_id;
         // Get predefined values for change template
         if (isset($ct->predefined['itilcategories_id']) && $ct->predefined['itilcategories_id']) {
            $newitilcategories_id = $ct->predefined['itilcategories_id'];
         }
         if ($newitilcategories_id) {

            $categ = new ITILCategory();
            if ($categ->getFromDB($newitilcategories_id)) {
               $field = 'changetemplates_id';

               if (!empty($field) && $categ->fields[$field]) {
                  // without category
                  if ($ct->getFromDBWithDatas($categ->fields[$field], false)) {
                     $template_loaded = true;
                  }
               }
            }
         }
      }
      return $ct;
   }

}
