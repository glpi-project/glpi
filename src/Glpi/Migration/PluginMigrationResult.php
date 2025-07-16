<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Migration;

use CommonDBTM;
use Glpi\Message\MessageType;

/**
 * @final
 */
class PluginMigrationResult
{
    /**
     * Whether the migration has been fully processed.
     */
    private bool $is_fully_processed;

    /**
     * Migration messages.
     *
     * @var array<int, array{type: MessageType, message: string}>
     */
    private array $messages = [];

    /**
     * IDs of created items.
     * @var array<class-string<CommonDBTM>, array<int, int>>
     */
    private array $created_items_ids = [];

    /**
     * IDs of reused items.
     * @var array<class-string<CommonDBTM>, array<int, int>>
     */
    private array $reused_items_ids = [];

    /**
     * IDs of ignored items.
     * @var array<class-string<CommonDBTM>, array<int, int>>
     */
    private array $ignored_items_ids = [];

    /**
     * Indicates whether the migration has been fully processed.
     */
    public function isFullyProcessed(): bool
    {
        return $this->is_fully_processed;
    }

    /**
     * Defines whether the migration has been fully processed.
     */
    public function setFullyProcessed(bool $is_fully_processed): void
    {
        $this->is_fully_processed = $is_fully_processed;
    }

    /**
     * Add an error message.
     */
    public function addMessage(MessageType $type, string $message): void
    {
        $this->messages[] = [
            'type'      => $type,
            'message'   => $message,
        ];
    }

    /**
     * Indicates whether errors have occurred.
     */
    public function hasErrors(): bool
    {
        $errors = \array_filter(
            $this->messages,
            static fn(array $entry) => $entry['type'] === MessageType::Error
        );
        return count($errors) > 0;
    }

    /**
     * Get the messages.
     *
     * @return array<int, array{type: MessageType, message: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Mark an item as created.
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param int $id
     */
    public function markItemAsCreated(string $itemtype, int $id): void
    {
        if (!\array_key_exists($itemtype, $this->created_items_ids)) {
            $this->created_items_ids[$itemtype] = [];
        }

        $this->created_items_ids[$itemtype][] = $id;
    }

    /**
     * Return the IDs of the created items.
     *
     * @return array<class-string<CommonDBTM>, array<int, int>>
     */
    public function getCreatedItemsIds(): array
    {
        return $this->created_items_ids;
    }

    /**
     * Mark an item as reused.
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param int $id
     */
    public function markItemAsReused(string $itemtype, int $id): void
    {
        if (!\array_key_exists($itemtype, $this->reused_items_ids)) {
            $this->reused_items_ids[$itemtype] = [];
        }

        $this->reused_items_ids[$itemtype][] = $id;
    }

    /**
     * Return the IDs of the reused items.
     *
     * @return array<class-string<CommonDBTM>, array<int, int>>
     */
    public function getReusedItemsIds(): array
    {
        return $this->reused_items_ids;
    }

    /**
     * Mark an item as ignored.
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param int $id
     */
    public function markItemAsIgnored(string $itemtype, int $id): void
    {
        if (!\array_key_exists($itemtype, $this->ignored_items_ids)) {
            $this->ignored_items_ids[$itemtype] = [];
        }

        $this->ignored_items_ids[$itemtype][] = $id;
    }

    /**
     * Return the IDs of the ignored items.
     *
     * @return array<class-string<CommonDBTM>, array<int, int>>
     */
    public function getIgnoredItemsIds(): array
    {
        return $this->ignored_items_ids;
    }
}
