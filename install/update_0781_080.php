<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Update from 0.78 to 0.80
 *
 * @param $output string for format
 *       HTML (default) for standard upgrade
 *       empty = no ouput for PHPUnit
 *
 * @return bool for success (will die for most error)
**/
function update0781to080($output='HTML') {
   global $DB, $LANG;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.80</h3>";
   }

   $migration = new Migration("080");

   displayMigrationMessage("080"); // Start

   displayMigrationMessage("080", $LANG['update'][141] . ' - Calendar'); // Updating schema


   $default_calendar_id = 0;
   if (!TableExists('glpi_calendars')) {
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
      $DB->query($query) or die("0.80 create glpi_calendars " . $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['Calendar'] = array(19);
      // Create default calendar : use existing config planning_begin _end
      $query = "INSERT INTO `glpi_calendars`
                       (`name`, `entities_id`, `is_recursive`, `comment`)
                VALUES ('Default', 0, 1, 'Default calendar');";
      $DB->query($query)
      or die("0.80 add default glpi_calendars " . $LANG['update'][90] . $DB->error());
      $default_calendar_id = $DB->insert_id();
   }

   if (!TableExists('glpi_calendarsegments')) {
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
      $DB->query($query)
      or die("0.80 create glpi_calendarsegments " . $LANG['update'][90] . $DB->error());

      // add defautl days : from monday to friday
      if ($default_calendar_id>0) {
         $query = "SELECT `planning_begin`, `planning_end`
                   FROM `glpi_configs`
                   WHERE `id` = '1'";

         if ($result = $DB->query($query)) {
            $begin = $DB->result($result, 0, 'planning_begin');
            $end   = $DB->result($result, 0, 'planning_end');

            if ($begin < $end) {
               for ($i=1 ; $i<6 ; $i++) {
                  $query = "INSERT INTO `glpi_calendarsegments`
                                   (`calendars_id`, `day`, `begin`, `end`)
                            VALUES ($default_calendar_id, $i, '$begin', '$end')";
                  $DB->query($query)
                  or die("0.80 add default glpi_calendarsegments " . $LANG['update'][90] . $DB->error());
               }
            }
         }

         // Update calendar
         include_once (GLPI_ROOT . "/inc/commondropdown.class.php");
         include_once (GLPI_ROOT . "/inc/commondbchild.class.php");
         include_once (GLPI_ROOT . "/inc/calendarsegment.class.php");
         include_once (GLPI_ROOT . "/inc/calendar.class.php");
         $calendar = new Calendar();
         if ($calendar->getFromDB($default_calendar_id)) {
            $query = "UPDATE `glpi_calendars`
                      SET `cache_duration` = '".exportArrayToDB($calendar->getDaysDurations())."'
                      WHERE `id` = '$default_calendar_id'";
                  $DB->query($query)
                  or die("0.80 update default calendar cache " . $LANG['update'][90] . $DB->error());
         }
      }

   }

   // Holidays : wrong management : may be a group of several days : will be easy to managed holidays
   if (!TableExists('glpi_holidays')) {
      $query = "CREATE TABLE `glpi_holidays` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
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
      $DB->query($query) or die("0.80 create glpi_holidays " . $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['Holiday'] = array(11, 12, 13);

//       $perpetualaddday=array(array('date'=>'2000-01-01','name'=>"New Year Day"),
//                            array('date'=>'2000-03-17','name'=>"St. Patrick\'s Day"),
//                            array('date'=>'2000-05-01','name'=>"Labour Day"),
//                            array('date'=>'2000-05-08','name'=>"Victory in Europe Day"),
//                            array('date'=>'2000-07-04','name'=>"Independence Day"),
//                            array('date'=>'2000-07-14','name'=>"French National Day"),
//                            array('date'=>'2000-07-12','name'=>"Orangeman\'s Holiday"),
//                            array('date'=>'2000-08-15','name'=>"Assumption of Mary"),
//                            array('date'=>'2000-11-01','name'=>"All Saints\' Day"),
//                            array('date'=>'2000-11-11','name'=>"Armistice Day"),
//                            array('date'=>'2000-12-25','name'=>"Christmas Day"),
//                            array('date'=>'2000-12-26','name'=>"Boxing Day"),
//                      );
//       foreach ($perpetualaddday as $val) {
//          $query="INSERT INTO `glpi_holidays` (`begin_date`,`end_date`,`is_perpetual`,`name`)
//                            VALUES ('".$val['date']."','".$val['date']."','1','".$val['name']."');";
//          $DB->query($query) or die("0.80 insert values in glpi_holidays " . $LANG['update'][90] . $DB->error());
//       }

   }


   if (!TableExists('glpi_calendars_holidays')) {
      $query = "CREATE TABLE `glpi_calendars_holidays` (
                  `id` int(11) NOT NULL auto_increment,
                  `calendars_id` int(11) NOT NULL default '0',
                  `holidays_id` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  UNIQUE KEY `unicity` (`calendars_id`,`holidays_id`),
                  KEY `holidays_id` (`holidays_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query)
      or die("0.80 create glpi_calendars_holidays " . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - SLA'); // Updating schema


   if (!TableExists('glpi_slas')) {
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
      $DB->query($query) or die("0.80 create glpi_slas " . $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['SLA'] = array(4);

      // Get first Ticket template
      $query = "SELECT `id`
                FROM `glpi_notificationtemplates`
                WHERE `itemtype` LIKE 'Ticket%'
                ORDER BY `id` ASC";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {

            $query = "INSERT INTO `glpi_notifications`
                      VALUES (NULL, 'Ticket Recall', 0, 'Ticket', 'recall', 'mail',
                              ".$DB->result($result,0,0).", '', 1, 1, NOW());";
            $DB->query($query)
            or die("0.80 insert notification" . $LANG['update'][90] . $DB->error());
         }
      }

   }
   if (!TableExists('glpi_slalevels')) {
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
      $DB->query($query) or die("0.80 create glpi_slalevels " . $LANG['update'][90] . $DB->error());
   }


   if (!TableExists('glpi_slalevelactions')) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_slalevelactions` (
                  `id` int(11) NOT NULL auto_increment,
                  `slalevels_id` int(11) NOT NULL default '0',
                  `action_type` varchar(255) collate utf8_unicode_ci default NULL,
                  `field` varchar(255) collate utf8_unicode_ci default NULL,
                  `value` varchar(255) collate utf8_unicode_ci default NULL,
                  PRIMARY KEY  (`id`),
                  KEY `slalevels_id` (`slalevels_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
      $DB->query($query)
      or die("0.80 create glpi_slalevelactions " . $LANG['update'][90] . $DB->error());
   }

   if ($migration->addField("glpi_profiles", "calendar", "CHAR( 1 ) NULL")) {
      $migration->migrationOneTable('glpi_profiles');
      $query = "UPDATE `glpi_profiles`
                SET `calendar` = `entity_dropdown`";
      $DB->query($query)
      or die("0.80 add calendar right users which are able to write entity_dropdown " . $LANG['update'][90] . $DB->error());
   }

   if ($migration->addField("glpi_profiles", "sla", "CHAR( 1 ) NULL")) {
      $migration->migrationOneTable('glpi_profiles');
      $query = "UPDATE `glpi_profiles`
                SET `sla` = `entity_rule_ticket`";
      $DB->query($query)
      or die("0.80 add sla right users which are able to write entity_rule_ticket " . $LANG['update'][90] . $DB->error());
   }

   $migration->addField("glpi_tickets", "slas_id", "INT( 11 ) NOT NULL DEFAULT 0");
   $migration->addKey("glpi_tickets", "slas_id");
   $migration->addField("glpi_tickets", "slalevels_id", "INT( 11 ) NOT NULL DEFAULT 0");
   $migration->addField("glpi_tickets", "due_date", "datetime default NULL");
   $migration->addKey("glpi_tickets", "due_date");
   $migration->addField("glpi_tickets", "begin_waiting_date", "datetime default NULL");
   $migration->addField("glpi_tickets", "sla_waiting_duration", "INT( 11 ) NOT NULL DEFAULT 0");

   if (!TableExists('glpi_slalevels_tickets')) {
      $query = "CREATE TABLE IF NOT EXISTS `glpi_slalevels_tickets` (
                  `id` int(11) NOT NULL auto_increment,
                  `tickets_id` int(11) NOT NULL default '0',
                  `slalevels_id` int(11) NOT NULL default '0',
                  `date` datetime default NULL,
                  PRIMARY KEY  (`id`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `slalevels_id` (`slalevels_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
      $DB->query($query)
      or die("0.80 create glpi_slalevels_tickets " . $LANG['update'][90] . $DB->error());

      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('SlaLevel_Ticket', 'slaticket', 300, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->query($query)
      or die("0.80 populate glpi_crontasks for slaticket" . $LANG['update'][90] . $DB->error());

   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - PasswordForget'); // Updating schema

   $migration->addField("glpi_users", "token", "char( 40 ) NULL DEFAULT ''");
   $migration->addField("glpi_users", "tokendate", "datetime NULL DEFAULT NULL");

   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `name` = 'Password Forget'";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                   VALUES(NULL, 'Password Forget', 'User', NOW(),'');";
         $DB->query($query)
         or die("0.80 add password forget notification" . $LANG['update'][90] . $DB->error());
         $notid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                   VALUES(NULL, $notid, '','##user.action##',
                          '##lang.user.realname## ##lang.user.firstname##

##lang.user.information##

##lang.user.link## ##user.passwordforgeturl##',
                          '&lt;p&gt;&lt;strong&gt;##lang.user.realname## ##lang.user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.user.information##&lt;/p&gt;
&lt;p&gt;##lang.user.link## &lt;a title=\"##user.passwordforgeturl##\" href=\"##user.passwordforgeturl##\"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;');";
      $DB->query($query)
      or die("0.80 add password forget notification translation" . $LANG['update'][90] . $DB->error());

      $query = "INSERT INTO `glpi_notifications`
                VALUES (NULL, 'Password Forget', 0, 'User', 'passwordforget', 'mail', $notid, '',
                        1, 1, NOW());";
      $DB->query($query)
      or die("0.80 add password forget notification" . $LANG['update'][90] . $DB->error());
      $notifid = $DB->insert_id();

      $query = "INSERT INTO `glpi_notificationtargets`
                       (`id`, `notifications_id`, `type`, `items_id`)
                VALUES (NULL, $notifid, 1, 19);";
      $DB->query($query)
      or die("0.80 add password forget notification target" . $LANG['update'][90] . $DB->error());
      }
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - Ticket'); // Updating schema

   $migration->addField("glpi_tickets", "ticket_waiting_duration", "INT( 11 ) NOT NULL DEFAULT 0");

   $migration->addField("glpi_entitydatas", "calendars_id", "INT( 11 ) NOT NULL DEFAULT 0");

   if ($migration->addField("glpi_tickets", "close_delay_stat", "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_tickets');
      // Manage stat computation for existing tickets
      $query = "UPDATE `glpi_tickets`
                SET `close_delay_stat`
                  = (UNIX_TIMESTAMP(`glpi_tickets`.`closedate`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`))
                WHERE `glpi_tickets`.`status` = 'closed'
                      AND `glpi_tickets`.`date` IS NOT NULL
                      AND `glpi_tickets`.`closedate` IS NOT NULL
                      AND `glpi_tickets`.`closedate` > `glpi_tickets`.`date`";
      $DB->query($query)
      or die("0.80 update ticket close_delay_stat value". $LANG['update'][90] . $DB->error());
   }

   if ($migration->addField("glpi_tickets", "solve_delay_stat", "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_tickets');
      // Manage stat computation for existing tickets
      $query = "UPDATE `glpi_tickets`
                SET `solve_delay_stat`
                  = (UNIX_TIMESTAMP(`glpi_tickets`.`solvedate`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`))
                WHERE (`glpi_tickets`.`status` = 'closed'
                        OR `glpi_tickets`.`status` = 'solved')
                      AND `glpi_tickets`.`date` IS NOT NULL
                      AND `glpi_tickets`.`solvedate` IS NOT NULL
                      AND `glpi_tickets`.`solvedate` > `glpi_tickets`.`date`";
      $DB->query($query)
      or die("0.80 update solve_delay_stat values in glpi_tickets". $LANG['update'][90] . $DB->error());
   }

   if ($migration->addField("glpi_tickets", "takeintoaccount_delay_stat",
                            "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_tickets');
      // Manage stat computation for existing tickets
      // Solved tickets
      $query = "SELECT `glpi_tickets`.`id` AS ID,
                       MIN(UNIX_TIMESTAMP(`glpi_tickets`.`solvedate`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS OPEN,
                       MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST,
                       MIN(UNIX_TIMESTAMP(`glpi_tickettasks`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST2
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
            while ($data = $DB->fetch_assoc($result)) {
               $firstactiontime = min($data['OPEN'], $data['FIRST'], $data['FIRST2']);
               $firstactiontime = max(0, $firstactiontime);
               $query2 = "UPDATE `glpi_tickets`
                          SET `takeintoaccount_delay_stat` = '$firstactiontime'
                          WHERE `id` = '".$data['ID']."'";
               $DB->query($query2)
               or die("0.80 update takeintoaccount_delay_stat values for #".
                      $data['ID']." ". $LANG['update'][90] . $DB->error());
            }
         }
      }
      // Not solved tickets
      $query = "SELECT `glpi_tickets`.`id` AS ID,
                       MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST,
                       MIN(UNIX_TIMESTAMP(`glpi_tickettasks`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST2
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
            while ($data = $DB->fetch_assoc($result)) {
               $firstactiontime = min($data['FIRST'], $data['FIRST2']);
               $firstactiontime = max(0, $firstactiontime);
               $query2 = "UPDATE `glpi_tickets`
                          SET `takeintoaccount_delay_stat` = '$firstactiontime'
                          WHERE `id` = '".$data['ID']."'";
               $DB->query($query2)
               or die("0.80 update takeintoaccount_delay_stat values for #".$data['ID']. " ".
                      $LANG['update'][90] . $DB->error());
            }
         }
      }

   }

   // Put realtime in seconds
   if ($migration->addField("glpi_tickets", "actiontime", "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_tickets');

      if (FieldExists('glpi_tickets','realtime')) {
         $query = "UPDATE `glpi_tickets`
                   SET `actiontime` = ROUND(realtime * 3600)";
         $DB->query($query)
         or die("0.80 compute actiontime value in glpi_tickets". $LANG['update'][90] . $DB->error());

         $migration->dropKey("glpi_tickets", "realtime");
      }
   }

   if ($migration->addField("glpi_tickettasks", "actiontime", "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->migrationOneTable('glpi_tickettasks');

      if (FieldExists('glpi_tickettasks','realtime')) {
         $query = "UPDATE `glpi_tickettasks`
                   SET `actiontime` = ROUND(realtime * 3600)";
         $DB->query($query)
         or die("0.80 compute actiontime value in glpi_tickettasks". $LANG['update'][90] . $DB->error());

         $migration->dropKey("glpi_tickettasks", "realtime");
      }
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - Software'); // Updating schema

   if ($migration->addField("glpi_softwareversions", "operatingsystems_id", "INT( 11 ) NOT NULL")) {
      $migration->addKey("glpi_softwareversions", "operatingsystems_id");
      $migration->migrationOneTable('glpi_softwareversions');

      $query = "UPDATE `glpi_softwareversions`,
                        (SELECT `id`, `operatingsystems_id`
                         FROM `glpi_softwares`) AS SOFT
                SET `glpi_softwareversions`.`operatingsystems_id` = `SOFT`.`operatingsystems_id`
                WHERE `glpi_softwareversions`.`softwares_id` = `SOFT`.`id` ";
      $DB->query($query)
      or die("0.80 transfer operatingsystems_id from glpi_softwares to glpi_softwareversions" . $LANG['update'][90] . $DB->error());

      $migration->dropKey("glpi_softwares", "operatingsystems_id");
   }


   if (!isIndex("glpi_computers_softwareversions","unicity")) {
      // clean datas
      $query = "SELECT `computers_id`,
                       `softwareversions_id`,
                       COUNT(*) AS CPT
               FROM `glpi_computers_softwareversions`
               GROUP BY `computers_id`, `softwareversions_id`
               HAVING CPT > 1";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $query2 = "SELECT `id`
                          FROM `glpi_computers_softwareversions`
                          WHERE `computers_id` = '".$data['computers_id']."'
                                AND `softwareversions_id` = '".$data['softwareversions_id']."'
                          LIMIT 1";

               if ($result2= $DB->query($query2)) {
                  if ($DB->numrows($result2)) {
                     $keep_id=$DB->result($result2,0,0);
                     $query3 = "DELETE FROM `glpi_computers_softwareversions`
                                WHERE `computers_id` = '".$data['computers_id']."'
                                      AND `softwareversions_id` = '".$data['softwareversions_id']."'
                                      AND `id` <> $keep_id";
                     $DB->query($query3)
                     or die("0.80 clean glpi_computers_softwareversions " . $LANG['update'][90] .
                            $DB->error());
                  }
               }
            }
         }
      }

      $migration->addKey("glpi_computers_softwareversions",
                         array('computers_id', 'softwareversions_id'), 'unicity', "UNIQUE");
   }

   $migration->dropKey("glpi_computers_softwareversions", "computers_id");

   if (!TableExists("glpi_computers_softwarelicenses")) {
      $query = "CREATE TABLE `glpi_computers_softwarelicenses` (
                  `id` int(11) NOT NULL auto_increment,
                  `computers_id` int(11) NOT NULL default '0',
                  `softwarelicenses_id` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `computers_id` (`computers_id`),
                  KEY `softwarelicenses_id` (`softwarelicenses_id`),
                  UNIQUE `unicity` ( `computers_id` , `softwarelicenses_id` )
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query)
      or die("0.80 create glpi_tickettasks " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_softwarelicenses","computers_id")) {
      $query = "SELECT *
                FROM `glpi_softwarelicenses`
                WHERE `computers_id` > 0
                      AND `computers_id` IS NOT NULL";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "INSERT INTO `glpi_computers_softwarelicenses`
                                (`computers_id`, `softwarelicenses_id`)
                         VALUES ('".$data['computers_id']."','".$data['id']."')";
               $DB->query($query)
               or die("0.80 migrate data to computers_softwarelicenses table " .
                      $LANG['update'][90] . $DB->error());
            }
         }
      }

      $migration->dropKey("glpi_softwarelicenses", "computers_id");
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - Common'); // Updating schema

   $migration->addField("glpi_softwarelicenses", "date_mod", "DATETIME NULL");
   $migration->addKey("glpi_softwarelicenses", "date_mod");

   if (TableExists("glpi_cartridges_printermodels")) {
      $query = "RENAME TABLE `glpi_cartridges_printermodels` TO `glpi_cartridgeitems_printermodels`  ;";
      $DB->query($query)
      or die("0.80 rename glpi_cartridges_printermodels " . $LANG['update'][90] . $DB->error());
   }

   $migration->addField("glpi_monitors", "have_hdmi",
                        "tinyint(1) NOT NULL DEFAULT 0 AFTER `have_pivot`");

   $migration->dropKey("glpi_configs", "dbreplicate_email");
   $migration->addField("glpi_configs", "auto_create_infocoms", "tinyint(1) NOT NULL DEFAULT 0");
   if ($migration->addField("glpi_configs", "csv_delimiter",
                            "CHAR( 1 ) NOT NULL AFTER `number_format`")) {
      $migration->migrationOneTable('glpi_configs');

      $query = "UPDATE `glpi_configs`
                SET `csv_delimiter` = ';'";
      $DB->query($query);
   }

   $migration->addField("glpi_users", "csv_delimiter", "CHAR( 1 ) NULL AFTER `number_format`");
   $migration->addField("glpi_users", "names_format",
                        "INT( 11 ) NULL DEFAULT NULL AFTER `number_format`");

   // drop car fait sur mauvais champ
   $migration->dropKey("glpi_budgets", "end_date");
   $migration->migrationOneTable("glpi_budgets");
   $migration->addKey("glpi_budgets", "end_date");

   $migration->addField("glpi_ocsservers", "ocs_db_utf8",
                        "tinyint(1) NOT NULL default '0' AFTER `ocs_db_name`");

   if ($migration->addField("glpi_authldaps", "is_active", "TINYINT( 1 ) NOT NULL DEFAULT '0'")) {
      $migration->migrationOneTable('glpi_authldaps');

      $query = "UPDATE `glpi_authldaps`
                SET `is_active` = '1'";
      $DB->query($query)
      or die("0.80 set all ldap servers active". $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['AuthLdap'] = array(30);
   }

   if ($migration->addField("glpi_authmails", "is_active", "TINYINT( 1 ) NOT NULL DEFAULT '0'")) {
      $migration->migrationOneTable('glpi_authmails');

      $query = "UPDATE `glpi_authmails`
                SET `is_active` = '1'";
      $DB->query($query)
      or die("0.80 set all ldap servers active". $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['AuthMail'] = array(6);
   }

   if ($migration->addField("glpi_ocsservers", "is_active", "TINYINT( 1 ) NOT NULL DEFAULT '0'")) {
      $migration->migrationOneTable('glpi_ocsservers');

       $query = "UPDATE `glpi_ocsservers`
                 SET `is_active` = '1'";
      $DB->query($query)
      or die("0.80 set all ocs servers active". $LANG['update'][90] . $DB->error());

      $ADDTODISPLAYPREF['OcsServer'] = array(6);
   }

   // Drop nl_be langage
   $migration->migrationOneTable('glpi_configs');
   $query = "UPDATE `glpi_configs`
             SET `language` = 'nl_NL'
             WHERE `language` = 'nl_BE';";
   $DB->query($query) or die("0.80 drop nl_be langage" . $LANG['update'][90] . $DB->error());

   $migration->migrationOneTable('glpi_users');
   $query = "UPDATE `glpi_users`
             SET `language` = 'nl_NL'
             WHERE `language` = 'nl_BE';";
   $DB->query($query) or die("0.80 drop nl_be langage" . $LANG['update'][90] . $DB->error());

   // CLean sl_SL
   $query = "UPDATE `glpi_configs`
             SET `language` = 'sl_SI'
             WHERE `language` = 'sl_SL';";
   $DB->query($query) or die("0.80 drop nl_be langage" . $LANG['update'][90] . $DB->error());

   $query = "UPDATE `glpi_users`
             SET `language` = 'sl_SI'
             WHERE `language` = 'sl_SL';";
   $DB->query($query) or die("0.80 drop nl_be langage" . $LANG['update'][90] . $DB->error());


   $migration->dropKey("glpi_knowbaseitemcategories", "unicity");
   $migration->addField("glpi_knowbaseitemcategories", "entities_id",
                        "INT NOT NULL DEFAULT '0' AFTER `id`");

   $migration->addKey("glpi_knowbaseitemcategories", "entities_id");

   if ($migration->addField("glpi_knowbaseitemcategories", "is_recursive",
                            "TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `entities_id`")) {

      $migration->addKey("glpi_knowbaseitemcategories", "is_recursive");

      $migration->migrationOneTable('glpi_knowbaseitemcategories');

      // Set existing categories recursive global
      $query = "UPDATE `glpi_knowbaseitemcategories`
                SET `is_recursive` = '1'";
      $DB->query($query)
      or die("0.80 set value of is_recursive in glpi_knowbaseitemcategories" .
             $LANG['update'][90] . $DB->error());
   }
   $migration->addKey("glpi_knowbaseitemcategories",
                      array('entities_id', 'knowbaseitemcategories_id', 'name'),
                      "unicity", "UNIQUE");


   $migration->changeField("glpi_configs", "use_auto_assign_to_tech", "auto_assign_mode",
                           "INT( 11 ) NOT NULL DEFAULT '1'");

   $migration->addField("glpi_entitydatas", "auto_assign_mode", "INT( 11 ) NOT NULL DEFAULT '-1'");

   $migration->addField("glpi_users", "user_dn", "TEXT DEFAULT NULL");

   $migration->addField("glpi_tickets", "users_id_lastupdater",
                        "INT( 11 ) NOT NULL DEFAULT 0 AFTER `date_mod`");
   $migration->addKey("glpi_tickets", "users_id_lastupdater");

   $migration->addField("glpi_tickets", "type",
                        "INT( 11 ) NOT NULL DEFAULT 1 AFTER `ticketcategories_id`");
   $migration->addKey("glpi_tickets", "type");
   $migration->addField("glpi_entitydatas", "tickettype", "INT( 11 ) NOT NULL DEFAULT 0");


   // Link between tickets
   if (!TableExists('glpi_tickets_tickets')) {
      $query = "CREATE TABLE `glpi_tickets_tickets` (
                  `id` int(11) NOT NULL auto_increment,
                  `tickets_id_1` int(11) NOT NULL default '0',
                  `tickets_id_2` int(11) NOT NULL default '0',
                  `link` int(11) NOT NULL default '1',
                  PRIMARY KEY  (`id`),
                  KEY `unicity` (`tickets_id_1`,`tickets_id_2`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.80 create glpi_tickets_tickets " . $LANG['update'][90] . $DB->error());
   }


   //inquest
   if (!TableExists('glpi_ticketsatisfactions')) {
      $query = "CREATE TABLE `glpi_ticketsatisfactions` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `date_begin` DATETIME NULL ,
                  `date_answered` DATETIME NULL ,
                  `satisfaction` INT(11) NULL ,
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `tickets_id` (`tickets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.80 create glpi_ticketinquests " . $LANG['update'][90] . $DB->error());
   }

   // config inquest by entity
   if ($migration->addField("glpi_entitydatas", "max_closedate", "DATETIME NULL")) {

      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('Ticket', 'createinquest', 86400, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->query($query)
      or die("0.80 populate glpi_crontasks for ticketsatisfaction" . $LANG['update'][90] . $DB->error());
   }

   $migration->addField("glpi_entitydatas", "inquest_config", "INT(11) NOT NULL DEFAULT '1' AFTER `auto_assign_mode`");
   $migration->addField("glpi_entitydatas", "inquest_rate", "INT(11) NOT NULL DEFAULT '-1'");
   $migration->addField("glpi_entitydatas", "inquest_delay", "INT(11) NOT NULL DEFAULT '-1'");
   $migration->addField("glpi_entitydatas", "inquest_URL", "VARCHAR( 255 ) NULL");

   // if no config inquest in the entity
   $migration->addField("glpi_configs", "inquest_rate", "INT(11) NOT NULL DEFAULT '0'");
   $migration->addField("glpi_configs", "inquest_delay", "INT(11) NOT NULL DEFAULT '0'");

   $migration->addField("glpi_networkports", "comment", "TEXT COLLATE utf8_unicode_ci");

   $migration->addField("glpi_profiles", "rule_dictionnary_printer", "CHAR( 1 ) NULL");

   //New infocom dates
   $migration->addField("glpi_infocoms", "order_date", "DATE NULL");
   $migration->addField("glpi_infocoms", "delivery_date", "DATE NULL");

   if ($migration->addField("glpi_infocoms", "warranty_date", "DATE NULL")) {
      $migration->migrationOneTable("glpi_infocoms");

      $query = "UPDATE  `glpi_infocoms`
                SET `warranty_date` = `buy_date`";
      $DB->query($query)
      or die("0.80 set copy buy_date to warranty_date in glpi_infocoms " . $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_rulecacheprinters')) {
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
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query)
      or die("0.80 add table glpi_rulecacheprinters". $LANG['update'][90] . $DB->error());
   }

   $migration->addField("glpi_configs", "use_slave_for_search", "tinyint( 1 ) NOT NULL DEFAULT '0'");
   $migration->addField("glpi_configs", "admin_email_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL AFTER `admin_email`");
   $migration->addField("glpi_configs", "admin_reply_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL AFTER `admin_reply`");

   $migration->addField("glpi_entitydatas", "admin_email_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL AFTER `admin_email`");
   $migration->addField("glpi_entitydatas", "admin_reply_name",
                        "varchar( 255 ) collate utf8_unicode_ci default NULL AFTER `admin_reply`");

   $migration->addField("glpi_notificationtemplates", "css", "text COLLATE utf8_unicode_ci");

   $migration->addField("glpi_configs", "url_maxlength",
                        "int(11) NOT NULL DEFAULT '30' AFTER `list_limit_max`");

   displayMigrationMessage("080", $LANG['update'][142] . ' - passwords encryption');

   /// how not to replay password encryption ?
   if (FieldExists('glpi_configs','proxy_password')) {
      $query = "SELECT `proxy_password`
                FROM `glpi_configs`
                WHERE `id` = '1'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value = $DB->result($result,0,0);
            if (!empty($value)) {
               $query = "UPDATE `glpi_configs`
                         SET `proxy_password` = '".addslashes(encrypt($value,GLPIKEY))."'
                         WHERE `id` = '1' ";
               $DB->query($query)
               or die("0.80 update proxy_password in glpi_configs ".$LANG['update'][90]. $DB->error());
            }
         }
      }
   }

   if (FieldExists('glpi_configs','smtp_password')) {
      $query = "SELECT `smtp_password`
                FROM `glpi_configs`
                WHERE `id` = '1'";

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value = $DB->result($result,0,0);
            if (!empty($value)) {
               $query = "UPDATE `glpi_configs`
                         SET `smtp_password` = '".addslashes(encrypt($value,GLPIKEY))."'
                         WHERE `id` = '1' ";
               $DB->query($query)
               or die("0.80 update smtp_password in glpi_configs ".$LANG['update'][90]. $DB->error());
            }
         }
      }
   }


   if (FieldExists('glpi_authldaps','rootdn_password')) {
      $query = "SELECT *
                FROM `glpi_authldaps`
                WHERE `rootdn_password` IS NOT NULL
                      AND `rootdn_password` <> ''";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               if (!empty($data['rootdn_password'])) {
                  $query = "UPDATE `glpi_authldaps`
                            SET `rootdn_password` = '".addslashes(encrypt($data['rootdn_password'],
                                                                          GLPIKEY))."'
                            WHERE `id` = '".$data['id']."' ";
                  $DB->query($query)
                  or die("0.80 update rootdn_password in glpi_authldaps ".$LANG['update'][90]. $DB->error());
               }
            }
         }
      }
   }

   if (FieldExists('glpi_mailcollectors','password')) {
      $query = "SELECT *
                FROM `glpi_mailcollectors`
                WHERE `password` IS NOT NULL
                      AND `password` <> ''";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               if (!empty($data['password'])) {
                  $query = "UPDATE `glpi_mailcollectors`
                            SET `password` = '".addslashes(encrypt($data['password'],GLPIKEY))."'
                            WHERE `id`= '".$data['id']."' ";
                  $DB->query($query)
                  or die("0.80 update password in glpi_mailcollectors ".$LANG['update'][90]. $DB->error());
               }
            }
         }
      }
   }


   displayMigrationMessage("080", $LANG['update'][142] . ' - glpi_displaypreferences');

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query = "SELECT DISTINCT users_id
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type';";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "SELECT MAX(rank)
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
                                  AND `itemtype` = '$type';";
                  if ($result2=$DB->query($query)) {
                     if ($DB->numrows($result2)==0) {
                        $query = "INSERT INTO `glpi_displaypreferences`
                                        (`itemtype` ,`num` ,`rank` ,`users_id`)
                                 VALUES ('$type', '$newval', '".$rank++."', '".$data['users_id']."')";
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

   $migration->executeMigration();

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("080"); // End

   return $updateresult;
}
?>
