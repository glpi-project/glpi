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
 * Update from 0.84.5 to 0.84.6
 *
 * @return bool for success (will die for most error)
**/
function update0845to0846() {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.84.6'));
   $migration->setVersion('0.84.6');

   // TODO : can be improved once DBmysql->update() supports JOIN
   // correct entities_id in documents_items
   $query_doc_i = "UPDATE `glpi_documents_items` as `doc_i`
                   INNER JOIN `glpi_documents` as `doc`
                   ON  `doc`.`id` = `doc_i`.`documents_id`
                   SET `doc_i`.`entities_id` = `doc`.`entities_id`,
                       `doc_i`.`is_recursive` = `doc`.`is_recursive`";
   $DB->queryOrDie($query_doc_i, "0.84.6 change entities_id in documents_items");

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
   // Migrate datas
   foreach ($status as $old => $new) {
      $DB->updateOrDie("glpi_tickettemplatepredefinedfields", [
            'value' => $new
         ], [
            'value'  => $old,
            'num'    => 12
         ],
         "0.84.6 status in glpi_tickettemplatepredefinedfields $old to $new"
      );
   }
   foreach (['glpi_ipaddresses', 'glpi_networknames'] as $table) {
      $migration->dropKey($table, 'item');
      $migration->migrationOneTable($table);
      $migration->addKey($table, ['itemtype', 'items_id', 'is_deleted'], 'item');
   }

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}
