<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Migration;

use Plugin;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param string           $message
     * @param ProgressBar|null $progress_bar
     *
     * @return void
     */
    protected function handleImportError($message, ProgressBar $progress_bar = null): void
    {
        $skip_errors = $this->input->getOption('skip-errors');

        $verbosity = $skip_errors
            ? OutputInterface::VERBOSITY_NORMAL
            : OutputInterface::VERBOSITY_QUIET;

        $message = '<error>' . $message . '</error>';

        if ($skip_errors && $progress_bar instanceof ProgressBar) {
            $this->writelnOutputWithProgressBar(
                $message,
                $progress_bar,
                $verbosity
            );
        } else {
            if (!$skip_errors && $progress_bar instanceof ProgressBar) {
                $this->output->write(PHP_EOL); // Keep progress bar last state and go to next line
            }
            $this->output->writeln(
                $message,
                $verbosity
            );
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Plugin data import failed.') . '</error>',
                self::ERROR_PLUGIN_IMPORT_FAILED
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
}
