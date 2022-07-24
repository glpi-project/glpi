<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * Update from 9.5.1 to 9.5.2
 *
 * @return bool for success (will die for most error)
 **/
function update951to952()
{
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

    $docs_input = [];
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

        foreach ($result as $data) {
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
                    '_do_update_ticket'  => false
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
            'state' => CronTask::STATE_WAITING,
        ]
    );
   /* /Register missing DomainAlert crontask */

   //add option to collect only unread mail
    $migration->addField('glpi_mailcollectors', 'collect_only_unread', 'bool', ['value' => 0]);

   /* Appliances rewrite */
    $migration->addField('glpi_appliances', 'is_helpdesk_visible', 'bool', ['after' => 'otherserial', 'value' => 1]);
    $migration->addKey('glpi_appliances', 'is_helpdesk_visible');
    $migration->addField('glpi_states', 'is_visible_appliance', 'bool', [
        'value' => 1,
        'after' => 'is_visible_contract'
    ]);
    $migration->addKey('glpi_states', 'is_visible_appliance');

    if ($DB->tableExists('glpi_appliancerelations')) {
        $migration->dropKey('glpi_appliancerelations', 'relations_id');
        $migration->changeField('glpi_appliancerelations', 'relations_id', 'items_id', 'integer');
        $migration->addField(
            'glpi_appliancerelations',
            'itemtype',
            'VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL',
            ['after' => 'appliances_items_id']
        );
        $migration->addKey('glpi_appliancerelations', 'itemtype');
        $migration->addKey('glpi_appliancerelations', 'items_id');
        $migration->addKey('glpi_appliancerelations', [
            'itemtype',
            'items_id',
        ], 'item');
        $migration->migrationOneTable('glpi_appliancerelations');
        $migration->renameTable('glpi_appliancerelations', 'glpi_appliances_items_relations');
    }

    if ($DB->fieldExists('glpi_appliances', 'relationtype')) {
        $iterator = $DB->request([
            'SELECT' => ['items.id', 'app.relationtype'],
            'FROM'   => 'glpi_appliances_items AS items',
            'LEFT JOIN' => [
                'glpi_appliances AS app' => [
                    'ON'  => [
                        'app'    => 'id',
                        'items'  => 'appliances_id'
                    ]
                ]
            ]
        ]);
        foreach ($iterator as $row) {
            $itemtype = null;
            switch ($row['relationtype']) {
                case 1:
                    $itemtype = 'Location';
                    break;
                case 2:
                    $itemtype = 'Network';
                    break;
                case 3:
                    $itemtype = 'Domain';
                    break;
            }

            $migration->addPostQuery(
                $DB->buildUpdate(
                    'glpi_appliances_items_relations',
                    [
                        'itemtype'  => $itemtype
                    ],
                    [
                        'appliances_items_id'   => $row['id']
                    ]
                )
            );
        }
        $migration->dropField('glpi_appliances', 'relationtype');
    }
   /* /Appliances rewrite */

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
