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
 * Update from 0.83.1 to 0.84
 *
 * @return bool for success (will die for most error)
**/
function update0831to084() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.84'));
   $migration->setVersion('0.84');

   // Add the internet field and copy rights from networking
   $migration->addField('glpi_profiles', 'internet', 'char', ['after'  => 'networking',
                                                                   'update' => '`networking`']);

   $backup_tables = false;
   $newtables     = ['glpi_contractcosts',
                          'glpi_entities_rssfeeds', 'glpi_groups_rssfeeds',
                          'glpi_problems_suppliers', 'glpi_profiles_rssfeeds',
                          'glpi_rssfeeds_users', 'glpi_rssfeeds',
                          'glpi_suppliers_tickets', 'glpi_ticketcosts'];

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if ($DB->tableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.",
                                 true);
   }

   updateNetworkFramework($ADDTODISPLAYPREF);

   $migration->addField('glpi_mailcollectors', 'accepted', 'string');
   $migration->addField('glpi_mailcollectors', 'refused', 'string');
   $migration->addField('glpi_mailcollectors', 'use_kerberos', 'bool', ['value' => 0]);
   $migration->addField("glpi_mailcollectors", 'errors', "integer");
   $migration->addField("glpi_mailcollectors", 'use_mail_date', "bool", ['value' => 0]);

   // Password security
   $migration->addField('glpi_configs', 'use_password_security', 'bool');
   $migration->addField('glpi_configs', 'password_min_length', 'integer', ['value' => 8]);
   $migration->addField('glpi_configs', 'password_need_number', 'bool', ['value' => 1]);
   $migration->addField('glpi_configs', 'password_need_letter', 'bool', ['value' => 1]);
   $migration->addField('glpi_configs', 'password_need_caps', 'bool', ['value' => 1]);
   $migration->addField('glpi_configs', 'password_need_symbol', 'bool', ['value' => 1]);

   $migration->addField('glpi_configs', 'use_check_pref', 'bool');

   // Ajax buffer time
   $migration->addField('glpi_configs', 'ajax_buffertime_load', 'integer',
                        ['value' => 0,
                              'after' => 'ajax_min_textsearch_load']);

   // Clean display prefs
   $query = "UPDATE `glpi_displaypreferences`
             SET `num` = 160
             WHERE `itemtype` = 'Software'
                   AND `num` = 7";
   $DB->query($query);

   // Update bookmarks from States to AllAssets
   foreach ($DB->request("glpi_bookmarks", "`itemtype` = 'States'") as $data) {
      $query = str_replace('itemtype=States', 'itemtype=AllAssets', $data['query']);
      $query = "UPDATE `glpi_bookmarks`
                SET query = '".addslashes($query)."'
                WHERE `id` = '".$data['id']."'";
      $DB->query($query);
   }
   $query = "UPDATE `glpi_bookmarks`
             SET `itemtype` = 'AllAssets', `path` = 'front/allassets.php'
             WHERE `itemtype` = 'States'";
   $DB->query($query);

   $query = "UPDATE `glpi_displaypreferences`
             SET `itemtype` = 'AllAssets'
             WHERE `itemtype` = 'States'";
   $DB->query($query);

   if ($DB->tableExists('glpi_networkportmigrations')) {
      $migration->displayWarning("You should have a look at the \"migration cleaner\" tool !", true);
      $migration->displayWarning("With it, you should re-create the networks topologies and the links between the networks and the addresses",
                                 true);
   }

   $lang_to_update = ['ca_CA' => 'ca_ES',
                           'dk_DK' => 'da_DK',
                           'ee_ET' => 'et_EE',
                           'el_EL' => 'el_GR',
                           'he_HE' => 'he_IL',
                           'no_NB' => 'nb_NO',
                           'no_NN' => 'nn_NO',
                           'ua_UA' => 'uk_UA',];
   foreach ($lang_to_update as $old => $new) {
      $query = "UPDATE `glpi_configs`
               SET `language` = '$new'
               WHERE `language` = '$old';";
      $DB->queryOrDie($query, "0.84 language in config $old to $new");

      $query = "UPDATE `glpi_users`
               SET `language` = '$new'
               WHERE `language` = '$old';";
      $DB->queryOrDie($query, "0.84 language in users $old to $new");
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'tickets and problems status'));

   $status  = ['new'           => CommonITILObject::INCOMING,
                    'assign'        => CommonITILObject::ASSIGNED,
                    'plan'          => CommonITILObject::PLANNED,
                    'waiting'       => CommonITILObject::WAITING,
                    'solved'        => CommonITILObject::SOLVED,
                    'closed'        => CommonITILObject::CLOSED,
                    'accepted'      => CommonITILObject::ACCEPTED,
                    'observe'       => CommonITILObject::OBSERVED,
                    'evaluation'    => CommonITILObject::EVALUATION,
                    'approbation'   => CommonITILObject::APPROVAL,
                    'test'          => CommonITILObject::TEST,
                    'qualification' => CommonITILObject::QUALIFICATION];
   foreach (['glpi_tickets', 'glpi_problems'] as $table) {
      // Migrate datas
      foreach ($status as $old => $new) {
         $query = "UPDATE `$table`
                   SET `status` = '$new'
                   WHERE `status` = '$old'";
         $DB->queryOrDie($query, "0.84 status in $table $old to $new");
      }
      $migration->changeField($table, 'status', 'status', 'integer',
                              ['value' => CommonITILObject::INCOMING]);
   }

   // Migrate templates
   $query = "SELECT `glpi_notificationtemplatetranslations`.*
             FROM `glpi_notificationtemplatetranslations`
             INNER JOIN `glpi_notificationtemplates`
                  ON (`glpi_notificationtemplates`.`id`
                        = `glpi_notificationtemplatetranslations`.`notificationtemplates_id`)
             WHERE `glpi_notificationtemplatetranslations`.`content_text` LIKE '%storestatus=%'
                   OR `glpi_notificationtemplatetranslations`.`content_html` LIKE '%storestatus=%'
                   OR `glpi_notificationtemplatetranslations`.`subject` LIKE '%storestatus=%'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $subject = $data['subject'];
            $text    = $data['content_text'];
            $html    = $data['content_html'];
            foreach ($status as $old => $new) {
               $subject = str_replace("ticket.storestatus=$old", "ticket.storestatus=$new", $subject);
               $text    = str_replace("ticket.storestatus=$old", "ticket.storestatus=$new", $text);
               $html    = str_replace("ticket.storestatus=$old", "ticket.storestatus=$new", $html);
               $subject = str_replace("problem.storestatus=$old", "problem.storestatus=$new", $subject);
               $text    = str_replace("problem.storestatus=$old", "problem.storestatus=$new", $text);
               $html    = str_replace("problem.storestatus=$old", "problem.storestatus=$new", $html);
            }
            $query = "UPDATE `glpi_notificationtemplatetranslations`
                      SET `subject` = '".addslashes($subject)."',
                          `content_text` = '".addslashes($text)."',
                          `content_html` = '".addslashes($html)."'
                      WHERE `id` = ".$data['id']."";
            $DB->queryOrDie($query, "0.84 fix tags usage for storestatus");
         }
      }
   }

   // Update Rules
   $changes                = [];
   $changes['RuleTicket']  = 'status';

   $DB->query("SET SESSION group_concat_max_len = 4194304;");
   foreach ($changes as $ruletype => $field) {
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
            foreach ($status as $old => $new) {
               $query = "UPDATE `glpi_ruleactions`
                         SET `value` = '$new'
                         WHERE `field` = '$field'
                               AND `value` = '$old'
                               AND `rules_id` IN ($rules)";

               $DB->queryOrDie($query, "0.84 update datas for rules actions");
            }
         }
      }
   }

   // Update glpi_profiles : ticket_status
   foreach ($DB->request('glpi_profiles') as $data) {
      $fields_to_decode = ['ticket_status','problem_status'];
      foreach ($fields_to_decode as $field) {
         $tab = importArrayFromDB($data[$field]);
         if (is_array($tab)) {
            $newtab = [];
            foreach ($tab as $key => $values) {
               foreach ($values as $key2 => $val2) {
                  $newtab[$status[$key]][$status[$key2]] = $val2;
               }
            }

            $query  = "UPDATE `glpi_profiles`
                       SET `$field` = '".addslashes(exportArrayToDB($newtab))."'
                       WHERE `id` = '".$data['id']."'";
            $DB->queryOrDie($query, "0.84 migrate $field of glpi_profiles");
         }
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'),
                                      'Merge entity and entitydatas'));

   if ($DB->tableExists('glpi_entitydatas')) {
      $migration->changeField('glpi_entities', 'id', 'id', 'integer');
      $migration->migrationOneTable('glpi_entities');
      // pour que la procedure soit re-entrante
      if (countElementsInTable("glpi_entities", ['id' => '0']) < 1) {
         // Create root entity
         $query = "INSERT INTO `glpi_entities`
                          (`id`, `name`, `completename`, `entities_id`, `level`)
                   VALUES (0,'".addslashes(__('Root entity'))."',
                           '".addslashes(__('Root entity'))."', '-1', '1');";

         $DB->queryOrDie($query, '0.84 insert root entity into glpi_entities');
      }
      //       $newID = $DB->insert_id();
      //       $query = "UPDATE `glpi_entities`
      //                 SET `id` = '0'
      //                 WHERE `id` = '$newID'";
      //       $DB->queryOrDie($query, '0.84 be sure that id of the root entity if 0 in glpi_entities');

      $migration->addField("glpi_entities", 'address', "text");
      $migration->addField("glpi_entities", 'postcode', "string");
      $migration->addField("glpi_entities", 'town', "string");
      $migration->addField("glpi_entities", 'state', "string");
      $migration->addField("glpi_entities", 'country', "string");
      $migration->addField("glpi_entities", 'website', "string");
      $migration->addField("glpi_entities", 'phonenumber', "string");
      $migration->addField("glpi_entities", 'fax', "string");
      $migration->addField("glpi_entities", 'email', "string");
      $migration->addField("glpi_entities", 'admin_email', "string");
      $migration->addField("glpi_entities", 'admin_email_name', "string");
      $migration->addField("glpi_entities", 'admin_reply', "string");
      $migration->addField("glpi_entities", 'admin_reply_name', "string");
      $migration->addField("glpi_entities", 'notification_subject_tag', "string");
      $migration->addField("glpi_entities", 'notepad', "longtext");
      $migration->addField("glpi_entities", 'ldap_dn', "string");
      $migration->addField("glpi_entities", 'tag', "string");
      $migration->addField("glpi_entities", 'authldaps_id', "integer");
      $migration->addField("glpi_entities", 'mail_domain', "string");
      $migration->addField("glpi_entities", 'entity_ldapfilter', "text");
      $migration->addField("glpi_entities", 'mailing_signature', "text");
      $migration->addField("glpi_entities", 'cartridges_alert_repeat', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'consumables_alert_repeat', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'use_licenses_alert', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'use_contracts_alert', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'use_infocoms_alert', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'use_reservations_alert', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'autoclose_delay', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'notclosed_delay', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'calendars_id', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'auto_assign_mode', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'tickettype', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'max_closedate', "datetime");
      $migration->addField("glpi_entities", 'inquest_config', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'inquest_rate', "integer");
      $migration->addField("glpi_entities", 'inquest_delay', "integer", ['value' => -10]);
      $migration->addField("glpi_entities", 'inquest_URL', "string");
      $migration->addField("glpi_entities", 'autofill_warranty_date', "string",
                                             ['value' => -2]);
      $migration->addField("glpi_entities", 'autofill_use_date', "string", ['value' => -2]);
      $migration->addField("glpi_entities", 'autofill_buy_date', "string", ['value' => -2]);
      $migration->addField("glpi_entities", 'autofill_delivery_date', "string",
                                             ['value' => -2]);
      $migration->addField("glpi_entities", 'autofill_order_date', "string", ['value' => -2]);
      $migration->addField("glpi_entities", 'tickettemplates_id', "integer", ['value' => -2]);
      $migration->addField("glpi_entities", 'entities_id_software', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'default_contract_alert', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'default_infocom_alert', "integer",
                           ['value' => -2]);
      $migration->addField("glpi_entities", 'default_alarm_threshold', "integer",
                           ['value' => -2]);
      $migration->migrationOneTable('glpi_entities');

      $fields = ['address', 'postcode', 'town', 'state', 'country', 'website',
                      'phonenumber', 'fax', 'email', 'admin_email', 'admin_email_name',
                      'admin_reply', 'admin_reply_name', 'notification_subject_tag',
                      'notepad', 'ldap_dn', 'tag', 'authldaps_id', 'mail_domain',
                      'entity_ldapfilter', 'mailing_signature', 'cartridges_alert_repeat',
                      'consumables_alert_repeat', 'use_licenses_alert', 'use_contracts_alert',
                      'use_infocoms_alert', 'use_reservations_alert', 'autoclose_delay',
                      'notclosed_delay', 'calendars_id', 'auto_assign_mode', 'tickettype',
                      'max_closedate', 'inquest_config', 'inquest_rate', 'inquest_delay',
                      'inquest_URL', 'autofill_warranty_date', 'autofill_use_date',
                      'autofill_buy_date', 'autofill_delivery_date', 'autofill_order_date',
                      'tickettemplates_id', 'entities_id_software', 'default_contract_alert',
                      'default_infocom_alert', 'default_alarm_threshold'];
      $entity = new Entity();
      foreach ($DB->request('glpi_entitydatas') as $data) {
         if ($entity->getFromDB($data['entities_id'])) {
            $update_fields = [];
            foreach ($fields as $field) {
               if (is_null($data[$field])) {
                  $update_fields[] = "`$field` = NULL";
               } else {
                  $update_fields[] = "`$field` = '".addslashes($data[$field])."'";
               }
            }

            $query  = "UPDATE `glpi_entities`
                       SET ".implode(',', $update_fields)."
                       WHERE `id` = '".$data['entities_id']."'";
            $DB->queryOrDie($query, "0.84 transfer datas from glpi_entitydatas to glpi_entities");
         } else {
            $migration->displayMessage('Entity ID '.$data['entities_id'].' does not exist');
         }

      }
      $migration->dropTable('glpi_entitydatas');
   }
   regenerateTreeCompleteName("glpi_entities");

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'copy entity information to computers_softwareversions'));

   if ($migration->addField("glpi_computers_softwareversions", "entities_id", "integer")) {
      $migration->migrationOneTable('glpi_computers_softwareversions');

      $query3 = "UPDATE `glpi_computers_softwareversions`
                 LEFT JOIN `glpi_computers`
                    ON `computers_id`=`glpi_computers`.`id`
                 SET `glpi_computers_softwareversions`.`entities_id` = `glpi_computers`.`entities_id`";

      $DB->queryOrDie($query3, "0.84 update entities_id in glpi_computers_softwareversions");

      /// create index for search count on tab
      $migration->addKey("glpi_computers_softwareversions",
                         ['entities_id', 'is_template', 'is_deleted'],
                         'computers_info');
      $migration->addKey("glpi_computers_softwareversions", 'is_template');
      $migration->addKey("glpi_computers_softwareversions", 'is_deleted');
   }

   /// create new index for search
   $migration->addKey("glpi_softwarelicenses", ['softwares_id', 'expire'],
                      'softwares_id_expire');
   $migration->dropKey("glpi_softwarelicenses", 'softwares_id');

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'create validation_answer notification'));

   // Check if notifications already exists
   if (countElementsInTable('glpi_notifications',
                            ['itemtype' => 'Ticket',
                             'event'    => 'validation_answer'])==0) {
      // No notifications duplicate all

      $query = "SELECT *
                FROM `glpi_notifications`
                WHERE `itemtype` = 'Ticket'
                      AND `event` = 'validation'";
      foreach ($DB->request($query) as $notif) {
         $query = "INSERT INTO `glpi_notifications`
                          (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                          `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                          `date_mod`)
                   VALUES ('".addslashes($notif['name'])." Answer',
                           '".$notif['entities_id']."', 'Ticket',
                           'validation_answer', '".$notif['mode']."',
                           '".$notif['notificationtemplates_id']."',
                           '".addslashes($notif['comment'])."', '".$notif['is_recursive']."',
                           '".$notif['is_active']."', NOW());";
         $DB->queryOrDie($query, "0.84 insert validation_answer notification");
         $newID  = $DB->insert_id();
         $query2 = "SELECT *
                    FROM `glpi_notificationtargets`
                    WHERE `notifications_id` = '".$notif['id']."'";
         foreach ($DB->request($query2) as $target) {
            $query = "INSERT INTO `glpi_notificationtargets`
                             (`notifications_id`, `type`, `items_id`)
                      VALUES ($newID, '".$target['type']."', '".$target['items_id']."')";
            $DB->queryOrDie($query, "0.84 insert targets for validation_answer notification");
         }
      }
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'create contracts notification'));

   $from_to = ['end'    => 'periodicity',
                    'notice' => 'periodicitynotice'];
   foreach ($from_to as $from => $to) {
      // Check if notifications already exists
      if (countElementsInTable('glpi_notifications',
                               ['itemtype' => 'Contract', 'event' => $to])==0) {
         // No notifications duplicate all

         $query = "SELECT *
                   FROM `glpi_notifications`
                   WHERE `itemtype` = 'Contract'
                         AND `event` = '$from'";
         foreach ($DB->request($query) as $notif) {
            $query = "INSERT INTO `glpi_notifications`
                             (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                              `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                              `date_mod`)
                      VALUES ('".addslashes($notif['name'])." Periodicity',
                              '".$notif['entities_id']."', 'Contract', '$to', '".$notif['mode']."',
                              '".$notif['notificationtemplates_id']."',
                              '".addslashes($notif['comment'])."', '".$notif['is_recursive']."',
                              '".$notif['is_active']."', NOW());";
            $DB->queryOrDie($query, "0.84 insert contract ".$to." notification");
            $newID  = $DB->insert_id();
            $query2 = "SELECT *
                       FROM `glpi_notificationtargets`
                       WHERE `notifications_id` = '".$notif['id']."'";
            foreach ($DB->request($query2) as $target) {
               $query = "INSERT INTO `glpi_notificationtargets`
                                (`notifications_id`, `type`, `items_id`)
                         VALUES ('".$newID."', '".$target['type']."', '".$target['items_id']."')";
               $DB->queryOrDie($query, "0.84 insert targets for ??ontract ".$to." notification");
            }
         }
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'),
                                      'contract and ticket costs'));

   if (!$DB->tableExists('glpi_contractcosts')) {
      $query = "CREATE TABLE `glpi_contractcosts` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `contracts_id` int(11) NOT NULL DEFAULT '0',
                  `name` varchar(255) DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `begin_date` date DEFAULT NULL,
                  `end_date` date DEFAULT NULL,
                  `cost` decimal(20,4) NOT NULL DEFAULT '0.0000',
                  `budgets_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `contracts_id` (`contracts_id`),
                  KEY `begin_date` (`begin_date`),
                  KEY `end_date` (`end_date`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `budgets_id` (`budgets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 add table glpi_contractcosts");

      $migration->migrationOneTable('glpi_contractcosts');

      foreach ($DB->request('glpi_contracts', "`cost` > 0") as $data) {
         $begin_to_add = "NULL";
         $end_to_add   = "NULL";

         if (!is_null($data['begin_date'])) {
            $begin_to_add = "'".$data['begin_date']."'";

            if ($data['duration']) {
               $end_to_add = "'".date("Y-m-d", strtotime($data['begin_date']. "+".$data['duration']." month"))."'";
            } else {
               $end_to_add = "'".$data['begin_date']."'";
            }

         }
         $query = "INSERT INTO `glpi_contractcosts`
                          (`contracts_id`, `name`, `begin_date`, `end_date`,
                           `cost`,  `entities_id`,
                           `is_recursive`)
                   VALUES ('".$data['id']."', 'Cost', $begin_to_add, $end_to_add,
                           '".$data['cost']."', '".$data['entities_id']."',
                           '".$data['is_recursive']."')";
         $DB->queryOrDie($query, '0.84 move contracts costs');
      }
      $migration->dropField('glpi_contracts', 'cost');
   }

   if (!$DB->tableExists('glpi_ticketcosts')) {
      $query = "CREATE TABLE `glpi_ticketcosts` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `name` varchar(255) DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `begin_date` date DEFAULT NULL,
                  `end_date` date DEFAULT NULL,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  `cost_time` decimal(20,4) NOT NULL DEFAULT '0.0000',
                  `cost_fixed` decimal(20,4) NOT NULL DEFAULT '0.0000',
                  `cost_material` decimal(20,4) NOT NULL DEFAULT '0.0000',
                  `budgets_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `begin_date` (`begin_date`),
                  KEY `end_date` (`end_date`),
                  KEY `entities_id` (`entities_id`),
                  KEY `budgets_id` (`budgets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 add table glpi_ticketcosts");

      $migration->migrationOneTable('glpi_ticketcosts');

      foreach ($DB->request('glpi_tickets', "`cost_time` > 0
                            OR `cost_fixed` > 0
                            OR `cost_material` > 0") as $data) {
         $begin_to_add = "NULL";
         $end_to_add   = "NULL";

         if (!is_null($data['date'])) {
            $begin_to_add = "'".$data['date']."'";

            if (!is_null($data['solvedate'])) {
               $end_to_add = "'".$data['solvedate']."'";
            } else {
               $end_to_add = "'".$data['date']."'";
            }

         }
         $query = "INSERT INTO `glpi_ticketcosts`
                          (`tickets_id`, `name`, `begin_date`, `end_date`,
                           `cost_time`,`cost_fixed`,
                           `cost_material`, `entities_id`,
                           `actiontime`)
                   VALUES ('".$data['id']."', 'Cost', $begin_to_add, $end_to_add,
                           '".$data['cost_time']."','".$data['cost_fixed']."',
                           '".$data['cost_material']."', '".$data['entities_id']."',
                           '".$data['actiontime']."')";
         $DB->queryOrDie($query, '0.84 move tickets costs');
      }
      $migration->dropField('glpi_tickets', 'cost_time');
      $migration->dropField('glpi_tickets', 'cost_fixed');
      $migration->dropField('glpi_tickets', 'cost_material');
   }

   $migration->addField("glpi_profiles", "ticketcost", "char",
                        ['update'    => "'w'",
                              'condition' => " WHERE `update_ticket` = 1"]);
   // Set default to r as before
   $query = "UPDATE `glpi_profiles`
             SET `ticketcost` = 'r'
             WHERE `ticketcost` IS NULL";
   $DB->queryOrDie($query, "0.84 set ticketcost in glpi_profiles");

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'rss flows'));

   if (!$DB->tableExists('glpi_rssfeeds')) {
      $query = "CREATE TABLE `glpi_rssfeeds` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `url` text COLLATE utf8_unicode_ci,
                  `refresh_rate` int(11) NOT NULL DEFAULT '86400',
                  `max_items` int(11) NOT NULL DEFAULT '20',
                  `have_error` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `is_active` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  `date_mod` DATETIME DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `users_id` (`users_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `have_error` (`have_error`),
                  KEY `is_active` (`is_active`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 add table glpi_rssfeeds");
      $ADDTODISPLAYPREF['RSSFeed'] = [2,4,5,19,6,7];
   }
   if (!$DB->tableExists('glpi_rssfeeds_users')) {
      $query = "CREATE TABLE `glpi_rssfeeds_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `rssfeeds_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `rssfeeds_id` (`rssfeeds_id`),
                  KEY `users_id` (`users_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 add table glpi_rssfeeds_users");
   }

   if (!$DB->tableExists('glpi_groups_rssfeeds')) {
      $query = "CREATE TABLE `glpi_groups_rssfeeds` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `rssfeeds_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '-1',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `rssfeeds_id` (`rssfeeds_id`),
                  KEY `groups_id` (`groups_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)

                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 add table glpi_groups_rssfeeds");
   }

   if (!$DB->tableExists('glpi_profiles_rssfeeds')) {
      $query = "CREATE TABLE `glpi_profiles_rssfeeds` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `rssfeeds_id` int(11) NOT NULL DEFAULT '0',
                  `profiles_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '-1',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `rssfeeds_id` (`rssfeeds_id`),
                  KEY `profiles_id` (`profiles_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 add table glpi_profiles_rssfeeds");
   }

   if (!$DB->tableExists('glpi_entities_rssfeeds')) {
      $query = "CREATE TABLE `glpi_entities_rssfeeds` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `rssfeeds_id` int(11) NOT NULL DEFAULT '0',
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `rssfeeds_id` (`rssfeeds_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 add table glpi_entities_rssfeeds");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'planning recalls'));

   if (!$DB->tableExists('glpi_planningrecalls')) {
      $query = "CREATE TABLE `glpi_planningrecalls` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `before_time` int(11) NOT NULL DEFAULT '-10',
                  `when` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `users_id` (`users_id`),
                  KEY `before_time` (`before_time`),
                  KEY `when` (`when`),
                  UNIQUE KEY `unicity` (`itemtype`,`items_id`, `users_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 add table glpi_planningrecalls");
   }

   $query = "SELECT *
             FROM `glpi_notificationtemplates`
             WHERE `itemtype` = 'PlanningRecall'";

   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_notificationtemplates`
                          (`name`, `itemtype`, `date_mod`)
                   VALUES ('Planning recall', 'PlanningRecall', NOW())";
         $DB->queryOrDie($query, "0.84 add planning recall notification");
         $notid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                          (`notificationtemplates_id`, `language`, `subject`,
                           `content_text`,
                           `content_html`)
                   VALUES ($notid, '', '##recall.action##: ##recall.item.name##',
                           '##recall.action##: ##recall.item.name##

##recall.item.content##

##lang.recall.planning.begin##: ##recall.planning.begin##
##lang.recall.planning.end##: ##recall.planning.end##
##lang.recall.planning.state##: ##recall.planning.state##
##lang.recall.item.private##: ##recall.item.private##',
'&lt;p&gt;##recall.action##: &lt;a href=\"##recall.item.url##\"&gt;##recall.item.name##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;##recall.item.content##&lt;/p&gt;
&lt;p&gt;##lang.recall.planning.begin##: ##recall.planning.begin##&lt;br /&gt;##lang.recall.planning.end##: ##recall.planning.end##&lt;br /&gt;##lang.recall.planning.state##: ##recall.planning.state##&lt;br /&gt;##lang.recall.item.private##: ##recall.item.private##&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;
&lt;p&gt;&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;')";
         $DB->queryOrDie($query, "0.84 add planning recall notification translation");

         $query = "INSERT INTO `glpi_notifications`
                          (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                           `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                           `date_mod`)
                   VALUES ('Planning recall', 0, 'PlanningRecall', 'planningrecall', 'mail',
                             $notid, '', 1, 1, NOW())";
         $DB->queryOrDie($query, "0.84 add planning recall notification");
         $notifid = $DB->insert_id();

         $query = "INSERT INTO `glpi_notificationtargets`
                          (`id`, `notifications_id`, `type`, `items_id`)
                   VALUES (NULL, $notifid, ".Notification::USER_TYPE.", ".Notification::AUTHOR.");";
         $DB->queryOrDie($query, "0.84 add planning recall notification target");
      }
   }

   if (!countElementsInTable('glpi_crontasks',
                             ['itemtype' => 'PlanningRecall', 'name' => 'planningrecall'])) {
      $query = "INSERT INTO `glpi_crontasks`
                       (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`,
                        `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                VALUES ('PlanningRecall', 'planningrecall', 300, NULL, 1, 1, 3,
                        0, 24, 30, NULL, NULL, NULL)";
      $DB->queryOrDie($query, "0.84 populate glpi_crontasks for planningrecall");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'various fields'));

   $migration->changeField('glpi_entities', 'default_alarm_threshold',
                           'default_cartridges_alarm_threshold', 'integer', ['value' => -2]);
   $migration->migrationOneTable('glpi_entities');
   $migration->addField("glpi_entities", 'default_consumables_alarm_threshold', "integer",
                        ['value'  => -2,
                              'update' => 'default_cartridges_alarm_threshold']);
   $migration->migrationOneTable('glpi_entities');
   // move -1 to Entity::CONFIG_NEVER
   $query = 'UPDATE `glpi_entities`
             SET `default_consumables_alarm_threshold` = -10
             WHERE `default_consumables_alarm_threshold` = -1';
   $DB->query($query);
   $query = 'UPDATE `glpi_entities`
             SET `default_cartridges_alarm_threshold` = -10
             WHERE `default_cartridges_alarm_threshold` = -1';
   $DB->query($query);

   $migration->addField("glpi_entities", 'send_contracts_alert_before_delay', "integer",
                        ['value'     => -2,
                              'after'     => 'use_contracts_alert',
                              'update'    => '0', // No delay for root entity
                              'condition' => 'WHERE `id`=0']);
   $migration->addField("glpi_entities", 'send_infocoms_alert_before_delay', "integer",
                        ['value'     => -2,
                              'after'     => 'use_infocoms_alert',
                              'update'    => '0', // No delay for root entity
                              'condition' => 'WHERE `id`=0']);
   $migration->addField("glpi_entities", 'send_licenses_alert_before_delay', "integer",
                        ['value'     => -2,
                              'after'     => 'use_licenses_alert',
                              'update'    => '0', // No delay for root entity
                              'condition' => 'WHERE `id`=0']);

   $migration->addField("glpi_configs", "notification_to_myself", "bool", ['value' => 1]);
   $migration->addField("glpi_configs", 'duedateok_color', "string", ['value' => '#06ff00']);
   $migration->addField("glpi_configs", 'duedatewarning_color', "string",
                        ['value' => '#ffb800']);
   $migration->addField("glpi_configs", 'duedatecritical_color', "string",
                        ['value' => '#ff0000']);
   $migration->addField("glpi_configs", 'duedatewarning_less', "integer", ['value' => 20]);
   $migration->addField("glpi_configs", 'duedatecritical_less', "integer", ['value' => 5]);
   $migration->addField("glpi_configs", 'duedatewarning_unit', "string", ['value' => '%']);
   $migration->addField("glpi_configs", 'duedatecritical_unit', "string", ['value' => '%']);
   $migration->addField("glpi_configs", "realname_ssofield", "string");
   $migration->addField("glpi_configs", "firstname_ssofield", "string");
   $migration->addField("glpi_configs", "email1_ssofield", "string");
   $migration->addField("glpi_configs", "email2_ssofield", "string");
   $migration->addField("glpi_configs", "email3_ssofield", "string");
   $migration->addField("glpi_configs", "email4_ssofield", "string");
   $migration->addField("glpi_configs", "phone_ssofield", "string");
   $migration->addField("glpi_configs", "phone2_ssofield", "string");
   $migration->addField("glpi_configs", "mobile_ssofield", "string");
   $migration->addField("glpi_configs", "comment_ssofield", "string");
   $migration->addField("glpi_configs", "title_ssofield", "string");
   $migration->addField("glpi_configs", "category_ssofield", "string");
   $migration->addField("glpi_configs", "language_ssofield", "string");
   $migration->addField("glpi_configs", "entity_ssofield", "string");
   $migration->addField("glpi_configs", "registration_number_ssofield", "string");

   $migration->addField("glpi_users", "notification_to_myself", "tinyint(1) DEFAULT NULL");
   $migration->addField("glpi_users", 'duedateok_color', "string");
   $migration->addField("glpi_users", 'duedatewarning_color', "string");
   $migration->addField("glpi_users", 'duedatecritical_color', "string");
   $migration->addField("glpi_users", 'duedatewarning_less', "int(11) DEFAULT NULL");
   $migration->addField("glpi_users", 'duedatecritical_less', "int(11) DEFAULT NULL");
   $migration->addField("glpi_users", 'duedatewarning_unit', "string");
   $migration->addField("glpi_users", 'duedatecritical_unit', "string");

   $migration->addField("glpi_users", 'display_options', "text");

   $migration->addField("glpi_reservationitems", "is_deleted", "bool");
   $migration->addKey("glpi_reservationitems", "is_deleted");

   $migration->addField("glpi_documentcategories", 'documentcategories_id', "integer");
   $migration->addField("glpi_documentcategories", 'completename', "text");
   $migration->addField("glpi_documentcategories", 'level', "integer");
   $migration->addField("glpi_documentcategories", 'ancestors_cache', "longtext");
   $migration->addField("glpi_documentcategories", 'sons_cache', "longtext");
   $migration->migrationOneTable('glpi_documentcategories');
   $migration->addKey("glpi_documentcategories", ['documentcategories_id','name'], 'unicity');
   regenerateTreeCompleteName("glpi_documentcategories");

   $migration->addField("glpi_contacts", 'usertitles_id', "integer");
   $migration->addKey("glpi_contacts", 'usertitles_id');

   $migration->addField("glpi_contacts", 'address', "text");
   $migration->addField("glpi_contacts", 'postcode', "string");
   $migration->addField("glpi_contacts", 'town', "string");
   $migration->addField("glpi_contacts", 'state', "string");
   $migration->addField("glpi_contacts", 'country', "string");

   $migration->addField("glpi_configs", 'x509_ou_restrict', "string",
                        ['after' => 'x509_email_field']);
   $migration->addField("glpi_configs", 'x509_o_restrict', "string",
                        ['after' => 'x509_email_field']);
   $migration->addField("glpi_configs", 'x509_cn_restrict', "string",
                        ['after' => 'x509_email_field']);

   if (!$DB->tableExists('glpi_slalevelcriterias')) {
      $query = "CREATE TABLE `glpi_slalevelcriterias` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `slalevels_id` int(11) NOT NULL DEFAULT '0',
                  `criteria` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `condition` int(11) NOT NULL DEFAULT '0'
                              COMMENT 'see define.php PATTERN_* and REGEX_* constant',
                  `pattern` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `slalevels_id` (`slalevels_id`),
                  KEY `condition` (`condition`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_slalevelcriterias");
   }

   $migration->addField("glpi_slalevels", 'match',
                        "CHAR(10) DEFAULT NULL COMMENT 'see define.php *_MATCHING constant'");

   $query = "UPDATE `glpi_slalevelactions`
             SET `action_type` = 'append'
             WHERE `action_type` = 'assign'
                   AND `field` IN ('_users_id_requester',  '_groups_id_requester',
                                   '_users_id_assign',     '_groups_id_assign',
                                   '_suppliers_id_assign', '_users_id_observer',
                                   '_groups_id_observer');";
   $DB->queryOrDie($query, "0.84 update data for SLA actors add");

   // Clean observer as recipient of satisfaction survey
   $query = "DELETE FROM `glpi_notificationtargets`
             WHERE `glpi_notificationtargets`.`type` = '".Notification::USER_TYPE."'
                   AND `glpi_notificationtargets`.`items_id` = '".Notification::OBSERVER."'
                   AND `notifications_id` IN (SELECT `glpi_notifications`.`id`
                                              FROM `glpi_notifications`
                                              WHERE `glpi_notifications`.`itemtype` = 'Ticket'
                                                    AND `glpi_notifications`.`event` = 'satisfaction')";

   $DB->queryOrDie($query, "0.84 clean targets for satisfaction notification");

   // Clean user as recipient of item not unique
   $query = "DELETE FROM `glpi_notificationtargets`
             WHERE `glpi_notificationtargets`.`type` = '".Notification::USER_TYPE."'
                   AND `glpi_notificationtargets`.`items_id` = '".Notification::USER."'
                   AND `notifications_id` IN (SELECT `glpi_notifications`.`id`
                                              FROM `glpi_notifications`
                                              WHERE `glpi_notifications`.`itemtype` = 'FieldUnicity'
                                                    AND `glpi_notifications`.`event` = 'refuse')";

   $DB->queryOrDie($query, "0.84 clean targets for fieldunicity notification");

   if (!$DB->tableExists('glpi_blacklists')) {
      $query = "CREATE TABLE `glpi_blacklists` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `type` int(11) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `type` (`type`),
                  KEY `name` (`name`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_blacklists");

      $ADDTODISPLAYPREF['Blacklist'] = [12,11];

      $toinsert = [Blacklist::IP  => ['empty IP'  => '',
                                                'localhost' => '127.0.0.1',
                                                'zero IP'   => '0.0.0.0'],
                        Blacklist::MAC => ['empty MAC' => '']];
      foreach ($toinsert as $type => $datas) {
         if (count($datas)) {
            foreach ($datas as $name => $value) {
               $query = "INSERT INTO `glpi_blacklists`
                                (`type`,`name`,`value`)
                         VALUES ('$type','".addslashes($name)."','".addslashes($value)."')";
               $DB->queryOrDie($query, "0.84 insert datas to glpi_blacklists");
            }
         }
      }
   }

   $query  = "SELECT `id`
              FROM `glpi_rulerightparameters`
              WHERE `name` = '(LDAP) MemberOf'";
   $result = $DB->query($query);
   if (!$DB->numrows($result)) {
      $query = "INSERT INTO `glpi_rulerightparameters`
                VALUES (NULL, '(LDAP) MemberOf', 'memberof', '')";
      $DB->queryOrDie($query, "0.84 insert (LDAP) MemberOf in glpi_rulerightparameters");
   }

   if (!$DB->tableExists('glpi_ssovariables')) {
      $query = "CREATE TABLE `glpi_ssovariables` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_ssovariables");

      $query = "INSERT INTO `glpi_ssovariables`
                       (`id`, `name`, `comment`)
                VALUES (1, 'HTTP_AUTH_USER', ''),
                       (2, 'REMOTE_USER', ''),
                       (3, 'PHP_AUTH_USER', ''),
                       (4, 'USERNAME', ''),
                       (5, 'REDIRECT_REMOTE_USER', ''),
                       (6, 'HTTP_REMOTE_USER', '')";
      $DB->queryOrDie($query, "0.84 add values from  glpi_ssovariables");
   }

   if ($migration->addField('glpi_configs', 'ssovariables_id', 'integer')) {
      $migration->migrationOneTable('glpi_configs');
      //Get configuration
      $query = "SELECT `existing_auth_server_field`
                FROM `glpi_configs`";
      $result = $DB->query($query);

      $existing_auth_server_field = $DB->result($result, 0, "existing_auth_server_field");
      if ($existing_auth_server_field) {
         //Get dropdown value for existing_auth_server_field
         $query = "SELECT `id`
                   FROM `glpi_ssovariables`
                   WHERE `name` = '$existing_auth_server_field'";
         $result = $DB->query($query);
         //Update config
         if ($DB->numrows($result) > 0) {
            $query = "UPDATE `glpi_configs`
                      SET `ssovariables_id` = '".$DB->result($result, 0, "id")."'";
            $DB->queryOrDie($query, "0.84 update glpi_configs");
         }
         //Drop old field
      }
   }

   $migration->dropField('glpi_configs', 'existing_auth_server_field');
   //Remove field to specify an ldap server for SSO users : don't need it anymore
   $migration->dropField('glpi_configs', 'authldaps_id_extra');

   // Clean uneeded logs
   $cleancondition                = [];
   $cleancondition['reminder_kb'] = "`itemtype` IN ('Entity', 'User', 'Profile', 'Group')
                                       AND `itemtype_link` IN ('Reminder', 'Knowbase')";

   foreach ($cleancondition as $name => $condition) {
      $query = "DELETE
                FROM `glpi_logs`
                WHERE $condition";
      $DB->queryOrDie($query, "0.84 clean logs for $name");
   }

   //Remove OCS tables from GLPI's core
   $migration->renameTable('glpi_ocsadmininfoslinks', 'ocs_glpi_ocsadmininfoslinks');
   $migration->renameTable('glpi_ocslinks', 'ocs_glpi_ocslinks');
   $migration->renameTable('glpi_ocsservers', 'ocs_glpi_ocsservers');
   $migration->renameTable('glpi_registrykeys', 'ocs_glpi_registrykeys');

   // Migrate RuleOcs to RuleImportEntity
   $query = "UPDATE `glpi_rules`
             SET `sub_type` = 'RuleImportEntity'
             WHERE `sub_type` = 'RuleOcs'";
   $DB->queryOrDie($query, "0.84 update datas for old OCS rules");

   $migration->copyTable('glpi_rules', 'ocs_glpi_rules');
   $migration->copyTable('glpi_ruleactions', 'ocs_glpi_ruleactions');
   $migration->copyTable('glpi_rulecriterias', 'ocs_glpi_rulecriterias');

   // Delete OCS rules
   $DB->query("SET SESSION group_concat_max_len = 4194304;");
   $query = "SELECT GROUP_CONCAT(`id`)
             FROM `glpi_rules`
             WHERE `sub_type` = 'RuleImportEntity'
             GROUP BY `sub_type`";
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         // Get rule string
         $rules = $DB->result($result, 0, 0);
         $query = "DELETE
                   FROM `glpi_ruleactions`
                   WHERE `rules_id` IN ($rules)";

         $DB->queryOrDie($query, "0.84 clean RuleImportEntity datas");

         $query = "DELETE
                   FROM `glpi_rulecriterias`
                   WHERE `rules_id` IN ($rules)";
         $DB->queryOrDie($query, "0.84 clean RuleImportEntity datas");

         $query = "DELETE
                   FROM `glpi_rules`
                   WHERE `id` IN ($rules)";
         $DB->queryOrDie($query, "0.84 clean RuleImportEntity datas");
      }
   }

   // copy table to keep value of fields deleted after
   $migration->copyTable('glpi_profiles', 'ocs_glpi_profiles');

   $migration->dropField('glpi_profiles', 'ocsng');
   $migration->dropField('glpi_profiles', 'sync_ocsng');
   $migration->dropField('glpi_profiles', 'view_ocsng');
   $migration->dropField('glpi_profiles', 'clean_ocsng');

   $migration->changeField('glpi_profiles', 'rule_ocs', 'rule_import', 'char');

   $migration->changeField('glpi_rulecacheprinters', 'ignore_ocs_import', 'ignore_import', 'char');
   $migration->changeField('glpi_rulecachesoftwares', 'ignore_ocs_import', 'ignore_import', 'char');

   $migration->dropField('glpi_configs', 'use_ocs_mode');

   // clean crontask
   $migration->copyTable('glpi_crontasks', 'ocs_glpi_crontasks');
   $query = "DELETE
             FROM `glpi_crontasks`
             WHERE `itemtype` = 'OcsServer'";
   $DB->queryOrDie($query, "0.84 delete OcsServer in crontasks");

   // clean displaypreferences
   $migration->copyTable('glpi_displaypreferences', 'ocs_glpi_displaypreferences');
   $query = "DELETE
             FROM `glpi_displaypreferences`
             WHERE `itemtype` = 'OcsServer'";
   $DB->queryOrDie($query, "0.84 delete OcsServer in displaypreferences");

   // Give history entries to plugin
   $query = "UPDATE `glpi_logs`
             SET `linked_action` = `linked_action`+1000,
                 `itemtype_link` = 'PluginOcsinventoryngOcslink'
             WHERE `linked_action` IN (8,9,10,11)";
   $DB->queryOrDie($query, "0.84 update OCS links in history");

   $migration->displayWarning("You can delete ocs_* tables if you use OCS mode ONLY AFTER ocsinventoryng plugin installation.",
                              true);
   $migration->displayWarning("You can delete ocs_* tables if you do not use OCS synchronisation.",
                              true);

   $migration->addField('glpi_authldaps', 'pagesize', 'integer');
   $migration->addField('glpi_authldaps', 'ldap_maxlimit', 'integer');
   $migration->addField('glpi_authldaps', 'can_support_pagesize', 'bool');

   // Add delete ticket notification
   if (countElementsInTable("glpi_notifications",
                            ['itemtype' => 'Ticket', 'event' => 'delete']) == 0) {
      // Get first template for tickets :
      $notid = 0;
      $query = "SELECT MIN(id) AS id
                FROM `glpi_notificationtemplates`
                WHERE `itemtype` = 'Ticket'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) == 1) {
            $notid = $DB->result($result, 0, 0);
         }
      }
      if ($notid > 0) {
         $notifications = ['delete' => [Notification::GLOBAL_ADMINISTRATOR]];

         $notif_names   = ['delete' => 'Delete Ticket'];

         foreach ($notifications as $type => $targets) {
            $query = "INSERT INTO `glpi_notifications`
                              (`name`, `entities_id`, `itemtype`, `event`, `mode`,
                               `notificationtemplates_id`, `comment`, `is_recursive`, `is_active`,
                               `date_mod`)
                       VALUES ('".$notif_names[$type]."', 0, 'Ticket', '$type', 'mail',
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

   // Add multiple suppliers for itil objects
   if (!$DB->tableExists('glpi_problems_suppliers')) {
      $query = "CREATE TABLE `glpi_problems_suppliers` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  `suppliers_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`problems_id`,`type`,`suppliers_id`),
                  KEY `group` (`suppliers_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 add table glpi_problems_suppliers");

      $migration->migrationOneTable('glpi_problems_suppliers');
      foreach ($DB->request('glpi_problems', "`suppliers_id_assign` > 0") as $data) {
         $query = "INSERT INTO `glpi_problems_suppliers`
                          (`suppliers_id`, `type`, `problems_id`)
                   VALUES ('".$data['suppliers_id_assign']."', '".CommonITILActor::ASSIGN."',
                           '".$data['id']."')";
         $DB->query($query);
      }
      $migration->dropField('glpi_problems', 'suppliers_id_assign');
   }

   if (!$DB->tableExists('glpi_suppliers_tickets')) {
      $query = "CREATE TABLE `glpi_suppliers_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `suppliers_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`tickets_id`,`type`,`suppliers_id`),
                  KEY `group` (`suppliers_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 add table glpi_suppliers_tickets");

      $migration->migrationOneTable('glpi_suppliers_tickets');
      foreach ($DB->request('glpi_tickets', "`suppliers_id_assign` > 0") as $data) {
         $query = "INSERT INTO `glpi_suppliers_tickets`
                          (`suppliers_id`, `type`, `tickets_id`)
                   VALUES ('".$data['suppliers_id_assign']."', '".CommonITILActor::ASSIGN."',
                           '".$data['id']."')";
         $DB->query($query);
      }
      $migration->dropField('glpi_tickets', 'suppliers_id_assign');
   }

   $migration->addField('glpi_tickets', 'locations_id', 'integer');
   $migration->addKey('glpi_tickets', 'locations_id');

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'RuleTicket'));

   $changes                            = [];
   $changes['RuleTicket']              = ['suppliers_id_assign' => '_suppliers_id_assign'];
   $changes['RuleDictionnarySoftware'] = ['_ignore_ocs_import' => '_ignore_import'];
   $changes['RuleImportEntity']        = ['_ignore_ocs_import' => '_ignore_import'];
   $changes['RuleDictionnaryPrinter']  = ['_ignore_ocs_import' => '_ignore_import'];

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

               $DB->queryOrDie($query, "0.84 update datas for rules actions");
            }
            // Update criteria
            foreach ($tab as $old => $new) {
               $query = "UPDATE `glpi_rulecriterias`
                         SET `criteria` = '$new'
                         WHERE `criteria` = '$old'
                               AND `rules_id` IN ($rules)";
               $DB->queryOrDie($query, "0.84 update datas for rules criteria");
            }
         }
      }
   }

   // change ruleaction for manufacturer (id to name)
   $query = "SELECT  `glpi_ruleactions` .`id` AS id,
                     `sub_type`,
                     `glpi_manufacturers`.`name` AS newvalue
             FROM `glpi_rules`
             INNER JOIN `glpi_ruleactions`
                  ON (`glpi_rules`.`id` = `glpi_ruleactions`.`rules_id`
                      AND `field` = 'Manufacturer')
             LEFT JOIN `glpi_manufacturers`
                  ON `glpi_manufacturers`.`id` = `glpi_ruleactions`.`value`
             WHERE `sub_type` = 'RuleDictionnarySoftware'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            // Update manufacturer
            $query = "UPDATE `glpi_ruleactions`
                      SET `value` = '".$data['newvalue']."'
                      WHERE `id` = ". $data['id'];

               $DB->queryOrDie($query, "0.84 update value of manufacturer for rules actions");
         }
      }
   }

   // Move ticketrecurrent values to correct ones
   $migration->changeField('glpi_ticketrecurrents', 'periodicity', 'periodicity', 'string');
   $migration->addField('glpi_ticketrecurrents', 'calendars_id', 'integer');
   $migration->addField('glpi_ticketrecurrents', 'end_date', 'datetime');

   $migration->migrationOneTable('glpi_ticketrecurrents');
   foreach ($DB->request('glpi_ticketrecurrents', "`periodicity` >= ".MONTH_TIMESTAMP) as $data) {
      $periodicity = $data['periodicity'];
      if (is_numeric($periodicity)) {
         if ($periodicity >= 365*DAY_TIMESTAMP) {
            $periodicity = round($periodicity/(365*DAY_TIMESTAMP)).'YEAR';
         } else {
            $periodicity = round($periodicity/(MONTH_TIMESTAMP)).'MONTH';
         }
         $query = "UPDATE `glpi_ticketrecurrents`
                   SET `periodicity` = '$periodicity'
                   WHERE `id` = '".$data['id']."'";
         $DB->query($query);
      }
   }

   $query = "UPDATE `glpi_notifications`
             SET   `itemtype` = 'CartridgeItem'
             WHERE `itemtype` = 'Cartridge'";
   $DB->queryOrDie($query, "0.83 update glpi_notifications for Cartridge");

   $query = "UPDATE `glpi_notificationtemplates`
             SET   `itemtype` = 'CartridgeItem'
             WHERE `itemtype` = 'Cartridge'";
   $DB->queryOrDie($query, "0.83 update glpi_notificationtemplates for Cartridge");

   $query = "UPDATE `glpi_notifications`
             SET   `itemtype` = 'ConsumableItem'
             WHERE `itemtype` = 'Consumable'";
   $DB->queryOrDie($query, "0.83 update glpi_notifications for Consumable");

   $query = "UPDATE `glpi_notificationtemplates`
             SET   `itemtype` = 'ConsumableItem'
             WHERE `itemtype` = 'Consumable'";
   $DB->queryOrDie($query, "0.83 update glpi_notificationtemplates for Consumable");

   $migration->createRule(['sub_type'      => 'RuleTicket',
                                'entities_id'   => 0,
                                'is_recursive'  => 1,
                                'is_active'     => 0,
                                'match'         => 'AND',
                                'name'          => 'Ticket location from item'],
                          [['criteria'   => 'locations_id',
                                      'condition'  => Rule::PATTERN_DOES_NOT_EXISTS,
                                      'pattern'    => 1],
                                ['criteria'   => 'items_locations',
                                      'condition'  => Rule::PATTERN_EXISTS,
                                      'pattern'    => 1]],
                          [['field'        => 'locations_id',
                                      'action_type'  => 'fromitem',
                                      'value'        => 1]]);

   $migration->createRule(['sub_type'      => 'RuleTicket',
                                'entities_id'   => 0,
                                'is_recursive'  => 1,
                                'is_active'     => 0,
                                'match'         => 'AND',
                                'name'          => 'Ticket location from user'],
                          [['criteria'   => 'locations_id',
                                      'condition'  => Rule::PATTERN_DOES_NOT_EXISTS,
                                      'pattern'    => 1],
                                ['criteria'   => 'users_locations',
                                      'condition'  => Rule::PATTERN_EXISTS,
                                      'pattern'    => 1]],
                          [['field'        => 'locations_id',
                                      'action_type'  => 'fromuser',
                                      'value'        => 1]]);

   // Change begin_date id for budget
   $query = ("UPDATE `glpi_displaypreferences`
              SET `num` = '5'
              WHERE `itemtype` = 'Budget'
                    AND `num` = '2'");
   $DB->query($query);

   migrateComputerDevice('DeviceProcessor', 'frequency', 'integer', ['serial' => 'string']);

   migrateComputerDevice('DeviceMemory', 'size', 'integer', ['serial' => 'string']);

   migrateComputerDevice('DeviceHardDrive', 'capacity', 'integer', ['serial' => 'string']);

   migrateComputerDevice('DeviceGraphicCard', 'memory', 'integer');
   migrateComputerDevice('DeviceNetworkCard', 'mac', 'string');
   migrateComputerDevice('DeviceSoundCard');
   migrateComputerDevice('DeviceMotherBoard');
   migrateComputerDevice('DeviceDrive');
   migrateComputerDevice('DeviceControl');
   migrateComputerDevice('DevicePci');
   migrateComputerDevice('DeviceCase');
   migrateComputerDevice('DevicePowerSupply');

   $migration->migrationOneTable('glpi_computers_softwareversions');

   //Rename fields in glpi_computers_softwareversions with inaproprious signification
   $migration->changeField('glpi_computers_softwareversions', 'is_deleted', 'is_deleted_computer',
                           'bool');
   $migration->changeField('glpi_computers_softwareversions', 'is_template', 'is_template_computer',
                           'bool');
   $migration->migrationOneTable('glpi_computers_softwareversions');

   $types = ['glpi_computers_items', 'glpi_computervirtualmachines',
                  'glpi_computers_softwareversions', 'glpi_computerdisks', 'glpi_networkports',
                  'glpi_computers_softwarelicenses', 'glpi_networknames', 'glpi_ipaddresses'];

   $devices = [
      'Item_DeviceMotherboard',
      'Item_DeviceProcessor',
      'Item_DeviceMemory',
      'Item_DeviceHardDrive',
      'Item_DeviceNetworkCard',
      'Item_DeviceDrive',
      'Item_DeviceControl',
      'Item_DeviceGraphicCard',
      'Item_DeviceSoundCard',
      'Item_DevicePci',
      'Item_DeviceCase',
      'Item_DevicePowerSupply'
   ];

   foreach ($devices as $type) {
      $types[] = getTableForItemType($type);
   }
   //Add is_deleted for relations
   foreach ($types as $table) {
      if ($DB->tableExists($table)) {
         if ($migration->addField($table, 'is_deleted', 'bool', ['value' => 0])) {
            $migration->migrationOneTable($table);
            $migration->addKey($table, 'is_deleted');
         }
      }
   }

   ///For computers, rename is is_ocs_import to is_dynamic
   $migration->changeField('glpi_computers', 'is_ocs_import', 'is_dynamic', 'bool');
   $migration->migrationOneTable('glpi_computers');
   $migration->dropKey("glpi_computers", 'is_ocs_import');
   $migration->addKey("glpi_computers", 'is_dynamic');

   //Add field is_dynamic
   $types = array_merge($types, ['glpi_printers', 'glpi_phones', 'glpi_peripherals',
                                      'glpi_networkequipments', 'glpi_networkports',
                                      'glpi_monitors', 'glpi_networknames', 'glpi_ipaddresses']);
   foreach ($types as $table) {
      if ($migration->addField($table, 'is_dynamic', 'bool')) {
         $migration->migrationOneTable($table);
         $migration->addKey($table, 'is_dynamic');
      }
   }

   $ADDTODISPLAYPREF['ReservationItem'] = [5];

   // split validation rights in both

   $migration->changeField('glpi_profiles', 'validate_ticket', 'validate_request', 'char');
   $migration->changeField('glpi_profiles', 'create_validation', 'create_request_validation', 'char');
   $migration->migrationOneTable('glpi_profiles');

   $migration->addField('glpi_profiles', 'validate_incident',
                        'char', ['update' => 'validate_request']);
   $migration->addField('glpi_profiles', 'create_incident_validation',
                        'char', ['update' => 'create_request_validation']);

   // add rights to delete all validation
   $migration->addField('glpi_profiles', 'delete_validations',
                        'char', ['update' => 'delete_ticket']);

   // add rights to manage public rssfeed
   $migration->addField('glpi_profiles', 'rssfeed_public',
                        'char', ['update' => 'reminder_public',
                                      'after'  => 'reminder_public']);

   // add ticket templates
   $migration->addField('glpi_profiles', 'tickettemplates_id', 'integer');

   // Drop not needed fields
   $migration->dropField('glpi_tickettemplatepredefinedfields', 'entities_id');
   $migration->dropField('glpi_tickettemplatepredefinedfields', 'is_recursive');
   $migration->dropField('glpi_tickettemplatemandatoryfields', 'entities_id');
   $migration->dropField('glpi_tickettemplatemandatoryfields', 'is_recursive');
   $migration->dropField('glpi_tickettemplatehiddenfields', 'entities_id');
   $migration->dropField('glpi_tickettemplatehiddenfields', 'is_recursive');

   // Clean unlinked calendar segments and holidays
   $query = "DELETE
             FROM `glpi_calendars_holidays`
             WHERE `glpi_calendars_holidays`.`calendars_id`
                     NOT IN (SELECT `glpi_calendars`.`id`
                             FROM `glpi_calendars`)";
   $DB->queryOrDie($query, "0.84 clean glpi_calendars_holidays");

   $query = "DELETE
             FROM `glpi_calendarsegments`
             WHERE `glpi_calendarsegments`.`calendars_id`
                     NOT IN (SELECT `glpi_calendars`.`id`
                             FROM `glpi_calendars`)";
   $DB->queryOrDie($query, "0.84 clean glpi_calendarsegments");

   // Add keys for serial, otherserial and uuid
   $newindexes = ['serial'      => ['glpi_computers', 'glpi_items_deviceharddrives',
                                              'glpi_items_devicememories',
                                              'glpi_items_deviceprocessors', 'glpi_monitors',
                                              'glpi_networkequipments', 'glpi_peripherals',
                                              'glpi_phones', 'glpi_printers'],
                       'otherserial' => ['glpi_computers', 'glpi_monitors',
                                              'glpi_networkequipments', 'glpi_peripherals',
                                              'glpi_phones', 'glpi_printers'],
                       'uuid'        => ['glpi_computers', 'glpi_computervirtualmachines']];
   foreach ($newindexes as $field => $tables) {
      foreach ($tables as $table) {
         $migration->addKey($table, $field);
      }
   }

   // Clean unlinked ticket_problem
   $query = "DELETE
             FROM `glpi_problems_tickets`
             WHERE `glpi_problems_tickets`.`tickets_id`
                     NOT IN (SELECT `glpi_tickets`.`id`
                             FROM `glpi_tickets`)";
   $DB->queryOrDie($query, "0.84 clean glpi_problems_tickets");

   $query = "DELETE
             FROM `glpi_problems_tickets`
             WHERE `glpi_problems_tickets`.`problems_id`
                     NOT IN (SELECT `glpi_problems`.`id`
                             FROM `glpi_problems`)";
   $DB->queryOrDie($query, "0.84 clean glpi_problems_tickets");

   // Clean unlinked softwarelicense_computer
   $query = "DELETE
             FROM `glpi_computers_softwarelicenses`
             WHERE `glpi_computers_softwarelicenses`.`softwarelicenses_id`
                     NOT IN (SELECT `glpi_softwarelicenses`.`id`
                             FROM `glpi_softwarelicenses`)";
   $DB->queryOrDie($query, "0.84 clean glpi_computers_softwarelicenses");

   $query = "DELETE
             FROM `glpi_computers_softwarelicenses`
             WHERE `glpi_computers_softwarelicenses`.`computers_id`
                     NOT IN (SELECT `glpi_computers`.`id`
                             FROM `glpi_computers`)";
   $DB->queryOrDie($query, "0.84 clean glpi_computers_softwarelicenses");

   // Clean unlinked items_problems
   $query = "DELETE
             FROM `glpi_items_problems`
             WHERE `glpi_items_problems`.`problems_id`
                     NOT IN (SELECT `glpi_problems`.`id`
                             FROM `glpi_problems`)";
   $DB->queryOrDie($query, "0.84 clean glpi_items_problems");

   $toclean = ['Computer', 'Monitor', 'NetworkEquipment',
                    'Peripheral', 'Phone', 'Printer', 'Software'];
   foreach ($toclean as $type) {
      $query = "DELETE
               FROM `glpi_items_problems`
               WHERE `glpi_items_problems`.`itemtype` = '$type'
                     AND `glpi_items_problems`.`items_id`
                        NOT IN (SELECT `".getTableForItemType($type)."`.`id`
                              FROM `".getTableForItemType($type)."`)";
      $DB->queryOrDie($query, "0.84 clean glpi_items_problems");
   }
   // ************ Keep it at the end **************
   //TRANS: %s is the table or item to migrate
   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

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

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}


