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
 * Update from 0.80 to 0.80.1
 *
 * @return bool for success (will die for most error)
**/
function update080to0801() {
   global $DB, $migration;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.80.1'));
   $migration->setVersion('0.80.1');

   // Clean duplicates
   $iterator = $DB->request([
      'SELECT'    => [
         "tickets_id",
         "type",
         "groups_id"
      ],
      'COUNT'     => "CPT",
      'FROM'      => "glpi_groups_tickets",
      'GROUPBY'   => ["tickets_id", "type", "groups_id"],
      'HAVING'    => [
         'CPT' => [">", 1]
      ]
   ]);
   foreach ($iterator as $data) {
      // Skip first
      $iterator2 = $DB->request([
         'SELECT' => "id",
         'FROM'   => "glpi_groups_tickets",
         'WHERE'  => [
            'tickets_id'   => $data['tickets_id'],
            'type'         => $data['type'],
            'groups_id'    => $data['groups_id'],
         ],
         'ORDER' => "id DESC",
         'START' => 1,
         'LIMIT' => 99999
      ]);
      foreach ($iterator2 as $data2) {
         $DB->deleteOrDie("glpi_groups_tickets", [
               'id' => $data2['id']
            ],
            "0.80.1 clean to update glpi_groups_tickets"
         );
      }
   }
   $migration->dropKey('glpi_groups_tickets', 'unicity');
   $migration->migrationOneTable('glpi_groups_tickets');
   $migration->addKey("glpi_groups_tickets", ['tickets_id', 'type','groups_id'],
                      "unicity", "UNIQUE");

   // Clean duplicates
   $iterator = $DB->request([
      'SELECT'    => [
         "tickets_id",
         "type",
         "users_id",
         "alternative_email",
      ],
      'COUNT'     => "CPT",
      'FROM'      => "glpi_tickets_users",
      'GROUPBY'   => ["tickets_id", "type", "users_id", "alternative_email"],
      'HAVING'    => [
         'CPT' => [">", 1]
      ]
   ]);
   foreach ($iterator as $data) {
      // Skip first
      $iterator2 = $DB->request([
         'SELECT' => "id",
         'FROM'   => "glpi_tickets_users",
         'WHERE'  => [
            'tickets_id'         => $data['tickets_id'],
            'type'               => $data['type'],
            'users_id'           => $data['users_id'],
            'alternative_email'  => $data['alternative_email'],
         ],
         'ORDER' => "id DESC",
         'START' => 1,
         'LIMIT' => 99999
      ]);
      foreach ($iterator2 as $data2) {
         $DB->deleteOrDie("glpi_tickets_users", [
               'id' => $data2['id']
            ],
            "0.80.1 clean to update glpi_tickets_users"
         );
      }
   }
   $migration->dropKey('glpi_tickets_users', 'tickets_id');
   $migration->migrationOneTable('glpi_tickets_users');
   $migration->addKey("glpi_tickets_users",
                      ['tickets_id', 'type','users_id','alternative_email'],
                      "unicity", "UNIQUE");

   $migration->addField("glpi_ocsservers", "ocs_version", "VARCHAR( 255 ) NULL");

   if ($migration->addField("glpi_slalevels", "entities_id", "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->addField("glpi_slalevels", "is_recursive", "TINYINT( 1 ) NOT NULL DEFAULT 0");
      $migration->migrationOneTable('glpi_slalevels');

      $entities    = getAllDatasFromTable('glpi_entities');
      $entities[0] = "Root";

      foreach (array_keys($entities) as $entID) {
         // Non recursive ones
         $DB->updateOrDie("glpi_slalevels", [
               'entities_id'  => $entID,
               'is_recursive' => 0
            ], [
               'slas_id' => new \QuerySubQuery([
                  'SELECT' => "id",
                  'FROM'   => "glpi_slas",
                  'WHERE'  => [
                     "entities_id"  => $entID,
                     "is_recursive" => 0
                  ]
               ])
            ],
            "0.80.1 update entities_id and is_recursive=0 in glpi_slalevels"
         );

         // Recursive ones
         $DB->updateOrDie("glpi_slalevels", [
               'entities_id'  => $entID,
               'is_recursive' => 1
            ], [
               'slas_id' => new \QuerySubQuery([
                  'SELECT' => "id",
                  'FROM'   => "glpi_slas",
                  'WHERE'  => [
                     "entities_id"  => $entID,
                     "is_recursive" => 1
                  ]
               ])
            ],
            "0.80.1 update entities_id and is_recursive=1 in glpi_slalevels"
         );
      }
   }

   // must always be at the end
   $migration->executeMigration();

   return true;
}
