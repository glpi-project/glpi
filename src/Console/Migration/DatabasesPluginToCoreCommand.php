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
use Domain;
use Glpi\Console\AbstractCommand;
use Glpi\Toolbox\Sanitizer;
use Infocom;
use Item_Problem;
use Item_Project;
use Item_Ticket;
use KnowbaseItem_Item;
use Location;
use Log;
use Network;
use Profile;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesPluginToCoreCommand extends AbstractCommand
{
    /**
     * Error code returned if plugin version or plugin data is invalid.
     *
     * @var integer
     */
    const ERROR_PLUGIN_VERSION_OR_DATA_INVALID = 1;

    /**
     * Error code returned if import failed.
     *
     * @var integer
     */
    const ERROR_PLUGIN_IMPORT_FAILED = 2;

    /**
     * list of possible relations of the plugin indexed by their correspond integer in the plugin
     *
     * @var array
     */
    const PLUGIN_RELATION_TYPES = [
//        1 => Location::class,
//        2 => Network::class,
//        3 => Domain::class,
    ];

    /**
     * list of usefull plugin tables and fields
     *
     * @var array
     */
    const PLUGIN_DATABASES_TABLES = [
        "glpi_plugin_databases_databases"       => [
            "id",
            "entities_id",
            "is_recursive",
            "name",
            "is_deleted",
            "plugin_databases_databasetypes_id",
            "comment",
            "locations_id",
            "plugin_databases_databasecategories_id",
            "users_id",
            "groups_id",
            "suppliers_id",
            "plugin_databases_servertypes_id",
            "manufacturers_id",
            "date_mod",
            "link",
            "is_helpdesk_visible",
        ],
        "glpi_plugin_databases_instances"       => [
           "id",
           "entities_id",
           "is_recursive",
           "name",
           "plugin_databases_databases_id",
           "port",
           "path",
           "comment",
        ],
//        "glpi_plugin_databases_scripts"       => [
//           "id",
//           "entities_id",
//           "is_recursive",
//           "name",
//           "plugin_databases_databases_id",
//           "plugin_databases_scripttypes_id",
//           "port",
//           "path",
//           "comment",
//        ],
        "glpi_plugin_databases_databasetypes"   => ["id","entities_id","name","comment"],
        "glpi_plugin_databases_databasecategories"   => ["id","entities_id","name","comment"],
//        "glpi_plugin_databases_servertypes"   => ["id","name","comment"],
        "glpi_plugin_databases_scripttypes"   => ["id","name","comment"],
        "glpi_plugin_databases_databases_items" => ["id", "plugin_databases_databases_id","items_id","itemtype"],
    ];

    /**
     * itemtype corresponding to database in plugin
     *
     * @var string
     */
    const PLUGIN_DATABASES_ITEMTYPE = "PluginDatabasesDatabase";

    /**
     * itemtype corresponding to database in core
     *
     * @var string
     */
    const CORE_DATABASES_ITEMTYPE = "DatabaseInstance";

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:databases_plugin_to_core');
        $this->setDescription(__('Migrate Databases plugin data into GLPI core tables'));

        $this->addOption(
            'skip-errors',
            's',
            InputOption::VALUE_NONE,
            __('Do not exit on import errors')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $no_interaction = $input->getOption('no-interaction');
        if (!$no_interaction) {
           // Ask for confirmation (unless --no-interaction)
            $output->writeln([
                __('You are about to launch migration of Databases plugin data into GLPI core tables.'),
                __('Any previous database created in core will be lost.'),
                __('It is better to make a backup of your existing data before continuing.')
            ]);

            $this->askForConfirmation();
        }

        if (!$this->checkPlugin()) {
            return self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID;
        }

        if (!$this->migratePlugin()) {
            return self::ERROR_PLUGIN_IMPORT_FAILED;
        }

        $output->writeln('<info>' . __('Migration done.') . '</info>');
        return 0; // Success
    }

    /**
     * Check that required tables exists and fields are OK for migration.
     *
     * @return bool
     */
    private function checkPlugin(): bool
    {
        $missing_tables = false;
        foreach (self::PLUGIN_DATABASES_TABLES as $table => $fields) {
            if (!$this->db->tableExists($table)) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Databases plugin table "%s" is missing.'), $table) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $missing_tables = true;
            } else {
                foreach ($fields as $field) {
                    if (!$this->db->fieldExists($table, $field)) {
                        $this->output->writeln(
                            '<error>' . sprintf(__('Databases plugin field "%s" is missing.'), $table . '.' . $field) . '</error>',
                            OutputInterface::VERBOSITY_QUIET
                        );
                        $missing_tables = true;
                    }
                }
            }
        }
        if ($missing_tables) {
            $this->output->writeln(
                '<error>' . __('Migration cannot be done.') . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        return true;
    }

    /**
     * Clean data from core tables.
     *
     * @throws RuntimeException
     */
    private function cleanCoreTables()
    {
        $core_tables = [
            \Database::getTable(),
            \DatabaseInstance::getTable(),
            \DatabaseInstanceType::getTable(),
            \DatabaseInstanceCategory::getTable(),
        ];

        foreach ($core_tables as $table) {
            $result = $this->db->query('TRUNCATE ' . DB::quoteName($table));

            if (!$result) {
                throw new \Symfony\Component\Console\Exception\RuntimeException(
                    sprintf('Unable to truncate table "%s"', $table)
                );
            }
        }

        $table  = Infocom::getTable();
        $result = $this->db->delete($table, [
            'itemtype' => self::CORE_DATABASES_ITEMTYPE
        ]);
        if (!$result) {
            throw new \Symfony\Component\Console\Exception\RuntimeException(
                sprintf('Unable to clean table "%s"', $table)
            );
        }
    }


    /**
     * Copy plugin tables to backup tables from plugin to core keeping same ID.
     *
     * @return bool
     */
    private function migratePlugin(): bool
    {
        global $CFG_GLPI;

       //prevent infocom creation from general setup
        if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]) {
            $CFG_GLPI['auto_create_infocoms'] = false;
        }
        $this->cleanCoreTables();

        return $this->createDatabaseTypes()
         && $this->createDatabaseCategories()
         && $this->createDatabases()
         && $this->createDatabaseInstances()
          && $this->createDatabaseInstanceItems()
         && $this->updateItemtypes()
         && $this->updateProfilesDatabaseRights();
    }

    /**
     * Update profile rights (Associable items to a ticket).
     *
     * @return bool
     */
    private function updateProfilesDatabaseRights(): bool
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
                self::CORE_DATABASES_ITEMTYPE
            )
        );
        if (false === $result) {
            $this->outputImportError(
                sprintf(__('Unable to update "%s" in profiles.'), __('Associable items to a ticket'))
            );
            if (!$this->input->getOption('skip-errors')) {
                 return false;
            }
        }

        return true;
    }

    /**
     * Rename itemtype in core tables.
     *
     * @return bool
     */
    private function updateItemtypes(): bool
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
            $result = $this->db->update($itemtype_table, [
                'itemtype' => self::CORE_DATABASES_ITEMTYPE,
            ], [
                'itemtype' => self::PLUGIN_DATABASES_ITEMTYPE,
            ]);

            if (false === $result) {
                $this->outputImportError(
                    sprintf(
                        __('Migration of table "%s" failed with message "(%s) %s".'),
                        $itemtype_table,
                        $this->db->errno(),
                        $this->db->error()
                    )
                );
                if (!$this->input->getOption('skip-errors')) {
                     return false;
                }
            }
        }

        return true;
    }

    /**
     * Create database instance items.
     *
     * @return bool
     */
    private function createDatabaseInstanceItems(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Database Instances Items...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_databases_items'
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $item) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing Database Instances item "%d"...'),
                    (int) $item['id']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $app_fields = Sanitizer::sanitize([
                'id'            => $item['plugin_databases_databases_id'],
                'items_id'      => $item['items_id'],
                'itemtype'      => $item['itemtype']
            ]);

            $appi = new \DatabaseInstance();
            $appi_id = $appi->update($app_fields);

            if (false === $appi_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Database Instances item %d.'), (int) $item['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                     return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }

    /**
     * Create database instance categories.
     *
     * @return bool
     */
    private function createDatabaseCategories(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Database Instance categories...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_databasecategories'
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $env) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing Instance category "%s"...'),
                    $env['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $app_fields = Sanitizer::sanitize([
                'id'      => $env['id'],
                'name'    => $env['name'],
                'comment' => $env['comment']
            ]);

            $appe = new \DatabaseInstanceCategory();
            if (!($appe_id = $appe->getFromDBByCrit($app_fields))) {
                $appe_id = $appe->add($app_fields);
            }

            if (false === $appe_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Database Instance category %s (%d).'), $env['name'], (int) $env['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                     return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }

    /**
     * Create databases.
     *
     * @return bool
     */
    private function createDatabases(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Databases...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_instances'
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $instance) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing database "%s"...'),
                    $instance['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $app_fields = Sanitizer::sanitize([
                'id'                       => $instance['id'],
                'entities_id'              => $instance['entities_id'],
                'is_recursive'             => $instance['is_recursive'],
                'name'                     => $instance['name'],
                'is_deleted'               => 0,
                'is_active'               => 1,
                'databaseinstances_id'               => $instance['plugin_databases_databases_id'],
            ]);

            $app = new \Database();
            if (!($app_id = $app->getFromDBByCrit($app_fields))) {
                $app_id = $app->add($app_fields);
            }

            if (false === $app_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Database %s (%d).'), $instance['name'], (int) $instance['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                     return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }


   /**
    * Create databases.
    *
    * @return bool
    */
   private function createDatabaseInstances(): bool
   {
      $this->output->writeln(
         '<comment>' . __('Creating Databases instances...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );
      $iterator = $this->db->request([
                                        'FROM' => 'glpi_plugin_databases_databases'
                                     ]);

      if (!count($iterator)) {
         return true;
      }

      $progress_bar = new ProgressBar($this->output);

      foreach ($progress_bar->iterate($iterator) as $database) {
         $this->writelnOutputWithProgressBar(
            sprintf(
               __('Importing database instance "%s"...'),
               $database['name']
            ),
            $progress_bar,
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $app_fields = Sanitizer::sanitize([
                                              'id'                            => $database['id'],
                                              'entities_id'                   => $database['entities_id'],
                                              'is_recursive'                  => $database['is_recursive'],
                                              'name'                          => $database['name'],
                                              'is_deleted'                    => $database['is_deleted'],
                                              'is_active'                     => 1,
                                              'databaseinstancetypes_id'      => $database['plugin_databases_databasetypes_id'],
                                              'databaseinstancecategories_id' => $database['plugin_databases_databasecategories_id'],
                                              'comment'                       => $database['comment'],
                                              'locations_id'                  => $database['locations_id'],
                                              'manufacturers_id'              => $database['manufacturers_id'],
                                              'users_id_tech'                 => $database['users_id'],
                                              'groups_id_tech'                => $database['groups_id'],
                                              'date_mod'                      => $database['date_mod'],
                                              'is_helpdesk_visible'           => $database['is_helpdesk_visible'],
                                              'states_id'                     => 0,
                                              'is_dynamic'                    => 0,
                                           ]);

         $app = new \DatabaseInstance();
         if (!($app_id = $app->getFromDBByCrit($app_fields))) {
            $app_id = $app->add($app_fields);
         }

         if (false === $app_id) {
            $this->outputImportError(
               sprintf(__('Unable to create Database instance %s (%d).'), $database['name'], (int) $database['id']),
               $progress_bar
            );
            if (!$this->input->getOption('skip-errors')) {
               return false;
            }
         }
      }

      $this->output->write(PHP_EOL);

      return true;
   }
    /**
     * Create database types.
     *
     * @return bool
     */
    private function createDatabaseTypes(): bool
    {
        $this->output->writeln(
            '<comment>' . __('Creating Database Instance types...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM' => 'glpi_plugin_databases_databasetypes'
        ]);

        if (!count($iterator)) {
            return true;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $type) {
            $this->writelnOutputWithProgressBar(
                sprintf(
                    __('Importing type "%s"...'),
                    $type['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $appt_fields = Sanitizer::sanitize([
                'id'                 => $type['id'],
                'name'               => $type['name'],
                'comment'            => $type['comment'],
            ]);

            $appt = new \DatabaseInstanceType();
            if (!($appt_id = $appt->getFromDBByCrit($appt_fields))) {
                $appt_id = $appt->add($appt_fields);
            }

            if (false === $appt_id) {
                $this->outputImportError(
                    sprintf(__('Unable to create Database Instance type %s (%d).'), $type['name'], (int) $type['id']),
                    $progress_bar
                );
                if (!$this->input->getOption('skip-errors')) {
                     return false;
                }
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }


    /**
     * Output import error message.
     *
     * @param string           $message
     * @param ProgressBar|null $progress_bar
     *
     * @return void
     */
    private function outputImportError($message, ProgressBar $progress_bar = null)
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
        }
    }
}
