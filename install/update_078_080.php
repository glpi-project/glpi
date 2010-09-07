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
 */
function update078to080($output='HTML') {
	global $DB, $LANG;

   $updateresult = true;
   $ADDTODISPLAYPREF = array();

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.80</h3>";
   }
   displayMigrationMessage("080"); // Start

   displayMigrationMessage("080", $LANG['update'][141] . ' - Calendar'); // Updating schema

   $default_calendar_id=0;
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

      $ADDTODISPLAYPREF['Calendar']=array(19);
      // Create default calendar : use existing config planning_begin _end
      $query="INSERT INTO `glpi_calendars` (`name`,`entities_id`,`is_recursive`,`comment`)
                  VALUES ('Default',0,1,'Default calendar');";
      $DB->query($query) or die("0.80 add default glpi_calendars " . $LANG['update'][90] . $DB->error());
      $default_calendar_id=$DB->insert_id();
   }

   if (!TableExists('glpi_calendarsegments')) {
      $query = "CREATE TABLE `glpi_calendarsegments` (
                  `id` int(11) NOT NULL auto_increment,
                  `calendars_id` int(11) NOT NULL default '0',
                  `day` tinyint(1) NOT NULL default '1' COMMENT 'numer of the day based on date(w)',
                  `begin` time DEFAULT NULL,
                  `end` time DEFAULT NULL,
                  PRIMARY KEY  (`id`),
                  KEY `calendars_id` (`calendars_id`),
                  KEY `day` (`day`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.80 create glpi_calendarsegments " . $LANG['update'][90] . $DB->error());
      // add defautl days : from monday to friday
      if ($default_calendar_id>0) {
         $query="SELECT `planning_begin`, `planning_end` FROM `glpi_configs` WHERE id=1";
         if ($result = $DB->query($query)) {
            $begin=$DB->result($result,0,'planning_begin');
            $end=$DB->result($result,0,'planning_end');
            if ($begin < $end) {
               for ($i=1;$i<6;$i++) {
                  $query="INSERT INTO `glpi_calendarsegments` (`calendars_id`,`day`,`begin`,`end`)
                        VALUES ($default_calendar_id,$i,'$begin','$end');";
                  $DB->query($query) or die("0.80 add default glpi_calendarsegments " . $LANG['update'][90] . $DB->error());
               }
            }
         }

         // Update calendar
         include_once (GLPI_ROOT . "/inc/commondropdown.class.php");
         include_once (GLPI_ROOT . "/inc/commondbchild.class.php");
         include_once (GLPI_ROOT . "/inc/calendarsegment.class.php");
         include_once (GLPI_ROOT . "/inc/calendar.class.php");
         $calendar=new Calendar();
         if ($calendar->getFromDB($default_calendar_id)) {
            $query="UPDATE `glpi_calendars` SET `cache_duration`='".exportArrayToDB($calendar->getDaysDurations())."'
                        WHERE `id`='$default_calendar_id';";
                  $DB->query($query) or die("0.80 update default calendar cache " . $LANG['update'][90] . $DB->error());
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

      $ADDTODISPLAYPREF['Holiday']=array(11,12,13);

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
      $DB->query($query) or die("0.80 create glpi_calendars_holidays " . $LANG['update'][90] . $DB->error());
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

      $ADDTODISPLAYPREF['SLA']=array(4);

      // Get first Ticket template
      $query="SELECT id FROM `glpi_notificationtemplates` WHERE `itemtype` LIKE 'Ticket%' ORDER BY id ASC";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {

            $query="INSERT INTO `glpi_notifications`
                                    VALUES (NULL, 'Ticket Recall', 0, 'Ticket', 'recall',
                                             'mail',".$DB->result($result,0,0).",
                                             '', 1, 1, NOW());";
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
      $DB->query($query) or die("0.80 create glpi_slalevelactions " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_profiles','calendar')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `calendar` CHAR( 1 ) NULL";
      $DB->query($query) or die("0.80 add calendar in glpi_profiles". $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `calendar`=`entity_dropdown`";
      $DB->query($query) or die("0.80 add calendar right users which are able to write entity_dropdown " . $LANG['update'][90] . $DB->error());
   }


   if (!FieldExists('glpi_tickets','slas_id')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `slas_id` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add slas_id in glpi_tickets". $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_tickets` ADD INDEX `slas_id` (`slas_id`)";
      $DB->query($query) or die("0.80 add index on slas_id in glpi_tickets". $LANG['update'][90] . $DB->error());

   }

   if (!FieldExists('glpi_tickets','slalevels_id')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `slalevels_id` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add slalevels_id in glpi_tickets". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','due_date')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `due_date` datetime default NULL";
      $DB->query($query) or die("0.80 add due_date in glpi_tickets". $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_tickets` ADD INDEX `due_date` (`due_date`)";
      $DB->query($query) or die("0.80 add index on due_date in glpi_tickets". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','begin_waiting_date')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `begin_waiting_date` datetime default NULL";
      $DB->query($query) or die("0.80 add begin_waiting_date in glpi_tickets". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','sla_waiting_duration')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `sla_waiting_duration` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add sla_waiting_duration in glpi_tickets". $LANG['update'][90] . $DB->error());
   }


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
      $DB->query($query) or die("0.80 create glpi_slalevels_tickets " . $LANG['update'][90] . $DB->error());

      $query="INSERT INTO `glpi_crontasks`
         (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
         VALUES
         ('SlaLevel_Ticket', 'slaticket', 300, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL)";
      $DB->query($query) or die("0.80 populate glpi_crontasks for slaticket" . $LANG['update'][90] . $DB->error());

   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - PasswordForget'); // Updating schema

   if (!FieldExists('glpi_users','token')) {
      $query = "ALTER TABLE `glpi_users` ADD `token` char( 40 ) NULL DEFAULT ''";
      $DB->query($query) or die("0.80 add token in glpi_users". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_users','tokendate')) {
      $query = "ALTER TABLE `glpi_users` ADD `tokendate` datetime NULL DEFAULT NULL";
      $DB->query($query) or die("0.80 add tokendate in glpi_users". $LANG['update'][90] . $DB->error());
   }

   $query="SELECT * FROM `glpi_notificationtemplates` WHERE `name` = 'Password Forget'";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                     VALUES(NULL, 'Password Forget', 'User', NOW(),'');";
         $DB->query($query) or die("0.80 add password forget notification" . $LANG['update'][90] . $DB->error());
         $notid=$DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                 VALUES(NULL, $notid, '','##user.action##',
                        '##lang.user.realname## ##lang.user.firstname## 

##lang.user.information## 

##lang.user.link## ##user.passwordforgeturl##',
                        '&lt;p&gt;&lt;strong&gt;##lang.user.realname## ##lang.user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.user.information##&lt;/p&gt;
&lt;p&gt;##lang.user.link## &lt;a title=\"##user.passwordforgeturl##\" href=\"##user.passwordforgeturl##\"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;');"; 
      $DB->query($query) or die("0.80 add password forget notification translation" . $LANG['update'][90] . $DB->error());

      $query="INSERT INTO `glpi_notifications`
                                VALUES (NULL, 'Password Forget', 0, 'User', 'passwordforget',
                                       'mail',$notid,
                                       '', 1, 1, NOW());";
      $DB->query($query) or die("0.80 add password forget notification" . $LANG['update'][90] . $DB->error());
      $notifid=$DB->insert_id();

      $query = "INSERT INTO `glpi_notificationtargets`
                     (`id`, `notifications_id`, `type`, `items_id`)
                     VALUES (NULL, $notifid, 1, 19);";
      $DB->query($query) or die("0.80 add password forget notification target" . $LANG['update'][90] . $DB->error());
      }
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - Ticket'); // Updating schema


   if (!FieldExists('glpi_tickets','ticket_waiting_duration')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `ticket_waiting_duration` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add ticket_waiting_duration in glpi_tickets". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_entitydatas','calendars_id')) {
      $query = "ALTER TABLE `glpi_entitydatas` ADD `calendars_id` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add calendars_id in glpi_entitydatas". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','close_delay_stat')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `close_delay_stat` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add close_delay_stat in glpi_tickets". $LANG['update'][90] . $DB->error());
      // Manage stat computation for existing tickets
      $query="UPDATE `glpi_tickets`
               SET `close_delay_stat` = (UNIX_TIMESTAMP(`glpi_tickets`.`closedate`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`))
               WHERE `glpi_tickets`.`status` = 'closed'
                  AND `glpi_tickets`.`date` IS NOT NULL
                  AND `glpi_tickets`.`closedate` IS NOT NULL
                  AND `glpi_tickets`.`closedate` > `glpi_tickets`.`date`";
      $DB->query($query) or die("0.80 update ticket close_delay_stat value". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','solve_delay_stat')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `solve_delay_stat` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add solve_delay_stat in glpi_tickets". $LANG['update'][90] . $DB->error());
      // Manage stat computation for existing tickets
      $query="UPDATE `glpi_tickets`
               SET `solve_delay_stat` = (UNIX_TIMESTAMP(`glpi_tickets`.`solvedate`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`))
               WHERE (`glpi_tickets`.`status` = 'closed' OR `glpi_tickets`.`status` = 'solved')
                  AND `glpi_tickets`.`date` IS NOT NULL
                  AND `glpi_tickets`.`solvedate` IS NOT NULL
                  AND `glpi_tickets`.`solvedate` > `glpi_tickets`.`date`";
      $DB->query($query) or die("0.80 update solve_delay_stat values in glpi_tickets". $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','takeintoaccount_delay_stat')) {
      $query = "ALTER TABLE `glpi_tickets` ADD `takeintoaccount_delay_stat` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 add takeintoaccount_delay_stat in glpi_tickets". $LANG['update'][90] . $DB->error());

      // Manage stat computation for existing tickets
      // Solved tickets
      $query="SELECT `glpi_tickets`.`id` AS ID,
                  MIN(UNIX_TIMESTAMP(`glpi_tickets`.`solvedate`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS OPEN,
                  MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST,
                  MIN(UNIX_TIMESTAMP(`glpi_tickettasks`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST2
               FROM `glpi_tickets`
               LEFT JOIN `glpi_ticketfollowups` ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`)
               LEFT JOIN `glpi_tickettasks` ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`)
               WHERE (`glpi_tickets`.`status` = 'closed' OR `glpi_tickets`.`status` = 'solved')
                     AND `glpi_tickets`.`solvedate` IS NOT NULL
               GROUP BY `glpi_tickets`.`id`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $firstactiontime=min($data['OPEN'],$data['FIRST'],$data['FIRST2']);
               $firstactiontime=max(0,$firstactiontime);
               $query2="UPDATE `glpi_tickets` SET `takeintoaccount_delay_stat`='$firstactiontime'
                        WHERE `id` = '".$data['ID']."'";
               $DB->query($query2) or die("0.80 update takeintoaccount_delay_stat values for #".
                                          $data['ID']." ". $LANG['update'][90] . $DB->error());
            }
         }
      }
      // Not solved tickets
      $query="SELECT `glpi_tickets`.`id` AS ID,
                  MIN(UNIX_TIMESTAMP(`glpi_ticketfollowups`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST,
                  MIN(UNIX_TIMESTAMP(`glpi_tickettasks`.`date`) - UNIX_TIMESTAMP(`glpi_tickets`.`date`)) AS FIRST2
               FROM `glpi_tickets`
               LEFT JOIN `glpi_ticketfollowups` ON (`glpi_ticketfollowups`.`tickets_id` = `glpi_tickets`.`id`)
               LEFT JOIN `glpi_tickettasks` ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`)
               WHERE (`glpi_tickets`.`status` <> 'closed' AND `glpi_tickets`.`status` <> 'solved')
                     OR `glpi_tickets`.`solvedate` IS NULL
               GROUP BY `glpi_tickets`.`id`";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $firstactiontime=min($data['FIRST'],$data['FIRST2']);
               $firstactiontime=max(0,$firstactiontime);
               $query2="UPDATE `glpi_tickets` SET `takeintoaccount_delay_stat`='$firstactiontime'
                        WHERE `id` = '".$data['ID']."'";
               $DB->query($query2) or die("0.80 update takeintoaccount_delay_stat values for #".
                                          $data['ID']." ". $LANG['update'][90] . $DB->error());
            }
         }
      }

   }

   // Put realtime in seconds
   if (!FieldExists('glpi_tickets','actiontime')) {

      $query = "ALTER TABLE `glpi_tickets` ADD `actiontime` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 alter realtime to actiontime in glpi_tickets". $LANG['update'][90] . $DB->error());

      if (FieldExists('glpi_tickets','realtime')) {
         $query = "UPDATE `glpi_tickets` SET `actiontime` = ROUND(realtime * 3600)";
         $DB->query($query) or die("0.80 compute actiontime value in glpi_tickets". $LANG['update'][90] . $DB->error());
         $query = "ALTER TABLE `glpi_tickets` DROP `realtime`";
         $DB->query($query) or die("0.80 alter realtime in glpi_tickets". $LANG['update'][90] . $DB->error());
      }
   }

   if (!FieldExists('glpi_tickettasks','actiontime')) {

      $query = "ALTER TABLE `glpi_tickettasks` ADD `actiontime` INT( 11 ) NOT NULL DEFAULT 0";
      $DB->query($query) or die("0.80 alter realtime to actiontime in glpi_tickettasks". $LANG['update'][90] . $DB->error());

      if (FieldExists('glpi_tickettasks','realtime')) {
         $query = "UPDATE `glpi_tickettasks` SET `actiontime` = ROUND(realtime * 3600)";
         $DB->query($query) or die("0.80 compute actiontime value in glpi_tickettasks". $LANG['update'][90] . $DB->error());

         $query = "ALTER TABLE `glpi_tickettasks` DROP `realtime`";
         $DB->query($query) or die("0.80 alter realtime in glpi_tickettasks". $LANG['update'][90] . $DB->error());
      }
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - Software'); // Updating schema


   if (!FieldExists("glpi_softwareversions","operatingsystems_id")) {
      $query = "ALTER TABLE  `glpi_softwareversions` ADD  `operatingsystems_id` INT( 11 ) NOT NULL";
      $DB->query($query) or die("0.80 add operatingsystems_id field in glpi_softwareversions" . $LANG['update'][90] . $DB->error());
      $query = "ALTER TABLE `glpi_softwareversions` ADD INDEX `operatingsystems_id` (`operatingsystems_id`)";
      $DB->query($query) or die("0.80 add index on operatingsystems_id in glpi_softwareversions". $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_softwareversions`,
                        (SELECT `id`, `operatingsystems_id` FROM `glpi_softwares`) AS SOFT
                           SET `glpi_softwareversions`.`operatingsystems_id` = `SOFT`.`operatingsystems_id`
                       WHERE `glpi_softwareversions`.`softwares_id` = `SOFT`.`id` ";
      $DB->query($query) or die("0.80 transfer operatingsystems_id from glpi_softwares to glpi_softwareversions" . $LANG['update'][90] . $DB->error());
      $query = "ALTER TABLE  `glpi_softwares` DROP  `operatingsystems_id`";
      $DB->query($query) or die("0.80 drop operatingsystems_id field in glpi_softwares " . $LANG['update'][90] . $DB->error());
   }


   if (!isIndex("glpi_computers_softwareversions","unicity")) {
      // clean datas
      $query="SELECT `computers_id`, `softwareversions_id`, COUNT(*) AS CPT 
               FROM `glpi_computers_softwareversions` 
               GROUP BY `computers_id`, `softwareversions_id` 
               HAVING CPT > 1";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $query2="SELECT `id` FROM `glpi_computers_softwareversions`
                        WHERE `computers_id` = '".$data['computers_id']."' 
                           AND `softwareversions_id` = '".$data['softwareversions_id']."'
                        LIMIT 1";
               if ($result2= $DB->query($query2)) {
                  if ($DB->numrows($result2)) {
                     $keep_id=$DB->result($result2,0,0);
                     $query3="DELETE FROM `glpi_computers_softwareversions`
                           WHERE `computers_id` = '".$data['computers_id']."' 
                           AND `softwareversions_id` = '".$data['softwareversions_id']."'
                           AND `id` <> $keep_id";
                     $DB->query($query3) or die("0.80 clean glpi_computers_softwareversions " . $LANG['update'][90] . $DB->error());
                  }
               }
            }
         }
      }
      $query="ALTER TABLE `glpi_computers_softwareversions` ADD UNIQUE `unicity` ( `computers_id` , `softwareversions_id` )";
      $DB->query($query) or die("0.80 add unicity index from glpi_computers_softwareversions " . $LANG['update'][90] . $DB->error());
   }   

   if (isIndex("glpi_computers_softwareversions","computers_id")) {
      $query="ALTER TABLE `glpi_computers_softwareversions` DROP INDEX `computers_id`";
      $DB->query($query) or die("0.80 drop computers_id index from glpi_computers_softwareversions " . $LANG['update'][90] . $DB->error());
   }

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
      $DB->query($query) or die("0.80 create glpi_tickettasks " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_softwarelicenses","computers_id")) {
      $query = "SELECT * FROM `glpi_softwarelicenses` WHERE `computers_id` > 0 and `computers_id` IS NOT NULL";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $query="INSERT INTO `glpi_computers_softwarelicenses` (`computers_id`, `softwarelicenses_id`) 
                              VALUES  ('".$data['computers_id']."','".$data['id']."')";
               $DB->query($query) or die("0.80 migrate data to computers_softwarelicenses table " . $LANG['update'][90] . $DB->error());
            }
         }
      }

      $query = "ALTER TABLE `glpi_softwarelicenses` DROP `computers_id`";
      $DB->query($query) or die("0.80 drop computers_id field in glpi_softwarelicenses " . $LANG['update'][90] . $DB->error());
   }
   

   // TODO : MIgrate data from existig computers_id field of license
   // Drop computers_id field in license

   displayMigrationMessage("080", $LANG['update'][141] . ' - Common'); // Updating schema


   if (!FieldExists("glpi_softwarelicenses","date_mod")) {
      $query = "ALTER TABLE `glpi_softwarelicenses` ADD `date_mod`  DATETIME NULL, ADD INDEX `date_mod` (`date_mod`)";
      $DB->query($query) or die("0.80 add date_mod field in glpi_softwarelicenses " . $LANG['update'][90] . $DB->error());
   }

   if (TableExists("glpi_cartridges_printermodels")) {
      $query = "RENAME TABLE `glpi_cartridges_printermodels`  TO `glpi_cartridgeitems_printermodels`  ;";
      $DB->query($query) or die("0.80 rename glpi_cartridges_printermodels " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_monitors","have_hdmi")) {
      $query = "ALTER TABLE `glpi_monitors` ADD `have_hdmi`  tinyint(1) NOT NULL DEFAULT 0 AFTER `have_pivot`";
      $DB->query($query) or die("0.80 add have_hdmi field in glpi_monitors " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_configs","dbreplicate_email")) {
      $query = "ALTER TABLE `glpi_configs` DROP `dbreplicate_email`";
      $DB->query($query) or die("0.80 drop dbreplicate_email field in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","auto_create_infocoms")) {
      $query = "ALTER TABLE `glpi_configs` ADD `auto_create_infocoms` tinyint( 1 ) NOT NULL DEFAULT '0' ";
      $DB->query($query) or die("0.80 add auto_create_infocoms field in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","csv_delimiter")) {
      $query = "ALTER TABLE `glpi_configs` ADD `csv_delimiter` CHAR( 1 ) NOT NULL AFTER `number_format` ";
      $DB->query($query) or die("0.80 add csv_delimiter field in glpi_configs " . $LANG['update'][90] . $DB->error());
      $query = "UPDATE `glpi_configs` SET `csv_delimiter` = ';'";
      $DB->query($query);
   }

   if (!FieldExists("glpi_users","csv_delimiter")) {
      $query = "ALTER TABLE `glpi_users` ADD `csv_delimiter` CHAR( 1 ) NULL AFTER `number_format` ";
      $DB->query($query) or die("0.80 add csv_delimiter field in glpi_users " . $LANG['update'][90] . $DB->error());

   }

   if (!FieldExists('glpi_users','names_format')) {
      $query = "ALTER TABLE `glpi_users`
                ADD `names_format` INT( 11 ) NULL DEFAULT NULL AFTER `number_format`";

      $DB->query($query) or die("0.80 add names_format in glpi_users" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (isIndex("glpi_budgets","end_date")) {
      $query = "ALTER TABLE `glpi_budgets` DROP INDEX `end_date` ";
      $DB->query($query) or die("0.80 correct end_date index " . $LANG['update'][90] . $DB->error());
   }

   if (!isIndex("glpi_budgets","end_date")) {
      $query = "ALTER TABLE `glpi_budgets` ADD INDEX `end_date` ( `end_date` ) ";
      $DB->query($query) or die("0.80 correct end_date index " . $LANG['update'][90] . $DB->error());
   }


   if (!FieldExists('glpi_ocsservers','ocs_db_utf8')) {
      $query = "ALTER TABLE `glpi_ocsservers`
                ADD `ocs_db_utf8` tinyint(1) NOT NULL default '0' AFTER `ocs_db_name`";

      $DB->query($query) or die("0.80 add ocs_db_utf8 in glpi_ocsservers" .
                                 $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("080", $LANG['update'][142] . ' - glpi_displaypreferences');

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query="SELECT DISTINCT users_id FROM glpi_displaypreferences WHERE itemtype='$type';";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query="SELECT max(rank) FROM glpi_displaypreferences
                           WHERE users_id='".$data['users_id']."' AND `itemtype`='$type';";
               $result=$DB->query($query);
               $rank=$DB->result($result,0,0);
               $rank++;
               foreach ($tab as $newval) {
                  $query="SELECT * FROM glpi_displaypreferences
                           WHERE users_id='".$data['users_id']."' AND num=$newval AND itemtype='$type';";
                  if ($result2=$DB->query($query)) {
                     if ($DB->numrows($result2)==0) {
                        $query="INSERT INTO glpi_displaypreferences (`itemtype` ,`num` ,`rank` ,`users_id`)
                                 VALUES ('$type', '$newval', '".$rank++."', '".$data['users_id']."');";
                        $DB->query($query);
                     }
                  }
               }
            }
         } else { // Add for default user
            $rank=1;
            foreach ($tab as $newval) {
               $query="INSERT INTO glpi_displaypreferences (`itemtype` ,`num` ,`rank` ,`users_id`)
                        VALUES ('$type', '$newval', '".$rank++."', '0');";
               $DB->query($query);
            }
         }
      }
   }

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("080"); // End

   return $updateresult;
}
?>
