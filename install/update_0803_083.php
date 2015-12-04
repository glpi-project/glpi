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

/**
 * Update from 0.80.3 to 0.83
 *
 * @return bool for success (will die for most error)
**/
function update0803to083() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.83'));
   $migration->setVersion('0.83');

   $backup_tables = false;
   $newtables     = array('glpi_entities_knowbaseitems', 'glpi_entities_reminders',
                          'glpi_groups_problems', 'glpi_groups_knowbaseitems', 'glpi_groups_reminders',
                          'glpi_knowbaseitems_profiles',  'glpi_knowbaseitems_users',
                          'glpi_items_problems', 'glpi_problems',
                          'glpi_problemtasks', 'glpi_problems_ticket', 'glpi_problems_users',
                          'glpi_profiles_reminders', 'glpi_reminders_users',
                          'glpi_ticketrecurrents',
                          'glpi_tickettemplates', 'glpi_tickettemplatehiddenfields',
                          'glpi_tickettemplatemandatoryfields',
                          'glpi_tickettemplatepredefinedfields', 'glpi_useremails');

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if (TableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.", true);
   }

   //TRANS: %s is the table or item implied
   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Problems'));

   // Clean ticket validations : already done in 0.80
   $query = "DELETE
             FROM `glpi_ticketvalidations`
             WHERE `glpi_ticketvalidations`.`tickets_id` NOT IN (SELECT `glpi_tickets`.`id`
                                                                 FROM `glpi_tickets`)";
   $DB->queryOrDie($query, "0.83 clean glpi_ticketvalidations");

   // Problems management
   if (!TableExists('glpi_problems')) {
      $query = "CREATE TABLE `glpi_problems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `status` varchar(255) DEFAULT NULL,
                  `content` longtext DEFAULT NULL,
                  `date_mod` DATETIME DEFAULT NULL,
                  `date` DATETIME DEFAULT NULL,
                  `solvedate` DATETIME DEFAULT NULL,
                  `closedate` DATETIME DEFAULT NULL,
                  `due_date` DATETIME DEFAULT NULL,
                  `users_id_recipient` int(11) NOT NULL DEFAULT '0',
                  `users_id_lastupdater` int(11) NOT NULL DEFAULT '0',
                  `suppliers_id_assign` int(11) NOT NULL DEFAULT '0',
                  `urgency` int(11) NOT NULL DEFAULT '1',
                  `impact` int(11) NOT NULL DEFAULT '1',
                  `priority` int(11) NOT NULL DEFAULT '1',
                  `itilcategories_id` int(11) NOT NULL DEFAULT '0',
                  `impactcontent` longtext DEFAULT NULL,
                  `causecontent` longtext DEFAULT NULL,
                  `symptomcontent` longtext DEFAULT NULL,
                  `solutiontypes_id` int(11) NOT NULL DEFAULT '0',
                  `solution` text COLLATE utf8_unicode_ci,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  `begin_waiting_date` datetime DEFAULT NULL,
                  `waiting_duration` int(11) NOT NULL DEFAULT '0',
                  `close_delay_stat` int(11) NOT NULL DEFAULT '0',
                  `solve_delay_stat` int(11) NOT NULL DEFAULT '0',
                  `notepad` LONGTEXT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `date` (`date`),
                  KEY `closedate` (`closedate`),
                  KEY `status` (`status`(1)),
                  KEY `priority` (`priority`),
                  KEY `date_mod` (`date_mod`),
                  KEY `suppliers_id_assign` (`suppliers_id_assign`),
                  KEY `itilcategories_id` (`itilcategories_id`),
                  KEY `users_id_recipient` (`users_id_recipient`),
                  KEY `solvedate` (`solvedate`),
                  KEY `solutiontypes_id` (`solutiontypes_id`),
                  KEY `urgency` (`urgency`),
                  KEY `impact` (`impact`),
                  KEY `due_date` (`due_date`),
                  KEY `users_id_lastupdater` (`users_id_lastupdater`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 create glpi_problems");
      $ADDTODISPLAYPREF['Problem'] = array(21,12,19,15,3,7,18);
   }

   if (FieldExists('glpi_tickets', 'ticket_waiting_duration', false)) {
     $migration->changeField('glpi_tickets', 'ticket_waiting_duration', 'waiting_duration',
                             'integer');
   }

   if (!TableExists('glpi_problems_users')) {
      $query = "CREATE TABLE `glpi_problems_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  `use_notification` tinyint(1) NOT NULL DEFAULT '0',
                  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`problems_id`,`type`,`users_id`,`alternative_email`),
                  KEY `user` (`users_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_problems_users");
   }

   if (!TableExists('glpi_groups_problems')) {
      $query = "CREATE TABLE `glpi_groups_problems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`problems_id`,`type`,`groups_id`),
                  KEY `group` (`groups_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_groups_problems");
   }

   if (!TableExists('glpi_items_problems')) {
      $query = "CREATE TABLE `glpi_items_problems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) default NULL,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`problems_id`,`itemtype`,`items_id`),
                  KEY `item` (`itemtype`,`items_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_items_problems");
   }

   if (!TableExists('glpi_problems_tickets')) {
      $query = "CREATE TABLE `glpi_problems_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`problems_id`,`tickets_id`),
                  KEY `tickets_id` (`tickets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_problems_tickets");
   }

   if (!TableExists('glpi_problemtasks')) {
      $query = "CREATE TABLE `glpi_problemtasks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  `taskcategories_id` int(11) NOT NULL DEFAULT '0',
                  `date` datetime DEFAULT NULL,
                  `begin` datetime DEFAULT NULL,
                  `end` datetime DEFAULT NULL,
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `users_id_tech` int(11) NOT NULL DEFAULT '0',
                  `content` longtext COLLATE utf8_unicode_ci,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  `state` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `problems_id` (`problems_id`),
                  KEY `users_id` (`users_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `date` (`date`),
                  KEY `begin` (`begin`),
                  KEY `end` (`end`),
                  KEY `state` (`state`),
                  KEY `taskcategories_id` (taskcategories_id)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_problemtasks");
   }

   $migration->addField("glpi_profiles", "show_my_problem", "char",
                        array('update'    => "1",
                              'condition' => " WHERE `own_ticket` = 1"));

   $migration->addField("glpi_profiles", "show_all_problem", "char",
                        array('update'    => "1",
                              'condition' => " WHERE `show_all_ticket` = 1"));

   $migration->addField("glpi_profiles", "edit_all_problem", "char",
                        array('update'    => "1",
                              'condition' => " WHERE `update_ticket` = 1"));

   $migration->changeField("glpi_profiles", 'helpdesk_status', 'ticket_status', "text",
                           array('comment' => "json encoded array of from/dest allowed status change"));

   $migration->addField('glpi_profiles', 'problem_status', "text",
                        array('comment' => "json encoded array of from/dest allowed status change"));

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Changes'));

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'TicketPlanning'));

   // Merge tickettasks and ticket planning
   if (TableExists('glpi_ticketplannings')) {
      $migration->addField("glpi_tickettasks", "begin", "datetime");
      $migration->addField("glpi_tickettasks", "end", "datetime");
      $migration->addField("glpi_tickettasks", "state", "integer", array('value' => '1'));
      $migration->addField("glpi_tickettasks", "users_id_tech", "integer");
      $migration->migrationOneTable('glpi_tickettasks');

      // migrate DATA
      $task = new TicketTask();
      foreach ($DB->request('glpi_ticketplannings') as $data) {
         if ($task->getFromDB($data['tickettasks_id'])) {
            $query = "UPDATE `glpi_tickettasks`
                      SET `begin` = ".((($data['begin'] == 'NULL') || is_null($data['begin']))
                                        ?'NULL':"'".$data['begin']."'").",
                          `end` = ".((($data['end'] == 'NULL') || is_null($data['end']))
                                      ?'NULL':"'".$data['end']."'").",
                          `users_id_tech` = '".$data['users_id']."',
                          `state` = '".$data['state']."'
                      WHERE `id` = '".$data['tickettasks_id']."'";
            $DB->queryOrDie($query, "0.83 migrate planning to glpi_tickettasks");
         }
      }

      $migration->dropTable("glpi_ticketplannings");


      $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Notification'));

      // Migrate templates
      $from = array('task.planning.user##', 'task.planning.begin##', 'task.planning.end##',
                    'task.planning.status##',);
      $to = array('task.user##', 'task.begin##', 'task.end##', 'task.status##',);

      $query = "SELECT `glpi_notificationtemplatetranslations`.*
                FROM `glpi_notificationtemplatetranslations`
                INNER JOIN `glpi_notificationtemplates`
                     ON (`glpi_notificationtemplates`.`id`
                           = `glpi_notificationtemplatetranslations`.`notificationtemplates_id`)
                WHERE `glpi_notificationtemplates`.`itemtype` = 'Ticket'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "UPDATE `glpi_notificationtemplatetranslations`
                         SET `subject` = '".addslashes(str_replace($from,$to,$data['subject']))."',
                             `content_text` = '".addslashes(str_replace($from,$to,
                                                                        $data['content_text']))."',
                             `content_html` = '".addslashes(str_replace($from,$to,
                                                                        $data['content_html']))."'
                         WHERE `id` = ".$data['id']."";
               $DB->queryOrDie($query, "0.83 fix tags usage for multi users");
            }
         }
      }
   }


   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `name` = 'Problems'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Problems', 'Problem', NOW())";
         $DB->queryOrDie($query, "0.83 add problem notification");
         $notid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                          (`notificationtemplates_id`, `language`, `subject`,
                           `content_text`,
                           `content_html`)
                   VALUES ($notid, '', '##problem.action## ##problem.title##',
                          '##IFproblem.storestatus=solved##
 ##lang.problem.url## : ##problem.urlapprove##
 ##lang.problem.solvedate## : ##problem.solvedate##
 ##lang.problem.solution.type## : ##problem.solution.type##
 ##lang.problem.solution.description## : ##problem.solution.description## ##ENDIFproblem.storestatus##
 ##ELSEproblem.storestatus## ##lang.problem.url## : ##problem.url## ##ENDELSEproblem.storestatus##

 ##lang.problem.description##

 ##lang.problem.title## &#160;:##problem.title##
 ##lang.problem.authors## &#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors## ##ELSEproblem.authors##--##ENDELSEproblem.authors##
 ##lang.problem.creationdate## &#160;:##problem.creationdate##
 ##IFproblem.assigntousers## ##lang.problem.assigntousers## &#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##
 ##lang.problem.status## &#160;: ##problem.status##
 ##IFproblem.assigntogroups## ##lang.problem.assigntogroups## &#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##
 ##lang.problem.urgency## &#160;: ##problem.urgency##
 ##lang.problem.impact## &#160;: ##problem.impact##
 ##lang.problem.priority## : ##problem.priority##
##IFproblem.category## ##lang.problem.category## &#160;:##problem.category## ##ENDIFproblem.category## ##ELSEproblem.category## ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##
 ##lang.problem.content## &#160;: ##problem.content##

##IFproblem.storestatus=closed##
 ##lang.problem.solvedate## : ##problem.solvedate##
 ##lang.problem.solution.type## : ##problem.solution.type##
 ##lang.problem.solution.description## : ##problem.solution.description##
##ENDIFproblem.storestatus##
 ##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##

##FOREACHtickets##
 [##ticket.date##] ##lang.problem.title## : ##ticket.title##
 ##lang.problem.content## ##ticket.content##

##ENDFOREACHtickets##
 ##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##

##FOREACHtasks##
 [##task.date##]
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##
',
                          '&lt;p&gt;##IFproblem.storestatus=solved##&lt;/p&gt;
&lt;div&gt;##lang.problem.url## : &lt;a href=\"##problem.urlapprove##\"&gt;##problem.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description## ##ENDIFproblem.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEproblem.storestatus## ##lang.problem.url## : &lt;a href=\"##problem.url##\"&gt;##problem.url##&lt;/a&gt; ##ENDELSEproblem.storestatus##&lt;/div&gt;
&lt;p class=\"description b\"&gt;&lt;strong&gt;##lang.problem.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.title##&lt;/span&gt;&#160;:##problem.title## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.authors##&lt;/span&gt;&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.creationdate##&lt;/span&gt;&#160;:##problem.creationdate## &lt;br /&gt; ##IFproblem.assigntousers## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.assigntousers##&lt;/span&gt;&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.status## &lt;/span&gt;&#160;: ##problem.status##&lt;br /&gt; ##IFproblem.assigntogroups## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.assigntogroups##&lt;/span&gt;&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.urgency##&lt;/span&gt;&#160;: ##problem.urgency##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.impact##&lt;/span&gt;&#160;: ##problem.impact##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.priority##&lt;/span&gt; : ##problem.priority## &lt;br /&gt;##IFproblem.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.category## &lt;/span&gt;&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.content##&lt;/span&gt;&#160;: ##problem.content##&lt;/p&gt;
&lt;p&gt;##IFproblem.storestatus=closed##&lt;br /&gt;&lt;span style=\"text-decoration: underline;\"&gt;&lt;strong&gt;&lt;span style=\"color: #888888;\"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.problem.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description##&lt;br /&gt;##ENDIFproblem.storestatus##&lt;/p&gt;
&lt;div class=\"description b\"&gt;##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##&lt;/div&gt;
&lt;p&gt;##FOREACHtickets##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##ticket.date##] &lt;em&gt;##lang.problem.title## : &lt;a href=\"##ticket.url##\"&gt;##ticket.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.content## &lt;/span&gt; ##ticket.content##
&lt;p&gt;##ENDFOREACHtickets##&lt;/p&gt;
&lt;div class=\"description b\"&gt;##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class=\"description b\"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;')";
         $DB->queryOrDie($query, "0.83 add problem notification translation");


         $notifications = array('new'         => array(),
                                'update'      => array(Notification::ASSIGN_TECH,
                                                       Notification::OLD_TECH_IN_CHARGE),
                                'solved'      => array(),
                                'add_task'    => array(),
                                'update_task' => array(),
                                'delete_task' => array(),
                                'closed'      => array(),
                                'delete'      => array());

         $notif_names   = array('new'         => 'New Problem',
                                'update'      => 'Update Problem',
                                'solved'      => 'Resolve Problem',
                                'add_task'    => 'Add Task',
                                'update_task' => 'Update Task',
                                'delete_task' => 'Delete Task',
                                'closed'      => 'Close Problem',
                                'delete'      => 'Delete Problem');

         foreach ($notifications as $key => $val) {
            $notifications[$key][] = Notification::AUTHOR;
            $notifications[$key][] = Notification::GLOBAL_ADMINISTRATOR;
            $notifications[$key][] = Notification::OBSERVER;
         }

         foreach ($notifications as $type => $targets) {
            $query = "INSERT INTO `glpi_notifications`
                             (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                              `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                              `date_mod`)
                      VALUES ('".$notif_names[$type]."', 0, 'Problem', '$type', 'mail',
                              $notid, '', 1, 1, NOW())";
            $DB->queryOrDie($query, "0.83 add problem $type notification");
            $notifid = $DB->insert_id();

            foreach ($targets as $target) {
               $query = "INSERT INTO `glpi_notificationtargets`
                                (`id`, `notifications_id`, `type`, `items_id`)
                         VALUES (NULL, $notifid, ".Notification::USER_TYPE.", $target);";
               $DB->queryOrDie($query, "0.83 add problem $type notification target");
            }
         }
      }
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'Clean Vlans'));

   // Clean `glpi_networkports_vlans` datas (`networkports_id` whithout networkports)
   $query = "DELETE
             FROM `glpi_networkports_vlans`
             WHERE `networkports_id` NOT IN (SELECT `id`
                                             FROM `glpi_networkports`)";
   $DB->queryOrDie($query, "0.83 clean networkports_vlans datas");

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Rename Solution objects'));

   // rename glpi_ticketsolutiontypes to glpi_solutiontypes
   $migration->renameTable('glpi_ticketsolutiontypes', 'glpi_solutiontypes');
   // rename glpi_ticketsolutiontemplates to glpi_solutiontemplates
   $migration->renameTable('glpi_ticketsolutiontemplates', 'glpi_solutiontemplates');

   $migration->changeField('glpi_tickets', 'ticketsolutiontypes_id', 'solutiontypes_id',
                           'integer');
   $migration->changeField('glpi_solutiontemplates', 'ticketsolutiontypes_id', 'solutiontypes_id',
                           'integer');

   $migration->changeField('glpi_tickets_users', 'use_notification', 'use_notification',
                           'bool', array('value' => '1'));


   // to have correct name of key
   $migration->dropKey('glpi_tickets', 'ticketsolutiontypes_id');
   $migration->migrationOneTable('glpi_tickets');
   $migration->addKey('glpi_tickets', 'solutiontypes_id');
   $migration->dropKey('glpi_solutiontemplates', 'ticketsolutiontypes_id');
   $migration->migrationOneTable('glpi_solutiontemplates');
   $migration->addKey('glpi_solutiontemplates', 'solutiontypes_id');

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Rename Category objects'));


   $migration->renameTable('glpi_ticketcategories','glpi_itilcategories');
   $migration->dropKey('glpi_itilcategories', 'ticketcategories_id');
   $migration->changeField('glpi_itilcategories', 'ticketcategories_id', 'itilcategories_id',
                           'integer');
   $migration->migrationOneTable('glpi_itilcategories');
   $migration->addKey('glpi_itilcategories', 'itilcategories_id');

   $migration->dropKey('glpi_tickets', 'ticketcategories_id');
   $migration->changeField('glpi_tickets', 'ticketcategories_id', 'itilcategories_id', 'integer');
   $migration->migrationOneTable('glpi_tickets');
   $migration->addKey('glpi_tickets', 'itilcategories_id');

   // Update Itemtype datas in tables
   $itemtype_tables = array("glpi_bookmarks", "glpi_bookmarks_users", "glpi_displaypreferences");

   $typestochange = array('TicketSolutionTemplate' => 'SolutionTemplate',
                          'TicketSolutionType'     => 'SolutionType',
                          'TicketCategory'         => 'ITILCategory',);

   foreach ($itemtype_tables as $table) {
      foreach ($typestochange as $key => $val) {
         $query = "UPDATE `$table`
                   SET `itemtype` = '$val'
                   WHERE `itemtype` = '$key'";
         $DB->queryOrDie($query, "0.83 update itemtype of table $table for $val");
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Add various fields'));

   $migration->addField("glpi_states", 'states_id', "integer");
   $migration->addField("glpi_states", 'completename', "text");
   $migration->addField("glpi_states", 'level', "integer");
   $migration->addField("glpi_states", 'ancestors_cache', "longtext");
   $migration->addField("glpi_states", 'sons_cache', "longtext");
   $migration->migrationOneTable('glpi_states');
   $migration->addKey("glpi_states", array('states_id','name'), 'unicity');
   regenerateTreeCompleteName("glpi_states");

   $migration->addField("glpi_knowbaseitemcategories", 'ancestors_cache', "longtext");
   $migration->addField("glpi_knowbaseitemcategories", 'sons_cache', "longtext");

   $migration->changeField("glpi_authldaps", 'group_condition', 'group_condition', "text");

   $migration->dropKey("glpi_groups", 'ldap_value');
   $migration->changeField("glpi_groups", 'ldap_value', 'ldap_value', "text");

   $migration->dropKey("glpi_groups", 'ldap_group_dn');
   $migration->changeField("glpi_groups", 'ldap_group_dn', 'ldap_group_dn', "text");

   $migration->addField("glpi_groups", 'groups_id', "integer");
   $migration->addField("glpi_groups", 'completename', "text");
   $migration->addField("glpi_groups", 'level', "integer");
   $migration->addField("glpi_groups", 'ancestors_cache', "longtext");
   $migration->addField("glpi_groups", 'sons_cache', "longtext");

   $migration->migrationOneTable('glpi_groups');
   $migration->addKey("glpi_groups", 'ldap_value', '', 'INDEX', 200);
   $migration->addKey("glpi_groups", 'ldap_group_dn', '', 'INDEX', 200);
   $migration->addKey("glpi_groups", 'groups_id');
   regenerateTreeCompleteName("glpi_groups");

   $migration->addField("glpi_entitydatas", 'notification_subject_tag', "string",
                        array('after' => 'admin_reply_name'));

   $migration->addField("glpi_vlans", 'tag', "integer");
   $ADDTODISPLAYPREF['Vlan'] = array(11);

   $migration->addField("glpi_profiles", 'create_ticket_on_login', "bool");

   $migration->addField("glpi_reminders", 'begin_view_date', "datetime");
   $migration->addField("glpi_reminders", 'end_view_date', "datetime");

   // only to change latin1 to utf-8 if not done in update 0.68.3 to 0.71
   // because there is an index fulltext based on 2 fields (perhaps both are not in same encoding)
   $migration->changeField("glpi_knowbaseitems", 'answer', 'answer', "longtext");

   $migration->changeField("glpi_knowbaseitems", 'question', 'name', "text");

   $migration->addField("glpi_configs", "ajax_min_textsearch_load", "integer",
                        array('after' => 'use_ajax'));

   $migration->addField("glpi_configs", "use_anonymous_followups", "bool",
                        array('after' => 'use_anonymous_helpdesk'));

   $migration->addField("glpi_configs", "show_count_on_tabs", "bool", array('value' => '1'));

   $migration->addField("glpi_users", "show_count_on_tabs", "tinyint(1) NULL DEFAULT NULL");

   $migration->addField("glpi_configs", "refresh_ticket_list", "integer");

   $migration->addField("glpi_users", "refresh_ticket_list", "int(11) NULL DEFAULT NULL");

   $migration->addField("glpi_configs", "set_default_tech", "bool", array('value' => '1'));

   $migration->addField("glpi_users", "set_default_tech", "tinyint(1) NULL DEFAULT NULL");

   $migration->addField("glpi_reservations", "group", "integer");

   $migration->addKey("glpi_reservations", array('reservationitems_id', 'group'), "resagroup");


   /// Add document types
   $types = array('csv' => array('name' => 'Comma-Separated Values',
                                 'icon' => 'csv-dist.png'),
                  'svg' => array('name' => 'Scalable Vector Graphics',
                                 'icon' => 'svg-dist.png'),);

   foreach ($types as $ext => $data) {

      $query = "SELECT *
                FROM `glpi_documenttypes`
                WHERE `ext` = '$ext'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result) == 0) {
            $query = "INSERT INTO `glpi_documenttypes`
                             (`name`, `ext`, `icon`, `is_uploadable`, `date_mod`)
                      VALUES ('".$data['name']."', '$ext', '".$data['icon']."', '1', NOW())";
            $DB->queryOrDie($query, "0.83 add document type $ext");
         }
      }
   }
   /// Update icons
   $types = array('c'   => 'c-dist.png',
                  'h'   => 'h-dist.png',
                  'swf' => 'swf-dist.png',
                  'pas' => 'pas-dist.png',
                  'wmv' => 'wmv-dist.png',
                  'zip' => 'zip-dist.png',);

   foreach ($types as $ext => $icon) {
      $query = "SELECT `id`
                FROM `glpi_documenttypes`
                WHERE `ext` = '$ext'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result) == 1) {
            $query = "UPDATE `glpi_documenttypes`
                      SET `icon` = '$icon', `date_mod` = NOW()
                      WHERE `id` = '".$DB->result($result,0,0)."'";
            $DB->queryOrDie($query, "0.83 update icon for doc type $ext");
         }
      }
   }


   /// add missing indexes  for fields
   $migration->addKey("glpi_authldaps", "is_active");
   $migration->addKey("glpi_authmails", "is_active");
   $migration->addKey("glpi_ocsservers", "is_active");


   $migration->changeField("glpi_users", 'token','password_forget_token',
                           "char(40) NULL DEFAULT NULL");

   $migration->changeField("glpi_users", 'tokendate','password_forget_token_date',
                           "datetime");

   $migration->addField("glpi_users", "personal_token", 'string');

   $migration->addField("glpi_users", "personal_token_date", "datetime");

   $migration->addField("glpi_tickets", "is_deleted", "bool");
   $migration->addKey("glpi_tickets", "is_deleted");

   $migration->addField("glpi_contracts", "template_name", 'string');
   $migration->addField("glpi_contracts", "is_template", 'bool');


   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Give consumable to groups'));

   if ($migration->addField("glpi_consumables", "itemtype", "VARCHAR(100) DEFAULT NULL",
                            array("after" => "date_out",
                                  "update" => "'User'"))) {

      $migration->dropKey("glpi_consumables", 'users_id');
      $migration->changeField("glpi_consumables", 'users_id', 'items_id', 'integer');
      $migration->addKey("glpi_consumables", array('itemtype','items_id'),'item');
   }


   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Several emails for users'));

   // Several email per users
   if (!TableExists('glpi_useremails')) {
      $query = "CREATE TABLE `glpi_useremails` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `is_default` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `is_dynamic` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `email` varchar( 255 ) NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`users_id`,`email`),
                  KEY `email` (`email`),
                  KEY `is_default` (`is_default`),
                  KEY `is_dynamic` (`is_dynamic`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_useremails");
   }
   // Manage migration : populate is_default=1
   // and is_dynamic depending of authldap config / authtype / auths_id
   if (FieldExists("glpi_users", 'email', false)) {
      $query = "SELECT *
                FROM `glpi_users`
                WHERE `email` <> '' AND `email` IS NOT NULL";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $is_dynamic = 0;
               $ldap_servers = array();
               // manage is_dynamic :
               if ($data['authtype'] == Auth::MAIL) {
                  $is_dynamic = 1;
               } else if ((Auth::isAlternateAuth($data["authtype"]) && $data['auths_id'] >0)
                           || $data['authtype'] == Auth::LDAP) {
                  if (!isset($ldap_servers[$data['auths_id']])) {
                     $ldap_servers[$data['auths_id']] = 0;
                     $ldap = new AuthLDAP();
                     if ($ldap->getFromDB($data['auths_id'])) {
                        if (!empty($ldap->fields['email_field'])) {
                           $ldap_servers[$data['auths_id']] = 1;
                        }
                     }
                  }
                  $is_dynamic = $ldap_servers[$data['auths_id']];
               }
               $query2 = "INSERT INTO `glpi_useremails`
                                 (`users_id`, `is_default`, `is_dynamic`, `email`)
                          VALUES ('".$data['id']."','1','$is_dynamic','".addslashes($data['email'])."')";
               $DB->queryOrDie($query2, "0.83 move emails to  glpi_useremails");
            }
         }
      }
      // Drop email field from glpi_users
      $migration->dropField("glpi_users", 'email');
   }

   // check unicity for users email : unset rule and display warning
   foreach ($DB->request("glpi_fieldunicities",
                         "`itemtype` = 'User' AND `fields` LIKE '%email%'") as $data) {
      $query = "UPDATE `glpi_fieldunicities`
                SET `is_active` = '0'
                WHERE `id` = '".$data['id']."'";
      $DB->query($query);
      echo "<div class='red'><p>A unicity check use email for users. ";
      echo "Due to new feature permit several email per users, this rule have been disabled.</p></div>";
   }

   // multiple manager in groups
   $migration->changeField("glpi_authldaps", 'email_field', 'email1_field', 'string');
   $migration->addField("glpi_authldaps", 'email2_field','string');
   $migration->addField("glpi_authldaps", 'email3_field','string');
   $migration->addField("glpi_authldaps", 'email4_field','string');


   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Multiple managers for groups'));

   /// migration : multiple group managers
   $migration->addField("glpi_groups_users", "is_manager", 'bool');
   $migration->addKey("glpi_groups_users", "is_manager");
   $migration->migrationOneTable('glpi_groups_users');

   if (FieldExists("glpi_groups", 'users_id', false)) {
      $query = "SELECT *
                FROM `glpi_groups`
                WHERE `users_id` > 0";
      $user = new User();
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               if ($user->getFromDB($data['users_id'])) {
                  $query = "SELECT `id`
                            FROM `glpi_groups_users`
                            WHERE `groups_id` = '".$data['id']."'
                                 AND `users_id` = '".$data['users_id']."'";
                  if ($result2 = $DB->query($query)) {
                     // add manager to groups_users setting if not present
                     if ($DB->numrows($result2)==0) {
                        $query2 = "INSERT INTO`glpi_groups_users`
                                          (`users_id`, `groups_id`, `is_manager`)
                                   VALUES ('".$data['users_id']."' ,'".$data['id']."', '1')";
                        $DB->queryOrDie($query2, "0.83 insert manager of groups");
                     } else {
                        // Update user as manager if presnet in groups_users
                        $query2 = "UPDATE `glpi_groups_users`
                                   SET `is_manager` = '1'
                                   WHERE `groups_id` = '".$data['id']."'
                                         AND `users_id` = '".$data['users_id']."'";
                        $DB->queryOrDie($query2, "0.83 set manager of groups");
                     }
                  }
               }
            }
         }
      }

      // Drop field glpi_groups
      $migration->dropField("glpi_groups", 'users_id');

   }


   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Add entities information on document links'));

   if ($migration->addField("glpi_documents_items", "entities_id", "integer")) {
      $migration->addField("glpi_documents_items", "is_recursive", "bool");
      $migration->migrationOneTable('glpi_documents_items');

      $query_doc_i = "UPDATE `glpi_documents_items` as `doc_i`
                      INNER JOIN `glpi_documents` as `doc`
                        ON  `doc`.`id` = `doc_i`.`documents_id`
                      SET `doc_i`.`entities_id` = `doc`.`entities_id`,
                         `doc_i`.`is_recursive` = `doc`.`is_recursive`";
      $DB->queryOrDie($query_doc_i,
                      "0.83 update entities_id and is_recursive in glpi_documents_items");

      /// create index for search count on tab
      $migration->dropKey("glpi_documents_items", "item");
      $migration->migrationOneTable('glpi_documents_items');
      $migration->addKey("glpi_documents_items",
                         array('itemtype', 'items_id', 'entities_id', 'is_recursive'),
                         'item');
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'RuleTicket'));

   $changes['RuleTicket'] = array('ticketcategories_id' => 'itilcategories_id');

   $DB->query("SET SESSION group_concat_max_len = 4194304;");
   foreach ($changes as $ruletype => $tab) {
      // Get rules
      $query = "SELECT GROUP_CONCAT(`id`)
                FROM `glpi_rules`
                WHERE `sub_type` = '".$ruletype."'
                GROUP BY `sub_type`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            // Get rule string
            $rules = $DB->result($result,0,0);
            // Update actions
            foreach ($tab as $old => $new) {
               $query = "UPDATE `glpi_ruleactions`
                         SET `field` = '$new'
                         WHERE `field` = '$old'
                               AND `rules_id` IN ($rules)";

               $DB->queryOrDie($query, "0.83 update datas for rules actions");
            }
            // Update criterias
            foreach ($tab as $old => $new) {
               $query = "UPDATE `glpi_rulecriterias`
                         SET `criteria` = '$new'
                         WHERE `criteria` = '$old'
                               AND `rules_id` IN ($rules)";
               $DB->queryOrDie($query, "0.83 update datas for rules criterias");
            }
         }
      }
   }


   $migration->displayMessage(sprintf(__('Data migration - %s'),'Ticket templates'));

   $default_ticket_template = 0;

   if (!TableExists('glpi_tickettemplates')) {
      $query = "CREATE TABLE `glpi_tickettemplates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar( 255 ) NULL DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `comment` TEXT DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_tickettemplates");

      $query = "INSERT INTO `glpi_tickettemplates`
                       (`name`, `is_recursive`)
                VALUES ('Default', 1)";
      $DB->queryOrDie($query, "0.83 add default ticket template");
      $default_ticket_template = $DB->insert_id();

   }

   $migration->addField('glpi_itilcategories', 'tickettemplates_id_incident', "integer");
   $migration->addKey('glpi_itilcategories', 'tickettemplates_id_incident');

   $migration->addField('glpi_itilcategories', 'tickettemplates_id_demand', "integer");
   $migration->addKey('glpi_itilcategories', 'tickettemplates_id_demand');

   $migration->addField('glpi_itilcategories', 'is_incident', "integer", array('value' => 1));
   $migration->addKey('glpi_itilcategories', 'is_incident');

   $migration->addField('glpi_itilcategories', 'is_request', "integer",  array('value' => 1));
   $migration->addKey('glpi_itilcategories', 'is_request');

   $migration->addField('glpi_itilcategories', 'is_problem', "integer",  array('value' => 1));
   $migration->addKey('glpi_itilcategories', 'is_problem');

   if (!TableExists('glpi_tickettemplatehiddenfields')) {
      $query = "CREATE TABLE `glpi_tickettemplatehiddenfields` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickettemplates_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `num` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `unicity` (`tickettemplates_id`,`num`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_tickettemplatehiddenfields");
   }

   if (!TableExists('glpi_tickettemplatepredefinedfields')) {
      $query = "CREATE TABLE `glpi_tickettemplatepredefinedfields` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickettemplates_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `num` int(11) NOT NULL DEFAULT '0',
                  `value` TEXT DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `unicity` (`tickettemplates_id`,`num`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_tickettemplatepredefinedfields");
   }

   if (!TableExists('glpi_tickettemplatemandatoryfields')) {
      $query = "CREATE TABLE `glpi_tickettemplatemandatoryfields` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickettemplates_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `num` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `unicity` (`tickettemplates_id`,`num`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_tickettemplatemandatoryfields");

      /// Add mandatory fields to default template
      if ($default_ticket_template > 0) {
         foreach ($DB->request('glpi_configs') as $data) {
            if (isset($data['is_ticket_title_mandatory']) && $data['is_ticket_title_mandatory']) {
               $query = "INSERT INTO `glpi_tickettemplatemandatoryfields`
                                (`tickettemplates_id`, `num`)
                         VALUES ('$default_ticket_template', 1)";
               $DB->queryOrDie($query, "0.83 add mandatory field for default ticket template");
            }
            if (isset($data['is_ticket_content_mandatory']) && $data['is_ticket_content_mandatory']) {
               $query = "INSERT INTO `glpi_tickettemplatemandatoryfields`
                                (`tickettemplates_id`, `num`)
                         VALUES ('$default_ticket_template', 21)";
               $DB->queryOrDie($query, "0.83 add mandatory field for default ticket template");
            }
            if (isset($data['is_ticket_category_mandatory']) && $data['is_ticket_category_mandatory']) {
               $query = "INSERT INTO `glpi_tickettemplatemandatoryfields`
                                (`tickettemplates_id`, `num`)
                         VALUES ('$default_ticket_template', 7)";
               $DB->queryOrDie($query, "0.83 add mandatory field for default ticket template");
            }
         }

         // Update itil categories
         $migration->migrationOneTable('glpi_itilcategories');
         $query = "UPDATE `glpi_itilcategories`
                   SET `tickettemplates_id_incident` = '$default_ticket_template',
                       `tickettemplates_id_demand` = '$default_ticket_template'";
         $DB->queryOrDie($query, "0.83 update default templates used by itil categories");
      }
   }
   // Drop global mandatory config
   $migration->dropField('glpi_configs', 'is_ticket_title_mandatory');
   $migration->dropField('glpi_configs', 'is_ticket_content_mandatory');
   $migration->dropField('glpi_configs', 'is_ticket_category_mandatory');

   $migration->addField('glpi_profiles', 'tickettemplate', "char", array('update' => '`sla`'));

   $migration->addField("glpi_entitydatas", "tickettemplates_id", 'integer',
                        array('value' => '-2'));

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'Tech Groups on items'));

   // Group of technicians in charge of Helpdesk items
   $migration->addField('glpi_computers', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_computers', 'groups_id_tech');

   $migration->addField('glpi_monitors', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_monitors', 'groups_id_tech');

   $migration->addField('glpi_networkequipments', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_networkequipments', 'groups_id_tech');

   $migration->addField('glpi_peripherals', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_peripherals', 'groups_id_tech');

   $migration->addField('glpi_phones', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_phones', 'groups_id_tech');

   $migration->addField('glpi_printers', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_printers', 'groups_id_tech');

   $migration->addField('glpi_softwares', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_softwares', 'groups_id_tech');

   $migration->addField('glpi_cartridgeitems', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_cartridgeitems', 'groups_id_tech');

   $migration->addField('glpi_consumableitems', 'groups_id_tech', "integer",
                        array('after' => "users_id_tech"));
   $migration->addKey('glpi_consumableitems', 'groups_id_tech');

   $migration->addField('glpi_printers', 'last_pages_counter', 'integer',
                        array('after' => 'init_pages_counter'));
   $migration->addKey('glpi_printers', 'last_pages_counter');

   $migration->displayMessage(sprintf(__('Data migration - %s'),'various cleaning DB'));

   // Clean ticket satisfactions
   $query = "DELETE
             FROM `glpi_ticketsatisfactions`
             WHERE `glpi_ticketsatisfactions`.`tickets_id` NOT IN (SELECT `glpi_tickets`.`id`
                                                                   FROM `glpi_tickets`)";
   $DB->queryOrDie($query, "0.83 clean glpi_ticketsatisfactions");

   // Clean unused slalevels
   $query = "DELETE
             FROM `glpi_slalevels_tickets`
             WHERE (`glpi_slalevels_tickets`.`tickets_id`, `glpi_slalevels_tickets`.`slalevels_id`)
                  NOT IN (SELECT `glpi_tickets`.`id`, `glpi_tickets`.`slalevels_id`
                          FROM `glpi_tickets`)";
   $DB->queryOrDie($query, "0.83 clean glpi_slalevels_tickets");

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'recurrent tickets'));

   if (!TableExists('glpi_ticketrecurrents')) {
      $query = "CREATE TABLE `glpi_ticketrecurrents` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar( 255 ) NULL DEFAULT NULL,
                  `comment` TEXT DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `is_active` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `tickettemplates_id` int(11) NOT NULL DEFAULT '0',
                  `begin_date` datetime DEFAULT NULL,
                  `periodicity` int(11) NOT NULL DEFAULT '0',
                  `create_before` int(11) NOT NULL DEFAULT '0',
                  `next_creation_date` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `is_active` (`is_active`),
                  KEY `tickettemplates_id` (`tickettemplates_id`),
                  KEY `next_creation_date` (`next_creation_date`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_ticketrecurrents");

      $ADDTODISPLAYPREF['TicketRecurrent'] = array(11, 12, 13, 15, 14);
   }


   if (!countElementsInTable('glpi_crontasks', "`itemtype`='TicketRecurrent' AND `name`='ticketrecurrent'")) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('TicketRecurrent', 'ticketrecurrent', 3600, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.83 populate glpi_crontasks for ticketrecurrent");
   }


   $migration->addField('glpi_profiles', 'ticketrecurrent', "char", array('update' => '`sla`'));


   $migration->displayMessage(sprintf(__('Data migration - %s'), 'various fields add'));


   // Ticket delegation
   $migration->addField('glpi_groups_users', 'is_userdelegate', 'bool');
   $migration->addKey('glpi_groups_users', 'is_userdelegate');


   //Software dictionnary update
   $migration->addField("glpi_rulecachesoftwares", "entities_id", "string");
   $migration->addField("glpi_rulecachesoftwares", "new_entities_id", "string");
   $migration->addField("glpi_entitydatas", "entities_id_software", 'integer',
                        array('value' => '-2'));

   // Groups perm
   $migration->addField('glpi_groups', 'is_requester', 'bool', array('value' => '1'));
   $migration->addField('glpi_groups', 'is_assign',    'bool', array('value' => '1'));
   $migration->addField('glpi_groups', 'is_notify',    'bool', array('value' => '1'));
   $migration->addField('glpi_groups', 'is_itemgroup', 'bool', array('value' => '1'));
   $migration->addField('glpi_groups', 'is_usergroup', 'bool', array('value' => '1'));

   $migration->addKey('glpi_groups', 'is_requester');
   $migration->addKey('glpi_groups', 'is_assign');
   $migration->addKey('glpi_groups', 'is_notify');
   $migration->addKey('glpi_groups', 'is_itemgroup');
   $migration->addKey('glpi_groups', 'is_usergroup');

   // Ticket solution by entity
   $migration->addfield('glpi_solutiontypes', 'entities_id', 'integer');
   $migration->addfield('glpi_solutiontypes', 'is_recursive', 'bool', array('value' => '1'));

   $migration->addKey('glpi_solutiontypes', 'entities_id');
   $migration->addKey('glpi_solutiontypes', 'is_recursive');

   // Fix solution template index
   $migration->dropKey('glpi_solutiontemplates', 'unicity');
   $migration->addKey('glpi_solutiontemplates', 'entities_id');

   // New index for count on tab
   $migration->addKey('glpi_ruleactions', array('field', 'value'), '', 'INDEX', 50);


   $migration->displayMessage(sprintf(__('Data migration - %s'), 'Create new default profiles'));

   $profiles = array('hotliner'   => array('name'                      => 'Hotliner',
                                           'interface'                 => 'central',
                                           'user'                      => 'r',
                                           'import_externalauth_users' => 'w',
                                           'create_ticket'             => '1',
                                           'assign_ticket'             => '1',
                                           'global_add_followups'      => '1',
                                           'add_followups'             => '1',
                                           'update_ticket'             => '1',
                                           'observe_ticket'            => '1',
                                           'show_all_ticket'           => '1',
                                           'show_full_ticket'          => '1',
                                           'show_all_problem'          => '1',
                                           'show_planning'             => '1',
                                           'statistic'                 => '1',
                                           'tickettemplate'            => 'r',
                                           'password_update'           => '1',
                                           'helpdesk_hardware'         => '3',
                                           'helpdesk_item_type'
                                                => addslashes('["Computer", "Monitor", "NetworkEquipment",' .
                                                              '"Peripheral", "Phone", "Printer",' .
                                                              '"Software"]'),
                                           'create_validation'         => '1',
                                           'update_own_followups'      => '1',
                                           'create_ticket_on_login'    => '1'),

                     'technician' => array('name'                      => 'Technician',
                                           'interface'                 => 'central',
                                           'password_update'           => '1',
                                           'computer'                  => 'w',
                                           'monitor'                   => 'w',
                                           'software'                  => 'w',
                                           'networking'                => 'w',
                                           'printer'                   => 'w',
                                           'peripheral'                => 'w',
                                           'cartridge'                 => 'w',
                                           'consumable'                => 'w',
                                           'phone'                     => 'w',
                                           'notes'                     => 'w',
                                           'document'                  => 'w',
                                           'knowbase'                  => 'w',
                                           'faq'                       => 'w',
                                           'reservation_helpdesk'      => '1',
                                           'reservation_central'       => 'w',
                                           'reports'                   => 'r',
                                           'view_ocsng'                => 'r',
                                           'sync_ocsng'                => 'w',
                                           'user'                      => 'w',
                                           'group'                     => 'r',
                                           'entity'                    => 'r',
                                           'transfer'                  => 'r',
                                           'reminder_public'           => 'w',
                                           'create_ticket'             => '1',
                                           'add_followups'             => '1',
                                           'global_add_followups'      => '1',
                                           'global_add_tasks'          => '1',
                                           'update_ticket'             => '1',
                                           'own_ticket'                => '1',
                                           'show_all_ticket'           => '1',
                                           'show_assign_ticket'        => '1',
                                           'show_full_ticket'          => '1',
                                           'observe_ticket'            => '1',
                                           'update_followups'          => '1',
                                           'update_tasks'              => '1',
                                           'show_planning'             => '1',
                                           'statistic'                 => '1',
                                           'helpdesk_hardware'         => '3',
                                           'helpdesk_item_type'
                                                => addslashes('["Computer", "Monitor", "NetworkEquipment",' .
                                                               '"Peripheral", "Phone", "Printer",' .
                                                               '"Software"]'),
                                           'import_externalauth_users' => 'w',
                                           'create_validation'         => '1',
                                           'sla'                       => 'r',
                                           'update_own_followups'      => '1',
                                           'show_my_problem'           => '1',
                                           'show_all_problem'          => '1',
                                           'tickettemplate'            => 'r',
                                           'ticketrecurrent'           => 'r'),

                     'supervisor' => array('name'                      => 'Supervisor',
                                           'interface'                 => 'central',
                                           'password_update'           => '1',
                                           'computer'                  => 'w',
                                           'monitor'                   => 'w',
                                           'software'                  => 'w',
                                           'networking'                => 'w',
                                           'printer'                   => 'w',
                                           'peripheral'                => 'w',
                                           'cartridge'                 => 'w',
                                           'consumable'                => 'w',
                                           'phone'                     => 'w',
                                           'notes'                     => 'w',
                                           'document'                  => 'w',
                                           'knowbase'                  => 'w',
                                           'faq'                       => 'w',
                                           'reservation_helpdesk'      => '1',
                                           'reservation_central'       => 'w',
                                           'reports'                   => 'r',
                                           'view_ocsng'                => 'r',
                                           'sync_ocsng'                => 'w',
                                           'entity_dropdown'           => 'w',
                                           'rule_ticket'               => 'r',
                                           'entity_rule_ticket'        => 'w',
                                           'user'                      => 'w',
                                           'group'                     => 'r',
                                           'entity'                    => 'r',
                                           'transfer'                  => 'r',
                                           'logs'                      => 'r',
                                           'reminder_public'           => 'w',
                                           'create_ticket'             => '1',
                                           'delete_ticket'             => '1',
                                           'add_followups'             => '1',
                                           'global_add_followups'      => '1',
                                           'global_add_tasks'          => '1',
                                           'update_ticket'             => '1',
                                           'update_priority'           => '1',
                                           'own_ticket'                => '1',
                                           'steal_ticket'              => '1',
                                           'assign_ticket'             => '1',
                                           'show_all_ticket'           => '1',
                                           'show_assign_ticket'        => '1',
                                           'show_full_ticket'          => '1',
                                           'observe_ticket'            => '1',
                                           'update_followups'          => '1',
                                           'update_tasks'              => '1',
                                           'show_planning'             => '1',
                                           'show_all_planning'         => '1',
                                           'statistic'                 => '1',
                                           'helpdesk_hardware'         => '3',
                                           'helpdesk_item_type'
                                                => addslashes('["Computer", "Monitor", "NetworkEquipment",' .
                                                               '"Peripheral", "Phone", "Printer",' .
                                                               '"Software"]'),
                                           'import_externalauth_users' => 'w',
                                           'rule_mailcollector'        => 'w',
                                           'validate_ticket'           => '1',
                                           'create_validation'         => '1',
                                           'calendar'                  => 'w',
                                           'sla'                       => 'w',
                                           'update_own_followups'      => '1',
                                           'delete_followups'          => '1',
                                           'show_my_problem'           => '1',
                                           'show_all_problem'          => '1',
                                           'edit_all_problem'          => '1',
                                           'tickettemplate'            => 'w',
                                           'ticketrecurrent'           => 'w')
                                 );

   foreach ($profiles as $profile => $data) {
      $query  = "INSERT INTO `glpi_profiles`
                         (`".implode("`, `",array_keys($data))."`)
                  VALUES ('".implode("', '",$data)."')";
      $DB->queryOrDie($query, "0.83 create new profile $profile");
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'),'Reminder visibility'));

   if (!TableExists('glpi_reminders_users')) {
      $query = "CREATE TABLE `glpi_reminders_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `reminders_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `reminders_id` (`reminders_id`),
                  KEY `users_id` (`users_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_reminders_users");
   }

   if (!TableExists('glpi_groups_reminders')) {
      $query = "CREATE TABLE `glpi_groups_reminders` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `reminders_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '-1',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `reminders_id` (`reminders_id`),
                  KEY `groups_id` (`groups_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)

                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_groups_reminders");
   }

   if (!TableExists('glpi_profiles_reminders')) {
      $query = "CREATE TABLE `glpi_profiles_reminders` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `reminders_id` int(11) NOT NULL DEFAULT '0',
                  `profiles_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '-1',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `reminders_id` (`reminders_id`),
                  KEY `profiles_id` (`profiles_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_profiles_reminders");
   }

   if (!TableExists('glpi_entities_reminders')) {
      $query = "CREATE TABLE `glpi_entities_reminders` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `reminders_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `reminders_id` (`reminders_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_entities_reminders");
   }

   /// Migrate datas for is_helpdesk_visible : add all helpdesk profiles / drop field is_helpdesk_visible
   if (FieldExists("glpi_reminders", 'is_helpdesk_visible', false)) {
      $query = "SELECT `id`
                FROM `glpi_reminders`
                WHERE `is_helpdesk_visible` = 1";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            // Grab helpdesk profiles
            $helpdesk_profiles = array();
            foreach ($DB->request("glpi_profiles",
                                  "`interface` = 'helpdesk' AND `reminder_public` = 'r'") as $data2) {
               $helpdesk_profiles[$data2['id']] = $data2['id'];
            }
            if (count($helpdesk_profiles)) {
               while ($data = $DB->fetch_assoc($result)) {
                  foreach ($helpdesk_profiles as $pid) {
                     $query = "INSERT INTO `glpi_profiles_reminders`
                                      (`reminders_id`, `profiles_id`)
                               VALUES ('".$data['id']."', '$pid');";
                     $DB->queryOrDie($query, "0.83 migrate data for is_helpdesk_visible drop on glpi_reminders");
                  }
               }
            }
         }
      }

      $migration->dropField("glpi_reminders", 'is_helpdesk_visible');

   }

   // Migrate datas for entities + drop fields : is_private / entities_id / is_recursive
   if (FieldExists("glpi_reminders", 'is_private', false)) {

      $query = "SELECT *
                FROM `glpi_reminders`
                WHERE `is_private` = 0";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "INSERT INTO `glpi_entities_reminders`
                                (`reminders_id`, `entities_id`, `is_recursive`)
                         VALUES ('".$data['id']."', '".$data['entities_id']."',
                                 '".$data['is_recursive']."');";
               $DB->queryOrDie($query, "0.83 migrate data for public reminders");
            }
         }
      }

      $migration->dropField("glpi_reminders", 'is_private');
      $migration->dropField("glpi_reminders", 'entities_id');
      $migration->dropField("glpi_reminders", 'is_recursive');
   }

   $ADDTODISPLAYPREF['Reminder'] = array(2,3,4,5,6,7);

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'KnowbaseItem visibility'));

   if (!TableExists('glpi_knowbaseitems_users')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `knowbaseitems_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `knowbaseitems_id` (`knowbaseitems_id`),
                  KEY `users_id` (`users_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_knowbaseitems_users");
   }

   if (!TableExists('glpi_groups_knowbaseitems')) {
      $query = "CREATE TABLE `glpi_groups_knowbaseitems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `knowbaseitems_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '-1',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `knowbaseitems_id` (`knowbaseitems_id`),
                  KEY `groups_id` (`groups_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)

                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_groups_knowbaseitems");
   }

   if (!TableExists('glpi_knowbaseitems_profiles')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_profiles` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `knowbaseitems_id` int(11) NOT NULL DEFAULT '0',
                  `profiles_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '-1',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `knowbaseitems_id` (`knowbaseitems_id`),
                  KEY `profiles_id` (`profiles_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_knowbaseitems_profiles");
   }

   if (!TableExists('glpi_entities_knowbaseitems')) {
      $query = "CREATE TABLE `glpi_entities_knowbaseitems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `knowbaseitems_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `knowbaseitems_id` (`knowbaseitems_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.83 add table glpi_entities_knowbaseitems");
   }

   /// Migrate datas for entities_id / is_recursive
   if (FieldExists("glpi_knowbaseitems", 'entities_id', false)) {
      $query = "SELECT *
                FROM `glpi_knowbaseitems`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "INSERT INTO `glpi_entities_knowbaseitems`
                                (`knowbaseitems_id`, `entities_id`, `is_recursive`)
                         VALUES ('".$data['id']."', '".$data['entities_id']."',
                                 '".$data['is_recursive']."');";
               $DB->queryOrDie($query, "0.83 migrate data for entities on glpi_entities_knowbaseitems");
            }
         }
      }

      $migration->dropField("glpi_knowbaseitems", 'entities_id');
      $migration->dropField("glpi_knowbaseitems", 'is_recursive');

   }

   // Plugins
   $migration->addField('glpi_plugins', 'license', 'string');


   $migration->migrationOneTable('glpi_entitydatas');
   $restore_root_entity_value = false;
   // create root entity if not exist with old default values
   if (countElementsInTable('glpi_entitydatas', 'entities_id=0') == 0) {
      $query = "INSERT INTO `glpi_entitydatas`
                       (`entities_id`, `entities_id_software`,
                        `autofill_order_date`, `autofill_delivery_date`, `autofill_buy_date`,
                        `autofill_use_date`, `autofill_warranty_date`,
                        `inquest_config`, `inquest_rate`, `inquest_delay`,
                        `tickettype`, `calendars_id`, `tickettemplates_id`,
                        `autoclose_delay`, `auto_assign_mode`,
                        `cartridges_alert_repeat`, `consumables_alert_repeat`,
                        `use_licenses_alert`, `use_infocoms_alert`, `notclosed_delay`,
                        `use_contracts_alert`, `use_reservations_alert`)
                VALUES (0, -10,
                        0, 0, 0,
                        0, 0,
                        1, 0, 0,
                        1, 0, '$default_ticket_template',
                        -1, -1,
                        -1, -1,
                        -1, -1, -1,
                        -1, -1)"; // -1 to keep config value - see 1647
      $DB->queryOrDie($query, "0.83 add entities_id 0 in glpi_entitydatas");
      $restore_root_entity_value = true;
   } else {
      $query = "UPDATE `glpi_entitydatas`
                SET `tickettemplates_id` = '$default_ticket_template'
                WHERE `entities_id` = 0
                      AND `tickettemplates_id` = -2";
      $DB->queryOrDie($query, "0.83 update tickettemplates_id for root entity in glpi_entitydatas");

      $query = "UPDATE `glpi_entitydatas`
                SET `entities_id_software` = -10
                WHERE `entities_id` = 0
                      AND `entities_id_software` = -2";
      $DB->queryOrDie($query, "0.83 update entities_id_software for root entity in glpi_entitydatas");

            // For root entity already exists in entitydatas in 0.78
      $query = "UPDATE `glpi_entitydatas`
                SET `tickettype` = 1
                WHERE `entities_id` = 0
                      AND `tickettype` = 0";
      $DB->queryOrDie($query, "0.83 update tickettype for root entity in glpi_entitydatas");

      $query = "UPDATE `glpi_entitydatas`
                SET `inquest_config` = 1
                WHERE `entities_id` = 0
                      AND `inquest_config` = 0";
      $DB->queryOrDie($query, "0.83 update inquest_config for root entity in glpi_entitydatas ");

      $query = "UPDATE `glpi_entitydatas`
                SET `inquest_rate` = 0
                WHERE `entities_id` = 0
                      AND `inquest_rate` = '-1'";
      $DB->queryOrDie($query, "0.83 update inquest_rate for root entity in glpi_entitydatas ");

      $query = "UPDATE `glpi_entitydatas`
                SET `inquest_delay` = 0
                WHERE `entities_id` = 0
                      AND `inquest_delay` = '-1'";
      $DB->queryOrDie($query, "0.83 update inquest_delay for root entity in glpi_entitydatas ");
   }

   // migration to new values for inherit parent (0 => -2)
   $field0 = array('calendars_id', 'tickettype', 'inquest_config');

   foreach ($field0 as $field_0) {
      if (FieldExists("glpi_entitydatas", $field_0, false) ) {
         $query = "UPDATE `glpi_entitydatas`
                   SET `$field_0` = '-2'
                   WHERE `$field_0` = '0'
                         AND `entities_id` > 0";
         $DB->queryOrDie($query, "0.83 new value for inherit parent 0 in glpi_entitydatas");
      }
   }

   // new default value
   $migration->changeField("glpi_entitydatas", "calendars_id", "calendars_id",
                           "int(11) NOT NULL DEFAULT '-2'");
   $migration->changeField("glpi_entitydatas", "tickettype", "tickettype",
                           "int(11) NOT NULL DEFAULT '-2'");
   $migration->changeField("glpi_entitydatas", "inquest_config", "inquest_config",
                           "int(11) NOT NULL DEFAULT '-2'");
   $migration->changeField("glpi_entitydatas", "inquest_rate", "inquest_rate",
                           "int(11) NOT NULL DEFAULT '0'");
   $migration->changeField("glpi_entitydatas", "inquest_delay", "inquest_delay",
                           "int(11) NOT NULL DEFAULT '-10'");

   // migration to new values for inherit parent (-1 => -2)
   $fieldparent = array('autofill_buy_date', 'autofill_delivery_date', 'autofill_warranty_date',
                        'autofill_order_date', 'autofill_use_date');

   foreach ($fieldparent as $field_parent) {
      if (FieldExists("glpi_entitydatas", $field_parent, false)) {
         $query = "UPDATE `glpi_entitydatas`
                   SET `$field_parent` = '-2'
                   WHERE `$field_parent` = '-1'";
         $DB->queryOrDie($query, "0.83 new value for inherit parent -1 in glpi_entitydatas");
      }
   }
   // new default value
   $migration->changeField("glpi_entitydatas", "autofill_buy_date", "autofill_buy_date",
                           'string', array('value' => '-2'));
   $migration->changeField("glpi_entitydatas", "autofill_delivery_date", "autofill_delivery_date",
                           'string', array('value' => '-2'));
   $migration->changeField("glpi_entitydatas", "autofill_warranty_date", "autofill_warranty_date",
                           'string', array('value' => '-2'));
   $migration->changeField("glpi_entitydatas", "autofill_order_date", "autofill_order_date",
                           'string', array('value' => '-2'));
   $migration->changeField("glpi_entitydatas", "autofill_use_date", "autofill_use_date",
                           'string', array('value' => '-2'));


   // migration to new values for inherit config
   $fieldconfig = array('auto_assign_mode', 'autoclose_delay', 'cartridges_alert_repeat',
                        'consumables_alert_repeat', 'notclosed_delay', 'use_contracts_alert',
                        'use_infocoms_alert', 'use_licenses_alert', 'use_reservations_alert');

   $query = "SELECT *
             FROM `glpi_configs`";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         if ($data = $DB->fetch_assoc($result)) {

            foreach ($fieldconfig as $field_config) {
               if (FieldExists("glpi_entitydatas", $field_config, false)
                   && FieldExists("glpi_configs", $field_config, false)) {
                  // value of general config
                  $query = "UPDATE `glpi_entitydatas`
                            SET `$field_config` = '".$data[$field_config]."'
                            WHERE `$field_config` = -1";
                  $DB->queryOrDie($query, "0.83 migrate data from config to glpi_entitydatas");

                  $migration->changeField("glpi_entitydatas", "$field_config", "$field_config",
                                          "int(11) NOT NULL DEFAULT '-2'");

                  $migration->dropField("glpi_configs", $field_config);
               }
            }
            if (FieldExists("glpi_entitydatas", "auto_assign_mode", false)) {
               // new value for never
               $query = "UPDATE `glpi_entitydatas`
                         SET `auto_assign_mode` = -10
                         WHERE `auto_assign_mode` = 0";
               $DB->queryOrDie($query, "0.83 change value Never in glpi_entitydatas for auto_assign_mode");
            }
         }
      }

   }

   // value of config in each entity
   $fieldconfig = array('default_contract_alert', 'default_infocom_alert', 'default_alarm_threshold');

   $query = "SELECT *
             FROM `glpi_configs`";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         if ($data = $DB->fetch_assoc($result)) {
            foreach ($fieldconfig as $field_config) {
               if (FieldExists("glpi_configs", $field_config, false)
                   && !FieldExists("glpi_entitydatas", $field_config, false)) {
                  // add config fields in entitydatas
                  $migration-> addField("glpi_entitydatas", $field_config, 'integer',
                                        array('update' => $data[$field_config],
                                              'value'  => ($field_config == "default_alarm_threshold"
                                                            ? 10 : 0)));

                  $migration->dropField("glpi_configs", $field_config);
               }
            }
         }
      }
   }


   if ($restore_root_entity_value) {
      $query = "UPDATE `glpi_entitydatas`
                SET `calendars_id` = 0
                WHERE `entities_id` = 0;";
      $DB->queryOrDie($query, "0.83 restore root entity default value");
   }

   $migration->addKey('glpi_computervirtualmachines', 'computers_id');
   $migration->addKey('glpi_computervirtualmachines', 'entities_id');
   $migration->addKey('glpi_computervirtualmachines', 'name');
   $migration->addKey('glpi_computervirtualmachines', 'virtualmachinestates_id');
   $migration->addKey('glpi_computervirtualmachines', 'virtualmachinesystems_id');
   $migration->addKey('glpi_computervirtualmachines', 'vcpu');
   $migration->addKey('glpi_computervirtualmachines', 'ram');

//   $ADDTODISPLAYPREF['KnowbaseItem'] = array(2,3,4,5,6,7);

   $renametables = array('TicketSolutionType'     => 'SolutionType',
                         'TicketSolutionTemplate' => 'SolutionTemplate',
                         'TicketCategory'         => 'ITILCategory');

   $itemtype_tables = array("glpi_bookmarks"          => 'itemtype',
                            "glpi_bookmarks_users"    => 'itemtype',
                            "glpi_displaypreferences" => 'itemtype',
                            "glpi_logs"               => 'itemtype',
                            "glpi_events"             => 'type',);

   foreach ($itemtype_tables as $table => $field) {
      foreach ($renametables as $key => $val) {
            $query = "UPDATE `$table`
                      SET `$field` = '".$val."'
                      WHERE `$field` = '".$key."'";
            $DB->queryOrDie($query, "0.83 update itemtype of table $table for $val");
      }
   }

   // ************ Keep it at the end **************
   $migration->displayMessage(sprintf(__('Data migration - %s'),'glpi_displaypreferences'));

   // Change is_recursive index
   $query = ("UPDATE `glpi_displaypreferences`
              SET `num` = '86'
              WHERE `itemtype` = 'Group'
                    AND `num` = '6'");
   $DB->query($query);

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query = "SELECT DISTINCT `users_id`
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "SELECT MAX(`rank`)
                         FROM `glpi_displaypreferences`
                         WHERE `users_id` = '".$data['users_id']."'
                               AND `itemtype` = '$type'";
               $result = $DB->query($query);
               $rank   = $DB->result($result,0,0);
               $rank++;

               foreach ($tab as $newval) {
                  $query = "SELECT *
                            FROM `glpi_displaypreferences`
                            WHERE `users_id` = '".$data['users_id']."'
                                  AND `num` = '$newval'
                                  AND `itemtype` = '$type'";
                  if ($result2=$DB->query($query)) {
                     if ($DB->numrows($result2)==0) {
                        $query = "INSERT INTO `glpi_displaypreferences`
                                         (`itemtype` ,`num` ,`rank` ,`users_id`)
                                  VALUES ('$type', '$newval', '".$rank++."',
                                          '".$data['users_id']."')";
                        $DB->query($query);
                     }
                  }
               }
            }

         } else { // Add for default user
            $rank = 1;
            foreach ($tab as $newval) {
               $query = "INSERT INTO `glpi_displaypreferences`
                                (`itemtype` ,`num` ,`rank` ,`users_id`)
                         VALUES ('$type', '$newval', '".$rank++."', '0')";
               $DB->query($query);
            }
         }
      }
   }

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}
?>
