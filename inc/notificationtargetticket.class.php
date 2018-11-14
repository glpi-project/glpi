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
**/
class NotificationTargetTicket extends NotificationTargetCommonITILObject {

   const HEADERTAG = '=-=-=-=';
   const FOOTERTAG = '=_=_=_=';

   function validateSendTo($event, array $infos, $notify_me = false) {

      // Always send notification for satisfaction : if send on ticket closure
      // Always send notification for new ticket
      if (in_array($event, ['satisfaction', 'new'])) {
         return true;
      }

      return parent::validateSendTo($event, $infos, $notify_me);
   }


   function getSubjectPrefix($event = '') {

      if ($event !='alertnotclosed') {
         $perso_tag = trim(Entity::getUsedConfig('notification_subject_tag', $this->getEntity(),
                                                 '', ''));

         if (empty($perso_tag)) {
            $perso_tag = 'GLPI';
         }
         return sprintf("[$perso_tag #%07d] ", $this->obj->getField('id'));
      }
      return parent::getSubjectPrefix();
   }


   /**
   * Get header to add to content
   **/
   function getContentHeader() {

      if ($this->getMode() == \Notification_NotificationTemplate::MODE_MAIL
         && MailCollector::getNumberOfActiveMailCollectors()
      ) {
         return self::HEADERTAG.' '.__('To answer by email, write above this line').' '.
                self::HEADERTAG;
      }

      return '';
   }


   /**
   * Get footer to add to content
   **/
   function getContentFooter() {

      if ($this->getMode() == \Notification_NotificationTemplate::MODE_MAIL
         && MailCollector::getNumberOfActiveMailCollectors()
      ) {
         return self::FOOTERTAG.' '.__('To answer by email, write under this line').' '.
                self::FOOTERTAG;
      }

      return '';
   }


   /**
    * @since 0.84
    *
    * @return string
   **/
   function getMessageID() {
      return "GLPI-".$this->obj->getField('id').".".time().".".rand(). "@".php_uname('n');
   }


   /**
    * Get item associated with the object on which the event was raised
    *
    * @param $event  (default '')
    *
    * @return the object associated with the itemtype
   **/
   function getObjectItem($event = '') {

      if ($this->obj && isset($this->obj->fields['id']) && !empty($this->obj->fields['id'])) {
         $item_ticket = new Item_Ticket();
         $data = $item_ticket->find(['tickets_id' => $this->obj->fields['id']]);
         foreach ($data as $val) {
            if (($val['itemtype'] != NOT_AVAILABLE)
                && ($val['itemtype'] != '')
                && ($item = getItemForItemtype($val['itemtype']))) {

               $item->getFromDB($val['items_id']);
               $this->target_object[] = $item;
            }
         }
      }
   }

   /**
    *Get events related to tickets
   **/
   function getEvents() {

      $events = ['new'               => __('New ticket'),
                      'update'            => __('Update of a ticket'),
                      'solved'            => __('Ticket solved'),
                      'rejectsolution'    => __('Solution rejected'),
                      'validation'        => __('Validation request'),
                      'validation_answer' => __('Validation request answer'),
                      'closed'            => __('Closing of the ticket'),
                      'delete'            => __('Deletion of a ticket'),
                      'alertnotclosed'    => __('Not solved tickets'),
                      'recall'            => __('Automatic reminders of SLAs'),
                      'recall_ola'        => __('Automatic reminders of OLAs'),
                      'satisfaction'      => __('Satisfaction survey'),
                      'replysatisfaction' => __('Satisfaction survey answer')];

      $events = array_merge($events, parent::getEvents());
      asort($events);
      return $events;
   }


   function getDataForObject(CommonDBTM $item, array $options, $simple = false) {
      global $CFG_GLPI;

      // Common ITIL data
      $data = parent::getDataForObject($item, $options, $simple);
      /*$data['##ticket.description##'] = Html::clean($data['##ticket.description##']);*/

      $data['##ticket.content##'] = $data['##ticket.description##'];
      // Specific data
      $data['##ticket.urlvalidation##']
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                          "ticket_".$item->getField("id")."_TicketValidation$1");
      $data['##ticket.globalvalidation##']
                        = TicketValidation::getStatus($item->getField('global_validation'));
      $data['##ticket.type##']
                        = Ticket::getTicketTypeName($item->getField('type'));
      $data['##ticket.requesttype##']
                        = Dropdown::getDropdownName('glpi_requesttypes',
                                                    $item->getField('requesttypes_id'));