/**
 * @param $origin
 * @param $id
 * @param $itemtype
 * @param $items_id
 * @param $error
**/
function logNetworkPortError($origin, $id, $itemtype, $items_id, $error) {
   global $migration;

   $migration->log($origin." - NetworkPort[".$id."]=".$itemtype."[".$items_id ."]: ".$error,
                   true);
}


/**
 * @param $itemtype
 * @param $items_id
 * @param $main_items_id
 * @param $main_itemtype
 * @param $entities_id
 * @param $IP
**/
function createNetworkNameFromItem($itemtype, $items_id, $main_items_id, $main_itemtype,
                                   $entities_id, $IP) {
   global $migration;

   // Using gethostbyaddr() allows us to define its reald internet name according to its IP.
   //   But each gethostbyaddr() may reach several milliseconds. With very large number of
   //   Networkports or NetworkeEquipment, the migration may take several minutes or hours ...
   //$computerName = gethostbyaddr($IP);
   $computerName = $IP;
   if ($computerName != $IP) {
      $position = strpos($computerName, ".");
      $name     = substr($computerName, 0, $position);
      $domain   = substr($computerName, $position + 1);
      $query    = "SELECT `id`
                   FROM `glpi_fqdns`
                   WHERE `fqdn` = '$domain'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         $data     = $DB->fetch_assoc($result);
         $domainID = $data['id'];
      }

   } else {
      $name     = "migration-".str_replace('.', '-', $computerName);
      $domainID = 0;
   }

   $IPaddress = new IPAddress();
   if ($IPaddress->setAddressFromString($IP)) {

      $input = ['name'         => $name,
                     'fqdns_id'     => $domainID,
                     'entities_id'  => $entities_id,
                     'items_id'     => $items_id,
                     'itemtype'     => $itemtype];

      $networknames_id = $migration->insertInTable('glpi_networknames', $input);

      $input = $IPaddress->setArrayFromAddress(['entities_id'   => $entities_id,
                                                     'itemtype'      => 'NetworkName',
                                                     'items_id'      => $networknames_id],
                                               "version", "name", "binary");

      $migration->insertInTable($IPaddress->getTable(), $input);

   } else { // Don't add the NetworkName if the address is not valid
      addNetworkPortMigrationError($items_id, 'invalid_address');
      logNetworkPortError('invalid IP address', $items_id, $main_itemtype, $main_items_id, "$IP");
   }

   unset($IPaddress);

}


