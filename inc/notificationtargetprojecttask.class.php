<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * NotificationTargetTicket Class
 *
 * @since version 0.85
**/
class NotificationTargetProjectTask extends NotificationTarget {


   /**
    *Get events related to tickets
   **/
   function getEvents() {

      $events = array('new'               => __('New project task'),
                      'update'            => __('Update of a project task'),
                      'delete'            => __('Deletion of a project task'));
      asort($events);
      return $events;
   }


   /**
    * @see NotificationTarget::getAdditionalTargets()
   **/
   function getAdditionalTargets($event='') {

      $this->addTarget(Notification::TEAM_USER, __('Project team user'));
      $this->addTarget(Notification::TEAM_GROUP,  __('Project team group'));
      $this->addTarget(Notification::TEAM_GROUP_SUPERVISOR, __('Manager of group of project team'));
      $this->addTarget(Notification::TEAM_GROUP_WITHOUT_SUPERVISOR,
                        __("Group of project team except manager users"));
      $this->addTarget(Notification::TEAM_CONTACT, __('Contact of project team'));
      $this->addTarget(Notification::TEAM_SUPPLIER, __('Supplier of project team'));
   }


   /**
    * @see NotificationTarget::getSpecificTargets()
   **/
   function getSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               //Send to the users in project team
               case Notification::TEAM_USER :
                  $this->getTeamUsers();
                  break;

               //Send to the groups in project team
               case Notification::TEAM_GROUP :
                  $this->getTeamGroups(0);
                  break;

               //Send to the groups supervisors in project team
               case Notification::TEAM_GROUP_SUPERVISOR :
                  $this->getTeamGroups(1);
                  break;

               //Send to the groups without supervisors in project team
               case Notification::TEAM_GROUP_WITHOUT_SUPERVISOR :
                  $this->getTeamGroups(2);
                  break;

               //Send to the contacts in project team
               case Notification::TEAM_CONTACT :
                  $this->getTeamContacts();
                  break;

