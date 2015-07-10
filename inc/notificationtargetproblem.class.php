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
 * NotificationTargetProblem Class
**/
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

      // Complex mode
      if (!$simple) {
         $restrict = "`problems_id`='".$item->getField('id')."'";
         $tickets  = getAllDatasFromTable('glpi_problems_tickets', $restrict);

         $datas['tickets'] = array();
         if (count($tickets)) {
            $ticket = new Ticket();
            foreach ($tickets as $data) {
               if ($ticket->getFromDB($data['tickets_id'])) {
                  $tmp = array();

                  $tmp['##ticket.id##']
                                    = $data['tickets_id'];
                  $tmp['##ticket.date##']
                                    = $ticket->getField('date');
                  $tmp['##ticket.title##']
                                    = $ticket->getField('name');
                  $tmp['##ticket.url##']
                                    = $this->formatURL($options['additionnaloption']['usertype'],
                                                       "Ticket_".$data['tickets_id']);
                  $tmp['##ticket.content##']
                                    = $ticket->getField('content');

                  $datas['tickets'][] = $tmp;
               }
            }
         }

         $datas['##problem.numberoftickets##'] = count($datas['tickets']);


         $restrict = "`problems_id`='".$item->getField('id')."'";
         $changes  = getAllDatasFromTable('glpi_changes_problems', $restrict);

         $datas['changes'] = array();
         if (count($changes)) {
            $change = new Change();
            foreach ($changes as $data) {
               if ($change->getFromDB($data['changes_id'])) {
                  $tmp = array();
                  $tmp['##change.id##']
                                    = $data['changes_id'];
                  $tmp['##change.date##']
                                    = $change->getField('date');
                  $tmp['##change.title##']
                                    = $change->getField('name');
                  $tmp['##change.url##']
                                    = $this->formatURL($options['additionnaloption']['usertype'],
                                                       "Change_".$data['changes_id']);
                  $tmp['##change.content##']
                                    = $change->getField('content');

                  $datas['changes'][] = $tmp;
               }
            }
         }

         $datas['##problem.numberofchanges##'] = count($datas['changes']);

         $restrict = "`problems_id` = '".$item->getField('id')."'";
         $items    = getAllDatasFromTable('glpi_items_problems',$restrict);

         $datas['items'] = array();
         if (count($items)) {
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
      $tags = array('problem.numberoftickets'   => _x('quantity', 'Number of tickets'),
                    'problem.numberofchanges'   => _x('quantity', 'Number of changes'),
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
      $tags = array('tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'changes'  => _n('Change', 'Changes', Session::getPluralNumber()),
                    'items'    => _n('Item', 'Items', Session::getPluralNumber()));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }

      //Tags with just lang
      $tags = array('problem.tickets'  => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                    'problem.changes'  => _n('Change', 'Changes', Session::getPluralNumber()),
                    'problem.items'    => _n('Item', 'Items', Session::getPluralNumber()));

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
                    'ticket.content'   => sprintf(__('%1$s: %2$s'), __('Ticket'), __('Description')),
                    'change.id'        => sprintf(__('%1$s: %2$s'), __('Change'), __('ID')),
                    'change.date'      => sprintf(__('%1$s: %2$s'), __('Change'), __('Date')),
                    'change.url'       => sprintf(__('%1$s: %2$s'), __('Change'), __('URL')),
                    'change.title'     => sprintf(__('%1$s: %2$s'), __('Change'), __('Title')),
                    'change.content'   => sprintf(__('%1$s: %2$s'), __('Change'), __('Description')),
                    );

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