/**
 * @param $port
 * @param $fields
 * @param $setNetworkCard
**/
function updateNetworkPortInstantiation($port, $fields, $setNetworkCard) {
   global $DB, $migration;

   $query = "SELECT `origin_glpi_networkports`.`name`,
                    `origin_glpi_networkports`.`id`,
                    `origin_glpi_networkports`.`mac`, ";

   $addleftjoin         = '';
   $manage_netinterface = false;
   if ($port instanceof NetworkPortEthernet) {
      $addleftjoin = "LEFT JOIN `glpi_networkinterfaces`
                        ON (`origin_glpi_networkports`.`networkinterfaces_id`
                              = `glpi_networkinterfaces` .`id`)";
      $query .= "`glpi_networkinterfaces`.`name` AS networkinterface, ";
      $manage_netinterface = true;
   }

   foreach ($fields as $SQL_field => $field) {
      $query .= "$SQL_field AS $field, ";
   }
   $query .= "`origin_glpi_networkports`.`itemtype`, `origin_glpi_networkports`.`items_id`
              FROM `origin_glpi_networkports`
              $addleftjoin
              WHERE `origin_glpi_networkports`.`id`
                                     IN (SELECT `id`
                                         FROM `glpi_networkports`
                                         WHERE `instantiation_type` = '".$port->getType()."')";
   foreach ($DB->request($query) as $portInformation) {
      $input = ['networkports_id' => $portInformation['id']];
      if ($manage_netinterface) {
         if (preg_match('/TX/i', $portInformation['networkinterface'])) {
            $input['type'] = 'T';
         }
         if (preg_match('/SX/i', $portInformation['networkinterface'])) {
            $input['type'] = 'SX';
         }
         if (preg_match('/LX/i', $portInformation['networkinterface'])) {
            $input['type'] = 'LX';
         }
         unset($portInformation['networkinterface']);
      }

      foreach ($fields as $field) {
         $input[$field] = $portInformation[$field];
      }

      if (($setNetworkCard) && ($portInformation['itemtype'] == 'Computer')) {
         $query = "SELECT link.`id` AS link_id,
                          device.`designation` AS name
                   FROM `glpi_devicenetworkcards` as device,
                        `glpi_computers_devicenetworkcards` as link
                   WHERE link.`computers_id` = ".$portInformation['items_id']."
                         AND device.`id` = link.`devicenetworkcards_id`
                         AND link.`specificity` = '".$portInformation['mac']."'";
         $result = $DB->query($query);

         if ($DB->numrows($result) > 0) {
            $set_first = ($DB->numrows($result) == 1);
            while ($link = $DB->fetch_assoc($result)) {
               if ($set_first || ($link['name'] == $portInformation['name'])) {
                  $input['items_devicenetworkcards_id'] = $link['link_id'];
                  break;
               }
            }
         }
      }
      $migration->insertInTable($port->getTable(), $input);
   }
}


