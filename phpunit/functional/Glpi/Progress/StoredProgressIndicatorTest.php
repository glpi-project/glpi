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

namespace tests\units\Glpi\Log;

use DateTimeImmutable;
use Glpi\Message\MessageType;
use Glpi\Progress\ProgressStorage;
use Glpi\Progress\StoredProgressIndicator;
use GLPITestCase;

class StoredProgressIndicatorTest extends GLPITestCase
{
    public function testConstructor(): void
    {
        // Arrange
        $storage_key = $this->getUniqueString();

        $storage = $this->createMock(ProgressStorage::class);

        $date = new DateTimeImmutable();

        // Act
        $instance = new StoredProgressIndicator($storage_key);
        $instance->setProgressStorage($storage);

        // Assert
        $this->assertEquals($instance->getStartedAt(), $instance->getUpdatedAt());

        $this->assertGreaterThanOrEqual($date, $instance->getStartedAt());
        $this->assertLessThanOrEqual(new DateTimeImmutable(), $instance->getStartedAt());

        $this->assertGreaterThanOrEqual($date, $instance->getUpdatedAt());
        $this->assertLessThanOrEqual(new DateTimeImmutable(), $instance->getUpdatedAt());

        $this->assertEquals($storage_key, $instance->getStorageKey());
    }

    public function testMessagesAccessors(): void
    {
        // Arrange
        $storage = $this->createMock(ProgressStorage::class);

        $instance = new StoredProgressIndicator(
            $this->getUniqueString()
        );
        $instance->setProgressStorage($storage);

        // Saving into the storage will be triggered during each message adding operation
        $storage->expects($this->exactly(7))->method('save');

        // Act
        $instance->addMessage(MessageType::Error, 'An unexpected error occurred');
        $instance->addMessage(MessageType::Warning, 'Invalid foo has been ignored.');
        $instance->addMessage(MessageType::Debug, 'Some debug info...');
        $instance->addMessage(MessageType::Debug, 'Some other debug info...');
        $instance->addMessage(MessageType::Error, 'Another unexpected error occurred.');
        $instance->addMessage(MessageType::Notice, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
        $instance->addMessage(MessageType::Success, 'Operation successfully done.');

        // Assert
        $expected_messages_1 = [
            [
                'type'      => MessageType::Error,
                'message'   => 'An unexpected error occurred',
            ],
            [
                'type'      => MessageType::Warning,
                'message'   => 'Invalid foo has been ignored.',
            ],
            [
                'type'      => MessageType::Debug,
                'message'   => 'Some debug info...',
            ],
            [
                'type'      => MessageType::Debug,
                'message'   => 'Some other debug info...',
            ],
            [
                'type'      => MessageType::Error,
                'message'   => 'Another unexpected error occurred.',
            ],
            [
                'type'      => MessageType::Notice,
                'message'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            ],
            [
                'type'      => MessageType::Success,
                'message'   => 'Operation successfully done.',
            ],
        ];
        $this->assertEquals($expected_messages_1, $instance->getMessages());
    }

    public function testUpdate(): void
    {
        // Arrange
        $storage = $this->createMock(ProgressStorage::class);

        $instance = new StoredProgressIndicator(
            $this->getUniqueString()
        );
        $instance->setProgressStorage($storage);

        // Saving into the storage will be triggered by the `update` call.
        $storage->expects($this->once())->method('save');

        // Act
        $this->callPrivateMethod($instance, 'update');

        // Assert
        // assertions have been done through the $storage mock
    }
}
