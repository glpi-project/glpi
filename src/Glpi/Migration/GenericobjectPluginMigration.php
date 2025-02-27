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
use Glpi\Asset\CustomFieldDefinition;
use Glpi\Asset\CustomFieldType\BooleanType;
use Glpi\Asset\CustomFieldType\DateTimeType;
use Glpi\Asset\CustomFieldType\DateType;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\NumberType;
use Glpi\Asset\CustomFieldType\StringType;
use Glpi\Asset\CustomFieldType\TextType;
use Glpi\Asset\CustomFieldType\URLType;
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
                'itemtype',
                'name',
                'comment',
                'is_active',
                'impact_icon',
                'date_mod',
                'date_creation',

                // Capacity fields
                'use_global_search',
                // Not optional anymore 'use_unicity',
                'use_history',
                'use_infocoms',
                'use_contracts',
                'use_documents',
                'use_links',
                'use_loans',
                'use_network_ports',
                'use_direct_connections',
                'use_projects',
                'use_itemdevices',
                'use_notepad',

                // Not handled fields (features are not existing in GLPI yet)
                // 'plugin_genericobject_typefamilies_id',
                // `is_helpdesk_visible`,

                // Obsolete fields (seems to be useless in the plugin)
                // 'entities_id',
                // 'use_menu_entry',
                // 'linked_itemtypes',

                // Plugin capacity fields (will not be handled in GLPI itself)
                // 'use_plugin_simcard',
                // 'use_plugin_treeview',
                // 'use_plugin_datainjection',
                // 'use_plugin_pdf',
                // 'use_plugin_order',
                // 'use_plugin_uninstall',
                // 'use_plugin_geninventorynumber',
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

        $count = 0;

        foreach ($this->types_iterator as $type_data) {
            $type_table = \getTableForItemType($type_data['itemtype']);
            $type_table_fields = array_column($this->db->listFields($type_table), 'Field');

            // Compute capacities
            $capacities = $this->getCapacities($type_data);

            // Compute profiles
            $profiles = \array_fill_keys($this->getAllProfilesIds(), 0);
            $profilerights_iterator = $this->db->request([
                'FROM'  => ProfileRight::getTable(),
                'WHERE' => [
                    // see `PluginGenericobjectProfile::getProfileNameForItemtype()`
                    'name' => preg_replace("/^glpi_/", "", $type_table),
                ],
            ]);
            foreach ($profilerights_iterator as $profileright_data) {
                $profiles[$profileright_data['profiles_id']] = $profileright_data['rights'];
            }

            // Compute translations
            $translations = [];
            // TODO Fetch translations from `/files/_plugins/genericobject/locales/` files

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
                    'fields_display' => [],
                    'date_creation'  => $type_data['date_creation'],
                    'date_mod'       => $type_data['date_mod'],
                ],
                reconciliation_criteria: [
                    'system_name' => $type_data['name'],
                ]
            );
            $this->asset_definitions[$type_data['id']] = $asset_definition;

            $is_outdated = \strtotime($type_data['date_mod']) < \strtotime($asset_definition->fields['date_mod']);

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
                        'translations'  => [],

                        // use type dates for freshness comparisons
                        'date_creation'  => $type_data['date_creation'],
                        'date_mod'       => $type_data['date_mod'],
                    ],
                    reconciliation_criteria: [
                        'system_name' => $category_system_name,
                    ]
                );
                $this->category_definitions[$type_data['id']] = $category_definition;
            }

            // Import the custom fields definition
            foreach ($type_table_fields as $type_table_field) {
                if (!$this->isACustomField($type_data['itemtype'], $type_table_field)) {
                    continue;
                }

                $custom_field_specs = $this->getCustomFieldSpecs($type_data['itemtype'], $type_table_field);

                $this->importItem(
                    CustomFieldDefinition::class,
                    input: $custom_field_specs + [
                        'assets_assetdefinitions_id' => $asset_definition->getID(),
                        'system_name'   => $type_table_field,
                        'field_options' => [],
                    ],
                    reconciliation_criteria: [
                        'assets_assetdefinitions_id' => $asset_definition->getID(),
                        'system_name' => $type_table_field,
                    ]
                );
            }

            // Update the fields display options
            if (!$is_outdated) {
                /* @var array<int, array{key: string, order:int, field_options:array<string, mixed>}> $fields_display */
                $fields_display = [];

                foreach ($type_table_fields as $type_table_field) {
                    if (
                        !$this->isAGenericField($type_table_field)
                        && !$this->isACustomField($type_data['itemtype'], $type_table_field)
                    ) {
                        continue;
                    }

                    $prefix = $this->isACustomField($type_data['itemtype'], $type_table_field) ? 'custom_' : '';

                    $fields_display[] = [
                        'key'           => $prefix . $type_table_field,
                        'order'         => count($fields_display) - 1,
                        'field_options' => [],
                    ];
                }
                $update_input = [
                    'id' => $asset_definition->getID(),
                    'fields_display' => $fields_display,
                ];
                if ($asset_definition->update($update_input) === false) {
                    $this->result->addMessage(
                        MessageType::Error,
                        sprintf(
                            __('Unable to update %s "%s" (%d).'),
                            AssetDefinition::getTypeName(1),
                            $asset_definition->getFriendlyName() ?: NOT_AVAILABLE,
                            $asset_definition->getID(),
                        )
                    );
                    throw new \RuntimeException('An error occured during the item update.');
                }
            }

            // Copy related data

            // TODO Add in profile `helpdesk_item_type` when required
            // TODO Copy history related to type ?

            $this->progress_indicator?->advance();
            $count++;
        }

        if ($count > 0) {
            $this->progress_indicator?->addMessage(
                MessageType::Success,
                sprintf(__('%d objects definitions successfully imported.'), $count)
            );
        }

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

                    // Copy generic fields
                    foreach ($asset_data as $field => $value) {
                        if ($this->isAGenericField($field)) {
                            $input[$field] = $value;
                            continue;
                        }

                        if ($this->isACustomField($type_data['itemtype'], $field)) {
                            $input['custom_' . $field] = $value;
                            continue;
                        }
                    }

                    // Copy mapped categories, models and types
                    foreach (['Model', 'Type', 'Category'] as $dropdown_suffix) {
                        $dropdown_type = $type_data['itemtype'] . $dropdown_suffix;
                        $dropdown_id   = $asset_data[\getForeignKeyFieldForItemType($dropdown_type)] ?? null;
                        if (
                            $dropdown_id > 0
                            && ($mapped_item = $this->getMappedItemTarget($dropdown_type, $dropdown_id)) !== null
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
                    $is_outdated = \strtotime($input['date_mod']) < \strtotime($asset->fields['date_mod']);

                    // Associate with groups
                    if (!$is_outdated) {
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

                        // TODO domains_id
                    }

                    $this->progress_indicator?->advance();
                    $count++;
                }

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

    /**
     * Return the list of capacities for the given GenericObject type.
     *
     * @param array<string, mixed> $type_data A row from the `glpi_plugin_genericobject_types` table.
     * @return array<int, class-string<\Glpi\Asset\Capacity\AbstractCapacity>>
     */
    private function getCapacities(array $type_data): array
    {
        $mapping = [
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

        $capacities = [];
        foreach ($mapping as $fieldname => $capacity) {
            if ($type_data[$fieldname]) {
                $capacities[] = $capacity;
            }
        }

        return $capacities;
    }

    /**
     * Check whether a field with the given name is a generic field.
     */
    private function isAGenericField(string $field): bool
    {
        return \in_array(
            $field,
            [
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
            ],
            true
        );
    }

    /**
     * Check whether a field with the given name is a custom field.
     *
     * @param class-string<\CommonDBTM> $itemtype   The generic object itemtype.
     * @param string                    $field      The field name.
     */
    private function isACustomField(string $itemtype, string $field): bool
    {
        if ($field === 'id') {
            return false;
        }

        if ($this->isAGenericField($field)) {
            return false;
        }

        $genericobject_definitions = $this->getGenericObjectFieldsDefinition($itemtype);
        if (
            \array_key_exists($field, $genericobject_definitions)
            && $genericobject_definitions[$field]['input_type'] === 'emptyspace'
        ) {
            // `emptyspace` is not a real field
            return false;
        }

        return !\in_array(
            $field,
            [
                // Dropdown values handled specificaly
                \getForeignKeyFieldForItemType($itemtype . 'Category'),
                \getForeignKeyFieldForItemType($itemtype . 'Model'),
                \getForeignKeyFieldForItemType($itemtype . 'Type'),

                // Group fields values will be converted into `Group_Item` entries
                'groups_id',
                'groups_id_tech',

                // Domain field value will be converted into a `Domain_Item` entry
                'domains_id',

                // Obsolete fields that are not imported
                'is_global',
                'is_helpdesk_visible',
                'notepad',
            ],
            true
        );
    }

    /**
     * Get the specifications related to a custom field.
     *
     * @param class-string<\CommonDBTM> $itemtype   The generic object itemtype.
     * @param string                    $field      The field name.
     *
     * @return array{label: string, type: class-string<\Glpi\Asset\CustomFieldType\AbstractType>}
     */
    private function getCustomFieldSpecs(string $itemtype, string $field): array
    {
        if (!$this->isACustomField($itemtype, $field)) {
            throw new \LogicException();
        }

        $genericobject_definitions = $this->getGenericObjectFieldsDefinition($itemtype);

        if (\array_key_exists($field, $genericobject_definitions)) {
            $genericobject_definition = $genericobject_definitions[$field];

            switch ($genericobject_definition['input_type']) {
                case 'bool':
                    $specs['type'] = BooleanType::class;
                    break;
                case 'date':
                    $specs['type'] = DateType::class;
                    break;
                case 'datetime':
                    $specs['type'] = DateTimeType::class;
                    break;
                case 'decimal':
                    $specs['type'] = NumberType::class;
                    break;
                case 'dropdown':
                    $specs['type'] = DropdownType::class;
                    $itemtype = \getItemtypeForForeignKeyField($field);
                    if (!\class_exists($itemtype)) {
                        throw new MigrationException(
                            \sprintf(
                                __('Custom dropdown migration is not supported. Unable to import the "%s" field.'),
                                $genericobject_definition['name']
                            ),
                            sprintf('Unable to import `%s` field.', $field)
                        );
                    }
                    $specs['itemtype'] = $itemtype;
                    break;
                case 'float':
                    $specs['type'] = NumberType::class;
                    break;
                case 'integer':
                    $specs['type'] = NumberType::class;
                    $specs['options'] = [];
                    foreach (['min', 'max', 'step'] as $opt) {
                        if (\array_key_exists($opt, $genericobject_definition)) {
                            $specs['options'][$opt] = $genericobject_definition[$opt];
                        }
                    }
                    break;
                case 'multitext':
                    $specs['type'] = TextType::class;
                    break;
                default:
                    $specs['type'] = StringType::class;
                    break;
            }

            // Compute translations
            $translations = [];
            // TODO Fetch translations from `/files/_plugins/genericobject/locales/` files

            return $specs + [
                'label'        => $genericobject_definition['name'],
                'translations' => $translations,
            ];
        }

        return match ($field) {
            // Native definitions from the plugin
            'creationdate' => [
                'label' => __('Creation date'),
                'type'  => DateType::class,
            ],
            'expirationdate' => [
                'label' => __('Expiration date'),
                'type'  => DateType::class,
            ],
            'url' => [
                'label' => __('URL'),
                'type'  => URLType::class,
            ],
            'other' => [
                'label' => __('Others'),
                'type'  => StringType::class,
            ],

            // Fallback definition
            default => [
                'label' => $field,
                'type'  => StringType::class,
            ]
        };
    }

    /**
     * Get the generic object fields definition from the plugin custom files.
     *
     * @return array<string, array{name: string, input_type: string, min?: int, max?: int, step?: int}>
     */
    private function getGenericObjectFieldsDefinition(string $itemtype): array
    {
        $system_name = \preg_replace('/^PluginGenericObject/', '', $itemtype);

        $constant_files = [
            sprintf('%s/genericobject/fields/field.constant.php', GLPI_PLUGIN_DOC_DIR),

            // The classname was computed using `ucfirst()`, therefore we are not able to know
            // if the initial first char was already uppercased ofr not, and we have to try both filenames.
            sprintf('%s/genericobject/fields/%s.constant.php', GLPI_PLUGIN_DOC_DIR, $system_name),
            sprintf('%s/genericobject/fields/%s.constant.php', GLPI_PLUGIN_DOC_DIR, \lcfirst($system_name)),
        ];

        $GO_FIELDS = [];
        // @phpstan-ignore closure.unusedUse
        (function () use (&$GO_FIELDS, $constant_files) {
            foreach ($constant_files as $constant_file) {
                if (\file_exists($constant_file)) {
                    include($constant_file);
                }
            }
        })();

        $valid_input_types = [
            'bool',
            'date',
            'datetime',
            'decimal',
            'dropdown',
            'emptyspace',
            'float',
            'integer',
            'multitext',
            'text',
        ];

        // Clean definitions to keep only valid values.
        $clean_definitions = [];
        // @phpstan-ignore foreach.emptyArray
        foreach ($GO_FIELDS as $key => $definition) {
            // @phpstan-ignore nullCoalesce.offset
            $name = $definition['name'] ?? null;
            if (!\is_string($name)) {
                $name = $key;
            }

            // @phpstan-ignore nullCoalesce.offset
            $input_type = $definition['input_type'] ?? 'none';
            if (!\in_array($input_type, $valid_input_types, true)) {
                throw new MigrationException(
                    \sprintf(
                        __('Unexpected input type "%s" for the "%s" field.'),
                        $input_type,
                        $name
                    ),
                    sprintf('Unexpected input type `%s` for the `%s` field.', $input_type, $key)
                );
            }

            $clean_definitions[$key] = [
                'name' => $name,
                'input_type' => $input_type,
            ];

            if ($definition['input_type'] === 'integer') {
                foreach (['min', 'max', 'step'] as $opt) {
                    // @phpstan-ignore nullCoalesce.offset
                    $opt_value = $definition[$opt] ?? null;
                    if (\is_int($opt_value)) {
                        $clean_definitions[$key][$opt] = $opt_value;
                    }
                }
            }
        }

        return $clean_definitions;
    }
}
