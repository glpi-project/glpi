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
use Entity;
use Glpi\UI\IllustrationManager;
use Group;
use KnowbaseItem;
use KnowbaseItem_Revision;
use KnowbaseItemTranslation;
use Log;
use LogicException;
use Profile;
use User;

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
        $this->addCurrentTranslationsToHistory();
        $this->addRevisionsToHistory();
        $this->addTranslationRevisionsToHistory();
        $this->addFaqStatusChangesToHistory();
        $this->addServiceCatalogChangesToHistory();
        $this->addAssociatedItemChangesToHistory();
        $this->addDocumentChangesToHistory();
        $this->addPermissionChangesToHistory();
        $this->addNameChangesToHistory();
        $this->addIllustrationChangesToHistory();

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

    private function addTranslationRevisionsToHistory(): void
    {
        global $DB;

        // Load revisions for the given KB entry.
        // We only load revisions with a defined language (which means it is
        // for a translation).
        $result = $DB->request([
            'SELECT' => [
                'id',
                'revision',
                'users_id',
                'date',
                'language',
            ],
            'FROM' => KnowbaseItem_Revision::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $this->kb->getID(),
                ['NOT' => ['language' => '']],
            ],
            'ORDER' => ['language ASC', 'revision DESC'],
        ]);

        // Group revisions by language, this is needed so we can compute the
        // revision index compared to others revisions from the same language
        // only.
        $by_language = [];
        foreach ($result as $row) {
            $by_language[$row['language']][] = $row;
        }

        // Insert events into the history.
        foreach ($by_language as $language => $rows) {
            $total = \count($rows);
            $current = $total;

            foreach ($rows as $row) {
                $this->history->addEvent(new TranslationRevisionEvent(
                    id: $row['id'],
                    index: $current,
                    date: $row['date'],
                    author_id: (int) $row['users_id'],
                    language: $language,
                ));

                $current--;
            }
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

        $this->history->addEvent(new CurrentVersionLogEvent(
            date: $row['date_mod'],
            author: $row['user_name'],
        ));
    }

    private function addCurrentTranslationsToHistory(): void
    {
        global $DB;

        $translations = $DB->request([
            'SELECT' => [
                'language',
                'users_id',
                'date_mod',
            ],
            'FROM' => KnowbaseItemTranslation::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $this->kb->getID(),
            ],
        ]);

        foreach ($translations as $translation) {
            $this->history->addEvent(new CurrentTranslationEvent(
                language       : $translation['language'],
                date       : $translation['date_mod'],
                author     : $translation['users_id'],
            ));
        }
    }

    private function addPermissionChangesToHistory(): void
    {
        global $DB;

        $target_types = [
            Entity::class,
            Group::class,
            Profile::class,
            User::class,
        ];

        $logs = $DB->request([
            'SELECT' => ['date_mod', 'user_name', 'linked_action', 'old_value', 'new_value'],
            'FROM'   => Log::getTable(),
            'WHERE'  => [
                'itemtype'      => KnowbaseItem::class,
                'items_id'      => $this->kb->getID(),
                'linked_action' => [Log::HISTORY_ADD_RELATION, Log::HISTORY_DEL_RELATION],
                'itemtype_link' => $target_types,
            ],
            'ORDER' => 'id DESC',
        ]);

        foreach ($logs as $row) {
            $is_add = (int) $row['linked_action'] === Log::HISTORY_ADD_RELATION;
            $target_name = $is_add ? $row['new_value'] : $row['old_value'];
            $description = $is_add
                ? sprintf(__("Access granted to %s by"), $target_name)
                : sprintf(__("Access revoked from %s by"), $target_name);

            $this->history->addEvent(new LogEvent(
                label: __("Permissions updated"),
                description: $description,
                date: $row['date_mod'],
                author: $row['user_name'],
            ));
        }
    }

    private function addNameChangesToHistory(): void
    {
        global $DB;

        $logs = $DB->request([
            'SELECT' => ['date_mod', 'user_name', 'old_value', 'new_value'],
            'FROM'   => Log::getTable(),
            'WHERE'  => [
                'itemtype'         => KnowbaseItem::class,
                'items_id'         => $this->kb->getID(),
                'linked_action'    => 0, // Update
                'id_search_option' => 1, // Name
            ],
            'ORDER' => 'id DESC',
        ]);

        foreach ($logs as $row) {
            $this->history->addEvent(new LogEvent(
                label: __("Renamed"),
                description: __("Updated by"),
                date: $row['date_mod'],
                author: $row['user_name'],
                old_value: $row['old_value'],
                new_value: $row['new_value'],
            ));
        }
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

        // Exclude permission types (Entity, Group, Profile, User) as they
        // are handled separately by addPermissionChangesToHistory().
        $permission_types = [
            Entity::class,
            Group::class,
            Profile::class,
            User::class,
        ];
        $item_types = array_values(array_diff($CFG_GLPI['kb_types'], $permission_types));

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
                'itemtype_link' => $item_types,
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

    private function addIllustrationChangesToHistory(): void
    {
        global $DB;

        $logs = $DB->request([
            'SELECT' => [
                'date_mod',
                'user_name',
                'old_value',
                'new_value',
            ],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype'         => KnowbaseItem::class,
                'items_id'         => $this->kb->getID(),
                'linked_action'    => 0, // Update
                'id_search_option' => 88, // illustration
            ],
            'ORDER' => 'id DESC',
        ]);

        foreach ($logs as $row) {
            $is_custom = str_starts_with(
                $row['new_value'],
                IllustrationManager::CUSTOM_ILLUSTRATION_PREFIX
            );
            $description = $is_custom
                ? __("Custom illustration set by")
                : __("Native illustration set by")
            ;

            $this->history->addEvent(new LogEvent(
                label: __("Illustration updated"),
                description: $description,
                date: $row['date_mod'],
                author: $row['user_name'],
            ));
        }
    }

    private function addServiceCatalogChangesToHistory(): void
    {
        global $DB;

        $logs = $DB->request([
            'SELECT' => [
                'date_mod',
                'user_name',
                'new_value',
                'old_value',
                'id_search_option',
            ],
            'FROM' => Log::getTable(),
            'WHERE' => [
                'itemtype'         => KnowbaseItem::class,
                'items_id'         => $this->kb->getID(),
                'linked_action'    => 0, // Update
                'id_search_option' => [
                    84, // show_in_service_catalog
                    85, // is_pinned
                    86, // description
                    87, // forms_categories_id
                ],
            ],
            'ORDER' => 'id DESC',
        ]);

        foreach ($logs as $row) {
            $description = match ($row['id_search_option']) {
                84 => $row['new_value']
                    ? __("Added to the service catalog by")
                    : __("Removed from the service catalog by")
                ,
                85 => $row['new_value']
                    ? __("Pinned to the top by")
                    : __("Unpinned from the top by")
                ,
                86 => __("Description updated by"),
                87 => __("Category updated by"),
                default => throw new LogicException("Impossible"),
            };

            $add_values = in_array($row['id_search_option'], [86, 87]);

            $this->history->addEvent(new LogEvent(
                label: __("Service catalog updated"),
                description: $description,
                date: $row['date_mod'],
                author: $row['user_name'],
                new_value: $add_values ? $row['new_value'] : null,
                old_value: $add_values ? $row['old_value'] : null,
            ));
        }
    }
}
