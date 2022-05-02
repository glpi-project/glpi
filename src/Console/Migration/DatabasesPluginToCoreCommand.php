<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Database;
use DatabaseInstance;
use DatabaseInstanceCategory;
use DatabaseInstanceType;
use Change_Item;
use Contract_Item;
use Document_Item;
use Item_Problem;
use Item_Project;
use Item_Ticket;
use KnowbaseItem_Item;
use Profile;
use Session;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Toolbox;

class DatabasesPluginToCoreCommand extends AbstractPluginToCoreCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:migration:databases_plugin_to_core');
        $this->setDescription(__('Migrate Databases plugin data into GLPI core tables'));
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
            'glpi_plugin_databases_databases.suppliers_id',
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

    protected function migratePlugin(): void
    {
        //prevent infocom creation from general setup
        global $CFG_GLPI;
        if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]) {
            $CFG_GLPI['auto_create_infocoms'] = false;
        }

        $this->importDatabaseInstanceTypes();
        $this->importDatabaseInstanceCategories();
        $this->importDatabaseInstances();
        $this->importDatabases();
        $this->importItemsRelations();
        $this->updateProfiles();
    }

    /**
     * Update profile rights (Associable items to a ticket).
     *
     * @return void
     */
    private function updateProfiles(): void
    {
        $this->output->writeln(
            '<comment>' . __('Updating profiles...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'SELECT' => ['id', 'name', 'helpdesk_item_type'],
            'FROM'   => Profile::getTable(),
            'ORDER'  => 'id ASC',
        ]);

        if ($iterator->count() === 0) {
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $profile_data) {
            $helpdesk_item_types = importArrayFromDB($profile_data['helpdesk_item_type']);
            if (
                in_array('PluginDatabasesDatabase', $helpdesk_item_types)
                && !in_array(DatabaseInstance::class, $helpdesk_item_types)
            ) {
                $profile = new Profile();
                if (!$profile->update(['id' => $profile_data['id'], 'helpdesk_item_type' => $helpdesk_item_types])) {
                    $this->handleImportError(
                        sprintf(
                            __('Unable to update "%s" in profile "%s" (%s).'),
                            __('Associable items to a ticket'),
                            $profile_data['name'],
                            $profile_data['id']
                        )
                    );
                }
            }
        }

        $this->output->write(PHP_EOL);
    }

    /**
     * Import items relations.
     *
     * @return void
     */
    private function importItemsRelations(): void
    {
        $this->output->writeln(
            '<comment>' . __('Importing relations with other itemtypes...') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $relations_itemtypes = [
            Item_Ticket::class,
            Item_Problem::class,
            Change_Item::class,
            Item_Project::class,
            Document_Item::class,
            Contract_Item::class,
            KnowbaseItem_Item::class,
        ];

        foreach ($relations_itemtypes as $relation_itemtype) {
            $this->output->writeln(
                '<comment>' . sprintf(__('Importing %s...'), $relation_itemtype::getTypeName(Session::getPluralNumber())) . '</comment>',
                OutputInterface::VERBOSITY_NORMAL
            );

            $iterator = $this->db->request([
                'FROM'  => $relation_itemtype::getTable(),
                'WHERE' => ['itemtype' => 'PluginDatabasesDatabase'],
                'ORDER' => 'id ASC'
            ]);

            if ($iterator->count() === 0) {
                $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
                return;
            }

            $opposite_fkey = $relation_itemtype::$items_id_1;

            $progress_bar = new ProgressBar($this->output);

            foreach ($progress_bar->iterate($iterator) as $relation_data) {
                $mapped_database = $this->getTargetItem('PluginDatabasesDatabase', $relation_data['items_id']);
                if ($mapped_database === null) {
                    $this->handleImportError(
                        sprintf(
                            __('Unable to find target item for %s #%s.'),
                            'PluginDatabasesDatabase',
                            $relation_data['items_id']
                        ),
                        $progress_bar,
                        true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                    );
                    continue;
                }

                $database_id = $mapped_database->fields['id'];

                $core_relation_id = $this->getMatchingElementId(
                    $relation_itemtype,
                    [
                        $opposite_fkey => $relation_data[$opposite_fkey],
                        'itemtype'     => DatabaseInstance::class,
                        'items_id'     => $database_id,
                    ]
                );

                $this->writelnOutputWithProgressBar(
                    sprintf(
                        $core_relation_id !== null ? __('Skip existing %s "%s".') : __('Importing %s "%s"...'),
                        $relation_itemtype::getTypeName(),
                        $relation_data[$opposite_fkey] . ' ' . DatabaseInstance::class . ' ' . $database_id
                    ),
                    $progress_bar,
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );

                if ($core_relation_id !== null) {
                     // If relation already exist in DB, there is nothing to change
                     continue;
                }

                $relation_input = $relation_data;
                unset($relation_input['id']);
                $relation_input['itemtype'] = DatabaseInstance::class;
                $relation_input['items_id'] = $database_id;
                $relation_input = Toolbox::addslashes_deep($relation_input);

                $item = new $relation_itemtype();
                if ($item->add($relation_input) === false) {
                    $message = sprintf(
                        __('Unable to create %s "%s" (%d).'),
                        $relation_itemtype::getTypeName(),
                        $relation_data[$opposite_fkey] . ' ' . DatabaseInstance::class . ' ' . $database_id
                    );
                    $this->handleImportError($message, $progress_bar);
                }
            }
        }
    }

    /**
     * Import database instance categories.
     *
     * @return void
     */
    private function importDatabaseInstanceCategories(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), DatabaseInstanceCategory::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM'  => 'glpi_plugin_databases_databasecategories',
            'ORDER' => 'id ASC'
        ]);

        if ($iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $category_data) {
            $core_category_id = $this->getMatchingElementId(DatabaseInstanceCategory::class, ['name' => $category_data['name']]);

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_category_id !== null ? __('Updating existing %s "%s"...') : __('Importing %s "%s"...'),
                    DatabaseInstanceCategory::getTypeName(),
                    $category_data['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $category = $this->storeItem(
                DatabaseInstanceCategory::class,
                $core_category_id,
                Toolbox::addslashes_deep(
                    [
                        'name'      => $category_data['name'],
                        'comment'   => $category_data['comment']
                    ]
                )
            );

            if ($category !== null) {
                $this->defineTargetItem(
                    'PluginDatabasesDatabaseCategory',
                    $category_data['id'],
                    DatabaseInstanceCategory::class,
                    $category->getID()
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
    private function importDatabases(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), Database::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $iterator = $this->db->request([
            'FROM'  => 'glpi_plugin_databases_instances', // Database in GLPI core corresponds to PluginDatabasesInstance
            'ORDER' => 'id ASC'
        ]);

        if ($iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $database_data) {
            $mapped_instance = $this->getTargetItem('PluginDatabasesDatabase', $database_data['plugin_databases_databases_id']);
            if ($database_data['plugin_databases_databases_id'] != 0 && $mapped_instance === null) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to find target item for %s #%s.'),
                        'PluginDatabasesDatabase',
                        $database_data['plugin_domains_domains_id']
                    ),
                    $progress_bar,
                    true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                );
            }

            $core_database_id = $this->getMatchingElementId(
                Database::class,
                [
                    'name'                  => $database_data['name'],
                    'databaseinstances_id'  => $mapped_instance !== null ? $mapped_instance->getID() : 0,
                ]
            );

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_database_id !== null ? __('Updating existing %s "%s"...') : __('Importing %s "%s"...'),
                    Database::getTypeName(),
                    $database_data['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $mapped_instance = $this->getTargetItem('PluginDatabasesDatabase', $database_data['plugin_databases_databases_id']);
            if ($database_data['plugin_databases_databases_id'] != 0 && $mapped_instance === null) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to find target item for %s #%s.'),
                        'PluginDatabasesDatabase',
                        $database_data['plugin_domains_domains_id']
                    ),
                    $progress_bar,
                    true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                );
            }

            $database = $this->storeItem(
                Database::class,
                $core_database_id,
                Toolbox::addslashes_deep(
                    [
                        'entities_id'           => $database_data['entities_id'],
                        'is_recursive'          => $database_data['is_recursive'],
                        'name'                  => $database_data['name'],
                        'is_deleted'            => 0,
                        'is_active'             => 1,
                        'databaseinstances_id'  => $mapped_instance !== null ? $mapped_instance->getID() : 0,
                    ]
                )
            );

            if ($database !== null) {
                $this->defineTargetItem(
                    'PluginDatabasesInstance',
                    $database_data['id'],
                    Database::class,
                    $database->getID()
                );
            }
        }

        $this->output->write(PHP_EOL);
    }

   /**
    * Import databases instances.
    *
    * @return void
    */
    private function importDatabaseInstances(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), DatabaseInstance::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $iterator = $this->db->request([
            'FROM'  => 'glpi_plugin_databases_databases', // Database in GLPI core corresponds to PluginDatabasesDatabase
            'ORDER' => 'id ASC'
        ]);

        if ($iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $instance_data) {
            $core_instance_id = $this->getMatchingElementId(DatabaseInstance::class, ['name' => $instance_data['name']]);

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_instance_id !== null ? __('Updating existing %s "%s"...') : __('Importing %s "%s"...'),
                    DatabaseInstance::getTypeName(),
                    $instance_data['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $mapped_type = $this->getTargetItem('PluginDatabasesDatabaseType', $instance_data['plugin_databases_databasetypes_id']);
            if ($instance_data['plugin_databases_databasetypes_id'] != 0 && $mapped_type === null) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to find target item for %s #%s.'),
                        'PluginDatabasesDatabaseType',
                        $instance_data['plugin_domains_domains_id']
                    ),
                    $progress_bar,
                    true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                );
            }

            $mapped_category = $this->getTargetItem('PluginDatabasesDatabaseCategory', $instance_data['plugin_databases_databasecategories_id']);
            if ($instance_data['plugin_databases_databasecategories_id'] != 0 && $mapped_category === null) {
                $this->handleImportError(
                    sprintf(
                        __('Unable to find target item for %s #%s.'),
                        'PluginDatabasesDatabaseCategory',
                        $instance_data['plugin_databases_databasecategories_id']
                    ),
                    $progress_bar,
                    true // Do not block migration as this error is probably resulting in presence of obsolete data in DB
                );
            }

            $instance = $this->storeItem(
                DatabaseInstance::class,
                $core_instance_id,
                Toolbox::addslashes_deep(
                    [
                        'entities_id'                   => $instance_data['entities_id'],
                        'is_recursive'                  => $instance_data['is_recursive'],
                        'name'                          => $instance_data['name'],
                        'is_deleted'                    => $instance_data['is_deleted'],
                        'is_active'                     => 1,
                        'databaseinstancetypes_id'      => $mapped_type !== null ? $mapped_type->getID() : 0,
                        'databaseinstancecategories_id' => $mapped_category !== null ? $mapped_category->getID() : 0,
                        'comment'                       => $instance_data['comment'],
                        'locations_id'                  => $instance_data['locations_id'],
                        'manufacturers_id'              => $instance_data['manufacturers_id'],
                        'users_id_tech'                 => $instance_data['users_id'],
                        'groups_id_tech'                => $instance_data['groups_id'],
                        'date_mod'                      => $instance_data['date_mod'],
                        'is_helpdesk_visible'           => $instance_data['is_helpdesk_visible'],
                        'states_id'                     => 0,
                        'is_dynamic'                    => 0,
                    ]
                )
            );

            if ($instance !== null) {
                $this->defineTargetItem(
                    'PluginDatabasesDatabase',
                    $instance_data['id'],
                    DatabaseInstance::class,
                    $instance->getID()
                );
                if (!empty($instance_data['suppliers_id'])) {
                    $this->storeInfocomForItem($instance, ['suppliers_id' => $instance_data['suppliers_id']], $progress_bar);
                }
            }
        }

        $this->output->write(PHP_EOL);
    }

    /**
     * Import database instance types.
     *
     * @return void
     */
    private function importDatabaseInstanceTypes(): void
    {
        $this->output->writeln(
            '<comment>' . sprintf(__('Importing %s...'), DatabaseInstanceType::getTypeName(Session::getPluralNumber())) . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
        );

        $iterator = $this->db->request([
            'FROM'  => 'glpi_plugin_databases_databasetypes',
            'ORDER' => 'id ASC'
        ]);

        if ($iterator->count() === 0) {
            $this->output->writeln('<comment>' . __('No elements found.') . '</comment>');
            return;
        }

        $progress_bar = new ProgressBar($this->output);

        foreach ($progress_bar->iterate($iterator) as $type_data) {
            $core_type_id = $this->getMatchingElementId(DatabaseInstanceType::class, ['name' => $type_data['name']]);

            $this->writelnOutputWithProgressBar(
                sprintf(
                    $core_type_id !== null ? __('Updating existing %s "%s"...') : __('Importing %s "%s"...'),
                    DatabaseInstanceType::getTypeName(),
                    $type_data['name']
                ),
                $progress_bar,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $type_input = Toolbox::addslashes_deep([
                'name'      => $type_data['name'],
                'comment'   => $type_data['comment'],
            ]);

            $type = $this->storeItem(DatabaseInstanceType::class, $core_type_id, $type_input);

            if ($type !== null) {
                $this->defineTargetItem(
                    'PluginDatabasesDatabaseType',
                    $type_data['id'],
                    DatabaseInstanceType::class,
                    $type->getID()
                );
            }
        }

        $this->output->write(PHP_EOL);
    }
}
