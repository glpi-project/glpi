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
use CommonDropdown;
use Domain_Item;
use DropdownTranslation;
use FieldUnicity;
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetModel;
use Glpi\Asset\AssetType;
use Glpi\Asset\Capacity\AbstractCapacity;
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
use Glpi\Asset\CustomFieldType\AbstractType;
use Glpi\Asset\CustomFieldType\BooleanType;
use Glpi\Asset\CustomFieldType\DateTimeType;
use Glpi\Asset\CustomFieldType\DateType;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\NumberType;
use Glpi\Asset\CustomFieldType\StringType;
use Glpi\Asset\CustomFieldType\TextType;
use Glpi\Asset\CustomFieldType\URLType;
use Glpi\Dropdown\Dropdown;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Message\MessageType;
use Group_Item;
use LogicException;
use Override;
use Profile;
use ProfileRight;
use RuntimeException;
use Toolbox;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\strtotime;

/**
 * @final
 */
class GenericobjectPluginMigration extends AbstractPluginMigration
{
    /**
     * Variable used to cache the list of the genericobject main itemtypes.
     *
     * @var null|array<int, string>
     */
    private ?array $main_itemtypes = null;

    /**
     * Variable used to cache the list of all the existing profiles IDs.
     *
     * @var null|array<int, int>
     */
    private ?array $profiles_ids_cache = null;

    /**
     * Generic objects types definitions iterator.
     */
    private ?iterable $types_iterator = null;

    /**
     * Imported asset definitions.
     *
     * @var array<string, AssetDefinition>
     */
    private array $asset_definitions = [];

    /**
     * Imported categories definitions.
     *
     * @var array<string, DropdownDefinition>
     */
    private array $dropdown_definitions = [];

    #[Override]
    protected function getHasBeenExecutedConfigurationKey(): string
    {
        return 'glpi_11_assets_migration';
    }

