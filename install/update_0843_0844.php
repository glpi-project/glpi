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
 * Update from 0.84.3 to 0.84.4
 *
 * @return bool for success (will die for most error)
**/
function update0843to0844() {
   global $DB, $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.84.4'));
   $migration->setVersion('0.84.4');

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

   // Migrate templates : back for validation
   $notificationtemplatetranslationsIterator = $DB->request([
      'SELECT'       => "glpi_notificationtemplatetranslations.*",
      'FROM'         => "glpi_notificationtemplatetranslations",
      'INNER JOIN'   => ["glpi_notificationtemplates" => [
            'ON' => [
               'glpi_notificationtemplates' => 'id',
               'glpi_notificationtemplatetranslations' => 'notificationtemplates_id'
            ]
         ]
      ],
      'WHERE'        =>  [
         'OR' => [
            'glpi_notificationtemplatetranslations.content_text' => [
               'LIKE', "%validation.storestatus=%"
            ],
            'glpi_notificationtemplatetranslations.content_html' => [
               'LIKE', "%validation.storestatus=%"
            ],
            'glpi_notificationtemplatetranslations.subject' => [
               'LIKE', "%validation.storestatus=%"
            ]
         ]
      ]
   ]);

   if (count($notificationtemplatetranslationsIterator)) {
      while ($data = $notificationtemplatetranslationsIterator->next()) {
         $subject = $data['subject'];
         $text = $data['content_text'];
         $html = $data['content_html'];
         foreach ($status as $old => $new) {
            $subject = str_replace("validation.storestatus=$new", "validation.storestatus=$old", $subject);
            $text    = str_replace("validation.storestatus=$new", "validation.storestatus=$old", $text);
            $html    = str_replace("validation.storestatus=$new", "validation.storestatus=$old", $html);
         }
         $DB->updateOrDie("glpi_notificationtemplatetranslations", [
               'subject'      => $subject,
               'content_text' => $text,
               'content_html' => $html,
            ], [
               'id' => $data['id']
            ],
            "0.84.4 fix tags usage for storestatus"
         );
      }
   }

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}

