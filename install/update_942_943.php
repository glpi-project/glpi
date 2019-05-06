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
 * Update from 9.4.2 to 9.4.3
 *
 * @return bool for success (will die for most error)
**/
function update942to943() {
   global $DB, $migration;

   $updateresult     = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.4.3'));
   $migration->setVersion('9.4.3');

   /** Fix URL of images inside ITIL objects contents */
   // This is an exact copy of the same process used in "update940to941()" and "update941to942()"
   // which was not working for elements having a simple quote in their content.
   // It has been fixed there for people who had not yet updated to 9.4.1 / 9.4.2 but have to
   // be put back here for people already having updated to 9.4.1 / 9.4.2.
   $migration->displayMessage(sprintf(__('Fix URL of images in ITIL tasks, followups ans solutions.')));

   // Search for contents that does not contains the itil object parameter after the docid parameter
   // (i.e. having a quote that ends the href just after the docid param value).
   // 1st capturing group is the end of href attribute value
   // 2nd capturing group is the href attribute ending quote
   $quotes_possible_exp   = ['\'', '&apos;', '&#39;', '&#x27;', '"', '&quot', '&#34;', '&#x22;'];
   $missing_param_pattern = '(document\.send\.php\?docid=[0-9]+)(' . implode('|', $quotes_possible_exp) . ')';

   $itil_mappings = [
      'Change' => [
         'itil_table' => 'glpi_changes',
         'itil_fkey'  => 'changes_id',
         'task_table' => 'glpi_changetasks',
      ],
      'Problem' => [
         'itil_table' => 'glpi_problems',
         'itil_fkey'  => 'problems_id',
         'task_table' => 'glpi_problemtasks',
      ],
      'Ticket' => [
         'itil_table' => 'glpi_tickets',
         'itil_fkey'  => 'tickets_id',
         'task_table' => 'glpi_tickettasks',
      ],
   ];

   $fix_content_fct = function($content, $itil_id, $itil_fkey) use ($missing_param_pattern) {
      // Add itil object param between docid param ($1) and ending quote ($2)
      return preg_replace(
         '/' . $missing_param_pattern . '/',
         '$1&amp;' . http_build_query([$itil_fkey => $itil_id]) . '$2',
         $content
      );
   };

   foreach ($itil_mappings as $itil_type => $itil_specs) {
      $itil_fkey  = $itil_specs['itil_fkey'];
      $task_table = $itil_specs['task_table'];

      // Fix followups and solutions
      foreach (['glpi_itilfollowups', 'glpi_itilsolutions'] as $itil_element_table) {
         $elements_to_fix = $DB->request(
            [
               'SELECT'    => ['id', 'items_id', 'content'],
               'FROM'      => $itil_element_table,
               'WHERE'     => [
                  'itemtype' => $itil_type,
                  'content'  => ['REGEXP', $DB->escape($missing_param_pattern)],
               ]
            ]
         );
         foreach ($elements_to_fix as $data) {
            $data['content'] = $DB->escape($fix_content_fct($data['content'], $data['items_id'], $itil_fkey));
            $DB->update($itil_element_table, $data, ['id' => $data['id']]);
         }
      }

      // Fix tasks
      $tasks_to_fix = $DB->request(
         [
            'SELECT'    => ['id', $itil_fkey, 'content'],
            'FROM'      => $task_table,
            'WHERE'     => [
               'content'  => ['REGEXP', $DB->escape($missing_param_pattern)],
            ]
         ]
      );
      foreach ($tasks_to_fix as $data) {
         $data['content'] = $DB->escape($fix_content_fct($data['content'], $data[$itil_fkey], $itil_fkey));
         $DB->update($task_table, $data, ['id' => $data['id']]);
      }
   }
   /** /Fix URL of images inside ITIL objects contents */

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