      $autoclose_value  = Entity::getUsedConfig('autoclose_delay', $this->getEntity(), '',
                                                Entity::CONFIG_NEVER);

      $data['##ticket.autoclose##']             = __('Never');
      $data['##lang.ticket.autoclosewarning##'] = "";
      if ($autoclose_value > 0) {
         $data['##ticket.autoclose##'] = $autoclose_value;
         $data['##lang.ticket.autoclosewarning##']
                     //TRANS: %s is the number of days before auto closing
            = sprintf(_n('Without a reply, the ticket will be automatically closed after %s day',
                         'Without a reply, the ticket will be automatically closed after %s days',
                         $autoclose_value),
                      $autoclose_value);
      }

      $data['##ticket.sla_tto##'] = '';
      if ($item->getField('slas_tto_id')) {
         $data['##ticket.sla_tto##'] = Dropdown::getDropdownName('glpi_slas',
                                                                 $item->getField('slas_tto_id'));
      }
      $data['##ticket.sla_ttr##'] = '';
      if ($item->getField('slas_ttr_id')) {
         $data['##ticket.sla_ttr##'] = Dropdown::getDropdownName('glpi_slas',
                                                                 $item->getField('slas_ttr_id'));
      }
      $data['##ticket.sla##'] = $data['##ticket.sla_ttr##'];

      $data['##ticket.ola_tto##'] = '';
      if ($item->getField('olas_tto_id')) {
         $data['##ticket.ola_tto##'] = Dropdown::getDropdownName('glpi_olas',
                                                                 $item->getField('olas_tto_id'));
      }
      $data['##ticket.ola_ttr##'] = '';
      if ($item->getField('olas_ttr_id')) {
         $data['##ticket.ola_ttr##'] = Dropdown::getDropdownName('glpi_olas',
                                                                 $item->getField('olas_ttr_id'));
      }

      $data['##ticket.location##'] = '';
      if ($item->getField('locations_id')) {
         $data['##ticket.location##'] = Dropdown::getDropdownName('glpi_locations',
                                                                   $item->getField('locations_id'));
         $locations = new Location();
         $locations->getFromDB($item->getField('locations_id'));
         if ($locations->getField('comment')) {
            $data['##ticket.location.comment##'] = $locations->getField('comment');
         }
         if ($locations->getField('room')) {
            $data['##ticket.location.room##'] = $locations->getField('room');
         }
         if ($locations->getField('building')) {
            $data['##ticket.location.building##'] = $locations->getField('building');
         }
         if ($locations->getField('latitude')) {
            $data['##ticket.location.latitude##'] = $locations->getField('latitude');
         }
         if ($locations->getField('longitude')) {
            $data['##ticket.location.longitude##'] = $locations->getField('longitude');
         }
         if ($locations->getField('altitude')) {
            $data['##ticket.location.altitude##'] = $locations->getField('altitude');
         }
      }

      // is ticket deleted
      $data['##ticket.isdeleted##'] = Dropdown::getYesNo($item->getField('is_deleted'));

      //Tags associated with the object linked to the ticket
      $data['##ticket.itemtype##']                 = '';
      $data['##ticket.item.name##']                = '';
      $data['##ticket.item.serial##']              = '';
      $data['##ticket.item.otherserial##']         = '';
      $data['##ticket.item.location##']            = '';
      $data['##ticket.item.locationcomment##']     = '';
      $data['##ticket.item.locationroom##']        = '';
      $data['##ticket.item.locationbuilding##']    = '';
      $data['##ticket.item.locationlatitude##']    = '';
      $data['##ticket.item.locationlongitude##']   = '';
      $data['##ticket.item.locationaltitude##']    = '';
      $data['##ticket.item.contact##']             = '';
      $data['##ticket.item.contactnumber##']       = '';
      $data['##ticket.item.user##']                = '';
      $data['##ticket.item.group##']               = '';
      $data['##ticket.item.model##']               = '';

