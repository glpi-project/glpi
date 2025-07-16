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

namespace Glpi\Progress;

use Glpi\Message\MessageType;
use RuntimeException;

/**
 * @final
 */
class StoredProgressIndicator extends AbstractProgressIndicator
{
    /**
     * Storage service used to store the current indicator.
     */
    private ?ProgressStorage $progress_storage = null;

    /**
     * Storage key.
     */
    private readonly string $storage_key;

    /**
     * Messages.
     *
     * @var array<int, array{type: MessageType, message: string}>
     */
    private array $messages = [];

    public function __construct(string $storage_key)
    {
        parent::__construct();

        $this->storage_key = $storage_key;
    }

    public function __serialize(): array
    {
        $data = [];

        foreach (\get_object_vars($this) as $property => $value) {
            if ($property === 'progress_storage') {
                continue; // the storage service must not be stored in the storage file
            }

            $data[$property] = $value;
        }

        return $data;
    }

    public function setProgressStorage(ProgressStorage $progress_storage): void
    {
        $this->progress_storage = $progress_storage;
    }

    public function addMessage(MessageType $type, string $message): void
    {
        $this->messages[] = [
            'type'      => $type,
            'message'   => $message,
        ];

        $this->store();
    }

    protected function update(): void
    {
        if (!($this->progress_storage instanceof ProgressStorage)) {
            throw new RuntimeException('Progress indicator cannot be updated from a read-only context.');
        }

        $this->store();
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
     * Get the storage key.
     */
    public function getStorageKey(): string
    {
        return $this->storage_key;
    }

    /**
     * Store the indicator into the storage.
     */
    private function store(): void
    {
        $this->progress_storage->save($this);
    }
}
