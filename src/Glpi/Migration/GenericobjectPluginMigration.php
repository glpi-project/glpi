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

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity\AllowedInGlobalSearchCapacity;
use Glpi\Asset\Capacity\HasContractsCapacity;
use Glpi\Asset\Capacity\HasDevicesCapacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Glpi\Asset\Capacity\HasLinksCapacity;
use Glpi\Asset\Capacity\HasNetworkPortCapacity;
use Glpi\Asset\Capacity\HasNotepadCapacity;
use Glpi\Asset\Capacity\HasPeripheralAssetsCapacity;
use Glpi\Asset\Capacity\IsProjectAssetCapacity;
use Glpi\Asset\Capacity\IsReservableCapacity;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Message\MessageType;
use Group_Item;
use Profile;
use ProfileRight;

/**
 * @final
 */
class GenericobjectPluginMigration extends AbstractPluginMigration
{
    /**
     * Mapping between GenericObject type capacity fields and capacity classes.
     * @var array<string, class-string<\Glpi\Asset\Capacity\AbstractCapacity>>
     */
    private const CAPACITIES_MAPPING = [
        'use_contracts'          => HasContractsCapacity::class,
        'use_direct_connections' => HasPeripheralAssetsCapacity::class,
        'use_documents'          => HasDocumentsCapacity::class,
        'use_global_search'      => AllowedInGlobalSearchCapacity::class,
        'use_history'            => HasHistoryCapacity::class,
        'use_infocoms'           => HasInfocomCapacity::class,
        'use_itemdevices'        => HasDevicesCapacity::class,
        'use_links'              => HasLinksCapacity::class,
        'use_loans'              => IsReservableCapacity::class,
        'use_network_ports'      => HasNetworkPortCapacity::class,
        'use_notepad'            => HasNotepadCapacity::class,
        'use_projects'           => IsProjectAssetCapacity::class,
    ];

    /**
     * GenericObject type table fields to copy during assets import.
     * @var array<int, string>
     */
    private const FIELDS_TO_COPY = [
        'comment',
        'contact',
        'contact_num',
        'date_creation',
        'date_mod',
        'entities_id',
        'is_deleted',
        'is_recursive',
        'is_template',
        'locations_id',
        'manufacturers_id',
        'name',
        'otherserial',
        'serial',
        'states_id',
        'template_name',
        'users_id',
        'users_id_tech',
    ];


    /**
     * Variable used to cache the list of all the existing profiles IDs.
     *
     * @var null|array<int, int>
     */
    private ?array $profiles_ids_cache = null;

    /**
     * Generic objects types definitions.
     */
    private iterable $types_iterator = [];

    /**
     * Imported asset definitions.
     *
     * @var array<int, \Glpi\Asset\AssetDefinition>
     */
    private array $asset_definitions = [];

    /**
     * Imported categories definitions.
     *
     * @var array<int, \Glpi\Dropdown\DropdownDefinition>
     */
    private array $category_definitions = [];

    protected function validatePrerequisites(): bool
    {
        $required_fields = [
            'glpi_plugin_genericobject_types' => [
                'id',
                // Obsolete field 'entities_id',
                'itemtype',
                'is_active',
                'name',
                'comment',
                'date_mod',
                'date_creation',
                'use_global_search',
                // 'use_unicity',
                'use_history',
                'use_infocoms',
                'use_contracts',
                'use_documents',
                // FIXME Should we handle the `is_helpdesk_visible` field ? 'use_tickets',
                'use_links',
                'use_loans',
                'use_network_ports',
                'use_direct_connections',
                // FIXME Handle 'use_plugin_datainjection',
                // FIXME Handle 'use_plugin_pdf',
                // FIXME Handle 'use_plugin_order',
                // FIXME Handle 'use_plugin_uninstall',
                // FIXME Handle 'use_plugin_geninventorynumber',
                // Obsolete field 'use_menu_entry',
                'use_projects',
                // FIXME Is it still used ? 'linked_itemtypes',
                // 'plugin_genericobject_typefamilies_id',
                'use_itemdevices',
                'impact_icon',
                'use_notepad',
                // FIXME Handle 'use_plugin_simcard',
                // FIXME Handle 'use_plugin_treeview',
            ],
        ];

        // Add required table fields depending on the glpi_plugin_genericobject_types entries
        if (
            $this->db->tableExists('glpi_plugin_genericobject_types')
            && $this->db->fieldExists('glpi_plugin_genericobject_types', 'itemtype')
        ) {
            $this->types_iterator = $this->db->request(['FROM' => 'glpi_plugin_genericobject_types']);
            foreach ($this->types_iterator as $type_data) {
                $item_table = \getTableForItemType($type_data['itemtype']);

                // Base fields that are always present.
                $required_fields[$item_table] = [
                    'id',
                    'entities_id',
                    'name',
                    'comment',
                    // Obsolete field 'notepad',
                    'date_mod',
                    'date_creation'
                ];

                // Dropdown tables related to the current object type
                foreach (['Category', 'Model', 'Type'] as $dropdown_suffix) {
                    $dropdown_classname = $type_data['itemtype'] . $dropdown_suffix;
                    if ($this->isDropdownUsed($type_data['itemtype'], $dropdown_classname)) {
                        $dropdown_table = \getTableForItemType($dropdown_classname);
                        $required_fields[$dropdown_table] = [
                            'id',
                            'name',
                            'comment',
                            'date_mod',
                            'date_creation'
                        ];
                    }
                }
            }
        }

        return $this->checkDbFieldsExists($required_fields);
    }