      $item_ticket = new Item_Ticket();
      $items = $item_ticket->find(['tickets_id' => $item->getField('id')]);
      $data['items'] = [];
      if (count($items)) {
         foreach ($items as $val) {
            if (isset($val['itemtype'])
                && ($hardware = getItemForItemtype($val['itemtype']))
                && isset($val["items_id"])
                && $hardware->getFromDB($val["items_id"])) {

               $tmp = [];

               //Object type
               $tmp['##ticket.itemtype##']  = $hardware->getTypeName();

               //Object name
               $tmp['##ticket.item.name##'] = $hardware->getField('name');

               //Object serial
               if ($hardware->isField('serial')) {
                  $tmp['##ticket.item.serial##'] = $hardware->getField('serial');
               }

               //Object contact
               if ($hardware->isField('contact')) {
                  $tmp['##ticket.item.contact##'] = $hardware->getField('contact');
               }

               //Object contact num
               if ($hardware->isField('contact_num')) {
                  $tmp['##ticket.item.contactnumber##'] = $hardware->getField('contact_num');
               }

               //Object otherserial
               if ($hardware->isField('otherserial')) {
                  $tmp['##ticket.item.otherserial##'] = $hardware->getField('otherserial');
               }

               //Object location
               if ($hardware->isField('locations_id')) {
                  $tmp['##ticket.item.location##']
                              = Dropdown::getDropdownName('glpi_locations',
                                                          $hardware->getField('locations_id'));
                  $locations = new Location();
                  $locations->getFromDB($hardware->getField('locations_id'));
                  if ($hardware->getField('comment')) {
                     $data['##ticket.item.locationcomment##'] = $locations->getField('comment');
                  }
                  if ($hardware->getField('room')) {
                     $data['##ticket.item.locationroom##'] = $locations->getField('room');
                  }
                  if ($hardware->getField('building')) {
                     $data['##ticket.item.locationbuilding##'] = $locations->getField('building');
                  }
                  if ($hardware->getField('latitude')) {
                     $data['##ticket.item.locationlatitude##'] = $locations->getField('latitude');
                  }
                  if ($hardware->getField('longitude')) {
                     $data['##ticket.item.locationlongitude##'] = $locations->getField('longitude');
                  }
                  if ($hardware->getField('altitude')) {
                     $data['##ticket.item.locationaltitude##'] = $locations->getField('altitude');
                  }
               }

               //Object user
               if ($hardware->getField('users_id')) {
                  $user_tmp = new User();
                  if ($user_tmp->getFromDB($hardware->getField('users_id'))) {
                     $tmp['##ticket.item.user##'] = $user_tmp->getName();
                  }
               }

               //Object group
               if ($hardware->getField('groups_id')) {
                  $tmp['##ticket.item.group##']
                              = Dropdown::getDropdownName('glpi_groups', $hardware->getField('groups_id'));
               }

               $modeltable = getSingular($hardware->getTable())."models";
               $modelfield = getForeignKeyFieldForTable($modeltable);

               if ($hardware->isField($modelfield)) {
                  $tmp['##ticket.item.model##']
                              = Dropdown::getDropdownName($modeltable, $hardware->getField($modelfield));
               }

               $data['items'][] = $tmp;
            }
         }
      }

      $data['##ticket.numberofitems##'] = count($data['items']);

