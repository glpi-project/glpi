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
 * Update from 0.84 to 0.84.1
 *
 * @return bool for success (will die for most error)
**/
function update084to0841() {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.84.1'));
   $migration->setVersion('0.84.1');

   // Convert html fields from numeric encoding to raw encoding
   $fields_to_clean = ['glpi_knowbaseitems'                    => 'answer',
                            'glpi_tickets'                          => 'solution',
                            'glpi_problems'                         => 'solution',
                            'glpi_reminders'                        => 'text',
                            'glpi_solutiontemplates'                => 'content',
                            'glpi_notificationtemplatetranslations' => 'content_text'];
   foreach ($fields_to_clean as $table => $field) {
      $iterator = $DB->request($table);
      foreach ($iterator as $data) {
         $text  = Toolbox::unclean_html_cross_side_scripting_deep($data[$field]);
         $text  = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
         $text  = Toolbox::clean_cross_side_scripting_deep($text);
         $DB->updateOrDie($table,
            [$field  => $text],
            ['id'    => $data['id']],
            "0.84.1 fix encoding of html field : $table.$field"
         );
      }
   }

   // Add date_mod to document_item
   $migration->addField('glpi_documents_items', 'date_mod', 'datetime');
   $migration->migrationOneTable('glpi_documents_items');
   // TODO : can be improved once DBmysql->update() supports JOIN
   $query_doc_i = "UPDATE `glpi_documents_items` as `doc_i`
                   INNER JOIN `glpi_documents` as `doc`
                     ON  `doc`.`id` = `doc_i`.`documents_id`
                   SET `doc_i`.`date_mod` = `doc`.`date_mod`";
   $DB->queryOrDie($query_doc_i,
                  "0.84.1 update date_mod in glpi_documents_items");

   // correct entities_id in documents_items
   // TODO : can be improved once DBmysql->update() supports JOIN
   $query_doc_i = "UPDATE `glpi_documents_items` as `doc_i`
                   INNER JOIN `glpi_documents` as `doc`
                     ON  `doc`.`id` = `doc_i`.`documents_id`
                   SET `doc_i`.`entities_id` = `doc`.`entities_id`,
                       `doc_i`.`is_recursive` = `doc`.`is_recursive`";
   $DB->queryOrDie($query_doc_i, "0.84.1 change entities_id in documents_items");

   // add delete_problem
   $migration->addField('glpi_profiles', 'delete_problem', 'char',
                        ['after'  => 'edit_all_problem',
                              'update' => 'edit_all_problem']);

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}

