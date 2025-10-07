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

use CommonDBConnexity;
use CommonDBTM;
use Config;
use DBmysql;
use Glpi\Message\MessageType;
use Glpi\Progress\AbstractProgressIndicator;
use Glpi\RichText\RichText;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use Throwable;

use function Safe\json_decode;
use function Safe\preg_replace;
use function Safe\strtotime;

abstract class AbstractPluginMigration
{
    use LoggerAwareTrait;

    /**
     * Progress indicator.
     */
    protected DBmysql $db;

    /**
     * Progress indicator.
     */
    protected ?AbstractProgressIndicator $progress_indicator = null;

    /**
     * Current execution results.
     */
    protected PluginMigrationResult $result;

    /**
     * Mapping between plugin items and GLPI core items.
     *
     * @var array<class-string<CommonDBTM>, array<int, array{itemtype: class-string<CommonDBTM>, items_id: int}>>
     */
    private array $target_items_mapping = [];

    public function __construct(DBmysql $db)
    {
        $this->db = $db;
    }

    abstract protected function getHasBeenExecutedConfigurationKey(): string;

    /**
     * Used to know if there is some potential data to migrate.
     * Not all tables need to be listed, just the ones that indicate that data
     * exist for this plugin.
     */
    abstract protected function getMainPluginTables(): array;

    final public function hasBeenExecuted(): bool
    {
        global $CFG_GLPI;

        return (bool) ($CFG_GLPI[$this->getHasBeenExecutedConfigurationKey()] ?? false);
    }