    protected function processMigration(): bool
    {
        // Init the progress bar
        $items_count = 0;
        $plugin_tables = $this->db->listTables('glpi\_plugin\_genericobject\_%');
        foreach ($plugin_tables as $plugin_table) {
            $items_count += $this->db->request(['COUNT' => 'count', 'FROM' => $plugin_table])->current()['count'];
        }
        $this->progress_indicator?->setMaxSteps($items_count);

        // Handle imports
        $success = $this->importTypeFamilies()
            && $this->importDefinitions()
            && $this->importDropdowns()
            && $this->importObjects();

        if ($success) {
            $this->progress_indicator?->setProgressBarMessage('');
            $this->progress_indicator?->finish();
        }

        return $success;
    }

    private function importTypeFamilies(): bool
    {
        if ($this->db->tableExists('glpi_plugin_genericobject_typefamilies')) {
            $families_count = $this->db->request(['COUNT' => 'count', 'FROM' => 'glpi_plugin_genericobject_typefamilies'])->current()['count'];

            if ($families_count > 0) {
                // FIXME Should we handle them? A family just adds a menu/breadcrumb level, nothing more.
                $this->result->addMessage(
                    MessageType::Notice,
                    __('The object types families have not been imported as they are not handled by GLPI.')
                );
            }

            $this->progress_indicator?->advance($families_count);
        }

        return true;
    }

    private function importDefinitions(): bool
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing object types...'));

        foreach ($this->types_iterator as $type_data) {
            // Compute capacities
            $capacities = [];
            foreach (self::CAPACITIES_MAPPING as $field => $capacity_class) {
                if ($type_data[$field]) {
                    $capacities[] = $capacity_class;
                }
            }

            // Compute profiles
            $profiles = \array_fill_keys($this->getAllProfilesIds(), 0);
            $profilerights_iterator = $this->db->request([
                'FROM'  => ProfileRight::getTable(),
                'WHERE' => [
                    // see `PluginGenericobjectProfile::getProfileNameForItemtype()`
                    'name' => preg_replace("/^glpi_/", "", \getTableForItemType($type_data['itemtype'])),
                ],
            ]);
            foreach ($profilerights_iterator as $profileright_data) {
                $profiles[$profileright_data['profiles_id']] = $profileright_data['rights'];
            }

            // Compute translations
            $translations = [];
            // TODO fetch translations from `/files/_plugins/genericobject/locales/` files

            // Compute fields display options
            $fields = [];
            // TODO Compute order based on the table fields

            // Import the asset definition
            $asset_definition = $this->importItem(
                AssetDefinition::class,
                input: [
                    'system_name'    => $type_data['name'],
                    'label'          => $type_data['name'],
                    'picture'        => $type_data['impact_icon'],
                    'comment'        => $type_data['comment'],
                    'is_active'      => $type_data['is_active'],
                    'capacities'     => $capacities,
                    'profiles'       => $profiles,
                    'translations'   => $translations,
                    'fields_display' => $fields,
                    'date_creation'  => $type_data['date_creation'],
                    'date_mod'       => $type_data['date_mod'],
                ],
                reconciliation_criteria: [
                    'system_name' => $type_data['name'],
                ]
            );
            $this->asset_definitions[$type_data['id']] = $asset_definition;

            // Import the Category dropdown definition
            if ($this->isDropdownUsed($type_data['itemtype'], $type_data['itemtype'] . 'Category')) {
                $category_system_name = $type_data['name'] . 'Category';

                $category_definition = $this->importItem(
                    DropdownDefinition::class,
                    input: [
                        'system_name'   => $category_system_name,
                        'label'         => sprintf(__('%s category'), $type_data['name']),
                        'is_active'     => true,
                        'profiles'      => \array_fill_keys($this->getAllProfilesIds(), READ | UPDATE | CREATE | PURGE),
                        'translations'  => [], // TODO Check if the plugin handle translations
                    ],
                    reconciliation_criteria: [
                        'system_name' => $category_system_name,
                    ]
                );
                $this->category_definitions[$type_data['id']] = $category_definition;
            }

            // Import the custom fields definition
            // TODO populate `glpi_assets_customfielddefinitions` using definitions from `/files/_plugins/genericobject/fields/` files

            // Copy related data
            // TODO Add in profile `helpdesk_item_type` when required
            // TODO Copy history related to type ?

            $this->progress_indicator?->advance();
        }

