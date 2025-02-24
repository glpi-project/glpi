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
use DBmysql;
use Glpi\Progress\AbstractProgressIndicator;
use Psr\Log\LoggerInterface;
use Glpi\Message\MessageType;

abstract class AbstractPluginMigration
{
    /**
     * Progress indicator.
     */
    protected DBmysql $db;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

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
     * @var array<class-string<\CommonDBTM>, array<int, array{itemtype: class-string<\CommonDBTM>, items_id: int}>>
     */
    private array $target_items_mapping = [];

    public function __construct(DBmysql $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
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
            if ($this->validatePrerequisites()) {
                $this->db->beginTransaction();

                $fully_processed = $this->processMigration();

                if ($simulate === false && $fully_processed === true) {
                    $this->db->commit();
                } else {
                    $this->db->rollBack();
                }
            }
        } catch (\Throwable $e) {
            $this->result->addMessage(MessageType::Error, __('An unexpected error occured.'));

            $this->logger->error($e->getMessage(), context: ['exception' => $e]);

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
        }

        $this->result->setFullyProcessed($fully_processed);

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
     * Import a plugin item.
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype             Target itemtype.
     * @param array $input                          Creation/update input.
     * @param array|null $reconciliation_criteria   Fields used to reconciliate input with a potential existing item.
     *
     * @return T    The created/reused item.
     */
    final protected function importItem(
        string $itemtype,
        array $input,
        ?array $reconciliation_criteria = null
    ): CommonDBTM {
        $item = \getItemForItemtype($itemtype);
        if ($item === false) {
            throw new \RuntimeException(sprintf('Invalid itemtype `%s`.', $itemtype));
        }

        if ($reconciliation_criteria !== null && $item->getFromDBByCrit($reconciliation_criteria)) {
            // Update the corresponding item.
            $input[$itemtype::getIndexName()] = $item->getID();

            if (
                \array_key_exists('date_mod', $input)
                && \array_key_exists('date_mod', $item->fields)
                && \strtotime($input['date_mod']) < \strtotime($item->fields['date_mod'])
            ) {
                // The item in GLPI has been modified after the last modification of the plugin item.
                // We consider the item from the plugin as outdated and preserve the GLPI item.
                $this->result->markItemAsReused($itemtype, $item->getID());
                $this->result->addMessage(
                    MessageType::Debug,
                    sprintf(
                        __('%s "%s" (%d) is most recent on GLPI side, its update has been skipped.'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
                return $item;
            }

            // Check if at least one field is updated.
            $has_updates = false;
            foreach ($input as $fieldname => $value) {
                if (
                    // field is not a real field, we cannot compare the value so it is preferable to trigger the update
                    !\array_key_exists($fieldname, $item->fields)
                    // the input value is not identical to the current value, we must trigger the update
                    || $value !== $item->fields[$fieldname]
                ) {
                    $has_updates = true;
                    break;
                }
            }
            if (!$has_updates) {
                $this->result->markItemAsReused($itemtype, $item->getID());
                $this->result->addMessage(
                    MessageType::Debug,
                    sprintf(
                        __('%s "%s" (%d) is already up-to-date, its update has been skipped.'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
                return $item;
            }

            if ($item->update($input) === false) {
                $this->result->addMessage(
                    MessageType::Error,
                    sprintf(
                        __('Unable to update %s "%s" (%d).'),
                        $itemtype::getTypeName(1),
                        $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                        $item->getID(),
                    )
                );
                throw new \RuntimeException('An error occured during the item update.');
            }

            $this->result->markItemAsReused($itemtype, $item->getID());
            $this->result->addMessage(
                MessageType::Debug,
                sprintf(
                    __('%s "%s" (%d) has been updated.'),
                    $itemtype::getTypeName(1),
                    $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                    $item->getID(),
                )
            );

            return $item;
        }

        // Create a new item.
        if ($item->add($input) === false) {
            $this->result->addMessage(
                MessageType::Error,
                sprintf(
                    __('Unable to create %s "%s".'),
                    $itemtype::getTypeName(1),
                    $input[$itemtype::getNameField()] ?? NOT_AVAILABLE,
                )
            );
            throw new \RuntimeException('An error occured during the item creation.');
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
     * Map the target item with the given source item.
     *
     * @param class-string<\CommonDBTM> $source_itemtype
     * @param int                       $source_items_id
     * @param class-string<\CommonDBTM> $target_itemtype
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
     * @return array{itemtype: class-string<\CommonDBTM>, items_id: int}
     */
    final protected function getMappedItemTarget(string $source_itemtype, int $source_items_id): ?array
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
