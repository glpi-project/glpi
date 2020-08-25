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
 * Update from 9.5.1 to 9.5.2
 *
 * @return bool for success (will die for most error)
 **/
function update951to952() {
   global $DB, $migration;

   $updateresult     = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.5.2'));
   $migration->setVersion('9.5.2');

   /* Fix document_item migration */
   $migration->displayTitle("Building inline images data in " . Document_Item::getTable());

   $itemtypes = [
      'ITILFollowup' => 'content',
      'ITILSolution' => 'content',
      'Reminder'     => 'text',
      'KnowbaseItem' => 'answer'
   ];

   foreach (['Change', 'Problem', 'Ticket'] as $itiltype) {
        $itemtypes[$itiltype] = 'content';
        $itemtypes[$itiltype . 'Task'] = 'content';
   }

   $docs_input =[];
   foreach ($itemtypes as $itemtype => $field) {
      // Check ticket and child items (followups, tasks, solutions) contents
      $regexPattern = 'document\\\.send\\\.php\\\?docid=[0-9]+';
      $user_field = is_a($itemtype, CommonITILObject::class, true) ? 'users_id_recipient' : 'users_id';
      $result = $DB->request([
         'SELECT' => ['id', $field, $user_field],
         'FROM'   => $itemtype::getTable(),
         'WHERE'  => [
            $field => ['REGEXP', $regexPattern]
         ]
      ]);

      while ($data = $result->next()) {
         preg_match_all('/document\\.send\\.php\\?docid=([0-9]+)/', $data[$field], $matches);

         // No inline documents found in this item, skip to next
         if (!isset($matches[1])) {
            continue;
         }

         foreach ($matches[1] as $docid) {
            $docs_input[] = [
               'documents_id'       => $docid,
               'itemtype'           => $itemtype,
               'items_id'           => $data['id'],
               'timeline_position'  => CommonITILObject::NO_TIMELINE,
               'users_id'           => $data[$user_field],
               '_disablenotif'      => true, // prevent parent object "update" notification
            ];
         }
      }
   }

   $ditem = new Document_Item();
   foreach ($docs_input as $doc_input) {
      if (!$ditem->alreadyExists($doc_input)) {
         $ditem->add($doc_input);
      }
   }
   /* /Fix document_item migration */

   /* Register missing DomainAlert crontask */
   CronTask::Register(
      'Domain',
      'DomainsAlert',
      DAY_TIMESTAMP,
      [
         'mode'  => CronTask::MODE_EXTERNAL,
         'state' => CronTask::STATE_DISABLE,
      ]
   );
   /* /Register missing DomainAlert crontask */

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
