<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// Class NotificationTarget
class NotificationTargetProblem extends NotificationTargetCommonITILObject {

   var $private_profiles = array();

   public $html_tags     = array('##problem.solution.description##');


   /**
    * Get events related to tickets
   **/
   function getEvents() {

      $events = array('new'            => __('New problem'),
                      'update'         => __('Update of a problem'),
                      'solved'         => __('Problem solved'),
                      'add_task'       => __('New task'),
                      'update_task'    => __('Update of a task'),
                      'delete_task'    => __('Deletion of a task'),
                      'closed'         => __('Closure of a problem'),
                      'delete'         => __('Deleting a problem'));
      asort($events);
      return $events;
   }


   /**
    * @see NotificationTargetCommonITILObject::getDatasForObject()
   **/
   function getDatasForObject(CommonDBTM $item, array $options, $simple=false) {
      global $CFG_GLPI;

      // Common ITIL datas
      $datas                         = parent::getDatasForObject($item, $options, $simple);

      $datas["##problem.impacts##"]  = $item->getField('impactcontent');
      $datas["##problem.causes##"]   = $item->getField('causecontent');
      $datas["##problem.symptoms##"] = $item->getField('symptomcontent');

      // Complex mode : get tasks
      if (!$simple) {
         $restrict = "`problems_id`='".$item->getField('id')."'";
         $tickets  = getAllDatasFromTable('glpi_problems_tickets', $restrict);

         $datas['tickets'] = array();
         if (count($tickets)) {
            $ticket = new Ticket();
            foreach ($tickets as $data) {
               if ($ticket->getFromDB($data['tickets_id'])) {
                  $tmp = array();
                  $tmp['##ticket.id##']      = $data['tickets_id'];
                  $tmp['##ticket.date##']    = $ticket->getField('date');
                  $tmp['##ticket.title##']   = $ticket->getField('name');
                  $tmp['##ticket.url##']     = urldecode($CFG_GLPI["url_base"]."/index.php".
                                                         "?redirect=Ticket_".$data['tickets_id']);
                  $tmp['##ticket.content##'] = $ticket->getField('content');

                  $datas['tickets'][] = $tmp;
               }
            }
         }

         $datas['##problem.numberoftickets##'] = count($datas['tickets']);

         $restrict  = "`problems_id` = '".$item->getField('id')."'
                       ORDER BY `date` DESC,
                                `id` ASC";

         //Task infos
         $tasks = getAllDatasFromTable('glpi_problemtasks', $restrict);
         $datas['tasks'] = array();
         foreach ($tasks as $task) {
            $tmp                          = array();
            $tmp['##task.author##']       = Html::clean(getUserName($task['users_id']));
            $tmp['##task.category##']     = Dropdown::getDropdownName('glpi_taskcategories',
                                                                      $task['taskcategories_id']);
            $tmp['##task.date##']         = Html::convDateTime($task['date']);
            $tmp['##task.description##']  = $task['content'];
            $tmp['##task.time##']         = Problem::getActionTime($task['actiontime']);
            $tmp['##task.status##']       = Planning::getState($task['state']);

            $tmp['##task.user##']         = "";
            $tmp['##task.begin##']        = "";
            $tmp['##task.end##']          = "";
            if (!is_null($task['begin'])) {
               $tmp['##task.user##']      = Html::clean(getUserName($task['users_id_tech']));
               $tmp['##task.begin##']     = Html::convDateTime($task['begin']);
               $tmp['##task.end##']       = Html::convDateTime($task['end']);
            }

            $datas['tasks'][] = $tmp;
         }

         $datas['##problem.numberoftasks##'] = count($datas['tasks']);

         $restrict = "`problems_id` = '".$item->getField('id')."'";
         $items    = getAllDatasFromTable('glpi_items_problems',$restrict);

         $datas['items'] = array();
         if (count($tickets)) {
            foreach ($items as $data) {
               if ($item2 = getItemForItemtype($data['itemtype'])) {
                  if ($item2->getFromDB($data['items_id'])) {
                     $tmp = array();
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

                     $datas['items'][] = $tmp;
                  }
               }
            }
         }

         $datas['##problem.numberofitems##'] = count($datas['items']);

      }
      return $datas;
   }


   function getTags() {

      parent::getTags();

      //Locales
      $tags = array('task.author'               => __('Writer'),
                    'task.isprivate'            => __('Private'),
                    'task.date'                 => __('Opening date'),
                    'task.description'          => __('Description'),
                    'task.category'             => __('Category'),
                    'task.time'                 => __('Total duration'),
                    'task.user'                 => __('By'),
                    'task.begin'                => __('Start date'),
                    'task.end'                  => __('End date'),
                    'task.status'               => __('Status'),
                    'problem.numberoftasks'     => __('Number of tasks'),
                    'problem.numberoftickets'   => __('Number of tickets'),
                    'problem.impacts'           => __('Impacts'),
                    'problem.causes'            => __('Causes'),
                    'problem.symptoms'          => __('Symptoms'),
                    'item.name'                 => __('Associated item'),
                    'item.serial'               => __('Serial number'),
                    'item.otherserial'          => __('Inventory number'),
                    'item.location'             => __('Location'),
                    'item.model'                => __('Model'),
                    'item.contact'              => __('Alternate username'),
                    'item.contactnumber'        => __('Alternate username number'),
                    'item.user'                 => __('User'),
                    'item.group'                => __('Group'),);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }

      //Foreach global tags
      $tags = array('tasks'    => _n('Task', 'Tasks', 2),
                    'tickets'  => _n('Ticket', 'Tickets', 2),
                    'items'    => _n('Item', 'Items', 2));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }

      //Tags with just lang
      $tags = array('ticket.tickets'   => _n('Ticket', 'Tickets', 2),
                    'items'            => _n('Item', 'Items', 2));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      //Tags without lang
      $tags = array('ticket.id'        => sprintf(__('%1$s: %2$s'), __('Ticket'), __('ID')),
                    'ticket.date'      => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Date')),
                    'ticket.url'       => sprintf(__('%1$s: %2$s'), __('Ticket'), __('URL')),
                    'ticket.title'     => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Title')),
                    'ticket.content'   => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Description')));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }
      asort($this->tag_descriptions);
   }


}
?>