    final public function hasPluginData(): bool
    {
        foreach ($this->getMainPluginTables() as $table) {
            if ($this->db->tableExists($table) && countElementsInTable($table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Defines the progress indicator.
     */
    final public function setProgressIndicator(AbstractProgressIndicator $progress_indicator): void
    {
        $this->progress_indicator = $progress_indicator;
    }

    /**
     * Execute (or simulate) the plugin migration.
     *
     * @param bool $simulate Whether the process should be simulated or actually executed.
     * @return PluginMigrationResult
     */
    final public function execute(bool $simulate = false): PluginMigrationResult
    {
        $this->result = new PluginMigrationResult();

        $fully_processed = false;
        try {
            $need_rollback_on_throw = false;

            if ($this->validatePrerequisites()) {
                $this->db->beginTransaction();
                $need_rollback_on_throw = true;

                $fully_processed = $this->processMigration();

                if ($simulate === false && $fully_processed === true) {
                    $this->db->commit();
                } else {
                    $this->db->rollBack();
                }
            } else {
                $this->result->addMessage(MessageType::Error, __('Migration cannot be done.'));
            }
        } catch (Throwable $e) {
            $this->result->addMessage(
                MessageType::Error,
                $e instanceof MigrationException ? $e->getLocalizedMessage() : __('An unexpected error occurred')
            );

            $this->logger?->error($e->getMessage(), context: ['exception' => $e]);

            if ($need_rollback_on_throw) {
                $this->db->rollBack();
            }
        }

        $this->result->setFullyProcessed($fully_processed);
        Config::setConfigurationValues('core', [
            $this->getHasBeenExecutedConfigurationKey() => 1,
        ]);
        return $this->result;
    }

    /**
     * Check that the given fields exists in database.
     *
     * @param array<string, array<int , string>> $required_fields
     *      List of required fields, in the following format:
     *          [
     *              'tablename1' => ['id', 'name', ...],
     *              'tablename2' => ['id', ...],
     *          ]
     */
    final protected function checkDbFieldsExists(array $required_fields): bool
    {
        $missing_tables = [];
        $missing_fields = [];
        foreach ($required_fields as $table => $fields) {
            if (!$this->db->tableExists($table)) {
                $missing_tables[] = $table;
                continue;
            }

            foreach ($fields as $field) {
                if (!$this->db->fieldExists($table, $field)) {
                    $missing_fields[] = $table . '.' . $field;
                }
            }
        }

        if (\count($missing_tables) > 0 || \count($missing_fields) > 0) {
            $this->result->addMessage(
                MessageType::Error,
                __('The database structure does not contain all the data required for migration.')
            );

            if (\count($missing_tables) > 0) {
                $this->result->addMessage(
                    MessageType::Error,
                    sprintf(
                        __('The following database tables are missing: %s.'),
                        '`' . implode('`, `', $missing_tables) . '`'
                    )
                );
            }
            if (\count($missing_fields) > 0) {
                $this->result->addMessage(
                    MessageType::Error,
                    sprintf(
                        __('The following database fields are missing: %s.'),
                        '`' . implode('`, `', $missing_fields) . '`'
                    )
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Count records from a table with optional conditions
     *
     * @param string $table Table name
     * @param array $conditions Optional WHERE conditions
     *
     * @return int Count of records
     */
    protected function countRecords(string $table, array $conditions = []): int
    {
        $criteria = [
            'FROM'  => $table,
            'COUNT' => 'cpt',
        ];

        if ($conditions !== []) {
            $criteria['WHERE'] = $conditions;
        }

        return $this->db->request($criteria)->current()['cpt'];
    }

    /**
     * Import a plugin item.
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype             Target itemtype.
     * @param array $input                          Creation/update input.
     * @param array|null $reconciliation_criteria   Fields used to reconciliate input with a potential existing item.
     * @param array $options                        Options to use during add/update operation.
     *
     * @return T    The created/reused item.
     */
    final protected function importItem(
        string $itemtype,
        array $input,
        ?array $reconciliation_criteria = null,
        array $options = []
    ): CommonDBTM {
        $item = \getItemForItemtype($itemtype);
        if ($item === false) {
            throw new RuntimeException(sprintf('Invalid itemtype `%s`.', $itemtype));
        }

        if ($reconciliation_criteria !== null && $item->getFromDBByCrit($reconciliation_criteria)) {
            // Update the corresponding item.
            $input[$itemtype::getIndexName()] = $item->getID();

            if (
                \array_key_exists('date_mod', $input)
                && \array_key_exists('date_mod', $item->fields)
                && strtotime($input['date_mod']) < strtotime($item->fields['date_mod'])
            ) {
                // The item in GLPI has been modified after the last modification of the plugin item.
                // We consider the item from the plugin as outdated and preserve the GLPI item.
                $this->result->markItemAsReused($itemtype, $item->getID());
                $this->result->addMessage(
                    MessageType::Debug,
                    sprintf(
                        __('%s "%s" (%d) is most recent on GLPI side, its update has been skipped.'),
                        $itemtype::getTypeName(1),
                        $item->getFriendlyName() ?: NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
                return $item;
            }

            // Check if at least one field is updated.
            $has_updates = false;
            foreach ($input as $fieldname => $value) {
                if (!\array_key_exists($fieldname, $item->fields)) {
                    // field is not a real field, we cannot compare the value so it is preferable to trigger the update
                    $has_updates = true;
                    break;
                }

                if (
                    \is_array($value)
                    && \is_string($item->fields[$fieldname])
                    && \json_validate($item->fields[$fieldname])
                    && json_decode($item->fields[$fieldname], associative: true) === $value
                ) {
                    // Passed value is an array identical to the JSON encoded value present in DB.
                    // We consider that the field is not updated.
                    continue;
                }

                if ($value === $item->fields[$fieldname]) {
                    // The value is already up-to-date.
                    continue;
                }

                $has_updates = true;
                break;
            }
            if (!$has_updates) {
                $this->result->markItemAsReused($itemtype, $item->getID());
                $this->result->addMessage(
                    MessageType::Debug,
                    sprintf(
                        __('%s "%s" (%d) is already up-to-date, its update has been skipped.'),
                        $itemtype::getTypeName(1),
                        $item->getFriendlyName() ?: NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
                return $item;
            }

            $updated = $item->update($input, options: $options);
            $this->addSessionMessagesToResult();
            if ($updated === false) {
                throw new MigrationException(
                    sprintf(
                        __('Unable to update %s "%s" (%d).'),
                        $itemtype::getTypeName(1),
                        $item->getFriendlyName() ?: NOT_AVAILABLE,
                        $item->getID(),
                    ),
                    'Update operation failed.'
                );
            }

            $this->result->markItemAsReused($itemtype, $item->getID());
            $this->result->addMessage(
                MessageType::Debug,
                sprintf(
                    __('%s "%s" (%d) has been updated.'),
                    $itemtype::getTypeName(1),
                    $item->getFriendlyName() ?: NOT_AVAILABLE,
                    $item->getID(),
                )
            );

            return $item;
        }

        // Create a new item.
        $created = $item->add($input, options: $options);
        $this->addSessionMessagesToResult();
        if ($created === false) {
            throw new MigrationException(
                sprintf(
                    __('Unable to create %s "%s".'),
                    $itemtype::getTypeName(1),
                    $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                ),
                'Add operation failed.'
            );
        }

        $this->result->markItemAsCreated($itemtype, $item->getID());
        $this->result->addMessage(
            MessageType::Debug,
            sprintf(
                __('%s "%s" (%d) has been created.'),
                $itemtype::getTypeName(1),
                $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                $item->getID(),
            )
        );

        return $item;
    }

    /**
     * Copy the items found using the given criteria, after application of the given replacements.
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param array<mixed, mixed> $where
     * @param array<int, array{field: string, from: mixed, to: mixed}> $replacements
     * @param bool $disable_unicity_check
     */
    final protected function copyItems(string $itemtype, array $where, array $replacements, bool $disable_unicity_check = false): void
    {
        if (!\is_a($itemtype, CommonDBTM::class, true)) {
            throw new InvalidArgumentException(sprintf('`%s` is not a valid `%s` class.', $itemtype, CommonDBTM::class));
        }

        $options = [];
        if ($disable_unicity_check) {
            $options['disable_unicity_check'] = true;
        }

        $offset = 0;
        $limit  = 500;
        do {
            $iterator = $this->db->request([
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => $where,
                'OFFSET' => $offset,
                'LIMIT'  => $limit,
            ]);

            foreach ($iterator as $related_item_data) {
                $origin_id = $related_item_data[$itemtype::getIndexName()] ?? '?';

                unset($related_item_data[$itemtype::getIndexName()]);

                foreach ($replacements as $replacement) {
                    $field = $replacement['field'];
                    $from  = $replacement['from'];
                    $to    = $replacement['to'];

                    if ($related_item_data[$field] === $from) {
                        $related_item_data[$field] = $to;
                    }
                }

                $copied_item = new $itemtype();
                if ($copied_item->getFromDbByCrit($related_item_data)) {
                    // The related item already exists, do not create a new one to prevent duplicates.
                    continue;
                }
                $created = $copied_item->add($related_item_data, $options);
                $this->addSessionMessagesToResult();
                if ($created === false) {
                    throw new MigrationException(
                        sprintf(
                            __('Unable to copy %s "%s".'),
                            $itemtype::getTypeName(1),
                            $related_item_data[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        ),
                        sprintf('Copy operation failed for itemtype `%s` (%s).', $itemtype, $origin_id)
                    );
                }
            }

            $offset += $limit;
        } while ($iterator->count() > 0);
    }

    /**
     * Copy the polymorphic relations related to the given source item and attach them to the given target item.
     *
     * @param class-string<CommonDBTM> $source_itemtype
     * @param int $source_items_id
     * @param class-string<CommonDBTM> $target_itemtype
     * @param int $target_items_id
     */
    final protected function copyPolymorphicConnexityItems(
        string $source_itemtype,
        int $source_items_id,
        string $target_itemtype,
        int $target_items_id
    ): void {
        $polymorphic_column_iterator = $this->db->request(
            [
                'SELECT' => [
                    'table_name AS TABLE_NAME',
                    'column_name AS COLUMN_NAME',
                ],
                'FROM'   => 'information_schema.columns',
                'WHERE'  => [
                    'table_schema' => $this->db->dbdefault,
                    'table_name'   => ['LIKE', 'glpi\_%'],
                    'OR' => [
                        ['column_name'  => 'items_id'],
                        ['column_name'  => ['LIKE', 'items_id_%']],
                    ],
                ],
                'ORDER'  => 'TABLE_NAME',
            ]
        );

        foreach ($polymorphic_column_iterator as $polymorphic_column_data) {
            $table = $polymorphic_column_data['TABLE_NAME'];
            $items_id_field = $polymorphic_column_data['COLUMN_NAME'];
            $itemtype_field = preg_replace('/^items_id/', 'itemtype', $items_id_field);

            $relation_itemtype = \getItemTypeForTable($table);
            if (!is_a($relation_itemtype, CommonDBConnexity::class, true)) {
                // Not a relation class.
                continue;
            }

            if (!$this->db->fieldExists($table, $itemtype_field)) {
                // The `items_id` field exists but the `itemtype` field does not exist.
                // It is not a polymorphic relation.
                continue;
            }

            $this->copyItems(
                $relation_itemtype,
                where: [
                    $itemtype_field => $source_itemtype,
                    $items_id_field => $source_items_id,
                ],
                replacements: [
                    [
                        'field' => $itemtype_field,
                        'from'  => $source_itemtype,
                        'to'    => $target_itemtype,
                    ],
                    [
                        'field' => $items_id_field,
                        'from'  => $source_items_id,
                        'to'    => $target_items_id,
                    ],
                ],
                // Disable unicity checks when copying connexity items.
                // These check may fail considering the source item and the copied item are doubles,
                // but as the source item will no longer be used, it should not be considered as an issue.
                disable_unicity_check: true,
            );
        }
    }

    /**
     * Map the target item with the given source item.
     *
     * @param string                    $source_itemtype Note: may be an itemtype from a deleted or disabled plugin so
     *                                                   it is not safe to assume that this is a class-string<CommonDBTM>
     * @param int                       $source_items_id
     * @param class-string<CommonDBTM> $target_itemtype
     * @param int                       $target_items_id
     *
     * @return void
     */
    final protected function mapItem(
        string $source_itemtype,
        int $source_items_id,
        string $target_itemtype,
        int $target_items_id,
    ): void {
        if (!array_key_exists($source_itemtype, $this->target_items_mapping)) {
            $this->target_items_mapping[$source_itemtype] = [];
        }

        $this->target_items_mapping[$source_itemtype][$source_items_id] = [
            'itemtype' => $target_itemtype,
            'items_id' => $target_items_id,
        ];
    }

    /**
     * Return the GLPI core item specs corresponding to the given plugin item.
     *
     * @return array{itemtype: class-string<CommonDBTM>, items_id: int}|null
     */
    final public function getMappedItemTarget(string $source_itemtype, int $source_items_id): ?array
    {
        if (
            !array_key_exists($source_itemtype, $this->target_items_mapping)
            || !array_key_exists($source_items_id, $this->target_items_mapping[$source_itemtype])
        ) {
            return null;
        }

        return $this->target_items_mapping[$source_itemtype][$source_items_id];
    }

    /**
     * Return the GLPI core items specs corresponding to the given plugin itemtype.
     *
     * @return array<int, array{itemtype: class-string<CommonDBTM>, items_id: int}>
     */
    final public function getMappedItemsForItemtype(string $source_itemtype): array
    {
        if (!array_key_exists($source_itemtype, $this->target_items_mapping)) {
            return [];
        }

        return $this->target_items_mapping[$source_itemtype];
    }

    /**
     * Add the session messages from the session to the result object.
     * Transfered messages will be removed from the session, to prevent output duplicates.
     */
    final protected function addSessionMessagesToResult(): void
    {
        if (!\array_key_exists('MESSAGE_AFTER_REDIRECT', $_SESSION)) {
            return;
        }

        foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'] as $key => $messages) {
            $type = match ($key) {
                ERROR   => MessageType::Error,
                WARNING => MessageType::Warning,
                default => MessageType::Notice,
            };
            foreach ($messages as $message) {
                $this->result->addMessage($type, RichText::getTextFromHtml($message));
            }
        }

        // Clean messages
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    }

    /**
     * Validates the plugin migration prerequisites.
     * If the prerequisites are not validated, the migration will not be processed.
     *
     * @return bool `true` if the prerequisites are valdiated, `false` otherwise.
     */
    abstract protected function validatePrerequisites(): bool;

    /**
     * Process the migation.
     *
     * A database transation is started before calling this method.
     * This transaction will be commited if the migration is not a simulation and is fully processed,
     * otherwise a rollback will be done.
     *
     * @return bool `true` if the migration has been fully processed, `false` otherwise.
     */
    abstract protected function processMigration(): bool;
}
