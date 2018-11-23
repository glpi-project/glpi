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
 * Update from 9.3 to 9.4
 *
 * @return bool for success (will die for most error)
**/
function update93to94() {
   global $DB, $migration, $CFG_GLPI;
   $dbutils = new DbUtils();

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];
   $config_to_drop = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.4'));
   $migration->setVersion('9.4');

   /** Add otherserial field on ConsumableItem */
   if (!$DB->fieldExists('glpi_consumableitems', 'otherserial')) {
      $migration->addField("glpi_consumableitems", "otherserial", "varchar(255) NULL DEFAULT NULL");
      $migration->addKey("glpi_consumableitems", 'otherserial');
   }
   /** /Add otherserial field on ConsumableItem */

   /** Add default group for a user */
   if ($migration->addField('glpi_users', 'groups_id', 'integer')) {
      $migration->addKey('glpi_users', 'groups_id');
   }
   /** /Add default group for a user */

   /** Add requester field on glpi_mailcollectors */
   $migration->addField("glpi_mailcollectors", "requester_field", "integer", [
      'value' => '0'
   ]);
   /** /Add requester field on glpi_mailcollectors */

   /** Increase value length for criteria */
   $migration->changeField('glpi_rulecriterias', 'pattern', 'pattern', 'text');
   /** /Increase value length for criteria */

   /** Add business rules on assets */
   $rule = ['name'         => 'Domain user assignation',
            'is_active'    => 1,
            'is_recursive' => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => 3,
            'entities_id'  => 0,
            'uuid'         => 'fbeb1115-7a37b143-5a3a6fc1afdc17.92779763',
            'match'        => \Rule::AND_MATCHING
           ];
   $criteria = [
      ['criteria' => '_itemtype', 'condition' => \Rule::PATTERN_IS, 'pattern' => 'Computer'],
      ['criteria' => '_auto', 'condition' => \Rule::PATTERN_IS, 'pattern' => 1],
      ['criteria' => 'contact', 'condition' => \Rule::REGEX_MATCH, 'pattern' => '/(.*)@/']
   ];
   $action = [['action_type' => 'regex_result', 'field' => '_affect_user_by_regex', 'value' => '#0']];
   $migration->createRule($rule, $criteria, $action);

   $rule = ['name'         => 'Multiple users: assign to the first',
            'is_active'    => 1,
            'is_recursive' => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => 3,
            'entities_id'  => 0,
            'uuid'         => 'fbeb1115-7a37b143-5a3a6fc1b03762.88595154',
            'match'        => \Rule::AND_MATCHING
           ];
   $criteria = [
      ['criteria' => '_itemtype', 'condition' => \Rule::PATTERN_IS, 'pattern' => 'Computer'],
      ['criteria' => '_auto', 'condition' => \Rule::PATTERN_IS, 'pattern' => 1],
      ['criteria' => 'contact', 'condition' => \Rule::REGEX_MATCH, 'pattern' => '/(.*),/']
   ];
   $migration->createRule($rule, $criteria, $action);

   $rule = ['name'         => 'One user assignation',
            'is_active'    => 1,
            'is_recursive' => 1,
            'sub_type'     => 'RuleAsset',
            'condition'    => 3,
            'entities_id'  => 0,
            'uuid'         => 'fbeb1115-7a37b143-5a3a6fc1b073e1.16257440',
            'match'        => \Rule::AND_MATCHING
           ];
   $criteria = [
      ['criteria' => '_itemtype', 'condition' => \Rule::PATTERN_IS, 'pattern' => 'Computer'],
      ['criteria' => '_auto', 'condition' => \Rule::PATTERN_IS, 'pattern' => 1],
      ['criteria' => 'contact', 'condition' => \Rule::REGEX_MATCH, 'pattern' => '/(.*)/']
   ];
   $migration->createRule($rule, $criteria, $action);

   if (!countElementsInTable('glpi_profilerights', ['profiles_id' => 4, 'name' => 'rule_asset'])) {
      $DB->query("INSERT INTO `glpi_profilerights` VALUES ('NULL','4','rule_asset','255')");
   }
   /** /Add business rules on assets */

   /** Drop use_rich_text parameter */
   $config_to_drop[] = 'use_rich_text';
   /** /Drop use_rich_text parameter */

   /** Drop ticket_timeline* parameters */
   $config_to_drop[] = 'ticket_timeline';
   $config_to_drop[] = 'ticket_timeline_keep_replaced_tabs';
   $migration->dropField('glpi_users', 'ticket_timeline');
   $migration->dropField('glpi_users', 'ticket_timeline_keep_replaced_tabs');
   /** /Drop ticket_timeline* parameters */

   /** Replacing changes_projects by itils_projects */
   if ($DB->tableExists('glpi_changes_projects')) {
      $migration->renameTable('glpi_changes_projects', 'glpi_itils_projects');

      $migration->dropKey('glpi_itils_projects', 'unicity');
      // Key have to be dropped now to be able to create a new one having same name
      $migration->migrationOneTable('glpi_itils_projects');

      $migration->addField(
         'glpi_itils_projects',
         'itemtype',
         "varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''",
         [
            'after'  => 'id',
            'update' => "'Change'",
         ]
      );

      $migration->changeField(
         'glpi_itils_projects',
         'changes_id',
         'items_id',
         "int(11) NOT NULL DEFAULT '0'"
      );

      $migration->addKey(
         'glpi_itils_projects',
         ['itemtype', 'items_id', 'projects_id'],
         'unicity',
         'UNIQUE'
      );
      $migration->migrationOneTable('glpi_itils_projects');
   }
   /** /Replacing changes_projects by itils_projects */

   /** Rename non fkey field */
   $migration->changeField(
      'glpi_items_operatingsystems',
      'license_id',
      'licenseid',
      "string"
   );
   /** Rename non fkey field */

   /** Add watcher visibility to groups */
   if (!$DB->fieldExists('glpi_groups', 'is_watcher')) {
      if ($migration->addField('glpi_groups', 'is_watcher', "tinyint(1) NOT NULL DEFAULT '1'", ['after' => 'is_requester'])) {
         $migration->addKey('glpi_groups', 'is_watcher');
         $migration->migrationOneTable('glpi_groups');
      }
   }
   /** Add watcher visibility to groups */

   Config::deleteConfigurationValues('core', $config_to_drop);

   // Add a config entry for the CAS version
   $migration->addConfig(['cas_version' => 'CAS_VERSION_2_0']);

   /** Factorize components search options on Computers, Printers and NetworkEquipments */
   $so_maping = [
      '10'  => '110',
      '35'  => '111',
      '11'  => '112',
      '20'  => '113',
      '15'  => '114',
      '34'  => '115',
      '39'  => '116',
      '95'  => '117'
   ];
   foreach ($so_maping as $old => $new) {
      $DB->updateOrDie(
         'glpi_displaypreferences', [
            'num' => $new
         ], [
            'num'       => $old,
            'itemtype'  => 'Computer'
         ]
      );
   }
   /** /Factorize components search options on Computers, Printers and NetworkEquipments */

   /** Add followup tables for new ITILFollowup class */
   if (!$DB->tableExists('glpi_itilfollowups')) {
      //Migrate ticket followups
      $migration->renameTable('glpi_ticketfollowups', 'glpi_itilfollowups');
      $migration->addField(
         'glpi_itilfollowups',
         'itemtype',
         "varchar(100) COLLATE utf8_unicode_ci NOT NULL",
         [
            'after'  => 'id',
            'update' => "'Ticket'", // Defines value for all existing elements
         ]
      );

      $migration->changeField(
         'glpi_itilfollowups',
         'tickets_id',
         'items_id',
         "int(11) NOT NULL DEFAULT '0'"
      );
      $migration->addKey(
         'glpi_itilfollowups',
         'itemtype'
      );
      $migration->dropKey(
         'glpi_itilfollowups',
         'tickets_id'
      );
      $migration->addKey(
         'glpi_itilfollowups',
         'items_id',
         'item_id'
      );
      $migration->addKey(
         'glpi_itilfollowups',
         ['itemtype','items_id'],
         'item'
      );
   }

   if ($DB->fieldExists('glpi_requesttypes', 'is_ticketfollowup')) {
      $migration->changeField(
         'glpi_requesttypes', 'is_ticketfollowup', 'is_itilfollowup', 'bool', ['value' => '1']
      );
      $migration->dropKey(
         'glpi_requesttypes',
         'is_ticketfollowup'
      );
      $migration->addKey(
         'glpi_requesttypes',
         'is_itilfollowup'
      );
   }

   if ($DB->fieldExists('glpi_itilsolutions', 'ticketfollowups_id')) {
      $migration->changeField(
         'glpi_itilsolutions',
         'ticketfollowups_id',
         'itilfollowups_id',
         "int(11) DEFAULT NULL"
      );
      $migration->dropKey(
         'glpi_itilsolutions',
         'ticketfollowups_id'
      );
      $migration->addKey(
         'glpi_itilsolutions',
         'itilfollowups_id'
      );
   }

   /** Add timeline_position to Change and Problem items */
   $migration->addField("glpi_changetasks", "timeline_position", "tinyint(1) NOT NULL DEFAULT '0'");
   $migration->addField("glpi_changevalidations", "timeline_position", "tinyint(1) NOT NULL DEFAULT '0'");
   $migration->addField("glpi_problemtasks", "timeline_position", "tinyint(1) NOT NULL DEFAULT '0'");

   /** Give all existing profiles access to personalizations for legacy functionality */
   $migration->addRight('personalization', READ | UPDATE);

   /** Search engine on plugins */
   $ADDTODISPLAYPREF['Plugin'] = [2, 3, 4, 5, 6, 7, 8];

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $rank = 1;
      foreach ($tab as $newval) {
         $query = "REPLACE INTO `glpi_displaypreferences`
                           (`itemtype` ,`num` ,`rank` ,`users_id`)
                     VALUES ('$type', '$newval', '".$rank++."', '0')";
         $DB->query($query);
      }
   }

   /** Renaming olas / slas foreign keys that does not match naming conventions */
   $olas_slas_mapping = [
      'olas_tto_id'      => 'olas_id_tto',
      'olas_ttr_id'      => 'olas_id_ttr',
      'ttr_olalevels_id' => 'olalevels_id_ttr',
      'slas_tto_id'      => 'slas_id_tto',
      'slas_ttr_id'      => 'slas_id_ttr',
      'ttr_slalevels_id' => 'slalevels_id_ttr',
   ];
   foreach ($olas_slas_mapping as $old_fieldname => $new_fieldname) {
      if ($DB->fieldExists('glpi_tickets', $old_fieldname)) {
         $migration->changeField('glpi_tickets', $old_fieldname, $new_fieldname, 'integer');
      }
      $migration->dropKey('glpi_tickets', $old_fieldname);
      $migration->addKey('glpi_tickets', $new_fieldname);
   }

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
