<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Knowbase\History;

use Document;
use KnowbaseItem;
use KnowbaseItem_Revision;
use Log;

final class HistoryBuilder
{
    private HistoryEventList $history;

    public function __construct(
        private KnowbaseItem $kb,
    ) {
        $this->history = new HistoryEventList();
    }

    public function buildHistory(): HistoryEventList
    {
        $this->addCurrentVersionToHistory();
        $this->addRevisionsToHistory();
        $this->addFaqStatusChangesToHistory();
        $this->addAssociatedItemChangesToHistory();
        $this->addDocumentChangesToHistory();
        $this->history->sort();
        return $this->history;
    }

    private function addRevisionsToHistory(): void
    {
        global $DB;

        // Fetch revisions from database
        $result = $DB->request([
            'SELECT' => [
                'id',
                'revision',
                'users_id',
                'date',
            ],
            'FROM' => KnowbaseItem_Revision::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $this->kb->getID(),
                'language' => '',
            ],
            'ORDER' => ['revision DESC'],
        ]);

        $total = \count($result);
        $current = $total;

        foreach ($result as $row) {
            $this->history->addEvent(new RevisionEvent(
                id: $row['id'],
                index: $current,
                date: $row['date'],
                author_id: (int) $row['users_id'],
            ));

            $current--;
        }
    }

    private function addCurrentVersionToHistory(): void
    {
        global $DB;

        // Current version will either be the last update to the content field
        // or the creation date if the content was never updated.
        $result = $DB->request([
            'SELECT' => [
                'date_mod',
                'user_name',
            ],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype'         => KnowbaseItem::class,
                'items_id'         => $this->kb->getID(),
                'linked_action'    => 0, // Update
                'id_search_option' => 7, // Content
            ],
            'LIMIT' => 1,
            'ORDER' => 'id DESC',
        ]);

        if ($result->count() === 0) {
            // There was no update yet, fallback to creation date
            $this->history->addEvent(new CreationEvent(
                date       : $this->kb->fields['date_creation'],
                author     : $this->kb->fields['users_id'],
            ));
            return;
        }

        $row = $result->current();

        $this->history->addEvent(new LogEvent(
            label: __("Current version"),
            description: __("Updated by"),
            date: $row['date_mod'],
            author: $row['user_name'],
        ));
    }

    private function addFaqStatusChangesToHistory(): void
    {
        global $DB;

        $logs = $DB->request([
            'SELECT' => [
                'date_mod',
                'user_name',
                'new_value',
            ],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype'         => KnowbaseItem::class,
                'items_id'         => $this->kb->getID(),
                'linked_action'    => 0, // Update
                'id_search_option' => 8, // is_faq
            ],
            'ORDER' => 'id DESC',
        ]);


        foreach ($logs as $row) {
            $label = $row['new_value']
                ? __("Added to the FAQ")
                : __("Removed from the FAQ")
            ;

            $this->history->addEvent(new LogEvent(
                label: $label,
                description: __("Updated by"),
                date: $row['date_mod'],
                author: $row['user_name'],
            ));
        }
    }

    private function addAssociatedItemChangesToHistory(): void
    {
        global $DB, $CFG_GLPI;

        $logs = $DB->request([
            'SELECT' => [
                'date_mod',
                'user_name',
                'linked_action',
                'itemtype_link',
                'old_value',
                'new_value',
            ],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype'      => KnowbaseItem::class,
                'items_id'      => $this->kb->getID(),
                'itemtype_link' => $CFG_GLPI['kb_types'],
                'linked_action' => [
                    Log::HISTORY_ADD_RELATION,
                    Log::HISTORY_DEL_RELATION,
                ],
            ],
            'ORDER' => 'id DESC',
        ]);

        foreach ($logs as $row) {
            $is_add = $row['linked_action'] == Log::HISTORY_ADD_RELATION;
            $item_name = $is_add ? $row['new_value'] : $row['old_value'];
            $type_name = $row['itemtype_link']::getTypeName(1);

            $label = $is_add
                ? __("Item linked")
                : __("Item unlinked")
            ;

            $description = sprintf(
                $is_add ? __('%s — Linked by') : __('%s — Unlinked by'),
                $type_name . ': ' . $item_name
            );

            $this->history->addEvent(new LogEvent(
                label: $label,
                description: $description,
                date: $row['date_mod'],
                author: $row['user_name'],
            ));
        }
    }

    private function addDocumentChangesToHistory(): void
    {
        global $DB;

        $logs = $DB->request([
            'SELECT' => [
                'date_mod',
                'user_name',
                'linked_action',
                'old_value',
                'new_value',
            ],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype'      => KnowbaseItem::class,
                'items_id'      => $this->kb->getID(),
                'itemtype_link' => Document::class,
                'linked_action' => [
                    Log::HISTORY_ADD_RELATION,
                    Log::HISTORY_DEL_RELATION,
                ],
            ],
            'ORDER' => 'id DESC',
        ]);

        foreach ($logs as $row) {
            $is_add = $row['linked_action'] == Log::HISTORY_ADD_RELATION;
            $document_name = $is_add ? $row['new_value'] : $row['old_value'];

            $label = $is_add
                ? __("File added")
                : __("File removed")
            ;

            $description = sprintf(
                $is_add ? __('%s — Added by') : __('%s — Removed by'),
                $document_name
            );

            $this->history->addEvent(new LogEvent(
                label: $label,
                description: $description,
                date: $row['date_mod'],
                author: $row['user_name'],
            ));
        }
    }
}
