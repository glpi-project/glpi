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
 * NotificationTargetTicket Class
 *
 * @since 0.85
**/
class NotificationTargetProject extends NotificationTarget {


   /**
    * Get events related to tickets
   **/
   function getEvents() {

      $events = ['new'               => __('New project'),
                      'update'            => __('Update of a project'),
                      'delete'            => __('Deletion of a project')];
      asort($events);
      return $events;
   }


   function addAdditionalTargets($event = '') {

      $this->addTarget(Notification::MANAGER_USER, __('Manager'));
      $this->addTarget(Notification::MANAGER_GROUP, __('Manager group'));
      $this->addTarget(Notification::MANAGER_GROUP_SUPERVISOR, __('Manager of manager group'));
      $this->addTarget(Notification::MANAGER_GROUP_WITHOUT_SUPERVISOR,
                        __("Manager group except manager users"));
      $this->addTarget(Notification::TEAM_USER, __('User of project team'));
      $this->addTarget(Notification::TEAM_GROUP, __('Group of project team'));
      $this->addTarget(Notification::TEAM_GROUP_SUPERVISOR, __('Manager of group of project team'));
      $this->addTarget(Notification::TEAM_GROUP_WITHOUT_SUPERVISOR,
                        __("Group of project team except manager users"));
      $this->addTarget(Notification::TEAM_CONTACT, __('Contact of project team'));
      $this->addTarget(Notification::TEAM_SUPPLIER, __('Supplier of project team'));
   }


   /**
    * @see NotificationTarget::addSpecificTargets()
   **/
   function addSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               case Notification::MANAGER_USER :
                  $this->addItemAuthor();
                  break;

               //Send to the manager group of the project
               case Notification::MANAGER_GROUP :
                  $this->addItemGroup();
                  break;

               //Send to the manager group supervisor of the project
               case Notification::MANAGER_GROUP_SUPERVISOR :
                  $this->addItemGroupSupervisor();
                  break;

               //Send to the manager group without supervisor of the project
               case Notification::MANAGER_GROUP_WITHOUT_SUPERVISOR :
                  $this->addItemGroupWithoutSupervisor();
                  break;

               //Send to the users in project team
               case Notification::TEAM_USER :
                  $this->addTeamUsers();
                  break;

               //Send to the groups in project team
               case Notification::TEAM_GROUP :
                  $this->addTeamGroups(0);
                  break;

               //Send to the group supervisors in project team
               case Notification::TEAM_GROUP_SUPERVISOR :
                  $this->addTeamGroups(1);
                  break;

               //Send to the group without supervisors in project team
               case Notification::TEAM_GROUP_WITHOUT_SUPERVISOR :
                  $this->addTeamGroups(2);
                  break;

               //Send to the contacts in project team
               case Notification::TEAM_CONTACT :
                  $this->addTeamContacts();
                  break;

