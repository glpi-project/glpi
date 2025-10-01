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

namespace Glpi\Asset;

use CommonDBTM;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\CustomFieldType\TextType;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\CustomObject\CustomObjectTrait;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;
use Glpi\Features\Inventoriable;
use Glpi\Features\StateInterface;
use Group;
use Group_Item;
use InvalidArgumentException;
use Location;
use Log;
use Manufacturer;
use RuntimeException;
use Safe\Exceptions\JsonException;
use Session;
use State;
use User;

use function Safe\json_decode;
use function Safe\json_encode;

abstract class Asset extends CommonDBTM implements AssignableItemInterface, StateInterface
{
    use CustomObjectTrait;

    use AssignableItem {
        getEmpty as getEmptyFromAssignableItem;
        post_getFromDB as post_getFromDBFromAssignableItem;
        post_addItem as post_addItemFromAssignableItem;
        post_updateItem as post_updateItemFromAssignableItem;
        canView as canViewFromAssignableItem;
    }
    use Clonable;
    use \Glpi\Features\State;
    use Inventoriable;

    /**
     * Asset definition system name.
     *
     * Must be defined here to make PHPStan happy (see https://github.com/phpstan/phpstan/issues/8808).
     * Must be defined by child class too to ensure that assigning a value to this property will affect
     * each child classe independently.
     */
    protected static string $definition_system_name;

    final public function __construct()
    {
        foreach (static::getDefinition()->getEnabledCapacities() as $capacity) {
            $capacity->onObjectInstanciation($this, static::getDefinition()->getCapacityConfiguration($capacity::class));
        }
    }

    public static function canView(): bool
    {
        if (!static::canViewFromAssignableItem()) {
            return false;
        }
        return (bool) static::getDefinition()->fields['is_active'];
    }

    /**
     * Get the asset definition related to concrete class.
     *
     * @return AssetDefinition
     */
    public static function getDefinition(): AssetDefinition
    {
        $definition = AssetDefinitionManager::getInstance()->getDefinition(static::$definition_system_name);
        if (!($definition instanceof AssetDefinition)) {
            throw new RuntimeException('Asset definition is expected to be defined in concrete class.');
        }

        return $definition;
    }

    public static function getDefinitionClassInstance(): AbstractDefinition
    {
        return new AssetDefinition();
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', static::getDefinition()->getAssetClassName()];
    }

