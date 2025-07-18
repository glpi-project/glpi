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
use DropdownTranslation;
use Entity;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Message\MessageType;
use Glpi\Migration\AbstractPluginMigration;
use Glpi\Migration\PluginMigrationResult;
use Infocom;
use Monitor;
use ReflectionClass;
use TaskCategory;

class AbstractPluginMigrationTest extends DbTestCase
{
    public function testExecuteWithUnvalidatedPrerequisites(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);

        $instance = new class ($db) extends AbstractPluginMigration {
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

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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
            [
                'type' => MessageType::Error,
                'message' => 'Migration cannot be done.',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
    }

    public function testExecuteWithValidatePrerequisitesException(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);

        $instance = new class ($db) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                throw new \RuntimeException('Something went wrong during prerequisites validation.');
                return true;
            }

            protected function processMigration(): bool
            {
                throw new \RuntimeException('This method is not supposed to be called when the prerequisites are not validated.');
            }

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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
                'message' => 'An unexpected error occurred',
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

        $instance = new class ($db) extends AbstractPluginMigration {
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

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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
        $db->expects($this->once())->method('beginTransaction'); // A transtation will be started ...
        $db->expects($this->once())->method('rollBack'); // ... but a roll-back will be done.
        $db->expects($this->never())->method('commit');

        $instance = new class ($db) extends AbstractPluginMigration {
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

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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
                'message' => 'An unexpected error occurred',
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

        $instance = new class ($db) extends AbstractPluginMigration {
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

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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
        $db->expects($this->once())->method('beginTransaction'); // A transtation will be started ...
        $db->expects($this->once())->method('rollBack'); // ... but a roll-back will be done.
        $db->expects($this->never())->method('commit');

        $instance = new class ($db) extends AbstractPluginMigration {
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

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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
                'message' => 'An unexpected error occurred',
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
            ->setConstructorArgs([$db])
            ->onlyMethods(['execute', 'validatePrerequisites', 'processMigration', 'getHasBeenExecutedConfigurationKey', 'getMainPluginTables'])
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

        $instance = new class ($db) extends AbstractPluginMigration {
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

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
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

    public function testImportItemWithErrorAndSessionMessage(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);

        $instance = new class ($db) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                return true;
            }

            protected function processMigration(): bool
            {
                $this->importItem(
                    AssetDefinition::class,
                    [
                        'system_name' => 'test',
                        'label'       => 'Test',
                    ]
                );
                $this->importItem(
                    AssetDefinition::class,
                    [
                        'system_name' => 'test_',
                        'label'       => 'Test with an invalid name',
                    ]
                );

                return true;
            }

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
            }
        };

        // Act
        $result = $instance->execute(simulate: true);

        $definition_id = \getItemByTypeName(AssetDefinition::class, 'Test', true);

        // Assert
        $this->assertFalse($result->isFullyProcessed());
        $this->assertTrue($result->hasErrors());
        $expected_messages = [
            [
                'type' => MessageType::Debug,
                'message' => sprintf('Asset definition "Test" (%d) has been created.', $definition_id),
            ],
            [
                'type' => MessageType::Error,
                'message' => 'The following field has an incorrect value: "System name".',
            ],
            [
                'type' => MessageType::Error,
                'message' => 'Unable to create Asset definition "Test with an invalid name".',
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());

        $this->assertEquals([AssetDefinition::class => [$definition_id]], $result->getCreatedItemsIds());
        $this->assertEquals([], $result->getReusedItemsIds());
    }

    public function testCopyItems(): void
    {
        // Arrange
        $definition = $this->initDropdownDefinition('MyCustomCategory');

        $db = $this->createMock(DBmysql::class);
        $db->method('request')->willReturnCallback(function ($criteria) {
            if (($criteria['OFFSET'] ?? 0) > 0) {
                return new \ArrayIterator([]);
            }

            $cat_1_id = \getItemByTypeName(TaskCategory::class, '_cat_1', true);

            return new \ArrayIterator([
                [
                    'items_id'   => $cat_1_id,
                    'itemtype'   => TaskCategory::class,
                    'language'   => 'fr_FR',
                    'field'      => 'name',
                    'value'      => 'FR - _cat_1',
                ],
                [
                    'items_id'   => $cat_1_id,
                    'itemtype'   => TaskCategory::class,
                    'language'   => 'es_SP',
                    'field'      => 'name',
                    'value'      => 'ES - _cat_1',
                ],
            ]);
        });

        $instance = new class ($db) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                return true;
            }

            protected function processMigration(): bool
            {
                $definition = \getItemByTypeName(DropdownDefinition::class, 'MyCustomCategory');

                $my_imported_cat = $this->importItem(
                    $definition->getDropdownClassName(),
                    [
                        'name' => 'Test category',
                    ]
                );

                $cat_1_id = \getItemByTypeName(TaskCategory::class, '_cat_1', true);

                $this->copyItems(
                    DropdownTranslation::class,
                    where: [
                        'itemtype' => TaskCategory::class,
                        'items_id' => $cat_1_id,
                    ],
                    replacements: [
                        [
                            'field' => 'itemtype',
                            'from'  => TaskCategory::class,
                            'to'    => $definition->getDropdownClassName(),
                        ],
                        [
                            'field' => 'items_id',
                            'from'  => $cat_1_id,
                            'to'    => $my_imported_cat->getID(),
                        ],
                    ]
                );

                return true;
            }

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
            }
        };