                  //Send to the suppliers in project team
               case Notification::TEAM_SUPPLIER :
                  $this->addTeamSuppliers();
                  break;

            }
      }
   }


   /**
    * Add team users to the notified user list
    *
    * @return void
    **/
   function addTeamUsers() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'items_id',
         'FROM'   => 'glpi_projectteams',
         'WHERE'  => [
            'itemtype'     => 'User',
            'projects_id'  => $this->obj->fields['id']
         ]
      ]);
      $user = new User;
      while ($data = $iterator->next()) {
         if ($user->getFromDB($data['items_id'])) {
            $this->addToRecipientsList(['language' => $user->getField('language'),
                                            'users_id' => $user->getField('id')]);
         }
      }
   }


   /**
    * Add team groups to the notified user list
    *
    * @param integer $manager 0 all users, 1 only supervisors, 2 all users without supervisors
    *
    * @return void
    **/
   function addTeamGroups($manager) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'items_id',
         'FROM'   => 'glpi_projectteams',
         'WHERE'  => [
            'itemtype'     => 'Group',
            'projects_id'  => $this->obj->fields['id']
         ]
      ]);

      while ($data = $iterator->next()) {
         $this->addForGroup($manager, $data['items_id']);
      }
   }


   /**
    * Add team contacts to the notified user list
    *
    * @return void
    **/
   function addTeamContacts() {
      global $DB, $CFG_GLPI;

      $iterator = $DB->request([
         'SELECT' => 'items_id',
         'FROM'   => 'glpi_projectteams',
         'WHERE'  => [
            'itemtype'     => 'Contact',
            'projects_id'  => $this->obj->fields['id']
         ]
      ]);

      $contact = new Contact();
      while ($data = $iterator->next()) {
         if ($contact->getFromDB($data['items_id'])) {
            $this->addToRecipientsList(["email"    => $contact->fields["email"],
                                            "name"     => $contact->getName(),
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER]);
         }
      }
   }


   /**
    * Add team suppliers to the notified user list
    *
    * @return void
    **/
   function addTeamSuppliers() {
      global $DB, $CFG_GLPI;

      $iterator = $DB->request([
         'SELECT' => 'items_id',
         'FROM'   => 'glpi_projectteams',
         'WHERE'  => [
            'itemtype'     => 'Supplier',
            'projects_id'  => $this->obj->fields['id']
         ]
      ]);

      $supplier = new Supplier();
      while ($data = $iterator->next()) {
         if ($supplier->getFromDB($data['items_id'])) {
            $this->addToRecipientsList(["email"    => $supplier->fields["email"],
                                            "name"     => $supplier->getName(),
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER]);
         }
      }
   }


   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI, $DB;

      //----------- Reservation infos -------------- //
      $events = $this->getAllEvents();
      $item   = $this->obj;

      $this->data['##project.action##']
                  = $events[$event];
      $this->data['##project.url##']
            = $this->formatURL($options['additionnaloption']['usertype'],
                               "Project_".$item->getField("id"));
      $this->data["##project.name##"]
            = $item->getField('name');
      $this->data["##project.code##"]
            = $item->getField('code');
      $this->data["##project.description##"]
            = $item->getField('content');
      $this->data["##project.comments##"]
            = $item->getField('comment');
      $this->data["##project.creationdate##"]
            = Html::convDateTime($item->getField('date'));
      $this->data["##project.lastupdatedate##"]
            = Html::convDateTime($item->getField('date_mod'));
      $this->data["##project.priority##"]
            = CommonITILObject::getPriorityName($item->getField('priority'));
      $this->data["##project.percent##"]
            = Dropdown::getValueWithUnit($item->getField('percent_done'), "%");
      $this->data["##project.planstartdate##"]
            = Html::convDateTime($item->getField('plan_start_date'));
      $this->data["##project.planenddate##"]
            = Html::convDateTime($item->getField('plan_end_date'));
      $this->data["##project.realstartdate##"]
            = Html::convDateTime($item->getField('real_start_date'));
      $this->data["##project.realenddate##"]
            = Html::convDateTime($item->getField('real_end_date'));

      $this->data["##project.plannedduration##"]
            = Html::timestampToString(ProjectTask::getTotalPlannedDurationForProject($item->getID()),
                                      false);
      $this->data["##project.effectiveduration##"]
            = Html::timestampToString(ProjectTask::getTotalEffectiveDurationForProject($item->getID()),
                                      false);

      $entity = new Entity();
      $this->data["##project.entity##"] = '';
      $this->data["##project.shortentity##"] = '';
      if ($entity->getFromDB($this->getEntity())) {
         $this->data["##project.entity##"]      = $entity->getField('completename');
         $this->data["##project.shortentity##"] = $entity->getField('name');
      }

      $this->data["##project.father##"] = '';
      if ($item->getField('projects_id')) {
         $this->data["##project.father##"]
                              = Dropdown::getDropdownName('glpi_projects',
                                                          $item->getField('projects_id'));
      }

      $this->data["##project.state##"] = '';
      if ($item->getField('projectstates_id')) {
         $this->data["##project.state##"]
                              = Dropdown::getDropdownName('glpi_projectstates',
                                                          $item->getField('projectstates_id'));
      }

      $this->data["##project.type##"] = '';
      if ($item->getField('projecttypes_id')) {
         $this->data["##project.type##"]
                              = Dropdown::getDropdownName('glpi_projecttypes',
                                                          $item->getField('projecttypes_id'));
      }

      $this->data["##project.manager##"] = '';
      if ($item->getField('users_id')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id'));
         $this->data["##project.manager##"] = $user_tmp->getName();
      }

      $this->data["##project.managergroup##"] = '';
      if ($item->getField('groups_id')) {
         $this->data["##project.managergroup##"]
                              = Dropdown::getDropdownName('glpi_groups',
                                                          $item->getField('groups_id'));
      }
      // Team infos
      $restrict = ['projects_id' => $item->getField('id')];
      $items    = getAllDatasFromTable('glpi_projectteams', $restrict);

      $this->data['teammembers'] = [];
      if (count($items)) {
         foreach ($items as $data) {
            if ($item2 = getItemForItemtype($data['itemtype'])) {
               if ($item2->getFromDB($data['items_id'])) {
                  $tmp                            = [];
                  $tmp['##teammember.itemtype##'] = $item2->getTypeName();
                  $tmp['##teammember.name##']     = $item2->getName();
                  $this->data['teammembers'][]   = $tmp;
               }
            }
         }
      }

      $this->data['##project.numberofteammembers##'] = count($this->data['teammembers']);

      // Task infos
      $tasks                = getAllDatasFromTable('glpi_projecttasks', $restrict, false, ['date DESC', 'id ASC']);
      $this->data['tasks'] = [];
      foreach ($tasks as $task) {
         $tmp                            = [];
         $tmp['##task.creationdate##']   = Html::convDateTime($task['date']);
         $tmp['##task.lastupdatedate##'] = Html::convDateTime($task['date_mod']);
         $tmp['##task.name##']           = $task['name'];
         $tmp['##task.description##']    = $task['content'];
         $tmp['##task.comments##']       = $task['comment'];

         $tmp['##task.state##']          = Dropdown::getDropdownName('glpi_projectstates',
                                                                     $task['projectstates_id']);
         $tmp['##task.type##']           = Dropdown::getDropdownName('glpi_projecttasktypes',
                                                                     $task['projecttasktypes_id']);
         $tmp['##task.percent##']        = Dropdown::getValueWithUnit($task['percent_done'], "%");

         $this->data["##task.planstartdate##"]  = '';
         $this->data["##task.planenddate##"]    = '';
         $this->data["##task.realstartdate##"]  = '';
         $this->data["##task.realenddate##"]    = '';
         if (!is_null($task['plan_start_date'])) {
            $tmp['##task.planstartdate##']       = Html::convDateTime($task['plan_start_date']);
         }
         if (!is_null($task['plan_end_date'])) {
            $tmp['##task.planenddate##']         = Html::convDateTime($task['plan_end_date']);
         }
         if (!is_null($task['real_start_date'])) {
            $tmp['##task.realstartdate##']       = Html::convDateTime($task['real_start_date']);
         }
         if (!is_null($task['real_end_date'])) {
            $tmp['##task.realenddate##']         = Html::convDateTime($task['real_end_date']);
         }

         $this->data['tasks'][]                 = $tmp;
      }

      $this->data["##project.numberoftasks##"] = count($this->data['tasks']);

      //costs infos
      $costs                = getAllDatasFromTable('glpi_projectcosts', $restrict, false, ['begin_date DESC', 'id ASC']);
      $this->data['costs'] = [];
      $this->data["##project.totalcost##"] = 0;
      foreach ($costs as $cost) {
         $tmp = [];
         $tmp['##cost.name##']         = $cost['name'];
         $tmp['##cost.comment##']      = $cost['comment'];
         $tmp['##cost.datebegin##']    = Html::convDate($cost['begin_date']);
         $tmp['##cost.dateend##']      = Html::convDate($cost['end_date']);
         $tmp['##cost.cost##']         = Html::formatNumber($cost['cost']);
         $tmp['##cost.budget##']       = Dropdown::getDropdownName('glpi_budgets',
                                                                     $cost['budgets_id']);
         $this->data["##project.totalcost##"] += $cost['cost'];
         $this->data['costs'][]                = $tmp;

         /// TODO add ticket costs ?
      }
      $this->data["##project.numberofcosts##"] = count($this->data['costs']);

      // History infos
      $this->data['log'] = [];
      // Use list_limit_max or load the full history ?
      foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $data) {
         $tmp                            = [];
         $tmp["##project.log.date##"]    = $data['date_mod'];
         $tmp["##project.log.user##"]    = $data['user_name'];
         $tmp["##project.log.field##"]   = $data['field'];
         $tmp["##project.log.content##"] = $data['change'];
         $this->data['log'][]           = $tmp;
      }

      $this->data["##project.numberoflogs##"] = count($this->data['log']);

      // ITIL items informations
      foreach ([Change::class, Problem::class, Ticket::class] as $itemtype) {
         $values = [];

         $lc_itemtype = strtolower($itemtype);
         $restrict = [
            'projects_id' => $item->getField('id'),
            'itemtype'    => $itemtype,
         ];
         $link_items = getAllDatasFromTable(Itil_Project::getTable(), $restrict);
         if (count($link_items)) {
            $nitem = new $itemtype();
            foreach ($link_items as $data) {
               if ($nitem->getFromDB($data['items_id'])) {
                  $values[] = [
                     '##' . $lc_itemtype . '.id##'      => $data['items_id'],
                     '##' . $lc_itemtype . '.date##'    => $nitem->getField('date'),
                     '##' . $lc_itemtype . '.title##'   => $nitem->getField('name'),
                     '##' . $lc_itemtype . '.url##'     => $this->formatURL(
                        $options['additionnaloption']['usertype'],
                        $lc_itemtype . '_' . $data['items_id']
                     ),
                     '##' . $lc_itemtype . '.content##' => $nitem->getField('content')
                  ];
               }
            }
         }
         $this->data[$lc_itemtype . 's'] = $values;

         $this->data['##project.numberof' . $lc_itemtype . 's##'] = count($values);
      }

      // Document
      $iterator = $DB->request([
         'SELECT'    => 'glpi_documents.*',
         'FROM'      => 'glpi_documents',
         'LEFT JOIN' => [
            'glpi_documents_items'  => [
               'ON' => [
                  'glpi_documents_items'  => 'documents_id',
                  'glpi_documents'        => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_documents_items.itemtype'  => 'Project',
            'glpi_documents_items.items_id'  => $item->fields['id']
         ]
      ]);

      $this->data["documents"] = [];
      while ($data = $iterator->next()) {
         $tmp                       = [];
         $tmp['##document.id##']    = $data['id'];
         $tmp['##document.name##']  = $data['name'];
         $tmp['##document.weblink##']
                                    = $data['link'];

         $tmp['##document.url##']   = $this->formatURL($options['additionnaloption']['usertype'],
                                                         "document_".$data['id']);
         $downloadurl               = "/front/document.send.php?docid=".$data['id'];

         $tmp['##document.downloadurl##']
                                       = $this->formatURL($options['additionnaloption']['usertype'],
                                                         $downloadurl);
         $tmp['##document.heading##']
                                       = Dropdown::getDropdownName('glpi_documentcategories',
                                                                  $data['documentcategories_id']);

         $tmp['##document.filename##']
                                       = $data['filename'];

         $this->data['documents'][] = $tmp;
      }

      $this->data["##project.urldocument##"]
                     = $this->formatURL($options['additionnaloption']['usertype'],
                                        "Project_".$item->getField("id").'_Document_Item$1');

      $this->data["##project.numberofdocuments##"]
                     = count($this->data['documents']);

      // Items infos
      $items                = getAllDatasFromTable('glpi_items_projects', $restrict);

      $this->data['items'] = [];
      if (count($items)) {
         foreach ($items as $data) {
            if ($item2 = getItemForItemtype($data['itemtype'])) {
               if ($item2->getFromDB($data['items_id'])) {
                  $tmp                         = [];
                  $tmp['##item.itemtype##']    = $item2->getTypeName();
                  $tmp['##item.name##']        = $item2->getField('name');
                  $tmp['##item.serial##']      = $item2->getField('serial');
                  $tmp['##item.otherserial##'] = $item2->getField('otherserial');
                  $tmp['##item.contact##']     = $item2->getField('contact');
                  $tmp['##item.contactnum##']  = $item2->getField('contactnum');
                  $tmp['##item.location##']    = '';
                  $tmp['##item.user##']        = '';
                  $tmp['##item.group##']       = '';
                  $tmp['##item.model##']       = '';

                  //Object location
                  if ($item2->getField('locations_id') != NOT_AVAILABLE) {
                     $tmp['##item.location##']
                                    = Dropdown::getDropdownName('glpi_locations',
                                                                  $item2->getField('locations_id'));
                  }

                  //Object user
                  if ($item2->getField('users_id')) {
                     $user_tmp = new User();
                     if ($user_tmp->getFromDB($item2->getField('users_id'))) {
                        $tmp['##item.user##'] = $user_tmp->getName();
                     }
                  }

                  //Object group
                  if ($item2->getField('groups_id')) {
                     $tmp['##item.group##']
                                    = Dropdown::getDropdownName('glpi_groups',
                                                                  $item2->getField('groups_id'));
                  }

                  $modeltable = getSingular($item2->getTable())."models";
                  $modelfield = getForeignKeyFieldForTable($modeltable);

                  if ($item2->isField($modelfield)) {
                     $tmp['##item.model##'] = $item2->getField($modelfield);
                  }

                  $this->data['items'][] = $tmp;
               }
            }
         }
      }

      $this->data['##project.numberofitems##'] = count($this->data['items']);

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = ['project.url'                 => __('URL'),
                        'project.action'              => _n('Event', 'Events', 1),
                        'project.name'                => __('Name'),
                        'project.code'                => __('Code'),
                        'project.description'         => __('Description'),
                        'project.comments'            => __('Comments'),
                        'project.creationdate'        => __('Creation date'),
                        'project.lastupdatedate'      => __('Last update'),
                        'project.planstartdate'       => __('Planned start date'),
                        'project.planenddate'         => __('Planned end date'),
                        'project.realstartdate'       => __('Real start date'),
                        'project.realenddate'         => __('Real end date'),
                        'project.priority'            => __('Priority'),
                        'project.father'              => __('Father'),
                        'project.manager'             => __('Manager'),
                        'project.managergroup'        => __('Manager group'),
                        'project.type'                => __('Type'),
                        'project.state'               => _x('item', 'State'),
                        'project.percent'             => __('Percent done'),
                        'project.plannedduration'     => __('Planned duration'),
                        'project.effectiveduration'   => __('Effective duration'),
                        'project.numberoftasks'       => _x('quantity', 'Number of tasks'),
                        'project.numberofteammembers' => _x('quantity', 'Number of team members'),
                        'task.date'                   => __('Opening date'),
                        'task.name'                   => __('Name'),
                        'task.description'            => __('Description'),
                        'task.comments'               => __('Comments'),
                        'task.creationdate'           => __('Creation date'),
                        'task.lastupdatedate'         => __('Last update'),
                        'task.type'                   => __('Type'),
                        'task.state'                  => _x('item', 'State'),
                        'task.percent'                => __('Percent done'),
                        'task.planstartdate'          => __('Planned start date'),
                        'task.planenddate'            => __('Planned end date'),
                        'task.realstartdate'          => __('Real start date'),
                        'task.realenddate'            => __('Real end date'),
                        'project.totalcost'           => __('Total cost'),
                        'project.numberofcosts'       => __('Number of costs'),
                        'project.numberoflogs'        => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                 _x('quantity', 'Number of items')),
                        'project.log.date'            => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                 __('Date')),
                        'project.log.user'            => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                 __('User')),
                        'project.log.field'           => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                 __('Field')),
                        'project.log.content'         => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                 _x('name', 'Update')),
                        'project.numberofchanges'     => _x('quantity', 'Number of changes'),
                        'project.numberofproblems'    => _x('quantity', 'Number of problems'),
                        'project.numberoftickets'     => _x('quantity', 'Number of tickets'),
                        'project.numberofdocuments'   => _x('quantity', 'Number of documents'),
                        'item.name'                   => __('Associated item'),
                        'item.serial'                 => __('Serial number'),
                        'item.otherserial'            => __('Inventory number'),
                        'item.location'               => __('Location'),
                        'item.model'                  => __('Model'),
                        'item.contact'                => __('Alternate username'),
                        'item.contactnumber'          => __('Alternate username number'),
                        'item.user'                   => __('User'),
                        'item.group'                  => __('Group')
                     ];

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      //Tags without lang
      $tags = [
         'change.id'            => sprintf(__('%1$s: %2$s'), __('Change'), __('ID')),
         'change.date'          => sprintf(__('%1$s: %2$s'), __('Change'), __('Date')),
         'change.url'           => sprintf(__('%1$s: %2$s'), __('Change'), ('URL')),
         'change.title'         => sprintf(__('%1$s: %2$s'), __('Change'), __('Title')),
         'change.content'       => sprintf(__('%1$s: %2$s'), __('Change'), __('Description')),
         'problem.id'           => sprintf(__('%1$s: %2$s'), __('Problem'), __('ID')),
         'problem.date'         => sprintf(__('%1$s: %2$s'), __('Problem'), __('Date')),
         'problem.url'          => sprintf(__('%1$s: %2$s'), __('Problem'), ('URL')),
         'problem.title'        => sprintf(__('%1$s: %2$s'), __('Problem'), __('Title')),
         'problem.content'      => sprintf(__('%1$s: %2$s'), __('Problem'), __('Description')),
         'ticket.id'            => sprintf(__('%1$s: %2$s'), __('Ticket'), __('ID')),
         'ticket.date'          => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Date')),
         'ticket.url'           => sprintf(__('%1$s: %2$s'), __('Ticket'), ('URL')),
         'ticket.title'         => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Title')),
         'ticket.content'       => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Description')),
         'cost.name'            => sprintf(__('%1$s: %2$s'), __('Cost'), __('Name')),
         'cost.comment'         => sprintf(__('%1$s: %2$s'), __('Cost'), __('Comments')),
         'cost.datebegin'       => sprintf(__('%1$s: %2$s'), __('Cost'), __('Begin date')),
         'cost.dateend'         => sprintf(__('%1$s: %2$s'), __('Cost'), __('End date')),
         'cost.cost'            => __('Cost'),
         'cost.budget'          => sprintf(__('%1$s: %2$s'), __('Cost'), __('Budget')),
         'document.url'         => sprintf(__('%1$s: %2$s'), __('Document'), __('URL')),
         'document.downloadurl' => sprintf(__('%1$s: %2$s'), __('Document'), __('Download URL')),
         'document.heading'     => sprintf(__('%1$s: %2$s'), __('Document'), __('Heading')),
         'document.id'          => sprintf(__('%1$s: %2$s'), __('Document'), __('ID')),
         'document.filename'    => sprintf(__('%1$s: %2$s'), __('Document'), __('File')),
         'document.weblink'     => sprintf(__('%1$s: %2$s'), __('Document'), __('Web link')),
         'document.name'        => sprintf(__('%1$s: %2$s'), __('Document'), __('Name')),
         'project.urldocument'  => sprintf(
            __('%1$s: %2$s'),
            _n('Document', 'Documents', Session::getPluralNumber()), __('URL')
         ),
         'project.entity'      => sprintf(__('%1$s (%2$s)'), __('Entity'), __('Complete name')),
         'project.shortentity' => sprintf(__('%1$s (%2$s)'), __('Entity'), __('Name')),
         'teammember.name'     => sprintf(
            __('%1$s: %2$s'),
            _n('Team member', 'Team members', 1), __('Name')
         ),
         'teammember.itemtype' => sprintf(
            __('%1$s: %2$s'),
            _n('Team member', 'Team members', 1), __('Type')
         ),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false]);
      }

      //Tags with just lang
      $tags = ['project.entity'   => __('Entity'),
                    'project.log'      => __('Historical'),
                    'project.tasks'    => _n('Task', 'Tasks', Session::getPluralNumber()),
                    'project.team'     => __('Project team'),
                    'project.costs'    => _n('Cost', 'Costs', Session::getPluralNumber()),
                    'project.changes'  => _n('Change', 'Changes', Session::getPluralNumber()),
                    'project.problems' => _n('Problem', 'Problems', Session::getPluralNumber()),
                    'project.tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'project.items'    => _n('Item', 'Items', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true]);
      }

      //Foreach global tags
      $tags = ['log'         => __('Historical'),
                    'tasks'       => _n('Task', 'Tasks', Session::getPluralNumber()),
                    'costs'       => _n('Cost', 'Costs', Session::getPluralNumber()),
                    'changes'     => _n('Change', 'Changes', Session::getPluralNumber()),
                    'problems'    => _n('Problem', 'Problems', Session::getPluralNumber()),
                    'tickets'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'teammembers' => _n('Team member', 'Team members', Session::getPluralNumber()),
                    'items'       => _n('Item', 'Items', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true]);
      }
      asort($this->tag_descriptions);
   }

}
