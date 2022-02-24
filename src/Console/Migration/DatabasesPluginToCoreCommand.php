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

use Database;
use DatabaseInstance;
use DatabaseInstanceCategory;
use DatabaseInstanceType;
use Change_Item;
use Contract_Item;
use DB;
use Document_Item;
use Glpi\Toolbox\Sanitizer;
use Infocom;
use Item_Problem;
use Item_Project;
use Item_Ticket;
use KnowbaseItem_Item;
use Log;
use Profile;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesPluginToCoreCommand extends AbstractPluginToCoreCommand
{
    /**
     * Error code returned when cleaning code tables failed.
     *
     * @var integer
     */
    const ERROR_CORE_DATA_CLEAN_FAILED = 3;

    /**
     * Itemtype corresponding to database in plugin.
     *
     * @var string
     */
    const PLUGIN_DATABASES_ITEMTYPE = "PluginDatabasesDatabase";

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:databases_plugin_to_core');
        $this->setDescription(__('Migrate Databases plugin data into GLPI core tables'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $no_interaction = $input->getOption('no-interaction');
        if (!$no_interaction) {
            // Ask for confirmation (unless --no-interaction)
            $output->writeln(
                [
                    __('You are about to launch migration of Databases plugin data into GLPI core tables.'),
                    __('Any previous database created in core will be lost.'),
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

    protected function getPluginKey(): string
    {
        return 'databases';
    }

    protected function getRequiredMinimalPluginVersion(): ?string
    {
        return '2.3.0';
    }

    protected function getRequiredDatabasePluginFields(): array
    {
        return [
            'glpi_plugin_databases_databases.id',
            'glpi_plugin_databases_databases.entities_id',
            'glpi_plugin_databases_databases.is_recursive',
            'glpi_plugin_databases_databases.name',
            'glpi_plugin_databases_databases.is_deleted',
            'glpi_plugin_databases_databases.plugin_databases_databasetypes_id',
            'glpi_plugin_databases_databases.plugin_databases_databasecategories_id',
            'glpi_plugin_databases_databases.comment',
            'glpi_plugin_databases_databases.locations_id',
            'glpi_plugin_databases_databases.manufacturers_id',
            'glpi_plugin_databases_databases.users_id',
            'glpi_plugin_databases_databases.groups_id',
            'glpi_plugin_databases_databases.date_mod',
            'glpi_plugin_databases_databases.is_helpdesk_visible',

            'glpi_plugin_databases_instances.id',
            'glpi_plugin_databases_instances.entities_id',
            'glpi_plugin_databases_instances.is_recursive',
            'glpi_plugin_databases_instances.name',
            'glpi_plugin_databases_instances.plugin_databases_databases_id',

            'glpi_plugin_databases_databasetypes.id',
            'glpi_plugin_databases_databasetypes.name',
            'glpi_plugin_databases_databasetypes.comment',

            'glpi_plugin_databases_databasecategories.id',
            'glpi_plugin_databases_databasecategories.name',
            'glpi_plugin_databases_databasecategories.comment',
        ];
    }

    /**
     * Clean data from core tables.
     *
     * @return void
     */
    private function cleanCoreTables(): void
    {
        $core_tables = [
            Database::getTable(),
            DatabaseInstance::getTable(),
            DatabaseInstanceType::getTable(),
            DatabaseInstanceCategory::getTable(),
        ];
        foreach ($core_tables as $table) {
            $result = $this->db->query('TRUNCATE ' . DB::quoteName($table));

            if (!$result) {
                throw new \Glpi\Console\Exception\EarlyExitException(
                    '<error>' . sprintf(__('Cleaning table "%s" failed.'), $table) . '</error>',
                    self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID
                );
            }
        }

        $table  = Infocom::getTable();
        $result = $this->db->delete($table, [
            'itemtype' => DatabaseInstance::class
        ]);
        if (!$result) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . sprintf(__('Cleaning table "%s" failed.'), $table) . '</error>',
                self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID
            );
        }
    }

    /**
     * Copy plugin tables to backup tables from plugin to core keeping same ID.
     *
     * @return void
     */
    private function migratePlugin(): void
    {
        //prevent infocom creation from general setup
        global $CFG_GLPI;
        if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]) {
            $CFG_GLPI['auto_create_infocoms'] = false;
        }

        $this->cleanCoreTables();

        $this->createDatabaseInstanceTypes();
        $this->createDatabaseInstanceCategories();
        $this->createDatabaseInstances();
        $this->createDatabases();
        $this->updateItemtypes();
        $this->updateProfilesDatabaseRights();
    }

    /**
     * Update profile rights (Associable items to a ticket).
     *
     * @return void
     */
    private function updateProfilesDatabaseRights(): void
    {
        $this->output->writeln(
            '<comment>' . __('Updating profiles...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $table  = Profile::getTable();
        $result = $this->db->query(
            sprintf(
                "UPDATE %s SET helpdesk_item_type = REPLACE(helpdesk_item_type, '%s', '%s')",
                DB::quoteName($table),
                self::PLUGIN_DATABASES_ITEMTYPE,
                DatabaseInstance::class
            )
        );
        if (false === $result) {
            $this->handleImportError(
                sprintf(__('Unable to update "%s" in profiles.'), __('Associable items to a ticket'))
            );
        }
    }

    /**
     * Rename itemtype in core tables.
     *
     * @return void
     */
    private function updateItemtypes(): void
    {
        $this->output->writeln(
            '<comment>' . __('Updating GLPI itemtypes...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $itemtypes_tables = [
            Item_Ticket::getTable(),
            Item_Problem::getTable(),
            Change_Item::getTable(),
            Item_Project::getTable(),
            Log::getTable(),
            Infocom::getTable(),
            Document_Item::getTable(),
            Contract_Item::getTable(),
            KnowbaseItem_Item::getTable(),
        ];

        foreach ($itemtypes_tables as $itemtype_table) {
            $result = $this->db->update(
                $itemtype_table,
                [
                    'itemtype' => DatabaseInstance::class,
                ],
                [
                    'itemtype' => self::PLUGIN_DATABASES_ITEMTYPE,
                ]
            );

            if (false === $result) {
                $this->handleImportError(
                    sprintf(
                        __('Migration of table "%s" failed with message "(%s) %s".'),
                        $itemtype_table,
                        $this->db->errno(),
                        $this->db->error()
                    )
                );
            }
        }
    }

    /**
     * Create database instance categories.
     *
     * @return void
     */
    private function createDatabaseInstanceCategories(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Creating %s...'), DatabaseInstanceCategory::getTypeName()) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_databasecategories'
        ]);

        if ($iterator->count() === 0) {
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $category_data) {
            $this->writelnOutputWithProgressBar(
                sprintf(__('Importing %s "%s"...'), DatabaseInstanceCategory::getTypeName(), $category_data['name']),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $category_fields = Sanitizer::sanitize([
                'id'        => $category_data['id'],
                'name'      => $category_data['name'],
                'comment'   => $category_data['comment']
            ]);

            $category = new DatabaseInstanceCategory();
            if ($category->getFromDBByCrit($category_fields) === false && $category->add($category_fields) === false) {
                $this->handleImportError(
                    sprintf(__('Unable to create %s "%s" (%d).'), DatabaseInstanceCategory::getTypeName(), $category_data['name'], (int) $category_data['id']),
                    $progress_bar
                );
            }
        }

        $this->output->write(PHP_EOL);
    }

    /**
     * Create databases.
     *
     * @return void
     */
    private function createDatabases(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Creating %s...'), Database::getTypeName()) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_instances' // Database in GLPI core corresponds to PluginDatabasesInstance
        ]);

        if ($iterator->count() === 0) {
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $database_data) {
            $this->writelnOutputWithProgressBar(
                sprintf(__('Importing %s "%s"...'), Database::getTypeName(), $database_data['name']),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $database_fields = Sanitizer::sanitize([
                'id'                    => $database_data['id'],
                'entities_id'           => $database_data['entities_id'],
                'is_recursive'          => $database_data['is_recursive'],
                'name'                  => $database_data['name'],
                'is_deleted'            => 0,
                'is_active'             => 1,
                'databaseinstances_id'  => $database_data['plugin_databases_databases_id'],
            ]);

            $database = new Database();
            if ($database->getFromDBByCrit($database_fields) === false && $database->add($database_fields) === false) {
                $this->handleImportError(
                    sprintf(__('Unable to create %s "%s" (%d).'), Database::getTypeName(), $database_data['name'], (int) $database_data['id']),
                    $progress_bar
                );
            }
        }

        $this->output->write(PHP_EOL);
    }

   /**
    * Create databases instances.
    *
    * @return void
    */
    private function createDatabaseInstances(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Creating %s...'), DatabaseInstance::getTypeName()) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_databases' // Database in GLPI core corresponds to PluginDatabasesDatabase
        ]);

        if ($iterator->count() === 0) {
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $instance_data) {
            $this->writelnOutputWithProgressBar(
                sprintf(__('Importing %s "%s"...'), DatabaseInstance::getTypeName(), $instance_data['name']),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $database_fields = Sanitizer::sanitize([
                'id'                            => $instance_data['id'],
                'entities_id'                   => $instance_data['entities_id'],
                'is_recursive'                  => $instance_data['is_recursive'],
                'name'                          => $instance_data['name'],
                'is_deleted'                    => $instance_data['is_deleted'],
                'is_active'                     => 1,
                'databaseinstancetypes_id'      => $instance_data['plugin_databases_databasetypes_id'],
                'databaseinstancecategories_id' => $instance_data['plugin_databases_databasecategories_id'],
                'comment'                       => $instance_data['comment'],
                'locations_id'                  => $instance_data['locations_id'],
                'manufacturers_id'              => $instance_data['manufacturers_id'],
                'users_id_tech'                 => $instance_data['users_id'],
                'groups_id_tech'                => $instance_data['groups_id'],
                'date_mod'                      => $instance_data['date_mod'],
                'is_helpdesk_visible'           => $instance_data['is_helpdesk_visible'],
                'states_id'                     => 0,
                'is_dynamic'                    => 0,
            ]);

            $instance = new DatabaseInstance();
            if ($instance->getFromDBByCrit($database_fields) === false && $instance->add($database_fields) === false) {
                $this->handleImportError(
                    sprintf(__('Unable to create %s "%s" (%d).'), DatabaseInstance::getTypeName(), $instance_data['name'], (int) $instance_data['id']),
                    $progress_bar
                );
            }
        }

        $this->output->write(PHP_EOL);
    }

    /**
     * Create database instance types.
     *
     * @return void
     */
    private function createDatabaseInstanceTypes(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Creating %s...'), DatabaseInstanceType::getTypeName()) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_databasetypes'
        ]);

        if ($iterator->count() === 0) {
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $type_data) {
            $this->writelnOutputWithProgressBar(
                sprintf(__('Importing %s "%s"...'), DatabaseInstanceType::getTypeName(), $type_data['name']),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $type_fields = Sanitizer::sanitize([
                'id'        => $type_data['id'],
                'name'      => $type_data['name'],
                'comment'   => $type_data['comment'],
            ]);

            $type = new DatabaseInstanceType();
            if ($type->getFromDBByCrit($type_fields) === false && $type->add($type_fields) === false) {
                $this->handleImportError(
                    sprintf(__('Unable to create %s "%s" (%d).'), DatabaseInstanceType::getTypeName(), $type_data['name'], (int) $type_data['id']),
                    $progress_bar
                );
            }
        }

        $this->output->write(PHP_EOL);
    }
}