        // Act
        $result = $instance->execute(simulate: true);

        // Assert
        $custom_cat = \getItemByTypeName($definition->getDropdownClassName(), 'Test category');

        $expected_messages = [
            [
                'type' => MessageType::Debug,
                'message' => sprintf('MyCustomCategory "Test category" (%d) has been created.', $custom_cat->getID()),
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
        $this->assertTrue($result->isFullyProcessed());
        $this->assertFalse($result->hasErrors());

        $copied_translations = \array_map(
            static function (array $entry): array {
                unset($entry['id']);
                return $entry;
            },
            \getAllDataFromTable(
                DropdownTranslation::getTable(),
                ['itemtype' => $custom_cat::class, 'items_id' => $custom_cat->getID()]
            )
        );

        $this->assertEquals(
            [
                [
                    'items_id' => $custom_cat->getID(),
                    'itemtype' => $custom_cat::class,
                    'language' => 'es_SP',
                    'field'    => 'name',
                    'value'    => 'ES - _cat_1',
                ],
                [
                    'items_id' => $custom_cat->getID(),
                    'itemtype' => $custom_cat::class,
                    'language' => 'fr_FR',
                    'field'    => 'name',
                    'value'    => 'FR - _cat_1',
                ],
            ],
            \array_values($copied_translations)
        );
    }

    public function testCopyPolymorphicConnexityItems(): void
    {
        // Arrange
        $definition = $this->initAssetDefinition(
            'MyCustomAsset',
            capacities: [
                new Capacity(name: HasInfocomCapacity::class),
            ]
        );

        $db = $this->createMock(DBmysql::class);
        $db->method('request')->willReturnCallback(function ($criteria) {
            if (($criteria['FROM'] ?? null) === 'information_schema.columns') {
                return new \ArrayIterator([
                    [
                        'TABLE_NAME'  => 'glpi_infocoms',
                        'COLUMN_NAME' => 'items_id',
                    ],
                    [
                        'TABLE_NAME'  => 'glpi_contracts_items',
                        'COLUMN_NAME' => 'items_id',
                    ],
                ]);
            }

            if (($criteria['OFFSET'] ?? 0) > 0) {
                return new \ArrayIterator([]);
            }

            $computer_1_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
            $computer_2_id = \getItemByTypeName(Computer::class, '_test_pc02', true);

            if (($criteria['FROM'] ?? null) === 'glpi_infocoms') {
                if ($criteria['WHERE']['items_id'] === $computer_1_id) {
                    return new \ArrayIterator([
                        [
                            'itemtype'          => Computer::class,
                            'items_id'          => $computer_1_id,
                            'warranty_date'     => '2024-12-04',
                            'warranty_duration' => 3,
                        ],
                    ]);
                } else {
                    return new \ArrayIterator([
                        [
                            'itemtype'          => Computer::class,
                            'items_id'          => $computer_2_id,
                            'warranty_date'     => '2025-01-17',
                            'warranty_duration' => 12,
                        ],
                    ]);
                }
            }

            if (($criteria['FROM'] ?? null) === 'glpi_contracts_items') {
                if ($criteria['WHERE']['items_id'] === $computer_1_id) {
                    return new \ArrayIterator([
                        [
                            'itemtype'     => Computer::class,
                            'items_id'     => $computer_1_id,
                            'contracts_id' => 3,
                        ],
                        [
                            'itemtype'     => Computer::class,
                            'items_id'     => $computer_1_id,
                            'contracts_id' => 9,
                        ],
                    ]);
                } else {
                    return new \ArrayIterator([
                        [
                            'itemtype'     => Computer::class,
                            'items_id'     => $computer_2_id,
                            'contracts_id' => 5,
                        ],
                    ]);
                }
            }

            return new \ArrayIterator([]);
        });
        $db->method('fieldExists')->willReturnCallback(function ($table, $field) {
            return match ($table . '.' . $field) {
                'glpi_infocoms.itemtype' => true,
                'glpi_contracts_items.itemtype' => true,
                default => false,
            };
        });

        $instance = new class ($db) extends AbstractPluginMigration {
            protected function validatePrerequisites(): bool
            {
                return true;
            }

            protected function processMigration(): bool
            {
                $definition = \getItemByTypeName(AssetDefinition::class, 'MyCustomAsset');

                $my_asset_1 = $this->importItem(
                    $definition->getAssetClassName(),
                    [
                        'name'        => 'Test asset 1',
                        'entities_id' => 0,
                    ]
                );
                $computer_1_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
                $this->copyPolymorphicConnexityItems(Computer::class, $computer_1_id, $my_asset_1::class, $my_asset_1->getID());

                $my_asset_2 = $this->importItem(
                    $definition->getAssetClassName(),
                    [
                        'name'        => 'Test asset 2',
                        'entities_id' => 0,
                    ]
                );
                $computer_2_id = \getItemByTypeName(Computer::class, '_test_pc02', true);
                $this->copyPolymorphicConnexityItems(Computer::class, $computer_2_id, $my_asset_2::class, $my_asset_2->getID());

                return true;
            }

            protected function getHasBeenExecutedConfigurationKey(): string
            {
                return 'config';
            }

            protected function getMainPluginTables(): array
            {
                return ['table'];
            }
        };

        // Act
        global $PHPLOGGER;
        $instance->setLogger($PHPLOGGER);
        $result = $instance->execute(simulate: true);

        // Assert
        $my_asset_1 = \getItemByTypeName($definition->getAssetClassName(), 'Test asset 1');
        $my_asset_2 = \getItemByTypeName($definition->getAssetClassName(), 'Test asset 2');

        $expected_messages = [
            [
                'type' => MessageType::Debug,
                'message' => sprintf('MyCustomAsset "Test asset 1" (%d) has been created.', $my_asset_1->getID()),
            ],
            [
                'type' => MessageType::Debug,
                'message' => sprintf('MyCustomAsset "Test asset 2" (%d) has been created.', $my_asset_2->getID()),
            ],
        ];
        $this->assertEquals($expected_messages, $result->getMessages());
        $this->assertTrue($result->isFullyProcessed());
        $this->assertFalse($result->hasErrors());

        $copied_infocom = \array_map(
            static function (array $entry): array {
                return [
                    'itemtype'          => $entry['itemtype'],
                    'items_id'          => $entry['items_id'],
                    'warranty_date'     => $entry['warranty_date'],
                    'warranty_duration' => $entry['warranty_duration'],
                ];
            },
            \getAllDataFromTable(
                Infocom::getTable(),
                ['itemtype' => $definition->getAssetClassName()]
            )
        );

        $this->assertEquals(
            [
                [
                    'items_id'          => $my_asset_1->getID(),
                    'itemtype'          => $my_asset_1::class,
                    'warranty_date'     => '2024-12-04',
                    'warranty_duration' => 3,
                ],
                [
                    'items_id'          => $my_asset_2->getID(),
                    'itemtype'          => $my_asset_2::class,
                    'warranty_date'     => '2025-01-17',
                    'warranty_duration' => 12,
                ],
            ],
            \array_values($copied_infocom)
        );

        $copied_contract_relations = \array_map(
            static function (array $entry): array {
                unset($entry['id']);
                return $entry;
            },
            \getAllDataFromTable(
                \Contract_Item::getTable(),
                ['itemtype' => $definition->getAssetClassName()]
            )
        );

        $this->assertEquals(
            [
                [
                    'itemtype'     => $my_asset_1::class,
                    'items_id'     => $my_asset_1->getID(),
                    'contracts_id' => 3,
                ],
                [
                    'itemtype'     => $my_asset_1::class,
                    'items_id'     => $my_asset_1->getID(),
                    'contracts_id' => 9,
                ],
                [
                    'itemtype'     => $my_asset_2::class,
                    'items_id'     => $my_asset_2->getID(),
                    'contracts_id' => 5,
                ],
            ],
            \array_values($copied_contract_relations)
        );
    }

    public function testMapItem(): void
    {
        // Arrange
        $instance = $this->getMockBuilder(AbstractPluginMigration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute', 'validatePrerequisites', 'processMigration', 'getHasBeenExecutedConfigurationKey', 'getMainPluginTables'])
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

    public function testCountRecords(): void
    {
        // Arrange
        $db = $this->createMock(DBmysql::class);
        $db->method('request')->willReturnCallback(function ($criteria) {
            $result = ['cpt' => 0]; // Default to 0

            if ($criteria['FROM'] === 'test_table') {
                if (!isset($criteria['WHERE'])) {
                    $result = ['cpt' => 42]; // Table without conditions
                } elseif (isset($criteria['WHERE']['condition']) && $criteria['WHERE']['condition'] === 'value') {
                    $result = ['cpt' => 15]; // With specific condition
                } elseif (isset($criteria['WHERE']['other_condition']) && $criteria['WHERE']['other_condition'] === 'other_value') {
                    $result = ['cpt' => 27]; // With different condition
                }
            }
            // Empty table always returns 0
            if ($criteria['FROM'] === 'empty_table') {
                $result = ['cpt' => 0];
            }

            return new \ArrayIterator([
                $result,
            ]);
        });

        $instance = $this->getMockBuilder(AbstractPluginMigration::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['validatePrerequisites', 'processMigration', 'getHasBeenExecutedConfigurationKey', 'getMainPluginTables'])
            ->getMock();

        // Act
        $count_without_conditions = $this->callPrivateMethod(
            $instance,
            'countRecords',
            'test_table'
        );

        $count_with_condition1 = $this->callPrivateMethod(
            $instance,
            'countRecords',
            'test_table',
            ['condition' => 'value']
        );

        $count_with_condition2 = $this->callPrivateMethod(
            $instance,
            'countRecords',
            'test_table',
            ['other_condition' => 'other_value']
        );

        $count_empty_table = $this->callPrivateMethod(
            $instance,
            'countRecords',
            'empty_table'
        );

        // Assert
        $this->assertEquals(42, $count_without_conditions);
        $this->assertEquals(15, $count_with_condition1);
        $this->assertEquals(27, $count_with_condition2);
        $this->assertEquals(0, $count_empty_table);
    }
}