                  //Send to the suppliers in project team
               case Notification::TEAM_SUPPLIER :
                  $this->getTeamSuppliers();
                  break;

            }
         }
   }


   /**
    * Add team users to the notified user list
   **/
   function getTeamUsers() {
      global $DB;

      $query = "SELECT `items_id`
                FROM `glpi_projecttaskteams`
                WHERE `glpi_projecttaskteams`.`itemtype` = 'User'
                      AND `glpi_projecttaskteams`.`projecttasks_id` = '".$this->obj->fields["id"]."'";
      $user = new User;
      foreach ($DB->request($query) as $data) {
         if ($user->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array('language' => $user->getField('language'),
                                            'users_id' => $user->getField('id')));
         }
      }
   }


   /**
    * Add team groups to the notified user list
    *
    * @param $manager      0 all users, 1 only supervisors, 2 all users without supervisors
   **/
   function getTeamGroups($manager) {
      global $DB;

      $query = "SELECT `items_id`
                FROM `glpi_projecttaskteams`
                WHERE `glpi_projecttaskteams`.`itemtype` = 'Group'
                      AND `glpi_projecttaskteams`.`projecttasks_id` = '".$this->obj->fields["id"]."'";
      foreach ($DB->request($query) as $data) {
         $this->getAddressesByGroup($manager, $data['items_id']);
      }
   }


   /**
    * Add team contacts to the notified user list
   **/
   function getTeamContacts() {
      global $DB, $CFG_GLPI;

      $query = "SELECT `items_id`
                FROM `glpi_projecttaskteams`
                WHERE `glpi_projecttaskteams`.`itemtype` = 'Contact'
                      AND `glpi_projecttaskteams`.`projecttasks_id` = '".$this->obj->fields["id"]."'";
      $contact = new Contact();
      foreach ($DB->request($query) as $data) {
         if ($contact->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array("email"    => $contact->fields["email"],
                                            "name"     => $contact->getName(),
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER));
         }
      }
   }


   /**
    * Add team suppliers to the notified user list
   **/
   function getTeamSuppliers() {
      global $DB, $CFG_GLPI;

      $query = "SELECT `items_id`
                FROM `glpi_projecttaskteams`
                WHERE `glpi_projecttaskteams`.`itemtype` = 'Supplier'
                      AND `glpi_projecttaskteams`.`projecttasks_id` = '".$this->obj->fields["id"]."'";
      $supplier = new Supplier();
      foreach ($DB->request($query) as $data) {
         if ($supplier->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array("email"    => $supplier->fields["email"],
                                            "name"     => $supplier->getName(),
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER));
         }
      }
   }


   /**
    * @see NotificationTarget::getDatasForTemplate()
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $CFG_GLPI, $DB;

      //----------- Reservation infos -------------- //
      $events     = $this->getAllEvents();
      $item       = $this->obj;

      $this->datas['##projecttask.action##']
                  = $events[$event];
      $this->datas['##projecttask.url##']
                  = $this->formatURL($options['additionnaloption']['usertype'],
                                     "ProjectTask_".$item->getField("id"));
      $this->datas["##projecttask.name##"]
                  = $item->getField('name');
      $this->datas["##projecttask.project##"]
                  = Dropdown::getDropdownName('glpi_projects', $item->getField('projects_id'));
      $this->datas["##projecttask.projecturl##"]
                  = $this->formatURL($options['additionnaloption']['usertype'],
                                     "Project_".$item->getField("projects_id"));
      $this->datas["##projecttask.description##"]
                  = $item->getField('content');
      $this->datas["##projecttask.comments##"]
                  = $item->getField('comment');
      $this->datas["##projecttask.creationdate##"]
                  = Html::convDateTime($item->getField('date'));
      $this->datas["##projecttask.lastupdatedate##"]
                  = Html::convDateTime($item->getField('date_mod'));
      $this->datas["##projecttask.percent##"]
                  = Dropdown::getValueWithUnit($item->getField('percent_done'),"%");
      $this->datas["##projecttask.planstartdate##"]
                  = Html::convDateTime($item->getField('plan_start_date'));
      $this->datas["##projecttask.planenddate##"]
                  = Html::convDateTime($item->getField('plan_end_date'));
      $this->datas["##projecttask.realstartdate##"]
                  = Html::convDateTime($item->getField('real_start_date'));
      $this->datas["##projecttask.realenddate##"]
                  = Html::convDateTime($item->getField('real_end_date'));

      $this->datas["##projecttask.plannedduration##"]
                  = Html::timestampToString($item->getField('planned_duration'), false);
      $this->datas["##projecttask.effectiveduration##"]
                  = Html::timestampToString($item->getField('effective_duration'), false);
      $ticket_duration
                  = ProjectTask_Ticket::getTicketsTotalActionTime($item->getID());
      $this->datas["##projecttask.ticketsduration##"]
                  = Html::timestampToString($ticket_duration, false);
      $this->datas["##projecttask.totalduration##"]
                  = Html::timestampToString($ticket_duration+$item->getField('effective_duration'),
                                            false);


      $entity = new Entity();
      $this->datas["##projecttask.entity##"] = '';
      $this->datas["##projecttask.shortentity##"] = '';
      if ($entity->getFromDB($this->getEntity())) {
         $this->datas["##projecttask.entity##"]      = $entity->getField('completename');
         $this->datas["##projecttask.shortentity##"] = $entity->getField('name');
      }

      $this->datas["##projecttask.father##"] = '';
      if ($item->getField('projecttasks_id')) {
         $this->datas["##projecttask.father##"]
                              = Dropdown::getDropdownName('glpi_projecttasks',
                                                          $item->getField('projecttasks_id'));
      }

      $this->datas["##projecttask.state##"] = '';
      if ($item->getField('projectstates_id')) {
         $this->datas["##projecttask.state##"]
                              = Dropdown::getDropdownName('glpi_projectstates',
                                                          $item->getField('projectstates_id'));
      }

      $this->datas["##projecttask.type##"] = '';
      if ($item->getField('projecttasktypes_id')) {
         $this->datas["##projecttask.type##"]
                              = Dropdown::getDropdownName('glpi_projecttasktypes',
                                                          $item->getField('projecttasktypes_id'));
      }

      $this->datas["##projecttask.createbyuser##"] = '';
      if ($item->getField('users_id')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id'));
         $this->datas["##projecttask.createbyuser##"] = $user_tmp->getName();
      }


      // Team infos
      $restrict = "`projecttasks_id` = '".$item->getField('id')."'";
      $items    = getAllDatasFromTable('glpi_projecttaskteams',$restrict);

      $this->datas['teammembers'] = array();
      if (count($items)) {
         foreach ($items as $data) {
            if ($item2 = getItemForItemtype($data['itemtype'])) {
               if ($item2->getFromDB($data['items_id'])) {
                  $tmp                               = array();
                  $tmp['##teammember.itemtype##']    = $item2->getTypeName();
                  $tmp['##teammember.name##']        = $item2->getName();
                  $this->datas['teammembers'][]      = $tmp;
               }
            }
         }
      }

      $this->datas['##projecttask.numberofteammembers##'] = count($this->datas['teammembers']);

      // Task infos
      $restrict             = "`projecttasks_id`='".$item->getField('id')."'";
      $restrict            .= " ORDER BY `date` DESC, `id` ASC";

      $tasks                = getAllDatasFromTable('glpi_projecttasks',$restrict);
      $this->datas['tasks'] = array();
      foreach ($tasks as $task) {
         $tmp                            = array();
         $tmp['##task.creationdate##']   = Html::convDateTime($task['date']);
         $tmp['##task.lastupdatedate##'] = Html::convDateTime($task['date_mod']);
         $tmp['##task.name##']           = $task['name'];
         $tmp['##task.description##']    = $task['content'];
         $tmp['##task.comments##']       = $task['comment'];

         $tmp['##task.state##']          = Dropdown::getDropdownName('glpi_projectstates',
                                                                     $task['projectstates_id']);
         $tmp['##task.type##']           = Dropdown::getDropdownName('glpi_projecttasktypes',
                                                                     $task['projecttasktypes_id']);
         $tmp['##task.percent##']        = Dropdown::getValueWithUnit($task['percent_done'],"%");

         $this->datas["##task.planstartdate##"]    = '';
         $this->datas["##task.planenddate##"]      = '';
         $this->datas["##task.realstartdate##"]    = '';
         $this->datas["##task.realenddate##"]      = '';
         if (!is_null($task['plan_start_date'])) {
            $tmp['##task.planstartdate##']         = Html::convDateTime($task['plan_start_date']);
         }
         if (!is_null($task['plan_end_date'])) {
            $tmp['##task.planenddate##']           = Html::convDateTime($task['plan_end_date']);
         }
         if (!is_null($task['real_start_date'])) {
            $tmp['##task.realstartdate##']         = Html::convDateTime($task['real_start_date']);
         }
         if (!is_null($task['real_end_date'])) {
            $tmp['##task.realenddate##']           = Html::convDateTime($task['real_end_date']);
         }

         $this->datas['tasks'][]                   = $tmp;
      }

      $this->datas["##projecttask.numberoftasks##"] = count($this->datas['tasks']);


      // History infos

      $this->datas['log'] = array();
      // Use list_limit_max or load the full history ?
      foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $data) {
         $tmp                                = array();
         $tmp["##projecttask.log.date##"]    = $data['date_mod'];
         $tmp["##projecttask.log.user##"]    = $data['user_name'];
         $tmp["##projecttask.log.field##"]   = $data['field'];
         $tmp["##projecttask.log.content##"] = $data['change'];
         $this->datas['log'][]               = $tmp;
      }

      $this->datas["##projecttask.numberoflogs##"] = count($this->datas['log']);

      // Tickets infos
      $restrict = "`projecttasks_id`='".$item->getField('id')."'";
      $tickets  = getAllDatasFromTable('glpi_projecttasks_tickets', $restrict);

      $this->datas['tickets'] = array();
      if (count($tickets)) {
         $ticket = new Ticket();
         foreach ($tickets as $data) {
            if ($ticket->getFromDB($data['tickets_id'])) {
               $tmp                    = array();

               $tmp['##ticket.id##']   = $data['tickets_id'];
               $tmp['##ticket.date##'] = $ticket->getField('date');
               $tmp['##ticket.title##']
                                       = $ticket->getField('name');
               $tmp['##ticket.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                          "Ticket_".$data['tickets_id']);
               $tmp['##ticket.content##']
                                       = $ticket->getField('content');

               $this->datas['tickets'][] = $tmp;
            }
         }
      }

      $this->datas['##projecttask.numberoftickets##'] = count($this->datas['tickets']);


      // Document
      $query = "SELECT `glpi_documents`.*
                FROM `glpi_documents`
                LEFT JOIN `glpi_documents_items`
                  ON (`glpi_documents`.`id` = `glpi_documents_items`.`documents_id`)
                WHERE `glpi_documents_items`.`itemtype` =  'ProjectTask'
                      AND `glpi_documents_items`.`items_id` = '".$item->getField('id')."'";


      $this->datas["documents"] = array();
      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
            $tmp                      = array();
            $tmp['##document.id##']   = $data['id'];
            $tmp['##document.name##'] = $data['name'];
            $tmp['##document.weblink##']
                                      = $data['link'];

            $tmp['##document.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                         "document_".$data['id']);
            $downloadurl              = "/front/document.send.php?docid=".$data['id'];

            $tmp['##document.downloadurl##']
                                      = $this->formatURL($options['additionnaloption']['usertype'],
                                                         $downloadurl);
            $tmp['##document.heading##']
                                      = Dropdown::getDropdownName('glpi_documentcategories',
                                                                  $data['documentcategories_id']);

            $tmp['##document.filename##']
                                      = $data['filename'];

            $this->datas['documents'][]     = $tmp;
         }
      }

      $this->datas["##projecttask.urldocument##"]
                     = $this->formatURL($options['additionnaloption']['usertype'],
                                        "ProjectTask_".$item->getField("id").'_Document_Item$1');

      $this->datas["##projecttask.numberofdocuments##"]
                     = count($this->datas['documents']);

      // Items infos
      $restrict = "`projects_id` = '".$item->getField('id')."'";
      $items    = getAllDatasFromTable('glpi_items_projects',$restrict);


      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = array('projecttask.url'                 => __('URL'),
                        'projecttask.action'              => _n('Event', 'Events', 1),
                        'projecttask.name'                => __('Name'),
                        'projecttask.project'             => __('Project'),
                        'projecttask.description'         => __('Description'),
                        'projecttask.comments'            => __('Comments'),
                        'projecttask.creationdate'        => __('Creation date'),
                        'projecttask.lastupdatedate'      => __('Last update'),
                        'projecttask.planstartdate'       => __('Planned start date'),
                        'projecttask.planenddate'         => __('Planned end date'),
                        'projecttask.realstartdate'       => __('Real start date'),
                        'projecttask.realenddate'         => __('Real end date'),
                        'projecttask.father'              => __('Father'),
                        'projecttask.createbyuser'        => __('Writer'),
                        'projecttask.type'                => __('Type'),
                        'projecttask.state'               => _x('item', 'State'),
                        'projecttask.percent'             => __('Percent done'),
                        'projecttask.plannedduration'     => __('Planned duration'),
                        'projecttask.effectiveduration'   => __('Effective duration'),
                        'projecttask.ticketsduration'     => __('Tickets duration'),
                        'projecttask.totalduration'       => __('Total duration'),
                        'projecttask.numberoftasks'       => _x('quantity', 'Number of tasks'),
                        'projecttask.numberofteammembers' => _x('quantity', 'Number of team members'),
                        'task.date'                       => __('Opening date'),
                        'task.name'                       => __('Name'),
                        'task.description'                => __('Description'),
                        'task.comments'                   => __('Comments'),
                        'task.creationdate'               => __('Creation date'),
                        'task.lastupdatedate'             => __('Last update'),
                        'task.type'                       => __('Type'),
                        'task.state'                      => _x('item', 'State'),
                        'task.percent'                    => __('Percent done'),
                        'task.planstartdate'              => __('Planned start date'),
                        'task.planenddate'                => __('Planned end date'),
                        'task.realstartdate'              => __('Real start date'),
                        'task.realenddate'                => __('Real end date'),
                        'projecttask.numberoflogs'        => sprintf(__('%1$s: %2$s'),
                                                                     __('Historical'),
                                                                     _x('quantity',
                                                                        'Number of items')),
                        'projecttask.log.date'            => sprintf(__('%1$s: %2$s'),
                                                                     __('Historical'), __('Date')),
                        'projecttask.log.user'            => sprintf(__('%1$s: %2$s'),
                                                                     __('Historical'), __('User')),
                        'projecttask.log.field'           => sprintf(__('%1$s: %2$s'),
                                                                     __('Historical'), __('Field')),
                        'projecttask.log.content'         => sprintf(__('%1$s: %2$s'),
                                                                     __('Historical'),
                                                                     _x('name', 'Update')),
                        'projecttask.numberoftickets'     => _x('quantity', 'Number of tickets'),
                        'projecttask.numberofdocuments'   => _x('quantity', 'Number of documents'),
                     );

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }


      //Tags without lang
      $tags = array('ticket.id'               => sprintf(__('%1$s: %2$s'), __('Ticket'), __('ID')),
                    'ticket.date'             => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Date')),
                    'ticket.url'              => sprintf(__('%1$s: %2$s'), __('Ticket'), ('URL')),
                    'ticket.title'            => sprintf(__('%1$s: %2$s'), __('Ticket'),
                                                         __('Title')),
                    'ticket.content'          => sprintf(__('%1$s: %2$s'), __('Ticket'),
                                                         __('Description')),
                    'projecttask.projecturl'  => sprintf(__('%1$s: %2$s'), __('Project'), __('URL')),
                    'document.url'            => sprintf(__('%1$s: %2$s'), __('Document'),
                                                         __('URL')),
                    'document.downloadurl'    => sprintf(__('%1$s: %2$s'), __('Document'),
                                                         __('Download URL')),
                    'document.heading'        => sprintf(__('%1$s: %2$s'), __('Document'),
                                                         __('Heading')),
                    'document.id'             => sprintf(__('%1$s: %2$s'), __('Document'), __('ID')),
                    'document.filename'       => sprintf(__('%1$s: %2$s'), __('Document'),
                                                         __('File')),
                    'document.weblink'        => sprintf(__('%1$s: %2$s'), __('Document'),
                                                         __('Web Link')),
                    'document.name'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                         __('Name')),
                    'projecttask.urldocument' => sprintf(__('%1$s: %2$s'),
                                                         _n('Document', 'Documents', Session::getPluralNumber()), __('URL')),
                    'projecttask.entity'      => sprintf(__('%1$s (%2$s)'),
                                                         __('Entity'), __('Complete name')),
                    'projecttask.shortentity' => sprintf(__('%1$s (%2$s)'),
                                                         __('Entity'), __('Name')),
                    'teammember.name'        => sprintf(__('%1$s: %2$s'),
                                                        _n('Team member', 'Team members', 1),
                                                        __('Name')),
                    'teammember.itemtype'    => sprintf(__('%1$s: %2$s'),
                                                        _n('Team member', 'Team members', 1),
                                                        __('Type'))
                     );


      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }

      //Tags with just lang
      $tags = array('projecttask.entity'   => __('Entity'),
                    'projecttask.log'      => __('Historical'),
                    'projecttask.tasks'    => _n('Task', 'Tasks', Session::getPluralNumber()),
                    'projecttask.team'     => __('Project team'),
                    'projecttask.tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      //Foreach global tags
      $tags = array('log'         => __('Historical'),
                    'tasks'       => _n('Task', 'Tasks', Session::getPluralNumber()),
                    'tickets'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'teammembers' => _n('Team member', 'Team members', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }
      asort($this->tag_descriptions);
   }

}
?>