      // Get followups, log, validation, satisfaction, linked tickets
      if (!$simple) {
         // Linked tickets
         $linked_tickets         = Ticket_Ticket::getLinkedTicketsTo($item->getField('id'));
         $data['linkedtickets'] = [];
         if (count($linked_tickets)) {
            $linkedticket = new Ticket();
            foreach ($linked_tickets as $row) {
               if ($linkedticket->getFromDB($row['tickets_id'])) {
                  $tmp = [];

                  $tmp['##linkedticket.id##']
                                    = $row['tickets_id'];
                  $tmp['##linkedticket.link##']
                                    = Ticket_Ticket::getLinkName($row['link']);
                  $tmp['##linkedticket.url##']
                                    = $this->formatURL($options['additionnaloption']['usertype'],
                                                       "ticket_".$row['tickets_id']);

                  $tmp['##linkedticket.title##']
                                    = $linkedticket->getField('name');
                  $tmp['##linkedticket.content##']
                                    = $linkedticket->getField('content');

                  $data['linkedtickets'][] = $tmp;
               }
            }
         }

         $data['##ticket.numberoflinkedtickets##'] = count($data['linkedtickets']);

         $restrict          = ['tickets_id' => $item->getField('id')];
         $problems          = getAllDatasFromTable('glpi_problems_tickets', $restrict);
         $data['problems'] = [];
         if (count($problems)) {
            $problem = new Problem();
            foreach ($problems as $row) {
               if ($problem->getFromDB($row['problems_id'])) {
                  $tmp = [];

                  $tmp['##problem.id##']
                                 = $row['problems_id'];
                  $tmp['##problem.date##']
                                 = $problem->getField('date');
                  $tmp['##problem.title##']
                                 = $problem->getField('name');
                  $tmp['##problem.url##']
                                 = $this->formatURL($options['additionnaloption']['usertype'],
                                                    "problem_".$row['problems_id']);
                  $tmp['##problem.content##']
                                 = $problem->getField('content');

                  $data['problems'][] = $tmp;
               }
            }
         }

         $data['##ticket.numberofproblems##'] = count($data['problems']);

         $changes          = getAllDatasFromTable('glpi_changes_tickets', $restrict);
         $data['changes'] = [];
         if (count($changes)) {
            $change = new Change();
            foreach ($changes as $row) {
               if ($change->getFromDB($row['changes_id'])) {
                  $tmp = [];

                  $tmp['##change.id##']
                                 = $row['changes_id'];
                  $tmp['##change.date##']
                                 = $change->getField('date');
                  $tmp['##change.title##']
                                 = $change->getField('name');
                  $tmp['##change.url##']
                                 = $this->formatURL($options['additionnaloption']['usertype'],
                                                    "change_".$row['changes_id']);
                  $tmp['##change.content##']
                                 = $change->getField('content');

                  $data['changes'][] = $tmp;
               }
            }
         }

         $data['##ticket.numberofchanges##'] = count($data['changes']);

         // Approbation of solution
         $solution_restrict = [
            'itemtype' => 'Ticket',
            'items_id' => $item->getField('id')
         ];
         $replysolved = getAllDatasFromTable('glpi_itilfollowups', $solution_restrict, false, ['date_mod DESC', 'id ASC']);
         $current = current($replysolved);
         $data['##ticket.solution.approval.description##'] = $current['content'];
         $data['##ticket.solution.approval.date##']        = Html::convDateTime($current['date']);
         $data['##ticket.solution.approval.author##']      = Html::clean(getUserName($current['users_id']));

         //Validation infos
         $restrict = ['tickets_id' => $item->getField('id')];

         if (isset($options['validation_id']) && $options['validation_id']) {
            $restrict['glpi_ticketvalidations.id'] = $options['validation_id'];
         }

         $validations = getAllDatasFromTable('glpi_ticketvalidations', $restrict, false, ['submission_date DESC', 'id ASC']);
         $data['validations'] = [];
         foreach ($validations as $validation) {
            $tmp = [];
            $tmp['##validation.submission.title##']
                              //TRANS: %s is the user name
                              = sprintf(__('An approval request has been submitted by %s'),
                                        Html::clean(getUserName($validation['users_id'])));
            $tmp['##validation.answer.title##']
                              //TRANS: %s is the user name
                              = sprintf(__('An answer to an an approval request was produced by %s'),
                                        Html::clean(getUserName($validation['users_id_validate'])));

            $tmp['##validation.author##']
                              = Html::clean(getUserName($validation['users_id']));

            $tmp['##validation.status##']
                              = TicketValidation::getStatus($validation['status']);
            $tmp['##validation.storestatus##']
                              = $validation['status'];
            $tmp['##validation.submissiondate##']
                              = Html::convDateTime($validation['submission_date']);
            $tmp['##validation.commentsubmission##']
                              = $validation['comment_submission'];
            $tmp['##validation.validationdate##']
                              = Html::convDateTime($validation['validation_date']);
            $tmp['##validation.validator##']
                              =  Html::clean(getUserName($validation['users_id_validate']));
            $tmp['##validation.commentvalidation##']
                              = $validation['comment_validation'];

            $data['validations'][] = $tmp;
         }

         // Ticket Satisfaction
         $inquest                                = new TicketSatisfaction();
         $data['##satisfaction.type##']         = '';
         $data['##satisfaction.datebegin##']    = '';
         $data['##satisfaction.dateanswered##'] = '';
         $data['##satisfaction.satisfaction##'] = '';
         $data['##satisfaction.description##']  = '';

         if ($inquest->getFromDB($item->getField('id'))) {
            // internal inquest
            if ($inquest->fields['type'] == 1) {
               $data['##ticket.urlsatisfaction##']
                           = $this->formatURL($options['additionnaloption']['usertype'],
                                              "ticket_".$item->getField("id").'_Ticket$3');

            } else if ($inquest->fields['type'] == 2) { // external inquest
               $data['##ticket.urlsatisfaction##'] = Entity::generateLinkSatisfaction($item);
            }

            $data['##satisfaction.type##']
                                       = $inquest->getTypeInquestName($inquest->getfield('type'));
            $data['##satisfaction.datebegin##']
                                       = Html::convDateTime($inquest->fields['date_begin']);
            $data['##satisfaction.dateanswered##']
                                       = Html::convDateTime($inquest->fields['date_answered']);
            $data['##satisfaction.satisfaction##']
                                       = $inquest->fields['satisfaction'];
            $data['##satisfaction.description##']
                                       = $inquest->fields['comment'];
         }
      }
      return $data;
   }


   static function isAuthorMailingActivatedForHelpdesk() {
      global $DB,$CFG_GLPI;

      if ($CFG_GLPI['notifications_mailing']) {
         $query = "SELECT COUNT(`glpi_notifications`.`id`)
                   FROM `glpi_notifications`
                   INNER JOIN `glpi_notificationtargets`
                     ON (`glpi_notifications`.`id` = `glpi_notificationtargets`.`notifications_id`)
                   INNER JOIN `glpi_notifications_notificationtemplates`
                     ON (`glpi_notifications`.`id`=`glpi_notifications_notificationtemplates`.`notifications_id`)
                   WHERE `glpi_notifications`.`itemtype` = 'Ticket'
                         AND `glpi_notifications_notificationtemplates`.`mode` = '" . Notification_NotificationTemplate::MODE_MAIL  . "'
                         AND `glpi_notificationtargets`.`type` = '".Notification::USER_TYPE."'
                         AND `glpi_notificationtargets`.`items_id` = '".Notification::AUTHOR."'";

         if ($result = $DB->query($query)) {
            if ($DB->result($result, 0, 0) > 0) {
               return true;
            }
         }
      }
      return false;
   }


   function getTags() {

      parent::getTags();

      //Locales
      $tags = ['ticket.type'                  => __('Type'),
                    'ticket.sla'                   => __('SLA'),
                    'ticket.sla_tto'               => sprintf(__('%1$s / %2$s'),
                                                              __('SLA'),
                                                              __('Time to own')),
                    'ticket.sla_ttr'               => sprintf(__('%1$s / %2$s'),
                                                              __('SLA'),
                                                              __('Time to resolve')),
                    'ticket.ola_tto'               => sprintf(__('%1$s / %2$s'),
                                                              __('OLA'),
                                                              __('Internal time to own')),
                    'ticket.ola_ttr'               => sprintf(__('%1$s / %2$s'),
                                                              __('OLA'),
                                                              __('Internal time to resolve')),
                    'ticket.requesttype'           => __('Request source'),
                    'ticket.itemtype'              => __('Item type'),
                    'ticket.item.name'             => __('Associated item'),
                    'ticket.item.serial'           => __('Serial number'),
                    'ticket.item.otherserial'      => __('Inventory number'),
                    'ticket.item.location'         => sprintf(__('%1$s: %2$s'),
                                                              _n('Associated element', 'Associated elements', 2),
                                                              __('Location name')),
                    'ticket.item.locationcomment'  => sprintf(__('%1$s: %2$s'),
                                                              _n('Associated element', 'Associated elements', 2),
                                                              __('Location comments')),
                    'ticket.item.locationroom'     => sprintf(__('%1$s: %2$s'),
                                                              _n('Associated element', 'Associated elements', 2),
                                                              __('Room number')),
                    'ticket.item.locationbuilding' => sprintf(__('%1$s: %2$s'),
                                                              _n('Associated element', 'Associated elements', 2),
                                                              __('Building number')),
                    'ticket.item.locationlatitude' => sprintf(__('%1$s: %2$s'),
                                                              _n('Associated element', 'Associated elements', 2),
                                                              __('Latitude')),
                    'ticket.item.locationlongitude' => sprintf(__('%1$s: %2$s'),
                                                               _n('Associated element', 'Associated elements', 2),
                                                               __('Longitude')),
                    'ticket.item.locationaltitude' => sprintf(__('%1$s: %2$s'),
                                                              _n('Associated element', 'Associated elements', 2),
                                                              __('Altitude')),
                    'ticket.item.model'            => __('Model'),
                    'ticket.item.contact'          => __('Alternate username'),
                    'ticket.item.contactnumber'    => __('Alternate username number'),
                    'ticket.item.user'             => __('User'),
                    'ticket.item.group'            => __('Group'),
                    'ticket.isdeleted'             => __('Deleted'),
                    'ticket.numberoflinkedtickets' => _x('quantity', 'Number of linked tickets'),
                    'ticket.numberofproblems'      => _x('quantity', 'Number of problems'),
                    'ticket.numberofchanges'       => _x('quantity', 'Number of changes'),
                    'ticket.numberofitems'         => _x('quantity', 'Number of items'),
                    'ticket.autoclose'             => __('Automatic closing of solved tickets after'),
                    'ticket.location'              => __('Location'),
                    'ticket.location.comment'      => __('Location comments'),
                    'ticket.location.room'         => __('Room number'),
                    'ticket.location.building'     => __('Building number'),
                    'ticket.location.latitude'     => __('Latitude'),
                    'ticket.location.longitude'    => __('Longitude'),
                    'ticket.location.altitude'     => __('Altitude'),
                    'ticket.globalvalidation'      => __('Global approval status'),
                    'ticket.solution.approval.description'  => __('Solution rejection comment'),
                    'ticket.solution.approval.date'         => __('Solution rejection date'),
                    'ticket.solution.approval.author'       => __('Approver')
                  ];
      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => NotificationTarget::TAG_FOR_ALL_EVENTS]);
      }

      //Events specific for validation
      $tags = ['validation.author'            => __('Requester'),
                    'validation.status'            => __('Status of the approval request'),
                    'validation.submissiondate'    => sprintf(__('%1$s: %2$s'), __('Request'),
                                                              __('Date')),
                    'validation.commentsubmission' => sprintf(__('%1$s: %2$s'), __('Request'),
                                                              __('Comments')),
                    'validation.validationdate'    => sprintf(__('%1$s: %2$s'), __('Validation'),
                                                              __('Date')),
                    'validation.validator'         => __('Decision-maker'),
                    'validation.commentvalidation' => sprintf(__('%1$s: %2$s'), __('Validation'),
                                                              __('Comments'))
                    ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => ['validation', 'validation_answer']]);
      }
      //Tags without lang for validation
      $tags = ['validation.submission.title'
                                          => __('A validation request has been submitted'),
                    'validation.answer.title'
                                          => __('An answer to a validation request was produced')
                    ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false,
                                   'events' => ['validation', 'validation_answer']]);
      }

      // Events for ticket satisfaction
      $tags = ['satisfaction.datebegin'    => __('Creation date of the satisfaction survey'),
                    'satisfaction.dateanswered' => __('Response date to the satisfaction survey'),
                    'satisfaction.satisfaction' => __('Satisfaction'),
                    'satisfaction.description'  => __('Comments to the satisfaction survey')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => ['satisfaction']]);
      }

      $tags = ['satisfaction.type'  => __('Survey type'),];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'lang'   => false,
                                   'events' => ['satisfaction']]);
      }

      $tags = ['satisfaction.text' => __('Invitation to fill out the survey')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => false,
                                   'lang'   => true,
                                   'events' => ['satisfaction']]);
      }

      //Foreach global tags
      $tags = ['validations'   => _n('Validation', 'Validations', Session::getPluralNumber()),
                    'linkedtickets' => _n('Linked ticket', 'Linked tickets', Session::getPluralNumber()),
                    'problems'      => _n('Problem', 'Problems', Session::getPluralNumber()),
                    'changes'       => _n('Change', 'Changes', Session::getPluralNumber()),
                    'items'         => _n('Associated item', 'Associated items', Session::getPluralNumber()),
                    'documents'     => _n('Document', 'Documents', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true]);
      }

      //Tags with just lang
      $tags = ['ticket.linkedtickets'    => _n('Linked ticket', 'Linked tickets', Session::getPluralNumber()),
                    'ticket.problems'         => _n('Problem', 'Problems', Session::getPluralNumber()),
                    'ticket.changes'          => _n('Change', 'Changes', Session::getPluralNumber()),
                    'ticket.autoclosewarning'
                     => sprintf(_n('Without a reply, the ticket will be automatically closed after %s day',
                                   'Without a reply, the ticket will be automatically closed after %s days',
                                   2),
                                '?')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true]);
      }

      //Foreach tag for alertnotclosed
      $this->addTagToList(['tag'     => 'tickets',
                                'label'   => __('Not solved tickets'),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => ['alertnotclosed']]);

      //Tags without lang
      $tags = ['ticket.urlvalidation'    => sprintf(__('%1$s: %2$s'), __('Validation request'),
                                                         __('URL')),
                    'ticket.urlsatisfaction'  => sprintf(__('%1$s: %2$s'), __('Satisfaction'),
                                                         __('URL')),
                    'linkedticket.id'         => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('ID')),
                    'linkedticket.link'       => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('Link')),
                    'linkedticket.url'        => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('URL')),
                    'linkedticket.title'      => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('Title')),
                    'linkedticket.content'    => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('Description')),
                    'problem.id'              => sprintf(__('%1$s: %2$s'), __('Problem'), __('ID')),
                    'problem.date'            => sprintf(__('%1$s: %2$s'), __('Problem'), __('Date')),
                    'problem.url'             => sprintf(__('%1$s: %2$s'), __('Problem'), ('URL')),
                    'problem.title'           => sprintf(__('%1$s: %2$s'), __('Problem'),
                                                         __('Title')),
                    'problem.content'         => sprintf(__('%1$s: %2$s'), __('Problem'),
                                                         __('Description')),
                    'change.id'               => sprintf(__('%1$s: %2$s'), __('Change'), __('ID')),
                    'change.date'             => sprintf(__('%1$s: %2$s'), __('Change'), __('Date')),
                    'change.url'              => sprintf(__('%1$s: %2$s'), __('Change'), ('URL')),
                    'change.title'            => sprintf(__('%1$s: %2$s'), __('Change'),
                                                         __('Title')),
                    'change.content'          => sprintf(__('%1$s: %2$s'), __('Change'),
                                                         __('Description'))
                   ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false]);
      }

      //Tickets with a fixed set of values
      $allowed_validation = [];
      $status = TicketValidation::getAllStatusArray(false, true);
      foreach ($status as $key => $value) {
         $allowed_validation[] = $key;
      }

      $tags = ['validation.validationstatus'
                     => ['text'           => __('Status value in database'),
                              'allowed_values' => $allowed_validation]];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'            => $tag,
                                   'label'          => $label['text'],
                                   'value'          => true,
                                   'lang'           => false,
                                   'allowed_values' => $label['allowed_values']]);
      }

      asort($this->tag_descriptions);
   }

}
