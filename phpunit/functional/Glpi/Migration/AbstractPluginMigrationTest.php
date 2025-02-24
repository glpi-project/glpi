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

namespace tests\units\Glpi\Migration;

use Computer;
use DBmysql;
use DbTestCase;
use Entity;
use Glpi\Message\MessageType;
use Glpi\Migration\AbstractPluginMigration;
use Glpi\Migration\PluginMigrationResult;
use Monitor;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class AbstractPluginMigrationTest extends DbTestCase
{
    public function testExecuteWithUnvalidatedPrerequisites(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                $this->result->addMessage(MessageType::Error, 'Appliances plugin table "glpi_plugins_myplugin_items" is missing.');
                return false;
            }

            protected function processMigration(): bool
            {
                throw new \RuntimeException('This method is not supposed to be called when the prerequisites are not validated.');
                return true;
            }
        };

        // Act
        $result = $instance->execute();

        // Assert
        $this->assertFalse($result->isFullyProcessed());
        $this->assertTrue($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Error,
                'message' => 'Appliances plugin table "glpi_plugins_myplugin_items" is missing.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testExecuteWithValidatePrerequisitesException(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                throw new \RuntimeException('Something went wrong during prerequisites validation.');
                return true;
            }

            protected function processMigration(): bool
            {
                throw new \RuntimeException('This method is not supposed to be called when the prerequisites are not validated.');
            }
        };

        // Act
        $result = $instance->execute();

        // Assert
        $this->assertFalse($result->isFullyProcessed());
        $this->assertTrue($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Error,
                'message' => 'An unexpected error occured.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testExecuteOk(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $db->expects($this->once())->method('beginTransaction'); // A transtation will be started ...
        $db->expects($this->once())->method('commit'); // ... and commited.

        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                return true;
            }

            protected function processMigration(): bool
            {
                // ... do something
                $this->result->addMessage(MessageType::Success, 'All data has been imported.');
                return true;
            }
        };

        // Act
        $result = $instance->execute();

        // Assert
        $this->assertTrue($result->isFullyProcessed());
        $this->assertFalse($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Success,
                'message' => 'All data has been imported.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testExecuteWithProcessMigrationException(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $db->method('inTransaction')->willReturn(true);
        $db->expects($this->once())->method('beginTransaction'); // A transtation will be started ...
        $db->expects($this->once())->method('rollBack'); // ... but a roll-back will be done.
        $db->expects($this->never())->method('commit');

        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                $this->result->addMessage(MessageType::Notice, 'Plugin\'s data can be imported.');
                return true;
            }

            protected function processMigration(): bool
            {
                throw new \RuntimeException('Something went wrong during migration processing.');
                return true;
            }
        };

        // Act
        $result = $instance->execute();

        // Assert
        $this->assertFalse($result->isFullyProcessed());
        $this->assertTrue($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Notice,
                'message' => 'Plugin\'s data can be imported.',
            ],
            [
                'type' => MessageType::Error,
                'message' => 'An unexpected error occured.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testExecuteSimulationOK(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $db->expects($this->once())->method('beginTransaction'); // A transtation will be started ...
        $db->expects($this->once())->method('rollBack'); // ... but a roll-back will be done.
        $db->expects($this->never())->method('commit');

        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                return true;
            }

            protected function processMigration(): bool
            {
                // ... do something
                $this->result->addMessage(MessageType::Success, 'The migration simulation succeed.');
                return true;
            }
        };

        // Act
        $result = $instance->execute(simulate: true);

        // Assert
        $this->assertTrue($result->isFullyProcessed());
        $this->assertFalse($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Success,
                'message' => 'The migration simulation succeed.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testExecuteSimulationWithProcessMigrationException(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $db->method('inTransaction')->willReturn(true);
        $db->expects($this->once())->method('beginTransaction'); // A transtation will be started ...
        $db->expects($this->once())->method('rollBack'); // ... but a roll-back will be done.
        $db->expects($this->never())->method('commit');

        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                $this->result->addMessage(MessageType::Notice, 'Plugin\'s data can be imported.');
                return true;
            }

            protected function processMigration(): bool
            {
                throw new \RuntimeException('Something went wrong during migration processing.');
                return true;
            }
        };

        // Act
        $result = $instance->execute(simulate: true);

        // Assert
        $this->assertFalse($result->isFullyProcessed());
        $this->assertTrue($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Notice,
                'message' => 'Plugin\'s data can be imported.',
            ],
            [
                'type' => MessageType::Error,
                'message' => 'An unexpected error occured.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testCheckDbFieldsExists(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $db->method('tableExists')->willReturnCallback(function ($table) {
            return match ($table) {
                'glpi_plugins_myplugin_items' => true,
                'glpi_plugins_myplugin_tickets' => true,
                'glpi_plugins_myplugin_bars' => true,
                default => false,
            };
        });
        $db->method('fieldExists')->willReturnCallback(function ($table, $field) {
            return match ($table . '.' . $field) {
                'glpi_plugins_myplugin_items.id' => true,
                'glpi_plugins_myplugin_items.name' => true,
                'glpi_plugins_myplugin_tickets.id' => true,
                'glpi_plugins_myplugin_tickets.name' => true,
                'glpi_plugins_myplugin_tickets.content' => true,
                'glpi_plugins_myplugin_bars.id' => true,
                default => false,
            };
        });

        $instance = $this->getMockBuilder(AbstractPluginMigration::class)
            ->setConstructorArgs([$db, $this->createMock(LoggerInterface::class)])
            ->onlyMethods(['execute', 'validatePrerequisites', 'processMigration'])
            ->getMock();

        $reflected_class = new ReflectionClass(AbstractPluginMigration::class);

        // Act
        $reflected_class->getProperty('result')->setValue($instance, $result = new PluginMigrationResult());
        $return_1 = $this->callPrivateMethod(
            $instance,
            'checkDbFieldsExists',
            [
                'glpi_plugins_myplugin_items' => ['id', 'name', 'items_id', 'plugin_myplugin_categories_id'],
                'glpi_plugins_myplugin_tickets' => ['id', 'name', 'content'],
                'glpi_plugins_myplugin_categories' => ['id', 'name'],
                'glpi_plugins_myplugin_foos' => ['id', 'name'],
                'glpi_plugins_myplugin_bars' => ['id', 'name'],
            ]
        );
        $messages_1 = $result->getMessages();

        $reflected_class->getProperty('result')->setValue($instance, $result = new PluginMigrationResult());
        $return_2 = $this->callPrivateMethod(
            $instance,
            'checkDbFieldsExists',
            [
                'glpi_plugins_myplugin_items' => ['id', 'name'],
                'glpi_plugins_myplugin_tickets' => ['id', 'name', 'content'],
            ]
        );
        $messages_2 = $result->getMessages();

        // Assert
        $this->assertEquals(false, $return_1);
        $expected_messages = [
            [
                'type' => MessageType::Error,
                'message' => 'The database structure does not contain all the data required for migration.',
            ],
            [
                'type' => MessageType::Error,
                'message' => 'The following database tables are missing: `glpi_plugins_myplugin_categories`, `glpi_plugins_myplugin_foos`.',
            ],
            [
                'type' => MessageType::Error,
                'message' => 'The following database fields are missing: `glpi_plugins_myplugin_items.items_id`, `glpi_plugins_myplugin_items.plugin_myplugin_categories_id`, `glpi_plugins_myplugin_bars.name`.',
            ],
        ];
        $this->assertEquals($expected_messages, $messages_1);

        $this->assertEquals(true, $return_2);
        $this->assertEquals([], $messages_2);
    }

    public function testImportItem(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $logger = $this->createMock(LoggerInterface::class);

        $instance = new class ($db, $logger) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                return true;
            }

            protected function processMigration(): bool
            {
                $entity_id = \getItemByTypeName(Entity::class, '_test_root_entity', true);

                // Reconciliation criteria matches an existing item, it should be updated ...
                $this->importItem(
                    Computer::class,
                    [
                        'name' => '_test_pc01',
                        'entities_id' => $entity_id,
                        'comment' => 'Imported from myplugin',
                    ],
                    [
                        'name' => '_test_pc01',
                        'entities_id' => $entity_id,
                    ]
                );

                // ... unless the values are identical
                $this->importItem(
                    Computer::class,
                    [
                        'name' => '_test_pc02',
                        'comment' => 'Comment for computer _test_pc02',
                    ],
                    [
                        'name' => '_test_pc02',
                    ]
                );

                // ... or the plugin item is outdated
                $this->importItem(
                    Computer::class,
                    [
                        'name' => '_test_pc03',
                        'comment' => 'Imported from myplugin',
                        'date_mod' => '2015-12-03 14:56:34',
                    ],
                    [
                        'name' => '_test_pc03',
                    ]
                );

                // Reconciliation criteria does not match an existing item, a new item should be created
                $this->importItem(
                    Computer::class,
                    [
                        'name' => 'a test computer',
                        'entities_id' => $entity_id,
                        'comment' => 'Imported from myplugin',
                    ],
                    [
                        'name' => 'a test computer',
                        'entities_id' => $entity_id,
                    ]
                );

                // Reconciliation criteria does not match an existing item, a new item should be created
                $this->importItem(
                    Computer::class,
                    [
                        'name' => 'another test computer',
                        'entities_id' => $entity_id,
                        'comment' => 'Imported from myplugin',
                    ],
                    [
                        'name' => 'another test computer',
                        'entities_id' => $entity_id,
                    ]
                );

                return true;
            }
        };

        // Act
        $result = $instance->execute(simulate: true);

        $computer_id_1 = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $computer_id_2 = \getItemByTypeName(Computer::class, '_test_pc02', true);
        $computer_id_3 = \getItemByTypeName(Computer::class, '_test_pc03', true);
        $computer_id_4 = \getItemByTypeName(Computer::class, 'a test computer', true);
        $computer_id_5 = \getItemByTypeName(Computer::class, 'another test computer', true);

        // Assert
        $this->assertTrue($result->isFullyProcessed());
        $this->assertFalse($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Debug,
                'message' => sprintf('Computer "_test_pc01" (%d) has been updated.', $computer_id_1),
            ],
            [
                'type' => MessageType::Debug,
                'message' => sprintf('Computer "_test_pc02" (%d) is already up-to-date, its update has been skipped.', $computer_id_2),
            ],
            [
                'type' => MessageType::Debug,
                'message' => sprintf('Computer "_test_pc03" (%d) is most recent on GLPI side, its update has been skipped.', $computer_id_3),
            ],
            [
                'type' => MessageType::Debug,
                'message' => sprintf('Computer "a test computer" (%d) has been created.', $computer_id_4),
            ],
            [
                'type' => MessageType::Debug,
                'message' => sprintf('Computer "another test computer" (%d) has been created.', $computer_id_5),
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());

        $this->assertEquals([Computer::class => [$computer_id_4, $computer_id_5]], $result->getCreatedItemsIds());
        $this->assertEquals([Computer::class => [$computer_id_1, $computer_id_2, $computer_id_3]], $result->getReusedItemsIds());
    }

    public function testMapItem(): void
    {
        // Arrange
        $instance = $this->getMockBuilder(AbstractPluginMigration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute', 'validatePrerequisites', 'processMigration'])
            ->getMock();

        // Act
        $this->callPrivateMethod($instance, 'mapItem', 'PluginMyPluginItem', 1, Computer::class, 3);
        $this->callPrivateMethod($instance, 'mapItem', 'PluginMyPluginItem', 2, Computer::class, 12);
        $this->callPrivateMethod($instance, 'mapItem', 'PluginMyPluginAnotherItem', 1, Monitor::class, 9);

        // Assert
        $this->assertEquals(
            ['itemtype' => Computer::class, 'items_id' => 3],
            $this->callPrivateMethod($instance, 'getMappedItemTarget', 'PluginMyPluginItem', 1)
        );
        $this->assertEquals(
            ['itemtype' => Computer::class, 'items_id' => 12],
            $this->callPrivateMethod($instance, 'getMappedItemTarget', 'PluginMyPluginItem', 2)
        );
        $this->assertEquals(
            ['itemtype' => Monitor::class, 'items_id' => 9],
            $this->callPrivateMethod($instance, 'getMappedItemTarget', 'PluginMyPluginAnotherItem', 1)
        );
        $this->assertEquals(
            null,
            $this->callPrivateMethod($instance, 'getMappedItemTarget', 'PluginMyPluginAnotherItem', 2)
        );
    }
}