        $this->progress_indicator?->addMessage(
            MessageType::Success,
            sprintf(__('%d objects definitions successfully imported.'), $this->types_iterator->count())
        );

        return true;
    }

    private function importDropdowns(): bool
    {
        foreach ($this->types_iterator as $type_data) {
            $count = 0;

            $asset_definition = $this->asset_definitions[$type_data['id']] ?? null;
            if (!($asset_definition instanceof AssetDefinition)) {
                throw new \LogicException('The asset definition is expected to be imported.');
            }

            // Import categories
            $category_classname = $type_data['itemtype'] . 'Category';
            if ($this->isDropdownUsed($type_data['itemtype'], $category_classname)) {
                $this->progress_indicator->setProgressBarMessage(
                    sprintf(__('Importing "%s" categories...'), $type_data['name'])
                );

                $category_definition = $this->category_definitions[$type_data['id']] ?? null;
                if (!($category_definition instanceof DropdownDefinition)) {
                    throw new \LogicException('The category definition is expected to be imported.');
                }

                $categories_iterator = $this->db->request(['FROM' => \getTableForItemType($category_classname)]);
                foreach ($categories_iterator as $category_data) {
                    $category = $this->importItem(
                        $category_definition->getDropdownClassName(),
                        input: [
                            'name'          => $category_data['name'],
                            'comment'       => $category_data['comment'],
                            'entities_id'   => 0,
                            'is_recursive'  => true,
                            'date_mod'      => $category_data['date_mod'],
                            'date_creation' => $category_data['date_creation'],
                        ],
                        reconciliation_criteria: [
                            'name' => $category_data['name'],
                        ]
                    );

                    $this->mapItem(
                        $category_classname,
                        $category_data['id'],
                        $category_definition->getDropdownClassName(),
                        $category->getID()
                    );

                    // TODO Copy history ?

                    $this->progress_indicator?->advance();
                }

                $count += $categories_iterator->count();
            }

            // Import models
            $model_classname = $type_data['itemtype'] . 'Model';
            if ($this->isDropdownUsed($type_data['itemtype'], $model_classname)) {
                $this->progress_indicator->setProgressBarMessage(
                    sprintf(__('Importing "%s" models...'), $type_data['name'])
                );

                $models_iterator = $this->db->request(['FROM' => \getTableForItemType($model_classname)]);
                foreach ($models_iterator as $model_data) {
                    $model = $this->importItem(
                        $asset_definition->getAssetModelClassName(),
                        input: [
                            'name'          => $model_data['name'],
                            'comment'       => $model_data['comment'],
                            'date_mod'      => $model_data['date_mod'],
                            'date_creation' => $model_data['date_creation'],
                        ],
                        reconciliation_criteria: [
                            'name' => $model_data['name'],
                        ]
                    );

                    $this->mapItem(
                        $model_classname,
                        $model_data['id'],
                        $asset_definition->getAssetModelClassName(),
                        $model->getID()
                    );

                    // TODO Copy history ?

                    $this->progress_indicator?->advance();
                }

                $count += $models_iterator->count();
            }

            // Import types
            $type_classname = $type_data['itemtype'] . 'Type';
            if ($this->isDropdownUsed($type_data['itemtype'], $type_classname)) {
                $this->progress_indicator->setProgressBarMessage(
                    sprintf(__('Importing "%s" types...'), $type_data['name'])
                );

                $types_iterator = $this->db->request(['FROM' => \getTableForItemType($type_classname)]);
                foreach ($types_iterator as $type_data) {
                    $type = $this->importItem(
                        $asset_definition->getAssetTypeClassName(),
                        input: [
                            'name'          => $type_data['name'],
                            'comment'       => $type_data['comment'],
                            'date_mod'      => $type_data['date_mod'],
                            'date_creation' => $type_data['date_creation'],
                        ],
                        reconciliation_criteria: [
                            'name' => $type_data['name'],
                        ]
                    );

                    $this->mapItem(
                        $type_classname,
                        $type_data['id'],
                        $asset_definition->getAssetTypeClassName(),
                        $type->getID()
                    );

                    // TODO Copy history ?

                    $this->progress_indicator?->advance();
                }

                $count += $types_iterator->count();
            }

            if ($count > 0) {
                $this->progress_indicator?->addMessage(
                    MessageType::Success,
                    sprintf(__('%d "%s" dropdown entries successfully imported.'), $count, $type_data['name'])
                );
            }
        }

        return true;
    }

    private function importObjects(): bool
    {
        foreach ($this->types_iterator as $type_data) {
            $this->progress_indicator->setProgressBarMessage(
                sprintf(__('Importing "%s" objects...'), $type_data['name'])
            );

            $asset_definition = $this->asset_definitions[$type_data['id']] ?? null;
            if (!($asset_definition instanceof AssetDefinition)) {
                throw new \LogicException('The asset definition is expected to be imported.');
            }

            $count  = 0;
            $offset = 0;
            $limit  = 500;
            do {
                $assets_iterator = $this->db->request([
                    'FROM' => \getTableForItemType($type_data['itemtype']),
                    'OFFSET' => $offset,
                    'LIMIT'  => $limit,
                ]);

                foreach ($assets_iterator as $asset_data) {
                    $input = [];

                    // Copy base fields
                    foreach (self::FIELDS_TO_COPY as $field) {
                        if (\array_key_exists($field, $asset_data)) {
                            $input[$field] = $asset_data[$field];
                        }
                    }

                    // Copy mapped models and types
                    foreach (['Model', 'Type'] as $dropdown_suffix) {
                        $dropdowntype = $type_data['itemtype'] . $dropdown_suffix;
                        $dropdown_id  = $asset_data[\getForeignKeyFieldForItemType($dropdowntype)] ?? null;
                        if (
                            $dropdown_id > 0
                            && ($mapped_item = $this->getMappedItemTarget($dropdowntype, $dropdown_id)) !== null
                        ) {
                            $input[\getForeignKeyFieldForItemType($mapped_item['itemtype'])] = $mapped_item['items_id'];
                        }
                    }

                    // Create the asset
                    $asset = $this->importItem(
                        $asset_definition->getAssetClassName(),
                        input: $input,
                        // Consider an asset with the same name, in the same entity, and created at the same datetime
                        // as the same as the asset we are importing.
                        reconciliation_criteria: [
                            'name'          => $asset_data['name'],
                            'entities_id'   => $asset_data['entities_id'],
                            'date_creation' => $asset_data['date_creation'],
                        ]
                    );

                    // Associate with groups
                    $groups_fields_mapping = [
                        'groups_id'      => Group_Item::GROUP_TYPE_NORMAL,
                        'groups_id_tech' => Group_Item::GROUP_TYPE_TECH,
                    ];
                    foreach ($groups_fields_mapping as $group_field => $group_type) {
                        if (\array_key_exists($group_field, $asset_data) && $asset_data[$group_field] > 0) {
                            $group_input = [
                                'groups_id' => $asset_data[$group_field],
                                'itemtype'  => $asset::class,
                                'items_id'  => $asset->getID(),
                                'type'      => $group_type,
                            ];
                            $this->importItem(
                                Group_Item::class,
                                input: $group_input,
                                reconciliation_criteria: $group_input,
                            );
                        }
                    }

                    // TODO plugin_genericobject_{xyz}categories_id

                    // TODO domains_id, expirationdate, is_global, other, url

                    $this->progress_indicator?->advance();
                }

                $count++;
                $offset += $limit;
            } while ($assets_iterator->count() > 0);

            if ($count > 0) {
                $this->progress_indicator?->addMessage(
                    MessageType::Success,
                    sprintf(__('%d "%s" objects successfully imported.'), $count, $type_data['name'])
                );
            }
        }

        return true;
    }

    /**
     * Checks whether the given dropdown is used by for the given itemtype.
     *
     * @param class-string<\CommonDBTM> $itemtype
     * @param class-string<\CommonDBTM> $dropdowntype
     */
    private function isDropdownUsed(string $itemtype, string $dropdowntype): bool
    {
        $item_table = \getTableForItemType($itemtype);

        return $this->db->tableExists($item_table)
            && $this->db->fieldExists($item_table, \getForeignKeyFieldForItemType($dropdowntype));
    }

    /**
     * Get the list of all existing profiles IDs.
     *
     * @return array<int, int>
     */
    private function getAllProfilesIds(): array
    {
        if ($this->profiles_ids_cache === null) {
            $profiles_iterator = $this->db->request([
                'SELECT' => Profile::getIndexName(),
                'FROM'   => Profile::getTable(),
            ]);
            $this->profiles_ids_cache = \array_column(\iterator_to_array($profiles_iterator), 'id');
        }

        return $this->profiles_ids_cache;
    }
}
