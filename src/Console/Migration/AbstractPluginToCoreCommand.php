<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Console\Migration;

use CommonDBTM;
use Infocom;
use Plugin;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Toolbox;

abstract class AbstractPluginToCoreCommand extends AbstractCommand
{
    /**
     * Error code returned if plugin version or plugin data is invalid.
     *
     * @var integer
     */
    const ERROR_PLUGIN_VERSION_OR_DATA_INVALID = 1;

    /**
     * Error code returned when import failed.
     *
     * @var integer
     */
    const ERROR_PLUGIN_IMPORT_FAILED = 2;

    /**
     * Target items mapping.
     *
     * @var array
     */
    private $target_items_mapping = [];

    /**
     * Matching items mapping.
     * Acts as a local cache to prevent DB queries when matching was found earlier for same criteria.
     *
     * @var array
     */
    private $matching_items_mapping = [];

    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'skip-errors',
            's',
            InputOption::VALUE_NONE,
            __('Do not exit on import errors')
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->checkPlugin();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $no_interaction = $input->getOption('no-interaction');
        if (!$no_interaction) {
            // Ask for confirmation (unless --no-interaction)
            $output->writeln(
                [
                    sprintf(__('You are about to launch migration of "%s" plugin data into GLPI core tables.'), $this->getPluginKey()),
                    __('It is better to make a backup of your existing data before continuing.')
                ],
                OutputInterface::VERBOSITY_QUIET
            );

            $this->askForConfirmation();
        }

        $this->migratePlugin();