/**
 * @param $networkports_id
 * @param $motive
**/
function addNetworkPortMigrationError($networkports_id, $motive) {
   global $DB;

   if (countElementsInTable("glpi_networkportmigrations", "`id` = '$networkports_id'") == 0) {
      $query = "INSERT INTO `glpi_networkportmigrations`
                       (SELECT *" . str_repeat(', 0', count(NetworkPortMigration::getMotives())) ."
                        FROM `origin_glpi_networkports`
                        WHERE `id` = '$networkports_id')";
      $DB->queryOrDie($query, "0.84 error on copy of network port during migration");
   }

   $query = "UPDATE `glpi_networkportmigrations`
             SET `$motive` = '1'
             WHERE `id`='$networkports_id'";
   $DB->queryOrDie($query, "0.84 append of motive to migration of network port error");

}


/**
 * Update all Network Organisation
 *
 * @param $ADDTODISPLAYPREF
**/
function updateNetworkFramework(&$ADDTODISPLAYPREF) {
   global $DB, $migration;

   $ADDTODISPLAYPREF['FQDN']                 = [11];
   $ADDTODISPLAYPREF['WifiNetwork']          = [10];
   $ADDTODISPLAYPREF['NetworkPortMigration'] = [];
   $ADDTODISPLAYPREF['IPNetwork']            = [14, 10, 11, 12, 13];
   $ADDTODISPLAYPREF['NetworkName']          = [12, 13];

   $optionIndex = 10;
   foreach (NetworkPortMigration::getMotives() as $key => $name) {
      $ADDTODISPLAYPREF['NetworkPortMigration'][] = $optionIndex ++;
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'Network framework'));

   $originTables = [];
   foreach (['glpi_networkports', 'glpi_networkequipments'] as $table) {
      $originTables[$table] = 'origin_'.$table;
   }

   if (!$DB->tableExists('origin_glpi_networkequipments')) {
      // remove of mac field from glpi_networkequipments is done at the end of migration
      // framework process
      if (!$DB->fieldExists('glpi_networkequipments', 'mac')) {
         // Nothing to be done : migration of NetworkPort already OK !

         // But don't add display preference for NetworkPortMigration if none exists
         if (!$DB->tableExists('glpi_networkportmigrations')) {
            unset($ADDTODISPLAYPREF['NetworkPortMigration']);
         }

         $migration->displayWarning('Network Framework already migrated: nothing to be done !',
                                    false);

         return;
      }

      foreach ($originTables as $table => $originTable) {
         if (!$DB->tableExists($originTable) && $DB->tableExists($table)) {
            $migration->copyTable($table, $originTable);
            $migration->displayWarning("To be safe, we are working on $originTable. ".
                                       "It is a copy of $table", false);
         }
      }
   }

   // Remove all tables created by any previous migration
   $new_network_ports = ['glpi_fqdns', 'glpi_ipaddresses', 'glpi_ipaddresses_ipnetworks',
                              'glpi_ipnetworks', 'glpi_networkaliases', 'glpi_networknames',
                              'glpi_networkportaggregates', 'glpi_networkportdialups',
                              'glpi_networkportethernets', 'glpi_networkportlocals',
                              'glpi_networkportmigrations', 'glpi_networkportwifis',
                              'glpi_wifinetworks'];

   foreach ($new_network_ports as $table) {
      $migration->dropTable($table);
   }

   // Create the glpi_networkportmigrations that is a copy of origin_glpi_networkports
   $query = "CREATE TABLE `glpi_networkportmigrations` LIKE `origin_glpi_networkports`";
   $DB->queryOrDie($query, "0.84 create glpi_networkportmigrations");

   // And add the error motive fields
   foreach (NetworkPortMigration::getMotives() as $key => $name) {
      $migration->addField('glpi_networkportmigrations', $key, 'bool');
   }
   $migration->migrationOneTable('glpi_networkportmigrations');

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_fqdns'));

   // Adding FQDN table
   if (!$DB->tableExists('glpi_fqdns')) {
      $query = "CREATE TABLE `glpi_fqdns` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `fqdn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `name` (`name`),
                  KEY `fqdn` (`fqdn`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_fqdns");

      $fqdn = new FQDN();

      // Then, populate it from domains (beware that "domains" can be FQDNs and Windows workgroups)
      $query = "SELECT DISTINCT LOWER(`name`) AS name, `comment`
                FROM `glpi_domains`";
      foreach ($DB->request($query) as $domain) {
         $domainName = $domain['name'];
         // We ensure that domains have at least 1 dote to be sure it is not a Windows workgroup
         if ((strpos($domainName, '.') !== false) && (FQDN::checkFQDN($domainName))) {
            $migration->insertInTable($fqdn->getTable(),
                                      ['entities_id' => 0,
                                            'name'        => $domainName,
                                            'fqdn'        => $domainName,
                                            'comment'     => $domain['comment']]);
         }
      }
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_ipaddresses'));

   // Adding IPAddress table
   if (!$DB->tableExists('glpi_ipaddresses')) {
      $query = "CREATE TABLE `glpi_ipaddresses` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `version` tinyint unsigned DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `binary_0`  int unsigned NOT NULL DEFAULT '0',
                  `binary_1`  int unsigned NOT NULL DEFAULT '0',
                  `binary_2`  int unsigned NOT NULL DEFAULT '0',
                  `binary_3`  int unsigned NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `textual` (`name`),
                  KEY `binary` (`binary_0`, `binary_1`, `binary_2`, `binary_3`),
                  KEY `item` (`itemtype`, `items_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_ipaddresses");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'glpi_wifinetworks'));

   // Adding WifiNetwork table
   if (!$DB->tableExists('glpi_wifinetworks')) {
      $query = "CREATE TABLE `glpi_wifinetworks` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `entities_id` int(11) NOT NULL DEFAULT '0',
                 `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                 `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `essid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `mode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
                        COMMENT 'ad-hoc, access_point',
                 `comment` text COLLATE utf8_unicode_ci,
                 PRIMARY KEY (`id`),
                 KEY `entities_id` (`entities_id`),
                 KEY `essid` (`essid`),
                 KEY `name` (`name`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_wifinetworks");

   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), "glpi_ipnetworks"));

   // Adding IPNetwork table
   if (!$DB->tableExists('glpi_ipnetworks')) {
      $query = "CREATE TABLE `glpi_ipnetworks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `ipnetworks_id` int(11) NOT NULL DEFAULT '0',
                  `completename` text COLLATE utf8_unicode_ci,
                  `level` int(11) NOT NULL DEFAULT '0',
                  `ancestors_cache` longtext COLLATE utf8_unicode_ci,
                  `sons_cache` longtext COLLATE utf8_unicode_ci,
                  `addressable` tinyint(1) NOT NULL DEFAULT '0',
                  `version` tinyint unsigned DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `address` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `address_0`  int unsigned NOT NULL DEFAULT '0',
                  `address_1`  int unsigned NOT NULL DEFAULT '0',
                  `address_2`  int unsigned NOT NULL DEFAULT '0',
                  `address_3`  int unsigned NOT NULL DEFAULT '0',
                  `netmask` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `netmask_0`  int unsigned NOT NULL DEFAULT '0',
                  `netmask_1`  int unsigned NOT NULL DEFAULT '0',
                  `netmask_2`  int unsigned NOT NULL DEFAULT '0',
                  `netmask_3`  int unsigned NOT NULL DEFAULT '0',
                  `gateway` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `gateway_0`  int unsigned NOT NULL DEFAULT '0',
                  `gateway_1`  int unsigned NOT NULL DEFAULT '0',
                  `gateway_2`  int unsigned NOT NULL DEFAULT '0',
                  `gateway_3`  int unsigned NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `network_definition` (`entities_id`,`address`,`netmask`),
                  KEY `address` (`address_0`, `address_1`, `address_2`, `address_3`),
                  KEY `netmask` (`netmask_0`, `netmask_1`, `netmask_2`, `netmask_3`),
                  KEY `gateway` (`gateway_0`, `gateway_1`, `gateway_2`, `gateway_3`),
                  KEY `name` (`name`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_ipnetworks");

      // Retrieve all the networks from the current network ports and add them to the IPNetworks
      $query = "SELECT DISTINCTROW INET_NTOA(INET_ATON(`ip`)&INET_ATON(`netmask`)) AS address,
                     `netmask`, `gateway`, `entities_id`
                FROM `origin_glpi_networkports`
                ORDER BY `gateway` DESC";
      $address = new IPAddress();
      $netmask = new IPNetmask();
      $gateway = new IPAddress();
      $network = new IPNetwork();
      foreach ($DB->request($query) as $entry) {

         $address = $entry['address'];
         $netmask = $entry['netmask'];
         $gateway = $entry['gateway'];
         $entities_id = $entry['entities_id'];

         if ((empty($address)) || ($address == '0.0.0.0') || (empty($netmask))
             || ($netmask == '0.0.0.0') || ($netmask == '255.255.255.255')) {
            continue;
         }

         if ($gateway == '0.0.0.0') {
            $gateway = '';
         }

         $networkDefinition = "$address/$netmask";
         $networkName       = $networkDefinition . (empty($gateway) ? "" : " - ".$gateway);

         $input             = ['entities_id'   => $entities_id,
                                    'name'          => $networkName,
                                    'network'       => $networkDefinition,
                                    'gateway'       => $gateway,
                                    'ipnetworks_id' => 0,
                                    'addressable'   => 1,
                                    'completename'  => $networkName,
                                    'level'         => 1];

         $preparedInput = $network->prepareInput($input);

         if (is_array($preparedInput['input'])) {
            $input = $preparedInput['input'];
            if (isset($preparedInput['error'])) {
               $query = "SELECT id, items_id, itemtype
                         FROM origin_glpi_networkports
                         WHERE INET_NTOA(INET_ATON(`ip`)&INET_ATON(`netmask`)) = '$address'
                               AND `netmask` = '$netmask'
                               AND `gateway` = '$gateway'
                               AND `entities_id` = '$entities_id'";
               $result = $DB->query($query);
               foreach ($DB->request($query) as $data) {
                  addNetworkPortMigrationError($data['id'], 'invalid_gateway');
                  logNetworkPortError('network warning', $data['id'], $data['itemtype'],
                                      $data['items_id'], $preparedInput['error']);
               }
            }
            $migration->insertInTable($network->getTable(), $input);
         } else if (isset($preparedInput['error'])) {
            $query = "SELECT id, items_id, itemtype
                      FROM origin_glpi_networkports
                      WHERE INET_NTOA(INET_ATON(`ip`)&INET_ATON(`netmask`)) = '".$entry['address']."'
                            AND `netmask` = '$netmask'
                            AND `gateway` = '$gateway'
                            AND `entities_id` = '$entities_id'";
            $result = $DB->query($query);
            foreach ($DB->request($query) as $data) {
               addNetworkPortMigrationError($data['id'], 'invalid_network');
               logNetworkPortError('network error', $data['id'], $data['itemtype'],
                                   $data['items_id'], $preparedInput['error']);
            }
         }
      }
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), "glpi_ipnetworks_vlans"));

   // Adding IPNetwork table
   if (!$DB->tableExists('glpi_ipnetworks_vlans')) {
      $query = "CREATE TABLE `glpi_ipnetworks_vlans` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `ipnetworks_id` int(11) NOT NULL DEFAULT '0',
                  `vlans_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `link` (`ipnetworks_id`, `vlans_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->queryOrDie($query, "0.84 create glpi_ipnetworks_vlans");
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), "glpi_networknames"));

   // Adding NetworkName table
   if (!$DB->tableExists('glpi_networknames')) {
      $query = "CREATE TABLE `glpi_networknames` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `fqdns_id` int(11) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `FQDN` (`name`,`fqdns_id`),
                  KEY `name` (`name`),
                  KEY `item` (`itemtype`, `items_id`),
                  KEY `fqdns_id` (`fqdns_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networknames");

      // Retrieve all the networks from the current network ports and add them to the IPNetworks
      $query = "SELECT `ip`, `id`, `entities_id`, `itemtype`, `items_id`
                FROM `origin_glpi_networkports`
                WHERE `ip` <> ''";

      foreach ($DB->request($query) as $entry) {
         if (empty($entry["ip"])) {
            continue;
         }

         createNetworkNameFromItem('NetworkPort', $entry['id'], $entry['items_id'],
                                   $entry['itemtype'], $entry['entities_id'], $entry["ip"]);
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkaliases"));

   // Adding NetworkAlias table
   if (!$DB->tableExists('glpi_networkaliases')) {
      $query = "CREATE TABLE `glpi_networkaliases` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `networknames_id` int(11) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `fqdns_id` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `name` (`name`),
                  KEY `networknames_id` (`networknames_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkaliases");
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'), "glpi_ipaddresses_ipnetworks"));

   // Adding IPAddress_IPNetwork table
   if (!$DB->tableExists('glpi_ipaddresses_ipnetworks')) {
      $query = "CREATE TABLE `glpi_ipaddresses_ipnetworks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `ipaddresses_id` int(11) NOT NULL DEFAULT '0',
                  `ipnetworks_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`ipaddresses_id`,`ipnetworks_id`),
                  KEY `ipnetworks_id` (`ipnetworks_id`),
                  KEY `ipaddresses_id` (`ipaddresses_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->queryOrDie($query, "0.84 create glpi_ipaddresses_ipnetworks");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkinterfaces"));

   // Update NetworkPorts
   $migration->addField('glpi_networkports', 'instantiation_type', 'string',
                        ['after'  => 'name',
                              'update' => "'NetworkPortEthernet'"]);

   $migration->displayMessage(sprintf(__('Data migration - %s'), "glpi_networkports"));

   // Retrieve all the networks from the current network ports and add them to the IPNetwork
   $query = "SELECT *
             FROM `glpi_networkinterfaces`";

   foreach ($DB->request($query) as $entry) {
      $instantiation_type = "";
      switch ($entry['name']) {
         case 'Local' :
            $instantiation_type = "NetworkPortLocal";
            break;

         case 'Ethernet' :
            $instantiation_type = "NetworkPortEthernet";
            break;

         case 'Wifi' :
            $instantiation_type = "NetworkPortWifi";
            break;

         case 'Dialup' :
            $instantiation_type = "NetworkPortDialup";
            break;

         default:
            if (preg_match('/TX/i', $entry['name'])
                || preg_match('/SX/i', $entry['name'])
                || preg_match('/Ethernet/i', $entry['name'])) {
               $instantiation_type = "NetworkPortEthernet";
            }
            break;

      }
      /// In case of unknown Interface Type, we should have to set instantiation_type to ''
      /// Thus we should be able to convert it later to correct type (ethernet, wifi, loopback ...)
      if (!empty($instantiation_type)) {
         $query = "UPDATE `glpi_networkports`
                   SET `instantiation_type` = '$instantiation_type'
                   WHERE `id` IN (SELECT `id`
                                  FROM `origin_glpi_networkports`
                                  WHERE `networkinterfaces_id` = '".$entry['id']."')";
         $DB->queryOrDie($query, "0.84 update instantiation_type field of glpi_networkports");
         // Clear $instantiation_type for next check inside the loop
         unset($instantiation_type);
      }
   }

   foreach (['ip', 'gateway', 'netmask', 'netpoints_id', 'networkinterfaces_id',
                  'subnet'] as $field) {
      $migration->dropField('glpi_networkports', $field);
   }

   foreach (['ip', 'mac'] as $field) {
      $migration->dropField('glpi_networkequipments', $field);
   }

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'Index mac field and transform address mac to lower'));

   $query = "UPDATE `glpi_networkports`
             SET `mac` = LOWER(`mac`)";
   $DB->queryOrDie($query, "0.84 transforme MAC to lower case");

   $migration->addKey('glpi_networkports', 'mac');

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'Update migration of interfaces errors'));

   $query = "SELECT id
             FROM `glpi_networkports`
             WHERE `instantiation_type` = ''";

   foreach ($DB->request($query) as $networkPortID) {
      addNetworkPortMigrationError($networkPortID['id'], 'unknown_interface_type');
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportethernets"));

   // Adding NetworkPortEthernet table
   if (!$DB->tableExists('glpi_networkportethernets')) {
      $query = "CREATE TABLE `glpi_networkportethernets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT '0',
                  `netpoints_id` int(11) NOT NULL DEFAULT '0',
                  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'T, LX, SX',
                  `speed` int(11) NOT NULL DEFAULT '10' COMMENT 'Mbit/s: 10, 100, 1000, 10000',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `card` (`items_devicenetworkcards_id`),
                  KEY `netpoint` (`netpoints_id`),
                  KEY `type` (`type`),
                  KEY `speed` (`speed`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkportethernets");

      $port = new NetworkPortEthernet();
      updateNetworkPortInstantiation($port, ['`netpoints_id`' => 'netpoints_id'], true);
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportwifis"));

   // Adding NetworkPortWifi table
   if (!$DB->tableExists('glpi_networkportwifis')) {
      $query = "CREATE TABLE `glpi_networkportwifis` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT '0',
                  `wifinetworks_id` int(11) NOT NULL DEFAULT '0',
                  `networkportwifis_id` int(11) NOT NULL DEFAULT '0'
                                        COMMENT 'only useful in case of Managed node',
                  `version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL
                            COMMENT 'a, a/b, a/b/g, a/b/g/n, a/b/g/n/y',
                  `mode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL
                         COMMENT 'ad-hoc, managed, master, repeater, secondary, monitor, auto',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `card` (`items_devicenetworkcards_id`),
                  KEY `essid` (`wifinetworks_id`),
                  KEY `version` (`version`),
                  KEY `mode` (`mode`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkportwifis");

      $port = new NetworkPortWifi();
      updateNetworkPortInstantiation($port, [], true);
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportlocals"));

   // Adding NetworkPortLocal table
   if (!$DB->tableExists('glpi_networkportlocals')) {
      $query = "CREATE TABLE `glpi_networkportlocals` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkportlocals");

      $port = new NetworkPortLocal();
      updateNetworkPortInstantiation($port, [], false);
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportdialups"));

   // Adding NetworkPortDialup table
   if (!$DB->tableExists('glpi_networkportdialups')) {
      $query = "CREATE TABLE `glpi_networkportdialups` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkportdialups");

      $port = new NetworkPortDialup();
      updateNetworkPortInstantiation($port, [], true);
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportaggregates"));

   // Adding NetworkPortAggregate table
   if (!$DB->tableExists('glpi_networkportaggregates')) {
      $query = "CREATE TABLE `glpi_networkportaggregates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `networkports_id_list` TEXT DEFAULT NULL
                             COMMENT 'array of associated networkports_id',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkportaggregates");

      // Transform NetworkEquipment local MAC address as a networkport that aggregates all ports
      $query = "SELECT *
                FROM `origin_glpi_networkequipments`
                WHERE `mac` != ''
                      OR `ip` != ''";
      $port_input = ['itemtype'           => 'NetworkEquipment',
                          'logical_number'     => '0',
                          'name'               => 'management',
                          'instantiation_type' => 'NetworkPortAggregate'];
      foreach ($DB->request($query) as $equipment) {

         $networkequipments_id       = $equipment['id'];

         $query = "SELECT `id`, `ip`, `mac`
                   FROM `origin_glpi_networkports`
                   WHERE `itemtype` = 'NetworkEquipment'
                         AND `items_id` = '$networkequipments_id'
                         AND (`ip` = '".$equipment['ip']."'
                              OR `mac` = '".$equipment['mac']."')";

         $both = [];
         $mac  = [];
         $ip   = [];
         foreach ($DB->request($query) as $ports) {
            if ($ports['ip'] == $equipment['ip']) {
               if ($ports['mac'] == $equipment['mac']) {
                  $both[] = $ports['id'];
               } else {
                  $ip[] = $ports['id'];
               }
            } else {
               $mac[] = $ports['id'];
            }
         }

         if (count($both) != 1) { // Only add a NetworkPort if there is 0 or more than one element !
            $port_input['items_id']     = $networkequipments_id;
            $port_input['entities_id']  = $equipment['entities_id'];
            $port_input['is_recursive'] = $equipment['is_recursive'];
            $port_input['mac']          = strtolower ($equipment['mac']);

            $networkports_id = $migration->insertInTable('glpi_networkports', $port_input);

            $aggregate_input                         = [];
            $aggregate_input['networkports_id']      = $networkports_id;
            $aggregate_input['networkports_id_list'] = exportArrayToDB($both);

            $migration->insertInTable('glpi_networkportaggregates', $aggregate_input);

            createNetworkNameFromItem('NetworkPort', $networkports_id, $equipment['id'],
                                      'NetworkEquipment', $equipment['entities_id'],
                                      $equipment['ip']);

            foreach ($both as $aggregated_networkports_id) {
               $query = "DELETE
                         FROM `glpi_networknames`
                         WHERE `itemtype` = 'NetworkPort'
                               AND `items_id` = '$aggregated_networkports_id'";
               $DB->query($query);

               $query = "UPDATE `glpi_networkports`
                         SET `mac` = ''
                         WHERE `id` = '$aggregated_networkports_id'";
               $DB->query($query);
            }
         }
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportaliases"));

   // Adding NetworkPortAlias table
   if (!$DB->tableExists('glpi_networkportaliases')) {
      $query = "CREATE TABLE `glpi_networkportaliases` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `networkports_id_alias` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `networkports_id_alias` (`networkports_id_alias`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_networkportaliases");

      // New element, so, we don't need to create items
   }

   $migration->addField('glpi_networkports_vlans', 'tagged', 'bool', ['value' => '0']);
   $migration->addField('glpi_vlans', 'entities_id', 'integer', ['value' => '0',
                                                                      'after' => 'id']);
   $migration->addKey('glpi_vlans', 'entities_id');
   $migration->addField('glpi_vlans', 'is_recursive', 'bool', ['value' => '0',
                                                                    'after' => 'entities_id',
                                                                    'update' => '1']);
   $migration->addKey('glpi_vlans', 'tag');

   $migration->displayMessage(sprintf(__('Data migration - %s'),
                                      'Update connections between IPAddress and IPNetwork'));

   // Here, we are sure that there is only IPv4 addresses. So, the SQL requests are simplified
   $query = "SELECT `id`, `address_3`, `netmask_3`
             FROM `glpi_ipnetworks`";

   if ($network_result = $DB->query($query)) {
      unset($query);
      while ($ipnetwork_row = $DB->fetch_assoc($network_result)) {
         $ipnetworks_id = $ipnetwork_row['id'];
         $netmask       = floatval($ipnetwork_row['netmask_3']);
         $address       = floatval($ipnetwork_row['address_3']) & $netmask;

         $query = "SELECT `id`
                   FROM `glpi_ipaddresses`
                   WHERE (`glpi_ipaddresses`.`binary_3` & '$netmask') = $address
                         AND `glpi_ipaddresses`.`version` = '4'
                   GROUP BY `items_id`";

         if ($ipaddress_result = $DB->query($query)) {
            unset($query);
            while ($link = $DB->fetch_assoc($ipaddress_result)) {
               $query = "INSERT INTO `glpi_ipaddresses_ipnetworks`
                                (`ipaddresses_id`, `ipnetworks_id`)
                         VALUES ('".$link['id']."', '$ipnetworks_id')";
               $DB->query($query);
               unset($query);
            }
         }
      }
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'),
                                      'Drop table glpi_networkportmigrations if empty'));

   if (countElementsInTable("glpi_networkportmigrations") == 0) {
      $migration->dropTable("glpi_networkportmigrations");
      $migration->dropTable("glpi_networkportinterfaces");
      unset($ADDTODISPLAYPREF['NetworkPortMigration']);
   }

   // We migrate glpi_networkequipments: mac field presence is used to check if framework has
   // already been migrated
   $migration->migrationOneTable('glpi_networkequipments');

   foreach ($originTables as $table) {
      $migration->dropTable($table);
   }

}


/**
 * @param $deviceType
 * @param $new_specif               (default NULL)
 * @param $new_specif_type          (default NULL)
 * @param $other_specif      array
 */
function migrateComputerDevice($deviceType, $new_specif = null, $new_specif_type = null,
                               array $other_specif = []) {
   global $DB, $migration;

   $table        = getTableForItemType('Item_'.$deviceType);
   $device_table = getTableForItemType($deviceType);
   $migration->renameTable(getTableForItemType('Computer_'.$deviceType), $table);

   $migration->changeField($table, 'computers_id', 'items_id', 'integer', ['value' => 0]);
   $migration->addField($table, 'itemtype', 'string', ['after'  => 'items_id',
                                                            'update' => "'Computer'"]);

   if (!empty($new_specif) && !empty($new_specif_type)) {
      $migration->changeField($table, 'specificity', $new_specif, $new_specif_type);
      $migration->changeField($device_table, 'specif_default', $new_specif.'_default',
                              $new_specif_type);

      // Update the log ...
      $query = "UPDATE `glpi_logs`
                SET `itemtype_link` = 'Item_".$deviceType."#".$new_specif."'
                WHERE `itemtype_link` = '$deviceType'";
      $DB->queryOrDie($query, "0.84 adapt glpi_logs to new item_devices");
   }

   foreach ($other_specif as $field => $format) {
      $migration->addField($table, $field, $format);
   }
   $migration->migrationOneTable($table);
}

