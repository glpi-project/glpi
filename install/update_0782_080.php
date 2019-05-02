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

/**
 * Update from 0.78.2 to 0.80
 *
 * @return bool for success (will die for most error)
**/
function update0782to080() {
   global $DB,$migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.80'));
   $migration->setVersion('0.80');

   $backup_tables = false;
   $newtables     = ['glpi_calendars',
                          'glpi_calendars_holidays',
                          'glpi_calendarsegments',
                          'glpi_computervirtualmachines',
                          'glpi_computers_softwarelicenses',
                          'glpi_fieldblacklists',
                          'glpi_fieldunicities',
                          'glpi_groups_tickets',
                          'glpi_holidays',
                          'glpi_rulecacheprinters',
                          'glpi_slas',
                          'glpi_slalevels',
                          'glpi_slalevels_tickets',
                          'glpi_slalevelactions',
                          'glpi_tickets_tickets',
                          'glpi_tickets_users',
                          'glpi_ticketsatisfactions',
                          'glpi_ticketsolutiontemplates',
                          'glpi_virtualmachinestates',
                          'glpi_virtualmachinesystems',
                          'glpi_virtualmachinetypes'];

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if ($DB->tableExists($new_table)) {
         if ($DB->tableExists("backup_$new_table")) {
            $query = "DROP TABLE `backup_".$new_table."`";
            $DB->queryOrDie($query, "0.80 drop backup table backup_$new_table");
         }
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.", true);
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Calendar')); // Updating schema

   $default_calendar_id = 0;
   if (!$DB->tableExists('glpi_calendars')) {
      $query = "CREATE TABLE `glpi_calendars` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `entities_id` int(11) NOT NULL default '0',
                  `is_recursive` tinyint(1) NOT NULL default '0',
                  `comment` TEXT DEFAULT NULL ,
                  `date_mod` DATETIME DEFAULT NULL ,
                  `cache_duration` TEXT DEFAULT NULL ,
                  PRIMARY KEY  (`id`),
                  KEY `name` (`name`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `date_mod` (`date_mod`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_calendars");

      $ADDTODISPLAYPREF['Calendar'] = [19];
      // Create default calendar : use existing config planning_begin _end
      $query = "INSERT INTO `glpi_calendars`
                       (`name`, `entities_id`, `is_recursive`, `comment`)
                VALUES ('Default', 0, 1, 'Default calendar');";
      $DB->queryOrDie($query, "0.80 add default glpi_calendars");
      $default_calendar_id = $DB->insertId();
   }

   if (!$DB->tableExists('glpi_calendarsegments')) {
      $query = "CREATE TABLE `glpi_calendarsegments` (
                  `id` int(11) NOT NULL auto_increment,
                  `calendars_id` int(11) NOT NULL default '0',
                  `entities_id` int(11) NOT NULL default '0',
                  `is_recursive` tinyint(1) NOT NULL default '0',
                  `day` tinyint(1) NOT NULL default '1' COMMENT 'numer of the day based on date(w)',
                  `begin` time DEFAULT NULL,
                  `end` time DEFAULT NULL,
                  PRIMARY KEY  (`id`),
                  KEY `calendars_id` (`calendars_id`),
                  KEY `day` (`day`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_calendarsegments");

      // add defautl days : from monday to friday
      if ($default_calendar_id>0) {
         $query = "SELECT `planning_begin`, `planning_end`
                   FROM `glpi_configs`
                   WHERE `id` = '1'";

         if ($result = $DB->query($query)) {
            $begin = $DB->result($result, 0, 'planning_begin');
            $end   = $DB->result($result, 0, 'planning_end');

            if ($begin < $end) {
               for ($i=1; $i<6; $i++) {
                  $query = "INSERT INTO `glpi_calendarsegments`
                                   (`calendars_id`, `day`, `begin`, `end`)
                            VALUES ($default_calendar_id, $i, '$begin', '$end')";
                  $DB->queryOrDie($query, "0.80 add default glpi_calendarsegments");
               }
            }
         }

         // Update calendar
         $calendar = new Calendar();
         if ($calendar->getFromDB($default_calendar_id)) {
            $query = "UPDATE `glpi_calendars`
                      SET `cache_duration` = '".exportArrayToDB($calendar->getDaysDurations())."'
                      WHERE `id` = '$default_calendar_id'";
                  $DB->queryOrDie($query, "0.80 update default calendar cache");
         }
      }

   }

   // Holidays : wrong management : may be a group of several days : will be easy to managed holidays
   if (!$DB->tableExists('glpi_holidays')) {
      $query = "CREATE TABLE `glpi_holidays` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `entities_id` int(11) NOT NULL default '0',
                  `is_recursive` tinyint(1) NOT NULL default '0',
                  `comment` TEXT DEFAULT NULL ,
                  `begin_date` date default NULL,
                  `end_date` date default NULL,
                  `is_perpetual` tinyint(1) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `name` (`name`),
                  KEY `begin_date` (`begin_date`),
                  KEY `end_date` (`end_date`),
                  KEY `is_perpetual` (`is_perpetual`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_holidays");

      $ADDTODISPLAYPREF['Holiday'] = [11, 12, 13];

   }

   if (!$DB->tableExists('glpi_calendars_holidays')) {
      $query = "CREATE TABLE `glpi_calendars_holidays` (
                  `id` int(11) NOT NULL auto_increment,
                  `calendars_id` int(11) NOT NULL default '0',
                  `holidays_id` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  UNIQUE KEY `unicity` (`calendars_id`,`holidays_id`),
                  KEY `holidays_id` (`holidays_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_calendars_holidays");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'SLA')); // Updating schema

   if (!$DB->tableExists('glpi_slas')) {
      $query = "CREATE TABLE `glpi_slas` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  `entities_id` int(11) NOT NULL default '0',
                  `is_recursive` tinyint(1) NOT NULL default '0',
                  `comment` TEXT DEFAULT NULL ,
                  `resolution_time` int(11) NOT NULL,
                  `calendars_id` int(11) NOT NULL default '0',
                  `date_mod` datetime default NULL,
                  PRIMARY KEY  (`id`),
                  KEY `name` (`name`),
                  KEY `calendars_id` (`calendars_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `date_mod` (`date_mod`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_slas");

      $ADDTODISPLAYPREF['SLA'] = [4];

      // Get first Ticket template
      $query = "SELECT `id`
                FROM `glpi_notificationtemplates`
                WHERE `itemtype` LIKE 'Ticket%'
                ORDER BY `id` ASC";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {

            $query = "INSERT INTO `glpi_notifications`
                             (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                              `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                              `date_mod`)
                      VALUES ('Ticket Recall', 0, 'Ticket', 'recall', 'mail',
                              ".$DB->result($result, 0, 0).", '', 1, 1,
                              NOW());";
            $DB->queryOrDie($query, "0.80 insert notification");
         }
      }

   }
   if (!$DB->tableExists('glpi_slalevels')) {
      $query = "CREATE TABLE `glpi_slalevels` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) collate utf8_unicode_ci default NULL,
                  `slas_id` int(11) NOT NULL default '0',
                  `execution_time` int(11) NOT NULL,
                  `is_active` tinyint(1) NOT NULL default '1',
                  PRIMARY KEY  (`id`),
                  KEY `name` (`name`),
                  KEY `is_active` (`is_active`),
                  KEY `slas_id` (`slas_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_slalevels");
   }

   if (!$DB->tableExists('glpi_slalevelactions')) {
      $query = "CREATE TABLE `glpi_slalevelactions` (
                  `id` int(11) NOT NULL auto_increment,
                  `slalevels_id` int(11) NOT NULL default '0',
                  `action_type` varchar(255) collate utf8_unicode_ci default NULL,
                  `field` varchar(255) collate utf8_unicode_ci default NULL,
                  `value` varchar(255) collate utf8_unicode_ci default NULL,
                  PRIMARY KEY  (`id`),
                  KEY `slalevels_id` (`slalevels_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
      $DB->queryOrDie($query, "0.80 create glpi_slalevelactions");
   }

   $migration->addField("glpi_profiles", "calendar", "CHAR( 1 ) NULL",
                        ['update' => "`entity_dropdown`"]);
   $migration->addField("glpi_profiles", "sla", "CHAR( 1 ) NULL",
                        ['update' => "`entity_rule_ticket`"]);

   $migration->addField("glpi_tickets", "slas_id", "INT( 11 ) NOT NULL DEFAULT 0");
   $migration->addKey("glpi_tickets", "slas_id");
   $migration->addField("glpi_tickets", "slalevels_id", "INT( 11 ) NOT NULL DEFAULT 0");
   $migration->addKey("glpi_tickets", "slalevels_id");

   if ($migration->addField("glpi_tickets", "due_date", "datetime default NULL")) {
      $ADDTODISPLAYPREF['Ticket'] = [18];
   }

   $migration->addKey("glpi_tickets", "due_date");
   $migration->addField("glpi_tickets", "begin_waiting_date", "datetime default NULL");
   $migration->addField("glpi_tickets", "sla_waiting_duration", "INT( 11 ) NOT NULL DEFAULT 0");

   if (!$DB->tableExists('glpi_slalevels_tickets')) {
      $query = "CREATE TABLE `glpi_slalevels_tickets` (
                  `id` int(11) NOT NULL auto_increment,
                  `tickets_id` int(11) NOT NULL default '0',
                  `slalevels_id` int(11) NOT NULL default '0',
                  `date` datetime default NULL,
                  PRIMARY KEY  (`id`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `slalevels_id` (`slalevels_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
      $DB->queryOrDie($query, "0.80 create glpi_slalevels_tickets");
   }

   if (!countElementsInTable('glpi_crontasks', ['itemtype' => 'SlaLevel_Ticket', 'name' => 'slaticket'])) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('SlaLevel_Ticket', 'slaticket', 300, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.80 populate glpi_crontasks for slaticket");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'PasswordForget')); // Updating schema

   $migration->addField("glpi_users", "token", "char( 40 ) NULL DEFAULT ''");
   $migration->addField("glpi_users", "tokendate", "datetime NULL DEFAULT NULL");

   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `name` = 'Password Forget'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Password Forget', 'User', NOW())";
         $DB->queryOrDie($query, "0.80 add password forget notification");
         $notid = $DB->insertId();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                          (`notificationtemplates_id`, `language`, `subject`,
                           `content_text`,
                           `content_html`)
                   VALUES ($notid, '', '##user.action##',
                          '##user.realname## ##user.firstname##

##lang.passwordforget.information##

##lang.passwordforget.link## ##user.passwordforgeturl##',
                          '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.information##&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.link## &lt;a title=\"##user.passwordforgeturl##\" href=\"##user.passwordforgeturl##\"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;')";
         $DB->queryOrDie($query, "0.80 add password forget notification translation");

         $query = "INSERT INTO `glpi_notifications`
                       (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                        `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                        `date_mod`)
                VALUES ('Password Forget', 0, 'User', 'passwordforget', 'mail',
                        $notid, '', 1, 1,
                        NOW())";
         $DB->queryOrDie($query, "0.80 add password forget notification");
         $notifid = $DB->insertId();

         $query = "INSERT INTO `glpi_notificationtargets`
                       (`id`, `notifications_id`, `type`, `items_id`)
                VALUES (NULL, $notifid, 1, 19);";
         $DB->queryOrDie($query, "0.80 add password forget notification target");
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Ticket')); // Updating schema

   $migration->addField("glpi_tickets", "ticket_waiting_duration", "INT( 11 ) NOT NULL DEFAULT 0");

   $migration->addField("glpi_entitydatas", "calendars_id", "INT( 11 ) NOT NULL DEFAULT 0");

   $migration->addField("glpi_tickets", "close_delay_stat", "INT( 11 ) NOT NULL DEFAULT 0",
                        ['update'    => "(UNIX_TIMESTAMP(`glpi_tickets`.`closedate`)
                                               - UNIX_TIMESTAMP(`glpi_tickets`.`date`))",
                              'condition' => " WHERE `glpi_tickets`.`status` = 'closed'
                                                     AND `glpi_tickets`.`date` IS NOT NULL
                                                     AND `glpi_tickets`.`closedate` IS NOT NULL
                                                     AND `glpi_tickets`.`closedate` > `glpi_tickets`.`date`"]);

   $migration->addField("glpi_tickets", "solve_delay_stat", "INT( 11 ) NOT NULL DEFAULT 0",
                        ['update'    => "(UNIX_TIMESTAMP(`glpi_tickets`.`solvedate`)
                                               - UNIX_TIMESTAMP(`glpi_tickets`.`date`))",
                              'condition' =>" WHERE (`glpi_tickets`.`status` = 'closed'
                                                      OR `glpi_tickets`.`status` = 'solved')
                                                    AND `glpi_tickets`.`date` IS NOT NULL
                                                    AND `glpi_tickets`.`solvedate` IS NOT NULL
                                                    AND `glpi_tickets`.`solvedate` > `glpi_tickets`.`date`"]);

   if ($migration->addField("glpi_tickets", "takeintoaccount_delay_stat",
                            "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_tickets');

      // Manage stat computation for existing tickets
      // Solved tickets
      $query = "SELECT `glpi_tickets`.`id` AS ID,
                       MIN(UNIX_TIMESTAMP(`glpi_tickets`.`solvedate`)
                            - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS OPEN,
                       MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`)
                            - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST,
                       MIN(UNIX_TIMESTAMP(`glpi_tickettasks`.`date`)
                            - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST2
               FROM `glpi_tickets`
               LEFT JOIN `glpi_ticketfollowups`
                     ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`)
               LEFT JOIN `glpi_tickettasks`
                     ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`)
               WHERE (`glpi_tickets`.`status` = 'closed'
                      OR `glpi_tickets`.`status` = 'solved')
                     AND `glpi_tickets`.`solvedate` IS NOT NULL
               GROUP BY `glpi_tickets`.`id`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetchAssoc($result)) {
               $firstactiontime = min($data['OPEN'], $data['FIRST'], $data['FIRST2']);
               $firstactiontime = max(0, $firstactiontime);
               $query2 = "UPDATE `glpi_tickets`
                          SET `takeintoaccount_delay_stat` = '$firstactiontime'
                          WHERE `id` = '".$data['ID']."'";
               $DB->queryOrDie($query2, "0.80 update takeintoaccount_delay_stat values for #". $data['ID']);
            }
         }
      }
      // Not solved tickets
      $query = "SELECT `glpi_tickets`.`id` AS ID,
                       MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`)
                            - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST,
                       MIN(UNIX_TIMESTAMP(`glpi_tickettasks`.`date`)
                            - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST2
                FROM `glpi_tickets`
                LEFT JOIN `glpi_ticketfollowups`
                     ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`)
                LEFT JOIN `glpi_tickettasks`
                     ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE (`glpi_tickets`.`status` <> 'closed'
                       AND `glpi_tickets`.`status` <> 'solved')
                      OR `glpi_tickets`.`solvedate` IS NULL
                GROUP BY `glpi_tickets`.`id`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetchAssoc($result)) {
               $firstactiontime = min($data['FIRST'], $data['FIRST2']);
               $firstactiontime = max(0, $firstactiontime);
               $query2 = "UPDATE `glpi_tickets`
                          SET `takeintoaccount_delay_stat` = '$firstactiontime'
                          WHERE `id` = '".$data['ID']."'";
               $DB->queryOrDie($query2, "0.80 update takeintoaccount_delay_stat values for #".$data['ID']);
            }
         }
      }

   }

   // Put realtime in seconds
   $migration->addField("glpi_tickets", "actiontime", "INT( 11 ) NOT NULL DEFAULT 0",
                        ['update' => "ROUND(realtime * 3600)"]);

   $migration->dropField("glpi_tickets", "realtime");

   $migration->addField("glpi_tickettasks", "actiontime", "INT( 11 ) NOT NULL DEFAULT 0",
                        ['update' => "ROUND(realtime * 3600)"]);
   $migration->dropField("glpi_tickettasks", "realtime");

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Software')); // Updating schema

   if ($migration->addField("glpi_softwareversions", "operatingsystems_id",
                            "INT( 11 ) NOT NULL DEFAULT '0'")) {
      $migration->addKey("glpi_softwareversions", "operatingsystems_id");
      $migration->migrationOneTable('glpi_softwareversions');

      $query = "UPDATE `glpi_softwareversions`,
                        (SELECT `id`, `operatingsystems_id`
                         FROM `glpi_softwares`) AS SOFT
                SET `glpi_softwareversions`.`operatingsystems_id` = `SOFT`.`operatingsystems_id`
                WHERE `glpi_softwareversions`.`softwares_id` = `SOFT`.`id` ";
      $DB->queryOrDie($query, "0.80 transfer operatingsystems_id from glpi_softwares to glpi_softwareversions");

      $migration->dropField("glpi_softwares", "operatingsystems_id");
   }

   if (!isIndex("glpi_computers_softwareversions", "unicity")) {
      // clean datas
      $query = "SELECT `computers_id`,
                       `softwareversions_id`,
                       COUNT(*) AS CPT
               FROM `glpi_computers_softwareversions`
               GROUP BY `computers_id`, `softwareversions_id`
               HAVING CPT > 1";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               $query2 = "SELECT `id`
                          FROM `glpi_computers_softwareversions`
                          WHERE `computers_id` = '".$data['computers_id']."'
                                AND `softwareversions_id` = '".$data['softwareversions_id']."'
                          LIMIT 1";

               if ($result2= $DB->query($query2)) {
                  if ($DB->numrows($result2)) {
                     $keep_id=$DB->result($result2, 0, 0);
                     $query3 = "DELETE
                                FROM `glpi_computers_softwareversions`
                                WHERE `computers_id` = '".$data['computers_id']."'
                                      AND `softwareversions_id` = '".$data['softwareversions_id']."'
                                      AND `id` <> $keep_id";
                     $DB->queryOrDie($query3, "0.80 clean glpi_computers_softwareversions");
                  }
               }
            }
         }
      }

      $migration->addKey("glpi_computers_softwareversions",
                         ['computers_id', 'softwareversions_id'], 'unicity', "UNIQUE");
   }

   $migration->dropKey("glpi_computers_softwareversions", "computers_id");

   // For real count : copy template and deleted information
   $migration->addField("glpi_computers_softwareversions", "is_deleted",
                        "tinyint(1) NOT NULL DEFAULT 0");
   // Gain de temps pour les beta-testeurs
   if ($migration->addField("glpi_computers_softwareversions", "is_template",
                            "tinyint(1) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_computers_softwareversions');

      // Update datas
      $query = "SELECT DISTINCT `computers_id`
                FROM `glpi_computers_softwareversions`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            while ($data = $DB->fetchAssoc($result)) {
               $comp = new Computer();
               if ($comp->getFromDB($data['computers_id'])) {
                  $query = "UPDATE `glpi_computers_softwareversions`
                            SET `is_template` = '".$comp->getField('is_template')."',
                                `is_deleted` = '".$comp->getField('is_deleted')."'
                            WHERE `computers_id` = '".$data['computers_id']."';";
                  $DB->query($query);
               }
            }
         }
      }
   }

   if (!$DB->tableExists("glpi_computers_softwarelicenses")) {
      $query = "CREATE TABLE `glpi_computers_softwarelicenses` (
                  `id` int(11) NOT NULL auto_increment,
                  `computers_id` int(11) NOT NULL default '0',
                  `softwarelicenses_id` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `computers_id` (`computers_id`),
                  KEY `softwarelicenses_id` (`softwarelicenses_id`),
                  UNIQUE `unicity` ( `computers_id` , `softwarelicenses_id` )
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_computers_softwarelicenses");
   }

   if ($DB->fieldExists("glpi_softwarelicenses", "computers_id", false)) {
      $query = "SELECT *
                FROM `glpi_softwarelicenses`
                WHERE `computers_id` > 0
                      AND `computers_id` IS NOT NULL";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               $query = "INSERT INTO `glpi_computers_softwarelicenses`
                                (`computers_id`, `softwarelicenses_id`)
                         VALUES ('".$data['computers_id']."','".$data['id']."')";
               $DB->queryOrDie($query, "0.80 migrate data to computers_softwarelicenses table");
            }
         }
      }

      $migration->dropField("glpi_softwarelicenses", "computers_id");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Common')); // Updating schema

   $migration->addField("glpi_softwarelicenses", "date_mod", "DATETIME NULL");
   $migration->addKey("glpi_softwarelicenses", "date_mod");

   $migration->renameTable("glpi_cartridges_printermodels", "glpi_cartridgeitems_printermodels");

   $migration->addField("glpi_monitors", "have_hdmi",
                        "tinyint(1) NOT NULL DEFAULT 0",
                        ['after' => 'have_pivot']);
   $migration->addField("glpi_monitors", "have_displayport",
                        "tinyint(1) NOT NULL DEFAULT 0",
                        ['after' => 'have_hdmi']);

   $migration->dropField("glpi_configs", "dbreplicate_email");
   $migration->addField("glpi_configs", "auto_create_infocoms", "tinyint(1) NOT NULL DEFAULT 0");

   $migration->addField("glpi_configs", "csv_delimiter", "CHAR( 1 ) NOT NULL",
                        ['after'  => 'number_format',
                         'update' => "';'"]);

   $migration->addField("glpi_users", "csv_delimiter", "CHAR( 1 ) NULL",
                        ['after' => 'number_format']);
   $migration->addField("glpi_users", "names_format", "INT( 11 ) NULL DEFAULT NULL",
                        ['after' => 'number_format']);

   // drop car fait sur mauvais champ
   $migration->dropKey("glpi_budgets", "end_date");
   $migration->migrationOneTable("glpi_budgets");
   $migration->addKey("glpi_budgets", "end_date");

   $migration->addField("glpi_authldaps", "is_active", "TINYINT( 1 ) NOT NULL DEFAULT '0'",
                        ['update' => "'1'"]);
   $ADDTODISPLAYPREF['AuthLdap'] = [30];

   $migration->addField("glpi_authmails", "is_active", "TINYINT( 1 ) NOT NULL DEFAULT '0'",
                        ['update' => "'1'"]);
   $ADDTODISPLAYPREF['AuthMail'] = [6];

   $migration->addField("glpi_ocsservers", "is_active", "TINYINT( 1 ) NOT NULL DEFAULT '0'",
                        ['update' => "'1'"]);
   $ADDTODISPLAYPREF['OcsServer'] = [6];

   $migration->changeField("glpi_configs", "use_auto_assign_to_tech", "auto_assign_mode",
                           "INT( 11 ) NOT NULL DEFAULT '1'");

   $migration->addField("glpi_entitydatas", "auto_assign_mode", "INT( 11 ) NOT NULL DEFAULT '-1'");

   $migration->changeField("glpi_entitydatas", "ldapservers_id", "authldaps_id",
                           "INT( 11 ) NOT NULL DEFAULT '0'");

   $migration->addField("glpi_users", "user_dn", "TEXT DEFAULT NULL");

   $migration->addField("glpi_tickets", "users_id_lastupdater",
                        "INT( 11 ) NOT NULL DEFAULT 0",
                        ['after' => 'date_mod']);
   $migration->addKey("glpi_tickets", "users_id_lastupdater");

   $migration->addField("glpi_tickets", "type",
                        "INT( 11 ) NOT NULL DEFAULT 1",
                        ['after' => 'ticketcategories_id']);
   $migration->addKey("glpi_tickets", "type");
   $migration->addField("glpi_entitydatas", "tickettype", "INT( 11 ) NOT NULL DEFAULT 0");

   // Link between tickets
   if (!$DB->tableExists('glpi_tickets_tickets')) {
      $query = "CREATE TABLE `glpi_tickets_tickets` (
                  `id` int(11) NOT NULL auto_increment,
                  `tickets_id_1` int(11) NOT NULL default '0',
                  `tickets_id_2` int(11) NOT NULL default '0',
                  `link` int(11) NOT NULL default '1',
                  PRIMARY KEY  (`id`),
                  KEY `unicity` (`tickets_id_1`,`tickets_id_2`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_tickets_tickets");
   }

   //inquest
   if (!$DB->tableExists('glpi_ticketsatisfactions')) {
      $query = "CREATE TABLE `glpi_ticketsatisfactions` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  `date_begin` DATETIME NULL ,
                  `date_answered` DATETIME NULL ,
                  `satisfaction` INT(11) NULL ,
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `tickets_id` (`tickets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_ticketsatisfactions");
   }

   // config inquest by entity
   $migration->addField("glpi_entitydatas", "max_closedate", "DATETIME NULL");

   if (!countElementsInTable('glpi_crontasks', ['itemtype' => 'Ticket', 'name' => 'createinquest'])) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('Ticket', 'createinquest', 86400, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.80 populate glpi_crontasks for ticketsatisfaction");
   }

   $migration->addField("glpi_entitydatas", "inquest_config", "INT(11) NOT NULL DEFAULT '0'");
   $migration->addField("glpi_entitydatas", "inquest_rate", "INT(11) NOT NULL DEFAULT '-1'");
   $migration->addField("glpi_entitydatas", "inquest_delay", "INT(11) NOT NULL DEFAULT '-1'");
   $migration->addField("glpi_entitydatas", "inquest_URL", "VARCHAR( 255 ) NULL");

   $migration->addField("glpi_networkports", "comment", "TEXT COLLATE utf8_unicode_ci");

   $migration->addField("glpi_profiles", "rule_dictionnary_printer", "CHAR( 1 ) NULL",
                        ['update' => "`rule_dictionnary_software`"]);

   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `name` = 'Ticket Satisfaction'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Ticket Satisfaction', 'Ticket', NOW())";
         $DB->queryOrDie($query, "0.80 add ticket satisfaction notification");
         $notid = $DB->insertId();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                          (`notificationtemplates_id`, `language`, `subject`,
                           `content_text`, `content_html`)
                   VALUES ($notid, '', '##ticket.action## ##ticket.title##',
                          '##lang.ticket.title## : ##ticket.title##

##lang.ticket.closedate## : ##ticket.closedate##

##lang.satisfaction.text## ##ticket.urlsatisfaction##',

                          '&lt;p&gt;##lang.ticket.title## : ##ticket.title##&lt;/p&gt;
&lt;p&gt;##lang.ticket.closedate## : ##ticket.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href=\"##ticket.urlsatisfaction##\"&gt;##ticket.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;')";
         $DB->queryOrDie($query, "0.80 add ticket satisfaction notification translation");

         $query = "INSERT INTO `glpi_notifications`
                          (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                           `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                           `date_mod`)
                   VALUES ('Ticket Satisfaction', 0, 'Ticket', 'satisfaction', 'mail',
                           $notid, '', 1, 1,
                           NOW())";
         $DB->queryOrDie($query, "0.80 add ticket satisfaction notification");
         $notifid = $DB->insertId();

         //          $query = "INSERT INTO `glpi_notificationtargets`
         //                           (`id`, `notifications_id`, `type`, `items_id`)
         //                    VALUES (NULL, $notifid, 1, 3)";
         //          $DB->queryOrDie($query, "0.80 add ticket satisfaction notification target");
      }
   }

   //New infocom dates
   $migration->addField("glpi_infocoms", "order_date", "DATE NULL");
   $migration->addField("glpi_infocoms", "delivery_date", "DATE NULL");
   $migration->addField("glpi_infocoms", "inventory_date", "DATE NULL");
   $migration->addField("glpi_infocoms", "warranty_date", "DATE NULL",
                        ['update' => "`buy_date`"]);

   if (!$DB->tableExists('glpi_rulecacheprinters')) {
      $query = "CREATE TABLE `glpi_rulecacheprinters` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `old_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `manufacturer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                    `rules_id` int(11) NOT NULL DEFAULT '0',
                    `new_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `new_manufacturer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                    `ignore_ocs_import` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `is_global` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `old_value` (`old_value`),
                    KEY `rules_id` (`rules_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 add table glpi_rulecacheprinters");
   }

   $migration->addField("glpi_configs", "use_slave_for_search", "tinyint( 1 ) NOT NULL DEFAULT '0'");
   $migration->addField("glpi_configs", "admin_email_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL",
                        ['after' => 'admin_email']);
   $migration->addField("glpi_configs", "admin_reply_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL",
                        ['after' => 'admin_reply']);

   $migration->addField("glpi_entitydatas", "admin_email_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL",
                        ['after' => 'admin_email']);
   $migration->addField("glpi_entitydatas", "admin_reply_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL",
                        ['after' => 'admin_reply']);

   $migration->addField("glpi_notificationtemplates", "css", "text COLLATE utf8_unicode_ci");

   $migration->addField("glpi_configs", "url_maxlength",
                        "int(11) NOT NULL DEFAULT '30'",
                        ['after' => 'list_limit_max']);

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'Multi user group for tickets'));

   if (!$DB->tableExists('glpi_groups_tickets')) {
      $query = "CREATE TABLE `glpi_groups_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  KEY `unicity` (`tickets_id`,`type`,`groups_id`),
                  KEY `group` (`groups_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 add table glpi_groups_tickets");

      $query = "SELECT `id`, `groups_id`, `groups_id_assign`
                FROM `glpi_tickets`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               if ($data['groups_id']>0) {
                  $query = "INSERT INTO `glpi_groups_tickets`
                                   (`tickets_id`, `groups_id`,
                                    `type`)
                            VALUES ('".$data['id']."', '".$data['groups_id']."',
                                    '".CommonITILActor::REQUESTER."')";
                  $DB->queryOrDie($query, "0.80 migrate data to glpi_groups_tickets table");
               }
               if ($data['groups_id_assign']>0) {
                  $query = "INSERT INTO `glpi_groups_tickets`
                                  (`tickets_id`, `groups_id`,
                                   `type`)
                           VALUES ('".$data['id']."', '".$data['groups_id_assign']."',
                                   '".CommonITILActor::ASSIGN."')";
                  $DB->queryOrDie($query, "0.80 migrate data to glpi_groups_tickets table");
               }
            }
         }
      }

      $migration->dropField('glpi_tickets', 'groups_id');
      $migration->dropField('glpi_tickets', 'groups_id_assign');

      // Migrate templates
      $from = ['ticket.group##', 'ticket.assigntogroup##', 'ticket.assigntouser##',
                    'ticket.author.name##', 'ticket.author##'];
      $to   = ['ticket.groups##', 'ticket.assigntogroups##', 'ticket.assigntousers##',
                    'ticket.authors##', 'author.id##'];

      $query = "SELECT `glpi_notificationtemplatetranslations`.*
                FROM `glpi_notificationtemplatetranslations`
                INNER JOIN `glpi_notificationtemplates`
                     ON (`glpi_notificationtemplates`.`id`
                           = `glpi_notificationtemplatetranslations`.`notificationtemplates_id`)
                WHERE `glpi_notificationtemplates`.`itemtype` = 'Ticket'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               $query = "UPDATE `glpi_notificationtemplatetranslations`
                         SET `subject` = '".addslashes(str_replace($from, $to, $data['subject']))."',
                             `content_text` = '".addslashes(str_replace($from, $to,
                                                                        $data['content_text']))."',
                             `content_html` = '".addslashes(str_replace($from, $to,
                                                                        $data['content_html']))."'
                         WHERE `id` = ".$data['id']."";
               $DB->queryOrDie($query, "0.80 fix tags usage for multi users");
            }
         }
      }

   }

   if (!$DB->tableExists('glpi_tickets_users')) {
      $query = "CREATE TABLE `glpi_tickets_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  `use_notification` tinyint(1) NOT NULL DEFAULT '0',
                  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `user` (`users_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 add table glpi_tickets_users");

      $query = "SELECT `glpi_tickets`.`id`,
                       `glpi_tickets`.`users_id_assign`,
                       `glpi_tickets`.`users_id`,
                       `glpi_tickets`.`use_email_notification`,
                       `glpi_tickets`.`user_email`,
                       `glpi_users`.`email` AS EMAIL
                FROM `glpi_tickets`
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets`.`users_id`)";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               if ($data['users_id_assign']>0) {
                  $query = "INSERT INTO `glpi_tickets_users`
                                   (`tickets_id`, `users_id`,
                                    `type`, `use_notification`)
                            VALUES ('".$data['id']."', '".$data['users_id_assign']."',
                                    '".CommonITILActor::ASSIGN."', '1')";
                  $DB->queryOrDie($query, "0.80 migrate data to glpi_tickets_users table");
               }
               if ($data['users_id']>0
                   || ($data['use_email_notification'] && !empty($data['user_email']))) {
                  $user_id = 0;
                  if ($data['users_id']>0) {
                     $user_id = $data['users_id'];
                  }
                  $user_email = '';
                  if (strcasecmp($data['user_email'], $data['EMAIL'])!= 0) {
                     $user_email = addslashes($data['user_email']);
                  }

                  $query = "INSERT INTO `glpi_tickets_users`
                                   (`tickets_id`, `users_id`,`type`,
                                    `use_notification`, `alternative_email`)
                            VALUES ('".$data['id']."', '$user_id', '".CommonITILActor::REQUESTER."',
                                    '".$data['use_email_notification']."', '$user_email')";
                  $DB->queryOrDie($query, "0.80 migrate data to glpi_tickets_users table");
               }
            }
         }
      }

      $migration->dropField('glpi_tickets', 'users_id');
      $migration->dropField('glpi_tickets', 'users_id_assign');
      $migration->dropField('glpi_tickets', 'use_email_notification');
      $migration->dropField('glpi_tickets', 'user_email');

      // ADD observer when requester is set : 3>21 / 13>20 / 12 >22
      $fromto = [3  => 21, // USER
                      13 => 20, // GROUP
                      12 => 22]; // GROUP_SUPERVISOR
      foreach ($fromto as $from => $to) {
         $query = "SELECT *
                   FROM `glpi_notificationtargets`
                   INNER JOIN `glpi_notifications`
                     ON (`glpi_notifications`.`id` = `glpi_notificationtargets`.`notifications_id`)
                   WHERE `glpi_notifications`.`itemtype` = 'Ticket'
                         AND `glpi_notificationtargets`.`type` = '1'
                         AND `glpi_notificationtargets`.`items_id` = '$from'";

         if ($result=$DB->query($query)) {
            if ($DB->numrows($result)) {
               while ($data = $DB->fetchAssoc($result)) {
                  $query = "INSERT INTO `glpi_notificationtargets`
                                   (`items_id` ,`type` ,`notifications_id`)
                            VALUES ('$to', '1', '".$data['notifications_id']."')";
                  $DB->queryOrDie($query, "0.80 insert default notif for observer");
               }
            }
         }
      }
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'passwords encryption'));

   if ($migration->addField('glpi_configs', 'proxy_passwd',
                            'varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL')) {
      $migration->migrationOneTable('glpi_configs');

      $query = "SELECT `proxy_password`
                FROM `glpi_configs`
                WHERE `id` = '1'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value = $DB->result($result, 0, 0);
            if (!empty($value)) {
               $query = "UPDATE `glpi_configs`
                         SET `proxy_passwd` = '".addslashes(Toolbox::encrypt($value, GLPIKEY))."'
                         WHERE `id` = '1' ";
               $DB->queryOrDie($query, "0.80 update proxy_passwd in glpi_configs");
            }
         }
      }
      $migration->dropField('glpi_configs', 'proxy_password');
   }

   if ($migration->addField('glpi_configs', 'smtp_passwd',
                            'varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL')) {
      $migration->migrationOneTable('glpi_configs');

      $query = "SELECT `smtp_password`
                FROM `glpi_configs`
                WHERE `id` = '1'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value = $DB->result($result, 0, 0);
            if (!empty($value)) {
               $query = "UPDATE `glpi_configs`
                         SET `smtp_passwd` = '".addslashes(Toolbox::encrypt($value, GLPIKEY))."'
                         WHERE `id` = '1' ";
               $DB->queryOrDie($query, "0.80 update smtp_passwd in glpi_configs");
            }
         }
      }
      $migration->dropField('glpi_configs', 'smtp_password');
   }

   if ($migration->addField('glpi_authldaps', 'rootdn_passwd',
                            'varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL')) {
      $migration->migrationOneTable('glpi_authldaps');

      $query = "SELECT *
                FROM `glpi_authldaps`
                WHERE `rootdn_password` IS NOT NULL
                      AND `rootdn_password` <> ''";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               if (!empty($data['rootdn_password'])) {
                  $query = "UPDATE `glpi_authldaps`
                            SET `rootdn_passwd` = '".addslashes(Toolbox::encrypt($data['rootdn_password'],
                                                                                 GLPIKEY))."'
                            WHERE `id` = '".$data['id']."' ";
                  $DB->queryOrDie($query, "0.80 update rootdn_passwd in glpi_authldaps");
               }
            }
         }
      }
      $migration->dropField('glpi_authldaps', 'rootdn_password');
   }

   //Add date config management fields
   $migration->addField("glpi_entitydatas", "autofill_warranty_date",
                        "varchar(255) COLLATE utf8_unicode_ci DEFAULT '-1'",
                        ['update'    => "'0'",
                              'condition' => " WHERE `entities_id` = '0'"]);
   $migration->addField("glpi_entitydatas", "autofill_use_date",
                        "varchar(255) COLLATE utf8_unicode_ci DEFAULT '-1'",
                        ['update'    => "'0'",
                              'condition' => " WHERE `entities_id` = '0'"]);
   $migration->addField("glpi_entitydatas", "autofill_buy_date",
                        "varchar(255) COLLATE utf8_unicode_ci DEFAULT '-1'",
                        ['update'    => "'0'",
                              'condition' => " WHERE `entities_id` = '0'"]);
   $migration->addField("glpi_entitydatas", "autofill_delivery_date",
                        "varchar(255) COLLATE utf8_unicode_ci DEFAULT '-1'",
                        ['update'    => "'0'",
                              'condition' => " WHERE `entities_id` = '0'"]);
   $migration->addField("glpi_entitydatas", "autofill_order_date",
                        "varchar(255) COLLATE utf8_unicode_ci DEFAULT '-1'",
                        ['update'    => "'0'",
                              'condition' => " WHERE `entities_id` = '0'"]);

   if (!$DB->tableExists('glpi_fieldunicities')) {
      $query = "CREATE TABLE `glpi_fieldunicities` (
                  `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                  `name` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0',
                  `itemtype` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `entities_id` INT( 11 ) NOT NULL DEFAULT  '-1',
                  `fields` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `is_active` TINYINT( 1 ) NOT NULL DEFAULT '0',
                  `action_refuse` TINYINT( 1 ) NOT NULL DEFAULT '0',
                  `action_notify` TINYINT( 1 ) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci
                ) ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
                  COMMENT = 'Stores field unicity criterias'";
      $DB->queryOrDie($query, "0.80 add table glpi_fieldunicities");

      $ADDTODISPLAYPREF['FieldUnicity'] = [1, 80, 4, 3, 86, 30];
   }

   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `name` = 'Item not unique'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Item not unique', 'FieldUnicity', NOW())";
         $DB->queryOrDie($query, "0.80 add item not unique notification");
         $notid = $DB->insertId();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations` " .
                  "VALUES(NULL, $notid, '', '##lang.unicity.action##', " .
                  "'##lang.unicity.entity## : ##unicity.entity## \r\n\n" .
                  "##lang.unicity.itemtype## : ##unicity.itemtype## \r\n\n" .
                  "##lang.unicity.message## : ##unicity.message## \r\n\n" .
                  "##lang.unicity.action_user## : ##unicity.action_user## \r\n\n" .
                  "##lang.unicity.action_type## : ##unicity.action_type## \r\n\n" .
                  "##lang.unicity.date## : ##unicity.date##'," .
                  "'&lt;p&gt;##lang.unicity.entity## : ##unicity.entity##&lt;/p&gt;\r\n&lt;p&gt;" .
                  "##lang.unicity.itemtype## : ##unicity.itemtype##&lt;/p&gt;\r\n&lt;p&gt;" .
                  "##lang.unicity.message## : ##unicity.message##&lt;/p&gt;\r\n&lt;p&gt;" .
                  "##lang.unicity.action_user## : ##unicity.action_user##&lt;/p&gt;\r\n&lt;p&gt;" .
                  "##lang.unicity.action_type## : ##unicity.action_type##&lt;/p&gt;\r\n&lt;p&gt;" .
                  "##lang.unicity.date## : ##unicity.date##&lt;/p&gt;');";
         $DB->queryOrDie($query, "0.80 add item not unique notification translation");

         $query = "INSERT INTO `glpi_notifications`
                       (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                        `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                        `date_mod`)
                VALUES ('Item not unique', 0, 'FieldUnicity', 'refuse', 'mail',
                        $notid, '', 1, 1,
                        NOW())";
         $DB->queryOrDie($query, "0.80 add computer not unique notification");
         $notifid = $DB->insertId();

         //       $query = "INSERT INTO `glpi_notificationtargets`
         //                        (`notifications_id`, `type`, `items_id`)
         //                 VALUES ($notifid, 1, 19);";
         //       $DB->queryOrDie($query, "0.80 add computer not unique notification target");
      }
   }

   if (!$DB->tableExists("glpi_fieldblacklists")) {
      $query = "CREATE TABLE `glpi_fieldblacklists` (
                  `id` INT (11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `field` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `value` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `itemtype` VARCHAR (255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                  `entities_id` INT (11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT (1) NOT NULL DEFAULT '0',
                  `comment` TEXT COLLATE utf8_unicode_ci,
                  PRIMARY KEY (id),
                  KEY `name` (`name`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 add table glpi_fieldblacklists");
   }

   if ($migration->addField('glpi_mailcollectors', 'passwd',
                            'varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL')) {
      $migration->migrationOneTable('glpi_mailcollectors');

      $query = "SELECT *
                FROM `glpi_mailcollectors`
                WHERE `password` IS NOT NULL
                      AND `password` <> ''";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               if (!empty($data['password'])) {
                  $query = "UPDATE `glpi_mailcollectors`
                            SET `passwd` = '".addslashes(Toolbox::encrypt($data['password'],
                                                                          GLPIKEY))."'
                            WHERE `id`= '".$data['id']."' ";
                  $DB->queryOrDie($query, "0.80 update passwd in glpi_mailcollectors");
               }
            }
         }
      }
      $migration->dropField('glpi_mailcollectors', 'password');
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'rule ticket migration'));
   $changes['RuleTicket'] = ['users_id'         => '_users_id_requester',
                                  'groups_id'        => '_groups_id_requester',
                                  'users_id_assign'  => '_users_id_assign',
                                  'groups_id_assign' => '_groups_id_assign'];
   // For Rule::RULE_TRACKING_AUTO_ACTION
   $changes['RuleMailCollector'] = ['username' => '_users_id_requester'];

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
            $rules = $DB->result($result, 0, 0);
            // Update actions
            foreach ($tab as $old => $new) {
               $query = "UPDATE `glpi_ruleactions`
                         SET `field` = '$new'
                         WHERE `field` = '$old'
                               AND `rules_id` IN ($rules)";

               $DB->queryOrDie($query, "0.80 update datas for rules actions");
            }
            // Update criteria
            foreach ($tab as $old => $new) {
               $query = "UPDATE `glpi_rulecriterias`
                         SET `criteria` = '$new'
                         WHERE `criteria` = '$old'
                               AND `rules_id` IN ($rules)";
               $DB->queryOrDie($query, "0.80 update datas for rules criteria");
            }
         }
      }
   }

   // Add watcher crontask
   if (!countElementsInTable('glpi_crontasks', ['itemtype' => 'Crontask', 'name' => 'watcher'])) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('Crontask', 'watcher', 86400, NULL, 1, 1, 3,
                         0, 24, 30, NULL, NULL, NULL);";
      $DB->queryOrDie($query, "0.80 populate glpi_crontasks for watcher");
   }
   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `name` = 'Crontask'";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Crontask', 'Crontask', NOW())";
         $DB->queryOrDie($query, "0.80 add crontask watcher notification");
         $notid = $DB->insertId();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                   VALUES (NULL, $notid, '', '##crontask.action##',
                           '##lang.crontask.warning## \r\n\n##FOREACHcrontasks## \n ##crontask.name## : ##crontask.description##\n \n##ENDFOREACHcrontasks##', '&lt;p&gt;##lang.crontask.warning##&lt;/p&gt;\r\n&lt;p&gt;##FOREACHcrontasks## &lt;br /&gt;&lt;a href=\"##crontask.url##\"&gt;##crontask.name##&lt;/a&gt; : ##crontask.description##&lt;br /&gt; &lt;br /&gt;##ENDFOREACHcrontasks##&lt;/p&gt;')";
         $DB->queryOrDie($query, "0.80 add crontask notification translation");

         $query = "INSERT INTO `glpi_notifications`
                VALUES (NULL, 'Crontask Watcher', 0, 'Crontask', 'alert', 'mail', $notid, '', 1, 1,
                        NOW())";
         $DB->queryOrDie($query, "0.80 add crontask notification");
         $notifid = $DB->insertId();

         $query = "INSERT INTO `glpi_notificationtargets`
                       (`id`, `notifications_id`, `type`, `items_id`)
                VALUES (NULL, $notifid, 1, 1)";
         $DB->queryOrDie($query, "0.80 add crontask notification target to global admin");
      }
   }
   /* OCS-NG new clean links features */
   if ($migration->addField('glpi_ocslinks', 'entities_id', 'int(11) NOT NULL DEFAULT \'0\'')) {
      $migration->migrationOneTable("glpi_ocslinks");

      $query = "SELECT `glpi_ocslinks`.`computers_id`, `glpi_computers`.`entities_id`
                FROM `glpi_ocslinks`
                INNER JOIN `glpi_computers`
                  ON (`glpi_computers`.`id` = `glpi_ocslinks`.`computers_id`)";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               $query = "UPDATE `glpi_ocslinks`
                         SET `entities_id` = '" . $data['entities_id'] . "'
                         WHERE `computers_id` = '" . $data['computers_id'] . "'";
               $DB->queryOrDie($query, "0.80 copy entities_id from computers to ocslinks ");
            }
         }
      }
   }

   $migration->addField("glpi_profiles", "clean_ocsng",
                        "char(1) COLLATE utf8_unicode_ci DEFAULT NULL",
                        ['update' => "`sync_ocsng`"]);

   /* END - OCS-NG new clean links features */

   $migration->addField("glpi_transfers", "keep_disk", "int( 11 ) NOT NULL DEFAULT 0",
                        ['update' => "'1'"]);

   if ($migration->addField("glpi_reminders", "is_helpdesk_visible",
                            "tinyint( 1 ) NOT NULL DEFAULT 0")) {
      $query = "UPDATE `glpi_profiles`
                SET `reminder_public` = 'r'
                WHERE `interface` = 'helpdesk';";
      $DB->queryOrDie($query, "0.80 default set of reminder view for helpdesk users");
   }

   if (!$DB->tableExists('glpi_ticketsolutiontemplates')) {
      $query = "CREATE TABLE `glpi_ticketsolutiontemplates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `content` text COLLATE utf8_unicode_ci,
                  `ticketsolutiontypes_id` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`entities_id`,`name`),
                  KEY `name` (`name`),
                  KEY `is_recursive` (`is_recursive`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 create glpi_ticketsolutiontemplates");
   }

   // Fix templates tags
   $updates = ['Ticket'
                     => ['from' => ['##lang.validation.validationstatus##'],
                              'to'   => ['##lang.validation.status## : ##validation.status##']]];

   foreach ($updates as $itemtype => $changes) {

      $query = "SELECT `glpi_notificationtemplatetranslations`.*
                  FROM `glpi_notificationtemplatetranslations`
                  INNER JOIN `glpi_notificationtemplates`
                     ON (`glpi_notificationtemplates`.`id`
                           = `glpi_notificationtemplatetranslations`.`notificationtemplates_id`)
                  WHERE `glpi_notificationtemplates`.`itemtype` = '$itemtype'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
               $query = "UPDATE `glpi_notificationtemplatetranslations`
                           SET `subject` = '".addslashes(
                                    str_replace($changes['from'], $changes['to'],
                                                $data['subject']))."',
                              `content_text` = '".addslashes(
                                    str_replace($changes['from'], $changes['to'],
                                                $data['content_text']))."',
                              `content_html` = '".addslashes(
                                    str_replace($changes['from'], $changes['to'],
                                                $data['content_html']))."'
                           WHERE `id` = '".$data['id']."'";
               $DB->queryOrDie($query, "0.80 fix template tag usage for $itemtype");
            }
         }
      }
   }

   $migration->addField('glpi_profiles', 'update_own_followups',
                        'char(1) COLLATE utf8_unicode_ci DEFAULT NULL',
                        ['update'    => '1',
                              'condition' => " WHERE `update_followups` = 1"]);
   $migration->addField('glpi_profiles', 'delete_followups',
                        'char(1) COLLATE utf8_unicode_ci DEFAULT NULL',
                        ['update' => '`update_followups`']);

   $migration->addField('glpi_ocsservers', 'deleted_behavior', "VARCHAR( 255 ) NOT NULL DEFAULT '1'");

   //User registration number
   $migration->addField('glpi_users', 'registration_number',
                        'VARCHAR( 255 ) COLLATE utf8_unicode_ci DEFAULT NULL');
   $migration->addField('glpi_authldaps', 'registration_number_field',
                        'VARCHAR( 255 ) COLLATE utf8_unicode_ci DEFAULT NULL');

   $migration->addField("glpi_users", "date_sync", "datetime default NULL",
                        ['after'     => "date_mod",
                         'update'    => "`date_mod`",
                         'condition' => " WHERE `auths_id` > 0"]);

   //Migrate OCS computers link from static config to rules engine
   if ($DB->fieldExists('glpi_ocsservers', 'is_glpi_link_enabled', false)) {
      $ocs_servers = getAllDataFromTable('glpi_ocsservers');
      $ranking     = 1;
      foreach ($ocs_servers as $ocs_server) {
         if ($ocs_server['is_glpi_link_enabled']) {
            $query = "INSERT INTO `glpi_rules`
                             (`entities_id`, `sub_type`, `ranking`, `name`,
                              `description`, `match`, `is_active`, `date_mod`, `is_recursive`)
                      VALUES ('0', 'RuleImportComputer', '$ranking', '".$ocs_server['name']."',
                              '', 'AND', 1, NOW(), 1)";
            $DB->queryOrDie($query, "0.80 add new rule RuleImportComputer");
            $rule_id = $DB->insertId();

            $query = "INSERT INTO `glpi_rulecriterias`
                             (`rules_id`, `criteria`, `condition`, `pattern`)
                      VALUES ('$rule_id', 'ocsservers_id', '0', '".$ocs_server['id']."')";
            $DB->queryOrDie($query, "0.80 add new criteria RuleImportComputer");

            if ($ocs_server['states_id_linkif']) {
               $query = "INSERT INTO `glpi_rulecriterias`
                                (`rules_id`, `criteria`, `condition`,
                                 `pattern`)
                         VALUES ('$rule_id', 'states_id', '0',
                                 '".$ocs_server['states_id_linkif']."')";
               $DB->queryOrDie($query, "0.80 add new criteria RuleImportComputer");
            }

            $simple_criteria = ['use_ip_to_link'     => 'IPADDRESS',
                                     'use_mac_to_link'    => 'MACADDRESS',
                                     'use_serial_to_link' => 'serial'];

            foreach ($simple_criteria as $field => $value) {
               $tmpcriteria = [];
               if ($ocs_server[$field]) {
                  $query = "INSERT INTO `glpi_rulecriterias`
                                   (`rules_id`, `criteria`, `condition`, `pattern`)
                            VALUES ('$rule_id', '$value', '10', '1')";
                  $DB->queryOrDie($query, "0.80 add new criteria RuleImportComputer");
               }
            }

            $tmpcriteria = [];
            $query = "INSERT INTO `glpi_rulecriterias`
                             (`rules_id`, `criteria`, `condition`, `pattern`)";

            switch ($ocs_server['use_name_to_link']) {
               case 1 :
                  $query .= "VALUES ('$rule_id', 'name', '10', '1')";
                  $DB->query($query);
                  break;

               case 2:
                  $query .= "VALUES ('$rule_id', 'name', '30', '1')";
                  $DB->query($query);
                  break;

            }
            $query = "INSERT INTO `glpi_ruleactions`
                             (`rules_id`, `action_type`, `field`, `value`)
                      VALUES ('$rule_id', 'assign', '_fusion', '0')";
            $DB->queryOrDie($query, "0.80 add new action RuleImportComputer");

            $ranking++;
         }
      }

      $todrop = ['is_glpi_link_enabled', 'states_id_linkif', 'use_ip_to_link',
                      'use_mac_to_link', 'use_name_to_link', 'use_serial_to_link'];
      foreach ($todrop as $field) {
         $migration->dropField('glpi_ocsservers', $field);
      }
   }

   /* New automatic transfert feature */
   $migration->addField('glpi_configs', 'transfers_id_auto',
                        'int(11) NOT NULL DEFAULT 0');

   $migration->addField('glpi_ocslinks', 'tag',
                        'varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL');
   /* END - New automatic transfert feature */

   $migration->addField('glpi_profiles', 'entity_helpdesk',
                        'char(1) COLLATE utf8_unicode_ci DEFAULT NULL',
                        ['update' => '`notification`']);

   $migration->addField('glpi_computers', 'uuid',
                        'varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL');

   $migration->addField('glpi_ocsservers', 'import_vms', 'TINYINT(1) NOT NULL DEFAULT 0');

   $migration->addField('glpi_ocsservers', 'import_general_uuid', 'TINYINT(1) NOT NULL DEFAULT 0');

   $migration->addField('glpi_ocslinks', 'import_vm',
                        'LONGTEXT COLLATE utf8_unicode_ci DEFAULT NULL');

   if (!$DB->tableExists('glpi_virtualmachinetypes')) {
      $query = "CREATE TABLE `glpi_virtualmachinetypes` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL DEFAULT '',
                  `comment` TEXT NOT NULL,
                  PRIMARY KEY (`id`)
                  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.80 add new table glpi_virtualmachinetypes");
   }

   if (!$DB->tableExists('glpi_virtualmachinesystems')) {
      $query = "CREATE TABLE `glpi_virtualmachinesystems` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL DEFAULT '',
                  `comment` TEXT NOT NULL,
                  PRIMARY KEY (`id`)
                  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.80 add new table glpi_virtualmachinesystems");
   }

   if (!$DB->tableExists('glpi_virtualmachinestates')) {
      $query = "CREATE TABLE `glpi_virtualmachinestates` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL DEFAULT '',
                  `comment` TEXT NOT NULL,
                  PRIMARY KEY (`id`)
                  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.80 add new table glpi_virtualmachinestates");
   }

   if (!$DB->tableExists('glpi_computervirtualmachines')) {
      $query = "CREATE TABLE `glpi_computervirtualmachines` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `entities_id` INT(11) NOT NULL DEFAULT '0',
                  `computers_id` INT(11) NOT NULL DEFAULT '0',
                  `name` VARCHAR(255) NOT NULL DEFAULT '',
                  `virtualmachinestates_id` INT(11) NOT NULL DEFAULT '0',
                  `virtualmachinesystems_id` INT(11) NOT NULL DEFAULT '0',
                  `virtualmachinetypes_id` INT(11) NOT NULL DEFAULT '0',
                  `uuid` VARCHAR(255) NOT NULL DEFAULT '',
                  `vcpu` INT(11) NOT NULL DEFAULT '0',
                  `ram` VARCHAR(255) NOT NULL DEFAULT '',
                  PRIMARY KEY (`id`)
                  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.80 add new table glpi_computervirtualmachines");
   }

   // Clean ticket validations
   $query = "DELETE
             FROM `glpi_ticketvalidations`
             WHERE `glpi_ticketvalidations`.`tickets_id` NOT IN (SELECT `glpi_tickets`.`id`
                                                                 FROM `glpi_tickets`)";
   $DB->queryOrDie($query, "0.80 clean glpi_ticketvalidations");

   // Keep it at the end
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query = "SELECT DISTINCT users_id
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetchAssoc($result)) {
               $query = "SELECT MAX(`rank`)
                         FROM `glpi_displaypreferences`
                         WHERE `users_id` = '".$data['users_id']."'
                               AND `itemtype` = '$type'";
               $result = $DB->query($query);
               $rank   = $DB->result($result, 0, 0);
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

   // Change value of autoclose ticket
   $query = "UPDATE `glpi_configs`
             SET `autoclose_delay` = '-1'
             WHERE `id` = '1'
                   AND `autoclose_delay` = '0'";

   $DB->queryOrDie($query, "0.80 change autoclose ticket in glpi_configs");

   $query = "UPDATE `glpi_entitydatas`
             SET `autoclose_delay` = '-2'
             WHERE `autoclose_delay` = '-1'";

   $DB->queryOrDie($query, "0.80 change autoclose ticket in glpi_entitydatas for inherit");

   $query = "UPDATE `glpi_entitydatas`
             SET `autoclose_delay` = '-1'
             WHERE `autoclose_delay` = '0'";

   $DB->queryOrDie($query, "0.80 change autoclose ticket in glpi_entitydatas for inactive");

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}