        $output->writeln('<info>' . __('Migration done.') . '</info>');
        return 0; // Success
    }

    /**
     * Check that required tables exists and fields are OK for migration.
     *
     * @return void
     */
    protected function checkPlugin(): void
    {
        $required_version = $this->getRequiredMinimalPluginVersion();

        if ($required_version !== null) {
            $plugin = new Plugin();
            if ($plugin->getFromDBbyDir($this->getPluginKey())) {
                if (version_compare($plugin->fields['version'], $required_version, '<=')) {
                    $msg = sprintf(
                        __('Previously installed installed plugin %s version was %s. Minimal version supported by migration is %s.'),
                        $this->getPluginKey(),
                        $plugin->fields['version'],
                        $required_version
                    );
                    throw new \Glpi\Console\Exception\EarlyExitException(
                        '<error>' . $msg . '</error>',
                        self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID
                    );
                }
            } else {
                $msg = sprintf(
                    __('Unable to validate that previously installed plugin %s version was %s.'),
                    $this->getPluginKey(),
                    $required_version
                );
                $this->output->writeln(
                    '<comment>' . $msg . '</comment>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $this->askForConfirmation(false);
            }
        }

        $required_fields = $this->getRequiredDatabasePluginFields();
        $missing_fields = false;
        foreach ($required_fields as $field) {
            if (!preg_match('/^[a-z_]+\.[a-z_]+$/', $field)) {
                trigger_error(sprintf('Invalid format for "%s" value', $field), E_USER_WARNING);
                $missing_fields = true;
                continue;
            }

            list($tablename, $fieldname) = explode('.', $field);
            if (!$this->db->tableExists($tablename) || !$this->db->fieldExists($tablename, $fieldname)) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Plugin database field "%s" is missing.'), $field) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $missing_fields = true;
            }
        }

        if ($missing_fields) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Migration cannot be done.') . '</error>',
                self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID
            );
        }
    }

    /**
     * Handle import error message.
     * Throws a `\Glpi\Console\Exception\EarlyExitException` unless `skip-errors` option is used.
     *
     * @param string            $message
     * @param ProgressBar|null  $progress_bar
     * @param bool              $prevent_exit
     *
     * @return void
     */
    protected function handleImportError($message, ?ProgressBar $progress_bar = null, bool $prevent_exit = false): void
    {
        $skip_errors = $this->input->getOption('skip-errors');

        $verbosity = $skip_errors
            ? OutputInterface::VERBOSITY_NORMAL
            : OutputInterface::VERBOSITY_QUIET;

        $message = '<error>' . $message . '</error>';

        if ($progress_bar instanceof ProgressBar) {
            if (!$skip_errors && !$prevent_exit) {
                $this->output->write(PHP_EOL); // Keep progress bar last state and go to next line
            }

            $this->writelnOutputWithProgressBar(
                $message,
                $progress_bar,
                $verbosity
            );
        } else {
            $this->output->writeln(
                $message,
                $verbosity
            );
        }

        if (!$skip_errors && !$prevent_exit) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Plugin data import failed.') . '</error>',
                self::ERROR_PLUGIN_IMPORT_FAILED
            );
        }
    }

    /**
     * Get ID of existing item that matches given fields, if any.
     *
     * @param string $itemtype
     * @param array $criteria
     *
     * @return int|null
     */
    protected function getMatchingElementId(string $itemtype, array $criteria): ?int
    {
        if (!array_key_exists($itemtype, $this->matching_items_mapping)) {
            $this->matching_items_mapping[$itemtype] = [];
        }

        $criteria_sha = sha1(serialize($criteria));
        if (array_key_exists($criteria_sha, $this->matching_items_mapping[$itemtype])) {
            return $this->matching_items_mapping[$itemtype][$criteria_sha];
        }

        $item = getItemForItemtype($itemtype);
        if ($item === false) {
            return null;
        }

        if ($item->getFromDBByCrit(Toolbox::addslashes_deep($criteria))) {
            $id = $item->getID();
            $this->matching_items_mapping[$itemtype][$criteria_sha] = $id;
            return $id;
        }

        return null;
    }

    /**
     * Define target item for given source item.
     *
     * @param string  $source_itemtype
     * @param integer $source_id
     * @param string  $target_itemtype
     * @param integer $target_id
     *
     * @return void
     */
    protected function defineTargetItem(string $source_itemtype, int $source_id, string $target_itemtype, int $target_id): void
    {
        if (!array_key_exists($source_itemtype, $this->target_items_mapping)) {
            $this->target_items_mapping[$source_itemtype] = [];
        }
        $this->target_items_mapping[$source_itemtype][$source_id] = [
            'itemtype' => $target_itemtype,
            'id'       => $target_id,
        ];
    }

    /**
     * Returns target item corresponding to given itemtype and id.
     *
     * @param string  $source_itemtype
     * @param integer $source_id
     *
     * @return null|CommonDBTM
     */
    protected function getTargetItem(string $source_itemtype, int $source_id): ?CommonDBTM
    {
        if (
            !array_key_exists($source_itemtype, $this->target_items_mapping)
            || !array_key_exists($source_id, $this->target_items_mapping[$source_itemtype])
        ) {
            return null;
        }

        $mapping  = $this->target_items_mapping[$source_itemtype][$source_id];
        $id       = $mapping['id'];
        $itemtype = $mapping['itemtype'];

        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return null;
        }

        $item = new $itemtype();
        if (!$item->getFromDB($id)) {
            return null;
        }

        return $item;
    }

    /**
     * Store an item. Will update existing item if `$existing_item_id` is passed,
     * otherwise will create a new item.
     *
     * @param string $itemtype
     * @param int|null $existing_item_id
     * @param array $input
     * @param ProgressBar|null $progress_bar
     *
     * @return null|CommonDBTM Stored item.
     */
    protected function storeItem(string $itemtype, ?int $existing_item_id, array $input, ?ProgressBar $progress_bar = null): ?CommonDBTM
    {
        if (!is_a($itemtype, CommonDBTM::class, true)) {
            throw new \LogicException(sprintf('Invalid itemtype "%s".', $itemtype));
        }

        $item = new $itemtype();
        if ($existing_item_id !== null) {
            $input[$itemtype::getIndexName()] = $existing_item_id;
            if ($item->update($input) === false) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to update %s "%s" (%d).'),
                        $itemtype::getTypeName(),
                        $input['name'] ?? NOT_AVAILABLE,
                        (int) $existing_item_id
                    ),
                    $progress_bar
                );
                return null;
            }
        } else {
            if ($item->add($input) === false) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to create %s "%s" (%d).'),
                        $itemtype::getTypeName(),
                        $input['name'] ?? NOT_AVAILABLE,
                        (int) $input[$itemtype::getIndexName()]
                    ),
                    $progress_bar
                );
                return null;
            }
        }

        return $item;
    }

    /**
     * Store infocom data related to given item.
     *
     * @param CommonDBTM $item
     * @param array $infocom_input
     * @param ProgressBar|null $progress_bar
     *
     * @return void
     */
    protected function storeInfocomForItem(CommonDBTM $item, array $infocom_input, ?ProgressBar $progress_bar = null): void
    {
        $infocom = new Infocom();

        $exists = $infocom->getFromDBByCrit(
            [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
            ]
        );
        if ($exists) {
            $infocom_input += [
                'id' => $infocom->fields['id']
            ];
            $success = $infocom->update($infocom_input);
        } else {
            $infocom_input += [
                'itemtype'     => $item->getType(),
                'items_id'     => $item->getID(),
                'entities_id'  => $item->fields['entities_id'] ?? 0,
                'is_recursive' => $item->fields['is_recursive'] ?? 0,
            ];
            $success = $infocom->add($infocom_input);
        }

        if (!$success) {
            $this->handleImportError(
                sprintf(
                    __('Unable to financial and administrative information for %s "%s" (%d).'),
                    $item->getType(),
                    $item->getName(),
                    $item->getID()
                ),
                $progress_bar
            );
        }
    }

    /**
     * Returns key of the plugin handled by this migration.
     *
     * @return string
     */
    abstract protected function getPluginKey(): string;

    /**
     * Returns the minimal version of plugin supported by this migration.
     *
     * @return string|null
     */
    abstract protected function getRequiredMinimalPluginVersion(): ?string;

    /**
     * Returns the list of database plugin fields by this migration.
     * Expected returned value is a string array containing values in `table_name.field_name` format.
     *
     * @return array
     */
    abstract protected function getRequiredDatabasePluginFields(): array;

    /**
     * Migrate plugin data.
     *
     * @return void
     */
    abstract protected function migratePlugin(): void;
}
