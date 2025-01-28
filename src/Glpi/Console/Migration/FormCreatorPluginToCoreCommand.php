<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Console\AbstractCommand;
use Glpi\Form\Migration\MigrationManager;
use Glpi\Helpdesk\DefaultDataManager;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FormCreatorPluginToCoreCommand extends AbstractCommand
{
    /**
     * Error code returned if plugin version or plugin data is invalid.
     *
     * @var integer
     */
    public const ERROR_PLUGIN_VERSION_OR_DATA_INVALID = 1;

    /**
     * Error code returned if import failed.
     *
     * @var integer
     */
    public const ERROR_PLUGIN_IMPORT_FAILED = 1;

    /**
     * Version of Formcreator plugin required for this migration.
     * @var string
     */
    public const FORMCREATOR_REQUIRED_VERSION = '2.13.9';

    protected function configure()
    {
        parent::configure();

        $this->setName('migration:formcreator_plugin_to_core');
        $this->setDescription(__('Migrate Formcreator plugin data into GLPI core tables'));

        $this->addOption(
            'skip-errors',
            's',
            InputOption::VALUE_NONE,
            __('Do not stop on import errors')
        );

        $this->addOption(
            'truncate',
            't',
            InputOption::VALUE_NONE,
            __('Remove existing core data')
        );

        $this->addOption(
            'without-plugin',
            'w',
            InputOption::VALUE_NONE,
            sprintf(
                __('Enable migration without plugin files (we cannot validate that plugin data are compatible with supported %s version)'),
                self::FORMCREATOR_REQUIRED_VERSION
            )
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrationManager = new MigrationManager($this->db, $this);
        if (!$migrationManager->checkPlugin(!$input->getOption('without-plugin'))) {
            return self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID;
        }

        if ($input->getOption('truncate')) {
            $this->cleanCoreTables();
        }

        if (!$this->migratePlugin()) {
            return self::ERROR_PLUGIN_IMPORT_FAILED;
        }

        $output->writeln('<info>' . __('Migration done.') . '</info>');

        return 0; // Success
    }

    /**
     * Clean data from core tables.
     *
     * @throws RuntimeException
     */
    private function cleanCoreTables()
    {
        $core_tables = [
            'glpi_forms_accesscontrols_formaccesscontrols',
            'glpi_forms_answerssets',
            'glpi_forms_categories',
            'glpi_forms_comments',
            'glpi_forms_destinations_answerssets_formdestinationitems',
            'glpi_forms_destinations_formdestinations',
            'glpi_forms_forms',
            'glpi_forms_questions',
            'glpi_forms_sections',
            'glpi_plugin_formcreator_forms_groups',
            'glpi_plugin_formcreator_forms_languages',
            'glpi_plugin_formcreator_forms_profiles',
            'glpi_plugin_formcreator_forms_users',
            'glpi_plugin_formcreator_forms_validators'
        ];

        foreach ($core_tables as $table) {
            $result = $this->db->delete($table, [1]);

            if (!$result) {
                throw new \Symfony\Component\Console\Exception\RuntimeException(
                    sprintf('Unable to truncate table "%s"', $table)
                );
            }
        }

        // Create default forms
        $helpdesk_data_manager = new DefaultDataManager();
        $helpdesk_data_manager->initializeDataIfNeeded();
    }

    private function migratePlugin()
    {
        $no_interaction = $this->input->getOption('no-interaction');
        $skip_errors = $this->input->getOption('skip-errors');

        if (!$no_interaction) {
            // Ask for confirmation (unless --no-interaction)
            $this->output->writeln(
                [
                    __('You are about to launch migration of Formcreator plugin data into GLPI core tables.'),
                    __('It is better to make a backup of your existing data before continuing.')
                ]
            );

            $this->askForConfirmation(false);
        }

        $migrationManager = new MigrationManager($this->db, $this);
        return $migrationManager->doMigration() || $skip_errors;
    }
}