    #[Override]
    protected function getMainPluginTables(): array
    {
        return ['glpi_plugin_genericobject_objects'];
    }

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
            foreach ($this->getGenericobjectTypesIterator() as $type_data) {
                $item_table = $this->getExpectedTableForPluginClassName($type_data['itemtype']);

                // Add base fields for main itemtypes
                $required_fields[$item_table] = [
                    'id',
                    'entities_id',
                    'name',
                    'comment',
                    'date_mod',
                    'date_creation',
                ];

                if (!$this->db->tableExists($item_table)) {
                    continue;
                }

                $item_table_fields = array_column($this->db->listFields($item_table), 'Field');
                foreach ($item_table_fields as $item_table_field) {
                    if (!$this->isAGenericObjectFkeyField($item_table_field)) {
                        continue;
                    }

                    $peer_type  = $this->getExpectedClassNameForPluginTable(
                        \getTableNameForForeignKeyField($item_table_field)
                    );

                    if (!$this->isAGenericObjectDropdownItemtype($peer_type)) {
                        continue;
                    }

                    $peer_table = $this->getExpectedTableForPluginClassName($peer_type);

                    if (\array_key_exists($peer_table, $required_fields)) {
                        continue;
                    }

                    // Add base fields for dropdown itemtypes
                    $required_fields[$peer_table] = [
                        'id',
                        'name',
                        'comment',
                        'date_mod',
                        'date_creation',
                    ];
                }
            }
        }

        return $this->checkDbFieldsExists($required_fields);
    }

    protected function processMigration(): bool
    {
        // Init the progress bar
        $items_count = 0;
        $plugin_tables = \array_column(
            \iterator_to_array($this->db->listTables('glpi\_plugin\_genericobject\_%')),
            'TABLE_NAME'
        );
        foreach ($plugin_tables as $plugin_table) {
            $items_count++; // Most tables correspond to a definition to create
            $items_count += $this->db->request(['COUNT' => 'count', 'FROM' => $plugin_table])->current()['count'];
        }
        $this->progress_indicator?->setMaxSteps($items_count);

        // Handle imports
        $success = $this->importTypeFamilies()
            && $this->importDropdownDefinitions()
            && $this->importAssetsDefinitions()
            && $this->updateSearchOptions()
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

    private function importDropdownDefinitions(): bool
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing dropdowns definitions...'));

        $count = 0;

        $profiles_ids = $this->getAllProfilesIds();

        $plugin_custom_tables = \array_column(
            \iterator_to_array(
                $this->db->listTables(
                    'glpi\_plugin\_genericobject\_%',
                    [
                        'NOT' => [
                            'TABLE_NAME' => [
                                'glpi_plugin_genericobject_types',
                                'glpi_plugin_genericobject_typefamilies',
                            ],
                        ],
                    ]
                )
            ),
            'TABLE_NAME'
        );
        foreach ($plugin_custom_tables as $plugin_table) {
            $itemtype = $this->getExpectedClassNameForPluginTable($plugin_table);

            if (!$this->isAGenericObjectDropdownItemtype($itemtype)) {
                continue;
            }

            $dropdown_system_name = \ucfirst(preg_replace('/^PluginGenericobject/', '', $itemtype));

            // Compute translations
            $translations = [];
            // FIXME Fetch translations from `/files/_plugins/genericobject/locales/` files

            $dropdown_definition = $this->importItem(
                DropdownDefinition::class,
                input: [
                    'system_name'   => $dropdown_system_name,
                    'label'         => $dropdown_system_name,
                    'is_active'     => true,
                    'profiles'      => \array_fill_keys($profiles_ids, READ | UPDATE | CREATE | PURGE),
                    'translations'  => $translations,
                ],
                reconciliation_criteria: [
                    'system_name' => $dropdown_system_name,
                ]
            );

            $this->dropdown_definitions[$itemtype] = $dropdown_definition;

            // FIXME Copy history, display preferences and saved searches ?
            // This requires the implementation of a mapping between plugin SO and generic asset SO.

            $this->progress_indicator?->advance();
            $count++;
        }

        if ($count > 0) {
            $this->progress_indicator?->addMessage(
                MessageType::Success,
                sprintf(__('%d dropdowns definitions successfully imported.'), $count)
            );
        }

        return true;
    }

    private function importAssetsDefinitions(): bool
    {
        $this->progress_indicator?->setProgressBarMessage(__('Importing assets definitions...'));

        $count          = 0;
        $outdated_types = [];

        $profiles_ids   = $this->getAllProfilesIds();

        // First, import the basic definition.
        foreach ($this->getGenericobjectTypesIterator() as $type_data) {
            $reconciliation_criteria = ['system_name' => $type_data['name']];

            // Check if the definition is outdated before trying to import it.
            // If it is outdated, we must not try to handle its child items (e.g. custom fields definitions) import.
            $existing_definition = new AssetDefinition();
            if (
                $existing_definition->getFromDBByCrit($reconciliation_criteria)
                && strtotime($type_data['date_mod']) < strtotime($existing_definition->fields['date_mod'])
            ) {
                $this->result->markItemAsReused(AssetDefinition::class, $existing_definition->getID());
                $this->result->addMessage(
                    MessageType::Debug,
                    sprintf(
                        __('%s "%s" (%d) is most recent on GLPI side, its update has been skipped.'),
                        AssetDefinition::getTypeName(1),
                        $existing_definition->getFriendlyName() ?: NOT_AVAILABLE,
                        $existing_definition->getID(),
                    )
                );
                $outdated_types[] = $type_data['itemtype'];
                $this->asset_definitions[$type_data['itemtype']] = $existing_definition;
                $this->progress_indicator?->advance();
                continue;
            }

            $plugin_itemtype = $type_data['itemtype'];

            // Compute capacities
            $capacities = $this->getCapacities($type_data);

            // Compute profiles
            $profiles = \array_fill_keys($profiles_ids, 0);
            $profilerights_iterator = $this->db->request([
                'FROM'  => ProfileRight::getTable(),
                'WHERE' => [
                    // see `PluginGenericobjectProfile::getProfileNameForItemtype()`
                    'name' => preg_replace('/^glpi_/', '', $this->getExpectedTableForPluginClassName($plugin_itemtype)),
                ],
            ]);
            foreach ($profilerights_iterator as $profileright_data) {
                $profiles[$profileright_data['profiles_id']] = $profileright_data['rights'];
            }

            // Try to copy the impact icon file
            $picture = Toolbox::savePicture(
                sprintf(
                    '%s/genericobject/impact_icons/%s_%s',
                    GLPI_PLUGIN_DOC_DIR,
                    $type_data['itemtype'],
                    $type_data['impact_icon'],
                ),
                uniq_prefix: $type_data['itemtype'],
                keep_src: true // do not delete the initial file
            );
            if ($picture === false) {
                $picture = null;
            }

            // Compute translations
            $translations = [];
            // FIXME Fetch translations from `/files/_plugins/genericobject/locales/` files

            // Import the asset definition
            $asset_definition = $this->importItem(
                AssetDefinition::class,
                input: [
                    'system_name'    => $type_data['name'],
                    'label'          => $type_data['name'],
                    'picture'        => $picture,
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
                    'system_name' => $reconciliation_criteria,
                ]
            );
            $this->asset_definitions[$type_data['itemtype']] = $asset_definition;

            $count++;
        }

        // Second, create custom fields, copy related data, ...
        // This must be done in a second time to be sure that any definition targeted by a foreign key
        // will already be created when the corresponding custom field definition will be created.
        foreach ($this->asset_definitions as $plugin_itemtype => $asset_definition) {
            if (\in_array($plugin_itemtype, $outdated_types)) {
                // This definition is outdated, skip handling of child items.
                continue;
            }

            $item_table = $this->getExpectedTableForPluginClassName($plugin_itemtype);
            $item_table_fields = $this->db->listFields($item_table, false);

            // Import the custom fields definition
            foreach ($item_table_fields as $item_table_field_specs) {
                $item_table_field = $item_table_field_specs['Field'];

                if (!$this->isACustomField($plugin_itemtype, $item_table_field)) {
                    continue;
                }

                $custom_field_specs = $this->getCustomFieldSpecs(
                    $plugin_itemtype,
                    $item_table_field,
                    $item_table_field_specs['Type']
                );

                $this->importItem(
                    CustomFieldDefinition::class,
                    input: $custom_field_specs + [
                        'assets_assetdefinitions_id' => $asset_definition->getID(),
                        'system_name'   => $custom_field_specs['system_name'],
                        'field_options' => [],
                    ],
                    reconciliation_criteria: [
                        'assets_assetdefinitions_id' => $asset_definition->getID(),
                        'system_name' => $custom_field_specs['system_name'],
                    ]
                );
            }

            // Reload the definition to refresh its custom fields definition cache
            $asset_definition->getFromDB($asset_definition->getID());

            // Update the fields display options
            $form_fields = array_keys($asset_definition->getAllFields());

            $fields_display = [];
            $field_options  = [];

            foreach ($item_table_fields as $item_table_field_specs) {
                $item_table_field = $item_table_field_specs['Field'];

                if (in_array($item_table_field, ['groups_id', 'groups_id_tech'])) {
                    // Specific case for group fields.
                    // They are "virtual" fields, so they do not have a real target fields.
                    // However, they must be defined in the `fields_display` configuration.
                    $target_field = $item_table_field;
                } else {
                    $target_field = $this->getTargetField($plugin_itemtype, $item_table_field);
                }

                if ($target_field === null || !\in_array($target_field, $form_fields, true)) {
                    continue;
                }

                $fields_display[] = $target_field;
                $field_options[$target_field] = [];
            }

            $update_input = [
                'id' => $asset_definition->getID(),
                'fields_display' => $fields_display,
                'field_options'  => $field_options,
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
                throw new RuntimeException('An error occurred during the item update.');
            }

            // Update profiles configuration
            $profiles_iterator = $this->db->request([
                'FROM' => Profile::getTable(),
            ]);
            foreach ($profiles_iterator as $profile_data) {
                $helpdesk_item_types = \importArrayFromDB($profile_data['helpdesk_item_type']);
                if (\in_array($plugin_itemtype, $helpdesk_item_types)) {
                    $helpdesk_item_types[] = $asset_definition->getAssetClassName();

                    $input = [
                        'id'                 => $profile_data['id'],
                        'helpdesk_item_type' => $helpdesk_item_types,
                    ];
                    $profile = new Profile();
                    if ($profile->update($input) === false) {
                        $this->result->addMessage(
                            MessageType::Error,
                            sprintf(
                                __('Unable to update %s "%s" (%d).'),
                                Profile::getTypeName(1),
                                $profile->getFriendlyName() ?: NOT_AVAILABLE,
                                $profile_data['id'],
                            )
                        );
                        throw new RuntimeException('An error occurred during the item update.');
                    }
                }
            }

            // Import fields unicities
            $this->copyItems(
                FieldUnicity::class,
                where: ['itemtype' => $plugin_itemtype],
                replacements: [
                    [
                        'field' => 'itemtype',
                        'from'  => $plugin_itemtype,
                        'to'    => $asset_definition->getAssetClassName(),
                    ],
                ]
            );

            // FIXME Copy history, display preferences and saved searches, for main definition, model and type ?
            // This requires the implementation of a mapping between plugin SO and generic asset SO.
        }

        if ($count > 0) {
            $this->progress_indicator?->advance($count);
            $this->progress_indicator?->addMessage(
                MessageType::Success,
                sprintf(__('%d assets definitions successfully imported.'), $count)
            );
        }

        return true;
    }

    private function updateSearchOptions(): bool
    {
        // FIXME Update display preferences and saved searches using a genericobject type meta criteria ?
        $this->result->addMessage(
            MessageType::Notice,
            __('The display preferences and saved searches related to a `genericobject` object type must be recreated manually.')
        );

        return true;
    }

    private function importDropdowns(): bool
    {
        $dropdown_mapping = [];

        foreach ($this->dropdown_definitions as $plugin_itemtype => $dropdown_definition) {
            $dropdown_mapping[$plugin_itemtype] = $dropdown_definition->getDropdownClassName();
        }

        foreach ($this->getGenericobjectTypesIterator() as $type_data) {
            $asset_definition = $this->asset_definitions[$type_data['itemtype']] ?? null;
            if (!($asset_definition instanceof AssetDefinition)) {
                throw new LogicException('The asset definition is expected to be imported.');
            }

            $plugin_model_itemtype = $type_data['itemtype'] . 'Model';
            if ($this->db->tableExists($this->getExpectedTableForPluginClassName($plugin_model_itemtype))) {
                $dropdown_mapping[$plugin_model_itemtype] = $asset_definition->getAssetModelClassName();
            }

            $plugin_type_itemtype = $type_data['itemtype'] . 'Type';
            if ($this->db->tableExists($this->getExpectedTableForPluginClassName($plugin_type_itemtype))) {
                $dropdown_mapping[$plugin_type_itemtype] = $asset_definition->getAssetTypeClassName();
            }
        }

        foreach ($dropdown_mapping as $plugin_itemtype => $dropdown_class) {
            $this->progress_indicator?->setProgressBarMessage(
                sprintf(__('Importing %s...'), $dropdown_class::getTypeName())
            );

            if (!is_a($dropdown_class, CommonDropdown::class, true)) {
                throw new LogicException(sprintf('Unexpected `%s` class.', $dropdown_class));
            }

            $dropdown_iterator = $this->db->request(['FROM' => $this->getExpectedTableForPluginClassName($plugin_itemtype)]);

            foreach ($dropdown_iterator as $dropdown_data) {
                $reconciliation_criteria = [
                    'name' => $dropdown_data['name'],
                ] + $dropdown_class::getSystemSQLCriteria();

                // Check if the dropdown is outdated before trying to import it.
                // If it is outdated, we must not try to handle its child items (e.g. translations) import.
                $existing_dropdown = new $dropdown_class();
                if (
                    $existing_dropdown->getFromDBByCrit($reconciliation_criteria)
                    && strtotime($dropdown_data['date_mod']) < strtotime($existing_dropdown->fields['date_mod'])
                ) {
                    $this->result->markItemAsReused($dropdown_class, $existing_dropdown->getID());
                    $this->result->addMessage(
                        MessageType::Debug,
                        sprintf(
                            __('%s "%s" (%d) is most recent on GLPI side, its update has been skipped.'),
                            $dropdown_class::getTypeName(1),
                            $existing_dropdown->getFriendlyName() ?: NOT_AVAILABLE,
                            $existing_dropdown->getID(),
                        )
                    );
                    $this->progress_indicator?->advance();
                    continue;
                }

                // Import the dropdown
                $dropdown = $this->importItem(
                    $dropdown_class,
                    input: [
                        'name'          => $dropdown_data['name'],
                        'comment'       => $dropdown_data['comment'],
                        'entities_id'   => $dropdown_data['entities_id'] ?? 0,
                        'is_recursive'  => $dropdown_data['is_recursive'] ?? true,
                        'date_mod'      => $dropdown_data['date_mod'],
                        'date_creation' => $dropdown_data['date_creation'],
                    ],
                    reconciliation_criteria: $reconciliation_criteria
                );

                $this->mapItem(
                    $plugin_itemtype,
                    $dropdown_data['id'],
                    $dropdown_class,
                    $dropdown->getID()
                );

                // Import dropdown translations ?
                $this->copyItems(
                    DropdownTranslation::class,
                    where: [
                        'itemtype' => $plugin_itemtype,
                        'items_id' => $dropdown_data['id'],
                    ],
                    replacements: [
                        [
                            'field' => 'itemtype',
                            'from'  => $plugin_itemtype,
                            'to'    => $dropdown_class,
                        ],
                        [
                            'field' => 'items_id',
                            'from'  => $dropdown_data['id'],
                            'to'    => $dropdown->getID(),
                        ],
                    ]
                );

                // FIXME Copy history ?
                // This requires the implementation of a mapping between plugin SO and generic asset SO.

                $this->progress_indicator?->advance();
            }

            if ($dropdown_iterator->count() > 0) {
                $this->progress_indicator?->addMessage(
                    MessageType::Success,
                    sprintf(
                        __('%d "%s" dropdown entries successfully imported.'),
                        $dropdown_iterator->count(),
                        $dropdown_class::getTypeName()
                    )
                );
            }
        }

        return true;
    }

    private function importObjects(): bool
    {
        /**
         * @var array<int, array{asset: Asset, fkeys_specs: array<int, array{field: string, peer_itemtype: class-string<CommonDBTM>, peer_items_id: int}>}> $fkeys_to_process
         */
        $fkeys_to_process = [];

        foreach ($this->getGenericobjectTypesIterator() as $type_data) {
            $this->progress_indicator?->setProgressBarMessage(
                sprintf(__('Importing "%s" objects...'), $type_data['name'])
            );

            $asset_definition = $this->asset_definitions[$type_data['itemtype']] ?? null;
            if (!($asset_definition instanceof AssetDefinition)) {
                throw new LogicException('The asset definition is expected to be imported.');
            }

            $asset_class = $asset_definition->getAssetClassName();

            $item_table = $this->getExpectedTableForPluginClassName($type_data['itemtype']);

            $count  = 0;
            $offset = 0;
            $limit  = 500;
            do {
                $assets_iterator = $this->db->request([
                    'FROM'   => $item_table,
                    'OFFSET' => $offset,
                    'LIMIT'  => $limit,
                ]);

                foreach ($assets_iterator as $asset_data) {
                    $input = [];

                    $asset_fkeys_specs = [];

                    // Copy generic and custom fields values
                    foreach ($asset_data as $field => $value) {
                        if (!$this->isAGenericField($field) && !$this->isACustomField($type_data['itemtype'], $field)) {
                            continue;
                        }

                        $target_field = $this->getTargetField($type_data['itemtype'], $field);

                        if ($value > 0 && $this->isAGenericObjectFkeyField($field)) {
                            $peer_source_type = $this->getExpectedClassNameForPluginTable(\getTableNameForForeignKeyField($field));
                            $peer_target_type = $this->getTargetItemtype($peer_source_type);

                            if (\is_a($peer_target_type, Asset::class, true)) {
                                // Foreign key corresponds to another asset.
                                // All assets must be imported before being able to map the value.
                                $asset_fkeys_specs[] = [
                                    'field'          => $target_field,
                                    'peer_itemtype'  => $peer_source_type,
                                    'peer_items_id'  => (int) $value,
                                ];
                                continue;
                            }

                            $value = $this->getMappedItemTarget($peer_source_type, (int) $value)['items_id'] ?? 0;
                        } elseif ($value === null && \isForeignKeyField($target_field)) {
                            // GLPI foreign key fields are most of the time not nullable and takes a `0` value when
                            // there is no peer item linked.
                            $value = 0;
                        }

                        $input[$target_field] = $value;
                    }

                    // Copy mapped models and types
                    foreach (['Model', 'Type'] as $dropdown_suffix) {
                        $dropdown_type = $type_data['itemtype'] . $dropdown_suffix;
                        $dropdown_id   = $asset_data[\getForeignKeyFieldForItemType($dropdown_type)] ?? null;
                        if (
                            $dropdown_id > 0
                            && ($mapped_item = $this->getMappedItemTarget($dropdown_type, $dropdown_id)) !== null
                        ) {
                            $input[\getForeignKeyFieldForItemType($mapped_item['itemtype'])] = $mapped_item['items_id'];
                        }
                    }

                    // Import the asset
                    $asset = $this->importItem(
                        $asset_class,
                        input: $input,
                        options: [
                            'disable_infocom_creation' => true,
                        ]
                    );

                    $this->mapItem(
                        $type_data['itemtype'],
                        $asset_data['id'],
                        $asset_class,
                        $asset->getID()
                    );

                    // Store foreign keys to process
                    if (count($asset_fkeys_specs) > 0) {
                        $fkeys_to_process[] = [
                            'asset'       => $asset,
                            'fkeys_specs' => $asset_fkeys_specs,
                        ];
                    }

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
                            );
                        }
                    }

                    // Associate with domains
                    if (\array_key_exists('domains_id', $asset_data) && $asset_data['domains_id'] > 0) {
                        $domain_input = [
                            'domains_id' => $asset_data['domains_id'],
                            'itemtype'   => $asset::class,
                            'items_id'   => $asset->getID(),
                        ];
                        $this->importItem(
                            Domain_Item::class,
                            input: $domain_input,
                        );
                    }

                    // Copy relation/child items
                    $this->copyPolymorphicConnexityItems(
                        $type_data['itemtype'],
                        $asset_data['id'],
                        $asset::class,
                        $asset->getID()
                    );

                    // FIXME Copy history ?
                    // This requires the implementation of a mapping between plugin SO and generic asset SO.

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

        if (count($fkeys_to_process) > 0) {
            $this->progress_indicator?->setProgressBarMessage(__('Importing objects relations...'));

            foreach ($fkeys_to_process as $asset_fkeys) {
                $asset       = $asset_fkeys['asset'];
                $fkeys_specs = $asset_fkeys['fkeys_specs'];

                $input = ['id' => $asset->getID()];

                foreach ($fkeys_specs as $fkey_specs) {
                    $input[$fkey_specs['field']] = $this->getMappedItemTarget($fkey_specs['peer_itemtype'], $fkey_specs['peer_items_id'])['items_id'] ?? null;
                }

                if (!$asset->update($input, history: false)) {
                    throw new MigrationException(
                        sprintf(
                            __('Unable to update %s "%s" (%d).'),
                            $asset::getTypeName(1),
                            $asset->getFriendlyName() ?: NOT_AVAILABLE,
                            $asset->getID(),
                        ),
                        'Update operation failed.'
                    );
                }
            }

            $this->progress_indicator?->addMessage(
                MessageType::Success,
                __('Objects relations successfully imported.')
            );
        }

        return true;
    }

    /**
     * Get the generic object types iterator.
     */
    private function getGenericobjectTypesIterator(): iterable
    {
        if ($this->types_iterator === null) {
            $this->types_iterator = $this->db->request(['FROM' => 'glpi_plugin_genericobject_types']);
        }

        return $this->types_iterator;
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
     * @return array<int, array{name: class-string<AbstractCapacity>}>
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
                $capacities[] = ['name' => $capacity];
            }
        }

        return $capacities;
    }

    /**
     * Returns the expected class name for a plugin table.
     */
    private function getExpectedClassNameForPluginTable(string $table): string
    {
        $table_matches = [];
        if (preg_match('/^glpi_plugin_genericobject_(?<itemtype_chunk>.+)$/', $table, $table_matches) !== 1) {
            throw new LogicException(
                sprintf('Table `%s` is not a Genericobject table.', $table)
            );
        }

        $chunks = \explode('_', $table_matches['itemtype_chunk']);
        $chunks = \array_map('getSingular', $chunks);
        $chunks = \array_map('ucfirst', $chunks);

        return 'PluginGenericobject' . implode('_', $chunks);
    }

    /**
     * Returns the expected table for a genericobject plugin class name.
     */
    private function getExpectedTableForPluginClassName(string $classname): string
    {
        $classname_matches = [];
        if (preg_match('/^PluginGenericobject(?<itemtype_chunk>.+)$/', $classname, $classname_matches) !== 1) {
            throw new LogicException(
                sprintf('`%s` is not a Genericobject class.', $classname)
            );
        }

        $expected_table = \getTableForItemType($classname);

        if (!$this->db->tableExists($expected_table)) {
            // Try to match with an existing table if the expected table does not exists.

            // Sometimes, the plugin table name has only its last chunk pluralized
            // (e.g. `glpi_plugin_genericobject_item_states` instead of `glpi_plugin_genericobject_items_states`).
            // It means that `\getTableForItemType(\getItemTypeForTable($table)))` result differs
            // from the original `$table` value.
            $fallback_table = 'glpi_plugin_genericobject_' . \strtolower(\getPlural($classname_matches['itemtype_chunk']));

            if ($this->db->tableExists($fallback_table)) {
                return $fallback_table;
            }

            // Sometimes, multiple tranformations from singular to plural or from plural to singular produces unexpected
            // results, e.g. `getPlural('uau')` -> `uaus`, then `getSingular('uaus')` -> `uaus`.
            $fallback_table = 'glpi_plugin_genericobject_' . \strtolower(\getSingular($classname_matches['itemtype_chunk']));

            if ($this->db->tableExists($fallback_table)) {
                return $fallback_table;
            }
        }

        // Always return the expected table name, even if it does not exist.
        return $expected_table;
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
     * Return the list of genericobject main itemtypes.
     *
     * @return array<int, string>
     */
    private function getGenericObjectMainItemtypes(): array
    {
        if ($this->main_itemtypes === null) {
            $this->main_itemtypes = [];

            foreach ($this->getGenericobjectTypesIterator() as $type_data) {
                $this->main_itemtypes[] = $type_data['itemtype'];
            }
        }

        return $this->main_itemtypes;
    }

    /**
     * Check whether a field with the given name is a foreign key to a genericobject itemtype.
     */
    private function isAGenericObjectFkeyField(string $field): bool
    {
        if (!\isForeignKeyField($field)) {
            return false;
        }

        return preg_match('/^plugin_genericobject_/', $field) === 1;
    }

    /**
     * Check whether an itemtype with the given name is a genericobject custom dropdown itemtype.
     */
    private function isAGenericObjectDropdownItemtype(string $itemtype): bool
    {
        foreach ($this->getGenericObjectMainItemtypes() as $main_itemtype) {
            $generic_itemtypes = [
                $main_itemtype,
                $main_itemtype . 'Model',
                $main_itemtype . 'Type',
            ];
            if (\in_array(\strtolower($itemtype), \array_map('strtolower', $generic_itemtypes), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the target itemtype for the given genericobject plugin itemtype.
     *
     * @return class-string<CommonDBTM>
     */
    private function getTargetItemtype(string $itemtype): string
    {
        if (\array_key_exists($itemtype, $this->asset_definitions)) {
            return $this->asset_definitions[$itemtype]->getAssetClassName();
        }

        if (\array_key_exists($itemtype, $this->dropdown_definitions)) {
            return $this->dropdown_definitions[$itemtype]->getDropdownClassName();
        }

        if (preg_match('/Model$/i', $itemtype) === 1) {
            $main_itemtype = preg_replace('/Model$/i', '', $itemtype);
            if (\array_key_exists($main_itemtype, $this->asset_definitions)) {
                return $this->asset_definitions[$main_itemtype]->getAssetModelClassName();
            }
        }

        if (preg_match('/Type$/i', $itemtype) === 1) {
            $main_itemtype = preg_replace('/Type$/i', '', $itemtype);
            if (\array_key_exists($main_itemtype, $this->asset_definitions)) {
                return $this->asset_definitions[$main_itemtype]->getAssetTypeClassName();
            }
        }

        throw new LogicException(
            sprintf('Unable to find the target itemtype for `%s`.', $itemtype)
        );
    }

    /**
     * Check whether a field with the given name is a custom field.
     *
     * @param class-string<CommonDBTM> $itemtype The generic object itemtype.
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
     * @param class-string<CommonDBTM> $itemtype The generic object itemtype.
     * @param string                    $field_name The field name.
     * @param string                    $field_type The field type (fetched from a `SHOW COLUMNS FROM` query).
     *
     * @return array{system_name: string, label: string, type: class-string<AbstractType>, itemtype?: class-string<CommonDBTM>, options?: array{min?: int, max?: int, step?: int}}
     */
    private function getCustomFieldSpecs(
        string $itemtype,
        string $field_name,
        string $field_type
    ): array {
        if (!$this->isACustomField($itemtype, $field_name)) {
            throw new LogicException();
        }

        // Fallback values
        $specs = [
            'system_name' => $field_name,
            'label'       => $field_name,
            'type'        => StringType::class,
        ];

        // Native definitions from the plugin
        switch ($field_name) {
            case 'creationdate':
                $specs['label'] = __('Creation date');
                $specs['type']  = DateType::class;
                break;
            case 'expirationdate':
                $specs['label'] = __('Expiration date');
                $specs['type']  = DateType::class;
                break;
            case 'url':
                $specs['label'] = __('URL');
                $specs['type']  = URLType::class;
                break;
            case 'other':
                $specs['label'] = __('Others');
                $specs['type']  = StringType::class;
                break;
        }

        if (\isForeignKeyField($field_name)) {
            // Foreign key field
            if ($this->isAGenericObjectFkeyField($field_name)) {
                $source_type = $this->getExpectedClassNameForPluginTable(\getTableNameForForeignKeyField($field_name));
                $target_type = $this->getTargetItemtype($source_type);
            } else {
                $target_type = \getItemtypeForForeignKeyField($field_name);
                if ($target_type === null) {
                    throw new MigrationException(
                        sprintf(__('Unable to import the "%s" field.'), $field_name),
                        sprintf('Unable to import the `%s` field.', $field_name)
                    );
                }
            }
            $specs['system_name'] = $this->getTargetField($itemtype, $field_name, with_prefix: false);
            $specs['label']       = $target_type::getTypeName();
            $specs['type']        = DropdownType::class;
            $specs['itemtype']    = $target_type;
        } else {
            // Keep only the main column type by removing anything that is preceded by a space (e.g. " unsigned")
            // or a parenthesis (e.g. "(255)").
            $field_type = \strtolower(preg_replace('/^([a-z]+)([ (].+)*$/', '$1', $field_type));

            switch (true) {
                case $field_type === 'tinyint':
                    $specs['type'] = BooleanType::class;
                    break;
                case preg_match('/text$/', $field_type):
                    $specs['type'] = TextType::class;
                    break;
                case preg_match('/int$/', $field_type):
                    $specs['type'] = NumberType::class;
                    break;
                case \in_array($field_type, ['float', 'decimal']):
                    $specs['type']    = NumberType::class;
                    $specs['options'] = ['step' => 'any'];
                    break;
                case $field_type === 'date':
                    $specs['type'] = DateType::class;
                    break;
                case \in_array($field_type, ['datetime', 'timestamp']):
                    $specs['type'] = DateTimeType::class;
                    break;
                default:
                    // Keep existing specs.
                    break;
            }
        }

        // Custom definitions
        $genericobject_definitions = $this->getGenericObjectFieldsDefinition($itemtype);
        if (\array_key_exists($field_name, $genericobject_definitions)) {
            $genericobject_definition = $genericobject_definitions[$field_name];
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
                    // already handled in previous code lines
                    break;
                case 'float':
                    $specs['type'] = NumberType::class;
                    break;
                case 'integer':
                    $specs['type'] = NumberType::class;
                    $specs['options'] = [];
                    foreach (['min', 'max', 'step'] as $opt) {
                        if (\array_key_exists($opt, $genericobject_definition)) {
                            $specs['options'][$opt] = (int) $genericobject_definition[$opt];
                        }
                    }
                    break;
                case 'multitext':
                    $specs['type'] = TextType::class;
                    break;
            }

            // Compute translations
            $translations = [];
            // FIXME Fetch translations from `/files/_plugins/genericobject/locales/` files

            $specs['label']        = $genericobject_definition['name'];
            $specs['translations'] = $translations;
        }

        return $specs;
    }

    /**
     * Get the target field for a given genericobject main item field.
     *
     * @param class-string<CommonDBTM> $itemtype The generic object itemtype.
     * @param string                    $field          The field name.
     * @param bool                      $with_prefix    Whether to append the `custom_` prefix on custom fields.
     */
    private function getTargetField(string $itemtype, string $field, bool $with_prefix = true): ?string
    {
        if ($this->isAGenericField($field)) {
            return $field;
        }

        if ($this->isAGenericObjectFkeyField($field)) {
            $source_type = $this->getExpectedClassNameForPluginTable(
                \getTableNameForForeignKeyField($field)
            );
            $target_type = $this->getTargetItemtype($source_type);

            // Append the dropdown/asset suffix to prevent collisions, when an object has foreign keys
            // targeting multiple custom dropdowns classes or multiple custom assets classes,
            // due to the fact that they share the same table and therefore the same foreign key.
            $suffix = '';
            if (\is_a($target_type, Asset::class, true) || \is_a($target_type, Dropdown::class, true)) {
                $suffix = '_' . \strtolower($target_type::getDefinition()->fields['system_name']);
            }

            if (\is_a($target_type, AssetModel::class, true) || \is_a($target_type, AssetType::class, true)) {
                // The target field is native and should never have the `custom_` prefix.
                $with_prefix = false;
            }

            // Append existing suffix
            $suffix .= \str_replace(
                \getForeignKeyFieldForTable($this->getExpectedTableForPluginClassName($source_type)),
                '',
                $field
            );

            // Use `str_replace` to preserve foreign key suffixes
            return ($with_prefix ? 'custom_' : '') . \getForeignKeyFieldForItemType($target_type) . $suffix;
        }

        if ($this->isACustomField($itemtype, $field)) {
            return ($with_prefix ? 'custom_' : '') . $field;
        }

        return null;
    }

    /**
     * Get the generic object fields definition from the plugin custom files.
     *
     * @return array<string, array{name: string, input_type: string, min?: int, max?: int, step?: int}>
     */
    private function getGenericObjectFieldsDefinition(string $itemtype): array
    {
        $system_name = preg_replace('/^PluginGenericObject/', '', $itemtype);

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
