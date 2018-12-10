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


class RuleTicket extends Rule {

   // From Rule
   static $rightname = 'rule_ticket';
   public $can_sort  = true;

   const PARENT  = 1024;


   const ONADD    = 1;
   const ONUPDATE = 2;

   function getTitle() {
      return __('Business rules for tickets');
   }


   function maybeRecursive() {
      return true;
   }


   function isEntityAssign() {
      return true;
   }


   function canUnrecurs() {
      return true;
   }


   function maxActionsCount() {
      return count($this->getActions());
   }


   /**
    * @since 0.85
   **/
   static function getConditionsArray() {

      return [static::ONADD                   => __('Add'),
                   static::ONUPDATE                => __('Update'),
                   static::ONADD|static::ONUPDATE  => sprintf(__('%1$s / %2$s'), __('Add'),
                                                              __('Update'))];
   }


   /**
    * display title for action form
    *
    * @since 0.84.3
   **/
   function getTitleAction() {

      parent::getTitleAction();
      $showwarning = false;
      if (isset($this->actions)) {
         foreach ($this->actions as $key => $val) {
            if (isset($val->fields['field'])) {
               if (in_array($val->fields['field'], ['impact', 'urgency'])) {
                  $showwarning = true;
               }
            }
         }
      }
      if ($showwarning) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><td>".
               __('Urgency or impact used in actions, think to add Priority: recompute action if needed.').
               "</td></tr>\n";
         echo "</table><br>";
      }
   }


   /**
    * @param $params
   **/
   function addSpecificParamsForPreview($params) {

      if (!isset($params["entities_id"])) {
         $params["entities_id"] = $_SESSION["glpiactive_entity"];
      }
      return $params;
   }


   /**
    * Function used to display type specific criterias during rule's preview
    *
    * @param $fields fields values
   **/
   function showSpecificCriteriasForPreview($fields) {

      $entity_as_criteria = false;
      foreach ($this->criterias as $criteria) {
         if ($criteria->fields['criteria'] == 'entities_id') {
            $entity_as_criteria = true;
            break;
         }
      }
      if (!$entity_as_criteria) {
         echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
      }
   }


   function executeActions($output, $params, array $input = []) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "send" :
                  //recall & recall_ola
                  $ticket = new Ticket();
                  if ($ticket->getFromDB($output['id'])) {
                     NotificationEvent::raiseEvent($action->fields['field'], $ticket);
                  }
                  break;

               case "add_validation" :
                  if (isset($output['_add_validation']) && !is_array($output['_add_validation'])) {
                     $output['_add_validation'] = [$output['_add_validation']];
                  }
                  switch ($action->fields['field']) {
                     case 'users_id_validate_requester_supervisor' :
                        $output['_add_validation'][] = 'requester_supervisor';
                        break;

                     case 'users_id_validate_assign_supervisor' :
                        $output['_add_validation'][] = 'assign_supervisor';
                        break;

                     case 'groups_id_validate' :
                        $output['_add_validation']['group'][] = $action->fields["value"];
                        break;

                     case 'users_id_validate' :
                        $output['_add_validation'][] = $action->fields["value"];
                        break;

                     case 'responsible_id_validate':
                        $output['_add_validation'][] = 'requester_responsible';
                        break;

                     case 'validation_percent' :
                        $output[$action->fields["field"]] = $action->fields["value"];
                        break;

                     default :
                        $output['_add_validation'][] = $action->fields["value"];
                        break;
                  }
                  break;

               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];

                  // Special case of users_id_requester
                  if ($action->fields["field"] === '_users_id_requester') {
                     // Add groups of requester
                     if (!isset($output['_groups_id_of_requester'])) {
                        $output['_groups_id_of_requester'] = [];
                     }
                     foreach (Group_User::getUserGroups($action->fields["value"]) as $g) {
                        $output['_groups_id_of_requester'][$g['id']] = $g['id'];
                     }
                  }

                  // Special case of slas_id_ttr & slas_id_tto & olas_id_ttr & olas_id_tto
                  if ($action->fields["field"] === 'slas_id_ttr'
                      || $action->fields["field"] === 'slas_id_tto'
                      ||$action->fields["field"] === 'olas_id_ttr'
                      || $action->fields["field"] === 'olas_id_tto') {
                     $output['_'.$action->fields["field"]] = $action->fields["value"];

                  }
                  break;

               case "append" :
                  $actions = $this->getActions();
                  $value   = $action->fields["value"];
                  if (isset($actions[$action->fields["field"]]["appendtoarray"])
                      && isset($actions[$action->fields["field"]]["appendtoarrayfield"])) {
                     $value = $actions[$action->fields["field"]]["appendtoarray"];
                     $value[$actions[$action->fields["field"]]["appendtoarrayfield"]]
                            = $action->fields["value"];
                  }
                  $output[$actions[$action->fields["field"]]["appendto"]][] = $value;

                  // Special case of users_id_requester
                  if ($action->fields["field"] === '_users_id_requester') {
                     // Add groups of requester
                     if (!isset($output['_groups_id_of_requester'])) {
                        $output['_groups_id_of_requester'] = [];
                     }
                     foreach (Group_User::getUserGroups($action->fields["value"]) as $g) {
                        $output['_groups_id_of_requester'][$g['id']] = $g['id'];
                     }
                  }

                  break;

               case 'fromuser' :
                  if (($action->fields['field'] == 'locations_id')
                      &&  isset($output['users_locations'])) {
                     $output['locations_id'] = $output['users_locations'];
                  }
                  break;

               case 'fromitem' :
                  if ($action->fields['field'] == 'locations_id' && isset($output['items_locations'])) {
                     $output['locations_id'] = $output['items_locations'];
                  }
                  if ($action->fields['field'] == '_groups_id_requester'
                      && isset($output['items_groups'])) {
                     $output['_groups_id_requester'] = $output['items_groups'];
                  }
                  break;

               case 'compute' :
                  // Value could be not set (from test)
                  $urgency = (isset($output['urgency'])?$output['urgency']:3);
                  $impact  = (isset($output['impact'])?$output['impact']:3);
                  // Apply priority_matrix from config
                  $output['priority'] = Ticket::computePriority($urgency, $impact);
                  break;

               case "affectbyip" :
               case "affectbyfqdn" :
               case "affectbymac" :
                  if (!isset($output["entities_id"])) {
                     $output["entities_id"] = $params["entities_id"];
                  }
                  if (isset($this->regex_results[0])) {
                     $regexvalue = RuleAction::getRegexResultById($action->fields["value"],
                                                                  $this->regex_results[0]);
                  } else {
                     $regexvalue = $action->fields["value"];
                  }

                  switch ($action->fields["action_type"]) {
                     case "affectbyip" :
                        $result = IPAddress::getUniqueItemByIPAddress($regexvalue,
                                                                      $output["entities_id"]);
                        break;

                     case "affectbyfqdn" :
                        $result= FQDNLabel::getUniqueItemByFQDN($regexvalue,
                                                                $output["entities_id"]);
                        break;

                     case "affectbymac" :
                        $result = NetworkPortInstantiation::getUniqueItemByMac($regexvalue,
                                                                               $output["entities_id"]);
                        break;

                     default:
                        $result = [];
                  }
                  if (!empty($result)) {
                     $output["items_id"] = [];
                     $output["items_id"][$result["itemtype"]][] = $result["id"];
                  }
                  break;
            }
         }
      }
      return $output;
   }


   /**
    * @param $output
   **/
   function preProcessPreviewResults($output) {

      $output = parent::preProcessPreviewResults($output);
      return Ticket::showPreviewAssignAction($output);
   }


   function getCriterias() {

      static $criterias = [];

      if (count($criterias)) {
         return $criterias;
      }

      $criterias['name']['table']                           = 'glpi_tickets';
      $criterias['name']['field']                           = 'name';
      $criterias['name']['name']                            = __('Title');
      $criterias['name']['linkfield']                       = 'name';

      $criterias['content']['table']                        = 'glpi_tickets';
      $criterias['content']['field']                        = 'content';
      $criterias['content']['name']                         = __('Description');
      $criterias['content']['linkfield']                    = 'content';

      $criterias['itilcategories_id']['table']              = 'glpi_itilcategories';
      $criterias['itilcategories_id']['field']              = 'name';
      $criterias['itilcategories_id']['name']               = __('Category')." - ".__('Name');
      $criterias['itilcategories_id']['linkfield']          = 'itilcategories_id';
      $criterias['itilcategories_id']['type']               = 'dropdown';

      $criterias['itilcategories_id_cn']['table']              = 'glpi_itilcategories';
      $criterias['itilcategories_id_cn']['field']              = 'completename';
      $criterias['itilcategories_id_cn']['name']               = __('Category').' - '.__('Complete name');
      $criterias['itilcategories_id_cn']['linkfield']          = 'itilcategories_id';
      $criterias['itilcategories_id_cn']['type']               = 'dropdown';

      $criterias['type']['table']                           = 'glpi_tickets';
      $criterias['type']['field']                           = 'type';
      $criterias['type']['name']                            = __('Type');
      $criterias['type']['linkfield']                       = 'type';
      $criterias['type']['type']                            = 'dropdown_tickettype';

      $criterias['_users_id_requester']['table']            = 'glpi_users';
      $criterias['_users_id_requester']['field']            = 'name';
      $criterias['_users_id_requester']['name']             = __('Requester');
      $criterias['_users_id_requester']['linkfield']        = '_users_id_requester';
      $criterias['_users_id_requester']['type']             = 'dropdown_users';
      $criterias['_users_id_requester']['linked_criteria']  = '_groups_id_of_requester';

      $criterias['_groups_id_of_requester']['table']        = 'glpi_groups';
      $criterias['_groups_id_of_requester']['field']        = 'completename';
      $criterias['_groups_id_of_requester']['name']         = __('Requester in group');
      $criterias['_groups_id_of_requester']['linkfield']    = '_groups_id_of_requester';
      $criterias['_groups_id_of_requester']['type']         = 'dropdown';

      $criterias['users_locations']['table']                = 'glpi_locations';
      $criterias['users_locations']['field']                = 'completename';
      $criterias['users_locations']['name']                 = __('Requester location');
      $criterias['users_locations']['linkfield']            = 'users_locations';
      $criterias['users_locations']['type']                 = 'dropdown';

      $criterias['items_locations']['table']                = 'glpi_locations';
      $criterias['items_locations']['field']                = 'completename';
      $criterias['items_locations']['name']                 = __('Item location');
      $criterias['items_locations']['linkfield']            = 'items_locations';
      $criterias['items_locations']['type']                 = 'dropdown';

      $criterias['items_groups']['table']                   = 'glpi_groups';
      $criterias['items_groups']['field']                   = 'completename';
      $criterias['items_groups']['name']                    = __('Item group');
      $criterias['items_groups']['linkfield']               = 'items_groups';
      $criterias['items_groups']['type']                    = 'dropdown';

      $criterias['locations_id']['table']                   = 'glpi_locations';
      $criterias['locations_id']['field']                   = 'completename';
      $criterias['locations_id']['name']                    = __('Ticket location');
      $criterias['locations_id']['linkfield']               = 'locations_id';
      $criterias['locations_id']['type']                    = 'dropdown';

      $criterias['_groups_id_requester']['table']           = 'glpi_groups';
      $criterias['_groups_id_requester']['field']           = 'completename';
      $criterias['_groups_id_requester']['name']            = __('Requester group');
      $criterias['_groups_id_requester']['linkfield']       = '_groups_id_requester';
      $criterias['_groups_id_requester']['type']            = 'dropdown';

      $criterias['_users_id_assign']['table']               = 'glpi_users';
      $criterias['_users_id_assign']['field']               = 'name';
      $criterias['_users_id_assign']['name']                = __('Technician');
      $criterias['_users_id_assign']['linkfield']           = '_users_id_assign';
      $criterias['_users_id_assign']['type']                = 'dropdown_users';

      $criterias['_groups_id_assign']['table']              = 'glpi_groups';
      $criterias['_groups_id_assign']['field']              = 'completename';
      $criterias['_groups_id_assign']['name']               = __('Technician group');
      $criterias['_groups_id_assign']['linkfield']          = '_groups_id_assign';
      $criterias['_groups_id_assign']['type']               = 'dropdown';
      $criterias['_groups_id_assign']['condition']          = '`is_assign`';

      $criterias['_suppliers_id_assign']['table']           = 'glpi_suppliers';
      $criterias['_suppliers_id_assign']['field']           = 'name';
      $criterias['_suppliers_id_assign']['name']            = __('Assigned to a supplier');
      $criterias['_suppliers_id_assign']['linkfield']       = '_suppliers_id_assign';
      $criterias['_suppliers_id_assign']['type']            = 'dropdown';

      $criterias['_users_id_observer']['table']             = 'glpi_users';
      $criterias['_users_id_observer']['field']             = 'name';
      $criterias['_users_id_observer']['name']              = __('Watcher');
      $criterias['_users_id_observer']['linkfield']         = '_users_id_observer';
      $criterias['_users_id_observer']['type']              = 'dropdown_users';

      $criterias['_groups_id_observer']['table']            = 'glpi_groups';
      $criterias['_groups_id_observer']['field']            = 'completename';
      $criterias['_groups_id_observer']['name']             = __('Watcher group');
      $criterias['_groups_id_observer']['linkfield']        = '_groups_id_observer';
      $criterias['_groups_id_observer']['type']             = 'dropdown';

      $criterias['requesttypes_id']['table']                = 'glpi_requesttypes';
      $criterias['requesttypes_id']['field']                = 'name';
      $criterias['requesttypes_id']['name']                 = __('Request source');
      $criterias['requesttypes_id']['linkfield']            = 'requesttypes_id';
      $criterias['requesttypes_id']['type']                 = 'dropdown';

      $criterias['itemtype']['table']                       = 'glpi_tickets';
      $criterias['itemtype']['field']                       = 'itemtype';
      $criterias['itemtype']['name']                        = __('Item type');
      $criterias['itemtype']['linkfield']                   = 'itemtype';
      $criterias['itemtype']['type']                        = 'dropdown_tracking_itemtype';

      $criterias['entities_id']['table']                    = 'glpi_entities';
      $criterias['entities_id']['field']                    = 'name';
      $criterias['entities_id']['name']                     = __('Entity');
      $criterias['entities_id']['linkfield']                = 'entities_id';
      $criterias['entities_id']['type']                     = 'dropdown';

      $criterias['profiles_id']['table']                    = 'glpi_profiles';
      $criterias['profiles_id']['field']                    = 'name';
      $criterias['profiles_id']['name']                     = __('Default profile');
      $criterias['profiles_id']['linkfield']                = 'profiles_id';
      $criterias['profiles_id']['type']                     = 'dropdown';

      $criterias['urgency']['name']                         = __('Urgency');
      $criterias['urgency']['type']                         = 'dropdown_urgency';

      $criterias['impact']['name']                          = __('Impact');
      $criterias['impact']['type']                          = 'dropdown_impact';

      $criterias['priority']['name']                        = __('Priority');
      $criterias['priority']['type']                        = 'dropdown_priority';

      $criterias['_mailgate']['table']                      = 'glpi_mailcollectors';
      $criterias['_mailgate']['field']                      = 'name';
      $criterias['_mailgate']['name']                       = __('Mails receiver');
      $criterias['_mailgate']['linkfield']                  = '_mailgate';
      $criterias['_mailgate']['type']                       = 'dropdown';

      $criterias['_x-priority']['name']                     = __('X-Priority email header');
      $criterias['_x-priority']['table']                    = '';
      $criterias['_x-priority']['type']                     = 'text';

      $criterias['slas_id_ttr']['table']                    = 'glpi_slas';
      $criterias['slas_id_ttr']['field']                    = 'name';
      $criterias['slas_id_ttr']['name']                     = sprintf(__('%1$s %2$s'), __('SLA'),
                                                                      __('Time to resolve'));
      $criterias['slas_id_ttr']['linkfield']                = 'slas_id_ttr';
      $criterias['slas_id_ttr']['type']                     = 'dropdown';
      $criterias['slas_id_ttr']['condition']                = "`glpi_slas`.`type` = '".SLM::TTR."'";

      $criterias['slas_id_tto']['table']                    = 'glpi_slas';
      $criterias['slas_id_tto']['field']                    = 'name';
      $criterias['slas_id_tto']['name']                     = sprintf(__('%1$s %2$s'), __('SLA'),
                                                                      __('Time to own'));
      $criterias['slas_id_tto']['linkfield']                = 'slas_id_tto';
      $criterias['slas_id_tto']['type']                     = 'dropdown';
      $criterias['slas_id_tto']['condition']                = "`glpi_slas`.`type` = '".SLM::TTO."'";

      $criterias['olas_id_ttr']['table']                    = 'glpi_olas';
      $criterias['olas_id_ttr']['field']                    = 'name';
      $criterias['olas_id_ttr']['name']                     = sprintf(__('%1$s %2$s'), __('OLA'),
                                                                      __('Time to resolve'));
      $criterias['olas_id_ttr']['linkfield']                = 'olas_id_ttr';
      $criterias['olas_id_ttr']['type']                     = 'dropdown';
      $criterias['olas_id_ttr']['condition']                = "`glpi_olas`.`type` = '".SLM::TTR."'";

      $criterias['olas_id_tto']['table']                    = 'glpi_olas';
      $criterias['olas_id_tto']['field']                    = 'name';
      $criterias['olas_id_tto']['name']                     = sprintf(__('%1$s %2$s'), __('OLA'),
                                                                      __('Time to own'));
      $criterias['olas_id_tto']['linkfield']                = 'olas_id_tto';
      $criterias['olas_id_tto']['type']                     = 'dropdown';
      $criterias['olas_id_tto']['condition']                = "`glpi_olas`.`type` = '".SLM::TTO."'";

      $criterias['_date_creation_calendars_id'] = [
         'name'            => __("Creation date is a working hour in calendar"),
         'table'           => Calendar::getTable(),
         'field'           => 'name',
         'linkfield'       => '_date_creation_calendars_id',
         'type'            => 'dropdown',
      ];

      return $criterias;
   }


   function getActions() {

      $actions                                              = [];

      $actions['itilcategories_id']['name']                 = __('Category');
      $actions['itilcategories_id']['type']                 = 'dropdown';
      $actions['itilcategories_id']['table']                = 'glpi_itilcategories';

      $actions['type']['name']                              = __('Type');
      $actions['type']['table']                             = 'glpi_tickets';
      $actions['type']['type']                              = 'dropdown_tickettype';

      $actions['_users_id_requester']['name']               = __('Requester');
      $actions['_users_id_requester']['type']               = 'dropdown_users';
      $actions['_users_id_requester']['force_actions']      = ['assign', 'append'];
      $actions['_users_id_requester']['permitseveral']      = ['append'];
      $actions['_users_id_requester']['appendto']           = '_additional_requesters';
      $actions['_users_id_requester']['appendtoarray']      = ['use_notification' => 1];
      $actions['_users_id_requester']['appendtoarrayfield'] = 'users_id';

      $actions['_groups_id_requester']['name']              = __('Requester group');
      $actions['_groups_id_requester']['type']              = 'dropdown';
      $actions['_groups_id_requester']['table']             = 'glpi_groups';
      $actions['_groups_id_requester']['condition']         = '`is_requester`';
      $actions['_groups_id_requester']['force_actions']     = ['assign', 'append', 'fromitem'];
      $actions['_groups_id_requester']['permitseveral']     = ['append'];
      $actions['_groups_id_requester']['appendto']          = '_additional_groups_requesters';

      $actions['_users_id_assign']['name']                  = __('Technician');
      $actions['_users_id_assign']['type']                  = 'dropdown_assign';
      $actions['_users_id_assign']['force_actions']         = ['assign', 'append'];
      $actions['_users_id_assign']['permitseveral']         = ['append'];
      $actions['_users_id_assign']['appendto']              = '_additional_assigns';
      $actions['_users_id_assign']['appendtoarray']         = ['use_notification' => 1];
      $actions['_users_id_assign']['appendtoarrayfield']    = 'users_id';

      $actions['_groups_id_assign']['table']                = 'glpi_groups';
      $actions['_groups_id_assign']['name']                 = __('Technician group');
      $actions['_groups_id_assign']['type']                 = 'dropdown';
      $actions['_groups_id_assign']['condition']            = '`is_assign`';
      $actions['_groups_id_assign']['force_actions']        = ['assign', 'append'];
      $actions['_groups_id_assign']['permitseveral']        = ['append'];
      $actions['_groups_id_assign']['appendto']             = '_additional_groups_assigns';

      $actions['_suppliers_id_assign']['table']             = 'glpi_suppliers';
      $actions['_suppliers_id_assign']['name']              = __('Assigned to a supplier');
      $actions['_suppliers_id_assign']['type']              = 'dropdown';
      $actions['_suppliers_id_assign']['force_actions']     = ['assign', 'append'];
      $actions['_suppliers_id_assign']['permitseveral']     = ['append'];
      $actions['_suppliers_id_assign']['appendto']          = '_additional_suppliers_assigns';
      $actions['_suppliers_id_assign']['appendtoarray']     = ['use_notification' => 1];
      $actions['_suppliers_id_assign']['appendtoarrayfield']  = 'suppliers_id';

      $actions['_users_id_observer']['name']                = __('Watcher');
      $actions['_users_id_observer']['type']                = 'dropdown_users';
      $actions['_users_id_observer']['force_actions']       = ['assign', 'append'];
      $actions['_users_id_observer']['permitseveral']       = ['append'];
      $actions['_users_id_observer']['appendto']            = '_additional_observers';
      $actions['_users_id_observer']['appendtoarray']       = ['use_notification' => 1];
      $actions['_users_id_observer']['appendtoarrayfield']  = 'users_id';

      $actions['_groups_id_observer']['table']              = 'glpi_groups';
      $actions['_groups_id_observer']['name']               = __('Watcher group');
      $actions['_groups_id_observer']['type']               = 'dropdown';
      $actions['_groups_id_observer']['condition']          = '`is_requester`';
      $actions['_groups_id_observer']['force_actions']      = ['assign', 'append'];
      $actions['_groups_id_observer']['permitseveral']      = ['append'];
      $actions['_groups_id_observer']['appendto']           = '_additional_groups_observers';

      $actions['urgency']['name']                           = __('Urgency');
      $actions['urgency']['type']                           = 'dropdown_urgency';

      $actions['impact']['name']                            = __('Impact');
      $actions['impact']['type']                            = 'dropdown_impact';

      $actions['priority']['name']                          = __('Priority');
      $actions['priority']['type']                          = 'dropdown_priority';
      $actions['priority']['force_actions']                 = ['assign', 'compute'];

      $actions['status']['name']                            = __('Status');
      $actions['status']['type']                            = 'dropdown_status';

      $actions['affectobject']['name']                      = _n('Associated element', 'Associated elements', Session::getPluralNumber());
      $actions['affectobject']['type']                      = 'text';
      $actions['affectobject']['force_actions']             = ['affectbyip', 'affectbyfqdn',
                                                                    'affectbymac'];

      $actions['slas_id_ttr']['table']                      = 'glpi_slas';
      $actions['slas_id_ttr']['field']                      = 'name';
      $actions['slas_id_ttr']['name']                       = sprintf(__('%1$s %2$s'), __('SLA'),
                                                                      __('Time to resolve'));
      $actions['slas_id_ttr']['linkfield']                  = 'slas_id_ttr';
      $actions['slas_id_ttr']['type']                       = 'dropdown';
      $actions['slas_id_ttr']['condition']                  = "`glpi_slas`.`type` = '".SLM::TTR."'";

      $actions['slas_id_tto']['table']                      = 'glpi_slas';
      $actions['slas_id_tto']['field']                      = 'name';
      $actions['slas_id_tto']['name']                       = sprintf(__('%1$s %2$s'), __('SLA'),
                                                                      __('Time to own'));
      $actions['slas_id_tto']['linkfield']                  = 'slas_id_tto';
      $actions['slas_id_tto']['type']                       = 'dropdown';
      $actions['slas_id_tto']['condition']                  = "`glpi_slas`.`type` = '".SLM::TTO."'";

      $actions['olas_id_ttr']['table']                      = 'glpi_olas';
      $actions['olas_id_ttr']['field']                      = 'name';
      $actions['olas_id_ttr']['name']                       = sprintf(__('%1$s %2$s'), __('OLA'),
                                                                      __('Time to resolve'));
      $actions['olas_id_ttr']['linkfield']                  = 'olas_id_ttr';
      $actions['olas_id_ttr']['type']                       = 'dropdown';
      $actions['olas_id_ttr']['condition']                  = "`glpi_olas`.`type` = '".SLM::TTR."'";

      $actions['olas_id_tto']['table']                      = 'glpi_olas';
      $actions['olas_id_tto']['field']                      = 'name';
      $actions['olas_id_tto']['name']                       = sprintf(__('%1$s %2$s'), __('OLA'),
                                                                      __('Time to own'));
      $actions['olas_id_tto']['linkfield']                  = 'olas_id_tto';
      $actions['olas_id_tto']['type']                       = 'dropdown';
      $actions['olas_id_tto']['condition']                  = "`glpi_olas`.`type` = '".SLM::TTO."'";

      $actions['users_id_validate']['name']                 = sprintf(__('%1$s - %2$s'),
                                                                      __('Send an approval request'),
                                                                      __('User'));
      $actions['users_id_validate']['type']                 = 'dropdown_users_validate';
      $actions['users_id_validate']['force_actions']        = ['add_validation'];

      $actions['responsible_id_validate']['name']                 = sprintf(__('%1$s - %2$s'),
                                                                      __('Send an approval request'),
                                                                      __('Responsible of the requester'));
      $actions['responsible_id_validate']['type']                 = 'yesno';
      $actions['responsible_id_validate']['force_actions']        = ['add_validation'];

      $actions['groups_id_validate']['name']                = sprintf(__('%1$s - %2$s'),
                                                                         __('Send an approval request'),
                                                                         __('Group'));
      $actions['groups_id_validate']['type']                = 'dropdown_groups_validate';
      $actions['groups_id_validate']['force_actions']       = ['add_validation'];

      $actions['validation_percent']['name']                = sprintf(__('%1$s - %2$s'),
                                                                      __('Send an approval request'),
                                                                      __('Minimum validation required'));
      $actions['validation_percent']['type']                = 'dropdown_validation_percent';

      $actions['users_id_validate_requester_supervisor']['name']
                                             = __('Approval request to requester group manager');
      $actions['users_id_validate_requester_supervisor']['type']
                                             = 'yesno';
      $actions['users_id_validate_requester_supervisor']['force_actions']
                                             = ['add_validation'];

      $actions['users_id_validate_assign_supervisor']['name']
                                             = __('Approval request to technician group manager');
      $actions['users_id_validate_assign_supervisor']['type']
                                             = 'yesno';
      $actions['users_id_validate_assign_supervisor']['force_actions']
                                             = ['add_validation'];

      $actions['locations_id']['name']                      = __('Location');
      $actions['locations_id']['type']                      = 'dropdown';
      $actions['locations_id']['table']                     = 'glpi_locations';
      $actions['locations_id']['force_actions']             = ['assign', 'fromuser', 'fromitem'];

      $actions['requesttypes_id']['name']                   = __('Request source');
      $actions['requesttypes_id']['type']                   = 'dropdown';
      $actions['requesttypes_id']['table']                  = 'glpi_requesttypes';

      return $actions;
   }


   /**
    * @since 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      //TRANS: short for : Business rules for ticket (entity parent)
      $values[self::PARENT] = ['short' => __('Parent business'),
                                    'long'  => __('Business rules for ticket (entity parent)')];

      return $values;
   }

}
