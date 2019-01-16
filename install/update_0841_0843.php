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
 * Update from 0.84.1 to 0.84.3
 *
 * @return bool for success (will die for most error)
**/
function update0841to0843() {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.84.3'));
   $migration->setVersion('0.84.3');

   // Upgrade ticket bookmarks and clean _glpi_csrf_token
   $status =  ['new'           => CommonITILObject::INCOMING,
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

   $bookmarksIterator = $DB->request("glpi_bookmarks");

   if (count($bookmarksIterator)) {
      while ($data = $bookmarksIterator->next()) {
         $options = [];
         parse_str($data["query"], $options);

         // unset _glpi_csrf_token
         if (isset($options['_glpi_csrf_token'])) {
            unset($options['_glpi_csrf_token']);
         }
         if (isset($options['field'])) {
            // update ticket statuses
            if ((($data['itemtype'] == 'Ticket')
                 || ($data['itemtype'] == 'Problem'))
                &&( $data['type'] == Bookmark::SEARCH)) {
               foreach ($options['field'] as $key => $val) {
                  if (($val == 12)
                      && isset($options['contains'][$key])) {
                     if (isset($status[$options['contains'][$key]])) {
                        $options['contains'][$key] = $status[$options['contains'][$key]];
                     }
                  }
               }
            }

            // Fix computer / allassets bookmarks : 17 -> 7 / 18 -> 8 / 7 -> 17
            if ((($data['itemtype'] == 'Computer')
                 || ($data['itemtype'] == 'AllAssets'))
                && ($data['type'] == Bookmark::SEARCH)) {
               foreach ($options['field'] as $key => $val) {
                  switch ($val) {
                     case 17 :
                        if (isset($options['contains'][$key])) {
                           $options['field'][$key] = 7;
                        }
                        break;

                     case 18 :
                        if (isset($options['contains'][$key])) {
                           $options['field'][$key] = 8;
                        }
                        break;

                     case 7 :
                        if (isset($options['contains'][$key])) {
                           $options['field'][$key] = 17;
                        }
                        break;
                  }
               }
            }
         }

         $DB->updateOrDie("glpi_bookmarks",
            ['query' => Toolbox::append_params($options)],
            ['id' => $data['id']],
            "0.84.3 update bookmarks"
         );
      }
   }

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}

require_once __DIR__ .'/old_objects.php';