    public function useDeletedToLockIfDynamic()
    {
        return false;
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options = array_merge($search_options, Location::rawSearchOptionsToAdd());

        $asset_model_class = static::getDefinition()->getAssetModelClassName();
        $asset_type_class = static::getDefinition()->getAssetTypeClassName();

        $search_options[] = [
            'id'            => '2',
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number',
        ];

        $search_options[] = [
            'id'        => '4',
            'table'     => $asset_type_class::getTable(),
            'field'     => 'name',
            'name'      => $asset_type_class::getTypeName(1),
            'datatype'  => 'dropdown',
            // Search class could not be able to retrieve the concrete type class when using `getItemTypeForTable()`
            // so we have to define an `itemtype` here.
            'itemtype'  => $asset_type_class,
        ];

        $search_options[] = [
            'id'        => '40',
            'table'     => $asset_model_class::getTable(),
            'field'     => 'name',
            'name'      => $asset_model_class::getTypeName(1),
            'datatype'  => 'dropdown',
            // Search class could not be able to retrieve the concrete model class when using `getItemTypeForTable()`
            // so we have to define an `itemtype` here.
            'itemtype'  => $asset_model_class,
        ];

        $search_options[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $search_options[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $search_options[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '70',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $search_options[] = [
            'id'                 => '71',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                    ],
                ],
            ],
            'datatype'           => 'dropdown',
        ];

        $search_options[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $search_options[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];


        $search_options[] = [
            'id'                 => '23',
            'table'              => Manufacturer::getTable(),
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $search_options[] = [
            'id'                 => '24',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge of the hardware'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $search_options[] = [
            'id'                 => '49',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'name'               => __('Group in charge of the hardware'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'datatype'           => 'dropdown',
        ];

        $search_options[] = [
            'id'                 => '65',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $search_options[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $search_options[] = [
            'id'                 => '250',
            'table'              => $this->getTable(),
            'field'              => AssetDefinition::getForeignKeyField(),
            'name'               => AssetDefinition::getTypeName(),
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        foreach (static::getDefinition()->getEnabledCapacities() as $capacity) {
            array_push($search_options, ...$capacity->getSearchOptions(static::class));
        }

        $search_options = $this->amendSearchOptions($search_options);

        $search_options[] = [
            'id' => 'customfields',
            'name' => _n('Custom field', 'Custom fields', Session::getPluralNumber()),
        ];
        $custom_fields = static::getDefinition()->getCustomFieldDefinitions();
        foreach ($custom_fields as $custom_field) {
            $opt = $custom_field->getFieldType()->getSearchOption();
            if ($opt !== null) {
                $opt['itemtype'] ??= static::class;
                $search_options[] = $opt;
            }
        }

        return $search_options;
    }

    public function getUnallowedFieldsForUnicity()
    {
        $not_allowed = parent::getUnallowedFieldsForUnicity();
        $not_allowed[] = AssetDefinition::getForeignKeyField();
        return $not_allowed;
    }

    public function getFormFields(): array
    {
        $all_fields = array_keys(static::getDefinition()->getAllFields());
        $fields_display = static::getDefinition()->getDecodedFieldsField();
        $shown_fields = array_column($fields_display, 'key');
        return array_filter($shown_fields, static fn($f) => in_array($f, $all_fields, true));
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $custom_fields = static::getDefinition()->getCustomFieldDefinitions();
        $custom_fields = array_combine(array_map(static fn($f) => 'custom_' . $f->fields['system_name'], $custom_fields), $custom_fields);
        $fields_display = static::getDefinition()->getDecodedFieldsField();
        $core_field_options = [];

        // Remove fields that are hidden for the current profile
        $custom_fields = array_filter($custom_fields, static fn($f) => !$f->getFieldType()->getOptionValues()['hidden']);

        $core_fields = static::getDefinition()->getAllFields();
        foreach ($fields_display as $field) {
            $f = new CustomFieldDefinition();
            $core_field = $core_fields[$field['key']];
            $f->fields['system_name'] = $field['key'];
            $f->fields['type'] = $core_field['type'];
            $f->fields['field_options'] = $field['field_options'] ?? [];
            $core_field_options[$field['key']] = $f->getFieldType()->getOptionValues();
        }

        $field_order = $this->getFormFields();
        $field_order = array_filter($field_order, static fn($f) => $core_field_options[$f]['hidden'] !== true);

        TemplateRenderer::getInstance()->display(
            'pages/assets/asset.html.twig',
            [
                'item'   => $this,
                'params' => $options,
                'custom_fields' => $custom_fields,
                'field_order' => $field_order,
                'additional_field_options' => $core_field_options,
            ]
        );
        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareGroupFields($input);
        $input = $this->handleReadonlyFieldUpdate($input);
        $input = $this->handleCustomFieldsUpdate($input);

        return $this->prepareDefinitionInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareGroupFields($input);
        $input = $this->handleReadonlyFieldUpdate($input);
        $input = $this->handleCustomFieldsUpdate($input);

        return $this->prepareDefinitionInput($input);
    }

    private function handleReadonlyFieldUpdate(array $input): array
    {
        foreach (static::getDefinition()->getDecodedFieldsField() as $field_definition) {
            $field_options = $field_definition['field_options'] ?? null;
            if (!$this->isReadonlyForCurrentProfile($field_options)) {
                continue;
            }

            $field_name = $field_definition['key'];
            $input = $this->applyPersistedReadonlyValue($input, $field_name);
        }

        // Custom fields
        foreach (static::getDefinition()->getCustomFieldDefinitions() as $custom_field) {
            $field_options = $custom_field->fields['field_options'] ?? null;
            if (!$this->isReadonlyForCurrentProfile($field_options)) {
                continue;
            }

            $field_name = 'custom_' . $custom_field->fields['system_name'];
            $input = $this->applyPersistedReadonlyValue($input, $field_name);
        }

        return $input;
    }

    /**
     * Determine if a field is readonly for the current active profile.
     */
    private function isReadonlyForCurrentProfile(mixed $field_options): bool
    {
        $profile_id = $_SESSION['glpiactiveprofile']['id'] ?? null;
        if ($profile_id === null) {
            return false;
        }

        if (!is_array($field_options) || !array_key_exists('readonly', $field_options)) {
            return false;
        }

        $readonly_profile_ids = $field_options['readonly'];
        if (is_array($readonly_profile_ids)) {
            return in_array($profile_id, $readonly_profile_ids, true);
        }

        // The normalization can return an empty string instead of an array when no profiles are set
        return false;
    }

    /**
     * Ensure the input value for a readonly field is the persisted value.
     */
    private function applyPersistedReadonlyValue(array $input, string $field_name): array
    {
        if (array_key_exists($field_name, $this->fields)) {
            $input[$field_name] = $this->fields[$field_name];
        } else {
            unset($input[$field_name]);
        }
        return $input;
    }

    protected function handleCustomFieldsUpdate(array $input): array
    {
        $custom_fields = $this->getDecodedCustomFields();

        foreach (static::getDefinition()->getCustomFieldDefinitions() as $custom_field) {
            $custom_field_name = 'custom_' . $custom_field->fields['system_name'];
            if (!isset($input[$custom_field_name])) {
                continue;
            }
            $value = $input[$custom_field_name];

            try {
                $custom_fields[$custom_field->getID()] = $custom_field->getFieldType()->formatValueForDB($value);
            } catch (InvalidArgumentException) {
                continue;
            }
        }
        $input['custom_fields'] = json_encode($custom_fields);

        return $input;
    }

    private function getDecodedCustomFields(): array
    {
        $return = [];
        try {
            $return = json_decode($this->fields['custom_fields'] ?? '[]', true);
        } catch (JsonException $e) {
            //empty catch
        }
        return $return;
    }

    public function getEmpty()
    {
        if (!$this->getEmptyFromAssignableItem()) {
            return false;
        }

        foreach (static::getDefinition()->getCustomFieldDefinitions() as $custom_field) {
            $f_name = 'custom_' . $custom_field->fields['system_name'];
            $this->fields[$f_name] = $custom_field->fields['default_value'];
        }
        return true;
    }

    public function post_getFromDB()
    {
        parent::post_getFromDB();

        $this->post_getFromDBFromAssignableItem();

        $custom_field_definitions = static::getDefinition()->getCustomFieldDefinitions();
        $custom_field_values = $this->getDecodedCustomFields();

        foreach ($custom_field_definitions as $custom_field) {
            $custom_field_name = 'custom_' . $custom_field->fields['system_name'];
            $value = $custom_field_values[$custom_field->getID()] ?? $custom_field->fields['default_value'];

            $this->fields[$custom_field_name] = $custom_field->getFieldType()->formatValueFromDB($value);
        }
    }

    public function pre_updateInDB()
    {
        parent::pre_updateInDB();
        // Fill old values for custom fields
        $custom_field_definitions = static::getDefinition()->getCustomFieldDefinitions();
        foreach ($custom_field_definitions as $custom_field) {
            $custom_field_name = 'custom_' . $custom_field->fields['system_name'];
            $this->oldvalues[$custom_field_name] = $this->fields[$custom_field_name];
        }
    }

    public function post_addItem()
    {
        $this->post_addItemFromAssignableItem();

        $this->addFilesFromRichTextCustomFields();
    }

    public function post_updateItem($history = true)
    {
        $this->post_updateItemFromAssignableItem($history);
        if ($this->dohistory && $history && in_array('custom_fields', $this->updates, true)) {
            foreach (static::getDefinition()->getCustomFieldDefinitions() as $custom_field) {
                $custom_field_name = 'custom_' . $custom_field->fields['system_name'];
                $field_type = $custom_field->getFieldType();
                $old_value = $field_type->formatValueFromDB($this->oldvalues[$custom_field_name] ?? $field_type->getDefaultValue());
                $current_value = $field_type->formatValueFromDB($this->fields[$custom_field_name] ?? null);
                $opt = $custom_field->getFieldType()->getSearchOption();

                if ($old_value !== $current_value) {
                    $dropdown = $opt['table'] !== static::getTable();
                    if ($dropdown) {
                        $old_value = $old_value !== null ? Dropdown::getDropdownName($opt['table'], $old_value) : $old_value;
                        $current_value = $current_value !== null ? Dropdown::getDropdownName($opt['table'], $current_value) : $current_value;
                    }
                    Log::history($this->getID(), static::class, [
                        $custom_field->getSearchOptionID(),
                        $old_value,
                        $current_value,
                    ]);
                }
            }
        }
        $this->addFilesFromRichTextCustomFields();
    }

    /**
     * Add files from rich text custom fields.
     */
    private function addFilesFromRichTextCustomFields(): void
    {
        $update_input = [];
        foreach (static::getDefinition()->getCustomFieldDefinitions() as $custom_field) {
            if (
                $custom_field->fields['type'] !== TextType::class
                || ($custom_field->fields['field_options']['enable_richtext'] ?? false) === false
                || ($custom_field->fields['field_options']['enable_images'] ?? false) === false
            ) {
                continue;
            }

            $custom_field_name = sprintf('custom_%s', $custom_field->fields['system_name']);
            $current_value     = $this->input[$custom_field_name];

            $result_input = $this->addFiles(
                $this->input,
                [
                    'force_update'  => false,
                    'name'          => $custom_field_name,
                    'content_field' => $custom_field_name,
                ]
            );

            if ($result_input[$custom_field_name] !== $current_value) {
                $update_input[$custom_field_name] = $result_input[$custom_field_name];
            }
        }

        if (count($update_input) > 0) {
            (new static())->update(['id' => $this->fields['id']] + $update_input, history: false);
        }
    }

    public function getNonLoggedFields(): array
    {
        $ignored_fields = array_map(
            static fn(CustomFieldDefinition $field) => 'custom_' . $field->fields['system_name'],
            static::getDefinition()->getCustomFieldDefinitions()
        );
        $ignored_fields[] = 'custom_fields';
        return $ignored_fields;
    }

    public function getCloneRelations(): array
    {
        $relations = [];
        $capacities = static::getDefinition()->getEnabledCapacities();
        foreach ($capacities as $capacity) {
            $relations = [...$relations, ...$capacity->getCloneRelations()];
        }
        return array_unique($relations);
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        return static::getDefinition()->getSystemSQLCriteriaForConcreteClass($tablename);
    }
}
