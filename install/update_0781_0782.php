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
 * Update from 0.78.1 to 0.78.2
 *
 * @return bool for success (will die for most error)
 */
function update0781to0782($output = 'HTML') {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.78.2'));
   $migration->setVersion('0.78.2');

   $migration->displayMessage(__('Data migration')); // Updating schema

   /// Add document types
   $types = ['docx' => ['name' => 'Word XML',
                                  'icon' => 'doc-dist.png'],
                  'xlsx' => ['name' => 'Excel XML',
                                  'icon' => 'xls-dist.png'],
                  'pptx' => ['name' => 'PowerPoint XML',
                                  'icon' => 'ppt-dist.png']];

   foreach ($types as $ext => $data) {

      $query = "SELECT *
                FROM `glpi_documenttypes`
                WHERE `ext` = '$ext'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result) == 0) {
            $query = "INSERT INTO `glpi_documenttypes`
                             (`name`, `ext`, `icon`, `is_uploadable`, `date_mod`)
                      VALUES ('".$data['name']."', '$ext', '".$data['icon']."', '1', NOW())";
            $DB->queryOrDie($query, "0.78.2 add document type $ext");
         }
      }
   }

   // Drop nl_be langage
   $query = "UPDATE `glpi_configs`
             SET `language` = 'nl_NL'
             WHERE `language` = 'nl_BE';";
   $DB->queryOrDie($query, "0.78.2 drop nl_be langage");

   $query = "UPDATE `glpi_users`
             SET `language` = 'nl_NL'
             WHERE `language` = 'nl_BE';";
   $DB->queryOrDie($query, "0.78.2 drop nl_be langage");

   // CLean sl_SL
   $query = "UPDATE `glpi_configs`
             SET `language` = 'sl_SI'
             WHERE `language` = 'sl_SL';";
   $DB->queryOrDie($query, "0.78.2 clean sl_SL langage");

   $query = "UPDATE `glpi_users`
             SET `language` = 'sl_SI'
             WHERE `language` = 'sl_SL';";
   $DB->queryOrDie($query, "0.78.2 clean sl_SL langage");

   if (isIndex('glpi_computers_items', 'unicity')) {
      $query = "ALTER TABLE `glpi_computers_items` DROP INDEX `unicity`";
      $DB->queryOrDie($query, "0.78.2 drop unicity index for glpi_computers_items");

      $query = "ALTER TABLE `glpi_computers_items` ADD INDEX `item` ( `itemtype` , `items_id` ) ";
      $DB->queryOrDie($query, "0.78.2 add index for glpi_computers_items");
   }

   // For Rule::RULE_TRACKING_AUTO_ACTION
   $changes['RuleMailCollector'] = ['X-Priority' => 'x-priority'];

   $DB->query("SET SESSION group_concat_max_len = 9999999;");
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

               $DB->queryOrDie($query, "0.78.2 update datas for rules actions");
            }
            // Update criteria
            foreach ($tab as $old => $new) {
               $query = "UPDATE `glpi_rulecriterias`
                         SET `criteria` = '$new'
                         WHERE `criteria` = '$old'
                               AND `rules_id` IN ($rules)";
               $DB->queryOrDie($query, "0.78.2 update datas for rules criteria");
            }
         }
      }
   }

   // Reorder ranking : start with 1
   $query = "SELECT DISTINCT `sub_type`
             FROM `glpi_rules`
             WHERE ranking = '0'";
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query = "UPDATE `glpi_rules`
                      SET `ranking` = ranking +1
                      WHERE `sub_type` = '".$data['sub_type']."';";
            $DB->queryOrDie($query, "0.78.2 reorder rule ranking for ".$data['sub_type']);
         }
      }
   }

   // Check existing rule
   if (countElementsInTable('glpi_rulecriterias',
         ['criteria' => ['auto-submitted', 'x-auto-response-suppress']]) == 0 ) {
      /// Reorder ranking
      $query = "UPDATE `glpi_rules`
                SET `ranking` = ranking +2
                WHERE `sub_type` = 'RuleMailCollector';";
      $DB->queryOrDie($query, "0.78.2 reorder rule ranking for RuleMailCollector");

      /// Insert new rule
      $query = "INSERT INTO `glpi_rules`
                       (`entities_id`, `sub_type`, `ranking`, `name`,
                        `description`, `match`, `is_active`, `date_mod`, `is_recursive`)
                VALUES ('0', 'RuleMailCollector', '1', 'Auto-Reply X-Auto-Response-Suppress',
                        'Exclude Auto-Reply emails using X-Auto-Response-Suppress header', 'AND',
                        0, NOW(), 1)";
      $DB->queryOrDie($query, "0.78.2 add new rule RuleMailCollector");
      $rule_id = $DB->insert_id();
      /// Insert criteria and action
      $query = "INSERT INTO `glpi_rulecriterias`
                       (`rules_id`, `criteria`, `condition`, `pattern`)
                VALUES ('$rule_id', 'x-auto-response-suppress', '6', '/\\\\S+/')";
      $DB->queryOrDie($query, "0.78.2 add new criteria RuleMailCollector");

      $query = "INSERT INTO `glpi_ruleactions`
                       (`rules_id`, `action_type`, `field`, `value`)
                VALUES ('$rule_id', 'assign', '_refuse_email_no_response', '1')";
      $DB->queryOrDie($query, "0.78.2 add new action RuleMailCollector");

      /// Insert new rule
      $query = "INSERT INTO `glpi_rules`
                       (`entities_id`, `sub_type`, `ranking`, `name`,
                        `description`, `match`, `is_active`, `date_mod`, `is_recursive`)
                VALUES ('0', 'RuleMailCollector', '2', 'Auto-Reply Auto-Submitted',
                        'Exclude Auto-Reply emails using Auto-Submitted header', 'AND', 0, NOW(), 1)";
      $DB->queryOrDie($query, "0.78.2 add new rule RuleMailCollector");
      $rule_id = $DB->insert_id();
      /// Insert criteria and action
      $query = "INSERT INTO `glpi_rulecriterias`
                       (`rules_id`, `criteria`, `condition`, `pattern`)
                VALUES ('$rule_id', 'auto-submitted', '6', '/\\\\S+/')";
      $DB->queryOrDie($query, "0.78.2 add new criteria RuleMailCollector");

      $query = "INSERT INTO `glpi_rulecriterias`
                       (`rules_id`, `criteria`, `condition`, `pattern`)
                VALUES ('$rule_id', 'auto-submitted', '1', 'no')";
      $DB->queryOrDie($query, "0.78.2 add new criteria RuleMailCollector");

      $query = "INSERT INTO `glpi_ruleactions`
                       (`rules_id`, `action_type`, `field`, `value`)
                VALUES ('$rule_id', 'assign', '_refuse_email_no_response', '1')";
      $DB->queryOrDie($query, "0.78.2 add new action RuleMailCollector");

   }

   if (!$DB->fieldExists('glpi_ocsservers', 'ocs_db_utf8', false)) {
      $query = "ALTER TABLE `glpi_ocsservers`
                ADD `ocs_db_utf8` tinyint(1) NOT NULL default '0' AFTER `ocs_db_name`";

      $DB->queryOrDie($query, "0.78.2 add ocs_db_utf8 in glpi_ocsservers");
   }

   // must always be at the end (only for end message)
   $migration->executeMigration();

   return $updateresult;
}
