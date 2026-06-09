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

namespace Glpi\Progress;

use Glpi\Message\MessageType;
use JsonSerializable;
use RuntimeException;
use Safe\DateTimeImmutable;

use function Safe\json_decode;

/**
 * @final
 */
class StoredProgressIndicator extends AbstractProgressIndicator implements JsonSerializable
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

    /**
     * @return array{
     *   storage_key: string,
     *   started_at: string,
     *   updated_at: string,
     *   ended_at: ?string,
     *   failed: bool,
     *   current_step: int,
     *   max_steps: int,
     *   progress_bar_message: string,
     *   messages: list<array{type: value-of<MessageType>, message: string}>,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'storage_key'          => $this->storage_key,
            'started_at'           => $this->started_at->format('c'),
            'updated_at'           => $this->updated_at->format('c'),
            'ended_at'             => $this->ended_at?->format('c'),
            'failed'               => $this->failed,
            'current_step'         => $this->current_step,
            'max_steps'            => $this->max_steps,
            'progress_bar_message' => $this->progress_bar_message,
            'messages'             => \array_map(
                fn(array $message_entry) => ['type' => $message_entry['type']->value, 'message' => $message_entry['message']],
                $this->messages
            ),
        ];
    }

    public static function fromJsonString(string $string): self
    {
        $progress_data = json_decode($string, true);

        $instance = new self($progress_data['storage_key']);

        $instance->started_at           = new DateTimeImmutable($progress_data['started_at']);
        $instance->updated_at           = new DateTimeImmutable($progress_data['updated_at']);
        $instance->ended_at             = $progress_data['ended_at'] !== null ? new DateTimeImmutable($progress_data['ended_at']) : null;
        $instance->failed               = $progress_data['failed'];
        $instance->current_step         = $progress_data['current_step'];
        $instance->max_steps            = $progress_data['max_steps'];
        $instance->progress_bar_message = $progress_data['progress_bar_message'];

        $instance->messages = \array_map(
            fn(array $message_entry) => ['type' => MessageType::from($message_entry['type']), 'message' => $message_entry['message']],
            $progress_data['messages']
        );

        return $instance;
    }
}
